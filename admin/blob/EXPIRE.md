# Blob expiration and cleanup

Tsugi stores blob metadata in the database (primarily `blob_file`, and optionally `blob_blob` for legacy or DB-backed content) and stores file bytes under `$CFG->dataroot` in the usual two-level SHA-256 directory layout. Over time, the database and the filesystem can get out of sync. Cleanup is usually done in one of two directions: **top-down** (metadata and lifecycle events first) or **bottom-up** (filesystem age first).

See also [README.md](README.md) for how the three stores relate, **recommended run order** (`clean_blob_file.php` → `clean_dataroot_blobs.php` → `clean_blob_blob.php`), and sample runs of those scripts.

To print the configured dataroot path from the same `config.php` the app uses (CLI only), `cd` into `tsugi/admin/blob` and run `php show_dataroot.php`. Use it to set a shell variable, for example `DATAROOT=$(php show_dataroot.php)` (still from that directory).

```bash
cd /path/to/tsugi/admin/blob
php show_dataroot.php
# DATAROOT=$(php show_dataroot.php)
```

---

## Top-down cleanup (lifecycle-driven orphans)

**What happens.** Users, contexts, tenants, or links are removed or expire according to application or institutional policy. Row deletes in `lti_context`, `lti_user`, or related tables may cascade or be paired with deletes of `blob_file` rows. Any path that removes `blob_file` rows without deleting the corresponding object under `$CFG->dataroot` leaves **orphan files on disk**: bytes that no longer have a row pointing at them.

**Why it matters.** The canonical index for “this file should exist on disk” is `blob_file` (via `file_sha256` / `path`). If the row is gone, the file is safe to remove from a **referential** standpoint, subject to your own retention rules.

**Operational pattern.** On a schedule (for example monthly), scan `$CFG->dataroot` and remove files that are **not** referenced by any row in `blob_file`. In this tree, `clean_dataroot_blobs.php` implements that scan: it walks the hashed layout, checks each candidate file against `blob_file`, and can remove unreferenced files (dry run by default; `remove` to apply).

```bash
cd /path/to/tsugi/admin/blob
php clean_dataroot_blobs.php              # dry run: lists rm targets
php clean_dataroot_blobs.php verbose      # dry run with per-file OK lines
php clean_dataroot_blobs.php remove       # actually unlink files / empty dirs
```

After removing orphan files, empty directories may remain; `clean_dataroot_blobs.php` also removes empty leaf directories when it is safe to do so.

**`clean_dataroot_blobs.php` guard.** Before scanning disk, the script runs a SQL check: if any `blob_file.path` is an **absolute** path that does **not** lie under the current `$CFG->dataroot`, it **exits with an error** (with sample `file_id`s). Relative paths under dataroot are fine. Fix or remove those rows with **`clean_blob_file.php`** first so you do not delete on-disk blobs that are still tied to legacy path strings.

**Order of operations (typical top-down).**

1. Application or admin workflows delete or expire contexts/users/tenants as policy requires (including `blob_file` cleanup if your code does that).
2. Run **`clean_blob_file.php`** (dry run, then **`apply`**) so `path` values match the current dataroot and dangling rows are removed — especially if you ever changed dataroot or rely on “last three path segments” remapping in serve code.
3. Run **`clean_dataroot_blobs.php`** (dry run, then `remove`) to drop disk files with no `blob_file` reference (by `file_sha256`).
4. Optionally run **`clean_blob_blob.php`** to remove rows in `blob_blob` that no longer have any `blob_file` pointing at them (see README for when `blob_blob` is in use).

```bash
cd /path/to/tsugi/admin/blob
php clean_blob_file.php                   # dry run
php clean_blob_file.php apply             # fix paths + delete unresolvable rows
php clean_dataroot_blobs.php              # dry run: disk orphans
php clean_dataroot_blobs.php remove
php clean_blob_blob.php                   # dry run: orphan blob_blob rows
php clean_blob_blob.php remove            # delete those blob_blob rows
```

---

## Bottom-up cleanup (age-driven, then reconcile the database)

**What happens.** Some sites prefer to cap **physical** retention regardless of whether rows still exist: for example, delete objects under `$CFG->dataroot` that have been neither read nor modified for a long time. A common pattern is to use **`find`** on the dataroot tree with predicates on **atime** and **mtime** together (e.g. both older than about **800 days**), so a file is removed only if it appears both unread and unmodified for that period. The exact threshold is a policy choice.

**Caveats.**

- **atime** may be disabled or not what you expect (`noatime`, relatime, backup scans, etc.). Treat age-based deletion as policy-sensitive; dry-run and sample before wide `remove`.
- Restrict `find` to the Tsugi blob tree under `$CFG->dataroot` so you do not touch unrelated files.

**Operational pattern.**

1. List files that match your age rules, then **preview** `rm` commands (this does not delete anything until you run the generated lines yourself).

   `find` measures **days**; `-mtime +800` and `-atime +800` mean last modified and last accessed are **more than 800 days** ago. Both must match.

   ```bash
   cd /path/to/tsugi/admin/blob
   php show_dataroot.php   

   DATAROOT=/efs/sites/www.ziggy.com

   find "$DATAROOT" -type f -mtime +800 -atime +800 -print \
     | awk '{print "rm -f --", $0}'
   ```

   Tsugi blob leaves use hex SHA-256 names (no spaces), so the above is usually enough. If paths might contain spaces or odd characters, use null-delimited output:

   ```bash
   find "$DATAROOT" -type f -mtime +800 -atime +800 -print0 \
     | awk -v RS='\0' '{printf "rm -f -- \047%s\047\n", $0}'
   ```

   Turn the preview into a script, review it, then execute when satisfied:

   ```bash
   find "$DATAROOT" -type f -mtime +800 -atime +800 -print \
     | awk '{print "rm -f --", $0}' > /tmp/prune-old-blobs.sh
   chmod +x /tmp/prune-old-blobs.sh
   less /tmp/prune-old-blobs.sh
   # sh -x /tmp/prune-old-blobs.sh
   ```

2. **Reconcile `blob_file` with disk** (same order as top-down): run **`clean_blob_file.php`** (dry run, then **`apply`**). That removes rows whose files are gone after the `find` / `rm` pass and fixes legacy `path` prefixes so `file_sha256` lookups match the tree. This is the mirror of **`clean_dataroot_blobs.php`**: here you drop DB rows with no file; there you drop disk files with no row.

3. **Filesystem orphans:** run **`clean_dataroot_blobs.php`** (dry run, then `remove`) so files still on disk without any `blob_file` reference are removed.

4. If you use **`blob_blob`**, run **`clean_blob_blob.php`** (dry run, then `remove`) for rows no longer referenced by `blob_file`.

```bash
cd /path/to/tsugi/admin/blob
php clean_blob_file.php
php clean_blob_file.php apply
php clean_dataroot_blobs.php
php clean_dataroot_blobs.php remove
php clean_blob_blob.php
php clean_blob_blob.php remove
```

---

## Summary

| Direction | Trigger | Filesystem | Database |
|-----------|---------|------------|----------|
| Top-down | Context/user/tenant expiry; `blob_file` rows removed | After **`clean_blob_file.php`**: orphans via **`clean_dataroot_blobs.php`** (files with no `blob_file` SHA match) | **`clean_blob_file.php`** first if paths may be legacy; then optional **`clean_blob_blob.php`** |
| Bottom-up | Age policy (`find` on atime **and** mtime) | Delete old files under `$CFG->dataroot` | **`clean_blob_file.php`** (drop rows for missing files, fix paths); then **`clean_dataroot_blobs.php`**; then **`clean_blob_blob.php`** if applicable |

Use **top-down** when the source of truth is the LMS or Tsugi lifecycle. Use **bottom-up** when the source of truth is a fixed retention window on disk. Many production setups combine both: periodic age pruning plus regular orphan sweeps after large context purges.

Quick reference (always from `tsugi/admin/blob`; order matters — **`clean_blob_file`** before **`clean_dataroot_blobs`**):

```bash
php show_dataroot.php
php clean_blob_file.php
php clean_blob_file.php apply
php clean_dataroot_blobs.php
php clean_dataroot_blobs.php remove
php clean_blob_blob.php
php clean_blob_blob.php remove
```

### Experience 2026-04-04

So I cleaned these files up and applied them. The first step was to run `clean_blob_file.php`.
This deleted entries from `blob_file` that I manually expired from disk a long time
ago but had left hanging.

    MISSING file_id=504184 sha256=54ecc17a7740660dab8afbba328272e139c319f2a75efc4415c01d50545c4919
      stored_path: /efs/tsugi_blobs/54/ec/54ecc17a7740660dab8afbba328272e139c319f2a75efc4415c01d50545c4919
    DELETE blob_file file_id=504184 sha256=54ecc17a7740660dab8afbba328272e139c319f2a75efc4415c01d50545c4919
      path=/efs/tsugi_blobs/54/ec/54ecc17a7740660dab8afbba328272e139c319f2a75efc4415c01d50545c4919

Also I had moved servers and not updated the blob paths because `blob_serve.php` automatically
served from the new paths using the last three bits of the path. `clean_blob_file.php`
also checks and changes the paths to the currennt `dataroot` like this:

  MISMATCH file_id=465189
    sha256=ba53dfe9874b66646843b1bfa5a31770f003866bbd3bf1124076528c90024e58
    stored_path: /efs/blobs/ba/53/ba53dfe9874b66646843b1bfa5a31770f003866bbd3bf1124076528c90024e58
    UPDATED path -> /efs/sites/www.py4e.com/ba/53/ba53dfe9874b66646843b1bfa5a31770f003866bbd3bf1124076528c90024e58

Then I ran `clean_dataroot_blobs.php` to scan the dataroot for blobs that were in
the `dataroot` but not in the `blob_file` table and remove them from `dataroot`.

Last, I cleaned up peer grading submissions pointing at no longer existent files.

    cd mod/peer-grade
    php cleanup_peer_submit_missing_blobs.php

This might delete some files that might be left hanging.   So we might want to rerun the above two steps.

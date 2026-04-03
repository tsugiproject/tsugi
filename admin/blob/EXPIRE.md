# Blob expiration and cleanup

Tsugi stores blob metadata in the database (primarily `blob_file`, and optionally `blob_blob` for legacy or DB-backed content) and stores file bytes under `$CFG->dataroot` in the usual two-level SHA-256 directory layout. Over time, the database and the filesystem can get out of sync. Cleanup is usually done in one of two directions: **top-down** (metadata and lifecycle events first) or **bottom-up** (filesystem age first).

See also [README.md](README.md) for how the three stores relate, and the sample runs of `clean_dataroot_blobs.php`, `clean_blob_file.php`, and `clean_blob_blob.php`.

---

## Top-down cleanup (lifecycle-driven orphans)

**What happens.** Users, contexts, tenants, or links are removed or expire according to application or institutional policy. Row deletes in `lti_context`, `lti_user`, or related tables may cascade or be paired with deletes of `blob_file` rows. Any path that removes `blob_file` rows without deleting the corresponding object under `$CFG->dataroot` leaves **orphan files on disk**: bytes that no longer have a row pointing at them.

**Why it matters.** The canonical index for “this file should exist on disk” is `blob_file` (via `file_sha256` / `path`). If the row is gone, the file is safe to remove from a **referential** standpoint, subject to your own retention rules.

**Operational pattern.** On a schedule (for example monthly), scan `$CFG->dataroot` and remove files that are **not** referenced by any row in `blob_file`. In this tree, `clean_dataroot_blobs.php` implements that scan: it walks the hashed layout, checks each candidate file against `blob_file`, and can remove unreferenced files (dry run by default; `remove` to apply).

After removing orphan files, empty directories may remain; `clean_dataroot_blobs.php` also removes empty leaf directories when it is safe to do so.

**Order of operations (typical).**

1. Application or admin workflows delete or expire contexts/users/tenants as policy requires (including `blob_file` cleanup if your code does that).
2. Run `clean_dataroot_blobs.php` (dry run, then `remove`) to drop disk files with no `blob_file` reference.
3. Optionally run `clean_blob_blob.php` to remove rows in `blob_blob` that no longer have any `blob_file` pointing at them (see README for when `blob_blob` is in use).

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
   grep dataroot ../../config.php    # Make sure there is a $CFG->dataroot
   DATAROOT=/path/to/your/dataroot   # same as $CFG->dataroot

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

2. **Reconcile the database:** scan `blob_file` (and `blob_blob` if used) and delete rows whose backing file no longer exists on disk. This step is the mirror image of top-down `clean_dataroot_blobs.php`: there you delete disk files with no row; here you delete rows whose `path` no longer references a file.

The **`clean_blob_file.php`** script does that: it removes `blob_file` rows with a non-empty `path` where the path does not exist on disk (dry run by default; `remove` to apply). It complements **`clean_dataroot_blobs.php`**, which removes **disk** files that have no `blob_file` row. Run it after age-based or manual file deletion under `$CFG->dataroot`, then consider **`clean_blob_blob.php`** for orphaned `blob_blob` rows.

3. After DB rows for missing files are removed, run `clean_blob_blob.php` if you use `blob_blob`, so unreferenced blob content rows are removed.

---

## Summary

| Direction | Trigger | Filesystem | Database |
|-----------|---------|------------|----------|
| Top-down | Context/user/tenant expiry; `blob_file` rows removed | Orphans: delete files with **no** `blob_file` reference (`clean_dataroot_blobs.php`) | Already updated by lifecycle; optional `clean_blob_blob.php` for `blob_blob` |
| Bottom-up | Age policy (`find` on atime **and** mtime) | Delete old files under `$CFG->dataroot` | Remove `blob_file` rows with missing backing files (`clean_blob_file.php`); then `clean_blob_blob.php` if applicable |

Use **top-down** when the source of truth is the LMS or Tsugi lifecycle. Use **bottom-up** when the source of truth is a fixed retention window on disk. Many production setups combine both: periodic age pruning plus regular orphan sweeps after large context purges.

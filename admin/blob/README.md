
All About Blobs
---------------

In Tsugi, there are three places where blobs are described / stored.

* The `blob_file` table which has information about the context, link, file name,
media type, etc for a file.  Everything but the blob itself.

* The `blob_blob` table which is basically for blob content indexed by the `blob_id`
and `blob_sha256`.   There is only one blob for each sha256 value - so there might be
more than one `blob_file` entry pointing to a `blob_id` in the `blob_blob` table.
This is how Tsugi implements single instance store.

* The `$CFG->dataroot` area on disk.  This stores blobs in a folder structure based
on the sha256 of the blob.  There is a two-level folder hierarchy based on the sha256.
The top folder is the first two characters in the sha256 and the second folder is characters
2-3 and the file name is the entire sha256.  This also is a single instance store
so more than one `blob_file`entry can point to one file on disk through the `path` column.

In the versions of Tsugi before 2018-02, the blobs were stored in the `content` column
in the `blob_file` table, indexed by `context_id` but not `link_id`.  It was a weird
single instance within course structure.  The approach after 2018-02 is single-instance
across the system and `blob_file` indexed by both `context_id` and `link_id`.

In the post 2018-02 Tsugi we only store blobs in `blob_blob` or `$CFG->dataroot` but
the Access code serves from any of the three locations.   Eventually in time, we will
migrate all blobs stored in `blob_file` into `blob_blob` and make the `blob_file.content`
column obsolete.

Recommended cleanup run order (disk-backed blobs)
------------------------------------------------

Run these from `tsugi/admin/blob` when reconciling the database with `$CFG->dataroot`:

1. **`clean_blob_file.php`** — Dry run first, then **`apply`** (or **`remove`** / **`fix`**, same thing) when you are satisfied. This updates legacy absolute `path` values to the current dataroot, deletes `blob_file` rows whose file cannot be resolved on disk, and is the step that gives you a “clean bill of health” for path metadata.

2. **`clean_dataroot_blobs.php`** — Only **after** step 1. It walks the dataroot tree and removes **files** that have no matching `blob_file` row (by `file_sha256`). If any `blob_file.path` is still an absolute path **outside** the current `$CFG->dataroot`, the script **refuses to run** and tells you to fix the database with `clean_blob_file.php` first — otherwise you risk deleting bytes that are still referenced via old path prefixes while the SHA lookup misses them.

3. **`clean_blob_blob.php`** — After the above, if you use `blob_blob`, remove orphan rows that no longer have any `blob_file` reference.

Bottom-up flows (e.g. age-based `find` deleting files under dataroot) should run **`clean_blob_file.php`** after files are removed from disk, **before** `clean_dataroot_blobs.php`, for the same reason.

Test Harness
------------

The easiest way to test the blob store is to use the blob sample code from:

    https://github.com/tsugiproject/tsugi-php-samples

Sweet test script to fake legagy blobs
--------------------------------------

Upload some files into `blob_blob` (i.e. `$CFG->dataroot` is not set) and then 
run this to get some "legacy" files with content in `blob_file`.

Don't run this on a production database:

    UPDATE blob_file, blob_blob SET blob_file.content=blob_blob.content
      WHERE blob_file.blob_id = blob_blob.blob_id ;
    UPDATE blob_file SET blob_id=null; 
    DELETE from blob_blob;

Then you can test migration from legacy `blob_file` to `blob_blob`.

Sample Executions of Admin Scripts in admin/blob
------------------------------------------------

(Prefer the **Recommended cleanup run order** above: `clean_blob_file` → `clean_dataroot_blobs` → `clean_blob_blob`.)

    $ php clean_blob_blob.php
    This is a dry run, use 'php clean_blob_blob.php remove' to actually remove the rows.
    DELETE blob_blob blob_id=4 69893b55bd8c9c5c53df72e3ea7e325cd2df8729d87b4dedce43630d668e6e1b
    # orphan blob_blob rows found=1 delete=1

    $ php clean_blob_blob.php remove
    This IS NOT A DRILL!
    ...
    DELETE blob_blob blob_id=4 69893b55bd8c9c5c53df72e3ea7e325cd2df8729d87b4dedce43630d668e6e1b
    # orphan blob_blob rows found=1 delete=1

    $ php clean_blob_blob.php
    This is a dry run, use 'php clean_blob_blob.php remove' to actually remove the rows.
    # orphan blob_blob rows found=0 delete=0

    $ php clean_blob_file.php
    Dry run: MISSING = no file on disk (would delete on apply); MISMATCH = legacy path (would update on apply).
    Run: php clean_blob_file.php apply   (remove/fix are aliases for apply)
    MISSING file_id=12 sha256=abc...
      stored_path=/old/root/aa/bb/hash...
      literal_absolute=... (missing); could not resolve on disk ...
    DELETE blob_file file_id=12 sha256=abc... path=/old/root/aa/bb/hash...
    # blob_file rows checked=50 missing_unresolvable=1 prefix_mismatch=0 would_delete=1 would_update=0

    $ php clean_blob_file.php apply
    This IS NOT A DRILL!
    ...
    # blob_file rows checked=50 missing_unresolvable=1 prefix_mismatch=0 deleted=1 updated=0 skipped_bad_hash=0

    $ php clean_dataroot_blobs.php
    This is a dry run, use 'php clean_dataroot_blobs.php remove' to actually remove the files.
    rm /Users/csev/tsugi_blobs/7c/95/7c954...98548f526218ef633152934334967
    This is a dry run, use 'php clean_dataroot_blobs.php remove' to actually remove the files.
    # folders scan=4 skip=0 rm=0
    # files scan=2 skip=0 good=1 rm=1

    $ php clean_dataroot_blobs.php remove
    This IS NOT A DRILL!
    ...
    rm /Users/csev/tsugi_blobs/7c/95/7c954...98548f526218ef633152934334967
    rmdir /Users/csev/tsugi_blobs/7c/95
    rmdir /Users/csev/tsugi_blobs/7c
    # folders scan=4 skip=0 rm=2
    # files scan=2 skip=0 good=1 rm=1

    $ php clean_dataroot_blobs.php
    This is a dry run, use 'php clean_dataroot_blobs.php remove' to actually remove the files.
    This is a dry run, use 'php clean_dataroot_blobs.php remove' to actually remove the files.
    # folders scan=2 skip=0 rm=0
    # files scan=1 skip=0 good=1 rm=0


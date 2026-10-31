# LTI maintenance

## When to run `upward.php`

Manage grade book labels a column **LMS** when that link can send a grade back through LTI 1.1. The label used to be decided by scanning `lti_result` for a row with `service_id` and a non-empty `sourcedid`. On a large course with no such rows, that scan was the slow part of the page.

`lti_link.upward` stores the answer on the link. A launch sets it when that launch writes a result that has both a `service_id` and a non-empty `sourcedid`. Links that already had those results stay 0, so their Source label stays **Line item** until a student launches again or this script sets the flag.

Run the script once on a site when you want those existing columns labeled without waiting for launches. You do not need it for new launches. Grades, resend, and the student grade list still read `sourcedid` and `service_id` on the result. The flag is only the Source label, and only for a column that is not already a quiz or a lesson item.

Run `php admin/upgrade.php` before the first run. The script stops if `lti_link.upward` is missing.

## What a run does

It walks one course at a time, then one link that is still `upward = 0`. A link is set when any non-deleted result on it has a `service_id` and a non-empty `sourcedid`. The script only sets the flag to 1. It never clears it, and it does not look at links that are already 1.

Each statement auto-commits. Nothing stays open across a pause. `--sleep` (seconds after each course, default 1) and `--pause` (milliseconds after each link, default 200) only keep the load down. `--sleep=0 --pause=0` is safe.

A later run sets nothing new. It skips the links already marked and checks the ones that are still 0.

From the site's Tsugi checkout:

```
cd admin/lti-maint
php upward.php
php upward.php apply --sleep=0 --pause=0
```

The first command is a dry run. `apply` writes. It prints a warning and waits ten seconds before the first course. That wait is not included in `elapsed`.

```
php upward.php apply --context=38
php upward.php --after=1000 --limit=25
```

`--context` is one course and ignores `--after` and `--limit`. `--after` resumes after that `context_id`. `--limit` stops after that many courses and prints the next command.

A course line is printed when that course still has links with `upward = 0`. The last line is the total: courses visited, links checked, links set, and elapsed time. A `SET` line uses the link title, which is often empty on an LMS activity. The course title is on that course's summary line.

## When to run `backfill-guid.php`

`lti_user`, `lti_context`, `lti_key`, and `lti_link` each have a nullable `guid` column. New installs set it on the starter `lti_key` rows. An upgrade adds the column and leaves existing rows `NULL`. This script fills those `NULL` values. It does not replace a guid that is already set.

Run `php admin/upgrade.php` first. The script stops if a `guid` column is missing. It is safe to stop and run again.

Each row is its own `UPDATE`. The guid comes from `UUID()` and is stored as a literal, so a replica keeps the same bytes. The script commits after each batch. The default batch is 1000 and the maximum is 10000. A batch holds locks on the rows it updates until that commit. `--seconds=15` stops the run at 15 seconds and reports how far it got. The admin Backfill button runs for up to 15 seconds or 5000 rows, whichever comes first, commits about every 1000 rows, and says how far it got. A server older than MySQL 8.0.16, or older than MariaDB 10.3.2, prints a warning and continues. The upgrade adds the column with `ALGORITHM=INSTANT` on MySQL 8.0.12 and newer, Aurora MySQL 3, and MariaDB 10.3.2 and newer, and with `ALGORITHM=INPLACE, LOCK=NONE` on MySQL 5.6, 5.7, and MySQL 8.0 before 8.0.12. Any other server, such as MySQL 5.5 or MariaDB before 10.3.2, gets a plain `ALTER TABLE`, and that copy can lock the table.

From the site's Tsugi checkout:

```
cd admin/lti-maint
php backfill-guid.php --dry-run
php backfill-guid.php
```

`--dry-run` counts `NULL` rows and writes nothing. The default batch size is 1000. The maximum is 10000.

```
php backfill-guid.php --table=lti_user --batch-size=1000
php backfill-guid.php --seconds=15
```

`--table` is one of `lti_user`, `lti_context`, `lti_key`, or `lti_link`. Omit it to process all four. Each batch is its own transaction and holds locks on the updated rows until it commits. A later run skips rows that already have a guid.

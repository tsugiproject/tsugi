# Google Classroom: what is there, and what to build next

Stored 2026-10-27. This is a reading of the code that is still in the tree. It is not an implementation plan with diffs. The goal of the next build is a teacher control panel, in the same spirit as the Tsugi Plus grade book log: the teacher can see that Classroom is awake, see what failed, and press a button to reconnect and send the grades that did not go.

File-based lessons (`$CFG->lessons`, no `manifest_id`) are the content source this integration already uses. Keeping it that way for the first cut matches the code and is enough.

## Verdict

C+ is the right grade.

The happy path is a complete integration, not a sketch. A teacher connects once, installs a lesson tool as a Classroom assignment, a student opens that assignment, and a score in Tsugi is patched onto the Classroom submission. That path was exercised recently and the pieces line up.

It stays a C+ because a failed refresh is silent, destructive, and aimed at the student. The teacher has no status, no log, and no "wake this up" button short of Settings → Connect to Google Classroom, which itself can wipe the token before the teacher understands why. Plus already solved the matching problem for an LMS: keep the local grade, record the failure, show it on a grade book tab, and let the teacher try again.

`ConfigInfo` still labels this "an experiment and is no longer supported." The routes, schema, and lessons icons are live whenever `$CFG->google_classroom_secret` is set.

## What the code does

Google login and Google Classroom are two OAuth dances on the same client id. Login (`GoogleLoginService`, redirect back to the login URL) identifies the person and opens the site session. Classroom (`/gclass/login`) asks for Classroom scopes and stores a token on that person's `lti_user` row. A person can be logged in and still be "not connected" to Classroom.

### Connect

Settings → Connect to Google Classroom (`Settings::gclassLogin`) sends the browser to `/gclass/login`.

`GoogleClassroom::getClient` builds a `Google_Client` with `access_type=offline` and these scopes:

- courses.readonly
- rosters.readonly
- profile.emails
- profile.photos
- coursework.students (create coursework and patch grades)

The redirect URI is `$CFG->wwwroot/gclass/login`. There is no `prompt=consent`. Google only returns a `refresh_token` on the first consent for that client. A later visit that already has a refresh token just refreshes. A stored blob with no `refresh_token` is rejected, and the message tells the teacher to revoke the app at myaccount.google.com and connect again.

On success the controller lists up to 100 courses, stores the token JSON in the session (encrypted) and on `lti_user.gc_token` (plaintext), sets `$_SESSION['gc_count']`, and redirects into file-based lessons with `nostyle=yes`.

### Install

With `gc_count` set, the nostyle lessons page puts a Classroom icon on each tool and discussion. The icon opens `/gclass/assign?rlid=...` in an iframe. The store detail page has the same Install button for a tool URL (`?lti=&title=`).

Assign resolves the lesson item, lists the teacher's courses, and on submit:

1. Builds a context key `gclass:{courseId}:{6-char sig}`. The sig is the first 6 hex chars of SHA-256 of `$CFG->google_classroom_secret`, the course id, and the teacher's user id. `$CFG->google_classroom_secret` is a Tsugi signing secret, not a Google secret. Do not change it after launch URLs exist.
2. Inserts `lti_context` if needed (`gc_secret`, `user_id` = the teacher, `key_id` from the teacher's current session). One Google course is one Tsugi context, owned by that teacher.
3. Inserts the teacher as an instructor membership.
4. Inserts `lti_link` whose `path` is the tool launch URL with `inherit={lesson resource_link_id}`. The lesson JSON stays the assignment definition. The new link does not copy the lesson into a manifest.
5. Creates a published Classroom coursework item, type ASSIGNMENT, max points 100, with one link material. The link URL is `/gclass/launch/{courseId}:{userSig}:{linkId}:{linkSig}`.
6. Rewrites `lti_link.link_key` to the Classroom coursework id.

Non-graded nostyle URLs use the old `g:sharetoclassroom` widget instead. `/gclass/share` is a stub page with that same widget. Graded tools do not go through it.

### Student launch

`/gclass/launch/{resource}` is not an LTI launch. The signed path is the credential.

The student must already be logged into Tsugi. If not, the return URL is this launch path. The email on the Tsugi account is how Classroom membership is checked.

The handler loads the context and link, checks both mini-signatures, then loads the **teacher's** token (`lti_context.user_id`) and calls Google:

- If this student has no membership yet, GET `courses/{id}/students/{email}` with the teacher's access token. A hit stores `lti_membership` with role learner and `json.student_id`. The course owner launching their own course is treated as instructor when the emails match.
- Lists student submissions for that coursework and student, and stores the first submission id on `lti_result.gc_submit_id`.

It then writes a synthetic `$_SESSION['lti']` (user, context, link, result, and `gc_owner_id`, `gc_course`, `gc_coursework`, `gc_submit_id`) and redirects to the tool URL with the session id. `?inherit=` on that URL makes the tool read its config from the file-based lesson.

A Google Web Preview user agent, when `$CFG->google_classroom_logo` is set, returns the logo and does not launch. That is the coursework thumbnail path.

### Grade send

`Result::gradeSendStatic` writes the 0–1 grade locally first. If the session has `gc_submit_id`, it does not use LTI 1.1 or LTI Advantage. It calls `GoogleClassroom::gradeSend(intval($grade * 100))`.

That loads the teacher's token again, refreshes if the access token is expired, and patches the student submission:

- `assignedGrade` and `draftGrade` set to the integer 0–100
- `updateMask` is `assignedGrade,draftGrade`
- coursework was created with `maxPoints` 100, so the integer matches

`setState('TURNED_IN')` is set on the object and then omitted from the mask, so Classroom does not receive a state change.

A string return becomes the student's flash: "Grade not sent: …". The local row is already updated. Nothing writes `lti_result.server_status`. Nothing writes a log row. `server_grade` is not set by this path.

## Where it lives

| Piece | Path |
| --- | --- |
| HTTP: login, assign, launch, share | `lib/src/Controllers/GoogleClassroom.php` |
| Token, refresh, grade patch | `lib/src/Google/GoogleClassroom.php` |
| Route mount | `lib/src/Controllers/Tsugi.php` |
| Settings link | `lib/src/Controllers/Settings.php` (`gclassLogin`, the experimental list item) |
| Install icons | `lib/src/Controllers/Lessons.php` (nostyle + `gc_count`), `store/details.php` |
| Grade fork | `lib/src/Core/Result.php` |
| Columns | `lti_user.gc_token`, `lti_context.gc_secret`, `lti_result.gc_submit_id` in `lib/src/Services/Lti/database.php` |
| Feature flag | `$CFG->google_classroom_secret` and `$CFG->google_classroom_logo` in `lib/src/Config/ConfigInfo.php` |
| Route test | `lib/tests/Controllers/GoogleClassroomControllerTest.php` (paths only) |

Admin context lists label a row Google when `gc_secret` is set, LTI otherwise.

## Why the token vanishes

The access token is short-lived. The refresh token is the real credential, and it lives in `lti_user.gc_token` as one JSON blob for every course that teacher installed. One bad refresh takes down every Classroom course they own.

`getClient` does this on every student launch and every grade send, with no lock:

1. If the access token is expired, call `fetchAccessTokenWithRefreshToken`.
2. JSON-encode whatever `getAccessToken()` returns.
3. If that string differs from the stored blob, UPDATE `lti_user.gc_token`.
4. Return the client even when the refresh call failed.

A failed refresh from the PHP client is an error array, often with no `refresh_token`. Step 3 then replaces a working blob with that error. The next call hits the "no refresh_token" branch and tells someone to revoke the app. That someone is usually the student, whose flash says "contact your instructor."

Several students submitting after the hour-long access token expires will all refresh the same row. If Google rotates the refresh token, the second writer can store a blob the first writer already invalidated. That is the mid-send failure.

Other ways the refresh token dies, even with correct code:

- The OAuth client is in testing, or the Classroom scope is not verified. Google expires those refresh tokens in about 7 days.
- The teacher revokes the app, changes the Google account's access, or Google drops an unused refresh token (on the order of six months).
- More than one consent flow for the same client drops an older refresh token. Login and Classroom are separate redirects, but they share the client id.

`/gclass/login` can recover. If `courses.list` throws, the controller destroys the token and sends the teacher back through consent. That only helps if the teacher knows to open Settings. A student grade send does not catch `Google_Service_Exception`, so a 401 during the patch is an uncaught error after the local grade was saved.

`retrieve_instructor_token` calls `destroy_access_token()` when the blob has no refresh token. The method that exists is `destroy_instructor_token()`. That call is a fatal error on the bad-token path.

The full token JSON, including the refresh token, is written to the PHP error log on update and on failure.

## Other defects that matter before a teacher UI

- The second "bad token" check inside `getClient` (after refresh) looks at the old blob, which has already returned false if it lacked a refresh token. It never protects the database from a bad new blob.
- Launch copies `$row['key_key']` into the session secret. The query selects `K.secret AS key_secret`. A tool that needs the tenant secret on a Classroom launch gets the consumer key instead.
- The membership upsert does not update `json` on duplicate key. A membership row created without `student_id` stays that way, and later launches cannot find the submission.
- Submission list uses `$submissions[0]` with no empty check.
- Grade send does not check the patch response. `intval($grade * 100)` truncates, so 0.999 becomes 99.
- Course list is one page of 100. There is no `pageToken` loop.
- `gc_count` is session-only. A new browser session looks disconnected until the teacher opens Connect again, even when `gc_token` is still good.
- There is no test of token refresh, coursework create, launch, or grade patch.

## What is solid and should stay

- File-based lessons as the catalog, `inherit=` as the way a Classroom link finds its tool config.
- One `lti_context` per Google course, owned by the teacher (`user_id`, `gc_secret`). Grades already land on that context's `lti_link` / `lti_result` rows, not on the site home course.
- The teacher's token grades the student. Classroom does not give the student a token that can patch their own grade. That part is right.
- Signed launch paths instead of a second LTI handshake.
- Local grade written before the outbound call. Plus does the same. The hole is that Classroom never records whether the outbound call worked.

Do not start by moving this onto manifest courses. The install UI, the `inherit` URL, and the separate context are the file-based design. A manifest course can wait.

## Control panel

Plus is the model, not a template to copy blindly.

Plus keeps `lti_result.server_status` for the last line-item or score error, clears it when a send works, and appends `lti_context_plus_log` (kind, success, HTTP status, URL, scrubbed body). The grade book kebab shows Server grade and Server error. Manage grade book can create a line item before any score. Grade book log lists the recent calls. There is no background retry. The teacher presses the button.

Classroom needs the same three facts: is the connection alive, did this score reach Google, and what do I press when it did not.

### Two layers

The token is per teacher. The coursework and the results are per Google course. The panel has to show both.

**Account.** Connected, or needs reconnect. Last successful Classroom call. A Reconnect button. Reconnect is `/gclass/login` with consent forced when the stored refresh token is missing or the last refresh failed, so Google issues a new refresh token without making the teacher revoke the app first. This status is the same on every course that teacher owns. Settings can keep the current Connect link. The course panel should show the same state, because that is where the teacher looks when a grade is missing.

**Course.** This is the `lti_context` created at install time (`gc_secret` set). Its grade book is the right surface, as another tab next to Grades, in the same place Plus puts Grade book log. The site home course's grade book is the wrong surface: those rows are not the Classroom results.

The tab should show:

- Connection status for the owning teacher, and Reconnect.
- Assignments already installed: link title, Classroom coursework id (`link_key`), max points. Creating the coursework at install time is the line-item create. The tab lists them. It does not need a separate "create line item" for the first cut, because assign already creates the coursework. A later button can repair a link whose coursework create failed halfway (Tsugi link row exists, Google object does not, or the reverse).
- Students and scores already in `lti_result`, with a Classroom column: sent, queued, or the last error. On success set `server_grade` and `retrieved_at` and clear `server_status`, the same way Plus does.
- The queue. A failed or blocked grade send appends a queue row for that result (course, link, user, grade, submission id). A later send for the same result updates that row instead of stacking duplicates. The teacher presses Send queue. That walks pending rows once, under the token lock, and does not run in the background. Success removes the row from the queue. Failure leaves it and records the error on the row.
- Reset. A teacher action for testing: put the queue back to pending (clear the last error, keep the grade), and a separate reconnect when the token itself is dead. Reset does not call Google. Send queue does.
- The log. Append-only, one row per attempt (reconnect, coursework create, score). Same shape as `lti_context_plus_log`: kind, success, status, URL, scrubbed detail. Do not store the token. The log viewer is a list on this tab, newest first, errors called out. The queue is what is still waiting. The log is what already happened. They stay separate tables so a reset of the queue does not erase history.

The teacher reaches that context from Settings → My Contexts today (they own it via `user_id`). The first UI cut should also link there from the lessons install view, so they do not have to know the context id. The install iframe can say which course it wrote and link to that course's Classroom tab.

### Token rules for the rebuild

These are the rules that make the button honest.

- Refresh under a named lock on the teacher user id. One refresh, then everyone else reads the new blob.
- Never replace a stored refresh token with a blob that has none. On refresh failure, leave the old blob, set a connection-dead flag the panel can read, and return a normal error string from grade send.
- Catch Classroom API errors in `gradeSend`. Write `server_status`, enqueue the result, append a log row. Return a string so the student sees that the score was kept here and will go out when the teacher sends the queue. Do not throw past the tool.
- Stop logging the token JSON.
- Call `destroy_instructor_token` only from the reconnect path after the failure has been recorded, not from a student request that noticed a bad blob.
- `prompt=consent` only when reconnecting a dead connection, so a healthy Connect click does not rotate the refresh token out from under in-flight grade sends.

### Student-visible copy

When the patch fails, the student already has a local grade. The flash should say the score is saved in Tsugi and will be sent to Classroom after the teacher reconnects. The current strings ("contact your instructor", "Classroom connection failed") are accurate and give the teacher nothing to click.

A launch that cannot refresh should still open the tool when the student is already a member and the submission id is already stored. The teacher's token is required to **discover** membership and the submission id. It should not be required to re-check them on every later launch. That keeps a dead token from locking students out of work they have already started. Grade send stays blocked until reconnect, and the panel shows the backlog.

## First cut

Started 2026-10-27. No background retry. The teacher tests, resets, and sends the queue.

The queue is `lti_gclass_queue` (one row per result, deleted on success). The log is `lti_gclass_log`. `lti_user.gc_status` is the last refresh error. The teacher page is `/gclass/courses` and `/gclass/course/{id}` (Settings, the install success page, and a Classroom tab on that course's grade book). A failed `gradeSend` queues the score and tells the student it is saved here. Reset clears the error and does not call Google. Send queue walks the rows once.

1. Make refresh non-destructive and serialized. Fix the `destroy_access_token` fatal. Stop logging secrets. On a failed patch, set `server_status`, enqueue the result, append a log row.
2. Add the Classroom grade book tab on contexts with `gc_secret`: connection status, reconnect, the queue, Reset, Send queue, and the log viewer.
3. Point the lessons install flow at that tab.
4. Leave manifest courses, the share widget, and any cron or daemon out.

After that, the happy path is the same code path it is now. A vanished token leaves a queue row the teacher can see, reset, and send.

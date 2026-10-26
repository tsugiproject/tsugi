# Tsugi Plus design

Stored 2026-10-24. This note describes the code. The original brief is [history/plus-course-shell-prompt.md](history/plus-course-shell-prompt.md). That brief asked for the whole Sakai+ use case, including a roster pull and grade columns posted up to the LMS. The roster pull is in. Grade columns are created when a result row is first inserted, and again if a grade arrives and the link still has no line item.

Tsugi Plus is one LMS course taught inside Tsugi. The LMS stays the system of record for roster, grades, and notifications. One LMS course is one `lti_context` and one new Lessons JSON v2 manifest. Students launch the same course-navigation link and work inside Tsugi. There is no second course, tenant, link, or line-item table.

## What is built

### Tenant

`lti_key.plus` is off until an admin turns it on. A launch to `/plus` while it is off returns "Tsugi Plus is not enabled for this tenant" and does not create a course.

Checking Tsugi Plus while creating or editing a tenant shows the configuration note. The note says dynamic registration includes a resource link titled Tsugi Plus, that an LMS can also place a Plus launch using a deep link request, and gives the target link URI `$CFG->wwwroot/plus` for an LMS that does not place that message. The note is on the Brightspace, Sakai, Moodle, Manual, and Blackboard screens. The Canvas screen is unchanged.

`lti_key.timezone` is an optional IANA name. It is copied onto the context when the Plus course is created and the context zone is still empty. A later edit of the key does not rewrite existing courses. Abbreviations such as EST are not copied. `$Person.address.timezone` and `$CFG->timezone` are not the course zone.

### Where the LMS places the launch

`/lti/canvas-config/{key_id}.json?unlock_code=` is a Canvas developer-key document for this key. The unlock code is the tenant's one-time `lti_key.unlock_code`, and `plus` must be 1. A missing or wrong code is a 404. Fetching the document does not clear the code. Dynamic registration still clears it. The Canvas tenant screen shows the URL when Plus is on and the code is set. One placement: course navigation, `LtiResourceLinkRequest`, `windowTarget` `_blank`. The placement `target_link_uri` is `$CFG->wwwroot/plus`. The top-level `target_link_uri` stays `/lti/oidc_launch`. `oidc_initiation_url` is `/lti/oidc_login/{key_id}`. Redirect and keyset stay `/lti/oidc_launch` and `/lti/keyset`. Scopes are score, lineitem, lineitem.readonly, result.readonly, and contextmembership.readonly. Privacy is public. `lti/store/canvas-config.json` is left as it is. The Canvas admin screen shows this JSON URL. It does not show the target link URI note used on the other LMS screens.

When `lti_key.plus` is 1, dynamic registration adds one `LtiResourceLinkRequest` titled Tsugi Plus, aimed at `$CFG->wwwroot/plus`. A tenant with `plus` 0 does not include that message. The registration still asks for the same five scopes.

The store deep-link screen has an Other launches tab when there is something to place. Privacy is listed only when `$CFG->privacy_url` is set, and the placed URL is that privacy URL. Tsugi Plus is listed only when `lti_key.plus` is 1. Both are resource links with no line item. If neither is available, the tab is omitted.

### Launch

`/plus` is a cookie session. The cookieless `?_LTI_TSUGI=` parameter is dropped. The launch POST that opens the session empties the previous cookie session first, including a cached membership, and then stores this launch. Another tab that shared the cookie loses the old session.

If that POST arrives inside an iframe, `/plus` does not start the session there. The button calls `window.open` and re-posts the launch into that window. A launch that is already the top window continues there. Leave course, in the avatar menu, calls `window.close()`.

`adjustData` still inserts `lti_context` on `(key_id, context_sha256)`, `lti_user` on `(key_id, subject_sha256)`, and `lti_membership` with the mapped role. When the launch carries a resource link id it also inserts an `lti_link`. Two subjects with the same email stay two `lti_user` rows.

A `/plus` launch is a context launch. It is accepted when the LMS sent no resource link id, as long as the context is present. When the launch has a resource link id, `adjustData` keeps that `lti_link`, and the context stores it in `plus_launch_link_id`. A later `/plus` launch with a different resource link inserts another `lti_link` and replaces `plus_launch_link_id`. The earlier link row stays. A launch with no resource link leaves the stored value. No code treats `plus_launch_link_id` as special beyond that store. It does not decide access, grades, or line items. A second placement aimed at `/plus` in the same LMS course opens the same Tsugi course. Service URLs on that course refresh because the request is `/plus`.

After the session is stored, `/plus` redirects to `/courses/{context_id}`. `/courses` still refuses an ordinary LMS launch. It allows this cookie session when the launch was to `/plus` and the id in the address is this launch's context. A different id, the course list, and creating a course stay refused. Plus is not a course switcher.

A later LMS launch of another tool is that tool's own launch. Trophy is a cookieless `_LTI_TSUGI` session and does not join the Plus cookie. A launch to `/quiz1` takes its context from that launch, and a fresh resource link id is a new `lti_link` in that course. It is not an existing quiz unless the instructor authors one, and that quiz gets its own link. Those launches do not become Plus sessions because the course was once opened through `/plus`.

The session records `plus_context_launch` when `/plus` opens the course. `/courses/{id}` allows that session. A later launch that replaces the session does not keep the flag.

### Course

When `plus_course` is still 0 and `manifest_id` is null, the launch attaches `Manifest::starter` through `saveNewVersion` and sets `plus_course` to 1. It stores `lti13_deployment_id` and, when the key has a real IANA zone and the context zone is empty, copies that zone. Title and short title are filled only while they are blank. The starter has empty modules and discussions.

If `manifest_id` is already set and `plus_course` is 0, the launch opens that course. It does not replace the manifest and it does not set `plus_course` on the row. The session is marked Plus so `/courses/{id}` opens. If `plus_course` is already 1 and the manifest is missing, the launch stops.

A Plus course keeps `lti13_lineitems`, `lti13_membership_url`, `lti13_context_groups_url`, and `lti13_deployment_id` unless this request is `/plus`. The course title is replaced only while it is blank. A link-level `lti13_lineitem` and link title are not guarded. The first launch still has `plus_course` 0 during `adjustData`, so that launch stores the service URLs normally.

The course is that manifest. It does not read `$CFG->lessons`. A missing manifest is an error page. `Manifest::createCourse` stays the path for a course created inside Tsugi, and that row keeps `plus_course` 0. Bringing in an existing course is lessons import and export after the shell exists.

The starter navigation is the default course menu without the courses widget and without Logout. The avatar menu has Leave course. Logout remains in the menu catalog, so a teacher can add it. Leave course is also available on any course. The navigation is stored when the shell is created. A course created earlier keeps the menu it already stored.

The resource link on a `/plus` launch is not a grade column. This change does not POST a line item for it. Grades belong to the activity links inside the course.

### Roster

After a Plus launch opens the course, Tsugi pulls `lti13_membership_url` when `nrps_at` is empty or older than 5 minutes for an instructor or 30 minutes for a learner. The pull uses the stored deployment id and the key's token URL. The deployment id is sent on that token call. Other token calls still follow `$CFG->token_deployment_id`.

The browser is redirected before the pull. A named lock keeps two launches from pulling the same course at once. A token failure does not move `nrps_at`, and the next try waits about a minute. A roster HTTP failure does move `nrps_at`, so the next try waits the 5 or 30 minutes. Paging stops after 10 pages or a repeated next link. An incomplete pull, or a pull that failed to save someone, does not mark anyone deleted.

Each returned person is an `lti_user` on `(key_id, subject_sha256)` and an `lti_membership`. The role numbers match a launch. A missing member is left alone until a completed pull has not seen them for 7 days, then `lti_membership.deleted` is set to 1. Their results, quiz attempts, and posts stay. A later roster row or launch sets `deleted` back to 0. `loadAllData` loads a deleted membership so that launch can restore it.

One `lti_context_plus_log` row is written per roster pull, with the count and a debug log. Tokens are not stored. Instructors place Roster in Settings → Navigation, in the upper left, upper right, or avatar menu. It is always instructor only. The first tab lists the people, including memberships marked deleted, and can set or clear that flag. A Plus course adds a Roster log tab with the last start, recent errors, recent pulls, and a button that refreshes now. A person an instructor removes comes back when a later roster row or launch still lists them.

### Grade columns

A Plus activity link gets a line item when its first `lti_result` row is inserted, and again when a grade is stored and `lti_link.lti13_lineitem` is still empty. The course-navigation link (`plus_launch_link_id`) is not a column. A link with no title or no positive `score_maximum` stays local. The POST goes to `lti_context.lti13_lineitems` with `label`, `scoreMaximum`, `resourceId` equal to `link_id`, and `endDateTime`. When `lti_context.timezone` is set, `endDateTime` is 23:59:59 on the due day (`lti_link.end_datetime`) in that zone. When the zone is empty, the stored clock time is sent as-is. The returned id is stored on `lti_link.lti13_lineitem`. It is not written to `ags_resource_id`. The token call sends the context deployment id.

A numeric score then POSTs to `{lineitem}/scores` with `scoreGiven` (the 0–1 `grade` times `score_maximum`), `scoreMaximum`, the comment, and the progress pair. `grades_released` 0 creates the line item when it can and does not post the score. Null and 1 send. The local `lti_result` row is kept either way. A failed token, line item, or score leaves `server_grade` at the last accepted fraction, sets `retrieved_at`, and stores the error on `server_status`. The next grade send tries again. There is no background retry. Success clears `server_status` and stores the fraction that was sent. Each create and each score post appends an `lti_context_plus_log` row (`lineitem_create` or `score`). Tokens are scrubbed.

The grade book kebab keeps Progress and Status. In a Plus course it also shows Server grade and Server updated at (`retrieved_at`). Server error appears only when `server_status` is set. The student grade list does not show Server grade as its own column. An instructor looking at one student's grades, or at one activity under All Students, can click a grade and store a new point total. When that link already has a line item, the new score is sent. The override does not create a line item. An instructor on a Plus course gets a Grade book log tab. It lists recent line-item and score rows from `lti_context_plus_log`, with errors above the successful sends. Roster rows stay on the Roster log.

Manage grade book, on a Plus course, adds a Line item column. Created means `lti_link.lti13_lineitem` is set. None has a button that asks for the line item on that link, before any score exists. The course launch link says it is not a grade column. A quiz with no `score_maximum` uses the sum of its question points, and that sum is stored on the link, then the line item is created. The same copy happens when the result row is first inserted and when a grade is stored, so a student score does not wait for the button. A link that still has no title or point total is not posted, and the page says so. An ordinary resource link still uses `Result::gradeSendStatic` with score maximum 1.

A due date is included when the line item is created. Saving, clearing, or applying weekly due dates PUTs that line item when one already exists and the stored due date changed. A cleared date sends `endDateTime` null. Title and point-total changes do not PUT yet. Existing scores are not rescaled and not reposted.

## Schema in this change

On `lti_key`: `plus` (`TINYINT NOT NULL DEFAULT 0`) and optional `timezone`.

On `lti_context`: `plus_course` (`TINYINT NOT NULL DEFAULT 0`), `plus_launch_link_id` (no foreign key; same pattern as `previous_context_id`), `lti13_deployment_id`, `timezone`, and `nrps_at`. Existing rows stay `plus_course` 0, including file-based lessons and ordinary resource links. `plus_launch_link_id` is the context's resource link from the latest `/plus` launch that had one. It is not how the launch is recognized, and it is not a grade column.

`lti_context_plus_log` is created: one row per upward call, with `context_id`, optional `link_id`, optional `user_id`, kind, success, HTTP status, URL, request body, response detail, and `created_at`. A roster pull writes kind `roster`. A line item create writes `lineitem_create`. A due-date change writes `lineitem_update`. A score post writes `score`.

`lti_result.server_status` is the last Plus line-item or score error. It is cleared when a score send succeeds. `server_grade` stays the last accepted 0–1 fraction. `retrieved_at` is the last attempt.

`nrps_at` is the last time a roster pull started. The Plus roster pull sets `$CFG->token_deployment_id` for that call so the stored deployment id is sent, then puts the flag back. `LTI13::get_access_token` still adds a deployment id only when that flag is set. Line-item and score calls do not set it.

## Left as it is

A file-based `$CFG->lessons` context has `manifest_id` null and `plus_course` 0. Its launch stays as it is.

An ordinary resource-link launch keeps today's `adjustData` and `Result::gradeSendStatic`: score maximum 1, and a line item created lazily on the first score. A Plus course does not use that 1-point path. `Gradebook::record` still writes the local fraction first.

`lti/store/canvas-config.json` and `Manifest::createCourse` stay on their current paths. The Canvas tenant screen stays as it was.

## Next phase

Not in this change:

- Changing the title or points PUTs the same line item. Existing scores are not rescaled and not reposted.
- A replay of an `lti_context_plus_log` row sends the stored request again and appends a new row. The old row stays.

## Left open

- Deleting an activity does not DELETE the LMS line item. `LTI13::deleteLineItem` exists and is not called.
- Letter grades and pass/fail are not sent.
- Grades are not read back from the LMS.
- How long `lti_context_plus_log` rows are kept. This design does not pick a retention period.

# Historical: Plus course shell prompt

Stored 2026-10-24. This file is the original brief for a Tsugi Plus course
shell. It is archived here so we can look back at the starting intent.

**This is not live product rules.** The current description is
[tsugi-plus-design.md](../tsugi-plus-design.md). Do not copy this into
`.cursor/rules/`, `AGENTS.md`, or `docs/README.md`. Do not treat this file
as current agent or contributor guidance. If the running code disagrees
with this document, the code is right.

Status: **launch and course navigation implemented 2026-10-24**. Roster and
grade posting in the stories below are the next phase. This file is still
a historical prompt, not live product rules.

---

# Cursor Prompt: Plus course shell

Do not write code yet. Inspect this Tsugi checkout and produce a design that reproduces the Sakai+ teaching use case. Use Tsugi's existing tables. Do not add a parallel tenant/context/link/line-item schema modeled on Sakai's PLUS_* tables.

Sakai+ lived in production at the University of Michigan for two years: Canvas was the system of record for roster, grades, and notifications. Sakai was the place the course was taught. The instructor never had to show students the Sakai gradebook.

## The use case

Canvas (or another enterprise LMS) launches Tsugi once per course, from course navigation, in a new tab. That launch provisions an empty editable course shell tied to that Canvas course. The instructor builds the course inside Tsugi: quizzes, lessons, discussions, and LTI tools placed in the course. Students launch the same course link and do the work inside Tsugi.

Every gradable activity the instructor creates inside that shell becomes a new grade column in Canvas, as soon as it is created, before any student has a score. As students earn numeric grades, those scores are sent up to the matching Canvas column, including comments. The instructor can change the column's title, points possible, and due date in Tsugi and Canvas updates. Canvas remains the gradebook students look at.

One Canvas course produces one Tsugi course. Many activities inside that course produce many Canvas line items. Those line items are grade columns. They are not separate Canvas resource links, and students do not launch each activity from Canvas.

## What Sakai+ actually does

Source of truth in the Sakai tree: `plus/README.md`, `plus/docs/INSTALL-CANVAS.md`, `plus/docs/TESTING.md`, `plus/docs/NOTES.md`, `plus/provider` (`ProviderServlet`, `WEB-INF/descriptor.json`), `plus/impl/PlusServiceImpl`, `plus/impl/PlusEventObserver`, and the gradebook hooks in `GradingServiceImpl.addAssignment` / `updateAssignment`.

Install. A tenant is one controlling LMS: issuer, client id, OIDC auth/keyset/token URLs, and an optional token audience (Brightspace). Canvas does not do IMS Dynamic Registration, so the tenant exposes a Canvas developer-key JSON document. Placements are course navigation, `LtiResourceLinkRequest`, `windowTarget: _blank`. Requested scopes are AGS `lineitem`, `lineitem.readonly`, `result.readonly`, `score`, and NRPS `contextmembership.readonly`. Privacy level is public, and email is trusted. Other LMSs can use dynamic registration. A tenant deployment id may be blank or `*` because Canvas mints many deployment ids per client; the deployment id used on access-token calls is stored on the course and refreshed from each launch.

First launch. Validate the launch against the tenant. Find or create the local user (trusted email keeps one person as one user across courses in that tenant). Find or create the course for (tenant, LTI context id). If the course is new, copy a template so the site has its working tools, permissions, and an initialized gradebook, and is published. Teaching content starts empty. Title and label are taken from the launch when the local title is still blank; a later local title is left alone. Store the AGS line-items URL, the NRPS memberships URL, and the context deployment id. Log the user into the course and open it.

Later launches. Reuse the same course. Refresh title only while the local title is blank. Refresh service URLs and deployment id when the launch carries new values. Enroll the launching user from their LTI roles.

Roster. On launch, pull the course roster with NRPS so students exist in the course before they have launched. Throttle: about 5 minutes after an instructor launch, about 30 minutes after a learner launch. Dropping a membership from the roster removes their access. It does not delete their work; a later launch or roster row restores access.

Grade columns. When a grade column is created inside the course — by the instructor, or by a tool such as a quiz, an assignment, a forum, or an LTI tool — POST a line item to the course's line-items URL:

- label = column name
- scoreMaximum = points possible
- resourceId = the local column id
- endDateTime = due date, shifted to the controlling LMS timezone (Sakai moves it to end of day first)

Store the returned line-item URL on that column. When the column's name, points, or due date change, PUT the line item. The column shows up in Canvas before any score exists.

Scores. When a numeric score or comment is written on that column, POST a score to `{lineItemUrl}/scores` with scoreGiven, scoreMaximum, comment, activityProgress `Completed`, and gradingProgress `FullyGraded`. This is one-way. An edit made in Canvas is not read back. Letter grades and pass/fail are not sent. Tools that withhold the gradebook write until the grade is released (Sakai Assignments) therefore withhold the Canvas update until release; the bridge itself forwards the grade event it is given.

Deleting a column in Sakai does not delete the Canvas line item. Decide explicitly whether Tsugi should DELETE the line item. Sakai left it.

Out of scope for this use case, because Sakai removed or never needed them here: launching a single inner tool as its own Canvas placement; deep-linking individual Tsugi activities back into Canvas modules; pulling grades down from Canvas; copying an old course into the shell.

## Map this onto Tsugi

Tsugi already has most of the machinery. Read it and extend it. In particular:

- `lti_key` is the security contract with one LMS (issuer, client id, OIDC endpoints). That is the tenant. Canvas's many deployment ids already need a per-context deployment id for token calls. Confirm where that is stored.
- `lti_context` is the course. It already has `lineitems_url`, `memberships_url`, and `manifest_id`.
- `lti_link` is the gradable activity inside the course (the recent design direction: one link is one course activity, with grade metadata). It already has `lti13_lineitem`.
- `lti_result` is one learner's score on one link. `Result::gradeSend` already stores the grade locally, and if the link has no line item but the context has a line-items URL, it POSTs a line item (today with `scoreMaximum` 1 and only when the first grade is sent) and then posts the score.
- `Tsugi\Util\LTI13` already implements create, update, and delete line item, and send score.
- A new course manifest (`lti_context.manifest_id`, lessons JSON v2) is the editable shell. File-based `$CFG->lessons` courses have no `manifest_id` and must keep launching exactly as they do now. Do not migrate old manifests. Treat a Plus shell as a new course created by this launch.

The behavior Sakai+ has that current `gradeSend` does not: the Canvas column is created when the instructor creates the activity, with the real points possible and due date, and it is updated when those change. Waiting until the first student score, and always sending a maximum of 1, does not match the use case.

## Use cases the design must satisfy

Write the design as these stories. Name the Tsugi rows each story reads and writes.

1. An admin registers Tsugi with Canvas using a developer-key URL, trusts email, and installs one course-navigation link that opens in a new tab. The registration asks for the AGS line-item and score scopes and the NRPS membership scope.
2. An instructor launches that link in a Canvas course that Tsugi has never seen. Tsugi creates an empty editable course (a new manifest on that context), enrolls the instructor, and shows the course authoring UI. A second launch opens the same course.
3. A student who has never launched is already in the course because the roster was loaded. The student launches the same Canvas link and sees the course as a learner.
4. The instructor adds a quiz worth 10 points, due on a date. Before any submission, Canvas has a new grade column named for that quiz, with 10 points and that due date.
5. The instructor adds an LTI tool placement inside the course and marks it for a grade. Canvas gains another column. The tool launch stays inside the Tsugi course.
6. A student scores 8/10. Canvas shows 8 for that student on that column. A comment entered in Tsugi appears in Canvas. A later change to 9 replaces the score.
7. The instructor renames the quiz and changes it to 20 points. The Canvas column updates. Existing scores stay associated with the same line item.
8. A second Canvas course, same Tsugi registration, gets its own empty shell and its own columns. Users match by trusted email. Grades never cross courses.
9. A normal Tsugi tool launch that is not a course shell — a single resource link placed in some other LMS course, and a file-based `$CFG->lessons` site — keeps today's grade return behavior.

## Design rules

- One Canvas context maps to one `lti_context` and one new manifest. Do not create a second course object.
- Each gradable activity inside the shell is an `lti_link` whose `lti13_lineitem` is the Canvas line-item URL. `resourceId` is the Tsugi link id.
- Create and update the line item when the activity is saved, not when the first result is written. Sending the score stays on the existing result path.
- The line-items URL used for those calls is the URL from the course-shell launch (the Canvas course), stored on `lti_context`. An inner tool's own launch must not replace that URL.
- Roster sync uses the memberships URL on that context, with the same instructor/learner throttle idea, and does not delete learner work when a membership lapses.
- Grades flow only from Tsugi to Canvas. Send numeric scores. Keep local results either way, so a missing line-items URL still stores the grade in Tsugi.
- Keep a short per-course log of roster pulls, line-item creates/updates, and score posts, visible to the instructor. Sakai's Plus tool inside the site was how failures were diagnosed.

## What to return

A design note, not a patch. For each use case, the rows and fields involved, what already exists in this checkout, and the smallest schema or code change that would satisfy it. Call out anything Tsugi already does that should be left unchanged, especially file-based lessons and ordinary single-link grade return. List decisions you are not making (line-item delete, letter grades, grade pull-back, copying a previous shell) rather than inventing them.

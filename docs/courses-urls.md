# Course URLs and the current context

Nested paths `/courses/{id}/announcements` (and other tools) name the
course for **that request**. Bare `/announcements` (and other site URLs)
always use the **Google site-login course**, not a leftover sandbox from
`/courses/{id}`.

## Who can use `/courses`

Google site login only (`google.com` key). An LMS LTI launch is refused:
that session already has a course from the LMS, and course switching is
not available. Bare tool URLs still work after an LMS launch.

## Course switch

On `/courses/{id}/…`, Tsugi writes `$_SESSION['context_id']` to that id,
stores the sandbox `manifest_id`, resets the per-request identity
snapshot, and drops session caches. Then `currentContextId()` is that
course for the rest of the request.

On a **site** URL (`/`, `/announcements`, buildmenu chrome), Tsugi puts
the Google-login course back and clears `manifest_id`. `Manifest::activeId()`
is then 0, so authoring / course Settings / v2 lessons treat this as
the global file-backed course. Re-enter a sandbox only via
`/courses/{id}/…` (the sites widget).

Grades and other context caches are tagged with the context id. A
mismatch is a miss, not a mix-up.

`GET /courses` (no id) does **not** enter a sandbox. It lists
memberships in the site-login course.

`GET /courses/{id}` switches, then redirects to **course Home**
(`/courses/{id}/home`), not the site apphome. That keeps the request in
the course URL space so course navigation applies.

## Multiple tabs

One PHP session, one stored `context_id`. File sessions lock, so requests
for that session run one at a time. The supported memcached setup turns
locking off (`memcached.sess_locking=0`), so two tabs **can** run at
once. Last write wins for leftover session fields.

Each request still rebinds from its URL: `/courses/2/…` is course 2,
`/announcements` is the Google-login course. Concurrent writes do not
mix those in-request globals. After both finish, leftover session is
whichever write landed last. The **next** click fixes it from the URL
again, including unprefixed site URLs.

## Menus vs controllers

Controllers follow the request path (`toolHome()`). Site menus stay on
global URLs. Typed `/courses/{id}/…` URLs still work.

The site-menu waffle is off unless local config sets
`$CFG->setExtension('show_courses_widget', true)`. Do not enable that in
production until there is more than one course to switch between.

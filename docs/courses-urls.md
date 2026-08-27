# Course URLs and the current context

Nested paths `/courses/{id}/announcements` (and other tools) name the
course for **that request**. Bare `/announcements` uses whatever course
is already in the session.

## Who can use `/courses`

Google site login only (`google.com` key). An LMS LTI launch is refused:
that session already has a course from the LMS, and course switching is
not available. Bare tool URLs still work after an LMS launch.

## Course switch

On `/courses/{id}/…`, Tsugi writes `$_SESSION['context_id']` to that id,
resets the per-request identity snapshot, and drops session caches.
Then `currentContextId()` is that course for the rest of the request.

Grades and other context caches are tagged with the context id. A
mismatch is a miss, not a mix-up.

`GET /courses` (no id) does **not** switch. It lists memberships and
leaves the last nested request’s context in the session.

`GET /courses/{id}` switches, then redirects to the site home.

## Multiple tabs

One PHP session, one stored `context_id`. File sessions lock, so requests
for that session run one at a time. The supported memcached setup turns
locking off (`memcached.sess_locking=0`), so two tabs **can** run at
once. Last write wins, and that is fine.

Each nested URL still switches to **its** `{id}` in that request’s
process, so a post to `/courses/2/…` is course 2 and a post to
`/courses/7/…` is course 7. Concurrent writes do not mix those
in-request globals. After both finish, leftover `context_id` is
whichever session write landed last.

That leftover only matters if the next click is **unprefixed**. Nested
menus stay honest.

## Menus vs controllers

Controllers follow the request path (`toolHome()`). They do not read a
config flag to decide `/courses/{id}/…` vs `/announcements`.

Parent-site menus may opt in with a temporary extension:

```php
$CFG->setExtension('courses_in_urls', true);
// or only some people:
$CFG->setExtension('courses_in_urls', array('you@example.com'));
```

Then: `rtrim($CFG->apphome, '/') . Courses::toolPathPrefix() . '/announcements'`.
The prefix is Google-login only; LMS launches stay unprefixed even when
the flag is on.

Unset or false keeps menus unprefixed so the feature can ship
unadvertised. Typed `/courses/{id}/…` URLs still work.

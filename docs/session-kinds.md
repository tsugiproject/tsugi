# Three kinds of sessions

Tsugi has three logins. Two of them share the `PHPSESSID` cookie. The third never uses that cookie.

| Kind | Transport | What it is |
| --- | --- | --- |
| Google site login | Cookie (`COOKIE_SESSION` defined) | A person signed in on the site with Google. |
| Plus launch | Cookie (`COOKIE_SESSION` defined) | An LMS course opened through `/plus`. The browser stays signed in with the site cookie. |
| Tool launch | Cookieless (`COOKIE_SESSION` not defined) | An LTI launch straight to one tool. The session id travels on the URL as `_LTI_TSUGI`. |

How the cookie is chosen is in `docs/session-establishment.md`. How `LTIX::session_start()` fills `ReqScope` is in `docs/ltix-session-start.md`. This page is about which login is in the session, and what Tsugi does differently for each.

A cookie is shared by every tab. A Plus launch and a Google login in the same browser replace each other, because both write the same `PHPSESSID` session. A cookieless tool launch does not. Its id is on that tool's URL, so another tab's cookie login leaves it alone.

## How a request tells them apart

`LTIX::session_start()` runs these checks, in order, on a cookie request:

1. `Courses::restoreSiteLoginContext()` runs only for a Google login. On a site URL it puts the Google home course back.
2. `Courses::releaseUnmountedCourse()` runs for every other cookie session. On a site URL it drops the top-level course fields. A Plus session is the exception: `ReqScope::isPlusSession()` keeps those fields. The launch row stays either way.
3. `LTIX::launchContextApplies()` decides whether course-shaped data on `$_SESSION['lti']` is used for this request.

| Check | Google | Plus | Cookieless tool |
| --- | --- | --- | --- |
| `COOKIE_SESSION` | Defined | Defined | Not defined |
| `$_SESSION['id']` | The site user id | Absent | Absent |
| `$_SESSION['lti_post']` | Absent | The launch post | The launch post |
| `$_SESSION['oauth_consumer_key']` | `google.com` | The LMS key | The LMS key |
| `$_SESSION['lti']['plus_context_launch']` | Absent | `1` | Absent |
| `ReqScope` origin | `site` | `site` | `lti` |

`Courses::isGoogleLoginSession()` is true when `lti_post` is empty and the key is `google.com`. A launch always stores `lti_post`, so a Plus session and a tool session are never a Google login.

`Courses::plusOwnsCourseUrl()` is true when the URL is `/courses/{id}`, `$_SESSION['lti']['plus_course']` is `1`, `plus_context_launch` is `1`, and `{id}` is that row's `context_id`. An ordinary tool launch does not set `plus_context_launch`.

`LTIX::launchContextApplies()`:

- Cookieless, and the row has a `context_id`: true. The tool's course is the request's course.
- Cookie URL `/courses/{id}`: true when `{id}` equals the row's `context_id`.
- Any other cookie URL: true when `$_SESSION['context_id']` equals the row's `context_id` and that id is greater than 0.

A request course of 0 does not match. `$USER` is still built from the row. `$CONTEXT`, `$LINK`, `$RESULT`, and the roster are built only when `launchContextApplies()` is true.

The site menu uses `ReqScope::isLoggedInLegacy()`. That reader uses `$_SESSION['id']` when it is set, and `$USER` otherwise. Google sets `$_SESSION['id']`. Plus and a tool launch do not, so the menu follows `$USER` after the launch has been built.

## Google site login

`GoogleLoginService::establishSiteSession()` starts this session. Google has already accepted the account. The method reads the return URL, then `resetSessionToEmpty()` replaces `$_SESSION` with an empty array. A Plus launch that was in this cookie is gone. The method then writes a new session:

- `$_SESSION['id']`, `user_id`, `user_key`, `email`, `displayname`, and the profile id.
- `$_SESSION['oauth_consumer_key']` = `google.com`.
- `$_SESSION['context_id']`, `context_key`, `context_title`, and `site_context_id` for the site course named by `$CFG->context_title`.
- `$_SESSION['lti']` with the same user and course, `key_key` = `google.com`, and no `manifest_id`.
- No `$_SESSION['lti_post']`. No `plus_context_launch`.

`ReqScope` origin is `site`. The user is `$_SESSION['id']`. The course is `$_SESSION['context_id']`.

On a site URL (`/grades`, `/lessons`, `/`, anything that is not `/courses/{id}`):

- `restoreSiteLoginContext()` puts the Google home course back when the session had been switched to another course, and clears a leftover session manifest.
- `releaseUnmountedCourse()` does nothing.
- `launchContextApplies()` is true, because `$_SESSION['context_id']` is the site course and that is also the id on `$_SESSION['lti']`.
- Lessons use that course's manifest. A course with no manifest uses `$CFG->lessons`.

On `/courses/{id}`:

- `Courses::gateResponse()` allows the visit.
- `ensureActiveContext()` loads that course when this user is a member. The session course, title, and manifest id become that course.
- A course this account does not belong to, including a Plus course opened from the LMS, returns the page "This session cannot open this course." **Continue** uses this tab's stored LTI return URL when one is present, and the site home when it is not. **Close Tab** closes the tab.

A site tool that calls `Tool::requireAuth()` with no course returns "This page needs a course," with the same two buttons. A configured Google login has the site course, so `/grades` opens that course's grade book.

## Plus launch

`/plus` is the login. `tsugi.php` sees that path and runs `plus.php`, which defines `COOKIE_SESSION` before `config.php`.

The LMS first posts the launch inside its iframe. `PlusShell::needsBreakout()` answers a page that asks for a new window, because a cookie set inside the iframe is not the site cookie. The new window posts the same launch with `plus_repost=1`. `PlusShell::replaceCookieSession()` empties `$_SESSION` and closes it, so the previous Google login or Plus course in this cookie is gone. `LTIX::session_start()` then stores the launch the same way any LTI launch is stored:

- `$_SESSION['lti']` is the launch row: `user_id`, `context_id`, `link_id`, the LMS `key_key`, the role, and `manifest_id` when the course has one.
- `$_SESSION['lti_post']` is the original post, including `launch_presentation_return_url` and `launch_presentation_error_return_url` when the LMS sent them.
- `$_SESSION['id']` is not set. The user id lives on `$_SESSION['lti']['user_id']`.

`PlusShell::remember()` then marks that row: `plus_course`, `plus_launch_link_id`, `plus_context_launch` = `1`, and the course title. It also stores the manifest id in `$_SESSION['manifest_id']` and on the row. The browser is redirected to `/courses/{context_id}`.

On `/courses/{that id}/…`:

- `plusOwnsCourseUrl()` is true, so `gateResponse()` allows the course. A different course id is refused: "This session is an LTI launch from an LMS. Course switching is not available."
- `releaseUnmountedCourse()` does nothing, because the URL is course-mounted.
- `launchContextApplies()` is true, because the URL id is the row's `context_id`.
- `ensureActiveContext()` writes the top-level course fields for that course: `context_id`, `context_title`, membership, and `isinstructor`.
- The user on `ReqScope` comes from `$_SESSION['lti']['user_id']`. The course, the link, and the manifest are the Plus course.
- Lessons, grades, and settings for that URL are that course.

On a site URL in the same cookie (`/grades`, `/lessons`, `/`):

- `restoreSiteLoginContext()` does nothing. This is not a Google login.
- `releaseUnmountedCourse()` does nothing. The session is Plus, so `context_id`, `context_title`, membership, `isinstructor`, and the session manifest stay. `$_SESSION['lti']` and `$_SESSION['lti_post']` stay too.
- `launchContextApplies()` is true while `$_SESSION['context_id']` is still that course. The tool does not get to use it.
- The router calls `Tool::exitPlusLeftCourse()` before a `Tool` action. The page says this session is a direct launch of the course titled in the launch row. **Go back to course** opens `/courses/{that id}`. **Log out** ends the cookie session. **Close Tab** asks the browser to close the tab, and after three seconds says it could not close this browser tab. `/login` is a `Tool`, so it gets this same page. Logout is not a `Tool`, so the Log out link still runs.
- Opening `/courses/{the plus id}` again is that same course. Nothing on the session was cleared to make the site URL stop.

Google login in this same browser calls `resetSessionToEmpty()` and writes a Google session over the Plus row. The Plus course URL then fails the membership check, because the Google user is the user on the session.

Each Tsugi page also writes the current `_TSUGI` object into this tab's `sessionStorage` under `TSUGI_SESSION_DATA`. That copy includes `launch_presentation_return_url` and `launch_presentation_error_return_url` from the launch being shown. The error pages read it for **Continue**. It is per tab. It is not the PHP session, and it is not how the three logins are detected.

## Cookieless tool launch

This is a deep link to one tool, such as `tool/gift/index.php`. The tool file does not define `COOKIE_SESSION`. `setup.php` turns cookies off and names the session `_LTI_TSUGI`. `LTIX::requireData()` validates the launch, builds a session id from it, and stores the same launch pair a Plus launch stores before `remember()`:

- `$_SESSION['lti']` with `user_id`, `context_id`, and `link_id`.
- `$_SESSION['lti_post']` with the launch post.
- No `$_SESSION['id']`. No `plus_context_launch`.

The redirect back to the tool carries `?_LTI_TSUGI=...`. Later requests to that tool send that id. There is no `PHPSESSID` for this launch.

`ReqScope` origin is `lti`. The user, the course, and the link all come from `$_SESSION['lti']`. `launchContextApplies()` is true whenever the row has a course. `releaseUnmountedCourse()` does nothing, because `COOKIE_SESSION` is not defined. The tool keeps its course for the life of that session id.

A site URL opened without `_LTI_TSUGI` is a different session. `tsugi.php` defines `COOKIE_SESSION` and reads `PHPSESSID`. The tool's course does not follow the user onto `/grades` or `/lessons`.

`Courses::gateResponse()` refuses this kind of session on `/courses` when it does arrive there: course switching is not available. The tool's own URL is the session.

## What changes with the two cookie sessions

Both use `PHPSESSID` and `ReqScope` origin `site`. The split is whether the session has `$_SESSION['id']` and an empty `lti_post` (Google), or a launch row with `plus_context_launch` and no `$_SESSION['id']` (Plus).

| | Google, site URL | Plus, site URL | Google, `/courses/{id}` | Plus, `/courses/{its id}` |
| --- | --- | --- | --- | --- |
| Active course | The Google home course | The Plus course stays in the session. The tool does not open it. | That course, when the user is a member | The Plus course |
| Launch row used for the course | Yes, it is the home course | Yes. The router stops the tool before the row is used. | Only when the URL id is the home course id | Yes |
| Manifest | The home course, else `$CFG->lessons` | The Plus manifest stays. The tool does not render it. | The course being opened | The Plus course |
| `/grades` | The home course grade book | The direct-launch page | That course, when allowed | That course |
| Course list and create | Allowed | Refused | Allowed | Refused |
| Another course's URL | Membership check | Refused | Membership check | Refused |

Returning from a Plus site URL to `/courses/{its id}` is the same login again. The course fields and the launch row were both left in place. A Google login in that cookie removes the Plus row.

## Reading the kind and the key

`ReqScope` reads the session directly. The three checks are mutually exclusive, in this order:

```php
if ( ReqScope::isGoogleSession() ) {
    // SESSION_GOOGLE
} else if ( ReqScope::isPlusSession() ) {
    // SESSION_PLUS
} else if ( ReqScope::isLtiSession() ) {
    // SESSION_LTI
}
```

`ReqScope::sessionType()` returns `google`, `plus`, `lti`, or `none`. `Courses::isGoogleLoginSession()` is the same check as `ReqScope::isGoogleSession()`.

`ReqScope::keyId()` is `$_SESSION['lti']['key_id']`, or 0 when that field is absent.

Each login writes that field once and leaves it there:

- Google writes the `google.com` key id in `establishSiteSession()`. Switching courses does not replace it.
- Plus and a tool launch copy `lti_key.key_id` into the launch row in `setupSession()`. A launch with no key is rejected before the row is stored. Dropping the course on a Plus site URL does not remove the row, so the key id stays.

A cookie that has not logged in has no launch row. `keyId()` is 0 and `sessionType()` is `none`. The key id is the tenant that established the session. It is not looked up from the course in the URL.

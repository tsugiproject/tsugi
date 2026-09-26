# Session establishment

Two choices are made before any controller runs. They combine into a few entry paths. `config.php` chooses the transport. `ReqScope` is filled later, when `LTIX::session_start()` or `LTIX::requireData()` returns. The field-by-field account of that fill is in `docs/ltix-session-start.md`.

## The two choices

**Transport.** Defined or not before `config.php` loads.

`config.php` loads `lib/include/setup.php` at the end. `setup.php` is not a method. While that load runs, `setup.php` does this:

- `COOKIE_SESSION` is defined: PHP cookie session. The cookie name stays `PHPSESSID`. `setup.php` only marks the cookie `HttpOnly` and, outside developer mode, `Secure`.
- `COOKIE_SESSION` is not defined: cookieless session. Cookies are turned off, transparent session ids are turned on, and the session name becomes `_LTI_TSUGI` (`TSUGI_COOKIELESS_SESSION_NAME` in `lib/include/tsugi_constants.php`). The id travels as `?_LTI_TSUGI=...` on the URL or in POST Data

`setup.php` (loaded at the end of `config.php`) does not call `session_start()`. `setup.php` only picks the transport. The PHP session is still empty when `config.php` finishes.

The flag has to be set before `config.php`. Setting the flag afterward is too late. `setup.php` (loaded at the end of `config.php`) has already run.

**Arrival.** What Apache executed.

`.htaccess` sends a URL to `tsugi.php` only when the URL is not already a real file or directory. A real `.php` file runs on its own.

- Direct file. Apache runs that file. Its first action is `config.php` (after it has chosen `COOKIE_SESSION` or left it undefined).
- Front controller. Apache runs `tsugi.php`. That file defines `COOKIE_SESSION`, loads `config.php`, calls `LTIX::session_start()`, then either includes a sibling `.php` or routes to a controller.

`LTIX::session_start()` is `requireDataPrivate(NONE)`. `LTIX::session_start()` does call PHP's `session_start()`. `LTIX::session_start()` builds `$TSUGI_LAUNCH` and fills `ReqScope`. When `$_SESSION['lti']` is already stored, `buildLaunch()` rebuilds `$USER`, `$CONTEXT`, `$LINK`, and `$RESULT` from that row, and `ReqScope` carries the same user, course, link, and result. `requireData()` with no arguments is the same function with user, context, and link required. A missing LTI row on that call dies.

`$_SESSION['lti']` is the stored launch row. It is not the session transport. A cookie session can hold that row. A cookieless session can hold it. The parameter `_LTI_TSUGI` is only the cookieless PHP session id.

## Combinations

| Transport | Arrival | Who starts the PHP session | Example |
| --- | --- | --- | --- |
| Cookie | Direct file | The file calls PHP `session_start()` | `/` → `index.php` |
| Cookie | Direct file, then a child file | The child, if it needs one | `/lti/store` → `lti/index.php` → `lti/store/index.php` |
| Cookie | `tsugi.php`, then a `.php` file | `LTIX::session_start()` in `tsugi.php` | `/about` → `tsugi.php` → `about.php` |
| Cookie | `tsugi.php`, then a controller | `LTIX::session_start()` in `tsugi.php` | `/quiz1` and `/courses/123/quiz1` |
| Cookieless | Direct file | `LTIX::requireData()` inside the tool | `/tool/gift/` → `tool/gift/index.php` |
| Cookieless or cookie | Direct file that looks at the query string first | Whatever the included file does | `/util/something` → `util/tsugi.php` |

`tsugi.php` always defines `COOKIE_SESSION` before `config.php`, so every path through that front controller is a cookie session. Cookieless is a direct file that does not define the flag.

### Cookie, direct file

`index.php` defines `COOKIE_SESSION`, includes `config.php`, then calls PHP's `session_start()` itself. It does not call `LTIX::session_start()`. The page is the site home. There is no LTI row required.

### Cookie, direct file, then another file

`lti/index.php` defines `COOKIE_SESSION`, includes `config.php`, and uses `FileRouter`. A URL whose first segment is a real `.php` in that folder is included and the front file returns. `lti/index.php` itself does not start the session. `lti/store/index.php` calls `LTIX::requireData(LTIX::USER)`, so that child still requires a stored launch row even though the transport is the cookie.

### Cookie, `tsugi.php`, then a PHP file

`/about` is not a file at the web root (`about.php` is). Apache rewrites it to `tsugi.php`. `FileRouter` sees `about` and includes `about.php`. `LTIX::session_start()` has already run. No controller is involved.

### Cookie, `tsugi.php`, then a controller

`/quiz1` matches no file, so Apache runs `tsugi.php`. `FileRouter` finds no `quiz1.php` and returns. `Tsugi\Controllers\Tsugi` routes to `Quiz1`. The same front door serves `/courses/123/quiz1`: the course controller switches the active course, then dispatches the inner `/quiz1` route. Both are cookie sessions. `LTIX::session_start()` has already filled `ReqScope`. `/courses/123/quiz1` then calls `ReqScope::replaceCourse()` for course 123. Quiz1 does not call `requireData`.

### Cookieless, direct file

`tool/gift/index.php` does not define `COOKIE_SESSION`. The first action of `tool/gift/index.php` is `config.php`, so `setup.php` (loaded at the end of `config.php`) selects `_LTI_TSUGI`. The tool then calls `LTIX::requireData()`. On a launch, `setupSession()` builds a stable session id from the launch, calls `session_id()` and `session_start()`, and stores the row in `$_SESSION['lti']`. A later request sends that id as `?_LTI_TSUGI=...`. `requireData()` loads the row, rebuilds `$USER`, `$CONTEXT`, `$LINK`, and `$RESULT`, and fills `ReqScope` with origin `lti`. There is no `PHPSESSID` cookie on this path.

### Direct file that picks the transport from the query string

`util/tsugi.php` is a real file, so Apache runs it directly. Before `config.php` it checks the query string. If `PHPSESSID` or `_LTI_TSUGI` is present it leaves `COOKIE_SESSION` undefined and the include becomes cookieless. Otherwise it defines `COOKIE_SESSION`. It then uses `FileRouter` the same way `tsugi.php` does, and it does not construct the controller app.

## Where ReqScope is filled

`config.php` chooses the transport and does not start the PHP session. The PHP session starts later, in one of three ways: PHP's `session_start()` (`index.php`), `LTIX::session_start()` (`tsugi.php`), or `LTIX::requireData()` (a tool).

`index.php` does not fill `ReqScope`. `LTIX::session_start()` and `LTIX::requireData()` both return through `requireDataPrivate()`. `requireDataPrivate()` fills `ReqScope` on that return, from the open session. A missing user is null. A missing course is null. A user with no course is a valid `ReqScope`.

`ReqScope` origin is `site` when `COOKIE_SESSION` is defined, and `lti` when `COOKIE_SESSION` is not defined. A cookieless tool copies the user, the course, and the link from `$_SESSION['lti']`. A cookie session copies `$_SESSION['id']` and `$_SESSION['context_id']`, and includes the link when `$_SESSION['lti']` names that same course. `buildLaunch()` sets the Launch globals. `ReqScope` keeps the same user, context, membership, link, and result. `docs/ltix-session-start.md` lists those rules.

`/courses/{id}/…` writes `$_SESSION['context_id']` after `LTIX::session_start()` returns, rebuilds the Launch, and calls `ReqScope::replaceCourse()`. The same course stays as `LTIX::session_start()` filled it. A different course reloads membership for the user already on `ReqScope`. The next request finds that course id already in the session.

A new launch POST still validates inside `launchCheck()`, stores `$_SESSION['lti']`, redirects, and calls `exit()`. `ReqScope` for that launch is filled on the redirect, when `requireData()` reads the stored row.

`ReqScope::loggedInUserId()`, `ReqScope::currentContextId()`, and `ReqScope::isLoggedIn()` are a separate cached pair. On a site login the pair matches `ReqScope`. On a cookieless LTI tool the pair can stay at user 0. LTI tools do not use those readers. `ReqScope` on that request still holds the launch user and course.

# What `LTIX::session_start()` does

## Why Tsugi tools call `LTIX::session_start()`

`LTIX::session_start()` sets up as much of a Launch, the global variables, and `ReqScope` as the stored session contains, for a cookie session and for a cookieless session. After the PHP session is open, `LTIX::session_start()` builds `$TSUGI_LAUNCH`, creates `$OUTPUT`, and attaches `$OUTPUT` to that Launch. When `$_SESSION['lti']` is present, `buildLaunch()` fills whichever of `$USER`, `$CONTEXT`, `$LINK`, and `$RESULT` that row contains. With no `$_SESSION['lti']` row those globals stay null, and `$OUTPUT` still exists. That is why `Output.php` can run on a cookie page such as `top.php` and on a cookieless caller such as `tool/gift/quiz_data.php`. A cookieless tool launch URL calls `requireData()` instead. `requireData()` enters the same `requireDataPrivate()` method, so that launch also gets `$OUTPUT` and, once the stored row is complete, the same globals. `requireDataPrivate()` fills `ReqScope` before either call returns. The user, context, membership, link, and result on `ReqScope` match those slots on the Launch.

## What `LTIX::session_start()` is

`LTIX::session_start()` is `requireDataPrivate(NONE)`. `NONE` means "open the session, and do not die when no LTI row is available." `LTIX::session_start()` is the same function as `requireData()`. `requireData()` with no arguments requires context, link, and user, and dies when the stored launch is missing. `LTIX::session_start()` does not.

The transport is already chosen. `setup.php` runs while `config.php` is loading, and `LTIX::session_start()` does not change that choice.

- `COOKIE_SESSION` defined: cookie session. The name is `PHPSESSID`. `session.use_cookies` stays on.
- `COOKIE_SESSION` not defined: cookieless session. `session.use_cookies` is `0`, and the name is `_LTI_TSUGI`.

`setup.php` (loaded at the end of `config.php`) does not call PHP's `session_start()`. Before `LTIX::session_start()` runs, `$_SESSION` is not started and `session_id()` is empty, unless some earlier code in the same request already started the PHP session.

## What `LTIX::session_start()` always does first

`LTIX::session_start()` builds a `Launch` and stores that Launch in the global `$TSUGI_LAUNCH`. `LTIX::session_start()` sets the globals `$USER`, `$CONTEXT`, `$LINK`, `$RESULT`, and `$ROSTER` to null. `LTIX::session_start()` opens a database connection on that Launch.

The browser-mark cookie `TSUGI-BROWSER-MARK` may be set during `LTIX::session_start()`. `TSUGI-BROWSER-MARK` is not the session id. A cookieless request can still send that one `Set-Cookie`.

## A new LTI launch does not call `LTIX::session_start()`

A new launch, including an LTI 1.3 POST that carries the JWT, is handled by `LTIX::requireData()`. `tool/gift/index.php` is the `requireData()` call. The callers of `LTIX::session_start()` are cookie pages: `tsugi.php`, `top.php`, a few admin scripts, and the cartridge upload. None of those cookie pages is the tool launch URL.

`LTIX::session_start()` and `requireData()` both enter `requireDataPrivate()`, and `requireDataPrivate()` calls `launchCheck()` before `requireDataPrivate()` looks at `NONE` versus context, link, and user. A JWT POST that did reach `LTIX::session_start()` would still be taken into `launchCheck()`. `launchCheck()` only decides that the POST is a launch, then calls `setupSession()`. `setupSession()` validates the launch. An invalid launch is rejected and nothing is stored. A valid launch is written into Tsugi: context, link, and user rows are inserted when missing, the resulting row is stored in `$_SESSION['lti']`, and the original post is stored in `$_SESSION['lti_post']`. The key row must already exist. A missing key, a bad signature, a reused nonce, a JWT that does not parse, or a platform public keyset that cannot be loaded rejects the launch. Those rejection paths call `abort_with_error_log()` and do not return a Launch. `abort_with_error_log()` always ends in `exit()`. If the launch POST already has `launch_presentation_return_url` or `launch_presentation_error_return_url`, the browser is redirected to that return URL with `lti_errormsg` set to the failure text. On an LTI 1.3 launch those return URLs are still inside the JWT, and `$_SESSION['lti_post']` has not been written yet, so the return URL is empty. The browser then gets an HTML error page titled "LTI Session is Missing or Expired" whose body is the failure text, such as `Failure loading keyset from ...`. `launchCheck()` then redirects to the launch URL and calls `exit()`. On a cookieless launch, `setupSession()` sets the session id from the launch before PHP's `session_start()`, and the redirect appends `_LTI_TSUGI`. The `launchCheck()` redirect does not return a Launch. Calling `LTIX::session_start()` is not how a launch is wired. The launch URL calls `requireData()`. The redirect is the request that reads `$_SESSION['lti']` back.

## When `LTIX::session_start()` does not come back

**Pre-1.3 launch authorization.** `launchAuthorizationFlow()` sends a redirect and returns true. `LTIX::session_start()` then returns with no value. The PHP session was not started. `launchAuthorizationFlow()` is not the LTI 1.3 JWT launch. The LTI 1.3 JWT launch calls `requireData()` and calls `exit()` inside `launchCheck()`, as described above.

A stored launch whose course has been deleted, or a session whose `script_path` does not match the current request, clears `$_SESSION` and dies. Those are the cases where `LTIX::session_start()` neither returns nor leaves a usable session.

## After a normal return

`$_SESSION` is an array. `session_id()` is non-empty. The return value is the `$TSUGI_LAUNCH` object. `$TSUGI_LAUNCH` is not null.

How the session id was chosen:

| Transport | What arrived | What `LTIX::session_start()` does |
| --- | --- | --- |
| Cookie | No `PHPSESSID` cookie | PHP `session_start()` creates a session and sends `Set-Cookie: PHPSESSID`. |
| Cookie | `PHPSESSID` cookie | PHP `session_start()` loads that session. The session id stays the cookie's id for the rest of the request. |
| Cookieless | `_LTI_TSUGI` in GET or POST | `LTIX::session_start()` calls `session_id()` with the `_LTI_TSUGI` value, then PHP `session_start()`. `$_SESSION` is that cookieless session. The session id stays the `_LTI_TSUGI` value for the rest of the request. No session `Set-Cookie` is sent. |
| Cookieless | No `_LTI_TSUGI` parameter | `NONE` does not die for a missing parameter. PHP `session_start()` creates a new id. No session `Set-Cookie` is sent. `session.use_trans_sid` can add the new id to URLs written later in the request. The browser will not send the new id back on its own. |

`requireData()` takes the cookieless "no parameter" row differently. Because `requireData()` asked for launch data, a missing `_LTI_TSUGI` dies with "please re-launch." `LTIX::session_start()` creates a new empty session instead.

## ReqScope on that return

`requireDataPrivate()` calls `provisionReqScope()` before `LTIX::session_start()` or `requireData()` returns. When `$_SESSION['lti']` is present, `buildLaunch()` runs first. `provisionReqScope()` does not replace `$USER`, `$CONTEXT`, `$LINK`, or `$RESULT`. When `$_SESSION['lti']` is missing, `provisionReqScope()` still runs and stores a null user and a null course.

`ReqScope` origin follows the transport. `COOKIE_SESSION` defined means origin `site`. `COOKIE_SESSION` undefined means origin `lti`. A Google login stores `$_SESSION['lti']` inside a cookie session, and that origin is still `site`.

- Cookieless session. The user, the course, and the link come from `$_SESSION['lti']`. The link is included when that row has a `link_id`.
- Cookie session. The user comes from `$_SESSION['id']`. The course comes from `$_SESSION['context_id']`. The link is included when `$_SESSION['lti']` names that same course. When `$_SESSION['id']` is missing and `$_SESSION['lti']` has a user, that row supplies the user, the course, and the link.

A missing user is stored as null. A missing course is stored as null. A user with no course is a valid `ReqScope`. A load failure is logged, and the request keeps the part that did load.

`context.key` on `ReqScope` is the tenant key, the same `key_key` `buildLaunch()` stores on the Launch context. `link.result_id` on the Launch link is the result id from the stored row, the same id `ReqScope` stores on the link.

`/courses/{id}/…` can run after `LTIX::session_start()` returns. That URL writes `$_SESSION['context_id']`, rebuilds the Launch from the updated row, and calls `ReqScope::replaceCourse()`. The same course is left as `LTIX::session_start()` filled it. A different course reloads membership, role, and instructor for the user already on `ReqScope`. The next request already has that course id in the session, so `LTIX::session_start()` fills that course and `replaceCourse()` does not load the membership again.

`ReqScope::loggedInUserId()`, `ReqScope::currentContextId()`, and `ReqScope::isLoggedIn()` read a cached pair. The pair is `$_SESSION['id']` and `$_SESSION['context_id']` when the session user id is set, and `$USER` and `$CONTEXT` otherwise. On a site login that pair matches `ReqScope`. On a cookieless LTI tool the pair can stay at user 0, because `Courses::restoreSiteLoginContext()` calls `ReqScope::isLoggedIn()` before `buildLaunch()`. LTI tools do not use those readers. `ReqScope` on that same request still holds the launch user and course.

## When the session contains a stored launch

After the session is open, `LTIX::session_start()` reads `$_SESSION['lti']`.

- The `$_SESSION['lti']` row is missing. `$USER`, `$CONTEXT`, `$LINK`, and `$RESULT` stay null. The returned Launch is the empty Launch built at the start. An empty Launch is the same for a cookie session and a cookieless session.
- The `$_SESSION['lti']` row is present. `buildLaunch()` fills the Launch from the `$_SESSION['lti']` row and sets the globals that the row contains: `$USER` when `user_id` is set, `$CONTEXT` when `context_id` is set, `$LINK` when `link_id` is set, `$RESULT` when `result_id` is set. The return value is that same Launch.

The `$_SESSION['lti']` row is the stored launch. The stored launch is not the transport. A cookie session can hold the `$_SESSION['lti']` row. A cookieless session can hold the `$_SESSION['lti']` row. Site login writes the `$_SESSION['lti']` row into the cookie session. A tool launch writes the `$_SESSION['lti']` row into whichever transport that tool's entry file selected.

## Why a cookie page calls `LTIX::session_start()`

The `COOKIE_SESSION` define only selects `PHPSESSID`. The `COOKIE_SESSION` define does not open the session, and the `COOKIE_SESSION` define does not rebuild the launch globals.

`tsugi.php` defines `COOKIE_SESSION`, loads `config.php`, then calls `LTIX::session_start()` because the controller app is constructed with the Launch that `LTIX::session_start()` returns. On a normal visit the cookie session has no `lti` row yet, so the Launch and `ReqScope` come back with a null user and a null course, and the page still has a live `PHPSESSID`. After a site login that same `LTIX::session_start()` call finds `$_SESSION['id']`, `$_SESSION['context_id']`, and `$_SESSION['lti']`. `buildLaunch()` fills `$USER` and `$CONTEXT`, and `ReqScope` holds that same user and course for the rest of the request.

`index.php` does not need the Launch. `index.php` calls PHP's `session_start()` and never enters `LTIX::session_start()`.

A tool that must have a launch calls `requireData()`, not `LTIX::session_start()`.

# Trying the APIs in a browser

The scripts under `api/` are HTTP endpoints. A few are GET and can be opened in the address bar. The rest are POST, so you send them from the developer-tools console of a page on this same Tsugi. The automated checks for the same endpoints are in [panther-qa.md](panther-qa.md).

Use the Tsugi you already have open. Paths below are under that root, for example `https://local.dj4e.com/tsugi` or `http://localhost:8000/tsugi`.

There are two different sessions. A site login (the cookie you get from logging in) is what `notifications.php` and `analytics_cookie.php` read. A tool launch is a separate session. Its id appears in the tool page as `_LTI_TSUGI=...`, and the launch endpoints only see it when that id is on the request. Logging in does not create it, and the launch id is not your login cookie.

Do the annotate and stickygrader calls last. Those two clear the launch session.

## Logged out

Open a private window so no login cookie is sent.

| Open this | You should see |
|---|---|
| `api/notifications.php` | HTTP 403, JSON `status` `error`, `detail` `Not logged in` |
| `api/analytics_cookie.php` | HTTP 403, JSON `error` `No link_id` |
| `api/analytics_cookie.php?link_id=1` | HTTP 403, JSON `error` `Not logged in` |
| `api/analytics.php` | HTTP 403, JSON `status` `error` |
| `api/no-such-endpoint` | HTTP 404, an HTML page that says `Page not found.` |
| `api/annotate/` | HTTP 500, text `Missing Session` |
| `api/stickygrader/` | HTTP 500, text `Missing Session` |

`api/settings.php`, `api/grade-submit.php`, `api/record-attempt.php`, and `api/socket.php` are the same 403 JSON when opened with no launch id. The grade and attempt scripts answer that on POST. Opening them with GET still fails the session check.

## Signed in

Log in the way you usually do. If this Tsugi has demo login on, that form is `login/simulate`.

Open `api/notifications.php`. HTTP 200, JSON `status` `success`, with `notifications`, `announcements`, and the three unread counts. With no course in the session the announcement list is empty. That is a successful call.

`api/analytics_cookie.php` with no `link_id` is still 403 `No link_id`. With `link_id=999999999` it is 403 `Invalid link_id`.

A real `link_id` is the number in a course URL such as `.../link/123` on a quiz Take link. Admin → Activity also lists `link_id` after a tool has been launched.

- As the instructor of that course, `api/analytics_cookie.php?link_id=123` is HTTP 200 and a chart object (`rows`, `n`, `width`, `timestart`). An empty `rows` array means the link has no activity yet.
- As a student, the same URL is HTTP 403 `Not authorized`.
- If you have unlocked the admin console in this same browser, the admin flag allows any `link_id`. Lock the console again before you treat a 200 as the instructor rule.

## A tool launch

The launch endpoints need a tool session.

If developer mode is on, open the store, choose Quizzes (Gift), and click Try It as Jane Instructor. Otherwise launch any tool from a course. Either way the tool is in a frame.

In DevTools, switch the console to that frame (the frame list at the top of the console). The frame's HTML contains the session id. This copies it:

```javascript
copy((document.documentElement.outerHTML.match(/_LTI_TSUGI=([A-Za-z0-9,-]+)/) || [])[1])
```

Stay in that frame's console for the rest of this section. `_TSUGI.wwwroot` is the root to call.

```javascript
const base = _TSUGI.wwwroot;
const session = (document.documentElement.outerHTML.match(/_LTI_TSUGI=([A-Za-z0-9,-]+)/) || [])[1];
```

If `session` is empty, the frame source has not been written yet. Reload Try It and run it again.

### Open these in the address bar

Paste `session` into the query string.

| Open this | You should see |
|---|---|
| `api/analytics.php?_LTI_TSUGI=...` | HTTP 200, JSON with `rows`, `n`, `width`, `timestart` |
| `api/socket.php?_LTI_TSUGI=...` | HTTP 200, a JSON array. It is `[]` until you post a message. |

### Send these from the frame console

The address bar cannot POST. Watch the Network panel for the status and the body. `response.json()` prints the body in the console.

Settings. An empty object does not change the link. The response body is `{}`.

```javascript
fetch(base + '/api/settings.php?_LTI_TSUGI=' + session, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
  body: '{}'
}).then(r => r.text()).then(console.log)
```

The same call without `X-CSRF-TOKEN` is HTTP 403 and JSON `error` `Missing or invalid CSRF token`. `CSRF_TOKEN` is a variable already set on the tool page.

Socket. Post a line, then open the GET URL from the table again. The array contains that message.

```javascript
fetch(base + '/api/socket.php?_LTI_TSUGI=' + session, {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: 'message=hello+from+the+console'
}).then(r => console.log(r.status))
```

The POST body is empty. The following GET is the check. Room `0` is the default. `api/socket.php/1?_LTI_TSUGI=...` is room 1, a different list.

Grade submit and record attempt. A normal launch does not set the budget these scripts require. Both are HTTP 200 with `status` `failure`.

```javascript
fetch(base + '/api/grade-submit.php?_LTI_TSUGI=' + session, {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: 'grade=1'
}).then(r => r.json()).then(console.log)

fetch(base + '/api/record-attempt.php?_LTI_TSUGI=' + session, {
  method: 'POST'
}).then(r => r.json()).then(console.log)
```

You should see `Missing GSRF token` and `Missing RECORD_ATTEMPT_GSRF token`. A tool sets those session counters before it is willing to accept a grade or an attempt. There is no button in core Tsugi that sets them.

### Annotate and stickygrader

Run these after everything above. Each one answers HTTP 400, HTML that says `Improper navigation detected`, and then that launch session is gone. Later calls with the same `session` value say the session is missing. Start a new Try It if you need a fresh id.

```javascript
fetch(base + '/api/annotate/' + session + ':1').then(r => r.text()).then(console.log)
fetch(base + '/api/stickygrader/' + session + ':1').then(r => r.text()).then(console.log)
```

A launch that started in `tool/gift` may call scripts that sit directly in `api/`. Annotate and stickygrader are one folder deeper, so this check refuses them. Their GET, POST, PUT, and DELETE actions are not reachable from a store launch.

Opening `api/annotate/` or `api/stickygrader/` with nothing after the slash is HTTP 500 `Missing Session`. `api/annotate/not-a-session` is HTTP 500 `Missing user_id`.

## Grade passback, roster, and RPC

These three are called by an LMS or by a tool page, not by typing a URL.

`api/poxresult.php` is the LTI 1.1 grade service. It expects a signed XML body. From the console, a plain-text body shows the rejection:

```javascript
fetch(base + '/api/poxresult.php', {
  method: 'POST',
  headers: { 'Content-Type': 'text/plain' },
  body: 'hello'
}).then(r => { console.log(r.status, r.statusText); return r.text() }).then(console.log)
```

Status 400, and the text says the content type must be XML. A body of `not-xml` with content type `application/xml` is 400 `Expecting XML`. A real replace, read, or delete is a signed POX document from the LMS. The address bar cannot build that signature.

`api/ltiextroster.php` reads a course roster. It expects a signed form field `id` of the shape `key::context::link::signature`. A bad id is enough to see the guard:

```javascript
fetch(base + '/api/ltiextroster.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: 'id=1::2::3'
}).then(r => console.log(r.status, r.statusText))
```

Status 400, status text `Invalid sourcedid format`. `id=1::2::nope::sig` is 400 `sourcedid requires 4 numeric parameters`. `id=1::2::3::not-a-real-signature` is 403, and the `X-Error-Message` response header says it could not locate the row. A successful member list needs roster sharing turned on for that link and a signature that matches the link secret.

`api/rpc.php` runs a method from the POST body. Send it with no token and stop there:

```javascript
fetch(base + '/api/rpc.php', { method: 'POST' })
  .then(r => r.text()).then(console.log)
```

The body contains `{"detail":"No token"}`. Do not add `object` and `method`. That call runs whatever names you post, on the launch.

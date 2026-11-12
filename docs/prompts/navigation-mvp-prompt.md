# Historical: Course navigation MVP prompt

Draft started 2026-09-15. This file is the working design / implementation
prompt for a course-scoped top navigation. It is archived here so we can
look back at the starting intent.

**This is not live product rules.** After the MVP ships, the code will
keep changing. Do not copy this into `.cursor/rules/`, `AGENTS.md`, or
`docs/README.md`. Do not treat this file as current agent or contributor
guidance. If the running code disagrees with this document, the code is
right.

Status: **MVP implemented 2026-09-15**. This file is still a historical
prompt, not live product rules. The running code may already differ.

---

## One-sentence goal

On `/courses/{id}/…` the top bar is that course’s teacher-edited
navigation. On every other URL the top bar is the site menu
(`buildmenu.php` / `$CFG->top_menu_callback`). The two worlds do not
mix.

---

## Decisions (2026-09-15)

### Which menu owns the request

- **`/courses/{id}/…`:** always the **course** menu from that course’s
  manifest. Never `buildmenu.php`, even if a site callback is configured.
- **Any URL without `/courses/{id}`:** always the **site** menu
  (`top_menu_callback` → `buildmenu.php`, then the existing fallbacks).
  A course sitting in the PHP session does **not** switch menus.
- Trigger is the **URL**, not `currentContextId()`. Same split as
  `Tool::isCourseRoute()`.

Chuck will later change enclosing-site `buildmenu.php` so it stops
emitting course-prefixed URLs and stops caring about the session
course. That is **site work**, not this Tsugi MVP. After that, global
nav never generates `/courses/{id}/…` links. A later site-wide **courses
picker / widget** in the global bar is how you re-enter a course after
a site-exit link (Profile, Login, Logout).

### Locked chrome (not in the checkbox grid)

The compiler **always** injects these. They are not stored in the JSON.
Hand-edited `home` / `settings` / `avatar` ids are ignored.

- **Home** — always upper left (`navbar-brand`). Label is the word
  **Home** (not the course title). Href `/courses/{id}/home`. Teachers
  cannot remove it. The Home **page** will later be configurable; the
  Home **slot** is not optional.
- **Avatar** — always on the right. Face (or display name if there is
  no avatar) opens the dropdown. The avatar menu stays; it is the
  course equivalent of `buildmenu.php`’s avatar menu.
- **Settings** — always last in the **avatar dropdown** →
  `/courses/{id}/settings`. Teachers cannot remove it, cannot drag it,
  cannot place it left/right. Hidden for students
  (`Settings::showInMenu()`).

Later, not MVP: a house icon when the bar is narrow; maybe a **short**
course title instead of “Home”. Never the full course title (it wrecks
the bar). Notifications may someday move onto Home (red dot, then the
Home page). Do not do those in this MVP.

### Three placements for ordinary links

Every optional **link** has three independent checkboxes:

| Placement | Where it renders |
|-----------|------------------|
| **Upper left** | Left strip next to Home (`navbar-main`) |
| **Upper right** | Right strip, sibling of the avatar, **not** inside it |
| **Avatar dropdown** | Under the face, **above** Settings |

Any combination, or none. Same tool may appear in more than one place.

### Widgets (Left / Right only)

Widgets are **separate catalog rows** from the matching **link** (the
word “Discussions”, “Calendar”, …). Widgets cannot go in the avatar
dropdown.

MVP widgets, existing components, course-mounted API/view URLs,
`hidden-xs`:

| Id | Component | Notes |
|----|-----------|--------|
| `notifications_widget` | `<tsugi-notifications>` | **No separate announcements widget.** This control is the current conglomeration of notifications **plus** announcements. Leave that behavior as-is. Future: may collapse further onto Home. |
| `discussions_widget` | `<tsugi-discussions>` | Leave as-is. Do not fold discussion alerts into Home or into the notifications widget. |
| `calendar_widget` | `<tsugi-calendar-due>` | Same placement rules as the other widgets (top left and/or top right). |

The **Announcements** *link* (the word) remains a normal three-placement
catalog item. It is not a widget.

### Site-exit links (teacher-placeable)

**Profile**, **Login**, and **Logout** are ordinary catalog links (Left /
Right / Dropdown). They are **not** course-mounted:

- Profile → `/profile` (site)
- Login → `/login` (site)
- Logout → `/logout` (site)

Following any of these **leaves the course**. That is intended. Return
path is the future global courses picker in `buildmenu.php`, not
something this MVP builds.

Runtime filter (even if the teacher checked them):

- **Login** only when the user is **not** logged in.
- **Profile** and **Logout** only when the user **is** logged in.

**New course default:** Logout **on**, avatar dropdown. Profile and
Login default **off**. Editing UI should hint to keep Logout on.

If Logout (or Profile, or Login) is in the dropdown, it sits in the
teacher-ordered dropdown list **above** locked Settings.

Admin, LMS Integration, Google Classroom, and the site Courses picker
stay **out** of the course catalog.

### New-course / missing JSON default

Compiler chrome plus one stored item:

- Home (injected, left, label “Home”)
- Avatar (injected, right)
- Logout in the avatar dropdown (stored)
- Settings last in the avatar dropdown (injected, instructors only)

Write this JSON at course create so the editor shows Logout already
checked. Treat NULL / missing / `{}` / `"items": []` as the same
default.

### Students

- Hide Settings.
- Avatar is still there.
- Empty-dropdown student filler is later. Do not duplicate Home into
  the dropdown for MVP.

### Authoring (Settings → Navigation tab)

`/courses/{id}/settings`, third tab next to Theme and Export.
Instructors of a manifest course only.

Grid of optional rows (not Home, not Settings, not Avatar):

- Each **link** row: checkboxes Left / Right / Dropdown.
- Each **widget** row: checkboxes Left / Right only.
- Logout row: same as a link, plus a short “keep this on” hint.
- Drag-reorder the optional rows. **One global order**, applied
  **within** each placement. No separate left-order vs dropdown-order
  for the same id.
- Home, Avatar, and Settings do not participate in the drag list.

Save → new manifest version (same as theme). Comment `"Set navigation"`.

### Responsive (existing chrome)

Reuse `Output::menuNav()` / Bootstrap collapse:

- Home (`navbar-brand`, text “Home”) stays visible at every width.
- Avatar stays on the right (wide screens).
- Narrow screens: left items, upper-right items, and the avatar
  dropdown collapse into the hamburger. Do not try to keep a long top
  strip.
- `hidden-xs` widgets hide on extra small.
- House icon instead of the word “Home” when narrow is **later**.

Do not build a second navbar system for the MVP.

---

## Prompt evaluation (this turn)

Closed: no `announcements_widget`; notifications widget keeps the
combined bell; discussions widget unchanged; calendar widget in MVP;
Home label is “Home”; Profile and Login are teacher-placeable **site**
exits. The courses picker in global nav is the way back — out of
scope here.

The doc is product-complete enough to become an implementation prompt
once Chuck says to implement.

---

## Current system (snapshot, will rot)

Do not treat this section as a spec. It is the 2026-09-15 starting point
so the MVP has somewhere to hook.

- Cookie-session top nav (`Output::topNav()`):
  1. `$CFG->top_menu_callback` if callable (DJ4E `buildmenu.php` lives
     **outside** Tsugi; Tsugi never loads it itself).
  2. `$CFG->defaultmenu` if it is a `MenuSet`.
  3. Session export from `$OUTPUT->topNavSession()`.
  4. `Output::defaultMenuSet()`.
- Course URLs: `/courses/{id}/announcements` names the course for
  **that request**. Bare `/announcements` uses whatever context is
  already in the session. See `docs/courses-urls.md`.
- `Tool::isCourseRoute()` is the reliable split. Do not use
  `currentContextId()` to choose the **menu system**.
- Course Settings today: tabs **Theme** and **Export**. Instructor +
  active manifest only (`Settings::showInMenu()`).
- Manifest sibling columns for course setup (theme is a VARCHAR key).
  Do **not** grow the lessons JSON blob. Navigation is another sibling
  column (JSON).
- DJ4E `buildmenu.php` today: brand, left Lessons/Assignments, right
  avatar dropdown, plus `<tsugi-notifications>`, `<tsugi-discussions>`,
  `<tsugi-calendar-due>` with `hidden-xs`. Some hrefs are prefixed
  `/courses/{cid}` when `courses_in_urls` is on. Chuck will stop that
  later.
- `MenuSet` export/import is a **renderer** format. Stored course nav
  is a catalog compiled to a `MenuSet` at request time.

---

## Target behavior

### Output::topNav()

If the request path is `/courses/{id}/…`, skip `top_menu_callback` and
friends. Load that course’s manifest navigation JSON (or the new-course
default). Compile to a `MenuSet`. Render with `menuNav()`.

Otherwise leave the site chain unchanged.

Course tools use course-mounted hrefs (`/courses/{id}/files`).
Profile / Login / Logout use **site** hrefs and leave `/courses/{id}`.

### Compiler

1. `setHome('Home', …/courses/{id}/home)`.
2. `addLeft` each item with `left` in JSON order (links or widgets).
3. `addRight` each item with `right` in JSON order (links or widgets).
4. `addRight` the avatar submenu: dropdown links in JSON order, then
   Settings if `Settings::showInMenu()`.
5. Unknown ids ignored.
6. Apply login-state filters for `login` / `logout` / `profile`.
7. If a controller defines `showInMenu()` and it is false for this
   user, omit that item (Settings is the important case).

---

## Suggested JSON

Optional rows only. Placements are booleans; omitted means false.

```json
{
  "items": [
    { "id": "logout", "dropdown": true },
    { "id": "lessons", "left": true },
    { "id": "files", "left": true, "dropdown": true },
    { "id": "announcements", "dropdown": true },
    { "id": "profile", "dropdown": true },
    { "id": "notifications_widget", "right": true },
    { "id": "discussions_widget", "right": true },
    { "id": "calendar_widget", "right": true }
  ]
}
```

New-course written JSON:

```json
{
  "items": [
    { "id": "logout", "dropdown": true }
  ]
}
```

---

## Catalog (MVP)

**Always injected (not in JSON):** Home, Avatar, Settings.

**Link tools** (Left / Right / Dropdown), course-mounted unless noted:

Lessons, Assignments, Files, Pages, Grades, Quiz1, Calendar, Topics,
Analytics, Badges, Labs, Map, Announcements, Discussions,
Notifications, **Profile** (site `/profile`), **Login** (site
`/login`), **Logout** (site `/logout`).

**Widgets** (Left / Right only): `notifications_widget`,
`discussions_widget`, `calendar_widget`.

**Not in the course catalog:** Admin, site CMS pages, LMS Integration,
Google Classroom, site Courses picker.

There is **no** `announcements_widget` id.

---

## Explicitly later / out of scope for this MVP

- Editing DJ4E `buildmenu.php` (Chuck). Tsugi only stops **calling** it
  on `/courses/{id}/…`.
- Site-wide courses picker / widget in the global bar (the way back
  after Profile/Login/Logout).
- House icon for Home when the bar is narrow.
- Course short-title as the brand (instead of “Home”).
- Moving notifications/announcements onto the Home control or Home
  page.
- Extra student-only avatar-dropdown content when the dropdown would
  otherwise be empty.
- Putting navigation inside the lessons JSON document.
- Free-form URLs, custom HTML labels, nested teacher-defined dropdowns.
- Per-student or per-section menus.
- File-based `$CFG->lessons` sites (no authorable manifest).
- Advertising `/courses/{id}` via `courses_in_urls`.
- Using this document as Cursor rules after implementation.

---

## Open questions

None that should change the MVP shape. If anything is still wrong, it
is catalog membership of obscure tools (Topics, Labs, Analytics, …),
not the chrome model.

---

## Implementation prompt (not active until Chuck says so)

When this document is the prompt:

- Add a JSON sibling column on `manifest` (not inside the lessons
  blob). Load/save through `Tsugi\Core\Manifest`. Version like theme.
- Write the new-course default JSON (Logout in the dropdown).
- Settings → Navigation tab: checkbox grid + drag order as above.
- In `Output::topNav()`, if the request is `/courses/{id}/…`, compile
  this catalog to a `MenuSet` and skip `top_menu_callback`. Otherwise
  do not change the site menu chain.
- Do not edit enclosing-site `buildmenu.php`.
- Do not add Cursor rules or `AGENTS.md` entries for this feature.

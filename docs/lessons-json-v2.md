# Lessons JSON v2

Lessons now has a canonical in-memory model based on a small set of
foundational resource types, while keeping the richer Lessons meaning on
`subtype`. Stored `lessons.json` files are **not** rewritten when they are
read.

## Foundational types

| Type | Meaning |
|------|---------|
| `heading` | Structural module heading (not a resource) |
| `web_link` | External or web-addressable URL |
| `html_page` | Course-owned page content (Markdown, HTML, `{apphome}` page, later a Pages record) |
| `file` | Stored or course-owned file |
| `discussion` | Forum topic (title, `resource_link_id`, optional description) |
| `lti` | LTI launch (quiz, autograder, peer-grade, and other tools) |

`text`, `carousel`, and `chapters` stay as structural/legacy types and are
not remapped.

## Subtypes

Subtype is a separate dimension: `reference`, `video`, `slides`,
`assignment`, `solution`, `quiz`, `autograder`, `peer_grade`.
Discussion is a foundational type (not a subtype). A future Common Cartridge
import can add a `resource_link_id` to a topic title/description and launch
`tsugi/tool/tdiscus`.

Example:

```json
{
  "type": "web_link",
  "subtype": "video",
  "title": "Django Models",
  "href": "https://www.youtube.com/watch?v=AqsPifp-ccc",
  "content_type": "text/html",
  "youtube": "AqsPifp-ccc",
  "kaltura_id": "1_55pyvf75",
  "media": "lesson-08-models/01-DJ-02-Models.m4v"
}
```

```json
{
  "type": "discussion",
  "title": "Welcome to Django for Everybody",
  "description": "Introduce yourself",
  "resource_link_id": "discussion_welcome"
}
```

## Legacy mapping

| Legacy type | Foundational type | Subtype |
|-------------|-------------------|---------|
| `header` | `heading` | (none; `text` becomes `title`) |
| `reference` | `web_link` | `reference` |
| `assignment` | `web_link` | `assignment` |
| `solution` | `web_link` | `solution` |
| `slide` / `slides` / `lecture` | `web_link` | `slides` |
| `video` | `web_link` | `video` |
| `discussion` | `discussion` | (none; implied launch is `tsugi/tool/tdiscus`) |
| `lti` with launch `tsugi/tool/tdiscus` | `discussion` | (none; launch dropped) |
| Other `lti` | `lti` | inferred from launch when reliable (`mod/tdiscus/` stays LTI) |

Legacy `lessons.json` has no `file` or `html_page` items — slides, assignments,
and references are hrefs. Normalization never invents those types. `file` and
`html_page` are only created by new Lessons JSON v2 authoring.

`{apphome}` / `{wwwroot}` hrefs stay as macros. `{apphome}` is not rewritten
to `$IMS-CC-FILEBASE$` — apphome is typically a folder above the cartridge
file base. Render expands `{apphome}` to `$CFG->apphome`. Sibling domains
(`samples.example.com` vs the course host) stay as ordinary web links.

An explicit `subtype` is never replaced by inference.

## Unknown fields

Recognized fields may be promoted (`header`/`text` → `heading`/`title`,
YouTube id → `href`). Every other property is copied through, including
historical or misspelled keys (`youtube-2016`, `FCP`, `todo`, …).

## Files identity

When a file is stored in Tsugi Files:

```json
{
  "type": "file",
  "subtype": "slides",
  "title": "Week One Reading",
  "href": "/files/download/8c2f4d...",
  "sha256": "8c2f4d...",
  "filename": "week-one.pdf",
  "content_type": "application/pdf"
}
```

`sha256`, `href`, `filename`, and `title` are independent. Filename is never
taken from the module title, and SHA-256 is taken from file metadata or the
download href — not by treating the filename as a storage key.

`content_type` is author-supplied, inferred from an extension, or (for stored
files) the Files subsystem MIME type. Ordinary read/render does not fetch
remote URLs to discover a web-link content type.

## Icons

Resolution order:

1. Explicit `icon` (type key or `fa-*` class)
2. Recognized subtype (with the existing PDF override for link-like items)
3. Recognized `content_type` (for example `application/pdf`)
4. Foundational type

Video, slides, assignment, reference, discussion, and LTI keep their current
icons. Quiz / autograder / peer-grade currently reuse the LTI icon so
existing courses do not change appearance.

## Authoring

Authoring is only for a **database-backed Lessons JSON v2** course.

* **Add course** writes `Manifest::starter()` as v2 (`lessons_json_version: 2`)
  into a new manifest. That is the only on-ramp for v2 in the database.
* Author is shown only when `Manifest::canAuthorCurrent()` is true
  (active manifest and stored document is v2).
* Save and import write v2 to a new manifest version. They never write
  `$CFG->lessons` or any other course file.
* File-backed classic `lessons.json` (PY4E, DJ4E, …) is view-only.
  Instructors get **Export lessons.json v2** on the Lessons page so they
  can download the in-memory normalized document without authoring or
  rewriting the file.

The author editor creates foundational types plus subtypes (for example
a video is `web_link` / `video`, slides are `web_link` / `slides`, an
assignment is `web_link` / `assignment`, a file is picked from course Files,
a discussion is `discussion` with a title, optional description, and
`resource_link_id`). File items get `href`, `sha256`, `filename`, and
`content_type` from the chosen Files row. Full HTML page WYSIWYG is still
later work.

## Export

From the Lessons author screen (v2 courses only):

* **Export lessons.json** — the stored manifest document.
* **Export lessons.json v2** — normalized canonical JSON (`lessons_json_version: 2`).

Routes: `/lessons/_author/export` and `/lessons/_author/export-v2`.

## Compatibility

* Reading a file never mutates it on disk.
* The in-memory `Lessons` object normalizes `items` after load, then applies
  the existing URL expansion for rendering.
* Normalization is idempotent: `normalize(normalize(item)) == normalize(item)`.

## Deferred

* IMS Common Cartridge import/export, XML namespaces, ZIP packaging, and
  restoring human filenames in a cartridge.
* HTML Page authoring and binding to Tsugi Pages (`page_id` vs `href` vs
  embedded `content` vs `logical_key`).
* Uploading a new file from the Lessons author dialog (pick existing
  course Files only).
* Converting legacy `media` paths into SHA-256 file records.
* A multi-representation media schema beyond the current YouTube / Kaltura /
  `media` fields.

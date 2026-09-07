# Historical: Lessons JSON v2 kickoff prompt

This is the original conversation prompt that started the Lessons JSON v2
work (2026-09-04). It is archived so we can look back at the starting
intent.

**This is not the current design.** Decisions after this prompt changed
the model (for example discussion became a foundational type, assignments
stayed web links, and legacy JSON never normalizes to `file` or
`html_page`). See [lessons-json-v2.md](../lessons-json-v2.md) for what
was actually implemented.

Do not treat this file as agent or contributor guidance.

---

We are going to evolve Tsugi Lessons toward a data model based on the foundational resource concepts found in IMS Common Cartridge, while preserving the richer semantics, icons, metadata, and authoring experience already present in Lessons.

This is an implementation task, but begin by inspecting the repository carefully. In particular, locate and understand:

* `Lessons.php` and related Lessons classes.
* The code that reads and normalizes `lessons.json`.
* The Lessons rendering code.
* The Lessons authoring code.
* The existing “Export JSON” feature.
* Any existing JSON import/reload functionality.
* The Tsugi Pages implementation.
* The Tsugi Files implementation.
* How file records, filenames, content types, and SHA-256 identifiers are stored.
* Existing tests and fixtures related to Lessons, Pages, Files, and JSON export.
* Any sample `lessons.json` files in the repository.

Do not begin by performing a broad mechanical conversion. First determine the actual code paths and existing conventions. Preserve backward compatibility.

## Overall objective

Create a new Lessons data model that uses a small set of foundational resource types:

```text
heading
web_link
html_page
file
lti
```

These foundational types should express what kind of underlying course object an item represents.

Separately, preserve the richer Lessons meaning through a `subtype`, such as:

```text
reference
video
slides
assignment
discussion
quiz
autograder
peer_grade
```

The foundational type and Lessons subtype are separate dimensions.

Examples:

```json
{
  "type": "web_link",
  "subtype": "reference",
  "title": "PythonAnywhere",
  "href": "https://www.pythonanywhere.com/",
  "content_type": "text/html"
}
```

```json
{
  "type": "lti",
  "subtype": "discussion",
  "title": "Welcome to Django for Everybody",
  "launch": "tsugi/tool/tdiscus/",
  "resource_link_id": "discussion_welcome"
}
```

```json
{
  "type": "file",
  "subtype": "slides",
  "title": "Django Data Models",
  "href": "/files/download/8c2f4d...",
  "sha256": "8c2f4d...",
  "filename": "DJ-02-Model-Single.pptx",
  "content_type": "application/vnd.openxmlformats-officedocument.presentationml.presentation"
}
```

Common Cartridge is the conceptual foundation, but it must not become the ceiling on the Lessons data model. Lessons must retain its current richer presentation and semantics.

## Scope for this change

For this phase:

* Add the foundational data model.
* Add lossless normalization of legacy Lessons objects.
* Teach existing rendering code to understand the normalized model.
* Preserve the appearance and behavior of existing courses.
* Add a new Lessons JSON v2 export representing the normalized model.
* Add or update tests for normalization, rendering compatibility, and JSON v2 output.

Do not implement IMS Common Cartridge import or export in this phase.

Do not implement full HTML Page authoring in this phase.

Do not implement full File authoring or uploading in this phase.

It is acceptable to recognize and render `html_page` and `file` objects before the authoring UI can create them. We want the model stable before adding those larger features.

Do not make unrelated architectural or formatting changes.

## Critical compatibility requirement

Existing `lessons.json` files must continue to load and render correctly without being rewritten.

The first major success criterion is:

> Read an existing legacy `lessons.json`, normalize it into the new model, and render a Lessons page that is visually and behaviorally equivalent to the current page.

Do not mutate the source JSON merely by reading it.

Do not migrate stored JSON automatically.

Do not discard fields that are not yet understood.

## Foundational model

Use these foundational types unless existing repository conventions strongly justify slightly different names:

```text
heading
web_link
html_page
file
lti
```

Use `subtype` to preserve the current Lessons semantics and presentation.

Suggested mapping:

| Legacy Lessons type         | Foundational type         | Subtype                      |
| --------------------------- | ------------------------- | ---------------------------- |
| `header`                    | `heading`                 | `section`, or no subtype     |
| External `reference`        | `web_link`                | `reference`                  |
| Course-owned page reference | `html_page`               | `reference`                  |
| `assignment`                | `html_page`               | `assignment`                 |
| External `slide`            | `web_link`                | `slides`                     |
| Course-owned `slide`        | `file`                    | `slides`                     |
| YouTube/Kaltura `video`     | `web_link`                | `video`                      |
| Stored/local media `video`  | `file`, where appropriate | `video`                      |
| `discussion`                | `lti`                     | `discussion`                 |
| Existing generic `lti`      | `lti`                     | inferred or explicit subtype |
| Peer grader                 | `lti`                     | `peer_grade`                 |
| Quiz tool                   | `lti`                     | `quiz`                       |
| Autograder                  | `lti`                     | `autograder`                 |

Treat this table as a design guide rather than a reason to make unsafe guesses. The actual object fields, launch paths, and rendering behavior should guide the normalization rules.

When classification is ambiguous, preserve the legacy meaning and fields rather than aggressively reclassifying the object.

## Do not over-normalize

The v2 JSON should remain readable, natural JSON. Do not turn it into a literal JSON translation of `imsmanifest.xml`.

Prefer:

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

Avoid unnecessary structures such as:

```json
{
  "type": "resource",
  "resource_type": "web_link",
  "presentation": {
    "semantic_type": "video"
  }
}
```

unless the existing architecture makes such a structure clearly preferable.

Common, useful properties should remain easy to read and access. We can decide later how these properties map into standard Common Cartridge XML versus Tsugi extensions.

## Preserve all existing detail

Legacy objects contain rich information that must survive normalization and v2 JSON export, including fields such as:

```text
youtube
kaltura_id
media
FCP
project
todo
note
notes
review
target
custom
learning_objectives
resource_link_id
youtube-2016
youtube-2018
youtube_pre_2025
```

Some historical files may contain misspelled, obsolete, or currently unknown attributes. Preserve them.

Use this rule:

> Recognized properties may be promoted or normalized, but unrecognized properties must survive unchanged.

Do not silently discard metadata merely because it has no Common Cartridge equivalent.

Lessons JSON v2 is a superset-oriented format, not a least-common-denominator format.

## Content type

Both `file` and `web_link` objects may have a `content_type` property.

Use MIME types:

```json
{
  "type": "web_link",
  "href": "https://example.org/reading.pdf",
  "content_type": "application/pdf"
}
```

```json
{
  "type": "file",
  "href": "/files/download/...",
  "filename": "week-one.pdf",
  "content_type": "application/pdf"
}
```

For a web link, `content_type` may be known, author-supplied, inferred, or absent. Do not require a live network request during ordinary reading or rendering merely to determine it.

For a stored file, use the authoritative content type from the Tsugi Files subsystem whenever it is available.

Use the property name `content_type` unless the repository already has a strongly established naming convention that should be reused.

## Tsugi Files semantics

Tsugi serves stored files using this URL form:

```text
/files/download/{sha256}
```

The SHA-256 is effectively the logical storage key.

A normalized file object should be able to preserve these concepts independently:

```json
{
  "type": "file",
  "title": "Week One Reading",
  "href": "/files/download/8c2f4d...",
  "sha256": "8c2f4d...",
  "filename": "week-one.pdf",
  "content_type": "application/pdf"
}
```

The fields have distinct purposes:

* `sha256`: stable logical identity in Tsugi file storage.
* `href`: retrieval URL.
* `filename`: original, human-facing, download, and eventual packaging filename.
* `content_type`: MIME type.
* `title`: label displayed in the course module.
* `subtype`: Lessons semantic and presentation meaning.
* `icon`: optional presentation override.

Do not treat `title` and `filename` as interchangeable.

Even though the SHA-256 may be derivable from `href`, preserve it explicitly when the file subsystem provides it. Other code should not have to parse Tsugi URL conventions to recover file identity.

Inspect the actual Files implementation before finalizing this representation. Reuse existing file metadata and helper methods rather than duplicating storage logic.

## Icon and rendering behavior

Do not reduce the Lessons interface to five generic icons.

The existing video, slides, assignment, reference, discussion, quiz, grader, and other useful visual distinctions should remain.

A reasonable default-resolution order is:

1. Explicit `icon`.
2. Recognized `subtype`.
3. Recognized `content_type`.
4. Foundational `type`.

Examples:

* `subtype: video` produces the video presentation.
* `subtype: slides` produces the slides presentation.
* `subtype: discussion` produces the discussion presentation.
* `content_type: application/pdf` can produce a PDF icon.
* An unknown `file` produces the generic file icon.
* An unknown `web_link` produces the generic web-link icon.
* An `lti` item without a recognized subtype produces the generic LTI presentation.

Reuse existing icon and rendering mechanisms. Do not duplicate the entire renderer for v2 objects if an adapter or common rendering method will work.

## Video objects

Videos are richer than a single web link. A legacy video may contain:

* A YouTube ID.
* A Kaltura ID.
* A local media path.
* Historical YouTube IDs.
* Production notes.
* Review flags.
* Project information.

Do not flatten these into one URL and discard the alternatives.

For this phase, it is acceptable for v2 video JSON to preserve the existing fields directly:

```json
{
  "type": "web_link",
  "subtype": "video",
  "title": "Django Models",
  "href": "https://www.youtube.com/watch?v=AqsPifp-ccc",
  "content_type": "text/html",
  "youtube": "AqsPifp-ccc",
  "kaltura_id": "1_55pyvf75",
  "media": "lesson-08-models/01-DJ-02-Models.m4v",
  "note": "Audio is messed up in FCPX"
}
```

Do not introduce a complex new `representations` schema unless the existing code demonstrates that it is needed now. That can be a later refinement.

If a media alternative is already stored in the Tsugi Files subsystem, it may eventually use a full file descriptor containing `href`, `sha256`, `filename`, and `content_type`. Do not force conversion of all existing media paths during this phase.

## HTML page meaning

Define and support the `html_page` model without yet solving its complete authoring lifecycle.

For now, `html_page` means:

> Course-owned page content, regardless of whether its current source is Markdown, HTML, a legacy `{apphome}` page, or eventually a Tsugi Pages database record.

A minimal object may be:

```json
{
  "type": "html_page",
  "subtype": "assignment",
  "title": "Installing Django",
  "href": "{apphome}/assn/dj4e_install52.md"
}
```

Do not prematurely decide whether the final authoring representation must use:

* `page_id`
* `href`
* Embedded `content`
* A Pages database identifier
* A generated HTML artifact

Inspect Pages and document what will need to be decided later, but defer the new HTML Page authoring feature.

## Heading semantics

Legacy headers such as:

```json
{
  "type": "header",
  "text": "Videos",
  "level": 2
}
```

should normalize cleanly, for example:

```json
{
  "type": "heading",
  "title": "Videos",
  "level": 2
}
```

Maintain compatibility with `text` where required, but establish one canonical v2 representation.

Headings are structural module items and do not point to resources.

## LTI semantics

Existing LTI objects should be close to a one-to-one mapping.

Preserve:

* Title.
* Launch URL.
* Resource link ID.
* Custom parameters.
* Target behavior.
* Learning objectives.
* Existing extra fields.

Discussion is currently a specialized LTI launch and should become:

```json
{
  "type": "lti",
  "subtype": "discussion",
  "title": "Welcome to Django for Everybody",
  "launch": "tsugi/tool/tdiscus/",
  "resource_link_id": "discussion_welcome"
}
```

If quiz, autograder, peer-grade, and other subtypes can be inferred reliably from existing launch paths or fields, centralize that inference in the normalization layer. Do not scatter launch-path tests throughout the renderer.

Avoid overriding an explicit subtype with an inferred subtype.

## URL handling

Do not perform global recursive URL replacement.

URL meaning depends on both the field and object type.

These can represent course-owned content:

```text
{apphome}/assn/example.md
{apphome}/lectures/example.pptx
lesson-01-welcome/video.m4v
lectures/example.pptx
```

Historical absolute course URLs may also represent course-owned content:

```text
https://www.dj4e.com/assn/example.md
http://www.dj4e.com/assn/example.md
```

But other URLs under related domains may be true external resources:

```text
https://samples.dj4e.com/
https://market.dj4e.com/
https://chucklist.dj4e.com/
```

Do not assume that every URL sharing a parent domain is course-owned.

For this phase, avoid aggressive rewriting to `$IMS-CC-FILEBASE$`. We are establishing the resource model, not implementing cartridge packaging. Preserve working URLs unless normalization has an existing, well-tested reason to change them.

## Lessons JSON v2 export

Add a new JSON export option representing the normalized model.

Preserve the existing legacy JSON export.

The user should conceptually have two choices:

```text
Export Lessons JSON
Export Lessons JSON v2
```

Use labels consistent with the existing interface.

The legacy export must continue producing the current format.

The v2 export should emit foundational types plus subtypes and retain all meaningful details.

The v2 output should be:

* Human-readable.
* Stable enough for source control.
* Deterministic where practical.
* Lossless with respect to the normalized Lessons model.
* Free of unnecessary Common Cartridge XML terminology.
* Capable of representing future native HTML pages and files.

Do not implement Common Cartridge XML or ZIP generation as part of this work.

If JSON import already exists and supporting v2 input is small and naturally part of the normalization layer, it is acceptable to make v2 reloadable. However, do not allow this to expand into a major import feature. The main deliverable is the model, compatibility, and v2 export.

## Suggested architecture

Prefer a central normalization boundary:

```text
legacy lessons.json
        ↓
Lessons normalizer
        ↓
canonical Lessons model
        ↓
existing renderer/editor
```

And:

```text
canonical Lessons model
        ↓
Lessons JSON v2 serializer
```

Avoid teaching every rendering method about every historical legacy shape.

Possible responsibilities include:

```text
normalizeLessonDocument()
normalizeLessonItem()
normalizeResourceType()
inferLessonSubtype()
inferContentType()
resolveLessonIcon()
serializeLessonsV2()
```

Use repository naming and PHP style rather than these exact names if appropriate.

Keep normalization idempotent:

```text
normalize(normalize(item)) == normalize(item)
```

A v2 item should pass through normalization without being degraded or reclassified incorrectly.

## Testing requirements

Add focused tests covering at least:

1. Legacy header to heading normalization.
2. External reference to web-link normalization.
3. Legacy assignment to HTML-page/assignment normalization.
4. Local slide to file/slides normalization.
5. External slide to web-link/slides normalization.
6. Video normalization without loss of YouTube, Kaltura, media, notes, or historical IDs.
7. Discussion to LTI/discussion normalization.
8. Existing LTI custom parameters and metadata.
9. Stored file representation using `/files/download/{sha256}`.
10. Separate preservation of SHA-256, filename, title, and content type.
11. Unknown legacy attributes surviving normalization and v2 serialization.
12. Explicit subtype taking precedence over inferred subtype.
13. Explicit icon taking precedence over inferred icon.
14. Normalization idempotence.
15. Existing legacy JSON export remaining unchanged.
16. Deterministic Lessons JSON v2 export.
17. Existing Lessons pages rendering without regressions.

Use the repository’s existing test framework and conventions.

If practical, take a representative real `lessons.json` fixture and verify that all input fields survive normalization and v2 serialization. A field-preservation test is important because this format contains historical metadata that may not be known to the normalizer.

## Implementation phases

Implement this work in small, reviewable phases.

### Phase 1: Repository inspection and design confirmation

Before editing:

* Identify relevant files and classes.
* Explain the current read, render, author, and export paths.
* Identify existing extension points.
* Identify current Files metadata APIs.
* Identify potential compatibility risks.
* State the smallest coherent implementation plan.

If the repository materially contradicts assumptions in this instruction, explain the conflict and adapt conservatively.

### Phase 2: Canonical vocabulary and normalization

* Add foundational types.
* Add subtype support.
* Normalize legacy objects.
* Preserve unknown fields.
* Make normalization idempotent.
* Do not modify stored source JSON.

### Phase 3: Rendering compatibility

* Make current UI rendering work from normalized items.
* Preserve current icons and behavior.
* Add generic rendering fallbacks for foundational `file`, `web_link`, and `html_page` objects.
* Do not build their full authoring interfaces.

### Phase 4: Lessons JSON v2 export

* Add the new export option.
* Emit canonical foundational types.
* Preserve rich Lessons metadata.
* Keep the legacy export unchanged.

### Phase 5: Tests and documentation

* Add the tests listed above as applicable.
* Document the v2 vocabulary and compatibility behavior.
* Document deferred decisions for Pages, Files authoring, and Common Cartridge.

Stop after these phases. Do not proceed into Common Cartridge import/export, Pages authoring, or file upload UI.

## Deferred work

Explicitly leave these for later:

* IMS Common Cartridge import.
* IMS Common Cartridge export.
* XML namespaces and Tsugi cartridge extensions.
* Cartridge ZIP packaging.
* Restoring human filenames during cartridge packaging.
* Full HTML Page authoring.
* Integration of HTML Page authoring with Tsugi Pages.
* Full File authoring and upload.
* Selecting existing Tsugi Files from Lessons.
* Conversion of all legacy media paths into SHA-256 file records.
* A generalized multi-representation media schema.

The new model should make those possible without implementing them now.

## Guardrails

* Do not automatically rewrite existing `lessons.json`.
* Do not remove legacy export.
* Do not discard unknown properties.
* Do not reduce all icons to generic resource icons.
* Do not flatten videos to a single URL.
* Do not confuse a module title with a filename.
* Do not use the displayed filename as the Tsugi storage key.
* Do not infer file identity only by parsing the download URL when authoritative file metadata is available.
* Do not make network requests during ordinary rendering to discover web-link content types.
* Do not globally convert all related-domain URLs into local course resources.
* Do not implement Common Cartridge yet.
* Do not build the HTML Page or File authoring UI yet.
* Do not perform unrelated cleanup.

## Final deliverable

When implementation is complete, report:

* The files changed.
* The canonical v2 item shape.
* The legacy-to-v2 normalization rules implemented.
* How unknown fields are preserved.
* How icon and subtype behavior remain compatible.
* How Tsugi file SHA-256, filename, href, and content type are represented.
* How to invoke the new v2 JSON export.
* Tests run and their results.
* Any deferred questions discovered in Pages or Files.
* Any compatibility risk that remains.

The guiding principle for the entire change is:

> Refactor Lessons around foundational course-resource types without reducing its current semantic richness. Legacy Lessons types become richer subtypes of those foundations, and Lessons JSON v2 is a readable, lossless superset designed to support future Files, Pages, and Common Cartridge work.

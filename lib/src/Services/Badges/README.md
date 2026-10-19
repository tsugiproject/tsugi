# Badges Service

Service for minted badge operations (OB2/OB3). Handles badge minting, lookup, and assertion data. Does not touch legacy `/badges/` endpoints.

## Tables

### badges
Denormalized data for durable badge assertions. Once minted, badge validity does not depend on `lti_user`, `lti_context`, or `lti_result`.

- **Identity**: `(user_id, context_id, badge_code)` uniquely identifies a badge—one minted badge per user per context per badge code.
- **issued_at**: Set when the user clicks Publish. Republish keeps this value and the GUID.
- **accomplishments**: JSON written at publish and refreshed on republish. It holds completion, the criteria narrative, the evidence URL, the image URL, the SHA-256 of that PNG, the issuer profile, and the assignment list (`items`). A published badge URL is served from this row. It does not read lessons or the course.
- **image_id** / **image_sha256**: Join to `badge_images`, and the file hash for a reference check. Many badges share one image row when the hash matches.

### badge_images

One row per distinct image file. `sha256` is unique. `mime_type`, `file_size`, and `image_blob` are the original file, stored whole when it is at most 5 MiB. The bytes stay out of `accomplishments`.

An unpublished badge still reads its assignment list from the current lessons.

## Minting Flow

1. **Badges Awarded** never mints. It uses `getMintedGuidIfExists()` to check; if minted → GUID URL, else → hex URL.
2. User views their badge via hex URL, sees **Publish** button (if owner and unminted).
3. User clicks **Publish** → `mintOrGet()` inserts the row and the accomplishments snapshot, or refreshes that snapshot on an existing row → redirect to GUID URL.
4. Badges Awarded then shows the GUID link.

## Badge Code Changes

**Badges Awarded only lists badges that exist in the current `lessons.json` and that the user has earned.** It iterates over the current badge definitions.

**Scenario: badge code changes** (e.g. `completion` → `completion_v2`)

1. **In Badges Awarded**: The old badge disappears from the list (it's no longer in lessons). If a new badge (e.g. `completion_v2`) replaces it with the same assignments, the user sees the new one as **unminted** (hex URL)—because `getMintedGuidIfExists(user_id, context_id, "completion_v2")` returns null; the minted row has `badge_code = "completion"`.

2. **The minted badge**: The old minted badge stays in the DB. Its GUID URL (`/assertions/m123...`) still works and displays correctly. It just no longer appears in Badges Awarded, since we only iterate over current lessons.

**Net effect**: Minted badges whose codes are renamed or removed become "orphaned"—the GUID URL remains valid and shareable, but the badge disappears from Badges Awarded. The new badge code shows as unminted until published.

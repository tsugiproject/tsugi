# Badges Service

Service for minted badge operations (OB2/OB3). Handles badge minting, lookup, and assertion data. Does not touch legacy `/badges/` endpoints.

## Tables

### badges
Denormalized data for durable badge assertions. Once minted, badge validity does not depend on `lti_user`, `lti_context`, or `lti_result`.

- **Identity**: `(user_id, context_id, badge_code)` uniquely identifies a badge—one minted badge per user per context per badge code.
- **issued_at**: Set when the user clicks Publish; aligns with Open Badges terminology.

### badge_assignments
Maps `badge_code` to required `resource_link_id`s (from lessons.json). Used for the Evidence section on assertion pages.

## Minting Flow

1. **Badges Awarded** never mints. It uses `getMintedGuidIfExists()` to check; if minted → GUID URL, else → hex URL.
2. User views their badge via hex URL, sees **Publish** button (if owner and unminted).
3. User clicks **Publish** → `mintOrGet()` inserts row → redirect to GUID URL.
4. Badges Awarded then shows the GUID link.

## Badge Code Changes

**Badges Awarded only lists badges that exist in the current `lessons.json` and that the user has earned.** It iterates over the current badge definitions.

**Scenario: badge code changes** (e.g. `completion` → `completion_v2`)

1. **In Badges Awarded**: The old badge disappears from the list (it's no longer in lessons). If a new badge (e.g. `completion_v2`) replaces it with the same assignments, the user sees the new one as **unminted** (hex URL)—because `getMintedGuidIfExists(user_id, context_id, "completion_v2")` returns null; the minted row has `badge_code = "completion"`.

2. **The minted badge**: The old minted badge stays in the DB. Its GUID URL (`/assertions/m123...`) still works and displays correctly. It just no longer appears in Badges Awarded, since we only iterate over current lessons.

**Net effect**: Minted badges whose codes are renamed or removed become "orphaned"—the GUID URL remains valid and shareable, but the badge disappears from Badges Awarded. The new badge code shows as unminted until published.

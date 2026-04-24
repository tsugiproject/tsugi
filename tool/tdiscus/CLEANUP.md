# TDISCUS Cleanup Notes

This file tracks follow-up cleanup work after shifting unread/subscription behavior to be subscribe-driven.

Current state:
- Runtime behavior is moving toward `subscribe` as the primary "following" signal.
- Data model still includes `tdiscus_user_thread_participation`, and some code paths still write/read it.
- No schema/data migration has been done yet.

## Goals

- Keep user-facing behavior: creating/posting auto-subscribes, users can unsubscribe anytime.
- Make unread rollups consistently driven by `tdiscus_user_thread.subscribe`.
- Reduce duplicate concepts ("subscribe" vs "participation") unless participation is explicitly retained for analytics only.

## Deferred Cleanup Checklist

### 1) Decide participation table fate

- Option A: Keep `tdiscus_user_thread_participation` for analytics/history only.
- Option B: Fully deprecate/remove it from app logic and schema.
- Document the decision in this file before implementation.

### 2) Remove participation as unread driver

- Ensure no unread badge/rollup SQL depends on `tdiscus_user_thread_participation`.
- Confirm both tool-side and controller-side rollups use `COALESCE(UT.subscribe, 0) = 1` consistently.

### 3) Remove participation writes if deprecated

- If Option B is chosen, remove writes to participation table in:
  - `tool/tdiscus/util/threads.php` (`threadInsert`, `commentInsertDao`, helper calls)
  - `lib/src/Controllers/Discussions.php` reset/repair flows

### 4) Simplify/reset maintenance flows

- In `Discussions` maintenance/audit pages, remove participation-specific checks/messages if no longer relevant.
- Keep owner subscribe restoration logic.

### 5) Settings and terminology alignment

- Review settings labels for "participation" wording.
- Rename/reword to "subscription" semantics where applicable.
- Keep backward compatibility for existing settings keys if needed.

### 6) Data migration plan (if schema change is approved)

- Draft migration steps and rollback plan.
- Sequence:
  1. stop reads/writes in app logic,
  2. deploy and observe,
  3. archive/drop table in a later release.

### 7) Verification checklist

- Thread list and single-thread view show consistent subscribe state.
- Unread badges: red/personal, orange/subscribed activity, green/global.
- Paging links include `start=` and navigate correctly.
- Auto-subscribe triggers:
  - create thread,
  - add top-level comment,
  - add reply/sub-comment,
  - posting after manual unsubscribe should resubscribe.


# Outbound LTI Dynamic Registration — design prompt

Stored 2026-09-30. This is the design brief. It is not the design report.

It follows the organization and outbound-tool briefs in `org-outbound-tools-prompts.md`. The tenant in this brief is written as `tenant_id`. The implementation uses the existing `lti_key` / `key_id`.

---

We want to add the first-pass persistence model for outbound LTI Dynamic Registration in Tsugi.

Please inspect the existing Tsugi database schema, migration style, naming conventions, PHP data-access patterns, and PHPUnit conventions before making changes.

Keep this task deliberately narrow.

The goal is to add a lossless, future-proof persistence model for Dynamic Registration that:

1. stores the current parsed registration state
2. stores the ordered `messages[]` descriptors as first-class rows
3. preserves the complete parsed JSON for registration and each message
4. preserves the raw protocol traffic in an append-only text log for debugging/certification
5. does not prematurely normalize every field inside the Dynamic Registration JSON

Do not implement full Dynamic Registration workflow/UI yet.

Do not implement every LTI message behavior yet.

Do not add many child tables for placements, roles, media types, custom parameters, etc.

We want two real relational tables plus a protocol/debug log.

## Core design

Add:

```text
lti_tool_registration
lti_tool_message
lti_tool_registration_log
```

The first two represent current parsed semantic state.

The log represents the actual protocol exchange.

## lti_tool_registration

This table already may exist from the previous organization/tool-registration work. Inspect the current branch and extend it rather than recreating it.

It should continue to carry the existing ownership/scope fields, such as:

```text
registration_id
tenant_id
org_id NULL
created_by_user_id NULL
title / name fields as appropriate
```

Add whatever scalar Dynamic Registration fields are clearly useful as normal columns, following the spec and existing Tsugi conventions.

Also add a native MySQL JSON column containing the complete successfully parsed registration/tool-configuration JSON.

Conceptually:

```text
registration_json JSON
```

The purpose of this column is:

- preserve the complete parsed Dynamic Registration state
- preserve fields Tsugi does not yet implement
- preserve vendor extensions
- preserve future spec additions
- allow MySQL 8 JSON querying later if needed

Do not use TEXT for this semantic current-state field.

Use the native MySQL `JSON` type.

## lti_tool_message

Create one row for each entry in the Dynamic Registration `messages[]` array.

Conceptually:

```text
lti_tool_message
    message_id
    registration_id        NOT NULL
    sequence               NOT NULL
    message_type           NOT NULL
    target_link_uri        NULL
    label                  NULL
    icon_uri               NULL
    message_json           JSON NOT NULL
```

Adjust exact names/types to existing Tsugi conventions.

### sequence

`sequence` records the exact array position from the original Dynamic Registration `messages[]` array.

If the incoming JSON contains:

```json
"messages": [
    {...},
    {...},
    {...}
]
```

store:

```text
first message   sequence = 0
second message  sequence = 1
third message   sequence = 2
```

This is not UI sort order.

It is protocol/source-document order.

Add:

```text
UNIQUE (registration_id, sequence)
```

Retrieval that depends on registration order should use:

```sql
ORDER BY sequence
```

Do not rely on `message_id` insertion order.

When replacing/reprocessing a registration, it is acceptable for this first implementation to delete and rebuild the message rows from the new registration JSON rather than trying to preserve row identity across registration updates.

### message_type

Store the Dynamic Registration message `type` as a normal searchable string column.

Do not make this an enum.

The protocol is extensible and future/vendor message types must remain representable.

### target_link_uri, label, icon_uri

Store these common message properties as normal nullable scalar columns.

These are expected to be operationally useful and easy to inspect/query.

### message_json

Store the entire successfully parsed message descriptor in a native MySQL `JSON` column.

This JSON should contain the complete original parsed descriptor, including fields such as:

```text
placements
roles
custom_parameters
supported_types
supported_media_types
localized fields
message-specific extensions
vendor-specific extensions
future properties Tsugi does not yet know about
```

Do not create separate relational tables for those fields in this pass.

The expected number of message descriptors per registration is small, so loading all messages for a registration and parsing small JSON objects in PHP is completely acceptable.

If later experience shows that a JSON property is frequently used in SQL `WHERE` clauses or needs indexing, we can promote it to a normal/generated/indexed column later.

## lti_tool_registration_log

Create an append-only log table for the raw Dynamic Registration protocol exchange.

This table is intentionally different from the two semantic tables above.

The payload must be stored as TEXT/LONGTEXT, not JSON.

Conceptually:

```text
lti_tool_registration_log
    log_id
    registration_id NULL
    sequence
    direction
    phase
    content_type NULL
    http_status NULL
    payload_text LONGTEXT
    created_at
```

Adjust exact columns to existing Tsugi conventions.

The important rule is:

> Store the raw payload text before attempting JSON parsing.

This is necessary because malformed JSON is exactly the kind of thing we may need to debug.

A MySQL JSON column would reject malformed JSON and therefore destroy the most important forensic evidence.

The intended workflow is:

```text
raw HTTP body arrives
    |
    +--> immediately append raw text to registration log
    |
    +--> attempt JSON parsing/validation
             |
             +--> if valid, populate/update lti_tool_registration
             +--> if valid, populate lti_tool_message rows
             +--> if invalid, semantic tables are not populated from that payload,
                  but the exact raw payload remains in the log
```

The log should support both directions of the Dynamic Registration conversation.

For example:

```text
inbound
outbound
```

Use whatever representation best matches Tsugi conventions.

Likewise, `phase` should identify the stage of the Dynamic Registration exchange sufficiently for debugging/certification.

Do not over-engineer this into a complete generic HTTP packet capture system.

The main purpose is:

- debugging interoperability problems
- certification evidence
- seeing exactly what the tool sent
- seeing exactly what Tsugi returned
- retaining malformed JSON
- reconstructing the registration exchange later

Preserve payload text as faithfully as practical.

Do not pretty-print or normalize it before storing it.

## JSON strategy

Use native MySQL 8 JSON columns for:

```text
lti_tool_registration.registration_json
lti_tool_message.message_json
```

Use TEXT/LONGTEXT for:

```text
lti_tool_registration_log.payload_text
```

This distinction is intentional:

```text
semantic tables:
    successfully parsed current state
    JSON type

log table:
    raw wire/protocol evidence
    text type
```

## Relational vs JSON philosophy

Do not normalize every Dynamic Registration array/property into its own table.

For this first pass, only properties that are clearly first-class relational/operational concepts should be columns.

Current intended message columns are roughly:

```text
message_id
registration_id
sequence
message_type
target_link_uri
label
icon_uri
message_json
```

Everything else stays in `message_json`.

Likewise, the registration table may have important scalar registration properties plus the complete `registration_json`.

MySQL 8 JSON queries remain available later if we need to query into the JSON directly.

For example, future code may use JSON expressions for fields like placements without changing the underlying persistence model.

Do not add generated columns or JSON indexes yet unless the existing Tsugi code clearly demonstrates a current need.

## Foreign keys and lifecycle

`lti_tool_message.registration_id` should reference `lti_tool_registration`.

Use `ON DELETE CASCADE` so deleting a registration deletes its current message rows.

For the log table, think carefully about lifecycle.

The debugging/certification value of the log may argue against blindly cascading away all log evidence when a registration row is deleted.

Please inspect existing Tsugi logging/audit conventions and propose the most appropriate behavior before choosing a cascade.

It is acceptable for `registration_id` in the log to be nullable so early registration traffic can be logged before a registration row exists.

## Indexes

At minimum consider indexes for:

```text
lti_tool_message.registration_id
lti_tool_message.message_type
lti_tool_registration_log.registration_id
lti_tool_registration_log.created_at
```

and:

```text
UNIQUE (registration_id, sequence)
```

Avoid speculative indexing of JSON properties in this pass.

## Tests

Add focused tests for the persistence behavior.

At minimum verify:

### Message ordering

Given three messages in the incoming array, verify rows are stored with:

```text
sequence = 0
sequence = 1
sequence = 2
```

and retrieve correctly using:

```text
ORDER BY sequence
```

### Complete JSON retention

Verify that:

```text
registration_json
message_json
```

retain fields not mapped to scalar columns.

Include examples such as:

```text
placements
roles
supported_types
supported_media_types
custom_parameters
unknown/vendor property
```

### Message type extensibility

Verify an unknown/nonstandard `message_type` can be stored without schema changes.

### Raw log before parsing

Verify raw payload text can be stored even when it contains malformed JSON.

For example, a payload with invalid syntax should still be preserved in:

```text
payload_text
```

while semantic JSON parsing fails appropriately.

### Log fidelity

Verify the stored raw payload is not reformatted or normalized before persistence.

### Rebuild behavior

If the implementation updates an existing registration by deleting/rebuilding message rows, verify:

- old message rows are removed
- new rows reflect the new array
- sequence values match the new message order

### Cascade behavior

Verify deleting a registration removes semantic message rows.

For log rows, test whatever lifecycle policy is chosen after reviewing Tsugi conventions.

## Important scope boundary

Do not extend this task into:

```text
full Dynamic Registration endpoint flow
browser redirects
OIDC launch implementation
Deep Linking execution
placement UI
role enforcement
message selection engine
AGS
NRPS
OneRoster
billing
vendor-specific compatibility logic
```

This task is about getting the persistence model right.

## Desired end state

After this change, Tsugi should be able to represent:

```text
one outbound LTI tool registration
    |
    +-- complete parsed registration JSON
    |
    +-- ordered message descriptors
    |       |
    |       +-- common scalar fields
    |       +-- complete parsed message JSON
    |
    +-- append-only raw registration protocol log
            |
            +-- inbound and outbound raw payload text
            +-- malformed JSON preserved
            +-- useful for debugging and certification
```

Please first inspect the current branch and propose:

1. exact schema changes
2. migration files
3. foreign keys
4. indexes
5. log lifecycle policy
6. tests

Then implement the smallest clean version consistent with existing Tsugi architecture.

Keep unrelated refactoring out of this change.

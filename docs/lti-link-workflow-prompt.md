# Tsugi LTI link workflow — design prompt

Stored 2026-09-23. This is the design brief. It is not the design report.

---

# Cursor Prompt: Tsugi LTI Link Workflow / Quiz / AGS Data Model Review

We need to design the next stage of Tsugi quiz/workflow support. Do not write code yet. First inspect the existing Tsugi repository, especially the `lti_link` schema/model, result/grade tables, LTI 1.1 outcome support, LTI 1.3 AGS support, quiz tables/models, and any existing code that associates course content with `lti_link`.

The architectural direction is:

`lti_link` should become the canonical Tsugi representation of a course activity/resource occurrence and its workflow/gradebook metadata.

Do NOT introduce a separate `assignment`, `workflow`, or auxiliary table just to hold dates/status. We deliberately want to extend `lti_link`.

A quiz itself is authored assessment content. It should not intrinsically own course-specific workflow such as due dates, publication state, or gradebook identity. A particular use of that quiz in a course should be represented through an `lti_link`.

Conceptually:

```text
quiz
    authored assessment definition/content

lti_link
    occurrence of an activity in a course
    workflow/access state
    grading/line-item metadata
    possible association with an upstream LTI platform line item

result
    state for one user on one lti_link
    score
    activity progress
    grading progress
    submission/grade timestamps, etc.
```

First, verify this model against the current official 1EdTech specifications. Use the current LTI 1.3 / LTI Advantage documents, especially:

- Assignment and Grade Services 2.0
- Deep Linking 2.0
- LTI Core 1.3 where relevant
- Submission Review if relevant

Do not rely on memory or old IMS documentation if newer 1EdTech material is available.

Pay particular attention to the distinction between resource-link metadata, line-item metadata, and per-user result/score state.

## 1. Deep Linking `ltiResourceLink`

Check the supported fields and semantics for:

```text
lineItem.scoreMaximum
lineItem.label
lineItem.resourceId
lineItem.tag
lineItem.gradesReleased

available.startDateTime
available.endDateTime

submission.startDateTime
submission.endDateTime
```

Deep Linking explicitly supports `available` separately from `submission`; preserve that distinction in your analysis. The spec says `available` controls when the activity/link is accessible, while `submission` controls when learner submissions can be made.

Spec reference:
https://standards.1edtech.org/lti/specifications/launch_messages/deep_linking/lti-deep-linking-spec

## 2. AGS LineItem

Determine the complete set of fields we should reasonably model locally on `lti_link`, including at minimum:

```text
label/title
scoreMaximum
resourceId
tag
resourceLinkId association
gradesReleased, if applicable
```

Determine which of these describe the Tsugi activity itself and which represent an upstream LMS/AGS line item.

Tsugi can operate both as an LTI tool consuming an upstream AGS service and, eventually, as an LTI platform exposing a downstream AGS service. Design the schema so that the same `lti_link` abstraction can support both roles cleanly.

## 3. AGS Score / Result

Verify that these are PER USER / PER RESULT and therefore must not be stored as general `lti_link` state:

```text
userId
scoreGiven
scoreMaximum where sent with a score
activityProgress
gradingProgress
timestamp
comment
```

AGS grades are fundamentally associated with a `(lineitem, user)` pair, not simply the link.

Inspect Tsugi's current result tables/models and tell me whether they already contain appropriate equivalents or whether they need extension.

In particular, identify whether Tsugi can represent all standardized AGS `activityProgress` and `gradingProgress` states without lossy conversion.

## 4. Workflow dates

We currently have these columns on `lti_link`:

```text
start_datetime
end_datetime
```

Inspect how they are currently used throughout Tsugi before proposing any semantic changes.

We likely need to represent at least:

```text
open / available start
due
final submission cutoff / close
```

Do not assume that `due` and `end` are the same.

Compare this with common LMS workflows:

```text
Canvas:
Available From
Due
Until

Sakai Assignments:
Open Date
Due Date
Accept Until

Sakai Tests & Quizzes:
Available Date
Due Date
Final Submission Deadline
```

Propose names and exact semantics for the Tsugi columns.

Also determine whether we should preserve all four LTI Deep Linking availability/submission boundaries:

```text
available.start
available.end
submission.start
submission.end
```

rather than forcing them into only three LMS-style dates.

I want the data model capable of representing the LTI specification accurately even if the initial Tsugi UI exposes a simpler Open / Due / Close workflow.

## 5. Publication / visibility

Determine whether `lti_link` currently has a meaningful published/unpublished state.

Published/unpublished is NOT equivalent to availability dates.

We need to support states such as:

```text
published, but opens next week
published and closed
unpublished regardless of dates
```

Determine whether an existing Tsugi field already does this. If not, propose one.

## 6. Upstream AGS

Tsugi already has:

```text
lti13_lineitem
```

on `lti_link`.

Trace exactly what this field currently means and every place it is used.

Determine whether it is:

- the upstream AGS LineItem URL,
- some other identifier,
- or overloaded.

Do not rename or replace it until you understand the existing behavior.

We want Tsugi, when acting as an LTI tool, to be able to associate its local `lti_link` with an upstream platform's line item and send learner scores/progress upstream.

## 7. Downstream AGS

This is important even though Tsugi does not necessarily implement it today.

Design `lti_link` so Tsugi could later act as the LTI platform and expose an AGS 2.0 service downstream to an external tool.

In that role an external LTI tool should eventually be able to:

```text
GET line items
GET one line item
POST/create a line item if permitted
PUT/update a line item
DELETE a line item if permitted

POST scores for users
GET results
```

The downstream AGS implementation should map naturally onto:

```text
lti_link -> line item/activity definition
result   -> per-user score/progress/result
```

Verify from the AGS specification whether that mapping is complete. Identify anything the local schema would be unable to represent.

Do not implement the service yet. We are designing the persistent model so implementing it later does not require another schema redesign.

## 8. Quiz integration

Inspect the current quiz schema and how quizzes appear in lessons/navigation/Common Cartridge import/export.

Propose the cleanest relationship that allows:

```text
quiz = reusable authored content

lti_link = this quiz assigned/placed in this course
```

Ideally the same quiz could theoretically be used by more than one `lti_link`, even if the first UI does not expose that feature.

Do not move course workflow fields into the quiz table.

## 9. Other course resources

This should not become quiz-specific architecture.

Eventually the same `lti_link` workflow model should be usable by:

- quizzes
- lessons/pages
- files
- URLs
- LTI launches
- VisitUrl/completion activities
- other course resources

Some will be gradable, some will not.

A non-gradable resource should not require a fake gradebook column.

## 10. Common Cartridge / QTI / LTI considerations

Inspect the current IMS Common Cartridge import/export implementation and determine how introducing the `lti_link` workflow fields affects round-trip import/export.

Do not conflate QTI quiz content with assignment/workflow metadata.

Explicitly identify which metadata can be represented by:

- QTI
- Common Cartridge organization/resource metadata
- LTI resource links / Deep Linking
- Tsugi-specific extension metadata, if necessary

## 11. Existing schema

Current `lti_link` includes at least:

```text
link_id
link_sha256
link_key
deleted
context_id
path
lti13_lineitem
title
score_maximum
json
settings
settings_url
placementsecret
oldplacementsecret
entity_version
created_at
updated_at
deleted_at
start_datetime
end_datetime
```

Verify against the actual repository/database migration source rather than assuming this screenshot is authoritative.

Do not casually repurpose existing columns. Search their current usages first.

# Deliverable

Do NOT modify code yet.

Produce a design report containing:

1. Current-state summary of `lti_link`, quiz, and result/grade models.
2. Current meanings/usages of `start_datetime`, `end_datetime`, `score_maximum`, and `lti13_lineitem`.
3. A table of relevant fields from Deep Linking and AGS and where each should live in Tsugi.
4. A proposed final `lti_link` schema, clearly marking:
   - existing field unchanged
   - existing field whose semantics should be clarified
   - proposed new field
5. Any required changes to result/grade tables.
6. How upstream AGS maps into the model.
7. How future downstream AGS maps into the same model.
8. How quizzes attach to `lti_link`.
9. Any implications for Common Cartridge/QTI import/export.
10. Open design questions or places where the 1EdTech specs do not map cleanly.

Cite the exact 1EdTech specification sections/URLs used for each standards conclusion.

Stop after the report. Do not create migrations or change application code until I review the proposed model.

# Important design question to think about carefully

Do not prematurely decide that Tsugi's database should only be:

```text
open
due
close
```

Deep Linking can express:

```text
available_start
available_end

submission_start
submission_end
```

and those are not necessarily the same windows.

We may ultimately decide that `lti_link` should store all four plus `due_datetime`:

```text
available_start_datetime
available_end_datetime
submission_start_datetime
submission_end_datetime
due_datetime
```

while the ordinary Tsugi instructor UI presents the friendlier:

```text
Open
Due
Close
```

Think hard about that before settling the schema.

# Decisions locked 2026-09-23

These override earlier speculation in the review. The design report follows them.

## gradesReleased

`grades_released` is a flag on `lti_link`. It is the activity's own release flag. It is not derived from dates, and it is not `lti13_lineitem`.

## Two line-item identities

`lti13_lineitem` is only the upstream line item URL from an incoming LTI launch. It records that this row is associated with a platform above Tsugi. It is not the id Tsugi shows to anyone downstream.

A downstream tool's line item is this link row. The line item URL is built from `link_id`. Outbound line items are one-to-one with resource links because creating a line item creates the link.

If a downstream tool POSTs a line item, Tsugi creates an `lti_link` owned by that tool. One row is still the resource link and the line item. There is no second line-item table.

## Launch link and extra columns

A quiz, and later a discussion, has one launch link. That is the old LTI 1.1 shape: the launch and the basic grade are the same row. On an LTI 1.3 launch Tsugi tells the tool that this link is already there if the tool wants the basic grade.

That one-to-one does not stop the tool from creating more columns. More columns are what AGS is for. A quiz tool, a discussion tool, or an external tool can POST another line item, and Tsugi creates another link row for it. Those rows belong to the tool that created them. Lessons does not point at them. They are not a second launch link for the quiz, and they do not make the quiz one-to-many in the outline.

When a grade on the row changes, and `lti13_lineitem` is set, that grade percolates up to the upstream LMS.

## Opening a quiz

Opening a quiz from lessons is not an LTI launch. Gift quizzes and discussions use a launch, which builds another context and another session. Core Tsugi does not do that for Quiz1.

The course and which quiz this is are on the URL, REST style, under `/courses/42/quizzes/…`. Because the quiz and its launch link are one-to-one, the path may carry the quiz id. The request looks up that quiz's link and, when the link exists, loads `Link` and `Result`. The path may instead carry the link id directly. Either one is enough. That load does not exist today.

If the quiz has no link yet, there is no result to load. Do not put the current quiz or the current link id in a new session field, and do not create a Tsugi session just to enter the quiz and come back.

## Internal grade API

Once that request has loaded them, `Link` is the line item and `Result` is the result. Those objects are the internal endpoints. Do not add a second in-process service in front of them.

The new surface is AGS-inspired methods and constants on those objects. They write `lti_link` and `lti_result`. Examples: set the score numerator and denominator, set `activityProgress`, set `gradingProgress`, set `gradesReleased`. The values passed in use the AGS names already defined on `LTI13` (`scoreGiven`, `scoreMaximum`, `Initialized`, `FullyGraded`, and the rest). `gradeSend` and the JSON helpers stay. A score write still updates the current result row, and if `lti13_lineitem` is set that same score is what goes upstream.

A later HTTP AGS endpoint should call these same `Link` and `Result` methods. `createLineItem` and `sendLineItemResult` remain the outbound client that talks to an LMS above Tsugi.

## Score and result

AGS defines Score and Result as different resources so a platform can refuse an update, alter a grade, or keep history. Tsugi does not. A score write updates the current `lti_result` row. The stored score is the result.

A later history feature can record previous values. Until then there is one current row per `(link_id, user_id)`. Tsugi does not reject a score because a platform would rather argue about it.

`scoring_user_id`, `submission.startedAt`, and `submitted_at` belong on that result row.

## Quiz identity and lessons

AGS `resourceId` is not the Quiz1 primary key.

A quiz and its link are one-to-one, the way a Canvas quiz is one-to-one with its assignment. Publishing creates that one link. Publishing the same quiz again does not create a second column. A second placement is a duplicated quiz and a new link. The quiz keeps that link.

Lessons places the quiz by quiz id. Availability and publish state are not copied onto the lesson item. Lessons walks quiz id to that one link:

- no link yet: never published
- a link with the published flag off: unpublished, and the row is still there
- a link with the published flag on: live, and the open / due / close values are the ones on the link

Unpublish is soft. It deletes nothing. The link, the dates, and the results stay. Publishing again flips the flag back. Lessons can do that a second later and see the new status through the same quiz id.

After the link exists, lessons is still the gate. A lesson item does not store its own open, due, or close. Lessons can show the item as not open yet. Students reach the quiz through lessons. The quiz list is not a second place to discover this week's work, and neither is a separate assignments index. The resource link id is what the gradebook, AGS, and the launch use once lessons has let the student through.

Discussions will use this same shape when they become gradable. One discussion, one link. Lessons keeps the discussion id and walks it to the link to see whether a link exists and what its publish state and dates are. A discussion can be unpublished, or published with a due date, without lessons storing those facts itself.

Deep linking to a quiz is out of scope for this model. Do not add a second launch link for a quiz, and do not add deep-linking columns to force that case. A later design can send a deep link at the quiz's one launch link, creating that link if it does not exist yet. Extra AGS columns stay tool-owned rows beside that launch link. That work stays one launch link per quiz.

## Scores

The stored score is a numerator and a denominator, the AGS pair `scoreGiven` / `scoreMaximum`. One out of three stays 1 and 3. It is not stored as the fraction 0.333.

`lti_link.score_maximum` is the column denominator. The result stores the numerator. The old `lti_result.grade` float, 0 through 1, is a derived fraction for LTI 1.1 and the current percent displays. It is not the source of truth. Upstream passback sends the numerator and the link's maximum.

The link remembers which quiz to render. That pointer is internal. It is not AGS `resourceId`.

## Common Cartridge: one quiz referenced twice

An organization `<item identifierref>` is a place in the outline. It points at one `<resource>`. Content Packaging allows many items to carry the same identifierref. That repeats the pointer. It does not copy the resource, and it does not create a gradebook column. Common Cartridge has no grade column on the item. The assessment resource is the quiz.

So two outline items aimed at the same assessment resource mean one quiz, one eventual link, and one gradebook column, shown in two places in lessons. Both lesson items store that quiz id. The quiz stays unpublished until a teacher publishes it. Students do not see it. Teachers do. Publishing once makes every lesson spot that points at that quiz live, and they share the column.

Two gradebook columns are two assessment resources, even when the QTI looks the same. That is the duplicate-the-quiz case.

A `dependency identifierref` (Canvas `assessment_meta`, an icon, a bundle) is not an outline placement. Item `isVisible` is a display hint, not publish. Canvas `module_meta` repeating a quiz migration id is the same rule: one quiz, one assignment, two module rows.

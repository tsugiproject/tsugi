# Quiz1

Native Tsugi quiz authoring (Phase 0). This is **not** an LTI tool and is not the Gift Quizzes module.

Pipeline:

```
Quiz editor  →  semantic Quiz1 model  →  Qti12Exporter  →  Common Cartridge QTI 1.2.1 XML
```

QTI XML is an interchange format only. It is never stored as the database representation.

## Data model

Tables (created via `admin/upgrade.php` from `lib/src/Services/Quiz1/database.php`):

| Table | Role |
|-------|------|
| `{prefix}quiz1_quiz` | Quiz owned by `lti_context` (`context_id`) |
| `{prefix}quiz1_question` | Ordered questions (`sequence`, `qtype`, prompt, points) |
| `{prefix}quiz1_answer` | Choices or accepted strings (`is_correct`, not GIFT markup) |

Internal types (`qtype`) are semantic:

| Type | Author label | CC `cc_profile` |
|------|----------------|-----------------|
| `multiple_choice` | Multiple choice (one answer) | `cc.multiple_choice.v0p1` |
| `multiple_response` | Multiple choice (several answers) | `cc.multiple_response.v0p1` |
| `true_false` | True / False | `cc.true_false.v0p1` |
| `essay` | Essay | `cc.essay.v0p1` |
| `fill_blank` | Fill in the blank | `cc.fib.v0p1` |
| `pattern_match` | Pattern match | `cc.pattern_match.v0p1` |

True/False is stored as two labeled answers (`True` / `False`). Fill-in-the-blank stores one or more **literal** accepted strings (CC matching is not case-sensitive). Pattern match stores substring patterns (`varsubstring` “contains”); it is not a Tsugi regex language.

Database ids and exported QTI `ident` values are separate. Export derives idents such as `Q1_QUIZ_{id}` / `Q1_ITEM_{id}` / `Q1_ANS_{id}` at serialization time.

Deleting a quiz cascades to questions and answers (InnoDB FKs).

## Code map

| Piece | Location |
|-------|----------|
| Controller / CRUD UI | `lib/src/Controllers/Quiz1.php` (`/quiz1`) |
| Validation + model | `lib/src/Services/Quiz1/Quiz.php`, `Question.php`, `Answer.php` |
| Persistence | `lib/src/Services/Quiz1/QuizRepository.php` |
| Take scoring | `lib/src/Services/Quiz1/Grader.php` |
| QTI 1.2.1 CC exporter | `lib/src/Services/Quiz1/Qti12Exporter.php` |
| Sample / interoperability quiz | `lib/src/Services/Quiz1/SampleQuiz.php` |

The exporter uses `DOMDocument`. The model does not know about XML.

Target profile: **IMS Common Cartridge 1.2 assessment profile of QTI 1.2.1**, not generic QTI 1.2 and not Canvas `question_type` metadata. Version constants live on `Tsugi\Util\CC`. Schema location:

`http://www.imsglobal.org/profile/cc/ccv1p2/ccv1p2_qtiasiv1p2p1_v1p0.xsd`

## Routes

Instructor-only authoring (same `requireInstructor` / CSRF patterns as Pages). Taking a quiz is available to any logged-in user in the course.

- `/quiz1` — instructor author list, or student take list
- `/quiz1/{id}` — **take** the quiz (deep link from Lessons)
- `/quiz1/add` — create
- `/quiz1/{id}/edit` — edit quiz (instructors; also linked from the take page)
- `/quiz1/{id}/questions/add` — add a question
- `/quiz1/{id}/questions/{qid}/edit`
- `/quiz1/{id}/export` — download QTI XML
- `POST /quiz1/sample` — create the six-type sample quiz

## Manual LMS interoperability test

1. Log in as a course instructor.
2. Open `/quiz1` (or `/courses/{id}/quiz1` when course URLs are enabled).
3. **Create sample quiz (all question types)** or author your own covering all six types.
4. **Export QTI** and save the XML.
5. Import that assessment into Canvas, Sakai, or another LMS (standalone QTI or inside a cartridge, depending on the LMS).
6. Inspect question types, order, scoring, HTML, and special characters.

Canvas does not implement `cc.pattern_match.v0p1`. A Generic cartridge will import,
but Canvas converts those items to Fill in the Blank and shows a warning.
Setup **Canvas** export writes those questions as `cc.fib.v0p1` so the warning
does not appear. Matching becomes exact (not substring). Generic and Sakai keep
the pattern-match profile. The stored Quiz1 type is unchanged.

## Not in Phase 0

QTI import, GIFT import/export, Gift Quizzes migration, LTI, question banks, persisted attempts / gradebook.

**Take:** Logged-in users open `/quiz1/{id}` (Lessons uses this URL). Computer-scored questions are graded on submit; essays are not auto-scored. Attempts are not stored yet. Instructors get an **Edit quiz** button on the take page.

**Lessons + cartridge:** A native quiz can be added as a Lessons item
(`type: quiz`, `quiz_id`). Setup Common Cartridge export (`LessonsCartridge`,
not `cc/export.php`) includes QTI for quizzes that appear in lessons.
Setup **Generic** is spec-only CC 1.2: no `course_settings/`, no Canvas
quiz wrapper, no Sakai extras. Leave it alone.

Setup **Sakai** adds `module_meta.xml` / `canvas_export.txt` so weblinks
open in a new tab. Quizzes stay `xml/Q1_*.xml` (no Canvas quiz wrapper).

Setup **Canvas** uses the Canvas course-export quiz layout:
`{id}/assessment_qti.xml`, `{id}/assessment_meta.xml`,
`non_cc_assessments/{id}.xml.qti`, a manifest `<dependency>` on the
meta LOR, and Canvas `question_type` item metadata. Pattern match is
written as fill-in-the-blank.

Legacy `/cc/export` is unchanged and still writes Canvas course_settings.

Later:

```
QTI import  →  Quiz1 model  →  QTI export
GIFT import →  Quiz1 model  →  QTI export  (and later GIFT export)
Persist attempts and send scores to the gradebook
Export quizzes that are not referenced in lessons
```

## Tests

```
cd lib && composer test -- tests/Services/Quiz1 tests/UI/LessonsCartridgeTest.php tests/UI/LessonsNormalizeTest.php tests/Util/CCTest.php
```

(or `qa/test-lib.sh tests/Services/Quiz1` from the repo root)

Isolated interoperability cartridges (not committed):

```
php qa/export-quiz1-fixtures.php
php qa/validate-imscc.php lib/tests/fixtures/Quiz1/generated/00-minimal-qti.imscc
```

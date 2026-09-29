# Panther tests

Browser tests in `qa/tests/` walk a local Tsugi through Chrome. How to run them is in [qa/README.md](../qa/README.md).

Every page a test reads is checked for PHP errors: warnings, notices, deprecations, fatals, uncaught exceptions, stack traces, and a database connection failure. Course settings pages that say "Warning:" in their own copy are not treated as PHP errors.

Instructor course tests log in as the demo persona Instructor 01, grant course creation, and make a new course titled `Panther Demo` plus a timestamp.

## Smoke and store

**`SmokeTest::testHomePageLoads`** opens the site home and expects the welcome text.

**`StoreTest::testStoreLoads`** opens the tool store and expects the Quizzes, Peer-Graded Dropbox, and Threaded Discussion entries.

## Admin

**`AdminTest::testAdminConsoleAccessibleWithPassphrase`** opens `/admin/`, submits the admin passphrase, and expects the Administration Console. It also checks that the unlock did not fail the CSRF check.

**`AdminTest::testAdminScreensHaveNoTracebacks`** unlocks the console, then opens the main admin screens one by one: site config, catalog, keys, expiry, contexts, activity, badges, users, profiles, recent logins, installed modules, keyset, caches, encrypt/decrypt, nonces, database size, mail, events, blob status, blob migration, blob cleanup, remote tools, and PHP info. It does not run upgrade, send mail, or delete data. The installed-modules screen may alert that git refuses the container checkout; that ownership alert is dismissed. Any other browser alert fails the test.

## Course setup

**`DemoCourseTest::testInstructorDemoLoginCreatesCourse`** creates a course, turns Assignments and Discussions on in the left navigation, creates and publishes a page, places that page in a lesson, and opens it from Lessons.

## Course controllers

**`CourseControllersTest::testInstructorUsesCourseControllers`** uploads a file and checks the folder count, posts an announcement, adds a discussion, and opens the grade book, assignments, and calendar. On course delete it types the confirmation fields but does not submit. It also creates the sample quiz, publishes it, and opens view and print.

**`CourseControllersTest::testInstructorLinksPublishedQuizFromLessons`** publishes the sample quiz, adds it to a lesson, and submits the quiz from Lessons with no answers. The result shows a score that is still pending manual grading.

**`DiscussionEquivalenceTest::testThreadsMatchBetweenLessonsLaunchAndController`** adds a discussion to a lesson. Docker sets `discussion_lti_launch`, so the lessons click LTI-launches `tool/tdiscus`. A thread created there shows up in the discussions controller, a thread created in the controller shows up on the next lessons launch, and both titles are on both pages.

**`CourseControllersTest::testInstructorSetsDueDateAndOpensMoreTools`** uploads a file and replaces it, then opens badges, notifications, class grades, student progress, export, and course images. It adds an LTI lesson item, saves a due date, and checks that the item appears on the calendar for that day.

**`CourseControllersTest::testInstructorOpensHomeCatalogAnalyticsAndMarksAnnouncementRead`** opens the course home, the catalog, analytics for files, the grade book, and announcements, plus the import form and the map. It posts an announcement and marks it read, then checks that the previously seen count is 1.

**`CourseControllersTest::testInstructorConfirmsHtmlFileAndScoresQuiz`** uploads an HTML file. A wrong confirmation phrase is rejected, and typing "I am sure" serves the file. It then answers the sample quiz (including the essay) and expects a score of 6 out of 11, still pending manual grading. The grade book shows 54.5 for that quiz.

**`CourseControllersTest::testInstructorManagesFolderAnnouncementAndPageHistory`** creates a folder, uploads a file into it, walks back to the parent, deletes the file, and deletes the empty folder. It edits an announcement title. It creates a page, edits it, restores the earlier version from history, and checks that the original sentence is back.

**`CourseControllersTest::testStudentJoinsCatalogCourseAndSeesSharedFiles`** puts files in Student, Public, Private, and the course root, posts an announcement, and lists the course in the catalog. Student 01 joins from the catalog. Files shows only the Student file, and a direct Private link is not found. The announcement is visible without a manage control. The grade book is the student's own, and course settings refuse the student.

## Store tools

These launch from the store without a site login. Try It picks an identity and the tool runs in the frame.

**`ToolLaunchTest::testGiftToolLaunches`** opens Gift as an instructor and expects either an unconfigured quiz or the submit screen.

**`ToolLaunchTest::testPeerGradeToolLaunches`** opens Peer Grade as a learner, configures a title as the instructor, then opens it again as the learner and expects the upload screen.

**`ToolLaunchTest::testTdiscusToolLaunches`** opens Threaded Discussion as an instructor and expects Add Thread.

**`ToolHappyPathTest::testGiftHappyPathConfigureAndRenderQuiz`** opens Gift's configure screen, saves a one-question gift (`2 + 2`), and expects "Quiz updated" and the submit screen.

**`ToolHappyPathTest::testPeerGradeHappyPathConfigureAssignment`** opens Peer Grade's configure screen, saves an assignment title, and expects the upload screen.

**`ToolHappyPathTest::testTdiscusHappyPathCreateThread`** opens the new-thread form, posts a title and body, and expects that thread title on the discussion.

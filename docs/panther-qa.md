# Panther tests

Browser tests in `qa/tests/` walk a local Tsugi through Chrome. How to run them is in [qa/README.md](../qa/README.md).

Every page a test reads is checked for PHP errors: warnings, notices, deprecations, fatals, uncaught exceptions, stack traces, and a database connection failure. Course settings pages that say "Warning:" in their own copy are not treated as PHP errors.

Instructor course tests log in as the demo persona Instructor 01, grant course creation, and make a new course titled `Panther Demo` plus a timestamp.

## Smoke and store

**`SmokeTest::testHomePageLoads`** opens the site home and expects the welcome text.

**`StoreTest::testStoreLoads`** opens the tool store and expects the Quizzes, Peer-Graded Dropbox, and Threaded Discussion entries.

## Admin

**`AdminTest::testAdminConsoleAccessibleWithPassphrase`** opens `/admin/`, submits the admin passphrase, and expects the Administration Console. It also checks that the unlock did not fail the CSRF check.

**`AdminTest::testAdminScreensHaveNoTracebacks`** unlocks the console, then opens the main admin screens one by one: site config, catalog, keys, expiry, contexts, activity, badges, users, profiles, recent logins, keyset, caches, encrypt/decrypt, nonces, database size, mail, events, blob status, blob migration, blob cleanup, remote tools, and PHP info. It does not open installed modules, run upgrade, send mail, or delete data.

## Course setup

**`DemoCourseTest::testInstructorDemoLoginCreatesCourse`** creates a course, turns Assignments and Discussions on in the left navigation, creates and publishes a page, places that page in a lesson, and opens it from Lessons.

## Course tools

**`CourseToolLaunchDebugTest::testPrivacyCheckboxesAppearInTheLaunchDebugToggle`** adds four LTI 1.1 tools on a course: names only, email only, both, and neither. Each tool's Test page opens the launch debug toggle. The parameter list includes a name or an email only when that privacy box was checked. The test does not submit the launch.

**`CourseToolLoopbackTest::testSameServerDynamicRegistrationShowsPrivacyAndDeepLink`** creates a draft tenant key, sets a one-time unlock code, and registers that key into the Panther course through the course tools dynamic registration URL. The same Docker Tsugi is the platform and the tool. The installed tool view lists a deep link and a privacy launch. The test page shows those two launches. It does not click Send.

## Course controllers

**`CourseControllersTest::testInstructorUsesCourseControllers`** uploads a file and checks the folder count, posts an announcement, adds a discussion, and opens the grade book, assignments, and calendar. On course delete it types the confirmation fields but does not submit. It also creates the sample quiz, publishes it, and opens view and print.

**`CourseControllersTest::testInstructorLinksPublishedQuizFromLessons`** publishes the sample quiz, adds it to a lesson, and submits the quiz from Lessons with no answers. The result shows a score that is still pending manual grading.

**`DiscussionEquivalenceTest::testThreadsMatchBetweenLessonsLaunchAndController`** adds a discussion to a lesson. Docker sets `discussion_lti_launch`, so the lessons click LTI-launches `tool/tdiscus`. A thread created there shows up in the discussions controller, a thread created in the controller shows up on the next lessons launch, and both titles are on both pages.

**`CourseControllersTest::testInstructorSetsDueDateAndOpensMoreTools`** uploads a file and replaces it, then opens badges, notifications, class grades, student progress, export, and course images. It adds a course LTI tool, places that tool in a lesson, saves a due date, and checks that the item appears on the calendar for that day.

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

## API

These hit `/api/` with HTTP and do not open Chrome. They run in the same Docker stack as the browser tests. To walk the same endpoints yourself in a browser, use [api-browser.md](api-browser.md).

**`ApiGuardTest`** calls each endpoint with no session: missing login, missing LTI session, bad content type, bad sourcedid, unknown path, and RPC tokens that do not open a session.

**`ApiCookieTest`** logs in as Instructor 01 through `/login/simulate` and checks notifications plus the analytics cookie errors for a missing or unknown link.

**`ApiLaunchTest`** POSTs the store Try It form for Gift, keeps the LTI session id, and calls the launch-scoped endpoints. It posts a socket message and reads it back from room 0, leaving room 1 without that message. It saves a link setting and reads `qa_api_marker` from `lti_link`. It also unlocks admin with that instructor cookie and reads analytics for the launched link. Grade submit and record-attempt stop at the missing session budget. Annotate and stickygrader are refused: a `tool/gift` session may call `/api/*.php`, and those two live in subdirectories. RPC with that launch session and no `object` stops at `Missing object` and is not asked to call a method.

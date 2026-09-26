<?php

use Tsugi\Core\Context;
use Tsugi\Core\LTIX;
use Tsugi\Core\Link;
use Tsugi\Core\Membership;
use Tsugi\Core\ReqScope;
use Tsugi\Core\ReqScopeException;
use Tsugi\Core\Result;
use Tsugi\Core\User;
use Tsugi\Services\Grades\Gradebook;
use Tsugi\Services\Quiz1\Quiz1;
use Tsugi\Services\Quiz1\Quiz1Repository;
use Tsugi\Util\U;

class ReqScopeTest extends \PHPUnit\Framework\TestCase
{
    private $sessionBefore;

    protected function setUp(): void
    {
        $this->sessionBefore = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array();
        ReqScope::reset();
    }

    protected function tearDown(): void
    {
        ReqScope::reset();
        $_SESSION = $this->sessionBefore;
    }

    public function testCurrentIsNullUntilHydrated() {
        $this->assertNull(ReqScope::current());
    }

    public function testValuesLastForTheRequest() {
        ReqScope::set('color', 'blue');
        ReqScope::set('empty', null);
        $this->assertSame('blue', ReqScope::get('color'));
        $this->assertNull(ReqScope::get('empty', 'default'));
        $this->assertSame('default', ReqScope::get('missing', 'default'));

        $user = new User();
        $user->id = 7;
        $context = new Context();
        $context->id = 9;
        ReqScope::hydrate($user, $context, null, null, null, null, false);

        $this->assertSame('blue', ReqScope::get('color'));
        $this->assertSame('blue', ReqScope::current()->values['color']);
        ReqScope::set('color', 'red');
        $this->assertSame('red', ReqScope::current()->values['color']);

        ReqScope::reset();
        $this->assertSame('default', ReqScope::get('color', 'default'));
    }

    public function testProvisionRecordsAMissingUserAndCourse() {
        $rc = ReqScope::provision(0, 0, null, ReqScope::ORIGIN_SITE);
        $this->assertSame($rc, ReqScope::current());
        $this->assertNull($rc->user);
        $this->assertNull($rc->context);
        $this->assertNull($rc->link);
        $this->assertSame(ReqScope::ORIGIN_SITE, $rc->origin);
    }

    public function testReplaceCourseKeepsTheCourseSessionStartAlreadySet() {
        $user = new User();
        $user->id = 7;
        $user->instructor = true;
        $context = new Context();
        $context->id = 9;
        $link = new Link();
        $link->id = 4;
        ReqScope::provision(0, 0, null, ReqScope::ORIGIN_LTI);
        ReqScope::hydrate($user, $context, $link, null, null, null, false);
        ReqScope::current()->origin = ReqScope::ORIGIN_LTI;

        $rc = ReqScope::replaceCourse(9);

        $this->assertSame(7, $rc->user->id);
        $this->assertSame(9, $rc->context->id);
        $this->assertSame(4, $rc->link->id);
        $this->assertTrue($rc->user->instructor);
        $this->assertSame(ReqScope::ORIGIN_LTI, $rc->origin);
    }

    public function testHydrateInstallsTheSameObjectsOnGlobals() {
        $user = new User();
        $user->id = 7;
        $user->displayname = 'Ada Lovelace';
        $user->instructor = false;
        $context = new Context();
        $context->id = 3;
        $context->title = 'Analytical Engines';
        $link = new Link();
        $link->id = 11;
        $link->title = 'Quiz link';
        $result = new Result();
        $result->id = 19;
        $membership = new Membership();
        $membership->id = 4;
        $membership->role = LTIX::ROLE_LEARNER;

        $rc = ReqScope::hydrate($user, $context, $link, $result, $membership, 1);

        $this->assertSame($rc, ReqScope::current());
        global $USER, $CONTEXT, $LINK, $RESULT;
        $this->assertSame($user, $rc->user);
        $this->assertSame($user, $USER);
        $this->assertSame($context, $CONTEXT);
        $this->assertSame($link, $LINK);
        $this->assertSame($result, $RESULT);
        $this->assertSame(1, $rc->published);
        $this->assertSame($this->sessionBefore, $_SESSION);
    }

    public function testHydrateAllowsNullLinkAndResult() {
        $user = new User();
        $user->id = 1;
        $context = new Context();
        $context->id = 2;

        $rc = ReqScope::hydrate($user, $context, null, null, null, null);

        global $LINK, $RESULT;
        $this->assertNull($rc->link);
        $this->assertNull($rc->result);
        $this->assertNull($rc->published);
        $this->assertNull($LINK);
        $this->assertNull($RESULT);
    }

    public function testSecondHydrateReplacesCurrent() {
        $firstUser = new User();
        $firstUser->id = 1;
        $secondUser = new User();
        $secondUser->id = 2;
        $context = new Context();
        $context->id = 9;

        ReqScope::hydrate($firstUser, $context);
        $rc = ReqScope::hydrate($secondUser, $context);

        global $USER;
        $this->assertSame(2, ReqScope::current()->user->id);
        $this->assertSame($secondUser, $USER);
        $this->assertSame($rc, ReqScope::current());
    }

    public function testResetClearsCurrentAndGlobals() {
        $user = new User();
        $user->id = 1;
        $context = new Context();
        $context->id = 1;
        ReqScope::hydrate($user, $context);
        ReqScope::reset();

        global $USER, $CONTEXT;
        $this->assertNull(ReqScope::current());
        $this->assertNull($USER);
        $this->assertNull($CONTEXT);
    }

    public function testNoteContextIgnoresALaterDifferentId() {
        ReqScope::noteContext(9);
        ReqScope::noteContext(4);
        $user = new User();
        $user->id = 1;
        $context = new Context();
        $context->id = 9;
        ReqScope::hydrate($user, $context);
        $this->assertSame(9, ReqScope::current()->context->id);
    }

    public function testNoteContextLeavesAnExistingCourse() {
        $user = new User();
        $user->id = 1;
        $context = new Context();
        $context->id = 9;
        ReqScope::hydrate($user, $context);
        ReqScope::noteContext(4);
        $this->assertSame(9, ReqScope::current()->context->id);
    }

    public function testReturnUrlIsAttachedOnHydrate() {
        ReqScope::setReturnUrl('https://example.test/lessons/intro');
        $user = new User();
        $user->id = 1;
        $context = new Context();
        $context->id = 3;
        $rc = ReqScope::hydrate($user, $context);
        $this->assertSame('https://example.test/lessons/intro', $rc->launchPresentation->return_url);
    }

    public function testNotedContextWinsOverALaterId() {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for ReqScope fixtures.');
        }
        try {
            $ids = $this->insertCourseFixture($pdo, LTIX::ROLE_LEARNER);
            ReqScope::noteContext($ids['context_id']);
            ReqScope::setReturnUrl('https://example.test/lessons/week1');
            $rc = ReqScope::fromInternalActivity($ids['user_id'], 999999, null);
            $this->assertSame($ids['context_id'], $rc->context->id);
            $this->assertSame('https://example.test/lessons/week1', $rc->launchPresentation->return_url);
        } finally {
            $pdo->rollBack();
        }
    }

    public function testLinkKeyIsStable() {
        $this->assertSame('quiz1:42', Quiz1Repository::linkKey(42));
        $this->assertSame('quiz1:42', Quiz1Repository::linkKey('42'));
    }

    public function testFromInternalActivityLoadsMemberAndReusesLinkAndResult() {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for ReqScope fixtures.');
        }

        try {
            $ids = $this->insertCourseFixture($pdo, LTIX::ROLE_LEARNER);
            $quiz = new Quiz1();
            $quiz->context_id = $ids['context_id'];
            $quiz->user_id = $ids['user_id'];
            $quiz->title = 'Request context quiz';
            $quiz_id = Quiz1Repository::insertQuiz($quiz);

            $session = $_SESSION;
            $rc = ReqScope::fromInternalActivity($ids['user_id'], $ids['context_id'], null);
            $this->assertNull($rc->link);
            $this->assertNull($rc->result);
            $this->assertNull($rc->published);
            $this->assertFalse($rc->user->instructor);
            $this->assertFalse($rc->user->admin);
            $this->assertSame($session, $_SESSION);

            $link_id = Quiz1Repository::publish($quiz_id, $ids['context_id']);
            $again = Quiz1Repository::publish($quiz_id, $ids['context_id']);
            $this->assertSame($link_id, $again);

            $loaded = Quiz1Repository::load($quiz_id, $ids['context_id']);
            $this->assertSame($link_id, $loaded->link_id);
            $this->assertSame(1, $loaded->published);

            $rc = ReqScope::fromInternalActivity($ids['user_id'], $ids['context_id'], $link_id);
            $this->assertSame($link_id, $rc->link->id);
            $this->assertNotNull($rc->result);
            $this->assertSame(1, $rc->published);
            $result_id = $rc->result->id;
            global $USER, $LINK, $RESULT;
            $this->assertSame($rc->user, $USER);
            $this->assertSame($rc->link, $LINK);
            $this->assertSame($rc->result, $RESULT);

            $rc2 = ReqScope::fromInternalActivity($ids['user_id'], $ids['context_id'], $link_id);
            $this->assertSame($result_id, $rc2->result->id);

            $this->assertTrue(Quiz1Repository::unpublish($quiz_id, $ids['context_id']));
            $loaded = Quiz1Repository::load($quiz_id, $ids['context_id']);
            $this->assertSame($link_id, $loaded->link_id);
            $this->assertSame(0, $loaded->published);

            $rc = ReqScope::fromInternalActivity($ids['user_id'], $ids['context_id'], $loaded->link_id);
            $this->assertSame(0, $rc->published);
            $this->assertSame($link_id, $rc->link->id);
            $this->assertSame($result_id, $rc->result->id);

            $republish = Quiz1Repository::publish($quiz_id, $ids['context_id']);
            $this->assertSame($link_id, $republish);

            $row = $pdo->rowDie(
                "SELECT link_key FROM {$this->prefix()}lti_link WHERE link_id = :LID",
                array(':LID' => $link_id)
            );
            $this->assertSame(Quiz1Repository::linkKey($quiz_id), $row['link_key']);
            $this->assertSame($session, $_SESSION);
        } finally {
            $pdo->rollBack();
        }
    }

    public function testGradebookStoresFractionOnTheReqScopeResult() {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for ReqScope fixtures.');
        }
        try {
            $ids = $this->insertCourseFixture($pdo, LTIX::ROLE_LEARNER);
            $quiz = new Quiz1();
            $quiz->context_id = $ids['context_id'];
            $quiz->user_id = $ids['user_id'];
            $quiz->title = 'Graded quiz';
            $quiz_id = Quiz1Repository::insertQuiz($quiz);
            $link_id = Quiz1Repository::publish($quiz_id, $ids['context_id']);
            $rc = ReqScope::fromInternalActivity($ids['user_id'], $ids['context_id'], $link_id);

            $stored = Gradebook::record($rc, 0.5);
            $this->assertEqualsWithDelta(0.5, $stored, 0.0001);

            $row = $pdo->rowDie(
                "SELECT grade FROM {$this->prefix()}lti_result WHERE result_id = :RID",
                array(':RID' => $rc->result->id)
            );
            $this->assertEqualsWithDelta(0.5, (float) $row['grade'], 0.0001);

            $again = ReqScope::fromInternalActivity($ids['user_id'], $ids['context_id'], $link_id);
            $this->assertEqualsWithDelta(0.5, (float) $again->result->grade, 0.0001);
        } finally {
            $pdo->rollBack();
        }
    }

    public function testInstructorAndAdminFlagsComeFromMembership() {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for ReqScope fixtures.');
        }
        try {
            $instructor = $this->insertCourseFixture($pdo, LTIX::ROLE_INSTRUCTOR);
            $rc = ReqScope::fromInternalActivity($instructor['user_id'], $instructor['context_id'], null);
            $this->assertTrue($rc->user->instructor);
            $this->assertFalse($rc->user->admin);
            $this->assertSame(LTIX::ROLE_INSTRUCTOR, $rc->membership->role);

            $admin = $this->insertCourseFixture($pdo, LTIX::ROLE_ADMINISTRATOR);
            $rc = ReqScope::fromInternalActivity($admin['user_id'], $admin['context_id'], null);
            $this->assertTrue($rc->user->instructor);
            $this->assertTrue($rc->user->admin);
        } finally {
            $pdo->rollBack();
        }
    }

    public function testNonMemberIsRejected() {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for ReqScope fixtures.');
        }
        try {
            $ids = $this->insertCourseFixture($pdo, LTIX::ROLE_LEARNER);
            $outsider = $this->insertUser($pdo, $ids['key_id'], 'outsider');
            $this->expectException(ReqScopeException::class);
            try {
                ReqScope::fromInternalActivity($outsider, $ids['context_id'], null);
            } catch ( ReqScopeException $ex ) {
                $this->assertSame(403, $ex->httpStatus);
                throw $ex;
            }
        } finally {
            $pdo->rollBack();
        }
    }

    public function testSetLinkKeepsTheEstablishedUser() {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for ReqScope fixtures.');
        }
        try {
            $ids = $this->insertCourseFixture($pdo, LTIX::ROLE_LEARNER);
            $rc = ReqScope::establish($ids['user_id'], $ids['context_id']);
            global $USER, $LINK;
            $this->assertNotSame($rc->user, $USER);
            $this->assertNull($rc->link);
            $_SESSION['id'] = $ids['user_id'];
            $_SESSION['context_id'] = $ids['context_id'];
            \Tsugi\Core\ReqScope::resetIdentity();
            ReqScope::logSessionDrift($ids['context_id']);
            ReqScope::logSessionDrift(999999);
            $this->assertSame($ids['context_id'], ReqScope::current()->context->id);
            $this->assertSame($ids['user_id'], ReqScope::current()->user->id);

            $quiz = new Quiz1();
            $quiz->context_id = $ids['context_id'];
            $quiz->user_id = $ids['user_id'];
            $quiz->title = 'Linked later';
            $quiz_id = Quiz1Repository::insertQuiz($quiz);
            $link_id = Quiz1Repository::publish($quiz_id, $ids['context_id']);

            $linked = ReqScope::setLink($link_id);
            $this->assertSame($ids['user_id'], $linked->user->id);
            $this->assertSame($link_id, $linked->link->id);
            $this->assertNotNull($linked->result);
            $this->assertNotSame($linked->link, $LINK);

            $again = ReqScope::establish($ids['user_id'], 999999);
            $this->assertSame($ids['context_id'], $again->context->id);
            $this->assertSame($link_id, $again->link->id);
        } finally {
            $pdo->rollBack();
        }
    }

    public function testSiteAdminAndCourseOwnerAreInstructors() {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for ReqScope fixtures.');
        }
        try {
            $ids = $this->insertCourseFixture($pdo, LTIX::ROLE_LEARNER);
            $learner = ReqScope::fromInternalActivity($ids['user_id'], $ids['context_id'], null);
            $this->assertFalse($learner->user->instructor);

            $_SESSION['admin'] = 'yes';
            $admin = ReqScope::fromInternalActivity($ids['user_id'], $ids['context_id'], null);
            $this->assertTrue($admin->user->instructor);
            $this->assertTrue($admin->user->admin);
            unset($_SESSION['admin']);

            $pdo->queryDie(
                "UPDATE {$this->prefix()}lti_context SET user_id = :UID WHERE context_id = :CID",
                array(':UID' => $ids['user_id'], ':CID' => $ids['context_id'])
            );
            $owner = ReqScope::fromInternalActivity($ids['user_id'], $ids['context_id'], null);
            $this->assertTrue($owner->user->instructor);
            $this->assertFalse($owner->user->admin);
        } finally {
            $pdo->rollBack();
        }
    }

    public function testLinkFromAnotherContextIsRejected() {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for ReqScope fixtures.');
        }
        try {
            $a = $this->insertCourseFixture($pdo, LTIX::ROLE_LEARNER);
            $b = $this->insertCourseFixture($pdo, LTIX::ROLE_LEARNER);
            $quiz = new Quiz1();
            $quiz->context_id = $b['context_id'];
            $quiz->user_id = $b['user_id'];
            $quiz->title = 'Other course';
            $quiz_id = Quiz1Repository::insertQuiz($quiz);
            $link_id = Quiz1Repository::publish($quiz_id, $b['context_id']);

            $this->expectException(ReqScopeException::class);
            try {
                ReqScope::fromInternalActivity($a['user_id'], $a['context_id'], $link_id);
            } catch ( ReqScopeException $ex ) {
                $this->assertSame(404, $ex->httpStatus);
                throw $ex;
            }
        } finally {
            $pdo->rollBack();
        }
    }

    public function testUnpublishWithoutALinkReturnsFalse() {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for ReqScope fixtures.');
        }
        try {
            $ids = $this->insertCourseFixture($pdo, LTIX::ROLE_INSTRUCTOR);
            $quiz = new Quiz1();
            $quiz->context_id = $ids['context_id'];
            $quiz->user_id = $ids['user_id'];
            $quiz->title = 'Draft';
            $quiz_id = Quiz1Repository::insertQuiz($quiz);
            $this->assertFalse(Quiz1Repository::unpublish($quiz_id, $ids['context_id']));
            $this->assertNull(Quiz1Repository::publish(99999999, $ids['context_id']));
        } finally {
            $pdo->rollBack();
        }
    }

    /**
     * @return \Tsugi\Util\PDOX|null
     */
    private function beginFixtureTransaction() {
        global $CFG, $PDOX;
        $config = dirname(__DIR__, 3) . '/config.php';
        if ( ! is_file($config) ) {
            return null;
        }
        if ( ! isset($CFG) || ! is_object($CFG) ) {
            require_once $config;
        }
        try {
            $PDOX = LTIX::getConnection();
        } catch ( \Throwable $ex ) {
            return null;
        }
        if ( ! is_object($PDOX) ) {
            return null;
        }
        try {
            $PDOX->rowDie("SELECT quiz_id FROM {$CFG->dbprefix}quiz1_quiz LIMIT 1");
        } catch ( \Throwable $ex ) {
            return null;
        }
        $PDOX->beginTransaction();
        return $PDOX;
    }

    private function prefix() {
        global $CFG;
        return $CFG->dbprefix;
    }

    /**
     * @return array{key_id:int,user_id:int,context_id:int}
     */
    private function insertCourseFixture($pdo, $role) {
        $p = $this->prefix();
        $stamp = 'rc' . bin2hex(random_bytes(6));
        $pdo->queryDie(
            "INSERT INTO {$p}lti_key (key_key, key_sha256, created_at, updated_at)
             VALUES (:K, :SHA, NOW(), NOW())",
            array(':K' => $stamp, ':SHA' => U::lti_sha256($stamp))
        );
        $key_id = (int) $pdo->lastInsertId();
        $user_id = $this->insertUser($pdo, $key_id, $stamp . '-user');
        $context_key = $stamp . '-context';
        $pdo->queryDie(
            "INSERT INTO {$p}lti_context
                (context_key, context_sha256, title, key_id, created_at, updated_at)
             VALUES (:CK, :SHA, :TITLE, :KID, NOW(), NOW())",
            array(
                ':CK' => $context_key,
                ':SHA' => U::lti_sha256($context_key),
                ':TITLE' => 'Request context course',
                ':KID' => $key_id,
            )
        );
        $context_id = (int) $pdo->lastInsertId();
        $pdo->queryDie(
            "INSERT INTO {$p}lti_membership
                (context_id, user_id, role, created_at, updated_at)
             VALUES (:CID, :UID, :ROLE, NOW(), NOW())",
            array(':CID' => $context_id, ':UID' => $user_id, ':ROLE' => $role)
        );
        return array(
            'key_id' => $key_id,
            'user_id' => $user_id,
            'context_id' => $context_id,
        );
    }

    private function insertUser($pdo, $key_id, $user_key) {
        $p = $this->prefix();
        $pdo->queryDie(
            "INSERT INTO {$p}lti_user
                (user_key, user_sha256, displayname, email, key_id, created_at, updated_at)
             VALUES (:UK, :SHA, :NAME, :EMAIL, :KID, NOW(), NOW())",
            array(
                ':UK' => $user_key,
                ':SHA' => U::lti_sha256($user_key),
                ':NAME' => 'Pat Student',
                ':EMAIL' => $user_key . '@example.test',
                ':KID' => $key_id,
            )
        );
        return (int) $pdo->lastInsertId();
    }
}

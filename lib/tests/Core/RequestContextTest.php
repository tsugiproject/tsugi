<?php

use Tsugi\Core\Context;
use Tsugi\Core\LTIX;
use Tsugi\Core\Link;
use Tsugi\Core\Membership;
use Tsugi\Core\RequestContext;
use Tsugi\Core\RequestContextException;
use Tsugi\Core\Result;
use Tsugi\Core\User;
use Tsugi\Services\Grades\Gradebook;
use Tsugi\Services\Quiz1\Quiz1;
use Tsugi\Services\Quiz1\Quiz1Repository;
use Tsugi\Util\U;

class RequestContextTest extends \PHPUnit\Framework\TestCase
{
    private $sessionBefore;

    protected function setUp(): void
    {
        $this->sessionBefore = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array();
        RequestContext::reset();
    }

    protected function tearDown(): void
    {
        RequestContext::reset();
        $_SESSION = $this->sessionBefore;
    }

    public function testCurrentIsNullUntilHydrated() {
        $this->assertNull(RequestContext::current());
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

        $rc = RequestContext::hydrate($user, $context, $link, $result, $membership, 1);

        $this->assertSame($rc, RequestContext::current());
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

        $rc = RequestContext::hydrate($user, $context, null, null, null, null);

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

        RequestContext::hydrate($firstUser, $context);
        $rc = RequestContext::hydrate($secondUser, $context);

        global $USER;
        $this->assertSame(2, RequestContext::current()->user->id);
        $this->assertSame($secondUser, $USER);
        $this->assertSame($rc, RequestContext::current());
    }

    public function testResetClearsCurrentAndGlobals() {
        $user = new User();
        $user->id = 1;
        $context = new Context();
        $context->id = 1;
        RequestContext::hydrate($user, $context);
        RequestContext::reset();

        global $USER, $CONTEXT;
        $this->assertNull(RequestContext::current());
        $this->assertNull($USER);
        $this->assertNull($CONTEXT);
    }

    public function testLinkKeyIsStable() {
        $this->assertSame('quiz1:42', Quiz1Repository::linkKey(42));
        $this->assertSame('quiz1:42', Quiz1Repository::linkKey('42'));
    }

    public function testFromInternalActivityLoadsMemberAndReusesLinkAndResult() {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for RequestContext fixtures.');
        }

        try {
            $ids = $this->insertCourseFixture($pdo, LTIX::ROLE_LEARNER);
            $quiz = new Quiz1();
            $quiz->context_id = $ids['context_id'];
            $quiz->user_id = $ids['user_id'];
            $quiz->title = 'Request context quiz';
            $quiz_id = Quiz1Repository::insertQuiz($quiz);

            $session = $_SESSION;
            $rc = RequestContext::fromInternalActivity($ids['user_id'], $ids['context_id'], null);
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

            $rc = RequestContext::fromInternalActivity($ids['user_id'], $ids['context_id'], $link_id);
            $this->assertSame($link_id, $rc->link->id);
            $this->assertNotNull($rc->result);
            $this->assertSame(1, $rc->published);
            $result_id = $rc->result->id;
            global $USER, $LINK, $RESULT;
            $this->assertSame($rc->user, $USER);
            $this->assertSame($rc->link, $LINK);
            $this->assertSame($rc->result, $RESULT);

            $rc2 = RequestContext::fromInternalActivity($ids['user_id'], $ids['context_id'], $link_id);
            $this->assertSame($result_id, $rc2->result->id);

            $this->assertTrue(Quiz1Repository::unpublish($quiz_id, $ids['context_id']));
            $loaded = Quiz1Repository::load($quiz_id, $ids['context_id']);
            $this->assertSame($link_id, $loaded->link_id);
            $this->assertSame(0, $loaded->published);

            $rc = RequestContext::fromInternalActivity($ids['user_id'], $ids['context_id'], $loaded->link_id);
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

    public function testGradebookStoresFractionOnTheRequestContextResult() {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for RequestContext fixtures.');
        }
        try {
            $ids = $this->insertCourseFixture($pdo, LTIX::ROLE_LEARNER);
            $quiz = new Quiz1();
            $quiz->context_id = $ids['context_id'];
            $quiz->user_id = $ids['user_id'];
            $quiz->title = 'Graded quiz';
            $quiz_id = Quiz1Repository::insertQuiz($quiz);
            $link_id = Quiz1Repository::publish($quiz_id, $ids['context_id']);
            $rc = RequestContext::fromInternalActivity($ids['user_id'], $ids['context_id'], $link_id);

            $stored = Gradebook::record($rc, 0.5);
            $this->assertEqualsWithDelta(0.5, $stored, 0.0001);

            $row = $pdo->rowDie(
                "SELECT grade FROM {$this->prefix()}lti_result WHERE result_id = :RID",
                array(':RID' => $rc->result->id)
            );
            $this->assertEqualsWithDelta(0.5, (float) $row['grade'], 0.0001);

            $again = RequestContext::fromInternalActivity($ids['user_id'], $ids['context_id'], $link_id);
            $this->assertEqualsWithDelta(0.5, (float) $again->result->grade, 0.0001);
        } finally {
            $pdo->rollBack();
        }
    }

    public function testInstructorAndAdminFlagsComeFromMembership() {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for RequestContext fixtures.');
        }
        try {
            $instructor = $this->insertCourseFixture($pdo, LTIX::ROLE_INSTRUCTOR);
            $rc = RequestContext::fromInternalActivity($instructor['user_id'], $instructor['context_id'], null);
            $this->assertTrue($rc->user->instructor);
            $this->assertFalse($rc->user->admin);
            $this->assertSame(LTIX::ROLE_INSTRUCTOR, $rc->membership->role);

            $admin = $this->insertCourseFixture($pdo, LTIX::ROLE_ADMINISTRATOR);
            $rc = RequestContext::fromInternalActivity($admin['user_id'], $admin['context_id'], null);
            $this->assertTrue($rc->user->instructor);
            $this->assertTrue($rc->user->admin);
        } finally {
            $pdo->rollBack();
        }
    }

    public function testNonMemberIsRejected() {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for RequestContext fixtures.');
        }
        try {
            $ids = $this->insertCourseFixture($pdo, LTIX::ROLE_LEARNER);
            $outsider = $this->insertUser($pdo, $ids['key_id'], 'outsider');
            $this->expectException(RequestContextException::class);
            try {
                RequestContext::fromInternalActivity($outsider, $ids['context_id'], null);
            } catch ( RequestContextException $ex ) {
                $this->assertSame(403, $ex->httpStatus);
                throw $ex;
            }
        } finally {
            $pdo->rollBack();
        }
    }

    public function testLinkFromAnotherContextIsRejected() {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for RequestContext fixtures.');
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

            $this->expectException(RequestContextException::class);
            try {
                RequestContext::fromInternalActivity($a['user_id'], $a['context_id'], $link_id);
            } catch ( RequestContextException $ex ) {
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
            $this->markTestSkipped('Database not available for RequestContext fixtures.');
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

<?php

use Tsugi\Core\LTIX;
use Tsugi\Services\Plus\RosterSync;
use Tsugi\Util\U;

class RosterSyncTest extends \PHPUnit\Framework\TestCase
{
    private $pdoBefore;

    protected function setUp(): void
    {
        global $PDOX;
        $this->pdoBefore = $PDOX ?? null;
    }

    protected function tearDown(): void
    {
        global $PDOX;
        if ( is_object($PDOX) && method_exists($PDOX, 'inTransaction') && $PDOX->inTransaction() ) {
            $PDOX->rollBack();
        }
        $PDOX = $this->pdoBefore;
    }

    public function testInstructorDelayIsFiveMinutesAndLearnerDelayIsThirty()
    {
        $this->assertFalse(RosterSync::dueAge(RosterSync::DELAY_INSTRUCTOR, true));
        $this->assertTrue(RosterSync::dueAge(RosterSync::DELAY_INSTRUCTOR + 1, true));
        $this->assertFalse(RosterSync::dueAge(RosterSync::DELAY_LEARNER, false));
        $this->assertTrue(RosterSync::dueAge(RosterSync::DELAY_LEARNER + 1, false));
        $this->assertTrue(RosterSync::dueAge(null, false));
        $this->assertTrue(RosterSync::dueAge(-1, false));
        $this->assertTrue(RosterSync::dueAge(1, true, true));
        $this->assertFalse(RosterSync::dueAge(10, false));
        $this->assertFalse(RosterSync::dueAge(10, true));
    }

    public function testFailedTokenWaitsAMinuteBeforeAnotherTry()
    {
        $this->assertTrue(RosterSync::failureHold(0, false));
        $this->assertTrue(RosterSync::failureHold(RosterSync::RETRY_SECONDS - 1, false));
        $this->assertFalse(RosterSync::failureHold(RosterSync::RETRY_SECONDS, false));
        $this->assertFalse(RosterSync::failureHold(1, true));
        $this->assertFalse(RosterSync::failureHold(null, false));
    }

    public function testRolesMatchALaunch()
    {
        $this->assertSame(LTIX::ROLE_LEARNER, RosterSync::roleFor(array(
            'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner',
        )));
        $this->assertSame(LTIX::ROLE_INSTRUCTOR, RosterSync::roleFor(array(
            'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor',
        )));
        $this->assertSame(LTIX::ROLE_ADMINISTRATOR, RosterSync::roleFor(array(
            'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor',
            'http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator',
        )));
        $this->assertSame(LTIX::ROLE_LEARNER, RosterSync::roleFor(array()));
    }

    public function testDisplayNamePrefersTheFullName()
    {
        $this->assertSame('Jane Doe', RosterSync::displayName(array(
            'name' => 'Jane Doe',
            'given_name' => 'Janet',
            'email' => 'jane@example.edu',
            'user_id' => 'abc',
        )));
        $this->assertSame('Janet Doe', RosterSync::displayName((object) array(
            'given_name' => 'Janet',
            'family_name' => 'Doe',
            'user_id' => 'abc',
        )));
        $this->assertSame('jane@example.edu', RosterSync::displayName(array(
            'email' => 'jane@example.edu',
            'user_id' => 'abc',
        )));
    }

    public function testScrubRemovesTokens()
    {
        $raw = 'Authorization: Bearer secret-token.abc client_assertion=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIn0.signature "access_token":"opaque"';
        $clean = RosterSync::scrub($raw);
        $this->assertStringNotContainsString('secret-token', $clean);
        $this->assertStringNotContainsString('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9', $clean);
        $this->assertStringNotContainsString('opaque', $clean);
        $this->assertStringContainsString('Bearer [redacted]', $clean);
    }

    public function testStoreMembersInsertsUpdatesAndExpireKeepsRecentRows()
    {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for roster fixtures.');
        }
        try {
            $ids = $this->insertCourse($pdo);
            $instructor = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor';
            $learner = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner';
            $first = RosterSync::storeMembers($ids['context_id'], $ids['key_id'], array(
                array(
                    'user_id' => 'inst-1',
                    'name' => 'Jane Instructor',
                    'email' => 'jane@example.edu',
                    'roles' => array($instructor),
                ),
                array(
                    'user_id' => 'learn-1',
                    'given_name' => 'Pat',
                    'family_name' => 'Student',
                    'email' => 'pat@example.edu',
                    'roles' => array($learner),
                ),
                array('email' => 'nobody@example.edu'),
            ));
            $this->assertSame(2, $first['count']);
            $this->assertSame(2, $first['inserted']);
            $this->assertSame(1, $first['errors']);

            $p = $this->prefix();
            $jane = $pdo->rowDie(
                "SELECT U.displayname, U.email, M.role, M.deleted
                 FROM {$p}lti_user AS U
                 JOIN {$p}lti_membership AS M ON M.user_id = U.user_id
                 WHERE U.key_id = :KID AND U.subject_key = :SUB",
                array(':KID' => $ids['key_id'], ':SUB' => 'inst-1')
            );
            $this->assertSame('Jane Instructor', $jane['displayname']);
            $this->assertSame('jane@example.edu', $jane['email']);
            $this->assertSame(LTIX::ROLE_INSTRUCTOR, (int) $jane['role']);
            $this->assertSame(0, (int) $jane['deleted']);

            $pdo->queryDie(
                "UPDATE {$p}lti_membership AS M
                 JOIN {$p}lti_user AS U ON U.user_id = M.user_id
                 SET M.deleted = 1, M.deleted_at = NOW(), M.updated_at = DATE_SUB(NOW(), INTERVAL 8 DAY)
                 WHERE M.context_id = :CID AND U.subject_key = :SUB",
                array(':CID' => $ids['context_id'], ':SUB' => 'learn-1')
            );

            $second = RosterSync::storeMembers($ids['context_id'], $ids['key_id'], array(
                array(
                    'user_id' => 'inst-1',
                    'name' => 'Jane Q Instructor',
                    'roles' => array($instructor),
                ),
                array(
                    'user_id' => 'learn-1',
                    'name' => 'Pat Student',
                    'email' => 'pat@example.edu',
                    'roles' => array($learner),
                ),
            ));
            $this->assertSame(0, $second['inserted']);
            $this->assertSame(2, $second['updated']);

            $pat = $pdo->rowDie(
                "SELECT M.deleted, M.role
                 FROM {$p}lti_membership AS M
                 JOIN {$p}lti_user AS U ON U.user_id = M.user_id
                 WHERE M.context_id = :CID AND U.subject_key = :SUB",
                array(':CID' => $ids['context_id'], ':SUB' => 'learn-1')
            );
            $this->assertSame(0, (int) $pat['deleted']);
            $this->assertSame(LTIX::ROLE_LEARNER, (int) $pat['role']);

            $pdo->queryDie(
                "UPDATE {$p}lti_membership AS M
                 JOIN {$p}lti_user AS U ON U.user_id = M.user_id
                 SET M.updated_at = DATE_SUB(NOW(), INTERVAL 8 DAY)
                 WHERE M.context_id = :CID AND U.subject_key = :SUB",
                array(':CID' => $ids['context_id'], ':SUB' => 'learn-1')
            );
            $this->assertSame(1, RosterSync::expireAbsent($ids['context_id']));
            $pat = $pdo->rowDie(
                "SELECT M.deleted
                 FROM {$p}lti_membership AS M
                 JOIN {$p}lti_user AS U ON U.user_id = M.user_id
                 WHERE M.context_id = :CID AND U.subject_key = :SUB",
                array(':CID' => $ids['context_id'], ':SUB' => 'learn-1')
            );
            $jane = $pdo->rowDie(
                "SELECT M.deleted
                 FROM {$p}lti_membership AS M
                 JOIN {$p}lti_user AS U ON U.user_id = M.user_id
                 WHERE M.context_id = :CID AND U.subject_key = :SUB",
                array(':CID' => $ids['context_id'], ':SUB' => 'inst-1')
            );
            $this->assertSame(1, (int) $pat['deleted']);
            $this->assertSame(0, (int) $jane['deleted']);
        } finally {
            $pdo->rollBack();
        }
    }

    public function testRoleLabelUsesTheHigherOverride()
    {
        $this->assertSame('Learner', RosterSync::roleLabel(0, null));
        $this->assertSame('Instructor', RosterSync::roleLabel(0, LTIX::ROLE_INSTRUCTOR));
        $this->assertSame('Administrator', RosterSync::roleLabel(LTIX::ROLE_INSTRUCTOR, LTIX::ROLE_ADMINISTRATOR));
    }

    public function testSetDeletedMarksAndRestoresOneMembership()
    {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for roster fixtures.');
        }
        try {
            $ids = $this->insertCourse($pdo);
            RosterSync::storeMembers($ids['context_id'], $ids['key_id'], array(
                array(
                    'user_id' => 'learn-1',
                    'name' => 'Pat Student',
                    'email' => 'pat@example.edu',
                    'roles' => array('Learner'),
                ),
            ));
            $rows = RosterSync::members($ids['context_id']);
            $this->assertCount(1, $rows);
            $this->assertSame('Pat Student', $rows[0]['displayname']);
            $this->assertSame(0, (int) $rows[0]['deleted']);
            $membershipId = (int) $rows[0]['membership_id'];

            $this->assertTrue(RosterSync::setDeleted($ids['context_id'], $membershipId, true));
            $this->assertFalse(RosterSync::setDeleted($ids['context_id'], $membershipId + 99, true));
            $rows = RosterSync::members($ids['context_id']);
            $this->assertSame(1, (int) $rows[0]['deleted']);

            $this->assertTrue(RosterSync::setDeleted($ids['context_id'], $membershipId, false));
            $rows = RosterSync::members($ids['context_id']);
            $this->assertSame(0, (int) $rows[0]['deleted']);
        } finally {
            $pdo->rollBack();
        }
    }

    /**
     * @return \Tsugi\Util\PDOX|null
     */
    private function beginFixtureTransaction() {
        global $CFG, $PDOX;
        $config = dirname(__DIR__, 4) . '/config.php';
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
            $PDOX->rowDie("SELECT context_id FROM {$CFG->dbprefix}lti_context LIMIT 1");
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
     * @param \Tsugi\Util\PDOX $pdo
     * @return array{key_id:int,context_id:int}
     */
    private function insertCourse($pdo) {
        $p = $this->prefix();
        $stamp = 'roster'.bin2hex(random_bytes(6));
        $pdo->queryDie(
            "INSERT INTO {$p}lti_key (key_key, key_sha256, created_at, updated_at)
             VALUES (:K, :SHA, NOW(), NOW())",
            array(':K' => $stamp, ':SHA' => U::lti_sha256($stamp))
        );
        $keyId = (int) $pdo->lastInsertId();
        $contextKey = $stamp.'-context';
        $pdo->queryDie(
            "INSERT INTO {$p}lti_context
                (context_key, context_sha256, title, key_id, plus_course, created_at, updated_at)
             VALUES (:CK, :SHA, :TITLE, :KID, 1, NOW(), NOW())",
            array(
                ':CK' => $contextKey,
                ':SHA' => U::lti_sha256($contextKey),
                ':TITLE' => 'Roster course',
                ':KID' => $keyId,
            )
        );
        return array(
            'key_id' => $keyId,
            'context_id' => (int) $pdo->lastInsertId(),
        );
    }
}

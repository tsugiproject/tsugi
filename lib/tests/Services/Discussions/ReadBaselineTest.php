<?php

use Tsugi\Core\LTIX;
use Tsugi\Core\ReqScope;
use Tsugi\Services\Discussions\DiscussionsService;

/**
 * First-visit read tracking marks every current thread in the course as read.
 * It leaves an existing tracking row's flags alone, and a later visit does not
 * move that baseline forward.
 */
class ReadBaselineTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->boot();
        $this->requireTables();
        $this->begin();
    }

    protected function tearDown(): void
    {
        global $PDOX;
        ReqScope::reset();
        if ( isset($PDOX) && is_object($PDOX) && $PDOX->inTransaction() ) {
            $PDOX->rollBack();
        }
        parent::tearDown();
    }

    public function testFirstVisitBaselinesTheCourseAndKeepsExistingRows(): void
    {
        $userId = $this->insertUser();
        $courseA = $this->insertContext();
        $courseB = $this->insertContext();
        $linkA = $this->insertLink($courseA, 'discuss-a');
        $otherLinkA = $this->insertLink($courseA, 'discuss-a-other');
        $linkB = $this->insertLink($courseB, 'discuss-b');

        $seen = $this->insertThread($linkA, 4);
        $unseen = $this->insertThread($linkA, 0);
        $otherDiscussion = $this->insertThread($otherLinkA, 7);
        $otherCourse = $this->insertThread($linkB, 3);

        $this->insertTracking($seen, $userId, array(
            'subscribe' => 1,
            'favorite' => 1,
            'comments' => 0,
            'read_at' => null,
        ));
        $this->insertTracking($otherCourse, $userId, array(
            'subscribe' => 1,
            'favorite' => 0,
            'comments' => 2,
            'read_at' => '2018-01-01 00:00:00',
        ));

        $this->baseline($linkA, $userId, $courseA);

        $seenRow = $this->tracking($seen, $userId);
        $this->assertSame(1, (int) $seenRow['subscribe']);
        $this->assertSame(1, (int) $seenRow['favorite']);
        $this->assertSame(4, (int) $seenRow['comments']);
        $this->assertNotNull($seenRow['read_at']);

        $unseenRow = $this->tracking($unseen, $userId);
        $this->assertSame(0, (int) $unseenRow['subscribe']);
        $this->assertSame(0, (int) $unseenRow['favorite']);
        $this->assertSame(0, (int) $unseenRow['comments']);
        $this->assertNotNull($unseenRow['read_at']);

        $otherDiscussionRow = $this->tracking($otherDiscussion, $userId);
        $this->assertSame(7, (int) $otherDiscussionRow['comments']);
        $this->assertNotNull($otherDiscussionRow['read_at']);

        $otherCourseRow = $this->tracking($otherCourse, $userId);
        $this->assertSame(1, (int) $otherCourseRow['subscribe']);
        $this->assertSame(2, (int) $otherCourseRow['comments']);
        $this->assertSame('2018-01-01 00:00:00', $otherCourseRow['read_at']);

        $this->assertSame(1, $this->trackingCount($seen, $userId));
        $this->setComments($seen, 11);
        $this->setReadAt($userId, '2019-05-01 08:00:00');
        $this->baseline($linkA, $userId, $courseA);

        $this->assertSame(1, $this->trackingCount($seen, $userId));
        $this->assertSame(4, (int) $this->tracking($seen, $userId)['comments']);
        $this->assertSame('2019-05-01 08:00:00', $this->tracking($seen, $userId)['read_at']);
        $this->assertSame(4, $this->trackingCountForUser($userId));
    }

    public function testExistingBaselineDoesNotBackfillThreads(): void
    {
        $userId = $this->insertUser();
        $courseId = $this->insertContext();
        $linkId = $this->insertLink($courseId, 'discuss-seen');
        $seen = $this->insertThread($linkId, 2);
        $later = $this->insertThread($linkId, 5);
        $this->insertTracking($seen, $userId, array(
            'subscribe' => 0,
            'favorite' => 0,
            'comments' => 2,
            'read_at' => '2020-02-02 00:00:00',
        ));

        $this->baseline($linkId, $userId, $courseId);

        $this->assertNull($this->tracking($later, $userId));
        $this->assertSame('2020-02-02 00:00:00', $this->tracking($seen, $userId)['read_at']);
        $this->assertSame(2, (int) $this->tracking($seen, $userId)['comments']);
    }

    private function baseline(int $linkId, int $userId, int $contextId): void
    {
        $method = new ReflectionMethod(DiscussionsService::class, 'ensureContextReadBaselineForUser');
        $method->invoke(null, $linkId, $userId, $contextId);
    }

    private function boot(): void
    {
        global $CFG, $PDOX;
        $root = realpath(dirname(__DIR__, 4));
        if ( $root === false ) {
            $this->fail('Could not resolve the Tsugi checkout.');
        }
        if ( ! isset($CFG) || ! is_object($CFG) || ! isset($CFG->dirroot) || realpath((string) $CFG->dirroot) !== $root ) {
            require_once $root.'/config.php';
        }
        if ( realpath((string) $CFG->dirroot) !== $root ) {
            $this->fail('Refusing to run against a different Tsugi checkout.');
        }
        LTIX::getConnection();
        if ( ! isset($PDOX) || ! is_object($PDOX) ) {
            $this->fail('Database is not available.');
        }
    }

    private function requireTables(): void
    {
        global $CFG, $PDOX;
        if ( ! $PDOX->isMySQL() ) {
            $this->markTestSkipped('MySQL is required.');
        }
        foreach ( array('lti_key', 'lti_user', 'lti_context', 'lti_link', 'tdiscus_thread', 'tdiscus_user_thread') as $table ) {
            if ( $PDOX->metadata($CFG->dbprefix.$table) === false ) {
                $this->markTestSkipped($table.' is missing. Run php admin/upgrade.php.');
            }
        }
    }

    private function begin(): void
    {
        global $PDOX;
        if ( $PDOX->inTransaction() ) {
            $PDOX->rollBack();
        }
        $PDOX->beginTransaction();
    }

    private function insertUser(): int
    {
        global $CFG;
        $token = bin2hex(random_bytes(16));
        $this->keyId = $this->insertRow(
            "INSERT INTO {$CFG->dbprefix}lti_key (key_sha256, key_key, key_title)
             VALUES (:sha, :key_key, :title)",
            array(
                ':sha' => hash('sha256', $token),
                ':key_key' => $token,
                ':title' => 'Read baseline test',
            )
        );
        $userToken = bin2hex(random_bytes(16));
        $this->userId = $this->insertRow(
            "INSERT INTO {$CFG->dbprefix}lti_user (user_sha256, user_key, key_id, displayname)
             VALUES (:sha, :user_key, :key_id, :displayname)",
            array(
                ':sha' => hash('sha256', $userToken),
                ':user_key' => $userToken,
                ':key_id' => $this->keyId,
                ':displayname' => 'Read baseline student',
            )
        );
        return $this->userId;
    }

    private function insertContext(): int
    {
        global $CFG;
        $token = bin2hex(random_bytes(16));
        return $this->insertRow(
            "INSERT INTO {$CFG->dbprefix}lti_context (context_sha256, context_key, key_id, title)
             VALUES (:sha, :context_key, :key_id, :title)",
            array(
                ':sha' => hash('sha256', $token),
                ':context_key' => $token,
                ':key_id' => $this->keyId,
                ':title' => 'Read baseline course',
            )
        );
    }

    private function insertLink(int $contextId, string $key): int
    {
        global $CFG;
        $token = $key.'-'.bin2hex(random_bytes(8));
        return $this->insertRow(
            "INSERT INTO {$CFG->dbprefix}lti_link (link_sha256, link_key, context_id, title)
             VALUES (:sha, :link_key, :context_id, :title)",
            array(
                ':sha' => hash('sha256', $token),
                ':link_key' => $token,
                ':context_id' => $contextId,
                ':title' => $key,
            )
        );
    }

    private function insertThread(int $linkId, int $comments): int
    {
        global $CFG;
        return $this->insertRow(
            "INSERT INTO {$CFG->dbprefix}tdiscus_thread (link_id, user_id, title, body, comments)
             VALUES (:link_id, :user_id, :title, :body, :comments)",
            array(
                ':link_id' => $linkId,
                ':user_id' => $this->userId,
                ':title' => 'Thread',
                ':body' => 'Body',
                ':comments' => $comments,
            )
        );
    }

    /** @param array{subscribe: int, favorite: int, comments: int, read_at: ?string} $row */
    private function insertTracking(int $threadId, int $userId, array $row): void
    {
        global $CFG;
        $this->insertRow(
            "INSERT INTO {$CFG->dbprefix}tdiscus_user_thread
                (thread_id, user_id, subscribe, favorite, comments, read_at)
             VALUES
                (:thread_id, :user_id, :subscribe, :favorite, :comments, :read_at)",
            array(
                ':thread_id' => $threadId,
                ':user_id' => $userId,
                ':subscribe' => $row['subscribe'],
                ':favorite' => $row['favorite'],
                ':comments' => $row['comments'],
                ':read_at' => $row['read_at'],
            )
        );
    }

    private function setComments(int $threadId, int $comments): void
    {
        global $CFG, $PDOX;
        $stmt = $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}tdiscus_thread SET comments = :comments WHERE thread_id = :thread_id",
            array(':comments' => $comments, ':thread_id' => $threadId)
        );
        $this->assertTrue((bool) $stmt->success, (string) $stmt->errorImplode);
    }

    private function setReadAt(int $userId, string $readAt): void
    {
        global $CFG, $PDOX;
        $stmt = $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}tdiscus_user_thread SET read_at = :read_at WHERE user_id = :user_id",
            array(':read_at' => $readAt, ':user_id' => $userId)
        );
        $this->assertTrue((bool) $stmt->success, (string) $stmt->errorImplode);
    }

    /** @return array<string, mixed>|null */
    private function tracking(int $threadId, int $userId): ?array
    {
        global $CFG, $PDOX;
        $row = $PDOX->rowDie(
            "SELECT subscribe, favorite, comments, read_at
             FROM {$CFG->dbprefix}tdiscus_user_thread
             WHERE thread_id = :thread_id AND user_id = :user_id",
            array(':thread_id' => $threadId, ':user_id' => $userId)
        );
        return is_array($row) ? $row : null;
    }

    private function trackingCount(int $threadId, int $userId): int
    {
        global $CFG, $PDOX;
        $row = $PDOX->rowDie(
            "SELECT COUNT(*) AS n
             FROM {$CFG->dbprefix}tdiscus_user_thread
             WHERE thread_id = :thread_id AND user_id = :user_id",
            array(':thread_id' => $threadId, ':user_id' => $userId)
        );
        return (int) $row['n'];
    }

    private function trackingCountForUser(int $userId): int
    {
        global $CFG, $PDOX;
        $row = $PDOX->rowDie(
            "SELECT COUNT(*) AS n FROM {$CFG->dbprefix}tdiscus_user_thread WHERE user_id = :user_id",
            array(':user_id' => $userId)
        );
        return (int) $row['n'];
    }

    /** @param array<string, mixed> $params */
    private function insertRow(string $sql, array $params): int
    {
        global $PDOX;
        $stmt = $PDOX->queryReturnError($sql, $params);
        $this->assertTrue((bool) $stmt->success, (string) $stmt->errorImplode);
        return (int) $PDOX->lastInsertId();
    }

    private int $userId = 0;
    private int $keyId = 0;

}

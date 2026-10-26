<?php

use Tsugi\Core\LTIX;
use Tsugi\Core\ReqScope;
use Tsugi\Services\Grades\Gradebook;
use Tsugi\Services\Plus\GradeSync;
use Tsugi\Util\LTI13;
use Tsugi\Util\U;

class GradeSyncTest extends \PHPUnit\Framework\TestCase
{
    private $pdoBefore;

    protected function setUp(): void
    {
        global $PDOX;
        $this->pdoBefore = $PDOX ?? null;
        ReqScope::reset();
    }

    protected function tearDown(): void
    {
        global $PDOX;
        ReqScope::reset();
        if ( is_object($PDOX) && method_exists($PDOX, 'inTransaction') && $PDOX->inTransaction() ) {
            $PDOX->rollBack();
        }
        $PDOX = $this->pdoBefore;
    }

    public function testColumnNeedsATitleAndAPositiveTotal()
    {
        $this->assertFalse(GradeSync::columnReady('', 10));
        $this->assertFalse(GradeSync::columnReady('Quiz', 0));
        $this->assertFalse(GradeSync::columnReady('Quiz', null));
        $this->assertTrue(GradeSync::columnReady('Quiz', 10));
    }

    public function testReleasedNullSendsAndZeroHolds()
    {
        $this->assertFalse(GradeSync::withheld(null));
        $this->assertFalse(GradeSync::withheld(''));
        $this->assertFalse(GradeSync::withheld(1));
        $this->assertTrue(GradeSync::withheld(0));
        $this->assertTrue(GradeSync::withheld('0'));
    }

    public function testShellLinkIsNotAColumn()
    {
        $this->assertTrue(GradeSync::isShellLink(5, 5));
        $this->assertFalse(GradeSync::isShellLink(5, 0));
        $this->assertFalse(GradeSync::isShellLink(5, 6));
    }

    public function testScoreGivenScalesTheFraction()
    {
        $this->assertEqualsWithDelta(8.0, GradeSync::scoreGiven(0.8, 10), 0.0001);
        $this->assertEqualsWithDelta(0.0, GradeSync::scoreGiven(0, 10), 0.0001);
        $this->assertNull(GradeSync::scoreGiven(0.8, 0));
        $this->assertNull(GradeSync::scoreGiven(null, 10));
    }

    public function testLineItemUsesPointsLinkIdAndDue()
    {
        $item = GradeSync::lineItemBody(42, 'Week 3', 10, '2026-10-26 11:59:59', 'America/Detroit');
        $this->assertNotNull($item);
        $this->assertSame(10.0, $item->scoreMaximum);
        $this->assertSame('Week 3', $item->label);
        $this->assertSame('42', $item->resourceId);
        $this->assertSame('2026-10-26T23:59:59-04:00', $item->endDateTime);
        $this->assertNull(GradeSync::lineItemBody(42, 'Week 3', 0, null, null));
        $this->assertNull(GradeSync::lineItemBody(42, '  ', 10, null, null));
    }

    public function testBlockReasonStopsAColumnThatIsNotReady()
    {
        $ready = array(
            'plus_course' => 1,
            'link_id' => 5,
            'plus_launch_link_id' => 9,
            'title' => 'Week 3',
            'score_maximum' => 10,
            'lti13_lineitem' => '',
            'lti13_lineitems' => 'https://lms.example/lineitems',
            'lti13_deployment_id' => 'deploy-1',
            'lms_token_url' => 'https://lms.example/token',
            'lms_client' => 'client-1',
        );
        $this->assertNull(GradeSync::blockReason($ready));
        $this->assertSame('not_plus', GradeSync::blockReason(array('plus_course' => 0) + $ready));
        $this->assertSame('shell', GradeSync::blockReason(array('plus_launch_link_id' => 5) + $ready));
        $this->assertSame('exists', GradeSync::blockReason(array('lti13_lineitem' => 'https://lms.example/lineitems/1') + $ready));
        $this->assertSame('not_ready', GradeSync::blockReason(array('score_maximum' => null) + $ready));
        $this->assertSame('no_token_url', GradeSync::blockReason(array('lms_token_url' => '') + $ready));
    }

    public function testRequestLineItemReportsAMissingToken()
    {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null || ! $this->hasServerStatus($pdo) ) {
            $this->markTestSkipped('Database not ready for Plus grade fixtures.');
        }
        try {
            $ids = $this->insertActivity($pdo, array(
                'plus_course' => 1,
                'lineitems' => 'https://lms.example/lineitems',
                'deployment' => 'deploy-1',
                'client' => 'client-1',
                'score_maximum' => 10,
                'title' => 'Week 3',
            ));
            $result = GradeSync::requestLineItem($ids['link_id']);
            $this->assertFalse($result['ok']);
            $this->assertSame('no_token_url', $result['status']);
            $link = $pdo->rowDie(
                "SELECT lti13_lineitem FROM {$this->prefix()}lti_link WHERE link_id = :LID",
                array(':LID' => $ids['link_id'])
            );
            $this->assertTrue($link['lti13_lineitem'] === null || $link['lti13_lineitem'] === '');
        } finally {
            $pdo->rollBack();
        }
    }

    public function testGradeSendCopiesQuizPointsAndAttemptsTheLineItem()
    {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null || ! $this->hasServerStatus($pdo) ) {
            $this->markTestSkipped('Database not ready for Plus grade fixtures.');
        }
        try {
            $ids = $this->insertActivity($pdo, array(
                'plus_course' => 1,
                'lineitems' => 'https://lms.example/lineitems',
                'deployment' => 'deploy-1',
                'client' => 'client-1',
                'title' => 'QTI Export Test',
            ));
            $p = $this->prefix();
            $pdo->queryDie(
                "INSERT INTO {$p}quiz1_quiz
                    (context_id, user_id, link_id, title, created_at, updated_at)
                 VALUES (:CID, :UID, :LID, :TITLE, NOW(), NOW())",
                array(
                    ':CID' => $ids['context_id'],
                    ':UID' => $ids['user_id'],
                    ':LID' => $ids['link_id'],
                    ':TITLE' => 'QTI Export Test',
                )
            );
            $quizId = (int) $pdo->lastInsertId();
            $pdo->queryDie(
                "INSERT INTO {$p}quiz1_question
                    (quiz_id, sequence, qtype, prompt, points, created_at, updated_at)
                 VALUES (:QID, 1, 'short', 'One', 11, NOW(), NOW())",
                array(':QID' => $quizId)
            );
            $pdo->queryDie(
                "INSERT INTO {$p}lti_result (link_id, user_id, grade, created_at, updated_at)
                 VALUES (:LID, :UID, 0.36, NOW(), NOW())",
                array(':LID' => $ids['link_id'], ':UID' => $ids['user_id'])
            );
            $resultId = (int) $pdo->lastInsertId();

            $handled = GradeSync::deliverIfPlus(
                $resultId,
                0.36,
                null,
                LTI13::ACTIVITY_PROGRESS_COMPLETED,
                LTI13::GRADING_PROGRESS_FULLYGRADED
            );
            $this->assertTrue($handled);

            $link = $pdo->rowDie(
                "SELECT score_maximum, lti13_lineitem FROM {$p}lti_link WHERE link_id = :LID",
                array(':LID' => $ids['link_id'])
            );
            $result = $pdo->rowDie(
                "SELECT server_status FROM {$p}lti_result WHERE result_id = :RID",
                array(':RID' => $resultId)
            );
            $this->assertEqualsWithDelta(11.0, (float) $link['score_maximum'], 0.0001);
            $this->assertTrue($link['lti13_lineitem'] === null || $link['lti13_lineitem'] === '');
            $this->assertStringContainsString('token URL', (string) $result['server_status']);
        } finally {
            $pdo->rollBack();
        }
    }

    public function testActivityLogListsErrorsApartFromSends()
    {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null || ! $this->hasServerStatus($pdo) ) {
            $this->markTestSkipped('Database not ready for Plus grade fixtures.');
        }
        try {
            $ids = $this->insertActivity($pdo, array(
                'plus_course' => 1,
                'title' => 'QTI Export Test',
            ));
            $p = $this->prefix();
            $fail = json_encode(array('detail' => 'The token URL is missing.', 'debug' => 'token trace'));
            $ok = json_encode(array('detail' => 'sent', 'debug' => ''));
            $pdo->queryDie(
                "INSERT INTO {$p}lti_context_plus_log
                    (context_id, link_id, user_id, kind, success, url, detail, created_at)
                 VALUES (:CID, :LID, :UID, 'lineitem_create', 0, 'https://lms.example/lineitems', :DETAIL, NOW())",
                array(
                    ':CID' => $ids['context_id'],
                    ':LID' => $ids['link_id'],
                    ':UID' => $ids['user_id'],
                    ':DETAIL' => $fail,
                )
            );
            $pdo->queryDie(
                "INSERT INTO {$p}lti_context_plus_log
                    (context_id, link_id, user_id, kind, success, http_response, url, detail, created_at)
                 VALUES (:CID, :LID, :UID, 'score', 1, 200, 'https://lms.example/lineitems/1/scores', :DETAIL, NOW())",
                array(
                    ':CID' => $ids['context_id'],
                    ':LID' => $ids['link_id'],
                    ':UID' => $ids['user_id'],
                    ':DETAIL' => $ok,
                )
            );
            $pdo->queryDie(
                "INSERT INTO {$p}lti_context_plus_log
                    (context_id, kind, success, http_response, url, detail, created_at)
                 VALUES (:CID, 'roster', 0, 500, 'https://lms.example/memberships', :DETAIL, NOW())",
                array(
                    ':CID' => $ids['context_id'],
                    ':DETAIL' => '{"detail":"roster failed"}',
                )
            );

            $log = GradeSync::activityLog($ids['context_id']);
            $this->assertCount(1, $log['failures']);
            $this->assertCount(1, $log['successes']);
            $this->assertSame('Line item', $log['failures'][0]['kind_label']);
            $this->assertSame('QTI Export Test', $log['failures'][0]['link_title']);
            $this->assertSame('Pat Student', $log['failures'][0]['displayname']);
            $this->assertSame('The token URL is missing.', $log['failures'][0]['detail_text']);
            $this->assertSame('token trace', $log['failures'][0]['detail_debug']);
            $this->assertSame('Score', $log['successes'][0]['kind_label']);
            $this->assertSame('sent', $log['successes'][0]['detail_text']);
        } finally {
            $pdo->rollBack();
        }
    }

    public function testOverrideStoresPointsAndSendsOnlyWhenALineItemExists()
    {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null || ! $this->hasServerStatus($pdo) ) {
            $this->markTestSkipped('Database not ready for Plus grade fixtures.');
        }
        try {
            $local = $this->insertActivity($pdo, array(
                'plus_course' => 1,
                'score_maximum' => 10,
                'title' => 'Quiz',
            ));
            $p = $this->prefix();
            $pdo->queryDie(
                "INSERT INTO {$p}lti_result (link_id, user_id, grade, created_at, updated_at)
                 VALUES (:LID, :UID, 0.4, NOW(), NOW())",
                array(':LID' => $local['link_id'], ':UID' => $local['user_id'])
            );
            $localResult = (int) $pdo->lastInsertId();

            $rejected = GradeSync::applyOverride($localResult, 11);
            $this->assertFalse($rejected['ok']);
            $this->assertSame('bad_points', $rejected['status']);

            $stored = GradeSync::applyOverride($localResult, 8);
            $this->assertTrue($stored['ok']);
            $this->assertSame('stored', $stored['status']);
            $row = $pdo->rowDie(
                "SELECT grade, score_given, lti13_lineitem
                 FROM {$p}lti_result AS R
                 JOIN {$p}lti_link AS L ON L.link_id = R.link_id
                 WHERE R.result_id = :RID",
                array(':RID' => $localResult)
            );
            $this->assertEqualsWithDelta(0.8, (float) $row['grade'], 0.0001);
            $this->assertEqualsWithDelta(8.0, (float) $row['score_given'], 0.0001);
            $this->assertTrue($row['lti13_lineitem'] === null || $row['lti13_lineitem'] === '');

            $remote = $this->insertActivity($pdo, array(
                'plus_course' => 1,
                'lineitems' => 'https://lms.example/lineitems',
                'deployment' => 'deploy-1',
                'client' => 'client-1',
                'score_maximum' => 10,
                'lineitem' => 'https://lms.example/lineitems/9',
                'title' => 'Sent quiz',
            ));
            $pdo->queryDie(
                "INSERT INTO {$p}lti_result (link_id, user_id, grade, created_at, updated_at)
                 VALUES (:LID, :UID, 0.2, NOW(), NOW())",
                array(':LID' => $remote['link_id'], ':UID' => $remote['user_id'])
            );
            $remoteResult = (int) $pdo->lastInsertId();
            $failed = GradeSync::applyOverride($remoteResult, 6);
            $this->assertTrue($failed['ok']);
            $this->assertSame('send_failed', $failed['status']);
            $sent = $pdo->rowDie(
                "SELECT grade, server_status, lti13_lineitem
                 FROM {$p}lti_result AS R
                 JOIN {$p}lti_link AS L ON L.link_id = R.link_id
                 WHERE R.result_id = :RID",
                array(':RID' => $remoteResult)
            );
            $this->assertEqualsWithDelta(0.6, (float) $sent['grade'], 0.0001);
            $this->assertSame('https://lms.example/lineitems/9', $sent['lti13_lineitem']);
            $this->assertStringContainsString('token URL', (string) $sent['server_status']);

            $held = $this->insertActivity($pdo, array(
                'plus_course' => 1,
                'score_maximum' => 10,
                'lineitem' => 'https://lms.example/lineitems/held',
                'released' => 0,
                'title' => 'Held quiz',
            ));
            $pdo->queryDie(
                "INSERT INTO {$p}lti_result (link_id, user_id, grade, created_at, updated_at)
                 VALUES (:LID, :UID, 0.1, NOW(), NOW())",
                array(':LID' => $held['link_id'], ':UID' => $held['user_id'])
            );
            $heldResult = (int) $pdo->lastInsertId();
            $heldResultRow = GradeSync::applyOverride($heldResult, 4);
            $this->assertSame('held', $heldResultRow['status']);
            $heldGrade = $pdo->rowDie(
                "SELECT grade, server_status FROM {$p}lti_result WHERE result_id = :RID",
                array(':RID' => $heldResult)
            );
            $this->assertEqualsWithDelta(0.4, (float) $heldGrade['grade'], 0.0001);
            $this->assertTrue($heldGrade['server_status'] === null || $heldGrade['server_status'] === '');
        } finally {
            $pdo->rollBack();
        }
    }

    public function testDueDateChangePutsTheExistingLineItem()
    {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null || ! $this->hasServerStatus($pdo) ) {
            $this->markTestSkipped('Database not ready for Plus grade fixtures.');
        }
        try {
            $plain = $this->insertActivity($pdo, array(
                'plus_course' => 1,
                'score_maximum' => 10,
                'title' => 'No column yet',
            ));
            $skipped = GradeSync::pushDueDateForKey($plain['context_id'], $this->linkKey($pdo, $plain['link_id']));
            $this->assertTrue($skipped['ok']);
            $this->assertSame('no_lineitem', $skipped['status']);

            $ids = $this->insertActivity($pdo, array(
                'plus_course' => 1,
                'lineitems' => 'https://lms.example/lineitems',
                'deployment' => 'deploy-1',
                'client' => 'client-1',
                'score_maximum' => 10,
                'lineitem' => 'https://lms.example/lineitems/9',
                'title' => 'Week 3',
            ));
            $p = $this->prefix();
            $pdo->queryDie(
                "UPDATE {$p}lti_link SET end_datetime = '2026-10-26 23:59:59' WHERE link_id = :LID",
                array(':LID' => $ids['link_id'])
            );
            $pdo->queryDie(
                "UPDATE {$p}lti_context SET timezone = 'America/Detroit' WHERE context_id = :CID",
                array(':CID' => $ids['context_id'])
            );
            $result = GradeSync::pushDueDateForKey($ids['context_id'], $this->linkKey($pdo, $ids['link_id']));
            $this->assertFalse($result['ok']);
            $this->assertSame('no_token_url', $result['status']);
            $log = $pdo->rowDie(
                "SELECT kind, request, detail FROM {$p}lti_context_plus_log
                 WHERE context_id = :CID AND kind = 'lineitem_update'
                 ORDER BY context_plus_log_id DESC LIMIT 1",
                array(':CID' => $ids['context_id'])
            );
            $this->assertSame('lineitem_update', $log['kind']);
            $this->assertStringContainsString('2026-10-26T23:59:59-04:00', (string) $log['request']);
            $this->assertStringContainsString('token URL', (string) $log['detail']);
        } finally {
            $pdo->rollBack();
        }
    }

    /**
     * @param \Tsugi\Util\PDOX $pdo
     * @param int $linkId
     */
    private function linkKey($pdo, $linkId)
    {
        $row = $pdo->rowDie(
            "SELECT link_key FROM {$this->prefix()}lti_link WHERE link_id = :LID",
            array(':LID' => (int) $linkId)
        );
        return (string) $row['link_key'];
    }

    public function testRequestLineItemUsesQuizQuestionPoints()
    {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null || ! $this->hasServerStatus($pdo) ) {
            $this->markTestSkipped('Database not ready for Plus grade fixtures.');
        }
        try {
            $ids = $this->insertActivity($pdo, array(
                'plus_course' => 1,
                'lineitems' => 'https://lms.example/lineitems',
                'deployment' => 'deploy-1',
                'client' => 'client-1',
                'title' => 'Quiz',
            ));
            $p = $this->prefix();
            $pdo->queryDie(
                "INSERT INTO {$p}quiz1_quiz
                    (context_id, user_id, link_id, title, created_at, updated_at)
                 VALUES (:CID, :UID, :LID, :TITLE, NOW(), NOW())",
                array(
                    ':CID' => $ids['context_id'],
                    ':UID' => $ids['user_id'],
                    ':LID' => $ids['link_id'],
                    ':TITLE' => 'Quiz',
                )
            );
            $quizId = (int) $pdo->lastInsertId();
            $pdo->queryDie(
                "INSERT INTO {$p}quiz1_question
                    (quiz_id, sequence, qtype, prompt, points, created_at, updated_at)
                 VALUES (:QID, 1, 'short', 'One', 4, NOW(), NOW())",
                array(':QID' => $quizId)
            );
            $pdo->queryDie(
                "INSERT INTO {$p}quiz1_question
                    (quiz_id, sequence, qtype, prompt, points, created_at, updated_at)
                 VALUES (:QID, 2, 'short', 'Two', 6, NOW(), NOW())",
                array(':QID' => $quizId)
            );

            $result = GradeSync::requestLineItem($ids['link_id']);
            $this->assertFalse($result['ok']);
            $this->assertSame('no_token_url', $result['status']);
            $link = $pdo->rowDie(
                "SELECT score_maximum FROM {$p}lti_link WHERE link_id = :LID",
                array(':LID' => $ids['link_id'])
            );
            $this->assertEqualsWithDelta(10.0, (float) $link['score_maximum'], 0.0001);
        } finally {
            $pdo->rollBack();
        }
    }

    public function testDueWithoutAZoneKeepsTheStoredClock()
    {
        $this->assertSame('2026-10-26T11:59:59', GradeSync::endDateTime('2026-10-26 11:59:59', null));
        $this->assertSame('2026-10-26T11:59:59', GradeSync::endDateTime('2026-10-26 11:59:59', 'EST'));
        $this->assertSame('2026-01-15T23:59:59-05:00', GradeSync::endDateTime('2026-01-15 08:00:00', 'America/Detroit'));
        $this->assertNull(GradeSync::endDateTime('', 'America/Detroit'));
        $this->assertNull(GradeSync::endDateTime('0000-00-00 00:00:00', 'America/Detroit'));
    }

    public function testMissingTokenRecordsTheErrorAndKeepsTheLocalGrade()
    {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null || ! $this->hasServerStatus($pdo) ) {
            $this->markTestSkipped('Database not ready for Plus grade fixtures.');
        }
        try {
            $ids = $this->insertActivity($pdo, array(
                'plus_course' => 1,
                'lineitems' => 'https://lms.example/lineitems',
                'deployment' => 'deploy-1',
                'client' => 'client-1',
                'score_maximum' => 10,
                'title' => 'Week 3',
            ));
            $rc = ReqScope::fromInternalActivity($ids['user_id'], $ids['context_id'], $ids['link_id']);
            $stored = Gradebook::record($rc, 0.8);
            $this->assertEqualsWithDelta(0.8, $stored, 0.0001);

            $p = $this->prefix();
            $result = $pdo->rowDie(
                "SELECT grade, server_grade, server_status FROM {$p}lti_result WHERE result_id = :RID",
                array(':RID' => $rc->result->id)
            );
            $link = $pdo->rowDie(
                "SELECT lti13_lineitem FROM {$p}lti_link WHERE link_id = :LID",
                array(':LID' => $ids['link_id'])
            );
            $logs = $pdo->allRowsDie(
                "SELECT kind, success, detail FROM {$p}lti_context_plus_log
                 WHERE context_id = :CID AND kind = :KIND",
                array(':CID' => $ids['context_id'], ':KIND' => GradeSync::KIND_LINEITEM)
            );
            $this->assertEqualsWithDelta(0.8, (float) $result['grade'], 0.0001);
            $this->assertNull($result['server_grade']);
            $this->assertStringContainsString('token URL', (string) $result['server_status']);
            $this->assertTrue($link['lti13_lineitem'] === null || $link['lti13_lineitem'] === '');
            $this->assertGreaterThanOrEqual(1, count($logs));
            $this->assertSame(0, (int) $logs[0]['success']);
        } finally {
            $pdo->rollBack();
        }
    }

    public function testHeldGradesDoNotPost()
    {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null || ! $this->hasServerStatus($pdo) ) {
            $this->markTestSkipped('Database not ready for Plus grade fixtures.');
        }
        try {
            $ids = $this->insertActivity($pdo, array(
                'plus_course' => 1,
                'lineitems' => 'https://lms.example/lineitems',
                'deployment' => 'deploy-1',
                'client' => 'client-1',
                'token' => 'https://lms.example/token',
                'score_maximum' => 10,
                'title' => 'Held quiz',
                'lineitem' => 'https://lms.example/lineitems/9',
                'released' => 0,
            ));
            $rc = ReqScope::fromInternalActivity($ids['user_id'], $ids['context_id'], $ids['link_id']);
            $stored = Gradebook::record($rc, 0.5);
            $this->assertEqualsWithDelta(0.5, $stored, 0.0001);

            $p = $this->prefix();
            $result = $pdo->rowDie(
                "SELECT grade, server_grade, server_status FROM {$p}lti_result WHERE result_id = :RID",
                array(':RID' => $rc->result->id)
            );
            $scores = $pdo->allRowsDie(
                "SELECT context_plus_log_id FROM {$p}lti_context_plus_log
                 WHERE context_id = :CID AND kind = :KIND",
                array(':CID' => $ids['context_id'], ':KIND' => GradeSync::KIND_SCORE)
            );
            $this->assertEqualsWithDelta(0.5, (float) $result['grade'], 0.0001);
            $this->assertNull($result['server_grade']);
            $this->assertTrue($result['server_status'] === null || $result['server_status'] === '');
            $this->assertSame(array(), $scores);
        } finally {
            $pdo->rollBack();
        }
    }

    public function testOrdinaryCourseDoesNotRecordAPlusError()
    {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null || ! $this->hasServerStatus($pdo) ) {
            $this->markTestSkipped('Database not ready for Plus grade fixtures.');
        }
        try {
            $ids = $this->insertActivity($pdo, array(
                'plus_course' => 0,
                'lineitems' => 'https://lms.example/lineitems',
                'score_maximum' => 10,
                'title' => 'Ordinary quiz',
            ));
            $rc = ReqScope::fromInternalActivity($ids['user_id'], $ids['context_id'], $ids['link_id']);
            $stored = Gradebook::record($rc, 0.4);
            $this->assertEqualsWithDelta(0.4, $stored, 0.0001);

            $p = $this->prefix();
            $result = $pdo->rowDie(
                "SELECT server_status FROM {$p}lti_result WHERE result_id = :RID",
                array(':RID' => $rc->result->id)
            );
            $logs = $pdo->allRowsDie(
                "SELECT context_plus_log_id FROM {$p}lti_context_plus_log WHERE context_id = :CID",
                array(':CID' => $ids['context_id'])
            );
            $this->assertTrue($result['server_status'] === null || $result['server_status'] === '');
            $this->assertSame(array(), $logs);
        } finally {
            $pdo->rollBack();
        }
    }

    private function beginFixtureTransaction()
    {
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

    private function prefix()
    {
        global $CFG;
        return $CFG->dbprefix;
    }

    /**
     * @param \Tsugi\Util\PDOX $pdo
     */
    private function hasServerStatus($pdo)
    {
        return $pdo->columnExists('server_status', $this->prefix().'lti_result');
    }

    /**
     * @param \Tsugi\Util\PDOX $pdo
     * @param array<string,mixed> $spec
     * @return array{key_id:int,context_id:int,user_id:int,link_id:int}
     */
    private function insertActivity($pdo, array $spec)
    {
        $p = $this->prefix();
        $stamp = 'ags'.bin2hex(random_bytes(6));
        $pdo->queryDie(
            "INSERT INTO {$p}lti_key
                (key_key, key_sha256, lms_client, lms_token_url, created_at, updated_at)
             VALUES (:K, :SHA, :CLIENT, :TOKEN, NOW(), NOW())",
            array(
                ':K' => $stamp,
                ':SHA' => U::lti_sha256($stamp),
                ':CLIENT' => $spec['client'] ?? null,
                ':TOKEN' => $spec['token'] ?? null,
            )
        );
        $keyId = (int) $pdo->lastInsertId();
        $pdo->queryDie(
            "INSERT INTO {$p}lti_user
                (user_key, user_sha256, displayname, email, subject_key, key_id, created_at, updated_at)
             VALUES (:UK, :SHA, :NAME, :EMAIL, :SUB, :KID, NOW(), NOW())",
            array(
                ':UK' => $stamp.'-user',
                ':SHA' => U::lti_sha256($stamp.'-user'),
                ':NAME' => 'Pat Student',
                ':EMAIL' => $stamp.'@example.test',
                ':SUB' => $stamp.'-subject',
                ':KID' => $keyId,
            )
        );
        $userId = (int) $pdo->lastInsertId();
        $contextKey = $stamp.'-context';
        $pdo->queryDie(
            "INSERT INTO {$p}lti_context
                (context_key, context_sha256, title, key_id, plus_course, lti13_lineitems,
                 lti13_deployment_id, created_at, updated_at)
             VALUES (:CK, :SHA, :TITLE, :KID, :PLUS, :ITEMS, :DEP, NOW(), NOW())",
            array(
                ':CK' => $contextKey,
                ':SHA' => U::lti_sha256($contextKey),
                ':TITLE' => 'Plus grade course',
                ':KID' => $keyId,
                ':PLUS' => (int) ($spec['plus_course'] ?? 0),
                ':ITEMS' => $spec['lineitems'] ?? null,
                ':DEP' => $spec['deployment'] ?? null,
            )
        );
        $contextId = (int) $pdo->lastInsertId();
        $pdo->queryDie(
            "INSERT INTO {$p}lti_membership
                (context_id, user_id, role, created_at, updated_at)
             VALUES (:CID, :UID, :ROLE, NOW(), NOW())",
            array(':CID' => $contextId, ':UID' => $userId, ':ROLE' => LTIX::ROLE_LEARNER)
        );
        $linkKey = $stamp.'-link';
        $pdo->queryDie(
            "INSERT INTO {$p}lti_link
                (link_key, link_sha256, context_id, title, score_maximum, lti13_lineitem,
                 grades_released, created_at, updated_at)
             VALUES (:LK, :SHA, :CID, :TITLE, :MAX, :ITEM, :REL, NOW(), NOW())",
            array(
                ':LK' => $linkKey,
                ':SHA' => U::lti_sha256($linkKey),
                ':CID' => $contextId,
                ':TITLE' => $spec['title'] ?? 'Quiz',
                ':MAX' => $spec['score_maximum'] ?? null,
                ':ITEM' => $spec['lineitem'] ?? null,
                ':REL' => array_key_exists('released', $spec) ? $spec['released'] : null,
            )
        );
        return array(
            'key_id' => $keyId,
            'context_id' => $contextId,
            'user_id' => $userId,
            'link_id' => (int) $pdo->lastInsertId(),
        );
    }
}

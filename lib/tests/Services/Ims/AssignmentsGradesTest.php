<?php

use Tsugi\Core\LTIX;
use Tsugi\Services\Ims\AccessToken;
use Tsugi\Services\Ims\AssignmentsGrades;
use Tsugi\Services\Outbound\PlatformDynamicRegistration;
use Tsugi\Services\Outbound\ToolDeploymentGrant;
use Tsugi\Services\Outbound\ToolRegistrationDocument;
use Tsugi\Util\LTI13;
use Firebase\JWT\JWT;

require_once __DIR__.'/../Org/PlatformSchemaCase.php';

class AssignmentsGradesTest extends PlatformSchemaCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $PDOX;
        if ( $PDOX->metadata($this->p().'lti_tool_registration_token') === false ) {
            $this->markTestSkipped('Registration token table is missing. Run php admin/upgrade.php.');
        }
        if ( ! $PDOX->columnExists('score_given', $this->p().'lti_result')
            || ! $PDOX->columnExists('ags_resource_id', $this->p().'lti_link') ) {
            $this->markTestSkipped('Grade columns are missing. Run php admin/upgrade.php.');
        }
    }

    public function testPath(): void
    {
        $item = AssignmentsGrades::parsePath('/tsugi/ims/ags/context/12/lineitems/9');
        $this->assertIsArray($item);
        $this->assertSame(12, $item['context_id']);
        $this->assertSame(9, $item['link_id']);
        $this->assertSame('item', $item['action']);

        $scores = AssignmentsGrades::parsePath('/lti/ags/context/12/lineitems/9/scores');
        $this->assertIsArray($scores);
        $this->assertSame('scores', $scores['action']);

        $one = AssignmentsGrades::parsePath('/ims/ags/context/12/lineitems/9/results/4/');
        $this->assertIsArray($one);
        $this->assertSame('results', $one['action']);
        $this->assertSame('4', $one['user_id']);

        $this->assertNull(AssignmentsGrades::parsePath('/ims/ags/context/12/lineitems/9/scores/4'));
        $this->assertNull(AssignmentsGrades::parsePath('/ims/ags/context/0/lineitems'));
        $this->assertNull(AssignmentsGrades::parsePath('/ims/nrps/context/12/memberships'));
    }

    public function testPostedScoreIsTheCurrentResult(): void
    {
        $registered = $this->register('Grade Garden');
        $instructor = $this->insertUser('Ada Instructor', 'ada-grade@example.test', LTIX::ROLE_INSTRUCTOR);
        $learner = $this->insertUser('Grace Learner', 'grace-grade@example.test', LTIX::ROLE_LEARNER);
        $gone = $this->insertUser('Gone Student', 'gone-grade@example.test', LTIX::ROLE_LEARNER, 1);
        $keys = $this->keyPair();
        $token = $this->token($registered, $keys, $this->scopes());
        $this->assertSame(200, $token['status']);

        $created = AssignmentsGrades::create($token['body']['access_token'], $this->id['eecs280'], array(
            'scoreMaximum' => 100,
            'label' => 'Chapter 5',
            'resourceId' => 'chapter-5',
            'tag' => 'grade',
            'startDateTime' => '2026-04-16T18:54:36Z',
            'endDateTime' => '2026-05-01T12:00:00Z',
            'gradesReleased' => false,
        ));
        $this->assertSame(201, $created['status']);
        $this->assertIsArray($created['body']);
        $this->assertSame($created['body']['id'], $created['location']);
        $this->assertSame(100.0, $created['body']['scoreMaximum']);
        $this->assertSame('Chapter 5', $created['body']['label']);
        $this->assertSame('chapter-5', $created['body']['resourceId']);
        $this->assertSame('grade', $created['body']['tag']);
        $this->assertFalse($created['body']['gradesReleased']);
        $this->assertSame('2026-04-16T18:54:36Z', $created['body']['startDateTime']);
        $this->assertSame('2026-05-01T12:00:00Z', $created['body']['endDateTime']);
        $this->assertArrayNotHasKey('resourceLinkId', $created['body']);
        $linkId = $this->linkId($created['body']['id']);

        $again = AssignmentsGrades::create($token['body']['access_token'], $this->id['eecs280'], array(
            'scoreMaximum' => 10,
            'label' => 'Progress',
            'tag' => 'progress',
        ));
        $this->assertSame(201, $again['status']);
        $third = AssignmentsGrades::create($token['body']['access_token'], $this->id['eecs280'], array(
            'scoreMaximum' => 5,
            'label' => 'Extra',
        ));
        $this->assertSame(201, $third['status']);

        $page = AssignmentsGrades::listItems($token['body']['access_token'], $this->id['eecs280'], array('limit' => '2'));
        $this->assertSame(200, $page['status']);
        $this->assertCount(2, $page['body']);
        $this->assertIsString($page['next']);
        $this->assertStringContainsString('offset=2', $page['next']);
        $rest = AssignmentsGrades::listItems($token['body']['access_token'], $this->id['eecs280'], array(
            'limit' => '2',
            'offset' => '2',
        ));
        $this->assertCount(1, $rest['body']);
        $this->assertNull($rest['next']);

        $tagged = AssignmentsGrades::listItems($token['body']['access_token'], $this->id['eecs280'], array(
            'tag' => 'grade',
            'resource_id' => 'chapter-5',
        ));
        $this->assertCount(1, $tagged['body']);
        $this->assertSame('Chapter 5', $tagged['body'][0]['label']);

        $this->insertPlainLink();
        $all = AssignmentsGrades::listItems($token['body']['access_token'], $this->id['eecs280'], array());
        $this->assertCount(3, $all['body']);

        $updated = AssignmentsGrades::update($token['body']['access_token'], $this->id['eecs280'], $linkId, array(
            'scoreMaximum' => 50,
            'label' => 'Chapter 5 revised',
            'gradesReleased' => true,
        ));
        $this->assertSame(200, $updated['status']);
        $this->assertSame('Chapter 5 revised', $updated['body']['label']);
        $this->assertSame(50.0, $updated['body']['scoreMaximum']);
        $this->assertTrue($updated['body']['gradesReleased']);
        $this->assertArrayNotHasKey('tag', $updated['body']);
        $this->assertArrayNotHasKey('resourceId', $updated['body']);
        $this->assertArrayNotHasKey('startDateTime', $updated['body']);

        $posted = AssignmentsGrades::score($token['body']['access_token'], $this->id['eecs280'], $linkId, array(
            'userId' => (string) $learner,
            'scoreGiven' => 83,
            'scoreMaximum' => 100,
            'comment' => 'Nice work',
            'activityProgress' => 'Completed',
            'gradingProgress' => 'FullyGraded',
            'timestamp' => '2026-04-16T18:54:36Z',
            'scoringUserId' => (string) $instructor,
        ));
        $this->assertSame(204, $posted['status']);
        $this->assertNull($posted['body']);

        $results = AssignmentsGrades::results($token['body']['access_token'], $this->id['eecs280'], $linkId, array(), null);
        $this->assertSame(200, $results['status']);
        $this->assertCount(1, $results['body']);
        $this->assertSame((string) $learner, $results['body'][0]['userId']);
        $this->assertSame(83.0, $results['body'][0]['resultScore']);
        $this->assertSame(100.0, $results['body'][0]['resultMaximum']);
        $this->assertSame('Nice work', $results['body'][0]['comment']);
        $this->assertSame((string) $instructor, $results['body'][0]['scoringUserId']);
        $this->assertSame(AssignmentsGrades::lineItemUrl($this->id['eecs280'], $linkId), $results['body'][0]['scoreOf']);

        $replaced = AssignmentsGrades::score($token['body']['access_token'], $this->id['eecs280'], $linkId, array(
            'userId' => (string) $learner,
            'scoreGiven' => 7,
            'scoreMaximum' => 10,
            'activityProgress' => 'Submitted',
            'gradingProgress' => 'Pending',
            'timestamp' => '2026-04-17T12:00:00Z',
        ));
        $this->assertSame(204, $replaced['status']);
        $current = AssignmentsGrades::results($token['body']['access_token'], $this->id['eecs280'], $linkId, array(
            'user_id' => (string) $learner,
        ), null);
        $this->assertCount(1, $current['body']);
        $this->assertSame(7.0, $current['body'][0]['resultScore']);
        $this->assertSame(10.0, $current['body'][0]['resultMaximum']);
        $this->assertSame('Nice work', $current['body'][0]['comment']);
        $this->assertSame(1, $this->resultCount($linkId, $learner));
        $stored = $this->resultRow($linkId, $learner);
        $this->assertEqualsWithDelta(0.7, (float) $stored['grade'], 0.00001);
        $this->assertSame('Pending', $stored['grading_progress']);
        $this->assertSame('Submitted', $stored['activity_progress']);

        $cleared = AssignmentsGrades::score($token['body']['access_token'], $this->id['eecs280'], $linkId, array(
            'userId' => (string) $learner,
            'scoreGiven' => null,
            'activityProgress' => 'Started',
            'gradingProgress' => 'NotReady',
            'timestamp' => '2026-04-18T12:00:00Z',
        ));
        $this->assertSame(204, $cleared['status']);
        $empty = AssignmentsGrades::results($token['body']['access_token'], $this->id['eecs280'], $linkId, array(), null);
        $this->assertSame(array(), $empty['body']);
        $clearedRow = $this->resultRow($linkId, $learner);
        $this->assertNull($clearedRow['grade']);
        $this->assertNull($clearedRow['score_given']);
        $this->assertSame('NotReady', $clearedRow['grading_progress']);
        $this->assertSame(1, $this->resultCount($linkId, $learner));

        $this->assertSame(400, AssignmentsGrades::score($token['body']['access_token'], $this->id['eecs280'], $linkId, array(
            'userId' => (string) $gone,
            'scoreGiven' => 1,
            'scoreMaximum' => 1,
            'activityProgress' => 'Completed',
            'gradingProgress' => 'FullyGraded',
            'timestamp' => '2026-04-18T12:00:00Z',
        ))['status']);
        $this->assertSame(400, AssignmentsGrades::score($token['body']['access_token'], $this->id['eecs280'], $linkId, array(
            'userId' => (string) $learner,
            'activityProgress' => 'Completed',
            'gradingProgress' => 'FullyGraded',
            'timestamp' => '2026-04-18T12:00:00Z',
        ))['status']);
        $this->assertSame(400, AssignmentsGrades::score($token['body']['access_token'], $this->id['eecs280'], $linkId, array(
            'userId' => (string) $learner,
            'scoreGiven' => -1,
            'scoreMaximum' => 10,
            'activityProgress' => 'Completed',
            'gradingProgress' => 'FullyGraded',
            'timestamp' => '2026-04-18T12:00:00Z',
        ))['status']);

        $removed = AssignmentsGrades::delete($token['body']['access_token'], $this->id['eecs280'], $linkId);
        $this->assertSame(204, $removed['status']);
        $missing = AssignmentsGrades::readItem($token['body']['access_token'], $this->id['eecs280'], $linkId);
        $this->assertSame(404, $missing['status']);
    }

    public function testResourceLinkKeepsOneColumnAndExtraColumns(): void
    {
        $registered = $this->register('Column Garden');
        $learner = $this->insertUser('Grace Learner', 'grace-column@example.test', LTIX::ROLE_LEARNER);
        $keys = $this->keyPair();
        $token = $this->token($registered, $keys, $this->scopes());
        $resourceLinkId = 'lti_garden_column';
        $this->insertContent($registered, $resourceLinkId);
        $this->assertNull(AssignmentsGrades::coupledLineItemUrl($this->id['eecs280'], $resourceLinkId));

        $unknown = AssignmentsGrades::create($token['body']['access_token'], $this->id['eecs280'], array(
            'scoreMaximum' => 10,
            'label' => 'Missing',
            'resourceLinkId' => 'not-a-launch',
        ));
        $this->assertSame(400, $unknown['status']);

        $coupled = AssignmentsGrades::create($token['body']['access_token'], $this->id['eecs280'], array(
            'scoreMaximum' => 20,
            'label' => 'Garden grade',
            'resourceLinkId' => $resourceLinkId,
            'tag' => 'grade',
        ));
        $this->assertSame(201, $coupled['status']);
        $this->assertSame($resourceLinkId, $coupled['body']['resourceLinkId']);
        $this->assertSame(
            $coupled['body']['id'],
            AssignmentsGrades::coupledLineItemUrl($this->id['eecs280'], $resourceLinkId)
        );

        $progress = AssignmentsGrades::create($token['body']['access_token'], $this->id['eecs280'], array(
            'scoreMaximum' => 1,
            'label' => 'Garden progress',
            'resourceLinkId' => $resourceLinkId,
            'tag' => 'progress',
        ));
        $this->assertSame(201, $progress['status']);
        $this->assertNotSame($coupled['body']['id'], $progress['body']['id']);
        $this->assertSame($resourceLinkId, $progress['body']['resourceLinkId']);
        $this->assertSame(
            $coupled['body']['id'],
            AssignmentsGrades::coupledLineItemUrl($this->id['eecs280'], $resourceLinkId)
        );

        $both = AssignmentsGrades::listItems($token['body']['access_token'], $this->id['eecs280'], array(
            'resource_link_id' => $resourceLinkId,
        ));
        $this->assertCount(2, $both['body']);

        $gradeId = $this->linkId($coupled['body']['id']);
        $progressId = $this->linkId($progress['body']['id']);
        $this->assertSame(204, AssignmentsGrades::score($token['body']['access_token'], $this->id['eecs280'], $gradeId, array(
            'userId' => (string) $learner,
            'scoreGiven' => 18,
            'scoreMaximum' => 20,
            'activityProgress' => 'Completed',
            'gradingProgress' => 'FullyGraded',
            'timestamp' => '2026-04-16T18:54:36Z',
        ))['status']);
        $gradeResults = AssignmentsGrades::results($token['body']['access_token'], $this->id['eecs280'], $gradeId, array(), null);
        $progressResults = AssignmentsGrades::results($token['body']['access_token'], $this->id['eecs280'], $progressId, array(), null);
        $this->assertSame(18.0, $gradeResults['body'][0]['resultScore']);
        $this->assertSame(array(), $progressResults['body']);

        $changed = AssignmentsGrades::update($token['body']['access_token'], $this->id['eecs280'], $gradeId, array(
            'scoreMaximum' => 20,
            'label' => 'Garden grade',
            'resourceLinkId' => 'someone-elses-link',
        ));
        $this->assertSame(400, $changed['status']);

        AssignmentsGrades::delete($token['body']['access_token'], $this->id['eecs280'], $gradeId);
        $this->assertNull(AssignmentsGrades::coupledLineItemUrl($this->id['eecs280'], $resourceLinkId));
        global $PDOX;
        $content = $PDOX->rowDie(
            "SELECT link_id FROM {$this->p()}lti_content
             WHERE context_id = :context_id AND resource_link_sha256 = :sha",
            array(
                ':context_id' => $this->id['eecs280'],
                ':sha' => hash('sha256', $resourceLinkId),
            )
        );
        $this->assertIsArray($content);
        $this->assertNull($content['link_id']);
    }

    public function testScopesFollowTheDeployment(): void
    {
        $registered = $this->register('Scope Garden');
        $learner = $this->insertUser('Grace Learner', 'grace-scope@example.test', LTIX::ROLE_LEARNER);
        $keys = $this->keyPair();
        $full = $this->token($registered, $keys, $this->scopes());
        $created = AssignmentsGrades::create($full['body']['access_token'], $this->id['eecs280'], array(
            'scoreMaximum' => 10,
            'label' => 'Scoped',
        ));
        $this->assertSame(201, $created['status']);
        $linkId = $this->linkId($created['body']['id']);

        $scoreOnly = $this->token($registered, $keys, ToolRegistrationDocument::SCOPE_SCORE);
        $this->assertSame(403, AssignmentsGrades::listItems($scoreOnly['body']['access_token'], $this->id['eecs280'], array())['status']);
        $this->assertSame('insufficient_scope', AssignmentsGrades::create($scoreOnly['body']['access_token'], $this->id['eecs280'], array(
            'scoreMaximum' => 1,
            'label' => 'Nope',
        ))['body']['error']);
        $this->assertSame(204, AssignmentsGrades::score($scoreOnly['body']['access_token'], $this->id['eecs280'], $linkId, array(
            'userId' => (string) $learner,
            'scoreGiven' => 4,
            'scoreMaximum' => 10,
            'activityProgress' => 'Completed',
            'gradingProgress' => 'FullyGraded',
            'timestamp' => '2026-04-16T18:54:36Z',
        ))['status']);

        $resultsOnly = $this->token($registered, $keys, ToolRegistrationDocument::SCOPE_RESULT);
        $read = AssignmentsGrades::results($resultsOnly['body']['access_token'], $this->id['eecs280'], $linkId, array(), null);
        $this->assertSame(200, $read['status']);
        $this->assertSame(4.0, $read['body'][0]['resultScore']);
        $this->assertSame(403, AssignmentsGrades::score($resultsOnly['body']['access_token'], $this->id['eecs280'], $linkId, array(
            'userId' => (string) $learner,
            'scoreGiven' => 1,
            'scoreMaximum' => 10,
            'activityProgress' => 'Completed',
            'gradingProgress' => 'FullyGraded',
            'timestamp' => '2026-04-16T18:54:36Z',
        ))['status']);

        $readonly = $this->token($registered, $keys, ToolRegistrationDocument::SCOPE_LINEITEM_READONLY);
        $this->assertSame(200, AssignmentsGrades::readItem($readonly['body']['access_token'], $this->id['eecs280'], $linkId)['status']);
        $this->assertSame(403, AssignmentsGrades::update($readonly['body']['access_token'], $this->id['eecs280'], $linkId, array(
            'scoreMaximum' => 10,
            'label' => 'Scoped',
        ))['status']);

        $other = $this->register('Other Garden');
        $otherToken = $this->token($other, $keys, $this->scopes());
        $hidden = AssignmentsGrades::readItem($otherToken['body']['access_token'], $this->id['eecs280'], $linkId);
        $this->assertSame(404, $hidden['status']);
        $this->assertSame(array(), AssignmentsGrades::listItems($otherToken['body']['access_token'], $this->id['eecs280'], array())['body']);

        $elsewhere = AssignmentsGrades::listItems($full['body']['access_token'], $this->id['eecs281'], array());
        $this->assertSame(403, $elsewhere['status']);
        $this->assertSame('not_allowed', $elsewhere['body']['error']);

        ToolDeploymentGrant::blockScope($registered['tool_deployment_id'], ToolRegistrationDocument::SCOPE_SCORE);
        $revoked = AssignmentsGrades::score($scoreOnly['body']['access_token'], $this->id['eecs280'], $linkId, array(
            'userId' => (string) $learner,
            'scoreGiven' => 9,
            'scoreMaximum' => 10,
            'activityProgress' => 'Completed',
            'gradingProgress' => 'FullyGraded',
            'timestamp' => '2026-04-16T18:54:36Z',
        ));
        $this->assertSame(403, $revoked['status']);
        $this->assertSame('not_allowed', $revoked['body']['error']);
        $still = AssignmentsGrades::results($resultsOnly['body']['access_token'], $this->id['eecs280'], $linkId, array(), null);
        $this->assertSame(4.0, $still['body'][0]['resultScore']);
    }

    private function scopes(): string
    {
        return implode(' ', array(
            ToolRegistrationDocument::SCOPE_SCORE,
            ToolRegistrationDocument::SCOPE_LINEITEM,
            ToolRegistrationDocument::SCOPE_LINEITEM_READONLY,
            ToolRegistrationDocument::SCOPE_RESULT,
        ));
    }

    private function linkId(string $url): int
    {
        $path = parse_url($url, PHP_URL_PATH);
        $this->assertIsString($path);
        $parsed = AssignmentsGrades::parsePath($path);
        $this->assertIsArray($parsed);
        $this->assertIsInt($parsed['link_id']);
        return $parsed['link_id'];
    }

    private function resultCount(int $linkId, int $userId): int
    {
        global $PDOX;
        $row = $PDOX->rowDie(
            "SELECT COUNT(*) AS n FROM {$this->p()}lti_result
             WHERE link_id = :link_id AND user_id = :user_id",
            array(
                ':link_id' => $linkId,
                ':user_id' => $userId,
            )
        );
        $this->assertIsArray($row);
        return (int) $row['n'];
    }

    /**
     * @return array<string, mixed>
     */
    private function resultRow(int $linkId, int $userId): array
    {
        global $PDOX;
        $row = $PDOX->rowDie(
            "SELECT grade, score_given, grading_progress, activity_progress, comment
             FROM {$this->p()}lti_result
             WHERE link_id = :link_id AND user_id = :user_id",
            array(
                ':link_id' => $linkId,
                ':user_id' => $userId,
            )
        );
        $this->assertIsArray($row);
        return $row;
    }

    private function insertPlainLink(): void
    {
        global $PDOX;
        $key = 'plain-'.bin2hex(random_bytes(4));
        $PDOX->queryDie(
            "INSERT INTO {$this->p()}lti_link
                (link_key, link_sha256, context_id, title, created_at, updated_at)
             VALUES (:link_key, :sha, :context_id, 'Quiz', NOW(), NOW())",
            array(
                ':link_key' => $key,
                ':sha' => hash('sha256', $key),
                ':context_id' => $this->id['eecs280'],
            )
        );
    }

    /**
     * @param array{client_id:string, deployment_id:string, tool_deployment_id:int} $registered
     */
    private function insertContent(array $registered, string $resourceLinkId): void
    {
        global $PDOX;
        $PDOX->queryDie(
            "INSERT INTO {$this->p()}lti_content
                (context_id, key_id, tool_deployment_id, title, launch_url, target,
                 resource_link_id, resource_link_sha256, published, created_at)
             VALUES
                (:context_id, :key_id, :tool_deployment_id, 'Garden', 'https://client.example.org/launch', 'window',
                 :resource_link_id, :sha, 1, NOW())",
            array(
                ':context_id' => $this->id['eecs280'],
                ':key_id' => $this->id['keyA'],
                ':tool_deployment_id' => $registered['tool_deployment_id'],
                ':resource_link_id' => $resourceLinkId,
                ':sha' => hash('sha256', $resourceLinkId),
            )
        );
    }

    /**
     * @return array{client_id:string, deployment_id:string, tool_deployment_id:int}
     */
    private function register(string $title): array
    {
        global $PDOX;
        $token = PlatformDynamicRegistration::startForCourse(
            $this->id['eecs280'],
            null,
            'https://client.example.org/register'
        );
        $raw = json_encode($this->payload($title));
        $this->assertIsString($raw);
        $stored = PlatformDynamicRegistration::accept('Bearer '.$token, $raw, 'application/json');
        $this->assertSame(200, $stored['status']);
        $row = $PDOX->rowDie(
            "SELECT d.tool_deployment_id, d.deployment_id
             FROM {$this->p()}lti_tool_registration r
             JOIN {$this->p()}lti_tool_deployment d ON d.registration_id = r.registration_id
             WHERE r.title = :title AND r.key_id = :key_id",
            array(
                ':title' => $title,
                ':key_id' => $this->id['keyA'],
            )
        );
        $this->assertIsArray($row);
        return array(
            'client_id' => (string) $stored['document']['client_id'],
            'deployment_id' => (string) $row['deployment_id'],
            'tool_deployment_id' => (int) $row['tool_deployment_id'],
        );
    }

    /**
     * @param array{client_id:string, deployment_id:string, tool_deployment_id:int} $registered
     * @param array{private:string, jwks:array<string, mixed>} $keys
     * @return array{status:int, body:array<string, mixed>}
     */
    private function token(array $registered, array $keys, string $scope): array
    {
        $audience = PlatformDynamicRegistration::openIdConfiguration()['token_endpoint'];
        return AccessToken::grant(array(
            'grant_type' => 'client_credentials',
            'client_assertion_type' => AccessToken::ASSERTION_TYPE,
            'client_assertion' => $this->assertion($registered['client_id'], $keys, $audience),
            'scope' => $scope,
        ), AccessToken::audiences(), $keys['jwks']);
    }

    /**
     * @param array{private:string, jwks:array<string, mixed>} $keys
     */
    private function assertion(string $clientId, array $keys, string $audience): string
    {
        $now = time();
        return LTI13::encode_jwt(array(
            'iss' => $clientId,
            'sub' => $clientId,
            'aud' => $audience,
            'iat' => $now,
            'exp' => $now + 60,
            'jti' => bin2hex(random_bytes(8)),
        ), $keys['private'], 'test-key');
    }

    /**
     * @return array{private:string, jwks:array<string, mixed>}
     */
    private function keyPair(): array
    {
        $public = null;
        $private = null;
        $ok = LTI13::generatePKCS8Pair($public, $private);
        $this->assertTrue($ok === true);
        $this->assertIsString($public);
        $this->assertIsString($private);
        $opened = openssl_pkey_get_public($public);
        $this->assertNotFalse($opened);
        $details = openssl_pkey_get_details($opened);
        $this->assertIsArray($details);
        return array(
            'private' => $private,
            'jwks' => array(
                'keys' => array(
                    array(
                        'kty' => 'RSA',
                        'alg' => 'RS256',
                        'use' => 'sig',
                        'kid' => 'test-key',
                        'n' => JWT::urlsafeB64Encode($details['rsa']['n']),
                        'e' => JWT::urlsafeB64Encode($details['rsa']['e']),
                    ),
                ),
            ),
        );
    }

    private function insertUser(string $name, string $email, int $role, int $deleted = 0): int
    {
        global $PDOX;
        $userKey = bin2hex(random_bytes(8));
        $PDOX->queryDie(
            "INSERT INTO {$this->p()}lti_user
                (user_key, user_sha256, displayname, email, key_id, created_at, updated_at)
             VALUES (:user_key, :sha, :name, :email, :key_id, NOW(), NOW())",
            array(
                ':user_key' => $userKey,
                ':sha' => hash('sha256', $userKey),
                ':name' => $name,
                ':email' => $email,
                ':key_id' => $this->id['keyA'],
            )
        );
        $userId = (int) $PDOX->lastInsertId();
        $PDOX->queryDie(
            "INSERT INTO {$this->p()}lti_membership
                (context_id, user_id, role, deleted, created_at, updated_at)
             VALUES (:context_id, :user_id, :role, :deleted, NOW(), NOW())",
            array(
                ':context_id' => $this->id['eecs280'],
                ':user_id' => $userId,
                ':role' => $role,
                ':deleted' => $deleted,
            )
        );
        return $userId;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $title): array
    {
        return array(
            'application_type' => 'web',
            'initiate_login_uri' => 'https://client.example.org/lti',
            'redirect_uris' => array('https://client.example.org/callback'),
            'client_name' => $title,
            'jwks_uri' => 'https://client.example.org/jwks.json',
            'scope' => $this->scopes(),
            ToolRegistrationDocument::TOOL_CONFIGURATION => array(
                'domain' => 'client.example.org',
                'target_link_uri' => 'https://client.example.org/launch',
                'claims' => array('iss', 'sub', 'name', 'email'),
                'messages' => array(
                    array(
                        'type' => 'LtiResourceLinkRequest',
                        'target_link_uri' => 'https://client.example.org/resource',
                    ),
                ),
            ),
        );
    }
}

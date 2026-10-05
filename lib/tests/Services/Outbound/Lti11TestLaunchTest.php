<?php

use Tsugi\Core\Result;
use Tsugi\Util\U;
use Tsugi\Services\Outbound\Lti11CourseTool;
use Tsugi\Services\Outbound\Lti11TestLaunch;
use Tsugi\Services\Outbound\ToolDeploymentService;
use Tsugi\Services\Outbound\ToolRegistrationService;

require_once __DIR__.'/../Org/PlatformSchemaCase.php';

class Lti11TestLaunchTest extends PlatformSchemaCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $PDOX;
        $p = $this->p();
        if ( $PDOX->metadata($p.'lti_tool_message_placement') === false
            || $PDOX->metadata($p.'lti_tool_deployment_claim') === false ) {
            $this->markTestSkipped('Outbound tool tables are missing. Run php admin/upgrade.php.');
        }
    }

    public function testResourceLinkIsSignedAndTheOtherLaunchesWait(): void
    {
        $registrationId = Lti11CourseTool::addToCourse($this->id['eecs280'], 0, $this->post());
        $userId = $this->insertInstructor();
        $listed = Lti11CourseTool::toolsOnCourse($this->id['eecs280']);
        $this->assertTrue($listed[0]['can_test']);

        $choices = Lti11TestLaunch::choices($this->id['eecs280'], $registrationId);
        $this->assertSame(
            array('LtiResourceLinkRequest', 'LtiDeepLinkingRequest'),
            array_column($choices, 'type')
        );
        $this->assertTrue($choices[0]['ready']);
        $this->assertFalse($choices[1]['ready']);
        $this->assertSame('LtiResourceLinkRequest', Lti11TestLaunch::defaultType($choices));

        $launch = Lti11TestLaunch::launch(
            $this->id['eecs280'],
            $registrationId,
            $userId,
            'LtiResourceLinkRequest',
            'https://local.dj4e.com/tsugi/return'
        );
        $this->assertTrue($launch['ready']);
        $this->assertSame('https://tool.example/launch', $launch['endpoint']);
        $parms = $launch['parameters'];
        $this->assertSame('basic-lti-launch-request', $parms['lti_message_type']);
        $this->assertSame('LTI-1p0', $parms['lti_version']);
        $this->assertSame('test-'.$registrationId, $parms['resource_link_id']);
        $this->assertSame('External quiz', $parms['resource_link_title']);
        $this->assertSame('Instructor', $parms['roles']);
        $this->assertSame('Instructor', $launch['role']);
        $learner = Lti11TestLaunch::launch(
            $this->id['eecs280'],
            $registrationId,
            $userId,
            'LtiResourceLinkRequest',
            'https://local.dj4e.com/tsugi/return',
            'Learner'
        );
        $this->assertSame('Learner', $learner['parameters']['roles']);
        try {
            Lti11TestLaunch::launch($this->id['eecs280'], $registrationId, $userId, 'LtiResourceLinkRequest', '', 'Administrator');
            $this->fail('Expected an unknown role to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('role', $ex->getMessage());
        }
        $this->assertSame((string) $userId, $parms['user_id']);
        $this->assertSame('Pat Instructor', $parms['lis_person_name_full']);
        $this->assertSame('Pat', $parms['lis_person_name_given']);
        $this->assertSame('Instructor', $parms['lis_person_name_family']);
        $this->assertSame('pat@example.test', $parms['lis_person_contact_email_primary']);
        $this->assertSame('https://local.dj4e.com/tsugi/return', $parms['launch_presentation_return_url']);
        $this->assertArrayHasKey('oauth_signature', $parms);
        $this->assertArrayNotHasKey('lti11_secret', $parms);

        global $PDOX;
        $p = $this->p();
        $key = $PDOX->rowDie(
            "SELECT key_key FROM {$p}lti_key WHERE key_id = :key_id",
            array(':key_id' => $this->id['keyA'])
        );
        $stored = Lti11CourseTool::formState($this->id['eecs280'], $registrationId);
        $this->assertSame((string) $this->id['eecs280'], $parms['context_id']);
        $this->assertSame('EECS 280', $parms['context_title']);
        $this->assertSame($key['key_key'], $parms['tool_consumer_instance_guid']);
        $this->assertSame($stored['lti11_key'], $parms['oauth_consumer_key']);

        $content = Lti11TestLaunch::launch(
            $this->id['eecs280'],
            $registrationId,
            $userId,
            'LtiDeepLinkingRequest',
            'https://local.dj4e.com/tsugi/return'
        );
        $this->assertFalse($content['ready']);
        $this->assertSame('Content item', $content['label']);
        $this->assertSame(array(), $content['parameters']);

        try {
            Lti11TestLaunch::launch($this->id['eecs281'], $registrationId, $userId, 'LtiResourceLinkRequest', '');
            $this->fail('Expected another course to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('does not have that tool', $ex->getMessage());
        }
    }

    public function testNamesAndEmailFollowTheGrant(): void
    {
        $post = $this->post();
        $post['privacy'] = array();
        $registrationId = Lti11CourseTool::addToCourse($this->id['eecs280'], 0, $post);
        $deploymentId = ToolDeploymentService::onlyDeploymentId($registrationId);
        $launch = Lti11TestLaunch::launch(
            $this->id['eecs280'],
            $registrationId,
            $this->insertInstructor(),
            'LtiResourceLinkRequest',
            ''
        );
        $this->assertArrayNotHasKey('lis_person_name_full', $launch['parameters']);
        $this->assertArrayNotHasKey('lis_person_contact_email_primary', $launch['parameters']);
        $this->assertArrayNotHasKey('launch_presentation_return_url', $launch['parameters']);
        $this->insertLessonLink($this->id['eecs280'], 'lti_private', 'Private');
        $asked = Lti11TestLaunch::courseResourceLink(
            $this->id['eecs280'],
            $deploymentId,
            $this->insertInstructor(),
            'lti_private',
            'Private',
            '',
            'Learner',
            'lms-user-2',
            true,
            true
        );
        $this->assertArrayNotHasKey('lis_person_name_full', $asked['parameters']);
        $this->assertArrayNotHasKey('lis_person_contact_email_primary', $asked['parameters']);
        $privacy = Lti11TestLaunch::privacy($this->id['eecs280'], $deploymentId);
        $this->assertFalse($privacy['send_name']);
        $this->assertFalse($privacy['send_email']);
    }

    public function testASharedToolCanBeTested(): void
    {
        $shared = ToolRegistrationService::createRegistration($this->id['keyA'], 'Shared quiz', $this->id['csA'], null, array(
            'lti_version' => '1.1',
            'lti11_key' => 'shared-key-'.bin2hex(random_bytes(4)),
            'lti11_secret' => 'shared-secret',
            'lti11_url' => 'https://tool.example/shared',
        ));
        ToolDeploymentService::assignContext(ToolDeploymentService::onlyDeploymentId($shared), $this->id['eecs280']);
        $listed = array();
        foreach ( Lti11CourseTool::toolsOnCourse($this->id['eecs280']) as $tool ) {
            $listed[$tool['title']] = $tool['can_test'];
        }
        $this->assertTrue($listed['Shared quiz']);
        $launch = Lti11TestLaunch::launch(
            $this->id['eecs280'],
            $shared,
            $this->insertInstructor(),
            'LtiResourceLinkRequest',
            ''
        );
        $this->assertTrue($launch['ready']);
        $this->assertSame('basic-lti-launch-request', $launch['parameters']['lti_message_type']);
        $this->assertSame('https://tool.example/shared', $launch['endpoint']);
    }

    public function testLessonLinkLaunchesTheCourseTool(): void
    {
        $registrationId = Lti11CourseTool::addToCourse($this->id['eecs280'], 0, $this->post());
        $deploymentId = ToolDeploymentService::onlyDeploymentId($registrationId);
        $userId = $this->insertInstructor();
        $this->assertTrue(Lti11TestLaunch::hasResourceLink($this->id['eecs280'], $deploymentId));
        $this->assertFalse(Lti11TestLaunch::hasResourceLink($this->id['eecs281'], $deploymentId));
        $choiceIds = array();
        foreach ( Lti11TestLaunch::lessonChoices($this->id['eecs280']) as $choice ) {
            $choiceIds[] = $choice['id'];
            if ( $choice['id'] === $deploymentId ) {
                $this->assertSame('1.1', $choice['lti_version']);
                $this->assertTrue($choice['send_grade']);
            }
        }
        $this->assertContains($deploymentId, $choiceIds);

        $this->insertLessonLink($this->id['eecs280'], 'lti_week_1', 'Week 1 quiz');
        $launch = Lti11TestLaunch::courseResourceLink(
            $this->id['eecs280'],
            $deploymentId,
            $userId,
            'lti_week_1',
            'Week 1 quiz',
            'https://local.dj4e.com/tsugi/lessons/return',
            'Learner',
            'lms-user-1'
        );
        $this->assertSame('https://tool.example/launch', $launch['endpoint']);
        $imported = Lti11TestLaunch::courseResourceLink(
            $this->id['eecs280'],
            $deploymentId,
            $userId,
            'lti_week_1',
            'Week 1 quiz',
            'https://local.dj4e.com/tsugi/lessons/return',
            'Learner',
            'lms-user-1',
            null,
            null,
            'https://imported.example/launch'
        );
        $this->assertSame('https://imported.example/launch', $imported['endpoint']);
        $this->assertSame($launch['parameters']['oauth_consumer_key'], $imported['parameters']['oauth_consumer_key']);
        $this->assertArrayNotHasKey('launch_presentation_document_target', $launch['parameters']);
        $embedded = Lti11TestLaunch::courseResourceLink(
            $this->id['eecs280'],
            $deploymentId,
            $userId,
            'lti_week_1',
            'Week 1 quiz',
            'https://local.dj4e.com/tsugi/lessons/return',
            'Learner',
            'lms-user-1',
            null,
            null,
            'https://imported.example/launch',
            'iframe'
        );
        $this->assertSame('iframe', $embedded['parameters']['launch_presentation_document_target']);
        $frameId = Lti11TestLaunch::parentFrameId('lti_week_1');
        $resized = Lti11TestLaunch::courseResourceLink(
            $this->id['eecs280'],
            $deploymentId,
            $userId,
            'lti_week_1',
            'Week 1 quiz',
            'https://local.dj4e.com/tsugi/lessons/return',
            'Learner',
            'lms-user-1',
            null,
            null,
            'https://imported.example/launch',
            'iframe',
            $frameId
        );
        $this->assertTrue(Lti11TestLaunch::embedsInline(''));
        $this->assertTrue(Lti11TestLaunch::embedsInline('iframe'));
        $this->assertFalse(Lti11TestLaunch::embedsInline('modal'));
        $this->assertSame($frameId, $resized['parameters']['ext_lti_element_id']);
        $this->assertNotSame($frameId, $launch['parameters']['ext_lti_element_id']);
        $this->assertSame('window', Lti11TestLaunch::documentTargetForLesson('_blank'));
        $this->assertSame('window', Lti11TestLaunch::documentTargetForLesson('_self'));
        $parms = $launch['parameters'];
        $this->assertSame('basic-lti-launch-request', $parms['lti_message_type']);
        $this->assertSame('lti_week_1', $parms['resource_link_id']);
        $this->assertSame('Week 1 quiz', $parms['resource_link_title']);
        $this->assertSame('Learner', $parms['roles']);
        $this->assertSame((string) $userId, $parms['user_id']);
        $this->assertArrayNotHasKey('resource_link_description', $parms);
        $this->assertSame('Pat Instructor', $parms['lis_person_name_full']);
        $this->assertSame('pat@example.test', $parms['lis_person_contact_email_primary']);
        $this->assertStringEndsWith('/api/poxresult.php', $parms['lis_outcome_service_url']);
        $sourced = explode('::', $parms['lis_result_sourcedid']);
        $this->assertCount(5, $sourced);
        $this->assertSame((string) $this->id['keyA'], $sourced[0]);
        $this->assertSame((string) $this->id['eecs280'], $sourced[1]);
        $withheld = Lti11TestLaunch::courseResourceLink(
            $this->id['eecs280'],
            $deploymentId,
            $userId,
            'lti_week_1',
            'Week 1 quiz',
            'https://local.dj4e.com/tsugi/lessons/return',
            'Learner',
            'lms-user-1',
            null,
            null,
            '',
            '',
            '',
            false
        );
        $this->assertArrayNotHasKey('lis_outcome_service_url', $withheld['parameters']);
        $this->assertArrayNotHasKey('lis_result_sourcedid', $withheld['parameters']);

        $namesOnly = Lti11TestLaunch::courseResourceLink(
            $this->id['eecs280'],
            $deploymentId,
            $userId,
            'lti_week_1',
            'Week 1 quiz',
            'https://local.dj4e.com/tsugi/lessons/return',
            'Learner',
            'lms-user-1',
            true,
            false
        );
        $this->assertSame('Pat Instructor', $namesOnly['parameters']['lis_person_name_full']);
        $this->assertArrayNotHasKey('lis_person_contact_email_primary', $namesOnly['parameters']);

        $private = Lti11TestLaunch::courseResourceLink(
            $this->id['eecs280'],
            $deploymentId,
            $userId,
            'lti_week_1',
            'Week 1 quiz',
            '',
            'Learner',
            'lms-user-1',
            false,
            false
        );
        $this->assertArrayNotHasKey('lis_person_name_full', $private['parameters']);
        $this->assertArrayNotHasKey('lis_person_name_given', $private['parameters']);
        $this->assertArrayNotHasKey('lis_person_contact_email_primary', $private['parameters']);

        $privacy = Lti11TestLaunch::privacy($this->id['eecs280'], $deploymentId);
        $this->assertTrue($privacy['send_name']);
        $this->assertTrue($privacy['send_email']);
        $stored = Lti11CourseTool::formState($this->id['eecs280'], $registrationId);
        $this->assertSame($stored['lti11_key'], $parms['oauth_consumer_key']);
        $this->assertArrayNotHasKey('lti11_secret', $parms);

        $doc = \Tsugi\Services\Lessons\LessonsNormalize::normalizeDocument(array(
            'modules' => array(array(
                'title' => 'Week',
                'items' => array(array(
                    'type' => 'lti',
                    'title' => 'Quiz',
                    'registration_id' => '9',
                    'tool_deployment_id' => '12',
                    'launch' => 'https://old.example/launch',
                    'resource_link_id' => 'lti_quiz',
                    'target' => '_blank',
                    'send_name' => true,
                    'send_email' => 0,
                    'send_grade' => 1,
                )),
            )),
        ));
        $item = $doc['modules'][0]['items'][0];
        $this->assertSame(12, $item['tool_deployment_id']);
        $this->assertArrayNotHasKey('registration_id', $item);
        $this->assertSame('lti_quiz', $item['resource_link_id']);
        $this->assertSame('_blank', $item['target']);
        $this->assertTrue($item['send_name']);
        $this->assertFalse($item['send_email']);
        $this->assertTrue($item['send_grade']);
        $this->assertSame('https://old.example/launch', $item['launch']);

        $contentOnly = $this->post();
        $contentOnly['title'] = 'Picker only';
        $contentOnly['messages'] = array('LtiDeepLinkingRequest');
        $contentId = Lti11CourseTool::addToCourse($this->id['eecs280'], 0, $contentOnly);
        $contentDeploymentId = ToolDeploymentService::onlyDeploymentId($contentId);
        $this->assertFalse(Lti11TestLaunch::hasResourceLink($this->id['eecs280'], $contentDeploymentId));
        try {
            Lti11TestLaunch::courseResourceLink(
                $this->id['eecs280'],
                $contentDeploymentId,
                $userId,
                'lti_picker',
                'Picker',
                '',
                'Learner',
                'lms-user-1'
            );
            $this->fail('Expected a content item tool to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('resource link', $ex->getMessage());
        }
    }

    public function testADeploymentWithoutScoreDoesNotSendAGradeCallback(): void
    {
        $post = $this->post();
        $post['services'] = array();
        $registrationId = Lti11CourseTool::addToCourse($this->id['eecs280'], 0, $post);
        $deploymentId = ToolDeploymentService::onlyDeploymentId($registrationId);
        $choices = Lti11TestLaunch::lessonChoices($this->id['eecs280']);
        $matched = null;
        foreach ( $choices as $choice ) {
            if ( $choice['id'] === $deploymentId ) {
                $matched = $choice;
            }
        }
        $this->assertNotNull($matched);
        $this->assertFalse($matched['send_grade']);
        $launch = Lti11TestLaunch::courseResourceLink(
            $this->id['eecs280'],
            $deploymentId,
            $this->insertInstructor(),
            'lti_no_grade',
            'No grade',
            'https://local.dj4e.com/tsugi/lessons/return',
            'Learner',
            'lms-user-2'
        );
        $this->assertArrayNotHasKey('lis_outcome_service_url', $launch['parameters']);
        $this->assertArrayNotHasKey('lis_result_sourcedid', $launch['parameters']);
    }

    public function testAGradeCallbackStoresTheScoreWithoutALaunch(): void
    {
        global $LINK, $PDOX;
        $registrationId = Lti11CourseTool::addToCourse($this->id['eecs280'], 0, $this->post());
        $deploymentId = ToolDeploymentService::onlyDeploymentId($registrationId);
        $userId = $this->insertInstructor();
        $this->insertLessonLink($this->id['eecs280'], 'lti_graded', 'Graded quiz');
        $launch = Lti11TestLaunch::courseResourceLink(
            $this->id['eecs280'],
            $deploymentId,
            $userId,
            'lti_graded',
            'Graded quiz',
            'https://local.dj4e.com/tsugi/lessons/return',
            'Learner',
            'lms-user-3'
        );
        $sourced = explode('::', $launch['parameters']['lis_result_sourcedid']);
        $this->assertCount(5, $sourced);
        $p = $this->p();
        $row = $PDOX->rowDie(
            "SELECT K.secret, K.key_key, R.result_id, R.grade, R.sourcedid, R.result_url, S.service_key AS service
             FROM {$p}lti_key AS K
             JOIN {$p}lti_context AS C ON K.key_id = C.key_id
             JOIN {$p}lti_link AS L ON C.context_id = L.context_id
             JOIN {$p}lti_result AS R ON L.link_id = R.link_id
             LEFT JOIN {$p}lti_service AS S ON S.service_id = R.service_id
             WHERE R.result_id = :result_id",
            array(':result_id' => (int) $sourced[3])
        );
        $LINK = false;
        $debug = array();
        $status = Result::gradeSendStatic('0.42', $row, $debug);
        $this->assertTrue($status);
        $stored = $PDOX->rowDie(
            "SELECT grade FROM {$p}lti_result WHERE result_id = :result_id",
            array(':result_id' => (int) $sourced[3])
        );
        $this->assertEqualsWithDelta(0.42, (float) $stored['grade'], 0.0001);
    }

    /**
     * @return array<string, mixed>
     */
    private function post(): array
    {
        return array(
            'title' => 'External quiz',
            'lti11_url' => 'https://tool.example/launch',
            'lti11_key' => 'course-key-'.bin2hex(random_bytes(4)),
            'lti11_secret' => 'course-secret',
            'messages' => array('LtiResourceLinkRequest', 'LtiDeepLinkingRequest'),
            'placements' => array('lessons'),
            'privacy' => array('names', 'email'),
            'services' => array('score'),
        );
    }

    public function testAGradedLaunchWithoutALinkIsRefused(): void
    {
        $registrationId = Lti11CourseTool::addToCourse($this->id['eecs280'], 0, $this->post());
        $deploymentId = ToolDeploymentService::onlyDeploymentId($registrationId);
        try {
            Lti11TestLaunch::courseResourceLink(
                $this->id['eecs280'],
                $deploymentId,
                $this->insertInstructor(),
                'lti_missing',
                'Missing',
                'https://local.dj4e.com/tsugi/lessons/return',
                'Learner',
                'lms-user-4'
            );
            $this->fail('Expected a missing lesson link to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('has not been created', $ex->getMessage());
        }
    }

    private function insertLessonLink($contextId, $resourceLinkId, $title): void
    {
        global $PDOX;
        $p = $this->p();
        $PDOX->queryDie(
            "INSERT INTO {$p}lti_link
                (link_key, link_sha256, title, context_id, published, created_at, updated_at)
             VALUES
                (:link_key, :link_sha256, :title, :context_id, 0, NOW(), NOW())",
            array(
                ':link_key' => $resourceLinkId,
                ':link_sha256' => U::lti_sha256($resourceLinkId),
                ':title' => $title,
                ':context_id' => (int) $contextId,
            )
        );
    }

    private function insertInstructor(): int
    {
        global $PDOX;
        $p = $this->p();
        $userKey = 'pat-'.bin2hex(random_bytes(4));
        $PDOX->queryDie(
            "INSERT INTO {$p}lti_user (user_key, user_sha256, displayname, email, key_id, created_at)
             VALUES (:user_key, :sha, 'Pat Instructor', 'pat@example.test', :key_id, NOW())",
            array(
                ':user_key' => $userKey,
                ':sha' => hash('sha256', $userKey),
                ':key_id' => $this->id['keyA'],
            )
        );
        return (int) $PDOX->lastInsertId();
    }
}

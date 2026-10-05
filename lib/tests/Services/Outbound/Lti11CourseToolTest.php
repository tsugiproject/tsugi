<?php

use Tsugi\Services\Outbound\Lti11CourseTool;
use Tsugi\Services\Outbound\ToolDeploymentGrant;
use Tsugi\Services\Outbound\ToolDeploymentService;
use Tsugi\Services\Outbound\ToolPlacementService;
use Tsugi\Services\Outbound\ToolRegistrationDocument;
use Tsugi\Services\Outbound\ToolRegistrationService;

require_once __DIR__.'/../Org/PlatformSchemaCase.php';

class Lti11CourseToolTest extends PlatformSchemaCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $PDOX;
        $p = $this->p();
        if ( $PDOX->metadata($p.'lti_tool_message_placement') === false
            || $PDOX->metadata($p.'lti_tool_deployment_claim') === false
            || $PDOX->metadata($p.'lti_tool_deployment_scope') === false ) {
            $this->markTestSkipped('Outbound tool tables are missing. Run php admin/upgrade.php.');
        }
    }

    public function testAddingAToolFillsTheCourseTables(): void
    {
        $registrationId = Lti11CourseTool::addToCourse($this->id['eecs280'], 0, $this->post());
        $registration = ToolRegistrationService::findRegistration($registrationId);
        $this->assertSame($this->id['eecs280'], $registration['owner_context_id']);
        $this->assertNull($registration['owner_org_id']);
        $this->assertSame('1.1', $registration['lti_version']);
        $this->assertSame($this->id['keyA'], $registration['key_id']);

        $deploymentId = ToolDeploymentService::onlyDeploymentId($registrationId);
        $visible = ToolDeploymentService::getDeploymentsForContext($this->id['eecs280']);
        $this->assertSame(array($deploymentId), array_column($visible, 'tool_deployment_id'));
        $this->assertSame(array(), ToolDeploymentService::getRegistrationsForContext($this->id['eecs281']));

        global $PDOX;
        $org = $PDOX->rowDie(
            "SELECT org_id FROM {$this->p()}lti_tool_deployment_org WHERE tool_deployment_id = :id",
            array(':id' => $deploymentId)
        );
        $this->assertFalse(is_array($org));

        $types = array();
        foreach ( ToolRegistrationDocument::messagesForRegistration($registrationId) as $message ) {
            $types[] = $message['message_type'];
        }
        $this->assertSame(
            array('LtiResourceLinkRequest', 'LtiDeepLinkingRequest'),
            $types
        );
        $enabled = array();
        foreach ( ToolPlacementService::getEnabledPlacementsForDeployment($deploymentId) as $row ) {
            $enabled[] = $row['placement'];
        }
        $this->assertSame(
            array('content_editor', 'course_navigation', 'lessons', 'content_editor', 'lessons'),
            $enabled
        );
        $this->assertSame(
            array('email', 'family_name', 'given_name', 'name'),
            ToolDeploymentGrant::allowedClaims($deploymentId)
        );
        $this->assertSame(
            array(ToolRegistrationDocument::SCOPE_SCORE),
            ToolDeploymentGrant::allowedScopes($deploymentId)
        );
    }

    public function testPlacementCheckboxesNameTheLaunchTheyRequire(): void
    {
        $requires = array();
        foreach ( Lti11CourseTool::formSections() as $section ) {
            if ( ($section['name'] ?? '') !== 'placements' ) {
                continue;
            }
            foreach ( $section['boxes'] as $box ) {
                $requires[$box['value']] = $box['requires'];
            }
        }
        $either = array('LtiDeepLinkingRequest', 'LtiResourceLinkRequest');
        $this->assertSame($either, $requires['lessons']);
        $this->assertSame($either, $requires['content_editor']);
        $this->assertSame($either, $requires['assignment_selection']);
        $this->assertSame(array('LtiDeepLinkingRequest'), $requires['common_cartridge']);
        $this->assertSame(array('LtiResourceLinkRequest'), $requires['course_navigation']);
    }

    public function testEditingAToolRewritesItsRows(): void
    {
        $registrationId = Lti11CourseTool::addToCourse($this->id['eecs280'], 0, $this->post());
        $state = Lti11CourseTool::formState($this->id['eecs280'], $registrationId);
        $this->assertSame('External quiz', $state['title']);
        $this->assertContains('LtiDeepLinkingRequest', $state['messages']);
        $this->assertContains('course_navigation', $state['placements']);
        $this->assertContains('names', $state['privacy']);
        $this->assertContains('score', $state['services']);

        $post = $this->post();
        $post['title'] = 'Renamed quiz';
        $post['lti11_url'] = 'https://tool.example/renamed';
        $post['lti11_secret'] = 'new-secret';
        $post['messages'] = array('LtiResourceLinkRequest');
        $post['placements'] = array('lessons');
        $post['privacy'] = array('email');
        $post['services'] = array();
        Lti11CourseTool::updateOnCourse($this->id['eecs280'], $registrationId, $post);

        $saved = Lti11CourseTool::formState($this->id['eecs280'], $registrationId);
        $this->assertSame($registrationId, $saved['registration_id']);
        $this->assertSame('Renamed quiz', $saved['title']);
        $this->assertSame('https://tool.example/renamed', $saved['lti11_url']);
        $this->assertSame('new-secret', $saved['lti11_secret']);
        $this->assertSame(array('LtiResourceLinkRequest'), $saved['messages']);
        $this->assertSame(array('lessons'), $saved['placements']);
        $this->assertSame(array('email'), $saved['privacy']);
        $this->assertSame(array(), $saved['services']);

        $deploymentId = ToolDeploymentService::onlyDeploymentId($registrationId);
        $this->assertSame(array('email'), ToolDeploymentGrant::allowedClaims($deploymentId));
        $this->assertSame(array(), ToolDeploymentGrant::allowedScopes($deploymentId));
        $enabled = array();
        foreach ( ToolPlacementService::getEnabledPlacementsForDeployment($deploymentId) as $row ) {
            $enabled[] = $row['placement'];
        }
        $this->assertSame(array('lessons'), $enabled);
        $messages = ToolRegistrationDocument::messagesForRegistration($registrationId);
        $this->assertSame('https://tool.example/renamed', $messages[0]['target_link_uri']);
        $this->assertSame(array(), ToolDeploymentService::getRegistrationsForContext($this->id['eecs281']));
    }

    public function testABadEditLeavesTheToolAlone(): void
    {
        $registrationId = Lti11CourseTool::addToCourse($this->id['eecs280'], 0, $this->post());
        $post = $this->post();
        $post['title'] = 'Should not stick';
        $post['messages'] = array('LtiDeepLinkingRequest');
        try {
            Lti11CourseTool::updateOnCourse($this->id['eecs280'], $registrationId, $post);
            $this->fail('Expected the course navigation checkbox to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('resource link', $ex->getMessage());
        }
        $this->assertSame('External quiz', ToolRegistrationService::findRegistration($registrationId)['title']);
    }

    public function testDeletingAToolRemovesItFromTheCourse(): void
    {
        $registrationId = Lti11CourseTool::addToCourse($this->id['eecs280'], 0, $this->post());
        $deploymentId = ToolDeploymentService::onlyDeploymentId($registrationId);
        Lti11CourseTool::deleteFromCourse($this->id['eecs280'], $registrationId);

        $this->assertNull(ToolRegistrationService::findRegistration($registrationId));
        $this->assertSame(array(), Lti11CourseTool::toolsOnCourse($this->id['eecs280']));
        global $PDOX;
        $p = $this->p();
        $deployment = $PDOX->rowDie(
            "SELECT tool_deployment_id FROM {$p}lti_tool_deployment WHERE tool_deployment_id = :id",
            array(':id' => $deploymentId)
        );
        $this->assertFalse(is_array($deployment));
        $messages = $PDOX->rowDie(
            "SELECT COUNT(*) AS n FROM {$p}lti_tool_message WHERE registration_id = :id",
            array(':id' => $registrationId)
        );
        $this->assertSame(0, (int) $messages['n']);
    }

    public function testAnotherCourseCannotEditOrDeleteTheTool(): void
    {
        $registrationId = Lti11CourseTool::addToCourse($this->id['eecs280'], 0, $this->post());
        $post = $this->post();
        $post['title'] = 'Taken';
        try {
            Lti11CourseTool::updateOnCourse($this->id['eecs281'], $registrationId, $post);
            $this->fail('Expected the other course to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('does not own', $ex->getMessage());
        }
        try {
            Lti11CourseTool::deleteFromCourse($this->id['eecs281'], $registrationId);
            $this->fail('Expected the other course to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('does not own', $ex->getMessage());
        }
        $this->assertSame('External quiz', ToolRegistrationService::findRegistration($registrationId)['title']);
    }

    public function testASharedToolIsListedWithoutCourseOwnership(): void
    {
        Lti11CourseTool::addToCourse($this->id['eecs280'], 0, $this->post());
        $shared = ToolRegistrationService::createRegistration($this->id['keyA'], 'Shared quiz', $this->id['csA'], null, array(
            'lti_version' => '1.1',
            'lti11_key' => 'shared-key-'.bin2hex(random_bytes(4)),
            'lti11_secret' => 'shared-secret',
            'lti11_url' => 'https://tool.example/shared',
        ));
        ToolDeploymentService::assignContext(ToolDeploymentService::onlyDeploymentId($shared), $this->id['eecs280']);

        $owned = array();
        foreach ( Lti11CourseTool::toolsOnCourse($this->id['eecs280']) as $tool ) {
            $owned[$tool['title']] = $tool['course_owned'];
        }
        $this->assertTrue($owned['External quiz']);
        $this->assertFalse($owned['Shared quiz']);

        try {
            Lti11CourseTool::deleteFromCourse($this->id['eecs280'], $shared);
            $this->fail('Expected a shared tool to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('does not own', $ex->getMessage());
        }
        $this->assertSame('Shared quiz', ToolRegistrationService::findRegistration($shared)['title']);
    }

    public function testLaunchUrlMayBeShared(): void
    {
        $first = $this->post();
        $first['title'] = 'First quiz';
        $first['lti11_url'] = 'https://tool.example/same';
        $a = Lti11CourseTool::addToCourse($this->id['eecs280'], 0, $first);
        $second = $this->post();
        $second['title'] = 'Second quiz';
        $second['lti11_url'] = 'https://tool.example/same';
        $b = Lti11CourseTool::addToCourse($this->id['eecs281'], 0, $second);
        $this->assertNotSame($a, $b);

        $other = $this->post();
        $other['title'] = 'Other tenant';
        $other['lti11_url'] = 'https://tool.example/same';
        $c = Lti11CourseTool::addToCourse($this->id['eecs183'], 0, $other);

        $this->assertSame(1, Lti11CourseTool::otherLaunchUrlCount($this->id['eecs280'], 'https://tool.example/same', $a));
        $this->assertSame(2, Lti11CourseTool::otherLaunchUrlCount($this->id['eecs280'], 'https://tool.example/same', 0));
        $this->assertSame(0, Lti11CourseTool::otherLaunchUrlCount($this->id['eecs183'], 'https://tool.example/same', $c));
        $this->assertSame(0, Lti11CourseTool::otherLaunchUrlCount($this->id['eecs280'], 'https://tool.example/other', $a));

        $listed = Lti11CourseTool::toolsOnCourse($this->id['eecs280']);
        $this->assertSame('First quiz', $listed[0]['title']);
        $this->assertSame('1.1', $listed[0]['lti_version']);
        $this->assertSame(1, $listed[0]['other_launch_urls']);
        $state = Lti11CourseTool::formState($this->id['eecs280'], $a);
        $this->assertSame(1, $state['other_launch_urls']);
        $this->assertSame('There is 1 other tool using this launch URL.', Lti11CourseTool::launchUrlNote(1));
        $this->assertSame('There are 2 other tools using this launch URL.', Lti11CourseTool::launchUrlNote(2));
        $this->assertSame('', Lti11CourseTool::launchUrlNote(0));
    }

    public function testAPlacementWithoutItsLaunchIsRejected(): void
    {
        $post = $this->post();
        $post['messages'] = array('LtiDeepLinkingRequest');
        $before = $this->registrationCount();
        try {
            Lti11CourseTool::addToCourse($this->id['eecs280'], 0, $post);
            $this->fail('Expected the course navigation checkbox to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('resource link', $ex->getMessage());
        }
        $this->assertSame($before, $this->registrationCount());
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
            'placements' => array('lessons', 'content_editor', 'course_navigation'),
            'privacy' => array('names', 'email'),
            'services' => array('score'),
        );
    }

    private function registrationCount(): int
    {
        global $PDOX;
        $row = $PDOX->rowDie("SELECT COUNT(*) AS n FROM {$this->p()}lti_tool_registration");
        return (int) $row['n'];
    }
}

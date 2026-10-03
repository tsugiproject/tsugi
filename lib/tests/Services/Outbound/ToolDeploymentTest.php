<?php

use Tsugi\Services\Org\OrgService;
use Tsugi\Services\Outbound\ToolDeploymentService;
use Tsugi\Services\Outbound\ToolRegistrationService;

require_once __DIR__.'/../Org/PlatformSchemaCase.php';

class ToolDeploymentTest extends PlatformSchemaCase
{
    public function testOneDeploymentAssignedToMultipleOrgs(): void
    {
        $reg = $this->registration(null, 'Pearson');
        $deployment = ToolDeploymentService::createDeployment($reg, 'ALGEBRA-001');
        ToolDeploymentService::assignOrg($deployment, $this->id['csA']);
        ToolDeploymentService::assignOrg($deployment, $this->id['meA']);

        $this->assertSame(array($this->id['csA'], $this->id['meA']), $this->assignedOrgs($deployment));
        $this->assertSame(array(), $this->assignedCourses($deployment));
        $this->assertSame(
            array('Pearson'),
            $this->titles(ToolDeploymentService::getRegistrationsForContext($this->id['eecs280']))
        );
        $this->assertSame(
            array('Pearson'),
            $this->titles(ToolDeploymentService::getRegistrationsForContext($this->id['me250']))
        );
        $this->assertSame(
            array(),
            ToolDeploymentService::getRegistrationsForContext($this->id['hist101'])
        );
        $this->expectException(\InvalidArgumentException::class);
        ToolDeploymentService::assignOrg($deployment, $this->id['csA']);
    }

    public function testOneDeploymentAssignedToMultipleCourses(): void
    {
        $reg = $this->registration(null, 'Course tool');
        $deployment = ToolDeploymentService::createDeployment($reg, 'COURSES-7');
        ToolDeploymentService::assignContext($deployment, $this->id['eecs280']);
        ToolDeploymentService::assignContext($deployment, $this->id['eecs281']);

        $this->assertSame(
            array($this->id['eecs280'], $this->id['eecs281']),
            $this->assignedCourses($deployment)
        );
        $this->assertSame(array(), $this->assignedOrgs($deployment));
        $this->assertCount(1, ToolDeploymentService::getDeploymentsForContext($this->id['eecs280']));
        $this->assertCount(1, ToolDeploymentService::getDeploymentsForContext($this->id['eecs281']));
        $this->assertSame(
            array(),
            ToolDeploymentService::getRegistrationsForContext($this->id['me250'])
        );
        $this->expectException(\InvalidArgumentException::class);
        ToolDeploymentService::assignContext($deployment, $this->id['eecs280']);
    }

    public function testOneDeploymentAssignedToOrgsAndCourses(): void
    {
        $reg = $this->registration(null, 'Mixed scope');
        $deployment = ToolDeploymentService::createDeployment($reg, 'MIXED-1');
        ToolDeploymentService::assignOrg($deployment, $this->id['lsaA']);
        ToolDeploymentService::assignContext($deployment, $this->id['free101']);
        ToolDeploymentService::assignContext($deployment, $this->id['eecs280']);

        $this->assertSame(array($this->id['lsaA']), $this->assignedOrgs($deployment));
        $this->assertSame(
            array($this->id['eecs280'], $this->id['free101']),
            $this->assignedCourses($deployment)
        );
        $this->assertSame(
            array('Mixed scope'),
            $this->titles(ToolDeploymentService::getRegistrationsForContext($this->id['hist101']))
        );
        $this->assertSame(
            array('Mixed scope'),
            $this->titles(ToolDeploymentService::getRegistrationsForContext($this->id['free101']))
        );
        $this->assertCount(1, ToolDeploymentService::getDeploymentsForContext($this->id['eecs280']));
        $this->assertSame(
            array(),
            ToolDeploymentService::getRegistrationsForContext($this->id['me250'])
        );
    }

    public function testOrganizationAssignmentIsInheritedByDescendantCourses(): void
    {
        $reg = $this->registration(null, 'Engineering tool');
        $deployment = ToolDeploymentService::createDeployment($reg, 'ENG-1');
        ToolDeploymentService::assignOrg($deployment, $this->id['engineeringA']);

        global $PDOX;
        $visible = ToolDeploymentService::getRegistrationsForContext($this->id['eecs280']);
        $this->assertSame(array('Engineering tool'), $this->titles($visible));
        $sql = (string) $PDOX->PDOX_LastSqlQuery;
        $this->assertStringContainsString('WITH RECURSIVE', $sql);
        $this->assertStringContainsString('org_ancestors', $sql);
        $this->assertStringContainsString('key_id', $sql);
        $this->assertStringContainsString('IN (SELECT org_id FROM org_ancestors)', $sql);
        $this->assertStringNotContainsString(':org_0', $sql);
        $this->assertSame(
            array('Engineering tool'),
            $this->titles(ToolDeploymentService::getRegistrationsForContext($this->id['eecs281']))
        );
        $this->assertSame(
            array('Engineering tool'),
            $this->titles(ToolDeploymentService::getRegistrationsForContext($this->id['me250']))
        );
        $this->assertSame(
            array(),
            ToolDeploymentService::getRegistrationsForContext($this->id['hist101'])
        );
        $this->assertSame(
            array(),
            ToolDeploymentService::getRegistrationsForContext($this->id['free101'])
        );
        $this->assertStringContainsString('WITH RECURSIVE', (string) $PDOX->PDOX_LastSqlQuery);
        $this->assertSame(
            array(),
            ToolDeploymentService::getRegistrationsForContext($this->id['eecs183'])
        );
    }

    public function testOrgOwnedRegistration(): void
    {
        $reg = $this->registration($this->id['engineeringA'], 'Owned by Engineering');
        $row = ToolRegistrationService::findRegistration($reg);
        $this->assertSame($this->id['keyA'], $row['key_id']);
        $this->assertSame($this->id['engineeringA'], $row['owner_org_id']);
        $this->assertNull($row['owner_context_id']);
    }

    public function testCourseOwnedRegistration(): void
    {
        $reg = $this->courseRegistration($this->id['eecs280'], 'Owned by EECS 280');
        $row = ToolRegistrationService::findRegistration($reg);
        $this->assertSame($this->id['keyA'], $row['key_id']);
        $this->assertNull($row['owner_org_id']);
        $this->assertSame($this->id['eecs280'], $row['owner_context_id']);

        $deployment = ToolDeploymentService::createDeployment($reg, 'local-280');
        ToolDeploymentService::assignContext($deployment, $this->id['eecs280']);
        $this->assertSame(
            array('Owned by EECS 280'),
            $this->titles(ToolDeploymentService::getRegistrationsForContext($this->id['eecs280']))
        );
        $this->assertSame(
            array(),
            ToolDeploymentService::getRegistrationsForContext($this->id['eecs281'])
        );
    }

    public function testTenantAdminMayManageOrgAndCourseOwnedRegistrations(): void
    {
        $orgOwned = $this->registration($this->id['csA'], 'CS registration');
        $courseOwned = $this->courseRegistration($this->id['eecs280'], 'Course registration');

        $this->assertTrue(ToolRegistrationService::mayAdminister($orgOwned, array('key_id' => $this->id['keyA'])));
        $this->assertTrue(ToolRegistrationService::mayAdminister($courseOwned, array('key_id' => $this->id['keyA'])));
        $this->assertTrue(ToolRegistrationService::mayAdminister($orgOwned, array('system' => true)));
        $this->assertFalse(ToolRegistrationService::mayAdminister($orgOwned, array('key_id' => $this->id['keyB'])));
        $this->assertFalse(ToolRegistrationService::mayAdminister($courseOwned, array('key_id' => $this->id['keyB'])));
    }

    public function testOrgAdminMayManageRegistrationsInTheOrgSubtree(): void
    {
        $engineering = $this->registration($this->id['engineeringA'], 'Engineering registration');
        $cs = $this->registration($this->id['csA'], 'CS registration');
        $lsa = $this->registration($this->id['lsaA'], 'LSA registration');
        $courseOwned = $this->courseRegistration($this->id['eecs280'], 'Course registration');

        $this->assertTrue(ToolRegistrationService::mayAdminister($engineering, array('org_id' => $this->id['engineeringA'])));
        $this->assertTrue(ToolRegistrationService::mayAdminister($cs, array('org_id' => $this->id['engineeringA'])));
        $this->assertFalse(ToolRegistrationService::mayAdminister($engineering, array('org_id' => $this->id['csA'])));
        $this->assertFalse(ToolRegistrationService::mayAdminister($lsa, array('org_id' => $this->id['engineeringA'])));
        $this->assertFalse(ToolRegistrationService::mayAdminister($cs, array('org_id' => $this->id['engineeringB'])));
        $this->assertFalse(ToolRegistrationService::mayAdminister($courseOwned, array('org_id' => $this->id['csA'])));
    }

    public function testCourseAdminMayManageTheRegistrationOwnedByThatCourse(): void
    {
        $owned = $this->courseRegistration($this->id['eecs280'], 'EECS 280 tool');
        $other = $this->courseRegistration($this->id['eecs281'], 'EECS 281 tool');
        $orgOwned = $this->registration($this->id['csA'], 'CS registration');

        $this->assertTrue(ToolRegistrationService::mayAdminister($owned, array('context_id' => $this->id['eecs280'])));
        $this->assertFalse(ToolRegistrationService::mayAdminister($other, array('context_id' => $this->id['eecs280'])));
        $this->assertFalse(ToolRegistrationService::mayAdminister($orgOwned, array('context_id' => $this->id['eecs280'])));
        $this->assertFalse(ToolRegistrationService::mayAdminister($owned, array('context_id' => $this->id['eecs183'])));
    }

    public function testOwnerOrgAndOwnerCourseCannotBothBeSet(): void
    {
        $this->expectRejection(function () {
            ToolRegistrationService::createRegistration(
                $this->id['keyA'],
                'Both owners',
                $this->id['csA'],
                null,
                array(),
                $this->id['eecs280']
            );
        });

        $this->assertSqlRejected(
            "INSERT INTO {$this->p()}lti_tool_registration
                (key_id, owner_org_id, owner_context_id, title, created_at)
             VALUES (:key_id, :owner_org_id, :owner_context_id, 'Both', NOW())",
            array(
                ':key_id' => $this->id['keyA'],
                ':owner_org_id' => $this->id['csA'],
                ':owner_context_id' => $this->id['eecs280'],
            ),
            'lti_tool_registration_chk_2'
        );
    }

    public function testDeploymentCannotBeAssignedAcrossTenants(): void
    {
        $reg = $this->registration(null, 'Tenant A tool');
        $deployment = ToolDeploymentService::createDeployment($reg, 'stay-home');

        $this->expectRejection(function () use ($deployment) {
            ToolDeploymentService::assignOrg($deployment, $this->id['csB']);
        });
        $this->expectRejection(function () use ($deployment) {
            ToolDeploymentService::assignContext($deployment, $this->id['eecs183']);
        });

        $p = $this->p();
        $this->assertSqlRejected(
            "INSERT INTO {$p}lti_tool_deployment_org
                (tool_deployment_id, org_id, key_id, created_at)
             VALUES (:tool_deployment_id, :org_id, :key_id, NOW())",
            array(
                ':tool_deployment_id' => $deployment,
                ':org_id' => $this->id['csB'],
                ':key_id' => $this->id['keyA'],
            ),
            'lti_tool_deployment_org_ibfk_2'
        );
        $this->assertSqlRejected(
            "INSERT INTO {$p}lti_tool_deployment_context
                (tool_deployment_id, context_id, key_id, created_at)
             VALUES (:tool_deployment_id, :context_id, :key_id, NOW())",
            array(
                ':tool_deployment_id' => $deployment,
                ':context_id' => $this->id['eecs183'],
                ':key_id' => $this->id['keyB'],
            ),
            'lti_tool_deployment_context_ibfk_1'
        );
    }

    public function testRegistrationAndDeploymentCannotCrossTenants(): void
    {
        $p = $this->p();
        $reg = $this->registration(null, 'Tenant A tool');

        $this->expectRejection(function () {
            ToolRegistrationService::createRegistration($this->id['keyA'], 'Borrowed org', $this->id['csB']);
        });
        $this->expectRejection(function () {
            ToolRegistrationService::createRegistration(
                $this->id['keyA'],
                'Borrowed course',
                null,
                null,
                array(),
                $this->id['eecs183']
            );
        });

        $this->assertSqlRejected(
            "INSERT INTO {$p}lti_tool_registration (key_id, owner_org_id, title, created_at)
             VALUES (:key_id, :owner_org_id, 'Borrowed', NOW())",
            array(':key_id' => $this->id['keyA'], ':owner_org_id' => $this->id['csB']),
            'lti_tool_registration_ibfk_2'
        );
        $this->assertSqlRejected(
            "INSERT INTO {$p}lti_tool_deployment
                (registration_id, key_id, deployment_id, created_at)
             VALUES (:registration_id, :key_id, 'cross', NOW())",
            array(
                ':registration_id' => $reg,
                ':key_id' => $this->id['keyB'],
            ),
            'lti_tool_deployment_ibfk_1'
        );
    }

    public function testDeploymentIdSurvivesScopeChanges(): void
    {
        global $PDOX;
        $p = $this->p();
        $reg = $this->registration(null, 'Pearson');
        $deployment = ToolDeploymentService::createDeployment($reg, 'ALGEBRA-001');
        ToolDeploymentService::assignOrg($deployment, $this->id['csA']);
        ToolDeploymentService::assignOrg($deployment, $this->id['meA']);
        ToolDeploymentService::assignContext($deployment, $this->id['free101']);

        OrgService::deleteOrg($this->id['csA']);

        $row = $PDOX->rowDie(
            "SELECT tool_deployment_id, deployment_id, key_id
             FROM {$p}lti_tool_deployment
             WHERE tool_deployment_id = :tool_deployment_id",
            array(':tool_deployment_id' => $deployment)
        );
        $this->assertSame($deployment, (int) $row['tool_deployment_id']);
        $this->assertSame('ALGEBRA-001', $row['deployment_id']);
        $this->assertSame($this->id['keyA'], (int) $row['key_id']);
        $this->assertContains($this->id['engineeringA'], $this->assignedOrgs($deployment));
        $this->assertSame(array($this->id['free101']), $this->assignedCourses($deployment));
    }

    public function testDeletingAnOrgMovesTheAssignmentAndKeepsTheDeployment(): void
    {
        global $PDOX;
        $p = $this->p();
        $tool = $this->registration($this->id['aiA'], 'Scoped to AI Lab');
        $deployment = ToolDeploymentService::createDeployment($tool, 'ai-deploy');
        ToolDeploymentService::assignOrg($deployment, $this->id['aiA']);
        $kept = ToolDeploymentService::createDeployment($this->registration(null, 'Other'), 'other-deploy');
        ToolDeploymentService::assignContext($kept, $this->id['eecs280']);

        OrgService::deleteOrg($this->id['aiA']);
        $this->assertTrue($this->deploymentExists($deployment));
        $this->assertSame(array($this->id['csA']), $this->assignedOrgs($deployment));
        $this->assertSame('ai-deploy', $this->deploymentId($deployment));
        $released = ToolRegistrationService::findRegistration($tool);
        $this->assertNull($released['owner_org_id']);
        $this->assertSame($this->id['keyA'], $released['key_id']);
        $this->assertTrue($this->deploymentExists($kept));

        $stmt = $PDOX->queryReturnError(
            "DELETE FROM {$p}lti_context WHERE context_id = :context_id",
            array(':context_id' => $this->id['eecs280'])
        );
        $this->assertTrue((bool) $stmt->success, (string) $stmt->errorImplode);
        $this->assertTrue($this->deploymentExists($kept));
        $this->assertSame(array(), $this->assignedCourses($kept));
        $this->assertSame('other-deploy', $this->deploymentId($kept));

        $stmt = $PDOX->queryReturnError(
            "DELETE FROM {$p}lti_tool_registration WHERE registration_id = :registration_id",
            array(':registration_id' => $tool)
        );
        $this->assertTrue((bool) $stmt->success, (string) $stmt->errorImplode);
        $this->assertFalse($this->deploymentExists($deployment));
        $this->assertTrue($this->deploymentExists($kept));
    }

    public function testDeletingARootOrgDropsTheAssignmentWithoutAKeyWideScope(): void
    {
        $tool = $this->registration(null, 'Bubble');
        $deployment = ToolDeploymentService::createDeployment($tool, 'bubble-id');
        ToolDeploymentService::assignOrg($deployment, $this->id['universityA']);

        OrgService::deleteOrg($this->id['universityA']);
        $this->assertTrue($this->deploymentExists($deployment));
        $this->assertSame('bubble-id', $this->deploymentId($deployment));
        $this->assertSame(array(), $this->assignedOrgs($deployment));
        $this->assertSame(
            array(),
            ToolDeploymentService::getRegistrationsForContext($this->id['free101'])
        );
        $this->assertSame(
            array(),
            ToolDeploymentService::getRegistrationsForContext($this->id['eecs183'])
        );
    }

    public function testUnassignedDeploymentIsNotVisible(): void
    {
        $tool = $this->registration(null, 'Waiting');
        ToolDeploymentService::createDeployment($tool, 'not-yet');

        $this->assertSame(
            array(),
            ToolDeploymentService::getRegistrationsForContext($this->id['eecs280'])
        );
        $this->assertSame(
            array(),
            ToolDeploymentService::getRegistrationsForContext($this->id['free101'])
        );
    }

    public function testDirectAndAncestorScopeCollapseToOneRegistration(): void
    {
        $tool = $this->registration(null, 'Both');
        $deployment = ToolDeploymentService::createDeployment($tool, 'both-1');
        ToolDeploymentService::assignOrg($deployment, $this->id['engineeringA']);
        ToolDeploymentService::assignContext($deployment, $this->id['eecs280']);
        $other = ToolDeploymentService::createDeployment($tool, 'both-2');
        ToolDeploymentService::assignContext($other, $this->id['eecs280']);

        $regs = ToolDeploymentService::getRegistrationsForContext($this->id['eecs280']);
        $this->assertSame(array('Both'), $this->titles($regs));
        $this->assertCount(2, ToolDeploymentService::getDeploymentsForContext($this->id['eecs280']));
    }

    public function testMissingCourseIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ToolDeploymentService::getRegistrationsForContext(2147483646);
    }

    public function testRegistrationIsEitherLti11OrLti13(): void
    {
        global $PDOX;
        $p = $this->p();
        $lti11 = ToolRegistrationService::createRegistration($this->id['keyA'], 'Old tool', null, null, array(
            'lti_version' => '1.1',
            'lti11_key' => 'consumer',
            'lti11_secret' => 'secret',
            'lti11_url' => 'https://tool.example/launch',
        ));
        $row = $PDOX->rowDie(
            "SELECT lti_version, lti11_key, lti11_secret, lti11_url, lti13_client_id
             FROM {$p}lti_tool_registration WHERE registration_id = :registration_id",
            array(':registration_id' => $lti11)
        );
        $this->assertSame('1.1', $row['lti_version']);
        $this->assertSame('consumer', $row['lti11_key']);
        $this->assertSame('secret', $row['lti11_secret']);
        $this->assertSame('https://tool.example/launch', $row['lti11_url']);
        $this->assertNull($row['lti13_client_id']);

        $lti13 = $this->registration(null, 'New tool');
        $modern = $PDOX->rowDie(
            "SELECT lti_version, lti11_key FROM {$p}lti_tool_registration WHERE registration_id = :registration_id",
            array(':registration_id' => $lti13)
        );
        $this->assertSame('1.3', $modern['lti_version']);
        $this->assertNull($modern['lti11_key']);

        $this->expectRejection(function () {
            ToolRegistrationService::createRegistration($this->id['keyA'], 'Half', null, null, array(
                'lti_version' => '1.1',
                'lti11_key' => 'consumer',
            ));
        });
        $this->expectRejection(function () {
            ToolRegistrationService::createRegistration($this->id['keyA'], 'Mixed', null, null, array(
                'lti11_key' => 'consumer',
                'lti11_secret' => 'secret',
                'lti11_url' => 'https://tool.example/launch',
            ));
        });
        $this->assertSqlRejected(
            "INSERT INTO {$p}lti_tool_registration
                (key_id, title, lti_version, lti11_key, lti11_secret, created_at)
             VALUES (:key_id, 'Broken', '1.1', 'consumer', 'secret', NOW())",
            array(':key_id' => $this->id['keyA']),
            'lti_tool_registration_chk_1'
        );
        $this->assertSqlRejected(
            "INSERT INTO {$p}lti_tool_registration
                (key_id, title, lti_version, lti11_key, lti11_secret, lti11_url, created_at)
             VALUES (:key_id, 'Also broken', '1.3', 'consumer', 'secret', 'https://tool.example/launch', NOW())",
            array(':key_id' => $this->id['keyA']),
            'lti_tool_registration_chk_1'
        );
    }

    public function testLti11RegistrationCreatesOneNullDeployment(): void
    {
        $reg = ToolRegistrationService::createRegistration($this->id['keyA'], 'Old tool', null, null, array(
            'lti_version' => '1.1',
            'lti11_key' => 'abc123',
            'lti11_secret' => 'shared-secret',
            'lti11_url' => 'https://tool.example/launch',
        ));
        $ids = $this->deploymentIdsFor($reg);
        $this->assertCount(1, $ids);
        $this->assertNull($this->nullableDeploymentId($ids[0]));
        $row = $this->registrationRow($reg);
        $this->assertSame('abc123', $row['lti11_key']);
        $this->assertSame('shared-secret', $row['lti11_secret']);
        $this->assertSame('https://tool.example/launch', $row['lti11_url']);
        $this->assertNull($row['lti13_client_id']);

        $resolved = ToolDeploymentService::resolveLti11('abc123', 'shared-secret', $this->id['keyA']);
        $this->assertSame($reg, $resolved['registration_id']);
        $this->assertSame($ids[0], $resolved['tool_deployment_id']);
        $this->assertNull($resolved['deployment_id']);
        $this->assertSame($this->id['keyA'], $resolved['key_id']);

        $this->expectRejection(function () {
            ToolDeploymentService::resolveLti11('abc123', 'wrong-secret', $this->id['keyA']);
        });
        $this->assertSame(0, ToolDeploymentService::ensureLti11Deployments());
        $this->assertCount(1, $this->deploymentIdsFor($reg));
        $this->expectException(\InvalidArgumentException::class);
        ToolDeploymentService::createDeployment($reg, null);
    }

    public function testLti11ConsumerKeyMayRepeat(): void
    {
        $meta = array(
            'lti_version' => '1.1',
            'lti11_key' => 'shared-consumer',
            'lti11_secret' => 'secret-a',
            'lti11_url' => 'https://tool.example/launch',
        );
        $a = ToolRegistrationService::createRegistration($this->id['keyA'], 'First', null, null, $meta);
        $meta['lti11_secret'] = 'secret-b';
        $b = ToolRegistrationService::createRegistration($this->id['keyA'], 'Second', null, null, $meta);
        $meta['lti11_secret'] = 'secret-c';
        $c = ToolRegistrationService::createRegistration($this->id['keyB'], 'Other tenant', null, null, $meta);
        $this->assertNotSame($a, $b);
        $this->assertNotSame($b, $c);

        $resolvedA = ToolDeploymentService::resolveLti11('shared-consumer', 'secret-a', $this->id['keyA']);
        $resolvedB = ToolDeploymentService::resolveLti11('shared-consumer', 'secret-b', $this->id['keyA']);
        $resolvedC = ToolDeploymentService::resolveLti11('shared-consumer', 'secret-c', $this->id['keyB']);
        $this->assertSame($a, $resolvedA['registration_id']);
        $this->assertSame($b, $resolvedB['registration_id']);
        $this->assertSame($c, $resolvedC['registration_id']);
        $this->expectRejection(function () {
            ToolDeploymentService::resolveLti11('shared-consumer', 'secret-a', $this->id['keyB']);
        });
        $meta['lti11_secret'] = 'secret-a';
        ToolRegistrationService::createRegistration($this->id['keyA'], 'Same secret again', null, null, $meta);
        $this->expectRejection(function () {
            ToolDeploymentService::resolveLti11('shared-consumer', 'secret-a', $this->id['keyA']);
        });
    }

    public function testLti11DeploymentRejectsAnExternalId(): void
    {
        $reg = ToolRegistrationService::createRegistration($this->id['keyA'], 'Shim', null, null, array(
            'lti_version' => '1.1',
            'lti11_key' => 'shim-key',
            'lti11_secret' => 'shim-secret',
            'lti11_url' => 'https://tool.example/launch',
        ));
        $this->expectException(\InvalidArgumentException::class);
        ToolDeploymentService::createDeployment($reg, '42');
    }

    public function testLti11ScopeUsesTheSameCourseLookup(): void
    {
        $lti11 = ToolRegistrationService::createRegistration($this->id['keyA'], 'LTI 1.1 tool', null, null, array(
            'lti_version' => '1.1',
            'lti11_key' => 'scope-key',
            'lti11_secret' => 'scope-secret',
            'lti11_url' => 'https://tool.example/launch',
        ));
        $shim = $this->deploymentIdsFor($lti11)[0];
        ToolDeploymentService::assignOrg($shim, $this->id['csA']);
        ToolDeploymentService::assignOrg($shim, $this->id['meA']);
        ToolDeploymentService::assignContext($shim, $this->id['free101']);
        $this->assertSame(array($this->id['csA'], $this->id['meA']), $this->assignedOrgs($shim));
        $this->assertSame(array($this->id['free101']), $this->assignedCourses($shim));

        $lti13 = $this->registration(null, 'LTI 1.3 tool');
        $modern = ToolDeploymentService::createDeployment($lti13, 'opaque-value');
        ToolDeploymentService::assignOrg($modern, $this->id['engineeringA']);

        global $PDOX;
        $visible = ToolDeploymentService::getRegistrationsForContext($this->id['eecs280']);
        $this->assertSame(array('LTI 1.1 tool', 'LTI 1.3 tool'), $this->titles($visible));
        $this->assertCount(2, ToolDeploymentService::getDeploymentsForContext($this->id['eecs280']));
        $sql = (string) $PDOX->PDOX_LastSqlQuery;
        $this->assertStringContainsString('WITH RECURSIVE', $sql);
        $this->assertStringContainsString('lti_tool_deployment_org', $sql);
        $this->assertStringContainsString('lti_tool_deployment_context', $sql);
        $this->assertSame(
            array('LTI 1.1 tool', 'LTI 1.3 tool'),
            $this->titles(ToolDeploymentService::getRegistrationsForContext($this->id['me250']))
        );
        $this->assertSame(
            array('LTI 1.1 tool'),
            $this->titles(ToolDeploymentService::getRegistrationsForContext($this->id['free101']))
        );
        $this->assertSame(
            array(),
            ToolDeploymentService::getRegistrationsForContext($this->id['hist101'])
        );
    }

    public function testWrongProtocolColumnsAreRejected(): void
    {
        $this->expectRejection(function () {
            ToolRegistrationService::createRegistration($this->id['keyA'], 'Mixed 1.1', null, null, array(
                'lti_version' => '1.1',
                'lti11_key' => 'mixed-key',
                'lti11_secret' => 'mixed-secret',
                'lti11_url' => 'https://tool.example/launch',
                'lti13_client_id' => 'should-not',
            ));
        });
        $this->expectRejection(function () {
            ToolRegistrationService::createRegistration($this->id['keyA'], 'Mixed 1.3', null, null, array(
                'lti_version' => '1.3',
                'lti11_key' => 'should-not',
            ));
        });
        $p = $this->p();
        $this->assertSqlRejected(
            "INSERT INTO {$p}lti_tool_registration
                (key_id, title, lti_version, lti11_key, lti11_secret, lti11_url, lti13_client_id, created_at)
             VALUES (:key_id, 'Mixed', '1.1', 'db-key', 'db-secret', 'https://tool.example/launch', 'client', NOW())",
            array(':key_id' => $this->id['keyA']),
            'lti_tool_registration_chk_1'
        );
        $this->assertSqlRejected(
            "INSERT INTO {$p}lti_tool_registration
                (key_id, title, lti_version, lti11_secret, created_at)
             VALUES (:key_id, 'Mixed 13', '1.3', 'secret', NOW())",
            array(':key_id' => $this->id['keyA']),
            'lti_tool_registration_chk_1'
        );
        $lti13 = ToolRegistrationService::createRegistration($this->id['keyA'], 'Bare 1.3', null, null, array(
            'lti_version' => '1.3',
            'lti13_client_id' => 'client-1',
        ));
        $stored = $this->registrationRow($lti13);
        $this->assertSame('1.3', $stored['lti_version']);
        $this->assertSame('client-1', $stored['lti13_client_id']);
        $this->assertNull($stored['lti11_key']);
        $this->assertNull($this->registrationRow($this->registration(null, 'No client yet'))['lti13_client_id']);
    }

    public function testLti11BackfillCreatesOneDeploymentAndLeavesCredentials(): void
    {
        global $PDOX;
        $p = $this->p();
        $stmt = $PDOX->queryReturnError(
            "INSERT INTO {$p}lti_tool_registration
                (key_id, title, lti_version, lti11_key, lti11_secret, lti11_url, created_at)
             VALUES (:key_id, 'Legacy', '1.1', 'legacy-key', 'legacy-secret', 'https://tool.example/legacy', NOW())",
            array(':key_id' => $this->id['keyA'])
        );
        $this->assertTrue((bool) $stmt->success, (string) $stmt->errorImplode);
        $reg = (int) $PDOX->lastInsertId();
        $this->assertSame(array(), $this->deploymentIdsFor($reg));

        $this->assertSame(1, ToolDeploymentService::ensureLti11Deployments());
        $ids = $this->deploymentIdsFor($reg);
        $this->assertCount(1, $ids);
        $this->assertNull($this->nullableDeploymentId($ids[0]));
        $this->assertSame(0, ToolDeploymentService::ensureLti11Deployments());
        $this->assertSame(0, ToolDeploymentService::ensureLti11Deployments());
        $this->assertCount(1, $this->deploymentIdsFor($reg));
        $row = $this->registrationRow($reg);
        $this->assertSame('legacy-key', $row['lti11_key']);
        $this->assertSame('legacy-secret', $row['lti11_secret']);
        $this->assertSame('https://tool.example/legacy', $row['lti11_url']);
    }

    public function testDeletingAnLti11RegistrationRemovesItsDeploymentAndScope(): void
    {
        global $PDOX;
        $p = $this->p();
        $reg = ToolRegistrationService::createRegistration($this->id['keyA'], 'Gone', null, null, array(
            'lti_version' => '1.1',
            'lti11_key' => 'gone-key',
            'lti11_secret' => 'gone-secret',
            'lti11_url' => 'https://tool.example/launch',
        ));
        $deployment = $this->deploymentIdsFor($reg)[0];
        ToolDeploymentService::assignOrg($deployment, $this->id['csA']);
        ToolDeploymentService::assignContext($deployment, $this->id['eecs280']);

        $stmt = $PDOX->queryReturnError(
            "DELETE FROM {$p}lti_tool_registration WHERE registration_id = :registration_id",
            array(':registration_id' => $reg)
        );
        $this->assertTrue((bool) $stmt->success, (string) $stmt->errorImplode);
        $this->assertFalse($this->deploymentExists($deployment));
        $this->assertSame(array(), $this->assignedOrgs($deployment));
        $this->assertSame(array(), $this->assignedCourses($deployment));
    }

    public function testDirectHoldingsCountOnlyRowsOnThatOrg(): void
    {
        $csTool = $this->registration($this->id['csA'], 'CS tool');
        $wide = $this->registration(null, 'Campus tool');
        $engineeringTool = $this->registration($this->id['engineeringA'], 'Engineering tool');
        $onCs = ToolDeploymentService::createDeployment($csTool, 'deploy-cs');
        ToolDeploymentService::assignOrg($onCs, $this->id['csA']);
        $onAi = ToolDeploymentService::createDeployment($wide, 'deploy-ai');
        ToolDeploymentService::assignOrg($onAi, $this->id['aiA']);
        $onCourse = ToolDeploymentService::createDeployment($wide, 'deploy-280');
        ToolDeploymentService::assignContext($onCourse, $this->id['eecs280']);
        ToolDeploymentService::createDeployment($wide, 'deploy-unassigned');

        $cs = OrgService::directHoldings($this->id['csA']);
        $this->assertSame(
            array('courses' => 2, 'deployments' => 1, 'children' => 1, 'registrations' => 1),
            $cs['counts']
        );
        $this->assertFalse($cs['empty']);
        $this->assertSame(array('EECS 280', 'EECS 281'), $this->titles($cs['courses']));
        $this->assertSame(array('CS tool'), $this->titles($cs['deployments']));
        $this->assertSame('deploy-cs', $cs['deployments'][0]['deployment_id']);
        $this->assertSame(array('AI Lab'), $this->titles($cs['children']));
        $this->assertSame(array('CS tool'), $this->titles($cs['registrations']));
        $this->assertSame($this->id['keyA'], $cs['courses'][0]['key_id']);

        $engineering = OrgService::directHoldings($this->id['engineeringA']);
        $this->assertSame(0, $engineering['counts']['courses']);
        $this->assertSame(0, $engineering['counts']['deployments']);
        $this->assertSame(array('Computer Science', 'Mechanical Engineering'), $this->titles($engineering['children']));
        $this->assertSame(array('Engineering tool'), $this->titles($engineering['registrations']));
        $this->assertFalse($engineering['empty']);

        $ai = OrgService::directHoldings($this->id['aiA']);
        $this->assertSame(1, $ai['counts']['deployments']);
        $this->assertSame(array('Campus tool'), $this->titles($ai['deployments']));
        $this->assertSame(0, $ai['counts']['courses']);
        $this->assertSame(0, $ai['counts']['children']);
        $this->assertSame(0, $ai['counts']['registrations']);

        OrgService::placeContext($this->id['eecs280'], $this->id['engineeringA']);
        OrgService::placeContext($this->id['eecs281'], null);
        $after = OrgService::directHoldings($this->id['csA']);
        $this->assertSame(0, $after['counts']['courses']);
        $moved = OrgService::directHoldings($this->id['engineeringA']);
        $this->assertSame(array('EECS 280'), $this->titles($moved['courses']));

        $fresh = OrgService::createOrg($this->id['keyA'], 'Empty office', $this->id['lsaA']);
        $empty = OrgService::directHoldings($fresh);
        $this->assertSame(
            array('courses' => 0, 'deployments' => 0, 'children' => 0, 'registrations' => 0),
            $empty['counts']
        );
        $this->assertTrue($empty['empty']);

        try {
            OrgService::directHoldings(0);
            $this->fail('Expected a missing organization to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('Organization', $ex->getMessage());
        }
    }

    private function registration(?int $orgId, string $title): int
    {
        return ToolRegistrationService::createRegistration($this->id['keyA'], $title, $orgId);
    }

    private function courseRegistration(int $contextId, string $title): int
    {
        return ToolRegistrationService::createRegistration(
            $this->id['keyA'],
            $title,
            null,
            null,
            array(),
            $contextId
        );
    }

    /**
     * @return array<int, int>
     */
    private function assignedOrgs(int $toolDeploymentId): array
    {
        return $this->assignedIds('lti_tool_deployment_org', 'org_id', $toolDeploymentId);
    }

    /**
     * @return array<int, int>
     */
    private function assignedCourses(int $toolDeploymentId): array
    {
        return $this->assignedIds('lti_tool_deployment_context', 'context_id', $toolDeploymentId);
    }

    /**
     * @return array<int, int>
     */
    private function assignedIds(string $table, string $column, int $toolDeploymentId): array
    {
        global $PDOX;
        $rows = $PDOX->allRowsDie(
            "SELECT {$column} AS target_id
             FROM {$this->p()}{$table}
             WHERE tool_deployment_id = :tool_deployment_id
             ORDER BY {$column} ASC",
            array(':tool_deployment_id' => $toolDeploymentId)
        );
        $ids = array();
        foreach ( $rows as $row ) {
            $ids[] = (int) $row['target_id'];
        }
        return $ids;
    }

    /**
     * @return array<int, int>
     */
    private function deploymentIdsFor(int $registrationId): array
    {
        global $PDOX;
        $rows = $PDOX->allRowsDie(
            "SELECT tool_deployment_id FROM {$this->p()}lti_tool_deployment
             WHERE registration_id = :registration_id
             ORDER BY tool_deployment_id ASC",
            array(':registration_id' => $registrationId)
        );
        $ids = array();
        foreach ( $rows as $row ) {
            $ids[] = (int) $row['tool_deployment_id'];
        }
        return $ids;
    }

    /**
     * @return array<string, mixed>
     */
    private function registrationRow(int $registrationId): array
    {
        global $PDOX;
        $row = $PDOX->rowDie(
            "SELECT lti_version, lti11_key, lti11_secret, lti11_url, lti13_client_id
             FROM {$this->p()}lti_tool_registration
             WHERE registration_id = :registration_id",
            array(':registration_id' => $registrationId)
        );
        return $row;
    }

    private function nullableDeploymentId(int $toolDeploymentId): ?string
    {
        global $PDOX;
        $row = $PDOX->rowDie(
            "SELECT deployment_id FROM {$this->p()}lti_tool_deployment
             WHERE tool_deployment_id = :tool_deployment_id",
            array(':tool_deployment_id' => $toolDeploymentId)
        );
        if ( ! is_array($row) || $row['deployment_id'] === null ) {
            return null;
        }
        return (string) $row['deployment_id'];
    }

    private function deploymentId(int $toolDeploymentId): string
    {
        global $PDOX;
        $row = $PDOX->rowDie(
            "SELECT deployment_id FROM {$this->p()}lti_tool_deployment
             WHERE tool_deployment_id = :tool_deployment_id",
            array(':tool_deployment_id' => $toolDeploymentId)
        );
        return (string) $row['deployment_id'];
    }

    private function deploymentExists(int $toolDeploymentId): bool
    {
        global $PDOX;
        $row = $PDOX->rowDie(
            "SELECT tool_deployment_id FROM {$this->p()}lti_tool_deployment
             WHERE tool_deployment_id = :tool_deployment_id",
            array(':tool_deployment_id' => $toolDeploymentId)
        );
        return is_array($row);
    }

    private function expectRejection(callable $fn): void
    {
        try {
            $fn();
            $this->fail('Expected the change to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertNotSame('', $ex->getMessage());
        }
    }
}

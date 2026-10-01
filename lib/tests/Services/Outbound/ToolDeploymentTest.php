<?php

use Tsugi\Services\Org\OrgService;
use Tsugi\Services\Outbound\ToolDeploymentService;
use Tsugi\Services\Outbound\ToolRegistrationService;

require_once __DIR__.'/../Org/PlatformSchemaCase.php';

class ToolDeploymentTest extends PlatformSchemaCase
{
    public function testTenantWideRegistrationDeploysAnywhereInTheTenant(): void
    {
        $reg = $this->registration(null, 'Campus tool');

        $this->assertTrue(ToolRegistrationService::canDeployToOrg($reg, $this->id['engineeringA']));
        $this->assertTrue(ToolRegistrationService::canDeployToOrg($reg, $this->id['csA']));
        $this->assertTrue(ToolRegistrationService::canDeployToOrg($reg, $this->id['meA']));
        $this->assertTrue(ToolRegistrationService::canDeployToOrg($reg, $this->id['lsaA']));
        $this->assertTrue(ToolRegistrationService::canDeployToContext($reg, $this->id['eecs280']));
        $this->assertTrue(ToolRegistrationService::canDeployToContext($reg, $this->id['free101']));
        $this->assertFalse(ToolRegistrationService::canDeployToOrg($reg, $this->id['engineeringB']));
        $this->assertFalse(ToolRegistrationService::canDeployToContext($reg, $this->id['eecs183']));

        $deployment = ToolDeploymentService::createDeployment($reg, null, $this->id['free101'], 'deploy-free');
        $this->assertGreaterThan(0, $deployment);
        $this->assertSame(array('Campus tool'), $this->titles(ToolDeploymentService::getRegistrationsForContext($this->id['free101'])));
    }

    public function testEngineeringScopedRegistration(): void
    {
        $reg = $this->registration($this->id['engineeringA'], 'Engineering tool');

        foreach ( array('engineeringA', 'csA', 'meA', 'aiA') as $org ) {
            $this->assertTrue(ToolRegistrationService::canDeployToOrg($reg, $this->id[$org]), $org);
        }
        $this->assertTrue(ToolRegistrationService::canDeployToContext($reg, $this->id['eecs280']));
        $this->assertTrue(ToolRegistrationService::canDeployToContext($reg, $this->id['me250']));

        $this->assertFalse(ToolRegistrationService::canDeployToOrg($reg, $this->id['universityA']));
        $this->assertFalse(ToolRegistrationService::canDeployToOrg($reg, $this->id['lsaA']));
        $this->assertFalse(ToolRegistrationService::canDeployToContext($reg, $this->id['hist101']));
        $this->assertFalse(ToolRegistrationService::canDeployToContext($reg, $this->id['free101']));
        $this->assertFalse(ToolRegistrationService::canDeployToOrg($reg, $this->id['engineeringB']));
        $this->assertFalse(ToolRegistrationService::canDeployToContext($reg, $this->id['eecs183']));

        $this->assertGreaterThan(0, ToolDeploymentService::createDeployment($reg, $this->id['meA'], null));
        $this->expectRejection(function () use ($reg) {
            ToolDeploymentService::createDeployment($reg, null, $this->id['free101']);
        });
    }

    public function testComputerScienceScopedRegistration(): void
    {
        $reg = $this->registration($this->id['csA'], 'CS tool');

        $this->assertTrue(ToolRegistrationService::canDeployToOrg($reg, $this->id['csA']));
        $this->assertTrue(ToolRegistrationService::canDeployToOrg($reg, $this->id['aiA']));
        $this->assertTrue(ToolRegistrationService::canDeployToContext($reg, $this->id['eecs280']));
        $this->assertTrue(ToolRegistrationService::canDeployToContext($reg, $this->id['eecs281']));

        $this->assertFalse(ToolRegistrationService::canDeployToOrg($reg, $this->id['meA']));
        $this->assertFalse(ToolRegistrationService::canDeployToOrg($reg, $this->id['engineeringA']));
        $this->assertFalse(ToolRegistrationService::canDeployToContext($reg, $this->id['me250']));
        $this->assertFalse(ToolRegistrationService::canDeployToContext($reg, $this->id['hist101']));
        $this->assertFalse(ToolRegistrationService::canDeployToContext($reg, $this->id['free101']));
    }

    public function testDatabaseEnforcesOneDeploymentTarget(): void
    {
        global $PDOX;
        $p = $this->p();
        $reg = $this->registration(null, 'XOR tool');
        $key = $this->id['keyA'];

        $keyRow = $PDOX->queryReturnError(
            "INSERT INTO {$p}lti_tool_deployment
                (registration_id, key_id, org_id, context_id, created_at)
             VALUES (:registration_id, :key_id, NULL, NULL, NOW())",
            array(':registration_id' => $reg, ':key_id' => $key)
        );
        $this->assertTrue((bool) $keyRow->success, (string) $keyRow->errorImplode);
        $this->assertSqlRejected(
            "INSERT INTO {$p}lti_tool_deployment
                (registration_id, key_id, org_id, context_id, created_at)
             VALUES (:registration_id, :key_id, NULL, NULL, NOW())",
            array(':registration_id' => $reg, ':key_id' => $key),
            'lti_tool_deployment_const_3'
        );
        $this->assertSqlRejected(
            "INSERT INTO {$p}lti_tool_deployment
                (registration_id, key_id, org_id, context_id, created_at)
             VALUES (:registration_id, :key_id, :org_id, :context_id, NOW())",
            array(
                ':registration_id' => $reg,
                ':key_id' => $key,
                ':org_id' => $this->id['csA'],
                ':context_id' => $this->id['eecs280'],
            ),
            'lti_tool_deployment_chk_1'
        );

        $orgRow = $PDOX->queryReturnError(
            "INSERT INTO {$p}lti_tool_deployment
                (registration_id, key_id, org_id, context_id, created_at)
             VALUES (:registration_id, :key_id, :org_id, NULL, NOW())",
            array(
                ':registration_id' => $reg,
                ':key_id' => $key,
                ':org_id' => $this->id['csA'],
            )
        );
        $this->assertTrue((bool) $orgRow->success, (string) $orgRow->errorImplode);

        $courseRow = $PDOX->queryReturnError(
            "INSERT INTO {$p}lti_tool_deployment
                (registration_id, key_id, org_id, context_id, created_at)
             VALUES (:registration_id, :key_id, NULL, :context_id, NOW())",
            array(
                ':registration_id' => $reg,
                ':key_id' => $key,
                ':context_id' => $this->id['free101'],
            )
        );
        $this->assertTrue((bool) $courseRow->success, (string) $courseRow->errorImplode);
    }

    public function testCascadesKeepUnrelatedDeployments(): void
    {
        global $PDOX;
        $p = $this->p();
        $wide = $this->registration(null, 'Wide');
        $other = $this->registration(null, 'Other');
        $scoped = $this->registration($this->id['aiA'], 'Scoped to AI Lab');

        $ai = ToolDeploymentService::createDeployment($wide, $this->id['aiA'], null);
        $cs = ToolDeploymentService::createDeployment($wide, $this->id['csA'], null);
        $direct = ToolDeploymentService::createDeployment($other, null, $this->id['eecs280']);
        $scopedDeployment = ToolDeploymentService::createDeployment($scoped, $this->id['aiA'], null);

        OrgService::deleteOrg($this->id['aiA']);
        $this->assertFalse($this->deploymentExists($ai));
        $scopedRow = $PDOX->rowDie(
            "SELECT org_id, context_id, key_id FROM {$p}lti_tool_deployment WHERE tool_deployment_id = :tool_deployment_id",
            array(':tool_deployment_id' => $scopedDeployment)
        );
        $this->assertSame($this->id['csA'], (int) $scopedRow['org_id']);
        $this->assertNull($scopedRow['context_id']);
        $this->assertSame($this->id['keyA'], (int) $scopedRow['key_id']);
        $released = ToolRegistrationService::findRegistration($scoped);
        $this->assertNotNull($released);
        $this->assertNull($released['org_id']);
        $this->assertTrue($this->deploymentExists($cs));
        $this->assertTrue($this->deploymentExists($direct));
        $this->assertNotNull(ToolRegistrationService::findRegistration($wide));

        $stmt = $PDOX->queryReturnError(
            "DELETE FROM {$p}lti_context WHERE context_id = :context_id",
            array(':context_id' => $this->id['eecs280'])
        );
        $this->assertTrue((bool) $stmt->success, (string) $stmt->errorImplode);
        $this->assertFalse($this->deploymentExists($direct));
        $this->assertTrue($this->deploymentExists($cs));

        $stmt = $PDOX->queryReturnError(
            "DELETE FROM {$p}lti_tool_registration WHERE registration_id = :registration_id",
            array(':registration_id' => $wide)
        );
        $this->assertTrue((bool) $stmt->success, (string) $stmt->errorImplode);
        $this->assertFalse($this->deploymentExists($cs));
        $this->assertNotNull(ToolRegistrationService::findRegistration($other));
    }

    public function testManyTargetsAndDuplicateRejection(): void
    {
        global $PDOX;
        $p = $this->p();
        $turnitin = $this->registration(null, 'Turnitin');
        $gradescope = $this->registration(null, 'Gradescope');

        ToolDeploymentService::createDeployment($turnitin, $this->id['csA'], null);
        ToolDeploymentService::createDeployment($turnitin, $this->id['meA'], null);
        ToolDeploymentService::createDeployment($turnitin, null, $this->id['eecs280']);
        ToolDeploymentService::createDeployment($turnitin, null, $this->id['free101']);
        ToolDeploymentService::createDeployment($gradescope, $this->id['csA'], null);

        $onCs = $PDOX->rowDie(
            "SELECT COUNT(*) AS n FROM {$p}lti_tool_deployment WHERE org_id = :org_id",
            array(':org_id' => $this->id['csA'])
        );
        $this->assertSame(2, (int) $onCs['n']);
        $forTurnitin = $PDOX->rowDie(
            "SELECT COUNT(*) AS n FROM {$p}lti_tool_deployment WHERE registration_id = :registration_id",
            array(':registration_id' => $turnitin)
        );
        $this->assertSame(4, (int) $forTurnitin['n']);

        $this->assertSqlRejected(
            "INSERT INTO {$p}lti_tool_deployment
                (registration_id, key_id, org_id, context_id, created_at)
             VALUES (:registration_id, :key_id, :org_id, NULL, NOW())",
            array(
                ':registration_id' => $turnitin,
                ':key_id' => $this->id['keyA'],
                ':org_id' => $this->id['csA'],
            ),
            'Duplicate'
        );

        $this->expectException(\InvalidArgumentException::class);
        ToolDeploymentService::createDeployment($turnitin, null, $this->id['eecs280']);
    }

    public function testContextVisibilityFollowsAncestorDeployments(): void
    {
        $toolA = $this->registration(null, 'Tool A');
        $toolB = $this->registration(null, 'Tool B');
        $toolC = $this->registration(null, 'Tool C');
        $toolD = $this->registration(null, 'Tool D');

        ToolDeploymentService::createDeployment($toolA, $this->id['csA'], null);
        ToolDeploymentService::createDeployment($toolB, null, $this->id['eecs280']);
        ToolDeploymentService::createDeployment($toolC, $this->id['engineeringA'], null);
        ToolDeploymentService::createDeployment($toolD, null, $this->id['free101']);

        $visible = ToolDeploymentService::getRegistrationsForContext($this->id['eecs280']);
        $this->assertSame(array('Tool A', 'Tool B', 'Tool C'), $this->titles($visible));
        global $PDOX;
        $sql = (string) $PDOX->PDOX_LastSqlQuery;
        $this->assertStringContainsString('WITH RECURSIVE', $sql);
        $this->assertStringContainsString('org_ancestors', $sql);
        $this->assertStringContainsString('key_id', $sql);
        $this->assertStringContainsString('IN (SELECT org_id FROM org_ancestors)', $sql);
        $this->assertStringNotContainsString(':org_0', $sql);
        $this->assertCount(3, ToolDeploymentService::getDeploymentsForContext($this->id['eecs280']));
        $this->assertSame(
            array('Tool A', 'Tool C'),
            $this->titles(ToolDeploymentService::getRegistrationsForContext($this->id['eecs281']))
        );
        $this->assertSame(
            array('Tool C'),
            $this->titles(ToolDeploymentService::getRegistrationsForContext($this->id['me250']))
        );
        $this->assertNotContains(
            'Tool A',
            $this->titles(ToolDeploymentService::getRegistrationsForContext($this->id['me250']))
        );
        $this->assertSame(
            array(),
            ToolDeploymentService::getRegistrationsForContext($this->id['hist101'])
        );
        $free = ToolDeploymentService::getRegistrationsForContext($this->id['free101']);
        $this->assertSame(array('Tool D'), $this->titles($free));
        $this->assertStringContainsString('WITH RECURSIVE', (string) $PDOX->PDOX_LastSqlQuery);
        $this->assertSame(
            array(),
            ToolDeploymentService::getRegistrationsForContext($this->id['eecs183'])
        );
    }

    public function testDeletingAnOrgBubblesItsDeploymentToTheParentThenTheKey(): void
    {
        global $PDOX;
        $p = $this->p();
        $tool = $this->registration(null, 'Bubble');
        $deployment = ToolDeploymentService::createDeployment($tool, $this->id['aiA'], null);

        OrgService::deleteOrg($this->id['aiA']);
        $this->assertSame($this->id['csA'], $this->deploymentOrg($deployment));

        OrgService::deleteOrg($this->id['csA']);
        $this->assertSame($this->id['engineeringA'], $this->deploymentOrg($deployment));

        OrgService::deleteOrg($this->id['engineeringA']);
        $this->assertSame($this->id['universityA'], $this->deploymentOrg($deployment));

        OrgService::deleteOrg($this->id['universityA']);
        $row = $PDOX->rowDie(
            "SELECT org_id, context_id, key_id FROM {$p}lti_tool_deployment WHERE tool_deployment_id = :tool_deployment_id",
            array(':tool_deployment_id' => $deployment)
        );
        $this->assertNull($row['org_id']);
        $this->assertNull($row['context_id']);
        $this->assertSame($this->id['keyA'], (int) $row['key_id']);
        $this->assertSame(
            array('Bubble'),
            $this->titles(ToolDeploymentService::getRegistrationsForContext($this->id['free101']))
        );
        $this->assertSame(
            array(),
            ToolDeploymentService::getRegistrationsForContext($this->id['eecs183'])
        );
    }

    public function testKeyDeploymentIsVisibleAcrossTheKey(): void
    {
        $tool = $this->registration(null, 'Key tool');
        ToolDeploymentService::createDeployment($tool, null, null);

        $this->assertSame(
            array('Key tool'),
            $this->titles(ToolDeploymentService::getRegistrationsForContext($this->id['eecs280']))
        );
        $this->assertSame(
            array('Key tool'),
            $this->titles(ToolDeploymentService::getRegistrationsForContext($this->id['free101']))
        );
        $this->assertSame(
            array(),
            ToolDeploymentService::getRegistrationsForContext($this->id['eecs183'])
        );

        $scoped = $this->registration($this->id['engineeringA'], 'Engineering only');
        $this->expectException(\InvalidArgumentException::class);
        ToolDeploymentService::createDeployment($scoped, null, null);
    }

    public function testDirectAndAncestorDeploymentCollapseToOneRegistration(): void
    {
        $tool = $this->registration(null, 'Both');
        ToolDeploymentService::createDeployment($tool, $this->id['engineeringA'], null);
        ToolDeploymentService::createDeployment($tool, null, $this->id['eecs280']);

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
            "SELECT lti_version, lti11_key, lti11_secret, lti11_url, client_id
             FROM {$p}lti_tool_registration WHERE registration_id = :registration_id",
            array(':registration_id' => $lti11)
        );
        $this->assertSame('1.1', $row['lti_version']);
        $this->assertSame('consumer', $row['lti11_key']);
        $this->assertSame('secret', $row['lti11_secret']);
        $this->assertSame('https://tool.example/launch', $row['lti11_url']);
        $this->assertNull($row['client_id']);

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

    public function testDatabaseRejectsCrossTenantRegistrationAndDeployment(): void
    {
        $p = $this->p();
        $reg = $this->registration(null, 'Tenant A tool');

        $this->expectRejection(function () {
            ToolRegistrationService::createRegistration($this->id['keyA'], 'Borrowed', $this->id['csB']);
        });

        $this->assertSqlRejected(
            "INSERT INTO {$p}lti_tool_registration (key_id, org_id, title, created_at)
             VALUES (:key_id, :org_id, 'Borrowed', NOW())",
            array(':key_id' => $this->id['keyA'], ':org_id' => $this->id['csB']),
            'lti_tool_registration_ibfk_2'
        );
        $this->assertSqlRejected(
            "INSERT INTO {$p}lti_tool_deployment
                (registration_id, key_id, org_id, context_id, created_at)
             VALUES (:registration_id, :key_id, :org_id, NULL, NOW())",
            array(
                ':registration_id' => $reg,
                ':key_id' => $this->id['keyA'],
                ':org_id' => $this->id['csB'],
            ),
            'lti_tool_deployment_ibfk_2'
        );
        $this->assertSqlRejected(
            "INSERT INTO {$p}lti_tool_deployment
                (registration_id, key_id, org_id, context_id, created_at)
             VALUES (:registration_id, :key_id, NULL, :context_id, NOW())",
            array(
                ':registration_id' => $reg,
                ':key_id' => $this->id['keyB'],
                ':context_id' => $this->id['eecs183'],
            ),
            'lti_tool_deployment_ibfk_1'
        );
    }

    public function testDirectHoldingsCountOnlyRowsOnThatOrg(): void
    {
        $csTool = $this->registration($this->id['csA'], 'CS tool');
        $wide = $this->registration(null, 'Campus tool');
        $engineeringTool = $this->registration($this->id['engineeringA'], 'Engineering tool');
        ToolDeploymentService::createDeployment($csTool, $this->id['csA'], null, 'deploy-cs');
        ToolDeploymentService::createDeployment($wide, $this->id['aiA'], null);
        ToolDeploymentService::createDeployment($wide, null, $this->id['eecs280'], 'deploy-280');
        ToolDeploymentService::createDeployment($wide, null, null, 'deploy-key');

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

    private function deploymentOrg(int $toolDeploymentId): int
    {
        global $PDOX;
        $row = $PDOX->rowDie(
            "SELECT org_id FROM {$this->p()}lti_tool_deployment WHERE tool_deployment_id = :tool_deployment_id",
            array(':tool_deployment_id' => $toolDeploymentId)
        );
        return (int) $row['org_id'];
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
            $this->fail('Expected the deployment to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertNotSame('', $ex->getMessage());
        }
    }
}

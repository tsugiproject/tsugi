<?php

use Tsugi\Services\Outbound\ToolDeploymentGrant;
use Tsugi\Services\Outbound\ToolDeploymentService;
use Tsugi\Services\Outbound\ToolRegistrationDocument;
use Tsugi\Services\Outbound\ToolRegistrationService;

require_once __DIR__.'/../Org/PlatformSchemaCase.php';

class ToolDeploymentGrantTest extends PlatformSchemaCase
{
    private const SCORE = 'https://purl.imsglobal.org/spec/lti-ags/scope/score';
    private const LINEITEM = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';
    private const ROSTER = 'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly';

    protected function setUp(): void
    {
        parent::setUp();
        global $PDOX;
        $p = $this->p();
        if ( $PDOX->metadata($p.'lti_tool_deployment_claim') === false
            || $PDOX->metadata($p.'lti_tool_deployment_scope') === false ) {
            $this->markTestSkipped('Deployment grant tables are missing. Run php admin/upgrade.php.');
        }
    }

    public function testADeploymentAllowsASubsetOfTheRequest(): void
    {
        $registrationId = $this->lti13();
        $first = ToolDeploymentService::createDeployment($registrationId, 'one');
        $second = ToolDeploymentService::createDeployment($registrationId, 'two');

        ToolDeploymentGrant::allowClaim($first, 'name');
        ToolDeploymentGrant::allowClaim($first, 'email');
        ToolDeploymentGrant::allowScope($first, self::SCORE);
        ToolDeploymentGrant::allowClaim($second, 'email');
        ToolDeploymentGrant::allowScope($second, self::ROSTER);

        $this->assertSame(array('email', 'name'), ToolDeploymentGrant::allowedClaims($first));
        $this->assertSame(array(self::SCORE), ToolDeploymentGrant::allowedScopes($first));
        $this->assertSame(array('email'), ToolDeploymentGrant::allowedClaims($second));
        $this->assertSame(array(self::ROSTER), ToolDeploymentGrant::allowedScopes($second));

        ToolDeploymentGrant::blockClaim($first, 'name');
        ToolDeploymentGrant::blockScope($first, self::SCORE);
        $this->assertSame(array('email'), ToolDeploymentGrant::allowedClaims($first));
        $this->assertSame(array(), ToolDeploymentGrant::allowedScopes($first));
    }

    public function testGrantsCannotExceedTheRequestOrRepeat(): void
    {
        $deploymentId = $this->onlyDeployment($this->lti11());
        $this->expectRejection(function () use ($deploymentId) {
            ToolDeploymentGrant::allowClaim($deploymentId, 'iss');
        });
        $this->expectRejection(function () use ($deploymentId) {
            ToolDeploymentGrant::allowClaim($deploymentId, 'sub');
        });
        $this->expectRejection(function () use ($deploymentId) {
            ToolDeploymentGrant::allowClaim($deploymentId, 'picture');
        });
        $this->expectRejection(function () use ($deploymentId) {
            ToolDeploymentGrant::allowScope($deploymentId, self::LINEITEM);
        });
        ToolDeploymentGrant::allowClaim($deploymentId, 'name');
        $this->expectRejection(function () use ($deploymentId) {
            ToolDeploymentGrant::allowClaim($deploymentId, 'name');
        });
        $this->expectRejection(function () use ($deploymentId) {
            ToolDeploymentGrant::blockScope($deploymentId, self::SCORE);
        });
    }

    public function testReplacingTheDocumentDropsGrantsThatAreNoLongerRequested(): void
    {
        $registrationId = $this->lti11();
        $deploymentId = $this->onlyDeployment($registrationId);
        ToolDeploymentGrant::allowClaim($deploymentId, 'name');
        ToolDeploymentGrant::allowClaim($deploymentId, 'email');
        ToolDeploymentGrant::allowScope($deploymentId, self::SCORE);
        ToolDeploymentGrant::allowScope($deploymentId, self::ROSTER);

        $document = ToolRegistrationDocument::registrationDocument($registrationId);
        $document[ToolRegistrationDocument::TOOL_CONFIGURATION]['claims'] = array('iss', 'sub', 'email');
        $document['scope'] = self::ROSTER;
        ToolRegistrationDocument::storeDocument($registrationId, $document);

        $this->assertSame(array('email'), ToolDeploymentGrant::allowedClaims($deploymentId));
        $this->assertSame(array(self::ROSTER), ToolDeploymentGrant::allowedScopes($deploymentId));
    }

    public function testAGrantCannotMoveToAnotherRegistration(): void
    {
        $home = $this->lti11();
        $other = $this->lti11();
        $deploymentId = $this->onlyDeployment($home);
        $p = $this->p();
        $this->assertSqlRejected(
            "INSERT INTO {$p}lti_tool_deployment_claim
                (tool_deployment_id, registration_id, key_id, claim, created_at)
             VALUES (:tool_deployment_id, :registration_id, :key_id, :claim, NOW())",
            array(
                ':tool_deployment_id' => $deploymentId,
                ':registration_id' => $other,
                ':key_id' => $this->id['keyA'],
                ':claim' => 'email',
            ),
            'lti_tool_deployment_claim_ibfk_1'
        );
    }

    public function testDeletingADeploymentRemovesItsGrants(): void
    {
        global $PDOX;
        $registrationId = $this->lti11();
        $deploymentId = $this->onlyDeployment($registrationId);
        ToolDeploymentGrant::allowClaim($deploymentId, 'email');
        ToolDeploymentGrant::allowScope($deploymentId, self::SCORE);

        $stmt = $PDOX->queryReturnError(
            "DELETE FROM {$this->p()}lti_tool_deployment WHERE tool_deployment_id = :tool_deployment_id",
            array(':tool_deployment_id' => $deploymentId)
        );
        $this->assertTrue((bool) $stmt->success, (string) $stmt->errorImplode);
        $claims = $PDOX->rowDie(
            "SELECT claim FROM {$this->p()}lti_tool_deployment_claim WHERE tool_deployment_id = :tool_deployment_id",
            array(':tool_deployment_id' => $deploymentId)
        );
        $scopes = $PDOX->rowDie(
            "SELECT scope FROM {$this->p()}lti_tool_deployment_scope WHERE tool_deployment_id = :tool_deployment_id",
            array(':tool_deployment_id' => $deploymentId)
        );
        $this->assertFalse(is_array($claims));
        $this->assertFalse(is_array($scopes));
    }

    private function lti13(): int
    {
        $registrationId = ToolRegistrationService::createRegistration($this->id['keyA'], 'Modern');
        ToolRegistrationDocument::storeDocument($registrationId, array(
            'scope' => self::SCORE.' '.self::ROSTER,
            ToolRegistrationDocument::TOOL_CONFIGURATION => array(
                'claims' => array('iss', 'sub', 'name', 'email'),
                'messages' => array(
                    array('type' => 'LtiResourceLinkRequest'),
                ),
            ),
        ));
        return $registrationId;
    }

    private function lti11(): int
    {
        return ToolRegistrationService::createRegistration($this->id['keyA'], 'Old tool', null, null, array(
            'lti_version' => '1.1',
            'lti11_key' => 'grant-'.bin2hex(random_bytes(4)),
            'lti11_secret' => 'grant-secret',
            'lti11_url' => 'https://tool.example/launch',
            'claims' => array('name', 'email'),
            'scopes' => array(self::SCORE, self::ROSTER),
        ));
    }

    private function onlyDeployment(int $registrationId): int
    {
        global $PDOX;
        $row = $PDOX->rowDie(
            "SELECT tool_deployment_id FROM {$this->p()}lti_tool_deployment
             WHERE registration_id = :registration_id",
            array(':registration_id' => $registrationId)
        );
        $this->assertIsArray($row);
        return (int) $row['tool_deployment_id'];
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

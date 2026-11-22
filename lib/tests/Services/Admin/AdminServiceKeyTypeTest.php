<?php

use Tsugi\Services\Admin\AdminService;

class AdminServiceKeyTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyKeyIsDraft() {
        $this->assertSame('Draft', AdminService::keyTypeLabel(array()));
    }

    public function testConsumerKeyWithoutSecretIsLti11Draft() {
        $this->assertSame('LTI 1.1 / Draft', AdminService::keyTypeLabel(array(
            'key_key' => 'school',
            'secret' => null,
        )));
        $this->assertSame('LTI 1.1 / Draft', AdminService::keyTypeLabel(array(
            'key_key' => 'school',
            'secret' => '',
        )));
    }

    public function testConsumerKeyAndSecretIsLti11() {
        $this->assertSame('LTI 1.1', AdminService::keyTypeLabel(array(
            'key_key' => 'school',
            'secret' => 's3cret',
        )));
    }

    public function testIssuerMissingClientKeysetOrTokenIsLti13Draft() {
        $base = array(
            'lms_issuer' => 'https://lms.example',
            'lms_client' => 'client-1',
            'lms_oidc_auth' => 'https://lms.example/oidc',
            'lms_keyset_url' => 'https://lms.example/keyset',
            'lms_token_url' => 'https://lms.example/token',
        );
        $this->assertSame('LTI 1.3', AdminService::keyTypeLabel($base));

        $this->assertSame('LTI 1.3 / Draft', AdminService::keyTypeLabel(array(
            'lms_issuer' => 'https://lms.example',
        )));

        $no_client = $base;
        $no_client['lms_client'] = '';
        $this->assertSame('LTI 1.3 / Draft', AdminService::keyTypeLabel($no_client));

        $no_keyset = $base;
        $no_keyset['lms_keyset_url'] = null;
        $this->assertSame('LTI 1.3 / Draft', AdminService::keyTypeLabel($no_keyset));

        $no_token = $base;
        $no_token['lms_token_url'] = null;
        $this->assertSame('LTI 1.3 / Draft', AdminService::keyTypeLabel($no_token));

        $no_oidc = $base;
        $no_oidc['lms_oidc_auth'] = '';
        $this->assertSame('LTI 1.3 / Draft', AdminService::keyTypeLabel($no_oidc));
    }

    public function testDeploymentWithoutIssuerIsDraft() {
        $this->assertSame('Draft', AdminService::keyTypeLabel(array(
            'deploy_key' => 'deploy-1',
        )));
    }

    public function testBothProtocolsDraftOnce() {
        $this->assertSame('LTI 1.1 / LTI 1.3 / Draft', AdminService::keyTypeLabel(array(
            'key_key' => 'school',
            'secret' => '',
            'lms_issuer' => 'https://lms.example',
            'lms_client' => 'client-1',
        )));
    }
}

<?php

use Tsugi\Services\Lti\PlatformObservation;

class PlatformObservationTest extends \PHPUnit\Framework\TestCase
{
    public function testMergeKeepsAValueTheLaunchLeftOut(): void
    {
        $current = PlatformObservation::blank();
        $current['platform_name'] = 'School One';
        $current['platform_guid'] = 'guid-1';
        $observed = PlatformObservation::blank();
        $observed['platform_version'] = '1.2';

        $merged = PlatformObservation::merge($current, $observed);

        $this->assertSame('School One', $merged['platform_name']);
        $this->assertSame('guid-1', $merged['platform_guid']);
        $this->assertSame('1.2', $merged['platform_version']);
        $this->assertTrue(PlatformObservation::same($current, $observed) === false);
    }

    public function testIdenticalLaunchDoesNotCountAsAChange(): void
    {
        $current = PlatformObservation::blank();
        $current['platform_name'] = 'School One';
        $current['platform_family'] = 'canvas';
        $observed = PlatformObservation::scalarsFromToolPlatform((object) array(
            'guid' => null,
            'name' => 'School One',
            'product_family_code' => 'canvas',
            'deployment_id' => 'deploy-9',
        ));

        $this->assertNull($observed['platform_guid']);
        $this->assertTrue(PlatformObservation::same($current, $observed));
    }

    public function testToolPlatformIgnoresFieldsThatAreNotPlatformScalars(): void
    {
        $row = PlatformObservation::scalarsFromToolPlatform(array(
            'guid' => 'abc',
            'name' => 'Example',
            'contact_email' => 'admin@school.edu',
            'product_family_code' => 'Moodle',
            'version' => '4.1',
            'url' => 'https://school.example',
            'description' => 'The campus LMS',
            'email' => 'student@school.edu',
            'deployment_id' => '42',
        ));

        $this->assertSame('abc', $row['platform_guid']);
        $this->assertSame('Example', $row['platform_name']);
        $this->assertSame('admin@school.edu', $row['platform_contact_email']);
        $this->assertSame('Moodle', $row['platform_family']);
        $this->assertSame('4.1', $row['platform_version']);
        $this->assertSame('https://school.example', $row['platform_url']);
        $this->assertSame('The campus LMS', $row['platform_description']);
        $this->assertArrayNotHasKey('email', $row);
        $this->assertArrayNotHasKey('deployment_id', $row);
    }

    public function testRegistrationScalarsComeFromThePlatformBlockOnly(): void
    {
        $openid = (object) array(
            'issuer' => 'https://lms.example',
            'authorization_endpoint' => 'https://lms.example/auth',
            'https://purl.imsglobal.org/spec/lti-platform-configuration' => (object) array(
                'product_family_code' => 'ExampleLMS',
                'version' => '9',
                'guid' => 'plat-1',
            ),
        );
        $response = (object) array(
            'client_id' => 'client-1',
            'client_name' => 'Tsugi',
            'contacts' => array('owner@tsugi.org'),
            'https://purl.imsglobal.org/spec/lti-tool-configuration' => (object) array(
                'product_family_code' => 'tsugi.org',
                'description' => 'Our tool',
            ),
        );

        $row = PlatformObservation::scalarsFromRegistration($openid);

        $this->assertSame('ExampleLMS', $row['platform_family']);
        $this->assertSame('9', $row['platform_version']);
        $this->assertSame('plat-1', $row['platform_guid']);
        $this->assertNull($row['platform_name']);
        $this->assertNull($row['platform_contact_email']);
        $this->assertNotSame('tsugi.org', $row['platform_family']);
        $this->assertNotSame('Tsugi', $row['platform_name']);
    }

    public function testRegistrationJsonDropsSecretsAndKeepsEndpoints(): void
    {
        $openid = (object) array(
            'issuer' => 'https://lms.example',
            'authorization_endpoint' => 'https://lms.example/auth',
            'registration_token' => 'should-not-remain',
        );
        $response = (object) array(
            'client_id' => 'client-1',
            'registration_access_token' => 'token-value',
            'client_secret' => 'secret-value',
        );

        $json = PlatformObservation::registrationJson($openid, $response);

        $this->assertIsString($json);
        $this->assertStringContainsString('https://lms.example/auth', $json);
        $this->assertStringContainsString('client-1', $json);
        $this->assertStringNotContainsString('token-value', $json);
        $this->assertStringNotContainsString('secret-value', $json);
        $this->assertStringNotContainsString('should-not-remain', $json);
        $this->assertStringNotContainsString('registration_access_token', $json);
    }

    public function testLaunchJsonIsTheClaimAndIssuer(): void
    {
        $json = PlatformObservation::launchJson(
            array('guid' => 'abc', 'name' => 'School'),
            'https://lms.example'
        );

        $this->assertIsString($json);
        $this->assertStringContainsString('School', $json);
        $this->assertStringContainsString('https://lms.example', $json);
        $decoded = json_decode($json, true);
        $this->assertSame(array('tool_platform', 'issuer'), array_keys($decoded));
    }

    public function testUnicodeInAsciiColumnsIsFolded(): void
    {
        if ( ! class_exists(\Transliterator::class) ) {
            $this->markTestSkipped('Transliterator is not available');
        }
        $row = PlatformObservation::scalarsFromToolPlatform(array(
            'guid' => "school-\u{00E9}",
            'name' => 'École',
            'product_family_code' => 'Möödlé',
            'version' => '4.1-bêta',
            'contact_email' => 'admin@école.edu',
            'description' => 'École',
        ));

        $this->assertSame('school-e', $row['platform_guid']);
        $this->assertSame('Moodle', $row['platform_family']);
        $this->assertSame('4.1-beta', $row['platform_version']);
        $this->assertSame('admin@ecole.edu', $row['platform_contact_email']);
        $this->assertSame('École', $row['platform_name']);
        $this->assertSame('École', $row['platform_description']);
        $this->assertSame('xue xiao', PlatformObservation::toPrintableAscii('学校', 64));
        $this->assertNull(PlatformObservation::toPrintableAscii('😀', 64));
    }

    public function testNewClientSharesTheCurrentSnapshotNotTheOriginalRegistration(): void
    {
        $this->assertSame(85, PlatformObservation::clientShareId(85, 42, false));
        $this->assertSame(85, PlatformObservation::clientShareId(null, 85, true));
        $this->assertNull(PlatformObservation::clientShareId(null, 85, false));
        $this->assertNull(PlatformObservation::clientShareId(null, null, true));
    }

    public function testLaunchSqlIsALeftJoinOfScalarColumns(): void
    {
        $select = PlatformObservation::launchSelectSql('t_', 'k');
        $join = PlatformObservation::launchJoinSql('t_', 'k');

        $this->assertStringStartsWith('LEFT JOIN t_lti_platform AS plat ON plat.platform_id = k.platform_id', $join);
        $this->assertStringContainsString('k.platform_id AS key_platform_id', $select);
        $this->assertStringContainsString('k.lti_client_id AS key_lti_client_id', $select);
        $this->assertStringContainsString('plat.platform_name AS plat_platform_name', $select);
        $this->assertStringNotContainsString('platform_json', $select);
        $this->assertStringNotContainsString('registration_json', $select);
        $this->assertSame('', PlatformObservation::launchJoinSql('bad prefix', 'k'));
    }
}

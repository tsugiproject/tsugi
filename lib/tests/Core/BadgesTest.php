<?php

require_once("src/Core/Badges.php");
require_once("src/Config/ConfigInfo.php");

use \Tsugi\Core\Badges;

class BadgesTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $originalPDOX;
    
    protected function setUp(): void
    {
        global $CFG, $PDOX;
        
        // Save original globals
        $this->originalCFG = $CFG;
        $this->originalPDOX = $PDOX;
        
        // Set up test CFG
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->wwwroot = 'http://localhost';
        $CFG->apphome = 'http://localhost/app';
        $CFG->servicename = 'Test Service';
        $CFG->badge_url = 'http://localhost/badges';
        $CFG->badge_assert_salt = 'test-salt-12345';
        $CFG->badge_issuer_email = 'issuer@example.com';
        $CFG->badge_include_legacy = false;
        // Required for badge generation methods
        $CFG->badge_encrypt_password = 'test-encrypt-password-123456789012345678901234567890';
        $CFG->badge_path = sys_get_temp_dir(); // Use temp directory for tests
    }
    
    protected function tearDown(): void
    {
        global $CFG, $PDOX;
        
        // Restore original globals
        $CFG = $this->originalCFG;
        $PDOX = $this->originalPDOX;
    }
    
    /**
     * Test OB2 Assertion generation
     */
    public function testGetOb2Assertion()
    {
        global $CFG;
        
        $encrypted = 'test-encrypted-id';
        $date = '2024-01-15T10:30:00Z';
        $code = 'test-badge';
        $badge = (object)['title' => 'Test Badge'];
        $title = 'Test Course';
        $email = 'student@example.com';
        
        $result = Badges::getOb2Assertion($encrypted, $date, $code, $badge, $title, $email);
        
        $this->assertIsString($result, 'Should return JSON string');
        
        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded, 'Should decode to array');
        $this->assertEquals(Badges::OB2_CONTEXT, $decoded['@context'], 'Should have OB2 context');
        $this->assertEquals('Assertion', $decoded['type'], 'Should be Assertion type');
        $this->assertEquals($date, $decoded['issuedOn'], 'Should have correct issuedOn date');
        $this->assertEquals('http://localhost/assertions/badge/test-badge.json', $decoded['badge'], 'Should have correct badge URL');
        $this->assertEquals('http://localhost/badges/test-badge.png', $decoded['image'], 'Should have correct image URL');
        
        // Check recipient
        $this->assertIsArray($decoded['recipient'], 'Recipient should be array');
        $this->assertEquals('email', $decoded['recipient']['type'], 'Recipient type should be email');
        $this->assertTrue($decoded['recipient']['hashed'], 'Recipient should be hashed');
        $this->assertEquals($CFG->badge_assert_salt, $decoded['recipient']['salt'], 'Should have correct salt');
        $expectedHash = 'sha256$' . hash('sha256', $email . $CFG->badge_assert_salt);
        $this->assertEquals($expectedHash, $decoded['recipient']['identity'], 'Should have correct hashed identity');
        
        // Check evidence
        $this->assertIsArray($decoded['evidence'], 'Evidence should be array');
        $this->assertCount(1, $decoded['evidence'], 'Should have one evidence entry');
        $this->assertEquals('Evidence', $decoded['evidence'][0]['type'], 'Evidence type should be Evidence');
        $this->assertEquals($CFG->apphome, $decoded['evidence'][0]['id'], 'Evidence ID should be apphome');
        $expectedNarrative = "Completed Test Badge in course Test Course at Test Service";
        $this->assertEquals($expectedNarrative, $decoded['evidence'][0]['narrative'], 'Should have correct narrative');
        
        // Check verification
        $this->assertEquals('hosted', $decoded['verification']['type'], 'Verification type should be hosted');
    }
    
    /**
     * Test OB2 Assertion date normalization (converts +00:00 to Z)
     */
    public function testGetOb2AssertionDateNormalization()
    {
        global $CFG;
        
        $encrypted = 'test-encrypted-id';
        $dateWithOffset = '2024-01-15T10:30:00+00:00';
        $dateExpected = '2024-01-15T10:30:00Z';
        $code = 'test-badge';
        $badge = (object)['title' => 'Test Badge'];
        $title = 'Test Course';
        $email = 'student@example.com';
        
        $result = Badges::getOb2Assertion($encrypted, $dateWithOffset, $code, $badge, $title, $email);
        $decoded = json_decode($result, true);
        
        // Date should be normalized to Z format
        $this->assertEquals($dateExpected, $decoded['issuedOn'], 'Date should be normalized to Z format');
        
        // Test with date already in Z format
        $dateZ = '2024-01-15T10:30:00Z';
        $result2 = Badges::getOb2Assertion($encrypted, $dateZ, $code, $badge, $title, $email);
        $decoded2 = json_decode($result2, true);
        $this->assertEquals($dateZ, $decoded2['issuedOn'], 'Date already in Z format should remain unchanged');
    }
    
    /**
     * Test OB2 Badge Class generation
     */
    public function testGetOb2Badge()
    {
        global $CFG;
        
        $encrypted = 'test-encrypted-id';
        $code = 'test-badge';
        $badge = (object)['title' => 'Test Badge'];
        $title = 'Test Course';
        
        $result = Badges::getOb2Badge($encrypted, $code, $badge, $title);
        
        $this->assertIsString($result, 'Should return JSON string');
        
        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded, 'Should decode to array');
        $this->assertEquals(Badges::OB2_CONTEXT, $decoded['@context'], 'Should have OB2 context');
        $this->assertEquals('BadgeClass', $decoded['type'], 'Should be BadgeClass type');
        $this->assertEquals('Test Badge', $decoded['name'], 'Should have correct name');
        $this->assertEquals('http://localhost/badges/test-badge.png', $decoded['image'], 'Should have correct image URL');
        $this->assertEquals('http://localhost/assertions/badge/test-badge.json', $decoded['id'], 'Should have correct badge URL');
        $this->assertEquals('http://localhost/assertions/issuer.json', $decoded['issuer'], 'Should have correct issuer URL');
        
        // Check criteria
        $this->assertIsArray($decoded['criteria'], 'Criteria should be array');
        $this->assertEquals($CFG->apphome, $decoded['criteria']['id'], 'Criteria ID should be apphome');
        $expectedNarrative = "Completed Test Badge in course Test Course at Test Service";
        $this->assertEquals($expectedNarrative, $decoded['criteria']['narrative'], 'Should have correct criteria narrative');
        $this->assertEquals($expectedNarrative, $decoded['description'], 'Description should match narrative');
    }
    
    /**
     * Test OB2 Issuer generation
     */
    public function testGetOb2Issuer()
    {
        global $CFG;
        
        $result = Badges::getOb2Issuer();
        
        $this->assertIsString($result, 'Should return JSON string');
        
        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded, 'Should decode to array');
        $this->assertEquals(Badges::OB2_CONTEXT, $decoded['@context'], 'Should have OB2 context');
        $this->assertEquals('Issuer', $decoded['type'], 'Should be Issuer type');
        $this->assertEquals('http://localhost/assertions/issuer.json', $decoded['id'], 'Should have correct issuer URL');
        // Should use getBadgeOrganization() if available, otherwise servicename
        $expectedName = method_exists($CFG, 'getBadgeOrganization') ? $CFG->getBadgeOrganization() : $CFG->servicename;
        $this->assertEquals($expectedName, $decoded['name'], 'Should have correct issuer name');
        $this->assertEquals($CFG->apphome, $decoded['url'], 'Should have correct URL');
        $this->assertEquals($CFG->badge_issuer_email, $decoded['email'], 'Should have correct email');
    }
    
    /**
     * Test OB2 Issuer with badge_organization set
     */
    public function testGetOb2IssuerWithBadgeOrganization()
    {
        global $CFG;
        $CFG->badge_organization = 'Custom Badge Organization';
        
        $result = Badges::getOb2Issuer();
        $decoded = json_decode($result, true);
        
        $this->assertEquals('Custom Badge Organization', $decoded['name'], 'Should use badge_organization when set');
    }
    
    /**
     * Test OB2 Issuer with servicedesc fallback
     */
    public function testGetOb2IssuerWithServicedesc()
    {
        global $CFG;
        $CFG->badge_organization = null;
        $CFG->servicedesc = 'Service Description';
        $CFG->servicename = 'Test Service';
        
        $result = Badges::getOb2Issuer();
        $decoded = json_decode($result, true);
        
        $expectedName = method_exists($CFG, 'getBadgeOrganization') 
            ? $CFG->getBadgeOrganization() 
            : $CFG->servicename;
        $this->assertEquals($expectedName, $decoded['name'], 'Should use getBadgeOrganization() fallback when badge_organization not set');
    }
    
    /**
     * Test OB2 Issuer with default email when not configured
     */
    public function testGetOb2IssuerDefaultEmail()
    {
        global $CFG;
        unset($CFG->badge_issuer_email);
        
        $result = Badges::getOb2Issuer();
        $decoded = json_decode($result, true);
        
        $this->assertEquals(Badges::DEFAULT_ISSUER_EMAIL, $decoded['email'], 'Should use default email when not configured');
    }
    
    /**
     * Test OB3 Assertion (Verifiable Credential) generation
     */
    public function testGetOb3Assertion()
    {
        global $CFG;
        
        $encrypted = 'test-encrypted-id';
        $date = '2024-01-15T10:30:00Z';
        $code = 'test-badge';
        $badge = (object)['title' => 'Test Badge'];
        $title = 'Test Course';
        $email = 'student@example.com';
        
        $result = Badges::getOb3Assertion($encrypted, $date, $code, $badge, $title, $email);
        
        $this->assertIsString($result, 'Should return JSON string');
        
        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded, 'Should decode to array');
        
        // Check context
        $this->assertIsArray($decoded['@context'], 'Should have array context');
        $this->assertContains(Badges::OB3_CREDENTIALS_CONTEXT, $decoded['@context'], 'Should have credentials context');
        $this->assertContains(Badges::OB3_OPENBADGES_CONTEXT, $decoded['@context'], 'Should have OpenBadges context');
        
        // Check type
        $this->assertIsArray($decoded['type'], 'Type should be array');
        $this->assertContains('VerifiableCredential', $decoded['type'], 'Should be VerifiableCredential');
        $this->assertContains('OpenBadgeCredential', $decoded['type'], 'Should be OpenBadgeCredential');
        
        // Check credential ID
        $expectedCredentialId = 'http://localhost/assertions/test-encrypted-id.vc.json';
        $this->assertEquals($expectedCredentialId, $decoded['id'], 'Should have correct credential ID');
        
        // Check issuer
        $this->assertIsArray($decoded['issuer'], 'Issuer should be array');
        $this->assertEquals('http://localhost/assertions/issuer.json?format=ob3', $decoded['issuer']['id'], 'Should have correct issuer ID');
        $this->assertEquals('Profile', $decoded['issuer']['type'], 'Issuer type should be Profile');
        $this->assertEquals($CFG->servicename, $decoded['issuer']['name'], 'Should have correct issuer name');
        $this->assertEquals($CFG->apphome, $decoded['issuer']['url'], 'Should have correct issuer URL');
        
        // Check issuance date
        $this->assertEquals($date, $decoded['issuanceDate'], 'Should have correct issuance date');
        
        // Check credential subject
        $this->assertIsArray($decoded['credentialSubject'], 'Credential subject should be array');
        $this->assertEquals('mailto:' . $email, $decoded['credentialSubject']['id'], 'Should have correct subject ID');
        $this->assertEquals('AchievementSubject', $decoded['credentialSubject']['type'], 'Subject type should be AchievementSubject');
        
        // Check achievement
        $this->assertIsArray($decoded['credentialSubject']['achievement'], 'Achievement should be array');
        $this->assertEquals('Achievement', $decoded['credentialSubject']['achievement']['type'], 'Achievement type should be Achievement');
        $this->assertEquals('http://localhost/assertions/badge/test-badge.json?format=ob3', $decoded['credentialSubject']['achievement']['id'], 'Should have correct achievement ID');
        $this->assertEquals('Test Badge', $decoded['credentialSubject']['achievement']['name'], 'Should have correct achievement name');
        
        // Check achievement image
        $this->assertIsArray($decoded['credentialSubject']['achievement']['image'], 'Achievement image should be array');
        $this->assertEquals('Image', $decoded['credentialSubject']['achievement']['image']['type'], 'Image type should be Image');
        $this->assertEquals('http://localhost/badges/test-badge.png', $decoded['credentialSubject']['achievement']['image']['id'], 'Should have correct image ID');
        
        // Check evidence
        $this->assertIsArray($decoded['credentialSubject']['evidence'], 'Evidence should be array');
        $this->assertCount(1, $decoded['credentialSubject']['evidence'], 'Should have one evidence entry');
        $this->assertEquals('Evidence', $decoded['credentialSubject']['evidence'][0]['type'], 'Evidence type should be Evidence');
    }
    
    /**
     * Test OB3 Assertion with legacy assertion included
     */
    public function testGetOb3AssertionWithLegacy()
    {
        global $CFG;
        $CFG->badge_include_legacy = true;
        
        $encrypted = 'test-encrypted-id';
        $date = '2024-01-15T10:30:00Z';
        $code = 'test-badge';
        $badge = (object)['title' => 'Test Badge'];
        $title = 'Test Course';
        $email = 'student@example.com';
        
        $result = Badges::getOb3Assertion($encrypted, $date, $code, $badge, $title, $email);
        $decoded = json_decode($result, true);
        
        $this->assertArrayHasKey('extensions', $decoded['credentialSubject'], 'Should have extensions when legacy enabled');
        $this->assertArrayHasKey('legacyAssertion', $decoded['credentialSubject']['extensions'], 'Should have legacyAssertion');
        $expectedLegacy = 'http://localhost/badges/assert.php?id=test-encrypted-id';
        $this->assertEquals($expectedLegacy, $decoded['credentialSubject']['extensions']['legacyAssertion'], 'Should have correct legacy assertion URL');
    }
    
    /**
     * Test OB3 Achievement generation
     */
    public function testGetOb3Achievement()
    {
        global $CFG;
        
        $encrypted = 'test-encrypted-id';
        $code = 'test-badge';
        $badge = (object)['title' => 'Test Badge'];
        $title = 'Test Course';
        
        $result = Badges::getOb3Achievement($encrypted, $code, $badge, $title);
        
        $this->assertIsString($result, 'Should return JSON string');
        
        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded, 'Should decode to array');
        
        // Check context
        $this->assertIsArray($decoded['@context'], 'Should have array context');
        $this->assertContains(Badges::OB3_CREDENTIALS_CONTEXT, $decoded['@context'], 'Should have credentials context');
        $this->assertContains(Badges::OB3_OPENBADGES_CONTEXT, $decoded['@context'], 'Should have OpenBadges context');
        
        // Check type
        $this->assertEquals('Achievement', $decoded['type'], 'Should be Achievement type');
        $this->assertEquals('http://localhost/assertions/badge/test-badge.json?format=ob3', $decoded['id'], 'Should have correct achievement ID');
        $this->assertEquals('Test Badge', $decoded['name'], 'Should have correct name');
        
        // Check image
        $this->assertIsArray($decoded['image'], 'Image should be array');
        $this->assertEquals('Image', $decoded['image']['type'], 'Image type should be Image');
        $this->assertEquals('http://localhost/badges/test-badge.png', $decoded['image']['id'], 'Should have correct image ID');
        
        // Check issuer
        $this->assertIsArray($decoded['issuer'], 'Issuer should be array');
        $this->assertEquals('http://localhost/assertions/issuer.json?format=ob3', $decoded['issuer']['id'], 'Should have correct issuer ID');
        $this->assertEquals('Profile', $decoded['issuer']['type'], 'Issuer type should be Profile');
        
        // Check criteria
        $this->assertIsArray($decoded['criteria'], 'Criteria should be array');
        $this->assertEquals($CFG->apphome, $decoded['criteria']['id'], 'Criteria ID should be apphome');
        $expectedNarrative = "Completed Test Badge in course Test Course at Test Service";
        $this->assertEquals($expectedNarrative, $decoded['criteria']['narrative'], 'Should have correct criteria narrative');
        $this->assertEquals($expectedNarrative, $decoded['description'], 'Description should match narrative');
    }
    
    /**
     * Test OB3 Issuer generation
     */
    public function testGetOb3Issuer()
    {
        global $CFG;
        
        $result = Badges::getOb3Issuer();
        
        $this->assertIsString($result, 'Should return JSON string');
        
        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded, 'Should decode to array');
        
        // Check context
        $this->assertIsArray($decoded['@context'], 'Should have array context');
        $this->assertContains(Badges::OB3_CREDENTIALS_CONTEXT, $decoded['@context'], 'Should have credentials context');
        $this->assertContains(Badges::OB3_OPENBADGES_CONTEXT, $decoded['@context'], 'Should have OpenBadges context');
        
        // Check type
        $this->assertEquals('Profile', $decoded['type'], 'Should be Profile type');
        $this->assertEquals('http://localhost/assertions/issuer.json?format=ob3', $decoded['id'], 'Should have correct issuer URL');
        // Should use getBadgeOrganization() if available, otherwise servicename
        $expectedName = method_exists($CFG, 'getBadgeOrganization') ? $CFG->getBadgeOrganization() : $CFG->servicename;
        $this->assertEquals($expectedName, $decoded['name'], 'Should have correct issuer name');
        $this->assertEquals($CFG->apphome, $decoded['url'], 'Should have correct URL');
        $this->assertEquals($CFG->badge_issuer_email, $decoded['email'], 'Should have correct email');
    }
    
    /**
     * Test OB3 Issuer with badge_organization set
     */
    public function testGetOb3IssuerWithBadgeOrganization()
    {
        global $CFG;
        $CFG->badge_organization = 'Custom Badge Organization';
        
        $result = Badges::getOb3Issuer();
        $decoded = json_decode($result, true);
        
        $this->assertEquals('Custom Badge Organization', $decoded['name'], 'Should use badge_organization when set');
    }
    
    /**
     * Test OB3 Issuer with servicedesc fallback
     */
    public function testGetOb3IssuerWithServicedesc()
    {
        global $CFG;
        $CFG->badge_organization = null;
        $CFG->servicedesc = 'Service Description';
        $CFG->servicename = 'Test Service';
        
        $result = Badges::getOb3Issuer();
        $decoded = json_decode($result, true);
        
        $expectedName = method_exists($CFG, 'getBadgeOrganization') 
            ? $CFG->getBadgeOrganization() 
            : $CFG->servicename;
        $this->assertEquals($expectedName, $decoded['name'], 'Should use getBadgeOrganization() fallback when badge_organization not set');
    }
    
    /**
     * Test OB3 Issuer with default email when not configured
     */
    public function testGetOb3IssuerDefaultEmail()
    {
        global $CFG;
        unset($CFG->badge_issuer_email);
        
        $result = Badges::getOb3Issuer();
        $decoded = json_decode($result, true);
        
        $this->assertEquals(Badges::DEFAULT_ISSUER_EMAIL, $decoded['email'], 'Should use default email when not configured');
    }
    
    /**
     * Test URL encoding in badge codes
     */
    public function testUrlEncoding()
    {
        global $CFG;
        
        $encrypted = 'test%20encrypted';
        $code = 'badge with spaces';
        $badge = (object)['title' => 'Test Badge'];
        $title = 'Test Course';
        $email = 'student@example.com';
        $date = '2024-01-15T10:30:00Z';
        
        // Test OB2 assertion URL encoding
        $result = Badges::getOb2Assertion($encrypted, $date, $code, $badge, $title, $email);
        $decoded = json_decode($result, true);
        
        // The assertion ID should be URL encoded
        $expectedAssertionId = 'http://localhost/assertions/test%2520encrypted.json';
        $this->assertEquals($expectedAssertionId, $decoded['id'], 'Assertion ID should be URL encoded');
        
        // Badge URL should be URL encoded (urlencode uses + for spaces)
        $expectedBadgeUrl = 'http://localhost/assertions/badge/badge+with+spaces.json';
        $this->assertEquals($expectedBadgeUrl, $decoded['badge'], 'Badge URL should be URL encoded');
    }
    
    /**
     * Test JSON encoding options (pretty print, unescaped slashes)
     */
    public function testJsonEncodingOptions()
    {
        global $CFG;
        
        $encrypted = 'test-id';
        $date = '2024-01-15T10:30:00Z';
        $code = 'test-badge';
        $badge = (object)['title' => 'Test Badge'];
        $title = 'Test Course';
        $email = 'student@example.com';
        
        $result = Badges::getOb2Assertion($encrypted, $date, $code, $badge, $title, $email);
        
        // Check that JSON is pretty printed (contains newlines)
        $this->assertStringContainsString("\n", $result, 'JSON should be pretty printed');
        
        // Check that slashes are not escaped (URLs should have / not \/)
        $this->assertStringNotContainsString('\\/', $result, 'Slashes should not be escaped');
        $this->assertStringContainsString('http://localhost', $result, 'Should contain unescaped URLs');
    }
    
    /**
     * Test constants are defined correctly
     */
    public function testConstants()
    {
        $this->assertEquals('https://w3id.org/openbadges/v2', Badges::OB2_CONTEXT, 'OB2_CONTEXT should be correct');
        $this->assertEquals('https://www.w3.org/ns/credentials/v2', Badges::OB3_CREDENTIALS_CONTEXT, 'OB3_CREDENTIALS_CONTEXT should be correct');
        $this->assertEquals('https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.3.json', Badges::OB3_OPENBADGES_CONTEXT, 'OB3_OPENBADGES_CONTEXT should be correct');
        $this->assertEquals('badge_issuer_email_not_set@example.com', Badges::DEFAULT_ISSUER_EMAIL, 'DEFAULT_ISSUER_EMAIL should be correct');
    }
}


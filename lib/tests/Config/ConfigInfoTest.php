<?php

require "src/Config/ConfigInfo.php";

use \Tsugi\Config\ConfigInfo;

class ConfigInfoTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor() {
        $dirroot = realpath(dirname(__FILE__));
        $wwwroot = 'http://localhost:8888/tsugi';
        $CFG = new ConfigInfo($dirroot, $wwwroot);
        
        $this->assertEquals($wwwroot, $CFG->wwwroot);
        $this->assertEquals($dirroot, $CFG->dirroot);
        $this->assertIsArray($CFG->extensions);
        $this->assertEmpty($CFG->extensions);
        $this->assertEquals('https://static.tsugi.org', $CFG->staticroot);
        $this->assertEquals($dirroot . '/storage/', $CFG->lumen_storage);
    }

    public function testConstructorWithDataroot() {
        $dirroot = realpath(dirname(__FILE__));
        $wwwroot = 'http://localhost:8888/tsugi';
        $dataroot = '/tmp/tsugi_blobs';
        $CFG = new ConfigInfo($dirroot, $wwwroot, $dataroot);
        
        $this->assertEquals($wwwroot, $CFG->wwwroot);
        $this->assertEquals($dirroot, $CFG->dirroot);
        // Note: dataroot is not stored in constructor, it's just a parameter
        // but we can verify the object was created correctly
        $this->assertIsArray($CFG->extensions);
    }

    public function testGetExtensionWithExistingKey() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://localhost:8888/tsugi');
        $CFG->extensions['test_key'] = 'test_value';
        
        $result = $CFG->getExtension('test_key');
        $this->assertEquals('test_value', $result);
    }

    public function testGetExtensionWithNonExistingKey() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://localhost:8888/tsugi');
        
        $result = $CFG->getExtension('non_existing_key');
        $this->assertNull($result);
    }

    public function testGetExtensionWithDefault() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://localhost:8888/tsugi');
        
        $result = $CFG->getExtension('non_existing_key', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    public function testGetExtensionWithNullDefault() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://localhost:8888/tsugi');
        
        $result = $CFG->getExtension('non_existing_key', null);
        $this->assertNull($result);
    }

    public function testSetExtension() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://localhost:8888/tsugi');
        
        $CFG->setExtension('new_key', 'new_value');
        $this->assertEquals('new_value', $CFG->extensions['new_key']);
        $this->assertEquals('new_value', $CFG->getExtension('new_key'));
    }

    public function testSetExtensionOverwrite() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://localhost:8888/tsugi');
        
        $CFG->setExtension('key', 'value1');
        $this->assertEquals('value1', $CFG->getExtension('key'));
        
        $CFG->setExtension('key', 'value2');
        $this->assertEquals('value2', $CFG->getExtension('key'));
    }

    public function testPromotedExtensionsWriteCoreProperties() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://localhost:8888/tsugi');
        $this->assertFalse($CFG->show_courses_widget);
        $this->assertFalse($CFG->show_course_catalog);
        $this->assertFalse($CFG->home_path);

        $CFG->setExtension('show_courses_widget', true);
        $CFG->setExtension('show_course_catalog', true);
        $CFG->setExtension('home_path', 'https://example.com/home');
        $this->assertTrue($CFG->show_courses_widget);
        $this->assertTrue($CFG->show_course_catalog);
        $this->assertSame('https://example.com/home', $CFG->home_path);
        $this->assertTrue($CFG->getExtension('show_courses_widget'));
        $this->assertTrue($CFG->getExtension('show_course_catalog'));
        $this->assertSame('https://example.com/home', $CFG->getExtension('home_path'));
        $this->assertTrue($CFG->extensions['show_courses_widget']);
        $this->assertTrue($CFG->extensions['show_course_catalog']);
        $this->assertSame('https://example.com/home', $CFG->extensions['home_path']);
    }

    public function testPromotedPropertiesReadableViaGetExtension() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://localhost:8888/tsugi');
        $CFG->show_courses_widget = true;
        $CFG->show_course_catalog = true;
        $CFG->home_path = 'https://example.com/home';
        $this->assertTrue($CFG->getExtension('show_courses_widget'));
        $this->assertTrue($CFG->getExtension('show_course_catalog'));
        $this->assertSame('https://example.com/home', $CFG->getExtension('home_path'));
    }

    public function testServerPrefixWithWwwroot() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $CFG->apphome = null;
        
        $prefix = $CFG->serverPrefix();
        $this->assertEquals('example.com/tsugi', $prefix);
    }

    public function testServerPrefixWithApphome() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $CFG->apphome = 'http://example.com/app';
        
        $prefix = $CFG->serverPrefix();
        $this->assertEquals('example.com/app', $prefix);
    }

    public function testServerPrefixWithHttps() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'https://example.com/tsugi');
        $CFG->apphome = null;
        
        $prefix = $CFG->serverPrefix();
        $this->assertEquals('example.com/tsugi', $prefix);
    }

    public function testServerPrefixWithLongPrefix() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        // Create a very long apphome that should trigger MD5 hashing
        $longApphome = 'http://' . str_repeat('a', 60) . '.example.com/app';
        $CFG->apphome = $longApphome;
        
        $prefix = $CFG->serverPrefix();
        // Should be MD5 hash (32 characters)
        $this->assertEquals(32, strlen($prefix));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $prefix);
    }

    public function testServerPrefixWithEmptyApphome() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $CFG->apphome = '';
        
        $prefix = $CFG->serverPrefix();
        // Empty string should fall back to wwwroot
        $this->assertEquals('example.com/tsugi', $prefix);
    }

    public function testLocalhostWithLocalhost() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://localhost:8888/tsugi');
        $this->assertTrue($CFG->localhost());
    }

    public function testLocalhostWith127() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://127.0.0.1:8888/tsugi');
        $this->assertTrue($CFG->localhost());
    }

    public function testLocalhostWithRemote() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $this->assertFalse($CFG->localhost());
    }

    public function testCanAuthor() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://localhost:8888/tsugi');
        $this->assertTrue($CFG->canAuthor());
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $this->assertFalse($CFG->canAuthor());
        $CFG->author_allow = true;
        $this->assertTrue($CFG->canAuthor());
    }

    public function testHasSiteLessons() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $this->assertFalse($CFG->lessons);
        $this->assertTrue(isset($CFG->lessons));
        $this->assertFalse($CFG->hasSiteContextTitle());
        $this->assertFalse($CFG->hasSiteLessons());
        $CFG->lessons = '';
        $this->assertFalse($CFG->hasSiteLessons());
        $CFG->lessons = '/path/to/lessons.json';
        $this->assertFalse($CFG->hasSiteLessons());
        $CFG->context_title = 'Web Applications for Everybody';
        $this->assertTrue($CFG->hasSiteContextTitle());
        $this->assertTrue($CFG->hasSiteLessons());
        $CFG->lessons = '   ';
        $this->assertFalse($CFG->hasSiteLessons());
        $CFG->lessons = '/path/to/lessons.json';
        $CFG->context_title = '   ';
        $this->assertFalse($CFG->hasSiteContextTitle());
        $this->assertFalse($CFG->hasSiteLessons());
    }

    public function testGetHomeUrlFallsBackToApphomeThenWwwroot() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $this->assertFalse($CFG->hasHomeUrl());
        $this->assertEquals('http://example.com/tsugi', $CFG->getHomeUrl());

        $CFG->apphome = 'http://example.com/app';
        $this->assertTrue($CFG->hasHomeUrl());
        $this->assertEquals('http://example.com/app', $CFG->getHomeUrl());

        $CFG->apphome = '  ';
        $this->assertFalse($CFG->hasHomeUrl());
        $this->assertEquals('http://example.com/tsugi', $CFG->getHomeUrl());
    }

    public function testGetHomeUrlUsesHomePath() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $CFG->apphome = 'http://example.com/app';
        $CFG->home_path = $CFG->wwwroot;
        $this->assertTrue($CFG->hasHomeUrl());
        $this->assertEquals('http://example.com/tsugi', $CFG->getHomeUrl());

        $CFG->home_path = 'https://example.org/wherever/';
        $this->assertEquals('https://example.org/wherever', $CFG->getHomeUrl());

        $CFG->apphome = false;
        $CFG->home_path = '/';
        $this->assertTrue($CFG->hasHomeUrl());
        $this->assertEquals('/', $CFG->getHomeUrl());

        $CFG->home_path = '   ';
        $this->assertFalse($CFG->hasHomeUrl());
        $this->assertEquals('http://example.com/tsugi', $CFG->getHomeUrl());
    }

    public function testGetLoginUrl() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $loginUrl = $CFG->getLoginUrl();
        $this->assertEquals('http://example.com/tsugi/login', $loginUrl);
    }

    public function testGetLoginUrlWithPort() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://localhost:8888/tsugi');
        $loginUrl = $CFG->getLoginUrl();
        $this->assertEquals('http://localhost:8888/tsugi/login', $loginUrl);
    }

    public function testGetLoginUrlUsesApphomeWhenSet() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $CFG->apphome = 'https://local.py4e.com';
        $loginUrl = $CFG->getLoginUrl();
        $this->assertEquals('https://local.py4e.com/login', $loginUrl);
    }

    public function testGetBadgeOrganizationWithBadgeOrganizationSet() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://localhost:8888/tsugi');
        $CFG->badge_organization = 'Custom Organization Name';
        
        $result = $CFG->getBadgeOrganization();
        $this->assertEquals('Custom Organization Name', $result);
    }

    public function testGetBadgeOrganizationWithServicedesc() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://localhost:8888/tsugi');
        $CFG->servicedesc = 'Service Description';
        $CFG->servicename = 'Service Name';
        $CFG->badge_organization = null; // Explicitly not set
        
        $result = $CFG->getBadgeOrganization();
        $this->assertEquals('Service Description (Service Name)', $result);
    }

    public function testGetBadgeOrganizationWithServicenameOnly() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://localhost:8888/tsugi');
        $CFG->servicename = 'Service Name';
        $CFG->servicedesc = false; // Not set
        $CFG->badge_organization = null; // Explicitly not set
        
        $result = $CFG->getBadgeOrganization();
        $this->assertEquals('Service Name', $result);
    }

    public function testGetBadgeOrganizationWithEmptyBadgeOrganization() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://localhost:8888/tsugi');
        $CFG->badge_organization = '';
        $CFG->servicedesc = 'Service Description';
        $CFG->servicename = 'Service Name';
        
        // Empty string should fall back to servicedesc (servicename)
        $result = $CFG->getBadgeOrganization();
        $this->assertEquals('Service Description (Service Name)', $result);
    }

    public function testGetBadgeOrganizationFallbackOrder() {
        $CFG = new ConfigInfo(realpath(dirname(__FILE__)), 'http://localhost:8888/tsugi');
        
        // Test 1: badge_organization takes precedence
        $CFG->badge_organization = 'Badge Org';
        $CFG->servicedesc = 'Service Desc';
        $CFG->servicename = 'Service Name';
        $result = $CFG->getBadgeOrganization();
        $this->assertEquals('Badge Org', $result);
        
        // Test 2: servicedesc (servicename) when badge_organization not set
        $CFG->badge_organization = null;
        $result = $CFG->getBadgeOrganization();
        $this->assertEquals('Service Desc (Service Name)', $result);
        
        // Test 3: servicename only when both badge_organization and servicedesc not set
        $CFG->servicedesc = false;
        $result = $CFG->getBadgeOrganization();
        $this->assertEquals('Service Name', $result);
    }
}

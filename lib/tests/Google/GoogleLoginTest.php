<?php

require_once "src/Google/GoogleLogin.php";
require_once "src/Config/ConfigInfo.php";

use \Tsugi\Google\GoogleLogin;

class GoogleLoginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test getLoginUrl() constructs correct URL with all parameters
     */
    public function testGetLoginUrl() {
        $client_id = 'test-client-id.apps.googleusercontent.com';
        $client_secret = 'test-secret';
        $redirect = 'http://example.com/callback';
        $state = 'test-state-123';
        
        $glog = new GoogleLogin($client_id, $client_secret, $redirect);
        $loginUrl = $glog->getLoginUrl($state);
        
        // Verify URL structure
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/auth?', $loginUrl, 
            'Login URL should start with Google OAuth endpoint');
        
        // Verify required parameters are present
        $this->assertStringContainsString('client_id=' . urlencode($client_id), $loginUrl,
            'Login URL should contain client_id');
        $this->assertStringContainsString('redirect_uri=' . urlencode($redirect), $loginUrl,
            'Login URL should contain redirect_uri');
        $this->assertStringContainsString('state=' . urlencode($state), $loginUrl,
            'Login URL should contain state');
        $this->assertStringContainsString('response_type=code', $loginUrl,
            'Login URL should contain response_type=code');
        $this->assertStringContainsString('scope=email%20profile', $loginUrl,
            'Login URL should contain email and profile scopes');
        $this->assertStringContainsString('include_granted_scopes=true', $loginUrl,
            'Login URL should include granted scopes');
    }
    
    /**
     * Test getLoginUrl() with openid_realm parameter
     */
    public function testGetLoginUrlWithOpenIdRealm() {
        $client_id = 'test-client-id.apps.googleusercontent.com';
        $client_secret = 'test-secret';
        $redirect = 'http://example.com/callback';
        $openid_realm = 'http://example.com';
        $state = 'test-state-456';
        
        $glog = new GoogleLogin($client_id, $client_secret, $redirect, $openid_realm);
        $loginUrl = $glog->getLoginUrl($state);
        
        // Verify openid.realm parameter is included
        $this->assertStringContainsString('openid.realm=' . urlencode($openid_realm), $loginUrl,
            'Login URL should contain openid.realm when provided');
    }
    
    /**
     * Test getLoginUrl() without openid_realm parameter
     */
    public function testGetLoginUrlWithoutOpenIdRealm() {
        $client_id = 'test-client-id.apps.googleusercontent.com';
        $client_secret = 'test-secret';
        $redirect = 'http://example.com/callback';
        $state = 'test-state-789';
        
        $glog = new GoogleLogin($client_id, $client_secret, $redirect);
        $loginUrl = $glog->getLoginUrl($state);
        
        // Verify openid.realm parameter is NOT included
        $this->assertStringNotContainsString('openid.realm=', $loginUrl,
            'Login URL should not contain openid.realm when not provided');
    }
    
    /**
     * Test getLoginUrl() URL encoding of special characters
     */
    public function testGetLoginUrlUrlEncoding() {
        $client_id = 'test@client.id';
        $client_secret = 'test-secret';
        $redirect = 'http://example.com/callback?param=value&other=test';
        $state = 'state with spaces & special chars';
        
        $glog = new GoogleLogin($client_id, $client_secret, $redirect);
        $loginUrl = $glog->getLoginUrl($state);
        
        // Verify URL encoding is applied
        $this->assertStringContainsString('client_id=' . urlencode($client_id), $loginUrl,
            'Client ID should be URL encoded');
        $this->assertStringContainsString('redirect_uri=' . urlencode($redirect), $loginUrl,
            'Redirect URI should be URL encoded');
        $this->assertStringContainsString('state=' . urlencode($state), $loginUrl,
            'State should be URL encoded');
    }
    
    /**
     * Test constructor sets properties correctly
     */
    public function testConstructor() {
        $client_id = 'test-client-id';
        $client_secret = 'test-secret';
        $redirect = 'http://example.com/callback';
        $openid_realm = 'http://example.com';
        
        $glog = new GoogleLogin($client_id, $client_secret, $redirect, $openid_realm);
        
        $this->assertEquals($client_id, $glog->client_id, 'client_id should be set');
        $this->assertEquals($client_secret, $glog->client_secret, 'client_secret should be set');
        $this->assertEquals($redirect, $glog->redirect, 'redirect should be set');
        $this->assertEquals($openid_realm, $glog->openid_realm, 'openid_realm should be set');
    }
    
    /**
     * Test constructor with optional openid_realm parameter
     */
    public function testConstructorWithoutOpenIdRealm() {
        $client_id = 'test-client-id';
        $client_secret = 'test-secret';
        $redirect = 'http://example.com/callback';
        
        $glog = new GoogleLogin($client_id, $client_secret, $redirect);
        
        $this->assertEquals($client_id, $glog->client_id, 'client_id should be set');
        $this->assertEquals($client_secret, $glog->client_secret, 'client_secret should be set');
        $this->assertEquals($redirect, $glog->redirect, 'redirect should be set');
        $this->assertFalse($glog->openid_realm, 'openid_realm should be false when not provided');
    }
}


<?php

require_once("src/Core/Entity.php");
require_once("src/Core/JsonTrait.php");
require_once("src/Core/SessionTrait.php");
require_once("src/Core/Launch.php");
require_once("src/Core/Profile.php");

use \Tsugi\Core\Profile;
use \Tsugi\Core\Launch;

class ProfileTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $profile = new Profile();
        $this->assertInstanceOf(Profile::class, $profile);
        $this->assertSame(0, $profile->premium, 'Premium should default to 0');
        $this->assertFalse($profile->isPremium(), 'isPremium should default to false');
        $this->assertSame(0, $profile->getPremiumLevel());
    }

    public function testPremiumLevels() {
        $profile = new Profile();
        $profile->premium = 2;
        $this->assertTrue($profile->isPremium());
        $this->assertSame(2, $profile->getPremiumLevel());
        $this->assertTrue($profile->isPremiumLevel(1));
        $this->assertTrue($profile->isPremiumLevel(2));
        $this->assertFalse($profile->isPremiumLevel(3));
    }

    public function testNormalizePremiumLevel() {
        $this->assertSame(0, Profile::normalizePremiumLevel(-1));
        $this->assertSame(0, Profile::normalizePremiumLevel('nope'));
        $this->assertSame(3, Profile::normalizePremiumLevel('3'));
    }

    public function testPremiumProviderJson() {
        $profile = new Profile();
        $profile->premium_json = '{"stripe":{"customer_id":"cus_test","subscription_id":"sub_test"},'
            .'"paypal":{"payer_id":"pay_123"}}';

        $this->assertTrue($profile->hasPremiumProvider('stripe'));
        $this->assertTrue($profile->hasPremiumProvider('paypal'));
        $this->assertFalse($profile->hasPremiumProvider('square'));

        $this->assertEquals('cus_test', $profile->getPremiumProviderKey('stripe', 'customer_id'));
        $this->assertEquals('sub_test', $profile->getPremiumProviderKey('stripe', 'subscription_id'));
        $this->assertEquals('pay_123', $profile->getPremiumProviderKey('paypal', 'payer_id'));
        $this->assertFalse($profile->getPremiumProviderKey('stripe', 'missing'));
    }

    public function testFromLaunchRowWithoutProfileId() {
        $launch = new Launch();
        $profile = Profile::fromLaunchRow(array('user_id' => 1), $launch);
        $this->assertInstanceOf(Profile::class, $profile);
        $this->assertSame(0, $profile->id);
        $this->assertFalse($profile->isLinked());
        $this->assertFalse($profile->isPremium());
        $this->assertFalse($profile->hasPremiumProvider('stripe'));
        $this->assertSame($launch, $profile->launch);
    }

    public function testFromLaunchRowWithSessionPremiumFields() {
        $launch = new Launch();
        $LTI = array(
            'profile_id' => 42,
            'profile_premium' => 2,
            'profile_premium_at' => '2026-06-07 12:00:00',
            'profile_premium_json' => '{"stripe":{"customer_id":"cus_abc"}}',
        );
        $profile = Profile::fromLaunchRow($LTI, $launch);
        $this->assertInstanceOf(Profile::class, $profile);
        $this->assertEquals(42, $profile->id);
        $this->assertSame(2, $profile->getPremiumLevel());
        $this->assertTrue($profile->isPremiumLevel(2));
        $this->assertFalse($profile->isPremiumLevel(3));
        $this->assertEquals('2026-06-07 12:00:00', $profile->premium_at);
        $this->assertEquals('cus_abc', $profile->getPremiumProviderKey('stripe', 'customer_id'));
        $this->assertTrue($profile->isLinked());
        $this->assertSame($launch, $profile->launch);
    }
}

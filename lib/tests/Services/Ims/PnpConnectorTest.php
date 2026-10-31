<?php

use Tsugi\Services\Ims\PnpConnector;

class PnpConnectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var bool */
    private $hadBase;

    /** @var bool */
    private $hadEmail;

    /** @var mixed */
    private $oldBase;

    /** @var mixed */
    private $oldEmail;

    /** @var bool */
    private $madeCfg = false;

    protected function setUp(): void
    {
        parent::setUp();
        global $CFG;
        if ( ! isset($CFG) || ! is_object($CFG) ) {
            $CFG = new \stdClass();
            $this->madeCfg = true;
        }
        $this->hadBase = property_exists($CFG, 'lti_pnp_baseurl');
        $this->hadEmail = property_exists($CFG, 'lti_pnp_use_email');
        $this->oldBase = $this->hadBase ? $CFG->lti_pnp_baseurl : null;
        $this->oldEmail = $this->hadEmail ? $CFG->lti_pnp_use_email : null;
        $CFG->lti_pnp_baseurl = false;
        $CFG->lti_pnp_use_email = false;
    }

    protected function tearDown(): void
    {
        global $CFG;
        if ( $this->madeCfg ) {
            unset($GLOBALS['CFG']);
        } else if ( is_object($CFG) ) {
            if ( $this->hadBase ) {
                $CFG->lti_pnp_baseurl = $this->oldBase;
            } else {
                unset($CFG->lti_pnp_baseurl);
            }
            if ( $this->hadEmail ) {
                $CFG->lti_pnp_use_email = $this->oldEmail;
            } else {
                unset($CFG->lti_pnp_use_email);
            }
        }
        parent::tearDown();
    }

    public function testUnsetBaseSendsNoClaim(): void
    {
        global $CFG;
        $this->assertNull(PnpConnector::launchClaim('admin', 'ada@example.edu'));

        $CFG->lti_pnp_baseurl = '   ';
        $this->assertNull(PnpConnector::launchClaim('admin'));

        $CFG->lti_pnp_baseurl = '';
        $this->assertNull(PnpConnector::launchClaim('admin'));

        $CFG->lti_pnp_use_email = true;
        $this->assertNull(PnpConnector::launchClaim('admin', 'ada@example.edu'));
    }

    public function testGuidReplacesThePlaceholder(): void
    {
        global $CFG;
        $guid = '550e8400e29b41d4a716446655440000';
        $CFG->lti_pnp_baseurl = 'https://pnp.amp-up.io/ims/afapnp/v1p0/users/@/activities/default/afapnprecords';
        $CFG->lti_pnp_use_email = true;
        $claim = PnpConnector::launchClaim($guid, '');
        $this->assertSame(
            array(
                'afapnp_endpoint_url' => 'https://pnp.amp-up.io/ims/afapnp/v1p0/users/'.$guid.'/activities/default/afapnprecords',
                'scopes' => array(PnpConnector::SCOPE_PNP_READONLY),
            ),
            $claim
        );

        $CFG->lti_pnp_use_email = false;
        $claim = PnpConnector::launchClaim($guid, 'ada@example.edu');
        $this->assertSame(
            'https://pnp.amp-up.io/ims/afapnp/v1p0/users/'.$guid.'/activities/default/afapnprecords',
            $claim['afapnp_endpoint_url']
        );
    }

    public function testEmailReplacesThePlaceholderWhenAsked(): void
    {
        global $CFG;
        $CFG->lti_pnp_baseurl = ' https://pnp.example.test/users/@/x/@ ';
        $CFG->lti_pnp_use_email = 'true';
        $claim = PnpConnector::launchClaim('550e8400e29b41d4a716446655440000', 'ada@example.edu');
        $this->assertSame(
            'https://pnp.example.test/users/ada@example.edu/x/ada@example.edu',
            $claim['afapnp_endpoint_url']
        );

        $this->assertNull(PnpConnector::launchClaim('', 'ada@example.edu'));
    }
}

<?php

require_once "src/Services/Site/Site.php";

use \Tsugi\Services\Site\Site;

class SiteTest extends \PHPUnit\Framework\TestCase
{
    public function testJsonFlagReadsUseCatalog()
    {
        $this->assertFalse(Site::jsonFlag(null, 'use_catalog'));
        $this->assertFalse(Site::jsonFlag('', 'use_catalog'));
        $this->assertFalse(Site::jsonFlag('not-json', 'use_catalog'));
        $this->assertFalse(Site::jsonFlag('{"use_catalog":false}', 'use_catalog'));
        $this->assertFalse(Site::jsonFlag('{"other":true}', 'use_catalog'));
        $this->assertTrue(Site::jsonFlag('{"use_catalog":true}', 'use_catalog'));
        $this->assertTrue(Site::jsonFlag('{"use_catalog":1}', 'use_catalog'));
    }

    public function testIsEmptyHtml()
    {
        $this->assertTrue(Site::isEmptyHtml(null));
        $this->assertTrue(Site::isEmptyHtml(''));
        $this->assertTrue(Site::isEmptyHtml('<p>&nbsp;</p>'));
        $this->assertFalse(Site::isEmptyHtml('<p>Hello</p>'));
        $this->assertFalse(Site::isEmptyHtml('<p><img src="/x.png" alt=""></p>'));
    }
}

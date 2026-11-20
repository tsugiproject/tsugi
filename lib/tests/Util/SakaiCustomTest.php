<?php

namespace Tsugi\Util;

use PHPUnit\Framework\TestCase;

class SakaiCustomTest extends TestCase {

    public function testSakaiAssignmentDateCustomUsesSakPrefix() {
        $custom = SakaiCustom::sakaiAssignmentDateCustom();
        $this->assertSame('$Sakai.assignment.visibleDate', $custom['sak_visibleDate']);
        $this->assertSame('$Sakai.assignment.openDate', $custom['sak_openDate']);
        $this->assertSame('$Sakai.assignment.dueDate', $custom['sak_dueDate']);
        $this->assertSame('$Sakai.assignment.closeDate', $custom['sak_closeDate']);
        $this->assertSame('$Sakai.assignment.resubmissionAcceptUntil', $custom['sak_resubmissionAcceptUntil']);
        $this->assertArrayNotHasKey('openDate', $custom);
    }

    public function testImsDateCustomUnprefixed() {
        $custom = SakaiCustom::imsDateCustom();
        $this->assertSame('$ResourceLink.submission.startDateTime', $custom['submissionStart']);
        $this->assertArrayNotHasKey('sak_openDate', $custom);
    }

    public function testDeepLinkCustomMergesCommonAndOptional() {
        $custom = SakaiCustom::deepLinkCustom(true);
        $this->assertArrayHasKey('sak_api_url', $custom);
        $this->assertArrayHasKey('sak_openDate', $custom);
        $this->assertArrayHasKey('submissionStart', $custom);
        $this->assertArrayHasKey('coursegroup_id', $custom);

        $minimal = SakaiCustom::deepLinkCustom(false);
        $this->assertArrayNotHasKey('coursegroup_id', $minimal);
        $this->assertArrayHasKey('sak_dueDate', $minimal);
        $this->assertArrayNotHasKey('api_url', $minimal);
    }
}

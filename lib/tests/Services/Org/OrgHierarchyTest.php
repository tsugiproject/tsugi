<?php

use Tsugi\Services\Org\OrgService;

require_once __DIR__.'/PlatformSchemaCase.php';

class OrgHierarchyTest extends PlatformSchemaCase
{
    public function testCreatesRootsAndChildren(): void
    {
        $library = OrgService::createOrg($this->id['keyA'], 'Library');
        $ancestors = OrgService::getAncestors($library);

        $this->assertSame(array('Library'), $this->titles($ancestors));
        $this->assertNull($ancestors[0]['parent_org_id']);
        $this->assertNotSame($this->id['universityA'], $library);

        $child = OrgService::getAncestors($this->id['csA']);
        $this->assertSame($this->id['engineeringA'], $child[1]['org_id']);
        $this->assertSame($this->id['keyA'], $child[0]['key_id']);
    }

    public function testTraversesAncestorsAndDescendantsIncludingSelf(): void
    {
        $this->assertSame(
            array('AI Lab', 'Computer Science', 'Engineering', 'University A'),
            $this->titles(OrgService::getAncestors($this->id['aiA']))
        );
        $this->assertSame(
            array('Engineering', 'Computer Science', 'Mechanical Engineering', 'AI Lab'),
            $this->titles(OrgService::getDescendants($this->id['engineeringA']))
        );
        $this->assertSame(
            array('Computer Science'),
            $this->titles(array(OrgService::getDescendants($this->id['csA'])[0]))
        );

        $this->assertTrue(OrgService::isDescendantOrSelf($this->id['csA'], $this->id['engineeringA']));
        $this->assertTrue(OrgService::isDescendantOrSelf($this->id['engineeringA'], $this->id['engineeringA']));
        $this->assertTrue(OrgService::isDescendantOrSelf($this->id['aiA'], $this->id['engineeringA']));
        $this->assertFalse(OrgService::isDescendantOrSelf($this->id['meA'], $this->id['csA']));
        $this->assertFalse(OrgService::isDescendantOrSelf($this->id['engineeringA'], $this->id['csA']));
        $this->assertFalse(OrgService::isDescendantOrSelf($this->id['lsaA'], $this->id['engineeringA']));
    }

    public function testOrgTypeIsStoredAndDoesNotAffectTheTree(): void
    {
        $ancestors = OrgService::getAncestors($this->id['aiA']);
        $this->assertSame('university', $ancestors[3]['org_type']);
        $this->assertSame('college', $ancestors[2]['org_type']);
        $this->assertNull($ancestors[0]['org_type']);
        $this->assertTrue(OrgService::isDescendantOrSelf($this->id['aiA'], $this->id['universityA']));
    }

    public function testMovesASubtreeThenPromotesItToTheRoot(): void
    {
        OrgService::moveOrg($this->id['engineeringA'], $this->id['lsaA']);

        $this->assertSame(
            array('Computer Science', 'Engineering', 'LSA', 'University A'),
            $this->titles(OrgService::getAncestors($this->id['csA']))
        );
        $this->assertContains('AI Lab', $this->titles(OrgService::getDescendants($this->id['lsaA'])));
        $this->assertContains('Mechanical Engineering', $this->titles(OrgService::getDescendants($this->id['engineeringA'])));

        OrgService::moveOrg($this->id['engineeringA'], null);

        $this->assertSame(
            array('Computer Science', 'Engineering'),
            $this->titles(OrgService::getAncestors($this->id['csA']))
        );
        $engineering = OrgService::getAncestors($this->id['engineeringA']);
        $this->assertSame(array('Engineering'), $this->titles($engineering));
        $this->assertNull($engineering[0]['parent_org_id']);
        $this->assertNotContains(
            'Engineering',
            $this->titles(OrgService::getDescendants($this->id['universityA']))
        );
        $this->assertContains('LSA', $this->titles(OrgService::getDescendants($this->id['universityA'])));
    }

    public function testRejectsCrossTenantLinks(): void
    {
        try {
            OrgService::createOrg($this->id['keyA'], 'Borrowed', $this->id['engineeringB']);
            $this->fail('Expected a cross-tenant parent to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('different tenant', $ex->getMessage());
        }

        try {
            OrgService::moveOrg($this->id['lsaA'], $this->id['engineeringB']);
            $this->fail('Expected a cross-tenant move to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('different tenant', $ex->getMessage());
        }

        try {
            OrgService::placeContext($this->id['eecs280'], $this->id['csB']);
            $this->fail('Expected a cross-tenant course placement to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('different tenant', $ex->getMessage());
        }
    }

    public function testTraversalStaysInsideTheTenant(): void
    {
        $descendants = OrgService::getDescendants($this->id['engineeringA']);
        $ids = array();
        foreach ( $descendants as $org ) {
            $this->assertSame($this->id['keyA'], $org['key_id']);
            $ids[] = $org['org_id'];
        }
        $this->assertNotContains($this->id['csB'], $ids);
        $this->assertNotContains($this->id['engineeringB'], $ids);

        $this->assertSame(
            array('Computer Science', 'Engineering', 'University B'),
            $this->titles(OrgService::getAncestors($this->id['csB']))
        );
        $this->assertFalse(OrgService::isDescendantOrSelf($this->id['csA'], $this->id['engineeringB']));
        $this->assertFalse(OrgService::isDescendantOrSelf($this->id['csB'], $this->id['engineeringA']));
        $this->assertFalse(OrgService::isDescendantOrSelf($this->id['csA'], 0));
    }

    public function testRejectsCycles(): void
    {
        try {
            OrgService::moveOrg($this->id['universityA'], $this->id['csA']);
            $this->fail('Expected a move under a descendant to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('descendants', $ex->getMessage());
        }

        try {
            OrgService::moveOrg($this->id['engineeringA'], $this->id['engineeringA']);
            $this->fail('Expected a self-parent to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('itself', $ex->getMessage());
        }

        $this->assertSame(
            array('University A'),
            array(OrgService::getAncestors($this->id['universityA'])[0]['title'])
        );
        $this->assertNull(OrgService::getAncestors($this->id['universityA'])[0]['parent_org_id']);
    }

    public function testDatabaseRejectsCrossTenantParent(): void
    {
        $p = $this->p();
        $this->assertSqlRejected(
            "UPDATE {$p}lti_org SET parent_org_id = :parent_org_id WHERE org_id = :org_id",
            array(
                ':parent_org_id' => $this->id['engineeringB'],
                ':org_id' => $this->id['engineeringA'],
            ),
            'lti_org_ibfk_2'
        );
    }

    public function testDeletingAParentRemovesTheSubtree(): void
    {
        global $PDOX;
        $p = $this->p();
        $stmt = $PDOX->queryReturnError(
            "DELETE FROM {$p}lti_org WHERE org_id = :org_id",
            array(':org_id' => $this->id['engineeringA'])
        );
        $this->assertTrue((bool) $stmt->success, (string) $stmt->errorImplode);

        $this->assertSame(array(), OrgService::getDescendants($this->id['csA']));
        $this->assertSame(array(), OrgService::getDescendants($this->id['aiA']));
        $this->assertSame(array(), OrgService::getDescendants($this->id['meA']));
        $this->assertSame(
            array('University A', 'LSA'),
            $this->titles(OrgService::getDescendants($this->id['universityA']))
        );

        $course = $PDOX->rowDie(
            "SELECT org_id FROM {$p}lti_context WHERE context_id = :context_id",
            array(':context_id' => $this->id['eecs280'])
        );
        $this->assertNull($course['org_id']);
        $kept = $PDOX->rowDie(
            "SELECT org_id FROM {$p}lti_context WHERE context_id = :context_id",
            array(':context_id' => $this->id['hist101'])
        );
        $this->assertSame($this->id['lsaA'], (int) $kept['org_id']);
    }
}

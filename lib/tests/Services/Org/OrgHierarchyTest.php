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

    public function testDeletingAnOrgReparentsChildrenAndUnplacesOnlyItsOwnCourses(): void
    {
        global $PDOX;
        $p = $this->p();
        $this->assertSqlRejected(
            "DELETE FROM {$p}lti_org WHERE org_id = :org_id",
            array(':org_id' => $this->id['engineeringA']),
            'lti_org_ibfk_2'
        );

        OrgService::placeContext($this->id['free101'], $this->id['engineeringA']);
        OrgService::deleteOrg($this->id['engineeringA']);

        $this->assertSame(array(), OrgService::getDescendants($this->id['engineeringA']));
        $this->assertSame(
            array('Computer Science', 'University A'),
            $this->titles(OrgService::getAncestors($this->id['csA']))
        );
        $this->assertSame(
            array('AI Lab', 'Computer Science', 'University A'),
            $this->titles(OrgService::getAncestors($this->id['aiA']))
        );
        $this->assertContains('Mechanical Engineering', $this->titles(OrgService::getDescendants($this->id['universityA'])));
        $this->assertContains('LSA', $this->titles(OrgService::getDescendants($this->id['universityA'])));

        $course = $PDOX->rowDie(
            "SELECT org_id, key_id FROM {$p}lti_context WHERE context_id = :context_id",
            array(':context_id' => $this->id['eecs280'])
        );
        $this->assertSame($this->id['csA'], (int) $course['org_id']);
        $this->assertSame($this->id['keyA'], (int) $course['key_id']);
        $kept = $PDOX->rowDie(
            "SELECT org_id FROM {$p}lti_context WHERE context_id = :context_id",
            array(':context_id' => $this->id['hist101'])
        );
        $this->assertSame($this->id['lsaA'], (int) $kept['org_id']);
        $unplaced = $PDOX->rowDie(
            "SELECT org_id, key_id FROM {$p}lti_context WHERE context_id = :context_id",
            array(':context_id' => $this->id['free101'])
        );
        $this->assertNull($unplaced['org_id']);
        $this->assertSame($this->id['keyA'], (int) $unplaced['key_id']);
    }

    public function testScopeComposesIntoOneStatement(): void
    {
        global $PDOX;
        $p = $this->p();
        $up = OrgService::ancestorScope($this->id['keyA'], $this->id['csA'], 'org_ancestors');
        $down = OrgService::descendantScope($this->id['keyA'], $this->id['engineeringA'], 'org_descendants');

        $this->assertNotSame(array_keys($up->params()), array_keys($down->params()));
        $this->assertSame($this->id['keyA'], $up->params()[':org_ancestors_key_id']);
        $this->assertSame($this->id['csA'], $up->params()[':org_ancestors_org_id']);
        $this->assertStringContainsString('key_id = :org_ancestors_key_id', $up->cte());
        $this->assertStringContainsString('o.org_id = step.parent_org_id', $up->cte());
        $this->assertStringContainsString('o.parent_org_id = step.org_id', $down->cte());
        $this->assertStringContainsString('o.key_id = step.key_id', $up->cte());
        $this->assertSame(
            'd.org_id IN (SELECT org_id FROM org_ancestors)',
            $up->in('d.org_id')
        );
        $this->assertSame(
            'INNER JOIN org_descendants ON org_descendants.org_id = o.org_id',
            $down->join('o.org_id')
        );

        $sql = "WITH RECURSIVE ".$up->cte().",
            ".$down->cte()."
            SELECT o.title
            FROM {$p}lti_org o
            ".$up->join('o.org_id')."
            WHERE ".$down->in('o.org_id')."
            ORDER BY o.title ASC";
        $rows = $PDOX->allRowsDie($sql, array_merge($up->params(), $down->params()));
        $this->assertSame(array('Computer Science', 'Engineering'), $this->titles($rows));
        $this->assertStringContainsString('WITH RECURSIVE', $PDOX->PDOX_LastSqlQuery);
        $this->assertStringNotContainsString(':org_0', $PDOX->PDOX_LastSqlQuery);

        $otherTenant = OrgService::ancestorScope($this->id['keyB'], $this->id['csA']);
        $foreign = $PDOX->allRowsDie(
            "WITH RECURSIVE ".$otherTenant->cte()." SELECT org_id FROM ".$otherTenant->name(),
            $otherTenant->params()
        );
        $this->assertSame(array(), $foreign);

        $course = OrgService::contextAncestorScope($this->id['eecs280']);
        $courseRows = $PDOX->allRowsDie(
            "WITH RECURSIVE ".$course->cte()."
             SELECT o.title
             FROM ".$course->name()." walk
             INNER JOIN {$p}lti_org o ON o.org_id = walk.org_id
             ORDER BY walk.depth ASC",
            $course->params()
        );
        $this->assertSame(
            array('Computer Science', 'Engineering', 'University A'),
            $this->titles($courseRows)
        );

        $unplaced = OrgService::contextAncestorScope($this->id['free101']);
        $unplacedRows = $PDOX->allRowsDie(
            "WITH RECURSIVE ".$unplaced->cte()." SELECT org_id FROM ".$unplaced->name(),
            $unplaced->params()
        );
        $this->assertSame(array(), $unplacedRows);
    }
}

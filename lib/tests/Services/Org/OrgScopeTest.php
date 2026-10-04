<?php

use Tsugi\Services\Org\OrgScope;
use Tsugi\Services\Org\OrgService;

/**
 * SQL shape of an organization scope. These checks do not need a database.
 */
class OrgScopeTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $CFG;
        if ( ! isset($CFG) || ! is_object($CFG) ) {
            $CFG = new \stdClass();
            $CFG->dbprefix = '';
        }
        if ( ! isset($CFG->dbprefix) ) {
            $CFG->dbprefix = '';
        }
    }

    public function testAncestorAndDescendantScopesStayDistinct(): void
    {
        global $CFG;
        $p = (string) $CFG->dbprefix;
        $up = OrgService::ancestorScope(4, 9, 'org_ancestors');
        $down = OrgService::descendantScope(4, 12, 'org_descendants');

        $sql = "WITH RECURSIVE ".$up->cte().", ".$down->cte();
        $this->assertStringContainsString('org_ancestors AS (', $sql);
        $this->assertStringContainsString('org_descendants AS (', $sql);
        $this->assertStringContainsString("FROM {$p}lti_org", $up->cte());
        $this->assertStringContainsString('WHERE key_id = :org_ancestors_key_id', $up->cte());
        $this->assertStringContainsString('AND org_id = :org_ancestors_org_id', $up->cte());
        $this->assertStringContainsString('o.key_id = step.key_id', $down->cte());
        $this->assertStringContainsString('o.org_id = step.parent_org_id', $up->cte());
        $this->assertStringContainsString('o.parent_org_id = step.org_id', $down->cte());
        $this->assertStringNotContainsString('WITH RECURSIVE', $up->cte());

        $params = array_merge($up->params(), $down->params());
        $this->assertSame(4, $params[':org_ancestors_key_id']);
        $this->assertSame(9, $params[':org_ancestors_org_id']);
        $this->assertSame(4, $params[':org_descendants_key_id']);
        $this->assertSame(12, $params[':org_descendants_org_id']);
        $this->assertCount(4, $params);

        $this->assertSame('d.org_id IN (SELECT org_id FROM org_ancestors)', $up->in('d.org_id'));
        $this->assertSame(
            'INNER JOIN org_descendants ON org_descendants.org_id = d.org_id',
            $down->join('d.org_id')
        );
    }

    public function testContextScopeBindsTheCourseAndKeepsTheTenantOnTheJoin(): void
    {
        global $CFG;
        $p = (string) $CFG->dbprefix;
        $scope = OrgService::contextAncestorScope(15);
        $cte = $scope->cte();

        $this->assertStringContainsString("FROM {$p}lti_context c", $cte);
        $this->assertStringContainsString('o.key_id = c.key_id', $cte);
        $this->assertStringContainsString('o.key_id = step.key_id', $cte);
        $this->assertSame(array(':org_ancestors_context_id' => 15), $scope->params());
    }

    public function testRejectsNamesAndColumnsThatCannotBeIdentifiers(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        OrgScope::checkColumn('d.org_id); DROP TABLE lti_org; --');
    }

    public function testRejectsAScopeNameThatIsNotAnIdentifier(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        OrgService::ancestorScope(1, 2, 'org ancestors');
    }
}

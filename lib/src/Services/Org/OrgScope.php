<?php

namespace Tsugi\Services\Org;

/**
 * One organization and its ancestors or descendants, as a SQL relation.
 *
 * This is not a list of ids. Embed cte() in a larger statement, then filter
 * with in() or join the relation with join(). params() are the bound values.
 * The caller writes WITH RECURSIVE. Two scopes in one statement need different
 * CTE names so the aliases and parameter names do not collide.
 *
 * The anchor limits the walk to one tenant key. The recursive step repeats
 * that limit by joining key_id, so the walk cannot cross tenants.
 */
class OrgScope {

    /** @var string */
    private $name;

    /** @var string */
    private $anchorSql;

    /** @var bool */
    private $upward;

    /** @var string */
    private $orgTable;

    /** @var int */
    private $maxDepth;

    /** @var array<string, int> */
    private $params;

    /**
     * @param string $name CTE alias, already checked
     * @param string $anchorSql anchor SELECT, already limited to one tenant
     * @param bool $upward true walks parents, false walks children
     * @param string $orgTable prefixed lti_org table
     * @param int $maxDepth
     * @param array<string, int> $params
     */
    public function __construct($name, $anchorSql, $upward, $orgTable, $maxDepth, array $params) {
        $this->name = $name;
        $this->anchorSql = $anchorSql;
        $this->upward = $upward ? true : false;
        $this->orgTable = $orgTable;
        $this->maxDepth = (int) $maxDepth;
        $this->params = $params;
    }

    /**
     * @return string CTE name
     */
    public function name() {
        return $this->name;
    }

    /**
     * Recursive CTE body, without the WITH RECURSIVE keyword.
     *
     * @return string
     */
    public function cte() {
        $link = $this->upward
            ? 'o.org_id = step.parent_org_id'
            : 'o.parent_org_id = step.org_id';
        $limit = (int) $this->maxDepth;

        return $this->name." AS (
    ".$this->anchorSql."
    UNION ALL
    SELECT o.org_id, o.parent_org_id, o.key_id, step.depth + 1 AS depth
    FROM ".$this->orgTable." o
    INNER JOIN ".$this->name." step
        ON ".$link."
       AND o.key_id = step.key_id
       AND step.depth < ".$limit."
)";
    }

    /**
     * Filter a column against this set.
     *
     * d.org_id becomes: d.org_id IN (SELECT org_id FROM org_ancestors)
     *
     * @param string $column table alias and column, such as d.org_id
     * @return string
     */
    public function in($column) {
        $column = self::checkColumn($column);
        return $column.' IN (SELECT org_id FROM '.$this->name.')';
    }

    /**
     * Join this set to a column.
     *
     * d.org_id becomes: INNER JOIN org_ancestors ON org_ancestors.org_id = d.org_id
     *
     * @param string $column table alias and column, such as d.org_id
     * @return string
     */
    public function join($column) {
        $column = self::checkColumn($column);
        return 'INNER JOIN '.$this->name.' ON '.$this->name.'.org_id = '.$column;
    }

    /**
     * @return array<string, int>
     */
    public function params() {
        return $this->params;
    }

    /**
     * @param string $name
     * @return string
     */
    public static function checkName($name) {
        $name = (string) $name;
        if ( ! preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,30}$/', $name) ) {
            throw new \InvalidArgumentException('Organization scope name is not usable in SQL.');
        }
        return $name;
    }

    /**
     * @param string $column
     * @return string
     */
    public static function checkColumn($column) {
        $column = (string) $column;
        if ( ! preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $column) ) {
            throw new \InvalidArgumentException('Column name is not usable in organization scope SQL.');
        }
        return $column;
    }
}

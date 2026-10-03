<?php

use Tsugi\Core\LTIX;
use Tsugi\Services\Org\OrgService;

/**
 * Shared MySQL fixture for the organization and outbound-tool tests.
 *
 * Each test runs inside a transaction and rolls it back.
 */
abstract class PlatformSchemaCase extends \PHPUnit\Framework\TestCase
{
    /** @var array<string, int> */
    protected $id = array();

    protected function setUp(): void
    {
        parent::setUp();
        $this->boot();
        $this->requireSchema();
        $this->begin();
        $this->buildFixture();
    }

    protected function tearDown(): void
    {
        global $PDOX;
        if ( isset($PDOX) && is_object($PDOX) && $PDOX->inTransaction() ) {
            $PDOX->rollBack();
        }
        parent::tearDown();
    }

    protected function boot(): void
    {
        global $CFG, $PDOX;
        $root = realpath(dirname(__DIR__, 4));
        if ( $root === false ) {
            $this->fail('Could not resolve the Tsugi checkout.');
        }
        if ( ! isset($CFG) || ! is_object($CFG) || ! isset($CFG->dirroot) || realpath((string) $CFG->dirroot) !== $root ) {
            require_once $root.'/config.php';
        }
        if ( realpath((string) $CFG->dirroot) !== $root ) {
            $this->fail('Refusing to run against a different Tsugi checkout.');
        }
        LTIX::getConnection();
        if ( ! isset($PDOX) || ! is_object($PDOX) ) {
            $this->fail('Database is not available.');
        }
    }

    protected function requireSchema(): void
    {
        global $CFG, $PDOX;
        if ( ! $PDOX->isMySQL() || ! $PDOX->versionAtLeast('8.0.16') ) {
            $this->markTestSkipped('MySQL 8.0.16+ is required.');
        }
        $p = $CFG->dbprefix;
        if ( $PDOX->metadata($p.'lti_org') === false || $PDOX->metadata($p.'lti_tool_deployment') === false ) {
            $this->markTestSkipped('Organization tables are missing. Run php admin/upgrade.php.');
        }
        if ( $PDOX->metadata($p.'lti_tool_deployment_org') === false
            || $PDOX->metadata($p.'lti_tool_deployment_context') === false ) {
            $this->markTestSkipped('Deployment scope tables are missing. Run php admin/upgrade.php.');
        }
        if ( ! $PDOX->columnExists('org_id', $p.'lti_context') ) {
            $this->markTestSkipped('lti_context.org_id is missing. Run php admin/upgrade.php.');
        }
        if ( ! $PDOX->columnExists('owner_org_id', $p.'lti_tool_registration')
            || ! $PDOX->columnExists('owner_context_id', $p.'lti_tool_registration') ) {
            $this->markTestSkipped('Registration owner columns are missing. Run php admin/upgrade.php.');
        }
        $check = $PDOX->rowDie(
            "SELECT CONSTRAINT_NAME AS constraint_name
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND CONSTRAINT_NAME = :constraint_name",
            array(
                ':table_name' => $p.'lti_tool_registration',
                ':constraint_name' => $p.'lti_tool_registration_chk_2',
            )
        );
        if ( ! is_array($check) ) {
            $this->markTestSkipped('Registration owner CHECK constraint is missing. Run php admin/upgrade.php.');
        }
    }

    protected function begin(): void
    {
        global $PDOX;
        if ( $PDOX->inTransaction() ) {
            $PDOX->rollBack();
        }
        $PDOX->beginTransaction();
    }

    protected function buildFixture(): void
    {
        $this->id['keyA'] = $this->insertKey('Tenant A');
        $this->id['keyB'] = $this->insertKey('Tenant B');

        $this->id['universityA'] = OrgService::createOrg($this->id['keyA'], 'University A', null, 'university');
        $this->id['engineeringA'] = OrgService::createOrg($this->id['keyA'], 'Engineering', $this->id['universityA'], 'college');
        $this->id['csA'] = OrgService::createOrg($this->id['keyA'], 'Computer Science', $this->id['engineeringA']);
        $this->id['aiA'] = OrgService::createOrg($this->id['keyA'], 'AI Lab', $this->id['csA']);
        $this->id['meA'] = OrgService::createOrg($this->id['keyA'], 'Mechanical Engineering', $this->id['engineeringA']);
        $this->id['lsaA'] = OrgService::createOrg($this->id['keyA'], 'LSA', $this->id['universityA']);

        $this->id['universityB'] = OrgService::createOrg($this->id['keyB'], 'University B');
        $this->id['engineeringB'] = OrgService::createOrg($this->id['keyB'], 'Engineering', $this->id['universityB']);
        $this->id['csB'] = OrgService::createOrg($this->id['keyB'], 'Computer Science', $this->id['engineeringB']);

        $this->id['eecs280'] = $this->insertContext($this->id['keyA'], 'EECS 280');
        $this->id['eecs281'] = $this->insertContext($this->id['keyA'], 'EECS 281');
        $this->id['me250'] = $this->insertContext($this->id['keyA'], 'ME 250');
        $this->id['hist101'] = $this->insertContext($this->id['keyA'], 'HIST 101');
        $this->id['free101'] = $this->insertContext($this->id['keyA'], 'FREE 101');
        $this->id['eecs183'] = $this->insertContext($this->id['keyB'], 'EECS 183');

        OrgService::placeContext($this->id['eecs280'], $this->id['csA']);
        OrgService::placeContext($this->id['eecs281'], $this->id['csA']);
        OrgService::placeContext($this->id['me250'], $this->id['meA']);
        OrgService::placeContext($this->id['hist101'], $this->id['lsaA']);
        OrgService::placeContext($this->id['eecs183'], $this->id['csB']);
    }

    protected function insertKey(string $title): int
    {
        global $CFG, $PDOX;
        $token = bin2hex(random_bytes(16));
        $stmt = $PDOX->queryReturnError(
            "INSERT INTO {$CFG->dbprefix}lti_key (key_sha256, key_key, key_title) VALUES (:sha, :key_key, :title)",
            array(
                ':sha' => hash('sha256', $token),
                ':key_key' => $token,
                ':title' => $title,
            )
        );
        $this->assertTrue((bool) $stmt->success, (string) $stmt->errorImplode);
        return (int) $PDOX->lastInsertId();
    }

    protected function insertContext(int $keyId, string $title): int
    {
        global $CFG, $PDOX;
        $token = bin2hex(random_bytes(16));
        $stmt = $PDOX->queryReturnError(
            "INSERT INTO {$CFG->dbprefix}lti_context
                (context_sha256, context_key, key_id, title)
             VALUES
                (:sha, :context_key, :key_id, :title)",
            array(
                ':sha' => hash('sha256', $token),
                ':context_key' => $token,
                ':key_id' => $keyId,
                ':title' => $title,
            )
        );
        $this->assertTrue((bool) $stmt->success, (string) $stmt->errorImplode);
        return (int) $PDOX->lastInsertId();
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, string>
     */
    protected function titles(array $rows): array
    {
        $titles = array();
        foreach ( $rows as $row ) {
            $titles[] = (string) $row['title'];
        }
        return $titles;
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function assertSqlRejected(string $sql, array $params, string $needle): void
    {
        global $PDOX;
        $stmt = $PDOX->queryReturnError($sql, $params, false);
        $this->assertFalse((bool) $stmt->success, 'Expected SQL to be rejected');
        $this->assertStringContainsString($needle, (string) $stmt->errorImplode);
    }

    protected function p(): string
    {
        global $CFG;
        return (string) $CFG->dbprefix;
    }
}

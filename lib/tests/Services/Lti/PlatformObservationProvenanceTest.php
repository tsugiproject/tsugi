<?php

use Tsugi\Core\LTIX;
use Tsugi\Services\Lti\PlatformObservation;
use Tsugi\Util\LTI13;

/**
 * Backlinks on lti_platform against the local database.
 * Skips when that database or the new columns are not there.
 */
class PlatformObservationProvenanceTest extends \PHPUnit\Framework\TestCase
{
    /** @var int[] */
    private $keyIds = array();

    /** @var int[] */
    private $clientIds = array();

    /** @var int[] */
    private $platformIds = array();

    protected function setUp(): void
    {
        $config = dirname(__DIR__, 4).'/config.php';
        if ( ! is_file($config) ) {
            $this->markTestSkipped('config.php is not in this checkout');
        }
        require_once $config;
        try {
            global $PDOX;
            if ( ! defined('PDO_WILL_CATCH') ) define('PDO_WILL_CATCH', true);
            $PDOX = LTIX::getConnection();
        } catch ( \Throwable $e ) {
            $this->markTestSkipped($e->getMessage());
        }
        global $CFG, $PDOX;
        if ( ! isset($PDOX) || ! $PDOX || ! isset($CFG) || ! PlatformObservation::ready($CFG->dbprefix) ) {
            $this->markTestSkipped('platform backlinks are not installed');
        }
    }

    protected function tearDown(): void
    {
        global $CFG, $PDOX;
        if ( ! isset($PDOX) || ! $PDOX || ! isset($CFG) ) return;
        $p = $CFG->dbprefix;
        foreach ( $this->keyIds as $key_id ) {
            $PDOX->queryDie(
                "UPDATE {$p}lti_key
                    SET platform_id = NULL, registration_platform_id = NULL, lti_client_id = NULL
                    WHERE key_id = :id",
                array(':id' => $key_id)
            );
            $PDOX->queryDie(
                "DELETE FROM {$p}lti_key WHERE key_id = :id",
                array(':id' => $key_id)
            );
        }
        foreach ( $this->clientIds as $client_id ) {
            $PDOX->queryDie(
                "DELETE FROM {$p}lti_client WHERE lti_client_id = :id",
                array(':id' => $client_id)
            );
        }
        foreach ( $this->platformIds as $platform_id ) {
            $PDOX->queryDie(
                "DELETE FROM {$p}lti_platform WHERE platform_id = :id",
                array(':id' => $platform_id)
            );
        }
    }

    public function testOldSnapshotKeepsItsKeyAfterTheForwardPointerMoves(): void
    {
        $client_id = $this->makeClient();
        $key_id = $this->makeKey($client_id);
        $other_id = $this->makeKey(null);

        $this->launch($key_id, $client_id, null, array(), array(
            'name' => 'School One',
            'product_family_code' => 'sakai',
            'version' => '1',
        ));
        $first = $this->newestForKey($key_id);
        $this->assertSame($key_id, (int) $first['key_id']);
        $this->assertSame($client_id, (int) $first['lti_client_id']);

        $this->launch($key_id, $client_id, (int) $first['platform_id'], $first, array(
            'name' => 'School One',
            'product_family_code' => 'sakai',
            'version' => '2',
        ));
        $rows = $this->rowsForKey($key_id);
        $this->assertCount(2, $rows);
        $older = $rows[0];
        $newer = $rows[1];
        $this->assertSame((int) $first['platform_id'], (int) $older['platform_id']);
        $this->assertSame($key_id, (int) $older['key_id']);
        $this->assertSame($client_id, (int) $older['lti_client_id']);
        $this->assertSame($key_id, (int) $newer['key_id']);
        $this->assertSame((int) $newer['platform_id'], $this->keyPlatformId($key_id));

        $before = count($this->rowsForKey($key_id));
        $this->launch($key_id, $client_id, (int) $newer['platform_id'], $newer, array(
            'name' => 'School One',
            'product_family_code' => 'sakai',
            'version' => '2',
        ));
        $this->assertCount($before, $this->rowsForKey($key_id));

        global $CFG, $PDOX;
        $p = $CFG->dbprefix;
        $PDOX->queryDie(
            "UPDATE {$p}lti_key SET platform_id = :pid WHERE key_id = :id",
            array(':pid' => (int) $older['platform_id'], ':id' => $other_id)
        );
        $this->launch($other_id, null, (int) $older['platform_id'], $older, array(
            'name' => 'School One',
            'product_family_code' => 'sakai',
            'version' => '1',
        ));
        $still = $this->platformRow((int) $older['platform_id']);
        $this->assertSame($key_id, (int) $still['key_id']);
        $this->assertSame($client_id, (int) $still['lti_client_id']);
    }

    public function testRegistrationSnapshotWaitsForTheSettledClient(): void
    {
        $client_id = $this->makeClient();
        $key_id = $this->makeKey($client_id);
        $record = PlatformObservation::recordKeyRegistration($key_id, array(
            'https://purl.imsglobal.org/spec/lti-platform-configuration' => array(
                'product_family_code' => 'sakai',
                'version' => '27',
            ),
        ), array());
        $this->assertIsArray($record);
        $this->rememberForKey($key_id);
        $platform_id = (int) $record['inserted_id'];
        $row = $this->platformRow($platform_id);
        $this->assertSame($key_id, (int) $row['key_id']);
        $this->assertNull($row['lti_client_id']);

        PlatformObservation::finishRegistrationLink($key_id, array(
            'id' => $client_id,
            'created' => false,
            'linked' => false,
        ), $record);
        $this->assertNull($this->platformRow($platform_id)['lti_client_id']);

        PlatformObservation::finishRegistrationLink($key_id, array(
            'id' => $client_id,
            'created' => false,
            'linked' => true,
        ), $record);
        $this->assertSame($client_id, (int) $this->platformRow($platform_id)['lti_client_id']);
    }

    public function testDeleteClearsTheBacklinkAndLeavesTheSnapshot(): void
    {
        $key_id = $this->makeKey(null);
        $this->launch($key_id, null, null, array(), array(
            'name' => 'School Two',
            'product_family_code' => 'moodle',
        ));
        $row = $this->newestForKey($key_id);
        $platform_id = (int) $row['platform_id'];
        $this->assertNull($row['lti_client_id']);

        $client_id = $this->makeClient();
        $other_id = $this->makeClient();
        PlatformObservation::finishRegistrationLink($key_id, array(
            'id' => $client_id,
            'created' => false,
            'linked' => true,
        ), array('inserted_id' => $platform_id));
        $filled = $this->platformRow($platform_id);
        $this->assertSame($client_id, (int) $filled['lti_client_id']);

        PlatformObservation::finishRegistrationLink($key_id, array(
            'id' => $other_id,
            'created' => false,
            'linked' => false,
        ), array('inserted_id' => $platform_id));
        $kept = $this->platformRow($platform_id);
        $this->assertSame($client_id, (int) $kept['lti_client_id']);

        global $CFG, $PDOX;
        $p = $CFG->dbprefix;
        $PDOX->queryDie("DELETE FROM {$p}lti_key WHERE key_id = :id", array(':id' => $key_id));
        $after_key = $this->platformRow($platform_id);
        $this->assertNotFalse($after_key);
        $this->assertNull($after_key['key_id']);
        $this->assertSame($client_id, (int) $after_key['lti_client_id']);

        $PDOX->queryDie("DELETE FROM {$p}lti_client WHERE lti_client_id = :id", array(':id' => $client_id));
        $after_client = $this->platformRow($platform_id);
        $this->assertNotFalse($after_client);
        $this->assertNull($after_client['key_id']);
        $this->assertNull($after_client['lti_client_id']);
    }

    private function makeClient()
    {
        global $CFG, $PDOX;
        $stamp = bin2hex(random_bytes(6));
        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}lti_client (issuer, client_id, title)
                VALUES (:issuer, :client, :title)",
            array(
                ':issuer' => 'https://prov.'.$stamp.'.test',
                ':client' => 'prov-'.$stamp,
                ':title' => 'provenance '.$stamp,
            )
        );
        $id = (int) $PDOX->lastInsertId();
        $this->clientIds[] = $id;
        return $id;
    }

    private function makeKey($clientId)
    {
        global $CFG, $PDOX;
        $stamp = bin2hex(random_bytes(6));
        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}lti_key (key_title, key_key, secret, lti_client_id)
                VALUES (:title, :key, 'secret', :client)",
            array(
                ':title' => 'provenance '.$stamp,
                ':key' => 'prov-'.$stamp,
                ':client' => $clientId,
            )
        );
        $id = (int) $PDOX->lastInsertId();
        $this->keyIds[] = $id;
        return $id;
    }

    private function launch($keyId, $clientId, $platformId, array $current, array $claim)
    {
        $row = array(
            'key_id' => $keyId,
            'key_platform_id' => $platformId,
            'key_lti_client_id' => $clientId,
            'plat_platform_guid' => $current['platform_guid'] ?? null,
            'plat_platform_name' => $current['platform_name'] ?? null,
            'plat_platform_description' => $current['platform_description'] ?? null,
            'plat_platform_url' => $current['platform_url'] ?? null,
            'plat_platform_family' => $current['platform_family'] ?? null,
            'plat_platform_version' => $current['platform_version'] ?? null,
            'plat_platform_contact_email' => $current['platform_contact_email'] ?? null,
        );
        $body = new \stdClass();
        $body->{LTI13::TOOL_PLATFORM_CLAIM} = (object) $claim;
        PlatformObservation::observeLaunch($row, $body);
        $this->rememberForKey($keyId);
    }

    private function rememberForKey($keyId)
    {
        foreach ( $this->rowsForKey($keyId) as $row ) {
            $id = (int) $row['platform_id'];
            if ( ! in_array($id, $this->platformIds, true) ) $this->platformIds[] = $id;
        }
    }

    private function rowsForKey($keyId)
    {
        global $CFG, $PDOX;
        return $PDOX->allRowsDie(
            "SELECT platform_id, key_id, lti_client_id, platform_guid, platform_name,
                    platform_description, platform_url, platform_family, platform_version,
                    platform_contact_email
                FROM {$CFG->dbprefix}lti_platform
                WHERE key_id = :id
                ORDER BY platform_id",
            array(':id' => $keyId)
        );
    }

    private function newestForKey($keyId)
    {
        $rows = $this->rowsForKey($keyId);
        $this->assertNotEmpty($rows);
        return $rows[count($rows) - 1];
    }

    private function platformRow($platformId)
    {
        global $CFG, $PDOX;
        return $PDOX->rowDie(
            "SELECT platform_id, key_id, lti_client_id
                FROM {$CFG->dbprefix}lti_platform
                WHERE platform_id = :id",
            array(':id' => $platformId)
        );
    }

    private function keyPlatformId($keyId)
    {
        global $CFG, $PDOX;
        $row = $PDOX->rowDie(
            "SELECT platform_id FROM {$CFG->dbprefix}lti_key WHERE key_id = :id",
            array(':id' => $keyId)
        );
        return (int) $row['platform_id'];
    }
}

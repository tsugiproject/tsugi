<?php

use Tsugi\Core\Context;
use Tsugi\Core\Guid;
use Tsugi\Core\Key;
use Tsugi\Core\Link;
use Tsugi\Core\LTIX;
use Tsugi\Core\User;
use Tsugi\Util\U;

class GuidTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyObjectsDoNotQuery() {
        $this->resetSqlCount();
        $user = new User();
        $context = new Context();
        $key = new Key();
        $link = new Link();
        $this->assertNull($user->guid);
        $this->assertNull($context->guid);
        $this->assertNull($key->guid);
        $this->assertNull($link->guid);
        $this->assertSame(0, $this->sqlCount());
    }

    public function testBinaryGuidIsHexWithoutSql() {
        $this->resetSqlCount();
        $hex = '550e8400e29b41d4a716446655440000';
        $binary = hex2bin($hex);
        $user = new User(array('user_id' => 4, 'user_guid' => $binary));
        $context = new Context(array('context_id' => 4, 'context_guid' => $binary));
        $key = new Key(array('key_id' => 4, 'key_guid' => $binary));
        $link = new Link(array('link_id' => 4, 'link_guid' => $binary));
        $this->assertSame($hex, $user->guid);
        $this->assertSame($hex, $context->guid);
        $this->assertSame($hex, $key->guid);
        $this->assertSame($hex, $link->guid);
        $this->assertSame(0, $this->sqlCount());
        $this->assertSame($hex, Guid::ensure('lti_user', 'user_id', 0, $binary));
        $this->assertSame(0, $this->sqlCount());
    }

    public function testMissingJoinRowDoesNotQuery() {
        $this->resetSqlCount();
        $user = new User(array('user_id' => null, 'user_guid' => null));
        $context = new Context(array('context_id' => null, 'context_guid' => null));
        $key = new Key(array('key_id' => null, 'key_guid' => null));
        $link = new Link(array('link_id' => null, 'link_guid' => null));
        $this->assertNull($user->guid);
        $this->assertNull($context->guid);
        $this->assertNull($key->guid);
        $this->assertNull($link->guid);
        $this->assertSame(0, $this->sqlCount());
    }

    public function testRememberedGuidIsNotReplaced() {
        if ( ! defined('TSUGI_SESSION_LTI') ) {
            require_once dirname(__DIR__, 2).'/include/tsugi_constants.php';
        }
        $_SESSION = array(TSUGI_SESSION_LTI => array('user_id' => 1));
        $method = new \ReflectionMethod(LTIX::class, 'rememberBinaryGuid');
        $first = '550e8400e29b41d4a716446655440000';
        $method->invoke(null, 'user_guid', $first);
        $stored = $_SESSION[TSUGI_SESSION_LTI]['user_guid'];
        $this->assertSame($first, bin2hex($stored));
        $method->invoke(null, 'user_guid', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        $this->assertSame($stored, $_SESSION[TSUGI_SESSION_LTI]['user_guid']);
    }

    public function testLtixInsertSelectAndBackfill() {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for guid fixtures.');
        }
        try {
            $made = $this->insertThroughLtix($pdo, 'a');
            $again = $this->insertThroughLtix($pdo, 'b');
            $guids = array($made['context'], $made['link'], $made['user'], $again['context'], $again['link'], $again['user']);
            foreach ( $guids as $hex ) {
                $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $hex);
            }
            $this->assertCount(6, array_unique($guids));
            $this->assertNull($this->storedHex($pdo, 'lti_key', 'key_id', $made['key_id']));

            $loaded = LTIX::loadAllData($this->prefix(), false, $made['post']);
            $this->assertIsArray($loaded);
            $this->assertArrayHasKey('user_guid', $loaded);
            $this->assertArrayHasKey('context_guid', $loaded);
            $this->assertArrayHasKey('key_guid', $loaded);
            $this->assertArrayHasKey('link_guid', $loaded);
            $this->assertNull($loaded['key_guid']);
            $this->assertSame($made['user'], bin2hex($loaded['user_guid']));
            $this->assertSame($made['context'], bin2hex($loaded['context_guid']));
            $this->assertSame($made['link'], bin2hex($loaded['link_guid']));
            $this->assertSame($made['user_id'], (int) $loaded['user_id']);
            $this->assertSame($made['context_id'], (int) $loaded['context_id']);
            $this->assertSame($made['link_id'], (int) $loaded['link_id']);
            $this->assertSame($made['key_id'], (int) $loaded['key_id']);

            $this->resetSqlCount();
            $user = new User($loaded);
            $context = new Context($loaded);
            $link = new Link($loaded);
            $this->assertSame($made['user'], $user->guid);
            $this->assertSame($made['context'], $context->guid);
            $this->assertSame($made['link'], $link->guid);
            $this->assertSame(0, $this->sqlCount());

            $userAgain = new User($loaded);
            $this->assertSame($user->guid, $userAgain->guid);
            $this->assertSame(0, $this->sqlCount());

            $key = new Key($loaded);
            $keyHex = $this->storedHex($pdo, 'lti_key', 'key_id', $made['key_id']);
            $this->assertSame($keyHex, $key->guid);
            $this->assertGreaterThan(0, $this->sqlCount());
            $loaded['key_guid'] = hex2bin($keyHex);
            $before = $this->sqlCount();
            $keyAgain = new Key($loaded);
            $this->assertSame($keyHex, $keyAgain->guid);
            $this->assertSame($before, $this->sqlCount());

            $dupPost = $made['post'];
            $dupRow = $this->blankRow($made['key_id'], $dupPost);
            LTIX::adjustData($this->prefix(), $dupRow, $dupPost, array());
            $this->assertSame($made['context'], $this->storedHex($pdo, 'lti_context', 'context_id', $made['context_id']));
            $this->assertSame($made['link'], $this->storedHex($pdo, 'lti_link', 'link_id', $made['link_id']));
            $this->assertSame($made['user'], $this->storedHex($pdo, 'lti_user', 'user_id', $made['user_id']));
            $this->assertSame($keyHex, $this->storedHex($pdo, 'lti_key', 'key_id', $made['key_id']));

            $bareId = $this->insertUser($pdo, $made['key_id'], $made['post']['key'].'-bare');
            $this->assertNull($this->storedHex($pdo, 'lti_user', 'user_id', $bareId));
            $bare = new User(array('user_id' => $bareId, 'user_guid' => null));
            $bareHex = $this->storedHex($pdo, 'lti_user', 'user_id', $bareId);
            $this->assertSame($bareHex, $bare->guid);
            $bareAgain = new User(array('user_id' => $bareId, 'user_guid' => hex2bin($bareHex)));
            $this->assertSame($bareHex, $bareAgain->guid);
            $this->resetSqlCount();
            $bareQuiet = new User(array('user_id' => $bareId, 'user_guid' => hex2bin($bareHex)));
            $this->assertSame($bareHex, $bareQuiet->guid);
            $this->assertSame(0, $this->sqlCount());

            $known = '0123456789abcdeffedcba9876543210';
            $raceId = $this->insertUser($pdo, $made['key_id'], $made['post']['key'].'-race');
            $pdo->queryDie(
                "UPDATE {$this->prefix()}lti_user SET guid = UNHEX(:hex) WHERE user_id = :id",
                array(':hex' => $known, ':id' => $raceId)
            );
            $raced = Guid::ensure('lti_user', 'user_id', $raceId, null);
            $again = Guid::ensure('lti_user', 'user_id', $raceId, null);
            $this->assertSame($known, $raced);
            $this->assertSame($known, $again);
            $this->assertSame($known, $this->storedHex($pdo, 'lti_user', 'user_id', $raceId));
            $racedUser = new User(array('user_id' => $raceId, 'user_guid' => null));
            $this->assertSame($known, $racedUser->guid);

            $this->resetSqlCount();
            $missing = new User(array('user_id' => 2147483001));
            $this->assertNull($missing->guid);
            $this->assertGreaterThan(0, $this->sqlCount());
            $this->assertNull($this->storedHex($pdo, 'lti_user', 'user_id', 2147483001));
        } finally {
            $pdo->rollBack();
        }
    }

    private function insertThroughLtix($pdo, $mark) {
        $stamp = 'guid'.$mark.bin2hex(random_bytes(4));
        $key = $stamp.'-key';
        $pdo->queryDie(
            "INSERT INTO {$this->prefix()}lti_key (key_key, key_sha256, created_at, updated_at)
             VALUES (:K, :SHA, NOW(), NOW())",
            array(':K' => $key, ':SHA' => U::lti_sha256($key))
        );
        $keyId = (int) $pdo->lastInsertId();
        $pdo->queryDie(
            "UPDATE {$this->prefix()}lti_key SET guid = NULL WHERE key_id = :id",
            array(':id' => $keyId)
        );
        $post = array(
            'key' => $key,
            'context_id' => $stamp.'-context',
            'context_title' => 'Ctx '.$mark,
            'link_id' => $stamp.'-link',
            'link_title' => 'Link '.$mark,
            'link_path' => '/'.$stamp,
            'user_id' => $stamp.'-user',
            'user_displayname' => 'Pat '.$mark,
            'user_email' => $stamp.'@example.test',
            'role' => 0,
            'nonce' => $stamp.'-nonce',
        );
        $row = $this->blankRow($keyId, $post);
        LTIX::adjustData($this->prefix(), $row, $post, array());
        return array(
            'post' => $post,
            'key_id' => $keyId,
            'context_id' => (int) $row['context_id'],
            'link_id' => (int) $row['link_id'],
            'user_id' => (int) $row['user_id'],
            'context' => $this->storedHex($pdo, 'lti_context', 'context_id', (int) $row['context_id']),
            'link' => $this->storedHex($pdo, 'lti_link', 'link_id', (int) $row['link_id']),
            'user' => $this->storedHex($pdo, 'lti_user', 'user_id', (int) $row['user_id']),
        );
    }

    private function blankRow($keyId, array $post) {
        return array(
            'key_id' => $keyId,
            'context_id' => null,
            'context_title' => $post['context_title'],
            'link_id' => null,
            'link_title' => $post['link_title'],
            'link_path' => $post['link_path'],
            'user_id' => null,
            'user_key' => null,
            'subject_key' => null,
            'user_displayname' => $post['user_displayname'],
            'user_email' => $post['user_email'],
            'user_image' => null,
            'user_locale' => null,
            'membership_id' => null,
            'role' => $post['role'],
            'result_id' => null,
            'sourcedid' => null,
            'result_url' => null,
            'service' => null,
            'service_id' => null,
            'upward' => 0,
            'plus_course' => 0,
        );
    }

    private function insertUser($pdo, $keyId, $userKey) {
        $pdo->queryDie(
            "INSERT INTO {$this->prefix()}lti_user
                (guid, user_key, user_sha256, displayname, email, key_id, created_at, updated_at)
             VALUES (NULL, :UK, :SHA, :NAME, :EMAIL, :KID, NOW(), NOW())",
            array(
                ':UK' => $userKey,
                ':SHA' => U::lti_sha256($userKey),
                ':NAME' => 'Bare User',
                ':EMAIL' => $userKey.'@example.test',
                ':KID' => $keyId,
            )
        );
        return (int) $pdo->lastInsertId();
    }

    private function storedHex($pdo, $table, $pk, $id) {
        $row = $pdo->rowDie(
            "SELECT LOWER(HEX(guid)) AS guid_hex FROM {$this->prefix()}{$table} WHERE {$pk} = :id",
            array(':id' => (int) $id)
        );
        if ( ! is_array($row) ) {
            return null;
        }
        $hex = $row['guid_hex'];
        if ( ! is_string($hex) || $hex === '' ) {
            return null;
        }
        return strtolower($hex);
    }

    private function beginFixtureTransaction() {
        global $CFG, $PDOX;
        $config = dirname(__DIR__, 3).'/config.php';
        if ( ! is_file($config) ) {
            return null;
        }
        if ( ! isset($CFG) || ! is_object($CFG) ) {
            require_once $config;
        }
        try {
            $PDOX = LTIX::getConnection();
            if ( ! is_object($PDOX) || ! $PDOX->columnExists('guid', $CFG->dbprefix.'lti_user') ) {
                return null;
            }
            $PDOX->beginTransaction();
        } catch ( \Throwable $ex ) {
            return null;
        }
        return $PDOX;
    }

    private function prefix() {
        global $CFG;
        return $CFG->dbprefix;
    }

    private function sqlCount() {
        $prop = new \ReflectionProperty(Guid::class, 'sqlCount');
        return (int) $prop->getValue();
    }

    private function resetSqlCount() {
        $prop = new \ReflectionProperty(Guid::class, 'sqlCount');
        $prop->setValue(null, 0);
    }
}

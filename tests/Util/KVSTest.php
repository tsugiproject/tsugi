<?php

require_once "src/Util/PDOX.php";
require_once "src/Util/PDOXStatement.php";

use \Tsugi\Util\U;
use \Tsugi\Util\PDOX;
use \Tsugi\Util\PDOXStatement;
use \Tsugi\Util\KVS;

class KVSTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Skip all tests in this class since SQLite support has been removed from Tsugi.
     * These tests were written to use SQLite for testing, but Tsugi now requires MySQL.
     * TODO: Rewrite these tests to use MySQL or a test database configuration.
     */
    protected function setUp(): void
    {
        $this->markTestSkipped('SQLite support has been removed from Tsugi. KVS tests need to be rewritten to use MySQL.');
    }

    public static $USE_DISK = true;
    public static $PDOX = null;

    public static function getPDOX()
    {
        if ( self::$PDOX ) return self::$PDOX;
        if ( self::$USE_DISK ) {
            try { unlink('/tmp/db.sqlite'); } catch(\Exception $e) {  }
            self::$PDOX = new PDOX("sqlite:/tmp/db.sqlite");
        } else {
            self::$PDOX = new PDOX('sqlite::memory:');
        }
        self::createTestTable(self::$PDOX, "lti_result_kvs", "result_id", "user_id");
        return self::$PDOX;
    }

    public static function getKVS($pdox=false) {
        if ( ! $pdox ) $pdox = self::getPDOX();
        $kvs = new KVS($pdox, "lti_result_kvs", "result_id", 1, "user_id", 1);
        return $kvs;
    }

    // Leave the file at the end
    public function testFileDB() {
        $kvs = self::getKVS();
        $this->assertTrue(is_object($kvs)); 
    }

    public function testValidate() {
        $long = 'ldfjlgfjglkjgfljlgfjgfjgfljkglfjkgfljkglfjklgjkflgflfgjkgfljklgk';
        $long .= $long . $long . $long;
        $long = substr($long, 0, 150);
        $x = array( 'uk1' => $long, 'sk1' => $long, 'tk1' => $long,
            'co1' => $long, 'co2' => $long);
        $kvs = self::getKVS();
        $val = $kvs->validate($kvs);
        $this->assertEquals('$data must be an array', $val);
        $val = $kvs->validate($x);
        $this->assertEquals('sk1 must be no more than 75 characters', $val);
        $x['sk1'] = 'shorter';
        $val = $kvs->validate($x);
        $this->assertTrue($val);
        $x['id'] = 42;
        $val = $kvs->validate($x);
        $this->assertTrue($val);
        $x['id'] = 'towel';
        $val = $kvs->validate($x);
        $this->assertEquals('id must be an an integer', $val);
    }

    public function testPrivate() {
        $pdox = self::getPDOX();
        $kvs = self::getKVS($pdox);
        // Calling private methods :)
        // https://stackoverflow.com/questions/249664/best-practices-to-test-protected-methods-with-phpunit
        $reflector = new ReflectionClass( '\Tsugi\Util\KVS' );
        $extractKeys = $reflector->getMethod( 'extractKeys' );
        $extractKeys->setAccessible( true );

        $x = array( 'uk1' => 'bob');
        $result = $extractKeys->invokeArgs( $kvs, array($x) );

        $this->assertEquals("bob", $result->uk1 );
        $this->assertNull( $result->sk1 );
        $this->assertNull( $result->tk1 );
        $this->assertNull( $result->co1 );
        $this->assertNull( $result->co2 );

        $x = array( 'uk1' => 'a', 'sk1' => 'b', 'tk1' => 'c',
            'co1' => 'd', 'co2' => 'e');
        $result = $extractKeys->invokeArgs( $kvs, array($x) );

        $this->assertEquals('a', $result->uk1 );
        $this->assertEquals('b', $result->sk1 );
        $this->assertEquals('c', $result->tk1 );
        $this->assertEquals('d', $result->co1 );
        $this->assertEquals('e', $result->co2 );

        $extractMap = $reflector->getMethod( 'extractMap' );
        $extractMap->setAccessible( true );
        $x = array( 'uk1' => 'bob');
        $result = $extractMap->invokeArgs( $kvs, array($x) );

        $this->assertEquals('bob', $result[':uk1'] );
        $this->assertEquals(null, $result[':sk1'] );
        $this->assertEquals(null, $result[':tk1'] );
        $this->assertEquals(null, $result[':co1'] );
        $this->assertEquals(null, $result[':co2'] );

        $x = array( 'uk1' => 'a', 'sk1' => 'b', 'tk1' => 'c',
            'co1' => 'd', 'co2' => 'e');
        $result = $extractMap->invokeArgs( $kvs, array($x) );
        $this->assertEquals('a', $result[':uk1'] );
        $this->assertEquals('b', $result[':sk1'] );
        $this->assertEquals('c', $result[':tk1'] );
        $this->assertEquals('d', $result[':co1'] );
        $this->assertEquals('e', $result[':co2'] );

        $extractWhere = $reflector->getMethod( 'extractWhere' );
        $extractWhere->setAccessible( true );

        $x = array( 'zzz' => 'bob');
        $values = false;
        $where = false;
        $result = $extractWhere->invokeArgs( $kvs, array($x, &$where, &$values) );
        $this->assertEquals('No key found for WHERE clause', $result );

        $x = array( 'uk1' => 'bob');
        $values = false;
        $where = false;
        $result = $extractWhere->invokeArgs( $kvs, array($x, &$where, &$values) );

        $this->assertTrue($result);
        $this->assertEquals("uk1 = :uk1", $where);
        $this->assertEquals('bob', $values[':uk1'] );

        $x = array( 'id' => 42, 'uk1' => 'bob');
        $values = false;
        $where = false;
        $result = $extractWhere->invokeArgs( $kvs, array($x, &$where, &$values) );

        $this->assertTrue($result);
        $this->assertEquals("id = :id AND uk1 = :uk1", $where);
        $this->assertEquals('bob', $values[':uk1'] );
        $this->assertEquals(42, $values[':id'] );

        $x = array( 'id' => 42, 'uk1' => 'LIKE bob');
        $values = false;
        $where = false;
        $result = $extractWhere->invokeArgs( $kvs, array($x, &$where, &$values) );

        $this->assertTrue($result);
        $this->assertEquals("id = :id AND uk1 LIKE :uk1", $where);
        $this->assertEquals('bob', $values[':uk1'] );
        $this->assertEquals(42, $values[':id'] );

        // Handle the ORDER BY
        $retval = $kvs->extractOrder(array('sk1'));
        $this->assertEquals('sk1', $retval );

        $retval = $kvs->extractOrder(array('sk1 DESC'));
        $this->assertEquals('sk1 DESC', $retval );
        $retval = $kvs->extractOrder(array('sk1 DESC', 'co1', 'co2'));
        $this->assertEquals('sk1 DESC, co1, co2', $retval );

        // Bad juju
        $retval = $kvs->extractOrder(array('zk1 DESC', 'co1', 'co2'));
        $this->assertFalse($retval );
    }

    public function testCRUD() {
        $pdox = self::getPDOX();
        $kvs = self::getKVS($pdox);
        try {
            $kvs->insert('not an array');
            $this->fail('Expecting an exception');
        } catch(Exception $e) {
            // Expected :)
        }
        $data = array('bob' => 42, 'uk1' => 'ABC', 'co1' => 'Yada');
        $id = $kvs->insert($data);
        $this->assertEquals($id, 1);

        // Sadly this does not work on SQLite
        // $retval = $kvs->insertOrUpdate($data);
        // $this->assertEquals($retval, 1);

        // Load some data
        $where = array('id' => $id);
        $row = $kvs->selectOne($where);
        $this->assertEquals($row['uk1'], 'ABC');
        $this->assertEquals($row['co1'], 'Yada');

        $where = array('uk1' => 'ABC');
        $row = $kvs->selectOne($where);
        $this->assertEquals($row['uk1'], 'ABC');
        $this->assertEquals($row['co1'], 'Yada');

        // Update some data
        $data['bob'] = 443;
        $data['co1'] = 'Yada 123';
        $retval = $kvs->update($data);
        $this->assertEquals($retval, 1);

        // Grab a row
        $rows = $kvs->selectAll();
        $this->assertEquals(count($rows), 1);
        $this->assertEquals($rows[0]['json_body'],'{"bob":443,"uk1":"ABC","co1":"Yada 123"}');

        // Attempt update with neither id nor uk1
        unset($data['uk1']);
        try {
            $retval = $kvs->update($data);
            $this->fail('Expecting an exception');
        } catch(Exception $e) {
            // Expected :)
        }

        // Grab a row
        $rows = $kvs->selectAll();
        $this->assertEquals(count($rows), 1);
        $this->assertEquals($rows[0]['json_body'],'{"bob":443,"uk1":"ABC","co1":"Yada 123"}');


        // Use primary key to update
        $data['id'] = $id;
        $retval = $kvs->update($data);
        $this->assertEquals($retval, 1);

        // Grab a row
        $rows = $kvs->selectAll();
        $this->assertEquals(count($rows), 1);
        $this->assertEquals($rows[0]['json_body'],'{"bob":443,"uk1":"ABC","co1":"Yada 123"}');

        // Insert a record for a different user
        $kvs2 = new KVS($pdox, "lti_result_kvs", "result_id", 1, "user_id", 2);

        // Insert a second record
        $data = array('bob' => 43, 'uk1' => 'DEF', 'co1' => 'Ah Yada');
        $id2 = $kvs2->insert($data);
        $this->assertEquals($id2, 2);

        // Grab some rows
        $rows = $kvs->selectAll();
        $this->assertEquals(count($rows), 2);
        $rows2 = $kvs2->selectAll();
        $this->assertEquals($rows, $rows2);

        $where = array('uk1' => 'DEF');
        $rows = $kvs->selectAll($where);
        $this->assertEquals(count($rows), 1);
        $rows2 = $kvs2->selectAll($where);
        $this->assertEquals($rows, $rows2);

        $where = false;
        $order = array('co1 DESC');
        $rows = $kvs->selectAll($where, $order);
        $this->assertEquals(count($rows), 2);
        $rows2 = $kvs2->selectAll($where);
        $this->assertEquals($rows, $rows2);

        $where = false;
        $order = array('uk1 DESC');
        $rows = $kvs->selectAll($where, $order);
        $this->assertEquals(count($rows), 2);
        $rows2 = $kvs2->selectAll($where);
        $this->assertEquals($rows, $rows2);

        // Delete some data
        $where = array('id' => $id);
        $retval = $kvs->delete($where);
        $this->assertTrue($retval);

        // Verify data no longer exists
        $where = array('id' => $id);
        $row = $kvs->selectOne($where);
        $this->assertFalse($row);

        // Delete some data belonging to the second user
        $where = array('id' => $id2);
        $retval = $kvs2->delete($where);
        $this->assertTrue($retval);

        // Verify data no longer exists
        $where = array('id' => $id2);
        $row = $kvs2->selectOne($where);
        $this->assertFalse($row);

    }

    private static function createTestTable($PDOX, $KVS_TABLE, $KVS_FK_NAME, $KVS_SK_NAME) {
        $sql = "CREATE TABLE $KVS_TABLE (
            id INTEGER  NOT NULL PRIMARY KEY AUTOINCREMENT,
            $KVS_FK_NAME INTEGER NOT NULL,
            $KVS_SK_NAME INTEGER NOT NULL,
            uk1 VARCHAR(150),
            sk1 VARCHAR(75),
            tk1 TEXT,
            co1 VARCHAR(150),
            co2 VARCHAR(150),
            json_body TEXT,
            created_at INTEGER,
            updated_at INTEGER)";

        $PDOX->queryDie($sql);

        $sql = "CREATE UNIQUE INDEX uk1 ON $KVS_TABLE ($KVS_FK_NAME, $KVS_SK_NAME, uk1)";
        $PDOX->queryDie($sql);

        $sql = "CREATE INDEX sk1 ON $KVS_TABLE ($KVS_FK_NAME, $KVS_SK_NAME, sk1)";
        $PDOX->queryDie($sql);

        // In MySQL only do the first 75 characters of tk1
        $sql = "CREATE INDEX tk1 ON $KVS_TABLE ($KVS_FK_NAME, $KVS_SK_NAME, tk1)";
        $PDOX->queryDie($sql);

    }

}

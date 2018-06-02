<?php

use \Tsugi\Util\U;
use \Tsugi\Util\PDOX;
use \Tsugi\Util\KVS;

class KVSTest extends PHPUnit_Framework_TestCase
{

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        unlink('/tmp/db.sqlite');
        $pdox = new PDOX("sqlite:/tmp/db.sqlite");
        self::createTestTable($pdox, "lti_result_kvs", "result_id");
    }

    // Leave the file at the end
    public function testFileDB() {
        $pdox = new PDOX("sqlite:/tmp/db.sqlite");
        $kvs = new KVS($pdox, "lti_result_kvs", "result_id", 1);
    }

    public function testValidate() {
        $long = 'ldfjlgfjglkjgfljlgfjgfjgfljkglfjkgfljkglfjklgjkflgflfgjkgfljklgk';
        $long .= $long . $long . $long;
        $long = substr($long, 0, 150);
        $x = array( 'uk1' => $long, 'sk1' => $long, 'tk1' => $long,
            'co1' => $long, 'co2' => $long);
        $pdox = new PDOX('sqlite::memory:');
        $kvs = new KVS($pdox, "lti_result_kvs", "result_id", 1);
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
        $pdox = new PDOX('sqlite::memory:');
        $kvs = new KVS($pdox, "lti_result_kvs", "result_id", 1);
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
    }

    public function testInsert() {
        $pdox = new PDOX("sqlite:/tmp/db.sqlite");

        $kvs = new KVS($pdox, "lti_result_kvs", "result_id", 1);
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
        $row = $kvs->getRow($where);
        $this->assertEquals($row['uk1'], 'ABC');
        $this->assertEquals($row['co1'], 'Yada');

        $where = array('uk1' => 'ABC');
        $row = $kvs->getRow($where);
        $this->assertEquals($row['uk1'], 'ABC');
        $this->assertEquals($row['co1'], 'Yada');

        // Update some data
        $data['bob'] = 443;
        $data['co1'] = 'Yada 123';
        $retval = $kvs->update($data);
        $this->assertEquals($retval, 1);

        // Attempt update with neither id nor uk1
        unset($data['uk1']);
        try {
            $retval = $kvs->update($data);
            $this->fail('Expecting an exception');
        } catch(Exception $e) {
            // Expected :)
        }

        // Use primary key to update
        $data['id'] = $id;
        $retval = $kvs->update($data);
        $this->assertEquals($retval, 1);

        // Delete some data
        $where = array('id' => $id);
        $retval = $kvs->delete($where);
        $this->assertTrue($retval);

        // Load some data
        $where = array('id' => $id);
        $row = $kvs->getRow($where);
        $this->assertFalse($row);

    }

    private static function createTestTable($PDOX, $KVS_TABLE, $KVS_FK_NAME) {
        $sql = "CREATE TABLE $KVS_TABLE (
            id INTEGER  NOT NULL PRIMARY KEY AUTOINCREMENT,
            $KVS_FK_NAME INTEGER NOT NULL,
            uk1 VARCHAR(150),
            sk1 VARCHAR(75),
            tk1 TEXT,
            co1 VARCHAR(150),
            co2 VARCHAR(150),
            json_body TEXT,
            created_at INTEGER,
            updated_at INTEGER)";

        $PDOX->queryDie($sql);

        $sql = "CREATE UNIQUE INDEX uk1 ON $KVS_TABLE ($KVS_FK_NAME, uk1)";
        $PDOX->queryDie($sql);

        $sql = "CREATE INDEX sk1 ON $KVS_TABLE ($KVS_FK_NAME, sk1)";
        $PDOX->queryDie($sql);

        // In MySQL only do the first 75 characters of tk1
        $sql = "CREATE INDEX tk1 ON $KVS_TABLE ($KVS_FK_NAME, tk1)";
        $PDOX->queryDie($sql);

    }

}

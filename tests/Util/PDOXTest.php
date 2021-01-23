<?php

require_once "src/Util/PDOX.php";

class mockPDOX extends \Tsugi\Util\PDOX
{
    public function __construct ()
    {}

}

class PDOXTest extends \PHPUnit\Framework\TestCase
{
    public function testDescribe() {
        $describe = self::mockDescribe();
        $this->assertTrue(is_array($describe));
        $PDOX = new mockPDOX();
        $column = $PDOX->describeColumn("key_id", $describe);
        $this->assertArrayHasKey("Field", $column);
        $this->assertArrayHasKey("Type", $column);
        $this->assertArrayHasKey("Null", $column);
        try {
            $PDOX->columnIsNull("zap", $describe);
            $this->assertTrue(false);
        } catch(Exception $e) {
        }
        try {
            $PDOX->columnType("zap", $describe);
            $this->assertTrue(false);
        } catch(Exception $e) {
        }
        try {
            $PDOX->columnLength("zap", $describe);
            $this->assertTrue(false);
        } catch(Exception $e) {
        }

        $this->assertFalse($PDOX->columnIsNull("key_id", $describe));
        $this->assertEquals("int", $PDOX->columnType("key_id", $describe));
        $this->assertEquals(11, $PDOX->columnLength("key_id", $describe));

        $this->assertTrue($PDOX->columnIsNull("secret", $describe));
        $this->assertEquals("text", $PDOX->columnType("secret", $describe));
        $this->assertEquals(0, $PDOX->columnLength("secret", $describe));

        $this->assertFalse($PDOX->columnIsNull("key_sha256", $describe));
        $this->assertEquals("char", $PDOX->columnType("key_sha256", $describe));
        $this->assertEquals(64, $PDOX->columnLength("key_sha256", $describe));

        $this->assertFalse($PDOX->columnIsNull("deleted", $describe));
        $this->assertEquals("tinyint", $PDOX->columnType("deleted", $describe));
        $this->assertEquals(1, $PDOX->columnLength("deleted", $describe));
    }

    public function mockDescribe() {
        return json_decode('[
    {
        "Field": "key_id",
        "0": "key_id",
        "Type": "int(11)",
        "1": "int(11)",
        "Null": "NO",
        "2": "NO",
        "Key": "PRI",
        "3": "PRI",
        "Default": null,
        "4": null,
        "Extra": "auto_increment",
        "5": "auto_increment"
    },
    {
        "Field": "key_sha256",
        "0": "key_sha256",
        "Type": "char(64)",
        "1": "char(64)",
        "Null": "NO",
        "2": "NO",
        "Key": "UNI",
        "3": "UNI",
        "Default": null,
        "4": null,
        "Extra": "",
        "5": ""
    },
    {
        "Field": "key_key",
        "0": "key_key",
        "Type": "text",
        "1": "text",
        "Null": "NO",
        "2": "NO",
        "Key": "",
        "3": "",
        "Default": null,
        "4": null,
        "Extra": "",
        "5": ""
    },
    {
        "Field": "deleted",
        "0": "deleted",
        "Type": "tinyint(1)",
        "1": "tinyint(1)",
        "Null": "NO",
        "2": "NO",
        "Key": "",
        "3": "",
        "Default": "0",
        "4": "0",
        "Extra": "",
        "5": ""
    },
    {
        "Field": "secret",
        "0": "secret",
        "Type": "text",
        "1": "text",
        "Null": "YES",
        "2": "YES",
        "Key": "",
        "3": "",
        "Default": null,
        "4": null,
        "Extra": "",
        "5": ""
    },
    {
        "Field": "new_secret",
        "0": "new_secret",
        "Type": "text",
        "1": "text",
        "Null": "YES",
        "2": "YES",
        "Key": "",
        "3": "",
        "Default": null,
        "4": null,
        "Extra": "",
        "5": ""
    },
    {
        "Field": "ack",
        "0": "ack",
        "Type": "text",
        "1": "text",
        "Null": "YES",
        "2": "YES",
        "Key": "",
        "3": "",
        "Default": null,
        "4": null,
        "Extra": "",
        "5": ""
    },
    {
        "Field": "user_id",
        "0": "user_id",
        "Type": "int(11)",
        "1": "int(11)",
        "Null": "YES",
        "2": "YES",
        "Key": "",
        "3": "",
        "Default": null,
        "4": null,
        "Extra": "",
        "5": ""
    },
    {
        "Field": "consumer_profile",
        "0": "consumer_profile",
        "Type": "mediumtext",
        "1": "mediumtext",
        "Null": "YES",
        "2": "YES",
        "Key": "",
        "3": "",
        "Default": null,
        "4": null,
        "Extra": "",
        "5": ""
    },
    {
        "Field": "new_consumer_profile",
        "0": "new_consumer_profile",
        "Type": "mediumtext",
        "1": "mediumtext",
        "Null": "YES",
        "2": "YES",
        "Key": "",
        "3": "",
        "Default": null,
        "4": null,
        "Extra": "",
        "5": ""
    },
    {
        "Field": "tool_profile",
        "0": "tool_profile",
        "Type": "mediumtext",
        "1": "mediumtext",
        "Null": "YES",
        "2": "YES",
        "Key": "",
        "3": "",
        "Default": null,
        "4": null,
        "Extra": "",
        "5": ""
    },
    {
        "Field": "new_tool_profile",
        "0": "new_tool_profile",
        "Type": "mediumtext",
        "1": "mediumtext",
        "Null": "YES",
        "2": "YES",
        "Key": "",
        "3": "",
        "Default": null,
        "4": null,
        "Extra": "",
        "5": ""
    },
    {
        "Field": "caliper_url",
        "0": "caliper_url",
        "Type": "text",
        "1": "text",
        "Null": "YES",
        "2": "YES",
        "Key": "",
        "3": "",
        "Default": null,
        "4": null,
        "Extra": "",
        "5": ""
    },
    {
        "Field": "caliper_key",
        "0": "caliper_key",
        "Type": "text",
        "1": "text",
        "Null": "YES",
        "2": "YES",
        "Key": "",
        "3": "",
        "Default": null,
        "4": null,
        "Extra": "",
        "5": ""
    },
    {
        "Field": "json",
        "0": "json",
        "Type": "mediumtext",
        "1": "mediumtext",
        "Null": "YES",
        "2": "YES",
        "Key": "",
        "3": "",
        "Default": null,
        "4": null,
        "Extra": "",
        "5": ""
    },
    {
        "Field": "settings",
        "0": "settings",
        "Type": "mediumtext",
        "1": "mediumtext",
        "Null": "YES",
        "2": "YES",
        "Key": "",
        "3": "",
        "Default": null,
        "4": null,
        "Extra": "",
        "5": ""
    },
    {
        "Field": "settings_url",
        "0": "settings_url",
        "Type": "text",
        "1": "text",
        "Null": "YES",
        "2": "YES",
        "Key": "",
        "3": "",
        "Default": null,
        "4": null,
        "Extra": "",
        "5": ""
    },
    {
        "Field": "entity_version",
        "0": "entity_version",
        "Type": "int(11)",
        "1": "int(11)",
        "Null": "NO",
        "2": "NO",
        "Key": "",
        "3": "",
        "Default": "0",
        "4": "0",
        "Extra": "",
        "5": ""
    },
    {
        "Field": "created_at",
        "0": "created_at",
        "Type": "timestamp",
        "1": "timestamp",
        "Null": "NO",
        "2": "NO",
        "Key": "",
        "3": "",
        "Default": "CURRENT_TIMESTAMP",
        "4": "CURRENT_TIMESTAMP",
        "Extra": "",
        "5": ""
    },
    {
        "Field": "updated_at",
        "0": "updated_at",
        "Type": "timestamp",
        "1": "timestamp",
        "Null": "NO",
        "2": "NO",
        "Key": "",
        "3": "",
        "Default": "1970-01-02 00:00:00",
        "4": "1970-01-02 00:00:00",
        "Extra": "",
        "5": ""
    },
    {
        "Field": "login_at",
        "0": "login_at",
        "Type": "timestamp",
        "1": "timestamp",
        "Null": "YES",
        "2": "YES",
        "Key": "",
        "3": "",
        "Default": null,
        "4": null,
        "Extra": "",
        "5": ""
    },
    {
        "Field": "login_count",
        "0": "login_count",
        "Type": "bigint(20)",
        "1": "bigint(20)",
        "Null": "YES",
        "2": "YES",
        "Key": "",
        "3": "",
        "Default": "0",
        "4": "0",
        "Extra": "",
        "5": ""
    },
    {
        "Field": "login_time",
        "0": "login_time",
        "Type": "bigint(20)",
        "1": "bigint(20)",
        "Null": "YES",
        "2": "YES",
        "Key": "",
        "3": "",
        "Default": "0",
        "4": "0",
        "Extra": "",
        "5": ""
    }
]', true);
    }
}

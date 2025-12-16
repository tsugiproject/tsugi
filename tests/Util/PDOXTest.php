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
        $this->assertArrayHasKey("Field", $column, 'Column description should have Field key');
        $this->assertArrayHasKey("Type", $column, 'Column description should have Type key');
        $this->assertArrayHasKey("Null", $column, 'Column description should have Null key');

        $this->assertFalse($PDOX->columnIsNull("key_id", $describe), 'key_id should not be null');
        $this->assertEquals("int", $PDOX->columnType("key_id", $describe));
        $this->assertEquals(11, $PDOX->columnLength("key_id", $describe));

        $this->assertTrue($PDOX->columnIsNull("secret", $describe));
        $this->assertEquals("text", $PDOX->columnType("secret", $describe));
        $this->assertEquals(0, $PDOX->columnLength("secret", $describe));

        $this->assertFalse($PDOX->columnIsNull("key_sha256", $describe));
        $this->assertEquals("char", $PDOX->columnType("key_sha256", $describe));
        $this->assertEquals(64, $PDOX->columnLength("key_sha256", $describe));

        $this->assertFalse($PDOX->columnIsNull("deleted", $describe), 'deleted should not be null');
        $this->assertEquals("tinyint", $PDOX->columnType("deleted", $describe), 'deleted should be tinyint type');
        $this->assertEquals(1, $PDOX->columnLength("deleted", $describe), 'deleted should have length 1');
    }

    public function testColumnIsNullThrowsException() {
        $describe = self::mockDescribe();
        $PDOX = new mockPDOX();
        $this->expectException(Exception::class);
        $PDOX->columnIsNull("zap", $describe);
    }

    public function testColumnTypeThrowsException() {
        $describe = self::mockDescribe();
        $PDOX = new mockPDOX();
        $this->expectException(Exception::class);
        $PDOX->columnType("zap", $describe);
    }

    public function testColumnLengthThrowsException() {
        $describe = self::mockDescribe();
        $PDOX = new mockPDOX();
        $this->expectException(Exception::class);
        $PDOX->columnLength("zap", $describe);
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

    public function testTimeFromMySQLTimeStamp() {
        // Test with a valid MySQL timestamp
        $datetime = "2024-01-15 14:30:45";
        $result = \Tsugi\Util\PDOX::timeFromMySQLTimeStamp($datetime);
        $this->assertIsInt($result);
        $this->assertEquals(strtotime($datetime), $result);

        // Test with another timestamp
        $datetime2 = "2023-12-25 00:00:00";
        $result2 = \Tsugi\Util\PDOX::timeFromMySQLTimeStamp($datetime2);
        $this->assertEquals(strtotime($datetime2), $result2);

        // Test with a date only (should still work)
        $datetime3 = "2024-01-15";
        $result3 = \Tsugi\Util\PDOX::timeFromMySQLTimeStamp($datetime3);
        $this->assertEquals(strtotime($datetime3), $result3);
    }

    public function testTimeToMySQLTimeStamp() {
        // Test with a Unix timestamp - use strtotime to ensure timezone consistency
        $datetime = "2024-01-15 14:30:45";
        $timestamp = strtotime($datetime);
        $result = \Tsugi\Util\PDOX::timeToMySQLTimeStamp($timestamp);
        $this->assertIsString($result);
        $this->assertEquals($datetime, $result);

        // Test with another timestamp
        $datetime2 = "2023-12-25 00:00:00";
        $timestamp2 = strtotime($datetime2);
        $result2 = \Tsugi\Util\PDOX::timeToMySQLTimeStamp($timestamp2);
        $this->assertEquals($datetime2, $result2);

        // Test with zero timestamp - date() uses server timezone, so we need to check against actual result
        $timestamp3 = 0;
        $result3 = \Tsugi\Util\PDOX::timeToMySQLTimeStamp($timestamp3);
        // The function uses date() which respects server timezone, so we verify it matches date() output
        $expected = date("Y-m-d H:i:s", $timestamp3);
        $this->assertEquals($expected, $result3);
    }

    public function testSqlDisplay() {
        // Test with numeric parameters
        $sql = "SELECT * FROM users WHERE id = :UID AND age > :AGE";
        $params = array(':UID' => 123, ':AGE' => 18);
        $result = \Tsugi\Util\PDOX::sqlDisplay($sql, $params);
        $this->assertStringContainsString('123', $result);
        $this->assertStringContainsString('18', $result);
        $this->assertStringNotContainsString(':UID', $result);
        $this->assertStringNotContainsString(':AGE', $result);

        // Test with string parameters
        $sql2 = "SELECT * FROM users WHERE name = :NAME AND email = :EMAIL";
        $params2 = array(':NAME' => "John Doe", ':EMAIL' => "john@example.com");
        $result2 = \Tsugi\Util\PDOX::sqlDisplay($sql2, $params2);
        $this->assertStringContainsString("'John Doe'", $result2);
        $this->assertStringContainsString("'john@example.com'", $result2);
        $this->assertStringNotContainsString(':NAME', $result2);
        $this->assertStringNotContainsString(':EMAIL', $result2);

        // Test with mixed numeric and string parameters
        $sql3 = "INSERT INTO users (id, name, age) VALUES (:ID, :NAME, :AGE)";
        $params3 = array(':ID' => 456, ':NAME' => "Jane", ':AGE' => 25);
        $result3 = \Tsugi\Util\PDOX::sqlDisplay($sql3, $params3);
        $this->assertStringContainsString('456', $result3);
        $this->assertStringContainsString("'Jane'", $result3);
        $this->assertStringContainsString('25', $result3);

        // Test with string containing special characters (should be HTML escaped)
        $sql4 = "SELECT * FROM users WHERE name = :NAME";
        $params4 = array(':NAME' => "O'Brien");
        $result4 = \Tsugi\Util\PDOX::sqlDisplay($sql4, $params4);
        // htmlspecialchars with ENT_QUOTES will escape single quotes to &#039;
        $this->assertStringContainsString("&#039;", $result4);
        // The result should be: SELECT * FROM users WHERE name = 'O&#039;Brien'
        // So it contains quotes around the value, but the apostrophe inside is escaped
        $this->assertStringContainsString("'O&#039;Brien'", $result4);
        // Verify the placeholder is replaced
        $this->assertStringNotContainsString(':NAME', $result4);

        // Test with string containing HTML characters (should be escaped)
        $sql5 = "SELECT * FROM users WHERE name = :NAME";
        $params5 = array(':NAME' => "<script>alert('xss')</script>");
        $result5 = \Tsugi\Util\PDOX::sqlDisplay($sql5, $params5);
        $this->assertStringContainsString("&lt;", $result5); // < should be escaped
        $this->assertStringContainsString("&gt;", $result5); // > should be escaped

        // Test with empty params array
        $sql6 = "SELECT * FROM users";
        $params6 = array();
        $result6 = \Tsugi\Util\PDOX::sqlDisplay($sql6, $params6);
        $this->assertEquals($sql6, $result6);

        // Test overlapping placeholder names - longer placeholders must be replaced first
        // This ensures :X1 is replaced before :X to avoid partial matches
        $sql7 = "SELECT :X, :X1 FROM users WHERE id = :ID";
        $params7 = array(':X' => 'short', ':X1' => 'longer', ':ID' => 123);
        $result7 = \Tsugi\Util\PDOX::sqlDisplay($sql7, $params7);
        // Verify :X1 was replaced correctly (not partially replaced by :X)
        $this->assertStringContainsString("'longer'", $result7);
        $this->assertStringContainsString("'short'", $result7);
        $this->assertStringContainsString('123', $result7);
        // Ensure :X1 wasn't corrupted by :X replacement
        $this->assertStringNotContainsString(':X1', $result7);
        $this->assertStringNotContainsString(':X', $result7);
        $this->assertStringNotContainsString(':ID', $result7);
        
        // Test another overlapping case with numeric values
        $sql8 = "SELECT :DAYS, :DAYS2 FROM users";
        $params8 = array(':DAYS' => 30, ':DAYS2' => 60);
        $result8 = \Tsugi\Util\PDOX::sqlDisplay($sql8, $params8);
        // Both should be replaced correctly
        $this->assertStringContainsString('30', $result8);
        $this->assertStringContainsString('60', $result8);
        $this->assertStringNotContainsString(':DAYS', $result8);
    }

    public function testLimitVars() {
        // Test filtering variables that exist in SQL (placeholders must be followed by whitespace)
        $sql = "SELECT * FROM users WHERE id = :UID AND age > :AGE AND name = :NAME";
        $vars = array(':UID' => 123, ':AGE' => 18, ':NAME' => 'John', ':EXTRA' => 'should not be included');
        $result = \Tsugi\Util\PDOX::limitVars($sql, $vars);
        $this->assertArrayHasKey(':UID', $result);
        $this->assertArrayHasKey(':AGE', $result);
        $this->assertArrayHasKey(':NAME', $result);
        $this->assertArrayNotHasKey(':EXTRA', $result);
        $this->assertEquals(123, $result[':UID']);
        $this->assertEquals(18, $result[':AGE']);
        $this->assertEquals('John', $result[':NAME']);

        // Test with variables in parentheses - note: placeholders followed by commas won't match
        // because the function splits by whitespace and :UID, becomes a single token
        $sql2 = "INSERT INTO users (id, name) VALUES (:UID , :NAME )";
        $vars2 = array(':UID' => 456, ':NAME' => 'Jane', ':EXTRA' => 'should not be included');
        $result2 = \Tsugi\Util\PDOX::limitVars($sql2, $vars2);
        $this->assertArrayHasKey(':UID', $result2);
        $this->assertArrayHasKey(':NAME', $result2);
        $this->assertArrayNotHasKey(':EXTRA', $result2);

        // Test with empty vars array
        $sql3 = "SELECT * FROM users WHERE id = :UID";
        $vars3 = array();
        $result3 = \Tsugi\Util\PDOX::limitVars($sql3, $vars3);
        $this->assertIsArray($result3);
        $this->assertEmpty($result3);

        // Test with SQL that has no placeholders
        $sql4 = "SELECT * FROM users";
        $vars4 = array(':UID' => 123);
        $result4 = \Tsugi\Util\PDOX::limitVars($sql4, $vars4);
        $this->assertIsArray($result4);
        $this->assertEmpty($result4);

        // Test that placeholders followed by commas don't match (limitation of the function)
        $sql5 = "INSERT INTO users (id, name) VALUES (:UID, :NAME)";
        $vars5 = array(':UID' => 456, ':NAME' => 'Jane');
        $result5 = \Tsugi\Util\PDOX::limitVars($sql5, $vars5);
        // Only :NAME matches because it's at the end, :UID, doesn't match because of the comma
        $this->assertArrayHasKey(':NAME', $result5);
        $this->assertArrayNotHasKey(':UID', $result5);
    }
}

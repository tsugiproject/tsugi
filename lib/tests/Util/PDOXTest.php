<?php

require_once "src/Util/PDOX.php";

class mockPDOX extends \Tsugi\Util\PDOX
{
    public function __construct ()
    {}

}

/**
 * Tests for PDOX utility methods
 * 
 * This test suite covers the utility methods added to PDOX for timestamp conversion,
 * SQL display formatting, and variable filtering. These methods are static helpers
 * that don't require a database connection.
 */
class PDOXTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test column metadata methods (describeColumn, columnIsNull, columnType, columnLength)
     * These methods parse MySQL SHOW COLUMNS output to extract column information.
     */
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

    /**
     * Test timeFromMySQLTimeStamp() - converts MySQL timestamp string to Unix timestamp
     * 
     * This method converts MySQL datetime format (YYYY-MM-DD HH:MM:SS) to a Unix timestamp integer.
     * Uses PHP's strtotime() internally, so it respects server timezone settings.
     */
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

    /**
     * Test timeToMySQLTimeStamp() - converts Unix timestamp to MySQL format string
     * 
     * This method converts a Unix timestamp integer to MySQL datetime format (YYYY-MM-DD HH:MM:SS).
     * Uses PHP's date() function, which respects server timezone settings (not UTC).
     * Important: The zero timestamp test accounts for timezone differences.
     */
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

    /**
     * Test sqlDisplay() - creates display version of SQL with parameter substitution
     * 
     * This method is for debugging/display purposes only - never use for actual SQL execution!
     * It substitutes placeholders with actual values for easier debugging.
     * 
     * Key features tested:
     * - Numeric values inserted without quotes
     * - String values wrapped in single quotes with HTML escaping
     * - Null values displayed as unquoted 'NULL' (prevents errors)
     * - Overlapping placeholder names handled correctly (longest first)
     * - HTML special characters escaped to prevent XSS
     * 
     * Critical: Placeholders are sorted by length (longest first) to prevent partial matches.
     * Example: :X1 must be replaced before :X, otherwise :X would corrupt :X1.
     */
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

        // Test overlapping placeholder names - CRITICAL: longer placeholders must be replaced first
        // Problem solved: Without sorting by length, :X would be replaced first, causing :X1 to become "short1"
        // instead of "longer". The implementation sorts placeholders by length (longest first) to prevent this.
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
        // Both should be replaced correctly (DAYS2 before DAYS)
        $this->assertStringContainsString('30', $result8);
        $this->assertStringContainsString('60', $result8);
        $this->assertStringNotContainsString(':DAYS', $result8);

        // Test null value handling - shows NULL instead of causing errors
        // Recent change: null values are explicitly checked and displayed as unquoted 'NULL'
        // This prevents warnings/errors when null values are passed to the function
        $sql9 = "SELECT * FROM users WHERE id = :ID AND name = :NAME";
        $params9 = array(':ID' => 123, ':NAME' => null);
        $result9 = \Tsugi\Util\PDOX::sqlDisplay($sql9, $params9);
        $this->assertStringContainsString('123', $result9);
        $this->assertStringContainsString('NULL', $result9);
        $this->assertStringNotContainsString(':ID', $result9);
        $this->assertStringNotContainsString(':NAME', $result9);
        // NULL should not be quoted (matches SQL syntax)
        $this->assertStringNotContainsString("'NULL'", $result9);
        $this->assertStringNotContainsString("NULL'", $result9);

        // Test multiple null values
        $sql10 = "INSERT INTO users (id, name, email) VALUES (:ID, :NAME, :EMAIL)";
        $params10 = array(':ID' => 456, ':NAME' => null, ':EMAIL' => null);
        $result10 = \Tsugi\Util\PDOX::sqlDisplay($sql10, $params10);
        $this->assertStringContainsString('456', $result10);
        $this->assertEquals(2, substr_count($result10, 'NULL'), 'Should have two NULL values');
        $this->assertStringNotContainsString(':ID', $result10);
        $this->assertStringNotContainsString(':NAME', $result10);
        $this->assertStringNotContainsString(':EMAIL', $result10);

        // Test placeholder appearing multiple times in SQL (all occurrences should be replaced)
        $sql11 = "SELECT * FROM users WHERE id = :ID OR parent_id = :ID";
        $params11 = array(':ID' => 789);
        $result11 = \Tsugi\Util\PDOX::sqlDisplay($sql11, $params11);
        $this->assertEquals(2, substr_count($result11, '789'), 'Placeholder appearing twice should be replaced twice');
        $this->assertStringNotContainsString(':ID', $result11);

        // Test empty string value
        $sql12 = "SELECT * FROM users WHERE name = :NAME";
        $params12 = array(':NAME' => '');
        $result12 = \Tsugi\Util\PDOX::sqlDisplay($sql12, $params12);
        $this->assertStringContainsString("''", $result12, 'Empty string should be wrapped in quotes');
        $this->assertStringNotContainsString(':NAME', $result12);

        // Test zero values (numeric zero)
        $sql13 = "SELECT * FROM users WHERE id = :ID AND count = :COUNT";
        $params13 = array(':ID' => 0, ':COUNT' => 0);
        $result13 = \Tsugi\Util\PDOX::sqlDisplay($sql13, $params13);
        $this->assertEquals(2, substr_count($result13, '0'), 'Zero should appear twice');
        $this->assertStringNotContainsString(':ID', $result13);
        $this->assertStringNotContainsString(':COUNT', $result13);

        // Test float values
        $sql14 = "SELECT * FROM products WHERE price = :PRICE";
        $params14 = array(':PRICE' => 19.99);
        $result14 = \Tsugi\Util\PDOX::sqlDisplay($sql14, $params14);
        $this->assertStringContainsString('19.99', $result14, 'Float value should be displayed correctly');
        $this->assertStringNotContainsString(':PRICE', $result14);

        // Test boolean values (PHP treats true/false as numeric in some contexts)
        // Note: In PHP, is_numeric(true) returns false, is_numeric(false) returns false
        // So booleans will be treated as strings and wrapped in quotes
        $sql15 = "SELECT * FROM users WHERE active = :ACTIVE AND verified = :VERIFIED";
        $params15 = array(':ACTIVE' => true, ':VERIFIED' => false);
        $result15 = \Tsugi\Util\PDOX::sqlDisplay($sql15, $params15);
        // Booleans are not numeric, so they'll be treated as strings
        // true becomes "1", false becomes "" (empty string) when converted to string
        $this->assertStringNotContainsString(':ACTIVE', $result15);
        $this->assertStringNotContainsString(':VERIFIED', $result15);

        // Test string "0" (should be treated as string, not numeric)
        $sql16 = "SELECT * FROM users WHERE code = :CODE";
        $params16 = array(':CODE' => '0');
        $result16 = \Tsugi\Util\PDOX::sqlDisplay($sql16, $params16);
        // String "0" is numeric in PHP, so it will be displayed without quotes
        $this->assertStringContainsString('0', $result16);
        $this->assertStringNotContainsString(':CODE', $result16);
    }

    /**
     * Test limitVars() - filters SQL substitution variables to only those used in the query
     * 
     * This method extracts only the variables from an array that are actually referenced
     * in the SQL query. Useful for cleaning up parameter arrays before execution.
     * 
     * Limitation: Placeholders must be followed by whitespace to be detected.
     * Placeholders immediately followed by commas (e.g., :UID,) won't match because
     * the function splits SQL by whitespace.
     */
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

    /**
     * Test indexExists() - checks if an index exists on a table
     * 
     * This method uses indexes() to get all indexes for a table and checks if
     * the specified index name is in that list. Since it requires a database
     * connection, we mock the indexes() method to return a known set of indexes.
     */
    public function testIndexExists() {
        $PDOX = $this->getMockBuilder(mockPDOX::class)
            ->onlyMethods(['indexes'])
            ->getMock();
        
        // Mock indexes() to return a known set of index names
        $mockIndexes = ['PRIMARY', 'idx_user_id', 'idx_email', 'idx_created_at'];
        $PDOX->method('indexes')->willReturn($mockIndexes);
        
        // Test that existing indexes return true
        $this->assertTrue($PDOX->indexExists('PRIMARY', 'test_table'), 
            'indexExists should return true for PRIMARY index');
        $this->assertTrue($PDOX->indexExists('idx_user_id', 'test_table'), 
            'indexExists should return true for idx_user_id index');
        $this->assertTrue($PDOX->indexExists('idx_email', 'test_table'), 
            'indexExists should return true for idx_email index');
        $this->assertTrue($PDOX->indexExists('idx_created_at', 'test_table'), 
            'indexExists should return true for idx_created_at index');
        
        // Test that non-existing indexes return false
        $this->assertFalse($PDOX->indexExists('nonexistent_index', 'test_table'), 
            'indexExists should return false for nonexistent index');
        $this->assertFalse($PDOX->indexExists('idx_missing', 'test_table'), 
            'indexExists should return false for missing index');
        
        // Test with empty indexes array
        $PDOXEmpty = $this->getMockBuilder(mockPDOX::class)
            ->onlyMethods(['indexes'])
            ->getMock();
        $PDOXEmpty->method('indexes')->willReturn([]);
        
        $this->assertFalse($PDOXEmpty->indexExists('any_index', 'test_table'), 
            'indexExists should return false when no indexes exist');
        
        // Test case sensitivity (index names are case-sensitive in MySQL)
        // Note: in_array() is case-sensitive, so indexExists() will be case-sensitive
        $this->assertFalse($PDOX->indexExists('primary', 'test_table'), 
            'indexExists should be case-sensitive - lowercase primary should not match PRIMARY');
        $this->assertFalse($PDOX->indexExists('IDX_USER_ID', 'test_table'), 
            'indexExists should be case-sensitive - uppercase IDX_USER_ID should not match lowercase idx_user_id');
        $this->assertFalse($PDOX->indexExists('IDX_EMAIL', 'test_table'), 
            'indexExists should be case-sensitive - uppercase IDX_EMAIL should not match lowercase idx_email');
    }
}

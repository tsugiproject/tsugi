<?php

require_once "src/UI/Table.php";

use \Tsugi\UI\Table;

class TableTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $table = new Table();
        $this->assertInstanceOf(\Tsugi\UI\Table::class, $table, 'Table should instantiate correctly');
    }

    /**
     * Folder admin URLs end in / in the browser, while the router stores the
     * path without that slash. A last-segment href (profile?order_by=) then
     * resolves to /admin/profile/profile. The sort link must be query-only.
     */
    public function testColumnSortLinkStaysOnTheCurrentUrl() {
        global $CFG;
        $CFG = $CFG ?? new \Tsugi\Config\ConfigInfo('dir', 'www');
        $CFG->wwwroot = 'https://local.dj4e.com/tsugi';

        $_SERVER['REQUEST_URI'] = '/tsugi/admin/profile';
        $_SERVER['SERVER_PORT'] = '443';
        $_SERVER['HTTP_HOST'] = 'local.dj4e.com';
        $_GET = array();

        if ( ! function_exists('htmlent_utf8') ) {
            function htmlent_utf8($string) {
                return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8');
            }
        }
        if ( ! function_exists('_m') ) {
            function _m($message, $textdomain = false) {
                return $message;
            }
        }

        $rows = array(
            array('profile_id' => 1, 'premium_at' => '2026-01-01'),
        );

        ob_start();
        Table::pagedTable($rows, array(), array('premium_at'));
        $html = ob_get_clean();

        $this->assertStringContainsString('<a href="?order_by=premium_at">Premium At</a>', $html);
        $this->assertStringNotContainsString('href="profile?order_by=premium_at"', $html);
    }

    public function testPagedQueryAsciiSearchStaysOnTheColumn() {
        $values = array();
        $sql = Table::pagedQuery(
            "SELECT user_id FROM lti_user WHERE user_id > :MIN",
            $values,
            array('user_key', 'displayname'),
            array('displayname'),
            array('search_text' => 'Minh')
        );

        $this->assertStringContainsString(
            'WHERE ( user_key LIKE :SEARCH0 OR displayname LIKE :SEARCH1 ) AND',
            $sql
        );
        $this->assertStringNotContainsString('CONVERT(', $sql);
        $this->assertSame('%Minh%', $values[':SEARCH0']);
        $this->assertSame('%Minh%', $values[':SEARCH1']);
    }

    /**
     * A pattern outside ASCII cannot be coerced into an ascii_bin column.
     * MySQL 1267 aborts the whole OR, including displayname.
     */
    public function testPagedQueryNonAsciiSearchWidensEveryColumn() {
        $values = array();
        $sql = Table::pagedQuery(
            "SELECT user_id FROM lti_user WHERE user_id > :MIN",
            $values,
            array('user_key', 'displayname'),
            array('displayname'),
            array('search_text' => 'Мина')
        );

        $this->assertStringContainsString(
            'WHERE ( CONVERT(user_key USING utf8mb4) LIKE :SEARCH0 OR CONVERT(displayname USING utf8mb4) LIKE :SEARCH1 ) AND',
            $sql
        );
        $this->assertSame('%Мина%', $values[':SEARCH0']);
        $this->assertSame('%Мина%', $values[':SEARCH1']);
    }

}

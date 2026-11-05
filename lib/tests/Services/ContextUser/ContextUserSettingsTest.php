<?php

use Tsugi\Core\Cache;
use Tsugi\Services\ContextUser\ContextUserSettings;

class ContextUserSettingsTest extends \PHPUnit\Framework\TestCase
{
    public function testPlainStripsTagsAndKeepsNewlines() {
        $text = ContextUserSettings::plain("  <b>Don't forget</b>\r\nthe midterm.  ");
        $this->assertSame("Don't forget\nthe midterm.", $text);
    }

    public function testPlainRejectsMoreThan500Characters() {
        $this->assertNull(ContextUserSettings::plain(str_repeat('a', 501)));
        $this->assertSame(str_repeat('a', 500), ContextUserSettings::plain(str_repeat('a', 500)));
    }

    public function testPlainTurnsBlankMarkupIntoAnEmptyString() {
        $this->assertSame('', ContextUserSettings::plain('   <br>  '));
        $this->assertSame('', ContextUserSettings::plain(null));
    }

    public function testHomeOffFromPostKeepsOnlyBlocksTheyUnchecked() {
        $off = ContextUserSettings::homeOffFromPost(
            array('hero', 'calendar', 'page:7'),
            array('hero', 'page:99', 'calendar')
        );
        $this->assertSame(array('page:7'), $off);
    }

    public function testHomeOffFromPostHidesEverythingWhenNothingIsChecked() {
        $off = ContextUserSettings::homeOffFromPost(array('hero', 'notes'), null);
        $this->assertSame(array('hero', 'notes'), $off);
    }

    public function testHomeOffFromPostKeepsHiddenBlocksThatAreNotOnTheForm() {
        $off = ContextUserSettings::homeOffFromPost(
            array('hero', 'calendar'),
            array('hero'),
            array('page:7' => true, 'calendar' => true)
        );
        $this->assertSame(array('page:7', 'calendar'), $off);
    }

    public function testViewFromPostKeepsAnOfferedView() {
        $this->assertSame('outline', ContextUserSettings::viewFromPost('outline', array('lessons', 'outline')));
        $this->assertSame('', ContextUserSettings::viewFromPost('content', array('lessons', 'outline')));
        $this->assertSame('', ContextUserSettings::viewFromPost('', array('lessons', 'outline')));
    }

    public function testStepsHiddenIsOnlyAnExplicitOff() {
        $this->assertTrue(ContextUserSettings::stepsHidden(array('steps' => false)));
        $this->assertFalse(ContextUserSettings::stepsHidden(array()));
        $this->assertFalse(ContextUserSettings::stepsHidden(array('steps' => true)));
    }

    public function testHomeOffLooksUpStoredKeys() {
        $set = ContextUserSettings::homeOff(array('home_off' => array('calendar', '', 4)));
        $this->assertSame(array('calendar' => true), $set);
    }

    public function testSessionCacheLoadsOnceUntilTheCourseChangesOrTheGearSaves() {
        global $CFG, $PDOX;
        $previousCfg = $CFG ?? null;
        $previousPdo = $PDOX ?? null;
        $previousSession = $_SESSION ?? null;
        $CFG = new \stdClass();
        $CFG->dbprefix = '';
        $_SESSION = array();
        $pdo = new class {
            public $reads = 0;
            public function rowDie($sql, $params = array()) {
                $this->reads++;
                return array('note' => 'hi', 'settings' => '{"view":"content"}');
            }
            public function queryDie($sql, $params = array()) {
                return true;
            }
        };
        $PDOX = $pdo;
        try {
            $this->clearSettingsRequestCache();
            $first = ContextUserSettings::load(7, 3);
            $this->assertSame('content', ContextUserSettings::contentView($first['settings']));
            $this->assertSame(1, $pdo->reads);

            $this->clearSettingsRequestCache();
            $second = ContextUserSettings::load(7, 3);
            $this->assertSame('hi', $second['note']);
            $this->assertSame(1, $pdo->reads);

            $this->clearSettingsRequestCache();
            ContextUserSettings::load(8, 3);
            $this->assertSame(2, $pdo->reads);

            $this->clearSettingsRequestCache();
            ContextUserSettings::saveGear(8, 3, array('home_off' => array(), 'view' => 'outline'));
            $this->assertNull(Cache::getContext(ContextUserSettings::CACHE_LOCATION, 8));

            $this->clearSettingsRequestCache();
            ContextUserSettings::load(8, 3);
            $this->assertSame(4, $pdo->reads);
        } finally {
            $this->clearSettingsRequestCache();
            $CFG = $previousCfg;
            $PDOX = $previousPdo;
            if ( is_array($previousSession) ) {
                $_SESSION = $previousSession;
            } else {
                $_SESSION = array();
            }
        }
    }

    private function clearSettingsRequestCache() {
        $prop = new \ReflectionProperty(ContextUserSettings::class, 'cache');
        $prop->setValue(null, array());
    }
}

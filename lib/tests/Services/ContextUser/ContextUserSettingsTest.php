<?php

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

    public function testHomeOffLooksUpStoredKeys() {
        $set = ContextUserSettings::homeOff(array('home_off' => array('calendar', '', 4)));
        $this->assertSame(array('calendar' => true), $set);
    }
}

<?php

require_once "src/Util/Color.php";
require_once "src/Util/HSLuv.php";
require_once "src/UI/Theme.php";

use \Tsugi\Util\Color;
use \Tsugi\Util\HSLuv;
use \Tsugi\UI\Theme;

class ThemeTest extends \PHPUnit\Framework\TestCase
{
    public function testOne() {
        $mid = Theme::findLMidPointForHue("#0000FF");
        $this->assertEquals($mid, '#6666ff');
        $pair = Theme::luminosityPair(7.0, $mid);
        $this->assertEquals($pair, array('#0000a7', '#b7b7ff'));
    }

    public function testTheme() {
        $tsugi_dark = '#99FF99';
        $tsugicolors = Theme::deriveTsugiColors($tsugi_dark);
        $expected = array(
            'tsugi-theme-dark-background' => '#000700',
            'tsugi-theme-dark-text' => '#071507',
            'tsugi-theme-dark-darker' => '#001c00',
            'tsugi-theme-dark' => '#003200',
            'tsugi-theme-dark-accent' => '#005200',
            'tsugi-theme-mid' => '#008e00',
            'tsugi-theme-light-accent' => '#2aff2a',
            'tsugi-theme-light' => '#76ff76',
            'tsugi-theme-light-lighter' => '#b0ffb0',
            'tsugi-theme-light-text' => '#c4ebc4',
            'tsugi-theme-light-background' => '#ecffec',
        );
        $this->assertEquals($tsugicolors, $expected);

        $dark_mode = true;
        $legacy_theme = Theme::getLegacyTheme($tsugi_dark, $dark_mode);
        $expected = array(
            'background-color' => '#000700',
            'primary-menu' => '#003200',
            'primary-border' => '#2aff2a',
            'text-light' => '#76ff76',
            'primary' => '#76ff76',
            'secondary-menu' => '#76ff76',
            'text' => '#c4ebc4',
            'primary-darkest' => '#c4ebc4',
        );
        $this->assertEquals($legacy_theme, $expected);

        $dark_mode = false;
        $legacy_theme = Theme::getLegacyTheme($tsugi_dark, $dark_mode);
        $expected = array(
            'text' => '#071507',
            'text-menu' => '#071507',
            'primary-darkest' => '#071507',
            'primary-darker' => '#001c00',
            'primary' => '#003200',
            'primary-menu' => '#003200',
            'text-light' => '#003200',
            'primary-border' => '#005200',
            'secondary' => '#76ff76',
            'background-color' => '#ecffec',
        );
        $this->assertEquals($legacy_theme, $expected);
    }


}

<?php

$toppath = dirname(__FILE__).'/../..';

// require_once "include/pre_config.php";
require_once "src/Core/I18N.php";
require_once "src/UI/HandleBars.php";

// require_once $toppath.'/vendor/symfony/translation/Translator.php';
// require_once $toppath.'/vendor/symfony/translation/TranslatorInterface.php';
// require_once $toppath.'/vendor/symfony/translation/TranslatorBagInterface.php';
// require_once $toppath.'/vendor/symfony/translation/TranslatorInterface.php';
// require_once $toppath.'/vendor/symfony/translation/Translator.php';
// require_once $toppath.'/vendor/symfony/translation/MessageSelector.php';
// require_once $toppath.'/vendor/symfony/translation/MessageCatalogueInterface.php';
// require_once $toppath.'/vendor/symfony/translation/MetadataAwareInterface.php';
// require_once $toppath.'/vendor/symfony/translation/MessageCatalogue.php';

use \Tsugi\UI\HandleBars;

class HandleBarsTest extends PHPUnit_Framework_TestCase
{
    public function testProcess() {
        $input = "one {{ two }} {{__ 'three' }}{{__ 'four'}}";
        $output = HandleBars::templateProcess($input);
        $this->assertEquals($output,'one {{ two }} threefour');
    }


}

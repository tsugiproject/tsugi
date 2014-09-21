<?php
require_once "../../config.php";
use \Tsugi\Core\Settings;
use \Tsugi\UI\SettingsForm;
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

use \Tsugi\Core\LTIX;

$LTI = LTIX::requireData(array('user_id', 'link_id', 'role','context_id'));
$p = $CFG->dbprefix;

if ( SettingsForm::handleSettingsPost() ) {
    header( 'Location: '.addSession('index.php') ) ;
    return;
}
Here is some documentation on the software used to build these unit tests:
<ul>
<li><a href="http://symfony.com/doc/current/components/dom_crawler.html" target="_new">
http://symfony.com/doc/current/components/dom_crawler.html
</a></li>
<li><a href="http://api.symfony.com/2.3/Symfony/Component/BrowserKit.html" target="_new">
http://api.symfony.com/2.3/Symfony/Component/BrowserKit.html
</a></li>
<li><a href="http://api.symfony.com/2.3/Symfony/Component/DomCrawler/Crawler.html" target="_new">
http://api.symfony.com/2.3/Symfony/Component/DomCrawler/Crawler.html
</a></li>
</ul>
</p>

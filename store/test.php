<?php

use \Tsugi\Util\U;
use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../config.php";
require_once "../admin/admin_util.php";
require_once("../dev/dev-data.php");

session_start();

$p = $CFG->dbprefix;
LTIX::getConnection();

$OUTPUT->header();
?>
<style>
    .card {
        border: 1px solid black;
        margin: 5px;
        padding: 5px;
    }
#loader {
      position: fixed;
      left: 0px;
      top: 0px;

      height: 100%;
      background-color: white;
      margin: 0;
      z-index: 100;
}
#XbasicltiDebugToggle {
    display: none;
}
</style>
<?php

$registrations = findAllRegistrations();
if ( count($registrations) < 1 ) $registrations = false;

// Switch user data if requested
$identity = U::get($_GET,'identity');
if ( $identity ) foreach( $lms_identities as $lms_identity => $lms_data ) {
    if ( $identity != $lms_identity ) continue;
    foreach ( $lms_data as $k => $val ) {
          $lmsdata[$k] = $lms_data[$k];
    }
}

// Load up the key and secret.
$key = '12345';
$secret = false;
if ( is_string($CFG->DEVELOPER) ) $key = $CFG->DEVELOPER;
$row = $PDOX->rowDie(
    "SELECT secret FROM {$CFG->dbprefix}lti_key WHERE key_key = :DKEY",
    array(':DKEY' => $key));
$secret = $row ? $row['secret'] : false;
if ( $secret === false ) {
    $_SESSION['error'] = 'Developer mode not properly configured';
    header('Location: '.$CFG->wwwroot);
    return;
}


$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

if ( ! ( $registrations ) ) {
    echo("<p>No tools found.</p>");
    $OUTPUT->footer();
    return;
}

$rest_path = U::rest_path();
$install = $rest_path->extra;

    if ( ! isset($registrations[$install])) {
        echo("<p>Tool registration for ".htmlentities($install)." not found</p>\n");
        $OUTPUT->footer();
        return;
    }
    $tool = $registrations[$install];

    $title = $tool['name'];
    $text = $tool['description'];
    $fa_icon = isset($tool['FontAwesome']) ? $tool['FontAwesome'] : false;
    $icon = false;
    if ( $fa_icon !== false ) {
        $icon = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';
    }

    if ( $fa_icon ) {
        echo('<i class="hidden-xs fa '.$fa_icon.' fa-3x" style="color: #1894C7; float:right; margin: 2px"></i>');
    }
    echo('<center>');
    echo("<b>".htmlent_utf8($title)."</b>\n");
    echo("</center>\n");
?>
<ul class="nav nav-tabs">
  <li class="active"><a href="#test" onclick="console.log('yada');" data-toggle="tab" aria-expanded="true">Test</a></li>
  <li><a href="#identity" data-toggle="tab" aria-expanded="false">
                    <?php if ( strlen($lmsdata['lis_person_name_full']) > 0 ) echo($lmsdata['lis_person_name_full']);
                        else echo('Anonymous');
                    ?>
        &#9660;
        </a>
      </li>
  <!-- <li><a href="#grades" data-toggle="tab" aria-expanded="false">Grades</a></li> -->
  <li><a href="#debug" data-toggle="tab" aria-expanded="false">Debug</a></li>
  <li class="hidden-xs"><a href="<?= $rest_path->parent ?>/details/<?= urlencode($install) ?>" role="button">Back to Details</a></li>
  <li class="visible-xs"><a href="<?= $rest_path->parent ?>/details/<?= urlencode($install) ?>" role="button">Details</a></li>
</ul>
<div id="myTabContent" class="tab-content" style="margin-top:10px;">
  <div class="tab-pane fade" id="identity">
    <p>You have three four identities that you can use to test the tool.
    There is an instructor, two students, and an anonymous student.   You can quickly use this screen 
    to switch back and forth between these identities to test tool functionality under the differet roles.
    </p>
    <ul>
      <li><a href="<?= $rest_path->full ?>?identity=instructor">Jane Instructor</a>
      <?php if ( isset($CFG->fallbacklocale) && $CFG->fallbacklocale ) {
          echo(' (Prefers '.$CFG->fallbacklocale.')');
      } ?>
      </li>
      <li><a href="<?= $rest_path->full ?>?identity=learner1">Sue Student</a> (Prefers EN-us)</li>
      <li><a href="<?= $rest_path->full ?>?identity=learner2">Ed Student</a> (Prefers ES-es)</li>
      <li><a href="<?= $rest_path->full ?>?identity=learner3">Anonymous</a> (Takes language from browser header)</li>
    </ul>
  </div>
  <div class="tab-pane fade active in" id="test">
<?php
$parms = $lmsdata;
// Cleanup parms before we sign
foreach( $parms as $k => $val ) {
    if (strlen(trim($parms[$k]) ) < 1 ) {
       unset($parms[$k]);
    }
}

// Use the actual direct URL to the launch
$endpoint = $tool['url'];
$endpoint = U::remove_relative_path($endpoint);

// Add oauth_callback to be compliant with the 1.0A spec
$parms["oauth_callback"] = "about:blank";
$parms['resource_link_id'] = md5($endpoint);
$parms['resource_link_title'] = $install;
$outcomes = false;
if ( $outcomes ) {
    $parms['lis_outcome_service_url'] = $outcomes;
}

$parms['launch_presentation_return_url'] = $rest_path->current . '/return';

$tool_consumer_instance_guid = $lmsdata['tool_consumer_instance_guid'];
$tool_consumer_instance_description = $lmsdata['tool_consumer_instance_description'];

$parms = LTI::signParameters($parms, $endpoint, "POST", $key, $secret,
        "Finish Launch", $tool_consumer_instance_guid, $tool_consumer_instance_description);

ksort($parms);
$content = LTI::postLaunchHTML($parms, $endpoint, isset($_POST['debug']),
       "width=\"100%\" height=\"900\" scrolling=\"auto\" frameborder=\"1\" transparency");
print($content);
?>
  </div>
  <div class="tab-pane fade" id="debug">
    <pre>
    Launch Parameters:
    <?php print_r($parms) ?>
    <hr/>
    Base String:
    <?= htmlentities(LTI::getLastOAuthBodyBaseString()) ?>
    <hr/>
    Tool Data Within Tsugi: 
    <?php print_r($tool); ?>
    </pre>
  </div>
</div>
<?php
    echo("<!-- \n");print_r($tool);echo("\n-->\n");
    $OUTPUT->footer();

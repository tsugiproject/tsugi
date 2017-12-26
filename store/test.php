<?php

use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../config.php";
require_once "../admin/admin_util.php";
require_once("../dev/dev-data.php");

session_start();

$p = $CFG->dbprefix;

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
        echo('<i class="fa '.$fa_icon.' fa-3x" style="color: #1894C7; float:right; margin: 2px"></i>');
    }
    echo('<center>');
    echo("<b>".htmlent_utf8($title)."</b>\n");
    echo("</center>\n");
?>
<ul class="nav nav-tabs">
  <li class="active"><a href="#test" onclick="alert('yada');" data-toggle="tab" aria-expanded="true">Test</a></li>
  <li><a href="#debug" data-toggle="tab" aria-expanded="false">Debug Data</a></li>
  <li><a href="#identity" data-toggle="tab" aria-expanded="false">
                    <?php if ( strlen($lmsdata['lis_person_name_full']) > 0 ) echo($lmsdata['lis_person_name_full']);
                        else echo('Anonymous');
                    ?>
        &#9660;
        </a>
      </li>
  <!-- <li><a href="#grades" data-toggle="tab" aria-expanded="false">Grades</a></li> -->
  <li><a href="<?= $rest_path->parent ?>/details/<?= urlencode($install) ?>" role="button">Back to Details</a></li>
</ul>
<div id="myTabContent" class="tab-content" style="margin-top:10px;">
  <div class="tab-pane fade" id="identity">
    <p>You have three four identities that you can use to thest the tool.
    There is an instructor, two students, and an anonymous student.   You can quickly use this screen 
    to switch back and forth between these identities to test tool functionality under the differet roles.
    </p>
              <ul>
                <li><a href="<?= $rest_path->full ?>?identity=instructor">Jane Instructor</a></li>
                <li><a href="<?= $rest_path->full ?>?identity=learner1">Sue Student</a></li>
                <li><a href="<?= $rest_path->full ?>?identity=learner2">Ed Student</a></li>
                <li><a href="<?= $rest_path->full ?>?identity=learner3">Anonymous</a></li>
              </ul>
  </div>
  <div class="tab-pane fade active in" id="test">
    TEST
  </div>
  <div class="tab-pane fade" id="debug">
    DEBUG
  </div>
</div>
<?php
    echo("<!-- \n");print_r($tool);echo("\n-->\n");
    $OUTPUT->footer();

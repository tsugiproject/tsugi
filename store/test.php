<?php

use \Tsugi\Util\U;
use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\Theme;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../config.php";
require_once "../admin/admin_util.php";
require_once("dev-data.php");

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
#body_container {
    padding-top: 40px;
}
.breadcrumb {
    background-color: var(--background-color);
    margin-bottom: 5px;
    padding: 5px 15px;
}
.breadcrumb>.active {
    color: var(--text-light);
}
.test-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2rem;
    background-color: var(--background-focus);
    border-bottom: 8px solid var(--secondary);
    flex-wrap: wrap;
}
.test-header-icon {
    font-size: 3rem;
    margin-right: 20px;
    color: var(--text);
}
.test-header-text {
    font-size: 3rem;
    line-height: 3rem;
    color: var(--text);
}
@media(max-width: 768px) {
    .title-container {
        display: flex;
        width: 100%;
        justify-content: center;
    }
    .install-button-container {
        width: 100%;
        text-align: center;
    }
    .install-button-container .btn {
        width: 275px;
        margin-top: 20px;
    }
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

// Allow minimal overrides in developer mode for QA launches
if ( $CFG->DEVELOPER ) {
    $override_map = array(
        'roles' => array('roles', 'role'),
        'context_id' => array('context_id'),
        'context_title' => array('context_title', 'course_title'),
        'context_label' => array('context_label', 'course_label'),
        'context_type' => array('context_type', 'course_type'),
        'lis_course_offering_sourcedid' => array('lis_course_offering_sourcedid', 'course_offering_sourcedid'),
        'lis_course_section_sourcedid' => array('lis_course_section_sourcedid', 'course_section_sourcedid'),
        'resource_link_id' => array('resource_link_id', 'link_id'),
        'resource_link_title' => array('resource_link_title', 'link_title'),
        'resource_link_description' => array('resource_link_description', 'link_description'),
        'lis_person_name_full' => array('lis_person_name_full', 'user_name'),
        'lis_person_name_given' => array('lis_person_name_given', 'user_given'),
        'lis_person_name_family' => array('lis_person_name_family', 'user_family'),
        'lis_person_contact_email_primary' => array('lis_person_contact_email_primary', 'user_email'),
        'lis_person_sourcedid' => array('lis_person_sourcedid', 'user_sourcedid'),
        'user_id' => array('user_id', 'user_key'),
        'launch_presentation_locale' => array('launch_presentation_locale', 'locale'),
        'tool_consumer_info_product_family_code' => array('tool_consumer_info_product_family_code', 'tc_product_family'),
        'tool_consumer_info_version' => array('tool_consumer_info_version', 'tc_version'),
        'tool_consumer_instance_guid' => array('tool_consumer_instance_guid', 'tc_guid'),
        'tool_consumer_instance_description' => array('tool_consumer_instance_description', 'tc_description'),
        'lis_outcome_service_url' => array('lis_outcome_service_url', 'outcome_service_url'),
        'lis_result_sourcedid' => array('lis_result_sourcedid', 'result_sourcedid'),
        'ext_memberships_id' => array('ext_memberships_id', 'memberships_id'),
        'ext_memberships_url' => array('ext_memberships_url', 'memberships_url'),
        'memberships_url' => array('memberships_url'),
        'lineitems_url' => array('lineitems_url'),
    );

    foreach ( $override_map as $target => $keys ) {
        foreach ( $keys as $key ) {
            if ( array_key_exists($key, $_GET) ) {
                $lmsdata[$target] = $_GET[$key];
                break;
            }
        }
    }

    foreach ( $_GET as $key => $value ) {
        if ( strpos($key, 'custom_') === 0 ) {
            $lmsdata[$key] = $value;
        }
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

$rest_path = U::rest_path();
$install = $rest_path->extra;

if ($registrations && isset($registrations[$install])) {
    $tool = $registrations[$install];
    $ltiurl = $tool['url'];
    $text = $tool['description'];
    $title = $tool['name'];
    $fa_icon = isset($tool['FontAwesome']) ? $tool['FontAwesome'] : false;
    $icon = false;
    if ( $fa_icon !== false ) {
        $icon = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';
    }
?>
    <div class="header-back-nav">
        <ol class="breadcrumb">
            <li><a href="<?= $rest_path->parent; ?>">Store</a></li>
            <li><a href="<?= $rest_path->parent ?>/details/<?= urlencode($install) ?>"><?= htmlent_utf8($title); ?></a></li>
            <li class="active">Try It</li>
        </ol>
    </div>
    <div class="test-header">
    <div class="title-container">
        <?php
        if ( $fa_icon ) {
            ?>
            <span class="fa <?= $fa_icon; ?> test-header-icon"></span>
            <?php
        }
        ?>
        <span class="test-header-text"><?= htmlent_utf8($title); ?></span>
    </div>
    <div class="install-button-container">
    <?php
    if ( isset($_SESSION['gc_count']) ) {
        echo('<a class="btn btn-success" href="'.$CFG->wwwroot.'/gclass/assign?lti='.urlencode($ltiurl).'&title='.urlencode($tool['name']));
        echo('" title="Install in Classroom" target="iframe-frame"'."\n");
        echo("onclick=\"showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl, true);\" >\n");
        echo('<span class="fa fa-plus"></span> Install</a>'."\n");
    }
    ?>
    </div>
</div>
<?php
}

$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

if ( ! ( $registrations ) ) {
    echo("<p>No tools found.</p>");
    $OUTPUT->footer();
    return;
}

if ( ! isset($registrations[$install])) {
    echo("<p>Tool registration for ".htmlentities($install)." not found</p>\n");
    $OUTPUT->footer();
    return;
}

?>
<ul class="nav nav-tabs">
  <li class="active"><a href="#test" onclick="console.log('yada');" data-toggle="tab" aria-expanded="true">Test</a></li>
  <li><a href="#identity" data-toggle="tab" aria-expanded="false">
                    <?php if ( U::strlen($lmsdata['lis_person_name_full']) > 0 ) echo($lmsdata['lis_person_name_full']);
                        else echo('Anonymous');
                    ?>
        &#9660;
        </a>
      </li>
  <!-- <li><a href="#grades" data-toggle="tab" aria-expanded="false">Grades</a></li> -->
  <li><a href="#debug" data-toggle="tab" aria-expanded="false">Debug</a></li>
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
    if (U::strlen(trim($parms[$k]) ) < 1 ) {
       unset($parms[$k]);
    }
}

// Use the actual direct URL to the launch
$endpoint = $tool['url'];
$endpoint = U::remove_relative_path($endpoint);

// Add oauth_callback to be compliant with the 1.0A spec
$parms["oauth_callback"] = "about:blank";
if ( ! isset($parms['context_id']) ) {
    $parms['context_id'] = md5($endpoint . ':context');
}
if ( ! isset($parms['resource_link_id']) ) {
    $parms['resource_link_id'] = md5($endpoint);
}
if ( ! isset($parms['resource_link_title']) ) {
    $parms['resource_link_title'] = $install;
}
$outcomes = false;
if ( $outcomes ) {
    $parms['lis_outcome_service_url'] = $outcomes;
}

$parms['launch_presentation_return_url'] = $rest_path->current . '/return';
// Add the dark mode preference from the Theme (defaulting to false)
$parms['theme_dark_mode'] = Theme::$dark_mode ? 'true' : 'false';
$tool_consumer_instance_guid = $lmsdata['tool_consumer_instance_guid'];
$tool_consumer_instance_description = $lmsdata['tool_consumer_instance_description'];

$parms = LTI::signParameters($parms, $endpoint, "POST", $key, $secret,
        "Finish Launch", $tool_consumer_instance_guid, $tool_consumer_instance_description);

ksort($parms);

// targets is not present or included "iframe"
$iframeattr = "width=\"100%\" height=\"900\" scrolling=\"auto\" frameborder=\"1\" transparency";

// "targets" =>  array("window"),
if ( isset($tool["targets"]) && is_array($tool["targets"]) && ! in_array("iframe", $tool["targets"]) ) {
    $iframeattr = "_blank";
    echo("<p>Content Opened in New Browser Tab</p>\n");
}
$content = LTI::postLaunchHTML($parms, $endpoint, isset($_POST['debug']), $iframeattr);
print($content);
?>
  </div>
  <div class="tab-pane fade" id="debug">
    <pre class="debug-output">
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

<?php
// In the top frame, we use cookies for session.
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
require_once("../admin/admin_util.php");

use \Tsugi\Util\U;
use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;
use \Tsugi\Config\ConfigInfo;

$PDOX = LTIX::getConnection();

session_start();

// We must be an administrator or in developer mode
if ( ! ( isset($_SESSION["admin"]) || $CFG->DEVELOPER )  ) {
    header('Location: '.$CFG->apphome);
    return;
}

$key = '12345';
if ( is_string($CFG->DEVELOPER) ) $key = $CFG->DEVELOPER;
$row = $PDOX->rowDie(
    "SELECT secret FROM {$CFG->dbprefix}lti_key WHERE key_key = :DKEY",
    array(':DKEY' => $key));
$secret = $row ? $row['secret'] : false;
if ( $secret === false ) {
    $_SESSION['error'] = 'Developer mode not properly configured';
    header('Location: '.$CFG->apphome);
    return;
}

if ( isset($_POST['loginsecret']) ) {
    if ( $_POST['loginsecret'] == $secret ) {
        $_SESSION['developer'] = 'yes';
        header('Location: '.$CFG->wwwroot.'/dev');
        return;
    }
    $_SESSION['error'] = 'Incorrect secret';
    header('Location: '.$CFG->apphome);
    return;
}

if ( ! isset($_SESSION['developer'] ) ) {
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
?>
<p>Please enter the developer password (default is 'secret'):</p>
<form method="post">
<input type="text" name="loginsecret" size="40">
<input type="submit" value="Login">
</form>
</body>
<?php
$OUTPUT->footer();
    return;
}

header('Content-Type: text/html; charset=utf-8');

// Load tools from various folders
$tools = array();
foreach( $CFG->tool_folders AS $tool_folder) {
    if ( $tool_folder == 'core' ) continue;
    if ( $tool_folder == 'admin' ) continue;
    findTools('../'.$tool_folder,$tools);
}

require_once("dev-data.php");

// Merge post data into  data
foreach ($lmsdata as $k => $val ) {
    if ( isset($_POST[$k]) ) {
        $lmsdata[$k] = $_POST[$k];
    }
}

// Switch user data if requested
if ( isset($_POST['learner1']) ) {
    foreach ( $learner1 as $k => $val ) {
          $lmsdata[$k] = $learner1[$k];
    }
}

if ( isset($_POST['learner2']) ) {
    foreach ( $learner2 as $k => $val ) {
          $lmsdata[$k] = $learner2[$k];
    }
}

if ( isset($_POST['learner3']) ) {
    foreach ( $learner3 as $k => $val ) {
          $lmsdata[$k] = $learner3[$k];
    }
}

if ( isset($_POST['instructor']) ) {
    foreach ( $instdata as $k => $val ) {
          $lmsdata[$k] = $instdata[$k];
    }
}

// Set up default LTI data
$key = isset($_REQUEST['key']) ? trim($_REQUEST["key"]) : $key; // UPDATE FROM JOSH HARINGTON TO PERSIST USER DEFINED KEY
$secret = isset($_REQUEST["secret"]) ? trim($_REQUEST["secret"]) : "secret";
$endpoint = isset($_REQUEST["endpoint"]) ? trim($_REQUEST["endpoint"]) : false;
if ( $endpoint == 'false' ) $endpoint = false;
$b64 = base64_encode($key.":::".$secret.':::');

$folder = U::get_rest_parent();
$cssurl = $folder . '/lms.css';
$returnurl = $folder . '/return';

$outcomes = isset($_REQUEST["outcomes"]) ? trim($_REQUEST["outcomes"]) : false;
if ( ! $outcomes ) {
    $outcomes = $folder . "/grade";
    $outcomes .= "?b64=" . htmlentities($b64);
    $lmsdata['lis_result_sourcedid'] = $lmsdata['context_id'].':'.$lmsdata['user_id'].':'.$lmsdata['resource_link_id'];
}

$tool_consumer_instance_guid = $lmsdata['tool_consumer_instance_guid'];
$tool_consumer_instance_description = $lmsdata['tool_consumer_instance_description'];

function doActive($field) {
    if ( isset($_POST[$field]) ) echo(' class="active" ');
}

$OUTPUT->header();
?>
<script language="javascript">
function lmsdataToggle() {
    var ele = document.getElementById("lmsDataForm");
    if(ele.style.display == "block") {
        ele.style.display = "none";
    }
    else {
        ele.style.display = "block";
    }
}

function getComboA(sel) {
    var value = sel.options[sel.selectedIndex].value;
    alert('FIX THIS');
    var ele = document.getElementById("endpoint");
    ele.value = value;
}

function doSubmit(name) {
    if ( name == 'launch' || name == 'debug' ) {
        var x = $("input[name='endpoint']").val();
        if ( x.length < 1 ) {
            alert('Please enter a Launch URL, or select a tool');
            return;
        }
    }
    nei = document.createElement('input');
    nei.setAttribute('type', 'hidden');
    nei.setAttribute('name', name);
    nei.setAttribute('value', '');
    document.getElementById("actionform").appendChild(nei);
    nei = document.createElement('input');
    nei.setAttribute('type', 'hidden');
    nei.setAttribute('name', 'launch');
    nei.setAttribute('value', '');
    document.getElementById("actionform").appendChild(nei);
    document.getElementById("actionform").submit();
}

// From KimKha - http://stackoverflow.com/questions/194846/is-there-any-kind-of-hashcode-function-in-javascript
String.prototype.hashCode = function(){
    var hash = 0;
    if (this.length == 0) return hash;
    for (var i = 0; i < this.length; i++) {
        var character = this.charCodeAt(i);
        hash = ((hash<<5)-hash)+character;
        hash = hash & hash; // Convert to 32bit integer
    }
    return Math.abs(hash);
}

function doSubmitTool(name) {
    nei = document.createElement('input');
    nei.setAttribute('type', 'hidden');
    nei.setAttribute('name', 'launch');
    nei.setAttribute('value', '');
    document.getElementById("actionform").appendChild(nei);
    $("input[name='endpoint']").val(name);
    $("input[name='resource_link_id']").val(name.hashCode());
    $("input[name='resource_link_title']").val('Activity: '+name.hashCode());
    $("input[name='lis_result_sourcedid']").val('sdid:'+name.hashCode());
    document.getElementById("actionform").submit();
}
</script>
<?php
$OUTPUT->bodyStart(false);
?>
  <form method="post" id="actionform">
      <!-- Static navbar -->
      <div class="navbar navbar-default" role="navigation">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="<?= $CFG->wwwroot ?>"><?= $CFG->servicename ?></a>
        </div>
        <div class="navbar-collapse collapse">
          <ul class="nav navbar-nav">
            <li <?php doActive('launch');?>><a href="#" onclick="doSubmit('launch');return false;">Launch</a></li>
            <li <?php doActive('debug');?>><a href="#" onclick="doSubmit('debug');return false;">Debug Launch</a></li>
            <li><a href="#" onclick="javascript:lmsdataToggle();return false;">Toggle Data</a></li>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">Tools<b class="caret"></b></a>
              <ul class="dropdown-menu">
                <?php
                foreach ($tools as $tool ) {
                    $toolname = $tool;
                    if ( strpos($tool,"../") === 0 ) $toolname = substr($tool,3);
                    if ( strpos($tool,"http") !== 0 ) {
                        $tool = $CFG->wwwroot . '/' . $toolname;
                        $tool = U::remove_relative_path($tool);
                    }
                    echo('<li><a href="#" onclick="doSubmitTool(\''.$tool.'\');return false;">'.$toolname.'</a></li>'."\n");
                }
                if ( $CFG->wwwroot == $CFG->apphome ) {
                    echo('<li><a href="#" onclick="doSubmitTool(\'http://localhost:8080/tsugi-servlet/hello\');return false;">Java Servlet (if installed)</a></li>'."\n");
                    echo('<li><a href="#" onclick="doSubmitTool(\'http://localhost:3000/lti\');return false;">Tsugi Node (if installed)</a></li>'."\n");
                    echo('<li><a href="#" onclick="doSubmitTool(\'http://127.0.0.1:8000/tsugi/default/launch\');return false;">Tsugi Web2Py (if installed)</a></li>'."\n");
                }
                ?>
                <li class="divider"></li>
                <li><a href="basecheck.php" target="_blank">Base String Checker</a></li>
                <li><a href="https://github.com/tsugitools" target="_blank">Available Tsugi Tools</a></li>
                <li><a href="https://github.com/tsugicontrib" target="_blank">Contributed Tsugi Tools</a></li>
                <li><a href="http://developers.imsglobal.org/" target="_blank">IMS LTI Documentation</a></li>
                <li><a href="http://www.imsglobal.org/LTI/v1p1p1/ltiIMGv1p1p1.html" target="_new">IMS LTI 1.1 Spec</a></li>
                <li><a href="https://vimeo.com/34168694" target="_new">IMS LTI Lecture</a></li>
              </ul>
            </li>
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li><a href="about-dev"><img style="width:4em; padding: 4px; border-radius: 4px; background-color:white;" src="<?= $CFG->staticroot ?>/img/logos/tsugi-logo.png"></a></li>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <?php if ( strlen($lmsdata['lis_person_name_full']) > 0 ) echo($lmsdata['lis_person_name_full']);
                        else echo('Anonymous');
                    ?>
                    <b class="caret"></b></a>
              <ul class="dropdown-menu">
                <li><a href="#" onclick="doSubmit('instructor');return false;">Jane Instructor</a></li>
                <li><a href="#" onclick="doSubmit('learner1');return false;">Sue Student</a></li>
                <li><a href="#" onclick="doSubmit('learner2');return false;">Ed Student</a></li>
                <li><a href="#" onclick="doSubmit('learner3');return false;">Anonymous</a></li>
              </ul>
            </li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>

      <div>
<?php
$OUTPUT->flashMessages();

if ( isset($_POST['launch']) || isset($_POST['debug']) ) {
        // isset($_POST['instructor']) || isset($_POST['learner1']) || isset($_POST['learner2']) ) {
    echo("<div id=\"lmsDataForm\" style=\"display:none\">\n");
} else {
    echo("<div id=\"lmsDataForm\" style=\"display:block\">\n");
}
echo("<fieldset><legend>LTI Resource</legend>\n");
$disabled = '';
echo("Launch URL: <input size=\"60\" type=\"text\" $disabled size=\"60\" name=\"endpoint\" value=\"$endpoint\">\n");
echo("<br/>Key: <input type\"text\" name=\"key\" $disabled size=\"60\" value=\"$key\">\n");
echo("<br/>Secret: <input type\"text\" name=\"secret\" $disabled size=\"60\" value=\"$secret\">\n");
echo("</fieldset><p>");
echo("<fieldset><legend>Launch Data</legend>\n");
ksort($lmsdata);
foreach ($lmsdata as $k => $val ) {
    echo($k.": <input id=\"".$k."\" type=\"text\" size=\"30\" name=\"".$k."\" value=\"");
    echo(htmlspecialchars($val));
    echo("\">");
    echo("<br/>\n");
}
echo("</fieldset>\n");
echo("</div>\n");
echo("</form>\n");

$parms = $lmsdata;
// Cleanup parms before we sign
foreach( $parms as $k => $val ) {
    if (strlen(trim($parms[$k]) ) < 1 ) {
       unset($parms[$k]);
    }
}

// Add oauth_callback to be compliant with the 1.0A spec
$parms["oauth_callback"] = "about:blank";
if ( $outcomes ) {
    $parms["lis_outcome_service_url"] = $outcomes;
}

$parms['launch_presentation_css_url'] = $cssurl;
$parms['launch_presentation_return_url'] = $returnurl;
$parms['lis_outcome_service_url'] = $outcomes;

if ( isset($_POST['endpoint']) && (isset($_POST['launch']) || isset($_POST['debug']) ) ) {
    // Use the actual direct URL to the launch
    $endpoint = $_POST['endpoint'];
    $endpoint = U::remove_relative_path($endpoint);
    $parms = LTI::signParameters($parms, $endpoint, "POST", $key, $secret,
        "Finish Launch", $tool_consumer_instance_guid, $tool_consumer_instance_description);

    $content = LTI::postLaunchHTML($parms, $endpoint, isset($_POST['debug']),
       "width=\"100%\" height=\"900\" scrolling=\"auto\" frameborder=\"1\" transparency");
    echo("<hr>\n");
    print($content);
}
?>
      </div>
<?php $OUTPUT->footer();

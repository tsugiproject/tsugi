<?php
// In the top frame, we use cookies for session.
define('COOKIE_SESSION', true);
require_once("config.php");
session_start();

// We must be an administrator or in developer mode
if ( ! ( isset($_SESSION["admin"]) || $CFG->DEVELOPER )  ) { 
    header("Location: index.php");
    return;
}

require_once("lib/lti_util.php");

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set("display_errors", 1);
header('Content-Type: text/html; charset=utf-8');

// Load tools from various folders
$tools = array();
findTools("mod",$tools);
findTools("solutions",$tools);
findTools("samples",$tools);
findTools("tmp",$tools);

$cur_url = curPageURL();

require_once("dev-data.php");

// Merge post data into  data
foreach ($lmsdata as $k => $val ) {
	if ( $_POST[$k] && strlen($_POST[$k]) > 0 ) {
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

if ( isset($_POST['instructor']) ) {
	foreach ( $instdata as $k => $val ) {
          $lmsdata[$k] = $instdata[$k];
	}
}

// Set up default LTI data
$key = trim($_REQUEST["key"]);
if ( ! $key ) $key = "12345";
$secret = trim($_REQUEST["secret"]);
if ( ! $secret ) $secret = "secret";
$endpoint = trim($_REQUEST["endpoint"]);
$b64 = base64_encode($key.":::".$secret);
if ( ! $endpoint ) $endpoint = str_replace("dev.php","lti.php",$cur_url);
$cssurl = str_replace("lms.php","lms.css",$cur_url);

$outcomes = trim($_REQUEST["outcomes"]);
if ( ! $outcomes ) {
    $outcomes = str_replace("dev.php","common/tool_consumer_outcome.php",$cur_url);
    $outcomes .= "?b64=" . htmlentities($b64);
}

$tool_consumer_instance_guid = $lmsdata['tool_consumer_instance_guid'];
$tool_consumer_instance_description = $lmsdata['tool_consumer_instance_description'];

function doActive($field) {
    if ( isset($_POST[$field]) ) echo(' class="active" ');
}

headerContent();
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
    var ele = document.getElementById("custom_assn");
	ele.value = value;
}

function doSubmit(name) {
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
    return hash;
}

function doSubmitTool(name) {
	nei = document.createElement('input');
	nei.setAttribute('type', 'hidden');
	nei.setAttribute('name', 'launch');
	nei.setAttribute('value', '');
	document.getElementById("actionform").appendChild(nei);
	$("input[name='custom_assn']").val(name);
	$("input[name='resource_link_id']").val(name.hashCode());
	document.getElementById("actionform").submit();
}
</script>
</head>
<body>
  <form method="post" id="actionform">
    <div class="container">
      <!-- Static navbar -->
      <div class="navbar navbar-default" role="navigation">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="index.php">TSUGI</a>
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
					echo('<li><a href="#" onclick="doSubmitTool(\''.$tool.'\');return false;">'.$tool.'</a></li>'."\n");
				}
				?>
                <li class="divider"></li>
                <li><a href="http://developers.imsglobal.org/" target="_blank">IMS LTI Documentation</a></li>
				<li><a href="http://www.imsglobal.org/LTI/v1p1p1/ltiIMGv1p1p1.html" target="_new">IMS LTI 1.1 Spec</a></li>
				<li><a href="https://vimeo.com/34168694" target="_new">IMS LTI Lecture</a></li>
                <li><a href="http://www.oauth.net/" target="_blank">OAuth Documentation</a></li>
              </ul>
            </li>
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li><a href="about-dev.php">Help</a></li>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo($lmsdata['lis_person_name_full']);?><b class="caret"></b></a>
              <ul class="dropdown-menu">
				<li><a href="#" onclick="doSubmit('instructor');return false;">Jane Instructor</a></li>
				<li><a href="#" onclick="doSubmit('learner1');return false;">Sue Student</a></li>
				<li><a href="#" onclick="doSubmit('learner2');return false;">Ed Student</a></li>
              </ul>
            </li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>

      <div>
<?php

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
    $parms["lis_result_sourcedid"] = "feb-123-456-2929::28883";
}
    
$parms['launch_presentation_css_url'] = $cssurl;

if ( isset($_POST['launch']) || isset($_POST['debug']) ) {
    $parms = signParameters($parms, $endpoint, "POST", $key, $secret, 
        "Finish Launch", $tool_consumer_instance_guid, $tool_consumer_instance_description);

    $content = postLaunchHTML($parms, $endpoint, isset($_POST['debug']), 
       "width=\"100%\" height=\"900\" scrolling=\"auto\" frameborder=\"1\" transparency");
    echo("<hr>\n");
    print($content);
}
?>
      </div>
    </div> <!-- /container -->
<?php footerContent(); 

<?php
define('COOKIE_SESSION', true);
require_once("config.php");
require_once("lib/lti_util.php");

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set("display_errors", 1);
header('Content-Type: text/html; charset=utf-8');
session_start();
function findTools($dir, &$retval) {
	if ( is_dir($dir) ) {
		if ($dh = opendir($dir)) {
			while (($sub = readdir($dh)) !== false) {
				if ( strpos($sub, ".") === 0 ) continue;
				$path = $dir . '/' . $sub;
				if ( ! is_dir($path) ) continue;
				if ( $sh = opendir($path)) {
					while (($file = readdir($sh)) !== false) {
						if ( $file == "index.php" ) {
							$retval[] = $path  ."/" . $file;
							break;
						}
					}
					closedir($sh);
				}
			}
			closedir($dh);
		}
	}
}
$tools = array();
findTools("mod",$tools);
findTools("solutions",$tools);
findTools("samples",$tools);
findTools("tmp",$tools);

$cur_url = curPageURL();

$instdata = array(
      "lis_person_name_full" => 'Jane Instructor',
      "lis_person_name_family" => 'Instructor',
      "lis_person_name_given" => 'Jane',
      "lis_person_contact_email_primary" => "inst@ischool.edu",
      "lis_person_sourcedid" => "ischool.edu:inst",
      "user_id" => "292832126",
      "roles" => "Instructor"
);

$learner1 = array(
      "lis_person_name_full" => 'Sue Student',
      "lis_person_name_family" => 'Student',
      "lis_person_name_given" => 'Sue',
      "lis_person_contact_email_primary" => "student@ischool.edu",
      "lis_person_sourcedid" => "ischool.edu:student",
      "user_id" => "998928898",
      "roles" => "Learner"
);

$learner2 = array(
      "lis_person_name_full" => 'Ed Student',
      "lis_person_name_family" => 'Student',
      "lis_person_name_given" => 'Ed',
      "lis_person_contact_email_primary" => "ed@ischool.edu",
      "lis_person_sourcedid" => "ischool.edu:ed",
      "user_id" => "121212331",
      "roles" => "Learner"
);

$lmsdata = array(
      "custom_assn" => "mod/map/index.php",

      "lis_person_name_full" => 'Jane Instructor',
      "lis_person_name_family" => 'Instructor',
      "lis_person_name_given" => 'Jane',
      "lis_person_contact_email_primary" => "inst@ischool.edu",
      "lis_person_sourcedid" => "ischool.edu:inst",
      "user_id" => "292832126",
      "roles" => "Instructor",

      "resource_link_id" => "120988f929-274612",
      "resource_link_title" => "Weekly Blog",
      "resource_link_description" => "A weekly blog.",
      "context_id" => "456434513",
      "context_label" => "SI106",
      "context_title" => "Introduction to Programming",
      "tool_consumer_info_product_family_code" => "ims",
      "tool_consumer_info_version" => "1.1",
      "tool_consumer_instance_guid" => "lmsng.ischool.edu",
      "tool_consumer_instance_description" => "University of Information",
	  "custom_due" => "2016-12-12 10:00:00.5",
	  // http://www.php.net/manual/en/timezones.php
	  "custom_timezone" => "Pacific/Honolulu",
	  "custom_penalty_time" => "" . 60*60*24,
	  "custom_penalty_cost" => "0.2"
      // 'launch_presentation_return_url' => $cur_url
      );

foreach ($lmsdata as $k => $val ) {
	if ( $_POST[$k] && strlen($_POST[$k]) > 0 ) {
		$lmsdata[$k] = $_POST[$k];
	}
}

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

  $key = trim($_REQUEST["key"]);
  if ( ! $key ) $key = "12345";
  $secret = trim($_REQUEST["secret"]);
  if ( ! $secret ) $secret = "secret";
  $endpoint = trim($_REQUEST["endpoint"]);
  $b64 = base64_encode($key.":::".$secret);
  if ( ! $endpoint ) $endpoint = str_replace("index.php","lti.php",$cur_url);
  $cssurl = str_replace("lms.php","lms.css",$cur_url);

  $outcomes = trim($_REQUEST["outcomes"]);
  if ( ! $outcomes ) {
      $outcomes = str_replace("lms.php","common/tool_consumer_outcome.php",$cur_url);
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
	document.getElementById("actionform").submit();
}

function doSubmitTool(name) {
	nei = document.createElement('input');
	nei.setAttribute('type', 'hidden');
	nei.setAttribute('name', 'launch');
	nei.setAttribute('value', '');
	document.getElementById("actionform").appendChild(nei);
	$("input[name='custom_assn']").val(name);
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
          <a class="navbar-brand" href="#">SI664</a>
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
            <li><a href="about.php">About</a></li>
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
	  if ( $k == "custom_assn" && count($tools) > 0 ) {
		echo('<select id="comboA" onchange="getComboA(this)">'."\n");
		echo('<option value="">Switch tool</option>'."\n");
		foreach ($tools as $tool ) {
			echo('<option value="'.$tool.'">'.$tool.'</option>'."\n");
		}
		echo('</select>'."\n");
      }
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

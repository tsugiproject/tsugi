<?php

function htmlspec_utf8($string) {
    return htmlspecialchars($string,ENT_QUOTES,$encoding = 'UTF-8');
}

function htmlent_utf8($string) {
    return htmlentities($string,ENT_QUOTES,$encoding = 'UTF-8');
}

function line_out($output) {
	echo(htmlent_utf8($output)."<br/>\n");
}

function error_out($output) {
	echo('<span style="color:red"><strong>'.htmlent_utf8($output)."</strong></span><br/>\n");
}

function success_out($output) {
	echo('<span style="color:green"><strong>'.htmlent_utf8($output)."</strong></span><br/>\n");
}

function isCli() {
     if(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
          return true;
     } else {
          return false;
     }
}

function doTop() {
echo('<html>
<head>
  <title>Automatic Web Grading Tool</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script language="javascript"> 
function dataToggle(divName) {
    var ele = document.getElementById(divName);
    if(ele.style.display == "block") {
        ele.style.display = "none";
    }
    else {
        ele.style.display = "block";
    }
} 
  //]]> 
</script>
</head>
<body style="font-family:sans-serif; background-color:#add8e6">
');
}

function togglePre($title, $html) {
    global $div_id;
    $div_id = $div_id + 1;
    echo('<h4>'.htmlent_utf8($title));
    echo(' (<a href="#" onclick="dataToggle('."'".$div_id."'".');return false;">Toggle</a>)</h4>'."\n");
    echo('<pre id="'.$div_id.'" style="display:none; border: solid 1px">'."\n");
    echo(htmlent_utf8($html));
    echo("</pre>\n");
}


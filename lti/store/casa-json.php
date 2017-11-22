<?php
require_once "../../config.php";

// http://imsglobal.github.io/casa/learn/tutorial/publish/
// https://gist.github.com/pfgray/f2d6b4414bdbb84bd75a

use \Tsugi\Util\LTI;
use \Tsugi\UI\Output;

$pieces = parse_url($CFG->wwwroot);
$domain = isset($pieces['host']) ? $pieces['host'] : false;

Output::headerJson();

// Scan the tools folders for registration settings
$tools = array();
$tools = findFiles("register.php","../../");

$output = array();

$toolcount = 0;
foreach($tools as $tool ) {

    if ( strpos($tool, '/store/') !== false ) continue;

    $path = str_replace("../","",$tool);
    // echo("Checking $path ...<br/>\n");
    $id = str_replace("/register.php","",$path);
    $id = str_replace("/","_",$id);
    unset($REGISTER_LTI2);
    require($tool);
    if ( ! isset($REGISTER_LTI2) ) continue;
    if ( ! is_array($REGISTER_LTI2) ) continue;

    if ( isset($REGISTER_LTI2['name']) && isset($REGISTER_LTI2['short_name']) &&
        isset($REGISTER_LTI2['description']) ) {
        // We are happy
    } else {
        lmsDie("Missing required name, short_name, and description in ".$tool);
    }
    // var_dump($REGISTER_LTI2);

    $title = $REGISTER_LTI2['name'];
    $text = $REGISTER_LTI2['description'];
    $fa_icon = isset($REGISTER_LTI2['FontAwesome']) ? $REGISTER_LTI2['FontAwesome'] : false;
    $icon = false;
    if ( $fa_icon !== false ) {
        $icon = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';
    }

    $entry = new stdClass();
    $entry->identity = new stdClass();
    $entry->identity->product_instance_guid = $CFG->product_instance_guid;
    $entry->identity->originator_id = $CFG->casa_originator_id;
    // id an string unique to the app among all apps published by the publisher.
    $entry->identity->id = $id;
    $orig = new stdClass();
    $orig->timestamp = "2015-01-02T22:17:00.371Z";
    $orig->uri = $CFG->wwwroot;
    $orig->share = true;
    $orig->propagate = true;
    $use = new stdClass();
    $use->{"1f2625c2-615f-11e3-bf13-d231feb1dc81"} = $title;
    $use->{"b7856963-4078-4698-8e95-8feceafe78da"} = $text;
    // $use->{"d59e3a1f-c034-4309-a282-60228089194e"} = [{"name":"Paul Gray","email":"pfbgray@gmail.com"}],

    if ( $icon !== false ) $use->{"d25b3012-1832-4843-9ecf-3002d3434155"} = $icon;
    $launch = new stdClass();
    $script = isset($REGISTER_LTI2['script']) ? $REGISTER_LTI2['script'] : "index.php";
    $script = $CFG->wwwroot . '/' . str_replace("register.php", $script, $path);
    $launch->launch_url = $script;
    $launch->registration_url = $CFG->wwwroot . '/lti/register.php';
    $use->{"f6820326-5ea3-4a02-840d-7f91e75eb01b"} = $launch;
    $orig->use = $use;
    $entry->original = $orig;

    $output[] = $entry;
    $toolcount++;
}
echo(LTI::jsonIndent(json_encode($output)));

/*
{
  "identity":{
    "originator_id":"a9a860ae-7c0f-4c12-a1cf-9fe490ee1f49",
    "id":"algebra"
  },
  "original":{
    "timestamp":"2015-01-02T22:17:00.371Z",
    "uri":"http://lebron.technology/",
    "share":true,
    "propagate":true,
    "use":{
      "1f2625c2-615f-11e3-bf13-d231feb1dc81":"College Algrebra",
      "b7856963-4078-4698-8e95-8feceafe78da": "Learn competencies surrounding algebraic operations, equations and inequalities, functions, and number systems.",
      "d59e3a1f-c034-4309-a282-60228089194e":[{"name":"Paul Gray","email":"pfbgray@gmail.com"}],
      "c80df319-d5da-4f59-8ca3-c89b234c5055":["dev","lti"],
      "c6e33506-b170-475b-83e9-4ecd6b6dd42a":["lti"],
      "d25b3012-1832-4843-9ecf-3002d3434155":"http://www.iconsdb.com/icons/preview/green/literature-xxl.png"
      "f6820326-5ea3-4a02-840d-7f91e75eb01b":{
          "launch_url":"http://www.google.com"
      }
    }
  }
}
*/

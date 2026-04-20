<?php
require_once "../config.php";
require_once "peer_util.php";

use \Tsugi\Util\U;
use \Tsugi\Util\LTI;
use \Tsugi\Core\Cache;
use \Tsugi\Core\LTIX;
use \Tsugi\Blob\BlobUtil;

// Sanity checks
$LTI = LTIX::requireData();

// Model
$p = $CFG->dbprefix;

//set json
if ( isset($_POST['save_settings']) ) {
  $partsArray = array();
  for ($i=0; $i < 20; $i++){
    $partList = array();
    if ( isset($_POST['part_title_'.$i]) && isset($_POST['part_type_'.$i])){
      $partList['title'] = $_POST['part_title_'.$i];
      $partList['type'] = $_POST['part_type_'.$i];
      if ($partList['type'] == 'code' && isset($_POST['part_code_'.$i])){
        $partList['code'] = $_POST['part_code_'.$i];
      };
      array_push($partsArray, $partList);
    };
  }

  $jsonArray = array(
    "title" => U::get($_POST, 'title'),
    "description" => U::get($_POST, 'description'),
    "grading" => U::get($_POST, 'grading'),
    "assignment" => U::get($_POST, 'assignment'),
    "parts" => $partsArray,
    "gallery" => U::get($_POST, 'gallery'),
    "totalpoints" => U::get($_POST, 'totalpoints'),
    "instructorpoints" => U::get($_POST, 'instructorpoints'),
    "peerpoints" => U::get($_POST, 'peerpoints'),
    "rating" => U::get($_POST, 'rating'),
    "assesspoints" => U::get($_POST, 'assesspoints'),
    "minassess" => U::get($_POST, 'minassess'),
    "maxassess" => U::get($_POST, 'maxassess'),
    "resubmit" => U::get($_POST, 'resubmit'),
    "autopeer" => U::get($_POST, 'autopeer'),
    "notepublic" => U::get($_POST, 'notepublic'),
    "image_size" => U::get($_POST, 'image_size', '1'),
    "pdf_size" => U::get($_POST, 'pdf_size'),
    "flag" => U::get($_POST, 'flag')
  );
  // var_dump($jsonArray);
    $json = json_encode($jsonArray);

    $json = json_decode(upgradeSubmission($json));
    if ( $json === null ) {
        $_SESSION['error'] = "Bad JSON Syntax";
        header( 'Location: '.addSession('configure.php') ) ;
        return;
    }

    // Some sanity checking...
    if ( $json->totalpoints < 0 ) {
        $_SESSION['error'] = "totalpoints is required and must be >= 0";
        header( 'Location: '.addSession('configure.php') ) ;
        return;
    }

    if ( $json->rating > 0 and $json->peerpoints > 0 ) {
        $_SESSION['error'] = "You can include peerpoints or rating range but not both.";
        header( 'Location: '.addSession('configure.php') ) ;
        return;
    }

    if ( ( $json->instructorpoints + $json->peerpoints +
        ($json->assesspoints*$json->minassess) ) != $json->totalpoints ) {
        $_SESSION['error'] = "instructorpoints + peerpoints + (assesspoints*minassess) != totalpoints ";
        header( 'Location: '.addSession('configure.php') ) ;
        return;
    }

    $json = json_encode($json);
    $stmt = $PDOX->queryReturnError(
        "INSERT INTO {$p}peer_assn
            (link_id, json, created_at, updated_at)
            VALUES ( :ID, :JSON, NOW(), NOW())
            ON DUPLICATE KEY UPDATE json = :JSON, updated_at = NOW()",
        array(
            ':JSON' => $json,
            ':ID' => $LINK->id)
        );
    Cache::clear("peer_assn");
    if ( $stmt->success ) {
        $_SESSION['success'] = 'Assignment updated';
        header( 'Location: '.addSession('index.php') ) ;
    } else {
        $_SESSION['error'] = $stmt->errorImplode;
        header( 'Location: '.addSession('configure.php') ) ;
    }
    return;
}

// Load up the assignment
$row = loadAssignment();
$json = "";
if ( $row !== false ) $json = $row['json'];

// Clean up the JSON for presentation
if ( U::isEmpty($json) ) $json = getDefaultJson();
$json = LTI::jsonIndent($json);
$jsonObj = json_decode(upgradeSubmission($json),true);


// View
$OUTPUT->header();
?>
<style>
.fieldset {
    border: 1px black solid;
    padding: 5px;
    margin: 5px;
}

.stacked-bar-graph {
  width: 100%;
  height: 38px;
  color: #414042;
}
.stacked-bar-graph span {
  display: inline-block;
  height: 100%;
  -webkit-box-sizing: border-box;
          box-sizing: border-box;
  float: left;
  font-weight: bold;
  font-family: arial, sans-serif;
  padding: 10px;
  border: 1px black solid;
}
.stacked-bar-graph .bar-1 {
  background: #F7B334;
  text-overflow: clip;
  text-overflow: clip;
}
.stacked-bar-graph .bar-2 {
  background: #A7A9AC;
  text-overflow: clip;
}
.stacked-bar-graph .bar-3 {
  background: #D57E00;
  overflow: hidden;
}
</style>
<?php

$menu = new \Tsugi\UI\MenuSet();
if ( $USER->instructor ) {
   $menu->addLeft('Cancel', 'index.php');
}

$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);
$OUTPUT->flashMessages();
if ( ! $USER->instructor ) die("Requires instructor role");

function input_radio($jsonObj, $field, $value, $onclick=false) {
    $retval = '<input type="radio" name="'.$field.'" value="' .$value.'"';
    if (U::get($jsonObj,$field) == $value ) {
        $retval .= ' checked';
    }
    if ( $onclick ) {
        $retval .= ' onclick="'.$onclick.'"';
    }
    $retval .= "/>\n";
    return $retval;
}
?>

<form method="post">
    <p>
      <span data-toggle="tooltip" title="
Description of assignment shown to students.  This can be edited
at any time - even after the assignment has started.
">Assignment Title <i class="fa fa-question-circle-o" aria-hidden="true"></i></span><br/>
      <input type="text" name="title"
        value="<?= htmlentities(U::get($jsonObj,'title') ?? '') ?>"
        style="width:90%"/>
    </p>
    <p>
      <span data-toggle="tooltip" title="
Description of assignment shown to students.  This can be edited at any time - even after the assignment has started.
">Assignment Description
<i class="fa fa-question-circle-o" aria-hidden="true"></i></span><br/>
<textarea name="description" style="width:90%">
<?= htmlentities(U::get($jsonObj,'description') ?? '') ?>
</textarea>
    </p>
    <p>
<span data-toggle="tooltip" title="
Assignment specification URL (optional)
">Assignment Specification <i class="fa fa-question-circle-o" aria-hidden="true"></i></span><br/>
      <input type="text" name="assignment"
        value="<?= htmlentities(U::get($jsonObj,'assignment') ?? '') ?>"
        style="width:90%"/>
    </p>
<span data-toggle="tooltip" title="Description of how the assignment will be/should be graded.
This can be edited at any time - even after the assignment has started.
">Grading Expectations
<i class="fa fa-question-circle-o" aria-hidden="true"></i></span><br/>
<textarea name="grading" style="width:90%">
<?= htmlentities(U::get($jsonObj,'grading') ?? ' ') ?>
</textarea>
    </p>
<div class="fieldset">
<p>
Total points are calculated by adding
points from peer-graders,
the instructor-assigned points,
and the points awarded for doing peer grading.
If you are using ratings and gallery mode (below)
you might want to set all of these points to zero.
</p>
    <p>
      <span data-toggle="tooltip" title="This is the maximum points that come from the other students assessments. Each of the peer-graders will assign a value up to this number. If this is zero, students can comment on assignments but not assign a score. Currently the grading policy is to take the highest score from peers since this is really intended for pass/fail assignments and getting feedback from peers rather than carefully crafted assignments with subtle differences in the scores.">
Points from Peers
<i class="fa fa-question-circle-o" aria-hidden="true"></i></span>
      <input type="number" name="peerpoints"
        value="<?= htmlentities(U::get($jsonObj,'peerpoints') ?? '') ?>"
        required/>
<i class="fa fa-asterisk" aria-hidden="true"></i>
    </p>
    <p>
      <span data-toggle="tooltip" title="This is the number of points that come from the instructor. Leave this as zero for a purely peer-graded assignment. Set all other values to zero to create a purely instructor graded drop box.">
Instructor Points
<i class="fa fa-question-circle-o" aria-hidden="true"></i></span>
      <input type="number" name="instructorpoints"
        value="<?= htmlentities(U::get($jsonObj,'instructorpoints') ?? '') ?>"
      required/>
<i class="fa fa-asterisk" aria-hidden="true"></i>
    </p>
    <p>
      <span data-toggle="tooltip" title="This is the number of points students get for each peer assessment that they perform. If this is zero, students can do peer assessing/commenting but will not award points for their efforts.">
Points for doing an Assessment
<i class="fa fa-question-circle-o" aria-hidden="true"></i></span>
      <input type="number" name="assesspoints"
        value="<?= htmlentities(U::get($jsonObj,'assesspoints') ?? '') ?>"
        required/>
<i class="fa fa-asterisk" aria-hidden="true"></i>
    </p>
    <p>
     <span data-toggle="tooltip" title="This is the minimum number of peer submissions each student must assess. If you set Points for Peer Assessment to zero and this field to something greater than zero, students will be able to comment on their peer submissions but not assign any points.">
Minimum Peer Assessments Required
<i class="fa fa-question-circle-o" aria-hidden="true"></i></span>
      <input type="text" name="minassess" id="minassess"
        value="<?= htmlentities(U::get($jsonObj,'minassess') ?? '') ?>"
 required>
<i class="fa fa-asterisk" aria-hidden="true"></i>
    </p>
    <p>
        <span data-toggle="tooltip" title="This is the maximum number of peer assessments the student can do.  It is OK to set this to a high number if you want to allow the students to evaluate a lot of peer submissions.">
Maximum Peer Assessments Allowed
<i class="fa fa-question-circle-o" aria-hidden="true"></i></span>
      <input type="number" name="maxassess" id="maxassess"
        value="<?= htmlentities(U::get($jsonObj,'maxassess') ?? '') ?>"
 required>
    </p>
<p>
Total Points (computed):
 <input type="number" class="no_border" name="totalpoints" readonly/><br/>
<div id="bar-total" class="stacked-bar-graph">
</div>
</p>
</div>
<div class="fieldset">
    <p>
      This is a list of deliverables for the assignment. You must have at least one deliverable.  Each deliverable has a title and type.
      The title text for a deliverable can be edited at any time. You should not change
      the order number, type, or order of the deliverables once the assignment has started.
</p>
<p>
      <button id="add_part" type="button">Add New Deliverable</button>
      <ul id="part_list"></ul>
    </p>
</div>
<div class="fieldset">
    <p>Assignment Settings</p>
    <p>
      <span data-toggle="tooltip" title="If this is true, students will be given the option to flag submissions and flag comments on their submissions. Setting this to off, turns off the flagging workflow.">
Flag
<i class="fa fa-question-circle-o" aria-hidden="true"></i></span><br/>
      <?= input_radio($jsonObj, 'flag', 'false') ?>Off &nbsp;
      <?= input_radio($jsonObj, 'flag', 'true') ?>On &nbsp;
    </p>
    <p>
      <span data-toggle="tooltip" title="This enables students to delete and resubmit their submission as long as the due date has not passed.">
Student Initiated Resubmit
<i class="fa fa-question-circle-o" aria-hidden="true"></i></span><br/>
      <?= input_radio($jsonObj, 'resubmit', 'off') ?>Off &nbsp;
      <?= input_radio($jsonObj, 'resubmit', 'always') ?>Always &nbsp;
    </p>
    <p>
      <span data-toggle="tooltip" title="
A number of minutes after which if a submission has not received any peer 'grades', we automatically assign a peer grade.
Leave this value zero to disable this feature.
The value is in seconds so a week is 7*24*60*60 or 604800.
The student needs to view their submission or a batch process has to run to trigger this processing.
">
Automatic Peer Grading Interval
<i class="fa fa-question-circle-o" aria-hidden="true"></i></span>
      <input type="number" name="autopeer"
        value="<?= htmlentities(U::get($jsonObj,'autopeer') ?? '') ?>"
        required/>
    </p>
<p>
The maximum file size that can be uploaded to this course is
<?= BlobUtil::maxUpload() ?> MB per *form*.  You can set
lower limits per artifact if you like.
</p>
<p>
Maximum image size (MB)
      <input type="number" name="image_size"
        value="<?= htmlentities(U::get($jsonObj,'image_size') ?? '') ?>" />
    </p>
<p>
Maximum PDF size (MB)
      <input type="number" name="pdf_size"
        value="<?= htmlentities(U::get($jsonObj,'pdf_size') ?? '') ?>" />
    </p>
</div>
<div class="fieldset">
<p>
Gallery is a variant of the user interface where students can see lots of submissions.  Often
gallery is used with no grades at all, and instead focuses on ratings.
</p>
    <p>
      <span data-toggle="tooltip" title="If enabled, this allows students to browse a gallery of other student submissions. You can indicate if a student can access the gallery before their submission or after their submission.">
Gallery
<i class="fa fa-question-circle-o" aria-hidden="true"></i></span><br/>
      <?= input_radio($jsonObj, 'gallery', 'off', 'return gallery_off();') ?>Off &nbsp;
      <?= input_radio($jsonObj, 'gallery', 'always', 'return gallery_on();') ?>Always &nbsp;
      <?= input_radio($jsonObj, 'gallery', 'after', 'return gallery_on();') ?>After &nbsp;
    </p>
    <p class="gallery">
      <span data-toggle="tooltip" title="
If the gallery view is selected, you can choose the format of the gallery.
">
Gallery Format
<i class="fa fa-question-circle-o" aria-hidden="true"></i></span><br/>
      <?= input_radio($jsonObj, 'galleryformat', 'card') ?>Card &nbsp;
      <?= input_radio($jsonObj, 'galleryformat', 'table') ?>Table &nbsp;
    </p>
    <p class="gallery">
      <span data-toggle="tooltip" title="This indicates that the peers are rating the submission rather than grading the submission. The number is the rating scale (1-10). If you use ratings, you should not use peer grading at all..">
Rating
<i class="fa fa-question-circle-o" aria-hidden="true"></i></span><br/>
      <?= input_radio($jsonObj, 'rating', '0') ?>Off &nbsp;
      <?= input_radio($jsonObj, 'rating', '1') ?>On &nbsp;
    </p>
    <p class="gallery">
<span data-toggle="tooltip" title="Show student-entered note field to other students viewing the gallery.">
Show Note field in Gallery
<i class="fa fa-question-circle-o" aria-hidden="true"></i></span><br/>
      <?= input_radio($jsonObj, 'notepublic', 'false') ?>Hide &nbsp;
      <?= input_radio($jsonObj, 'notepublic', 'true') ?>Show &nbsp;
    </p>

</div>
<p>
<input id="save_settings" name="save_settings" type="submit" value="Save">
<input type=submit name=doCancel onclick="location='<?php echo(addSession('index.php'));?>'; return false;" value="Cancel"></p>
</form>

<pre id="debug_pre" style="display:none">
Previous JSON:
<?= $json ?>
</pre>
<a href="#" onclick="$('#debug_pre').toggle(); return false;">Toggle Debug Data</a>

<?php

$OUTPUT->footerStart();
?>
<script type="text/javascript">

var counter = 0;

function gallery_on() {
    $('.gallery').each(function() { $(this).show(); } );
}
function gallery_off() {
    $('.gallery').each(function() { $(this).hide(); } );
}

function compute_total() {
    if ($("input[name='instructorpoints']").val().length > 0 && $("input[name='peerpoints']").val().length > 0 &&
        $("input[name='assesspoints']").val().length > 0 && $("input[name='minassess']").val().length > 0){

        var inst_points = parseFloat($("input[name='instructorpoints']").val());
        var peer_points = parseFloat($("input[name='peerpoints']").val());
        var assess_points = parseFloat($("input[name='assesspoints']").val());
        var min_assess = parseFloat($("input[name='minassess']").val());
        var bar_total =  inst_points + peer_points + assess_points * min_assess;
        $("input[name='totalpoints']").val(bar_total);
        $("#bar-total").empty();
        bar_total = bar_total*1.1;
        var inst_pct = Math.trunc(100*inst_points/bar_total);
        var peer_pct = Math.trunc(100*peer_points/bar_total);
        var assess_pct = Math.trunc(100*assess_points/bar_total);
        if ( inst_pct > 0 ) $("#bar-total").append('<span style="width:'+inst_pct+'%" class="bar-1">Instructor</span>');
        if ( peer_pct > 0 ) $("#bar-total").append('<span style="width:'+peer_pct+'%" class="bar-2">Peers</span>');
        if ( min_assess > 0 ) {
            for(var i=0; i<min_assess; i++){
                $("#bar-total").append('<span style="width:'+assess_pct+'%" class="bar-3">Assess</span>');
            }
        }
    }
}

function add_part(part) {
    counter += 1;

    part = part || {} ;
    // Grab some HTML with hot spots and insert into the DOM
    var source  = $("#part-template").html();
    $('#part_list').append(source.replace(/@PART@/g,counter));
    if ( part.title ) $('#part_title_'+counter).val(part.title);
    if ( part.type ) {
        $('#part_type_'+counter).val(part.type);
        if ( part.type == 'code' && part.language ) {
            $('#part_code_'+counter).val(part.language);
            $('#part_code_'+counter).show();
        }
    }
}

$( document ).ready(function() {
    compute_total();
<?php
    if ( U::get($jsonObj,'parts') ) {
        foreach ($jsonObj['parts'] as $part ) {
            echo("    add_part(".json_encode($part).");\n");
        }
    }
    if ( U::get($jsonObj,'gallery') == 'off' ) {
        echo("    gallery_off();\n");
    }
?>
    $("input[name='instructorpoints'], input[name='peerpoints'], input[name='assesspoints'], input[name='minassess']").change(compute_total);

    $("#add_part").on('click', function(){
        add_part();
    });

});

$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip();
});

function update_part_form(partno) {
    var x = $('#part_type_'+partno).val();
    if ( x == 'code' ) {
        $('#part_code_'+partno).show();
    } else {
        $('#part_code_'+partno).hide();
    }
}

</script>
<!-- HTML with Substitution hot spots -->
<script id="part-template" type="text">
<li id="part_@PART@">
<p>
<input type="text" style="width:70%" name="part_title_@PART@" id="part_title_@PART@"
placeholder=" .. Title (Required) .." required>
<span data-toggle="tooltip" title="Please enter the title of the deliverable.">
<i class="fa fa-asterisk"></i>
</span>
 <br>
<select name="part_type_@PART@" id="part_type_@PART@" class="part_type" onchange="update_part_form(@PART@);">
<option value="">-- Select Type of Submission --</option>
<option value="image">Image</option>
<option value="pdf">PDF</option>
<option value="url">URL</option>
<option value="html">Formatted text</option>
<option value="code">Code/text</option>
</select>
&nbsp;
<select name="part_code_@PART@" id="part_code_@PART@" style="display:none;">
<option value="">-- Select format of submission ---</option>
<option value="markup">Markup</option>
<option value="css">CSS</option>
<option value="javascript">JavaScript</option>
<option value="php">PHP</option>
<option value="java">Java</option>
<option value="c">C</option>
<option value="python">Python</option>
<option value="sql">SQL</option>
<option value="ruby">Ruby</option>
</select>
&nbsp
<input type="button" value="Delete Deliverable" onclick="
if( confirm('Are you sure you want to delete this deliverable?') ) $('#part_@PART@').remove();return false;">
</li>
</script>

<?php
$OUTPUT->footerEnd();

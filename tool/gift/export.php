<?php

require_once "../config.php";
require_once "parse.php";

use \Tsugi\Util\U;
use \Tsugi\Util\LTI;
use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsForm;

require_once "util.php";

$LAUNCH = LTIX::requireData();
if ( ! $USER->instructor ) die('Instructor only');

// Load the quiz
$text = $LAUNCH->link->getJson();

$menu = new \Tsugi\UI\MenuSet();
$menu->addLeft('Back', 'index.php');

$fname = preg_replace( '/[\W]/', '_', $LAUNCH->link->title);
$fname = str_replace( '___', '_', $fname);
$fname = str_replace( '__', '_', $fname);

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);
?>
<p>
This is an experimental feature to convert your quiz to 
<a href="http://www.imsglobal.org/question/" target="_blank">QTI 1.2.1</a>.
</p>
</p>
<form method="post" action="<?= addSession('process.php') ?>" target="working" style="margin:20px;">
<p style="float:right">
<p>Quiz Title: <input type="text" name="title" size="60" value="<?= $LAUNCH->link->title ?>"/></p>
<p>Quiz File Name (no suffix): <input type="text" name="name" size="30" value="<?= $fname ?>"/> (optional)</p>
<textarea rows="30" style="width: 98%; display:none;" name="text">
<?= htmlentities($text); ?>
</textarea>
<p><input type="checkbox" name="bypass" value="bypass">
Do not validate the XML</p>
<p><input type="checkbox" name="htmlhack" value="htmlhack">
LMS Does not handle text/plain well (Canvas / Coursera)</p>
<input type="submit" name="submit" class="btn btn-primary" value="Convert GIFT to QTI"
onclick="$('#myModal').modal('show');"></p>
</form>
</p>
<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width:80%">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" 
            onclick="$('#working').attr('src', 'waiting.php');" ><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Converting to QTI...</h4>
      </div>
      <div class="modal-body">
        <iframe id="working" name="working" src="waiting.php" style="width:100%; height: 400px"></iframe>
      </div>
    </div>
  </div>
</div>

<?php

$OUTPUT->footer();

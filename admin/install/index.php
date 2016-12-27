<?php

use \Tsugi\Core\LTIX;

define('COOKIE_SESSION', true);
require_once("../../config.php");
session_start();
require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

$LAUNCH = LTIX::session_start();

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

if ( ! isset($CFG->install_folder) ) {
    echo('<h1>Install folder ($CFG->install_folder) is not configured</h1>'."\n");
    $OUTPUT->footer();
    return;
}

?>
<ul class="nav nav-tabs">
  <li class="active"><a href="#home" data-toggle="tab" aria-expanded="true">Installed Modules</a></li>
  <li class=""><a href="#available" data-toggle="tab" aria-expanded="false">Available Modules</a></li>
  <li class=""><a href="#advanced" data-toggle="tab" aria-expanded="false">Advanced</a></li>
</ul>
<div id="myTabContent" class="tab-content">
  <div class="tab-pane fade active in" id="home">
    <ul id="installed_ul">
    <img src="<?= $OUTPUT->getSpinnerUrl() ?>" id="spinner">
    </ul>
  </div>
  <div class="tab-pane fade" id="available">
    <ul id="available_ul">
    <img src="<?= $OUTPUT->getSpinnerUrl() ?>" id="spinner">
    </ul>
  </div>
  <div class="tab-pane fade" id="advanced">
    <p>TBD</p>
  </div>
</div>

<?php



$OUTPUT->footerStart();
$OUTPUT->templateInclude(array('installed', 'available'));
// $OUTPUT->templateInclude('installed');
// $OUTPUT->templateInclude('available');
?>
<script>
$(document).ready(function(){
    $.getJSON('<?= addSession('repos_json.php') ?>', function(repos) {
        window.console && console.log(repos);
        tsugiHandlebarsToDiv('installed_ul', 'installed', repos);
        tsugiHandlebarsToDiv('available_ul', 'available', repos);
    }).fail( function() { alert('getJSON fail'); } );
});

</script>
<?php
$OUTPUT->footerEnd();

<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\Git;

define('COOKIE_SESSION', true);
require_once("../../config.php");
session_start();
require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

$LAUNCH = LTIX::session_start();

// In case we need a setuid copy of git
if ( isset($CFG->git_command) ) {
    Git::set_bin($CFG->git_command);
}

// Cleanup partial git attempts
unset($_SESSION['git_results']);

$repo = new \Tsugi\Util\GitRepo($CFG->dirroot);
$git_version = $repo->run('--version');

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
<div id="readonly-dialog" title="Read Only Dialog" style="display: none;">
<p>This server does not appear to allow the <b>git</b> command to 
make changes to the web files.  So this tool will not be able to install,
update, or reconfigure any of these tools.  You will have to update these files
some other way.  Alternatively, you may be able to configure a copy of <b>git</b>
that can update the file system - see the documentation for <b>$CFG->git_command</b> in 
the <b>config-dist.php</b> file.
</p>
</div>
<div id="iframe-dialog" title="Read Only Dialog" style="display: none;">
   <iframe name="iframe-frame" style="height:400px" id="iframe-frame" 
    src="<?= $OUTPUT->getSpinnerUrl() ?>"></iframe>
</div>
<p>This screen is a wrapper for the <b>git</b> command if it is installed in your system.
This screen runs <b>git</b> commands on your behalf.
It will only handle the normal operations and assuming that they work.  If you log in
and edit the files after they are checked out, this tool might not be able to upgrade
some of these git repos.
</p>
<p>Using: <?= htmlentities($git_version) ?></p>
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

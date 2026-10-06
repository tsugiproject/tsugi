<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\Git;


$LAUNCH = LTIX::session_start();

// In case we need a setuid copy of git
if ( isset($CFG->git_command) && is_string($CFG->git_command) ) {
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

define('SANITY_DB_ALLOW_NO_TABLES', true);
require_once $CFG->dirroot . '/sanity-db.php';

if ( ! isset($CFG->install_folder) ) {
    echo('<h1>Install folder ($CFG->install_folder) is not configured</h1>'."\n");
    $OUTPUT->footer();
    return;
}

$installReport = \Tsugi\Services\Admin\AdminService::installModuleReport();
$installed = $installReport['installed'];
$available = $installReport['available'];
$required = $installReport['required'];
$installGit = $CFG->wwwroot.'/admin/modules/git';

// Check to see if we are in a cluster
$other_nodes = count(\Tsugi\Services\Admin\AdminService::getClusterIPs());
$clusterServers = array();
if ( $other_nodes > 0 ) {
    $clusterServers = \Tsugi\Services\Admin\AdminService::installClusterServers();
}

?>
<a href="<?= $CFG->wwwroot ?>/admin" style="float: right;" class="btn btn-default">Admin</a>

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
   <iframe name="iframe-frame" style="height:400px" id="iframe-frame" title="Module installation and git operations"
    src="<?= $OUTPUT->getSpinnerUrl() ?>"></iframe>
</div>
<p>This screen is a wrapper for the <b>git</b> command if it is installed in your system.
This screen runs <b>git</b> commands on your behalf.
It will only handle the normal operations and assume that they work.  If you log in
and edit the files after they are checked out, this tool might not be able to upgrade
some of these git repos.
</p>
<?php if ( $other_nodes > 0 ) { ?>
<p><b>Note:</b> This is a clustered environment with <?= $other_nodes+1 ?> nodes,
it may take some time before installations / updates propagate to all
the nodes in the cluster.  It can take up to an hour to clear out cluster nodes
that have left the cluster.  Please be  patient.</p>
<?php } ?>
<p>Using: <?= htmlentities($git_version) ?></p>
<?php if ( ! empty($installReport['error']) ) { ?>
<p class="text-danger">Module list error:</p>
<pre class="text-danger"><?= htmlentities((string) $installReport['error']) ?></pre>
<?php } ?>
<ul class="nav nav-tabs">
  <li class="active"><a href="#home" data-toggle="tab" aria-expanded="true">Installed Modules</a></li>
<?php if ( $other_nodes > 0 ) { ?>
  <li class=""><a href="#cluster-div" data-toggle="tab" aria-expanded="false">Cluster Status</a></li>
<?php } ?>
<?php if ( isset($CFG->lessons) ) { ?>
  <li class=""><a href="#required-div" data-toggle="tab" aria-expanded="false">Required Modules</a></li>
<?php } ?>
  <li class=""><a href="#available-div" data-toggle="tab" aria-expanded="false">Available Modules</a></li>
  <li class=""><a href="#advanced-div" data-toggle="tab" aria-expanded="false">Advanced</a></li>
</ul>
<div id="myTabContent" class="tab-content" style="margin-top:10px;">
  <div class="tab-pane fade active in" id="home">
    <?php include __DIR__.'/installed.php'; ?>
  </div>
<?php if ( $other_nodes > 0 ) { ?>
  <div class="tab-pane fade" id="cluster-div">
    <?php include __DIR__.'/cluster.php'; ?>
  </div>
<?php } ?>
<?php if ( isset($CFG->lessons) ) { ?>
  <div class="tab-pane fade" id="required-div">
    <?php include __DIR__.'/required.php'; ?>
  </div>
<?php } ?>
  <div class="tab-pane fade" id="available-div">
    <?php include __DIR__.'/available.php'; ?>
  </div>
  <div class="tab-pane fade" id="advanced-div">
    <p>This screen allows you to clone a repository into your <b>install_folder</b>.
    Make sure to know the code you are installing and review it carefully before
    installing it. The repository will be checked out into a folder of the
    same name as the respsitory.</p>
    <p>
    <form method="GET" action="<?= htmlentities(addSession($installGit)) ?>" target="iframe-frame">
    <input type="hidden" name="command" value="clone">
    Repository: <input size="60" type="text" name="remote"><br/>
    <!-- Sub-Folder: <input type="text" name="folder"> (optional)<br/> -->
    <input type="submit" value="Clone Repository"
        onclick="showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl, true);" >
    </form>
    </p>
  </div>
</div>
<?php
$OUTPUT->footer();

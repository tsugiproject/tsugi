<?php
// In the top frame, we use cookies for session.
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\UI\Table;
use \Tsugi\Core\Mail;
use \Tsugi\Core\LTIX;

\Tsugi\Core\LTIX::getConnection();

if ( $CFG->providekeys === false || $CFG->owneremail === false ) {
    $_SESSION['error'] = _m("This service does not accept instructor requests for keys");
    header('Location: '.$CFG->wwwroot);
    return;
}

// Note - this does not require login.
header('Content-Type: text/html; charset=utf-8');
session_start();

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>Using Your Key</h1>
<p>
  <a href="index" class="btn btn-default" aria-label="LTI Keys">LTI Keys</a>
  <a href="<?= htmlspecialchars(LTIX::curPageUrlFolder(), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-default active" aria-label="Using Your Key (current page)">Using Your Key</a>
  <a href="requests" class="btn btn-default" aria-label="Key Requests">Key Requests</a>
  <a href="<?= htmlspecialchars($CFG->wwwroot . '/settings/', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-default" aria-label="My Settings">My Settings</a>
</p>
<p>
<ul class="nav nav-tabs" role="tablist">
  <li class="active" role="presentation"><a href="#lti" id="lti-tab" data-toggle="tab" role="tab" aria-controls="lti" aria-selected="true">General</a></li>
  <li role="presentation"><a href="#sakai" id="sakai-tab" data-toggle="tab" role="tab" aria-controls="sakai" aria-selected="false">Sakai</a></li>
  <li role="presentation"><a href="#canvas" id="canvas-tab" data-toggle="tab" role="tab" aria-controls="canvas" aria-selected="false">Canvas</a></li>
  <li role="presentation"><a href="#moodle" id="moodle-tab" data-toggle="tab" role="tab" aria-controls="moodle" aria-selected="false">Moodle</a></li>
<?php if ( isset($CFG->lessons) ) { ?>
  <li role="presentation"><a href="#bb" id="bb-tab" data-toggle="tab" role="tab" aria-controls="bb" aria-selected="false">Blackboard</a></li>
<?php } ?>
  <li role="presentation"><a href="#auto" id="auto-tab" data-toggle="tab" role="tab" aria-controls="auto" aria-selected="false">LTI 1.3 Dynamic Registration</a></li>
</ul>
<div id="myTabContent" class="tab-content" style="margin-top:10px;">
  <div class="tab-pane fade active in" id="lti" role="tabpanel" aria-labelledby="lti-tab">
<ul>
<?php
$tools=findAllRegistrations();
foreach($tools as $tool) {
    $short_name = htmlspecialchars($tool['short_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $url = htmlspecialchars($tool['url'] ?? '', ENT_QUOTES, 'UTF-8');
    echo("<li>\n");
    echo("You can install the " . $short_name . " tool at the following URL:\n");
    echo("<pre aria-label=\"Installation URL for " . $short_name . "\">\n");
    echo($url);
    echo("</pre>\n");
    echo("</li>\n");
}
?>
</div>
<div class="tab-pane fade" id="sakai" role="tabpanel" aria-labelledby="sakai-tab">
In Sakai, you can install
this site as an "App Store" / "Learning Object Repository" using this url:
<pre aria-label="Sakai LTI store URL"><?= htmlspecialchars($CFG->wwwroot, ENT_QUOTES, 'UTF-8') ?>/lti/store/</pre>
In Sakai, use the Lessons tool, select "External Tools" and install this as
an LTI 1.x tool.  Make sure to check the
"Supports Deep Linking / Content Item" option when installing this URL in Sakai and tick
the boxes to allow both the title and url to be changed.
</p>
<p>
Then this "<?= htmlspecialchars($CFG->servicename ?? '', ENT_QUOTES, 'UTF-8') ?> store" will appear in Lessons as a new external tool, when you
select the store you will be launched into the picker to choose tools and/or
resources to be pulled into Lessons.   The key and secret will be inherited
from the store to each of the installed tools.  Once the app store
is installed, the rerources from this site will be avilable from within the
rich text editor.
<?php if ( isset($CFG->lessons) ) { ?>
<p>
You can import all the content (including autograders) from this site into your Sakai
course by downloading it as an
<a href="<?= htmlspecialchars($CFG->wwwroot, ENT_QUOTES, 'UTF-8') ?>/cc/export" aria-label="Download Common Cartridge export">Common Cartridge</a> and then using
the Import feature in Lessons.   In order to activate all the LTI links automatically, install an External tool with the URL:
<pre aria-label="External tool installation URL"><?= htmlspecialchars(isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot, ENT_QUOTES, 'UTF-8') ?></pre>
with your key and secret.  Then as LTI items are imported they will automatically
be associated with your key and secret.
</p>
<?php } ?>
<p>
Sakai supports LTI Advantage Dynamic Registration but you need help from this system's admin to
set the unlock code for this key and get a dynamic configuration URL.
</p>
</div>
<div class="tab-pane fade" id="canvas" role="tabpanel" aria-labelledby="canvas-tab">
You can install this into Canvas as an "App Store" / "Learning Object Repository"
using XML configuration with your key and secret
and the following URL:
<pre aria-label="Canvas LTI configuration URL"><?= htmlspecialchars($CFG->wwwroot, ENT_QUOTES, 'UTF-8') ?>/lti/store/canvas-config.xml</pre>
Your tool should see the little search icon (<i style="color: blue;" class="fa fa-search" aria-hidden="true"></i>) once
it is installed in Canvas to indicate that it is a searchable source of tools and content.
This content will be available in the Modules, Pages, Assignments, and Import
within Canvas under "external tools".
</div>
<div class="tab-pane fade" id="moodle" role="tabpanel" aria-labelledby="moodle-tab">
<?php if ( isset($CFG->lessons) ) { ?>
<p>
You can import all the content (including autograders) from this site into your Moodle
course by downloading it as an
<a href="<?= htmlspecialchars($CFG->wwwroot, ENT_QUOTES, 'UTF-8') ?>/cc/export" aria-label="Download Common Cartridge export">Common Cartridge</a> and then using
the Restore feature to upload the content into your Moodle course.   Once you
install your LTI key and secret in the course, the LTI links to the autograder
should start to function.
</p>
<?php } ?>
<p>
You can install
this site as an "App Store" / "Learning Object Repository" using this url:
<pre aria-label="Moodle LTI store URL"><?= htmlspecialchars($CFG->wwwroot, ENT_QUOTES, 'UTF-8') ?>/lti/store/</pre>
Make sure to find and check the "Supports Content Item" or "Deep Linking" option when installing
this URL.
</p>
<p>
Moodle supports <strong>LTI Advantage Dynamic Registration</strong>.
</p>
</div>
<?php if ( isset($CFG->lessons) ) { ?>
<div class="tab-pane fade" id="bb" role="tabpanel" aria-labelledby="bb-tab">
<p>
You can import all the content from (including autograders) this site into your Blackboard
course by downloading it as an
<a href="<?= htmlspecialchars($CFG->wwwroot, ENT_QUOTES, 'UTF-8') ?>/cc/export" aria-label="Download Common Cartridge export">Common Cartridge</a> and then using
the Import feature to upload the content into your course.   Once you
install your LTI key and secret in the course, the LTI links to the autograder
should start to function.
</p>
</div>
<?php } ?>
<div class="tab-pane fade" id="auto" role="tabpanel" aria-labelledby="auto-tab">
<p>
Tsugi supports the
<strong>LTI Advantage Dynamic</strong> but it is not yet self service.  You will need
to contact the administator of this system to get a LTI advantage key set up and prepared
for Dynamic registration.  The administrator will send you the Dynamic Regustration URL with
a one-time access code.
</p>
</div>
</div>
</div>

<?php
$OUTPUT->footer();


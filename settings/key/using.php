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
  <a href="index" class="btn btn-default">LTI Keys</a>
  <a href="<?= LTIX::curPageUrlFolder() ?>" class="btn btn-default active">Using Your Key</a>
  <a href="requests" class="btn btn-default">Key Requests</a>
  <a href="<?= $CFG->wwwroot.'/settings/' ?>" class="btn btn-default">My Settings</a>
</p>
<p>
<ul class="nav nav-tabs">
  <li class="active"><a href="#lti" data-toggle="tab" aria-expanded="true">General</a></li>
  <li><a href="#sakai" data-toggle="tab" aria-expanded="false">Sakai</a></li>
  <li><a href="#canvas" data-toggle="tab" aria-expanded="false">Canvas</a></li>
  <li><a href="#moodle" data-toggle="tab" aria-expanded="false">Moodle</a></li>
<?php if ( isset($CFG->lessons) ) { ?>
  <li><a href="#bb" data-toggle="tab" aria-expanded="false">Blackboard</a></li>
<?php } ?>
  <li><a href="#auto" data-toggle="tab" aria-expanded="false">LTI Auto Configuration</a></li>
</ul>
<div id="myTabContent" class="tab-content" style="margin-top:10px;">
  <div class="tab-pane fade active in" id="lti">
<ul>
<?php
$tools=findAllRegistrations();
foreach($tools as $tool) {
    echo("<li>\n");
    echo("You can install the ".$tool['short_name']." tool at the folowing URL:\n");
    echo("<pre>\n");
    echo($tool['url']);
    echo("</pre>\n");
    echo("</li>\n");
}
?>
</div>
<div class="tab-pane fade" id="sakai">
Sakai 10 and later supports the IMS Content Item standard so you can install
this site as an "App Store" / "Learning Object Repository" using this url:
<pre>
<?= $CFG->wwwroot ?>/lti/store/
</pre>
In Sakai, use the Lessons tool, select "External Tools" and install this as 
an LTI 1.x tool.  Make sure to check the 
"Supports Content Item" option when installing this URL in Sakai and tick 
the boxes to allow both the title and url to be changed.
</p>
<p>
Then this "<?= $CFG->servicename ?> store" will appear in Lessons as a new external tool, when you 
select the store you will be launched into the picker to choose tools and/or
resources to be pulled into Lessons.   The key and secret will be inherited
from the store to each of the installed tools.  In Sakai-12, once the app store
is installed, the rerources fomr this site will be avilable from within the 
rich text editor.
<?php if ( isset($CFG->lessons) ) { ?>
<p>
You can import all the content (including autograders) from this site into your Sakai
course by downloading it as an 
<a href="<?= $CFG->wwwroot ?>/cc/export">Common Cartridge</a> and then using
the Import feature in Lessons.   In order to activate all the LTI links automatically, install an External tool with the URL:
<pre>
<?= isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot ?>
</pre>
with your key and secret.  Then as LTI items are imported they will automatically
be associated with your key and secret.
</p>
<?php } ?>
<p>
There is an effort to include <b>"LTI Advantage Auto Configuration"</b> as part of Sakai 21.1.
Once that is available, instructions on how to auto-provision a <?= $CFG->servicename ?> key into 
Sakai will be included here.  Reference:
<a href="https://jira.sakaiproject.org/browse/SAK-44055" target="_blank">https://jira.sakaiproject.org/browse/SAK-44055</a>.
</p>
</div>
<div class="tab-pane fade" id="canvas">
You can install this into Canvas as an "App Store" / "Learning Object Repository"
using XML configuration with your key and secret
and the following URL:
<pre>
<?= $CFG->wwwroot ?>/lti/store/canvas-config.xml
</pre>
Your tool should see the little search icon (<i style="color: blue;" class="fa fa-search"></i>) once
it is installed in Canvas to indicate that it is a searchable source of tools and content.
This content will be available in the Modules, Pages, Assignments, and Import
within Canvas under "external tools".
</div>
<div class="tab-pane fade" id="moodle">
<?php if ( isset($CFG->lessons) ) { ?>
<p>
You can import all the content (including autograders) from this site into your Moodle
course by downloading it as an 
<a href="<?= $CFG->wwwroot ?>/cc/export">Common Cartridge</a> and then using
the Restore feature to upload the content into your Moodle course.   Once you
install your LTI key and secret in the course, the LTI links to the autograder
should start to function.
</p>
<?php } ?>
<p>
Moodle 3.4 and later also supports the IMS Content Item standard so you can install
this site as an "App Store" / "Learning Object Repository" using this url:
<pre>
<?= $CFG->wwwroot ?>/lti/store/
</pre>
Make sure to find and check the "Supports Content Item" option when installing 
this URL.
</p>
<p>
Moodle supports an early draft of <b>"LTI Advantage Auto Configuration"</b> in Moodle 3.10.
Tsugi also supports an early draft of the specification - but the combination needs to be
tested and interoperability needs to be aligned - this is part of standards development.
You can find the auto configuration URL in the detail page of your LTI key.
</p>
</div>
<?php if ( isset($CFG->lessons) ) { ?>
<div class="tab-pane fade" id="bb">
<p>
You can import all the content from (including autograders) this site into your Blackboard
course by downloading it as an 
<a href="<?= $CFG->wwwroot ?>/cc/export">Common Cartridge</a> and then using
the Import feature to upload the content into your course.   Once you
install your LTI key and secret in the course, the LTI links to the autograder
should start to function.
</p>
</div>
<?php } ?>
<div class="tab-pane fade" id="auto">
<p>
There is an under-development standard to allow to automatically provision
an LTI Advantage tool in an LMS using web services.   This specification
will likely be made public sometime on 2021.  The code in Tsugi is test/pre-release
code to help in the early testing of the specification as it is being developed.
</p>
<p>
You can look a the detail page for your key and at the bottom there is a
<b>"LTI Advantage Auto Configuration URL"</b> that can be copied into an LMS
that supports this new protocol.
</p>
<p>
Once the standard is finalized and each LMS implementation has been tested with Tsugi,
instructions will be added to each ot the LMS sections of this document.
</p>
</div>
</div>
</div>

<?php
$OUTPUT->footer();


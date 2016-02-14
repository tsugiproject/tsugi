<?php
require_once $CFG->dirroot."/admin/admin_util.php";
$OUTPUT->header();
?>
</head>
<body style="padding:10px;">
<div class="alert alert-danger" style="margin: 10px;">
<p>This request is missing custom parameters to configure which tool you would
like to select to run on this system.   You Learning Management system can
set these custom parameters as key/value pairs.
</p>
</div>
<p>
While each tool hosted in this system may have its own custom parameters
here are some of the parameters that you might need to use.
<ul>
<li> <b>assn=mod/pythonauto/auto.php</b> - the <b>assn</b> parameter selects
which tool to run in the system.</li>
<li> <b>exercise=3.3</b> - For the pythonauto tool, use this custom parameter
to select the exercise.</li>
<li> <b>done=_close</b> - Some tools have a "Done" button.  If this is a URL
when the user presses "Done" within the tool they will be sent to the URL.
If this is "_close" the window will be closed - this makes the most sense
if the original launch was a popup window.  If this parameter is omitted,
no "Done" button will be shown - this is useful if the tool is launched
in an iframe.</li>
</ul>
<p>
<?php

$tools = findFiles();
if ( count($tools) > 1 ) {
    echo("<p>Tools in this system:</p><ul>\n");
    foreach ($tools as $tool ) {
        echo("<li>".htmlent_utf8($tool)."</li>\n");
    }
    echo("</ul>\n");
}

$OUTPUT->footer();

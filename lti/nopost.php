<?php
$OUTPUT->header();
?>
</head>
<body style="padding:10px;">
<div class="alert alert-danger" style="margin: 10px;">
<p>This request is missing critical lauch data.  This URL is intended to be
launched using
<a href="http://developers.imsglobal.org/" target="_blank">IMS Learning
Tools Interoperability</a> with POST data that is signed using an
OAuth Key and secret that needs to be obtained from the system administrator
of <b>
<?php echo($CFG->servicename); ?></b>.  At a minumum this application needs
a <b>user_id</b> parameter as part of the LTI launch data (i.e. no anonymous launches).
</p>
</div>
<?php
if ( count($_POST) > 0 ) {
    print "<pre>\n";
    print "Raw POST Parameters:\n\n";
    ksort($_POST);
    foreach($_POST as $key => $value ) {
        print htmlent_utf8($key) . "=" . htmlent_utf8($value) . " (".mb_detect_encoding($value).")\n";
    }
    print "</pre>\n";
}

$OUTPUT->footer();

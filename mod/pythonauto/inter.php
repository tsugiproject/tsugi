<?php 
require_once "../../config.php";
session_start();
$OUTPUT->header();
?>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Skulpt</title>
    <link rel="stylesheet" type="text/css" media="all" href="<?php echo($CFG->staticroot); ?>/static/codemirrorepl/codemirror.css">
    <script type="text/javascript" src="<?php echo($CFG->staticroot); ?>/static/js/jquery-1.10.2.min.js"></script>
    <script src="<?php echo($CFG->staticroot); ?>/static/codemirrorepl/codemirrorepl.js" type="text/javascript"></script>
    <script src="<?php echo($CFG->staticroot); ?>/static/codemirrorepl/repl.js" type="text/javascript"></script>
    <script src="<?php echo($CFG->staticroot); ?>/static/codemirrorepl/python.js" type="text/javascript"></script>
    <script src="<?php echo(getLocalStatic(__FILE__)); ?>/static/skulpt-new/skulpt.min.js" type="text/javascript"></script>
    <script src="<?php echo(getLocalStatic(__FILE__)); ?>/static/skulpt-new/skulpt-stdlib.js" type="text/javascript"></script>
</head>
<?php
$OUTPUT->bodyStart();
?>
<div class="page" style="padding: 5px; border: 3px solid grey">
    <textarea id="interactive" cols="85" rows="10" ></textarea>
<div style="height:80px"></div>
</div>
<div class="notes" style="padding:20px;">
You can type Python commands at the 
above chevron prompt (>>>).  Some commands might be:
<pre>
>>> print "hello world"
>>> x = 123
>>> x = x + 100
>>> print x
</pre>
This is an experimental in-browser Python interpreter.  The 
error messages and output will be a little different than the Python that runs on your desktop.
This only supports a subset of Python 2.6 (i.e. it is not Python 3.0).
</p>
</div>
<p>
This interactive Python interpreter is based on <a href="http://skulpt.org/" target="_blank">Skulpt</a> and
<a href="http://codemirror.net/" target="_blank">CodeMirror</a>.
</p>
<?php
$OUTPUT->footer();

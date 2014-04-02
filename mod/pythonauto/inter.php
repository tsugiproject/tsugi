<?php 
require_once "../../config.php";
session_start();
headerContent();
?>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Skulpt</title>
    <link rel="stylesheet" type="text/css" media="all" href="<?php echo($CFG->staticroot); ?>/static/codemirror/codemirror.css">
    <script type="text/javascript" src="<?php echo($CFG->staticroot); ?>/static/js/jquery-1.10.2.min.js"></script>
    <script src="<?php echo($CFG->staticroot); ?>/static/codemirror/codemirrorepl.js" type="text/javascript"></script>
    <script src="<?php echo($CFG->staticroot); ?>/static/codemirror/repl.js" type="text/javascript"></script>
    <script src="<?php echo($CFG->staticroot); ?>/static/codemirror/python.js" type="text/javascript"></script>
    <script src="<?php echo(getLocalStatic(__FILE__)); ?>/static/skulpt-new/skulpt.min.js" type="text/javascript"></script>
    <script src="<?php echo(getLocalStatic(__FILE__)); ?>/static/skulpt-new/skulpt-stdlib.js" type="text/javascript"></script>
</head>
<?php
startBody();
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
This is an experimental tool and the 
error messages might be a little different than the Python that runs on your desktop.
</p>
</div>
<p>
This interactive Python application is based on <a href="http://skulpt.org/" target="_blank">Skulpt</a> and
<a href="http://codemirror.net/" target="_blank">CodeMirror</a>.
</p>
<?php
footerContent();

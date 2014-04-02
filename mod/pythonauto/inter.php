<?php 
require_once "../../config.php";
?>
<!DOCTYPE>
<html>
<head>
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
<body>
<div class="page">
    <textarea id="interactive" cols="85" rows="1"></textarea>
<hr/>

</div>
</body>
</html>

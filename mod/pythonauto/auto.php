<?php
require_once "../../config.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
session_start();

require_once "exercises.php";

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isInstructor($LTI);
$user_id = $LTI['user_id'];
$p = $CFG->dbprefix;

headerContent();

$QTEXT = 'Please write a Python program to open the file 
"mbox-short.txt" and count the number of lines in the file and 
match the desired output below:';
$DESIRED = '1910 Lines';
$CODE = 'fh = open("mbox-short.txt", "r")

count = 0
for line in fh:
   count = count + 1

print count,"Lines"
';
$CHECKS = false;
$ex = "count";
$EX = true;

if ( isset($_REQUEST["exercise"]) ) {
    $ex = $_REQUEST["exercise"];
    $EX = false;
    if ( isset($EXERCISES[$ex]) ) $EX = $EXERCISES[$ex];
    if ( $EX !== false ) {
        $CODE = '';
        $QTEXT = $EX["qtext"];
        $DESIRED = $EX["desired"];
        if ( isset($EX["code"]) ) $CODE = $EX["code"];
        if ( isset($EX["checks"]) ) $CHECKS = json_encode($EX["checks"]);
    }
} 

$DESIRED = rtrim($DESIRED);
if ( $EX === false ) {
    echo("</head><body><h1>Error, exercise ".htmlentities($ex).
        " is not available.  Please see your instructor.</h1></body>");
    return;
}
?>
<style>
body { font-family: sans-serif; }
</style>
<script src="skulpt/skulpt.js?v=1" type="text/javascript"></script>
<script src="skulpt/builtin.js?v=1" type="text/javascript"></script>
<script type="text/javascript">

function builtinRead(x)
{
    if (Sk.builtinFiles === undefined || Sk.builtinFiles["files"][x] === undefined)
        throw "File not found: '" + x + "'";
    return Sk.builtinFiles["files"][x];
}

function makefilediv(name,text) {
    var msgContainer = document.createElement('div');
    msgContainer.setAttribute('id', name);  //set id
    msgContainer.setAttribute('style', 'display:none');  //set CSS
    text.replace("&","&amp;");
    text.replace("<","&lt;");

    var msg2 = document.createTextNode(text);
    msgContainer.appendChild(msg2);
    document.body.appendChild(msgContainer);
}

// May want this under the control of the exercises.
// Instead of always retrieving them

function load_files() {
    $.get('romeo.txt', function(data) {
        makefilediv('romeo.txt', data);
    });
    $.get('words.txt', function(data) {
        makefilediv('words.txt', data);
    });
    $.get('mbox-short.txt', function(data) {
        makefilediv('mbox-short.txt', data);
    });
}

<?php
    if ( $CHECKS === false ) {
        echo("   window.CHECKS = false;\n");
    } else {
        echo("   window.CHECKS = $CHECKS;\n");
    }
?>
    window.GLOBAL_ERROR = true;
    window.GLOBAL_TIMER = false;

    if (typeof console == "undefined") {
        console = {log: function() {}};
    }

    function hideall() {
        $("#check").hide();
        $("#grade").hide();
        $("#redo").hide();
        $("#gradegood").hide();
        $("#gradebad").hide();
    }

    function finalcheck() {
        if ( window.GLOBAL_TIMER != false ) window.clearInterval(window.GLOBAL_TIMER);
        window.GLOBAL_TIMER = false;
        hideall();
        $("#spinner").hide();
        var prog = document.getElementById("code").value;
        for ( var key in window.CHECKS ) {
            // The key can be inverted if the first character is !
            if ( key.length > 1 && key.substring(0,1) == '!' ) {
                xkey = key.substring(1);
                if ( prog.indexOf(xkey) < 0 ) continue;
            } else {
                if ( prog.indexOf(key) >= 0 ) continue;
            }
            alert(window.CHECKS[key]);
            window.GLOBAL_ERROR = true;
            break;
        }

        if ( window.GLOBAL_ERROR ) {
            $("#redo").show();
        } else {
            $("#check").show();
            $("#grade").show();
        }
    }

    function outf(text)
    {
        // console.log('Text='+text);
        var output = document.getElementById("output");
        oldtext = output.innerHTML;
        oldtext = oldtext.replace(/<span.*span>/g,"")
        text = text.replace(/</g, '&lt;');
        newtext = oldtext + text;
        output.innerHTML = newtext;
        var desired = document.getElementById("desired").innerHTML;

        deslines = desired.split('\n');
        newlines = newtext.split('\n');
        newoutput = '';
        err = false;
        if ( window.GLOBAL_TIMER != false ) window.clearInterval(window.GLOBAL_TIMER);
        window.GLOBAL_TIMER = setTimeout("finalcheck();",1500);
        for ( i=0, newlength = newlines.length; i < newlength; i++ ) {
            if ( i > 0 ) newoutput += '\n';
            nl = newlines[i];
            newoutput += nl;
            if ( i >= deslines.length ) {
                if ( !err ) newoutput += '<span style="color:red"> &larr; Extra output</span>';
                err = true;
                continue;
            }
            dl = deslines[i];
            if ( dl != nl ) {
                if ( !err ) newoutput += '<span style="color:red"> &larr; Mismatch</span>';
                err = true;
                continue;
            }
        }
        if ( !err && deslines.length > newlines.length ) {
            newoutput += '<span style="color:red"> &larr; Missing output</span>';
        }
        window.GLOBAL_ERROR = err;
        console.log(err);
        output.innerHTML = newoutput;
    }

    function runit()
    {
        hideall();
        var prog = document.getElementById("code").value;
        if ( prog.length < 1 ) {
            alert("You do not have any Python code");
            return false;
        }
        $("#spinner").show();
        var output = document.getElementById("output");
        output.innerHTML = '';
        if ( window.GLOBAL_TIMER != false ) window.clearInterval(window.GLOBAL_TIMER);
        window.GLOBAL_TIMER = setTimeout("finalcheck();",2500);
        Sk.configure({output:outf, read: builtinRead});
        try {
            var module = Sk.importMainWithBody("<stdin>", false, prog);
        } catch (e) {
            if ( window.GLOBAL_TIMER != false ) window.clearInterval(window.GLOBAL_TIMER);
            window.GLOBAL_TIMER = false;
            window.GLOBAL_ERROR = true;
            hideall();
            $("#spinner").hide();
            $("#redo").show();
            alert(e);
        }
        return false;
    }

    function gradeit() {
        $("#check").hide();
        $("#spinner").show();

        var grade = 1.0;
        var code = document.getElementById("code").value;
        var toSend = { grade : grade, code : code };

        $.ajax({
            type: "POST",
            url: "<? echo sessionize('grade.php'); ?>",
            dataType: "json",
            data: toSend
        }).done( function (data) {
            console.log(data);
            $("#spinner").hide();
            if ( data["status"] == "success") {
                $("#gradegood").show();
            } else {
                $("#gradebad").show();
            }
        });
        return false;
    }

function doc_ready() {
	$doc = $(window).height();
	$fh = $("#footer").height();
	$it = $('#inputs').offset().top;
	$ct = $('#code').offset().top;
    $qh = $ct - $it;
	$avail = $doc - $ct - $fh;
	if ( $avail < 400 ) $avail = 400;
	if ( $avail > 900 ) $avail = 900;
	$ch = $avail * 0.5;
	$("#inputs").height($qh+$ch);
	$("#outputs").height($avail*0.5);
	$('#code').height($ch -15);
    load_files();
} 
</script>
<style>
pre {
white-space: -moz-pre-wrap; /* Mozilla, supported since 1999 */
white-space: -pre-wrap; /* Opera 4 - 6 */
white-space: -o-pre-wrap; /* Opera 7 */
white-space: pre-wrap; /* CSS3 */
word-wrap: break-word; /* IE 5.5+ */
}
</style>
<?php
startBody();
?>
<div style="padding: 0px 15px 0px 15px;">
<div id="inputs" style="height:300px;">
<div class="well" style="background-color: #EEE8AA">
<?php echo($QTEXT); ?>
</div>
<form style="height:100%;">
<button onclick="runit()" type="button">Check Code</button>
<?php
   if ( $instructor ){
       echo(' <a href="'.sessionize("../../core/gradebook/grade.php").'" target="_blank">View Grades</a>'."\n");
?>
<span id="grade" style="display:none"></span>
<?php } else { ?>
<button id="grade" onclick="gradeit()" type="button" style="display:none">Submit Grade</button>
<?php } 
// } ?>
<?php
if ( ! isset($_GET["done"]) ) {
  $url = $_GET['done'];
  echo("<button onclick=\"window.location='$url';\" type=\"button\">Done</button>\n");
}
?>
<img id="spinner" src="skulpt/spinner.gif" style="vertical-align: middle;display: none">
<span id="redo" style="color:red;display:none"> Please Correct your code and re-run. </span>
<span id="check" style="color:green;display:none"> Congratulations the exercise is complete. </span>
<span id="gradegood" style="color:green;display:none"> Grade Updated. </span>
<span id="gradebad" style="color:red;display:none"> Error storing grade. </span>
<br/>
Enter/Edit Your Python Code Here:<br/>
<textarea id="code" cols="80" style="font-family:Courier,fixed;font-size:16px;color:blue;width:99%;">
<?php echo($CODE); ?>
</textarea>
</form>
</div>
<div id="outputs" style="height:300px; min-height:200px;">
<div id="left" style="padding:8px;width:47%;float:right;height:100%;overflow:scroll;border:1px solid black">
<b>Desired Output</b>
<pre id="desired" style="height:100%"><?php echo($DESIRED); echo("\n"); ?>
</pre>
</div>
<div id="right" style="padding: 8px;width:47%;height:100%;float:left;overflow:scroll;border:1px solid black">
<b>Your Output</b>
<pre id="output" style="height:100%;"></pre>
</div>
</div>
<div id="footer">
<br clear="all"/>
<center>
This autograder is based on <a href="http://skulpt.org/" target="_new">Skulpt</a>.
</center>
</div>
<?php
footerContent('<script type="text/javascript">
$(document).ready(function() { doc_ready(); } );
</script>');

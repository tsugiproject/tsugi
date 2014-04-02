<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";
session_start();

require_once "exercises.php";

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isInstructor($LTI);
$user_id = $LTI['user_id'];
$p = $CFG->dbprefix;

// Get the current user's grade data also checks session
$row = loadGrade($pdo);
$OLDCODE = false;
if ( $row !== false && isset($row['json'])) {
    $json = json_decode($row['json']);
    if ( isset($json->code) ) $OLDCODE = $json->code;
}

// Get any due date information
$dueDate = getDueDate();

headerContent();

// Defaults
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
$EX = true;

// Check which exercise we are supposed to do
$ex = getCustom('exercise');
if ( $ex === false && isset($_REQUEST["exercise"]) ) {
    $ex = $_REQUEST["exercise"];
}
if ( $ex === false ) {
    $ex = "count";
} else {
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
.inputarea { width: 100%; height: 250px; }
</style>
<link href="<?php echo($CFG->staticroot); ?>/static/css/jquery.splitter.css" rel="stylesheet"/>
<link href="<?php echo($CFG->staticroot); ?>/static/codemirror/codemirror.css" rel="stylesheet"/>
<script type="text/javascript" src="<?php echo($CFG->staticroot); ?>/static/codemirror/codemirror.js"></script>
<script type="text/javascript" src="<?php echo($CFG->staticroot); ?>/static/codemirror/python.js"></script>
<!--
<script src="skulpt/skulpt.js?v=1" type="text/javascript"></script>
<script src="skulpt/builtin.js?v=1" type="text/javascript"></script>
-->
<script src="<?php echo(getLocalStatic(__FILE__)); ?>/static/skulpt-new/skulpt.min.js?v=1" type="text/javascript"></script>
<script src="<?php echo(getLocalStatic(__FILE__)); ?>/static/skulpt-new/skulpt-stdlib.js?v=1" type="text/javascript"></script>
<script type="text/javascript">

function builtinRead(x)
{
    if (Sk.builtinFiles === undefined || Sk.builtinFiles["files"][x] === undefined)
        throw "File not found: '" + x + "'";
    return Sk.builtinFiles["files"][x];
}

function makefilediv(name,text) {
    text.replace("&","&amp;");
    text.replace("<","&lt;");
/*
    var msgContainer = document.createElement('div');

    var msg2 = document.createTextNode(text);
    msgContainer.appendChild(msg2);
    msgContainer.setAttribute('id', name);  //set id
    msgContainer.setAttribute('style', 'display:none');  //set CSS
    document.body.appendChild(msgContainer);
*/
    $('body').append('<div id="'+name+'" style="display: none">'+text+'</div>');
}

// May want this under the control of the exercises.
// Instead of always retrieving them

function load_files() {
    $.get('<?php echo(getLocalStatic(__FILE__)); ?>/static/files/romeo.txt', function(data) {
        makefilediv('romeo.txt', data);
    });
    $.get('<?php echo(getLocalStatic(__FILE__)); ?>/static/files/words.txt', function(data) {
        makefilediv('words.txt', data);
    });
    $.get('<?php echo(getLocalStatic(__FILE__)); ?>/static/files/mbox-short.txt', function(data) {
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
    window.CM_EDITOR = false;
    window.SPLIT_1 = false;
    window.SPLIT_2 = false;
    window.MOBILE = false;

    if (typeof console == "undefined") {
        console = {log: function() {}};
    }

    function hideall() {
        $("#check").hide();
        $("#grade").hide();
        $("#redo").hide();
        $("#gradegood").hide();
        $("#gradelow").hide();
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
            // $("#grade").show();
            gradeit();
        }
    }

    function outf(text)
    {
        // console.log('Text='+text+':');
        var output = document.getElementById("output");
        oldtext = output.innerHTML;
        // window.console && console.log(oldtext);
        oldtext = oldtext.replace(/<span.*span>/g,"")
        text = text.replace(/</g, '&lt;');
        newtext = oldtext + text;
        output.innerHTML = newtext;
        var desired = document.getElementById("desired").innerHTML;

        // desired = $.trim(desired);
        // newtext = $.trim(newtext);
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
            // Extra blank lines are no problem.
            if ( i >= deslines.length && $.trim(nl) == '' ) {
                continue;
            }
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
        if ( window.CM_EDITOR !== false ) window.CM_EDITOR.save();
        var prog = document.getElementById("code").value;
        window.console && console.log('code');
        window.console && console.log(prog);
        if ( prog.length < 1 ) {
            alert("You do not have any Python code");
            return false;
        }
        $("#spinner").show();

        var toSend = { code : prog };
        $.ajax({
            type: "POST",
            url: "<?php echo sessionize('sendcode.php'); ?>",
            dataType: "json",
            data: toSend
        }).done( function (data) {
            console.log("Code updated on server.");
        });

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

    function resetcode() {
        if ( ! confirm("Are you sure you want to reset the code area to the original sample code?") ) return;
        if ( window.CM_EDITOR !== false ) window.CM_EDITOR.toTextArea();
        window.CM_EDITOR = false;
        document.getElementById("code").value = document.getElementById("resetcode").value;
        if ( window.MOBILE === false ) load_cm();
    }

    function gradeit() {
        $("#check").hide();
        $("#spinner").show();

        var oldgrade = <?php echo($row && isset($row['grade']) ? $row['grade'] : '0.0'); ?>;
        var grade = 1.0 - <?php echo( $dueDate->penalty); ?>;
        if ( oldgrade > grade ) grade = oldgrade;  // Never go down
        window.console && console.log("Sending grade="+grade);
        var code = document.getElementById("code").value;
        var toSend = { grade : grade, code : code };

        $.ajax({
            type: "POST",
            url: "<?php echo sessionize('sendgrade.php'); ?>",
            dataType: "json",
            data: toSend
        }).done( function (data) {
            window.console && console.log("Grade response received...");
            window.console && console.log(data);
            $("#spinner").hide();
            if ( data["status"] == "success") {
                $("#gradegood").show();
            } else {
                $("#gradebad").show();
            }
        }).error( function(data) {;
            window.console && console.log("Grade response received...");
            window.console && console.log(data);
            $("#spinner").hide();
            $("#gradebad").show();
        });
        return false;
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
<div id="overall" style="border: 3px solid black;">
<div id="inputs">
<div class="well" style="background-color: #EEE8AA">
<?php
if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}
?>
<?php echo($QTEXT); ?>
</div>
<form id="forminput">
<button onclick="runit()" type="button">Check Code</button>
<?php 
    if ( strlen($CODE) > 0 ) {
        echo('<button onclick="resetcode()" type="button">Reset Code</button> ');
    }
    doneButton();
    if ( $instructor ) {
       echo(' <a href="grades.php" target="_blank">View Grades</a>'."\n");
    }
?>
<img id="spinner" src="skulpt/spinner.gif" style="vertical-align: middle;display: none">
<span id="redo" style="color:red;display:none"> Please Correct your code and re-run. </span>
<span id="gradegood" style="color:green;display:none"> Grade updated on server. </span>
<span id="gradelow" style="color:green;display:none"> Grade updated on server. </span>
<span id="gradebad" style="color:red;display:none"> Error storing grade on server. </span>
<br/>
&nbsp;<br/>
<div id="textarea" class="inputarea">
<textarea id="code" style="width:100%; height: 100%; font-family:Courier,fixed;font-size:16px;color:blue;">
<?php 
if ( $OLDCODE !== false ) {
    echo(htmlentities($OLDCODE));
} else {
    echo(htmlentities($CODE));
}
?>
</textarea>
</div>
</div>
<div id="outputs">
<div id="left">
<b>Your Output</b>
<pre id="output" class="inputarea"></pre>
</pre>
</div>
<div id="right">
<b>Desired Output</b>
<pre id="desired" class="inputarea"><?php echo($DESIRED); echo("\n"); ?>
</div>
</div>
</div>
</form>
</div>
<div id="footer" style="text-align: center">
This autograder is based on <a href="http://skulpt.org/" target="_blank">Skulpt</a> and
<a href="http://codemirror.net/" target="_blank">CodeMirror</a>.
<textarea id="resetcode" cols="80" style="display:none">
<?php   echo(htmlentities($CODE)); ?>
</textarea>
<?php
footerStart();
?>
<script type="text/javascript" src="<?php echo($CFG->staticroot); ?>/static/js/jquery.splitter-0.14.0.js"></script>
<script type="text/javascript">
// $(document).ready(function() { doc_ready(); } );
function compute_divs() {
	$doc = $(window).height();
	$ot = $('#overall').offset().top;
	$ft = $('#forminput').offset().top;
    window.console && console.log('doc='+$doc+' ft='+$ft+' overall='+$ot);
	$avail = $doc - ($ot - 30);
	if ( $avail < 400 ) $avail = 400;
	if ( $avail > 700 ) $avail = 700;
    $favail = $avail - $ft + $ot;

    $('#overall').width('95%').height($avail);
    $('#inputs').width('45%').height($avail);
    $('#forminput').width('95%').height($favail);
    $('#outputs').width('45%').height($avail);
    $('#textarea').height('100%');
    $('#output').height('100%');
    $('#desired').height('100%');

    if ( window.SPLIT_1 == false ) {
        window.SPLIT_1 = $('#overall').split({orientation:'vertical', limit:100});
        window.console && console.log(window.SPLIT_1);
        window.SPLIT_2 = $('#outputs').split({orientation:'horizontal', limit:100});
    } else {
        window.SPLIT_1.position('50%');
        window.SPLIT_2.position('50%');
    }
    window.console && console.log('avail='+$avail+' favail='+$favail);
} 

// Setup Codemirror
function load_cm() {
    window.CM_EDITOR = CodeMirror.fromTextArea(document.getElementById("code"), 
    {
        mode: {name: "python",
        version: 2,
        singleLineStringErrors: false},
        lineNumbers: true,
        indentUnit: 4,
        matchBrackets: true
    });
    window.CM_EDITOR.setSize('100%', '100%');
}

 $().ready(function(){
    // I cannot make this reliable :(
    $(window).resize(function () { compute_divs(); });
    window.MOBILE = $(window).width() <= 480;
    // window.MOBILE = TRUE;
    load_files();
    if ( MOBILE === false ) {
        compute_divs();
        load_cm();
    }
 });
</script>
<?php
footerEnd();

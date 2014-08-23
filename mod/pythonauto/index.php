<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";
require_once "exercises.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;

// Sanity checks
$LTI = LTIX::requireData(array('user_id', 'link_id', 'role','context_id', 'result_id'));
$user_id = $USER->id;
$p = $CFG->dbprefix;

$oldsettings = Settings::linkGetAll();

// Handle incoming settings POST data
if ( isset($_POST['settings_internal_post']) && $USER->instructor ) {
    $newsettings = array();
    foreach ( $_POST as $k => $v ) {
        if ( $k == session_name() ) continue;
        if ( $k == 'settings_internal_post' ) continue;
        $newsettings[$k] = $v;
    }
    // Only update settings if they change
    if ( array_diff_assoc($oldsettings,$newsettings) ) {
        Settings::linkSetAll($newsettings);
        header( 'Location: '.addSession('index.php?howdysuppress=1') ) ;
        return;
    }
}

// Get the current user's grade data
$row = gradeLoad();
$OLDCODE = false;
$json = array();
$editor = 1;
if ( $row !== false && isset($row['json'])) {
    $json = json_decode($row['json'], true);
    if ( isset($json["code"]) ) $OLDCODE = $json["code"];
    if ( isset($json["editor"]) ) $editor = $json["editor"];
}

if ( isset($_GET['editor']) && ( $_GET['editor'] == '1' || $_GET['editor'] == '0' ) ) {
    $neweditor = $_GET['editor']+0;
    if ( $editor != $neweditor ) {
        gradeUpdateJson(array("editor" => $neweditor));
        $json['editor'] = $neweditor;
        $editor = $neweditor;
    }
}
$codemirror = $editor == 1;

// Get any due date information
$dueDate = LTIX::getDueDate();

$OUTPUT->header();

// Defaults
$QTEXT = 'You can write any code you like in the window below.  There are three files
loaded and ready for you to open if you want to do file processing:
"mbox-short.txt", "romeo.txt", and "words.txt".';
$DESIRED = false;
$CODE = 'fh = open("romeo.txt", "r")

count = 0
for line in fh:
    print line.strip()
    count = count + 1

print count,"Lines"';
$CHECKS = false;
$EX = false;

// Check which exercise we are supposed to do - settings, then custom, then 
// GET
if ( isset($oldsettings['exercise']) && $oldsettings['exercise'] != '0' ) {
    $ex = $oldsettings['exercise'];
} else {
    $ex = LTIX::getCustom('exercise');
}
if ( $ex === false && isset($_REQUEST["exercise"]) ) {
    $ex = $_REQUEST["exercise"];
}
if ( $ex !== false && $ex != "code" ) {
    if ( isset($EXERCISES[$ex]) ) $EX = $EXERCISES[$ex];
    if ( $EX !== false ) {
        $CODE = '';
        $QTEXT = $EX["qtext"];
        $DESIRED = $EX["desired"];
        $DESIRED2 = isset($EX["desired2"]) ? $EX["desired2"] : '';
        $DESIRED = rtrim($DESIRED);
        $DESIRED2 = rtrim($DESIRED2);
        if ( isset($EX["code"]) ) $CODE = $EX["code"];
        if ( isset($EX["checks"]) ) $CHECKS = json_encode($EX["checks"]);
    }
    if ( $EX === false ) {
        echo("</head><body><h1>Error, exercise ".htmlentities($ex).
            " is not available.  Please see your instructor.</h1></body>");
        return;
    }
}
?>
<style>
body { font-family: sans-serif; }
.inputarea { width: 100%; height: 250px; }
</style>
<link href="<?php echo($CFG->staticroot); ?>/static/css/jquery.splitter.css" rel="stylesheet"/>
<?php if ( $codemirror ) { ?>
<link href="<?php echo($CFG->staticroot); ?>/static/codemirror/codemirror.css" rel="stylesheet"/>
<script type="text/javascript" src="<?php echo($CFG->staticroot); ?>/static/codemirror/codemirror.js"></script>
<script type="text/javascript" src="<?php echo($CFG->staticroot); ?>/static/codemirror/python.js"></script>
<?php } ?>
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
    $.get('static/files/romeo.txt', function(data) {
        makefilediv('romeo.txt', data);
    });
    $.get('static/files/words.txt', function(data) {
        makefilediv('words.txt', data);
    });
    $.get('static/files/mbox-short.txt', function(data) {
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
        $("#complete").hide();
        $("#gradegood").hide();
        $("#gradelow").hide();
        $("#gradebad").hide();
    }

    // http://stackoverflow.com/questions/1418050/string-strip-for-javascript
    if(typeof(String.prototype.trim) === "undefined")
    {
        String.prototype.trim = function()
        {
            return String(this).replace(/^\s+|\s+$/g, '');
        };
    }

    function finalcheck() {
        if ( window.GLOBAL_TIMER != false ) window.clearInterval(window.GLOBAL_TIMER);
        window.GLOBAL_TIMER = false;
        hideall();
        $("#spinner").hide();
        var prog = document.getElementById("code").value;
        var lines = prog.split("\n");
        prog = '';
        for ( var i = 0; i < lines.length; i++ ) {
            line = lines[i];
            if ( line.substring(0,1) == '#' ) continue;
            var pos = line.indexOf('#');
            if ( pos > 0 ) {
                line = line.substring(0,pos);
            }
            prog = prog + line + "\n";
        }
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
<?php if ( $EX !== false ) { ?>
            $("#redo").show();
<?php } else { ?>
            $("#complete").show();
<?php } ?>
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

        if ( window.GLOBAL_TIMER != false ) window.clearInterval(window.GLOBAL_TIMER);
        window.GLOBAL_TIMER = setTimeout("finalcheck();",1500);

        var desired = document.getElementById("desired");
        if ( desired == null ) return;
        var desired = desired.innerHTML;
        var desired2 = document.getElementById("desired2").innerHTML;

        deslines = desired.split('\n');
        deslines2 = desired2.split('\n');
        newlines = newtext.split('\n');
        newoutput = '';
        err = false;
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
            dl2 = dl;
            if ( i < deslines2.length ) {
                dl2 = deslines2[i];
            }
            if ( dl != nl && dl2 != nl) {
                if ( !err ) newoutput += '<span style="color:red"> &larr; Mismatch</span>';
                err = true;
                continue;
            }
        }
        if ( !err && deslines.length > newlines.length ) {
            newoutput += '<span style="color:red"> &larr; Missing output</span>';
            err = true;
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

<?php if ( isset($LTI['result_id']) ) { ?>
        var toSend = { code : prog };
        $.ajax({
            type: "POST",
            url: "<?php echo addSession('sendcode.php'); ?>",
            dataType: "json",
            beforeSend: function (request)
            {
                request.setRequestHeader("X-CSRF-Token", CSRF_TOKEN);
            },
            data: toSend
        }).done( function (data) {
            console.log("Code updated on server.");
        });
<?php } ?>

        var output = document.getElementById("output");
        output.innerHTML = '';
        if ( window.GLOBAL_TIMER != false ) window.clearInterval(window.GLOBAL_TIMER);
        window.GLOBAL_TIMER = setTimeout("finalcheck();",2500);
        Sk.configure({output:outf, read: builtinRead});
        // Sk.execLimit = 10000; // Ten Seconds

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

        if ( window.CM_EDITOR !== false ) window.CM_EDITOR.save();
        var code = document.getElementById("code").value;
        var toSend = { grade : grade, code : code };

        $.ajax({
            type: "POST",
            url: "<?php echo addSession('sendgrade.php'); ?>",
            dataType: "json",
            beforeSend: function (request)
            {
                request.setRequestHeader("X-CSRF-Token", CSRF_TOKEN);
            },
            data: toSend
        }).done( function (data) {
            window.console && console.log("Grade response received...");
            window.console && console.log(data);
            $("#spinner").hide();
            if ( data.status == "success") {
                $("#gradegood").show();
                $('#curgrade').text('1');
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
$OUTPUT->bodyStart();

if ( $USER->instructor ) {
?>
<div class="modal fade" id="settings">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Configure 
        <?php echo(htmlent_utf8($LINK->title)); ?>
        </h4>
      </div>
      <div class="modal-body">
        <img id="settings_spinner" src="<?php echo($OUTPUT->getSpinnerUrl()); ?>" style="display: none">
        <span id="save_fail" style="display:none; color:red">Unable to save settings</span>
        </p>
        <form id="settings_form" method="POST">
            <input type="hidden" name="settings_internal_post" value="1"/>
            <select name="exercise">
            <option value="0">No Exercise - Python Playground</option>
<?php
            foreach ( $EXERCISES as $k => $v ) {
                echo('<option value="'.$k.'"');
                if ( isset($oldsettings['exercise']) && $k == $oldsettings['exercise'] ) {
                    echo(' selected');
                }
                echo('>'.$k.'</option>'."\n");
            }
?>
            </select>
<?php
            $due = isset($oldsettings['due']) ? $oldsettings['due'] : '';
            $timezone = isset($oldsettings['timezone']) ? $oldsettings['timezone'] : 'Pacific/Honolulu';
            $time = isset($oldsettings['time']) ? $oldsettings['time'] : 86400;
            $cost = isset($oldsettings['cost']) ? $oldsettings['cost'] : 0.2;
?>
            <label for="due">
            Please enter a due date in ISO 8601 format (2015-01-30T20:30) or leave blank for no due date.  
            You can leave off the time to allow the assignment to be turned in any time during the day.<br/>
            <input type="text" value="<?php echo(htmlspec_utf8($due)); ?>" name="due"></label>
            <label for="timezone">
            Please enter a valid PHP Time Zone like 'Pacific/Honolulu' (default).  If you are
            teaching in many time zones around the world, 'Pacific/Honolulu' is a good time
            zone to choose - this is why it is the default.<br/>
            <input type="text" value="<?php echo(htmlspec_utf8($timezone)); ?>" name="timezone"></label>
            <p>The next two fields determine the "overall penalty" for being late.  We define a time period
            (in seconds) and a fractional penalty per time period.  The penalty is assessed for each 
            full or partial time period past the due date.  For example to deduct 20% per day, you would
            set the period to be 86400 (24*60*60) and the penalty to be 0.2.
            </p>
            <label for="time">Please enter the penalty time period in seconds.<br/>
            <input type="text" value="<?php echo(htmlspec_utf8($time)); ?>" name="time"></label>
            <label for="cost">Please enter the penalty deduction as a decimal between 0.0 and 1.0.<br/>
            <input type="text" value="<?php echo(htmlspec_utf8($cost)); ?>" name="cost"></label>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" id="settings_save" class="btn btn-primary">Save changes</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<?php } // end isInstructor() ?>

<div class="modal fade" id="info">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">
<?php
if ( isset($LINK->title) ) {
    echo(htmlent_utf8($LINK->title));
} else {
    welcomeUserCourse();
}
?></h4>
      </div>
      <div class="modal-body">
<?php if ( $EX === false ) { ?>
        <p>This is an open-ended space for you to write and execute Python programs.
        This page does not check our output and it does not send a grade back.  It is
        here as a place for you to develop small programs and test things out.
        </p>
<?php if ( isset($LTI['result_id']) ) { ?>
        <p>
        Whatever code you type will be saved and restored when you come back to this
        page.</p>
<?php } ?>
        <p>
        Remember that this is an in-browser Python emulator and as your programs get
        more sophisticated, you may encounter situations where this Python emulator
        gives <i>different</i> results than the real Python 2.7
        running on your laptop, desktop, or server.  It is intended to be used
        for simple programs being developed by beginning programmers while they
        are learning to program.
        </p> <p>
        There are three files loaded into this environment from the
        <a href="http://www.pythonlearn.com/" target="_blank">Python for Informatics</a>
        web site and ready for you to open if you want to
        do file processing: "mbox-short.txt", "romeo.txt", and "words.txt".
        </p>
<?php } else { ?>
<?php if ( isset($LTI['grade']) ) { ?>
        <p style="border: blue 1px solid">Your current grade in this
        exercise is <span id="curgrade"><?php echo($LTI['grade']); ?></span>.</p>
<?php } ?>
        <p>Your goal in this auto grader is to write or paste in a program that implements the specifications
        of the assignment.  You run the program by pressing "Check Code".
        The output of your program is displayed in the "Your Output" section of the screen.
        If your output does not match the "Desired Output", you will not get a score.
        </p><p>
        Even if "Your Output" matches "Desired Output" exactly,
        the autograder still does a few checks of your source code to make sure that you
        implemented the assignment using the expected techniques from the chapter. These messages
        can also help struggling students with clues as to what might be missing.
        </p>
        <p>
        This autograder keeps your highest score, not your last score.  You either get full credit (1.0) or
        no credit (0.0) when you run your code - but if you have a 1.0 score and you do a failed run,
        your score will not be changed.
        </p>
<?php } ?>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


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
<?php
    if ( $EX !== false ) {
        echo('<button onclick="runit()" type="button">Check Code</button>'."\n");
    } else {
        echo('<button onclick="runit()" type="button">Run Python</button>'."\n");
    }
    if ( strlen($CODE) > 0 ) {
        echo('<button onclick="resetcode()" type="button">Reset Code</button> ');
    }
    echo('<button onclick="$(\'#info\').modal();return false;" type="button"><span class="glyphicon glyphicon-info-sign"></span></button>'."\n");
    if ( $USER->instructor ) {
        echo('<button onclick="$(\'#settings\').modal();return false;" type="button"><span class="glyphicon glyphicon-pencil"></span></button>'."\n");
    }
    $OUTPUT->doneButton();
    if ( $USER->instructor ) {
        if ( $EX === false ) {
            echo(' <a href="grades.php" target="_blank">View Student Code</a>'."\n");
        } else {
            echo(' <a href="grades.php" target="_blank">View Grades</a>'."\n");
        }
    }
?>
<img id="spinner" src="skulpt/spinner.gif" style="vertical-align: middle;display: none">
<span id="redo" style="color:red;display:none"> Please correct your code and re-run. </span>
<span id="complete" style="color:green;display:none"> Execution complete. </span>
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
<?php if ( $EX !== false ) { ?>
<div id="right">
<b>Desired Output</b>
<pre id="desired" class="inputarea"><?php echo($DESIRED); echo("\n"); ?></pre>
<span id="desired2" style="display:none"><?php echo($DESIRED2); echo("\n"); ?></span>
</div>
<?php } ?>
</div>
</div>
</form>
</div>
<div id="footer" style="text-align: center">
Setting:
<?php
    if ( $codemirror ) {
        $editurl = reconstruct_query('auto.php',array("editor" => 0));
        $textval = "Hide editor";
    } else {
        $editurl = reconstruct_query('auto.php',array("editor" => 1));
        $textval = "Show editor";
    }
    echo('<a href="'.$editurl.'">'.$textval.'</a>.  ');
?>
This software supports Python 2.7 and is based on <a href="http://skulpt.org/" target="_blank">Skulpt</a>
and <a href="http://codemirror.net/" target="_blank">CodeMirror</a>.
The source code for this auto-grader is available on
<a href="https://github.com/csev/tsugi" target="_blank">on GitHub</a>.
<textarea id="resetcode" cols="80" style="display:none">
<?php   echo(htmlentities($CODE)); ?>
</textarea>
<?php
$OUTPUT->footerStart();
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
<?php if ( $EX !== false ) { ?>
    $('#desired').height('100%');
<?php } ?>

    if ( window.SPLIT_1 == false ) {
        window.SPLIT_1 = $('#overall').split({orientation:'vertical', limit:100});
        window.console && console.log(window.SPLIT_1);
<?php if ( $EX !== false ) { ?>
        window.SPLIT_2 = $('#outputs').split({orientation:'horizontal', limit:100});
<?php } ?>
    } else {
        window.SPLIT_1.position('50%');
<?php if ( $EX !== false ) { ?>
        window.SPLIT_2.position('50%');
<?php } ?>
    }
    window.console && console.log('avail='+$avail+' favail='+$favail);
}

<?php if ( $codemirror ) { ?>
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
<?php } ?>

 $().ready(function(){
<?php if ( $EX === false && ! isset($_REQUEST['howdysuppress']) ) { ?>
    $('#info').modal();
<? } ?>
    // I cannot make this reliable :(
    $(window).resize(function () { compute_divs(); });
    window.MOBILE = $(window).width() <= 480;
    // window.MOBILE = TRUE;
    load_files();
    if ( MOBILE === false ) {
        compute_divs();
<?php if ( $codemirror ) { ?>
        load_cm();
<?php } ?>
    }
    $('#settings_save').click(function(event) {
        $('#settings_spinner').show();
        $('#settings_form').submit();
        $('#settings_spinner').hide();
   });
 });
</script>
<?php
$OUTPUT->footerEnd();

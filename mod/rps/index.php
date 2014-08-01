<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

use \Tsugi\Core\LTIX;

// Sanity checks
$LTI = LTIX::requireData(array('user_id', 'link_id', 'role','context_id'));
$p = $CFG->dbprefix;

// The reset operation is a normal POST - not AJAX
if ( $USER->instructor && isset($_POST['reset']) ) {
    $PDOX->queryDie("DELETE FROM {$p}rps WHERE link_id = :LI",
        array(':LI' => $LINK->id));
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

?>
<html><head><title><?php _e("Playing Rock Paper Scissors in"); ?>
<?php echo(htmlent_utf8($CONTEXT->title)); ?>
</title>
<script type="text/javascript" 
src="<?php echo($CFG->staticroot); ?>/static/js/jquery-1.10.2.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){ 
  window.console && console.log('Hello JQuery..');
  $("#rock").click( function() { play(0); } ) ;
  $("#paper").click( function() { play(1); } ) ;
  $("#scissors").click( function() { play(2); } ) ;
});

var I18N = {};
I18N.playing = '<?php _e("Playing...");?>';
I18N.played = '<?php _e("Played");?>';
I18N.waiting = '<?php _e("Waiting for opponent...");?>';
I18N.tied = '<?php _e("You tied %s");?>';
I18N.beat = '<?php _e("You defeated %s");?>';
I18N.lost = '<?php _e("You lost to %s");?>';

function play(strategy) {
    $("#success").html("");
    $("#error").html("");
    $("#statustext").html(I18N.playing);
    $("#rpsform input").attr("disabled", true);
    $("#status").show();
    window.console && console.log(I18N.played+strategy);
    $.getJSON('<?php echo(addSession('play.php')); ?>&play='+strategy, function(data) {
        window.console && console.log(data);
        if ( data.guid ) {
            $("#statustext").html(I18N.waiting);
            check(data.guid); // Start the checking process
        } else {
            $("#status").hide();
            if ( data.tie ) {
                $("#success").html(sprintf(I18N.tied,data.displayname));
            } else if ( data.win ) {
                $("#success").html(sprintf(I18N.beat,data.displayname));
            } else { 
                $("#success").html(sprintf(I18N.lost+data.displayname));
            }
            $("#rpsform input").attr("disabled", false);
            leaders();  // Immediately update the leaderboard
        }
  });
  return false;
}

var GLOBAL_GUID;
function check(guid) {
    GLOBAL_GUID = guid;
    window.console && console.log('Checking game '+guid);
    $.getJSON('<?php echo(addSession('play.php')); ?>&game='+guid, function(data) {
        window.console && console.log(data);
        window.console && console.log(GLOBAL_GUID);
        if ( ! data.displayname ) {
            window.console && console.log("Need to wait some more...");
            setTimeout('check("'+GLOBAL_GUID+'")', 4000);
            return;
        }
        $("#status").hide();
        if ( data.tie ) {
            $("#success").html(sprintf(I18N.tied,data.displayname));
        } else if ( data.win ) {
            $("#success").html(sprintf(I18N.beat,data.displayname));
        } else { 
            $("#success").html(sprintf(I18N.lost,data.displayname));
        }
        $("#rpsform input").attr("disabled", false);
        leaders();  // Immediately update the leaderboard
  });
}

var OLD_TIMEOUT = false;
function leaders() {
    if ( OLD_TIMEOUT ) {
        clearTimeout(OLD_TIMEOUT);
        OLD_TIMEOUT = false;
    }
    window.console && console.log('Updating leaders...');
    $.getJSON('<?php echo(addSession('stats.php')); ?>', function(data) {
        window.console && console.log(data);
        $("#leaders").html("");
        $("#leaders").append("<ol>\n");
        for (var i = 0; i < data.length; i++) {
            entry = data[i];
            $("#leaders").append("<li>"+entry.name+' ('+entry.games+') score='+entry.score+"</li>\n");
            console.log(data[i]);
        }
        $("#leaders").append("</ol>\n");
        OLD_TIMEOUT = setTimeout('leaders()', 20000);
  });
}

// Run for the first time
leaders();
</script>

</head>
<body>
<form id="rpsform" method="post">
<input type="submit" id="rock" name="rock" value="<?php _e("Rock"); ?>"/>
<input type="submit" id="paper" name="paper" value="<?php _e("Paper"); ?>"/>
<input type="submit" id="scissors" name="scissors" value="<?php _e("Scissors"); ?>"/>
<?php if ( $USER->instructor ) { ?>
<input type="submit" name="reset" value="<?php _e("Reset"); ?>"/>
<?php } ?>
</form>
<p id="error" style="color:red"></p>
<p id="success" style="color:green"></p>
<p id="status" style="display:none">
<img id="spinner" src="spinner.gif">
<span id="statustext" style="color:orange"></span>
</p>
<div>
<p><b><?php _e("Leaderboard"); ?></b></p>
<p id="leaders">
</p>
<?php
$OUTPUT->footer();

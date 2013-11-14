<?php
require_once "../../config.php";
require_once $CFG->dirroot."/db.php";
require_once $CFG->dirroot."/lib/lti_util.php";

session_start();

// Sanity checks
if ( !isset($_SESSION['lti']) ) {
	die('This tool must be launched using LTI');
}
$LTI = $_SESSION['lti'];
if ( !isset($LTI['user_id']) || !isset($LTI['link_id']) ) {
	die('A user_id and link_id are required for this tool to function.');
}
$p = $CFG->dbprefix;
$instructor = isset($LTI['role']) && $LTI['role'] == 1 ;

// The reset operation is a normal POST - not AJAX
if ( $instructor && isset($_POST['reset']) ) {
    $sql = "DELETE FROM {$p}rps WHERE link_id = :LI";
    $stmt = $db->prepare($sql);
    $stmt->execute(array(':LI' => $LTI['link_id']));
    header( 'Location: '.sessionize('index.php') ) ;
    return;
}

?>
<html><head><title>Playing Rock Paper Scissors in
<?php echo(htmlent_utf8($LTI['context_title'])); ?>
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

function play(strategy) {
	$("#success").html("");
	$("#error").html("");
	$("#statustext").html("Playing...");
	$("#rpsform input").attr("disabled", true);
	$("#status").show();
	window.console && console.log('Played '+strategy);
	$.getJSON('<?php echo(sessionize('play.php')); ?>&play='+strategy, function(data) {
		window.console && console.log(data);
		if ( data.guid ) {
			$("#statustext").html("Waiting for opponent...");
			check(data.guid); // Start the checking process
		} else {
			$("#status").hide();
			if ( data.tie ) {
				$("#success").html("You tied "+data.displayname);
			} else if ( data.win ) {
				$("#success").html("You beat "+data.displayname);
			} else { 
				$("#success").html("You lost to "+data.displayname);
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
	$.getJSON('<?php echo(sessionize('play.php')); ?>&game='+guid, function(data) {
		window.console && console.log(data);
		window.console && console.log(GLOBAL_GUID);
		if ( ! data.displayname ) {
			window.console && console.log("Need to wait some more...");
			setTimeout('check("'+GLOBAL_GUID+'")', 4000);
			return;
		}
		$("#status").hide();
		if ( data.tie ) {
			$("#success").html("You tied "+data.displayname);
		} else if ( data.win ) {
			$("#success").html("You beat "+data.displayname);
		} else { 
			$("#success").html("You lost to "+data.displayname);
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
	$.getJSON('<?php echo(sessionize('stats.php')); ?>', function(data) {
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
<input type="submit" id="rock" name="rock" value="Rock"/>
<input type="submit" id="paper" name="paper" value="Paper"/>
<input type="submit" id="scissors" name="scissors" value="Scissors"/>
<?php if ( $instructor ) { ?>
<input type="submit" name="reset" value="Reset"/>
<?php } ?>
</form>
<p id="error" style="color:red"></p>
<p id="success" style="color:green"></p>
<p id="status" style="display:none">
<img id="spinner" src="spinner.gif">
<span id="statustext" style="color:orange"></span>
</p>
<div>
<p><b>Leaderboard</b></p>
<p id="leaders">
</p>
</body>

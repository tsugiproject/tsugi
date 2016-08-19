<?php
// In the top frame, we use cookies for session.
define('COOKIE_SESSION', true);
require_once("config.php");

header('Content-Type: text/html; charset=utf-8');
session_start();

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();

if ( !isset($_SESSION['id']) ) {
    echo('<p>You are not logged in.</p>'."\n");
    $OUTPUT->footer();
    return;
}

if ( !isset($CFG->google_map_api_key) ) {
    echo('<p>There is no MAP api key ($CFG->google_map_api_key)</p>'."\n");
    $OUTPUT->footer();
    return;
}
    
?>
<div class="container">
<div id="map_canvas" style="width:100%; height:400px"></div>
</div>
<?php
$OUTPUT->footerStart();
?>
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&key=<?= $CFG->google_map_api_key ?>"></script>
<script type="text/javascript">
var map;

// https://developers.google.com/maps/documentation/javascript/reference
function initialize_map(data) {
  var myLatlng = new google.maps.LatLng(data.center[0],data.center[1]);
  window.console && console.log("Building map...");

  var myOptions = {
     zoom: 3,
     center: myLatlng,
     mapTypeId: google.maps.MapTypeId.ROADMAP
  }

  map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

  // Add the other points
  window.console && console.log("Loading "+data.points.length+" points");
  for ( var i = 0; i < data.points.length; i++ ) {
    var row = data.points[i];
    if ( i < 3 ) { console.log(row); }
    var newLatlng = new google.maps.LatLng(row[0], row[1]);
    var iconpath = '<?php echo($CFG->staticroot); ?>/img/icons/';
    var icon = row[3] ? 'green-dot.png' : 'green.png';
    var marker = new google.maps.Marker({
      position: newLatlng,
      map: map,
      icon: iconpath + icon,
      title : row[2]
     });
  }
}

$(document).ready(function() {
    $.getJSON('mapjson.php', function(data) {
        initialize_map(data);
    } );

} );
</script>
<?php
$OUTPUT->footerEnd();


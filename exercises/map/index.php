<?php
require_once "../../config.php";

use \Tsugi\Core\LTIX;

// Sanity checks
$LAUNCH = LTIX::requireData();

$p = $CFG->dbprefix;
if ( isset($_POST['lat']) && isset($_POST['lng']) ) {
    if ( abs($_POST['lat']) > 85 || abs($_POST['lng']) > 180 ) {
        $_SESSION['error'] = "Latitude or longitude out of range";
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }
    $sql = "INSERT INTO {$p}sample_map
        (context_id, user_id, lat, lng, updated_at)
        VALUES ( :CID, :UID, :LAT, :LNG, NOW() )
        ON DUPLICATE KEY
        UPDATE lat = :LAT, lng = :LNG, updated_at = NOW()";
    $stmt = $PDOX->prepare($sql);
    $stmt->execute(array(
        ':CID' => $CONTEXT->id,
        ':UID' => $USER->id,
        ':LAT' => $_POST['lat'],
        ':LNG' => $_POST['lng']));
    $_SESSION['success'] = 'Location updated...';
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// Retrieve our row
$stmt = $PDOX->prepare("SELECT lat,lng FROM {$p}sample_map
        WHERE context_id = :CID AND user_id = :UID");
$stmt->execute(array(":CID" => $CONTEXT->id, ":UID" => $USER->id));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
// The default for latitude and longitude
$lat = 42.279070216140425;
$lng = -83.73981015789798;
if ( $row !== false ) {
    if ( abs($row['lat']) <= 90 ) $lat = $row['lat'];
    if ( abs($row['lng']) <= 180 ) $lng = $row['lng'];
}

//Retrieve the other rows
$stmt = $PDOX->prepare("SELECT lat,lng,displayname FROM {$p}sample_map
        JOIN {$p}lti_user
        ON {$p}sample_map.user_id = {$p}lti_user.user_id
        WHERE context_id = :CID AND {$p}sample_map.user_id <> :UID");
$stmt->execute(array(":CID" => $CONTEXT->id, ":UID" => $USER->id));
$points = array();
while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    if ( abs($row['lat']) > 90 ) $row['lat'] = 89;
    if ( abs($row['lng']) > 180 ) $row['lng'] = 179;
    $points[] = array($row['lat']+0.0,$row['lng']+0.0);
}

$OUTPUT->header();
?>
<script src="//maps.googleapis.com/maps/api/js?v=3.exp&sensor=false"></script>
<script type="text/javascript">
var map;

// https://developers.google.com/maps/documentation/javascript/reference
function initialize_map() {
  var myLatlng = new google.maps.LatLng(<?php echo($lat.", ".$lng); ?>);
  window.console && console.log("Building map...");

  var myOptions = {
     zoom: 2,
     center: myLatlng,
     mapTypeId: google.maps.MapTypeId.ROADMAP
  }

  map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

  var marker = new google.maps.Marker({
    draggable: true,
    position: myLatlng,
    map: map,
    title: "Your location"
  });

  google.maps.event.addListener(marker, 'dragend', function (event) {
    // getPosition returns a google.maps.LatLng class for
    // for the dropped marker
    window.console && console.log(this.getPosition());
    // TODO: Fix these next two lines - search the web for a solution
    document.getElementById("latbox").value = 71.0;
    document.getElementById("lngbox").value = -41.0;
  });

  // Add the other points
  window.console && console.log("Loading "+other_points.length+" points");
  for ( var i = 0; i < other_points.length; i++ ) {
    var row = other_points[i];
    // if ( i < 3 ) { alert(row); }
    var newLatlng = new google.maps.LatLng(row[0], row[1]);
    var iconpath = '<?php echo($CFG->staticroot); ?>/img/icons/';
    var icon = 'green.png';
    var marker = new google.maps.Marker({
      position: newLatlng,
      map: map,
      icon: iconpath + icon,
      // TODO: See if you can get the user's displayname here
      title : "TODO: name would be here"
     });
  }
}

// Load the other points
other_points =
<?php echo( json_encode($points));?>
;
</script>
<?php
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
?>
<div id="map_canvas" style="margin: 10px; width:500px; max-width: 100%; height:500px"></div>
<form method="post">
 Latitude: <input size="30" type="text" id="latbox" name="lat"
  <?php echo(' value="'.htmlent_utf8($lat).'" '); ?> >
 Longitude: <input size="30" type="text" id="lngbox" name="lng"
  <?php echo(' value="'.htmlent_utf8($lng).'" '); ?> >
 <button type="submit">Save Location</button>
</form>
<?php
$OUTPUT->footerStart();
?>
<script type="text/javascript">
    initialize_map();
</script>
<?php
$OUTPUT->footerEnd();

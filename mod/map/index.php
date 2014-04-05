<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isset($LTI['role']) && $LTI['role'] == 1 ;

$p = $CFG->dbprefix;
//Retrieve the other rows
$stmt = $pdo->prepare("SELECT lat,lng,displayname FROM {$p}context_map 
		JOIN {$p}lti_user
		ON {$p}context_map.user_id = {$p}lti_user.user_id
		WHERE context_id = :CID AND {$p}context_map.user_id <> :UID");
$stmt->execute(array(":CID" => $LTI['context_id'], ":UID" => $LTI['user_id']));
$points = array();
while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
	$lat = $row['lat']+0;
	$lng = $row['lng']+0;
    if ( abs($lat) > 85 ) $lat = 0;
    if ( abs($lng) > 180 ) $lng = 179.9;
	$points[] = array($lat, $lng, $row['displayname']);
}

// Retrieve our row
$stmt = $pdo->prepare("SELECT lat,lng FROM {$p}context_map 
		WHERE context_id = :CID AND user_id = :UID");
$stmt->execute(array(":CID" => $LTI['context_id'], ":UID" => $LTI['user_id']));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
// The default for latitude and longitude
$lat = 42.279070216140425;
$lng = -83.73981015789798;
if ( $row !== false ) {
    if ( abs($row['lat']) < 85 ) $lat = $row['lat'];
    if ( abs($row['lng']) < 180 ) $lng = $row['lng'];
}


?>
<html><head><title>Map for 
<?php echo($LTI['context_title']); ?>
</title>
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
	window.console && console.log(this.getPosition());
    $.post( '<?php echo(sessionize('update.php')); ?>', 
      { 'lat': this.getPosition().lat(), 'lng' : this.getPosition().lng() },
      function( data ) {
          window.console && console.log(data);
      }
    ).error( function() { 
      window.console && console.log('error'); 
    });
  });

  // Add the other points
  window.console && console.log("Loading "+other_points.length+" points");
  for ( var i = 0; i < other_points.length; i++ ) {
	var row = other_points[i];
	// if ( i < 3 ) { alert(row); }
	var newLatlng = new google.maps.LatLng(row[0], row[1]);
	var iconpath = '<?php echo($CFG->staticroot); ?>/static/img/icons/';
	var icon = 'green.png';
    var marker = new google.maps.Marker({
      position: newLatlng,
      map: map,
      icon: iconpath + icon,
      title : row[2]
     });
  }
}
// Load the other points 
other_points = 
<?php echo( json_encode($points));?> 
;
</script>
</head>
<body style="font-family: sans-serif;" onload="initialize_map();">
<div id="map_canvas" style="margin: 10px; width:95%; height:500px"></div>
<?php

footerStart();
?>
<script type="text/javascript">
$(window).resize(function() {
  window.console && console.log('.resize() called. width='+
    $(window).width()+' height='+$(window).height());
});
</script>
<?php
footerEnd();


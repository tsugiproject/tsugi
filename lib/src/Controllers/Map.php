<?php

namespace Tsugi\Controllers;

use Laravel\Lumen\Routing\Controller;
use Laravel\Lumen\Routing\Router;
use Tsugi\Lumen\Application;
use Tsugi\Util\U;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use \Tsugi\Core\LTIX;

class Map extends Controller {

    const ROUTE = '/map';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix.'/json', function (Request $request) use ($app) {
            return Map::getjson($app);
        });
        $app->router->get($prefix, function (Request $request) use ($app) {
            return Map::getMap($app);
        });
        $app->router->get($prefix.'/', function (Request $request) use ($app) {
            return Map::getMap($app);
        });
    }

    public static function getMap(Application $app)
    {
        global $CFG;
        $tsugi = $app['tsugi'];
        if ( !isset($tsugi->user) ) {
            return self::viewError('<p>You are not logged in.</p>');
        }

        if ( !isset($tsugi->cfg->google_map_api_key) ) {
            return self::viewError('<p>There is no MAP api key ($CFG->google_map_api_key)</p>');
        }

        return self::viewMap();
    }

    public static function getjson(Application $app)
    {
        global $CFG;
        $tsugi = $app['tsugi'];
        if ( !isset($tsugi->user) ) {
            return self::viewError('<p>You are not logged in.</p>');
        }

        if ( !isset($tsugi->cfg->google_map_api_key) ) {
            return self::viewError('<p>There is no MAP api key ($CFG->google_map_api_key)</p>');
        }

        $PDOX = LTIX::getConnection();
        
        $p = $tsugi->cfg->dbprefix;
        $sql = "SELECT U.user_id, P.displayname, P.json 
            FROM {$p}lti_user AS U JOIN {$p}profile AS P
            ON U.profile_id = P.profile_id
            WHERE P.json LIKE '%\"map\":3%' OR P.json LIKE '%\"map\":2%'";
        $rows = $PDOX->allRowsDie($sql);
        $center = false;
        $points = array();
        foreach($rows as $row ) {
            if ( !isset($row['json']) ) continue;
            if ( !isset($row['user_id']) ) continue;
            $json = json_decode($row['json']);
            if ( !isset($json->lat) ) continue;
            if ( !isset($json->lng) ) continue;
            if ( !isset($json->map) ) continue;
            $lat = $json->lat+0;
            $lng = $json->lng+0;
            if ( $lat == 0 && $lng == 0 ) continue;
            if ( abs($lat) > 85 ) $lat = 0;
            if ( abs($lng) > 180 ) $lng = 179.9;
            // 0=not set, 1=show nothing, 2=show location, 3=show location+name
            $map = $json->map+0;
            if ( $map < 2 ) continue;
            $name = $row['displayname'];
            if ( ! isset($_SESSION["admin"]) ) {
                if ( $map < 3 ) $name = '';
            }
            $display = $name;
            $points[] = array($lat, $lng, $display);
            if ( isset($_SESSION['id']) && $_SESSION['id'] == $row['user_id'] ) {
                $center = array($lat,$lng);
            }
        }
        if ( $center === false ) $center = array(42.279070216140425, -83.73981015789798);
        $retval = array('center' => $center, 'points' => $points );
        return response()->json($retval);
    }

    public static function viewMap()
    {
        global $OUTPUT, $CFG;

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $menu = false;
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        $rest_path = U::rest_path();
?>
<div class="container">
<div id="map_canvas" style="width:100%; height:400px"></div>
</div>
<p id="counter" style="text-align:center; padding-top:10px; display:none">
</p>
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
    var iconpath = '<?= $CFG->staticroot ?>/img/icons/';
    var icon = row[3] ? 'green-dot.png' : 'green.png';
    var marker = new google.maps.Marker({
      position: newLatlng,
      map: map,
      icon: iconpath + icon,
      title : row[2]
     });
  }
  if ( data.points.length == 1 ) {
    $("#counter").text('There is one user on the map.');
    $("#counter").show();
  }
  if ( data.points.length > 1 ) {
    $("#counter").text('There are '+data.points.length+' users who have placed themselves on the map.');
    $("#counter").show();
  }
}

$(document).ready(function() {
    $.getJSON('<?= $rest_path->current ?>/json', function(data) {
        initialize_map(data);
    } );

} );
</script>
<?php
        $OUTPUT->footerEnd();
    }

    public static function viewError($msg)
    {
        global $OUTPUT, $CFG;

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $menu = false;
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        echo("<h1>Error</h1>\n");
        echo($msg);
        $OUTPUT->footer();
    }
}

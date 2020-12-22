<?php

namespace Tsugi\Controllers;

use Laravel\Lumen\Routing\Controller;
use Laravel\Lumen\Routing\Router;
use Tsugi\Lumen\Application;
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
            return view('Error',
                array('error' => '<p>You are not logged in.</p>')
                );
        }

        if ( !isset($tsugi->cfg->google_map_api_key) ) {
            return view('Error',
                array('error' => '<p>There is no MAP api key ($CFG->google_map_api_key)</p>')
                );
        }

        return view('Map');
    }

    public static function getjson(Application $app)
    {
        global $CFG;
        $tsugi = $app['tsugi'];
        if ( !isset($tsugi->user) ) {
            return view('Error',
                array('error' => '<p>You are not logged in.</p>')
                );
        }

        if ( !isset($tsugi->cfg->google_map_api_key) ) {
            return view('Error',
                array('error' => '<p>There is no MAP api key ($CFG->google_map_api_key)</p>')
                );
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
}

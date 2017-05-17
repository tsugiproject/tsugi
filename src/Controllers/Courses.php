<?php

namespace Koseu\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use \Tsugi\Core\LTIX;

class Courses {

    const ROUTE = '/courses';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->get($prefix.'/json', 'Koseu\\Controllers\\Courses::getjson');
    }

    public function getjson(Request $request, Application $app)
    {
        global $CFG;
        $tsugi = $app['tsugi'];
        if ( !isset($tsugi->user)) {
        // if ( !isset($_SESSION['id'])) {
            return $app['twig']->render('@Tsugi/Error.twig',
                array('error' => '<p>You are not logged in.</p>')
                );
        }

        $PDOX = LTIX::getConnection();
        $p = $tsugi->cfg->dbprefix;
        
        $row = $PDOX->rowDie("SELECT profile_id FROM {$p}lti_user WHERE user_id = :UID;",
            array(':UID' => $tsugi->user->id)
        );

        if ( $row === false || ! isset($row['profile_id']) ) {
            echo(json_encode(array("error" => "No profile_id")));
            return;
        }

        $sql = "SELECT P.profile_id, U.user_id, U.email, C.context_id, C.title
            FROM profile AS P 
            JOIN lti_user AS U ON P.profile_id = U.profile_id
            JOIN lti_membership AS M ON U.user_id = M.user_id
            JOIN lti_context AS C ON M.context_id = C.context_id
            WHERE P.profile_id = :PID";

        $rows = $PDOX->allRowsDie($sql, array(':PID' => $row['profile_id']));
        return $app->json($rows);
    }
}

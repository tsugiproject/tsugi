<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Core\LTIX;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Tsugi\UI\Table;

class Grades extends Tool {

    const ROUTE = '/grades';
    const NAME = 'Grades';
    const REDIRECT = 'tsugi_controllers_grades';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Grades@index');
        $app->router->get($prefix.'/', 'Grades@index');
        $app->router->get('/'.self::REDIRECT, 'Grades@index');
        $app->router->get($prefix.'/class', 'Grades@classView');
        $app->router->get($prefix.'/analytics', 'Grades@analytics');
    }

    /**
     * Get context title from session or database
     * 
     * @param int $context_id
     * @return string
     */
    private function getContextTitle($context_id) {
        global $CFG, $PDOX;
        
        $context_title = U::get($_SESSION, 'context_title');
        if ( ! $context_title ) {
            $context_info = $PDOX->rowDie(
                "SELECT title FROM {$CFG->dbprefix}lti_context WHERE context_id = :CID",
                array(':CID' => $context_id)
            );
            $context_title = $context_info ? $context_info['title'] : 'Unknown Context';
        }
        return $context_title;
    }

    /**
     * Get user info for display
     * 
     * @param int $user_id
     * @return array|false User info array or false if not found
     */
    private function getUserInfo($user_id) {
        global $CFG, $PDOX;
        
        return $PDOX->rowDie(
            "SELECT user_id, displayname, email, user_key 
             FROM {$CFG->dbprefix}lti_user 
             WHERE user_id = :UID",
            array(':UID' => $user_id)
        );
    }

    /**
     * Process grade rows: retrieve server grades, sync, and format for display
     * 
     * @param array $rows Grade rows from database
     * @param bool $force Force retrieval of server grades
     * @return array Processed rows ready for display
     */
    private function processGradeRows($rows, $force = false) {
        $RETRIEVE_INTERVAL = 3600; // One Hour
        $newrows = array();
        $retrieval_debug = '';
        
        foreach ( $rows as $row ) {
            $newrow = $row;
            unset($newrow['result_id']);
            unset($newrow['diff_in_seconds']);
            unset($newrow['time_now']);
            unset($newrow['sourcedid']);
            unset($newrow['service']);
            unset($newrow['result_url']);
            $newrow['note'] = '';
            
            if ( $row['grade'] <= 0.0 ) {
                $newrows[] = $newrow;
                continue;
            }

            $diff = $row['diff_in_seconds'];
            $remote_grade = U::get($row,'result_url') || (U::get($row,'sourcedid') && U::get($row,'service'));

            $retrieval_debug .= "\n";
            if ( U::get($row,'result_url') ) $retrieval_debug .= "result_url=".U::get($row,'result_url')."\n";
            if ( U::get($row,'service') ) $retrieval_debug .= "service=".U::get($row,'service')."\n";
            if ( U::get($row,'sourcedid') ) $retrieval_debug .= "sourcedid=".U::get($row,'sourcedid')."\n";

            // Retrieve server grade if needed
            if ( $remote_grade && ( $force || !isset($row['retrieved_at']) || $row['retrieved_at'] < $row['updated_at'] ||
                ! U::get($row, 'server_grade') || U::get($row, 'grade') != U::get($row, 'server_grade') ||
                $diff > $RETRIEVE_INTERVAL ) ) {

                $server_grade = LTIX::gradeGet($row);
                $retrieval_debug .= "Retrieved server grade: ".$server_grade."\n";
                
                if ( is_string($server_grade) && U::strlen(trim($server_grade)) == 0 ) $server_grade = 0.0;
                if ( is_string($server_grade)) {
                    $msg = "result_id=".$row['result_id']."\n".
                        "grade=".$row['grade']." updated=".$row['updated_at']."\n".
                        "server_grade=".$row['server_grade']." retrieved=".$row['retrieved_at']."\n".
                        "error=".$server_grade;

                    error_log("Problem Retrieving Grade: ".session_id()."\n".$msg."\n".
                      "service=".U::get($row,'service')." sourcedid=".U::get($row,'sourcedid'));
                    
                    $newrow['note'] = "Problem Retrieving Server Grade: ".$server_grade;
                    $newrows[] = $newrow;
                    continue;
                } else {
                    $newrow['note'] .= ' Server grade retrieved: '.$server_grade;
                }
                $row['server_grade'] = $server_grade;
                $newrow['server_grade'] = $server_grade;
                $newrow['retrieved_at'] = $row['time_now'];
                $row['retrieved_at'] = $row['time_now'];
            }

            // Check if we need to update the server_grade
            if ( $remote_grade && $row['server_grade'] < $row['grade'] ) {
                error_log("Patching server grade: ".session_id()." result_id=".$row['result_id']."\n".
                        "grade=".$row['grade']." updated=".$row['updated_at']."\n".
                        "server_grade=".$row['server_grade']." retrieved=".$row['retrieved_at']);

                $debug_log = array();
                $retrieval_debug .= "Sending Tsugi grade: ".$row['grade']."\n";
                $status = LTIX::gradeSend($row['grade'], $row, $debug_log);
                $retrieval_debug .= "Send status: ".$status."\n";

                if ( $status === true ) {
                    $server_grade = LTIX::gradeGet($row);
                    $retrieval_debug .= "Re-retrieved server grade: ".$server_grade."\n";
                    if ( is_string($server_grade) && U::strlen(trim($server_grade)) == 0 ) $server_grade = 0.0;
                    $newrow['server_grade'] = $server_grade;
                    $row['server_grade'] = $server_grade;
                    if ( $server_grade != $row['grade'] ){
                        $newrow['note'] .= " Grade re-send mismatch.";
                    } else {
                        $newrow['note'] .= " Grade re-sent and checked.";
                    }
                } else {
                    $msg = "result_id=".$row['result_id']."\n".
                        "grade=".$row['grade']." updated=".$row['updated_at']."\n".
                        "server_grade=".$row['server_grade']." retrieved=".$row['retrieved_at']."\n".
                        "error=".$status;

                    error_log("Problem Updating Grade: ".session_id()."\n".$msg."\n".
                      "service=".U::get($row,'service')." sourcedid=".U::get($row,'sourcedid'));
                    
                    $newrow['note'] .= " Problem Updating Server Grade";
                }
            }

            $newrows[] = $newrow;
        }
        
        // Format grades as percentages
        $showrows = array();
        foreach ( $newrows as $row ) {
            $g = $row['grade'] * 100.0;
            $row['grade'] = sprintf("%1.1f",$g);
            $g = $row['server_grade'] * 100.0;
            $row['server_grade'] = sprintf("%1.1f",$g);
            $showrows[] = $row;
        }
        
        return array('rows' => $showrows, 'debug' => $retrieval_debug);
    }

    /**
     * Main grades index - shows student's own grades or instructor viewing specific student
     */
    public function index(Request $request)
    {
        global $CFG, $OUTPUT, $PDOX;
        
        $this->requireAuth();
        
        LTIX::getConnection();
        
        $context_id = $_SESSION['context_id'];
        $user_id = $_SESSION['id'];
        $is_instructor = $this->isInstructor();
        
        $p = $CFG->dbprefix;
        
        // Get user_id - students see their own, instructors can view specific students
        $view_user_id = $user_id;
        $user_info = false;
        if ( $is_instructor && $request->query->has('user_id') ) {
            $view_user_id = $request->query->get('user_id') + 0;
            $user_info = $this->getUserInfo($view_user_id);
        }
        
        $context_title = $this->getContextTitle($context_id);
        
        // Query for user's grades
        $query_parms = array(":UID" => $view_user_id, ":CID" => $context_id);
        $searchfields = array("L.title", "R.grade", "R.note", "R.updated_at", "retrieved_at");
        $user_sql =
            "SELECT R.result_id AS result_id, L.title as title, R.grade AS grade, R.note AS note,
                R.updated_at as updated_at, server_grade, retrieved_at, sourcedid, result_url, service_key as service,
                TIMESTAMPDIFF(SECOND,retrieved_at,NOW()) as diff_in_seconds, NOW() AS time_now
            FROM {$p}lti_result AS R
            JOIN {$p}lti_link as L ON R.link_id = L.link_id
            LEFT JOIN {$p}lti_service AS S ON R.service_id = S.service_id
            WHERE R.user_id = :UID AND L.context_id = :CID AND R.grade IS NOT NULL";
        
        // Build menu
        $menu = false;
        if ( $is_instructor ) {
            $menu = new \Tsugi\UI\MenuSet();
            $tool_home = $this->toolHome(self::ROUTE);
            $class_url = $tool_home . '/class';
            $menu->addLeft(__('View Class Grades'), $class_url);
            
            $force_url = U::add_url_parm($tool_home, "force", "yes");
            $menu->addRight(__('Force Reload'), $force_url);
        }
        
        $force = $request->query->has('force') && $request->query->get('force') == 'yes';
        
        // Record learner analytics
        $this->lmsRecordLaunchAnalytics(self::ROUTE, 'Grade Book');
        
        // Check if user is instructor/admin for analytics button
        $is_admin = $this->isAdmin();
        $show_analytics = $is_instructor || $is_admin;
        
        // Get grades with pagination
        $DEFAULT_PAGE_LENGTH = 15;
        $newsql = Table::pagedQuery($user_sql, $query_parms, $searchfields);
        $rows = $PDOX->allRowsDie($newsql, $query_parms);
        
        // Process grades (retrieve server grades, sync, format)
        $processed = $this->processGradeRows($rows, $force);
        $showrows = $processed['rows'];
        $retrieval_debug = $processed['debug'];
        
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav($menu);
        $OUTPUT->flashMessages();
        
        // Show analytics button if instructor/admin
        if ( $show_analytics ) {
            $tool_home = $this->toolHome(self::ROUTE);
            $analytics_url = $tool_home . '/analytics';
            echo('<span style="position: fixed; right: 10px; top: 75px; z-index: 999; background-color: white; padding: 2px;"><a href="'.$analytics_url.'" class="btn btn-default"><span class="glyphicon glyphicon-signal"></span> Analytics</a></span>');
        }
        
        echo("<p>Class: ".htmlspecialchars($context_title)."</p>\n");
        
        if ( $user_info !== false ) {
            echo("<p>Results for ".htmlspecialchars($user_info['displayname'])."</p>\n");
        }
        
        $searchfields = array();
        Table::pagedTable($showrows, $searchfields, false);
        
        // Display user identity from session
        $identity = __("Logged in as: ").U::get($_SESSION, 'user_key', '');
        if ( U::get($_SESSION, 'email') ) {
            $identity .= ' ' . htmlentities($_SESSION['email']);
        }
        if ( U::get($_SESSION, 'displayname') ) {
            $identity .= ' ' . htmlentities($_SESSION['displayname']);
        }
        echo("<p>".$identity."</p>");
        
        if ( U::strlen(trim($retrieval_debug)) > 0 && $is_instructor ) {
            echo("<pre>\n");
            echo(htmlentities($retrieval_debug));
            echo("</pre>\n");
        }
        
        $OUTPUT->footer();
    }

    /**
     * Instructor class view - shows all students or grades for a specific link
     */
    public function classView(Request $request)
    {
        global $CFG, $OUTPUT, $PDOX;
        
        $this->requireInstructor();
        
        LTIX::getConnection();
        
        $context_id = $_SESSION['context_id'];
        $p = $CFG->dbprefix;
        
        $link_id = 0;
        if ( $request->query->has('link_id') ) {
            $link_id = $request->query->get('link_id') + 0;
        }
        
        $link_info = false;
        if ( $link_id > 0 ) {
            $link_info = $PDOX->rowDie(
                "SELECT link_id, title FROM {$p}lti_link 
                 WHERE link_id = :LID AND context_id = :CID",
                array(':LID' => $link_id, ':CID' => $context_id)
            );
        }
        
        $context_title = $this->getContextTitle($context_id);
        
        // Build query based on whether viewing specific link or all students
        $query_parms = array();
        $searchfields = array();
        $orderfields = array();
        $class_sql = false;
        $summary_sql = false;
        
        if ( $link_id > 0 ) {
            $query_parms = array(":LID" => $link_id);
            $searchfields = array("R.user_id", "displayname", "grade", "R.updated_at", "server_grade", "retrieved_at");
            $class_sql =
                "SELECT R.user_id AS user_id, U.displayname, R.grade,
                    R.updated_at as updated_at, R.server_grade, R.retrieved_at
                FROM {$p}lti_result AS R
                INNER JOIN {$p}lti_user as U ON R.user_id = U.user_id
                WHERE R.link_id = :LID AND R.grade IS NOT NULL AND R.deleted = 0";
        } else {
            $query_parms = array(":CID" => $context_id);
            $orderfields = array("R.user_id", "displayname", "email", "user_key", "grade_count");
            $searchfields = array("R.user_id", "displayname", "email", "user_key");
            $summary_sql =
                "SELECT R.user_id AS user_id, U.displayname, U.email, COUNT(R.grade) AS grade_count, U.user_key
                FROM {$p}lti_link as L
                INNER JOIN {$p}lti_result AS R ON L.link_id = R.link_id AND R.grade IS NOT NULL AND R.deleted = 0
                INNER JOIN {$p}lti_user as U ON R.user_id = U.user_id
                WHERE L.context_id = :CID
                GROUP BY R.user_id, U.displayname, U.email, U.user_key";
        }
        
        // Get list of links for menu
        $lstmt = $PDOX->queryDie(
            "SELECT DISTINCT L.title as title, L.link_id AS link_id
            FROM {$p}lti_link AS L JOIN {$p}lti_result as R
                ON L.link_id = R.link_id AND R.grade IS NOT NULL
            WHERE L.context_id = :CID",
            array(":CID" => $context_id)
        );
        $links = $lstmt->fetchAll();
        
        // Build menu
        $menu = new \Tsugi\UI\MenuSet();
        $tool_home = $this->toolHome(self::ROUTE);
        $menu->addLeft(__('View My Grades'), $tool_home);
        
        if ( $links !== false && count($links) > 0 ) {
            $submenu = new \Tsugi\UI\Menu();
            foreach($links as $link) {
                $link_url = $tool_home . '/class?link_id='.$link['link_id'];
                $submenu->addLink($link['title'], $link_url);
            }
            $menu->addRight(__('Activity Detail'), $submenu);
        }
        
        // Record analytics (instructors don't get recorded, but we can still track the page)
        $this->lmsRecordLaunchAnalytics(self::ROUTE, 'Grade Book');
        
        // Check if user is instructor/admin for analytics button
        $is_admin = $this->isAdmin();
        $show_analytics = true; // Always show for instructors
        
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav($menu);
        $OUTPUT->flashMessages();
        
        // Show analytics button
        if ( $show_analytics ) {
            $analytics_url = $tool_home . '/analytics';
            echo('<span style="position: fixed; right: 10px; top: 75px; z-index: 999; background-color: white; padding: 2px;"><a href="'.$analytics_url.'" class="btn btn-default"><span class="glyphicon glyphicon-signal"></span> Analytics</a></span>');
        }
        
        echo("<p>Class: ".htmlspecialchars($context_title)."</p>\n");
        if ( $link_info ) echo("<p>Link: ".htmlspecialchars($link_info["title"])."</p>\n");
        
        // Build detail URL for pagedAuto
        $detail_url = U::reconstruct_query($tool_home . '/class', array("detail" => ""));
        
        if ( $summary_sql !== false ) {
            Table::pagedAuto($summary_sql, $query_parms, $searchfields, $orderfields, $detail_url);
        }
        
        if ( $class_sql !== false ) {
            Table::pagedAuto($class_sql, $query_parms, $searchfields, $searchfields, $detail_url);
        }
        
        $OUTPUT->footer();
    }

    /**
     * Analytics view for grades
     */
    public function analytics(Request $request)
    {
        return $this->showAnalytics(self::ROUTE, 'Grade Book');
    }
}

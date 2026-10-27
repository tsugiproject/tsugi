<?php

namespace Tsugi\Controllers;

use Tsugi\Lumos\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\ReqScope;
use \Tsugi\UI\Lessons;
use \Tsugi\Google\GoogleClassroom as ClassroomApi;
use \Tsugi\Services\Google\ClassroomQueue;

/**
 * Google Classroom pages. Public URLs stay /gclass/login, /gclass/assign,
 * /gclass/launch, and /gclass/share.
 */
class GoogleClassroom extends Tool {

    const ROUTE = '/gclass';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        // The router trims a trailing slash, so /gclass/login/ is this same route.
        $app->router->get($prefix.'/login', 'GoogleClassroom@login');
        $app->router->get($prefix.'/assign', 'GoogleClassroom@assign');
        $app->router->get($prefix.'/share', 'GoogleClassroom@share');
        $app->router->get($prefix.'/launch', 'GoogleClassroom@launch');
        $app->router->get($prefix.'/launch/{resource}', 'GoogleClassroom@launch');
        $app->router->get($prefix.'/courses', 'GoogleClassroom@courses');
        $app->router->get($prefix.'/course/{context}', 'GoogleClassroom@course');
        $app->router->post($prefix.'/course/{context}/send', 'GoogleClassroom@sendQueue');
        $app->router->post($prefix.'/course/{context}/reset', 'GoogleClassroom@resetQueue');
        $app->router->get($prefix.'/course/{context}/reconnect', 'GoogleClassroom@reconnect');
        $app->router->get($prefix.'/use-account', 'GoogleClassroom@useAccount');
    }

    /**
     * OAuth return URL for a Classroom token. Same path Google has registered.
     */
    public function login(Request $request) {
        global $CFG;

        LTIX::getConnection();
        LTIX::session_start();

        if ( ! ReqScope::isLoggedInLegacy() ) {
            $back = $CFG->wwwroot.'/gclass/login';
            if ( isset($_GET['consent']) && (string) $_GET['consent'] === '1' ) {
                $back .= '?consent=1';
            }
            Login::setReturnUrl($back);
            return new RedirectResponse(Login::loginUrl());
        }

        unset($_SESSION['gc_count']);

        $gate = $this->sanityCheck();
        if ( $gate ) {
            return $gate;
        }

        $accessTokenStr = ClassroomApi::retrieve_instructor_token();
        $googleEmail = '';
        $results = array();

        try {
            $client = $this->clientOrRedirect($accessTokenStr);
            if ( $client instanceof Response ) {
                return $client;
            }
            $service = new \Google_Service_Classroom($client);
            $results = $this->teacherCourses($service);
            $googleEmail = $this->googleEmail($service);
            $newAccessTokenStr = json_encode($client->getAccessToken(), true);

            $_SESSION['gc_token'] = LTIX::encrypt_secret($newAccessTokenStr);
        } catch (\Exception $e) {
            ClassroomApi::destroy_instructor_token();
            $redirect = $this->clientOrRedirect(false);
            if ( $redirect instanceof Response ) {
                return $redirect;
            }
            return new Response('', 200);
        }

        $_SESSION['gc_count'] = count($results);
        $panel = $this->takePanelReturn();
        if ( $panel ) {
            if ( count($results) < 1 ) {
                U::flashError($this->courseSummary($googleEmail, $results));
            } else {
                U::flashSuccess($this->courseSummary($googleEmail, $results));
            }
            return new RedirectResponse($panel);
        }

        // $CFG->lessons defaults to false, so isset() is always true.
        $lessonsPath = (is_string($CFG->lessons) && trim($CFG->lessons) !== '') ? $CFG->lessons : false;
        if ( ! $lessonsPath || ! isset($CFG->apphome) || ! $CFG->apphome ) {
            if (count($results) == 0) {
                U::flashError($this->courseSummary($googleEmail, $results));
                return $this->loginRedirect();
            } else {
                U::flashSuccess($this->courseSummary($googleEmail, $results));
                $_SESSION['gc_count'] = count($results);
                return $this->loginRedirect();
            }
        }

        $l = new Lessons($lessonsPath);
        $firstmodule = false;
        if (isset($l->lessons->modules[0]->anchor) ) {
            $firstmodule = $l->lessons->modules[0]->anchor;
        }

        if (count($results) == 0) {
            U::flashError($this->courseSummary($googleEmail, $results));
            return $this->loginRedirect();
        }

        Login::takeReturnUrl();
        U::flashSuccess($this->courseSummary($googleEmail, $results).
            ' Use the icon by each link to install links / assignments into your Google Classroom.');
        $_SESSION['gc_count'] = count($results);
        return new RedirectResponse($CFG->apphome.'/lessons/'.$firstmodule.'?gclass=yes');
    }

    /**
     * Pick a Classroom course and create the coursework link.
     */
    public function assign(Request $request) {
        global $CFG, $OUTPUT, $PDOX;

        $PDOX = LTIX::getConnection();
        LTIX::session_start();

        $gate = $this->sanityCheck();
        if ( $gate ) {
            return $gate;
        }

        $endpoint = U::get($_GET,'lti');
        $endpoint_title = U::get($_GET, 'title', 'External Tool');
        if ( ! $endpoint ) {
            if ( ! U::get($_GET,'rlid') ) {
                die_with_error_log('Error: rlid parameter is required');
            }

            $l = \Tsugi\Core\Manifest::currentLessons();
            $lti = $l ? $l->getLtiByRlid($_GET['rlid']) : null;
            if ( ! $lti ) {
                die_with_error_log('Invalid resource link id');
            }
            $launch = \Tsugi\Services\Lessons\LessonsNormalize::launchUrlForItem($lti);
            $endpoint = U::add_url_parm($launch, 'inherit', $lti->resource_link_id);
            $endpoint_title = $lti->title;
        }

        $user_id = ReqScope::loggedInUserIdLegacy();
        $key_id = $_SESSION[TSUGI_SESSION_LTI]['key_id'];

        $accessTokenStr = ClassroomApi::retrieve_instructor_token();
        if ( ! $accessTokenStr ) {
            die_with_error_log('Error: Access Token not in session');
        }

        $client = $this->clientOrRedirect($accessTokenStr);
        if ( $client instanceof Response ) {
            return $client;
        }

        $service = new \Google_Service_Classroom($client);
        $courses = $this->teacherCourses($service);
        $googleEmail = $this->googleEmail($service);

        $gc_course = false;
        $gc_title = false;
        $gc_url = false;
        $installError = false;
        if ( U::get($_GET,'gc_course') ) {
            foreach( $courses as $course ) {
                if ( $course->getId() == $_GET['gc_course'] ) {
                    $gc_course = $_GET['gc_course'];
                    $gc_title = $course->getName();
                    $gc_url = $course->getAlternateLink();
                    break;
                }
            }
            if ( ! $gc_course ) {
                $installError = 'That class is not one this Google account teaches.';
            }
        }

        if ( $gc_course ) {
            // secret:$gc_course:$user_id:secret
            $plain = $CFG->google_classroom_secret.$gc_course.ReqScope::loggedInUserIdLegacy().$CFG->google_classroom_secret;
            $user_mini_sig = lti_sha256($plain);
            $user_mini_sig = substr($user_mini_sig,0,6);
            $context_url = $gc_course . ':' . $user_mini_sig;
            $context_key = 'gclass:' . $context_url;
            $context_sha256 = lti_sha256($context_key);

            $row = $PDOX->rowDie(
                "SELECT * FROM {$CFG->dbprefix}lti_context
                    WHERE context_sha256 = :context LIMIT 1",
                array(':context' => $context_sha256)
            );

            $context_id = false;
            $gc_secret = false;
            if ( $row != false ) {
                if ( $row['user_id'] != ReqScope::loggedInUserIdLegacy() ) {
                    die_with_error_log('Error: Incorrect course ownership');
                }
                $context_id = $row['context_id'];
                $gc_secret = $row['gc_secret'];
                if ( $row['title'] != $gc_title ) {
                    $sql = "UPDATE {$CFG->dbprefix}lti_context
                        SET title = :title, updated_at=NOW() WHERE context_id = :CID";
                    $PDOX->queryDie($sql,
                        array(':title' => $gc_title, ':CID' => $context_id)
                    );
                }
            }

            if ( ! $context_id ) {
                $gc_secret = bin2hex( openssl_random_pseudo_bytes( 128/2 ) ) ;
                $sql = "INSERT INTO {$CFG->dbprefix}lti_context
                    ( context_key, context_sha256, title, key_id, gc_secret, user_id, created_at, updated_at )
                    VALUES
                    ( :context_key, :context_sha256, :title, :key_id, :GCS, :user_id, NOW(), NOW() )";
                $PDOX->queryDie($sql, array(
                    ':context_key' => $context_key,
                    ':context_sha256' => $context_sha256,
                    ':title' => $gc_title,
                    ':GCS' => $gc_secret,
                    ':user_id' => $user_id,
                    ':key_id' => $key_id));
                $context_id = $PDOX->lastInsertId();
            }

            $sql = "INSERT INTO {$CFG->dbprefix}lti_membership
                ( context_id, user_id, role, created_at, updated_at ) VALUES
                ( :context_id, :user_id, :role, NOW(), NOW() )
                ON DUPLICATE KEY UPDATE role=:role, updated_at=NOW()";
            $PDOX->queryDie($sql, array(
                ':context_id' => $context_id,
                ':user_id' => $user_id,
                ':role' => LTIX::ROLE_INSTRUCTOR));

            $resource_link_id_tmp = uniqid();
            $sql = "INSERT INTO {$CFG->dbprefix}lti_link
                ( link_key, link_sha256, title, context_id, path, created_at, updated_at ) VALUES
                    ( :link_key, :link_sha256, :title, :context_id, :path, NOW(), NOW() )";
            $PDOX->queryDie($sql, array(
                ':link_key' => $resource_link_id_tmp,
                ':link_sha256' => lti_sha256($resource_link_id_tmp),
                ':title' => $endpoint_title,
                ':context_id' => $context_id,
                ':path' => $endpoint
            ));
            $link_id = $PDOX->lastInsertId();

            // secret:gc_course:mini-sig-user:link_id:secret
            $plain = $gc_secret.$context_url.$link_id.$gc_secret;
            $link_mini_sig = lti_sha256($plain);
            $link_mini_sig = substr($link_mini_sig,0,6);

            $launch_url = $CFG->wwwroot . '/gclass/launch/' .
            $context_url . ':' . $link_id . ':' . $link_mini_sig;

            $link = new \Google_Service_Classroom_Link();
            $link->setTitle($endpoint_title);
            $link->setUrl($launch_url);
            if ( isset($CFG->google_classroom_logo) ) {
                $link->setThumbnailUrl($CFG->google_classroom_logo);
            }
            $materials = new \Google_Service_Classroom_Material();
            $materials->setLink($link);

            $cw = new \Google_Service_Classroom_CourseWork();
            $cw->setTitle($endpoint_title);
            $cw->setMaterials(array($materials));
            $cw->setMaxpoints(100);
            $cw->setWorkType("ASSIGNMENT");
            $cw->setState("PUBLISHED");

            $courseWorkService = $service->courses_courseWork;
            try {
                $courseWorkObject = $courseWorkService->create($gc_course, $cw);
            } catch (\Throwable $e) {
                $PDOX->queryDie(
                    "DELETE FROM {$CFG->dbprefix}lti_link WHERE link_id = :LID",
                    array(':LID' => $link_id)
                );
                $installError = ClassroomQueue::scrub($e->getMessage());
                if ( strlen($installError) > 400 ) {
                    $installError = substr($installError, 0, 400);
                }
                $gc_course = false;
            }
            if ( $gc_course ) {
                $resource_link_id = $courseWorkObject->id;
                ClassroomQueue::note(
                    (int) $context_id,
                    (int) $link_id,
                    (int) $user_id,
                    ClassroomQueue::KIND_COURSEWORK,
                    true,
                    'Created coursework '.$resource_link_id
                );

                $sql = "UPDATE {$CFG->dbprefix}lti_link
                    SET link_key = :link_key, link_sha256=:link_sha256 WHERE link_id = :LID";
                $PDOX->queryDie($sql, array(
                    ':link_key' => $resource_link_id,
                    ':link_sha256' => lti_sha256($resource_link_id),
                    ':LID' => $link_id
                ));

                $OUTPUT->header();
                $OUTPUT->bodyStart();
                echo("<center><p>\n");
                echo(_m('Success installing').'<br/>');
                echo('<strong>');
                echo(htmlentities($endpoint_title));
                echo('</strong>');
                if ( $gc_title ) {
                    echo('<br/>'._m('in').' ');
                    echo(htmlentities($gc_title));
                }
                echo("</p>\n");
                if ( $gc_url ) {
                    $launch = filter_var($gc_url, FILTER_SANITIZE_URL);
                    echo("<p><a href=".$launch.' target=_blank>');
                    echo(_m('Go to Classroom site'));
                    echo("</p>\n");
                }
                $queueUrl = $CFG->wwwroot.'/gclass/course/'.(int) $context_id;
                echo('<p><a href="'.htmlspecialchars($queueUrl, ENT_QUOTES, 'UTF-8').'">');
                echo(_m('Open the Classroom queue'));
                echo("</a></p>\n");
                $OUTPUT->footer();
                return new Response('', 200);
            }
        }

        $switchNext = $CFG->wwwroot.'/gclass/assign?'.http_build_query(array(
            'rlid' => U::get($_GET,'rlid'),
            'lti' => U::get($_GET,'lti'),
            'title' => U::get($_GET,'title'),
        ));
        $switchUrl = $CFG->wwwroot.'/gclass/use-account?next='.rawurlencode($switchNext);

        $assignTitle = $endpoint_title;
        include __DIR__.'/templates/Google/assign.inc.php';
        return new Response('', 200);
    }

    /**
     * Student launch stored on the Classroom assignment.
     *
     * @param string|null $resource course:sig:link:sig
     */
    public function launch(Request $request, $resource = null) {
        global $CFG, $PDOX;

        $agent = $request->headers->get('User-Agent');
        if ( isset($CFG->google_classroom_logo) && $agent && stripos($agent,'Google Web Preview') !== false ) {
            echo('<center><img src="'.$CFG->google_classroom_logo.'"></center>'."\n");
            return new Response('', 200);
        }

        if ( session_id() == '' ) {
            session_start();
        }

        $pieces = $this->launchPieces($resource);
        if ( $pieces === null ) {
            U::flashError('Invalid resource id format');
            return new RedirectResponse($CFG->apphome);
        }

        $gc_course = $pieces[0];
        $user_mini_sig = $pieces[1];
        $link_id = $pieces[2];
        $link_mini_sig = $pieces[3];

        $context_url = $gc_course . ':' . $user_mini_sig;
        $context_key = 'gclass:' . $context_url;
        $context_sha256 = lti_sha256($context_key);

        if ( ! ReqScope::isLoggedInLegacy() ) {
            Login::setReturnUrl($this->currentRequestPath($request));
            return new RedirectResponse(Login::loginUrl());
        }

        $user_id = ReqScope::loggedInUserIdLegacy();
        $user_email = $_SESSION['email'];
        $user_displayname = $_SESSION['displayname'];
        $user_key = $_SESSION['user_key'];
        $user_avatar = U::get($_SESSION,'avatar', null);

        $PDOX = LTIX::getConnection();

        $sql = "SELECT gc_secret, O.user_id AS owner_id, O.email AS owner_email,
                role, role_override,
                C.context_id AS context_id, C.title AS context_title,
                L.path AS path, link_key, L.link_id as link_id, L.title AS link_title,
                result_id, gc_submit_id,
                M.json AS membership_json,
                K.key_id AS key_id, K.secret AS key_secret, K.key_key AS key_key
            FROM {$CFG->dbprefix}lti_context AS C
            JOIN {$CFG->dbprefix}lti_user AS O
                ON C.user_id = O.user_id
            JOIN {$CFG->dbprefix}lti_link AS L
                ON C.context_id = L.context_id
            JOIN {$CFG->dbprefix}lti_key AS K
                ON C.key_id = K.key_id
            LEFT JOIN {$CFG->dbprefix}lti_result AS R
                ON L.link_id = R.link_id AND R.user_id = :UID
            LEFT JOIN {$CFG->dbprefix}lti_membership AS M
                ON C.context_id = M.context_id AND M.user_id = :UID
            WHERE context_sha256 = :context_sha256 AND context_key = :context_key
            AND L.link_id = :LID
            LIMIT 1";

        $row = $PDOX->rowDie($sql,
            array(
                ':context_sha256' => $context_sha256,
                ':context_key' => $context_key,
                ':UID' => $user_id,
                ':LID' => $link_id )
            );

        if ( ! $row ) {
            U::flashError('Could not lookup resource id');
            return new RedirectResponse($CFG->apphome);
        }

        $gc_secret = $row['gc_secret'];
        $path = $row['path'];
        $owner_id = $row['owner_id'];
        $gc_coursework = $row['link_key'];
        $owner_email = $row['owner_email'];
        $context_id = $row['context_id'];
        $context_title = $row['context_title'];
        $key_id = $row['key_id'];
        $key_key = $row['key_key'];
        $key_secret = $row['key_secret'];
        $gc_submit_id = $row['gc_submit_id'];
        $result_id = $row['result_id'];
        $link_title = $row['link_title'];
        $role = $row['role'];
        $membership_json = $row['membership_json'];
        if ( $row['role_override'] > $row['role'] ) {
            $role = $row['role_override'];
        }

        $plain = $CFG->google_classroom_secret.$gc_course.$owner_id.$CFG->google_classroom_secret;
        $user_mini_check = lti_sha256($plain);
        $user_mini_check = substr($user_mini_check,0,6);

        $plain = $gc_secret.$context_url.$link_id.$gc_secret;
        $link_mini_check = lti_sha256($plain);
        $link_mini_check = substr($link_mini_check,0,6);

        if ( $link_mini_check != $link_mini_sig || $user_mini_check != $user_mini_sig ) {
            U::flashError('Could not validate resource id');
            return new RedirectResponse($CFG->apphome);
        }

        $knownMember = $role !== null && $role !== '';
        $knownSubmit = is_string($gc_submit_id) && $gc_submit_id !== '';
        $skipGoogle = $knownMember && ( (int) $role > LTIX::ROLE_LEARNER || $knownSubmit );

        $student_id = false;
        if ( ! $skipGoogle ) {
            $accessTokenStr = ClassroomApi::retrieve_instructor_token($owner_id);
            $client = $accessTokenStr ? ClassroomApi::getClient($accessTokenStr, $owner_id) : false;
            if ( ! $client ) {
                U::flashError('Classroom connection not set up, see your instructor');
                error_log('Classroom connection failed id='.$owner_id);
                return new RedirectResponse($CFG->apphome);
            }

            $service = new \Google_Service_Classroom($client);

            if ( $role != null ) {
                if ( $role <= LTIX::ROLE_LEARNER && U::isNotEmpty($membership_json) ) {
                    $mj = json_decode($membership_json, true);
                    $student_id = U::get($mj, 'student_id');
                    if ( ! $student_id ) error_log('Could not restore student_id');
                }
            } else {
                if ( $user_email == $owner_email ) $role = LTIX::ROLE_INSTRUCTOR;

                if ( ! $role ) {
                    $access_token_data = $client->getAccessToken();
                    $access_token = $access_token_data['access_token'];

                    $membership_info_url = "https://classroom.googleapis.com/v1/courses/".$gc_course.
                        "/students/".urlencode($user_email)."?alt=json&access_token=" .  $access_token;

                    $response = \Tsugi\Util\Net::doGet($membership_info_url);
                    $membership = json_decode($response);

                    if ( isset($membership->courseId) ) {
                        $role = LTIX::ROLE_LEARNER;
                    } else {
                        U::flashError('You are not enrolled in this class');
                        error_log('Classroom enrollment failed id='.$owner_id);
                        return new RedirectResponse($CFG->apphome);
                    }

                    if ( isset($membership->userId) ) {
                        $student_id = $membership->userId;
                    } else {
                        U::flashError('You are do not have a studentId in this class');
                        error_log('Classroom enrollment failed id='.$owner_id);
                        return new RedirectResponse($CFG->apphome);
                    }

                    $json = new \stdClass();
                    $json->student_id = $student_id;
                    $json = json_encode($json);

                    $sql = "INSERT INTO {$CFG->dbprefix}lti_membership
                        ( context_id, user_id, role, json, created_at, updated_at ) VALUES
                        ( :context_id, :user_id, :role, :json, NOW(), NOW() )
                        ON DUPLICATE KEY UPDATE role=:role, json=:json, updated_at=NOW()";
                    $PDOX->queryDie($sql, array(
                        ':context_id' => $context_id,
                        ':user_id' => $user_id,
                        ':json' => $json,
                        ':role' => $role));
                }
            }

            if ( $student_id && $gc_submit_id === null ) {
                try {
                    $studentSubmissions = $service->courses_courseWork_studentSubmissions;
                    $retval = $studentSubmissions->listCoursesCourseWorkStudentSubmissions(
                        $gc_course, $gc_coursework, array('userId' => $student_id));
                    $submissions = $retval->studentSubmissions;
                    if ( ! is_array($submissions) || ! isset($submissions[0]) ) {
                        U::flashError('Could not retrieve submission for this assignment.');
                        error_log('Classroom submission missing id='.$owner_id);
                        return new RedirectResponse($CFG->apphome);
                    }
                    $first = $submissions[0];
                    $gc_submit_id = $first->id;
                } catch (\Exception $e) {
                    U::flashError('Could not retrieve submission for this assignment.');
                    error_log('Could not retrieve submission for this assignment id='.$owner_id);
                    return new RedirectResponse($CFG->apphome);
                }
            }
        }

        if ( $result_id == null || $row['gc_submit_id'] === null ) {
            $sql = "INSERT INTO {$CFG->dbprefix}lti_result
                ( link_id, user_id, gc_submit_id, created_at, updated_at ) VALUES
                ( :link_id, :user_id, :GCS, NOW(), NOW() )
                ON DUPLICATE KEY UPDATE gc_submit_id=:GCS, updated_at=NOW()";
            $PDOX->queryDie($sql, array(
                ':link_id' => $link_id,
                ':user_id' => $user_id,
                ':GCS' => $gc_submit_id));
            $result_id = $PDOX->lastInsertId();
            error_log('New student='.$user_id.' context='.$context_id.' owner='.$user_email);
        }

        $lti = array();
        $lti['key_id'] = $key_id;
        $lti['key_key'] = $key_key;

        if ( U::isNotEmpty($key_secret) ) {
            $lti['secret'] = LTIX::encrypt_secret($key_secret);
        }

        $lti['user_id'] = $user_id;
        $lti['user_key'] = $user_key;
        $lti['user_email'] = $user_email;
        $lti['user_displayname'] = $user_displayname;
        if ( U::isNotEmpty($user_avatar) ) {
            $lti['user_image'] = $user_avatar;
        }
        $lti['role'] = $role;

        $lti['context_id'] = $context_id;
        $lti['context_key'] = $context_key;
        $lti['context_title'] = $context_title;

        $lti['link_id'] = $link_id;
        $lti['link_title'] = $link_title;

        $lti['result_id'] = $result_id;

        $lti['gc_owner_id'] = $owner_id;
        $lti['gc_course'] = $gc_course;
        $lti['gc_coursework'] = $gc_coursework;
        $lti['gc_submit_id'] = $gc_submit_id;

        $start_time = U::get($_SESSION, 'tsugi_permanent_start_time', false);
        if ( $start_time === false ) {
            LTIX::noteLoggedIn($lti);
        }

        $launch = $this->toolLaunchUrl($path, $lti);

        if ( ! U::get($_GET,'debug') ) {
            return new RedirectResponse($launch);
        }
?>
<a href="<?= $launch ?>" target="_blank"><?= $launch ?></a>
<pre>
LTI:
<?php
        print_r($lti);
?>
Row:
<?php
        print_r($row);
?>
Path:
<?php
        print_r($path);
?>

Post:
<?php
        print_r($_POST);
?>
<hr/>
Get:
<?php
        print_r($_GET);
        return new Response('', 200);
    }

    public function share(Request $request) {
        global $CFG;

        $url = $CFG->wwwroot . '/gclass/launch';
?>
<h1>I am a share</h1>

<center>
<p>
Install in Google Classroom
</p>
<script src="https://apis.google.com/js/platform.js" async defer></script>
<g:sharetoclassroom url="<?= $url ?>" size="32"></g:sharetoclassroom>
</center>
<?php
        return new Response('', 200);
    }

    /**
     * Classroom courses this teacher installed.
     */
    public function courses(Request $request) {
        global $CFG, $OUTPUT;

        $gate = $this->requireLoggedIn();
        if ( $gate ) {
            return $gate;
        }

        $userId = ReqScope::loggedInUserIdLegacy();
        $rows = ClassroomQueue::coursesForOwner($userId);
        $connection = ClassroomQueue::connectionStatus($userId);
        $email = '';
        $taught = array();
        $matched = array();
        try {
            $token = ClassroomApi::retrieve_instructor_token($userId);
            $client = $token ? ClassroomApi::getClient($token, $userId) : false;
            if ( $client ) {
                $service = new \Google_Service_Classroom($client);
                $email = self::googleEmail($service);
                $installedByCourse = array();
                foreach ( $rows as $row ) {
                    if ( ! is_array($row) ) {
                        continue;
                    }
                    $gid = self::googleCourseId((string) ($row['context_key'] ?? ''));
                    if ( $gid !== '' ) {
                        $installedByCourse[$gid] = $row;
                    }
                }
                foreach ( self::teacherCourses($service) as $course ) {
                    $gid = (string) $course->getId();
                    $installed = isset($installedByCourse[$gid]) ? $installedByCourse[$gid] : null;
                    if ( $installed ) {
                        $matched[$gid] = true;
                    }
                    $taught[] = array(
                        'name' => (string) $course->getName(),
                        'context_id' => $installed ? (int) $installed['context_id'] : 0,
                        'waiting' => $installed ? (int) $installed['waiting'] : 0,
                    );
                }
            }
        } catch ( \Throwable $e ) {
            error_log('Classroom course page user='.(int) $userId.' '.$e->getMessage());
        }
        $other = array();
        foreach ( $rows as $row ) {
            if ( ! is_array($row) ) {
                continue;
            }
            $gid = self::googleCourseId((string) ($row['context_key'] ?? ''));
            if ( $gid === '' || ! isset($matched[$gid]) ) {
                $other[] = $row;
            }
        }
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        include __DIR__.'/templates/Google/courses.inc.php';
        $OUTPUT->footer();
        return new Response('', 200);
    }

    /**
     * Queue, reset, send, and the log for one Classroom course.
     *
     * @param string|int $context
     */
    public function course(Request $request, $context = null) {
        global $CFG, $OUTPUT;

        $gate = $this->requireLoggedIn();
        if ( $gate ) {
            return $gate;
        }
        $row = $this->ownedClassroom((int) $context);
        if ( ! $row ) {
            U::flashError('That Classroom course was not found.');
            return new RedirectResponse($CFG->wwwroot.'/gclass/courses');
        }
        if ( (string) $request->query->get('connected') === '1' ) {
            ClassroomQueue::note(
                (int) $row['context_id'],
                0,
                (int) $row['user_id'],
                ClassroomQueue::KIND_RECONNECT,
                true,
                'Reconnected'
            );
            return new RedirectResponse($this->courseUrl((int) $row['context_id']));
        }
        $this->renderCourse($row);
        return new Response('', 200);
    }

    /**
     * @param string|int $context
     */
    public function sendQueue(Request $request, $context = null) {
        global $CFG;

        $gate = $this->requireLoggedIn();
        if ( $gate ) {
            return $gate;
        }
        $row = $this->ownedClassroom((int) $context);
        if ( ! $row ) {
            U::flashError('That Classroom course was not found.');
            return new RedirectResponse($CFG->wwwroot.'/gclass/courses');
        }
        $page = $this->courseUrl((int) $row['context_id']);
        $csrf = self::requireCsrf($page);
        if ( $csrf ) {
            return $csrf;
        }
        $waiting = ClassroomQueue::rowsToSend((int) $row['context_id']);
        $sent = 0;
        $failed = 0;
        foreach ( $waiting as $item ) {
            $status = ClassroomApi::deliver($item);
            if ( $status === true ) {
                $sent++;
            } else {
                $failed++;
            }
        }
        if ( count($waiting) < 1 ) {
            U::flashSuccess('Nothing is waiting to send.');
        } else if ( $failed < 1 ) {
            U::flashSuccess('Sent '.$sent.' to Google Classroom.');
        } else {
            U::flashError('Sent '.$sent.'. '.$failed.' still in the queue.');
        }
        return new RedirectResponse($page);
    }

    /**
     * @param string|int $context
     */
    public function resetQueue(Request $request, $context = null) {
        global $CFG;

        $gate = $this->requireLoggedIn();
        if ( $gate ) {
            return $gate;
        }
        $row = $this->ownedClassroom((int) $context);
        if ( ! $row ) {
            U::flashError('That Classroom course was not found.');
            return new RedirectResponse($CFG->wwwroot.'/gclass/courses');
        }
        $page = $this->courseUrl((int) $row['context_id']);
        $csrf = self::requireCsrf($page);
        if ( $csrf ) {
            return $csrf;
        }
        $cleared = ClassroomQueue::reset((int) $row['context_id']);
        if ( $cleared < 1 ) {
            U::flashSuccess('Nothing to reset.');
        } else {
            U::flashSuccess('Reset '.$cleared.' queued grade'.($cleared === 1 ? '' : 's').'.');
        }
        return new RedirectResponse($page);
    }

    /**
     * @param string|int $context
     */
    public function reconnect(Request $request, $context = null) {
        global $CFG;

        $gate = $this->requireLoggedIn();
        if ( $gate ) {
            return $gate;
        }
        $row = $this->ownedClassroom((int) $context);
        if ( ! $row ) {
            U::flashError('That Classroom course was not found.');
            return new RedirectResponse($CFG->wwwroot.'/gclass/courses');
        }
        Login::setReturnUrl($this->courseUrl((int) $row['context_id']));
        return new RedirectResponse($CFG->wwwroot.'/gclass/login?consent=1');
    }

    /**
     * Ask Google which account to use, then come back to the install page.
     */
    public function useAccount(Request $request) {
        global $CFG;

        $gate = $this->requireLoggedIn();
        if ( $gate ) {
            return $gate;
        }
        $next = (string) $request->query->get('next', '');
        if ( strpos($next, $CFG->wwwroot.'/gclass/') !== 0 || strpos($next, $CFG->wwwroot.'/gclass/login') === 0 ) {
            $next = $this->coursesUrl();
        }
        Login::setReturnUrl($next);
        return new RedirectResponse($CFG->wwwroot.'/gclass/login?consent=1');
    }

    /**
     * @param array<string,mixed> $row
     */
    private function renderCourse(array $row) {
        global $OUTPUT;
        $contextId = (int) $row['context_id'];
        $connection = ClassroomQueue::connectionStatus((int) $row['user_id']);
        $queue = ClassroomQueue::queueForContext($contextId);
        $log = ClassroomQueue::logForContext($contextId);
        $courseUrl = $this->courseUrl($contextId);
        $sendUrl = $courseUrl.'/send';
        $resetUrl = $courseUrl.'/reset';
        $reconnectUrl = $courseUrl.'/reconnect';
        $coursesUrl = $this->coursesUrl();
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        include __DIR__.'/templates/Google/course.inc.php';
        $OUTPUT->footer();
    }

    /**
     * @param int $contextId
     * @return array<string,mixed>|null
     */
    private function ownedClassroom($contextId) {
        global $CFG, $PDOX;
        if ( $contextId < 1 ) {
            return null;
        }
        $uid = ReqScope::loggedInUserIdLegacy();
        if ( ! $uid ) {
            return null;
        }
        $PDOX = LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT context_id, title, user_id, gc_secret
             FROM {$CFG->dbprefix}lti_context
             WHERE context_id = :CID
               AND user_id = :UID
               AND gc_secret IS NOT NULL AND gc_secret <> ''
               AND (deleted IS NULL OR deleted = 0)
             LIMIT 1",
            array(':CID' => $contextId, ':UID' => $uid)
        );
        return is_array($row) ? $row : null;
    }

    /**
     * @return RedirectResponse|null
     */
    private function requireLoggedIn() {
        LTIX::getConnection();
        LTIX::session_start();
        if ( ! ReqScope::isLoggedInLegacy() ) {
            Login::setReturnUrl($this->currentRequestPath(
                \Symfony\Component\HttpFoundation\Request::createFromGlobals()
            ));
            return new RedirectResponse(Login::loginUrl());
        }
        return null;
    }

    /**
     * Post-login return when it points at a Classroom page.
     *
     * @return string|null
     */
    private function takePanelReturn() {
        global $CFG;
        $url = Login::peekReturnUrl();
        if ( ! is_string($url) || strpos($url, $CFG->wwwroot.'/gclass/') !== 0 ) {
            return null;
        }
        if ( strpos($url, $CFG->wwwroot.'/gclass/login') === 0 ) {
            return null;
        }
        Login::takeReturnUrl();
        $join = strpos($url, '?') === false ? '?' : '&';
        return $url.$join.'connected=1';
    }

    /**
     * Google course id stored in gclass:{id}:{sig}.
     */
    private static function googleCourseId($contextKey) {
        if ( strpos($contextKey, 'gclass:') !== 0 ) {
            return '';
        }
        $parts = explode(':', $contextKey);
        return isset($parts[1]) ? (string) $parts[1] : '';
    }

    /**
     * Active classes the connected Google account teaches.
     *
     * @param \Google_Service_Classroom $service
     * @return array<int,\Google_Service_Classroom_Course>
     */
    public static function teacherCourses($service) {
        $found = array();
        $page = null;
        do {
            $params = array(
                'pageSize' => 100,
                'teacherId' => 'me',
                'courseStates' => 'ACTIVE',
            );
            if ( is_string($page) && $page !== '' ) {
                $params['pageToken'] = $page;
            }
            $list = $service->courses->listCourses($params);
            $courses = $list->getCourses();
            if ( is_array($courses) ) {
                foreach ( $courses as $course ) {
                    $found[] = $course;
                }
            }
            $page = $list->getNextPageToken();
        } while ( is_string($page) && $page !== '' );
        return $found;
    }

    /**
     * @param string $email
     * @param array<int,\Google_Service_Classroom_Course> $courses
     */
    public static function courseSummary($email, array $courses) {
        $count = count($courses);
        $word = $count === 1 ? 'class' : 'classes';
        $names = array();
        foreach ( $courses as $course ) {
            $name = $course->getName();
            if ( is_string($name) && $name !== '' ) {
                $names[] = $name;
            }
        }
        $who = $email !== '' ? 'Connected as '.$email.'. ' : '';
        $list = $names ? ': '.implode(', ', $names) : '';
        if ( $count < 1 ) {
            return $who.'No active classes for that Google account.';
        }
        return $who.'Found '.$count.' '.$word.$list.'.';
    }

    /**
     * @param \Google_Service_Classroom $service
     */
    public static function googleEmail($service) {
        try {
            $me = $service->userProfiles->get('me');
            $email = $me->getEmailAddress();
            return is_string($email) ? $email : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * @param int $contextId
     */
    private function courseUrl($contextId) {
        global $CFG;
        return $CFG->wwwroot.'/gclass/course/'.(int) $contextId;
    }

    private function coursesUrl() {
        global $CFG;
        return $CFG->wwwroot.'/gclass/courses';
    }

    /**
     * @return \Google_Client|Response
     */
    private function clientOrRedirect($accessTokenStr, $user_id = false) {
        $client = ClassroomApi::getClient($accessTokenStr, $user_id);
        if ( $client ) {
            return $client;
        }
        if ( is_string(ClassroomApi::$redirectError) && ClassroomApi::$redirectError !== '' ) {
            U::flashError(ClassroomApi::$redirectError);
        }
        $url = ClassroomApi::$redirectUrl;
        if ( is_string($url) && $url !== '' ) {
            return new RedirectResponse($url);
        }
        return new Response('', 200);
    }

    /**
     * @return RedirectResponse|null
     */
    private function sanityCheck() {
        global $CFG;

        if ( ! ReqScope::isLoggedInLegacy() ) {
            die_with_error_log('Error: Must be logged in to use Google Classroom');
        }

        if ( !isset($_SESSION[TSUGI_SESSION_LTI]) ) {
            U::flashError('Please log out and back in.');
            return new RedirectResponse($CFG->apphome);
        }

        if ( !isset($_SESSION[TSUGI_SESSION_LTI]['key_id']) ) {
            die_with_error_log('Error: Session is missing key_id');
        }

        return null;
    }

    /**
     * @param string|false $path
     * @return RedirectResponse
     */
    private function loginRedirect($path=false) {
        $url = Login::takeReturnUrl();
        if ( $url ) {
            return new RedirectResponse($url);
        }
        $home = Login::defaultHomeUrl();
        return new RedirectResponse($path ? rtrim($home, '/').'/'.$path : $home);
    }

    /**
     * Hand the quiz a cookieless tool session. The site login is a cookie.
     * tool/gift starts with cookies off and only resumes _LTI_TSUGI.
     *
     * @param string $path
     * @param array<string,mixed> $lti
     */
    private function toolLaunchUrl($path, array $lti) {
        if ( session_status() === PHP_SESSION_ACTIVE ) {
            session_write_close();
        }

        $previous = error_reporting();
        error_reporting(0);
        ini_set('session.use_cookies', '0');
        ini_set('session.use_only_cookies', '0');
        ini_set('session.use_trans_sid', '1');
        ini_set('session.use_strict_mode', '0');
        error_reporting($previous);

        session_name(TSUGI_COOKIELESS_SESSION_NAME);
        session_id(bin2hex(random_bytes(16)));
        session_start();

        $_SESSION[TSUGI_SESSION_LTI] = $lti;
        $_SESSION['LAST_ACTIVITY'] = time();
        $_SESSION['tsugi_permanent_start_time'] = time();
        if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
            $_SESSION['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
        }
        $ipaddr = \Tsugi\Util\Net::getIP();
        if ( is_string($ipaddr) ) {
            $_SESSION['REMOTE_ADDR'] = $ipaddr;
        }
        $_SESSION['BROWSER_MARK'] = LTIX::getBrowserMark();
        LTIX::ensureCsrfToken(true);

        $id = session_id();
        session_write_close();

        return U::add_url_parm($path, TSUGI_COOKIELESS_SESSION_NAME, $id);
    }

    /**
     * course:sig:link:sig, or null when the segment is missing or malformed.
     *
     * @param string|null $resource
     * @return array|null
     */
    private function launchPieces($resource) {
        if ( ! is_string($resource) || $resource === '' ) {
            return null;
        }
        $pieces = explode(':', $resource);
        if ( count($pieces) != 4 || strlen($pieces[1]) != 6 || strlen($pieces[3]) != 6
             || ! is_numeric($pieces[0]) || ! is_numeric($pieces[2]) ) {
            return null;
        }
        return $pieces;
    }

    /**
     * Path of this request, without the query string, for the post-login return.
     */
    private function currentRequestPath(Request $request) {
        $uri = $request->getRequestUri();
        $pos = strpos($uri, '?');
        if ( $pos !== false ) {
            $uri = substr($uri, 0, $pos);
        }
        return $uri;
    }
}

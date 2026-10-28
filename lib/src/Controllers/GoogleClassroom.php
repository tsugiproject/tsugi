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
        $app->router->get($prefix.'/launch/lesson/{sig}/{anchor}', 'GoogleClassroom@launchLesson');
        $app->router->get($prefix.'/launch/course/{sig}', 'GoogleClassroom@launchCourse');
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
        return new RedirectResponse($CFG->apphome.'/lessons/'.$firstmodule);
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
        $this->reenterCourseFromQuery();
        if ( ! \Tsugi\Controllers\Lessons::classroomInstallView()
            || ! \Tsugi\Controllers\Lessons::viewerIsCourseInstructor() ) {
            U::flashError('Only a Classroom instructor can install into Google Classroom.');
            return new RedirectResponse($CFG->apphome);
        }

        if ( (string) U::get($_GET, 'join', '') === 'course' ) {
            return $this->assignCourse();
        }

        $module = $this->lessonAnchor(U::get($_GET, 'module', ''));
        if ( $module !== '' ) {
            return $this->assignLesson($module);
        }

        $endpoint = U::get($_GET,'lti');
        $endpoint_title = U::get($_GET, 'title', 'External Tool');
        if ( ! $endpoint ) {
            if ( ! U::get($_GET,'rlid') ) {
                die_with_error_log('Error: rlid parameter is required');
            }

            $resolved = $this->endpointForResource((string) $_GET['rlid']);
            if ( $resolved === null ) {
                die_with_error_log('Invalid resource link id');
            }
            $endpoint = $resolved['url'];
            if ( ! U::get($_GET, 'title') ) {
                $endpoint_title = $resolved['title'];
            }
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
            $placed = $this->placementForInstall($gc_course, $gc_title, (int) $user_id, $key_id);
            if ( is_string($placed) ) {
                $installError = $placed;
                $gc_course = false;
            }
        }

        if ( $gc_course ) {
            $sourceContext = (int) ReqScope::currentContextId();
            if ( $sourceContext < 1 ) {
                $sourceContext = (int) ReqScope::currentContextIdLegacy();
            }
            $bound = $this->bindQuizEndpoint($endpoint, $sourceContext);
            if ( $bound === null ) {
                $installError = 'That quiz is not in this course.';
                $gc_course = false;
            } else {
                $endpoint = $bound;
            }
        }

        if ( $gc_course ) {
            $context_id = $placed['context_id'];
            $gc_secret = $placed['gc_secret'];
            $sig_user = $placed['sig_user'];

            // secret:$gc_course:$user_id:secret
            $plain = $CFG->google_classroom_secret.$gc_course.$sig_user.$CFG->google_classroom_secret;
            $user_mini_sig = lti_sha256($plain);
            $user_mini_sig = substr($user_mini_sig,0,6);
            $context_url = $gc_course . ':' . $user_mini_sig;

            $sql = "INSERT INTO {$CFG->dbprefix}lti_membership
                ( context_id, user_id, role, created_at, updated_at ) VALUES
                ( :context_id, :user_id, :role, NOW(), NOW() )
                ON DUPLICATE KEY UPDATE role=:role, updated_at=NOW()";
            $PDOX->queryDie($sql, array(
                ':context_id' => $context_id,
                ':user_id' => $user_id,
                ':role' => LTIX::ROLE_INSTRUCTOR));

            $createdLink = false;
            $link_id = $this->existingCourseLinkId((int) $context_id, (string) U::get($_GET, 'rlid', ''));
            if ( $link_id < 1 ) {
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
                $link_id = (int) $PDOX->lastInsertId();
                $createdLink = true;
            }

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
            $dueRow = $PDOX->rowDie(
                "SELECT end_datetime FROM {$CFG->dbprefix}lti_link WHERE link_id = :LID",
                array(':LID' => $link_id)
            );
            ClassroomApi::fillDueDate($cw, is_array($dueRow) ? ($dueRow['end_datetime'] ?? null) : null);

            $courseWorkService = $service->courses_courseWork;
            try {
                $courseWorkObject = $courseWorkService->create($gc_course, $cw);
            } catch (\Throwable $e) {
                $installError = ClassroomQueue::scrub($e->getMessage());
                if ( strlen($installError) > 400 ) {
                    $installError = substr($installError, 0, 400);
                }
                ClassroomQueue::note(
                    (int) $context_id,
                    (int) $link_id,
                    (int) $user_id,
                    ClassroomQueue::KIND_COURSEWORK,
                    false,
                    $installError
                );
                if ( $createdLink ) {
                    $PDOX->queryDie(
                        "DELETE FROM {$CFG->dbprefix}lti_link WHERE link_id = :LID",
                        array(':LID' => $link_id)
                    );
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

                if ( $createdLink ) {
                    $sql = "UPDATE {$CFG->dbprefix}lti_link
                        SET link_key = :link_key, link_sha256=:link_sha256,
                            gc_course = :COURSE
                        WHERE link_id = :LID";
                    $PDOX->queryDie($sql, array(
                        ':link_key' => $resource_link_id,
                        ':link_sha256' => lti_sha256($resource_link_id),
                        ':COURSE' => $gc_course,
                        ':LID' => $link_id
                    ));
                } else {
                    $sql = "UPDATE {$CFG->dbprefix}lti_link
                        SET gc_coursework = :CW, gc_course = :COURSE, updated_at = NOW()
                        WHERE link_id = :LID";
                    $PDOX->queryDie($sql, array(
                        ':CW' => $resource_link_id,
                        ':COURSE' => $gc_course,
                        ':LID' => $link_id
                    ));
                }

                $pushed = ClassroomApi::pushStoredGrades(
                    (int) $context_id,
                    (int) $link_id,
                    (int) $user_id,
                    (string) $gc_course,
                    (string) $resource_link_id
                );

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
                if ( $pushed['sent'] > 0 ) {
                    echo('<p>'.htmlspecialchars(sprintf(_m('Sent %d stored grades.'), (int) $pushed['sent']))."</p>\n");
                }
                if ( $pushed['queued'] > 0 ) {
                    echo('<p>'.htmlspecialchars(sprintf(_m('%d stored grades are waiting in the queue.'), (int) $pushed['queued']))."</p>\n");
                }
                if ( $pushed['waiting'] > 0 ) {
                    echo('<p>'.htmlspecialchars(sprintf(_m('%d stored grades will be sent when those students open the assignment.'), (int) $pushed['waiting']))."</p>\n");
                }
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
            'context' => U::get($_GET,'context'),
        ));
        $switchUrl = $CFG->wwwroot.'/gclass/use-account?next='.rawurlencode($switchNext);

        $assignTitle = $endpoint_title;
        include __DIR__.'/templates/Google/assign.inc.php';
        return new Response('', 200);
    }

    /**
     * Install a lesson page as a Classroom material. No resource link is created.
     *
     * @param string $anchor
     * @return Response
     */
    private function assignLesson($anchor) {
        $lessons = \Tsugi\Core\Manifest::currentLessons($anchor);
        $module = $lessons ? $lessons->getModuleByAnchor($anchor) : null;
        if ( ! $module ) {
            die_with_error_log('That lesson was not found.');
        }
        $endpoint_title = trim((string) U::get($_GET, 'title', ''));
        if ( $endpoint_title === '' && isset($module->title) ) {
            $endpoint_title = trim((string) $module->title);
        }
        if ( $endpoint_title === '' ) {
            $endpoint_title = $anchor;
        }

        return $this->postMaterial(
            $endpoint_title,
            array('module' => $anchor),
            function ($gcCourse, $contextId, $gcSecret, $sigUser) use ($anchor) {
                global $CFG;
                $userSig = substr(lti_sha256($CFG->google_classroom_secret.$gcCourse.$sigUser.$CFG->google_classroom_secret), 0, 6);
                $modSig = substr(lti_sha256($gcSecret.$contextId.$anchor.$gcSecret), 0, 6);
                return $CFG->wwwroot.'/gclass/launch/lesson/'
                    .$gcCourse.':'.$userSig.':'.$contextId.':'.$modSig
                    .'/'.rawurlencode($anchor);
            },
            'lesson_link',
            'Lesson '.$anchor
        );
    }

    /**
     * Install the whole course as a Classroom material. Opening it joins
     * the course. No resource link is created.
     *
     * @return Response
     */
    private function assignCourse() {
        $title = trim((string) U::get($_GET, 'title', ''));
        if ( $title === '' ) {
            $lessons = \Tsugi\Core\Manifest::currentLessons();
            if ( $lessons && isset($lessons->lessons->title) ) {
                $title = trim((string) $lessons->lessons->title);
            }
        }
        if ( $title === '' ) {
            $title = 'Course';
        }
        return $this->postMaterial(
            $title,
            array('join' => 'course'),
            function ($gcCourse, $contextId, $gcSecret, $sigUser) {
                global $CFG;
                $userSig = substr(lti_sha256($CFG->google_classroom_secret.$gcCourse.$sigUser.$CFG->google_classroom_secret), 0, 6);
                $courseSig = substr(lti_sha256($gcSecret.$contextId.'course'.$gcSecret), 0, 6);
                return $CFG->wwwroot.'/gclass/launch/course/'
                    .$gcCourse.':'.$userSig.':'.$contextId.':'.$courseSig;
            },
            'course_link',
            'Course'
        );
    }

    /**
     * Class picker and Classroom material. $urlFor builds the signed launch URL.
     *
     * @param string $endpoint_title
     * @param array<string,string> $keep
     * @param callable $urlFor
     * @param string $kind
     * @param string $detail
     * @return Response
     */
    private function postMaterial($endpoint_title, array $keep, $urlFor, $kind, $detail) {
        global $CFG, $OUTPUT, $PDOX;

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
        if ( U::get($_GET, 'gc_course') ) {
            foreach ( $courses as $course ) {
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
            $placed = $this->placementForInstall($gc_course, $gc_title, (int) $user_id, $key_id);
            if ( is_string($placed) ) {
                $installError = $placed;
                $gc_course = false;
            }
        }

        if ( $gc_course ) {
            $context_id = $placed['context_id'];
            $gc_secret = $placed['gc_secret'];
            $sig_user = $placed['sig_user'];
            $PDOX->queryDie(
                "INSERT INTO {$CFG->dbprefix}lti_membership
                    ( context_id, user_id, role, created_at, updated_at ) VALUES
                    ( :context_id, :user_id, :role, NOW(), NOW() )
                    ON DUPLICATE KEY UPDATE role=:role, updated_at=NOW()",
                array(
                    ':context_id' => $context_id,
                    ':user_id' => $user_id,
                    ':role' => LTIX::ROLE_INSTRUCTOR,
                )
            );

            $launch_url = $urlFor($gc_course, $context_id, $gc_secret, $sig_user);

            $link = new \Google_Service_Classroom_Link();
            $link->setTitle($endpoint_title);
            $link->setUrl($launch_url);
            if ( isset($CFG->google_classroom_logo) ) {
                $link->setThumbnailUrl($CFG->google_classroom_logo);
            }
            $materials = new \Google_Service_Classroom_Material();
            $materials->setLink($link);
            $item = new \Google_Service_Classroom_CourseWorkMaterial();
            $item->setTitle($endpoint_title);
            $item->setMaterials(array($materials));
            $item->setState('PUBLISHED');

            $live = $client->getAccessToken();
            $liveJson = is_array($live) ? json_encode($live) : $accessTokenStr;
            $materialsScope = \Google_Service_Classroom::CLASSROOM_COURSEWORKMATERIALS;
            if ( ClassroomApi::grantsScope($liveJson, $materialsScope) === false ) {
                $reconnect = $this->materialsConsent($endpoint_title, $gc_course, $keep);
                if ( $reconnect ) {
                    return $reconnect;
                }
                $installError = 'Reconnect Google Classroom to allow lesson links.';
                $gc_course = false;
            }

            if ( $gc_course ) {
                try {
                    $service->courses_courseWorkMaterials->create($gc_course, $item);
                } catch ( \Throwable $e ) {
                    if ( $this->scopeDenied($e) ) {
                        $reconnect = $this->materialsConsent($endpoint_title, $gc_course, $keep);
                        if ( $reconnect ) {
                            return $reconnect;
                        }
                        $installError = 'Reconnect Google Classroom to allow lesson links.';
                    } else {
                        $installError = ClassroomQueue::scrub($e->getMessage());
                        if ( strlen($installError) > 400 ) {
                            $installError = substr($installError, 0, 400);
                        }
                    }
                    $gc_course = false;
                }
            }

            if ( $gc_course ) {
                ClassroomQueue::note(
                    (int) $context_id,
                    0,
                    (int) $user_id,
                    $kind,
                    true,
                    $detail
                );
                $OUTPUT->header();
                $OUTPUT->bodyStart();
                echo("<center><p>\n");
                echo(_m('Success installing').'<br/>');
                echo('<strong>'.htmlentities($endpoint_title).'</strong>');
                if ( $gc_title ) {
                    echo('<br/>'._m('in').' '.htmlentities($gc_title));
                }
                echo("</p>\n");
                if ( $gc_url ) {
                    $launch = filter_var($gc_url, FILTER_SANITIZE_URL);
                    echo('<p><a href="'.htmlspecialchars($launch, ENT_QUOTES, 'UTF-8').'" target="_blank">');
                    echo(_m('Go to Classroom site'));
                    echo("</a></p>\n");
                }
                $OUTPUT->footer();
                return new Response('', 200);
            }
        }

        $switchNext = $CFG->wwwroot.'/gclass/assign?'.http_build_query($keep + array(
            'title' => $endpoint_title,
            'context' => U::get($_GET, 'context'),
        ));
        $switchUrl = $CFG->wwwroot.'/gclass/use-account?next='.rawurlencode($switchNext);
        $assignTitle = $endpoint_title;
        include __DIR__.'/templates/Google/assign.inc.php';
        return new Response('', 200);
    }

    /**
     * The stored Classroom connection can install assignments. Lesson and
     * course links need a newer permission, so send the teacher through
     * consent once.
     *
     * @param string $title
     * @param string $gcCourse
     * @param array<string,string> $keep
     * @return Response|null
     */
    private function materialsConsent($title, $gcCourse, array $keep) {
        global $CFG;
        if ( (string) U::get($_GET, 'scope', '') === '1' ) {
            return null;
        }
        $next = $CFG->wwwroot.'/gclass/assign?'.http_build_query($keep + array(
            'title' => $title,
            'context' => U::get($_GET, 'context'),
            'gc_course' => $gcCourse,
            'scope' => '1',
        ));
        $url = $CFG->wwwroot.'/gclass/use-account?next='.rawurlencode($next);
        $safe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        header('Content-Type: text/html; charset=utf-8');
        echo('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Reconnect</title></head><body>');
        echo('<p>Google Classroom needs permission to add a lesson link. Reconnect to continue.</p>');
        echo('<script>window.top.location.href='.json_encode($url).';</script>');
        echo('<p><a href="'.$safe.'" target="_top">Reconnect</a></p></body></html>');
        return new Response('', 200);
    }

    /**
     * @param \Throwable $e
     */
    private function scopeDenied($e) {
        $message = $e->getMessage();
        return stripos($message, 'insufficient') !== false
            || stripos($message, 'ACCESS_TOKEN_SCOPE_INSUFFICIENT') !== false;
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
                L.path AS path, link_key, L.gc_coursework AS gc_coursework,
                L.link_id as link_id, L.title AS link_title,
                result_id, gc_submit_id, R.grade AS result_grade, R.server_grade AS server_grade,
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
            WHERE L.link_id = :LID
            AND (
                (C.context_sha256 = :context_sha256 AND C.context_key = :context_key)
                OR (C.manifest_id IS NOT NULL AND C.manifest_id > 0)
            )
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

        $gc_secret = trim((string) ($row['gc_secret'] ?? ''));
        $path = $row['path'];
        $owner_id = $row['owner_id'];
        $storedWork = trim((string) ($row['gc_coursework'] ?? ''));
        $gc_coursework = $storedWork !== '' ? $storedWork : (string) $row['link_key'];
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

        if ( $gc_secret === ''
            || ! $this->sameSig($link_mini_check, $link_mini_sig)
            || ! $this->sameSig($user_mini_check, $user_mini_sig) ) {
            U::flashError('Could not validate resource id');
            return new RedirectResponse($CFG->apphome);
        }

        $mirrorLinkId = (int) $link_id;
        $gradeLink = $this->inheritedGradeLink((int) $context_id, (string) $path, $mirrorLinkId);
        if ( $gradeLink ) {
            $link_id = $gradeLink['link_id'];
            if ( $gradeLink['title'] !== '' ) {
                $link_title = $gradeLink['title'];
            }
            if ( $gc_coursework !== '' ) {
                $PDOX->queryDie(
                    "UPDATE {$CFG->dbprefix}lti_link
                     SET gc_coursework = :CW, updated_at = NOW()
                     WHERE link_id = :LID AND (gc_coursework IS NULL OR gc_coursework = '')",
                    array(':CW' => $gc_coursework, ':LID' => $link_id)
                );
            }
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}lti_link
                 SET deleted = 1, deleted_at = NOW(), updated_at = NOW()
                 WHERE link_id = :LID AND link_id <> :KEEP",
                array(':LID' => $mirrorLinkId, ':KEEP' => $link_id)
            );
            $again = $PDOX->rowDie(
                "SELECT result_id, gc_submit_id, grade, server_grade
                 FROM {$CFG->dbprefix}lti_result
                 WHERE link_id = :LID AND user_id = :UID AND deleted = 0
                 ORDER BY updated_at DESC LIMIT 1",
                array(':LID' => $link_id, ':UID' => $user_id)
            );
            $result_id = is_array($again) ? ($again['result_id'] ?? null) : null;
            $gc_submit_id = is_array($again) ? ($again['gc_submit_id'] ?? null) : null;
            $row['gc_submit_id'] = $gc_submit_id;
            $row['result_grade'] = is_array($again) ? ($again['grade'] ?? null) : null;
            $row['server_grade'] = is_array($again) ? ($again['server_grade'] ?? null) : null;
        }

        $this->rememberCourse((int) $link_id, (string) $gc_course);

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
            $newId = (int) $PDOX->lastInsertId();
            if ( ($result_id === null || $result_id === '') && $newId > 0 ) {
                $result_id = $newId;
            }
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

        $haveGrade = isset($row['result_grade']) && $row['result_grade'] !== null && $row['result_grade'] !== '';
        $serverGrade = $row['server_grade'] ?? null;
        $notSent = $serverGrade === null || $serverGrade === '';
        if ( $haveGrade && $notSent && is_string($gc_submit_id) && $gc_submit_id !== '' ) {
            ClassroomApi::deliver(array(
                'context_id' => $context_id,
                'link_id' => $link_id,
                'user_id' => $user_id,
                'result_id' => $result_id,
                'owner_id' => $owner_id,
                'gc_course' => $gc_course,
                'gc_coursework' => $gc_coursework,
                'gc_submit_id' => $gc_submit_id,
                'grade' => (float) $row['result_grade'],
            ));
        }

        $launchPath = $this->absoluteLaunchUrl((string) $path, (int) $link_id, (int) $context_id);
        if ( $launchPath === '' ) {
            U::flashError('Could not open this assignment');
            return new RedirectResponse($CFG->apphome);
        }
        // A manifest course already has the link, and the site cookie is the login.
        // The cookieless _LTI_TSUGI session is for a file-based tool with no site login.
        if ( \Tsugi\Core\Manifest::idForContext((int) $context_id) > 0 ) {
            $launch = $launchPath;
        } else {
            $launch = $this->toolLaunchUrl($launchPath, $lti);
        }

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

    /**
     * Open the course from a Classroom material. The student is logged in
     * and joined. No resource link is created.
     *
     * @param string|null $sig
     */
    public function launchCourse(Request $request, $sig = null) {
        return $this->openSignedCourse($request, $sig, null);
    }

    /**
     * Open a lesson from a Classroom material. The signature is the course,
     * not a resource link, and no link row is created.
     *
     * @param string|null $sig
     * @param string|null $anchor
     */
    public function launchLesson(Request $request, $sig = null, $anchor = null) {
        return $this->openSignedCourse($request, $sig, $anchor);
    }

    /**
     * @param string|null $sig
     * @param string|null $anchor Null opens the course itself.
     */
    private function openSignedCourse(Request $request, $sig, $anchor) {
        global $CFG, $PDOX;

        $agent = $request->headers->get('User-Agent');
        if ( isset($CFG->google_classroom_logo) && $agent && stripos($agent, 'Google Web Preview') !== false ) {
            echo('<center><img src="'.$CFG->google_classroom_logo.'"></center>'."\n");
            return new Response('', 200);
        }

        if ( session_id() == '' ) {
            session_start();
        }

        $joining = ($anchor === null);
        if ( ! $joining ) {
            $anchor = $this->lessonAnchor(rawurldecode((string) $anchor));
        }
        $pieces = is_string($sig) ? explode(':', $sig) : array();
        if ( ( ! $joining && $anchor === '' ) || count($pieces) !== 4 || ! ctype_digit($pieces[0]) || strlen($pieces[1]) !== 6
            || ! ctype_digit($pieces[2]) || strlen($pieces[3]) !== 6 ) {
            U::flashError('Invalid resource id format');
            return new RedirectResponse($CFG->apphome);
        }

        if ( ! ReqScope::isLoggedInLegacy() ) {
            Login::setReturnUrl($this->currentRequestPath($request));
            return new RedirectResponse(Login::loginUrl());
        }

        $gc_course = $pieces[0];
        $user_mini_sig = $pieces[1];
        $context_id = (int) $pieces[2];
        $mod_sig = $pieces[3];
        $PDOX = LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT C.gc_secret AS gc_secret, C.user_id AS owner_id, O.email AS owner_email
             FROM {$CFG->dbprefix}lti_context AS C
             JOIN {$CFG->dbprefix}lti_user AS O ON C.user_id = O.user_id
             WHERE C.context_id = :CID AND (C.deleted IS NULL OR C.deleted = 0)
             LIMIT 1",
            array(':CID' => $context_id)
        );
        if ( ! is_array($row) ) {
            U::flashError('Could not lookup resource id');
            return new RedirectResponse($CFG->apphome);
        }

        $owner_id = (int) ($row['owner_id'] ?? 0);
        $gc_secret = trim((string) ($row['gc_secret'] ?? ''));
        $user_check = substr(lti_sha256($CFG->google_classroom_secret.$gc_course.$owner_id.$CFG->google_classroom_secret), 0, 6);
        $bound = $joining ? 'course' : $anchor;
        $mod_check = substr(lti_sha256($gc_secret.$context_id.$bound.$gc_secret), 0, 6);
        if ( $gc_secret === ''
            || ! $this->sameSig($user_check, $user_mini_sig)
            || ! $this->sameSig($mod_check, $mod_sig) ) {
            U::flashError('Could not validate resource id');
            return new RedirectResponse($CFG->apphome);
        }

        $accepted = $this->acceptClassroomStudent($context_id, $gc_course, $owner_id, (string) ($row['owner_email'] ?? ''));
        if ( $accepted !== true ) {
            U::flashError($accepted);
            return new RedirectResponse($CFG->apphome);
        }

        if ( \Tsugi\Core\Manifest::idForContext($context_id) > 0 ) {
            $switched = Courses::ensureActiveContext($context_id);
            if ( $switched !== true ) {
                U::flashError(is_string($switched) ? $switched : 'You are not a member of that course.');
                return new RedirectResponse($CFG->apphome);
            }
            $dest = $joining
                ? Courses::courseUrlPrefix($context_id)
                : Courses::courseUrlPrefix($context_id).'/lessons/'.rawurlencode($anchor);
        } else if ( $joining ) {
            $dest = rtrim((string) $CFG->apphome, '/').'/lessons';
        } else {
            $dest = rtrim((string) $CFG->apphome, '/').'/lessons/'.rawurlencode($anchor);
        }
        return new RedirectResponse($dest);
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
     * After a site login, a stored Classroom token means this person already
     * connected. Count the classes they teach and remember it for this session.
     *
     * @param int $userId
     */
    public static function rememberCourseCount($userId) {
        global $CFG;
        unset($_SESSION['gc_count']);
        if ( ! isset($CFG->google_classroom_secret) || ! is_string($CFG->google_classroom_secret)
            || $CFG->google_classroom_secret === '' ) {
            return;
        }
        $userId = (int) $userId;
        if ( $userId < 1 || ! ClassroomApi::retrieve_instructor_token($userId) ) {
            return;
        }
        try {
            $client = ClassroomApi::getClient(false, $userId, false, 0, 2);
            if ( ! $client ) {
                return;
            }
            $courses = self::teacherCourses(new \Google_Service_Classroom($client), 1);
            $_SESSION['gc_count'] = count($courses);
        } catch ( \Throwable $e ) {
            error_log('Classroom course count skipped user='.$userId);
        }
    }

    /**
     * Active classes the connected Google account teaches.
     *
     * @param \Google_Service_Classroom $service
     * @param int $maxPages 0 reads every page
     * @return array<int,\Google_Service_Classroom_Course>
     */
    public static function teacherCourses($service, $maxPages=0) {
        $found = array();
        $page = null;
        $pages = 0;
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
            $pages++;
            $page = $list->getNextPageToken();
            if ( $maxPages > 0 && $pages >= $maxPages ) {
                break;
            }
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
     * /gclass/assign is a site URL, so the session is put back on the global
     * course before this runs. The lesson that opened the installer names
     * its course. Come back to that course before resolving the resource.
     */
    private function reenterCourseFromQuery() {
        $courseId = (int) U::get($_GET, 'context', 0);
        if ( $courseId < 1 ) {
            return;
        }
        $result = \Tsugi\Controllers\Courses::ensureActiveContext($courseId);
        if ( $result !== true ) {
            die_with_error_log(is_string($result) ? $result : 'Could not open that course.');
        }
    }

    /**
     * The lesson resource's own link in this course, or 0 when this install
     * still needs a new link. A manifest quiz already has that link.
     *
     * @param int $contextId
     * @param string $rlid
     */
    private function existingCourseLinkId($contextId, $rlid) {
        global $CFG, $PDOX;
        $rlid = trim($rlid);
        if ( $contextId < 1 || $rlid === '' ) {
            return 0;
        }
        if ( \Tsugi\Core\Manifest::idForContext($contextId) < 1 ) {
            return 0;
        }
        $row = $PDOX->rowDie(
            "SELECT link_id FROM {$CFG->dbprefix}lti_link
             WHERE context_id = :CID AND link_key = :KEY
               AND (deleted IS NULL OR deleted = 0)
             LIMIT 1",
            array(':CID' => $contextId, ':KEY' => $rlid)
        );
        if ( ! is_array($row) ) {
            return 0;
        }
        return (int) ($row['link_id'] ?? 0);
    }

    /**
     * Remember which Google course this column grades, so a course-page
     * submit can send the score without the Classroom launch session.
     *
     * @param int $linkId
     * @param string $courseId
     */
    private function rememberCourse($linkId, $courseId) {
        global $CFG, $PDOX;
        $courseId = trim($courseId);
        if ( $linkId < 1 || $courseId === '' || ! ctype_digit($courseId) ) {
            return;
        }
        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}lti_link
             SET gc_course = :COURSE, updated_at = NOW()
             WHERE link_id = :LID",
            array(':COURSE' => $courseId, ':LID' => $linkId)
        );
    }

    /**
     * A Classroom link whose path says inherit= the quiz. The session should
     * be the quiz link. The coursework id stays on the Classroom link.
     *
     * @param int $contextId
     * @param string $path
     * @param int $fromLinkId
     * @return array{link_id:int,title:string}|null
     */
    private function inheritedGradeLink($contextId, $path, $fromLinkId) {
        global $CFG, $PDOX;
        $query = parse_url($path, PHP_URL_QUERY);
        if ( ! is_string($query) || $query === '' ) {
            return null;
        }
        $params = array();
        parse_str($query, $params);
        $rlid = isset($params['inherit']) && is_string($params['inherit']) ? $params['inherit'] : '';
        if ( $rlid === '' ) {
            return null;
        }
        $row = $PDOX->rowDie(
            "SELECT link_id, title FROM {$CFG->dbprefix}lti_link
             WHERE context_id = :CID AND link_key = :KEY
               AND link_id <> :FROM
               AND (deleted IS NULL OR deleted = 0)
             LIMIT 1",
            array(':CID' => (int) $contextId, ':KEY' => $rlid, ':FROM' => (int) $fromLinkId)
        );
        if ( ! is_array($row) || (int) ($row['link_id'] ?? 0) < 1 ) {
            return null;
        }
        return array(
            'link_id' => (int) $row['link_id'],
            'title' => (string) ($row['title'] ?? ''),
        );
    }

    /**
     * Course that owns the new Classroom link.
     * A manifest course stays that course. A file-based course gets one
     * Tsugi course per Google class, created on the first install.
     *
     * @param string $googleCourseId
     * @param string $googleTitle
     * @param int $userId
     * @param mixed $keyId
     * @return array{context_id:int,gc_secret:string,sig_user:int}|string
     */
    private function placementForInstall($googleCourseId, $googleTitle, $userId, $keyId) {
        global $CFG, $PDOX;
        $currentId = (int) ReqScope::currentContextId();
        if ( $currentId < 1 ) {
            $currentId = (int) ReqScope::currentContextIdLegacy();
        }
        $manifestId = $currentId > 0 ? \Tsugi\Core\Manifest::idForContext($currentId) : 0;
        if ( $manifestId > 0 ) {
            return $this->manifestPlacement($currentId, $userId);
        }
        return $this->fileCoursePlacement($googleCourseId, $googleTitle, $userId, $keyId);
    }

    /**
     * @param int $contextId
     * @param int $userId
     * @return array{context_id:int,gc_secret:string,sig_user:int}|string
     */
    private function manifestPlacement($contextId, $userId) {
        global $CFG, $PDOX;
        $row = $PDOX->rowDie(
            "SELECT context_id, user_id, gc_secret
             FROM {$CFG->dbprefix}lti_context
             WHERE context_id = :CID AND (deleted IS NULL OR deleted = 0)",
            array(':CID' => (int) $contextId)
        );
        if ( ! is_array($row) ) {
            return 'This course could not be used for Classroom.';
        }
        $sigUser = (int) ($row['user_id'] ?? 0);
        if ( $sigUser > 0 && $sigUser !== (int) $userId ) {
            return 'Only the course owner can install into Google Classroom.';
        }
        if ( $sigUser < 1 ) {
            $sigUser = (int) $userId;
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}lti_context
                 SET user_id = :UID, updated_at = NOW()
                 WHERE context_id = :CID AND (user_id IS NULL OR user_id = 0)",
                array(':UID' => $sigUser, ':CID' => (int) $contextId)
            );
        }
        $secret = trim((string) ($row['gc_secret'] ?? ''));
        if ( $secret === '' ) {
            $secret = bin2hex(openssl_random_pseudo_bytes(128 / 2));
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}lti_context
                 SET gc_secret = :GCS, updated_at = NOW()
                 WHERE context_id = :CID AND (gc_secret IS NULL OR gc_secret = '')",
                array(':GCS' => $secret, ':CID' => (int) $contextId)
            );
        }
        return array(
            'context_id' => (int) $contextId,
            'gc_secret' => $secret,
            'sig_user' => $sigUser,
        );
    }

    /**
     * @param string $googleCourseId
     * @param string $googleTitle
     * @param int $userId
     * @param mixed $keyId
     * @return array{context_id:int,gc_secret:string,sig_user:int}
     */
    private function fileCoursePlacement($googleCourseId, $googleTitle, $userId, $keyId) {
        global $CFG, $PDOX;
        $plain = $CFG->google_classroom_secret.$googleCourseId.$userId.$CFG->google_classroom_secret;
        $user_mini_sig = substr(lti_sha256($plain), 0, 6);
        $context_key = 'gclass:'.$googleCourseId.':'.$user_mini_sig;
        $context_sha256 = lti_sha256($context_key);

        $row = $PDOX->rowDie(
            "SELECT * FROM {$CFG->dbprefix}lti_context
                WHERE context_sha256 = :context LIMIT 1",
            array(':context' => $context_sha256)
        );

        $context_id = false;
        $gc_secret = false;
        if ( $row != false ) {
            if ( $row['user_id'] != $userId ) {
                die_with_error_log('Error: Incorrect course ownership');
            }
            $context_id = $row['context_id'];
            $gc_secret = $row['gc_secret'];
            if ( $row['title'] != $googleTitle ) {
                $PDOX->queryDie(
                    "UPDATE {$CFG->dbprefix}lti_context
                     SET title = :title, updated_at=NOW() WHERE context_id = :CID",
                    array(':title' => $googleTitle, ':CID' => $context_id)
                );
            }
        }

        if ( ! $context_id ) {
            $gc_secret = bin2hex(openssl_random_pseudo_bytes(128 / 2));
            $PDOX->queryDie(
                "INSERT INTO {$CFG->dbprefix}lti_context
                    ( context_key, context_sha256, title, key_id, gc_secret, user_id, created_at, updated_at )
                    VALUES
                    ( :context_key, :context_sha256, :title, :key_id, :GCS, :user_id, NOW(), NOW() )",
                array(
                    ':context_key' => $context_key,
                    ':context_sha256' => $context_sha256,
                    ':title' => $googleTitle,
                    ':GCS' => $gc_secret,
                    ':user_id' => $userId,
                    ':key_id' => $keyId,
                )
            );
            $context_id = $PDOX->lastInsertId();
        }

        return array(
            'context_id' => (int) $context_id,
            'gc_secret' => (string) $gc_secret,
            'sig_user' => (int) $userId,
        );
    }

    /**
     * Launch URL and title for a lesson resource id.
     * File lessons resolve through the lesson JSON. A manifest tool, quiz,
     * or discussion resolves through this course's manifest and its links.
     *
     * @param string $rlid
     * @return array{url:string,title:string}|null
     */
    private function endpointForResource($rlid) {
        global $CFG, $PDOX;
        $rlid = trim($rlid);
        if ( $rlid === '' ) {
            return null;
        }
        $contextId = (int) ReqScope::currentContextId();
        if ( $contextId < 1 ) {
            $contextId = (int) ReqScope::currentContextIdLegacy();
        }
        $manifestId = $contextId > 0 ? \Tsugi\Core\Manifest::idForContext($contextId) : 0;

        $lessons = \Tsugi\Core\Manifest::currentLessons();
        if ( $lessons ) {
            $lti = $lessons->getLtiByRlid($rlid);
            if ( $lti ) {
                $built = $this->endpointFromLessonItem($lti, $rlid, $contextId, $manifestId > 0);
                if ( $built !== null ) {
                    return $built;
                }
            }
        }
        if ( $manifestId > 0 ) {
            $json = \Tsugi\Core\Manifest::loadJson($manifestId);
            $data = is_string($json) ? json_decode($json, true) : null;
            $node = is_array($data) ? $this->findResourceNode($data, $rlid) : null;
            if ( is_array($node) ) {
                $built = $this->endpointFromLessonItem($node, $rlid, $contextId, true);
                if ( $built !== null ) {
                    return $built;
                }
            }
        }
        if ( $contextId < 1 ) {
            return null;
        }
        $row = $PDOX->rowDie(
            "SELECT link_id, title, path FROM {$CFG->dbprefix}lti_link
             WHERE context_id = :CID AND link_key = :KEY
               AND (deleted IS NULL OR deleted = 0)
             LIMIT 1",
            array(':CID' => $contextId, ':KEY' => $rlid)
        );
        if ( is_array($row) ) {
            $built = $this->endpointFromStoredPath(
                trim((string) ($row['path'] ?? '')),
                (int) ($row['link_id'] ?? 0),
                trim((string) ($row['title'] ?? '')),
                $rlid
            );
            if ( $built !== null ) {
                return $built;
            }
        }
        $content = $PDOX->rowDie(
            "SELECT C.title AS title, L.link_id AS link_id, L.path AS path, L.title AS link_title
             FROM {$CFG->dbprefix}lti_content C
             LEFT JOIN {$CFG->dbprefix}lti_link L
               ON L.link_id = C.link_id AND L.context_id = C.context_id
             WHERE C.context_id = :CID AND C.resource_link_id = :KEY
             LIMIT 1",
            array(':CID' => $contextId, ':KEY' => $rlid)
        );
        if ( ! is_array($content) ) {
            return null;
        }
        $title = trim((string) ($content['link_title'] ?? ''));
        if ( $title === '' ) {
            $title = trim((string) ($content['title'] ?? ''));
        }
        $path = trim((string) ($content['path'] ?? ''));
        if ( $path === '' && $manifestId > 0 ) {
            $path = $this->courseLessonLaunch($contextId, $rlid);
        }
        return $this->endpointFromStoredPath($path, (int) ($content['link_id'] ?? 0), $title, $rlid);
    }

    /**
     * @param object|array<string,mixed> $item
     * @param string $rlid
     * @param int $contextId
     * @param bool $manifest
     * @return array{url:string,title:string}|null
     */
    private function endpointFromLessonItem($item, $rlid, $contextId, $manifest) {
        $title = '';
        if ( is_object($item) && isset($item->title) && is_string($item->title) ) {
            $title = $item->title;
        } else if ( is_array($item) && isset($item['title']) && is_string($item['title']) ) {
            $title = $item['title'];
        }
        if ( $manifest && \Tsugi\Services\Lessons\LessonsNormalize::isDiscussion($item) && $contextId > 0 ) {
            return array(
                'url' => $this->discussionLaunchUrl($contextId, $rlid),
                'title' => $title !== '' ? $title : 'Discussion',
            );
        }
        $launch = \Tsugi\Services\Lessons\LessonsNormalize::launchUrlForItem($item);
        if ( ! is_string($launch) || $launch === '' ) {
            return null;
        }
        return array(
            'url' => U::add_url_parm($launch, 'inherit', $rlid),
            'title' => $title !== '' ? $title : 'External Tool',
        );
    }

    /**
     * @param array<string,mixed> $node
     * @param string $rlid
     * @return array<string,mixed>|null
     */
    private function findResourceNode($node, $rlid) {
        if ( isset($node['resource_link_id']) && (string) $node['resource_link_id'] === $rlid ) {
            return $node;
        }
        foreach ( $node as $child ) {
            if ( ! is_array($child) ) {
                continue;
            }
            $found = $this->findResourceNode($child, $rlid);
            if ( $found !== null ) {
                return $found;
            }
        }
        return null;
    }

    /**
     * Discussion URL inside this course, matching the lessons page.
     *
     * @param int $contextId
     * @param string $rlid
     */
    private function discussionLaunchUrl($contextId, $rlid) {
        global $CFG;
        $prefix = \Tsugi\Controllers\Courses::courseUrlPrefix($contextId);
        $tail = $CFG->getExtension('discussion_lti_launch', false)
            ? 'lessons_launch/'.$rlid
            : 'discussions/'.$rlid;
        return rtrim($prefix, '/').'/'.$tail;
    }

    /**
     * @param int $contextId
     * @param string $rlid
     */
    private function courseLessonLaunch($contextId, $rlid) {
        $prefix = \Tsugi\Controllers\Courses::courseUrlPrefix($contextId);
        return rtrim($prefix, '/').'/lessons_launch/'.$rlid;
    }

    /**
     * @param string $path
     * @param int $linkId
     * @param string $title
     * @param string $rlid
     * @return array{url:string,title:string}|null
     */
    private function endpointFromStoredPath($path, $linkId, $title, $rlid) {
        $url = $this->absoluteLaunchUrl($path, $linkId);
        if ( $url === '' ) {
            return null;
        }
        if ( ! str_contains($url, 'inherit=') ) {
            $url = U::add_url_parm($url, 'inherit', $rlid);
        }
        if ( $title === '' ) {
            $title = 'External Tool';
        }
        return array('url' => $url, 'title' => $title);
    }

    /**
     * A stored link path may be a full URL or a tool-relative path.
     * A quiz path is the take page for that link, so the Classroom launch
     * opens the quiz that lives on the course. The redirect must be absolute:
     * a short path is resolved against /gclass/launch/ and never reaches the quiz.
     *
     * @param string $path
     * @param int $linkId
     * @param int $contextId Course that owns the link. The launch request is not under /courses.
     */
    private function absoluteLaunchUrl($path, $linkId, $contextId = 0) {
        global $CFG;
        if ( $path === '' ) {
            return '';
        }
        if ( preg_match('#^https?://#i', $path) ) {
            return $path;
        }
        $contextId = (int) $contextId;
        if ( $contextId < 1 ) {
            $contextId = (int) ReqScope::currentContextId();
            if ( $contextId < 1 ) {
                $contextId = (int) ReqScope::currentContextIdLegacy();
            }
        }
        if ( preg_match('#^quiz1/\d+$#', $path) && $linkId > 0 ) {
            if ( $contextId > 0 && \Tsugi\Core\Manifest::idForContext($contextId) > 0 ) {
                $prefix = \Tsugi\Controllers\Courses::courseUrlPrefix($contextId);
                return rtrim($prefix, '/').'/quiz1/link/'.(string) $linkId;
            }
            $home = Tool::determineToolHome(Quiz1::ROUTE);
            if ( is_string($home) && $home !== '' ) {
                return Tool::joinToolHome($home, 'link/'.(string) $linkId);
            }
        }
        return rtrim((string) $CFG->wwwroot, '/').'/'.ltrim($path, '/');
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
    /**
     * A lesson anchor safe to put in a signed Classroom URL.
     *
     * @param mixed $raw
     */
    private function lessonAnchor($raw) {
        $anchor = trim((string) $raw);
        if ( $anchor === '' || strlen($anchor) > 128 ) {
            return '';
        }
        if ( ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._~-]*$/', $anchor) ) {
            return '';
        }
        return $anchor;
    }

    /**
     * The signed-in user may open this course from Classroom.
     * A person who is already a member is not checked again.
     *
     * @param int $contextId
     * @param string $gcCourse
     * @param int $ownerId
     * @param string $ownerEmail
     * @return true|string
     */
    private function acceptClassroomStudent($contextId, $gcCourse, $ownerId, $ownerEmail) {
        global $CFG, $PDOX;
        $userId = ReqScope::loggedInUserIdLegacy();
        if ( $userId < 1 || $contextId < 1 ) {
            return 'Must be logged in.';
        }
        $row = $PDOX->rowDie(
            "SELECT role FROM {$CFG->dbprefix}lti_membership
             WHERE context_id = :CID AND user_id = :UID LIMIT 1",
            array(':CID' => $contextId, ':UID' => $userId)
        );
        if ( is_array($row) ) {
            return true;
        }

        $email = trim((string) ($_SESSION['email'] ?? ''));
        $role = LTIX::ROLE_LEARNER;
        if ( $email !== '' && strcasecmp($email, trim($ownerEmail)) === 0 ) {
            $role = LTIX::ROLE_INSTRUCTOR;
        } else {
            $accessTokenStr = ClassroomApi::retrieve_instructor_token($ownerId);
            $client = $accessTokenStr ? ClassroomApi::getClient($accessTokenStr, $ownerId) : false;
            if ( ! $client ) {
                error_log('Classroom connection failed id='.$ownerId);
                return 'Classroom connection not set up, see your instructor';
            }
            $access_token_data = $client->getAccessToken();
            $access_token = is_array($access_token_data) ? ($access_token_data['access_token'] ?? '') : '';
            if ( ! is_string($access_token) || $access_token === '' || $email === '' ) {
                return 'You are not enrolled in this class';
            }
            $membership_info_url = 'https://classroom.googleapis.com/v1/courses/'.$gcCourse
                .'/students/'.urlencode($email).'?alt=json&access_token='.$access_token;
            $response = \Tsugi\Util\Net::doGet($membership_info_url);
            $membership = json_decode($response);
            if ( ! isset($membership->courseId) ) {
                error_log('Classroom enrollment failed id='.$ownerId);
                return 'You are not enrolled in this class';
            }
        }

        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}lti_membership
                ( context_id, user_id, role, created_at, updated_at ) VALUES
                ( :CID, :UID, :ROLE, NOW(), NOW() )
                ON DUPLICATE KEY UPDATE role=:ROLE, updated_at=NOW()",
            array(':CID' => $contextId, ':UID' => $userId, ':ROLE' => $role)
        );
        return true;
    }

    /**
     * A Quiz1 take URL has to name a link in the course being installed.
     * The stored path records that course and link. Other tools are unchanged.
     *
     * @param string $endpoint
     * @param int $contextId
     * @return string|null
     */
    private function bindQuizEndpoint($endpoint, $contextId) {
        global $CFG, $PDOX;
        if ( ! preg_match('#/quiz1/link/(\d+)#', $endpoint, $match) ) {
            return $endpoint;
        }
        if ( preg_match('#^https?://#i', $endpoint) ) {
            $host = parse_url($endpoint, PHP_URL_HOST);
            $ours = array();
            foreach ( array($CFG->wwwroot ?? '', $CFG->apphome ?? '') as $base ) {
                $known = parse_url((string) $base, PHP_URL_HOST);
                if ( is_string($known) && $known !== '' ) {
                    $ours[] = strtolower($known);
                }
            }
            if ( ! is_string($host) || ! in_array(strtolower($host), $ours, true) ) {
                return null;
            }
        }
        $linkId = (int) $match[1];
        if ( $linkId < 1 || $contextId < 1 ) {
            return null;
        }
        $row = $PDOX->rowDie(
            "SELECT context_id FROM {$CFG->dbprefix}lti_link
             WHERE link_id = :LID AND (deleted IS NULL OR deleted = 0)
             LIMIT 1",
            array(':LID' => $linkId)
        );
        if ( ! is_array($row) || (int) ($row['context_id'] ?? 0) !== (int) $contextId ) {
            return null;
        }
        $endpoint = U::add_url_parm($endpoint, 'gc_context', (string) (int) $contextId);
        return U::add_url_parm($endpoint, 'gc_link', (string) $linkId);
    }

    /**
     * Constant-time compare for the 6-character Classroom signatures.
     *
     * @param mixed $expected
     * @param mixed $given
     */
    private function sameSig($expected, $given) {
        $expected = (string) $expected;
        $given = (string) $given;
        if ( $expected === '' || strlen($expected) !== strlen($given) ) {
            return false;
        }
        return hash_equals($expected, $given);
    }

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

<?php

namespace Tsugi\Controllers;


use \Tsugi\Util\U;
use Tsugi\Lumos\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tsugi\Grades\GradeUtil;
use Tsugi\Core\LTIX;
use Tsugi\Core\Manifest;
use Tsugi\Core\Membership;
use Tsugi\Services\Lessons\LessonsService;
use Tsugi\Services\Lessons\LessonsNormalize;

use Tsugi\Core\ReqScope;

class Assignments extends Tool {

    const ROUTE = '/assignments';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix.'/student-progress', 'Assignments@studentProgress');
        $app->router->get($prefix.'/student-progress/', 'Assignments@studentProgress');
        $app->router->get($prefix.'/manage-due-dates', 'Assignments@manageDueDates');
        $app->router->get($prefix.'/manage-due-dates/', 'Assignments@manageDueDates');
        $app->router->post($prefix.'/manage-due-dates/add-link-rows', 'Assignments@addMissingLinkRowsPost');
        $app->router->post($prefix.'/manage-due-dates/add-link-rows/', 'Assignments@addMissingLinkRowsPost');
        $app->router->post($prefix.'/manage-due-dates/apply-weekly', 'Assignments@applyWeeklyDueDatesPost');
        $app->router->post($prefix.'/manage-due-dates/apply-weekly/', 'Assignments@applyWeeklyDueDatesPost');
        $app->router->post($prefix.'/manage-due-dates/clear-all', 'Assignments@clearAllDueDatesPost');
        $app->router->post($prefix.'/manage-due-dates/clear-all/', 'Assignments@clearAllDueDatesPost');
        $app->router->post($prefix.'/manage-due-dates', 'Assignments@manageDueDatesPost');
        $app->router->post($prefix.'/manage-due-dates/', 'Assignments@manageDueDatesPost');
        $app->router->post($prefix.'/toggle-view-due-dates', 'Assignments@toggleViewDueDatesPost');
        $app->router->post($prefix.'/toggle-view-due-dates/', 'Assignments@toggleViewDueDatesPost');
        $app->router->get($prefix, 'Assignments@get');
        $app->router->get($prefix.'/', 'Assignments@get');
    }

    /**
     * After lti_membership or due-date visibility changes for this context.
     */
    private function invalidateMembershipAndDueDatesCache() {
        $context_id = ReqScope::currentContextId();
        if ( ! $context_id ) {
            return;
        }
        $cid = (int) $context_id;
        Membership::invalidateSessionCache($cid);
        GradeUtil::invalidateDueDatesCache($cid);
    }

    public function get(Request $request)
    {
        global $CFG, $OUTPUT;

        $l = Manifest::requireCurrentLessons();

        $allgrades = array();
        $alldates = array();
        $alllinkids = array();
        $rows = GradeUtil::loadGradesCurrentUser();
        foreach ( $rows as $row ) {
            $allgrades[$row['resource_link_id']] = $row['grade'];
            $alldates[$row['resource_link_id']] = $row['updated_at'];
            if ( isset($row['link_id']) ) {
                $alllinkids[$row['resource_link_id']] = (int) $row['link_id'];
            }
        }

        if ( $this->isInstructor() && ReqScope::currentContextId() !== 0 ) {
            foreach ( GradeUtil::loadLinkIdsForContext(ReqScope::currentContextId()) as $rlid => $link_id ) {
                $alllinkids[$rlid] = (int) $link_id;
            }
        }

        $duedates = array();
        if ( ReqScope::currentContextId() !== 0 ) {
            $duedates = GradeUtil::loadDueDatesForDisplay(ReqScope::currentContextId());
        }

        $showDueDateToggle = ReqScope::isLoggedIn() && ReqScope::currentContextId() !== 0
            && GradeUtil::contextHasAnyDueDate(ReqScope::currentContextId());

        $toolbar_html = null;
        if ( $this->isInstructor() || $showDueDateToggle ) {
            ob_start();
            echo('<div style="display:flex;flex-wrap:wrap;gap:0.35em;justify-content:flex-end;align-items:center;">');
            if ( $showDueDateToggle ) {
                LTIX::getConnection();
                $mm = Membership::ensureInSession(ReqScope::currentContextId(), ReqScope::loggedInUserId());
                $hiding = empty($mm->viewDueDates);
                $btnLabel = $hiding ? __('Show due dates') : __('Hide due dates');
                $toggleAction = U::addSession($this->toolHome(self::ROUTE) . '/toggle-view-due-dates');
                echo('<form method="post" action="'.htmlspecialchars($toggleAction).'" style="display:inline;margin:0;">');
                echo(self::csrfField());
                echo('<button type="submit" class="btn btn-default btn-sm">'.htmlspecialchars($btnLabel).'</button>');
                echo('</form>');
            }
            if ( $this->isInstructor() ) {
                $grades_home = $this->toolHome(Grades::ROUTE);
                $md_url = U::addSession($grades_home . '/manage');
                echo('<a href="'.htmlspecialchars($md_url).'" class="btn btn-default btn-sm"><i class="fa fa-calendar" aria-hidden="true"></i> '.__('Manage grade book').'</a>');
                $sp_url = U::addSession($grades_home . '/student-progress');
                echo('<a href="'.htmlspecialchars($sp_url).'" class="btn btn-default btn-sm"><i class="fa fa-line-chart" aria-hidden="true"></i> '.__('Student Progress').'</a>');
            }
            echo('</div>');
            $toolbar_html = ob_get_clean();
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        if ( ! ReqScope::isLoggedIn() ) {
            $login_url = htmlspecialchars(U::addSession(Login::loginUrl()));
            echo('<p><a href="'.$login_url.'">'.__('Log in').'</a> ');
            echo(__('to see your scores on these assignments.').'</p>'."\n");
        }
        self::renderAssignments($l, $allgrades, $alldates, false, $duedates, $toolbar_html, $alllinkids, $this->isInstructor());
        $OUTPUT->footer();
    }

    /**
     * Old assignments URL. The page now lives on the grade book.
     */
    public function studentProgress(Request $request)
    {
        return new RedirectResponse($this->gradesLessonUrl('/student-progress', $request));
    }

    public function manageDueDates(Request $request)
    {
        return new RedirectResponse($this->gradesLessonUrl('/manage-due-dates', $request));
    }

    public function manageDueDatesPost(Request $request)
    {
        return (new Grades())->manageDueDatesPost($request);
    }

    public function clearAllDueDatesPost(Request $request)
    {
        return (new Grades())->clearAllDueDatesPost($request);
    }

    public function applyWeeklyDueDatesPost(Request $request)
    {
        return (new Grades())->applyWeeklyDueDatesPost($request);
    }

    public function addMissingLinkRowsPost(Request $request)
    {
        return (new Grades())->addMissingLinkRowsPost($request);
    }

    /**
     * @param string $path
     */
    private function gradesLessonUrl($path, Request $request)
    {
        $url = $this->toolHome(Grades::ROUTE).$path;
        $qs = $request->getQueryString();
        if ( is_string($qs) && $qs !== '' ) {
            $url .= '?'.$qs;
        }
        return U::addSession($url);
    }

    /**
     * Toggle lti_membership.viewDueDates for the current user in this context.
     */
    public function toggleViewDueDatesPost(Request $request)
    {
        global $CFG, $PDOX;

        if ( ! Manifest::hasCurrent() ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons) or an active course manifest');
        }
        $this->requireAuth();
        $home = U::addSession($this->toolHome(self::ROUTE));
        $csrf = self::requireCsrf($home);
        if ( $csrf ) {
            return $csrf;
        }
        $context_id = ReqScope::currentContextId();
        $user_id = ReqScope::loggedInUserId();
        if ( ! $context_id || ! $user_id ) {
            U::flashError(__('Context required.'));
            return new RedirectResponse($home);
        }
        $cid = (int) $context_id;
        $uid = (int) $user_id;

        LTIX::getConnection();

        if ( ! GradeUtil::contextHasAnyDueDate($cid) ) {
            U::flashError(__('This course has no due dates to show or hide.'));
            return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE)));
        }

        $m = Membership::ensureInSession($cid, $uid);
        $newVal = empty($m->viewDueDates) ? 1 : 0;

        $p = $CFG->dbprefix;
        $sql = "UPDATE {$p}lti_membership SET viewDueDates = :VDD, updated_at = NOW()
            WHERE context_id = :CID AND user_id = :UID AND deleted = 0";
        $stmt = $PDOX->queryReturnError($sql, array(
            ':CID' => $cid,
            ':UID' => $uid,
            ':VDD' => $newVal,
        ));
        if ( ! $stmt->success ) {
            U::flashError(__('Could not update your preference. Please try again.'));
            return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE)));
        }
        if ( $stmt->rowCount() < 1 ) {
            U::flashError(__('No course membership was found. Open this tool again from your course, then try again.'));
            return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE)));
        }

        $this->invalidateMembershipAndDueDatesCache();
        if ( $newVal ) {
            U::flashSuccess(__('Due dates are shown for you in this course.'));
        } else {
            U::flashSuccess(__('Due dates are hidden for you in this course.'));
        }
        return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE)));
    }


    private static function renderAssignmentItem($resource_link_id, $title, $allgrades, $alldates, $duedates = array(), $lti_item = null, $module_anchor = '', $alllinkids = array(), $is_instructor = false) {
        $graded = LessonsService::ltiLaunchIsGraded($lti_item);
        echo('<li class="tsugi-assignments-item">');
        echo('<span class="tsugi-assignments-status">');
        if ( ! $graded ) {
            echo('<i class="fa fa-square-o text-muted" aria-hidden="true"></i>');
        } else if ( isset($allgrades[$resource_link_id]) ) {
            if ( $allgrades[$resource_link_id] > 0.8 ) {
                echo('<i class="fa fa-check-square-o text-success" aria-hidden="true"></i>');
            } else {
                echo('<i class="fa fa-square-o text-warning" aria-hidden="true"></i>');
            }
        } else {
            echo('<i class="fa fa-square-o text-danger" aria-hidden="true"></i>');
        }
        echo('</span>');
        echo('<span class="tsugi-assignments-title-row">');
        $title_esc = htmlspecialchars($title);
        if ( is_string($module_anchor) && $module_anchor !== '' && is_string($resource_link_id) && $resource_link_id !== '' ) {
            $mod_href = U::get_rest_parent() . '/lessons/' . rawurlencode($module_anchor);
            $mod_href = htmlspecialchars($mod_href . '#' . LessonsService::domIdForResourceLink($resource_link_id));
            $jump_lbl = htmlspecialchars(__('Open in module'), ENT_QUOTES, 'UTF-8');
            echo('<a class="tsugi-assignments-title tsugi-assignments-title-link" href="'.$mod_href.'" title="'.$jump_lbl.'">'.$title_esc.'</a>');
        } else {
            echo('<span class="tsugi-assignments-title">'.$title_esc.'</span>');
        }
        if ( LessonsService::shouldShowAssignmentResultSignature($resource_link_id, $allgrades, $alllinkids, $is_instructor) ) {
            $link_id = isset($alllinkids[$resource_link_id]) ? (int) $alllinkids[$resource_link_id] : 0;
            echo(\Tsugi\Controllers\Lessons::resultLinkSignatureMarkup($resource_link_id, $link_id));
        }
        \Tsugi\Controllers\Lessons::echoDueDateBadgeForResourceLink($resource_link_id, $allgrades, $duedates, $graded);
        echo('</span>');
        if ( $graded && isset($allgrades[$resource_link_id]) ) {
            $datestring = U::get($alldates, $resource_link_id, "");
            if ( strlen($datestring) > 0 ) {
                $datestring = " (".substr($datestring,0,10).")";
            }
            echo('<span class="tsugi-assignments-score">Score: '.(100*$allgrades[$resource_link_id]).$datestring.'</span>');
        } else {
            echo('<span class="tsugi-assignments-score">&nbsp;</span>');
        }
        echo('</li>');
    }

    public static function renderAssignments(\Tsugi\Services\Lessons\LessonsService $lessons, $allgrades, $alldates, $buffer=false, $duedates=array(), $toolbar_html=null, $alllinkids=array(), $is_instructor=false)
    {
        ob_start();
        \Tsugi\Controllers\Lessons::printLtiProgressStyles();
        echo('<h1>'.$lessons->lessons->title."</h1>\n");
        if ( is_string($toolbar_html) && $toolbar_html !== '' ) {
            echo('<div class="clearfix tsugi-assignments-actions" style="margin-bottom:0.75em;">' . "\n");
            echo('<div class="pull-right">' . $toolbar_html . '</div>' . "\n");
            echo("</div>\n");
        }
        $displayname = U::get($_SESSION, 'displayname');
        $email = U::get($_SESSION, 'email');
        if ( is_string($displayname) || is_string($email) ) {
            $output = "";
            if ( is_string($displayname) ) $output .= $displayname;
            if ( is_string($displayname) && is_string($email) ) $output .= ' ';
            if ( is_string($email) ) $output .= $email;
            echo("<p>Grades for: ");
            echo(htmlentities($output));
            $sig = md5("42 ".$output);
            echo(" | ".substr($sig,0,5)."</p>\n");
        }
        echo('<ul class="tsugi-assignments-progress-list" role="list">'."\n");
        foreach($lessons->lessons->modules as $module) {
            // Items array takes precedence - check items first
            $has_assignments = false;
            if ( isset($module->items) ) {
                foreach($module->items as $item) {
                    if ( LessonsNormalize::isAssignmentLti($item)
                        && LessonsService::ltiLaunchIsGraded($item) ) {
                        $has_assignments = true;
                        break;
                    }
                }
            } else if ( isset($module->lti) ) {
                $ltis_check = $module->lti;
                if ( ! is_array($ltis_check) ) {
                    $ltis_check = array($ltis_check);
                }
                foreach ( $ltis_check as $lti_check ) {
                    if ( isset($lti_check->resource_link_id) && LessonsService::ltiLaunchIsGraded($lti_check) ) {
                        $has_assignments = true;
                        break;
                    }
                }
            }
            if ( !$has_assignments ) continue;
            
            $href = U::get_rest_parent() . '/lessons/' . urlencode($module->anchor);
            echo('<li class="tsugi-assignments-module">');
            echo('<a href="'.$href.'" class="tsugi-assignments-module-link">'.htmlspecialchars($module->title).'</a>');
            echo('<ul class="tsugi-assignments-items" role="list">');
            
            // Process items array first (takes precedence)
            if ( isset($module->items) ) {
                foreach($module->items as $item) {
                    if ( ! LessonsNormalize::isAssignmentLti($item) ) continue;
                    if ( ! LessonsService::ltiLaunchIsGraded($item) ) continue;
                    self::renderAssignmentItem($item->resource_link_id, isset($item->title) ? $item->title : (isset($item->text) ? $item->text : 'Assignment'), $allgrades, $alldates, $duedates, $item, isset($module->anchor) ? $module->anchor : '', $alllinkids, $is_instructor);
                }
            } else {
                // Process legacy lti array only if items is not present
                if ( isset($module->lti) ) {
                    $ltis = $module->lti;
                    if ( ! is_array($ltis) ) $ltis = array($ltis);
                    foreach($ltis as $lti) {
                        if ( !isset($lti->resource_link_id) ) continue;
                        if ( ! LessonsService::ltiLaunchIsGraded($lti) ) continue;
                        self::renderAssignmentItem($lti->resource_link_id, $lti->title, $allgrades, $alldates, $duedates, $lti, isset($module->anchor) ? $module->anchor : '', $alllinkids, $is_instructor);
                    }
                }
            }
            echo('</ul></li>');
        }
        echo('</ul>'."\n");
        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);
    }

}

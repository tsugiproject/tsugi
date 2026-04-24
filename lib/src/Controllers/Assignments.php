<?php

namespace Tsugi\Controllers;


use \Tsugi\Util\U;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tsugi\Grades\GradeUtil;
use Tsugi\Core\LTIX;
use Tsugi\Core\Membership;
use Tsugi\UI\Table;

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
     * @return array<string,array{start_datetime:mixed,end_datetime:mixed}> link_key => row
     */
    private function loadDueDatesByLinkKey($context_id) {
        return GradeUtil::loadDueDatesForContext($context_id);
    }

    private function invalidateDueDatesSessionCache() {
        $context_id = U::currentContextId();
        if ( $context_id ) {
            GradeUtil::invalidateDueDatesCache((int) $context_id);
        }
    }

    /**
     * After lti_membership or due-date visibility changes for this context.
     */
    private function invalidateMembershipAndDueDatesCache() {
        $context_id = U::currentContextId();
        if ( ! $context_id ) {
            return;
        }
        $cid = (int) $context_id;
        Membership::invalidateSessionCache($cid);
        GradeUtil::invalidateDueDatesCache($cid);
    }

    /**
     * Value for HTML date input (YYYY-MM-DD) from a stored MySQL datetime.
     */
    private static function toDateInputValue($mysql) {
        if ( $mysql === null || $mysql === '' ) {
            return '';
        }
        if ( preg_match('/^(\d{4}-\d{2}-\d{2})/', $mysql, $m ) ) {
            return $m[1];
        }
        return '';
    }

    /**
     * Parse a due date from the UI (calendar day only). Stored as 11:59:59 PM that day.
     *
     * @return string|null MySQL datetime or null to clear
     */
    private static function parseDueDateEndOfDay($s) {
        $s = trim((string) $s);
        if ( $s === '' ) {
            return null;
        }
        if ( preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ) {
            return $s . ' 23:59:59';
        }
        return null;
    }

    private function tableExists($table_name) {
        global $PDOX;
        $row = $PDOX->rowDie(
            "SELECT 1 AS present
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = :TN",
            array(':TN' => $table_name)
        );
        return is_array($row);
    }

    public function get(Request $request)
    {
        global $CFG, $OUTPUT;

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }

        $l = new \Tsugi\UI\Lessons($CFG->lessons);

        $allgrades = array();
        $alldates = array();
        $rows = GradeUtil::loadGradesCurrentUser();
        foreach ( $rows as $row ) {
            $allgrades[$row['resource_link_id']] = $row['grade'];
            $alldates[$row['resource_link_id']] = $row['updated_at'];
        }

        $duedates = array();
        if ( U::currentContextId() !== 0 ) {
            $duedates = GradeUtil::loadDueDatesForDisplay(U::currentContextId());
        }

        $showDueDateToggle = U::isLoggedIn() && U::currentContextId() !== 0
            && GradeUtil::contextHasAnyDueDate(U::currentContextId());

        $toolbar_html = null;
        if ( $this->isInstructor() || $showDueDateToggle ) {
            ob_start();
            echo('<div style="display:flex;flex-wrap:wrap;gap:0.35em;justify-content:flex-end;align-items:center;">');
            if ( $showDueDateToggle ) {
                LTIX::getConnection();
                $mm = Membership::ensureInSession(U::currentContextId(), U::loggedInUserId());
                $hiding = empty($mm->viewDueDates);
                $btnLabel = $hiding ? __('Show due dates') : __('Hide due dates');
                $toggleAction = U::addSession($this->toolHome(self::ROUTE) . '/toggle-view-due-dates');
                echo('<form method="post" action="'.htmlspecialchars($toggleAction).'" style="display:inline;margin:0;">');
                echo('<button type="submit" class="btn btn-default btn-sm">'.htmlspecialchars($btnLabel).'</button>');
                echo('</form>');
            }
            if ( $this->isInstructor() ) {
                $md_url = U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates');
                echo('<a href="'.htmlspecialchars($md_url).'" class="btn btn-default btn-sm"><i class="fa fa-calendar" aria-hidden="true"></i> '.__('Manage due dates').'</a>');
                $sp_url = U::addSession($this->toolHome(self::ROUTE) . '/student-progress');
                echo('<a href="'.htmlspecialchars($sp_url).'" class="btn btn-default btn-sm"><i class="fa fa-line-chart" aria-hidden="true"></i> '.__('Student Progress').'</a>');
            }
            echo('</div>');
            $toolbar_html = ob_get_clean();
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        $l->renderAssignments($allgrades, $alldates, false, $duedates, $toolbar_html);
        $OUTPUT->footer();
    }

    public function studentProgress(Request $request)
    {
        global $CFG, $OUTPUT, $PDOX;

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }
        $this->requireInstructor(U::addSession($this->toolHome(self::ROUTE)));

        LTIX::getConnection();
        $context_id = U::currentContextId();
        $p = $CFG->dbprefix;

        $l = new \Tsugi\UI\Lessons($CFG->lessons);
        $assignment_items = $l->enumerateLtiAssignmentItems(true);
        $assignment_rlids = array();
        foreach ( $assignment_items as $it ) {
            if ( isset($it['resource_link_id']) && U::isNotEmpty($it['resource_link_id']) ) {
                $assignment_rlids[$it['resource_link_id']] = true;
            }
        }
        $total_assignments = count($assignment_rlids);

        $has_discussions = $this->tableExists($CFG->dbprefix.'tdiscus_comment') && $this->tableExists($CFG->dbprefix.'tdiscus_thread');
        $comment_select = "0 AS comment_count";
        $question_select = "0 AS threads_started";
        $discussion_joins = "";
        if ( $has_discussions ) {
            $comment_select = "COALESCE(CC.comment_count, 0) AS comment_count";
            $question_select = "COALESCE(QQ.question_count, 0) AS threads_started";
            $discussion_joins = "
        LEFT JOIN (
            SELECT C.user_id, COUNT(*) AS comment_count
            FROM {$p}tdiscus_comment C
            JOIN {$p}tdiscus_thread T ON T.thread_id = C.thread_id
            JOIN {$p}lti_link LL ON LL.link_id = T.link_id
            WHERE LL.context_id = :CID2
            GROUP BY C.user_id
        ) CC ON CC.user_id = U.user_id
        LEFT JOIN (
            SELECT T.user_id, COUNT(*) AS question_count
            FROM {$p}tdiscus_thread T
            JOIN {$p}lti_link LL ON LL.link_id = T.link_id
            WHERE LL.context_id = :CID4
            GROUP BY T.user_id
        ) QQ ON QQ.user_id = U.user_id";
        }

        $assignment_grade_clause = "FALSE";
        $params = array(
            ':CID' => $context_id,
            ':CID3' => $context_id,
        );
        if ( $total_assignments > 0 ) {
            $rlid_placeholders = array();
            $i = 0;
            foreach ( array_keys($assignment_rlids) as $rlid ) {
                $i++;
                $ph = ':RLID'.$i;
                $rlid_placeholders[] = $ph;
                $params[$ph] = $rlid;
            }
            $assignment_grade_clause = "L.link_key IN (".implode(',', $rlid_placeholders).")";
        }
        if ( $has_discussions ) {
            $params[':CID2'] = $context_id;
            $params[':CID4'] = $context_id;
        }
        $group_by_discussion = $has_discussions ? ", QQ.question_count, CC.comment_count" : "";

        $assignment_count_sql = (int) $total_assignments;
        $sql = "SELECT
            U.user_id,
            U.displayname,
            U.email,
            M.created_at AS enrolled_at,
            MAX(COALESCE(R.updated_at, M.updated_at)) AS last_visited_at,
            COUNT(DISTINCT CASE
                WHEN R.grade IS NOT NULL
                    AND R.grade >= 0.8
                    AND {$assignment_grade_clause}
                THEN L.link_key
                ELSE NULL
            END) AS completed_assignments,
            CASE
                WHEN {$assignment_count_sql} > 0 THEN ROUND(
                    100 * COUNT(DISTINCT CASE
                        WHEN R.grade IS NOT NULL
                            AND R.grade >= 0.8
                            AND {$assignment_grade_clause}
                        THEN L.link_key
                        ELSE NULL
                    END) / {$assignment_count_sql},
                    0
                )
                ELSE 0
            END AS progress_pct,
            {$question_select},
            {$comment_select}
        FROM {$p}lti_membership M
        JOIN {$p}lti_user U
            ON U.user_id = M.user_id
        LEFT JOIN {$p}lti_result R
            ON R.user_id = U.user_id
            AND R.deleted = 0
        LEFT JOIN {$p}lti_link L
            ON L.link_id = R.link_id
            AND L.context_id = :CID
        {$discussion_joins}
        WHERE M.context_id = :CID3
          AND M.deleted = 0
        GROUP BY U.user_id, U.displayname, U.email, M.created_at{$group_by_discussion}";

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();

        $back = U::addSession($this->toolHome(self::ROUTE));
        echo('<p><a href="'.htmlspecialchars($back).'"><i class="fa fa-arrow-left" aria-hidden="true"></i> '.__('Back to Assignments').'</a></p>'."\n");
        echo('<h1>'.__('Student Progress')."</h1>\n");
        echo('<p>'.__('Instructor view of enrollment, recent activity, assignment completion, and discussion participation.')."</p>\n");
        echo('<p class="text-muted">'.sprintf(__('Assignments complete uses %1$d lesson assignment(s) with grade >= 80%%.'), $total_assignments)."</p>\n");

        $searchfields = array(
            "U.displayname",
            "U.email",
        );
        $orderfields = array(
            "last_visited_at",
            "displayname",
            "email",
            "enrolled_at",
            "progress_pct",
            "threads_started",
            "comment_count",
            "completed_assignments",
        );

        $table_params = $request->query->all();
        if ( ! isset($table_params['order_by']) ) {
            $table_params['order_by'] = 'last_visited_at';
            $table_params['desc'] = 1;
        }
        if ( ! isset($table_params['page_length']) ) {
            $table_params['page_length'] = 25;
        }

        Table::pagedAuto($sql, $params, $searchfields, $orderfields, false, $table_params);

        $OUTPUT->footer();
    }

    /**
     * Toggle lti_membership.viewDueDates for the current user in this context.
     */
    public function toggleViewDueDatesPost(Request $request)
    {
        global $CFG, $PDOX;

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }
        $this->requireAuth();
        $context_id = U::currentContextId();
        $user_id = U::loggedInUserId();
        if ( ! $context_id || ! $user_id ) {
            U::flashError(__('Context required.'));
            return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE)));
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

    public function manageDueDates(Request $request)
    {
        global $CFG, $OUTPUT;

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }
        $this->requireInstructor(U::addSession($this->toolHome(self::ROUTE)));

        $context_id = U::currentContextId();
        $l = new \Tsugi\UI\Lessons($CFG->lessons);
        $items = $l->enumerateLtiAssignmentItems(true);
        $dueMap = $this->loadDueDatesByLinkKey($context_id);

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();

        $back = U::addSession($this->toolHome(self::ROUTE));
        $action = U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates');

        echo('<p><a href="'.htmlspecialchars($back).'"><i class="fa fa-arrow-left" aria-hidden="true"></i> '.__('Back to Assignments').'</a></p>'."\n");
        echo('<h1>'.__('Manage due dates')."</h1>\n");
        echo('<p>'.__('Set optional due dates for each assignment (end of that calendar day, 11:59 PM). They are stored on the LTI resource link for this course (end_datetime).')."</p>\n");

        if ( count($items) < 1 ) {
            echo('<p>'.__('No LTI assignments are defined in the lessons configuration.')."</p>\n");
            $OUTPUT->footer();
            return;
        }

        $missingRlids = array();
        foreach ( $items as $it ) {
            $r = $it['resource_link_id'];
            if ( ! array_key_exists($r, $dueMap) ) {
                $missingRlids[$r] = true;
            }
        }
        $missingCount = count($missingRlids);

        if ( $missingCount > 0 ) {
            $addLinksAction = U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates/add-link-rows');
            echo('<div class="well" style="margin-bottom:1.5em;">');
            echo('<p><strong>'.__('Add missing link rows').'</strong></p>');
            echo('<p>'.__('Creates an lti_link row for each lesson assignment that does not have one in this course yet, using the same insert as an LTI launch (<code>ON DUPLICATE KEY UPDATE</code>). If a row already exists for that resource link in this context, nothing is duplicated; a later launch only refreshes <code>updated_at</code>.').'</p>');
            echo('<p class="text-warning"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ');
            echo(__('The resource link id in your lessons file must match the LMS resource link id when students launch, or launches will create a different row.'));
            echo('</p>');
            echo('<form method="post" action="'.htmlspecialchars($addLinksAction).'">');
            echo('<button type="submit" class="btn btn-default">');
            echo('<i class="fa fa-plus-circle" aria-hidden="true"></i> '.__('Add missing link rows').' ('.$missingCount.')');
            echo('</button>');
            echo('</form>');
            echo('</div>');
        }

        $weeklyAction = U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates/apply-weekly');
        echo('<div class="well" style="margin-bottom:1.5em;">');
        echo('<p><strong>'.__('Weekly due dates').'</strong></p>');
        echo('<p>'.__('Lesson modules are ordered as in your lessons file. Pick the due date for all assignments in the first module (week 1). Each later module adds one week; every due is 11:59 PM on that calendar day.').'</p>');
        echo('<form method="post" action="'.htmlspecialchars($weeklyAction).'" class="form-inline">');
        echo('<div class="form-group" style="margin-right:1em;">');
        echo('<label for="week1_due" style="margin-right:0.5em;">'.__('First module due date').'</label> ');
        echo('<input type="date" class="form-control" id="week1_due" name="week1_due" required>');
        echo('</div> ');
        echo('<button type="submit" class="btn btn-default"><i class="fa fa-magic" aria-hidden="true"></i> '.__('Apply weekly due dates').'</button>');
        echo('</form>');
        echo('<p class="text-muted" style="margin-top:0.75em;margin-bottom:0;">'.__('Only assignments that already have an LTI link row in this course are updated; add link rows first if needed.').'</p>');
        echo('</div>');

        $clearAllAction = U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates/clear-all');
        echo('<div class="well" style="margin-bottom:1.5em;">');
        echo('<p><strong>'.__('Clear all due dates').'</strong></p>');
        echo('<p>'.__('Sets due date to empty for every lesson assignment that has an LTI link row in this course.').'</p>');
        echo('<form method="post" action="'.htmlspecialchars($clearAllAction).'" onsubmit=\'return confirm('.
            json_encode(__('Remove all due dates for lesson assignments in this course?')).');\'>');
        echo('<button type="submit" class="btn btn-warning">');
        echo('<i class="fa fa-eraser" aria-hidden="true"></i> '.__('Clear all due dates'));
        echo('</button>');
        echo('</form>');
        echo('</div>');

        echo('<form method="post" action="'.htmlspecialchars($action).'" class="table-responsive">'."\n");
        echo('<table class="table table-bordered table-striped">'."\n");
        echo('<thead><tr><th>'.__('Module').'</th><th>'.__('Assignment').'</th><th>'.__('Resource link').'</th><th>'.__('Due date').'</th></tr></thead>'."\n");
        echo("<tbody>\n");
        foreach ( $items as $it ) {
            $rlid = $it['resource_link_id'];
            $hasRow = array_key_exists($rlid, $dueMap);
            $endVal = '';
            if ( $hasRow ) {
                $endVal = self::toDateInputValue($dueMap[$rlid]['end_datetime']);
            }
            echo('<tr>');
            echo('<td>'.htmlspecialchars($it['module_title']).'</td>');
            echo('<td>'.htmlspecialchars($it['item_title']).'</td>');
            echo('<td><code>'.htmlspecialchars($rlid).'</code>');
            if ( ! $hasRow ) {
                echo('<br><span class="text-warning"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> '.__('No LTI link row yet').'</span>');
            }
            echo('</td>');
            echo('<td>');
            if ( $hasRow ) {
                $dueAria = sprintf(
                    __('Due date for %1$s (resource link %2$s)'),
                    $it['item_title'],
                    $rlid
                );
                echo('<input type="date" class="form-control" name="end[]" value="'.htmlspecialchars($endVal).'" aria-label="'.htmlspecialchars($dueAria, ENT_QUOTES, 'UTF-8').'">');
                echo('<input type="hidden" name="rlid[]" value="'.htmlspecialchars($rlid).'">');
            } else {
                echo('<span class="text-muted">—</span>');
            }
            echo('</td>');
            echo("</tr>\n");
        }
        echo("</tbody></table>\n");
        echo('<p><button type="submit" class="btn btn-primary">'.__('Save due dates').'</button></p>'."\n");
        echo("</form>\n");

        $OUTPUT->footer();
    }

    public function manageDueDatesPost(Request $request)
    {
        global $CFG, $PDOX;

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }
        $this->requireInstructor(U::addSession($this->toolHome(self::ROUTE)));

        $context_id = U::currentContextId();
        $l = new \Tsugi\UI\Lessons($CFG->lessons);
        $allowed = array();
        foreach ( $l->enumerateLtiAssignmentItems(true) as $it ) {
            $allowed[$it['resource_link_id']] = true;
        }

        $rlids = U::get($_POST, 'rlid');
        $ends = U::get($_POST, 'end');
        if ( ! is_array($rlids) || ! is_array($ends) ) {
            U::flashError(__('Invalid form submission.'));
            return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates'));
        }

        $n = count($rlids);
        if ( $n !== count($ends) ) {
            U::flashError(__('Mismatched form fields.'));
            return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates'));
        }

        $p = $CFG->dbprefix;
        $updated = 0;
        for ( $i = 0; $i < $n; $i++ ) {
            $rlid = $rlids[$i];
            if ( ! isset($allowed[$rlid]) ) {
                continue;
            }
            $endSql = self::parseDueDateEndOfDay($ends[$i]);
            if ( $ends[$i] !== '' && $endSql === null ) {
                $this->invalidateDueDatesSessionCache();
                U::flashError(__('Invalid due date for resource link: ').$rlid);
                return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates'));
            }
            $stmt = $PDOX->queryReturnError(
                "UPDATE {$p}lti_link SET end_datetime = :E, updated_at = NOW()
                 WHERE context_id = :CID AND link_key = :LK AND (deleted IS NULL OR deleted = 0)",
                array(
                    ':E' => $endSql,
                    ':CID' => $context_id,
                    ':LK' => $rlid,
                )
            );
            if ( ! $stmt->success ) {
                $this->invalidateDueDatesSessionCache();
                U::flashError(__('Could not save due dates. Please try again.'));
                return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates'));
            }
            $updated += $stmt->rowCount();
        }

        $this->invalidateDueDatesSessionCache();
        U::flashSuccess(__('Saved due dates.').' ('.$updated.' '.__('link row(s) updated').').');
        return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates'));
    }

    /**
     * Set end_datetime to NULL for all enumerated lesson assignments that have a link row in this context.
     */
    public function clearAllDueDatesPost(Request $request)
    {
        global $CFG, $PDOX;

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }
        $this->requireInstructor(U::addSession($this->toolHome(self::ROUTE)));

        $context_id = U::currentContextId();
        $l = new \Tsugi\UI\Lessons($CFG->lessons);
        $allowed = array();
        foreach ( $l->enumerateLtiAssignmentItems(true) as $it ) {
            $allowed[$it['resource_link_id']] = true;
        }

        $p = $CFG->dbprefix;
        $updated = 0;
        foreach ( array_keys($allowed) as $rlid ) {
            $stmt = $PDOX->queryReturnError(
                "UPDATE {$p}lti_link SET end_datetime = NULL, updated_at = NOW()
                 WHERE context_id = :CID AND link_key = :LK AND (deleted IS NULL OR deleted = 0)",
                array(
                    ':CID' => $context_id,
                    ':LK' => $rlid,
                )
            );
            if ( ! $stmt->success ) {
                $this->invalidateDueDatesSessionCache();
                U::flashError(__('Could not clear due dates. Please try again.'));
                return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates'));
            }
            $updated += $stmt->rowCount();
        }

        $this->invalidateDueDatesSessionCache();
        U::flashSuccess(__('Cleared due dates.').' ('.$updated.' '.__('link row(s) updated').').');
        return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates'));
    }

    /**
     * Set end_datetime for each link from a base due date on module 0, plus 7 days per module index.
     */
    public function applyWeeklyDueDatesPost(Request $request)
    {
        global $CFG, $PDOX;

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }
        $this->requireInstructor(U::addSession($this->toolHome(self::ROUTE)));

        $context_id = U::currentContextId();
        $raw = U::get($_POST, 'week1_due', '');
        $baseSql = self::parseDueDateEndOfDay($raw);
        if ( $baseSql === null ) {
            U::flashError(__('Enter a valid first-module due date.'));
            return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates'));
        }

        $baseTs = strtotime($baseSql);
        if ( $baseTs === false ) {
            U::flashError(__('Enter a valid first-module due date.'));
            return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates'));
        }

        $l = new \Tsugi\UI\Lessons($CFG->lessons);
        $items = $l->enumerateLtiAssignmentItems(true);
        $dueMap = $this->loadDueDatesByLinkKey($context_id);

        $p = $CFG->dbprefix;
        $updated = 0;
        $skipped = 0;
        foreach ( $items as $it ) {
            $rlid = $it['resource_link_id'];
            if ( ! array_key_exists($rlid, $dueMap) ) {
                $skipped++;
                continue;
            }
            $modIdx = isset($it['module_index']) ? (int) $it['module_index'] : 0;
            $dueTs = strtotime('+' . ($modIdx * 7) . ' days', $baseTs);
            if ( $dueTs === false ) {
                $this->invalidateDueDatesSessionCache();
                U::flashError(__('Could not compute a due date for resource link: ').$rlid);
                return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates'));
            }
            $endSql = date('Y-m-d H:i:s', $dueTs);
            $stmt = $PDOX->queryReturnError(
                "UPDATE {$p}lti_link SET end_datetime = :E, updated_at = NOW()
                 WHERE context_id = :CID AND link_key = :LK AND (deleted IS NULL OR deleted = 0)",
                array(
                    ':E' => $endSql,
                    ':CID' => $context_id,
                    ':LK' => $rlid,
                )
            );
            if ( ! $stmt->success ) {
                $this->invalidateDueDatesSessionCache();
                U::flashError(__('Could not save due dates. Please try again.'));
                return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates'));
            }
            $updated += $stmt->rowCount();
        }

        $msg = __('Applied weekly due dates.').' '.sprintf(__('Updated %d link row(s).'), $updated);
        if ( $skipped > 0 ) {
            $msg .= ' '.sprintf(__('Skipped %d assignment(s) with no link row yet.'), $skipped);
        }
        $this->invalidateDueDatesSessionCache();
        U::flashSuccess($msg);
        return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates'));
    }

    /**
     * Insert lti_link rows for lesson assignments that have no row in this context yet.
     * Uses the same statement as LTIX::adjustData() so a later launch does not create a duplicate.
     */
    public function addMissingLinkRowsPost(Request $request)
    {
        global $CFG, $PDOX;

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }
        $this->requireInstructor(U::addSession($this->toolHome(self::ROUTE)));

        $context_id = U::currentContextId();
        $l = new \Tsugi\UI\Lessons($CFG->lessons);
        $items = $l->enumerateLtiAssignmentItems(true);
        $dueMap = $this->loadDueDatesByLinkKey($context_id);

        $p = $CFG->dbprefix;
        $sql = "INSERT INTO {$p}lti_link
            ( link_key, link_sha256, settings_url, title, context_id, path, created_at, updated_at ) VALUES
                ( :link_key, :link_sha256, :settings_url, :title, :context_id, :path, NOW(), NOW() )
            ON DUPLICATE KEY UPDATE updated_at = NOW()";

        $attempted = 0;
        $inserted = 0;
        foreach ( $items as $it ) {
            $rlid = $it['resource_link_id'];
            if ( array_key_exists($rlid, $dueMap) ) {
                continue;
            }
            $attempted++;
            $stmt = $PDOX->queryReturnError($sql, array(
                ':link_key' => $rlid,
                ':link_sha256' => lti_sha256($rlid),
                ':settings_url' => null,
                ':title' => $it['item_title'],
                ':context_id' => $context_id,
                ':path' => null,
            ));
            if ( ! $stmt->success ) {
                $this->invalidateDueDatesSessionCache();
                U::flashError(__('Could not add link rows. Please try again.'));
                return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates'));
            }
            // MySQL: 1 = new row, 2 = duplicate updated (same as launch)
            $rc = $stmt->rowCount();
            if ( $rc === 1 ) {
                $inserted++;
            }
            $dueMap[$rlid] = array('start_datetime' => null, 'end_datetime' => null);
        }

        if ( $attempted < 1 ) {
            U::flashSuccess(__('No missing link rows; nothing to add.'));
        } else {
            $this->invalidateDueDatesSessionCache();
            U::flashSuccess(
                __('Added link rows.').' '.sprintf(__('New inserts: %d.'), $inserted).' '.
                sprintf(__('Missing assignments processed: %d. Same insert as launch—no duplicate if a row already existed.'), $attempted)
            );
        }
        return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates'));
    }
}

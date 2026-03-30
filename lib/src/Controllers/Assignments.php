<?php

namespace Tsugi\Controllers;

use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tsugi\Grades\GradeUtil;
use Tsugi\Util\U;

class Assignments extends Tool {

    const ROUTE = '/assignments';

    public static function routes(Application $app, $prefix=self::ROUTE) {
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
        $app->router->get($prefix, 'Assignments@get');
        $app->router->get($prefix.'/', 'Assignments@get');
    }

    /**
     * @return array<string,array{start_datetime:mixed,end_datetime:mixed}> link_key => row
     */
    private function loadDueDatesByLinkKey($context_id) {
        return GradeUtil::loadDueDatesForContext($context_id);
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

    public function get(Request $request)
    {
        global $CFG, $OUTPUT;

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }

        $l = new \Tsugi\UI\Lessons($CFG->lessons);

        $allgrades = array();
        $alldates = array();
        if ( isset($_SESSION['id']) && isset($_SESSION['context_id']) ) {
            $rows = GradeUtil::loadGradesForCourse($_SESSION['id'], $_SESSION['context_id']);
            foreach ( $rows as $row ) {
                $allgrades[$row['resource_link_id']] = $row['grade'];
                $alldates[$row['resource_link_id']] = $row['updated_at'];
            }
        }

        $duedates = array();
        if ( isset($_SESSION['context_id']) ) {
            $duedates = $this->loadDueDatesByLinkKey($_SESSION['context_id']);
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        if ( $this->isInstructor() ) {
            $md_url = U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates');
            echo('<p class="text-right" style="margin-bottom:0.5em;">');
            echo('<a href="'.htmlspecialchars($md_url).'" class="btn btn-default btn-sm"><i class="fa fa-calendar" aria-hidden="true"></i> '.__('Manage due dates').'</a>');
            echo("</p>\n");
        }
        $l->renderAssignments($allgrades, $alldates, false, $duedates);
        $OUTPUT->footer();
    }

    public function manageDueDates(Request $request)
    {
        global $CFG, $OUTPUT;

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }
        $this->requireInstructor(U::addSession($this->toolHome(self::ROUTE)));

        $context_id = U::get($_SESSION, 'context_id');
        $l = new \Tsugi\UI\Lessons($CFG->lessons);
        $items = $l->enumerateLtiAssignmentItems();
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

        $context_id = U::get($_SESSION, 'context_id');
        $l = new \Tsugi\UI\Lessons($CFG->lessons);
        $allowed = array();
        foreach ( $l->enumerateLtiAssignmentItems() as $it ) {
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
                U::flashError(__('Could not save due dates. Please try again.'));
                return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates'));
            }
            $updated += $stmt->rowCount();
        }

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

        $context_id = U::get($_SESSION, 'context_id');
        $l = new \Tsugi\UI\Lessons($CFG->lessons);
        $allowed = array();
        foreach ( $l->enumerateLtiAssignmentItems() as $it ) {
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
                U::flashError(__('Could not clear due dates. Please try again.'));
                return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates'));
            }
            $updated += $stmt->rowCount();
        }

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

        $context_id = U::get($_SESSION, 'context_id');
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
        $items = $l->enumerateLtiAssignmentItems();
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
                U::flashError(__('Could not save due dates. Please try again.'));
                return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates'));
            }
            $updated += $stmt->rowCount();
        }

        $msg = __('Applied weekly due dates.').' '.sprintf(__('Updated %d link row(s).'), $updated);
        if ( $skipped > 0 ) {
            $msg .= ' '.sprintf(__('Skipped %d assignment(s) with no link row yet.'), $skipped);
        }
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

        $context_id = U::get($_SESSION, 'context_id');
        $l = new \Tsugi\UI\Lessons($CFG->lessons);
        $items = $l->enumerateLtiAssignmentItems();
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
            U::flashSuccess(
                __('Added link rows.').' '.sprintf(__('New inserts: %d.'), $inserted).' '.
                sprintf(__('Missing assignments processed: %d. Same insert as launch—no duplicate if a row already existed.'), $attempted)
            );
        }
        return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE) . '/manage-due-dates'));
    }
}

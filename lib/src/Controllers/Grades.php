<?php

namespace Tsugi\Controllers;


use \Tsugi\Util\U;
use Tsugi\Core\LTIX;
use Tsugi\Lumos\Application;
use Symfony\Component\HttpFoundation\Request;
use Tsugi\UI\Table;
use Tsugi\Core\Manifest;
use Tsugi\Services\Lessons\LessonsNormalize;
use Tsugi\Services\Lessons\LessonsService;
use Tsugi\Services\Lessons\LessonDue;
use Tsugi\Services\Lessons\LessonPublish;
use Tsugi\Services\Outbound\LtiContentService;
use Tsugi\Services\Quiz1\Quiz1Repository;

use Tsugi\Core\ReqScope;
use Tsugi\Grades\GradeUtil;
use Tsugi\Services\Grades\GradeCategoryRepository;
use Tsugi\Services\Plus\GradeSync;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Grades extends Tool {

    const ROUTE = '/grades';
    const NAME = 'Grades';
    const REDIRECT = 'tsugi_controllers_grades';

    /** @var int */
    private static $gradeKebabSeq = 0;

    /** @var array<string,array{href:string,blank:bool,students:bool}>|null */
    private $lessonLaunchHrefCache = null;

    /** @var array<string,bool>|null */
    private $linkHiddenCache = null;

    /** @var array<int,array{link_key:string,link_id:int}>|null */
    private $publishedQuizById = null;

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Grades@index');
        $app->router->get($prefix.'/', 'Grades@index');
        $app->router->get('/'.self::REDIRECT, 'Grades@index');
        $app->router->get($prefix.'/outline', 'Grades@outline');
        $app->router->get($prefix.'/outline/', 'Grades@outline');
        $app->router->get($prefix.'/class', 'Grades@classView');
        $app->router->get($prefix.'/configuration', 'Grades@configuration');
        $app->router->get($prefix.'/manage', 'Grades@manage');
        $app->router->get($prefix.'/manage/', 'Grades@manage');
        $app->router->get($prefix.'/analytics', 'Grades@analytics');
        $app->router->get($prefix.'/resend', 'Grades@resend');
        $app->router->get($prefix.'/resend/', 'Grades@resend');
        $app->router->get($prefix.'/log', 'Grades@agsLog');
        $app->router->get($prefix.'/log/', 'Grades@agsLog');
        $app->router->post($prefix.'/resend', 'Grades@resendPost');
        $app->router->post($prefix.'/resend/', 'Grades@resendPost');
        $app->router->get($prefix.'/student-progress', 'Grades@studentProgress');
        $app->router->get($prefix.'/student-progress/', 'Grades@studentProgress');
        $app->router->get($prefix.'/manage-due-dates', 'Grades@manageDueDates');
        $app->router->get($prefix.'/manage-due-dates/', 'Grades@manageDueDates');
        $app->router->post($prefix.'/manage-due-dates/add-link-rows', 'Grades@addMissingLinkRowsPost');
        $app->router->post($prefix.'/manage-due-dates/add-link-rows/', 'Grades@addMissingLinkRowsPost');
        $app->router->post($prefix.'/manage-due-dates/apply-weekly', 'Grades@applyWeeklyDueDatesPost');
        $app->router->post($prefix.'/manage-due-dates/apply-weekly/', 'Grades@applyWeeklyDueDatesPost');
        $app->router->post($prefix.'/manage-due-dates/clear-all', 'Grades@clearAllDueDatesPost');
        $app->router->post($prefix.'/manage-due-dates/clear-all/', 'Grades@clearAllDueDatesPost');
        $app->router->post($prefix.'/manage-due-dates', 'Grades@manageDueDatesPost');
        $app->router->post($prefix.'/manage-due-dates/', 'Grades@manageDueDatesPost');
        $app->router->post($prefix.'/publish', 'Grades@publishPost');
        $app->router->post($prefix.'/publish/', 'Grades@publishPost');
        $app->router->post($prefix.'/hide', 'Grades@hidePost');
        $app->router->post($prefix.'/hide/', 'Grades@hidePost');
        $app->router->post($prefix.'/categories', 'Grades@categoryAddPost');
        $app->router->post($prefix.'/categories/{id}', 'Grades@categoryPost');
        $app->router->post($prefix.'/links/{id}', 'Grades@linkPost');
        $app->router->post($prefix.'/links/{id}/lineitem', 'Grades@createLineItemPost');
        $app->router->post($prefix.'/links/{id}/lineitem/', 'Grades@createLineItemPost');
        $app->router->post($prefix.'/results/{id}', 'Grades@overridePost');
        $app->router->post($prefix.'/results/{id}/', 'Grades@overridePost');
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
     * Shape grade rows for the table. Does not call the LMS.
     *
     * @param array<int,array<string,mixed>> $rows
     * @param string $overrideReturn Page to reopen after a teacher saves a grade
     * @return array<int,array<string,mixed>>
     */
    private function processGradeRows($rows, $overrideReturn = '') {
        $newrows = array();

        foreach ( $rows as $row ) {
            $newrow = $row;
            $newrow['_result_id'] = (int) ($row['result_id'] ?? 0);
            unset($newrow['result_id']);
            unset($newrow['resource_link_id']);
            unset($newrow['link_id']);
            unset($newrow['diff_in_seconds']);
            unset($newrow['time_now']);
            unset($newrow['sourcedid']);
            unset($newrow['service']);
            unset($newrow['result_url']);
            unset($newrow['lti13_lineitem']);
            unset($newrow['subject_key']);
            $passed_upward = $this->resultPassedUpward($row);
            $plus = $this->rowIsPlus($row);
            $newrow['_upward'] = $passed_upward ? 1 : 0;
            $newrow['_plus'] = $plus ? 1 : 0;
            unset($newrow['plus_course']);
            if ( ! $passed_upward && ! $plus ) {
                unset($newrow['server_grade']);
                unset($newrow['retrieved_at']);
                unset($newrow['server_status']);
            } else if ( ! $plus ) {
                $newrow['server_status'] = '';
            }
            $newrows[] = $newrow;
        }

        $showrows = array();
        foreach ( $newrows as $row ) {
            if ( $this->isInstructor() && $overrideReturn !== '' && is_numeric($row['grade'] ?? null) ) {
                $row['_html_grade'] = $this->gradeOverrideMarkup($row, $overrideReturn);
            }
            unset($row['_result_id']);
            $this->attachGradeMenu($row);
            unset($row['server_grade']);
            $this->formatResultGrade($row);
            if ( array_key_exists('due_date', $row) ) {
                $row['due_date'] = $this->dueDateLabel($row['due_date']);
            }
            $showrows[] = $this->orderGradeBookColumns($row);
        }

        return $showrows;
    }

    /**
     * Put title (or displayname) before code, then remaining columns.
     *
     * @param array<string,mixed> $row
     * @param string[] $leading
     * @return array<string,mixed>
     */
    private function orderGradeBookColumns($row, $leading = array('title', 'code', 'due_date', 'published')) {
        $ordered = array();
        if ( array_key_exists('user_id', $row) ) {
            $ordered['user_id'] = $row['user_id'];
        }
        foreach ( $leading as $key ) {
            if ( array_key_exists($key, $row) ) {
                $ordered[$key] = $row[$key];
            }
        }
        foreach ( $row as $k => $v ) {
            if ( strpos($k, '_') === 0 || $k === 'menu' ) {
                continue;
            }
            if ( array_key_exists($k, $ordered) ) {
                continue;
            }
            $ordered[$k] = $v;
        }
        if ( array_key_exists('menu', $row) ) {
            $ordered['menu'] = $row['menu'];
        }
        foreach ( $row as $k => $v ) {
            if ( strpos($k, '_') === 0 ) {
                $ordered[$k] = $v;
            }
        }
        return $ordered;
    }

    /**
     * Show a result as points earned over the maximum, such as 8/10.
     * score_given is the numerator when the tool posted one. Otherwise the
     * 0-1 grade is scaled by the link maximum, or by 1 when no maximum is stored.
     *
     * @param array<string,mixed> $row
     */
    private function formatResultGrade(&$row)
    {
        if ( array_key_exists('grade', $row) && is_numeric($row['grade']) ) {
            $row['grade'] = $this->gradeFractionLabel($row, 'grade');
        }
        if ( array_key_exists('server_grade', $row) && is_numeric($row['server_grade']) ) {
            $row['server_grade'] = $this->gradeFractionLabel($row, 'server_grade');
        }
        unset($row['score_given']);
        unset($row['result_maximum']);
        unset($row['score_maximum']);
    }

    /**
     * Clickable grade for an instructor. The popup stores a new point total.
     *
     * @param array<string,mixed> $row
     * @param string $return
     */
    private function gradeOverrideMarkup(array $row, $return)
    {
        $resultId = (int) ($row['_result_id'] ?? $row['result_id'] ?? 0);
        if ( $resultId < 1 || ! is_numeric($row['grade'] ?? null) ) {
            return '';
        }
        $maximum = GradeSync::pointsMaximum($row);
        $earned = GradeSync::earnedPoints($row);
        $label = $this->gradeFractionLabel($row, 'grade');
        $value = $earned === null ? '' : $this->gradeNumber($earned);
        $maxLabel = $this->gradeNumber($maximum);
        $action = htmlspecialchars($this->toolHome(self::ROUTE).'/results/'.$resultId, ENT_QUOTES, 'UTF-8');
        $html = '<div class="dropdown grade-override">';
        $html .= '<button type="button" class="btn btn-link btn-xs dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="'.htmlspecialchars(__('Change grade'), ENT_QUOTES, 'UTF-8').'">';
        $html .= htmlspecialchars($label).'</button>';
        $html .= '<div class="dropdown-menu grade-override-menu" onclick="event.stopPropagation()">';
        $html .= '<form method="post" action="'.$action.'">';
        $html .= self::csrfField();
        $html .= '<input type="hidden" name="return" value="'.htmlspecialchars($return, ENT_QUOTES, 'UTF-8').'">';
        $html .= '<p class="grade-override-current">'.htmlspecialchars(__('Current grade')).' '.htmlspecialchars($label).'</p>';
        $html .= '<label class="grade-override-field">'.htmlspecialchars(__('Grade'));
        $html .= ' <input class="form-control input-sm grade-override-points" type="number" name="points" min="0" max="'.htmlspecialchars($maxLabel, ENT_QUOTES, 'UTF-8').'" step="any" required value="'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'">';
        $html .= ' / '.htmlspecialchars($maxLabel).'</label>';
        $html .= '<button type="submit" class="btn btn-primary btn-xs">'.htmlspecialchars(__('Save')).'</button>';
        $html .= '</form></div></div>';
        return $html;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @param string $return
     */
    private function attachGradeOverrides(&$rows, $return)
    {
        if ( ! $this->isInstructor() || $return === '' ) {
            foreach ( $rows as &$row ) {
                unset($row['result_id']);
            }
            unset($row);
            return;
        }
        foreach ( $rows as &$row ) {
            if ( is_numeric($row['grade'] ?? null) ) {
                $row['_html_grade'] = $this->gradeOverrideMarkup($row, $return);
            }
            unset($row['result_id']);
        }
        unset($row);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     */
    private function formatResultGrades(&$rows)
    {
        foreach ( $rows as &$row ) {
            $this->formatResultGrade($row);
        }
        unset($row);
    }

    /**
     * @param array<string,mixed> $row
     * @param string $field
     */
    private function gradeFractionLabel($row, $field)
    {
        $maximum = 1.0;
        if ( isset($row['result_maximum']) && is_numeric($row['result_maximum']) && (float) $row['result_maximum'] > 0 ) {
            $maximum = (float) $row['result_maximum'];
        } else if ( isset($row['score_maximum']) && is_numeric($row['score_maximum']) && (float) $row['score_maximum'] > 0 ) {
            $maximum = (float) $row['score_maximum'];
        }
        $score = (float) $row[$field] * $maximum;
        if ( $field === 'grade' && isset($row['score_given']) && is_numeric($row['score_given']) ) {
            $score = (float) $row['score_given'];
        }
        return $this->gradeNumber($score).'/'.$this->gradeNumber($maximum);
    }

    /**
     * @param float $value
     */
    private function gradeNumber($value)
    {
        $value = (float) $value;
        if ( abs($value - round($value)) < 0.000001 ) {
            return (string) (int) round($value);
        }
        return rtrim(rtrim(sprintf('%.4f', $value), '0'), '.');
    }

    /**
     * Kebab at the end of a grade row. Progress and status always; server
     * status and server updated time only when the result is sent upward.
     * Server status and retrieved time leave the table and live in this menu.
     * The student grade list also drops server grade after the menu is built.
     *
     * @param array<string,mixed> $row
     * @param bool $keep_due
     */
    private function attachGradeMenu(&$row, $keep_due = true)
    {
        $row['menu'] = '';
        $row['_html_menu'] = $this->gradeKebabMarkup($row);
        $row['_header_menu'] = '<span class="sr-only">'.htmlspecialchars(__('More')).'</span>';
        unset($row['server_status']);
        unset($row['retrieved_at']);
        if ( ! $keep_due ) {
            unset($row['due_date']);
        }
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @param bool $keep_due
     */
    private function attachGradeMenus(&$rows, $keep_due = true)
    {
        foreach ( $rows as &$row ) {
            $this->attachGradeMenu($row, $keep_due);
        }
        unset($row);
    }

    /**
     * @param array<string,mixed> $row
     */
    private function gradeKebabMarkup($row)
    {
        $grade = (isset($row['grade']) && is_numeric($row['grade'])) ? (float) $row['grade'] : null;
        $due = array_key_exists('due_date', $row) ? $row['due_date'] : null;
        $items = array(
            array(__('Progress'), $this->gradeProgressLabel($grade)),
            array(__('Status'), $this->gradeStatusLabel($grade, $due)),
        );
        if ( $this->rowIsPlus($row) ) {
            $server_grade = '—';
            if ( isset($row['server_grade']) && is_numeric($row['server_grade']) ) {
                $server_grade = $this->gradeFractionLabel($row, 'server_grade');
            } else if ( isset($row['server_grade']) && trim((string) $row['server_grade']) !== '' ) {
                $server_grade = trim((string) $row['server_grade']);
            }
            $retrieved = trim((string) U::get($row, 'retrieved_at', ''));
            $error = trim((string) U::get($row, 'server_status', ''));
            $items[] = array(__('Server grade'), $server_grade);
            $items[] = array(__('Server updated at'), $retrieved === '' ? '—' : $retrieved);
            if ( $error !== '' ) {
                $items[] = array(__('Server error'), $error);
            }
        } else if ( $this->rowIsUpward($row) ) {
            $server_status = trim((string) U::get($row, 'server_status', ''));
            $retrieved = trim((string) U::get($row, 'retrieved_at', ''));
            $items[] = array(__('Server status'), $server_status === '' ? '—' : $server_status);
            $items[] = array(__('Server updated at'), $retrieved === '' ? '—' : $retrieved);
        }
        $id = 'grade-kebab-'.self::$gradeKebabSeq;
        self::$gradeKebabSeq++;
        $html = '<div class="dropdown grade-kebab">';
        $html .= '<button type="button" class="btn btn-link btn-xs dropdown-toggle" id="'.htmlspecialchars($id).'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="'.htmlspecialchars(__('More')).'">';
        $html .= '<i class="fa fa-ellipsis-v" aria-hidden="true"></i>';
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu dropdown-menu-right grade-kebab-menu" aria-labelledby="'.htmlspecialchars($id).'">';
        foreach ( $items as $item ) {
            $html .= '<li class="grade-kebab-item" role="presentation">';
            $html .= '<span class="grade-kebab-label">'.htmlspecialchars($item[0]).'</span>';
            $html .= '<span class="grade-kebab-value">'.htmlspecialchars($item[1]).'</span>';
            $html .= '</li>';
        }
        $html .= '</ul></div>';
        return $html;
    }

    /**
     * @param float|null $grade
     */
    private function gradeProgressLabel($grade)
    {
        if ( $grade === null ) {
            return __('Not started');
        }
        if ( $grade > 0.8 ) {
            return __('Complete');
        }
        return (string) (int) round($grade * 100).'%';
    }

    /**
     * @param float|null $grade
     * @param mixed $due
     */
    private function gradeStatusLabel($grade, $due)
    {
        if ( $due !== null && $due !== '' ) {
            $grades = array();
            if ( $grade !== null ) {
                $grades['row'] = $grade;
            }
            $mod = LessonsService::assignmentsDueBadgeModifier('row', $due, $grades);
            if ( $mod !== 'tsugi-assignments-due-neutral' ) {
                return \Tsugi\Controllers\Lessons::assignmentsDueStateVisibleLabel($mod);
            }
        }
        if ( $grade !== null && $grade > 0.8 ) {
            return __('Completed');
        }
        return __('No due date');
    }

    /**
     * @param array<string,mixed> $row
     */
    private function rowIsUpward($row)
    {
        if ( array_key_exists('_upward', $row) ) {
            return (int) $row['_upward'] === 1;
        }
        return $this->resultPassedUpward($row);
    }

    /**
     * @param array<string,mixed> $row
     */
    private function rowIsPlus($row)
    {
        if ( array_key_exists('_plus', $row) ) {
            return (int) $row['_plus'] === 1;
        }
        return (int) ($row['plus_course'] ?? 0) === 1;
    }

    /**
     * The live due date is lti_link.end_datetime, stored as 11:59 PM that day.
     *
     * @param mixed $mysql
     */
    private function dueDateLabel($mysql)
    {
        if ( $mysql === null || $mysql === '' ) {
            return '';
        }
        if ( preg_match('/^(\d{4}-\d{2}-\d{2})/', (string) $mysql, $match) ) {
            return $match[1];
        }
        return '';
    }

    /**
     * Add a Code column with result signature markup when appropriate.
     *
     * @param array<int,array<string,mixed>> $rows
     */
    private function addGradeBookCodeColumn(&$rows, $is_instructor) {
        foreach ( $rows as &$row ) {
            $row['code'] = '';
            unset($row['_html_title']);
            $rlid = U::get($row, 'resource_link_id', U::get($row, 'link_key', ''));
            $link_id = (int) U::get($row, 'link_id', 0);
            if ( ! LessonsService::shouldShowGradesResultSignature($rlid, U::get($row, 'grade'), $is_instructor) ) {
                continue;
            }
            $row['_html_code'] = \Tsugi\Controllers\Lessons::resultLinkSignatureMarkup($rlid, $link_id, false);
        }
        unset($row);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     */
    private function addClassViewCodeColumn(&$rows, $link_info) {
        if ( ! is_array($link_info) ) {
            return;
        }
        $rlid = U::get($link_info, 'link_key', '');
        $link_id = (int) U::get($link_info, 'link_id', 0);
        $html = \Tsugi\Controllers\Lessons::resultLinkSignatureMarkup($rlid, $link_id, false);
        foreach ( $rows as &$row ) {
            $row['code'] = '';
            $row['_html_code'] = $html;
            $row = $this->orderGradeBookColumns($row, array('displayname', 'code'));
        }
        unset($row);
    }

    /**
     * Main grades index - shows student's own grades or instructor viewing specific student
     */
    public function index(Request $request)
    {
        global $CFG, $OUTPUT, $PDOX;
        
        $this->requireAuth();
        
        LTIX::getConnection();
        
        $context_id = ReqScope::currentContextId();
        $user_id = ReqScope::loggedInUserId();
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
        $searchfields = array("L.title", "R.grade", "R.comment", "R.updated_at", "retrieved_at");
        $user_sql = $this->visibleGradeSql($p);
        
        $tool_home = $this->toolHome(self::ROUTE);
        $is_admin = $this->isAdmin();
        $analytics_url = $this->gradeAnalyticsUrl($tool_home);
        $show_analytics = $analytics_url !== '';
        $class_url = '';
        $resend_url = '';
        if ( $is_instructor ) {
            $class_url = $tool_home . '/class';
            $resend_url = $this->resendUrl(
                $context_id,
                $view_user_id,
                $tool_home,
                (int) $view_user_id !== (int) $user_id
            );
        }
        $lesson_urls = $this->lessonGradeUrls();

        // Record learner analytics
        $this->lmsRecordLaunchAnalytics(self::ROUTE, 'Grade Book');
        
        // Get grades with pagination. A course with categories loads every
        // visible row so a group is not split across pages.
        $categories = GradeCategoryRepository::listForContext($context_id);
        $grouped = count($categories) > 0;
        if ( $grouped ) {
            $ordered_sql = $user_sql." ORDER BY (C.category_id IS NULL), C.position ASC, C.category_id ASC, L.title ASC";
            $rows = $PDOX->allRowsDie($ordered_sql, $query_parms);
        } else {
            $newsql = Table::pagedQuery($user_sql, $query_parms, $searchfields);
            $rows = $PDOX->allRowsDie($newsql, $query_parms);
        }
        
        $this->addGradeBookCodeColumn($rows, $is_instructor);
        $showrows = $this->processGradeRows($rows, $this->gradeBookUserUrl('', $view_user_id));
        $this->linkLaunchableTitles($showrows);
        $this->addPublishColumn($showrows, $this->gradeBookUserUrl('', $view_user_id));
        
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav(false);
        $OUTPUT->flashMessages();
        \Tsugi\Controllers\Lessons::printLtiProgressStyles();
        $this->printGradeStateIconStyles();
        ?>
        <main class="container" id="main-content">
        <h1><?= __('Grade Book') ?></h1>
        <p>Class: <?= htmlspecialchars($context_title) ?></p>
        <?php
        $this->printGradeCategoryStyles();
        $outline_url = $this->outlineTabUrl($view_user_id);
        $grade_nav = array(
            'active' => 'grades',
            'outline_url' => $outline_url,
            'grades_href' => $this->gradeBookUserUrl('', $view_user_id),
            'class_url' => $class_url,
            'resend_url' => $resend_url,
            'analytics_url' => $analytics_url,
            'configure_url' => $is_instructor ? $tool_home.'/configuration' : '',
            'due_dates_url' => $lesson_urls['due_dates_url'],
            'progress_url' => $lesson_urls['progress_url'],
        );
        if ( $outline_url === '' && ! $is_instructor && ! $show_analytics ) {
            $this->echoViewedStudent($user_info);
            $this->renderGradeRows($grouped, $categories, $showrows, $context_id, $view_user_id);
        } else {
            $this->renderGradeNav($grade_nav);
            $this->echoViewedStudent($user_info);
            $this->renderGradeRows($grouped, $categories, $showrows, $context_id, $view_user_id);
        }
        // Display user identity from session
        $identity = __("Logged in as: ").htmlspecialchars(U::get($_SESSION, 'user_key', ''));
        if ( U::get($_SESSION, 'email') ) {
            $identity .= ' ' . htmlspecialchars($_SESSION['email']);
        }
        if ( U::get($_SESSION, 'displayname') ) {
            $identity .= ' ' . htmlspecialchars($_SESSION['displayname']);
        }
        echo("<p>".$identity."</p>");
        ?>
        </main>
        <?php
        $this->finishGradePage();
    }

    /**
     * Course outline for this user. Grades stay on their own page.
     */
    public function outline(Request $request)
    {
        global $CFG, $OUTPUT, $PDOX;

        $this->requireAuth();
        LTIX::getConnection();

        $context_id = ReqScope::currentContextId();
        $user_id = ReqScope::loggedInUserId();
        $is_instructor = $this->isInstructor();
        $view_user_id = $user_id;
        $user_info = false;
        if ( $is_instructor && $request->query->has('user_id') ) {
            $view_user_id = $request->query->get('user_id') + 0;
            $user_info = $this->getUserInfo($view_user_id);
        }

        $p = $CFG->dbprefix;
        $rows = $PDOX->allRowsDie(
            $this->visibleGradeSql($p)." ORDER BY L.title ASC",
            array(':UID' => $view_user_id, ':CID' => $context_id)
        );
        $this->addGradeBookCodeColumn($rows, $is_instructor);
        $showrows = $this->processGradeRows($rows, $this->gradeBookUserUrl('/outline', $view_user_id));
        $this->linkLaunchableTitles($showrows);
        $outline_return = $this->gradeBookUserUrl('/outline', $view_user_id);
        $this->addPublishColumn($showrows, $outline_return);
        $outline = $this->courseOutlineModules($context_id, $showrows, $outline_return);

        $tool_home = $this->toolHome(self::ROUTE);
        $class_url = '';
        $resend_url = '';
        if ( $is_instructor ) {
            $class_url = $tool_home.'/class';
            $resend_url = $this->resendUrl(
                $context_id,
                $view_user_id,
                $tool_home,
                (int) $view_user_id !== (int) $user_id
            );
        }
        $lesson_urls = $this->lessonGradeUrls();
        $context_title = $this->getContextTitle($context_id);

        $this->lmsRecordLaunchAnalytics(self::ROUTE, 'Grade Book');
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav(false);
        $OUTPUT->flashMessages();
        \Tsugi\Controllers\Lessons::printLtiProgressStyles();
        $this->printGradeStateIconStyles();
        echo '<main class="container" id="main-content">'."\n";
        echo '<h1>'.htmlspecialchars(__('Grade Book'))."</h1>\n";
        echo '<p>Class: '.htmlspecialchars($context_title)."</p>\n";
        $this->printGradeCategoryStyles();
        $this->renderGradeNav(array(
            'active' => 'outline',
            'outline_url' => $this->outlineTabUrl($view_user_id),
            'grades_href' => $this->gradeBookUserUrl('', $view_user_id),
            'class_url' => $class_url,
            'resend_url' => $resend_url,
            'analytics_url' => $this->gradeAnalyticsUrl($tool_home),
            'configure_url' => $is_instructor ? $tool_home.'/configuration' : '',
            'due_dates_url' => $lesson_urls['due_dates_url'],
            'progress_url' => $lesson_urls['progress_url'],
        ));
        $this->echoViewedStudent($user_info);
        if ( ! is_array($outline) ) {
            echo '<p>'.htmlspecialchars(__('No modules with active links.'))."</p>\n";
        } else {
            $this->renderCourseOutline($outline);
        }
        echo "</main>\n";
        $this->finishGradePage();
    }

    /**
     * Instructor class view - shows all students or grades for a specific link
     */
    public function classView(Request $request)
    {
        global $CFG, $OUTPUT, $PDOX;
        
        $this->requireInstructor($this->toolHome(self::ROUTE));

        LTIX::getConnection();
        
        $context_id = ReqScope::currentContextId();
        $p = $CFG->dbprefix;
        
        $link_id = 0;
        if ( $request->query->has('link_id') ) {
            $link_id = $request->query->get('link_id') + 0;
        }
        
        $link_info = false;
        if ( $link_id > 0 ) {
            $link_info = $PDOX->rowDie(
                "SELECT link_id, link_key, title FROM {$p}lti_link 
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
                "SELECT R.result_id AS result_id, R.user_id AS user_id, U.displayname, U.subject_key AS subject_key, R.grade,
                    R.score_given AS score_given, R.result_maximum AS result_maximum, L.score_maximum AS score_maximum,
                    L.end_datetime AS due_date,
                    R.updated_at as updated_at, R.server_grade, R.retrieved_at, R.server_status,
                    R.sourcedid AS sourcedid, S.service_key AS service, L.lti13_lineitem AS lti13_lineitem,
                    CTX.plus_course AS plus_course
                FROM {$p}lti_result AS R
                INNER JOIN {$p}lti_user as U ON R.user_id = U.user_id
                INNER JOIN {$p}lti_link AS L ON R.link_id = L.link_id
                INNER JOIN {$p}lti_context AS CTX ON CTX.context_id = L.context_id
                LEFT JOIN {$p}lti_service AS S ON R.service_id = S.service_id
                WHERE R.link_id = :LID AND R.grade IS NOT NULL AND R.deleted = 0";
        } else {
            $query_parms = array(":CID" => $context_id);
            $orderfields = array("R.user_id", "displayname", "email", "user_key", "grade_count", "latest_grade_updated_at");
            $searchfields = array("R.user_id", "displayname", "email", "user_key");
            $summary_sql =
                "SELECT R.user_id AS user_id, U.displayname, U.email, COUNT(R.grade) AS grade_count, U.user_key,
                    MAX(R.updated_at) AS latest_grade_updated_at
                FROM {$p}lti_link as L
                INNER JOIN {$p}lti_result AS R ON L.link_id = R.link_id AND R.grade IS NOT NULL AND R.deleted = 0
                INNER JOIN {$p}lti_user as U ON R.user_id = U.user_id
                WHERE L.context_id = :CID
                GROUP BY R.user_id, U.displayname, U.email, U.user_key";
        }
        
        // Get list of links for menu
        $lstmt = $PDOX->queryDie(
            "SELECT DISTINCT L.title as title, L.link_id AS link_id, L.hidden AS hidden
            FROM {$p}lti_link AS L JOIN {$p}lti_result as R
                ON L.link_id = R.link_id AND R.grade IS NOT NULL
            WHERE L.context_id = :CID",
            array(":CID" => $context_id)
        );
        $links = $lstmt->fetchAll();
        
        $tool_home = $this->toolHome(self::ROUTE);

        // Record analytics (instructors don't get recorded, but we can still track the page)
        $this->lmsRecordLaunchAnalytics(self::ROUTE, 'Grade Book');

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav(false);
        $OUTPUT->flashMessages();
        \Tsugi\Controllers\Lessons::printLtiProgressStyles();
        $this->printGradeStateIconStyles();
        ?>
        <main class="container" id="main-content">
        <h1><?= __('Grade Book') ?></h1>
        <p>Class: <?= htmlspecialchars($context_title) ?></p>
        <?php
        $this->printGradeCategoryStyles();
        $lesson_urls = $this->lessonGradeUrls();
        $this->renderGradeNav(array(
            'active' => 'class',
            'outline_url' => $this->outlineTabUrl(ReqScope::loggedInUserId()),
            'grades_href' => $tool_home,
            'class_url' => $tool_home . '/class',
            'resend_url' => $this->resendUrl($context_id, ReqScope::loggedInUserId(), $tool_home, false),
            'analytics_url' => $this->gradeAnalyticsUrl($tool_home),
            'configure_url' => $tool_home . '/configuration',
            'due_dates_url' => $lesson_urls['due_dates_url'],
            'progress_url' => $lesson_urls['progress_url'],
        ));
        $activity_html = $this->renderActivityDetail($links, $tool_home, $link_id, $link_info);

        // Build detail URL for pagedAuto - link to index (single-student view) so clicking
        // a student in the class list dives into that student's grade detail
        $detail_url = U::reconstruct_query($tool_home, array("detail" => ""));
        
        if ( $summary_sql !== false ) {
            $params = $request->query->all();
            if ( !isset($params['order_by']) ) {
                $params['order_by'] = 'latest_grade_updated_at';
                $params['desc'] = 1;
            }
            Table::pagedAuto($summary_sql, $query_parms, $searchfields, $orderfields, $detail_url, $params, false, $activity_html);
        }
        
        if ( $class_sql !== false ) {
            $params = $request->query->all();
            $newsql = Table::pagedQuery($class_sql, $query_parms, $searchfields, $searchfields, $params);
            $class_rows = $PDOX->allRowsDie($newsql, $query_parms);
            $this->addClassViewCodeColumn($class_rows, $link_info);
            $this->attachGradeMenus($class_rows, false);
            $this->attachGradeOverrides($class_rows, $tool_home.'/class?link_id='.(int) $link_id);
            $this->formatResultGrades($class_rows);
            $this->dropServerColumnsUnlessUpward($class_rows);
            Table::pagedTable($this->withUniformServerColumns($class_rows), $searchfields, $searchfields, $detail_url, $params, false, $activity_html);
        }
        ?>
        </main>
        <?php
        $this->finishGradePage();
    }

    /**
     * Activity list and the open activity, after the search and sort buttons.
     *
     * @param array<int,array<string,mixed>>|false $links
     * @param array<string,mixed>|false $link_info
     */
    private function renderActivityDetail($links, $tool_home, $link_id, $link_info)
    {
        if ( ! is_array($links) || count($links) < 1 ) {
            return '';
        }
        $html = '<div class="grade-activity">';
        $html .= '<div class="dropdown">';
        $html .= '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $html .= htmlspecialchars(__('Activity Detail')).' <span class="caret"></span>';
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu">';
        foreach ( $links as $link ) {
            $id = (int) $link['link_id'];
            $title = (string) $link['title'];
            if ( (int) $link['hidden'] === 1 ) {
                $title .= ' ('.__('hidden').')';
            }
            $href = $tool_home.'/class?link_id='.$id;
            $html .= '<li'.($id === (int) $link_id ? ' class="active"' : '').'>';
            $html .= '<a href="'.htmlspecialchars($href, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($title).'</a>';
            $html .= '</li>';
        }
        $html .= '</ul></div>';
        if ( is_array($link_info) && isset($link_info['title']) ) {
            $html .= '<span class="grade-activity-current">'.htmlspecialchars(__('Link:')).' '
                .htmlspecialchars((string) $link_info['title']).'</span>';
        }
        $html .= "</div>\n";
        return $html;
    }

    /**
     * Old configuration URL. Categories and columns live on Manage grade book.
     */
    public function configuration(Request $request)
    {
        return new RedirectResponse($this->manageUrl());
    }

    /**
     * One instructor page for line items, gradable quizzes, categories, and due dates.
     */
    public function manage(Request $request)
    {
        $this->renderManageGradeBook();
    }

    /**
     * Instructor view of enrollment, recent activity, and lesson assignment completion.
     */
    public function studentProgress(Request $request)
    {
        global $CFG;

        $this->requireLessonGrades();
        $context_id = ReqScope::currentContextId();
        $p = $CFG->dbprefix;

        $l = Manifest::requireCurrentLessons();
        $assignment_items = $l->enumerateLtiAssignmentItems(true);
        $assignment_rlids = array();
        foreach ( $assignment_items as $it ) {
            if ( isset($it['resource_link_id']) && U::isNotEmpty($it['resource_link_id']) ) {
                $assignment_rlids[$it['resource_link_id']] = true;
            }
        }
        $total_assignments = count($assignment_rlids);

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

        $assignment_grade_clause = "FALSE";
        $params = array(
            ':CID' => $context_id,
            ':CID2' => $context_id,
            ':CID3' => $context_id,
            ':CID4' => $context_id,
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
        $group_by_discussion = ", QQ.question_count, CC.comment_count";

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

        $this->beginLessonGradePage('progress');
        echo('<h2>'.__('Student Progress')."</h2>\n");
        echo('<p>'.__('Instructor view of enrollment, recent activity, assignment completion, and discussion participation.')."</p>\n");
        echo('<p class="text-muted">'.sprintf(__('Assignments complete uses %1$d lesson assignment(s) with grade >= 80%%.'), $total_assignments)."</p>\n");

        $searchfields = array("U.displayname", "U.email");
        $orderfields = array(
            "last_visited_at", "displayname", "email", "enrolled_at",
            "progress_pct", "threads_started", "comment_count", "completed_assignments",
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
        $this->endLessonGradePage();
    }

    /**
     * Old due-date URL. The page is Manage grade book.
     */
    public function manageDueDates(Request $request)
    {
        return new RedirectResponse($this->manageUrl());
    }

    /**
     * Line items, gradable quizzes, categories, publish, hide, and due dates.
     */
    private function renderManageGradeBook()
    {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        LTIX::getConnection();

        $context_id = ReqScope::currentContextId();
        $manifest = $this->manifestCourse();
        $plus = $this->plusManageState($context_id);
        $items = $this->manageCatalog($context_id);
        $dueMap = GradeUtil::loadDueDatesForContext($context_id);
        $post_url = $this->dueDatesUrl();
        $return_url = $this->manageUrl();
        $has_lesson_rows = false;
        foreach ( $items as $it ) {
            if ( ! empty($it['in_lesson']) ) {
                $has_lesson_rows = true;
                break;
            }
        }

        $this->beginLessonGradePage('due-dates');
        echo('<h2>'.__('Manage grade book')."</h2>\n");
        echo('<p>'.__('Every line item in this course, plus quizzes and lesson tools that can still become a column. A due date is the end of that calendar day, 11:59 PM, stored on the link (end_datetime). An unprovisioned lesson tool is listed with its resource link id, and its due date is the date on the lesson. Publish, unpublish, and hide are the same controls as the grade book.')."</p>\n");

        $categories = GradeCategoryRepository::listForContext($context_id);
        $grade_columns = array();
        $categories_only = true;
        $post_home = $home;
        $return_inputs = '';
        include __DIR__ . '/templates/Grades/manage_dialog.inc.php';

        if ( count($items) < 1 ) {
            echo('<p>'.__('No line items or gradable quizzes yet.')."</p>\n");
            $this->endLessonGradePage();
            return;
        }

        $missingCount = 0;
        foreach ( $items as $it ) {
            if ( $this->isUnprovisionedLessonTool($it) ) {
                continue;
            }
            $r = trim((string) ($it['resource_link_id'] ?? ''));
            if ( $r === '' || ! array_key_exists($r, $dueMap) ) {
                $missingCount++;
            }
        }

        echo('<h3>'.__('Gradable things')."</h3>\n");
        echo('<form id="due-dates" method="post" action="'.htmlspecialchars($post_url).'">'."\n");
        echo(self::csrfField());
        echo("</form>\n");
        echo('<div class="table-responsive">'."\n");
        echo('<table class="table table-bordered table-striped">'."\n");
        echo('<thead><tr><th>'.__('Source').'</th><th>'.__('Name').'</th><th>'.__('Resource link').'</th><th>'.__('Due date').'</th><th>'.__('Category').'</th>');
        if ( $plus['plus'] ) {
            echo('<th>'.__('Line item').'</th>');
        }
        echo '<th><span class="sr-only">'.htmlspecialchars(__('Published and hidden')).'</span></th>';
        echo "</tr></thead>\n";
        echo("<tbody>\n");
        $linkFlags = $this->dueDateLinkFlags($context_id);
        $contentPublished = $manifest ? $this->dueDateContentPublished($context_id) : array();
        foreach ( $items as $it ) {
            $rlid = trim((string) ($it['resource_link_id'] ?? ''));
            $hasRow = $rlid !== '' && array_key_exists($rlid, $dueMap);
            $endVal = '';
            if ( $hasRow ) {
                $endVal = self::toDateInputValue($dueMap[$rlid]['end_datetime']);
            }
            $source = $this->gradeSource($it);
            echo('<tr>');
            echo('<td><span title="'.htmlspecialchars($source['hint'], ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($source['label']).'</span>');
            if ( $source['detail'] !== '' ) {
                echo('<br><span class="text-muted">'.htmlspecialchars($source['detail']).'</span>');
            }
            echo('</td>');
            echo('<td>'.htmlspecialchars($it['item_title']).'</td>');
            $unprovisionedTool = $this->isUnprovisionedLessonTool($it);
            echo('<td>');
            if ( $rlid !== '' ) {
                echo('<code>'.htmlspecialchars($rlid).'</code>');
            }
            if ( $unprovisionedTool ) {
                if ( $rlid !== '' ) {
                    echo('<br>');
                }
                echo('<span class="text-warning"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> '.__('Unprovisioned').'</span>');
            } else if ( $rlid === '' || ! $hasRow ) {
                if ( $rlid !== '' ) {
                    echo('<br>');
                }
                echo('<span class="text-warning"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> '.__('No LTI link row yet').'</span>');
            }
            echo('</td>');
            echo('<td>');
            if ( $hasRow ) {
                $dueAria = sprintf(
                    __('Due date for %1$s (resource link %2$s)'),
                    $it['item_title'],
                    $rlid
                );
                echo('<input type="date" class="form-control" form="due-dates" name="end[]" value="'.htmlspecialchars($endVal).'" aria-label="'.htmlspecialchars($dueAria, ENT_QUOTES, 'UTF-8').'">');
                echo('<input type="hidden" form="due-dates" name="rlid[]" value="'.htmlspecialchars($rlid).'">');
            } else if ( $unprovisionedTool && $rlid !== '' ) {
                $lessonDue = GradeUtil::dueDay((string) ($it['due'] ?? ''));
                $dueAria = sprintf(
                    __('Due date on the lesson for %1$s (resource link %2$s)'),
                    $it['item_title'],
                    $rlid
                );
                echo('<input type="date" class="form-control" form="due-dates" name="end[]" value="'.htmlspecialchars($lessonDue ?? '').'" aria-label="'.htmlspecialchars($dueAria, ENT_QUOTES, 'UTF-8').'">');
                echo('<input type="hidden" form="due-dates" name="rlid[]" value="'.htmlspecialchars($rlid).'">');
            } else {
                echo('<span class="text-muted">—</span>');
            }
            echo('</td>');
            echo('<td>');
            $linkId = (int) ($it['link_id'] ?? 0);
            if ( $linkId > 0 ) {
                $this->echoCategoryForm($home, $linkId, $it['category_id'] ?? null, $categories, (string) $it['item_title']);
            } else {
                echo('<span class="text-muted">—</span>');
            }
            echo('</td>');
            if ( $plus['plus'] ) {
                echo('<td>');
                $this->echoLineItemCell($home, $linkId, $it, (int) $plus['shell_link_id']);
                echo('</td>');
            }
            $published = $this->assignmentIsPublished($it, $linkFlags, $contentPublished);
            $link = ($rlid !== '' && isset($linkFlags[$rlid])) ? $linkFlags[$rlid] : null;
            $unprovisioned = empty($it['quiz_id'])
                && (int) ($it['content_id'] ?? 0) < 1
                && (int) ($it['tool_deployment_id'] ?? 0) < 1
                && $link === null;
            echo('<td><div class="grade-state-icons">');
            if ( $unprovisioned || $rlid === '' || $link === null ) {
                $hidden = $link !== null && ! empty($link['hidden']);
                echo($this->unprovisionedStateIcons($hidden));
            } else {
                echo($this->publishButtonHtml($rlid, $published, $return_url));
                echo($this->hiddenButtonHtml($rlid, ! empty($link['hidden']), $return_url));
            }
            if ( ! empty($it['module_hidden']) ) {
                echo($this->hiddenStatusIcon());
            }
            echo('</div></td>');
            echo("</tr>\n");
        }
        echo("</tbody></table>\n");
        echo('<p><button type="submit" class="btn btn-primary" form="due-dates">'.__('Save due dates').'</button></p>'."\n");
        echo("</div>\n");

        if ( $missingCount > 0 ) {
            $add_detail = $manifest
                ? __('Applies to Lessons tools that send a grade, and to Quiz rows, when they do not have a link yet. An unprovisioned tool is left alone. LMS and Line item rows are left as they are. A tool or quiz that is already published stays published, so students do not gain access from this button.')
                : __('Applies to Lessons assignments and Quiz rows that do not have a link yet. LMS and Line item rows are left as they are. The insert matches an LTI launch, so a row that already exists is not duplicated.');
            $this->echoCourseUnlockForm(array(
                'id' => 'unlock-add-links',
                'action' => $post_url.'/add-link-rows',
                'heading' => __('Add missing link rows'),
                'detail' => $add_detail,
                'note' => $manifest ? '' : __('The resource link id in your lessons file must match the LMS resource link id when students launch, or launches will create a different row.'),
                'date_field' => false,
                'button_icon' => 'fa-plus-circle',
                'button_class' => 'btn btn-default',
                'button_text' => __('Add missing link rows').' ('.$missingCount.')',
                'confirm' => $this->courseUnlockWarning(__('WARNING: This adds missing link rows on %s in course "%s" (%s members). Existing rows are left as they are. Continue?')),
            ));
        }

        if ( $has_lesson_rows ) {
            $weekly_detail = __('Lesson modules are ordered as in your lessons file. Pick the due date for all assignments in the first module (week 1). Each later module adds one week; every due is 11:59 PM on that calendar day.').' ';
            $weekly_detail .= $manifest
                ? __('Applies to Lessons assignments and to Quiz rows in those modules. An unprovisioned lesson tool stores the date on the lesson. LMS and Line item rows are left as they are. A row that already has a link is updated on the link.')
                : __('Applies to Lessons assignments. Quiz, LMS, and Line item rows are left as they are. Only rows that already have a link are updated.');
            $this->echoCourseUnlockForm(array(
                'id' => 'unlock-weekly',
                'action' => $post_url.'/apply-weekly',
                'heading' => __('Weekly due dates'),
                'detail' => $weekly_detail,
                'note' => '',
                'date_field' => true,
                'button_icon' => 'fa-magic',
                'button_class' => 'btn btn-default',
                'button_text' => __('Apply weekly due dates'),
                'confirm' => $this->courseUnlockWarning(__('WARNING: This replaces lesson due dates on %s in course "%s" (%s members). The dates you had before are not kept. Continue?')),
            ));
        }

        $this->echoCourseUnlockForm(array(
            'id' => 'unlock-clear-dues',
            'action' => $post_url.'/clear-all',
            'heading' => __('Clear all due dates'),
            'detail' => __('This removes the due date on every Lessons assignment, Quiz, LMS, and Line item in this list that already has a link, and on unprovisioned lesson tools. Grades, links, and the course stay.'),
            'note' => '',
            'date_field' => false,
            'button_icon' => 'fa-eraser',
            'button_class' => 'btn btn-danger',
            'button_text' => __('Clear all due dates'),
            'confirm' => $this->courseUnlockWarning(__('WARNING: This clears every due date on %s in course "%s" (%s members). Grades and links stay. The dates you had before are not kept. Continue?')),
        ));
        $this->echoCourseUnlockScript();
        $this->endLessonGradePage();
    }

    public function manageDueDatesPost(Request $request)
    {
        global $CFG, $PDOX;

        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        LTIX::getConnection();
        $dates_url = $this->manageUrl();
        $csrf = self::requireCsrf($dates_url);
        if ( $csrf ) {
            return $csrf;
        }

        $context_id = ReqScope::currentContextId();
        $allowed = $this->manageableDueKeys($context_id);
        $lessonKeys = $this->unprovisionedDueKeys($context_id);

        $rlids = U::get($_POST, 'rlid');
        $ends = U::get($_POST, 'end');
        if ( ! is_array($rlids) || ! is_array($ends) ) {
            U::flashError(__('Invalid form submission.'));
            return new RedirectResponse($dates_url);
        }

        $n = count($rlids);
        if ( $n !== count($ends) ) {
            U::flashError(__('Mismatched form fields.'));
            return new RedirectResponse($dates_url);
        }

        $p = $CFG->dbprefix;
        $updated = 0;
        $lessonDues = array();
        $duePush = $this->emptyDuePush();
        for ( $i = 0; $i < $n; $i++ ) {
            $rlid = $rlids[$i];
            $onLink = isset($allowed[$rlid]);
            $onLesson = isset($lessonKeys[$rlid]);
            if ( ! $onLink && ! $onLesson ) {
                continue;
            }
            $endSql = self::parseDueDateEndOfDay($ends[$i]);
            if ( $ends[$i] !== '' && $endSql === null ) {
                $this->invalidateDueDatesSessionCache();
                U::flashError(__('Invalid due date for resource link: ').$rlid);
                return new RedirectResponse($dates_url);
            }
            if ( ! $onLink ) {
                $lessonDues[$rlid] = $endSql === null ? '' : substr($endSql, 0, 10);
                continue;
            }
            $before = $this->storedDueInstant($context_id, $rlid);
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
                return new RedirectResponse($dates_url);
            }
            $updated += $stmt->rowCount();
            if ( $this->dueInstant($endSql) !== $before ) {
                $this->noteDuePush($context_id, $rlid, $duePush);
            }
        }

        $lessonUpdated = LessonDue::writeItemDues($context_id, $lessonDues);
        if ( $lessonUpdated === null ) {
            $this->invalidateDueDatesSessionCache();
            U::flashError(__('Could not save the lesson due date.'));
            return new RedirectResponse($dates_url);
        }

        $this->invalidateDueDatesSessionCache();
        LessonDue::pullPublished($context_id, array_keys($allowed));
        $msg = __('Saved due dates.').' ('.$updated.' '.__('link row(s) updated').').';
        if ( $lessonUpdated > 0 ) {
            $msg .= ' '.sprintf(__('Updated %d lesson due date(s).'), $lessonUpdated);
        }
        $msg .= $this->duePushSentence($duePush);
        $this->flashDuePushFailure($duePush);
        U::flashSuccess($msg);
        return new RedirectResponse($dates_url);
    }

    /**
     * @return array{updated:int,failed:int,message:string}
     */
    private function emptyDuePush()
    {
        return array('updated' => 0, 'failed' => 0, 'message' => '');
    }

    /**
     * Clock time stored for this link, or an empty string when it has no due date.
     *
     * @param int $context_id
     * @param string $link_key
     */
    private function storedDueInstant($context_id, $link_key)
    {
        global $CFG, $PDOX;
        $row = $PDOX->rowDie(
            "SELECT end_datetime FROM {$CFG->dbprefix}lti_link
             WHERE context_id = :CID AND link_key = :LK AND (deleted IS NULL OR deleted = 0)
             LIMIT 1",
            array(':CID' => (int) $context_id, ':LK' => $link_key)
        );
        if ( ! is_array($row) ) {
            return '';
        }
        return $this->dueInstant($row['end_datetime'] ?? null);
    }

    /**
     * @param mixed $value
     */
    private function dueInstant($value)
    {
        if ( $value === null ) {
            return '';
        }
        $raw = trim((string) $value);
        if ( $raw === '' || strpos($raw, '0000-00-00') === 0 ) {
            return '';
        }
        $raw = str_replace('T', ' ', $raw);
        return strlen($raw) >= 19 ? substr($raw, 0, 19) : $raw;
    }

    /**
     * @param int $context_id
     * @param string $link_key
     * @param array{updated:int,failed:int,message:string} $tally
     */
    private function noteDuePush($context_id, $link_key, array &$tally)
    {
        $result = GradeSync::pushDueDateForKey($context_id, $link_key);
        $status = (string) ($result['status'] ?? '');
        if ( ! empty($result['ok']) && $status === 'updated' ) {
            $tally['updated']++;
            return;
        }
        if ( in_array($status, array('no_lineitem', 'not_plus', 'shell', 'not_found'), true) ) {
            return;
        }
        $tally['failed']++;
        if ( $tally['message'] === '' && isset($result['message']) ) {
            $tally['message'] = (string) $result['message'];
        }
    }

    /**
     * @param array{updated:int,failed:int,message:string} $tally
     */
    private function duePushSentence(array $tally)
    {
        if ( (int) $tally['updated'] < 1 ) {
            return '';
        }
        return ' '.sprintf(__('Updated %d line item(s) in the LMS.'), (int) $tally['updated']);
    }

    /**
     * @param array{updated:int,failed:int,message:string} $tally
     */
    private function flashDuePushFailure(array $tally)
    {
        if ( (int) $tally['failed'] < 1 ) {
            return;
        }
        $message = trim((string) $tally['message']);
        U::flashError($message !== '' ? $message : __('A line item due date was not updated in the LMS.'));
    }

    /**
     * Same warning shape as import and delete: site, course title, and member count.
     *
     * @param string $format
     */
    private function courseUnlockWarning($format)
    {
        $domain = Settings::importSiteDomain();
        $title = Settings::importReplaceCourseTitle();
        $members = Settings::importReplaceMemberCount();
        return sprintf(
            $format,
            $domain !== '' ? $domain : __('this site'),
            $title !== '' ? $title : __('this course'),
            $members === null ? __('unknown') : number_format($members)
        );
    }

    /**
     * Domain, course title, and member count before a course-wide change.
     *
     * @param array{id:string,action:string,heading:string,detail:string,note:string,date_field:bool,button_icon:string,button_class:string,button_text:string,confirm:string} $spec
     */
    private function echoCourseUnlockForm($spec)
    {
        $domain = Settings::importSiteDomain();
        $title = Settings::importReplaceCourseTitle();
        $members = Settings::importReplaceMemberCount();
        $member_label = $members === null ? __('this course') : number_format($members);
        if ( $members === null ) {
            $member_sentence = __('This course has an unknown number of members.');
        } else if ( $members === 1 ) {
            $member_sentence = __('This course has 1 member.');
        } else {
            $member_sentence = sprintf(__('This course has %s members.'), number_format($members));
        }
        $id = $spec['id'];
        echo('<div class="well" style="margin-top:1.5em;margin-bottom:1.5em;">');
        echo('<p><strong>'.htmlspecialchars($spec['heading']).'</strong></p>');
        echo('<p>'.htmlspecialchars($spec['detail']).'</p>');
        if ( $spec['note'] !== '' ) {
            echo('<p class="text-warning"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ');
            echo(htmlspecialchars($spec['note']));
            echo('</p>');
        }
        echo('<div class="alert alert-danger" role="alert" style="margin-top:8px;padding:18px 20px;border-width:3px;">');
        echo('<p style="font-size:1.75em;font-weight:bold;margin:0 0 12px 0;line-height:1.2;">'.__('Difficult or impossible to undo').'</p>');
        echo('<p style="font-size:1.15em;margin-bottom:10px;">'.__('This changes the course as a whole. Type the domain of this site, the title of this course, and the number of members. The button stays off until all three match.').'</p>');
        echo('<p style="font-size:1.15em;font-weight:bold;margin-bottom:12px;">'.htmlspecialchars($member_sentence).'</p>');
        echo('<p style="font-size:1.1em;margin-bottom:8px;"><label for="'.htmlspecialchars($id).'_domain">'.sprintf(__('Type the domain name of this site (%s).'), htmlspecialchars($domain !== '' ? $domain : __('this site'))).'</label></p>');
        echo('<p style="margin-bottom:12px;"><input type="text" id="'.htmlspecialchars($id).'_domain" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"></p>');
        echo('<p style="font-size:1.1em;margin-bottom:8px;"><label for="'.htmlspecialchars($id).'_title">'.sprintf(__('Type the title of this course (%s).'), htmlspecialchars($title !== '' ? $title : __('this course'))).'</label></p>');
        echo('<p style="margin-bottom:12px;"><input type="text" id="'.htmlspecialchars($id).'_title" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"></p>');
        echo('<p style="font-size:1.1em;margin-bottom:8px;"><label for="'.htmlspecialchars($id).'_members">'.sprintf(__('Type the number of members in this course (%s).'), htmlspecialchars($member_label)).'</label></p>');
        echo('<p style="margin-bottom:0;"><input type="text" id="'.htmlspecialchars($id).'_members" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" inputmode="numeric"></p>');
        echo('</div>');
        echo('<form id="'.htmlspecialchars($id).'" class="course-unlock" method="post" action="'.htmlspecialchars($spec['action']).'" data-confirm="'.htmlspecialchars($spec['confirm'], ENT_QUOTES, 'UTF-8').'">');
        echo(self::csrfField());
        echo('<input type="hidden" name="course_unlock_domain" value="">');
        echo('<input type="hidden" name="course_unlock_title" value="">');
        echo('<input type="hidden" name="course_unlock_members" value="">');
        if ( $spec['date_field'] ) {
            echo('<div class="form-inline" style="margin-bottom:1em;">');
            echo('<label for="week1_due" style="margin-right:0.5em;">'.__('First module due date').'</label> ');
            echo('<input type="date" class="form-control" id="week1_due" name="week1_due" required>');
            echo('</div>');
        }
        echo('<button type="submit" class="'.htmlspecialchars($spec['button_class']).'" disabled>');
        echo('<i class="fa '.htmlspecialchars($spec['button_icon']).'" aria-hidden="true"></i> '.htmlspecialchars($spec['button_text']));
        echo('</button>');
        echo('</form>');
        echo('</div>');
    }

    /**
     * One script for every course-unlock form on this page.
     */
    private function echoCourseUnlockScript()
    {
        $domain = Settings::importSiteDomain();
        $title = Settings::normalizeImportTitle(Settings::importReplaceCourseTitle());
        $members = Settings::importReplaceMemberCount();
        echo "<script>\n";
        echo 'var courseUnlockDomain = '.json_encode($domain).";\n";
        echo 'var courseUnlockTitle = '.json_encode($title).";\n";
        echo 'var courseUnlockMembers = '.json_encode($members).";\n";
        echo 'var courseUnlockMismatch = '.json_encode(__('Type this site\'s domain, this course\'s title, and the number of members before this change.')).";\n";
        echo <<<'JS'
document.querySelectorAll('form.course-unlock').forEach(function(form) {
    var domainInput = document.getElementById(form.id + '_domain');
    var titleInput = document.getElementById(form.id + '_title');
    var membersInput = document.getElementById(form.id + '_members');
    var button = form.querySelector('button[type="submit"]');
    function typedDomain() {
        var raw = (domainInput.value || '').trim();
        raw = raw.replace(/^[a-z][a-z0-9+.-]*:\/\//i, '');
        raw = raw.split('/')[0].split(':')[0];
        return raw.toLowerCase();
    }
    function typedTitle() {
        return (titleInput.value || '').trim().replace(/\s+/g, ' ').toLowerCase();
    }
    function typedMembers() {
        var raw = (membersInput.value || '').trim().replace(/[,\s]/g, '');
        if ( ! /^\d+$/.test(raw) ) {
            return null;
        }
        return parseInt(raw, 10);
    }
    function matches() {
        if ( courseUnlockDomain === '' || typedDomain() !== courseUnlockDomain ) {
            return false;
        }
        if ( courseUnlockTitle === '' || typedTitle() !== courseUnlockTitle ) {
            return false;
        }
        if ( courseUnlockMembers === null || typeof courseUnlockMembers !== 'number' ) {
            return false;
        }
        return typedMembers() === courseUnlockMembers;
    }
    function sync() {
        form.querySelector('input[name="course_unlock_domain"]').value = domainInput.value || '';
        form.querySelector('input[name="course_unlock_title"]').value = titleInput.value || '';
        form.querySelector('input[name="course_unlock_members"]').value = membersInput.value || '';
        button.disabled = ! matches();
    }
    domainInput.addEventListener('input', sync);
    titleInput.addEventListener('input', sync);
    membersInput.addEventListener('input', sync);
    form.addEventListener('submit', function(event) {
        sync();
        if ( ! matches() ) {
            event.preventDefault();
            alert(courseUnlockMismatch);
            return;
        }
        if ( ! window.confirm(form.getAttribute('data-confirm') || '') ) {
            event.preventDefault();
        }
    });
    sync();
});
JS;
        echo "</script>\n";
    }

    /**
     * @return string|null
     */
    private function courseUnlockError()
    {
        if ( ! Settings::importReplaceDomainConfirmed(U::get($_POST, 'course_unlock_domain', '')) ) {
            return __('Type the domain name of this site to confirm this change.');
        }
        if ( ! Settings::importReplaceTitleConfirmed(U::get($_POST, 'course_unlock_title', '')) ) {
            return __('Type this course\'s title to confirm this change.');
        }
        if ( ! Settings::importReplaceMembersConfirmed(U::get($_POST, 'course_unlock_members', '')) ) {
            return __('Type the number of members in this course to confirm this change.');
        }
        return null;
    }

    /**
     * Set end_datetime to NULL for all enumerated lesson assignments that have a link row in this context.
     */
    public function clearAllDueDatesPost(Request $request)
    {
        global $CFG, $PDOX;

        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        LTIX::getConnection();
        $dates_url = $this->manageUrl();
        $csrf = self::requireCsrf($dates_url);
        if ( $csrf ) {
            return $csrf;
        }

        $confirm_error = $this->courseUnlockError();
        if ( $confirm_error !== null ) {
            U::flashError($confirm_error);
            return new RedirectResponse($dates_url);
        }

        $context_id = ReqScope::currentContextId();
        $allowed = $this->manageableDueKeys($context_id);
        $lessonKeys = $this->unprovisionedDueKeys($context_id);

        $p = $CFG->dbprefix;
        $updated = 0;
        $duePush = $this->emptyDuePush();
        foreach ( array_keys($allowed) as $rlid ) {
            $before = $this->storedDueInstant($context_id, $rlid);
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
                return new RedirectResponse($dates_url);
            }
            $updated += $stmt->rowCount();
            if ( $before !== '' ) {
                $this->noteDuePush($context_id, $rlid, $duePush);
            }
        }

        $lessonClear = array();
        foreach ( array_keys($lessonKeys) as $rlid ) {
            $lessonClear[$rlid] = '';
        }
        $lessonUpdated = LessonDue::writeItemDues($context_id, $lessonClear);
        if ( $lessonUpdated === null ) {
            $this->invalidateDueDatesSessionCache();
            U::flashError(__('Could not clear the lesson due dates.'));
            return new RedirectResponse($dates_url);
        }

        $this->invalidateDueDatesSessionCache();
        LessonDue::pullPublished($context_id, array_keys($allowed));
        $msg = __('Cleared due dates.').' ('.$updated.' '.__('link row(s) updated').').';
        if ( $lessonUpdated > 0 ) {
            $msg .= ' '.sprintf(__('Cleared %d lesson due date(s).'), $lessonUpdated);
        }
        $msg .= $this->duePushSentence($duePush);
        $this->flashDuePushFailure($duePush);
        U::flashSuccess($msg);
        return new RedirectResponse($dates_url);
    }

    /**
     * Set end_datetime for each link from a base due date on module 0, plus 7 days per module index.
     */
    public function applyWeeklyDueDatesPost(Request $request)
    {
        global $CFG, $PDOX;

        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        LTIX::getConnection();
        $dates_url = $this->manageUrl();
        $csrf = self::requireCsrf($dates_url);
        if ( $csrf ) {
            return $csrf;
        }

        $confirm_error = $this->courseUnlockError();
        if ( $confirm_error !== null ) {
            U::flashError($confirm_error);
            return new RedirectResponse($dates_url);
        }

        if ( ! Manifest::hasCurrent() ) {
            U::flashError(__('Weekly due dates follow the lesson modules.'));
            return new RedirectResponse($dates_url);
        }

        $context_id = ReqScope::currentContextId();
        $raw = U::get($_POST, 'week1_due', '');
        $baseSql = self::parseDueDateEndOfDay($raw);
        if ( $baseSql === null ) {
            U::flashError(__('Enter a valid first-module due date.'));
            return new RedirectResponse($dates_url);
        }

        $baseTs = strtotime($baseSql);
        if ( $baseTs === false ) {
            U::flashError(__('Enter a valid first-module due date.'));
            return new RedirectResponse($dates_url);
        }

        $l = Manifest::requireCurrentLessons();
        $items = $this->dueDateItems($l, $this->manifestCourse());
        $dueMap = GradeUtil::loadDueDatesForContext($context_id);

        $p = $CFG->dbprefix;
        $updated = 0;
        $skipped = 0;
        $lessonDues = array();
        $duePush = $this->emptyDuePush();
        foreach ( $items as $it ) {
            $rlid = $it['resource_link_id'];
            $modIdx = isset($it['module_index']) ? (int) $it['module_index'] : 0;
            $dueTs = strtotime('+' . ($modIdx * 7) . ' days', $baseTs);
            if ( $dueTs === false ) {
                $this->invalidateDueDatesSessionCache();
                U::flashError(__('Could not compute a due date for resource link: ').$rlid);
                return new RedirectResponse($dates_url);
            }
            $it['in_lesson'] = true;
            if ( ! array_key_exists($rlid, $dueMap) ) {
                if ( $this->isUnprovisionedLessonTool($it) ) {
                    $lessonDues[$rlid] = date('Y-m-d', $dueTs);
                } else {
                    $skipped++;
                }
                continue;
            }
            $endSql = date('Y-m-d H:i:s', $dueTs);
            $before = $this->storedDueInstant($context_id, $rlid);
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
                return new RedirectResponse($dates_url);
            }
            $updated += $stmt->rowCount();
            if ( $this->dueInstant($endSql) !== $before ) {
                $this->noteDuePush($context_id, $rlid, $duePush);
            }
        }

        $lessonUpdated = LessonDue::writeItemDues($context_id, $lessonDues);
        if ( $lessonUpdated === null ) {
            $this->invalidateDueDatesSessionCache();
            U::flashError(__('Could not save the lesson due dates.'));
            return new RedirectResponse($dates_url);
        }

        $msg = __('Applied weekly due dates.').' '.sprintf(__('Updated %d link row(s).'), $updated);
        if ( $lessonUpdated > 0 ) {
            $msg .= ' '.sprintf(__('Updated %d lesson due date(s).'), $lessonUpdated);
        }
        if ( $skipped > 0 ) {
            $msg .= ' '.sprintf(__('Skipped %d assignment(s) with no link row yet.'), $skipped);
        }
        $msg .= $this->duePushSentence($duePush);
        $this->flashDuePushFailure($duePush);
        $this->invalidateDueDatesSessionCache();
        $synced = array();
        foreach ( $items as $it ) {
            if ( array_key_exists($it['resource_link_id'], $dueMap) ) {
                $synced[] = $it['resource_link_id'];
            }
        }
        LessonDue::pullPublished($context_id, $synced);
        U::flashSuccess($msg);
        return new RedirectResponse($dates_url);
    }

    /**
     * This course is showing a saved manifest, not the lessons file.
     */
    private function manifestCourse()
    {
        return Manifest::resolvedId() > 0;
    }

    /**
     * Due-date rows. A manifest course also lists its quizzes.
     *
     * @param bool $manifest
     * @return array<int,array<string,mixed>>
     */
    private function dueDateItems(LessonsService $lessons, $manifest)
    {
        $items = $lessons->enumerateLtiAssignmentItems(true);
        if ( ! $manifest || ! isset($lessons->lessons->modules) ) {
            return $items;
        }
        $seen = array();
        foreach ( $lessons->lessons->modules as $modIndex => $module ) {
            if ( ! isset($module->items) || ! is_array($module->items) ) {
                continue;
            }
            foreach ( $module->items as $item ) {
                if ( ! LessonsNormalize::isNativeQuiz($item) ) {
                    continue;
                }
                $quizId = LessonsNormalize::quizIdOf($item);
                if ( $quizId < 1 || isset($seen[$quizId]) || ! $lessons->quiz1ExistsInCourse($quizId) ) {
                    continue;
                }
                $seen[$quizId] = true;
                $title = isset($item->title) && is_string($item->title) && $item->title !== ''
                    ? $item->title
                    : __('Quiz');
                $items[] = array(
                    'module_index' => (int) $modIndex,
                    'module_title' => isset($module->title) ? $module->title : '',
                    'module_hidden' => isset($module->hidden) && $module->hidden,
                    'item_title' => $title,
                    'resource_link_id' => Quiz1Repository::resourceLinkId($quizId, ReqScope::currentContextId()),
                    'quiz_id' => $quizId,
                );
            }
        }
        return $items;
    }

    /**
     * Lesson assignments, existing grade columns, and quizzes that have no link yet.
     *
     * @param int $context_id
     * @return array<int,array<string,mixed>>
     */
    private function manageCatalog($context_id)
    {
        $context_id = (int) $context_id;
        $byKey = array();
        $order = array();
        $manifest = $this->manifestCourse();
        $lessons = Manifest::hasCurrent() ? Manifest::currentLessons() : null;
        if ( $lessons ) {
            foreach ( $this->dueDateItems($lessons, $manifest) as $it ) {
                $key = trim((string) ($it['resource_link_id'] ?? ''));
                if ( $key === '' || isset($byKey[$key]) ) {
                    continue;
                }
                $it['in_lesson'] = true;
                $it['link_id'] = 0;
                $it['category_id'] = null;
                $byKey[$key] = $it;
                $order[] = $key;
            }
            if ( $manifest ) {
                foreach ( $this->manifestMissingGradeRows($lessons, $context_id) as $missing ) {
                    if ( ($missing['kind'] ?? '') !== 'content' ) {
                        continue;
                    }
                    $key = 'content:'.(int) $missing['content_id'];
                    if ( isset($byKey[$key]) ) {
                        continue;
                    }
                    $byKey[$key] = array(
                        'resource_link_id' => '',
                        'item_title' => (string) ($missing['title'] ?? __('Tool')),
                        'module_title' => __('Tool'),
                        'module_hidden' => false,
                        'quiz_id' => 0,
                        'content_id' => (int) $missing['content_id'],
                        'tool_deployment_id' => 0,
                        'in_lesson' => true,
                        'link_id' => 0,
                        'category_id' => null,
                    );
                    $order[] = $key;
                }
            }
        }

        foreach ( GradeCategoryRepository::gradeColumns($context_id, 0, $this->configurationLinkKeys()) as $col ) {
            if ( ! is_array($col) ) {
                continue;
            }
            $key = trim((string) ($col['link_key'] ?? ''));
            if ( $key === '' ) {
                continue;
            }
            if ( ! $manifest && $this->isDiscussionLink($lessons, $key, $col) ) {
                continue;
            }
            if ( ! isset($byKey[$key]) ) {
                $where = Quiz1Repository::isQuizLinkKey($key) ? __('Quiz') : __('Line item');
                $inLesson = $lessons && isset($lessons->resource_links[$key]);
                $module = '';
                if ( $inLesson ) {
                    $mod = $lessons->getModuleByRlid($key);
                    if ( is_object($mod) && isset($mod->title) ) {
                        $module = (string) $mod->title;
                    }
                }
                $byKey[$key] = array(
                    'resource_link_id' => $key,
                    'item_title' => (string) ($col['title'] ?? ''),
                    'module_title' => ($inLesson && $module !== '') ? $module : $where,
                    'module_hidden' => false,
                    'quiz_id' => 0,
                    'content_id' => 0,
                    'tool_deployment_id' => 0,
                    'in_lesson' => $inLesson,
                    'link_id' => (int) $col['link_id'],
                    'category_id' => $col['category_id'] ?? null,
                    'lti13_lineitem' => trim((string) ($col['lti13_lineitem'] ?? '')),
                    'send_grade' => ! empty($col['send_grade']),
                    'upward' => ! empty($col['upward']),
                );
                $order[] = $key;
            } else {
                $byKey[$key]['link_id'] = (int) $col['link_id'];
                $byKey[$key]['category_id'] = $col['category_id'] ?? null;
                $byKey[$key]['lti13_lineitem'] = trim((string) ($col['lti13_lineitem'] ?? ''));
                $byKey[$key]['send_grade'] = ! empty($col['send_grade']);
                $byKey[$key]['upward'] = ! empty($col['upward']);
            }
        }

        foreach ( $this->unlinkedCourseQuizzes($context_id) as $quiz ) {
            $quizId = (int) $quiz['quiz_id'];
            $key = Quiz1Repository::resourceLinkId($quizId, $context_id);
            if ( $key === '' ) {
                continue;
            }
            if ( isset($byKey[$key]) ) {
                if ( empty($byKey[$key]['quiz_id']) ) {
                    $byKey[$key]['quiz_id'] = $quizId;
                }
                continue;
            }
            $title = trim((string) ($quiz['title'] ?? ''));
            if ( $title === '' ) {
                $title = __('Quiz');
            }
            $byKey[$key] = array(
                'resource_link_id' => $key,
                'item_title' => $title,
                'module_title' => __('Quiz'),
                'module_hidden' => false,
                'quiz_id' => $quizId,
                'content_id' => 0,
                'tool_deployment_id' => 0,
                'in_lesson' => false,
                'link_id' => 0,
                'category_id' => null,
            );
            $order[] = $key;
        }

        $items = array();
        foreach ( $order as $key ) {
            $items[] = $byKey[$key];
        }
        return $items;
    }

    /**
     * A discussion launch is not a gradable column on a file-based course.
     *
     * @param LessonsService|null $lessons
     * @param string $key
     * @param array<string,mixed> $col
     */
    private function isDiscussionLink($lessons, $key, $col)
    {
        if ( $lessons ) {
            $item = $lessons->getLtiByRlid($key);
            if ( $item && LessonsNormalize::isDiscussion($item) ) {
                return true;
            }
        }
        $path = (string) ($col['path'] ?? '');
        return str_contains($path, 'tool/tdiscus');
    }

    /**
     * Where a gradable column came from.
     *
     * Lessons and quizzes come from the course. LMS is a grade this link sends
     * up to the LMS. Anything else is a line item held here.
     *
     * @param array<string,mixed> $it
     * @return array{label:string,detail:string,hint:string}
     */
    private function gradeSource($it)
    {
        $key = trim((string) ($it['resource_link_id'] ?? ''));
        $quiz = (int) ($it['quiz_id'] ?? 0) > 0 || Quiz1Repository::isQuizLinkKey($key);
        if ( $quiz ) {
            return array(
                'label' => __('Quiz'),
                'detail' => '',
                'hint' => __('Quiz in this course'),
            );
        }
        if ( ! empty($it['in_lesson']) ) {
            $module = trim((string) ($it['module_title'] ?? ''));
            if ( in_array($module, array(__('Tool'), __('Quiz'), __('Line item')), true) ) {
                $module = '';
            }
            return array(
                'label' => __('Lessons'),
                'detail' => $module,
                'hint' => $module !== '' ? $module : __('From the lesson'),
            );
        }
        $lineitem = trim((string) ($it['lti13_lineitem'] ?? ''));
        if ( $lineitem !== '' || ! empty($it['upward']) ) {
            return array(
                'label' => __('LMS'),
                'detail' => __('up to the LMS'),
                'hint' => __('Grade sent up to the LMS'),
            );
        }
        $detail = ! empty($it['send_grade']) ? __('down from a tool') : '';
        return array(
            'label' => __('Line item'),
            'detail' => $detail,
            'hint' => $detail !== '' ? __('Grade sent down from a tool') : __('Line item in this grade book'),
        );
    }

    /**
     * Link keys on the manage page that already have an lti_link row.
     *
     * @param int $context_id
     * @return array<string,bool>
     */
    private function manageableDueKeys($context_id)
    {
        $dueMap = GradeUtil::loadDueDatesForContext($context_id);
        $allowed = array();
        foreach ( $this->manageCatalog($context_id) as $it ) {
            $key = trim((string) ($it['resource_link_id'] ?? ''));
            if ( $key !== '' && array_key_exists($key, $dueMap) ) {
                $allowed[$key] = true;
            }
        }
        return $allowed;
    }

    /**
     * Resource link ids for lesson tools that are not provisioned yet.
     * Their due date is stored on the lesson item.
     *
     * @param int $context_id
     * @return array<string,bool>
     */
    private function unprovisionedDueKeys($context_id)
    {
        $allowed = array();
        foreach ( $this->manageCatalog($context_id) as $it ) {
            if ( ! $this->isUnprovisionedLessonTool($it) ) {
                continue;
            }
            $key = trim((string) ($it['resource_link_id'] ?? ''));
            if ( $key === '' ) {
                continue;
            }
            $allowed[$key] = true;
        }
        return $allowed;
    }

    /**
     * Quizzes in this course that do not have a grade column yet.
     *
     * @param int $context_id
     * @return array<int,array<string,mixed>>
     */
    private function unlinkedCourseQuizzes($context_id)
    {
        global $CFG, $PDOX;
        $rows = $PDOX->allRowsDie(
            "SELECT quiz_id, title FROM {$CFG->dbprefix}quiz1_quiz
             WHERE context_id = :CID AND (link_id IS NULL OR link_id = 0)",
            array(':CID' => (int) $context_id)
        );
        return is_array($rows) ? $rows : array();
    }

    /**
     * @param int $context_id
     */
    private function addUnlinkedQuizLinks($context_id)
    {
        $added = 0;
        foreach ( $this->unlinkedCourseQuizzes($context_id) as $quiz ) {
            $linkId = Quiz1Repository::ensureLink((int) $quiz['quiz_id'], (int) $context_id);
            if ( $linkId ) {
                $added++;
            }
        }
        return $added;
    }

    /**
     * @param string $home
     * @param int $link_id
     * @param mixed $category_id
     * @param array<int,array<string,mixed>> $categories
     * @param string $title
     */
    private function echoCategoryForm($home, $link_id, $category_id, $categories, $title)
    {
        $selected = ($category_id === null || $category_id === '') ? 0 : (int) $category_id;
        $action = $home.'/links/'.$link_id;
        echo '<form method="post" action="'.htmlspecialchars($action).'" class="form-inline">';
        echo self::csrfField();
        echo '<label class="sr-only" for="link-category-'.$link_id.'">'.htmlspecialchars(__('Category for').' '.$title).'</label>';
        echo '<select class="form-control" id="link-category-'.$link_id.'" name="category_id">';
        echo '<option value=""'.($selected === 0 ? ' selected' : '').'>'.htmlspecialchars(__('Uncategorized')).'</option>';
        foreach ( $categories as $category ) {
            $id = (int) $category['category_id'];
            echo '<option value="'.$id.'"'.($selected === $id ? ' selected' : '').'>'
                .htmlspecialchars((string) $category['title']).'</option>';
        }
        echo '</select> ';
        echo '<button type="submit" class="btn btn-default" name="action" value="category">'.htmlspecialchars(__('Save')).'</button>';
        echo '</form>';
    }

    /**
     * link_key => published and hidden. A missing key means there is no link row.
     *
     * @param int $context_id
     * @return array<string,array{published:bool,hidden:bool}>
     */
    private function dueDateLinkFlags($context_id)
    {
        global $CFG, $PDOX;
        $rows = $PDOX->allRowsDie(
            "SELECT link_key, published, hidden FROM {$CFG->dbprefix}lti_link
             WHERE context_id = :CID AND (deleted IS NULL OR deleted = 0)",
            array(':CID' => (int) $context_id)
        );
        $map = array();
        if ( ! is_array($rows) ) {
            return $map;
        }
        foreach ( $rows as $row ) {
            if ( ! is_array($row) ) {
                continue;
            }
            $key = trim((string) ($row['link_key'] ?? ''));
            if ( $key === '' ) {
                continue;
            }
            $map[$key] = array(
                'published' => (int) ($row['published'] ?? 0) === 1,
                'hidden' => (int) ($row['hidden'] ?? 0) === 1,
            );
        }
        return $map;
    }

    /**
     * @param int $context_id
     * @param string $link_key
     */
    private function linkIdForKey($context_id, $link_key)
    {
        global $CFG, $PDOX;
        $row = $PDOX->rowDie(
            "SELECT link_id FROM {$CFG->dbprefix}lti_link
             WHERE context_id = :CID AND link_key = :LK AND (deleted IS NULL OR deleted = 0)
             LIMIT 1",
            array(
                ':CID' => (int) $context_id,
                ':LK' => $link_key,
            )
        );
        if ( ! is_array($row) || empty($row['link_id']) ) {
            return 0;
        }
        return (int) $row['link_id'];
    }

    /**
     * resource_link_id => whether students can see this content launch.
     * A grade column uses the link. Otherwise the content row.
     *
     * @param int $context_id
     * @return array<string,bool>
     */
    private function dueDateContentPublished($context_id)
    {
        global $CFG, $PDOX;
        $p = $CFG->dbprefix;
        $rows = $PDOX->allRowsDie(
            "SELECT c.resource_link_id, c.link_id, c.published, l.published AS link_published
             FROM {$p}lti_content c
             LEFT JOIN {$p}lti_link l ON l.link_id = c.link_id
             WHERE c.context_id = :CID",
            array(':CID' => (int) $context_id)
        );
        $map = array();
        if ( ! is_array($rows) ) {
            return $map;
        }
        foreach ( $rows as $row ) {
            if ( ! is_array($row) ) {
                continue;
            }
            $key = trim((string) ($row['resource_link_id'] ?? ''));
            if ( $key === '' ) {
                continue;
            }
            if ( ! empty($row['link_id']) ) {
                $map[$key] = isset($row['link_published']) && (int) $row['link_published'] === 1;
            } else {
                $map[$key] = (int) ($row['published'] ?? 0) === 1;
            }
        }
        return $map;
    }

    /**
     * Mark a due-date row students cannot open. No link row on a file lesson stays unmarked.
     *
     * @param array<string,mixed> $item
     * @param array<string,array{published:bool,hidden:bool}> $linkFlags
     * @param array<string,bool> $contentPublished
     */
    private function echoDueDateVisibility($item, $linkFlags, $contentPublished)
    {
        $rlid = trim((string) ($item['resource_link_id'] ?? ''));
        $link = ($rlid !== '' && isset($linkFlags[$rlid])) ? $linkFlags[$rlid] : null;
        $notes = array();
        if ( $rlid !== '' && array_key_exists($rlid, $contentPublished) ) {
            if ( ! $contentPublished[$rlid] ) {
                $notes[] = __('unpublished');
            }
        } else if ( ! empty($item['quiz_id']) ) {
            if ( $link === null || empty($link['published']) ) {
                $notes[] = __('unpublished');
            }
        } else if ( $link !== null && empty($link['published']) ) {
            $notes[] = __('unpublished');
        }
        if ( ! empty($item['module_hidden']) || ($link !== null && ! empty($link['hidden'])) ) {
            $notes[] = __('hidden');
        }
        if ( count($notes) < 1 ) {
            return;
        }
        echo ' <span class="text-muted">('.htmlspecialchars(implode(', ', $notes)).')</span>';
    }

    /**
     * Gradable tools with no grade column, and quizzes with no link.
     *
     * @param int $context_id
     * @return array<int,array{kind:string,content_id?:int,quiz_id?:int,title:string}>
     */
    private function manifestMissingGradeRows(LessonsService $lessons, $context_id)
    {
        $missing = array();
        $seenContent = array();
        $seenQuiz = array();
        if ( ! isset($lessons->lessons->modules) ) {
            return $missing;
        }
        foreach ( $lessons->lessons->modules as $module ) {
            if ( ! isset($module->items) || ! is_array($module->items) ) {
                continue;
            }
            foreach ( $module->items as $item ) {
                $contentId = isset($item->content_id) ? (int) $item->content_id : 0;
                if ( $contentId > 0 ) {
                    if ( isset($seenContent[$contentId]) ) {
                        continue;
                    }
                    $seenContent[$contentId] = true;
                    $row = LtiContentService::find((int) $context_id, $contentId);
                    if ( $row === null || (int) ($row['send_grade'] ?? 0) !== 1 || ! empty($row['link_id']) ) {
                        continue;
                    }
                    $title = isset($item->title) && is_string($item->title) && $item->title !== ''
                        ? $item->title
                        : (string) ($row['title'] ?? '');
                    $missing[] = array(
                        'kind' => 'content',
                        'content_id' => $contentId,
                        'title' => $title,
                    );
                    continue;
                }
                if ( ! LessonsNormalize::isNativeQuiz($item) ) {
                    continue;
                }
                $quizId = LessonsNormalize::quizIdOf($item);
                if ( $quizId < 1 || isset($seenQuiz[$quizId]) || ! $lessons->quiz1ExistsInCourse($quizId) ) {
                    continue;
                }
                $seenQuiz[$quizId] = true;
                if ( $this->quizLinkId((int) $context_id, $quizId) > 0 ) {
                    continue;
                }
                $title = isset($item->title) && is_string($item->title) && $item->title !== ''
                    ? $item->title
                    : __('Quiz');
                $missing[] = array(
                    'kind' => 'quiz',
                    'quiz_id' => $quizId,
                    'title' => $title,
                );
            }
        }
        return $missing;
    }

    /**
     * @param int $context_id
     * @param int $quiz_id
     */
    private function quizLinkId($context_id, $quiz_id)
    {
        global $CFG, $PDOX;
        $row = $PDOX->rowDie(
            "SELECT link_id FROM {$CFG->dbprefix}quiz1_quiz
             WHERE quiz_id = :QID AND context_id = :CID",
            array(':QID' => (int) $quiz_id, ':CID' => (int) $context_id)
        );
        if ( ! is_array($row) || empty($row['link_id']) ) {
            return 0;
        }
        return (int) $row['link_id'];
    }

    /**
     * A lesson tool with no deployment and no content row. Provisioning creates those.
     * Add missing link rows does not.
     *
     * @param array<string,mixed> $it
     */
    private function isUnprovisionedLessonTool($it)
    {
        if ( ! $this->manifestCourse() || empty($it['in_lesson']) ) {
            return false;
        }
        if ( (int) ($it['quiz_id'] ?? 0) > 0 ) {
            return false;
        }
        $key = trim((string) ($it['resource_link_id'] ?? ''));
        if ( str_starts_with($key, 'quiz1:') ) {
            return false;
        }
        return (int) ($it['content_id'] ?? 0) < 1 && (int) ($it['tool_deployment_id'] ?? 0) < 1;
    }

    /**
     * @param int $context_id
     * @param string $dates_url
     */
    private function addManifestGradeRows(LessonsService $lessons, $context_id, $dates_url)
    {
        $gradeColumns = 0;
        $quizLinks = 0;
        $titles = array();
        foreach ( $this->manifestMissingGradeRows($lessons, $context_id) as $row ) {
            if ( $row['kind'] === 'content' ) {
                $linkId = LtiContentService::ensureGradeColumnForLaunch($context_id, (int) $row['content_id']);
                if ( ! $linkId ) {
                    continue;
                }
                $gradeColumns++;
                $titles[] = (string) $row['title'];
                continue;
            }
            $linkId = Quiz1Repository::ensureLink((int) $row['quiz_id'], $context_id);
            if ( ! $linkId ) {
                continue;
            }
            $quizLinks++;
            $titles[] = (string) $row['title'];
        }
        foreach ( $this->unlinkedCourseQuizzes($context_id) as $quiz ) {
            $linkId = Quiz1Repository::ensureLink((int) $quiz['quiz_id'], $context_id);
            if ( ! $linkId ) {
                continue;
            }
            $quizLinks++;
            $titles[] = (string) ($quiz['title'] ?? __('Quiz'));
        }

        if ( $gradeColumns < 1 && $quizLinks < 1 ) {
            U::flashSuccess(__('No missing link rows; nothing to add.'));
            return new RedirectResponse($dates_url);
        }

        $this->invalidateDueDatesSessionCache();
        $msg = __('Added link rows.').' '.
            sprintf(__('Grade columns added: %d.'), $gradeColumns).' '.
            sprintf(__('Quiz links added: %d.'), $quizLinks).' '.
            __('Published tools and quizzes were left as they were.');
        if ( count($titles) > 0 ) {
            $msg .= ' '.implode(', ', $titles).'.';
        }
        U::flashSuccess($msg);
        return new RedirectResponse($dates_url);
    }

    /**
     * Insert lti_link rows for lesson assignments that have no row in this context yet.
     * Uses the same statement as LTIX::adjustData() so a later launch does not create a duplicate.
     */
    public function addMissingLinkRowsPost(Request $request)
    {
        global $CFG, $PDOX;

        $this->requireLessonGrades();
        $dates_url = $this->manageUrl();
        $csrf = self::requireCsrf($dates_url);
        if ( $csrf ) {
            return $csrf;
        }

        $confirm_error = $this->courseUnlockError();
        if ( $confirm_error !== null ) {
            U::flashError($confirm_error);
            return new RedirectResponse($dates_url);
        }

        $context_id = ReqScope::currentContextId();
        $l = Manifest::requireCurrentLessons();
        if ( $this->manifestCourse() ) {
            return $this->addManifestGradeRows($l, $context_id, $dates_url);
        }
        $items = $l->enumerateLtiAssignmentItems(true);
        $dueMap = GradeUtil::loadDueDatesForContext($context_id);

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
                return new RedirectResponse($dates_url);
            }
            $rc = $stmt->rowCount();
            if ( $rc === 1 ) {
                $inserted++;
            }
            $dueMap[$rlid] = array('start_datetime' => null, 'end_datetime' => null);
        }

        $quizAdded = $this->addUnlinkedQuizLinks($context_id);
        if ( $attempted < 1 && $quizAdded < 1 ) {
            U::flashSuccess(__('No missing link rows; nothing to add.'));
        } else if ( $attempted < 1 ) {
            $this->invalidateDueDatesSessionCache();
            U::flashSuccess(__('Added link rows.').' '.sprintf(__('Quiz links added: %d.'), $quizAdded));
        } else {
            $this->invalidateDueDatesSessionCache();
            U::flashSuccess(
                __('Added link rows.').' '.sprintf(__('New inserts: %d.'), $inserted).' '.
                sprintf(__('Missing assignments processed: %d. Same insert as launch—no duplicate if a row already existed.'), $attempted)
            );
        }
        return new RedirectResponse($dates_url);
    }

    /**
     * Plus AGS log. Errors are listed before the successful sends.
     */
    public function agsLog(Request $request)
    {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        LTIX::getConnection();
        $contextId = ReqScope::currentContextId();
        if ( ! $this->plusManageState($contextId)['plus'] ) {
            return new RedirectResponse(U::addSession($home));
        }
        $log = GradeSync::activityLog($contextId);
        $this->beginLessonGradePage('log');
        include __DIR__.'/templates/Grades/ags_log.inc.php';
        $this->endLessonGradePage();
    }

    /**
     * Explain the resend. The POST is the second press that talks to the LMS.
     */
    public function resend(Request $request)
    {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        LTIX::getConnection();
        $user_id = $this->resendTargetUserId($request);
        $this->beginLessonGradePage('resend', $user_id);
        $this->renderResendIntro($user_id);
        $this->renderResendForm($user_id);
        $this->endLessonGradePage();
    }

    /**
     * Read each upward grade from the LMS and send the local grade when the server is behind.
     */
    public function resendPost(Request $request)
    {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        LTIX::getConnection();
        $user_id = $this->resendTargetUserId($request);
        $page = $this->resendPageUrl($user_id);
        $csrf = self::requireCsrf($page);
        if ( $csrf ) {
            return $csrf;
        }

        $this->beginLessonGradePage('resend', $user_id);
        $this->renderResendIntro($user_id);
        $this->renderResendForm($user_id);
        echo '<pre class="grade-resend-log">'."\n";
        $this->runResendLog($user_id);
        echo "</pre>\n";
        $this->endLessonGradePage();
    }

    /**
     * @param int $user_id
     */
    private function renderResendIntro($user_id)
    {
        echo '<h2>'.htmlspecialchars(__('Resend grades'))."</h2>\n";
        echo '<p>'.htmlspecialchars(__('Reads each grade that was sent up to the LMS. When the server copy is behind the grade stored here, sends it again.'))."</p>\n";
    }

    /**
     * @param int $user_id
     */
    private function renderResendForm($user_id)
    {
        if ( ! $this->userHasOutcomeResults(ReqScope::currentContextId(), $user_id) ) {
            echo '<p>'.htmlspecialchars(__('No upward grades to resend.'))."</p>\n";
            return;
        }
        $action = $this->toolHome(self::ROUTE).'/resend';
        echo '<form method="post" action="'.htmlspecialchars($action).'">';
        echo self::csrfField();
        if ( (int) $user_id !== (int) ReqScope::loggedInUserId() ) {
            echo '<input type="hidden" name="user_id" value="'.(int) $user_id.'">';
        }
        echo '<button type="submit" class="btn btn-primary">'.htmlspecialchars(__('Resend grades')).'</button>';
        echo "</form>\n";
    }

    /**
     * The user whose upward grades this page covers.
     */
    private function resendTargetUserId(Request $request)
    {
        $user_id = (int) ReqScope::loggedInUserId();
        $requested = 0;
        if ( $request->request->has('user_id') ) {
            $requested = (int) $request->request->get('user_id');
        } else if ( $request->query->has('user_id') ) {
            $requested = (int) $request->query->get('user_id');
        }
        if ( $requested > 0 ) {
            return $requested;
        }
        return $user_id;
    }

    /**
     * @param int $user_id
     */
    private function resendPageUrl($user_id)
    {
        $url = $this->toolHome(self::ROUTE).'/resend';
        if ( (int) $user_id !== (int) ReqScope::loggedInUserId() ) {
            $url .= '?user_id='.(int) $user_id;
        }
        return $url;
    }

    /**
     * @param int $user_id
     */
    private function runResendLog($user_id)
    {
        set_time_limit(0);
        $rows = $this->upwardGradeRows(ReqScope::currentContextId(), $user_id);
        $ok = 0;
        $fail = 0;
        if ( count($rows) < 1 ) {
            $this->echoResendLine(__('No upward grades to resend.'));
            return;
        }
        foreach ( $rows as $row ) {
            $line = $this->resendOneGrade($row);
            if ( str_starts_with($line, 'fail') ) {
                $fail++;
            } else {
                $ok++;
            }
            $title = trim((string) U::get($row, 'title', ''));
            if ( $title === '' ) {
                $title = 'result '.(int) $row['result_id'];
            }
            $this->echoResendLine($title.': '.$line);
        }
        $this->echoResendLine(__('Done.').' '.$ok.' '.__('success').', '.$fail.' '.__('fail').'.');
    }

    /**
     * @param array<string,mixed> $row
     */
    private function resendOneGrade($row)
    {
        $server = LTIX::gradeGet($row);
        if ( is_string($server) && trim($server) === '' ) {
            $server = 0.0;
        }
        if ( ! is_numeric($server) ) {
            $reason = is_string($server) && $server !== '' ? $server : __('could not read the server grade');
            return 'fail: '.$reason;
        }
        if ( (float) $server >= (float) $row['grade'] ) {
            return 'success';
        }
        $debug_log = array();
        $status = LTIX::gradeSend($row['grade'], $row, $debug_log);
        if ( $status !== true ) {
            $reason = is_string($status) && $status !== '' ? $status : __('send failed');
            return 'fail: '.$reason;
        }
        $again = LTIX::gradeGet($row);
        if ( is_string($again) && trim($again) === '' ) {
            $again = 0.0;
        }
        if ( ! is_numeric($again) ) {
            return 'fail: '.__('sent, then could not read the server grade');
        }
        if ( (float) $again != (float) $row['grade'] ) {
            return 'fail: '.__('sent, server grade is').' '.$again;
        }
        return 'success';
    }

    /**
     * @param string $line
     */
    private function echoResendLine($line)
    {
        echo htmlspecialchars($line)."\n";
        if ( ob_get_level() > 0 ) {
            ob_flush();
        }
        flush();
    }

    /**
     * LTI 1.1 results this resend can read and send.
     *
     * @param int $context_id
     * @param int $user_id
     * @return array<int,array<string,mixed>>
     */
    private function upwardGradeRows($context_id, $user_id)
    {
        global $CFG, $PDOX;
        LTIX::getConnection();
        $p = $CFG->dbprefix;
        $rows = $PDOX->allRowsDie(
            "SELECT R.result_id AS result_id, L.title AS title, R.grade AS grade,
                R.sourcedid AS sourcedid, S.service_key AS service
             FROM {$p}lti_result AS R
             JOIN {$p}lti_link AS L ON R.link_id = L.link_id
             LEFT JOIN {$p}lti_service AS S ON R.service_id = S.service_id
             WHERE R.user_id = :UID AND L.context_id = :CID
               AND R.grade > 0 AND R.deleted = 0 AND L.deleted = 0 AND L.hidden = 0
               AND R.sourcedid IS NOT NULL AND R.sourcedid <> ''
               AND R.service_id IS NOT NULL
             ORDER BY L.title ASC",
            array(':UID' => (int) $user_id, ':CID' => (int) $context_id)
        );
        return is_array($rows) ? $rows : array();
    }

    /**
     * Analytics view for grades
     */
    public function analytics(Request $request)
    {
        if ( ! $this->isLtiLaunchSession() ) {
            return new RedirectResponse($this->toolHome(self::ROUTE));
        }
        return $this->showAnalytics(self::ROUTE, 'Grade Book');
    }

    public function categoryAddPost(Request $request)
    {
        return $this->categoryWrite($request, 0, 'add');
    }

    public function categoryPost(Request $request, $id)
    {
        $action = U::get($_POST, 'action', 'save');
        return $this->categoryWrite($request, (int) $id, is_string($action) ? $action : 'save');
    }

    public function createLineItemPost(Request $request, $id)
    {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $back = $this->manageUrl();
        $csrf = self::requireCsrf($back);
        if ( $csrf ) {
            return $csrf;
        }

        $context_id = ReqScope::currentContextId();
        $linkId = (int) $id;
        if ( ! $this->linkInContext($context_id, $linkId) ) {
            U::flashError(__('This link is not in this course.'));
            return new RedirectResponse($back);
        }

        $result = GradeSync::requestLineItem($linkId);
        $status = (string) ($result['status'] ?? '');
        if ( ! empty($result['ok']) && $status === 'created' ) {
            U::flashSuccess(__('Line item created.'));
        } else if ( ! empty($result['ok']) && $status === 'exists' ) {
            U::flashSuccess(__('This link already has a line item.'));
        } else {
            U::flashError($this->lineItemError($result));
        }
        return new RedirectResponse($back);
    }

    /**
     * Teacher override of one stored grade. A line item that already exists is sent.
     */
    public function overridePost(Request $request, $id)
    {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        LTIX::getConnection();
        $back = $this->safeGradeReturn((string) $request->request->get('return', ''));
        $csrf = self::requireCsrf($back);
        if ( $csrf ) {
            return $csrf;
        }

        $resultId = (int) $id;
        if ( ! $this->resultInContext(ReqScope::currentContextId(), $resultId) ) {
            U::flashError(__('That grade is not in this course.'));
            return new RedirectResponse($back);
        }

        $result = GradeSync::applyOverride($resultId, $request->request->get('points', ''));
        $status = (string) ($result['status'] ?? '');
        if ( empty($result['ok']) ) {
            $message = (string) ($result['message'] ?? '');
            U::flashError($message !== '' ? $message : __('That grade was not found.'));
        } else if ( $status === 'sent' ) {
            U::flashSuccess(__('Grade stored and sent.'));
        } else if ( $status === 'held' ) {
            U::flashSuccess(__('Grade stored. Grades for this activity are not released.'));
        } else if ( $status === 'send_failed' ) {
            $message = trim((string) ($result['message'] ?? ''));
            U::flashError($message !== '' ? __('Grade stored.').' '.$message : __('Grade stored. The score was not sent.'));
        } else {
            U::flashSuccess(__('Grade stored.'));
        }
        return new RedirectResponse($back);
    }

    /**
     * @param int $context_id
     * @param int $result_id
     */
    private function resultInContext($context_id, $result_id)
    {
        global $CFG, $PDOX;
        if ( $result_id < 1 ) {
            return false;
        }
        $row = $PDOX->rowDie(
            "SELECT R.result_id
             FROM {$CFG->dbprefix}lti_result AS R
             JOIN {$CFG->dbprefix}lti_link AS L ON L.link_id = R.link_id
             WHERE R.result_id = :RID AND L.context_id = :CID
               AND R.deleted = 0 AND L.deleted = 0",
            array(':RID' => (int) $result_id, ':CID' => (int) $context_id)
        );
        return is_array($row);
    }

    /**
     * @param int $context_id
     * @return array{plus:bool,shell_link_id:int}
     */
    private function plusManageState($context_id)
    {
        global $CFG, $PDOX;
        $row = $PDOX->rowDie(
            "SELECT plus_course, plus_launch_link_id
             FROM {$CFG->dbprefix}lti_context
             WHERE context_id = :CID",
            array(':CID' => (int) $context_id)
        );
        return array(
            'plus' => is_array($row) && (int) ($row['plus_course'] ?? 0) === 1,
            'shell_link_id' => is_array($row) ? (int) ($row['plus_launch_link_id'] ?? 0) : 0,
        );
    }

    /**
     * @param int $context_id
     * @param int $link_id
     */
    private function linkInContext($context_id, $link_id)
    {
        global $CFG, $PDOX;
        if ( $link_id < 1 ) {
            return false;
        }
        $row = $PDOX->rowDie(
            "SELECT link_id FROM {$CFG->dbprefix}lti_link
             WHERE link_id = :LID AND context_id = :CID AND deleted = 0",
            array(':LID' => $link_id, ':CID' => (int) $context_id)
        );
        return is_array($row);
    }

    /**
     * @param string $home
     * @param int $linkId
     * @param array<string,mixed> $it
     * @param int $shellLinkId
     */
    private function echoLineItemCell($home, $linkId, array $it, $shellLinkId)
    {
        if ( $linkId < 1 ) {
            echo('<span class="text-muted">—</span>');
            return;
        }
        if ( $shellLinkId > 0 && $linkId === $shellLinkId ) {
            echo('<span class="text-muted">'.htmlspecialchars(__('Not a grade column')).'</span>');
            return;
        }
        $lineitem = trim((string) ($it['lti13_lineitem'] ?? ''));
        if ( $lineitem !== '' ) {
            echo('<span>'.htmlspecialchars(__('Created')).'</span>');
            return;
        }
        $title = (string) ($it['item_title'] ?? '');
        $action = $home.'/links/'.$linkId.'/lineitem';
        echo('<span class="text-muted">'.htmlspecialchars(__('None')).'</span> ');
        echo('<form method="post" action="'.htmlspecialchars($action).'" class="form-inline">');
        echo(self::csrfField());
        echo('<button type="submit" class="btn btn-default btn-xs" aria-label="'.htmlspecialchars(__('Create line item').': '.$title).'">');
        echo(htmlspecialchars(__('Create line item')));
        echo('</button></form>');
    }

    /**
     * @param array{ok?:bool,status?:string,message?:string} $result
     */
    private function lineItemError(array $result)
    {
        $status = (string) ($result['status'] ?? '');
        if ( $status === 'not_found' ) {
            return __('This link was not found.');
        }
        if ( $status === 'not_plus' ) {
            return __('This course is not a Plus course.');
        }
        if ( $status === 'shell' ) {
            return __('The course launch link is not a grade column.');
        }
        if ( $status === 'not_ready' ) {
            return __('This activity needs a title and a point total before it can have a line item.');
        }
        if ( $status === 'no_lineitems_url' ) {
            return __('This course has no line items URL.');
        }
        if ( $status === 'no_deployment' ) {
            return __('This course has no deployment id.');
        }
        if ( $status === 'no_token_url' ) {
            return __('This tenant has no token URL.');
        }
        if ( $status === 'no_client' ) {
            return __('This tenant has no client id.');
        }
        $message = trim((string) ($result['message'] ?? ''));
        if ( $message !== '' ) {
            return $message;
        }
        return __('The line item was not created.');
    }

    public function linkPost(Request $request, $id)
    {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $csrf = self::requireCsrf($this->configurationUrl());
        if ( $csrf ) {
            return $csrf;
        }

        $context_id = ReqScope::currentContextId();
        $action = U::get($_POST, 'action', '');
        $category_raw = U::get($_POST, 'category_id', '');
        $category_id = ($category_raw === '' || $category_raw === null) ? null : (int) $category_raw;
        $error = GradeCategoryRepository::setLinkCategory($context_id, (int) $id, $category_id);
        if ( $error === null && $action === 'hide' ) {
            $error = GradeCategoryRepository::setLinkHidden($context_id, (int) $id, true);
        } else if ( $error === null && $action === 'unhide' ) {
            $error = GradeCategoryRepository::setLinkHidden($context_id, (int) $id, false);
        } else if ( $error === null && $action !== 'category' ) {
            $error = __('Unknown grade action.');
        }

        if ( $error !== null ) {
            U::flashError($error);
        } else if ( $action === 'hide' ) {
            U::flashSuccess(__('Grade hidden from students.'));
        } else if ( $action === 'unhide' ) {
            U::flashSuccess(__('Grade unhidden.'));
        } else {
            U::flashSuccess(__('Grade category saved.'));
        }
        return new RedirectResponse($this->configurationUrl());
    }

    /**
     * @return RedirectResponse
     */
    private function categoryWrite(Request $request, $category_id, $action)
    {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $csrf = self::requireCsrf($this->configurationUrl());
        if ( $csrf ) {
            return $csrf;
        }

        $context_id = ReqScope::currentContextId();
        $error = null;
        $success = '';
        if ( $action === 'add' ) {
            $error = GradeCategoryRepository::create(
                $context_id,
                U::get($_POST, 'title', ''),
                U::get($_POST, 'grade_percent', ''),
                U::get($_POST, 'extra_credit', '') ? true : false
            );
            $success = __('Category added.');
        } else if ( $action === 'save' ) {
            $error = GradeCategoryRepository::update(
                $context_id,
                $category_id,
                U::get($_POST, 'title', ''),
                U::get($_POST, 'grade_percent', ''),
                U::get($_POST, 'extra_credit', '') ? true : false
            );
            $success = __('Category saved.');
        } else if ( $action === 'delete' ) {
            $error = GradeCategoryRepository::delete($context_id, $category_id);
            $success = __('Category deleted. Its grades are uncategorized.');
        } else if ( $action === 'move' ) {
            $error = GradeCategoryRepository::move(
                $context_id,
                $category_id,
                (string) U::get($_POST, 'direction', '')
            );
            $success = __('Category moved.');
        } else {
            $error = __('Unknown category action.');
        }

        if ( $error !== null ) {
            U::flashError($error);
        } else if ( $success !== '' ) {
            U::flashSuccess($success);
        }
        return new RedirectResponse($this->configurationUrl());
    }

    /**
     * @return string
     */
    private function configurationUrl()
    {
        return $this->manageUrl();
    }

    private function manageUrl()
    {
        return $this->toolHome(self::ROUTE).'/manage';
    }

    /**
     * Lesson assignment link keys, so a column can be categorized before a score exists.
     *
     * @return array<int,string>
     */
    private function configurationLinkKeys()
    {
        if ( ! Manifest::hasCurrent() ) {
            return array();
        }
        $keys = array();
        foreach ( Manifest::requireCurrentLessons()->enumerateLtiAssignmentItems(true) as $item ) {
            $key = isset($item['resource_link_id']) ? trim((string) $item['resource_link_id']) : '';
            if ( $key !== '' ) {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    private function renderConfiguration($context_id, $post_home)
    {
        $categories = GradeCategoryRepository::listForContext($context_id);
        $grade_columns = GradeCategoryRepository::gradeColumns($context_id, 0, $this->configurationLinkKeys());
        $return_inputs = '';
        include __DIR__ . '/templates/Grades/manage_dialog.inc.php';
    }

    /**
     * @param array<int,array<string,mixed>> $categories
     * @param array<int,array<string,mixed>> $rows
     * @param int $context_id
     * @param int $user_id
     */
    private function renderGradeRows($grouped, $categories, $rows, $context_id = 0, $user_id = 0)
    {
        if ( $grouped ) {
            $this->renderGroupedGrades($categories, $rows, $context_id, $user_id);
            return;
        }
        Table::pagedTable($this->withUniformServerColumns($rows), array(), false);
    }

    /**
     * LTI 1.1 needs sourcedid and the outcome service. LTI 1.3 needs this
     * link's line item and this user's subject key. Either pair means the
     * result is sent upward.
     *
     * @param array<string,mixed> $row
     */
    private function resultPassedUpward($row)
    {
        if ( $this->resultHasOutcomeService($row) ) {
            return true;
        }
        $lineitem = trim((string) U::get($row, 'lti13_lineitem', ''));
        $subject = trim((string) U::get($row, 'subject_key', ''));
        return $lineitem !== '' && $subject !== '';
    }

    /**
     * Resend tab for this user's LTI 1.1 upward grades. Empty when there are none.
     *
     * @param int $context_id
     * @param int $user_id
     * @param string $tool_home
     * @param bool $include_user
     */
    private function resendUrl($context_id, $user_id, $tool_home, $include_user)
    {
        if ( ! $this->userHasOutcomeResults($context_id, $user_id) ) {
            return '';
        }
        $url = $tool_home.'/resend';
        if ( $include_user ) {
            $url .= '?user_id='.(int) $user_id;
        }
        return $url;
    }

    /**
     * A visible grade above zero with both sourcedid and an outcome service.
     *
     * @param int $context_id
     * @param int $user_id
     */
    private function userHasOutcomeResults($context_id, $user_id)
    {
        global $CFG, $PDOX;
        LTIX::getConnection();
        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie(
            "SELECT R.result_id
             FROM {$p}lti_result AS R
             JOIN {$p}lti_link AS L ON R.link_id = L.link_id
             WHERE R.user_id = :UID AND L.context_id = :CID
               AND R.grade > 0 AND R.deleted = 0 AND L.deleted = 0 AND L.hidden = 0
               AND R.sourcedid IS NOT NULL AND R.sourcedid <> ''
               AND R.service_id IS NOT NULL
             LIMIT 1",
            array(':UID' => (int) $user_id, ':CID' => (int) $context_id)
        );
        return is_array($row);
    }

    /**
     * @param array<string,mixed> $row
     */
    private function resultHasOutcomeService($row)
    {
        $sourcedid = trim((string) U::get($row, 'sourcedid', ''));
        $service = trim((string) U::get($row, 'service', ''));
        if ( $service === '' ) {
            $service = trim((string) U::get($row, 'service_key', ''));
        }
        return $sourcedid !== '' && $service !== '';
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     */
    private function dropServerColumnsUnlessUpward(&$rows)
    {
        foreach ( $rows as &$row ) {
            $upward = $this->resultPassedUpward($row);
            unset($row['sourcedid']);
            unset($row['service']);
            unset($row['service_key']);
            unset($row['lti13_lineitem']);
            unset($row['subject_key']);
            unset($row['result_url']);
            $plus = $this->rowIsPlus($row);
            unset($row['plus_course']);
            if ( ! $upward && ! $plus ) {
                unset($row['server_grade']);
                unset($row['retrieved_at']);
                unset($row['server_status']);
            }
        }
        unset($row);
    }

    /**
     * pagedTable reads columns from the first row. Keep server columns on
     * every row when any visible row is sent upward.
     *
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function withUniformServerColumns($rows)
    {
        $keys = array('server_grade', 'retrieved_at', 'server_status');
        $present = array();
        foreach ( $rows as $row ) {
            foreach ( $keys as $key ) {
                if ( array_key_exists($key, $row) ) {
                    $present[$key] = true;
                }
            }
        }
        if ( count($present) < 1 ) {
            return $rows;
        }
        $aligned = array();
        foreach ( $rows as $row ) {
            foreach ( $keys as $key ) {
                if ( isset($present[$key]) && ! array_key_exists($key, $row) ) {
                    $row[$key] = '';
                }
            }
            $aligned[] = $this->orderGradeBookColumns($row);
        }
        return $aligned;
    }

    /**
     * Student name under the tabs when an instructor opened one student from All Students.
     *
     * @param array<string,mixed>|false $user_info
     */
    private function echoViewedStudent($user_info)
    {
        if ( ! is_array($user_info) || ! isset($user_info['displayname']) ) {
            return;
        }
        $name = trim((string) $user_info['displayname']);
        if ( $name === '' ) {
            return;
        }
        echo '<p>'.htmlspecialchars(__('Results for')).' '.htmlspecialchars($name)."</p>\n";
    }

    /**
     * Grade book tabs. Each tab is its own page, with this page's tab selected.
     *
     * @param array{active:string,outline_url:string,grades_href:string,class_url:string,resend_url:string,analytics_url:string,configure_url:string,due_dates_url:string,progress_url:string} $nav
     */
    private function renderGradeNav($nav)
    {
        echo '<ul class="nav nav-tabs tsugi-grade-tabs" role="tablist">';
        $this->renderHrefTab('grades-link', __('Grades'), $nav['grades_href'], $nav['active'] === 'grades');
        if ( $nav['outline_url'] !== '' ) {
            $this->renderHrefTab('outline-link', __('Course Outline'), $nav['outline_url'], $nav['active'] === 'outline');
        }
        if ( $nav['class_url'] !== '' ) {
            $this->renderHrefTab('class-grades-tab', __('All Students'), $nav['class_url'], $nav['active'] === 'class');
        }
        if ( $nav['progress_url'] !== '' ) {
            $this->renderHrefTab('student-progress-tab', __('Student Progress'), $nav['progress_url'], $nav['active'] === 'progress');
        }
        if ( $nav['due_dates_url'] !== '' ) {
            $this->renderHrefTab('manage-gradebook-tab', __('Manage grade book'), $nav['due_dates_url'], $nav['active'] === 'due-dates' || $nav['active'] === 'manage');
        }
        if ( $nav['analytics_url'] !== '' ) {
            $this->renderHrefTab('analytics-tab', __('Analytics'), $nav['analytics_url'], false);
        }
        if ( $nav['resend_url'] !== '' ) {
            $this->renderHrefTab('resend-tab', __('Resend grades'), $nav['resend_url'], $nav['active'] === 'resend');
        }
        if ( $this->isInstructor() && $this->plusManageState(ReqScope::currentContextId())['plus'] ) {
            $this->renderHrefTab(
                'gradebook-log-tab',
                __('Grade book log'),
                $this->toolHome(self::ROUTE).'/log',
                $nav['active'] === 'log'
            );
        }
        echo "</ul>\n";
    }

    private function renderHrefTab($id, $label, $href, $active)
    {
        echo '<li'.($active ? ' class="active"' : '').' role="presentation">';
        echo '<a href="'.htmlspecialchars($href, ENT_QUOTES, 'UTF-8').'" id="'.htmlspecialchars($id, ENT_QUOTES, 'UTF-8').'">';
        echo htmlspecialchars($label).'</a></li>';
    }

    /**
     * Manage due dates and Student Progress, for an instructor on a lessons or manifest course.
     *
     * @return array{due_dates_url:string,progress_url:string}
     */
    /**
     * The grade book analytics tab is for an LMS launch of this tool.
     * A course login does not have the launch post, so the tab stays hidden.
     */
    private function isLtiLaunchSession()
    {
        $postKey = defined('TSUGI_SESSION_LTI_POST') ? TSUGI_SESSION_LTI_POST : 'lti_post';
        return ! empty($_SESSION[$postKey]);
    }

    /**
     * @param string $home
     */
    private function gradeAnalyticsUrl($home)
    {
        if ( ! $this->isLtiLaunchSession() ) {
            return '';
        }
        if ( ! $this->isInstructor() && ! $this->isAdmin() ) {
            return '';
        }
        return $home.'/analytics';
    }

    private function lessonGradeUrls()
    {
        $urls = array('due_dates_url' => '', 'progress_url' => '');
        if ( ! $this->isInstructor() ) {
            return $urls;
        }
        $home = $this->toolHome(self::ROUTE);
        $urls['due_dates_url'] = $home.'/manage';
        if ( Manifest::hasCurrent() ) {
            $urls['progress_url'] = $home.'/student-progress';
        }
        return $urls;
    }

    /**
     * Same grade book tabs, for the due-date and student-progress pages.
     *
     * @param string $active due-dates, progress, or resend
     * @param int|null $resend_user_id
     */
    private function renderGradeBookChrome($active, $resend_user_id = null)
    {
        $home = $this->toolHome(self::ROUTE);
        $lesson_urls = $this->lessonGradeUrls();
        $user_id = $resend_user_id === null ? (int) ReqScope::loggedInUserId() : (int) $resend_user_id;
        $this->printGradeCategoryStyles();
        $this->renderGradeNav(array(
            'active' => $active,
            'outline_url' => $this->outlineTabUrl($user_id),
            'grades_href' => $this->gradeBookUserUrl('', $user_id),
            'class_url' => $home.'/class',
            'resend_url' => $this->resendUrl(
                ReqScope::currentContextId(),
                $user_id,
                $home,
                $user_id !== (int) ReqScope::loggedInUserId()
            ),
            'analytics_url' => $this->gradeAnalyticsUrl($home),
            'configure_url' => $home.'/configuration',
            'due_dates_url' => $lesson_urls['due_dates_url'],
            'progress_url' => $lesson_urls['progress_url'],
        ));
        if ( $resend_user_id !== null && (int) $resend_user_id !== (int) ReqScope::loggedInUserId() ) {
            $this->echoViewedStudent($this->getUserInfo((int) $resend_user_id));
        }
    }

    private function requireLessonGrades()
    {
        if ( ! Manifest::hasCurrent() ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons) or an active course manifest');
        }
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        LTIX::getConnection();
    }

    private function dueDatesUrl()
    {
        return $this->toolHome(self::ROUTE).'/manage-due-dates';
    }

    /**
     * Publish or unpublish one assignment. The lesson, grade book, and due-date page read the same flag.
     */
    public function publishPost(Request $request)
    {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $return = $this->safeGradeReturn(U::get($_POST, 'return', ''));
        $csrf = self::requireCsrf($return);
        if ( $csrf ) {
            return $csrf;
        }
        $context_id = (int) ReqScope::currentContextId();
        $link_key = trim((string) U::get($_POST, 'link_key', ''));
        $raw = U::get($_POST, 'published', U::get($_GET, 'published', ''));
        $want = ($raw === '1' || $raw === 1 || $raw === true);
        $posted = ($raw === '1' || $raw === 1 || $raw === true || $raw === '0' || $raw === 0 || $raw === false);
        if ( $context_id < 1 || $link_key === '' || ! $posted ) {
            U::flashError(__('Missing publish data.'));
            return new RedirectResponse($return);
        }

        $allowed = $this->publishableLinkKeys($context_id);
        if ( ! isset($allowed[$link_key]) ) {
            U::flashError(__('That assignment is not in this course.'));
            return new RedirectResponse($return);
        }
        $title = is_string($allowed[$link_key]) ? $allowed[$link_key] : '';
        if ( ! LessonPublish::apply($context_id, $link_key, $want, $title) ) {
            U::flashError(__('Could not change publish for that assignment.'));
            return new RedirectResponse($return);
        }
        U::flashSuccess($want ? __('Published.') : __('Unpublished.'));
        return new RedirectResponse($return);
    }

    /**
     * Hide or show one grade column. The same flag as Configuration.
     */
    public function hidePost(Request $request)
    {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $return = $this->safeGradeReturn(U::get($_POST, 'return', ''));
        $csrf = self::requireCsrf($return);
        if ( $csrf ) {
            return $csrf;
        }
        $context_id = (int) ReqScope::currentContextId();
        $link_key = trim((string) U::get($_POST, 'link_key', ''));
        $raw = U::get($_POST, 'hidden', '');
        $want = ($raw === '1' || $raw === 1 || $raw === true);
        $posted = ($raw === '1' || $raw === 1 || $raw === true || $raw === '0' || $raw === 0 || $raw === false);
        if ( $context_id < 1 || $link_key === '' || ! $posted ) {
            U::flashError(__('Missing hide data.'));
            return new RedirectResponse($return);
        }
        $allowed = $this->publishableLinkKeys($context_id);
        if ( ! isset($allowed[$link_key]) ) {
            U::flashError(__('That assignment is not in this course.'));
            return new RedirectResponse($return);
        }
        $link_id = $this->linkIdForKey($context_id, $link_key);
        if ( $link_id < 1 ) {
            U::flashError(__('That assignment has no link yet.'));
            return new RedirectResponse($return);
        }
        $error = GradeCategoryRepository::setLinkHidden($context_id, $link_id, $want);
        if ( $error !== null ) {
            U::flashError($error);
            return new RedirectResponse($return);
        }
        U::flashSuccess($want ? __('Hidden from the grade book.') : __('Shown in the grade book.'));
        return new RedirectResponse($return);
    }

    /**
     * Stay on a grade book page. Anything else returns to the grade list.
     *
     * @param mixed $return
     */
    private function safeGradeReturn($return)
    {
        $home = $this->toolHome(self::ROUTE);
        if ( ! is_string($return) || ! str_starts_with($return, $home) ) {
            return $home;
        }
        $extra = substr($return, strlen($home));
        $path = $extra;
        $query = '';
        $split = strpos($extra, '?');
        if ( $split !== false ) {
            $path = substr($extra, 0, $split);
            $query = substr($extra, $split + 1);
        }
        if ( $path === '/' ) {
            $path = '';
        }
        $paths = array('', '/outline', '/class', '/manage', '/manage-due-dates');
        if ( ! in_array($path, $paths, true) ) {
            return $home;
        }
        if ( $query !== '' ) {
            $params = array();
            parse_str($query, $params);
            foreach ( $params as $key => $value ) {
                if ( ! in_array($key, array('user_id', 'link_id'), true) || ! preg_match('/^\d+$/', (string) $value) ) {
                    return $home;
                }
            }
        }
        return $return;
    }

    /**
     * Link keys an instructor may publish from this course, with a title when the lesson has one.
     *
     * @param int $context_id
     * @return array<string,string>
     */
    private function publishableLinkKeys($context_id)
    {
        $allowed = array();
        if ( Manifest::hasCurrent() ) {
            $lessons = Manifest::currentLessons();
            if ( $lessons ) {
                foreach ( $this->dueDateItems($lessons, $this->manifestCourse()) as $it ) {
                    $key = trim((string) ($it['resource_link_id'] ?? ''));
                    if ( $key === '' ) {
                        continue;
                    }
                    $allowed[$key] = isset($it['item_title']) ? (string) $it['item_title'] : '';
                }
            }
        }
        foreach ( $this->dueDateLinkFlags($context_id) as $key => $flags ) {
            if ( ! isset($allowed[$key]) ) {
                $allowed[$key] = '';
            }
        }
        return $allowed;
    }

    /**
     * @param array<string,mixed> $item
     * @param array<string,array{published:bool,hidden:bool}> $linkFlags
     * @param array<string,bool> $contentPublished
     */
    private function assignmentIsPublished($item, $linkFlags, $contentPublished)
    {
        $rlid = trim((string) ($item['resource_link_id'] ?? ''));
        $link = ($rlid !== '' && isset($linkFlags[$rlid])) ? $linkFlags[$rlid] : null;
        if ( $rlid !== '' && array_key_exists($rlid, $contentPublished) ) {
            return (bool) $contentPublished[$rlid];
        }
        if ( ! empty($item['quiz_id']) ) {
            return $link !== null && ! empty($link['published']);
        }
        if ( $link !== null ) {
            return ! empty($link['published']);
        }
        return true;
    }

    /**
     * Instructor control on a grade or outline row.
     *
     * @param array<int,array<string,mixed>> $rows
     * @param string $returnUrl
     */
    private function addPublishColumn(&$rows, $returnUrl)
    {
        if ( ! $this->isInstructor() || ! $this->manifestCourse() ) {
            return;
        }
        foreach ( $rows as &$row ) {
            $key = trim((string) U::get($row, '_link_key', ''));
            if ( $key === '' ) {
                continue;
            }
            $on = isset($row['_link_published']) && (int) $row['_link_published'] === 1;
            $row['published'] = $on ? __('Published') : __('Unpublished');
            $row['_html_published'] = $this->stateIconsHtml($key, $on, false, $returnUrl);
            $row['_header_published'] = '<span class="sr-only">'.htmlspecialchars(__('Published and hidden')).'</span>';
            $row = $this->orderGradeBookColumns($row);
        }
        unset($row);
    }

    /**
     * @param string $linkKey
     * @param bool $published
     * @param string $returnUrl
     */
    /**
     * @param string $linkKey
     * @param bool $published
     * @param bool $hidden
     * @param string $returnUrl
     */
    private function stateIconsHtml($linkKey, $published, $hidden, $returnUrl)
    {
        return '<div class="grade-state-icons">'
            .$this->publishButtonHtml($linkKey, $published, $returnUrl)
            .$this->hiddenButtonHtml($linkKey, $hidden, $returnUrl)
            .'</div>';
    }

    /**
     * @param string $linkKey
     * @param bool $published
     * @param string $returnUrl
     */
    private function publishButtonHtml($linkKey, $published, $returnUrl)
    {
        if ( $published ) {
            $label = __('Published. Click to unpublish.');
            $icon = 'fa-check-circle publish-on';
            $next = '0';
        } else {
            $label = __('Unpublished. Click to publish.');
            $icon = 'fa-ban publish-off';
            $next = '1';
        }
        return $this->stateIconForm('/publish', $linkKey, 'published', $next, $icon, $label, $returnUrl);
    }

    /**
     * @param string $linkKey
     * @param bool $hidden
     * @param string $returnUrl
     */
    private function hiddenButtonHtml($linkKey, $hidden, $returnUrl)
    {
        if ( $hidden ) {
            $label = __('Hidden. Click to show in the grade book.');
            $icon = 'fa-eye-slash hidden-on';
            $next = '0';
        } else {
            $label = __('Shown in the grade book. Click to hide.');
            $icon = 'fa-eye hidden-off';
            $next = '1';
        }
        return $this->stateIconForm('/hide', $linkKey, 'hidden', $next, $icon, $label, $returnUrl);
    }

    /**
     * An unprovisioned tool has no publish switch yet. Show the marks it will have
     * once it is connected: unpublished, and the grade column visible unless hidden.
     *
     * @param bool $hidden
     */
    private function unprovisionedStateIcons($hidden)
    {
        $publish = __('Unpublished until this tool is provisioned.');
        if ( $hidden ) {
            $hiddenLabel = __('Hidden from the grade book.');
            $hiddenClass = 'hidden-on';
            $hiddenIcon = 'fa-eye-slash';
        } else {
            $hiddenLabel = __('Shown in the grade book once this tool is published.');
            $hiddenClass = 'hidden-off';
            $hiddenIcon = 'fa-eye';
        }
        return $this->stateMarkHtml('fa-ban', 'publish-off', $publish)
            .$this->stateMarkHtml($hiddenIcon, $hiddenClass, $hiddenLabel);
    }

    /**
     * @param string $icon
     * @param string $stateClass
     * @param string $label
     */
    private function stateMarkHtml($icon, $stateClass, $label)
    {
        $labelAttr = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        return '<span class="grade-state-icon '.$stateClass.'" title="'.$labelAttr.'" aria-label="'.$labelAttr.'">'
            .'<i class="fa '.htmlspecialchars($icon, ENT_QUOTES, 'UTF-8').'" aria-hidden="true"></i></span>';
    }

    /**
     * Lesson module is hidden. The grade column may not exist yet, so this is not a button.
     */
    private function hiddenStatusIcon()
    {
        $label = htmlspecialchars(__('Hidden in the lesson'), ENT_QUOTES, 'UTF-8');
        return '<span class="grade-state-icon" title="'.$label.'"><i class="fa fa-eye-slash hidden-on" aria-hidden="true"></i></span>';
    }

    /**
     * @param string $path
     * @param string $linkKey
     * @param string $field
     * @param string $next
     * @param string $iconClass
     * @param string $label
     * @param string $returnUrl
     */
    private function stateIconForm($path, $linkKey, $field, $next, $iconClass, $label, $returnUrl)
    {
        $action = htmlspecialchars($this->toolHome(self::ROUTE).$path, ENT_QUOTES, 'UTF-8');
        $labelAttr = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        return '<form method="post" action="'.$action.'">'
            .self::csrfField()
            .'<input type="hidden" name="return" value="'.htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8').'">'
            .'<input type="hidden" name="link_key" value="'.htmlspecialchars($linkKey, ENT_QUOTES, 'UTF-8').'">'
            .'<input type="hidden" name="'.$field.'" value="'.$next.'">'
            .'<button type="submit" class="btn btn-default btn-icon grade-state-btn" title="'.$labelAttr.'" aria-label="'.$labelAttr.'">'
            .'<i class="fa '.$iconClass.'" aria-hidden="true"></i></button></form>';
    }

    private function printGradeStateIconStyles()
    {
        echo '<style>
.grade-state-icons form { display: inline-block; margin: 0 4px 0 0; }
.grade-state-btn.btn { padding: 4px 8px; min-width: 32px; line-height: 1; }
.grade-state-btn .publish-on,
.grade-state-btn .hidden-off,
.grade-state-icon.publish-on,
.grade-state-icon.hidden-off { color: #0b874b; }
.grade-state-btn .publish-off,
.grade-state-btn .hidden-on,
.grade-state-icon { color: #8d959f; }
.grade-state-icon { display: inline-block; padding: 4px 8px; min-width: 32px; line-height: 1; text-align: center; }
.grade-override { display: inline-block; }
.grade-override > .btn-link { padding: 0; }
.grade-override-menu { padding: 8px 10px; min-width: 11em; }
.grade-override-menu form { margin: 0; }
.grade-override-current { margin: 0 0 6px; }
.grade-override-field { font-weight: normal; margin: 0 0 6px; display: block; }
.grade-override-points { width: 5em; display: inline-block; }
</style>'."\n";
    }

    /**
     * @param string $active due-dates, progress, or resend
     * @param int|null $resend_user_id Whose upward grades the Resend tab covers
     */
    private function beginLessonGradePage($active, $resend_user_id = null)
    {
        global $OUTPUT;
        $context_title = $this->getContextTitle(ReqScope::currentContextId());
        $this->lmsRecordLaunchAnalytics(self::ROUTE, 'Grade Book');
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav(false);
        $OUTPUT->flashMessages();
        \Tsugi\Controllers\Lessons::printLtiProgressStyles();
        $this->printGradeStateIconStyles();
        echo '<main class="container" id="main-content">'."\n";
        echo '<h1>'.htmlspecialchars(__('Grade Book'))."</h1>\n";
        echo '<p>Class: '.htmlspecialchars($context_title)."</p>\n";
        $this->renderGradeBookChrome($active, $resend_user_id);
    }

    private function endLessonGradePage()
    {
        echo "</main>\n";
        $this->finishGradePage();
    }

    private function invalidateDueDatesSessionCache()
    {
        $context_id = ReqScope::currentContextId();
        if ( $context_id ) {
            GradeUtil::invalidateDueDatesCache((int) $context_id);
        }
    }

    /**
     * Value for HTML date input (YYYY-MM-DD) from a stored MySQL datetime.
     */
    private static function toDateInputValue($mysql)
    {
        return GradeUtil::dueDateInputValue($mysql);
    }

    /**
     * Parse a due date from the UI (calendar day only). Stored as 11:59:59 PM that day.
     *
     * @return string|null MySQL datetime or null to clear
     */
    private static function parseDueDateEndOfDay($s)
    {
        return GradeUtil::parseDueDateEndOfDay($s);
    }

    /**
     * One user's visible grade rows. Callers add ORDER BY or paging.
     *
     * @param string $p
     */
    private function visibleGradeSql($p)
    {
        return "SELECT R.result_id AS result_id, L.title as title, L.link_key AS resource_link_id,
                L.link_id AS link_id, L.end_datetime AS due_date, L.score_maximum AS score_maximum,
                R.grade AS grade, R.score_given AS score_given, R.result_maximum AS result_maximum,
                R.comment AS comment,
                R.updated_at as updated_at, server_grade, retrieved_at, R.server_status AS server_status,
                sourcedid, result_url, service_key as service,
                L.lti13_lineitem AS lti13_lineitem, U.subject_key AS subject_key,
                CTX.plus_course AS plus_course,
                TIMESTAMPDIFF(SECOND,retrieved_at,NOW()) as diff_in_seconds, NOW() AS time_now,
                L.link_key AS _link_key, L.published AS _link_published,
                C.category_id AS _category_id, C.position AS _position
            FROM {$p}lti_result AS R
            JOIN {$p}lti_link as L ON R.link_id = L.link_id
            JOIN {$p}lti_context AS CTX ON CTX.context_id = L.context_id
            JOIN {$p}lti_user AS U ON R.user_id = U.user_id
            LEFT JOIN {$p}lti_service AS S ON R.service_id = S.service_id
            LEFT JOIN {$p}lti_category AS C
                ON C.category_id = L.category_id AND C.context_id = L.context_id
            WHERE R.user_id = :UID AND L.context_id = :CID AND R.grade IS NOT NULL
              AND L.hidden = 0";
    }

    /**
     * Grade book URL for this user. Another student's id stays on the query string.
     *
     * @param string $path
     * @param int $user_id
     */
    private function gradeBookUserUrl($path, $user_id)
    {
        $url = $this->toolHome(self::ROUTE).$path;
        if ( (int) $user_id > 0 && (int) $user_id !== (int) ReqScope::loggedInUserId() ) {
            $url .= '?user_id='.(int) $user_id;
        }
        return $url;
    }

    /**
     * Course Outline tab URL, or empty when this course has no lessons.
     *
     * @param int $user_id
     */
    private function outlineTabUrl($user_id)
    {
        if ( ! Manifest::hasCurrent() ) {
            return '';
        }
        return $this->gradeBookUserUrl('/outline', $user_id);
    }

    /**
     * @param array<int,array{title:string,href:string,rows:array<int,array<string,mixed>>}> $outline
     */
    private function renderCourseOutline($outline)
    {
        if ( count($outline) < 1 ) {
            echo '<p>'.htmlspecialchars(__('No modules with active links.'))."</p>\n";
            return;
        }
        foreach ( $outline as $module ) {
            $title = htmlspecialchars($module['title']);
            $href = isset($module['href']) ? (string) $module['href'] : '';
            echo '<h2 class="grade-category-heading">';
            if ( $href !== '' ) {
                echo '<a href="'.htmlspecialchars($href, ENT_QUOTES, 'UTF-8').'">'.$title.'</a>';
            } else {
                echo $title;
            }
            echo '</h2>';
            $this->renderGradeTable($module['rows']);
        }
    }

    /**
     * Modules from the course manifest or file-based lessons, in document order.
     * Null when this course has neither. A module is included only when it has
     * a visible lti_link for one of its resource links.
     *
     * @param array<int,array<string,mixed>> $grade_rows
     * @param string $publish_return Where an outline publish button comes back
     * @return array<int,array{title:string,href:string,rows:array<int,array<string,mixed>>}>|null
     */
    private function courseOutlineModules($context_id, $grade_rows, $publish_return = '')
    {
        if ( ! Manifest::hasCurrent() ) {
            return null;
        }
        $lessons = Manifest::currentLessons();
        if ( ! $lessons || ! isset($lessons->lessons->modules) || ! is_array($lessons->lessons->modules) ) {
            return null;
        }

        $grades_by_key = array();
        foreach ( $grade_rows as $row ) {
            $key = trim((string) U::get($row, '_link_key', ''));
            if ( $key === '' || isset($grades_by_key[$key]) ) {
                continue;
            }
            $grades_by_key[$key] = $row;
        }

        $links = $this->visibleLinksByKey($context_id);
        $columns = $this->gradeViewColumns($grade_rows);
        $used = array();
        $groups = array();
        foreach ( $lessons->lessons->modules as $module ) {
            if ( isset($module->hidden) && $module->hidden ) {
                continue;
            }
            $rows = array();
            foreach ( $this->moduleResourceLinkIds($module) as $resource_link_id ) {
                if ( isset($used[$resource_link_id]) || ! isset($links[$resource_link_id]) ) {
                    continue;
                }
                $used[$resource_link_id] = true;
                $grade_row = isset($grades_by_key[$resource_link_id]) ? $grades_by_key[$resource_link_id] : null;
                $rows[] = $this->outlineRow($links[$resource_link_id], $grade_row, $columns, $publish_return);
            }
            if ( count($rows) < 1 ) {
                continue;
            }
            $title = isset($module->title) ? trim((string) $module->title) : '';
            if ( $title === '' ) {
                $title = __('Module');
            }
            $anchor = isset($module->anchor) ? trim((string) $module->anchor) : '';
            $groups[] = array(
                'title' => $title,
                'href' => $this->moduleHref($anchor),
                'rows' => $rows,
            );
        }
        return $groups;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function visibleLinksByKey($context_id)
    {
        global $CFG, $PDOX;
        LTIX::getConnection();
        $rows = $PDOX->allRowsDie(
            "SELECT link_id, link_key, title, end_datetime, published
             FROM {$CFG->dbprefix}lti_link
             WHERE context_id = :CID AND deleted = 0 AND hidden = 0",
            array(':CID' => (int) $context_id)
        );
        $links = array();
        foreach ( $rows as $row ) {
            $key = trim((string) $row['link_key']);
            if ( $key === '' || isset($links[$key]) ) {
                continue;
            }
            $links[$key] = $row;
        }
        return $links;
    }

    /**
     * Course lessons URL for a module anchor. Empty when the module has no anchor.
     */
    private function moduleHref($anchor)
    {
        $anchor = trim((string) $anchor);
        if ( $anchor === '' ) {
            return '';
        }
        $parent = U::get_rest_parent();
        if ( str_ends_with($parent, self::ROUTE) ) {
            $parent = substr($parent, 0, -strlen(self::ROUTE));
        }
        return $parent . Lessons::ROUTE . '/' . urlencode($anchor);
    }

    /**
     * Point a grade or outline title at the lessons launch for that resource link.
     *
     * @param array<int,array<string,mixed>> $rows
     */
    private function linkLaunchableTitles(&$rows)
    {
        foreach ( $rows as &$row ) {
            $this->linkLaunchableTitle($row, U::get($row, '_link_key', ''));
        }
        unset($row);
    }

    /**
     * @param array<string,mixed> $row
     * @param mixed $link_key
     */
    private function linkLaunchableTitle(&$row, $link_key)
    {
        $key = trim((string) $link_key);
        if ( $key === '' ) {
            return;
        }
        $hrefs = $this->lessonLaunchHrefs();
        if ( ! isset($hrefs[$key]) ) {
            return;
        }
        $launch = $hrefs[$key];
        if ( empty($launch['students']) && ! $this->isInstructor() ) {
            return;
        }
        $title = (string) U::get($row, 'title', '');
        $attrs = ! empty($launch['blank']) ? ' target="_blank" rel="noopener noreferrer"' : '';
        $row['_html_title'] = '<a href="'.htmlspecialchars($launch['href'], ENT_QUOTES, 'UTF-8').'"'.$attrs.'>'
            .htmlspecialchars($title).'</a>';
    }

    /**
     * Resource link id to the lessons launch, for items lessons itself links.
     * blank is true when lessons opens that launch in a new tab.
     * students is false when a learner may not launch it.
     *
     * @return array<string,array{href:string,blank:bool,students:bool}>
     */
    private function lessonLaunchHrefs()
    {
        if ( $this->lessonLaunchHrefCache !== null ) {
            return $this->lessonLaunchHrefCache;
        }
        $hrefs = array();
        $this->lessonLaunchHrefCache = $hrefs;
        if ( ! Manifest::hasCurrent() ) {
            return $hrefs;
        }
        $service = Manifest::currentLessons();
        if ( ! $service || ! isset($service->lessons->modules) || ! is_array($service->lessons->modules) ) {
            return $hrefs;
        }
        $manifest_course = Manifest::resolvedId() > 0;
        foreach ( $service->lessons->modules as $module ) {
            if ( isset($module->hidden) && $module->hidden ) {
                continue;
            }
            $anchor = isset($module->anchor) ? trim((string) $module->anchor) : '';
            foreach ( $this->moduleLaunchItems($module) as $item ) {
                $this->rememberLaunchHref($hrefs, $service, $item, $manifest_course, $anchor);
            }
        }
        if ( isset($service->lessons->launches) ) {
            foreach ( $service->getLaunches() as $launch ) {
                $this->rememberLaunchHref($hrefs, $service, $launch, $manifest_course);
            }
        }
        $this->lessonLaunchHrefCache = $hrefs;
        return $hrefs;
    }

    /**
     * @param array<string,array{href:string,blank:bool,students:bool}> $hrefs
     * @param object $item
     * @param string $anchor Lesson module anchor, so a quiz can return there
     */
    private function rememberLaunchHref(&$hrefs, LessonsService $service, $item, $manifest_course, $anchor = '')
    {
        if ( ! is_object($item) ) {
            return;
        }
        if ( $manifest_course && LessonsNormalize::isAssignmentLti($item) ) {
            $deployment = isset($item->tool_deployment_id) ? (int) $item->tool_deployment_id : 0;
            $content_id = isset($item->content_id) ? (int) $item->content_id : 0;
            if ( $deployment < 1 && $content_id < 1 ) {
                return;
            }
        }
        $resource_link_id = $this->itemResourceLinkId($item);
        if ( $resource_link_id === '' || isset($hrefs[$resource_link_id]) ) {
            return;
        }
        $href = $this->lessonItemHref($service, $item, $resource_link_id, $anchor);
        if ( $href === '' ) {
            return;
        }
        $hrefs[$resource_link_id] = array(
            'href' => $href,
            'blank' => $this->lessonItemOpensNewWindow($item),
            'students' => $this->lessonStudentsMayLaunch($service, $item, $resource_link_id),
        );
    }

    /**
     * Students do not launch a hidden link or an unpublished tool.
     * A discussion stays available, matching lessons. No link row stays available.
     *
     * @param object $item
     */
    private function lessonStudentsMayLaunch(LessonsService $service, $item, $resource_link_id)
    {
        if ( $this->linkRowHidden($resource_link_id) ) {
            return false;
        }
        if ( LessonsNormalize::isDiscussion($item) ) {
            return true;
        }
        $content_id = isset($item->content_id) ? (int) $item->content_id : 0;
        if ( $content_id > 0 ) {
            return LtiContentService::studentVisible(ReqScope::currentContextId(), $content_id) === true;
        }
        return $service->resourceLinkPublished($resource_link_id) !== false;
    }

    /**
     * @param mixed $link_key
     */
    private function linkRowHidden($link_key)
    {
        $key = trim((string) $link_key);
        if ( $key === '' ) {
            return false;
        }
        if ( $this->linkHiddenCache === null ) {
            $this->linkHiddenCache = array();
            $context_id = ReqScope::currentContextIdLegacy();
            if ( $context_id > 0 ) {
                global $CFG, $PDOX;
                LTIX::getConnection();
                $rows = $PDOX->allRowsDie(
                    "SELECT link_key, hidden FROM {$CFG->dbprefix}lti_link WHERE context_id = :CID",
                    array(':CID' => $context_id)
                );
                if ( is_array($rows) ) {
                    foreach ( $rows as $row ) {
                        $row_key = trim((string) $row['link_key']);
                        if ( $row_key === '' ) {
                            continue;
                        }
                        $this->linkHiddenCache[$row_key] = ((int) $row['hidden']) === 1;
                    }
                }
            }
        }
        return ! empty($this->linkHiddenCache[$key]);
    }

    /**
     * Same window unless this launch should open in a new tab.
     * New tab is target _blank, blank, window, or modal. A content row stored
     * as iframe is the modal case. Inline embed stays in this window.
     *
     * @param object $item
     */
    private function lessonItemOpensNewWindow($item)
    {
        if ( LessonsNormalize::isNativeQuiz($item) ) {
            return false;
        }
        $content_id = isset($item->content_id) ? (int) $item->content_id : 0;
        if ( $content_id > 0 ) {
            $row = LtiContentService::find(ReqScope::currentContextId(), $content_id);
            $stored = is_array($row) && isset($row['target']) ? strtolower(trim((string) $row['target'])) : '';
            return $stored !== 'inline';
        }
        if ( LessonsNormalize::isDiscussion($item) ) {
            return false;
        }
        $target = isset($item->target) ? strtolower(trim((string) $item->target)) : '';
        return $target === '_blank' || $target === 'blank' || $target === 'window' || $target === 'modal';
    }

    /**
     * @param object $item
     */
    private function lessonItemHref(LessonsService $service, $item, $resource_link_id, $anchor = '')
    {
        if ( LessonsNormalize::isNativeQuiz($item) ) {
            return $this->quizLaunchHref($item, $anchor);
        }
        if ( LessonsNormalize::isDiscussion($item) ) {
            global $CFG;
            $use_launch = is_object($CFG) && method_exists($CFG, 'getExtension')
                ? (bool) $CFG->getExtension('discussion_lti_launch', false)
                : false;
            if ( ! $use_launch ) {
                $home = self::determineToolHome(Discussions::ROUTE);
                if ( is_string($home) && $home !== '' ) {
                    return $home.'/'.rawurlencode($resource_link_id);
                }
            }
        }
        return $service->lessonsLaunchPath($resource_link_id);
    }

    /**
     * Same take link lessons uses for a published Quiz1 item.
     *
     * @param object $item
     */
    private function quizLaunchHref($item, $anchor)
    {
        $quiz = $this->publishedQuizLink(LessonsNormalize::quizIdOf($item));
        if ( $quiz === null ) {
            return '';
        }
        $home = self::determineToolHome(Quiz1::ROUTE);
        if ( ! is_string($home) || $home === '' ) {
            return '';
        }
        $href = U::addSession(self::joinToolHome($home, 'link/'.(string) $quiz['link_id']));
        $anchor = trim((string) $anchor);
        if ( $anchor !== '' && preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,80}$/', $anchor) ) {
            $href .= (str_contains($href, '?') ? '&' : '?').'from='.rawurlencode($anchor);
        }
        return $href;
    }

    /**
     * Published Quiz1 link for this course. Hidden and unpublished quizzes are omitted.
     *
     * @return array{link_key:string,link_id:int}|null
     */
    private function publishedQuizLink($quiz_id)
    {
        $quiz_id = (int) $quiz_id;
        if ( $this->publishedQuizById === null ) {
            $this->publishedQuizById = array();
            $context_id = ReqScope::currentContextId();
            if ( $context_id < 1 ) {
                $context_id = ReqScope::currentContextIdLegacy();
            }
            if ( $context_id > 0 ) {
                global $CFG, $PDOX;
                LTIX::getConnection();
                $rows = $PDOX->allRowsDie(
                    "SELECT Q.quiz_id AS quiz_id, L.link_id AS link_id, L.link_key AS link_key
                     FROM {$CFG->dbprefix}quiz1_quiz AS Q
                     JOIN {$CFG->dbprefix}lti_link AS L ON L.link_id = Q.link_id
                     WHERE Q.context_id = :CID AND L.context_id = :CID
                       AND L.deleted = 0 AND L.hidden = 0 AND L.published = 1",
                    array(':CID' => $context_id)
                );
                if ( is_array($rows) ) {
                    foreach ( $rows as $row ) {
                        $id = (int) $row['quiz_id'];
                        $key = trim((string) $row['link_key']);
                        $link_id = (int) $row['link_id'];
                        if ( $id < 1 || $link_id < 1 || $key === '' ) {
                            continue;
                        }
                        $this->publishedQuizById[$id] = array(
                            'link_key' => $key,
                            'link_id' => $link_id,
                        );
                    }
                }
            }
        }
        if ( $quiz_id < 1 || ! isset($this->publishedQuizById[$quiz_id]) ) {
            return null;
        }
        return $this->publishedQuizById[$quiz_id];
    }

    /**
     * @return object[]
     */
    private function moduleLaunchItems($module)
    {
        $items = array();
        if ( isset($module->items) && is_array($module->items) ) {
            foreach ( $module->items as $item ) {
                $items[] = is_array($item) ? (object) $item : $item;
            }
            return $items;
        }
        foreach ( array('lti', 'discussions') as $field ) {
            if ( ! isset($module->$field) ) {
                continue;
            }
            $list = $module->$field;
            if ( ! is_array($list) ) {
                $list = array($list);
            }
            foreach ( $list as $item ) {
                $items[] = is_array($item) ? (object) $item : $item;
            }
        }
        return $items;
    }

    /**
     * Resource link ids in outline order. Items take precedence over legacy lists.
     *
     * @return string[]
     */
    private function moduleResourceLinkIds($module)
    {
        $ids = array();
        if ( isset($module->items) && is_array($module->items) ) {
            foreach ( $module->items as $item ) {
                $resource_link_id = $this->itemResourceLinkId($item);
                if ( $resource_link_id !== '' ) {
                    $ids[] = $resource_link_id;
                }
            }
            return $ids;
        }
        foreach ( array('lti', 'discussions') as $field ) {
            if ( ! isset($module->$field) ) {
                continue;
            }
            $list = $module->$field;
            if ( ! is_array($list) ) {
                $list = array($list);
            }
            foreach ( $list as $item ) {
                $resource_link_id = $this->itemResourceLinkId($item);
                if ( $resource_link_id !== '' ) {
                    $ids[] = $resource_link_id;
                }
            }
        }
        return $ids;
    }

    /**
     * @param object|array<string,mixed> $item
     */
    private function itemResourceLinkId($item)
    {
        if ( is_array($item) ) {
            $item = (object) $item;
        }
        if ( ! is_object($item) || (isset($item->hidden) && $item->hidden) ) {
            return '';
        }
        if ( LessonsNormalize::isNativeQuiz($item) ) {
            $quiz = $this->publishedQuizLink(LessonsNormalize::quizIdOf($item));
            return $quiz === null ? '' : $quiz['link_key'];
        }
        $content_id = isset($item->content_id) ? (int) $item->content_id : 0;
        if ( $content_id > 0 ) {
            $row = LtiContentService::find(ReqScope::currentContextId(), $content_id);
            if ( is_array($row) && isset($row['resource_link_id']) ) {
                return trim((string) $row['resource_link_id']);
            }
        }
        if ( LessonsNormalize::isLtiLaunch($item) && isset($item->resource_link_id) ) {
            if ( $this->manifestCourse() && LessonsNormalize::isAssignmentLti($item) ) {
                $deployment = isset($item->tool_deployment_id) ? (int) $item->tool_deployment_id : 0;
                if ( $deployment < 1 && $content_id < 1 ) {
                    return '';
                }
            }
            return trim((string) $item->resource_link_id);
        }
        return '';
    }

    /**
     * Visible grade-list columns, in grade-list order. Server columns appear
     * when any result on that list has them.
     *
     * @param array<int,array<string,mixed>> $grade_rows
     * @return string[]
     */
    private function gradeViewColumns($grade_rows)
    {
        $columns = $this->gradeTableColumns($grade_rows);
        if ( count($columns) < 1 ) {
            $columns = array('title', 'code', 'due_date', 'grade', 'comment', 'updated_at');
        }
        if ( $this->isInstructor() && $this->manifestCourse() && ! in_array('published', $columns, true) ) {
            $menu_at = array_search('menu', $columns, true);
            if ( $menu_at === false ) {
                $columns[] = 'published';
            } else {
                array_splice($columns, $menu_at, 0, array('published'));
            }
        }
        if ( ! in_array('menu', $columns, true) ) {
            $columns[] = 'menu';
        }
        return $columns;
    }

    /**
     * @param array<string,mixed> $link
     * @param array<string,mixed>|null $grade_row
     * @param string[] $columns
     * @param string $publish_return
     * @return array<string,mixed>
     */
    private function outlineRow($link, $grade_row, $columns, $publish_return = '')
    {
        $row = array();
        foreach ( $columns as $key ) {
            if ( is_array($grade_row) && array_key_exists($key, $grade_row) ) {
                $row[$key] = $grade_row[$key];
                $html_key = '_html_'.$key;
                if ( isset($grade_row[$html_key]) && $grade_row[$html_key] !== '' ) {
                    $row[$html_key] = $grade_row[$html_key];
                }
                continue;
            }
            if ( $key === 'title' ) {
                $title = trim((string) $link['title']);
                $row[$key] = $title === '' ? (string) $link['link_key'] : $title;
                $this->linkLaunchableTitle($row, $link['link_key']);
                continue;
            }
            if ( $key === 'due_date' ) {
                $row[$key] = $this->dueDateLabel(isset($link['end_datetime']) ? $link['end_datetime'] : null);
                continue;
            }
            if ( $key === 'published' ) {
                $on = isset($link['published']) && (int) $link['published'] === 1;
                $row[$key] = $on ? __('Published') : __('Unpublished');
                $row['_html_published'] = $this->stateIconsHtml((string) $link['link_key'], $on, false, $publish_return);
                $row['_header_published'] = '<span class="sr-only">'.htmlspecialchars(__('Published and hidden')).'</span>';
                continue;
            }
            if ( $key === 'menu' ) {
                $blank = array(
                    'grade' => null,
                    'due_date' => isset($link['end_datetime']) ? $link['end_datetime'] : null,
                );
                $this->attachGradeMenu($blank);
                $row['menu'] = '';
                $row['_html_menu'] = $blank['_html_menu'];
                $row['_header_menu'] = $blank['_header_menu'];
                continue;
            }
            $row[$key] = '';
        }
        return $row;
    }

    /**
     * @param array<int,array<string,mixed>> $categories
     * @param array<int,array<string,mixed>> $rows
     * @param int $context_id
     * @param int $user_id
     */
    private function renderGroupedGrades($categories, $rows, $context_id, $user_id)
    {
        $by_category = array();
        foreach ( $rows as $row ) {
            $category_id = (int) U::get($row, '_category_id', 0);
            if ( ! isset($by_category[$category_id]) ) {
                $by_category[$category_id] = array();
            }
            $by_category[$category_id][] = $row;
        }

        $work = $this->categoryWork($context_id, $user_id);
        $included = $this->overallFromWork($categories, $work, 'include');
        $after_due = $this->overallFromWork($categories, $work, 'due');
        $this->echoOverallGrade(__('Overall, incomplete included'), $included['overall']);
        if ( ! $this->sameOverall($included['overall'], $after_due['overall']) ) {
            $this->echoOverallGrade(__('Overall, zero after due date'), $after_due['overall']);
        }

        $shown = 0;
        foreach ( $categories as $category ) {
            $category_id = (int) $category['category_id'];
            $group = isset($by_category[$category_id]) ? $by_category[$category_id] : array();
            $has_work = isset($work[$category_id]) && count($work[$category_id]) > 0;
            if ( count($group) < 1 && ! $has_work ) {
                continue;
            }
            $shown += count($group);
            if ( $has_work ) {
                $shown++;
            }
            echo $this->categoryHeading(
                (string) $category['title'],
                (int) $category['grade_percent'],
                (int) $category['extra_credit'] === 1,
                $included['averages'][$category_id],
                $after_due['averages'][$category_id]
            );
            if ( count($group) > 0 ) {
                $this->renderGradeTable($group);
            } else {
                echo '<p>'.htmlspecialchars(__('No grades yet.'))."</p>\n";
            }
        }

        $uncategorized = isset($by_category[0]) ? $by_category[0] : array();
        if ( count($uncategorized) > 0 ) {
            $shown += count($uncategorized);
            echo '<h2 class="grade-category-heading">'.htmlspecialchars(__('Uncategorized')).'</h2>';
            $this->renderGradeTable($uncategorized);
        }

        if ( $shown < 1 ) {
            echo '<p>'.htmlspecialchars(__('Nothing to display.'))."</p>\n";
        }
    }

    private function echoOverallGrade($label, $overall)
    {
        if ( $overall === null ) {
            return;
        }
        echo '<p class="grade-overall"><strong>'.htmlspecialchars($label).'</strong> '
            .htmlspecialchars(sprintf('%1.1f', $overall))."%</p>\n";
    }

    /**
     * True when both overalls are missing or they display as the same percent.
     *
     * @param float|null $left
     * @param float|null $right
     */
    private function sameOverall($left, $right)
    {
        if ( $left === null || $right === null ) {
            return $left === null && $right === null;
        }
        return sprintf('%1.1f', $left) === sprintf('%1.1f', $right);
    }

    /**
     * Visible links in a category, with this user's grade when one exists.
     * A missing grade is blank. due_passed is 1 when end_datetime is in the past.
     *
     * @param int $context_id
     * @param int $user_id
     * @return array<int,array<int,array{grade:float|null,due_passed:int}>>
     */
    private function categoryWork($context_id, $user_id)
    {
        global $CFG, $PDOX;
        if ( (int) $context_id < 1 || (int) $user_id < 1 ) {
            return array();
        }
        LTIX::getConnection();
        $p = $CFG->dbprefix;
        $rows = $PDOX->allRowsDie(
            "SELECT L.category_id AS category_id, R.grade AS grade,
                CASE WHEN L.end_datetime IS NOT NULL AND L.end_datetime <= NOW() THEN 1 ELSE 0 END AS due_passed
             FROM {$p}lti_link AS L
             LEFT JOIN {$p}lti_result AS R
                ON R.link_id = L.link_id AND R.user_id = :UID AND R.deleted = 0
             WHERE L.context_id = :CID AND L.deleted = 0 AND L.hidden = 0
               AND L.category_id IS NOT NULL",
            array(':UID' => (int) $user_id, ':CID' => (int) $context_id)
        );
        $work = array();
        foreach ( $rows as $row ) {
            $category_id = (int) $row['category_id'];
            $grade = $row['grade'];
            if ( ! isset($work[$category_id]) ) {
                $work[$category_id] = array();
            }
            $work[$category_id][] = array(
                'grade' => ($grade === null || $grade === '') ? null : (float) $grade,
                'due_passed' => (int) $row['due_passed'],
            );
        }
        return $work;
    }

    /**
     * include: a blank grade counts as zero.
     * due: a blank grade counts as zero only after its due date.
     * Regular weights are the denominator. Extra credit is added on top.
     *
     * @param array<int,array<string,mixed>> $categories
     * @param array<int,array<int,array{grade:float|null,due_passed:int}>> $work
     * @param string $mode include|due
     * @return array{overall:float|null,averages:array<int,float|null>}
     */
    private function overallFromWork($categories, $work, $mode)
    {
        $averages = array();
        $numerator = 0.0;
        $denominator = 0.0;
        foreach ( $categories as $category ) {
            $category_id = (int) $category['category_id'];
            $items = isset($work[$category_id]) ? $work[$category_id] : array();
            $average = $this->categoryWorkAverage($items, $mode);
            $averages[$category_id] = $average;
            if ( $average === null ) {
                continue;
            }
            $weight = (int) $category['grade_percent'];
            if ( $weight < 1 ) {
                continue;
            }
            $numerator += ($average / 100.0) * $weight;
            if ( (int) $category['extra_credit'] !== 1 ) {
                $denominator += $weight;
            }
        }
        $overall = null;
        if ( $denominator > 0 ) {
            $overall = ($numerator / $denominator) * 100.0;
        }
        return array('overall' => $overall, 'averages' => $averages);
    }

    /**
     * @param array<int,array{grade:float|null,due_passed:int}> $items
     * @param string $mode include|due
     */
    private function categoryWorkAverage($items, $mode)
    {
        $sum = 0.0;
        $count = 0;
        foreach ( $items as $item ) {
            if ( $item['grade'] !== null ) {
                $sum += $item['grade'] * 100.0;
                $count++;
                continue;
            }
            if ( $mode === 'include' || ($mode === 'due' && (int) $item['due_passed'] === 1) ) {
                $count++;
            }
        }
        if ( $count < 1 ) {
            return null;
        }
        return $sum / $count;
    }

    private function categoryHeading($title, $percent, $extra_credit, $included = null, $after_due = null)
    {
        $parts = array();
        if ( $included !== null ) {
            $parts[] = sprintf('%1.1f', $included).'% '.__('incomplete included');
        }
        if ( $after_due !== null ) {
            $parts[] = sprintf('%1.1f', $after_due).'% '.__('zero after due date');
        }
        if ( $extra_credit ) {
            $parts[] = (int) $percent.'% '.__('extra credit');
        } else {
            $parts[] = (int) $percent.'%';
        }
        return '<h2 class="grade-category-heading">'.htmlspecialchars($title)
            .' <small>'.htmlspecialchars(implode(' · ', $parts)).'</small></h2>';
    }

    /**
     * Column order follows the first row, then any later row that still has
     * a server column because that result is sent upward.
     *
     * @param array<int,array<string,mixed>> $rows
     * @return string[]
     */
    private function gradeTableColumns($rows)
    {
        $columns = array();
        foreach ( $rows as $row ) {
            foreach ( $row as $key => $value ) {
                if ( isset($columns[$key]) ) {
                    continue;
                }
                if ( strpos($key, '_') === 0 ) {
                    continue;
                }
                if ( substr($key, -3) === '_id' && is_numeric($value) ) {
                    continue;
                }
                $columns[$key] = true;
            }
        }
        return array_keys($columns);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     */
    private function renderGradeTable($rows)
    {
        if ( count($rows) < 1 ) {
            return;
        }
        echo '<div style="padding:3px;">';
        echo '<table border="1" class="table table-hover table-condensed table-responsive">';
        $columns = $this->gradeTableColumns($rows);
        echo '<thead><tr>';
        foreach ( $columns as $key ) {
            $header_key = '_header_'.$key;
            echo '<th scope="col">';
            if ( isset($rows[0][$header_key]) ) {
                echo $rows[0][$header_key];
            } else {
                echo htmlspecialchars(ucwords(str_replace('_', ' ', $key)));
            }
            echo '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ( $rows as $row ) {
            echo '<tr>';
            foreach ( $columns as $key ) {
                $value = array_key_exists($key, $row) ? $row[$key] : '';
                echo '<td>';
                $html_key = '_html_'.$key;
                if ( isset($row[$html_key]) && $row[$html_key] !== '' ) {
                    echo $row[$html_key];
                } else {
                    echo htmlspecialchars((string) $value);
                }
                echo '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    private function printGradeCategoryStyles()
    {
        echo '<style>
            .tsugi-grade-tabs { margin: 0 0 1em; }
            .grade-activity { display: inline-block; margin: 0 0 0.5em 0.75em; vertical-align: middle; }
            .grade-activity .dropdown { display: inline-block; margin-right: 0.75em; vertical-align: middle; }
            .grade-activity-current { vertical-align: middle; }
            .grade-overall { font-size: 1.3em; }
            .grade-category-heading { margin-top: 1.25em; font-size: 1.4em; }
            .grade-category-heading small { color: #777; font-weight: normal; }
            .grade-kebab .btn-link { color: #333; padding: 0 0.4em; }
            .grade-kebab-menu { min-width: 16em; padding: 0.35em 0; }
            .grade-kebab-item { padding: 0.35em 1em; white-space: normal; }
            .grade-kebab-label { display: block; color: #777; font-size: 0.85em; }
            .grade-kebab-value { display: block; }
            .grades-configuration .grades-section { margin-top: 1.5em; }
            .grades-configuration h2 { margin-top: 0; }
            .grades-configuration .grades-percent { width: 5.5em; }
            .grades-configuration .form-inline { display: inline-block; margin: 0 0.25em 0.25em 0; vertical-align: middle; }
            .grade-resend-log { margin-top: 1em; white-space: pre-wrap; }
            .grade-log { list-style: none; padding-left: 0; margin: 0; }
            .grade-log > li { margin: 0 0 0.2em; }
            .grade-log-line { margin: 0; line-height: 1.4; }
            .grade-log-expand { border: 0; background: transparent; padding: 0 0.2em; line-height: 1; color: #555; vertical-align: middle; }
            .grade-log-expand .fa { display: inline-block; transition: transform 0.12s linear; }
            .grade-log-expand[aria-expanded="true"] .fa { transform: rotate(180deg); }
            .grade-log-detail { margin: 0.15em 0 0.4em 1.1em; white-space: pre-wrap; }
        </style>';
    }

    private function finishGradePage()
    {
        global $OUTPUT;
        $OUTPUT->footerStart();
        $OUTPUT->footerEnd();
    }
}

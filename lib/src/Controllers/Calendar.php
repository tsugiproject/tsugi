<?php

namespace Tsugi\Controllers;


use \Tsugi\Util\U;
use Tsugi\Core\LTIX;
use Tsugi\Core\Manifest;
use Tsugi\Lumos\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tsugi\Grades\GradeUtil;

use Tsugi\Core\ReqScope;

class Calendar extends Tool {

    const ROUTE = '/calendar';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix.'/json', 'Calendar@jsonDue');
        $app->router->get($prefix.'/json/', 'Calendar@jsonDue');
        $app->router->get($prefix, 'Calendar@get');
        $app->router->get($prefix.'/', 'Calendar@get');
    }

    /**
     * YYYY-MM-DD from MySQL datetime string, or null.
     */
    private static function mysqlEndToDateKey($mysql) {
        if ( $mysql === null || $mysql === '' ) {
            return null;
        }
        if ( preg_match('/^(\d{4}-\d{2}-\d{2})/', $mysql, $m ) ) {
            return $m[1];
        }
        return null;
    }

    /**
     * Omit a link's grade when computing due-badge state for ungraded ("result": false) LTI items.
     *
     * @param array<string,float> $allgrades
     * @return array<string,float>
     */
    private static function gradesMapForDueModifier($allgrades, $linkKey, $participatesInGrades) {
        if ( $participatesInGrades ) {
            return $allgrades;
        }
        $g = $allgrades;
        unset($g[$linkKey]);
        return $g;
    }

    /**
     * Flat due rows, earliest date first, then title.
     *
     * @param array<int,array<string,mixed>> $items
     * @param array<string,array<string,mixed>> $dueMap
     * @param array<string,float> $allgrades
     * @return list<array{date:string,title:string,href:string,module_title:string,due_mod:string}>
     */
    private function dueRows($items, $dueMap, $allgrades) {
        $rows = array();
        $parent = U::get_rest_parent();
        $lessonsPath = Lessons::ROUTE;

        foreach ( $items as $it ) {
            $lk = $it['resource_link_id'];
            if ( ! isset($dueMap[$lk]) ) {
                continue;
            }
            $end = $dueMap[$lk]['end_datetime'];
            $dateKey = self::mysqlEndToDateKey($end);
            if ( $dateKey === null ) {
                continue;
            }
            $anchor = isset($it['module_anchor']) ? (string) $it['module_anchor'] : '';
            $href = $parent . $lessonsPath . '/' . urlencode($anchor);
            $participates = ! isset($it['participates_in_grades']) || $it['participates_in_grades'];
            $dueMod = \Tsugi\Services\Lessons\LessonsService::assignmentsDueBadgeModifier($lk, $end, self::gradesMapForDueModifier($allgrades, $lk, $participates));
            $rows[] = array(
                'date' => $dateKey,
                'title' => (string) $it['item_title'],
                'href' => $href,
                'module_title' => (string) $it['module_title'],
                'due_mod' => $dueMod,
            );
        }
        return self::sortDueRows($rows);
    }

    /**
     * @param list<array{date:string,title:string}> $rows
     * @return list<array{date:string,title:string}>
     */
    public static function sortDueRows(array $rows) {
        usort($rows, function ($a, $b) {
            $byDate = strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''));
            if ( $byDate !== 0 ) {
                return $byDate;
            }
            return strcasecmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
        });
        return $rows;
    }

    /**
     * @param list<array{date:string,title:string,href:string,module_title:string,due_mod:string}> $rows
     * @return array<string, list<array{date:string,title:string,href:string,module_title:string,due_mod:string}>>
     */
    public static function groupDueRows(array $rows) {
        $byDate = array();
        foreach ( $rows as $row ) {
            $dateKey = (string) ($row['date'] ?? '');
            if ( $dateKey === '' ) {
                continue;
            }
            if ( ! isset($byDate[$dateKey]) ) {
                $byDate[$dateKey] = array();
            }
            $byDate[$dateKey][] = $row;
        }
        return $byDate;
    }

    /**
     * @param \Tsugi\Services\Lessons\LessonsService|false $lessons
     * @return array{rows:list<array<string,string>>,byDate:array<string,list<array<string,string>>>}|null
     */
    private function loadDueBundle($lessons) {
        if ( ! $lessons ) {
            return null;
        }
        $items = $this->dueSourceItems($lessons);
        $dueMap = array();
        if ( ReqScope::currentContextId() !== 0 ) {
            $dueMap = GradeUtil::loadDueDatesForDisplay(ReqScope::currentContextId());
        }
        $allgrades = array();
        $gradeRows = GradeUtil::loadGradesCurrentUser();
        foreach ( $gradeRows as $row ) {
            $allgrades[$row['resource_link_id']] = $row['grade'];
        }
        $rows = $this->dueRows($items, $dueMap, $allgrades);
        return array(
            'rows' => $rows,
            'byDate' => self::groupDueRows($rows),
        );
    }

    /**
     * LTI assignment rows plus native Quiz1 rows. A quiz uses the same link key as its due date.
     *
     * @param \Tsugi\Services\Lessons\LessonsService $lessons
     * @return array<int,array<string,mixed>>
     */
    private function dueSourceItems($lessons) {
        $items = $lessons->enumerateLtiAssignmentItems();
        $seen = array();
        foreach ( $items as $it ) {
            $key = isset($it['resource_link_id']) ? trim((string) $it['resource_link_id']) : '';
            if ( $key !== '' ) {
                $seen[$key] = true;
            }
        }
        foreach ( $lessons->enumerateQuizDueItems() as $quiz ) {
            $key = isset($quiz['resource_link_id']) ? trim((string) $quiz['resource_link_id']) : '';
            if ( $key === '' || isset($seen[$key]) ) {
                continue;
            }
            $seen[$key] = true;
            $items[] = $quiz;
        }
        return $items;
    }

    /**
     * Month grid for the calendar page or an embedded home block.
     *
     * @param bool $embedded Home block: smaller heading, no view switcher.
     */
    public function monthHtml(Request $request, $baseUrl, $embedded = false) {
        $lessons = Manifest::currentLessons();
        if ( ! $lessons ) {
            return '<p>'.htmlspecialchars(__('No lessons are configured for this course.')).'</p>';
        }
        $bundle = $this->loadDueBundle($lessons);
        $byDate = is_array($bundle) ? $bundle['byDate'] : array();
        return $this->monthMarkup($request, $baseUrl, $byDate, $embedded);
    }

    /**
     * Simple list of assignment due dates, earliest first.
     *
     * @param bool $embedded Home block: smaller heading.
     */
    public function listHtml($embedded = false) {
        $lessons = Manifest::currentLessons();
        if ( ! $lessons ) {
            return '<p>'.htmlspecialchars(__('No lessons are configured for this course.')).'</p>';
        }
        $bundle = $this->loadDueBundle($lessons);
        $rows = is_array($bundle) ? $bundle['rows'] : array();
        return self::listMarkup($rows, $embedded);
    }

    /**
     * @param string $view month|list
     */
    public function viewSwitcherHtml($baseUrl, $view, Request $request) {
        $monthUrl = U::add_url_parm($baseUrl, 'view', 'month');
        $listUrl = U::add_url_parm($baseUrl, 'view', 'list');
        $year = $request->query->get('year');
        $month = $request->query->get('month');
        if ( $year !== null && $year !== '' ) {
            $monthUrl = U::add_url_parm($monthUrl, 'year', (string) (int) $year);
        }
        if ( $month !== null && $month !== '' ) {
            $monthUrl = U::add_url_parm($monthUrl, 'month', (string) (int) $month);
        }
        $monthCurrent = $view !== 'list';
        $html = self::calendarStyles();
        $html .= '<div class="tsugi-cal-views" role="navigation" aria-label="'.htmlspecialchars(__('Calendar view')).'">'."\n";
        $html .= '<a class="btn btn-default'.($monthCurrent ? ' active' : '').'" href="'.htmlspecialchars($monthUrl).'"';
        if ( $monthCurrent ) {
            $html .= ' aria-current="page"';
        }
        $html .= '>'.htmlspecialchars(__('Month')).'</a>'."\n";
        $html .= '<a class="btn btn-default'.($monthCurrent ? '' : ' active').'" href="'.htmlspecialchars($listUrl).'"';
        if ( ! $monthCurrent ) {
            $html .= ' aria-current="page"';
        }
        $html .= '>'.htmlspecialchars(__('Due dates')).'</a>'."\n";
        $html .= "</div>\n";
        return $html;
    }

    /**
     * Count unfinished assignments that are “due soon” (within 7 days) or “late” (past due).
     *
     * @param array<int,array<string,mixed>> $items from dueSourceItems()
     * @param array<string,array<string,mixed>> $dueMap from GradeUtil::loadDueDatesForDisplay()
     * @param array<string,float> $allgrades resource_link_id => grade
     * @return array{due_soon:int,late:int}
     */
    private function countDueSoonAndLate($items, $dueMap, $allgrades) {
        $dueSoon = 0;
        $late = 0;
        foreach ( $items as $it ) {
            $lk = $it['resource_link_id'];
            if ( ! isset($dueMap[$lk]) ) {
                continue;
            }
            $end = $dueMap[$lk]['end_datetime'];
            if ( $end === null || $end === '' ) {
                continue;
            }
            $participates = ! isset($it['participates_in_grades']) || $it['participates_in_grades'];
            $mod = \Tsugi\Services\Lessons\LessonsService::assignmentsDueBadgeModifier($lk, $end, self::gradesMapForDueModifier($allgrades, $lk, $participates));
            if ( $mod === 'tsugi-assignments-due-soon' ) {
                $dueSoon++;
            } elseif ( $mod === 'tsugi-assignments-due-past' ) {
                $late++;
            }
        }
        return array('due_soon' => $dueSoon, 'late' => $late);
    }

    /**
     * JSON web service: counts of unfinished “due soon” and unfinished late assignments.
     */
    public function jsonDue(Request $request)
    {
        global $CFG;

        $l = Manifest::currentLessons();
        if ( ! $l ) {
            return new JsonResponse(
                array('status' => 'error', 'detail' => 'Lessons not configured'),
                500
            );
        }
        if ( ! ReqScope::isLoggedIn() || ! ReqScope::currentContextId() ) {
            return new JsonResponse(
                array('status' => 'error', 'detail' => 'Authentication and course context required'),
                401
            );
        }

        LTIX::getConnection();

        $items = $this->dueSourceItems($l);
        $dueMap = GradeUtil::loadDueDatesForDisplay(ReqScope::currentContextId());

        $allgrades = array();
        $rows = GradeUtil::loadGradesCurrentUser();
        foreach ( $rows as $row ) {
            $allgrades[$row['resource_link_id']] = $row['grade'];
        }

        $counts = $this->countDueSoonAndLate($items, $dueMap, $allgrades);

        return new JsonResponse(array(
            'status' => 'success',
            'due_soon' => $counts['due_soon'],
            'late' => $counts['late'],
        ));
    }

    public function get(Request $request)
    {
        global $OUTPUT;

        Manifest::requireCurrentLessons();
        $base = U::addSession($this->toolHome(self::ROUTE));
        $view = strtolower(trim((string) $request->query->get('view', 'month')));
        if ( $view !== 'list' ) {
            $view = 'month';
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();

        echo('<div class="container tsugi-cal-wrap">' . "\n");
        echo($this->viewSwitcherHtml($base, $view, $request));
        if ( $view === 'list' ) {
            echo($this->listHtml(false));
        } else {
            echo($this->monthHtml($request, $base, false));
        }
        echo('<p class="text-muted small">'.__('Each link opens the lessons module that contains that assignment. Due dates use the course end-of-day time stored for each assignment.')."</p>\n");
        echo("</div>\n");

        $OUTPUT->footer();
    }

    /**
     * @param array<string, list<array<string,string>>> $byDate
     */
    private function monthMarkup(Request $request, $baseUrl, array $byDate, $embedded) {
        $nowY = (int) date('Y');
        $nowM = (int) date('n');
        $nowD = (int) date('j');

        $y = (int) $request->query->get('year', $nowY);
        $m = (int) $request->query->get('month', $nowM);
        if ( $m < 1 || $m > 12 ) {
            $m = $nowM;
        }
        if ( $y < 2000 || $y > 2100 ) {
            $y = $nowY;
        }

        $firstTs = mktime(0, 0, 0, $m, 1, $y);
        if ( $firstTs === false ) {
            $y = $nowY;
            $m = $nowM;
            $firstTs = mktime(0, 0, 0, $m, 1, $y);
        }
        $daysInMonth = (int) date('t', $firstTs);
        $startDow = (int) date('w', $firstTs);

        $prevTs = mktime(0, 0, 0, $m - 1, 1, $y);
        if ( $prevTs === false ) {
            $prevTs = strtotime('-1 month', $firstTs);
        }
        $nextTs = mktime(0, 0, 0, $m + 1, 1, $y);
        if ( $nextTs === false ) {
            $nextTs = strtotime('+1 month', $firstTs);
        }
        $prevY = (int) date('Y', $prevTs);
        $prevM = (int) date('n', $prevTs);
        $nextY = (int) date('Y', $nextTs);
        $nextM = (int) date('n', $nextTs);

        $urlPrev = U::add_url_parm(U::add_url_parm($baseUrl, 'year', (string) $prevY), 'month', (string) $prevM);
        $urlNext = U::add_url_parm(U::add_url_parm($baseUrl, 'year', (string) $nextY), 'month', (string) $nextM);
        $urlToday = $baseUrl;
        $monthTitle = date('F Y', $firstTs);
        $heading = $embedded ? 'h2' : 'h1';
        $title = ($embedded ? __('Calendar') : __('Assignment due dates')).' — '.$monthTitle;

        $html = self::calendarStyles();
        $html .= '<div class="tsugi-cal-nav">'."\n";
        $html .= '<a href="'.htmlspecialchars($urlPrev).'" class="btn btn-default">&laquo; '.__('Previous').'</a>'."\n";
        $html .= '<'.$heading.'>'.htmlspecialchars($title).'</'.$heading.">\n";
        $html .= '<span>'."\n";
        $html .= '<a href="'.htmlspecialchars($urlToday).'" class="btn btn-default btn-sm">'.__('Today').'</a> '."\n";
        $html .= '<a href="'.htmlspecialchars($urlNext).'" class="btn btn-default">'.__('Next').' &raquo;</a>'."\n";
        $html .= "</span>\n</div>\n";

        $html .= '<table class="table table-bordered tsugi-cal-table" role="grid" aria-label="'.htmlspecialchars(__('Assignment due dates')).'">'."\n";
        $html .= "<thead><tr>\n";
        foreach ( array(__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')) as $label ) {
            $html .= '<th scope="col">'.htmlspecialchars($label)."</th>\n";
        }
        $html .= "</tr></thead>\n<tbody>\n";

        $prevMonthTs = mktime(0, 0, 0, $m, 0, $y);
        $dimPrev = $prevMonthTs !== false ? (int) date('t', $prevMonthTs) : 31;
        $totalCells = $startDow + $daysInMonth;
        $numRows = (int) ceil($totalCells / 7);
        $totalSlots = $numRows * 7;

        $html .= "<tr>\n";
        for ( $slot = 0; $slot < $totalSlots; $slot++ ) {
            if ( $slot > 0 && $slot % 7 === 0 ) {
                $html .= "</tr>\n<tr>\n";
            }
            if ( $slot < $startDow ) {
                $d = $dimPrev - $startDow + $slot + 1;
                $html .= '<td class="tsugi-cal-day tsugi-cal-other"><div class="tsugi-cal-day-inner"><span class="tsugi-cal-daynum">'.$d.'</span></div></td>'."\n";
                continue;
            }
            if ( $slot < $startDow + $daysInMonth ) {
                $day = $slot - $startDow + 1;
                $dateKey = sprintf('%04d-%02d-%02d', $y, $m, $day);
                $isToday = ( $y === $nowY && $m === $nowM && $day === $nowD );
                $tdClass = 'tsugi-cal-day' . ( $isToday ? ' tsugi-cal-today' : '' );
                $html .= '<td class="'.htmlspecialchars($tdClass).'"><div class="tsugi-cal-day-inner">'."\n";
                $html .= '<div class="tsugi-cal-daynum" aria-hidden="true">'.$day.'</div>'."\n";
                if ( isset($byDate[$dateKey]) && count($byDate[$dateKey]) > 0 ) {
                    $html .= '<ul class="tsugi-cal-events">'."\n";
                    foreach ( $byDate[$dateKey] as $ev ) {
                        $html .= self::eventLinkHtml($ev);
                    }
                    $html .= "</ul>\n";
                }
                $html .= "</div></td>\n";
                continue;
            }
            $nextDay = $slot - ($startDow + $daysInMonth) + 1;
            $html .= '<td class="tsugi-cal-day tsugi-cal-other"><div class="tsugi-cal-day-inner"><span class="tsugi-cal-daynum">'.$nextDay.'</span></div></td>'."\n";
        }
        $html .= "</tr>\n</tbody></table>\n";
        if ( $embedded ) {
            $open = U::addSession(self::determineToolHome(self::ROUTE));
            $html .= '<p><a href="'.htmlspecialchars($open).'">'.htmlspecialchars(__('Open calendar')).'</a></p>'."\n";
        }
        return $html;
    }

    /**
     * Home due list. More than two late rows keep the most recent one.
     * More than two Upcoming rows keep the two soonest. Up next and Completed stay.
     *
     * @param list<array<string,string>> $rows Date order, then title
     * @return list<array<string,mixed>>
     */
    public static function homeDueEntries(array $rows) {
        $rows = self::sortDueRows($rows);
        $late = array();
        $upcoming = array();
        foreach ( $rows as $i => $ev ) {
            if ( self::isLateRow($ev) ) {
                $late[] = $i;
            } else if ( self::isUpcomingRow($ev) ) {
                $upcoming[] = $i;
            }
        }
        $drop = array();
        $lateCount = count($late);
        if ( $lateCount > 2 ) {
            $keepLate = $late[$lateCount - 1];
            foreach ( $late as $idx ) {
                if ( $idx !== $keepLate ) {
                    $drop[$idx] = true;
                }
            }
        }
        $upcomingCount = count($upcoming);
        if ( $upcomingCount > 2 ) {
            $keepUpcoming = array_flip(array_slice($upcoming, 0, 2));
            foreach ( $upcoming as $idx ) {
                if ( ! isset($keepUpcoming[$idx]) ) {
                    $drop[$idx] = true;
                }
            }
        }
        $entries = array();
        $lateMarked = false;
        $upcomingShown = 0;
        foreach ( $rows as $i => $ev ) {
            if ( isset($drop[$i]) ) {
                continue;
            }
            if ( $lateCount > 2 && ! $lateMarked && self::isLateRow($ev) ) {
                $entries[] = array('marker' => 'late', 'count' => $lateCount);
                $lateMarked = true;
            }
            $entries[] = $ev;
            if ( $upcomingCount > 2 && self::isUpcomingRow($ev) ) {
                $upcomingShown++;
                if ( $upcomingShown === 2 ) {
                    $entries[] = array('marker' => 'upcoming', 'count' => $upcomingCount);
                }
            }
        }
        return $entries;
    }

    /**
     * @param array<string,mixed> $ev
     */
    private static function isLateRow(array $ev) {
        return (string) ($ev['due_mod'] ?? '') === 'tsugi-assignments-due-past';
    }

    /**
     * Upcoming is the future badge. Up next stays on the home list.
     *
     * @param array<string,mixed> $ev
     */
    private static function isUpcomingRow(array $ev) {
        return (string) ($ev['due_mod'] ?? '') === 'tsugi-assignments-due-future';
    }

    /**
     * @param 'late'|'upcoming' $which
     */
    private static function viewAllHtml($which, $count) {
        $open = U::add_url_parm(U::addSession(self::determineToolHome(self::ROUTE)), 'view', 'list');
        $label = $which === 'late'
            ? sprintf(__('View all (%d late)'), (int) $count)
            : sprintf(__('View all (%d upcoming)'), (int) $count);
        return '<li class="tsugi-due-list-more"><a class="btn btn-default btn-sm" href="'.htmlspecialchars($open).'">'.htmlspecialchars($label)."</a></li>\n";
    }

    /**
     * @param list<array<string,string>> $rows
     */
    public static function listMarkup(array $rows, $embedded = false) {
        $heading = $embedded ? 'h2' : 'h1';
        $html = self::calendarStyles();
        $html .= '<div class="tsugi-due-list">'."\n";
        $html .= '<'.$heading.'>'.htmlspecialchars(__('Due dates')).'</'.$heading.">\n";
        if ( count($rows) === 0 ) {
            $html .= '<p>'.htmlspecialchars(__('No assignment due dates.'))."</p>\n</div>\n";
            return $html;
        }
        $entries = $embedded ? self::homeDueEntries($rows) : $rows;
        $html .= '<ul class="tsugi-due-list-items">'."\n";
        $lastDate = null;
        $entryCount = count($entries);
        foreach ( $entries as $n => $ev ) {
            if ( isset($ev['marker']) ) {
                $nextDate = '';
                if ( $n + 1 < $entryCount && ! isset($entries[$n + 1]['marker']) ) {
                    $nextDate = (string) ($entries[$n + 1]['date'] ?? '');
                }
                if ( $lastDate !== null && $nextDate !== $lastDate ) {
                    $html .= "</ul></li>\n";
                    $lastDate = null;
                }
                $html .= self::viewAllHtml((string) $ev['marker'], (int) ($ev['count'] ?? 0));
                continue;
            }
            $dateKey = (string) ($ev['date'] ?? '');
            if ( $dateKey !== $lastDate ) {
                if ( $lastDate !== null ) {
                    $html .= "</ul></li>\n";
                }
                $lastDate = $dateKey;
                $html .= '<li class="tsugi-due-list-day"><div class="tsugi-due-list-date">'.htmlspecialchars(self::dateKeyLabel($dateKey)).'</div><ul>'."\n";
            }
            $html .= self::eventLinkHtml($ev, true);
        }
        if ( $lastDate !== null ) {
            $html .= "</ul></li>\n";
        }
        $html .= "</ul>\n</div>\n";
        if ( $embedded ) {
            $open = U::add_url_parm(U::addSession(self::determineToolHome(self::ROUTE)), 'view', 'list');
            $html .= '<p><a href="'.htmlspecialchars($open).'">'.htmlspecialchars(__('Open due date list')).'</a></p>'."\n";
        }
        return $html;
    }

    /**
     * @param array<string,string> $ev
     */
    private static function eventLinkHtml(array $ev, $withModule = false) {
        $full = (string) ($ev['title'] ?? '');
        $moduleTitle = (string) ($ev['module_title'] ?? '');
        $dueMod = (string) ($ev['due_mod'] ?? '');
        $stateText = Lessons::assignmentsDueStateVisibleLabel($dueMod);
        $aria = $full.' — '.$moduleTitle.' ('.$stateText.')';
        $classes = 'tsugi-cal-event-link '.$dueMod;
        $html = '<li><a class="'.htmlspecialchars($classes).'" href="'.htmlspecialchars(U::addSession((string) ($ev['href'] ?? ''))).'" aria-label="'.htmlspecialchars($aria).'">'.htmlspecialchars($full).'</a>';
        if ( $withModule ) {
            if ( $moduleTitle !== '' ) {
                $html .= ' <span class="text-muted">'.htmlspecialchars($moduleTitle).'</span>';
            }
            $html .= ' <span class="tsugi-assignments-due-badge '.htmlspecialchars($dueMod).'">'.htmlspecialchars($stateText).'</span>';
        }
        return $html."</li>\n";
    }

    private static function dateKeyLabel($dateKey) {
        $ts = strtotime($dateKey.' 12:00:00');
        if ( $ts === false ) {
            return (string) $dateKey;
        }
        return date('M j, Y', $ts);
    }

    private static function calendarStyles() {
        return '<style>
.tsugi-cal-views { margin: 0 0 1em; }
.tsugi-cal-views .btn { margin-right: 0.35em; }
.tsugi-cal-views .btn.active { font-weight: 700; }
.tsugi-cal-nav { display: flex; align-items: center; justify-content: space-between; gap: 0.75em; flex-wrap: wrap; margin-bottom: 0.75em; }
.tsugi-cal-nav h1, .tsugi-cal-nav h2 { margin: 0; font-size: 1.4em; }
.tsugi-due-list-items, .tsugi-due-list-day > ul { list-style: none; padding: 0; margin: 0; }
.tsugi-due-list-day { margin: 0 0 0.9em; }
.tsugi-due-list-date { font-weight: 600; }
.tsugi-due-list-day > ul { margin-top: 0.25em; }
.tsugi-due-list-day > ul > li { margin: 0.2em 0; }
.tsugi-due-list-more { margin: 0.35em 0 0.55em; }
.tsugi-assignments-due-badge { display: inline-block; margin-left: 0.35em; padding: 0.1em 0.45em; border-radius: 0.25em; font-size: 0.75em; border: 1px solid transparent; }
.tsugi-assignments-due-completed { background: #d4edda; color: #155724; border-color: #c3e6cb; }
.tsugi-assignments-due-past { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
.tsugi-assignments-due-soon { background: #fff3cd; color: #856404; border-color: #ffeeba; }
.tsugi-assignments-due-future { background: #e9ecef; color: #495057; border-color: #dee2e6; }
.tsugi-assignments-due-neutral { background: #f8f9fa; color: #6c757d; border-color: #dee2e6; }
</style>'."\n";
    }
}

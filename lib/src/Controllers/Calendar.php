<?php

namespace Tsugi\Controllers;

use Tsugi\Core\LTIX;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tsugi\Grades\GradeUtil;
use Tsugi\Util\U;

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
     * @param array<string,float> $allgrades resource_link_id => grade (0..1)
     * @return array<string, list<array{title:string,href:string,module_title:string,due_mod:string}>>
     */
    private function buildDueDatesByDay($items, $dueMap, $allgrades) {
        $byDate = array();
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
            if ( ! isset($byDate[$dateKey]) ) {
                $byDate[$dateKey] = array();
            }
            $dueMod = \Tsugi\UI\Lessons::assignmentsDueBadgeModifier($lk, $end, $allgrades);
            $byDate[$dateKey][] = array(
                'title' => $it['item_title'],
                'href' => $href,
                'module_title' => $it['module_title'],
                'due_mod' => $dueMod,
            );
        }
        return $byDate;
    }

    /**
     * Count unfinished assignments that are “due soon” (within 7 days) or “late” (past due).
     *
     * @param array<int,array<string,mixed>> $items from Lessons::enumerateLtiAssignmentItems()
     * @param array<string,array<string,mixed>> $dueMap from GradeUtil::loadDueDatesForContext()
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
            $mod = \Tsugi\UI\Lessons::assignmentsDueBadgeModifier($lk, $end, $allgrades);
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

        if ( ! isset($CFG->lessons) ) {
            return new JsonResponse(
                array('status' => 'error', 'detail' => 'Lessons not configured'),
                500
            );
        }
        if ( ! U::get($_SESSION, 'id') || ! isset($_SESSION['context_id']) ) {
            return new JsonResponse(
                array('status' => 'error', 'detail' => 'Authentication and course context required'),
                401
            );
        }

        LTIX::getConnection();

        $l = new \Tsugi\UI\Lessons($CFG->lessons);
        $items = $l->enumerateLtiAssignmentItems();
        $dueMap = GradeUtil::loadDueDatesForContext($_SESSION['context_id']);

        $allgrades = array();
        $rows = GradeUtil::loadGradesForCourse($_SESSION['id'], $_SESSION['context_id']);
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
        global $CFG, $OUTPUT;

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }

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

        $l = new \Tsugi\UI\Lessons($CFG->lessons);
        $items = $l->enumerateLtiAssignmentItems();
        $dueMap = array();
        if ( isset($_SESSION['context_id']) ) {
            $dueMap = GradeUtil::loadDueDatesForContext($_SESSION['context_id']);
        }
        $allgrades = array();
        if ( isset($_SESSION['id']) && isset($_SESSION['context_id']) ) {
            $rows = GradeUtil::loadGradesForCourse($_SESSION['id'], $_SESSION['context_id']);
            foreach ( $rows as $row ) {
                $allgrades[$row['resource_link_id']] = $row['grade'];
            }
        }
        $byDate = $this->buildDueDatesByDay($items, $dueMap, $allgrades);

        $calHome = U::addSession($this->toolHome(self::ROUTE));
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

        $urlPrev = $calHome . '?year=' . $prevY . '&month=' . $prevM;
        $urlNext = $calHome . '?year=' . $nextY . '&month=' . $nextM;
        $urlToday = $calHome;

        $monthTitle = htmlspecialchars(date('F Y', $firstTs));

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();

        echo('<div class="container tsugi-cal-wrap">' . "\n");

        echo('<div class="tsugi-cal-nav">' . "\n");
        echo('<a href="'.htmlspecialchars($urlPrev).'" class="btn btn-default">&laquo; '.__('Previous').'</a>' . "\n");
        echo('<h1>'.__('Assignment due dates').' &mdash; '.$monthTitle."</h1>\n");
        echo('<span>' . "\n");
        echo('<a href="'.htmlspecialchars($urlToday).'" class="btn btn-default btn-sm">'.__('Today').'</a> ' . "\n");
        echo('<a href="'.htmlspecialchars($urlNext).'" class="btn btn-default">'.__('Next').' &raquo;</a>' . "\n");
        echo("</span>\n");
        echo("</div>\n");

        echo('<table class="table table-bordered tsugi-cal-table" role="grid" aria-label="'.htmlspecialchars(__('Assignment due dates')).'">' . "\n");
        echo("<thead><tr>\n");
        $dowLabels = array(__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat'));
        foreach ( $dowLabels as $label ) {
            echo('<th scope="col">'.htmlspecialchars($label)."</th>\n");
        }
        echo("</tr></thead>\n<tbody>\n");

        $prevMonthTs = mktime(0, 0, 0, $m, 0, $y);
        $dimPrev = $prevMonthTs !== false ? (int) date('t', $prevMonthTs) : 31;

        $totalCells = $startDow + $daysInMonth;
        $numRows = (int) ceil($totalCells / 7);
        $totalSlots = $numRows * 7;

        echo("<tr>\n");
        for ( $slot = 0; $slot < $totalSlots; $slot++ ) {
            if ( $slot > 0 && $slot % 7 === 0 ) {
                echo("</tr>\n<tr>\n");
            }

            if ( $slot < $startDow ) {
                $d = $dimPrev - $startDow + $slot + 1;
                echo('<td class="tsugi-cal-day tsugi-cal-other"><div class="tsugi-cal-day-inner"><span class="tsugi-cal-daynum">'.$d.'</span></div></td>' . "\n");
                continue;
            }
            if ( $slot < $startDow + $daysInMonth ) {
                $day = $slot - $startDow + 1;
                $dateKey = sprintf('%04d-%02d-%02d', $y, $m, $day);
                $isToday = ( $y === $nowY && $m === $nowM && $day === $nowD );
                $tdClass = 'tsugi-cal-day' . ( $isToday ? ' tsugi-cal-today' : '' );
                echo('<td class="'.htmlspecialchars($tdClass).'"><div class="tsugi-cal-day-inner">' . "\n");
                echo('<div class="tsugi-cal-daynum" aria-hidden="true">'.$day.'</div>' . "\n");
                if ( isset($byDate[$dateKey]) && count($byDate[$dateKey]) > 0 ) {
                    echo('<ul class="tsugi-cal-events">' . "\n");
                    foreach ( $byDate[$dateKey] as $ev ) {
                        $full = $ev['title'];
                        $moduleTitle = $ev['module_title'];
                        $dueMod = $ev['due_mod'];
                        $stateText = \Tsugi\UI\Lessons::assignmentsDueStateVisibleLabel($dueMod);
                        $aria = $full . ' — ' . $moduleTitle . ' (' . $stateText . ')';
                        $classes = 'tsugi-cal-event-link ' . $dueMod;
                        echo('<li><a class="'.htmlspecialchars($classes).'" href="'.htmlspecialchars(U::addSession($ev['href'])).'" aria-label="'.htmlspecialchars($aria).'">'.htmlspecialchars($full).'</a></li>' . "\n");
                    }
                    echo("</ul>\n");
                }
                echo("</div></td>\n");
                continue;
            }

            $nextDay = $slot - ($startDow + $daysInMonth) + 1;
            echo('<td class="tsugi-cal-day tsugi-cal-other"><div class="tsugi-cal-day-inner"><span class="tsugi-cal-daynum">'.$nextDay.'</span></div></td>' . "\n");
        }
        echo("</tr>\n");
        echo("</tbody></table>\n");

        echo('<p class="text-muted small">'.__('Each link opens the lessons module that contains that assignment. Due dates use the course end-of-day time stored for each assignment.')."</p>\n");
        echo("</div>\n");

        $OUTPUT->footer();
    }
}

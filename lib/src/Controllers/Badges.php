<?php

namespace Tsugi\Controllers;

use Tsugi\Crypt\AesOpenSSL;
use Tsugi\Lumos\Application;
use Symfony\Component\HttpFoundation\Request;
use Tsugi\Core\LTIX;
use Tsugi\Core\Manifest;
use Tsugi\Grades\GradeUtil;
use Tsugi\Core\Badges as BadgeJson;
use Tsugi\Services\Badges\BadgeService;
use Tsugi\Services\Badges\CourseBadges;
use Tsugi\Services\Lessons\LessonsService;
use Tsugi\Util\U;

use Tsugi\Core\ReqScope;

class Badges extends Tool {

    const ROUTE = '/badges';
    const NAME = 'Badges';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Badges@get');
        $app->router->get($prefix.'/', 'Badges@get');
        $app->router->get($prefix.'/analytics', 'Badges@analytics');
    }

    public function get(Request $request)
    {
        global $CFG, $OUTPUT;

        // Ensure database connection
        LTIX::getConnection();

        // Check if user is logged in
        $this->requireAuth();

        // Record learner analytics (synthetic lti_link in this context)
        $this->lmsRecordLaunchAnalytics(self::ROUTE, self::NAME);

        // Check if user is instructor/admin for analytics button
        $show_analytics = $this->isInstructor() || $this->isAdmin();

        $l = Manifest::requireCurrentLessons();

        // Load all the Grades so far
        $allgrades = array();
        $rows = GradeUtil::loadGradesCurrentUser();
        foreach ( $rows as $row ) {
            $allgrades[$row['resource_link_id']] = $row['grade'];
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $menu = false;
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        if ( $show_analytics ) {
            $analytics_url = $this->toolHome(self::ROUTE) . '/analytics';
            echo('<span style="position: fixed; right: 10px; top: 75px; z-index: 999; background-color: white; padding: 2px;"><a href="'.htmlspecialchars($analytics_url).'" class="btn btn-default"><span class="glyphicon glyphicon-signal" aria-hidden="true"></span> '.htmlspecialchars(__('Analytics')).'</a></span>');
        }
        echo('<main class="container" id="main-content">');
        self::renderBadges($l, $allgrades, false, true);
        echo('</main>');
        $OUTPUT->footer();

    }

    /**
     * Analytics view for badges
     */
    public function analytics(Request $request)
    {
        return $this->showAnalytics(self::ROUTE, self::NAME);
    }

    public static function renderBadges(\Tsugi\Services\Lessons\LessonsService $lessons, $allgrades, $buffer=false, $supporter_ui=false)
    {
        ob_start();
        global $CFG, $OUTPUT;
        echo('<h1>'.$lessons->lessons->title."</h1>\n");
        $display = '';
        $displayname = U::get($_SESSION, 'displayname');
        $email = U::get($_SESSION, 'email');
        if ( $displayname ) $display .=  $displayname;
        if ( $email ) {
            if ( U::strlen($display) > 0 ) {
                $display .= ' (';
                $display .= $email;
                $display .= ')';
            } else {
                $display = $email;
            }
        }
        if (U::strlen($display) > 0 ) {
            echo("<p>".__("Student:")." ".htmlspecialchars($display)."</p>\n");
        }
        if ( $supporter_ui ) {
            \Tsugi\UI\Supporter::renderThankYou($CFG);
            \Tsugi\UI\Supporter::renderInvite($CFG);
        }
        $awarded = array();
?>
<ul class="nav nav-tabs" role="tablist">
  <li class="active" role="presentation"><a href="#home" id="home-tab" data-toggle="tab" role="tab" aria-controls="home" aria-selected="true">Progress</a></li>
  <li class="" role="presentation"><a href="#profile" id="profile-tab" data-toggle="tab" role="tab" aria-controls="profile" aria-selected="false">Badges Awarded</a></li>
</ul>
<div id="myTabContent" class="tab-content">
  <div class="tab-pane fade active in" id="home" role="tabpanel" aria-labelledby="home-tab">
<?php
        $defined = array();
        if ( isset($lessons->lessons->badges) && is_iterable($lessons->lessons->badges) ) {
            foreach ( $lessons->lessons->badges as $badge ) {
                $defined[] = $badge;
            }
        }
        if ( count($defined) < 1 ) {
            echo('<p>'.htmlspecialchars(__('No badges are defined for this course.')).'</p>');
            if ( Manifest::activeId() > 0 ) {
                $author = new self();
                if ( $author->isInstructor() ) {
                    $authorUrl = self::determineToolHome('/settings') . '/badges';
                    echo('<p><a href="'.htmlspecialchars($authorUrl).'">'.htmlspecialchars(__('Author badges')).'</a></p>');
                }
            }
        }
        echo('<table class="table table-striped table-hover "><tbody>'."\n");
        foreach($defined as $badge) {
            $threshold = (isset($badge->threshold) && is_numeric($badge->threshold)) ? $badge->threshold : 1.0;
            $assignmentIds = (isset($badge->assignments) && is_iterable($badge->assignments)) ? $badge->assignments : array();
            $count = 0;
            $total = 0;
            $scores = array();
            $places = array();
            foreach($assignmentIds as $resource_link_id) {
                $id = (string) $resource_link_id;
                $place = CourseBadges::placement($lessons, $id);
                $places[$id] = $place;
                $graded = $place['graded'];
                $score = 0;
                if ( $graded && isset($allgrades[$resource_link_id]) ) {
                    $score = 100*$allgrades[$resource_link_id];
                }
                $scores[$resource_link_id] = $score;
                if ( $graded ) {
                    $total = $total + $score;
                    $count = $count + 1;
                }
            }
            $max = $count * 100;
            $progress = $max <= 0 ? 100 : intval(($total / $max)*100);
            $kind = 'danger';
            if ( $progress < 5 ) $progress = 5;
            if ( $progress > 5 ) $kind = 'warning';
            if ( $progress > 50 ) $kind = 'info';
            if ( $progress >= (int) round($threshold * 100) ) {
                $kind = 'success';
                $awarded[] = $badge;
            }
            echo('<tr><td class="info">');
            $image = CourseBadges::displayUrl($badge, ReqScope::currentContextId());
            if ( $image === '' ) {
                echo('<i class="fa fa-certificate" aria-hidden="true" style="padding-right: 5px;"></i>');
            } else {
                echo('<img src="'.htmlspecialchars($image).'" alt="'.htmlspecialchars($badge->title).'" style="width: 4rem;"/> ');
            }
            echo(htmlspecialchars($badge->title));
            echo('</td><td class="info" style="width: 30%; min-width: 200px;">');
            echo('<div class="progress" role="progressbar" aria-valuenow="'.$progress.'" aria-valuemin="0" aria-valuemax="100" aria-label="'.htmlspecialchars($badge->title).': '.$progress.' percent">');
            echo('<div class="progress-bar progress-bar-'.$kind.'" style="width: '.$progress.'%"></div>');
            echo('</div>');
            echo("</td></tr>\n");
            foreach($assignmentIds as $resource_link_id) {
                $id = (string) $resource_link_id;
                $place = isset($places[$id]) ? $places[$id] : CourseBadges::placement($lessons, $id);
                $graded = $place['graded'];
                $score = 0;
                if ( $graded && isset($allgrades[$resource_link_id]) ) {
                    $score = 100*$allgrades[$resource_link_id];
                }
                $progress = intval($score*100);
                $kind = 'danger';
                if ( $progress < 5 ) $progress = 5;
                if ( $progress > 5 ) $kind = 'warning';
                if ( $progress > 50 ) $kind = 'info';
                if ( $progress >= 100 ) $kind = 'success';

                echo('<tr><td>');

                $href = $lessons->lessonsHome();
                if ( ! empty($place['anchor']) ) {
                    $href = $lessons->lessonsHome() . '/' . urlencode($place['anchor']);
                }

                $badge_title = $place['title'];

                echo('<a href="'.htmlspecialchars($href).'">');
                echo('<i class="fa fa-square-o text-info" aria-hidden="true" style="label label-success; padding-right: 5px;"></i>');
                echo(htmlspecialchars($badge_title)."</a>\n");
                echo('</td><td style="width: 30%; min-width: 200px;">');
                echo('<a href="'.htmlspecialchars($href).'">');
                echo('<span class="sr-only">'.htmlspecialchars($badge_title).': '.$progress.'% '.__('complete').'</span>');
                echo('<div class="progress" role="progressbar" aria-valuenow="'.$progress.'" aria-valuemin="0" aria-valuemax="100" aria-label="'.htmlspecialchars($badge_title).': '.$progress.' percent">');
                echo('<div class="progress-bar progress-bar-'.$kind.'" style="width: '.$progress.'%"></div>');
                echo('</div>');
                echo('</a>');
                echo("</td></tr>\n");
            }
        }
        echo('</tbody></table>'."\n");
?>
  </div>
  <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
<p></p>
<?php
    if ( count($awarded) < 1 ) {
        echo("<p>No badges have been awarded yet.</p>");
    } else if ( ! ReqScope::isLoggedIn() || ! ReqScope::currentContextId() ) {
        echo("<p>You must be logged in to see your badges.</p>\n");
    } else {
        $filesRequired = false;
        foreach ( $awarded as $badge ) {
            $badgeImage = (isset($badge->image) && is_string($badge->image)) ? $badge->image : '';
            if ( ! CourseBadges::isPublicImage($badgeImage) ) {
                $filesRequired = true;
                break;
            }
        }
        $configError = BadgeJson::checkBadgeConfig($filesRequired);
        if ( $configError !== false ) {
            echo('<div class="alert alert-warning">'.$configError.'</div>');
        } else {
            echo("<ul style=\"list-style: none;\">\n");
            foreach($awarded as $badge) {
                echo("<li><p>");
                $code = basename($badge->image,'.png');
                $decrypted = ReqScope::loggedInUserId().':'.$code.':'.ReqScope::currentContextId();
                $encrypted = bin2hex(AesOpenSSL::encrypt($decrypted, $CFG->badge_encrypt_password));
                $published_guid = BadgeService::getMintedGuidIfExists(
                    ReqScope::loggedInUserId(),
                    ReqScope::currentContextId(),
                    $code
                );
                $assert_url = $published_guid !== null
                    ? $CFG->wwwroot . '/assertions/' . rawurlencode($published_guid) . '.html'
                    : $CFG->wwwroot . '/assertions/' . $encrypted . '.html';
                $thumb = CourseBadges::isPublicImage($badge->image)
                    ? CourseBadges::displayUrl($badge, ReqScope::currentContextId())
                    : '';
                if ( $thumb === '' ) {
                    $thumb = $CFG->wwwroot.'/badges/images/'.$encrypted.'.png';
                }
                echo('<a href="'.htmlspecialchars($assert_url).'" target="_blank" rel="noopener noreferrer" aria-label="'.__('View badge assertion, opens in new window').'">');
                echo('<img src="'.htmlspecialchars($thumb).'" width="90" alt="'.htmlspecialchars($badge->title).'"></a>');
                echo(htmlspecialchars($badge->title));
                echo("</p></li>\n");
            }
            echo("</ul>\n");
            echo("<p>These badges contain the official Open Badge metadata.  You can download the badge and\n");
            echo("put it on your own server, or add the badge to a \"badge packpack\".  You could validate the badge\n");
            echo("using <a href=\"http://www.dr-chuck.com/obi-sample/\" target=\"_blank\" rel=\"noopener noreferrer\">A simple badge validator</a>.\n");
            echo("</p>\n");
        }
    }
?>
</div>
<?php
        if ( $supporter_ui ) {
            \Tsugi\UI\Supporter::renderRenew($CFG);
        }
        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);
    }

}

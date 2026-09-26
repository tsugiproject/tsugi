<?php

namespace Tsugi\Controllers;

use Tsugi\Crypt\AesOpenSSL;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Tsugi\Core\LTIX;
use Tsugi\Core\Manifest;
use Tsugi\Grades\GradeUtil;
use Tsugi\Services\Badges\BadgeService;
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
        echo('<table class="table table-striped table-hover "><tbody>'."\n");
        foreach($lessons->lessons->badges as $badge) {
            $threshold = $badge->threshold;
            $count = 0;
            $total = 0;
            $scores = array();
            foreach($badge->assignments as $resource_link_id) {
                $lti = $lessons->getLtiByRlid($resource_link_id);
                $graded = LessonsService::ltiLaunchIsGraded($lti);
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
            if ( $progress >= $threshold*100 ) {
                $kind = 'success';
                $awarded[] = $badge;
            }
            echo('<tr><td class="info">');
            if ( ! isset($CFG->badge_url) ) {
                echo('<i class="fa fa-certificate" aria-hidden="true" style="padding-right: 5px;"></i>');
            } else {
                $image = $CFG->badge_url . '/' . $badge->image;
                echo('<img src="'.htmlspecialchars($image).'" alt="'.htmlspecialchars($badge->title).'" style="width: 4rem;"/> ');
            }
            echo(htmlspecialchars($badge->title));
            echo('</td><td class="info" style="width: 30%; min-width: 200px;">');
            echo('<div class="progress" role="progressbar" aria-valuenow="'.$progress.'" aria-valuemin="0" aria-valuemax="100" aria-label="'.htmlspecialchars($badge->title).': '.$progress.' percent">');
            echo('<div class="progress-bar progress-bar-'.$kind.'" style="width: '.$progress.'%"></div>');
            echo('</div>');
            echo("</td></tr>\n");
            foreach($badge->assignments as $resource_link_id) {
                $lti = $lessons->getLtiByRlid($resource_link_id);
                $graded = LessonsService::ltiLaunchIsGraded($lti);
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
                $module = $lessons->getModuleByRlid($resource_link_id);

                echo('<tr><td>');

                $href= "Missing ". $resource_link_id;
                if ($module != null )  $href = $lessons->lessonsHome() . '/' . urlencode($module->anchor);

                $badge_title = "Missing ". $resource_link_id;
                if ( $lti != null ) $badge_title = $lti->title;

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
        // Check badge configuration before attempting to encrypt
        if ( !isset($CFG->badge_encrypt_password) || empty($CFG->badge_encrypt_password) || $CFG->badge_encrypt_password === false ||
             !isset($CFG->badge_path) || empty($CFG->badge_path) ||
             !isset($CFG->badge_url) || empty($CFG->badge_url) ||
             !isset($CFG->badge_assert_salt) || empty($CFG->badge_assert_salt) || $CFG->badge_assert_salt === false ) {
            echo("<div class=\"alert alert-warning\">\n");
            echo("<h3>Badge Configuration Required</h3>\n");
            echo("<p>The following badge configuration parameters are missing or not set in your <code>config.php</code>:</p>\n");
            echo("<ul>\n");
            if ( !isset($CFG->badge_encrypt_password) || empty($CFG->badge_encrypt_password) || $CFG->badge_encrypt_password === false ) {
                echo("<li><code>\$CFG->badge_encrypt_password</code></li>\n");
            }
            if ( !isset($CFG->badge_assert_salt) || empty($CFG->badge_assert_salt) || $CFG->badge_assert_salt === false ) {
                echo("<li><code>\$CFG->badge_assert_salt</code></li>\n");
            }
            if ( !isset($CFG->badge_path) || empty($CFG->badge_path) ) {
                echo("<li><code>\$CFG->badge_path</code></li>\n");
            }
            if ( !isset($CFG->badge_url) || empty($CFG->badge_url) ) {
                echo("<li><code>\$CFG->badge_url</code></li>\n");
            }
            echo("</ul>\n");
            echo("<h4>How to Configure Badges</h4>\n");
            echo("<p>To enable badge functionality, add the following to your <code>config.php</code> file:</p>\n");
            echo("<pre>\n");
            echo("// Badge generation settings - once you set these values to something\n");
            echo("// other than false and start issuing badges - don't change these or\n");
            echo("// existing badge images that have been downloaded from the system\n");
            echo("// will be invalidated.\n");
            echo("\$CFG->badge_encrypt_password = \"somethinglongwithhex387438758974987\";\n");
            echo("\$CFG->badge_assert_salt = \"mediumlengthhexstring\";\n");
            echo("\n");
            echo("// This folder contains the badge images\n");
            echo("\$CFG->badge_path = \$CFG->dirroot . '/../bimages';\n");
            echo("\$CFG->badge_url = \$CFG->apphome . '/bimages';\n");
            echo("</pre>\n");
            echo("<p>For more information, see <code>config-dist.php</code> in your Tsugi installation.</p>\n");
            echo("</div>\n");
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
                echo('<a href="'.htmlspecialchars($assert_url).'" target="_blank" rel="noopener noreferrer" aria-label="'.__('View badge assertion, opens in new window').'">');
                echo('<img src="'.htmlspecialchars($CFG->wwwroot.'/badges/images/'.$encrypted.'.png').'" width="90" alt="'.htmlspecialchars($badge->title).'"></a>');
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

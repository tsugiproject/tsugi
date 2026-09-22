<?php

namespace Tsugi\Controllers;

use Tsugi\Lumen\Controller;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use \Tsugi\Util\U;

class Analytics extends Controller {

    const ROUTE = '/analytics';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, function (Request $request) use ($app) {
            return Analytics::getAnalytics($app);
        });
        $app->router->get($prefix.'/', function (Request $request) use ($app) {
            return Analytics::getAnalytics($app);
        });
    }

    public static function getAnalytics(Application $app)
    {
        global $CFG, $OUTPUT;
        $tsugi = $app['tsugi'];
        // echo("<pre>\n");var_dump($tsugi);
        if ( !isset($tsugi->user) ) {
            $app->tsugiFlashError(__('You are not logged in.'));
            $redirect_path = isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;
            return new RedirectResponse($redirect_path);
        }

        $analytics_url = U::addSession($CFG->wwwroot."/api/analytics");

        $menu = new \Tsugi\UI\MenuSet();
        $menu->addLeft(__('Back'), 'index.php');
        $OUTPUT->buffer = false;
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav($menu);
        $OUTPUT->flashMessages();
        ?>
        <main class="container" id="main-content">
            <h1><?= __('Analytics') ?></h1>
            <?= self::graphBody() ?>
        </main>
        <?php
        $OUTPUT->footerStart();
        echo(self::graphScript($analytics_url));
        $OUTPUT->footerEnd();
        return;
    }

    /**
     * Emit a properly styled analytics button.
     *
     * This is just the button, using the signal icon. Wrap in a
     * span or div tag if you want to move it around.
     */
    public static function button($right = false)
    {
        global $LINK;
        if ( $right ) echo('<span style="position: fixed; right: 10px; top: 5px;">');
        echo('<button onclick="showModal(\''.__('Analytics').' '.htmlentities($LINK->title).'\',\'analytics_div\'); return false;" type="button" class="btn btn-default" aria-label="'.htmlspecialchars(__('Analytics').' '.$LINK->title).'">');
        echo('<span class="glyphicon glyphicon-signal" aria-hidden="true"></span></button>'."\n");
        if ( $right ) echo('</span>');
    }

    /**
     * Chart markup for the analytics modal or page.
     */
    public static function graphBody()
    {
        $x = <<<EOF
<div id="analytics-error" class="alert alert-danger" role="alert" style="display: none;"></div>
<div id="chart_div" style="width: 100%; height: 400px;" role="img" aria-label="Analytics chart showing launch activity over time"></div>
EOF;
        return $x;
    }

    /**
     * Chart script. $json is the view-model URL the browser fetches.
     */
    public static function graphScript($json)
    {
        $x = <<<EOF
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    function showAnalyticsError(msg) {
        var el = document.getElementById('analytics-error');
        if (el) { el.textContent = msg; el.style.display = ''; } else { alert(msg); }
    }
    $.getJSON('$json', function (x) {
        if ( typeof x == 'undefined' || typeof x.rows == 'undefined' ) {
            showAnalyticsError('No analytics data');
            return;
        }
        var rows = Array();
        var j = 0;
        for(var i=0; i<x.rows.length;i++) {
            rows[j++] = [new Date(x.rows[i][0]*1000), 0];
            rows[j++] = [new Date(x.rows[i][0]*1000), x.rows[i][1]];
            rows[j++] = [new Date((x.rows[i][0]+x.width)*1000), x.rows[i][1]];
            rows[j++] = [new Date((x.rows[i][0]+x.width)*1000), 0];
        }

        google.charts.load('current', {'packages':['annotatedtimeline']});
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('datetime', 'Date');
            data.addColumn('number', 'Launches');
            data.addRows(rows);

            var chart = new google.visualization.AnnotationChart(document.getElementById('chart_div'));

            var options = {
                displayAnnotations: true,
                fill: 50,
                max: (x.max*1.10),
                thickness: 2
            };

            chart.draw(data, options);
        }
    })
    .fail(function() { showAnalyticsError("Error retrieving analytics data"); });
</script>
EOF;
        return $x;
    }

}

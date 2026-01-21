<?php

namespace Tsugi\UI;

/**
 * Our class to show analytics
 */
class Analytics {

    /**
      * Emit a properly styled "settings" button
      *
      * This is just the button, using the pencil icon.  Wrap in a
      * span or div tag if you want to move it around
      */
    public static function button($right = false)
    {
        global $LINK;
        if ( $right ) echo('<span style="position: fixed; right: 10px; top: 5px;">');
        echo('<button onclick="showModal(\''.__('Analytics').' '.htmlentities($LINK->title).'\',\'analytics_div\'); return false;" type="button" class="btn btn-default">');
        echo('<span class="glyphicon glyphicon-signal"></span></button>'."\n");
        if ( $right ) echo('</span>');
    }

    /**
     * Output a Graph
     */
    public static function graphBody()
    {
        $x = <<<EOF
<div id="chart_div" style="width: 100%; height: 400px;"></div>
EOF;
        return $x;
    }

    /**
     * Output a Graph
     */
    public static function graphScript($json)
    {
        $x = <<<EOF
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    $.getJSON('$json', function (x) {
        if ( typeof x == 'undefined' || typeof x.rows == 'undefined' ) {
            alert('No analytics data');
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
    .fail(function() { alert("Error retrieving analytics data"); });
</script>
EOF;
        return $x;
    }

}

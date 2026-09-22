<?php

if (!function_exists('__')) {
    function __($str) { return $str; }
}

use Tsugi\Controllers\Analytics;
use Tsugi\Event\Entry;
use Tsugi\Services\Analytics\AnalyticsService;
use Tsugi\UI\Analytics as AnalyticsUi;

class AnalyticsServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testLegacyUiAnalyticsExtendsTheService()
    {
        $this->assertTrue(is_subclass_of(AnalyticsUi::class, AnalyticsService::class));
    }

    public function testLegacyUiAnalyticsForwardsChartMarkup()
    {
        $url = 'https://example.test/api/analytics?x=1';
        $this->assertSame(Analytics::graphBody(), AnalyticsUi::graphBody());
        $this->assertStringContainsString('id="chart_div"', AnalyticsUi::graphBody());
        $this->assertSame(Analytics::graphScript($url), AnalyticsUi::graphScript($url));
        $this->assertStringContainsString($url, AnalyticsUi::graphScript($url));
    }

    public function testLegacyUiAnalyticsButton()
    {
        global $LINK;
        $LINK = new \stdClass();
        $LINK->title = "Instructor's Quiz";
        ob_start();
        AnalyticsUi::button(true);
        $html = ob_get_clean();
        $this->assertStringContainsString('analytics_div', $html);
        $this->assertStringContainsString('Quiz', $html);
        $this->assertStringContainsString('\u0027', $html);
        $this->assertStringNotContainsString("Instructor's Quiz", $html);
    }

    public function testViewModelForLinkReadsActivity()
    {
        global $CFG, $PDOX;
        $savedCfg = isset($CFG) ? $CFG : null;
        $savedPdox = isset($PDOX) ? $PDOX : null;

        $source = new Entry(9000, 900);
        $source->click(9000);
        $source->total = 4;

        $CFG = new \stdClass();
        $CFG->dbprefix = 'tsugi_';
        $PDOX = new class($source) {
            public $sql;
            public $values;
            private $source;
            public function __construct($source) { $this->source = $source; }
            public function rowDie($sql, $values) {
                $this->sql = $sql;
                $this->values = $values;
                return array(
                    'link_count' => $this->source->total,
                    'activity' => $this->source->serialize(),
                );
            }
        };

        try {
            $model = AnalyticsService::viewModelForLink(17);
            $this->assertStringContainsString('tsugi_lti_link_activity', $PDOX->sql);
            $this->assertSame(17, $PDOX->values[':link_id']);
            $this->assertSame(1, $model->n);
            $this->assertSame(1, $model->max);
        } finally {
            $CFG = $savedCfg;
            $PDOX = $savedPdox;
        }
    }

    public function testLegacyUiAnalyticsViewModelWhenMissing()
    {
        global $CFG, $PDOX;
        $savedCfg = isset($CFG) ? $CFG : null;
        $savedPdox = isset($PDOX) ? $PDOX : null;
        $CFG = new \stdClass();
        $CFG->dbprefix = '';
        $PDOX = new class {
            public function rowDie($sql, $values) {
                return false;
            }
        };
        try {
            $model = AnalyticsUi::viewModelForLink(3);
            $this->assertSame(0, $model->n);
            $this->assertSame(array(), $model->rows);
        } finally {
            $CFG = $savedCfg;
            $PDOX = $savedPdox;
        }
    }
}

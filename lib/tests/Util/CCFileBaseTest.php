<?php

require_once "src/Util/CCFileBase.php";

use Tsugi\Util\CCFileBase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for Common Cartridge course-local URL expand/canonicalize.
 */
class CCFileBaseTest extends \PHPUnit\Framework\TestCase
{
    const BASE = 'https://lms.example.com/courses/12';

    private function href($url) {
        return '<p><a href="'.$url.'">Week 2</a></p>';
    }

    private function attr($html, $name = 'href') {
        if ( ! preg_match('/\b'.preg_quote($name, '/').'\s*=\s*"([^"]*)"/i', $html, $m) ) {
            $this->fail('Expected '.$name.' attribute in: '.$html);
        }
        return $m[1];
    }

    public function testCanonicalTokenExpands() {
        $html = $this->href('$IMS-CC-FILEBASE$pages/week2');
        $out = CCFileBase::expand($html, self::BASE);
        $this->assertEquals(self::BASE.'/pages/week2', $this->attr($out));
    }

    public function testEdTechTokenRecognizedOnInput() {
        $html = $this->href('$1EdTech-CC-FILEBASE$pages/week2');
        $out = CCFileBase::expand($html, self::BASE);
        $this->assertEquals(self::BASE.'/pages/week2', $this->attr($out));
    }

    public function testUnderscoreTokenRecognizedOnInput() {
        $html = $this->href('$IMS_CC_FILEBASE$pages/week2');
        $out = CCFileBase::expand($html, self::BASE);
        $this->assertEquals(self::BASE.'/pages/week2', $this->attr($out));
    }

    public function testCurrentCourseAbsoluteCanonicalizes() {
        $html = $this->href(self::BASE.'/pages/foo');
        $out = CCFileBase::canonicalize($html, self::BASE);
        $this->assertEquals('$IMS-CC-FILEBASE$pages/foo', $this->attr($out));
    }

    public function testExternalHttpsUntouched() {
        $html = $this->href('https://www.example.com/');
        $this->assertEquals($html, CCFileBase::canonicalize($html, self::BASE));
        $this->assertEquals($html, CCFileBase::expand($html, self::BASE));
    }

    public function testFragmentOnlyUntouched() {
        $html = $this->href('#section');
        $this->assertEquals($html, CCFileBase::canonicalize($html, self::BASE));
        $this->assertEquals($html, CCFileBase::expand($html, self::BASE));
    }

    public function testQueryAndFragmentSurviveRoundTrip() {
        $canonical = $this->href('$IMS-CC-FILEBASE$pages/foo?x=1&amp;y=2#bar');
        $expanded = CCFileBase::expand($canonical, self::BASE);
        $this->assertEquals(self::BASE.'/pages/foo?x=1&y=2#bar', html_entity_decode($this->attr($expanded)));
        $again = CCFileBase::canonicalize($expanded, self::BASE);
        $this->assertEquals('$IMS-CC-FILEBASE$pages/foo?x=1&y=2#bar', html_entity_decode($this->attr($again)));
    }

    public function testDatabaseExpandCanonicalizePreservesSemantics() {
        $db = '<p><a href="$IMS-CC-FILEBASE$pages/foo">Foo</a>'
            .'<img src="$IMS-CC-FILEBASE$files/images/example.png">'
            .'<a href="https://www.example.com/">External</a>'
            .'<a href="#top">Top</a></p>';
        $expanded = CCFileBase::expand($db, self::BASE);
        $canonical = CCFileBase::canonicalize($expanded, self::BASE);
        $this->assertEquals(self::BASE.'/pages/foo', $this->attr($expanded));
        $this->assertEquals(self::BASE.'/files/images/example.png', $this->attr($expanded, 'src'));
        $this->assertEquals('$IMS-CC-FILEBASE$pages/foo', $this->attr($canonical));
        $this->assertEquals('$IMS-CC-FILEBASE$files/images/example.png', $this->attr($canonical, 'src'));
        $this->assertStringContainsString('https://www.example.com/', $canonical);
        $this->assertStringContainsString('href="#top"', $canonical);
    }

    public function testRepeatedCanonicalizeIsIdempotent() {
        $html = $this->href(self::BASE.'/pages/foo');
        $once = CCFileBase::canonicalize($html, self::BASE);
        $twice = CCFileBase::canonicalize($once, self::BASE);
        $this->assertEquals($once, $twice);
        $this->assertEquals('$IMS-CC-FILEBASE$pages/foo', $this->attr($twice));
    }

    public function testRepeatedExpandDoesNotCorruptExpandedUrls() {
        $html = $this->href('$IMS-CC-FILEBASE$pages/foo');
        $once = CCFileBase::expand($html, self::BASE);
        $twice = CCFileBase::expand($once, self::BASE);
        $this->assertEquals($once, $twice);
        $this->assertEquals(self::BASE.'/pages/foo', $this->attr($twice));
    }

    public function testPathAbsoluteCanonicalizes() {
        $html = $this->href('/courses/12/pages/foo');
        $out = CCFileBase::canonicalize($html, self::BASE);
        $this->assertEquals('$IMS-CC-FILEBASE$pages/foo', $this->attr($out));
    }

    public function testHistoricalCourseSingularPathExpands() {
        $html = $this->href('https://lms.example.com/course/12/pages/foo');
        $out = CCFileBase::expand($html, self::BASE);
        $this->assertEquals(self::BASE.'/pages/foo', $this->attr($out));
    }

    public function testTokenAliasCanonicalizesToHistoricalToken() {
        $html = $this->href('$1EdTech-CC-FILEBASE$files/download/abc');
        $out = CCFileBase::canonicalize($html, self::BASE);
        $this->assertEquals('$IMS-CC-FILEBASE$files/download/abc', $this->attr($out));
    }

    public function testMailtoAndTelUntouched() {
        $mail = $this->href('mailto:user@example.com');
        $tel = $this->href('tel:+15555550100');
        $this->assertEquals($mail, CCFileBase::canonicalize($mail, self::BASE));
        $this->assertEquals($tel, CCFileBase::canonicalize($tel, self::BASE));
    }

    public function testSameHostNonCourseUntouched() {
        $html = $this->href('https://lms.example.com/about');
        $this->assertEquals($html, CCFileBase::canonicalize($html, self::BASE));
    }

    public function testSameHostLoginUntouched() {
        $html = $this->href('https://lms.example.com/courses/12/login');
        $this->assertEquals($html, CCFileBase::canonicalize($html, self::BASE, array('pages', 'files')));
    }

    public function testRelativeDotDotUntouched() {
        $html = $this->href('../other/page');
        $this->assertEquals($html, CCFileBase::canonicalize($html, self::BASE));
        $this->assertEquals($html, CCFileBase::expand($html, self::BASE));
    }

    public function testProtocolRelativeSameCourseCanonicalizes() {
        $html = $this->href('//lms.example.com/courses/12/pages/foo');
        $out = CCFileBase::canonicalize($html, self::BASE);
        $this->assertEquals('$IMS-CC-FILEBASE$pages/foo', $this->attr($out));
    }

    public function testSrcsetRewritesCourseUrlsOnly() {
        $html = '<img srcset="https://www.example.com/a.png 1x, https://lms.example.com/courses/12/files/a.png 2x">';
        $out = CCFileBase::canonicalize($html, self::BASE);
        $this->assertEquals('https://www.example.com/a.png 1x, $IMS-CC-FILEBASE$files/a.png 2x', $this->attr($out, 'srcset'));
    }

    public function testCourseBaseUrlCombinesOriginAndPrefix() {
        $this->assertEquals(
            'https://lms.example.com/courses/12',
            CCFileBase::courseBaseUrl('/courses/12', 'https://lms.example.com')
        );
        $this->assertEquals(
            'https://www.py4e.com/tsugi',
            CCFileBase::courseBaseUrl('', 'https://www.py4e.com/tsugi')
        );
        $this->assertEquals(
            'https://www.py4e.com/tsugi/courses/2',
            CCFileBase::courseBaseUrl('/tsugi/courses/2', 'https://www.py4e.com/tsugi')
        );
        $this->assertEquals(
            'https://www.py4e.com/tsugi/courses/2',
            CCFileBase::courseBaseUrl('/courses/2', 'https://www.py4e.com/tsugi')
        );
    }

    public function testHttpHttpsSchemeMismatchStillCanonicalizes() {
        $html = $this->href('http://lms.example.com/courses/12/pages/foo');
        $out = CCFileBase::canonicalize($html, self::BASE);
        $this->assertEquals('$IMS-CC-FILEBASE$pages/foo', $this->attr($out));
    }

    public function testRootMountedCourseDoesNotRewriteWithoutPrefixes() {
        $base = 'https://www.py4e.com';
        $html = $this->href('https://www.py4e.com/pages/week2');
        $this->assertEquals($html, CCFileBase::canonicalize($html, $base));
        $about = $this->href('https://www.py4e.com/about');
        $this->assertEquals($about, CCFileBase::canonicalize($about, $base));
    }

    public function testRootMountedCourseRewritesWhenCallerPassesPrefixes() {
        $base = 'https://www.py4e.com';
        $html = $this->href('https://www.py4e.com/pages/week2');
        $out = CCFileBase::canonicalize($html, $base, array('pages'));
        $this->assertEquals('$IMS-CC-FILEBASE$pages/week2', $this->attr($out));

        $other = $this->href('https://www.py4e.com/about');
        $this->assertEquals($other, CCFileBase::canonicalize($other, $base, array('pages')));
    }

    public function testJavascriptSchemeUntouched() {
        $html = $this->href('javascript:void(0)');
        $this->assertEquals($html, CCFileBase::canonicalize($html, self::BASE));
    }

    public function testOptionalSlashAfterToken() {
        $html = $this->href('$IMS-CC-FILEBASE$/pages/week2');
        $out = CCFileBase::expand($html, self::BASE);
        $this->assertEquals(self::BASE.'/pages/week2', $this->attr($out));
    }

    #[DataProvider('messySlashProvider')]
    public function testMessySlashesNormalizeOnRewrite($input, $description) {
        $canonical = CCFileBase::rewriteUrl($input, self::BASE, 'canonicalize');
        $expanded = CCFileBase::rewriteUrl($input, self::BASE, 'expand');
        $this->assertEquals('$IMS-CC-FILEBASE$pages/week2', $canonical, $description.' (canonicalize)');
        $this->assertEquals(self::BASE.'/pages/week2', $expanded, $description.' (expand)');
        $this->assertPathHasNoDoubleSlash($canonical);
        $this->assertPathHasNoDoubleSlash($expanded);
        $this->assertStringNotContainsString('$IMS-CC-FILEBASE$/', $canonical);
    }

    public static function messySlashProvider() {
        $base = 'https://lms.example.com/courses/12';
        return [
            'token, no slash (canonical form)' => ['$IMS-CC-FILEBASE$pages/week2', 'canonical token'],
            'token with one slash' => ['$IMS-CC-FILEBASE$/pages/week2', 'slash after token'],
            'token with two slashes' => ['$IMS-CC-FILEBASE$//pages/week2', 'double slash after token'],
            'token with three slashes' => ['$IMS-CC-FILEBASE$///pages/week2', 'triple slash after token'],
            'double slash in remainder' => ['$IMS-CC-FILEBASE$pages//week2', 'double slash in path'],
            'slash plus double in remainder' => ['$IMS-CC-FILEBASE$/pages//week2', 'slash after token and in path'],
            'absolute with trailing slash on base join' => [$base.'/pages/week2', 'normal absolute'],
            'absolute double slash after course base' => [$base.'//pages/week2', 'double slash after course base'],
            'absolute triple slash after course base' => [$base.'///pages/week2', 'triple slash after course base'],
            'absolute double slash in folder' => [$base.'/pages//week2', 'double slash in folder'],
            'path-absolute double after course' => ['/courses/12//pages/week2', 'path-absolute double after course'],
            'path-absolute double in folder' => ['/courses/12/pages//week2', 'path-absolute double in folder'],
            'edtech token with extra slashes' => ['$1EdTech-CC-FILEBASE$//pages/week2', '1EdTech with extra slashes'],
        ];
    }

    public function testHtmlRoundTripDoesNotIntroduceDoubleSlashes() {
        $inputs = array(
            '$IMS-CC-FILEBASE$//pages/week2',
            '$IMS-CC-FILEBASE$/pages//week2',
            self::BASE.'//pages/week2',
            '/courses/12//pages/week2',
        );
        foreach ( $inputs as $url ) {
            $html = $this->href($url);
            $expanded = CCFileBase::expand($html, self::BASE);
            $canonical = CCFileBase::canonicalize($expanded, self::BASE);
            $again = CCFileBase::expand($canonical, self::BASE);
            $stored = $this->attr($canonical);
            $live = $this->attr($expanded);
            $this->assertEquals('$IMS-CC-FILEBASE$pages/week2', $stored, 'DB form for '.$url);
            $this->assertEquals(self::BASE.'/pages/week2', $live, 'expanded form for '.$url);
            $this->assertEquals($live, $this->attr($again));
            $this->assertPathHasNoDoubleSlash($stored);
            $this->assertPathHasNoDoubleSlash($live);
        }
    }

    public function testCourseBaseTrailingSlashDoesNotDoubleOnExpand() {
        $html = $this->href('$IMS-CC-FILEBASE$pages/week2');
        $out = CCFileBase::expand($html, self::BASE.'/');
        $this->assertEquals(self::BASE.'/pages/week2', $this->attr($out));
        $this->assertPathHasNoDoubleSlash($this->attr($out));
    }

    public function testCourseBaseUrlCollapsesDoubleSlashesInPrefix() {
        $this->assertEquals(
            'https://lms.example.com/courses/12',
            CCFileBase::courseBaseUrl('/courses//12/', 'https://lms.example.com/')
        );
        $this->assertEquals(
            'https://lms.example.com/courses/12',
            CCFileBase::courseBaseUrl('/courses/12', 'https://lms.example.com//')
        );
    }

    public function testQueryHttpUrlDoesNotGetPathSlashesCollapsed() {
        $html = $this->href('$IMS-CC-FILEBASE$pages/foo?next=https://www.example.com/a');
        $out = CCFileBase::expand($html, self::BASE);
        $this->assertEquals(self::BASE.'/pages/foo?next=https://www.example.com/a', $this->attr($out));
        $back = CCFileBase::canonicalize($out, self::BASE);
        $this->assertEquals('$IMS-CC-FILEBASE$pages/foo?next=https://www.example.com/a', $this->attr($back));
    }

    /**
     * Path portion must never contain //, and the token must never be followed by /.
     */
    private function assertPathHasNoDoubleSlash($url) {
        $this->assertStringNotContainsString('$IMS-CC-FILEBASE$/', $url, 'token must not be followed by a slash: '.$url);
        $path = $url;
        if ( str_starts_with($path, '$IMS-CC-FILEBASE$') ) {
            $path = substr($path, strlen('$IMS-CC-FILEBASE$'));
        } else {
            $path = preg_replace('#^[a-z][a-z0-9+.-]*://#i', '', $path);
            $slash = strpos($path, '/');
            $path = ($slash === false) ? '' : substr($path, $slash);
        }
        $qpos = strpos($path, '?');
        if ( $qpos !== false ) {
            $path = substr($path, 0, $qpos);
        }
        $hpos = strpos($path, '#');
        if ( $hpos !== false ) {
            $path = substr($path, 0, $hpos);
        }
        $this->assertStringNotContainsString('//', $path, 'double slash in path of '.$url);
    }
}

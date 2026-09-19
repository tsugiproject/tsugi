<?php

require_once "src/Core/ContextImages.php";
require_once "src/Config/ConfigInfo.php";

use \Tsugi\Core\ContextImages;

class ContextImagesTest extends \PHPUnit\Framework\TestCase
{
    public function testSpecHeroAndIcon()
    {
        $hero = ContextImages::spec(ContextImages::KIND_HERO);
        $this->assertSame(1280, $hero['width']);
        $this->assertSame(720, $hero['height']);
        $this->assertSame(16 / 9, $hero['width'] / $hero['height']);
        $this->assertLessThanOrEqual(204800, $hero['max_bytes']);

        $icon = ContextImages::spec(ContextImages::KIND_ICON);
        $this->assertSame(512, $icon['width']);
        $this->assertSame(512, $icon['height']);
        $this->assertSame($icon['width'], $icon['height']);

        $this->assertNull(ContextImages::spec('nope'));
        $this->assertTrue(ContextImages::isKind('hero'));
        $this->assertTrue(ContextImages::isKind('icon'));
        $this->assertFalse(ContextImages::isKind('card'));
    }

    public function testCoverCropRectWiderSource()
    {
        $r = ContextImages::coverCropRect(2000, 1000, 1280, 720);
        $this->assertSame(1000, $r['sh']);
        $this->assertSame(0, $r['sy']);
        $this->assertGreaterThan(0, $r['sw']);
        $this->assertLessThan(2000, $r['sw']);
        $this->assertEqualsWithDelta(16 / 9, $r['sw'] / $r['sh'], 0.02);
    }

    public function testCoverCropRectTallerSource()
    {
        $r = ContextImages::coverCropRect(800, 1200, 512, 512);
        $this->assertSame(800, $r['sw']);
        $this->assertSame(0, $r['sx']);
        $this->assertSame(800, $r['sh']);
        $this->assertGreaterThan(0, $r['sy']);
    }

    public function testConstructJpegHeroFromPng()
    {
        $path = $this->writePng(1600, 900, [20, 80, 160]);
        $out = ContextImages::constructJpeg($path, ContextImages::KIND_HERO);
        @unlink($path);
        $this->assertIsArray($out, is_string($out) ? $out : 'expected array');
        $this->assertSame('image/jpeg', $out['mime']);
        $this->assertSame(1280, $out['width']);
        $this->assertSame(720, $out['height']);
        $this->assertLessThanOrEqual(ContextImages::HERO_MAX_BYTES, strlen($out['bytes']));
        $info = getimagesizefromstring($out['bytes']);
        $this->assertSame(IMAGETYPE_JPEG, $info[2]);
        $this->assertSame(1280, $info[0]);
        $this->assertSame(720, $info[1]);
    }

    public function testConstructJpegIconFromPortrait()
    {
        $path = $this->writePng(600, 900, [200, 40, 40]);
        $out = ContextImages::constructJpeg($path, ContextImages::KIND_ICON);
        @unlink($path);
        $this->assertIsArray($out, is_string($out) ? $out : 'expected array');
        $this->assertSame(512, $out['width']);
        $this->assertSame(512, $out['height']);
        $this->assertLessThanOrEqual(ContextImages::ICON_MAX_BYTES, strlen($out['bytes']));
    }

    public function testConstructJpegRejectsTinyImage()
    {
        $path = $this->writePng(40, 40, [0, 0, 0]);
        $out = ContextImages::constructJpeg($path, ContextImages::KIND_ICON);
        @unlink($path);
        $this->assertIsString($out);
        $this->assertStringContainsString('too small', $out);
    }

    public function testConstructJpegRejectsUnknownKind()
    {
        $path = $this->writePng(800, 800, [1, 2, 3]);
        $out = ContextImages::constructJpeg($path, 'banner');
        @unlink($path);
        $this->assertSame('Unknown image kind.', $out);
    }

    public function testUrlIncludesVersion()
    {
        global $CFG;
        $original = $CFG;
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->wwwroot = 'http://localhost/tsugi';
        $url = ContextImages::url(42, 'icon', '2026-09-18 01:02:03');
        $CFG = $original;
        $this->assertSame(
            'http://localhost/tsugi/courses/42/image/icon?v=' . rawurlencode('2026-09-18 01:02:03'),
            $url
        );
    }

    public function testServedUrlEmptyWithoutBytes()
    {
        global $CFG;
        $original = $CFG;
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->wwwroot = 'http://localhost/tsugi';
        $this->assertSame('', ContextImages::servedUrl(42, 'icon', 0, '2026-09-18 01:02:03'));
        $this->assertSame('', ContextImages::servedUrl(0, 'hero', 99, '2026-09-18 01:02:03'));
        $got = ContextImages::servedUrl(42, 'hero', 99, '2026-09-18 01:02:03');
        $CFG = $original;
        $this->assertSame(
            'http://localhost/tsugi/courses/42/image/hero?v=' . rawurlencode('2026-09-18 01:02:03'),
            $got
        );
    }

    public function testHeroPlaceholderSeedIsStableAndDiverse()
    {
        $a = ContextImages::heroPlaceholderSeed(35);
        $again = ContextImages::heroPlaceholderSeed(35);
        $b = ContextImages::heroPlaceholderSeed(36);
        $this->assertSame($a, $again);
        $this->assertSame(35, $a['id']);
        $this->assertGreaterThanOrEqual(0, $a['palette']);
        $this->assertLessThan(12, $a['palette']);
        $this->assertGreaterThanOrEqual(0, $a['variant']);
        $this->assertLessThan(8, $a['variant']);
        $this->assertNotSame($a, $b);
        $this->assertNotSame(
            ContextImages::heroPlaceholderSvg(1),
            ContextImages::heroPlaceholderSvg(2)
        );
    }

    public function testHeroPlaceholderSvgHasUniqueIdsAndNoTitle()
    {
        $svg = ContextImages::heroPlaceholderSvg(35);
        $other = ContextImages::heroPlaceholderSvg(99);
        $this->assertStringStartsWith('<svg ', $svg);
        $this->assertStringContainsString('tsugi-ph-35-', $svg);
        $this->assertStringNotContainsString('<text', $svg);
        $this->assertNotSame($svg, $other);
        $this->assertSame($svg, ContextImages::heroPlaceholderSvg(35));
    }

    /**
     * @param array{0:int,1:int,2:int} $rgb
     */
    private function writePng($w, $h, $rgb) {
        $im = imagecreatetruecolor($w, $h);
        $c = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
        imagefilledrectangle($im, 0, 0, $w, $h, $c);
        $path = tempnam(sys_get_temp_dir(), 'ctximg');
        imagepng($im, $path);
        return $path;
    }
}

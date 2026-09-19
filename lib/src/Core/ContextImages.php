<?php

namespace Tsugi\Core;

use \Tsugi\Util\U;

/**
 * 1-1 course branding images on lti_context (hero 16:9 + square icon).
 *
 * Bytes live in context_images, not dataroot / blob_file. List queries must
 * not SELECT the MEDIUMBLOB columns; serve each kind from its own endpoint.
 * Browser JS constructs JPEG to these specs; PHP re-constructs and verifies.
 */
class ContextImages {

    const KIND_HERO = 'hero';
    const KIND_ICON = 'icon';

    const MIME = 'image/jpeg';

    const HERO_WIDTH = 1280;
    const HERO_HEIGHT = 720;
    const HERO_MAX_BYTES = 204800; // 200KB
    const HERO_MIN_EDGE = 360;

    const ICON_WIDTH = 512;
    const ICON_HEIGHT = 512;
    const ICON_MAX_BYTES = 51200; // 50KB
    const ICON_MIN_EDGE = 128;

    /**
     * @return array<string, mixed>|null
     */
    public static function spec($kind) {
        if ( $kind === self::KIND_HERO ) {
            return array(
                'kind' => self::KIND_HERO,
                'width' => self::HERO_WIDTH,
                'height' => self::HERO_HEIGHT,
                'max_bytes' => self::HERO_MAX_BYTES,
                'min_edge' => self::HERO_MIN_EDGE,
                'label' => '16×9 course image',
            );
        }
        if ( $kind === self::KIND_ICON ) {
            return array(
                'kind' => self::KIND_ICON,
                'width' => self::ICON_WIDTH,
                'height' => self::ICON_HEIGHT,
                'max_bytes' => self::ICON_MAX_BYTES,
                'min_edge' => self::ICON_MIN_EDGE,
                'label' => 'Square course icon',
            );
        }
        return null;
    }

    public static function isKind($kind) {
        return $kind === self::KIND_HERO || $kind === self::KIND_ICON;
    }

    /**
     * Cover-crop PNG/JPEG to the kind's exact size and encode JPEG under max bytes.
     *
     * @return array{bytes:string,mime:string,width:int,height:int}|string Error message
     */
    public static function constructJpeg($path, $kind) {
        $spec = self::spec($kind);
        if ( $spec === null ) {
            return 'Unknown image kind.';
        }
        if ( ! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg') ) {
            return 'Image processing is not available on this server.';
        }
        if ( ! is_string($path) || $path === '' || ! is_readable($path) ) {
            return 'Could not read the uploaded file.';
        }
        $size = @filesize($path);
        if ( $size === false || $size < 24 ) {
            return 'Uploaded file is empty.';
        }
        // Hard cap on inbound bytes (browser should already be under this).
        if ( $size > 8 * 1024 * 1024 ) {
            return 'Uploaded file is too large.';
        }

        $info = @getimagesize($path);
        if ( ! is_array($info) || ! isset($info[0], $info[1], $info[2]) ) {
            return 'Files must be PNG or JPEG images.';
        }
        $srcW = (int) $info[0];
        $srcH = (int) $info[1];
        $type = (int) $info[2];
        if ( $type !== IMAGETYPE_JPEG && $type !== IMAGETYPE_PNG ) {
            return 'Files must be PNG or JPEG images.';
        }
        if ( $srcW < 1 || $srcH < 1 ) {
            return 'Invalid image dimensions.';
        }
        if ( min($srcW, $srcH) < (int) $spec['min_edge'] ) {
            return 'Image is too small. Use at least '.$spec['min_edge'].' pixels on the short edge.';
        }

        $raw = @file_get_contents($path);
        if ( $raw === false || $raw === '' ) {
            return 'Could not read the uploaded file.';
        }
        $src = @imagecreatefromstring($raw);
        if ( $src === false ) {
            return 'Could not decode the image.';
        }

        $dstW = (int) $spec['width'];
        $dstH = (int) $spec['height'];
        $crop = self::coverCropRect($srcW, $srcH, $dstW, $dstH);
        $dst = imagecreatetruecolor($dstW, $dstH);
        if ( $dst === false ) {
            return 'Could not create the resized image.';
        }
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $white);
        imagecopyresampled(
            $dst, $src,
            0, 0,
            $crop['sx'], $crop['sy'],
            $dstW, $dstH,
            $crop['sw'], $crop['sh']
        );

        $encoded = self::jpegUnderLimit($dst, (int) $spec['max_bytes']);
        if ( ! is_string($encoded) || strncmp($encoded, "\xFF\xD8\xFF", 3) !== 0 ) {
            return is_string($encoded) ? $encoded : 'Failed to compress image.';
        }

        return array(
            'bytes' => $encoded,
            'mime' => self::MIME,
            'width' => $dstW,
            'height' => $dstH,
        );
    }

    /**
     * Source rectangle for CSS object-fit: cover into $dstW × $dstH.
     *
     * @return array{sx:int,sy:int,sw:int,sh:int}
     */
    public static function coverCropRect($srcW, $srcH, $dstW, $dstH) {
        $srcW = max(1, (int) $srcW);
        $srcH = max(1, (int) $srcH);
        $dstW = max(1, (int) $dstW);
        $dstH = max(1, (int) $dstH);
        $srcAspect = $srcW / $srcH;
        $dstAspect = $dstW / $dstH;
        if ( $srcAspect > $dstAspect ) {
            $sh = $srcH;
            $sw = (int) round($srcH * $dstAspect);
            if ( $sw < 1 ) {
                $sw = 1;
            }
            if ( $sw > $srcW ) {
                $sw = $srcW;
            }
            $sx = (int) floor(($srcW - $sw) / 2);
            $sy = 0;
        } else {
            $sw = $srcW;
            $sh = (int) round($srcW / $dstAspect);
            if ( $sh < 1 ) {
                $sh = 1;
            }
            if ( $sh > $srcH ) {
                $sh = $srcH;
            }
            $sx = 0;
            $sy = (int) floor(($srcH - $sh) / 2);
        }
        return array('sx' => $sx, 'sy' => $sy, 'sw' => $sw, 'sh' => $sh);
    }

    /**
     * @return string JPEG bytes, or error message
     */
    private static function jpegUnderLimit($im, $maxBytes) {
        $quality = 85;
        $minQuality = 40;
        $last = '';
        while ( $quality >= $minQuality ) {
            ob_start();
            $ok = imagejpeg($im, null, $quality);
            $bytes = ob_get_clean();
            if ( ! $ok || ! is_string($bytes) || $bytes === '' ) {
                return 'Failed to compress image.';
            }
            $last = $bytes;
            if ( strlen($bytes) <= $maxBytes ) {
                return $bytes;
            }
            $quality -= 10;
        }
        if ( is_string($last) && $last !== '' && strlen($last) <= $maxBytes ) {
            return $last;
        }
        return 'Unable to compress image below size limit.';
    }

    /**
     * Metadata only (no BLOBs). Missing row → empty flags.
     *
     * @return array<string, mixed>
     */
    public static function metadata($context_id) {
        global $CFG, $PDOX;
        $cid = (int) $context_id;
        $empty = array(
            'context_id' => $cid,
            'has_hero' => false,
            'hero_bytes' => 0,
            'hero_updated_at' => null,
            'has_icon' => false,
            'icon_bytes' => 0,
            'icon_updated_at' => null,
        );
        if ( $cid < 1 || $PDOX === null || $PDOX === false ) {
            return $empty;
        }
        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie(
            "SELECT hero_bytes, hero_updated_at, icon_bytes, icon_updated_at
             FROM {$p}context_images WHERE context_id = :CID",
            array(':CID' => $cid)
        );
        if ( ! is_array($row) ) {
            return $empty;
        }
        $heroBytes = isset($row['hero_bytes']) ? (int) $row['hero_bytes'] : 0;
        $iconBytes = isset($row['icon_bytes']) ? (int) $row['icon_bytes'] : 0;
        return array(
            'context_id' => $cid,
            'has_hero' => $heroBytes > 0,
            'hero_bytes' => $heroBytes,
            'hero_updated_at' => $row['hero_updated_at'] ?? null,
            'has_icon' => $iconBytes > 0,
            'icon_bytes' => $iconBytes,
            'icon_updated_at' => $row['icon_updated_at'] ?? null,
        );
    }

    /**
     * One kind's bytes. Does not SELECT the other BLOB.
     *
     * @return array{bytes:string,mime:string,updated_at:?string}|null
     */
    public static function blob($context_id, $kind) {
        global $CFG, $PDOX;
        $cid = (int) $context_id;
        if ( $cid < 1 || ! self::isKind($kind) ) {
            return null;
        }
        if ( $PDOX === null || $PDOX === false ) {
            return null;
        }
        $col = $kind === self::KIND_HERO ? 'hero' : 'icon';
        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie(
            "SELECT {$col} AS content, {$col}_mime AS mime, {$col}_bytes AS nbytes, {$col}_updated_at AS updated_at
             FROM {$p}context_images WHERE context_id = :CID",
            array(':CID' => $cid)
        );
        if ( ! is_array($row) || ! isset($row['content']) || $row['content'] === null || $row['content'] === '' ) {
            return null;
        }
        $bytes = $row['content'];
        if ( ! is_string($bytes) ) {
            return null;
        }
        return array(
            'bytes' => $bytes,
            'mime' => isset($row['mime']) && is_string($row['mime']) && $row['mime'] !== '' ? $row['mime'] : self::MIME,
            'updated_at' => $row['updated_at'] ?? null,
        );
    }

    /**
     * Insert or update one kind. Leaves the other image alone.
     *
     * @return string|null Error message
     */
    public static function save($context_id, $kind, $bytes) {
        global $CFG, $PDOX;
        $cid = (int) $context_id;
        if ( $cid < 1 || ! self::isKind($kind) ) {
            return 'Unknown image kind.';
        }
        if ( ! is_string($bytes) || $bytes === '' ) {
            return 'Image data was empty.';
        }
        $spec = self::spec($kind);
        if ( strlen($bytes) > (int) $spec['max_bytes'] ) {
            return 'Image is too large after conversion.';
        }
        if ( $PDOX === null || $PDOX === false ) {
            return 'Database is not available.';
        }
        $p = $CFG->dbprefix;
        $col = $kind === self::KIND_HERO ? 'hero' : 'icon';
        $exists = $PDOX->rowDie(
            "SELECT context_id FROM {$p}context_images WHERE context_id = :CID",
            array(':CID' => $cid)
        );
        $len = strlen($bytes);
        if ( $exists ) {
            $sql = "UPDATE {$p}context_images
                SET {$col} = :BLOB, {$col}_mime = :MIME, {$col}_bytes = :LEN,
                    {$col}_updated_at = NOW(), updated_at = NOW()
                WHERE context_id = :CID";
        } else {
            $sql = "INSERT INTO {$p}context_images
                (context_id, {$col}, {$col}_mime, {$col}_bytes, {$col}_updated_at, created_at, updated_at)
                VALUES (:CID, :BLOB, :MIME, :LEN, NOW(), NOW(), NOW())";
        }
        $stmt = $PDOX->prepare($sql);
        $stmt->bindValue(':CID', $cid, \PDO::PARAM_INT);
        $stmt->bindValue(':MIME', self::MIME, \PDO::PARAM_STR);
        $stmt->bindValue(':LEN', $len, \PDO::PARAM_INT);
        $fp = fopen('php://temp', 'r+');
        if ( $fp === false ) {
            return 'Could not buffer the image.';
        }
        fwrite($fp, $bytes);
        rewind($fp);
        $stmt->bindParam(':BLOB', $fp, \PDO::PARAM_LOB);
        $ok = $stmt->execute();
        fclose($fp);
        if ( ! $ok ) {
            return 'Failed to save the image.';
        }
        return null;
    }

    /**
     * @return string|null Error message
     */
    public static function clear($context_id, $kind) {
        global $CFG, $PDOX;
        $cid = (int) $context_id;
        if ( $cid < 1 || ! self::isKind($kind) ) {
            return 'Unknown image kind.';
        }
        if ( $PDOX === null || $PDOX === false ) {
            return 'Database is not available.';
        }
        $p = $CFG->dbprefix;
        $col = $kind === self::KIND_HERO ? 'hero' : 'icon';
        $PDOX->queryDie(
            "UPDATE {$p}context_images
             SET {$col} = NULL, {$col}_mime = NULL, {$col}_bytes = NULL,
                 {$col}_updated_at = NULL, updated_at = NOW()
             WHERE context_id = :CID",
            array(':CID' => $cid)
        );
        return null;
    }

    /**
     * Public URL for a served image (versioned for browser cache).
     */
    public static function url($context_id, $kind, $updated_at = null) {
        global $CFG;
        $cid = (int) $context_id;
        if ( $cid < 1 || ! self::isKind($kind) ) {
            return '';
        }
        $base = rtrim((string) $CFG->wwwroot, '/') . '/courses/' . $cid . '/image/' . $kind;
        if ( is_string($updated_at) && $updated_at !== '' ) {
            $base .= (strpos($base, '?') === false ? '?' : '&') . 'v=' . rawurlencode($updated_at);
        }
        return $base;
    }

    /**
     * Versioned, session-safe URL when this kind has stored bytes.
     */
    public static function servedUrl($context_id, $kind, $nbytes, $updated_at = null) {
        if ( (int) $nbytes < 1 ) {
            return '';
        }
        $url = self::url($context_id, $kind, $updated_at);
        return $url === '' ? '' : U::addSession($url);
    }

    /**
     * Stable seed so each course gets its own placeholder look.
     *
     * @return array{id:int,palette:int,variant:int,a:int,b:int,c:int}
     */
    public static function heroPlaceholderSeed($context_id) {
        $cid = (int) $context_id;
        $raw = md5('tsugi-course-hero-'.$cid);
        return array(
            'id' => $cid,
            'palette' => hexdec(substr($raw, 0, 4)) % 12,
            'variant' => hexdec(substr($raw, 4, 4)) % 8,
            'a' => hexdec(substr($raw, 8, 4)),
            'b' => hexdec(substr($raw, 12, 4)),
            'c' => hexdec(substr($raw, 16, 4)),
        );
    }

    /**
     * Decorative 16×9 SVG for an empty hero. Title is HTML overlay, not in the SVG.
     */
    public static function heroPlaceholderSvg($context_id) {
        $seed = self::heroPlaceholderSeed($context_id);
        $palettes = self::heroPalettes();
        $p = $palettes[$seed['palette']];
        $uid = 'tsugi-ph-'.$seed['id'].'-'.$seed['palette'].'-'.$seed['variant'];
        $art = self::heroPlaceholderArt($uid, $p, $seed);
        return '<svg class="tsugi-course-card-placeholder-art" viewBox="0 0 160 90" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" aria-hidden="true">'.$art.'</svg>';
    }

    /**
     * @return array<int, array{0:string,1:string,2:string}>
     */
    private static function heroPalettes() {
        return array(
            array('#0d3b4c', '#1a7a72', '#e6c98a'),
            array('#1b2a4a', '#3e6d8a', '#f0c27b'),
            array('#3a1c14', '#c45c28', '#ead4b0'),
            array('#143328', '#2f7a58', '#d7e3c0'),
            array('#3a1730', '#b04a6a', '#f3d2c9'),
            array('#222c3c', '#5b7c99', '#d4b06a'),
            array('#0e3048', '#2b7d9a', '#7ec8c0'),
            array('#2a163f', '#6a3ea0', '#e0b34d'),
            array('#1f2e1c', '#6b8f4e', '#e4d5a8'),
            array('#4a1f1a', '#d97b3d', '#f2e1c0'),
            array('#12344d', '#4f6cad', '#c9d6e8'),
            array('#2d2420', '#8a6a4b', '#e8dcc8'),
        );
    }

    /**
     * @param array{0:string,1:string,2:string} $p
     * @param array{id:int,palette:int,variant:int,a:int,b:int,c:int} $seed
     */
    private static function heroPlaceholderArt($uid, array $p, array $seed) {
        $c0 = $p[0];
        $c1 = $p[1];
        $c2 = $p[2];
        $a = $seed['a'];
        $b = $seed['b'];
        $c = $seed['c'];
        $angle = 18 + ($a % 50);
        $x1 = 10 + ($a % 90);
        $y1 = 4 + ($b % 40);
        $x2 = 70 + ($b % 70);
        $y2 = 30 + ($c % 45);
        $r1 = 28 + ($a % 36);
        $r2 = 18 + ($b % 28);

        $defs = '<defs>'
            .'<linearGradient id="'.$uid.'-bg" x1="0" y1="0" x2="1" y2="1">'
            .'<stop offset="0%" stop-color="'.$c0.'"/>'
            .'<stop offset="100%" stop-color="'.$c1.'"/>'
            .'</linearGradient>'
            .'<linearGradient id="'.$uid.'-scrim" x1="0" y1="0" x2="0" y2="1">'
            .'<stop offset="35%" stop-color="#000" stop-opacity="0"/>'
            .'<stop offset="100%" stop-color="#000" stop-opacity="0.55"/>'
            .'</linearGradient>'
            .'</defs>';
        $bg = '<rect width="160" height="90" fill="url(#'.$uid.'-bg)"/>';
        $scrim = '<rect width="160" height="90" fill="url(#'.$uid.'-scrim)"/>';

        $variant = (int) $seed['variant'];
        if ( $variant === 1 ) {
            $art = '<path d="M0 58 C 30 44, 50 72, 80 58 S 130 44, 160 60 L 160 90 L 0 90 Z" fill="'.$c1.'" opacity="0.85"/>'
                .'<path d="M0 68 C 40 80, 70 52, 110 70 S 140 86, 160 72 L 160 90 L 0 90 Z" fill="'.$c2.'" opacity="0.55"/>';
        } elseif ( $variant === 2 ) {
            $art = '<polygon points="0,0 160,0 '.$x1.',90 0,90" fill="'.$c0.'"/>'
                .'<polygon points="160,0 160,90 0,90" fill="'.$c1.'" opacity="0.9"/>'
                .'<circle cx="'.$x2.'" cy="'.$y1.'" r="'.(12 + ($c % 18)).'" fill="'.$c2.'" opacity="0.8"/>';
        } elseif ( $variant === 3 ) {
            $cx = 20 + ($a % 120);
            $cy = 10 + ($b % 50);
            $art = '<circle cx="'.$cx.'" cy="'.$cy.'" r="70" fill="'.$c1.'" opacity="0.35"/>'
                .'<circle cx="'.$cx.'" cy="'.$cy.'" r="48" fill="'.$c0.'" opacity="0.25"/>'
                .'<circle cx="'.$cx.'" cy="'.$cy.'" r="28" fill="'.$c2.'" opacity="0.7"/>'
                .'<circle cx="'.$cx.'" cy="'.$cy.'" r="10" fill="'.$c0.'" opacity="0.9"/>';
        } elseif ( $variant === 4 ) {
            $tiles = '';
            for ( $i = 0; $i < 12; $i++ ) {
                $tx = ($i % 4) * 42 + (($a + $i * 7) % 8);
                $ty = (int) floor($i / 4) * 32 + (($b + $i * 5) % 6);
                $tw = 28 + (($c + $i) % 12);
                $th = 18 + (($a + $i * 3) % 10);
                $fill = ($i % 3 === 0) ? $c2 : (($i % 3 === 1) ? $c1 : $c0);
                $tiles .= '<rect x="'.$tx.'" y="'.$ty.'" width="'.$tw.'" height="'.$th.'" rx="4" fill="'.$fill.'" opacity="0.55"/>';
            }
            $art = $tiles;
        } elseif ( $variant === 5 ) {
            $art = '<ellipse cx="'.$x1.'" cy="'.$y1.'" rx="'.(40 + ($a % 30)).'" ry="'.(22 + ($b % 18)).'" fill="'.$c2.'" opacity="0.55"/>'
                .'<ellipse cx="'.$x2.'" cy="'.$y2.'" rx="'.(34 + ($b % 24)).'" ry="'.(26 + ($c % 16)).'" fill="'.$c1.'" opacity="0.7"/>'
                .'<circle cx="'.(140 - ($c % 40)).'" cy="'.(12 + ($a % 20)).'" r="'.(10 + ($b % 12)).'" fill="'.$c2.'" opacity="0.85"/>';
        } elseif ( $variant === 6 ) {
            $chev = '';
            $step = 18;
            for ( $i = -2; $i < 12; $i++ ) {
                $x = $i * $step;
                $chev .= '<polyline points="'.$x.',5 '.($x + 12).',45 '.($x + 24).',5" fill="none" stroke="'.$c2.'" stroke-width="6" opacity="0.35"/>';
            }
            $art = '<g transform="rotate('.($angle - 30).' 80 45)">'.$chev.'</g>';
        } elseif ( $variant === 7 ) {
            $art = '<rect y="52" width="160" height="38" fill="'.$c0.'"/>'
                .'<circle cx="'.(30 + ($a % 100)).'" cy="'.(22 + ($b % 16)).'" r="'.(14 + ($c % 10)).'" fill="'.$c2.'" opacity="0.9"/>'
                .'<path d="M0 58 L 40 48 L 80 62 L 120 44 L 160 56 L 160 90 L 0 90 Z" fill="'.$c1.'"/>';
        } else {
            $art = '<circle cx="'.$x1.'" cy="'.$y1.'" r="'.$r1.'" fill="'.$c2.'" opacity="0.45"/>'
                .'<circle cx="'.$x2.'" cy="'.$y2.'" r="'.$r2.'" fill="'.$c1.'" opacity="0.55"/>'
                .'<circle cx="'.(20 + ($c % 30)).'" cy="'.(60 + ($a % 20)).'" r="'.(16 + ($b % 14)).'" fill="'.$c2.'" opacity="0.35"/>';
        }

        return $defs.$bg.$art.$scrim;
    }
}

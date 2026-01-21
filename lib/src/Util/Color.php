<?php

namespace Tsugi\Util;

// https://gist.github.com/sebdesign/a65cc39e3bcd81201609e6a8087a83b3

class Color
{
    /**
     * Allow array pr parameter form for RGB
     */
    public static function fixRgb($r, $g=0, $b=0) {
        if ( is_array($r) ) return $r;
        if ( is_string($r) ) return Color::rgb($r);
        return [$r, $g, $b];
    }

    /**
     * Convert a hex color to RGB.
     * 
     * @param  string $hex  #BADA55
     * @return array        [186, 218, 85]
     */
    public static function rgb ($hex)
    {
        return sscanf($hex, "#%02x%02x%02x");
    }

    /**
     * Convert a RGB to hex
     * 
     * @param  string $hex  #BADA55
     * @return array        [186, 218, 85]
     */
    public static function hex ($r, $g = false, $b=false)
    {
        if ( is_array($r) ) {
            $b = $r[2];
            $g = $r[1];
            $r = $r[0];
        }
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }

    /**
     * Calculate the relative luminance of an RGB color.
     * 
     * @param  int $r
     * @param  int $g
     * @param  int $b
     * @return float
     */
    public static function luminance ($r, $g=false, $b=false) {
        if ( is_array($r) ) {
            $b = $r[2];
            $g = $r[1];
            $r = $r[0];
        }
        return 0.2126 * pow($r/255, 2.2) +
               0.7152 * pow($g/255, 2.2) +
               0.0722 * pow($b/255, 2.2);
    }

    /**
     * Calculate the relative luminance of two colors.
     * 
     * @param  string $C1 hex color
     * @param  string $C2 hex color
     * @return float
     */
    public static function relativeLuminance ($C1, $C2) {
        $L1 = self::luminance(...self::rgb($C1));
        $L2 = self::luminance(...self::rgb($C2));

        if ($L1 > $L2) {
            return ($L1 + 0.05) / ($L2 + 0.05);
        } else {
            return ($L2 + 0.05) / ($L1 + 0.05);
        }
    }
}

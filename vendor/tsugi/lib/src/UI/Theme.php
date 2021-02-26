<?php
    
namespace Tsugi\UI;
    
use \Tsugi\Util\U;
use \Tsugi\Util\Color;
    
class Theme {
    
    /**
     * Get default theme values from a configured theme or the actual defaults
     */
    public static function defaults($theme=false) {
        if ( ! is_array($theme) ) $theme = array();
        $primary = U::get($theme, 'primary', '#0D47A1');
        $secondary = U::get($theme, 'secondary', '#EEEEEE');
        return array(
            "primary" => U::get($theme, 'primary', $primary),
            "primary-menu" => U::get($theme, 'primary-menu', $primary),
            "primary-border" => U::get($theme, 'primary-border', self::adjustBrightness($primary,-0.075)),
            "primary-darker" => U::get($theme, 'primary-darker', self::adjustBrightness($primary,-0.1)),
            "primary-darkest" => U::get($theme, 'primary-darkest', self::adjustBrightness($primary,-0.175)),
            'background-color' => U::get($theme, 'background-color', '#FFFFFF'),
            "secondary" => U::get($theme, 'secondary', $secondary),
            "secondary-menu" => U::get($theme, 'secondary-menu', $secondary),
            "text" => U::get($theme, 'text', '#111111'),
            "text-light" => U::get($theme, 'text-light', '#5E5E5E'),
            "font-family" => U::get($theme, 'font-family', 'sans-serif'),
            "font-size" => U::get($theme, 'font-size', '14px'),
        );
    }

    /**
    * From https://stackoverflow.com/questions/3512311/how-to-generate-lighter-darker-color-with-php
    *
    * Increases or decreases the brightness of a color by a percentage of the current brightness.
    *
    * @param   string  $hexCode        Supported formats: `#FFF`, `#FFFFFF`, `FFF`, `FFFFFF`
    * @param   float   $adjustPercent  A number between -1 and 1. E.g. 0.3 = 30% lighter; -0.4 = 40% darker.
    *
    * @return  string
    */
    private static function adjustBrightness($hexCode, $adjustPercent) {
        $hexCode = ltrim($hexCode, '#');

        if (strlen($hexCode) == 3) {
            $hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
        }

        $hexCode = array_map('hexdec', str_split($hexCode, 2));

        foreach ($hexCode as & $color) {
            $adjustableLimit = $adjustPercent < 0 ? $color : 255 - $color;
            $adjustAmount = ceil($adjustableLimit * $adjustPercent);

            $color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
        }

        return '#' . implode($hexCode);
    }

    /**
     * Take a color and move it along its luminance until it is halfway between
     * a given dark color and white 
     */
    public static function findLMidPointForHue($r, $g=false, $b=false, $dark=false) {
        // echo("findLMidPoint $r $g $b\n");
        if ( ! $dark ) $dark = "#000000";
        $rgb = Color::fixRgb($r, $g, $b);
        $hsl = self::rgbToHsl($rgb[0], $rgb[1], $rgb[2] );
        $h = $hsl[0];
        $s = $hsl[1];
        $l = $hsl[2];
        $lasttot = 100;  // > 21
        // TODO: Find closed form way to solve this problem
        for($l = 0.0; $l <= 1.0; $l += 0.01 ) {
            $rgb = self::hslToRgb( $h, $s, $l );
            // print_r($rgb);
            $hex = Color::hex($rgb);
            // Come down from white
            $relblack = Color::relativeLuminance($dark, $hex);
            $relwhite = Color::relativeLuminance("#FFFFFF", $hex);
            $reltot = $relblack + $relwhite;
            // echo("#000000 $h $s $l $hex $relblack $relwhite $reltot\n");
            if ( $reltot > $lasttot ) return ($hex);
            $lasttot = $reltot;
            // if ( $relblack > 10.5 ) { return $hex; }
        }
        return $hex;
    
    }
    
    /**
     * Take a color, move a pair of colors outwards towards white and black
     * until the colors are at least as far apart as $difference
     */
    public static function luminosityPair($difference, $r, $g=false, $b=false) {
        // echo("luminosityPair $difference $r $g $b\n");
        $rgb = Color::fixRgb($r, $g, $b);
        // print_r($rgb);
        $hex = Color::hex($rgb);
        $relblack = Color::relativeLuminance("#000000", $hex);
        $relwhite = Color::relativeLuminance("#FFFFFF", $hex);
        // print("w $relwhite b $relblack\n");
        $hsl = self::rgbToHsl($rgb[0], $rgb[1], $rgb[2] );
        // print_r($hsl);
        $h = $hsl[0];
        $s = $hsl[1];
        $l = $hsl[2];
        $updist = 1.0 - $l;
        $downdist = $l;
        $increment = 0.01;
        // TODO: Find closed form way to solve this problem
        for($d = 0.0; $d <= 1.0; $d += 0.01 ) {
            $downl =  $l - ($d * $downdist);
            $upl =  $l + ($d * $updist);
            $downrgb = self::hslToRgb( $h, $s, $downl );
            $uprgb = self::hslToRgb( $h, $s, $upl );
            // print_r($rgb);
            $uphex = Color::hex($uprgb);
            $downhex = Color::hex($downrgb);
            // Go up from black
            $rel = Color::relativeLuminance($downhex, $uphex);
            // echo("$downhex $uphex $rel\n");
            if ( $rel > $difference ) { return [$downhex, $uphex]; }
        }
    }
    
    public static function getLegacyTheme($tsugi_dark, $dark_mode) {
    
        $tsuginames = self::deriveTsugiColors($tsugi_dark);
        if ( $dark_mode ) {
            $tusgitolegacy = array(
                'tsugi-theme-light-text' => ['text', 'primary-darkest'],
                'tsugi-theme-light' => ['text-light', 'primary', 'secondary-menu'],
                'tsugi-theme-light-darker' => 'primary-darker',
                'tsugi-theme-light-accent' => 'primary-border',
                'tsugi-theme-dark' => 'primary-menu',
                'tsugi-theme-dark-background' => 'background-color',
            );
        } else {
            $tusgitolegacy = array(
                'tsugi-theme-dark-text' => ['text', 'text-menu', 'primary-darkest'],
                'tsugi-theme-dark' => ['primary', 'primary-menu', 'text-light'],
                'tsugi-theme-dark-darker' => 'primary-darker',
                'tsugi-theme-dark-accent' => 'primary-border',
                'tsugi-theme-light' => 'secondary',
                'tsugi-theme-light-background' => 'background-color',
            );
        }
    
        $legacy_theme = array();
        foreach($tsuginames as $name => $default) {
            $legacy = isset($tusgitolegacy[$name]) ? $tusgitolegacy[$name] : false;
            if ( ! $legacy ) continue;
            if ( is_string($legacy) ) {
                $legacy_theme[$legacy] = $default;
            } else {
                $legacies = $legacy;
                foreach($legacies as $legacy) {
                    $legacy_theme[$legacy] = $default;
                }
            }
        }
        return $legacy_theme;
    }
    
    /**
     * Derive a range of named colors from a single base color
     *
     * This color should have at least an 8.0 luminance ratio 
     * from white - if not - it will be moved to the point where
     * it does have an 8.0 luminance ratio from white.
     */
    public static function deriveTsugiColors($tsugi_dark) {
        $fromwhite = Color::relativeLuminance("#FFFFFF", $tsugi_dark);
        if ( $fromwhite < 8.0 ) {
            $mid = self::findLMidPointForHue($tsugi_dark);
        } else {
            $rgb = Color::fixRgb($tsugi_dark);
            $mid = self::findLMidPointForHue($rgb[0], $rgb[1], $rgb[2], $tsugi_dark);
        }
    
        $outerpair = self::luminosityPair(20.0, $mid);
        $innerpair = self::luminosityPair(7.0, $mid);
    
        $dark_outer_hsl = self::rgbToHsl($outerpair[0]);
        $dark_inner_hsl = self::rgbToHsl($innerpair[0]);
    
        $light_inner_hsl = self::rgbToHsl($innerpair[1]);
        $light_outer_hsl = self::rgbToHsl($outerpair[1]);
    
        $tsugi_dark_hsl = self::rgbToHsl($tsugi_dark);
    
        $ddelta = $dark_inner_hsl[2] - $dark_outer_hsl[2];
        $ldelta = $light_outer_hsl[2] - $light_inner_hsl[2];
    
        $hue = $dark_outer_hsl[0];
        $sat_dark = $dark_outer_hsl[1];
        $sat_light = $light_outer_hsl[1];
        $lightness_dark = $dark_outer_hsl[2];
        $lightness_light = $light_outer_hsl[2];
    
        if ( $fromwhite < 8.0 ) {
            $tsugi_dark = Color::hex(self::hslToRgb($hue, $sat_dark, $lightness_dark + ($ddelta * 0.6)));
            $tsugi_dark_hsl = self::rgbToHsl($tsugi_dark);
        }
    
        $lightness_darker = ($tsugi_dark_hsl[2] + $dark_outer_hsl[2]) / 2.0;
        $lightness_dark_accent = ($tsugi_dark_hsl[2] + $dark_inner_hsl[2]) / 2.0;
    
        $tsuginames = array(
            "tsugi-theme-dark-background" => $outerpair[0],
            "tsugi-theme-dark-text" =>  Color::hex(self::hslToRgb($hue, $sat_dark*0.5, $lightness_darker)),
            "tsugi-theme-dark-darker" => Color::hex(self::hslToRgb($hue, $sat_dark, $lightness_darker)),
            "tsugi-theme-dark" =>  $tsugi_dark,
            "tsugi-theme-dark-accent" => $innerpair[0],
            "tsugi-theme-mid" => $mid,
            "tsugi-theme-light-accent" => $innerpair[1],
            "tsugi-theme-light" => Color::hex(self::hslToRgb($hue, $sat_light, $lightness_light - ($ldelta * 0.6))),
            "tsugi-theme-light-lighter" => Color::hex(self::hslToRgb($hue, $sat_light, $lightness_light - ($ldelta * 0.3))),
            "tsugi-theme-light-text" => Color::hex(self::hslToRgb($hue, $sat_light*0.5, $lightness_light - ($ldelta * 0.3))),
            "tsugi-theme-light-background" => $outerpair[1],
        );
    
        return $tsuginames;
    }
    
    
    // https://gist.github.com/brandonheyer/5254516
    
    public static function rgbToHsl( $r, $g=false, $b=false ) {
        $rgb = Color::fixRgb($r, $g, $b);
        $r = $rgb[0];
        $g = $rgb[1];
        $b = $rgb[2];
    
    	$oldR = $r;
    	$oldG = $g;
    	$oldB = $b;
    
    	$r /= 255;
    	$g /= 255;
    	$b /= 255;
    
        $max = max( $r, $g, $b );
    	$min = min( $r, $g, $b );
    
    	$h;
    	$s;
    	$l = ( $max + $min ) / 2;
    	$d = $max - $min;
    
        	if( $d == 0 ){
            	$h = $s = 0; // achromatic
        	} else {
            	$s = $d / ( 1 - abs( 2 * $l - 1 ) );
    
    		switch( $max ){
    	            case $r:
    	            	$h = 60 * fmod( ( ( $g - $b ) / $d ), 6 ); 
                            if ($b > $g) {
    	                    $h += 360;
    	                }
    	                break;
    
    	            case $g: 
    	            	$h = 60 * ( ( $b - $r ) / $d + 2 ); 
    	            	break;
    
    	            case $b: 
    	            	$h = 60 * ( ( $r - $g ) / $d + 4 ); 
    	            	break;
    	        }			        	        
    	}
    
    	return array( round( $h, 2 ), round( $s, 2 ), round( $l, 2 ) );
    }
    
    // h: 0-360 s: 0-1 l: 0-1
    public static function hslToRgb( $h, $s=false, $l=false ){
        if ( is_array($h) ) {
            $s = $h[1];
            $l = $h[2];
            $h = $h[0];
        }
        $r; 
        $g; 
        $b;
    
    	$c = ( 1 - abs( 2 * $l - 1 ) ) * $s;
    	$x = $c * ( 1 - abs( fmod( ( $h / 60 ), 2 ) - 1 ) );
    	$m = $l - ( $c / 2 );
    
    	if ( $h < 60 ) {
    		$r = $c;
    		$g = $x;
    		$b = 0;
    	} else if ( $h < 120 ) {
    		$r = $x;
    		$g = $c;
    		$b = 0;			
    	} else if ( $h < 180 ) {
    		$r = 0;
    		$g = $c;
    		$b = $x;					
    	} else if ( $h < 240 ) {
    		$r = 0;
    		$g = $x;
    		$b = $c;
    	} else if ( $h < 300 ) {
    		$r = $x;
    		$g = 0;
    		$b = $c;
    	} else {
    		$r = $c;
    		$g = 0;
    		$b = $x;
    	}
    
    	$r = ( $r + $m ) * 255;
    	$g = ( $g + $m ) * 255;
    	$b = ( $b + $m  ) * 255;
    
        // Added by Chuck
        if ( $r < 0 ) $r = 0;
        if ( $g < 0 ) $g = 0;
        if ( $b < 0 ) $b = 0;
    
        return array( floor( $r ), floor( $g ), floor( $b ) );
    }

}

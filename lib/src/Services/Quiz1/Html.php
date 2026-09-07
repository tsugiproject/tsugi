<?php

namespace Tsugi\Services\Quiz1;

/**
 * Safe HTML for quiz prompts and answers, using HTMLPurifier like Discussions.
 */
class Html {

    /** @var \HTMLPurifier|null */
    private static $purifier = null;

    public static function purify($html) {
        if ( $html === null ) {
            return '';
        }
        $html = (string) $html;
        if ( $html === '' ) {
            return '';
        }
        return self::purifier()->purify($html);
    }

    public static function excerpt($html, $max = 80) {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim($text);
        if ( mb_strlen($text) <= $max ) {
            return $text;
        }
        return rtrim(mb_substr($text, 0, $max - 1)) . '…';
    }

    private static function purifier() {
        if ( self::$purifier === null ) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('Cache.DefinitionImpl', null);
            self::$purifier = new \HTMLPurifier($config);
        }
        return self::$purifier;
    }
}

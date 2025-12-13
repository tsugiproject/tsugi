<?php

namespace Tsugi\Core;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\MoFileLoader;


use \Tsugi\Util\U;

/**
 * Internationalization (i18n) utility class for translation support
 *
 * Provides translation functions similar to WordPress pattern.
 * Supports both legacy gettext and Symfony Translation component.
 */
class I18N {

    /**
     * Translate a message from the master domain
     *
     * @param string $message The message to translate
     * @return string The translated message
     */
    public static function _m($message) {
        return self::__($message, "master");
    }

    /**
     * Echo a translated message from the master domain
     *
     * @param string $message The message to translate and echo
     * @return void
     */
    public static function _me($message) {
        echo(self::_m($message));
    }

    /**
     * Echo a translated message from a textdomain
     *
     * @param string $message The message to translate and echo
     * @param string|false $textdomain The text domain to use, or false for current domain
     * @return void
     */
    public static function _e($message, $textdomain=false) {
        echo(self::__($message, $textdomain));
    }

    /**
     * Translate a message from a textdomain
     *
     * Pattern borrowed from WordPress. This is the core translation function.
     *
     * @param string $message The message to translate
     * @param string|false $textdomain The text domain to use, or false for current domain
     * @return string The translated message
     */
    public static function __($message, $textdomain=false) {
        global $CFG, $PDOX, $TSUGI_LOCALE, $TSUGI_LOCALE_RELOAD, $TSUGI_TRANSLATE;
    
        // If we have been asked to do some translation
        if ( isset($CFG->checktranslation) && $CFG->checktranslation && isset($PDOX) ) 
        {
            $string_sha = U::lti_sha256($message);
            $domain = $textdomain;
            if ( ! $domain ) $domain = textdomain(NULL);
            if ( $domain ) {
                $stmt = $PDOX->queryReturnError("INSERT INTO {$CFG->dbprefix}tsugi_string
                        (domain, string_text, string_sha256) VALUES ( :DOM, :TEXT, :SHA)
                        ON DUPLICATE KEY UPDATE updated_at = NOW()",
                    array(
                        ':DOM' => $domain,
                        ':TEXT' => $message,
                        ':SHA' => $string_sha
                    )
                );
            }
        }

        // Setup Text domain late...
        if ( $TSUGI_LOCALE_RELOAD ) {
            self::setupTextDomain();
        }

        if ( isset($TSUGI_TRANSLATE) && $TSUGI_TRANSLATE ) {
            $retval = $TSUGI_TRANSLATE->trans($message);
            // error_log("DOM=$TSUGI_LOCALE msg=$message trans=$retval");
            return $retval;
        }

        if ( ! function_exists('gettext')) return $message;
        if ( $textdomain === false ) {
            return gettext($message);
        } else {
            return dgettext($textdomain, $message);
        }
    }

    /**
     * Set the locale for the current user
     *
     * @param string|null $locale The locale to set (e.g., 'en_US', 'fr_FR'). If null, detects from HTTP headers
     * @return void
     */
    public static function setLocale($locale=null)
    {
        global $CFG, $TSUGI_LOCALE, $TSUGI_LOCALE_RELOAD;

        // No internationalization support
        if ( isset($CFG->legacytranslation) ) {
            if ( ! function_exists('bindtextdomain') ) return;
            if ( ! function_exists('textdomain') ) return;
        }

        if ( isset($CFG->legacytranslation) && $locale && 
            strpos($locale, 'UTF-8') === false ) $locale = $locale . '.UTF-8';

        if ( $locale === null && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ) {

            if ( class_exists('\Locale') ) {
                try {
                    // Symfony may implement a stub for this function that throws an exception
                    $locale = \Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
                } catch (exception $e) { }
            }
            if ($locale === null) { // Crude fallback if we can't use Locale::acceptFromHttp
                $pieces = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
                $locale = $pieces[0];
            }
        }

        if ( $locale === null && isset($CFG->fallbacklocale) && $CFG->fallbacklocale ) {
            $locale = $CFG->fallbacklocale;
        }

        if ( $locale === null ) return;

        $locale = str_replace('-','_',$locale);
        if ( isset($CFG->legacytranslation) ) {
            putenv('LC_ALL='.$locale);
            setlocale(LC_ALL, $locale);
        }
        // error_log("setLocale=$locale");
        if ( $TSUGI_LOCALE != $locale ) $TSUGI_LOCALE_RELOAD = true;
        $TSUGI_LOCALE = $locale;
    }

    /**
     * Set up the translation text domain
     *
     * Configures the translation system with master and domain-specific translation files.
     * Uses Symfony Translation component if available, falls back to legacy gettext.
     *
     * @return void
     */
    public static function setupTextDomain() {
        global $CFG, $TSUGI_LOCALE, $TSUGI_LOCALE_RELOAD, $TSUGI_TRANSLATE;

        $domain = $CFG->getScriptFolder();
        $folder = $CFG->getScriptPathFull()."/locale";

        // error_log("setupTextDomain($domain, $folder, $TSUGI_LOCALE, $TSUGI_LOCALE_RELOAD)");

        $TSUGI_LOCALE_RELOAD = false;
        $TSUGI_TRANSLATE = false;

        if ( isset($CFG->legacytranslation) && $CFG->legacytranslation ) {
            if (function_exists('bindtextdomain')) {
                bindtextdomain("master", $CFG->dirroot."/locale");
                bindtextdomain($domain, $folder);
            }
            if (function_exists('textdomain')) {
                textdomain($domain);
            }
            return;
        }

        if ( isset($TSUGI_LOCALE) && is_string($TSUGI_LOCALE) ) {
            $lang = substr($TSUGI_LOCALE, 0, 2);
            if ( $lang == '*' ) $lang = 'en';  // Symfony can't handle '*'
            // error_log("lang=$lang");
            $master_file = $CFG->dirroot."/locale/$lang/LC_MESSAGES/master.mo";
            $domain_file = $CFG->getScriptPathFull()."/locale/$lang/LC_MESSAGES/$domain.mo";
            $TSUGI_TRANSLATE = new Translator($lang);
            if ( file_exists($master_file) ) {
                $TSUGI_TRANSLATE->addLoader('master', new MoFileLoader());
                $TSUGI_TRANSLATE->addResource('master', $master_file, $lang);
            }

            if ( file_exists($domain_file) ) {
                $TSUGI_TRANSLATE->addLoader($domain, new MoFileLoader());
                $TSUGI_TRANSLATE->addResource($domain, $domain_file, $lang);
            }
        }

    }
}

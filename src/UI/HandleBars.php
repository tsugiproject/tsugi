<?php

namespace Tsugi\UI;

use Tsugi\Util\U;

/**
 * This is a class that suports the HandleBars use cases in Tsugi
 */

class HandleBars {

    /**
     * templateInclude - Include a handlebars template, dealing with i18n
     *
     * This is a normal handlebars template except we can ask for a translation
     * of text as follows:
     *
     *    ...
     *    {{__ 'Hello world' }}
     *    ...
     *
     * The i18n replacement will be handled in the server in the template.  Single
     * or Double quotes can be used.
     */
    public static function templateInclude($name) {
        if ( is_array($name) ) {
            foreach($name as $n) {
                self::templateInclude($n);
            }
            return;
        }
        echo('<script id="template-'.$name.'" type="text/x-handlebars-template">'."\n");
        $template = file_get_contents('templates/'.$name.'.hbs');
        echo(self::templateProcess($template));
        echo("</script>\n");
    }

    /**
     * templateProcess - Process a handlebars template, dealing with i18n
     *
     * This is a normal handlebars template except we can ask for a translation
     * of text as follows:
     *
     *    ...
     *    {{__ 'Hello world' }}
     *    ...
     *
     * The i18n replacement will be handled in the server in the template.  Single
     * or double quotes can be used.
     */
    public static function templateProcess($template) {
        $new = preg_replace_callback(
            '|{{__ *\'([^\']*)\' *}}|',
            function ($matches) {
                return __(htmlent_utf8(trim($matches[1])));
            },
            $template
        );
        $new = preg_replace_callback(
            '|{{__ *"([^"]*)" *}}|',
            function ($matches) {
                return __(htmlent_utf8(trim($matches[1])));
            },
            $new
        );
        $new = preg_replace_callback(
            '|{{>> *([^ ]*) *}}|',
            function ($matches) {
                $name = 'templates/'.trim($matches[1]);
                if ( file_exists($name) ) {
                    return file_get_contents($name);
                }
                return "Unable to open $name";
            },
            $new
        );
        return $new;
    }
}

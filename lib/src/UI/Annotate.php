<?php

namespace Tsugi\UI;

use \Tsugi\Util\U;

/**
 * Our class to handle annotation markup
 */
class Annotate {

    /**
     * Provide the header material to support annotations
     *
     * @return A string with the text to include
     */
    public static function header()
    {
        global $CFG;
        return '<link rel="stylesheet" href="'.$CFG->staticroot.'/js/annotator-full.1.2.10/annotator.min.css" />'.PHP_EOL;
    }

    /**
     * Define the function tsugiStartAnnotation(id) to start the annotations on a div
     *
     * @param $user_id The user+id for the annotations
     *
     * @return A string with the text to include
     */
    public static function footer($user_id)
    {
        global $CFG;

        $api_endpoint = $CFG->wwwroot . '/api/annotate/' . session_id() . ':' . $user_id;
        ob_start();
?>
<script src="<?= $CFG->staticroot ?>/js/annotator-full.1.2.10/annotator-full.min.js"></script>
<script type="text/javascript">
function tsugiStartAnnotation(id) {
    $(id).annotator()
    .annotator('setupPlugins', {} , {
        Auth: false,
        Tags: false,
        Filter: false,
        Store: {
           prefix: '<?= $api_endpoint ?>',
           loadFromSearch: false
        }
     } );
     console.log('Annotator started id='+id);
}
</script>
<?php
        $retval = ob_get_contents();
        ob_end_clean();
        return $retval;
    }
}

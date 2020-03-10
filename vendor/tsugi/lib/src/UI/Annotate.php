<?php

namespace Tsugi\UI;

use \Tsugi\Util\U;

/**
 * Our class to handle annotation markup
 */
class Annotate {

    /**
     * Provide the header material to support annotations
     */
    public static function header()
    {
        global $CFG;
        return '<link rel="stylesheet" href="'.$CFG->staticroot.'/js/annotator-full.1.2.10/annotator.min.css" />'.PHP_EOL;
    }

    /**
     * Define the function tsugiStartAnnotation(id) to start the annotations on a div
     *
     * @param $LAUNCH The current launch
     * @param $api_endpoint The id of the area to be annotated
     */
    public static function footer($LAUNCH, $api_endpoint=false)
    {
        global $CFG;

        if ( ! $api_endpoint ) {
            $pieces = U::rest_path();
            $api_endpoint = $CFG->wwwroot . '/api/annotate/' . session_id() . ':' . $LAUNCH->result->id;
        }
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
        return ob_get_contents();
    }
}

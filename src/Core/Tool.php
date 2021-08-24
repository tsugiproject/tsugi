<?php

namespace Tsugi\Core;

use \Tsugi\Util\U;
use \Tsugi\Util\PS;
use \Tsugi\UI\Output;
use \Tsugi\UI\HandleBars;
use \Tsugi\Core\I18N;

/**
 * Provide support for a Tsugi Tool
 */
class Tool {

    public $analytics = true;

    function __construct() {
        // TODO
    }

    public static function handleConfig()
    {
        global $CFG, $TSUGI_LAUNCH, $PDOX, $TSUGI_LOCALE;
        global $OUTPUT, $USER, $CONTEXT, $LINK, $RESULT, $ROSTER;

        // Check for a few special cases
        $rest_path = U::rest_path();
        if ( ! $rest_path ) return false;

        if ( file_exists('templates') && $rest_path->controller == 'load_templates') {
            self::sendTemplates();
            return true;
        }

        if ( file_exists('register.php') && $rest_path->controller == 'register.json') {
            $reg = self::loadRegistration();
            if ( is_string($reg) ) {
                OUTPUT::jsonError($reg);
                return true;
            }
            OUTPUT::headerJson();
            echo(json_encode($reg,JSON_PRETTY_PRINT));
            return true;
        }

        if ( file_exists('register.php') && $rest_path->controller == 'canvas-config.xml') {
            $reg = self::loadRegistration();
            if ( is_string($reg) ) {
                OUTPUT::xmlError($reg);
                return;
            }
            $xml = self::canvasToolRegistration($reg);
            OUTPUT::xmlOutput($xml);
            return true;
        }
        return false;
    }

    public function run()
    {
        global $CFG, $TSUGI_LAUNCH, $PDOX, $TSUGI_LOCALE;
        global $OUTPUT, $USER, $CONTEXT, $LINK, $RESULT, $ROSTER;

        // Check if this is a config request
        if ( self::handleConfig() ) return;

        // Make PHP paths pretty .../install => install.php
        $router = new \Tsugi\Util\FileRouter();
        $file = $router->fileCheck();
        if ( $file ) {
            require_once($file);
            return;
        }

        // Make a Tsugi Application
        $launch = \Tsugi\Core\LTIX::requireData();
        $app = new \Tsugi\Lumen\Application($launch);

        // Add some routes
        if ( $this->analytics ) {
            \Tsugi\Controllers\Analytics::routes($app);
        }

        $app->run();
    }

    public static function loadRegistration() {
        global $CFG;
        require_once('register.php');

        if ( ! isset($REGISTER_LTI) && isset($REGISTER_LTI2) ) $REGISTER_LTI = $REGISTER_LTI2;
        if ( ! isset($REGISTER_LTI) ) return "Error in register.php";
        if ( ! is_array($REGISTER_LTI) ) return "Error in register.php";

        if ( isset($REGISTER_LTI['name']) && isset($REGISTER_LTI['short_name']) &&
            isset($REGISTER_LTI['description']) ) {
            // We are happy
        } else {
            error_log("Missing required name, short_name, and description in ".$tool_folder);
            return "Missing required name, short_name, and description in ".$tool_folder;
        }

        $rest_path = U::rest_path();
        $url = U::get_base_url($CFG->wwwroot) . $rest_path->parent;
        if ( ! PS::s($url)->endsWith('/') ) $url .= '/';

        $REGISTER_LTI['url'] = $url;

        self::patchRegistration($REGISTER_LTI);

        $capability_offered = U::get($REGISTER_LTI, 'capability_offered');
        if ( ! $capability_offered ) $capability_offered = array();
        $capability_offered[] = 'OAuth.hmac-sha256';
        $capability_offered[] = 'OAuth.splitSecret';
        $capability_offered[] = 'basic-lti-launch-request';
        $REGISTER_LTI['capability_offered'] = $capability_offered;

        // If the tool renders no opinion, choose reasonable default
        $capability_required = U::get($REGISTER_LTI, 'capability_required');
        if ( ! $capability_required ) {
            $capability_required = array();
            $capability_required[] = 'User.id';
            $capability_required[] = 'Context.id';
            $capability_required[] = 'ResourceLink.id';
        }
        $REGISTER_LTI['capability_required'] = $capability_required;

        $messages = U::get($REGISTER_LTI, 'messages');
        $capability_desired = U::get($REGISTER_LTI, 'capability_desired');
        if ( ! $capability_desired ) $capability_desired = array();
        $capability_desired[] = 'Person.name.given';
        $capability_desires[] = 'Person.name.family';
        $capability_desired[] = 'Person.email.primary';
        $capability_desired[] = 'User.image';
        if ( ! $messages || ($messages && in_array('launch_grade', $messages)) ) {
            $capability_desired[] = 'Result.sourcedId';
            $capability_desired[] = 'Result.autocreate';
            $capability_desired[] = 'Result.url';
        }
        $REGISTER_LTI['capability_desired'] = $capability_desired;

        return $REGISTER_LTI;
    }

    public static function patchRegistration(&$tool) {
        global $CFG;

        $url = $tool['url'];

        if ( isset($CFG->privacy_url) && ! U::get($tool, 'privacy_url') ) {
            $tool['privacy_url'] = $CFG->privacy_url;
        }

        if ( isset($CFG->sla_url) && ! U::get($tool, 'sla_url') ) {
            $tool['sla_url'] = $CFG->sla_url;
        }

        if ( isset($CFG->key_url) && ! U::get($tool, 'key_url') ) {
            $tool['key_url'] = $CFG->key_url;
        }

        if ( ! U::get($tool, 'key_url') ) {
            $tool['key_url'] = $CFG->wwwroot . '/settings';
        }

        if ( ! U::get($tool, 'app_store_url') ) {
            $tool['app_store_url'] = $CFG->wwwroot . '/lti/store';
        }

        if ( ! U::get($tool, 'app_store_canvas_url') ) {
            $tool['app_store_canvas_url'] = $CFG->wwwroot . '/lti/store/canvas-config.xml';
        }

        // Make an icon URL
        $fa_icon = isset($tool['FontAwesome']) ? $tool['FontAwesome'] : false;
        if ( $fa_icon !== false ) {
            $tool['icon'] = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';
        }

        // The default
        if ( ! U::get($tool, 'messages') ) $tool['messages'] = array('launch');

        $fa_icon = U::get($tool,'FontAwesome');
        $icon = U::get($tool, 'icon');
        if ( ! $icon && $fa_icon && $CFG->fontawesome ) {
            $tool['icon'] = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';
        }

        $screen_shots = U::get($tool, 'screen_shots');
        if ( is_array($screen_shots) && count($screen_shots) > 0 ) {
            $new = array();
            foreach($screen_shots as $screen_shot ) {
                $ps = PS::s($screen_shot);
                if ( $ps->startsWith('http://') || $ps->startsWith('https://') ) {
                    $new[] = $url;
                } else {
                    $new[] = $url . '/' . $screen_shot;
                }
            }
            $tool['screen_shots'] = $new;
        }

    }

    public static function sendTemplates()
    {
        global $CFG;

        if ( ! $CFG->localhost() ) {
            Output::maxCacheHeader(3600*24); // 1 day
        }

        $rest_path = U::rest_path();

        if ( $rest_path->action && strlen($rest_path->action) > 0 ) {
            echo("<!-- Locale ".htmlentities($rest_path->action)." -->\n");
            I18N::setLocale($rest_path->action);
        } else {
            I18N::setLocale();
        }
        echo("<!-- td=".htmlentities(textdomain(null))." -->\n");

        $count = 0;
        foreach(glob('templates/*.hbs') as $name) {
            $count++;
            echo "<template id=\"webcomponents-template-" . basename($name, '.hbs') . "\"><div>\n";
            $template = file_get_contents($name);
            echo(\Tsugi\UI\HandleBars::templateProcess($template));
            echo("</div></template>\n");
        }

        if ( $count == 0 ) {
            echo("<!-- no templates found -->\n");
        } else {
// Make it so that polyfilled browsers can find templates
// But since the poly fill folds the templates into the base document,
// this will likely never be called since we find them through
// querySelector - see the logic in tsugiscripts.js tsugiHandlebarsRender()
// TODO: Probably remove this unless we find different polyfill behavior
?>
<script>
(function(window, document, undefined) {
    // Refers to the "importer", which is "out parent"
    var thatDoc = document;

    // Refers to the "importee", which is this document.
    var thisDoc =  (thatDoc._currentScript || thatDoc.currentScript).ownerDocument;

    // Make sure we chain if there was more than one import
    var recurse = window.HandleBarsTemplateFromImport;
    window.HandleBarsTemplateFromImport = function (temp) {
        var template = thisDoc.querySelector(temp);
        if ( template ) return template.content.firstElementChild.innerHTML;
        if ( recurse ) {
            console.log('Tool calling HandleBarsTemplateFromImport recursively...');
            console.log(recurse);
            return recurse(temp);
        }
        return;
    };
})(window, document);
</script>
<?php
        }
    }

/*
Best documentation on how it works is here:

https://www.eduappcenter.com/docs/extensions/content

I believe you already have a canvas account and you can install
a tool in any course you have or create. If you want to be able
to see it in action, you can install this tool:

http://lti-tool-provider.herokuapp.com/

it links to its xml config on that page, it is here:

http://lti-tool-provider.herokuapp.com/tool_config.xml

The trick in that xml is the block: <lticm:options name="editor_button">

That is what makes a button appear in the RCE. The other option of
interest to you there is selection for module items which is
'resource_selection'. We don't have an UI components to enable those placements.

These 2 pages show how to configure it in Canvas:

https://www.eduappcenter.com/docs/extensions/canvas_wysiwyg
https://www.eduappcenter.com/docs/extensions/canvas_link_selection

If you need more details let me know.

- Bracken

Note from: John Jonston - https://www.edu-apps.org/build_xml.html
*/

    public static function canvasToolRegistration($tool) {
        global $CFG;
        $pieces = parse_url($CFG->wwwroot);
        $domain = isset($pieces['host']) ? $pieces['host'] : false;
        $privacy_level = U::get($tool, 'privacy_level', 'public');

        $retval = '<?xml version="1.0" encoding="UTF-8"?>
<cartridge_basiclti_link xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0" xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0" xmlns:lticm="http://www.imsglobal.org/xsd/imslticm_v1p0" xmlns:lticp="http://www.imsglobal.org/xsd/imslticp_v1p0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticc_v1p0.xsd http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0p1.xsd http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd">
  <blti:title>'.htmlent_utf8(strip_tags($tool['name'])).'</blti:title>
  <blti:description>'.htmlent_utf8(strip_tags($tool['description'])).'</blti:description>
  <blti:launch_url>'.$tool['url'].'</blti:launch_url>
  <blti:custom>
    <lticm:property name="sub_canvas_account_id">$Canvas.account.id</lticm:property>
    <lticm:property name="sub_canvas_account_name">$Canvas.account.name</lticm:property>
    <lticm:property name="sub_canvas_account_sis_sourceId">$Canvas.account.sisSourceId</lticm:property>
    <lticm:property name="sub_canvas_api_domain">$Canvas.api.domain</lticm:property>
';
    $messages = U::get($tool, 'messages');
    if ( $messages && in_array('launch_grade', $messages) ) {
$retval .= '    <lticm:property name="sub_canvas_assignment_id">$Canvas.assignment.id</lticm:property>
    <lticm:property name="sub_canvas_assignment_points_possible">$Canvas.assignment.pointsPossible</lticm:property>
    <lticm:property name="sub_canvas_assignment_title">$Canvas.assignment.title</lticm:property>
';
    }
    $retval .= '    <lticm:property name="sub_canvas_course_id">$Canvas.course.id</lticm:property>
    <lticm:property name="sub_canvas_course_sis_source_id">$Canvas.course.sisSourceId</lticm:property>
    <lticm:property name="sub_canvas_enrollment_enrollment_state">$Canvas.enrollment.enrollmentState</lticm:property>
    <lticm:property name="sub_canvas_membership_concluded_roles">$Canvas.membership.concludedRoles</lticm:property>
    <lticm:property name="sub_canvas_membership_roles">$Canvas.membership.roles</lticm:property>
    <lticm:property name="sub_canvas_root_account.id">$Canvas.root_account.id</lticm:property>
    <lticm:property name="sub_canvas_root_account_sis_source_id">$Canvas.root_account.sisSourceId</lticm:property>
    <lticm:property name="sub_canvas_user_id">$Canvas.user.id</lticm:property>
    <lticm:property name="sub_canvas_user_login_id">$Canvas.user.loginId</lticm:property>
    <lticm:property name="sub_canvas_user_sis_source_id">$Canvas.user.sisSourceId</lticm:property>
    <lticm:property name="sub_canvas_caliper_url">$Caliper.url</lticm:property>
    <lticm:property name="person_address_timezone">$Person.address.timezone</lticm:property>
    <lticm:property name="person_email_primary">$Person.email.primary</lticm:property>
    <lticm:property name="person_name_family">$Person.name.family</lticm:property>
    <lticm:property name="person_name_full">$Person.name.full</lticm:property>
    <lticm:property name="person_name_given">$Person.name.given</lticm:property>
    <lticm:property name="user_image">$User.image</lticm:property>
  </blti:custom>
  <blti:extensions platform="canvas.instructure.com">
';
    $retval .= '    <lticm:property name="privacy_level">'.$privacy_level."</lticm:property>\n";
    if ( $domain ) $retval .= '    <lticm:property name="domain">'.$domain."</lticm:property>\n";

    $retval .= '    <lticm:property name="text">'.htmlent_utf8(strip_tags($tool['name'])).'</lticm:property>
  </blti:extensions>
  <blti:extensions platform="tsugi.org">
    <lticm:property name="OAuth.hmac-sha256">true</lticm:property>
    <lticm:property name="OAuth.splitSecret">true</lticm:property>
';

    if ( $messages && in_array('launch_grade', $messages) ) {
        $retval .= '    <lticm:property name="simple_outcomes">true</lticm:property>'."\n";
    }
    $privacy_url = U::get($tool, 'privacy_url');
    if ( $privacy_url ) $retval .= '    <lticm:property name="privacy_url">'.$privacy_url."</lticm:property>\n";
    $sla_url = U::get($tool, 'sla_url');
    if ( $sla_url ) $retval .= '    <lticm:property name="sla_url">'.$sla_url."</lticm:property>\n";
    $key_url = U::get($tool, 'key_url');
    if ( $key_url ) $retval .= '    <lticm:property name="key_url">'.$key_url."</lticm:property>\n";
    $retval .= '    <lticm:property name="app_store_url">'.$CFG->wwwroot."/lti/store</lticm:property>\n";
    $retval .= '    <lticm:property name="app_store_canvas_url">'.$CFG->wwwroot."/lti/store/canvas-config.xml</lticm:property>\n";
  $retval .='  </blti:extensions>
</cartridge_basiclti_link>';

        return $retval;
    }

    public static function canvasStoreRegistration() {
        global $CFG;
        $pieces = parse_url($CFG->wwwroot);
        $domain = isset($pieces['host']) ? $pieces['host'] : false;
/*
        $string = '<?xml version="1.0" encoding="UTF-8"?>
<cartridge_basiclti_link xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0" xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0" xmlns:lticm="http://www.imsglobal.org/xsd/imslticm_v1p0" xmlns:lticp="http://www.imsglobal.org/xsd/imslticp_v1p0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticc_v1p0.xsd http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0p1.xsd http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd">
  <blti:title>'.htmlent_utf8(strip_tags($CFG->servicename)).'</blti:title>
  <blti:description>'.htmlent_utf8(strip_tags($CFG->servicedesc)).'</blti:description>
  <blti:launch_url>'.$CFG->wwwroot.'/about.php</blti:launch_url>
  <blti:custom>
    <lticm:property name="sub_canvas_account_id">$Canvas.account.id</lticm:property>
    <lticm:property name="sub_canvas_account_name">$Canvas.account.name</lticm:property>
    <lticm:property name="sub_canvas_account_sis_sourceId">$Canvas.account.sisSourceId</lticm:property>
    <lticm:property name="sub_canvas_api_domain">$Canvas.api.domain</lticm:property>
    <lticm:property name="sub_canvas_assignment_id">$Canvas.assignment.id</lticm:property>
    <lticm:property name="sub_canvas_assignment_points_possible">$Canvas.assignment.pointsPossible</lticm:property>
    <lticm:property name="sub_canvas_assignment_title">$Canvas.assignment.title</lticm:property>
    <lticm:property name="sub_canvas_course_id">$Canvas.course.id</lticm:property>
    <lticm:property name="sub_canvas_course_sis_source_id">$Canvas.course.sisSourceId</lticm:property>
    <lticm:property name="sub_canvas_enrollment_enrollment_state">$Canvas.enrollment.enrollmentState</lticm:property>
    <lticm:property name="sub_canvas_membership_concluded_roles">$Canvas.membership.concludedRoles</lticm:property>
    <lticm:property name="sub_canvas_membership_roles">$Canvas.membership.roles</lticm:property>
    <lticm:property name="sub_canvas_root_account.id">$Canvas.root_account.id</lticm:property>
    <lticm:property name="sub_canvas_root_account_sis_source_id">$Canvas.root_account.sisSourceId</lticm:property>
    <lticm:property name="sub_canvas_user_id">$Canvas.user.id</lticm:property>
    <lticm:property name="sub_canvas_user_login_id">$Canvas.user.loginId</lticm:property>
    <lticm:property name="sub_canvas_user_sis_source_id">$Canvas.user.sisSourceId</lticm:property>
    <lticm:property name="sub_canvas_caliper_url">$Caliper.url</lticm:property>
    <lticm:property name="person_address_timezone">$Person.address.timezone</lticm:property>
    <lticm:property name="person_email_primary">$Person.email.primary</lticm:property>
    <lticm:property name="person_name_family">$Person.name.family</lticm:property>
    <lticm:property name="person_name_full">$Person.name.full</lticm:property>
    <lticm:property name="person_name_given">$Person.name.given</lticm:property>
    <lticm:property name="user_image">$User.image</lticm:property>
  </blti:custom>
  <blti:extensions platform="canvas.instructure.com">
     <lticm:property name="privacy_level">public</lticm:property>
<?php
     if ( $domain ) echo ('<lticm:property name="domain">'.$domain."</lticm:property>\n");
?>
    <lticm:property name="icon_url">'.$CFG->staticroot.'/img/default-icon-16x16.png</lticm:property>
    <lticm:options name="link_selection">
      <lticm:property name="message_type">ContentItemSelectionRequest</lticm:property>
      <lticm:property name="url">'.$CFG->wwwroot.'/lti/store/index.php?type=link_selection</lticm:property>
    </lticm:options>
    <lticm:options name="assignment_selection">
      <lticm:property name="message_type">ContentItemSelectionRequest</lticm:property>
      <lticm:property name="url">'.$CFG->wwwroot.'/lti/store/index.php?type=assignment_selection</lticm:property>
    </lticm:options>
    <lticm:options name="homework_submission">
      <lticm:property name="message_type">ContentItemSelectionRequest</lticm:property>
      <lticm:property name="url">'.$CFG->wwwroot.'/lti/store/index.php?type=homework_submission</lticm:property>
    </lticm:options>
    <lticm:options name="editor_button">
      <lticm:property name="message_type">ContentItemSelectionRequest</lticm:property>
      <lticm:property name="url">'.$CFG->wwwroot.'/lti/store/index.php?type=editor_button</lticm:property>
    </lticm:options>
<?php if ( isset($CFG->lessons) ) {.'
    <lticm:options name="migration_selection">
        <lticm:property name="enabled">true</lticm:property>
        <lticm:property name="url">'.$CFG->wwwroot.'/cc/export</lticm:property>
    </lticm:options>
<?php }.'
    <lticm:property name="text">'.htmlent_utf8(strip_tags($CFG->servicename)).'</lticm:property>
  </blti:extensions>
</cartridge_basiclti_link>';
*/

    }
}

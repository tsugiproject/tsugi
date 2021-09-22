<?php

namespace Tsugi\UI;

/**
 * A series of routines used to generate and process the settings forms.
 */

use \Tsugi\Util\U;
use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;

class SettingsForm {

    /**
     * Check for incoming settings post data
     *
     * @return boolean Returns true if there were settings to handle and false
     * if there was nothing done.  Generally the calling tool will redirect
     * when true is returned.
     *
     *     if ( SettingsForm::isSettingsPost() ) {
     *         // Do form validation if you like
     *         SettingsForm::handleSettingsPost();
     *         header( 'Location: '.U::addSession('index.php?howdysuppress=1') ) ;
     *         return;
     *     }
     */
    public static function isSettingsPost() {
        global $USER;
        if ( ! $USER ) return false;
        return ( isset($_POST['settings_internal_post']) && $USER->instructor );
    }

    /**
     * Handle incoming settings post data
     *
     * @return boolean Returns true if there were settings to handle and false
     * if there was nothing done.  Generally the calling tool will redirect
     * when true is returned.
     *
     *     if ( SettingsForm::handleSettingsPost() ) {
     *         header( 'Location: '.U::addSession('index.php?howdysuppress=1') ) ;
     *         return;
     *     }
     */
    public static function handleSettingsPost() {
        global $USER;
        if ( ! $USER ) return false;

        if ( isset($_POST['settings_internal_post']) && $USER->instructor ) {
            $newsettings = array();
            foreach ( $_POST as $k => $v ) {
                if ( $k == session_name() ) continue;
                if ( $k == 'settings_internal_post' ) continue;
                if ( strpos('_ignore',$k) > 0 ) continue;
                if ( strpos('.ignore',$k) > 0 ) continue;
                $newsettings[$k] = $v;
            }

            // Merge these with the existing settings
            Settings::linkUpdate($newsettings);
            return true;
        }
        return false;
    }

    /**
      * Emit a properly styled "settings" button
      *
      * This is just the button, using the pencil icon.  Wrap in a
      * span or div tag if you want to move it around
      */
    public static function buttonText($right = false)
    {
        global $LINK;
        if ( ! $LINK ) return;
        $retval = "";
        if ( $right ) $retval .= '<span style="position: fixed; right: 10px; top: 5px;">';
        $retval .= '<button type="button" '.self::attr().' class="btn btn-default">';
        $retval .= '<span class="glyphicon glyphicon-pencil"></span></button>'."\n";
        if ( $right ) $retval .= '</span>';
        return $retval;
    }

    /**
      * Emit a properly styled "settings" button
      *
      * This is just the button, using the pencil icon.  Wrap in a
      * span or div tag if you want to move it around
      */
    public static function button($right = false)
    {
        echo(self::buttonText($right));
    }


    /**
     * Emit a properly styled "settings" link
     *
     * This is just the link, using the pencil icon and label.
     */
    public static function link($right = false)
    {
        global $LINK;
        if ( ! $LINK ) return;
        if ($right) {
            $pos = "pull-right";
        } else {
            $pos = "";
        }

        echo '<button type="button" '.self::attr().' class="btn btn-link '.$pos.'");>';
        echo '<span class="fas fa-cog" aria-hidden="true"></span> '.__("Settings").'</button>'."\n";
    }

    /**
     * Return the attributes to add to a tag to connect to activate the settings modal
     */
    public static function attr()
    {
        return 'data-toggle="modal" data-target="#tsugi_settings_dialog"';
    }


    public static function start() {
        global $USER, $OUTPUT, $LINK;
        if ( ! $USER ) return;
?>
<!-- Modal -->
<div id="tsugi_settings_dialog" class="modal fade" role="dialog" style="display: none;">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span class="fa fa-close" aria-hidden="true"></span><span class="sr-only">Close</span></button>
        <h4 class="modal-title"><?=htmlentities($LINK->title)?> <?=__("Settings")?></h4>
      </div>
      <div class="modal-body">
      <?php if ( $USER->instructor ) { ?>
        <form method="post" id="tsugi_settings_form">
      <?php } ?>
            <img id="tsugi_settings_spinner" src="<?php echo($OUTPUT->getSpinnerUrl()); ?>" style="display: none">
            <span id="tsugi_settings_save_fail" class="text-danger" style="display:none;"><?php _me('Unable to save settings'); ?></span>
            <?php if ( $USER->instructor ) { ?>
            <input type="hidden" name="settings_internal_post" value="1"/>
            <?php }
    }

    /**
     * Finish the form output
     */
    public static function end($ajax=false) {
        global $USER, $CFG;
        if ( ! $USER ) return;
        $settings_url = U::addSession($CFG->wwwroot."/api/settings.php");
?>
        <?php if ( $USER->instructor ) { ?>
        <?php if ( $ajax ) { ?>
        <button type="button" id="tsugi_settings_save" onclick="tsugi_settings_submit();" class="btn btn-primary"><?= _m("Save changes") ?></button>
        <script>
        function tsugi_settings_submit() {
            var inputs = $("#tsugi_settings_form :input");
            var json = {};
            $.map(inputs, function(n, i) {
                if (n.name.length < 1) return;
                if (n.name.endsWith('.ignore') ) return;
                if (n.name == 'settings_internal_post' ) return;
                json[n.name] = $(n).val();
                return;
            });
            // console.log(json);
            $.ajax({
                type: "POST",
                url: '<?= U::addSession($CFG->wwwroot."/api/settings.php") ?>',
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify(json),
                success: function (data) {
                    $('#tsugi_settings_dialog').modal('hide');
                }
            });
        }
        </script>
        <?php } else { ?>
        <button type="button" id="tsugi_settings_save" onclick="submit();" class="btn btn-primary"><?= _m("Save changes") ?></button>
        <?php } ?>
        </form>
        <?php } ?>
      </div>
    </div>

  </div>
</div>
<?php
    }

    /**
     * Handle a settings selector box
     *
     *
     * @param string $name  The name of the setting value
     * @param string $default  What to display as the first entry in the drop down
     * @param array $fields  The options to provide for the user (required)
     */
    public static function select($name, $default=false, $fields=false)
    {
        global $USER;
        if ( ! $USER ) return;
        if ( ! is_array($fields) ) die('SettingsForm::select() requires the $fields parameter');
        $oldsettings = Settings::linkGetAll();
        if ( ! $USER->instructor ) {
            $configured = false;
            foreach ( $fields as $k => $v ) {
                $index = $k;
                $display = $v;
                if ( is_int($index) ) $index = $display; // No keys
                if ( ! is_string($display) ) $display = $index;
                if ( isset($oldsettings[$name]) && $k == $oldsettings[$name] ) {
                    $configured = $display;
                }
            }
            if ( $configured === false ) {
                echo('<p>'._m('Setting').' '.htmlent_utf8($name).' '._m('is not set').'</p>');
            } else {
                echo('<p>'.htmlent_utf8(ucwords($name)).' '._m('is set to').' '.htmlent_utf8($configured).'</p>');
            }
            return;
        }

        // Instructor view
        if ( $default === false ) $default = _m('Please Select');
        echo('<div class="form-group"><select class="tsugi_setting form-control" id="tsugi_setting_'.$name.'" name="'.$name.'">');
        echo('<option value="0">'.$default.'</option>');
        foreach ( $fields as $k => $v ) {
            $index = $k;
            $display = $v;
            if ( is_int($index) ) $index = $display; // No keys
            if ( ! is_string($display) ) $display = $index;
            echo('<option value="'.$index.'"');
            if ( isset($oldsettings[$name]) && $index == $oldsettings[$name] ) {
                echo(' selected');
            }
            echo('>'.$display.'</option>'."\n");
        }
        echo('</select></div>');
    }

    /**
     * Handle a settings text box
     */
    public static function text($name, $title=false)
    {
        global $USER;
        if ( ! $USER ) return false;

        $oldsettings = Settings::linkGetAll();
        $configured = isset($oldsettings[$name]) ? $oldsettings[$name] : false;
        if ( $title === false ) $title = $name;
        if ( ! $USER->instructor ) {
            if ( $configured === false || strlen($configured) < 1 ) {
                echo('<p>'._m('Setting').' '.htmlent_utf8($name).' '._m('is not set').'</p>');
            } else {
                echo('<p>'.htmlent_utf8(ucwords($name)).' '._m('is set to').' '.htmlent_utf8($configured).'</p>');
            }
            return;
        }

        // Instructor view
        ?>
        <div class="form-group">
            <label for="tsugi_setting_<?=$name?>"><?=htmlent_utf8($title)?></label>
            <input type="text" class="tsugi_setting form-control" id="tsugi_setting_<?=$name?>" name="<?=$name?>" value="<?=htmlent_utf8($configured)?>">
        </div>
        <?php
    }

    /**
     * Handle a settings number box
     */
    public static function number($name, $title=false)
    {
        global $USER;
        if ( ! $USER ) return false;

        $oldsettings = Settings::linkGetAll();
        $configured = isset($oldsettings[$name]) ? $oldsettings[$name] : false;
        if ( $title === false ) $title = $name;
        if ( ! $USER->instructor ) {
            if ( $configured === false || strlen($configured) < 1 ) {
                echo('<p>'._m('Setting').' '.htmlent_utf8($name).' '._m('is not set').'</p>');
            } else {
                echo('<p>'.htmlent_utf8(ucwords($name)).' '._m('is set to').' '.htmlent_utf8($configured).'</p>');
            }
            return;
        }

        // Instructor view
        ?>
        <div class="form-group">
            <label for="<?=$name?>"><?=htmlent_utf8($title)?></label>
            <input type="number" class="tsugi_setting form-control" id="tsugi_setting_<?=$name?>" name="<?=$name?>" value="<?=htmlent_utf8($configured)?>">
        </div>
        <?php
    }

    /**
     * Handle a settings textarea box
     */
    public static function textarea($name, $title=false)
    {
        global $USER;
        if ( ! $USER ) return false;
        $oldsettings = Settings::linkGetAll();
        $configured = isset($oldsettings[$name]) ? $oldsettings[$name] : false;
        if ( $title === false ) $title = $name;
        if ( ! $USER->instructor ) {
            if ( $configured === false ) {
                echo('<p>'._m('Setting').' '.htmlent_utf8($name).' '._m('is not set').'</p>');
            } else {
                echo('<p>'.htmlent_utf8(ucwords($name)).' '._m('is set to').' '.htmlent_utf8($configured).'</p>');
            }
            return;
        }

        // Instructor view
        ?>
        <div class="form-group">
            <label for="<?=$name?>"><?=htmlent_utf8($title)?></label>
            <textarea class="tsugi_setting form-control" rows="5" id="tsugi_setting_<?=$name?>" name="<?=$name?>"><?=htmlent_utf8($configured)?></textarea>
        </div>
        <?php
    }

    /**
     * Handle a settings checkbox
     */
    public static function checkbox($name, $title=false)
    {
        global $USER;
        if ( ! $USER ) return false;
        $oldsettings = Settings::linkGetAll();
        $configured = isset($oldsettings[$name]) ? $oldsettings[$name] : false;
        if ( $title === false ) $title = $name;
        if ( ! $USER->instructor ) {
            if ( $configured === false ) {
                echo('<p>'._m('Setting').' '.htmlent_utf8($name).' '._m('is not set').'</p>');
            } else {
                echo('<p>'.htmlent_utf8(ucwords($name)).' is set to '.htmlent_utf8($configured).'</p>');
            }
            return;
        }

        // Instructor view
        ?>
        <div class="checkbox">
            <label><input type="checkbox" class="tsugi_setting" value="1" id="tsugi_setting_<?=$name?>" name="<?=$name?>"
            <?php
        if ( $configured == 1 ) {
            echo(' checked ');
            echo("onclick=\"if(this.checked) document.getElementById('");
            echo($name);
            echo(".mirror').name = '");
            echo($name);
            echo(".ignore'; else document.getElementById('");
            echo($name);
            echo(".mirror').name = '");
            echo($name);
            echo("';\"");
        }
            ?>
                ><?=htmlent_utf8($title)?></label>
        </div>
        <?php
        if ( $configured == 1 ) {
            echo("<input type=\"hidden\" name=\"");
            echo($name);
            echo(".ignore\" id=\"");
            echo($name);
            echo(".mirror\" value=\"0\" />");
        }
    }
    /**
      * Get the due data data in an object
      */

    public static function getDueDate() {
        $retval = new \stdClass();
        $retval->penaltyinfo = false;  // Details about the penalty irrespective of the current date
        $retval->message = false;
        $retval->penalty = 0;
        $retval->dayspastdue = 0;
        $retval->percent = 0;
        $retval->duedate = false;
        $retval->duedatestr = false;

        $duedatestr = Settings::linkGet('due');
        if ( $duedatestr === false ) return $retval;
        $duedate = strtotime($duedatestr);

        $diff = -1;
        $penalty = false;

        date_default_timezone_set('Pacific/Honolulu'); // Lets be generous
        $new_time_zone = Settings::linkGet('timezone');
        if ( $new_time_zone && in_array($new_time_zone, timezone_identifiers_list())) {
            date_default_timezone_set($new_time_zone);
        }

        if ( $duedate === false ) return $retval;

        $penalty_time = Settings::linkGet('penalty_time') ? Settings::linkGet('penalty_time') + 0 : 24*60*60;
        $penalty_cost = Settings::linkGet('penalty_cost') ? Settings::linkGet('penalty_cost') + 0.0 : 0.2;

        $retval->penaltyinfo = sprintf(_m("Once the due date has passed your
            score will be reduced by %f percent and each %s after the due date,
            your score will be further reduced by %s percent."),
                htmlent_utf8($penalty_cost*100), htmlent_utf8(self::getDueDateDelta($penalty_time)),
                htmlent_utf8($penalty_cost*100) );

        //  If it is just a date - add nearly an entire day of time...
        if ( strlen($duedatestr) <= 10 ) $duedate = $duedate + 24*60*60 - 1;
        $diff = time() - $duedate;

        $retval->duedate = $duedate;
        $retval->duedatestr = $duedatestr;
        // Should be a percentage off between 0.0 and 1.0
        if ( $diff > 0 ) {
            $penalty_exact = $diff / $penalty_time;
            $penalties = intval($penalty_exact) + 1;
            $penalty = $penalties * $penalty_cost;
            if ( $penalty < 0 ) $penalty = 0;
            if ( $penalty > 1 ) $penalty = 1;
            $retval->penalty = $penalty;
            $retval->dayspastdue = $diff / (24*60*60);
            $retval->percent = intval($penalty * 100);
            $retval->message = sprintf(
                _m("It is currently %s past the due date (%s) so your late penalty is %f percent."),
                self::getDueDateDelta($diff), htmlentities($duedatestr),$retval->percent);
        }
        return $retval;
    }

    /**
     * Show a due date delta in reasonable units
     */
    public static function getDueDateDelta($time)
    {
        if ( $time < 600 ) {
            $delta = $time . ' seconds';
        } else if ($time < 3600) {
            $delta = sprintf("%0.0f",($time/60.0)) . ' ' . _m('minutes');
        } else if ($time <= 86400 ) {
            $delta = sprintf("%0.2f",($time/3600.0)) . ' ' . _m('hours');
        } else {
            $delta = sprintf("%0.2f",($time/86400.0)) . ' ' . _m('days');
        }
        return $delta;
    }

    /**
     * Emit the text and form fields to support due dates
     */
    public static function dueDate()
    {
        global $USER;
        if ( ! $USER ) return false;
        $due = Settings::linkGet('due', '');
        $timezone = Settings::linkGet('timezone', 'Pacific/Honolulu');
        if ( ! in_array($timezone, timezone_identifiers_list()) ) $timezone = 'Pacific/Honolulu';
        $time = Settings::linkGet('penalty_time', 86400);
        $cost = Settings::linkGet('penalty_cost', 0.2);

        if ( ! $USER->instructor ) {
            if ( strlen($due) < 1 ) {
                echo("<p>"._m("There is currently no due date/time for this assignment.")."</p>\n");
                return;
            }
            $dueDate = self::getDueDate();
            echo("<p>"._m("Due date: ").htmlent_utf8($due)."</p>\n");
            echo("<p>".$dueDate->penaltyinfo."</p>\n");
            if ( $dueDate->message ) {
                echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
            }
            return;
        }
?>
        <label for="due">
            <?= _m("Please enter a due date in ISO 8601 format (2015-01-30T20:30) or leave blank for no due date.
            You can leave off the time to allow the assignment to be turned in any time during the day.") ?><br/>
        <input type="text" class="tsugi_setting form-control" id="tsugi_setting_due" value="<?php echo(htmlspec_utf8($due)); ?>" name="due"></label>
        <label for="timezone">
            <?= _m("Please enter a valid PHP Time Zone like 'Pacific/Honolulu' (default).  If you are
            teaching in many time zones around the world, 'Pacific/Honolulu' is a good time
            zone to choose - this is why it is the default if it is available.") ?><br/>
        <select name="timezone" id="tsugi_setting_timezone" class="tsugi_setting form-control">
<?php
            foreach(timezone_identifiers_list() as $tz ) {
                echo('<option value="'.htmlspec_utf8($tz).'" ');
                if ( $tz == $timezone ) echo(' selected="yes" ');
                echo('>'.htmlentities($tz)."</option>\n");
            }
?>
        </select>
        </label>
            <p><?= _m("The next two fields determine the 'overall penalty' for being late.  We define a time period
            (in seconds) and a fractional penalty per time period.  The penalty is assessed for each
            full or partial time period past the due date.  For example to deduct 20% per day, you would
            set the period to be 86400 (24*60*60) and the penalty to be 0.2.") ?>
            </p>
        <label for="penalty_time"><?= _m("Please enter the penalty time period in seconds.") ?><br/>
        <input type="number" id="tsugi_setting_penalty_time" class="tsugi_setting form-control" value="<?php echo(htmlspec_utf8($time)); ?>" name="penalty_time"></label>
        <label for="penalty_cost"><?= _m("Please enter the penalty deduction as a decimal between 0.0 and 1.0.") ?><br/>
        <input type="number" id="tsugi_setting_penalty_cost" class="tsugi_setting form-control" value="<?php echo(htmlspec_utf8($cost)); ?>" name="penalty_cost"></label>
<?php
    }

    /**
     * Emit the text and form fields to support the done option
     */
    public static function done()
    {
        global $USER;
        if ( ! $USER ) return false;
/*
        return; // Deprecated
        if ( ! $USER->instructor ) return;
        $done = Settings::linkGet('done', '');
?>
        <label for="done">
            This option allows you to control the existance and behavior of a "Done" button for this tool.
            If you leave this blank the tool will assume it is in an iFrame and will not show a Done button.
            If you put a URL here, a Done button will be shown and when pressed the tool will navigate to
            the specified URL.  If you expect to launch this tool in a popup, enter "_close" here and
            the tool will close its window when Done is pressed.<br/>
        <input type="text" class="tsugi_setting form-control" value="<?php echo(htmlspec_utf8($done)); ?>" name="done"></label>
<?php
*/
    }

}

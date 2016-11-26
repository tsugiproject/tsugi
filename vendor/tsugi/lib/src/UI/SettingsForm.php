<?php

namespace Tsugi\UI;

/**
 * A series of routines used to generate and process the settings forms.
 */

use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;

class SettingsForm {

    /**
     * Handle incoming settings post data
     *
     * @return boolean Returns true if there were settings to handle and false
     * if there was nothing done.  Generally the calling tool will redirect
     * when true is returned.
     *
     *     if ( $OUTPUT->handleSettingsPost() ) {
     *         header( 'Location: '.addSession('index.php?howdysuppress=1') ) ;
     *         return;
     *     }
     */
    public static function handleSettingsPost() {
        global $USER;

        if ( isset($_POST['settings_internal_post']) && $USER->instructor ) {
            $newsettings = array();
            foreach ( $_POST as $k => $v ) {
                if ( $k == session_name() ) continue;
                if ( $k == 'settings_internal_post' ) continue;
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
    public static function button($right = false)
    {
        if ( $right ) echo('<span style="position: fixed; right: 10px; top: 5px;">');
        echo('<button onclick="$(\'#settings\').modal();return false;" type="button" class="btn btn-default">');
        echo('<span class="glyphicon glyphicon-pencil"></span></button>'."\n");
        if ( $right ) echo('</span>');
    }

    public static function start() {
        global $USER, $LINK, $OUTPUT;
?>
<div class="modal fade" id="settings">
  <div class="modal-dialog">
    <div class="modal-content">
      <?php if ( $USER->instructor ) { ?>
      <form id="settings_form" method="POST">
      <?php } ?>
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title"><?php _me('Configure'); ?>
        <?php echo(htmlent_utf8($LINK->title)); ?>
        </h4>
      </div><!-- / .modal-header -->
      <div class="modal-body">
        <img id="settings_spinner" src="<?php echo($OUTPUT->getSpinnerUrl()); ?>" style="display: none">
        <span id="save_fail" style="display:none; color:red"><?php _me('Unable to save settings'); ?></span>
        </p>
            <?php if ( $USER->instructor ) { ?>
            <input type="hidden" name="settings_internal_post" value="1"/>
            <?php }
    }

    /**
     * Finish the form output
     */
    public static function end() {
        global $USER;
?>
      </div><!-- / .modal-body -->
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <?php if ( $USER->instructor ) { ?>
        <button type="button" id="settings_save" onclick="submit();" class="btn btn-primary">Save changes</button>
        <?php } ?>
      </div><!-- / .modal-footer -->
    <?php if ( $USER->instructor ) { ?>
    </form>
    <?php } ?>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php
    }

    /**
     * Handle a settings selector box
     */
    public static function select($name, $default=false, $fields)
    {
        global $USER;
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
                echo('<p>Setting '.htmlent_utf8($name).' is not set</p>');
            } else {
                echo('<p>'.htmlent_utf8(ucwords($name)).' is set to '.htmlent_utf8($configured).'</p>');
            }
            return;
        }

        // Instructor view
        if ( $default === false ) $default = _m('Please Select');
        echo('<select name="'.$name.'">');
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
        echo('</select>');
    }

    /**
     * Handle a settings text box
     */
    public static function text($name, $title=false)
    {
        global $USER;
        $oldsettings = Settings::linkGetAll();
        $configured = isset($oldsettings[$name]) ? $oldsettings[$name] : false;
        if ( $title === false ) $title = $name;
        if ( ! $USER->instructor ) {
            if ( $configured === false ) {
                echo('<p>Setting '.htmlent_utf8($name).' is not set</p>');
            } else {
                echo('<p>'.htmlent_utf8(ucwords($name)).' is set to '.htmlent_utf8($configured).'</p>');
            }
            return;
        }

        // Instructor view
        echo('<label style="width:100%;" for="'.$name.'">'.htmlent_utf8($title)."\n");
        echo('<input type="text" class="form-control" style="width:100%;" name="'.$name.'"');
        echo('value="'.htmlent_utf8($configured).'"></label>'."\n");
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
        if ( Settings::linkGet('timezone') ) {
            date_default_timezone_set(Settings::linkGet('timezone'));
        }

        if ( $duedate === false ) return $retval;

        $penalty_time = Settings::linkGet('penalty_time') ? Settings::linkGet('penalty_time') + 0 : 24*60*60;
        $penalty_cost = Settings::linkGet('penalty_cost') ? Settings::linkGet('penalty_cost') + 0.0 : 0.2;

        $r = "Once the due date has passed your score will be reduced by ".htmlent_utf8($penalty_cost*100);
        $r .= " percent and each \n";
        $r .= htmlent_utf8(self::getDueDateDelta($penalty_time));
        $r .= " after the due date, your score will be further reduced by ".htmlent_utf8($penalty_cost*100);
        $r .= " percent.</p>";
        $retval->penaltyinfo = $r;

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
            $retval->message = 'It is currently '.self::getDueDateDelta($diff)."\n".
                'past the due date ('.htmlentities($duedatestr).') so your late penalty '.
                'is '.$retval->percent." percent.\n";
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
            $delta = sprintf("%0.0f",($time/60.0)) . ' minutes';
        } else if ($time <= 86400 ) {
            $delta = sprintf("%0.2f",($time/3600.0)) . ' hours';
        } else {
            $delta = sprintf("%0.2f",($time/86400.0)) . ' days';
        }
        return $delta;
    }

    /**
     * Emit the text and form fields to support due dates
     */
    public static function dueDate()
    {
        global $USER;
        $due = Settings::linkGet('due', '');
        $timezone = Settings::linkGet('timezone', 'Pacific/Honolulu');
        $time = Settings::linkGet('penalty_time', 86400);
        $cost = Settings::linkGet('penalty_cost', 0.2);

        if ( ! $USER->instructor ) {
            if ( strlen($due) < 1 ) {
                echo("<p>There is currently no due date/time for this assignment.</p>\n");
                return;
            }
            $dueDate = self::getDueDate();
            echo("<p>Due date: ".htmlent_utf8($due)."</p>\n");
            echo("<p>".$dueDate->penaltyinfo."</p>\n");
            if ( $dueDate->message ) {
                echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
            }
            return;
        }
?>
        <label for="due">
            Please enter a due date in ISO 8601 format (2015-01-30T20:30) or leave blank for no due date.
            You can leave off the time to allow the assignment to be turned in any time during the day.<br/>
        <input type="text" class="form-control" value="<?php echo(htmlspec_utf8($due)); ?>" name="due"></label>
        <label for="timezone">
            Please enter a valid PHP Time Zone like 'Pacific/Honolulu' (default).  If you are
            teaching in many time zones around the world, 'Pacific/Honolulu' is a good time
            zone to choose - this is why it is the default.<br/>
        <input type="text" class="form-control" value="<?php echo(htmlspec_utf8($timezone)); ?>" name="timezone"></label>
            <p>The next two fields determine the "overall penalty" for being late.  We define a time period
            (in seconds) and a fractional penalty per time period.  The penalty is assessed for each
            full or partial time period past the due date.  For example to deduct 20% per day, you would
            set the period to be 86400 (24*60*60) and the penalty to be 0.2.
            </p>
        <label for="penalty_time">Please enter the penalty time period in seconds.<br/>
        <input type="text" class="form-control" value="<?php echo(htmlspec_utf8($time)); ?>" name="penalty_time"></label>
        <label for="penalty_cost">Please enter the penalty deduction as a decimal between 0.0 and 1.0.<br/>
        <input type="text" class="form-control" value="<?php echo(htmlspec_utf8($cost)); ?>" name="penalty_cost"></label>
<?php
    }

    /**
     * Emit the text and form fields to support the done option
     */
    public static function done()
    {
        global $USER;
        if ( ! $USER->instructor ) return;
        $done = Settings::linkGet('done', '');
?>
        <label for="done">
            This option allows you to control the existance and behavior of a "Done" button for this tool.
            If you leave this blank the tool will assume it is in an iFrame and will not show a Done button.
            If you put a URL here, a Done button will be shown and when pressed the tool will navigate to
            the specified URL.  If you expect to launch this tool in a popup, enter "_close" here and
            the tool will close its window when Done is pressed.<br/>
        <input type="text" class="form-control" value="<?php echo(htmlspec_utf8($done)); ?>" name="done"></label>
<?php
    }

}

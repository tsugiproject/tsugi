<?php

namespace Tsugi\UI;

/**
 * A series of routines used to generate and process the settings forms.
 */

use \Tsugi\Core\Settings;

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
            Settings::linkSetAll($newsettings);
            return true;
        }
        return false;
    }

    public static function start() {
        global $LINK, $OUTPUT;
?>
<div class="modal fade" id="settings">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title"><?php _me('Configure'); ?>
        <?php echo(htmlent_utf8($LINK->title)); ?>
        </h4>
      </div>
      <div class="modal-body">
        <img id="settings_spinner" src="<?php echo($OUTPUT->getSpinnerUrl()); ?>" style="display: none">
        <span id="save_fail" style="display:none; color:red"><?php _me('Unable to save settings'); ?></span>
        </p>
        <form id="settings_form" method="POST">
            <input type="hidden" name="settings_internal_post" value="1"/>
<?php
    }

    /**
     * Finish the form output
     */
    public static function end() {
?>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" id="settings_save" class="btn btn-primary">Save changes</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php
    }

    /**
     * Emit the text and form fields to support due dates
     */
    public static function dueDate()
    {
            $due = Settings::linkGet('due', '');
            $timezone = Settings::linkGet('timezone', 'Pacific/Honolulu');
            $time = Settings::linkGet('penalty_time', 86400);
            $cost = Settings::linkGet('penalty_cost', 0.2);
?>
            <label for="due">
            Please enter a due date in ISO 8601 format (2015-01-30T20:30) or leave blank for no due date.  
            You can leave off the time to allow the assignment to be turned in any time during the day.<br/>
            <input type="text" value="<?php echo(htmlspec_utf8($due)); ?>" name="due"></label>
            <label for="timezone">
            Please enter a valid PHP Time Zone like 'Pacific/Honolulu' (default).  If you are
            teaching in many time zones around the world, 'Pacific/Honolulu' is a good time
            zone to choose - this is why it is the default.<br/>
            <input type="text" value="<?php echo(htmlspec_utf8($timezone)); ?>" name="timezone"></label>
            <p>The next two fields determine the "overall penalty" for being late.  We define a time period
            (in seconds) and a fractional penalty per time period.  The penalty is assessed for each 
            full or partial time period past the due date.  For example to deduct 20% per day, you would
            set the period to be 86400 (24*60*60) and the penalty to be 0.2.
            </p>
            <label for="penalty_time">Please enter the penalty time period in seconds.<br/>
            <input type="text" value="<?php echo(htmlspec_utf8($time)); ?>" name="penalty_time"></label>
            <label for="penalty_cost">Please enter the penalty deduction as a decimal between 0.0 and 1.0.<br/>
            <input type="text" value="<?php echo(htmlspec_utf8($cost)); ?>" name="penalty_cost"></label>
<?php
    }

    /**
     * Emit the text and form fields to support the done option
     */
    public static function done()
    {
            $done = Settings::linkGet('done', '');
?>
            <label for="done">
            This option allows you to control the existance and behavior of a "Done" button for this tool.
            If you leave this blank the tool will assume it is in an iFrame and will not show a Done button.
            If you put a URL here, a Done button will be shown and when pressed the tool will navigate to
            the specified URL.  If you expect to launch this tool in a popup, enter "_close" here and 
            the tool will close its window when Done is pressed.<br/>
            <input type="text" value="<?php echo(htmlspec_utf8($done)); ?>" size="80" name="done"></label>
<?php
    }

}

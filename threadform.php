<?php
require_once "../config.php";
require_once "util/tdiscus.php";
require_once "util/threads.php";

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tdiscus\Tdiscus;
use \Tdiscus\Threads;

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData();

$THREADS = new Threads();
$TDISCUS = new Tdiscus();
$rest_path = U::rest_path();

$purifier = $THREADS->getPurifier();

$come_back = 'threadform';
$thread_id = null;
$old_thread = null;
if ( isset($rest_path->action) && is_numeric($rest_path->action) ) {
    $thread_id = intval($rest_path->action);
    error_log("thread_id=$thread_id");
    $old_thread = $THREADS->threadLoadForUpdate($thread_id);
    if ( ! is_array($old_thread) ) {
        $_SESSION['error'] = __('Could not load thread');
        header( 'Location: '.addSession('threadform') ) ;
        return;
    }
    $come_back .= '/' . $thread_id;
}

if ( count($_POST) > 0 ) {
    if ( $old_thread ) {
        $retval = $THREADS->threadUpdate($thread_id, $_POST);
        if ( is_string($retval) ) {
            $_SESSION['error'] = $retval;
            header( 'Location: '.addSession($TOOL_ROOT . '/' . $come_back) ) ;
            return;
        }

        $_SESSION['success'] = __('Thread updated');
        header( 'Location: '.addSession($TOOL_ROOT . '/') ) ;
    } else {
        $retval = $THREADS->threadInsert($_POST);
        if ( is_string($retval) ) {
            $_SESSION['error'] = $retval;
            header( 'Location: '.addSession($TOOL_ROOT . '/' . $come_back) ) ;
            return;
        }

        $_SESSION['success'] = __('Thread added');
        header( 'Location: '.addSession($TOOL_ROOT . '/') ) ;
    }
    return;
}

$OUTPUT->header();
$TDISCUS->header();

$menu = false;

$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);
$OUTPUT->flashMessages();
if ( $old_thread ) {
    echo("<h1>".__('Editing Thread')."</h1>\n");
} else {
    echo("<h1>".__('New Thread')."</h1>\n");
}
?>
<div id="add-thread-div" title="<?= __("New Thread") ?>" >
<form id="add-thread-form" method="post">
<p><?= __("Title:") ?> <br/>
<input type="text" name="title" class="form-control"
<?php 
if ( $old_thread ) {
    echo('value="'.htmlentities($old_thread['title'] ?? '').'" ');
}
?>
>
</p>
<p><?= __("Description:") ?> <br/>
<textarea id="editor" name="body" class="form-control">
<?php 
if ( $old_thread ) {
    echo($purifier->purify($old_thread['body']));
}
?>
</textarea>
</p>
<p>
<input type="submit" id="add-thread-submit" value="<?= ($old_thread ? __('Update') : __('+ Thread')) ?>" >
<input type="submit" id="add-thread-cancel" value="<?= __('Cancel') ?>"
onclick='window.location.href="<?= addSession($TOOL_ROOT . '/') ?>";return false;'
>
</p>
</form>
</div>
<?php

$OUTPUT->footerStart();
$TDISCUS->footer();
$TDISCUS->ckeditor_footer();
$OUTPUT->footerEnd();

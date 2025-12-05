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

$come_back = 'commentform';
$comment_id = null;
$thread_id = null;
$old_comment = null;
if ( isset($rest_path->action) && is_numeric($rest_path->action) ) {
    $comment_id = intval($rest_path->action);
    $old_comment = $THREADS->commentLoadForUpdate($comment_id);
    if ( ! is_array($old_comment) ) {
        $_SESSION['error'] = __('Could not load comment');
        header( 'Location: '.addSession('commentform') ) ;
        return;
    }
    $thread_id = $old_comment['thread_id'];
    $come_back .= '/' . $comment_id;
}

if ( count($_POST) > 0 ) {
    $retval = $THREADS->commentUpdateDao($old_comment, U::get($_POST, 'comment'));
    if ( is_string($retval) ) {
        $_SESSION['error'] = $retval;
        header( 'Location: '.addSession($TOOL_ROOT . '/' . $come_back) ) ;
        return;
    }

    $_SESSION['success'] = __('Comment updated');
    header( 'Location: '.addSession($TOOL_ROOT.'/thread/'.$thread_id) ) ;
    return;
}

$OUTPUT->header();
$TDISCUS->header();

$OUTPUT->bodyStart();
$OUTPUT->topNav(false);
$OUTPUT->flashMessages();
if ( $old_comment ) {
    echo("<h1>".__('Edit Comment')."</h1>\n");
} else {
    echo("<h1>".__('Add Comment')."</h1>\n");
}
?>
<div id="edit-comment-div" title="<?= __("Comment") ?>" >
<form id="edit-comment-form" method="post">
<input type="text" name="comment" class="form-control"
<?php 
if ( $old_comment ) {
    echo('value="'.htmlentities($old_comment['comment'] ?? '').'" ');
}
?>
>
</p>
<p>
<input type="submit" id="edit-comment-submit" value="<?= __('Update') ?>" >
<input type="submit" id="edit-comment-cancel" value="<?= __('Cancel') ?>"
onclick='window.location.href="<?= addSession($TOOL_ROOT.'/thread/'.$thread_id) ?>";return false;'
>
</p>
</form>
</div>
<?php

$OUTPUT->footerStart();
$TDISCUS->footer();
$TDISCUS->ckeditor_footer();
$OUTPUT->footerEnd();

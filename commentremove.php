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

$old_comment = null;
$thread_id = null;
if ( isset($rest_path->action) && is_numeric($rest_path->action) ) {
    $comment_id = intval($rest_path->action);
    $old_comment = $THREADS->commentLoadForUpdate($comment_id);
    if ( is_array($old_comment) ) $thread_id = $old_comment['thread_id'];
}

if ( !$old_comment || !$thread_id) {
    $_SESSION['error'] = __('Could not load comment');
    header( 'Location: '.addSession($TOOL_ROOT) ) ;
    return;
}

$come_back = $TOOL_ROOT . '/commentremove/' . $comment_id;
$all_done = $TOOL_ROOT.'/thread/'.$thread_id;

if ( count($_POST) > 0 ) {
    $retval = $THREADS->commentDelete($comment_id, $thread_id);
    if ( is_string($retval) ) {
        $_SESSION['error'] = $retval;
        header( 'Location: '.addSession($come_back) ) ;
        return;
    }

    $_SESSION['success'] = __('Comment deleted');
    header( 'Location: '.addSession($all_done) ) ;
    return;
}

$OUTPUT->header();
$TDISCUS->header();

$OUTPUT->bodyStart();
$OUTPUT->topNav(false);
$OUTPUT->flashMessages();

echo("<h1>".__('Delete Comment')."</h1>\n");

?>
<div id="delete-comment-div" title="<?= __("Delete comment") ?>" >
<form id="delete-comment-form" method="post">
<p><?= __("Comment:") ?> <br/>
<?php
echo('<b>'.htmlentities($old_comment['comment'] ?? '').'</b></br>');
?>
</p>
<p>
<input type="submit" id="delete-comment-submit" value="<?= __('Delete') ?>" >
<input type="submit" id="delete-comment-cancel" value="<?= __('Cancel') ?>"
onclick='window.location.href="<?= addSession($all_done) ?>";return false;'
>
</p>
</form>
</div>
<?php

$OUTPUT->footerStart();
$TDISCUS->footer();
$OUTPUT->footerEnd();


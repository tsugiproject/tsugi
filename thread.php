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

$purifier = $THREADS->getPurifier();

$rest_path = U::rest_path();

$thread_id = null;
$thread = null;
if ( isset($rest_path->action) && is_numeric($rest_path->action) ) {
    $thread_id = intval($rest_path->action);
    $thread = $THREADS->threadLoadMarkRead($thread_id);
}

$thread_locked = intval($thread['locked']) && ! $LAUNCH->user->instructor;

if ( ! $thread ) {
    $_SESSION['error'] = __('Could not load thread');
    header( 'Location: '.addSession($TOOL_ROOT . '/') ) ;
    return;
}

$come_back = $TOOL_ROOT . '/thread/' . $thread_id;
$all_done = $TOOL_ROOT . '/';
$discussion_title = strlen(Settings::linkget('title')) > 0 ? Settings::linkget('title') : $LAUNCH->link->title;

if ( count($_POST) > 0 ) {
    $retval = $THREADS->commentInsertDao($thread, U::get($_POST, 'comment') );
    if ( is_string($retval) ) {
        $_SESSION['error'] = $retval;
        header( 'Location: '.addSession($come_back) ) ;
        return;
    }

    if ( Settings::linkGet('grade') > 0 ) {
        $LAUNCH->result->gradeSend(1.0, false);
    }


    header( 'Location: '.addSession($come_back) ) ;
    return;
}

$retval = $THREADS->comments($thread_id);
$comments = $retval->rows;

$OUTPUT->header();
$TDISCUS->header();

$pagesize = intval(U::get($_GET, 'pagesize', Threads::default_page_size));
$start = intval(U::get($_GET, 'start', 0));
$page_base = $come_back;

// Does not include start
$copyparms = array('search', 'sort', 'pagesize');
foreach ( $copyparms as $parm ) {
    $val = U::get($_GET, $parm, "");
    if ( strlen($val) == 0 ) continue;
    $page_base = U::add_url_parm($page_base, $parm, $val);
}

$menu = false;
// $menu->addLeft(__('All Threads'), $TOOL_ROOT);

$commenttop = (Settings::linkGet('commenttop') == 1);

$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);
$sortable = $THREADS->commentsSortableBy();
$OUTPUT->flashMessages();
echo('<div class="tdiscus-thread-container">'."\n");
echo('<p>');
echo('<a style="float: right;" href="'.$TOOL_ROOT.'/"><i class="fa fa-home" title="'.__('All Threads').'"></i> '.__('All Threads').'</a></p>');
echo('<span class="tdiscus-thread-title"><a href="'.$page_base.'"'.($thread['hidden'] ? ' style="text-decoration: line-through;"' : '').'>'.htmlentities($thread['title'] ?? '').'</a>');
echo("</span></p>\n");
?>
<p class="tdiscus-thread-info">
<span class="tdiscus-user-name"><?= $thread['displayname'] ?></span>
 -
<time class="timeago" datetime="<?= $thread['modified_at'] ?>"><?= $thread['modified_at'] ?></time>
<?php if ( $thread['edited'] == 1 ) {
    echo(" - ".__("edited"));
} ?>
</p>
<p class="tdiscus-thread-body">
<?= $purifier->purify($thread['body']) ?>
</p>
<p>
<!--
<?= $thread['netvote'] ?> Upvotes
<i class="fa fa-arrow-up"></i>
<a href="#reply"
onclick="document.querySelector('#tdiscus-add-comment-div').scrollIntoView({ behavior: 'smooth' });"
><?= __('Reply') ?>
<i class="fa fa-reply-all"></i>
</a>
-->
</div>
<?php if ( count($comments) > 0 ) { ?>
<div class="tdiscus-comments-container">
<div class="tdiscus-comments-sort">
<?php
$TDISCUS->search_box($sortable);
if ( $commenttop && ! $thread_locked) $TDISCUS->add_comment($thread_id);
?>
</div>
<div class="tdiscus-comments-list">

<?php
    foreach($comments as $comment ) {
        $TDISCUS->renderComment($LAUNCH, $thread_id, $comment);
    }
?>
</div> <!-- tdiscus-comments-list -->
</div> <!-- tdiscus-comments-container -->
<?php
    }

  $TDISCUS->paginator($page_base, $start, $pagesize, $retval->total);

if ( ! $commenttop && ! $thread_locked) $TDISCUS->add_comment($thread_id);

$OUTPUT->footerStart();
$TDISCUS->footer();
$TDISCUS->renderBooleanScript();
$OUTPUT->footerEnd();

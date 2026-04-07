<?php
require_once "../config.php";
require_once "util/tdiscus.php";
require_once "util/threads.php";

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsDialog;
use \Tdiscus\Tdiscus;
use \Tdiscus\Threads;

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData();
$settingsDialog = new SettingsDialog();

if ( $settingsDialog->handleSettingsPost() ) {
    header( 'Location: '.addSession('index') ) ;
    return;
}

$THREADS = new Threads();
$TDISCUS = new Tdiscus();
$tool_root = Tdiscus::toolRoot();
if ( isset($TOOL_ROOT) && is_string($TOOL_ROOT) && strlen($TOOL_ROOT) > 0 ) {
    $tool_root = $TOOL_ROOT;
}

$OUTPUT->header();
$TDISCUS->header();

$pagesize = intval(U::get($_GET, 'pagesize', Threads::default_page_size));
$start = intval(U::get($_GET, 'start', 0));
$comeback = $tool_root.'/';

// Does not include start
$copyparms = array('search', 'sort', 'pagesize');
foreach ( $copyparms as $parm ) {
    $val = U::get($_GET, $parm, "");
    if ( strlen($val) == 0 ) continue;
    $comeback = U::add_url_parm($comeback, $parm, $val);
}

$menu = false;
if ( $USER->instructor ) {
    $menu = new \Tsugi\UI\MenuSet();
    if ( $CFG->launchactivity ) {
        $menu->addRight(__('Analytics'), 'analytics');
    }
    $menu->addRight(__('Settings'), '#', /* push */ false, $settingsDialog->attr());
}

$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);

$dicussion_title = strlen($LAUNCH->link->settingsGet('title')) > 0 ? $LAUNCH->link->settingsGet('title') : $LAUNCH->link->title;

if ( $LAUNCH->link->settingsGet('depth') < 1 ) $LAUNCH->link->settingsSet('depth', '2');

echo('<div class="tdiscus-threads-header">');
echo('<span class="tdiscus-threads-title">');
echo('<a href="'.$comeback.'">');
echo(htmlentities($dicussion_title));
echo('</a></span>' );
echo('<a class="tdiscus-add-thread-link" href="'.$tool_root.'/threadform'.'" aria-label="'.htmlspecialchars(__('Add Thread')).'">');
echo('<i class="fa fa-plus" aria-hidden="true"></i> ');
echo(__('Add Thread'));
echo('</a>');
echo('</div><br clear="all"/>'."\n");

$settingsDialog->start();
$settingsDialog->text('title',__('Discussion title override.'));
$settingsDialog->checkbox('grade',__('Give a 100% grade for a student making a post or a comment.'));
// $settingsDialog->checkbox('studentthread',__('Allow learners to create a thread.'));
$settingsDialog->checkbox('commenttop',__('Put comment box before comments in thread display.'));
// $settingsDialog->number('lockminutes',__('Number of minutes before posts are locked.'));
$settingsDialog->number('maxdepth',__('Allowed depth of nested comments. Default is 2. Set to 1 for no nested comments.'));
$settingsDialog->checkbox('badge_include_participating',__('Include participating thread activity in main badge.'));
$settingsDialog->checkbox('badge_participation_personal',__('Treat participating thread activity as personal unread.'));
$settingsDialog->dueDate();
$settingsDialog->end();

$OUTPUT->flashMessages();

$retval = $THREADS->threads();
$threads = $retval->rows;


$sortable = $THREADS->threadsSortableBy();
$TDISCUS->search_box($sortable);

if ( count($threads) < 1 ) {
    echo("<p>".__('No threads')."</p>\n");
} else {
    echo('<ul class="tdiscus-threads-list" role="list" aria-label="'.htmlspecialchars(__('Discussion threads')).'">');
    echo('<!-- Total: '.$retval->total." next=".$retval->next."-->\n");
    foreach($threads as $thread ) {
        $pin = $thread['pin'];
        $locked = $thread['locked'];
        $hidden = $thread['hidden'];
        $thread_id = $thread['thread_id'];
        $subscribe = $thread['subscribe'];
        $favorite = $thread['favorite'];
        $unread = intval($thread['comments']) - intval($thread['user_comments']);
        if ( $unread < 0 ) $unread = 0;
?>
  <li class="tdiscus-thread-item">
  <div class="tdiscus-thread-item-left">
  <p class="tdiscus-thread-item-title">
  <?php
    if ( $LAUNCH->user->instructor ) {
        $TDISCUS->renderBooleanSwitch('thread', $thread_id, 'pin', 'pin', $pin, 0, 'fa-thumbtack fa-rotate-270', 'orange');
        $TDISCUS->renderBooleanSwitch('thread', $thread_id, 'hidden', 'hide', $hidden, 0, 'fa-eye-slash', 'orange');
        $TDISCUS->renderBooleanSwitch('thread', $thread_id, 'locked', 'lock', $locked, 0, 'fa-lock', 'orange');
        $TDISCUS->renderBooleanSwitch('threaduser', $thread_id, 'favorite', 'favorite', $favorite, 0, 'fa-star', 'green');
        // $TDISCUS->renderBooleanSwitch('threaduser', $thread_id, 'subscribe', 'subscribe', $subscribe, 0, 'fa-envelope', 'green');
    } else {
        echo('<span '.($pin == 0 ? 'style="display:none;"' : '').' aria-hidden="true"><i class="fa fa-thumbtack fa-rotate-270" style="color: orange;"></i></span>');
        echo(' <span '.($locked == 0 ? 'style="display:none;"' : '').' aria-hidden="true"><i class="fa fa-lock fa-rotate-270" style="color: orange;"></i></span>');
    }
    $unread_str = '';
    if ( $unread > 0 ) {
        $badge_class = 'tdiscus-thread-item-title-badge tdiscus-thread-item-title-badge-global';
        $badge_label = sprintf(__('%d global unread'), $unread);
        $is_personal = intval($thread['mention_unread']) > 0 ||
            (intval($thread['personal_unread']) > 0 && intval($thread['user_comments']) > 0);
        if ( $is_personal ) {
            $badge_class = 'tdiscus-thread-item-title-badge tdiscus-thread-item-title-badge-personal';
            $badge_label = sprintf(__('%d personal unread'), $unread);
        } else if ( intval($thread['participating_unread']) > 0 ) {
            $badge_class = 'tdiscus-thread-item-title-badge tdiscus-thread-item-title-badge-participating';
            $badge_label = sprintf(__('%d participating unread'), $unread);
        }
        $unread_str = ' <span class="'.$badge_class.'" aria-label="'.htmlspecialchars($badge_label).'">'.$unread.'</span><!-- dbg u='.
            intval($unread).
            ' p='.intval($thread['personal_unread']).
            ' m='.intval($thread['mention_unread']).
            ' t='.intval($thread['participating_unread']).
            ' uc='.intval($thread['user_comments']).
            ' -->';
    }
?>
  <a href="<?= $tool_root.'/thread/'.$thread['thread_id'] ?>">
  <b<?= ($hidden ? ' style="text-decoration: line-through;"' : '') ?>><?= htmlentities($thread['title'] ?? '') ?></b></a>
<?php if ( $thread['owned'] || $LAUNCH->user->instructor ) { ?>
    <span class="tdiscus-thread-owned-menu" role="group" aria-label="<?= htmlspecialchars(__('Thread actions')) ?>">
    <a href="<?= $tool_root ?>/threadform/<?= $thread['thread_id'] ?>" aria-label="<?= htmlspecialchars(__('Edit thread')) ?>"><i class="fa fa-pencil" aria-hidden="true"></i></a>
    <a href="<?= $tool_root ?>/threadremove/<?= $thread['thread_id'] ?>" aria-label="<?= htmlspecialchars(__('Delete thread')) ?>"><i class="fa fa-trash" aria-hidden="true"></i></a>
  <?php
    if ( $LAUNCH->user->instructor ) {
        $TDISCUS->renderBooleanSwitch('thread', $thread_id, 'pin', 'pin', $pin, 1, 'fa-thumbtack');
        $TDISCUS->renderBooleanSwitch('thread', $thread_id, 'hidden', 'hide', $hidden, 1, 'fa-eye-slash');
        $TDISCUS->renderBooleanSwitch('thread', $thread_id, 'locked', 'lock', $locked, 1, 'fa-lock');
        $TDISCUS->renderBooleanSwitch('threaduser', $thread_id, 'favorite', 'favorite', $favorite, 1, 'fa-star');
        // $TDISCUS->renderBooleanSwitch('threaduser', $thread_id, 'subscribe', 'subscribe', $subscribe, 1, 'fa-envelope');
        // TODO: Make this work
        // $TDISCUS->renderBooleanSwitch('threaduser', $thread_id, 'comments', 'catch up', $unread, 1, 'fa-check');
    }
?>
    </span>
<?php } ?>
<?= $unread_str ?>
</p>
<?php
    if ( $thread['staffcreate'] > 0 ) {
        echo('<span class="tdiscus-staff-created">'.__('Staff Created').'</span>');
        echo(" ".__("Created by")." ");
        echo('<span class="tdiscus-user-name">'.htmlentities($thread['displayname'] ?? '').'</span>');
        echo(' - '.__("last post").' <time class="timeago" datetime="'.$thread['modified_at'].'">'.$thread['modified_at'].'</time>');
    } else {
        if ( $thread['staffread'] > 0 ) echo('<span class="tdiscus-staff-read">'.__('Staff Read')."</span>\n");
        if ( $thread['staffanswer'] > 0 ) echo('<span class="tdiscus-staff-answer">'.__('Staff Answer')."</span>\n");
        echo(__("Last post").' <time class="timeago" datetime="'.$thread['modified_at'].'">'.$thread['modified_at']."</time>\n");
    }
?>
  </div>
  <div class="tdiscus-thread-item-right" role="group" aria-label="<?= htmlspecialchars(__('Thread stats')) ?>">
   <span><?= __('Views') ?>: <?= $thread['views'] ?></span><br/>
   <span><?= __('Comments') ?>: <?= $thread['comments'] ?></span>
  </div>
  </li>
<?php
    }
  echo("</ul>");
  $TDISCUS->paginator($comeback, $start, $pagesize, $retval->total);
}

$OUTPUT->footerStart();
$TDISCUS->footer();
$TDISCUS->renderBooleanScript();
$OUTPUT->footerEnd();

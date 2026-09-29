<?php

namespace Tsugi\Controllers;

use Tsugi\Core\ReqScope;
use Tsugi\Core\User;
use Tsugi\Services\Discussions\DiscussionsService;
use Tsugi\UI\SettingsDialog;
use Tsugi\Util\U;

/**
 * Discussion pages shared by the controller and the tool/tdiscus launch shell.
 *
 * Page methods return a path to redirect to, or null after they have rendered.
 */
class DiscussionsUi {

    const paginator_width = 7;

    /** @var DiscussionUrls|null */
    private static $urls = null;

    public static function bind(DiscussionUrls $urls) {
        self::$urls = $urls;
    }

    public static function toolUrls() {
        return DiscussionUrls::forTool();
    }

    /**
     * @param callable|null $afterComment
     * @return string|null
     */
    public static function threadListPage() {
        global $CFG, $OUTPUT;

        $urls = self::urls();
        $launchSettings = self::useLaunchSettings();
        $settingsDialog = null;
        if ( $launchSettings ) {
            $settingsDialog = new SettingsDialog();
            if ( $settingsDialog->handleSettingsPost() ) {
                if ( ! Tool::checkCsrf() ) {
                    U::flashError('Missing or invalid CSRF token');
                } else {
                    $_SESSION['success'] = __('Settings updated');
                }
                return $urls->home();
            }
        } else if ( ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['tsugi_discussion_settings']) ) {
            if ( ! Tool::csrfOk() ) {
                U::flashError('Missing or invalid CSRF token');
                return $urls->home();
            }
            if ( ! DiscussionsService::instructor() ) {
                $_SESSION['error'] = __('You must be an instructor to change discussion settings.');
                return $urls->home();
            }
            $maxdepth = trim((string) U::get($_POST, 'maxdepth', '2'));
            if ( $maxdepth === '' || ! is_numeric($maxdepth) ) {
                $maxdepth = '2';
            }
            $timezone = trim((string) U::get($_POST, 'timezone', 'Pacific/Honolulu'));
            if ( ! in_array($timezone, timezone_identifiers_list(), true) ) {
                $timezone = 'Pacific/Honolulu';
            }
            $penaltyTime = trim((string) U::get($_POST, 'penalty_time', '86400'));
            if ( $penaltyTime === '' || ! is_numeric($penaltyTime) ) {
                $penaltyTime = '86400';
            }
            $penaltyCost = trim((string) U::get($_POST, 'penalty_cost', '0.05'));
            if ( $penaltyCost === '' || ! is_numeric($penaltyCost) ) {
                $penaltyCost = '0.05';
            }
            DiscussionsService::saveLinkSettings(array(
                'title' => trim((string) U::get($_POST, 'title', '')),
                'grade' => isset($_POST['grade']) ? '1' : '0',
                'commenttop' => isset($_POST['commenttop']) ? '1' : '0',
                'maxdepth' => (string) intval($maxdepth),
                'badge_include_participating' => isset($_POST['badge_include_participating']) ? '1' : '0',
                'badge_participation_personal' => isset($_POST['badge_participation_personal']) ? '1' : '0',
                'due' => trim((string) U::get($_POST, 'due', '')),
                'timezone' => $timezone,
                'penalty_time' => (string) intval($penaltyTime),
                'penalty_cost' => (string) $penaltyCost,
            ));
            $_SESSION['success'] = __('Settings updated');
            return $urls->home();
        }

        $menu = false;
        if ( DiscussionsService::instructor() ) {
            $menu = new \Tsugi\UI\MenuSet();
            if ( $CFG->launchactivity ) {
                $menu->addRight(__('Analytics'), U::addSession($urls->analytics()));
            }
            $menu->addRight(__('Settings'), '#', false, 'data-toggle="modal" data-target="#settings"');
        }

        $pagesize = intval(U::get($_GET, 'pagesize', DiscussionsService::default_page_size));
        $start = intval(U::get($_GET, 'start', 0));
        $comeback = $urls->home();
        foreach ( array('search', 'sort', 'pagesize') as $parm ) {
            $val = U::get($_GET, $parm, "");
            if ( strlen($val) == 0 ) {
                continue;
            }
            $comeback = U::add_url_parm($comeback, $parm, $val);
        }

        $retval = DiscussionsService::threads();
        $threads = $retval->rows;
        $has_read_baseline = DiscussionsService::hasReadBaselineForCurrentContext();
        $sortable = DiscussionsService::threadsSortableBy();
        $title = DiscussionsService::discussionTitle();

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav($menu);

        echo('<div class="tdiscus-threads-header">');
        echo('<span class="tdiscus-threads-title">');
        echo('<a href="'.htmlspecialchars(U::addSession($comeback)).'">');
        echo(htmlentities($title));
        echo('</a></span>');
        echo('<a class="tdiscus-add-thread-link" href="'.htmlspecialchars(U::addSession($urls->threadForm())).'" aria-label="'.htmlspecialchars(__('Add Thread')).'">');
        echo('<i class="fa fa-plus" aria-hidden="true"></i> ');
        echo(__('Add Thread'));
        echo('</a>');
        echo('</div><br clear="all"/>'."\n");

        if ( $settingsDialog ) {
            $settingsDialog->start();
            $settingsDialog->text('title', __('Discussion title override.'));
            $settingsDialog->checkbox('grade', __('Give a 100% grade for a student making a post or a comment.'));
            $settingsDialog->checkbox('commenttop', __('Put comment box before comments in thread display.'));
            $settingsDialog->number('maxdepth', __('Allowed depth of nested comments. Default is 2. Set to 1 for no nested comments.'));
            $settingsDialog->checkbox('badge_include_participating', __('Include participating thread activity in main badge.'));
            $settingsDialog->checkbox('badge_participation_personal', __('Treat participating thread activity as personal unread.'));
            $settingsDialog->dueDate();
            $settingsDialog->end();
        } else if ( DiscussionsService::instructor() ) {
            self::renderControllerSettings();
        }

        $OUTPUT->flashMessages();
        self::searchBox($sortable);

        if ( count($threads) < 1 ) {
            echo("<p>".__('No threads')."</p>\n");
        } else {
            echo('<ul class="tdiscus-threads-list" role="list" aria-label="'.htmlspecialchars(__('Discussion threads')).'">');
            echo('<!-- Total: '.$retval->total." next=".$retval->next."-->\n");
            foreach ( $threads as $thread ) {
                self::renderThreadRow($thread, $has_read_baseline);
            }
            echo("</ul>");
            self::paginator($comeback, $start, $pagesize, $retval->total);
        }

        self::finish(true, false);
        return null;
    }

    /**
     * Instructor analytics for this discussion's link. The tool shell keeps its own /analytics page.
     *
     * @return string|null
     */
    public static function analyticsPage() {
        global $CFG, $OUTPUT;

        $urls = self::urls();
        if ( ! DiscussionsService::instructor() ) {
            $_SESSION['error'] = __('You must be an instructor to view analytics.');
            return $urls->home();
        }
        $rc = ReqScope::current();
        $linkId = ($rc && $rc->link) ? (int) $rc->link->id : 0;
        if ( $linkId < 1 ) {
            $_SESSION['error'] = __('Cannot find that discussion in this course');
            return $urls->home();
        }

        $analytics_url = $CFG->wwwroot.'/api/analytics_cookie.php?link_id='.intval($linkId);
        $menu = new \Tsugi\UI\MenuSet();
        $menu->addLeft(__('Back'), U::addSession($urls->home()));

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav($menu);
        $OUTPUT->flashMessages();
        ?>
        <main class="container" id="main-content">
            <h1><?= __('Analytics') ?></h1>
            <?= Analytics::graphBody() ?>
        </main>
        <?php
        $OUTPUT->footerStart();
        echo(Analytics::graphScript($analytics_url));
        $OUTPUT->footerEnd();
        return null;
    }

    /**
     * @param callable|null $afterComment Called after a successful reply. The tool uses this for grade passback.
     * @return string|null
     */
    public static function threadPage($threadId, $afterComment = null) {
        global $OUTPUT;

        $urls = self::urls();
        $threadId = intval($threadId);
        $comeBack = $urls->thread($threadId);

        if ( count($_POST) > 0 ) {
            if ( ! Tool::csrfOk() ) {
                U::flashError('Missing or invalid CSRF token');
                return $comeBack;
            }
            $thread = DiscussionsService::threadLoad($threadId);
            if ( ! is_array($thread) ) {
                $_SESSION['error'] = __('Could not load thread');
                return $urls->home();
            }
            $locked = intval($thread['locked']) && ! DiscussionsService::instructor();
            if ( $locked ) {
                $_SESSION['error'] = __('This thread is locked');
                return $comeBack;
            }
            $retval = DiscussionsService::commentInsertDao($thread, U::get($_POST, 'comment'));
            if ( is_string($retval) ) {
                $_SESSION['error'] = $retval;
                return $comeBack;
            }
            if ( is_callable($afterComment) ) {
                $afterComment();
            }
            return $comeBack;
        }

        $thread = DiscussionsService::threadLoadMarkRead($threadId);
        if ( ! is_array($thread) ) {
            $_SESSION['error'] = __('Could not load thread');
            return $urls->home();
        }

        $thread_locked = intval($thread['locked']) && ! DiscussionsService::instructor();
        $retval = DiscussionsService::comments($threadId);
        $comments = $retval->rows;
        $pagesize = intval(U::get($_GET, 'pagesize', DiscussionsService::default_page_size));
        $start = intval(U::get($_GET, 'start', 0));
        $page_base = $comeBack;
        foreach ( array('search', 'sort', 'pagesize') as $parm ) {
            $val = U::get($_GET, $parm, "");
            if ( strlen($val) == 0 ) {
                continue;
            }
            $page_base = U::add_url_parm($page_base, $parm, $val);
        }
        $commenttop = (DiscussionsService::linkSetting('commenttop') == 1);
        $purifier = DiscussionsService::getPurifier();

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav(false);
        $OUTPUT->flashMessages();

        echo('<div class="tdiscus-thread-container">'."\n");
        echo('<p>');
        echo('<a class="tdiscus-all-threads-link" href="'.htmlspecialchars(U::addSession($urls->home())).'" title="'.htmlspecialchars(__('All threads')).'">'.htmlentities(DiscussionsService::discussionTitle()).'</a>');
        echo("</p>\n");
        echo('<h1 class="tdiscus-thread-title">');
        self::renderBooleanSwitch('threaduser', $threadId, 'subscribe', 'subscribe', intval($thread['subscribe']), 0, 'fa-bell', 'orange');
        echo('<a href="'.htmlspecialchars(U::addSession($page_base)).'"'.($thread['hidden'] ? ' style="text-decoration: line-through;"' : '').'>'.htmlentities($thread['title'] ?? '').'</a>');
        echo(' <span class="tdiscus-thread-subscribe-menu" role="group" aria-label="'.htmlspecialchars(__('Thread subscription actions')).'" style="font-size:0.7em;">');
        self::renderBooleanSwitch('threaduser', $threadId, 'subscribe', 'subscribe', intval($thread['subscribe']), 1, 'fa-bell', '#337ab7');
        echo('</span>');
        echo("</h1>\n");
        ?>
<p class="tdiscus-thread-info">
<span class="tdiscus-user-name"><?= self::displayNameHtmlFromRow($thread) ?></span>
 -
<time class="timeago" datetime="<?= $thread['modified_at'] ?>"><?= $thread['modified_at'] ?></time>
<?php if ( $thread['edited'] == 1 ) {
    echo(" - ".__("edited"));
} ?>
</p>
<p class="tdiscus-thread-body">
<?= $purifier->purify($thread['body']) ?>
</p>
</div>
<?php
        if ( count($comments) > 0 ) {
            ?>
<h2 class="visually-hidden"><?= __('Comments') ?></h2>
<div class="tdiscus-comments-container" role="region" aria-label="<?= htmlspecialchars(__('Comments')) ?>">
<div class="tdiscus-comments-sort">
<?php
            self::searchBox(DiscussionsService::commentsSortableBy());
            if ( $commenttop && ! $thread_locked ) {
                self::addComment($threadId);
            }
            ?>
</div>
<div class="tdiscus-comments-list" role="list" aria-label="<?= htmlspecialchars(__('Comment list')) ?>">
<?php
            foreach ( $comments as $comment ) {
                self::renderComment($threadId, $comment);
            }
            ?>
</div>
</div>
<?php
            self::paginator($page_base, $start, $pagesize, $retval->total);
        }

        if ( ! $commenttop && ! $thread_locked ) {
            self::addComment($threadId);
        }

        self::finish(true, false);
        return null;
    }

    /**
     * @param int|null $threadId
     * @return string|null
     */
    public static function threadFormPage($threadId = null) {
        global $OUTPUT;

        $urls = self::urls();
        $threadId = $threadId ? intval($threadId) : null;
        $old = null;
        $comeBack = $urls->threadForm($threadId);
        if ( $threadId ) {
            $old = DiscussionsService::threadLoadForUpdate($threadId);
            if ( ! is_array($old) ) {
                $_SESSION['error'] = __('Could not load thread');
                return $urls->home();
            }
        }

        if ( count($_POST) > 0 ) {
            if ( ! Tool::csrfOk() ) {
                U::flashError('Missing or invalid CSRF token');
                return $comeBack;
            }
            if ( $old ) {
                $retval = DiscussionsService::threadUpdate($threadId, $_POST);
                if ( is_string($retval) ) {
                    $_SESSION['error'] = $retval;
                    return $comeBack;
                }
                $_SESSION['success'] = __('Thread updated');
                return $urls->home();
            }
            $retval = DiscussionsService::threadInsert($_POST);
            if ( is_string($retval) ) {
                $_SESSION['error'] = $retval;
                return $comeBack;
            }
            $_SESSION['success'] = __('Thread added');
            return $urls->home();
        }

        $purifier = DiscussionsService::getPurifier();
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav(false);
        $OUTPUT->flashMessages();
        if ( $old ) {
            echo("<h1 id=\"add-thread-heading\">".__('Editing Thread')."</h1>\n");
        } else {
            echo("<h1 id=\"add-thread-heading\">".__('New Thread')."</h1>\n");
        }
        ?>
<div id="add-thread-div" title="<?= __("New Thread") ?>" role="region" aria-labelledby="add-thread-heading">
<form id="add-thread-form" method="post">
<?= Tool::csrfField() ?>
<p>
<label for="add-thread-title"><?= __("Title:") ?></label><br/>
<input type="text" id="add-thread-title" name="title" class="form-control" aria-required="true"
<?php
        if ( $old ) {
            echo('value="'.htmlentities($old['title'] ?? '').'" ');
        }
?>
>
</p>
<p>
<label for="editor"><?= __("Description:") ?></label><br/>
<textarea id="editor" name="body" class="form-control" aria-required="true">
<?php
        if ( $old ) {
            echo($purifier->purify($old['body']));
        }
?>
</textarea>
</p>
<p>
<input type="submit" id="add-thread-submit" value="<?= ($old ? __('Update') : __('+ Thread')) ?>" >
<button type="button" id="add-thread-cancel" onclick='window.location.href="<?= htmlspecialchars(U::addSession($urls->home())) ?>";'><?= __('Cancel') ?></button>
</p>
</form>
</div>
<?php
        self::finish(false, true);
        return null;
    }

    /**
     * @return string|null
     */
    public static function threadRemovePage($threadId) {
        global $OUTPUT;

        $urls = self::urls();
        $threadId = intval($threadId);
        $old = DiscussionsService::threadLoadForUpdate($threadId);
        if ( ! is_array($old) ) {
            $_SESSION['error'] = __('Could not load thread');
            return $urls->home();
        }
        $comeBack = $urls->threadRemove($threadId);
        if ( count($_POST) > 0 ) {
            if ( ! Tool::csrfOk() ) {
                U::flashError('Missing or invalid CSRF token');
                return $comeBack;
            }
            $retval = DiscussionsService::threadDelete($threadId);
            if ( is_string($retval) ) {
                $_SESSION['error'] = $retval;
                return $comeBack;
            }
            $_SESSION['success'] = __('Thread deleted');
            return $urls->home();
        }

        $purifier = DiscussionsService::getPurifier();
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav(false);
        $OUTPUT->flashMessages();
        echo("<h1 id=\"delete-thread-heading\">".__('Delete Thread')."</h1>\n");
        ?>
<div id="delete-thread-div" title="<?= __("Delete thread") ?>" role="region" aria-labelledby="delete-thread-heading">
<p><?= __("Title:") ?><br/>
<?php echo('<b>'.htmlentities($old['title'] ?? '').'</b><br/>'); ?>
</p>
<p><?= __("Description:") ?><br/>
<?= $purifier->purify($old['body']) ?>
</p>
<form id="delete-thread-form" method="post">
<?= Tool::csrfField() ?>
<p>
<input type="submit" id="delete-thread-submit" value="<?= __('Delete') ?>" >
<button type="button" id="delete-thread-cancel" onclick='window.location.href="<?= htmlspecialchars(U::addSession($urls->home())) ?>";'><?= __('Cancel') ?></button>
</p>
</form>
</div>
<?php
        self::finish(false, false);
        return null;
    }

    /**
     * @return string|null
     */
    public static function commentFormPage($commentId) {
        global $OUTPUT;

        $urls = self::urls();
        $commentId = intval($commentId);
        $old = DiscussionsService::commentLoadForUpdate($commentId);
        if ( ! is_array($old) ) {
            $_SESSION['error'] = __('Could not load comment');
            return $urls->home();
        }
        $threadId = $old['thread_id'];
        $comeBack = $urls->commentForm($commentId);
        if ( count($_POST) > 0 ) {
            if ( ! Tool::csrfOk() ) {
                U::flashError('Missing or invalid CSRF token');
                return $comeBack;
            }
            $retval = DiscussionsService::commentUpdateDao($old, U::get($_POST, 'comment'));
            if ( is_string($retval) ) {
                $_SESSION['error'] = $retval;
                return $comeBack;
            }
            $_SESSION['success'] = __('Comment updated');
            return $urls->thread($threadId);
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav(false);
        $OUTPUT->flashMessages();
        echo("<h1 id=\"edit-comment-heading\">".__('Edit Comment')."</h1>\n");
        ?>
<div id="edit-comment-div" title="<?= __("Comment") ?>" role="region" aria-labelledby="edit-comment-heading">
<form id="edit-comment-form" method="post">
<?= Tool::csrfField() ?>
<p>
<label for="edit-comment-input"><?= __("Comment:") ?></label><br/>
<input type="text" id="edit-comment-input" name="comment" class="form-control" aria-required="true"
value="<?= htmlentities($old['comment'] ?? '') ?>"
>
</p>
<p>
<input type="submit" id="edit-comment-submit" value="<?= __('Update') ?>" >
<button type="button" id="edit-comment-cancel" onclick='window.location.href="<?= htmlspecialchars(U::addSession($urls->thread($threadId))) ?>";'><?= __('Cancel') ?></button>
</p>
</form>
</div>
<?php
        self::finish(false, false);
        return null;
    }

    /**
     * @return string|null
     */
    public static function commentRemovePage($commentId) {
        global $OUTPUT;

        $urls = self::urls();
        $commentId = intval($commentId);
        $old = DiscussionsService::commentLoadForUpdate($commentId);
        if ( ! is_array($old) || ! isset($old['thread_id']) ) {
            $_SESSION['error'] = __('Could not load comment');
            return $urls->home();
        }
        $threadId = $old['thread_id'];
        $comeBack = $urls->commentRemove($commentId);
        if ( count($_POST) > 0 ) {
            if ( ! Tool::csrfOk() ) {
                U::flashError('Missing or invalid CSRF token');
                return $comeBack;
            }
            $retval = DiscussionsService::commentDelete($commentId, $threadId);
            if ( is_string($retval) ) {
                $_SESSION['error'] = $retval;
                return $comeBack;
            }
            $_SESSION['success'] = __('Comment deleted');
            return $urls->thread($threadId);
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav(false);
        $OUTPUT->flashMessages();
        echo("<h1 id=\"delete-comment-heading\">".__('Delete Comment')."</h1>\n");
        ?>
<div id="delete-comment-div" title="<?= __("Delete comment") ?>" role="region" aria-labelledby="delete-comment-heading">
<form id="delete-comment-form" method="post">
<?= Tool::csrfField() ?>
<p><?= __("Comment:") ?><br/>
<?php echo('<b>'.htmlentities($old['comment'] ?? '').'</b><br/>'); ?>
</p>
<p>
<input type="submit" id="delete-comment-submit" value="<?= __('Delete') ?>" >
<button type="button" id="delete-comment-cancel" onclick='window.location.href="<?= htmlspecialchars(U::addSession($urls->thread($threadId))) ?>";'><?= __('Cancel') ?></button>
</p>
</form>
</div>
<?php
        self::finish(false, false);
        return null;
    }

    /**
     * @return string|null Error text, or null after the fragment is rendered
     */
    public static function apiAddSubComment($afterComment = null) {
        if ( ! Tool::csrfOk() ) {
            return 'Missing or invalid CSRF token';
        }
        $thread_id = U::get($_POST, 'thread_id');
        $comment_id = U::get($_POST, 'comment_id');
        $comment = U::get($_POST, 'comment');
        $retval = DiscussionsService::commentAddSubComment($thread_id, $comment_id, $comment);
        if ( is_string($retval) ) {
            return $retval;
        }
        if ( is_callable($afterComment) ) {
            $afterComment();
        }
        $loaded = DiscussionsService::commentLoad($retval);
        if ( ! is_array($loaded) ) {
            return __('Could not load comment');
        }
        self::renderComment(intval($thread_id), $loaded);
        return null;
    }

    /**
     * @return string|null
     */
    public static function apiSetBoolean($kind, $id, $column, $value) {
        if ( ! Tool::csrfOk() ) {
            return 'Missing or invalid CSRF token';
        }
        if ( $kind === 'thread' ) {
            $retval = DiscussionsService::threadSetBoolean($id, $column, $value);
        } else if ( $kind === 'threaduser' ) {
            $retval = DiscussionsService::threadUserSetBoolean($id, $column, $value);
        } else if ( $kind === 'comment' ) {
            $retval = DiscussionsService::commentSetBoolean($id, $column, $value);
        } else {
            $retval = __('Missing required parameters');
        }
        if ( is_string($retval) ) {
            return $retval;
        }
        return null;
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function displayNameHtmlFromRow($row) {
        return User::displayNameHtml(
            U::get($row, 'displayname', ''),
            (int) U::get($row, 'premium', 0)
        );
    }

    private static function renderControllerSettings() {
        $urls = self::urls();
        $title = DiscussionsService::linkSetting('title', '');
        if ( ! is_string($title) ) {
            $title = '';
        }
        $maxdepth = DiscussionsService::linkSetting('maxdepth', '2');
        $grade = intval(DiscussionsService::linkSetting('grade', '0')) > 0;
        $commenttop = intval(DiscussionsService::linkSetting('commenttop', '0')) > 0;
        $badgePart = intval(DiscussionsService::linkSetting('badge_include_participating', '0')) > 0;
        $badgePersonal = intval(DiscussionsService::linkSetting('badge_participation_personal', '0')) > 0;
        $due = DiscussionsService::linkSetting('due', '');
        if ( ! is_string($due) ) {
            $due = '';
        }
        $timezone = DiscussionsService::linkSetting('timezone', 'Pacific/Honolulu');
        if ( ! is_string($timezone) || ! in_array($timezone, timezone_identifiers_list(), true) ) {
            $timezone = 'Pacific/Honolulu';
        }
        $penaltyTime = DiscussionsService::linkSetting('penalty_time', '86400');
        $penaltyCost = DiscussionsService::linkSetting('penalty_cost', '0.05');
        ?>
<div id="settings" class="modal fade" role="dialog" aria-modal="true" aria-labelledby="settings_dialog_title" style="display: none;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlentities(__('Close')) ?>"><span class="fa fa-close" aria-hidden="true"></span></button>
        <h4 id="settings_dialog_title" class="modal-title"><?= __('Settings') ?></h4>
      </div>
      <div class="modal-body">
        <form method="post" action="<?= htmlspecialchars(U::addSession($urls->home())) ?>">
        <?= Tool::csrfField() ?>
        <input type="hidden" name="tsugi_discussion_settings" value="1">
        <div class="form-group">
            <label for="title"><?= __('Discussion title override.') ?></label>
            <input type="text" class="form-control" id="title" name="title" value="<?= htmlentities($title) ?>">
        </div>
        <div class="checkbox">
            <label><input type="checkbox" value="1" name="grade" <?= $grade ? 'checked' : '' ?>>
            <?= __('Give a 100% grade for a student making a post or a comment.') ?></label>
        </div>
        <div class="checkbox">
            <label><input type="checkbox" value="1" name="commenttop" <?= $commenttop ? 'checked' : '' ?>>
            <?= __('Put comment box before comments in thread display.') ?></label>
        </div>
        <div class="form-group">
            <label for="maxdepth"><?= __('Allowed depth of nested comments. Default is 2. Set to 1 for no nested comments.') ?></label>
            <input type="number" class="form-control" id="maxdepth" name="maxdepth" value="<?= htmlentities((string) $maxdepth) ?>">
        </div>
        <div class="checkbox">
            <label><input type="checkbox" value="1" name="badge_include_participating" <?= $badgePart ? 'checked' : '' ?>>
            <?= __('Include participating thread activity in main badge.') ?></label>
        </div>
        <div class="checkbox">
            <label><input type="checkbox" value="1" name="badge_participation_personal" <?= $badgePersonal ? 'checked' : '' ?>>
            <?= __('Treat participating thread activity as personal unread.') ?></label>
        </div>
        <div class="form-group">
            <label for="settings_due"><?= __('Due date') ?></label>
            <input type="text" class="form-control" id="settings_due" name="due" value="<?= htmlentities($due) ?>">
            <small class="form-text text-muted"><?= __('ISO 8601 format (2026-04-06T20:30) or leave blank for no due date. You can leave off the time to allow turn-in any time during the day.') ?></small>
        </div>
        <div class="form-group">
            <label for="settings_timezone"><?= __('Time zone') ?></label>
            <select name="timezone" id="settings_timezone" class="form-control">
<?php
        foreach ( timezone_identifiers_list() as $tz ) {
            echo('<option value="'.htmlspecialchars($tz).'"');
            if ( $tz === $timezone ) {
                echo(' selected="selected"');
            }
            echo('>'.htmlspecialchars($tz)."</option>\n");
        }
?>
            </select>
        </div>
        <div class="form-group">
            <label for="settings_penalty_time"><?= __('Penalty time period (seconds)') ?></label>
            <input type="number" id="settings_penalty_time" class="form-control" name="penalty_time" value="<?= htmlentities((string) $penaltyTime) ?>">
        </div>
        <div class="form-group">
            <label for="settings_penalty_cost"><?= __('Penalty deduction (0.0 to 1.0)') ?></label>
            <input type="number" id="settings_penalty_cost" class="form-control" name="penalty_cost" value="<?= htmlentities((string) $penaltyCost) ?>">
        </div>
        <button type="submit" class="btn btn-primary"><?= __('Save changes') ?></button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php
    }

    /**
     * @param array<string,mixed> $thread
     */
    private static function renderThreadRow($thread, $has_read_baseline) {
        $urls = self::urls();
        $pin = $thread['pin'];
        $locked = $thread['locked'];
        $hidden = $thread['hidden'];
        $thread_id = $thread['thread_id'];
        $subscribe = $thread['subscribe'];
        $favorite = $thread['favorite'];
        $unread = $has_read_baseline ? (intval($thread['comments']) - intval($thread['user_comments'])) : 0;
        if ( $unread < 0 ) {
            $unread = 0;
        }
        $instructor = DiscussionsService::instructor();
        ?>
  <li class="tdiscus-thread-item">
  <div class="tdiscus-thread-item-left">
  <p class="tdiscus-thread-item-title">
<?php
        if ( $instructor ) {
            self::renderBooleanSwitch('thread', $thread_id, 'pin', 'pin', $pin, 0, 'fa-thumbtack fa-rotate-270', 'orange');
            self::renderBooleanSwitch('thread', $thread_id, 'hidden', 'hide', $hidden, 0, 'fa-eye-slash', 'orange');
            self::renderBooleanSwitch('thread', $thread_id, 'locked', 'lock', $locked, 0, 'fa-lock', 'orange');
            self::renderBooleanSwitch('threaduser', $thread_id, 'favorite', 'favorite', $favorite, 0, 'fa-star', 'green');
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
            $unread_str = ' <span class="'.$badge_class.'" aria-label="'.htmlspecialchars($badge_label).'">'.$unread.'</span>';
        }
        self::renderBooleanSwitch('threaduser', $thread_id, 'subscribe', 'subscribe', $subscribe, 0, 'fa-bell', 'orange');
        ?>
  <a href="<?= htmlspecialchars(U::addSession($urls->thread($thread['thread_id']))) ?>">
  <b<?= ($hidden ? ' style="text-decoration: line-through;"' : '') ?>><?= htmlentities($thread['title'] ?? '') ?></b></a>
<?php if ( $thread['owned'] || $instructor ) { ?>
    <span class="tdiscus-thread-owned-menu" role="group" aria-label="<?= htmlspecialchars(__('Thread actions')) ?>">
    <?php
        self::renderBooleanSwitch('threaduser', $thread_id, 'subscribe', 'subscribe', $subscribe, 1, 'fa-bell', '#337ab7');
    ?>
    <a href="<?= htmlspecialchars(U::addSession($urls->threadForm($thread['thread_id']))) ?>" aria-label="<?= htmlspecialchars(__('Edit thread')) ?>"><i class="fa fa-pencil" aria-hidden="true"></i></a>
    <a href="<?= htmlspecialchars(U::addSession($urls->threadRemove($thread['thread_id']))) ?>" aria-label="<?= htmlspecialchars(__('Delete thread')) ?>"><i class="fa fa-trash" aria-hidden="true"></i></a>
<?php
        if ( $instructor ) {
            self::renderBooleanSwitch('thread', $thread_id, 'pin', 'pin', $pin, 1, 'fa-thumbtack');
            self::renderBooleanSwitch('thread', $thread_id, 'hidden', 'hide', $hidden, 1, 'fa-eye-slash');
            self::renderBooleanSwitch('thread', $thread_id, 'locked', 'lock', $locked, 1, 'fa-lock');
            self::renderBooleanSwitch('threaduser', $thread_id, 'favorite', 'favorite', $favorite, 1, 'fa-star');
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
            echo('<span class="tdiscus-user-name">'.self::displayNameHtmlFromRow($thread).'</span>');
            echo(' - '.__("last post").' <time class="timeago" datetime="'.$thread['modified_at'].'">'.$thread['modified_at'].'</time>');
        } else {
            if ( $thread['staffread'] > 0 ) {
                echo('<span class="tdiscus-staff-read">'.__('Staff Read')."</span>\n");
            }
            if ( $thread['staffanswer'] > 0 ) {
                echo('<span class="tdiscus-staff-answer">'.__('Staff Answer')."</span>\n");
            }
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

    /**
     * @param array<string,mixed> $comment
     */
    public static function renderComment($thread_id, $comment) {
        $urls = self::urls();
        $locked = $comment['locked'];
        $hidden = $comment['hidden'];
        $depth = $comment['depth'];
        $comment_id = $comment['comment_id'];
        $unique = '_'.$thread_id.'_'.$comment_id;
        $instructor = DiscussionsService::instructor();

        if ( $depth < 3 ) {
            $indent = ($depth * 10);
        } else {
            $indent = 20 + ($depth-3) * 2;
        }

        $comment_author = $comment['displayname'] ?? '';
        echo('<article id="tdiscus-comment-container-'.$comment_id.'" class="tdiscus-comment-container" style="padding-left:'.$indent.'px;" aria-label="'.htmlspecialchars(sprintf(__('Comment by %s'), $comment_author)).'">'."\n");

        if ( $instructor ) {
            self::renderBooleanSwitch('comment', $comment_id, 'hidden', 'hide', $hidden, 0, 'fa-eye-slash', 'orange');
            self::renderBooleanSwitch('comment', $comment_id, 'locked', 'lock', $locked, 0, 'fa-lock', 'orange');
        } else {
            echo('<span '.($locked == 0 ? 'style="display:none;"' : '').' aria-hidden="true"><i class="fa fa-lock fa-rotate-270" style="color: orange;"></i></span>');
        }
        ?>
  <span class="tdiscus-user-name"><?= self::displayNameHtmlFromRow($comment) ?></span>
  <time class="timeago" datetime="<?= $comment['modified_at'] ?>"><?= $comment['modified_at'] ?></time>
<?php if ( $comment['owned'] || $instructor ) { ?>
    <a href="<?= htmlspecialchars(U::addSession($urls->commentForm($comment['comment_id']))) ?>" aria-label="<?= htmlspecialchars(__("Edit comment")) ?>"><i class="fa fa-pencil" aria-hidden="true"></i></a>
    <a href="<?= htmlspecialchars(U::addSession($urls->commentRemove($comment['comment_id']))) ?>" aria-label="<?= htmlspecialchars(__("Delete comment")) ?>"><i class="fa fa-trash" aria-hidden="true"></i></a>
<?php } ?>
<?php
        if ( $instructor ) {
            self::renderBooleanSwitch('comment', $comment_id, 'hidden', 'hide', $hidden, 1, 'fa-eye-slash');
            self::renderBooleanSwitch('comment', $comment_id, 'locked', 'lock', $locked, 1, 'fa-lock');
        }
        $id = "tdiscus-add-sub-comment-div$unique";
        if ( DiscussionsService::maxDepth() > 0 && ($depth+1) < DiscussionsService::maxDepth() ) {
            self::renderToggle(__('reply'), $id, 'fa-comment', 'green');
        }
        ?>
  <br/>
  <div style="padding-left: 10px;<?= ($hidden ? ' text-decoration: line-through;' : '') ?>"><?= htmlentities($comment['comment'] ?? '') ?></div>
<?php
        if ( DiscussionsService::maxDepth() > 0 ) {
            echo('<div class="tdiscus-sub-comment-container">'."\n");
            self::addSubComment($id, $thread_id, $comment_id);
            echo('</div>');
        }
        echo('</article>');
    }

    private static function addComment($thread_id) {
        ?>
<div id="tdiscus-add-comment-div" class="tdiscus-add-comment-container" title="<?= __("Reply") ?>" role="region" aria-label="<?= htmlspecialchars(__("Add reply")) ?>">
<form id="tdiscus-add-comment-form" method="post">
<?= Tool::csrfField() ?>
<p>
<label for="tdiscus-add-comment-text-<?= $thread_id ?>" class="tdiscus-visually-hidden"><?= __("Your reply") ?></label>
<textarea id="tdiscus-add-comment-text-<?= $thread_id ?>" style="width:100%;" class="tdiscus-add-sub-comment-text form-control" name="comment" aria-label="<?= htmlspecialchars(__("Your reply")) ?>">
</textarea>
</p>
<p>
<input type="submit" id="tdiscus-add-comment-submit" name="submit" value="<?= __('Reply') ?>" >
</p>
</form>
</div>
<?php
    }

    private static function addSubComment($html_id, $thread_id, $comment_id) {
        $textarea_id = $html_id . '-textarea';
        ?>
<div id="<?= $html_id ?>" class="tdiscus-add-sub-comment-container" title="<?= __("Reply") ?>" role="region" aria-label="<?= htmlspecialchars(__("Add reply")) ?>" style="display:none;">
<form method="post"
    data-click-done="<?= $html_id ?>_toggle"
    class="tdiscus-add-sub-comment-form">
<?= Tool::csrfField() ?>
<p>
<input type="hidden" name="comment_id" value="<?= $comment_id ?>">
<input type="hidden" name="thread_id" value="<?= $thread_id ?>">
<label for="<?= $textarea_id ?>" class="tdiscus-visually-hidden"><?= __("Your reply") ?></label>
<textarea id="<?= $textarea_id ?>" style="width:100%;" class="tdiscus-add-sub-comment-text form-control" name="comment" aria-label="<?= htmlspecialchars(__("Your reply")) ?>">
</textarea>
</p>
<p>
<input type="submit" name="submit" value="<?= __('Reply') ?>" >
</p>
</form>
</div>
<?php
    }

    private static function searchBox($sortby = false) {
        $searchvalue = U::get($_GET, 'search') ? 'value="'.htmlentities(U::get($_GET, 'search')).'" ' : "";
        $sortvalue = U::get($_GET, 'sort');
        echo('<div class="tdiscus-threads-search-sort" role="search"><form>'."\n");
        if ( is_array($sortby) ) {
            ?>
<div class="tdiscus-threads-sort">
<label for="sort"><?= __("Sort by") ?></label>
<select name="sort" id="sort" onchange="this.form.submit();" aria-label="<?= htmlspecialchars(__("Sort by")) ?>">
<?php
            foreach ( $sortby as $sort ) {
                echo('<option value="'.$sort.'" '.($sortvalue == $sort ? 'selected="selected"' : '').' >'.__(ucfirst($sort)).'</option>'."\n");
            }
            ?>
</select>
</div>
<?php
        }
        ?>
<div class="tdiscus-threads-search">
  <label for="tdiscus-threads-search-input" class="tdiscus-visually-hidden"><?= __("Search") ?></label>
  <input type="text" id="tdiscus-threads-search-input" placeholder="<?= htmlspecialchars(__("Search")) ?>..." name="search"
  <?= $searchvalue ?> aria-label="<?= htmlspecialchars(__("Search threads")) ?>"
  >
  <button type="submit" aria-label="<?= htmlspecialchars(__("Search")) ?>"><i class="fa fa-search" aria-hidden="true"></i></button>
  <button type="button" onclick='document.getElementById("tdiscus-threads-search-input").value = ""; this.form.submit();' aria-label="<?= htmlspecialchars(__("Clear search")) ?>"><i class="fa fa-undo" aria-hidden="true"></i></button>
</div>
<?php
        echo("</form></div>\n");
    }

    private static function paginator($baseurl, $start, $pagesize, $total) {
        if ( $start == 0 && $total < $pagesize ) {
            return;
        }
        $page_url = function ($offset) use ($baseurl) {
            $parts = parse_url($baseurl);
            $path = isset($parts['path']) ? $parts['path'] : $baseurl;
            $query = array();
            if ( isset($parts['query']) ) {
                parse_str($parts['query'], $query);
            }
            $query['start'] = intval($offset);
            return U::addSession($path . '?' . http_build_query($query));
        };
        $laststart = intval($total / $pagesize) * $pagesize;
        $showpages = self::paginator_width;
        $firststart = $start - (intval($showpages/2) * $pagesize);
        if ( $firststart < 0 ) {
            $firststart = 0;
        }
        ?>
<nav aria-label="Page navigation">
  <ul class="pagination">
  <li class="page-item<?= ($start>0) ? '' : ' disabled'?>">
    <a class="page-link" href="<?= $page_url(0) ?>" aria-label="First">First</a>
  </li>
<?php
        if ( $firststart > 0 ) {
            $prefirststart = $firststart - $pagesize;
            echo('<li class="page-item"><a class="page-link" href="'.$page_url($prefirststart).'">...</a></li>');
        }
        for ( $i = 0; $i < $showpages; $i++ ) {
            if ( $firststart > $laststart ) {
                break;
            }
            $active = ($firststart == $start) ? ' active' : '';
            $pageno = intval($firststart/$pagesize);
            echo('<li class="page-item'.$active.'"><a class="page-link" href="'.$page_url($firststart).'">'.($pageno+1)."</a></li>\n");
            $firststart = $firststart + $pagesize;
        }
        if ( $firststart <= $laststart ) {
            echo('<li class="page-item"><a class="page-link" href="'.$page_url($firststart).'">...</a></li>');
        }
        ?>
    <li class="page-item<?= ($start<$laststart) ? '' : ' disabled'?>">
      <a class="page-link" href="<?= $page_url($laststart) ?>" aria-label="Last">Last</a>
    </li>
  </ul>
</nav>
<?php
    }

    private static function renderBooleanSwitch($type, $thread_id, $variable, $title, $value, $set, $icon, $color = false) {
        $action = ($set ? '' : 'un').$title;
        $uitype = $type;
        if ( $uitype == 'threaduser' ) {
            $uitype = 'thread';
        }
        if ( $uitype == 'threadcomment' ) {
            $uitype = 'comment';
        }
        ?>
        <a href="#"
        class="<?= $type ?><?= $variable ?>_<?= $thread_id ?> tdiscus-boolean-api-call"
        data-class="<?= $type ?><?= $variable ?>_<?= $thread_id ?>"
        data-endpoint="<?= $type ?>setboolean/<?= $thread_id ?>/<?= $variable ?>/<?= $set ?>"
        data-confirm="<?= htmlentities(__('Do you want to '.$action.' this '.$uitype.'?')) ?>"
        title="<?= __(ucfirst($action)." ".ucfirst($uitype)) ?>"
        aria-label="<?= htmlspecialchars(__(ucfirst($action)." ".ucfirst($uitype))) ?>"
        role="button"
         <?= ($value == $set ? 'style="display:none;"' : '') ?>
         ><i class="fa <?= $icon ?>" aria-hidden="true" <?= ($color ? 'style="color: '.$color.'";' : '') ?>></i></a>
<?php
    }

    private static function renderToggle($title, $id, $icon, $color = false) {
        ?>
        <a href="#"
        id="<?= $id ?>_toggle"
        data-id="<?= $id ?>"
        class="tdiscus-toggle-api-call"
        aria-label="<?= htmlspecialchars(__("Toggle").' '.$title) ?>"
        aria-expanded="false"
        role="button"
        title="<?= htmlentities(__("Toggle").' '.$title) ?>">
        <i id="<?= $id ?>_icon_on" class="fa <?= $icon ?>" aria-hidden="true"></i>
        <i id="<?= $id ?>_icon_off" class="fa <?= $icon ?>"
         style="display:none; <?= ($color ? ('color: '.$color.';') : '') ?>" aria-hidden="true"></i></a>
<?php
    }

    private static function renderBooleanScript() {
        global $OUTPUT;
        $apiBase = self::urls()->apiBase();
        ?>
<script>
$(document).ready( function() {
    var tdiscusApiBase = <?= json_encode($apiBase) ?>;
    function handleApiCall(ev) {
        ev.preventDefault()
        if ( ! confirm($(this).attr('data-confirm')) ) return;
        var data_class = $(this).attr('data-class');
        $.ajax({
            url: addSession(tdiscusApiBase + '/' + $(this).attr('data-endpoint')),
            method: 'POST',
            headers: tsugiCsrfHeaders()
        })
            .done( function(data) {
                $('.'+data_class).toggle();
            })
            .error( function(xhr, status, error) {
                var message = <?= json_encode(__('Request Failed')) ?>;
                if ( error && error.length > 0 ) {
                    message = message + ": "+error.substring(0,40);
                }
                alert(message);
            });
    }

    function toggleApiCall(ev) {
        ev.preventDefault()
        var $btn = $(this);
        var data_id = $btn.attr('data-id');
        var $target = $('#'+data_id);
        var isExpanded = $target.is(':visible');
        $target.toggle();
        $('#'+data_id+"_icon_on").toggle();
        $('#'+data_id+"_icon_off").toggle();
        $btn.attr('aria-expanded', !isExpanded);
    }

    function handleSubmit(ev) {
        var comment = $(this).find('textarea[name="comment"]').val();
        ev.preventDefault();
        var ser = $(this).serialize();
        var click_done = $(this).attr('data-click-done');
        var txt3 = document.createElement("p");
        txt3.innerHTML = '<img src="<?= $OUTPUT->getSpinnerUrl() ?>">';
        $(this).closest('.tdiscus-sub-comment-container').prepend(txt3);
        if ( click_done ) $('#'+click_done).click();
        $(this).find('textarea[name="comment"]').val('');
        $.ajax({
            url: addSession(tdiscusApiBase + '/addsubcomment'),
            method: 'POST',
            data: ser,
            headers: tsugiCsrfHeaders()
        })
            .done( function(data) {
                if ( comment.length > 0 ) txt3.innerHTML = data;
                $('.tdiscus-add-sub-comment-form').on('submit', handleSubmit);
                $('.tdiscus-toggle-api-call').click(toggleApiCall);
                $('.tdiscus-boolean-api-call').click(handleApiCall);
            })
            .error( function(xhr, status, error) {
                alert(error);
            });
    }

    $('.tdiscus-add-sub-comment-form').on('submit', handleSubmit);
    $('.tdiscus-boolean-api-call').click(handleApiCall);
    $('.tdiscus-toggle-api-call').click(toggleApiCall);
});
</script>
<?php
    }

    private static function ckeditorLoad() {
        global $CFG;
        echo('<script src="'.$CFG->staticroot.'/util/ckeditor_4.8.0/ckeditor.js"></script>'."\n");
    }

    private static function ckeditorFooter() {
        ?>
<script>
$(document).ready( function () {
    CKEDITOR.replace( 'editor' );
});
</script>
<?php
    }

    private static function finish($withBoolean, $withEditor) {
        global $OUTPUT;
        $OUTPUT->footerStart();
        self::ckeditorLoad();
        if ( $withEditor ) {
            self::ckeditorFooter();
        }
        if ( $withBoolean ) {
            self::renderBooleanScript();
        }
        $OUTPUT->footerEnd();
    }

    private static function ltiOrigin() {
        $rc = ReqScope::current();
        return $rc && $rc->origin === ReqScope::ORIGIN_LTI;
    }

    /**
     * The LTI settings dialog writes the global launch link. Use it only when
     * that link is the discussion on this request.
     */
    private static function useLaunchSettings() {
        global $LINK;
        if ( ! self::ltiOrigin() ) {
            return false;
        }
        $rc = ReqScope::current();
        if ( ! $rc || ! $rc->link || ! isset($LINK) || ! is_object($LINK) || ! isset($LINK->launch) ) {
            return false;
        }
        return (int) $LINK->id === (int) $rc->link->id;
    }

    private static function urls() {
        if ( ! self::$urls ) {
            throw new \RuntimeException('Discussion URLs are not bound');
        }
        return self::$urls;
    }

}

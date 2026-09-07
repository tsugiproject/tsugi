<?php

namespace Tsugi\Controllers;


use \Tsugi\Util\U;
use Tsugi\Util\LTI;
use Tsugi\Core\LTIX;
use Tsugi\Core\Manifest;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class Discussions extends Tool {

    const ROUTE = '/discussions';
    const EXPIRE_DELETE_BATCH_LIMIT = 500;

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Discussions@get');
        $app->router->get($prefix.'/', 'Discussions@get');
        $app->router->get($prefix.'/add', 'Discussions@addForm');
        $app->router->post($prefix.'/add', 'Discussions@addPost');
        $app->router->get($prefix.'/reorder', 'Discussions@reorderForm');
        $app->router->post($prefix.'/reorder', 'Discussions@reorderPost');
        $app->router->get($prefix.'/manage', 'Discussions@manage');
        $app->router->get($prefix.'/scan-fix-unread-tracking', 'Discussions@scanFixUnreadTracking');
        $app->router->get($prefix.'/expire-threads', 'Discussions@expireThreads');
        $app->router->get($prefix.'/expire-comments', 'Discussions@expireComments');
        $app->router->get($prefix.'/json', 'Discussions@json');
        $app->router->post($prefix.'/mark-read', 'Discussions@markRead');
        $app->router->post($prefix.'/reset-unread-tracking', 'Discussions@resetUnreadTracking');
        $app->router->post($prefix.'/scan-fix-unread-tracking-run', 'Discussions@scanFixUnreadTrackingRun');
        $app->router->post($prefix.'/expire-threads-dry-run', 'Discussions@expireThreadsDryRun');
        $app->router->post($prefix.'/expire-comments-dry-run', 'Discussions@expireCommentsDryRun');
        $app->router->get($prefix.'_launch/{anchor}', function(Request $request, $anchor = null) use ($app) {
            return Discussions::launch($app, $anchor);
        });
    }

    public function get(Request $request)
    {
        global $CFG, $OUTPUT;

        $l = Manifest::requireCurrentLessons();

        $add_url = null;
        $reorder_url = null;
        if ( Manifest::activeId() > 0 && $this->isInstructor() ) {
            $add_url = U::addSession($this->toolHome(self::ROUTE) . '/add');
            $reorder_url = U::addSession($this->toolHome(self::ROUTE) . '/reorder');
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $menu = false;
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        echo('<main class="container" id="main-content">');
        $l->renderDiscussions(false, $this->toolHome(self::ROUTE), $add_url, $reorder_url);
        echo('</main>');
        $OUTPUT->footer();


    }

    /**
     * Instructor form to add a course-level discussion (manifest courses only).
     */
    public function addForm(Request $request)
    {
        global $OUTPUT;

        $list_url = U::addSession($this->toolHome(self::ROUTE));
        $gate = $this->addDiscussionGate($list_url);
        if ( $gate ) {
            return $gate;
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" id="main-content">
            <h1><?= __('Add discussion') ?></h1>
            <p><?= __('Creates a new discussion in this course and saves it as a new manifest version.') ?></p>
            <form method="post" action="<?= htmlspecialchars(U::addSession($this->toolHome(self::ROUTE) . '/add')) ?>">
                <?= self::csrfField() ?>
                <p>
                    <label for="discussion_title"><?= __('Title') ?></label><br/>
                    <input type="text" id="discussion_title" name="title" required maxlength="512" style="min-width: 20em;"/>
                </p>
                <p>
                    <button type="submit" class="btn btn-primary"><?= __('Add discussion') ?></button>
                    <a href="<?= htmlspecialchars($list_url) ?>" class="btn btn-default"><?= __('Cancel') ?></a>
                </p>
            </form>
        </main>
        <?php
        $OUTPUT->footer();
        return '';
    }

    /**
     * POST: append a top-level discussion and save a new manifest version.
     */
    public function addPost(Request $request)
    {
        $list_url = U::addSession($this->toolHome(self::ROUTE));
        $form_url = U::addSession($this->toolHome(self::ROUTE) . '/add');
        $gate = $this->addDiscussionGate($list_url);
        if ( $gate ) {
            return $gate;
        }
        $csrf = self::requireCsrf($form_url);
        if ( $csrf ) {
            return $csrf;
        }

        $title = trim((string) U::get($_POST, 'title', ''));
        if ( $title === '' ) {
            U::flashError(__('Title is required.'));
            return new RedirectResponse($form_url);
        }

        $doc = Manifest::currentDocument();
        if ( ! $doc ) {
            U::flashError(__('Cannot find course manifest.'));
            return new RedirectResponse($list_url);
        }
        $data = json_decode($doc['json'], true);
        if ( ! is_array($data) ) {
            U::flashError(__('Cannot parse course manifest.'));
            return new RedirectResponse($list_url);
        }

        try {
            $added = Manifest::appendDiscussion($data, $title);
            Manifest::saveNewVersion(
                U::currentContextId(),
                $added['data'],
                U::loggedInUserId(),
                'Add discussion'
            );
        } catch ( \InvalidArgumentException $e ) {
            U::flashError($e->getMessage());
            return new RedirectResponse($form_url);
        } catch ( \Exception $e ) {
            U::flashError(__('Failed to save discussion.'));
            return new RedirectResponse($form_url);
        }

        U::flashSuccess(__('Discussion added.'));
        return new RedirectResponse($list_url);
    }

    /**
     * Instructor page to drag-reorder discussions (manifest courses only).
     */
    public function reorderForm(Request $request)
    {
        global $OUTPUT;

        $list_url = U::addSession($this->toolHome(self::ROUTE));
        $gate = $this->addDiscussionGate($list_url);
        if ( $gate ) {
            return $gate;
        }

        $l = Manifest::requireCurrentLessons();
        $discussions = $l->flattenedDiscussions();
        if ( count($discussions) < 2 ) {
            U::flashError(__('You need at least two discussions to reorder.'));
            return new RedirectResponse($list_url);
        }

        $save_url = U::addSession($this->toolHome(self::ROUTE) . '/reorder');

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <style>
.tsugi-discussions-sortable { list-style: none; padding-left: 0; max-width: 40em; }
.tsugi-discussions-sortable > li {
    display: flex; align-items: center; gap: 0.5em;
    padding: 0.6em 0.75em; margin-bottom: 0.4em;
    background: #fff; border: 1px solid #ddd; border-radius: 3px;
}
.tsugi-discussion-drag-handle { cursor: grab; color: #999; padding: 0.15em 0.45em; user-select: none; font-size: 1.2em; }
.tsugi-discussion-drag-handle:hover { color: #333; }
.tsugi-discussion-drag-handle:active { cursor: grabbing; }
.tsugi-discussions-sortable .ui-sortable-placeholder {
    height: 2.5em; border: 2px dashed #007bff; visibility: visible !important;
    background: #f0f8ff;
}
.tsugi-reorder-save-bar {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: #333; color: #fff; padding: 15px 20px;
    display: flex; justify-content: space-between; align-items: center;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.2); z-index: 1000;
}
.tsugi-reorder-save-bar.hidden { display: none; }
.tsugi-reorder-save-bar .actions { display: flex; gap: 10px; }
        </style>
        <main class="container" id="main-content" style="padding-bottom: 5em;">
            <p><a href="<?= htmlspecialchars($list_url) ?>" class="btn btn-default btn-sm tsugi-reorder-leave"><?= __('Back to Discussions') ?></a></p>
            <h1><?= __('Reorder discussions') ?></h1>
            <p><?= __('Drag the handle to change the order, then save. Nothing is stored until you save.') ?></p>
            <ul id="tsugi-reorder-list" class="tsugi-discussions-sortable">
                <?php foreach ( $discussions as $discussion ) {
                    $rid = isset($discussion->resource_link_id) ? (string) $discussion->resource_link_id : '';
                    $title = isset($discussion->title) ? (string) $discussion->title : $rid;
                    if ( $rid === '' ) {
                        continue;
                    }
                ?>
                <li data-resource-link-id="<?= htmlspecialchars($rid) ?>">
                    <span class="tsugi-discussion-drag-handle" title="<?= htmlspecialchars(__('Drag to reorder')) ?>" aria-label="<?= htmlspecialchars(__('Drag to reorder')) ?>">&#8942;</span>
                    <span><?= htmlspecialchars($title) ?></span>
                </li>
                <?php } ?>
            </ul>
            <p>
                <button type="button" id="tsugi-reorder-save" class="btn btn-success" disabled><?= __('Save') ?></button>
                <a href="<?= htmlspecialchars($list_url) ?>" class="btn btn-default tsugi-reorder-leave"><?= __('Discard') ?></a>
            </p>
        </main>
        <div id="tsugi-reorder-save-bar" class="tsugi-reorder-save-bar hidden">
            <div><?= __('You have unsaved order changes') ?></div>
            <div class="actions">
                <button type="button" id="tsugi-reorder-save-bar-btn" class="btn btn-success"><?= __('Save') ?></button>
                <a href="<?= htmlspecialchars($list_url) ?>" class="btn btn-default tsugi-reorder-leave"><?= __('Discard') ?></a>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var hasChanges = false;
            var allowLeave = false;
            var saveUrl = <?= json_encode($save_url) ?>;
            var listUrl = <?= json_encode($list_url) ?>;
            var leaveMsg = <?= json_encode(__('Please save before you navigate, or discard your changes.')) ?>;

            function markChanged() {
                hasChanges = true;
                var save = document.getElementById('tsugi-reorder-save');
                if (save) save.disabled = false;
                var bar = document.getElementById('tsugi-reorder-save-bar');
                if (bar) bar.classList.remove('hidden');
            }

            function collectOrder() {
                var order = [];
                document.querySelectorAll('#tsugi-reorder-list > li[data-resource-link-id]').forEach(function(li) {
                    var id = li.getAttribute('data-resource-link-id');
                    if (id) order.push(id);
                });
                return order;
            }

            function saveOrder() {
                if (!hasChanges) return;
                var btn = document.getElementById('tsugi-reorder-save');
                var barBtn = document.getElementById('tsugi-reorder-save-bar-btn');
                if (btn) btn.disabled = true;
                if (barBtn) barBtn.disabled = true;
                jQuery.ajax({
                    url: saveUrl,
                    method: 'POST',
                    data: { order: collectOrder() },
                    headers: tsugiCsrfHeaders()
                })
                    .done(function(data) {
                        allowLeave = true;
                        hasChanges = false;
                        window.location = (data && data.redirect) ? data.redirect : listUrl;
                    })
                    .fail(function(xhr) {
                        if (btn) btn.disabled = false;
                        if (barBtn) barBtn.disabled = false;
                        var msg = 'Could not save discussion order.';
                        try {
                            var body = JSON.parse(xhr.responseText);
                            if (body && body.error) msg = body.error;
                        } catch (e) {}
                        alert(msg);
                    });
            }

            if (typeof jQuery !== 'undefined' && jQuery.fn.sortable) {
                jQuery('#tsugi-reorder-list').sortable({
                    handle: '.tsugi-discussion-drag-handle',
                    items: '> li',
                    placeholder: 'ui-sortable-placeholder',
                    tolerance: 'pointer',
                    update: function() { markChanged(); }
                });
            }

            var save = document.getElementById('tsugi-reorder-save');
            if (save) save.addEventListener('click', saveOrder);
            var barBtn = document.getElementById('tsugi-reorder-save-bar-btn');
            if (barBtn) barBtn.addEventListener('click', saveOrder);

            window.addEventListener('beforeunload', function(ev) {
                if (!hasChanges || allowLeave) return;
                ev.preventDefault();
                ev.returnValue = leaveMsg;
                return leaveMsg;
            });

            document.addEventListener('click', function(ev) {
                var a = ev.target && ev.target.closest ? ev.target.closest('a') : null;
                if (!a || !hasChanges || allowLeave) return;
                var href = a.getAttribute('href');
                if (!href || href.charAt(0) === '#') return;
                var target = (a.getAttribute('target') || '_self').toLowerCase();
                if (target !== '_self') return;
                ev.preventDefault();
                if (window.confirm(leaveMsg + '\n\n' + <?= json_encode(__('Leave without saving?')) ?>)) {
                    allowLeave = true;
                    window.location = a.href;
                }
            }, true);
        });
        </script>
        <?php
        $OUTPUT->footer();
        return '';
    }

    /**
     * POST: persist a new catalog order as a manifest version (AJAX).
     */
    public function reorderPost(Request $request)
    {
        if ( Manifest::activeId() < 1 ) {
            return new JsonResponse(array('success' => false, 'error' => 'Manifest required'), 403);
        }
        if ( ! $this->isInstructor() ) {
            return new JsonResponse(array('success' => false, 'error' => 'Instructor required'), 403);
        }
        $csrf = self::requireCsrfJson();
        if ( $csrf ) {
            return $csrf;
        }

        $order = U::get($_POST, 'order', array());
        if ( is_string($order) ) {
            $decoded = json_decode($order, true);
            $order = is_array($decoded) ? $decoded : array();
        }
        if ( ! is_array($order) ) {
            return new JsonResponse(array('success' => false, 'error' => 'Order is required'), 400);
        }

        $doc = Manifest::currentDocument();
        if ( ! $doc ) {
            return new JsonResponse(array('success' => false, 'error' => 'Cannot find course manifest'), 500);
        }
        $data = json_decode($doc['json'], true);
        if ( ! is_array($data) ) {
            return new JsonResponse(array('success' => false, 'error' => 'Cannot parse course manifest'), 500);
        }

        try {
            $data = Manifest::reorderDiscussions($data, $order);
            Manifest::saveNewVersion(
                U::currentContextId(),
                $data,
                U::loggedInUserId(),
                'Reorder discussions'
            );
        } catch ( \InvalidArgumentException $e ) {
            return new JsonResponse(array('success' => false, 'error' => $e->getMessage()), 400);
        } catch ( \Exception $e ) {
            return new JsonResponse(array('success' => false, 'error' => 'Failed to save order'), 500);
        }

        U::flashSuccess(__('Discussion order saved.'));
        return new JsonResponse(array(
            'success' => true,
            'redirect' => U::addSession($this->toolHome(self::ROUTE)),
        ));
    }

    /**
     * @return RedirectResponse|null
     */
    private function addDiscussionGate($list_url) {
        if ( Manifest::activeId() < 1 ) {
            U::flashError(__('Adding discussions is only available for courses with a manifest.'));
            return new RedirectResponse($list_url);
        }
        $this->requireInstructor($list_url);
        return null;
    }

    public static function launch(Application $app, $anchor=null)
    {
        $toolHome = self::determineToolHome(self::ROUTE);
        $redirect_path = U::addSession(self::determineParentPath(self::ROUTE));
        if ( $redirect_path == '') $redirect_path = '/';

        $l = Manifest::currentLessons();
        if ( ! $l ) {
            $app->tsugiFlashError(__('Cannot find lessons.json ($CFG->lessons) or an active course manifest'));
            return new RedirectResponse($redirect_path);
        }

        $lti = $l->getLtiByRlid($anchor);
        if ( ! $lti ) {
            $app->tsugiFlashError(__('Cannot find lti resource link id'));
            return new RedirectResponse($redirect_path);
        }

        return Tool::sendLti11LaunchFromLessonsItem(
            $app,
            $lti,
            $toolHome,
            $redirect_path
        );
    }

    public function json(Request $request)
    {
        global $CFG, $PDOX;

        if ( ! U::isLoggedIn() || ! U::currentContextId() ) {
            return new JsonResponse(array('status' => 'error', 'detail' => 'Must be logged in with context'), 401);
        }

        LTIX::getConnection();

        $context_id = U::currentContextId();
        $user_id = U::loggedInUserId();

        $has_mentions = $this->tableExists($CFG->dbprefix.'tdiscus_mention');

        $rows = $PDOX->allRowsDie("SELECT L.link_id, L.link_key
            FROM {$CFG->dbprefix}lti_link L
            WHERE L.context_id = :CID
              AND EXISTS (
                SELECT 1 FROM {$CFG->dbprefix}tdiscus_thread T
                WHERE T.link_id = L.link_id
              )
            ORDER BY L.link_id",
            array(':CID' => $context_id)
        );

        $include_participating_in_main = intval(U::get($_GET, 'include_participating', 0)) > 0;
        $include_participation_as_personal = intval(U::get($_GET, 'participation_personal', 0)) > 0;
        $has_read_baseline = $this->hasDiscussionReadBaseline($context_id, $user_id);

        $by_discussion = array();
        $totals = array('personal' => 0, 'participating' => 0, 'global' => 0, 'main_badge' => 0);
        foreach ( $rows as $row ) {
            if ( $has_read_baseline ) {
                $counts = $this->rollupsForLink(
                    intval($row['link_id']),
                    $user_id,
                    $has_mentions,
                    $include_participation_as_personal
                );
            } else {
                $counts = array('personal' => 0, 'participating' => 0, 'global' => 0);
            }
            $main_badge = intval($counts['personal']);
            if ( $include_participating_in_main ) {
                $main_badge += intval($counts['participating']);
            }

            $by_discussion[] = array(
                'link_id' => intval($row['link_id']),
                'resource_link_id' => $row['link_key'],
                'badge' => $counts,
                'main_badge' => $main_badge,
            );

            $totals['personal'] += intval($counts['personal']);
            $totals['participating'] += intval($counts['participating']);
            $totals['global'] += intval($counts['global']);
            $totals['main_badge'] += $main_badge;
        }

        return new JsonResponse(array(
            'status' => 'success',
            'totals' => $totals,
            'discussions' => $by_discussion,
            'config' => array(
                'include_participating_in_main_badge' => $include_participating_in_main ? 1 : 0,
                'include_participation_as_personal' => $include_participation_as_personal ? 1 : 0,
            ),
        ));
    }

    /**
     * Mark every discussion thread in the current course context as read for the logged-in user.
     * Aligns tdiscus_user_thread.comments with each thread and sets read_at (same idea as Threads::threadMarkAsReadForUserDao).
     */
    public function markRead(Request $request)
    {
        global $CFG, $PDOX;

        $discussions_url = $this->toolHome(self::ROUTE);

        if ( ! U::isLoggedIn() || ! U::currentContextId() ) {
            U::flashError(__('You must be logged in with a course context to mark discussions as read.'));
            return new RedirectResponse(U::addSession($discussions_url));
        }
        $csrf = self::requireCsrf(U::addSession($discussions_url));
        if ( $csrf ) {
            return $csrf;
        }

        if ( ! U::isNotEmpty($CFG->tdiscus) || ! $CFG->tdiscus ) {
            U::flashError(__('Discussions are not available on this site.'));
            return new RedirectResponse(U::addSession($discussions_url));
        }

        LTIX::getConnection();

        $context_id = U::currentContextId();
        $user_id = U::loggedInUserId();

        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}tdiscus_user_thread UT
            JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = UT.thread_id
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            SET UT.read_at = NOW(), UT.comments = T.comments
            WHERE L.context_id = :CID AND UT.user_id = :UID",
            array(':CID' => $context_id, ':UID' => $user_id)
        );

        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}tdiscus_user_thread (thread_id, user_id, comments, read_at)
            SELECT T.thread_id, :UID, T.comments, NOW()
            FROM {$CFG->dbprefix}tdiscus_thread T
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            WHERE L.context_id = :CID
              AND NOT EXISTS (
                SELECT 1 FROM {$CFG->dbprefix}tdiscus_user_thread UT
                WHERE UT.thread_id = T.thread_id AND UT.user_id = :UID2
              )",
            array(':UID' => $user_id, ':CID' => $context_id, ':UID2' => $user_id)
        );

        U::flashSuccess(__('All discussions in this course have been marked as read.'));
        return new RedirectResponse(U::addSession($discussions_url));
    }

    public function expireThreadsDryRun(Request $request)
    {
        global $CFG, $PDOX;

        $expire_url = $this->toolHome(self::ROUTE) . '/expire-threads';
        $redirect_url = U::addSession($expire_url);

        if ( ! U::isLoggedIn() || ! U::currentContextId() ) {
            U::flashError(__('You must be logged in with a course context to manage discussion expiration.'));
            return new RedirectResponse($redirect_url);
        }
        if ( ! $this->isInstructor() ) {
            U::flashError(__('Only instructors can run discussion expiration.'));
            return new RedirectResponse($redirect_url);
        }
        $csrf = self::requireCsrf($redirect_url);
        if ( $csrf ) {
            return $csrf;
        }

        $months_raw = trim((string) U::get($_POST, 'months', ''));
        $months = intval($months_raw);
        if ( ! ctype_digit($months_raw) || $months <= 1 ) {
            U::flashError(__('Months must be a whole number greater than 1.'));
            return new RedirectResponse($redirect_url);
        }

        LTIX::getConnection();
        $context_id = U::currentContextId();
        $confirm_raw = trim((string) U::get($_POST, 'confirm', ''));
        $confirm = ($confirm_raw === '1');

        $count_sql = "SELECT COUNT(*) AS count
            FROM {$CFG->dbprefix}tdiscus_thread T
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            LEFT JOIN (
                SELECT C.thread_id, MAX(C.created_at) AS latest_post_at
                FROM {$CFG->dbprefix}tdiscus_comment C
                GROUP BY C.thread_id
            ) LC ON LC.thread_id = T.thread_id
            WHERE L.context_id = :CID
              AND T.created_at < DATE_SUB(NOW(), INTERVAL :MONTHS MONTH)
              AND COALESCE(LC.latest_post_at, T.created_at) < DATE_SUB(NOW(), INTERVAL :MONTHS2 MONTH)";

        $count_row = $PDOX->rowDie($count_sql, array(
            ':CID' => $context_id,
            ':MONTHS' => $months,
            ':MONTHS2' => $months,
        ));

        $candidate_sql = "SELECT T.thread_id
FROM {$CFG->dbprefix}tdiscus_thread T
JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
LEFT JOIN (
    SELECT C.thread_id, MAX(C.created_at) AS latest_post_at
    FROM {$CFG->dbprefix}tdiscus_comment C
    GROUP BY C.thread_id
) LC ON LC.thread_id = T.thread_id
WHERE L.context_id = :CID
  AND T.created_at < DATE_SUB(NOW(), INTERVAL :MONTHS MONTH)
  AND COALESCE(LC.latest_post_at, T.created_at) < DATE_SUB(NOW(), INTERVAL :MONTHS2 MONTH)
ORDER BY COALESCE(LC.latest_post_at, T.created_at), T.thread_id
LIMIT ".self::EXPIRE_DELETE_BATCH_LIMIT;

        $delete_sql = "DELETE FROM {$CFG->dbprefix}tdiscus_thread
WHERE thread_id IN (:THREAD_ID_1, :THREAD_ID_2, ... up to ".self::EXPIRE_DELETE_BATCH_LIMIT." ids)";

        $matching_before = intval(U::get($count_row, 'count', 0));
        $matching_after = $matching_before;
        $deleted_now = 0;
        $limit_hit = 0;
        if ( $confirm ) {
            @set_time_limit(30);
            $candidate_rows = $PDOX->allRowsDie($candidate_sql, array(
                ':CID' => $context_id,
                ':MONTHS' => $months,
                ':MONTHS2' => $months,
            ));
            $thread_ids = array();
            foreach ( $candidate_rows as $row ) {
                $thread_id = intval(U::get($row, 'thread_id', 0));
                if ( $thread_id > 0 ) $thread_ids[] = $thread_id;
            }
            if ( count($thread_ids) > 0 ) {
                $delete_params = array();
                $placeholders = array();
                $ix = 0;
                foreach ( $thread_ids as $thread_id ) {
                    $ix++;
                    $ph = ':TID'.$ix;
                    $placeholders[] = $ph;
                    $delete_params[$ph] = $thread_id;
                }
                $run_delete_sql = "DELETE FROM {$CFG->dbprefix}tdiscus_thread
                    WHERE thread_id IN (".implode(',', $placeholders).")";
                $stmt = $PDOX->queryDie($run_delete_sql, $delete_params);
                $deleted_now = $stmt->rowCount();
                $limit_hit = (count($thread_ids) >= self::EXPIRE_DELETE_BATCH_LIMIT && $matching_before > $deleted_now) ? 1 : 0;
            }
            $after_row = $PDOX->rowDie($count_sql, array(
                ':CID' => $context_id,
                ':MONTHS' => $months,
                ':MONTHS2' => $months,
            ));
            $matching_after = intval(U::get($after_row, 'count', 0));
            if ( $deleted_now > 0 ) {
                if ( $limit_hit ) {
                    U::flashSuccess(__('Deleted ').$deleted_now.__(' threads. Batch limit reached (500). Run again to continue.'));
                } else {
                    U::flashSuccess(__('Deleted ').$deleted_now.__(' threads.'));
                }
            } else {
                U::flashSuccess(__('No matching threads were deleted.'));
            }
        } else {
            U::flashSuccess(__('Dry run complete: no threads were deleted.'));
        }

        $_SESSION['discussions_expire_dry_run_result'] = array(
            'months' => $months,
            'count' => $matching_after,
            'count_before' => $matching_before,
            'confirmed' => $confirm ? 1 : 0,
            'deleted_now' => $deleted_now,
            'limit_hit' => $limit_hit,
            'batch_limit' => self::EXPIRE_DELETE_BATCH_LIMIT,
            'candidate_sql' => $candidate_sql,
            'sql' => $delete_sql,
            'params' => array(
                ':CID' => $context_id,
                ':MONTHS' => $months,
                ':MONTHS2' => $months,
            ),
        );
        return new RedirectResponse($redirect_url);
    }

    public function expireThreads(Request $request)
    {
        global $CFG, $OUTPUT, $PDOX;

        if ( ! U::isLoggedIn() || ! U::currentContextId() ) {
            U::flashError(__('You must be logged in with a course context to manage discussion expiration.'));
            return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE)));
        }
        if ( ! $this->isInstructor() ) {
            U::flashError(__('Only instructors can run discussion expiration.'));
            return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE)));
        }

        LTIX::getConnection();
        $context_id = U::currentContextId();
        $oldest_post_row = $PDOX->rowDie(
            "SELECT MIN(C.created_at) AS oldest_post_at
                FROM {$CFG->dbprefix}tdiscus_comment C
                JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = C.thread_id
                JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
                WHERE L.context_id = :CID",
            array(':CID' => $context_id)
        );
        $oldest_thread_row = $PDOX->rowDie(
            "SELECT MIN(T.created_at) AS oldest_thread_at
                FROM {$CFG->dbprefix}tdiscus_thread T
                JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
                WHERE L.context_id = :CID",
            array(':CID' => $context_id)
        );
        $oldest_post_at = U::get($oldest_post_row, 'oldest_post_at', null);
        $oldest_thread_at = U::get($oldest_thread_row, 'oldest_thread_at', null);

        $dry_run_url = U::addSession($this->toolHome(self::ROUTE).'/expire-threads-dry-run');
        $expire_result = U::get($_SESSION, 'discussions_expire_dry_run_result', false);
        unset($_SESSION['discussions_expire_dry_run_result']);

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        echo('<main class="container" id="main-content">');
        echo('<p><a href="'.htmlspecialchars(U::addSession($this->toolHome(self::ROUTE).'/manage')).'" class="btn btn-default btn-sm">'.__('Back to Manage Discussions').'</a></p>');
        $this->renderExpireDryRunPanel($dry_run_url, $expire_result, $oldest_post_at, $oldest_thread_at);
        $this->emitExpireDeleteFormEnhancements();
        echo('</main>');
        $OUTPUT->footer();
    }

    public function expireCommentsDryRun(Request $request)
    {
        global $CFG, $PDOX;

        $expire_url = $this->toolHome(self::ROUTE) . '/expire-comments';
        $redirect_url = U::addSession($expire_url);

        if ( ! U::isLoggedIn() || ! U::currentContextId() ) {
            U::flashError(__('You must be logged in with a course context to manage discussion expiration.'));
            return new RedirectResponse($redirect_url);
        }
        if ( ! $this->isInstructor() ) {
            U::flashError(__('Only instructors can run discussion expiration.'));
            return new RedirectResponse($redirect_url);
        }
        $csrf = self::requireCsrf($redirect_url);
        if ( $csrf ) {
            return $csrf;
        }

        $months_raw = trim((string) U::get($_POST, 'months', ''));
        $months = intval($months_raw);
        if ( ! ctype_digit($months_raw) || $months <= 1 ) {
            U::flashError(__('Months must be a whole number greater than 1.'));
            return new RedirectResponse($redirect_url);
        }

        LTIX::getConnection();
        $context_id = U::currentContextId();
        $confirm_raw = trim((string) U::get($_POST, 'confirm', ''));
        $confirm = ($confirm_raw === '1');

        if ( ! $this->ensureTdiscusThreadsLoaded() ) {
            U::flashError(__('Discussion tools are not available on this installation.'));
            return new RedirectResponse($redirect_url);
        }

        $count_sql = $this->expireCommentsCountSql($CFG->dbprefix);
        $count_row = $PDOX->rowDie($count_sql, array(
            ':CID' => $context_id,
            ':MONTHS' => $months,
            ':MONTHS2' => $months,
        ));

        $delete_sql = "For each comment_id returned by the candidate query: \\Tdiscus\\Threads::commentDeleteDao(array('comment_id'=>..., 'parent_id'=>...), thread_id)";

        $matching_before = intval(U::get($count_row, 'count', 0));
        $matching_after = $matching_before;
        $deleted_now = 0;
        $limit_hit = 0;
        if ( $confirm ) {
            @set_time_limit(120);
            for ( $i = 0; $i < self::EXPIRE_DELETE_BATCH_LIMIT && $matching_after > 0; $i++ ) {
                $candidate_sql_one = $this->expireCommentsCandidateSql($CFG->dbprefix, 1);
                $row = $PDOX->rowDie($candidate_sql_one, array(
                    ':CID' => $context_id,
                    ':MONTHS' => $months,
                    ':MONTHS2' => $months,
                ));
                if ( ! is_array($row) ) {
                    break;
                }
                $comment_id = intval(U::get($row, 'comment_id', 0));
                $thread_id = intval(U::get($row, 'thread_id', 0));
                if ( $comment_id <= 0 || $thread_id <= 0 ) {
                    break;
                }
                $comment = array(
                    'comment_id' => $comment_id,
                    'parent_id' => intval(U::get($row, 'parent_id', 0)),
                );
                $retval = \Tdiscus\Threads::commentDeleteDao($comment, $thread_id);
                if ( is_string($retval) ) {
                    error_log('Discussions::expireCommentsDryRun commentDeleteDao failed for comment_id='.$comment_id.': '.$retval);
                    continue;
                }
                $deleted_now++;
                $after_row = $PDOX->rowDie($count_sql, array(
                    ':CID' => $context_id,
                    ':MONTHS' => $months,
                    ':MONTHS2' => $months,
                ));
                $matching_after = intval(U::get($after_row, 'count', 0));
            }
            $limit_hit = ($deleted_now >= self::EXPIRE_DELETE_BATCH_LIMIT && $matching_after > 0) ? 1 : 0;
            if ( $deleted_now > 0 ) {
                if ( $limit_hit ) {
                    U::flashSuccess(__('Deleted ').$deleted_now.__(' comment subtree(s). Batch limit reached (500). Run again to continue.'));
                } else {
                    U::flashSuccess(__('Deleted ').$deleted_now.__(' comment subtree(s).'));
                }
            } else {
                U::flashSuccess(__('No matching comments were deleted.'));
            }
        } else {
            U::flashSuccess(__('Dry run complete: no comments were deleted.'));
        }

        $_SESSION['discussions_expire_comments_dry_run_result'] = array(
            'months' => $months,
            'count' => $matching_after,
            'count_before' => $matching_before,
            'confirmed' => $confirm ? 1 : 0,
            'deleted_now' => $deleted_now,
            'limit_hit' => $limit_hit,
            'batch_limit' => self::EXPIRE_DELETE_BATCH_LIMIT,
            'candidate_sql' => $this->expireCommentsCandidateSql($CFG->dbprefix, self::EXPIRE_DELETE_BATCH_LIMIT),
            'sql' => $delete_sql,
            'params' => array(
                ':CID' => $context_id,
                ':MONTHS' => $months,
                ':MONTHS2' => $months,
            ),
        );
        return new RedirectResponse($redirect_url);
    }

    public function expireComments(Request $request)
    {
        global $CFG, $OUTPUT, $PDOX;

        if ( ! U::isLoggedIn() || ! U::currentContextId() ) {
            U::flashError(__('You must be logged in with a course context to manage discussion expiration.'));
            return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE)));
        }
        if ( ! $this->isInstructor() ) {
            U::flashError(__('Only instructors can run discussion expiration.'));
            return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE)));
        }

        LTIX::getConnection();
        $context_id = U::currentContextId();
        $oldest_comment_row = $PDOX->rowDie(
            "SELECT MIN(C.created_at) AS oldest_comment_at
                FROM {$CFG->dbprefix}tdiscus_comment C
                JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = C.thread_id
                JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
                WHERE L.context_id = :CID",
            array(':CID' => $context_id)
        );
        $oldest_comment_at = U::get($oldest_comment_row, 'oldest_comment_at', null);

        $dry_run_url = U::addSession($this->toolHome(self::ROUTE).'/expire-comments-dry-run');
        $expire_result = U::get($_SESSION, 'discussions_expire_comments_dry_run_result', false);
        unset($_SESSION['discussions_expire_comments_dry_run_result']);
        $tdiscus_threads_ok = $this->ensureTdiscusThreadsLoaded();

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        echo('<main class="container" id="main-content">');
        echo('<p><a href="'.htmlspecialchars(U::addSession($this->toolHome(self::ROUTE).'/manage')).'" class="btn btn-default btn-sm">'.__('Back to Manage Discussions').'</a></p>');
        $this->renderExpireCommentsDryRunPanel($dry_run_url, $expire_result, $oldest_comment_at, $tdiscus_threads_ok);
        $this->emitExpireDeleteFormEnhancements();
        echo('</main>');
        $OUTPUT->footer();
    }

    public function manage(Request $request)
    {
        global $OUTPUT;

        if ( ! U::isLoggedIn() || ! U::currentContextId() ) {
            U::flashError(__('You must be logged in with a course context to manage discussions.'));
            return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE)));
        }
        if ( ! $this->isInstructor() ) {
            U::flashError(__('Only instructors can manage discussions.'));
            return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE)));
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        echo('<main class="container" id="main-content">');
        echo('<p><a href="'.htmlspecialchars(U::addSession($this->toolHome(self::ROUTE))).'" class="btn btn-default btn-sm">'.__('Back to Discussions').'</a></p>');
        echo('<h1>'.__('Manage Discussions').'</h1>');
        echo('<p>'.__('Discussion maintenance tools for this course context.').'</p>');
        echo('<ul>');
        // Temporarily hide reset unread tracking from UI; use scan/fix workflow instead.
        /*
        echo('<li>');
        echo('<a href="#" class="tsugi-discussions-reset-unread-tracking-link">'.__('Reset unread tracking for all users').'</a>');
        echo(' <span class="text-muted">('.__('clears read tracking for this course; subscription preferences are unchanged').')</span>');
        echo('<form method="post" action="'.htmlspecialchars(U::addSession($this->toolHome(self::ROUTE).'/reset-unread-tracking')).'" class="tsugi-discussions-reset-unread-tracking-form" style="display:none;">'.self::csrfField().'</form>');
        echo('</li>');
        */
        echo('<li><a href="'.htmlspecialchars(U::addSession($this->toolHome(self::ROUTE).'/expire-comments')).'">'.__('Expire old comments').'</a></li>');
        echo('<li><a href="'.htmlspecialchars(U::addSession($this->toolHome(self::ROUTE).'/expire-threads')).'">'.__('Expire old threads').'</a></li>');
        echo('<li>');
        echo('<a href="'.htmlspecialchars(U::addSession($this->toolHome(self::ROUTE).'/scan-fix-unread-tracking')).'">'.__('Scan unread tracking (dry run) and optionally fix').'</a>');
        echo(' <span class="text-muted">('.__('pre-scan first, then run fix in a second step').')</span>');
        echo('</li>');
        echo('</ul>');
?>
<script>
(function () {
  if (window.__tsugiDiscussionsResetUnreadTrackingBound) return;
  window.__tsugiDiscussionsResetUnreadTrackingBound = true;
  document.addEventListener('click', function (ev) {
    var link = ev.target;
    if (!link || !link.classList || !link.classList.contains('tsugi-discussions-reset-unread-tracking-link')) return;
    ev.preventDefault();
    if (!window.confirm('Reset unread tracking for all users in this course?')) return;
    var form = document.querySelector('.tsugi-discussions-reset-unread-tracking-form');
    if (form) form.requestSubmit();
  }, true);
})();
</script>
<?php
        echo('</main>');
        $OUTPUT->footer();
    }

    /**
     * Reset unread tracking state for all users in the current context.
     *
     * Clears participation and nulls per-thread read markers for everyone.
     * Subscription preferences are left unchanged.
     */
    public function resetUnreadTracking(Request $request)
    {
        global $CFG, $PDOX;

        $manage_url = $this->toolHome(self::ROUTE) . '/manage';
        $redirect_url = U::addSession($manage_url);

        if ( ! U::isLoggedIn() || ! U::currentContextId() ) {
            U::flashError(__('You must be logged in with a course context to manage discussions.'));
            return new RedirectResponse($redirect_url);
        }
        if ( ! $this->isInstructor() ) {
            U::flashError(__('Only instructors can manage discussions.'));
            return new RedirectResponse($redirect_url);
        }
        $csrf = self::requireCsrf($redirect_url);
        if ( $csrf ) {
            return $csrf;
        }
        if ( ! U::isNotEmpty($CFG->tdiscus) || ! $CFG->tdiscus ) {
            U::flashError(__('Discussions are not available on this site.'));
            return new RedirectResponse($redirect_url);
        }

        LTIX::getConnection();
        $context_id = U::currentContextId();

        $PDOX->queryDie(
            "DELETE UTP FROM {$CFG->dbprefix}tdiscus_user_thread_participation UTP
            JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = UTP.thread_id
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            WHERE L.context_id = :CID",
            array(':CID' => $context_id)
        );

        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}tdiscus_user_thread UT
            JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = UT.thread_id
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            SET UT.read_at = NULL,
                UT.comments = 0
            WHERE L.context_id = :CID",
            array(':CID' => $context_id)
        );

        // Participation rows were cleared above; restore for thread creators only.
        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}tdiscus_user_thread_participation (thread_id, user_id, last_posted_at)
            SELECT T.thread_id, T.user_id, NOW()
            FROM {$CFG->dbprefix}tdiscus_thread T
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            JOIN {$CFG->dbprefix}lti_user U ON U.user_id = T.user_id
            WHERE L.context_id = :CID
            ON DUPLICATE KEY UPDATE last_posted_at = NOW()",
            array(':CID' => $context_id)
        );

        U::flashSuccess(__('Unread tracking has been reset for all users in this course.'));
        return new RedirectResponse($redirect_url);
    }

    public function scanFixUnreadTracking(Request $request)
    {
        global $CFG, $OUTPUT;

        if ( ! U::isLoggedIn() || ! U::currentContextId() ) {
            U::flashError(__('You must be logged in with a course context to manage discussions.'));
            return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE)));
        }
        if ( ! $this->isInstructor() ) {
            U::flashError(__('Only instructors can manage discussions.'));
            return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE)));
        }
        if ( ! U::isNotEmpty($CFG->tdiscus) || ! $CFG->tdiscus ) {
            U::flashError(__('Discussions are not available on this site.'));
            return new RedirectResponse(U::addSession($this->toolHome(self::ROUTE).'/manage'));
        }

        LTIX::getConnection();
        $context_id = U::currentContextId();
        $counts = $this->unreadTrackingAuditCounts($context_id);
        $details = $this->unreadTrackingAuditDetails($context_id, 15);
        $likely_causes = $this->unreadTrackingLikelyCauses($counts);
        $last_result = U::get($_SESSION, 'discussions_scan_fix_unread_result', false);
        unset($_SESSION['discussions_scan_fix_unread_result']);
        $run_url = U::addSession($this->toolHome(self::ROUTE).'/scan-fix-unread-tracking-run');

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        echo('<main class="container" id="main-content">');
        echo('<p><a href="'.htmlspecialchars(U::addSession($this->toolHome(self::ROUTE).'/manage')).'" class="btn btn-default btn-sm">'.__('Back to Manage Discussions').'</a></p>');
        echo('<div class="panel panel-info" style="margin-bottom: 1.5em;">');
        echo('<div class="panel-heading"><strong>'.__('Instructor: Unread tracking pre-scan (two phase)').'</strong></div>');
        echo('<div class="panel-body">');
        echo('<p class="text-muted" style="margin-top:0;">'.__('Use this page to inspect inconsistencies first (dry run), then run repair as a separate step.').'</p>');
        echo('<ul>');
        echo('<li>'.__('Invalid per-user counters (tdiscus_user_thread.comments outside valid range)').': <strong>'.intval(U::get($counts, 'invalid_user_thread_comments', 0)).'</strong></li>');
        echo('<li>'.__('Missing owner subscribe rows').': <strong>'.intval(U::get($counts, 'missing_owner_subscribe', 0)).'</strong></li>');
        echo('<li>'.__('Missing owner participation rows').': <strong>'.intval(U::get($counts, 'missing_owner_participation', 0)).'</strong></li>');
        echo('</ul>');
        if ( count($likely_causes) > 0 ) {
            echo('<p style="margin-bottom:0.35em;"><strong>'.__('Likely causes to investigate').'</strong></p>');
            echo('<ul>');
            foreach ( $likely_causes as $cause ) {
                echo('<li>'.htmlspecialchars($cause).'</li>');
            }
            echo('</ul>');
        }

        $snapshot = array(
            'generated_at_utc' => gmdate('Y-m-d\TH:i:s\Z'),
            'context_id' => intval($context_id),
            'counts' => $counts,
            'samples' => $details,
        );
        echo('<p style="margin-bottom:0.35em;"><strong>'.__('Diagnostic snapshot (copy/paste friendly)').'</strong></p>');
        echo('<pre style="max-height: 24em; overflow:auto;">'.htmlspecialchars(json_encode($snapshot, JSON_PRETTY_PRINT)).'</pre>');

        echo('<form method="post" action="'.htmlspecialchars($run_url).'" class="form-inline" style="margin-bottom:0.75em;">');
        echo(self::csrfField());
        echo('<input type="hidden" name="confirm" value="0">');
        echo('<button type="submit" class="btn btn-info">'.__('Run Pre-Scan Again (dry run)').'</button>');
        echo('</form>');

        echo('<form method="post" action="'.htmlspecialchars($run_url).'" class="form-inline tsugi-discussions-scan-fix-form">');
        echo(self::csrfField());
        echo('<input type="hidden" name="confirm" value="1">');
        echo('<button type="submit" class="btn btn-danger" data-running-label="'.htmlspecialchars(__('Applying repairs…')).'">'.__('Apply Repair (phase 2)').'</button>');
        echo('</form>');

        if ( is_array($last_result) ) {
            echo('<hr/>');
            echo('<p><strong>'.__('Last run result').'</strong></p>');
            echo('<ul>');
            echo('<li>'.__('Mode').': <strong>'.(intval(U::get($last_result, 'confirmed', 0)) === 1 ? __('repair') : __('dry run')).'</strong></li>');
            echo('<li>'.__('Fixed user counters').': <strong>'.intval(U::get($last_result, 'fixed_comments', 0)).'</strong></li>');
            echo('<li>'.__('Fixed owner subscribe rows').': <strong>'.intval(U::get($last_result, 'fixed_subscribe', 0)).'</strong></li>');
            echo('<li>'.__('Fixed owner participation rows').': <strong>'.intval(U::get($last_result, 'fixed_participation', 0)).'</strong></li>');
            echo('<li>'.__('Remaining findings').': <strong>'.intval(U::get($last_result, 'remaining', 0)).'</strong></li>');
            echo('</ul>');
            $before_counts = U::get($last_result, 'before_counts', array());
            $after_counts = U::get($last_result, 'after_counts', array());
            $before_details = U::get($last_result, 'before_details', array());
            $last_snapshot = array(
                'generated_at_utc' => gmdate('Y-m-d\TH:i:s\Z'),
                'mode' => intval(U::get($last_result, 'confirmed', 0)) === 1 ? 'repair' : 'dry_run',
                'before_counts' => $before_counts,
                'after_counts' => $after_counts,
                'before_samples' => $before_details,
            );
            echo('<p style="margin-bottom:0.35em;"><strong>'.__('Last run snapshot').'</strong></p>');
            echo('<pre style="max-height: 20em; overflow:auto;">'.htmlspecialchars(json_encode($last_snapshot, JSON_PRETTY_PRINT)).'</pre>');
        }
        echo('</div>');
        echo('</div>');
?>
<script>
(function () {
  if (window.__tsugiDiscussionsScanFixBound) return;
  window.__tsugiDiscussionsScanFixBound = true;
  document.addEventListener('submit', function (ev) {
    var form = ev.target;
    if (!form || !form.classList || !form.classList.contains('tsugi-discussions-scan-fix-form')) return;
    if (!window.confirm('Apply unread tracking repairs for this course?')) {
      ev.preventDefault();
      return;
    }
    var btn = form.querySelector('button[type="submit"]');
    if (!btn || btn.disabled) return;
    btn.disabled = true;
    btn.setAttribute('aria-busy', 'true');
    btn.textContent = btn.getAttribute('data-running-label') || 'Applying repairs...';
  }, true);
})();
</script>
<?php
        echo('</main>');
        $OUTPUT->footer();
    }

    /**
     * Scan and optionally repair common unread-tracking inconsistencies.
     *
     * confirm=0 => dry run only
     * confirm=1 => apply idempotent repairs (no explicit transaction)
     */
    public function scanFixUnreadTrackingRun(Request $request)
    {
        global $CFG, $PDOX;

        $scan_url = $this->toolHome(self::ROUTE) . '/scan-fix-unread-tracking';
        $redirect_url = U::addSession($scan_url);

        if ( ! U::isLoggedIn() || ! U::currentContextId() ) {
            U::flashError(__('You must be logged in with a course context to manage discussions.'));
            return new RedirectResponse($redirect_url);
        }
        if ( ! $this->isInstructor() ) {
            U::flashError(__('Only instructors can manage discussions.'));
            return new RedirectResponse($redirect_url);
        }
        $csrf = self::requireCsrf($redirect_url);
        if ( $csrf ) {
            return $csrf;
        }
        if ( ! U::isNotEmpty($CFG->tdiscus) || ! $CFG->tdiscus ) {
            U::flashError(__('Discussions are not available on this site.'));
            return new RedirectResponse($redirect_url);
        }

        LTIX::getConnection();
        $context_id = U::currentContextId();
        $confirm_raw = trim((string) U::get($_POST, 'confirm', '0'));
        $confirm = ($confirm_raw === '1');
        $before = $this->unreadTrackingAuditCounts($context_id);
        $before_details = $this->unreadTrackingAuditDetails($context_id, 15);

        if ( $confirm ) {
            // 1) Keep denormalized per-user comment counters within valid bounds.
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}tdiscus_user_thread UT
                JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = UT.thread_id
                JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
                SET UT.comments = CASE
                    WHEN COALESCE(UT.comments, 0) < 0 THEN 0
                    WHEN COALESCE(UT.comments, 0) > COALESCE(T.comments, 0) THEN COALESCE(T.comments, 0)
                    ELSE COALESCE(UT.comments, 0)
                END
                WHERE L.context_id = :CID
                  AND (
                      COALESCE(UT.comments, 0) < 0
                      OR COALESCE(UT.comments, 0) > COALESCE(T.comments, 0)
                      OR UT.comments IS NULL
                  )",
                array(':CID' => $context_id)
            );

            // 2) Ensure thread owners stay subscribed to their own threads.
            $PDOX->queryDie(
                "INSERT INTO {$CFG->dbprefix}tdiscus_user_thread (thread_id, user_id, subscribe)
                SELECT T.thread_id, T.user_id, 1
                FROM {$CFG->dbprefix}tdiscus_thread T
                JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
                JOIN {$CFG->dbprefix}lti_user U ON U.user_id = T.user_id
                WHERE L.context_id = :CID
                ON DUPLICATE KEY UPDATE subscribe = 1",
                array(':CID' => $context_id)
            );

            // 3) Ensure participation rows exist for thread creators.
            $PDOX->queryDie(
                "INSERT INTO {$CFG->dbprefix}tdiscus_user_thread_participation (thread_id, user_id, last_posted_at)
                SELECT T.thread_id, T.user_id, COALESCE(T.updated_at, T.created_at)
                FROM {$CFG->dbprefix}tdiscus_thread T
                JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
                JOIN {$CFG->dbprefix}lti_user U ON U.user_id = T.user_id
                WHERE L.context_id = :CID
                ON DUPLICATE KEY UPDATE last_posted_at = GREATEST(
                    COALESCE(tdiscus_user_thread_participation.last_posted_at, '1970-01-01 00:00:00'),
                    COALESCE(VALUES(last_posted_at), '1970-01-01 00:00:00')
                )",
                array(':CID' => $context_id)
            );
        }

        $after = $this->unreadTrackingAuditCounts($context_id);

        $fixed_comments = max(0, intval(U::get($before, 'invalid_user_thread_comments', 0)) - intval(U::get($after, 'invalid_user_thread_comments', 0)));
        $fixed_subscribe = max(0, intval(U::get($before, 'missing_owner_subscribe', 0)) - intval(U::get($after, 'missing_owner_subscribe', 0)));
        $fixed_participation = max(0, intval(U::get($before, 'missing_owner_participation', 0)) - intval(U::get($after, 'missing_owner_participation', 0)));
        $remaining = intval(U::get($after, 'invalid_user_thread_comments', 0))
            + intval(U::get($after, 'missing_owner_subscribe', 0))
            + intval(U::get($after, 'missing_owner_participation', 0));

        $_SESSION['discussions_scan_fix_unread_result'] = array(
            'confirmed' => $confirm ? 1 : 0,
            'fixed_comments' => $fixed_comments,
            'fixed_subscribe' => $fixed_subscribe,
            'fixed_participation' => $fixed_participation,
            'remaining' => $remaining,
            'before_counts' => $before,
            'after_counts' => $after,
            'before_details' => $before_details,
        );
        if ( $confirm ) {
            U::flashSuccess(
                __('Unread tracking repair complete.').
                ' '.__('Fixed user counters').': '.$fixed_comments.
                ', '.__('owner subscribe rows').': '.$fixed_subscribe.
                ', '.__('owner participation rows').': '.$fixed_participation.
                '. '.__('Remaining findings').': '.$remaining
            );
        } else {
            U::flashSuccess(
                __('Unread tracking pre-scan complete (dry run).').
                ' '.__('Current findings').': '.
                __('invalid counters').'='.intval(U::get($before, 'invalid_user_thread_comments', 0)).
                ', '.__('missing owner subscribe').'='.intval(U::get($before, 'missing_owner_subscribe', 0)).
                ', '.__('missing owner participation').'='.intval(U::get($before, 'missing_owner_participation', 0))
            );
        }
        return new RedirectResponse($redirect_url);
    }

    private function unreadTrackingAuditCounts($context_id)
    {
        global $CFG, $PDOX;

        $invalid_comments = $PDOX->rowDie(
            "SELECT COUNT(*) AS count
            FROM {$CFG->dbprefix}tdiscus_user_thread UT
            JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = UT.thread_id
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            WHERE L.context_id = :CID
              AND (
                  COALESCE(UT.comments, 0) < 0
                  OR COALESCE(UT.comments, 0) > COALESCE(T.comments, 0)
                  OR UT.comments IS NULL
              )",
            array(':CID' => $context_id)
        );

        $missing_owner_subscribe = $PDOX->rowDie(
            "SELECT COUNT(*) AS count
            FROM {$CFG->dbprefix}tdiscus_thread T
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            JOIN {$CFG->dbprefix}lti_user U ON U.user_id = T.user_id
            LEFT JOIN {$CFG->dbprefix}tdiscus_user_thread UT
                ON UT.thread_id = T.thread_id AND UT.user_id = T.user_id
            WHERE L.context_id = :CID
              AND (UT.user_id IS NULL OR COALESCE(UT.subscribe, 0) <> 1)",
            array(':CID' => $context_id)
        );

        $missing_owner_participation = $PDOX->rowDie(
            "SELECT COUNT(*) AS count
            FROM {$CFG->dbprefix}tdiscus_thread T
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            JOIN {$CFG->dbprefix}lti_user U ON U.user_id = T.user_id
            LEFT JOIN {$CFG->dbprefix}tdiscus_user_thread_participation UTP
                ON UTP.thread_id = T.thread_id AND UTP.user_id = T.user_id
            WHERE L.context_id = :CID
              AND UTP.user_id IS NULL",
            array(':CID' => $context_id)
        );

        return array(
            'invalid_user_thread_comments' => intval(U::get($invalid_comments, 'count', 0)),
            'missing_owner_subscribe' => intval(U::get($missing_owner_subscribe, 'count', 0)),
            'missing_owner_participation' => intval(U::get($missing_owner_participation, 'count', 0)),
        );
    }

    private function unreadTrackingAuditDetails($context_id, $limit=15)
    {
        global $CFG, $PDOX;
        $limit = intval($limit);
        if ( $limit < 1 ) $limit = 1;
        if ( $limit > 100 ) $limit = 100;

        $invalid_comments = $PDOX->allRowsDie(
            "SELECT T.thread_id, UT.user_id,
                    COALESCE(UT.comments, 0) AS user_comments,
                    COALESCE(T.comments, 0) AS thread_comments,
                    UT.read_at
            FROM {$CFG->dbprefix}tdiscus_user_thread UT
            JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = UT.thread_id
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            WHERE L.context_id = :CID
              AND (
                  COALESCE(UT.comments, 0) < 0
                  OR COALESCE(UT.comments, 0) > COALESCE(T.comments, 0)
                  OR UT.comments IS NULL
              )
            ORDER BY T.thread_id, UT.user_id
            LIMIT ".$limit,
            array(':CID' => $context_id)
        );

        $missing_owner_subscribe = $PDOX->allRowsDie(
            "SELECT T.thread_id, T.user_id AS owner_user_id,
                    COALESCE(UT.subscribe, 0) AS owner_subscribe,
                    T.created_at, T.updated_at
            FROM {$CFG->dbprefix}tdiscus_thread T
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            JOIN {$CFG->dbprefix}lti_user U ON U.user_id = T.user_id
            LEFT JOIN {$CFG->dbprefix}tdiscus_user_thread UT
                ON UT.thread_id = T.thread_id AND UT.user_id = T.user_id
            WHERE L.context_id = :CID
              AND (UT.user_id IS NULL OR COALESCE(UT.subscribe, 0) <> 1)
            ORDER BY T.thread_id
            LIMIT ".$limit,
            array(':CID' => $context_id)
        );

        $missing_owner_participation = $PDOX->allRowsDie(
            "SELECT T.thread_id, T.user_id AS owner_user_id, T.created_at, T.updated_at
            FROM {$CFG->dbprefix}tdiscus_thread T
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            JOIN {$CFG->dbprefix}lti_user U ON U.user_id = T.user_id
            LEFT JOIN {$CFG->dbprefix}tdiscus_user_thread_participation UTP
                ON UTP.thread_id = T.thread_id AND UTP.user_id = T.user_id
            WHERE L.context_id = :CID
              AND UTP.user_id IS NULL
            ORDER BY T.thread_id
            LIMIT ".$limit,
            array(':CID' => $context_id)
        );

        return array(
            'invalid_user_thread_comments_rows' => $invalid_comments,
            'missing_owner_subscribe_rows' => $missing_owner_subscribe,
            'missing_owner_participation_rows' => $missing_owner_participation,
        );
    }

    private function unreadTrackingLikelyCauses($counts)
    {
        $causes = array();
        if ( intval(U::get($counts, 'invalid_user_thread_comments', 0)) > 0 ) {
            $causes[] = 'Per-user unread counters are denormalized; comment/thread deletes can leave UT.comments above current thread comment count until repair or re-read.';
        }
        if ( intval(U::get($counts, 'missing_owner_subscribe', 0)) > 0 ) {
            $causes[] = 'Owner subscribe rows are typically created on thread create; historical rows or partial maintenance runs can leave owner subscribe unset.';
        }
        if ( intval(U::get($counts, 'missing_owner_participation', 0)) > 0 ) {
            $causes[] = 'Participation rows are restored for owners during reset/repair and on posting; older data or interrupted maintenance may miss some owners.';
        }
        if ( count($causes) < 1 ) {
            $causes[] = 'No inconsistencies detected by the current checks.';
        }
        return $causes;
    }

    /**
     * Comments eligible for age-based removal: older than N months and no descendant newer than N months.
     *
     * @param string $p table prefix including trailing underscore segment as in $CFG->dbprefix
     * @param int $limit max rows (use 1 in delete loop)
     */
    private function expireCommentsCandidateSql($p, $limit)
    {
        $limit = intval($limit);
        if ( $limit < 1 ) {
            $limit = 1;
        }
        return "SELECT C.comment_id, C.thread_id, C.parent_id
FROM {$p}tdiscus_comment C
JOIN {$p}tdiscus_thread T ON T.thread_id = C.thread_id
JOIN {$p}lti_link L ON L.link_id = T.link_id
WHERE L.context_id = :CID
  AND C.created_at < DATE_SUB(NOW(), INTERVAL :MONTHS MONTH)
  AND NOT EXISTS (
    SELECT 1
    FROM {$p}tdiscus_closure CL
    JOIN {$p}tdiscus_comment D ON D.comment_id = CL.child_id
    WHERE CL.parent_id = C.comment_id
      AND CL.child_id <> C.comment_id
      AND D.created_at >= DATE_SUB(NOW(), INTERVAL :MONTHS2 MONTH)
  )
ORDER BY C.created_at, C.comment_id
LIMIT ".$limit;
    }

    private function expireCommentsCountSql($p)
    {
        return "SELECT COUNT(*) AS count
FROM {$p}tdiscus_comment C
JOIN {$p}tdiscus_thread T ON T.thread_id = C.thread_id
JOIN {$p}lti_link L ON L.link_id = T.link_id
WHERE L.context_id = :CID
  AND C.created_at < DATE_SUB(NOW(), INTERVAL :MONTHS MONTH)
  AND NOT EXISTS (
    SELECT 1
    FROM {$p}tdiscus_closure CL
    JOIN {$p}tdiscus_comment D ON D.comment_id = CL.child_id
    WHERE CL.parent_id = C.comment_id
      AND CL.child_id <> C.comment_id
      AND D.created_at >= DATE_SUB(NOW(), INTERVAL :MONTHS2 MONTH)
  )";
    }

    private function ensureTdiscusThreadsLoaded()
    {
        global $CFG;
        if ( class_exists('\Tdiscus\Threads', false) ) {
            return true;
        }
        $path = $CFG->dirroot . '/tool/tdiscus/util/threads.php';
        if ( is_readable($path) ) {
            require_once $path;
        }
        return class_exists('\Tdiscus\Threads', false);
    }

    private function renderExpireDryRunPanel($action_url, $result=false, $oldest_post_at=null, $oldest_thread_at=null)
    {
        $default_months = 2;
        if ( is_array($result) && isset($result['months']) ) {
            $default_months = intval($result['months']);
            if ( $default_months <= 1 ) $default_months = 2;
        }
        ?>
        <div class="panel panel-warning" style="margin-bottom: 1.5em;">
            <div class="panel-heading"><strong>Instructor: Expire old discussion threads</strong></div>
            <div class="panel-body">
                <?php if ( is_string($oldest_post_at) && strlen($oldest_post_at) > 0 ) { ?>
                    <p class="text-muted" style="margin-top: 0;">
                        Oldest post date in this course: <strong><?= htmlspecialchars($oldest_post_at) ?></strong>
                    </p>
                <?php } else if ( is_string($oldest_thread_at) && strlen($oldest_thread_at) > 0 ) { ?>
                    <p class="text-muted" style="margin-top: 0;">
                        No posts found yet in this course. Oldest thread creation date: <strong><?= htmlspecialchars($oldest_thread_at) ?></strong>
                    </p>
                <?php } else { ?>
                    <p class="text-muted" style="margin-top: 0;">
                        No discussion threads found in this course.
                    </p>
                <?php } ?>
                <p class="text-muted" style="margin-top: 0;">
                    This first version is dry run only. It never deletes data, and always shows the SQL that would run.
                </p>
                <form method="post" action="<?= htmlspecialchars($action_url) ?>" class="form-inline" style="margin-bottom: 1em;">
                    <?= self::csrfField() ?>
                    <div class="form-group">
                        <label for="expire-months">Months:</label>
                        <input id="expire-months" type="number" min="2" step="1" name="months"
                            class="form-control" style="width: 8em; margin-left: 0.5em;"
                            value="<?= htmlspecialchars((string) $default_months) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-warning" style="margin-left: 0.5em;">Dry Run</button>
                </form>

                <?php if ( is_array($result) ) { ?>
                    <?php
                        $candidate_sql_comment = str_replace('--', '- -', (string) U::get($result, 'candidate_sql', ''));
                        $delete_sql_comment = str_replace('--', '- -', (string) U::get($result, 'sql', ''));
                        $params_comment = str_replace('--', '- -', json_encode(U::get($result, 'params', array()), JSON_PRETTY_PRINT));
                    ?>
                    <div class="alert alert-info" style="margin-bottom: 0.75em;">
                        Matching threads for <strong><?= intval($result['months']) ?></strong> months: <strong><?= intval(U::get($result, 'count', 0)) ?></strong>
                        <?php if ( intval(U::get($result, 'confirmed', 0)) === 1 ) { ?>
                            <br/>Matching before delete: <strong><?= intval(U::get($result, 'count_before', U::get($result, 'count', 0))) ?></strong>
                            <br/>Deleted this run: <strong><?= intval(U::get($result, 'deleted_now', 0)) ?></strong>
                            <?php if ( intval(U::get($result, 'limit_hit', 0)) === 1 ) { ?>
                                <br/>Batch limit reached (<?= intval(U::get($result, 'batch_limit', self::EXPIRE_DELETE_BATCH_LIMIT)) ?>). Run again to continue.
                            <?php } ?>
                        <?php } ?>
                    </div>
                    <!-- Candidate SQL (limited batch)
<?= htmlspecialchars($candidate_sql_comment) ?>
SQL used for deletion
<?= htmlspecialchars($delete_sql_comment) ?>
Bound parameters
<?= htmlspecialchars($params_comment) ?>
                    -->
                    <?php if ( intval(U::get($result, 'count', 0)) > 0 ) { ?>
                        <form method="post" action="<?= htmlspecialchars($action_url) ?>" class="form-inline tsugi-expire-delete-form">
                            <?= self::csrfField() ?>
                            <input type="hidden" name="months" value="<?= intval($result['months']) ?>">
                            <input type="hidden" name="confirm" value="1">
                            <button type="submit" class="btn btn-danger"
                                data-deleting-label="<?= htmlspecialchars(__('Deleting…')) ?>">Delete Threads (no undo)</button>
                        </form>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
        <?php
    }

    private function renderExpireCommentsDryRunPanel($action_url, $result=false, $oldest_comment_at=null, $tdiscus_threads_ok=true)
    {
        $default_months = 2;
        if ( is_array($result) && isset($result['months']) ) {
            $default_months = intval($result['months']);
            if ( $default_months <= 1 ) {
                $default_months = 2;
            }
        }
        ?>
        <div class="panel panel-warning" style="margin-bottom: 1.5em;">
            <div class="panel-heading"><strong>Instructor: Expire old discussion comments</strong></div>
            <div class="panel-body">
                <?php if ( ! $tdiscus_threads_ok ) { ?>
                    <div class="alert alert-danger" style="margin-top: 0;">
                        The tdiscus tool (<code>tool/tdiscus/util/threads.php</code>) is not available, so comment expiration cannot run on this server.
                    </div>
                <?php } else if ( is_string($oldest_comment_at) && strlen($oldest_comment_at) > 0 ) { ?>
                    <p class="text-muted" style="margin-top: 0;">
                        Oldest comment date in this course: <strong><?= htmlspecialchars($oldest_comment_at) ?></strong>
                    </p>
                <?php } else { ?>
                    <p class="text-muted" style="margin-top: 0;">
                        No discussion comments found in this course.
                    </p>
                <?php } ?>
                <p class="text-muted" style="margin-top: 0;">
                    A comment is removed only when it is older than the cutoff <em>and</em> every reply under it (in the same thread) is also older than the cutoff.
                    Deleting one match removes that comment and its whole subtree. Dry run shows counts and SQL; confirming deletes up to <?= intval(self::EXPIRE_DELETE_BATCH_LIMIT) ?> subtree(s) per run.
                </p>
                <form method="post" action="<?= htmlspecialchars($action_url) ?>" class="form-inline" style="margin-bottom: 1em;">
                    <?= self::csrfField() ?>
                    <div class="form-group">
                        <label for="expire-comments-months">Months:</label>
                        <input id="expire-comments-months" type="number" min="2" step="1" name="months"
                            class="form-control" style="width: 8em; margin-left: 0.5em;"
                            value="<?= htmlspecialchars((string) $default_months) ?>" required
                            <?= $tdiscus_threads_ok ? '' : ' disabled' ?>>
                    </div>
                    <button type="submit" class="btn btn-warning" style="margin-left: 0.5em;"<?= $tdiscus_threads_ok ? '' : ' disabled' ?>>Dry Run</button>
                </form>

                <?php if ( is_array($result) ) { ?>
                    <?php
                        $candidate_sql_comment = str_replace('--', '- -', (string) U::get($result, 'candidate_sql', ''));
                        $delete_sql_comment = str_replace('--', '- -', (string) U::get($result, 'sql', ''));
                        $params_comment = str_replace('--', '- -', json_encode(U::get($result, 'params', array()), JSON_PRETTY_PRINT));
                    ?>
                    <div class="alert alert-info" style="margin-bottom: 0.75em;">
                        Matching comment subtree roots for <strong><?= intval($result['months']) ?></strong> months: <strong><?= intval(U::get($result, 'count', 0)) ?></strong>
                        <?php if ( intval(U::get($result, 'confirmed', 0)) === 1 ) { ?>
                            <br/>Matching before delete: <strong><?= intval(U::get($result, 'count_before', U::get($result, 'count', 0))) ?></strong>
                            <br/>Subtree roots deleted this run: <strong><?= intval(U::get($result, 'deleted_now', 0)) ?></strong>
                            <?php if ( intval(U::get($result, 'limit_hit', 0)) === 1 ) { ?>
                                <br/>Batch limit reached (<?= intval(U::get($result, 'batch_limit', self::EXPIRE_DELETE_BATCH_LIMIT)) ?>). Run again to continue.
                            <?php } ?>
                        <?php } ?>
                    </div>
                    <!-- Candidate SQL (limited batch)
<?= htmlspecialchars($candidate_sql_comment) ?>
SQL used for deletion
<?= htmlspecialchars($delete_sql_comment) ?>
Bound parameters
<?= htmlspecialchars($params_comment) ?>
                    -->
                    <?php if ( $tdiscus_threads_ok && intval(U::get($result, 'count', 0)) > 0 ) { ?>
                        <form method="post" action="<?= htmlspecialchars($action_url) ?>" class="form-inline tsugi-expire-delete-form">
                            <?= self::csrfField() ?>
                            <input type="hidden" name="months" value="<?= intval($result['months']) ?>">
                            <input type="hidden" name="confirm" value="1">
                            <button type="submit" class="btn btn-danger"
                                data-deleting-label="<?= htmlspecialchars(__('Deleting…')) ?>">Delete Comments (no undo)</button>
                        </form>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
        <?php
    }

    /**
     * Disable delete buttons and show a spinner after submit (threads/comments expire confirm forms).
     */
    private function emitExpireDeleteFormEnhancements()
    {
        ?>
<style>
@keyframes tsugiDiscussionsExpireSpin { to { transform: rotate(360deg); } }
.tsugi-discussions-expire-spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  margin-right: 6px;
  vertical-align: text-bottom;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: tsugiDiscussionsExpireSpin .65s linear infinite;
}
</style>
<script>
(function () {
  if (window.__tsugiExpireDeleteSubmitBound) return;
  window.__tsugiExpireDeleteSubmitBound = true;
  document.addEventListener('submit', function (ev) {
    var form = ev.target;
    if (!form || !form.classList || !form.classList.contains('tsugi-expire-delete-form')) return;
    var btn = form.querySelector('button[type="submit"]');
    if (!btn || btn.disabled) return;
    btn.disabled = true;
    btn.setAttribute('aria-busy', 'true');
    var label = btn.getAttribute('data-deleting-label') || 'Deleting…';
    var spin = document.createElement('span');
    spin.className = 'tsugi-discussions-expire-spinner';
    spin.setAttribute('aria-hidden', 'true');
    btn.textContent = '';
    btn.appendChild(spin);
    btn.appendChild(document.createTextNode(' ' + label));
  }, true);
})();
</script>
        <?php
    }

    private function rollupsForLink($link_id, $user_id, $has_mentions, $include_participation_as_personal)
    {
        global $PDOX, $CFG;

        $personal_join_mention = "";
        $personal_is_mention = "FALSE";
        if ( $has_mentions ) {
            $personal_join_mention = "LEFT JOIN {$CFG->dbprefix}tdiscus_mention M
                ON M.post_id = C.comment_id AND M.mentioned_user_id = :UID";
            $personal_is_mention = "M.mentioned_user_id IS NOT NULL";
        }

        $personal_participation_clause = "FALSE";
        if ( $include_participation_as_personal ) {
            $personal_participation_clause = "COALESCE(UT.subscribe, 0) = 1";
        }

        $personal = $PDOX->rowDie("SELECT COUNT(DISTINCT C.comment_id) AS count
            FROM {$CFG->dbprefix}tdiscus_comment C
            JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = C.thread_id
            LEFT JOIN {$CFG->dbprefix}tdiscus_user_thread UT
                ON UT.thread_id = C.thread_id AND UT.user_id = :UID
            LEFT JOIN {$CFG->dbprefix}tdiscus_comment P ON P.comment_id = C.parent_id
            $personal_join_mention
            WHERE T.link_id = :LID
              AND C.user_id <> :UID
              AND C.created_at > COALESCE(UT.read_at, '1970-01-01 00:00:00')
              AND (
                    P.user_id = :UID
                    OR (T.user_id = :UID AND C.parent_id > 0)
                    OR $personal_is_mention
                    OR $personal_participation_clause
              )",
            array(':UID' => $user_id, ':LID' => $link_id)
        );

        $participating = $PDOX->rowDie("SELECT COUNT(*) AS count
            FROM {$CFG->dbprefix}tdiscus_thread T
            LEFT JOIN {$CFG->dbprefix}tdiscus_user_thread UT
                ON UT.thread_id = T.thread_id AND UT.user_id = :UID
            WHERE T.link_id = :LID
              AND COALESCE(UT.subscribe, 0) = 1
              AND (T.comments - COALESCE(UT.comments, 0)) > 0",
            array(':UID' => $user_id, ':LID' => $link_id)
        );

        $global = $PDOX->rowDie("SELECT COUNT(*) AS count
            FROM {$CFG->dbprefix}tdiscus_comment C
            JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = C.thread_id
            LEFT JOIN {$CFG->dbprefix}tdiscus_user_thread UT
                ON UT.thread_id = C.thread_id AND UT.user_id = :UID
            WHERE T.link_id = :LID
              AND C.user_id <> :UID
              AND C.created_at > COALESCE(UT.read_at, '1970-01-01 00:00:00')",
            array(':UID' => $user_id, ':LID' => $link_id)
        );

        return array(
            'personal' => intval($personal['count']),
            'participating' => intval($participating['count']),
            'global' => intval($global['count']),
        );
    }

    private function tableExists($table_name)
    {
        global $PDOX;
        $row = $PDOX->rowDie("SELECT 1 AS present
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = :TN",
            array(':TN' => $table_name)
        );
        return is_array($row);
    }

    private function hasDiscussionReadBaseline($context_id, $user_id)
    {
        global $PDOX, $CFG;
        $row = $PDOX->rowDie("SELECT 1 AS present
            FROM {$CFG->dbprefix}tdiscus_user_thread UT
            JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = UT.thread_id
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            WHERE L.context_id = :CID
              AND UT.user_id = :UID
              AND (UT.read_at IS NOT NULL OR COALESCE(UT.comments, 0) > 0)
            LIMIT 1",
            array(':CID' => $context_id, ':UID' => $user_id)
        );
        return is_array($row);
    }

}

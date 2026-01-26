<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Core\LTIX;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class Announcements extends Tool {

    const ROUTE = '/announcements';
    const NAME = 'Announcements';
    const REDIRECT = 'tsugi_controllers_announcements';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Announcements@index');
        $app->router->get($prefix.'/', 'Announcements@index');
        $app->router->get('/'.self::REDIRECT, 'Announcements@index');
        $app->router->get($prefix.'/json', 'Announcements@json');
        $app->router->post($prefix.'/dismiss', 'Announcements@dismiss');
        $app->router->get($prefix.'/add', 'Announcements@add');
        $app->router->post($prefix.'/add', 'Announcements@addPost');
        $app->router->get($prefix.'/edit/{id}', 'Announcements@edit');
        $app->router->post($prefix.'/edit/{id}', 'Announcements@editPost');
        $app->router->get($prefix.'/manage', 'Announcements@manage');
        $app->router->post($prefix.'/manage', 'Announcements@managePost');
        $app->router->get($prefix.'/analytics', 'Announcements@analytics');
    }

    /**
     * Get announcements for a user in a context
     * 
     * Returns announcements created within the last 30 days, limited to 50.
     * Separates dismissed and undismissed announcements.
     * 
     * @param int $context_id The context ID
     * @param int $user_id The user ID
     * @return array Array with keys: all_announcements, undismissed, dismissed, announcements, dismissed_count, show_dismissed_section
     */
    private function getAnnouncementsForUser($context_id, $user_id) {
        global $CFG, $PDOX;
        
        // Get all announcements for this context, including dismissal status
        // Only show announcements created within the last 30 days, limit to 50
        $sql = "SELECT A.announcement_id, A.title, A.text, A.url, A.created_at, A.updated_at,
                    U.displayname AS creator_name,
                    CASE WHEN D.dismissal_id IS NOT NULL THEN 1 ELSE 0 END AS dismissed
                FROM {$CFG->dbprefix}announcement AS A
                LEFT JOIN {$CFG->dbprefix}lti_user AS U ON A.user_id = U.user_id
                LEFT JOIN {$CFG->dbprefix}announcement_dismissal AS D 
                    ON A.announcement_id = D.announcement_id AND D.user_id = :UID
                WHERE A.context_id = :CID
                  AND A.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY A.created_at DESC
                LIMIT 50";

        $all_announcements = $PDOX->allRowsDie($sql, array(
            ':CID' => $context_id,
            ':UID' => $user_id
        ));

        // Separate dismissed and undismissed announcements
        $undismissed = array();
        $dismissed = array();
        foreach ($all_announcements as $announcement) {
            if ($announcement['dismissed']) {
                $dismissed[] = $announcement;
            } else {
                $undismissed[] = $announcement;
            }
        }

        // If there are undismissed announcements, show only those
        // If no undismissed announcements, show dismissed ones directly
        $announcements = count($undismissed) > 0 ? $undismissed : $dismissed;
        $dismissed_count = count($dismissed);
        $show_dismissed_section = count($undismissed) > 0 && $dismissed_count > 0;

        return array(
            'all_announcements' => $all_announcements,
            'undismissed' => $undismissed,
            'dismissed' => $dismissed,
            'announcements' => $announcements,
            'dismissed_count' => $dismissed_count,
            'show_dismissed_section' => $show_dismissed_section
        );
    }

    public function index(Request $request)
    {
        global $CFG, $OUTPUT, $PDOX;
        
        $this->requireAuth();
        
        LTIX::getConnection();
        
        $context_id = $_SESSION['context_id'];
        $user_id = $_SESSION['id'];
        
        // Record learner analytics
        $this->lmsRecordLaunchAnalytics(self::ROUTE, self::NAME);
        
        // Check if user is instructor/admin
        $is_context_admin = $this->isInstructor();
        $is_admin = $this->isAdmin();
        $show_analytics = $is_context_admin || $is_admin;
        
        // Get announcements
        $announcement_data = $this->getAnnouncementsForUser($context_id, $user_id);
        $all_announcements = $announcement_data['all_announcements'];
        $undismissed = $announcement_data['undismissed'];
        $dismissed = $announcement_data['dismissed'];
        $announcements = $announcement_data['announcements'];
        $dismissed_count = $announcement_data['dismissed_count'];
        $show_dismissed_section = $announcement_data['show_dismissed_section'];
        
        $tool_home = $this->toolHome(self::ROUTE);
        $analytics_url = $tool_home . '/analytics';
        $manage_url = $tool_home . '/manage';
        $dismiss_url = $tool_home . '/dismiss';
        
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <div class="container">
            <h1>Announcements
                <span class="pull-right">
                    <?php if ($show_analytics): ?>
                        <a href="<?= $analytics_url ?>" class="btn btn-default">
                            <span class="glyphicon glyphicon-signal"></span> Analytics
                        </a>
                    <?php endif; ?>
                    <?php if ($is_context_admin): ?>
                        <a href="<?= $manage_url ?>" class="btn btn-default">Manage Announcements</a>
                    <?php endif; ?>
                </span>
            </h1>
            
            <?php if (count($announcements) == 0): ?>
                <div class="alert alert-info">
                    <p>No announcements at this time.</p>
                </div>
            <?php else: ?>
                <?php if ($show_dismissed_section): ?>
                    <p class="text-muted">
                        Dismissed: (<?= $dismissed_count ?>)
                        <button id="show-dismissed-btn" class="btn btn-sm btn-link" style="padding: 0; margin-left: 5px;">SHOW</button>
                    </p>
                <?php endif; ?>
                
                <div id="announcements-list">
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="panel panel-default announcement-item <?= $announcement['dismissed'] ? 'dismissed' : '' ?>" 
                             data-announcement-id="<?= htmlspecialchars($announcement['announcement_id']) ?>">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    <span class="dismiss-check-icon pull-right <?= $announcement['dismissed'] ? 'dismissed' : '' ?>" 
                                          data-dismiss-toggle
                                          data-dismiss-id="<?= htmlspecialchars($announcement['announcement_id']) ?>"
                                          data-announcement-id="<?= htmlspecialchars($announcement['announcement_id']) ?>"
                                          data-dismiss-title="Dismiss"
                                          data-dismiss-title-undismiss="Undismiss"
                                          title="<?= $announcement['dismissed'] ? 'Undismiss' : 'Dismiss' ?>"
                                          style="cursor: pointer;">
                                        <span class="glyphicon glyphicon-ok"></span>
                                    </span>
                                    <?= htmlspecialchars($announcement['title']) ?>
                                </h3>
                            </div>
                            <div class="panel-body">
                                <div class="announcement-text">
                                    <?= nl2br(htmlspecialchars($announcement['text'])) ?>
                                </div>
                                <?php if (!empty($announcement['url'])): ?>
                                    <p class="announcement-url">
                                        <a href="<?= htmlspecialchars($announcement['url']) ?>" target="_blank" class="btn btn-link">
                                            Learn more <span class="glyphicon glyphicon-new-window"></span>
                                        </a>
                                    </p>
                                <?php endif; ?>
                                <div class="text-muted small">
                                    <em>
                                        Posted <?= date('M j, Y g:i A', strtotime($announcement['created_at'])) ?>
                                        <?php if ($announcement['creator_name']): ?>
                                            by <?= htmlspecialchars($announcement['creator_name']) ?>
                                        <?php endif; ?>
                                    </em>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ($show_dismissed_section): ?>
                        <?php foreach ($dismissed as $announcement): ?>
                            <div class="panel panel-default announcement-item dismissed dismissed-hidden" 
                                 data-announcement-id="<?= htmlspecialchars($announcement['announcement_id']) ?>"
                                 style="display: none;">
                                <div class="panel-heading">
                                    <h3 class="panel-title">
                                        <span class="dismiss-check-icon pull-right dismissed" 
                                              data-dismiss-toggle
                                              data-dismiss-id="<?= htmlspecialchars($announcement['announcement_id']) ?>"
                                              data-announcement-id="<?= htmlspecialchars($announcement['announcement_id']) ?>"
                                              data-dismiss-title="Dismiss"
                                              data-dismiss-title-undismiss="Undismiss"
                                              title="Undismiss"
                                              style="cursor: pointer;">
                                            <span class="glyphicon glyphicon-ok"></span>
                                        </span>
                                        <?= htmlspecialchars($announcement['title']) ?>
                                    </h3>
                                </div>
                                <div class="panel-body">
                                    <div class="announcement-text">
                                        <?= nl2br(htmlspecialchars($announcement['text'])) ?>
                                    </div>
                                    <?php if (!empty($announcement['url'])): ?>
                                        <p class="announcement-url">
                                            <a href="<?= htmlspecialchars($announcement['url']) ?>" target="_blank" class="btn btn-link">
                                                Learn more <span class="glyphicon glyphicon-new-window"></span>
                                            </a>
                                        </p>
                                    <?php endif; ?>
                                    <div class="text-muted small">
                                        <em>
                                            Posted <?= date('M j, Y g:i A', strtotime($announcement['created_at'])) ?>
                                            <?php if ($announcement['creator_name']): ?>
                                                by <?= htmlspecialchars($announcement['creator_name']) ?>
                                            <?php endif; ?>
                                        </em>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .dismiss-check-icon {
            font-size: 18px;
            padding: 5px;
            margin-left: 10px;
            user-select: none;
        }
        .dismiss-check-icon:not(.dismissed) {
            color: #999;
            border: 2px solid #ccc;
            border-radius: 3px;
            padding: 3px 5px;
        }
        .dismiss-check-icon.dismissed {
            color: #5cb85c;
            background-color: #f0f8f0;
            border: 2px solid #5cb85c;
            border-radius: 3px;
            padding: 3px 5px;
        }
        .dismiss-check-icon:hover {
            opacity: 0.7;
        }
        .announcement-item.dismissed {
            opacity: 0.7;
            border-left: 4px solid #5cb85c;
        }
        .announcement-item.dismissed .panel-heading {
            background-color: #f0f8f0;
        }
        </style>

        <script src="<?= htmlspecialchars($this->staticUrl('dismiss.js')) ?>"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var showDismissedBtn = document.getElementById('show-dismissed-btn');
            if (showDismissedBtn) {
                var dismissedHidden = true;
                showDismissedBtn.addEventListener('click', function() {
                    var dismissedItems = document.querySelectorAll('.dismissed-hidden');
                    if (dismissedHidden) {
                        dismissedItems.forEach(function(item) {
                            item.style.display = '';
                        });
                        showDismissedBtn.textContent = 'HIDE';
                        dismissedHidden = false;
                    } else {
                        dismissedItems.forEach(function(item) {
                            item.style.display = 'none';
                        });
                        showDismissedBtn.textContent = 'SHOW';
                        dismissedHidden = true;
                    }
                });
            }
            
            if (typeof dismissToggle !== 'undefined') {
                <?php $dismiss_url = $this->toolHome(self::ROUTE) . '/dismiss'; ?>
                dismissToggle.init('<?= $dismiss_url ?>', {
                    selector: '.dismiss-check-icon',
                    idAttribute: 'data-announcement-id',
                    itemSelector: '.announcement-item',
                    dismissedClass: 'dismissed',
                    formDataBuilder: function(itemId, dismiss) {
                        var formData = new FormData();
                        formData.append('announcement_id', itemId);
                        formData.append('dismiss', dismiss ? 1 : 0);
                        return formData;
                    },
                    updateUI: function(item, trigger, isDismissed) {
                        if (isDismissed) {
                            item.classList.add('dismissed');
                            trigger.classList.add('dismissed');
                            trigger.setAttribute('title', 'Undismiss');
                            var undismissedItems = document.querySelectorAll('#announcements-list .announcement-item:not(.dismissed)');
                            if (undismissedItems.length > 0) {
                                item.classList.add('dismissed-hidden');
                                item.style.display = 'none';
                            }
                            updateDismissedCount(1);
                        } else {
                            item.classList.remove('dismissed');
                            item.classList.remove('dismissed-hidden');
                            trigger.classList.remove('dismissed');
                            trigger.setAttribute('title', 'Dismiss');
                            item.style.display = '';
                            updateDismissedCount(-1);
                        }
                    },
                    onError: function(error, item, trigger) {
                        alert(error);
                    }
                });
            }
            
            function updateDismissedCount(change) {
                var dismissedText = document.querySelector('.text-muted');
                if (dismissedText) {
                    var match = dismissedText.textContent.match(/Dismissed: \((\d+)\)/);
                    if (match) {
                        var currentCount = parseInt(match[1]);
                        var newCount = Math.max(0, currentCount + change);
                        if (newCount === 0) {
                            dismissedText.style.display = 'none';
                        } else {
                            dismissedText.innerHTML = 'Dismissed: (' + newCount + ') <button id="show-dismissed-btn" class="btn btn-sm btn-link" style="padding: 0; margin-left: 5px;">SHOW</button>';
                            var newBtn = document.getElementById('show-dismissed-btn');
                            if (newBtn) {
                                var dismissedHidden = true;
                                newBtn.addEventListener('click', function() {
                                    var dismissedItems = document.querySelectorAll('.dismissed-hidden');
                                    if (dismissedHidden) {
                                        dismissedItems.forEach(function(item) {
                                            item.style.display = '';
                                        });
                                        newBtn.textContent = 'HIDE';
                                        dismissedHidden = false;
                                    } else {
                                        dismissedItems.forEach(function(item) {
                                            item.style.display = 'none';
                                        });
                                        newBtn.textContent = 'SHOW';
                                        dismissedHidden = true;
                                    }
                                });
                            }
                        }
                    }
                }
            }
        });
        </script>

        <?php
        $OUTPUT->footer();
    }

    public function json(Request $request)
    {
        global $CFG, $PDOX;
        
        $this->requireAuth();
        
        LTIX::getConnection();
        
        $context_id = $_SESSION['context_id'];
        $user_id = $_SESSION['id'];
        
        $announcement_data = $this->getAnnouncementsForUser($context_id, $user_id);
        $announcements = $announcement_data['announcements'];
        
        $result = array(
            'status' => 'success',
            'announcements' => array(),
            'dismissed_count' => $announcement_data['dismissed_count']
        );
        
        foreach ($announcements as $announcement) {
            $result['announcements'][] = array(
                'announcement_id' => intval($announcement['announcement_id']),
                'title' => $announcement['title'],
                'text' => $announcement['text'],
                'url' => $announcement['url'],
                'created_at' => $announcement['created_at'],
                'updated_at' => $announcement['updated_at'],
                'creator_name' => $announcement['creator_name'],
                'dismissed' => $announcement['dismissed'] == 1
            );
        }
        
        return new JsonResponse($result);
    }

    public function dismiss(Request $request)
    {
        global $CFG, $PDOX;
        
        $this->requireAuth();
        
        LTIX::getConnection();
        
        if ($request->getMethod() !== 'POST') {
            return new JsonResponse(['status' => 'error', 'detail' => 'Method not allowed'], 405);
        }
        
        $announcement_id = U::get($_POST, 'announcement_id');
        if (!$announcement_id || !is_numeric($announcement_id)) {
            return new JsonResponse(['status' => 'error', 'detail' => 'Invalid announcement_id'], 400);
        }
        
        $dismiss = U::get($_POST, 'dismiss', 1);
        $dismiss = ($dismiss == 1 || $dismiss === '1' || $dismiss === true) ? 1 : 0;
        
        $user_id = $_SESSION['id'];
        $context_id = $_SESSION['context_id'];
        $announcement_id = intval($announcement_id);
        
        // Verify the announcement exists and belongs to this context
        $announcement = $PDOX->rowDie(
            "SELECT announcement_id FROM {$CFG->dbprefix}announcement 
             WHERE announcement_id = :AID AND context_id = :CID",
            array(':AID' => $announcement_id, ':CID' => $context_id)
        );
        
        if (!$announcement) {
            return new JsonResponse(['status' => 'error', 'detail' => 'Announcement not found'], 404);
        }
        
        // Check if already dismissed
        $existing = $PDOX->rowDie(
            "SELECT dismissal_id FROM {$CFG->dbprefix}announcement_dismissal 
             WHERE announcement_id = :AID AND user_id = :UID",
            array(':AID' => $announcement_id, ':UID' => $user_id)
        );
        
        if ($dismiss) {
            // Dismiss: Insert dismissal if not already dismissed
            if (!$existing) {
                $sql = "INSERT INTO {$CFG->dbprefix}announcement_dismissal 
                        (announcement_id, user_id, dismissed_at) 
                        VALUES (:AID, :UID, NOW())";
                $values = array(
                    ':AID' => $announcement_id,
                    ':UID' => $user_id
                );
                $q = $PDOX->queryReturnError($sql, $values);
                if (!$q->success) {
                    return new JsonResponse(['status' => 'error', 'detail' => 'Database error'], 500);
                }
            }
        } else {
            // Undismiss: Delete dismissal if it exists
            if ($existing) {
                $sql = "DELETE FROM {$CFG->dbprefix}announcement_dismissal 
                        WHERE announcement_id = :AID AND user_id = :UID";
                $values = array(
                    ':AID' => $announcement_id,
                    ':UID' => $user_id
                );
                $q = $PDOX->queryReturnError($sql, $values);
                if (!$q->success) {
                    return new JsonResponse(['status' => 'error', 'detail' => 'Database error'], 500);
                }
            }
        }
        
        return new JsonResponse(['status' => 'success']);
    }

    public function add(Request $request)
    {
        global $CFG, $OUTPUT;
        
        $this->requireInstructor('/announcements');
        
        $tool_home = $this->toolHome(self::ROUTE);
        $manage_url = $tool_home . '/manage';
        $index_url = $tool_home;
        $add_url = $tool_home . '/add';
        
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <div class="container">
            <h1>Add New Announcement</h1>
            
            <p>
                <a href="<?= $manage_url ?>" class="btn btn-default">Back to Manage</a>
                <a href="<?= $index_url ?>" class="btn btn-default">Student View</a>
            </p>
            
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Create New Announcement</h3>
                </div>
                <div class="panel-body">
                    <form method="POST" action="<?= $add_url ?>">
                        <div class="form-group">
                            <label for="title">Title *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= htmlspecialchars(U::get($_POST, 'title', '')) ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="text">Text *</label>
                            <textarea class="form-control" id="text" name="text" rows="5" required><?= htmlspecialchars(U::get($_POST, 'text', '')) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="url">URL (optional)</label>
                            <input type="url" class="form-control" id="url" name="url" 
                                   value="<?= htmlspecialchars(U::get($_POST, 'url', '')) ?>" 
                                   placeholder="https://example.com">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Create Announcement</button>
                        <a href="<?= $manage_url ?>" class="btn btn-default">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
        <?php
        $OUTPUT->footer();
    }

    public function addPost(Request $request)
    {
        global $CFG, $PDOX;
        
        $this->requireInstructor('/announcements');
        
        $tool_home = $this->toolHome(self::ROUTE);
        $add_url = $tool_home . '/add';
        $manage_url = $tool_home . '/manage';
        
        LTIX::getConnection();
        
        $context_id = $_SESSION['context_id'];
        $user_id = $_SESSION['id'];
        
        $title = trim(U::get($_POST, 'title'));
        $text = trim(U::get($_POST, 'text'));
        $url = trim(U::get($_POST, 'url'));
        
        if (empty($title) || empty($text)) {
            $_SESSION['error'] = 'Title and text are required';
            return new RedirectResponse($add_url);
        }
        
        if (empty($url)) {
            $url = null;
        }
        
        $sql = "INSERT INTO {$CFG->dbprefix}announcement 
                (context_id, title, text, url, user_id, created_at, updated_at) 
                VALUES (:CID, :title, :text, :url, :UID, NOW(), NOW())";
        $values = array(
            ':CID' => $context_id,
            ':title' => $title,
            ':text' => $text,
            ':url' => $url,
            ':UID' => $user_id
        );
        $q = $PDOX->queryReturnError($sql, $values);
        if ($q->success) {
            $_SESSION['success'] = 'Announcement created successfully';
            return new RedirectResponse($manage_url);
        } else {
            $_SESSION['error'] = 'Error creating announcement';
            return new RedirectResponse($add_url);
        }
    }

    public function edit(Request $request, $id)
    {
        global $CFG, $OUTPUT, $PDOX;
        
        $this->requireInstructor('/announcements');
        
        $tool_home = $this->toolHome(self::ROUTE);
        $manage_url = $tool_home . '/manage';
        $index_url = $tool_home;
        
        LTIX::getConnection();
        
        $context_id = $_SESSION['context_id'];
        $announcement_id = intval($id);
        
        if (!$announcement_id) {
            $_SESSION['error'] = 'Invalid announcement ID';
            return new RedirectResponse($manage_url);
        }
        
        // Get announcement for editing
        $announcement = $PDOX->rowDie(
            "SELECT * FROM {$CFG->dbprefix}announcement 
             WHERE announcement_id = :AID AND context_id = :CID",
            array(':AID' => $announcement_id, ':CID' => $context_id)
        );
        
        if (!$announcement) {
            $_SESSION['error'] = 'Announcement not found';
            return new RedirectResponse($manage_url);
        }
        
        $edit_url = $tool_home . '/edit/' . $announcement_id;
        
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <div class="container">
            <h1>Edit Announcement</h1>
            
            <p>
                <a href="<?= $manage_url ?>" class="btn btn-default">Back to Manage</a>
                <a href="<?= $index_url ?>" class="btn btn-default">Student View</a>
            </p>
            
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Edit Announcement</h3>
                </div>
                <div class="panel-body">
                    <form method="POST" action="<?= $edit_url ?>">
                        <div class="form-group">
                            <label for="title">Title *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= htmlspecialchars($announcement['title']) ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="text">Text *</label>
                            <textarea class="form-control" id="text" name="text" rows="5" required><?= htmlspecialchars($announcement['text']) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="url">URL (optional)</label>
                            <input type="url" class="form-control" id="url" name="url" 
                                   value="<?= htmlspecialchars($announcement['url'] ?: '') ?>" 
                                   placeholder="https://example.com">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Announcement</button>
                        <a href="<?= $manage_url ?>" class="btn btn-default">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
        <?php
        $OUTPUT->footer();
    }

    public function editPost(Request $request, $id)
    {
        global $CFG, $PDOX;
        
        $this->requireInstructor('/announcements');
        
        $tool_home = $this->toolHome(self::ROUTE);
        $manage_url = $tool_home . '/manage';
        
        LTIX::getConnection();
        
        $context_id = $_SESSION['context_id'];
        $announcement_id = intval($id);
        
        $title = trim(U::get($_POST, 'title'));
        $text = trim(U::get($_POST, 'text'));
        $url = trim(U::get($_POST, 'url'));
        
        if (empty($title) || empty($text)) {
            $_SESSION['error'] = 'Title and text are required';
            $edit_url = $tool_home . '/edit/' . $announcement_id;
            return new RedirectResponse($edit_url);
        }
        
        if (empty($url)) {
            $url = null;
        }
        
        $sql = "UPDATE {$CFG->dbprefix}announcement 
                SET title = :title, text = :text, url = :url, updated_at = NOW() 
                WHERE announcement_id = :AID AND context_id = :CID";
        $values = array(
            ':title' => $title,
            ':text' => $text,
            ':url' => $url,
            ':AID' => $announcement_id,
            ':CID' => $context_id
        );
        $q = $PDOX->queryReturnError($sql, $values);
        if ($q->success) {
            $_SESSION['success'] = 'Announcement updated successfully';
            return new RedirectResponse($manage_url);
        } else {
            $_SESSION['error'] = 'Error updating announcement';
            $edit_url = $tool_home . '/edit/' . $announcement_id;
            return new RedirectResponse($edit_url);
        }
    }

    public function manage(Request $request)
    {
        global $CFG, $OUTPUT, $PDOX;
        
        $this->requireInstructor('/announcements');
        
        $tool_home = $this->toolHome(self::ROUTE);
        $back_url = $tool_home;
        $add_url = $tool_home . '/add';
        
        LTIX::getConnection();
        
        $context_id = $_SESSION['context_id'];
        
        // Get all announcements for this context with read counts
        $announcements = $PDOX->allRowsDie(
            "SELECT A.*, U.displayname AS creator_name,
                    COUNT(D.dismissal_id) AS read_count
             FROM {$CFG->dbprefix}announcement AS A
             LEFT JOIN {$CFG->dbprefix}lti_user AS U ON A.user_id = U.user_id
             LEFT JOIN {$CFG->dbprefix}announcement_dismissal AS D ON A.announcement_id = D.announcement_id
             WHERE A.context_id = :CID 
             GROUP BY A.announcement_id, A.context_id, A.title, A.text, A.url, A.user_id, A.created_at, A.updated_at, U.displayname
             ORDER BY A.created_at DESC",
            array(':CID' => $context_id)
        );
        
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <div class="container">
            <h1>Manage Announcements
                <span class="pull-right">
                    <a href="<?= $back_url ?>" class="btn btn-default" style="margin-right: 10px;">Back</a>
                    <a href="<?= $add_url ?>" class="btn btn-primary">Add New Announcement</a>
                </span>
            </h1>
            
            <?php if (count($announcements) == 0): ?>
                <div class="alert alert-info">
                    <p>No announcements yet. <a href="<?= $add_url ?>">Create one</a>.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Text Preview</th>
                                <th>URL</th>
                                <th>Created</th>
                                <th>Creator</th>
                                <th>Read Count</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($announcements as $announcement): ?>
                                <tr>
                                    <td><?= htmlspecialchars($announcement['title']) ?></td>
                                    <td><?= htmlspecialchars(substr($announcement['text'], 0, 100)) ?><?= strlen($announcement['text']) > 100 ? '...' : '' ?></td>
                                    <td>
                                        <?php if (!empty($announcement['url'])): ?>
                                            <a href="<?= htmlspecialchars($announcement['url']) ?>" target="_blank">
                                                <?= htmlspecialchars(substr($announcement['url'], 0, 30)) ?><?= strlen($announcement['url']) > 30 ? '...' : '' ?>
                                            </a>
                                        <?php else: ?>
                                            <em>None</em>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M j, Y g:i A', strtotime($announcement['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($announcement['creator_name'] ?: 'Unknown') ?></td>
                                    <td><?= htmlspecialchars($announcement['read_count'] ?: '0') ?></td>
                                    <td>
                                        <?php $edit_url = $tool_home . '/edit/' . $announcement['announcement_id']; ?>
                                        <a href="<?= $edit_url ?>" 
                                           class="btn btn-sm btn-primary">Edit</a>
                                        <?php $manage_url = $tool_home . '/manage'; ?>
                                        <form method="POST" action="<?= $manage_url ?>" 
                                              style="display: inline-block;" 
                                              onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="announcement_id" value="<?= htmlspecialchars($announcement['announcement_id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
        $OUTPUT->footer();
    }

    public function managePost(Request $request)
    {
        global $CFG, $PDOX;
        
        $this->requireInstructor('/announcements');
        
        $tool_home = $this->toolHome(self::ROUTE);
        $manage_url = $tool_home . '/manage';
        
        LTIX::getConnection();
        
        $context_id = $_SESSION['context_id'];
        
        // Handle delete action
        $action = U::get($_POST, 'action');
        $announcement_id = U::get($_POST, 'announcement_id');
        
        if ($action === 'delete' && $announcement_id) {
            // Verify ownership/context
            $check = $PDOX->rowDie(
                "SELECT announcement_id FROM {$CFG->dbprefix}announcement 
                 WHERE announcement_id = :AID AND context_id = :CID",
                array(':AID' => $announcement_id, ':CID' => $context_id)
            );
            if ($check) {
                $sql = "DELETE FROM {$CFG->dbprefix}announcement 
                        WHERE announcement_id = :AID AND context_id = :CID";
                $q = $PDOX->queryReturnError($sql, array(':AID' => $announcement_id, ':CID' => $context_id));
                if ($q->success) {
                    $_SESSION['success'] = 'Announcement deleted successfully';
                } else {
                    $_SESSION['error'] = 'Error deleting announcement';
                }
            } else {
                $_SESSION['error'] = 'Announcement not found';
            }
        }
        
        return new RedirectResponse($manage_url);
    }

    public function analytics(Request $request)
    {
        return $this->showAnalytics(self::ROUTE, self::NAME);
    }
}

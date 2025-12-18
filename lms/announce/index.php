<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";
require_once "../lms-util.php";
require_once "announcement-util.php";

LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! U::get($_SESSION,'id') ) {
    die('Must be logged in');
}

if ( ! isset($_SESSION['context_id']) ) {
    die('Context required');
}

$context_id = $_SESSION['context_id'];
$user_id = $_SESSION['id'];

// Check if user is instructor/admin for this context
$is_context_admin = isInstructor();

// Get announcements using shared utility
$announcement_data = getAnnouncementsForUser($context_id, $user_id);
$all_announcements = $announcement_data['all_announcements'];
$undismissed = $announcement_data['undismissed'];
$dismissed = $announcement_data['dismissed'];
$announcements = $announcement_data['announcements'];
$dismissed_count = $announcement_data['dismissed_count'];
$show_dismissed_section = $announcement_data['show_dismissed_section'];

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<div class="container">
    <h1>Announcements
        <?php if ($is_context_admin): ?>
            <a href="<?= addSession('manage.php') ?>" class="btn btn-default pull-right">Manage Announcements</a>
        <?php endif; ?>
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
    /* Not dismissed: outlined style */
    color: #999;
    border: 2px solid #ccc;
    border-radius: 3px;
    padding: 3px 5px;
}
.dismiss-check-icon.dismissed {
    /* Dismissed: colored/filled */
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


<script src="<?= addSession('dismiss.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle show/hide dismissed announcements
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
    
    // Initialize dismiss functionality with announcement-specific configuration
    dismissToggle.init('<?= addSession('dismiss.php') ?>', {
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
            // Update classes
            if (isDismissed) {
                item.classList.add('dismissed');
                trigger.classList.add('dismissed');
                trigger.setAttribute('title', 'Undismiss');
                // Hide the item if there are still undismissed items
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
                    // Re-attach event listener to new button
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

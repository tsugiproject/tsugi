<?php
/**
 * Author Interface Template
 * 
 * This file contains the HTML/CSS/JavaScript for the lesson authoring interface.
 * It's included by Lessons.php controller - no references to /lms directory.
 * 
 * Variables expected:
 * - $lessons_title: HTML-escaped lessons title
 * - $lessons_file_escaped: HTML-escaped lessons file path
 * - $lessons_json: JSON-encoded lessons data for JavaScript
 * - $export_url: session-bearing URL to download the saved document
 * - $export_v2_url: session-bearing URL to download Lessons JSON v2
 * - $import_url: session-bearing URL to POST an uploaded lessons.json
 * - $files_json_url: session-bearing URL for course Files JSON (picker)
 * - $files_home_url: session-bearing URL for the Files tool
 * - $pages_json_url, $lessons_json_url, $pages_base, $app_home: link picker
 */
?>
<style>
.lesson-author {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.lesson-author-header {
    background: #f5f5f5;
    padding: 20px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.lesson-author-header h1 {
    margin: 0 0 10px 0;
    color: #333;
}

.lesson-author-header .info {
    color: #666;
    font-size: 14px;
}

.author-io {
    margin-top: 12px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}

.author-io form {
    display: inline;
    margin: 0;
}

.author-io input[type="file"] {
    display: none;
}

.module-container {
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.module-header {
    background: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.module-header:hover {
    background: #e9ecef;
}

.drag-handle {
    cursor: grab;
    color: #999;
    padding: 8px 6px;
    margin-right: 8px;
    user-select: none;
    display: inline-flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    opacity: 0.6;
    transition: opacity 0.2s;
    position: relative;
    width: 12px;
    pointer-events: auto;
    z-index: 10;
}

.drag-handle:hover {
    opacity: 1;
    color: #666;
}

.drag-handle:active {
    cursor: grabbing;
}

.drag-handle::before {
    content: "";
    position: absolute;
    left: 2px;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 14px;
    background: currentColor;
    border-radius: 2px;
    box-shadow: 5px 0 0 currentColor;
}

.module-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.module-icon {
    font-size: 18px;
    color: #666;
}

.module-actions {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 6px 12px;
    border: 1px solid #ccc;
    background: white;
    border-radius: 3px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    color: #333;
    display: inline-block;
}

.btn:hover {
    background: #f0f0f0;
}

.btn-primary {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.btn-primary:hover {
    background: #0056b3;
    border-color: #0056b3;
}

.btn-danger {
    background: #dc3545;
    color: white;
    border-color: #dc3545;
}

.btn-danger:hover {
    background: #c82333;
    border-color: #bd2130;
}

.btn-success {
    background: #28a745;
    color: white;
    border-color: #28a745;
}

.btn-success:hover {
    background: #218838;
    border-color: #1e7e34;
}

.btn-icon {
    padding: 6px 10px;
    min-width: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    background: white;
    color: #333;
    border-color: #ccc;
}

.btn-icon:hover {
    background: #f0f0f0;
}

.module-body {
    padding: 20px;
    display: block;
    transition: max-height 0.3s ease-out, padding 0.3s ease-out, opacity 0.2s ease-out;
    overflow: hidden;
    max-height: 10000px;
    opacity: 1;
}

.module-body.collapsed {
    max-height: 0;
    padding: 0 20px;
    opacity: 0;
    overflow: hidden;
}

.expand-toggle {
    cursor: pointer;
    color: #666;
    font-size: 14px;
    padding: 4px 8px;
    border: none;
    background: transparent;
    user-select: none;
    display: inline-flex;
    align-items: center;
    transition: transform 0.2s ease;
}

.expand-toggle:hover {
    color: #333;
}

.expand-toggle.collapsed {
    transform: rotate(-90deg);
}

.expand-toggle::before {
    content: "▼";
    font-size: 12px;
    margin-right: 4px;
}

.module-description {
    margin-bottom: 15px;
    padding: 10px 12px;
    color: #666;
    font-size: 14px;
    line-height: 1.5;
    border-radius: 3px;
}

.module-description-edit {
    display: block;
    width: 100%;
    text-align: left;
    background: #fafafa;
    border: 1px dashed #ccc;
    cursor: pointer;
}

.module-description-edit:hover {
    border-color: #007bff;
    background: #f0f7ff;
}

.module-description-edit.module-description-empty {
    color: #999;
    font-style: italic;
}

.items-list {
    margin-top: 20px;
}

.item {
    background: white;
    border: 1px solid #ddd;
    border-radius: 3px;
    margin-bottom: 10px;
    padding: 15px;
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: default;
}

.item:hover {
    border-color: #007bff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.item-content {
    flex: 1;
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    width: 100%;
}

.item-type {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 3px;
    font-size: 14px;
    color: white;
    margin-right: 8px;
}

.item-type.header { display: none; }
.item-type.video { background: #dc3545; }
.item-type.reference { background: #17a2b8; }
.item-type.discussion { background: #ffc107; color: #333; }
.item-type.lti { background: #28a745; }
.item-type.assignment { background: #fd7e14; }
.item-type.slide { background: #6f42c1; }
.item-type.file { background: #6c757d; }

.file-picker-summary {
    margin-top: 8px;
    padding: 8px 10px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 13px;
    color: #555;
}

.file-picker-empty {
    color: #666;
    font-size: 14px;
}

.item-title {
    font-weight: 600;
    color: #333;
    margin: 0;
    flex: 1;
    margin-left: 10px;
}

.item.header-item {
    background: #f8f9fa;
    border-left: 3px solid #007bff;
}

.header-toggle {
    cursor: pointer;
    color: #666;
    font-size: 14px;
    padding: 4px 8px;
    border: none;
    background: transparent;
    user-select: none;
    display: inline-flex;
    align-items: center;
    transition: transform 0.2s ease;
    margin-right: 8px;
}

.header-toggle:hover {
    color: #333;
}

.header-toggle.collapsed {
    transform: rotate(-90deg);
}

.header-toggle::before {
    content: "▼";
    font-size: 12px;
}

.item.collapsed-section {
    display: none !important;
    /* Hide from sortable as well - these items won't be draggable when collapsed */
}

.item-actions {
    display: flex;
    gap: 5px;
}

.item-details {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #eee;
}

.item-details label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: #555;
    font-size: 13px;
}

.item-details input,
.item-details textarea,
.item-details select {
    width: 100%;
    padding: 6px;
    border: 1px solid #ccc;
    border-radius: 3px;
    font-size: 13px;
    box-sizing: border-box;
    margin-bottom: 10px;
}

.custom-fields {
    margin-top: 10px;
}

.custom-field {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    align-items: center;
}

.custom-field input {
    flex: 1;
    margin-bottom: 0;
}

.add-item-btn,
.add-module-btn {
    margin-top: 15px;
}

.save-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #333;
    color: white;
    padding: 15px 20px;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.2);
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 1000;
}

.save-bar.hidden {
    display: none;
}

.save-bar .message {
    flex: 1;
}

.save-bar .actions {
    display: flex;
    gap: 10px;
}

.ui-sortable-placeholder {
    height: 50px;
    background: #f0f0f0;
    border: 2px dashed #007bff;
    border-radius: 3px;
    margin-bottom: 10px;
    visibility: visible !important;
}

.ui-sortable-helper {
    opacity: 0.8;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 20px;
    border-radius: 4px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.modal-header h2 {
    margin: 0;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.close:hover {
    color: #000;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: #555;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 3px;
    font-size: 14px;
    box-sizing: border-box;
}

.form-group textarea {
    min-height: 80px;
    resize: vertical;
}

.form-group-radios {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}

.form-group-radios label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 400;
    margin: 0;
}

.form-group-radios input[type="radio"] {
    width: auto;
    margin: 0;
}

.form-actions {
    margin-top: 20px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.loading-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 3000;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    color: white;
    font-size: 16px;
}

.loading-overlay.active {
    display: flex;
}

.loading-spinner {
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top: 4px solid white;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin-bottom: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media (prefers-reduced-motion: reduce) {
    .loading-spinner {
        animation: none;
        border-top-color: rgba(255, 255, 255, 0.6);
    }
}
<?php \Tsugi\UI\CKEditor::renderStyles(['includeLinkPicker' => true, 'includeLinkUnderline' => false]); ?>
</style>

<div class="lesson-author">
    <div class="lesson-author-header">
        <h1><?= $lessons_title ?></h1>
        <div class="info">
            <?= $lessons_file_escaped ?>
        </div>
        <div class="author-io">
            <a class="btn" href="<?= htmlspecialchars($export_url) ?>"><?= __('Export lessons.json') ?></a>
            <a class="btn" href="<?= htmlspecialchars($export_v2_url) ?>"><?= __('Export lessons.json v2') ?></a>
            <form id="lessons-import-form" onsubmit="return false;">
                <input type="file" id="lessons-import-file" name="file" accept=".json,application/json" />
                <button type="button" class="btn" onclick="document.getElementById('lessons-import-file').click()"><?= __('Import lessons.json') ?></button>
            </form>
        </div>
    </div>

    <div id="modules-container">
        <!-- Modules will be rendered here -->
    </div>

    <div class="add-module-btn">
        <button type="button" class="btn btn-icon" onclick="addModule()" title="Add Module" aria-label="Add module">
            <i class="fa fa-plus" aria-hidden="true"></i>
        </button>
    </div>
</div>

<div id="save-bar" class="save-bar hidden">
    <div class="message">You have unsaved changes</div>
    <div class="actions">
        <button type="button" class="btn btn-success" onclick="saveChanges()">Save Changes</button>
        <button type="button" class="btn" onclick="discardChanges()">Discard</button>
    </div>
</div>

<!-- Modal for editing items -->
<div id="item-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title">Edit Item</h2>
            <button type="button" class="close" onclick="closeModal()" aria-label="Close">&times;</button>
        </div>
        <div id="modal-body">
            <!-- Form will be inserted here -->
        </div>
    </div>
</div>

<?php \Tsugi\UI\CKEditor::renderLinkPickerModal('Choose a link'); ?>

<!-- Loading overlay for drag-and-drop updates -->
<div id="loading-overlay" class="loading-overlay" role="status" aria-live="polite" aria-busy="false">
    <div class="loading-spinner" aria-hidden="true"></div>
    <div id="loading-overlay-text">Updating structure...</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
let lessonsData = <?= $lessons_json ?>;
if (!lessonsData.lessons_json_version) {
    lessonsData.lessons_json_version = 2;
}
var filesJsonUrl = <?= json_encode($files_json_url ?? '') ?>;
var filesHomeUrl = <?= json_encode($files_home_url ?? '') ?>;
var pagesJsonUrl = <?= json_encode($pages_json_url ?? '') ?>;
var lessonsJsonUrl = <?= json_encode($lessons_json_url ?? '') ?>;
var pagesBase = <?= json_encode($pages_base ?? '') ?>;
var filesBase = filesHomeUrl;
var appHome = <?= json_encode($app_home ?? '') ?>;
var currentPageId = null;
<?php \Tsugi\UI\CKEditor::renderLinkPickerScript(); ?>
let courseFilesCache = null;
let filePickerState = emptyFilePickerState();
let hasChanges = false;
let editingItemIndex = null;
let editingModuleIndex = null;
let editingAfterItemIndex = null;

function emptyFilePickerState() {
    return { sha256: '', filename: '', href: '', content_type: '', path: '' };
}

function isHeadingItem(item) {
    return !!(item && (item.type === 'heading' || item.type === 'header'));
}

function discussionRlidBase(title) {
    let slug = String(title || '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    if (!slug) {
        slug = 'topic';
    }
    if (slug.length > 40) {
        slug = slug.substring(0, 40).replace(/_+$/g, '');
    }
    return 'discussion_' + slug;
}

function collectUsedResourceLinkIds(skipModuleIndex, skipItemIndex) {
    const used = {};
    function add(id) {
        if (typeof id === 'string') {
            id = id.trim();
            if (id) {
                used[id] = true;
            }
        }
    }
    (lessonsData.discussions || []).forEach(function(d) { add(d && d.resource_link_id); });
    (lessonsData.launches || []).forEach(function(d) { add(d && d.resource_link_id); });
    (lessonsData.modules || []).forEach(function(mod, mi) {
        (mod.items || []).forEach(function(item, ii) {
            if (mi === skipModuleIndex && ii === skipItemIndex) {
                return;
            }
            add(item && item.resource_link_id);
        });
        (mod.discussions || []).forEach(function(d) { add(d && d.resource_link_id); });
        (mod.lti || []).forEach(function(d) { add(d && d.resource_link_id); });
    });
    return used;
}

function allocateDiscussionRlid(title, used) {
    const base = discussionRlidBase(title);
    let rlid = base;
    let n = 2;
    while (used[rlid]) {
        rlid = base + '_' + n;
        n++;
        if (n > 50) {
            rlid = base + '_' + Math.random().toString(16).slice(2, 8);
            break;
        }
    }
    used[rlid] = true;
    return rlid;
}

function itemEditorKind(item) {
    if (!item || !item.type) return 'heading';
    if (isHeadingItem(item)) return 'heading';
    const sub = item.subtype || '';
    if (item.type === 'video' || sub === 'video') return 'video';
    if (item.type === 'discussion' || sub === 'discussion') return 'discussion';
    if (item.type === 'assignment' || sub === 'assignment') return 'assignment';
    if (item.type === 'file') return 'file';
    if (item.type === 'slide' || item.type === 'slides' || sub === 'slides') return 'slide';
    if (item.type === 'reference' || sub === 'reference') return 'reference';
    if (item.type === 'lti') return 'lti';
    if (item.type === 'html_page') return 'assignment';
    if (item.type === 'web_link') return 'reference';
    return 'reference';
}

// Migrate FCPX to reference for video items
function migrateFCPXToReference() {
    if (!lessonsData.modules || !Array.isArray(lessonsData.modules)) {
        return;
    }
    
    lessonsData.modules.forEach(module => {
        if (module.items && Array.isArray(module.items)) {
            module.items.forEach(item => {
                if ((item.type === 'video' || item.subtype === 'video') && item.FCPX && !item.reference) {
                    item.reference = item.FCPX;
                    delete item.FCPX;
                }
            });
        }
    });
}

// Initialize
$(document).ready(function() {
    migrateFCPXToReference();
    renderModules();
    setupSortable();

    var importFile = document.getElementById('lessons-import-file');
    if (importFile) {
        importFile.addEventListener('change', function() {
            if (!this.files || !this.files.length) return;
            var warn = hasChanges
                ? 'You have unsaved changes. Loading this file will replace the editor contents. Continue?'
                : 'Load this file into the editor? You will need to Save Changes to store it.';
            if (!confirm(warn)) {
                this.value = '';
                return;
            }
            var file = this.files[0];
            var input = this;
            var reader = new FileReader();
            reader.onload = function(ev) {
                var parsed;
                try {
                    parsed = JSON.parse(ev.target.result);
                } catch (err) {
                    alert('Invalid JSON: ' + (err && err.message ? err.message : 'could not parse file'));
                    input.value = '';
                    return;
                }
                if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
                    alert('lessons.json must be a JSON object.');
                    input.value = '';
                    return;
                }
                if (!parsed.modules || !Array.isArray(parsed.modules)) {
                    alert('lessons.json must have a modules array.');
                    input.value = '';
                    return;
                }
                lessonsData = parsed;
                migrateFCPXToReference();
                var titleEl = document.querySelector('.lesson-author-header h1');
                if (titleEl) {
                    titleEl.textContent = lessonsData.title || 'Untitled';
                }
                renderModules();
                setupSortable();
                markChanged();
                input.value = '';
            };
            reader.onerror = function() {
                alert('Could not read that file.');
                input.value = '';
            };
            reader.readAsText(file);
        });
    }
    
    // Track changes
    $(document).on('input change', 'input, textarea, select', function() {
        markChanged();
    });
    
    // Event delegation for edit/delete buttons (so they work after reordering)
    $(document).on('click', '.edit-item-btn', function() {
        editItemFromButton(this);
    });
    
    $(document).on('click', '.delete-item-btn', function() {
        deleteItemFromButton(this);
    });

    $(document).on('change', 'input[name="edit-href-source"]', function() {
        updateWebLinkSourceRows();
    });

    $(document).on('click', '#edit-href-pick', function() {
        if (typeof tsugiOpenLinkPicker === 'function') {
            tsugiOpenLinkPicker(applyWebLinkPick);
        }
    });
    
    // Warn before leaving with unsaved changes
    $(window).on('beforeunload', function() {
        if (hasChanges) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
});

function markChanged() {
    if (!hasChanges) {
        hasChanges = true;
        $('#save-bar').removeClass('hidden');
    }
}

// Loading overlay functions
function showLoading() {
    $('#loading-overlay').addClass('active').attr('aria-busy', 'true');
    // Disable sortable during update (if they exist)
    try {
        if ($('#modules-container').hasClass('ui-sortable')) {
            $('#modules-container').sortable('disable');
        }
        $('.items-list').each(function() {
            if ($(this).hasClass('ui-sortable')) {
                $(this).sortable('disable');
            }
        });
    } catch (e) {
        // Sortable might not be initialized yet, ignore
    }
}

function hideLoading() {
    $('#loading-overlay').removeClass('active').attr('aria-busy', 'false');
    // Sortable will be re-initialized by setupSortable() after renderModules()
    // So we don't need to re-enable here
}

/**
 * Rebuilds the entire lessonsData structure from the current DOM state.
 * This is the single source of truth - DOM is authoritative during drag operations.
 */
function rebuildLessonsDataFromDOM() {
    const newModules = [];
    
    // Rebuild modules array from DOM order
    $('#modules-container .module-container').each(function(moduleIndex) {
        const $module = $(this);
        const oldModuleIndex = parseInt($module.attr('data-module-index'));
        
        // Get the module data from lessonsData (preserve all properties)
        const module = oldModuleIndex !== undefined && !isNaN(oldModuleIndex) && 
                      lessonsData.modules[oldModuleIndex] 
            ? JSON.parse(JSON.stringify(lessonsData.modules[oldModuleIndex]))
            : {
                title: 'Untitled Module',
                anchor: '',
                icon: '',
                description: '',
                items: []
            };
        
        // Update module index in DOM
        $module.attr('data-module-index', moduleIndex);
        
        // Rebuild items array from DOM order
        const items = [];
        const $itemsList = $module.find('.items-list');
        
        $itemsList.find('.item').each(function(itemIndex) {
            const $item = $(this);
            
            // Get item from stored item-object (most reliable)
            let item = $item.data('item-object');
            
            // If not found, try to get from current data attributes
            if (!item) {
                const itemModuleIndex = parseInt($item.attr('data-module-index'));
                const oldItemIndex = parseInt($item.attr('data-item-index'));
                
                if (itemModuleIndex !== undefined && !isNaN(itemModuleIndex) && 
                    oldItemIndex !== undefined && !isNaN(oldItemIndex) &&
                    lessonsData.modules[itemModuleIndex] &&
                    lessonsData.modules[itemModuleIndex].items &&
                    lessonsData.modules[itemModuleIndex].items[oldItemIndex]) {
                    item = JSON.parse(JSON.stringify(lessonsData.modules[itemModuleIndex].items[oldItemIndex]));
                }
            }
            
            // If still no item, create a default one (shouldn't happen, but safety)
            if (!item) {
                item = {
                    type: 'heading',
                    title: 'Untitled Item',
                    level: 2
                };
            }
            
            // Store item object on DOM element for future use
            $item.data('item-object', item);
            
            // Update data attributes
            $item.attr('data-module-index', moduleIndex);
            $item.attr('data-item-index', itemIndex);
            
            items.push(item);
        });
        
        module.items = items;
        newModules.push(module);
    });
    
    // Update lessonsData with rebuilt structure
    lessonsData.modules = newModules;
}

/**
 * Unified function to sync data from DOM and re-render.
 * This ensures data consistency - DOM is source of truth, then we rebuild data and re-render.
 */
function syncDataAndRender() {
    showLoading();
    
    // Use setTimeout to allow DOM to settle after drag operation
    setTimeout(function() {
        try {
            // Rebuild data structure from DOM (DOM is source of truth)
            rebuildLessonsDataFromDOM();
            
            // Re-render everything from the updated data
            renderModules();
            
            // Mark that changes were made
            markChanged();
        } catch (error) {
            console.error('Error syncing data and rendering:', error);
            alert('An error occurred while updating. Please refresh the page.');
        } finally {
            hideLoading();
        }
    }, 50); // Small delay to ensure DOM is settled
}

function renderModules() {
    const container = $('#modules-container');
    container.empty();
    
    if (!lessonsData.modules || !Array.isArray(lessonsData.modules)) {
        lessonsData.modules = [];
    }
    
    lessonsData.modules.forEach((module, moduleIndex) => {
        const moduleHtml = createModuleHtml(module, moduleIndex);
        container.append(moduleHtml);
        
        // Store item data on each item element for reliable retrieval
        const moduleContainer = $(`.module-container[data-module-index="${moduleIndex}"]`);
        const itemsList = moduleContainer.find('.items-list');
        itemsList.find('.item').each(function(itemIndex) {
            const item = module.items[itemIndex];
            if (item) {
                $(this).data('item-object', item);
            }
        });
        
        // Restore collapsed state from localStorage for modules
        const stateKey = `module_${moduleIndex}_collapsed`;
        if (localStorage.getItem(stateKey) === 'true') {
            const moduleBody = moduleContainer.find('.module-body');
            const toggleButton = moduleContainer.find('.expand-toggle');
            moduleBody.addClass('collapsed');
            toggleButton.addClass('collapsed');
        }
        
        // Apply header section collapsed states
        applyHeaderSectionStates(moduleIndex);
    });
    
    setupSortable();
}

/**
 * Applies collapsed states to header sections based on localStorage
 */
function applyHeaderSectionStates(moduleIndex) {
    const itemsList = $(`.items-list[data-module-index="${moduleIndex}"]`);
    const items = itemsList.find('.item').toArray();
    
    // First, remove all collapsed-section classes to start fresh
    // This prevents items from getting stuck in hidden state
    items.forEach((itemElement) => {
        $(itemElement).removeClass('collapsed-section');
    });
    
    // Then apply collapsed states
    items.forEach((itemElement, index) => {
        const $item = $(itemElement);
        const itemIndex = parseInt($item.attr('data-item-index'));
        const stateKey = `header_${moduleIndex}_${itemIndex}_collapsed`;
        const isCollapsed = localStorage.getItem(stateKey) === 'true';
        
        if (isCollapsed) {
            // Find all items after this header until the next header or end
            for (let i = index + 1; i < items.length; i++) {
                const $nextItem = $(items[i]);
                const nextItemData = $nextItem.data('item-object');
                
                // Stop at next header
                if (nextItemData && isHeadingItem(nextItemData)) {
                    break;
                }
                
                // Mark as collapsed section
                $nextItem.addClass('collapsed-section');
            }
        }
    });
}

function createModuleHtml(module, moduleIndex) {
    const itemsHtml = (module.items || []).map((item, itemIndex) => 
        createItemHtml(item, moduleIndex, itemIndex)
    ).join('');
    
    return `
        <div class="module-container" data-module-index="${moduleIndex}">
            <div class="module-header">
                <span class="drag-handle" title="Drag to reorder" aria-hidden="true"></span>
                <button type="button" class="expand-toggle" onclick="toggleModule(${moduleIndex})" title="Expand/Collapse" aria-label="Expand or collapse module">
                </button>
                <div style="flex: 1;">
                    <h3 class="module-title">
                        ${module.icon ? `<i class="fa ${escapeHtml(module.icon)} module-icon" aria-hidden="true"></i>` : ''}
                        <span>${escapeHtml(module.title || 'Untitled Module')}</span>
                    </h3>
                </div>
                <div class="module-actions">
                    <button type="button" class="btn btn-icon" onclick="editModule(${moduleIndex})" title="Edit Module" aria-label="Edit module">
                        <i class="fa fa-pencil" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="btn btn-icon" onclick="deleteModule(${moduleIndex})" title="Delete Module" aria-label="Delete module">
                        <i class="fa fa-trash" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="module-body">
                <button type="button" class="module-description module-description-edit${module.description ? '' : ' module-description-empty'}" onclick="editModule(${moduleIndex}, 'description')" title="Edit module description">
                    ${module.description ? escapeHtml(module.description) : 'Add a description (shown on the Lessons cards)'}
                </button>
                <div class="items-list" data-module-index="${moduleIndex}">
                    ${itemsHtml}
                </div>
                <div class="add-item-btn">
                    <button type="button" class="btn btn-icon" onclick="addItem(${moduleIndex})" title="Add Item" aria-label="Add item">
                        <i class="fa fa-plus" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
}

function getItemTypeIcon(itemOrType) {
    const kind = (typeof itemOrType === 'object' && itemOrType !== null)
        ? itemEditorKind(itemOrType)
        : itemOrType;
    const icons = {
        'heading': 'fa-header',
        'header': 'fa-header',
        'video': 'fa-play-circle',
        'reference': 'fa-external-link',
        'discussion': 'fa-comments',
        'lti': 'fa-puzzle-piece',
        'assignment': 'fa-file-text',
        'slide': 'fa-file-powerpoint-o',
        'web_link': 'fa-external-link',
        'html_page': 'fa-file-text-o',
        'file': 'fa-file-o'
    };
    return icons[kind] || 'fa-circle';
}

function createItemHtml(item, moduleIndex, itemIndex) {
    const type = itemEditorKind(item);
    const title = getItemTitle(item);
    const isHeader = isHeadingItem(item);
    
    // Check if this header section is collapsed
    const stateKey = `header_${moduleIndex}_${itemIndex}_collapsed`;
    const isCollapsed = localStorage.getItem(stateKey) === 'true';
    
    return `
        <div class="item ${isHeader ? 'header-item' : ''}" data-module-index="${moduleIndex}" data-item-index="${itemIndex}">
            <span class="drag-handle" title="Drag to reorder"></span>
            <div class="item-content">
                <div class="item-header">
                        ${isHeader ? `<button type="button" class="header-toggle ${isCollapsed ? 'collapsed' : ''}" onclick="toggleHeaderSection(${moduleIndex}, ${itemIndex}, event)" title="Expand/Collapse section" aria-label="Expand or collapse section"></button>` : ''}
                    ${!isHeader ? `<span class="item-type ${type}" aria-label="${type}" title="${type}"><i class="fa ${getItemTypeIcon(item)}" aria-hidden="true"></i></span>` : ''}
                    <span class="item-title">${escapeHtml(title)}</span>
                    <div class="item-actions">
                        ${isHeader ? `<button type="button" class="btn btn-icon add-item-after-btn" onclick="addItemAfter(${moduleIndex}, ${itemIndex})" title="Add item after this header" aria-label="Add item after this header">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </button>` : ''}
                        <button type="button" class="btn btn-icon edit-item-btn" title="Edit Item" aria-label="Edit item">
                            <i class="fa fa-pencil" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="btn btn-icon delete-item-btn" title="Delete Item" aria-label="Delete item">
                            <i class="fa fa-trash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function getItemTitle(item) {
    if (item.title) return item.title;
    if (isHeadingItem(item)) return item.text || 'Heading';
    if (item.href) return item.href;
    if (item.filename) return item.filename;
    return 'Untitled Item';
}

function rebuildModuleItemsArray($itemsList) {
    // Rebuild a module's items array from DOM order
    const moduleIndex = $itemsList.data('module-index');
    if (moduleIndex === undefined || isNaN(moduleIndex)) return;
    
    const items = [];
    
    // Collect all items in their current DOM order
    $itemsList.find('.item').each(function(index) {
        const $item = $(this);
        
        // Check if this item was just moved from another module (has stored item data)
        const movedItem = $item.data('moved-item');
        if (movedItem !== undefined) {
            // Use the stored item object
            items.push(movedItem);
            // Store it as item-object for future use
            $item.data('item-object', movedItem);
            // Clear the moved-item data since we've used it
            $item.removeData('moved-item');
        } else {
            // Try to get item from stored item-object first (most reliable)
            const storedItem = $item.data('item-object');
            if (storedItem !== undefined) {
                items.push(storedItem);
            } else {
                // Fallback: look up item by its current module and index
                const itemModuleIndex = parseInt($item.attr('data-module-index'));
                const oldItemIndex = parseInt($item.attr('data-item-index'));
                
                // Get the actual item object from the correct module
                if (itemModuleIndex !== undefined && !isNaN(itemModuleIndex) && 
                    oldItemIndex !== undefined && !isNaN(oldItemIndex) &&
                    lessonsData.modules[itemModuleIndex] &&
                    lessonsData.modules[itemModuleIndex].items &&
                    lessonsData.modules[itemModuleIndex].items[oldItemIndex] !== undefined) {
                    const item = lessonsData.modules[itemModuleIndex].items[oldItemIndex];
                    items.push(item);
                    // Store it for future use
                    $item.data('item-object', item);
                }
            }
        }
        
        // Update data attributes to reflect new position
        $item.attr('data-module-index', moduleIndex);
        $item.attr('data-item-index', index);
    });
    
    // Update the module's items array
    lessonsData.modules[moduleIndex].items = items;
    markChanged();
}

function setupSortable() {
    // Destroy any existing sortable instances first (in case of re-render)
    if ($('#modules-container').hasClass('ui-sortable')) {
        $('#modules-container').sortable('destroy');
    }
    $('.items-list').each(function() {
        if ($(this).hasClass('ui-sortable')) {
            $(this).sortable('destroy');
        }
    });
    
    // Make modules sortable
    $('#modules-container').sortable({
        handle: '.module-header .drag-handle',
        placeholder: 'ui-sortable-placeholder',
        tolerance: 'pointer',
        update: function(event, ui) {
            // On any module reorder, sync data and re-render
            syncDataAndRender();
        }
    });
    
    // Make items within each module sortable
    $('.items-list').sortable({
        items: '> .item',
        handle: '.drag-handle',
        placeholder: 'ui-sortable-placeholder',
        tolerance: 'pointer',
        connectWith: '.items-list',
        receive: function(event, ui) {
            // When an item is received (dropped) into a new list, check if it's in a collapsed section
            const $droppedItem = ui.item;
            const $itemsList = $(this);
            const moduleIndex = parseInt($itemsList.attr('data-module-index'));
            
            // Find the header that this item is now under
            const allItems = $itemsList.find('.item').toArray();
            const droppedIndex = allItems.indexOf($droppedItem[0]);
            
            // Look backwards to find the nearest header
            for (let i = droppedIndex - 1; i >= 0; i--) {
                const $prevItem = $(allItems[i]);
                const prevItemData = $prevItem.data('item-object');
                
                if (prevItemData && isHeadingItem(prevItemData)) {
                    const prevItemIndex = parseInt($prevItem.attr('data-item-index'));
                    const stateKey = `header_${moduleIndex}_${prevItemIndex}_collapsed`;
                    const isCollapsed = localStorage.getItem(stateKey) === 'true';
                    
                    // If the header is collapsed, expand it automatically
                    if (isCollapsed) {
                        localStorage.removeItem(stateKey);
                        // The syncDataAndRender will handle re-rendering with expanded state
                    }
                    break;
                }
            }
        },
        update: function(event, ui) {
            // On any item reorder (within module or between modules), sync data and re-render
            // This handles both same-module moves and cross-module moves
            syncDataAndRender();
        }
    });
}

function updateModuleField(moduleIndex, field, value) {
    if (!lessonsData.modules[moduleIndex]) return;
    lessonsData.modules[moduleIndex][field] = value;
    markChanged();
}

function addModule() {
    const newModule = {
        title: 'New Module',
        anchor: '',
        icon: '',
        description: '',
        items: []
    };
    lessonsData.modules.push(newModule);
    renderModules();
    markChanged();
    editModule(lessonsData.modules.length - 1);
}

function editModule(moduleIndex, focusField) {
    editingModuleIndex = moduleIndex;
    const module = lessonsData.modules[moduleIndex];
    
    const formHtml = `
        <div class="form-group">
            <label>Title:</label>
            <input type="text" id="edit-module-title" value="${escapeHtml(module.title || '')}">
        </div>
        <div class="form-group">
            <label>Anchor:</label>
            <input type="text" id="edit-module-anchor" value="${escapeHtml(module.anchor || '')}">
        </div>
        <div class="form-group">
            <label>Icon:</label>
            <input type="text" id="edit-module-icon" value="${escapeHtml(module.icon || '')}" 
                   placeholder="e.g., fa-smile-o">
        </div>
        <div class="form-group">
            <label>Description:</label>
            <textarea id="edit-module-description" rows="6" placeholder="Shown on the Lessons card view">${escapeHtml(module.description || '')}</textarea>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-primary" onclick="saveModule()">Save</button>
            <button type="button" class="btn" onclick="closeModal()">Cancel</button>
        </div>
    `;
    
    $('#modal-title').text('Edit Module');
    $('#modal-body').html(formHtml);
    $('#item-modal').show();
    if (focusField === 'description') {
        const desc = document.getElementById('edit-module-description');
        if (desc) {
            desc.focus();
        }
    }
}

function saveModule() {
    if (editingModuleIndex === null) return;
    
    const desc = $('#edit-module-description').val().trim();
    lessonsData.modules[editingModuleIndex].title = $('#edit-module-title').val().trim();
    lessonsData.modules[editingModuleIndex].anchor = $('#edit-module-anchor').val().trim();
    lessonsData.modules[editingModuleIndex].icon = $('#edit-module-icon').val().trim();
    if (desc) {
        lessonsData.modules[editingModuleIndex].description = desc;
    } else {
        delete lessonsData.modules[editingModuleIndex].description;
    }
    
    closeModal();
    renderModules();
    markChanged();
}

function deleteModule(moduleIndex) {
    if (!confirm('Are you sure you want to delete this module?')) return;
    
    lessonsData.modules.splice(moduleIndex, 1);
    renderModules();
    markChanged();
}

function addItem(moduleIndex) {
    editingItemIndex = null;
    editingModuleIndex = moduleIndex;
    showItemModal('Add Item', getDefaultItem());
}

function addItemAfter(moduleIndex, afterItemIndex) {
    editingItemIndex = null;
    editingModuleIndex = moduleIndex;
    editingAfterItemIndex = afterItemIndex;
    showItemModal('Add Item', getDefaultItem());
}

function editItem(moduleIndex, itemIndex) {
    // If called with parameters (for backwards compatibility)
    if (moduleIndex !== undefined && itemIndex !== undefined) {
        editingModuleIndex = moduleIndex;
        editingItemIndex = itemIndex;
        const item = lessonsData.modules[moduleIndex].items[itemIndex];
        showItemModal('Edit Item', item);
    }
}

function editItemFromButton(button) {
    // Read indices from the item's data attributes
    const $item = $(button).closest('.item');
    const moduleIndex = parseInt($item.attr('data-module-index'));
    const itemIndex = parseInt($item.attr('data-item-index'));
    
    if (!isNaN(moduleIndex) && !isNaN(itemIndex)) {
        editingModuleIndex = moduleIndex;
        editingItemIndex = itemIndex;
        const item = lessonsData.modules[moduleIndex].items[itemIndex];
        // Migrate FCPX to reference if needed
        if ((item.type === 'video' || item.subtype === 'video') && item.FCPX && !item.reference) {
            item.reference = item.FCPX;
            delete item.FCPX;
            markChanged();
        }
        showItemModal('Edit Item', item);
    }
}

function getDefaultItem() {
    return {
        type: 'heading',
        title: '',
        level: 2
    };
}

function showItemModal(title, item) {
    const type = itemEditorKind(item);
    
    let formHtml = `
        <div class="form-group">
            <label>Type:</label>
            <select id="edit-item-type" onchange="updateItemForm()">
                <option value="heading" ${type === 'heading' ? 'selected' : ''}>Heading</option>
                <option value="video" ${type === 'video' ? 'selected' : ''}>Video</option>
                <option value="reference" ${type === 'reference' ? 'selected' : ''}>Web link</option>
                <option value="discussion" ${type === 'discussion' ? 'selected' : ''}>Discussion</option>
                <option value="lti" ${type === 'lti' ? 'selected' : ''}>LTI</option>
                <option value="assignment" ${type === 'assignment' ? 'selected' : ''}>Assignment</option>
                <option value="slide" ${type === 'slide' ? 'selected' : ''}>Slides</option>
                <option value="file" ${type === 'file' ? 'selected' : ''}>File</option>
            </select>
        </div>
        <div id="item-form-fields"></div>
        <div class="form-actions">
            <button type="button" class="btn btn-primary" onclick="saveItem()">Save</button>
            <button type="button" class="btn" onclick="closeModal()">Cancel</button>
        </div>
    `;
    
    $('#modal-title').text(title);
    $('#modal-body').html(formHtml);
    updateItemFormFields(item);
    $('#item-modal').show();
}

function updateItemForm() {
    const type = $('#edit-item-type').val();
    const currentItem = editingItemIndex !== null 
        ? lessonsData.modules[editingModuleIndex].items[editingItemIndex]
        : getDefaultItem();
    
    currentItem.type = type;
    updateItemFormFields(currentItem);
}

function updateItemFormFields(item) {
    const type = itemEditorKind(item);
    let fieldsHtml = '';
    
    if (type === 'heading' || type === 'header') {
        fieldsHtml = `
            <div class="form-group">
                <label>Title:</label>
                <input type="text" id="edit-title" value="${escapeHtml(item.title || item.text || '')}">
            </div>
            <div class="form-group">
                <label>Level:</label>
                <select id="edit-level">
                    <option value="1" ${item.level == 1 ? 'selected' : ''}>Level 1</option>
                    <option value="2" ${item.level == 2 ? 'selected' : ''}>Level 2</option>
                    <option value="3" ${item.level == 3 ? 'selected' : ''}>Level 3</option>
                </select>
            </div>
        `;
    } else if (type === 'video') {
        const referenceValue = item.reference || item.FCPX || '';
        fieldsHtml = `
            <div class="form-group">
                <label>Title:</label>
                <input type="text" id="edit-title" value="${escapeHtml(item.title || '')}">
            </div>
            <div class="form-group">
                <label>URL:</label>
                <input type="text" id="edit-href" value="${escapeHtml(item.href || '')}">
            </div>
            <div class="form-group">
                <label>YouTube ID:</label>
                <input type="text" id="edit-youtube" value="${escapeHtml(item.youtube || '')}">
            </div>
            <div class="form-group">
                <label>Kaltura ID:</label>
                <input type="text" id="edit-kaltura" value="${escapeHtml(item.kaltura_id || '')}">
            </div>
            <div class="form-group">
                <label>Media:</label>
                <input type="text" id="edit-media" value="${escapeHtml(item.media || '')}">
            </div>
            <div class="form-group">
                <label>Reference:</label>
                <input type="text" id="edit-reference" value="${escapeHtml(referenceValue)}">
            </div>
        `;
    } else if (type === 'reference') {
        const samePage = item.target === '_self';
        const hrefVal = item.href || '';
        fieldsHtml = `
            <div class="form-group">
                <label>Title:</label>
                <input type="text" id="edit-title" value="${escapeHtml(item.title || '')}">
            </div>
            <div class="form-group">
                <label>Link:</label>
                <div class="form-group-radios">
                    <label><input type="radio" name="edit-href-source" value="url" checked> URL</label>
                    <label><input type="radio" name="edit-href-source" value="course"> Course content</label>
                </div>
            </div>
            <div class="form-group" id="edit-href-url-row">
                <input type="text" id="edit-href" value="${escapeHtml(hrefVal)}" placeholder="https://">
            </div>
            <div class="form-group" id="edit-href-course-row" style="display: none;">
                <button type="button" class="btn" id="edit-href-pick">Choose from course…</button>
                <div id="edit-href-chosen" class="file-picker-summary">${hrefVal ? escapeHtml(hrefVal) : 'Pages, lessons, discussions, or files.'}</div>
            </div>
            <div class="form-group">
                <label>Open:</label>
                <div class="form-group-radios">
                    <label><input type="radio" name="edit-target" value="_self" ${samePage ? 'checked' : ''}> Same page</label>
                    <label><input type="radio" name="edit-target" value="_blank" ${samePage ? '' : 'checked'}> New page</label>
                </div>
            </div>
        `;
    } else if (type === 'discussion') {
        fieldsHtml = `
            <div class="form-group">
                <label>Title:</label>
                <input type="text" id="edit-title" value="${escapeHtml(item.title || '')}">
            </div>
            <div class="form-group">
                <label>Description:</label>
                <textarea id="edit-description" rows="3">${escapeHtml(item.description || '')}</textarea>
            </div>
            <div class="form-group">
                <label>Resource Link ID:</label>
                <input type="text" id="edit-resource-link-id" value="${escapeHtml(item.resource_link_id || '')}" placeholder="Leave blank to generate from the title">
            </div>
        `;
    } else if (type === 'lti') {
        const customFields = item.custom || [];
        const customFieldsHtml = customFields.map((field, index) => `
            <div class="custom-field">
                <input type="text" placeholder="Key" value="${escapeHtml(field.key || '')}" 
                       class="custom-key" data-index="${index}">
                <input type="text" placeholder="Value" value="${escapeHtml(field.value || '')}" 
                       class="custom-value" data-index="${index}">
                <button type="button" class="btn btn-danger" onclick="removeCustomField(${index})">Remove</button>
            </div>
        `).join('');
        
        fieldsHtml = `
            <div class="form-group">
                <label>Title:</label>
                <input type="text" id="edit-title" value="${escapeHtml(item.title || '')}">
            </div>
            <div class="form-group">
                <label>Launch:</label>
                <input type="text" id="edit-launch" value="${escapeHtml(item.launch || '')}">
            </div>
            <div class="form-group">
                <label>Resource Link ID:</label>
                <input type="text" id="edit-resource-link-id" value="${escapeHtml(item.resource_link_id || '')}">
            </div>
            <div class="form-group">
                <label>Target:</label>
                <select id="edit-target">
                    <option value="">Default (same window)</option>
                    <option value="_blank" ${item.target === '_blank' ? 'selected' : ''}>New Window (_blank)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Custom Parameters:</label>
                <div id="custom-fields-container">
                    ${customFieldsHtml}
                </div>
                <button type="button" class="btn btn-primary" onclick="addCustomField()">+ Add Custom Field</button>
            </div>
        `;
    } else if (type === 'assignment') {
        fieldsHtml = `
            <div class="form-group">
                <label>Title:</label>
                <input type="text" id="edit-title" value="${escapeHtml(item.title || '')}">
            </div>
            <div class="form-group">
                <label>URL:</label>
                <input type="text" id="edit-href" value="${escapeHtml(item.href || '')}">
            </div>
        `;
    } else if (type === 'slide') {
        fieldsHtml = `
            <div class="form-group">
                <label>Title:</label>
                <input type="text" id="edit-title" value="${escapeHtml(item.title || '')}">
            </div>
            <div class="form-group">
                <label>URL:</label>
                <input type="text" id="edit-href" value="${escapeHtml(item.href || '')}">
            </div>
        `;
    } else if (type === 'file') {
        const subtypeVal = item.subtype || '';
        filePickerState = {
            sha256: item.sha256 || '',
            filename: item.filename || '',
            href: item.href || '',
            content_type: item.content_type || '',
            path: item.filename || item.title || ''
        };
        fieldsHtml = `
            <div class="form-group">
                <label>Title:</label>
                <input type="text" id="edit-title" value="${escapeHtml(item.title || '')}">
            </div>
            <div class="form-group">
                <label>File:</label>
                <select id="edit-file-picker">
                    <option value="">Loading files…</option>
                </select>
                <div id="file-picker-summary" class="file-picker-summary"></div>
            </div>
            <div class="form-group">
                <label>Subtype:</label>
                <select id="edit-subtype">
                    <option value="" ${subtypeVal === '' ? 'selected' : ''}>None</option>
                    <option value="slides" ${subtypeVal === 'slides' ? 'selected' : ''}>Slides</option>
                </select>
            </div>
        `;
    }
    
    $('#item-form-fields').html(fieldsHtml);
    if (type === 'file') {
        updateFilePickerSummary();
        populateFilePicker(item);
    }
    if (type === 'reference') {
        updateWebLinkSourceRows();
    }
}

function loadCourseFiles() {
    if (courseFilesCache) {
        return Promise.resolve(courseFilesCache);
    }
    if (!filesJsonUrl) {
        return Promise.resolve([]);
    }
    return fetch(filesJsonUrl, { credentials: 'same-origin' })
        .then(function(resp) {
            if (!resp.ok) {
                throw new Error('Could not load course files');
            }
            return resp.json();
        })
        .then(function(data) {
            courseFilesCache = Array.isArray(data) ? data : [];
            return courseFilesCache;
        });
}

function applyPickedFile(file) {
    const prevFilename = filePickerState.filename || '';
    const titleEl = document.getElementById('edit-title');
    const currentTitle = titleEl ? titleEl.value.trim() : '';
    filePickerState = {
        sha256: file.sha256 || file.id || '',
        filename: file.filename || file.title || '',
        href: file.href || '',
        content_type: file.content_type || '',
        path: file.path || file.filename || file.title || ''
    };
    if (titleEl && (!currentTitle || currentTitle === prevFilename)) {
        titleEl.value = filePickerState.filename;
    }
    updateFilePickerSummary();
}

function updateFilePickerSummary() {
    const el = document.getElementById('file-picker-summary');
    if (!el) {
        return;
    }
    if (!filePickerState.sha256) {
        el.textContent = 'Choose a file to fill filename, content type, and download link.';
        return;
    }
    const bits = [];
    if (filePickerState.path) {
        bits.push(filePickerState.path);
    } else if (filePickerState.filename) {
        bits.push(filePickerState.filename);
    }
    if (filePickerState.content_type) {
        bits.push(filePickerState.content_type);
    }
    el.textContent = bits.join(' · ');
}

function populateFilePicker(item) {
    const select = document.getElementById('edit-file-picker');
    if (!select) {
        return;
    }
    loadCourseFiles().then(function(files) {
        const selectedSha = (item && item.sha256) ? String(item.sha256).toLowerCase() : '';
        select.innerHTML = '';
        if (!files.length) {
            const empty = document.createElement('option');
            empty.value = '';
            empty.textContent = 'No files in this course';
            select.appendChild(empty);
            const hint = document.getElementById('file-picker-summary');
            if (hint) {
                hint.innerHTML = filesHomeUrl
                    ? 'Upload a file in <a href="' + escapeHtml(filesHomeUrl) + '" target="_blank" rel="noopener">Files</a> first.'
                    : 'No files are available to pick.';
            }
            return;
        }
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select a file…';
        select.appendChild(placeholder);

        const groups = {};
        files.forEach(function(file) {
            const folder = file.folder || '';
            if (!groups[folder]) {
                groups[folder] = [];
            }
            groups[folder].push(file);
        });
        Object.keys(groups).sort(function(a, b) {
            return a.localeCompare(b);
        }).forEach(function(folder) {
            const og = document.createElement('optgroup');
            og.label = folder === '' ? 'Course files' : folder;
            groups[folder].forEach(function(file) {
                const opt = document.createElement('option');
                opt.value = file.sha256 || file.id || '';
                opt.textContent = file.filename || file.title || opt.value;
                if (opt.value && opt.value.toLowerCase() === selectedSha) {
                    opt.selected = true;
                    applyPickedFile(file);
                }
                og.appendChild(opt);
            });
            select.appendChild(og);
        });

        if (selectedSha && !select.value) {
            const missing = document.createElement('option');
            missing.value = selectedSha;
            missing.textContent = (item.filename || 'Current file') + ' (not in Files)';
            missing.selected = true;
            select.insertBefore(missing, select.options[1] || null);
        }

        select.onchange = function() {
            const sha = select.value;
            const file = files.find(function(f) {
                return (f.sha256 || f.id) === sha;
            });
            if (file) {
                applyPickedFile(file);
            }
        };
        updateFilePickerSummary();
    }).catch(function() {
        select.innerHTML = '';
        const err = document.createElement('option');
        err.value = '';
        err.textContent = 'Could not load files';
        select.appendChild(err);
        const hint = document.getElementById('file-picker-summary');
        if (hint) {
            hint.textContent = 'Could not load the course Files list.';
        }
    });
}

function updateWebLinkSourceRows() {
    const source = $('input[name="edit-href-source"]:checked').val();
    if (source === 'course') {
        $('#edit-href-url-row').hide();
        $('#edit-href-course-row').show();
    } else {
        $('#edit-href-url-row').show();
        $('#edit-href-course-row').hide();
    }
}

function applyWebLinkPick(picked) {
    if (!picked) {
        return;
    }
    const url = picked.url || '';
    $('#edit-href').val(url);
    const titleEl = document.getElementById('edit-title');
    if (titleEl && !titleEl.value.trim() && picked.title) {
        titleEl.value = picked.title;
    }
    const chosen = document.getElementById('edit-href-chosen');
    if (chosen) {
        chosen.textContent = picked.title ? (picked.title + ' — ' + url) : url;
    }
    if (picked.targetBlank) {
        $('input[name="edit-target"][value="_blank"]').prop('checked', true);
    } else {
        $('input[name="edit-target"][value="_self"]').prop('checked', true);
    }
    $('input[name="edit-href-source"][value="course"]').prop('checked', true);
    updateWebLinkSourceRows();
}

function addCustomField() {
    const container = $('#custom-fields-container');
    const index = container.find('.custom-field').length;
    const fieldHtml = `
        <div class="custom-field">
            <input type="text" placeholder="Key" class="custom-key" data-index="${index}">
            <input type="text" placeholder="Value" class="custom-value" data-index="${index}">
            <button type="button" class="btn btn-danger" onclick="removeCustomField(${index})">Remove</button>
        </div>
    `;
    container.append(fieldHtml);
}

function removeCustomField(index) {
    $('#custom-fields-container .custom-field').eq(index).remove();
    // Reindex remaining fields
    $('#custom-fields-container .custom-field').each(function(i) {
        $(this).find('.custom-key, .custom-value').attr('data-index', i);
        $(this).find('button').attr('onclick', `removeCustomField(${i})`);
    });
}

function saveItem() {
    const type = $('#edit-item-type').val();
    let item = {};
    if (editingItemIndex !== null &&
        lessonsData.modules[editingModuleIndex] &&
        lessonsData.modules[editingModuleIndex].items &&
        lessonsData.modules[editingModuleIndex].items[editingItemIndex]) {
        item = Object.assign({}, lessonsData.modules[editingModuleIndex].items[editingItemIndex]);
    }
    
    if (type === 'heading' || type === 'header') {
        item.type = 'heading';
        delete item.subtype;
        item.title = $('#edit-title').val().trim();
        item.level = parseInt($('#edit-level').val());
        delete item.text;
    } else if (type === 'video') {
        item.type = 'web_link';
        item.subtype = 'video';
        item.title = $('#edit-title').val().trim();
        const hrefVal = $('#edit-href').val().trim();
        const youtubeVal = $('#edit-youtube').val().trim();
        const mediaVal = $('#edit-media').val().trim();
        const referenceVal = $('#edit-reference').val().trim();
        const kalturaVal = $('#edit-kaltura').val().trim();
        if (hrefVal) { item.href = hrefVal; } else { delete item.href; }
        if (youtubeVal) { item.youtube = youtubeVal; } else { delete item.youtube; }
        if (mediaVal) { item.media = mediaVal; } else { delete item.media; }
        if (referenceVal) { item.reference = referenceVal; } else { delete item.reference; }
        if (kalturaVal) { item.kaltura_id = kalturaVal; } else { delete item.kaltura_id; }
        delete item.FCPX;
    } else if (type === 'reference') {
        item.type = 'web_link';
        item.subtype = 'reference';
        item.title = $('#edit-title').val().trim();
        item.href = $('#edit-href').val().trim();
        const targetVal = $('input[name="edit-target"]:checked').val();
        item.target = targetVal === '_self' ? '_self' : '_blank';
    } else if (type === 'discussion') {
        item.type = 'discussion';
        delete item.subtype;
        delete item.launch;
        item.title = $('#edit-title').val().trim();
        const descriptionVal = $('#edit-description').val().trim();
        if (descriptionVal) {
            item.description = descriptionVal;
        } else {
            delete item.description;
        }
        let rlid = $('#edit-resource-link-id').val().trim();
        if (!rlid) {
            rlid = allocateDiscussionRlid(item.title, collectUsedResourceLinkIds(editingModuleIndex, editingItemIndex));
        }
        item.resource_link_id = rlid;
    } else if (type === 'lti') {
        const custom = [];
        $('#custom-fields-container .custom-field').each(function() {
            const key = $(this).find('.custom-key').val().trim();
            const value = $(this).find('.custom-value').val().trim();
            if (key && value) {
                custom.push({ key: key, value: value });
            }
        });
        item.type = 'lti';
        if (item.subtype === 'discussion') {
            delete item.subtype;
        }
        item.title = $('#edit-title').val().trim();
        item.launch = $('#edit-launch').val().trim();
        item.resource_link_id = $('#edit-resource-link-id').val().trim();
        const targetVal = $('#edit-target').val().trim();
        if (targetVal) {
            item.target = targetVal;
        } else {
            delete item.target;
        }
        if (custom.length > 0) {
            item.custom = custom;
        } else {
            delete item.custom;
        }
    } else if (type === 'assignment') {
        item.type = 'web_link';
        item.subtype = 'assignment';
        item.title = $('#edit-title').val().trim();
        item.href = $('#edit-href').val().trim();
    } else if (type === 'slide') {
        item.type = 'web_link';
        item.subtype = 'slides';
        item.title = $('#edit-title').val().trim();
        item.href = $('#edit-href').val().trim();
        delete item.filename;
        delete item.sha256;
    } else if (type === 'file') {
        if (!filePickerState.sha256 || !filePickerState.href) {
            alert('Pick a file from the course Files list.');
            return;
        }
        const subtypeVal = $('#edit-subtype').length ? $('#edit-subtype').val().trim() : '';
        item.type = 'file';
        if (subtypeVal) {
            item.subtype = subtypeVal;
        } else {
            delete item.subtype;
        }
        item.title = $('#edit-title').val().trim() || filePickerState.filename;
        item.href = filePickerState.href;
        item.sha256 = filePickerState.sha256;
        item.filename = filePickerState.filename;
        if (filePickerState.content_type) {
            item.content_type = filePickerState.content_type;
        } else {
            delete item.content_type;
        }
    }
    
    if (editingItemIndex !== null) {
        lessonsData.modules[editingModuleIndex].items[editingItemIndex] = item;
    } else {
        if (!lessonsData.modules[editingModuleIndex].items) {
            lessonsData.modules[editingModuleIndex].items = [];
        }
        // If editingAfterItemIndex is set, insert after that item, otherwise append to end
        if (editingAfterItemIndex !== null) {
            lessonsData.modules[editingModuleIndex].items.splice(editingAfterItemIndex + 1, 0, item);
        } else {
            lessonsData.modules[editingModuleIndex].items.push(item);
        }
    }
    
    closeModal();
    renderModules();
    markChanged();
}

function deleteItem(moduleIndex, itemIndex) {
    // If called with parameters (for backwards compatibility)
    if (moduleIndex !== undefined && itemIndex !== undefined) {
        if (!confirm('Are you sure you want to delete this item?')) return;
        
        lessonsData.modules[moduleIndex].items.splice(itemIndex, 1);
        renderModules();
        markChanged();
    }
}

function deleteItemFromButton(button) {
    // Read indices from the item's data attributes
    const $item = $(button).closest('.item');
    const moduleIndex = parseInt($item.attr('data-module-index'));
    const itemIndex = parseInt($item.attr('data-item-index'));
    
    if (!isNaN(moduleIndex) && !isNaN(itemIndex)) {
        if (!confirm('Are you sure you want to delete this item?')) return;
        
        lessonsData.modules[moduleIndex].items.splice(itemIndex, 1);
        renderModules();
        markChanged();
    }
}

function toggleModule(moduleIndex) {
    const moduleContainer = $(`.module-container[data-module-index="${moduleIndex}"]`);
    const moduleBody = moduleContainer.find('.module-body');
    const toggleButton = moduleContainer.find('.expand-toggle');
    
    moduleBody.toggleClass('collapsed');
    toggleButton.toggleClass('collapsed');
    
    // Store state in localStorage
    const stateKey = `module_${moduleIndex}_collapsed`;
    if (moduleBody.hasClass('collapsed')) {
        localStorage.setItem(stateKey, 'true');
    } else {
        localStorage.removeItem(stateKey);
    }
}

function toggleHeaderSection(moduleIndex, itemIndex, event) {
    // Prevent event from interfering with drag operations
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    
    const itemsList = $(`.items-list[data-module-index="${moduleIndex}"]`);
    const $headerItem = itemsList.find(`.item[data-item-index="${itemIndex}"]`);
    const $toggleButton = $headerItem.find('.header-toggle');
    
    // Toggle collapsed state
    const isCollapsed = $toggleButton.hasClass('collapsed');
    const newCollapsedState = !isCollapsed;
    
    // Update toggle button
    $toggleButton.toggleClass('collapsed');
    
    // Find all items after this header until the next header or end
    const allItems = itemsList.find('.item').toArray();
    const headerIndex = allItems.indexOf($headerItem[0]);
    
    if (headerIndex === -1) return;
    
    // Show or hide items between this header and the next header
    for (let i = headerIndex + 1; i < allItems.length; i++) {
        const $nextItem = $(allItems[i]);
        const nextItemData = $nextItem.data('item-object');
        
        // Stop at next header
        if (nextItemData && isHeadingItem(nextItemData)) {
            break;
        }
        
        // Toggle visibility
        if (newCollapsedState) {
            $nextItem.addClass('collapsed-section');
        } else {
            $nextItem.removeClass('collapsed-section');
        }
    }
    
    // Store state in localStorage
    const stateKey = `header_${moduleIndex}_${itemIndex}_collapsed`;
    if (newCollapsedState) {
        localStorage.setItem(stateKey, 'true');
    } else {
        localStorage.removeItem(stateKey);
    }
}

function closeModal() {
    $('#item-modal').hide();
    editingItemIndex = null;
    editingModuleIndex = null;
    editingAfterItemIndex = null;
}

function saveChanges() {
    const jsonData = JSON.stringify(lessonsData, null, 4);
    
    $.ajax({
        url: window.location.pathname,
        method: 'POST',
        headers: tsugiCsrfHeaders(),
        data: {
            action: 'save',
            data: jsonData
        },
        success: function(response) {
            const result = typeof response === 'string' ? JSON.parse(response) : response;
            if (result.success) {
                hasChanges = false;
                $('#save-bar').addClass('hidden');
                alert('Changes saved successfully!');
            } else {
                alert('Error saving: ' + (result.error || 'Unknown error'));
            }
        },
        error: function() {
            alert('Error saving changes. Please try again.');
        }
    });
}

function discardChanges() {
    if (!confirm('Are you sure you want to discard all unsaved changes?')) return;
    
    location.reload();
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('click', function(event) {
    const modal = document.getElementById('item-modal');
    if (modal && event.target === modal) {
        closeModal();
    }
});
</script>

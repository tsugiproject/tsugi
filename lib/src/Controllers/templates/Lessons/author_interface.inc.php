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
    padding: 10px 0;
    color: #666;
    font-size: 14px;
    line-height: 1.5;
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
</style>

<div class="lesson-author">
    <div class="lesson-author-header">
        <h1><?= $lessons_title ?></h1>
        <div class="info">
            <?= $lessons_file_escaped ?>
        </div>
    </div>

    <div id="modules-container">
        <!-- Modules will be rendered here -->
    </div>

    <div class="add-module-btn">
        <button class="btn btn-icon" onclick="addModule()" title="Add Module">
            <i class="fa fa-plus"></i>
        </button>
    </div>
</div>

<div id="save-bar" class="save-bar hidden">
    <div class="message">You have unsaved changes</div>
    <div class="actions">
        <button class="btn btn-success" onclick="saveChanges()">Save Changes</button>
        <button class="btn" onclick="discardChanges()">Discard</button>
    </div>
</div>

<!-- Modal for editing items -->
<div id="item-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title">Edit Item</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div id="modal-body">
            <!-- Form will be inserted here -->
        </div>
    </div>
</div>

<!-- Loading overlay for drag-and-drop updates -->
<div id="loading-overlay" class="loading-overlay">
    <div class="loading-spinner"></div>
    <div>Updating structure...</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
let lessonsData = <?= $lessons_json ?>;
let hasChanges = false;
let editingItemIndex = null;
let editingModuleIndex = null;
let editingAfterItemIndex = null;

// Migrate FCPX to reference for video items
function migrateFCPXToReference() {
    if (!lessonsData.modules || !Array.isArray(lessonsData.modules)) {
        return;
    }
    
    lessonsData.modules.forEach(module => {
        if (module.items && Array.isArray(module.items)) {
            module.items.forEach(item => {
                if (item.type === 'video' && item.FCPX && !item.reference) {
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
    $('#loading-overlay').addClass('active');
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
    $('#loading-overlay').removeClass('active');
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
                    type: 'header',
                    text: 'Untitled Item',
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
                if (nextItemData && nextItemData.type === 'header') {
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
                <span class="drag-handle" title="Drag to reorder"></span>
                <button class="expand-toggle" onclick="toggleModule(${moduleIndex})" title="Expand/Collapse">
                </button>
                <div style="flex: 1;">
                    <h3 class="module-title">
                        ${module.icon ? `<i class="fa ${escapeHtml(module.icon)} module-icon"></i>` : ''}
                        <span>${escapeHtml(module.title || 'Untitled Module')}</span>
                    </h3>
                </div>
                <div class="module-actions">
                    <button class="btn btn-icon" onclick="editModule(${moduleIndex})" title="Edit Module">
                        <i class="fa fa-pencil"></i>
                    </button>
                    <button class="btn btn-icon" onclick="deleteModule(${moduleIndex})" title="Delete Module">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="module-body">
                ${module.description ? `<div class="module-description">${escapeHtml(module.description)}</div>` : ''}
                <div class="items-list" data-module-index="${moduleIndex}">
                    ${itemsHtml}
                </div>
                <div class="add-item-btn">
                    <button class="btn btn-icon" onclick="addItem(${moduleIndex})" title="Add Item">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
}

function getItemTypeIcon(type) {
    const icons = {
        'video': 'fa-play-circle',
        'reference': 'fa-external-link',
        'discussion': 'fa-comments',
        'lti': 'fa-puzzle-piece',
        'assignment': 'fa-file-text',
        'slide': 'fa-file-powerpoint-o'
    };
    return icons[type] || 'fa-circle';
}

function createItemHtml(item, moduleIndex, itemIndex) {
    const type = item.type || 'unknown';
    const title = getItemTitle(item);
    const isHeader = type === 'header';
    
    // Check if this header section is collapsed
    const stateKey = `header_${moduleIndex}_${itemIndex}_collapsed`;
    const isCollapsed = localStorage.getItem(stateKey) === 'true';
    
    return `
        <div class="item ${isHeader ? 'header-item' : ''}" data-module-index="${moduleIndex}" data-item-index="${itemIndex}">
            <span class="drag-handle" title="Drag to reorder"></span>
            <div class="item-content">
                <div class="item-header">
                    ${isHeader ? `<button class="header-toggle ${isCollapsed ? 'collapsed' : ''}" onclick="toggleHeaderSection(${moduleIndex}, ${itemIndex}, event)" title="Expand/Collapse section"></button>` : ''}
                    ${!isHeader ? `<span class="item-type ${type}" aria-label="${type}" title="${type}"><i class="fa ${getItemTypeIcon(type)}"></i></span>` : ''}
                    <span class="item-title">${escapeHtml(title)}</span>
                    <div class="item-actions">
                        ${isHeader ? `<button class="btn btn-icon add-item-after-btn" onclick="addItemAfter(${moduleIndex}, ${itemIndex})" title="Add item after this header">
                            <i class="fa fa-plus"></i>
                        </button>` : ''}
                        <button class="btn btn-icon edit-item-btn" title="Edit Item">
                            <i class="fa fa-pencil"></i>
                        </button>
                        <button class="btn btn-icon delete-item-btn" title="Delete Item">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function getItemTitle(item) {
    if (item.title) return item.title;
    if (item.type === 'header') return item.text || 'Header';
    if (item.type === 'reference') return item.href || 'Reference';
    if (item.type === 'assignment') return item.href || 'Assignment';
    if (item.type === 'slide') return item.href || 'Slide';
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
                
                if (prevItemData && prevItemData.type === 'header') {
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

function editModule(moduleIndex) {
    editingModuleIndex = moduleIndex;
    const module = lessonsData.modules[moduleIndex];
    
    const formHtml = `
        <div class="form-group">
            <label>Title:</label>
            <input type="text" id="edit-title" value="${escapeHtml(module.title || '')}">
        </div>
        <div class="form-group">
            <label>Anchor:</label>
            <input type="text" id="edit-anchor" value="${escapeHtml(module.anchor || '')}">
        </div>
        <div class="form-group">
            <label>Icon:</label>
            <input type="text" id="edit-icon" value="${escapeHtml(module.icon || '')}" 
                   placeholder="e.g., fa-smile-o">
        </div>
        <div class="form-group">
            <label>Description:</label>
            <textarea id="edit-description">${escapeHtml(module.description || '')}</textarea>
        </div>
        <div class="form-actions">
            <button class="btn btn-primary" onclick="saveModule()">Save</button>
            <button class="btn" onclick="closeModal()">Cancel</button>
        </div>
    `;
    
    $('#modal-title').text('Edit Module');
    $('#modal-body').html(formHtml);
    $('#item-modal').show();
}

function saveModule() {
    if (editingModuleIndex === null) return;
    
    lessonsData.modules[editingModuleIndex].title = $('#edit-title').val().trim();
    lessonsData.modules[editingModuleIndex].anchor = $('#edit-anchor').val().trim();
    lessonsData.modules[editingModuleIndex].icon = $('#edit-icon').val().trim();
    lessonsData.modules[editingModuleIndex].description = $('#edit-description').val().trim();
    
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
        if (item.type === 'video' && item.FCPX && !item.reference) {
            item.reference = item.FCPX;
            delete item.FCPX;
            markChanged();
        }
        showItemModal('Edit Item', item);
    }
}

function getDefaultItem() {
    return {
        type: 'header',
        text: '',
        level: 2
    };
}

function showItemModal(title, item) {
    const type = item.type || 'header';
    
    let formHtml = `
        <div class="form-group">
            <label>Type:</label>
            <select id="edit-item-type" onchange="updateItemForm()">
                <option value="header" ${type === 'header' ? 'selected' : ''}>Header</option>
                <option value="video" ${type === 'video' ? 'selected' : ''}>Video</option>
                <option value="reference" ${type === 'reference' ? 'selected' : ''}>Reference</option>
                <option value="discussion" ${type === 'discussion' ? 'selected' : ''}>Discussion</option>
                <option value="lti" ${type === 'lti' ? 'selected' : ''}>LTI</option>
                <option value="assignment" ${type === 'assignment' ? 'selected' : ''}>Assignment</option>
                <option value="slide" ${type === 'slide' ? 'selected' : ''}>Slide</option>
            </select>
        </div>
        <div id="item-form-fields"></div>
        <div class="form-actions">
            <button class="btn btn-primary" onclick="saveItem()">Save</button>
            <button class="btn" onclick="closeModal()">Cancel</button>
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
    const type = item.type || 'header';
    let fieldsHtml = '';
    
    if (type === 'header') {
        fieldsHtml = `
            <div class="form-group">
                <label>Text:</label>
                <input type="text" id="edit-text" value="${escapeHtml(item.text || '')}">
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
        // Handle FCPX migration for display
        const referenceValue = item.reference || item.FCPX || '';
        fieldsHtml = `
            <div class="form-group">
                <label>Title:</label>
                <input type="text" id="edit-title" value="${escapeHtml(item.title || '')}">
            </div>
            <div class="form-group">
                <label>YouTube ID:</label>
                <input type="text" id="edit-youtube" value="${escapeHtml(item.youtube || '')}">
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
    } else if (type === 'discussion') {
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
    }
    
    $('#item-form-fields').html(fieldsHtml);
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
    
    if (type === 'header') {
        item = {
            type: 'header',
            text: $('#edit-text').val().trim(),
            level: parseInt($('#edit-level').val())
        };
    } else if (type === 'video') {
        const youtubeVal = $('#edit-youtube').val().trim();
        const mediaVal = $('#edit-media').val().trim();
        const referenceVal = $('#edit-reference').val().trim();
        item = {
            type: 'video',
            title: $('#edit-title').val().trim(),
            youtube: youtubeVal || undefined,
            media: mediaVal || undefined,
            reference: referenceVal || undefined
        };
        // Remove undefined fields
        Object.keys(item).forEach(key => item[key] === undefined && delete item[key]);
        // Ensure FCPX is removed if it exists (migrated to reference)
        if (item.FCPX) {
            delete item.FCPX;
        }
    } else if (type === 'reference') {
        item = {
            type: 'reference',
            title: $('#edit-title').val().trim(),
            href: $('#edit-href').val().trim()
        };
    } else if (type === 'discussion') {
        item = {
            type: 'discussion',
            title: $('#edit-title').val().trim(),
            launch: $('#edit-launch').val().trim(),
            resource_link_id: $('#edit-resource-link-id').val().trim()
        };
    } else if (type === 'lti') {
        const custom = [];
        $('#custom-fields-container .custom-field').each(function() {
            const key = $(this).find('.custom-key').val().trim();
            const value = $(this).find('.custom-value').val().trim();
            if (key && value) {
                custom.push({ key: key, value: value });
            }
        });
        
        item = {
            type: 'lti',
            title: $('#edit-title').val().trim(),
            launch: $('#edit-launch').val().trim(),
            resource_link_id: $('#edit-resource-link-id').val().trim()
        };
        
        const targetVal = $('#edit-target').val().trim();
        if (targetVal) {
            item.target = targetVal;
        }
        
        if (custom.length > 0) {
            item.custom = custom;
        }
    } else if (type === 'assignment') {
        item = {
            type: 'assignment',
            href: $('#edit-href').val().trim()
        };
    } else if (type === 'slide') {
        item = {
            type: 'slide',
            title: $('#edit-title').val().trim(),
            href: $('#edit-href').val().trim()
        };
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
        if (nextItemData && nextItemData.type === 'header') {
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

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('item-modal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>

<?php

namespace Tsugi\UI;

/**
 * CKEditor 5 helper for Tsugi applications.
 *
 * Provides reusable configuration, styles, and utilities for CKEditor 5
 * so multiple controllers (Pages, Announcements, etc.) can use it DRY.
 *
 * Usage:
 *   CKEditor::renderScriptTag();
 *   CKEditor::renderConfigScript(['pagesBase' => $url, 'appHome' => $url]);
 *   CKEditor::renderStyles(['includeLinkPicker' => true]);
 */
class CKEditor {

    /**
     * Default CDN URL for CKEditor 5 Classic build.
     */
    const CDN_URL = 'https://cdn.ckeditor.com/ckeditor5/16.0.0/classic/ckeditor.js';

    /**
     * Default toolbar items for rich text editing.
     */
    const DEFAULT_TOOLBAR = [
        'heading',
        '|',
        'bold',
        'italic',
        'link',
        'bulletedList',
        'numberedList',
        'blockQuote',
        'insertTable',
        'mediaEmbed',
        'undo',
        'redo'
    ];

    /**
     * Output the script tag to load CKEditor from CDN.
     *
     * @param string|null $url Optional override for CDN URL
     */
    public static function renderScriptTag($url = null)
    {
        $url = $url ?: self::CDN_URL;
        echo '<script src="' . htmlspecialchars($url) . '"></script>';
    }

    /**
     * Output JavaScript that sets ClassicEditor.defaultConfig.
     *
     * The link decorator callback uses pagesBase and appHome from the caller's
     * scope - ensure those variables are defined before this runs.
     *
     * @param array $options Optional overrides:
     *   - toolbar: array of toolbar item names (default: DEFAULT_TOOLBAR)
     *   - linkDecorators: array to merge/add to link.decorators (default: openExternalInNewTab)
     */
    public static function renderConfigScript(array $options = [])
    {
        $toolbar = $options['toolbar'] ?? self::DEFAULT_TOOLBAR;
        $toolbarJson = json_encode($toolbar);

        // Link decorator callback - expects pagesBase, filesBase, and appHome in scope
        $linkCallback = 'function(url) {
            if (!url) return false;
            if (typeof pagesBase !== "undefined" && pagesBase && url.indexOf(pagesBase) === 0) return false;
            if (typeof filesBase !== "undefined" && filesBase && url.indexOf(filesBase) === 0) return false;
            if (typeof appHome !== "undefined" && appHome && url.indexOf(appHome + "/lessons") === 0) return false;
            var slideExt = /\.(pptx?|pptm|pdf|key|odp)(\?|$)/i;
            if (slideExt.test(url)) return true;
            if (typeof appHome === "undefined" || !appHome) return (url.indexOf("youtube.com") !== -1 || url.indexOf("youtu.be") !== -1);
            return url.indexOf(appHome) !== 0;
        }';

        ?>
        ClassicEditor.defaultConfig = {
            toolbar: {
                items: <?= $toolbarJson ?>
            },
            link: {
                decorators: {
                    openExternalInNewTab: {
                        mode: 'automatic',
                        callback: <?= $linkCallback ?>,
                        attributes: {
                            target: '_blank',
                            rel: 'noopener noreferrer'
                        }
                    }
                }
            }
        };
        <?php
    }

    /**
     * Output CSS styles for CKEditor and optional link picker modal.
     *
     * @param array $options Optional:
     *   - includeLinkPicker: bool, include modal/expando styles (default: false)
     *   - includeLinkUnderline: bool, underline links in editor and content (default: true)
     *   - extraStyles: string, additional CSS to append (e.g. .ckeditor-container { min-height: 400px; })
     */
    public static function renderStyles(array $options = [])
    {
        $includeLinkPicker = $options['includeLinkPicker'] ?? false;
        $includeLinkUnderline = $options['includeLinkUnderline'] ?? true;
        $extraStyles = $options['extraStyles'] ?? '';
        ?>
        <?php if ($extraStyles): ?>
        <?= $extraStyles ?>

        <?php endif; ?>
        <?php if ($includeLinkUnderline): ?>
        .ck-editor .ck-content a, .ck.ck-editor__editable a { text-decoration: underline; }
        <?php endif; ?>
        /* Make links in rendered page content stand out - override weak grey defaults */
        .page-content a {
            color: #0d6efd !important;
            text-decoration: underline !important;
            font-weight: 500;
            transition: color 0.15s ease;
        }
        .page-content a:hover {
            color: #0a58ca !important;
            text-decoration: underline !important;
        }
        .page-content a:visited {
            color: #6f42c1 !important;
        }
        .page-content a:visited:hover {
            color: #5a32a3 !important;
        }
        <?php if ($includeLinkPicker): ?>
        #page-link-modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); transition: opacity 0.25s ease; opacity: 0; }
        #page-link-modal.open { opacity: 1; }
        #page-link-modal-content { position: fixed; right: 0; top: 0; height: 100%; width: 360px; max-width: 90%; background-color: #fefefe; padding: 20px; box-shadow: -4px 0 20px rgba(0,0,0,0.15); overflow-y: auto; transition: transform 0.25s ease; transform: translateX(100%); }
        #page-link-modal.open #page-link-modal-content { transform: translateX(0); }
        #page-link-list { max-height: calc(100vh - 120px); overflow-y: auto; margin: 10px 0; }
        .page-link-item { display: block; width: 100%; padding: 8px; text-align: left; cursor: pointer; border: none; border-bottom: 1px solid #ddd; background: transparent; font-size: inherit; }
        .page-link-item:hover { background-color: #f0f0f0; }
        .page-link-expando { margin: 8px 0; border: 1px solid #ddd; border-radius: 4px; }
        .page-link-expando-header { display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; cursor: pointer; font-weight: bold; color: #555; font-size: 12px; text-transform: uppercase; background: #f8f8f8; border-radius: 4px; user-select: none; }
        .page-link-expando-header:hover { background: #eee; }
        .page-link-expando-header .expando-chevron { transition: transform 0.2s ease; display: inline-block; }
        .page-link-expando.collapsed .page-link-expando-header .expando-chevron { transform: rotate(-90deg); }
        .page-link-expando-content { max-height: 280px; overflow-y: auto; transition: max-height 0.2s ease; }
        .page-link-expando.collapsed .page-link-expando-content { max-height: 0; overflow: hidden; }
        .page-link-expando-content.page-link-expando-nested { max-height: none; overflow: visible; }
        .page-link-expando.collapsed > .page-link-expando-content.page-link-expando-nested { max-height: 0; overflow: hidden; }
        .page-link-expando .page-link-expando { margin: 0; border: none; border-radius: 0; border-bottom: 1px solid #eee; }
        .page-link-expando .page-link-expando .page-link-expando-header { text-transform: none; font-size: 13px; }
        [data-page-link-button] { display: inline-flex !important; align-items: center !important; }
        [data-page-link-button] .ck-icon { width: 20px !important; height: 20px !important; }
        <?php endif; ?>
        <?php
    }

    /**
     * Output the standard link picker modal HTML.
     *
     * Uses id="page-link-modal" and id="page-link-list" for the modal and list container.
     * Pair with renderLinkPickerScript() to populate the list.
     *
     * @param string $modalTitle Optional title (default: 'Insert link')
     */
    public static function renderLinkPickerModal($modalTitle = 'Insert link')
    {
        ?>
        <div id="page-link-modal" role="dialog" aria-modal="true" aria-labelledby="page-link-modal-title" aria-describedby="page-link-list" tabindex="-1">
            <div id="page-link-modal-content">
                <div class="page-link-modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h3 id="page-link-modal-title" style="margin: 0;"><?= htmlspecialchars($modalTitle) ?></h3>
                    <button type="button" onclick="closePageLinkModal()" class="btn btn-default">Cancel</button>
                </div>
                <div id="page-link-list" role="list"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Output the Insert-link picker JavaScript (fetch, expandos, modal, toolbar button).
     *
     * Caller must define before this runs:
     *   pagesJsonUrl, lessonsJsonUrl, pagesBase, appHome, currentPageId
     * Optional: filesJsonUrl, filesBase
     *
     * Expects #editor_body, #page_form, and the modal from renderLinkPickerModal().
     */
    public static function renderLinkPickerScript()
    {
        ?>
        var editor;
        var pagesList = [];
        var lessonsList = [];
        var lessonsModules = [];
        var filesList = [];

        function fetchJson(url) {
            if (!url) return Promise.resolve(null);
            return fetch(url).then(function(r) { return r.json(); }).catch(function() { return null; });
        }

        Promise.all([
            fetchJson(pagesJsonUrl),
            fetchJson(lessonsJsonUrl),
            fetchJson(typeof filesJsonUrl !== 'undefined' ? filesJsonUrl : '')
        ]).then(function(results) {
            pagesList = results[0] || [];
            var lessonsData = results[1] || {};
            lessonsList = lessonsData.items || (Array.isArray(lessonsData) ? lessonsData : []);
            lessonsModules = lessonsData.modules || [];
            filesList = Array.isArray(results[2]) ? results[2] : [];
            populatePageLinkList();
        }).catch(function(error) {
            console.error('Error loading link data:', error);
        });

        function createExpandoSection(title, count, itemsContainer, startCollapsed) {
            var expando = document.createElement('div');
            expando.className = 'page-link-expando' + (startCollapsed ? ' collapsed' : '');
            expando.setAttribute('role', 'group');
            expando.setAttribute('aria-label', title);

            var header = document.createElement('div');
            header.className = 'page-link-expando-header';
            header.setAttribute('role', 'button');
            header.setAttribute('aria-expanded', !startCollapsed);
            header.setAttribute('tabindex', '0');
            header.innerHTML = '<span>' + title + ' (' + count + ')</span><span class="expando-chevron" aria-hidden="true">&#9660;</span>';

            header.onclick = function(e) {
                e.preventDefault();
                expando.classList.toggle('collapsed');
                header.setAttribute('aria-expanded', !expando.classList.contains('collapsed'));
            };
            header.onkeydown = function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    expando.classList.toggle('collapsed');
                    header.setAttribute('aria-expanded', !expando.classList.contains('collapsed'));
                }
            };

            expando.appendChild(header);
            expando.appendChild(itemsContainer);
            return expando;
        }

        function addLinkButton(container, label, title, onInsert) {
            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'page-link-item';
            item.textContent = label;
            item.setAttribute('role', 'listitem');
            if (title) item.setAttribute('title', title);
            item.onclick = function() {
                onInsert();
                closePageLinkModal();
            };
            item.onkeydown = function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    onInsert();
                    closePageLinkModal();
                }
            };
            container.appendChild(item);
        }

        function populatePageLinkList() {
            var listDiv = document.getElementById('page-link-list');
            if (!listDiv) return;

            listDiv.innerHTML = '';

            var displayPages = currentPageId ? pagesList.filter(function(p) { return p.id != currentPageId; }) : pagesList;
            var hasContent = false;

            if (displayPages.length > 0) {
                var pagesContent = document.createElement('div');
                pagesContent.className = 'page-link-expando-content';
                pagesContent.setAttribute('role', 'list');

                displayPages.forEach(function(page) {
                    addLinkButton(pagesContent, page.title, page.url, function() { insertPageLink(page); });
                });

                listDiv.appendChild(createExpandoSection('Pages', displayPages.length, pagesContent, true));
                hasContent = true;
            }

            if (filesList.length > 0) {
                var filesOuter = document.createElement('div');
                filesOuter.className = 'page-link-expando-content page-link-expando-nested';
                var byFolder = {};
                filesList.forEach(function(fileItem) {
                    var folder = fileItem.folder || '';
                    if (!byFolder[folder]) byFolder[folder] = [];
                    byFolder[folder].push(fileItem);
                });
                var folders = Object.keys(byFolder).sort(function(a, b) {
                    if (a === '') return -1;
                    if (b === '') return 1;
                    return a.toLowerCase().localeCompare(b.toLowerCase());
                });
                folders.forEach(function(folder) {
                    var items = byFolder[folder];
                    var content = document.createElement('div');
                    content.className = 'page-link-expando-content';
                    content.setAttribute('role', 'list');
                    items.forEach(function(fileItem) {
                        addLinkButton(content, fileItem.title, fileItem.path || fileItem.url, function() { insertFileLink(fileItem); });
                    });
                    var label = folder === '' ? 'Course files' : folder;
                    filesOuter.appendChild(createExpandoSection(label, items.length, content, folders.length > 1));
                });
                listDiv.appendChild(createExpandoSection('Files', filesList.length, filesOuter, true));
                hasContent = true;
            }

            if (lessonsModules.length > 0) {
                var modulesContent = document.createElement('div');
                modulesContent.className = 'page-link-expando-content';
                modulesContent.setAttribute('role', 'list');

                lessonsModules.forEach(function(moduleItem) {
                    addLinkButton(modulesContent, moduleItem.title, moduleItem.url, function() { insertLessonLink(moduleItem); });
                });

                listDiv.appendChild(createExpandoSection('Modules', lessonsModules.length, modulesContent, true));
                hasContent = true;
            }

            if (lessonsList.length > 0) {
                var typeGroups = {
                    video: { label: 'Videos', items: [] },
                    discussion: { label: 'Discussions', items: [] },
                    lti: { label: 'LTI & Tools', items: [] },
                    'not-lti': { label: 'LTI & Tools', items: [] },
                    reference: { label: 'References', items: [] },
                    slide: { label: 'Slides', items: [] }
                };

                lessonsList.forEach(function(lessonItem) {
                    var t = lessonItem.type || 'reference';
                    if (t === 'not-lti') t = 'lti';
                    if (typeGroups[t]) typeGroups[t].items.push(lessonItem);
                });

                var ltiItems = (typeGroups.lti ? typeGroups.lti.items : []).concat(typeGroups['not-lti'] ? typeGroups['not-lti'].items : []);
                var groupsToShow = [
                    { label: 'Videos', items: typeGroups.video ? typeGroups.video.items : [] },
                    { label: 'Discussions', items: typeGroups.discussion ? typeGroups.discussion.items : [] },
                    { label: 'LTI & Tools', items: ltiItems },
                    { label: 'References', items: typeGroups.reference ? typeGroups.reference.items : [] },
                    { label: 'Slides', items: typeGroups.slide ? typeGroups.slide.items : [] }
                ];

                groupsToShow.forEach(function(group) {
                    if (!group.items || group.items.length === 0) return;

                    var content = document.createElement('div');
                    content.className = 'page-link-expando-content';
                    content.setAttribute('role', 'list');

                    group.items.forEach(function(lessonItem) {
                        var label = lessonItem.title + (lessonItem.module ? ' (' + lessonItem.module + ')' : '');
                        addLinkButton(content, label, lessonItem.url, function() { insertLessonLink(lessonItem); });
                    });

                    listDiv.appendChild(createExpandoSection(group.label, group.items.length, content, true));
                    hasContent = true;
                });
            }

            if (!hasContent) {
                listDiv.innerHTML = '<p role="status">No pages, files, or lesson content available.</p>';
            }
        }

        var pageLinkModalFocusBeforeOpen = null;

        function showPageLinkModal() {
            var modal = document.getElementById('page-link-modal');
            pageLinkModalFocusBeforeOpen = document.activeElement;
            modal.style.display = 'block';
            requestAnimationFrame(function() { modal.classList.add('open'); });
            document.addEventListener('keydown', pageLinkModalKeyHandler);
            var firstFocusable = modal.querySelector('.page-link-item') || modal.querySelector('button');
            if (firstFocusable) {
                firstFocusable.focus();
            } else {
                modal.focus();
            }
        }

        function closePageLinkModal() {
            var modal = document.getElementById('page-link-modal');
            modal.classList.remove('open');
            document.removeEventListener('keydown', pageLinkModalKeyHandler);
            setTimeout(function() {
                modal.style.display = 'none';
                if (pageLinkModalFocusBeforeOpen) {
                    pageLinkModalFocusBeforeOpen.focus();
                }
            }, 250);
        }

        function pageLinkModalKeyHandler(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                closePageLinkModal();
            }
        }

        function insertLinkedText(url, title) {
            if (!editor) return;
            const model = editor.model;
            const selection = model.document.selection;

            model.change(writer => {
                if (selection.isCollapsed) {
                    const textNode = writer.createText(title);
                    const insertPosition = selection.getFirstPosition();
                    const insertedRange = model.insertContent(textNode, insertPosition);
                    writer.setSelection(insertedRange);
                }
            });

            editor.execute('link', url);
        }

        function insertPageLink(page) {
            insertLinkedText(pagesBase + '/' + encodeURIComponent(page.logical_key), page.title);
        }

        function insertLessonLink(lessonItem) {
            insertLinkedText(lessonItem.url, lessonItem.title);
        }

        function insertFileLink(fileItem) {
            insertLinkedText(fileItem.url, fileItem.title);
        }

        $(document).ready( function () {
            ClassicEditor
                .create( document.querySelector( '#editor_body' ), ClassicEditor.defaultConfig )
                .then(ed => {
                    editor = ed;
                    setTimeout(function() {
                        addPageLinkButtonToToolbar();
                    }, 500);
                })
                .catch( error => {
                    console.error( error );
                });

            $('#page_form').on('submit', function(e) {
                if ( editor ) {
                    $('#editor_body').val(editor.getData());
                }
            });

            window.onclick = function(event) {
                var modal = document.getElementById('page-link-modal');
                if (event.target == modal) {
                    closePageLinkModal();
                }
            };
        });

        function addPageLinkButtonToToolbar() {
            var toolbar = document.querySelector('.ck-editor .ck-toolbar') ||
                          document.querySelector('.ck.ck-toolbar') ||
                          document.querySelector('[class*="ck-toolbar"]');

            if (!toolbar) {
                console.log('Toolbar not found, retrying...');
                setTimeout(addPageLinkButtonToToolbar, 200);
                return;
            }

            if (toolbar.querySelector('[data-page-link-button]')) {
                return;
            }

            var linkButton = toolbar.querySelector('button[aria-label*="Link" i]') ||
                             toolbar.querySelector('button[title*="Link" i]') ||
                             toolbar.querySelector('.ck-button[class*="link" i]');

            var separator = document.createElement('span');
            separator.className = 'ck ck-toolbar__separator';

            var button = document.createElement('button');
            button.className = 'ck ck-button ck-toolbar__item';
            button.type = 'button';
            button.setAttribute('aria-label', 'Insert link');
            button.setAttribute('title', 'Insert link');
            button.setAttribute('data-page-link-button', 'true');
            button.innerHTML = '<svg class="ck-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width: 20px; height: 20px; fill: currentColor;" aria-hidden="true"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.15.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>';

            button.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                showPageLinkModal();
            };

            if (linkButton && linkButton.parentElement) {
                var parent = linkButton.parentElement;
                if (linkButton.nextSibling) {
                    parent.insertBefore(separator, linkButton.nextSibling);
                    parent.insertBefore(button, separator.nextSibling);
                } else {
                    parent.appendChild(separator);
                    parent.appendChild(button);
                }
            } else {
                var toolbarGroup = toolbar.querySelector('.ck-toolbar__items') || toolbar;
                toolbarGroup.appendChild(separator);
                toolbarGroup.appendChild(button);
            }
        }
        <?php
    }
}

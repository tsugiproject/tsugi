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

        // Link decorator callback - expects pagesBase and appHome in scope
        $linkCallback = 'function(url) {
            if (!url) return false;
            if (typeof pagesBase !== "undefined" && pagesBase && url.indexOf(pagesBase) === 0) return false;
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
        .page-content a, .ck-editor .ck-content a, .ck.ck-editor__editable a { text-decoration: underline; }
        <?php endif; ?>
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
        [data-page-link-button] { display: inline-flex !important; align-items: center !important; }
        [data-page-link-button] .ck-icon { width: 20px !important; height: 20px !important; }
        <?php endif; ?>
        <?php
    }

    /**
     * Output the standard link picker modal HTML.
     *
     * Uses id="page-link-modal" and id="page-link-list" for the modal and list container.
     * Caller must provide JavaScript to populate the list and handle insert.
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
}

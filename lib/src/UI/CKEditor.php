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
 *
 * Pages uses a newer modular build (font, text color, styled rules) via
 * renderPagesEditorAssets() and renderPagesEditorModule(). The module
 * imports ckeditor.js by its full URL. Other tools stay on the classic
 * build from CDN_URL.
 */
class CKEditor {

    /**
     * Default CDN URL for CKEditor 5 Classic build.
     */
    const CDN_URL = 'https://cdn.ckeditor.com/ckeditor5/16.0.0/classic/ckeditor.js';

    /**
     * Modular CKEditor 5 used by the Pages editor (font, color, styled rules).
     */
    const PAGES_VERSION = '49.1.0';

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
     * Pages toolbar: the classic items plus font, text color, and styled rules.
     */
    const PAGES_TOOLBAR = [
        'heading',
        '|',
        'fontFamily',
        'fontSize',
        'fontColor',
        'highlight',
        '|',
        'bold',
        'italic',
        'link',
        '|',
        'alignment',
        'bulletedList',
        'numberedList',
        '|',
        'blockQuote',
        'codeBlock',
        'styledHorizontalLine',
        'insertTable',
        'mediaEmbed',
        '|',
        'undo',
        'redo',
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
     *   - automaticExternalBlank: bool, auto target=_blank on external URLs (default: true).
     *     Set false when the author must be able to clear target=_blank; the automatic
     *     decorator puts it back on getData() even if the manual switch is off.
     */
    public static function renderConfigScript(array $options = [])
    {
        $toolbar = $options['toolbar'] ?? self::DEFAULT_TOOLBAR;
        $toolbarJson = json_encode($toolbar);
        $automatic = array_key_exists('automaticExternalBlank', $options)
            ? ! empty($options['automaticExternalBlank'])
            : true;

        // Automatic decorator: external / slide / YouTube URLs, and uploaded
        // course files, get target=_blank. Same-site pages and lessons stay
        // in the current tab unless the author turns on the manual switch.
        // Expects pagesBase, filesBase, and appHome in scope.
        $linkCallback = 'function(url) {
            if (!url) return false;
            if (typeof pagesBase !== "undefined" && pagesBase && url.indexOf(pagesBase) === 0) return false;
            if (typeof filesBase !== "undefined" && filesBase) {
                var filesRoot = String(filesBase).replace(/\\/$/, "");
                if (url.indexOf(filesRoot + "/") === 0) return true;
            }
            if (typeof appHome !== "undefined" && appHome && url.indexOf(appHome + "/lessons") === 0) return false;
            var slideExt = /\.(pptx?|pptm|pdf|key|odp)(\?|$)/i;
            if (slideExt.test(url)) return true;
            if (typeof appHome === "undefined" || !appHome) return (url.indexOf("youtube.com") !== -1 || url.indexOf("youtu.be") !== -1);
            return url.indexOf(appHome) !== 0;
        }';

        ?>
        var tsugiEditorConfig = {
            toolbar: {
                items: <?= $toolbarJson ?>
            },
            link: {
                decorators: {
                    <?php if ( $automatic ) { ?>
                    openExternalInNewTab: {
                        mode: 'automatic',
                        callback: <?= $linkCallback ?>,
                        attributes: {
                            target: '_blank',
                            rel: 'noopener noreferrer'
                        }
                    },
                    <?php } ?>
                    openInNewTab: {
                        mode: 'manual',
                        label: 'Open in a new tab',
                        attributes: {
                            target: '_blank',
                            rel: 'noopener noreferrer'
                        }
                    }
                }
            }
        };
        if (typeof ClassicEditor !== 'undefined') {
            ClassicEditor.defaultConfig = tsugiEditorConfig;
        }
        <?php
    }

    /**
     * Stylesheet for the Pages editor.
     *
     * The files are a self-hosted GPL build of CKEditor 5 (PAGES_VERSION) at
     * lib/src/Controllers/static/Pages/. The CDN build is the commercial
     * "cloud" channel and opens read-only when licenseKey is GPL.
     *
     * The editor script imports that file by URL. An import map cannot be
     * added here: the page footer already loads other modules, and a map
     * that appears after those is ignored.
     *
     * Call this before renderPagesEditorModule(). Quiz, catalog, and site
     * keep renderScriptTag() and the classic build.
     */
    public static function renderPagesEditorAssets()
    {
        $base = self::pagesEditorBase();
        echo '<link rel="stylesheet" href="' . htmlspecialchars($base . 'ckeditor.css', ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }

    /**
     * Absolute URL of the Pages CKEditor folder, with a trailing slash.
     */
    private static function pagesEditorBase()
    {
        global $CFG;
        $base = '/lib/src/Controllers/static/Pages/';
        if (isset($CFG) && isset($CFG->wwwroot) && is_string($CFG->wwwroot) && $CFG->wwwroot !== '') {
            $base = rtrim($CFG->wwwroot, '/') . $base;
        }
        return $base;
    }

    /**
     * Module that creates the Pages editor: fonts, text color, and styled rules.
     *
     * The caller sets window.tsugiPagesEditor and includes renderConfigScript()
     * plus renderLinkPickerScript() first, so this can call tsugiBindEditor().
     */
    public static function renderPagesEditorModule()
    {
        $toolbarJson = json_encode(self::PAGES_TOOLBAR);
        $colorsJson = json_encode(self::pagesColors());
        $sizesJson = json_encode(self::pagesFontSizes());
        $thicknessJson = json_encode(self::pagesRuleThicknesses());
        $editorSrc = json_encode(self::pagesEditorBase() . 'ckeditor.js', JSON_UNESCAPED_SLASHES);
        ?>
        <script type="module">
        import {
            ClassicEditor,
            Essentials,
            Paragraph,
            Heading,
            Bold,
            Italic,
            Font,
            Link,
            List,
            Indent,
            BlockQuote,
            Alignment,
            Highlight,
            CodeBlock,
            HorizontalLine,
            GeneralHtmlSupport,
            Table,
            TableToolbar,
            MediaEmbed,
            MediaEmbedStyle,
            MediaEmbedToolbar,
            Image,
            ImageCaption,
            ImageStyle,
            ImageToolbar,
            ImageUpload,
            Autoformat,
            PasteFromOffice,
            Plugin,
            Command,
            View,
            createDropdown,
            IconHorizontalLine
        } from <?= $editorSrc ?>;

        var fontColors = <?= $colorsJson ?>;
        var ruleThicknesses = <?= $thicknessJson ?>;

        function ruleStyles(thickness, color) {
            return {
                styles: {
                    'border-top-width': thickness,
                    'border-top-style': 'solid',
                    'border-top-color': color,
                    'border-right-width': '0px',
                    'border-bottom-width': '0px',
                    'border-left-width': '0px',
                    'height': '0px',
                    'background-color': 'transparent',
                    'margin-top': '1.25em',
                    'margin-bottom': '1.25em'
                }
            };
        }

        class StyledHorizontalLineCommand extends Command {
            execute(options) {
                var thickness = options && options.thickness ? options.thickness : '4px';
                var color = options && options.color ? options.color : '#222222';
                var attrs = ruleStyles(thickness, color);
                var editor = this.editor;
                var selected = editor.model.document.selection.getSelectedElement();
                try {
                    editor.model.change(function(writer) {
                        if (selected && selected.is('element', 'horizontalLine')) {
                            writer.setAttribute('htmlHrAttributes', attrs, selected);
                            return;
                        }
                        var hr = writer.createElement('horizontalLine', {
                            htmlHrAttributes: attrs
                        });
                        editor.model.insertObject(hr);
                    });
                } catch (err) {
                    console.error(err);
                    editor.execute('horizontalLine');
                }
            }

            refresh() {
                var selected = this.editor.model.document.selection.getSelectedElement();
                if (selected && selected.is('element', 'horizontalLine')) {
                    this.isEnabled = true;
                    return;
                }
                var plain = this.editor.commands.get('horizontalLine');
                this.isEnabled = plain ? plain.isEnabled : true;
            }
        }

        function selectedRule(editor) {
            var selected = editor.model.document.selection.getSelectedElement();
            if (!selected || !selected.is('element', 'horizontalLine')) {
                return null;
            }
            var attrs = selected.getAttribute('htmlHrAttributes') || {};
            var styles = attrs.styles || {};
            return {
                thickness: styles['border-top-width'] || '4px',
                color: (styles['border-top-color'] || '#4b5563').toLowerCase()
            };
        }

        class RulePickerView extends View {
            constructor(locale) {
                super(locale);
                this.setTemplate({
                    tag: 'div',
                    attributes: {
                        class: ['tsugi-hr-picker', 'ck-reset_all-excluded'],
                        role: 'group',
                        'aria-label': 'Horizontal line'
                    }
                });
            }
        }

        function buildRulePicker(root, editor, onPick) {
            var line = selectedRule(editor);
            var thickness = line ? line.thickness : '4px';
            var pickedThickness = false;
            root.replaceChildren();

            var heading = document.createElement('p');
            heading.textContent = 'Thickness';
            root.appendChild(heading);

            var thicknessRow = document.createElement('div');
            thicknessRow.className = 'tsugi-hr-thicknesses';
            thicknessRow.setAttribute('role', 'radiogroup');
            thicknessRow.setAttribute('aria-label', 'Line thickness');
            var thicknessButtons = [];
            ruleThicknesses.forEach(function(item) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'tsugi-hr-thickness ck-reset_all-excluded' + (item.value === thickness ? ' is-selected' : '');
                button.textContent = item.label;
                button.setAttribute('role', 'radio');
                button.setAttribute('aria-checked', item.value === thickness ? 'true' : 'false');
                button.addEventListener('mousedown', function(event) { event.preventDefault(); });
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    thickness = item.value;
                    pickedThickness = true;
                    thicknessButtons.forEach(function(other) {
                        var on = other === button;
                        other.classList.toggle('is-selected', on);
                        other.setAttribute('aria-checked', on ? 'true' : 'false');
                    });
                    var current = selectedRule(editor);
                    if (current) {
                        onPick(item.value, current.color, false);
                    }
                });
                thicknessButtons.push(button);
                thicknessRow.appendChild(button);
            });
            root.appendChild(thicknessRow);

            var colorHeading = document.createElement('p');
            colorHeading.textContent = 'Color';
            root.appendChild(colorHeading);

            var colorRow = document.createElement('div');
            colorRow.className = 'tsugi-hr-colors';
            fontColors.forEach(function(item) {
                var button = document.createElement('button');
                button.type = 'button';
                var chosen = line && line.color === item.color.toLowerCase();
                button.className = 'tsugi-hr-swatch ck-reset_all-excluded' + (chosen ? ' is-selected' : '');
                button.style.backgroundColor = item.color;
                button.setAttribute('aria-label', item.label);
                button.setAttribute('title', item.label);
                button.addEventListener('mousedown', function(event) { event.preventDefault(); });
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    var current = selectedRule(editor);
                    var useThickness = pickedThickness ? thickness : (current ? current.thickness : thickness);
                    onPick(useThickness, item.color, true);
                });
                colorRow.appendChild(button);
            });
            root.appendChild(colorRow);

            var hint = document.createElement('p');
            hint.className = 'tsugi-hr-hint';
            hint.textContent = 'Click a color to insert a line. With a line selected, click a thickness or a color to change it.';
            root.appendChild(hint);
        }

        class StyledHorizontalLine extends Plugin {
            static get requires() {
                return [HorizontalLine, GeneralHtmlSupport];
            }
            static get pluginName() {
                return 'StyledHorizontalLine';
            }
            init() {
                var editor = this.editor;
                editor.commands.add('styledHorizontalLine', new StyledHorizontalLineCommand(editor));
                editor.ui.componentFactory.add('styledHorizontalLine', function(locale) {
                    var command = editor.commands.get('styledHorizontalLine');
                    var dropdown = createDropdown(locale);
                    dropdown.buttonView.set({
                        label: 'Horizontal line',
                        icon: IconHorizontalLine,
                        tooltip: true
                    });
                    dropdown.bind('isEnabled').to(command, 'isEnabled');
                    var picker = new RulePickerView(locale);
                    function applyRule(thickness, color, close) {
                        editor.execute('styledHorizontalLine', { thickness: thickness, color: color });
                        if (close) {
                            dropdown.isOpen = false;
                            editor.editing.view.focus();
                        }
                    }
                    picker.on('render', function() {
                        buildRulePicker(picker.element, editor, applyRule);
                    });
                    dropdown.on('change:isOpen', function() {
                        if (dropdown.isOpen && picker.element) {
                            buildRulePicker(picker.element, editor, applyRule);
                        }
                    });
                    dropdown.panelView.children.add(picker);
                    return dropdown;
                });
            }
        }

        var mediaSizes = [
            { label: 'Small', value: '25%' },
            { label: 'Medium', value: '50%' },
            { label: 'Large', value: '75%' },
            { label: 'Full', value: '100%' }
        ];

        class TsugiMediaSizeCommand extends Command {
            execute(options) {
                var width = options && options.width ? options.width : null;
                var editor = this.editor;
                var selected = editor.model.document.selection.getSelectedElement();
                if (!selected || !selected.is('element', 'media')) {
                    return;
                }
                editor.model.change(function(writer) {
                    if (width) {
                        writer.setAttribute('tsugiMediaWidth', width, selected);
                    } else {
                        writer.removeAttribute('tsugiMediaWidth', selected);
                    }
                });
            }

            refresh() {
                var selected = this.editor.model.document.selection.getSelectedElement();
                var onMedia = !!(selected && selected.is('element', 'media'));
                this.isEnabled = onMedia;
                this.value = onMedia ? (selected.getAttribute('tsugiMediaWidth') || null) : null;
            }
        }

        class MediaSizePickerView extends View {
            constructor(locale) {
                super(locale);
                this.setTemplate({
                    tag: 'div',
                    attributes: {
                        class: ['tsugi-media-sizes', 'ck-reset_all-excluded'],
                        role: 'group',
                        'aria-label': 'Video size'
                    }
                });
            }
        }

        function buildMediaSizePicker(root, editor, onPick) {
            var selected = editor.model.document.selection.getSelectedElement();
            var current = selected && selected.is('element', 'media')
                ? (selected.getAttribute('tsugiMediaWidth') || '100%')
                : '100%';
            root.replaceChildren();
            mediaSizes.forEach(function(item) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'tsugi-media-size ck-reset_all-excluded' + (item.value === current ? ' is-selected' : '');
                button.textContent = item.label;
                button.addEventListener('mousedown', function(event) { event.preventDefault(); });
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    onPick(item.value);
                });
                root.appendChild(button);
            });
        }

        class TsugiMediaSize extends Plugin {
            static get requires() {
                return [MediaEmbed];
            }
            static get pluginName() {
                return 'TsugiMediaSize';
            }
            afterInit() {
                var editor = this.editor;
                editor.model.schema.extend('media', { allowAttributes: ['tsugiMediaWidth'] });
                editor.conversion.for('downcast').add(function(dispatcher) {
                    dispatcher.on('attribute:tsugiMediaWidth:media', function(evt, data, conversionApi) {
                        if (!conversionApi.consumable.consume(data.item, evt.name)) {
                            return;
                        }
                        var figure = conversionApi.mapper.toViewElement(data.item);
                        if (!figure) {
                            return;
                        }
                        if (data.attributeNewValue) {
                            conversionApi.writer.setStyle('width', data.attributeNewValue, figure);
                        } else {
                            conversionApi.writer.removeStyle('width', figure);
                        }
                    });
                });
                editor.conversion.for('upcast').attributeToAttribute({
                    view: {
                        name: 'figure',
                        styles: { width: /.+/ }
                    },
                    model: {
                        key: 'tsugiMediaWidth',
                        value: function(viewElement) {
                            if (!viewElement.hasClass('media')) {
                                return null;
                            }
                            return viewElement.getStyle('width');
                        }
                    }
                });
                editor.commands.add('tsugiMediaSize', new TsugiMediaSizeCommand(editor));
                editor.ui.componentFactory.add('tsugiMediaSize', function(locale) {
                    var command = editor.commands.get('tsugiMediaSize');
                    var dropdown = createDropdown(locale);
                    dropdown.buttonView.set({
                        label: 'Video size',
                        withText: true,
                        tooltip: true
                    });
                    dropdown.bind('isEnabled').to(command, 'isEnabled');
                    var picker = new MediaSizePickerView(locale);
                    function applySize(width) {
                        editor.execute('tsugiMediaSize', { width: width });
                        dropdown.isOpen = false;
                        editor.editing.view.focus();
                    }
                    picker.on('render', function() {
                        buildMediaSizePicker(picker.element, editor, applySize);
                    });
                    dropdown.on('change:isOpen', function() {
                        if (dropdown.isOpen && picker.element) {
                            buildMediaSizePicker(picker.element, editor, applySize);
                        }
                    });
                    dropdown.panelView.children.add(picker);
                    return dropdown;
                });
            }
        }

        var node = document.querySelector('#editor_body');
        if (node && typeof window.tsugiBindEditor === 'function') {
            var base = window.tsugiEditorConfig || {};
            ClassicEditor
                .create(node, {
                    licenseKey: 'GPL',
                    plugins: [
                        Essentials,
                        Paragraph,
                        Heading,
                        Bold,
                        Italic,
                        Font,
                        Link,
                        List,
                        Indent,
                        BlockQuote,
                        Alignment,
                        Highlight,
                        CodeBlock,
                        HorizontalLine,
                        GeneralHtmlSupport,
                        StyledHorizontalLine,
                        Table,
                        TableToolbar,
                        MediaEmbed,
                        MediaEmbedStyle,
                        MediaEmbedToolbar,
                        TsugiMediaSize,
                        Image,
                        ImageCaption,
                        ImageStyle,
                        ImageToolbar,
                        ImageUpload,
                        Autoformat,
                        PasteFromOffice
                    ],
                    toolbar: { items: <?= $toolbarJson ?> },
                    link: base.link,
                    fontFamily: {
                        options: [
                            'default',
                            'Arial, Helvetica, sans-serif',
                            'Courier New, Courier, monospace',
                            'Georgia, serif',
                            'Lucida Sans Unicode, Lucida Grande, sans-serif',
                            'Palatino Linotype, Palatino, serif',
                            'Tahoma, Geneva, sans-serif',
                            'Times New Roman, Times, serif',
                            'Trebuchet MS, Helvetica, sans-serif',
                            'Verdana, Geneva, sans-serif'
                        ],
                        supportAllValues: true
                    },
                    fontSize: {
                        options: <?= $sizesJson ?>,
                        supportAllValues: true
                    },
                    fontColor: {
                        colors: fontColors,
                        columns: 6
                    },
                    alignment: {
                        options: ['left', 'center', 'right', 'justify']
                    },
                    codeBlock: {
                        languages: [
                            { language: 'plaintext', label: 'Plain text' },
                            { language: 'python', label: 'Python' },
                            { language: 'html', label: 'HTML' },
                            { language: 'css', label: 'CSS' },
                            { language: 'javascript', label: 'JavaScript' },
                            { language: 'sql', label: 'SQL' },
                            { language: 'json', label: 'JSON' },
                            { language: 'bash', label: 'Bash' },
                            { language: 'xml', label: 'XML' },
                            { language: 'php', label: 'PHP' }
                        ]
                    },
                    htmlSupport: {
                        allow: [
                            { name: 'hr', styles: true }
                        ]
                    },
                    table: {
                        contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
                    },
                    image: {
                        toolbar: ['imageTextAlternative']
                    },
                    mediaEmbed: {
                        previewsInData: true,
                        toolbar: [
                            'mediaEmbed:wrapText',
                            'mediaEmbed:breakText',
                            'tsugiMediaSize'
                        ]
                    }
                })
                .then(function(ed) {
                    window.tsugiBindEditor(ed);
                })
                .catch(function(error) {
                    console.error(error);
                });
        }
        </script>
        <?php
    }

    /**
     * Text colors shared by the font color dropdown and the line picker.
     *
     * @return list<array{color:string,label:string,hasBorder?:bool}>
     */
    private static function pagesColors()
    {
        return array(
            array('color' => '#111111', 'label' => 'Black'),
            array('color' => '#4b5563', 'label' => 'Dim gray'),
            array('color' => '#9ca3af', 'label' => 'Silver', 'hasBorder' => true),
            array('color' => '#dc2626', 'label' => 'Red'),
            array('color' => '#ea580c', 'label' => 'Orange'),
            array('color' => '#ca8a04', 'label' => 'Gold'),
            array('color' => '#16a34a', 'label' => 'Green'),
            array('color' => '#0d9488', 'label' => 'Teal'),
            array('color' => '#2563eb', 'label' => 'Blue'),
            array('color' => '#4f46e5', 'label' => 'Indigo'),
            array('color' => '#7c3aed', 'label' => 'Purple'),
            array('color' => '#db2777', 'label' => 'Pink'),
        );
    }

    /**
     * Pixel sizes. Inline font-size survives outside the editor.
     *
     * @return list<int|string>
     */
    private static function pagesFontSizes()
    {
        return array('default', 12, 14, 16, 18, 20, 24, 28, 36);
    }

    /**
     * Line thicknesses. The value is the rendered bar height.
     *
     * @return list<array{label:string,value:string}>
     */
    private static function pagesRuleThicknesses()
    {
        return array(
            array('label' => 'Hairline', 'value' => '1px'),
            array('label' => 'Thin', 'value' => '2px'),
            array('label' => 'Medium', 'value' => '4px'),
            array('label' => 'Thick', 'value' => '8px'),
        );
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
        /* A plain rule. A styled rule carries its thickness as border-top-width. */
        .page-content hr {
            display: block;
            width: 100%;
            height: 1px;
            border: 0;
            background-color: #cccccc;
            margin: 1.25em 0;
        }
        .page-content hr[style*="border-top-width"],
        .ck-content hr[style*="border-top-width"] {
            height: 0 !important;
            background: none !important;
            display: block;
        }
        .tsugi-hr-picker { padding: 10px 12px; width: 248px; box-sizing: border-box; }
        .tsugi-hr-picker p { margin: 0 0 6px; font-size: 12px; font-weight: 600; color: #333; }
        .tsugi-hr-picker .tsugi-hr-hint { font-weight: 400; color: #666; margin: 2px 0 0; }
        .tsugi-hr-thicknesses, .tsugi-hr-colors { display: flex; flex-wrap: wrap; gap: 6px; margin: 0 0 8px; }
        /* CKEditor's reset sets width/height/border auto on panel contents. */
        .ck .tsugi-hr-thickness,
        .tsugi-hr-thickness { width: auto !important; height: auto !important; border: 1px solid #ccc !important; background: #fff; border-radius: 3px; padding: 3px 8px !important; cursor: pointer; font-size: 12px; }
        .tsugi-hr-thickness.is-selected { border-color: #1d4ed8 !important; background: #eff6ff; color: #1e3a8a; }
        .tsugi-hr-swatch.is-selected { outline: 2px solid #1d4ed8; outline-offset: 1px; }
        /* Embedded video. Floated videos take half the column unless a size is set. */
        .page-content { display: flow-root; }
        .page-content figure.media {
            clear: both;
            display: block;
            margin: 0.9em auto;
            max-width: 100%;
        }
        .page-content figure.media iframe {
            width: 100%;
            height: auto;
            aspect-ratio: 16 / 9;
            border: 0;
            display: block;
        }
        .ck-content figure.media.media-style-align-left,
        .ck-content figure.media.media-style-align-right,
        .page-content figure.media.media-style-align-left,
        .page-content figure.media.media-style-align-right {
            clear: none;
            width: 50%;
            max-width: 100%;
        }
        .ck-content figure.media.media-style-align-left,
        .page-content figure.media.media-style-align-left {
            float: left;
            margin: 0.4em 1.5em 0.9em 0;
        }
        .ck-content figure.media.media-style-align-right,
        .page-content figure.media.media-style-align-right {
            float: right;
            margin: 0.4em 0 0.9em 1.5em;
        }
        .page-content figure.media.media-style-block-align-left {
            margin-left: 0;
            margin-right: auto;
        }
        .page-content figure.media.media-style-block-align-right {
            margin-left: auto;
            margin-right: 0;
        }
        .tsugi-media-sizes { display: flex; flex-wrap: wrap; gap: 6px; padding: 8px; }
        .ck .tsugi-media-size,
        .tsugi-media-size { width: auto !important; height: auto !important; border: 1px solid #ccc !important; background: #fff; border-radius: 3px; padding: 3px 8px !important; cursor: pointer; font-size: 12px; }
        .tsugi-media-size.is-selected { border-color: #1d4ed8 !important; background: #eff6ff; color: #1e3a8a; }
        .page-content pre {
            text-align: left;
            tab-size: 4;
            white-space: pre-wrap;
            word-break: normal;
            overflow-wrap: break-word;
            background: #f7f7f9;
            border: 1px solid #e1e1e8;
            border-radius: 4px;
            padding: 1em;
            margin: 0.9em 0;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }
        .page-content pre code {
            background: transparent;
            padding: 0;
            color: inherit;
            font-size: inherit;
        }
        .page-content .marker-yellow { background-color: #fdfd77; }
        .page-content .marker-green { background-color: #62f962; }
        .page-content .marker-pink { background-color: #fc7899; }
        .page-content .marker-blue { background-color: #72ccfd; }
        .page-content .pen-red { color: #e71313; background-color: transparent; }
        .page-content .pen-green { color: #128a00; background-color: transparent; }
        .ck .tsugi-hr-swatch,
        .tsugi-hr-swatch { width: 24px !important; height: 24px !important; min-width: 24px; border: 1px solid rgba(0,0,0,.35) !important; border-radius: 3px; padding: 0 !important; cursor: pointer; display: inline-block; }
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
        #page-link-new-tab-row { margin: 0 0 12px 0; font-weight: normal; display: flex; align-items: center; }
        #page-link-new-tab-row input { margin: 0 8px 0 0; }
        .ck-link-form .ck-button.ck-switchbutton { margin-top: 8px; }
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
                <label id="page-link-new-tab-row" for="page-link-new-tab">
                    <input type="checkbox" id="page-link-new-tab">
                    Open in a new tab
                </label>
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
                    slide: { label: 'Slides', items: [] },
                    assignment: { label: 'Assignments', items: [] },
                    solution: { label: 'Solutions', items: [] }
                };

                lessonsList.forEach(function(lessonItem) {
                    var t = lessonItem.type || 'reference';
                    if (t === 'not-lti') t = 'lti';
                    if (t === 'web_link' || t === 'html_page' || t === 'file') t = 'reference';
                    if (typeGroups[t]) typeGroups[t].items.push(lessonItem);
                });

                var ltiItems = (typeGroups.lti ? typeGroups.lti.items : []).concat(typeGroups['not-lti'] ? typeGroups['not-lti'].items : []);
                var groupsToShow = [
                    { label: 'Videos', items: typeGroups.video ? typeGroups.video.items : [] },
                    { label: 'Discussions', items: typeGroups.discussion ? typeGroups.discussion.items : [] },
                    { label: 'LTI & Tools', items: ltiItems },
                    { label: 'Assignments', items: typeGroups.assignment ? typeGroups.assignment.items : [] },
                    { label: 'Solutions', items: typeGroups.solution ? typeGroups.solution.items : [] },
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

        function shouldOpenLinkInNewTab(explicit) {
            if (explicit) return true;
            var cb = document.getElementById('page-link-new-tab');
            return !!(cb && cb.checked);
        }

        function insertLinkedText(url, title, openInNewTab) {
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

            if (shouldOpenLinkInNewTab(openInNewTab)) {
                editor.execute('link', url, { linkOpenInNewTab: true });
            } else {
                editor.execute('link', url);
            }
        }

        function completeLinkPick(url, title, targetBlank) {
            var openNew = shouldOpenLinkInNewTab(!!targetBlank);
            if (typeof window.tsugiLinkPickerCallback === 'function') {
                var cb = window.tsugiLinkPickerCallback;
                window.tsugiLinkPickerCallback = null;
                cb({ url: url, title: title || '', targetBlank: openNew });
                return;
            }
            insertLinkedText(url, title, openNew);
        }

        window.tsugiOpenLinkPicker = function(callback) {
            window.tsugiLinkPickerCallback = callback;
            showPageLinkModal();
        };

        function insertPageLink(page) {
            completeLinkPick(pagesBase + '/' + encodeURIComponent(page.logical_key), page.title);
        }

        function insertLessonLink(lessonItem) {
            completeLinkPick(lessonItem.url, lessonItem.title, !!lessonItem.target_blank);
        }

        function insertFileLink(fileItem) {
            var path = fileItem.path || '';
            var url = '';
            if (path && typeof filesBase !== 'undefined' && filesBase) {
                url = filesBase.replace(/\/$/, '') + '/' + path.split('/').map(encodeURIComponent).join('/');
            } else {
                url = fileItem.url || fileItem.href || '';
            }
            completeLinkPick(url, fileItem.title || fileItem.filename || '', true);
        }

        function tsugiBindEditor(ed) {
            editor = ed;
            setTimeout(function() {
                addPageLinkButtonToToolbar();
            }, 500);
        }
        window.tsugiBindEditor = tsugiBindEditor;

        $(document).ready( function () {
            if (!window.tsugiPagesEditor && typeof ClassicEditor !== 'undefined' && document.querySelector('#editor_body')) {
                var editorConfig = ClassicEditor.defaultConfig || window.tsugiEditorConfig;
                ClassicEditor
                    .create( document.querySelector( '#editor_body' ), editorConfig )
                    .then(tsugiBindEditor)
                    .catch( error => {
                        console.error( error );
                    });
            }

            $('#page_form').on('submit', function(e) {
                if ( editor ) {
                    $('#editor_body').val(editor.getData());
                }
            });

            document.addEventListener('click', function(event) {
                var modal = document.getElementById('page-link-modal');
                if (modal && event.target == modal) {
                    closePageLinkModal();
                }
            });
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

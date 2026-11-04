<?php
/**
 * Course home.
 *
 * Included from Home::render(). Expected:
 *   $course_title
 *   $layout (HomeLayout document)
 *   $can_configure (instructors see the gear)
 *   $save_url
 *   $pages (page_id, title, published)
 *   $page_blocks (page_id => title, body_html, published, edit_url)
 *   $files (path, name, image) non-private course files
 *   $file_blocks (path => name, url, image)
 *   $hero_url
 *   $calendar_html
 *   $dues_html
 */
$course_title = isset($course_title) ? trim((string) $course_title) : '';
$layout = (isset($layout) && is_array($layout)) ? $layout : array('blocks' => array());
$blocks = (isset($layout['blocks']) && is_array($layout['blocks'])) ? $layout['blocks'] : array();
$can_configure = ! empty($can_configure);
$save_url = isset($save_url) ? (string) $save_url : '';
$pages = (isset($pages) && is_array($pages)) ? $pages : array();
$page_blocks = (isset($page_blocks) && is_array($page_blocks)) ? $page_blocks : array();
$files = (isset($files) && is_array($files)) ? $files : array();
$file_blocks = (isset($file_blocks) && is_array($file_blocks)) ? $file_blocks : array();
$hero_url = isset($hero_url) ? trim((string) $hero_url) : '';
$calendar_html = isset($calendar_html) ? (string) $calendar_html : '';
$dues_html = isset($dues_html) ? (string) $dues_html : '';
$hero_alt = $course_title !== '' ? $course_title : __('Course image');
$renderPageOptions = function ($selected) use ($pages) {
    $selected = (int) $selected;
    $html = '<option value="0"'.($selected === 0 ? ' selected' : '').'>'.htmlspecialchars(__("Don't show a page")).'</option>';
    foreach ( $pages as $page ) {
        if ( ! is_array($page) ) {
            continue;
        }
        $pid = (int) ($page['page_id'] ?? 0);
        if ( $pid < 1 ) {
            continue;
        }
        $title = trim((string) ($page['title'] ?? ''));
        if ( $title === '' ) {
            $title = __('Untitled');
        }
        if ( empty($page['published']) ) {
            $title .= ' ('.__('draft').')';
        }
        $html .= '<option value="'.$pid.'"'.($pid === $selected ? ' selected' : '').'>'.htmlspecialchars($title).'</option>';
    }
    return $html;
};
$renderFileControls = function ($path, $place, $radioName) use ($files) {
    $path = (string) $path;
    $place = in_array($place, array('left', 'right', 'center', 'inline'), true) ? $place : 'inline';
    $selectedImage = false;
    $options = '<option value="" data-image="0"'.($path === '' ? ' selected' : '').'>'.htmlspecialchars(__("Don't include a file")).'</option>';
    foreach ( $files as $file ) {
        if ( ! is_array($file) ) {
            continue;
        }
        $filePath = (string) ($file['path'] ?? '');
        if ( $filePath === '' ) {
            continue;
        }
        $image = ! empty($file['image']);
        if ( $filePath === $path && $image ) {
            $selectedImage = true;
        }
        $options .= '<option value="'.htmlspecialchars($filePath, ENT_QUOTES, 'UTF-8').'" data-image="'.($image ? '1' : '0').'"'.($filePath === $path ? ' selected' : '').'>'.htmlspecialchars($filePath).'</option>';
    }
    $choices = array(
        'left' => __('Float left'),
        'right' => __('Float right'),
        'center' => __('Center'),
        'inline' => __('Inline'),
    );
    $radios = '';
    foreach ( $choices as $value => $label ) {
        $radios .= '<label><input type="radio" class="tsugi-home-file-place-radio" name="'.htmlspecialchars($radioName, ENT_QUOTES, 'UTF-8').'" value="'.htmlspecialchars($value).'"'.($place === $value ? ' checked' : '').'> '.htmlspecialchars($label).'</label>';
    }
    return '<label>'.htmlspecialchars(__('File')).' <select name="file_path[]" class="form-control input-sm tsugi-home-file-select">'.$options.'</select></label>'
        .'<input type="hidden" name="file_place[]" class="tsugi-home-file-place-value" value="'.htmlspecialchars($place, ENT_QUOTES, 'UTF-8').'">'
        .'<fieldset class="tsugi-home-hero-choice tsugi-home-file-place"'.($selectedImage ? '' : ' hidden').'><legend>'.htmlspecialchars(__('Place image')).'</legend>'.$radios.'</fieldset>';
};
?>
<main class="container<?= $can_configure ? ' tsugi-home-has-gear' : '' ?>" role="main" id="main-content">
<style>
.tsugi-home-top { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 0.25em 0.75em; }
.tsugi-home-top > h1 { flex: 1 1 auto; margin-top: 0; }
.tsugi-home-config { flex: 0 0 auto; }
.tsugi-home-config[open] { flex: 1 1 100%; }
.tsugi-home-config[open] > .tsugi-home-gear {
    position: absolute;
    top: 0.2em;
    right: 15px;
}
.tsugi-home-has-gear { position: relative; }
.tsugi-home-gear {
    list-style: none;
    cursor: pointer;
    color: #666;
    font-size: 1.15em;
    line-height: 1;
    padding: 0.45em 0.15em;
}
.tsugi-home-gear::-webkit-details-marker { display: none; }
.tsugi-home-gear::marker { content: ""; }
.tsugi-home-hero-choice { border: 0; margin: 0; padding: 0; }
.tsugi-home-hero-choice legend { font-size: 1em; font-weight: 600; border: 0; float: none; width: auto; margin: 0 0 0.2em; }
.tsugi-home-hero-choice label { font-weight: normal; margin-right: 0.8em; }
.tsugi-home-gear:focus { outline: 2px solid #337ab7; outline-offset: 2px; }
.tsugi-home-config-panel {
    margin: 0 0 1.25em;
    padding: 0.85em 1em;
    border: 1px solid #ddd;
    background: #fafafa;
}
.tsugi-home-layout { width: 100%; max-width: 48em; }
.tsugi-home-layout th, .tsugi-home-layout td { padding: 0.45em 0.4em; vertical-align: middle; }
.tsugi-home-handle { cursor: move; color: #777; width: 1.2em; }
.tsugi-home-layout tr.tsugi-home-dragging { opacity: 0.5; }
.tsugi-home-hero { width: 400px; max-width: 100%; height: auto; }
.tsugi-home-hero-center { text-align: center; }
.tsugi-home-hero-center .tsugi-home-hero { display: inline-block; margin: 0 auto 1em; }
.tsugi-home-hero-left, .tsugi-home-hero-right { display: contents; }
.tsugi-home-hero-left .tsugi-home-hero { float: left; max-width: 46%; margin: 0 1.25em 1em 0; }
.tsugi-home-hero-right .tsugi-home-hero { float: right; max-width: 46%; margin: 0 0 1em 1.25em; }
.tsugi-home-file-img { max-width: 100%; height: auto; }
.tsugi-home-file-center { text-align: center; }
.tsugi-home-file-center .tsugi-home-file-img { display: inline-block; max-width: 400px; margin: 0 auto 1em; }
.tsugi-home-file-left, .tsugi-home-file-right { display: contents; }
.tsugi-home-file-left .tsugi-home-file-img { float: left; max-width: min(400px, 46%); margin: 0 1.25em 1em 0; }
.tsugi-home-file-right .tsugi-home-file-img { float: right; max-width: min(400px, 46%); margin: 0 0 1em 1.25em; }
.tsugi-home-file-inline .tsugi-home-file-img { display: inline-block; vertical-align: middle; }
.tsugi-home-clear { clear: both; }
.tsugi-home-block { margin: 0 0 1.5em; }
.tsugi-home-page-title { display: flex; justify-content: space-between; align-items: center; gap: 0.75em; }
</style>
<?php if ( $course_title !== '' || $can_configure ) { ?>
<div class="tsugi-home-top">
<?php if ( $course_title !== '' ) { ?>
<h1><?= htmlspecialchars($course_title) ?></h1>
<?php } else { ?>
<h1 class="sr-only"><?= __('Home') ?></h1>
<?php } ?>
<?php if ( $can_configure ) { ?>
<details class="tsugi-home-config">
    <summary class="tsugi-home-gear" aria-label="<?= htmlspecialchars(__('Configure home')) ?>">
        <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
    </summary>
    <form class="tsugi-home-config-panel" method="post" action="<?= htmlspecialchars($save_url) ?>">
        <?= \Tsugi\Controllers\Home::csrfField() ?>
        <p><?= __('Choose what appears on the home page. Drag a row, or use the arrows, to set the order.') ?></p>
        <table class="table table-condensed tsugi-home-layout" id="tsugi-home-layout">
            <tbody>
            <?php $fileRow = 0; foreach ( $blocks as $block ) {
                if ( ! is_array($block) ) {
                    continue;
                }
                $type = (string) ($block['type'] ?? '');
                if ( $type === '' ) {
                    continue;
                }
                $typeAttr = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
                ?>
            <tr draggable="true" data-home-type="<?= $typeAttr ?>">
                <td class="tsugi-home-handle" title="<?= htmlspecialchars(__('Drag to reorder')) ?>">&#8942;</td>
                <td>
                    <input type="hidden" name="home_order[]" value="<?= $typeAttr ?>">
                    <?php if ( $type === 'hero' ) {
                        $heroPlace = isset($block['place']) ? (string) $block['place'] : '';
                        if ( ! in_array($heroPlace, array('center', 'left', 'right', 'none'), true) ) {
                            $heroPlace = ! empty($block['on']) ? 'right' : 'none';
                        }
                        $heroChoices = array(
                            'center' => __('Center'),
                            'right' => __('Float right'),
                            'left' => __('Float left'),
                            'none' => __('No'),
                        );
                        ?>
                    <fieldset class="tsugi-home-hero-choice">
                        <legend><?= __('Include hero image') ?></legend>
                        <?php foreach ( $heroChoices as $value => $label ) { ?>
                        <label>
                            <input type="radio" name="hero" value="<?= htmlspecialchars($value) ?>"<?= $heroPlace === $value ? ' checked' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </label>
                        <?php } ?>
                    </fieldset>
                    <?php } elseif ( $type === 'page' ) {
                        $rowPage = (int) ($block['page_id'] ?? 0);
                        ?>
                    <label>
                        <?= __('Page') ?>
                        <select name="page_id[]" class="form-control input-sm">
                            <?= $renderPageOptions($rowPage) ?>
                        </select>
                    </label>
                    <?php } elseif ( $type === 'file' ) {
                        $filePath = isset($block['path']) ? (string) $block['path'] : '';
                        $filePlace = isset($block['place']) ? (string) $block['place'] : 'inline';
                        echo $renderFileControls($filePath, $filePlace, 'file_place_ui_'.$fileRow);
                        $fileRow++;
                    } elseif ( $type === 'calendar' ) { ?>
                    <label>
                        <input type="checkbox" name="show_calendar" value="1"<?= ! empty($block['on']) ? ' checked' : '' ?>>
                        <?= __('Show calendar') ?>
                    </label>
                    <span class="help-block"><?= __('Month view of assignment due dates.') ?></span>
                    <?php } elseif ( $type === 'dues' ) { ?>
                    <label>
                        <input type="checkbox" name="show_dues" value="1"<?= ! empty($block['on']) ? ' checked' : '' ?>>
                        <?= __('Show due date list') ?>
                    </label>
                    <span class="help-block"><?= __('A simple list of assignment due dates. The same list is on the calendar page.') ?></span>
                    <?php } ?>
                </td>
                <td>
                    <button type="button" class="btn btn-default btn-xs tsugi-home-up" aria-label="<?= htmlspecialchars(__('Move up')) ?>">&#9650;</button>
                    <button type="button" class="btn btn-default btn-xs tsugi-home-down" aria-label="<?= htmlspecialchars(__('Move down')) ?>">&#9660;</button>
                    <?php if ( $type === 'page' || $type === 'file' ) { ?>
                    <button type="button" class="btn btn-default btn-xs tsugi-home-remove" aria-label="<?= htmlspecialchars($type === 'file' ? __('Remove file') : __('Remove page')) ?>">&times;</button>
                    <?php } ?>
                </td>
            </tr>
            <?php } ?>
            </tbody>
        </table>
        <template id="tsugi-home-page-template">
            <tr draggable="true" data-home-type="page">
                <td class="tsugi-home-handle" title="<?= htmlspecialchars(__('Drag to reorder')) ?>">&#8942;</td>
                <td>
                    <input type="hidden" name="home_order[]" value="page">
                    <label>
                        <?= __('Page') ?>
                        <select name="page_id[]" class="form-control input-sm">
                            <?= $renderPageOptions(0) ?>
                        </select>
                    </label>
                </td>
                <td>
                    <button type="button" class="btn btn-default btn-xs tsugi-home-up" aria-label="<?= htmlspecialchars(__('Move up')) ?>">&#9650;</button>
                    <button type="button" class="btn btn-default btn-xs tsugi-home-down" aria-label="<?= htmlspecialchars(__('Move down')) ?>">&#9660;</button>
                    <button type="button" class="btn btn-default btn-xs tsugi-home-remove" aria-label="<?= htmlspecialchars(__('Remove page')) ?>">&times;</button>
                </td>
            </tr>
        </template>
        <template id="tsugi-home-file-template">
            <tr draggable="true" data-home-type="file">
                <td class="tsugi-home-handle" title="<?= htmlspecialchars(__('Drag to reorder')) ?>">&#8942;</td>
                <td>
                    <input type="hidden" name="home_order[]" value="file">
                    <?= $renderFileControls('', 'inline', 'file_place_ui_new') ?>
                </td>
                <td>
                    <button type="button" class="btn btn-default btn-xs tsugi-home-up" aria-label="<?= htmlspecialchars(__('Move up')) ?>">&#9650;</button>
                    <button type="button" class="btn btn-default btn-xs tsugi-home-down" aria-label="<?= htmlspecialchars(__('Move down')) ?>">&#9660;</button>
                    <button type="button" class="btn btn-default btn-xs tsugi-home-remove" aria-label="<?= htmlspecialchars(__('Remove file')) ?>">&times;</button>
                </td>
            </tr>
        </template>
        <p>
            <button type="button" class="btn btn-default" id="tsugi-home-add-page"><?= __('Add page') ?></button>
            <button type="button" class="btn btn-default" id="tsugi-home-add-file"><?= __('Add file') ?></button>
        </p>
        <button type="submit" class="btn btn-primary"><?= __('Save home page') ?></button>
    </form>
</details>
<?php } ?>
</div>
<?php } ?>
<script>
(function () {
    var table = document.getElementById('tsugi-home-layout');
    var tbody = table && table.querySelector('tbody');
    if (!tbody) return;
    function rowOf(node) {
        return node && node.closest ? node.closest('tr') : null;
    }
    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('button');
        var tr = rowOf(btn);
        if (!btn || !tr) return;
        if (btn.classList.contains('tsugi-home-remove')) {
            tr.remove();
            return;
        }
        if (btn.classList.contains('tsugi-home-up') && tr.previousElementSibling) {
            tbody.insertBefore(tr, tr.previousElementSibling);
        } else if (btn.classList.contains('tsugi-home-down') && tr.nextElementSibling) {
            tbody.insertBefore(tr.nextElementSibling, tr);
        }
    });
    var dragging = null;
    tbody.addEventListener('dragstart', function (e) {
        var tr = rowOf(e.target);
        if (!tr) return;
        dragging = tr;
        tr.classList.add('tsugi-home-dragging');
        e.dataTransfer.effectAllowed = 'move';
    });
    tbody.addEventListener('dragend', function () {
        if (dragging) dragging.classList.remove('tsugi-home-dragging');
        dragging = null;
    });
    tbody.addEventListener('dragover', function (e) {
        e.preventDefault();
        var tr = rowOf(e.target);
        if (!tr || tr === dragging || !dragging) return;
        var rect = tr.getBoundingClientRect();
        var next = (e.clientY - rect.top) > (rect.height / 2);
        tbody.insertBefore(dragging, next ? tr.nextSibling : tr);
    });
    var addPage = document.getElementById('tsugi-home-add-page');
    var pageTemplate = document.getElementById('tsugi-home-page-template');
    function insertAfterLast(type, tr, fragment) {
        var rows = tbody.querySelectorAll('tr[data-home-type="' + type + '"]');
        if (tr && rows.length) {
            rows[rows.length - 1].insertAdjacentElement('afterend', tr);
        } else {
            tbody.appendChild(fragment);
        }
    }
    if (addPage && pageTemplate) {
        addPage.addEventListener('click', function () {
            var row = pageTemplate.content.cloneNode(true);
            insertAfterLast('page', row.querySelector('tr'), row);
        });
    }
    var fileSeq = 0;
    function wireFileRow(tr, rename) {
        var select = tr.querySelector('.tsugi-home-file-select');
        var place = tr.querySelector('.tsugi-home-file-place');
        var hidden = tr.querySelector('.tsugi-home-file-place-value');
        var radios = tr.querySelectorAll('.tsugi-home-file-place-radio');
        if (!select || !place || !hidden) return;
        if (rename) {
            fileSeq++;
            var group = 'file_place_added_' + fileSeq;
            radios.forEach(function (radio) { radio.name = group; });
        }
        function chosen() {
            var checked = tr.querySelector('.tsugi-home-file-place-radio:checked');
            if (checked) hidden.value = checked.value;
        }
        function sync() {
            var opt = select.options[select.selectedIndex];
            place.hidden = !(opt && opt.getAttribute('data-image') === '1');
            chosen();
        }
        select.addEventListener('change', sync);
        radios.forEach(function (radio) {
            radio.addEventListener('change', chosen);
        });
        sync();
    }
    tbody.querySelectorAll('tr[data-home-type="file"]').forEach(function (tr) {
        wireFileRow(tr, false);
    });
    var addFile = document.getElementById('tsugi-home-add-file');
    var fileTemplate = document.getElementById('tsugi-home-file-template');
    if (addFile && fileTemplate) {
        addFile.addEventListener('click', function () {
            var row = fileTemplate.content.cloneNode(true);
            var tr = row.querySelector('tr');
            insertAfterLast('file', tr, row);
            if (tr) wireFileRow(tr, true);
        });
    }
})();
</script>
<?php
$shown = false;
foreach ( $blocks as $block ) {
    if ( ! is_array($block) ) {
        continue;
    }
    $type = (string) ($block['type'] ?? '');
    $heroPlace = isset($block['place']) ? (string) $block['place'] : '';
    if ( $type === 'hero' && ! in_array($heroPlace, array('center', 'left', 'right', 'none'), true) ) {
        $heroPlace = ! empty($block['on']) ? 'right' : 'none';
    }
    if ( $type === 'hero' && $heroPlace !== 'none' && $hero_url !== '' ) {
        $shown = true;
        ?>
<section class="tsugi-home-block tsugi-home-hero-<?= htmlspecialchars($heroPlace) ?>" aria-label="<?= htmlspecialchars(__('Course image')) ?>">
    <img class="tsugi-home-hero" src="<?= htmlspecialchars($hero_url) ?>" alt="<?= htmlspecialchars($hero_alt) ?>">
</section>
        <?php
    } elseif ( $type === 'hero' && $heroPlace !== 'none' && $hero_url === '' && $can_configure ) {
        $shown = true;
        ?>
<section class="tsugi-home-block">
    <p class="text-muted"><?= __('Include hero image is on, but this course does not have a hero image yet.') ?></p>
</section>
        <?php
    } elseif ( $type === 'page' ) {
        $pid = (int) ($block['page_id'] ?? 0);
        $page_block = ($pid > 0 && isset($page_blocks[$pid]) && is_array($page_blocks[$pid])) ? $page_blocks[$pid] : null;
        if ( ! $page_block ) {
            continue;
        }
        $shown = true;
        $page_title = isset($page_block['title']) ? trim((string) $page_block['title']) : '';
        $body_html = isset($page_block['body_html']) ? (string) $page_block['body_html'] : '';
        $published = ! empty($page_block['published']);
        $edit_url = isset($page_block['edit_url']) ? (string) $page_block['edit_url'] : '';
        if ( ! class_exists('\Tsugi\UI\CKEditor', false) ) {
            require_once __DIR__ . '/../../../UI/CKEditor.php';
        }
        ?>
<section class="tsugi-home-block">
    <style>
    <?php \Tsugi\UI\CKEditor::renderStyles(['includeLinkPicker' => false]); ?>
    </style>
    <?php if ( $page_title !== '' || $edit_url !== '' ) { ?>
    <h2 class="tsugi-home-page-title">
        <span><?= htmlspecialchars($page_title) ?></span>
        <?php if ( $edit_url !== '' ) { ?>
        <a href="<?= htmlspecialchars($edit_url) ?>" class="btn btn-primary" aria-label="<?= htmlspecialchars(__('Edit')) ?> <?= htmlspecialchars($page_title) ?>"><?= __('Edit') ?></a>
        <?php } ?>
    </h2>
    <?php } ?>
    <?php if ( ! $published ) { ?>
    <div class="alert alert-warning" role="status">
        <p><?= __('This page is not published. Students do not see it on the home page.') ?></p>
    </div>
    <?php } ?>
    <div class="page-content">
        <?= $body_html ?>
    </div>
</section>
        <?php
    } elseif ( $type === 'file' ) {
        $path = isset($block['path']) ? (string) $block['path'] : '';
        $file = ($path !== '' && isset($file_blocks[$path]) && is_array($file_blocks[$path])) ? $file_blocks[$path] : null;
        if ( ! $file || empty($file['url']) ) {
            continue;
        }
        $shown = true;
        $fileName = isset($file['name']) ? (string) $file['name'] : $path;
        $fileUrl = (string) $file['url'];
        $filePlace = isset($block['place']) ? (string) $block['place'] : 'inline';
        if ( ! in_array($filePlace, array('left', 'right', 'center', 'inline'), true) ) {
            $filePlace = 'inline';
        }
        if ( ! empty($file['image']) ) {
            ?>
<section class="tsugi-home-block tsugi-home-file-<?= htmlspecialchars($filePlace) ?>">
    <img class="tsugi-home-file-img" src="<?= htmlspecialchars($fileUrl) ?>" alt="<?= htmlspecialchars($fileName) ?>">
</section>
            <?php
        } else {
            ?>
<section class="tsugi-home-block">
    <p><a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($fileName) ?></a></p>
</section>
            <?php
        }
    } elseif ( $type === 'calendar' && ! empty($block['on']) && $calendar_html !== '' ) {
        $shown = true;
        echo '<section class="tsugi-home-block" aria-label="'.htmlspecialchars(__('Calendar')).'">'.$calendar_html.'</section>';
    } elseif ( $type === 'dues' && ! empty($block['on']) && $dues_html !== '' ) {
        $shown = true;
        echo '<section class="tsugi-home-block" aria-label="'.htmlspecialchars(__('Due dates')).'">'.$dues_html.'</section>';
    }
}
if ( ! $shown ) { ?>
    <p><?= __('This home page feature is under construction.') ?></p>
    <p><?= __('TODO: Write Home Controller') ?></p>
    <p><?= __('Eventually this page will hold a set of widgets and gadgets for the course. The first, and most important, will tell you exactly what to do next based on your current activity, progress, and due dates.') ?></p>
<?php } ?>
<div class="tsugi-home-clear"></div>
</main>

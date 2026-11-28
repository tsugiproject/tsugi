<?php
/**
 * Course navigation editor.
 *
 * Expected: $nav_rows (CourseNav::editorRows), $save_url,
 * $nav_pages_json_url, $nav_lessons_json_url, $nav_files_json_url.
 * A missing picker URL is an empty string, and that destination list stays empty.
 */
?>
<p><?= __('Home is always in the upper left. You can rename the Home label. The avatar menu is always on the right. Student View is always in the avatar menu before Settings, and Settings is always before Logout. Both are instructors only.') ?></p>
<p><?= __('Check where each tool should appear. Instructor only hides that item from students. Widgets can only sit in the top left or top right, not under the avatar.') ?></p>
<p><?= __('Lessons stays under Home. Students see one link: the default view, or an available view they pick on Home. Only one view can be the default. If none is marked default, Card View is the default. Check Instructor test on a view to add a test link in your avatar menu. Students do not see test links.') ?></p>
<p><?= __('Add nav link opens an item editor. Choose a module, a web link, a discussion, an LTI tool, a quiz, a page, or a file. A quiz, an LTI tool, or a discussion has to already be in the lesson. A web link or an LTI tool can open in a new window or in a modal. Then put the link on the left, the right, or in the avatar menu, and reorder it with the other links.') ?></p>
<style>
.tsugi-nav-editor { width: 100%; max-width: 56em; border-collapse: separate; border-spacing: 0; }
.tsugi-nav-editor th, .tsugi-nav-editor td { padding: 0.4em 0.6em; vertical-align: middle; }
.tsugi-nav-editor thead th {
    position: sticky;
    top: var(--tsugi-nav-sticky-top, 50px);
    z-index: 4;
    background: var(--background-color, #fff);
    background-clip: padding-box;
    box-shadow: 0 1px 0 var(--primary-border, #ddd);
}
.tsugi-nav-editor .tsugi-nav-handle { cursor: move; color: #777; }
.tsugi-nav-editor tr.tsugi-nav-dragging { opacity: 0.5; }
.tsugi-nav-editor tr.tsugi-nav-pinned .tsugi-nav-handle { visibility: hidden; cursor: default; }
.tsugi-nav-hint { color: #666; font-size: 0.9em; margin: 0.2em 0 0; }
.tsugi-nav-link-lesson { color: #8a6d3b; }
.tsugi-nav-link-lesson[hidden] { display: none; }
.tsugi-nav-home-label { display: inline-block; width: 14em; max-width: 100%; }
.tsugi-nav-link-label { display: inline-block; width: 14em; max-width: 100%; }
.tsugi-nav-link-actions { margin-top: 0.35em; }
.tsugi-nav-link-actions .btn { margin-right: 0.35em; }
.tsugi-nav-item-modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
.tsugi-nav-item-modal.open { display: block; }
.tsugi-nav-item-dialog { background: #fff; margin: 5% auto; padding: 20px; border-radius: 4px; width: 90%; max-width: 680px; max-height: 80vh; overflow-y: auto; }
.tsugi-nav-item-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #ddd; }
.tsugi-nav-item-header h2 { margin: 0; font-size: 1.25em; }
.tsugi-nav-item-close { font-size: 28px; font-weight: bold; line-height: 1; cursor: pointer; color: #aaa; background: transparent; border: 0; }
.tsugi-nav-item-dialog .form-group { margin-bottom: 12px; }
.tsugi-nav-item-dialog .form-group > label { display: block; }
.tsugi-nav-item-dialog input[type="text"],
.tsugi-nav-item-dialog input[type="url"],
.tsugi-nav-item-dialog select,
.tsugi-nav-item-dialog textarea { width: 100%; max-width: 100%; box-sizing: border-box; }
.tsugi-nav-item-dialog .form-group-radios label { display: inline-block; margin-right: 0.9em; font-weight: normal; }
.tsugi-nav-item-note { color: #666; font-size: 0.9em; }
.tsugi-nav-item-error { color: #a94442; min-height: 1.2em; margin: 0.4em 0 0; }
.tsugi-nav-item-dialog .form-actions .btn { margin-right: 0.4em; }
.tsugi-nav-steps-label {
    display: inline-flex;
    align-items: center;
    gap: 0.45em;
    margin: 0;
    font-weight: normal;
    cursor: pointer;
}
.tsugi-nav-steps-label input {
    margin: 0;
}
.tsugi-nav-editor label { font-weight: normal; margin: 0; cursor: pointer; }
.tsugi-nav-editor tr.tsugi-nav-lessons td { border-bottom: 0; padding-bottom: 0.15em; }
.tsugi-nav-editor tr.tsugi-nav-lessons-views td { border-top: 0; padding-top: 0; }
.tsugi-nav-editor tr.tsugi-nav-lessons-views .tsugi-nav-view-list { margin-top: 0; }
.tsugi-nav-view-list { margin-top: 0.7em; }
.tsugi-nav-steps[hidden] { display: none; }
.tsugi-nav-view-row { margin: 0.35em 0; }
.tsugi-nav-view-name { display: inline-block; min-width: 8em; }
.tsugi-nav-view-row label { font-weight: normal; margin-right: 0.8em; }
.tsugi-nav-editor th.tsugi-nav-move-cell,
.tsugi-nav-move-cell { white-space: nowrap; text-align: right; }
.tsugi-nav-move { position: relative; display: inline-block; }
.tsugi-nav-move-btn .caret { margin-left: 0.35em; }
.tsugi-nav-move-menu {
    position: absolute;
    right: 0;
    top: 100%;
    z-index: 30;
    min-width: 11em;
    margin: 0.15em 0 0;
    padding: 0.25em 0;
    list-style: none;
    background: var(--background-color, #fff);
    border: 1px solid var(--primary-border, #ddd);
    border-radius: 4px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
}
.tsugi-nav-move-menu[hidden] { display: none; }
.tsugi-nav-move-menu-up { top: auto; bottom: 100%; margin: 0 0 0.15em; }
.tsugi-nav-move-menu button {
    display: block;
    width: 100%;
    margin: 0;
    padding: 0.35em 0.8em;
    border: 0;
    background: transparent;
    color: inherit;
    font-weight: normal;
    text-align: left;
    white-space: nowrap;
}
.tsugi-nav-move-menu button:hover,
.tsugi-nav-move-menu button:focus {
    background: var(--primary, #0a4b33);
    color: #fff;
    outline: none;
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
.save-bar.hidden { display: none; }
.save-bar .message { flex: 1; }
.save-bar .actions { display: flex; gap: 10px; }
body.tsugi-nav-dirty { padding-bottom: 72px; }
html:has(body.tsugi-nav-dirty) { scroll-padding-bottom: 96px; }
body.tsugi-nav-dirty input,
body.tsugi-nav-dirty button,
body.tsugi-nav-dirty a,
body.tsugi-nav-dirty select,
body.tsugi-nav-dirty textarea { scroll-margin-bottom: 96px; }
</style>
<form method="post" action="<?= htmlspecialchars($save_url) ?>">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <table class="table table-condensed tsugi-nav-editor" id="tsugi-nav-editor">
        <thead>
            <tr>
                <th scope="col"><span class="sr-only"><?= __('Drag') ?></span></th>
                <th scope="col"><?= __('Tool') ?></th>
                <th scope="col"><?= __('Upper left') ?></th>
                <th scope="col"><?= __('Upper right') ?></th>
                <th scope="col"><?= __('Avatar dropdown') ?></th>
                <th scope="col"><?= __('Instructor only') ?></th>
                <th scope="col" class="tsugi-nav-move-cell"><?= __('Reorder') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php
        $view_by_id = array();
        foreach ( $nav_rows as $view_row ) {
            $view_id = $view_row['id'] ?? '';
            if ( $view_id === 'lessons' || $view_id === 'content' || $view_id === 'outline' ) {
                $view_by_id[$view_id] = $view_row;
            }
        }
        $view_place = array('left' => false, 'right' => false, 'dropdown' => false);
        $view_placed = array();
        foreach ( array('lessons', 'content', 'outline') as $view_id ) {
            $view_row = $view_by_id[$view_id] ?? array();
            $view_on = ! empty($view_row['left']) || ! empty($view_row['right']) || ! empty($view_row['dropdown']);
            if ( ! $view_on ) {
                continue;
            }
            $view_placed[] = $view_row;
            foreach ( array('left', 'right', 'dropdown') as $place_key ) {
                if ( ! empty($view_row[$place_key]) ) {
                    $view_place[$place_key] = true;
                }
            }
        }
        $view_instructor = count($view_placed) > 0;
        foreach ( $view_placed as $view_row ) {
            if ( empty($view_row['instructor']) ) {
                $view_instructor = false;
                break;
            }
        }
        $view_any = false;
        $view_has_default = false;
        $view_default_id = '';
        foreach ( array('lessons', 'content', 'outline') as $view_id ) {
            $view_row = $view_by_id[$view_id] ?? array();
            $view_on = ! empty($view_row['left']) || ! empty($view_row['right']) || ! empty($view_row['dropdown']);
            $view_explicit = array_key_exists('default', $view_row) || array_key_exists('available', $view_row) || array_key_exists('test', $view_row);
            $view_default_on = $view_on && ($view_explicit ? ! empty($view_row['default']) : true);
            if ( $view_default_on ) {
                $view_has_default = true;
                if ( $view_default_id === '' ) {
                    $view_default_id = $view_id;
                }
            }
            if ( $view_on && ($view_explicit ? ( ! empty($view_row['default']) || ! empty($view_row['available']) || ! empty($view_row['test']) ) : true) ) {
                $view_any = true;
            }
        }
        $card_fallback = ! $view_has_default && count($view_placed) > 0;
        $placeLeft = __('Upper left');
        $placeRight = __('Upper right');
        $placeDrop = __('Avatar dropdown');
        $placeInst = __('Instructor only');
        $navCheck = static function ($name, $on, $text, $class = '') {
            $cls = $class !== '' ? ' class="'.htmlspecialchars($class, ENT_QUOTES, 'UTF-8').'"' : '';
            return '<label class="tsugi-nav-check">'
                .'<input type="checkbox" name="'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'" value="1"'.$cls.($on ? ' checked' : '').'>'
                .'<span class="sr-only">'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</span>'
                .'</label>';
        };
        $navCheckShown = static function ($name, $on, $prefix, $visible) {
            return '<label>'
                .'<input type="checkbox" name="'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'" value="1"'.($on ? ' checked' : '').'>'
                .'<span class="sr-only">'.htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8').', </span>'
                .htmlspecialchars($visible, ENT_QUOTES, 'UTF-8')
                .'</label>';
        };
        $renderNavLinkRow = static function (array $row) use ($navCheck, $placeLeft, $placeRight, $placeDrop, $placeInst) {
            $id = htmlspecialchars((string) ($row['id'] ?? ''), ENT_QUOTES, 'UTF-8');
            $label = isset($row['label']) && is_string($row['label']) ? $row['label'] : '';
            $summary = isset($row['target_summary']) && is_string($row['target_summary']) ? $row['target_summary'] : '';
            $lessonNote = isset($row['lesson_note']) && is_string($row['lesson_note']) ? $row['lesson_note'] : '';
            $target = (isset($row['target']) && is_array($row['target'])) ? $row['target'] : null;
            $lessonKey = '';
            if ( is_array($target) ) {
                $lessonKind = isset($target['kind']) ? (string) $target['kind'] : '';
                if ( $lessonKind === 'quiz' ) {
                    $lessonKey = 'quiz:'.(int) ($target['quiz_id'] ?? 0);
                } else if ( $lessonKind === 'lti' || $lessonKind === 'discussion' ) {
                    $lessonKey = $lessonKind.':'.(string) ($target['resource_link_id'] ?? '');
                }
            }
            $targetJson = $target === null ? '' : json_encode($target, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ( ! is_string($targetJson) ) {
                $targetJson = '';
            }
            $toolLabel = $label !== '' ? $label : __('Nav link');
            $moveId = 'tsugi-nav-move-'.$id;
            $moveBtnId = 'tsugi-nav-move-btn-'.$id;
            ?>
            <tr draggable="true" data-nav-id="<?= $id ?>">
                <td class="tsugi-nav-handle" title="<?= htmlspecialchars(__('Drag to reorder')) ?>">&#8942;</td>
                <td>
                    <input type="hidden" name="nav_order[]" value="<?= $id ?>">
                    <input type="text" name="nav_label[<?= $id ?>]" value="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>" maxlength="40" placeholder="<?= htmlspecialchars(__('Nav link'), ENT_QUOTES, 'UTF-8') ?>" class="form-control input-sm tsugi-nav-link-label" aria-label="<?= htmlspecialchars(__('Nav link label'), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="nav_target[<?= $id ?>]" value="<?= htmlspecialchars($targetJson, ENT_QUOTES, 'UTF-8') ?>" class="tsugi-nav-link-target-input">
                    <p class="tsugi-nav-hint tsugi-nav-link-target"><?= $summary !== '' ? htmlspecialchars($summary) : htmlspecialchars(__('No destination yet.')) ?></p>
                    <p class="tsugi-nav-hint tsugi-nav-link-lesson" data-lesson-key="<?= htmlspecialchars($lessonKey, ENT_QUOTES, 'UTF-8') ?>"<?= $lessonNote === '' ? ' hidden' : '' ?>><?= htmlspecialchars($lessonNote) ?></p>
                    <div class="tsugi-nav-link-actions">
                        <button type="button" class="btn btn-default btn-xs tsugi-nav-link-edit" aria-label="<?= htmlspecialchars(__('Edit').' '.$toolLabel, ENT_QUOTES, 'UTF-8') ?>"><?= __('Edit') ?></button>
                        <button type="button" class="btn btn-link btn-xs tsugi-nav-link-remove"><?= __('Remove link') ?></button>
                    </div>
                </td>
                <td><?= $navCheck('nav['.$id.'][left]', ! empty($row['left']), $toolLabel.', '.$placeLeft) ?></td>
                <td><?= $navCheck('nav['.$id.'][right]', ! empty($row['right']), $toolLabel.', '.$placeRight) ?></td>
                <td><?= $navCheck('nav['.$id.'][dropdown]', ! empty($row['dropdown']), $toolLabel.', '.$placeDrop) ?></td>
                <td><?= $navCheck('nav['.$id.'][instructor]', ! empty($row['instructor']), $toolLabel.', '.$placeInst) ?></td>
                <td class="tsugi-nav-move-cell">
                    <div class="tsugi-nav-move">
                        <button type="button" id="<?= $moveBtnId ?>" class="btn btn-default btn-xs tsugi-nav-move-btn" aria-haspopup="menu" aria-expanded="false" aria-controls="<?= $moveId ?>" aria-label="<?= htmlspecialchars(__('Reorder').' '.$toolLabel, ENT_QUOTES, 'UTF-8') ?>" data-nav-label="<?= htmlspecialchars($toolLabel, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('Reorder')) ?> <span class="caret" aria-hidden="true"></span></button>
                        <ul id="<?= $moveId ?>" class="tsugi-nav-move-menu" role="menu" hidden aria-labelledby="<?= $moveBtnId ?>"></ul>
                    </div>
                </td>
            </tr>
            <?php
        };
        foreach ( $nav_rows as $row ) {
            if ( ($row['kind'] ?? '') === 'navlink' ) {
                $renderNavLinkRow($row);
                continue;
            }
            unset($toolLabel);
            $row_id = $row['id'] ?? '';
            if ( $row_id === 'lessons' || $row_id === 'content' || $row_id === 'outline' ) {
                continue;
            }
            $id = htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8');
            $widget = ($row['kind'] ?? '') === 'widget';
            $chrome = ($row['kind'] ?? '') === 'chrome';
            $pinned = ! empty($row['pin']);
            ?>
            <tr<?= $pinned ? '' : ' draggable="true"' ?> data-nav-id="<?= $id ?>" class="<?= $pinned ? 'tsugi-nav-pinned' : '' ?>">
                <td class="tsugi-nav-handle" title="<?= htmlspecialchars($pinned ? __('Stays in place') : __('Drag to reorder')) ?>">&#8942;</td>
                <td>
                    <?php if ( ! $chrome ) { ?>
                    <input type="hidden" name="nav_order[]" value="<?= $id ?>">
                    <?php } ?>
                    <?php if ( ! empty($row['rename']) ) {
                        $homeValue = ! empty($row['custom']) ? (string) $row['label'] : '';
                        ?>
                    <input type="text" name="home" value="<?= htmlspecialchars($homeValue, ENT_QUOTES, 'UTF-8') ?>" maxlength="40" placeholder="<?= htmlspecialchars(__('Home'), ENT_QUOTES, 'UTF-8') ?>" class="form-control input-sm tsugi-nav-home-label" aria-label="<?= htmlspecialchars(__('Home label'), ENT_QUOTES, 'UTF-8') ?>">
                    <?php } else {
                        $shown = (isset($row['settings_label']) && is_string($row['settings_label']) && $row['settings_label'] !== '')
                            ? $row['settings_label']
                            : $row['label'];
                        $toolLabel = __($shown);
                        ?>
                    <?= htmlspecialchars($toolLabel) ?>
                    <?php } ?>
                    <?php if ( ! empty($row['hint']) && is_string($row['hint']) ) { ?>
                        <p class="tsugi-nav-hint"><?= htmlspecialchars(__($row['hint'])) ?></p>
                    <?php } ?>
                </td>
                <?php if ( $chrome ) { ?>
                <td>—</td>
                <td>—</td>
                <td>—</td>
                <td>—</td>
                <?php } else { ?>
                <td><?= $navCheck('nav['.$id.'][left]', ! empty($row['left']), $toolLabel.', '.$placeLeft) ?></td>
                <td><?= $navCheck('nav['.$id.'][right]', ! empty($row['right']), $toolLabel.', '.$placeRight) ?></td>
                <td>
                    <?php if ( $widget ) { ?>
                        —
                    <?php } else { ?>
                        <?= $navCheck('nav['.$id.'][dropdown]', ! empty($row['dropdown']), $toolLabel.', '.$placeDrop) ?>
                    <?php } ?>
                </td>
                <td>
                    <?php if ( ! empty($row['instructor_locked']) ) { ?>
                        —
                    <?php } else { ?>
                        <?= $navCheck('nav['.$id.'][instructor]', ! empty($row['instructor']), $toolLabel.', '.$placeInst) ?>
                    <?php } ?>
                </td>
                <?php } ?>
                <td class="tsugi-nav-move-cell">
                    <?php if ( ! $pinned && isset($toolLabel) ) {
                        $moveId = 'tsugi-nav-move-'.$id;
                        $moveBtnId = 'tsugi-nav-move-btn-'.$id;
                        ?>
                    <div class="tsugi-nav-move">
                        <button type="button" id="<?= $moveBtnId ?>" class="btn btn-default btn-xs tsugi-nav-move-btn" aria-haspopup="menu" aria-expanded="false" aria-controls="<?= $moveId ?>" aria-label="<?= htmlspecialchars(__('Reorder').' '.$toolLabel, ENT_QUOTES, 'UTF-8') ?>" data-nav-label="<?= htmlspecialchars($toolLabel, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('Reorder')) ?> <span class="caret" aria-hidden="true"></span></button>
                        <ul id="<?= $moveId ?>" class="tsugi-nav-move-menu" role="menu" hidden aria-labelledby="<?= $moveBtnId ?>"></ul>
                    </div>
                    <?php } ?>
                </td>
            </tr>
            <?php if ( $row_id === 'home' ) {
                if ( ! isset($steps_on) ) {
                    $steps_on = true;
                }
                ?>
            <?php $lessonsLabel = __('Lessons'); ?>
            <tr class="tsugi-nav-pinned tsugi-nav-lessons" data-nav-id="course_views">
                <td class="tsugi-nav-handle" title="<?= htmlspecialchars(__('Stays under Home')) ?>">&#8942;</td>
                <td>
                    <input type="hidden" name="nav_order[]" value="course_views">
                    <?= htmlspecialchars($lessonsLabel) ?>
                </td>
                <td><?= $navCheck('nav[course_views][left]', ! empty($view_place['left']), $lessonsLabel.', '.$placeLeft) ?></td>
                <td><?= $navCheck('nav[course_views][right]', ! empty($view_place['right']), $lessonsLabel.', '.$placeRight) ?></td>
                <td><?= $navCheck('nav[course_views][dropdown]', ! empty($view_place['dropdown']), $lessonsLabel.', '.$placeDrop) ?></td>
                <td><?= $navCheck('nav[course_views][instructor]', $view_instructor, $lessonsLabel.', '.$placeInst) ?></td>
                <td class="tsugi-nav-move-cell"></td>
            </tr>
            <tr class="tsugi-nav-pinned tsugi-nav-lessons-views">
                <td class="tsugi-nav-handle"></td>
                <td>
                    <div class="tsugi-nav-view-list">
                    <?php foreach ( array('lessons', 'content', 'outline') as $view_id ) {
                        $view_row = $view_by_id[$view_id] ?? array();
                        $view_on = ! empty($view_row['left']) || ! empty($view_row['right']) || ! empty($view_row['dropdown']);
                        $view_explicit = array_key_exists('default', $view_row) || array_key_exists('available', $view_row) || array_key_exists('test', $view_row);
                        $view_default = ($view_id === $view_default_id)
                            || ($view_id === 'lessons' && $card_fallback);
                        $view_available = $view_on && ($view_explicit ? ! empty($view_row['available']) : true);
                        $view_test = $view_on && ! empty($view_row['test']);
                        $view_name = (isset($view_row['settings_label']) && is_string($view_row['settings_label']) && $view_row['settings_label'] !== '')
                            ? $view_row['settings_label']
                            : ($view_row['label'] ?? $view_id);
                        $viewTitle = __($view_name);
                        ?>
                    <div class="tsugi-nav-view-row">
                        <span class="tsugi-nav-view-name"><?= htmlspecialchars($viewTitle) ?></span>
                        <?= $navCheckShown('view_default['.$view_id.']', $view_default, $viewTitle, __('Default')) ?>
                        <?= $navCheckShown('view_available['.$view_id.']', $view_available, $viewTitle, __('Available')) ?>
                        <?= $navCheckShown('view_test['.$view_id.']', $view_test, $viewTitle, __('Instructor test')) ?>
                    </div>
                    <?php } ?>
                    <div class="tsugi-nav-steps" data-nav-child="course_views"<?= $view_any ? '' : ' hidden' ?>>
                        <label class="tsugi-nav-steps-label">
                            <?= __('Show content progress checkboxes') ?>
                            <input type="hidden" name="steps" value="0">
                            <input type="checkbox" name="steps" value="1"<?= ! empty($steps_on) ? ' checked' : '' ?>>
                        </label>
                    </div>
                    </div>
                </td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <?php } ?>
        <?php } ?>
        </tbody>
    </table>
    <div id="tsugi-nav-move-status" class="sr-only" role="status" aria-live="polite" aria-atomic="true"></div>
    <p>
        <button type="button" class="btn btn-default" id="tsugi-nav-add"><?= __('Add nav link') ?></button>
        <button type="submit" class="btn btn-primary"><?= __('Save navigation') ?></button>
        <a href="<?= htmlspecialchars($save_url) ?>" class="btn btn-default"><?= __('Cancel') ?></a>
    </p>
</form>
<div id="tsugi-nav-save-bar" class="save-bar hidden">
    <div class="message"><?= __('You have unsaved changes. Navigation must be saved.') ?></div>
    <div class="actions">
        <button type="button" class="btn btn-success" id="tsugi-nav-save-bar-save"><?= __('Save navigation') ?></button>
        <button type="button" class="btn" id="tsugi-nav-save-bar-discard"><?= __('Discard') ?></button>
    </div>
</div>
<template id="tsugi-nav-link-template">
<?php
$renderNavLinkRow(array(
    'id' => 'navlink:TEMPLATE',
    'kind' => 'navlink',
    'label' => '',
    'target' => null,
    'target_summary' => '',
    'left' => false,
    'right' => false,
    'dropdown' => false,
    'instructor' => false,
));
?>
</template>
<div id="tsugi-nav-item-modal" class="tsugi-nav-item-modal">
    <div class="tsugi-nav-item-dialog" role="dialog" aria-modal="true" aria-labelledby="tsugi-nav-item-title">
        <div class="tsugi-nav-item-header">
            <h2 id="tsugi-nav-item-title"><?= __('Add link') ?></h2>
            <button type="button" class="tsugi-nav-item-close" id="tsugi-nav-item-close" aria-label="<?= htmlspecialchars(__('Close')) ?>">&times;</button>
        </div>
        <div id="tsugi-nav-item-body"></div>
    </div>
</div>
<?php
// TODO: Keyboard reorder for Lessons authoring and Discussions reorder.
$navPicker = array(
    'pages' => isset($nav_pages_json_url) ? (string) $nav_pages_json_url : '',
    'lessons' => isset($nav_lessons_json_url) ? (string) $nav_lessons_json_url : '',
    'files' => isset($nav_files_json_url) ? (string) $nav_files_json_url : '',
    'empty' => __('No destination yet.'),
    'failed' => __('Could not load this course.'),
    'addLabel' => __('Nav link'),
    'reorder' => __('Reorder'),
    'edit' => __('Edit'),
    'addTitle' => __('Add link'),
    'editTitle' => __('Edit link'),
    'badUrl' => __('Use an http or https address.'),
    'needChoice' => __('Pick one from this course.'),
    'needTitle' => __('A title is required.'),
    'type' => __('Type'),
    'title' => __('Title'),
    'url' => __('URL'),
    'open' => __('Open'),
    'newWindow' => __('New window'),
    'modal' => __('Modal'),
    'module' => __('Module'),
    'page' => __('Page'),
    'file' => __('File'),
    'quiz' => __('Quiz'),
    'save' => __('Save'),
    'mustSave' => __('Navigation must be saved.'),
    'missingDestination' => __('Not in the lesson'),
    'tooMany' => __('A course can have at most 24 navigation links.'),
    'cancel' => __('Cancel'),
    'loading' => __('Loading…'),
    'kinds' => array(
        'module' => __('Module'),
        'href' => __('Web link'),
        'discussion' => __('Discussion'),
        'lti' => __('LTI link'),
        'quiz' => __('Quiz'),
        'page' => __('Page'),
        'file' => __('File'),
    ),
    'types' => array(
        array('id' => 'module', 'label' => __('Module')),
        array('id' => 'web_link', 'label' => __('Web link')),
        array('id' => 'discussion', 'label' => __('Discussion')),
        array('id' => 'lti', 'label' => __('LTI link')),
        array('id' => 'quiz', 'label' => __('Quiz')),
        array('id' => 'file', 'label' => __('File')),
        array('id' => 'html_page', 'label' => __('Page')),
    ),
);
?>
<script>
(function () {
    var table = document.querySelector('#tsugi-nav-editor');
    var tbody = table && table.querySelector('tbody');
    if (!tbody) return;

    function stickUnderNavbar() {
        var nav = document.getElementById('tsugi_main_nav_bar');
        var top = 50;
        if (nav && nav.offsetHeight > 0) {
            top = nav.offsetHeight;
        }
        table.style.setProperty('--tsugi-nav-sticky-top', top + 'px');
    }
    stickUnderNavbar();
    window.addEventListener('resize', stickUnderNavbar);

    var form = table.closest('form');
    var saveBar = document.getElementById('tsugi-nav-save-bar');
    var saveBarSave = document.getElementById('tsugi-nav-save-bar-save');
    var saveBarDiscard = document.getElementById('tsugi-nav-save-bar-discard');
    var navDirty = false;
    var leaveWarning = <?= json_encode(__('You have unsaved changes. Navigation must be saved.'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var discardWarning = <?= json_encode(__('Are you sure you want to discard all unsaved changes?'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;

    function markChanged() {
        if (navDirty) return;
        navDirty = true;
        if (saveBar) saveBar.classList.remove('hidden');
        document.body.classList.add('tsugi-nav-dirty');
    }
    window.tsugiNavMarkChanged = markChanged;

    if (form) {
        form.addEventListener('input', markChanged);
        form.addEventListener('change', markChanged);
        form.addEventListener('submit', function () {
            navDirty = false;
        });
    }
    if (saveBarSave && form) {
        saveBarSave.addEventListener('click', function () {
            navDirty = false;
            if (form.requestSubmit) form.requestSubmit();
            else form.submit();
        });
    }
    if (saveBarDiscard) {
        saveBarDiscard.addEventListener('click', function () {
            if (!window.confirm(discardWarning)) return;
            navDirty = false;
            window.location.reload();
        });
    }
    window.addEventListener('beforeunload', function (e) {
        if (!navDirty) return;
        e.preventDefault();
        e.returnValue = leaveWarning;
        return leaveWarning;
    });

    var dragging = null;
    tbody.addEventListener('dragstart', function (e) {
        if (e.target.closest && e.target.closest('.tsugi-nav-move, input, button, a, label')) {
            e.preventDefault();
            return;
        }
        closeMenu(false);
        var tr = e.target.closest('tr');
        if (!tr || tr.classList.contains('tsugi-nav-pinned')) {
            e.preventDefault();
            return;
        }
        dragging = tr;
        tr.classList.add('tsugi-nav-dragging');
        e.dataTransfer.effectAllowed = 'move';
    });
    tbody.addEventListener('dragend', function () {
        if (dragging) dragging.classList.remove('tsugi-nav-dragging');
        dragging = null;
    });
    tbody.addEventListener('dragover', function (e) {
        e.preventDefault();
        var tr = e.target.closest('tr');
        if (!tr || tr === dragging || !dragging) return;
        if (tr.classList.contains('tsugi-nav-pinned')) return;
        var rect = tr.getBoundingClientRect();
        var next = (e.clientY - rect.top) > (rect.height / 2);
        tbody.insertBefore(dragging, next ? tr.nextSibling : tr);
        markChanged();
    });

    function syncProgressBoxes() {
        var steps = tbody.querySelector('[data-nav-child="course_views"]');
        if (!steps) return;
        var on = false;
        tbody.querySelectorAll('input[name^="view_default["], input[name^="view_available["], input[name^="view_test["]').forEach(function (box) {
            if (box.checked) on = true;
        });
        if (on) steps.removeAttribute('hidden');
        else steps.setAttribute('hidden', 'hidden');
    }

    tbody.addEventListener('change', function (e) {
        var name = e.target && e.target.name ? e.target.name : '';
        if (name.indexOf('view_default[') === 0 && e.target.checked) {
            tbody.querySelectorAll('input[name^="view_default["]').forEach(function (box) {
                if (box !== e.target) box.checked = false;
            });
        }
        if (name.indexOf('view_default[') === 0 || name.indexOf('view_available[') === 0 || name.indexOf('view_test[') === 0) {
            syncProgressBoxes();
        }
    });
    syncProgressBoxes();

    var moveText = <?= json_encode(array(
        'up' => __('Move up'),
        'down' => __('Move down'),
        'top' => __('Move to top'),
        'bottom' => __('Move to bottom'),
        'didUp' => __('moved up'),
        'didDown' => __('moved down'),
        'didTop' => __('moved to top'),
        'didBottom' => __('moved to bottom'),
        'status' => __('%s, %s, position %d of %d'),
    ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var moveStatus = document.getElementById('tsugi-nav-move-status');
    var openMenu = null;
    var openButton = null;

    function movableRows() {
        return Array.prototype.filter.call(tbody.querySelectorAll('tr'), function (tr) {
            return !tr.classList.contains('tsugi-nav-pinned');
        });
    }

    function syncMoveButtons() {
        var rows = movableRows();
        rows.forEach(function (row) {
            var wrap = row.querySelector('.tsugi-nav-move');
            if (wrap) wrap.hidden = rows.length < 2;
        });
    }

    function fillStatus(label, phrase, index, total) {
        var i = 0;
        var values = [label, phrase, index, total];
        return moveText.status.replace(/%[sd]/g, function () {
            var value = values[i++];
            return value === undefined ? '' : String(value);
        });
    }

    function announce(row, action, index, total) {
        if (!moveStatus) return;
        var button = row.querySelector('.tsugi-nav-move-btn');
        var label = button ? button.getAttribute('data-nav-label') : '';
        var phrase = moveText[action === 'up' ? 'didUp' : action === 'down' ? 'didDown' : action === 'top' ? 'didTop' : 'didBottom'];
        var text = fillStatus(label, phrase, index + 1, total);
        moveStatus.textContent = '';
        window.setTimeout(function () { moveStatus.textContent = text; }, 80);
    }

    function closeMenu(focusButton) {
        if (!openMenu) return;
        openMenu.hidden = true;
        openMenu.classList.remove('tsugi-nav-move-menu-up');
        openMenu.innerHTML = '';
        if (openButton) openButton.setAttribute('aria-expanded', 'false');
        var button = openButton;
        openMenu = null;
        openButton = null;
        if (focusButton && button) button.focus();
    }

    function actionsFor(index, total) {
        var actions = [];
        if (index > 0) actions.push({ id: 'up', label: moveText.up, phrase: 'up' });
        if (index < total - 1) actions.push({ id: 'down', label: moveText.down, phrase: 'down' });
        if (index > 0) actions.push({ id: 'top', label: moveText.top, phrase: 'top' });
        if (index < total - 1) actions.push({ id: 'bottom', label: moveText.bottom, phrase: 'bottom' });
        return actions;
    }

    function placeRow(row, action) {
        var rows = movableRows();
        var index = rows.indexOf(row);
        var total = rows.length;
        if (index < 0) return;
        if (action === 'up' && index > 0) {
            tbody.insertBefore(row, rows[index - 1]);
        } else if (action === 'down' && index < total - 1) {
            tbody.insertBefore(row, rows[index + 1].nextSibling);
        } else if (action === 'top' && index > 0) {
            tbody.insertBefore(row, rows[0]);
        } else if (action === 'bottom' && index < total - 1) {
            tbody.insertBefore(row, rows[total - 1].nextSibling);
        } else {
            return;
        }
        markChanged();
        var next = movableRows();
        var pos = next.indexOf(row);
        var button = row.querySelector('.tsugi-nav-move-btn');
        if (button) button.focus();
        announce(row, action, pos, next.length);
    }

    function menuItems() {
        if (!openMenu) return [];
        return Array.prototype.slice.call(openMenu.querySelectorAll('[role="menuitem"]'));
    }

    function openMoveMenu(button, focusLast) {
        closeMenu(false);
        var row = button.closest('tr');
        var rows = movableRows();
        var actions = actionsFor(rows.indexOf(row), rows.length);
        if (!actions.length) return;
        var menu = document.getElementById(button.getAttribute('aria-controls'));
        if (!menu) return;
        menu.innerHTML = '';
        actions.forEach(function (action) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var item = document.createElement('button');
            item.type = 'button';
            item.setAttribute('role', 'menuitem');
            item.tabIndex = -1;
            item.dataset.action = action.phrase;
            item.textContent = action.label;
            li.appendChild(item);
            menu.appendChild(li);
        });
        menu.hidden = false;
        menu.classList.remove('tsugi-nav-move-menu-up');
        var menuRect = menu.getBoundingClientRect();
        var buttonRect = button.getBoundingClientRect();
        if (menuRect.bottom > window.innerHeight && menuRect.height < buttonRect.top) {
            menu.classList.add('tsugi-nav-move-menu-up');
        }
        button.setAttribute('aria-expanded', 'true');
        openMenu = menu;
        openButton = button;
        var items = menuItems();
        var target = focusLast ? items[items.length - 1] : items[0];
        if (target) target.focus();
    }

    function activateItem(item) {
        if (!item || !openButton) return;
        var row = openButton.closest('tr');
        var action = item.dataset.action;
        closeMenu(false);
        placeRow(row, action);
    }

    function tabbables() {
        var form = table.closest('form');
        return Array.prototype.filter.call(form.querySelectorAll('a[href], button, input, select, textarea'), function (el) {
            if (el.disabled || el.type === 'hidden' || el.tabIndex < 0) return false;
            if (el.closest('[hidden]')) return false;
            return true;
        });
    }

    tbody.addEventListener('click', function (e) {
        var item = e.target.closest('[role="menuitem"]');
        if (item) {
            activateItem(item);
            return;
        }
        var button = e.target.closest('.tsugi-nav-move-btn');
        if (!button) return;
        if (openButton === button) closeMenu(true);
        else openMoveMenu(button, false);
    });

    tbody.addEventListener('keydown', function (e) {
        var button = e.target.closest('.tsugi-nav-move-btn');
        if (!button || openMenu) return;
        if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            e.stopPropagation();
            openMoveMenu(button, false);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            e.stopPropagation();
            openMoveMenu(button, true);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (!openMenu) return;
        var items = menuItems();
        var current = items.indexOf(document.activeElement);
        if (e.key === 'Escape') {
            e.preventDefault();
            closeMenu(true);
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            items[(current + 1) % items.length].focus();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            items[(current - 1 + items.length) % items.length].focus();
        } else if (e.key === 'Home') {
            e.preventDefault();
            items[0].focus();
        } else if (e.key === 'End') {
            e.preventDefault();
            items[items.length - 1].focus();
        } else if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            activateItem(document.activeElement);
        } else if (e.key === 'Tab') {
            e.preventDefault();
            var button = openButton;
            var backwards = e.shiftKey;
            closeMenu(false);
            var stops = tabbables();
            var index = stops.indexOf(button);
            var next = stops[index + (backwards ? -1 : 1)];
            if (next) next.focus();
        }
    });

    document.addEventListener('mousedown', function (e) {
        if (!openMenu) return;
        if (openMenu.contains(e.target) || (openButton && openButton.contains(e.target))) return;
        closeMenu(false);
    });

    syncMoveButtons();
})();
</script>
<script>
(function () {
    var cfg = <?= json_encode($navPicker, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var tbody = document.querySelector('#tsugi-nav-editor tbody');
    var addButton = document.getElementById('tsugi-nav-add');
    var template = document.getElementById('tsugi-nav-link-template');
    var modal = document.getElementById('tsugi-nav-item-modal');
    var modalBody = document.getElementById('tsugi-nav-item-body');
    var modalTitle = document.getElementById('tsugi-nav-item-title');
    var modalClose = document.getElementById('tsugi-nav-item-close');
    if (!tbody || !addButton || !template || !modal || !modalBody) return;

    var editingRow = null;
    var loaded = null;

    var tailIds = {
        courses_widget: true,
        roster: true,
        student: true,
        settings: true,
        login: true,
        exit_course: true,
        leave_course: true,
        logout: true
    };

    function movableRows() {
        return Array.prototype.filter.call(tbody.querySelectorAll('tr'), function (tr) {
            return !tr.classList.contains('tsugi-nav-pinned');
        });
    }

    function syncMoves() {
        var rows = movableRows();
        rows.forEach(function (row) {
            var wrap = row.querySelector('.tsugi-nav-move');
            if (wrap) wrap.hidden = rows.length < 2;
        });
    }

    function newId() {
        var bytes = new Uint8Array(4);
        if (window.crypto && window.crypto.getRandomValues) {
            window.crypto.getRandomValues(bytes);
        } else {
            for (var i = 0; i < bytes.length; i++) bytes[i] = Math.floor(Math.random() * 256);
        }
        var hex = Array.prototype.map.call(bytes, function (b) {
            return ('0' + b.toString(16)).slice(-2);
        }).join('');
        var id = 'navlink:' + hex;
        if (tbody.querySelector('tr[data-nav-id="' + id + '"]')) return newId();
        return id;
    }

    function insertRow(row) {
        var rows = tbody.querySelectorAll('tr');
        var anchor = null;
        for (var i = 0; i < rows.length; i++) {
            var id = rows[i].getAttribute('data-nav-id');
            if (id && tailIds[id]) {
                anchor = rows[i];
                break;
            }
        }
        if (anchor) tbody.insertBefore(row, anchor);
        else tbody.appendChild(row);
    }

    function syncLabel(row) {
        var input = row.querySelector('.tsugi-nav-link-label');
        var button = row.querySelector('.tsugi-nav-move-btn');
        if (!input || !button) return;
        var label = input.value.trim() || cfg.addLabel;
        button.setAttribute('data-nav-label', label);
        button.setAttribute('aria-label', cfg.reorder + ' ' + label);
        var edit = row.querySelector('.tsugi-nav-link-edit');
        if (edit) edit.setAttribute('aria-label', cfg.edit + ' ' + label);
    }

    function lessonIdentity(target) {
        if (!target) return '';
        if (target.kind === 'quiz') return 'quiz:' + (parseInt(target.quiz_id, 10) || 0);
        if (target.kind === 'lti' || target.kind === 'discussion') return target.kind + ':' + (target.resource_link_id || '');
        return '';
    }

    function summaryText(target) {
        if (!target || !target.kind) return cfg.empty;
        var name = (cfg.kinds && cfg.kinds[target.kind]) || target.kind;
        if (target.owned) return name;
        var detail = target.anchor || target.logical_key || target.path || target.href || target.resource_link_id || (target.quiz_id ? String(target.quiz_id) : '');
        return detail ? name + ' · ' + detail : name;
    }

    function applyTarget(row, target) {
        var input = row.querySelector('.tsugi-nav-link-label');
        var hidden = row.querySelector('.tsugi-nav-link-target-input');
        var summary = row.querySelector('.tsugi-nav-link-target');
        if (hidden) hidden.value = JSON.stringify(target);
        if (summary) summary.textContent = summaryText(target);
        var lessonNote = row.querySelector('.tsugi-nav-link-lesson');
        if (lessonNote) {
            var nextKey = lessonIdentity(target);
            if (lessonNote.getAttribute('data-lesson-key') !== nextKey) {
                lessonNote.textContent = '';
                lessonNote.hidden = true;
                lessonNote.setAttribute('data-lesson-key', nextKey);
            }
        }
        if (input) {
            var title = (target.title || '').trim();
            if (title) input.value = title.slice(0, 40);
            input.removeAttribute('data-label-auto');
        }
        syncLabel(row);
        if (window.tsugiNavMarkChanged) window.tsugiNavMarkChanged();
    }

    function esc(value) {
        return String(value || '').replace(/[&<>"']/g, function (ch) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
        });
    }

    function typeFromTarget(target) {
        var kind = target && target.kind;
        if (kind === 'href') return 'web_link';
        if (kind === 'page') return 'html_page';
        if (kind === 'module' || kind === 'discussion' || kind === 'lti' || kind === 'quiz' || kind === 'file') return kind;
        return 'web_link';
    }

    function field(id) {
        return document.getElementById(id);
    }

    function checkedValue(name, fallback) {
        var el = modalBody.querySelector('input[name="' + name + '"]:checked');
        return el ? el.value : fallback;
    }

    function openFields(open) {
        var modalOn = open === 'modal';
        return '<div class="form-group"><label>' + esc(cfg.open) + '</label><div class="form-group-radios">'
            + '<label><input type="radio" name="tsugi-nav-open" value="blank"' + (modalOn ? '' : ' checked') + '> ' + esc(cfg.newWindow) + '</label>'
            + '<label><input type="radio" name="tsugi-nav-open" value="modal"' + (modalOn ? ' checked' : '') + '> ' + esc(cfg.modal) + '</label>'
            + '</div></div>';
    }

    function renderForm(target) {
        var type = typeFromTarget(target);
        var title = target && target.title ? target.title : '';
        var options = (cfg.types || []).map(function (row) {
            return '<option value="' + esc(row.id) + '"' + (row.id === type ? ' selected' : '') + '>' + esc(row.label) + '</option>';
        }).join('');
        var extra = '';
        if (type === 'module') {
            extra = '<div class="form-group"><label for="tsugi-nav-item-module">' + esc(cfg.module) + '</label><select id="tsugi-nav-item-module" class="form-control"><option value="">' + esc(cfg.loading) + '</option></select></div>';
        } else if (type === 'web_link') {
            extra = '<div class="form-group"><label for="tsugi-nav-item-href">' + esc(cfg.url) + '</label><input type="url" id="tsugi-nav-item-href" class="form-control" placeholder="https://" value="' + esc(target.href || '') + '"></div>' + openFields(target.open);
        } else if (type === 'discussion') {
            extra = '<div class="form-group"><label for="tsugi-nav-item-discussion">' + esc(cfg.kinds.discussion) + '</label><select id="tsugi-nav-item-discussion" class="form-control"><option value="">' + esc(cfg.loading) + '</option></select></div>';
        } else if (type === 'lti') {
            extra = '<div class="form-group"><label for="tsugi-nav-item-lti">' + esc(cfg.kinds.lti) + '</label><select id="tsugi-nav-item-lti" class="form-control"><option value="">' + esc(cfg.loading) + '</option></select></div>'
                + openFields(target.open);
        } else if (type === 'quiz') {
            extra = '<div class="form-group"><label for="tsugi-nav-item-quiz">' + esc(cfg.quiz) + '</label><select id="tsugi-nav-item-quiz" class="form-control"><option value="">' + esc(cfg.loading) + '</option></select></div>';
        } else if (type === 'file') {
            extra = '<div class="form-group"><label for="tsugi-nav-item-file">' + esc(cfg.file) + '</label><select id="tsugi-nav-item-file" class="form-control"><option value="">' + esc(cfg.loading) + '</option></select></div>';
        } else if (type === 'html_page') {
            extra = '<div class="form-group"><label for="tsugi-nav-item-page">' + esc(cfg.page) + '</label><select id="tsugi-nav-item-page" class="form-control"><option value="">' + esc(cfg.loading) + '</option></select></div>';
        }
        modalBody.innerHTML = '<div class="form-group"><label for="tsugi-nav-item-type">' + esc(cfg.type) + '</label><select id="tsugi-nav-item-type" class="form-control">' + options + '</select></div>'
            + '<div class="form-group"><label for="tsugi-nav-item-title-input">' + esc(cfg.title) + '</label><input type="text" id="tsugi-nav-item-title-input" class="form-control" maxlength="40" value="' + esc(title) + '"></div>'
            + extra
            + '<p class="tsugi-nav-item-error" id="tsugi-nav-item-error" role="alert"></p>'
            + (editingRow ? '<p class="tsugi-nav-item-note">' + esc(cfg.mustSave) + '</p>' : '')
            + '<div class="form-actions"><button type="button" class="btn btn-primary" id="tsugi-nav-item-save">' + esc(cfg.save) + '</button><button type="button" class="btn btn-default" id="tsugi-nav-item-cancel">' + esc(cfg.cancel) + '</button></div>';
        field('tsugi-nav-item-type').addEventListener('change', function () {
            var titleNow = field('tsugi-nav-item-title-input');
            var typeNow = field('tsugi-nav-item-type').value;
            renderForm({ kind: kindFromType(typeNow), title: titleNow ? titleNow.value : '' });
            var typeField = field('tsugi-nav-item-type');
            if (typeField) {
                typeField.value = typeNow;
                typeField.focus();
            }
        });
        field('tsugi-nav-item-save').addEventListener('click', saveEditor);
        field('tsugi-nav-item-cancel').addEventListener('click', function () { closeEditor(true); });
        fillSelects(type, target || {});
    }

    function kindFromType(type) {
        if (type === 'web_link') return 'href';
        if (type === 'html_page') return 'page';
        return type;
    }

    function fillSelect(select, rows, selected, labelOf, valueOf) {
        if (!select) return;
        select.innerHTML = '';
        var found = false;
        rows.forEach(function (row) {
            var option = document.createElement('option');
            option.value = valueOf(row);
            option.textContent = labelOf(row);
            if (selected && option.value === selected) {
                option.selected = true;
                found = true;
            }
            select.appendChild(option);
        });
        if (selected && !found) {
            var missing = document.createElement('option');
            missing.value = selected;
            missing.textContent = selected + ' — ' + cfg.missingDestination;
            missing.selected = true;
            select.insertBefore(missing, select.firstChild);
            return;
        }
        if (!rows.length) {
            var empty = document.createElement('option');
            empty.value = '';
            empty.textContent = cfg.needChoice;
            select.appendChild(empty);
        }
    }

    function fillSelects(type, target) {
        if (type !== 'module' && type !== 'discussion' && type !== 'lti' && type !== 'quiz' && type !== 'file' && type !== 'html_page') return;
        var typeField = field('tsugi-nav-item-type');
        ensureData().then(function (data) {
            if (!modal.classList.contains('open')) return;
            if (field('tsugi-nav-item-type') !== typeField) return;
            if (!data) {
                var error = field('tsugi-nav-item-error');
                if (error) error.textContent = cfg.failed;
                return;
            }
            var endpointFailed = data.failed && (
                ((type === 'module' || type === 'discussion' || type === 'lti' || type === 'quiz') && data.failed.lessons)
                || (type === 'html_page' && data.failed.pages)
                || (type === 'file' && data.failed.files)
            );
            if (endpointFailed) {
                var failed = field('tsugi-nav-item-error');
                if (failed) failed.textContent = cfg.failed;
                return;
            }
            if (type === 'module') {
                var modules = (data.modules || []).filter(function (mod) { return mod.anchor; });
                fillSelect(field('tsugi-nav-item-module'), modules, target.anchor || '', function (mod) {
                    return mod.title || mod.anchor;
                }, function (mod) { return mod.anchor; });
            } else if (type === 'html_page') {
                var pages = (data.pages || []).filter(function (page) { return page.logical_key; });
                fillSelect(field('tsugi-nav-item-page'), pages, target.logical_key || '', function (page) {
                    return page.title || page.logical_key;
                }, function (page) { return page.logical_key; });
            } else if (type === 'file') {
                var files = (data.files || []).filter(function (file) { return file.path; });
                fillSelect(field('tsugi-nav-item-file'), files, target.path || '', function (file) {
                    return file.title || file.path;
                }, function (file) { return file.path; });
            } else if (type === 'discussion') {
                var discussions = [];
                (data.items || []).forEach(function (item) {
                    var parsed = targetFromLesson(item);
                    if (parsed && parsed.kind === 'discussion') discussions.push(parsed);
                });
                fillSelect(field('tsugi-nav-item-discussion'), discussions, target.resource_link_id || '', function (row) {
                    return row.title || row.resource_link_id;
                }, function (row) { return row.resource_link_id || ''; });
            } else if (type === 'quiz') {
                var quizzes = [];
                (data.items || []).forEach(function (item) {
                    var parsed = targetFromLesson(item);
                    if (parsed && parsed.kind === 'quiz') quizzes.push(parsed);
                });
                fillSelect(field('tsugi-nav-item-quiz'), quizzes, target.quiz_id ? String(target.quiz_id) : '', function (row) {
                    return row.title || String(row.quiz_id);
                }, function (row) { return String(row.quiz_id); });
            } else if (type === 'lti') {
                var tools = [];
                (data.items || []).forEach(function (item) {
                    var parsed = targetFromLesson(item);
                    if (parsed && parsed.kind === 'lti') tools.push(parsed);
                });
                (data.launches || []).forEach(function (item) {
                    var parsed = targetFromLesson(item);
                    if (parsed && parsed.kind === 'lti') tools.push(parsed);
                });
                var chosen = target.resource_link_id
                    ? target.resource_link_id + '|' + (target.launch === 'top' ? 'top' : 'lesson')
                    : '';
                var rows = tools.map(function (tool) {
                    return {
                        key: tool.resource_link_id + '|' + (tool.launch === 'top' ? 'top' : 'lesson'),
                        title: tool.title || tool.resource_link_id
                    };
                });
                fillSelect(field('tsugi-nav-item-lti'), rows, chosen, function (row) { return row.title; }, function (row) { return row.key; });
            }
        });
    }

    function showError(message) {
        var error = field('tsugi-nav-item-error');
        if (error) error.textContent = message || '';
    }

    function httpUrl(value) {
        var url = String(value || '').trim();
        if (!/^https?:\/\//i.test(url) || /[\s<>"']/.test(url)) return '';
        try {
            var parsed = new URL(url);
            if ((parsed.protocol !== 'http:' && parsed.protocol !== 'https:') || !parsed.hostname) return '';
        } catch (e) {
            return '';
        }
        return url;
    }

    function readEditor() {
        var type = field('tsugi-nav-item-type').value;
        var title = field('tsugi-nav-item-title-input').value.trim();
        if (!title) return { error: cfg.needTitle };
        var target = { title: title };
        if (type === 'web_link') {
            var href = httpUrl(field('tsugi-nav-item-href').value);
            if (!href) return { error: cfg.badUrl };
            target.kind = 'href';
            target.href = href;
            target.open = checkedValue('tsugi-nav-open', 'blank');
            return { target: target };
        }
        if (type === 'module') {
            var anchor = field('tsugi-nav-item-module').value;
            if (!anchor) return { error: cfg.needChoice };
            target.kind = 'module';
            target.anchor = anchor;
            return { target: target };
        }
        if (type === 'html_page') {
            var key = field('tsugi-nav-item-page').value;
            if (!key) return { error: cfg.needChoice };
            target.kind = 'page';
            target.logical_key = key;
            return { target: target };
        }
        if (type === 'file') {
            var path = field('tsugi-nav-item-file').value;
            if (!path) return { error: cfg.needChoice };
            target.kind = 'file';
            target.path = path;
            return { target: target };
        }
        if (type === 'discussion') {
            var existing = field('tsugi-nav-item-discussion').value;
            if (!existing) return { error: cfg.needChoice };
            target.kind = 'discussion';
            target.resource_link_id = existing;
            return { target: target };
        }
        if (type === 'quiz') {
            var quizId = parseInt(field('tsugi-nav-item-quiz').value, 10) || 0;
            if (!quizId) return { error: cfg.needChoice };
            target.kind = 'quiz';
            target.quiz_id = quizId;
            return { target: target };
        }
        var tool = field('tsugi-nav-item-lti').value;
        if (!tool) return { error: cfg.needChoice };
        var parts = tool.split('|');
        target.kind = 'lti';
        target.resource_link_id = parts[0];
        target.open = checkedValue('tsugi-nav-open', 'blank');
        if (parts[1] === 'top') target.launch = 'top';
        return { target: target };
    }

    function makeRow() {
        var id = newId();
        var holder = document.createElement('tbody');
        holder.innerHTML = template.innerHTML.split('navlink:TEMPLATE').join(id);
        return holder.querySelector('tr');
    }

    function saveEditor() {
        var read = readEditor();
        if (!read || read.error) {
            showError(read ? read.error : cfg.needChoice);
            return;
        }
        var row = editingRow;
        if (!row && navLinkCount() >= 24) {
            showError(cfg.tooMany);
            return;
        }
        if (!row) {
            row = makeRow();
            if (!row) return;
            insertRow(row);
            editingRow = row;
        }
        applyTarget(row, read.target);
        syncMoves();
        closeEditor(true);
    }

    function rowTarget(row) {
        var hidden = row.querySelector('.tsugi-nav-link-target-input');
        if (!hidden || !hidden.value) return {};
        try { return JSON.parse(hidden.value) || {}; } catch (e) { return {}; }
    }

    function navLinkCount() {
        return tbody.querySelectorAll('tr[data-nav-id^="navlink:"]').length;
    }

    function setBackgroundInert(dialog, on) {
        var node = dialog;
        while (node && node.parentElement && node.parentElement !== document.documentElement) {
            var parent = node.parentElement;
            var kids = parent.children;
            var i;
            for (i = 0; i < kids.length; i++) {
                if (kids[i] === node) continue;
                if (on) {
                    if (kids[i].getAttribute('data-tsugi-nav-inert') === '1') continue;
                    kids[i].setAttribute('data-tsugi-nav-inert', '1');
                    kids[i].setAttribute('inert', '');
                } else if (kids[i].getAttribute('data-tsugi-nav-inert') === '1') {
                    kids[i].removeAttribute('inert');
                    kids[i].removeAttribute('data-tsugi-nav-inert');
                }
            }
            if (parent === document.body) break;
            node = parent;
        }
    }

    function trapTab(container, event) {
        if (event.key !== 'Tab' || !container) return;
        var nodes = container.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled])');
        var list = [];
        var i;
        for (i = 0; i < nodes.length; i++) {
            if (nodes[i].closest('[hidden]')) continue;
            list.push(nodes[i]);
        }
        if (!list.length) return;
        var first = list[0];
        var last = list[list.length - 1];
        var active = document.activeElement;
        if (event.shiftKey && (active === first || !container.contains(active))) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && (active === last || !container.contains(active))) {
            event.preventDefault();
            first.focus();
        }
    }

    function openEditor(row) {
        if (!row && navLinkCount() >= 24) {
            window.alert(cfg.tooMany);
            return;
        }
        editingRow = row || null;
        var target = row ? rowTarget(row) : { kind: 'href' };
        if (row) {
            var input = row.querySelector('.tsugi-nav-link-label');
            if (input && input.value.trim()) target.title = input.value.trim();
        }
        modalTitle.textContent = row ? cfg.editTitle : cfg.addTitle;
        renderForm(target);
        modal.classList.add('open');
        setBackgroundInert(modal, true);
        var first = modal.querySelector('select, input');
        if (first) first.focus();
    }

    function closeEditor(focusReturn) {
        modal.classList.remove('open');
        setBackgroundInert(modal, false);
        var focus = editingRow ? editingRow.querySelector('.tsugi-nav-link-edit') : addButton;
        editingRow = null;
        if (focusReturn && focus) focus.focus();
    }

    addButton.addEventListener('click', function () {
        openEditor(null);
    });

    tbody.addEventListener('input', function (e) {
        var input = e.target.closest ? e.target.closest('.tsugi-nav-link-label') : null;
        if (!input) return;
        input.removeAttribute('data-label-auto');
        var row = input.closest('tr');
        if (row) syncLabel(row);
    });

    tbody.addEventListener('click', function (e) {
        var remove = e.target.closest ? e.target.closest('.tsugi-nav-link-remove') : null;
        if (remove) {
            var row = remove.closest('tr');
            if (row) row.remove();
            syncMoves();
            if (window.tsugiNavMarkChanged) window.tsugiNavMarkChanged();
            addButton.focus();
            return;
        }
        var edit = e.target.closest ? e.target.closest('.tsugi-nav-link-edit') : null;
        if (!edit) return;
        openEditor(edit.closest('tr'));
    });

    function fetchJson(url) {
        if (!url) return Promise.resolve(null);
        return fetch(url, { credentials: 'same-origin' }).then(function (r) {
            if (!r.ok) throw new Error('load');
            return r.json();
        });
    }

    function settleJson(url) {
        return fetchJson(url).then(function (value) {
            return { ok: true, value: value };
        }, function () {
            return { ok: false, value: null };
        });
    }

    function ensureData() {
        if (loaded) return loaded;
        loaded = Promise.all([
            settleJson(cfg.pages),
            settleJson(cfg.lessons),
            settleJson(cfg.files)
        ]).then(function (results) {
            if (!results[0].ok && !results[1].ok && !results[2].ok) {
                loaded = null;
                return null;
            }
            var lessons = results[1].ok && results[1].value ? results[1].value : {};
            return {
                pages: results[0].ok && Array.isArray(results[0].value) ? results[0].value : [],
                items: lessons.items || [],
                modules: lessons.modules || [],
                launches: lessons.launches || [],
                files: results[2].ok && Array.isArray(results[2].value) ? results[2].value : [],
                failed: {
                    pages: !results[0].ok,
                    lessons: !results[1].ok,
                    files: !results[2].ok
                }
            };
        });
        return loaded;
    }

    function segmentAfter(url, marker) {
        var path = String(url || '').split('?')[0].split('#')[0];
        var at = path.indexOf(marker);
        if (at < 0) return '';
        var rest = path.slice(at + marker.length).replace(/^\/+/, '');
        var seg = rest.split('/')[0];
        if (!seg) return '';
        try { return decodeURIComponent(seg); } catch (e) { return ''; }
    }

    function pathAfter(url, marker) {
        var path = String(url || '').split('?')[0].split('#')[0];
        var at = path.indexOf(marker);
        if (at < 0) return '';
        var rest = path.slice(at + marker.length).replace(/^\/+/, '');
        if (!rest || rest.indexOf('..') !== -1) return '';
        var parts = rest.split('/');
        try {
            parts = parts.map(function (part) { return decodeURIComponent(part); });
        } catch (e) {
            return '';
        }
        for (var i = 0; i < parts.length; i++) {
            if (parts[i] === '' || parts[i] === '.' || parts[i] === '..') return '';
        }
        return parts.join('/');
    }

    function hrefTarget(item) {
        var url = String(item.url || '');
        if (!/^https?:\/\//i.test(url)) return null;
        return { kind: 'href', href: url, title: item.title || '' };
    }

    function ltiTarget(item) {
        var url = item.url || '';
        var fromLessons = segmentAfter(url, '/lessons_launch/');
        if (fromLessons) return { kind: 'lti', resource_link_id: fromLessons, title: item.title || '' };
        var fromTop = segmentAfter(url, '/launch/');
        if (fromTop) return { kind: 'lti', resource_link_id: fromTop, launch: 'top', title: item.title || '' };
        if (item.resource_link_id) {
            return { kind: 'lti', resource_link_id: String(item.resource_link_id), launch: 'top', title: item.title || '' };
        }
        return hrefTarget(item);
    }

    function targetFromLesson(item) {
        var type = item.type || '';
        if (type === 'not-lti') type = 'lti';
        if (type === 'quiz1' || (type === 'quiz' && item.quiz_id)) {
            var quizId = parseInt(item.quiz_id, 10) || 0;
            if (!quizId) return null;
            return { kind: 'quiz', quiz_id: quizId, title: item.title || '' };
        }
        if (type === 'discussion') {
            var id = segmentAfter(item.url, '/discussions/') || segmentAfter(item.url, '/lessons_launch/');
            if (!id) return hrefTarget(item);
            return { kind: 'discussion', resource_link_id: id, title: item.title || '' };
        }
        if (type === 'lti' || type === 'quiz' || type === 'autograder' || type === 'peer_grade') {
            return ltiTarget(item);
        }
        var pageKey = segmentAfter(item.url, '/pages/');
        if (pageKey) return { kind: 'page', logical_key: pageKey, title: item.title || '' };
        var filePath = pathAfter(item.url, '/files/');
        if (filePath && filePath.indexOf('download/') !== 0) {
            return { kind: 'file', path: filePath, title: item.title || '' };
        }
        return hrefTarget(item);
    }

    modalClose.addEventListener('click', function () { closeEditor(true); });
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeEditor(true);
    });
    document.addEventListener('keydown', function (e) {
        if (!modal.classList.contains('open')) return;
        if (e.key === 'Tab') {
            trapTab(modal, e);
            return;
        }
        if (e.key !== 'Escape') return;
        e.preventDefault();
        closeEditor(true);
    });
})();
</script>

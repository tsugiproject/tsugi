<?php
/**
 * Course navigation editor.
 *
 * Expected: $nav_rows (CourseNav::editorRows), $save_url
 */
?>
<p><?= __('Home is always in the upper left. You can rename the Home label. The avatar menu is always on the right. Student View is always in the avatar menu before Settings, and Settings is always before Logout. Both are instructors only.') ?></p>
<p><?= __('Check where each tool should appear. Instructor only hides that item from students. Widgets can only sit in the top left or top right, not under the avatar.') ?></p>
<p><?= __('Lessons stays under Home. Students see one link: the default view, or an available view they pick on Home. Only one view can be the default. If none is marked default, Card View is the default. Check Instructor test on a view to add a test link in your avatar menu. Students do not see test links.') ?></p>
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
.tsugi-nav-home-label { display: inline-block; width: 14em; max-width: 100%; }
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
        foreach ( $nav_rows as $row ) {
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
        <button type="submit" class="btn btn-primary"><?= __('Save navigation') ?></button>
        <a href="<?= htmlspecialchars($save_url) ?>" class="btn btn-default"><?= __('Cancel') ?></a>
    </p>
</form>
<?php
// TODO: Keyboard reorder for Lessons authoring and Discussions reorder.
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

    var dragging = null;
    tbody.addEventListener('dragstart', function (e) {
        if (e.target.closest && e.target.closest('.tsugi-nav-move')) {
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

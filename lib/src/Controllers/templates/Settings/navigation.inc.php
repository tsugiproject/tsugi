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
.tsugi-nav-editor tr.tsugi-nav-lessons td { vertical-align: top; }
.tsugi-nav-editor tr.tsugi-nav-lessons .tsugi-nav-place { margin-top: 0.45em; }
.tsugi-nav-view-list { margin-top: 0.7em; }
.tsugi-nav-steps[hidden] { display: none; }
.tsugi-nav-view-row { margin: 0.35em 0; }
.tsugi-nav-view-name { display: inline-block; min-width: 8em; }
.tsugi-nav-view-row label { font-weight: normal; margin-right: 0.8em; }
</style>
<form method="post" action="<?= htmlspecialchars($save_url) ?>">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <table class="table table-condensed tsugi-nav-editor" id="tsugi-nav-editor">
        <thead>
            <tr>
                <th></th>
                <th><?= __('Tool') ?></th>
                <th><?= __('Upper left') ?></th>
                <th><?= __('Upper right') ?></th>
                <th><?= __('Avatar dropdown') ?></th>
                <th><?= __('Instructor only') ?></th>
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
        foreach ( $nav_rows as $row ) {
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
                        ?>
                    <?= htmlspecialchars(__($shown)) ?>
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
                <td><input type="checkbox" name="nav[<?= $id ?>][left]" value="1"<?= ! empty($row['left']) ? ' checked' : '' ?>></td>
                <td><input type="checkbox" name="nav[<?= $id ?>][right]" value="1"<?= ! empty($row['right']) ? ' checked' : '' ?>></td>
                <td>
                    <?php if ( $widget ) { ?>
                        —
                    <?php } else { ?>
                        <input type="checkbox" name="nav[<?= $id ?>][dropdown]" value="1"<?= ! empty($row['dropdown']) ? ' checked' : '' ?>>
                    <?php } ?>
                </td>
                <td>
                    <?php if ( ! empty($row['instructor_locked']) ) { ?>
                        —
                    <?php } else { ?>
                        <input type="checkbox" name="nav[<?= $id ?>][instructor]" value="1"<?= ! empty($row['instructor']) ? ' checked' : '' ?>>
                    <?php } ?>
                </td>
                <?php } ?>
            </tr>
            <?php if ( $row_id === 'home' ) {
                if ( ! isset($steps_on) ) {
                    $steps_on = true;
                }
                ?>
            <tr class="tsugi-nav-pinned tsugi-nav-lessons" data-nav-id="course_views">
                <td class="tsugi-nav-handle" title="<?= htmlspecialchars(__('Stays under Home')) ?>">&#8942;</td>
                <td>
                    <input type="hidden" name="nav_order[]" value="course_views">
                    <?= __('Lessons') ?>
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
                        ?>
                    <div class="tsugi-nav-view-row">
                        <span class="tsugi-nav-view-name"><?= htmlspecialchars(__($view_name)) ?></span>
                        <label><input type="checkbox" name="view_default[<?= htmlspecialchars($view_id) ?>]" value="1"<?= $view_default ? ' checked' : '' ?>> <?= __('Default') ?></label>
                        <label><input type="checkbox" name="view_available[<?= htmlspecialchars($view_id) ?>]" value="1"<?= $view_available ? ' checked' : '' ?>> <?= __('Available') ?></label>
                        <label><input type="checkbox" name="view_test[<?= htmlspecialchars($view_id) ?>]" value="1"<?= $view_test ? ' checked' : '' ?>> <?= __('Instructor test') ?></label>
                    </div>
                    <?php } ?>
                    <div class="tsugi-nav-steps" data-nav-child="course_views"<?= $view_any ? '' : ' hidden' ?>>
                        <label class="tsugi-nav-steps-label">
                            <?= __('Show content progress checkboxes') ?>
                            <input type="hidden" name="steps" value="0">
                            <input type="checkbox" name="steps" value="1"<?= ! empty($steps_on) ? ' checked' : '' ?> aria-label="<?= htmlspecialchars(__('Show content progress checkboxes'), ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                    </div>
                    </div>
                </td>
                <td><input type="checkbox" class="tsugi-nav-place" name="nav[course_views][left]" value="1"<?= ! empty($view_place['left']) ? ' checked' : '' ?>></td>
                <td><input type="checkbox" class="tsugi-nav-place" name="nav[course_views][right]" value="1"<?= ! empty($view_place['right']) ? ' checked' : '' ?>></td>
                <td><input type="checkbox" class="tsugi-nav-place" name="nav[course_views][dropdown]" value="1"<?= ! empty($view_place['dropdown']) ? ' checked' : '' ?>></td>
                <td><input type="checkbox" class="tsugi-nav-place" name="nav[course_views][instructor]" value="1"<?= $view_instructor ? ' checked' : '' ?>></td>
            </tr>
            <?php } ?>
        <?php } ?>
        </tbody>
    </table>
    <p>
        <button type="submit" class="btn btn-primary"><?= __('Save navigation') ?></button>
        <a href="<?= htmlspecialchars($save_url) ?>" class="btn btn-default"><?= __('Cancel') ?></a>
    </p>
</form>
<?php
// TODO: Keyboard reorder (Move up/down) for every drag-reorder UI, not just this
// table: Settings → Navigation, Lessons authoring, Discussions reorder.
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
})();
</script>

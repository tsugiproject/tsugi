<?php
/**
 * Course navigation editor.
 *
 * Expected: $nav_rows (CourseNav::editorRows), $save_url
 */
?>
<p><?= __('Home is always in the upper left. You can rename the Home label. The avatar menu is always on the right. Student View is always in the avatar menu before Settings, and Settings is always before Logout. Both are instructors only.') ?></p>
<p><?= __('Check where each tool should appear. Instructor only hides that item from students. Widgets can only sit in the top left or top right, not under the avatar.') ?></p>
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
.tsugi-nav-editor tr.tsugi-nav-steps[hidden] { display: none; }
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
        $content_nav_shown = false;
        foreach ( $nav_rows as $nav_row ) {
            $nav_id = $nav_row['id'] ?? '';
            if ( $nav_id !== 'lessons' && $nav_id !== 'content' && $nav_id !== 'outline' ) {
                continue;
            }
            if ( ! empty($nav_row['left']) || ! empty($nav_row['right']) || ! empty($nav_row['dropdown']) ) {
                $content_nav_shown = true;
                break;
            }
        }
        foreach ( $nav_rows as $row ) {
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
            <?php if ( ($row['id'] ?? '') === 'outline' ) {
                if ( ! isset($steps_on) ) {
                    $steps_on = true;
                }
                ?>
            <tr class="tsugi-nav-pinned tsugi-nav-steps" data-nav-child="outline"<?= $content_nav_shown ? '' : ' hidden' ?>>
                <td class="tsugi-nav-handle" aria-hidden="true"></td>
                <td colspan="5">
                    <label class="tsugi-nav-steps-label">
                        <?= __('Show content progress checkboxes') ?>
                        <input type="hidden" name="steps" value="0">
                        <input type="checkbox" name="steps" value="1"<?= ! empty($steps_on) ? ' checked' : '' ?> aria-label="<?= htmlspecialchars(__('Show content progress checkboxes'), ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                </td>
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
        keepStepsUnderOutline();
    });
    tbody.addEventListener('dragover', function (e) {
        e.preventDefault();
        var tr = e.target.closest('tr');
        if (!tr || tr === dragging || !dragging) return;
        if (tr.getAttribute('data-nav-child') === 'outline') {
            tr = tr.previousElementSibling;
        }
        if (!tr || tr === dragging || tr.classList.contains('tsugi-nav-pinned')) return;
        var rect = tr.getBoundingClientRect();
        var next = (e.clientY - rect.top) > (rect.height / 2);
        tbody.insertBefore(dragging, next ? tr.nextSibling : tr);
        keepStepsUnderOutline();
    });

    function keepStepsUnderOutline() {
        var outlineRow = tbody.querySelector('tr[data-nav-id="outline"]');
        var steps = tbody.querySelector('tr[data-nav-child="outline"]');
        if (!outlineRow || !steps || outlineRow.nextElementSibling === steps) return;
        outlineRow.insertAdjacentElement('afterend', steps);
    }

    function rowIsPlaced(id) {
        var row = tbody.querySelector('tr[data-nav-id="' + id + '"]');
        if (!row) return false;
        var places = ['left', 'right', 'dropdown'];
        for (var i = 0; i < places.length; i++) {
            var box = row.querySelector('input[name="nav[' + id + '][' + places[i] + ']"]');
            if (box && box.checked) return true;
        }
        return false;
    }

    function syncProgressBoxes() {
        var steps = tbody.querySelector('tr[data-nav-child="outline"]');
        if (!steps) return;
        if (rowIsPlaced('lessons') || rowIsPlaced('content') || rowIsPlaced('outline')) steps.removeAttribute('hidden');
        else steps.setAttribute('hidden', 'hidden');
    }

    ['lessons', 'content', 'outline'].forEach(function (id) {
        var row = tbody.querySelector('tr[data-nav-id="' + id + '"]');
        if (row) row.addEventListener('change', syncProgressBoxes);
    });
    syncProgressBoxes();
})();
</script>

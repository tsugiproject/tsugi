<?php
/**
 * Course navigation editor.
 *
 * Expected: $nav_rows (CourseNav::editorRows), $save_url
 */
?>
<p><?= __('Home is always in the upper left. You can rename the Home label. The avatar menu is always on the right. Settings is always in the avatar menu before Logout and is instructors only.') ?></p>
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
</style>
<form method="post" action="<?= htmlspecialchars($save_url) ?>">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <table class="table table-condensed tsugi-nav-editor" id="tsugi-nav-editor">
        <thead>
            <tr>
                <th></th>
                <th><?= __('Tool') ?></th>
                <th><?= __('Toolbar left') ?></th>
                <th><?= __('Toolbar right') ?></th>
                <th><?= __('Right dropdown') ?></th>
                <th><?= __('Instructor only') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $nav_rows as $row ) {
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
                    <?php } else { ?>
                    <?= htmlspecialchars(__($row['label'])) ?>
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
                <td><input type="checkbox" name="nav[<?= $id ?>][instructor]" value="1"<?= ! empty($row['instructor']) ? ' checked' : '' ?>></td>
                <?php } ?>
            </tr>
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
})();
</script>

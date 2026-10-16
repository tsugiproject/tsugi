<?php
/**
 * Instructor configuration for grade categories and hidden columns.
 *
 * Expects $categories, $grade_columns, $post_home, $return_inputs.
 */

use Tsugi\Controllers\Tool;

$visible_columns = array();
$hidden_columns = array();
foreach ( $grade_columns as $column ) {
    if ( (int) $column['hidden'] === 1 ) {
        $hidden_columns[] = $column;
    } else {
        $visible_columns[] = $column;
    }
}

$category_options = function($selected) use ($categories) {
    $html = '<option value=""'.($selected === null || $selected === '' ? ' selected' : '').'>'
        .htmlspecialchars(__('Uncategorized')).'</option>';
    foreach ( $categories as $category ) {
        $id = (int) $category['category_id'];
        $is_selected = ((int) $selected === $id) ? ' selected' : '';
        $html .= '<option value="'.$id.'"'.$is_selected.'>'
            .htmlspecialchars((string) $category['title']).'</option>';
    }
    return $html;
};

$column_title = function($title) {
    $title = trim((string) $title);
    if ( $title === '' ) {
        return __('Untitled');
    }
    return $title;
};
?>
<div class="grades-configuration">
        <?php if ( empty($categories_only) ) { ?>
        <h2><?= htmlspecialchars(__('Configuration')) ?></h2>
        <?php } ?>
        <section>
          <h3><?= htmlspecialchars(__('Categories')) ?></h3>
          <p><?= htmlspecialchars(__('Regular percents are meant to total 100. Extra credit is added on top of that.')) ?></p>
          <?php if ( count($categories) < 1 ) { ?>
          <p><?= htmlspecialchars(__('No categories yet. Grades stay in one list until you add a category.')) ?></p>
          <?php } else { ?>
          <table class="table table-condensed">
            <tbody>
            <?php
            $last = count($categories) - 1;
            foreach ( $categories as $index => $category ) {
                $id = (int) $category['category_id'];
                $action = $post_home.'/categories/'.$id;
                $title = (string) $category['title'];
            ?>
              <tr>
                <td>
                  <form method="post" action="<?= htmlspecialchars($action) ?>" class="form-inline grades-category-form">
                    <?= Tool::csrfField() ?>
                    <?= $return_inputs ?>
                    <input type="hidden" name="action" value="save">
                    <label class="sr-only" for="category-title-<?= $id ?>"><?= htmlspecialchars(__('Title')) ?></label>
                    <input class="form-control" id="category-title-<?= $id ?>" name="title" type="text" maxlength="255" required value="<?= htmlspecialchars($title) ?>">
                    <label class="sr-only" for="category-percent-<?= $id ?>"><?= htmlspecialchars(__('Percent')) ?></label>
                    <input class="form-control grades-percent" id="category-percent-<?= $id ?>" name="grade_percent" type="number" min="0" max="100" step="1" required value="<?= (int) $category['grade_percent'] ?>">
                    <label for="category-extra-<?= $id ?>">
                      <input id="category-extra-<?= $id ?>" name="extra_credit" type="checkbox" value="1"<?= ((int) $category['extra_credit'] === 1) ? ' checked' : '' ?>>
                      <?= htmlspecialchars(__('Extra credit')) ?>
                    </label>
                    <button type="submit" class="btn btn-default"><?= htmlspecialchars(__('Save')) ?></button>
                  </form>
                  <form method="post" action="<?= htmlspecialchars($action) ?>" class="form-inline">
                    <?= Tool::csrfField() ?>
                    <?= $return_inputs ?>
                    <input type="hidden" name="action" value="move">
                    <input type="hidden" name="direction" value="up">
                    <button type="submit" class="btn btn-default"<?= $index === 0 ? ' disabled' : '' ?> aria-label="<?= htmlspecialchars(__('Move up').': '.$title) ?>"><?= htmlspecialchars(__('Up')) ?></button>
                  </form>
                  <form method="post" action="<?= htmlspecialchars($action) ?>" class="form-inline">
                    <?= Tool::csrfField() ?>
                    <?= $return_inputs ?>
                    <input type="hidden" name="action" value="move">
                    <input type="hidden" name="direction" value="down">
                    <button type="submit" class="btn btn-default"<?= $index === $last ? ' disabled' : '' ?> aria-label="<?= htmlspecialchars(__('Move down').': '.$title) ?>"><?= htmlspecialchars(__('Down')) ?></button>
                  </form>
                  <form method="post" action="<?= htmlspecialchars($action) ?>" class="form-inline" onsubmit="return confirm(<?= htmlspecialchars(json_encode(__('Delete this category? Its grades become uncategorized.'))) ?>);">
                    <?= Tool::csrfField() ?>
                    <?= $return_inputs ?>
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-default" aria-label="<?= htmlspecialchars(__('Delete').': '.$title) ?>"><?= htmlspecialchars(__('Delete')) ?></button>
                  </form>
                </td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
          <?php } ?>
          <form method="post" action="<?= htmlspecialchars($post_home.'/categories') ?>" class="form-inline">
            <?= Tool::csrfField() ?>
            <?= $return_inputs ?>
            <label class="sr-only" for="new-category-title"><?= htmlspecialchars(__('New category title')) ?></label>
            <input class="form-control" id="new-category-title" name="title" type="text" maxlength="255" required placeholder="<?= htmlspecialchars(__('New category')) ?>">
            <label class="sr-only" for="new-category-percent"><?= htmlspecialchars(__('Percent')) ?></label>
            <input class="form-control grades-percent" id="new-category-percent" name="grade_percent" type="number" min="0" max="100" step="1" required value="0">
            <label for="new-category-extra">
              <input id="new-category-extra" name="extra_credit" type="checkbox" value="1">
              <?= htmlspecialchars(__('Extra credit')) ?>
            </label>
            <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('Add category')) ?></button>
          </form>
        </section>

        <?php
        $render_columns = function($columns, $hide_action, $hide_label) use ($post_home, $return_inputs, $category_options, $column_title) {
            if ( count($columns) < 1 ) {
                return;
            }
            echo '<table class="table table-condensed"><thead><tr>';
            echo '<th scope="col">'.htmlspecialchars(__('Grade')).'</th>';
            echo '<th scope="col">'.htmlspecialchars(__('Category')).'</th>';
            echo '<th scope="col"></th>';
            echo '</tr></thead><tbody>';
            foreach ( $columns as $column ) {
                $id = (int) $column['link_id'];
                $title = $column_title($column['title']);
                $action = $post_home.'/links/'.$id;
                echo '<tr><td>'.htmlspecialchars($title).'</td>';
                echo '<td colspan="2">';
                echo '<form method="post" action="'.htmlspecialchars($action).'" class="form-inline">';
                echo Tool::csrfField();
                echo $return_inputs;
                echo '<label class="sr-only" for="link-category-'.$id.'">'.htmlspecialchars(__('Category')).'</label>';
                echo '<select class="form-control" id="link-category-'.$id.'" name="category_id">';
                echo $category_options($column['category_id']);
                echo '</select> ';
                echo '<button type="submit" class="btn btn-default" name="action" value="category">'.htmlspecialchars(__('Save')).'</button> ';
                echo '<button type="submit" class="btn btn-default" name="action" value="'.htmlspecialchars($hide_action).'">'.htmlspecialchars($hide_label).'</button>';
                echo '</form></td></tr>';
            }
            echo '</tbody></table>';
        };
        ?>

        <?php if ( empty($categories_only) ) { ?>
        <section class="grades-section">
          <h3><?= htmlspecialchars(__('Grades')) ?></h3>
          <?php if ( count($visible_columns) < 1 ) { ?>
          <p><?= htmlspecialchars(__('No visible grades.')) ?></p>
          <?php } else {
              $render_columns($visible_columns, 'hide', __('Hide from students'));
          } ?>
        </section>

        <section class="grades-section">
          <h3><?= htmlspecialchars(__('Hidden grades')) ?></h3>
          <p><?= htmlspecialchars(__('Hidden grades stay off the student grade book. Unhide puts the column back.')) ?></p>
          <?php if ( count($hidden_columns) < 1 ) { ?>
          <p><?= htmlspecialchars(__('No hidden grades.')) ?></p>
          <?php } else {
              $render_columns($hidden_columns, 'unhide', __('Unhide'));
          } ?>
        </section>
        <?php } ?>
</div>

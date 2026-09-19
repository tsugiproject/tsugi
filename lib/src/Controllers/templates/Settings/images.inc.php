<?php
/**
 * Course branding images: 16×9 hero and square icon.
 *
 * Expected: $save_url, $hero_url, $icon_url, $meta, $hero_spec, $icon_spec
 */
$has_hero = ! empty($meta['has_hero']);
$has_icon = ! empty($meta['has_icon']);
$hero_w = (int) $hero_spec['width'];
$hero_h = (int) $hero_spec['height'];
$icon_w = (int) $icon_spec['width'];
$icon_h = (int) $icon_spec['height'];
$hero_max = (int) $hero_spec['max_bytes'];
$icon_max = (int) $icon_spec['max_bytes'];
?>
<style>
.tsugi-course-image-slot { margin: 1.5em 0 2em; max-width: 40em; }
.tsugi-course-image-slot h2 { font-size: 1.2em; margin: 0 0 0.4em; }
.tsugi-course-image-preview {
    margin: 0.75em 0;
    padding: 8px;
    border: 1px solid #ddd;
    background: #fafafa;
    display: inline-block;
    max-width: 100%;
}
.tsugi-course-image-preview img { display: block; max-width: 100%; height: auto; }
.tsugi-course-image-preview.hero img { width: 320px; aspect-ratio: 16 / 9; object-fit: cover; }
.tsugi-course-image-preview.icon img { width: 128px; height: 128px; object-fit: cover; }
.tsugi-course-image-hint { color: #666; font-size: 0.9em; }
.tsugi-course-image-info { margin: 0.4em 0; font-size: 0.9em; }
</style>

<p><?= __('PNG or JPEG uploads are cropped to the required shape and converted to JPEG in the browser. The server checks the same limits before saving.') ?></p>

<div class="tsugi-course-image-slot">
    <h2><?= __('16×9 course image') ?></h2>
    <p class="tsugi-course-image-hint"><?= sprintf(__('Used on the all-courses list. Constructed as %1$d×%2$d JPEG, under %3$s.'), $hero_w, $hero_h, \Tsugi\Util\U::displaySize($hero_max)) ?></p>
    <?php if ( $has_hero && $hero_url ) { ?>
    <div class="tsugi-course-image-preview hero">
        <img src="<?= htmlspecialchars($hero_url) ?>" alt="<?= htmlspecialchars(__('Current 16×9 course image')) ?>">
    </div>
    <?php } ?>
    <form method="post" action="<?= htmlspecialchars($save_url) ?>" enctype="multipart/form-data">
        <?= \Tsugi\Controllers\Settings::csrfField() ?>
        <input type="hidden" name="image_action" value="save_hero">
        <p>
            <label for="hero_file"><?= __('Choose image') ?></label><br>
            <input type="file" name="uploaded_file" id="hero_file" class="tsugi_image"
                accept="image/png, image/jpeg, image/jpg"
                data-target-width="<?= $hero_w ?>"
                data-target-height="<?= $hero_h ?>"
                data-max-bytes="<?= $hero_max ?>"
                data-min-edge="<?= (int) $hero_spec['min_edge'] ?>"
                data-preview="hero_preview_img"
                data-info="hero_preview_info">
        </p>
        <div id="hero_preview_wrap" class="tsugi-course-image-preview hero" style="display:none;">
            <img id="hero_preview_img" alt="<?= htmlspecialchars(__('Processed 16×9 preview')) ?>">
        </div>
        <p id="hero_preview_info" class="tsugi-course-image-info"></p>
        <p>
            <button type="submit" class="btn btn-primary"><?= __('Save 16×9 image') ?></button>
        </p>
    </form>
    <?php if ( $has_hero ) { ?>
    <form method="post" action="<?= htmlspecialchars($save_url) ?>" onsubmit="return confirm(<?= htmlspecialchars(json_encode(__('Remove the 16×9 course image?')), ENT_QUOTES, 'UTF-8') ?>);">
        <?= \Tsugi\Controllers\Settings::csrfField() ?>
        <input type="hidden" name="image_action" value="clear_hero">
        <p><button type="submit" class="btn btn-default"><?= __('Remove 16×9 image') ?></button></p>
    </form>
    <?php } ?>
</div>

<div class="tsugi-course-image-slot">
    <h2><?= __('Square course icon') ?></h2>
    <p class="tsugi-course-image-hint"><?= sprintf(__('Used as a small course mark (navigation, lists). Constructed as %1$d×%2$d JPEG, under %3$s.'), $icon_w, $icon_h, \Tsugi\Util\U::displaySize($icon_max)) ?></p>
    <?php if ( $has_icon && $icon_url ) { ?>
    <div class="tsugi-course-image-preview icon">
        <img src="<?= htmlspecialchars($icon_url) ?>" alt="<?= htmlspecialchars(__('Current course icon')) ?>">
    </div>
    <?php } ?>
    <form method="post" action="<?= htmlspecialchars($save_url) ?>" enctype="multipart/form-data">
        <?= \Tsugi\Controllers\Settings::csrfField() ?>
        <input type="hidden" name="image_action" value="save_icon">
        <p>
            <label for="icon_file"><?= __('Choose image') ?></label><br>
            <input type="file" name="uploaded_file" id="icon_file" class="tsugi_image"
                accept="image/png, image/jpeg, image/jpg"
                data-target-width="<?= $icon_w ?>"
                data-target-height="<?= $icon_h ?>"
                data-max-bytes="<?= $icon_max ?>"
                data-min-edge="<?= (int) $icon_spec['min_edge'] ?>"
                data-preview="icon_preview_img"
                data-info="icon_preview_info">
        </p>
        <div id="icon_preview_wrap" class="tsugi-course-image-preview icon" style="display:none;">
            <img id="icon_preview_img" alt="<?= htmlspecialchars(__('Processed icon preview')) ?>">
        </div>
        <p id="icon_preview_info" class="tsugi-course-image-info"></p>
        <p>
            <button type="submit" class="btn btn-primary"><?= __('Save icon') ?></button>
        </p>
    </form>
    <?php if ( $has_icon ) { ?>
    <form method="post" action="<?= htmlspecialchars($save_url) ?>" onsubmit="return confirm(<?= htmlspecialchars(json_encode(__('Remove the course icon?')), ENT_QUOTES, 'UTF-8') ?>);">
        <?= \Tsugi\Controllers\Settings::csrfField() ?>
        <input type="hidden" name="image_action" value="clear_icon">
        <p><button type="submit" class="btn btn-default"><?= __('Remove icon') ?></button></p>
    </form>
    <?php } ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    ['hero_preview_img', 'icon_preview_img'].forEach(function (id) {
        var img = document.getElementById(id);
        if (!img) return;
        img.addEventListener('load', function () {
            if (img.getAttribute('src')) {
                var wrap = img.parentElement;
                if (wrap) wrap.style.display = 'inline-block';
            }
        });
    });
});
</script>

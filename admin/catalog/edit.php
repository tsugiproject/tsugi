<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\ContextImages;
use \Tsugi\Controllers\StaticFiles;
use \Tsugi\Controllers\Tool;
use \Tsugi\Services\Catalog\CatalogRepository;
use \Tsugi\UI\CKEditor;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../admin_util.php';

LTIX::getConnection();
session_start();
require_once __DIR__ . '/../gate.php';
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! isAdmin() ) {
    \Tsugi\Controllers\Login::setReturnUrl(LTIX::curPageUrlFolder());
    header('Location: '.\Tsugi\Controllers\Login::loginUrl());
    return;
}

$catalog_id = (int) U::get($_GET, 'id', U::get($_POST, 'catalog_id', 0));
$self = U::addSession('edit.php'.($catalog_id > 0 ? '?id='.$catalog_id : ''));
$list_url = U::addSession('index.php');

$home_id = CatalogRepository::homeContextId();

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    if ( Tool::csrfRedirect($self) ) return;
    $image_action = (string) U::get($_POST, 'image_action', '');
    if ( $image_action !== '' ) {
        if ( $catalog_id < 1 ) {
            U::flashError(__('Save the listing before uploading an image.'));
            header('Location: '.$self);
            return;
        }
        if ( $image_action === 'clear_hero' ) {
            $err = CatalogRepository::clearHero($catalog_id);
            if ( $err ) {
                U::flashError($err);
            } else {
                U::flashSuccess(__('16×9 image removed.'));
            }
            header('Location: '.$self);
            return;
        }
        if ( $image_action !== 'save_hero' ) {
            U::flashError(__('Unknown image action.'));
            header('Location: '.$self);
            return;
        }
        $fdes = isset($_FILES['uploaded_file']) && is_array($_FILES['uploaded_file'])
            ? $_FILES['uploaded_file'] : null;
        if ( $fdes === null || ! isset($fdes['tmp_name']) || ! is_uploaded_file($fdes['tmp_name']) ) {
            U::flashError(__('Please choose an image to upload.'));
            header('Location: '.$self);
            return;
        }
        if ( isset($fdes['error']) && (int) $fdes['error'] !== UPLOAD_ERR_OK ) {
            U::flashError(__('Upload failed.'));
            header('Location: '.$self);
            return;
        }
        $constructed = ContextImages::constructJpeg($fdes['tmp_name'], ContextImages::KIND_HERO);
        if ( is_string($constructed) ) {
            U::flashError($constructed);
            header('Location: '.$self);
            return;
        }
        $err = CatalogRepository::saveHero($catalog_id, $constructed['bytes']);
        if ( $err ) {
            U::flashError($err);
        } else {
            U::flashSuccess(__('16×9 image saved.'));
        }
        header('Location: '.$self);
        return;
    }

    $norm = CatalogRepository::normalizeInput($_POST, $home_id);
    if ( empty($norm['ok']) ) {
        U::flashError($norm['error'] ?? __('Could not save catalog entry.'));
        header('Location: '.$self);
        return;
    }
    $result = CatalogRepository::save($catalog_id, $norm['data'], U::loggedInUserId());
    if ( empty($result['ok']) ) {
        U::flashError($result['error'] ?? __('Could not save catalog entry.'));
        header('Location: '.$self);
        return;
    }
    $new_id = (int) $result['catalog_id'];
    U::flashSuccess(__('Catalog entry saved.'));
    header('Location: '.U::addSession('edit.php?id='.$new_id));
    return;
}

$row = $catalog_id > 0 ? CatalogRepository::load($catalog_id, false, 0) : null;
if ( $catalog_id > 0 && $row === null ) {
    U::flashError(__('Catalog entry not found.'));
    header('Location: '.$list_url);
    return;
}

$choices = CatalogRepository::contextChoices($home_id, $catalog_id);
$kind = ($row && trim((string) ($row['external_url'] ?? '')) !== '') ? 'link' : 'course';
if ( $row === null ) {
    $kind = 'course';
}
$title = $row['title'] ?? '';
$short = $row['short_description'] ?? '';
$description = $row['description'] ?? '';
$published = $row ? ! empty($row['published']) : false;
$sort_order = $row ? (int) ($row['sort_order'] ?? 0) : 0;
$new_window = $row ? ! empty($row['new_window']) : true;
$external_url = $row['external_url'] ?? '';
$context_id = $row ? (int) ($row['context_id'] ?? 0) : 0;
$hero_url = is_array($row) ? (string) ($row['hero_url'] ?? '') : '';
$has_catalog_hero = is_array($row) && ! empty($row['has_catalog_hero']);
$hero_spec = ContextImages::spec(ContextImages::KIND_HERO);
$js_url = StaticFiles::url('Settings', 'tsugi-image-upload.js', rtrim((string) $CFG->wwwroot, '/').'/static');

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1><?= $catalog_id > 0 ? htmlspecialchars(__('Edit catalog listing')) : htmlspecialchars(__('Add catalog listing')) ?></h1>
<p><a href="<?= htmlspecialchars($list_url) ?>"><?= __('Course catalog') ?></a></p>

<form method="post" action="<?= htmlspecialchars($self) ?>" id="catalog_form">
    <?= Tool::csrfField() ?>
    <?php if ( $catalog_id > 0 ) { ?>
    <input type="hidden" name="catalog_id" value="<?= $catalog_id ?>">
    <?php } ?>
    <div class="form-group">
        <label><?= htmlspecialchars(__('Listing type')) ?></label><br>
        <label style="font-weight:normal;margin-right:1em;">
            <input type="radio" name="kind" value="course" <?= $kind === 'course' ? 'checked' : '' ?>>
            <?= __('Course') ?>
        </label>
        <label style="font-weight:normal;">
            <input type="radio" name="kind" value="link" <?= $kind === 'link' ? 'checked' : '' ?>>
            <?= __('Link to another site') ?>
        </label>
    </div>
    <div class="form-group" id="kind_course">
        <label for="context_id"><?= htmlspecialchars(__('Course')) ?></label>
        <select class="form-control" id="context_id" name="context_id">
            <option value=""><?= htmlspecialchars(__('Select a course')) ?></option>
            <?php foreach ( $choices as $choice ) {
                $cid = (int) $choice['context_id'];
                $sel = $cid === $context_id ? ' selected' : '';
            ?>
            <option value="<?= $cid ?>"<?= $sel ?>><?= htmlspecialchars($choice['title']) ?></option>
            <?php } ?>
        </select>
        <p class="help-block"><?= __('The site home course is not listed here. Add it as a link to the site home instead.') ?></p>
    </div>
    <div class="form-group" id="kind_link">
        <label for="external_url"><?= htmlspecialchars(__('URL')) ?></label>
        <input class="form-control" type="url" id="external_url" name="external_url" value="<?= htmlspecialchars((string) $external_url) ?>" placeholder="https://">
        <label style="font-weight:normal;margin-top:0.5em;">
            <input type="checkbox" name="new_window" value="1" <?= $new_window ? 'checked' : '' ?>>
            <?= __('Open in a new window') ?>
        </label>
    </div>
    <div class="form-group">
        <label for="title"><?= htmlspecialchars(__('Title')) ?></label>
        <input class="form-control" type="text" id="title" name="title" required maxlength="512" value="<?= htmlspecialchars((string) $title) ?>">
    </div>
    <div class="form-group">
        <label for="short_description"><?= htmlspecialchars(__('Short description')) ?></label>
        <input class="form-control" type="text" id="short_description" name="short_description" maxlength="512" value="<?= htmlspecialchars((string) $short) ?>">
        <p class="help-block"><?= __('Plain text only. Shown on catalog cards.') ?></p>
    </div>
    <div class="form-group">
        <label for="editor_body"><?= htmlspecialchars(__('Description')) ?></label>
        <div class="ckeditor-container">
            <textarea name="description" id="editor_body"><?= htmlspecialchars((string) $description) ?></textarea>
        </div>
    </div>
    <div class="form-group">
        <label for="sort_order"><?= htmlspecialchars(__('Sort order')) ?></label>
        <input class="form-control" type="number" id="sort_order" name="sort_order" value="<?= (int) $sort_order ?>" style="max-width:8em;">
    </div>
    <div class="form-group">
        <label style="font-weight:normal;">
            <input type="checkbox" name="published" value="1" <?= $published ? 'checked' : '' ?>>
            <?= __('Published') ?>
        </label>
    </div>
    <p>
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('Save')) ?></button>
        <a href="<?= htmlspecialchars($list_url) ?>" class="btn btn-default"><?= htmlspecialchars(__('Back to list')) ?></a>
    </p>
</form>

<?php if ( $catalog_id > 0 && is_array($hero_spec) ) {
    $hero_w = (int) $hero_spec['width'];
    $hero_h = (int) $hero_spec['height'];
    $hero_max = (int) $hero_spec['max_bytes'];
    $preview = is_string($hero_url) ? $hero_url : '';
?>
<hr>
<div class="tsugi-course-image-slot">
    <h2><?= __('16×9 catalog image') ?></h2>
    <p class="help-block"><?= sprintf(__('Overrides the course image when set. Constructed as %1$d×%2$d JPEG, under %3$s. Empty listings use the course image or a placeholder.'), $hero_w, $hero_h, U::displaySize($hero_max)) ?></p>
    <?php if ( $preview !== '' ) { ?>
    <div class="tsugi-course-image-preview hero" style="margin:0.75em 0;padding:8px;border:1px solid #ddd;display:inline-block;">
        <img src="<?= htmlspecialchars($preview) ?>" alt="" style="display:block;width:320px;aspect-ratio:16/9;object-fit:cover;">
    </div>
    <?php } ?>
    <form method="post" action="<?= htmlspecialchars($self) ?>" enctype="multipart/form-data">
        <?= Tool::csrfField() ?>
        <input type="hidden" name="catalog_id" value="<?= $catalog_id ?>">
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
        <div id="hero_preview_wrap" class="tsugi-course-image-preview hero" style="display:none;margin:0.75em 0;padding:8px;border:1px solid #ddd;">
            <img id="hero_preview_img" alt="" style="display:block;width:320px;aspect-ratio:16/9;object-fit:cover;">
        </div>
        <p id="hero_preview_info"></p>
        <p><button type="submit" class="btn btn-primary"><?= __('Save 16×9 image') ?></button></p>
    </form>
    <?php if ( $has_catalog_hero ) { ?>
    <form method="post" action="<?= htmlspecialchars($self) ?>" onsubmit="return confirm(<?= htmlspecialchars(json_encode(__('Remove the catalog 16×9 image?')), ENT_QUOTES, 'UTF-8') ?>);">
        <?= Tool::csrfField() ?>
        <input type="hidden" name="catalog_id" value="<?= $catalog_id ?>">
        <input type="hidden" name="image_action" value="clear_hero">
        <p><button type="submit" class="btn btn-default"><?= __('Remove 16×9 image') ?></button></p>
    </form>
    <?php } ?>
</div>
<?php } ?>

<?php
$OUTPUT->footerStart();
$apphome = isset($CFG->apphome) ? rtrim((string) $CFG->apphome, '/') : '';
?>
<style>
<?php CKEditor::renderStyles(['includeLinkPicker' => false, 'extraStyles' => '.ckeditor-container { min-height: 12em; }']); ?>
</style>
<?php CKEditor::renderScriptTag(); ?>
<script src="<?= htmlspecialchars($js_url) ?>"></script>
<script type="text/javascript">
var appHome = <?= json_encode($apphome) ?>;
var pagesBase = appHome;
var filesBase = appHome;
<?php CKEditor::renderConfigScript(array('automaticExternalBlank' => false)); ?>
function tsugiCatalogKind() {
    var course = document.getElementById('kind_course');
    var link = document.getElementById('kind_link');
    var checked = document.querySelector('input[name="kind"]:checked');
    var kind = checked ? checked.value : 'course';
    if (course) course.style.display = kind === 'course' ? '' : 'none';
    if (link) link.style.display = kind === 'link' ? '' : 'none';
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[name="kind"]').forEach(function (el) {
        el.addEventListener('change', tsugiCatalogKind);
    });
    tsugiCatalogKind();
    if (typeof ClassicEditor === 'undefined') return;
    var el = document.getElementById('editor_body');
    if (!el) return;
    ClassicEditor.create(el, ClassicEditor.defaultConfig).catch(function(e) { console.error(e); });
    var img = document.getElementById('hero_preview_img');
    if (img) {
        img.addEventListener('load', function () {
            if (img.getAttribute('src')) {
                var wrap = img.parentElement;
                if (wrap) wrap.style.display = 'inline-block';
            }
        });
    }
});
</script>
<?php
$OUTPUT->footerEnd();

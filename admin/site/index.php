<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Controllers\Tool;
use \Tsugi\Services\Site\Site;
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

$self = U::addSession('index.php');

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    if ( Tool::csrfRedirect($self) ) return;
    if ( ! Site::tableExists() ) {
        U::flashError(__('Site table is missing. Run Upgrade Database.'));
        header('Location: '.$self);
        return;
    }
    if ( U::get($_POST, 'clear') ) {
        Site::clear();
        U::flashSuccess(__('Restored the built-in landing page.'));
    } else {
        $use_catalog = U::get($_POST, 'use_catalog') ? true : false;
        $saved = Site::save(U::get($_POST, 'body', ''), $use_catalog);
        if ( $use_catalog ) {
            U::flashSuccess(__('The public landing page will show the course catalog.'));
        } elseif ( $saved ) {
            U::flashSuccess(__('Saved site landing page.'));
        } else {
            U::flashSuccess(__('Restored the built-in landing page.'));
        }
    }
    header('Location: '.$self);
    return;
}

$table_ok = Site::tableExists();
$current = $table_ok ? Site::body() : null;
$has_custom = is_string($current);
$use_catalog = $table_ok && Site::useCatalog();

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1><?= htmlspecialchars(__('Edit Site Config')) ?></h1>
<?php if ( ! $table_ok ) { ?>
<p><?= __('The site table is missing. Run Upgrade Database in the Administration console, then return here.') ?></p>
<p><a href="<?= htmlspecialchars($CFG->wwwroot.'/admin/') ?>"><?= __('Administration console') ?></a></p>
<?php
    $OUTPUT->footer();
    return;
}
?>
<p><?= __('This HTML replaces the body of the public Tsugi landing page. Leave it empty or restore the default to use the built-in welcome text. Or skip the HTML and use the course catalog as the landing page.') ?></p>
<?php if ( $use_catalog ) { ?>
<p><em><?= __('The course catalog is currently the public landing page.') ?></em></p>
<?php } elseif ( $has_custom ) { ?>
<p><em><?= __('A custom landing page is currently published.') ?></em></p>
<?php } else { ?>
<p><em><?= __('Using the built-in landing page.') ?></em></p>
<?php } ?>
<form method="post" id="site_form">
    <?= Tool::csrfField() ?>
    <div class="checkbox">
        <label for="use_catalog">
            <input type="checkbox" name="use_catalog" id="use_catalog" value="1"<?= $use_catalog ? ' checked' : '' ?>>
            <?= htmlspecialchars(__('Use the course catalog as the landing page')) ?>
        </label>
        <p class="help-block"><?= __('When checked, visitors to the Tsugi home page see the catalog instead of the HTML below. Your HTML is kept if you uncheck this later.') ?></p>
    </div>
    <div class="form-group" id="landing_editor">
        <label for="editor_body"><?= htmlspecialchars(__('Landing page')) ?></label>
        <div class="ckeditor-container">
            <textarea name="body" id="editor_body"><?= htmlspecialchars($has_custom ? $current : '') ?></textarea>
        </div>
    </div>
    <p>
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('Save')) ?></button>
        <button type="submit" name="clear" value="1" class="btn btn-default"><?= htmlspecialchars(__('Restore default')) ?></button>
        <a href="<?= htmlspecialchars($CFG->wwwroot.'/admin/') ?>" class="btn btn-default"><?= htmlspecialchars(__('Cancel')) ?></a>
        <a href="<?= htmlspecialchars($CFG->wwwroot.'/') ?>" class="btn btn-link" target="_blank"><?= htmlspecialchars(__('View landing page')) ?></a>
    </p>
</form>
<?php
$OUTPUT->footerStart();
$apphome = isset($CFG->apphome) ? rtrim((string) $CFG->apphome, '/') : '';
?>
<style>
<?php CKEditor::renderStyles(['includeLinkPicker' => false, 'extraStyles' => '.ckeditor-container { min-height: 16em; }']); ?>
</style>
<?php CKEditor::renderScriptTag(); ?>
<script type="text/javascript">
var appHome = <?= json_encode($apphome) ?>;
var pagesBase = appHome;
var filesBase = appHome;
<?php CKEditor::renderConfigScript(); ?>
document.addEventListener('DOMContentLoaded', function() {
    var catalogBox = document.getElementById('use_catalog');
    var editorWrap = document.getElementById('landing_editor');
    var editorEl = document.getElementById('editor_body');
    var editorInstance = null;

    function ensureEditor() {
        if (editorInstance || typeof ClassicEditor === 'undefined' || !editorEl) return;
        ClassicEditor.create(editorEl, ClassicEditor.defaultConfig).then(function(ed) {
            editorInstance = ed;
        }).catch(function(e) { console.error(e); });
    }

    function syncLandingUi() {
        var on = catalogBox && catalogBox.checked;
        if (editorWrap) editorWrap.style.display = on ? 'none' : '';
        if (!on) ensureEditor();
    }

    if (catalogBox) catalogBox.addEventListener('change', syncLandingUi);
    syncLandingUi();
});
</script>
<?php
$OUTPUT->footerEnd();

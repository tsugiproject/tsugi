<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Core\Manifest;
use Tsugi\Core\Membership;
use Tsugi\UI\LessonsCartridge;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Course setup for manifest-backed courses.
 *
 * Setup fields are independent columns on the manifest row, not part of
 * the legacy lessons JSON. Theme is the first tab; Common Cartridge export
 * is /setup/export (nested: /courses/{id}/setup/export). File-based
 * $CFG->lessons sites keep using the site $CFG->theme; this page is
 * instructor + manifest only. Leave /tsugi/cc as the site-file cartridge.
 *
 * Parent menus (instructors of a manifest course only):
 * if ( \Tsugi\Controllers\Setup::showInMenu() ) {
 *     $set->addLink('Setup', rtrim($CFG->apphome, '/') . Courses::toolPathPrefix() . '/setup');
 * }
 */
class Setup extends Tool {

    const ROUTE = '/setup';
    const NAME = 'Setup';
    const REDIRECT = 'tsugi_controllers_setup';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix.'/export/download', 'Setup@exportDownload');
        $app->router->get($prefix.'/export', 'Setup@export');
        $app->router->get($prefix.'/export/', 'Setup@export');
        $app->router->post($prefix.'/export', 'Setup@export');
        $app->router->post($prefix.'/export/', 'Setup@export');
        $app->router->get($prefix, 'Setup@get');
        $app->router->get($prefix.'/', 'Setup@get');
        $app->router->get('/'.self::REDIRECT, 'Setup@get');
        $app->router->post($prefix, 'Setup@post');
        $app->router->post($prefix.'/', 'Setup@post');
    }

    /**
     * True when the current user may open Setup (instructor/admin of a manifest course).
     *
     * Use this in parent buildmenu.php so students never see the link.
     */
    public static function showInMenu() {
        if ( Manifest::activeId() < 1 ) {
            return false;
        }
        return self::currentUserIsInstructor();
    }

    /**
     * Instructor or site admin for the current context (membership role, not $USER->instructor).
     */
    private static function currentUserIsInstructor() {
        $context_id = U::currentContextId();
        $user_id = U::loggedInUserId();
        if ( ! $context_id || ! $user_id ) {
            return false;
        }
        if ( isset($_SESSION['admin']) && $_SESSION['admin'] == 'yes' ) {
            return true;
        }
        $m = Membership::ensureInSession($context_id, $user_id);
        return $m->isInstructor();
    }

    public function get(Request $request)
    {
        global $OUTPUT;

        $setup_url = U::addSession($this->toolHome(self::ROUTE));
        $gate = $this->setupGate();
        if ( $gate ) {
            return $gate;
        }

        $theme_current = Manifest::currentThemeKey();
        $theme_palettes = Manifest::palettes();
        $theme_site_primary = Manifest::siteDefaultPrimary();
        $save_url = $setup_url;
        $export_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'export'));
        $setup_tab = 'theme';

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" id="main-content">
            <h1><?= __('Setup') ?></h1>
            <?php include __DIR__ . '/templates/Setup/tabs.inc.php'; ?>
            <div style="margin-top:10px;">
            <p><?= __('Theme is stored with each manifest version. New courses start with the site default until you pick one.') ?></p>
            <form method="post" action="<?= htmlspecialchars($save_url) ?>">
                <?= self::csrfField() ?>
                <?php include __DIR__ . '/templates/Setup/theme_picker.inc.php'; ?>
                <p>
                    <button type="submit" class="btn btn-primary"><?= __('Save theme') ?></button>
                    <a href="<?= htmlspecialchars($save_url) ?>" class="btn btn-default"><?= __('Cancel') ?></a>
                </p>
            </form>
            </div>
        </main>
        <?php
        $OUTPUT->footer();
        return '';
    }

    /**
     * Common Cartridge export form for the current manifest course.
     */
    public function export(Request $request)
    {
        global $CFG, $OUTPUT;

        $setup_url = U::addSession($this->toolHome(self::ROUTE));
        $export_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'export'));
        $download_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'export/download'));
        $gate = $this->setupGate();
        if ( $gate ) {
            return $gate;
        }

        $l = Manifest::currentLessons();
        if ( ! $l ) {
            U::flashError(__('Cannot load course lessons.'));
            return new RedirectResponse($setup_url);
        }

        $counts = LessonsCartridge::summarize($l);
        $youtube_enabled = isset($CFG->youtube_url);
        $localhost_warning = strpos($CFG->wwwroot, '//localhost') !== false;
        $canvas_return_url = U::get($_POST, 'ext_content_return_url', false);
        if ( ! is_string($canvas_return_url) || $canvas_return_url === '' ) {
            $canvas_return_url = false;
        }
        $setup_tab = 'export';

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" id="main-content">
            <h1><?= __('Setup') ?></h1>
            <?php include __DIR__ . '/templates/Setup/tabs.inc.php'; ?>
            <div style="margin-top:10px;">
            <?php include __DIR__ . '/templates/Setup/export.inc.php'; ?>
            </div>
        </main>
        <?php
        $OUTPUT->footerStart();
        ?>
<script>
function myfunc(){
    $("#res").val('');
    $('#void input[type="checkbox"]').each(function(){
         if ( ! $(this).is(':checked') ) return;
         var b = $("#res").val();
         if(b.length > 0){
            $("#res").val( b + ',' + $(this).val() );
        } else {
            $("#res").val( $(this).val() );
        }
    });

    $("#tsugi_lms_real").val($("#tsugi_lms_select_partial").val());
    var stuff = $("#res").val();
    $("#youtube_real").val($("#youtube_select_partial").val() || '');
    $("#topic_real").val($("#topic_select_partial").val() || '');

    if ( stuff.length < 1 ) {
        alert(<?= json_encode(__('Please select at least one module')) ?>);
    } else {
        $("#real").submit();
    }
}
function sendToCanvas() {
    var youtube = $("#youtube_select_full").val() || 'no';
    var topic = $("#topic_select_full").val() || 'none';
    var return_url = <?= json_encode($canvas_return_url ? $canvas_return_url : '') ?>;
    var export_url = <?= json_encode($download_url) ?>;
    export_url = export_url + (export_url.indexOf('?') >= 0 ? '&' : '?') + 'tsugi_lms=canvas';
    export_url = export_url + '&youtube=' + encodeURIComponent(youtube);
    export_url = export_url + '&topic=' + encodeURIComponent(topic);
    return_url = return_url + (return_url.indexOf('?') >= 0 ? '&' : '?');
    return_url = return_url + 'return_type=file&text=' + encodeURIComponent(<?= json_encode(isset($CFG->servicename) ? $CFG->servicename : 'Tsugi') ?>);
    return_url = return_url + '&url=' + encodeURIComponent(export_url);
    window.location.href = return_url;
}
</script>
        <?php
        $OUTPUT->footerEnd();
        return '';
    }

    /**
     * Download a Common Cartridge built from the current course manifest.
     */
    public function exportDownload(Request $request)
    {
        global $CFG;

        $export_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'export'));
        $gate = $this->setupGate();
        if ( $gate ) {
            return $gate;
        }

        $l = Manifest::currentLessons();
        if ( ! $l ) {
            U::flashError(__('Cannot load course lessons.'));
            return new RedirectResponse($export_url);
        }

        $anchor_str = U::get($_GET, 'anchors', false);
        $anchors = false;
        if ( $anchor_str ) {
            $anchors = explode(',', $anchor_str);
        }
        if ( ! is_array($anchors) || count($anchors) < 1 ) {
            $anchors = false;
        }
        if ( $anchors ) {
            $anchor_count = 0;
            foreach ( $l->lessons->modules as $module ) {
                if ( in_array($module->anchor, $anchors) ) {
                    $anchor_count++;
                }
            }
            if ( $anchor_count < 1 ) {
                $anchors = false;
            }
        }

        $filename = tempnam(sys_get_temp_dir(), isset($CFG->servicename) ? $CFG->servicename : 'cc');
        if ( $filename === false ) {
            U::flashError(__('Could not create a temporary file for the cartridge.'));
            return new RedirectResponse($export_url);
        }
        unlink($filename);
        $zip = new \ZipArchive();
        if ( $zip->open($filename, \ZipArchive::CREATE) !== true ) {
            U::flashError(__('Cannot open the cartridge zip file.'));
            return new RedirectResponse($export_url);
        }

        LessonsCartridge::writeZip($l, $zip, array(
            'tsugi_lms' => U::get($_GET, 'tsugi_lms', false),
            'topic' => U::get($_GET, 'topic', false),
            'youtube' => U::get($_GET, 'youtube', false),
            'anchors' => $anchors,
        ));
        $zip->close();

        $download = LessonsCartridge::downloadName($l);
        $download = str_replace(array('\\', '"'), '', $download);
        $response = new BinaryFileResponse($filename);
        $response->headers->set('Content-Type', 'application/x-zip');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $download);
        $response->deleteFileAfterSend(true);
        return $response;
    }

    public function post(Request $request)
    {
        $setup_url = U::addSession($this->toolHome(self::ROUTE));
        $gate = $this->setupGate();
        if ( $gate ) {
            return $gate;
        }
        $csrf = self::requireCsrf($setup_url);
        if ( $csrf ) {
            return $csrf;
        }

        $posted = U::get($_POST, 'theme', '');
        $norm = Manifest::normalizeThemeKey($posted);
        if ( $norm === false ) {
            U::flashError(__('Unknown theme.'));
            return new RedirectResponse($setup_url);
        }

        $doc = Manifest::currentDocument();
        if ( ! $doc ) {
            U::flashError(__('Cannot load course manifest.'));
            return new RedirectResponse($setup_url);
        }
        $decoded = json_decode($doc['json'], true);
        if ( ! is_array($decoded) ) {
            U::flashError(__('Invalid manifest JSON.'));
            return new RedirectResponse($setup_url);
        }

        $context_id = U::currentContextId();
        $store = $norm === null ? '' : $norm;
        try {
            Manifest::saveNewVersion(
                $context_id,
                $decoded,
                U::loggedInUserId(),
                'Set theme',
                $store
            );
        } catch ( \InvalidArgumentException $e ) {
            U::flashError($e->getMessage());
            return new RedirectResponse($setup_url);
        } catch ( \Exception $e ) {
            U::flashError(__('Failed to save theme.'));
            return new RedirectResponse($setup_url);
        }

        U::flashSuccess(__('Theme saved.'));
        return new RedirectResponse($setup_url);
    }

    /**
     * @return RedirectResponse|null
     */
    private function setupGate() {
        $home = U::addSession(self::configuredHomeUrl());
        $this->requireInstructor($home);
        if ( Manifest::activeId() < 1 ) {
            U::flashError(__('Setup is only available for courses with a manifest.'));
            return new RedirectResponse($home);
        }
        return null;
    }
}

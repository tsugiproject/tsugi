<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Core\Manifest;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Course setup for manifest-backed courses.
 *
 * First field is the course theme. File-based $CFG->lessons sites keep using
 * the site $CFG->theme; this page is instructor + manifest only.
 *
 * Parent menus:
 * rtrim($CFG->apphome, '/') . Courses::toolPathPrefix() . '/setup'
 */
class Setup extends Tool {

    const ROUTE = '/setup';
    const NAME = 'Setup';
    const REDIRECT = 'tsugi_controllers_setup';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Setup@get');
        $app->router->get($prefix.'/', 'Setup@get');
        $app->router->get('/'.self::REDIRECT, 'Setup@get');
        $app->router->post($prefix, 'Setup@post');
        $app->router->post($prefix.'/', 'Setup@post');
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
        $save_url = $setup_url;

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" id="main-content">
            <h1><?= __('Setup') ?></h1>
            <p><?= __('Theme is stored with each manifest version. New courses start with the site default until you pick one.') ?></p>
            <form method="post" action="<?= htmlspecialchars($save_url) ?>">
                <?php include __DIR__ . '/templates/Setup/theme_picker.inc.php'; ?>
                <p>
                    <button type="submit" class="btn btn-primary"><?= __('Save theme') ?></button>
                </p>
            </form>
        </main>
        <?php
        $OUTPUT->footer();
        return '';
    }

    public function post(Request $request)
    {
        $setup_url = U::addSession($this->toolHome(self::ROUTE));
        $gate = $this->setupGate();
        if ( $gate ) {
            return $gate;
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
        if ( Manifest::activeId() < 1 ) {
            U::flashError(__('Setup is only available for courses with a manifest.'));
            return new RedirectResponse($home);
        }
        $this->requireInstructor($home);
        return null;
    }
}

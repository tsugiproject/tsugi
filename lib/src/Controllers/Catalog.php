<?php

namespace Tsugi\Controllers;

use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Tsugi\Core\ContextImages;
use Tsugi\Services\Catalog\CatalogRepository;
use Tsugi\Util\U;

class Catalog extends Tool {

    const ROUTE = '/catalog';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix.'/json', function(Request $request) use ($app) {
            return Catalog::getjson($app);
        });
        $app->router->get($prefix, function(Request $request) use ($app) {
            return Catalog::index($app, $request);
        });
        $app->router->get($prefix.'/', function(Request $request) use ($app) {
            return Catalog::index($app, $request);
        });
        $app->router->get($prefix.'/{id:\d+}/image/{kind}', function(Request $request, $id, $kind) use ($app) {
            return Catalog::image($app, $request, $id, $kind);
        });
        $app->router->post($prefix.'/{id:\d+}/enrol', function(Request $request, $id) use ($app) {
            return Catalog::enrol($app, $request, $id);
        });
        $app->router->get($prefix.'/{id:\d+}', function(Request $request, $id) use ($app) {
            return Catalog::detail($app, $request, $id);
        });
        $app->router->get($prefix.'/{id:\d+}/', function(Request $request, $id) use ($app) {
            return Catalog::detail($app, $request, $id);
        });
    }

    /**
     * CFG show_course_catalog: waffle footer link. Off by default.
     */
    public static function showCourseCatalog() {
        global $CFG;
        if ( ! isset($CFG) || ! is_object($CFG) ) {
            return false;
        }
        return ! empty($CFG->show_course_catalog);
    }

    public static function catalogUrl() {
        global $CFG;
        return rtrim((string) $CFG->wwwroot, '/').self::ROUTE;
    }

    /**
     * True when $url is the site Home URL (home_path, else apphome, else wwwroot).
     *
     * That is the Google site-home catalog card: a link, not an enrollable course.
     */
    public static function isSiteHomeLink($url) {
        global $CFG;
        if ( ! is_string($url) || trim($url) === '' ) {
            return false;
        }
        if ( ! isset($CFG) || ! is_object($CFG) || ! method_exists($CFG, 'getHomeUrl') ) {
            return false;
        }
        $left = self::normalizeHomeLink($url);
        $right = self::normalizeHomeLink($CFG->getHomeUrl());
        return $left !== '' && $right !== '' && strcasecmp($left, $right) === 0;
    }

    /**
     * Google site-login users are already on the site home; star that one link.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    public static function markHomeEnrolled(array $rows) {
        if ( U::loggedInUserId() < 1 || ! Courses::isGoogleLoginSession() ) {
            return $rows;
        }
        $homeId = CatalogRepository::homeContextId();
        foreach ( $rows as $i => $row ) {
            if ( ! empty($row['enrolled']) ) {
                continue;
            }
            $url = isset($row['external_url']) ? (string) $row['external_url'] : '';
            if ( $url !== '' && self::isSiteHomeLink($url) ) {
                $rows[$i]['enrolled'] = true;
                continue;
            }
            $cid = (int) ($row['context_id'] ?? 0);
            if ( $homeId > 0 && $cid === $homeId ) {
                $rows[$i]['enrolled'] = true;
            }
        }
        return $rows;
    }

    /**
     * App home (home_path, else apphome, else wwwroot).
     */
    public static function siteHomeUrl() {
        return Courses::appHomeUrl();
    }

    /**
     * Public card href. The Google site-login course opens the app home
     * unless the listing has a long description (that stays on /catalog/{id}).
     *
     * @param array<string, mixed> $row
     * @return array{0:string,1:bool} href and whether to open a new window
     */
    public static function publicHref(array $row, $catalogHome, $siteHomeUrl, $homeContextId) {
        $cid = (int) ($row['context_id'] ?? 0);
        $homeId = (int) $homeContextId;
        $site = trim((string) $siteHomeUrl);
        $hasDetail = ! empty($row['has_detail']);
        if ( $cid > 0 && $homeId > 0 && $cid === $homeId && $site !== '' && ! $hasDetail ) {
            return array($site, false);
        }
        $link = trim((string) ($row['external_url'] ?? ''));
        if ( $link !== '' && ! $hasDetail ) {
            return array($link, ! empty($row['new_window']));
        }
        $id = (int) ($row['catalog_id'] ?? 0);
        return array(self::joinToolHome($catalogHome, (string) $id), false);
    }

    /**
     * Where Enter course goes. The Google site-login course opens the app home.
     */
    public static function enterUrl($context_id, $siteHomeUrl, $homeContextId) {
        $cid = (int) $context_id;
        $homeId = (int) $homeContextId;
        $site = trim((string) $siteHomeUrl);
        if ( $cid > 0 && $homeId > 0 && $cid === $homeId && $site !== '' ) {
            return $site;
        }
        if ( $cid > 0 ) {
            return Courses::courseHomeUrl($cid);
        }
        return '';
    }

    public static function normalizeHomeLink($url) {
        $url = trim((string) $url);
        if ( $url === '' || $url === '/' ) {
            return $url;
        }
        return rtrim($url, '/');
    }

    /**
     * Site waffle markup, including an optional catalog-url when CFG is on.
     */
    public static function coursesWidgetTag() {
        global $CFG;
        $root = rtrim((string) $CFG->wwwroot, '/');
        $a = htmlspecialchars($root, ENT_QUOTES, 'UTF-8');
        $extra = '';
        if ( self::showCourseCatalog() ) {
            $extra = ' catalog-url="'.$a.self::ROUTE.'"';
        }
        return '<tsugi-courses api-url="'.$a.'/courses/json"'
            .' all-url="'.$a.'/courses"'
            .' enter-url="'.$a.'/courses"'
            .$extra.'></tsugi-courses>';
    }

    /**
     * Published catalog rows with hrefs for the listing cards.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function listingRows() {
        $user_id = U::loggedInUserId();
        $rows = Catalog::markHomeEnrolled(CatalogRepository::listPublished($user_id));
        $home = self::catalogUrl();
        $site = self::siteHomeUrl();
        $homeId = CatalogRepository::homeContextId();
        foreach ( $rows as $i => $row ) {
            list($href, $newWindow) = self::publicHref($row, $home, $site, $homeId);
            $rows[$i]['href'] = $href;
            $rows[$i]['href_new_window'] = $newWindow;
        }
        return $rows;
    }

    /**
     * Split listing rows into enrolled vs everything else.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    public static function partitionRows(array $rows) {
        $enrolled = array();
        $other = array();
        foreach ( $rows as $row ) {
            if ( ! empty($row['enrolled']) ) {
                $enrolled[] = $row;
            } else {
                $other[] = $row;
            }
        }
        return array($enrolled, $other);
    }

    /**
     * Card listing markup only (no chrome). Used by /catalog and index.php.
     */
    public static function renderListing() {
        $rows = self::listingRows();
        list($enrolled_rows, $other_rows) = self::partitionRows($rows);
        include __DIR__ . '/templates/Catalog/index.inc.php';
    }

    public static function index(Application $app, Request $request) {
        global $OUTPUT;

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        self::renderListing();
        $OUTPUT->footer();
        return '';
    }

    public static function detail(Application $app, Request $request, $id) {
        global $OUTPUT;

        $user_id = U::loggedInUserId();
        $row = CatalogRepository::load($id, true, $user_id);
        if ( $row === null ) {
            return new Response('Catalog entry not found.', 404);
        }
        $marked = Catalog::markHomeEnrolled(array($row));
        $row = $marked[0];

        $logged_in = $user_id > 0;
        $can_enrol = $logged_in && Courses::isGoogleLoginSession();
        $tool = new self();
        $home = $tool->toolHome(self::ROUTE);
        $enrol_url = self::joinToolHome($home, ((int) $id).'/enrol');
        $cid = (int) ($row['context_id'] ?? 0);
        $homeId = CatalogRepository::homeContextId();
        $site_home = $homeId > 0 && $cid === $homeId;
        $enter_url = self::enterUrl($cid, self::siteHomeUrl(), $homeId);

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        include __DIR__ . '/templates/Catalog/detail.inc.php';
        $OUTPUT->footer();
        return '';
    }

    /**
     * Public catalog hero or course icon. Unpublished rows require site admin.
     */
    public static function image(Application $app, Request $request, $id, $kind) {
        $cid = (int) $id;
        if ( $cid < 1 || ($kind !== ContextImages::KIND_HERO && $kind !== ContextImages::KIND_ICON) ) {
            return new Response('', 404);
        }
        $meta = CatalogRepository::load($cid, false, 0);
        if ( $meta === null ) {
            return new Response('', 404);
        }
        $published = ! empty($meta['published']);
        $is_admin = isset($_SESSION['admin']) && $_SESSION['admin'] == 'yes';
        if ( ! $published && ! $is_admin ) {
            return new Response('', 404);
        }
        $row = $kind === ContextImages::KIND_HERO
            ? CatalogRepository::heroBlob($cid)
            : CatalogRepository::iconBlob($cid);
        return self::imageResponse($row, $kind, $cid, $published);
    }

    public static function enrol(Application $app, Request $request, $id) {
        $gate = Courses::gateResponse();
        if ( $gate ) {
            return $gate;
        }
        $tool = new self();
        $home = $tool->toolHome(self::ROUTE);
        $detail = U::addSession(self::joinToolHome($home, (string) (int) $id));
        $csrf = self::requireCsrf($detail);
        if ( $csrf ) {
            return $csrf;
        }

        $user_id = U::loggedInUserId();
        $row = CatalogRepository::load($id, true, $user_id);
        if ( $row === null ) {
            return new Response('Catalog entry not found.', 404);
        }
        $cid = (int) ($row['context_id'] ?? 0);
        if ( $cid < 1 ) {
            U::flashError(__('This catalog entry is a link, not a course you can join.'));
            return new RedirectResponse($detail);
        }
        $homeId = CatalogRepository::homeContextId();
        if ( ! CatalogRepository::enrollLearner($cid, $user_id) ) {
            U::flashError(__('Could not join that course.'));
            return new RedirectResponse($detail);
        }
        if ( $homeId > 0 && $cid === $homeId ) {
            $dest = self::siteHomeUrl();
            if ( $dest === '' ) {
                $dest = $detail;
            }
            return new RedirectResponse($dest);
        }
        $result = Courses::ensureActiveContext($cid);
        if ( $result !== true ) {
            return new Response($result, 400);
        }
        Courses::touchVisited($cid);
        return new RedirectResponse(Courses::courseHomeUrl($cid));
    }

    public static function getjson(Application $app) {
        $rows = Catalog::markHomeEnrolled(CatalogRepository::listPublished(U::loggedInUserId()));
        $entries = array();
        foreach ( $rows as $row ) {
            $entries[] = array(
                'catalog_id' => (int) ($row['catalog_id'] ?? 0),
                'title' => $row['title'] ?? '',
                'short_description' => $row['short_description'] ?? '',
                'hero_url' => $row['hero_url'] ?? '',
                'icon_url' => $row['icon_url'] ?? '',
                'external_url' => $row['external_url'] ?? null,
                'context_id' => isset($row['context_id']) ? (int) $row['context_id'] : 0,
                'enrolled' => ! empty($row['enrolled']),
            );
        }
        return \response()->json(array('status' => 'success', 'entries' => $entries));
    }

    /**
     * @param array{bytes:string,mime:string,updated_at:?string}|null $row
     */
    private static function imageResponse($row, $kind, $catalog_id, $published = true) {
        $cache = $published ? 'public, max-age=86400' : 'private, no-store';
        $cache404 = $published ? 'public, max-age=60' : 'private, no-store';
        if ( ! is_array($row) || ! isset($row['bytes']) || ! is_string($row['bytes']) || $row['bytes'] === '' ) {
            $response = new Response('', 404);
            $response->headers->set('Cache-Control', $cache404);
            return $response;
        }
        $etag = '"'.sha1($catalog_id.'|'.$kind.'|'.strlen($row['bytes']).'|'.(string) ($row['updated_at'] ?? '')).'"';
        $inm = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if ( is_string($inm) && $inm !== '' && hash_equals($etag, $inm) ) {
            $response = new Response('', 304);
            $response->setEtag(trim($etag, '"'));
            $response->headers->set('Cache-Control', $cache);
            return $response;
        }
        $response = new Response($row['bytes'], 200);
        $response->headers->set('Content-Type', isset($row['mime']) ? $row['mime'] : ContextImages::MIME);
        $response->headers->set('Content-Length', (string) strlen($row['bytes']));
        $response->headers->set('Cache-Control', $cache);
        $response->setEtag(trim($etag, '"'));
        return $response;
    }
}

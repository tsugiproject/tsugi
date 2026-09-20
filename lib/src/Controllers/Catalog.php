<?php

namespace Tsugi\Controllers;

use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Tsugi\Core\ContextImages;
use Tsugi\Core\LTIX;
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
        return ! empty($CFG->getExtension('show_course_catalog', false));
    }

    public static function catalogUrl() {
        global $CFG;
        return rtrim((string) $CFG->wwwroot, '/').self::ROUTE;
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

    public static function index(Application $app, Request $request) {
        global $OUTPUT, $PDOX;

        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }
        if ( ! CatalogRepository::tableExists() ) {
            return new Response('Course catalog is not installed. Run Upgrade Database.', 503);
        }

        $user_id = U::loggedInUserId();
        $rows = CatalogRepository::listPublished($user_id);
        $logged_in = $user_id > 0;
        $tool = new self();
        $home = $tool->toolHome(self::ROUTE);
        foreach ( $rows as $i => $row ) {
            $id = (int) ($row['catalog_id'] ?? 0);
            $rows[$i]['href'] = self::joinToolHome($home, (string) $id);
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        include __DIR__ . '/templates/Catalog/index.inc.php';
        $OUTPUT->footer();
        return '';
    }

    public static function detail(Application $app, Request $request, $id) {
        global $OUTPUT, $PDOX;

        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }
        if ( ! CatalogRepository::tableExists() ) {
            return new Response('Course catalog is not installed. Run Upgrade Database.', 503);
        }

        $user_id = U::loggedInUserId();
        $row = CatalogRepository::load($id, true, $user_id);
        if ( $row === null ) {
            return new Response('Catalog entry not found.', 404);
        }

        $logged_in = $user_id > 0;
        $can_enrol = $logged_in && Courses::isGoogleLoginSession();
        $tool = new self();
        $home = $tool->toolHome(self::ROUTE);
        $enrol_url = self::joinToolHome($home, ((int) $id).'/enrol');
        $enter_url = '';
        $cid = (int) ($row['context_id'] ?? 0);
        if ( $cid > 0 ) {
            $enter_url = Courses::courseHomeUrl($cid);
        }

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
        global $PDOX;

        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }
        $cid = (int) $id;
        if ( $cid < 1 || ($kind !== ContextImages::KIND_HERO && $kind !== ContextImages::KIND_ICON) ) {
            return new Response('', 404);
        }
        if ( ! CatalogRepository::tableExists() ) {
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
        return self::imageResponse($row, $kind, $cid);
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
        if ( $homeId > 0 && $cid === $homeId ) {
            U::flashError(__('The site home course is not joined from the catalog.'));
            return new RedirectResponse($detail);
        }
        if ( ! CatalogRepository::enrollLearner($cid, $user_id) ) {
            U::flashError(__('Could not join that course.'));
            return new RedirectResponse($detail);
        }
        $result = Courses::ensureActiveContext($cid);
        if ( $result !== true ) {
            return new Response($result, 400);
        }
        Courses::touchVisited($cid);
        return new RedirectResponse(Courses::courseHomeUrl($cid));
    }

    public static function getjson(Application $app) {
        if ( ! CatalogRepository::tableExists() ) {
            return \response()->json(array('status' => 'success', 'entries' => array()));
        }
        $rows = CatalogRepository::listPublished(U::loggedInUserId());
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
    private static function imageResponse($row, $kind, $catalog_id) {
        if ( ! is_array($row) || ! isset($row['bytes']) || ! is_string($row['bytes']) || $row['bytes'] === '' ) {
            $response = new Response('', 404);
            $response->headers->set('Cache-Control', 'public, max-age=60');
            return $response;
        }
        $etag = '"'.sha1($catalog_id.'|'.$kind.'|'.strlen($row['bytes']).'|'.(string) ($row['updated_at'] ?? '')).'"';
        $inm = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if ( is_string($inm) && $inm !== '' && hash_equals($etag, $inm) ) {
            $response = new Response('', 304);
            $response->setEtag(trim($etag, '"'));
            $response->headers->set('Cache-Control', 'public, max-age=86400');
            return $response;
        }
        $response = new Response($row['bytes'], 200);
        $response->headers->set('Content-Type', isset($row['mime']) ? $row['mime'] : ContextImages::MIME);
        $response->headers->set('Content-Length', (string) strlen($row['bytes']));
        $response->headers->set('Cache-Control', 'public, max-age=86400');
        $response->setEtag(trim($etag, '"'));
        return $response;
    }
}

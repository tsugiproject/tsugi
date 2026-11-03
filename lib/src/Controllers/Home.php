<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Core\LTIX;
use Tsugi\Lumos\Application;
use Symfony\Component\HttpFoundation\Request;

use Tsugi\Core\ReqScope;
use Tsugi\Services\Pages\PageRepository;

/**
 * Course home: site-wide at /home, course-mounted at /courses/{id}/home.
 *
 * A published main page replaces the default copy. Keep HTTP and chrome here;
 * put domain logic in Tsugi\Services\Home as this grows. Nested dispatch
 * from Courses keeps REQUEST_URI prefixed, so isCourseRoute() and toolHome() work.
 *
 * Parent menus (site URLs only):
 * if ( \Tsugi\Controllers\Home::showInMenu() ) {
 *     $set->addLeft('Home', rtrim($CFG->apphome, '/') . '/home');
 * }
 */
class Home extends Tool {

    const ROUTE = '/home';
    const NAME = 'Home';
    const REDIRECT = 'tsugi_controllers_home';

    /**
     * True when the current user may open course Home (logged in with a context).
     */
    public static function showInMenu() {
        return ReqScope::isLoggedIn() && ReqScope::currentContextId() !== 0;
    }

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Home@index');
        $app->router->get($prefix.'/', 'Home@index');
        $app->router->get('/'.self::REDIRECT, 'Home@index');
    }

    public function index(Request $request)
    {
        $this->requireAuth();
        LTIX::getConnection();
        $this->render('index.inc.php', array(
            'course_title' => $this->courseTitle(),
            'main_page' => $this->mainPageForHome(),
        ));
    }

    /**
     * Main page that should stand in for the default home copy.
     *
     * Students only see a published main page. Instructors also see a draft,
     * so they can tell the home replacement is in place before students can.
     *
     * @return array{title:string,body_html:string,published:int,edit_url:string}|null
     */
    protected function mainPageForHome() {
        $context_id = ReqScope::currentContextId();
        if ( $context_id < 1 ) {
            return null;
        }
        $instructor = $this->isInstructor();
        $page = PageRepository::loadMain($context_id, ! $instructor);
        if ( ! is_array($page) ) {
            return null;
        }
        $title = isset($page['title']) ? trim((string) $page['title']) : '';
        $body = isset($page['body']) ? (string) $page['body'] : '';
        if ( $title === '' && trim($body) === '' ) {
            return null;
        }
        $edit_url = '';
        if ( $instructor && ! empty($page['page_id']) ) {
            $edit_url = $this->toolHome(Pages::ROUTE).'/edit/'.((int) $page['page_id']);
        }
        return array(
            'title' => $title,
            'body_html' => PageRepository::expandHtml(
                $body,
                $this->courseFileBaseUrl(self::ROUTE),
                self::courseLocalPrefixes()
            ),
            'published' => empty($page['published']) ? 0 : 1,
            'edit_url' => $edit_url,
        );
    }

    /**
     * Current course name for the Home heading.
     *
     * Session first (set on course switch / LTI), then lti_context.title.
     * Do not fall back to $CFG->context_title — that is the site-home course.
     */
    protected function courseTitle() {
        global $CFG, $PDOX;

        $title = U::get($_SESSION, 'context_title');
        if ( is_string($title) && trim($title) !== '' ) {
            return trim($title);
        }
        $context_id = ReqScope::currentContextId();
        if ( $context_id < 1 ) {
            return '';
        }
        $row = $PDOX->rowDie(
            "SELECT title FROM {$CFG->dbprefix}lti_context WHERE context_id = :CID",
            array(':CID' => $context_id)
        );
        if ( ! is_array($row) || ! isset($row['title']) ) {
            return '';
        }
        return trim((string) $row['title']);
    }

    /**
     * Shared chrome for Home screens. Future actions call this instead of
     * duplicating header/nav/footer.
     *
     * @param string $template File under templates/Home/
     * @param array $vars Extracted into the template scope
     */
    protected function render($template, array $vars = array()) {
        global $OUTPUT;

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        if ( $vars ) {
            extract($vars, EXTR_SKIP);
        }
        include __DIR__ . '/templates/Home/' . $template;
        $OUTPUT->footer();
    }
}

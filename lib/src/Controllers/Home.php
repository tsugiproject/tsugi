<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Core\LTIX;
use Tsugi\Lumos\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Tsugi\Core\ContextImages;
use Tsugi\Core\ReqScope;
use Tsugi\Services\Files\FileRepository;
use Tsugi\Services\Home\HomeLayout;
use Tsugi\Services\Pages\PageRepository;
use Tsugi\Util\CCFileBase;

/**
 * Course home: site-wide at /home, course-mounted at /courses/{id}/home.
 *
 * Instructors pick an ordered set of blocks (hero, pages, course files, the
 * month calendar, and the due-date list) with the gear on this screen. Keep
 * HTTP and chrome here. Nested dispatch from Courses keeps REQUEST_URI
 * prefixed, so isCourseRoute() and toolHome() work.
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
        $app->router->post($prefix, 'Home@save');
        $app->router->post($prefix.'/', 'Home@save');
        $app->router->get('/'.self::REDIRECT, 'Home@index');
    }

    public function index(Request $request)
    {
        $this->requireAuth();
        LTIX::getConnection();
        $context_id = ReqScope::currentContextId();
        $instructor = $this->isInstructor();
        $heroUrl = $this->courseHeroUrl();
        $layout = HomeLayout::load($context_id, $heroUrl !== '');
        $hero = HomeLayout::block($layout, 'hero');
        $calendar = HomeLayout::block($layout, 'calendar');
        $dues = HomeLayout::block($layout, 'dues');
        $pageBlocks = array();
        foreach ( $layout['blocks'] as $block ) {
            if ( ($block['type'] ?? '') !== 'page' ) {
                continue;
            }
            $pid = (int) ($block['page_id'] ?? 0);
            if ( $pid < 1 || array_key_exists($pid, $pageBlocks) ) {
                continue;
            }
            $loaded = $this->pageForHome($pid);
            if ( is_array($loaded) ) {
                $pageBlocks[$pid] = $loaded;
            }
        }
        $homeUrl = U::addSession($this->toolHome(self::ROUTE));
        $calendarHtml = '';
        $duesHtml = '';
        if ( (is_array($calendar) && ! empty($calendar['on'])) || (is_array($dues) && ! empty($dues['on'])) ) {
            $cal = new Calendar();
            if ( is_array($calendar) && ! empty($calendar['on']) ) {
                $calendarHtml = $cal->monthHtml($request, $homeUrl, true);
            }
            if ( is_array($dues) && ! empty($dues['on']) ) {
                $duesHtml = $cal->listHtml(true);
            }
        }
        $pages = $instructor ? PageRepository::listForPicker($context_id, false) : array();
        $courseFiles = FileRepository::listForHome($context_id);
        $fileBlocks = array();
        foreach ( $courseFiles as $file ) {
            $url = $this->courseFileUrl($file['path']);
            if ( $url === '' ) {
                continue;
            }
            $fileBlocks[$file['path']] = array(
                'name' => $file['name'],
                'url' => U::addSession($url),
                'image' => ! empty($file['image']),
            );
        }
        $heroPlace = is_array($hero) ? (string) ($hero['place'] ?? 'none') : 'none';
        $this->render('index.inc.php', array(
            'course_title' => $this->courseTitle(),
            'layout' => $layout,
            'can_configure' => $instructor,
            'save_url' => $homeUrl,
            'pages' => is_array($pages) ? $pages : array(),
            'page_blocks' => $pageBlocks,
            'files' => $courseFiles,
            'file_blocks' => $fileBlocks,
            'hero_url' => $heroPlace !== 'none' ? $heroUrl : '',
            'calendar_html' => $calendarHtml,
            'dues_html' => $duesHtml,
        ));
    }

    /**
     * Save the instructor's home layout and return to this page.
     */
    public function save(Request $request)
    {
        $this->requireAuth();
        LTIX::getConnection();
        $home = U::addSession($this->toolHome(self::ROUTE));
        if ( ! $this->isInstructor() ) {
            U::flashError(__('Only instructors can configure the home page.'));
            return new RedirectResponse($home);
        }
        $csrf = self::requireCsrf($home);
        if ( $csrf ) {
            return $csrf;
        }
        $context_id = ReqScope::currentContextId();
        $pages = PageRepository::listForPicker($context_id, false);
        $ids = array();
        if ( is_array($pages) ) {
            foreach ( $pages as $page ) {
                if ( isset($page['page_id']) ) {
                    $ids[] = (int) $page['page_id'];
                }
            }
        }
        $paths = array();
        foreach ( FileRepository::listForHome($context_id) as $file ) {
            $paths[] = $file['path'];
        }
        HomeLayout::save($context_id, HomeLayout::fromPost($_POST, $ids, $paths));
        U::flashSuccess(__('Home page updated.'));
        return new RedirectResponse($home);
    }

    /**
     * Course-mounted URL for a Files path (/courses/{id}/files/{folder}/{name}).
     *
     * The path stays stable when the file bytes are replaced.
     *
     * @param string $path
     * @return string
     */
    protected function courseFileUrl($path) {
        $href = FileRepository::hrefForPath($path);
        if ( ! is_string($href) || $href === '' || strpos($href, Files::ROUTE) !== 0 ) {
            return '';
        }
        return rtrim($this->toolHome(Files::ROUTE), '/') . substr($href, strlen(Files::ROUTE));
    }

    /**
     * Versioned course hero URL, or empty when this course has no hero image.
     *
     * @return string
     */
    protected function courseHeroUrl() {
        $context_id = ReqScope::currentContextId();
        if ( $context_id < 1 ) {
            return '';
        }
        $meta = ContextImages::metadata($context_id);
        return ContextImages::servedUrl(
            $context_id,
            ContextImages::KIND_HERO,
            $meta['hero_bytes'] ?? 0,
            $meta['hero_updated_at'] ?? null
        );
    }

    /**
     * One course page chosen for the home layout.
     *
     * Students only see it after it is published. Instructors also see a draft.
     *
     * @param int $page_id
     * @return array{title:string,body_html:string,published:int,edit_url:string}|null
     */
    protected function pageForHome($page_id) {
        $context_id = ReqScope::currentContextId();
        $page_id = (int) $page_id;
        if ( $context_id < 1 || $page_id < 1 ) {
            return null;
        }
        $instructor = $this->isInstructor();
        $page = PageRepository::load($page_id, $context_id);
        if ( ! is_array($page) ) {
            return null;
        }
        if ( ! $instructor && empty($page['published']) ) {
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
        $pages_base = rtrim($this->toolHome(Pages::ROUTE), '/').'/';
        return array(
            'title' => $title,
            'body_html' => PageRepository::expandHtml(
                self::rebaseRelativePageUrls($body, $pages_base),
                $this->courseFileBaseUrl(Pages::ROUTE),
                self::courseLocalPrefixes()
            ),
            'published' => empty($page['published']) ? 0 : 1,
            'edit_url' => $edit_url,
        );
    }

    /**
     * Point plain relative links at the course Pages URL.
     *
     * A link such as href="next" or srcset="small.png 1x" resolves under
     * /pages/{key} and would resolve under /home when this body is rendered
     * there. Absolute, root-relative, fragment, and query-only URLs stay as
     * stored. srcset width and density descriptors stay with their candidate.
     *
     * @param string $html
     * @param string $pagesBase Pages URL with a trailing slash
     * @return string
     */
    private static function rebaseRelativePageUrls($html, $pagesBase) {
        if ( ! is_string($html) || $html === '' || ! is_string($pagesBase) || $pagesBase === '' ) {
            return is_string($html) ? $html : '';
        }
        $rewritten = preg_replace_callback(
            '/\b(href|src|srcset)\s*=\s*(["\'])([^"\']*)\2/i',
            function ($match) use ($pagesBase) {
                if ( strtolower($match[1]) === 'srcset' ) {
                    $resolved = self::rebaseSrcset($pagesBase, $match[3]);
                } else {
                    $resolved = self::resolveAgainstPages($pagesBase, $match[3]);
                }
                if ( $resolved === null ) {
                    return $match[0];
                }
                return $match[1].'='.$match[2].$resolved.$match[2];
            },
            $html
        );
        return is_string($rewritten) ? $rewritten : $html;
    }

    /**
     * Rebase each relative srcset candidate. Leave the descriptor on that candidate.
     *
     * @param string $pagesBase
     * @param string $value
     * @return string|null Null when nothing in the list is path-relative
     */
    private static function rebaseSrcset($pagesBase, $value) {
        $parts = explode(',', $value);
        $out = array();
        $changed = false;
        foreach ( $parts as $part ) {
            $part = trim($part);
            if ( $part === '' ) {
                continue;
            }
            if ( preg_match('/^(.*?)(?:\s+([\d.]+x|\d+w))?$/i', $part, $match) ) {
                $url = trim($match[1]);
                $desc = isset($match[2]) ? $match[2] : '';
                $resolved = self::resolveAgainstPages($pagesBase, $url);
                if ( $resolved !== null ) {
                    $changed = true;
                    $url = $resolved;
                }
                $out[] = $desc !== '' ? ($url.' '.$desc) : $url;
            } else {
                $out[] = $part;
            }
        }
        if ( ! $changed ) {
            return null;
        }
        return implode(', ', $out);
    }

    /**
     * Resolve a path-relative URL against the Pages directory, or null to leave it.
     *
     * @param string $pagesBase
     * @param string $url
     * @return string|null
     */
    private static function resolveAgainstPages($pagesBase, $url) {
        $url = trim($url);
        if ( $url === '' || $url[0] === '#' || $url[0] === '?' || $url[0] === '/' ) {
            return null;
        }
        if ( preg_match('#^[a-z][a-z0-9+.-]*:#i', $url) ) {
            return null;
        }
        foreach ( CCFileBase::TOKEN_ALIASES as $token ) {
            if ( str_contains($url, $token) || str_contains($url, rawurlencode($token)) ) {
                return null;
            }
        }
        $fragment = '';
        $query = '';
        $path = $url;
        $hashPos = strpos($path, '#');
        if ( $hashPos !== false ) {
            $fragment = substr($path, $hashPos);
            $path = substr($path, 0, $hashPos);
        }
        $queryPos = strpos($path, '?');
        if ( $queryPos !== false ) {
            $query = substr($path, $queryPos);
            $path = substr($path, 0, $queryPos);
        }
        if ( $path === '' ) {
            return null;
        }

        $origin = '';
        $basePath = $pagesBase;
        if ( preg_match('#^https?://#i', $pagesBase) ) {
            $parts = parse_url($pagesBase);
            if ( ! is_array($parts) || empty($parts['host']) ) {
                return null;
            }
            $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
            $origin = $scheme.'://'.strtolower((string) $parts['host']);
            if ( ! empty($parts['port']) ) {
                $origin .= ':'.$parts['port'];
            }
            $basePath = isset($parts['path']) ? (string) $parts['path'] : '/';
        }
        if ( $basePath === '' || $basePath[0] !== '/' ) {
            $basePath = '/'.$basePath;
        }
        if ( ! str_ends_with($basePath, '/') ) {
            $basePath .= '/';
        }
        $segments = explode('/', trim($basePath, '/'));
        foreach ( explode('/', $path) as $part ) {
            if ( $part === '' || $part === '.' ) {
                continue;
            }
            if ( $part === '..' ) {
                array_pop($segments);
                continue;
            }
            $segments[] = $part;
        }
        return $origin.'/'.implode('/', $segments).$query.$fragment;
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

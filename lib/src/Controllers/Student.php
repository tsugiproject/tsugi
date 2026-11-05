<?php

namespace Tsugi\Controllers;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Tsugi\Core\LTIX;
use Tsugi\Core\Manifest;
use Tsugi\Core\ReqScope;
use Tsugi\Lumos\Application;
use Tsugi\Services\StudentView\StudentView;
use Tsugi\Util\U;

/**
 * View this manifest course as one of four course-local test students.
 */
class Student extends Tool {

    const ROUTE = StudentView::ROUTE;

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Student@show');
        $app->router->get($prefix.'/', 'Student@show');
        $app->router->post($prefix, 'Student@show');
        $app->router->post($prefix.'/', 'Student@show');
        $app->router->post($prefix.'/leave', 'Student@leave');
        $app->router->post($prefix.'/leave/', 'Student@leave');
    }

    public function show(Request $request) {
        global $OUTPUT;

        LTIX::getConnection();
        $contextId = ReqScope::currentContextId();
        $home = Courses::courseUrlPrefix($contextId).'/home';
        if ( StudentView::isActive() ) {
            return new RedirectResponse(U::addSession($home));
        }
        $this->requireInstructor($home);
        if ( Manifest::idForContext($contextId) < 1 ) {
            U::flashError(htmlspecialchars('Student view is for courses that use a manifest.', ENT_QUOTES, 'UTF-8'));
            return new RedirectResponse(U::addSession($home));
        }

        $here = $this->toolHome(self::ROUTE);
        if ( $request->isMethod('POST') ) {
            if ( ! $this->checkCsrf() ) {
                return new RedirectResponse(U::addSession($here));
            }
            return $this->posted($contextId, $request, $here, $home);
        }

        $rows = StudentView::rows($contextId);
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        echo '<div class="container">';
        include __DIR__ . '/templates/Student/index.inc.php';
        echo '</div>';
        $OUTPUT->footer();
    }

    public function leave(Request $request) {
        if ( ! self::checkCsrf() ) {
            $cid = StudentView::activeContextId();
            if ( $cid < 1 ) {
                $cid = ReqScope::currentContextId();
            }
            $back = Courses::courseUrlPrefix($cid).'/home';
            return new RedirectResponse(U::addSession($back));
        }
        $cid = StudentView::activeContextId();
        if ( $cid < 1 ) {
            $cid = ReqScope::currentContextId();
        }
        $was = StudentView::isActive();
        StudentView::restoreInstructor();
        if ( $was ) {
            U::flashSuccess(htmlspecialchars('You are back in your instructor account.', ENT_QUOTES, 'UTF-8'));
        }
        $dest = $cid > 0
            ? Courses::courseUrlPrefix($cid).self::ROUTE
            : self::configuredHomeUrl();
        return new RedirectResponse(U::addSession($dest));
    }

    /**
     * @param int $contextId
     * @param string $here
     * @param string $home
     * @return RedirectResponse
     */
    private function posted($contextId, Request $request, $here, $home) {
        $action = (string) $request->request->get('action', '');
        $slug = (string) $request->request->get('slug', '');
        if ( $action === 'enter' ) {
            $result = StudentView::enter($contextId, $slug);
            if ( empty($result['ok']) ) {
                U::flashError(htmlspecialchars((string) ($result['error'] ?? 'Could not enter student view.'), ENT_QUOTES, 'UTF-8'));
                return new RedirectResponse(U::addSession($here));
            }
            return new RedirectResponse(U::addSession($home));
        }
        if ( $action === 'reset' ) {
            $result = StudentView::reset($contextId, $slug);
            $name = htmlspecialchars((string) ($result['name'] ?? 'That student'), ENT_QUOTES, 'UTF-8');
            if ( empty($result['ok']) ) {
                U::flashError(htmlspecialchars((string) ($result['error'] ?? 'Could not reset that student.'), ENT_QUOTES, 'UTF-8'));
            } else {
                U::flashSuccess($name.' has been reset. That account and its course data are gone.');
            }
            return new RedirectResponse(U::addSession($here));
        }
        U::flashError(htmlspecialchars('That action is not available.', ENT_QUOTES, 'UTF-8'));
        return new RedirectResponse(U::addSession($here));
    }
}

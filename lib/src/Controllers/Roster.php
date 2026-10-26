<?php

namespace Tsugi\Controllers;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Tsugi\Core\LTIX;
use Tsugi\Core\ReqScope;
use Tsugi\Lumos\Application;
use Tsugi\Services\Plus\RosterSync;
use Tsugi\Util\U;

/**
 * Course roster, and the Plus pull log when this course came from an LMS.
 */
class Roster extends Tool {

    const ROUTE = '/roster';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Roster@show');
        $app->router->get($prefix.'/', 'Roster@show');
        $app->router->post($prefix, 'Roster@show');
        $app->router->post($prefix.'/', 'Roster@show');
        $app->router->get($prefix.'/log', 'Roster@log');
        $app->router->get($prefix.'/log/', 'Roster@log');
        $app->router->post($prefix.'/log', 'Roster@log');
        $app->router->post($prefix.'/log/', 'Roster@log');
    }

    public function show(Request $request) {
        global $OUTPUT;

        LTIX::getConnection();
        $contextId = ReqScope::currentContextId();
        $home = Courses::courseUrlPrefix($contextId).'/home';
        $this->requireInstructor($home);

        $here = $this->toolHome(self::ROUTE);
        $plus = RosterSync::courseIsPlus($contextId);
        if ( $request->isMethod('POST') ) {
            if ( ! $this->tokenMatches($request) ) {
                U::flashError(htmlspecialchars('The form expired. Try again.', ENT_QUOTES, 'UTF-8'));
            } else {
                $this->applyDeleted($contextId, $request);
            }
            return new RedirectResponse(U::addSession($here));
        }

        $members = RosterSync::members($contextId);
        $selfId = ReqScope::loggedInUserId();
        $token = $this->token();
        $tab = 'people';
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        echo '<main class="container" id="main-content">';
        echo '<h1>Roster</h1>';
        include __DIR__ . '/templates/Roster/tabs.inc.php';
        include __DIR__ . '/templates/Roster/people.inc.php';
        echo '</main>';
        $OUTPUT->footer();
    }

    public function log(Request $request) {
        global $OUTPUT;

        LTIX::getConnection();
        $contextId = ReqScope::currentContextId();
        $home = Courses::courseUrlPrefix($contextId).'/home';
        $this->requireInstructor($home);

        $here = $this->toolHome(self::ROUTE);
        if ( ! RosterSync::courseIsPlus($contextId) ) {
            return new RedirectResponse(U::addSession($here));
        }

        if ( $request->isMethod('POST') ) {
            if ( ! $this->tokenMatches($request) ) {
                U::flashError(htmlspecialchars('The refresh form expired. Try again.', ENT_QUOTES, 'UTF-8'));
            } else {
                $result = RosterSync::sync($contextId, ReqScope::loggedInUserId(), true, true);
                $message = htmlspecialchars((string) ($result['message'] ?? ''), ENT_QUOTES, 'UTF-8');
                if ( ! empty($result['ok']) ) {
                    U::flashSuccess($message);
                } else {
                    U::flashError($message);
                }
            }
            return new RedirectResponse(U::addSession($here.'/log'));
        }

        $status = RosterSync::status($contextId);
        $token = $this->token();
        $plus = true;
        $tab = 'log';
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        echo '<main class="container" id="main-content">';
        echo '<h1>Roster</h1>';
        include __DIR__ . '/templates/Roster/tabs.inc.php';
        include __DIR__ . '/templates/Roster/index.inc.php';
        echo '</main>';
        $OUTPUT->footer();
    }

    /**
     * @param int $contextId
     */
    private function applyDeleted($contextId, Request $request) {
        $membershipId = (int) $request->request->get('membership_id', 0);
        $deleted = (string) $request->request->get('deleted', '') === '1';
        if ( $membershipId < 1 ) {
            U::flashError(htmlspecialchars('That person is not in this course.', ENT_QUOTES, 'UTF-8'));
            return;
        }
        $name = 'That person';
        foreach ( RosterSync::members($contextId) as $member ) {
            if ( (int) ($member['membership_id'] ?? 0) !== $membershipId ) {
                continue;
            }
            if ( (int) ($member['user_id'] ?? 0) === ReqScope::loggedInUserId() ) {
                U::flashError(htmlspecialchars('You stay in this course.', ENT_QUOTES, 'UTF-8'));
                return;
            }
            $display = trim((string) ($member['displayname'] ?? ''));
            if ( $display !== '' ) {
                $name = $display;
            }
            break;
        }
        if ( ! RosterSync::setDeleted($contextId, $membershipId, $deleted) ) {
            U::flashError(htmlspecialchars('That person is not in this course.', ENT_QUOTES, 'UTF-8'));
            return;
        }
        $verb = $deleted ? ' is removed from this course.' : ' is back in this course.';
        U::flashSuccess(htmlspecialchars($name.$verb, ENT_QUOTES, 'UTF-8'));
    }

    private function token() {
        if ( ! isset($_SESSION['plus_roster_token']) || ! is_string($_SESSION['plus_roster_token']) || $_SESSION['plus_roster_token'] === '' ) {
            $_SESSION['plus_roster_token'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['plus_roster_token'];
    }

    private function tokenMatches(Request $request) {
        $posted = (string) $request->request->get('token', '');
        $expected = isset($_SESSION['plus_roster_token']) ? (string) $_SESSION['plus_roster_token'] : '';
        return $expected !== '' && hash_equals($expected, $posted);
    }

}

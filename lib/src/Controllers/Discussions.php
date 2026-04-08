<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Util\LTI;
use Tsugi\Core\LTIX;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class Discussions {

    const ROUTE = '/discussions';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Discussions@get');
        $app->router->get($prefix.'/', 'Discussions@get');
        $app->router->get($prefix.'/json', 'Discussions@json');
        $app->router->post($prefix.'/mark-read', 'Discussions@markRead');
        $app->router->get($prefix.'_launch/{anchor}', function(Request $request, $anchor = null) use ($app) {
            return Discussions::launch($app, $anchor);
        });
    }

    public function get(Request $request)
    {
        global $CFG, $OUTPUT;

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }

        // Load the Lesson
        $l = new \Tsugi\UI\Lessons($CFG->lessons);

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $menu = false;
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        echo('<main class="container" id="main-content">');
        $l->renderDiscussions(false);
        echo('</main>');
        $OUTPUT->footer();


    }

    public static function launch(Application $app, $anchor=null)
    {
        global $CFG;
        $tsugi = $app['tsugi'];

        $path = U::rest_path();
        $redirect_path = U::addSession($path->parent);
        if ( $redirect_path == '') $redirect_path = '/';

        if ( ! isset($CFG->lessons) ) {
            $app->tsugiFlashError(__('Cannot find lessons.json ($CFG->lessons)'));
            return new RedirectResponse($redirect_path);
        }

        /// Load the Lesson
        $l = new \Tsugi\UI\Lessons($CFG->lessons);
        if ( ! $l ) {
            $app->tsugiFlashError(__('Cannot load lessons.'));
            return new RedirectResponse($redirect_path);
        }

        $lti = $l->getLtiByRlid($anchor);
        if ( ! $lti ) {
            $app->tsugiFlashError(__('Cannot find lti resource link id'));
            return new RedirectResponse($redirect_path);
        }

        // Check that the session has the minimums...
        if ( U::get($_SESSION,'secret') && U::get($_SESSION,'context_key')
                && U::get($_SESSION,'user_key') && U::get($_SESSION,'displayname') && U::get($_SESSION,'email') )
        {
            // All good
        } else {
            $app->tsugiFlashError(__('Missing session data required for launch'));
            return new RedirectResponse($redirect_path);
        }

        $key = isset($_SESSION['oauth_consumer_key']) ? $_SESSION['oauth_consumer_key'] : false;
        $secret = false;
        if ( isset($_SESSION['secret']) ) {
            $secret = LTIX::decrypt_secret($_SESSION['secret']);
        }

        $resource_link_id = $lti->resource_link_id;
        $parms = array(
            'lti_message_type' => 'basic-lti-launch-request',
            'resource_link_id' => $resource_link_id,
            'resource_link_title' => $lti->title,
            'tool_consumer_info_product_family_code' => 'tsugi',
            'tool_consumer_info_version' => '1.1',
            'context_id' => $_SESSION['context_key'],
            'context_label' => $CFG->context_title,
            'context_title' => $CFG->context_title,
            'user_id' => $_SESSION['user_key'],
            'lis_person_name_full' => $_SESSION['displayname'],
            'lis_person_contact_email_primary' => $_SESSION['email'],
            'roles' => 'Learner'
        );
        if ( isset($_SESSION['avatar']) ) $parms['user_image'] = $_SESSION['avatar'];

        if ( isset($lti->custom) ) {
            foreach($lti->custom as $custom) {
                if ( isset($custom->value) ) {
                    $parms['custom_'.$custom->key] = $custom->value;
                }
                if ( isset($custom->json) ) {
                    $parms['custom_'.$custom->key] = json_encode($custom->json);
                }
            }
        }

        $return_url = $path->parent . '/' . str_replace('_launch', '', $path->controller) ;
        $parms['launch_presentation_return_url'] = $return_url;

        $sess_key = 'tsugi_top_nav_'.$CFG->wwwroot;
        if ( isset($_SESSION[$sess_key]) ) {
            $parms['ext_tsugi_top_nav'] = $_SESSION[$sess_key];
        }

        $form_id = "tsugi_form_id_".bin2Hex(openssl_random_pseudo_bytes(4));
        $parms['ext_lti_form_id'] = $form_id;

        $endpoint = $lti->launch;
        \Tsugi\UI\Lessons::absolute_url_ref($endpoint);
        $parms = LTI::signParameters($parms, $endpoint, "POST", $key, $secret,
            "Finish Launch", $CFG->wwwroot, $CFG->servicename);

        $content = LTI::postLaunchHTML($parms, $endpoint, false /*debug */);
        print($content);
        return "";
    }

    public function json(Request $request)
    {
        global $CFG, $PDOX;

        if ( ! U::get($_SESSION, 'id') || ! U::get($_SESSION, 'context_id') ) {
            return new JsonResponse(array('status' => 'error', 'detail' => 'Must be logged in with context'), 401);
        }

        LTIX::getConnection();

        $context_id = intval($_SESSION['context_id']);
        $user_id = intval($_SESSION['id']);

        $has_mentions = $this->tableExists($CFG->dbprefix.'tdiscus_mention');
        $has_participation = $this->tableExists($CFG->dbprefix.'tdiscus_user_thread_participation');

        $rows = $PDOX->allRowsDie("SELECT L.link_id, L.link_key
            FROM {$CFG->dbprefix}lti_link L
            WHERE L.context_id = :CID
              AND EXISTS (
                SELECT 1 FROM {$CFG->dbprefix}tdiscus_thread T
                WHERE T.link_id = L.link_id
              )
            ORDER BY L.link_id",
            array(':CID' => $context_id)
        );

        $include_participating_in_main = intval(U::get($_GET, 'include_participating', 0)) > 0;
        $include_participation_as_personal = intval(U::get($_GET, 'participation_personal', 0)) > 0;

        $by_discussion = array();
        $totals = array('personal' => 0, 'participating' => 0, 'global' => 0, 'main_badge' => 0);
        foreach ( $rows as $row ) {
            $counts = $this->rollupsForLink(
                intval($row['link_id']),
                $user_id,
                $has_mentions,
                $has_participation,
                $include_participation_as_personal
            );
            $main_badge = intval($counts['personal']);
            if ( $include_participating_in_main ) {
                $main_badge += intval($counts['participating']);
            }

            $by_discussion[] = array(
                'link_id' => intval($row['link_id']),
                'resource_link_id' => $row['link_key'],
                'badge' => $counts,
                'main_badge' => $main_badge,
            );

            $totals['personal'] += intval($counts['personal']);
            $totals['participating'] += intval($counts['participating']);
            $totals['global'] += intval($counts['global']);
            $totals['main_badge'] += $main_badge;
        }

        return new JsonResponse(array(
            'status' => 'success',
            'totals' => $totals,
            'discussions' => $by_discussion,
            'config' => array(
                'include_participating_in_main_badge' => $include_participating_in_main ? 1 : 0,
                'include_participation_as_personal' => $include_participation_as_personal ? 1 : 0,
            ),
        ));
    }

    /**
     * Mark every discussion thread in the current course context as read for the logged-in user.
     * Aligns tdiscus_user_thread.comments with each thread and sets read_at (same idea as Threads::threadMarkAsReadForUserDao).
     */
    public function markRead(Request $request)
    {
        global $CFG, $PDOX;

        $discussions_url = (isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot) . self::ROUTE;

        if ( ! U::get($_SESSION, 'id') || ! U::get($_SESSION, 'context_id') ) {
            U::flashError(__('You must be logged in with a course context to mark discussions as read.'));
            return new RedirectResponse(U::addSession($discussions_url));
        }

        if ( ! U::isNotEmpty($CFG->tdiscus) || ! $CFG->tdiscus ) {
            U::flashError(__('Discussions are not available on this site.'));
            return new RedirectResponse(U::addSession($discussions_url));
        }

        LTIX::getConnection();

        $context_id = intval($_SESSION['context_id']);
        $user_id = intval($_SESSION['id']);

        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}tdiscus_user_thread UT
            JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = UT.thread_id
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            SET UT.read_at = NOW(), UT.comments = T.comments
            WHERE L.context_id = :CID AND UT.user_id = :UID",
            array(':CID' => $context_id, ':UID' => $user_id)
        );

        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}tdiscus_user_thread (thread_id, user_id, comments, read_at)
            SELECT T.thread_id, :UID, T.comments, NOW()
            FROM {$CFG->dbprefix}tdiscus_thread T
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            WHERE L.context_id = :CID
              AND NOT EXISTS (
                SELECT 1 FROM {$CFG->dbprefix}tdiscus_user_thread UT
                WHERE UT.thread_id = T.thread_id AND UT.user_id = :UID2
              )",
            array(':UID' => $user_id, ':CID' => $context_id, ':UID2' => $user_id)
        );

        U::flashSuccess(__('All discussions in this course have been marked as read.'));
        return new RedirectResponse(U::addSession($discussions_url));
    }

    private function rollupsForLink($link_id, $user_id, $has_mentions, $has_participation, $include_participation_as_personal)
    {
        global $PDOX, $CFG;

        $personal_join_mention = "";
        $personal_is_mention = "FALSE";
        if ( $has_mentions ) {
            $personal_join_mention = "LEFT JOIN {$CFG->dbprefix}tdiscus_mention M
                ON M.post_id = C.comment_id AND M.mentioned_user_id = :UID";
            $personal_is_mention = "M.mentioned_user_id IS NOT NULL";
        }

        $personal_participation_clause = "FALSE";
        if ( $has_participation && $include_participation_as_personal ) {
            $personal_participation_clause = "EXISTS (
                SELECT 1 FROM {$CFG->dbprefix}tdiscus_user_thread_participation UTP
                WHERE UTP.user_id = :UID AND UTP.thread_id = C.thread_id
            )";
        }

        $personal = $PDOX->rowDie("SELECT COUNT(DISTINCT C.comment_id) AS count
            FROM {$CFG->dbprefix}tdiscus_comment C
            JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = C.thread_id
            LEFT JOIN {$CFG->dbprefix}tdiscus_user_thread UT
                ON UT.thread_id = C.thread_id AND UT.user_id = :UID
            LEFT JOIN {$CFG->dbprefix}tdiscus_comment P ON P.comment_id = C.parent_id
            $personal_join_mention
            WHERE T.link_id = :LID
              AND C.user_id <> :UID
              AND C.created_at > COALESCE(UT.read_at, '1970-01-01 00:00:00')
              AND (
                    P.user_id = :UID
                    OR (T.user_id = :UID AND C.parent_id > 0)
                    OR $personal_is_mention
                    OR $personal_participation_clause
              )",
            array(':UID' => $user_id, ':LID' => $link_id)
        );

        $participation_where = "COALESCE(UT.subscribe, 0) = 1";
        if ( $has_participation ) {
            $participation_where = "(
                EXISTS (
                    SELECT 1 FROM {$CFG->dbprefix}tdiscus_user_thread_participation UTP
                    WHERE UTP.user_id = :UID AND UTP.thread_id = T.thread_id
                )
                OR COALESCE(UT.subscribe, 0) = 1
            )";
        }

        $participating = $PDOX->rowDie("SELECT COUNT(*) AS count
            FROM {$CFG->dbprefix}tdiscus_thread T
            LEFT JOIN {$CFG->dbprefix}tdiscus_user_thread UT
                ON UT.thread_id = T.thread_id AND UT.user_id = :UID
            WHERE T.link_id = :LID
              AND $participation_where
              AND (T.comments - COALESCE(UT.comments, 0)) > 0",
            array(':UID' => $user_id, ':LID' => $link_id)
        );

        $global = $PDOX->rowDie("SELECT COUNT(*) AS count
            FROM {$CFG->dbprefix}tdiscus_comment C
            JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = C.thread_id
            LEFT JOIN {$CFG->dbprefix}tdiscus_user_thread UT
                ON UT.thread_id = C.thread_id AND UT.user_id = :UID
            WHERE T.link_id = :LID
              AND C.user_id <> :UID
              AND C.created_at > COALESCE(UT.read_at, '1970-01-01 00:00:00')",
            array(':UID' => $user_id, ':LID' => $link_id)
        );

        return array(
            'personal' => intval($personal['count']),
            'participating' => intval($participating['count']),
            'global' => intval($global['count']),
        );
    }

    private function tableExists($table_name)
    {
        global $PDOX;
        $row = $PDOX->rowDie("SELECT 1 AS present
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = :TN",
            array(':TN' => $table_name)
        );
        return is_array($row);
    }

}

<?php

namespace Tsugi\Controllers;


use \Tsugi\Util\U;
use Tsugi\Util\LTI;
use Tsugi\Core\LTIX;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class Discussions extends Tool {

    const ROUTE = '/discussions';
    const EXPIRE_DELETE_BATCH_LIMIT = 500;

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Discussions@get');
        $app->router->get($prefix.'/', 'Discussions@get');
        $app->router->get($prefix.'/expire-threads', 'Discussions@expireThreads');
        $app->router->get($prefix.'/json', 'Discussions@json');
        $app->router->post($prefix.'/mark-read', 'Discussions@markRead');
        $app->router->post($prefix.'/expire-threads-dry-run', 'Discussions@expireThreadsDryRun');
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

        // Allow deployments to override lesson JSON launch URLs for discussions
        // via $CFG->tdiscus (e.g., alternate host/path for tdiscus).
        $endpoint = $lti->launch;
        if ( isset($CFG->tdiscus) && U::isNotEmpty($CFG->tdiscus) ) {
            $endpoint = $CFG->tdiscus;
        }
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

        if ( ! U::isLoggedIn() || ! U::currentContextId() ) {
            return new JsonResponse(array('status' => 'error', 'detail' => 'Must be logged in with context'), 401);
        }

        LTIX::getConnection();

        $context_id = U::currentContextId();
        $user_id = U::loggedInUserId();

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

        if ( ! U::isLoggedIn() || ! U::currentContextId() ) {
            U::flashError(__('You must be logged in with a course context to mark discussions as read.'));
            return new RedirectResponse(U::addSession($discussions_url));
        }

        if ( ! U::isNotEmpty($CFG->tdiscus) || ! $CFG->tdiscus ) {
            U::flashError(__('Discussions are not available on this site.'));
            return new RedirectResponse(U::addSession($discussions_url));
        }

        LTIX::getConnection();

        $context_id = U::currentContextId();
        $user_id = U::loggedInUserId();

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

    public function expireThreadsDryRun(Request $request)
    {
        global $CFG, $PDOX;

        $expire_url = (isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot) . self::ROUTE . '/expire-threads';
        $redirect_url = U::addSession($expire_url);

        if ( ! U::isLoggedIn() || ! U::currentContextId() ) {
            U::flashError(__('You must be logged in with a course context to manage discussion expiration.'));
            return new RedirectResponse($redirect_url);
        }
        if ( ! $this->isInstructor() ) {
            U::flashError(__('Only instructors can run discussion expiration.'));
            return new RedirectResponse($redirect_url);
        }

        $months_raw = trim((string) U::get($_POST, 'months', ''));
        $months = intval($months_raw);
        if ( ! ctype_digit($months_raw) || $months <= 1 ) {
            U::flashError(__('Months must be a whole number greater than 1.'));
            return new RedirectResponse($redirect_url);
        }

        LTIX::getConnection();
        $context_id = U::currentContextId();
        $confirm_raw = trim((string) U::get($_POST, 'confirm', ''));
        $confirm = ($confirm_raw === '1');

        $count_sql = "SELECT COUNT(*) AS count
            FROM {$CFG->dbprefix}tdiscus_thread T
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            LEFT JOIN (
                SELECT C.thread_id, MAX(C.created_at) AS latest_post_at
                FROM {$CFG->dbprefix}tdiscus_comment C
                GROUP BY C.thread_id
            ) LC ON LC.thread_id = T.thread_id
            WHERE L.context_id = :CID
              AND T.created_at < DATE_SUB(NOW(), INTERVAL :MONTHS MONTH)
              AND COALESCE(LC.latest_post_at, T.created_at) < DATE_SUB(NOW(), INTERVAL :MONTHS2 MONTH)";

        $count_row = $PDOX->rowDie($count_sql, array(
            ':CID' => $context_id,
            ':MONTHS' => $months,
            ':MONTHS2' => $months,
        ));

        $candidate_sql = "SELECT T.thread_id
FROM {$CFG->dbprefix}tdiscus_thread T
JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
LEFT JOIN (
    SELECT C.thread_id, MAX(C.created_at) AS latest_post_at
    FROM {$CFG->dbprefix}tdiscus_comment C
    GROUP BY C.thread_id
) LC ON LC.thread_id = T.thread_id
WHERE L.context_id = :CID
  AND T.created_at < DATE_SUB(NOW(), INTERVAL :MONTHS MONTH)
  AND COALESCE(LC.latest_post_at, T.created_at) < DATE_SUB(NOW(), INTERVAL :MONTHS2 MONTH)
ORDER BY COALESCE(LC.latest_post_at, T.created_at), T.thread_id
LIMIT ".self::EXPIRE_DELETE_BATCH_LIMIT;

        $delete_sql = "DELETE FROM {$CFG->dbprefix}tdiscus_thread
WHERE thread_id IN (:THREAD_ID_1, :THREAD_ID_2, ... up to ".self::EXPIRE_DELETE_BATCH_LIMIT." ids)";

        $matching_before = intval(U::get($count_row, 'count', 0));
        $matching_after = $matching_before;
        $deleted_now = 0;
        $limit_hit = 0;
        if ( $confirm ) {
            @set_time_limit(30);
            $candidate_rows = $PDOX->allRowsDie($candidate_sql, array(
                ':CID' => $context_id,
                ':MONTHS' => $months,
                ':MONTHS2' => $months,
            ));
            $thread_ids = array();
            foreach ( $candidate_rows as $row ) {
                $thread_id = intval(U::get($row, 'thread_id', 0));
                if ( $thread_id > 0 ) $thread_ids[] = $thread_id;
            }
            if ( count($thread_ids) > 0 ) {
                $delete_params = array();
                $placeholders = array();
                $ix = 0;
                foreach ( $thread_ids as $thread_id ) {
                    $ix++;
                    $ph = ':TID'.$ix;
                    $placeholders[] = $ph;
                    $delete_params[$ph] = $thread_id;
                }
                $run_delete_sql = "DELETE FROM {$CFG->dbprefix}tdiscus_thread
                    WHERE thread_id IN (".implode(',', $placeholders).")";
                $stmt = $PDOX->queryDie($run_delete_sql, $delete_params);
                $deleted_now = $stmt->rowCount();
                $limit_hit = (count($thread_ids) >= self::EXPIRE_DELETE_BATCH_LIMIT && $matching_before > $deleted_now) ? 1 : 0;
            }
            $after_row = $PDOX->rowDie($count_sql, array(
                ':CID' => $context_id,
                ':MONTHS' => $months,
                ':MONTHS2' => $months,
            ));
            $matching_after = intval(U::get($after_row, 'count', 0));
            if ( $deleted_now > 0 ) {
                if ( $limit_hit ) {
                    U::flashSuccess(__('Deleted ').$deleted_now.__(' threads. Batch limit reached (500). Run again to continue.'));
                } else {
                    U::flashSuccess(__('Deleted ').$deleted_now.__(' threads.'));
                }
            } else {
                U::flashSuccess(__('No matching threads were deleted.'));
            }
        } else {
            U::flashSuccess(__('Dry run complete: no threads were deleted.'));
        }

        $_SESSION['discussions_expire_dry_run_result'] = array(
            'months' => $months,
            'count' => $matching_after,
            'count_before' => $matching_before,
            'confirmed' => $confirm ? 1 : 0,
            'deleted_now' => $deleted_now,
            'limit_hit' => $limit_hit,
            'batch_limit' => self::EXPIRE_DELETE_BATCH_LIMIT,
            'candidate_sql' => $candidate_sql,
            'sql' => $delete_sql,
            'params' => array(
                ':CID' => $context_id,
                ':MONTHS' => $months,
                ':MONTHS2' => $months,
            ),
        );
        return new RedirectResponse($redirect_url);
    }

    public function expireThreads(Request $request)
    {
        global $CFG, $OUTPUT, $PDOX;

        if ( ! U::isLoggedIn() || ! U::currentContextId() ) {
            U::flashError(__('You must be logged in with a course context to manage discussion expiration.'));
            return new RedirectResponse(U::addSession(self::ROUTE));
        }
        if ( ! $this->isInstructor() ) {
            U::flashError(__('Only instructors can run discussion expiration.'));
            return new RedirectResponse(U::addSession(self::ROUTE));
        }

        LTIX::getConnection();
        $context_id = U::currentContextId();
        $oldest_post_row = $PDOX->rowDie(
            "SELECT MIN(C.created_at) AS oldest_post_at
                FROM {$CFG->dbprefix}tdiscus_comment C
                JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = C.thread_id
                JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
                WHERE L.context_id = :CID",
            array(':CID' => $context_id)
        );
        $oldest_thread_row = $PDOX->rowDie(
            "SELECT MIN(T.created_at) AS oldest_thread_at
                FROM {$CFG->dbprefix}tdiscus_thread T
                JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
                WHERE L.context_id = :CID",
            array(':CID' => $context_id)
        );
        $oldest_post_at = U::get($oldest_post_row, 'oldest_post_at', null);
        $oldest_thread_at = U::get($oldest_thread_row, 'oldest_thread_at', null);

        $dry_run_url = U::addSession(self::ROUTE.'/expire-threads-dry-run');
        $expire_result = U::get($_SESSION, 'discussions_expire_dry_run_result', false);
        unset($_SESSION['discussions_expire_dry_run_result']);

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        echo('<main class="container" id="main-content">');
        echo('<p><a href="'.htmlspecialchars(U::addSession(self::ROUTE)).'" class="btn btn-default btn-sm">'.__('Back to Discussions').'</a></p>');
        $this->renderExpireDryRunPanel($dry_run_url, $expire_result, $oldest_post_at, $oldest_thread_at);
        echo('</main>');
        $OUTPUT->footer();
    }

    private function renderExpireDryRunPanel($action_url, $result=false, $oldest_post_at=null, $oldest_thread_at=null)
    {
        $default_months = 2;
        if ( is_array($result) && isset($result['months']) ) {
            $default_months = intval($result['months']);
            if ( $default_months <= 1 ) $default_months = 2;
        }
        ?>
        <div class="panel panel-warning" style="margin-bottom: 1.5em;">
            <div class="panel-heading"><strong>Instructor: Expire old discussion threads</strong></div>
            <div class="panel-body">
                <?php if ( is_string($oldest_post_at) && strlen($oldest_post_at) > 0 ) { ?>
                    <p class="text-muted" style="margin-top: 0;">
                        Oldest post date in this course: <strong><?= htmlspecialchars($oldest_post_at) ?></strong>
                    </p>
                <?php } else if ( is_string($oldest_thread_at) && strlen($oldest_thread_at) > 0 ) { ?>
                    <p class="text-muted" style="margin-top: 0;">
                        No posts found yet in this course. Oldest thread creation date: <strong><?= htmlspecialchars($oldest_thread_at) ?></strong>
                    </p>
                <?php } else { ?>
                    <p class="text-muted" style="margin-top: 0;">
                        No discussion threads found in this course.
                    </p>
                <?php } ?>
                <p class="text-muted" style="margin-top: 0;">
                    This first version is dry run only. It never deletes data, and always shows the SQL that would run.
                </p>
                <form method="post" action="<?= htmlspecialchars($action_url) ?>" class="form-inline" style="margin-bottom: 1em;">
                    <div class="form-group">
                        <label for="expire-months">Months:</label>
                        <input id="expire-months" type="number" min="2" step="1" name="months"
                            class="form-control" style="width: 8em; margin-left: 0.5em;"
                            value="<?= htmlspecialchars((string) $default_months) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-warning" style="margin-left: 0.5em;">Dry Run</button>
                </form>

                <?php if ( is_array($result) ) { ?>
                    <?php
                        $candidate_sql_comment = str_replace('--', '- -', (string) U::get($result, 'candidate_sql', ''));
                        $delete_sql_comment = str_replace('--', '- -', (string) U::get($result, 'sql', ''));
                        $params_comment = str_replace('--', '- -', json_encode(U::get($result, 'params', array()), JSON_PRETTY_PRINT));
                    ?>
                    <div class="alert alert-info" style="margin-bottom: 0.75em;">
                        Matching threads for <strong><?= intval($result['months']) ?></strong> months: <strong><?= intval(U::get($result, 'count', 0)) ?></strong>
                        <?php if ( intval(U::get($result, 'confirmed', 0)) === 1 ) { ?>
                            <br/>Matching before delete: <strong><?= intval(U::get($result, 'count_before', U::get($result, 'count', 0))) ?></strong>
                            <br/>Deleted this run: <strong><?= intval(U::get($result, 'deleted_now', 0)) ?></strong>
                            <?php if ( intval(U::get($result, 'limit_hit', 0)) === 1 ) { ?>
                                <br/>Batch limit reached (<?= intval(U::get($result, 'batch_limit', self::EXPIRE_DELETE_BATCH_LIMIT)) ?>). Run again to continue.
                            <?php } ?>
                        <?php } ?>
                    </div>
                    <!-- Candidate SQL (limited batch)
<?= htmlspecialchars($candidate_sql_comment) ?>
SQL used for deletion
<?= htmlspecialchars($delete_sql_comment) ?>
Bound parameters
<?= htmlspecialchars($params_comment) ?>
                    -->
                    <?php if ( intval(U::get($result, 'count', 0)) > 0 ) { ?>
                        <form method="post" action="<?= htmlspecialchars($action_url) ?>" class="form-inline">
                            <input type="hidden" name="months" value="<?= intval($result['months']) ?>">
                            <input type="hidden" name="confirm" value="1">
                            <button type="submit" class="btn btn-danger">Delete Threads (no undo)</button>
                        </form>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
        <?php
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

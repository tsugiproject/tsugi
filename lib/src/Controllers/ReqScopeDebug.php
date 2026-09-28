<?php

namespace Tsugi\Controllers;

use Tsugi\Core\ReqScope;
use Tsugi\Lumos\Application;
use Tsugi\Util\LTI13;
use Tsugi\Util\U;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Temporary dump of ReqScope and the launch object.
 *
 * On only when config.php calls $CFG->setExtension('reqscope_debug', true).
 * Remove this controller, tool/reqscope, ReqScope::scopeWalker(), and that
 * extension around December 2026.
 */
class ReqScopeDebug extends Tool {

    const ROUTE = '/reqscope';
    const NAME = 'ReqScope';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'ReqScopeDebug@index');
        $app->router->get($prefix.'/', 'ReqScopeDebug@index');
        $app->router->post($prefix, 'ReqScopeDebug@post');
        $app->router->post($prefix.'/', 'ReqScopeDebug@post');
    }

    public static function enabled() {
        global $CFG;
        return isset($CFG) && is_object($CFG) && $CFG->getExtension('reqscope_debug') === true;
    }

    public function index(Request $request) {
        global $TSUGI_LAUNCH;
        self::render(isset($TSUGI_LAUNCH) ? $TSUGI_LAUNCH : null, self::gradeResult() !== null);
    }

    /**
     * Trophy-style grade post. The cookieless id stays on the URL, then we
     * come back to the debug page in the same session.
     */
    public function post(Request $request) {
        global $CFG;
        if ( ! self::checkCsrf() ) {
            return new RedirectResponse(addSession($CFG->wwwroot.'/reqscope'));
        }
        if ( self::enabled() ) {
            self::sendGrade();
        }
        return new RedirectResponse(addSession($CFG->wwwroot.'/reqscope'));
    }

    /**
     * @param object|null $launch Tools pass $LAUNCH. The site route passes $TSUGI_LAUNCH.
     * @param bool $withGrade True when ReqScope has a result that can send a grade.
     * @param string|null $formAction Where the grade form posts. The site route posts to /reqscope.
     */
    public static function render($launch, $withGrade = false, $formAction = null) {
        global $OUTPUT;

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        echo("<h1>ReqScope</h1>\n");
        if ( ! self::enabled() ) {
            echo("<p>reqscope_debug is off.</p>\n");
            echo("<p>Add <code>\$CFG-&gt;setExtension('reqscope_debug', true);</code> to config.php.</p>\n");
            $OUTPUT->footer();
            return;
        }

        if ( $withGrade ) {
            self::gradePanel($formAction);
        }

        $scope = ReqScope::current();
        $origin = ($scope && isset($scope->origin)) ? $scope->origin : null;
        echo("<h2>Scope walker</h2>\n");
        echo("<p>Launch compared with ReqScope.</p>\n");
        $launchNotes = ReqScope::scopeWalker($launch);
        if ( ! $launchNotes ) {
            echo("<p>No mismatches.</p>\n");
        } else {
            echo(self::pre($launchNotes));
        }
        if ( $origin !== ReqScope::ORIGIN_LTI ) {
            echo("<h2>Comparing Legacy Accessors to ReqScope</h2>\n");
            $legacyNotes = ReqScope::legacyAccessorNotes();
            if ( ! $legacyNotes ) {
                echo("<p>No mismatches.</p>\n");
            } else {
                echo(self::pre($legacyNotes));
            }
        }
        echo("<h2>Readers</h2>\n");
        echo(self::pre(self::export(array(
            'isLoggedIn' => ReqScope::isLoggedIn(),
            'loggedInUserId' => ReqScope::loggedInUserId(),
            'currentContextId' => ReqScope::currentContextId(),
            'isLoggedInLegacy' => ReqScope::isLoggedInLegacy(),
            'loggedInUserIdLegacy' => ReqScope::loggedInUserIdLegacy(),
            'currentContextIdLegacy' => ReqScope::currentContextIdLegacy(),
        ))));
        echo("<h2>ReqScope</h2>\n");
        echo(self::pre(self::export($scope)));
        echo("<h2>Launch</h2>\n");
        echo(self::pre(self::export($launch)));
        $OUTPUT->footer();
    }

    /**
     * The result on this request, when it has an id and can send a grade.
     *
     * @return \Tsugi\Core\Result|null
     */
    private static function gradeResult() {
        $scope = ReqScope::current();
        if ( ! $scope || ! $scope->result || (int) $scope->result->id < 1 ) {
            return null;
        }
        return $scope->result;
    }

    /**
     * True when this request has a result that can send a grade.
     */
    public static function hasGradeResult() {
        return self::gradeResult() !== null;
    }

    /**
     * Store a posted grade when the debug page is on.
     */
    public static function acceptGrade() {
        if ( self::enabled() ) {
            self::sendGrade();
        }
    }

    private static function gradePanel($formAction = null) {
        global $CFG;
        if ( $formAction === null ) {
            $formAction = $CFG->wwwroot.'/reqscope';
        }

        if ( ! self::gradeResult() ) {
            return;
        }
        $scope = ReqScope::current();
        if ( $scope && $scope->user && $scope->user->instructor ) {
            echo("<p>Instructors can't send grades with LTI.</p>\n");
            return;
        }

        $sent = U::get($_SESSION, 'reqscope_sent');
        $grade = U::get($_SESSION, 'reqscope_grade', 0.95);
        $comment = U::get($_SESSION, 'reqscope_comment', '');
        $transport = U::get($_SESSION, 'reqscope_transport');
        $debug_log = U::get($_SESSION, 'reqscope_debug_log');
        $status = U::get($_SESSION, 'reqscope_status');
        $gradingProgress = U::get($_SESSION, 'reqscope_grading_progress');
        $activityProgress = U::get($_SESSION, 'reqscope_activity_progress');
        $action = htmlspecialchars(addSession($formAction));

        echo("<form method=\"post\" action=\"".$action."\">\n");
        echo(self::csrfField()."\n");
        echo("<input type=\"text\" name=\"grade\" value=\"".htmlspecialchars((string) $grade)."\"/> Grade<br/>\n");
        echo("<input type=\"text\" name=\"comment\" value=\"".htmlspecialchars((string) $comment)."\"/> Comment<br/>\n");
        echo("<select name=\"".LTI13::GRADING_PROGRESS."\">\n");
        echo("<option value=\"\">-- select ".LTI13::GRADING_PROGRESS." (optional) ---</option>\n");
        self::doOption(LTI13::GRADING_PROGRESS_FULLYGRADED, $gradingProgress);
        self::doOption(LTI13::GRADING_PROGRESS_PENDING, $gradingProgress);
        self::doOption(LTI13::GRADING_PROGRESS_PENDINGMANUAL, $gradingProgress);
        self::doOption(LTI13::GRADING_PROGRESS_FAILED, $gradingProgress);
        self::doOption(LTI13::GRADING_PROGRESS_NOTREADY, $gradingProgress);
        echo("</select><br/>\n");
        echo("<select name=\"".LTI13::ACTIVITY_PROGRESS."\">\n");
        echo("<option value=\"\">-- select ".LTI13::ACTIVITY_PROGRESS." (optional) ---</option>\n");
        self::doOption(LTI13::ACTIVITY_PROGRESS_INITIALIZED, $activityProgress);
        self::doOption(LTI13::ACTIVITY_PROGRESS_STARTED, $activityProgress);
        self::doOption(LTI13::ACTIVITY_PROGRESS_INPROGRESS, $activityProgress);
        self::doOption(LTI13::ACTIVITY_PROGRESS_SUBMITTED, $activityProgress);
        self::doOption(LTI13::ACTIVITY_PROGRESS_COMPLETED, $activityProgress);
        echo("</select><br/>\n");
        echo("<input type=\"submit\">\n");
        echo("</form>\n");

        if ( $sent && $status === true ) {
            echo("<p><i class=\"fa fa-trophy\" aria-hidden=\"true\"></i> Grade send finished.");
            if ( $transport ) {
                echo(" Sent using ".htmlspecialchars((string) $transport).".");
            } else {
                echo(" Stored locally.");
            }
            echo("</p>\n");
        } else if ( $sent ) {
            echo("<p>Grade send failed.");
            if ( is_string($status) && $status !== '' ) {
                echo(" ".htmlspecialchars($status));
            }
            echo("</p>\n");
        }
        if ( $sent && $debug_log ) {
            echo(self::pre($debug_log));
        }
    }

    /**
     * Send the posted grade through the result on ReqScope.
     */
    private static function sendGrade() {
        $grade = U::get($_POST, 'grade');
        $comment = U::get($_POST, 'comment');
        $gradingProgress = U::get($_POST, LTI13::GRADING_PROGRESS);
        $activityProgress = U::get($_POST, LTI13::ACTIVITY_PROGRESS);
        if ( count($_POST) < 1 || ! is_string($grade) ) {
            return;
        }

        $result = self::gradeResult();
        if ( ! $result ) {
            return;
        }
        $scope = ReqScope::current();
        $debug_log = array();
        $transport = null;
        $status = self::gradeInputError($grade, $gradingProgress, $activityProgress);
        if ( $status === null && $scope && $scope->user && $scope->user->instructor ) {
            $status = "Instructors can't send grades with LTI.";
        }
        if ( $status === null ) {
            $extra = array();
            if ( is_string($comment) ) {
                $extra[LTI13::LINEITEM_COMMENT] = $comment;
            }
            if ( $activityProgress ) $extra[LTI13::ACTIVITY_PROGRESS] = $activityProgress;
            if ( $gradingProgress ) $extra[LTI13::GRADING_PROGRESS] = $gradingProgress;
            $status = $result->gradeSend($grade, false, $debug_log, $extra);
            $transport = $result->lastSendTransport;
        }

        $_SESSION['reqscope_sent'] = true;
        $_SESSION['reqscope_grade'] = $grade;
        $_SESSION['reqscope_comment'] = $comment;
        $_SESSION['reqscope_grading_progress'] = $gradingProgress;
        $_SESSION['reqscope_activity_progress'] = $activityProgress;
        $_SESSION['reqscope_transport'] = $transport;
        $_SESSION['reqscope_debug_log'] = $debug_log;
        $_SESSION['reqscope_status'] = $status;
    }

    /**
     * @param mixed $grade
     * @param mixed $gradingProgress
     * @param mixed $activityProgress
     * @return string|null An error message, or null when the posted values can be sent.
     */
    private static function gradeInputError($grade, $gradingProgress, $activityProgress) {
        if ( ! is_numeric($grade) || (float) $grade < 0.0 || (float) $grade > 1.0 ) {
            return 'Grade must be between 0.0 and 1.0.';
        }
        $grading = array(
            LTI13::GRADING_PROGRESS_FULLYGRADED,
            LTI13::GRADING_PROGRESS_PENDING,
            LTI13::GRADING_PROGRESS_PENDINGMANUAL,
            LTI13::GRADING_PROGRESS_FAILED,
            LTI13::GRADING_PROGRESS_NOTREADY,
        );
        $activity = array(
            LTI13::ACTIVITY_PROGRESS_INITIALIZED,
            LTI13::ACTIVITY_PROGRESS_STARTED,
            LTI13::ACTIVITY_PROGRESS_INPROGRESS,
            LTI13::ACTIVITY_PROGRESS_SUBMITTED,
            LTI13::ACTIVITY_PROGRESS_COMPLETED,
        );
        if ( ! self::optionalProgressOk($gradingProgress, $grading) ) {
            return 'Grading progress is not a recognized value.';
        }
        if ( ! self::optionalProgressOk($activityProgress, $activity) ) {
            return 'Activity progress is not a recognized value.';
        }
        return null;
    }

    /**
     * An optional progress value may be absent or empty. Any other value must be a recognized string.
     *
     * @param mixed $value
     * @param string[] $allowed
     */
    private static function optionalProgressOk($value, array $allowed) {
        if ( $value === null || $value === false || $value === '' ) {
            return true;
        }
        return is_string($value) && in_array($value, $allowed, true);
    }

    private static function doOption($option, $current) {
        echo('<option value="'.htmlspecialchars($option).'"');
        if ( $option == $current ) echo(' selected');
        echo('>'.htmlspecialchars($option)."</option>\n");
    }

    private static function pre($value) {
        return '<pre>'.htmlspecialchars(print_r($value, true))."</pre>\n";
    }

    /**
     * Public properties, with connections and secrets left out.
     *
     * @param mixed $value
     * @param int $depth
     * @return mixed
     */
    private static function export($value, $depth = 0) {
        if ( $depth > 4 ) {
            return '...';
        }
        if ( is_bool($value) ) {
            return $value ? 'true' : 'false';
        }
        if ( $value === null || is_scalar($value) ) {
            return $value;
        }
        if ( is_array($value) ) {
            $out = array();
            foreach ( $value as $key => $item ) {
                $out[$key] = self::exportChild($key, $item, $depth);
            }
            return $out;
        }
        if ( ! is_object($value) ) {
            return '('.gettype($value).')';
        }
        $class = get_class($value);
        if ( $value instanceof \PDO || $class === 'Tsugi\Util\PDOX' || $class === 'Tsugi\UI\Output' ) {
            return '('.$class.')';
        }
        $out = array('__class' => $class);
        foreach ( get_object_vars($value) as $key => $item ) {
            if ( $key === 'launch' || $key === 'pdox' || $key === 'output' ) {
                $out[$key] = is_object($item) ? '('.get_class($item).')' : $item;
                continue;
            }
            $out[$key] = self::exportChild($key, $item, $depth);
        }
        return $out;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @param int $depth
     * @return mixed
     */
    private static function exportChild($key, $value, $depth) {
        if ( is_string($key) && preg_match('/secret|password|token|private/i', $key) ) {
            if ( $value === null || $value === '' || $value === false ) {
                return $value;
            }
            return '(redacted)';
        }
        return self::export($value, $depth + 1);
    }
}

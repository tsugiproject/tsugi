<?php

namespace Tsugi\Controllers;

use Tsugi\Core\ReqScope;
use Tsugi\Lumen\Application;
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
    }

    public static function enabled() {
        global $CFG;
        return isset($CFG) && is_object($CFG) && $CFG->getExtension('reqscope_debug') === true;
    }

    public function index(Request $request) {
        global $TSUGI_LAUNCH;
        self::render(isset($TSUGI_LAUNCH) ? $TSUGI_LAUNCH : null);
    }

    /**
     * @param object|null $launch Tools pass $LAUNCH. The site route passes $TSUGI_LAUNCH.
     */
    public static function render($launch) {
        global $OUTPUT;

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        echo("<h1>ReqScope</h1>\n");
        if ( ! self::enabled() ) {
            echo("<p>reqscope_debug is off.</p>\n");
            echo("<p>Add <code>\$CFG-&gt;setExtension('reqscope_debug', true);</code> to config.php.</p>\n");
            $OUTPUT->footer();
            return;
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
            echo("<h2>Readers</h2>\n");
            echo(self::pre(self::export(array(
                'isLoggedIn' => ReqScope::isLoggedIn(),
                'loggedInUserId' => ReqScope::loggedInUserId(),
                'currentContextId' => ReqScope::currentContextId(),
                'origin' => $origin,
            ))));
        }
        echo("<h2>ReqScope</h2>\n");
        echo(self::pre(self::export($scope)));
        echo("<h2>Launch</h2>\n");
        echo(self::pre(self::export($launch)));
        $OUTPUT->footer();
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

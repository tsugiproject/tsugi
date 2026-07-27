<?php

namespace Tsugi\Util;

use \Tsugi\Core\LTIX;
use \Tsugi\UI\Lessons;
use \Tsugi\Util\U;

/**
 * Shared helpers for Tsugi autograder tools (WebGrader, PythonGrader, DBGrader, …).
 *
 * Beachhead for grader-specific launch/config patterns that do not belong on
 * Settings, LTIX, or Lessons. Grow this carefully as more duplication appears.
 */
class Autograder {

    /**
     * Load full assignment/exercise JSON from LTI custom_config, then lessons.
     *
     * Precedence:
     *   1. LTI custom parameter "config" (JSON string)
     *   2. lessons.json entry for ?inherit=<rlid>, else ?exercise=<rlid>,
     *      reading a custom entry with key "config"
     *
     * The tool supplies $isValid to decide whether a decoded array is a
     * usable assignment for that tool's schema.
     *
     * @param callable $isValid function(array $decoded): bool
     * @return array|null
     */
    public static function loadCustomConfig($isValid)
    {
        global $CFG;

        if ( ! is_callable($isValid) ) {
            return null;
        }

        $custom = LTIX::ltiCustomGet('config');
        $exercise = self::decodeConfig($custom, $isValid);
        if ( $exercise ) {
            return $exercise;
        }

        $rlid = null;
        if ( isset($_GET['inherit']) && is_string($_GET['inherit']) && strlen($_GET['inherit']) ) {
            $rlid = $_GET['inherit'];
        } else if ( isset($_GET['exercise']) && is_string($_GET['exercise']) && strlen($_GET['exercise']) ) {
            $rlid = $_GET['exercise'];
        }

        if ( ! $rlid || ! isset($CFG->lessons) ) {
            return null;
        }

        $lessons = new Lessons($CFG->lessons);
        if ( ! $lessons ) {
            return null;
        }

        $lti = $lessons->getLtiByRlid($rlid);
        if ( ! isset($lti->custom) || ! is_array($lti->custom) ) {
            return null;
        }

        foreach ( $lti->custom as $c ) {
            if ( ! isset($c->key, $c->json) || $c->key !== 'config' ) {
                continue;
            }
            if ( is_string($c->json) ) {
                $exercise = self::decodeConfig($c->json, $isValid);
            } else {
                $asArray = json_decode(json_encode($c->json), true);
                if ( is_array($asArray) && call_user_func($isValid, $asArray) ) {
                    $exercise = $asArray;
                } else {
                    $exercise = null;
                }
            }
            if ( $exercise ) {
                return $exercise;
            }
        }

        return null;
    }

    /**
     * Decode a JSON string into an array accepted by $isValid, or null.
     *
     * @param mixed $raw
     * @param callable $isValid
     * @return array|null
     */
    private static function decodeConfig($raw, $isValid)
    {
        if ( ! $raw || ! is_string($raw) || U::isEmpty($raw) ) {
            return null;
        }
        $decoded = json_decode($raw, true);
        if ( is_array($decoded) && call_user_func($isValid, $decoded) ) {
            return $decoded;
        }
        return null;
    }

}

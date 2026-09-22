<?php

namespace Tsugi\UI;

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Controllers\Login;

/**
 * Config-gated simulated Google site login (no Google account).
 */
class DemoLogin {

    const PERSONA_INSTRUCTORS = 5;
    const PERSONA_STUDENTS = 10;

    /**
     * True when /login/simulate may be used.
     */
    public static function isEnabled() {
        global $CFG;
        if ( ! isset($CFG->demo_login) || $CFG->demo_login !== true ) {
            return false;
        }
        $secret = $CFG->demo_secret ?? false;
        return is_string($secret) && $secret !== '';
    }

    /**
     * URL of the simulate form (same host as /login).
     */
    public static function simulateUrl() {
        return rtrim(Login::loginUrl(), '/') . '/simulate';
    }

    /**
     * Timing-safe check of a posted secret against $CFG->demo_secret.
     *
     * Supports plaintext or sha256:... like $CFG->adminpw.
     */
    public static function secretMatches($posted) {
        global $CFG;
        if ( ! self::isEnabled() ) {
            return false;
        }
        if ( ! is_string($posted) || $posted === '' ) {
            return false;
        }
        $configured = $CFG->demo_secret;
        if ( ! is_string($configured) || $configured === '' ) {
            return false;
        }
        if ( strpos($configured, 'sha256:') === 0 ) {
            $hash = 'sha256:'.U::lti_sha256($posted);
            return hash_equals($configured, $hash);
        }
        return hash_equals($configured, $posted);
    }

    /**
     * Fixed catalog of 15 personas keyed by id (instructor-01 .. student-10).
     *
     * @return array<string,array>
     */
    public static function personas() {
        $list = array();
        for ( $i = 1; $i <= self::PERSONA_INSTRUCTORS; $i++ ) {
            $nn = sprintf('%02d', $i);
            $id = 'instructor-'.$nn;
            $email = 'instructor'.$nn.'@notgoogle.com';
            $list[$id] = array(
                'id' => $id,
                'label' => 'Instructor '.$nn,
                'firstName' => 'Instructor',
                'lastName' => $nn,
                'email' => $email,
                'user_key' => 'googlemail:'.$email,
                'instructor' => true,
            );
        }
        for ( $i = 1; $i <= self::PERSONA_STUDENTS; $i++ ) {
            $nn = sprintf('%02d', $i);
            $id = 'student-'.$nn;
            $email = 'student'.$nn.'@notgoogle.com';
            $list[$id] = array(
                'id' => $id,
                'label' => 'Student '.$nn,
                'firstName' => 'Student',
                'lastName' => $nn,
                'email' => $email,
                'user_key' => 'googlemail:'.$email,
                'instructor' => false,
            );
        }
        return $list;
    }

    /**
     * @param string $id
     * @return array|null
     */
    public static function persona($id) {
        if ( ! is_string($id) || $id === '' ) {
            return null;
        }
        $list = self::personas();
        return $list[$id] ?? null;
    }

    /**
     * Options for GoogleLoginHandler::establishGoogleSiteSession().
     *
     * @param array $persona
     * @return array
     */
    public static function sessionOptions($persona) {
        $instructor = ! empty($persona['instructor']);
        return array(
            'create_courses' => $instructor ? 1 : 0,
            'membership_role' => $instructor ? LTIX::ROLE_INSTRUCTOR : LTIX::ROLE_LEARNER,
            'force_membership_role' => true,
        );
    }
}

<?php
if ( ! isset($CFG) ) {
    http_response_code(404);
    exit;
}
/**
 * These are some configuration variables that are not secure / sensitive
 *
 * This file is included at the end of tsugi/config.php
 */

$CFG->service_worker = true;

$CFG->top_menu_callback = function() {
    global $CFG;
    $T = rtrim((string) $CFG->wwwroot, '/') . '/';
    $set = new \Tsugi\UI\MenuSet();
    $set->setHome($CFG->servicename, $CFG->getHomeUrl());
    if ( $CFG->google_client_id && ! \Tsugi\Core\ReqScope::isLoggedInLegacy() ) {
        $set->addRight('Login', $T.'login');
    }
    if ( $CFG->google_client_id && \Tsugi\Core\ReqScope::isLoggedInLegacy() ) {
        $submenu = new \Tsugi\UI\Menu();
        $submenu->addLink('Profile', $T.'profile');
        $submenu->addLink('Map', $T.'map');
        if ( isset($_COOKIE['adminmenu']) && $_COOKIE['adminmenu'] == 'true' ) {
            $submenu->addLink('Admin', $T.'admin/');
        }
        $submenu->addLink('Logout', $T.'logout');
        $set->addRight(\Tsugi\UI\Output::avatarMenuTrigger(), $submenu);
    }
    if ( \Tsugi\Core\ReqScope::isLoggedInLegacy() && \Tsugi\Controllers\Courses::showCoursesWidget() ) {
        $set->addRight(
            '<tsugi-courses api-url="'. htmlspecialchars($T . 'courses/json') . '" all-url="'. htmlspecialchars($T . 'courses') . '" enter-url="'. htmlspecialchars($T . 'courses') . '"></tsugi-courses>',
            false,
            true,
            'hidden-xs tsugi-wc-nav-item'
        );
    }
    if ( $CFG->hasSiteLessons() ) {
        $set->addLeft('Lessons', $T.'lessons');
    }
    return $set;
};

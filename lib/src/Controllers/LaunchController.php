<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Outbound LTI 1.1 launches by lessons resource_link_id at /launch/{resource_link_id}.
 *
 * Same launch payload as {@see Lessons::launch()} (lessons_launch/…); return URL defaults to /lessons
 * or /lessons/{anchor} when the link belongs to a module.
 */
class LaunchController extends Tool {

    const ROUTE = '/launch';

    public static function routes(Application $app, $prefix = self::ROUTE) {
        $app->router->get($prefix.'/{resource_link_id}', function (Request $request, $resource_link_id = null) use ($app) {
            return self::launchByResourceLinkId($app, $resource_link_id);
        });
    }

    /**
     * @return RedirectResponse|string
     */
    public static function launchByResourceLinkId(Application $app, $resource_link_id) {
        global $CFG;

        $path = U::rest_path();
        $redirect_path = U::addSession($path->parent);
        if ( $redirect_path === '' ) {
            $redirect_path = '/';
        }

        if ( ! isset($CFG->lessons) ) {
            $app->tsugiFlashError(__('Cannot find lessons.json ($CFG->lessons)'));
            return new RedirectResponse($redirect_path);
        }

        $l = new \Tsugi\UI\Lessons($CFG->lessons);
        $lti = $l->getLtiByRlid($resource_link_id);
        if ( ! $lti ) {
            $app->tsugiFlashError(__('Cannot find lti resource link id'));
            return new RedirectResponse($redirect_path);
        }

        $module = $l->getModuleByRlid($resource_link_id);

        $lessons_index = $path->parent . '/lessons';
        $return_url = $module
            ? $lessons_index . '/' . $module->anchor
            : $lessons_index;

        $fallback_title = ( $module && isset($module->title) ) ? $module->title : '';

        return self::sendLti11LaunchFromLessonsItem(
            $app,
            $lti,
            $return_url,
            $redirect_path,
            $fallback_title,
            self::SESSION_LESSONS_GRADE_REFRESH_AFTER_LAUNCH
        );
    }
}

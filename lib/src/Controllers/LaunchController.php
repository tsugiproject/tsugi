<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Outbound LTI 1.1 launches by lessons resource_link_id at /launch/{resource_link_id}.
 *
 * {@see launchByResourceLinkId()} sets `launch_presentation_return_url` to
 * `/launch/_launch_return`, which runs {@see Tool::applyGradeRefreshAfterLaunchReturn()} and redirects
 * to {@see Tool::configuredHomeUrl()} (apphome, or wwwroot when apphome is empty).
 */
class LaunchController extends Tool {

    const ROUTE = '/launch';

    /** Path segment for LTI return handling (must be registered before `{resource_link_id}`). */
    const RETURN_SEGMENT = '_launch_return';

    public static function routes(Application $app, $prefix = self::ROUTE) {
        $app->router->get($prefix.'/'.self::RETURN_SEGMENT, 'LaunchController@returnFromTool');
        $app->router->get($prefix.'/'.self::RETURN_SEGMENT.'/', 'LaunchController@returnFromTool');
        $app->router->get($prefix.'/{resource_link_id}', function (Request $request, $resource_link_id = null) use ($app) {
            return self::launchByResourceLinkId($app, $resource_link_id);
        });
    }

    /**
     * LTI return target: refresh grades if flagged, then redirect to site home.
     */
    public function returnFromTool(Request $request) {
        Tool::applyGradeRefreshAfterLaunchReturn();
        $home = Tool::configuredHomeUrl();
        return new RedirectResponse(U::addSession($home));
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

        $return_url = $path->parent . self::ROUTE . '/' . self::RETURN_SEGMENT;

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

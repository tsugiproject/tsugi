<?php

namespace Tsugi\Services;

use Tsugi\Core\Cache;
use Tsugi\Core\ReqScope;
use Tsugi\Services\Courses\CoursesService;

/**
 * In-memory registry of permissions declared by services.
 *
 * Tsugi.php calls assemble() in the same initialization that registers
 * routes. Definitions are not scanned from disk and not stored in the
 * database. has() answers from the logged-in user's grants, loaded once
 * into the session cache.
 */
class Permission extends Service {

    /**
     * Services whose permissions() are merged. Explicit, like the
     * controller list in Tsugi.php.
     *
     * @var list<class-string<Service>>
     */
    private const SERVICES = [
        CoursesService::class,
    ];

    /** @var array<string, array<string, mixed>>|null */
    private static $registry = null;

    /** Session cache slot for one user's active grants. */
    private const GRANT_CACHE = 'permissions';

    /** How long a loaded grant set stays in the session, in seconds. */
    private const GRANT_CACHE_SECONDS = 300;

    /**
     * Merge permissions() from the service list into one registry.
     *
     * Startup calls assemble() with no arguments and keeps that result.
     * A caller can pass a list to merge that list instead; a passed list
     * does not replace the startup registry. A repeated permission code
     * aborts the merge.
     *
     * @param list<class-string<Service>>|null $services
     * @return array<string, array<string, mixed>>
     */
    public static function assemble(?array $services = null): array {
        $install = $services === null;
        if ( $services === null ) {
            $services = self::SERVICES;
        }

        $registry = [];
        foreach ( $services as $service ) {
            foreach ( $service::permissions() as $code => $permission ) {
                if ( array_key_exists($code, $registry) ) {
                    throw new \InvalidArgumentException(
                        'Duplicate permission code: '.$code
                    );
                }
                $registry[$code] = $permission;
            }
        }
        if ( $install ) {
            self::$registry = $registry;
        }
        return $registry;
    }

    /**
     * Registry installed by assemble() during startup.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function registry(): array {
        if ( self::$registry === null ) {
            return self::assemble();
        }
        return self::$registry;
    }

    /**
     * True when the current user holds an active grant for a registered permission.
     *
     * Unknown codes are denied. The scope defaults to the system row (scope_id 1).
     * A grant is active when it is not revoked and not past expires_at.
     * Site-admin bypass does not belong here.
     *
     * The user's active grants are read once and kept in the session cache
     * for five minutes. Login and logout clear that cache. cacheClear()
     * drops it. cacheRefresh() drops it and loads the current user again.
     *
     * @param string $permission
     * @param int|null $scope_id
     */
    public static function has($permission, $scope_id = null) {
        if ( ! ReqScope::isLoggedInLegacy() ) {
            return false;
        }
        $code = trim((string) $permission);
        if ( $code === '' || ! isset(self::registry()[$code]) ) {
            return false;
        }
        $uid = ReqScope::loggedInUserIdLegacy();
        if ( $uid < 1 ) {
            return false;
        }
        $scope = $scope_id === null ? 1 : (int) $scope_id;
        if ( $scope < 1 ) {
            return false;
        }
        $grants = self::activeGrants($uid);
        return isset($grants[$scope][$code]);
    }

    /**
     * Drop the cached grants for this session.
     *
     * The next has() loads them again. Call this after a change that should
     * be visible before the five minutes are up.
     */
    public static function cacheClear() {
        Cache::clear(self::GRANT_CACHE);
    }

    /**
     * Drop the cached grants and load the current user's grants again.
     *
     * A logged-out session is only cleared. A missing connection leaves the
     * cache empty so the next check can try again.
     */
    public static function cacheRefresh() {
        self::cacheClear();
        if ( ! ReqScope::isLoggedInLegacy() ) {
            return;
        }
        $uid = ReqScope::loggedInUserIdLegacy();
        if ( $uid < 1 ) {
            return;
        }
        self::activeGrants($uid);
    }

    /**
     * Active grants for $uid, keyed by scope id then permission code.
     *
     * A session hit is returned as stored. A miss loads every active grant
     * for that user. A missing connection or a failed query is not stored,
     * so the next check can try again.
     *
     * @return array<int, array<string, true>>
     */
    private static function activeGrants(int $uid): array {
        global $CFG, $PDOX;

        $cached = Cache::check(self::GRANT_CACHE, $uid);
        if ( is_array($cached) ) {
            return $cached;
        }
        // Pages connect in session_start. Opening a connection here dies
        // when the database is down, including unit tests with the default DSN.
        if ( ! isset($PDOX) || ! is_object($PDOX) ) {
            return array();
        }
        $stmt = $PDOX->queryReturnError(
            "SELECT scope_id, permission
             FROM {$CFG->dbprefix}site_grant
             WHERE user_id = :UID
               AND revoked_at IS NULL
               AND (expires_at IS NULL OR expires_at > NOW())",
            array(':UID' => $uid)
        );
        if ( ! $stmt->success ) {
            return array();
        }
        $grants = array();
        while ( $row = $stmt->fetch(\PDO::FETCH_ASSOC) ) {
            $scope = (int) ($row['scope_id'] ?? 0);
            $code = trim((string) ($row['permission'] ?? ''));
            if ( $scope < 1 || $code === '' ) {
                continue;
            }
            $grants[$scope][$code] = true;
        }
        $stmt->closeCursor();
        Cache::set(self::GRANT_CACHE, $uid, $grants, self::GRANT_CACHE_SECONDS);
        return $grants;
    }
}

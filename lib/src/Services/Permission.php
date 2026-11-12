<?php

namespace Tsugi\Services;

use Tsugi\Services\Courses\CoursesService;

/**
 * In-memory registry of permissions declared by services.
 *
 * Tsugi.php calls assemble() in the same initialization that registers
 * routes. Definitions are not scanned from disk and not stored in the
 * database. Grant checks are a later pass.
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
}

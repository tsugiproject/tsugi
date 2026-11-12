<?php

namespace Tsugi\Services;

/**
 * Base class for Tsugi services.
 *
 * Parallel to Tsugi\Controllers\Tool, which is the base for controllers.
 * Controllers declare routes. Services declare permissions. Tsugi.php
 * assembles both. This is not Tsugi\Core\Tool.
 *
 * permissions() is the first shared capability. A service that declares
 * nothing inherits the empty list.
 */
abstract class Service {

    /**
     * Permission codes this service owns.
     *
     * Keys are stable permission identifiers. Values describe the
     * permission for application screens. Unknown codes are denied
     * later, when a grant is checked.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function permissions(): array {
        return [];
    }
}

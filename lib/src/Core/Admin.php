<?php

namespace Tsugi\Core;

/**
 * Admin console session.
 *
 * Admin pages call this instead of PHP's session_start() so a raw
 * session_start() under admin/ is a missed conversion. This does not
 * call LTIX::session_start(). The admin gate must open a session when
 * the database is down so the passphrase and the upgrade page still
 * work. LTIX::session_start() restores the course and fills ReqScope,
 * and that path needs a database.
 */
class Admin {

    /**
     * Open the PHP session for an admin page.
     *
     * @return bool
     */
    public static function session_start() {
        return session_start();
    }
}

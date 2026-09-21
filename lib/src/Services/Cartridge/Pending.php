<?php

namespace Tsugi\Services\Cartridge;

/**
 * Hold an uploaded cartridge zip across the Settings import select step.
 *
 * The file stays on disk; only a token and path live in $_SESSION.
 * Any stash or load also deletes every ccimp* file in temp older than TTL,
 * including other users' abandoned uploads.
 */
class Pending {

    const SESSION_KEY = 'tsugi_cc_import_pending';
    const TTL_SECONDS = 3600;
    const FILE_PREFIX = 'ccimp';

    /**
     * Remember a moved upload for this instructor and course. Replaces any prior pending zip.
     *
     * @param string $path Absolute path already moved off PHP's upload tmp
     * @param string $name Original filename
     * @return string Token posted back on confirm
     */
    public static function stash($path, $name, $context_id, $user_id) {
        self::sweep();
        $path = (string) $path;
        if ( $path === '' || ! is_readable($path) ) {
            throw new ImportException('Cartridge zip is not readable.');
        }
        if ( ! self::isStashPath($path) ) {
            throw new ImportException('Cartridge zip is not in the import stash directory.');
        }
        $old = self::raw();
        if ( $old !== null && isset($old['path']) && $old['path'] !== $path ) {
            @unlink($old['path']);
        }
        unset($_SESSION[self::SESSION_KEY]);
        $token = bin2hex(random_bytes(16));
        $_SESSION[self::SESSION_KEY] = array(
            'token' => $token,
            'path' => $path,
            'name' => is_string($name) ? $name : '',
            'context_id' => (int) $context_id,
            'user_id' => (int) $user_id,
            'created' => time(),
        );
        return $token;
    }

    /**
     * Pending zip for this course and user, or null if none / expired / mismatched.
     *
     * @return array{token:string,path:string,name:string,context_id:int,user_id:int,created:int}|null
     */
    public static function load($context_id, $user_id) {
        self::sweep();
        $row = self::raw();
        if ( $row === null ) {
            return null;
        }
        if ( (int) $row['context_id'] !== (int) $context_id
            || (int) $row['user_id'] !== (int) $user_id ) {
            return null;
        }
        if ( (int) $row['created'] + self::TTL_SECONDS < time() ) {
            self::clear();
            return null;
        }
        if ( ! is_readable($row['path']) || ! self::isStashPath($row['path']) ) {
            self::clear();
            return null;
        }
        return $row;
    }

    /**
     * True when $token matches the pending row for this course and user.
     *
     * @param mixed $token
     */
    public static function matches($context_id, $user_id, $token) {
        if ( ! is_string($token) || $token === '' ) {
            return false;
        }
        $row = self::load($context_id, $user_id);
        return $row !== null && hash_equals($row['token'], $token);
    }

    /**
     * Unlink the stashed zip and drop the session row.
     */
    public static function clear() {
        $row = self::raw();
        if ( $row !== null && isset($row['path']) && is_string($row['path']) && $row['path'] !== '' ) {
            @unlink($row['path']);
        }
        unset($_SESSION[self::SESSION_KEY]);
    }

    /**
     * @return array{token:string,path:string,name:string,context_id:int,user_id:int,created:int}|null
     */
    private static function raw() {
        if ( ! isset($_SESSION[self::SESSION_KEY]) || ! is_array($_SESSION[self::SESSION_KEY]) ) {
            return null;
        }
        $row = $_SESSION[self::SESSION_KEY];
        foreach ( array('token', 'path', 'name', 'context_id', 'user_id', 'created') as $k ) {
            if ( ! array_key_exists($k, $row) ) {
                unset($_SESSION[self::SESSION_KEY]);
                return null;
            }
        }
        return array(
            'token' => (string) $row['token'],
            'path' => (string) $row['path'],
            'name' => (string) $row['name'],
            'context_id' => (int) $row['context_id'],
            'user_id' => (int) $row['user_id'],
            'created' => (int) $row['created'],
        );
    }

    /**
     * Unlink every stashed cartridge whose mtime is older than TTL.
     *
     * Runs on stash/load so the next import request cleans leftovers from
     * every user, not only the current session.
     */
    public static function sweep() {
        $tmp = realpath(sys_get_temp_dir());
        if ( $tmp === false || ! is_dir($tmp) ) {
            return;
        }
        $cutoff = time() - self::TTL_SECONDS;
        $found = glob($tmp.DIRECTORY_SEPARATOR.self::FILE_PREFIX.'*');
        if ( ! is_array($found) ) {
            return;
        }
        foreach ( $found as $path ) {
            if ( ! is_file($path) || ! self::isStashPath($path) ) {
                continue;
            }
            $mtime = @filemtime($path);
            if ( $mtime === false || $mtime >= $cutoff ) {
                continue;
            }
            @unlink($path);
        }
    }

    /**
     * Uploaded cartridges live in the system temp dir with a ccimp prefix.
     *
     * @param string $path
     * @return bool
     */
    public static function isStashPath($path) {
        $real = realpath($path);
        if ( $real === false ) {
            return false;
        }
        $tmp = realpath(sys_get_temp_dir());
        if ( $tmp === false ) {
            return false;
        }
        $prefix = $tmp.DIRECTORY_SEPARATOR;
        if ( $real !== $tmp && ! str_starts_with($real, $prefix) ) {
            return false;
        }
        return str_starts_with(basename($real), self::FILE_PREFIX);
    }
}

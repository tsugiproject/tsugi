<?php

namespace Tsugi\Core;

use \Tsugi\Services\Mail\MailService;

/** Mail utilities — delegates to Tsugi\Services\Mail\MailService */

class Mail {

    public static function computeCheck($identity) {
        return MailService::computeCheck($identity);
    }

    /** One-to-one / system response mail. */
    public static function sendTransactional($to, $subject, $message, $id=false, $token=false) {
        return MailService::sendTransactional($to, $subject, $message, $id, $token);
    }

    /** Campaign / list-style mail. */
    public static function sendBulk($to, $subject, $message, $id=false, $token=false) {
        return MailService::sendBulk($to, $subject, $message, $id, $token);
    }

    /**
     * @deprecated Prefer sendTransactional() or sendBulk(). Defaults to transactional.
     */
    public static function send($to, $subject, $message, $id=false, $token=false) {
        return MailService::sendTransactional($to, $subject, $message, $id, $token);
    }
}

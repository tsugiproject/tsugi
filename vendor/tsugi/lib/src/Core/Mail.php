<?php

namespace Tsugi\Core;

use \Tsugi\UI\Output;

/** Mail utilities */

class Mail {

    public static function computeCheck($identity) {
        global $CFG;
        return sha1($CFG->mailsecret . '::' . $identity);
    }

    public static function send($to, $subject, $message, $id=false, $token=false) {
        global $CFG;

        if ( (!isset($CFG->maildomain)) || $CFG->maildomain === false ) return;

        if ( isset($CFG->maileol) && isset($CFG->wwwroot) && isset($CFG->maildomain) ) {
            // All good
        } else {
            die_with_error_log("Incomplete mail configuration in mailSend");
        }

        if ( strlen($to) < 1 || strlen($subject) < 1 ) return false;

        $EOL = $CFG->maileol;
        $maildomain = $CFG->maildomain;
        $manage = $CFG->wwwroot . "/profile";
        $unsubscribe_url = $manage;
        if ( strlen($id) > 0 && strlen($token) > 0 ) {
            $unsubscribe_url = Output::getUtilUrl("/unsubscribe?id=$id&token=$token");
        }
        $msg = $message;
        if ( substr($msg,-1) != "\n" ) $msg .= "\n";
        // $msg .= "\nYou can manage your mail preferences at $manage \n";
        // TODO: Make unsubscribe work

        // echo $msg;

        $headers = "From: no-reply@$maildomain" . $EOL .
            "Return-Path: <bounced-$id-$token@$maildomain>" . $EOL .
            "List-Unsubscribe: <$unsubscribe_url>" . $EOL .
            'X-Mailer: PHP/' . phpversion();

        error_log("Mail to: $to $subject");
        // echo $headers;
        return mail($to,$subject,$msg,$headers);
    }
}

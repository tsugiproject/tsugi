<?php

namespace Tsugi\Controllers\Api;

use Tsugi\Core\LTIX;
use Tsugi\Crypt\AesCtr;
use Tsugi\Util\Net;
use Tsugi\Util\U;

/**
 * POST /api/rpc.php
 *
 * A missing token reports the error and then falls through. A bad prefix
 * or an invalid session returns.
 */
class Rpc {

    public function handle(): void
    {
        global $CFG;

        $token = U::get($_REQUEST, 'token');
        if ( ! $token ) $this->failure('No token');

        $decr = AesCtr::decrypt($token, $CFG->cookiesecret, 256);
        $pieces = explode('::', $decr);
        if ( count($pieces) != 2 ) {
            $this->failure('Bad token format');
            return;
        }
        if ( $pieces[0] != $CFG->cookiepad ) {
            $this->failure('Bad token prefix');
            return;
        }

        $session_id = $pieces[1];
        error_log('RPC session: '.$session_id."\n");

        session_id($session_id);

        session_start();
        $LTI = $_SESSION[TSUGI_SESSION_LTI];
        if ( ! $LTI ) {
            $this->failure('Invalid session');
            return;
        }

        $LAUNCH = LTIX::buildLaunch($LTI);

        $object = U::get($_POST, 'object');
        if ( ! $object ) {
            $this->failure('Missing object');
            return;
        }
        $method = U::get($_POST, 'method');
        if ( ! $method ) {
            $this->failure('Missing method');
            return;
        }
        $p1 = U::get($_POST, 'p1');
        if ( ! $p1 ) {
            $this->failure('Missing parameter');
            return;
        }

        $retval = $LAUNCH->{$object}->{$method}($p1);

        print_r($retval);
    }

    private function failure(string $message)
    {
        Net::send400($message);
        $detail = array('detail' => $message);
        print(json_encode($detail));
    }

}

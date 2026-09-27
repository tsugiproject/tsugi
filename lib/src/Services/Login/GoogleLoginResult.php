<?php

namespace Tsugi\Services\Login;

/**
 * Outcome of a Google site login attempt.
 */
class GoogleLoginResult {

    public $success = false;
    public $error = null;
    public $redirect_url = null;
    public $login_url = null;
    public $user_id = null;
    public $user_email = null;
    public $display_name = null;
    public $did_insert = false;
    public $context_id = null;
    public $context_key = null;
}

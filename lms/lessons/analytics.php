<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";
require_once "../lms-util.php";

// Load analytics configuration from register.php
require_once(__DIR__ . '/register.php');

// Wrapper around shared analytics viewer (no redirects).
require_once(__DIR__ . '/../tool_support/analytics.php');

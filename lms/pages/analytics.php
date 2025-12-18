<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";
require_once "../lms-util.php";

// Wrapper around shared analytics viewer (no redirects).
$LMS_ANALYTICS_TOOL = 'pages';
$LMS_ANALYTICS_TITLE = 'Pages';
$LMS_ANALYTICS_BACK = 'pages/';
$LMS_ANALYTICS_STABLE_PATH = '/lms/pages';

require_once(__DIR__ . '/../tool_support/analytics.php');

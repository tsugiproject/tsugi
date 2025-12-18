<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";
require_once "../lms-util.php";

// Wrapper around shared analytics viewer (no redirects).
$LMS_ANALYTICS_TOOL = 'assignments';
$LMS_ANALYTICS_TITLE = 'Assignments';
$LMS_ANALYTICS_BACK = 'assignments/';
$LMS_ANALYTICS_STABLE_PATH = '/lms/assignments';

require_once(__DIR__ . '/../tool_support/analytics.php');

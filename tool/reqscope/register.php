<?php

// Temporary. Remove with ReqScopeDebug around December 2026.
global $CFG;
$show = isset($CFG) && is_object($CFG) && $CFG->getExtension('reqscope_debug') === true;

$REGISTER_LTI2 = array(
    'name' => 'ReqScope',
    'FontAwesome' => 'fa-bug',
    'short_name' => 'ReqScope',
    'description' => 'Temporary dump of ReqScope and the launch. Shown only when reqscope_debug is true.',
    'tool_phase' => 'debug',
    'hide_from_store' => ! $show,
    'messages' => array('launch'),
    'privacy_level' => 'public',
    'license' => 'Apache',
    'languages' => array(
        'English',
    ),
    'placements' => array(),
);

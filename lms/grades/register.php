<?php

$REGISTER_LTI2 = array(
"name" => "Grade Book",
"FontAwesome" => "fa-area-chart",
"short_name" => "Grade Book",
"description" => "This tool allows both the students and instructors to view their grades for
a course across all the LTI tools on this server.  
This tool also provides simple grade review and maintenance tools for instructors.",
"privacy_level" => "public",  // anonymous, name_only, public
    "messages" => array("launch"),
    "license" => "Apache",
    "languages" => array(
        "English"
    ),
    "source_url" => "https://github.com/tsugitools/grade",
    // For now Tsugi tools delegate this to /lti/store
    "placements" => array(
        "course_navigation"  // Would be nice if this happened :)
        /*
        "course_navigation", "homework_submission",
        "course_home_submission", "editor_button",
        "link_selection", "migration_selection", "resource_selection",
        "tool_configuration", "user_navigation"
        */
    ),

);

// Analytics configuration
$LMS_ANALYTICS_TOOL = 'grades';
$LMS_ANALYTICS_TITLE = 'Grade Book';
$LMS_ANALYTICS_BACK = 'grades/';
$LMS_ANALYTICS_STABLE_PATH = '/lms/grades';

<?php

$REGISTER_LTI2 = array(
    "name" => "Visit URL",
    "FontAwesome" => "fa-external-link",
    "short_name" => "Visit URL",
    "description" => "Send students to an instructor-configured web page. When a student
clicks to visit the URL, the tool can send a 100% grade back to the LMS. Instructors
can add a title, instructions, and a due date with a late penalty.",
    "messages" => array("launch", "launch_grade"),
    "tool_phase" => "core",
    "privacy_level" => "name_only",  // anonymous, name_only, public
    "license" => "Apache",
    "languages" => array(
        "English"
    ),
    "analytics" => array(
        "internal"
    ),
    "source_url" => "https://github.com/tsugiproject/tsugi",
    // For now Tsugi tools delegate this to /lti/store
    "placements" => array(
        /*
        "course_navigation", "homework_submission",
        "course_home_submission", "editor_button",
        "link_selection", "migration_selection", "resource_selection",
        "tool_configuration", "user_navigation"
        */
    ),
    "screen_shots" => array(
    )
);

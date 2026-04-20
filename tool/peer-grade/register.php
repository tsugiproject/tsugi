<?php

$REGISTER_LTI2 = array(
"name" => "Peer-Graded Dropbox",
"FontAwesome" => "fa-code-fork",
"short_name" => "Peer Grader",
"description" => "This tool provides a structured dropbox that can take images,
URLs, text and code.  These tools can be peer-graded, instructor graded,
or a blend of peer and instructor graded assignments.",
"messages" => array("launch", "launch_grade"),
 "privacy_level" => "public",  // anonymous, name_only, public
    "license" => "Apache",
    "languages" => array(
        "English"
    ),
    "analytics" => array(
        "internal"
    ),
    "source_url" => "https://github.com/tsugitools/peer-grade",
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
        "store/screen-01.png",
        "store/screen-02.png",
        "store/screen-03.png",
        "store/screen-04.png",
        "store/screen-inst-01.png",
        "store/screen-inst-02.png",
        "store/screen-inst-03.png",
        "store/screen-inst-04.png",
        "store/screen-inst-05.png",
        "store/screen-inst-06.png",
        "store/screen-inst-07.png",
        "store/screen-analytics.png"
    )
);


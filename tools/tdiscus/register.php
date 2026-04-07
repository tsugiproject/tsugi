<?php

$REGISTER_LTI2 = array(
"name" => "Threaded Discussion",
"FontAwesome" => "fa-comments",
"short_name" => "Discussion tool",
"description" => "This is a threaded discussion tool that can be used through LTI.  Multi-level discussions are supported with instructor-controlled hierarchy depth. The instructor can pin, hide, or lock, threads.  The tool can be set up to award grades for students when they post a comment on a thread.  The tool can be placed many times in a course and each of the placements has its own set of threads and comments. Anonymous students can view the discussions but cannot post to threads.  The tool provides usage analytics.",
    // By default, accept launch messages..
    "messages" => array("launch", "launch_grade"),
    "privacy_level" => "public",  // anonymous, name_only, public
    "license" => "Apache",
    "languages" => array(
        "English"
    ),
    "video" => "https://youtu.be/_be5vBiljng",
    "source_url" => "https://github.com/tsugitools/tdiscus",
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
    )

);

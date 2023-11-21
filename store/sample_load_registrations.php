<?php

/*
 * To override the tools listed in the store, copy this file into the folder above
 * 'tsugi' and do whatever it takes to fill it with data.
 *
 * This completely replaces all tool registrations
 */

function loadRegistrations() {
    $retval = array();

    $entry = array(
            "name" => "Tom Autograder",
            "FontAwesome" => "fa-check-square-o",
            "short_name" => "Python Autograder",
            "description" => "This is an autograder for the assignments from www.py4e.com (Python for Everybody).",
            "messages" => array(
                    "0" => "launch",
                    "1" => "launch_grade",
                ),
            "icon" => "http://localhost:8888/tsugi-static/fontawesome-free-5.8.2-web/png/check-square-o.png",
            "url" => "http://localhost:8888/py4e/tools/pythonauto/?x=42",
    );

    $retval["tom"] = $entry;
    return $retval;
};


<?php
$instdata = array(
      "lis_person_name_full" => 'Jane Instructor',
      "lis_person_name_family" => 'Instructor',
      "lis_person_name_given" => 'Jane',
      "lis_person_contact_email_primary" => "inst@ischool.edu",
      "lis_person_sourcedid" => "ischool.edu:inst",
      "user_image" => "https://static.tsugi.org-4.7.0/png/birthday-cake.png",
      "user_id" => "292832126",
      "roles" => "Instructor"
);
if ( isset($CFG->fallbacklocale) && $CFG->fallbacklocale ) {
    $instdata["launch_presentation_locale"] = $CFG->fallbacklocale;
}

$learner1 = array(
      "lis_person_name_full" => 'Sue Student',
      "lis_person_name_family" => 'Student',
      "lis_person_name_given" => 'Sue',
      "lis_person_contact_email_primary" => "student@ischool.edu",
      "lis_person_sourcedid" => "ischool.edu:student",
      "user_id" => "998928898",
      "user_image" => "https://static.tsugi.org/font-awesome-4.7.0/png/briefcase.png",
      "launch_presentation_locale" => "en-US",
      "roles" => "Learner"
);

$learner2 = array(
      "lis_person_name_full" => 'Ed Student',
      "lis_person_name_family" => 'Student',
      "lis_person_name_given" => 'Ed',
      "lis_person_contact_email_primary" => "ed@ischool.edu",
      "lis_person_sourcedid" => "ischool.edu:ed",
      "user_id" => "121212331",
      "user_image" => "https://static.tsugi.org/font-awesome-4.7.0/png/eye.png",
      "launch_presentation_locale" => "es-ES",
      "roles" => "Learner"
);

$learner3 = array(
      "lis_person_name_full" => '',
      "lis_person_name_family" => '',
      "lis_person_name_given" => '',
      "lis_person_contact_email_primary" => '',
      "lis_person_sourcedid" => "ischool.edu:ed",
      "user_id" => "777777777",
      "user_image" => "https://static.tsugi.org/font-awesome-4.7.0/png/eye-slash.png",
      "roles" => "Learner"
);

$lmsdata = array(
      "lis_person_name_full" => 'Jane Instructor',
      "lis_person_name_family" => 'Instructor',
      "lis_person_name_given" => 'Jane',
      "lis_person_contact_email_primary" => "inst@ischool.edu",
      "lis_person_sourcedid" => "ischool.edu:inst",
      "user_id" => "292832126",
      "user_image" => "https://static.tsugi.org/font-awesome-4.7.0/png/birthday-cake.png",
      "roles" => "Instructor",
      "resource_link_id" => "292832126",
      "resource_link_title" => "Weekly Blog",
      "resource_link_description" => "A weekly blog.",
      "context_id" => "456434513",
      "context_label" => "SI106",
      "context_title" => "Introduction to Programming",
      "tool_consumer_info_product_family_code" => "ims",
      "tool_consumer_info_version" => "1.1",
      "tool_consumer_instance_guid" => "lmsng.ischool.edu",
      "tool_consumer_instance_description" => "University of Information"
      // 'launch_presentation_return_url' => $cur_url
      );

$lms_identities = array(
    'learner1' => $learner1,
    'learner2' => $learner2,
    'learner3' => $learner3,
    'instructor' => $instdata
);

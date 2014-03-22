<?php
$instdata = array(
      "lis_person_name_full" => 'Jane Instructor',
      "lis_person_name_family" => 'Instructor',
      "lis_person_name_given" => 'Jane',
      "lis_person_contact_email_primary" => "inst@ischool.edu",
      "lis_person_sourcedid" => "ischool.edu:inst",
      "user_id" => "292832126",
      "roles" => "Instructor"
);

$learner1 = array(
      "lis_person_name_full" => 'Sue Student',
      "lis_person_name_family" => 'Student',
      "lis_person_name_given" => 'Sue',
      "lis_person_contact_email_primary" => "student@ischool.edu",
      "lis_person_sourcedid" => "ischool.edu:student",
      "user_id" => "998928898",
      "roles" => "Learner"
);

$learner2 = array(
      "lis_person_name_full" => 'Ed Student',
      "lis_person_name_family" => 'Student',
      "lis_person_name_given" => 'Ed',
      "lis_person_contact_email_primary" => "ed@ischool.edu",
      "lis_person_sourcedid" => "ischool.edu:ed",
      "user_id" => "121212331",
      "roles" => "Learner"
);

$lmsdata = array(
      "custom_assn" => "mod/map/index.php",

      "lis_person_name_full" => 'Jane Instructor',
      "lis_person_name_family" => 'Instructor',
      "lis_person_name_given" => 'Jane',
      "lis_person_contact_email_primary" => "inst@ischool.edu",
      "lis_person_sourcedid" => "ischool.edu:inst",
      "user_id" => "292832126",
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
      "tool_consumer_instance_description" => "University of Information",
	  "custom_due" => "2016-12-12 10:00:00.5",
	  // http://www.php.net/manual/en/timezones.php
	  "custom_timezone" => "Pacific/Honolulu",
	  "custom_penalty_time" => "" . 60*60*24,
	  "custom_penalty_cost" => "0.2"
      // 'launch_presentation_return_url' => $cur_url
      );

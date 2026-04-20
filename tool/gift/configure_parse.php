<?php
/* -----------------------------------------------------------------------------
//                        configure_parse.php
// Parse POST data from configure.php in order to pull out the details for each
// question and create a GIFT formatted string that can be read by check_gift()
// -----------------------------------------------------------------------------
*/

// The important one. Roll through the POST data until we run out of questions,
// then return the final GIFT formatted string
function parse_configure_post() {
  $questions = array();
  $q_num = 1;
  $question_details = array("Not Null"); // Make sure the first time we enter the loop, this isn't Null
  while ($question_details != Null) {
    // pass the current number to question details and see if we can get the details for it
    $question_details = get_question($q_num);
    // if there is data for a question, create the format. If it's null, we're done and it's time to exit the loop
    if ($question_details != Null) {
      // if it's not null, just tack it onto the $questions array for later
      array_push($questions, create_gift_format($question_details));
    }
    // increment the question number to get the next one
    $q_num++;
  }
  // join all of the questions array (strings of GIFT format) with a double return between each
  return implode("\n\n", $questions);
}

// Get the details of a specific question from the POST data, by question number
function get_question($num) {
  $question_details = array('answer'=>array()); // initialize our array
  foreach ($_POST as $key => $value) { // Loop through the entire POST
    // We're only interested in keys which have "questionX" in them, and which have a value associated with them
    if ((strpos($key, "question".$num) > 0) &&($value != Null)) {
      // Trim off the "_questionX" part of the key and make that the key name
      $key_name = implode("_", explode("_", $key, -1));
      // Is this an answer option?
      if (strpos($key_name, "answer") !== false) {
        // Get the number for this answer from the key name (format "answerX" or "answerX_iscorrect")
        $key_parts = explode("_", $key_name); // make an array from the key name (['answerX', 'questionX'])
        $answer_index = explode("answer", $key_parts[0])[1];
        // is this the text of the answer or an indicator that this is the correct answer?
        if (sizeof($key_parts) > 1 && $key_parts[1] == 'iscorrect') {
          // T/F questions are a little different. We can identify them because they have an 'iscorrect' key
          $question_details['answer'][$answer_index]['iscorrect'] = true;
        } else {
          $question_details['answer'][$answer_index] = array('text'=>$value, 'iscorrect'=>false);
        }
      } else {
        // It's not an answer option, so jus save the k-v pair
        $question_details[$key_name] = $value;
      }
    }
  }

  // if we actaully found something return it
  if (sizeof($question_details) > 1) {
    return $question_details;
  } else {
    // otherwise, return Null, which will indicate to parse_configure_post that we're done
    return Null;
  }
}

// Create the GIFT format string from the question details returned by get_question
function create_gift_format($question) {
  $answers = Null; // initialize
  if ($question['type'] == "true_false_question") {
    // if the answer is "true", make answers "T". Otherwise, make it "F"
    $answers = (($question['answer'][1]['text'] == 'true') ? "T" : "F");
  } elseif (($question['type'] == "multiple_choice_question") || ($question['type'] == "multiple_answers_question")) {
    $answers = array();
    // iterate through the answers to this question
    foreach ($question['answer'] as $answer) {
      if ($answer['iscorrect']) {
        array_push($answers, "={$answer['text']}"); // GIFT indication for a correct answer
      } else {
        array_push($answers, "~{$answer['text']}"); // Incorrect answer
      }
    }
    $answers= implode(" ", $answers); // smash all the answers together seperated by spaces
  } elseif ($question['type'] == "short_answer_question") {
    // only difference between MC/MA and SA answers is that SA answers are only provided if they are "correct"
    // ignore the "is correct" bit of the array, and just add every one of the answers
    $answers = array();
    foreach ($question['answer'] as $answer) {
      array_push($answers, "={$answer['text']}");
    }
    $answers= implode(" ", $answers);
  }
  // was the html box checked? if so, prepend the text with [html] for the parser
  if (isset($question['html'])) {
    $question['text'] = '[html]'.$question['text'];
  }
  // create the formatted string and return it
  $title = $question['title'] ?? ' ';
  return "::{$title}:: {$question['text']} {{$answers}}";
}

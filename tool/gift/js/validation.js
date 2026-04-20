/* -----------------------------------------------------------------------------
//                        validation.js
// Check the quiz to make sure we don't have an error that's going to create
// a problem when we try and save.
// -----------------------------------------------------------------------------
*/

/* Iterate through every question in the quiz and call validate_question
// on each. Any errors that validate_question returns are added to a list,
// which is shown in the error list div.
// If there are no errors, make sure the error list div is hidden and the
// "save" buttons are enabled.
*/
function validate_quiz() {
  var validation_errors = [];
  for (var i = 1; i <= $(".question-container").length; i++) {
    var question_validation_errors = validate_question(i);
    // don't touch the error list unless we got something back
    if (question_validation_errors != null) {
      validation_errors = validation_errors.concat(question_validation_errors);
    }
  }

  // is there at least one question in the quiz?
  if ($(".question-container").length == 0) {
    validation_errors.push("Quiz must contain at least one question");
  }

  // Did we get any errors?
  if (validation_errors.length > 0) {
    // Yes, so show the error list
    $("#validation-error-list").show();
    // disable the save buttons
    $("input[name='save_quiz']").attr("disabled", "disabled");
    // set the text at the start of the div
    $("#validation-error-list").html('<p>WARNING: Please fix the following errors before saving...</p>');

    // Add each error as a seperate <p> element
    for (var i = 0; i < validation_errors.length; i++) {
      var p = $("<p></p>").text(validation_errors[i]);
      $("#validation-error-list").append(p);
    }
  }
  else {
    // No errors, so hide the error list and enable the "save" buttons
    $("#validation-error-list").hide();
    $("input[name='save_quiz']").removeAttr("disabled");
  }
  lti_frameResize();
}

// Check the given question number to make sure there are no validation errors
// If there are, return a list of strings indicating which errors were found
// If not, return null
function validate_question(num) {
  var validation_errors = [];
  var q_div = "#question"+num; // the div id for this question
  var q_type = $(q_div + " [type='hidden']").val(); // the type of this question

  // check that there's at least something in the question text field
  var q_text = $(q_div + " [name^=text_question]");
  if (q_text.val().length == 0) {
    q_text.addClass("warning");
    validation_errors.push("Question " + num + ": Question Text must not be blank.");
  } else {
    q_text.removeClass("warning");
  }

  // specific checks for different question types
  if (q_type == "true_false_question") {
    // make sure the user has selected either true or false for this question
    var checked_count = $(q_div + " [type='radio']:checked").length;
    if (checked_count == 0) {
      $(q_div + " .truefalse-container").addClass("warning");
      validation_errors.push("Question " + num + ": True/False Questions must have \"True\" or \"False\" selected.");
    } else {
      $(q_div + " .truefalse-container").removeClass("warning");
    }
  } else if ( q_type == "multiple_choice_question" || q_type == "multiple_answers_question") {
    // make sure there's at least one right answer checked for MC/MA questions
    var checked_count = $(q_div + " .possible-answer > [type='checkbox']:checked").length;
    if (checked_count == 0) {
      $(q_div + " .question-content-container").addClass("warning");
      validation_errors.push("Question " + num + ": At least one correct answer must be provided.");
    } else {
      $(q_div + " .question-content-container").removeClass("warning");
    }
  } else if (q_type == "short_answer_question") {
    // make sure there's at least one answer at least 1 character long
    var answer_present = false;
    var answers = $(q_div + " .answer-option");
    for (var i = 0; i < answers.length; i++) {
      if ($(answers[i]).val().length > 0) {
        answer_present = true;
      }
    }
    if (!answer_present) {
      $(q_div + " .question-content-container").addClass("warning");
      validation_errors.push("Question " + num + ": At least one correct answer must be provided.");
    } else {
      $(q_div + " .question-content-container").removeClass("warning");
    }
  }
  // return the list of errors, or null if there weren't any
  if (validation_errors.length > 0) {
    return validation_errors;
  } else {
    return null;
  }
}

// Add a new question when the dropdown is changed
$("#question_type_select").change(function() {
  var selected_value = $("#question_type_select").val();
  if (selected_value != "") { // As long as the selected value isn't the placeholder
    // Create a new context for the templates
    var context = {};
    context.count = $("#quiz_content").children().length+1;
    switch(context.type) {
      case "true_false_question": addTrueFalse(context); break;
      case "multiple_choice_question": addMultipleChoice(context); break; // Multiple choice and multiple answer are handled the same
      case "multiple_answers_question": addMultipleChoice(context); break; // Multiple choice and multiple answer are handled the same
      case "short_answer_question": addShortAnswer(context); break;
      // case "essay_question": addEssay(context); break;
      default:
    }

    context.type = selected_value;
    addQuestion(context);
    $("#question_type_select").val(""); // reset the dropdown
    set_focus_on_lastquestion();
  }
});

/* Add a question to the form with the given context
   context should have, at minimum, "count" and "type"
   if more is included in the context (i.e. we are loading a quiz from a saved gift),
   what is required changes based on the type of question (provided by parse_gift(), but for reference):
      T/F: "answer" (either "T" or "F")
      MC/MA/SA: "parsed_answer" (array of arrays, each in the form [bool, 'answer_text', '', 'question_id'])
*/
function addQuestion(context) {
  switch(context.type) {
    case "true_false_question":
      context.PrettyType = "True/False";
      $('#quiz_content').append(tsugiHandlebarsRender('common', context))
      addTrueFalse(context);
      break;
    case "multiple_choice_question":
      context.PrettyType = "Multiple Choice/Multiple Answer";
      $('#quiz_content').append(tsugiHandlebarsRender('common', context))
      addMultipleChoice(context);
      break; // Multiple choice and multiple answer are handled the same
    case "multiple_answers_question":
      context.PrettyType = "Multiple Choice/Multiple Answer";
      $('#quiz_content').append(tsugiHandlebarsRender('common', context))
      addMultipleChoice(context);
      break; // Multiple choice and multiple answer are handled the same
    case "short_answer_question":
      context.PrettyType = "Short Answer";
      $('#quiz_content').append(tsugiHandlebarsRender('common', context))
      addShortAnswer(context);
      break;
    case "essay_question":
      context.PrettyType = "Essay Answer";
      $('#quiz_content').append(tsugiHandlebarsRender('common', context))
      break;
    default: console.log("unrecognized question type: " + context.type);
  }

  set_enabled_question_types("#question"+context.count+"_type_select", context.type);

  lti_frameResize();
}

// Add a True/False question to the form. If the context has an answer, fill it out
function addTrueFalse(context) {
  if (context.answer == "T") {
    context.answer_true = true;
  } else if (context.answer == "F") {
    context.answer_false = true;
  }
  addAnswer('#content_question'+context.count, 'tf_authoring', context)
}

// Add a Multiple Choice/Multiple Answer Question to the form. If there are answers in the context, add them
function addMultipleChoice(context) {
  if ("parsed_answer" in context) {
    for (var a=0; a<context.parsed_answer.length; a++) {
      var answer_context = {};
      // tell the template to skip adding the "+" button on the first answer option
      answer_context.first = (a==0);
      answer_context.isCorrect = context.parsed_answer[a][0];
      answer_context.value = context.parsed_answer[a][1];
      addAnswer('#content_question'+context.count, 'mc_authoring', answer_context)
    }
    var answer_number = context.parsed_answer.length
  } else {
    var answer_number = 1;
  }
  // Always add one empty answer field
  context.num = ++answer_number;
  addAnswer('#content_question'+context.count, 'mc_authoring', context)
}

// Add a Short Answer Question to the form. If there are answers in the context, add them
function addShortAnswer(context) {
  if ("parsed_answer" in context) {
    for (var a=0; a<context.parsed_answer.length; a++) {
      var answer_context = {};
      // tell the template to skip adding the "+" button on the first answer option
      answer_context.first = (a==0);
      answer_context.value = context.parsed_answer[a][1];
      addAnswer('#content_question'+context.count, 'sa_authoring', answer_context)
    }
    var answer_number = context.parsed_answer.length
  } else {
    var answer_number = 1;
  }
  // Always add one empty answer field
  context.num = ++answer_number;
  addAnswer('#content_question'+context.count, 'sa_authoring', context)
}

// Adds an answer option to a given div
// requires a div ID, a template
// optional object answer_context (only for loading quizes)
function addAnswer(div, template_name, answer_context={}) {
  answer_context.num = $(div).children().length + 1;
  answer_context.count = div.split("question")[1];
  answer_context.first = (answer_context.num == 1);
  $(div).append(tsugiHandlebarsRender(template_name, answer_context))
  lti_frameResize();
}

// runs through all the provided answers for the given question number and modifies the
// html so the answers are always numbered sequentially from 1 to X
function renumber_answers(question_number) {
  var answers = $("#content_question"+question_number).children();
  for (var i = 0; i < answers.length; i++) {
    // get the number that this answer currently has
    var to_replace = answers[i].id.split('_')[1];
    // update the id of the div for this with the new answer
    answers[i].id = answers[i].id.replace(to_replace, "answer" + (i+1));

    // see if the checkbox was checked, since this property gets wiped in a sec
    var is_checked = false;
    if ($(answers[i]).find("[type=checkbox]:checked").length == 1) {
      is_checked = true;
    }

    save_values_to_html($("#question"+question_number));

    // update the entirety of the html for this div with the new answer
    var html = $(answers[i]).html();
    var new_html = html.replace(new RegExp(to_replace, 'g'), "answer" + (i+1));
    $(answers[i]).html(new_html);

    // if the checkbox was checked, re-check it here
    if (is_checked) {
      $(answers[i]).find("[type=checkbox]").prop("checked", true);
    }

    // if this is not the last button, make sure the "+" button is display:none
    if (i < answers.length-1) {
      $(answers[i]).find("[name^=btn_add]").hide();
    } else {
      $(answers[i]).find("[name^=btn_add]").show();
    }
  }
}

// In the event a question is deleted, run through the form and re-number all of the items
// very similar to renumber_answers()
function renumber_questions() {
  var questions = $("#quiz_content").children();
  for (var i = 0; i < questions.length; i++) {
    // get the string we need to replace later (question_)
    var to_replace = questions[i].id;
    // change the H1 for the question
    $(questions[i]).find("h1").text("Question " + (i+1));
    // change the id for the div
    questions[i].id = "question"+(i+1);

    save_values_to_html(questions[i]);

    var html = $(questions[i]).html();
    var new_html = html.replace(new RegExp(to_replace, 'g'), "question"+(i+1));
    $(questions[i]).html(new_html);
  }
}

// Called by the question type selector in each question header.
// Replaces the answer options with those of the newly selected type
function change_question_type(question_div) {
  var question_num = question_div.split("question")[1];
  var selected_value = $("#question"+question_num+"_type_select").val();
  $("#question"+question_num+"_type_select").val(""); //reset the dropdown
  if (selected_value != "") {
    // Remove all of the answers in the content container
    $("#content_question" + question_num).html("");

    var template_type;
    switch(selected_value) {
      case "true_false_question": template_type="tf_authoring"; break;
      case "multiple_choice_question": template_type="mc_authoring"; break;
      case "multiple_answers_question": template_type="mc_authoring"; break;
      case "short_answer_question": template_type="sa_authoring"; break;
    }

    addAnswer("#content_question" + question_num, template_type);

    // Change the hidden question type value so the validator knows what to look for
    $("#type_question" + question_num).val(selected_value);
    // Disable the option that matches the new type and make sure the others are enabled
    set_enabled_question_types("#question"+question_num+"_type_select", selected_value);
  }
}

// For a given select element, set the option whose value matches disabled_type
// to disabled and enable all of the others.
function set_enabled_question_types(select_id, disabled_type) {
  $(select_id).children().each(function() {
    if (this.value == disabled_type) {
      $(this).attr("disabled", "disabled");
    } else {
      $(this).removeAttr("disabled");
    }
  });
}

// the .html() function doesn't grab values which have been input by the user
// since the form was rendered. This function grabs all of the user's values
// and drops them in the default value property of each input so that they get
// picked up
// From https://stackoverflow.com/a/15825509
function save_values_to_html(question_div) {
  $(question_div).find("[type=text], textarea").each(function() {
    this.defaultValue = this.value;
  });
  $(question_div).find('[type=checkbox], [type=radio]').each(function() {
    this.defaultChecked = this.checked;
  });
  $(question_div).find('select option').each(function() {
    this.defaultSelected = this.selected;
  });
}

// -----------------------------------------------------------------------------
//                        KEYBOARD NAV FUNCTIONS
// -----------------------------------------------------------------------------

// Set the cursor focus on the last text box in the given div
// Used when the user presses the "+" button to add a new answer option
function set_focus_on_lastinput(sender) {
  $(sender + " div:last-child input[type=text]").focus();
}

// Set the cursor focus on the textarea of the last question in the form
// Used when the user adds a new question to the quiz
function set_focus_on_lastquestion() {
  $("#quiz_content").children().last().find("textarea").focus();
}

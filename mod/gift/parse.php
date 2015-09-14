<?php

function parse_gift($text, &$questions, &$errors) {
$raw_questions = array();
$question = "";
$lines = explode("\n", $text);
foreach ( $lines as $line ) {
    $line = rtrim($line);
    // print $line."\n";
    if ( strpos($line, "//") === 0 ) continue;
    if ($line == "" ) {
        if ( strlen($question) > 0 ) {
            $raw_questions[] = $question;
            $question = "";
        }
        continue;
    }
    if ( strlen($question) > 0 ) $question .= "\n";
    $question .= $line;
}

if ( strlen($question) > 0 ) {
    $raw_questions[] = $question;
}

// var_dump_pre($raw_questions);

foreach ( $raw_questions as $raw ) {
    $pieces = explode('::', $raw);
    if ( count($pieces) != 3 ) {
        $errors[] = "Mal-formed question: ".$raw;
        continue;
    }
    // print_r($pieces);
    $name = trim($pieces[1]);
    $text = trim($pieces[2]);
    $spos = false;
    $epos = false;
    $answer = false;
    $escape = false;
    $found = false;
    // echo("==================\n".$text."\n");
    // Parse out the overall question and answer.
    for ( $i=0; $i < strlen($text); $i++ ) {
        $ch = $text[$i];
        // Eat up the \{ and \} escapes
        if ( $escape && ($ch == '{' || $ch == '}') ) {
            if ( $answer !== false ) $answer .= $ch;
            $escape = false;
            continue;
        }
        if ( $ch == '\\' && ! $escape ) {
            $escape = true;
            continue;
        }

        if ( $answer === false ) {
            if ( $ch != '{' ) continue;
            $answer = "";
            $spos = $i;
            continue;
        }
        if ( $ch == '}' ) {
            $epos = $i;
            $found = true;
            break;
        }
        if ( $escape ) {
            $answer .= '\\';
            $answer .= $ch;
            $escape = false;
        } else {
            if ( $ch == '\\' ) {
                $escape = true;
                continue;
            }
            $answer .= $ch;
        }
    }

    if ( $found === false ) {
        $errors[] = "Could not find answer: ".$raw;
        continue;
    }

    $answer = trim($answer);
    // echo("Answer=".$answer."\n");

    // We won't know until later if the question is short answer or not.
    if ( $epos == strlen($text)-1 ) {
        $question = trim(substr($text,0,$spos-1));
        $sa_question = $question;   // No blank at the end of the question
    } else {
        $question = trim(substr($text,0,$spos-1)) . " " . trim(substr($text,$epos+1));
        $sa_question = trim(substr($text,0,$spos-1)) . " [_____] " . trim(substr($text,$epos+1));
    }

    // Fix the remaining escapes in the question
    $question = str_replace(array('\\{','\\}'), array('{', '}'),$question);

    // echo("Question=".$question."\n");

    if ( strpos($answer, "->" ) > 0 ) {
        $type = 'matching_question'; // CHECK THIS
        $errors[] = "Matching questions not yet supported: ".$raw;
        continue;
    } else if ( strpos($answer,"T") === 0 || strpos($answer, "F") === 0 ) {
        $type = 'true_false_question';
    } else if ( strlen($answer) < 1 ) {
        $type = 'essay_question';
    } else if ( strpos($answer, '#') === 0 ) {
        $type = 'numerical_question';
        $errors[] = "Numerical questions not yet supported: ".$raw;
        continue;
    }  else if ( strpos($answer,"=") === 0 || strpos($answer, "~") === 0 ) {
        $type = 'multiple_choice_question';  // Also will be multiple_answer and short_answer
    } else { 
        $errors[] = "Could not determine question type: ".$raw;
        continue;
    }
    $answers = array();
    $parsed_answer = false;
    $correct_answers = 0;
    $incorrect_answers = 0;
    // Also will be multiple_answer_question and short_answer_question
    if ( $type == 'multiple_choice_question') {
        $parsed_answer = array();
        $correct = null;
        $answer_text = false;
        $feedback = false;
        $in_feedback = false;

        for($i=0;$i<strlen($answer)+1; $i++ ) {
            $ch = $i < strlen($answer) ? $answer[$i] : -1;
            // Handle escape sequences
            if ( $ch == '\\' && $i < strlen($answer)-1) {
                $nextch = $answer[$i+1];
                if ( strpos("~=#{}:->%",$nextch) !== false ) {
                    if ( $in_feedback ) {
                        $feedback .= $nextch;
                    } else {
                        $answer_text .= $nextch;
                    }
                    $i = $i + 1;  // Advance past the nextch
                    continue;
                }
            }

            // echo $i," ", $ch, "\n";
            // Finish up the previous entry
            if ( ( $ch == -1 || $ch == '=' || $ch == "~" ) && strlen($answer_text) > 0 ) {
                if ( $correct === null || $answer_text === false ) {
                    $errors[] = "Mal-formed answer sequence: ".$raw;
                    $parsed_answer = array();
                    break;
                }
                if ( $correct ) {
                    $correct_answers++;
                } else {
                    $incorrect_answers++;
                }
                $code = substr(md5(trim($name.$question.$answer_text)),0,10);
                $parsed_answer[] = array($correct, trim($answer_text), trim($feedback), $code);
                // Set up for the next one
                $correct = null;
                $answer_text = false;
                $feedback = false;
                $in_feedback = false;
            }

            // We are done...
            if ( $ch == -1 ) break;

            // right or wrong?
            if ( $ch == '=' ) {
                $correct = true;
                continue;
            }
            if ( $ch == '~' ) {
                $correct = false;
                continue;
            }

            // right or wrong?
            if ( $ch == '#' && $in_feedback === false ) {
                $in_feedback = true;
                continue;
            }

            if ( $in_feedback ) {
                $feedback .= $ch;
            } else {
                $answer_text .= $ch;
            }

        }
        if ( count($parsed_answer) < 1 ) {
            $errors[] = "Mal-formed answer sequence: ".$raw;
            continue;
        }
        if ( $correct_answers < 1 ) {
            $errors[] = "No correct answers found: ".$raw;
            continue;
        } else if ( $correct_answers == 1 && $incorrect_answers > 0 ) {
            $type = 'multiple_choice_question';
        } else if ( $correct_answers > 1 && $incorrect_answers > 0 ) {
            $type = 'multiple_answers_question';
        } else if ( $correct_answers > 0 && $incorrect_answers == 0 ) {
            $type = 'short_answer_question';
            $question = $sa_question;
        } else {
            $errors[] = "Could not determine question type: ".$raw;
            continue;
        }
    }
    // echo "\nN: ",$name,"\nQ: ",$question,"\nA: ",$answer,"\nType:",$type,"\n";
    $qobj = new stdClass();
    $qobj->name = $name;
    $qobj->question = $question;
    $qobj->code = substr(md5($name.$question),0,9);
    $qobj->answer = $answer;
    $qobj->type = $type;
    $qobj->parsed_answer = $parsed_answer;
    $qobj->correct_answers = $correct_answers;
    $questions[] = $qobj;
}

// var_dump_pre($questions);

}


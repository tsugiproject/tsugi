<?php

// We can't use U::strlen because of a weird interaction between config.php and XML parsing...

use \Tsugi\Util\Mersenne_Twister;

function parse_gift($text, &$questions, &$errors) {
    $raw_questions = array();
    $question = "";
    $lines = explode("\n", $text);
    foreach ( $lines as $line ) {
        $line = rtrim($line);
        // print $line."\n";
        if ( strpos($line, "//") === 0 ) continue;
        if ($line == "" ) {
            if ( strlen($question ?? '') > 0 ) {
                $raw_questions[] = $question;
                $question = "";
            }
            continue;
        }
        if ( strlen($question ?? '') > 0 ) $question .= "\n";
        $question .= $line;
    }

    if ( strlen($question ?? '') > 0 ) {
        $raw_questions[] = $question;
    }

    // var_dump_pre($raw_questions);

    $quesno = 0;
    foreach ( $raw_questions as $raw ) {
        // Skip Moodle-specific $CATEGORY lines
        if ( preg_match('/^\$CATEGORY:/', trim($raw)) ) {
            continue;
        }
        
        $pieces = explode('::', $raw, 3);
        if ( count($pieces) != 3 ) {
            $errors[] = "Mal-formed question: ".$raw;
            continue;
        }
        // print_r($pieces);
        $name = trim($pieces[1]);
        $text = trim($pieces[2]);
        
        // Find the matching brace pair - need to handle nested/escaped braces properly
        // Find the LAST matching brace pair (answer section is always at the end)
        $spos = false;
        $epos = false;
        $escape = false;
        $brace_depth = 0;
        $last_brace_start = false;
        $last_brace_end = false;
        
        // Scan from the end to find the last complete brace pair
        // This handles cases like {{ variable }} in question text
        for ( $i=strlen($text)-1; $i >= 0; $i-- ) {
            $ch = $text[$i];
            
            // Check if previous char was escape
            $is_escaped = ($i > 0 && $text[$i-1] == '\\');
            $escape_count = 0;
            for ( $j = $i-1; $j >= 0 && $text[$j] == '\\'; $j-- ) {
                $escape_count++;
            }
            $is_escaped = ($escape_count % 2 == 1);
            
            if ( $is_escaped ) {
                continue;
            }

            if ( $ch == '}' && $last_brace_end === false ) {
                // Found a closing brace - work backwards to find its matching opening brace
                $depth = 1;
                $last_brace_end = $i;
                for ( $j = $i-1; $j >= 0; $j-- ) {
                    $ch2 = $text[$j];
                    $is_escaped2 = false;
                    if ( $j > 0 ) {
                        $escape_count2 = 0;
                        for ( $k = $j-1; $k >= 0 && $text[$k] == '\\'; $k-- ) {
                            $escape_count2++;
                        }
                        $is_escaped2 = ($escape_count2 % 2 == 1);
                    }
                    
                    if ( !$is_escaped2 ) {
                        if ( $ch2 == '}' ) {
                            $depth++;
                        } elseif ( $ch2 == '{' ) {
                            $depth--;
                            if ( $depth == 0 ) {
                                $last_brace_start = $j;
                                break;
                            }
                        }
                    }
                }
                if ( $last_brace_start !== false ) {
                    break;
                }
            }
        }
        
        $spos = $last_brace_start;
        $epos = $last_brace_end;

        if ( $spos === false || $epos === false || $spos >= $epos) {
            $errors[] = "Could not find answer braces spos=$spos epos=$epos\n".$raw;
            continue;
        }

        $question = trim(substr($text,0,$spos));
        $sa_question = trim(substr($text,0,$spos)) . " [_____] " . trim(substr($text,$epos+1));
        $answer = trim(substr($text, $spos+1, $epos-$spos-1));

        /// echo("<pre>\n");echo("spos $spos epos $epos\n");echo("== Q ==\n".$question."\n");echo("== A ==\n".$answer."====\n");echo("</pre>\n");

        // Normalize whitespace in answer (collapse multiple spaces/newlines)
        $answer = preg_replace('/\s+/', ' ', $answer);
        
        // Determine question type
        $answer_trimmed = trim($answer);
        if ( strlen($answer_trimmed) < 1 ) {
            $type = 'essay_question';
        } else if ( preg_match('/^[TF]\s*#/', $answer_trimmed) || preg_match('/^[TF]\s*$/', $answer_trimmed) || 
                    preg_match('/^TRUE\s*$/i', $answer_trimmed) || preg_match('/^FALSE\s*$/i', $answer_trimmed) ) {
            $type = 'true_false_question';
        } else if ( strpos($answer_trimmed, '#') === 0 ) {
            $type = 'numerical_question';
        } else if ( strpos($answer_trimmed, "->") !== false ) {
            $type = 'matching_question';
        } else if ( strpos($answer_trimmed,"=") === 0 || strpos($answer_trimmed, "~") === 0 ) {
            $type = 'multiple_choice_question';  // Also will be multiple_answer and short_answer
        } else { 
            // Debug: log what we got to help diagnose
            $errors[] = "Could not determine question type. Answer starts with: [" . substr($answer_trimmed, 0, 20) . "] (length: " . strlen($answer_trimmed) . "). Raw: ".$raw;
            continue;
        }

        $quesno = $quesno + 1;
        $ansno = 0;
        $answers = array();
        $parsed_answer = false;
        $correct_answers = 0;
        $incorrect_answers = 0;
        
        // Parse True/False questions with feedback support
        if ( $type == 'true_false_question') {
            $parsed_answer = array();
            $answer_trimmed = trim($answer);
            $correct_value = strtoupper(substr($answer_trimmed, 0, 1));
            
            // Parse feedback format: {T#wrong feedback#correct feedback} or {T#feedback}
            if ( preg_match('/^[TF]\s*#(.+?)#(.+)$/', $answer_trimmed, $matches) ) {
                // Two feedbacks: wrong and correct
                $wrong_feedback = trim($matches[1]);
                $correct_feedback = trim($matches[2]);
                $parsed_answer[] = array(false, ($correct_value == 'T' ? 'False' : 'True'), $wrong_feedback, substr($quesno.':1:'.md5('F'),0,10));
                $parsed_answer[] = array(true, ($correct_value == 'T' ? 'True' : 'False'), $correct_feedback, substr($quesno.':2:'.md5('T'),0,10));
            } else if ( preg_match('/^[TF]\s*#(.+)$/', $answer_trimmed, $matches) ) {
                // Single feedback (only for correct answer)
                $feedback = trim($matches[1]);
                $parsed_answer[] = array(false, ($correct_value == 'T' ? 'False' : 'True'), '', substr($quesno.':1:'.md5('F'),0,10));
                $parsed_answer[] = array(true, ($correct_value == 'T' ? 'True' : 'False'), $feedback, substr($quesno.':2:'.md5('T'),0,10));
            } else {
                // No feedback
                $parsed_answer[] = array(false, ($correct_value == 'T' ? 'False' : 'True'), '', substr($quesno.':1:'.md5('F'),0,10));
                $parsed_answer[] = array(true, ($correct_value == 'T' ? 'True' : 'False'), '', substr($quesno.':2:'.md5('T'),0,10));
            }
            $correct_answers = 1;
            $incorrect_answers = 1;
        }
        // Parse Numerical questions
        else if ( $type == 'numerical_question') {
            $parsed_answer = array();
            $answer_trimmed = trim($answer);
            
            // Check if this is a multi-answer numerical question (starts with # and has = entries)
            if ( strpos($answer_trimmed, '#') === 0 && strpos($answer_trimmed, '=') !== false ) {
                // Multi-answer format: {# =1822:0 # Correct! =%50%1822:2 # Half credit}
                // Remove leading #
                $answer_trimmed = ltrim($answer_trimmed, '#');
                $answer_trimmed = trim($answer_trimmed);
                
                // Parse each =value entry
                $parts = preg_split('/(?<!\\\\)=/', $answer_trimmed);
                foreach ( $parts as $part ) {
                    $part = trim($part);
                    if ( empty($part) ) continue;
                    
                    $percentage_weight = null;
                    $value_text = '';
                    $feedback = '';
                    $in_feedback = false;
                    $in_percentage = false;
                    $percentage_text = '';
                    
                    // Parse percentage weight and value:tolerance
                    for ( $i = 0; $i < strlen($part); $i++ ) {
                        $ch = $part[$i];
                        $prevch = $i > 0 ? $part[$i-1] : ' ';
                        
                        // Check for percentage: %50%
                        if ( $prevch != "\\" && $ch == '%' && !$in_feedback && !$in_percentage ) {
                            $in_percentage = true;
                            continue;
                        }
                        
                        if ( $in_percentage ) {
                            if ( $prevch != "\\" && $ch == '%' ) {
                                $percentage_weight = floatval($percentage_text);
                                $percentage_text = '';
                                $in_percentage = false;
                                continue;
                            } else {
                                $percentage_text .= $ch;
                                continue;
                            }
                        }
                        
                        // Check for feedback separator
                        if ( $prevch != "\\" && $ch == '#' && !$in_feedback ) {
                            $in_feedback = true;
                            continue;
                        }
                        
                        if ( $in_feedback ) {
                            $feedback .= $ch;
                        } else {
                            $value_text .= $ch;
                        }
                    }
                    
                    $value_text = trim($value_text);
                    $feedback = trim($feedback);
                    
                    // Parse value:tolerance or range
                    if ( preg_match('/^([+-]?\d*\.?\d+)\s*\.\.\s*([+-]?\d*\.?\d+)$/', $value_text, $matches) ) {
                        $min = floatval($matches[1]);
                        $max = floatval($matches[2]);
                        $parsed_answer[] = array(true, "$min..$max", $feedback, substr($quesno.':'.(count($parsed_answer)+1).':'.md5("$min..$max"),0,10), $percentage_weight);
                        $correct_answers++;
                    } else if ( preg_match('/^([+-]?\d*\.?\d+)\s*:\s*([+-]?\d*\.?\d+)$/', $value_text, $matches) ) {
                        $value = floatval($matches[1]);
                        $tolerance = floatval($matches[2]);
                        $parsed_answer[] = array(true, "$value:$tolerance", $feedback, substr($quesno.':'.(count($parsed_answer)+1).':'.md5("$value:$tolerance"),0,10), $percentage_weight);
                        $correct_answers++;
                    } else if ( preg_match('/^([+-]?\d*\.?\d+)$/', $value_text, $matches) ) {
                        $value = floatval($matches[1]);
                        $parsed_answer[] = array(true, "$value", $feedback, substr($quesno.':'.(count($parsed_answer)+1).':'.md5("$value"),0,10), $percentage_weight);
                        $correct_answers++;
                    }
                }
                
                if ( count($parsed_answer) < 1 ) {
                    $errors[] = "Mal-formed numerical answer: ".$raw;
                    continue;
                }
            } else {
                // Single answer format
                // Remove leading #
                $answer_trimmed = ltrim($answer_trimmed, '#');
                $answer_trimmed = trim($answer_trimmed);
                
                // Extract feedback if present
                $feedback = '';
                if ( ($hash_pos = strpos($answer_trimmed, '#')) !== false ) {
                    $feedback = trim(substr($answer_trimmed, $hash_pos+1));
                    $answer_trimmed = trim(substr($answer_trimmed, 0, $hash_pos));
                }
                
                // Parse formats: #3:2 (value:tolerance) or #1..5 (range)
                if ( preg_match('/^([+-]?\d*\.?\d+)\s*\.\.\s*([+-]?\d*\.?\d+)$/', $answer_trimmed, $matches) ) {
                    // Range format: #1..5
                    $min = floatval($matches[1]);
                    $max = floatval($matches[2]);
                    $parsed_answer[] = array(true, "$min..$max", $feedback, substr($quesno.':1:'.md5("$min..$max"),0,10));
                    $correct_answers = 1;
                } else if ( preg_match('/^([+-]?\d*\.?\d+)\s*:\s*([+-]?\d*\.?\d+)$/', $answer_trimmed, $matches) ) {
                    // Value:tolerance format: #3:2
                    $value = floatval($matches[1]);
                    $tolerance = floatval($matches[2]);
                    $parsed_answer[] = array(true, "$value:$tolerance", $feedback, substr($quesno.':1:'.md5("$value:$tolerance"),0,10));
                    $correct_answers = 1;
                } else if ( preg_match('/^([+-]?\d*\.?\d+)$/', $answer_trimmed, $matches) ) {
                    // Simple value: #3
                    $value = floatval($matches[1]);
                    $parsed_answer[] = array(true, "$value", $feedback, substr($quesno.':1:'.md5("$value"),0,10));
                    $correct_answers = 1;
                } else {
                    $errors[] = "Mal-formed numerical answer: ".$raw;
                    continue;
                }
            }
        }
        // Parse Matching questions
        else if ( $type == 'matching_question') {
            $parsed_answer = array();
            // Format: {=option1 -> match1 =option2 -> match2}
            // Split by unescaped = or ~ followed by content ending with ->
            // Use a regex to find all matching pairs
            $matches_found = preg_match_all('/(?<!\\\\)[=~]([^=~]+?)\s*->\s*([^#=~]+?)(?:\s*#([^=~]*))?(?=\s*(?:[=~]|$))/', $answer, $matches, PREG_SET_ORDER);
            
            if ( $matches_found > 0 ) {
                foreach ( $matches as $match ) {
                    $option = trim($match[1]);
                    $match_text = trim($match[2]);
                    $feedback = isset($match[3]) ? trim($match[3]) : '';
                    
                    // Clean up escaped characters in option and match
                    $option = str_replace("\\\\","&#92;", $option);
                    $option = str_replace("\\","", $option);
                    $option = str_replace("&#92;", "\\", $option);
                    
                    $match_text = str_replace("\\\\","&#92;", $match_text);
                    $match_text = str_replace("\\","", $match_text);
                    $match_text = str_replace("&#92;", "\\", $match_text);
                    
                    $parsed_answer[] = array(true, "$option -> $match_text", $feedback, substr($quesno.':'.(count($parsed_answer)+1).':'.md5($option),0,10));
                    $correct_answers++;
                }
            }
            
            if ( count($parsed_answer) < 1 ) {
                $errors[] = "Mal-formed matching question: ".$raw;
                continue;
            }
        }
        // Parse Multiple Choice, Multiple Answer, and Short Answer questions
        else if ( $type == 'multiple_choice_question') {
            $parsed_answer = array();
            $correct = null;
            $answer_text = false;
            $feedback = false;
            $in_feedback = false;
            $percentage_weight = null;
            $in_percentage = false;
            $percentage_text = '';

            // Over scan by 1 so we can handle the last entry inside the loop
            // with a middle exit
            for($i=0;$i<strlen($answer)+1; $i++ ) {
                $prevch = $i > 0 ? $answer[$i-1] : ' ';
                $ch = $i < strlen($answer) ? $answer[$i] : -1;

                // echo("<pre>\n$i $ch\n</pre>\n");
                // Finish up the previous entry
                if ( strlen($answer_text ?? '') > 0 && ($ch == -1 || ($prevch != "\\" && ($ch == '=' || $ch == "~" )) ) ) {
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
                    $ansno = $ansno + 1;
                    // echo("<pre>BEFORE\n".($correct? "C" : "X")." $answer_text -- $feedback\n</pre>\n");

                    // Escape the answer text - This will go through htmlentities
                    // Note - \n is only in question text, not answers
                    $answer_text = str_replace("\\\\","&#92;", $answer_text);
                    $answer_text = str_replace("\\","", $answer_text);
                    $answer_text = str_replace("&#92;", "\\", $answer_text);
                    $code = substr($quesno.':'.$ansno.':'.md5(trim($answer_text)),0,10);

                    // echo("<pre>\n".($correct? "C" : "X")." $code $answer_text -- $feedback\n</pre>\n");

                    $parsed_answer[] = array($correct, trim($answer_text), trim($feedback), $code, $percentage_weight);
                    // Set up for the next one
                    $correct = null;
                    $answer_text = false;
                    $feedback = false;
                    $in_feedback = false;
                    $percentage_weight = null;
                    $in_percentage = false;
                    $percentage_text = '';
                }

                // We are done...
                if ( $ch == -1 ) break;

                // Check for percentage weight: %50%
                if ( $prevch != "\\" && $ch == '%' && !$in_feedback && !$in_percentage ) {
                    $in_percentage = true;
                    continue;
                }
                
                if ( $in_percentage ) {
                    if ( $prevch != "\\" && $ch == '%' ) {
                        // End of percentage
                        $percentage_weight = floatval($percentage_text);
                        $percentage_text = '';
                        $in_percentage = false;
                        continue;
                    } else {
                        $percentage_text .= $ch;
                        continue;
                    }
                }

                // right or wrong?
                // Handle =~ as = (fix for ChatGPT torture test - =~ is invalid but treat as =)
                if ( $prevch != "\\" && $ch == '=' ) {
                    $correct = true;
                    // If next char is ~ (and not escaped), skip it (treat =~ as =)
                    if ( $i < strlen($answer)-1 && $answer[$i+1] == '~' && ($i == 0 || $answer[$i-1] != "\\") ) {
                        $i++; // Skip the ~ character
                    }
                    continue;
                }
                if ( $prevch != "\\" && $ch == '~' ) {
                    $correct = false;
                    continue;
                }

                // feedback separator - only treat # as feedback if we have collected answer text
                // This prevents # in answers (like hex colors #ff0000) from being treated as feedback
                if ( $prevch != "\\" && $ch == '#' && $in_feedback === false ) {
                    // Only switch to feedback mode if we have some answer text already
                    // This handles cases like "=#ff0000" where # is part of the answer
                    if ( $answer_text !== false && strlen(trim($answer_text ?? '')) > 0 ) {
                        $in_feedback = true;
                        continue;
                    }
                    // If no answer text yet, treat # as part of the answer (e.g., hex colors)
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

        // var_dump_pre($parsed_answer, true);
        // echo "\nN: ",$name,"\nQ: ",$question,"\nA: ",$answer,"\nType:",$type,"\n";
        $qobj = new stdClass();
        $qobj->name = $name;
        if ( strpos($question,'[html]') === 0 ) {
            $question = de_escape(ltrim(substr($question,6)));
            $qobj->html = true;
        } else {
            $question = htmlentities($question);
            $question = str_replace("\\\\","&#92;", $question);
            $question = str_replace("\\n","<br>", $question);
            $question = de_escape($question);
        }
        $qobj->question = $question;
        $qobj->code = $quesno.':'.substr(md5($question),0,9);
        $qobj->answer = $answer;
        $qobj->type = $type;
        $qobj->parsed_answer = $parsed_answer;
        $qobj->correct_answers = $correct_answers;
        $questions[] = $qobj;
    }

    // var_dump_pre($questions, true);
}


function make_quiz($submit, $questions, $errors, $seed=-1) {

    $retval = array("status" => "failure", "errors" => $errors);
    if ( count($questions) < 1 ) {
        $retval["message"] = "No questions found";
        return $retval;
    }

    // Load the gift submission
    $submit = isset($_SESSION['gift_submit']) ? $_SESSION['gift_submit'] : array();
    $doscore = count($submit) > 0;

    $retval['status'] = 'success';
    $retval['scored'] = $doscore;
    $safe = array();
    $count = 1;
    $cumulative_score = 0;
    $cumulative_total = 0;
    $mt = new Mersenne_Twister($seed);
    // Filter out questions for the user-visible stuff
    foreach($questions as $question) {
        $nq = new stdClass();
        if ( ! isset($question->question) ) continue;
        if ( ! isset($question->type) ) continue;
        if ( ! isset($question->code) ) continue;
        $nq->question = $question->question;
        $nq->scored = $doscore;
        $q_code = $question->code;
        $nq->code = $q_code;
        $t = $question->type;
        $nq->type = $t;
        if ( isset($question->name) ) $nq->name = $question->name;

        if ( $t == 'short_answer_question' ) {
            if ( isset($submit[$q_code]) ) {
                $nq->value = $submit[$q_code];
            }
        }

        // Because Handlebars can't tell the difference between not set and false
        if ( $t == 'true_false_question' ) {
            if ( isset($submit[$q_code]) ) {
                $nq->value_true = $submit[$q_code] == 'T';
                $nq->value_false = $submit[$q_code] == 'F';
            }
        }

        // Score the questions that don't have answers
        $score = null;
        $correct = null;
        if ( $doscore && $t == 'short_answer_question' ) {
            if ( isset($submit[$q_code]) ) {
                $nq->value = $submit[$q_code];
                foreach($question->parsed_answer as $answer ) {
                    $ans = preg_replace('/\s+/', '', $answer[1]);
                    $sub = preg_replace('/\s+/', '', $submit[$q_code]);
                    if ( strcasecmp($sub, $ans) == 0 ) {
                        $score = 1;
                        $correct = true;
                        break;
                    }
                }
            }
            if ( $score === null ) {
                $score = 0;
                $correct = false;
            }
        } else if ( $doscore && $t == 'true_false_question' ) {
            if ( isset($submit[$q_code]) ) {
                // Find the correct answer from parsed_answer
                $correct_ans = 'T';
                foreach($question->parsed_answer as $answer ) {
                    if ( is_array($answer) && count($answer) >= 2 && $answer[0] === true ) {
                        $correct_ans = (stripos($answer[1], 'true') !== false) ? 'T' : 'F';
                        break;
                    }
                }
                $score = (strtoupper($submit[$q_code]) == $correct_ans) ? 1 : 0;
                $correct = ($score == 1);
            } else {
                $score = 0;
                $correct = false;
            }
        } else if ( $doscore && $t == 'numerical_question' ) {
            if ( isset($submit[$q_code]) ) {
                $user_value = floatval($submit[$q_code]);
                $score = 0;
                $correct = false;
                
                foreach($question->parsed_answer as $answer ) {
                    if ( !is_array($answer) || count($answer) < 2 ) continue;
                    $answer_text = $answer[1];
                    
                    // Check range format: 1..5
                    if ( preg_match('/^([+-]?\d*\.?\d+)\s*\.\.\s*([+-]?\d*\.?\d+)$/', $answer_text, $matches) ) {
                        $min = floatval($matches[1]);
                        $max = floatval($matches[2]);
                        if ( $user_value >= $min && $user_value <= $max ) {
                            $score = 1;
                            $correct = true;
                            break;
                        }
                    }
                    // Check value:tolerance format: 3:2
                    else if ( preg_match('/^([+-]?\d*\.?\d+)\s*:\s*([+-]?\d*\.?\d+)$/', $answer_text, $matches) ) {
                        $value = floatval($matches[1]);
                        $tolerance = floatval($matches[2]);
                        if ( abs($user_value - $value) <= $tolerance ) {
                            $score = 1;
                            $correct = true;
                            break;
                        }
                    }
                    // Check exact value
                    else if ( is_numeric($answer_text) ) {
                        $value = floatval($answer_text);
                        if ( abs($user_value - $value) < 0.0001 ) { // Small epsilon for floating point
                            $score = 1;
                            $correct = true;
                            break;
                        }
                    }
                }
                
                if ( $score === null ) {
                    $score = 0;
                    $correct = false;
                }
            } else {
                $score = 0;
                $correct = false;
            }
        } else if ( $t == 'numerical_question' ) {
            if ( isset($submit[$q_code]) ) {
                $nq->value = $submit[$q_code];
            }
        } else if ( $t == 'matching_question' && isset($question->parsed_answer) && is_array($question->parsed_answer) ) {
            $answers = array();
            $matches = array();
            if ( $doscore ) {
                $score = 0;
                $correct = false;
                $total_matches = count($question->parsed_answer);
                $correct_matches = 0;
            }
            
            foreach($question->parsed_answer as $answer ) {
                if ( !is_array($answer) || count($answer) < 4 ) continue;
                $match_text = $answer[1];
                $a_code = $answer[3];
                
                if ( preg_match('/^(.+?)\s*->\s*(.+)$/', $match_text, $match_parts) ) {
                    $option = trim($match_parts[1]);
                    $match = trim($match_parts[2]);
                    
                    $ans = new stdClass();
                    $ans->option = $option;
                    $ans->match = $match;
                    $ans->code = $a_code;
                    
                    if ( $doscore && isset($submit[$a_code]) ) {
                        $ans->selected = $submit[$a_code];
                        // Check if the selected match is correct
                        if ( strcasecmp(trim($submit[$a_code]), $match) == 0 ) {
                            $correct_matches++;
                            $ans->correct = true;
                        } else {
                            $ans->correct = false;
                        }
                    }
                    
                    $answers[] = $ans;
                }
            }
            
            if ( $doscore ) {
                $correct = ($correct_matches == $total_matches);
                $score = $correct ? 1 : 0;
            }
            
            $nq->answers = $mt->shuffle($answers);
        } 

        if ( $t == 'multiple_choice_question' &&
                isset($question->parsed_answer) && is_array($question->parsed_answer) ) {
            $answers = array();
            $value = false;
            if ( $doscore ) {
                $score = 0;
                $correct = false;
            }
            if ( isset($submit[$q_code]) ) {
                $value = $submit[$q_code];
                $nq->value = $submit[$q_code];
            }
            foreach($question->parsed_answer as $answer ) {
                $ans = new stdClass();
                if ( ! is_array($answer) ) continue;
                if ( count($answer) < 4 ) continue;

                $expected = $answer[0];  // An actual boolean
                $ans->text = $answer[1];
                $a_code = $answer[3];
                $ans->code = $a_code;
                if ( isset($answer[4]) ) {
                    $ans->percentage_weight = $answer[4];
                }
                if ( $value == $a_code ) {
                    $ans->checked = true;
                    if ( $doscore && $expected ) {
                        $correct = true;
                        $score = 1;
                    }
                }
                $answers[] = $ans;
            }
            // $answers = $mt->shuffle($answers);
            $nq->answers = $mt->shuffle($answers);
        } 

        if ( $t == 'multiple_answers_question'  &&
                isset($question->parsed_answer) && is_array($question->parsed_answer) ) {
            $answers = array();
            $got = 0;
            $need = 0;
            $oneanswer = false;
            foreach($question->parsed_answer as $answer ) {
                $ans = new stdClass();
                if ( ! is_array($answer) ) continue;
                if ( count($answer) < 4 ) continue;
                $ans->text = $answer[1];
                $a_code = $answer[3];
                $expected = $answer[0];  // An actual boolean
                if ( isset($answer[4]) ) {
                    $ans->percentage_weight = $answer[4];
                }
                $oneanswer = $oneanswer || isset($submit[$a_code]);
                $ans->checked = isset($submit[$a_code]);

                $actual = false;
                if (isset($submit[$a_code])) {  // If the user checked the box for this answer...
                  if ($expected){               // And the answer was supposed to be checked
                    $actual = true;             // Then the user should get a point towards the score
                  }
                } else {                        // If the user did NOT check this box...
                  if (!$expected){              // And the answer was not supposed to be checked
                    $actual = true;             // Then the user should get a point
                  }
                }

                if ( $actual ) $got++;          // $actual is true if the user gave the correct option
                $need++;
                $ans->code = $a_code;
                if ( $doscore ) {
                    $ans->correct = $actual == $expected;
                }
                $answers[] = $ans;
            }
            if ( $doscore ) {
                $correct = $got == $need;
                if ( $correct || $oneanswer ) {
                    $score = $correct + 0;
                } else {
                    $score = 0;
                }
            }
            $nq->answers = $mt->shuffle($answers);
        }

        if ( $correct !== null ) $nq->correct = $correct;
        if ( $score !== null ) {
            $nq->score = $score;
            $cumulative_score += $score;
            $cumulative_total += 1;
            // $nq->cumulative_total = $cumulative_total;
            // $nq->cumulative_score = $cumulative_score;
        }
        $nq->count = $count;
        $count++;
        $safe[] = $nq;
    }

    $retval["questions"] = $safe;
    $retval["submit"] = $submit;
    if ( $doscore ) {
        if ( $cumulative_total == 0 ) {
            $retval["score"] = 0;
        } else {
            $retval["score"] = $cumulative_score / $cumulative_total;
        }
    }

    return $retval;
}

// Some POST sanity checking...
function check_gift($gift) {
    $questions = array();
    $errors = array();
    parse_gift($gift, $questions, $errors);

    if ( count($questions) < 1 ) {
        $_SESSION['error'] = "No valid questions found in input data";
        return false;
    }

    if ( count($errors) > 0 ) {
        $msg = "Errors in GIFT data: ";
        $i = 1;
        foreach ( $errors as $error ) {
            $msg .= " ($i) ".$error;
        }
        $_SESSION['error'] = $msg;
        return false;
    }
    return true;
}

function de_escape($str) {
    $retval = "";
    for ($i = 0; $i < strlen($str); $i++){
        $ch = $str[$i];
        if ( $ch == '\\' && $i < strlen($str)-1) {
            $ch = $str[++$i];
        }
        $retval .= $ch;
    }
    return $retval;
}


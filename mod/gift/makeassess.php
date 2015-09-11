<?php

date_default_timezone_set('UTC');

$text = 
"// true/false
::Q1:: 1+1=2 {T}

// multiple choice with specified feedback for right and wrong answers
::Q2:: What's between orange and green in the spectrum? 
{ =yellow # right; good! ~red # wrong, it's yellow ~blue # wrong, it's yellow }

// fill-in-the-blank
::Q3:: Two plus {=two =2} equals four.

// matching
::Q4:: Which animal eats which food? { =cat -> cat food =dog -> dog food }

// math range question
::Q5:: What is a number from 1 to 5? {#3:2}

// math range specified with interval end points
::Q6:: What is a number from 1 to 5? {#1..5}
// translated on import to the same as Q5, but unavailable from Moodle question interface

// multiple numeric answers with partial credit and feedback
::Q7:: When was Ulysses S. Grant born? {#
         =1822:0      # Correct! Full credit.
         =%50%1822:2  # He was born in 1822. Half credit for being close.
}

// essay
::Q8:: How are you? {}
";

$errors = [];
$raw_questions = array();
$question = "";
$lines = explode("\n", $text);
foreach ( $lines as $line ) {
    $line = rtrim($line);
    if ( strpos($line, "//") === 0 ) continue;
    if ($line == "" ) {
        if ( strlen($question) > 0 ) {
            $raw_questions[] = $question;
            $question = "";
        }
        continue;
    }
    if ( strlen($question) > 0 ) $question .= " ";
    $question .= trim($line);
}

$questions = array();
foreach ( $raw_questions as $raw ) {
    $pieces = explode('::', $raw);
    if ( count($pieces) != 3 ) {
        $errors[] = "Mal-formed question: ".$raw;
        continue;
    }
    // print_r($pieces);
    $name = trim($pieces[1]);
    $text = trim($pieces[2]);
    $spos = strpos($text,'{');
    $epos = strpos($text,'}', $spos);
    // echo $spos, " ", $epos, "\n";
    if ( $spos < 1 || $epos < 1 ) {
        $errors[] = "Could not find answer: ".$raw;
        continue;
    }
    $answer = trim(substr($text,$spos+1, $epos-$spos-1));
    if ( $epos == strlen($text)-1 ) {
        $question = trim(substr($text,0,$spos-1));
        $type = 'unknown';
    } else {
        $question = trim(substr($text,0,$spos-1)) . " [_____] " . trim(substr($text,$epos+1));
        $type = 'short_answer_question';
        
        $errors[] = "Short answer questions not yet supported: ".$raw;
        continue;
    }

    if ( $type == 'short_answer_question' ) {
        // We are good...
    } else if ( strpos($answer,"T") === 0 || strpos($answer, "F") === 0 ) {
        $type = 'true_false_question';
    } else if ( strlen($answer) < 1 ) {
        $type = 'essay_question';
    } else if ( strpos($answer, '#') === 0 ) {
        $type = 'numerical_question';
        $errors[] = "Numerical questions not yet supported: ".$raw;
        continue;
    }  else if ( strpos($answer,"=") === 0 || strpos($answer, "~") === 0 ) {
        $type = 'multiple_choice_question';
    } else { 
        $errors[] = "Could not determine question type: ".$raw;
        continue;
    }
    $answers = [];
    $parsed_answer = false;
    $correct_answers = 0;
    if ( $type == 'short_answer_question' || $type == 'multiple_choice_question') {
        $parsed_answer = [];
        $correct = null;
        $answer_text = false;
        $feedback = false;
        $in_feedback = false;
        for($i=0;$i<strlen($answer)+1; $i++ ) {
            $ch = $i < strlen($answer) ? $answer[$i] : -1;
            // echo $i," ", $ch, "\n";
            // Finish up the previous entry
            if ( ( $ch == -1 || $ch == '=' || $ch == "~" ) && strlen($answer_text) > 0 ) {
                if ( $correct === null || $answer_text === false ) {
                    $errors[] = "Mal-formed answer sequence: ".$raw;
                    $parsed_answer = [];
                    break;
                }
                if ( $correct ) $correct_answers++;
                $parsed_answer[] = array($correct, trim($answer_text), trim($feedback));
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
        }
        if ( $correct_answers < 1 ) {
            $errors[] = "No correct answers found: ".$raw;
            continue;
        }
        if ( $correct_answers > 1 ) {
            $type = 'multiple_answers_question';
            $errors[] = "No support for multiple_answers_question type: ".$raw;
            continue;
        }
    }
    // echo "\nN: ",$name,"\nQ: ",$question,"\nA: ",$answer,"\nType:",$type,"\n";
    $qobj = new stdClass();
    $qobj->name = $name;
    $qobj->question = $question;
    $qobj->answer = $answer;
    $qobj->type = $type;
    $qobj->parsed_answer = $parsed_answer;
    $qobj->correct_answers = $correct_answers;
    $questions[] = $qobj;
}


if ( count($errors) == 0 ) {
    print "Conversion success\n";
} else {
    print "Conversion errors:\n";
    print_r($errors);
}

print_r($questions);
$QTI = simplexml_load_file('xml/assessment.xml');
// var_dump($XML);
// echo $XML->asXML();

// echo $QTI->assessment->asXML();
// echo $QTI->assessment['ident'], "\n";
// echo $QTI->assessment->section->asXML();

/*
foreach ($QTI->assessment->section->item as $item) {
    echo "-----------------\n";
    echo $item->asXML(), "\n";
}
*/

$uuid = uniqid();
$offset=100;
unset($QTI->assessment);
$QTI->addChild("assessment");
$QTI->assessment->addAttribute("ident", $uuid);
$QTI->assessment->addAttribute("title", "Gift2QTI Convertor");
$section = $QTI->assessment->addChild('section');
$section->addAttribute("ident", "root_section");

echo "\n=============================================================\n\n";
foreach($questions as $question) {
    $item = $section->addChild("item");
    $item->addAttribute("title",$question->name);
    $item->addAttribute("ident",$uuid.'_'.$offset++);
    $itemmetadata = $item->addChild("itemmetadata");
    $qtimetadata = $itemmetadata->addChild("qtimetadata");
    $qtimetadatafield = $qtimetadata->addChild("qtimetadatafield");
    $qtimetadatafield->addChild("fieldlabel", "question_type");
    $qtimetadatafield->addChild("fieldentry", $question->type);
    $qtimetadatafield = $qtimetadata->addChild("qtimetadatafield");
    $qtimetadatafield->addChild("fieldlabel", "points_possible");
    $qtimetadatafield->addChild("fieldentry", "1");
    $qtimetadatafield = $qtimetadata->addChild("qtimetadatafield");
    $qtimetadatafield->addChild("fieldlabel", "assessment_question_identifierref");
    $qtimetadatafield->addChild("fieldentry", $uuid.'_'.$offset++);

    $presentation = $item->addChild("presentation");
    $material = $presentation->addChild("material");
    $mattext = $material->addChild("mattext", $question->question);
    $mattext->addAttribute("texttype", "text/plain");

    if ( $question->type == 'true_false_question' ) {

        $response_lid = $presentation->addChild('response_lid');
        $response_lid->addAttribute("ident", "response1");
        $response_lid->addAttribute("rcardinality", "Single");
        $render_choice = $response_lid->addChild('render_choice');
        $trueval = $offset++;
        $response_label = $render_choice->addChild('response_label');
        $response_label->addAttribute('ident', $trueval);
        $material = $response_label->addChild("material");
        $mattext = $material->addChild("mattext", "True");
        $mattext->addAttribute("texttype", "text/plain");
        $falseval = $offset++;
        $response_label = $render_choice->addChild('response_label');
        $response_label->addAttribute('ident', $falseval);
        $material = $response_label->addChild("material");
        $mattext = $material->addChild("mattext", "False");
        $mattext->addAttribute("texttype", "text/plain");

        $resprocessing = $item->addChild("resprocessing");
        $outcomes = $resprocessing->addChild("outcomes");
        $decvar = $outcomes->addChild("decvar");
        $decvar->addAttribute("maxvalue", "100");
        $decvar->addAttribute("minvalue", "0");
        $decvar->addAttribute("varname", "SCORE");
        $decvar->addAttribute("vartype", "Decimal");
        $respcondition = $resprocessing->addChild("respcondition");
        $respcondition->addAttribute("continue", "No");
        $conditionvar = $respcondition->addChild("conditionvar");
        $ans = strtolower($question->answer);
        $val = strpos($ans,"t") === 0 ? $trueval : $falseval;
        $varequal = $conditionvar->addChild("varequal",$val);
        $varequal->addAttribute("respident", "response1");
        $setvar = $respcondition->addChild("setvar", 100);
        $setvar->addAttribute("action", "Set");
        $setvar->addAttribute("varname", "SCORE");
    }

    if ( $question->type == 'multiple_choice_question' ) {

        $response_lid = $presentation->addChild('response_lid');
        $response_lid->addAttribute("ident", "response1");
        $response_lid->addAttribute("rcardinality", "Single");
        $render_choice = $response_lid->addChild('render_choice');

        $correct = null;
        foreach ( $question->parsed_answer as $parsed_answer )  {
            $val = $offset++;
            if ( $parsed_answer[0] === true ) $correct = $val;
            $response_label = $render_choice->addChild('response_label');
            $response_label->addAttribute('ident', $val);
            $material = $response_label->addChild("material");
            $mattext = $material->addChild("mattext", $parsed_answer[1]);
            $mattext->addAttribute("texttype", "text/plain");
        }

        $resprocessing = $item->addChild("resprocessing");
        $outcomes = $resprocessing->addChild("outcomes");
        $decvar = $outcomes->addChild("decvar");
        $decvar->addAttribute("maxvalue", "100");
        $decvar->addAttribute("minvalue", "0");
        $decvar->addAttribute("varname", "SCORE");
        $decvar->addAttribute("vartype", "Decimal");
        $respcondition = $resprocessing->addChild("respcondition");
        $respcondition->addAttribute("continue", "No");
        $conditionvar = $respcondition->addChild("conditionvar");
        $varequal = $conditionvar->addChild("varequal",$correct);
        $varequal->addAttribute("respident", "response1");
        $setvar = $respcondition->addChild("setvar", 100);
        $setvar->addAttribute("action", "Set");
        $setvar->addAttribute("varname", "SCORE");
    }

    if ( $question->type == 'essay_question' ) {

        $response_str = $presentation->addChild('response_str');
        $response_str->addAttribute("ident", "response1");
        $response_str->addAttribute("rcardinality", "Single");
        $render_fib = $response_str->addChild('render_fib');
        $response_label = $render_fib->addChild('response_label');
        $response_label->addAttribute('ident', $offset++);
        $response_label->addAttribute('rshuffle', "No");

        $resprocessing = $item->addChild("resprocessing");
        $outcomes = $resprocessing->addChild("outcomes");
        $decvar = $outcomes->addChild("decvar");
        $decvar->addAttribute("maxvalue", "100");
        $decvar->addAttribute("minvalue", "0");
        $decvar->addAttribute("varname", "SCORE");
        $decvar->addAttribute("vartype", "Decimal");
        $respcondition = $resprocessing->addChild("respcondition");
        $respcondition->addAttribute("continue", "No");
        $conditionvar = $respcondition->addChild("conditionvar");
        $other = $conditionvar->addChild("other");

    }

}

    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($QTI->asXML());
    echo $dom->saveXML();
/*
    echo "\nValidating (may take a few seconds)...\n";
    if ( $dom->schemaValidate('xml/ims_qtiasiv1p2p1.xsd') ) {
        echo "\n===== Valid =======\n";
    } else {
        echo "\n===== Not Valid =======\n";
    }
    $dom->save("quiz.xml");
*/

unlink("quiz.zip");
echo "Making a ZIP\n";
$zip = new ZipArchive();
if ($zip->open("quiz.zip", ZipArchive::CREATE)!==TRUE) {
    exit("cannot open <quiz.zip>\n");
}

// Stuff we substitute...
$quiz_id = 'i'.$uuid;
$today = date('Y-m-d');
$ref_id = 'r'.uniqid();
$manifest_id = 'm'.uniqid();
$title = "Title goes here";
$desc = "Description goes here";
$source = array("__DATE__", "__QUIZ_ID__","__REF_ID__", "__TITLE__","__DESCRIPTION__", "__MANIFEST_ID__");
$dest = array($today, $quiz_id, $ref_id, $title, $desc, $manifest_id);

// Add the ims Manifest
$manifest = str_replace($source, $dest, file_get_contents('xml/imsmanifest.xml'));
$zip->addFromString('imsmanifest.xml',$manifest);

// Add the Assessment Metadata
$meta = str_replace($source, $dest, file_get_contents('xml/assessment_meta.xml'));
$zip->addFromString($quiz_id.'/assessment_meta.xml',$meta);

// Add the quiz
$zip->addFromString($quiz_id.'/'.$quiz_id.'.xml',$dom->saveXML());

// $zip->addFile($thisdir . "/too.php","/testfromfile.php");
echo "numfiles: " . $zip->numFiles . "\n";
echo "status:" . $zip->status . "\n";

$zip->close();


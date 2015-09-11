<?php

// When turning plain text into HTML to go into XML we need
// to double encode the less-thans and greater-thans
// Since the PHP XML serialization won't double-encode 
// HTMLentities - we need to do it here
function xml_double_encode_string($str) {
    $from = array('&', '\'', '"', '<', '>');
    $to = array('&amp;', '&apos;', '&quot;', '&amp;lt;', '&amp;gt;');
    $to = array('&amp;', '&apos;', '&quot;', '&lt;', '&gt;');
    return(str_replace($from, $to, $str));
}

// Since the pattern of 
//    $mattext = $material->addChild("mattext");
//    $material->mattext = $questext;
// Will escape everything < > " " except for an & we pre-escape the 
// ampersands
function plain_to_html_in_xml($questext) {
    return str_replace("&","&amp;",$questext);
}

/**
 * From http://stackoverflow.com/questions/3957360/generating-xml-document-in-php-escape-characters
 * @param $arr1 the single string that shall be masked
 * @return the resulting string with the masked characters
 */
function html_in_xml_replace_char($arr1)
{
    if (strpos ($arr1,'&')!== FALSE) { //test if the character appears 
        $arr1=preg_replace('/&/','&amp;', $arr1); // do this first
    }

    // just encode the 
    if (strpos ($arr1,'>')!== FALSE) {
        $arr1=preg_replace('/>/','&gt;', $arr1);
    }
    if (strpos ($arr1,'<')!== FALSE) {
        $arr1=preg_replace('/</','&lt;', $arr1);
    }

    if (strpos ($arr1,'"')!== FALSE) {
        $arr1=preg_replace('/"/','&quot;', $arr1);
    }

    if (strpos ($arr1,'\'')!== FALSE) {
        $arr1=preg_replace('/\'/','&apos;', $arr1);
    }

    return $arr1;
}

// Somehow simplexml_load_file() seems to get confused now and then..
$data = file_get_contents(getcwd().'/xml/assessment.xml');
$QTI = simplexml_load_string($data);
$uuid = uniqid();
$offset=100;
unset($QTI->assessment);
$QTI->addChild("assessment");
$QTI->assessment->addAttribute("ident", $uuid);
$title = isset($_SESSION['title']) ? $_SESSION['title'] : 'Converted by the Gift2QTI Converter';
$QTI->assessment->addAttribute("title", $title);
$section = $QTI->assessment->addChild('section');
$section->addAttribute("ident", "root_section");

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
    $questext = $question->question;
    if ( strpos($questext,"[html]") === 0 ) {
        // Here we are depending on 
        $questext = ltrim(substr($questext,6));
        // $questext = xml_double_encode_string($questext);
    } else {
        $questext = ltrim($questext);
        $questext = xml_double_encode_string($questext);
        //$questext = plain_to_html_in_xml($questext);
    }
    // $mattext = $material->addChild("mattext", $questext);
    $mattext = $material->addChild("mattext");
    $material->mattext = $questext;
    $mattext->addAttribute("texttype", 'text/html');

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
        continue; // XXX
    }

    if ( $question->type == 'multiple_choice_question' || 
        $question->type == 'multiple_answers_question' ||
        $question->type == 'short_answer_question' ) {

        $response_lid = $presentation->addChild('response_lid');
        $response_lid->addAttribute("ident", "response1");
        $render_choice = $response_lid->addChild('render_choice');

        $correct = array();;
        $incorrect = array();;
        foreach ( $question->parsed_answer as $parsed_answer )  {
            $val = $offset++;
            if ( $parsed_answer[0] === true ) {
                $correct[] = $val;
            } else {
                $incorrect[] = $val;
            }
            $response_label = $render_choice->addChild('response_label');
            $response_label->addAttribute('ident', $val);
            $material = $response_label->addChild("material");
            $mattext = $material->addChild("mattext");
            $material->mattext = $parsed_answer[1];
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
        if ( $question->type == 'multiple_choice_question' ) {
            $response_lid->addAttribute("rcardinality", "Single");
            $varequal = $conditionvar->addChild("varequal",$correct[0]);
            $varequal->addAttribute("respident", "response1");
        } else if ( $question->type == 'short_answer_question' ) {
            $response_lid->addAttribute("rcardinality", "Single");
            foreach( $correct as $cor ) {
                $varequal = $conditionvar->addChild("varequal",$cor);
                $varequal->addAttribute("respident", "response1");
            }
        } else { // 'multiple_answers_question' 
            $response_lid->addAttribute("rcardinality", "Multiple");
            $and = $conditionvar->addChild("and");
            foreach( $correct as $cor ) {
                $varequal = $and->addChild("varequal",$cor);
                $varequal->addAttribute("respident", "response1");
            }
            foreach( $incorrect as $incor ) {
                $not = $and->addChild("not");
                $varequal = $not->addChild("varequal",$incor);
                $varequal->addAttribute("respident", "response1");
            }
        }
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
        continue; // XXX
    }

}


$DOM = new DOMDocument('1.0');
$DOM->preserveWhiteSpace = false;
$DOM->formatOutput = true;
$DOM->loadXML($QTI->asXML());
echo "\nValidating (may take a few seconds)...\n";
libxml_use_internal_errors(true);
if ( isset($_SESSION['novalidate']) ) {
    echo "\nContent validation bypassed...\n";
} else if ( ! $DOM->schemaValidate('xml/ims_qtiasiv1p2p1.xsd') ) {
    echo "\nWarning: Quiz XML Not Valid\n";
    $errors = libxml_get_errors();
    foreach ($errors as $error) {
        echo "Error:", libxml_display_error($error), "\n";
    }
    libxml_clear_errors();
} else { 
    echo "\nQuiz XML validated\n";
}


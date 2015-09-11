<?php

/* The Mneme exports are pretty rough to import so we kind 
   of zap some characters.  Some hand-editing would be necessary
 */

function trimBlankLines($val) {
    $lines = explode("\n", $val);
    $newtext = "";
    foreach($lines as $line) {
        if ( strlen(trim($line)) < 1 ) continue;
        if ( strlen($newtext) > 0 ) $newtext .= "\n";
        $newtext .= rtrim($line);
    }
    return $newtext;
}

function hammerNonAscii($val) {
    $ret = "";
    for ($i=0; $i<strlen($val); $i++) {
        $ch = $val[$i];
        $o = ord($ch);
        if ( $ch == "\n" ) {
            $ret .= $ch;
        } else if ( $o < 32 || $o > 126 ) {
            continue;
        } else {
            $ret .= $ch;
        }
    }
    return $ret;
}
function hammerTags($val) {
    $val = preg_replace('/<[a-zA-Z]*?>/','', $val);
    $val = preg_replace('/<[a-zA-Z]* .*?>/','', $val);
    $val = preg_replace('/<\/[a-zA-Z]*?>/','', $val);
    return $val;
}

function escapeGIFTQ($val) {
    $val = str_replace(
        array('{', '}', '…','”', '“'),
        array('\{', '\}', '...', '"', '"'),
        $val);
    $val = hammerNonAscii($val);
    return $val;
}

// "~=#{}:->%"
function escapeGIFTA($val) {
    $val = hammerTags($val);
    $val = trimBlankLines($val);
    $val = html_entity_decode($val);
    $val = trim($val);
    $val = str_replace(
        array('”', '“', '…', '{', '}', '~', '=', '#', ':', '-', '>', '%'),
        array('"', '"', '...', '\{', '\}', '\~', '\=', '\#', '\:', '\-', '\>', '\%'),
        $val);
    $val = hammerNonAscii($val);
    return $val;
}

// http://stackoverflow.com/questions/12954771/how-to-parse-an-xml-ignoring-errors-with-simplexml
$config = array(
           'indent'     => true,
           'input-xml'  => true,
           'output-xml' => true,
           'wrap'       => false);

$files = scandir('.');
$qnumber = 0;
foreach ($files as $file ) {
    if ( strpos($file,"question") === false ) continue;
    if ( strpos($file,".xml") === false ) continue;
    $qnumber++;
    // if ( $qnumber <= 5 ) continue;
    // echo("+++++++++++++\n$$file $qnumber\n");
    // Tidy
    $tidy = new tidy;
    $tidy->parseFile($file, $config);
    $tidy->cleanRepair();
    // Output
    // echo tidy_get_output($tidy);
    $DOM = simplexml_load_string(tidy_get_output($tidy));
    // echo $DOM->asXML();
    $itemBody = $DOM->itemBody;
    $div = $itemBody->div;
    $question_text = $div->asXML();
    // Get rid of some crap..
    $question_text = str_replace(
        array('<p/>', '<div/>'), 
        array('',''),
        $question_text);
    // Uh lets say good by to <spans...>
    $question_text = preg_replace('/<span.*?>/','', $question_text);
    $question_text = preg_replace('/<\/span>/','', $question_text);

    $question_text = trimBlankLines($question_text);

    // Pull out the mneme way of indicating short answer.
    $qt = preg_replace('/<textEntryInteraction .*\>/','',$question_text);
    $short_answer = false;
    if ( $qt != $question_text ) {
        $short_answer = true;
        $question_text = trimBlankLines($qt);
    }

    echo("\n::Q$qnumber::[html] ".escapeGIFTQ(trim($question_text))."\n");

    $responseDeclaration = $DOM->responseDeclaration;
    // echo $choiceInteraction->asXML();
    $correctResponse = $responseDeclaration->correctResponse;
    $values = array();
    foreach($correctResponse->children() as $child ) {
        $tag = $child->getName();
        if ( $tag != 'value') {
            echo $correctResponse->asXML();
            die("Unknown tag $tag in $file");
        }
        $values[] = $child."";
    }

    // Check for fill in the blank
    if ( !isset($itemBody->choiceInteraction) ) {
        if ( count($values) != 1 ) {
            echo $correctResponse->asXML();
            die('Expecting one value in choiceInteraction for fill-in-the-blank');
        }
        $answers = explode('|', $values[0]);
        print "{ ";
        foreach($answers as $answer) {
           print " =".escapeGIFTA(trim($answer));
        }
        print " }\n";
        continue;
    }

    // Check the options...
    $choiceInteraction = $itemBody->choiceInteraction;

    // Check for true_false
    $found = 0;
    $answer = null;
    foreach($choiceInteraction->children() as $child ) {
        $identifier = trim($child['identifier']);
        $tag = $child->getName();
        if ( $tag != 'simpleChoice') {
            echo $choiceInteraction->asXML();
            die("Unknown tag $tag in $file");
        }
        $txt = strtolower($child."");
        if ( $txt == "true" ) {
            $found++;
            if ( in_array($identifier, $values) ) $answer = "T";
        } else if ( $txt == "false" ) {
            $found++;
            if ( in_array($identifier, $values) ) $answer = "F";
        } else {
            break;
        }
    }

    if ( $found == 2 && is_string($answer)) { 
        echo " {".$answer. "}\n";
        continue;
    }

    echo "{\n";
    foreach($choiceInteraction->children() as $child ) {
    
        $identifier = trim($child['identifier']);
        // if ( $identifier == $value ) {
        if ( in_array($identifier, $values) ) {
            echo  "=";
        } else {
            echo "~";
        }
        if ( count($child->children()) == 0 ) {
            $txt = $child."";
            echo escapeGIFTA($txt)."\n";
        } else {
            $txt = trim($child->p."");
            if ( strlen($txt) < 1 ) {
                echo escapeGIFTA($child->asXML())."\n";
            } else {
                echo escapeGIFTA($txt)."\n";
            }
        }
        // echo $child->p."\n";
    }
    echo "}\n";

    // break;
}

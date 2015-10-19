<?php
require_once("../../../config.php");
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once("../names.php");
require_once("../data_util.php");

use \Tsugi\Core\LTIX;
use \Tsugi\Util\Mersenne_Twister;

$local_path = route_get_local_path(__DIR__);
$pos = strpos($local_path,'?');
if ( $pos > 0 ) $local_path = substr($local_path,0,$pos);

if ( $local_path == "assn_11_actual.txt" || $local_path == "assn_11_sample.txt" ) {
    $LTI = LTIX::requireData();
    $code = $USER->id+$LINK->id+$CONTEXT->id;
    if ( $local_path == "assn_11_sample.txt" ) {
        echo("This file contains the sample data\n\n");
        $code = 42;
    } else {
        echo("This file contains the actual data for your assignment - good luck!\n\n");
    }

    $handle = fopen("../static/intro.txt", "r");
    if ($handle) {
        $count = 0;
        $MT = new Mersenne_Twister($code);
        header('Content-Type: text/plain');
        // header('Content-Disposition: attachment; filename='.$local_path.';');
        while (($line = fgets($handle)) !== false) {
            $count++;
            $choose = ($count < 400 ) ? $MT->getNext(0,9) : 1 ;
            if ( $choose != 0 ) {
                echo($line);
                continue;
            }
            $howmany = $MT->getNext(1,3);
            if ( $howmany == 1 ) {
                echo($MT->getNext(1,10000).' '.$line);
            } else if ( $howmany == 2 ) {
                echo($MT->getNext(1,10000).' '.rtrim($line).' '.$MT->getNext(1,10000)."\n");
            } else if ( $howmany == 3 ) {
                $words = explode(' ',$line);
                if ( count($words) > 3 ) {
                    for($i=0; $i<count($words);$i++) {
                        echo($words[$i].' ');
                        if ( $i < 3 ) {
                            echo($MT->getNext(1,10000).' ');
                        }
                    }
                } else {
                    echo($MT->getNext(1,10000).' '.$MT->getNext(1,10000).' '.$MT->getNext(1,10000)."\n");
                }
            }

            // process the line read.
        }
        echo("42\n");
        echo("The end\n");

        fclose($handle);
    } else {
        echo("<p>File not found: intro.txt</p>\n");
        return;
    } 
    return;

} else if ( strpos($local_path, "comments") === 0 ) {
    header('Content-Type: text/html');
?><html>
<head>
<title>Welcome to Assignment 12B for www.pythonlearn.com</title>
</head>
<body>
<?php
    $code = 42;
    $pieces = preg_split('/[_.]/',$local_path);
    if ( count($pieces) == 3 && $pieces[1]+0 > 0 ) {
        $code = $pieces[1]+0;
    }
    
    if ( $code == 42 ) {
        echo("<h1>This file contains the sample data</h1>\n\n");
    } else {
        echo("<h1>This file contains the actual data for your assignment - good luck!</h1>\n\n");
    }
    $MT = new Mersenne_Twister($code);
?>
<table border="2">
<tr>
<td>Name</td><td>Comments</td>
</tr>
<?php
    $new = getShuffledNames($code);
    $data = array();
    for($i=0; $i<50 && $i < count($new); $i++) {
        $amount = $MT->getNext(0,100);
        $data[$new[$i]] = $amount;
    }
    arsort($data);
    foreach( $data as $k=>$v ) {
        echo('<tr><td>'.$k.'</td><td><span class="comments">'.$v.'</span></td></tr>'."\n");
    }
    echo("</table>\n</body>\n</html>\n");
    return;

} else if ( strpos($local_path, "friends_of_") === 0 ) {
    header('Content-Type: text/html');
    $code = 0;
    $name = $names[0];
    $pieces = preg_split('/[_.]/',$local_path);
    if ( count($pieces) == 4 ) {
        $where = array_search($pieces[2], $names);
        if ( $where !== false ) {
            $name = $names[$where];
            $code = $where;
        }
    }
    
?><html>
<head>
<title>Friend Of <?= htmlentities($name) ?></title>
</head>
<body>
<h1>A list of friends of <?= htmlentities($name) ?></h1>
<ul>
<?php
    $curr_url = getCurrentFileUrl(__FILE__);
    $new = getShuffledNames($code);
    for($i = 0; $i < count($new) && $i < 100; $i++) {
        if ( $new[$i] == $name ) continue;
        $new_url = str_replace($curr_url, "index.php", "friends_of_".$new[$i].".html");
        echo('<li><a href="'.$new_url.'">'.$new[$i]."</a></li>\n");
    }
    echo("</ul>\n</body>\n</html>\n");
    
    return;
}
?>
<p>File not found <?= htmlentities($local_path) ?></p>

<?php


require_once("../config.php");

require_once("parse.php");

use \Tsugi\Util\U;

echo("<pre>\n");

$q1 = <<< EOF
::Q1::[html] Testing 123...
Can I print a \~ tilde sign in a question
Can I print a \{ open curly brace in a question
Can I print a \} close curly brace in a question
Can I print a \# hash tag in a question
Can I print a [ open bracket in a question
Can I print a ] close bracket in a question
Can I print a * asterisk in a question
Can I print a < less than in a question
Can I print a ; semi colon in a question
Can I print a &amp; ampersand in an HTML question using its htmlentity
Can I print a &pound; pound sign in an HTML question using its htmlentity
</pre>
{
=Can I print an \= equals sign in an answer
~Can I print a \~ tilde sign in an answer
~Can I print a \{ open curly brace in an answer
~Can I print a \} close curly brace in an answer
~Can I print a \# hash tag in an answer
~Can I print a [ open bracket in an answer
~Can I print a ] close bracket in an answer
~Can I print a ; semi colon in an answer
~Can I print a * asterisk in an answer
~Can I print a & ampersand in an answer
~Can I print a < less than in an answer
}
EOF
;

$retval = parse_gift($q1, $questions, $errors);

echo($q1);
print_r($errors);
print_r($questions);


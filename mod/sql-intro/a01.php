<?php

require_once "names.php";

srand($USER->id*$LINK->id*$CONTEXT->id);
$my_names = array();
$my_age = array();
$howmany = rand(4,6);
for($i=0; $i < $howmany; $i ++ ) {
    $name = $names[rand(0,count($names))];
    $age = rand(13,40);
    $sha = sha1($name.$age);
    $database[] = array($sha,$name,$age);
}
$sorted = $database;
sort($sorted);
$goodsha = $sorted[0][0];
echo($goodsha);
?>
<p>
First, create a database and then create a table in the database called "Ages":

<pre>
CREATE TABLE Ages ( 
  name VARCHAR(128), 
  age INTEGER
)
</pre>
<p>
Then make sure the table is empty by deleting any rows that 
you previously inserted, and insert these rows and only these rows 
with the following commands:
<pre>
<?php
echo("DELETE FROM Ages;\n");
foreach($database as $row) {
   echo("INSERT INTO Ages (name, age) VALUES ('".$row[1]."', ".$row[2].");\n");
}
?>
</pre>
Once the inserts are done, run the following SQL command:
<pre>
SELECT sha1(CONCAT(name,age)) AS X FROM Ages ORDER BY X
</pre>
</p>


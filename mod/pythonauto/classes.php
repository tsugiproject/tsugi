<?php

class PythonGradeDetail {
    function link($row) {
        echo('<a href="'.sessionize('grade-detail.php?user_id='.$row['user_id']).'">');
        echo(htmlent_utf8($row['displayname']));
        echo('</a>');
    }

}

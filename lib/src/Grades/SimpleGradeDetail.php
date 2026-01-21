<?php

namespace Tsugi\Grades;

class SimpleGradeDetail {
    function link($row) {
        echo('<a href="grade-detail.php?user_id='.$row['user_id'].'">');
        echo(htmlent_utf8($row['displayname']));
        echo('</a>');
    }

}

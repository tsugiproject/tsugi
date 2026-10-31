<?php

use \Tsugi\Controllers\Tool;

$guid_fillable = false;
foreach ( $guid_rows as $row ) {
    if ( ! empty($row['fillable']) ) {
        $guid_fillable = true;
        break;
    }
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();

$red = ' style="color: red;"';
?>
<h1>GUID status</h1>
<table class="table table-bordered">
<thead>
<tr>
<th>Table</th>
<th>Column</th>
<th>Nullability</th>
<th>Index</th>
<th>Null rows</th>
<th>Not null rows</th>
</tr>
</thead>
<tbody>
<?php
foreach ( $guid_rows as $row ) {
    $table = (string) $row['table'];
    $present = ! empty($row['present']);
    $nulls = (int) $row['nulls'];
    $note = (string) $row['note'];
    echo("<tr>\n");
    echo('<td>'.htmlspecialchars($table === '' ? '—' : $table, ENT_QUOTES, 'UTF-8')."</td>\n");
    if ( ! $present ) {
        echo('<td'.$red.'>'.htmlspecialchars($note, ENT_QUOTES, 'UTF-8')."</td>\n");
        echo("<td>—</td><td>—</td><td>—</td><td>—</td>\n");
        echo("</tr>\n");
        continue;
    }
    $type = (string) $row['type'];
    $type_bad = $type !== 'binary(16)';
    echo('<td'.($type_bad ? $red : '').'>'.htmlspecialchars($type, ENT_QUOTES, 'UTF-8'));
    if ( $note !== '' ) {
        echo('<br/>'.htmlspecialchars($note, ENT_QUOTES, 'UTF-8'));
    }
    echo("</td>\n");
    echo('<td>'.($row['nullable'] ? 'NULL' : 'NOT NULL')."</td>\n");
    $indexes = $row['indexes'];
    if ( count($indexes) < 1 ) {
        echo("<td>no index</td>\n");
    } else {
        $shown = array();
        foreach ( $indexes as $index ) {
            $shown[] = htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8');
        }
        echo('<td>'.implode(', ', $shown)."</td>\n");
    }
    echo('<td'.($nulls > 0 ? $red : '').'>'.$nulls."</td>\n");
    echo('<td>'.(int) $row['filled']."</td>\n");
    echo("</tr>\n");
}
?>
</tbody>
</table>
<?php
if ( $guid_healthy ) {
    echo("<p>guids appear to be functioning normally.</p>\n");
}
if ( $guid_fillable ) {
    echo('<form method="post">'."\n");
    echo(Tool::csrfField()."\n");
    echo('<input type="hidden" name="backfill" value="1">'."\n");
    echo('<button type="submit" class="btn btn-default">Backfill</button>'."\n");
    echo("</form>\n");
    echo("<p>Backfill runs only when you press the button. Each press updates at most 5000 null rows per table.</p>\n");
}
$OUTPUT->footer();

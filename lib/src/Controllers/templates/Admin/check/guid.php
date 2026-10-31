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
if ( $guid_fillable && count($guid_sql) > 0 ) {
    $sql_text = implode(";\n\n", $guid_sql).';';
    echo("<style>details > summary { list-style: none; cursor: pointer; } details > summary::-webkit-details-marker { display: none; }</style>\n");
    echo("<details>\n");
    echo('<summary class="btn btn-default" style="display:inline-block; width:auto;">Backfill</summary>'."\n");
    echo("<p>Run this SQL? Each statement updates at most 5000 null rows.</p>\n");
    echo('<pre style="white-space: pre-wrap;">'.htmlspecialchars($sql_text, ENT_QUOTES, 'UTF-8')."</pre>\n");
    echo('<form method="post">'."\n");
    echo(Tool::csrfField()."\n");
    echo('<input type="hidden" name="backfill" value="1">'."\n");
    echo('<button type="submit" class="btn btn-default">Yes</button>'."\n");
    echo('<button type="button" class="btn btn-default" style="margin-left: 0.5em;" onclick="this.closest(\'details\').open = false;">No</button>'."\n");
    echo("</form>\n");
    echo("</details>\n");
}
$OUTPUT->footer();

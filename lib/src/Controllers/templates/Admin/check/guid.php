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
<th>Duplicate GUIDs</th>
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
        echo("<td>—</td><td>—</td><td>—</td><td>—</td><td>—</td>\n");
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
    $duplicates = (int) $row['duplicates'];
    echo('<td'.($nulls > 0 ? $red : '').'>'.$nulls."</td>\n");
    echo('<td>'.(int) $row['filled']."</td>\n");
    echo('<td'.($duplicates > 0 ? $red : '').'>'.$duplicates."</td>\n");
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
    $limit = (int) $guid_limit;
    $seconds = (int) $guid_seconds;
    $commit = (int) $guid_commit;
    echo("<p>Run these statements? Each row is its own UPDATE. The guid is chosen first and stored as a literal. This click runs for up to {$seconds} seconds or {$limit} rows, whichever comes first, and commits about every {$commit} rows. It then says how far it got.</p>\n");
    echo('<pre style="white-space: pre-wrap;">'.htmlspecialchars($sql_text, ENT_QUOTES, 'UTF-8')."</pre>\n");
    echo('<form method="post" id="guid-backfill-form">'."\n");
    echo(Tool::csrfField()."\n");
    echo('<input type="hidden" name="backfill" value="1">'."\n");
    echo('<button type="submit" class="btn btn-default" id="guid-backfill-yes">');
    echo('<i class="fa fa-spinner fa-spin" id="guid-backfill-spin" style="display:none; margin-right:0.35em;" aria-hidden="true"></i>');
    echo("Yes</button>\n");
    echo('<button type="button" class="btn btn-default" id="guid-backfill-no" style="margin-left: 0.5em;" onclick="this.closest(\'details\').open = false;">No</button>'."\n");
    echo("</form>\n");
    echo("<script>\n");
    echo("(function () {\n");
    echo("  var form = document.getElementById('guid-backfill-form');\n");
    echo("  if (!form) return;\n");
    echo("  form.addEventListener('submit', function (ev) {\n");
    echo("    var btn = document.getElementById('guid-backfill-yes');\n");
    echo("    if (!btn) return;\n");
    echo("    if (btn.getAttribute('aria-busy') === 'true') {\n");
    echo("      ev.preventDefault();\n");
    echo("      return;\n");
    echo("    }\n");
    echo("    btn.setAttribute('aria-busy', 'true');\n");
    echo("    btn.style.pointerEvents = 'none';\n");
    echo("    var spin = document.getElementById('guid-backfill-spin');\n");
    echo("    if (spin) spin.style.display = 'inline';\n");
    echo("    var no = document.getElementById('guid-backfill-no');\n");
    echo("    if (no) no.style.pointerEvents = 'none';\n");
    echo("    setTimeout(function () {\n");
    echo("      btn.disabled = true;\n");
    echo("      if (no) no.disabled = true;\n");
    echo("    }, 0);\n");
    echo("  });\n");
    echo("})();\n");
    echo("</script>\n");
    echo("</details>\n");
}
$OUTPUT->footer();

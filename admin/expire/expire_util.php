<?php

function get_count_table($table) {
    global $PDOX, $CFG;
    $row = $PDOX->rowDie("SELECT COUNT(*) AS count FROM {$CFG->dbprefix}{$table}");
    $count = $row ? $row['count'] : 0;
    return $count;
}

function get_expirable_records($table, $days) {
    global $PDOX, $CFG;
    $row = $PDOX->rowDie("
        SELECT COUNT(*) AS count FROM {$CFG->dbprefix}{$table}
        WHERE created_at <= CURRENT_DATE() - INTERVAL :DAY DAY
        AND (login_at IS NULL OR login_at <= CURRENT_DATE() - INTERVAL :DAY DAY)",
        array(":DAY" => $days )
    );
    $count = $row ? $row['count'] : 0;
    return $count;
}


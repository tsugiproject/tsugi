<?php

/**
 * Helpers to purge old mail audit rows (admin only).
 */

const MAIL_ADMIN_PURGE_DAYS = 30;

/**
 * @return string Cutoff datetime for purge (UTC/server local as MySQL NOW()-compatible)
 */
function mail_admin_purge_cutoff($days = MAIL_ADMIN_PURGE_DAYS) {
    $days = (int) $days;
    if ( $days < 1 ) {
        $days = MAIL_ADMIN_PURGE_DAYS;
    }
    return date('Y-m-d H:i:s', strtotime('-'.$days.' days'));
}

/**
 * @return int Number of rows older than $days
 */
function mail_admin_purge_count($table, $days = MAIL_ADMIN_PURGE_DAYS) {
    global $CFG, $PDOX;

    $table = preg_replace('/[^a-z0-9_]/i', '', (string) $table);
    if ( $table === '' ) {
        return 0;
    }
    $row = $PDOX->rowDie(
        "SELECT COUNT(*) AS c FROM {$CFG->dbprefix}{$table} WHERE created_at < :CUTOFF",
        array(':CUTOFF' => mail_admin_purge_cutoff($days))
    );
    return is_array($row) ? (int) $row['c'] : 0;
}

/**
 * @return int Rows deleted
 */
function mail_admin_purge_delete($table, $days = MAIL_ADMIN_PURGE_DAYS) {
    global $CFG, $PDOX;

    $table = preg_replace('/[^a-z0-9_]/i', '', (string) $table);
    if ( $table === '' ) {
        return 0;
    }
    $q = $PDOX->queryReturnError(
        "DELETE FROM {$CFG->dbprefix}{$table} WHERE created_at < :CUTOFF",
        array(':CUTOFF' => mail_admin_purge_cutoff($days))
    );
    if ( !$q->success ) {
        return -1;
    }
    return (int) $q->rowCount();
}

/**
 * Render purge form for a mail audit table.
 *
 * @param string $action_url Relative form action (current script)
 * @param string $table Logical table name without prefix (mail_ses_events|mail_sent)
 * @param string $label Human label for the table
 */
function mail_admin_purge_form($action_url, $table, $label) {
    $days = MAIL_ADMIN_PURGE_DAYS;
    $old = mail_admin_purge_count($table, $days);
    $confirm = 'Delete '.$old.' '.$label.' row(s) older than '.$days.' days?';
    ?>
<div style="margin:8px 0;">
  Older than <?= (int) $days ?> days: <strong><?= (int) $old ?></strong>
  <?php if ( $old > 0 ) { ?>
  <form method="post" action="<?= htmlentities($action_url) ?>" style="display:inline;margin-left:8px;"
        onsubmit="return confirm(<?= htmlentities(json_encode($confirm), ENT_QUOTES) ?>);">
    <input type="hidden" name="purge_old" value="1">
    <input type="hidden" name="confirm_purge" value="1">
    <button type="submit" class="btn btn-danger btn-xs">Purge</button>
  </form>
  <?php } ?>
</div>
    <?php
}

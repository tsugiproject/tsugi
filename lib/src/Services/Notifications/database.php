<?php

if ( ! isset($CFG) ) exit;

$DATABASE_UNINSTALL = array(
    "drop table if exists {$CFG->dbprefix}push_subscriptions",
    "drop table if exists {$CFG->dbprefix}notification"
);

$DATABASE_INSTALL = array(
    array( "{$CFG->dbprefix}push_subscriptions",
        "create table {$CFG->dbprefix}push_subscriptions (
    subscription_id     INTEGER NOT NULL AUTO_INCREMENT,
    user_id             INTEGER NOT NULL,
    endpoint            TEXT NOT NULL,
    p256dh              TEXT NOT NULL,
    auth                TEXT NOT NULL,
    browser_name        VARCHAR(50) NULL,
    browser_endpoint_type VARCHAR(20) NULL,
    user_agent          TEXT NULL,
    created_at          DATETIME NOT NULL,
    updated_at          DATETIME NOT NULL,
    CONSTRAINT `{$CFG->dbprefix}push_subscriptions_const_pk` PRIMARY KEY (subscription_id),
    KEY `{$CFG->dbprefix}push_subscriptions_idx_user_id` (user_id),
    KEY `{$CFG->dbprefix}push_subscriptions_idx_user_browser` (user_id, browser_name)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

    array( "{$CFG->dbprefix}notification",
        "create table {$CFG->dbprefix}notification (
    notification_id     INTEGER NOT NULL AUTO_INCREMENT,
    user_id             INTEGER NOT NULL,
    title               VARCHAR(512) NOT NULL,
    text                TEXT NULL,
    url                 VARCHAR(2048) NULL,
    json                MEDIUMTEXT NULL,
    dedupe_key          VARCHAR(255) NULL,
    read_at             TIMESTAMP NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `{$CFG->dbprefix}notification_const_pk` PRIMARY KEY (notification_id),
    KEY `{$CFG->dbprefix}notification_idx_user_id` (user_id),
    KEY `{$CFG->dbprefix}notification_idx_user_read` (user_id, read_at),
    KEY `{$CFG->dbprefix}notification_idx_dedupe` (user_id, dedupe_key, created_at),
    CONSTRAINT `{$CFG->dbprefix}notification_ibfk_1`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

$DATABASE_POST_CREATE = function($table) {
    global $CFG, $PDOX;

    // Migrate push_subscriptions table to support multiple subscriptions per user (MySQL)
    if ( $PDOX->isMySQL() && $table == "{$CFG->dbprefix}push_subscriptions") {
        $check_sql = "SHOW COLUMNS FROM {$CFG->dbprefix}push_subscriptions LIKE 'browser_name'";
        $exists = $PDOX->rowDie($check_sql);

        if (!$exists) {
            try {
                $index_sql = "ALTER TABLE {$CFG->dbprefix}push_subscriptions
                             ADD KEY `{$CFG->dbprefix}push_subscriptions_idx_user_browser` (user_id, browser_name)";
                $PDOX->queryDie($index_sql);
            } catch (Exception $e) {
                // Index might already exist
            }

            try {
                $PDOX->queryDie("ALTER TABLE {$CFG->dbprefix}push_subscriptions DROP INDEX `{$CFG->dbprefix}push_subscriptions_const_user`");
            } catch (Exception $e) {
                // Index might not exist
            }

            $update_sql = "UPDATE {$CFG->dbprefix}push_subscriptions
                          SET browser_name = CASE
                              WHEN endpoint LIKE '%fcm.googleapis.com%' THEN 'Chrome'
                              WHEN endpoint LIKE '%updates.push.services.mozilla.com%' THEN 'Firefox'
                              WHEN endpoint LIKE '%wns2-%' OR endpoint LIKE '%notify.windows.com%' THEN 'Edge'
                              ELSE 'Unknown'
                          END,
                          browser_endpoint_type = CASE
                              WHEN endpoint LIKE '%fcm.googleapis.com%' THEN 'fcm'
                              WHEN endpoint LIKE '%updates.push.services.mozilla.com%' THEN 'mozilla'
                              WHEN endpoint LIKE '%wns2-%' OR endpoint LIKE '%notify.windows.com%' THEN 'windows'
                              ELSE 'unknown'
                          END
                          WHERE browser_name IS NULL";
            $PDOX->queryDie($update_sql);
        }
    }
};

$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    $add_some_fields = array(
        array('push_subscriptions', 'browser_name', 'VARCHAR(50) NULL'),
        array('push_subscriptions', 'browser_endpoint_type', 'VARCHAR(20) NULL'),
        array('push_subscriptions', 'user_agent', 'TEXT NULL'),
        array('notification', 'dedupe_key', 'VARCHAR(255) NULL'),
    );

    foreach ( $add_some_fields as $add_field ) {
        if (count($add_field) != 3) continue;
        $table = $add_field[0];
        $column = $add_field[1];
        $type = $add_field[2];
        if ( $PDOX->columnExists($column, "{$CFG->dbprefix}".$table) ) continue;
        $sql = "ALTER TABLE {$CFG->dbprefix}$table ADD $column $type";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryReturnError($sql);
    }

    // Add dedupe index for notification de-duplication
    $notification_indexes = array(
        "{$CFG->dbprefix}notification_idx_dedupe" =>
            "CREATE INDEX `{$CFG->dbprefix}notification_idx_dedupe` ON {$CFG->dbprefix}notification (user_id, dedupe_key, created_at)"
    );

    foreach ($notification_indexes as $index_name => $sql) {
        if ( ! $PDOX->indexExists($index_name, "{$CFG->dbprefix}notification") ) {
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryReturnError($sql);
            if ( ! $q->success ) {
                $message = "Non-Fatal error creating notification index: ".$q->errorImplode;
                error_log($message);
                echo($message."<br/>\n");
            }
        }
    }

    return 202503100000;
};

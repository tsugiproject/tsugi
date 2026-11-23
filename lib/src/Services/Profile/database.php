<?php

if ( ! isset($CFG) ) exit;

// Profile is denormalized and not tightly connected to allow
// for disconnecting and reconnecting various user_id values
$DATABASE_UNINSTALL = array(
    "drop table if exists {$CFG->dbprefix}profile"
);

$DATABASE_INSTALL = array(
    array( "{$CFG->dbprefix}profile",
        "create table {$CFG->dbprefix}profile (
    profile_id          INTEGER NOT NULL AUTO_INCREMENT,
    profile_sha256      CHAR(64) NOT NULL UNIQUE,
    -- Same value as lti_user.user_key. googlemail: plus an email can pass 255.
    profile_key         VARCHAR(512) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    key_id              INTEGER NOT NULL,

    displayname         TEXT NULL,
    email               TEXT NULL,
    image               TEXT NULL,
    locale              CHAR(63) NULL,
    subscribe           SMALLINT NULL,
    google_translate    TINYINT(1) NOT NULL DEFAULT 0,

    -- Map pin choice. 0 not chosen, 1 hidden, 2 location, 3 location and name.
    -- Latitude and longitude stay in json.
    map                 TINYINT NULL,

    premium             INTEGER NOT NULL DEFAULT 0,
    premium_at          TIMESTAMP NULL,
    premium_json        MEDIUMTEXT NULL,

    json                MEDIUMTEXT NULL,
    login_at            TIMESTAMP NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,

    INDEX `{$CFG->dbprefix}profile_indx_email` (email(255)),
    INDEX `{$CFG->dbprefix}profile_indx_map` (`map`),

    -- ascii_bin treats trailing spaces as equal. Soft-deleted rows count.
    -- Upgrade adds this unique only after a scan finds no repeated profile_key.
    -- The hash unique on profile_sha256 stays.
    CONSTRAINT `{$CFG->dbprefix}profile_const_profile_key` UNIQUE (profile_key),

    CONSTRAINT `{$CFG->dbprefix}profile_const_pk` PRIMARY KEY (profile_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC")
);

$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    $add_some_fields = array(
        array('profile', 'google_translate', 'TINYINT(1) NOT NULL DEFAULT 0'),
        array('profile', 'premium', 'INTEGER NOT NULL DEFAULT 0'),
        array('profile', 'premium_at', 'TIMESTAMP NULL'),
        array('profile', 'premium_json', 'MEDIUMTEXT NULL'),
        array('profile', 'deleted_at', 'TIMESTAMP NULL'),
        array('profile', 'updated_at', 'TIMESTAMP NULL'),
        array('profile', 'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'),
        array('profile', 'map', 'TINYINT NULL'),
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

    // email is TEXT. A 255-char prefix covers an address and lets
    // unsubscribe opt-out match email = :E instead of scanning LOWER(email).
    $email_index = "{$CFG->dbprefix}profile_indx_email";
    if ( ! $PDOX->indexExists($email_index, "{$CFG->dbprefix}profile") ) {
        $sql = "CREATE INDEX `{$email_index}` ON {$CFG->dbprefix}profile (email(255))";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating index: ".$q->errorImplode;
            error_log($message);
            echo($message."<br/>\n");
        }
    }

    // Copy profile.json.map into the column once. Later saves write the column.
    if ( $oldversion < 202611070002 ) {
        if ( ! $PDOX->columnExists('map', "{$CFG->dbprefix}profile") ) {
            die("Unable to add profile.map<br/>\n");
        }
        $p = $CFG->dbprefix;
        $from_json = "JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(json), json, NULL), '$.map'))";
        $sql = "UPDATE {$p}profile
            SET `map` = CAST({$from_json} AS SIGNED)
            WHERE `map` IS NULL
              AND {$from_json} REGEXP '^[0-3]\$'";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        echo("Profiles with map copied from json: ".$q->rowCount()."<br/>\n");

        $counts = $PDOX->allRowsDie(
            "SELECT `map` AS map, COUNT(*) AS n FROM {$p}profile GROUP BY `map` ORDER BY `map`"
        );
        foreach ( $counts as $count ) {
            $value = $count['map'];
            $label = $value === null ? 'NULL' : $value;
            echo("profile.map ".$label.": ".$count['n']."<br/>\n");
        }

        $mismatch = $PDOX->rowDie(
            "SELECT COUNT(*) AS n FROM {$p}profile
             WHERE {$from_json} REGEXP '^[0-3]\$'
               AND (`map` IS NULL OR `map` <> CAST({$from_json} AS SIGNED))"
        );
        if ( ! is_array($mismatch) || (int) $mismatch['n'] !== 0 ) {
            $n = is_array($mismatch) ? $mismatch['n'] : 'unknown';
            die("profile.map does not match json for {$n} rows<br/>\n");
        }
        echo("profile.map matches json for every 0-3 value<br/>\n");
    }

    // Map page: WHERE map IN (2, 3). The column alone still reads every profile row.
    $map_index = "{$CFG->dbprefix}profile_indx_map";
    if ( ! $PDOX->indexExists($map_index, "{$CFG->dbprefix}profile") ) {
        $sql = "CREATE INDEX `{$map_index}` ON {$CFG->dbprefix}profile (`map`)";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating index: ".$q->errorImplode;
            error_log($message);
            echo($message."<br/>\n");
        }
    }

    // UNIQUE(profile_key) beside UNIQUE(profile_sha256). The LTI upgrade runs
    // first and does not add this. NULL is not allowed on this column.
    // ascii_bin is PAD SPACE, so GROUP BY already treats a trailing space as
    // the same value. Soft-deleted rows count. A prefix unique (Sub_part not
    // null) does not count. The scan runs whenever a full-width unique is
    // missing. A duplicate group skips the index and the upgrade continues.
    $profile_note = function($message) {
        error_log($message);
        echo(htmlentities($message)."<br/>\n");
    };
    $profile_uniques = array(
        array('profile', 'profile_key', 512, false, 'profile_id', 'profile_const_profile_key', array('profile_key')),
    );
    foreach ( $profile_uniques as $profile_unique ) {
        try {
            $profile_table = $profile_unique[0];
            $profile_column = $profile_unique[1];
            $profile_width = (int) $profile_unique[2];
            $profile_nullable = ! empty($profile_unique[3]);
            $profile_id = $profile_unique[4];
            $profile_index_short = $profile_unique[5];
            $profile_columns = $profile_unique[6];
            $profile_label = 'Identity column '.$profile_table.'.'.$profile_column;
            $profile_ident = '/^[A-Za-z0-9_]+$/';
            $profile_names_ok = preg_match($profile_ident, $profile_table) === 1
                && preg_match($profile_ident, $profile_column) === 1
                && preg_match($profile_ident, $profile_id) === 1
                && preg_match($profile_ident, $profile_index_short) === 1
                && $profile_width > 0;
            $profile_wanted = array();
            if ( $profile_names_ok && is_array($profile_columns) ) {
                foreach ( $profile_columns as $profile_unique_column ) {
                    if ( preg_match($profile_ident, $profile_unique_column) !== 1 ) {
                        $profile_names_ok = false;
                        break;
                    }
                    $profile_wanted[] = strtolower($profile_unique_column);
                }
            }
            if ( ! $profile_names_ok || count($profile_wanted) < 1 || ! in_array(strtolower($profile_column), $profile_wanted, true) ) {
                $profile_note($profile_label.': unique description is not usable. Unique index not added.');
                continue;
            }
            $profile_physical = $CFG->dbprefix.$profile_table;
            $profile_index = $CFG->dbprefix.$profile_index_short;
            $profile_unique_sql = implode(', ', $profile_columns);
            if ( ! is_array($PDOX->describe($profile_physical)) ) {
                $profile_note($profile_label.': table '.$profile_table.' is absent. Unique index not added.');
                continue;
            }
            $profile_block = \Tsugi\Services\Admin\AdminService::uniqueColumnBlock($profile_physical, $profile_column, $profile_width, $profile_nullable);
            if ( $profile_block !== '' ) {
                $profile_note($profile_label.': '.$profile_block.'. Unique index not added.');
                continue;
            }
            $profile_detail = $PDOX->indexDetail($profile_physical);
            if ( ! is_array($profile_detail) ) {
                $profile_note($profile_label.': index metadata unavailable. Unique index not added.');
                continue;
            }
            $profile_by_name = array();
            foreach ( $profile_detail as $profile_index_row ) {
                $profile_index_name = (string) \Tsugi\Util\U::getIgnoreCase($profile_index_row, 'Key_name');
                if ( $profile_index_name === '' || strcasecmp($profile_index_name, 'PRIMARY') === 0 ) continue;
                $profile_seq = (int) \Tsugi\Util\U::getIgnoreCase($profile_index_row, 'Seq_in_index');
                if ( ! isset($profile_by_name[$profile_index_name]) ) {
                    $profile_by_name[$profile_index_name] = array(
                        'columns' => array(),
                        'full_unique' => true,
                    );
                }
                $profile_by_name[$profile_index_name]['columns'][$profile_seq] = strtolower((string) \Tsugi\Util\U::getIgnoreCase($profile_index_row, 'Column_name'));
                // Sub_part is NULL for the whole column. A number is a prefix.
                $profile_sub_part = \Tsugi\Util\U::getIgnoreCase($profile_index_row, 'Sub_part');
                if ( (int) \Tsugi\Util\U::getIgnoreCase($profile_index_row, 'Non_unique') !== 0 || $profile_sub_part !== null ) {
                    $profile_by_name[$profile_index_name]['full_unique'] = false;
                }
            }
            $profile_present = null;
            foreach ( $profile_by_name as $profile_index_name => $profile_index_info ) {
                $profile_ordered = $profile_index_info['columns'];
                ksort($profile_ordered);
                $profile_ordered = array_values($profile_ordered);
                if ( $profile_index_info['full_unique'] && $profile_ordered === $profile_wanted ) {
                    $profile_present = $profile_index_name;
                    break;
                }
            }
            if ( $profile_present !== null ) {
                $profile_present_note = $profile_present === $profile_index ? '' : ' ('.$profile_present.')';
                $profile_note($profile_label.': unique ('.$profile_unique_sql.') already present'.$profile_present_note.'.');
                continue;
            }
            $profile_filter = $profile_nullable ? "\n                        WHERE {$profile_column} IS NOT NULL" : '';
            $profile_group_rows = $PDOX->allRows(
                "SELECT COUNT(*) AS duplicate_groups FROM (
                        SELECT 1 AS present
                        FROM {$profile_physical}{$profile_filter}
                        GROUP BY {$profile_unique_sql}
                        HAVING COUNT(*) > 1
                    ) AS duplicate_rows",
                false,
                false
            );
            if ( ! is_array($profile_group_rows) || ! isset($profile_group_rows[0]) ) {
                $profile_note($profile_label.': could not count duplicate values for unique ('.$profile_unique_sql.'). Unique index not added.');
                continue;
            }
            $profile_groups = (int) \Tsugi\Util\U::get($profile_group_rows[0], 'duplicate_groups', 0);
            $profile_group_word = $profile_groups === 1 ? 'group' : 'groups';
            if ( $profile_groups > 0 ) {
                $profile_select = array(
                    'MIN('.$profile_id.') AS example_id',
                    'MAX('.$profile_id.') AS id_max',
                    'COUNT(*) AS n',
                );
                foreach ( $profile_columns as $profile_unique_column ) {
                    $profile_select[] = 'MIN('.$profile_unique_column.') AS '.$profile_unique_column;
                }
                $profile_examples = $PDOX->allRows(
                    "SELECT ".implode(', ', $profile_select)."
                        FROM {$profile_physical}{$profile_filter}
                        GROUP BY {$profile_unique_sql}
                        HAVING COUNT(*) > 1
                        ORDER BY n DESC, example_id
                        LIMIT 5",
                    false,
                    false
                );
                $profile_bits = array();
                if ( is_array($profile_examples) ) {
                    foreach ( $profile_examples as $profile_example ) {
                        $profile_bit = $profile_id.'='.\Tsugi\Util\U::get($profile_example, 'example_id')
                            .' count='.\Tsugi\Util\U::get($profile_example, 'n');
                        foreach ( $profile_columns as $profile_unique_column ) {
                            $profile_bit .= ' '.$profile_unique_column.'='.\Tsugi\Util\U::get($profile_example, $profile_unique_column);
                        }
                        $profile_id_max = \Tsugi\Util\U::get($profile_example, 'id_max');
                        $profile_id_min = \Tsugi\Util\U::get($profile_example, 'example_id');
                        if ( $profile_id_max !== null && $profile_id_max !== false && (string) $profile_id_max !== (string) $profile_id_min ) {
                            $profile_bit .= ' '.$profile_id.'_max='.$profile_id_max;
                        }
                        $profile_bits[] = $profile_bit;
                    }
                }
                $profile_example_text = count($profile_bits) > 0 ? ' Examples: '.implode('; ', $profile_bits).'.' : '';
                $profile_note($profile_label.': '.$profile_groups.' duplicate '.$profile_group_word.' for unique ('.$profile_unique_sql.').'.$profile_example_text.' Unique index not added.');
                continue;
            }
            $profile_have = array();
            if ( isset($profile_by_name[$profile_index]) ) {
                $profile_have = $profile_by_name[$profile_index]['columns'];
                ksort($profile_have);
                $profile_have = array_values($profile_have);
            }
            $profile_replace = count($profile_have) > 0;
            if ( $profile_replace ) {
                $profile_sql = "ALTER TABLE {$profile_physical} DROP INDEX `{$profile_index}`, ADD CONSTRAINT `{$profile_index}` UNIQUE ({$profile_unique_sql})";
                $profile_note($profile_label.': unique '.$profile_index.' is on ('.implode(', ', $profile_have).'). 0 duplicate groups for unique ('.$profile_unique_sql.'). Replacing it.');
            } else {
                $profile_sql = "ALTER TABLE {$profile_physical} ADD CONSTRAINT `{$profile_index}` UNIQUE ({$profile_unique_sql})";
                $profile_note($profile_label.': 0 duplicate groups for unique ('.$profile_unique_sql.'). Adding constraint '.$profile_index.'.');
            }
            $profile_note('Upgrading: '.$profile_sql);
            $profile_alter = $PDOX->queryReturnError($profile_sql, false, false);
            if ( ! $profile_alter->success ) {
                $profile_note($profile_label.': unique ('.$profile_unique_sql.') alter failed: '.$profile_alter->errorImplode.'. Unique index not added.');
                \Tsugi\Services\Admin\AdminService::addUpgradeBeaconNote('unique ('.$profile_unique_sql.') not added: alter failed');
                continue;
            }
            $profile_verb = $profile_replace ? 'replaced' : 'added';
            $profile_note($profile_label.': '.$profile_verb.' unique ('.$profile_unique_sql.').');
        } catch (\Throwable $profile_unique_error) {
            $profile_fail_table = isset($profile_unique[0]) ? $profile_unique[0] : '?';
            $profile_fail_column = isset($profile_unique[1]) ? $profile_unique[1] : '?';
            $profile_note('Identity column '.$profile_fail_table.'.'.$profile_fail_column.': '.$profile_unique_error->getMessage().'. Unique index not added.');
        }
    }

    return 202611220003;
};

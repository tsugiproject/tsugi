<?php

if ( ! isset($CFG) ) exit;

// Permission definitions stay in the in-memory registry. These tables store
// the system scope, requests, and grants. scope_id 1 is the system scope.
//
// create_courses backfill: a non-deleted lti_user with create_courses = 1
// gets one system-scope course.create grant, source create_courses, no
// grantor. A later upgrade skips anyone who already has a course.create
// grant in that scope, including a revoked one. The flag column stays.

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}site_grant",
"drop table if exists {$CFG->dbprefix}site_application",
"drop table if exists {$CFG->dbprefix}site_scope"
);

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}site_scope",
"create table {$CFG->dbprefix}site_scope (
    scope_id            INTEGER NOT NULL AUTO_INCREMENT,
    code                VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    title               VARCHAR(128) NOT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}site_scope_pk` PRIMARY KEY (scope_id),
    UNIQUE `{$CFG->dbprefix}site_scope_uniq_code` (code)

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}site_application",
"create table {$CFG->dbprefix}site_application (
    application_id      INTEGER NOT NULL AUTO_INCREMENT,
    user_id             INTEGER NOT NULL,
    scope_id            INTEGER NOT NULL DEFAULT 1,
    permission          VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    justification       TEXT NOT NULL,
    status              VARCHAR(16) NOT NULL,
    reviewer_id         INTEGER NULL,
    response            TEXT NULL,
    reviewed_at         TIMESTAMP NULL,
    json                MEDIUMTEXT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}site_application_pk` PRIMARY KEY (application_id),

    KEY `{$CFG->dbprefix}site_application_idx_user` (user_id, scope_id, permission, status),
    KEY `{$CFG->dbprefix}site_application_idx_queue` (status, created_at),

    CONSTRAINT `{$CFG->dbprefix}site_application_ibfk_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}site_application_ibfk_reviewer`
        FOREIGN KEY (`reviewer_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}site_application_ibfk_scope`
        FOREIGN KEY (`scope_id`)
        REFERENCES `{$CFG->dbprefix}site_scope` (`scope_id`)
        ON DELETE RESTRICT ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}site_grant",
"create table {$CFG->dbprefix}site_grant (
    grant_id            INTEGER NOT NULL AUTO_INCREMENT,
    user_id             INTEGER NOT NULL,
    scope_id            INTEGER NOT NULL DEFAULT 1,
    permission          VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    source              VARCHAR(16) NOT NULL,
    application_id      INTEGER NULL,
    grantor_id          INTEGER NULL,
    granted_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at          TIMESTAMP NULL,
    revoked_at          TIMESTAMP NULL,
    revoked_by          INTEGER NULL,
    json                MEDIUMTEXT NULL,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}site_grant_pk` PRIMARY KEY (grant_id),

    UNIQUE `{$CFG->dbprefix}site_grant_uniq_application` (application_id),
    UNIQUE `{$CFG->dbprefix}site_grant_uniq_user` (user_id, scope_id, permission),

    CONSTRAINT `{$CFG->dbprefix}site_grant_ibfk_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}site_grant_ibfk_grantor`
        FOREIGN KEY (`grantor_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}site_grant_ibfk_revoked`
        FOREIGN KEY (`revoked_by`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}site_grant_ibfk_scope`
        FOREIGN KEY (`scope_id`)
        REFERENCES `{$CFG->dbprefix}site_scope` (`scope_id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}site_grant_ibfk_application`
        FOREIGN KEY (`application_id`)
        REFERENCES `{$CFG->dbprefix}site_application` (`application_id`)
        ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

$DATABASE_POST_CREATE = function($table) {
    global $CFG, $PDOX;

    if ( $table != "{$CFG->dbprefix}site_scope" ) {
        return;
    }
    $sql = "INSERT INTO {$CFG->dbprefix}site_scope (scope_id, code, title) VALUES (1, 'system', 'System')";
    error_log("Post-create: ".$sql);
    echo("Post-create: ".$sql."<br/>\n");
    $PDOX->queryDie($sql);
};

$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    $p = $CFG->dbprefix;
    $scope = "{$p}site_scope";
    $grant = "{$p}site_grant";
    if ( $PDOX->metadata($scope) === false || $PDOX->metadata($grant) === false ) {
        return $oldversion;
    }

    $system = $PDOX->rowDie("SELECT scope_id FROM {$scope} WHERE scope_id = 1 LIMIT 1");
    if ( ! is_array($system) ) {
        $sql = "INSERT INTO {$scope} (scope_id, code, title) VALUES (1, 'system', 'System')";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryDie($sql);
    }

    $userIndex = "{$p}site_grant_idx_user";
    $userUnique = "{$p}site_grant_uniq_user";
    if ( ! $PDOX->indexExists($userUnique, $grant) ) {
        // One row per user, scope, and permission. Keep the newest if a
        // race already stored two. Add the unique key before dropping the
        // old one: the user foreign key is using that index.
        $PDOX->queryDie(
            "DELETE G FROM {$grant} AS G
             INNER JOIN {$grant} AS N
                ON N.user_id = G.user_id
               AND N.scope_id = G.scope_id
               AND N.permission = G.permission
               AND N.grant_id > G.grant_id"
        );
        $sql = "ALTER TABLE {$grant} ADD UNIQUE `{$userUnique}` (user_id, scope_id, permission)";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryDie($sql);
    }
    if ( $PDOX->indexExists($userIndex, $grant) ) {
        $sql = "ALTER TABLE {$grant} DROP INDEX `{$userIndex}`";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryDie($sql);
    }

    // Bulk INSERT ... SELECT. queryDie() rewrites inserts into upserts.
    // IGNORE keeps one duplicate from aborting the rest of the copy.
    $sql = "INSERT IGNORE INTO {$grant}
            (user_id, scope_id, permission, source, granted_at)
            SELECT U.user_id, :SID, :PERM, :SRC, NOW()
            FROM {$p}lti_user AS U
            WHERE U.create_courses = 1
              AND U.deleted = 0
              AND NOT EXISTS (
                  SELECT 1 FROM {$grant} AS G
                  WHERE G.user_id = U.user_id
                    AND G.scope_id = :SID2
                    AND G.permission = :PERM2
              )";
    $q = $PDOX->queryReturnErrorInternal($sql, array(
        ':SID' => 1,
        ':PERM' => 'course.create',
        ':SRC' => 'create_courses',
        ':SID2' => 1,
        ':PERM2' => 'course.create',
    ));
    if ( ! $q->success ) {
        die("Unable to backfill create_courses grants: ".$q->errorImplode."<br/>\n");
    }
    $copied = $q->rowCount();
    if ( $copied > 0 ) {
        echo("Backfilled {$copied} create_courses grant".($copied == 1 ? '' : 's').".<br/>\n");
        error_log("Backfilled {$copied} create_courses grants.");
    }

    return $oldversion;
};

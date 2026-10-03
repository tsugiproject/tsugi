<?php

namespace Tsugi\Services\Outbound;

use Tsugi\Core\LTIX;

/**
 * What a deployment is willing to release from the registration's request.
 *
 * A row is an allow. No row is a block. The registration document stays the
 * request. iss and sub are not rows; they are always sent. A deployment
 * cannot allow a claim or scope the registration did not ask for.
 */
class ToolDeploymentGrant {

    /**
     * @param int $toolDeploymentId
     * @param string $claim
     * @return void
     */
    public static function allowClaim($toolDeploymentId, $claim) {
        $deployment = self::requireDeployment($toolDeploymentId);
        $claim = trim((string) $claim);
        if ( $claim === 'iss' || $claim === 'sub' ) {
            throw new \InvalidArgumentException('iss and sub are always sent.');
        }
        if ( $claim === '' || strlen($claim) > 64 ) {
            throw new \InvalidArgumentException('That claim was not requested.');
        }
        $document = ToolRegistrationDocument::registrationDocument((int) $deployment['registration_id']);
        $requested = $document === null ? array() : ToolRegistrationDocument::requestedClaims($document);
        if ( ! in_array($claim, $requested, true) ) {
            throw new \InvalidArgumentException('That claim was not requested.');
        }
        self::insert('lti_tool_deployment_claim', 'claim', $deployment, $claim, 'That claim is already allowed for this deployment.');
    }

    /**
     * @param int $toolDeploymentId
     * @param string $claim
     * @return void
     */
    public static function blockClaim($toolDeploymentId, $claim) {
        $deployment = self::requireDeployment($toolDeploymentId);
        self::delete('lti_tool_deployment_claim', 'claim', $deployment, trim((string) $claim), 'That claim is not allowed for this deployment.');
    }

    /**
     * @param int $toolDeploymentId
     * @return array<int, string>
     */
    public static function allowedClaims($toolDeploymentId) {
        $deployment = self::requireDeployment($toolDeploymentId);
        return self::listValues('lti_tool_deployment_claim', 'claim', (int) $deployment['tool_deployment_id']);
    }

    /**
     * @param int $toolDeploymentId
     * @param string $scope
     * @return void
     */
    public static function allowScope($toolDeploymentId, $scope) {
        $deployment = self::requireDeployment($toolDeploymentId);
        $scope = trim((string) $scope);
        if ( $scope === '' || strlen($scope) > 255 ) {
            throw new \InvalidArgumentException('That scope was not requested.');
        }
        $document = ToolRegistrationDocument::registrationDocument((int) $deployment['registration_id']);
        $requested = $document === null ? array() : ToolRegistrationDocument::requestedScopes($document);
        if ( ! in_array($scope, $requested, true) ) {
            throw new \InvalidArgumentException('That scope was not requested.');
        }
        self::insert('lti_tool_deployment_scope', 'scope', $deployment, $scope, 'That scope is already allowed for this deployment.');
    }

    /**
     * @param int $toolDeploymentId
     * @param string $scope
     * @return void
     */
    public static function blockScope($toolDeploymentId, $scope) {
        $deployment = self::requireDeployment($toolDeploymentId);
        self::delete('lti_tool_deployment_scope', 'scope', $deployment, trim((string) $scope), 'That scope is not allowed for this deployment.');
    }

    /**
     * @param int $toolDeploymentId
     * @return array<int, string>
     */
    public static function allowedScopes($toolDeploymentId) {
        $deployment = self::requireDeployment($toolDeploymentId);
        return self::listValues('lti_tool_deployment_scope', 'scope', (int) $deployment['tool_deployment_id']);
    }

    /**
     * Drop grants this registration no longer asks for.
     *
     * @param int $registrationId
     * @param array<int, string> $claims
     * @param array<int, string> $scopes
     * @return void
     */
    public static function retain($registrationId, array $claims, array $scopes) {
        self::deleteMissing('lti_tool_deployment_claim', 'claim', (int) $registrationId, $claims);
        self::deleteMissing('lti_tool_deployment_scope', 'scope', (int) $registrationId, $scopes);
    }

    /**
     * @param string $table
     * @param string $column
     * @param array<string, mixed> $deployment
     * @param string $value
     * @param string $duplicate
     * @return void
     */
    private static function insert($table, $column, array $deployment, $value, $duplicate) {
        $p = self::prefix();
        $existing = self::db()->rowDie(
            "SELECT tool_deployment_id FROM {$p}{$table}
             WHERE tool_deployment_id = :tool_deployment_id AND {$column} = :value",
            array(
                ':tool_deployment_id' => (int) $deployment['tool_deployment_id'],
                ':value' => $value,
            )
        );
        if ( is_array($existing) ) {
            throw new \InvalidArgumentException($duplicate);
        }
        $stmt = self::db()->queryReturnError(
            "INSERT INTO {$p}{$table}
                (tool_deployment_id, registration_id, key_id, {$column}, created_at)
             VALUES
                (:tool_deployment_id, :registration_id, :key_id, :value, NOW())",
            array(
                ':tool_deployment_id' => (int) $deployment['tool_deployment_id'],
                ':registration_id' => (int) $deployment['registration_id'],
                ':key_id' => (int) $deployment['key_id'],
                ':value' => $value,
            )
        );
        if ( $stmt->success ) {
            return;
        }
        $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
        if ( stripos($detail, 'Duplicate') !== false ) {
            throw new \InvalidArgumentException($duplicate);
        }
        throw new \RuntimeException('Could not allow '.$column.'. '.$detail);
    }

    /**
     * @param string $table
     * @param string $column
     * @param array<string, mixed> $deployment
     * @param string $value
     * @param string $missing
     * @return void
     */
    private static function delete($table, $column, array $deployment, $value, $missing) {
        $p = self::prefix();
        $existing = self::db()->rowDie(
            "SELECT tool_deployment_id FROM {$p}{$table}
             WHERE tool_deployment_id = :tool_deployment_id AND {$column} = :value",
            array(
                ':tool_deployment_id' => (int) $deployment['tool_deployment_id'],
                ':value' => $value,
            )
        );
        if ( ! is_array($existing) ) {
            throw new \InvalidArgumentException($missing);
        }
        $stmt = self::db()->queryReturnError(
            "DELETE FROM {$p}{$table}
             WHERE tool_deployment_id = :tool_deployment_id AND {$column} = :value",
            array(
                ':tool_deployment_id' => (int) $deployment['tool_deployment_id'],
                ':value' => $value,
            )
        );
        if ( ! $stmt->success ) {
            $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
            throw new \RuntimeException('Could not block '.$column.'. '.$detail);
        }
    }

    /**
     * @param string $table
     * @param string $column
     * @param int $toolDeploymentId
     * @return array<int, string>
     */
    private static function listValues($table, $column, $toolDeploymentId) {
        $p = self::prefix();
        $rows = self::db()->allRowsDie(
            "SELECT {$column} AS value FROM {$p}{$table}
             WHERE tool_deployment_id = :tool_deployment_id
             ORDER BY {$column} ASC",
            array(':tool_deployment_id' => $toolDeploymentId)
        );
        $out = array();
        foreach ( $rows as $row ) {
            $out[] = (string) $row['value'];
        }
        return $out;
    }

    /**
     * @param string $table
     * @param string $column
     * @param int $registrationId
     * @param array<int, string> $keep
     * @return void
     */
    private static function deleteMissing($table, $column, $registrationId, array $keep) {
        $p = self::prefix();
        $params = array(':registration_id' => $registrationId);
        if ( count($keep) === 0 ) {
            $sql = "DELETE FROM {$p}{$table} WHERE registration_id = :registration_id";
        } else {
            $holders = array();
            foreach ( array_values($keep) as $i => $value ) {
                $key = ':keep_'.$i;
                $holders[] = $key;
                $params[$key] = $value;
            }
            $sql = "DELETE FROM {$p}{$table}
                WHERE registration_id = :registration_id
                  AND {$column} NOT IN (".implode(', ', $holders).")";
        }
        $stmt = self::db()->queryReturnError($sql, $params);
        if ( ! $stmt->success ) {
            $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
            throw new \RuntimeException('Could not reduce deployment grants. '.$detail);
        }
    }

    /**
     * @param int $toolDeploymentId
     * @return array<string, mixed>
     */
    private static function requireDeployment($toolDeploymentId) {
        $toolDeploymentId = (int) $toolDeploymentId;
        if ( $toolDeploymentId < 1 ) {
            throw new \InvalidArgumentException('Deployment is required.');
        }
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT tool_deployment_id, registration_id, key_id
             FROM {$p}lti_tool_deployment
             WHERE tool_deployment_id = :tool_deployment_id",
            array(':tool_deployment_id' => $toolDeploymentId)
        );
        if ( ! is_array($row) ) {
            throw new \InvalidArgumentException('Deployment was not found.');
        }
        return array(
            'tool_deployment_id' => (int) $row['tool_deployment_id'],
            'registration_id' => (int) $row['registration_id'],
            'key_id' => (int) $row['key_id'],
        );
    }

    /**
     * @return \Tsugi\Util\PDOX
     */
    private static function db() {
        global $CFG, $PDOX;
        LTIX::getConnection();
        if ( ! isset($CFG) || ! is_object($CFG) || ! isset($PDOX) || ! is_object($PDOX) ) {
            throw new \RuntimeException('Database is not available.');
        }
        return $PDOX;
    }

    /**
     * @return string
     */
    private static function prefix() {
        global $CFG;
        return (string) $CFG->dbprefix;
    }
}

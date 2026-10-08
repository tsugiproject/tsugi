<?php

namespace Tsugi\Services\Ims;

use Tsugi\Core\LTIX;
use Tsugi\Services\Outbound\ToolDeploymentGrant;
use Tsugi\Services\Outbound\ToolDeploymentService;
use Tsugi\Services\Outbound\ToolRegistrationDocument;

/**
 * LTI Advantage Names and Roles Provisioning Service, version 2.
 *
 * The launch claim points here only when the deployment allows
 * contextmembership.readonly. A call still checks that deployment again:
 * the token's scope list is not enough on its own.
 *
 * TODO: Support NRPS differences and paging (https://github.com/tsugiproject/tsugi/issues/289).
 * limit and offset only slice the full roster after it is loaded.
 */
class NamesRoles {

    public const ROLE_LEARNER = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner';
    public const ROLE_INSTRUCTOR = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor';
    public const ROLE_ADMINISTRATOR = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator';

    public const LIMIT_MAX = 500;

    /**
     * Absolute membership URL placed in the launch claim.
     */
    public static function membershipUrl(int $contextId) {
        global $CFG;
        return rtrim((string) $CFG->wwwroot, '/').'/ims/nrps/context/'.$contextId.'/memberships';
    }

    /**
     * Context id from a request path, or null when this is not the membership URL.
     */
    public static function contextIdFromPath(string $path) {
        if ( ! preg_match('#/ims/nrps/context/([1-9][0-9]*)/memberships/?$#', $path, $match) ) {
            return null;
        }
        if ( (string) (int) $match[1] !== $match[1] ) {
            return null;
        }
        return (int) $match[1];
    }

    /**
     * @param array<string, mixed> $query
     * @return array{status:int, body:array<string, mixed>, next:?string}
     */
    public static function read(string $accessToken, int $contextId, array $query) {
        $token = AccessToken::verify($accessToken);
        if ( $token === null ) {
            return self::error(401, 'invalid_token');
        }
        if ( ! in_array(ToolRegistrationDocument::SCOPE_ROSTER, $token['scopes'], true) ) {
            return self::error(403, 'insufficient_scope');
        }
        if ( $contextId < 1 ) {
            return self::error(404, 'not_found');
        }

        $limit = self::limit($query);
        if ( $limit === false ) {
            return self::error(400, 'invalid_request');
        }
        $offset = self::offset($query, $limit);
        if ( $offset === false ) {
            return self::error(400, 'invalid_request');
        }
        $roleFilter = isset($query['role']) && is_string($query['role']) ? trim($query['role']) : '';

        try {
            $visible = ToolDeploymentService::getDeploymentsForContext($contextId);
        } catch ( \InvalidArgumentException $ex ) {
            return self::error(404, 'not_found');
        }

        $covering = array();
        foreach ( $visible as $deployment ) {
            if ( (int) $deployment['registration_id'] !== $token['registration_id'] ) {
                continue;
            }
            if ( (int) $deployment['key_id'] !== $token['key_id'] ) {
                continue;
            }
            $deploymentId = isset($deployment['deployment_id']) ? (string) $deployment['deployment_id'] : '';
            if ( $token['deployment_id'] !== null && $deploymentId !== $token['deployment_id'] ) {
                continue;
            }
            $scopes = ToolDeploymentGrant::allowedScopes((int) $deployment['tool_deployment_id']);
            if ( ! in_array(ToolRegistrationDocument::SCOPE_ROSTER, $scopes, true) ) {
                continue;
            }
            $covering[] = (int) $deployment['tool_deployment_id'];
        }
        if ( count($covering) < 1 ) {
            return self::error(403, 'not_allowed');
        }

        $context = self::contextRow($contextId, $token['key_id']);
        if ( $context === null ) {
            return self::error(404, 'not_found');
        }
        $claims = self::sharedClaims($covering);
        $members = self::members($contextId, $context['key_id'], $claims, $roleFilter);
        $page = $members;
        $next = null;
        if ( $limit !== null ) {
            $page = array_slice($members, $offset, $limit);
            if ( ($offset + $limit) < count($members) ) {
                $next = self::nextUrl(self::membershipUrl($contextId), $limit, $offset + $limit, $roleFilter);
            }
        }

        return array(
            'status' => 200,
            'next' => $next,
            'body' => array(
                'id' => self::membershipUrl($contextId),
                'context' => array(
                    'id' => $context['context_id'],
                    'label' => $context['label'],
                    'title' => $context['title'],
                ),
                'members' => $page,
            ),
        );
    }

    /**
     * @param array<string, mixed> $query
     * @return int|null|false null means return every member
     */
    private static function limit(array $query) {
        if ( ! isset($query['limit']) || $query['limit'] === '' ) {
            return null;
        }
        $raw = $query['limit'];
        if ( is_array($raw) ) {
            return false;
        }
        $raw = (string) $raw;
        if ( ! preg_match('/^[1-9][0-9]*$/', $raw) || (string) (int) $raw !== $raw ) {
            return false;
        }
        $limit = (int) $raw;
        if ( $limit > self::LIMIT_MAX ) {
            $limit = self::LIMIT_MAX;
        }
        return $limit;
    }

    /**
     * @param array<string, mixed> $query
     * @param int|null $limit
     * @return int|false
     */
    private static function offset(array $query, $limit) {
        if ( $limit === null || ! isset($query['offset']) || $query['offset'] === '' ) {
            return 0;
        }
        $raw = $query['offset'];
        if ( is_array($raw) ) {
            return false;
        }
        $raw = (string) $raw;
        if ( ! preg_match('/^(0|[1-9][0-9]*)$/', $raw) || (string) (int) $raw !== $raw ) {
            return false;
        }
        return (int) $raw;
    }

    /**
     * @param array<int, int> $deploymentIds
     * @return array<int, string>
     */
    private static function sharedClaims(array $deploymentIds) {
        $shared = null;
        foreach ( $deploymentIds as $deploymentId ) {
            $claims = ToolDeploymentGrant::allowedClaims($deploymentId);
            if ( $shared === null ) {
                $shared = $claims;
                continue;
            }
            $shared = array_values(array_intersect($shared, $claims));
        }
        return is_array($shared) ? $shared : array();
    }

    /**
     * @param array<int, string> $claims
     * @return array<int, array<string, mixed>>
     */
    private static function members(int $contextId, int $keyId, array $claims, string $roleFilter) {
        $p = self::prefix();
        $rows = self::db()->allRowsDie(
            "SELECT U.user_id, U.displayname, U.email, M.role, M.role_override
             FROM {$p}lti_membership M
             JOIN {$p}lti_user U ON U.user_id = M.user_id AND U.key_id = :key_id
             WHERE M.context_id = :context_id AND M.deleted = 0
             ORDER BY U.user_id ASC",
            array(
                ':context_id' => $contextId,
                ':key_id' => $keyId,
            )
        );
        if ( ! is_array($rows) ) {
            return array();
        }
        $sendName = in_array('name', $claims, true);
        $sendGiven = in_array('given_name', $claims, true);
        $sendFamily = in_array('family_name', $claims, true);
        $sendEmail = in_array('email', $claims, true);
        $members = array();
        foreach ( $rows as $row ) {
            $roles = self::roleUris(self::effectiveRole($row));
            if ( $roleFilter !== '' && ! in_array($roleFilter, $roles, true) ) {
                continue;
            }
            $member = array(
                'status' => 'Active',
                'user_id' => (string) $row['user_id'],
                'roles' => $roles,
            );
            $full = isset($row['displayname']) && is_string($row['displayname']) ? trim($row['displayname']) : '';
            $given = $full;
            $family = '';
            $space = strpos($full, ' ');
            if ( $space !== false ) {
                $given = trim(substr($full, 0, $space));
                $family = trim(substr($full, $space + 1));
            }
            if ( $sendName && $full !== '' ) {
                $member['name'] = $full;
            }
            if ( $sendGiven && $given !== '' ) {
                $member['given_name'] = $given;
            }
            if ( $sendFamily && $family !== '' ) {
                $member['family_name'] = $family;
            }
            $email = isset($row['email']) && is_string($row['email']) ? trim($row['email']) : '';
            if ( $sendEmail && $email !== '' ) {
                $member['email'] = $email;
            }
            $members[] = $member;
        }
        return $members;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function effectiveRole(array $row) {
        $role = isset($row['role']) ? (int) $row['role'] : 0;
        $override = isset($row['role_override']) ? (int) $row['role_override'] : 0;
        return max($role, $override);
    }

    /**
     * @return array<int, string>
     */
    private static function roleUris(int $role) {
        if ( $role >= LTIX::ROLE_ADMINISTRATOR ) {
            return array(self::ROLE_ADMINISTRATOR, self::ROLE_INSTRUCTOR);
        }
        if ( $role >= LTIX::ROLE_INSTRUCTOR ) {
            return array(self::ROLE_INSTRUCTOR);
        }
        return array(self::ROLE_LEARNER);
    }

    private static function nextUrl(string $membershipUrl, int $limit, int $offset, string $roleFilter) {
        $query = array(
            'limit' => (string) $limit,
            'offset' => (string) $offset,
        );
        if ( $roleFilter !== '' ) {
            $query['role'] = $roleFilter;
        }
        return $membershipUrl.'?'.http_build_query($query);
    }

    /**
     * @return array{context_id:string, key_id:int, title:string, label:string}|null
     */
    private static function contextRow(int $contextId, int $keyId) {
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT context_id, key_id, title, short_title
             FROM {$p}lti_context
             WHERE context_id = :context_id AND key_id = :key_id",
            array(
                ':context_id' => $contextId,
                ':key_id' => $keyId,
            )
        );
        if ( ! is_array($row) ) {
            return null;
        }
        $title = isset($row['title']) && is_string($row['title']) ? trim($row['title']) : '';
        if ( $title === '' ) {
            $title = 'Course';
        }
        $label = isset($row['short_title']) && is_string($row['short_title']) ? trim($row['short_title']) : '';
        if ( $label === '' ) {
            $label = $title;
        }
        return array(
            'context_id' => (string) $row['context_id'],
            'key_id' => (int) $row['key_id'],
            'title' => $title,
            'label' => $label,
        );
    }

    /**
     * @return array{status:int, body:array<string, mixed>, next:?string}
     */
    private static function error(int $status, string $error) {
        return array(
            'status' => $status,
            'next' => null,
            'body' => array('error' => $error),
        );
    }

    /**
     * @return \Tsugi\Util\PDOX
     */
    private static function db() {
        $PDOX = LTIX::getConnection();
        if ( ! $PDOX ) {
            throw new \RuntimeException('Database connection is not available.');
        }
        return $PDOX;
    }

    private static function prefix() {
        global $CFG;
        return isset($CFG->dbprefix) ? (string) $CFG->dbprefix : '';
    }
}

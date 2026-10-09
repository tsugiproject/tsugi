<?php

namespace Tsugi\Services\Outbound;

use Tsugi\Core\LTIX;
use Tsugi\Util\U;

/**
 * One outbound launch. A lesson item stores content_id. Launch fields live here.
 *
 * A new row is unpublished. link_id null is not a grade column. Checking send
 * grade creates the link and copies published onto it. After that, student
 * visibility is the link's published flag.
 */
class LtiContentService {

    /**
     * @param int $contextId
     * @param int $toolDeploymentId
     * @param string $title Outline title. Empty uses the tool title.
     * @return array<string, mixed>
     */
    public static function place($contextId, $toolDeploymentId, $title) {
        $contextId = (int) $contextId;
        $tool = ToolRegistrationService::visibleLti11Deployment($contextId, (int) $toolDeploymentId);
        if ( ! Lti11TestLaunch::hasResourceLink($contextId, (int) $tool['tool_deployment_id']) ) {
            throw new \InvalidArgumentException('This tool does not have a resource link launch.');
        }
        $launchUrl = trim((string) $tool['lti11_url']);
        self::refuseDroppedPost($launchUrl);
        return self::writeContent($contextId, $tool, $title, $launchUrl);
    }

    /**
     * Place a launch URL that was chosen from a deployment's messages.
     *
     * The caller has already checked that this course can use the deployment
     * and that the URL is the one the message or the deep link return named.
     *
     * @param int $contextId
     * @param int $toolDeploymentId
     * @param string $title Outline title. Empty uses the tool title.
     * @param string $launchUrl
     * @return array<string, mixed>
     */
    public static function placeAt($contextId, $toolDeploymentId, $title, $launchUrl) {
        $contextId = (int) $contextId;
        $tool = self::visibleDeployment($contextId, (int) $toolDeploymentId);
        return self::writeContent($contextId, $tool, $title, $launchUrl);
    }

    /**
     * @param int $contextId
     * @param array<string, mixed> $tool
     * @param string $title
     * @param string $launchUrl
     * @return array<string, mixed>
     */
    private static function writeContent($contextId, array $tool, $title, $launchUrl) {
        $launchUrl = trim((string) $launchUrl);
        if ( ! preg_match('#^https?://#i', $launchUrl) ) {
            throw new \InvalidArgumentException('This tool has no launch URL.');
        }
        $context = self::contextRow($contextId);
        if ( (int) $context['key_id'] !== (int) $tool['key_id'] ) {
            throw new \InvalidArgumentException('That deployment is in a different tenant.');
        }
        $title = self::titleOrTool(trim((string) $title), (string) $tool['title']);
        $privacy = self::grant((int) $tool['tool_deployment_id']);
        $resourceLinkId = self::newResourceLinkId($contextId);
        $p = self::prefix();
        self::db()->queryDie(
            "INSERT INTO {$p}lti_content
                (context_id, key_id, tool_deployment_id, title, launch_url, target,
                 resource_link_id, resource_link_sha256,
                 send_name, send_email, send_grade, published, created_at)
             VALUES
                (:context_id, :key_id, :tool_deployment_id, :title, :launch_url, 'window',
                 :resource_link_id, :resource_link_sha256,
                 :send_name, :send_email, :send_grade, 0, NOW())",
            array(
                ':context_id' => $contextId,
                ':key_id' => (int) $tool['key_id'],
                ':tool_deployment_id' => (int) $tool['tool_deployment_id'],
                ':title' => $title,
                ':launch_url' => $launchUrl,
                ':resource_link_id' => $resourceLinkId,
                ':resource_link_sha256' => U::lti_sha256($resourceLinkId),
                ':send_name' => $privacy['send_name'] ? 1 : 0,
                ':send_email' => $privacy['send_email'] ? 1 : 0,
                ':send_grade' => $privacy['send_grade'] ? 1 : 0,
            )
        );
        $contentId = (int) self::db()->lastInsertId();
        $row = self::requireRow($contextId, $contentId);
        if ( $privacy['send_grade'] ) {
            self::ensureGradeColumn($row);
        }
        return self::editorRow(self::requireRow($contextId, $contentId));
    }

    /**
     * Target, launch URL, and the send flags. Does not change the lesson document.
     *
     * @param int $contextId
     * @param int $contentId
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    public static function updateLaunch($contextId, $contentId, array $fields) {
        $contextId = (int) $contextId;
        $row = self::requireRow($contextId, (int) $contentId);
        $privacy = self::grant((int) $row['tool_deployment_id']);
        $sets = array();
        $parms = array(':content_id' => (int) $row['content_id']);
        if ( array_key_exists('launch_url', $fields) ) {
            $launchUrl = trim((string) $fields['launch_url']);
            if ( $launchUrl === '' ) {
                throw new \InvalidArgumentException('Launch URL is required.');
            }
            self::refuseDroppedPost($launchUrl);
            $sets[] = 'launch_url = :launch_url';
            $parms[':launch_url'] = $launchUrl;
        }
        if ( array_key_exists('target', $fields) ) {
            $target = trim((string) $fields['target']);
            if ( ! in_array($target, array('window', 'iframe', 'inline'), true) ) {
                throw new \InvalidArgumentException('That open target is not valid.');
            }
            $sets[] = 'target = :target';
            $parms[':target'] = $target;
        }
        foreach ( array('send_name', 'send_email', 'send_grade') as $flag ) {
            if ( ! array_key_exists($flag, $fields) ) {
                continue;
            }
            $on = self::flag($fields[$flag]) === 1;
            if ( $on && empty($privacy[$flag]) ) {
                throw new \InvalidArgumentException('This deployment does not allow that.');
            }
            $sets[] = $flag.' = :'.$flag;
            $parms[':'.$flag] = $on ? 1 : 0;
            $row[$flag] = $on ? 1 : 0;
        }
        if ( count($sets) > 0 ) {
            $p = self::prefix();
            self::db()->queryDie(
                "UPDATE {$p}lti_content SET ".implode(', ', $sets).", updated_at = NOW()
                 WHERE content_id = :content_id",
                $parms
            );
        }
        if ( (int) $row['send_grade'] === 1 ) {
            self::ensureGradeColumn(self::requireRow($contextId, (int) $row['content_id']));
        }
        return self::editorRow(self::requireRow($contextId, (int) $row['content_id']));
    }

    /**
     * Copy the lesson outline title onto this row, and onto its grade column.
     * An empty lesson title leaves the content title alone.
     *
     * @param int $contextId
     * @param int $contentId
     * @param string $title
     * @return void
     */
    public static function copyTitle($contextId, $contentId, $title) {
        $title = trim((string) $title);
        if ( $title === '' ) {
            return;
        }
        if ( mb_strlen($title) > 512 ) {
            throw new \InvalidArgumentException('That title is too long.');
        }
        $row = self::requireRow((int) $contextId, (int) $contentId);
        $p = self::prefix();
        self::db()->queryDie(
            "UPDATE {$p}lti_content SET title = :title, updated_at = NOW()
             WHERE content_id = :content_id",
            array(
                ':title' => $title,
                ':content_id' => (int) $row['content_id'],
            )
        );
        if ( ! empty($row['link_id']) ) {
            self::db()->queryDie(
                "UPDATE {$p}lti_link SET title = :title, updated_at = NOW()
                 WHERE link_id = :link_id AND context_id = :context_id",
                array(
                    ':title' => $title,
                    ':link_id' => (int) $row['link_id'],
                    ':context_id' => (int) $contextId,
                )
            );
        }
    }

    /**
     * @param int $contextId
     * @param int $contentId
     * @return array<string, mixed>|null
     */
    public static function find($contextId, $contentId) {
        $contentId = (int) $contentId;
        if ( $contentId < 1 ) {
            return null;
        }
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT c.content_id, c.context_id, c.key_id, c.tool_deployment_id, c.link_id,
                    c.title, c.launch_url, c.target, c.resource_link_id,
                    c.send_name, c.send_email, c.send_grade, c.published,
                    l.published AS link_published
             FROM {$p}lti_content c
             LEFT JOIN {$p}lti_link l ON l.link_id = c.link_id
             WHERE c.content_id = :content_id AND c.context_id = :context_id",
            array(
                ':content_id' => $contentId,
                ':context_id' => (int) $contextId,
            )
        );
        return is_array($row) ? $row : null;
    }

    /**
     * @param int $contextId
     * @param array<int, int> $contentIds
     * @return array<int, array<string, mixed>>
     */
    public static function editorRows($contextId, array $contentIds) {
        $out = array();
        foreach ( $contentIds as $contentId ) {
            $row = self::find((int) $contextId, (int) $contentId);
            if ( $row === null ) {
                continue;
            }
            $editor = self::editorRow($row);
            $out[(int) $editor['id']] = $editor;
        }
        return $out;
    }

    /**
     * Every launch row in this course, keyed by content id.
     *
     * @param int $contextId
     * @return array<int, array<string, mixed>>
     */
    public static function rowsForContext($contextId) {
        $p = self::prefix();
        $rows = self::db()->allRowsDie(
            "SELECT c.content_id, c.context_id, c.key_id, c.tool_deployment_id, c.link_id,
                    c.title, c.launch_url, c.target, c.resource_link_id,
                    c.send_name, c.send_email, c.send_grade, c.published,
                    l.published AS link_published
             FROM {$p}lti_content c
             LEFT JOIN {$p}lti_link l ON l.link_id = c.link_id
             WHERE c.context_id = :context_id
             ORDER BY c.content_id",
            array(':context_id' => (int) $contextId)
        );
        $out = array();
        if ( ! is_array($rows) ) {
            return $out;
        }
        foreach ( $rows as $row ) {
            if ( ! is_array($row) ) {
                continue;
            }
            $editor = self::editorRow($row);
            $out[(int) $editor['id']] = $editor;
        }
        return $out;
    }

    /**
     * Student visibility. A grade column uses the link. Otherwise this row.
     *
     * @param int $contextId
     * @param int $contentId
     * @param bool $published
     * @return bool
     */
    public static function setPublished($contextId, $contentId, $published) {
        $row = self::find((int) $contextId, (int) $contentId);
        if ( $row === null ) {
            return false;
        }
        $flag = $published ? 1 : 0;
        $p = self::prefix();
        if ( ! empty($row['link_id']) ) {
            self::db()->queryDie(
                "UPDATE {$p}lti_link
                 SET published = :published, deleted = 0, updated_at = NOW()
                 WHERE link_id = :link_id AND context_id = :context_id",
                array(
                    ':published' => $flag,
                    ':link_id' => (int) $row['link_id'],
                    ':context_id' => (int) $contextId,
                )
            );
            return true;
        }
        self::db()->queryDie(
            "UPDATE {$p}lti_content SET published = :published, updated_at = NOW()
             WHERE content_id = :content_id",
            array(
                ':published' => $flag,
                ':content_id' => (int) $row['content_id'],
            )
        );
        return true;
    }

    /**
     * True when students may see this launch. Null when the row is missing.
     *
     * @param int $contextId
     * @param int $contentId
     * @return bool|null
     */
    public static function studentVisible($contextId, $contentId) {
        $row = self::find((int) $contextId, (int) $contentId);
        if ( $row === null ) {
            return null;
        }
        return self::effectivePublished($row);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function editorRow(array $row) {
        return array(
            'id' => (int) $row['content_id'],
            'tool_deployment_id' => (int) $row['tool_deployment_id'],
            'title' => (string) $row['title'],
            'launch_url' => (string) $row['launch_url'],
            'target' => (string) $row['target'],
            'resource_link_id' => (string) $row['resource_link_id'],
            'send_name' => (int) $row['send_name'] === 1,
            'send_email' => (int) $row['send_email'] === 1,
            'send_grade' => (int) $row['send_grade'] === 1,
            'published' => self::effectivePublished($row),
            'link_id' => empty($row['link_id']) ? null : (int) $row['link_id'],
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return bool
     */
    private static function effectivePublished(array $row) {
        if ( ! empty($row['link_id']) ) {
            return isset($row['link_published']) && (int) $row['link_published'] === 1;
        }
        return (int) $row['published'] === 1;
    }

    /**
     * @param array<string, mixed> $row
     * @return void
     */
    private static function ensureGradeColumn(array $row) {
        if ( ! empty($row['link_id']) ) {
            return;
        }
        $p = self::prefix();
        $sha = U::lti_sha256((string) $row['resource_link_id']);
        $existing = self::db()->rowDie(
            "SELECT link_id FROM {$p}lti_link
             WHERE context_id = :context_id AND link_sha256 = :link_sha256",
            array(
                ':context_id' => (int) $row['context_id'],
                ':link_sha256' => $sha,
            )
        );
        if ( is_array($existing) && isset($existing['link_id']) ) {
            $linkId = (int) $existing['link_id'];
        } else {
            $title = trim((string) $row['title']);
            if ( $title === '' ) {
                $title = (string) $row['resource_link_id'];
            }
            self::db()->queryDie(
                "INSERT INTO {$p}lti_link
                    (link_key, link_sha256, title, context_id, published, created_at, updated_at)
                 VALUES
                    (:link_key, :link_sha256, :title, :context_id, :published, NOW(), NOW())",
                array(
                    ':link_key' => (string) $row['resource_link_id'],
                    ':link_sha256' => $sha,
                    ':title' => $title,
                    ':context_id' => (int) $row['context_id'],
                    ':published' => (int) $row['published'] === 1 ? 1 : 0,
                )
            );
            $linkId = (int) self::db()->lastInsertId();
        }
        self::db()->queryDie(
            "UPDATE {$p}lti_content SET link_id = :link_id, updated_at = NOW()
             WHERE content_id = :content_id AND link_id IS NULL",
            array(
                ':link_id' => $linkId,
                ':content_id' => (int) $row['content_id'],
            )
        );
    }

    /**
     * @param int $toolDeploymentId
     * @return array{send_name:bool, send_email:bool, send_grade:bool}
     */
    private static function grant($toolDeploymentId) {
        $claims = ToolDeploymentGrant::allowedClaims((int) $toolDeploymentId);
        $scopes = ToolDeploymentGrant::allowedScopes((int) $toolDeploymentId);
        return array(
            'send_name' => in_array('name', $claims, true)
                || in_array('given_name', $claims, true)
                || in_array('family_name', $claims, true),
            'send_email' => in_array('email', $claims, true),
            'send_grade' => in_array(ToolRegistrationDocument::SCOPE_SCORE, $scopes, true),
        );
    }

    /**
     * @param int $contextId
     * @return string
     */
    private static function newResourceLinkId($contextId) {
        $p = self::prefix();
        for ( $i = 0; $i < 5; $i++ ) {
            $id = 'lti_'.bin2hex(random_bytes(8));
            $sha = U::lti_sha256($id);
            $parms = array(
                ':context_id' => (int) $contextId,
                ':sha' => $sha,
            );
            $taken = self::db()->rowDie(
                "SELECT content_id FROM {$p}lti_content
                 WHERE context_id = :context_id AND resource_link_sha256 = :sha",
                $parms
            );
            if ( ! is_array($taken) ) {
                $taken = self::db()->rowDie(
                    "SELECT link_id FROM {$p}lti_link
                     WHERE context_id = :context_id AND link_sha256 = :sha",
                    $parms
                );
            }
            if ( ! is_array($taken) ) {
                return $id;
            }
        }
        throw new \InvalidArgumentException('Could not allocate a resource link id.');
    }

    /**
     * @param string $title
     * @param string $toolTitle
     * @return string
     */
    private static function titleOrTool($title, $toolTitle) {
        if ( $title === '' ) {
            $title = trim($toolTitle);
        }
        if ( $title === '' ) {
            $title = 'Tool';
        }
        if ( mb_strlen($title) > 512 ) {
            throw new \InvalidArgumentException('That title is too long.');
        }
        return $title;
    }

    /**
     * @param mixed $value
     * @return int
     */
    private static function flag($value) {
        if ( $value === true || $value === 1 || $value === '1' ) {
            return 1;
        }
        if ( $value === false || $value === 0 || $value === '0' ) {
            return 0;
        }
        throw new \InvalidArgumentException('That choice is not valid.');
    }

    /**
     * @param int $contextId
     * @param int $contentId
     * @return array<string, mixed>
     */
    private static function requireRow($contextId, $contentId) {
        $row = self::find($contextId, $contentId);
        if ( $row === null ) {
            throw new \InvalidArgumentException('That launch was not found in this course.');
        }
        return $row;
    }

    /**
     * A deployment this course can see, LTI 1.1 or 1.3.
     *
     * @param int $contextId
     * @param int $toolDeploymentId
     * @return array{registration_id:int, key_id:int, title:string, tool_deployment_id:int}
     */
    private static function visibleDeployment($contextId, $toolDeploymentId) {
        $toolDeploymentId = (int) $toolDeploymentId;
        $found = null;
        foreach ( ToolDeploymentService::getDeploymentsForContext((int) $contextId) as $row ) {
            if ( (int) $row['tool_deployment_id'] === $toolDeploymentId ) {
                $found = $row;
                break;
            }
        }
        if ( $found === null ) {
            throw new \InvalidArgumentException('This course does not have that deployment.');
        }
        $p = self::prefix();
        $reg = self::db()->rowDie(
            "SELECT registration_id, key_id, title, lti_version
             FROM {$p}lti_tool_registration
             WHERE registration_id = :registration_id",
            array(':registration_id' => (int) $found['registration_id'])
        );
        if ( ! is_array($reg) || (int) $reg['key_id'] !== (int) $found['key_id'] ) {
            throw new \InvalidArgumentException('This course does not have that deployment.');
        }
        return array(
            'registration_id' => (int) $reg['registration_id'],
            'key_id' => (int) $reg['key_id'],
            'title' => (string) $reg['title'],
            'lti_version' => (string) $reg['lti_version'],
            'tool_deployment_id' => $toolDeploymentId,
        );
    }

    /**
     * The deployment is still available in this course.
     *
     * @param int $contextId
     * @param int $toolDeploymentId
     * @return bool
     */
    public static function inCourse($contextId, $toolDeploymentId) {
        try {
            self::visibleDeployment((int) $contextId, (int) $toolDeploymentId);
        } catch ( \InvalidArgumentException $ex ) {
            return false;
        }
        return true;
    }

    /**
     * @param int $contextId
     * @param int $toolDeploymentId
     * @return string
     */
    public static function version($contextId, $toolDeploymentId) {
        return self::visibleDeployment((int) $contextId, (int) $toolDeploymentId)['lti_version'];
    }

    /**
     * Refuse an address that will not accept the launch.
     *
     * Checked when the link is authored. A launch uses the stored address as it is.
     * A redirect that drops a POST, or a missing address, is refused. No answer
     * is allowed. The tool may simply be down.
     *
     * @param string $url
     * @param callable|null $lookup Returns array{code:int, location:string}, or null when there is no answer.
     * @return void
     */
    public static function refuseDroppedPost($url, $lookup = null) {
        $reply = $lookup === null ? self::headReply($url) : $lookup($url);
        if ( ! is_array($reply) ) {
            return;
        }
        $code = isset($reply['code']) ? (int) $reply['code'] : 0;
        if ( $code === 404 || $code === 410 ) {
            throw new \InvalidArgumentException('This address was not found, so the launch will not arrive.');
        }
        if ( ! in_array($code, array(301, 302, 303), true) ) {
            return;
        }
        $target = isset($reply['location']) ? trim((string) $reply['location']) : '';
        if ( $target === '' ) {
            $target = trim((string) $url);
        }
        throw new \InvalidArgumentException('This address redirects to '.$target.', so the launch will not arrive.');
    }

    /**
     * The status and redirect target from one HEAD. Null when the host does not answer.
     *
     * @param string $url
     * @return array{code:int, location:string}|null
     */
    private static function headReply($url) {
        if ( ! function_exists('curl_init') ) {
            return null;
        }
        $ch = curl_init($url);
        if ( $ch === false ) {
            return null;
        }
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ( ! is_string($raw) || $code < 100 ) {
            return null;
        }
        $location = '';
        if ( preg_match('/^Location:\s*(\S+)/mi', $raw, $match) ) {
            $location = self::resolveRedirect($url, $match[1]);
        }
        return array(
            'code' => $code,
            'location' => $location,
        );
    }

    /**
     * @param string $from
     * @param string $location
     * @return string
     */
    private static function resolveRedirect($from, $location) {
        $location = trim($location);
        if ( preg_match('~^https?://~i', $location) ) {
            return $location;
        }
        $parts = parse_url($from);
        if ( ! is_array($parts) || empty($parts['scheme']) || empty($parts['host']) ) {
            return $location;
        }
        $origin = $parts['scheme'].'://'.$parts['host'];
        if ( isset($parts['port']) ) {
            $origin .= ':'.$parts['port'];
        }
        if ( str_starts_with($location, '/') ) {
            return $origin.$location;
        }
        $path = isset($parts['path']) ? $parts['path'] : '/';
        $dir = substr($path, 0, (int) strrpos($path, '/'));
        return $origin.$dir.'/'.$location;
    }

    /**
     * @param int $contextId
     * @return array<string, mixed>
     */
    private static function contextRow($contextId) {
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT context_id, key_id FROM {$p}lti_context WHERE context_id = :context_id",
            array(':context_id' => (int) $contextId)
        );
        if ( ! is_array($row) ) {
            throw new \InvalidArgumentException('Course was not found.');
        }
        return $row;
    }

    /**
     * @return \Tsugi\Util\PDOX
     */
    private static function db() {
        global $PDOX;
        LTIX::getConnection();
        return $PDOX;
    }

    /**
     * @return string
     */
    private static function prefix() {
        global $CFG;
        return $CFG->dbprefix;
    }
}

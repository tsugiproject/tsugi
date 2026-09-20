<?php

namespace Tsugi\Services\Cartridge;

/**
 * Classify an incoming cartridge resource against existing cc_object rows.
 *
 * Duplicate is origin plus fingerprint: same resource id and type, stored
 * hash still matches, and the local object has not diverged. Identifier
 * match alone is ancestry (copy), not a skip.
 */
class Matcher {

    const NEW = 'new';
    const DUPLICATE = 'duplicate';
    const COPY = 'copy';

    const ACTION_CREATED = 'created';
    const ACTION_DUPLICATE = 'duplicate';
    const ACTION_COPY = 'copy';
    const ACTION_SKIPPED = 'skipped';
    const ACTION_ERROR = 'error';

    const STATUS_RUNNING = 'running';
    const STATUS_OK = 'ok';
    const STATUS_PARTIAL = 'partial';
    const STATUS_FAIL = 'fail';

    const TYPE_WEBCONTENT = 'webcontent';
    const TYPE_WEBLINK = 'imswl_xmlv1p1';
    const TYPE_LTI = 'imsbasiclti_xmlv1p0';
    const TYPE_QTI = 'imsqti_xmlv1p2';
    const TYPE_TOPIC = 'imsdt_xmlv1p1';

    /**
     * @param array<int, array<string, mixed>> $objects Rows already in this context
     * @param string $resourceIdentifier Incoming &lt;resource identifier&gt;
     * @param string $resourceType Incoming resource type
     * @param string $contentHash Canonical fingerprint of incoming content
     * @return array{action:string,object:?array}
     */
    public static function classify(array $objects, $resourceIdentifier, $resourceType, $contentHash) {
        $resourceIdentifier = is_string($resourceIdentifier) ? $resourceIdentifier : '';
        $resourceType = is_string($resourceType) ? $resourceType : '';
        $contentHash = is_string($contentHash) ? $contentHash : '';

        $same = array();
        foreach ( $objects as $row ) {
            if ( ! is_array($row) ) {
                continue;
            }
            $rid = isset($row['resource_identifier']) ? (string) $row['resource_identifier'] : '';
            $rtype = isset($row['resource_type']) ? (string) $row['resource_type'] : '';
            if ( $rid === $resourceIdentifier && $rtype === $resourceType ) {
                $same[] = $row;
            }
        }

        if ( count($same) < 1 ) {
            return array('action' => self::NEW, 'object' => null);
        }

        if ( $contentHash !== '' ) {
            foreach ( $same as $row ) {
                $diverged = ! empty($row['diverged']);
                $hash = isset($row['content_hash']) ? (string) $row['content_hash'] : '';
                if ( ! $diverged && $hash === $contentHash ) {
                    return array('action' => self::DUPLICATE, 'object' => $row);
                }
            }
        }

        return array('action' => self::COPY, 'object' => $same[count($same) - 1]);
    }

    /**
     * Log action written for a classify result (new becomes created).
     *
     * @param string $classifyAction
     * @return string
     */
    public static function logAction($classifyAction) {
        if ( $classifyAction === self::NEW ) {
            return self::ACTION_CREATED;
        }
        return (string) $classifyAction;
    }
}

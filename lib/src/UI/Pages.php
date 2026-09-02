<?php

namespace Tsugi\UI;

use \Tsugi\Core\LTIX;
use \Tsugi\Controllers\Courses;
use \Tsugi\Controllers\Tool;
use \Tsugi\Util\CCFileBase;
use \Tsugi\Util\U;

/**
 * Utility class for Pages functionality
 */
class Pages {

    /**
     * Get the front page text if it exists and is published
     * 
     * @param int $context_id The context ID to look for the front page
     * @return string|null The front page body text if found and published, null otherwise
     */
    public static function getFrontPageText($context_id) {
        global $CFG, $PDOX;
        
        if ( ! $context_id || $context_id < 1 ) {
            return null;
        }
        
        // Ensure we have a database connection
        if ( ! $PDOX ) {
            LTIX::getConnection();
        }
        
        // Query for front page that is published
        $row = $PDOX->rowDie(
            "SELECT body FROM {$CFG->dbprefix}pages 
             WHERE context_id = :CID AND is_front_page = 1 AND published = 1 
             LIMIT 1",
            array(':CID' => $context_id)
        );
        
        if ( $row && isset($row['body']) && U::strlen($row['body']) > 0 ) {
            return self::expandFrontPageHtml($row['body']);
        }
        
        return null;
    }

    /**
     * Expand FILEBASE tokens using the current request's course base URL.
     *
     * @param string $html
     * @return string
     */
    private static function expandFrontPageHtml($html) {
        global $CFG;
        $pathPrefix = '';
        if ( class_exists('\Tsugi\Controllers\Courses') ) {
            $pathPrefix = Courses::toolPathPrefix();
        }
        if ( $pathPrefix === '' && class_exists('\Tsugi\Controllers\Tool') ) {
            $detected = Tool::determineParentPath();
            if ( is_string($detected) && $detected !== '' ) {
                $pathPrefix = $detected;
            }
        }
        $home = '';
        if ( isset($CFG->apphome) && is_string($CFG->apphome) && trim($CFG->apphome) !== '' ) {
            $home = $CFG->apphome;
        } else if ( isset($CFG->wwwroot) && is_string($CFG->wwwroot) && trim($CFG->wwwroot) !== '' ) {
            $home = $CFG->wwwroot;
        }
        return CCFileBase::expand($html, CCFileBase::courseBaseUrl($pathPrefix, $home), Tool::courseLocalPrefixes());
    }
}

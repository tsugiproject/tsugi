<?php

namespace Tsugi\UI;

use \Tsugi\Core\LTIX;
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
            return $row['body'];
        }
        
        return null;
    }
}

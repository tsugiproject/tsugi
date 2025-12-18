<?php

use \Tsugi\Util\U;

/**
 * Generate a logical key from a title
 * 
 * Converts title to lowercase, removes punctuation, 
 * replaces sequences of spaces with single dash,
 * and limits to 99 characters
 * 
 * @param string $title The page title
 * @return string The logical key
 */
function generateLogicalKey($title) {
    // Convert to lowercase
    $key = strtolower($title);
    
    // Remove punctuation (keep alphanumeric, spaces, and dashes)
    $key = preg_replace('/[^a-z0-9\s-]/', '', $key);
    
    // Replace sequences of spaces with single dash
    $key = preg_replace('/\s+/', '-', $key);
    
    // Remove leading/trailing dashes
    $key = trim($key, '-');
    
    // Limit to 99 characters
    if ( strlen($key) > 99 ) {
        $key = substr($key, 0, 99);
        // Remove trailing dash if we cut in the middle
        $key = rtrim($key, '-');
    }
    
    // Ensure we have something
    if ( empty($key) ) {
        $key = 'page-' . time();
    }
    
    return $key;
}

<?php
/**
 * Convert lessons.json from old format to new items array format
 * 
 * Usage: php convert-lessons-to-items.php [input.json] [output.json]
 * 
 * SECURITY: This script can only be run from the command line (CLI)
 */

// Ensure this script can only run from CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Error: This script can only be run from the command line.\n");
}

if ($argc < 2) {
    echo "Usage: php convert-lessons-to-items.php [input.json] [output.json]\n";
    echo "Example: php convert-lessons-to-items.php lessons.json lessons-items.json\n";
    exit(1);
}

$input_file = $argv[1];
$output_file = isset($argv[2]) ? $argv[2] : str_replace('.json', '-items.json', $input_file);

if (!file_exists($input_file)) {
    echo "Error: Input file '$input_file' not found.\n";
    exit(1);
}

$json_str = file_get_contents($input_file);
$lessons = json_decode($json_str, true);

if ($lessons === null) {
    echo "Error: Failed to parse JSON: " . json_last_error_msg() . "\n";
    exit(1);
}

// Helper function to convert objects/arrays to arrays, preserving structure
function objectToArray($data) {
    // Use json_encode/json_decode for reliable conversion of nested structures
    if (is_object($data) || is_array($data)) {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json !== false) {
            $result = json_decode($json, true);
            if ($result !== null || json_last_error() === JSON_ERROR_NONE) {
                return $result;
            }
        }
        // Fallback: manual conversion
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        if (is_array($data)) {
            return array_map('objectToArray', $data);
        }
    }
    return $data;
}

// Convert each module
foreach($lessons['modules'] as &$module) {
    $items = array();
    
    // Add description as text item if present
    if (isset($module['description'])) {
        // Description will be rendered separately, but we could add it as text if needed
    }
    
    // Convert carousel (can be single item or array)
    if (isset($module['carousel'])) {
        $carousel_items = $module['carousel'];
        if (!is_array($carousel_items)) {
            $carousel_items = array($carousel_items);
        }
        if (count($carousel_items) > 0) {
            $items[] = array(
                'type' => 'header',
                'text' => 'Videos',
                'level' => 2
            );
            foreach($carousel_items as $video) {
                $video_arr = is_array($video) ? $video : (array)$video;
                $items[] = array_merge(array('type' => 'video'), $video_arr);
            }
        }
    }
    
    // Helper function to normalize URLs (adds {apphome}/ prefix if needed)
    $normalizeUrl = function($url) {
        if (empty($url)) return $url;
        // If URL doesn't start with http://, https://, or /
        if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0 && strpos($url, '/') !== 0) {
            return '{apphome}/' . $url;
        }
        return $url;
    };
    
    // Convert slides (can be string, single object, or array)
    if (isset($module['slides'])) {
        $has_slides = false;
        if (is_string($module['slides'])) {
            // Single string slide
            $has_slides = true;
            $slide_url = $normalizeUrl($module['slides']);
            $items[] = array(
                'type' => 'header',
                'text' => 'Slides',
                'level' => 2
            );
            $items[] = array(
                'type' => 'slide',
                'title' => basename($module['slides']),
                'href' => $slide_url
            );
        } else if (is_array($module['slides']) && count($module['slides']) > 0) {
            // Array of slides
            $has_slides = true;
            $items[] = array(
                'type' => 'header',
                'text' => 'Slides',
                'level' => 2
            );
            foreach($module['slides'] as $slide) {
                if (is_string($slide)) {
                    $slide_url = $normalizeUrl($slide);
                    $items[] = array(
                        'type' => 'slide',
                        'title' => basename($slide),
                        'href' => $slide_url
                    );
                } else {
                    $slide_arr = is_array($slide) ? $slide : (array)$slide;
                    // Normalize href or url if present
                    if (isset($slide_arr['href'])) {
                        $slide_arr['href'] = $normalizeUrl($slide_arr['href']);
                    }
                    if (isset($slide_arr['url'])) {
                        $slide_arr['url'] = $normalizeUrl($slide_arr['url']);
                    }
                    $items[] = array_merge(array('type' => 'slide'), $slide_arr);
                }
            }
        } else if (!is_array($module['slides'])) {
            // Single object slide
            $has_slides = true;
            $items[] = array(
                'type' => 'header',
                'text' => 'Slides',
                'level' => 2
            );
            $slide_obj = (array)$module['slides'];
            // Normalize href or url if present
            if (isset($slide_obj['href'])) {
                $slide_obj['href'] = $normalizeUrl($slide_obj['href']);
            }
            if (isset($slide_obj['url'])) {
                $slide_obj['url'] = $normalizeUrl($slide_obj['url']);
            }
            $items[] = array_merge(array('type' => 'slide'), $slide_obj);
        }
    }
    
    // Convert videos (can be single item or array)
    if (isset($module['videos'])) {
        $videos = $module['videos'];
        if (!is_array($videos)) {
            $videos = array($videos);
        }
        if (count($videos) > 0) {
            $items[] = array(
                'type' => 'header',
                'text' => 'Videos',
                'level' => 2
            );
            foreach($videos as $video) {
                $video_arr = is_array($video) ? $video : (array)$video;
                $items[] = array_merge(array('type' => 'video'), $video_arr);
            }
        }
    }
    
    // Convert references (can be single item or array)
    if (isset($module['references'])) {
        $references = $module['references'];
        if (!is_array($references)) {
            $references = array($references);
        }
        if (count($references) > 0) {
            $items[] = array(
                'type' => 'header',
                'text' => 'References',
                'level' => 2
            );
            foreach($references as $ref) {
                $ref_arr = is_array($ref) ? $ref : (array)$ref;
                // Normalize href or url if present
                if (isset($ref_arr['href'])) {
                    $ref_arr['href'] = $normalizeUrl($ref_arr['href']);
                }
                if (isset($ref_arr['url'])) {
                    $ref_arr['url'] = $normalizeUrl($ref_arr['url']);
                }
                $items[] = array_merge(array('type' => 'reference'), $ref_arr);
            }
        }
    }
    
    // Convert chapters
    if (isset($module['chapters'])) {
        $items[] = array(
            'type' => 'chapters',
            'chapters' => $module['chapters']
        );
    }
    
    // Convert assignment
    if (isset($module['assignment'])) {
        $assignment_url = $normalizeUrl($module['assignment']);
        $items[] = array(
            'type' => 'header',
            'text' => 'Assignment',
            'level' => 2
        );
        $items[] = array(
            'type' => 'assignment',
            'href' => $assignment_url
        );
    }
    
    // Convert solution
    if (isset($module['solution'])) {
        $solution_url = $normalizeUrl($module['solution']);
        $items[] = array(
            'type' => 'header',
            'text' => 'Solution',
            'level' => 2
        );
        $items[] = array(
            'type' => 'solution',
            'href' => $solution_url
        );
    }
    
    // Convert discussions (can be single item or array)
    if (isset($module['discussions'])) {
        $discussions = $module['discussions'];
        if (!is_array($discussions)) {
            $discussions = array($discussions);
        }
        if (count($discussions) > 0) {
            $items[] = array(
                'type' => 'header',
                'text' => 'Discussions',
                'level' => 2
            );
            foreach($discussions as $disc) {
                $disc_arr = is_array($disc) ? $disc : (array)$disc;
                $items[] = array_merge(array('type' => 'discussion'), $disc_arr);
            }
        }
    }
    
    // Convert lti (can be single object or array)
    if (isset($module['lti'])) {
        $ltis_raw = $module['lti'];
        // Normalize to array of LTI items
        // Check if it's already an array of LTIs (numeric keys) or a single LTI object (associative array)
        if (is_array($ltis_raw)) {
            // Check if it's a numeric array (multiple LTIs) or associative array (single LTI)
            $is_numeric_array = isset($ltis_raw[0]) && is_numeric(key($ltis_raw));
            if ($is_numeric_array) {
                // Already an array of LTIs
                $ltis = $ltis_raw;
            } else {
                // Single LTI object (associative array) - wrap it
                $ltis = array($ltis_raw);
            }
        } else {
            // Not an array - wrap it
            $ltis = array($ltis_raw);
        }
        
        if (count($ltis) > 0) {
            $items[] = array(
                'type' => 'header',
                'text' => 'Tools',
                'level' => 2
            );
            foreach($ltis as $lti) {
                // Preserve the entire LTI structure intact - just add type field
                if (is_array($lti)) {
                    // Already an array (from json_decode with true), create new array with type first
                    $lti_item = array('type' => 'lti');
                    // Copy all fields from LTI array
                    foreach($lti as $key => $value) {
                        $lti_item[$key] = $value;
                    }
                    $items[] = $lti_item;
                } else if (is_object($lti)) {
                    // Object - convert via json_encode/json_decode to preserve nested structures
                    $lti_json = json_encode($lti, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    if ($lti_json === false) {
                        echo("Warning: Failed to encode LTI object: " . json_last_error_msg() . "\n");
                        continue;
                    }
                    $lti_arr = json_decode($lti_json, true);
                    if ($lti_arr === null && json_last_error() !== JSON_ERROR_NONE) {
                        echo("Warning: Failed to decode LTI JSON: " . json_last_error_msg() . "\n");
                        continue;
                    }
                    if (is_array($lti_arr)) {
                        $lti_item = array('type' => 'lti');
                        foreach($lti_arr as $key => $value) {
                            $lti_item[$key] = $value;
                        }
                        $items[] = $lti_item;
                    } else {
                        echo("Warning: LTI decoded to non-array type: " . gettype($lti_arr) . "\n");
                    }
                } else {
                    // Unexpected type - skip
                    echo("Warning: Unexpected LTI type: " . gettype($lti) . " - skipping\n");
                }
            }
        }
    }
    
    // Add items array to module
    $module['items'] = $items;
    
    // Optionally remove old arrays (commented out for safety)
    // unset($module['videos']);
    // unset($module['references']);
    // unset($module['discussions']);
    // unset($module['lti']);
    // unset($module['slides']);
    // unset($module['carousel']);
    // unset($module['assignment']);
    // unset($module['solution']);
    // unset($module['chapters']);
}

// Write output
$output_json = json_encode($lessons, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents($output_file, $output_json);

echo "Conversion complete!\n";
echo "Input:  $input_file\n";
echo "Output: $output_file\n";
echo "\nNote: Old arrays are preserved in the output for backward compatibility.\n";
echo "You can manually remove them after verifying the new format works.\n";

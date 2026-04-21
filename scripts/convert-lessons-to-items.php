<?php
/**
 * Convert lessons.json from old format to new items array format
 * 
 * Usage: php convert-lessons-to-items.php [input.json] [output.json] [keep]
 *
 * By default, legacy per-module arrays (slides, lectures, videos, etc.) are removed
 * after conversion so only `items` remains. Pass keep as a third argument to retain them.
 * 
 * SECURITY: This script can only be run from the command line (CLI)
 */

// Ensure this script can only run from CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Error: This script can only be run from the command line.\n");
}

if ($argc < 2) {
    echo "Usage: php tsugi/scripts/convert-lessons-to-items.php [input.json] [output.json] [sections...] [keep]\n";
    echo "Example: php tsugi/scripts/convert-lessons-to-items.php lessons.json lessons-items.json\n";
    echo "         php tsugi/scripts/convert-lessons-to-items.php lessons.json out.json keep\n";
    echo "         php tsugi/scripts/convert-lessons-to-items.php lessons.json out.json lectures form\n";
    echo "  sections: optional list (slides lectures videos references discussions lti assignment solution chapters form carousel)\n";
    echo "  keep: keep legacy arrays/fields after building items\n";
    exit(1);
}

$input_file = $argv[1];
$output_file = isset($argv[2]) ? $argv[2] : str_replace('.json', '-items.json', $input_file);
$raw_args = array_slice($argv, 3);
$keep_legacy = false;
$requested_sections = array();
$section_aliases = array(
    'carousel' => 'carousel',
    'slide' => 'slides',
    'slides' => 'slides',
    'lecture' => 'lectures',
    'lectures' => 'lectures',
    'video' => 'videos',
    'videos' => 'videos',
    'reference' => 'references',
    'references' => 'references',
    'discussion' => 'discussions',
    'discussions' => 'discussions',
    'tool' => 'lti',
    'tools' => 'lti',
    'lti' => 'lti',
    'assignment' => 'assignment',
    'solution' => 'solution',
    'chapter' => 'chapters',
    'chapters' => 'chapters',
    'form' => 'form',
    'forms' => 'form',
);
foreach ($raw_args as $arg) {
    $arg = strtolower(trim($arg));
    if ($arg === '') continue;
    if ($arg === 'keep') {
        $keep_legacy = true;
        continue;
    }
    if (isset($section_aliases[$arg])) {
        $requested_sections[$section_aliases[$arg]] = true;
    } else {
        echo "Warning: Unknown section '$arg' ignored.\n";
    }
}
$convert_all_sections = (count($requested_sections) === 0);
$shouldConvert = function($section_name) use ($convert_all_sections, $requested_sections) {
    return $convert_all_sections || isset($requested_sections[$section_name]);
};

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
    $converted_legacy_keys = array();
    
    // Add description as text item if present
    if (isset($module['description'])) {
        // Description will be rendered separately, but we could add it as text if needed
    }
    
    // Convert carousel (can be single item or array)
    if ($shouldConvert('carousel') && isset($module['carousel']) && !empty($module['carousel'])) {
        $carousel_items = $module['carousel'];
        if (!is_array($carousel_items)) {
            $carousel_items = array($carousel_items);
        }
        $carousel_video_items = array();
        foreach($carousel_items as $video) {
            if (!empty($video)) {
                $video_arr = is_array($video) ? $video : (array)$video;
                // Must have at least youtube, media, or title to be valid
                if (isset($video_arr['youtube']) && !empty($video_arr['youtube']) ||
                    isset($video_arr['media']) && !empty($video_arr['media']) ||
                    isset($video_arr['title']) && !empty($video_arr['title'])) {
                    $carousel_video_items[] = array_merge(array('type' => 'video'), $video_arr);
                }
            }
        }
        if (count($carousel_video_items) > 0) {
            $converted_legacy_keys['carousel'] = true;
            $items[] = array(
                'type' => 'header',
                'text' => 'Videos',
                'level' => 2
            );
            $items = array_merge($items, $carousel_video_items);
        }
    }
    
    // Helper: add {apphome}/ for relative paths only — do not double-prefix Tsugi macros or absolute URLs
    $normalizeUrl = function($url) {
        if ($url === null || $url === '') {
            return $url;
        }
        if (!is_string($url)) {
            return $url;
        }
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0 || strpos($url, '/') === 0) {
            return $url;
        }
        // Already uses expandLink macros (common in lessons.json)
        if (strpos($url, '{apphome}') === 0 || strpos($url, '{wwwroot}') === 0) {
            return $url;
        }
        return '{apphome}/' . $url;
    };
    
    // Convert slides (can be string, single object, or array)
    if ($shouldConvert('slides') && isset($module['slides']) && !empty($module['slides'])) {
        $slide_items = array();
        if (is_string($module['slides']) && !empty($module['slides'])) {
            // Single string slide
            $slide_url = $normalizeUrl($module['slides']);
            $slide_items[] = array(
                'type' => 'slide',
                'title' => basename($module['slides']),
                'href' => $slide_url
            );
        } else if (is_array($module['slides']) && count($module['slides']) > 0) {
            // Array of slides
            foreach($module['slides'] as $slide) {
                if (is_string($slide) && !empty($slide)) {
                    $slide_url = $normalizeUrl($slide);
                    $slide_items[] = array(
                        'type' => 'slide',
                        'title' => basename($slide),
                        'href' => $slide_url
                    );
                } else if (!is_array($slide) && !empty($slide)) {
                    // Single object slide
                    $slide_arr = (array)$slide;
                    // Must have href or url to be valid
                    if (isset($slide_arr['href']) && !empty($slide_arr['href'])) {
                        $slide_arr['href'] = $normalizeUrl($slide_arr['href']);
                        $slide_items[] = array_merge(array('type' => 'slide'), $slide_arr);
                    } else if (isset($slide_arr['url']) && !empty($slide_arr['url'])) {
                        $slide_arr['url'] = $normalizeUrl($slide_arr['url']);
                        $slide_items[] = array_merge(array('type' => 'slide'), $slide_arr);
                    }
                } else if (is_array($slide) && !empty($slide)) {
                    // Array slide object - must have href or url to be valid
                    if (isset($slide['href']) && !empty($slide['href'])) {
                        $slide['href'] = $normalizeUrl($slide['href']);
                        $slide_items[] = array_merge(array('type' => 'slide'), $slide);
                    } else if (isset($slide['url']) && !empty($slide['url'])) {
                        $slide['url'] = $normalizeUrl($slide['url']);
                        $slide_items[] = array_merge(array('type' => 'slide'), $slide);
                    }
                }
            }
        } else if (!is_array($module['slides']) && !empty($module['slides'])) {
            // Single object slide
            $slide_obj = (array)$module['slides'];
            // Must have href or url to be valid
            if (isset($slide_obj['href']) && !empty($slide_obj['href'])) {
                $slide_obj['href'] = $normalizeUrl($slide_obj['href']);
                $slide_items[] = array_merge(array('type' => 'slide'), $slide_obj);
            } else if (isset($slide_obj['url']) && !empty($slide_obj['url'])) {
                $slide_obj['url'] = $normalizeUrl($slide_obj['url']);
                $slide_items[] = array_merge(array('type' => 'slide'), $slide_obj);
            }
        }
        
        // Only add header if we have slide items
        if (count($slide_items) > 0) {
            $converted_legacy_keys['slides'] = true;
            $items[] = array(
                'type' => 'header',
                'text' => 'Slides',
                'level' => 2
            );
            $items = array_merge($items, $slide_items);
        }
    }
    
    // Convert videos (can be single item or array)
    if ($shouldConvert('videos') && isset($module['videos']) && !empty($module['videos'])) {
        $videos = $module['videos'];
        if (!is_array($videos)) {
            $videos = array($videos);
        }
        $video_items = array();
        foreach($videos as $video) {
            if (!empty($video)) {
                $video_arr = is_array($video) ? $video : (array)$video;
                // Must have at least youtube, media, or title to be valid
                if (isset($video_arr['youtube']) && !empty($video_arr['youtube']) ||
                    isset($video_arr['media']) && !empty($video_arr['media']) ||
                    isset($video_arr['title']) && !empty($video_arr['title'])) {
                    $video_items[] = array_merge(array('type' => 'video'), $video_arr);
                }
            }
        }
        if (count($video_items) > 0) {
            $converted_legacy_keys['videos'] = true;
            $items[] = array(
                'type' => 'header',
                'text' => 'Videos',
                'level' => 2
            );
            $items = array_merge($items, $video_items);
        }
    }

    // Convert lectures (legacy key used in many course files)
    if ($shouldConvert('lectures') && isset($module['lectures']) && !empty($module['lectures'])) {
        $lectures = $module['lectures'];
        if (!is_array($lectures)) {
            $lectures = array($lectures);
        }
        $lecture_items = array();
        foreach ($lectures as $lecture) {
            if (!empty($lecture)) {
                $lecture_arr = is_array($lecture) ? $lecture : (array)$lecture;
                if (isset($lecture_arr['youtube']) && !empty($lecture_arr['youtube']) ||
                    isset($lecture_arr['media']) && !empty($lecture_arr['media']) ||
                    isset($lecture_arr['title']) && !empty($lecture_arr['title'])) {
                    $lecture_items[] = array_merge(array('type' => 'video'), $lecture_arr);
                }
            }
        }
        if (count($lecture_items) > 0) {
            $converted_legacy_keys['lectures'] = true;
            $items[] = array(
                'type' => 'header',
                'text' => 'Videos',
                'level' => 2
            );
            $items = array_merge($items, $lecture_items);
        }
    }
    
    // Convert references (can be single item or array)
    if ($shouldConvert('references') && isset($module['references']) && !empty($module['references'])) {
        $references = $module['references'];
        if (!is_array($references)) {
            $references = array($references);
        }
        $ref_items = array();
        foreach($references as $ref) {
            if (!empty($ref)) {
                $ref_arr = is_array($ref) ? $ref : (array)$ref;
                // Must have href to be valid reference
                if (isset($ref_arr['href']) && !empty($ref_arr['href'])) {
                    $ref_arr['href'] = $normalizeUrl($ref_arr['href']);
                    $ref_items[] = array_merge(array('type' => 'reference'), $ref_arr);
                } else if (isset($ref_arr['url']) && !empty($ref_arr['url'])) {
                    $ref_arr['url'] = $normalizeUrl($ref_arr['url']);
                    $ref_items[] = array_merge(array('type' => 'reference'), $ref_arr);
                }
            }
        }
        if (count($ref_items) > 0) {
            $converted_legacy_keys['references'] = true;
            $items[] = array(
                'type' => 'header',
                'text' => 'References',
                'level' => 2
            );
            $items = array_merge($items, $ref_items);
        }
    }
    
    // Convert chapters
    if ($shouldConvert('chapters') && isset($module['chapters'])) {
        $converted_legacy_keys['chapters'] = true;
        $items[] = array(
            'type' => 'chapters',
            'chapters' => $module['chapters']
        );
    }
    
    // Convert assignment
    if ($shouldConvert('assignment') && isset($module['assignment']) && !empty($module['assignment'])) {
        $converted_legacy_keys['assignment'] = true;
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
    if ($shouldConvert('solution') && isset($module['solution']) && !empty($module['solution'])) {
        $converted_legacy_keys['solution'] = true;
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
    if ($shouldConvert('discussions') && isset($module['discussions']) && !empty($module['discussions'])) {
        $discussions = $module['discussions'];
        if (!is_array($discussions)) {
            $discussions = array($discussions);
        }
        $disc_items = array();
        foreach($discussions as $disc) {
            if (!empty($disc)) {
                $disc_arr = is_array($disc) ? $disc : (array)$disc;
                // Must have launch or resource_link_id to be valid
                if ((isset($disc_arr['launch']) && !empty($disc_arr['launch'])) ||
                    (isset($disc_arr['resource_link_id']) && !empty($disc_arr['resource_link_id']))) {
                    $disc_items[] = array_merge(array('type' => 'discussion'), $disc_arr);
                }
            }
        }
        if (count($disc_items) > 0) {
            $converted_legacy_keys['discussions'] = true;
            $items[] = array(
                'type' => 'header',
                'text' => 'Discussions',
                'level' => 2
            );
            $items = array_merge($items, $disc_items);
        }
    }
    
    // Convert lti (can be single object or array)
    if ($shouldConvert('lti') && isset($module['lti']) && !empty($module['lti'])) {
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
        
        $lti_items = array();
        foreach($ltis as $lti) {
            if (empty($lti)) continue;
            // Preserve the entire LTI structure intact - just add type field
            if (is_array($lti)) {
                // Must have launch or resource_link_id to be valid
                if ((isset($lti['launch']) && !empty($lti['launch'])) ||
                    (isset($lti['resource_link_id']) && !empty($lti['resource_link_id']))) {
                    // Already an array (from json_decode with true), create new array with type first
                    $lti_item = array('type' => 'lti');
                    // Copy all fields from LTI array
                    foreach($lti as $key => $value) {
                        $lti_item[$key] = $value;
                    }
                    $lti_items[] = $lti_item;
                }
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
                    // Must have launch or resource_link_id to be valid
                    if ((isset($lti_arr['launch']) && !empty($lti_arr['launch'])) ||
                        (isset($lti_arr['resource_link_id']) && !empty($lti_arr['resource_link_id']))) {
                        $lti_item = array('type' => 'lti');
                        foreach($lti_arr as $key => $value) {
                            $lti_item[$key] = $value;
                        }
                        $lti_items[] = $lti_item;
                    }
                } else {
                    echo("Warning: LTI decoded to non-array type: " . gettype($lti_arr) . "\n");
                }
            } else {
                // Unexpected type - skip
                echo("Warning: Unexpected LTI type: " . gettype($lti) . " - skipping\n");
            }
        }
        
        if (count($lti_items) > 0) {
            $converted_legacy_keys['lti'] = true;
            $items[] = array(
                'type' => 'header',
                'text' => 'Tools',
                'level' => 2
            );
            $items = array_merge($items, $lti_items);
        }
    }

    // Convert form payloads to item entries, preserving all fields
    if ($shouldConvert('form') && isset($module['form']) && !empty($module['form'])) {
        $forms_raw = $module['form'];
        if (!is_array($forms_raw)) {
            $forms = array($forms_raw);
        } else {
            $is_list = array_keys($forms_raw) === range(0, count($forms_raw) - 1);
            $forms = $is_list ? $forms_raw : array($forms_raw);
        }
        $form_items = array();
        foreach ($forms as $form) {
            if (empty($form)) continue;
            $form_arr = is_array($form) ? $form : (array)$form;
            if (isset($form_arr['href']) && !empty($form_arr['href'])) {
                $form_arr['href'] = $normalizeUrl($form_arr['href']);
            }
            if (isset($form_arr['url']) && !empty($form_arr['url'])) {
                $form_arr['url'] = $normalizeUrl($form_arr['url']);
            }
            $form_items[] = array_merge(array('type' => 'form'), $form_arr);
        }
        if (count($form_items) > 0) {
            $converted_legacy_keys['form'] = true;
            $items[] = array(
                'type' => 'header',
                'text' => 'Forms',
                'level' => 2
            );
            $items = array_merge($items, $form_items);
        }
    }
    
    // Add items array to module
    $module['items'] = $items;

    if (!$keep_legacy) {
        if ($convert_all_sections) {
            $legacy_keys = array(
                'carousel',
                'slides',
                'videos',
                'lectures',
                'references',
                'discussions',
                'lti',
                'assignment',
                'solution',
                'chapters',
                'form',
            );
        } else {
            $legacy_keys = array_keys($converted_legacy_keys);
        }
        foreach ($legacy_keys as $key) {
            unset($module[$key]);
        }
    }
}

// Write output
$output_json = json_encode($lessons, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents($output_file, $output_json);

echo "Conversion complete!\n";
echo "Input:  $input_file\n";
echo "Output: $output_file\n";
if ($keep_legacy) {
    echo "Legacy arrays: kept (keep flag).\n";
} else {
    echo "Legacy arrays: removed from each module (only items + module metadata remain).\n";
}

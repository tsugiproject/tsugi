<?php


namespace Tsugi\Util;

/**
 * Utility class for generating deterministic, hash-based identifiers for IMS Common Cartridge exports.
 * 
 * This ensures idempotency: the same logical content always produces the same identifier,
 * regardless of ordering changes or later additions.
 * 
 * Usage:
 *     $idgen = new CCIdentifier();
 *     $identifier = $idgen->makeIdentifier('module', 'Week 1', '');
 *     $identifier = $idgen->makeIdentifier('lti', 'Assignment 1', 'Week 1', ['url' => 'https://...']);
 */
class CCIdentifier {

    /**
     * Set of identifiers already generated in this export run (for collision detection)
     * @var array
     */
    private $existingIds = array();

    /**
     * Length of hash to use (truncated from SHA1)
     * @var int
     */
    private $hashLength = 16;

    /**
     * Prefix for different types of identifiers
     * @var array
     */
    private $typePrefixes = array(
        'module' => 'M',
        'submodule' => 'S',
        'weblink' => 'WL',
        'lti' => 'LT',
        'topic' => 'TO',
        'header' => 'H'
    );

    /**
     * Generate a deterministic identifier based on stable properties.
     * 
     * @param string $type The type of item (module, submodule, weblink, lti, topic, header)
     * @param string $title The title/name of the item
     * @param string $parentPath The path of parent modules (e.g., "Week 1|Part 1")
     * @param array $additionalProps Additional stable properties (e.g., ['url' => '...', 'resource_link_id' => '...'])
     * @return string A deterministic identifier (e.g., "M_a1b2c3d4e5f6g7h8")
     */
    public function makeIdentifier($type, $title, $parentPath = '', $additionalProps = array()) {
        // Build canonical identity string from stable properties
        $identityParts = array(
            'type' => $type,
            'title' => $this->normalizeString($title),
            'parent' => $this->normalizeString($parentPath)
        );

        // Add additional stable properties if provided
        if (!empty($additionalProps)) {
            // Sort keys for consistent ordering
            ksort($additionalProps);
            foreach ($additionalProps as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $identityParts[$key] = $this->normalizeString((string)$value);
                } elseif (is_array($value)) {
                    // For arrays, create a stable representation
                    ksort($value);
                    $identityParts[$key] = json_encode($value, JSON_UNESCAPED_SLASHES);
                }
            }
        }

        // Create canonical string representation
        $canonical = '';
        foreach ($identityParts as $key => $value) {
            $canonical .= $key . '|' . $value . '|';
        }

        // Generate SHA1 hash and truncate
        $hash = hash('sha1', $canonical);
        $shortHash = substr($hash, 0, $this->hashLength);

        // Get prefix for this type
        $prefix = isset($this->typePrefixes[$type]) ? $this->typePrefixes[$type] : 'ID';

        // Build base identifier
        $baseId = $prefix . '_' . $shortHash;

        // Handle collisions within this export run
        $identifier = $baseId;
        $counter = 0;
        while (isset($this->existingIds[$identifier])) {
            $counter++;
            $identifier = $baseId . '_' . $counter;
        }

        // Register this identifier
        $this->existingIds[$identifier] = true;

        return $identifier;
    }

    /**
     * Normalize a string for consistent hashing.
     * Removes extra whitespace, converts to lowercase, and trims.
     * 
     * @param string $str The string to normalize
     * @return string Normalized string
     */
    private function normalizeString($str) {
        if (!is_string($str)) {
            $str = (string)$str;
        }
        // Trim, lowercase, and collapse whitespace
        $str = trim($str);
        $str = mb_strtolower($str, 'UTF-8');
        $str = preg_replace('/\s+/', ' ', $str);
        return $str;
    }

    /**
     * Reset the collision tracking (useful for testing or if starting a new export)
     */
    public function reset() {
        $this->existingIds = array();
    }

    /**
     * Get the number of identifiers generated so far
     * 
     * @return int
     */
    public function getCount() {
        return count($this->existingIds);
    }
}


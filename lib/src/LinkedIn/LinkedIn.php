<?php
declare(strict_types=1);

namespace Tsugi\LinkedIn;

/**
 * LinkedIn integration helpers for "Add to profile" (Licenses & Certifications).
 *
 * Key rule (learned the hard way):
 *  - Use organizationId (numeric) to get the LinkedIn Company Page logo.
 *  - Do NOT use organizationName in the add URL, or you risk the generic icon.
 *  - Falls back to organizationName if organizationId is not available.
 */
class LinkedIn
{
    public const DEFAULT_START_TASK = 'CERTIFICATION_NAME';

    private ?int $organizationId;
    private ?string $organizationName;

    /**
     * Create LinkedIn helper instance.
     * 
     * @param int|null $organizationId LinkedIn organization ID (preferred)
     * @param string|null $organizationName Organization name (fallback if organizationId not set)
     */
    public function __construct(?int $organizationId = null, ?string $organizationName = null)
    {
        if ($organizationId !== null && $organizationId <= 0) {
            throw new \InvalidArgumentException('organizationId must be a positive integer or null');
        }
        $this->organizationId = $organizationId;
        $this->organizationName = $organizationName;
    }

    /**
     * Create LinkedIn helper from CFG configuration.
     * 
     * @param object $CFG Configuration object with linkedin_organization_id and badge_organization
     * @return self
     */
    public static function fromConfig($CFG): self
    {
        $organizationId = null;
        $organizationName = null;
        
        if (isset($CFG->linkedin_organization_id) && !empty($CFG->linkedin_organization_id)) {
            $organizationId = is_numeric($CFG->linkedin_organization_id) 
                ? (int)$CFG->linkedin_organization_id 
                : null;
        }
        
        if (isset($CFG->badge_organization) && !empty($CFG->badge_organization)) {
            $organizationName = $CFG->badge_organization;
        }
        
        return new self($organizationId, $organizationName);
    }

    /**
     * Build a LinkedIn "Add certification" URL.
     *
     * @param string      $name       Certification name (badge title)
     * @param string      $certUrl    Public credential URL (your HTML credential page)
     * @param string|null $certId     Credential ID (e.g., "3f022") - optional
     * @param int|null    $issueYear  4-digit year - optional
     * @param int|null    $issueMonth 1-12 - optional
     * @param string|null $startTask  Usually CERTIFICATION_NAME (optional)
     * @return string|null Returns URL if organization info is available, null otherwise
     */
    public function buildAddCertificationUrl(
        string $name,
        string $certUrl,
        ?string $certId = null,
        ?int $issueYear = null,
        ?int $issueMonth = null,
        ?string $startTask = self::DEFAULT_START_TASK
    ): ?string {
        // Must have either organizationId or organizationName
        if ($this->organizationId === null && empty($this->organizationName)) {
            return null;
        }

        $params = [
            'startTask' => $startTask ?? self::DEFAULT_START_TASK,
            'name'      => $name,
            'certUrl'   => $certUrl,
        ];

        // Prefer organizationId over organizationName
        if ($this->organizationId !== null) {
            $params['organizationId'] = (string) $this->organizationId;
        } elseif (!empty($this->organizationName)) {
            $params['organizationName'] = $this->organizationName;
        }

        // Add optional parameters if provided
        if ($certId !== null && $certId !== '') {
            $params['certId'] = $certId;
        }

        if ($issueYear !== null) {
            $params['issueYear'] = (string) $issueYear;
        }

        if ($issueMonth !== null) {
            $issueMonth = $this->clamp($issueMonth, 1, 12);
            $params['issueMonth'] = str_pad((string) $issueMonth, 2, '0', STR_PAD_LEFT);
        }

        return 'https://www.linkedin.com/profile/add?' . $this->buildQuery($params);
    }

    /**
     * Produce HTML <link> tags that help crawlers discover the OB2 assertion JSON.
     * Returns the tag strings (already HTML-escaped) so you can echo them in <head>.
     *
     * @param string $assertionJsonUrl e.g. https://.../assertions/<token>.json
     * @param string $canonicalUrl     e.g. https://.../assertions/<token>
     */
    public function buildOb2HeadLinkTags(string $assertionJsonUrl, string $canonicalUrl): array
    {
        $assertionJsonUrl = $this->escapeAttr($assertionJsonUrl);
        $canonicalUrl     = $this->escapeAttr($canonicalUrl);

        return [
            '<link rel="alternate" type="application/ld+json" href="'.$assertionJsonUrl.'">',
            '<link rel="badge" href="'.$assertionJsonUrl.'">',
            '<link rel="canonical" href="'.$canonicalUrl.'">',
        ];
    }

    /**
     * Organization ID getter (sometimes useful for display/debugging).
     */
    public function getOrganizationId(): ?int
    {
        return $this->organizationId;
    }

    /**
     * Organization name getter (fallback value).
     */
    public function getOrganizationName(): ?string
    {
        return $this->organizationName;
    }

    // ---- internals ----

    /**
     * RFC3986 encoding (spaces => %20). Plays nicest with strict parsers.
     */
    private function buildQuery(array $params): string
    {
        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    private function escapeAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}

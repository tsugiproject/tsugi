# Open Badges 2.0 / 3.0 Assertions

This folder implements Open Badges 2.0 (OB2) and Open Badges 3.0 / Verifiable Credentials (OB3) support, reusing the same evidence structure as the legacy OB1 badges in the `/badges` folder.

## Overview

The assertions system provides modern badge formats while maintaining compatibility with existing OB1 badges. All assertions use the same evidence (`$CFG->apphome`) as the OB1 implementation. This is a **storage-free** approach - no database tables needed, just like the `/badges` folder.

## Endpoints

### OB2 Assertion
- **URL**: `/assertions/{encrypted-id}.json`
- **Format**: Open Badges 2.0 JSON-LD
- **Context**: `https://purl.imsglobal.org/spec/ob/v2p1/context.json`
- **Content-Type**: `application/json`
- **Note**: Default format when Accept header is missing or doesn't request OB3

### OB3/VC Assertion
- **URL**: `/assertions/{encrypted-id}.json` (with `Accept: application/vc+json` header)
- **URL (explicit)**: `/assertions/{encrypted-id}.vc.json`
- **Format**: Open Badges 3.0 Verifiable Credential JSON-LD
- **Context**: `https://www.w3.org/ns/credentials/v2` + `https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.3.json`
- **Content-Type**: `application/vc+json`
- **Note**: Served when Accept header requests `application/vc+json` or `application/vc+ld+json`

### Badge Class (OB2)
- **URL**: `/assertions/{encrypted-id}/badge.json`
- **Format**: OB2 BadgeClass JSON-LD

### Issuer Profile
- **URL**: `/assertions/{encrypted-id}/issuer.json`
- **Format**: OB2 Issuer JSON-LD (default)
- **OB3 Format**: `/assertions/{encrypted-id}/issuer.json?format=ob3`

### Achievement (OB3)
- **URL**: `/assertions/{encrypted-id}/achievement.json`
- **Format**: OB3 Achievement JSON-LD

### HTML Display Page
- **URL**: `/assertions/{encrypted-id}`
- **Format**: Human-readable HTML page with links to all formats

## Usage

To access an assertion, use the same **encrypted ID** from the OB1 badge system, but put it directly in the URL path.

### Example

Given an OB1 assertion URL:
```
/badges/assert.php?id=abc123def456...
```

You can access:
- OB2: `/assertions/abc123def456....json`
- OB3: `/assertions/abc123def456....vc.json`
- Badge Class: `/assertions/abc123def456.../badge.json`
- Issuer: `/assertions/abc123def456.../issuer.json`
- Achievement: `/assertions/abc123def456.../achievement.json`
- HTML: `/assertions/abc123def456...`

## Evidence

All assertions use the same evidence structure as OB1:
- **Evidence URL**: `$CFG->apphome`
- **Evidence Type**: Evidence object with narrative describing the achievement

## Legacy Support

The assertions include optional references to legacy OB1 badges when `$CFG->badge_include_legacy` is enabled:
- OB2: `extensions.legacyAssertion`
- OB3: `credentialSubject.extensions.legacyAssertion`

## Files

- `route.php` - Main routing handler for all assertion endpoints
- `assertion-util.php` - Utility functions for generating OB2/OB3 JSON
- `.htaccess` - URL rewriting configuration

## Differences from OB1

1. **Modern Contexts**: Uses OB2/OB3 JSON-LD contexts
2. **Structured Evidence**: Evidence is an array of Evidence objects (OB2) or embedded in credentialSubject (OB3)
3. **Verifiable Credentials**: OB3 supports VC format for future-proofing
4. **No Baking**: Assertions are served as JSON, not baked into PNG images
5. **Same URL Pattern**: Uses the same encrypted ID parameter approach as OB1 badges

## Testing

Validate OB2 badges using:
- IMS Global Open Badges 2.0 Validator
- Test import into Credly for LinkedIn sharing

OB3 badges can be used with Verifiable Credentials platforms and newer badge systems.

# Open Badges 2.0 / 3.0 Assertions

This folder implements Open Badges 2.0 (OB2) and Open Badges 3.0 / Verifiable Credentials (OB3) support, reusing the same evidence structure as the legacy OB1 badges in the `/badges` folder.

## Overview

The assertions system provides modern badge formats while maintaining compatibility with existing OB1 badges. All assertions use the same evidence (`$CFG->apphome`) as the OB1 implementation. This is a **storage-free** approach - no database tables needed, just like the `/badges` folder.

## Configuration

To enable badge assertions, configure the following values in your `config.php`:

### Required Configuration

1. **Badge Encryption Password**:
   ```php
   $CFG->badge_encrypt_password = "somethinglongwithhex387438758974987";
   ```
   Used to encrypt/decrypt badge assertion IDs. **Do not change** once badges have been issued.

2. **Badge Assertion Salt**:
   ```php
   $CFG->badge_assert_salt = "mediumlengthhexstring";
   ```
   Used for hashing recipient email addresses in assertions. **Do not change** once badges have been issued.

3. **Badge Image Path** (file system):
   ```php
   $CFG->badge_path = $CFG->dirroot . '/../bimages';
   ```
   File system path where badge PNG images are stored.

4. **Badge Image URL** (web accessible):
   ```php
   $CFG->badge_url = $CFG->apphome . '/bimages';
   ```
   Public URL where badge images can be accessed via HTTP/HTTPS.

5. **Lessons Configuration**:
   ```php
   $CFG->lessons = $CFG->dirroot . '/../lessons.json';
   ```
   Path to your `lessons.json` file that defines badges and their metadata.

### Optional Configuration

1. **Badge Organization Name** (for issuer and LinkedIn):
   ```php
   $CFG->badge_organization = "Your Organization Name";
   ```
   Organization name used in issuer assertions and LinkedIn badge integration. If not set, defaults to `"$CFG->servicedesc ($CFG->servicename)"` or just `$CFG->servicename`.

2. **Badge Issuer Email**:
   ```php
   $CFG->badge_issuer_email = "badges@example.com";
   ```
   Email address for the Open Badges issuer (required for OB2 compliance). Defaults to a placeholder if not set.

3. **Service Name**:
   ```php
   $CFG->servicename = "Your Service Name";
   ```
   Used as fallback for issuer organization name if `badge_organization` is not set.

4. **Service Description**:
   ```php
   $CFG->servicedesc = "Your Service Description";
   ```
   Used in fallback organization name format: `"$CFG->servicedesc ($CFG->servicename)"`.

### Badge Configuration in lessons.json

Badges are defined in your `lessons.json` file. To mark a badge as a completion badge (eligible for LinkedIn sharing), add the `completion` field:

```json
{
  "badges": [
    {
      "title": "Course Completion",
      "image": "completion.png",
      "completion": true
    }
  ]
}
```

- Badges with `completion: true` will show the "Add to LinkedIn" button on the badge landing page (when viewed by the badge owner).
- Badges without `completion: true` are considered "milestone badges" and will show a message indicating they represent progress toward a final credential.

### Important Notes

- **Do not change** `badge_encrypt_password` or `badge_assert_salt` after issuing badges, as this will break existing badge URLs.
- Badge images must be accessible via the `badge_url` path.
- The `lessons.json` file must contain badge definitions with image filenames matching the badge code.

## Endpoints

### OB2 Assertion
- **URL**: `/assertions/{encrypted-id}.json`
- **Format**: Open Badges 2.0 JSON-LD
- **Context**: `https://w3id.org/openbadges/v2`
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

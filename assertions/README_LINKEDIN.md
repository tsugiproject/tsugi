# LinkedIn Integration Guide for Tsugi Badges

This document explains how Tsugi can be set up to add Python for Everybody (PY4E) badges to LinkedIn credentials without using third-party vendors (Credly, Badgr, etc.). Tsugi issues Open Badges 2.0 compliant credentials, and LinkedIn allows manual badge entry using standard fields.

We use PY4E as an example throughout.

---

## 🎯 Goal

Enable students to easily add Tsugi completion badges to their LinkedIn profiles, using:

- the new **PY4E LinkedIn Organization Page**
- the student's **unique badge assertion URL**
- instructions + UI guidance inside Tsugi

---

## 🧾 LinkedIn Issuer

PY4E now has a dedicated LinkedIn page:

**Python for Everybody (PY4E)**  
https://www.linkedin.com/company/py4e/

Students should select this issuer when adding credentials to LinkedIn.  
This ensures the PY4E logo appears automatically.

---

## ⚙️ Configuration Setup

To enable LinkedIn integration for badges, configure the following in your `config.php`:

### Required Configuration

1. **Badge Organization Name** (optional but recommended):
   ```php
   $CFG->badge_organization = "Python for Everybody (PY4E)";
   ```
   
   If not set, the system will use `"$CFG->servicedesc ($CFG->servicename)"` format, or just `$CFG->servicename` if `servicedesc` is not set.

2. **Service Name** (required):
   ```php
   $CFG->servicename = "PY4E";
   ```

3. **Service Description** (optional, used in fallback):
   ```php
   $CFG->servicedesc = "Python for Everybody";
   ```

### Badge Configuration in lessons.json

For a badge to show the LinkedIn button, it must be marked with `completion: true` in your `lessons.json`:

```json
{
  "badges": [
    {
      "title": "PY4E Course Completion",
      "image": "py4e_completion.png",
      "completion": true
    }
  ]
}
```

**Note:** Badges without `completion: true` are considered "milestone badges" and will show a message indicating they represent progress toward a final credential, rather than showing the LinkedIn button.

### How It Works

- The LinkedIn button only appears when:
  1. The badge has `completion: true` in lessons.json
  2. The viewer is logged in
  3. The logged-in user owns the badge being viewed
  
- The organization name sent to LinkedIn uses this priority:
  1. `$CFG->badge_organization` (if set)
  2. `"$CFG->servicedesc ($CFG->servicename)"` (if servicedesc is set)
  3. `$CFG->servicename` (final fallback)

---

## 🟢 What Students Will Enter on LinkedIn

When adding a credential on LinkedIn, students fill these fields:

- **Name:** PY4E Course Completion
- **Issuing Organization:** Python for Everybody (PY4E)
- **Issue Date:** (month/year of completion)
- **Credential URL:** link to the student's badge landing page
- **Credential ID:** optional (internal signature, hash, etc.)

---

## 🔗 Credential URL Format

Each badge has a unique, public verification URL, for example:




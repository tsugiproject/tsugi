# Controller Static Files Serving Solution

## Overview

This solution provides an efficient way to serve controller-specific JavaScript, CSS, and other static files while keeping them co-located with their controllers in the codebase.

## Architecture

### Components

1. **StaticFiles Controller** (`tsugi/lib/src/Controllers/StaticFiles.php`)
   - Handles serving static files with proper headers
   - Provides security (directory traversal protection)
   - Implements efficient caching (ETag, Last-Modified, 304 responses)

2. **Tool Base Class Enhancement** (`tsugi/lib/src/Controllers/Tool.php`)
   - Added `staticUrl()` method for easy URL generation
   - Automatically derives controller name from class
   - Includes `assetUrl()` alias for backward compatibility

3. **Static Directory Structure**
   ```
   tsugi/lib/src/Controllers/static/
   └── {ControllerName}/
       ├── *.js
       ├── *.css
       └── other static files
   ```

## Key Features

### ✅ Co-location
Static files live next to their controllers in the codebase:
```
tsugi/lib/src/Controllers/
├── Announcements.php
└── static/
    └── Announcements/
        ├── tsugi-announce.js
        └── dismiss.js
```

### ✅ Efficient Serving
- **Proper MIME types**: Automatically set based on file extension
- **Caching**: 1-year cache with `immutable` directive
- **ETag support**: Efficient revalidation
- **304 responses**: Saves bandwidth for unchanged files

### ✅ Security
- **Directory traversal protection**: Validates file paths
- **Filename validation**: Prevents malicious filenames
- **Controller name validation**: Ensures safe controller names

### ✅ Easy to Use
```php
// In any controller extending Tool:
$jsUrl = $this->staticUrl('my-script.js');
echo '<script src="' . htmlspecialchars($jsUrl) . '"></script>';
```

## Implementation Steps

### 1. Routes Automatically Registered

The Static controller routes are automatically registered in `Tsugi.php`. No additional setup needed!

### 2. Organize Static Files

Create directories for each controller:
```bash
mkdir -p tsugi/lib/src/Controllers/static/Announcements
mkdir -p tsugi/lib/src/Controllers/static/Pages
# etc.
```

### 3. Use in Controllers

```php
class Announcements extends Tool {
    public function get(Request $request) {
        // Generate static file URL
        $jsUrl = $this->staticUrl('tsugi-announce.js');
        
        // Use in HTML
        echo '<script src="' . htmlspecialchars($jsUrl) . '"></script>';
    }
}
```

### 4. Migrate Existing Static Files

Move static files from legacy locations:
```bash
# Example: Move Announcements static files
mv tsugi/lms/announce/tsugi-announce.js \
   tsugi/lib/src/Controllers/static/Announcements/
mv tsugi/lms/announce/dismiss.js \
   tsugi/lib/src/Controllers/static/Announcements/
```

Update references in code:
```php
// Old:
echo '<script src="' . addSession('dismiss.js') . '"></script>';

// New:
echo '<script src="' . htmlspecialchars($this->staticUrl('dismiss.js')) . '"></script>';
```

## Supported File Types

The Static controller automatically sets MIME types for:
- `.js` → `application/javascript`
- `.css` → `text/css`
- `.json` → `application/json`
- `.png`, `.jpg`, `.gif`, `.svg` → image types
- `.woff`, `.woff2`, `.ttf`, `.eot` → font types

## URL Structure

Static files are served at:
```
/static/{ControllerName}/{filename}
```

Examples:
- `/static/Announcements/tsugi-announce.js`
- `/static/Pages/page-editor.js`
- `/static/Badges/badge-display.js`

## Benefits Over Legacy Approach

| Feature | Legacy (`/lms/announce/`) | New (`/static/`) |
|---------|---------------------------|------------------|
| **Location** | Separate from controllers | Co-located with controllers |
| **Caching** | Basic | Advanced (ETag, 304) |
| **MIME Types** | Manual | Automatic |
| **Security** | Basic | Enhanced (traversal protection) |
| **Consistency** | Varies | Standardized |
| **URL Generation** | Manual/hardcoded | Helper method |
| **Registration** | Manual | Automatic |

## Performance Considerations

1. **Caching**: Static files are cached for 1 year with `immutable` directive
2. **ETag**: Enables efficient revalidation without full downloads
3. **304 Responses**: Saves bandwidth for unchanged files
4. **No Session**: Static files don't require session handling (public files)

## Security Considerations

1. **Path Validation**: Prevents directory traversal attacks
2. **Filename Validation**: Only allows safe characters
3. **Controller Validation**: Ensures valid controller names
4. **Real Path Check**: Verifies files are within static directory

## Future Enhancements

Potential improvements:
- Static file minification/compression
- File bundling
- Version hashing for cache busting
- CDN integration
- Static file preloading hints

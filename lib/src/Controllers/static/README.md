# Controller Static Files

This directory contains static files (JavaScript, CSS, etc.) that are specific to individual controllers.

## Directory Structure

```
tsugi/lib/src/Controllers/static/
├── README.md (this file)
├── Announcements/
│   ├── tsugi-announce.js
│   └── dismiss.js
├── Pages/
│   └── page-editor.js
└── Badges/
    └── badge-display.js
```

## Usage

### In Controllers

Controllers that extend `Tool` can use the `staticUrl()` method:

```php
<?php
namespace Tsugi\Controllers;

class Announcements extends Tool {
    
    public function get(Request $request) {
        // ... controller code ...
        
        // Generate static file URL
        $jsUrl = $this->staticUrl('tsugi-announce.js');
        
        // Output in HTML
        echo '<script type="module" src="' . htmlspecialchars($jsUrl) . '"></script>';
    }
}
```

### Static Usage

You can also use the static method directly:

```php
use Tsugi\Controllers\StaticFiles;

$jsUrl = StaticFiles::url('Announcements', 'tsugi-announce.js');
```

## Static File Serving

Static files are served via the `/static/{controller}/{filename}` route with:
- **Proper MIME types** (application/javascript for .js, text/css for .css, etc.)
- **Caching headers** (1 year cache with ETag support)
- **Security** (prevents directory traversal attacks)
- **304 Not Modified** responses for cached files

## Migration from Legacy Locations

To migrate existing static files from `/lms/{controller}/` directories:

1. Create the controller's static directory:
   ```bash
   mkdir -p tsugi/lib/src/Controllers/static/Announcements
   ```

2. Move JS files:
   ```bash
   mv tsugi/lms/announce/tsugi-announce.js tsugi/lib/src/Controllers/static/Announcements/
   mv tsugi/lms/announce/dismiss.js tsugi/lib/src/Controllers/static/Announcements/
   ```

3. Update controller code to use `staticUrl()`:
   ```php
   // Old:
   echo '<script src="' . addSession('dismiss.js') . '"></script>';
   
   // New:
   echo '<script src="' . htmlspecialchars($this->staticUrl('dismiss.js')) . '"></script>';
   ```

4. The Static routes are automatically registered in Tsugi.php.

## Route Registration

The Static controller routes are automatically registered in `Tsugi.php`. No additional registration needed!

Routes are available at:
- `/static/Announcements/tsugi-announce.js`
- `/static/Pages/page-editor.js`
- etc.

## Benefits

1. **Co-location**: Static files live next to their controllers in the codebase
2. **Efficient serving**: Proper caching headers reduce server load
3. **Type safety**: MIME types are automatically set correctly
4. **Security**: Directory traversal protection built-in
5. **Consistency**: Standardized approach across all controllers

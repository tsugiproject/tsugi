# Static Files Usage Example

## Example: Announcements Controller

Here's how to use the static file serving system in an Announcements controller:

### 1. Controller Implementation

```php
<?php
namespace Tsugi\Controllers;

use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Tsugi\Core\LTIX;

class Announcements extends Tool {
    
    const ROUTE = '/announcements';
    const NAME = 'Announcements';
    
    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Announcements@get');
        $app->router->get($prefix.'/', 'Announcements@get');
        $app->router->get($prefix.'/analytics', 'Announcements@analytics');
    }
    
    public function get(Request $request) {
        global $CFG, $OUTPUT;
        
        $this->requireAuth();
        LTIX::getConnection();
        
        // ... your controller logic ...
        
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <div class="container">
            <h1>Announcements</h1>
            <!-- Your HTML content -->
        </div>
        
        <!-- Load controller-specific JavaScript -->
        <script src="<?= htmlspecialchars($this->staticUrl('tsugi-announce.js')) ?>"></script>
        <script src="<?= htmlspecialchars($this->staticUrl('dismiss.js')) ?>"></script>
        <?php
        $OUTPUT->footer();
    }
}
```

### 2. File Organization

Place your JavaScript files in:
```
tsugi/lib/src/Controllers/static/Announcements/
├── tsugi-announce.js
└── dismiss.js
```

### 3. Route Registration

The Static routes are automatically registered in `Tsugi.php`. No additional registration needed!

### 4. Generated URLs

The `staticUrl()` method will generate URLs like:
- `/static/Announcements/tsugi-announce.js`
- `/static/Announcements/dismiss.js`

These URLs will:
- Be served with proper MIME types (`application/javascript`)
- Include caching headers (1 year cache)
- Support ETag for efficient revalidation
- Return 304 Not Modified when appropriate

### 5. Migration from Legacy Code

If you have existing code like:

```php
// Old approach
echo '<script src="' . addSession('dismiss.js') . '"></script>';
```

Replace with:

```php
// New approach
echo '<script src="' . htmlspecialchars($this->staticUrl('dismiss.js')) . '"></script>';
```

Note: The new approach doesn't need `addSession()` because static files are public files that don't require session handling.

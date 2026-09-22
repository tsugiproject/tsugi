<?php

namespace Tsugi\Services\CourseNav;

use \Tsugi\Controllers\Courses;
use \Tsugi\Controllers\Catalog;
use \Tsugi\Controllers\Login;
use Tsugi\Controllers\Settings;
use Tsugi\Core\ContextImages;
use Tsugi\UI\Menu;
use Tsugi\UI\MenuSet;
use Tsugi\Util\U;

/**
 * Teacher-edited course top nav: catalog, normalize, compile to MenuSet.
 *
 * Locked chrome (Home slot, Avatar, Settings) is not stored as items.
 * An optional `home` string renames the Home label. Optional items have
 * left / right / dropdown flags (widgets: left / right only).
 */
class CourseNav {

    const WIDGET_LI_CLASS = 'hidden-xs tsugi-wc-nav-item';
    const HOME_LABEL_MAX = 40;

    /**
     * @return array<string, mixed>
     */
    public static function defaultDocument() {
        return array(
            'items' => array(
                array('id' => 'lessons', 'left' => true),
                array('id' => 'files', 'left' => true),
                array('id' => 'pages', 'left' => true),
                array('id' => 'quiz1', 'left' => true),
                array('id' => 'courses_widget', 'right' => true),
                array('id' => 'logout', 'dropdown' => true),
            ),
        );
    }

    /**
     * @param mixed $json JSON string or null
     * @return array<string, mixed>
     */
    public static function documentFromJson($json) {
        if ( ! is_string($json) || trim($json) === '' ) {
            return self::defaultDocument();
        }
        $decoded = json_decode($json, true);
        if ( ! is_array($decoded) ) {
            return self::defaultDocument();
        }
        if ( ! array_key_exists('items', $decoded) ) {
            return self::defaultDocument();
        }
        return self::normalize($decoded);
    }

    /**
     * @param mixed $doc
     * @return array<string, mixed>
     */
    public static function normalize($doc) {
        if ( is_string($doc) ) {
            $decoded = json_decode($doc, true);
            $doc = is_array($decoded) ? $decoded : array();
        }
        if ( ! is_array($doc) ) {
            $doc = array();
        }
        $known = self::catalogById();
        $items = array();
        $rawItems = $doc['items'] ?? array();
        if ( ! is_array($rawItems) ) {
            $rawItems = array();
        }
        $seen = array();
        foreach ( $rawItems as $row ) {
            if ( ! is_array($row) ) {
                continue;
            }
            $id = isset($row['id']) ? (string) $row['id'] : '';
            if ( $id === '' || isset($seen[$id]) || ! isset($known[$id]) ) {
                continue;
            }
            if ( $id === 'home' || $id === 'settings' || $id === 'avatar' ) {
                continue;
            }
            $seen[$id] = true;
            $entry = $known[$id];
            $item = array('id' => $id);
            $left = ! empty($row['left']);
            $right = ! empty($row['right']);
            $dropdown = ! empty($row['dropdown']);
            if ( $entry['kind'] === 'widget' ) {
                $dropdown = false;
            }
            if ( $left ) {
                $item['left'] = true;
            }
            if ( $right ) {
                $item['right'] = true;
            }
            if ( $dropdown ) {
                $item['dropdown'] = true;
            }
            if ( ! empty($row['instructor']) ) {
                $item['instructor'] = true;
            }
            if ( empty($item['left']) && empty($item['right']) && empty($item['dropdown']) ) {
                continue;
            }
            $items[] = $item;
        }
        $out = array('items' => $items);
        $home = self::sanitizeHomeLabel($doc['home'] ?? null);
        if ( $home !== null ) {
            $out['home'] = $home;
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $doc
     */
    public static function encode(array $doc) {
        $json = json_encode(self::normalize($doc), JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : '{"items":[]}';
    }

    /**
     * Catalog rows in editor order (not including locked chrome).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function catalog() {
        $rows = array(
            array('id' => 'lessons', 'kind' => 'link', 'label' => 'Lessons', 'route' => '/lessons', 'site' => false),
            array('id' => 'assignments', 'kind' => 'link', 'label' => 'Assignments', 'route' => '/assignments', 'site' => false),
            array('id' => 'announcements', 'kind' => 'link', 'label' => 'Announcements', 'route' => '/announcements', 'site' => false),
            array('id' => 'files', 'kind' => 'link', 'label' => 'Files', 'route' => '/files', 'site' => false),
            array('id' => 'pages', 'kind' => 'link', 'label' => 'Pages', 'route' => '/pages', 'site' => false),
            array('id' => 'discussions_widget', 'kind' => 'widget', 'label' => 'Discussions widget', 'route' => '/discussions', 'site' => false),
            array('id' => 'discussions', 'kind' => 'link', 'label' => 'Discussions', 'route' => '/discussions', 'site' => false),
            array('id' => 'grades', 'kind' => 'link', 'label' => 'Grades', 'route' => '/grades', 'site' => false),
            array('id' => 'quiz1', 'kind' => 'link', 'label' => 'Quizzes', 'route' => '/quiz1', 'site' => false),
            array('id' => 'calendar_widget', 'kind' => 'widget', 'label' => 'Calendar widget', 'route' => '/calendar', 'site' => false),
            array('id' => 'calendar', 'kind' => 'link', 'label' => 'Calendar', 'route' => '/calendar', 'site' => false),
            array('id' => 'map', 'kind' => 'link', 'label' => 'Map', 'route' => '/map', 'site' => false),
            array('id' => 'notifications', 'kind' => 'link', 'label' => 'Notifications', 'route' => '/notifications', 'site' => false),
            array('id' => 'notifications_widget', 'kind' => 'widget', 'label' => 'Notifications widget', 'route' => '/notifications', 'site' => false),
            array('id' => 'profile', 'kind' => 'link', 'label' => 'Profile', 'route' => '/profile', 'site' => true),
            array('id' => 'analytics', 'kind' => 'link', 'label' => 'Analytics', 'route' => '/analytics', 'site' => false),
            array('id' => 'badges', 'kind' => 'link', 'label' => 'Badges', 'route' => '/badges', 'site' => false),
            array('id' => 'courses_widget', 'kind' => 'widget', 'label' => 'Courses widget', 'route' => '/courses', 'site' => true, 'pin' => 'catalog', 'hint' => 'Switcher for other courses. Place in the upper left or upper right, like other widgets.'),
            array('id' => 'exit_course', 'kind' => 'link', 'label' => 'Exit course', 'route' => '', 'site' => true, 'pin' => 'catalog', 'hint' => 'Leaves the course and returns to the site home.', 'needs_apphome' => true),
            array('id' => 'login', 'kind' => 'link', 'label' => 'Login', 'route' => '/login', 'site' => true, 'pin' => 'last', 'hint' => 'Only shown when the user is not logged in.'),
            array('id' => 'logout', 'kind' => 'link', 'label' => 'Logout', 'route' => '/logout', 'site' => true, 'hint' => 'Only shown when the user is logged in. Keep Logout on unless you have another way out of the course.', 'pin' => 'last'),
        );
        if ( self::hasAppHome() ) {
            return $rows;
        }
        $out = array();
        foreach ( $rows as $row ) {
            if ( ! empty($row['needs_apphome']) ) {
                continue;
            }
            $out[] = $row;
        }
        return $out;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function catalogById() {
        $out = array();
        foreach ( self::catalog() as $row ) {
            $out[$row['id']] = $row;
        }
        return $out;
    }

    /**
     * Merge stored items onto the catalog in stored order, then remaining catalog rows.
     *
     * @param array<string, mixed> $doc
     * @return array<int, array<string, mixed>>
     */
    public static function editorRows(array $doc) {
        $known = self::catalogById();
        $placed = array();
        $items = $doc['items'] ?? array();
        if ( ! is_array($items) ) {
            $items = array();
        }
        $flagsById = array();
        foreach ( $items as $item ) {
            if ( ! is_array($item) ) {
                continue;
            }
            $id = isset($item['id']) ? (string) $item['id'] : '';
            if ( $id === '' || ! isset($known[$id]) || isset($flagsById[$id]) ) {
                continue;
            }
            $flagsById[$id] = array(
                'left' => ! empty($item['left']),
                'right' => ! empty($item['right']),
                'dropdown' => ! empty($item['dropdown']),
                'instructor' => ! empty($item['instructor']),
            );
        }
        $homeCustom = self::sanitizeHomeLabel($doc['home'] ?? null);
        $rows = array(
            array(
                'id' => 'home',
                'kind' => 'chrome',
                'label' => $homeCustom !== null ? $homeCustom : 'Home',
                'rename' => true,
                'custom' => $homeCustom !== null,
                'pin' => 'first',
                'hint' => 'Always in the upper left. You can rename this label.',
            ),
        );
        foreach ( $items as $item ) {
            if ( ! is_array($item) ) {
                continue;
            }
            $id = isset($item['id']) ? (string) $item['id'] : '';
            if ( $id === '' || ! isset($known[$id]) || isset($placed[$id]) ) {
                continue;
            }
            if ( self::isPinnedLast($known[$id] ?? array()) || self::isPinnedToCatalog($known[$id] ?? array()) ) {
                continue;
            }
            $placed[$id] = true;
            $rows[] = self::editorRow($known[$id], $flagsById[$id]);
        }
        foreach ( self::catalog() as $entry ) {
            if ( isset($placed[$entry['id']]) ) {
                continue;
            }
            if ( self::isPinnedLast($entry) ) {
                continue;
            }
            $placed[$entry['id']] = true;
            $empty = array('left' => false, 'right' => false, 'dropdown' => false, 'instructor' => false);
            $rows[] = self::editorRow($entry, $flagsById[$entry['id']] ?? $empty);
        }
        $injectedSettings = false;
        foreach ( self::catalog() as $entry ) {
            if ( ! self::isPinnedLast($entry) ) {
                continue;
            }
            if ( ! $injectedSettings ) {
                $rows[] = array(
                    'id' => 'settings',
                    'kind' => 'chrome',
                    'label' => 'Settings',
                    'pin' => 'last',
                    'hint' => 'Always in the avatar menu before Logout. Instructors only.',
                );
                $injectedSettings = true;
            }
            $empty = array('left' => false, 'right' => false, 'dropdown' => false, 'instructor' => false);
            $rows[] = self::editorRow($entry, $flagsById[$entry['id']] ?? $empty);
        }
        return $rows;
    }

    /**
     * @param array<string, mixed> $entry
     * @param array<string, bool> $flags
     * @return array<string, mixed>
     */
    private static function editorRow(array $entry, array $flags) {
        return array_merge($entry, $flags);
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    public static function fromPost(array $post) {
        $order = $post['nav_order'] ?? array();
        if ( ! is_array($order) ) {
            $order = array();
        }
        $nav = $post['nav'] ?? array();
        if ( ! is_array($nav) ) {
            $nav = array();
        }
        $items = array();
        $seen = array();
        foreach ( $order as $id ) {
            $id = is_string($id) || is_int($id) ? (string) $id : '';
            if ( $id === '' || isset($seen[$id]) ) {
                continue;
            }
            $seen[$id] = true;
            $flags = $nav[$id] ?? array();
            if ( ! is_array($flags) ) {
                $flags = array();
            }
            $items[] = array(
                'id' => $id,
                'left' => ! empty($flags['left']),
                'right' => ! empty($flags['right']),
                'dropdown' => ! empty($flags['dropdown']),
                'instructor' => ! empty($flags['instructor']),
            );
        }
        return self::normalize(array(
            'home' => $post['home'] ?? '',
            'items' => $items,
        ));
    }

    /**
     * @param array<string, mixed> $doc
     * @return MenuSet
     */
    public static function compile(array $doc, $context_id) {
        $cid = (int) $context_id;
        $doc = self::normalize($doc);
        $known = self::catalogById();
        $prefix = Courses::courseUrlPrefix($cid);
        $set = new MenuSet();
        $set->setHome(
            self::homeBrandHtml($cid, self::homeLabel($doc)),
            $prefix.'/home'
        );

        foreach ( $doc['items'] as $item ) {
            if ( empty($item['left']) ) {
                continue;
            }
            self::addPlacement($set, $known, $item, 'left', $prefix);
        }
        foreach ( $doc['items'] as $item ) {
            if ( empty($item['right']) ) {
                continue;
            }
            self::addPlacement($set, $known, $item, 'right', $prefix);
        }

        $submenu = new Menu();
        $exitBuilt = null;
        $logoutBuilt = null;
        foreach ( $doc['items'] as $item ) {
            if ( empty($item['dropdown']) ) {
                continue;
            }
            $built = self::linkEntry($known, $item, $prefix);
            if ( $built === null ) {
                continue;
            }
            $id = $item['id'] ?? '';
            if ( $id === 'exit_course' ) {
                $exitBuilt = $built;
                continue;
            }
            if ( $id === 'logout' ) {
                $logoutBuilt = $built;
                continue;
            }
            $submenu->addLink($built['label'], $built['href']);
        }
        if ( $exitBuilt !== null ) {
            $submenu->addLink($exitBuilt['label'], $exitBuilt['href']);
        }
        if ( self::maySeeSettings() ) {
            $submenu->addLink(__('Settings'), $prefix.'/settings');
        }
        if ( $logoutBuilt !== null ) {
            $submenu->addLink($logoutBuilt['label'], $logoutBuilt['href']);
        }
        $set->addRight(self::avatarTrigger(), $submenu, false);

        return $set;
    }

    /**
     * @param array<string, array<string, mixed>> $known
     * @param array<string, mixed> $item
     */
    private static function addPlacement(MenuSet $set, array $known, array $item, $placement, $prefix) {
        $id = $item['id'];
        $entry = $known[$id] ?? null;
        if ( ! is_array($entry) ) {
            return;
        }
        if ( ! empty($item['instructor']) && ! self::isCourseInstructor() ) {
            return;
        }
        if ( $entry['kind'] === 'widget' ) {
            $html = self::widgetHtml($id, $prefix);
            if ( $html === '' ) {
                return;
            }
            if ( $placement === 'left' ) {
                $set->addLeft($html, false, false, self::WIDGET_LI_CLASS);
            } else {
                $set->addRight($html, false, false, self::WIDGET_LI_CLASS);
            }
            return;
        }
        $built = self::linkEntry($known, $item, $prefix);
        if ( $built === null ) {
            return;
        }
        if ( $placement === 'left' ) {
            $set->addLeft($built['label'], $built['href']);
        } else {
            $set->addRight($built['label'], $built['href'], false);
        }
    }

    /**
     * @param array<string, array<string, mixed>> $known
     * @param array<string, mixed> $item
     * @return array{label: string, href: string}|null
     */
    private static function linkEntry(array $known, array $item, $prefix) {
        $id = $item['id'] ?? '';
        $entry = $known[$id] ?? null;
        if ( ! is_array($entry) || $entry['kind'] !== 'link' ) {
            return null;
        }
        if ( $id === 'login' && U::isLoggedIn() ) {
            return null;
        }
        if ( ( $id === 'logout' || $id === 'profile' ) && ! U::isLoggedIn() ) {
            return null;
        }
        if ( $id === 'exit_course' && ! self::hasAppHome() ) {
            return null;
        }
        if ( ! empty($item['instructor']) && ! self::isCourseInstructor() ) {
            return null;
        }
        if ( $id === 'exit_course' ) {
            global $CFG;
            return array(
                'label' => __($entry['label']),
                'href' => $CFG->getHomeUrl(),
            );
        }
        $route = $entry['route'];
        if ( ! empty($entry['site']) ) {
            $href = self::siteUrl($route);
        } else {
            $href = $prefix.$route;
        }
        return array('label' => __($entry['label']), 'href' => $href);
    }

    /**
     * Label shown for the locked Home slot.
     *
     * @param array<string, mixed> $doc
     */
    public static function homeLabel(array $doc) {
        $custom = self::sanitizeHomeLabel($doc['home'] ?? null);
        return $custom !== null ? $custom : __('Home');
    }

    /**
     * Home slot inner HTML: optional course icon plus escaped label.
     */
    public static function homeBrandHtml($context_id, $label) {
        $html = htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8');
        $cid = (int) $context_id;
        if ( $cid < 1 ) {
            return $html;
        }
        $meta = ContextImages::metadata($cid);
        if ( empty($meta['has_icon']) ) {
            return $html;
        }
        $url = ContextImages::servedUrl(
            $cid,
            ContextImages::KIND_ICON,
            $meta['icon_bytes'] ?? 0,
            $meta['icon_updated_at'] ?? null
        );
        if ( $url === '' ) {
            return $html;
        }
        return '<img class="tsugi-course-nav-icon" src="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'" alt="" width="32" height="32">'.$html;
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private static function sanitizeHomeLabel($value) {
        if ( ! is_string($value) ) {
            return null;
        }
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x1F\x7F]+/', '', $value);
        if ( ! is_string($value) ) {
            return null;
        }
        $value = trim(preg_replace('/\s+/', ' ', $value));
        if ( $value === '' || $value === 'Home' ) {
            return null;
        }
        $max = self::HOME_LABEL_MAX;
        if ( function_exists('mb_strlen') && mb_strlen($value) > $max ) {
            $value = mb_substr($value, 0, $max);
        } elseif ( strlen($value) > $max ) {
            $value = substr($value, 0, $max);
        }
        return $value;
    }

    private static function isPinnedLast(array $entry) {
        return ! empty($entry['pin']) && $entry['pin'] === 'last';
    }

    private static function isPinnedToCatalog(array $entry) {
        return ! empty($entry['pin']) && $entry['pin'] === 'catalog';
    }

    private static function hasAppHome() {
        global $CFG;
        if ( isset($CFG) && is_object($CFG) && method_exists($CFG, 'hasHomeUrl') ) {
            return $CFG->hasHomeUrl();
        }
        return isset($CFG->apphome) && is_string($CFG->apphome) && trim($CFG->apphome) !== '';
    }

    private static function maySeeSettings() {
        try {
            return Settings::showInMenu();
        } catch ( \Throwable $e ) {
            return false;
        }
    }

    private static function isCourseInstructor() {
        if ( ! empty($_SESSION['isinstructor']) || ! empty($_SESSION['instructor']) ) {
            return true;
        }
        return self::maySeeSettings();
    }

    private static function siteUrl($route) {
        if ( $route === '/login' ) {
            return Login::loginUrl();
        }
        global $CFG;
        $base = ( isset($CFG->apphome) && is_string($CFG->apphome) && $CFG->apphome )
            ? $CFG->apphome
            : $CFG->wwwroot;
        return rtrim((string) $base, '/').$route;
    }

    private static function avatarTrigger() {
        return \Tsugi\UI\Output::avatarMenuTrigger();
    }

    private static function widgetHtml($id, $prefix) {
        global $CFG;
        $apiRoot = rtrim((string) $CFG->wwwroot, '/');
        $p = htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8');
        $a = htmlspecialchars($apiRoot, ENT_QUOTES, 'UTF-8');
        if ( $id === 'notifications_widget' ) {
            return '<tsugi-notifications api-url="'.$a.'/api/notifications.php"'
                .' notifications-view-url="'.$p.'/notifications"'
                .' announcements-view-url="'.$p.'/announcements"></tsugi-notifications>';
        }
        if ( $id === 'discussions_widget' ) {
            return '<tsugi-discussions api-url="'.$p.'/discussions/json"'
                .' discussions-url="'.$p.'/discussions"></tsugi-discussions>';
        }
        if ( $id === 'calendar_widget' ) {
            return '<tsugi-calendar-due api-url="'.$p.'/calendar/json"'
                .' lessons-url="'.$p.'/calendar"'
                .' calendar-url="'.$p.'/calendar"></tsugi-calendar-due>';
        }
        if ( $id === 'courses_widget' ) {
            return Catalog::coursesWidgetTag();
        }
        return '';
    }
}

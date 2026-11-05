<?php

namespace Tsugi\Services\CourseNav;

use \Tsugi\Controllers\Courses;
use \Tsugi\Controllers\Catalog;
use \Tsugi\Services\Catalog\CatalogRepository;
use \Tsugi\Services\Site\Site;
use \Tsugi\Controllers\Login;
use Tsugi\Controllers\Settings;
use Tsugi\Core\Manifest;
use Tsugi\Services\ContextUser\ContextUserSettings;
use Tsugi\Services\StudentView\StudentView;
use Tsugi\Core\ContextImages;
use Tsugi\UI\Menu;
use Tsugi\UI\MenuSet;
use Tsugi\Util\U;

use Tsugi\Core\ReqScope;

/**
 * Teacher-edited course top nav: catalog, normalize, compile to MenuSet.
 *
 * Locked chrome (Home slot, Avatar, Student View, Settings) is not stored as items.
 * An optional `home` string renames the Home label. Optional items have
 * left / right / dropdown flags (widgets: left / right only).
 */
class CourseNav {

    const WIDGET_LI_CLASS = 'hidden-xs tsugi-wc-nav-item';
    const HOME_LABEL_MAX = 40;
    const INITIAL_HOME_CHARS = 6;

    /**
     * @return array<string, mixed>
     */
    public static function defaultDocument() {
        return array(
            'items' => array(
                array('id' => 'lessons', 'left' => true),
                array('id' => 'content', 'left' => true),
                array('id' => 'outline', 'left' => true),
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
            if ( ! empty($entry['instructor_locked']) || ! empty($row['instructor']) ) {
                $item['instructor'] = true;
            }
            if ( ! empty($row['default']) ) {
                $item['default'] = true;
            }
            if ( ! empty($row['available']) ) {
                $item['available'] = true;
            }
            if ( ! empty($row['test']) ) {
                $item['test'] = true;
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
            array('id' => 'lessons', 'kind' => 'link', 'label' => 'Lessons', 'settings_label' => 'Card View', 'route' => '/lessons', 'site' => false),
            array('id' => 'content', 'kind' => 'link', 'label' => 'Content', 'settings_label' => 'Content', 'route' => '/content', 'site' => false),
            array('id' => 'outline', 'kind' => 'link', 'label' => 'Outline', 'settings_label' => 'Outline', 'route' => '/outline', 'site' => false),
            array('id' => 'assignments', 'kind' => 'link', 'label' => 'Assignments', 'route' => '/assignments', 'site' => false),
            array('id' => 'announcements', 'kind' => 'link', 'label' => 'Announcements', 'route' => '/announcements', 'site' => false),
            array('id' => 'files', 'kind' => 'link', 'label' => 'Files', 'route' => '/files', 'site' => false),
            array('id' => 'pages', 'kind' => 'link', 'label' => 'Pages', 'route' => '/pages', 'site' => false),
            array('id' => 'discussions_widget', 'kind' => 'widget', 'label' => 'Discussions widget', 'route' => '/discussions', 'site' => false),
            array('id' => 'discussions', 'kind' => 'link', 'label' => 'Discussions', 'route' => '/discussions', 'site' => false),
            array('id' => 'grades', 'kind' => 'link', 'label' => 'Grades', 'route' => '/grades', 'site' => false),
            array('id' => 'badges', 'kind' => 'link', 'label' => 'Badges', 'route' => '/badges', 'site' => false),
            array('id' => 'quiz1', 'kind' => 'link', 'label' => 'Quizzes', 'route' => '/quiz1', 'site' => false),
            array('id' => 'calendar_widget', 'kind' => 'widget', 'label' => 'Calendar widget', 'route' => '/calendar', 'site' => false),
            array('id' => 'calendar', 'kind' => 'link', 'label' => 'Calendar', 'route' => '/calendar', 'site' => false),
            array('id' => 'map', 'kind' => 'link', 'label' => 'Map', 'route' => '/map', 'site' => false),
            array('id' => 'notifications', 'kind' => 'link', 'label' => 'Notifications', 'route' => '/notifications', 'site' => false),
            array('id' => 'notifications_widget', 'kind' => 'widget', 'label' => 'Notifications widget', 'route' => '/notifications', 'site' => false),
            array('id' => 'profile', 'kind' => 'link', 'label' => 'Profile', 'route' => '/profile', 'site' => true),
            array('id' => 'analytics', 'kind' => 'link', 'label' => 'Analytics', 'route' => '/analytics', 'site' => false),
            array('id' => 'courses_widget', 'kind' => 'widget', 'label' => 'Courses widget', 'route' => '/courses', 'site' => true, 'pin' => 'catalog', 'hint' => 'Switcher for other courses. Place in the upper left or upper right, like other widgets.'),
            array('id' => 'exit_course', 'kind' => 'link', 'label' => 'Exit course', 'route' => '', 'site' => true, 'pin' => 'catalog', 'hint' => 'Leaves the course and returns to the site home.', 'needs_apphome' => true),
            array('id' => 'leave_course', 'kind' => 'link', 'label' => 'Leave course', 'route' => '', 'site' => true, 'pin' => 'catalog', 'hint' => 'Closes this window. The browser allows that when this window was opened for the course.'),
            array('id' => 'roster', 'kind' => 'link', 'label' => 'Roster', 'route' => '/roster', 'site' => false, 'instructor_locked' => true, 'pin' => 'last', 'hint' => 'Always instructor only. Students do not see it.'),
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
            $flags = array(
                'left' => ! empty($item['left']),
                'right' => ! empty($item['right']),
                'dropdown' => ! empty($item['dropdown']),
                'instructor' => ! empty($item['instructor']),
            );
            if ( array_key_exists('default', $item) ) {
                $flags['default'] = ! empty($item['default']);
            }
            if ( array_key_exists('available', $item) ) {
                $flags['available'] = ! empty($item['available']);
            }
            if ( array_key_exists('test', $item) ) {
                $flags['test'] = ! empty($item['test']);
            }
            $flagsById[$id] = $flags;
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
            if ( self::skipSiteAccountNav($id) ) {
                continue;
            }
            if ( self::isPinnedLast($known[$id] ?? array()) || self::isPinnedToCatalog($known[$id] ?? array()) ) {
                continue;
            }
            $placed[$id] = true;
            $rows[] = self::editorRow($known[$id], $flagsById[$id]);
        }
        foreach ( self::catalog() as $entry ) {
            if ( self::skipSiteAccountNav($entry['id']) ) {
                continue;
            }
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
        $empty = array('left' => false, 'right' => false, 'dropdown' => false, 'instructor' => false);
        if ( isset($known['roster']) ) {
            $rows[] = self::editorRow($known['roster'], $flagsById['roster'] ?? $empty);
        }
        $injectedSettings = false;
        foreach ( self::catalog() as $entry ) {
            if ( ! self::isPinnedLast($entry) || $entry['id'] === 'roster' ) {
                continue;
            }
            if ( ! $injectedSettings ) {
                $rows[] = array(
                    'id' => 'student',
                    'kind' => 'chrome',
                    'label' => 'Student View',
                    'pin' => 'last',
                    'hint' => 'Always in the avatar menu before Settings. Instructors only.',
                );
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
        $rows = self::moveRowAfter($rows, 'content', 'lessons');
        return self::moveRowAfter($rows, 'outline', 'content');
    }

    /**
     * Put one editor row immediately after another. No change when either id is missing
     * or the row is already next.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private static function moveRowAfter(array $rows, $id, $afterId) {
        $from = null;
        $after = null;
        foreach ( $rows as $i => $row ) {
            $rowId = isset($row['id']) ? (string) $row['id'] : '';
            if ( $rowId === $id && $from === null ) {
                $from = $i;
            }
            if ( $rowId === $afterId && $after === null ) {
                $after = $i;
            }
        }
        if ( $from === null || $after === null || $from === $after + 1 ) {
            return $rows;
        }
        $moved = $rows[$from];
        array_splice($rows, $from, 1);
        if ( $from < $after ) {
            $after--;
        }
        array_splice($rows, $after + 1, 0, array($moved));
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
     * Lessons, Content, and Outline. One menu slot, three ways to read the course.
     *
     * @return list<string>
     */
    public static function contentViewIds() {
        return array('lessons', 'content', 'outline');
    }

    /**
     * Views a student may choose. Instructor-only rows are left out.
     * A view is included when it is marked default or available. Older rows
     * with neither key count as both.
     *
     * @param array<string, mixed> $doc
     * @return list<string>
     */
    public static function studentContentViews(array $doc) {
        $doc = self::normalize($doc);
        $placed = array();
        foreach ( $doc['items'] as $item ) {
            if ( ! self::isStudentContentItem($item) ) {
                continue;
            }
            $id = (string) $item['id'];
            if ( isset($placed[$id]) ) {
                continue;
            }
            if ( ! self::contentViewFlag($item, 'default') && ! self::contentViewFlag($item, 'available') ) {
                continue;
            }
            $placed[$id] = true;
        }
        $out = array();
        foreach ( self::contentViewIds() as $id ) {
            if ( isset($placed[$id]) ) {
                $out[] = $id;
            }
        }
        return $out;
    }

    /**
     * Students get one reading link: the view they picked, or the default.
     * Other reading views are removed, including instructor test links.
     *
     * @param array<string, mixed> $doc
     * @param mixed $preferred
     * @return array<string, mixed>
     */
    public static function applyContentView(array $doc, $preferred) {
        $doc = self::normalize($doc);
        $defaultId = self::navContentView($doc, '');
        $primary = self::navContentView($doc, is_string($preferred) ? $preferred : '');
        $doc = self::keepContentView($doc, $primary);
        return self::stampDefaultLabel($doc, $defaultId);
    }

    /**
     * The one reading link for the top menu, and the other views an instructor
     * asked to test. Test views are not part of the document items.
     *
     * @param array<string, mixed> $doc
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    private static function instructorContentViews(array $doc) {
        $doc = self::normalize($doc);
        $primary = self::navContentView($doc, '');
        $tests = array();
        foreach ( self::contentViewIds() as $id ) {
            if ( $id === $primary ) {
                continue;
            }
            foreach ( $doc['items'] as $item ) {
                if ( ! self::isPlacedContentItem($item) || (string) $item['id'] !== $id ) {
                    continue;
                }
                if ( ! empty($item['test']) ) {
                    $tests[] = $id;
                }
                break;
            }
        }
        return array(self::stampDefaultLabel(self::keepContentView($doc, $primary), $primary), $tests);
    }

    /**
     * The menu words stay with the course default. The link can be another view.
     *
     * @param array<string, mixed> $doc
     * @param string $defaultId
     * @return array<string, mixed>
     */
    private static function stampDefaultLabel(array $doc, $defaultId) {
        $entry = self::catalogById()[$defaultId] ?? null;
        if ( ! is_array($entry) || ! isset($entry['label']) || ! is_string($entry['label']) || $entry['label'] === '' ) {
            return $doc;
        }
        foreach ( $doc['items'] as $i => $item ) {
            if ( ! is_array($item) ) {
                continue;
            }
            $id = isset($item['id']) ? (string) $item['id'] : '';
            if ( ! in_array($id, self::contentViewIds(), true) ) {
                continue;
            }
            $doc['items'][$i]['nav_label'] = $entry['label'];
        }
        return $doc;
    }

    /**
     * @param array<string, mixed> $doc
     * @return array<string, mixed>
     */
    private static function keepContentView(array $doc, $primary) {
        if ( $primary === 'lessons' ) {
            $doc = self::ensureCardView($doc);
        }
        $items = array();
        foreach ( $doc['items'] as $item ) {
            if ( ! is_array($item) ) {
                continue;
            }
            $id = isset($item['id']) ? (string) $item['id'] : '';
            if ( in_array($id, self::contentViewIds(), true) && $id !== $primary ) {
                continue;
            }
            $items[] = $item;
        }
        $doc['items'] = $items;
        return $doc;
    }

    /**
     * Card View uses the same menu place as the other reading views when it
     * was not stored on its own.
     *
     * @param array<string, mixed> $doc
     * @return array<string, mixed>
     */
    private static function ensureCardView(array $doc) {
        foreach ( $doc['items'] as $item ) {
            if ( self::isPlacedContentItem($item) && (string) $item['id'] === 'lessons' ) {
                return $doc;
            }
        }
        $donor = null;
        foreach ( $doc['items'] as $item ) {
            if ( self::isPlacedContentItem($item) ) {
                $donor = $item;
                break;
            }
        }
        if ( ! is_array($donor) ) {
            return $doc;
        }
        $card = array('id' => 'lessons', 'default' => true);
        foreach ( array('left', 'right', 'dropdown', 'instructor') as $flag ) {
            if ( ! empty($donor[$flag]) ) {
                $card[$flag] = true;
            }
        }
        $items = array();
        $inserted = false;
        foreach ( $doc['items'] as $item ) {
            if ( ! $inserted && self::isPlacedContentItem($item) ) {
                $items[] = $card;
                $inserted = true;
            }
            $items[] = $item;
        }
        $doc['items'] = $items;
        return $doc;
    }

    /**
     * A student's pick when it is allowed, otherwise the default view.
     * When Lessons is on and no view is marked default, Card View is the link.
     *
     * @param array<string, mixed> $doc
     * @param string $preferred
     * @return string
     */
    private static function navContentView(array $doc, $preferred) {
        if ( $preferred !== '' && in_array($preferred, self::studentContentViews($doc), true) ) {
            return $preferred;
        }
        $placed = false;
        foreach ( self::contentViewIds() as $id ) {
            foreach ( $doc['items'] as $item ) {
                if ( ! self::isPlacedContentItem($item) || (string) $item['id'] !== $id ) {
                    continue;
                }
                $placed = true;
                if ( self::contentViewFlag($item, 'default') ) {
                    return $id;
                }
            }
        }
        return $placed ? 'lessons' : '';
    }

    /**
     * @param mixed $item
     */
    private static function isStudentContentItem($item) {
        if ( ! is_array($item) ) {
            return false;
        }
        $id = isset($item['id']) ? (string) $item['id'] : '';
        if ( ! in_array($id, self::contentViewIds(), true) || ! empty($item['instructor']) ) {
            return false;
        }
        return ! empty($item['left']) || ! empty($item['right']) || ! empty($item['dropdown']);
    }

    /**
     * @param mixed $item
     */
    private static function isPlacedContentItem($item) {
        if ( ! is_array($item) ) {
            return false;
        }
        $id = isset($item['id']) ? (string) $item['id'] : '';
        if ( ! in_array($id, self::contentViewIds(), true) ) {
            return false;
        }
        return ! empty($item['left']) || ! empty($item['right']) || ! empty($item['dropdown']);
    }

    /**
     * Explicit default/available/test flags win. A placed row from before those
     * flags existed counts as default and available, not as an instructor test link.
     *
     * @param array<string, mixed> $item
     * @param string $flag default|available|test
     */
    private static function contentViewFlag(array $item, $flag) {
        $explicit = array_key_exists('default', $item)
            || array_key_exists('available', $item)
            || array_key_exists('test', $item);
        if ( $explicit ) {
            return ! empty($item[$flag]);
        }
        return $flag === 'default' || $flag === 'available';
    }

    /**
     * Avatar label for a reading view the instructor is testing.
     *
     * @param string $id
     * @return string
     */
    private static function contentTestLabel($id) {
        if ( $id === 'content' ) {
            return __('Content (test)');
        }
        if ( $id === 'outline' ) {
            return __('Outline (test)');
        }
        return __('Lessons (test)');
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
        $hasViews = false;
        foreach ( $order as $id ) {
            if ( (is_string($id) || is_int($id)) && (string) $id === 'course_views' ) {
                $hasViews = true;
                break;
            }
        }
        foreach ( $order as $id ) {
            $id = is_string($id) || is_int($id) ? (string) $id : '';
            if ( $id === '' || isset($seen[$id]) ) {
                continue;
            }
            if ( $id === 'course_views' ) {
                $seen[$id] = true;
                foreach ( self::contentViewsFromPost($post) as $viewItem ) {
                    $vid = $viewItem['id'];
                    if ( isset($seen[$vid]) ) {
                        continue;
                    }
                    $seen[$vid] = true;
                    $items[] = $viewItem;
                }
                continue;
            }
            if ( $hasViews && in_array($id, self::contentViewIds(), true) ) {
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
     * Keep Map, Profile, and the courses widget when this login cannot edit them.
     *
     * The editor omits those rows for a non-Google login. A save must not
     * delete flags that a Google login still uses, and those rows stay in
     * their previous places while the posted items fill the other slots.
     *
     * @param array<string, mixed> $previous
     * @param array<string, mixed> $posted
     * @return array<string, mixed>
     */
    public static function retainSiteAccountItems(array $previous, array $posted) {
        if ( self::showsSiteAccountNav() ) {
            return $posted;
        }
        $previousItems = $previous['items'] ?? array();
        if ( ! is_array($previousItems) ) {
            $previousItems = array();
        }
        $keptById = array();
        foreach ( $previousItems as $item ) {
            if ( ! is_array($item) ) {
                continue;
            }
            $id = isset($item['id']) ? (string) $item['id'] : '';
            if ( $id !== '' && self::isSiteAccountNav($id) && ! isset($keptById[$id]) ) {
                $keptById[$id] = $item;
            }
        }
        if ( $keptById === array() ) {
            return $posted;
        }
        $postedItems = $posted['items'] ?? array();
        if ( ! is_array($postedItems) ) {
            $postedItems = array();
        }
        $editable = array();
        foreach ( $postedItems as $item ) {
            if ( ! is_array($item) ) {
                continue;
            }
            $id = isset($item['id']) ? (string) $item['id'] : '';
            if ( $id === '' || self::isSiteAccountNav($id) ) {
                continue;
            }
            $editable[] = $item;
        }
        $merged = array();
        $seen = array();
        $used = 0;
        foreach ( $previousItems as $item ) {
            if ( ! is_array($item) ) {
                continue;
            }
            $id = isset($item['id']) ? (string) $item['id'] : '';
            if ( $id !== '' && isset($keptById[$id]) ) {
                if ( ! isset($seen[$id]) ) {
                    $merged[] = $keptById[$id];
                    $seen[$id] = true;
                }
                continue;
            }
            if ( $used >= count($editable) ) {
                continue;
            }
            $next = $editable[$used];
            $used++;
            $nid = isset($next['id']) ? (string) $next['id'] : '';
            if ( $nid === '' || isset($seen[$nid]) ) {
                continue;
            }
            $merged[] = $next;
            $seen[$nid] = true;
        }
        while ( $used < count($editable) ) {
            $next = $editable[$used];
            $used++;
            $nid = isset($next['id']) ? (string) $next['id'] : '';
            if ( $nid === '' || isset($seen[$nid]) ) {
                continue;
            }
            $merged[] = $next;
            $seen[$nid] = true;
        }
        $posted['items'] = $merged;
        if ( isset($previous['home']) && ! isset($posted['home']) ) {
            $posted['home'] = $previous['home'];
        }
        return self::normalize($posted);
    }

    /**
     * Map, Profile, and the courses widget are the site account.
     * A Plus launch has no such account yet.
     */
    private static function showsSiteAccountNav() {
        return Courses::isGoogleLoginSession();
    }

    /**
     * @param string $id
     */
    private static function isSiteAccountNav($id) {
        return $id === 'map' || $id === 'profile' || $id === 'courses_widget';
    }

    /**
     * @param string $id
     */
    private static function skipSiteAccountNav($id) {
        return self::isSiteAccountNav($id) && ! self::showsSiteAccountNav();
    }

    /**
     * @param array<string, mixed> $doc
     * @return MenuSet
     */
    public static function compile(array $doc, $context_id) {
        $cid = (int) $context_id;
        $doc = self::normalize($doc);
        $testViews = array();
        if ( ! self::isCourseInstructor() ) {
            $doc = self::applyContentView($doc, self::studentContentView($cid));
        } else {
            list($doc, $testViews) = self::instructorContentViews($doc);
        }
        if ( ReqScope::isAnonymousLessonView() ) {
            return self::anonymousLessonMenu();
        }
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
        $leaveBuilt = null;
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
            if ( $id === 'leave_course' ) {
                $leaveBuilt = $built;
                continue;
            }
            if ( $id === 'logout' ) {
                $logoutBuilt = $built;
                continue;
            }
            $submenu->addLink($built['label'], $built['href'], false, $built['attr'] ?? false);
        }
        if ( $exitBuilt !== null ) {
            $submenu->addLink($exitBuilt['label'], $exitBuilt['href'], false, $exitBuilt['attr'] ?? false);
        }
        if ( $leaveBuilt !== null ) {
            $submenu->addLink($leaveBuilt['label'], $leaveBuilt['href'], false, $leaveBuilt['attr'] ?? false);
        }
        foreach ( $testViews as $testId ) {
            $built = self::linkEntry($known, array('id' => $testId), $prefix);
            if ( $built === null ) {
                continue;
            }
            $submenu->addLink(self::contentTestLabel($testId), $built['href'], false, false);
        }
        if ( self::maySeeSettings() ) {
            if ( Manifest::idForContext($cid) > 0 ) {
                $submenu->addLink(__('Student View'), $prefix.StudentView::ROUTE);
            }
            $submenu->addLink(__('Settings'), $prefix.'/settings');
        }
        if ( $logoutBuilt !== null ) {
            $submenu->addLink($logoutBuilt['label'], $logoutBuilt['href']);
        }
        $set->addRight(self::avatarTrigger(), $submenu, false);

        return $set;
    }

    /**
     * Top bar for a reader who is not in the course.
     *
     * No course tools, no avatar, no exit. The upper-left brand is the
     * only link. On a page it says Lessons and returns to this course's
     * lessons. On the lessons, when the catalog is on, it says Catalog and
     * returns to this course's catalog page. Otherwise the text is
     * $CFG->servicename and it goes to the app home.
     *
     * @return MenuSet
     */
    private static function anonymousLessonMenu() {
        global $CFG;
        $set = new MenuSet();
        if ( self::anonymousPageRequest() ) {
            $href = Courses::lessonsUrl(ReqScope::anonymousLessonContextId());
            $label = __('Lessons');
        } else {
            $href = self::anonymousCatalogHref();
            if ( $href !== '' ) {
                $label = __('Catalog');
            } else {
                if ( isset($CFG) && is_object($CFG) && method_exists($CFG, 'getHomeUrl') ) {
                    $href = $CFG->getHomeUrl();
                }
                $label = 'Home';
                if ( isset($CFG->servicename) && is_string($CFG->servicename) && trim($CFG->servicename) !== '' ) {
                    $label = trim($CFG->servicename);
                }
            }
        }
        $set->setHome(htmlspecialchars($label, ENT_QUOTES, 'UTF-8'), $href);
        return $set;
    }

    /**
     * This course's catalog page when the site publishes a catalog, otherwise empty.
     *
     * A course that is not in the catalog falls back to the catalog listing.
     *
     * @return string
     */
    private static function anonymousCatalogHref() {
        if ( ! Catalog::showCourseCatalog() && ! Site::useCatalog() ) {
            return '';
        }
        $catalog_id = CatalogRepository::publishedIdForContext(ReqScope::anonymousLessonContextId());
        return Catalog::catalogDetailUrl($catalog_id);
    }

    /**
     * True when this anonymous read is a course page.
     *
     * @return bool
     */
    private static function anonymousPageRequest() {
        $uri = (isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI']))
            ? $_SERVER['REQUEST_URI']
            : '';
        $path = parse_url($uri, PHP_URL_PATH);
        if ( ! is_string($path) || $path === '' ) {
            return false;
        }
        return (bool) preg_match('#/pages(?:/|$)#', $path);
    }

    /**
     * @param array<string, array<string, mixed>> $known
     * @param array<string, mixed> $item
     */
    private static function addPlacement(MenuSet $set, array $known, array $item, $placement, $prefix) {
        $id = $item['id'];
        if ( self::skipSiteAccountNav($id) ) {
            return;
        }
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
        $attr = $built['attr'] ?? false;
        if ( $placement === 'left' ) {
            $set->addLeft($built['label'], $built['href'], false, $attr);
        } else {
            $set->addRight($built['label'], $built['href'], false, $attr);
        }
    }

    /**
     * @param array<string, array<string, mixed>> $known
     * @param array<string, mixed> $item
     * @return array{label: string, href: string, attr?: string}|null
     */
    private static function linkEntry(array $known, array $item, $prefix) {
        $id = $item['id'] ?? '';
        if ( self::skipSiteAccountNav($id) ) {
            return null;
        }
        $entry = $known[$id] ?? null;
        if ( ! is_array($entry) || $entry['kind'] !== 'link' ) {
            return null;
        }
        if ( $id === 'login' && ReqScope::isLoggedInLegacy() ) {
            return null;
        }
        if ( ( $id === 'logout' || $id === 'profile' ) && ! ReqScope::isLoggedInLegacy() ) {
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
        if ( $id === 'leave_course' ) {
            return array(
                'label' => __($entry['label']),
                'href' => '#',
                'attr' => 'onclick="window.close();setTimeout(function(){if(!window.closed){window.alert(\'This browser kept the tab open. You can close it yourself.\');}},200);return false;"',
            );
        }
        $route = $entry['route'];
        if ( ! empty($entry['site']) ) {
            $href = self::siteUrl($route);
        } else {
            $href = $prefix.$route;
        }
        $label = $entry['label'];
        if ( isset($item['nav_label']) && is_string($item['nav_label']) && $item['nav_label'] !== '' ) {
            $label = $item['nav_label'];
        }
        return array('label' => __($label), 'href' => $href);
    }

    /**
     * Home label seeded from a course short title: the first six characters.
     *
     * Empty, missing, or a slice that normalizes away (blank or the word Home)
     * returns null so the course keeps the default Home label.
     *
     * @param mixed $shortTitle
     * @return string|null
     */
    public static function homeLabelFromShortTitle($shortTitle) {
        if ( ! is_string($shortTitle) ) {
            return null;
        }
        $shortTitle = trim($shortTitle);
        if ( $shortTitle === '' ) {
            return null;
        }
        $n = self::INITIAL_HOME_CHARS;
        if ( function_exists('mb_substr') ) {
            $slice = mb_substr($shortTitle, 0, $n);
        } else {
            $slice = substr($shortTitle, 0, $n);
        }
        return self::sanitizeHomeLabel($slice);
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

    /**
     * The signed-in student's saved view, or empty when they have not picked one.
     *
     * @param int $context_id
     * @return string
     */
    private static function studentContentView($context_id) {
        $context_id = (int) $context_id;
        $user_id = ReqScope::loggedInUserId();
        if ( $context_id < 1 || $user_id < 1 ) {
            return '';
        }
        try {
            $row = ContextUserSettings::load($context_id, $user_id);
        } catch ( \Throwable $e ) {
            return '';
        }
        return ContextUserSettings::contentView($row['settings']);
    }

    /**
     * Card View, Content, and Outline from the Lessons group.
     * They share one placement. A view is stored when it is default, available, or an instructor test link.
     *
     * @param array<string, mixed> $post
     * @return list<array<string, mixed>>
     */
    private static function contentViewsFromPost(array $post) {
        $nav = $post['nav'] ?? array();
        $flags = (is_array($nav) && isset($nav['course_views']) && is_array($nav['course_views']))
            ? $nav['course_views']
            : array();
        $left = ! empty($flags['left']);
        $right = ! empty($flags['right']);
        $dropdown = ! empty($flags['dropdown']);
        if ( ! $left && ! $right && ! $dropdown ) {
            return array();
        }
        $defaults = $post['view_default'] ?? array();
        $available = $post['view_available'] ?? array();
        $tests = $post['view_test'] ?? array();
        if ( ! is_array($defaults) ) {
            $defaults = array();
        }
        if ( ! is_array($available) ) {
            $available = array();
        }
        if ( ! is_array($tests) ) {
            $tests = array();
        }
        $instructor = ! empty($flags['instructor']);
        $items = array();
        foreach ( self::contentViewIds() as $id ) {
            $isDefault = ! empty($defaults[$id]);
            $isAvailable = ! empty($available[$id]);
            $isTest = ! empty($tests[$id]);
            if ( ! $isDefault && ! $isAvailable && ! $isTest ) {
                continue;
            }
            $item = array('id' => $id);
            if ( $left ) {
                $item['left'] = true;
            }
            if ( $right ) {
                $item['right'] = true;
            }
            if ( $dropdown ) {
                $item['dropdown'] = true;
            }
            if ( $instructor ) {
                $item['instructor'] = true;
            }
            if ( $isDefault ) {
                $item['default'] = true;
            }
            if ( $isAvailable ) {
                $item['available'] = true;
            }
            if ( $isTest ) {
                $item['test'] = true;
            }
            $items[] = $item;
        }
        $hasDefault = false;
        foreach ( $items as $item ) {
            if ( ! empty($item['default']) ) {
                $hasDefault = true;
                break;
            }
        }
        if ( $hasDefault ) {
            return $items;
        }
        foreach ( $items as $i => $item ) {
            if ( $item['id'] === 'lessons' ) {
                $items[$i]['default'] = true;
                return $items;
            }
        }
        $card = array('id' => 'lessons', 'default' => true);
        if ( $left ) {
            $card['left'] = true;
        }
        if ( $right ) {
            $card['right'] = true;
        }
        if ( $dropdown ) {
            $card['dropdown'] = true;
        }
        if ( $instructor ) {
            $card['instructor'] = true;
        }
        array_unshift($items, $card);
        return $items;
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

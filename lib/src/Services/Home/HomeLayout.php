<?php

namespace Tsugi\Services\Home;

use Tsugi\Core\Context;
use Tsugi\Services\Files\FileRepository;

/**
 * Ordered blocks an instructor chooses for the course home page.
 *
 * Stored on lti_context.json under the "home" key so other context JSON stays put.
 * Hero, calendar, the due-date list, and a note to self appear once. Page and
 * file blocks can repeat. Order is the array order.
 */
class HomeLayout {

    const JSON_KEY = 'home';

    const HERO_PLACES = array('center', 'left', 'right', 'none');

    const FILE_PLACES = array('left', 'right', 'center', 'inline');

    /**
     * @return array{blocks:list<array<string,mixed>>}
     */
    public static function defaultDocument() {
        return array(
            'blocks' => array(
                array('type' => 'hero', 'place' => 'right'),
                array('type' => 'page', 'page_id' => 0),
                array('type' => 'file', 'path' => '', 'place' => 'inline'),
                array('type' => 'calendar', 'on' => false),
                array('type' => 'dues', 'on' => false),
                array('type' => 'notes', 'on' => false),
            ),
        );
    }

    /**
     * @param mixed $doc
     * @return array{blocks:list<array<string,mixed>>}
     */
    public static function normalize($doc) {
        if ( is_string($doc) ) {
            $decoded = json_decode($doc, true);
            $doc = is_array($decoded) ? $decoded : array();
        }
        if ( ! is_array($doc) ) {
            $doc = array();
        }
        $raw = isset($doc['blocks']) && is_array($doc['blocks']) ? $doc['blocks'] : array();
        $seen = array();
        $blocks = array();
        $pageCount = 0;
        $fileCount = 0;
        foreach ( $raw as $row ) {
            $block = self::cleanBlock($row);
            if ( $block === null ) {
                continue;
            }
            if ( $block['type'] === 'page' ) {
                $pageCount++;
                $blocks[] = $block;
                continue;
            }
            if ( $block['type'] === 'file' ) {
                $fileCount++;
                $blocks[] = $block;
                continue;
            }
            if ( isset($seen[$block['type']]) ) {
                continue;
            }
            $seen[$block['type']] = true;
            $blocks[] = $block;
        }
        foreach ( self::defaultDocument()['blocks'] as $fallback ) {
            if ( $fallback['type'] === 'page' ) {
                if ( $pageCount === 0 ) {
                    $blocks[] = $fallback;
                }
                continue;
            }
            if ( $fallback['type'] === 'file' ) {
                if ( $fileCount === 0 ) {
                    $blocks[] = $fallback;
                }
                continue;
            }
            if ( isset($seen[$fallback['type']]) ) {
                continue;
            }
            $blocks[] = $fallback;
        }
        return array('blocks' => $blocks);
    }

    /**
     * @param mixed $row
     * @return array<string,mixed>|null
     */
    private static function cleanBlock($row) {
        if ( ! is_array($row) || empty($row['type']) ) {
            return null;
        }
        $type = (string) $row['type'];
        if ( $type === 'hero' ) {
            return array('type' => 'hero', 'place' => self::heroPlace($row));
        }
        if ( $type === 'page' ) {
            $page_id = isset($row['page_id']) ? (int) $row['page_id'] : 0;
            if ( $page_id < 0 ) {
                $page_id = 0;
            }
            return array('type' => 'page', 'page_id' => $page_id);
        }
        if ( $type === 'file' ) {
            $path = FileRepository::normalizeFilePath(isset($row['path']) ? (string) $row['path'] : '');
            return array(
                'type' => 'file',
                'path' => is_string($path) ? $path : '',
                'place' => self::filePlace($row['place'] ?? 'inline'),
            );
        }
        if ( $type === 'calendar' || $type === 'dues' || $type === 'notes' ) {
            return array('type' => $type, 'on' => self::flag($row['on'] ?? false));
        }
        return null;
    }

    /**
     * left, right, center, or inline (in the normal flow).
     *
     * @param mixed $place
     */
    private static function filePlace($place) {
        $place = is_string($place) ? $place : '';
        return in_array($place, self::FILE_PLACES, true) ? $place : 'inline';
    }

    /**
     * center, left (float left), right (float right), or none.
     *
     * Older layouts stored on=true for "show the image". Those become float right.
     *
     * @param array<string,mixed> $row
     */
    private static function heroPlace(array $row) {
        $place = isset($row['place']) ? (string) $row['place'] : '';
        if ( in_array($place, self::HERO_PLACES, true) ) {
            return $place;
        }
        if ( self::flag($row['on'] ?? false) || $place === '1' ) {
            return 'right';
        }
        return 'none';
    }

    /**
     * @param mixed $value
     */
    private static function flag($value) {
        return $value === true || $value === 1 || $value === '1';
    }

    /**
     * Stable id for a block the instructor placed, or null for an empty slot.
     *
     * Hero, calendar, due dates, and the note use their type. A page is
     * page:{id}. A file is file:{path}.
     *
     * @param array<string,mixed> $block
     * @return string|null
     */
    public static function blockKey(array $block) {
        $type = (string) ($block['type'] ?? '');
        if ( $type === 'hero' ) {
            return self::heroPlace($block) === 'none' ? null : 'hero';
        }
        if ( $type === 'page' ) {
            $page_id = isset($block['page_id']) ? (int) $block['page_id'] : 0;
            return $page_id > 0 ? ('page:'.$page_id) : null;
        }
        if ( $type === 'file' ) {
            $path = FileRepository::normalizeFilePath(isset($block['path']) ? (string) $block['path'] : '');
            if ( ! is_string($path) || $path === '' ) {
                return null;
            }
            return 'file:'.$path;
        }
        if ( ($type === 'calendar' || $type === 'dues' || $type === 'notes') && self::flag($block['on'] ?? false) ) {
            return $type;
        }
        return null;
    }

    /**
     * @param array<string,mixed> $doc
     * @return array<string,mixed>|null
     */
    public static function block(array $doc, $type) {
        $doc = self::normalize($doc);
        foreach ( $doc['blocks'] as $block ) {
            if ( $block['type'] === $type ) {
                return $block;
            }
        }
        return null;
    }

    /**
     * @param array<string,mixed> $post
     * @param list<int> $pageIds Pages in this course
     * @param list<string> $filePaths Non-private course file paths
     * @return array{blocks:list<array<string,mixed>>}
     */
    public static function fromPost(array $post, array $pageIds, array $filePaths = array()) {
        $allowed = array();
        foreach ( $pageIds as $id ) {
            $allowed[(int) $id] = true;
        }
        $postedPages = $post['page_id'] ?? array();
        if ( ! is_array($postedPages) ) {
            $postedPages = array($postedPages);
        }
        $pageIdsInOrder = array();
        foreach ( $postedPages as $posted ) {
            $page_id = (int) $posted;
            if ( $page_id < 1 || empty($allowed[$page_id]) ) {
                $page_id = 0;
            }
            $pageIdsInOrder[] = $page_id;
        }
        $allowedFiles = array();
        foreach ( $filePaths as $path ) {
            $norm = FileRepository::normalizeFilePath(is_string($path) ? $path : '');
            if ( is_string($norm) ) {
                $allowedFiles[$norm] = true;
            }
        }
        $postedFiles = $post['file_path'] ?? array();
        if ( ! is_array($postedFiles) ) {
            $postedFiles = array($postedFiles);
        }
        $postedPlaces = $post['file_place'] ?? array();
        if ( ! is_array($postedPlaces) ) {
            $postedPlaces = array($postedPlaces);
        }
        $hero = self::heroPlace(array('place' => $post['hero'] ?? 'none'));
        $calendar = ! empty($post['show_calendar']);
        $dues = ! empty($post['show_dues']);
        $notes = ! empty($post['show_notes']);
        $order = isset($post['home_order']) && is_array($post['home_order']) ? $post['home_order'] : array();
        if ( count($order) === 0 ) {
            $order = array('hero', 'page', 'file', 'calendar', 'dues', 'notes');
        }
        $raw = array();
        $pageCursor = 0;
        $fileCursor = 0;
        foreach ( $order as $type ) {
            $type = (string) $type;
            if ( $type === 'page' ) {
                $page_id = $pageIdsInOrder[$pageCursor] ?? 0;
                $pageCursor++;
                $raw[] = array('type' => 'page', 'page_id' => $page_id);
            } elseif ( $type === 'file' ) {
                $path = FileRepository::normalizeFilePath(isset($postedFiles[$fileCursor]) ? (string) $postedFiles[$fileCursor] : '');
                if ( ! is_string($path) || empty($allowedFiles[$path]) ) {
                    $path = '';
                }
                $place = $postedPlaces[$fileCursor] ?? 'inline';
                $fileCursor++;
                $raw[] = array('type' => 'file', 'path' => $path, 'place' => $place);
            } elseif ( $type === 'hero' ) {
                $raw[] = array('type' => 'hero', 'place' => $hero);
            } elseif ( $type === 'calendar' || $type === 'dues' || $type === 'notes' ) {
                $on = false;
                if ( $type === 'calendar' ) {
                    $on = $calendar;
                } elseif ( $type === 'dues' ) {
                    $on = $dues;
                } else {
                    $on = $notes;
                }
                $raw[] = array('type' => $type, 'on' => $on);
            }
        }
        return self::normalize(array('blocks' => $raw));
    }

    /**
     * @param int $context_id
     * @return array{blocks:list<array<string,mixed>>}
     */
    public static function load($context_id) {
        $context_id = (int) $context_id;
        if ( $context_id < 1 ) {
            return self::defaultDocument();
        }
        $context = new Context();
        $context->id = $context_id;
        $raw = $context->getJsonKeyLegacy(self::JSON_KEY, null);
        if ( ! is_array($raw) ) {
            return self::defaultDocument();
        }
        return self::normalize($raw);
    }

    /**
     * @param int $context_id
     * @param array<string,mixed> $doc
     */
    public static function save($context_id, array $doc) {
        $context_id = (int) $context_id;
        if ( $context_id < 1 ) {
            return;
        }
        $context = new Context();
        $context->id = $context_id;
        $context->setJsonKey(self::JSON_KEY, self::normalize($doc));
    }
}

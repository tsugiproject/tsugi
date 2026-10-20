<?php

namespace Tsugi\Services\Badges;

use Tsugi\Core\ReqScope;
use Tsugi\Services\Files\FileRepository;
use Tsugi\Services\Lessons\LessonsNormalize;
use Tsugi\Services\Lessons\LessonsService;
use Tsugi\Services\Outbound\LtiContentService;
use Tsugi\Services\Quiz1\Quiz1Repository;

/**
 * Badge definitions stored on a course manifest, with pictures in Files Public.
 *
 * The badge code is the PNG file name without .png. It must match the
 * pattern baked into an assertion id: letters, numbers, and underscores.
 */
class CourseBadges {

    /** @var array<string, list<array{id:string,title:string,anchor:?string,graded:bool,published:bool}>> */
    private static $courseItemsCache = array();

    /**
     * @param mixed $image
     */
    public static function isPublicImage($image): bool {
        return self::canonicalPublicPath($image) !== null;
    }

    /**
     * Public/hello.png with a canonical Public folder name, or null.
     *
     * @param mixed $image
     */
    public static function canonicalPublicPath($image): ?string {
        if ( ! is_string($image) || $image === '' ) {
            return null;
        }
        $path = FileRepository::normalizeFilePath($image);
        if ( $path === null || ! FileRepository::isPublicPath($path) ) {
            return null;
        }
        $parts = explode('/', $path);
        if ( count($parts) < 2 ) {
            return null;
        }
        $parts[0] = FileRepository::PUBLIC_FOLDER;
        return implode('/', $parts);
    }

    /**
     * File name without .png. Empty when the name is not a lowercase .png.
     *
     * @param mixed $image
     */
    public static function codeFromImage($image): string {
        if ( ! is_string($image) || $image === '' ) {
            return '';
        }
        $base = basename(str_replace('\\', '/', $image));
        if ( strlen($base) < 5 || substr($base, -4) !== '.png' ) {
            return '';
        }
        return substr($base, 0, -4);
    }

    public static function isSafeCode(string $code): bool {
        return $code !== '' && preg_match('/^[A-Za-z0-9_]+$/', $code) === 1;
    }

    /**
     * @param mixed $image
     */
    public static function imageMatchesCode($image, string $code): bool {
        return $code !== '' && self::codeFromImage($image) === $code;
    }

    /**
     * Absolute URL for a Public file, or the site badge folder for a file name.
     *
     * A Public path is turned into that course's content hash. The same
     * Public/name.png in two courses must not share one URL.
     *
     * @param object|string $badge Badge object or image path
     */
    public static function displayUrl($badge, int $contextId = 0): string {
        global $CFG;

        $image = '';
        if ( is_object($badge) && isset($badge->image) && is_string($badge->image) ) {
            $image = $badge->image;
        } elseif ( is_string($badge) ) {
            $image = $badge;
        }
        $public = self::canonicalPublicPath($image);
        if ( $public !== null ) {
            if ( $contextId < 1 ) {
                $contextId = ReqScope::currentContextId();
            }
            $href = null;
            if ( $contextId > 0 ) {
                $link = FileRepository::ensureLink($contextId);
                $row = $link ? FileRepository::getFileRowByPath($public, $link, $contextId) : null;
                if ( is_array($row) && isset($row['file_sha256']) ) {
                    $href = FileRepository::downloadHrefForSha256($row['file_sha256']);
                }
            }
            if ( is_string($href) && $href !== '' && isset($CFG->wwwroot) && is_string($CFG->wwwroot) ) {
                return rtrim($CFG->wwwroot, '/') . $href;
            }
            return '';
        }
        if ( $image !== '' && isset($CFG->badge_url) && is_string($CFG->badge_url) && $CFG->badge_url !== '' ) {
            return rtrim($CFG->badge_url, '/') . '/' . ltrim($image, '/');
        }
        return '';
    }

    /**
     * PNG bytes for a Public file in this course.
     *
     * A zero context id looks up the public path in any course.
     */
    public static function bytes(string $image, int $contextId = 0): ?string {
        $path = self::canonicalPublicPath($image);
        if ( $path === null ) {
            return null;
        }
        $row = null;
        if ( $contextId > 0 ) {
            $link = FileRepository::ensureLink($contextId);
            if ( $link ) {
                $row = FileRepository::getFileRowByPath($path, $link, $contextId);
            }
            if ( ! is_array($row) ) {
                return null;
            }
        } else {
            $row = FileRepository::getPublicFileByPath($path);
            if ( is_array($row) && isset($row['context_id']) ) {
                $contextId = (int) $row['context_id'];
            }
        }
        if ( ! is_array($row) || empty($row['file_sha256']) ) {
            return null;
        }
        $payload = FileRepository::readExportPayload($row['file_sha256'], $contextId);
        if ( ! is_array($payload) || ! isset($payload['bytes']) || ! is_string($payload['bytes']) || $payload['bytes'] === '' ) {
            return null;
        }
        return $payload['bytes'];
    }

    /**
     * Copy a Public PNG into badge_images.
     *
     * Null means the file is missing. image_id is null when the file is over
     * the cap; sha256 is still set so the row is not retried.
     *
     * @return array{image_id:?int,sha256:string}|null
     */
    public static function storedLink(string $image, int $contextId): ?array {
        $bytes = self::bytes($image, $contextId);
        if ( ! is_string($bytes) || $bytes === '' ) {
            return null;
        }
        if ( strlen($bytes) > BadgeService::MAX_IMAGE_BYTES ) {
            return array(
                'image_id' => null,
                'sha256' => hash('sha256', $bytes),
            );
        }
        $stored = BadgeService::storeImageBytes($bytes);
        if ( ! is_array($stored) ) {
            return null;
        }
        return array(
            'image_id' => $stored['image_id'],
            'sha256' => $stored['sha256'],
        );
    }

    /**
     * PNG files already in this course's Public folder.
     *
     * @return list<array{path:string,code:string,safe:bool}>
     */
    public static function publicPngs(int $contextId): array {
        $link = FileRepository::ensureLink($contextId);
        if ( ! $link ) {
            return array();
        }
        $out = array();
        foreach ( FileRepository::allItems($link, $contextId) as $row ) {
            $meta = FileRepository::decodeMeta($row);
            if ( $meta['kind'] !== FileRepository::KIND_FILE || ! FileRepository::isPublicPath($meta['folder']) ) {
                continue;
            }
            $path = FileRepository::pathFromFileRow($row);
            $canonical = self::canonicalPublicPath($path);
            if ( $canonical === null ) {
                continue;
            }
            $code = self::codeFromImage($canonical);
            if ( $code === '' ) {
                continue;
            }
            $out[] = array(
                'path' => $canonical,
                'code' => $code,
                'safe' => self::isSafeCode($code),
            );
        }
        usort($out, function ($a, $b) {
            return strcmp($a['path'], $b['path']);
        });
        return $out;
    }

    /**
     * Published graded items a badge can count.
     *
     * A native quiz is included once it is published. Its grade is stored
     * under the quiz link key. A content launch is included when students
     * can see it and it records a grade.
     *
     * @return array<string, string> resource_link_id => title
     */
    public static function assignmentChoices(LessonsService $lessons): array {
        $choices = array();
        foreach ( self::courseItems($lessons) as $item ) {
            if ( empty($item['published']) || empty($item['graded']) || $item['id'] === '' ) {
                continue;
            }
            if ( isset($choices[$item['id']]) ) {
                continue;
            }
            $choices[$item['id']] = $item['title'] !== '' ? $item['title'] : $item['id'];
        }
        return $choices;
    }

    /**
     * Title, module anchor, and whether this id counts toward a badge.
     *
     * An id that is not in the course stays graded so an older badge still
     * reads a stored score.
     *
     * @return array{title:string,anchor:?string,graded:bool,known:bool}
     */
    public static function placement(LessonsService $lessons, string $id, int $contextId = 0): array {
        $id = trim($id);
        foreach ( self::courseItems($lessons, $contextId) as $item ) {
            if ( $item['id'] !== $id ) {
                continue;
            }
            return array(
                'title' => $item['title'] !== '' ? $item['title'] : $id,
                'anchor' => $item['anchor'],
                'graded' => $item['graded'],
                'known' => true,
            );
        }
        return array(
            'title' => $id !== '' ? $id : 'Assignment',
            'anchor' => null,
            'graded' => true,
            'known' => false,
        );
    }

    /**
     * Every gradable lesson item, published or not.
     *
     * @return list<array{id:string,title:string,anchor:?string,graded:bool,published:bool}>
     */
    private static function courseItems(LessonsService $lessons, int $contextId = 0): array {
        if ( $contextId < 1 ) {
            $contextId = ReqScope::currentContextId();
        }
        $cacheKey = spl_object_id($lessons) . ':' . $contextId;
        if ( isset(self::$courseItemsCache[$cacheKey]) ) {
            return self::$courseItemsCache[$cacheKey];
        }
        $items = array();
        $push = function ($id, $title, $anchor, $graded, $published) use (&$items) {
            $id = trim((string) $id);
            if ( $id === '' ) {
                return;
            }
            $items[] = array(
                'id' => $id,
                'title' => is_string($title) ? $title : '',
                'anchor' => (is_string($anchor) && $anchor !== '') ? $anchor : null,
                'graded' => (bool) $graded,
                'published' => (bool) $published,
            );
        };
        $record = function ($item, $anchor) use ($lessons, $contextId, $push) {
            if ( is_array($item) ) {
                $item = (object) $item;
            }
            if ( ! is_object($item) ) {
                return;
            }
            if ( LessonsNormalize::isNativeQuiz($item) ) {
                $quizId = LessonsNormalize::quizIdOf($item);
                if ( $quizId < 1 ) {
                    return;
                }
                $push(
                    Quiz1Repository::resourceLinkId($quizId, $contextId),
                    self::itemTitle($item),
                    $anchor,
                    true,
                    $lessons->quiz1IsPublished($quizId)
                );
                return;
            }
            $contentId = isset($item->content_id) ? (int) $item->content_id : 0;
            if ( $contentId > 0 ) {
                $row = LtiContentService::find($contextId, $contentId);
                if ( ! is_array($row) ) {
                    return;
                }
                $title = self::itemTitle($item);
                if ( $title === '' && isset($row['title']) && is_string($row['title']) ) {
                    $title = $row['title'];
                }
                $graded = (int) ($row['send_grade'] ?? 0) === 1 || ! empty($row['link_id']);
                $push(
                    isset($row['resource_link_id']) ? $row['resource_link_id'] : '',
                    $title,
                    $anchor,
                    $graded,
                    LtiContentService::studentVisible($contextId, $contentId) === true
                );
                return;
            }
            if ( ! LessonsNormalize::isAssignmentLti($item) ) {
                return;
            }
            $rlid = (string) $item->resource_link_id;
            $push(
                $rlid,
                self::itemTitle($item),
                $anchor,
                LessonsService::ltiLaunchIsGraded($item),
                $lessons->resourceLinkPublished($rlid) === true
            );
        };

        foreach ( $lessons->getLaunches() as $launch ) {
            $record($launch, null);
        }
        $doc = $lessons->lessons;
        if ( isset($doc->modules) && is_iterable($doc->modules) ) {
            foreach ( $doc->modules as $mod ) {
                $anchor = (is_object($mod) && isset($mod->anchor)) ? (string) $mod->anchor : null;
                if ( isset($mod->lti) && is_iterable($mod->lti) ) {
                    foreach ( $mod->lti as $lti ) {
                        $record($lti, $anchor);
                    }
                }
                if ( isset($mod->items) && is_iterable($mod->items) ) {
                    foreach ( $mod->items as $item ) {
                        $record($item, $anchor);
                    }
                }
            }
        }
        self::$courseItemsCache[$cacheKey] = $items;
        return $items;
    }

    /**
     * @param object $item
     */
    private static function itemTitle($item): string {
        if ( isset($item->title) && is_string($item->title) && $item->title !== '' ) {
            return $item->title;
        }
        return '';
    }

    /**
     * Apply an authoring post to the manifest badges array.
     *
     * @param mixed $badges
     * @param array<string, mixed> $post
     * @return list<array<string, mixed>>
     */
    public static function apply($badges, array $post, LessonsService $lessons, int $contextId): array {
        $clean = self::listFromDocument($badges);
        $action = isset($post['badge_action']) ? (string) $post['badge_action'] : '';
        $index = self::postIndex($post);

        if ( $action === 'delete' ) {
            if ( $index === null || ! isset($clean[$index]) ) {
                throw new \InvalidArgumentException('Badge not found.');
            }
            array_splice($clean, $index, 1);
            return array_values($clean);
        }

        if ( $action === 'move' ) {
            if ( $index === null || ! isset($clean[$index]) ) {
                throw new \InvalidArgumentException('Badge not found.');
            }
            $dir = (isset($post['move_dir']) && $post['move_dir'] === 'up') ? -1 : 1;
            $swap = $index + $dir;
            if ( $swap < 0 || $swap >= count($clean) ) {
                return $clean;
            }
            $item = $clean[$index];
            $clean[$index] = $clean[$swap];
            $clean[$swap] = $item;
            return array_values($clean);
        }

        if ( $action !== 'save' ) {
            throw new \InvalidArgumentException('Unknown badge action.');
        }

        $one = self::fromEditor($post, $lessons, $contextId);
        $code = self::codeFromImage($one['image']);
        foreach ( $clean as $i => $existing ) {
            if ( $index !== null && $i === $index ) {
                continue;
            }
            $existingImage = isset($existing['image']) ? $existing['image'] : '';
            if ( self::codeFromImage($existingImage) === $code ) {
                throw new \InvalidArgumentException('Another badge already uses that image name.');
            }
        }

        if ( $index === null ) {
            $clean[] = $one;
        } else {
            if ( ! isset($clean[$index]) ) {
                throw new \InvalidArgumentException('Badge not found.');
            }
            $merged = $clean[$index];
            $merged['title'] = $one['title'];
            $merged['image'] = $one['image'];
            $merged['threshold'] = $one['threshold'];
            $merged['assignments'] = $one['assignments'];
            if ( ! empty($one['completion']) ) {
                $merged['completion'] = true;
            } else {
                unset($merged['completion']);
            }
            $clean[$index] = $merged;
        }
        return array_values($clean);
    }

    /**
     * @param mixed $badges
     * @return list<array<string, mixed>>
     */
    public static function listFromDocument($badges): array {
        if ( ! is_array($badges) ) {
            return array();
        }
        $out = array();
        foreach ( $badges as $badge ) {
            if ( is_object($badge) ) {
                $badge = json_decode(json_encode($badge), true);
            }
            if ( is_array($badge) ) {
                $out[] = $badge;
            }
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $post
     * @return array{title:string,image:string,threshold:float,assignments:list<string>,completion?:bool}
     */
    private static function fromEditor(array $post, LessonsService $lessons, int $contextId): array {
        $title = isset($post['title']) && is_string($post['title']) ? trim($post['title']) : '';
        if ( $title === '' ) {
            throw new \InvalidArgumentException('Title is required.');
        }

        $image = isset($post['image']) && is_string($post['image']) ? $post['image'] : '';
        $path = self::canonicalPublicPath($image);
        $code = $path === null ? '' : self::codeFromImage($path);
        if ( $path === null || ! self::isSafeCode($code) ) {
            throw new \InvalidArgumentException('Choose a Public PNG whose name uses only letters, numbers, and underscores, like hello_world.png.');
        }
        $known = false;
        foreach ( self::publicPngs($contextId) as $png ) {
            if ( $png['path'] === $path && $png['safe'] ) {
                $known = true;
                break;
            }
        }
        if ( ! $known ) {
            throw new \InvalidArgumentException('That image is not in Files Public. Upload the PNG there first.');
        }

        $percentRaw = isset($post['threshold_percent']) ? $post['threshold_percent'] : '';
        if ( ! is_numeric($percentRaw) ) {
            throw new \InvalidArgumentException('Percent required must be a whole number from 0 to 100.');
        }
        $asFloat = (float) $percentRaw;
        if ( $asFloat < 0 || $asFloat > 100 || abs($asFloat - round($asFloat)) > 0.001 ) {
            throw new \InvalidArgumentException('Percent required must be a whole number from 0 to 100.');
        }
        $percent = (int) round($asFloat);

        $choices = self::assignmentChoices($lessons);
        $posted = isset($post['assignments']) && is_array($post['assignments']) ? $post['assignments'] : array();
        $assignments = array();
        foreach ( $posted as $rlid ) {
            if ( ! is_string($rlid) && ! is_numeric($rlid) ) {
                throw new \InvalidArgumentException('Unknown assignment.');
            }
            $rlid = (string) $rlid;
            if ( ! isset($choices[$rlid]) && ! self::placement($lessons, $rlid)['known'] ) {
                throw new \InvalidArgumentException('Unknown assignment.');
            }
            if ( ! in_array($rlid, $assignments, true) ) {
                $assignments[] = $rlid;
            }
        }

        $one = array(
            'title' => $title,
            'image' => $path,
            'threshold' => $percent / 100,
            'assignments' => $assignments,
        );
        if ( ! empty($post['completion']) ) {
            $one['completion'] = true;
        }
        return $one;
    }

    /**
     * @param array<string, mixed> $post
     */
    private static function postIndex(array $post): ?int {
        if ( ! isset($post['badge_index']) ) {
            return null;
        }
        $raw = trim((string) $post['badge_index']);
        if ( $raw === '' || ! ctype_digit($raw) ) {
            return null;
        }
        return (int) $raw;
    }
}

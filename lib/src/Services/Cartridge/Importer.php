<?php

namespace Tsugi\Services\Cartridge;

use Tsugi\Services\Files\FileRepository;
use Tsugi\Services\Pages\PageRepository;
use Tsugi\Core\LTIX;
use Tsugi\Core\Manifest;
use Tsugi\Services\Quiz1\Qti12Importer;
use Tsugi\Services\Quiz1\Quiz1Repository;
use Tsugi\Services\Lessons\LessonsNormalize;
use Tsugi\Util\CC;

/**
 * Import a Common Cartridge into a course: Files, Pages, Quiz1, LTI links, Lessons.
 */
class Importer {

    /**
     * @param array{modules?:list<string>, replace?:bool} $options
     *        modules = organization keys from Package::describeModules();
     *        replace = wipe pages, files, quizzes, resource links, results, and lessons first
     * @return array<string, mixed> Persisted cc_import row
     */
    public static function run($path, $context_id, $user_id, array $options = array()) {
        $context_id = (int) $context_id;
        $user_id = (int) $user_id;
        if ( $context_id < 1 ) {
            throw new ImportException('A course is required to import a cartridge.');
        }
        $pkg = Package::open($path);
        try {
            if ( isset($options['modules']) && is_array($options['modules']) && count($options['modules']) > 0 ) {
                $pkg->restrictToModules($options['modules']);
            }
            if ( ! empty($options['replace']) ) {
                Wipe::beforeImport($context_id, $user_id);
            }
            return self::runPackage($pkg, $context_id, $user_id);
        } finally {
            $pkg->close();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function runPackage(Package $pkg, $context_id, $user_id) {
        LTIX::getConnection();
        $existing = Store::loadObjects($context_id);
        $knownIds = array();
        foreach ( $existing as $row ) {
            $knownIds[] = (int) ($row['object_id'] ?? 0);
        }
        $session = new Session($context_id, $user_id, $existing);
        $meta = array(
            'filename' => $pkg->path !== '' ? basename($pkg->path) : '',
            'title' => $pkg->title,
            'manifest_identifier' => $pkg->identifier,
            'cc_version' => $pkg->schemaversion,
            'zip_sha256' => ($pkg->path !== '' && is_readable($pkg->path))
                ? (string) hash_file('sha256', $pkg->path)
                : '',
        );
        $session->begin($meta);

        $actions = array();
        $lessonsByRes = array();
        $fileHrefToLocal = array();

        $importable = $pkg->importableResources();
        usort($importable, function ($a, $b) {
            $ka = Fingerprint::localKind((string) ($a['type'] ?? ''), (string) ($a['href'] ?? '')) === 'file' ? 0 : 1;
            $kb = Fingerprint::localKind((string) ($b['type'] ?? ''), (string) ($b['href'] ?? '')) === 'file' ? 0 : 1;
            return $ka <=> $kb;
        });

        foreach ( $importable as $res ) {
            $id = (string) $res['identifier'];
            $type = (string) $res['type'];
            $href = (string) $res['href'];
            $extra = array(
                'title' => (string) ($res['title'] ?? ''),
                'item_identifier' => (string) ($res['item_identifier'] ?? ''),
                'identifiers' => array('href' => $href),
            );
            try {
                $hash = Fingerprint::resource($pkg, $res);
                $d = $session->consider($id, $type, $hash, $extra);
                $kind = Fingerprint::localKind($type, $href);
                $localId = null;
                $localKey = null;
                $lesson = null;
                if ( $d->isNew() || $d->isCopy() ) {
                    $made = self::materialize($pkg, $res, $kind, $context_id, $user_id, $fileHrefToLocal, $d->isCopy());
                    $kind = $made['local_kind'];
                    $localId = $made['local_id'];
                    $localKey = $made['local_key'];
                    $lesson = $made['lesson'];
                    if ( $kind === 'file' && isset($made['sha256']) ) {
                        $fileHrefToLocal[$href] = $made;
                    }
                } else if ( $d->isDuplicate() && is_array($d->object) ) {
                    $lesson = self::lessonFromObject($d->object, (string) ($res['title'] ?? ''));
                    if ( (string) ($d->object['local_kind'] ?? '') === 'file' ) {
                        $sha = (string) ($d->object['local_key'] ?? '');
                        $dupHref = FileRepository::downloadHrefForSha256($sha);
                        if ( is_string($dupHref) && $dupHref !== '' ) {
                            $fileHrefToLocal[$href] = array(
                                'sha256' => $sha,
                                'href' => $dupHref,
                            );
                        }
                    }
                }
                $session->record($d, $kind, $localId, $localKey);
                $actions[$id] = $d->action;
                if ( is_array($lesson) ) {
                    $lessonsByRes[$id] = $lesson;
                }
            } catch ( \Throwable $e ) {
                $session->error($e->getMessage(), array(
                    'resource_identifier' => $id,
                    'resource_type' => $type,
                    'title' => $extra['title'],
                ));
                $actions[$id] = Matcher::ACTION_ERROR;
            }
        }

        $newModules = self::buildLessonsModules($pkg, $session, $actions, $lessonsByRes);
        $session->finish();
        $row = Store::persistSession($session, $knownIds);
        if ( count($newModules) > 0 ) {
            try {
                self::appendModules($context_id, $user_id, $pkg->title, $newModules, $meta['filename']);
            } catch ( \Throwable $e ) {
                throw new ImportException(
                    'Imported items but could not update Lessons: '.$e->getMessage(),
                    0,
                    $e
                );
            }
        }
        return $row;
    }

    /**
     * @param array<string, mixed> $res
     * @param array<string, array<string, mixed>> $fileHrefToLocal
     * @return array{local_kind:string,local_id:?int,local_key:?string,lesson:?array,sha256?:string,href?:string}
     */
    private static function materialize(Package $pkg, array $res, $kind, $context_id, $user_id, array $fileHrefToLocal, $isCopy = false) {
        $type = (string) $res['type'];
        $href = (string) $res['href'];
        $title = (string) ($res['title'] ?? '');
        if ( $title === '' ) {
            $title = basename(str_replace('\\', '/', $href));
        }

        if ( $kind === 'file' ) {
            $bytes = $pkg->readHref($href);
            $filename = basename(str_replace('\\', '/', $href));
            $folder = self::filesFolderFromHref($href);
            $stored = FileRepository::importBytes($bytes, $filename, $folder, self::mimeForName($filename));
            $lesson = array(
                'type' => LessonsNormalize::TYPE_FILE,
                'title' => $title,
                'filename' => $stored['filename'],
                'sha256' => $stored['sha256'],
                'href' => $stored['href'],
            );
            return array(
                'local_kind' => 'file',
                'local_id' => $stored['file_id'],
                'local_key' => $stored['sha256'],
                'lesson' => $lesson,
                'sha256' => $stored['sha256'],
                'href' => $stored['href'],
            );
        }

        if ( $kind === 'page' ) {
            $html = $pkg->readHref($href);
            $html = self::rewriteFileBase($html, $fileHrefToLocal);
            $body = self::htmlBody($html);
            $key = pathinfo(basename(str_replace('\\', '/', $href)), PATHINFO_FILENAME);
            $page = PageRepository::importHtml($title, $body, $key, $context_id, $user_id);
            $lesson = array(
                'type' => LessonsNormalize::TYPE_HTML_PAGE,
                'title' => $page['title'],
                'logical_key' => $page['logical_key'],
                'page_id' => $page['page_id'],
            );
            $pageHref = PageRepository::hrefForLogicalKey($page['logical_key']);
            if ( is_string($pageHref) && $pageHref !== '' ) {
                $lesson['href'] = $pageHref;
            }
            return array(
                'local_kind' => 'page',
                'local_id' => $page['page_id'],
                'local_key' => $page['logical_key'],
                'lesson' => $lesson,
            );
        }

        if ( $kind === 'quiz' ) {
            $xml = $pkg->readHref($href);
            list($quiz, $warnings) = Qti12Importer::import($xml);
            unset($warnings);
            if ( $quiz->title === '' ) {
                $quiz->title = $title;
            }
            $quiz->context_id = $context_id;
            $quiz->user_id = $user_id;
            Quiz1Repository::insertQuiz($quiz);
            $lesson = array(
                'type' => LessonsNormalize::TYPE_QUIZ,
                'title' => $quiz->title,
                'quiz_id' => $quiz->id,
            );
            return array(
                'local_kind' => 'quiz',
                'local_id' => $quiz->id,
                'local_key' => null,
                'lesson' => $lesson,
            );
        }

        if ( $kind === 'web_link' ) {
            $xml = $pkg->readHref($href);
            $url = Fingerprint::webLinkHref($xml);
            $lesson = array(
                'type' => LessonsNormalize::TYPE_WEB_LINK,
                'title' => $title,
                'href' => $url,
            );
            $target = CC::lessonTargetFromWindowTarget(Fingerprint::webLinkWindowTarget($xml));
            if ( $target !== null ) {
                $lesson['target'] = $target;
            }
            return array(
                'local_kind' => 'web_link',
                'local_id' => null,
                'local_key' => $url,
                'lesson' => $lesson,
            );
        }

        if ( $kind === 'lti_link' ) {
            $xml = $pkg->readHref($href);
            $xmlTitle = self::firstElementText($xml, 'title');
            if ( $xmlTitle !== '' && ($title === '' || $title === basename(str_replace('\\', '/', $href))) ) {
                $title = $xmlTitle;
            }
            $launch = self::ltiLaunch($xml);
            $linkKey = (string) $res['identifier'];
            if ( $isCopy ) {
                $linkKey .= '-'.bin2hex(random_bytes(3));
            }
            $linkId = self::insertLtiLink($context_id, $linkKey, $title, $launch);
            $lesson = array(
                'type' => LessonsNormalize::TYPE_LTI,
                'title' => $title,
                'launch' => $launch,
                'resource_link_id' => $linkKey,
            );
            return array(
                'local_kind' => 'lti_link',
                'local_id' => $linkId,
                'local_key' => $linkKey,
                'lesson' => $lesson,
            );
        }

        if ( $kind === 'discussion' ) {
            $xml = $pkg->readHref($href);
            $text = self::topicText($xml);
            $lesson = array(
                'type' => LessonsNormalize::TYPE_DISCUSSION,
                'title' => $title,
                'description' => $text,
            );
            return array(
                'local_kind' => 'discussion',
                'local_id' => null,
                'local_key' => (string) $res['identifier'],
                'lesson' => $lesson,
            );
        }

        return array(
            'local_kind' => $kind,
            'local_id' => null,
            'local_key' => null,
            'lesson' => array('type' => 'web_link', 'title' => $title, 'href' => $href),
        );
    }

    /**
     * @param array<string, string> $actions
     * @param array<string, array<string, mixed>> $lessonsByRes
     * @return list<array<string, mixed>>
     */
    private static function buildLessonsModules(Package $pkg, Session $session, array $actions, array $lessonsByRes) {
        $out = array();
        foreach ( $pkg->modules as $mod ) {
            $items = array();
            foreach ( $mod['items'] as $item ) {
                if ( ! empty($item['heading']) ) {
                    $title = (string) ($item['title'] ?? '');
                    $hid = (string) ($item['identifier'] ?? '');
                    if ( $hid === '' || $title === '' ) {
                        continue;
                    }
                    $hash = Fingerprint::hashString('heading|'.$title);
                    $d = $session->consider($hid, 'heading', $hash, array('title' => $title));
                    $session->record($d, 'heading', null, $hid);
                    if ( $d->isDuplicate() ) {
                        continue;
                    }
                    $heading = array(
                        'type' => LessonsNormalize::TYPE_HEADING,
                        'title' => $title,
                    );
                    self::applyOrgItemExtras($heading, $item);
                    $items[] = $heading;
                    continue;
                }
                $ref = (string) ($item['identifierref'] ?? '');
                if ( $ref === '' || ! isset($lessonsByRes[$ref]) ) {
                    continue;
                }
                $action = $actions[$ref] ?? '';
                if ( $action !== Matcher::NEW && $action !== Matcher::COPY ) {
                    continue;
                }
                $lesson = $lessonsByRes[$ref];
                if ( isset($item['title']) && is_string($item['title']) && $item['title'] !== '' ) {
                    $lesson['title'] = $item['title'];
                }
                self::applyOrgItemExtras($lesson, $item);
                $items[] = $lesson;
            }
            if ( count($items) < 1 ) {
                continue;
            }
            $title = (string) ($mod['title'] ?? 'Imported');
            $row = array(
                'title' => $title,
                'anchor' => self::anchor($title, (string) ($mod['identifier'] ?? '')),
                'description' => '',
                'items' => $items,
            );
            self::applyOrgItemExtras($row, $mod);
            if ( ! isset($row['description']) || ! is_string($row['description']) ) {
                $row['description'] = '';
            }
            $out[] = $row;
        }
        return $out;
    }

    /**
     * Copy LOM extras from a parsed organization row onto a lesson/module array.
     *
     * @param array<string, mixed> $dest
     * @param array<string, mixed> $row
     */
    private static function applyOrgItemExtras(array &$dest, array $row) {
        $desc = self::orgItemDescription($row);
        if ( $desc !== null ) {
            $dest['description'] = $desc;
        }
        $icon = CC::organizationIcon($row);
        if ( $icon !== null ) {
            $dest['icon'] = $icon;
        }
        $hrefSource = CC::organizationHrefSource($row);
        if ( $hrefSource !== null ) {
            $dest['href_source'] = $hrefSource;
        }
        if ( isset($row['target']) && is_string($row['target']) && $row['target'] !== '' ) {
            $dest['target'] = $row['target'];
        }
    }

    /**
     * LOM description from a parsed organization module or item, if present.
     *
     * @param array<string, mixed> $row
     * @return string|null
     */
    private static function orgItemDescription(array $row) {
        return CC::organizationDescription($row);
    }

    /**
     * @param list<array<string, mixed>> $newModules
     */
    private static function appendModules($context_id, $user_id, $cartridgeTitle, array $newModules, $filename) {
        $current = Manifest::currentDocument();
        if ( ! is_array($current) || ! isset($current['json']) ) {
            throw new ImportException('This course has no lessons document to import into.');
        }
        $doc = json_decode($current['json'], true);
        if ( ! is_array($doc) ) {
            throw new ImportException('Course lessons JSON could not be read.');
        }
        if ( ! Manifest::documentIsV2($doc) ) {
            throw new ImportException('Cartridge import needs a Lessons v2 course.');
        }
        $modules = isset($doc['modules']) && is_array($doc['modules']) ? $doc['modules'] : array();
        if ( self::isEmptyStarter($modules) ) {
            $modules = $newModules;
        } else {
            $used = array();
            foreach ( $modules as $m ) {
                if ( isset($m['anchor']) && is_string($m['anchor']) ) {
                    $used[$m['anchor']] = true;
                }
            }
            foreach ( $newModules as $mod ) {
                $anchor = $mod['anchor'];
                $n = 2;
                while ( isset($used[$anchor]) ) {
                    $anchor = $mod['anchor'].'-'.$n;
                    $n++;
                }
                $mod['anchor'] = $anchor;
                $used[$anchor] = true;
                $modules[] = $mod;
            }
        }
        $doc['modules'] = $modules;
        if ( (! isset($doc['title']) || $doc['title'] === '' || $doc['title'] === 'Untitled Course')
            && is_string($cartridgeTitle) && $cartridgeTitle !== '' ) {
            $doc['title'] = preg_replace('/\s+import$/i', '', $cartridgeTitle) ?? $cartridgeTitle;
        }
        $comment = 'Common Cartridge import';
        if ( is_string($filename) && $filename !== '' ) {
            $comment .= ': '.$filename;
        }
        Manifest::saveNewVersion($context_id, $doc, $user_id, $comment);
    }

    /**
     * @param list<mixed> $modules
     */
    private static function isEmptyStarter(array $modules) {
        if ( count($modules) < 1 ) {
            return true;
        }
        if ( count($modules) !== 1 ) {
            return false;
        }
        $m = $modules[0];
        if ( ! is_array($m) ) {
            return false;
        }
        $items = $m['items'] ?? array();
        return is_array($items) && count($items) < 1;
    }

    /**
     * @param array<string, mixed> $object
     * @return array<string, mixed>|null
     */
    private static function lessonFromObject(array $object, $title) {
        $kind = (string) ($object['local_kind'] ?? '');
        $title = $title !== '' ? $title : 'Item';
        if ( $kind === 'file' ) {
            $sha = (string) ($object['local_key'] ?? '');
            $href = FileRepository::downloadHrefForSha256($sha);
            return array(
                'type' => LessonsNormalize::TYPE_FILE,
                'title' => $title,
                'sha256' => $sha,
                'href' => is_string($href) ? $href : '',
            );
        }
        if ( $kind === 'page' ) {
            $logical_key = (string) ($object['local_key'] ?? '');
            $lesson = array(
                'type' => LessonsNormalize::TYPE_HTML_PAGE,
                'title' => $title,
                'logical_key' => $logical_key,
                'page_id' => isset($object['local_id']) ? (int) $object['local_id'] : 0,
            );
            $pageHref = PageRepository::hrefForLogicalKey($logical_key);
            if ( is_string($pageHref) && $pageHref !== '' ) {
                $lesson['href'] = $pageHref;
            }
            return $lesson;
        }
        if ( $kind === 'quiz' ) {
            return array(
                'type' => LessonsNormalize::TYPE_QUIZ,
                'title' => $title,
                'quiz_id' => isset($object['local_id']) ? (int) $object['local_id'] : 0,
            );
        }
        if ( $kind === 'web_link' ) {
            return array(
                'type' => LessonsNormalize::TYPE_WEB_LINK,
                'title' => $title,
                'href' => (string) ($object['local_key'] ?? ''),
            );
        }
        if ( $kind === 'lti_link' ) {
            return array(
                'type' => LessonsNormalize::TYPE_LTI,
                'title' => $title,
                'resource_link_id' => (string) ($object['local_key'] ?? ''),
            );
        }
        if ( $kind === 'discussion' ) {
            return array(
                'type' => LessonsNormalize::TYPE_DISCUSSION,
                'title' => $title,
            );
        }
        return null;
    }

    /**
     * Files-tool folder for a cartridge href. Keep the zip path under
     * web_resources (or the zip-relative path), minus the filename.
     * No extra Imported wrapper.
     *
     * @param mixed $href
     * @return string
     */
    public static function filesFolderFromHref($href) {
        $href = str_replace('\\', '/', (string) $href);
        $href = preg_replace('/[?#].*$/', '', $href);
        $href = trim((string) $href);
        if ( $href === '' ) {
            return '';
        }
        if ( preg_match('#(?:^|/)web_resources/(.+)$#', $href, $m) ) {
            $rel = $m[1];
        } else {
            $rel = ltrim($href, '/');
        }
        $rel = trim($rel, '/');
        if ( $rel === '' ) {
            return '';
        }
        $slash = strrpos($rel, '/');
        if ( $slash === false ) {
            return '';
        }
        $folder = substr($rel, 0, $slash);
        $norm = FileRepository::normalizeFolder($folder);
        return ($norm === false) ? '' : $norm;
    }

    private static function mimeForName($filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $map = array(
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'html' => 'text/html',
            'htm' => 'text/html',
            'xml' => 'application/xml',
            'mp4' => 'video/mp4',
            'zip' => 'application/zip',
        );
        return $map[$ext] ?? 'application/octet-stream';
    }

    private static function htmlBody($html) {
        if ( preg_match('/<body\b[^>]*>(.*)<\/body>/is', (string) $html, $m) ) {
            return $m[1];
        }
        return (string) $html;
    }

    /**
     * @param array<string, array<string, mixed>> $fileHrefToLocal
     */
    private static function rewriteFileBase($html, array $fileHrefToLocal) {
        foreach ( $fileHrefToLocal as $zipHref => $stored ) {
            $local = isset($stored['href']) ? (string) $stored['href'] : '';
            if ( $local === '' ) {
                continue;
            }
            $zipHref = str_replace('\\', '/', (string) $zipHref);
            $stripped = $zipHref;
            if ( str_starts_with($stripped, 'web_resources/') ) {
                $stripped = substr($stripped, strlen('web_resources/'));
            }
            $html = str_replace('$IMS-CC-FILEBASE$/'.$stripped, $local, $html);
            $html = str_replace('$IMS-CC-FILEBASE$'.$stripped, $local, $html);
            $html = str_replace('$IMS-CC-FILEBASE$/'.$zipHref, $local, $html);
        }
        return $html;
    }

    private static function firstElementText($xml, $localName) {
        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $ok = $dom->loadXML((string) $xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if ( ! $ok || ! $dom->documentElement ) {
            return '';
        }
        foreach ( $dom->getElementsByTagName('*') as $el ) {
            if ( $el instanceof \DOMElement && $el->localName === $localName ) {
                return trim($el->textContent);
            }
        }
        return '';
    }

    private static function ltiLaunch($xml) {
        $key = Fingerprint::ltiKey($xml);
        if ( preg_match('/^launch\|([^|]*)/', $key, $m) ) {
            return $m[1];
        }
        return '';
    }

    private static function topicText($xml) {
        $key = Fingerprint::topicKey($xml);
        if ( preg_match('/\|text\|(.*)$/', $key, $m) ) {
            return $m[1];
        }
        return '';
    }

    private static function insertLtiLink($context_id, $resourceId, $title, $launch) {
        global $CFG, $PDOX;
        $key = $resourceId !== '' ? $resourceId : ('cc-'.bin2hex(random_bytes(8)));
        $json = json_encode(array('launch_url' => $launch, 'imported' => true));
        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}lti_link
                (link_key, link_sha256, title, context_id, path, json, created_at, updated_at)
             VALUES
                (:key, :sha, :title, :cid, :path, :json, NOW(), NOW())",
            array(
                ':key' => $key,
                ':sha' => lti_sha256($key),
                ':title' => $title,
                ':cid' => (int) $context_id,
                ':path' => $launch,
                ':json' => $json,
            )
        );
        return (int) $PDOX->lastInsertId();
    }

    private static function anchor($title, $identifier) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title) ?? '');
        $slug = trim($slug, '-');
        if ( $slug === '' ) {
            $slug = $identifier !== '' ? strtolower($identifier) : 'imported';
        }
        if ( strlen($slug) > 80 ) {
            $slug = substr($slug, 0, 80);
        }
        return $slug;
    }
}

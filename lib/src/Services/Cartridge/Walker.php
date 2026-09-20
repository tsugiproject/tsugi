<?php

namespace Tsugi\Services\Cartridge;

/**
 * Walk a cartridge zip into a Session (classify + log, no local pages yet).
 */
class Walker {

    /**
     * @param string $path Path to an .imscc / .zip
     * @param array<string, mixed> $meta Extra cc_import fields
     * @return array<string, mixed> Finished import row
     */
    public static function scan($path, Session $session, array $meta = array()) {
        $pkg = Package::open($path);
        try {
            return self::scanPackage($pkg, $session, $meta);
        } finally {
            $pkg->close();
        }
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    public static function scanPackage(Package $pkg, Session $session, array $meta = array()) {
        $defaults = array(
            'filename' => $pkg->path !== '' ? basename($pkg->path) : '',
            'title' => $pkg->title,
            'manifest_identifier' => $pkg->identifier,
            'cc_version' => $pkg->schemaversion,
            'zip_sha256' => ($pkg->path !== '' && is_readable($pkg->path))
                ? (string) hash_file('sha256', $pkg->path)
                : '',
        );
        $session->begin(array_merge($defaults, $meta));

        foreach ( $pkg->importableResources() as $res ) {
            $id = (string) ($res['identifier'] ?? '');
            $type = (string) ($res['type'] ?? '');
            $href = (string) ($res['href'] ?? '');
            $extra = array(
                'title' => (string) ($res['title'] ?? ''),
                'item_identifier' => (string) ($res['item_identifier'] ?? ''),
                'identifiers' => array(
                    'href' => $href,
                ),
            );
            try {
                $hash = Fingerprint::resource($pkg, $res);
                $d = $session->consider($id, $type, $hash, $extra);
                $session->record($d, Fingerprint::localKind($type, $href));
            } catch ( \Throwable $e ) {
                $session->error($e->getMessage(), array(
                    'resource_identifier' => $id,
                    'resource_type' => $type,
                    'item_identifier' => $extra['item_identifier'],
                    'title' => $extra['title'],
                ));
            }
        }

        return $session->finish();
    }
}

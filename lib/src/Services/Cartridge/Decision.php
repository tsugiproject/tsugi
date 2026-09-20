<?php

namespace Tsugi\Services\Cartridge;

/**
 * Result of matching one incoming cartridge resource to cc_object rows.
 */
class Decision {

    /** @var string Matcher::NEW, DUPLICATE, or COPY */
    public $action;

    /** @var array<string, mixed>|null Matching or ancestral cc_object row */
    public $object;

    /** @var string */
    public $resource_identifier = '';

    /** @var string */
    public $resource_type = '';

    /** @var string */
    public $content_hash = '';

    /** @var string */
    public $item_identifier = '';

    /** @var string */
    public $title = '';

    /** @var array<string, mixed> Extra ids (Canvas migration_id, etc.) */
    public $identifiers = array();

    public function isDuplicate() {
        return $this->action === Matcher::DUPLICATE;
    }

    public function isNew() {
        return $this->action === Matcher::NEW;
    }

    public function isCopy() {
        return $this->action === Matcher::COPY;
    }
}

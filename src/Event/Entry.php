<?php

namespace Tsugi\Event;

/**
 * A adaptive, multi-scale compressed time series of event counter buckets
 *
 */

class Entry {

    /**
     * The earliest time for the first bucket
     */
    public $timestart = 0;

    /**
     * The scale in seconds
     */
    public $scale = 0;

    /**
     * The length of the arrays
     */
    public $size = 0;

    /**
     * The offsets (0-65K)
     */
    public $offsets = null;

    /**
     * The offsets (0-65K)
     */
    public $buckets = null;

    public function __construct($start=null, $size=256, $scale=15*60)
    {
        $this->size = $size;
    }

}

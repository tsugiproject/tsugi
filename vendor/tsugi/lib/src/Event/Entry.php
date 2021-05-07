<?php

namespace Tsugi\Event;

use \Tsugi\Util\U;

/**
 * A adaptive, multi-scale compressed time series of event counter buckets
 *
 */

class Entry {

    /**
     * The earliest time for the first bucket - default first entry
     */
    public $timestart = null;

    /**
     * The scale in seconds
     */
    public $scale = 900;

    /**
     * The total number of clicks
     */
    public $total = 0;

    /**
     * The buckets key->value
     */
    public $buckets = array();

    public function __construct($timestart=null, $scale=900, $maxlen=1024) {
        $this->timestart = (int) ($timestart / $scale);
        $this->scale = $scale;
        $this->maxlen = $maxlen;
    }

    /**
     * Record a click...
     */
    public function click($time=null) {
        $this->total++;
        if ( ! $this->timestart ) {
            $this->timestart = (int) ( time() / $this->scale );
        }
        if ( ! $time ) $time = time();
        $time = (int) ($time / $this->scale);
        $delta = $time - $this->timestart;
        if ( $delta < 0 ) $delta = 0;  // 0 is OK
        if ( isset($this->buckets[$delta]) ) {
            $this->buckets[$delta]++;
        } else {
            $this->buckets[$delta] = 1;
        }
    }

    /**
     * Reconstruct to the actual times
     */
    public function reconstruct() {
        $retval = array();
        foreach($this->buckets as $k => $v) {
            $t = ($this->timestart + $k) * $this->scale;
            $retval[$t] = $v;
        }
        return $retval;
    }

    /**
     * Produce an view model of the entire object
     *
     * This is a "view model" in that it is intended to be easily used
     * in rendering situations.
     */
    public function viewModel() {
        $retval = new \stdClass();
        $buckets = $this->reconstruct();
        $retval->timestart = $this->timestart * $this->scale;
        $retval->width = $this->scale;
        $max = false;
        $maxt = false;
        $min = false;
        $rows = array();
        foreach($buckets as $k => $v ) {
            if ( $maxt === false || $k > $maxt ) $maxt = $k;
            if ( $max === false || $v > $max ) $max = $v;
            if ( $min === false || $v < $min ) $min = $v;
            $rows[] = array($k,$v);
        }
        $retval->rows = $rows;
        $retval->n = count($rows);
        $retval->max = $max;
        $retval->min = $min;
        $retval->timeend = $maxt;
        return $retval;
    }

    /**
     * Double the Scale - Return *copy of* new buckets
     */
    public function reScale($factor=2) {
        $newbuckets = array();
        $newscale = $this->scale * $factor;
        $newstart = (int) ($this->timestart / $factor);
        foreach($this->buckets as $k => $v) {
            $oldtime = ($this->timestart + $k) * $this->scale;
            $newposition = (int) ($oldtime / $newscale);
            $delta = $newposition - $newstart;
            if ( isset($newbuckets[$delta]) ) {
                $newbuckets[$delta] += $v;
            } else {
                $newbuckets[$delta] = $v;
            }
        }
        return $newbuckets;
    }

    /**
     * Optionally uncompress a serialized entry if it is compressed
     */
    public static function uncompressEntry($text) {
        if ( !is_string($text) ) return $text;
        $needed = false;
        for ($i = 0; $i < strlen($text); $i++){
            $ch = $text[$i];
            if ( $ch >= '0' && $ch <= '9' ) continue;
            if ( $ch == ':' || $ch == ',' || $ch == '=' ) continue;
            $needed = true;
            break;
        }
        if ( ! $needed ) return $text;
        return gzuncompress($text);
    }

    /**
     * De-Serialize scale, timestart and buckets (can be compressed)
     *
     * Does not set total
     */
    public function deSerialize($data) {
        $data = self::uncompressEntry($data);
        $chunks = False;
        if ( is_string($data) ) $chunks = explode(':',$data);
        // Nothing to see here - Might be null ... is OK
        if ( !is_array($chunks) || count($chunks) != 3 || !is_numeric($chunks[0]) || !is_numeric($chunks[1]) ) {
            $this->scale = 900;
            $this->timestart = 0;
            $this->buckets = array();
            return;
        }
        $this->scale = (int) $chunks[0];
        $this->timestart = (int) $chunks[1];
        $this->buckets = U::array_Integer_Deserialize($chunks[2]);
    }

    /**
     *  Serialize to a key=value pair
     */
    public function serialize($maxlength=null, $compress=false) {
        if ( ! $maxlength ) $maxlength = $this->maxlen;
        $retval = $this->scale . ':' .$this->timestart . ':' . U::array_Integer_Serialize($this->buckets);
        if ( strlen($retval) <= $maxlength ) return $retval;

        $allowCompress = $compress && function_exists('gzcompress') && function_exists('gzuncompress');

        // Stratey 1 - Compress if we are allowed - since it is lossless
        if ( $allowCompress ) {
            $compressed = gzcompress($retval);
            if ( strlen($retval) <= $maxlength ) return $retval;
        }
        
        // Strategy 2 - Double or Quadruple Scale as long as buckets < 24 hours
        if ( $this->scale < 24*60*60 ) { 
            foreach(array(2,4) as $factor ) {
                $newbuckets = $this->reScale($factor);
                $newscale = $this->scale * $factor;
                $newstart = (int) ($this->timestart / $factor);
                $retval = $newscale . ':' .$newstart . ':' . U::array_Integer_Serialize($newbuckets);

                if ( strlen($retval) <= $maxlength ) return $retval;

                if ( $allowCompress ) {
                    $compressed = gzcompress($retval);
                    if ( strlen($retval) <= $maxlength ) return $retval;
                }
            }
        }

        // Strategy 3, pitch data
        $oldbuckets = $this->buckets;
        $newstart = $this->timestart;
        $firstoffset = null;
        while( count($oldbuckets) > 4 ) {
            $fewerbuckets = array();
            $pos = 0;
            $fistoffset = null;
            foreach($oldbuckets as $oldoffset => $v ) {
                $pos++;
                if ( $pos == 1 ) continue; // first entry
                if ( $pos == 2 ) $firstoffset = $oldoffset;
                $newoffset = $oldoffset - $firstoffset;
                $fewerbuckets[$newoffset] = $oldbuckets[$oldoffset];
            }
            if ( count($oldbuckets)-1 != count($fewerbuckets) ) {
                throw new Exception('Internal failure during serialization');
            }
            $newstart = $newstart + $firstoffset;

            $retval = $this->scale . ':' .$newstart . ':' . U::array_Integer_Serialize($fewerbuckets);
            if ( strlen($retval) <= $maxlength ) return $retval;

            if ( $allowCompress ) {
                 $compressed = gzcompress($retval);
                 if ( strlen($retval) <= $maxlength ) return $retval;
            }
            $oldbuckets = $fewerbuckets;
        }

        // Strategy 4: Violate the max request :)
        return $retval;
    }

}

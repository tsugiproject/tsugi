<?php
namespace Tsugi\Image;

/**
 * This is a set of PNG related libraries
 */

class Png {

    // Adapted from
    // http://stackoverflow.com/questions/8842387/php-add-itxt-comment-to-a-png-image
    public static function addOrReplaceTextInPng($png,$key,$text) {
        $png = self::removeTextChunks($key, $png);
        $chunk = self::phpTextChunk($key,$text);
        $png2 = self::addPngChunk($chunk,$png);
        return $png2;
    }

    // Strip out any existing text chunks with a particular key
    public static function removeTextChunks($key,$png) {
        // Read the magic bytes and verify
        $retval = substr($png,0,8);
        $ipos = 8;
        if ($retval != "\x89PNG\x0d\x0a\x1a\x0a")
            throw new Exception('Is not a valid PNG image');

        // Loop through the chunks. Byte 0-3 is length, Byte 4-7 is type
        $chunkHeader = substr($png,$ipos,8);
        $ipos = $ipos + 8;
        while ($chunkHeader) {
            // Extract length and type from binary data
            $chunk = @unpack('Nsize/a4type', $chunkHeader);
            $skip = false;
            if ( $chunk['type'] == 'tEXt' ) {
                $data = substr($png,$ipos,$chunk['size']);
                $sections = explode("\0", $data);
                print_r($sections);
                if ( $sections[0] == $key ) $skip = true;
            }

            // Extract the data and the CRC
            $data = substr($png,$ipos,$chunk['size']+4);
            $ipos = $ipos + $chunk['size'] + 4;

            // Add in the header, data, and CRC
            if ( ! $skip ) $retval = $retval . $chunkHeader . $data;

            // Read next chunk header
            $chunkHeader = substr($png,$ipos,8);
            $ipos = $ipos + 8;
        }
        return $retval;
    }

    // creates a tEXt chunk with given key and text (iso8859-1)
    // ToDo: check that key length is less than 79 and that neither includes null bytes
    public static function phpTextChunk($key,$text) {
        $chunktype = "tEXt";
        $chunkdata = $key . "\0" . $text;
        $crc = pack("N", crc32($chunktype . $chunkdata));
        $len = pack("N",strlen($chunkdata));
        return $len .  $chunktype  . $chunkdata . $crc;
    }

    // inserts chunk before IEND chunk (last 12 bytes)
    public static function addPngChunk($chunk,$png) {
        $len = strlen($png);
        return substr($png,0,$len-12) . $chunk . substr($png,$len-12,12);
    }

}

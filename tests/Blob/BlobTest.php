<?php

use \Tsugi\Util\U;
use \Tsugi\Blob\BlobUtil;

class BlobTest extends \PHPUnit\Framework\TestCase
{
    // https://stackoverflow.com/questions/11267086/php-unlink-all-files-within-a-directory-and-then-deleting-that-directory
    public static function recursiveRemoveDirectory($directory)
    {
        // echo("\n");
        if ( ! file_exists($directory) ) return;
        foreach(glob("{$directory}/*") as $file)
        {
            if(is_dir($file)) {
                if ( ! preg_match('/^[0-9a-f][0-9a-f]/', basename($file)) ){
                    echo("Skipping folder $file\n");
                    continue;
                }
                // echo("Recurse folder $file\n");
                self::recursiveRemoveDirectory($file);
            } else {
                if ( ! preg_match('/^[0-9a-f]+$/', basename($file)) ){
                    echo("Skipping file $file\n");
                    continue;
                }
                // echo("Delete file $file\n");
                unlink($file);
            }
        }
        // echo("Remove directory $directory\n");
        rmdir($directory);
    }

    public function testGet() {
        $tmp = sys_get_temp_dir();
        if (strlen($tmp) > 1 && substr($tmp, -1) == '/') $tmp = substr($tmp,0,-1);
        $tmp .= '/tsugi_unit';
        if ( strlen($tmp) < 10 ) {
            die('Dangerous to delete '.$tmp);
        }
        self::recursiveRemoveDirectory($tmp);
        mkdir($tmp);
        $blob_dir = BlobUtil::getBlobFolder('abcdef0123456789', $tmp);
        $this->assertTrue(strpos($blob_dir, 'tsugi_unit/ab/cd') > 0, 'Blob folder path should contain expected subdirectory structure');
        $this->assertFalse(file_exists($blob_dir), 'Blob folder should not exist before mkdirSha256');
        $blob_dir = BlobUtil::mkdirSha256('abcdef0123456789', $tmp);
        $this->assertTrue(strpos($blob_dir, 'tsugi_unit/ab/cd') > 0, 'Blob folder path should contain expected subdirectory structure');
        $this->assertTrue(file_exists($blob_dir), 'Blob folder should exist after mkdirSha256');
        self::recursiveRemoveDirectory($tmp);
        $this->assertFalse(file_exists($blob_dir), 'Blob folder should not exist after cleanup');
    }

}

<?php

require_once "src/Config/ConfigInfo.php";

use Tsugi\Services\Cartridge\Pending;

class CartridgePendingTest extends \PHPUnit\Framework\TestCase
{
    /** @var mixed */
    private $originalSession;
    /** @var string */
    private $file;

    protected function setUp(): void
    {
        $this->originalSession = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array();
        $_SESSION = array();
        $this->file = tempnam(sys_get_temp_dir(), Pending::FILE_PREFIX);
        $this->assertNotFalse($this->file);
        file_put_contents($this->file, 'zip-bytes');
    }

    protected function tearDown(): void
    {
        Pending::clear();
        if ( is_string($this->file) && $this->file !== '' && file_exists($this->file) ) {
            @unlink($this->file);
        }
        $_SESSION = $this->originalSession;
    }

    public function testStashLoadAndClear()
    {
        $token = Pending::stash($this->file, 'course.imscc', 9, 4);
        $this->assertNotSame('', $token);
        $row = Pending::load(9, 4);
        $this->assertNotNull($row);
        $this->assertSame($token, $row['token']);
        $this->assertSame('course.imscc', $row['name']);
        $this->assertTrue(Pending::matches(9, 4, $token));
        $this->assertFalse(Pending::matches(9, 4, 'nope'));
        $this->assertNull(Pending::load(8, 4));
        $this->assertFileExists($this->file);
        Pending::clear();
        $this->assertFileDoesNotExist($this->file);
        $this->assertNull(Pending::load(9, 4));
    }

    public function testExpiredPendingIsCleared()
    {
        Pending::stash($this->file, 'course.imscc', 9, 4);
        $_SESSION[Pending::SESSION_KEY]['created'] = time() - Pending::TTL_SECONDS - 5;
        $this->assertNull(Pending::load(9, 4));
        $this->assertFileDoesNotExist($this->file);
    }

    public function testIsStashPathRejectsOtherTempFiles()
    {
        $other = tempnam(sys_get_temp_dir(), 'other');
        $this->assertNotFalse($other);
        try {
            $this->assertTrue(Pending::isStashPath($this->file));
            $this->assertFalse(Pending::isStashPath($other));
        } finally {
            @unlink($other);
        }
    }

    public function testSweepDeletesOldStashesFromAnyone()
    {
        $orphan = tempnam(sys_get_temp_dir(), Pending::FILE_PREFIX);
        $this->assertNotFalse($orphan);
        file_put_contents($orphan, 'old-zip');
        $this->assertTrue(touch($orphan, time() - Pending::TTL_SECONDS - 10));
        try {
            Pending::stash($this->file, 'fresh.imscc', 9, 4);
            $this->assertFileDoesNotExist($orphan);
            $this->assertFileExists($this->file);
            $this->assertNotNull(Pending::load(9, 4));
        } finally {
            if ( file_exists($orphan) ) {
                @unlink($orphan);
            }
        }
    }

    public function testLoadWithNoSessionStillSweepsOldFiles()
    {
        $orphan = tempnam(sys_get_temp_dir(), Pending::FILE_PREFIX);
        $this->assertNotFalse($orphan);
        file_put_contents($orphan, 'old-zip');
        $this->assertTrue(touch($orphan, time() - Pending::TTL_SECONDS - 10));
        try {
            $this->assertNull(Pending::load(9, 4));
            $this->assertFileDoesNotExist($orphan);
        } finally {
            if ( file_exists($orphan) ) {
                @unlink($orphan);
            }
        }
    }
}

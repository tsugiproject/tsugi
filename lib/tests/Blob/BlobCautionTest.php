<?php

use Tsugi\Blob\BlobUtil;

class BlobCautionTest extends \PHPUnit\Framework\TestCase
{
    public function testOrdinaryFilesNeedNoCaution()
    {
        $this->assertNull(BlobUtil::cautionFileKind('notes.pdf'));
        $this->assertNull(BlobUtil::cautionFileKind('photo.PNG'));
        $this->assertNull(BlobUtil::cautionFileKind('readme.txt'));
        $this->assertNull(BlobUtil::cautionFileKind('no-suffix'));
    }

    public function testHtmlAndZipAskForConfirmation()
    {
        $this->assertSame('HTML', BlobUtil::cautionFileKind('page.html'));
        $this->assertSame('HTML', BlobUtil::cautionFileKind('page.HTM'));
        $this->assertSame('ZIP', BlobUtil::cautionFileKind('week.zip'));
        $this->assertSame('TAR', BlobUtil::cautionFileKind('week.tar'));
        $this->assertSame('TAR', BlobUtil::cautionFileKind('week.tgz'));
        $this->assertSame('TAR', BlobUtil::cautionFileKind('week.tar.gz'));
        $this->assertSame('JS', BlobUtil::cautionFileKind('app.js'));
        $this->assertTrue(BlobUtil::cautionFileOpensInline('HTML'));
        $this->assertFalse(BlobUtil::cautionFileOpensInline('ZIP'));
    }

    public function testZipAndTarUploadsAreAllowed()
    {
        $this->assertTrue(BlobUtil::safeFileSuffix('week.zip'));
        $this->assertTrue(BlobUtil::safeFileSuffix('week.tar'));
        $this->assertTrue(BlobUtil::safeFileSuffix('week.tar.gz'));
        $this->assertTrue(BlobUtil::safeFileSuffix('week.tgz'));
        $this->assertTrue(BlobUtil::safeFileSuffix('week.TBZ'));
        $this->assertTrue(BlobUtil::safeFileSuffix('week.tar.bz2'));
        $this->assertTrue(BlobUtil::safeFileSuffix('notes.pdf'));
        $this->assertFalse(BlobUtil::safeFileSuffix('notes.gz'));
        $this->assertFalse(BlobUtil::safeFileSuffix('week.rar'));
        $this->assertFalse(BlobUtil::safeFileSuffix('app.js'));
    }

    public function testCompressedDownloadsUseOctetStream()
    {
        $this->assertSame('application/octet-stream', BlobUtil::downloadContentType('jquery.zip', 'application/zip'));
        $this->assertSame('application/octet-stream', BlobUtil::downloadContentType('week.tar', 'application/x-tar'));
        $this->assertSame('application/octet-stream', BlobUtil::downloadContentType('week.tar.gz', 'application/gzip'));
        $this->assertSame('application/octet-stream', BlobUtil::downloadContentType('week.tar.bz2', 'application/x-bzip2'));
        $this->assertSame('application/octet-stream', BlobUtil::downloadContentType('week.tgz', 'application/gzip'));
        $this->assertSame('application/octet-stream', BlobUtil::downloadContentType('week.tbz', 'application/x-bzip2'));
        $this->assertSame('application/pdf', BlobUtil::downloadContentType('notes.pdf', 'application/pdf'));
        $this->assertSame('text/html', BlobUtil::downloadContentType('page.html', 'text/html'));
        $this->assertSame('text/html', BlobUtil::downloadContentType('evil.html', 'text/javascript'));
        $this->assertSame('text/html', BlobUtil::downloadContentType('page.HTM', 'application/octet-stream'));
        $this->assertSame('image/svg+xml', BlobUtil::downloadContentType('icon.svg', 'text/plain'));
        $this->assertSame('text/plain', BlobUtil::downloadContentType('notes.txt', 'text/html'));
        $this->assertSame('text/plain', BlobUtil::downloadContentType('notes.txt', 'text/html; charset=UTF-8'));
        $this->assertSame('text/plain', BlobUtil::downloadContentType('notes.txt', 'image/svg+xml'));
        $this->assertSame('text/plain', BlobUtil::downloadContentType('notes.txt', 'text/javascript'));
        $this->assertSame('text/plain', BlobUtil::downloadContentType('notes.txt', 'application/xhtml+xml'));
        $this->assertSame('text/plain', BlobUtil::downloadContentType('readme.txt', 'text/plain'));
        $this->assertSame('text/plain', BlobUtil::downloadContentType('notes.pdf', 'text/html'));
        $this->assertSame('text/plain', BlobUtil::downloadContentType('app.js', 'application/javascript'));
    }
}

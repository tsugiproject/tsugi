<?php

use Tsugi\Blob\BlobUtil;
use Tsugi\Util\PDOX;

/**
 * Single-instance blob store: many blob_file rows, one blob_blob.
 *
 * SQLite in memory. No uploads and no dataroot. deleteBlob counts
 * blob_file rows by SHA-256 and removes blob_blob only on the last one.
 */
class BlobStoreTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $originalPDOX;

    protected function setUp(): void
    {
        global $CFG, $PDOX;

        $this->originalCFG = $CFG ?? null;
        $this->originalPDOX = $PDOX ?? null;

        $CFG = new \stdClass();
        $CFG->dbprefix = '';
        $CFG->DEVELOPER = true;

        if ( ! in_array('sqlite', \PDO::getAvailableDrivers(), true) ) {
            $this->markTestSkipped('pdo_sqlite is not available');
        }

        $PDOX = new PDOX('sqlite::memory:');
        $PDOX->exec(
            "CREATE TABLE blob_blob (
                blob_id INTEGER PRIMARY KEY AUTOINCREMENT,
                blob_sha256 CHAR(64) NOT NULL UNIQUE,
                content BLOB
            )"
        );
        $PDOX->exec(
            "CREATE TABLE blob_file (
                file_id INTEGER PRIMARY KEY AUTOINCREMENT,
                file_sha256 CHAR(64) NOT NULL,
                path TEXT,
                blob_id INTEGER
            )"
        );
    }

    protected function tearDown(): void
    {
        global $CFG, $PDOX;
        $CFG = $this->originalCFG;
        $PDOX = $this->originalPDOX;
    }

    public function testSharedBlobSurvivesUntilTheLastFileRow()
    {
        $sha = str_repeat('ab', 32);
        $blobId = $this->insertBlob($sha, 'shared-bytes');
        $first = $this->insertFile($sha, $blobId);
        $second = $this->insertFile($sha, $blobId);

        BlobUtil::deleteBlob($first, 'admin_bypass');

        $this->assertFalse($this->fileRowExists($first));
        $this->assertTrue($this->fileRowExists($second));
        $this->assertSame('shared-bytes', $this->blobContent($blobId));

        BlobUtil::deleteBlob($second, 'admin_bypass');

        $this->assertFalse($this->fileRowExists($second));
        $this->assertNull($this->blobContent($blobId));
    }

    public function testDeletingOneBlobLeavesADifferentBlob()
    {
        $keepSha = str_repeat('cd', 32);
        $dropSha = str_repeat('ef', 32);
        $keepId = $this->insertBlob($keepSha, 'keep-me');
        $dropId = $this->insertBlob($dropSha, 'drop-me');
        $keepFile = $this->insertFile($keepSha, $keepId);
        $dropFile = $this->insertFile($dropSha, $dropId);

        BlobUtil::deleteBlob($dropFile, 'admin_bypass');

        $this->assertFalse($this->fileRowExists($dropFile));
        $this->assertNull($this->blobContent($dropId));
        $this->assertTrue($this->fileRowExists($keepFile));
        $this->assertSame('keep-me', $this->blobContent($keepId));
    }

    public function testReferenceCountRisesAndFallsBeforeTheBlobIsRemoved()
    {
        $sha = str_repeat('12', 32);
        $blobId = $this->insertBlob($sha, 'counted-bytes');
        $files = array();

        $files[] = $this->insertFile($sha, $blobId);
        $files[] = $this->insertFile($sha, $blobId);
        $this->assertSame(2, $this->fileCount($sha));
        $this->assertSame('counted-bytes', $this->blobContent($blobId));

        $this->deleteOne($files);
        $this->assertSame(1, $this->fileCount($sha));
        $this->assertSame('counted-bytes', $this->blobContent($blobId));

        for ($i = 0; $i < 4; $i++) {
            $files[] = $this->insertFile($sha, $blobId);
        }
        $this->assertSame(5, $this->fileCount($sha));
        $this->assertSame('counted-bytes', $this->blobContent($blobId));

        $this->deleteOne($files);
        $this->deleteOne($files);
        $this->assertSame(3, $this->fileCount($sha));
        $this->assertSame('counted-bytes', $this->blobContent($blobId));

        $this->deleteOne($files);
        $this->assertSame(2, $this->fileCount($sha));
        $this->assertSame('counted-bytes', $this->blobContent($blobId));

        $this->deleteOne($files);
        $this->assertSame(1, $this->fileCount($sha));
        $this->assertSame('counted-bytes', $this->blobContent($blobId));

        $this->deleteOne($files);
        $this->assertSame(0, $this->fileCount($sha));
        $this->assertNull($this->blobContent($blobId));
    }

    /**
     * @param string $sha
     * @param string $content
     * @return int
     */
    private function insertBlob($sha, $content)
    {
        global $PDOX;
        $stmt = $PDOX->prepare(
            "INSERT INTO blob_blob (blob_sha256, content) VALUES (:SHA, :CONTENT)"
        );
        $stmt->execute(array(':SHA' => $sha, ':CONTENT' => $content));
        return (int) $PDOX->lastInsertId();
    }

    /**
     * @param string $sha
     * @param int $blobId
     * @return int
     */
    private function insertFile($sha, $blobId)
    {
        global $PDOX;
        $stmt = $PDOX->prepare(
            "INSERT INTO blob_file (file_sha256, path, blob_id) VALUES (:SHA, NULL, :BID)"
        );
        $stmt->execute(array(':SHA' => $sha, ':BID' => $blobId));
        return (int) $PDOX->lastInsertId();
    }

    /**
     * @param array $files
     */
    private function deleteOne(&$files)
    {
        $fileId = array_pop($files);
        BlobUtil::deleteBlob($fileId, 'admin_bypass');
        $this->assertFalse($this->fileRowExists($fileId));
    }

    /**
     * @param string $sha
     * @return int
     */
    private function fileCount($sha)
    {
        global $PDOX;
        $stmt = $PDOX->prepare("SELECT COUNT(*) AS count FROM blob_file WHERE file_sha256 = :SHA");
        $stmt->execute(array(':SHA' => $sha));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) $row['count'];
    }

    /**
     * @param int $fileId
     * @return bool
     */
    private function fileRowExists($fileId)
    {
        global $PDOX;
        $stmt = $PDOX->prepare("SELECT file_id FROM blob_file WHERE file_id = :FID");
        $stmt->execute(array(':FID' => $fileId));
        return $stmt->fetch(\PDO::FETCH_ASSOC) !== false;
    }

    /**
     * @param int $blobId
     * @return string|null
     */
    private function blobContent($blobId)
    {
        global $PDOX;
        $stmt = $PDOX->prepare("SELECT content FROM blob_blob WHERE blob_id = :BID");
        $stmt->execute(array(':BID' => $blobId));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ( $row === false ) {
            return null;
        }
        return (string) $row['content'];
    }
}

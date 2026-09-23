<?php

use Tsugi\Blob\BlobUtil;
use Tsugi\Util\PDOX;

/**
 * Replacing one blob_file row must not rewrite bytes still used by another row.
 */
class BlobReplaceTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $originalPDOX;
    private $originalContext;
    private $dataroot;

    protected function setUp(): void
    {
        global $CFG, $PDOX, $CONTEXT;

        $this->originalCFG = $CFG ?? null;
        $this->originalPDOX = $PDOX ?? null;
        $this->originalContext = $CONTEXT ?? null;
        $this->dataroot = null;

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
                content BLOB,
                created_at TEXT
            )"
        );
        $PDOX->exec(
            "CREATE TABLE blob_file (
                file_id INTEGER PRIMARY KEY AUTOINCREMENT,
                file_sha256 CHAR(64) NOT NULL,
                file_name TEXT,
                contenttype TEXT,
                path TEXT,
                blob_id INTEGER,
                bytelen INTEGER,
                context_id INTEGER
            )"
        );

        $CONTEXT = new \stdClass();
        $CONTEXT->id = 7;
        $CONTEXT->key = 'school';
    }

    protected function tearDown(): void
    {
        global $CFG, $PDOX, $CONTEXT;
        $CFG = $this->originalCFG;
        $PDOX = $this->originalPDOX;
        $CONTEXT = $this->originalContext;
        if ( is_string($this->dataroot) && is_dir($this->dataroot) ) {
            $this->removeTree($this->dataroot);
        }
    }

    public function testReplacingOneRowLeavesASharedBlob()
    {
        $old = 'shared-bytes';
        $sha = hash('sha256', $old);
        $blobId = $this->insertBlob($sha, $old);
        $keep = $this->insertFile($sha, $blobId, 'keep.png', 8);
        $change = $this->insertFile($sha, $blobId, 'farewell-lake.png', 7);

        $result = BlobUtil::replaceStoredFile($change, $this->tempFile('new-bytes'));

        $this->assertTrue($result);
        $this->assertSame($old, $this->blobContent($blobId));
        $this->assertSame($sha, $this->fileSha($keep));
        $this->assertSame($blobId, $this->fileBlobId($keep));
        $this->assertSame('farewell-lake.png', $this->fileName($change));
        $this->assertSame(hash('sha256', 'new-bytes'), $this->fileSha($change));
        $this->assertNotSame($blobId, $this->fileBlobId($change));
        $this->assertSame('new-bytes', $this->blobContent($this->fileBlobId($change)));
    }

    public function testReplacingTheLastReferenceRemovesTheOldBlob()
    {
        $old = 'only-bytes';
        $sha = hash('sha256', $old);
        $blobId = $this->insertBlob($sha, $old);
        $fileId = $this->insertFile($sha, $blobId, 'only.png', 7);

        $this->assertTrue(BlobUtil::replaceStoredFile($fileId, $this->tempFile('fresh-bytes')));

        $this->assertSame($fileId, $fileId);
        $this->assertNull($this->blobContent($blobId));
        $this->assertSame('fresh-bytes', $this->blobContent($this->fileBlobId($fileId)));
        $this->assertSame('only.png', $this->fileName($fileId));
    }

    public function testReplaceWithExistingBytesReusesThatBlob()
    {
        $keepSha = hash('sha256', 'keep-me');
        $dropSha = hash('sha256', 'drop-me');
        $keepId = $this->insertBlob($keepSha, 'keep-me');
        $dropId = $this->insertBlob($dropSha, 'drop-me');
        $keepFile = $this->insertFile($keepSha, $keepId, 'stay.png', 7);
        $dropFile = $this->insertFile($dropSha, $dropId, 'change.png', 7);

        $this->assertTrue(BlobUtil::replaceStoredFile($dropFile, $this->tempFile('keep-me')));

        $this->assertSame($keepId, $this->fileBlobId($dropFile));
        $this->assertSame($keepId, $this->fileBlobId($keepFile));
        $this->assertSame('keep-me', $this->blobContent($keepId));
        $this->assertNull($this->blobContent($dropId));
        $this->assertSame(1, $this->blobCount($keepSha));
    }

    public function testIdenticalBytesDoNotRemoveTheBlob()
    {
        $bytes = 'same-bytes';
        $sha = hash('sha256', $bytes);
        $blobId = $this->insertBlob($sha, $bytes);
        $fileId = $this->insertFile($sha, $blobId, 'same.png', 7);

        $this->assertTrue(BlobUtil::replaceStoredFile($fileId, $this->tempFile($bytes)));

        $this->assertSame($blobId, $this->fileBlobId($fileId));
        $this->assertSame($bytes, $this->blobContent($blobId));
    }

    public function testFolderRowIsRejected()
    {
        global $PDOX;
        $sha = hash('sha256', 'folder-marker');
        $stmt = $PDOX->prepare(
            "INSERT INTO blob_file (file_sha256, file_name, contenttype, context_id)
             VALUES (:SHA, 'Public', 'inode/directory', 7)"
        );
        $stmt->execute(array(':SHA' => $sha));
        $fileId = (int) $PDOX->lastInsertId();

        $this->assertSame(
            'Folders cannot be replaced',
            BlobUtil::replaceStoredFile($fileId, $this->tempFile('nope'))
        );
    }

    public function testDiskReplaceLeavesASharedFileInPlace()
    {
        global $CFG, $CONTEXT;
        $this->dataroot = sys_get_temp_dir().'/tsugi-blob-replace-'.bin2hex(random_bytes(4));
        mkdir($this->dataroot, 0770, true);
        $CFG->dataroot = $this->dataroot;
        $CONTEXT->key = 'school';

        $old = 'disk-shared';
        $sha = hash('sha256', $old);
        $folder = BlobUtil::mkdirSha256($sha);
        $this->assertIsString($folder);
        $path = $folder.'/'.$sha;
        file_put_contents($path, $old);

        $keep = $this->insertFile($sha, null, 'keep.png', 9, $path);
        $change = $this->insertFile($sha, null, 'change.png', 7, $path);

        $this->assertTrue(BlobUtil::replaceStoredFile($change, $this->tempFile('disk-new')));

        $this->assertSame($old, file_get_contents($path));
        $this->assertSame($path, $this->filePath($keep));
        $this->assertSame($sha, $this->fileSha($keep));
        $newSha = hash('sha256', 'disk-new');
        $this->assertSame($newSha, $this->fileSha($change));
        $newPath = $this->filePath($change);
        $this->assertIsString($newPath);
        $this->assertNotSame($path, $newPath);
        $this->assertSame('disk-new', file_get_contents($newPath));
        $this->assertSame('change.png', $this->fileName($change));
    }

    /**
     * @param string $bytes
     * @return string
     */
    private function tempFile($bytes)
    {
        $path = tempnam(sys_get_temp_dir(), 'blob');
        file_put_contents($path, $bytes);
        return $path;
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
            "INSERT INTO blob_blob (blob_sha256, content, created_at) VALUES (:SHA, :CONTENT, datetime('now'))"
        );
        $stmt->execute(array(':SHA' => $sha, ':CONTENT' => $content));
        return (int) $PDOX->lastInsertId();
    }

    /**
     * @param string $sha
     * @param int|null $blobId
     * @param string $name
     * @param int $contextId
     * @param string|null $path
     * @return int
     */
    private function insertFile($sha, $blobId, $name, $contextId, $path = null)
    {
        global $PDOX;
        $stmt = $PDOX->prepare(
            "INSERT INTO blob_file (file_sha256, file_name, contenttype, path, blob_id, context_id)
             VALUES (:SHA, :NAME, 'image/png', :PATH, :BID, :CID)"
        );
        $stmt->execute(array(
            ':SHA' => $sha,
            ':NAME' => $name,
            ':PATH' => $path,
            ':BID' => $blobId,
            ':CID' => $contextId,
        ));
        return (int) $PDOX->lastInsertId();
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

    /**
     * @param string $sha
     * @return int
     */
    private function blobCount($sha)
    {
        global $PDOX;
        $stmt = $PDOX->prepare("SELECT COUNT(*) AS count FROM blob_blob WHERE blob_sha256 = :SHA");
        $stmt->execute(array(':SHA' => $sha));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) $row['count'];
    }

    /**
     * @param int $fileId
     * @return string
     */
    private function fileSha($fileId)
    {
        return (string) $this->fileColumn($fileId, 'file_sha256');
    }

    /**
     * @param int $fileId
     * @return int
     */
    private function fileBlobId($fileId)
    {
        return (int) $this->fileColumn($fileId, 'blob_id');
    }

    /**
     * @param int $fileId
     * @return string
     */
    private function fileName($fileId)
    {
        return (string) $this->fileColumn($fileId, 'file_name');
    }

    /**
     * @param int $fileId
     * @return string|null
     */
    private function filePath($fileId)
    {
        $value = $this->fileColumn($fileId, 'path');
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param int $fileId
     * @param string $column
     * @return mixed
     */
    private function fileColumn($fileId, $column)
    {
        global $PDOX;
        $stmt = $PDOX->prepare("SELECT $column AS value FROM blob_file WHERE file_id = :FID");
        $stmt->execute(array(':FID' => $fileId));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row['value'];
    }

    /**
     * @param string $dir
     */
    private function removeTree($dir)
    {
        $items = scandir($dir);
        if ( ! is_array($items) ) {
            return;
        }
        foreach ( $items as $item ) {
            if ( $item === '.' || $item === '..' ) {
                continue;
            }
            $path = $dir.'/'.$item;
            if ( is_dir($path) ) {
                $this->removeTree($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}

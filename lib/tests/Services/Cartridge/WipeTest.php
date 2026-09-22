<?php

require_once "src/Config/ConfigInfo.php";

use Tsugi\Services\Cartridge\Wipe;
use Tsugi\Services\Files\FileRepository;

class CartridgeWipeTest extends \PHPUnit\Framework\TestCase
{
    public function testFolderRowFromContentType()
    {
        $this->assertTrue(Wipe::isFolderRow(array(
            'contenttype' => FileRepository::FOLDER_CONTENTTYPE,
            'file_name' => 'Imported',
        )));
        $this->assertFalse(Wipe::isFolderRow(array(
            'contenttype' => 'application/pdf',
            'file_name' => 'notes.pdf',
        )));
    }

    public function testFolderRowFromJsonKind()
    {
        $this->assertTrue(Wipe::isFolderRow(array(
            'contenttype' => 'application/octet-stream',
            'json' => json_encode(array('kind' => FileRepository::KIND_FOLDER, 'folder' => '')),
            'file_name' => 'Imported',
        )));
        $this->assertFalse(Wipe::isFolderRow(array(
            'json' => json_encode(array('kind' => FileRepository::KIND_FILE, 'folder' => 'Imported')),
            'file_name' => 'notes.pdf',
        )));
    }

    public function testReservedRootFoldersStay()
    {
        foreach ( array(FileRepository::STUDENT_FILES_FOLDER, FileRepository::PUBLIC_FOLDER, FileRepository::PRIVATE_FOLDER) as $name ) {
            $this->assertTrue(Wipe::isReservedRootFolderRow(array(
                'contenttype' => FileRepository::FOLDER_CONTENTTYPE,
                'file_name' => $name,
                'json' => json_encode(array('kind' => FileRepository::KIND_FOLDER, 'folder' => '')),
            )), $name.' should be reserved');
        }
        $this->assertTrue(Wipe::isReservedRootFolderRow(array(
            'contenttype' => FileRepository::FOLDER_CONTENTTYPE,
            'file_name' => 'student',
            'json' => json_encode(array('kind' => FileRepository::KIND_FOLDER, 'folder' => '')),
        )));
        $this->assertFalse(Wipe::isReservedRootFolderRow(array(
            'contenttype' => FileRepository::FOLDER_CONTENTTYPE,
            'file_name' => 'Imported',
            'json' => json_encode(array('kind' => FileRepository::KIND_FOLDER, 'folder' => '')),
        )));
        $this->assertFalse(Wipe::isReservedRootFolderRow(array(
            'contenttype' => FileRepository::FOLDER_CONTENTTYPE,
            'file_name' => FileRepository::STUDENT_FILES_FOLDER,
            'json' => json_encode(array('kind' => FileRepository::KIND_FOLDER, 'folder' => 'Imported')),
        )));
        $this->assertFalse(Wipe::isReservedRootFolderRow(array(
            'contenttype' => 'application/pdf',
            'file_name' => FileRepository::STUDENT_FILES_FOLDER,
        )));
    }

    public function testLmsAnalyticsLinkKeysAreKept()
    {
        $this->assertTrue(Wipe::isLmsAnalyticsLinkKey('lms:/files'));
        $this->assertTrue(Wipe::isLmsAnalyticsLinkKey('lms:/pages'));
        $this->assertFalse(Wipe::isLmsAnalyticsLinkKey('week1-assignment'));
        $this->assertFalse(Wipe::isLmsAnalyticsLinkKey('LMS:/files'));
        $this->assertFalse(Wipe::isLmsAnalyticsLinkKey(''));
        $this->assertFalse(Wipe::isLmsAnalyticsLinkKey(null));
    }

    public function testEmptyLessonsDocumentHasNoModules()
    {
        $doc = Wipe::emptyLessonsDocument('Imported Course');
        $this->assertSame(2, $doc['lessons_json_version']);
        $this->assertSame('Imported Course', $doc['title']);
        $this->assertSame(array(), $doc['modules']);
        $this->assertSame(array(), $doc['discussions']);
        $this->assertSame(array(), $doc['badges']);
    }
}

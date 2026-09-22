<?php

require_once "src/Config/ConfigInfo.php";

use Tsugi\Services\Cartridge\Importer;
use Tsugi\Services\Files\FileRepository;

class CartridgeImporterTest extends \PHPUnit\Framework\TestCase
{
    public function testFilesFolderFromHrefKeepsCartridgePath()
    {
        $this->assertSame('', Importer::filesFolderFromHref('web_resources/week-one.pdf'));
        $this->assertSame('', Importer::filesFolderFromHref('i_am_a_file.pdf'));
        $this->assertSame('code3', Importer::filesFolderFromHref('web_resources/code3/browser.zip'));
        $this->assertSame('code3/week1', Importer::filesFolderFromHref('web_resources/code3/week1/browser.zip'));
        $this->assertSame('other', Importer::filesFolderFromHref('web_resources/other/notes.txt'));
        $this->assertSame('Imported', Importer::filesFolderFromHref('web_resources/Imported/week-one.pdf'));
        $this->assertSame(FileRepository::STUDENT_FILES_FOLDER, Importer::filesFolderFromHref('web_resources/Student/week-one.pdf'));
        $this->assertSame(FileRepository::PUBLIC_FOLDER, Importer::filesFolderFromHref('web_resources/Public/logo.png'));
        $this->assertSame(FileRepository::PUBLIC_FOLDER.'/images', Importer::filesFolderFromHref('web_resources/Public/images/logo.png'));
        $this->assertSame(FileRepository::PRIVATE_FOLDER, Importer::filesFolderFromHref('web_resources/Private/key.txt'));
        $this->assertSame('code3', Importer::filesFolderFromHref('web_resources\\code3\\browser.zip'));
    }
}

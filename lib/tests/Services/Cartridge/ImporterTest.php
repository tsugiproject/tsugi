<?php

require_once "src/Config/ConfigInfo.php";

use Tsugi\Controllers\Files;
use Tsugi\Services\Cartridge\Importer;

class CartridgeImporterTest extends \PHPUnit\Framework\TestCase
{
    public function testFilesFolderFromHrefUsesRootExceptReserved()
    {
        $this->assertSame('', Importer::filesFolderFromHref('web_resources/week-one.pdf'));
        $this->assertSame('', Importer::filesFolderFromHref('web_resources/Imported/week-one.pdf'));
        $this->assertSame('', Importer::filesFolderFromHref('web_resources/other/notes.txt'));
        $this->assertSame('', Importer::filesFolderFromHref('i_am_a_file.pdf'));
        $this->assertSame(Files::STUDENT_FILES_FOLDER, Importer::filesFolderFromHref('web_resources/Student/week-one.pdf'));
        $this->assertSame(Files::PUBLIC_FOLDER, Importer::filesFolderFromHref('web_resources/Public/logo.png'));
        $this->assertSame(Files::PRIVATE_FOLDER, Importer::filesFolderFromHref('web_resources/Private/key.txt'));
    }
}

<?php

require_once "src/Services/Files/FileRepository.php";

use Tsugi\Services\Files\FileRepository;

class FileFolderCountTest extends \PHPUnit\Framework\TestCase
{
    public function testCountRollsUpNestedFilesAndSkipsFolderRows()
    {
        $rows = array(
            $this->row('Student', 'folder', ''),
            $this->row('notes.pdf', 'file', 'Student'),
            $this->row('week1', 'folder', 'Student'),
            $this->row('a.pdf', 'file', 'Student/week1'),
            $this->row('sub', 'folder', 'Student/week1'),
            $this->row('b.pdf', 'file', 'Student/week1/sub'),
            $this->row('logo.png', 'file', 'Public'),
            $this->row('other.pdf', 'file', 'Student-notes'),
        );

        $this->assertSame(3, FileRepository::countFilesUnder($rows, 'Student'));
        $this->assertSame(2, FileRepository::countFilesUnder($rows, 'Student/week1'));
        $this->assertSame(1, FileRepository::countFilesUnder($rows, 'Student/week1/sub'));
        $this->assertSame(1, FileRepository::countFilesUnder($rows, 'Public'));
        $this->assertSame(0, FileRepository::countFilesUnder($rows, 'Empty'));
        $this->assertSame(1, FileRepository::countFilesUnder($rows, 'Student-notes'));
        $this->assertSame(5, FileRepository::countFilesUnder($rows, ''));
    }

    /**
     * @param string $name
     * @param string $kind
     * @param string $folder
     * @return array<string, mixed>
     */
    private function row($name, $kind, $folder)
    {
        return array(
            'file_name' => $name,
            'json' => json_encode(array('kind' => $kind, 'folder' => $folder)),
        );
    }
}

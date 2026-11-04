<?php

use Tsugi\Services\Notes\StudentNoteRepository;

class StudentNoteRepositoryTest extends \PHPUnit\Framework\TestCase
{
    public function testPlainTextKeepsAParagraphAndDropsMarkup() {
        $text = StudentNoteRepository::plain("  <b>Don't forget</b>\r\nthe midterm.  ");
        $this->assertSame("Don't forget\nthe midterm.", $text);
    }

    public function testPlainTextRejectsMoreThanFiveHundredCharacters() {
        $this->assertNull(StudentNoteRepository::plain(str_repeat('a', 501)));
        $this->assertSame(str_repeat('a', 500), StudentNoteRepository::plain(str_repeat('a', 500)));
    }

    public function testEmptyTextIsAnEmptyString() {
        $this->assertSame('', StudentNoteRepository::plain('   <br>  '));
        $this->assertSame('', StudentNoteRepository::plain(null));
    }
}

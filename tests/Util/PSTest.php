<?php

use \Tsugi\Util\PS;

class PSTest extends \PHPUnit\Framework\TestCase
{
    public function testOne() {
        // hello
        // 01234
        $st = new PS('hello');
        $this->assertTrue($st->startsWith('h'));
        $this->assertTrue($st->startsWith('he'));
        $this->assertTrue($st->endsWith('o'));
        $this->assertTrue($st->endsWith('lo'));
        $this->assertEquals($st->replace('ll','LL'), 'heLLo');
        $this->assertEquals($st->lower(), 'hello');
        $this->assertEquals($st->upper(), 'HELLO');
        $this->assertEquals($st->slice(), 'hello');
        $this->assertEquals($st->slice(2), 'llo');
        $this->assertEquals($st->slice(2,5), 'll');
        $this->assertEquals($st->find('e'), 1);
        $this->assertEquals($st->find('z'), -1);
        $this->assertEquals($st->rfind('l'), 3);
        $this->assertEquals(PS::s('hello')->upper(), 'HELLO');

        // Can compare in if test
        if ( ! PS::s('hello') == 'hello' ) {
            $this->assertEquals("PS::s('hello')", 'hello');
        }
        if ( ! (string) PS::s('hello') == 'hello' ) {
            $this->assertEquals("(string) PS::s('hello')", 'hello');
        }
        if ( ! PS::s('hello')->get() == 'hello' ) {
            $this->assertEquals("PS::s('hello')->get()", 'hello');
        }
    }


}

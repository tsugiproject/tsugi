<?php

use Tsugi\Services\Home\HomeLayout;

class HomeLayoutTest extends \PHPUnit\Framework\TestCase
{
    public function testFromPostKeepsTheSubmittedOrder() {
        $doc = HomeLayout::fromPost(array(
            'home_order' => array('dues', 'page', 'page', 'hero', 'calendar'),
            'hero' => 'left',
            'page_id' => array('8', '7'),
            'show_dues' => '1',
        ), array(7, 8));

        $this->assertSame(array('dues', 'page', 'page', 'hero', 'calendar', 'file'), array_column($doc['blocks'], 'type'));
        $this->assertTrue($doc['blocks'][0]['on']);
        $this->assertSame(8, $doc['blocks'][1]['page_id']);
        $this->assertSame(7, $doc['blocks'][2]['page_id']);
        $this->assertSame('left', $doc['blocks'][3]['place']);
        $this->assertFalse($doc['blocks'][4]['on']);
    }

    public function testFromPostDropsAPageFromAnotherCourse() {
        $doc = HomeLayout::fromPost(array(
            'home_order' => array('page', 'page'),
            'page_id' => array('99', '7'),
            'hero' => 'none',
        ), array(7));
        $pages = array();
        foreach ( $doc['blocks'] as $block ) {
            if ( $block['type'] === 'page' ) {
                $pages[] = $block['page_id'];
            }
        }
        $this->assertSame(array(0, 7), $pages);
        $this->assertSame('none', HomeLayout::block($doc, 'hero')['place']);
    }

    public function testFromPostKeepsEachFilePathAndPlace() {
        $doc = HomeLayout::fromPost(array(
            'home_order' => array('file', 'file', 'file'),
            'file_path' => array('Public/logo.png', 'Private/secret.pdf', 'Student/notes.pdf'),
            'file_place' => array('left', 'right', 'nope'),
        ), array(), array('Public/logo.png', 'Student/notes.pdf'));
        $files = array();
        foreach ( $doc['blocks'] as $block ) {
            if ( $block['type'] === 'file' ) {
                $files[] = array($block['path'], $block['place']);
            }
        }
        $this->assertSame(array(
            array('Public/logo.png', 'left'),
            array('', 'right'),
            array('Student/notes.pdf', 'inline'),
        ), $files);
    }

    public function testFromPostWithoutAnOrderStillAppliesTheChoices() {
        $doc = HomeLayout::fromPost(array(
            'hero' => 'center',
            'show_calendar' => '1',
            'page_id' => '8',
        ), array(8));
        $this->assertSame(array('hero', 'page', 'file', 'calendar', 'dues'), array_column($doc['blocks'], 'type'));
        $this->assertSame('center', HomeLayout::block($doc, 'hero')['place']);
        $this->assertTrue(HomeLayout::block($doc, 'calendar')['on']);
        $this->assertFalse(HomeLayout::block($doc, 'dues')['on']);
        $this->assertSame(8, HomeLayout::block($doc, 'page')['page_id']);
    }

    public function testOldHeroOnBecomesFloatRight() {
        $doc = HomeLayout::normalize(array(
            'blocks' => array(
                array('type' => 'hero', 'on' => true),
            ),
        ));
        $this->assertSame('right', HomeLayout::block($doc, 'hero')['place']);
    }

    public function testNormalizeAppendsMissingBlocks() {
        $doc = HomeLayout::normalize(array(
            'blocks' => array(
                array('type' => 'calendar', 'on' => '1'),
                array('type' => 'nope'),
                array('type' => 'calendar', 'on' => false),
                array('type' => 'page', 'page_id' => 3),
                array('type' => 'page', 'page_id' => 4),
            ),
        ));
        $this->assertSame(array('calendar', 'page', 'page', 'hero', 'file', 'dues'), array_column($doc['blocks'], 'type'));
        $this->assertTrue($doc['blocks'][0]['on']);
        $this->assertSame(3, $doc['blocks'][1]['page_id']);
        $this->assertSame(4, $doc['blocks'][2]['page_id']);
    }

    public function testDefaultLeavesEveryBlockOff() {
        $doc = HomeLayout::defaultDocument();
        foreach ( $doc['blocks'] as $block ) {
            if ( $block['type'] === 'page' ) {
                $this->assertSame(0, $block['page_id']);
            } elseif ( $block['type'] === 'hero' ) {
                $this->assertSame('none', $block['place']);
            } elseif ( $block['type'] === 'file' ) {
                $this->assertSame('', $block['path']);
                $this->assertSame('inline', $block['place']);
            } else {
                $this->assertFalse($block['on']);
            }
        }
    }
}

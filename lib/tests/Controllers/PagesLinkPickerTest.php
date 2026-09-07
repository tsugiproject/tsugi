<?php

require_once "src/Controllers/Pages.php";
require_once "src/UI/LessonsNormalize.php";
require_once "src/Config/ConfigInfo.php";

use Tsugi\Controllers\Pages;

class PagesLinkPickerTest extends \PHPUnit\Framework\TestCase
{
    public function testLessonsLinkPickerPayloadIncludesDiscussionModuleAndFileHref()
    {
        $doc = array(
            'title' => 'T',
            'modules' => array(
                array(
                    'title' => 'Week 1',
                    'anchor' => 'week-1',
                    'items' => array(
                        array(
                            'type' => 'discussion',
                            'title' => 'Welcome',
                            'resource_link_id' => 'discussion_welcome',
                        ),
                        array(
                            'type' => 'slide',
                            'title' => 'Deck',
                            'href' => '{apphome}/lectures/a.pptx',
                        ),
                        array(
                            'type' => 'file',
                            'title' => 'Handout',
                            'href' => '/files/download/8c2f4d0123456789abcdef0123456789abcdef0123456789abcdef0123456789',
                            'filename' => 'handout.pdf',
                        ),
                    ),
                ),
            ),
        );
        $out = Pages::lessonsLinkPickerPayload(
            $doc,
            'http://localhost/app',
            '/lessons',
            '/lessons_launch/',
            '/launch/'
        );
        $this->assertCount(1, $out['modules']);
        $this->assertSame('/lessons/week-1', $out['modules'][0]['url']);
        $urls = array();
        $types = array();
        foreach ( $out['items'] as $item ) {
            $urls[$item['title']] = $item['url'];
            $types[$item['title']] = $item['type'];
        }
        $this->assertSame('/lessons_launch/discussion_welcome', $urls['Welcome']);
        $this->assertSame('discussion', $types['Welcome']);
        $this->assertSame('http://localhost/app/lectures/a.pptx', $urls['Deck']);
        $this->assertSame('slide', $types['Deck']);
        $this->assertSame(
            '/files/download/8c2f4d0123456789abcdef0123456789abcdef0123456789abcdef0123456789',
            $urls['Handout']
        );
    }
}

<?php

require_once "src/UI/Topics.php";

use \Tsugi\UI\Topics;

class TopicsTest extends \PHPUnit\Framework\TestCase
{
    private $testJsonFile;

    protected function setUp(): void
    {
        parent::setUp();
        // Unset $_GET to avoid interference with constructor parameters
        unset($_GET['anchor']);
        unset($_GET['index']);
        
        // Create a temporary JSON file for testing
        $this->testJsonFile = sys_get_temp_dir() . '/test_topics_' . uniqid() . '.json';
        $testData = [
            'topics' => [
                [
                    'title' => 'Test Topic 1',
                    'anchor' => 'topic1'
                ],
                [
                    'title' => 'Test Topic 2',
                    'anchor' => 'topic2'
                ]
            ]
        ];
        file_put_contents($this->testJsonFile, json_encode($testData));
    }

    protected function tearDown(): void
    {
        // Clean up temporary file
        if (file_exists($this->testJsonFile)) {
            unlink($this->testJsonFile);
        }
        parent::tearDown();
    }

    public function testInstantiation() {
        $topics = new Topics($this->testJsonFile);
        $this->assertInstanceOf(\Tsugi\UI\Topics::class, $topics, 'Topics should instantiate correctly');
        $this->assertNotNull($topics->course, 'Topics should load JSON data');
        $this->assertCount(2, $topics->course->topics, 'Topics should load 2 topics');
        $this->assertEquals('Test Topic 1', $topics->course->topics[0]->title, 'First topic should have correct title');
        $this->assertEquals('topic1', $topics->course->topics[0]->anchor, 'First topic should have correct anchor');
    }

    public function testInstantiationWithParameters() {
        $topics = new Topics($this->testJsonFile, 'topic1', null);
        $this->assertInstanceOf(\Tsugi\UI\Topics::class, $topics, 'Topics should instantiate with parameters');
        $this->assertNotNull($topics->topic, 'Topic should be found when anchor matches');
        $this->assertEquals('topic1', $topics->anchor, 'Anchor should be set from matched topic');
        $this->assertEquals('Test Topic 1', $topics->topic->title, 'Correct topic should be selected');
    }

    public function testInstantiationWithIndex() {
        $topics = new Topics($this->testJsonFile, null, 1);
        $this->assertInstanceOf(\Tsugi\UI\Topics::class, $topics, 'Topics should instantiate with index parameter');
        $this->assertNotNull($topics->topic, 'Topic should be found when index matches');
        $this->assertEquals(1, $topics->topicposition, 'Topic position should be set correctly');
    }

}

<?php

require_once "src/UI/Pages.php";
require_once "src/Util/PDOX.php";
require_once "src/Config/ConfigInfo.php";

/**
 * Tests for Pages utility class
 * 
 * Tests the getFrontPageText() method which retrieves published front page content
 * for a given context ID from the database.
 */
class PagesTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $originalPDOX;
    
    protected function setUp(): void
    {
        global $CFG, $PDOX;
        
        // Save original globals
        $this->originalCFG = $CFG;
        $this->originalPDOX = $PDOX;
        
        // Set up test CFG
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->dbprefix = 'tsugi_';
        
        // Set up mock PDOX
        $PDOX = $this->createMockPDOX();
    }
    
    protected function tearDown(): void
    {
        global $CFG, $PDOX;
        
        // Restore original globals
        $CFG = $this->originalCFG;
        $PDOX = $this->originalPDOX;
    }
    
    /**
     * Create a mock PDOX object that can handle rowDie calls
     */
    private function createMockPDOX()
    {
        $mockPDOX = $this->getMockBuilder(\Tsugi\Util\PDOX::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['rowDie'])
            ->getMock();
        
        return $mockPDOX;
    }
    
    /**
     * Test getFrontPageText with invalid context IDs
     */
    public function testGetFrontPageTextInvalidContextId() {
        global $PDOX;
        
        // Test with null context_id
        $result = \Tsugi\UI\Pages::getFrontPageText(null);
        $this->assertNull($result, 'Should return null for null context_id');
        
        // Test with zero context_id
        $result = \Tsugi\UI\Pages::getFrontPageText(0);
        $this->assertNull($result, 'Should return null for zero context_id');
        
        // Test with negative context_id
        $result = \Tsugi\UI\Pages::getFrontPageText(-1);
        $this->assertNull($result, 'Should return null for negative context_id');
    }
    
    /**
     * Test getFrontPageText when no row is found
     */
    public function testGetFrontPageTextNoRowFound() {
        global $PDOX;
        
        // Mock rowDie to return false (no row found)
        $PDOX->expects($this->once())
            ->method('rowDie')
            ->with(
                $this->stringContains('SELECT body FROM'),
                $this->equalTo(array(':CID' => 123))
            )
            ->willReturn(false);
        
        $result = \Tsugi\UI\Pages::getFrontPageText(123);
        $this->assertNull($result, 'Should return null when no row is found');
    }
    
    /**
     * Test getFrontPageText when row has empty body
     */
    public function testGetFrontPageTextEmptyBody() {
        global $PDOX;
        
        // Mock rowDie to return row with empty body
        $PDOX->expects($this->once())
            ->method('rowDie')
            ->willReturn(array('body' => ''));
        
        $result = \Tsugi\UI\Pages::getFrontPageText(123);
        $this->assertNull($result, 'Should return null when body is empty');
    }
    
    /**
     * Test getFrontPageText when row has null body
     */
    public function testGetFrontPageTextNullBody() {
        global $PDOX;
        
        // Mock rowDie to return row with null body
        $PDOX->expects($this->once())
            ->method('rowDie')
            ->willReturn(array('body' => null));
        
        $result = \Tsugi\UI\Pages::getFrontPageText(123);
        $this->assertNull($result, 'Should return null when body is null');
    }
    
    /**
     * Test getFrontPageText when row has valid body text
     */
    public function testGetFrontPageTextValidBody() {
        global $PDOX;
        
        $expectedBody = '<h1>Welcome to the Course</h1><p>This is the front page content.</p>';
        
        // Mock rowDie to return row with valid body
        $PDOX->expects($this->once())
            ->method('rowDie')
            ->with(
                $this->stringContains('SELECT body FROM tsugi_pages'),
                $this->equalTo(array(':CID' => 456))
            )
            ->willReturn(array('body' => $expectedBody));
        
        $result = \Tsugi\UI\Pages::getFrontPageText(456);
        $this->assertEquals($expectedBody, $result, 'Should return the body text when found');
    }
    
    /**
     * Test getFrontPageText when PDOX is not initially set (tests connection initialization)
     * 
     * Note: This test verifies that the code handles the case where PDOX needs to be initialized.
     * Since LTIX::getConnection() is a static method that sets the global $PDOX, we test
     * by ensuring the method still works when PDOX is initially null but gets set during execution.
     */
    public function testGetFrontPageTextInitializesConnection() {
        global $PDOX;
        
        // Temporarily unset PDOX
        $savedPDOX = $PDOX;
        $PDOX = null;
        
        // Create a new mock that will be set by LTIX::getConnection()
        // In practice, LTIX::getConnection() would initialize this, but for testing
        // we'll set it manually to simulate the connection being established
        $mockPDOX = $this->createMockPDOX();
        $mockPDOX->expects($this->once())
            ->method('rowDie')
            ->willReturn(array('body' => 'Test content after connection'));
        
        // Simulate LTIX::getConnection() setting the global PDOX
        $PDOX = $mockPDOX;
        
        $result = \Tsugi\UI\Pages::getFrontPageText(789);
        $this->assertEquals('Test content after connection', $result, 'Should work after connection is initialized');
        
        // Restore original PDOX
        $PDOX = $savedPDOX;
    }
    
    /**
     * Test getFrontPageText verifies SQL query structure
     */
    public function testGetFrontPageTextQueryStructure() {
        global $PDOX, $CFG;
        
        $CFG->dbprefix = 'test_';
        
        // Verify the SQL query includes the correct conditions
        $PDOX->expects($this->once())
            ->method('rowDie')
            ->with(
                $this->callback(function($sql) {
                    return strpos($sql, 'SELECT body FROM') !== false &&
                           strpos($sql, 'test_pages') !== false &&
                           strpos($sql, 'context_id = :CID') !== false &&
                           strpos($sql, 'is_front_page = 1') !== false &&
                           strpos($sql, 'published = 1') !== false &&
                           strpos($sql, 'LIMIT 1') !== false;
                }),
                $this->equalTo(array(':CID' => 999))
            )
            ->willReturn(array('body' => 'Content'));
        
        $result = \Tsugi\UI\Pages::getFrontPageText(999);
        $this->assertEquals('Content', $result);
    }
    
    /**
     * Test getFrontPageText with various body content types
     */
    public function testGetFrontPageTextVariousContent() {
        global $PDOX;
        
        $testCases = array(
            'Simple text' => 'Hello World',
            'HTML content' => '<div><h1>Title</h1><p>Paragraph</p></div>',
            'Long content' => str_repeat('This is a very long content string. ', 100),
            'Special characters' => '<script>alert("test")</script>',
            'Unicode' => 'Привет мир 🌍',
        );
        
        foreach ($testCases as $description => $content) {
            $PDOX = $this->createMockPDOX();
            $PDOX->expects($this->once())
                ->method('rowDie')
                ->willReturn(array('body' => $content));
            
            $result = \Tsugi\UI\Pages::getFrontPageText(100);
            $this->assertEquals($content, $result, "Should return content for: $description");
        }
    }
}


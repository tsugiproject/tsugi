<?php

use Tsugi\Services\Cartridge\Matcher;
use Tsugi\Services\Cartridge\Session;

class CartridgeSessionTest extends \PHPUnit\Framework\TestCase
{
    public function testExactSameCartridgeSecondImportIsAllDuplicates() {
        $s = new Session(10, 3);
        $s->begin(array('filename' => 'course.imscc', 'zip_sha256' => self::hash('zip')));
        $this->importThree($s, self::hash('page'), self::hash('quiz'), self::hash('link'));
        $first = $s->finish();
        $this->assertSame(Matcher::STATUS_OK, $first['status']);
        $this->assertSame(3, $first['created_count']);
        $this->assertSame(0, $first['duplicate_count']);

        $s->begin(array('filename' => 'course.imscc', 'zip_sha256' => self::hash('zip')));
        $this->importThree($s, self::hash('page'), self::hash('quiz'), self::hash('link'));
        $second = $s->finish();
        $this->assertSame(Matcher::STATUS_OK, $second['status']);
        $this->assertSame(0, $second['created_count']);
        $this->assertSame(3, $second['duplicate_count']);
        $this->assertSame(0, $second['copy_count']);
        $this->assertCount(3, $s->objects);
        $this->assertSame(Matcher::ACTION_DUPLICATE, $s->logs[0]['action']);
        $this->assertSame(Matcher::ACTION_DUPLICATE, $s->logs[1]['action']);
        $this->assertSame(Matcher::ACTION_DUPLICATE, $s->logs[2]['action']);
    }

    public function testEditThenReimportOriginalCreatesACopy() {
        $s = new Session(10, 3);
        $s->begin();
        $d = $s->consider('PAGE1', Matcher::TYPE_WEBCONTENT, self::hash('body'));
        $this->assertTrue($d->isNew());
        $s->record($d, 'page', 42);
        $objectId = $d->object['object_id'];
        $s->finish();

        $this->assertTrue($s->markDiverged($objectId));

        $s->begin();
        $again = $s->consider('PAGE1', Matcher::TYPE_WEBCONTENT, self::hash('body'));
        $this->assertTrue($again->isCopy());
        $s->record($again, 'page', 99);
        $done = $s->finish();

        $this->assertSame(1, $done['copy_count']);
        $this->assertSame(0, $done['duplicate_count']);
        $this->assertCount(2, $s->objects);
        $this->assertSame(1, $s->objects[0]['diverged']);
        $this->assertSame(0, $s->objects[1]['diverged']);
        $this->assertSame(42, $s->objects[0]['local_id']);
        $this->assertSame(99, $s->objects[1]['local_id']);
        $this->assertSame('PAGE1', $s->objects[1]['resource_identifier']);
        $this->assertSame(Matcher::ACTION_COPY, $s->logs[0]['action']);
    }

    public function testMixedEditOnSecondImport() {
        $s = new Session(10);
        $s->begin();
        $this->importThree($s, self::hash('page'), self::hash('quiz'), self::hash('link'));
        $s->finish();

        $s->markDivergedByLocal('quiz', 2);

        $s->begin();
        $this->importThree($s, self::hash('page'), self::hash('quiz'), self::hash('link'));
        $done = $s->finish();

        $this->assertSame(0, $done['created_count']);
        $this->assertSame(2, $done['duplicate_count']);
        $this->assertSame(1, $done['copy_count']);
        $this->assertCount(4, $s->objects);
        $actions = array();
        foreach ( $s->logs as $row ) {
            $actions[] = $row['action'];
        }
        $this->assertSame(
            array(Matcher::ACTION_DUPLICATE, Matcher::ACTION_COPY, Matcher::ACTION_DUPLICATE),
            $actions
        );
    }

    public function testDeleteThenReimportIsNew() {
        $s = new Session(10);
        $s->begin();
        $d = $s->consider('FILE1', Matcher::TYPE_WEBCONTENT, self::hash('pdf'));
        $s->record($d, 'file', 7);
        $s->finish();

        $this->assertTrue($s->forget($d->object['object_id']));

        $s->begin();
        $again = $s->consider('FILE1', Matcher::TYPE_WEBCONTENT, self::hash('pdf'));
        $this->assertTrue($again->isNew());
        $s->record($again, 'file', 8);
        $done = $s->finish();
        $this->assertSame(1, $done['created_count']);
        $this->assertCount(1, $s->objects);
        $this->assertSame(8, $s->objects[0]['local_id']);
    }

    public function testTwoCoursesDoNotCollide() {
        $a = new Session(10);
        $a->begin();
        $d = $a->consider('R1', Matcher::TYPE_LTI, self::hash('launch'));
        $a->record($d, 'lti_link', 1);
        $a->finish();

        $b = new Session(11, 0, array());
        $b->begin();
        $d2 = $b->consider('R1', Matcher::TYPE_LTI, self::hash('launch'));
        $this->assertTrue($d2->isNew());
        $b->record($d2, 'lti_link', 1);
        $done = $b->finish();
        $this->assertSame(1, $done['created_count']);
        $this->assertSame(11, $b->objects[0]['context_id']);
    }

    public function testItemIdentifierIsStoredButNotUsedForMatching() {
        $s = new Session(10);
        $s->begin();
        $d = $s->consider('R1', Matcher::TYPE_WEBCONTENT, self::hash('x'), array(
            'item_identifier' => 'ITEM-A',
            'title' => 'Week 1 file',
        ));
        $s->record($d, 'file', 1);
        $s->finish();

        $s->begin();
        $again = $s->consider('R1', Matcher::TYPE_WEBCONTENT, self::hash('x'), array(
            'item_identifier' => 'ITEM-B',
            'title' => 'Week 3 file',
        ));
        $this->assertTrue($again->isDuplicate());
        $s->record($again);
        $s->finish();
        $this->assertSame('ITEM-A', $s->objects[0]['item_identifier']);
        $this->assertSame('Week 3 file', $s->logs[0]['title']);
    }

    public function testExistingObjectsAreLoadedForALaterSession() {
        $first = new Session(10);
        $first->begin();
        $d = $first->consider('R1', Matcher::TYPE_QTI, self::hash('quiz'));
        $first->record($d, 'quiz', 5);
        $first->finish();

        $later = new Session(10, 0, $first->objects);
        $later->begin();
        $again = $later->consider('R1', Matcher::TYPE_QTI, self::hash('quiz'));
        $this->assertTrue($again->isDuplicate());
        $later->record($again);
        $later->finish();
        $this->assertCount(1, $later->objects);
    }

    public function testErrorMakesPartialWhenSomethingElseSucceeded() {
        $s = new Session(10);
        $s->begin();
        $d = $s->consider('R1', Matcher::TYPE_WEBLINK, self::hash('url'));
        $s->record($d, 'lesson_item', null, 'item-uuid');
        $s->error('bad xml', array('resource_identifier' => 'R2'));
        $done = $s->finish();
        $this->assertSame(Matcher::STATUS_PARTIAL, $done['status']);
        $this->assertSame(1, $done['created_count']);
        $this->assertSame(1, $done['error_count']);
    }

    public function testOnlyErrorsIsFail() {
        $s = new Session(10);
        $s->begin();
        $s->error('boom');
        $done = $s->finish();
        $this->assertSame(Matcher::STATUS_FAIL, $done['status']);
        $this->assertSame(1, $done['error_count']);
    }

    public function testConsiderBeforeBeginThrows() {
        $s = new Session(10);
        $this->expectException(\LogicException::class);
        $s->consider('R1', Matcher::TYPE_WEBCONTENT, self::hash('a'));
    }

    public function testBeginWhileRunningThrows() {
        $s = new Session(10);
        $s->begin();
        $this->expectException(\LogicException::class);
        $s->begin();
    }

    public function testEmptyResourceIdThrows() {
        $s = new Session(10);
        $s->begin();
        $this->expectException(\InvalidArgumentException::class);
        $s->consider('', Matcher::TYPE_WEBCONTENT, self::hash('a'));
    }

    public function testDuplicateTouchesLastImportId() {
        $s = new Session(10);
        $s->begin();
        $d = $s->consider('R1', Matcher::TYPE_WEBCONTENT, self::hash('a'));
        $s->record($d, 'page', 1);
        $firstImport = $s->import['import_id'];
        $s->finish();

        $s->begin();
        $again = $s->consider('R1', Matcher::TYPE_WEBCONTENT, self::hash('a'));
        $s->record($again);
        $secondImport = $s->import['import_id'];
        $s->finish();

        $this->assertNotSame($firstImport, $secondImport);
        $this->assertSame($secondImport, $s->objects[0]['import_id']);
    }

    public function testIdentifiersRoundTripOnObject() {
        $s = new Session(10);
        $s->begin();
        $d = $s->consider('R1', Matcher::TYPE_WEBCONTENT, self::hash('a'), array(
            'identifiers' => array('canvas_migration_id' => 'g123', 'identifierref' => 'R1'),
        ));
        $s->record($d, 'page', 1);
        $s->finish();
        $this->assertSame('g123', $s->objects[0]['identifiers']['canvas_migration_id']);
    }

    /**
     * @param string $pageHash
     * @param string $quizHash
     * @param string $linkHash
     */
    private function importThree(Session $s, $pageHash, $quizHash, $linkHash) {
        $page = $s->consider('PAGE1', Matcher::TYPE_WEBCONTENT, $pageHash, array('title' => 'Page'));
        if ( $page->isDuplicate() ) {
            $s->record($page);
        } else {
            $s->record($page, 'page', 1);
        }
        $quiz = $s->consider('QUIZ1', Matcher::TYPE_QTI, $quizHash, array('title' => 'Quiz'));
        if ( $quiz->isDuplicate() ) {
            $s->record($quiz);
        } else {
            $s->record($quiz, 'quiz', 2);
        }
        $link = $s->consider('LTI1', Matcher::TYPE_LTI, $linkHash, array('title' => 'Tool'));
        if ( $link->isDuplicate() ) {
            $s->record($link);
        } else {
            $s->record($link, 'lti_link', 3);
        }
    }

    /**
     * @param string $s
     * @return string
     */
    private static function hash($s) {
        return hash('sha256', $s);
    }
}

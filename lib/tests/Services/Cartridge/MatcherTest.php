<?php

use Tsugi\Services\Cartridge\Matcher;

class CartridgeMatcherTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyIsNew() {
        $got = Matcher::classify(array(), 'R1', Matcher::TYPE_WEBCONTENT, self::hash('a'));
        $this->assertSame(Matcher::NEW, $got['action']);
        $this->assertNull($got['object']);
    }

    public function testMatchingHashNotDivergedIsDuplicate() {
        $objects = array(self::obj(1, 'R1', Matcher::TYPE_WEBCONTENT, self::hash('a'), 0));
        $got = Matcher::classify($objects, 'R1', Matcher::TYPE_WEBCONTENT, self::hash('a'));
        $this->assertSame(Matcher::DUPLICATE, $got['action']);
        $this->assertSame(1, $got['object']['object_id']);
    }

    public function testDivergedWithSameHashIsCopy() {
        $objects = array(self::obj(1, 'R1', Matcher::TYPE_WEBCONTENT, self::hash('a'), 1));
        $got = Matcher::classify($objects, 'R1', Matcher::TYPE_WEBCONTENT, self::hash('a'));
        $this->assertSame(Matcher::COPY, $got['action']);
        $this->assertSame(1, $got['object']['object_id']);
    }

    public function testSameIdDifferentHashIsCopy() {
        $objects = array(self::obj(1, 'R1', Matcher::TYPE_WEBCONTENT, self::hash('old'), 0));
        $got = Matcher::classify($objects, 'R1', Matcher::TYPE_WEBCONTENT, self::hash('new'));
        $this->assertSame(Matcher::COPY, $got['action']);
    }

    public function testDifferentTypeIsNew() {
        $objects = array(self::obj(1, 'R1', Matcher::TYPE_WEBCONTENT, self::hash('a'), 0));
        $got = Matcher::classify($objects, 'R1', Matcher::TYPE_WEBLINK, self::hash('a'));
        $this->assertSame(Matcher::NEW, $got['action']);
    }

    public function testDifferentResourceIdIsNew() {
        $objects = array(self::obj(1, 'R1', Matcher::TYPE_WEBCONTENT, self::hash('a'), 0));
        $got = Matcher::classify($objects, 'R2', Matcher::TYPE_WEBCONTENT, self::hash('a'));
        $this->assertSame(Matcher::NEW, $got['action']);
    }

    public function testEmptyHashNeverDuplicates() {
        $objects = array(self::obj(1, 'R1', Matcher::TYPE_WEBCONTENT, self::hash('a'), 0));
        $got = Matcher::classify($objects, 'R1', Matcher::TYPE_WEBCONTENT, '');
        $this->assertSame(Matcher::COPY, $got['action']);
    }

    public function testEmptyHashWithNoAncestryIsNew() {
        $got = Matcher::classify(array(), 'R1', Matcher::TYPE_WEBCONTENT, '');
        $this->assertSame(Matcher::NEW, $got['action']);
    }

    public function testDivergedThenFreshCopyStillDuplicatesTheNewOne() {
        $objects = array(
            self::obj(1, 'R1', Matcher::TYPE_WEBCONTENT, self::hash('a'), 1),
            self::obj(2, 'R1', Matcher::TYPE_WEBCONTENT, self::hash('a'), 0),
        );
        $got = Matcher::classify($objects, 'R1', Matcher::TYPE_WEBCONTENT, self::hash('a'));
        $this->assertSame(Matcher::DUPLICATE, $got['action']);
        $this->assertSame(2, $got['object']['object_id']);
    }

    public function testCopyPointsAtLatestAncestor() {
        $objects = array(
            self::obj(1, 'R1', Matcher::TYPE_WEBCONTENT, self::hash('a'), 1),
            self::obj(2, 'R1', Matcher::TYPE_WEBCONTENT, self::hash('a'), 1),
        );
        $got = Matcher::classify($objects, 'R1', Matcher::TYPE_WEBCONTENT, self::hash('a'));
        $this->assertSame(Matcher::COPY, $got['action']);
        $this->assertSame(2, $got['object']['object_id']);
    }

    public function testLogActionMapsNewToCreated() {
        $this->assertSame(Matcher::ACTION_CREATED, Matcher::logAction(Matcher::NEW));
        $this->assertSame(Matcher::ACTION_DUPLICATE, Matcher::logAction(Matcher::DUPLICATE));
        $this->assertSame(Matcher::ACTION_COPY, Matcher::logAction(Matcher::COPY));
    }

    /**
     * @return array<string, mixed>
     */
    private static function obj($id, $rid, $type, $hash, $diverged) {
        return array(
            'object_id' => $id,
            'resource_identifier' => $rid,
            'resource_type' => $type,
            'content_hash' => $hash,
            'diverged' => $diverged,
        );
    }

    /**
     * @param string $s
     * @return string
     */
    private static function hash($s) {
        return hash('sha256', $s);
    }
}

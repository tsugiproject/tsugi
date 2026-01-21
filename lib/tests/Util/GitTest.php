<?php


require_once "src/Util/Git.php";
require_once "src/Util/GitRepo.php";

class GitTest extends \PHPUnit\Framework\TestCase
{
    public function testIndent() {
        $repo = new \Tsugi\Util\GitRepo();
        // This will only run if git is available on the system
        $this->assertTrue($repo->test_git());
    }
}

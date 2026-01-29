<?php

require_once("src/Core/User.php");
require_once("src/Core/JsonTrait.php");
use \Tsugi\Core\User;

class UserTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $user = new \Tsugi\Core\User();
        $this->assertInstanceOf(\Tsugi\Core\User::class, $user, 'User should instantiate correctly');
        $this->assertFalse($user->launch, 'Launch should default to false');
        $this->assertFalse($user->admin, 'Admin should default to false');
    }

    /**
     * @dataProvider getDisplayProvider
     */
    public function testGetDisplay($user_id, $displayname, $email, $expected) {
        $result = User::getDisplay($user_id, $displayname, $email);
        $this->assertEquals($expected, $result, 
            "getDisplay should format user_id={$user_id}, displayname='{$displayname}', email='{$email}' correctly");
    }

    public static function getDisplayProvider() {
        return [
            'Both name and email' => [1, 'John Doe', 'john@example.com', 'John Doe (john@example.com)'],
            'Name only' => [2, 'Jane Smith', '', 'Jane Smith'],
            'Email only' => [3, '', 'jane@example.com', 'jane@example.com'],
            'User ID only' => [42, '', '', 'User: 42'],
            'Nothing provided' => [0, '', '', false],
            'Name with spaces' => [4, '  Bob Wilson  ', 'bob@example.com', 'Bob Wilson (bob@example.com)'],
            'Non-string displayname' => [5, null, 'test@example.com', 'test@example.com'],
            'Non-string email' => [6, 'Alice', null, 'Alice'],
        ];
    }

    public function testGetNameAndEmail() {
        $user = new \Tsugi\Core\User();
        $user->id = 1;
        $user->displayname = 'Test User';
        $user->email = 'test@example.com';
        
        $result = $user->getNameAndEmail();
        $this->assertEquals('Test User (test@example.com)', $result, 
            'getNameAndEmail should return formatted name and email');
    }

    public function testGetFirstName() {
        $user = new \Tsugi\Core\User();
        $user->displayname = 'John Doe';
        $user->email = 'john@example.com';
        
        $result = $user->getFirstName();
        $this->assertEquals('John', $result, 'getFirstName should extract first name from displayname');
        
        $result = $user->getFirstName('Jane Smith');
        $this->assertEquals('Jane', $result, 'getFirstName should extract first name from parameter');
        
        $user->displayname = '';
        $user->email = '';
        $result = $user->getFirstName();
        $this->assertEmpty($result, 'getFirstName should return empty when no name available');
    }

}

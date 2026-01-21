<?php

require_once "src/UI/CrudForm.php";

use \Tsugi\UI\CrudForm;

class CrudFormTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct() {
        $crudForm = new CrudForm();
        $this->assertTrue(is_object($crudForm));
    }


}

<?php

require_once "src/UI/CKEditor.php";

use \Tsugi\UI\CKEditor;

class CKEditorTest extends \PHPUnit\Framework\TestCase
{
    public function testRenderConfigScriptIncludesAutomaticDecoratorByDefault()
    {
        ob_start();
        CKEditor::renderConfigScript();
        $js = ob_get_clean();
        $this->assertStringContainsString('openExternalInNewTab', $js);
        $this->assertStringContainsString('openInNewTab', $js);
    }

    public function testRenderConfigScriptCanDisableAutomaticExternalBlank()
    {
        ob_start();
        CKEditor::renderConfigScript(array('automaticExternalBlank' => false));
        $js = ob_get_clean();
        $this->assertStringNotContainsString('openExternalInNewTab', $js);
        $this->assertStringContainsString('openInNewTab', $js);
    }
}

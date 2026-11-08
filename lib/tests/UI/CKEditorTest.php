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
        $this->assertStringContainsString('filesRoot + "/"', $js);
    }

    public function testRenderConfigScriptCanDisableAutomaticExternalBlank()
    {
        ob_start();
        CKEditor::renderConfigScript(array('automaticExternalBlank' => false));
        $js = ob_get_clean();
        $this->assertStringNotContainsString('openExternalInNewTab', $js);
        $this->assertStringContainsString('openInNewTab', $js);
    }

    public function testRenderConfigScriptAssignsSharedConfig()
    {
        ob_start();
        CKEditor::renderConfigScript();
        $js = ob_get_clean();
        $this->assertStringContainsString('var tsugiEditorConfig', $js);
        $this->assertStringContainsString('ClassicEditor.defaultConfig = tsugiEditorConfig', $js);
    }

    public function testPagesEditorModuleIncludesFontColorAndRules()
    {
        ob_start();
        CKEditor::renderPagesEditorAssets();
        $assets = ob_get_clean();
        $this->assertStringContainsString('/lib/src/Controllers/static/Pages/ckeditor.css', $assets);
        $this->assertStringNotContainsString('importmap', $assets);

        ob_start();
        CKEditor::renderPagesEditorModule();
        $js = ob_get_clean();
        $this->assertStringContainsString('type="module"', $js);
        $this->assertStringContainsString('/lib/src/Controllers/static/Pages/ckeditor.js', $js);
        $this->assertStringNotContainsString("from 'ckeditor5'", $js);
        $this->assertStringContainsString('fontFamily', $js);
        $this->assertStringContainsString('fontColor', $js);
        $this->assertStringContainsString('styledHorizontalLine', $js);
        $this->assertStringContainsString("name: 'hr'", $js);
        $this->assertStringContainsString('licenseKey: \'GPL\'', $js);
        $this->assertStringContainsString('Hairline', $js);
        $this->assertStringContainsString('border-top-width', $js);
        $this->assertStringContainsString('click a thickness or a color to change it', $js);
        $this->assertStringContainsString('previewsInData: true', $js);
        $this->assertStringContainsString('mediaEmbed:wrapText', $js);
        $this->assertStringContainsString('tsugiMediaSize', $js);
        $this->assertStringContainsString('MediaEmbedStyle', $js);
        $this->assertStringContainsString('CodeBlock', $js);
        $this->assertStringContainsString('Alignment', $js);
        $this->assertStringContainsString('Highlight', $js);
        $this->assertStringContainsString('codeBlock', $js);
        $this->assertStringContainsString('language: \'python\'', $js);
        $this->assertStringContainsString('8px', $js);
        $this->assertStringContainsString('#2563eb', $js);
    }

    public function testLinkPickerSkipsClassicEditorWhenPagesModuleLoads()
    {
        ob_start();
        CKEditor::renderLinkPickerScript();
        $js = ob_get_clean();
        $this->assertStringContainsString('window.tsugiPagesEditor', $js);
        $this->assertStringContainsString('window.tsugiBindEditor', $js);
    }
}

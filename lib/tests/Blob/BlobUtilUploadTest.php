<?php

require_once "src/Core/I18N.php";
require_once "include/setup_i18n.php";
require_once "src/Blob/BlobUtil.php";

use Tsugi\Blob\BlobUtil;

class BlobUtilUploadTest extends \PHPUnit\Framework\TestCase
{
    public function testRequestLargerThanPhpPostLimitDetectsContentLength() {
        $origMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $origLen = $_SERVER['CONTENT_LENGTH'] ?? null;
        $postMax = BlobUtil::return_bytes(ini_get('post_max_size'));
        $this->assertGreaterThan(0, $postMax);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_LENGTH'] = (string) ($postMax + 1024);
        $this->assertTrue(BlobUtil::requestLargerThanPhpPostLimit());

        $_SERVER['CONTENT_LENGTH'] = (string) max(1, $postMax - 1024);
        $this->assertFalse(BlobUtil::requestLargerThanPhpPostLimit());

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['CONTENT_LENGTH'] = (string) ($postMax + 1024);
        $this->assertFalse(BlobUtil::requestLargerThanPhpPostLimit());

        $this->restoreServer($origMethod, $origLen);
    }

    public function testPhpUploadTooLargeMessageIncludesLimitsAndSentSize() {
        $msg = BlobUtil::phpUploadTooLargeMessage(41 * 1024 * 1024);
        $this->assertStringContainsString('upload_max_filesize', $msg);
        $this->assertStringContainsString('post_max_size', $msg);
        $this->assertStringContainsString('41', $msg);
    }

    public function testPhpUploadLimitLabelIsNonEmpty() {
        $label = BlobUtil::phpUploadLimitLabel();
        $this->assertNotSame('', $label);
        $this->assertMatchesRegularExpression('/\d/', $label);
    }

    /**
     * @param mixed $method
     * @param mixed $len
     */
    private function restoreServer($method, $len) {
        if ( $method === null ) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $method;
        }
        if ( $len === null ) {
            unset($_SERVER['CONTENT_LENGTH']);
        } else {
            $_SERVER['CONTENT_LENGTH'] = $len;
        }
    }
}

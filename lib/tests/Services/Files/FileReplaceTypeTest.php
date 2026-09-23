<?php

require_once "src/Services/Files/FileRepository.php";

use Tsugi\Services\Files\FileRepository;

class FileReplaceTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testPngReplacementMustStayPng()
    {
        $png = $this->tempBytes($this->pngBytes());
        $zip = $this->tempZip();

        $this->assertNull(FileRepository::replacementTypeError(
            'image/png',
            'farewell-lake.png',
            $png,
            'application/octet-stream',
            'other.png'
        ));

        $error = FileRepository::replacementTypeError(
            'image/png',
            'farewell-lake.png',
            $zip,
            'image/png',
            'farewell-lake.png'
        );
        $this->assertIsString($error);
        $this->assertStringContainsString('image/png', $error);
        $this->assertStringContainsString('application/zip', $error);

        @unlink($png);
        @unlink($zip);
    }

    public function testHtmlCannotReplacePowerPoint()
    {
        $html = $this->tempBytes("<html><body>hi</body></html>");
        $error = FileRepository::replacementTypeError(
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'week.pptx',
            $html,
            'text/html',
            'week.pptx'
        );
        $this->assertIsString($error);
        $this->assertStringContainsString('text/html', $error);
        @unlink($html);
    }

    public function testPowerPointMayBeReplacedByAnotherPowerPoint()
    {
        $zip = $this->tempZip();
        $stored = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
        $this->assertNull(FileRepository::replacementTypeError(
            $stored,
            'week.pptx',
            $zip,
            $stored,
            'slides.pptx'
        ));
        $error = FileRepository::replacementTypeError(
            $stored,
            'week.pptx',
            $zip,
            $stored,
            'notes.docx'
        );
        $this->assertIsString($error);
        $this->assertStringContainsString('.pptx', $error);
        $this->assertStringContainsString('.docx', $error);
        @unlink($zip);
    }

    public function testSuffixMustMatchExceptForKnownAliases()
    {
        $png = $this->tempBytes($this->pngBytes());
        $this->assertNull(FileRepository::replacementTypeError(
            'image/png',
            'farewell-lake.png',
            $png,
            'image/png',
            'IMG_2044.PNG'
        ));
        $wrong = FileRepository::replacementTypeError(
            'image/png',
            'farewell-lake.png',
            $png,
            'image/png',
            'farewell-lake.jpg'
        );
        $this->assertIsString($wrong);
        $this->assertStringContainsString('.png', $wrong);
        $this->assertStringContainsString('.jpg', $wrong);

        $jpeg = $this->tempBytes("\xff\xd8\xff\xe0".str_repeat("\x00", 16));
        $sniff = (new \finfo(FILEINFO_MIME_TYPE))->file($jpeg);
        if ( $sniff === 'image/jpeg' ) {
            $this->assertNull(FileRepository::replacementTypeError(
                'image/jpeg',
                'photo.jpg',
                $jpeg,
                'image/jpeg',
                'photo.jpeg'
            ));
        }
        $html = $this->tempBytes("<html><body>hi</body></html>");
        $this->assertNull(FileRepository::replacementTypeError(
            'text/html',
            'page.htm',
            $html,
            'text/html',
            'page.html'
        ));
        $bare = FileRepository::replacementTypeError(
            'text/plain',
            'README',
            $html,
            'text/plain',
            'README.txt'
        );
        $this->assertIsString($bare);
        $this->assertStringContainsString('no suffix', $bare);

        $this->assertSame('.png', FileRepository::replacementEndingLabel('farewell-lake.PNG'));
        $this->assertSame('.jpg or .jpeg', FileRepository::replacementEndingLabel('photo.jpeg'));
        $this->assertSame('no suffix', FileRepository::replacementEndingLabel('README'));

        @unlink($png);
        @unlink($jpeg);
        @unlink($html);
    }

    public function testMissingStoredTypeCannotBeReplaced()
    {
        $png = $this->tempBytes($this->pngBytes());
        $error = FileRepository::replacementTypeError('', 'farewell-lake.png', $png, 'image/png', 'a.png');
        $this->assertIsString($error);
        $this->assertStringContainsString('no type', $error);
        @unlink($png);
    }

    public function testJpegAliasMatches()
    {
        $jpeg = $this->tempBytes("\xff\xd8\xff\xe0".str_repeat("\x00", 16));
        $sniff = (new \finfo(FILEINFO_MIME_TYPE))->file($jpeg);
        if ( $sniff !== 'image/jpeg' ) {
            $this->markTestSkipped('finfo did not classify the sample as image/jpeg');
        }
        $this->assertNull(FileRepository::replacementTypeError(
            'image/jpg',
            'photo.jpg',
            $jpeg,
            'image/jpeg',
            'photo.jpg'
        ));
        @unlink($jpeg);
    }

    /**
     * @param string $bytes
     * @return string
     */
    private function tempBytes($bytes)
    {
        $path = tempnam(sys_get_temp_dir(), 'repl');
        file_put_contents($path, $bytes);
        return $path;
    }

    /**
     * @return string
     */
    private function tempZip()
    {
        $path = tempnam(sys_get_temp_dir(), 'repl');
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::OVERWRITE);
        $zip->addFromString('a.txt', 'hello');
        $zip->close();
        return $path;
    }

    /**
     * @return string
     */
    private function pngBytes()
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    }
}

<?php

namespace Tsugi\Services\Cartridge;

use Tsugi\Services\Quiz1\SampleQuiz1;
use Tsugi\Services\Lessons\LessonsCartridge;

/**
 * Tiny mixed Common Cartridge for walker tests (not a Canvas dump).
 */
class Fixtures {

    /**
     * @param string $dir
     * @param string $flavor generic|canvas
     * @return string Absolute path of the .imscc file
     */
    public static function writeMixed($dir, $flavor = 'generic') {
        if ( ! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir) ) {
            throw new ImportException('Cannot create fixture directory: '.$dir);
        }
        $stem = $flavor === 'canvas' ? 'mixed-canvas' : 'mixed-generic';
        $path = rtrim($dir, '/').'/'.$stem.'.imscc';
        if ( file_exists($path) ) {
            unlink($path);
        }
        $zip = new \ZipArchive();
        if ( $zip->open($path, \ZipArchive::CREATE) !== true ) {
            throw new ImportException('Cannot create '.$path);
        }
        $quiz = SampleQuiz1::buildMinimal(1);
        $fileBytes = "tiny-file-bytes\n";
        LessonsCartridge::writeZip(self::mixedLessons($quiz), $zip, array(
            'tsugi_lms' => $flavor === 'canvas' ? 'canvas' : 'generic',
            'topic' => 'lms',
            'load_quiz' => function ($id) use ($quiz) {
                return $quiz;
            },
            'load_file' => function ($item) use ($fileBytes) {
                return array(
                    'bytes' => $fileBytes,
                    'filename' => 'reading.txt',
                );
            },
            'load_page' => function ($item) {
                return array(
                    'title' => 'Welcome',
                    'logical_key' => 'welcome',
                    'body' => '<p>Hello cartridge.</p>',
                );
            },
        ));
        $zip->close();
        return $path;
    }

    /**
     * Two web-link modules for import Select Content tests.
     *
     * @param string $dir
     * @return string
     */
    public static function writeTwoModules($dir) {
        if ( ! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir) ) {
            throw new ImportException('Cannot create fixture directory: '.$dir);
        }
        $path = rtrim($dir, '/').'/two-modules.imscc';
        if ( file_exists($path) ) {
            unlink($path);
        }
        $zip = new \ZipArchive();
        if ( $zip->open($path, \ZipArchive::CREATE) !== true ) {
            throw new ImportException('Cannot create '.$path);
        }
        $l = (object) array(
            'lessons' => (object) array(
                'title' => 'Two weeks',
                'modules' => array(
                    (object) array(
                        'title' => 'Week 1',
                        'anchor' => 'w1',
                        'items' => array(
                            (object) array(
                                'type' => 'web_link',
                                'subtype' => 'reference',
                                'title' => 'One',
                                'href' => 'https://example.com/one',
                            ),
                        ),
                    ),
                    (object) array(
                        'title' => 'Week 2',
                        'anchor' => 'w2',
                        'items' => array(
                            (object) array(
                                'type' => 'web_link',
                                'subtype' => 'reference',
                                'title' => 'Two',
                                'href' => 'https://example.com/two',
                            ),
                        ),
                    ),
                ),
            ),
        );
        LessonsCartridge::writeZip($l, $zip, array(
            'tsugi_lms' => 'generic',
            'topic' => 'lms',
        ));
        $zip->close();
        return $path;
    }

    /**
     * @param \Tsugi\Services\Quiz1\Quiz1 $quiz
     * @return object
     */
    public static function mixedLessons($quiz) {
        return (object) array(
            'lessons' => (object) array(
                'title' => 'Walker mix',
                'modules' => array(
                    (object) array(
                        'title' => 'Week 1',
                        'anchor' => 'w1',
                        'items' => array(
                            (object) array(
                                'type' => 'heading',
                                'title' => 'Start here',
                            ),
                            (object) array(
                                'type' => 'web_link',
                                'subtype' => 'reference',
                                'title' => 'Docs',
                                'href' => 'https://www.dr-chuck.com/',
                            ),
                            (object) array(
                                'type' => 'file',
                                'title' => 'Reading',
                                'filename' => 'reading.txt',
                                'sha256' => hash('sha256', "tiny-file-bytes\n"),
                            ),
                            (object) array(
                                'type' => 'html_page',
                                'title' => 'Welcome',
                                'logical_key' => 'welcome',
                            ),
                            (object) array(
                                'type' => 'quiz',
                                'title' => $quiz->title,
                                'quiz_id' => (int) $quiz->id,
                            ),
                            (object) array(
                                'type' => 'lti',
                                'title' => 'Autograder',
                                'launch' => 'https://www.wa4e.com/tools/autograder/index.php',
                            ),
                            (object) array(
                                'type' => 'discussion',
                                'title' => 'Introduce yourself',
                                'description' => 'Say hello.',
                            ),
                        ),
                    ),
                ),
            ),
        );
    }
}

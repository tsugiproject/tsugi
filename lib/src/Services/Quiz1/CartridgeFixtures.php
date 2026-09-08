<?php

namespace Tsugi\Services\Quiz1;

use Tsugi\UI\LessonsCartridge;

/**
 * Isolated Common Cartridge 1.2 fixtures for LMS interoperability testing.
 *
 * Generates tiny .imscc packages (one question type each) so Canvas/Sakai
 * failures can be attributed to a profile instead of a six-question blob.
 */
class CartridgeFixtures {

    /**
     * @return array<string, callable>
     */
    public static function builders() {
        return array(
            '00-minimal-qti' => array(SampleQuiz::class, 'buildMinimal'),
            '01-multiple-choice' => array(SampleQuiz::class, 'buildMultipleChoice'),
            '02-multiple-response' => array(SampleQuiz::class, 'buildMultipleResponse'),
            '03-true-false' => array(SampleQuiz::class, 'buildTrueFalse'),
            '04-essay' => array(SampleQuiz::class, 'buildEssay'),
            '05-fib-single-answer' => array(SampleQuiz::class, 'buildFillBlankSingle'),
            '06-fib-multiple-answer' => array(SampleQuiz::class, 'buildFillBlankMultiple'),
            '07-pattern-match' => array(SampleQuiz::class, 'buildPatternMatch'),
            '08-all-question-types' => array(SampleQuiz::class, 'build'),
        );
    }

    /**
     * Write every fixture into $dir. Returns map of stem => absolute path.
     *
     * @return array<string, string>
     */
    public static function writeAll($dir) {
        if ( ! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir) ) {
            throw new ExportException('Cannot create fixture directory: '.$dir);
        }
        $written = array();
        foreach ( self::builders() as $stem => $builder ) {
            $written[$stem] = self::writeOne($dir, $stem, call_user_func($builder, 1));
        }
        return $written;
    }

    /**
     * @return string Absolute path of the .imscc file
     */
    public static function writeOne($dir, $stem, Quiz $quiz, array $options = array()) {
        if ( ! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir) ) {
            throw new ExportException('Cannot create fixture directory: '.$dir);
        }
        $path = rtrim($dir, '/').'/'.$stem.'.imscc';
        if ( file_exists($path) ) {
            unlink($path);
        }
        $zip = new \ZipArchive();
        if ( $zip->open($path, \ZipArchive::CREATE) !== true ) {
            throw new ExportException('Cannot create '.$path);
        }
        $lessons = self::lessonsDoc($quiz);
        LessonsCartridge::writeZip($lessons, $zip, array_merge(array(
            'load_quiz' => function ($id) use ($quiz) {
                return $quiz;
            },
        ), $options));
        $zip->close();
        return $path;
    }

    /**
     * @return object
     */
    public static function lessonsDoc(Quiz $quiz) {
        return (object) array(
            'lessons' => (object) array(
                'title' => $quiz->title,
                'modules' => array(
                    (object) array(
                        'title' => 'Week 1',
                        'anchor' => 'w1',
                        'items' => array(
                            (object) array(
                                'type' => 'quiz',
                                'title' => $quiz->title,
                                'quiz_id' => (int) $quiz->id,
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * Mixed module used to verify heading + weblink + quiz positions.
     *
     * @return object
     */
    public static function mixedLessonsDoc(Quiz $quiz) {
        return (object) array(
            'lessons' => (object) array(
                'title' => 'Mixed positions',
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
                                'type' => 'quiz',
                                'title' => $quiz->title,
                                'quiz_id' => (int) $quiz->id,
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

    public static function writeMixed($dir, Quiz $quiz) {
        $path = rtrim($dir, '/').'/09-mixed-module.imscc';
        if ( file_exists($path) ) {
            unlink($path);
        }
        $zip = new \ZipArchive();
        if ( $zip->open($path, \ZipArchive::CREATE) !== true ) {
            throw new ExportException('Cannot create '.$path);
        }
        LessonsCartridge::writeZip(self::mixedLessonsDoc($quiz), $zip, array(
            'tsugi_lms' => 'canvas',
            'load_quiz' => function ($id) use ($quiz) {
                return $quiz;
            },
        ));
        $zip->close();
        return $path;
    }
}

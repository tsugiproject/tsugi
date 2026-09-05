<?php

require_once "src/Core/I18N.php";
require_once "include/setup_i18n.php";
require_once "src/UI/Lessons.php";
require_once "src/UI/LessonsNormalize.php";
require_once "src/Config/ConfigInfo.php";

use Tsugi\UI\Lessons;
use Tsugi\UI\LessonsNormalize;
use Tsugi\Controllers\Files;

class LessonsNormalizeTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;

    private const SHA = '8c2f4d0123456789abcdef0123456789abcdef0123456789abcdef0123456789';

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->apphome = 'http://localhost/app';
        $CFG->wwwroot = 'http://localhost';
        $CFG->fontawesome = 'http://localhost/fontawesome';
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
    }

    public function testIsV2Document() {
        $this->assertFalse(LessonsNormalize::isV2Document(array('title' => 'Classic')));
        $this->assertTrue(LessonsNormalize::isV2Document(array(
            'lessons_json_version' => 2,
            'title' => 'V2',
        )));
    }

    public function testCanAuthorCurrentIsFalseWithoutManifestSession() {
        $prev = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array();
        $_SESSION = array();
        \Tsugi\Core\Manifest::resetRequestCache();
        try {
            $this->assertFalse(\Tsugi\Core\Manifest::canAuthorCurrent());
        } finally {
            $_SESSION = $prev;
            \Tsugi\Core\Manifest::resetRequestCache();
        }
    }

    public function testHeaderToHeading() {
        $out = LessonsNormalize::normalizeItem(array(
            'type' => 'header',
            'text' => 'Videos',
            'level' => 2,
        ));
        $this->assertSame('heading', $out['type']);
        $this->assertSame('Videos', $out['title']);
        $this->assertSame(2, $out['level']);
        $this->assertArrayNotHasKey('text', $out);
    }

    public function testExternalReferenceToWebLink() {
        $out = LessonsNormalize::normalizeItem(array(
            'type' => 'reference',
            'title' => 'PythonAnywhere',
            'href' => 'https://www.pythonanywhere.com/',
        ));
        $this->assertSame('web_link', $out['type']);
        $this->assertSame('reference', $out['subtype']);
        $this->assertSame('https://www.pythonanywhere.com/', $out['href']);
    }

    public function testAssignmentToWebLinkKeepsApphome() {
        $out = LessonsNormalize::normalizeItem(array(
            'type' => 'assignment',
            'title' => 'Installing Django',
            'href' => '{apphome}/assn/dj4e_install52.md',
        ));
        $this->assertSame('web_link', $out['type']);
        $this->assertSame('assignment', $out['subtype']);
        $this->assertSame('{apphome}/assn/dj4e_install52.md', $out['href']);
        $this->assertSame('text/markdown', $out['content_type']);
        $again = LessonsNormalize::normalizeItem($out);
        $this->assertSame($out['href'], $again['href']);
    }

    public function testLocalSlideToWebLink() {
        $out = LessonsNormalize::normalizeItem(array(
            'type' => 'slide',
            'title' => 'Django Data Models',
            'href' => '{apphome}/lectures/DJ-02-Model-Single.pptx',
        ));
        $this->assertSame('web_link', $out['type']);
        $this->assertSame('slides', $out['subtype']);
        $this->assertSame('{apphome}/lectures/DJ-02-Model-Single.pptx', $out['href']);
        $this->assertArrayNotHasKey('filename', $out);
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            $out['content_type']
        );
    }

    public function testCourseOwnedReferenceStaysWebLink() {
        $out = LessonsNormalize::normalizeItem(array(
            'type' => 'reference',
            'title' => 'Install notes',
            'href' => '{apphome}/install.md',
        ));
        $this->assertSame('web_link', $out['type']);
        $this->assertSame('reference', $out['subtype']);
        $this->assertSame('{apphome}/install.md', $out['href']);
        $this->assertSame('text/markdown', $out['content_type']);
    }

    public function testExternalSlideToWebLink() {
        $out = LessonsNormalize::normalizeItem(array(
            'type' => 'slide',
            'title' => 'External Deck',
            'href' => 'https://slides.example.com/week-one.pptx',
        ));
        $this->assertSame('web_link', $out['type']);
        $this->assertSame('slides', $out['subtype']);
        $this->assertSame('https://slides.example.com/week-one.pptx', $out['href']);
    }

    public function testVideoPreservesYoutubeKalturaMediaAndNotes() {
        $in = array(
            'type' => 'video',
            'title' => 'Django Models',
            'youtube' => 'AqsPifp-ccc',
            'kaltura_id' => '1_55pyvf75',
            'media' => 'lesson-08-models/01-DJ-02-Models.m4v',
            'note' => 'Audio is messed up in FCPX',
            'youtube-2016' => 'oldid2016',
            'youtube-2018' => 'oldid2018',
            'youtube_pre_2025' => 'pre2025',
        );
        $out = LessonsNormalize::normalizeItem($in);
        $this->assertSame('web_link', $out['type']);
        $this->assertSame('video', $out['subtype']);
        $this->assertSame('AqsPifp-ccc', $out['youtube']);
        $this->assertSame('1_55pyvf75', $out['kaltura_id']);
        $this->assertSame('lesson-08-models/01-DJ-02-Models.m4v', $out['media']);
        $this->assertSame('Audio is messed up in FCPX', $out['note']);
        $this->assertSame('oldid2016', $out['youtube-2016']);
        $this->assertSame('oldid2018', $out['youtube-2018']);
        $this->assertSame('pre2025', $out['youtube_pre_2025']);
        $this->assertSame('https://www.youtube.com/watch?v=AqsPifp-ccc', $out['href']);
        $this->assertSame('text/html', $out['content_type']);
    }

    public function testBuiltInTdiscusBecomesDiscussionWithoutLaunch() {
        $out = LessonsNormalize::normalizeItem(array(
            'type' => 'discussion',
            'title' => 'Welcome to Django for Everybody',
            'launch' => 'tsugi/tool/tdiscus/',
            'resource_link_id' => 'discussion_welcome',
            'description' => 'Say hello',
        ));
        $this->assertSame('discussion', $out['type']);
        $this->assertArrayNotHasKey('subtype', $out);
        $this->assertArrayNotHasKey('launch', $out);
        $this->assertSame('discussion_welcome', $out['resource_link_id']);
        $this->assertSame('Say hello', $out['description']);
        $this->assertSame('http://localhost/tool/tdiscus/', LessonsNormalize::launchUrlForItem($out));
        $this->assertStringEndsWith('/tool/tdiscus/', LessonsNormalize::builtinDiscussionLaunchUrl());
    }

    public function testLtiBuiltInTdiscusPromotesToDiscussion() {
        $out = LessonsNormalize::normalizeItem(array(
            'type' => 'lti',
            'title' => 'Welcome',
            'launch' => 'http://localhost/tsugi/tool/tdiscus/',
            'resource_link_id' => 'discussion_welcome',
        ));
        $this->assertSame('discussion', $out['type']);
        $this->assertArrayNotHasKey('launch', $out);
        $this->assertArrayNotHasKey('subtype', $out);
    }

    public function testDiscussionWithoutResourceLinkIdGetsOneFromTitle() {
        $out = LessonsNormalize::normalizeItem(array(
            'type' => 'discussion',
            'title' => 'Welcome to Django for Everybody',
            'description' => 'Say hello',
        ));
        $this->assertSame('discussion_welcome_to_django_for_everybody', $out['resource_link_id']);
        $again = LessonsNormalize::normalizeItem($out);
        $this->assertSame($out['resource_link_id'], $again['resource_link_id']);
    }

    public function testDiscussionBlankResourceLinkIdIsRegenerated() {
        $out = LessonsNormalize::normalizeItem(array(
            'type' => 'discussion',
            'title' => 'Office Hours',
            'resource_link_id' => '   ',
        ));
        $this->assertSame('discussion_office_hours', $out['resource_link_id']);
    }

    public function testDocumentAssignsUniqueDiscussionResourceLinkIds() {
        $doc = LessonsNormalize::normalizeDocument(array(
            'title' => 'T',
            'modules' => array(
                array(
                    'title' => 'M',
                    'anchor' => 'm',
                    'items' => array(
                        array('type' => 'discussion', 'title' => 'Welcome'),
                        array('type' => 'discussion', 'title' => 'Welcome'),
                    ),
                ),
            ),
        ));
        $this->assertSame('discussion_welcome', $doc['modules'][0]['items'][0]['resource_link_id']);
        $this->assertSame('discussion_welcome_2', $doc['modules'][0]['items'][1]['resource_link_id']);
        $again = LessonsNormalize::normalizeDocument($doc);
        $this->assertSame($doc['modules'][0]['items'][0]['resource_link_id'], $again['modules'][0]['items'][0]['resource_link_id']);
        $this->assertSame($doc['modules'][0]['items'][1]['resource_link_id'], $again['modules'][0]['items'][1]['resource_link_id']);
    }

    public function testDocumentAssignsDiscussionRlidsInNestedItems() {
        $doc = LessonsNormalize::normalizeDocument(array(
            'title' => 'T',
            'modules' => array(
                array(
                    'title' => 'M',
                    'anchor' => 'm',
                    'items' => array(
                        array(
                            'type' => 'slides',
                            'title' => 'Group',
                            'items' => array(
                                array('type' => 'discussion', 'title' => 'Nested Talk'),
                            ),
                        ),
                    ),
                ),
            ),
        ));
        $this->assertSame(
            'discussion_nested_talk',
            $doc['modules'][0]['items'][0]['items'][0]['resource_link_id']
        );
    }

    public function testAuthorDuplicateResourceLinkIdsAreKept() {
        $doc = LessonsNormalize::normalizeDocument(array(
            'title' => 'T',
            'modules' => array(
                array(
                    'title' => 'M',
                    'anchor' => 'm',
                    'items' => array(
                        array(
                            'type' => 'discussion',
                            'title' => 'Office Hours',
                            'resource_link_id' => 'shared',
                        ),
                        array(
                            'type' => 'discussion',
                            'title' => 'Also Office Hours',
                            'resource_link_id' => 'shared',
                        ),
                    ),
                ),
            ),
        ));
        $this->assertSame('shared', $doc['modules'][0]['items'][0]['resource_link_id']);
        $this->assertSame('shared', $doc['modules'][0]['items'][1]['resource_link_id']);
    }

    public function testGeneratedDiscussionRlidAvoidsExistingIds() {
        $doc = LessonsNormalize::normalizeDocument(array(
            'title' => 'T',
            'modules' => array(
                array(
                    'title' => 'M',
                    'anchor' => 'm',
                    'items' => array(
                        array(
                            'type' => 'lti',
                            'title' => 'Quiz',
                            'launch' => 'mod/gift/',
                            'resource_link_id' => 'discussion_office_hours',
                        ),
                        array(
                            'type' => 'discussion',
                            'title' => 'Office Hours',
                        ),
                    ),
                ),
            ),
        ));
        $this->assertSame('discussion_office_hours_2', $doc['modules'][0]['items'][1]['resource_link_id']);
    }

    public function testModTdiscusLtiStaysLti() {
        $out = LessonsNormalize::normalizeItem(array(
            'type' => 'lti',
            'title' => 'Old forum',
            'launch' => 'mod/tdiscus/',
            'resource_link_id' => 'd1',
        ));
        $this->assertSame('lti', $out['type']);
        $this->assertSame('discussion', $out['subtype']);
        $this->assertSame('mod/tdiscus/', $out['launch']);
        $this->assertSame('mod/tdiscus/', LessonsNormalize::launchUrlForItem($out));
    }

    public function testLtiCustomAndMetadataSurvive() {
        $custom = array(
            array('key' => 'exercise', 'value' => 'ex1'),
            array('key' => 'config', 'json' => array('title' => 'Peer Review')),
        );
        $out = LessonsNormalize::normalizeItem(array(
            'type' => 'lti',
            'title' => 'Peer Graded: Installation',
            'launch' => 'mod/peer-grade/',
            'resource_link_id' => 'install',
            'target' => '_blank',
            'custom' => $custom,
            'learning_objectives' => array('Install Python'),
        ));
        $this->assertSame('lti', $out['type']);
        $this->assertSame('peer_grade', $out['subtype']);
        $this->assertSame($custom, $out['custom']);
        $this->assertSame('_blank', $out['target']);
        $this->assertSame(array('Install Python'), $out['learning_objectives']);
    }

    public function testLegacySlideWithDownloadHrefStaysWebLink() {
        $href = '/files/download/'.self::SHA;
        $out = LessonsNormalize::normalizeItem(array(
            'type' => 'slide',
            'title' => 'Week One Reading',
            'href' => $href,
            'filename' => 'week-one.pdf',
            'content_type' => 'application/pdf',
        ));
        $this->assertSame('web_link', $out['type']);
        $this->assertSame('slides', $out['subtype']);
        $this->assertSame(self::SHA, $out['sha256']);
        $this->assertSame($href, $out['href']);
        $this->assertSame('week-one.pdf', $out['filename']);
        $this->assertSame('application/pdf', $out['content_type']);
        $this->assertSame('Week One Reading', $out['title']);
    }

    public function testAuthoredFileAndHtmlPageStayCanonical() {
        $file = LessonsNormalize::normalizeItem(array(
            'type' => 'file',
            'subtype' => 'slides',
            'title' => 'Week One Reading',
            'href' => '/files/download/'.self::SHA,
            'sha256' => self::SHA,
            'filename' => 'week-one.pdf',
            'content_type' => 'application/pdf',
        ));
        $this->assertSame('file', $file['type']);
        $this->assertSame('slides', $file['subtype']);
        $this->assertSame(self::SHA, $file['sha256']);

        $page = LessonsNormalize::normalizeItem(array(
            'type' => 'html_page',
            'subtype' => 'reference',
            'title' => 'Syllabus',
            'href' => '{apphome}/syllabus.md',
        ));
        $this->assertSame('html_page', $page['type']);
        $this->assertSame('reference', $page['subtype']);
        $this->assertSame('{apphome}/syllabus.md', $page['href']);
    }

    public function testUnknownAttributesSurviveNormalizeAndV2() {
        $in = array(
            'type' => 'reference',
            'title' => 'Odd Link',
            'href' => 'https://example.org/x',
            'todo' => 'fix later',
            'FCP' => true,
            'project' => 'dj4e',
            'review' => 'needs audio',
            'obscure_typo_field' => 'keep-me',
        );
        $out = LessonsNormalize::normalizeItem($in);
        $this->assertSame('keep-me', $out['obscure_typo_field']);
        $this->assertSame('fix later', $out['todo']);
        $this->assertTrue($out['FCP']);
        $this->assertSame('dj4e', $out['project']);
        $this->assertSame('needs audio', $out['review']);

        $doc = array(
            'title' => 'T',
            'modules' => array(
                array('title' => 'M', 'anchor' => 'm', 'items' => array($in)),
            ),
        );
        $v2 = json_decode(LessonsNormalize::serializeV2($doc), true);
        $item = $v2['modules'][0]['items'][0];
        $this->assertSame('keep-me', $item['obscure_typo_field']);
        $this->assertSame('fix later', $item['todo']);
    }

    public function testExplicitSubtypeBeatsInference() {
        $out = LessonsNormalize::normalizeItem(array(
            'type' => 'lti',
            'title' => 'Custom Quiz Tool',
            'launch' => 'mod/peer-grade/',
            'resource_link_id' => 'x',
            'subtype' => 'quiz',
        ));
        $this->assertSame('quiz', $out['subtype']);
        $again = LessonsNormalize::normalizeItem($out);
        $this->assertSame('quiz', $again['subtype']);
    }

    public function testExplicitIconBeatsInferredIcon() {
        $item = array(
            'type' => 'video',
            'title' => 'Clip',
            'youtube' => 'abc123',
            'icon' => 'fa-star',
        );
        $this->assertSame('fa-star', LessonsNormalize::iconKey($item));
        $norm = LessonsNormalize::normalizeItem($item);
        $this->assertSame('fa-star', LessonsNormalize::iconKey($norm));
        $this->assertSame('video', LessonsNormalize::presentationKind($norm));
    }

    public function testNormalizationIsIdempotent() {
        $samples = array(
            array('type' => 'header', 'text' => 'Videos', 'level' => 2),
            array('type' => 'reference', 'title' => 'PA', 'href' => 'https://www.pythonanywhere.com/'),
            array('type' => 'assignment', 'href' => '{apphome}/assn/x.md'),
            array('type' => 'slide', 'title' => 'S', 'href' => '{apphome}/lectures/a.pptx'),
            array('type' => 'slide', 'title' => 'E', 'href' => 'https://example.com/a.pptx'),
            array('type' => 'video', 'title' => 'V', 'youtube' => 'abc', 'kaltura_id' => '1_x', 'media' => 'a.m4v'),
            array('type' => 'discussion', 'title' => 'D', 'launch' => 'mod/tdiscus/', 'resource_link_id' => 'd1'),
            array('type' => 'discussion', 'title' => 'D2', 'launch' => 'tsugi/tool/tdiscus/', 'resource_link_id' => 'd2'),
            array('type' => 'lti', 'title' => 'D3', 'launch' => 'tsugi/tool/tdiscus/', 'resource_link_id' => 'd3'),
            array('type' => 'lti', 'title' => 'Q', 'launch' => 'mod/gift/?quiz=1', 'resource_link_id' => 'q1'),
            array(
                'type' => 'file',
                'subtype' => 'slides',
                'title' => 'Week One Reading',
                'href' => '/files/download/'.self::SHA,
                'sha256' => self::SHA,
                'filename' => 'week-one.pdf',
                'content_type' => 'application/pdf',
            ),
        );
        foreach ( $samples as $item ) {
            $once = LessonsNormalize::normalizeItem($item);
            $twice = LessonsNormalize::normalizeItem($once);
            $this->assertSame($once, $twice, 'normalize(normalize(item)) === normalize(item) for '.$item['type']);
        }
    }

    public function testLegacyJsonExportFileIsNotRewrittenByRead() {
        $path = __DIR__ . '/../fixtures/lessons/py4e-modern-lessons-items.json';
        $before = file_get_contents($path);
        $this->assertNotFalse($before);
        new Lessons($path);
        $after = file_get_contents($path);
        $this->assertSame($before, $after);
    }

    public function testSerializeV2IsDeterministic() {
        $doc = json_decode(file_get_contents(__DIR__ . '/../fixtures/lessons/py4e-modern-lessons-items.json'), true);
        $a = LessonsNormalize::serializeV2($doc);
        $b = LessonsNormalize::serializeV2($doc);
        $this->assertSame($a, $b);
        $decoded = json_decode($a, true);
        $this->assertSame(2, $decoded['lessons_json_version']);
        $this->assertSame('heading', $decoded['modules'][0]['items'][0]['type']);
    }

    public function testPy4eFixtureFieldsSurviveNormalizeAndV2() {
        $path = __DIR__ . '/../fixtures/lessons/py4e-modern-lessons-items.json';
        $doc = json_decode(file_get_contents($path), true);
        $fields = LessonsNormalize::collectItemFields($doc);
        $this->assertNotEmpty($fields);

        $v2 = json_decode(LessonsNormalize::serializeV2($doc), true);
        $by_path = array();
        foreach ( LessonsNormalize::collectItemFields($v2) as $row ) {
            $by_path[$row['path']."\0".$row['key']] = $row['value'];
        }

        foreach ( $fields as $row ) {
            $key = $row['key'];
            $value = $row['value'];
            if ( $key === 'type' ) {
                continue;
            }
            if ( $key === 'text' ) {
                $title_key = $row['path']."\0title";
                $text_key = $row['path']."\0text";
                $this->assertTrue(
                    array_key_exists($title_key, $by_path) || array_key_exists($text_key, $by_path),
                    'header text should survive as title or text at '.$row['path']
                );
                if ( array_key_exists($title_key, $by_path) ) {
                    $this->assertSame($value, $by_path[$title_key]);
                } else {
                    $this->assertSame($value, $by_path[$text_key]);
                }
                continue;
            }
            $lookup = $row['path']."\0".$key;
            $this->assertArrayHasKey($lookup, $by_path, 'Lost field '.$key.' at '.$row['path']);
            $this->assertSame($value, $by_path[$lookup], 'Changed field '.$key.' at '.$row['path']);
        }
    }

    public function testRenderLegacyAndNormalizedItemsMatch() {
        $lessons = new class extends Lessons {
            public function __construct() {
            }
        };
        $module = (object) array('title' => 'Test Module');
        $pairs = array(
            array(
                (object) array('type' => 'header', 'text' => 'Videos', 'level' => 2),
                (object) array('type' => 'heading', 'title' => 'Videos', 'level' => 2),
            ),
            array(
                (object) array('type' => 'reference', 'title' => 'Docs', 'href' => 'https://example.com/ref'),
                (object) array('type' => 'web_link', 'subtype' => 'reference', 'title' => 'Docs', 'href' => 'https://example.com/ref'),
            ),
            array(
                (object) array('type' => 'assignment', 'title' => 'HW', 'href' => 'http://example.com/assign'),
                (object) array('type' => 'web_link', 'subtype' => 'assignment', 'title' => 'HW', 'href' => 'http://example.com/assign'),
            ),
            array(
                (object) array('type' => 'slide', 'title' => 'Deck', 'href' => 'http://example.com/slide'),
                (object) array('type' => 'web_link', 'subtype' => 'slides', 'title' => 'Deck', 'href' => 'http://example.com/slide'),
            ),
            array(
                (object) array('type' => 'video', 'title' => 'Clip', 'youtube' => 'abc123'),
                (object) array('type' => 'web_link', 'subtype' => 'video', 'title' => 'Clip', 'youtube' => 'abc123'),
            ),
        );

        foreach ( $pairs as $pair ) {
            ob_start();
            $lessons->renderItem($pair[0], $module);
            $legacy = ob_get_clean();
            ob_start();
            $lessons->renderItem($pair[1], $module);
            $v2 = ob_get_clean();
            $this->assertSame($legacy, $v2, 'Render mismatch for '.$pair[0]->type);
        }
    }

    public function testGenericFoundationalFallbacksRender() {
        $lessons = new class extends Lessons {
            public function __construct() {
            }
        };
        $module = (object) array('title' => 'Test Module');

        ob_start();
        $lessons->renderItem((object) array(
            'type' => 'file',
            'title' => 'Handout',
            'href' => '/files/download/'.self::SHA,
            'filename' => 'handout.bin',
        ), $module);
        $file_html = ob_get_clean();
        $this->assertStringContainsString('Handout', $file_html);
        $this->assertStringContainsString('fa-file-o', $file_html);
        $this->assertStringContainsString('/files/download/'.self::SHA, $file_html);

        ob_start();
        $lessons->renderItem((object) array(
            'type' => 'web_link',
            'title' => 'Somewhere',
            'href' => 'https://example.org/',
        ), $module);
        $link_html = ob_get_clean();
        $this->assertStringContainsString('Somewhere', $link_html);
        $this->assertStringContainsString('fa-external-link', $link_html);

        ob_start();
        $lessons->renderItem((object) array(
            'type' => 'html_page',
            'title' => 'Course Page',
            'href' => '{apphome}/info.md',
        ), $module);
        $page_html = ob_get_clean();
        $this->assertStringContainsString('Course Page', $page_html);
        $this->assertStringContainsString('fa-file-text-o', $page_html);
        $this->assertStringContainsString('http://localhost/app/info.md', $page_html);
    }

    public function testGenericLinkRejectsNonHttpSchemes() {
        $lessons = new class extends Lessons {
            public function __construct() {
            }
        };
        $module = (object) array('title' => 'Test Module');
        ob_start();
        $lessons->renderItem((object) array(
            'type' => 'web_link',
            'title' => 'Nope',
            'href' => 'javascript:alert(1)',
        ), $module);
        $html = ob_get_clean();
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('Nope', $html);
    }

    public function testConstructorDoesNotTreatSiblingDomainsAsCourseOwned() {
        $out = LessonsNormalize::normalizeItem(array(
            'type' => 'reference',
            'title' => 'Samples',
            'href' => 'https://samples.dj4e.com/',
        ));
        $this->assertSame('web_link', $out['type']);
        $this->assertSame('https://samples.dj4e.com/', $out['href']);
    }

    public function testLoadedBuiltInDiscussionHasNoLaunch() {
        $tmp = tempnam(sys_get_temp_dir(), 'tsugi-lessons-v2');
        file_put_contents($tmp, json_encode(array(
            'title' => 'T',
            'modules' => array(
                array(
                    'title' => 'M',
                    'anchor' => 'm',
                    'items' => array(
                        array(
                            'type' => 'discussion',
                            'title' => 'Hello',
                            'launch' => 'tsugi/tool/tdiscus/',
                            'resource_link_id' => 'd1',
                        ),
                    ),
                ),
            ),
        )));
        try {
            $L = new Lessons($tmp);
            $item = $L->lessons->modules[0]->items[0];
            $this->assertSame('discussion', $item->type);
            $this->assertFalse(isset($item->launch));
            $this->assertFalse(isset($item->subtype));
            $found = $L->getLtiByRlid('d1');
            $this->assertNotNull($found);
            $this->assertTrue(LessonsNormalize::isDiscussion($found));
            $this->assertCount(1, $L->flattenedDiscussions());
        } finally {
            @unlink($tmp);
        }
    }

    public function testGiftLaunchInfersQuiz() {
        $out = LessonsNormalize::normalizeItem(array(
            'type' => 'lti',
            'title' => 'Quiz',
            'launch' => 'mod/gift/?quiz=Py4Inf-01-Intro.txt',
            'resource_link_id' => 'q',
        ));
        $this->assertSame('quiz', $out['subtype']);
    }

    public function testPythonautoLaunchInfersAutograder() {
        $out = LessonsNormalize::normalizeItem(array(
            'type' => 'lti',
            'title' => 'Hello',
            'launch' => 'tools/pythonauto/',
            'resource_link_id' => 'a',
        ));
        $this->assertSame('autograder', $out['subtype']);
    }
}

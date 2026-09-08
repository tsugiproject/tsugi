<?php

namespace Tsugi\Services\Quiz1;

use Tsugi\Util\CC;

/**
 * Structural checks for a Quiz1 Common Cartridge 1.2 package.
 *
 * Development aid — not a full XSD validator and not a production feature.
 */
class CartridgeValidator {

    /**
     * CC 1.1 tokens that must not appear in Quiz1 cartridge XML.
     * Content Packaging `imscp_v1p1` and QTI item profiles (`v0p1`) are allowed.
     */
    const FORBIDDEN_CC11 = array(
        'imsccv1p1',
        'ccv1p1',
        'imscc_xmlv1p1',
        'imswl_xmlv1p1',
        'imsdt_v1p1',
        '<schemaversion>1.1.0</schemaversion>',
    );

    /**
     * @return array{ok:bool,lines:string[],errors:string[]}
     */
    public static function validate($imsccPath) {
        $lines = array();
        $errors = array();

        if ( ! is_readable($imsccPath) ) {
            return self::fail($lines, $errors, 'ZIP', 'Cannot read '.$imsccPath);
        }

        $zip = new \ZipArchive();
        if ( $zip->open($imsccPath) !== true ) {
            return self::fail($lines, $errors, 'ZIP', 'Not a readable ZIP: '.$imsccPath);
        }
        $lines[] = 'PASS ZIP structure';

        $manifest = $zip->getFromName('imsmanifest.xml');
        if ( $manifest === false ) {
            $zip->close();
            return self::fail($lines, $errors, 'manifest XML', 'imsmanifest.xml missing');
        }

        $dom = new \DOMDocument();
        if ( ! @$dom->loadXML($manifest) ) {
            $zip->close();
            return self::fail($lines, $errors, 'manifest XML', 'imsmanifest.xml is not well formed');
        }
        $lines[] = 'PASS manifest XML';

        $xp = new \DOMXPath($dom);
        $xp->registerNamespace('c', CC::CC_NS);

        $version = trim((string) $xp->evaluate('string(/c:manifest/c:metadata/c:schemaversion)'));
        if ( $version !== CC::VERSION ) {
            $errors[] = 'FAIL CC version: expected '.CC::VERSION.', got '.($version !== '' ? $version : '(missing)');
        } else {
            $lines[] = 'PASS CC version: '.$version;
        }

        $schema = trim((string) $xp->evaluate('string(/c:manifest/c:metadata/c:schema)'));
        if ( $schema !== CC::SCHEMA_NAME ) {
            $errors[] = 'FAIL CC schema name: '.$schema;
        }

        $names = array();
        for ( $i = 0; $i < $zip->numFiles; $i++ ) {
            $name = $zip->getNameIndex($i);
            if ( is_string($name) ) {
                $names[$name] = true;
            }
        }

        $ids = array();
        foreach ( $xp->query('//*[@identifier]') as $node ) {
            $id = $node->getAttribute('identifier');
            if ( isset($ids[$id]) ) {
                $errors[] = 'FAIL manifest references: duplicate identifier '.$id;
            }
            $ids[$id] = true;
        }

        foreach ( $xp->query('//*[@identifierref]') as $node ) {
            $ref = $node->getAttribute('identifierref');
            if ( ! isset($ids[$ref]) ) {
                $errors[] = 'FAIL manifest references: identifierref '.$ref.' does not resolve';
            }
        }

        foreach ( $xp->query('//c:file[@href] | //c:resource[@href]') as $node ) {
            $href = $node->getAttribute('href');
            if ( $href === '' ) {
                continue;
            }
            if ( str_starts_with($href, '/') || preg_match('#^[A-Za-z]:[\\\\/]#', $href) ) {
                $errors[] = 'FAIL manifest references: absolute path '.$href;
            }
            if ( ! isset($names[$href]) ) {
                $errors[] = 'FAIL manifest references: missing file '.$href;
            }
        }

        if ( ! self::hasErrorPrefix($errors, 'FAIL manifest references') ) {
            $lines[] = 'PASS manifest references';
        }

        $qtiCount = 0;
        $profiles = array();
        foreach ( $xp->query('//c:resource') as $res ) {
            $type = $res->getAttribute('type');
            if ( $type !== CC::QTI_ASSESSMENT_TYPE ) {
                continue;
            }
            $file = $xp->query('c:file[@href]', $res)->item(0);
            if ( ! $file ) {
                $errors[] = 'FAIL QTI XML: assessment resource has no file';
                continue;
            }
            $href = $file->getAttribute('href');
            $xml = $zip->getFromName($href);
            if ( $xml === false ) {
                $errors[] = 'FAIL QTI XML: '.$href.' missing';
                continue;
            }
            $qdom = new \DOMDocument();
            if ( ! @$qdom->loadXML($xml) ) {
                $errors[] = 'FAIL QTI XML: '.$href.' is not well formed';
                continue;
            }
            $qtiCount++;
            $qxp = new \DOMXPath($qdom);
            $qxp->registerNamespace('q', CC::QTI_NS);

            if ( $qdom->documentElement->namespaceURI !== CC::QTI_NS ) {
                $errors[] = 'FAIL QTI XML: '.$href.' wrong namespace';
            }
            $schemaLoc = $qdom->documentElement->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'schemaLocation');
            if ( $schemaLoc !== CC::QTI_SCHEMA_LOCATION ) {
                $errors[] = 'FAIL QTI XML: '.$href.' schemaLocation is not CC 1.2';
            }
            $score = self::qtiMeta($qxp, $qxp->query('/q:questestinterop/q:assessment')->item(0), 'qmd_scoretype');
            if ( $score !== 'Percentage' ) {
                $errors[] = 'FAIL assessment metadata: qmd_scoretype='.($score ?? '(missing)');
            }

            foreach ( $qxp->query('//q:item') as $item ) {
                $ident = $item->getAttribute('ident');
                $profile = self::qtiMeta($qxp, $item, 'cc_profile');
                $profiles[] = $profile;
                if ( $profile === 'cc.essay.v0p1' ) {
                    $sol = $qxp->query('q:itemfeedback[@ident="solution"]/q:solution/q:solutionmaterial/q:material/q:mattext', $item);
                    $bare = $qxp->query('q:itemfeedback[@ident="solution"]/q:material', $item);
                    if ( $sol->length !== 1 ) {
                        $errors[] = 'FAIL essay solution hierarchy: '.$ident;
                    }
                    if ( $bare->length !== 0 ) {
                        $errors[] = 'FAIL essay solution hierarchy: '.$ident.' has bare material';
                    }
                }
                if ( $profile === 'cc.fib.v0p1' ) {
                    $ors = $qxp->query('q:resprocessing/q:respcondition/q:conditionvar/q:or', $item);
                    $ves = $qxp->query('q:resprocessing/q:respcondition[@continue="No"]//q:varequal', $item);
                    if ( $ves->length > 1 && $ors->length !== 1 ) {
                        $errors[] = 'FAIL FIB multiple-answer processing: '.$ident.' missing <or>';
                    }
                }
            }

            foreach ( self::FORBIDDEN_CC11 as $token ) {
                if ( str_contains($xml, $token) ) {
                    $errors[] = 'FAIL CC version: QTI '.$href.' contains '.$token;
                }
            }
        }

        if ( $qtiCount > 0 && ! self::hasErrorPrefix($errors, 'FAIL QTI XML') ) {
            $lines[] = 'PASS QTI XML';
        }
        if ( $qtiCount > 0 && ! self::hasErrorPrefix($errors, 'FAIL assessment metadata') ) {
            $lines[] = 'PASS assessment metadata';
        }
        if ( ! self::hasErrorPrefix($errors, 'FAIL essay solution') ) {
            $lines[] = 'PASS essay solution hierarchy';
        }
        if ( ! self::hasErrorPrefix($errors, 'FAIL FIB') ) {
            $lines[] = 'PASS FIB multiple-answer processing';
        }

        $meta = $zip->getFromName('course_settings/module_meta.xml');
        if ( $meta !== false ) {
            $mdom = new \DOMDocument();
            if ( ! @$mdom->loadXML($meta) ) {
                $errors[] = 'FAIL module positions: module_meta.xml is not well formed';
            } else {
                $mx = new \DOMXPath($mdom);
                $mx->registerNamespace('m', 'http://canvas.instructure.com/xsd/cccv1p0');
                foreach ( $mx->query('//m:item') as $item ) {
                    $pos = $mx->query('m:position', $item);
                    $ident = $item->getAttribute('identifier');
                    if ( $pos->length !== 1 ) {
                        $errors[] = 'FAIL module positions: item '.$ident.' has '.$pos->length.' <position> elements';
                    }
                }
                if ( ! self::hasErrorPrefix($errors, 'FAIL module positions') ) {
                    $lines[] = 'PASS module positions';
                }
            }
        }

        foreach ( self::FORBIDDEN_CC11 as $token ) {
            if ( str_contains($manifest, $token) ) {
                $errors[] = 'FAIL CC version: manifest contains '.$token;
            }
        }

        $zip->close();

        $label = array(
            'cc.multiple_choice.v0p1' => 'multiple_choice',
            'cc.multiple_response.v0p1' => 'multiple_response',
            'cc.true_false.v0p1' => 'true_false',
            'cc.essay.v0p1' => 'essay',
            'cc.fib.v0p1' => 'fib',
            'cc.pattern_match.v0p1' => 'pattern_match',
        );
        if ( count($profiles) > 0 ) {
            $lines[] = '';
            $lines[] = count($profiles).' questions:';
            foreach ( $profiles as $profile ) {
                $name = $label[$profile] ?? $profile;
                $lines[] = '  PASS '.$name;
            }
        }

        return array(
            'ok' => count($errors) === 0,
            'lines' => $lines,
            'errors' => $errors,
        );
    }

    public static function format(array $result) {
        $out = implode("\n", $result['lines']);
        if ( count($result['errors']) > 0 ) {
            if ( $out !== '' ) {
                $out .= "\n";
            }
            $out .= implode("\n", $result['errors']);
        }
        return $out."\n";
    }

    private static function qtiMeta(\DOMXPath $xp, $ctx, $label) {
        if ( ! $ctx ) {
            return null;
        }
        foreach ( $xp->query('.//q:qtimetadatafield', $ctx) as $field ) {
            $lab = $xp->query('q:fieldlabel', $field)->item(0);
            if ( $lab && trim($lab->textContent) === $label ) {
                $entry = $xp->query('q:fieldentry', $field)->item(0);
                return $entry ? trim($entry->textContent) : null;
            }
        }
        return null;
    }

    private static function hasErrorPrefix(array $errors, $prefix) {
        foreach ( $errors as $err ) {
            if ( str_starts_with($err, $prefix) ) {
                return true;
            }
        }
        return false;
    }

    private static function fail(array $lines, array $errors, $step, $message) {
        $errors[] = 'FAIL '.$step.': '.$message;
        return array('ok' => false, 'lines' => $lines, 'errors' => $errors);
    }
}

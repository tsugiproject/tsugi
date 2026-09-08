<?php

namespace Tsugi\Util;

/**
 * Canvas course-export quiz settings (assessment_meta.xml).
 *
 * Only used when Setup export is Canvas. Not Common Cartridge.
 */
class CanvasAssessmentMeta {

    const NS = 'http://canvas.instructure.com/xsd/cccv1p0';

    /**
     * @param string $identifier Quiz folder / QTI assessment ident
     * @param string $title
     * @param string $description HTML or text
     * @param int|float $pointsPossible
     * @param int $allowedAttempts
     * @param string $quizIdentifierref Quiz migration_id (folder / QTI assessment ident)
     * @return string UTF-8 XML
     */
    public static function xml($identifier, $title, $description, $pointsPossible, $allowedAttempts = 1, $quizIdentifierref = '') {
        $esc = function ($value) {
            return htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        };
        $points = is_numeric($pointsPossible) ? (0 + $pointsPossible) : 0;
        $attempts = (int) $allowedAttempts;
        if ( $attempts === 0 ) {
            $attempts = 1;
        }
        $ref = $quizIdentifierref !== '' ? $quizIdentifierref : $identifier;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<quiz identifier="'.$esc($identifier).'" xmlns="'.self::NS.'" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="'.self::NS.' https://canvas.instructure.com/xsd/cccv1p0.xsd">'."\n";
        $xml .= '  <title>'.$esc($title).'</title>'."\n";
        $xml .= '  <description>'.$esc($description).'</description>'."\n";
        $xml .= '  <quiz_type>assignment</quiz_type>'."\n";
        $xml .= '  <points_possible>'.$esc($points).'</points_possible>'."\n";
        $xml .= '  <allowed_attempts>'.$esc($attempts).'</allowed_attempts>'."\n";
        $xml .= '  <scoring_policy>keep_highest</scoring_policy>'."\n";
        $xml .= '  <shuffle_answers>false</shuffle_answers>'."\n";
        $xml .= '  <show_correct_answers>true</show_correct_answers>'."\n";
        $xml .= '  <one_question_at_a_time>false</one_question_at_a_time>'."\n";
        $xml .= '  <cant_go_back>false</cant_go_back>'."\n";
        $xml .= '  <anonymous_submissions>false</anonymous_submissions>'."\n";
        $xml .= '  <available>true</available>'."\n";
        $xml .= '  <assignment identifier="'.$esc($identifier).'_A">'."\n";
        $xml .= '    <title>'.$esc($title).'</title>'."\n";
        $xml .= '    <points_possible>'.$esc($points).'</points_possible>'."\n";
        $xml .= '    <grading_type>points</grading_type>'."\n";
        $xml .= '    <submission_types>online_quiz</submission_types>'."\n";
        $xml .= '    <quiz_identifierref>'.$esc($ref).'</quiz_identifierref>'."\n";
        $xml .= '  </assignment>'."\n";
        $xml .= '</quiz>'."\n";
        return $xml;
    }
}

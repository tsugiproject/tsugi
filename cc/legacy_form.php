<?php

/**
 * Cartridge form markup shared by cc/index.php and cc/export.php.
 */
class CcExportForm {

    /**
     * Print the pre-export file scan on the legacy cartridge form.
     *
     * @param array{scanned:int,files:int,listings:int,links:int,paths:list<string>} $summary
     */
    public static function echoFilesPreview(array $summary) {
        echo('<p>Slide, reference, assignment, and solution links scanned: '.(int) $summary['scanned']."</p>\n");
        echo('<p>Available for a thick cartridge: '.(int) $summary['files']." file(s)</p>\n");
        if ( isset($summary['listings']) && (int) $summary['listings'] > 0 ) {
            echo('<p>Additional listings of those same files: '.(int) $summary['listings']."</p>\n");
        }
        echo('<p>Will remain as web links even in a thick cartridge: '.(int) $summary['links']."</p>\n");
        if ( isset($summary['paths']) && is_array($summary['paths']) && count($summary['paths']) > 0 ) {
            echo("<p>Files a thick cartridge would include:</p>\n<ul>\n");
            foreach ( $summary['paths'] as $path ) {
                echo('<li>'.htmlentities((string) $path)."</li>\n");
            }
            echo("</ul>\n");
        }
    }

    /**
     * Thin / thick dropdown. Default is thick (include file contents).
     *
     * @param string $id
     */
    public static function echoCartridgeSelect($id) {
        $id = (string) $id;
        echo('<p>'."\n");
        echo('<label for="'.htmlentities($id).'">Cartridge style:</label>'."\n");
        echo('<select name="cartridge" id="'.htmlentities($id).'">'."\n");
        echo('  <option value="thick" selected>Thick cartridge (include file contents)</option>'."\n");
        echo('  <option value="thin">Thin cartridge (files as web links)</option>'."\n");
        echo('</select>'."\n");
        echo('</p>'."\n");
    }

    /**
     * Print the pre-export GIFT scan on the legacy cartridge form.
     *
     * @param array{scanned:int,gift:int,found:int,lti:int,paths:list<string>} $summary
     */
    public static function echoGiftPreview(array $summary) {
        echo('<p>LTI items scanned: '.(int) $summary['scanned']."</p>\n");
        echo('<p>Look like GIFT quizzes: '.(int) $summary['gift']."</p>\n");
        echo('<p>Readable GIFT on disk (available to convert to QTI): '.(int) $summary['found']."</p>\n");
        echo('<p>Will remain as LTI: '.(int) $summary['lti']."</p>\n");
        if ( isset($summary['paths']) && is_array($summary['paths']) && count($summary['paths']) > 0 ) {
            echo("<p>GIFT files a QTI conversion would include:</p>\n<ul>\n");
            foreach ( $summary['paths'] as $path ) {
                echo('<li>'.htmlentities((string) $path)."</li>\n");
            }
            echo("</ul>\n");
        }
    }

    /**
     * Convert-to-QTI / keep-LTI dropdown. Default is convert found GIFT to QTI.
     *
     * @param string $id
     */
    public static function echoGiftQtiSelect($id) {
        $id = (string) $id;
        echo('<p>'."\n");
        echo('<label for="'.htmlentities($id).'">GIFT quizzes:</label>'."\n");
        echo('<select name="gift_qti" id="'.htmlentities($id).'">'."\n");
        echo('  <option value="qti">Convert found GIFT quizzes to QTI</option>'."\n");
        echo('  <option value="lti">Keep GIFT quizzes as LTI launches</option>'."\n");
        echo('</select>'."\n");
        echo('</p>'."\n");
    }

}

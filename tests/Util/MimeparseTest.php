<?php

use \Tsugi\Util\Mimeparse;

require_once("src/Util/Mimeparse.php");


/**
 * Note from Chuck: This was downloaded from 
 *
 * https://code.google.com/p/mimeparse/
 * 
 * on July 13, 2015 - it has an MIT license.
 *
 * I split it into two files to pull out the unit tests
 */


// Unit tests //////////////////////////////////////////////////////////////////////////////////////////////////////////

class MimeparseTest extends PHPUnit_Framework_TestCase
{

    public $m;

    public function testGet() {
        $this->m = new Mimeparse();

        $this->assertEquals($this->m->parse_media_range("application/xml;q=1"), array(0 => "application", 1=> "xml", 2=> array("q" => "1")),
            "application/xml;q=1");

        $this->assertEquals($this->m->parse_media_range("application/xml"), array(0 => "application", 1=> "xml", 2=> array("q" => "1")),
            "application/xml");

        $this->assertEquals($this->m->parse_media_range("application/xml;q="), array(0 => "application", 1=> "xml", 2=> array("q" => "1")),
            "application/xml;q=");

        $this->assertEquals($this->m->parse_media_range("application/xml ; q=1;b=other"), array(0 => "application", 1=> "xml", 2=> array("q" => "1", "b" => "other")),
            "application/xml ; q=1;b=other");

        $this->assertEquals($this->m->parse_media_range("application/xml ; q=2;b=other"), array(0 => "application", 1=> "xml", 2=> array("q" => "1", "b" => "other")),
            "application/xml ; q=2;b=other");

        /* Java URLConnection class sends an Accept header that includes a single "*" */
        $this->assertEquals($this->m->parse_media_range(" *; q=.2"), array(0 => "*", 1=> "*", 2=> array("q" => ".2") ),
            " *; q=.2");

        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $accept = "text/*;q=0.3, text/html;q=0.7, text/html;level=1, text/html;level=2;q=0.4, */*;q=0.5";

        $this->assertEquals (1, $this->m->quality("text/html;level=1", $accept), 'text/html;level=1');
        $this->assertEquals (0.7, $this->m->quality("text/html", $accept), 'text/html');
        $this->assertEquals (0.3, $this->m->quality("text/plain", $accept), 'text/plain');
        $this->assertEquals (0.5, $this->m->quality("image/jpeg", $accept), 'image/jpeg');
        $this->assertEquals (0.4, $this->m->quality("text/html;level=2", $accept), 'text/html;level=2');
        $this->assertEquals (0.7, $this->m->quality("text/html;level=3", $accept), 'text/html;level=3');

        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        global $supported_mime_types;
        $supported_mime_types = array("application/xbel+xml", "application/xml");

        # direct match
        $this->assert_best_match ("application/xbel+xml", "application/xbel+xml");
        # direct match with a q parameter
        $this->assert_best_match ("application/xbel+xml", "application/xbel+xml; q=1");
        # direct match of our second choice with a q parameter
        $this->assert_best_match ("application/xml", "application/xml; q=1");
        # match using a subtype wildcard
        $this->assert_best_match ("application/xml", "application/*; q=1");
        # match using a type wildcard
        $this->assert_best_match ("application/xml", "* / *");

        $supported_mime_types = array( "application/xbel+xml", "text/xml" );
        # match using a type versus a lower weighted subtype
        $this->assert_best_match ("text/xml", "text/ *;q=0.5,* / *;q=0.1");
        # fail to match anything
        $this->assert_best_match (null, "text/html,application/atom+xml; q=0.9" );
        # common AJAX scenario
        $supported_mime_types = array( "application/json", "text/html" );
        $this->assert_best_match("application/json", "application/json, text/javascript, */*");
        # verify fitness sorting
        $supported_mime_types = array( "application/json", "text/html" );
        $this->assert_best_match("application/json", "application/json, text/html;q=0.9");


        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $supported_mime_types = array('image/*', 'application/xml');
        # match using a type wildcard
        $this->assert_best_match ('image/*', 'image/png');
        # match using a wildcard for both requested and supported
        $this->assert_best_match ('image/*', 'image/*');
    }


    function assert_best_match($expected, $header) {
        global $supported_mime_types;
        $this->assertEquals($expected,$this->m->best_match($supported_mime_types, $header));
    }

}

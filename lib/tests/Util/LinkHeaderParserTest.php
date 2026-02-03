<?php
/**
 * tests for our parser
 */

require_once "src/Util/LinkHeader.php";
require_once "src/Util/LinkHeaderItem.php";

use \Tsugi\Util\LinkHeader;
use \Tsugi\Util\LinkHeaderItem;

/**
 * @author  List of contributors <https://github.com/libgraviton/link-header-rel-parser/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 */
class LinkHeaderParserTest extends \PHPUnit\Framework\TestCase
{

    /**
     * basic behavior
     *
     * @param string $header header
     * @param array  $result result
     *
     * @dataProvider basicDataProvider
     *
     * @return void
     */
    // TODO: Use this or delete this.
    public function checkBasicParsing($header, $result)
    {
        $linkHeader = LinkHeader::fromString($header);

        $this->assertEquals(count($result), count($linkHeader->all()));

        foreach ($result as $relation => $url) {
            $this->assertEquals($relation, $linkHeader->getRel($relation)->getRel());
            $this->assertEquals($url, $linkHeader->getRel($relation)->getUri());
        }
    }

    /**
     * dataprovider for basic tests
     *
     * @return array test items
     */
    public static function basicDataProvider()
    {
        return [
            'rql' => [
                '<http://localhost/service/?select(id)&sort(-order)&limit(1)>; rel="self", '.
                '<http://localhost/service/?select(id)&sort(-order)&limit(1,10)>; rel="next", '.
                '<http://localhost/service/?select(id)&sort(-order)&limit(1,100)>; rel="last", '.
                '<http://localhost/schema/service/collection>; rel="schema"',
                [
                    'self' => 'http://localhost/service/?select(id)&sort(-order)&limit(1)',
                    'next' => 'http://localhost/service/?select(id)&sort(-order)&limit(1,10)',
                    'last' => 'http://localhost/service/?select(id)&sort(-order)&limit(1,100)',
                    'schema' => 'http://localhost/schema/service/collection'
                ]
            ],
            'fiql' => [
                '<http://localhost/service/?last_name==foo*,(age=lt=55;age=gt=5)>; rel="self", '.
                '<http://localhost/service/?last_name==foo*,(age=lt=55;age=gt=5),page=2>; rel="next" ',
                [
                    'self' => 'http://localhost/service/?last_name==foo*,(age=lt=55;age=gt=5)',
                    'next' => 'http://localhost/service/?last_name==foo*,(age=lt=55;age=gt=5),page=2'
                ]
            ],
            'no-spaces-trailing-comma' => [
                '<http://localhost/service/self>; rel="self",'.
                '<http://localhost/service/next>; rel="next",',
                [
                    'self' => 'http://localhost/service/self',
                    'next' => 'http://localhost/service/next'
                ]
            ],
            'invalid' => [
                '<;>; rel=self",',
                []
            ],
            'empty' => [
                '',
                []
            ],
            'null' => [
                false,
                []
            ]
        ];
    }

    /**
     * test to create link header programmatically
     *
     * @return void
     */
    public function testCreationAndManipulation()
    {
        $header = new LinkHeader();
        $header->add(new LinkHeaderItem('http://localhost?limit(10,10)', 'self'));
        $header->add(new LinkHeaderItem('http://localhost?limit(10,30)', ['rel' => 'next']));
        $header->add(new LinkHeaderItem('http://localhost?limit(10,0)', ['rel' => 'prev', 'added' => 'more']));

        $this->assertEquals(
            '<http://localhost?limit(10,10)>; rel="self", '.
            '<http://localhost?limit(10,30)>; rel="next", '.
            '<http://localhost?limit(10,0)>; rel="prev"; added="more"',
            (string) $header
        );

        // retrieval
        $this->assertEquals(
            'http://localhost?limit(10,30)',
            $header->getRel('next')->getUri()
        );

        $this->assertEquals(
            'self',
            $header->getRel('self')->getRel()
        );

        $this->assertEquals(
            'more',
            $header->getRel('prev')->getAttribute('added')
        );

        // non existent
        $this->assertNull($header->getRel('dude'));

        // manipulate more
        $header->removeRel('prev');
        $header->getRel('next')->setUri('http://newhost');

        $this->assertEquals(
            '<http://localhost?limit(10,10)>; rel="self", <http://newhost>; rel="next"',
            (string) $header
        );
    }
}

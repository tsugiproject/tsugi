<?php
/**
 * Represents a Link header.
 */

namespace Tsugi\Util;

/**
 * @author  List of contributors <https://github.com/libgraviton/link-header-rel-parser/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 */
class LinkHeader
{
    /**
     * @var LinkHeaderItem[]
     */
    private $items;

    const PATTERN = '/\<(?<uri>.*)\>;(\s?)rel=\"(?<rel>.*)\"/U';

    /**
     * Constructor
     *
     * @param LinkHeaderItem[] $items link header items
     */
    public function __construct(?array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Builds a LinkHeader instance from a string.
     *
     * @param string $headerValue value of complete header
     *
     * @return LinkHeader
     */
    public static function fromString($headerValue): LinkHeader {
        $matches = [];

        if (empty($headerValue)) {
            return new self([]);
        }

        preg_match_all(self::PATTERN, $headerValue, $matches, PREG_SET_ORDER);

        return new self(
            array_map(
                function ($match) {
                    return new LinkHeaderItem($match['uri'], $match['rel']);
                },
                $matches
            )
        );
    }

    /**
     * get all items
     *
     * @return LinkHeaderItem[]
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * add a LinkHeaderItem.
     *
     * @param LinkHeaderItem $item item to add
     *
     * @return LinkHeader
     */
    public function add(LinkHeaderItem $item)
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * gets an item for a certain rel
     *
     * @param string $rel rel
     *
     * @return LinkHeaderItem|null item
     */
    public function getRel(string $rel)
    {
        foreach ($this->items as $item) {
            if ($item->getRel() == $rel) {
                return $item;
            }
        }

        return null;
    }

    /**
     * removes a certain relation
     *
     * @param string $rel rel name
     *
     * @return void
     */
    public function removeRel(string $rel)
    {
        foreach ($this->items as $key => $item) {
            if ($item->getRel() == $rel) {
                unset($this->items[$key]);
            }
        }
        $this->items = array_values($this->items);
    }

    /**
     * Cast contents to string.
     *
     * @return string
     */
    public function __toString()
    {
        return implode(', ', $this->items);
    }
}

<?php
/**
 * Represents a Link header item.
 */

namespace Tsugi\Util;

/**
 * @author  List of contributors <https://github.com/libgraviton/link-header-rel-parser/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 */
class LinkHeaderItem
{
    /**
     * @var string
     */
    private $uri;

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * Constructor.
     *
     * @param string       $uri        uri value of item
     * @param string|array $attributes array of attributes or rel attribute
     */
    public function __construct($uri, $attributes)
    {
        $this->uri = $uri;

        if (is_string($attributes)) {
            $this->setAttribute('rel', $attributes);
        }

        if (is_array($attributes)) {
            foreach ($attributes as $name => $value) {
                $this->setAttribute($name, $value);
            }
        }
    }

    /**
     * cast item to string
     *
     * @return string
     */
    public function __toString()
    {
        $values = array('<'.$this->uri.'>');

        foreach ($this->attributes as $name => $value) {
            $values[] = sprintf('%s="%s"', $name, $value);
        }

        return implode('; ', $values);
    }

    /**
     * Get URI.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Set a new URI.
     *
     * @param string $uri new URI value
     *
     * @return LinkHeaderItem
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Get rel attribute
     *
     * @return string
     */
    public function getRel()
    {
        $relation = $this->getAttribute('rel');
        return empty($relation) ? '' : $relation;
    }

    /**
     * Set attribute
     *
     * @param string $name  attribute name
     * @param string $value attribute value
     *
     * @return LinkHeaderItem
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Get an attribute.
     *
     * @param string $name attribute name
     *
     * @return string
     */
    public function getAttribute($name)
    {
        return empty($this->attributes[$name]) ? '' : $this->attributes[$name];
    }
}

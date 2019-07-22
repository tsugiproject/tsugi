<?php

namespace Tsugi\UI;

use \Tsugi\UI\MenuEntry;

/**
 * Our class to generate menus
 */
class MenuEntry {

    /**
     * The constant for a separator
     */
    public static $SEPARATOR = '----------';

    /**
     * The link data
     */
    public $link = false;

    /**
     * The place to go for a link
     */
    public $href = false;

    /**
     * Extra attributes
     */
    public $attr = false;

    /**
     * Construct a menu entry from a link and href
     *
     * @param $link The text of the link - can be text, HTML, or even an img tag
     * @param $href An optional place to go when the link is clicked.  Also can be
     * a Menu.
     * @param $attr An optional string to add within the anchor tag
     */
    public function __construct($link, $href=false, $attr=false) {
        $this->link = $link;
        $this->attr = $attr;
        if ( $href instanceof \Tsugi\UI\Menu ){
            $this->href = $href->menu;
        } else {
            $this->href = $href;
        }
    }

    /**
     * Construct a menu entry separator
     *
     * @return MenuEntry A MenuEntry separator
     */
    public static function separator()
    {
        return new MenuEntry(self::$SEPARATOR);
    }

}

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
     * The Left menu entries
     */
    public $href = false;

    /**
     * Construct a menu entry from a link and href
     *
     * @param $link The text of the link - can be text, HTML, or even an img tag
     * @param $href An optional place to go when the link is clicked.  Also can be
     * a Menu.
     */
    public function __construct($link, $href=false) { 
        $this->link = $link;
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

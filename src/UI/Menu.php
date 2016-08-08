<?php

namespace Tsugi\UI;

use \Tsugi\UI\Menu;

/**
 * Our class to generate menus
 */
class Menu {

    /**
     * The entries - an array of MenuEntry objects
     */
    public $menu = array();

    /**
     * Add an entry to the menu
     *
     * @param $menuentry - An entry
     * @return The instance is returned to allow chaining syntax
     */
    public function add($entry, $top=false)
    {
        if ( $top ) {
            array_unshift($this->menu, $entry);
        } else {
            $this->menu[] = $entry;
        }
        return $this;
    }

}

<?php

namespace Tsugi\UI;

use \Tsugi\UI\Menu;
use \Tsugi\UI\MenuEntry;

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
     * @param $entry a MenuEntry
     * @param $push true if this is to be put before the rest of the items in the menue
     *
     * @return Menu The instance is returned to allow chaining syntax
     */
    public function add($entry, $push=false)
    {
        if ( $push ) {
            array_unshift($this->menu, $entry);
        } else {
            $this->menu[] = $entry;
        }
        return $this;
    }

    /**
     * Add an link to the menu
     *
     * @param $link The text of the link - can be text, HTML, or even an img tag
     * @param $href An optional place to go when the link is clicked.  Also can be
     * a Menu.
     * @param $push true if this is to be put before the rest of the items in the menue
     *
     * @return Menu The instance is returned to allow chaining syntax
     */
    public function addLink($link, $href, $push=false)
    {
        $entry = new MenuEntry($link, $href);
        return $this->add($entry, $push);
    }

    /**
     * Add a separator to the menu
     *
     * @param $push true if this is to be put before the rest of the items in the menue
     *
     * @return Menu The instance is returned to allow chaining syntax
     */
    public function addSeparator($push=false)
    {
        $entry = MenuEntry::separator();
        return $this->add($entry, $push);
    }

}

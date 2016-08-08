<?php

namespace Tsugi\UI;

use \Tsugi\UI\MenuSet;

/**
 * Our class to capture a set of top menus
 *
 * A top menu looks like
 *
 *     Home Left Left        Right Right
 */
class MenuSet {

    /**
     * The Home Menu
     */
    public $home = false;

    /**
     * The Left Menu
     */
    public $left = false;

    /**
     * The Right Menu
     */
    public $right = false;

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

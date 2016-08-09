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
     * Set the Home Menu
     *
     * @param $link The text of the link - can be text, HTML, or even an img tag
     * @param $href An optional place to go when the link is clicked
     *
     * @return The instance to allow for chaining
     */
    public function setHome($link, $href)
    {
        $this->home = new \Tsugi\UI\MenuEntry($link, $href);
        return $this;
    }

    /**
     * Add an entty to the Left Menu
     *
     * @param $link The text of the link - can be text, HTML, or even an img tag
     * @param $href An optional place to go when the link is clicked
     * @param $push Indicates to push down the other menu entries
     *
     * @return The instance to allow for chaining
     */
    public function addLeft($link, $href, $push=false)
    {
        if ( $this->left == false ) $this->left = new Menu();
        $x = new \Tsugi\UI\MenuEntry($link, $href, $push);
        $this->left->add($x, $push);
        return $this;
    }

    /**
     * Add an entty to the Right Menu
     *
     * @param $link The text of the link - can be text, HTML, or even an img tag
     * @param $href An optional place to go when the link is clicked
     * @param $push Indicates to push down the other menu entries
     *
     * @return The instance to allow for chaining
     */
    public function addRight($link, $href, $push=true)
    {
        if ( $this->right == false ) $this->right = new Menu();
        $x = new \Tsugi\UI\MenuEntry($link, $href, $push);
        $this->right->add($x, $push);
        return $this;
    }

    /**
     * Export a menu to JSON
     *
     * @param $pretty - True if we want pretty output
     *
     * @return JSON string for the menu
     */
    public function export($pretty=false)
    {
        $tmp = new \stdClass();
        if ( $this->home != false ) $tmp->home = $this->home;
        if ( $this->left != false ) $tmp->left = $this->left->menu;
        if ( $this->right != false ) $tmp->right = $this->right->menu;
        if ( $pretty ) {
            return json_encode($tmp, JSON_PRETTY_PRINT);
        } else {
            return json_encode($tmp);
        }
    }

}

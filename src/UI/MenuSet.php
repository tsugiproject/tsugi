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
     * @return MenuSet The instance to allow for chaining
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
     * @param $attr An optional string to add within the anchor tag
     *
     * @return MenuSet The instance to allow for chaining
     */
    public function addLeft($link, $href, $push=false, $attr=false)
    {
        if ( $this->left == false ) $this->left = new Menu();
        $x = new \Tsugi\UI\MenuEntry($link, $href, $attr);
        $this->left->add($x, $push);
        return $this;
    }

    /**
     * Add an entty to the Right Menu
     *
     * @param $link The text of the link - can be text, HTML, or even an img tag
     * @param $href An optional place to go when the link is clicked
     * @param $push Indicates to push down the other menu entries
     * @param $attr An optional string to add within the anchor tag
     *
     * @return MenuSet The instance to allow for chaining
     */
    public function addRight($link, $href, $push=true, $attr=false)
    {
        if ( $this->right == false ) $this->right = new Menu();
        $x = new \Tsugi\UI\MenuEntry($link, $href, $attr);
        $this->right->add($x, $push);
        return $this;
    }

    /**
     * Export a menu to JSON
     *
     * @param $pretty - True if we want pretty output
     *
     * @return string JSON string for the menu
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

    /**
     * Import a menu from JSON
     *
     * @param $json_str The menu as exported
     *
     * @return The MenuSet as parsed or false on error
     */
    public static function import($json_str)
    {
        try {
            $json = json_decode($json_str); 
            // print_r($json);
            $retval = new MenuSet();
            $retval->home = new \Tsugi\UI\MenuEntry($json->home->link, $json->home->href);
            if ( isset($json->left) ) $retval->left = self::importRecurse($json->left, 0);
            if ( isset($json->right) ) $retval->right = self::importRecurse($json->right, 0);
            return $retval;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Walk a JSON tree to import a hierarchy of menus
     */
    private static function importRecurse($entry, $depth) {
        if ( isset($entry->menu) ) $entry = $entry->menu; // Skip right past these
        if ( ! is_array($entry) ) {
            $link = $entry->link;
            $href = $entry->href;
            if ( is_string($href) ) {
                return new \Tsugi\UI\MenuEntry($link, $href);
            }
            $submenu = self::importRecurse($href, $depth+1);
            return new \Tsugi\UI\MenuEntry($link, $submenu);
        }

        $submenu = new \Tsugi\UI\Menu();
        foreach($entry as $child) {
            $submenu->add(self::importRecurse($child, $depth+1));
        }
        return $submenu;
    }

}

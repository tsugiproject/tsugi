<?php

/**
 * Shared top links for Admin → Mail pages (and testmail).
 *
 * @param string $active One of: home, test, bulk, suppress, events, sent
 */
function mail_admin_nav($active = 'home') {
    global $CFG;

    $base = rtrim((string) $CFG->wwwroot, '/') . '/admin/mail';
    $items = array(
        'home' => array('Mail', $base . '/'),
        'test' => array('Test E-Mail', rtrim((string) $CFG->wwwroot, '/') . '/admin/testmail'),
        'bulk' => array('Bulk campaigns', $base . '/bulk'),
        'suppress' => array('Suppressed', $base . '/suppress'),
        'events' => array('SES events', $base . '/ses-events'),
        'sent' => array('Sent log', $base . '/sent'),
        'admin' => array('Admin', rtrim((string) $CFG->wwwroot, '/') . '/admin'),
    );

    echo "<p>\n";
    $first = true;
    foreach ( $items as $key => $item ) {
        if ( !$first ) {
            echo " |\n";
        }
        $first = false;
        $label = $item[0];
        $href = $item[1];
        if ( $key === $active ) {
            echo '<strong>'.htmlentities($label).'</strong>';
        } else {
            echo '<a href="'.htmlentities($href).'">'.htmlentities($label).'</a>';
        }
    }
    echo "\n</p>\n";
}

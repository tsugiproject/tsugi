<?php

use \Tsugi\Crypt\AesCtr;
use \Tsugi\Core\User;


if ( ! function_exists('hex2bin')) {
function hex2bin($hexString)
{
    $hexLength = strlen($hexString);
    // only hex numbers is allowed
    if ($hexLength % 2 != 0 || preg_match("/[^\da-fA-F]/",$hexString)) return FALSE;
    unset($binString);
    $binString = "";
    for ($x = 1; $x <= $hexLength/2; $x++)
    {
     $binString .= chr(hexdec(substr($hexString,2 * $x - 2,2)));
    }
    return $binString;
}

}

function parse_badge_id($encrypted, $lesson) {
    global $CFG, $PDOX;

    $decrypted = \Tsugi\Crypt\AesCtr::decrypt(hex2bin($encrypted), $CFG->badge_encrypt_password, 256);
    $pieces = explode(':',$decrypted);

    if ( count($pieces) != 3 || !is_numeric($pieces[0]) || !is_numeric($pieces[2])) {
        return 'Decryption failed';
    }
    $pattern = '/[^a-zA-Z0-9_]/';
    if ( preg_match($pattern, $pieces[1]) ) {
        return 'File pattern fail';
    }

    // Make sure this is a legit badge
    $legit = false;
    foreach($lesson->lessons->badges as $badge) {
        if ( $badge->image == $pieces[1].'.png') {
            $legit = true;
            break;
        }
    }
    if ( ! $legit ) {
        return 'Bad badge image';
    }

    // Load the badge
    $file = $CFG->badge_path . '/' . $pieces[1] . '.png';
    $png = file_get_contents($file);
    if ( $png === false ) return 'File contents fail';

    $row = $PDOX->rowDie(
            "SELECT displayname, email, user_key, U.login_at, title FROM {$CFG->dbprefix}lti_user AS U
            JOIN {$CFG->dbprefix}lti_membership AS M
            ON U.user_id = M.user_id 
            JOIN {$CFG->dbprefix}lti_context AS C
            ON M.context_id = C.context_id 
            WHERE U.user_id = :UID AND M.context_id = :CID",
            array(":UID" => $pieces[0], ":CID" => $pieces[2])
    );
    if ( $row === false ) return 'Metadata failed';

    return array($row, $png, $pieces, $badge);
}

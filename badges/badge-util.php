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

function get_assertion($encrypted, $date, $code, $badge, $title, $email ) {
    global $CFG;

    $image = $CFG->badge_url.'/'.$code.'.png';
    $recepient = 'sha256$' . hash('sha256', $email . $CFG->badge_assert_salt);
    $assert_id = $CFG->wwwroot . "/badges/assert.php?id=". $encrypted;
    $badge_url = $CFG->wwwroot . "/badges/badge-info.php?id=". $encrypted;
    $retval = <<< EOF
{
  "@context": "https://w3id.org/openbadges/v2",
  "type": "Assertion",
  "id": "$assert_id",
  "recipient": {
    "type": "email",
    "hashed": true,
    "salt": "$CFG->badge_assert_salt",
    "identity": "$recepient"
  },
  "issuedOn": "$date",
  "badge": "$badge_url",
  "image" : "$image",
  "evidence" : "$CFG->apphome",
  "verification": {
    "type": "hosted"
  }
}
EOF;
    $retval = json_encode(json_decode($retval), JSON_PRETTY_PRINT);
    return $retval;
}

function get_badge($encrypted, $code, $badge, $title) {
    global $CFG;

    $image = $CFG->badge_url.'/'.$code.'.png';
    $badge_url = $CFG->wwwroot . "/badges/badge-info.php?id=". $encrypted;
    $badge_issuer = $CFG->wwwroot . "/badges/badge-issuer.php?id=". $encrypted;
    $retval = <<< EOF
{
  "@context": "https://w3id.org/openbadges/v2",
    "id": "$badge_url",
    "type": "BadgeClass",
    "name": "$badge->title",
    "image": "$image",
    "description": "Completed $badge->title in course $title at $CFG->servicename",
    "criteria": "$CFG->apphome",
    "issuer": "$badge_issuer"
}
EOF;
    $retval = json_encode(json_decode($retval), JSON_PRETTY_PRINT);
    return $retval;
}

function get_issuer($encrypted, $code, $badge, $title) {
    global $CFG;

    $image = $CFG->badge_url.'/'.$code.'.png';
    $badge_issuer = $CFG->wwwroot . "/badges/badge-issuer.php?id=". $encrypted;
    $parse = parse_url($CFG->wwwroot);
    $domain = $parse['host'];
    $retval = <<< EOF
{
  "@context": "https://w3id.org/openbadges/v2",
      "id": "$badge_issuer",
      "type": "Profile",
      "url": "$CFG->apphome",
      "name": "$CFG->servicename",
      "org": "$CFG->servicename",
      "verification": {
         "allowedOrigins": "$domain"
      }
}
EOF;
    $retval = json_encode(json_decode($retval), JSON_PRETTY_PRINT);
    return $retval;
}

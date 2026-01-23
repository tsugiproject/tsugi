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

/**
 * Check if badge configuration is properly set up
 * Returns an error message string if configuration is missing, or false if OK
 */
function check_badge_config() {
    global $CFG;
    
    $missing = array();
    
    if ( !isset($CFG->badge_encrypt_password) || empty($CFG->badge_encrypt_password) || $CFG->badge_encrypt_password === false ) {
        $missing[] = '$CFG->badge_encrypt_password';
    }
    
    if ( !isset($CFG->badge_assert_salt) || empty($CFG->badge_assert_salt) || $CFG->badge_assert_salt === false ) {
        $missing[] = '$CFG->badge_assert_salt';
    }
    
    if ( !isset($CFG->badge_path) || empty($CFG->badge_path) ) {
        $missing[] = '$CFG->badge_path';
    }
    
    if ( !isset($CFG->badge_url) || empty($CFG->badge_url) ) {
        $missing[] = '$CFG->badge_url';
    }
    
    if ( count($missing) > 0 ) {
        $config_file = isset($CFG->dirroot) ? $CFG->dirroot . '/config.php' : 'config.php';
        $msg = "<h2>Badge Configuration Required</h2>\n";
        $msg .= "<p>The following badge configuration parameters are missing or not set in your <code>config.php</code>:</p>\n";
        $msg .= "<ul>\n";
        foreach($missing as $param) {
            $msg .= "<li><code>" . htmlspecialchars($param) . "</code></li>\n";
        }
        $msg .= "</ul>\n";
        $msg .= "<h3>How to Configure Badges</h3>\n";
        $msg .= "<p>To enable badge functionality, add the following to your <code>config.php</code> file:</p>\n";
        $msg .= "<pre>\n";
        $msg .= "// Badge generation settings - once you set these values to something\n";
        $msg .= "// other than false and start issuing badges - don't change these or\n";
        $msg .= "// existing badge images that have been downloaded from the system\n";
        $msg .= "// will be invalidated.\n";
        $msg .= "\$CFG->badge_encrypt_password = \"somethinglongwithhex387438758974987\";\n";
        $msg .= "\$CFG->badge_assert_salt = \"mediumlengthhexstring\";\n";
        $msg .= "\n";
        $msg .= "// This folder contains the badge images\n";
        $msg .= "\$CFG->badge_path = \$CFG->dirroot . '/../bimages';\n";
        $msg .= "\$CFG->badge_url = \$CFG->apphome . '/bimages';\n";
        $msg .= "</pre>\n";
        $msg .= "<p>For more information, see <code>config-dist.php</code> in your Tsugi installation.</p>\n";
        return $msg;
    }
    
    return false;
}

function parse_badge_id($encrypted, $lesson) {
    global $CFG, $PDOX;

    $config_error = check_badge_config();
    if ( $config_error !== false ) {
        return $config_error;
    }

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

    $config_error = check_badge_config();
    if ( $config_error !== false ) {
        return $config_error;
    }

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

    $config_error = check_badge_config();
    if ( $config_error !== false ) {
        return $config_error;
    }

    $image = $CFG->badge_url.'/'.$code.'.png';
    // Use code-based URL instead of encrypted ID
    $badge_url = $CFG->wwwroot . "/badges/badge-info.php?code=". urlencode($code);
    $badge_issuer = $CFG->wwwroot . "/badges/badge-issuer.php";
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

    $config_error = check_badge_config();
    if ( $config_error !== false ) {
        return $config_error;
    }

    // Issuer is now installation-wide, no encrypted ID needed
    $badge_issuer = $CFG->wwwroot . "/badges/badge-issuer.php";
    $parse = parse_url($CFG->wwwroot);
    $domain = $parse['host'];
    
    // Use config email if set and not empty, otherwise default to placeholder
    $issuer_email = (isset($CFG->badge_issuer_email) && !empty($CFG->badge_issuer_email)) 
        ? $CFG->badge_issuer_email 
        : "badge_issuer_email_not_set@example.com";
    
    $retval = <<< EOF
{
  "@context": "https://w3id.org/openbadges/v2",
      "id": "$badge_issuer",
      "type": "Issuer",
      "url": "$CFG->apphome",
      "name": "$CFG->servicename",
      "email": "$issuer_email",
      "org": "$CFG->servicename",
      "verification": {
         "allowedOrigins": "$domain"
      }
}
EOF;
    $retval = json_encode(json_decode($retval), JSON_PRETTY_PRINT);
    return $retval;
}

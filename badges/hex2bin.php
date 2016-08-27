<?php
if ( ! function_exists('hex2bin')) {
function hex2bin($hexString)
{
    $hexLenght = strlen($hexString);
    // only hex numbers is allowed
    if ($hexLenght % 2 != 0 || preg_match("/[^\da-fA-F]/",$hexString)) return FALSE;
    unset($binString);
    $binString = "";
    for ($x = 1; $x <= $hexLenght/2; $x++)
    {
     $binString .= chr(hexdec(substr($hexString,2 * $x - 2,2)));
    }
    return $binString;
}

}

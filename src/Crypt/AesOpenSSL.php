<?php

// Tsugi specific Aes encryption using OpenSSL Library
// https://stackoverflow.com/questions/3422759/php-aes-encrypt-decrypt

/* (From Stack Overflow)

    The following code:

    o   uses AES256 in CBC mode
    o   is compatible with other AES implementations, but not mcrypt, since mcrypt uses PKCS#5 instead of PKCS#7.
    o   generates a key from the provided password using SHA256
    o   generates a hmac hash of the encrypted data for integrity check
    o   generates a random IV for each message
    o   prepends the IV (16 bytes) and the hash (32 bytes) to the ciphertext
    o   should be pretty secure

 */

namespace Tsugi\Crypt;

class AesOpenSSL {

  /**
   * Unicode multi-byte character safe
   *
   * @param plaintext source text to be encrypted
   * @param password  the password to use to generate a key
   * @param nBits     (ignored - always set to 256)
   * @return string   encrypted text
   */
  public static function encrypt($plaintext, $password, $nBits=256) {
    $method = "AES-256-CBC";
    $key = hash('sha256', $password, true);
    $iv = openssl_random_pseudo_bytes(16);

    $ciphertext = openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv);
    $hash = hash_hmac('sha256', $ciphertext . $iv, $key, true);

    $retval = base64_encode($iv . $hash . $ciphertext);
    return $retval;
  }

  /**
   * Decrypt a text encrypted by AES in counter mode of operation
   *
   * @param ciphertext source text to be decrypted
   * @param password   the password to use to generate a key
   * @param nBits     (ignored - always set to 256)
   * @return string    decrypted text
   */
  public static function decrypt($ciphertext, $password, $nBits=256) {
    $method = "AES-256-CBC";
    $ivHashCiphertext = base64_decode($ciphertext);
    $iv = substr($ivHashCiphertext, 0, 16);
    $hash = substr($ivHashCiphertext, 16, 32);
    $ciphertext = substr($ivHashCiphertext, 48);
    $key = hash('sha256', $password, true);

    if (!hash_equals(hash_hmac('sha256', $ciphertext . $iv, $key, true), $hash)) return null;

    return openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
  }


  /*
   * Unsigned right shift function, since PHP has neither >>> operator nor unsigned ints
   *
   * @param a  number to be shifted (32-bit integer)
   * @param b  number of bits to shift a to the right (0..31)
   * @return integer  a right-shifted and zero-filled by b bits
   */
  private static function urs($a, $b) {
echo("a ".gettype($a)."\n");
echo("b ".gettype($b)."\n");
    $a &= 0xffffffff; $b &= 0x1f;  // (bounds check)
    if ($a&0x80000000 && $b>0) {   // if left-most bit set
      $a = ($a>>1) & 0x7fffffff;   //   right-shift one bit & clear left-most bit
      $a = $a >> ($b-1);           //   remaining right-shifts
    } else {                       // otherwise
      $a = ($a>>$b);               //   use normal right-shift
    }
    return $a;
  }

}
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
?>

<?php

use phpseclib\Crypt\RSA;
use phpseclib\Crypt\AES;
use phpseclib\Crypt\Random;

class JOSE_JWE extends JOSE_JWT {
    var $plain_text;
    var $cipher_text;
    var $content_encryption_key;
    var $jwe_encrypted_key;
    var $encryption_key;
    var $mac_key;
    var $iv;
    var $authentication_tag;
    var $auth_data;

    function __construct($input = null) {
        if ($input instanceof JOSE_JWT) {
            $this->raw = $input->toString();
        } else {
            $this->raw = $input;
        }
        unset($this->header['typ']);
    }

    function encrypt($public_key_or_secret, $algorithm = 'RSA1_5', $encryption_method = 'A128CBC-HS256') {
        $this->header['alg'] = $algorithm;
        $this->header['enc'] = $encryption_method;
        if (
            $public_key_or_secret instanceof JOSE_JWK &&
            !array_key_exists('kid', $this->header) &&
            array_key_exists('kid', $public_key_or_secret->components)
        ) {
            $this->header['kid'] = $public_key_or_secret->components['kid'];
        }
        $this->plain_text = $this->raw;
        $this->generateContentEncryptionKey($public_key_or_secret);
        $this->encryptContentEncryptionKey($public_key_or_secret);
        $this->generateIv();
        $this->deriveEncryptionAndMacKeys();
        $this->encryptCipherText();
        $this->generateAuthenticationTag();
        return $this;
    }

    function decrypt($private_key_or_secret) {
        $this->decryptContentEncryptionKey($private_key_or_secret);
        $this->deriveEncryptionAndMacKeys();
        $this->decryptCipherText();
        $this->checkAuthenticationTag();
        return $this;
    }

    function toString() {
        return implode('.', array(
            $this->compact((object) $this->header),
            $this->compact($this->jwe_encrypted_key),
            $this->compact($this->iv),
            $this->compact($this->cipher_text),
            $this->compact($this->authentication_tag)
        ));
    }

    private function rsa($public_or_private_key, $padding_mode) {
        if ($public_or_private_key instanceof JOSE_JWK) {
            $rsa = $public_or_private_key->toKey();
        } else if ($public_or_private_key instanceof RSA) {
            $rsa = $public_or_private_key;
        } else {
            $rsa = new RSA();
            $rsa->loadKey($public_or_private_key);
        }
        $rsa->setEncryptionMode($padding_mode);
        return $rsa;
    }

    private function cipher() {
        switch ($this->header['enc']) {
            case 'A128GCM':
            case 'A256GCM':
                throw new JOSE_Exception_UnexpectedAlgorithm('Algorithm not supported');
            case 'A128CBC-HS256':
            case 'A256CBC-HS512':
                $cipher = new AES(AES::MODE_CBC);
                break;
            default:
                throw new JOSE_Exception_UnexpectedAlgorithm('Unknown algorithm');
        }
        switch ($this->header['enc']) {
            case 'A128GCM':
            case 'A128CBC-HS256':
                $cipher->setBlockLength(128);
                break;
            case 'A256GCM':
            case 'A256CBC-HS512':
                $cipher->setBlockLength(256);
                break;
            default:
                throw new JOSE_Exception_UnexpectedAlgorithm('Unknown algorithm');
        }
        return $cipher;
    }

    private function generateRandomBytes($length) {
        return Random::string($length);
    }

    private function generateIv() {
        switch ($this->header['enc']) {
            case 'A128GCM':
            case 'A128CBC-HS256':
                $this->iv = $this->generateRandomBytes(128 / 8);
                break;
            case 'A256GCM':
            case 'A256CBC-HS512':
                $this->iv = $this->generateRandomBytes(256 / 8);
                break;
            default:
                throw new JOSE_Exception_UnexpectedAlgorithm('Unknown algorithm');
        }
    }

    private function generateContentEncryptionKey($public_key_or_secret) {
        if ($this->header['alg'] == 'dir') {
            $this->content_encryption_key = $public_key_or_secret;
        } else {
            switch ($this->header['enc']) {
                case 'A128GCM':
                case 'A128CBC-HS256':
                    $this->content_encryption_key = $this->generateRandomBytes(256 / 8);
                    break;
                case 'A256GCM':
                case 'A256CBC-HS512':
                    $this->content_encryption_key = $this->generateRandomBytes(512 / 8);
                    break;
                default:
                    throw new JOSE_Exception_UnexpectedAlgorithm('Unknown algorithm');
            }
        }
    }

    private function encryptContentEncryptionKey($public_key_or_secret) {
        switch ($this->header['alg']) {
            case 'RSA1_5':
                $rsa = $this->rsa($public_key_or_secret, RSA::ENCRYPTION_PKCS1);
                $this->jwe_encrypted_key = $rsa->encrypt($this->content_encryption_key);
                break;
            case 'RSA-OAEP':
                $rsa = $this->rsa($public_key_or_secret, RSA::ENCRYPTION_OAEP);
                $this->jwe_encrypted_key = $rsa->encrypt($this->content_encryption_key);
                break;
            case 'dir':
                $this->jwe_encrypted_key = '';
                return;
            case 'A128KW':
            case 'A256KW':
            case 'ECDH-ES':
            case 'ECDH-ES+A128KW':
            case 'ECDH-ES+A256KW':
                throw new JOSE_Exception_UnexpectedAlgorithm('Algorithm not supported');
            default:
                throw new JOSE_Exception_UnexpectedAlgorithm('Unknown algorithm');
        }
        if (!$this->jwe_encrypted_key) {
            throw new JOSE_Exception_EncryptionFailed('Master key encryption failed');
        }
    }

    private function decryptContentEncryptionKey($private_key_or_secret) {
        $this->generateContentEncryptionKey(null); # NOTE: run this always not to make timing difference
        $fake_content_encryption_key = $this->content_encryption_key;
        switch ($this->header['alg']) {
            case 'RSA1_5':
                $rsa = $this->rsa($private_key_or_secret, RSA::ENCRYPTION_PKCS1);
                $this->content_encryption_key = $rsa->decrypt($this->jwe_encrypted_key);
                break;
            case 'RSA-OAEP':
                $rsa = $this->rsa($private_key_or_secret, RSA::ENCRYPTION_OAEP);
                $this->content_encryption_key = $rsa->decrypt($this->jwe_encrypted_key);
                break;
            case 'dir':
                $this->content_encryption_key = $private_key_or_secret;
                break;
            case 'A128KW':
            case 'A256KW':
            case 'ECDH-ES':
            case 'ECDH-ES+A128KW':
            case 'ECDH-ES+A256KW':
                throw new JOSE_Exception_UnexpectedAlgorithm('Algorithm not supported');
            default:
                throw new JOSE_Exception_UnexpectedAlgorithm('Unknown algorithm');
        }
        if (!$this->content_encryption_key) {
            # NOTE:
            #  Not to disclose timing difference between CEK decryption error and others.
            #  Mitigating Bleichenbacher Attack on PKCS#1 v1.5
            #  ref.) http://inaz2.hatenablog.com/entry/2016/01/26/222303
            $this->content_encryption_key = $fake_content_encryption_key;
        }
    }

    private function deriveEncryptionAndMacKeys() {
        switch ($this->header['enc']) {
            case 'A128GCM':
            case 'A256GCM':
                $this->encryption_key = $this->content_encryption_key;
                $this->mac_key = "won't be used";
                break;
            case 'A128CBC-HS256':
                $this->deriveEncryptionAndMacKeysCBC(256);
                break;
            case 'A256CBC-HS512':
                $this->deriveEncryptionAndMacKeysCBC(512);
                break;
            default:
                throw new JOSE_Exception_UnexpectedAlgorithm('Unknown algorithm');
        }
        if (!$this->encryption_key || !$this->mac_key) {
            throw new JOSE_Exception_DecryptionFailed('Encryption/Mac key derivation failed');
        }
    }

    private function deriveEncryptionAndMacKeysCBC($sha_size) {
        $this->mac_key = substr($this->content_encryption_key, 0, $sha_size / 2 / 8);
        $this->encryption_key = substr($this->content_encryption_key, $sha_size / 2 / 8);
    }

    private function encryptCipherText() {
        $cipher = $this->cipher();
        $cipher->setKey($this->encryption_key);
        $cipher->setIV($this->iv);
        $this->cipher_text = $cipher->encrypt($this->plain_text);
        if (!$this->cipher_text) {
            throw new JOSE_Exception_DecryptionFailed('Payload encryption failed');
        }
    }

    private function decryptCipherText() {
        $cipher = $this->cipher();
        $cipher->setKey($this->encryption_key);
        $cipher->setIV($this->iv);
        $this->plain_text = $cipher->decrypt($this->cipher_text);
        if (!$this->plain_text) {
            throw new JOSE_Exception_DecryptionFailed('Payload decryption failed');
        }
    }

    private function generateAuthenticationTag() {
        $this->authentication_tag = $this->calculateAuthenticationTag();
    }

    private function calculateAuthenticationTag($use_raw = false) {
        switch ($this->header['enc']) {
            case 'A128GCM':
            case 'A256GCM':
                throw new JOSE_Exception_UnexpectedAlgorithm('Algorithm not supported');
            case 'A128CBC-HS256':
                return $this->calculateAuthenticationTagCBC(256);
            case 'A256CBC-HS512':
                return $this->calculateAuthenticationTagCBC(512);
            default:
                throw new JOSE_Exception_UnexpectedAlgorithm('Unknown algorithm');
        }
    }

    private function calculateAuthenticationTagCBC($sha_size) {
        if (!$this->auth_data) {
            $this->auth_data = $this->compact((object) $this->header);
        }
        $auth_data_length = strlen($this->auth_data);
        $max_32bit = 2147483647;
        $secured_input = implode('', array(
            $this->auth_data,
            $this->iv,
            $this->cipher_text,
            // NOTE: PHP doesn't support 64bit big endian, so handling upper & lower 32bit.
            pack('N2', ($auth_data_length / $max_32bit) * 8, ($auth_data_length % $max_32bit) * 8)
        ));
        return substr(
            hash_hmac('sha' . $sha_size, $secured_input, $this->mac_key, true),
            0, $sha_size / 2 / 8
        );
    }

    private function checkAuthenticationTag() {
        if (hash_equals($this->authentication_tag, $this->calculateAuthenticationTag())) {
            return true;
        } else {
            throw new JOSE_Exception_UnexpectedAlgorithm('Invalid authentication tag');
        }
    }
}

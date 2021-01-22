# JOSE

PHP JOSE (Javascript Object Signing and Encryption) Implementation

[![Build Status](https://travis-ci.org/nov/jose-php.png?branch=master)](https://travis-ci.org/nov/jose-php)

## Requirements

phpseclib is required.
http://phpseclib.sourceforge.net

## Example

### JWT

#### Encoding

```php
$jwt = new JOSE_JWT(array(
    'foo' => 'bar'
));
$jwt->toString();
```

#### Decoding

```php
$jwt_string = 'eyJ...';
$jwt = JOSE_JWT::decode($jwt_string);
```

### JWS

#### Signing

```php
$private_key = "-----BEGIN RSA PRIVATE KEY-----\n....";
$jwt = new JOSE_JWT(array(
    'foo' => 'bar'
));
$jws = $jwt->sign($private_key, 'RS256');
```

NOTE: `$private_key` can be `phpseclib\Crypt\RSA` instance.

#### Verification

```php
$public_key = "-----BEGIN RSA PUBLIC KEY-----\n....";
$jwt_string = 'eyJ...';
$jws = JOSE_JWT::decode($jwt_string);
$jws->verify($public_key, 'RS256');
```

NOTE: `$public_key` can be `JOSE_JWK` or `phpseclib\Crypt\RSA` instance.

### JWE

#### Encryption

```php
$jwe = new JOSE_JWE($plain_text);
$jwe->encrypt(file_get_contents('/path/to/public_key.pem'));
$jwe->toString();
```

#### Decryption

```php
$jwt_string = 'eyJ...';
$jwe = JOSE_JWT::decode($jwt_string);
$jwe->decrypt($private_key);
```

### JWK

#### Encode

##### RSA Public Key

```php
$public_key = new phpseclib\Crypt\RSA();
$public_key->loadKey('-----BEGIN RSA PUBLIC KEY-----\n...');
JOSE_JWK::encode($public_key); # => JOSE_JWK instance
```

##### RSA Private Key

```php
$private_key = new phpseclib\Crypt\RSA();
$private_key->setPassword($pass_phrase); # skip if not encrypted
$private_key->loadKey('-----BEGIN RSA PRIVATE KEY-----\n...');
JOSE_JWK::encode($private_key); # => JOSE_JWK instance
```

#### Decode

##### RSA Public Key

```php
# public key
$components = array(
    'kty' => 'RSA',
    'e' => 'AQAB',
    'n' => 'x9vNhcvSrxjsegZAAo4OEuo...'
);
JOSE_JWK::decode($components); # => phpseclib\Crypt\RSA instance
```

##### RSA Private Key

Not supported.

## Run Test

```bash
git clone git://github.com/nov/jose-php.git
cd jose
php composer.phar install --dev
./vendor/bin/phpunit -c test/phpunit.xml --tap
```

## Copyright

Copyright &copy; 2013 Nov Matake & GREE Inc. See LICENSE for details.

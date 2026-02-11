# JSON Canonicalization for PHP 8.1+ 

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mmccook/php-json-canonicalization-scheme.svg?style=flat-square)](https://packagist.org/packages/mmccook/php-json-canonicalization-scheme)
[![Tests](https://img.shields.io/github/actions/workflow/status/mmccook/php-json-canonicalization-scheme/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mmccook/php-json-canonicalization-scheme/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/mmccook/php-json-canonicalization-scheme.svg?style=flat-square)](https://packagist.org/packages/mmccook/php-json-canonicalization-scheme)

Needed a way to canonicalize JSON to validate webhooks from [The Campaign Registry](https://csp-api.campaignregistry.com/v2/restAPI) 
couldn't find an actively maintained package that all passed the JCS tests, so I used the one listed on the JCS Github and updated/refactored it. 

## Installation

You can install the package via composer:

```bash
composer require mmccook/php-json-canonicalization-scheme
```

## Usage

```php
$canonicalization = JsonCanonicalizatorFactory::getInstance();
$canonicalizedJsonString = $canonicalization->canonicalize($input);
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Credits
- [Mark M. McCook](https://github.com/mmccook)

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

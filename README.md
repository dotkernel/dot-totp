# dot-totp

Dotkernel's TOTP authentication.

> dot-totp implements RFC 6238 one-time passwords and renders enrolment QR codes with [endroid/qr-code](https://github.com/endroid/qr-code)

## Documentation

Documentation is available at: https://docs.dotkernel.org/dot-totp/.

## Version History

| Branch | Release   | PSR-11   | QR Code            | OSS Lifecycle                                                                                                                             | PHP Version                                                                                              |
|--------|-----------|----------|--------------------|-------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------|
| 1.0    | `>=1.0.0` | 1 \|\| 2 | `endroid/qr-code`  | ![OSS Lifecycle](https://img.shields.io/osslifecycle?file_url=https%3A%2F%2Fgithub.com%2Fdotkernel%2Fdot-totp%2Fblob%2F1.0%2FOSSMETADATA) | ![PHP from Packagist (specify version)](https://img.shields.io/packagist/php-v/dotkernel/dot-totp/1.0.0) |

## Badges

![OSS Lifecycle](https://img.shields.io/osslifecycle/dotkernel/dot-totp)
![PHP from Packagist (specify version)](https://img.shields.io/packagist/dependency-v/dotkernel/dot-totp/php)

[![GitHub issues](https://img.shields.io/github/issues/dotkernel/dot-totp)](https://github.com/dotkernel/dot-totp/issues)
[![GitHub forks](https://img.shields.io/github/forks/dotkernel/dot-totp)](https://github.com/dotkernel/dot-totp/network)
[![GitHub stars](https://img.shields.io/github/stars/dotkernel/dot-totp)](https://github.com/dotkernel/dot-totp/stargazers)
[![GitHub license](https://img.shields.io/github/license/dotkernel/dot-totp)](https://github.com/dotkernel/dot-totp/blob/1.0/LICENSE.md)

[![Build Static](https://github.com/dotkernel/dot-totp/actions/workflows/continuous-integration.yml/badge.svg?branch=1.0)](https://github.com/dotkernel/dot-totp/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/dotkernel/dot-totp/graph/badge.svg?token=R5PopWHvRu)](https://codecov.io/gh/dotkernel/dot-totp)
[![PHPStan](https://github.com/dotkernel/dot-totp/actions/workflows/static-analysis.yml/badge.svg?branch=1.0)](https://github.com/dotkernel/dot-totp/actions/workflows/static-analysis.yml)

## Installation

Install `dotkernel/dot-totp` by executing the following Composer command:

```shell
composer require dotkernel/dot-totp
```

Then register `Dot\Totp\ConfigProvider::class` in your configuration aggregator (to `config/config.php` for example) and add the options described in the [documentation](https://docs.dotkernel.org/dot-totp/v1/configuration/).

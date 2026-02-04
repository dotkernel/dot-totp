# dot-totp

Dotkernel's TOTP authentication.

![OSS Lifecycle](https://img.shields.io/osslifecycle/dotkernel/dot-totp)
![PHP from Packagist (specify version)](https://img.shields.io/packagist/php-v/dotkernel/dot-totp/1.0.0)

[![GitHub issues](https://img.shields.io/github/issues/dotkernel/dot-totp)](https://github.com/dotkernel/dot-totp/issues)
[![GitHub forks](https://img.shields.io/github/forks/dotkernel/dot-totp)](https://github.com/dotkernel/dot-totp/network)
[![GitHub stars](https://img.shields.io/github/stars/dotkernel/dot-totp)](https://github.com/dotkernel/dot-totp/stargazers)
[![GitHub license](https://img.shields.io/github/license/dotkernel/dot-totp)](https://github.com/dotkernel/dot-totp/blob/1.0/LICENSE.md)

[![Build Static](https://github.com/dotkernel/dot-totp/actions/workflows/continuous-integration.yml/badge.svg?branch=1.0)](https://github.com/dotkernel/dot-totp/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/dotkernel/dot-totp/graph/badge.svg?token=DayAoD2Oj6)](https://codecov.io/gh/dotkernel/dot-totp)

## Install

You can install dot-totp by running the following command:

```shell
composer require dotkernel/dot-totp
```

## Configuration

> **Note:** These instructions are written in the style of Mezzio middleware configuration and assume the use of Doctrine ORM.  
> They can be adapted to any database layer or configuration style. If you are using a different framework or service container, follow the same logical steps while adjusting the syntax and configuration to match your environment.

Create a new file configuration `config/autoload/totp.global.php`.

```php
return [
    'dot_totp' => [
        'options'              => [
            // Time step in seconds
            'period'    => 30,
            // Number of digits in the TOTP code
            'digits'    => 6,
            // Hashing algorithm used to generate the code
            'algorithm' => 'sha1',
        ],
    ],
];
```

The values listed above are provided as defaults and may be adjusted based on your needs.

## Usage

### Enabling and Disabling TOTP

To enable or disable the TOTP feature, you need to add two properties to your entity: one to store the TOTP secret and another to track whether TOTP is enabled.

You can reuse the following trait in your entity:

```php
trait TotpTrait
{
    #[ORM\Column(name: 'totp_secret', type: 'string', length: 32, nullable: true)]
    protected ?string $totpSecret = null;

    #[ORM\Column(name: 'totp_enabled', type: 'boolean', options: ['default' => false])]
    protected bool $totpEnabled = false;

    public function enableTotp(string $secret): self
    {
        $this->totpSecret  = $secret;
        $this->totpEnabled = true;

        return $this;
    }

    public function disableTotp(): self
    {
        $this->totpSecret  = null;
        $this->totpEnabled = false;

        return $this;
    }

    public function isTotpEnabled(): bool
    {
        return $this->totpEnabled;
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }
}
```

### Enable TOTP

To enable TOTP, generate a temporary secret and encode it into a QR code, which the user scans with an authenticator app (e.g., Google Authenticator, Authy). The user then confirms by providing a one-time code from the app.

**Steps:**

1. Generate a temporary Base32 secret.
2. Build a provisioning URI with a label and issuer.
3. Render the URI as a QR code.
4. Validate the code from the authenticator app.

Once validated, update the relevant properties to enable or disable TOTP.

# App

The `app` method. The user scans a QR code once, and from then on their authenticator app derives every code offline - the code never travels, which makes this the strongest of the three methods.

--8<-- "totp-service.md"

| Method | Purpose |
| --- | --- |
| `generateSecretBase32(int $length = 16): string` | A new random Base32 secret. |
| `getProvisioningUri(string $label, string $issuer, string $secret): string` | The `otpauth://totp/...` URI an authenticator app scans. |
| `generateInlineSvgQr(string $data, int $size = 180): string` | An inline SVG QR code for that URI. |
| `verifyCode(string $secret, string $code, int $window = 1): bool` | Whether a submission matches, checking `$window` steps either side of now. |
| `generateRecoveryCodes(int $count = 8, int $length = 10, string $separator = '-'): array` | Single-use fallback codes in plain text. |
| `hashRecoveryCodes(array $codes): array` | The same codes hashed with `password_hash()`, for storage. |
| `validateRecoveryCode(string $inputCode, array &$hashedCodes): bool` | Consumes a matching code, **modifying the array by reference**. |
| `getPeriod(): int` / `getDigits(): int` / `getAlgorithm(): string` | The configured options. |

`getCode()` is not listed: the user's device derives the code, so the server only ever verifies one.

## Entity trait

Add the following trait to the entity that authenticates. It carries only what the `app` method needs:

```php
<?php

declare(strict_types=1);

namespace YourApp\Entity;

use Doctrine\ORM\Mapping as ORM;

trait TotpTrait
{
    public const string TOTP_METHOD_APP   = 'app';
    public const string TOTP_METHOD_EMAIL = 'email';
    public const string TOTP_METHOD_SMS   = 'sms';

    #[ORM\Column(name: 'totp_secret', type: 'string', length: 32, nullable: true)]
    protected ?string $totpSecret = null;

    #[ORM\Column(name: 'totp_enabled', type: 'boolean', options: ['default' => false])]
    protected bool $totpEnabled = false;

    #[ORM\Column(name: 'totp_method', type: 'string', length: 16, options: ['default' => 'app'])]
    protected string $totpMethod = self::TOTP_METHOD_APP;

    /** @var array<int, string>|null */
    #[ORM\Column(name: 'totp_recovery_codes', type: 'json', nullable: true)]
    protected ?array $totpRecoveryCodes = null;

    public function enableTotp(string $secret, string $method = self::TOTP_METHOD_APP): self
    {
        $this->totpSecret  = $secret;
        $this->totpEnabled = true;
        $this->totpMethod  = $method;

        return $this;
    }

    public function disableTotp(): self
    {
        $this->totpSecret        = null;
        $this->totpEnabled       = false;
        $this->totpMethod        = self::TOTP_METHOD_APP;
        $this->totpRecoveryCodes = null;

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

    public function getTotpMethod(): string
    {
        return $this->totpMethod;
    }

    public function usesTotpDelivery(): bool
    {
        return $this->totpMethod !== self::TOTP_METHOD_APP;
    }

    /**
     * @return array<int, string>
     */
    public function getTotpRecoveryCodes(): array
    {
        return $this->totpRecoveryCodes ?? [];
    }

    /**
     * @param array<int, string> $hashedCodes
     */
    public function setTotpRecoveryCodes(array $hashedCodes): self
    {
        $this->totpRecoveryCodes = $hashedCodes;

        return $this;
    }
}
```

| Column | Purpose |
| --- | --- |
| `totp_secret` | The shared Base32 secret. |
| `totp_enabled` | Whether the second factor is on. |
| `totp_method` | `app`, `email` or `sms`, so the application knows how to deliver a code. |
| `totp_recovery_codes` | Hashes of the codes not yet used. |

With no recovery codes, `totp_secret` and `totp_enabled` are the only columns you need.

This trait is a strict subset of the one on [Email](email.md) and [SMS](sms.md). Adding a delivery method later means adding members to it, never changing the ones above.

--8<-- "totp-entity-mapping.md"

> The method constants live on the trait, and PHP requires reading them through the class that uses it - `User::TOTP_METHOD_APP`, not `TotpTrait::TOTP_METHOD_APP`.

## Enrolling a user

An authenticator app enrols by scanning a QR code:

```php
$secret = $totp->generateSecretBase32();
$uri    = $totp->getProvisioningUri($user->getIdentity(), 'Your App', $secret);
$svg    = $totp->generateInlineSvgQr($uri);
```

`generateInlineSvgQr()` returns SVG markup, so the template embeds it directly - there is no image file to write or serve.

Hold `$secret` in the session, not in the database, until the user has typed a code from their app.
Otherwise a QR code that was never scanned successfully leaves the user with a factor they cannot satisfy.

```php
if (! $totp->verifyCode($secret, $submittedCode)) {
    return $this->error('That code is not correct. Check the time on your phone and try again.');
}

$user->enableTotp($secret);
$entityManager->flush();
```

`enableTotp()` defaults to `TOTP_METHOD_APP`, so no method argument is needed here.

## Verifying a code

At sign-in, a code from an authenticator app is verified with the default window, which accepts the previous, current and next time step - about ninety seconds with a 30-second `period`, enough to absorb a phone's clock drift:

```php
if (! $totp->verifyCode((string) $user->getTotpSecret(), $submittedCode)) {
    return $this->error('That code is not correct.');
}
```

There is nothing to send first, so an app user is asked for a code straight away. `usesTotpDelivery()` returns `false` for this method, which is how a sign-in flow that also offers a delivery method tells the two apart.

Nothing else is required: the secret stays put, and there is no attempt counter or resend cooldown to maintain. Those exist only for delivered codes, and [Methods](../methods.md) explains why.

## Recovery codes

If a user loses the device holding the secret, a recovery code is the only way back in.

--8<-- "totp-recovery-codes.md"

## Other methods

[Email](email.md) and [SMS](sms.md) cover the two delivery methods. [Methods](../methods.md) compares all three.

# Usage

The container gives you a single service, configured from `dot_totp.options`:

```php
use Dot\Totp\Totp;

$totp = $container->get(Totp::class);
```

| Method | Purpose |
| --- | --- |
| `generateSecretBase32(int $length = 16): string` | A new random Base32 secret. |
| `getCode(string $secret, ?int $timestamp = null): string` | The code for a secret at a moment in time; defaults to now. |
| `verifyCode(string $secret, string $code, int $window = 1): bool` | Whether a submission matches, checking `$window` steps either side of now. |
| `getProvisioningUri(string $label, string $issuer, string $secret): string` | The `otpauth://totp/...` URI an authenticator app scans. |
| `generateInlineSvgQr(string $data, int $size = 180): string` | An inline SVG QR code for that URI. |
| `generateRecoveryCodes(int $count = 8, int $length = 10, string $separator = '-'): array` | Single-use fallback codes in plain text. |
| `hashRecoveryCodes(array $codes): array` | The same codes hashed with `password_hash()`, for storage. |
| `validateRecoveryCode(string $inputCode, array &$hashedCodes): bool` | Consumes a matching code, **modifying the array by reference**. |
| `getPeriod(): int` / `getDigits(): int` / `getAlgorithm(): string` | The configured options. |

## Entity trait

Add the following trait to the entity that authenticates:

```php
<?php

declare(strict_types=1);

namespace YourApp\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

use function max;

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

    #[ORM\Column(name: 'totp_code_sent_at', type: 'datetime_immutable', nullable: true)]
    protected ?DateTimeImmutable $totpCodeSentAt = null;

    #[ORM\Column(name: 'totp_attempts', type: 'smallint', options: ['default' => 0])]
    protected int $totpAttempts = 0;

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
        $this->totpCodeSentAt    = null;
        $this->totpAttempts      = 0;
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

    public function rotateTotpSecret(string $secret): self
    {
        $this->totpSecret = $secret;

        return $this;
    }

    public function getTotpMethod(): string
    {
        return $this->totpMethod;
    }

    public function usesTotpDelivery(): bool
    {
        return $this->totpMethod !== self::TOTP_METHOD_APP;
    }

    public function markTotpCodeSent(): self
    {
        $this->totpCodeSentAt = new DateTimeImmutable();
        $this->totpAttempts   = 0;

        return $this;
    }

    public function getTotpResendRetryAfter(int $resendInterval): int
    {
        if ($this->totpCodeSentAt === null) {
            return 0;
        }

        $elapsed = (new DateTimeImmutable())->getTimestamp() - $this->totpCodeSentAt->getTimestamp();

        return max(0, $resendInterval - $elapsed);
    }

    public function canResendTotpCode(int $resendInterval): bool
    {
        return $this->getTotpResendRetryAfter($resendInterval) === 0;
    }

    public function registerFailedTotpAttempt(): self
    {
        $this->totpAttempts++;

        return $this;
    }

    public function hasTotpAttemptsLeft(int $maxAttempts): bool
    {
        return $this->totpAttempts < $maxAttempts;
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

| Column | Needed for | Purpose |
| --- | --- | --- |
| `totp_secret` | all methods | The shared Base32 secret. |
| `totp_enabled` | all methods | Whether the second factor is on. |
| `totp_method` | all methods | `app`, `email` or `sms`, so the application knows how to deliver a code. |
| `totp_code_sent_at` | email, SMS | When the last code went out, driving the resend cooldown. |
| `totp_attempts` | email, SMS | Wrong guesses made against the code in flight. |
| `totp_recovery_codes` | recovery codes | Hashes of the codes not yet used. |

For an authenticator app with no recovery codes, `totp_secret` and `totp_enabled` are the only columns you need.

```php
#[ORM\Entity]
class User
{
    use TotpTrait;
}
```

`totp_method` and `totp_attempts` carry defaults so the `ALTER TABLE` succeeds on a populated table.
Then generate a migration:

```shell
php bin/doctrine migrations:diff
```

> The method constants live on the trait, and PHP requires reading them through the class that uses it - `User::TOTP_METHOD_EMAIL`, not `TotpTrait::TOTP_METHOD_EMAIL`.

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

Email and SMS have nothing to scan, so enrolment is one call with a secret the user never sees:

```php
$user->enableTotp($totp->generateSecretBase32(), User::TOTP_METHOD_EMAIL);
$entityManager->flush();
```

## Sending a code by email or SMS

Check the cooldown, derive the code with `getCode()`, stamp the send, then deliver the plain code:

```php
$resendInterval = 60;

if (! $user->canResendTotpCode($resendInterval)) {
    // 429, with Retry-After: $user->getTotpResendRetryAfter($resendInterval)
    return $this->tooManyRequests($user->getTotpResendRetryAfter($resendInterval));
}

$code = $totp->getCode((string) $user->getTotpSecret());

$user->markTotpCodeSent();
$entityManager->flush();

if ($user->getTotpMethod() === User::TOTP_METHOD_SMS) {
    $this->smsGateway->send($user->getPhone(), sprintf('Your code is %s', $code));
} else {
    $this->mailer->send($user->getEmail(), sprintf('Your code is %s', $code));
}
```

`markTotpCodeSent()` also resets the attempt counter, so guesses spent on the previous code do not count against the new one.

Email delivery pairs with [dotkernel/dot-mail](https://docs.dotkernel.org/dot-mail/).
There is no Dotkernel SMS component, so `$this->smsGateway` is yours to provide.

At sign-in, `usesTotpDelivery()` tells the two flows apart: an app user is asked for a code straight away, a delivery user needs one sent first.

```php
if ($user->isTotpEnabled() && $user->usesTotpDelivery()) {
    // send a code, then ask for it
}
```

## Verifying a code

A code from an authenticator app is verified with the default window, which accepts the previous, current and next time step - about ninety seconds with a 30-second `period`, enough to absorb a phone's clock drift:

```php
if (! $totp->verifyCode((string) $user->getTotpSecret(), $submittedCode)) {
    return $this->error('That code is not correct.');
}
```

A delivered code has to survive a mail queue or an SMS carrier, so it needs a wider window, an attempt limit, and a rotation once accepted:

```php
$maxAttempts = 3;

if (! $user->hasTotpAttemptsLeft($maxAttempts)) {
    return $this->error('Too many attempts. Request a new code.');
}

// 30s period, 10 steps either side: roughly five minutes of grace
if (! $totp->verifyCode((string) $user->getTotpSecret(), $submittedCode, window: 10)) {
    $user->registerFailedTotpAttempt();
    $entityManager->flush();

    return $this->error('That code is not correct.');
}

// Accepted: rotate the secret so this code cannot be used again.
$user->rotateTotpSecret($totp->generateSecretBase32());
$entityManager->flush();
```

Pick the window from how long you are prepared to let a code live, divided by `period` - not from how long delivery usually takes.
A number that only just covers a fast send will reject legitimate users the first time your mail queue backs up.

> Why the wider window needs the other two lines is explained in [Methods](methods.md).

## Recovery codes

Generate the codes once, show them once, and store only their hashes:

```php
$codes = $totp->generateRecoveryCodes();
// ['A2C4E-6GH8J', 'K3M5P-7QR9S', ...]

$user->setTotpRecoveryCodes($totp->hashRecoveryCodes($codes));
$entityManager->flush();

// $codes is the only chance the user has to write them down.
```

`validateRecoveryCode()` takes the hashes **by reference** and removes the one it consumes, so the mutated array has to be persisted or the code stays usable:

```php
$hashedCodes = $user->getTotpRecoveryCodes();

if (! $totp->validateRecoveryCode($submittedCode, $hashedCodes)) {
    return $this->error('That recovery code is not valid.');
}

$user->setTotpRecoveryCodes($hashedCodes);
$entityManager->flush();
```

## Delivery method usage

[Methods](methods.md)

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

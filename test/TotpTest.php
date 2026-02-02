<?php

declare(strict_types=1);

namespace Dot\test;

use Dot\Totp\Totp;
use PHPUnit\Framework\TestCase;
use Random\RandomException;

class TotpTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $totp = new Totp();

        $this->assertSame(30, $totp->getPeriod());
        $this->assertSame(6, $totp->getDigits());
        $this->assertSame('sha1', $totp->getAlgorithm());
    }

    public function testConstructorCustomValues(): void
    {
        $totp = new Totp(60, 8, 'test');

        $this->assertSame(60, $totp->getPeriod());
        $this->assertSame(8, $totp->getDigits());
        $this->assertSame('test', $totp->getAlgorithm());
    }

    /**
     * @throws RandomException
     */
    public function testGenerateSecretBase32(): void
    {
        $totp   = new Totp();
        $secret = $totp->generateSecretBase32(16);

        $this->assertMatchesRegularExpression('/^[A-Z2-7]{16}$/', $secret);
    }

    public function testGetCodeAndVerifyCode(): void
    {
        $totp   = new Totp();
        $secret = 'JBSWY3DPEHPK3PXP';
        $code   = $totp->getCode($secret);

        $this->assertTrue($totp->verifyCode($secret, $code));
        $this->assertFalse($totp->verifyCode($secret, '000000'));
    }

    public function testGetProvisioningUri(): void
    {
        $totp = new Totp(30, 6, 'sha1');
        $uri  = $totp->getProvisioningUri('test@example.com', 'Example', 'JBSWY3DPEHPK3PXP');

        $this->assertStringContainsString('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $uri);
        $this->assertStringContainsString('issuer=Example', $uri);
        $this->assertStringContainsString('period=30', $uri);
        $this->assertStringContainsString('digits=6', $uri);
        $this->assertStringContainsString('algorithm=SHA1', $uri);
    }

    public function testGenerateInlineSvgQr(): void
    {
        $totp = new Totp();
        $svg  = $totp->generateInlineSvgQr('test-data', 100);

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('xmlns="http://www.w3.org/2000/svg"', $svg);
        $this->assertStringContainsString('width="100px"', $svg);
        $this->assertStringContainsString('height="100px"', $svg);
    }
}

<?php

declare(strict_types=1);

namespace DotTest\Totp;

use Dot\Totp\Totp;
use PHPUnit\Framework\TestCase;
use Random\RandomException;

use function explode;
use function password_verify;
use function strlen;

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

    /**
     * @throws RandomException
     */
    public function testGenerateRecoveryCodes(): void
    {
        $totp = new Totp();

        $count     = 5;
        $length    = 8;
        $separator = '-';

        $codes = $totp->generateRecoveryCodes($count, $length, $separator);

        $this->assertCount($count, $codes);

        foreach ($codes as $code) {
            $parts = explode($separator, $code);
            $this->assertCount(2, $parts);
            $this->assertSame((int) ($length / 2), strlen($parts[0]));
            $this->assertSame((int) ($length / 2), strlen($parts[1]));
        }
    }

    public function testHashRecoveryCodes(): void
    {
        $totp   = new Totp();
        $codes  = ['TEST-RECO', 'RECO-TEST'];
        $hashed = $totp->hashRecoveryCodes($codes);

        foreach ($hashed as $i => $hash) {
            $this->assertNotSame($codes[$i], $hash);
            $this->assertTrue(password_verify($codes[$i], $hash));
        }
    }

    public function testValidateRecoveryCode(): void
    {
        $totp = new Totp();

        $codes  = ['TEST-RECO', 'RECO-TEST'];
        $hashed = $totp->hashRecoveryCodes($codes);

        $input  = 'TEST-RECO';
        $result = $totp->validateRecoveryCode($input, $hashed);

        $this->assertTrue($result);
        $this->assertCount(1, $hashed);
        $this->assertFalse(password_verify($input, $hashed[0]));

        $invalid       = 'TEST-TEST';
        $resultInvalid = $totp->validateRecoveryCode($invalid, $hashed);

        $this->assertFalse($resultInvalid);
        $this->assertCount(1, $hashed);
    }
}

<?php

declare(strict_types=1);

namespace Dot\Totp;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Random\RandomException;

use function array_map;
use function array_values;
use function chr;
use function hash_equals;
use function hash_hmac;
use function intdiv;
use function ord;
use function pack;
use function password_hash;
use function password_verify;
use function preg_replace;
use function random_int;
use function rawurlencode;
use function sprintf;
use function str_pad;
use function strlen;
use function strpos;
use function strtoupper;
use function substr;
use function time;
use function trim;
use function unpack;

use const PASSWORD_DEFAULT;
use const STR_PAD_LEFT;

class Totp
{
    public const string BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function __construct(
        protected int $period = 30,
        protected int $digits = 6,
        protected string $algorithm = 'sha1'
    ) {
    }

    /**
     * @throws RandomException
     */
    public function generateSecretBase32(int $length = 16): string
    {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_CHARS[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Generate a TOTP code for a given secret and timestamp
     */
    public function getCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $counter   = intdiv($timestamp, $this->period);
        $secretKey = $this->base32Decode($secret);

        $binaryCounter = pack('N*', 0) . pack('N*', $counter);
        $hash          = hash_hmac($this->algorithm, $binaryCounter, $secretKey, true);
        $offset        = ord(substr($hash, -1)) & 0x0F;
        $truncatedHash = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        $code = $truncatedHash % (10 ** $this->digits);

        return str_pad((string) $code, $this->digits, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a TOTP code with timing window tolerance
     */
    public function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        $now  = time();
        $code = trim($code);

        // Check current time period plus/minus window
        for ($i = -$window; $i <= $window; $i++) {
            $timestamp = $now + ($i * $this->period);
            if (hash_equals($this->getCode($secret, $timestamp), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate the otpauth:// URI for QR code scanning
     */
    public function getProvisioningUri(
        string $label,
        string $issuer,
        string $secret
    ): string {
        $label         = rawurlencode($label);
        $issuerEncoded = rawurlencode($issuer);

        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&period=%d&digits=%d&algorithm=%s',
            $label,
            $secret,
            $issuerEncoded,
            $this->period,
            $this->digits,
            strtoupper($this->algorithm)
        );
    }

    /**
     * Generate an inline SVG QR code
     */
    public function generateInlineSvgQr(string $data, int $size = 180): string
    {
        $writer = new SvgWriter();
        $qrCode = new QrCode(
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 0
        );

        $result = $writer->write($qrCode);
        return $result->getString();
    }

    /**
     * Decode a base32-encoded string to binary
     */
    private function base32Decode(string $secret): string
    {
        $secret = strtoupper($secret);
        $secret = preg_replace('/[^A-Z2-7]/', '', $secret) ?? '';

        $bits     = '';
        $value    = 0;
        $bitCount = 0;

        for ($i = 0; $i < strlen($secret); $i++) {
            $char  = $secret[$i];
            $index = strpos(self::BASE32_CHARS, $char);

            if ($index !== false) {
                $value     = ($value << 5) | $index;
                $bitCount += 5;

                if ($bitCount >= 8) {
                    $bitCount -= 8;
                    $bits     .= chr(($value >> $bitCount) & 0xFF);
                }
            }
        }

        return $bits;
    }

    /**
     * @throws RandomException
     */
    public function generateRecoveryCodes(int $count = 8, int $length = 10, string $separator = '-'): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $code = '';
            for ($j = 0; $j < $length; $j++) {
                $code .= self::BASE32_CHARS[random_int(0, strlen(self::BASE32_CHARS) - 1)];
            }

            $half    = intdiv(strlen($code), 2);
            $code    = substr($code, 0, $half) . $separator . substr($code, $half);
            $codes[] = $code;
        }

        return $codes;
    }

    public function hashRecoveryCodes(array $codes): array
    {
        return array_map(fn($code) => password_hash($code, PASSWORD_DEFAULT), $codes);
    }

    public function validateRecoveryCode(string $inputCode, array &$hashedCodes): bool
    {
        foreach ($hashedCodes as $index => $hash) {
            if (password_verify($inputCode, $hash)) {
                unset($hashedCodes[$index]);
                $hashedCodes = array_values($hashedCodes);
                return true;
            }
        }
        return false;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function getDigits(): int
    {
        return $this->digits;
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }
}

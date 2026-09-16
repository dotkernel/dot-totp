| Method | Purpose |
| --- | --- |
| `generateSecretBase32(int $length = 16): string` | A new random Base32 secret. |
| `getCode(string $secret, ?int $timestamp = null): string` | The code for a secret at a moment in time; defaults to now. This is what lets the server derive the code it sends. |
| `verifyCode(string $secret, string $code, int $window = 1): bool` | Whether a submission matches, checking `$window` steps either side of now. |
| `generateRecoveryCodes(int $count = 8, int $length = 10, string $separator = '-'): array` | Single-use fallback codes in plain text. |
| `hashRecoveryCodes(array $codes): array` | The same codes hashed with `password_hash()`, for storage. |
| `validateRecoveryCode(string $inputCode, array &$hashedCodes): bool` | Consumes a matching code, **modifying the array by reference**. |
| `getPeriod(): int` / `getDigits(): int` / `getAlgorithm(): string` | The configured options. |

`getProvisioningUri()` and `generateInlineSvgQr()` are not listed: a delivered code gives the user nothing to scan.

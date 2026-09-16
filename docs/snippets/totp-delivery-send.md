```php
$resendInterval = 60;

if (! $user->canResendTotpCode($resendInterval)) {
    // 429, with Retry-After: $user->getTotpResendRetryAfter($resendInterval)
    return $this->tooManyRequests($user->getTotpResendRetryAfter($resendInterval));
}

$code = $totp->getCode((string) $user->getTotpSecret());

$user->markTotpCodeSent();
$entityManager->flush();
```

`markTotpCodeSent()` also resets the attempt counter, so guesses spent on the previous code do not count against the new one.

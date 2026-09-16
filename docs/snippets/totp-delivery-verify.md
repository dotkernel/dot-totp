A delivered code has to survive a mail queue or an SMS carrier, so it needs a wider window, an attempt limit, and a rotation once accepted:

```php
$maxAttempts = 3;

if (! $user->hasTotpAttemptsLeft($maxAttempts)) {
    return $this->error('Too many attempts. Request a new code.');
}

// 30s period, window 5: 11 steps of 30s, so the code lives about five and a half minutes
if (! $totp->verifyCode((string) $user->getTotpSecret(), $submittedCode, window: 5)) {
    $user->registerFailedTotpAttempt();
    $entityManager->flush();

    return $this->error('That code is not correct.');
}

// Accepted: rotate the secret so this code cannot be used again.
$user->rotateTotpSecret($totp->generateSecretBase32());
$entityManager->flush();
```

`verifyCode()` accepts `$window` steps either side of now, so a code stays valid for `(2 * $window + 1) * period` seconds - half of that before the moment it was derived, half after.
Pick `$window` from how long you are prepared to let a code live, `(lifetime / period - 1) / 2`, not from how long delivery usually takes.
A number that only just covers a fast send will reject legitimate users the first time delivery backs up.

> Why the wider window needs the other two lines is explained in [Methods](../methods.md).

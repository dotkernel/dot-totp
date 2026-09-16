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

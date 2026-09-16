# Email

The `email` method. The server derives the code and sends it by email, so the secret lives on the server only and the user has nothing to install.

--8<-- "totp-service.md"

--8<-- "totp-methods-delivery.md"

## Entity trait

Add the following trait to the entity that authenticates:

--8<-- "totp-trait-delivery.md"

--8<-- "totp-columns-delivery.md"

--8<-- "totp-entity-mapping.md"

> The method constants live on the trait, and PHP requires reading them through the class that uses it - `User::TOTP_METHOD_EMAIL`, not `TotpTrait::TOTP_METHOD_EMAIL`.

## Enrolling a user

Email has nothing to scan, so enrolment is one call with a secret the user never sees:

```php
$user->enableTotp($totp->generateSecretBase32(), User::TOTP_METHOD_EMAIL);
$entityManager->flush();
```

Send only to an address the user has already verified.

## Sending a code

Check the cooldown, derive the code with `getCode()` and stamp the send:

--8<-- "totp-delivery-send.md"

Then deliver the plain code:

```php
$this->mailer->send($user->getEmail(), sprintf('Your code is %s', $code));
```

Email delivery pairs with [dotkernel/dot-mail](https://docs.dotkernel.org/dot-mail/).

--8<-- "totp-delivery-signin.md"

## Verifying a code

--8<-- "totp-delivery-verify.md"

## Recovery codes

A user who loses access to the mailbox needs another way in.

--8<-- "totp-recovery-codes.md"

## Other methods

[App](app.md) and [SMS](sms.md) cover the other two methods. [Methods](../methods.md) compares all three.

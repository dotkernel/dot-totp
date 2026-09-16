At sign-in, `usesTotpDelivery()` tells the two flows apart: an app user is asked for a code straight away, a delivery user needs one sent first.

```php
if ($user->isTotpEnabled() && $user->usesTotpDelivery()) {
    // send a code, then ask for it
}
```

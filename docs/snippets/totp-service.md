The container gives you a single service, configured from `dot_totp.options`:

```php
use Dot\Totp\Totp;

$totp = $container->get(Totp::class);
```

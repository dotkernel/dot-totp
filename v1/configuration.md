# Configuration

Register `dot-totp` in your project by adding `Dot\Totp\ConfigProvider::class` to your configuration aggregator (to `config/config.php` for example).
This registers `Dot\Totp\Totp::class`, built by `Dot\Totp\TotpFactory` from the options below.

Then create the file `config/autoload/totp.global.php`:

```php
return [
    'dot_totp' => [
        'options'      => [
            // Time step in seconds
            'period'    => 30,
            // Number of digits in the TOTP code
            'digits'    => 6,
            // Hashing algorithm used to generate the code
            'algorithm' => 'sha1',
        ],
    ],
];
```

The values above are the defaults, and each key is read independently, so a file that sets only `period` keeps the other two.
All three are written into the provisioning URI, so an authenticator app configures itself from them when it scans the QR code.

> Changing any of the three after users have enrolled invalidates every existing enrolment, because the app on the user's device keeps the values it scanned.

The attempt limit and the resend cooldown that a delivered code needs are not `dot_totp` options.
They are passed in where they are used, from your own application configuration.

> The examples throughout this documentation assume Mezzio and Doctrine ORM. Any container and any persistence layer will do - the steps are the same.

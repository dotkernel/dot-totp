```php
#[ORM\Entity]
class User
{
    use TotpTrait;
}
```

The `totp_*` columns carry defaults so the `ALTER TABLE` succeeds on a populated table.
Then generate a migration:

```shell
php bin/doctrine migrations:diff
```

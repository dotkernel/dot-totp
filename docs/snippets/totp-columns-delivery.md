| Column | Purpose |
| --- | --- |
| `totp_secret` | The shared Base32 secret. |
| `totp_enabled` | Whether the second factor is on. |
| `totp_method` | `app`, `email` or `sms`, so the application knows how to deliver a code. |
| `totp_code_sent_at` | When the last code went out, driving the resend cooldown. |
| `totp_attempts` | Wrong guesses made against the code in flight. |
| `totp_recovery_codes` | Hashes of the codes not yet used. |

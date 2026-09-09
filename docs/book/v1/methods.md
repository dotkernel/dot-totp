# Methods

A code is derived from a shared secret and the current time step, so the same code can reach the user three ways:

| Method | How the user gets the code | Secret lives |
|---|---|---|
| `app` | Scans a QR code once, then their device derives every code offline | Server and device |
| `email` | The server derives the code and sends it by email | Server only |
| `sms` | The server derives the code and sends it by SMS | Server only |

Generation and verification are the same two calls in all three cases.
`getCode()` is what lets the server derive the code it expects, so email and SMS need no second implementation.

`app` is the strongest of the three, because the code never travels. Offer the others to users who cannot or will not install an authenticator app.

## Why delivered codes need a wider window

A delivered code has to survive a mail queue or an SMS carrier, so it cannot expire after one time step of `period` seconds.
Widening `verifyCode()`'s `$window` is how its lifetime is extended, and it costs something: every step inside the window is a valid code, and a code stays valid for the whole window rather than being consumed on use.

Three measures pay for that, and none of them is optional.

- **Rotate the secret on success.** A delivery user's secret exists only on the server, so replacing it after an accepted code makes that code single-use and invalidates every other code still inside the window.
- **Cap the attempts.** A six-digit code is about a million combinations, and a wide window means several are live at once. The attempt limit, not the algorithm, is what makes guessing impractical.
- **Cool down the resend.** Without it a caller can spend every attempt, request a fresh code immediately, and repeat at full speed.

> The cooldown is per user. It does not slow an attacker spraying codes at many accounts, so rate limit the routes as well.

## Security notes

- Never log a code, a secret or a provisioning URI - the URI carries the secret in its query string.
- Keep the secret out of the database until an app user has confirmed a code derived from it.
- Send only to an address or phone number the user has already verified.
- Store recovery codes hashed, and only hashed.

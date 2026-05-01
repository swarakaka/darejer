# Authentication — Fortify

Authentication is powered by **Laravel Fortify** (`^1.38`). Fortify owns every POST handler (login, logout, password reset, 2FA challenge, email verification, password confirmation). Darejer wires Fortify's GET views to Inertia pages so host apps never write auth controllers.

## Routes

All Fortify routes are mounted under the same prefix as Darejer (`/darejer` by default). Configure via `darejer.route_prefix`.

| URL | Page |
|---|---|
| `GET /darejer/login` | `Auth/Login.vue` |
| `POST /darejer/login` | Fortify login handler |
| `POST /darejer/logout` | Fortify logout |
| `GET /darejer/forgot-password` | `Auth/ForgotPassword.vue` |
| `POST /darejer/forgot-password` | Fortify password-email |
| `GET /darejer/reset-password/{token}` | `Auth/ResetPassword.vue` |
| `POST /darejer/reset-password` | Fortify password-update |
| `GET /darejer/confirm-password` | `Auth/ConfirmPassword.vue` |
| `GET /darejer/two-factor-challenge` | `Auth/TwoFactorChallenge.vue` |
| `GET /darejer/email/verify` | `Auth/VerifyEmail.vue` |
| `GET /darejer/register` | `Auth/Register.vue` (if registration enabled) |

## Default features

Darejer ships its own `config/fortify.php` enabling:

| Feature | Status |
|---|---|
| `resetPasswords` | ✅ enabled |
| `updateProfileInformation` | ✅ enabled |
| `updatePasswords` | ✅ enabled |
| `twoFactorAuthentication` (with `confirm` + `confirmPassword`) | ✅ enabled |
| `registration` | ❌ disabled |
| `emailVerification` | ❌ disabled |

**To enable registration / email verification**, publish the Fortify config:

```bash
php artisan vendor:publish --tag=darejer-fortify-config
```

Then edit `config/fortify.php`. Your published file overrides the package defaults.

## Fortify actions

`src/Actions/Fortify/` ships default implementations:

- `CreateNewUser`
- `UpdateUserProfileInformation`
- `UpdateUserPassword`
- `ResetUserPassword`
- `PasswordValidationRules` (trait)

Override by binding your own class to the relevant Fortify contract in your host app's `AppServiceProvider` or a dedicated provider:

```php
use Laravel\Fortify\Contracts\CreatesNewUsers;
use App\Actions\Fortify\CreateNewUser;

public function register(): void
{
    $this->app->bind(CreatesNewUsers::class, CreateNewUser::class);
}
```

## Important — do NOT add manual auth controllers

The package does not ship `LoginController`, `ForgotPasswordController`, etc. **Do not re-introduce them.** Fortify owns the auth surface — your host app extends behavior via Fortify actions, not by adding controllers.

## Logging in programmatically (tests, console)

Use Laravel's standard `actingAs` / `Auth::login(...)`. No Darejer-specific helper is needed.

## Related

- [Laravel Fortify docs](https://laravel.com/docs/fortify)
- [`getting-started/configuration.md`](../getting-started/configuration.md) — `darejer.route_prefix` and `home_route`.
- [`permissions.md`](../permissions.md) — what users can do once logged in.

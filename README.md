# albrachtsystems/laravel-auth

Herbruikbare authenticatiemodule voor Laravel: registratie, in-/uitloggen,
e-mailverificatie & -wijziging, wachtwoordherstel, passkeys (WebAuthn), profiel
(naam/avatar/preferences) en de bijbehorende mails. Werkt **cookie/sessie-first**
(Sanctum stateful, HttpOnly + CSRF) met **token** (Bearer) als optie.

De module bevat **geen domeinlogica**. Koppeling met het project loopt via config
(`user_with`, `register_rules`, `profiel_rules`) en het host-`User`-model.

## Vereisten

- PHP `^8.3`, Laravel `^13`
- `laravel/sanctum`, `laravel/passkeys`, `intervention/image-laravel`

## Installatie

Tijdens monorepo-ontwikkeling via een Composer **path repository**:

```jsonc
// composer.json (host)
"repositories": [
    { "type": "path", "url": "../packages/laravel-auth" }
],
"require": {
    "albrachtsystems/laravel-auth": "*"
}
```

Later (eigen repo) via een VCS-repository met een git-tag. De `AuthServiceProvider`
wordt automatisch ontdekt (package discovery).

## Host-`User`-model

```php
use AlbrachtSystems\Auth\Concerns\HasAuthentication;
use Laravel\Passkeys\Contracts\PasskeyUser;

class User extends Authenticatable implements PasskeyUser
{
    use HasAuthentication; // levert name/email/password/avatar/preferences + casts + passkeys

    // Alleen domeinvelden hier:
    protected $fillable = ['school', 'bio', ...];
}
```

## Sessie-modus aanzetten (aanbevolen)

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->statefulApi();                       // Sanctum stateful SPA
    $middleware->validateCsrfTokens(except: ['api/*']); // Sanctum doet CSRF op first-party
})
```

`.env` (productie, subdomeinen):

```dotenv
AUTH_MODE=session
SESSION_DOMAIN=.jouwdomein.nl
SANCTUM_STATEFUL_DOMAINS=jouwdomein.nl,*.jouwdomein.nl
FRONTEND_URL=https://jouwdomein.nl
AUTH_APP_NAME="Jouw App"
```

## Configuratie

Publiceren (optioneel — defaults werken out of the box):

```bash
php artisan vendor:publish --tag=auth-module-config
```

| Key | Default | Toelichting |
|-----|---------|-------------|
| `user_model` | `App\Models\User` | Eloquent user-model |
| `guard` | `web` | Guard voor sessie-login |
| `mode` | `session` | `session` (cookie/CSRF) of `token` (Bearer) |
| `app_name` | `env(AUTH_APP_NAME)` | Branding in mails |
| `frontend_url` | `env(FRONTEND_URL)` | Basis voor verificatie-/resetlinks |
| `route_prefix` | `api` | Prefix voor de auth-routes |
| `route_middleware` | `['api']` | Middleware-groep |
| `token_ttl.email_verification` | `1440` min | Geldigheid verificatielink |
| `token_ttl.email_change` | `60` min | Geldigheid e-mailwijziging |
| `token_ttl.password_reset` | `60` min | Geldigheid resetlink |
| `user_with` | `[]` | **Projectspecifiek**: relaties op auth-responses |
| `user_resource` | `null` | Optionele JsonResource voor de user-payload (anders het model) |
| `features` | zie onder | Route-vlaggen: `registration`, `email_verification`, `email_change`, `avatar`, `preferences`, `passkeys` (default `true`); `magic_link`, `impersonation` (default `false`) |
| `impersonate_ability` | `null` | Gate-ability die bepaalt wie mag impersoneren (krijgt de doel-user) |
| `token_ttl.magic_link` | `15` min | Geldigheid magic-link |
| `register_rules` | `[]` | **Projectspecifiek**: extra registratievelden |
| `profiel_rules` | `[]` | **Projectspecifiek**: extra profielvelden |

**Features**: core-routes (login, logout, me, profiel, wachtwoord wijzigen, wachtwoordherstel)
staan altijd aan. Zet via `features` de optionele groepen uit die je niet gebruikt — handig voor
projecten die bv. geen zelfregistratie of e-mailverificatie willen.

**user_resource**: standaard geeft `/me`/login het model terug (met casts/appends). Zet een
JsonResource om de vorm volledig te bepalen (bv. rollen of berekende velden meesturen).

**Magic-link** (`features.magic_link`): passwordless login. `POST /magic-link` (vraag link aan,
anti-enumeratie) + `POST /magic-link/login` (token→sessie). Vereist een werkende mailer.

**Impersonatie** (`features.impersonation`): `POST /impersonate/{user}` + `POST /stop-impersonating`
(ingelogd). Autorisatie via `impersonate_ability` (Gate, in de host gedefinieerd); de
oorspronkelijke gebruiker wordt in de sessie bewaard onder `impersonating_from`.

Voorbeeld host-config (samenlesgeven):

```php
'mode' => 'session',
'user_with' => ['vakken', 'beheerdeVakken', 'vakCombinaties', 'methodes'],
'register_rules' => ['school' => 'nullable|string|max:255'],
'profiel_rules'  => ['school' => 'nullable|string|max:255', 'bio' => 'nullable|string|max:1000'],
```

Voorbeeld host-config (KMS — geen zelfregistratie, eigen user-vorm):

```php
'mode' => 'session',
'user_resource' => App\Http\Resources\UserResource::class,
'features' => ['registration' => false, 'email_verification' => false, 'email_change' => false,
               'avatar' => false, 'preferences' => false, 'passkeys' => true],
```

## Migraties

De module levert publiceerbare migraties voor `email_verification_tokens` en
`email_change_tokens` (met `Schema::hasTable`-guards). `users`,
`password_reset_tokens`, `personal_access_tokens` en `passkeys` komen uit
respectievelijk Laravel, Sanctum en `laravel/passkeys`.

```bash
php artisan vendor:publish --tag=auth-module-migrations
php artisan migrate
```

## Routes

Onder `route_prefix` (default `/api`):

**Publiek:** `POST /register`, `POST /login`, `POST /wachtwoord-vergeten`,
`POST /wachtwoord-resetten`, `GET /email-bevestigen`, `GET /email-verifieren`,
`GET /passkeys/login/options`, `POST /passkeys/login`

**Ingelogd (`auth:sanctum`):** `POST /logout`, `GET /me`, `PUT /me`,
`PUT /me/wachtwoord`, `DELETE /me/wachtwoord`, `POST /me/email`,
`POST /verificatie-opnieuw-sturen`, `POST /me/avatar`, `PUT /me/preferences`,
`GET|POST /user/passkeys`, `GET /user/passkeys/options`, `DELETE /user/passkeys/{passkey}`

Beveilig domeinroutes met de meegeleverde alias `email.geverifieerd`.

## session vs token

| | `session` | `token` |
|--|-----------|---------|
| Login-response | `{ user }` (sessiecookie) | `{ user, token }` (Bearer) |
| Logout | sessie invalideren | huidige token verwijderen |
| Frontend | cookies + CSRF | `Authorization: Bearer` |
| Geschikt voor | first-party SPA | mobiel / third-party / cross-domain |

## Mails / branding

Onderwerpen gebruiken `app_name`. Views overschrijven:

```bash
php artisan vendor:publish --tag=auth-module-views
# resources/views/vendor/auth-module/emails/*.blade.php
```

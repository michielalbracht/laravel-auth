<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gebruikersmodel & guard
    |--------------------------------------------------------------------------
    */

    'user_model' => env('AUTH_USER_MODEL', 'App\\Models\\User'),

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Authenticatiemodus
    |--------------------------------------------------------------------------
    |
    | 'session' = cookie/sessie-gebaseerd (Sanctum stateful SPA, HttpOnly +
    | CSRF) — de veilige default voor first-party browser-SPA's.
    | 'token'   = Sanctum personal access tokens (Bearer) — voor mobiel,
    | third-party of cross-domain clients.
    |
    */

    'mode' => env('AUTH_MODE', 'session'),

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    |
    | Naam van de applicatie zoals getoond in mail-onderwerpen, -teksten en de
    | meegeleverde blade-templates. Stel per project in via AUTH_APP_NAME.
    |
    */

    'app_name' => env('AUTH_APP_NAME', env('APP_NAME', 'App')),

    /*
    |--------------------------------------------------------------------------
    | Frontend-URL
    |--------------------------------------------------------------------------
    |
    | Basis-URL van de frontend waar verificatie-, e-mailwijzigings- en
    | wachtwoordresetlinks naartoe wijzen.
    |
    */

    'frontend_url' => env('FRONTEND_URL', env('APP_URL', 'http://localhost')),

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Prefix en middleware-groep waaronder de auth-routes worden geregistreerd.
    |
    */

    'route_prefix' => env('AUTH_ROUTE_PREFIX', 'api'),

    'route_middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Token-geldigheidsduur (in minuten)
    |--------------------------------------------------------------------------
    */

    'token_ttl' => [
        'email_verification' => 60 * 24, // 24 uur
        'email_change'       => 60,      // 1 uur
        'password_reset'     => 60,      // 1 uur
    ],

    /*
    |--------------------------------------------------------------------------
    | Eager-load relaties op auth-responses
    |--------------------------------------------------------------------------
    |
    | Relaties die met de gebruiker worden meegeladen in auth-responses
    | (register/login/me/profiel). PROJECTSPECIFIEK: standaard leeg; het
    | host-project vult dit in.
    |
    */

    'user_with' => [],

    /*
    |--------------------------------------------------------------------------
    | User-resource
    |--------------------------------------------------------------------------
    |
    | Optionele JsonResource-klasse die de user-payload in auth-responses vormt
    | (me/login/register/passkey). Null = het kale model (met casts/appends).
    | Zet dit om bv. rollen of berekende velden mee te sturen.
    |
    */

    'user_resource' => null,

    /*
    |--------------------------------------------------------------------------
    | Features (route-vlaggen)
    |--------------------------------------------------------------------------
    |
    | Bepaalt welke optionele routes worden geregistreerd. Core (login, logout,
    | me, profiel, wachtwoord wijzigen, wachtwoordherstel) staat altijd aan.
    | Zet features uit die een project niet gebruikt.
    |
    */

    'features' => [
        'registration'       => true,
        'email_verification' => true,
        'email_change'       => true,
        'avatar'             => true,
        'preferences'        => true,
        'passkeys'           => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Extra validatieregels
    |--------------------------------------------------------------------------
    |
    | Aanvullende regels bovenop de generieke auth-velden bij registratie en
    | profiel bijwerken. PROJECTSPECIFIEK: standaard leeg. De bijbehorende
    | velden moeten op het User-model fillable zijn.
    |
    */

    'register_rules' => [],

    'profiel_rules' => [],

];

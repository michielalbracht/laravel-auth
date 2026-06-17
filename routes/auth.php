<?php

use AlbrachtSystems\Auth\Http\Controllers\AuthController;
use AlbrachtSystems\Auth\Http\Controllers\ImpersonationController;
use AlbrachtSystems\Auth\Http\Controllers\MagicLinkController;
use AlbrachtSystems\Auth\Http\Controllers\PasskeyController;
use AlbrachtSystems\Auth\Http\Controllers\ProfielController;
use Illuminate\Support\Facades\Route;

// Feature-vlaggen bepalen welke optionele routes worden geregistreerd.
// Core (altijd aan): login, logout, me, profiel bijwerken, wachtwoord wijzigen
// en wachtwoordherstel.
$features = array_merge([
    'registration'       => true,
    'email_verification' => true,
    'email_change'       => true,
    'avatar'             => true,
    'preferences'        => true,
    'passkeys'           => true,
    'magic_link'         => false,
    'impersonation'      => false,
], config('auth-module.features', []));

Route::prefix(config('auth-module.route_prefix', 'api'))
    ->middleware(config('auth-module.route_middleware', ['api']))
    ->group(function () use ($features) {
        // ─── Publiek (core) ───────────────────────────────────────────────
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/wachtwoord-vergeten', [AuthController::class, 'wachtwoordVergeten']);
        Route::post('/wachtwoord-resetten', [AuthController::class, 'wachtwoordResetten']);

        // ─── Publiek (optioneel) ──────────────────────────────────────────
        if ($features['registration']) {
            Route::post('/register', [AuthController::class, 'register']);
        }
        if ($features['email_change']) {
            Route::get('/email-bevestigen', [AuthController::class, 'emailBevestigen']);
        }
        if ($features['email_verification']) {
            Route::get('/email-verifieren', [AuthController::class, 'emailVerificeren']);
        }
        if ($features['passkeys']) {
            Route::get('/passkeys/login/options', [PasskeyController::class, 'loginOptions']);
            Route::post('/passkeys/login', [PasskeyController::class, 'login']);
        }
        if ($features['magic_link']) {
            Route::post('/magic-link', [MagicLinkController::class, 'aanvragen']);
            Route::post('/magic-link/login', [MagicLinkController::class, 'login']);
        }

        // ─── Ingelogd ─────────────────────────────────────────────────────
        Route::middleware('auth:sanctum')->group(function () use ($features) {
            // Core
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/me', [ProfielController::class, 'updateProfiel']);
            Route::put('/me/wachtwoord', [AuthController::class, 'changePassword']);

            // Optioneel
            if ($features['passkeys']) {
                // Wachtwoord uitschakelen kan alleen met minstens één passkey.
                Route::delete('/me/wachtwoord', [AuthController::class, 'disablePassword']);
            }
            if ($features['email_change']) {
                Route::post('/me/email', [AuthController::class, 'emailWijzigen']);
            }
            if ($features['email_verification']) {
                Route::post('/verificatie-opnieuw-sturen', [AuthController::class, 'emailVerificatieOpnieuwSturen']);
            }
            if ($features['avatar']) {
                Route::post('/me/avatar', [ProfielController::class, 'updateAvatar']);
            }
            if ($features['preferences']) {
                Route::put('/me/preferences', [ProfielController::class, 'updatePreferences']);
            }
            if ($features['passkeys']) {
                Route::get('/user/passkeys/options', [PasskeyController::class, 'registerOptions']);
                Route::post('/user/passkeys', [PasskeyController::class, 'register']);
                Route::get('/user/passkeys', [PasskeyController::class, 'index']);
                Route::delete('/user/passkeys/{passkey}', [PasskeyController::class, 'destroy']);
            }
            if ($features['impersonation']) {
                Route::post('/impersonate/{user}', [ImpersonationController::class, 'start']);
                Route::post('/stop-impersonating', [ImpersonationController::class, 'stop']);
            }
        });
    });

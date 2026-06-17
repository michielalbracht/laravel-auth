<?php

use AlbrachtSystems\Auth\Http\Controllers\AuthController;
use AlbrachtSystems\Auth\Http\Controllers\PasskeyController;
use AlbrachtSystems\Auth\Http\Controllers\ProfielController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('auth-module.route_prefix', 'api'))
    ->middleware(config('auth-module.route_middleware', ['api']))
    ->group(function () {
        // ─── Publiek ──────────────────────────────────────────────────────
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/wachtwoord-vergeten', [AuthController::class, 'wachtwoordVergeten']);
        Route::post('/wachtwoord-resetten', [AuthController::class, 'wachtwoordResetten']);
        Route::get('/email-bevestigen', [AuthController::class, 'emailBevestigen']);
        Route::get('/email-verifieren', [AuthController::class, 'emailVerificeren']);

        Route::get('/passkeys/login/options', [PasskeyController::class, 'loginOptions']);
        Route::post('/passkeys/login', [PasskeyController::class, 'login']);

        // ─── Ingelogd ─────────────────────────────────────────────────────
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/me', [ProfielController::class, 'updateProfiel']);
            Route::put('/me/wachtwoord', [AuthController::class, 'changePassword']);
            Route::delete('/me/wachtwoord', [AuthController::class, 'disablePassword']);
            Route::post('/me/email', [AuthController::class, 'emailWijzigen']);
            Route::post('/verificatie-opnieuw-sturen', [AuthController::class, 'emailVerificatieOpnieuwSturen']);
            Route::post('/me/avatar', [ProfielController::class, 'updateAvatar']);
            Route::put('/me/preferences', [ProfielController::class, 'updatePreferences']);

            // Passkey-beheer
            Route::get('/user/passkeys/options', [PasskeyController::class, 'registerOptions']);
            Route::post('/user/passkeys', [PasskeyController::class, 'register']);
            Route::get('/user/passkeys', [PasskeyController::class, 'index']);
            Route::delete('/user/passkeys/{passkey}', [PasskeyController::class, 'destroy']);
        });
    });

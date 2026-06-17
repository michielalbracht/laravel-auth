<?php

namespace AlbrachtSystems\Auth\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gedeelde auth-respons- en sessiehelpers voor de auth-controllers
 * (login/register, magic-link, impersonatie). Houdt het sessie-/token-gedrag
 * op één plek.
 */
trait InteractsWithAuthSession
{
    use RespondsWithUser;

    protected function isSessionMode(): bool
    {
        return config('auth-module.mode', 'session') === 'session';
    }

    /**
     * Log de gebruiker in de sessie-guard (sessie-modus) en vernieuw de sessie-ID.
     * In token-modus doet dit niets (de response levert dan een token).
     */
    protected function establishSession(Request $request, Model $user): void
    {
        if (! $this->isSessionMode()) {
            return;
        }

        Auth::guard(config('auth-module.guard'))->login($user);
        $this->regenerateSession($request);
    }

    protected function regenerateSession(Request $request): void
    {
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
    }

    /**
     * Bouwt de auth-response. In token-modus wordt een Bearer-token bijgevoegd;
     * in sessie-modus draagt de sessiecookie de authenticatie.
     */
    protected function authResponse(Request $request, Model $user, int $status = 200): JsonResponse
    {
        $payload = ['user' => $this->metRelaties($user)];

        if (! $this->isSessionMode()) {
            $payload['token'] = $user->createToken('auth_token')->plainTextToken;
        }

        return response()->json($payload, $status);
    }
}

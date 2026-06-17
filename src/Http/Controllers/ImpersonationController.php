<?php

namespace AlbrachtSystems\Auth\Http\Controllers;

use AlbrachtSystems\Auth\Concerns\InteractsWithAuthSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Impersonatie: een gemachtigde gebruiker logt tijdelijk in als een andere
 * gebruiker. De autorisatie (wie mag impersoneren) is projectspecifiek en loopt
 * via een Gate-ability uit config('auth-module.impersonate_ability').
 */
class ImpersonationController extends Controller
{
    use InteractsWithAuthSession;

    public function start(Request $request, string $user): JsonResponse
    {
        $model = config('auth-module.user_model');
        $target = $model::findOrFail($user);

        if ($ability = config('auth-module.impersonate_ability')) {
            Gate::authorize($ability, $target);
        }

        // Bewaar de oorspronkelijke gebruiker zodat we kunnen terugkeren.
        $request->session()->put('impersonating_from', $request->user()->getAuthIdentifier());
        Auth::guard(config('auth-module.guard'))->login($target);

        return $this->authResponse($request, $target);
    }

    public function stop(Request $request): JsonResponse
    {
        $originalId = $request->session()->pull('impersonating_from');
        abort_unless($originalId, 400, 'Niet aan het impersoneren.');

        $model = config('auth-module.user_model');
        $original = $model::findOrFail($originalId);
        Auth::guard(config('auth-module.guard'))->login($original);

        return $this->authResponse($request, $original);
    }
}

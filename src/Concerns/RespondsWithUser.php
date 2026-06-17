<?php

namespace AlbrachtSystems\Auth\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Laadt de projectspecifieke relaties (config auth-module.user_with) op de
 * gebruiker voor auth-/profiel-responses. Standaard is deze lijst leeg; het
 * host-project vult hem in.
 */
trait RespondsWithUser
{
    protected function metRelaties(Model $user): Model
    {
        return $user->load(config('auth-module.user_with', []));
    }
}

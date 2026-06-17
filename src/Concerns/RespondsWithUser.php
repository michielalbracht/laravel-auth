<?php

namespace AlbrachtSystems\Auth\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Laadt de projectspecifieke relaties (config auth-module.user_with) op de
 * gebruiker voor auth-/profiel-responses, en wikkelt deze optioneel in een
 * JsonResource (config auth-module.user_resource) zodat het host-project de
 * vorm van de user-payload volledig bepaalt. Zonder resource wordt het model
 * teruggegeven.
 */
trait RespondsWithUser
{
    protected function metRelaties(Model $user): Model|JsonResource
    {
        $user->load(config('auth-module.user_with', []));

        $resource = config('auth-module.user_resource');

        return $resource ? new $resource($user) : $user;
    }
}

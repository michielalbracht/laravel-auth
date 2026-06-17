<?php

namespace AlbrachtSystems\Auth\Concerns;

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Bundelt alle generieke authenticatie-concerns van het gebruikersmodel:
 * API-tokens (Sanctum), notificaties, passkeys, e-mailverificatie, het
 * wachtwoord en het avatar/preferences-mechanisme.
 *
 * Domeinspecifieke velden en relaties blijven op het host-model staan.
 */
trait HasAuthentication
{
    use HasApiTokens;
    use Notifiable;
    use PasskeyAuthenticatable;

    /**
     * Voegt de generieke auth-velden, casts en zichtbaarheid toe aan het model
     * zodra het wordt geïnstantieerd. Het host-model houdt zijn eigen
     * domein-$fillable/$casts; deze worden hiermee samengevoegd.
     */
    public function initializeHasAuthentication(): void
    {
        $this->mergeFillable(['name', 'email', 'password', 'avatar', 'preferences']);

        $this->mergeCasts([
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'preferences'       => 'array',
        ]);

        $this->append(['avatar_url', 'has_password']);
        $this->makeHidden(['password', 'remember_token']);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? Storage::disk('public')->url($this->avatar) : null;
    }

    public function getHasPasswordAttribute(): bool
    {
        return ! is_null($this->password);
    }
}

<?php

namespace AlbrachtSystems\Auth\Http\Controllers;

use AlbrachtSystems\Auth\Concerns\RespondsWithUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Generieke profiel-acties: naam bijwerken (+ projectspecifieke velden via
 * config('auth-module.profiel_rules')), avatar-upload en het preferences-
 * mechanisme. Domeinspecifieke profielvelden worden door het project via config
 * toegevoegd.
 */
class ProfielController extends Controller
{
    use RespondsWithUser;

    public function updateProfiel(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge([
            'name' => 'sometimes|string|max:255',
        ], config('auth-module.profiel_rules', [])));

        $request->user()->update($validated);

        return response()->json($this->metRelaties($request->user()->fresh()));
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate(['avatar' => 'required|image|max:2048']);

        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $bestand = $request->file('avatar');
        $bestandsnaam = 'avatars/' . uniqid() . '.jpg';

        $afbeelding = Image::decode($bestand)
            ->cover(256, 256)
            ->encode(new \Intervention\Image\Encoders\JpegEncoder(quality: 85));

        Storage::disk('public')->put($bestandsnaam, $afbeelding);
        $user->update(['avatar' => $bestandsnaam]);

        return response()->json($this->metRelaties($user->fresh()));
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate(['preferences' => 'required|array']);
        $request->user()->update(['preferences' => $request->preferences]);
        return response()->json(['preferences' => $request->user()->fresh()->preferences]);
    }
}

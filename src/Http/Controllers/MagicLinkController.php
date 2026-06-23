<?php

namespace AlbrachtSystems\Auth\Http\Controllers;

use AlbrachtSystems\Auth\Concerns\InteractsWithAuthSession;
use AlbrachtSystems\Auth\Mail\MagicLinkMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Passwordless inloggen via een eenmalige e-maillink (magic link).
 *
 * De e-mail bevat zowel een klikbare link als een 6-cijferige code die de
 * gebruiker handmatig kan invoeren. Beide leiden naar hetzelfde verzilver-
 * endpoint. De link-token is onraadbaar lang; de korte code heeft een
 * pogingenlimiet tegen brute-force.
 */
class MagicLinkController extends Controller
{
    use InteractsWithAuthSession;

    /** Maximaal aantal foute code-invoeren voordat de aanvraag vervalt. */
    private const MAX_CODE_ATTEMPTS = 5;

    public function aanvragen(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $model = config('auth-module.user_model');
        $user = $model::where('email', $request->email)->first();

        // Altijd succesbericht (voorkomt e-mailenumeratie).
        if ($user) {
            DB::table('magic_link_tokens')->where('email', $user->email)->delete();

            $token = Str::random(64);
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            DB::table('magic_link_tokens')->insert([
                'email'      => $user->email,
                'token'      => Hash::make($token),
                'code'       => Hash::make($code),
                'attempts'   => 0,
                'created_at' => now(),
            ]);

            $url = rtrim(config('auth-module.frontend_url'), '/')
                . '/magic-login?token=' . $token . '&email=' . urlencode($user->email);

            Mail::to($user->email)->send(new MagicLinkMail($url, $code, $user->name));
        }

        return response()->json(['message' => 'Als dit e-mailadres bekend is, ontvang je een inloglink.']);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required_without:code|string',
            'code'  => 'required_without:token|string',
        ]);

        $record = DB::table('magic_link_tokens')->where('email', $request->email)->first();

        if (!$record) {
            throw ValidationException::withMessages(['token' => ['Deze inloglink is ongeldig.']]);
        }

        // Verlopen? (absolute verschil — Carbon 3 geeft anders een negatief getal)
        if (now()->diffInMinutes($record->created_at, true) > config('auth-module.token_ttl.magic_link')) {
            DB::table('magic_link_tokens')->where('email', $request->email)->delete();
            throw ValidationException::withMessages(['token' => ['Deze inloglink is verlopen. Vraag een nieuwe aan.']]);
        }

        // Verzilveren via link-token of via de handmatig ingevoerde code.
        $viaCode = $request->filled('code') && ! $request->filled('token');

        $geldig = $viaCode
            ? Hash::check($request->code, $record->code)
            : Hash::check($request->token, $record->token);

        if (! $geldig) {
            // Korte code is raadbaar: tel pogingen en verval na te veel fouten.
            if ($viaCode) {
                $pogingen = (int) $record->attempts + 1;

                if ($pogingen >= self::MAX_CODE_ATTEMPTS) {
                    DB::table('magic_link_tokens')->where('email', $request->email)->delete();
                    throw ValidationException::withMessages(['code' => ['Te veel pogingen. Vraag een nieuwe inloglink aan.']]);
                }

                DB::table('magic_link_tokens')->where('email', $request->email)->update(['attempts' => $pogingen]);

                throw ValidationException::withMessages(['code' => ['Deze code is ongeldig.']]);
            }

            throw ValidationException::withMessages(['token' => ['Deze inloglink is ongeldig.']]);
        }

        $model = config('auth-module.user_model');
        $user = $model::where('email', $request->email)->firstOrFail();

        // Eenmalig gebruik.
        DB::table('magic_link_tokens')->where('email', $request->email)->delete();

        $this->establishSession($request, $user);

        return $this->authResponse($request, $user);
    }
}

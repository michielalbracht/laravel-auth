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
 */
class MagicLinkController extends Controller
{
    use InteractsWithAuthSession;

    public function aanvragen(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $model = config('auth-module.user_model');
        $user = $model::where('email', $request->email)->first();

        // Altijd succesbericht (voorkomt e-mailenumeratie).
        if ($user) {
            DB::table('magic_link_tokens')->where('email', $user->email)->delete();
            $token = Str::random(64);
            DB::table('magic_link_tokens')->insert([
                'email'      => $user->email,
                'token'      => Hash::make($token),
                'created_at' => now(),
            ]);

            $url = rtrim(config('auth-module.frontend_url'), '/')
                . '/magic-login?token=' . $token . '&email=' . urlencode($user->email);

            Mail::to($user->email)->send(new MagicLinkMail($url, $user->name));
        }

        return response()->json(['message' => 'Als dit e-mailadres bekend is, ontvang je een inloglink.']);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
        ]);

        $record = DB::table('magic_link_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            throw ValidationException::withMessages(['token' => ['Deze inloglink is ongeldig.']]);
        }

        if (now()->diffInMinutes($record->created_at) > config('auth-module.token_ttl.magic_link')) {
            DB::table('magic_link_tokens')->where('email', $request->email)->delete();
            throw ValidationException::withMessages(['token' => ['Deze inloglink is verlopen. Vraag een nieuwe aan.']]);
        }

        $model = config('auth-module.user_model');
        $user = $model::where('email', $request->email)->firstOrFail();

        // Eenmalig gebruik.
        DB::table('magic_link_tokens')->where('email', $request->email)->delete();

        $this->establishSession($request, $user);

        return $this->authResponse($request, $user);
    }
}

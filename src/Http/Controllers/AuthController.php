<?php

namespace AlbrachtSystems\Auth\Http\Controllers;

use AlbrachtSystems\Auth\Concerns\InteractsWithAuthSession;
use AlbrachtSystems\Auth\Mail\EmailVerificatieMail;
use AlbrachtSystems\Auth\Mail\EmailWijzigenMail;
use AlbrachtSystems\Auth\Mail\WachtwoordResetMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use InteractsWithAuthSession;

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ], config('auth-module.register_rules', [])));

        $model = $this->userModel();
        $user = $model::create($validated);

        // Stuur verificatie-email (fout in mail mag registratie niet breken)
        try {
            $this->stuurVerificatieMail($user);
        } catch (\Exception $e) {
            Log::error('Verificatiemail mislukt bij registratie', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }

        // In sessie-modus de gebruiker direct inloggen.
        $this->establishSession($request, $user);

        return $this->authResponse($request, $user, 201);
    }

    private function stuurVerificatieMail(Model $user): void
    {
        DB::table('email_verification_tokens')->where('user_id', $user->id)->delete();
        $token = Str::random(64);
        DB::table('email_verification_tokens')->insert([
            'user_id'    => $user->id,
            'token'      => Hash::make($token),
            'created_at' => now(),
        ]);
        $verificatieUrl = $this->frontendUrl('/email-verifieren?token=' . $token . '&user=' . $user->id);
        Mail::to($user->email)->send(new EmailVerificatieMail($verificatieUrl, $user->name));
    }

    public function emailVerificatieOpnieuwSturen(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Je e-mailadres is al bevestigd.'], 422);
        }

        try {
            $this->stuurVerificatieMail($user);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Het versturen van de verificatiemail is mislukt. Probeer het later opnieuw.'], 500);
        }

        return response()->json(['message' => 'Er is een nieuwe verificatiemail gestuurd.']);
    }

    public function emailVerificeren(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'user'  => 'required|integer|exists:users,id',
        ]);

        $userId = (int) $request->input('user');
        $record = DB::table('email_verification_tokens')->where('user_id', $userId)->first();

        if (!$record) {
            return response()->json(['status' => 'ongeldig'], 422);
        }

        // Expiry eerst controleren: verlopen token toont 'verlopen' met herstelknop,
        // ook als de URL ondertussen is vervangen of beschadigd.
        $ttl = config('auth-module.token_ttl.email_verification');
        if (\Carbon\Carbon::parse($record->created_at)->addMinutes($ttl)->isPast()) {
            DB::table('email_verification_tokens')->where('user_id', $userId)->delete();
            return response()->json(['status' => 'verlopen'], 422);
        }

        if (!Hash::check($request->input('token'), $record->token)) {
            return response()->json(['status' => 'ongeldig'], 422);
        }

        $this->userModel()::where('id', $userId)->update(['email_verified_at' => now()]);
        DB::table('email_verification_tokens')->where('user_id', $userId)->delete();

        return response()->json(['status' => 'gelukt']);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['De opgegeven gegevens zijn onjuist.'],
            ]);
        }

        // Auth::attempt heeft de gebruiker al in de sessie-guard gezet; bij een
        // succesvolle login de sessie-ID vernieuwen tegen session fixation.
        if ($this->isSessionMode()) {
            $this->regenerateSession($request);
        }

        return $this->authResponse($request, Auth::user());
    }

    public function logout(Request $request): JsonResponse
    {
        if ($this->isSessionMode()) {
            Auth::guard(config('auth-module.guard'))->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        } else {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Uitgelogd']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->metRelaties($request->user()));
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();
        $heeftWachtwoord = !is_null($user->password);

        $request->validate([
            'huidig_wachtwoord' => $heeftWachtwoord ? 'required|string' : 'sometimes|string',
            'nieuw_wachtwoord'  => 'required|string|min:8|confirmed',
        ]);

        if ($heeftWachtwoord && !Hash::check($request->huidig_wachtwoord, $user->password)) {
            throw ValidationException::withMessages([
                'huidig_wachtwoord' => ['Het huidige wachtwoord is onjuist.'],
            ]);
        }

        $user->update(['password' => Hash::make($request->nieuw_wachtwoord)]);

        return response()->json(['message' => 'Wachtwoord gewijzigd.']);
    }

    public function emailWijzigen(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'nieuw_email' => 'required|email|unique:users,email|different:' . $user->email,
        ]);

        // Verwijder eventueel bestaand verzoek
        DB::table('email_change_tokens')->where('user_id', $user->id)->delete();

        $token = Str::random(64);
        DB::table('email_change_tokens')->insert([
            'user_id'     => $user->id,
            'nieuw_email' => $request->nieuw_email,
            'token'       => Hash::make($token),
            'created_at'  => now(),
        ]);

        $bevestigUrl = $this->frontendUrl('/email-bevestigen?token=' . $token . '&user=' . $user->id);

        Mail::to($request->nieuw_email)->send(new EmailWijzigenMail($bevestigUrl, $user->name, $request->nieuw_email));

        return response()->json(['message' => 'Er is een bevestigingsmail gestuurd naar ' . $request->nieuw_email . '.']);
    }

    public function emailBevestigen(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'user'  => 'required|integer|exists:users,id',
        ]);

        $userId = (int) $request->input('user');
        $record = DB::table('email_change_tokens')->where('user_id', $userId)->first();

        if (!$record || !Hash::check($request->input('token'), $record->token)) {
            return response()->json(['status' => 'ongeldig'], 422);
        }

        if (now()->diffInMinutes($record->created_at) > config('auth-module.token_ttl.email_change')) {
            DB::table('email_change_tokens')->where('user_id', $userId)->delete();
            return response()->json(['status' => 'verlopen'], 422);
        }

        if ($this->userModel()::where('email', $record->nieuw_email)->exists()) {
            DB::table('email_change_tokens')->where('user_id', $userId)->delete();
            return response()->json(['status' => 'al_in_gebruik'], 422);
        }

        $this->userModel()::where('id', $userId)->update(['email' => $record->nieuw_email]);
        DB::table('email_change_tokens')->where('user_id', $userId)->delete();

        return response()->json(['status' => 'gelukt']);
    }

    public function wachtwoordVergeten(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = $this->userModel()::where('email', $request->email)->first();

        // Altijd succesbericht tonen (voorkomt e-mailenumeratie)
        if (!$user) {
            return response()->json(['message' => 'Als dit e-mailadres bekend is, ontvang je een resetlink.']);
        }

        // Verwijder eventuele bestaande token en sla nieuwe op
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email'      => $user->email,
            'token'      => Hash::make($token),
            'created_at' => now(),
        ]);

        $resetUrl = $this->frontendUrl('/wachtwoord-resetten?token=' . $token . '&email=' . urlencode($user->email));

        Mail::to($user->email)->send(new WachtwoordResetMail($resetUrl, $user->name));

        return response()->json(['message' => 'Als dit e-mailadres bekend is, ontvang je een resetlink.']);
    }

    public function wachtwoordResetten(Request $request): JsonResponse
    {
        $request->validate([
            'email'      => 'required|email',
            'token'      => 'required|string',
            'wachtwoord' => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            throw ValidationException::withMessages(['token' => ['Deze resetlink is ongeldig.']]);
        }

        if (now()->diffInMinutes($record->created_at) > config('auth-module.token_ttl.password_reset')) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            throw ValidationException::withMessages(['token' => ['Deze resetlink is verlopen. Vraag een nieuwe aan.']]);
        }

        $user = $this->userModel()::where('email', $request->email)->firstOrFail();
        $user->update(['password' => Hash::make($request->wachtwoord)]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Wachtwoord succesvol gewijzigd. Je kunt nu inloggen.']);
    }

    public function disablePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->passkeys()->count() === 0) {
            throw ValidationException::withMessages([
                'password' => ['Je hebt minimaal één passkey nodig voordat je je wachtwoord kunt uitschakelen.'],
            ]);
        }

        $user->update(['password' => null]);

        return response()->json(['message' => 'Wachtwoord uitgeschakeld.']);
    }

    /**
     * Het geconfigureerde Eloquent user-model (FQCN).
     */
    protected function userModel(): string
    {
        return config('auth-module.user_model');
    }

    /**
     * Bouwt een absolute frontend-URL op basis van de geconfigureerde frontend-URL.
     */
    private function frontendUrl(string $path): string
    {
        return rtrim(config('auth-module.frontend_url'), '/') . $path;
    }
}

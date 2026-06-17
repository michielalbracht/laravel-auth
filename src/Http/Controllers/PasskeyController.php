<?php

namespace AlbrachtSystems\Auth\Http\Controllers;

use AlbrachtSystems\Auth\Concerns\RespondsWithUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Support\WebAuthn;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

class PasskeyController extends Controller
{
    use RespondsWithUser;

    private const CHALLENGE_TTL = 300; // 5 minutes

    // ─── Login (gast) ───────────────────────────────────────────────────────

    public function loginOptions(GenerateVerificationOptions $generate): JsonResponse
    {
        $options = $generate();
        $serialized = WebAuthn::toJson($options);
        $browserOptions = WebAuthn::toBrowserArray($options);

        // challenge is base64url-encoded in the browser array
        $challenge = $browserOptions['challenge'];
        Cache::put("passkey:login:{$challenge}", $serialized, self::CHALLENGE_TTL);

        return response()->json(['options' => $browserOptions]);
    }

    public function login(Request $request, VerifyPasskey $verify): JsonResponse
    {
        $credential = $this->parseCredential($request);
        $options = $this->pullLoginOptions($request);

        try {
            $passkey = $verify($credential, $options);
        } catch (InvalidPasskeyException $e) {
            throw ValidationException::withMessages(['credential' => $e->getMessage()]);
        }

        $user = $passkey->user;

        if (config('auth-module.mode', 'session') === 'session') {
            Auth::guard(config('auth-module.guard'))->login($user);
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            return response()->json(['user' => $this->metRelaties($user)]);
        }

        $token = $user->createToken('passkey')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $this->metRelaties($user)]);
    }

    // ─── Registratie (ingelogd) ──────────────────────────────────────────────

    public function registerOptions(Request $request, GenerateRegistrationOptions $generate): JsonResponse
    {
        $user = $request->user();
        $options = $generate($user);
        $serialized = WebAuthn::toJson($options);

        Cache::put("passkey:reg:{$user->id}", $serialized, self::CHALLENGE_TTL);

        return response()->json(['options' => WebAuthn::toBrowserArray($options)]);
    }

    public function register(Request $request, StorePasskey $store): JsonResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:255']]);

        $user = $request->user();
        $credential = $this->parseCredential($request);
        $options = $this->pullRegisterOptions($user->id);

        try {
            $passkey = $store($user, $request->input('name'), $credential, $options);
        } catch (InvalidPasskeyException $e) {
            throw ValidationException::withMessages(['credential' => $e->getMessage()]);
        }

        return response()->json($this->passkeyPayload($passkey), 201);
    }

    // ─── Beheer (ingelogd) ───────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $passkeys = $request->user()->passkeys()->orderByDesc('created_at')->get()
            ->map(fn (Passkey $p) => $this->passkeyPayload($p));

        return response()->json($passkeys);
    }

    public function destroy(Request $request, Passkey $passkey): JsonResponse
    {
        $user = $request->user();
        abort_unless($passkey->user_id === $user->id, 403);

        if (is_null($user->password) && $user->passkeys()->count() <= 1) {
            throw ValidationException::withMessages([
                'passkey' => ['Je kunt je laatste passkey niet verwijderen als je geen wachtwoord hebt ingesteld.'],
            ]);
        }

        $passkey->delete();

        return response()->json(['ok' => true]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function parseCredential(Request $request): PublicKeyCredential
    {
        $data = $request->validate(['credential' => ['required', 'array']]);

        try {
            return WebAuthn::fromJson(
                json_encode($data['credential'], JSON_THROW_ON_ERROR),
                PublicKeyCredential::class
            );
        } catch (\Throwable) {
            throw ValidationException::withMessages(['credential' => 'Ongeldig credential formaat.']);
        }
    }

    private function pullLoginOptions(Request $request): PublicKeyCredentialRequestOptions
    {
        // clientDataJSON is base64url-encoded; decode it to extract the challenge
        $clientDataJSON = $request->input('credential.response.clientDataJSON', '');
        $decoded = json_decode(base64_decode(strtr($clientDataJSON, '-_', '+/')), true);
        $challenge = $decoded['challenge'] ?? '';

        $serialized = Cache::pull("passkey:login:{$challenge}");

        if (! $serialized) {
            throw ValidationException::withMessages(['credential' => 'Passkey sessie verlopen. Probeer opnieuw.']);
        }

        return WebAuthn::fromJson($serialized, PublicKeyCredentialRequestOptions::class);
    }

    private function pullRegisterOptions(int $userId): PublicKeyCredentialCreationOptions
    {
        $serialized = Cache::pull("passkey:reg:{$userId}");

        if (! $serialized) {
            throw ValidationException::withMessages(['credential' => 'Registratie sessie verlopen. Probeer opnieuw.']);
        }

        return WebAuthn::fromJson($serialized, PublicKeyCredentialCreationOptions::class);
    }

    private function passkeyPayload(Passkey $passkey): array
    {
        return [
            'id' => $passkey->id,
            'name' => $passkey->name,
            'created_at' => $passkey->created_at?->toIso8601String(),
            'last_used_at' => $passkey->last_used_at?->toIso8601String(),
        ];
    }

}

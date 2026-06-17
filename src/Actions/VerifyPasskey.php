<?php

namespace AlbrachtSystems\Auth\Actions;

use Laravel\Passkeys\Actions\VerifyPasskey as BaseVerifyPasskey;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialRequestOptions;

class VerifyPasskey extends BaseVerifyPasskey
{
    protected function validate(
        AuthenticatorAssertionResponse $response,
        Passkey $passkey,
        PublicKeyCredentialRequestOptions $options
    ): CredentialRecord {
        $source = WebAuthn::fromJson(
            json_encode($passkey->credential, JSON_THROW_ON_ERROR),
            CredentialRecord::class
        );

        if (! config('passkeys.allow_subdomains', false)) {
            return WebAuthn::assertionValidator()->check(
                credentialRecord: $source,
                authenticatorAssertionResponse: $response,
                publicKeyCredentialRequestOptions: $options,
                host: Passkeys::relyingPartyId(),
                userHandle: $source->userHandle,
            );
        }

        $attestationManager = AttestationStatementSupportManager::create();
        $attestationManager->add(NoneAttestationStatementSupport::create());

        $factory = new CeremonyStepManagerFactory;
        $factory->setAllowedOrigins(Passkeys::allowedOrigins(), allowSubdomains: true);
        $factory->setAttestationStatementSupportManager($attestationManager);

        return AuthenticatorAssertionResponseValidator::create(
            ceremonyStepManager: $factory->requestCeremony()
        )->check(
            credentialRecord: $source,
            authenticatorAssertionResponse: $response,
            publicKeyCredentialRequestOptions: $options,
            host: Passkeys::relyingPartyId(),
            userHandle: $source->userHandle,
        );
    }
}

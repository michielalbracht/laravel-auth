<?php

namespace AlbrachtSystems\Auth\Actions;

use Laravel\Passkeys\Actions\StorePasskey as BaseStorePasskey;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialCreationOptions;

class StorePasskey extends BaseStorePasskey
{
    protected function validate(
        AuthenticatorAttestationResponse $response,
        PublicKeyCredentialCreationOptions $options
    ): CredentialRecord {
        if (! config('passkeys.allow_subdomains', false)) {
            return WebAuthn::attestationValidator()->check(
                authenticatorAttestationResponse: $response,
                publicKeyCredentialCreationOptions: $options,
                host: Passkeys::relyingPartyId(),
            );
        }

        $attestationManager = AttestationStatementSupportManager::create();
        $attestationManager->add(NoneAttestationStatementSupport::create());

        $factory = new CeremonyStepManagerFactory;
        $factory->setAllowedOrigins(Passkeys::allowedOrigins(), allowSubdomains: true);
        $factory->setAttestationStatementSupportManager($attestationManager);

        return AuthenticatorAttestationResponseValidator::create(
            ceremonyStepManager: $factory->creationCeremony()
        )->check(
            authenticatorAttestationResponse: $response,
            publicKeyCredentialCreationOptions: $options,
            host: Passkeys::relyingPartyId(),
        );
    }
}

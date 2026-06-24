<?php
namespace App\Security;

/**
 * JwtVerifier placeholder
 *
 * The project currently validates JWTs inside `JwtAuthenticator` directly.
 * This class is provided as a small, documented placeholder you can replace or
 * refactor `JwtAuthenticator` to use. Implementations should:
 *  - fetch JWKS from the configured issuer
 *  - cache JWKS and select the key by `kid`
 *  - verify signature, issuer, audience and exp
 *
 * For now this file only documents the intended responsibilities.
 */
class JwtVerifier
{
    /**
     * Verify and decode a JWT token.
     *
     * @param string $token The raw JWT string
     * @param string $expectedIssuer Expected issuer (iss claim)
     * @param string|null $expectedAudience Expected audience (aud claim) or null to skip
     * @return array Decoded payload claims
     * @throws \RuntimeException on verification failure
     */
    public function verify(string $token, string $expectedIssuer, ?string $expectedAudience = null): array
    {
        // TODO: Move existing logic from JwtAuthenticator::validateAndDecodeToken here.
        throw new \RuntimeException('JwtVerifier::verify not implemented yet. See JwtAuthenticator for current validation logic.');
    }
}


<?php

namespace App\Security;

use App\Entity\Benutzer;
use App\Service\KeycloakUserProvisioningService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Psr\Log\LoggerInterface;

class JwtAuthenticator extends AbstractAuthenticator
{
    private string $oidcIssuer;
    private EntityManagerInterface $entityManager;
    private KeycloakUserProvisioningService $provisioningService;
    private LoggerInterface $logger;
    private ?array $cachedJwks = null;
    private ?int $jwksLastFetch = null;
    private const JWKS_CACHE_TTL = 3600; // 1 hour

    public function __construct(
        string $oidcIssuer,
        EntityManagerInterface $entityManager,
        KeycloakUserProvisioningService $provisioningService,
        LoggerInterface $logger
    ) {
        $this->oidcIssuer = $oidcIssuer;
        $this->entityManager = $entityManager;
        $this->provisioningService = $provisioningService;
        $this->logger = $logger;
    }

    /**
     * Called on every request to decide if this authenticator should be used.
     */
    public function supports(Request $request): ?bool
    {
        // Only authenticate requests with Authorization header containing "Bearer"
        $token = $this->extractTokenFromRequest($request);
        return $token !== null;
    }

    /**
     * Create a Passport for the request (authenticate the token).
     */
    public function authenticate(Request $request): Passport
    {
        $token = $this->extractTokenFromRequest($request);

        if (!$token) {
            throw new AuthenticationException('No JWT token provided');
        }

        try {
            // Validate and decode the JWT
            $decodedToken = $this->validateAndDecodeToken($token);
            $identityId = $decodedToken['sub'] ?? null;

            if (!$identityId) {
                throw new AuthenticationException('JWT token missing "sub" claim (identityId)');
            }

            // Provision user if needed
            $this->provisioningService->provisionUserFromJwtClaims($decodedToken);

            // Return a Passport with UserBadge for Symfony to load the user
            return new SelfValidatingPassport(
                new UserBadge($identityId, function ($identifier) {
                    $benutzer = $this->entityManager->getRepository(Benutzer::class)
                        ->findOneBy(['identityId' => $identifier]);

                    if (!$benutzer) {
                        throw new AuthenticationException("User with identityId '$identifier' not found after provisioning");
                    }

                    return $benutzer;
                })
            );
        } catch (\Throwable $e) {
            $this->logger->error('JWT validation error', ['exception' => $e->getMessage()]);
            throw new AuthenticationException('Invalid JWT token: ' . $e->getMessage());
        }
    }

    /**
     * Called after authentication is successful; creates an authenticated token.
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?JsonResponse
    {
        // Authentication was successful; let the request continue
        return null;
    }

    /**
     * Called when authentication fails.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        $data = [
            'type' => '/problems/authentication-failed',
            'title' => 'Authentication Failed',
            'status' => 401,
            'detail' => $exception->getMessageKey(),
            'instance' => $request->getPathInfo()
        ];

        return new JsonResponse($data, JsonResponse::HTTP_UNAUTHORIZED);
    }

    /**
     * Extract JWT token from Authorization header.
     */
    private function extractTokenFromRequest(Request $request): ?string
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return substr($authHeader, 7); // Remove "Bearer " prefix
    }

    /**
     * Validate JWT token against Keycloak's JWKS and return decoded claims.
     */
    private function validateAndDecodeToken(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new AuthenticationException('Invalid JWT structure');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;
        $header = $this->jsonDecode($this->base64UrlDecode($headerB64));
        $payload = $this->jsonDecode($this->base64UrlDecode($payloadB64));

        if (!isset($header['alg']) || $header['alg'] !== 'RS256') {
            throw new AuthenticationException('Unsupported JWT algorithm');
        }

        if (!isset($header['kid'])) {
            throw new AuthenticationException('JWT header missing kid');
        }

        if (!isset($payload['iss']) || $payload['iss'] !== $this->oidcIssuer) {
            throw new AuthenticationException('Invalid token issuer');
        }

        if (isset($payload['exp']) && (int) $payload['exp'] < time()) {
            throw new AuthenticationException('Token has expired');
        }

        $jwks = $this->getKeycloakJwks();

        $jwk = $this->findMatchingJwk($jwks, $header['kid']);
        $publicKey = $this->jwkToRsaPublicKeyPem($jwk);

        $signedData = $headerB64 . '.' . $payloadB64;
        $signature = $this->base64UrlDecode($signatureB64);
        $verifyResult = openssl_verify($signedData, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($verifyResult !== 1) {
            throw new AuthenticationException('Token signature verification failed');
        }

        return $payload;
    }

    /**
     * Fetch JWKS (JSON Web Key Set) from Keycloak with caching.
     */
    private function getKeycloakJwks(): array
    {
        // Use cached JWKS if available and not expired
        if ($this->cachedJwks !== null && $this->jwksLastFetch !== null) {
            if (time() - $this->jwksLastFetch < self::JWKS_CACHE_TTL) {
                return $this->cachedJwks;
            }
        }

        $jwksUrls = $this->buildCandidateJwksUrls();

        $lastError = null;
        foreach ($jwksUrls as $jwksUrl) {
            try {
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'timeout' => 5,
                        'ignore_errors' => true,
                        'header' => "Accept: application/json\r\n",
                    ],
                ]);

                $json = @file_get_contents($jwksUrl, false, $context);
                if ($json === false) {
                    throw new AuthenticationException('Failed to fetch JWKS from issuer');
                }

                $jwks = json_decode($json, true);
                if (!is_array($jwks)) {
                    throw new AuthenticationException('JWKS response is not valid JSON');
                }

                $this->cachedJwks = $jwks;
                $this->jwksLastFetch = time();

                return $jwks;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                $this->logger->warning('Failed to fetch JWKS candidate', [
                    'url' => $jwksUrl,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        throw new AuthenticationException('Failed to validate JWT: Could not fetch JWKS from issuer. Last error: ' . ($lastError ?? 'unknown'));
    }

    /**
     * Build JWKS URL candidates.
     * First try the configured issuer URL, then fall back to the internal Docker hostname if needed.
     */
    private function buildCandidateJwksUrls(): array
    {
        $issuerBase = rtrim($this->oidcIssuer, '/');
        $urls = [$issuerBase . '/protocol/openid-connect/certs'];

        $parts = parse_url($issuerBase);
        if (($parts['host'] ?? null) === 'localhost' || ($parts['host'] ?? null) === '127.0.0.1') {
            $scheme = $parts['scheme'] ?? 'http';
            $path = $parts['path'] ?? '';
            $urls[] = $scheme . '://keycloak:8080' . $path . '/protocol/openid-connect/certs';
        }

        return array_values(array_unique($urls));
    }

    private function findMatchingJwk(array $jwks, string $kid): array
    {
        if (!isset($jwks['keys']) || !is_array($jwks['keys'])) {
            throw new AuthenticationException('Invalid JWKS document');
        }

        foreach ($jwks['keys'] as $jwk) {
            if (($jwk['kid'] ?? null) === $kid && ($jwk['kty'] ?? null) === 'RSA') {
                return $jwk;
            }
        }

        throw new AuthenticationException(sprintf('No matching RSA key found for kid "%s"', $kid));
    }

    private function jwkToRsaPublicKeyPem(array $jwk): string
    {
        if (!isset($jwk['n'], $jwk['e'])) {
            throw new AuthenticationException('JWK missing RSA parameters n/e');
        }

        $modulus = $this->base64UrlDecode($jwk['n']);
        $exponent = $this->base64UrlDecode($jwk['e']);

        $modulusDer = $this->asn1Integer($modulus);
        $exponentDer = $this->asn1Integer($exponent);
        $rsaPublicKeyDer = $this->asn1Sequence($modulusDer . $exponentDer);

        $pem = "-----BEGIN RSA PUBLIC KEY-----\n"
            . chunk_split(base64_encode($rsaPublicKeyDer), 64, "\n")
            . "-----END RSA PUBLIC KEY-----\n";

        $publicKey = openssl_pkey_get_public($pem);
        if (false === $publicKey) {
            throw new AuthenticationException('Failed to create public key from JWKS');
        }

        return $pem;
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if (false === $decoded) {
            throw new AuthenticationException('Invalid base64url value');
        }

        return $decoded;
    }

    private function jsonDecode(string $json): array
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new AuthenticationException('Invalid JSON payload in JWT');
        }

        return $data;
    }

    private function asn1Integer(string $binary): string
    {
        $binary = ltrim($binary, "\x00");
        if ($binary === '') {
            $binary = "\x00";
        }

        if ((ord($binary[0]) & 0x80) !== 0) {
            $binary = "\x00" . $binary;
        }

        return "\x02" . $this->asn1Length(strlen($binary)) . $binary;
    }

    private function asn1Sequence(string $binary): string
    {
        return "\x30" . $this->asn1Length(strlen($binary)) . $binary;
    }

    private function asn1Length(int $length): string
    {
        if ($length <= 0x7F) {
            return chr($length);
        }

        $temp = '';
        while ($length > 0) {
            $temp = chr($length & 0xFF) . $temp;
            $length >>= 8;
        }

        return chr(0x80 | strlen($temp)) . $temp;
    }
}


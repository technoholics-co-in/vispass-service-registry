<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\Security;

use Firebase\JWT\JWT;
use Ramsey\Uuid\Uuid;
use Technoholics\ServiceRegistry\Auth\Constants\ServiceTokenClaims;
use Technoholics\ServiceRegistry\Auth\Entities\SigningKey;

/**
 * Issues RS256 JWTs for internal service-to-service calls.
 */
class ServiceJwtIssuer
{
    public function __construct(
        private SigningKeyService $signingKeyService,
        private string $issuer = ServiceTokenClaims::ISSUER
    ) {
    }

    /**
     * @param list<string> $scopes
     *
     * @return array{token: string, expiresIn: int}
     */
    public function issue(
        string $serviceName,
        string $targetService,
        array $scopes,
        int $ttl,
        int $credentialVersion,
        string $tenantId
    ): array {
        $signingKey = $this->signingKeyService->getActiveSigningKey($tenantId);
        $now = time();
        $ttl = max(ServiceTokenClaims::MIN_TTL, min($ttl, ServiceTokenClaims::MAX_TTL));

        $payload = [
            'iss' => $this->issuer,
            ServiceTokenClaims::SUBJECT => $serviceName,
            ServiceTokenClaims::AUDIENCE => $targetService,
            ServiceTokenClaims::SCOPES => array_values($scopes),
            ServiceTokenClaims::TOKEN_TYPE => ServiceTokenClaims::TOKEN_TYPE_SERVICE,
            ServiceTokenClaims::CREDENTIAL_VERSION => $credentialVersion,
            ServiceTokenClaims::TENANT_ID => $tenantId,
            'iat' => $now,
            'exp' => $now + $ttl,
            'jti' => Uuid::uuid4()->toString(),
        ];

        $token = JWT::encode(
            $payload,
            $signingKey->getPrivateKey(),
            $signingKey->getAlgorithm(),
            $signingKey->getKid()
        );

        return ['token' => $token, 'expiresIn' => $ttl];
    }
}

<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\Security;

use Ramsey\Uuid\Uuid;
use Technoholics\ServiceRegistry\Auth\Constants\ServiceTokenClaims;
use Technoholics\ServiceRegistry\Auth\Entities\SigningKey;
use Technoholics\ServiceRegistry\Auth\Entities\SigningKeyFields;
use Technoholics\ServiceRegistry\Auth\Repositories\SigningKeyRepository;
use Technoholics\ServiceRegistry\Auth\Services\AuditLogService;
use Technoholics\ServiceRegistry\Shared\Tenant\TenantContextResolver;

/**
 * RS256 signing key lifecycle (generate, rotate, JWKS).
 */
class SigningKeyService
{
    public function __construct(
        private SigningKeyRepository $repository,
        private AuditLogService $auditLogService
    ) {
    }

    public function getActiveSigningKey(?string $tenantId = null): SigningKey
    {
        $tenantId = $tenantId ?? TenantContextResolver::requireTenantId();
        $key = $this->repository->findActiveKey($tenantId);
        if ($key !== null) {
            return $key;
        }

        return $this->generateAndPersistKeyPair($tenantId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getJwks(?string $tenantId = null): array
    {
        $tenantId = $tenantId ?? TenantContextResolver::requireTenantId();
        $keys = [];
        foreach ($this->repository->findAllActiveKeys($tenantId) as $signingKey) {
            $keys[] = $this->toJwk($signingKey);
        }

        if ($keys === []) {
            $keys[] = $this->toJwk($this->getActiveSigningKey($tenantId));
        }

        return $keys;
    }

    public function rotateKeys(?string $tenantId = null): SigningKey
    {
        $tenantId = $tenantId ?? TenantContextResolver::requireTenantId();
        $this->repository->deactivateAll($tenantId);
        $newKey = $this->generateAndPersistKeyPair($tenantId);
        $this->auditLogService->logSigningKeyRotated($newKey->getKid());

        return $newKey;
    }

    private function generateAndPersistKeyPair(string $tenantId): SigningKey
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($resource === false) {
            throw new \RuntimeException('Failed to generate RSA key pair');
        }

        $privateKey = '';
        openssl_pkey_export($resource, $privateKey);
        $details = openssl_pkey_get_details($resource);
        if ($details === false || !isset($details['key'])) {
            throw new \RuntimeException('Failed to export public key');
        }

        $kid = 'sr-' . substr(Uuid::uuid4()->toString(), 0, 8);

        /** @var SigningKey $entity */
        $entity = $this->repository->create([
            SigningKeyFields::TENANT_ID => $tenantId,
            SigningKeyFields::KID => $kid,
            SigningKeyFields::ALGORITHM => SigningKeyFields::ALGORITHM_RS256,
            SigningKeyFields::PUBLIC_KEY => $details['key'],
            SigningKeyFields::PRIVATE_KEY => $privateKey,
            SigningKeyFields::ACTIVE => true,
        ]);

        return $entity;
    }

    /**
     * @return array<string, mixed>
     */
    private function toJwk(SigningKey $signingKey): array
    {
        $resource = openssl_pkey_get_public($signingKey->getPublicKey());
        if ($resource === false) {
            throw new \RuntimeException('Invalid public key for JWKS');
        }

        $details = openssl_pkey_get_details($resource);
        if ($details === false || !isset($details['rsa'])) {
            throw new \RuntimeException('Failed to parse RSA public key');
        }

        return [
            'kty' => 'RSA',
            'use' => 'sig',
            'alg' => $signingKey->getAlgorithm(),
            'kid' => $signingKey->getKid(),
            'n' => rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '='),
            'e' => rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '='),
        ];
    }
}

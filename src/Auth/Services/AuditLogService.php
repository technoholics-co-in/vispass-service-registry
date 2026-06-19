<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\Services;

use Technoholics\ServiceRegistry\Auth\Constants\AuditEventType;
use Technoholics\ServiceRegistry\Auth\Entities\AuditLogFields;
use Technoholics\ServiceRegistry\Auth\Repositories\AuditLogRepository;
use Technoholics\ServiceRegistry\Shared\Tenant\TenantContextResolver;

/**
 * Append-only audit trail for registry security events.
 */
class AuditLogService
{
    public function __construct(
        private AuditLogRepository $repository
    ) {
    }

    /**
     * @param array<string, mixed> $details
     */
    public function log(
        string $eventType,
        ?string $actorService = null,
        ?string $resourceType = null,
        ?string $resourceId = null,
        array $details = []
    ): void {
        $this->repository->create([
            AuditLogFields::TENANT_ID => TenantContextResolver::requireTenantId(),
            AuditLogFields::EVENT_TYPE => $eventType,
            AuditLogFields::ACTOR_SERVICE => $actorService,
            AuditLogFields::RESOURCE_TYPE => $resourceType,
            AuditLogFields::RESOURCE_ID => $resourceId,
            AuditLogFields::DETAILS => $details,
        ]);
    }

    public function logServiceRegistered(string $serviceName): void
    {
        $this->log(AuditEventType::SERVICE_REGISTERED, $serviceName, 'service', $serviceName);
    }

    public function logTokenIssued(string $caller, string $target, array $scopes, int $ttl): void
    {
        $this->log(AuditEventType::TOKEN_ISSUED, $caller, 'service_token', $target, [
            'targetService' => $target,
            'scopes' => $scopes,
            'ttl' => $ttl,
        ]);
    }

    public function logSigningKeyRotated(string $kid): void
    {
        $this->log(AuditEventType::SIGNING_KEY_ROTATED, null, 'signing_key', $kid);
    }
}

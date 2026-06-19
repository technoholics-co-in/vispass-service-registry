# Service Registry

Centralized service identity registry for internal microservice communication.

## Quick start

```bash
cd service-registry
cp .env.example .env
composer install --ignore-platform-req=ext-mongodb
docker-compose up -d
php bin/generate-signing-key.php
```

API: `http://localhost:8080`

## Phase 1 — Registration & discovery

- `POST /services` — register a service
- `GET /services/{name}` — discover service metadata
- `GET /health` — database and Redis health

## Phase 2 — Scopes, trust, credentials

- `POST /services/{service}/scopes` — assign explicit scope
- `GET /services/{service}/scopes` — list scopes
- `DELETE /services/{service}/scopes/{scope}` — remove scope
- `POST /trust-rules` — create caller → target trust rule
- `GET /trust-rules` — list trust rules
- `DELETE /trust-rules/{id}` — delete trust rule
- `php bin/seed-service-credential.php <service> <secret>` — dev credential seed

## Phase 3 — Service JWT auth

- `POST /auth/token` — issue short-lived RS256 service token (Bearer secret)
- `GET /.well-known/jwks.json` — JWKS for token verification

### Token request example

```bash
curl -X POST http://localhost:8080/auth/token \
  -H "Authorization: Bearer dev-secret" \
  -H "Content-Type: application/json" \
  -d '{
    "service": "document-service",
    "targetService": "storage-service",
    "requestedScopes": ["storage.upload"]
  }'
```

## Phase 4 — RS256 rotation & audit

- `php bin/generate-signing-key.php` — ensure active signing key
- `php bin/rotate-signing-key.php` — rotate RS256 keys (audit logged)
- `audit_logs` table records registration, scope, trust, and token events

## Phase 5 — mTLS & consumer middleware

- Set `MTLS_REQUIRED=true` when behind a mesh/ingress that sets `X-Forwarded-Client-Cert-Verify: SUCCESS`
- Consumer services use `Technoholics\Core\SharedContracts\Auth\InternalServiceAuthMiddleware`
- Optional `MtlsClientVerifyMiddleware` on consuming services

```php
use Technoholics\Core\SharedContracts\Auth\InternalServiceAuthMiddleware;
use Technoholics\Core\SharedContracts\Auth\ServiceTokenValidator;

$validator = new ServiceTokenValidator(['base_url' => 'http://service-registry:8080']);
$app->get('/internal/files')->add(new InternalServiceAuthMiddleware($validator, ['storage.read']));
```

## Client library

`Technoholics\Core\SharedContracts\Clients\ServiceRegistry\ServiceRegistryClient`

## Tests

```bash
composer test
```

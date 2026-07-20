# Deployment - service-registry

Central service identity registry for internal microservice communication: service registration,
scopes, trust rules, caller credentials, RS256 JWT issuance, JWKS, and audit logging.

**Deploy this service first.** All S2S-enabled microservices depend on it before they can mint or
verify service JWTs.

**Docker container (dev monorepo compose):** `php-service-registry`  
**Standalone compose:** `service-registry-app` (see `service-registry/docker-compose.yml`)  
**Database:** PostgreSQL (`service_registry`)  
**Cache:** Redis  

---

## Deploy order

| Order | Service | Why |
|-------|---------|-----|
| **0 (first)** | **service-registry** | Issues service JWTs and stores signing keys |
| 1+ | user-service, location, lookup, ... | Require registry for S2S auth |

Full monorepo order: [disaster-recovery-runbook.md](../../docs/disaster-recovery-runbook.md) section 7.2.

---

## Prerequisites

- PostgreSQL reachable (`service_registry` database)
- Redis reachable (health check + cache)
- Composer and PHP 8.1+
- For monorepo dev: shared `docker/docker-compose_dev.yml` stack (Kong Postgres or dedicated DB)

---

## Pre-deploy checklist

- [ ] `composer test` passes on the release commit
- [ ] Postgres `service_registry` database exists
- [ ] Migrations 001-004 applied (see below)
- [ ] Redis reachable from the app container
- [ ] Signing key generated (`php bin/generate-signing-key.php`)
- [ ] S2S seed scripts run for tenant(s) before dependent services start
- [ ] After SQL restore: `audit_logs_id_seq` and `signing_keys_id_seq` synced

---

## Local deployment (standalone)

From the service folder:

```bash
cd service-registry
cp .env.example .env
composer install --ignore-platform-req=ext-mongodb
docker compose up -d
php bin/generate-signing-key.php
```

API: `http://localhost:8080`

Standalone compose starts:

- `service-registry-app` on port **8080**
- `service-registry-postgres` on port **5433**
- `service-registry-redis` on port **6380**

Migrations 001-003 run automatically on first Postgres boot via `docker-entrypoint-initdb.d`.
Apply **004** manually on existing databases:

```bash
docker exec -i service-registry-postgres psql -U kong -d service_registry \
  < migrations/004_add_tenant_id.sql
```

---

## Local deployment (monorepo dev stack)

From monorepo root:

```bash
cd docker
docker compose -f docker-compose_dev.yml up -d php-service-registry
```

Inside the container:

```bash
docker exec -it service-registry bash
cd /var/www/html
composer install
php bin/generate-signing-key.php
```

Apply migrations on the shared Kong Postgres if not already present:

```bash
docker exec -i kong_postgres psql -U kong -d service_registry \
  < service-registry/migrations/004_add_tenant_id.sql
```

Restart after env changes:

```bash
docker compose -f docker-compose_dev.yml restart php-service-registry
```

---

## Stage / production deployment

1. Promote release to target branch (`main` or `stage`) and run `composer test`.
2. Pull on the target host.
3. `composer install --no-dev --optimize-autoloader`
4. Ensure Postgres + Redis env vars are set (see below).
5. Apply any pending SQL migrations.
6. Run `php bin/generate-signing-key.php` (creates active RS256 key if missing).
7. Run S2S seed scripts for the tenant(s) you deploy.
8. Restart PHP-FPM / container.
9. Run post-deploy verification.

There is **no Kong public route** for service-registry. Other services call it directly on the
internal network (`http://service-registry:80` or host-specific URL).

---

## Environment variables

| Variable | Purpose |
|----------|---------|
| `DB_HOST` | Postgres host |
| `DB_PORT` | Postgres port (default 5432) |
| `DB_NAME` | `service_registry` |
| `DB_USER` / `DB_PASSWORD` | Postgres credentials |
| `REDIS_HOST` / `REDIS_PORT` | Redis for cache |
| `REDIS_PREFIX` | Cache key prefix |
| `SERVICE_CACHE_TTL` | Registry metadata cache TTL (seconds) |
| `MTLS_REQUIRED` | `true` when behind mesh/ingress with verified client certs |

See [`.env.example`](../.env.example).

---

## Database and migrations

| File | Purpose |
|------|---------|
| `001_create_registry_tables.sql` | Services, scopes, trust rules |
| `002_create_service_credentials.sql` | Caller shared secrets (bcrypt) |
| `003_signing_keys_and_audit.sql` | RS256 signing keys + audit_logs |
| `004_add_tenant_id.sql` | Tenant-scoped keys and credentials |

On a **restored** database, sync sequences before issuing new tokens or audit rows:

```sql
SELECT setval('audit_logs_id_seq', COALESCE((SELECT MAX(id) FROM audit_logs), 1), true);
SELECT setval('signing_keys_id_seq', COALESCE((SELECT MAX(id) FROM signing_keys), 1), true);
```

Or run `backup/Backup/drop_visa_and_restore_all.sh` (closes connections, restores SQL, runs setval).

Verify after restore:

```sql
SELECT MAX(id) FROM audit_logs;
SELECT last_value FROM audit_logs_id_seq;
SELECT MAX(id) FROM signing_keys;
SELECT last_value FROM signing_keys_id_seq;
```

---

## Signing keys

Generate the first active RS256 key (one active key per tenant; rotation adds history rows):

```bash
php bin/generate-signing-key.php
```

Rotate keys (deactivates old, creates new, audit logged):

```bash
php bin/rotate-signing-key.php
```

JWKS endpoint for consumers:

```bash
curl -s http://localhost:8080/.well-known/jwks.json
```

---

## S2S bootstrap (seed scripts)

Run **after** service-registry is up and **before** dependent services rely on S2S tokens.

### Callers to user-service

Registers services, scopes, trust rules, and prints caller secrets:

```bash
php bin/seed-user-service-s2s.php --tenant-id=<tenant-uuid>
```

Rotate one caller secret:

```bash
php bin/seed-user-service-s2s.php \
  --tenant-id=<tenant-uuid> \
  --callers=document-service \
  --rotate-credentials
```

Common callers: `document-service`, `bff-services`, `upload-service`, `email-service`,
`ingestion-service`, `person-service`, `report-service`, `vac-services`, `visa-services`.

Copy printed secrets into each service's compose env (e.g. `DOCUMENT_SERVICE_REGISTRY_SECRET`).

### Callers to vac-services

```bash
php bin/seed-vac-service-s2s.php --tenant-id=<tenant-uuid>
php bin/seed-vac-service-s2s.php --tenant-id=<tenant-uuid> --callers=visa-services --rotate-credentials
```

### Callers to location-service / lookup-service

Example: allow vac-services to call internal location/lookup APIs:

```bash
php bin/seed-location-service-s2s.php --tenant-id=<tenant-uuid> --callers=vac-services --skip-credentials
php bin/seed-lookup-service-s2s.php --tenant-id=<tenant-uuid> --callers=vac-services --skip-credentials
```

### Version service

```bash
php bin/seed-version-service-s2s.php --tenant-id=<tenant-uuid>
```

Per-service wiring details: see each consumer's [docs/service-registry.md](../../user-service/docs/service-registry.md) (linked from [README.md](README.md) consumer table).

---

## Post-deploy verification

| Check | Command | Expected |
|-------|---------|----------|
| Health | `curl -s http://localhost:8080/health` | HTTP 200, DB + Redis ok |
| JWKS | `curl -s http://localhost:8080/.well-known/jwks.json` | JSON with `keys` array |
| Signing key row | `SELECT COUNT(*) FROM signing_keys WHERE active = true;` | >= 1 per tenant |
| Token issue | `POST /auth/token` with caller Bearer secret + `x-tenant-id` | 200 + JWT |
| Sequence sync | Compare MAX(id) vs last_value (see above) | Equal after restore |

Token request example:

```bash
curl -X POST http://localhost:8080/auth/token \
  -H "Authorization: Bearer <caller-secret>" \
  -H "x-tenant-id: <tenant-uuid>" \
  -H "Content-Type: application/json" \
  -d '{
    "service": "document-service",
    "targetService": "user-service",
    "requestedScopes": ["user.read"]
  }'
```

---

## Kong gateway

No public Kong route. Internal services resolve `SERVICE_REGISTRY_URL` (e.g.
`http://service-registry:80` in Docker).

---

## Rollback

1. Redeploy previous stable commit.
2. `composer install --no-dev --optimize-autoloader`
3. Restart container.
4. Do **not** delete `signing_keys` or `audit_logs` casually; rotate or restore from backup.
5. Re-run seed scripts with `--rotate-credentials` if caller secrets were lost.

---

## Related

- [service-registry README](README.md) - API phases and consumer service table
- Monorepo deploy order: [disaster-recovery-runbook.md](../../docs/disaster-recovery-runbook.md) section 7.2
- Consumer S2S docs: each service's `docs/service-registry.md`

# Deployment - service-registry

Central service identity registry: S2S JWT issuance, JWKS, scopes, trust rules, and audit logging.

**Docker container (dev compose):** `php-service-registry`
**Database:** PostgreSQL (service_registry)
**Tenant DB routing:** No

Deploy **before** all S2S-enabled services (prerequisite, not listed in section 7.2).

---

## Prerequisites

- Infrastructure reachable for this service (PostgreSQL, MongoDB, Redis, RabbitMQ, S3 as needed)
- Kong gateway configured for public routes
- `service-registry` running when this service uses S2S
- Shared `TENANT_DB_ENCRYPTION_KEY` unchanged when restoring tenant catalog DB

---

## Pre-deploy checklist

- [ ] `composer test` passes on the release commit
- [ ] Environment variables updated on target host (see below)
- [ ] Database backup taken (if production/stage)
- [ ] Migrations reviewed for the release
- [ ] Dependent upstream services healthy

---

## Local / Docker deployment

From monorepo root:

```bash
cd docker
docker compose -f docker-compose_dev.yml up -d php-service-registry
```

Inside the container or service folder:

```bash
cd service-registry
composer install
cp .env.example .env   # when present; set secrets via compose env
# SQL migrations applied via migrations/*.sql
```

Restart after env changes:

```bash
docker compose -f docker-compose_dev.yml restart php-service-registry
```

## Stage deployment

1. Promote release to the stage branch (merge from main, run tests).
2. Pull origin/stage on the stage host.
3. composer install --no-dev --optimize-autoloader
4. Apply migrations and start workers per sections below.
5. Restart the container or PHP-FPM pool.
6. Run post-deploy verification.

---

## Environment variables

| Variable | Purpose |
|----------|---------|
| DB_* | Registry Postgres |
| REDIS_HOST | Cache |

See `.env.example` when present in the service folder.

---

## Database and migrations

Apply migrations/*.sql on fresh Postgres (docker init) or manually on existing DB.

```bash
# SQL migrations applied via migrations/*.sql
```

## Service registry bootstrap

Seed caller credentials before deploying dependent services.

After restoring service_registry from SQL dump, sync sequences:

`sql
SELECT setval('audit_logs_id_seq', COALESCE((SELECT MAX(id) FROM audit_logs), 1), true);
SELECT setval('signing_keys_id_seq', COALESCE((SELECT MAX(id) FROM signing_keys), 1), true);
`

Or run ackup/Backup/drop_visa_and_restore_all.sh.

---

## Kong gateway

Forward `x-tenant-id` and `x-user-id` from Kong for tenant-scoped APIs.

No public Kong route (internal only).

---

## Workers and background jobs

None. CLI: php bin/generate-signing-key.php, seed scripts.

---

## Post-deploy verification

curl -s http://localhost:8080/health returns HTTP 200.

---

## Rollback

1. Redeploy the previous stable commit on the target branch (`stage` or `main`).
2. `composer install --no-dev --optimize-autoloader`
3. Restart container / PHP-FPM and workers.
4. Do **not** roll back applied migrations unless a DBA approves; restore DB from backup if needed.

---

## Related


- Monorepo deploy order: [disaster-recovery-runbook.md](../../docs/disaster-recovery-runbook.md) section 7.2
- [service-registry README](README.md)

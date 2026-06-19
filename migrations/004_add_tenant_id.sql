-- Migration: Tenant-scoped registry data

ALTER TABLE services ADD COLUMN IF NOT EXISTS tenant_id UUID;

UPDATE services SET tenant_id = '00000000-0000-0000-0000-000000000000' WHERE tenant_id IS NULL;

ALTER TABLE services ALTER COLUMN tenant_id SET NOT NULL;

ALTER TABLE services DROP CONSTRAINT IF EXISTS uq_services_name;

ALTER TABLE services ADD CONSTRAINT uq_services_tenant_name UNIQUE (tenant_id, name);

CREATE INDEX IF NOT EXISTS idx_services_tenant_id ON services (tenant_id);

ALTER TABLE signing_keys ADD COLUMN IF NOT EXISTS tenant_id UUID;

UPDATE signing_keys SET tenant_id = '00000000-0000-0000-0000-000000000000' WHERE tenant_id IS NULL;

ALTER TABLE signing_keys ALTER COLUMN tenant_id SET NOT NULL;

ALTER TABLE signing_keys DROP CONSTRAINT IF EXISTS uq_signing_keys_kid;

ALTER TABLE signing_keys ADD CONSTRAINT uq_signing_keys_tenant_kid UNIQUE (tenant_id, kid);

CREATE INDEX IF NOT EXISTS idx_signing_keys_tenant_id ON signing_keys (tenant_id);

ALTER TABLE audit_logs ADD COLUMN IF NOT EXISTS tenant_id UUID;

UPDATE audit_logs SET tenant_id = '00000000-0000-0000-0000-000000000000' WHERE tenant_id IS NULL;

ALTER TABLE audit_logs ALTER COLUMN tenant_id SET NOT NULL;

CREATE INDEX IF NOT EXISTS idx_audit_logs_tenant_id ON audit_logs (tenant_id);

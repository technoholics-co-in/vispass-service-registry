-- Migration: Service credentials (Phase 2 — table + seed CLI; token API in Phase 3)

CREATE TABLE IF NOT EXISTS service_credentials (
    id SERIAL PRIMARY KEY,
    service_id UUID NOT NULL REFERENCES services (id) ON DELETE CASCADE,
    auth_type VARCHAR(50) NOT NULL DEFAULT 'shared_secret',
    secret_hash VARCHAR(255) NOT NULL,
    rotation_version INTEGER NOT NULL DEFAULT 1,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_service_credentials_service_version UNIQUE (service_id, rotation_version)
);

CREATE INDEX IF NOT EXISTS idx_service_credentials_service_id ON service_credentials (service_id);
CREATE INDEX IF NOT EXISTS idx_service_credentials_active ON service_credentials (active);

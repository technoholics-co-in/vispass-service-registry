-- Migration: Service Registry core tables (Phase 1 schema, Phase 2 APIs)

CREATE TABLE IF NOT EXISTS services (
    id UUID PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_services_name UNIQUE (name)
);

CREATE INDEX IF NOT EXISTS idx_services_status ON services (status);

CREATE TABLE IF NOT EXISTS service_scopes (
    id SERIAL PRIMARY KEY,
    service_id UUID NOT NULL REFERENCES services (id) ON DELETE CASCADE,
    scope VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_service_scopes_service_scope UNIQUE (service_id, scope)
);

CREATE INDEX IF NOT EXISTS idx_service_scopes_service_id ON service_scopes (service_id);

CREATE TABLE IF NOT EXISTS service_trust_rules (
    id SERIAL PRIMARY KEY,
    caller_service_id UUID NOT NULL REFERENCES services (id) ON DELETE CASCADE,
    target_service_id UUID NOT NULL REFERENCES services (id) ON DELETE CASCADE,
    allowed_scopes JSONB NOT NULL DEFAULT '[]',
    max_ttl INTEGER NOT NULL DEFAULT 900,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_service_trust_rules_caller_target UNIQUE (caller_service_id, target_service_id)
);

CREATE INDEX IF NOT EXISTS idx_service_trust_rules_caller ON service_trust_rules (caller_service_id);
CREATE INDEX IF NOT EXISTS idx_service_trust_rules_target ON service_trust_rules (target_service_id);

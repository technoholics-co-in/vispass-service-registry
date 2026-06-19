-- Migration: JWT signing keys (RS256) and audit log (Phase 3–4)

CREATE TABLE IF NOT EXISTS signing_keys (
    id SERIAL PRIMARY KEY,
    kid VARCHAR(64) NOT NULL,
    algorithm VARCHAR(10) NOT NULL DEFAULT 'RS256',
    public_key TEXT NOT NULL,
    private_key TEXT NOT NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    rotated_at TIMESTAMP NULL,
    CONSTRAINT uq_signing_keys_kid UNIQUE (kid)
);

CREATE INDEX IF NOT EXISTS idx_signing_keys_active ON signing_keys (active);

CREATE TABLE IF NOT EXISTS audit_logs (
    id SERIAL PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    actor_service VARCHAR(100) NULL,
    resource_type VARCHAR(50) NULL,
    resource_id VARCHAR(100) NULL,
    details JSONB NOT NULL DEFAULT '{}',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_audit_logs_event_type ON audit_logs (event_type);
CREATE INDEX IF NOT EXISTS idx_audit_logs_actor ON audit_logs (actor_service);
CREATE INDEX IF NOT EXISTS idx_audit_logs_created_at ON audit_logs (created_at);

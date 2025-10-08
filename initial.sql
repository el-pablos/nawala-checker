-- Nawala Checker Database Schema for Supabase PostgreSQL
-- Generated for Laravel 11 Application
-- Run this in Supabase SQL Editor

-- Enable UUID extension (if not already enabled)
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Create users table (if not exists from Laravel Breeze/Jetstream)
CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Tags table
CREATE TABLE nc_tags (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    color VARCHAR(7) DEFAULT '#3B82F6' NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE INDEX nc_tags_slug_index ON nc_tags(slug);

-- Groups table
CREATE TABLE nc_groups (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    check_interval INTEGER DEFAULT 300 NOT NULL,
    jitter_percent INTEGER DEFAULT 15 NOT NULL,
    notifications_enabled BOOLEAN DEFAULT true NOT NULL,
    created_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE INDEX nc_groups_slug_index ON nc_groups(slug);
CREATE INDEX nc_groups_created_by_index ON nc_groups(created_by);

-- Resolvers table (DNS servers)
CREATE TABLE nc_resolvers (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(255) DEFAULT 'dns' NOT NULL,
    address VARCHAR(255) NOT NULL,
    port INTEGER NULL,
    is_active BOOLEAN DEFAULT true NOT NULL,
    priority INTEGER DEFAULT 100 NOT NULL,
    weight INTEGER DEFAULT 100 NOT NULL,
    metadata JSONB NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE INDEX nc_resolvers_is_active_priority_index ON nc_resolvers(is_active, priority);

-- Vantage Nodes table (optional multi-location checking)
CREATE TABLE nc_vantage_nodes (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    endpoint_url VARCHAR(255) NOT NULL,
    api_key VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT true NOT NULL,
    weight INTEGER DEFAULT 100 NOT NULL,
    metadata JSONB NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE INDEX nc_vantage_nodes_is_active_index ON nc_vantage_nodes(is_active);

-- Targets table (domains/URLs to monitor)
CREATE TABLE nc_targets (
    id BIGSERIAL PRIMARY KEY,
    domain_or_url VARCHAR(255) NOT NULL,
    type VARCHAR(255) DEFAULT 'domain' NOT NULL,
    group_id BIGINT NULL REFERENCES nc_groups(id) ON DELETE SET NULL,
    owner_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    enabled BOOLEAN DEFAULT true NOT NULL,
    check_interval INTEGER NULL,
    current_status VARCHAR(255) DEFAULT 'UNKNOWN' NOT NULL,
    last_checked_at TIMESTAMP NULL,
    last_status_change_at TIMESTAMP NULL,
    consecutive_failures INTEGER DEFAULT 0 NOT NULL,
    notes TEXT NULL,
    metadata JSONB NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL
);

CREATE UNIQUE INDEX nc_targets_domain_or_url_owner_id_unique ON nc_targets(domain_or_url, owner_id);
CREATE INDEX nc_targets_owner_id_enabled_index ON nc_targets(owner_id, enabled);
CREATE INDEX nc_targets_group_id_enabled_index ON nc_targets(group_id, enabled);
CREATE INDEX nc_targets_current_status_index ON nc_targets(current_status);
CREATE INDEX nc_targets_last_checked_at_index ON nc_targets(last_checked_at);

-- Target Tags pivot table
CREATE TABLE nc_target_tag (
    target_id BIGINT NOT NULL REFERENCES nc_targets(id) ON DELETE CASCADE,
    tag_id BIGINT NOT NULL REFERENCES nc_tags(id) ON DELETE CASCADE,
    PRIMARY KEY (target_id, tag_id)
);

-- Check Results table
CREATE TABLE nc_check_results (
    id BIGSERIAL PRIMARY KEY,
    target_id BIGINT NOT NULL REFERENCES nc_targets(id) ON DELETE CASCADE,
    resolver_id BIGINT NULL REFERENCES nc_resolvers(id) ON DELETE SET NULL,
    vantage_node_id BIGINT NULL REFERENCES nc_vantage_nodes(id) ON DELETE SET NULL,
    status VARCHAR(255) NOT NULL,
    response_time_ms INTEGER NULL,
    resolved_ip VARCHAR(255) NULL,
    http_status_code INTEGER NULL,
    error_message TEXT NULL,
    raw_response JSONB NULL,
    confidence INTEGER DEFAULT 100 NOT NULL,
    checked_at TIMESTAMP NOT NULL
);

CREATE INDEX nc_check_results_target_id_checked_at_index ON nc_check_results(target_id, checked_at);
CREATE INDEX nc_check_results_target_id_status_index ON nc_check_results(target_id, status);
CREATE INDEX nc_check_results_checked_at_index ON nc_check_results(checked_at);

-- Shortlink Groups table
CREATE TABLE nc_shortlink_groups (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    rotation_threshold INTEGER DEFAULT 3 NOT NULL,
    cooldown_seconds INTEGER DEFAULT 300 NOT NULL,
    min_confidence INTEGER DEFAULT 80 NOT NULL,
    auto_rollback BOOLEAN DEFAULT true NOT NULL,
    created_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE INDEX nc_shortlink_groups_slug_index ON nc_shortlink_groups(slug);

-- Shortlinks table
CREATE TABLE nc_shortlinks (
    id BIGSERIAL PRIMARY KEY,
    slug VARCHAR(255) UNIQUE NOT NULL,
    group_id BIGINT NULL REFERENCES nc_shortlink_groups(id) ON DELETE SET NULL,
    current_target_id BIGINT NULL,
    original_target_id BIGINT NULL,
    is_active BOOLEAN DEFAULT true NOT NULL,
    last_rotated_at TIMESTAMP NULL,
    rotation_count INTEGER DEFAULT 0 NOT NULL,
    created_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    metadata JSONB NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE INDEX nc_shortlinks_slug_index ON nc_shortlinks(slug);
CREATE INDEX nc_shortlinks_group_id_is_active_index ON nc_shortlinks(group_id, is_active);

-- Shortlink Targets table (candidate destinations)
CREATE TABLE nc_shortlink_targets (
    id BIGSERIAL PRIMARY KEY,
    shortlink_id BIGINT NOT NULL REFERENCES nc_shortlinks(id) ON DELETE CASCADE,
    url VARCHAR(255) NOT NULL,
    priority INTEGER DEFAULT 100 NOT NULL,
    weight INTEGER DEFAULT 100 NOT NULL,
    is_active BOOLEAN DEFAULT true NOT NULL,
    current_status VARCHAR(255) DEFAULT 'UNKNOWN' NOT NULL,
    last_checked_at TIMESTAMP NULL,
    metadata JSONB NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE INDEX nc_shortlink_targets_shortlink_id_is_active_priority_index ON nc_shortlink_targets(shortlink_id, is_active, priority);
CREATE INDEX nc_shortlink_targets_current_status_index ON nc_shortlink_targets(current_status);

-- Add foreign key constraints for shortlinks (after nc_shortlink_targets exists)
ALTER TABLE nc_shortlinks 
    ADD CONSTRAINT nc_shortlinks_current_target_id_foreign 
    FOREIGN KEY (current_target_id) REFERENCES nc_shortlink_targets(id) ON DELETE SET NULL;

ALTER TABLE nc_shortlinks 
    ADD CONSTRAINT nc_shortlinks_original_target_id_foreign 
    FOREIGN KEY (original_target_id) REFERENCES nc_shortlink_targets(id) ON DELETE SET NULL;

-- Notification Channels table (Telegram)
CREATE TABLE nc_notification_channels (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(255) DEFAULT 'telegram' NOT NULL,
    chat_id VARCHAR(255) NOT NULL,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE CASCADE,
    group_id BIGINT NULL REFERENCES nc_groups(id) ON DELETE SET NULL,
    is_active BOOLEAN DEFAULT true NOT NULL,
    notify_on_block BOOLEAN DEFAULT true NOT NULL,
    notify_on_recover BOOLEAN DEFAULT true NOT NULL,
    notify_on_rotation BOOLEAN DEFAULT true NOT NULL,
    metadata JSONB NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE INDEX nc_notification_channels_user_id_is_active_index ON nc_notification_channels(user_id, is_active);
CREATE INDEX nc_notification_channels_group_id_is_active_index ON nc_notification_channels(group_id, is_active);

-- Rotation History table
CREATE TABLE nc_rotation_history (
    id BIGSERIAL PRIMARY KEY,
    shortlink_id BIGINT NOT NULL REFERENCES nc_shortlinks(id) ON DELETE CASCADE,
    from_target_id BIGINT NULL REFERENCES nc_shortlink_targets(id) ON DELETE SET NULL,
    to_target_id BIGINT NULL REFERENCES nc_shortlink_targets(id) ON DELETE SET NULL,
    reason VARCHAR(255) NOT NULL,
    notes TEXT NULL,
    triggered_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    rotated_at TIMESTAMP NOT NULL
);

CREATE INDEX nc_rotation_history_shortlink_id_rotated_at_index ON nc_rotation_history(shortlink_id, rotated_at);

-- Insert default data
-- Default admin user (password: 'password' hashed with bcrypt)
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Admin', 'admin@nawalachecker.local', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW())
ON CONFLICT (email) DO NOTHING;

-- Default tags
INSERT INTO nc_tags (name, slug, description, color, created_at, updated_at) VALUES
('Social Media', 'social-media', 'Social media platforms', '#3B82F6', NOW(), NOW()),
('Streaming', 'streaming', 'Video/audio streaming services', '#8B5CF6', NOW(), NOW()),
('News', 'news', 'News websites', '#EF4444', NOW(), NOW()),
('E-Commerce', 'e-commerce', 'Online shopping platforms', '#10B981', NOW(), NOW()),
('Critical', 'critical', 'Critical infrastructure', '#F59E0B', NOW(), NOW())
ON CONFLICT (slug) DO NOTHING;

-- Default resolvers
INSERT INTO nc_resolvers (name, type, address, port, is_active, priority, weight, created_at, updated_at) VALUES
('Nawala DNS Primary', 'dns', '180.131.144.144', 53, true, 1, 100, NOW(), NOW()),
('Nawala DNS Secondary', 'dns', '180.131.145.145', 53, true, 2, 100, NOW(), NOW()),
('Google DNS Primary', 'dns', '8.8.8.8', 53, true, 10, 100, NOW(), NOW()),
('Google DNS Secondary', 'dns', '8.8.4.4', 53, true, 11, 100, NOW(), NOW()),
('Cloudflare DNS Primary', 'dns', '1.1.1.1', 53, true, 20, 100, NOW(), NOW()),
('Cloudflare DNS Secondary', 'dns', '1.0.0.1', 53, true, 21, 100, NOW(), NOW()),
('Quad9 DNS', 'dns', '9.9.9.9', 53, true, 30, 100, NOW(), NOW()),
('Cloudflare DoH', 'doh', 'https://cloudflare-dns.com/dns-query', NULL, true, 40, 100, NOW(), NOW()),
('Google DoH', 'doh', 'https://dns.google/dns-query', NULL, true, 41, 100, NOW(), NOW());

-- Default group
INSERT INTO nc_groups (name, slug, description, check_interval, jitter_percent, notifications_enabled, created_at, updated_at) VALUES
('Default Group', 'default', 'Default monitoring group', 300, 15, true, NOW(), NOW())
ON CONFLICT (slug) DO NOTHING;

-- Default shortlink group
INSERT INTO nc_shortlink_groups (name, slug, description, rotation_threshold, cooldown_seconds, min_confidence, auto_rollback, created_at, updated_at) VALUES
('Default Shortlink Group', 'default', 'Default shortlink rotation group', 3, 300, 80, true, NOW(), NOW())
ON CONFLICT (slug) DO NOTHING;

-- Success message
DO $$
BEGIN
    RAISE NOTICE 'Nawala Checker database schema created successfully!';
    RAISE NOTICE 'Default admin user: admin@nawalachecker.local / password';
    RAISE NOTICE 'Total tables created: 12';
END $$;


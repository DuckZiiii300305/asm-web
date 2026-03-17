-- Migration: Create assets table
-- Version: 001
-- Description: Initial schema for asset management system
USE asm_web;
CREATE TABLE IF NOT EXISTS assets (

    -- Primary key: UUID stored as CHAR(36)
    -- Example: 550e8400-e29b-41d4-a716-446655440000
    id CHAR(36) PRIMARY KEY,

    -- Asset identification
    name VARCHAR(255) NOT NULL,

    -- Asset classification
    type VARCHAR(50) NOT NULL,

    -- Asset status
    status VARCHAR(50) NOT NULL,

    -- Audit timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP 
        ON UPDATE CURRENT_TIMESTAMP,

    -- Constraints
    CONSTRAINT chk_assets_status 
        CHECK (status IN ('active','inactive')),

    CONSTRAINT chk_assets_type 
        CHECK (type IN ('domain','ip','service'))

);

-- Indexes for performance optimization

-- Index for filtering by type
CREATE INDEX idx_assets_type ON assets(type);

-- Index for filtering by status
CREATE INDEX idx_assets_status ON assets(status);

-- Index for searching by name
CREATE INDEX idx_assets_name ON assets(name);

-- Index for sorting by creation date
CREATE INDEX idx_assets_created_at ON assets(created_at);

-- Optional composite index
-- CREATE INDEX idx_assets_type_status ON assets(type, status);
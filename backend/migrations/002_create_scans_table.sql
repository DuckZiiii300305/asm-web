USE asm_web;
CREATE TABLE scan_jobs (
    id VARCHAR(36) PRIMARY KEY,
    asset_id VARCHAR(36),
    scan_type VARCHAR(50),
    status VARCHAR(50),
    results INT DEFAULT 0,
    error TEXT,
    started_at DATETIME NULL,
    ended_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE subdomains (
    id VARCHAR(36) PRIMARY KEY,
    asset_id VARCHAR(36),
    scan_job_id VARCHAR(36),
    name VARCHAR(255),
    source VARCHAR(50),
    is_active BOOLEAN
);

CREATE TABLE whois_records (
    id VARCHAR(36) PRIMARY KEY,
    asset_id VARCHAR(36),
    scan_job_id VARCHAR(36),
    registrar VARCHAR(255),
    created_date VARCHAR(100),
    expiry_date VARCHAR(100),
    raw_data TEXT
);

CREATE TABLE dns_records (
    id VARCHAR(36) PRIMARY KEY,
    asset_id VARCHAR(36),
    scan_job_id VARCHAR(36),
    record_type VARCHAR(20),
    name VARCHAR(255),
    value VARCHAR(1024),
    ttl INT
);

CREATE TABLE ip_scan_results (
    id VARCHAR(36) PRIMARY KEY,
    scan_job_id VARCHAR(36),
    asset_id VARCHAR(36),
    ip_address VARCHAR(45),
    geolocation TEXT,
    asn TEXT,
    reverse_dns VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE port_scan_results (
    id VARCHAR(36) PRIMARY KEY,
    scan_job_id VARCHAR(36),
    asset_id VARCHAR(36),
    ip_address VARCHAR(45),
    open_ports TEXT,
    closed_ports INT,
    total_scanned INT,
    scan_duration_ms INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ssl_scan_results (
    id VARCHAR(36) PRIMARY KEY,
    scan_job_id VARCHAR(36),
    asset_id VARCHAR(36),
    domain VARCHAR(255),
    certificate TEXT,
    connection TEXT,
    grade VARCHAR(10),
    issues TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tech_scan_results (
    id VARCHAR(36) PRIMARY KEY,
    scan_job_id VARCHAR(36),
    asset_id VARCHAR(36),
    domain VARCHAR(255),
    technologies TEXT,
    headers TEXT,
    meta_tags TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE cert_trans_results (
    id VARCHAR(36) PRIMARY KEY,
    scan_job_id VARCHAR(36),
    asset_id VARCHAR(36),
    domain VARCHAR(255),
    certificate TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
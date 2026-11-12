CREATE DATABASE IF NOT EXISTS blocksafe
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE blocksafe;

CREATE TABLE documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_code VARCHAR(50) NOT NULL UNIQUE,
    document_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(150) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    file_hash CHAR(64) NOT NULL,
    blockchain_network VARCHAR(50) DEFAULT 'sepolia',
    transaction_hash VARCHAR(100) DEFAULT NULL,
    blockchain_status ENUM(
        'PENDING',
        'CONFIRMED',
        'FAILED'
    ) DEFAULT 'PENDING',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_file_hash (file_hash),
    INDEX idx_created_at (created_at)
);

CREATE TABLE verification_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id BIGINT UNSIGNED NOT NULL,
    original_hash CHAR(64) NOT NULL,
    checked_hash CHAR(64) NOT NULL,
    verification_status ENUM(
        'VALID',
        'INVALID',
        'MISSING'
    ) NOT NULL,

    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_verification_document
    FOREIGN KEY (document_id)
    REFERENCES documents(id)
    ON DELETE RESTRICT
);
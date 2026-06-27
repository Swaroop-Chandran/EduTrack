-- migration_token.sql
ALTER TABLE users ADD COLUMN activation_token VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN token_expires_at TIMESTAMP NULL;

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    event_type VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

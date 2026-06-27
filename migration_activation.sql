-- migration_activation.sql
ALTER TABLE users ADD COLUMN activation_status VARCHAR(50) DEFAULT 'inactive';
ALTER TABLE users ADD COLUMN activated_at TIMESTAMP NULL;

-- Update existing active users to active
UPDATE users SET activation_status = 'active' WHERE status = 'active';

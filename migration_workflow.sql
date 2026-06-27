-- migration_workflow.sql
-- 1. Alter users table to support password change tracker
ALTER TABLE users ADD COLUMN password_changed TINYINT(1) DEFAULT 0;
-- Set existing seed accounts as password changed so they don't get forced to change password immediately
UPDATE users SET password_changed = 1 WHERE email IN ('admin@edutrack.com', 'student@edutrack.com', 'teacher@edutrack.com');

-- 2. Alter student_profiles to support admission_no, programme, section, personal_email, and archive flag
ALTER TABLE student_profiles
ADD COLUMN admission_no VARCHAR(50) NULL UNIQUE,
ADD COLUMN programme VARCHAR(100) NULL,
ADD COLUMN section VARCHAR(50) NULL,
ADD COLUMN personal_email VARCHAR(255) NULL,
ADD COLUMN is_archived TINYINT(1) DEFAULT 0;

-- Populate admission_no with roll_no for existing students
UPDATE student_profiles SET admission_no = roll_no;
-- Populate default programme and section for existing students
UPDATE student_profiles SET programme = 'B.Tech', section = 'A';

-- 3. Alter teacher_profiles to support experience and personal_email
ALTER TABLE teacher_profiles
ADD COLUMN experience TEXT NULL,
ADD COLUMN personal_email VARCHAR(255) NULL;

-- 4. Create teacher_documents table
CREATE TABLE IF NOT EXISTS teacher_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_profile_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL, -- 'qualification_certificate', 'experience_certificate', 'other'
    file_path VARCHAR(512) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_teacher_document_profile FOREIGN KEY (teacher_profile_id) REFERENCES teacher_profiles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_profile_doc_type (teacher_profile_id, document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

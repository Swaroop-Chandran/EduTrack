-- migration_student_profile.sql
-- Database migration script to extend the Student Profile schema

-- 1. Add fields to student_profiles table
ALTER TABLE student_profiles
ADD COLUMN gender VARCHAR(20) NULL,
ADD COLUMN religion VARCHAR(50) NULL,
ADD COLUMN caste VARCHAR(50) NULL,
ADD COLUMN nationality VARCHAR(50) NULL,
ADD COLUMN blood_group VARCHAR(10) NULL,
ADD COLUMN aadhaar_number VARCHAR(20) NULL,
ADD COLUMN father_name VARCHAR(255) NULL,
ADD COLUMN father_phone VARCHAR(50) NULL,
ADD COLUMN father_email VARCHAR(255) NULL,
ADD COLUMN father_occupation VARCHAR(255) NULL,
ADD COLUMN mother_name VARCHAR(255) NULL,
ADD COLUMN mother_phone VARCHAR(50) NULL,
ADD COLUMN mother_email VARCHAR(255) NULL,
ADD COLUMN mother_occupation VARCHAR(255) NULL,
ADD COLUMN annual_income DECIMAL(12, 2) NULL,
ADD COLUMN present_address TEXT NULL;

-- 2. Create student_documents table for uploading certificates
CREATE TABLE IF NOT EXISTS student_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_profile_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL, -- '10th_certificate', 'transfer_certificate', 'caste_certificate', 'plus_two_certificate'
    file_path VARCHAR(512) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_document_profile FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_profile_doc_type (student_profile_id, document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

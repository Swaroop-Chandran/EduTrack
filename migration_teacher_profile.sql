-- migration_teacher_profile.sql
-- Database migration script to extend the Teacher Profile schema

-- 1. Add fields to teacher_profiles table
ALTER TABLE teacher_profiles
ADD COLUMN dob DATE NULL,
ADD COLUMN gender VARCHAR(20) NULL,
ADD COLUMN religion VARCHAR(50) NULL,
ADD COLUMN caste VARCHAR(50) NULL,
ADD COLUMN nationality VARCHAR(50) NULL,
ADD COLUMN blood_group VARCHAR(10) NULL,
ADD COLUMN aadhaar_number VARCHAR(20) NULL,
ADD COLUMN address TEXT NULL;

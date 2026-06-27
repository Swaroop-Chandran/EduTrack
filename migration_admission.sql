-- migration_admission.sql
CREATE TABLE IF NOT EXISTS admission_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    dob DATE NOT NULL,
    gender VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    department_id INT NOT NULL,
    programme VARCHAR(100) NOT NULL,
    academic_year VARCHAR(50) NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    remarks TEXT NULL,
    password_hash VARCHAR(255) NOT NULL,
    doc_10th VARCHAR(255) NULL,
    doc_12th VARCHAR(255) NULL,
    doc_tc VARCHAR(255) NULL,
    doc_caste VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ar_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

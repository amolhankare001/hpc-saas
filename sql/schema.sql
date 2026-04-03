-- HPC Card SaaS Database Schema
CREATE DATABASE IF NOT EXISTS hpc_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hpc_saas;

-- Subscription Plans
CREATE TABLE IF NOT EXISTS plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    name_mr VARCHAR(100) NOT NULL,
    max_students INT NOT NULL DEFAULT 50,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    duration_months INT NOT NULL DEFAULT 12,
    features TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Schools (SaaS tenants)
CREATE TABLE IF NOT EXISTS schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    name_mr VARCHAR(255),
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    village VARCHAR(100),
    taluka VARCHAR(100),
    district VARCHAR(100),
    state VARCHAR(100) DEFAULT 'महाराष्ट्र',
    pin_code VARCHAR(6),
    udise_code VARCHAR(11),
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(15),
    password VARCHAR(255) NOT NULL,
    logo VARCHAR(255),
    plan_id INT DEFAULT NULL,
    subscription_start DATE DEFAULT NULL,
    subscription_end DATE DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    working_days INT DEFAULT 0,
    working_days_monthly TEXT DEFAULT NULL COMMENT 'JSON: {"apr":22,"may":20,...} per-month working days',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Teachers
CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    name_mr VARCHAR(255),
    teacher_code VARCHAR(50),
    email VARCHAR(255),
    phone VARCHAR(15),
    class_assigned VARCHAR(50),
    section VARCHAR(10),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Students
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    teacher_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    name_mr VARCHAR(255),
    apaar_id VARCHAR(50),
    udid VARCHAR(50),
    roll_no VARCHAR(20),
    registration_no VARCHAR(50),
    grade VARCHAR(20) DEFAULT 'इयत्ता १',
    section VARCHAR(10),
    date_of_birth DATE,
    age INT,
    gender ENUM('मुलगा','मुलगी','इतर') DEFAULT 'मुलगा',
    photo VARCHAR(255),
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    phone VARCHAR(15),
    mother_name VARCHAR(255),
    mother_education VARCHAR(100),
    mother_occupation VARCHAR(100),
    father_name VARCHAR(255),
    father_education VARCHAR(100),
    father_occupation VARCHAR(100),
    guardian_name VARCHAR(255),
    guardian_relation VARCHAR(50),
    num_siblings INT DEFAULT 0,
    siblings_age VARCHAR(100),
    mother_tongue VARCHAR(50) DEFAULT 'मराठी',
    medium_of_instruction VARCHAR(50) DEFAULT 'मराठी',
    area_type ENUM('ग्रामीण','शहरी') DEFAULT 'ग्रामीण',
    blood_group VARCHAR(5),
    aadhar_no VARCHAR(12),
    favourite_color VARCHAR(100) DEFAULT NULL,
    favourite_food VARCHAR(100) DEFAULT NULL,
    favourite_flower VARCHAR(100) DEFAULT NULL,
    favourite_sport VARCHAR(100) DEFAULT NULL,
    favourite_animal VARCHAR(100) DEFAULT NULL,
    favourite_subject VARCHAR(100) DEFAULT NULL,
    aspiration VARCHAR(255) DEFAULT NULL,
    best_friend1 VARCHAR(255) DEFAULT NULL,
    best_friend2 VARCHAR(255) DEFAULT NULL,
    best_friend3 VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Attendance
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    academic_year VARCHAR(10) NOT NULL,
    month TINYINT NOT NULL,
    working_days INT DEFAULT 0,
    days_present INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_attendance (student_id, academic_year, month),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Interests Master Table
CREATE TABLE IF NOT EXISTS interests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    name_mr VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- Student Interests
CREATE TABLE IF NOT EXISTS student_interests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    interest VARCHAR(100) NOT NULL,
    other_details TEXT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- HPC Cards
CREATE TABLE IF NOT EXISTS hpc_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    school_id INT NOT NULL,
    academic_year VARCHAR(10) NOT NULL,
    teacher_code VARCHAR(50),
    status ENUM('draft','completed','printed') DEFAULT 'draft',
    -- Part C yearly summary: overall levels for awareness, sensitivity, creativity
    summary_awareness VARCHAR(20) DEFAULT NULL COMMENT 'Overall awareness level: akash/parvat/pravah/pailu',
    summary_sensitivity VARCHAR(20) DEFAULT NULL COMMENT 'Overall sensitivity level: akash/parvat/pravah/pailu',
    summary_creativity VARCHAR(20) DEFAULT NULL COMMENT 'Overall creativity level: akash/parvat/pravah/pailu',
    -- Part C per-domain summary feedback
    summary_domain_1 TEXT DEFAULT NULL,
    summary_domain_2 TEXT DEFAULT NULL,
    summary_domain_3 TEXT DEFAULT NULL,
    summary_domain_4 TEXT DEFAULT NULL,
    summary_domain_5 TEXT DEFAULT NULL,
    summary_domain_6 TEXT DEFAULT NULL,
    -- Final annual feedback
    final_annual_feedback TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- HPC Domain Assessments (Part B)
CREATE TABLE IF NOT EXISTS hpc_domain_assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hpc_card_id INT NOT NULL,
    domain_id INT NOT NULL,
    domain_name VARCHAR(255) NOT NULL,
    domain_name_mr VARCHAR(255) NOT NULL,
    curricular_goals TEXT,
    competencies TEXT,
    activity TEXT,
    activity_mr TEXT,
    assessment_questions TEXT,
    assessment_questions_mr TEXT,
    -- Rubric assessments (4-level: pailu/pravah/parvat/akash)
    awareness_level VARCHAR(20) DEFAULT NULL,
    sensitivity_level VARCHAR(20) DEFAULT NULL,
    creativity_level VARCHAR(20) DEFAULT NULL,
    teacher_feedback TEXT,
    teacher_feedback_mr TEXT,
    -- Self & Peer Assessment
    self_assessment TEXT,
    peer_assessment TEXT,
    -- Parent/Caregiver Observation
    parent_observation TEXT,
    parent_observation_mr TEXT,
    -- Per-competency assessment activities (JSON: {"C-1.1": "activity text", ...})
    competency_activities TEXT,
    -- Term 2 (द्वितीय सत्र) fields
    curricular_goals_term2 TEXT,
    competencies_term2 TEXT,
    activity_mr_term2 TEXT,
    assessment_questions_mr_term2 TEXT,
    awareness_level_term2 VARCHAR(20) DEFAULT NULL,
    sensitivity_level_term2 VARCHAR(20) DEFAULT NULL,
    creativity_level_term2 VARCHAR(20) DEFAULT NULL,
    teacher_feedback_mr_term2 TEXT,
    self_assessment_term2 TEXT,
    peer_assessment_term2 TEXT,
    parent_observation_mr_term2 TEXT,
    competency_activities_term2 TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hpc_card_id) REFERENCES hpc_cards(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- HPC Part C - Credit Framework
CREATE TABLE IF NOT EXISTS hpc_credits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hpc_card_id INT NOT NULL,
    domain_name VARCHAR(255) NOT NULL,
    domain_name_mr VARCHAR(255) NOT NULL,
    credits DECIMAL(3,1) DEFAULT 4.5,
    ncf_level DECIMAL(3,1) DEFAULT 0.2,
    credit_points DECIMAL(3,2) DEFAULT 0.90,
    credit_points_earned DECIMAL(3,2) DEFAULT 0.00,
    credit_points_earned_term2 DECIMAL(3,2) DEFAULT 0.00,
    FOREIGN KEY (hpc_card_id) REFERENCES hpc_cards(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Admin users
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    is_super TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Coupon Codes
CREATE TABLE IF NOT EXISTS coupon_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_percent INT NOT NULL DEFAULT 10,
    max_uses INT NOT NULL DEFAULT 100,
    used_count INT NOT NULL DEFAULT 0,
    valid_from DATE DEFAULT NULL,
    valid_to DATE DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Payment/Subscription records
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    plan_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    original_amount DECIMAL(10,2) DEFAULT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    coupon_code VARCHAR(50) DEFAULT NULL,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    razorpay_order_id VARCHAR(100) DEFAULT NULL,
    razorpay_payment_id VARCHAR(100) DEFAULT NULL,
    razorpay_signature VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','completed','failed') DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert default plans
INSERT INTO plans (name, name_mr, max_students, price, duration_months, features) VALUES
('मोफत', 'मोफत योजना', 10, 0.00, 12, '["10 विद्यार्थी", "HPC कार्ड तयार करा", "PDF डाउनलोड"]'),
('स्टार्टर', 'स्टार्टर योजना', 10, 149.00, 12, '["10 विद्यार्थी", "HPC कार्ड तयार करा", "PDF डाउनलोड", "प्राधान्य सहाय्य"]'),
('स्टँडर्ड', 'स्टँडर्ड योजना', 20, 249.00, 12, '["20 विद्यार्थी", "HPC कार्ड", "PDF डाउनलोड", "बॅच प्रिंट", "प्राधान्य सहाय्य"]'),
('प्रीमियम', 'प्रीमियम योजना', 9999, 499.00, 12, '["अमर्यादित विद्यार्थी", "सर्व सुविधा", "बॅच प्रिंट", "कस्टम ब्रँडिंग", "प्राधान्य सहाय्य"]');

-- CMS Pages (editable from admin panel)
CREATE TABLE IF NOT EXISTS cms_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_slug VARCHAR(50) NOT NULL UNIQUE,
    page_title VARCHAR(255) NOT NULL,
    page_title_mr VARCHAR(255) NOT NULL,
    page_content LONGTEXT,
    is_active TINYINT(1) DEFAULT 1,
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin
INSERT INTO admins (username, password_hash, name, email, is_super) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin', 'admin@hpcsaas.com', 1);
-- Default password: password

-- Insert default CMS pages
INSERT INTO cms_pages (page_slug, page_title, page_title_mr, page_content) VALUES
('terms', 'Terms & Conditions', 'अटी व शर्ती', '<h3>अटी व शर्ती</h3><p>HPC कार्ड SaaS प्लॅटफॉर्म वापरण्यासाठी खालील अटी व शर्ती लागू आहेत.</p>'),
('privacy', 'Privacy Policy', 'गोपनीयता धोरण', '<h3>गोपनीयता धोरण</h3><p>आम्ही आपल्या गोपनीयतेला महत्त्व देतो.</p>'),
('about', 'About Us', 'आमच्याबद्दल', '<h3>आमच्याबद्दल</h3><p>HPC कार्ड SaaS हे NEP 2020 अंतर्गत सर्वांगीण प्रगती पत्रक तयार करण्यासाठी डिजिटल प्लॅटफॉर्म आहे.</p>'),
('contact', 'Contact Us', 'संपर्क', '<h3>संपर्क करा</h3><p>ईमेल: info@hpcsaas.com</p>');

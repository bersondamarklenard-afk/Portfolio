-- Portfolio CMS Schema
-- Compatible with MySQL 5.7+ / MariaDB (XAMPP & Hostinger)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS portfolio_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE portfolio_db;

-- Admins (replaces / extends admin_users)
CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_admins_username (username),
  UNIQUE KEY uq_admins_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Personal / profile information (single-row style content)
CREATE TABLE IF NOT EXISTS personal_information (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  professional_title VARCHAR(150) DEFAULT NULL,
  tagline VARCHAR(255) DEFAULT NULL,
  about_me TEXT DEFAULT NULL,
  what_i_do TEXT DEFAULT NULL,
  career_objective TEXT DEFAULT NULL,
  interests TEXT DEFAULT NULL,
  strengths TEXT DEFAULT NULL,
  email VARCHAR(150) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  location VARCHAR(150) DEFAULT NULL,
  profile_photo VARCHAR(255) DEFAULT NULL,
  resume_file VARCHAR(255) DEFAULT NULL,
  years_experience VARCHAR(50) DEFAULT NULL,
  availability_status VARCHAR(100) DEFAULT 'Open to opportunities',
  meta_title VARCHAR(200) DEFAULT NULL,
  meta_description TEXT DEFAULT NULL,
  footer_text VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Skills
CREATE TABLE IF NOT EXISTS skills (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  category ENUM('frontend','backend','database','tools','design','other') NOT NULL DEFAULT 'other',
  proficiency TINYINT UNSIGNED DEFAULT 70 COMMENT '0-100',
  icon_class VARCHAR(80) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_skills_category (category),
  KEY idx_skills_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Experience / internships
CREATE TABLE IF NOT EXISTS experiences (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  position VARCHAR(150) NOT NULL,
  company VARCHAR(150) NOT NULL,
  location VARCHAR(150) DEFAULT NULL,
  start_date DATE DEFAULT NULL,
  end_date DATE DEFAULT NULL,
  is_current TINYINT(1) NOT NULL DEFAULT 0,
  description TEXT DEFAULT NULL,
  responsibilities TEXT DEFAULT NULL COMMENT 'One responsibility per line',
  technologies VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_exp_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Projects
CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  short_description VARCHAR(300) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  problem_purpose TEXT DEFAULT NULL,
  key_features TEXT DEFAULT NULL COMMENT 'One feature per line',
  technologies VARCHAR(255) DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  github_url VARCHAR(255) DEFAULT NULL,
  live_url VARCHAR(255) DEFAULT NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_projects_featured (is_featured),
  KEY idx_projects_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Education
CREATE TABLE IF NOT EXISTS education (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  degree VARCHAR(200) NOT NULL,
  institution VARCHAR(200) NOT NULL,
  category VARCHAR(40) NOT NULL DEFAULT 'college',
  location VARCHAR(150) DEFAULT NULL,
  start_date DATE DEFAULT NULL,
  end_date DATE DEFAULT NULL,
  is_current TINYINT(1) NOT NULL DEFAULT 0,
  description TEXT DEFAULT NULL,
  academic_honor VARCHAR(200) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Certifications & achievements
CREATE TABLE IF NOT EXISTS certifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  issuer VARCHAR(150) DEFAULT NULL,
  type ENUM('certification','award','achievement','training','other') NOT NULL DEFAULT 'certification',
  issue_date DATE DEFAULT NULL,
  description TEXT DEFAULT NULL,
  credential_url VARCHAR(255) DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Social / professional links
CREATE TABLE IF NOT EXISTS social_links (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  platform VARCHAR(50) NOT NULL,
  label VARCHAR(100) DEFAULT NULL,
  url VARCHAR(255) NOT NULL,
  icon_class VARCHAR(80) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_social_platform (platform)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contact form messages
CREATE TABLE IF NOT EXISTS contact_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL,
  subject VARCHAR(200) DEFAULT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_messages_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional key-value settings for extras
CREATE TABLE IF NOT EXISTS site_settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL,
  setting_value TEXT DEFAULT NULL,
  setting_type ENUM('text','textarea','image','file') DEFAULT 'text',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO site_settings (setting_key, setting_value, setting_type)
VALUES ('portfolio_visible', '1', 'text');

SET FOREIGN_KEY_CHECKS = 1;

-- Create database and user (optional). Run these in XAMPP's phpMyAdmin or MySQL CLI.
-- Adjust password and permissions as needed.

CREATE DATABASE IF NOT EXISTS gift_finder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gift_finder;

-- Core tables (English names only)

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) UNIQUE,
  name VARCHAR(190),
   password_hash VARCHAR(255) NOT NULL DEFAULT '',
  google_id VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  
);

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  currency VARCHAR(10) DEFAULT 'THB',
  description TEXT,
  image_url VARCHAR(500),
  external_url VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS product_categories (
  product_id INT NOT NULL,
  category_id INT NOT NULL,
  PRIMARY KEY (product_id, category_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS interests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS product_interests (
  product_id INT NOT NULL,
  interest_id INT NOT NULL,
  PRIMARY KEY (product_id, interest_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (interest_id) REFERENCES interests(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  content TEXT,
  author_name VARCHAR(190),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS blog_posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  content MEDIUMTEXT,
  published_at TIMESTAMP NULL DEFAULT NULL
);

-- Seed example lookup data (optional)
/*
CREATE TABLE IF NOT EXISTS gift_recipients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190),
  relationship VARCHAR(190),
  gender VARCHAR(50),
  age_range VARCHAR(50),
  budget VARCHAR(50),
  category VARCHAR(100),
  user_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
  แก้ให้ดีขึ้น
*/ 

-- bookmarks table to link users and their bookmarked products
CREATE TABLE IF NOT EXISTS bookmarks(
  id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  product_id int(11) NOT NULL,
  folder_id int(11) DEFAULT NULL,
  custom_name varchar(190) DEFAULT NULL,
  note text DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
)

CREATE TABLE IF NOT EXISTS bookmark_folders (
  id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  name varchar(190) NOT NULL DEFAULT 'รายการของฉัน',
  created_at timestamp NOT NULL DEFAULT current_timestamp()
)


-- จัดประเภทใหม่ แบบ คงที่

CREATE TABLE budget_options (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  min_price DECIMAL(10,2) DEFAULT 0,
  max_price DECIMAL(10,2) DEFAULT 999999.99,
  display_order INT DEFAULT 0
);

-- ตัวอย่างข้อมูล
INSERT INTO budget_options (name, min_price, max_price, display_order) VALUES
('Under 500', 0, 499.99, 1),
('500 - 1,000', 500, 1000, 2),
('1,000 - 3,000', 1000, 3000, 3),
('Over 3,000', 3000, 999999.99, 4);

CREATE TABLE genders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  display_name VARCHAR(50) NOT NULL,
  icon VARCHAR(50) DEFAULT NULL
);

INSERT INTO genders (name, display_name) VALUES
('any', 'Any'),
('male', 'Male'),
('female', 'Female'),
('non_binary', 'Non-binary');

CREATE TABLE age_ranges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  min_age INT,
  max_age INT,
  display_name VARCHAR(100)
);

INSERT INTO age_ranges (name, min_age, max_age, display_name) VALUES
('any', 0, 150, 'Any'),
('child', 0, 12, 'Child (0-12)'),
('teen', 13, 19, 'Teen (13-19)'),
('adult', 20, 60, 'Adult (20-60)'),
('senior', 61, 150, 'Senior (61+)');

CREATE TABLE relationships (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  display_name VARCHAR(100) NOT NULL,
  icon VARCHAR(50) DEFAULT NULL
);

INSERT INTO relationships (name, display_name) VALUES
('family', 'ครบครัว'),
('friend', 'เพื่อน'),
('colleague', 'เพื่อนร่วมงาน'),
('partner', 'คู่รัก'),
('boss', 'เจ้านาย');


CREATE TABLE gift_recipients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190),
  relationship_id INT, -- แทน relationship VARCHAR
  gender_id INT,       -- แทน gender VARCHAR
  age_range_id INT,    -- แทน age_range VARCHAR
  budget_id INT,       -- แทน budget VARCHAR
  category VARCHAR(100), -- ถ้ายังใช้ string ได้ (หรือจะเชื่อมกับ categories ที่มีแล้วก็ได้)
  user_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (relationship_id) REFERENCES relationships(id) ON DELETE SET NULL,
  FOREIGN KEY (gender_id) REFERENCES genders(id) ON DELETE SET NULL,
  FOREIGN KEY (age_range_id) REFERENCES age_ranges(id) ON DELETE SET NULL,
  FOREIGN KEY (budget_id) REFERENCES budget_options(id) ON DELETE SET NULL
);


CREATE TABLE IF NOT EXISTS product_budgets (
    product_id INT NOT NULL,
    budget_id INT NOT NULL,
    PRIMARY KEY (product_id, budget_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (budget_id) REFERENCES budget_options(id) ON DELETE CASCADE
);


-- table to link products with target audiences (relationship, gender, age range)
CREATE TABLE IF NOT EXISTS product_target_audiences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    relationship_id INT NULL, -- อาจเป็น NULL ถ้าไม่ระบุ
    gender_id INT NULL,
    age_range_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (relationship_id) REFERENCES relationships(id) ON DELETE CASCADE,
    FOREIGN KEY (gender_id) REFERENCES genders(id) ON DELETE CASCADE,
    FOREIGN KEY (age_range_id) REFERENCES age_ranges(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_audience (product_id, relationship_id, gender_id, age_range_id) -- ป้องกันการเชื่อมซ้ำ
);
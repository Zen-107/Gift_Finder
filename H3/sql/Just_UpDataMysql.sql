-- 1. ใช้ฐานข้อมูล gift_finder (หรือสร้างถ้ายังไม่มี)
CREATE DATABASE IF NOT EXISTS gift_finder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gift_finder;

-- 2. สร้างตารางใหม่ (เฉพาะที่ยังไม่มี)
-- ตาราง lookup ใหม่
CREATE TABLE IF NOT EXISTS budget_options (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  min_price DECIMAL(10,2) DEFAULT 0,
  max_price DECIMAL(10,2) DEFAULT 999999.99,
  display_order INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS genders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  display_name VARCHAR(50) NOT NULL DEFAULT '',
  icon VARCHAR(50) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS age_ranges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  min_age INT,
  max_age INT,
  display_name VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS relationships (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  display_name VARCHAR(100) NOT NULL,
  icon VARCHAR(50) DEFAULT NULL
);

-- ตาราง gift_recipients ใหม่
CREATE TABLE IF NOT EXISTS gift_recipients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190),
  relationship_id INT,
  gender_id INT,
  age_range_id INT,
  budget_id INT,
  category VARCHAR(100),
  user_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (relationship_id) REFERENCES relationships(id) ON DELETE SET NULL,
  FOREIGN KEY (gender_id) REFERENCES genders(id) ON DELETE SET NULL,
  FOREIGN KEY (age_range_id) REFERENCES age_ranges(id) ON DELETE SET NULL,
  FOREIGN KEY (budget_id) REFERENCES budget_options(id) ON DELETE SET NULL
);

-- 3. อัปเดตตารางเดิม (เพิ่มคอลัมน์และ FK หากยังไม่มี)
-- เพิ่มคอลัมน์และ FK ให้ bookmarks ถ้ายังไม่มี
ALTER TABLE bookmarks
  ADD COLUMN IF NOT EXISTS folder_id INT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS custom_name VARCHAR(190) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS note TEXT DEFAULT NULL,
  ADD FOREIGN KEY IF NOT EXISTS (folder_id) REFERENCES bookmark_folders(id) ON DELETE SET NULL,
  ADD FOREIGN KEY IF NOT EXISTS (user_id) REFERENCES users(id) ON DELETE CASCADE,
  ADD FOREIGN KEY IF NOT EXISTS (product_id) REFERENCES products(id) ON DELETE CASCADE;

-- เพิ่ม PK และ FK ให้ bookmarks ถ้ายังไม่มี
-- ตรวจสอบว่า `id` เป็น AUTO_INCREMENT PRIMARY KEY แล้วหรือยัง (อาจต้องดูจากข้อมูลเดิม)
-- ถ้าคุณแน่ใจว่าคุณจะเพิ่มเอง ให้ใช้คำสั่งนี้ (แต่ระวัง ถ้ามี PK อยู่แล้วจะ error):
-- ALTER TABLE bookmarks ADD PRIMARY KEY (id);

-- เพิ่ม FK ให้ bookmark_folders ถ้ายังไม่มี
ALTER TABLE bookmark_folders
  ADD FOREIGN KEY IF NOT EXISTS (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- 4. เพิ่มข้อมูลตัวอย่าง (Seed Data) ถ้ายังไม่มี
-- ใช้ INSERT IGNORE เพื่อป้องกัน error หากมี record ที่ซ้ำ (เช่น id ซ้ำ)
INSERT IGNORE INTO budget_options (id, name, min_price, max_price, display_order) VALUES
(1, 'Under 500', 0, 499.99, 1),
(2, '500 - 1,000', 500, 1000, 2),
(3, '1,000 - 3,000', 1000, 3000, 3),
(4, 'Over 3,000', 3000, 999999.99, 4);

INSERT IGNORE INTO genders (id, name, display_name) VALUES
(1, 'any', 'Any'),
(2, 'male', 'Male'),
(3, 'female', 'Female'),
(4, 'non_binary', 'Non-binary');

INSERT IGNORE INTO age_ranges (id, name, min_age, max_age, display_name) VALUES
(1, 'any',    0, 150, 'All Ages'),
(2, '0-5y',   0, 5,   'Infant & Toddler (0–5)'),
(3, '6-12y',  6, 12,  'Child (6–12)'),
(4, '13-19y', 13, 19, 'Teenager (13–19)'),
(5, '20-35y', 20, 35, 'Young Adult (20–35)'),
(6, '36-50y', 36, 50, 'Middle-aged Adult (36–50)'),
(7, '50y+',   51, 150,'Senior (51+)');


INSERT IGNORE INTO relationships (id, name, display_name) VALUES
(1, 'Friend', 'เพื่อน'),
(2, 'Lover / Partner', 'คู่รัก/คนคุย'),
(3, 'Family', 'ครอบครัว'),
(4, 'Respectful Adults', 'ผู้ใหญ่ที่เคารพ'),
(5, 'self-geft', 'ตัวเอง'),
(6, 'Guests','ลูกค้า/ผู้มาเยือน');





-- Unused tables removal
DROP TABLE order_items, orders;


-- เพิ่มข้อมูลหมวดหมู่ตแบบไม่ต้องเปลี่ยนแปลงโครงสร้างตาราง
INSERT INTO categories (name) VALUES
('Photography'),
('travel & Outdoors'),
('Toys & Kids'),
('Beauty & Personal Care'),
('Pets'),
('Food, Drinks & Cooking'),
('Electronics'),
('Gaming & Accessories'),
('Fashion & Jewelry'),
('Stationery & Books'),
('Home & Lifestyle'),
('Health & Supplements'),
('Art & Music'),
('DIY & Crafts');

-- ลิงก์ gift_recipients กับ categories (คนหนึ่งชอบได้หลายหมวด)
CREATE TABLE IF NOT EXISTS recipient_categories (
  recipient_id INT NOT NULL,
  category_id  INT NOT NULL,
  PRIMARY KEY (recipient_id, category_id),
  FOREIGN KEY (recipient_id) REFERENCES gift_recipients(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id)  REFERENCES categories(id)      ON DELETE CASCADE
);

-- เก็บ log การค้นหาของผู้ใช้ เพื่อเอาไปวิเคราะห์ทีหลัง
CREATE TABLE IF NOT EXISTS search_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  budget_id INT NULL,
  gender_id INT NULL,
  age_range_id INT NULL,
  relationship_id INT NULL,
  categories_json TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)        REFERENCES users(id)          ON DELETE SET NULL,
  FOREIGN KEY (budget_id)      REFERENCES budget_options(id) ON DELETE SET NULL,
  FOREIGN KEY (gender_id)      REFERENCES genders(id)        ON DELETE SET NULL,
  FOREIGN KEY (age_range_id)   REFERENCES age_ranges(id)     ON DELETE SET NULL,
  FOREIGN KEY (relationship_id) REFERENCES relationships(id) ON DELETE SET NULL
);

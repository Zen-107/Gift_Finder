CREATE DATABASE IF NOT EXISTS gift_finder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gift_finder;

-- Core tables

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) UNIQUE,
  name VARCHAR(190),
 password_hash VARCHAR(255) NOT NULL DEFAULT '',
  google_id VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ลบ price และ currency ออกจาก products เพราะราคาขึ้นกับแหล่งขาย
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  description TEXT,
  image_url VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- เพิ่ม min_price, max_price, และ currency ที่นี่แทน
CREATE TABLE IF NOT EXISTS product_external_urls (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  url VARCHAR(1000) NOT NULL,
  source_name VARCHAR(100),
  min_price DECIMAL(10,2) NOT NULL,
  max_price DECIMAL(10,2) NOT NULL,
  currency VARCHAR(10) DEFAULT 'THB',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  -- เพิ่ม constraint เพื่อให้ min_price <= max_price
  CHECK (min_price <= max_price)
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

-- โฟลเดอร์สำหรับจัดกลุ่ม bookmark
CREATE TABLE IF NOT EXISTS bookmark_folders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(190) NOT NULL DEFAULT 'รายการของฉัน', -- ชื่อโฟลเดอร์
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- รายการ bookmark แต่ละอัน ผูกกับสินค้า + โฟลเดอร์ + ผู้ใช้
CREATE TABLE IF NOT EXISTS bookmarks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  folder_id INT, -- อาจไม่ระบุโฟลเดอร์ก็ได้ (ถ้าอนุญาตให้ใส่ได้ทั้งใน/นอกโฟลเดอร์)
  custom_name VARCHAR(190), -- เช่น "รองเท้าพ่อ - ไซส์ 42", ถ้าไม่ตั้งก็ใช้ชื่อสินค้าเดิม
  note TEXT, -- หมายเหตุเพิ่มเติม (optional)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (folder_id) REFERENCES bookmark_folders(id) ON DELETE SET NULL
);
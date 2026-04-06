CREATE DATABASE IF NOT EXISTS 55_national DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
USE 55_national;

-- Publishers
CREATE TABLE publishers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    isbn_code VARCHAR(10) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Contacts
CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publisher_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL,
    FOREIGN KEY (publisher_id) REFERENCES publishers(id)
);

-- Admins (密碼以 hash 儲存)
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super', 'publisher') NOT NULL,
    publisher_id INT NULL,
    name VARCHAR(50) NOT NULL,
    FOREIGN KEY (publisher_id) REFERENCES publishers(id)
);

-- 新增預設系統管理員 (帳號：admin / 密碼：1234)
-- 使用 SHA256 儲存，PHP 端在登入驗證時使用 hash('sha256', $password) 比對
INSERT INTO admins (username, password_hash, role, name) 
VALUES ('admin', SHA2('1234', 256), 'super', 'Super Administrator');

-- Books
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    author VARCHAR(255) NOT NULL,
    publisher_id INT NOT NULL,
    is_hidden BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (publisher_id) REFERENCES publishers(id)
);

-- Book Images
CREATE TABLE book_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    is_cover BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- ==========================================
-- 測試用假資料 (Mock Data)
-- ==========================================

-- 1. 新增供測試有價資料的出版社
INSERT INTO publishers (name, address, phone, isbn_code, status) VALUES 
('深智數位', '台北市中山區...', '02-12345678', '986', 'active'),
('松崗出版', '台北市中正區...', '02-87654321', '777', 'active'),
('停權的出版社', '被封鎖區', '0900-000000', '999', 'inactive');

-- 2. 新增各出版社的聯絡人
INSERT INTO contacts (publisher_id, name, phone, email) VALUES 
(1, '陳經理', '0912-345678', 'chen@deep.com'),
(1, '林業務', '0987-654321', 'lin@deep.com'),
(2, '王主編', '0922-333444', 'wang@song.com');

-- 3. 新增出版社管理員 (密碼皆為 1234)
INSERT INTO admins (username, password_hash, role, publisher_id, name) VALUES 
('pub1', SHA2('1234', 256), 'publisher', 1, '深智管理員'),
('pub2', SHA2('1234', 256), 'publisher', 2, '松崗管理員');

-- 4. 新增書籍資料 (故意包含一筆隱藏的)
INSERT INTO books (isbn, title, description, author, publisher_id, is_hidden) VALUES 
('978-986-181-728-6', 'PHP & MySQL 網站開發', '這是一本優質的 PHP 學習手冊', '施大師', 1, 0),
('978-986-555-555-4', '網頁大師之路', '教你如何一氣呵成寫出比賽代碼。', '前端王', 1, 0),
('978-777-111-222-3', '秘密書籍', '這本書目前被下架了...', '隱藏大師', 2, 1);

-- 5. 新增書籍圖片 (指向預期可能上傳的假檔名)
INSERT INTO book_images (book_id, image_url, is_cover) VALUES 
(1, 'fake_cover1.jpg', 1),
(2, 'fake_cover2.jpg', 1);


-- ==========================================================
-- SQL MIGRATION: Modul Master Pixel Terpusat & Relasi Produk
-- Jalankan query ini di phpMyAdmin cPanel pada database Anda
-- ==========================================================

-- 1. Buat Tabel Master Pixels (jika belum ada)
CREATE TABLE IF NOT EXISTS `pixels` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(100) NOT NULL,
  `meta_pixel_id` VARCHAR(50) NOT NULL,
  `conversion_api_token` TEXT DEFAULT NULL,
  `test_event_code` VARCHAR(50) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_meta_pixel_id` (`meta_pixel_id`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Tambah kolom pixel_id di tabel produk (jika belum ada)
-- Abaikan jika kolom pixel_id sudah ada
ALTER TABLE `produk` 
ADD COLUMN `pixel_id` INT(11) NULL DEFAULT NULL AFTER `admin_wa`;

-- 3. Kosongkan tabel pixels terlebih dahulu agar bersih dan tidak ada duplikasi
TRUNCATE TABLE `pixels`;

-- 4. Masukkan Akun Pixel Asli Anda yang Bersih & Rapi
-- Pixel 1: Pixel EduMuslim / Ebook
INSERT INTO `pixels` (`id`, `nama`, `meta_pixel_id`, `conversion_api_token`, `is_active`)
SELECT 
    1,
    'Pixel Utama (EduMuslim)',
    '300555229246452',
    conversion_api_token,
    1
FROM `produk`
WHERE meta_pixel_id = '300555229246452' AND conversion_api_token != ''
ORDER BY id DESC
LIMIT 1;

-- Pixel 2: Pixel Bisnis / Produk Umum
INSERT INTO `pixels` (`id`, `nama`, `meta_pixel_id`, `conversion_api_token`, `is_active`)
SELECT 
    2,
    'Pixel Bisnis (Produk Umum)',
    '947931514007781',
    conversion_api_token,
    1
FROM `produk`
WHERE meta_pixel_id = '947931514007781' AND conversion_api_token != ''
ORDER BY id DESC
LIMIT 1;

-- 5. Hubungkan produk ke Master Pixel yang sesuai
-- Produk-produk dengan Pixel ID 300555229246452 dihubungkan ke Pixel 1
UPDATE `produk` SET `pixel_id` = 1 WHERE `meta_pixel_id` = '300555229246452';

-- Produk-produk dengan Pixel ID 947931514007781 dihubungkan ke Pixel 2
UPDATE `produk` SET `pixel_id` = 2 WHERE `meta_pixel_id` = '947931514007781';

-- 6. Bersihkan data dummy testing lama (admin_crm / sayaadmin)
UPDATE `produk` SET `pixel_id` = NULL, `meta_pixel_id` = '', `conversion_api_token` = '' WHERE `meta_pixel_id` = 'admin_crm';

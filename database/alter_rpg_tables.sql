-- Skrip ini untuk menambahkan kolom enemies dan difficulty ke tabel rpg_maps yang sudah ada.
-- Jalankan skrip ini di phpMyAdmin cPanel Anda.

ALTER TABLE `rpg_maps` 
ADD COLUMN `enemies` JSON NULL COMMENT '[{x:0,y:0,speed:1,avatar:"👻"}, ...]' AFTER `obstacles`;

ALTER TABLE `rpg_maps` 
ADD COLUMN `difficulty` VARCHAR(20) NOT NULL DEFAULT 'easy' COMMENT 'easy, medium, hard' AFTER `enemies`;

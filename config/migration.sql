-- Migration for contenir-asset 2.0.0
-- Adds focal point support to image table

-- Add focal point columns to image table
ALTER TABLE `image`
ADD COLUMN `focal_x` DECIMAL(5,4) DEFAULT 0.5 COMMENT 'Focal point X coordinate (0.0 = left, 1.0 = right)',
ADD COLUMN `focal_y` DECIMAL(5,4) DEFAULT 0.5 COMMENT 'Focal point Y coordinate (0.0 = top, 1.0 = bottom)',
ADD COLUMN `crop_mode` VARCHAR(20) DEFAULT 'cover' COMMENT 'Crop mode: cover, contain, fill, exact';

-- Add index for common queries
ALTER TABLE `image`
ADD INDEX `idx_focal` (`focal_x`, `focal_y`);

-- Optional: Add columns for named crops (advanced feature)
CREATE TABLE IF NOT EXISTS `image_crop` (
    `crop_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `image_id` INT UNSIGNED NOT NULL,
    `crop_name` VARCHAR(50) NOT NULL COMMENT 'Named crop preset (e.g., hero, thumbnail, portrait)',
    `focal_x` DECIMAL(5,4) DEFAULT 0.5,
    `focal_y` DECIMAL(5,4) DEFAULT 0.5,
    `crop_x` INT UNSIGNED DEFAULT NULL COMMENT 'Absolute crop X coordinate (pixels)',
    `crop_y` INT UNSIGNED DEFAULT NULL COMMENT 'Absolute crop Y coordinate (pixels)',
    `crop_width` INT UNSIGNED DEFAULT NULL COMMENT 'Crop width (pixels)',
    `crop_height` INT UNSIGNED DEFAULT NULL COMMENT 'Crop height (pixels)',
    PRIMARY KEY (`crop_id`),
    UNIQUE KEY `unique_image_crop` (`image_id`, `crop_name`),
    CONSTRAINT `fk_image_crop_image` FOREIGN KEY (`image_id`) REFERENCES `image` (`image_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- =============================================================================
-- Tilkit — bring occcoke_tk (or similar) in line with Laravel migrations
-- Run in phpMyAdmin when artisan migrate is not available.
-- Skip any statement that errors with "Duplicate column name".
-- =============================================================================

-- --- 2026_10_24_170000_tilkit_season_months_and_record_collection ---
ALTER TABLE `tilkit_seasons`
  ADD COLUMN `start_month` TINYINT UNSIGNED NULL AFTER `label`,
  ADD COLUMN `end_month` TINYINT UNSIGNED NULL AFTER `start_month`;

ALTER TABLE `tilkit_indicator_records`
  ADD COLUMN `collection_date` DATE NULL AFTER `protocol_version`,
  ADD COLUMN `collection_year` SMALLINT UNSIGNED NULL AFTER `collection_date`;

-- --- 2026_10_29_120000_tilkit_seasons_per_site_and_study_area ---
ALTER TABLE `tilkit_sites`
  ADD COLUMN `study_area_geojson` JSON NULL AFTER `longitude`;

ALTER TABLE `tilkit_seasons`
  ADD COLUMN `site_id` BIGINT UNSIGNED NULL AFTER `program_id`,
  ADD KEY `tilkit_seasons_site_id_foreign` (`site_id`),
  ADD CONSTRAINT `tilkit_seasons_site_id_foreign`
    FOREIGN KEY (`site_id`) REFERENCES `tilkit_sites` (`id`) ON DELETE CASCADE;

-- Backfill one site per program (adjust manually if needed)
UPDATE `tilkit_seasons` s
INNER JOIN (
  SELECT `program_id`, MIN(`id`) AS `site_id`
  FROM `tilkit_sites`
  WHERE `program_id` IS NOT NULL
  GROUP BY `program_id`
) pick ON pick.`program_id` = s.`program_id`
SET s.`site_id` = pick.`site_id`
WHERE s.`site_id` IS NULL;

-- Per-site season labels: MySQL reuses (project_id, label) for the project_id FK — drop FK first.
-- If DROP INDEX says "check that column/key exists", the legacy index is already gone; continue.
ALTER TABLE `tilkit_seasons` DROP FOREIGN KEY `tilkit_seasons_project_id_foreign`;

ALTER TABLE `tilkit_seasons` DROP INDEX `tilkit_seasons_project_id_label_unique`;

ALTER TABLE `tilkit_seasons`
  ADD CONSTRAINT `tilkit_seasons_project_id_foreign`
    FOREIGN KEY (`project_id`) REFERENCES `dc_projects` (`id`) ON DELETE CASCADE;

ALTER TABLE `tilkit_seasons`
  ADD UNIQUE KEY `tilkit_seasons_site_id_label_unique` (`site_id`, `label`);

ALTER TABLE `tilkit_seasons` MODIFY `label` VARCHAR(120) NOT NULL;

-- --- 2026_11_10_120000_tool_invitation_tilkit_persona ---
ALTER TABLE `tool_members`
  ADD COLUMN `tilkit_persona` VARCHAR(40) NULL AFTER `role`;

ALTER TABLE `tool_invitations`
  ADD COLUMN `tilkit_persona` VARCHAR(40) NULL AFTER `role`;

-- Record migrations (only rows not already present)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_24_170000_tilkit_season_months_and_record_collection', IFNULL(MAX(`batch`), 0) + 1 FROM `migrations`
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_24_170000_tilkit_season_months_and_record_collection'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_29_120000_tilkit_seasons_per_site_and_study_area', IFNULL(MAX(`batch`), 0) + 1 FROM `migrations`
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_29_120000_tilkit_seasons_per_site_and_study_area'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_10_120000_tool_invitation_tilkit_persona', IFNULL(MAX(`batch`), 0) + 1 FROM `migrations`
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_10_120000_tool_invitation_tilkit_persona'
);

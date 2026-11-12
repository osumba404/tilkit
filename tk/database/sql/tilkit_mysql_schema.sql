-- =============================================================================
-- Tilkit — full MySQL/MariaDB schema (structure only, no data)
-- Generated from local database `tk1` (MariaDB 10.4, matches Laravel migrations)
-- Use in production: create empty DB in cPanel, then import this file (phpMyAdmin
-- or MySQL → Import). Structure only; run seeders/artisan separately for data.
--
-- Notes:
-- * Drops existing tables (DROP TABLE IF EXISTS) — safe only on an empty database.
-- * Includes audit_logs append-only triggers.
-- * DEFINER clauses removed for shared-hosting compatibility.
-- =============================================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `occurred_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `actor_type` varchar(10) NOT NULL DEFAULT 'user',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `event` varchar(30) NOT NULL,
  `auditable_type` varchar(60) DEFAULT NULL,
  `auditable_id` bigint(20) unsigned DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `module` varchar(40) DEFAULT NULL,
  `request_id` char(36) DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `http_method` varchar(8) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `device_uuid` char(36) DEFAULT NULL,
  `tags` varchar(120) DEFAULT NULL,
  `note` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `audit_logs_uuid_unique` (`uuid`),
  KEY `ix_audit_auditable` (`auditable_type`,`auditable_id`,`occurred_at`),
  KEY `ix_audit_user` (`user_id`,`occurred_at`),
  KEY `ix_audit_event` (`event`,`occurred_at`),
  KEY `ix_audit_request` (`request_id`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER trg_audit_logs_no_update BEFORE UPDATE ON audit_logs
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs is append-only' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER trg_audit_logs_no_delete BEFORE DELETE ON audit_logs
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs is append-only' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_access_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_access_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `action` varchar(12) NOT NULL,
  `form_id` bigint(20) unsigned DEFAULT NULL,
  `submission_id` bigint(20) unsigned DEFAULT NULL,
  `record_count` int(11) DEFAULT NULL,
  `fields_included` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`fields_included`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `occurred_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `dc_access_log_form_id_foreign` (`form_id`),
  KEY `dc_access_log_submission_id_foreign` (`submission_id`),
  KEY `ix_dc_access_user` (`user_id`,`occurred_at`),
  CONSTRAINT `dc_access_log_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `dc_forms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_access_log_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `dc_submissions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_access_log_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_aggregate_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_aggregate_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `data_element_id` bigint(20) unsigned NOT NULL,
  `org_unit_type` varchar(40) NOT NULL,
  `org_unit_id` bigint(20) unsigned NOT NULL,
  `period` varchar(12) NOT NULL,
  `category_key` varchar(80) NOT NULL DEFAULT 'default',
  `value_number` decimal(20,4) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_aggregate_values_unique` (`data_element_id`,`org_unit_type`,`org_unit_id`,`period`,`category_key`),
  CONSTRAINT `dc_aggregate_values_data_element_id_foreign` FOREIGN KEY (`data_element_id`) REFERENCES `dc_data_elements` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_answer_choices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_answer_choices` (
  `answer_id` bigint(20) unsigned NOT NULL,
  `choice_id` bigint(20) unsigned DEFAULT NULL,
  `choice_name` varchar(100) NOT NULL,
  `rank_no` int(11) DEFAULT NULL,
  `other_text` text DEFAULT NULL,
  PRIMARY KEY (`answer_id`,`choice_name`),
  KEY `dc_answer_choices_choice_id_foreign` (`choice_id`),
  CONSTRAINT `dc_answer_choices_answer_id_foreign` FOREIGN KEY (`answer_id`) REFERENCES `dc_answers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dc_answer_choices_choice_id_foreign` FOREIGN KEY (`choice_id`) REFERENCES `dc_choices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_answers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint(20) unsigned NOT NULL,
  `field_id` bigint(20) unsigned NOT NULL,
  `repeat_id` bigint(20) unsigned DEFAULT NULL,
  `value_text` text DEFAULT NULL,
  `value_number` decimal(20,6) DEFAULT NULL,
  `value_bool` tinyint(1) DEFAULT NULL,
  `value_date` date DEFAULT NULL,
  `value_datetime` timestamp NULL DEFAULT NULL,
  `value_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`value_json`)),
  `is_missing` tinyint(1) NOT NULL DEFAULT 0,
  `missing_code` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_answers_submission_id_field_id_repeat_id_unique` (`submission_id`,`field_id`,`repeat_id`),
  KEY `dc_answers_repeat_id_foreign` (`repeat_id`),
  KEY `ix_dc_ans_field_num` (`field_id`,`value_number`),
  KEY `ix_dc_ans_field_date` (`field_id`,`value_date`),
  KEY `ix_dc_ans_field_text` (`field_id`,`value_text`(191)),
  CONSTRAINT `dc_answers_field_id_foreign` FOREIGN KEY (`field_id`) REFERENCES `dc_form_fields` (`id`),
  CONSTRAINT `dc_answers_repeat_id_foreign` FOREIGN KEY (`repeat_id`) REFERENCES `dc_repeat_instances` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_answers_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `dc_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned NOT NULL,
  `assignee_id` bigint(20) unsigned NOT NULL,
  `subject_type` varchar(60) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `prefill` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`prefill`)),
  `due_at` timestamp NULL DEFAULT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'open',
  `submission_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dc_assignments_form_id_foreign` (`form_id`),
  KEY `dc_assignments_assignee_id_foreign` (`assignee_id`),
  KEY `dc_assignments_submission_id_foreign` (`submission_id`),
  CONSTRAINT `dc_assignments_assignee_id_foreign` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`),
  CONSTRAINT `dc_assignments_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `dc_forms` (`id`),
  CONSTRAINT `dc_assignments_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `dc_submissions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_audit_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_audit_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint(20) unsigned NOT NULL,
  `field_name` varchar(80) DEFAULT NULL,
  `event_type` varchar(16) NOT NULL,
  `occurred_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `elapsed_ms` int(11) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `lat` decimal(9,6) DEFAULT NULL,
  `lng` decimal(9,6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_dc_audit_sub` (`submission_id`,`occurred_at`),
  CONSTRAINT `dc_audit_events_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `dc_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_cases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `case_type` varchar(60) NOT NULL,
  `subject_type` varchar(60) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `parent_case_id` bigint(20) unsigned DEFAULT NULL,
  `owner_user_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'open',
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `opened_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_cases_uuid_unique` (`uuid`),
  KEY `dc_cases_parent_case_id_foreign` (`parent_case_id`),
  KEY `dc_cases_owner_user_id_foreign` (`owner_user_id`),
  KEY `ix_dc_cases_subject` (`subject_type`,`subject_id`,`case_type`),
  CONSTRAINT `dc_cases_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_cases_parent_case_id_foreign` FOREIGN KEY (`parent_case_id`) REFERENCES `dc_cases` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_choice_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_choice_lists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_version_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(80) NOT NULL,
  `source_type` varchar(12) NOT NULL DEFAULT 'static',
  `dataset_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_choice_lists_form_version_id_name_unique` (`form_version_id`,`name`),
  KEY `dc_choice_lists_dataset_id_foreign` (`dataset_id`),
  CONSTRAINT `dc_choice_lists_dataset_id_foreign` FOREIGN KEY (`dataset_id`) REFERENCES `dc_datasets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_choice_lists_form_version_id_foreign` FOREIGN KEY (`form_version_id`) REFERENCES `dc_form_versions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_choices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_choices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `list_id` bigint(20) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `label` varchar(255) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `parent_name` varchar(100) DEFAULT NULL,
  `filter_attrs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filter_attrs`)),
  `media_id` bigint(20) unsigned DEFAULT NULL,
  `concept_code` varchar(40) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_choices_list_id_name_unique` (`list_id`,`name`),
  KEY `dc_choices_media_id_foreign` (`media_id`),
  CONSTRAINT `dc_choices_list_id_foreign` FOREIGN KEY (`list_id`) REFERENCES `dc_choice_lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dc_choices_media_id_foreign` FOREIGN KEY (`media_id`) REFERENCES `media_attachments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_consents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_consents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `submission_id` bigint(20) unsigned DEFAULT NULL,
  `subject_type` varchar(60) NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `consent_text_version` varchar(30) NOT NULL,
  `scope` text NOT NULL,
  `method` varchar(16) NOT NULL,
  `given` tinyint(1) NOT NULL,
  `given_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `obtained_by` bigint(20) unsigned DEFAULT NULL,
  `witness_name` varchar(160) DEFAULT NULL,
  `guardian_name` varchar(160) DEFAULT NULL,
  `guardian_relationship` varchar(40) DEFAULT NULL,
  `signature_media_id` bigint(20) unsigned DEFAULT NULL,
  `withdrawn_at` timestamp NULL DEFAULT NULL,
  `withdrawal_note` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dc_consents_project_id_foreign` (`project_id`),
  KEY `dc_consents_submission_id_foreign` (`submission_id`),
  KEY `dc_consents_obtained_by_foreign` (`obtained_by`),
  KEY `dc_consents_signature_media_id_foreign` (`signature_media_id`),
  KEY `ix_dc_consent_subject` (`subject_type`,`subject_id`,`project_id`),
  CONSTRAINT `dc_consents_obtained_by_foreign` FOREIGN KEY (`obtained_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_consents_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `dc_projects` (`id`),
  CONSTRAINT `dc_consents_signature_media_id_foreign` FOREIGN KEY (`signature_media_id`) REFERENCES `media_attachments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_consents_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `dc_submissions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_data_elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_data_elements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(60) NOT NULL,
  `name` varchar(200) NOT NULL,
  `value_type` varchar(12) NOT NULL,
  `category_combo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`category_combo`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_data_elements_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_dataset_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_dataset_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dataset_id` bigint(20) unsigned NOT NULL,
  `record_key` varchar(80) NOT NULL,
  `label` varchar(255) NOT NULL,
  `attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attributes`)),
  `subject_type` varchar(60) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `row_version` bigint(20) unsigned NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_dataset_records_dataset_id_record_key_unique` (`dataset_id`,`record_key`),
  KEY `ix_dc_dsrec_delta` (`dataset_id`,`row_version`),
  CONSTRAINT `dc_dataset_records_dataset_id_foreign` FOREIGN KEY (`dataset_id`) REFERENCES `dc_datasets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_datasets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_datasets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(60) NOT NULL,
  `name` varchar(160) NOT NULL,
  `source_type` varchar(12) NOT NULL,
  `source_ref` varchar(120) DEFAULT NULL,
  `refresh_cron` varchar(40) DEFAULT NULL,
  `version` bigint(20) unsigned NOT NULL DEFAULT 1,
  `last_built_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_datasets_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_uuid` char(36) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `platform` varchar(12) DEFAULT NULL,
  `model` varchar(80) DEFAULT NULL,
  `os_version` varchar(30) DEFAULT NULL,
  `app_version` varchar(30) DEFAULT NULL,
  `push_token` text DEFAULT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'active',
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_seen_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_devices_device_uuid_unique` (`device_uuid`),
  KEY `dc_devices_user_id_foreign` (`user_id`),
  CONSTRAINT `dc_devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_external_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_external_forms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `integration_id` bigint(20) unsigned NOT NULL,
  `form_id` bigint(20) unsigned NOT NULL,
  `external_uid` varchar(120) NOT NULL,
  `external_version` varchar(60) DEFAULT NULL,
  `field_name_map` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`field_name_map`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_external_forms_integration_id_external_uid_unique` (`integration_id`,`external_uid`),
  KEY `dc_external_forms_form_id_foreign` (`form_id`),
  CONSTRAINT `dc_external_forms_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `dc_forms` (`id`),
  CONSTRAINT `dc_external_forms_integration_id_foreign` FOREIGN KEY (`integration_id`) REFERENCES `dc_integrations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_external_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_external_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `external_form_id` bigint(20) unsigned NOT NULL,
  `external_id` varchar(120) NOT NULL,
  `submission_id` bigint(20) unsigned DEFAULT NULL,
  `raw_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`raw_payload`)),
  `pulled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(10) NOT NULL DEFAULT 'pending',
  `error_detail` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_external_submissions_external_form_id_external_id_unique` (`external_form_id`,`external_id`),
  KEY `dc_external_submissions_submission_id_foreign` (`submission_id`),
  CONSTRAINT `dc_external_submissions_external_form_id_foreign` FOREIGN KEY (`external_form_id`) REFERENCES `dc_external_forms` (`id`),
  CONSTRAINT `dc_external_submissions_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `dc_submissions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_field_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_field_mappings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_version_id` bigint(20) unsigned NOT NULL,
  `field_id` bigint(20) unsigned NOT NULL,
  `target_table` varchar(80) NOT NULL,
  `target_column` varchar(80) NOT NULL,
  `transform` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_field_mappings_unique` (`form_version_id`,`field_id`,`target_table`,`target_column`),
  KEY `dc_field_mappings_field_id_foreign` (`field_id`),
  CONSTRAINT `dc_field_mappings_field_id_foreign` FOREIGN KEY (`field_id`) REFERENCES `dc_form_fields` (`id`),
  CONSTRAINT `dc_field_mappings_form_version_id_foreign` FOREIGN KEY (`form_version_id`) REFERENCES `dc_form_versions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_field_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_field_types` (
  `code` varchar(40) NOT NULL,
  `family` varchar(10) NOT NULL,
  `catalogue_id` varchar(12) DEFAULT NULL,
  `value_kind` varchar(12) NOT NULL,
  `is_input` tinyint(1) NOT NULL DEFAULT 1,
  `is_container` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_form_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_form_access` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned NOT NULL,
  `grantee_type` varchar(10) NOT NULL,
  `grantee_id` bigint(20) unsigned NOT NULL,
  `permission` varchar(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_form_access_form_id_grantee_type_grantee_id_permission_unique` (`form_id`,`grantee_type`,`grantee_id`,`permission`),
  CONSTRAINT `dc_form_access_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `dc_forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_form_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_form_fields` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_version_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `field_type` varchar(40) NOT NULL,
  `name` varchar(80) NOT NULL,
  `label` text DEFAULT NULL,
  `hint` text DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `choice_list_id` bigint(20) unsigned DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `required_message` text DEFAULT NULL,
  `relevant_expr` text DEFAULT NULL,
  `constraint_expr` text DEFAULT NULL,
  `constraint_msg` text DEFAULT NULL,
  `calculation_expr` text DEFAULT NULL,
  `default_value` text DEFAULT NULL,
  `is_read_only` tinyint(1) NOT NULL DEFAULT 0,
  `appearance` varchar(120) DEFAULT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `repeat_count_expr` text DEFAULT NULL,
  `sensitivity` varchar(12) NOT NULL DEFAULT 'none',
  `is_identifier` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_form_fields_form_version_id_name_unique` (`form_version_id`,`name`),
  KEY `dc_form_fields_parent_id_foreign` (`parent_id`),
  KEY `dc_form_fields_choice_list_id_foreign` (`choice_list_id`),
  KEY `ix_dc_fields_version` (`form_version_id`,`parent_id`,`position`),
  KEY `dc_form_fields_field_type_foreign` (`field_type`),
  CONSTRAINT `dc_form_fields_choice_list_id_foreign` FOREIGN KEY (`choice_list_id`) REFERENCES `dc_choice_lists` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_form_fields_field_type_foreign` FOREIGN KEY (`field_type`) REFERENCES `dc_field_types` (`code`),
  CONSTRAINT `dc_form_fields_form_version_id_foreign` FOREIGN KEY (`form_version_id`) REFERENCES `dc_form_versions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dc_form_fields_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `dc_form_fields` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_form_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_form_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned NOT NULL,
  `version_no` int(11) NOT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'draft',
  `compiled_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`compiled_json`)),
  `source_media_id` bigint(20) unsigned DEFAULT NULL,
  `checksum` char(64) DEFAULT NULL,
  `change_note` text DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `published_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_form_versions_form_id_version_no_unique` (`form_id`,`version_no`),
  KEY `dc_form_versions_source_media_id_foreign` (`source_media_id`),
  KEY `dc_form_versions_published_by_foreign` (`published_by`),
  CONSTRAINT `dc_form_versions_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `dc_forms` (`id`),
  CONSTRAINT `dc_form_versions_published_by_foreign` FOREIGN KEY (`published_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_form_versions_source_media_id_foreign` FOREIGN KEY (`source_media_id`) REFERENCES `media_attachments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_forms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `code` varchar(60) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `record_kind` varchar(20) NOT NULL DEFAULT 'record',
  `subject_type` varchar(60) DEFAULT NULL,
  `allow_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `allow_offline` tinyint(1) NOT NULL DEFAULT 1,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `current_version_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_forms_uuid_unique` (`uuid`),
  UNIQUE KEY `dc_forms_code_unique` (`code`),
  KEY `dc_forms_project_id_foreign` (`project_id`),
  KEY `dc_forms_current_version_id_foreign` (`current_version_id`),
  CONSTRAINT `dc_forms_current_version_id_foreign` FOREIGN KEY (`current_version_id`) REFERENCES `dc_form_versions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_forms_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `dc_projects` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_geo_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_geo_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `answer_id` bigint(20) unsigned NOT NULL,
  `geom_type` varchar(8) NOT NULL,
  `lat` decimal(9,6) DEFAULT NULL,
  `lng` decimal(9,6) DEFAULT NULL,
  `altitude_m` decimal(8,2) DEFAULT NULL,
  `accuracy_m` decimal(8,2) DEFAULT NULL,
  `gnss_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gnss_meta`)),
  `geojson` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`geojson`)),
  PRIMARY KEY (`id`),
  KEY `dc_geo_values_answer_id_foreign` (`answer_id`),
  CONSTRAINT `dc_geo_values_answer_id_foreign` FOREIGN KEY (`answer_id`) REFERENCES `dc_answers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_import_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_import_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned DEFAULT NULL,
  `dataset_id` bigint(20) unsigned DEFAULT NULL,
  `media_id` bigint(20) unsigned NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'queued',
  `rows_total` int(11) DEFAULT NULL,
  `rows_ok` int(11) DEFAULT NULL,
  `rows_failed` int(11) DEFAULT NULL,
  `error_report` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`error_report`)),
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `dc_import_jobs_form_id_foreign` (`form_id`),
  KEY `dc_import_jobs_dataset_id_foreign` (`dataset_id`),
  KEY `dc_import_jobs_media_id_foreign` (`media_id`),
  KEY `dc_import_jobs_created_by_foreign` (`created_by`),
  CONSTRAINT `dc_import_jobs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_import_jobs_dataset_id_foreign` FOREIGN KEY (`dataset_id`) REFERENCES `dc_datasets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_import_jobs_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `dc_forms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_import_jobs_media_id_foreign` FOREIGN KEY (`media_id`) REFERENCES `media_attachments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_integrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_integrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(16) NOT NULL,
  `name` varchar(120) NOT NULL,
  `base_url` varchar(255) DEFAULT NULL,
  `credentials` text DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'active',
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `last_cursor` varchar(120) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) NOT NULL,
  `name` varchar(160) NOT NULL,
  `module` varchar(40) NOT NULL,
  `purpose` text NOT NULL,
  `lawful_basis` varchar(30) NOT NULL,
  `retention_months` int(11) DEFAULT NULL,
  `starts_on` date DEFAULT NULL,
  `ends_on` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `public_collection_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `consent_text` text DEFAULT NULL,
  `consent_ack_label` varchar(500) DEFAULT NULL,
  `consent_require_signature` tinyint(1) NOT NULL DEFAULT 0,
  `brand_logo_path` varchar(500) DEFAULT NULL,
  `brand_footer` varchar(1000) DEFAULT NULL,
  `default_locale` varchar(10) NOT NULL DEFAULT 'en',
  `owner_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_projects_code_unique` (`code`),
  KEY `dc_projects_owner_user_id_foreign` (`owner_user_id`),
  CONSTRAINT `dc_projects_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_promotion_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_promotion_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint(20) unsigned NOT NULL,
  `target_table` varchar(80) NOT NULL,
  `target_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(8) NOT NULL,
  `status` varchar(8) NOT NULL,
  `error_detail` text DEFAULT NULL,
  `promoted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `promoted_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dc_promotion_log_submission_id_foreign` (`submission_id`),
  KEY `dc_promotion_log_promoted_by_foreign` (`promoted_by`),
  CONSTRAINT `dc_promotion_log_promoted_by_foreign` FOREIGN KEY (`promoted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_promotion_log_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `dc_submissions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_quality_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_quality_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `rule_type` varchar(20) NOT NULL,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`config`)),
  `severity` varchar(8) NOT NULL DEFAULT 'warn',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `dc_quality_rules_form_id_foreign` (`form_id`),
  CONSTRAINT `dc_quality_rules_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `dc_forms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_repeat_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_repeat_instances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint(20) unsigned NOT NULL,
  `field_id` bigint(20) unsigned NOT NULL,
  `parent_instance_id` bigint(20) unsigned DEFAULT NULL,
  `index_no` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_repeat_instances_unique` (`submission_id`,`field_id`,`parent_instance_id`,`index_no`),
  KEY `dc_repeat_instances_field_id_foreign` (`field_id`),
  KEY `dc_repeat_instances_parent_instance_id_foreign` (`parent_instance_id`),
  CONSTRAINT `dc_repeat_instances_field_id_foreign` FOREIGN KEY (`field_id`) REFERENCES `dc_form_fields` (`id`),
  CONSTRAINT `dc_repeat_instances_parent_instance_id_foreign` FOREIGN KEY (`parent_instance_id`) REFERENCES `dc_repeat_instances` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_repeat_instances_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `dc_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_sensor_readings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_sensor_readings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint(20) unsigned DEFAULT NULL,
  `device_id` bigint(20) unsigned DEFAULT NULL,
  `sensor_type` varchar(30) NOT NULL,
  `value_number` decimal(14,4) DEFAULT NULL,
  `unit` varchar(12) DEFAULT NULL,
  `taken_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `dc_sensor_readings_submission_id_foreign` (`submission_id`),
  KEY `dc_sensor_readings_device_id_foreign` (`device_id`),
  KEY `ix_dc_sensor` (`sensor_type`,`taken_at`),
  CONSTRAINT `dc_sensor_readings_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `dc_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_sensor_readings_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `dc_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_submission_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_submission_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint(20) unsigned NOT NULL,
  `answer_id` bigint(20) unsigned DEFAULT NULL,
  `media_id` bigint(20) unsigned NOT NULL,
  `kind` varchar(12) NOT NULL,
  `exif` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`exif`)),
  `sha256` char(64) DEFAULT NULL,
  `captured_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dc_submission_files_submission_id_foreign` (`submission_id`),
  KEY `dc_submission_files_answer_id_foreign` (`answer_id`),
  KEY `dc_submission_files_media_id_foreign` (`media_id`),
  CONSTRAINT `dc_submission_files_answer_id_foreign` FOREIGN KEY (`answer_id`) REFERENCES `dc_answers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_submission_files_media_id_foreign` FOREIGN KEY (`media_id`) REFERENCES `media_attachments` (`id`),
  CONSTRAINT `dc_submission_files_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `dc_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_submission_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_submission_flags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint(20) unsigned NOT NULL,
  `rule_id` bigint(20) unsigned DEFAULT NULL,
  `field_name` varchar(80) DEFAULT NULL,
  `severity` varchar(8) NOT NULL,
  `message` text NOT NULL,
  `raised_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dc_submission_flags_rule_id_foreign` (`rule_id`),
  KEY `dc_submission_flags_resolved_by_foreign` (`resolved_by`),
  KEY `ix_dc_flags_open` (`submission_id`,`resolved_at`),
  CONSTRAINT `dc_submission_flags_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_submission_flags_rule_id_foreign` FOREIGN KEY (`rule_id`) REFERENCES `dc_quality_rules` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_submission_flags_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `dc_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_submission_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_submission_reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint(20) unsigned NOT NULL,
  `reviewer_id` bigint(20) unsigned NOT NULL,
  `level_no` smallint(6) NOT NULL DEFAULT 1,
  `decision` varchar(16) NOT NULL,
  `comment` text DEFAULT NULL,
  `signature_media_id` bigint(20) unsigned DEFAULT NULL,
  `decided_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `dc_submission_reviews_submission_id_foreign` (`submission_id`),
  KEY `dc_submission_reviews_reviewer_id_foreign` (`reviewer_id`),
  KEY `dc_submission_reviews_signature_media_id_foreign` (`signature_media_id`),
  CONSTRAINT `dc_submission_reviews_reviewer_id_foreign` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`),
  CONSTRAINT `dc_submission_reviews_signature_media_id_foreign` FOREIGN KEY (`signature_media_id`) REFERENCES `media_attachments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_submission_reviews_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `dc_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_submission_revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_submission_revisions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint(20) unsigned NOT NULL,
  `revision_no` int(11) NOT NULL,
  `data_before` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data_before`)),
  `data_after` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data_after`)),
  `reason` text NOT NULL,
  `edited_by` bigint(20) unsigned NOT NULL,
  `edited_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_submission_revisions_submission_id_revision_no_unique` (`submission_id`,`revision_no`),
  KEY `dc_submission_revisions_edited_by_foreign` (`edited_by`),
  CONSTRAINT `dc_submission_revisions_edited_by_foreign` FOREIGN KEY (`edited_by`) REFERENCES `users` (`id`),
  CONSTRAINT `dc_submission_revisions_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `dc_submissions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_uuid` char(36) NOT NULL,
  `supersedes_uuid` char(36) DEFAULT NULL,
  `form_id` bigint(20) unsigned NOT NULL,
  `form_version_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `parent_submission_id` bigint(20) unsigned DEFAULT NULL,
  `case_id` bigint(20) unsigned DEFAULT NULL,
  `assignment_id` bigint(20) unsigned DEFAULT NULL,
  `subject_type` varchar(60) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `collector_id` bigint(20) unsigned DEFAULT NULL,
  `device_id` bigint(20) unsigned DEFAULT NULL,
  `sync_batch_id` bigint(20) unsigned DEFAULT NULL,
  `channel` varchar(16) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'submitted',
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `language` varchar(8) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `received_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `duration_sec` int(11) DEFAULT NULL,
  `geo_lat` decimal(9,6) DEFAULT NULL,
  `geo_lng` decimal(9,6) DEFAULT NULL,
  `geo_accuracy_m` decimal(8,2) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `app_version` varchar(30) DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `data_hash` char(64) DEFAULT NULL,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `retain_until` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_submissions_client_uuid_unique` (`client_uuid`),
  KEY `dc_submissions_form_version_id_foreign` (`form_version_id`),
  KEY `dc_submissions_project_id_foreign` (`project_id`),
  KEY `dc_submissions_parent_submission_id_foreign` (`parent_submission_id`),
  KEY `dc_submissions_case_id_foreign` (`case_id`),
  KEY `dc_submissions_assignment_id_foreign` (`assignment_id`),
  KEY `dc_submissions_device_id_foreign` (`device_id`),
  KEY `dc_submissions_sync_batch_id_foreign` (`sync_batch_id`),
  KEY `ix_dc_sub_form` (`form_id`,`status`,`received_at`),
  KEY `ix_dc_sub_subject` (`subject_type`,`subject_id`),
  KEY `ix_dc_sub_collector` (`collector_id`,`received_at`),
  KEY `dc_submissions_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `dc_submissions_assignment_id_foreign` FOREIGN KEY (`assignment_id`) REFERENCES `dc_assignments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_submissions_case_id_foreign` FOREIGN KEY (`case_id`) REFERENCES `dc_cases` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_submissions_collector_id_foreign` FOREIGN KEY (`collector_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_submissions_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `dc_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_submissions_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `dc_forms` (`id`),
  CONSTRAINT `dc_submissions_form_version_id_foreign` FOREIGN KEY (`form_version_id`) REFERENCES `dc_form_versions` (`id`),
  CONSTRAINT `dc_submissions_parent_submission_id_foreign` FOREIGN KEY (`parent_submission_id`) REFERENCES `dc_submissions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_submissions_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `dc_projects` (`id`),
  CONSTRAINT `dc_submissions_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_submissions_sync_batch_id_foreign` FOREIGN KEY (`sync_batch_id`) REFERENCES `dc_sync_batches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_sync_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_sync_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `direction` varchar(4) NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `finished_at` timestamp NULL DEFAULT NULL,
  `records_total` int(11) DEFAULT NULL,
  `records_ok` int(11) DEFAULT NULL,
  `records_failed` int(11) DEFAULT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'running',
  `error_detail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`error_detail`)),
  PRIMARY KEY (`id`),
  KEY `dc_sync_batches_device_id_foreign` (`device_id`),
  KEY `dc_sync_batches_user_id_foreign` (`user_id`),
  CONSTRAINT `dc_sync_batches_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `dc_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dc_sync_batches_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dc_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dc_translations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(12) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `lang` varchar(8) NOT NULL,
  `attribute` varchar(20) NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dc_translations_entity_type_entity_id_lang_attribute_unique` (`entity_type`,`entity_id`,`lang`,`attribute`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `attachable_type` varchar(60) DEFAULT NULL,
  `attachable_id` bigint(20) unsigned DEFAULT NULL,
  `collection` varchar(40) NOT NULL DEFAULT 'default',
  `disk` varchar(20) NOT NULL DEFAULT 'private',
  `path` varchar(500) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(120) NOT NULL,
  `extension` varchar(12) DEFAULT NULL,
  `size_bytes` bigint(20) unsigned NOT NULL,
  `sha256` char(64) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `duration_sec` decimal(10,2) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `caption` varchar(500) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `visibility` varchar(10) NOT NULL DEFAULT 'private',
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `scan_status` varchar(10) NOT NULL DEFAULT 'pending',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_attachments_uuid_unique` (`uuid`),
  KEY `media_attachments_uploaded_by_foreign` (`uploaded_by`),
  KEY `media_attachments_created_by_foreign` (`created_by`),
  KEY `media_attachments_updated_by_foreign` (`updated_by`),
  KEY `ix_media_attachable` (`attachable_type`,`attachable_id`,`collection`,`sort_order`),
  KEY `ix_media_sha256` (`sha256`),
  KEY `ix_media_orphans` (`created_at`),
  CONSTRAINT `media_attachments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_attachments_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `platform_analysis_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_analysis_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `platform_dataset_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `prompt` text NOT NULL,
  `generated_code` longtext DEFAULT NULL,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `chart_spec` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`chart_spec`)),
  `chart_image_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `platform_analysis_runs_platform_dataset_id_foreign` (`platform_dataset_id`),
  KEY `platform_analysis_runs_user_id_foreign` (`user_id`),
  CONSTRAINT `platform_analysis_runs_platform_dataset_id_foreign` FOREIGN KEY (`platform_dataset_id`) REFERENCES `platform_datasets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `platform_analysis_runs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `platform_dataset_columns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_dataset_columns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `platform_dataset_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `position` smallint(5) unsigned NOT NULL,
  `inferred_type` varchar(30) NOT NULL,
  `sample_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sample_values`)),
  `null_count` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `platform_dataset_columns_platform_dataset_id_foreign` (`platform_dataset_id`),
  CONSTRAINT `platform_dataset_columns_platform_dataset_id_foreign` FOREIGN KEY (`platform_dataset_id`) REFERENCES `platform_datasets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `platform_datasets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_datasets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `name` varchar(200) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `storage_path` varchar(500) NOT NULL,
  `workspace_path` varchar(500) NOT NULL,
  `row_count` int(10) unsigned NOT NULL DEFAULT 0,
  `column_count` int(10) unsigned NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'ready',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_datasets_uuid_unique` (`uuid`),
  KEY `platform_datasets_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `platform_datasets_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `submission_drafts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `submission_drafts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `form_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `device_token` varchar(64) DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `submission_drafts_project_id_user_id_unique` (`project_id`,`user_id`),
  UNIQUE KEY `submission_drafts_project_id_device_token_unique` (`project_id`,`device_token`),
  KEY `submission_drafts_form_id_foreign` (`form_id`),
  KEY `submission_drafts_user_id_foreign` (`user_id`),
  CONSTRAINT `submission_drafts_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `dc_forms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `submission_drafts_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `dc_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `submission_drafts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tilkit_consensus_forecasts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tilkit_consensus_forecasts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint(20) unsigned NOT NULL,
  `season_id` bigint(20) unsigned NOT NULL,
  `label` varchar(120) NOT NULL,
  `community_tercile` tinyint(3) unsigned DEFAULT NULL,
  `kmsa_tercile` tinyint(3) unsigned DEFAULT NULL,
  `integrated_tercile` tinyint(3) unsigned DEFAULT NULL,
  `advisory_message` text DEFAULT NULL,
  `tally_snapshot_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tilkit_consensus_forecasts_site_id_foreign` (`site_id`),
  KEY `tilkit_consensus_forecasts_season_id_foreign` (`season_id`),
  KEY `tilkit_consensus_forecasts_tally_snapshot_id_foreign` (`tally_snapshot_id`),
  KEY `tilkit_consensus_forecasts_created_by_foreign` (`created_by`),
  CONSTRAINT `tilkit_consensus_forecasts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tilkit_consensus_forecasts_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `tilkit_seasons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tilkit_consensus_forecasts_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `tilkit_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tilkit_consensus_forecasts_tally_snapshot_id_foreign` FOREIGN KEY (`tally_snapshot_id`) REFERENCES `tilkit_tally_snapshots` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tilkit_indicator_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tilkit_indicator_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `category` varchar(8) NOT NULL,
  `type` varchar(8) DEFAULT NULL,
  `sub_type` varchar(8) DEFAULT NULL,
  `label` varchar(200) DEFAULT NULL,
  `usage_notes` text DEFAULT NULL,
  `prior_multiplier` decimal(5,3) DEFAULT NULL,
  `preferred_code` varchar(32) DEFAULT NULL,
  `deprecated_at` timestamp NULL DEFAULT NULL,
  `protocol_version` varchar(32) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tilkit_indicator_codes_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tilkit_indicator_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tilkit_indicator_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `indicator_id` bigint(20) unsigned NOT NULL,
  `site_id` bigint(20) unsigned NOT NULL,
  `season_id` bigint(20) unsigned NOT NULL,
  `protocol_version` varchar(32) NOT NULL,
  `sighted` tinyint(1) NOT NULL DEFAULT 0,
  `confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `confirmed_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `approved` tinyint(1) NOT NULL DEFAULT 0,
  `approved_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `validation_status` varchar(40) NOT NULL DEFAULT 'provisional_emerging',
  `tercile_forecast` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `current_reliability` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `decision_relevance` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `onset_earliest` date DEFAULT NULL,
  `onset_latest` date DEFAULT NULL,
  `cessation_earliest` date DEFAULT NULL,
  `cessation_latest` date DEFAULT NULL,
  `distribution_forecast` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `actual_onset` date DEFAULT NULL,
  `actual_cessation` date DEFAULT NULL,
  `actual_distribution` tinyint(3) unsigned DEFAULT NULL,
  `actual_tercile` tinyint(3) unsigned DEFAULT NULL,
  `season_outcome` varchar(20) DEFAULT NULL,
  `do_no_harm` tinyint(1) NOT NULL DEFAULT 0,
  `consent_level` tinyint(3) unsigned DEFAULT NULL,
  `consent_credit` varchar(200) DEFAULT NULL,
  `demographics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`demographics`)),
  `extended_attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extended_attributes`)),
  `entered_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tilkit_indicator_records_indicator_id_site_id_season_id_unique` (`indicator_id`,`site_id`,`season_id`),
  UNIQUE KEY `tilkit_indicator_records_uuid_unique` (`uuid`),
  KEY `tilkit_indicator_records_site_id_foreign` (`site_id`),
  KEY `tilkit_indicator_records_season_id_foreign` (`season_id`),
  KEY `tilkit_indicator_records_entered_by_foreign` (`entered_by`),
  KEY `tilkit_indicator_records_confirmed_by_user_id_foreign` (`confirmed_by_user_id`),
  KEY `tilkit_indicator_records_approved_by_user_id_foreign` (`approved_by_user_id`),
  KEY `tilkit_indicator_records_verified_by_user_id_foreign` (`verified_by_user_id`),
  CONSTRAINT `tilkit_indicator_records_approved_by_user_id_foreign` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tilkit_indicator_records_confirmed_by_user_id_foreign` FOREIGN KEY (`confirmed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tilkit_indicator_records_entered_by_foreign` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tilkit_indicator_records_indicator_id_foreign` FOREIGN KEY (`indicator_id`) REFERENCES `tilkit_indicators` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tilkit_indicator_records_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `tilkit_seasons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tilkit_indicator_records_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `tilkit_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tilkit_indicator_records_verified_by_user_id_foreign` FOREIGN KEY (`verified_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tilkit_indicators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tilkit_indicators` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `indicator_uid` varchar(32) NOT NULL,
  `indicator_code_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `local_name` varchar(200) DEFAULT NULL,
  `local_description` text DEFAULT NULL,
  `local_prediction` text DEFAULT NULL,
  `locality` varchar(200) DEFAULT NULL,
  `ethnic_community` varchar(120) DEFAULT NULL,
  `language` varchar(40) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tilkit_indicators_indicator_uid_unique` (`indicator_uid`),
  KEY `tilkit_indicators_indicator_code_id_foreign` (`indicator_code_id`),
  KEY `tilkit_indicators_project_id_foreign` (`project_id`),
  CONSTRAINT `tilkit_indicators_indicator_code_id_foreign` FOREIGN KEY (`indicator_code_id`) REFERENCES `tilkit_indicator_codes` (`id`),
  CONSTRAINT `tilkit_indicators_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `dc_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tilkit_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tilkit_programs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `name` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tilkit_programs_project_id_name_unique` (`project_id`,`name`),
  CONSTRAINT `tilkit_programs_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `dc_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tilkit_season_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tilkit_season_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint(20) unsigned NOT NULL,
  `season_id` bigint(20) unsigned NOT NULL,
  `use_prior_multiplier` tinyint(1) NOT NULL DEFAULT 0,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `kmsa_outlook_tercile` tinyint(3) unsigned DEFAULT NULL,
  `ndma_drought_phase` varchar(40) DEFAULT NULL,
  `ndma_vci3m` decimal(5,2) DEFAULT NULL,
  `protocol_version` varchar(32) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tilkit_season_settings_site_id_season_id_unique` (`site_id`,`season_id`),
  KEY `tilkit_season_settings_season_id_foreign` (`season_id`),
  CONSTRAINT `tilkit_season_settings_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `tilkit_seasons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tilkit_season_settings_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `tilkit_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tilkit_seasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tilkit_seasons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `label` varchar(40) NOT NULL,
  `starts_on` date DEFAULT NULL,
  `ends_on` date DEFAULT NULL,
  `protocol_version` varchar(32) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tilkit_seasons_project_id_label_unique` (`project_id`,`label`),
  KEY `tilkit_seasons_program_id_foreign` (`program_id`),
  CONSTRAINT `tilkit_seasons_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `tilkit_programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tilkit_seasons_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `dc_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tilkit_sightings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tilkit_sightings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `indicator_record_id` bigint(20) unsigned NOT NULL,
  `sighted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `place_text` varchar(500) DEFAULT NULL,
  `landmark` varchar(200) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `observed_by` varchar(200) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tilkit_sightings_uuid_unique` (`uuid`),
  KEY `tilkit_sightings_indicator_record_id_foreign` (`indicator_record_id`),
  KEY `tilkit_sightings_created_by_foreign` (`created_by`),
  CONSTRAINT `tilkit_sightings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tilkit_sightings_indicator_record_id_foreign` FOREIGN KEY (`indicator_record_id`) REFERENCES `tilkit_indicator_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tilkit_sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tilkit_sites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `continent` varchar(60) DEFAULT NULL,
  `ward` varchar(120) DEFAULT NULL,
  `county` varchar(120) DEFAULT NULL,
  `country` varchar(2) NOT NULL DEFAULT 'KE',
  `country_name` varchar(120) DEFAULT NULL,
  `primary_language` varchar(40) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tilkit_sites_project_id_name_unique` (`project_id`,`name`),
  KEY `tilkit_sites_program_id_foreign` (`program_id`),
  CONSTRAINT `tilkit_sites_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `tilkit_programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tilkit_sites_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `dc_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tilkit_tally_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tilkit_tally_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint(20) unsigned NOT NULL,
  `season_id` bigint(20) unsigned NOT NULL,
  `protocol_version` varchar(32) NOT NULL,
  `use_prior_multiplier` tinyint(1) NOT NULL DEFAULT 0,
  `settings_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings_payload`)),
  `results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`results`)),
  `reconciliation` varchar(16) NOT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tilkit_tally_snapshots_season_id_foreign` (`season_id`),
  KEY `tilkit_tally_snapshots_created_by_foreign` (`created_by`),
  KEY `tilkit_tally_snapshots_site_id_season_id_published_at_index` (`site_id`,`season_id`,`published_at`),
  CONSTRAINT `tilkit_tally_snapshots_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tilkit_tally_snapshots_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `tilkit_seasons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tilkit_tally_snapshots_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `tilkit_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tilkit_weight_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tilkit_weight_reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `indicator_record_id` bigint(20) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `climate_flag` varchar(20) NOT NULL DEFAULT 'STRONG',
  `notes` text DEFAULT NULL,
  `assigned_lead_itk_user_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_technical_user_id` bigint(20) unsigned DEFAULT NULL,
  `signed_off_by` bigint(20) unsigned DEFAULT NULL,
  `signed_off_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tilkit_weight_reviews_indicator_record_id_climate_flag_unique` (`indicator_record_id`,`climate_flag`),
  KEY `tilkit_weight_reviews_assigned_lead_itk_user_id_foreign` (`assigned_lead_itk_user_id`),
  KEY `tilkit_weight_reviews_assigned_technical_user_id_foreign` (`assigned_technical_user_id`),
  KEY `tilkit_weight_reviews_signed_off_by_foreign` (`signed_off_by`),
  CONSTRAINT `tilkit_weight_reviews_assigned_lead_itk_user_id_foreign` FOREIGN KEY (`assigned_lead_itk_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tilkit_weight_reviews_assigned_technical_user_id_foreign` FOREIGN KEY (`assigned_technical_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tilkit_weight_reviews_indicator_record_id_foreign` FOREIGN KEY (`indicator_record_id`) REFERENCES `tilkit_indicator_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tilkit_weight_reviews_signed_off_by_foreign` FOREIGN KEY (`signed_off_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tilkit_workbook_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tilkit_workbook_media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `indicator_record_id` bigint(20) unsigned NOT NULL,
  `column_letter` varchar(3) NOT NULL DEFAULT 'Y',
  `storage_path` varchar(255) NOT NULL,
  `mime_type` varchar(80) DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tilkit_workbook_media_indicator_record_id_foreign` (`indicator_record_id`),
  CONSTRAINT `tilkit_workbook_media_indicator_record_id_foreign` FOREIGN KEY (`indicator_record_id`) REFERENCES `tilkit_indicator_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tilkit_workbook_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tilkit_workbook_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `indicator_record_id` bigint(20) unsigned NOT NULL,
  `column_letter` varchar(3) NOT NULL,
  `field_key` varchar(80) NOT NULL,
  `is_derived` tinyint(1) NOT NULL DEFAULT 0,
  `value_text` text DEFAULT NULL,
  `value_number` decimal(16,6) DEFAULT NULL,
  `value_date` date DEFAULT NULL,
  `value_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`value_json`)),
  `protocol_version` varchar(32) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tilkit_workbook_values_indicator_record_id_column_letter_unique` (`indicator_record_id`,`column_letter`),
  KEY `tilkit_workbook_values_indicator_record_id_is_derived_index` (`indicator_record_id`,`is_derived`),
  CONSTRAINT `tilkit_workbook_values_indicator_record_id_foreign` FOREIGN KEY (`indicator_record_id`) REFERENCES `tilkit_indicator_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tool_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tool_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `subject_type` varchar(80) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tool_audit_logs_user_id_foreign` (`user_id`),
  KEY `tool_audit_logs_project_id_created_at_index` (`project_id`,`created_at`),
  CONSTRAINT `tool_audit_logs_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `dc_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tool_audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tool_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tool_invitations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `email` varchar(190) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'collector',
  `can_export` tinyint(1) DEFAULT NULL,
  `can_publish_forms` tinyint(1) DEFAULT NULL,
  `token` char(36) NOT NULL,
  `invited_by` bigint(20) unsigned NOT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tool_invitations_project_id_email_unique` (`project_id`,`email`),
  UNIQUE KEY `tool_invitations_token_unique` (`token`),
  KEY `tool_invitations_invited_by_foreign` (`invited_by`),
  CONSTRAINT `tool_invitations_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tool_invitations_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `dc_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tool_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tool_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'member',
  `tilkit_persona` varchar(40) DEFAULT NULL,
  `can_export` tinyint(1) DEFAULT NULL,
  `can_publish_forms` tinyint(1) DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tool_members_project_id_user_id_unique` (`project_id`,`user_id`),
  KEY `tool_members_user_id_role_index` (`user_id`,`role`),
  CONSTRAINT `tool_members_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `dc_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tool_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tool_scheduled_exports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tool_scheduled_exports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `recipient_emails` varchar(1000) NOT NULL,
  `frequency` varchar(20) NOT NULL DEFAULT 'weekly',
  `last_run_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tool_scheduled_exports_project_id_foreign` (`project_id`),
  CONSTRAINT `tool_scheduled_exports_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `dc_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `user_type` varchar(12) NOT NULL DEFAULT 'staff',
  `platform_role` varchar(20) NOT NULL DEFAULT 'member',
  `person_type` varchar(60) DEFAULT NULL,
  `person_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(160) NOT NULL,
  `username` varchar(60) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'pending',
  `mfa_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `failed_login_count` smallint(6) NOT NULL DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `locale` varchar(8) NOT NULL DEFAULT 'en',
  `timezone` varchar(40) NOT NULL DEFAULT 'Africa/Nairobi',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_uuid_unique` (`uuid`),
  UNIQUE KEY `ux_users_email` (`email`),
  UNIQUE KEY `ux_users_username` (`username`),
  UNIQUE KEY `ux_users_phone` (`phone`),
  KEY `ix_users_person` (`person_type`,`person_id`),
  KEY `ix_users_status` (`status`,`user_type`),
  KEY `users_created_by_foreign` (`created_by`),
  KEY `users_updated_by_foreign` (`updated_by`),
  CONSTRAINT `users_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


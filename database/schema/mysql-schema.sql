/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!50717 SELECT COUNT(*) INTO @rocksdb_has_p_s_session_variables FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'performance_schema' AND TABLE_NAME = 'session_variables' */;
/*!50717 SET @rocksdb_get_is_supported = IF (@rocksdb_has_p_s_session_variables, 'SELECT COUNT(*) INTO @rocksdb_is_supported FROM performance_schema.session_variables WHERE VARIABLE_NAME=\'rocksdb_bulk_load\'', 'SELECT 0') */;
/*!50717 PREPARE s FROM @rocksdb_get_is_supported */;
/*!50717 EXECUTE s */;
/*!50717 DEALLOCATE PREPARE s */;
/*!50717 SET @rocksdb_enable_bulk_load = IF (@rocksdb_is_supported, 'SET SESSION rocksdb_bulk_load = 1', 'SET @rocksdb_dummy_bulk_load = 0') */;
/*!50717 PREPARE s FROM @rocksdb_enable_bulk_load */;
/*!50717 EXECUTE s */;
/*!50717 DEALLOCATE PREPARE s */;
DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actor_id` bigint unsigned DEFAULT NULL,
  `action` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_id` bigint unsigned DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `ip` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_actor_id_index` (`actor_id`),
  KEY `activity_logs_action_index` (`action`),
  KEY `activity_logs_target_type_index` (`target_type`),
  KEY `activity_logs_target_id_index` (`target_id`),
  CONSTRAINT `activity_logs_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle_reg` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_garage_phone` (`garage_id`,`phone`),
  CONSTRAINT `customers_garage_id_foreign` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned NOT NULL,
  `documentable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `documentable_id` bigint unsigned NOT NULL,
  `document_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documents_documentable_type_documentable_id_index` (`documentable_type`,`documentable_id`),
  KEY `documents_garage_id_document_type_index` (`garage_id`,`document_type`),
  CONSTRAINT `documents_garage_id_foreign` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `garage_organizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `garage_organizations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned NOT NULL,
  `organization_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `garage_organizations_garage_id_organization_id_unique` (`garage_id`,`organization_id`),
  KEY `garage_organizations_organization_id_foreign` (`organization_id`),
  CONSTRAINT `garage_organizations_garage_id_foreign` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `garage_organizations_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `garages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `garages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_sequence` int unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `garage_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_details` json DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `subscription_expires_at` timestamp NULL DEFAULT NULL,
  `trial_ends_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sms_driver` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fake',
  `sms_config` json DEFAULT NULL,
  `use_global_sms` tinyint(1) NOT NULL DEFAULT '1',
  `payment_methods` json DEFAULT NULL,
  `garage_config` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `garages_garage_code_unique` (`garage_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `insurers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `insurers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_insurers_garage_name` (`garage_id`,`name`),
  KEY `insurers_garage_id_index` (`garage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventory_item_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_item_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned NOT NULL,
  `inventory_item_id` bigint unsigned NOT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_item_movements_inventory_item_id_foreign` (`inventory_item_id`),
  KEY `inventory_item_movements_job_id_foreign` (`job_id`),
  KEY `inventory_item_movements_created_by_foreign` (`created_by`),
  KEY `inventory_item_movements_garage_id_inventory_item_id_index` (`garage_id`,`inventory_item_id`),
  KEY `inventory_item_movements_type_index` (`type`),
  CONSTRAINT `inventory_item_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_item_movements_garage_id_foreign` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_item_movements_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_item_movements_job_id_foreign` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventory_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brand` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `part_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pcs',
  `cost_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `selling_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `current_stock` int NOT NULL DEFAULT '0',
  `reorder_level` int NOT NULL DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_items_garage_id_index` (`garage_id`),
  CONSTRAINT `inventory_items_garage_id_foreign` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `item_type` enum('labour','part') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'labour',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `line_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_index` (`invoice_id`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned NOT NULL,
  `job_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `vehicle_id` bigint unsigned DEFAULT NULL,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('draft','sent','paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `payment_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `paid_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `paid_at` timestamp NULL DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '16.00',
  `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KES',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_job_id_unique` (`job_id`),
  UNIQUE KEY `invoices_garage_invoice_number_unique` (`garage_id`,`invoice_number`),
  KEY `invoices_garage_id_index` (`garage_id`),
  KEY `invoices_job_id_index` (`job_id`),
  KEY `invoices_customer_id_index` (`customer_id`),
  KEY `invoices_vehicle_id_index` (`vehicle_id`),
  CONSTRAINT `invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_garage_id_foreign` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_job_id_foreign` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_inspection_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_inspection_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned NOT NULL,
  `inspection_id` bigint unsigned NOT NULL,
  `item_no` smallint unsigned NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ok',
  `notes` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_inspection_items_inspection_id_item_no_unique` (`inspection_id`,`item_no`),
  KEY `job_inspection_items_garage_id_index` (`garage_id`),
  KEY `job_inspection_items_inspection_id_index` (`inspection_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_inspections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_inspections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned NOT NULL,
  `job_id` bigint unsigned DEFAULT NULL,
  `draft_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'check_in',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_inspections_garage_id_index` (`garage_id`),
  KEY `job_inspections_job_id_index` (`job_id`),
  KEY `job_inspections_draft_uuid_index` (`draft_uuid`),
  KEY `job_inspections_completed_by_index` (`completed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_insurance_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_insurance_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `job_id` bigint unsigned NOT NULL,
  `garage_id` bigint unsigned DEFAULT NULL,
  `insurer_id` bigint unsigned DEFAULT NULL,
  `insurer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `policy_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `claim_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `excess_amount` decimal(10,2) DEFAULT NULL,
  `adjuster_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adjuster_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_insurance_details_job_id_unique` (`job_id`),
  KEY `idx_job_insurance_details_insurer_id` (`insurer_id`),
  KEY `job_insurance_details_garage_id_index` (`garage_id`),
  CONSTRAINT `job_insurance_details_insurer_id_foreign` FOREIGN KEY (`insurer_id`) REFERENCES `insurers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `job_insurance_details_job_id_foreign` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `job_id` bigint unsigned NOT NULL,
  `type` enum('part','labour') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `inventory_item_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_items_job_id_foreign` (`job_id`),
  KEY `job_items_inventory_item_id_foreign` (`inventory_item_id`),
  CONSTRAINT `job_items_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `job_items_job_id_foreign` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_part_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_part_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `job_id` bigint unsigned NOT NULL,
  `inventory_item_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(10,2) NOT NULL,
  `line_total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_part_items_job_id_foreign` (`job_id`),
  KEY `job_part_items_inventory_item_id_index` (`inventory_item_id`),
  CONSTRAINT `job_part_items_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `job_part_items_job_id_foreign` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_work_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_work_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `job_id` bigint unsigned NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hours` decimal(8,2) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_work_items_job_id_foreign` (`job_id`),
  CONSTRAINT `job_work_items_job_id_foreign` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned NOT NULL,
  `vehicle_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `job_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_date` date DEFAULT NULL,
  `service_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complaint` text COLLATE utf8mb4_unicode_ci,
  `diagnosis` text COLLATE utf8mb4_unicode_ci,
  `work_done` text COLLATE utf8mb4_unicode_ci,
  `parts_used` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `mileage` int DEFAULT NULL,
  `labour_cost` decimal(10,2) DEFAULT NULL,
  `parts_cost` decimal(10,2) DEFAULT NULL,
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `final_cost` decimal(10,2) DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `payer_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual',
  `organization_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jobs_job_number_unique` (`job_number`),
  KEY `jobs_vehicle_id_foreign` (`vehicle_id`),
  KEY `jobs_customer_id_foreign` (`customer_id`),
  KEY `jobs_garage_id_vehicle_id_index` (`garage_id`,`vehicle_id`),
  KEY `jobs_garage_id_customer_id_index` (`garage_id`,`customer_id`),
  KEY `jobs_garage_id_status_index` (`garage_id`,`status`),
  KEY `jobs_garage_id_job_date_index` (`garage_id`,`job_date`),
  KEY `jobs_created_by_foreign` (`created_by`),
  KEY `jobs_organization_id_foreign` (`organization_id`),
  KEY `jobs_garage_id_payer_type_index` (`garage_id`,`payer_type`),
  KEY `jobs_garage_id_organization_id_index` (`garage_id`,`organization_id`),
  CONSTRAINT `jobs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `jobs_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jobs_garage_id_foreign` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jobs_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `jobs_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned NOT NULL,
  `media_item_id` bigint unsigned NOT NULL,
  `attachable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachable_id` bigint unsigned NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_media_attach` (`media_item_id`,`attachable_type`,`attachable_id`),
  KEY `media_attachments_attachable_type_attachable_id_index` (`attachable_type`,`attachable_id`),
  KEY `media_attachments_garage_id_index` (`garage_id`),
  KEY `media_attachments_media_item_id_index` (`media_item_id`),
  CONSTRAINT `media_attachments_media_item_id_foreign` FOREIGN KEY (`media_item_id`) REFERENCES `media_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned NOT NULL,
  `media_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `disk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_bytes` bigint unsigned NOT NULL DEFAULT '0',
  `width` int unsigned DEFAULT NULL,
  `height` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_items_media_uuid_unique` (`media_uuid`),
  KEY `media_items_garage_id_created_at_index` (`garage_id`,`created_at`),
  KEY `media_items_garage_id_index` (`garage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `organizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `organizations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('insurance','corporate') COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_terms` smallint unsigned NOT NULL DEFAULT '30',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `otps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `expires_at` timestamp NOT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `otps_phone_index` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned NOT NULL,
  `provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mpesa',
  `method` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paybill',
  `paybill_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shortcode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `store_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sandbox',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `reference_strategy` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'invoice_number',
  `reference_prefix` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `callback_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credentials` json DEFAULT NULL,
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_garage_provider_method` (`garage_id`,`provider`,`method`),
  KEY `payment_configs_garage_id_provider_method_index` (`garage_id`,`provider`,`method`),
  CONSTRAINT `payment_configs_garage_id_foreign` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned DEFAULT NULL,
  `payment_id` bigint unsigned DEFAULT NULL,
  `provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mpesa',
  `event_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correlation_id` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `headers` json DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `received_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_logs_provider_event_type_index` (`provider`,`event_type`),
  KEY `payment_logs_garage_id_received_at_index` (`garage_id`,`received_at`),
  KEY `payment_logs_payment_id_received_at_index` (`payment_id`,`received_at`),
  KEY `payment_logs_correlation_id_index` (`correlation_id`),
  CONSTRAINT `payment_logs_garage_id_foreign` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_logs_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned NOT NULL,
  `invoice_id` bigint unsigned DEFAULT NULL,
  `job_id` bigint unsigned DEFAULT NULL,
  `provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mpesa',
  `channel` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `msisdn` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payer_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KES',
  `transaction_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `merchant_request_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checkout_request_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `result_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `result_desc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `raw_payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `payer_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mpesa_merchant_request_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mpesa_checkout_request_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mpesa_receipt_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mpesa_result_code` int DEFAULT NULL,
  `mpesa_result_desc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mpesa_callback_payload` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_provider_transaction_id` (`provider`,`transaction_id`),
  UNIQUE KEY `uniq_provider_checkout_request_id` (`provider`,`checkout_request_id`),
  KEY `payments_garage_id_created_at_index` (`garage_id`,`created_at`),
  KEY `payments_garage_id_status_index` (`garage_id`,`status`),
  KEY `payments_garage_id_reference_index` (`garage_id`,`reference`),
  KEY `payments_garage_id_invoice_id_index` (`garage_id`,`invoice_id`),
  KEY `payments_garage_id_job_id_index` (`garage_id`,`job_id`),
  KEY `payments_provider_transaction_id_index` (`provider`,`transaction_id`),
  KEY `payments_provider_checkout_request_id_index` (`provider`,`checkout_request_id`),
  KEY `payments_invoice_id_foreign` (`invoice_id`),
  KEY `payments_job_id_foreign` (`job_id`),
  KEY `payments_payer_phone_index` (`payer_phone`),
  KEY `payments_mpesa_merchant_request_id_index` (`mpesa_merchant_request_id`),
  KEY `payments_mpesa_checkout_request_id_index` (`mpesa_checkout_request_id`),
  KEY `payments_mpesa_receipt_number_index` (`mpesa_receipt_number`),
  CONSTRAINT `payments_garage_id_foreign` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_job_id_foreign` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pending_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pending_registrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `otp_code_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otp_expires_at` timestamp NULL DEFAULT NULL,
  `otp_attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `otp_last_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pending_registrations_phone_index` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sms_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_campaigns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `filters_json` json DEFAULT NULL,
  `total_recipients` int NOT NULL DEFAULT '0',
  `sent_count` int NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sms_campaigns_garage_id_foreign` (`garage_id`),
  CONSTRAINT `sms_campaigns_garage_id_foreign` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sms_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `job_id` bigint unsigned DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sms_message_id` bigint unsigned DEFAULT NULL,
  `to_phone` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error` text COLLATE utf8mb4_unicode_ci,
  `meta` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sms_logs_garage_id_index` (`garage_id`),
  KEY `sms_logs_customer_id_index` (`customer_id`),
  KEY `sms_logs_job_id_index` (`job_id`),
  KEY `sms_logs_sms_message_id_index` (`sms_message_id`),
  KEY `sms_logs_to_phone_index` (`to_phone`),
  KEY `sms_logs_type_index` (`type`),
  CONSTRAINT `sms_logs_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sms_logs_garage_id_foreign` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sms_logs_job_id_foreign` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sms_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned NOT NULL,
  `sms_campaign_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sent',
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `to_phone` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `from_name` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unique_key` varchar(140) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `send_at` timestamp NULL DEFAULT NULL,
  `error` text COLLATE utf8mb4_unicode_ci,
  `meta` json DEFAULT NULL,
  `attempts` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sms_messages_unique_key_unique` (`unique_key`),
  KEY `sms_messages_garage_id_sms_campaign_id_index` (`garage_id`,`sms_campaign_id`),
  KEY `sms_messages_to_phone_index` (`to_phone`),
  KEY `sms_messages_type_index` (`type`),
  KEY `sms_messages_send_at_index` (`send_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sms_driver` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fake',
  `sms_config` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `garage_id` bigint unsigned DEFAULT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'staff',
  `is_super_admin` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `suspended_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_garage_id_foreign` (`garage_id`),
  KEY `users_status_index` (`status`),
  KEY `users_suspended_at_index` (`suspended_at`),
  CONSTRAINT `users_garage_id_foreign` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `garage_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `registration_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `make` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year` smallint DEFAULT NULL,
  `vin` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_garage_reg` (`garage_id`,`registration_number`),
  UNIQUE KEY `vehicles_garage_reg_unique` (`garage_id`,`registration_number`),
  KEY `vehicles_garage_id_index` (`garage_id`),
  KEY `vehicles_customer_id_index` (`customer_id`),
  CONSTRAINT `vehicles_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicles_garage_id_foreign` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50112 SET @disable_bulk_load = IF (@is_rocksdb_supported, 'SET SESSION rocksdb_bulk_load = @old_rocksdb_bulk_load', 'SET @dummy_rocksdb_bulk_load = 0') */;
/*!50112 PREPARE s FROM @disable_bulk_load */;
/*!50112 EXECUTE s */;
/*!50112 DEALLOCATE PREPARE s */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_12_05_180000_create_garages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_12_05_180903_add_garage_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_12_06_022208_create_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_12_06_024317_add_unique_phone_per_garage_to_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_12_06_025109_create_vehicles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_12_06_042032_add_address_city_vehicle_reg_to_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_12_06_051320_create_vehicles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_12_06_053054_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_12_06_054548_rebuild_jobs_table_with_multitenant_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_12_06_062507_update_jobs_table_for_job_cards',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_12_06_091857_create_inventory_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_12_06_104947_create_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_12_07_172833_create_invoice_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_12_07_174257_add_payment_fields_to_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_12_07_185324_create_job_work_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_12_07_185414_create_job_part_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_12_08_052510_create_sms_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_12_08_052707_create_sms_campaign_recipients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_12_08_064558_add_sms_settings_to_garages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_12_08_064718_create_sms_messages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_12_08_081139_create_job_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_12_08_083649_add_address_to_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_12_08_093217_add_unique_reg_to_vehicles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_12_10_185748_create_documents_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_12_08_102357_create_job_work_items_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_12_08_102357_create_job_work_items_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_12_08_102421_create_job_part_items_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_12_06_053054_create_jobs_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_12_10_091139_create_otps_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_12_10_132149_create_invoices_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_12_10_132149_create_invoices_table',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_12_10_143149_add_customer_and_vehicle_to_invoices_table',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_12_10_144026_add_payment_fields_to_invoices_table',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_12_10_144943_add_amount_fields_to_invoices_table',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_12_08_102357_create_job_work_items_table',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_12_08_102421_create_job_part_items_table',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_12_10_181120_create_documents_table',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_12_11_084651_create_inventory_item_movements_table',100);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_12_12_092416_add_inventory_item_id_to_job_part_items_table',101);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_12_12_093225_add_unique_job_id_to_invoices_table',102);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_12_12_121041_add_payment_methods_to_garages_table',103);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_12_12_162628_add_trial_ends_at_to_garages_table',104);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_12_13_072401_create_pending_registrations_table',105);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_12_13_072542_add_phone_fields_to_users_table',105);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_12_13_104916_add_admin_fields_to_users_table',106);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_12_13_111513_create_activity_logs_table',107);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_12_15_000001_make_invoice_number_unique_per_garage',108);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_12_15_000001_add_tax_rate_to_invoices_table',109);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2025_12_15_000001_add_invoice_sequence_to_garages_table',110);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_12_15_000002_add_unique_invoice_number_per_garage_to_invoices_table',111);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_12_15_104604_add_logo_path_to_garages_table',112);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_12_15_141952_add_payment_details_to_garages_table',113);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_01_12_060019_add_use_global_sms_to_garages_table',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_01_12_061134_create_system_settings_table',115);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_01_14_111336_add_paid_at_to_invoices_table',116);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_01_27_051525_create_payment_configs_table',117);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_01_27_051524_create_payments_table',118);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_01_27_051527_create_payment_logs_table',119);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_01_27_120000_sms_engine_patch',120);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_01_28_000001_add_mpesa_stk_fields_to_payments',121);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_01_30_061920_add_garage_config_to_garages_table',122);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_01_30_072055_create_organizations_tables',123);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_02_04_000001_create_media_items_table',124);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_02_04_000002_create_media_attachments_table',124);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_02_05_000001_create_insurers_table',125);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_02_05_000002_add_insurer_id_to_job_insurance_details',125);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_02_07_054813_add_garage_id_to_job_insurance_details',126);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_02_07_061451_create_job_inspections_table',127);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_02_07_061536_create_job_inspection_items_table',127);

-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: talafair
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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

--
-- Table structure for table `announcement_participations`
--

DROP TABLE IF EXISTS `announcement_participations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_participations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `scanned_by` bigint(20) unsigned NOT NULL,
  `scanned_at` datetime NOT NULL,
  `points_awarded` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `announcement_participations_announcement_id_user_id_unique` (`announcement_id`,`user_id`),
  KEY `announcement_participations_user_id_foreign` (`user_id`),
  KEY `announcement_participations_scanned_by_foreign` (`scanned_by`),
  CONSTRAINT `announcement_participations_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_participations_scanned_by_foreign` FOREIGN KEY (`scanned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_participations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement_participations`
--

LOCK TABLES `announcement_participations` WRITE;
/*!40000 ALTER TABLE `announcement_participations` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcement_participations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `is_event` tinyint(1) NOT NULL DEFAULT 0,
  `allow_guest_scanning` tinyint(1) NOT NULL DEFAULT 0,
  `raffle_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `event_start_at` datetime DEFAULT NULL,
  `event_end_at` datetime DEFAULT NULL,
  `rsvp_due_at` datetime DEFAULT NULL,
  `audiences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`audiences`)),
  `base_points` int(10) unsigned NOT NULL DEFAULT 0,
  `confirmation_points` int(10) unsigned NOT NULL DEFAULT 0,
  `weight_points` decimal(8,2) NOT NULL DEFAULT 1.00,
  `participation_points` int(10) unsigned NOT NULL DEFAULT 0,
  `qr_token` varchar(64) DEFAULT NULL,
  `qr_expires_at` datetime DEFAULT NULL,
  `venue_name` varchar(255) DEFAULT NULL,
  `venue_lat` decimal(10,7) DEFAULT NULL,
  `venue_lng` decimal(10,7) DEFAULT NULL,
  `geofence_radius` int(10) unsigned NOT NULL DEFAULT 300,
  `banner_path` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `announcements_qr_token_unique` (`qr_token`),
  KEY `announcements_created_by_foreign` (`created_by`),
  KEY `announcements_updated_by_foreign` (`updated_by`),
  KEY `announcements_is_event_event_start_at_index` (`is_event`,`event_start_at`),
  CONSTRAINT `announcements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `announcements_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (29,'LOCAL DEMO - Community Clean-up Check-in','events','Sample local event with demo resident attendance records.',0,0,1,0,0,'2026-10-24 10:00:00','2026-10-30 12:00:00','2026-10-24 09:00:00','[\"public\",\"students\",\"women\",\"pwd\",\"four_ps\",\"solo_parents\",\"out_of_school_youth\",\"lgbtqia\",\"sjtoda\",\"bpc\",\"youth\",\"senior\",\"family_heads\",\"officials\"]',0,0,1.00,0,'67bb7828-7439-47c3-9465-c3ff619f6b1e','2026-10-30 12:00:00','Barangay San Jose Hall',13.4002668,123.3814055,5000,NULL,NULL,132,'2026-10-25 10:47:09','2026-10-25 10:50:18');
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendances`
--

DROP TABLE IF EXISTS `attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `user_category` enum('resident','official') NOT NULL DEFAULT 'resident',
  `attendance_method` enum('event_qr_scan','official_qr_scan','manual_unique_id') NOT NULL DEFAULT 'event_qr_scan',
  `scanned_at` datetime NOT NULL,
  `is_early` tinyint(1) NOT NULL DEFAULT 0,
  `pre_registered` tinyint(1) NOT NULL DEFAULT 0,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `distance_m` int(10) unsigned DEFAULT NULL,
  `points_awarded` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendances_announcement_id_user_id_unique` (`announcement_id`,`user_id`),
  KEY `attendances_user_id_foreign` (`user_id`),
  CONSTRAINT `attendances_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendances`
--

LOCK TABLES `attendances` WRITE;
/*!40000 ALTER TABLE `attendances` DISABLE KEYS */;
INSERT INTO `attendances` VALUES (2,29,133,'resident','event_qr_scan','2026-10-24 10:15:00',0,0,NULL,NULL,NULL,0,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(3,29,134,'resident','event_qr_scan','2026-10-24 10:27:00',0,0,NULL,NULL,NULL,0,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(4,29,135,'resident','event_qr_scan','2026-10-24 10:39:00',0,0,NULL,NULL,NULL,0,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(5,29,136,'resident','event_qr_scan','2026-10-24 10:51:00',0,0,NULL,NULL,NULL,0,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(6,29,137,'resident','event_qr_scan','2026-10-24 11:03:00',0,0,NULL,NULL,NULL,0,'2026-10-25 10:47:11','2026-10-25 10:47:11');
/*!40000 ALTER TABLE `attendances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `actor_unique_id` varchar(24) DEFAULT NULL,
  `action` varchar(20) NOT NULL,
  `auditable_type` varchar(255) NOT NULL,
  `auditable_id` varchar(255) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `duration_ms` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=219 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (181,NULL,NULL,'created','App\\Models\\User','128',NULL,'{\"name\":\"Resident Local Test\",\"first_name\":\"Resident\",\"last_name\":\"Local Test\",\"gender\":\"female\",\"sex_at_birth\":\"female\",\"preferred_gender_identity\":\"woman\",\"birthdate\":\"2000-01-01 00:00:00\",\"house_no\":\"1\",\"street\":\"Local Test Street\",\"zone\":\"1\",\"barangay\":\"San Jose\",\"city\":\"Iriga City\",\"province\":\"Camarines Sur\",\"country\":\"Philippines\",\"postal_code\":\"4431\",\"is_head_of_family\":false,\"username\":\"resident\",\"email\":null,\"is_verified\":true,\"points\":0,\"role\":\"resident\",\"updated_at\":\"2026-10-21 22:13:39\",\"created_at\":\"2026-10-21 22:13:39\",\"id\":128}','127.0.0.1',NULL,'2026-10-21 14:13:39','2026-10-21 14:13:39'),(182,NULL,NULL,'updated','App\\Models\\User','128',NULL,'{\"unique_id\":\"Z1-26000000001\"}','127.0.0.1',NULL,'2026-10-21 14:13:39','2026-10-21 14:13:39'),(183,NULL,NULL,'created','App\\Models\\User','129',NULL,'{\"name\":\"Guest Local Test\",\"first_name\":\"Guest\",\"last_name\":\"Local Test\",\"gender\":\"female\",\"sex_at_birth\":\"female\",\"preferred_gender_identity\":\"woman\",\"birthdate\":\"2000-01-01 00:00:00\",\"house_no\":\"1\",\"street\":\"Local Test Street\",\"zone\":\"1\",\"barangay\":\"San Jose\",\"city\":\"Iriga City\",\"province\":\"Camarines Sur\",\"country\":\"Philippines\",\"postal_code\":\"4431\",\"is_head_of_family\":false,\"username\":\"guest\",\"email\":null,\"is_verified\":true,\"points\":0,\"role\":\"guest\",\"updated_at\":\"2026-10-21 22:13:39\",\"created_at\":\"2026-10-21 22:13:39\",\"id\":129}','127.0.0.1',NULL,'2026-10-21 14:13:39','2026-10-21 14:13:39'),(184,NULL,NULL,'updated','App\\Models\\User','129',NULL,'{\"unique_id\":\"GZ1-26000000001\"}','127.0.0.1',NULL,'2026-10-21 14:13:39','2026-10-21 14:13:39'),(185,NULL,NULL,'created','App\\Models\\User','130',NULL,'{\"name\":\"Official Local Test\",\"first_name\":\"Official\",\"last_name\":\"Local Test\",\"gender\":\"female\",\"sex_at_birth\":\"female\",\"preferred_gender_identity\":\"woman\",\"birthdate\":\"2000-01-01 00:00:00\",\"house_no\":\"1\",\"street\":\"Local Test Street\",\"zone\":\"1\",\"barangay\":\"San Jose\",\"city\":\"Iriga City\",\"province\":\"Camarines Sur\",\"country\":\"Philippines\",\"postal_code\":\"4431\",\"is_head_of_family\":false,\"username\":\"official\",\"email\":null,\"is_verified\":true,\"points\":0,\"role\":\"official\",\"official_group\":\"barangay_council\",\"official_position\":\"barangay_captain\",\"updated_at\":\"2026-10-21 22:13:39\",\"created_at\":\"2026-10-21 22:13:39\",\"id\":130}','127.0.0.1',NULL,'2026-10-21 14:13:39','2026-10-21 14:13:39'),(186,NULL,NULL,'updated','App\\Models\\User','130',NULL,'{\"unique_id\":\"Z1-26000000002\"}','127.0.0.1',NULL,'2026-10-21 14:13:39','2026-10-21 14:13:39'),(187,NULL,NULL,'created','App\\Models\\User','131',NULL,'{\"name\":\"Personnel Local Test\",\"first_name\":\"Personnel\",\"last_name\":\"Local Test\",\"gender\":\"female\",\"sex_at_birth\":\"female\",\"preferred_gender_identity\":\"woman\",\"birthdate\":\"2000-01-01 00:00:00\",\"house_no\":\"1\",\"street\":\"Local Test Street\",\"zone\":\"1\",\"barangay\":\"San Jose\",\"city\":\"Iriga City\",\"province\":\"Camarines Sur\",\"country\":\"Philippines\",\"postal_code\":\"4431\",\"is_head_of_family\":false,\"username\":\"personnel\",\"email\":null,\"is_verified\":true,\"points\":0,\"role\":\"official\",\"official_group\":\"personnel\",\"official_position\":\"other_personnel\",\"updated_at\":\"2026-10-21 22:13:40\",\"created_at\":\"2026-10-21 22:13:40\",\"id\":131}','127.0.0.1',NULL,'2026-10-21 14:13:40','2026-10-21 14:13:40'),(188,NULL,NULL,'updated','App\\Models\\User','131',NULL,'{\"unique_id\":\"Z1-26000000003\"}','127.0.0.1',NULL,'2026-10-21 14:13:40','2026-10-21 14:13:40'),(189,NULL,NULL,'created','App\\Models\\User','132',NULL,'{\"name\":\"Superadmin Local Test\",\"first_name\":\"Superadmin\",\"last_name\":\"Local Test\",\"gender\":\"female\",\"sex_at_birth\":\"female\",\"preferred_gender_identity\":\"woman\",\"birthdate\":\"2000-01-01 00:00:00\",\"house_no\":\"1\",\"street\":\"Local Test Street\",\"zone\":\"1\",\"barangay\":\"San Jose\",\"city\":\"Iriga City\",\"province\":\"Camarines Sur\",\"country\":\"Philippines\",\"postal_code\":\"4431\",\"is_head_of_family\":false,\"username\":\"superadmin\",\"email\":null,\"is_verified\":true,\"points\":0,\"role\":\"superadmin\",\"updated_at\":\"2026-10-21 22:13:40\",\"created_at\":\"2026-10-21 22:13:40\",\"id\":132}','127.0.0.1',NULL,'2026-10-21 14:13:40','2026-10-21 14:13:40'),(190,NULL,NULL,'updated','App\\Models\\User','132',NULL,'{\"unique_id\":\"Z1-26000000004\"}','127.0.0.1',NULL,'2026-10-21 14:13:40','2026-10-21 14:13:40'),(191,130,'Z1-26000000002','login','App\\Models\\User','130',NULL,NULL,'127.0.0.1',366,'2026-10-21 14:14:38','2026-10-21 14:14:38'),(192,130,'Z1-26000000002','created','App\\Models\\Survey','9',NULL,'{\"title\":\"tfui\",\"description\":\"gftj\",\"due_at\":\"2026-10-22 23:59:00\",\"questions\":\"[\\\"question\\\"]\",\"sections\":\"[{\\\"heading\\\":\\\"header\\\",\\\"start\\\":0}]\",\"suggestion_enabled\":true,\"points\":\"68\",\"audience\":\"public\",\"event_id\":null,\"created_by\":130,\"updated_at\":\"2026-10-22 15:18:34\",\"created_at\":\"2026-10-22 15:18:34\",\"id\":9}','127.0.0.1',110,'2026-10-22 07:18:34','2026-10-22 07:18:34'),(193,132,'Z1-26000000004','login','App\\Models\\User','132',NULL,NULL,'127.0.0.1',422,'2026-10-22 07:22:31','2026-10-22 07:22:31'),(194,132,'Z1-26000000004','updated','App\\Models\\User','128','{\"is_head_of_family\":false}','{\"is_head_of_family\":\"1\"}','127.0.0.1',132,'2026-10-22 07:27:48','2026-10-22 07:27:48'),(195,132,'Z1-26000000004','updated','App\\Models\\User','128','{\"zone\":\"1\"}','{\"zone\":\"2\"}','127.0.0.1',74,'2026-10-22 07:28:24','2026-10-22 07:28:24'),(196,132,'Z1-26000000004','updated','App\\Models\\User','128','{\"zone\":\"2\",\"unique_id\":\"Z1-26000000001\"}','{\"zone\":\"3\",\"unique_id\":\"Z3-26000000001\"}','127.0.0.1',155,'2026-10-22 07:36:58','2026-10-22 07:36:58'),(197,132,'Z1-26000000004','updated','App\\Models\\Survey','9','{\"research_consent_enabled\":false}','{\"research_consent_enabled\":true}','127.0.0.1',70,'2026-10-22 08:45:55','2026-10-22 08:45:55'),(198,132,'Z1-26000000004','deleted','App\\Models\\Survey','9','{\"id\":9,\"title\":\"tfui\",\"description\":\"gftj\",\"due_at\":\"2026-10-22T15:59:00.000000Z\",\"questions\":[\"question\"],\"suggestion_enabled\":true,\"points\":68,\"audience\":\"public\",\"event_id\":null,\"created_by\":130,\"created_at\":\"2026-10-22T07:18:34.000000Z\",\"updated_at\":\"2026-10-22T08:45:55.000000Z\",\"sections\":[{\"heading\":\"header\",\"start\":0}],\"research_consent_enabled\":true}',NULL,'127.0.0.1',137,'2026-10-25 08:27:26','2026-10-25 08:27:26'),(199,NULL,NULL,'created','App\\Models\\Announcement','29',NULL,'{\"title\":\"LOCAL DEMO - Community Clean-up Check-in\",\"category\":\"events\",\"body\":\"Sample local event with demo resident attendance records.\",\"is_event\":true,\"event_start_at\":\"2026-10-24 10:00:00\",\"event_end_at\":\"2026-10-24 12:00:00\",\"rsvp_due_at\":\"2026-10-24 09:00:00\",\"audiences\":\"[\\\"public\\\"]\",\"base_points\":0,\"venue_name\":\"Barangay San Jose Hall\",\"venue_lat\":13.426208,\"venue_lng\":123.3987476,\"geofence_radius\":300,\"updated_at\":\"2026-10-25 18:47:09\",\"created_at\":\"2026-10-25 18:47:09\",\"id\":29}','127.0.0.1',NULL,'2026-10-25 10:47:09','2026-10-25 10:47:09'),(200,NULL,NULL,'updated','App\\Models\\Announcement','29',NULL,'{\"qr_token\":\"67bb7828-7439-47c3-9465-c3ff619f6b1e\",\"qr_expires_at\":\"2026-10-24 12:00:00\"}','127.0.0.1',NULL,'2026-10-25 10:47:09','2026-10-25 10:47:09'),(201,NULL,NULL,'created','App\\Models\\User','133',NULL,'{\"username\":\"local-demo-resident-01\",\"name\":\"Local Demo Resident 01\",\"first_name\":\"Local Demo\",\"last_name\":\"Resident 01\",\"email\":null,\"role\":\"resident\",\"is_verified\":true,\"points\":0,\"birthdate\":\"1996-10-25 00:00:00\",\"house_no\":\"11\",\"street\":\"Demo Street\",\"zone\":\"1\",\"barangay\":\"San Jose\",\"city\":\"Iriga City\",\"province\":\"Camarines Sur\",\"country\":\"Philippines\",\"postal_code\":\"4431\",\"updated_at\":\"2026-10-25 18:47:10\",\"created_at\":\"2026-10-25 18:47:10\",\"id\":133}','127.0.0.1',NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(202,NULL,NULL,'updated','App\\Models\\User','133',NULL,'{\"unique_id\":\"Z1-26000000005\"}','127.0.0.1',NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(203,NULL,NULL,'created','App\\Models\\Attendance','2',NULL,'{\"user_id\":133,\"user_category\":\"resident\",\"attendance_method\":\"event_qr_scan\",\"scanned_at\":\"2026-10-24 10:15:00\",\"points_awarded\":0,\"announcement_id\":29,\"updated_at\":\"2026-10-25 18:47:10\",\"created_at\":\"2026-10-25 18:47:10\",\"id\":2}','127.0.0.1',NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(204,NULL,NULL,'created','App\\Models\\User','134',NULL,'{\"username\":\"local-demo-resident-02\",\"name\":\"Local Demo Resident 02\",\"first_name\":\"Local Demo\",\"last_name\":\"Resident 02\",\"email\":null,\"role\":\"resident\",\"is_verified\":true,\"points\":0,\"birthdate\":\"1996-10-25 00:00:00\",\"house_no\":\"12\",\"street\":\"Demo Street\",\"zone\":\"1\",\"barangay\":\"San Jose\",\"city\":\"Iriga City\",\"province\":\"Camarines Sur\",\"country\":\"Philippines\",\"postal_code\":\"4431\",\"updated_at\":\"2026-10-25 18:47:10\",\"created_at\":\"2026-10-25 18:47:10\",\"id\":134}','127.0.0.1',NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(205,NULL,NULL,'updated','App\\Models\\User','134',NULL,'{\"unique_id\":\"Z1-26000000006\"}','127.0.0.1',NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(206,NULL,NULL,'created','App\\Models\\Attendance','3',NULL,'{\"user_id\":134,\"user_category\":\"resident\",\"attendance_method\":\"event_qr_scan\",\"scanned_at\":\"2026-10-24 10:27:00\",\"points_awarded\":0,\"announcement_id\":29,\"updated_at\":\"2026-10-25 18:47:10\",\"created_at\":\"2026-10-25 18:47:10\",\"id\":3}','127.0.0.1',NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(207,NULL,NULL,'created','App\\Models\\User','135',NULL,'{\"username\":\"local-demo-resident-03\",\"name\":\"Local Demo Resident 03\",\"first_name\":\"Local Demo\",\"last_name\":\"Resident 03\",\"email\":null,\"role\":\"resident\",\"is_verified\":true,\"points\":0,\"birthdate\":\"1996-10-25 00:00:00\",\"house_no\":\"13\",\"street\":\"Demo Street\",\"zone\":\"1\",\"barangay\":\"San Jose\",\"city\":\"Iriga City\",\"province\":\"Camarines Sur\",\"country\":\"Philippines\",\"postal_code\":\"4431\",\"updated_at\":\"2026-10-25 18:47:10\",\"created_at\":\"2026-10-25 18:47:10\",\"id\":135}','127.0.0.1',NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(208,NULL,NULL,'updated','App\\Models\\User','135',NULL,'{\"unique_id\":\"Z1-26000000007\"}','127.0.0.1',NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(209,NULL,NULL,'created','App\\Models\\Attendance','4',NULL,'{\"user_id\":135,\"user_category\":\"resident\",\"attendance_method\":\"event_qr_scan\",\"scanned_at\":\"2026-10-24 10:39:00\",\"points_awarded\":0,\"announcement_id\":29,\"updated_at\":\"2026-10-25 18:47:10\",\"created_at\":\"2026-10-25 18:47:10\",\"id\":4}','127.0.0.1',NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(210,NULL,NULL,'created','App\\Models\\User','136',NULL,'{\"username\":\"local-demo-resident-04\",\"name\":\"Local Demo Resident 04\",\"first_name\":\"Local Demo\",\"last_name\":\"Resident 04\",\"email\":null,\"role\":\"resident\",\"is_verified\":true,\"points\":0,\"birthdate\":\"1996-10-25 00:00:00\",\"house_no\":\"14\",\"street\":\"Demo Street\",\"zone\":\"1\",\"barangay\":\"San Jose\",\"city\":\"Iriga City\",\"province\":\"Camarines Sur\",\"country\":\"Philippines\",\"postal_code\":\"4431\",\"updated_at\":\"2026-10-25 18:47:10\",\"created_at\":\"2026-10-25 18:47:10\",\"id\":136}','127.0.0.1',NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(211,NULL,NULL,'updated','App\\Models\\User','136',NULL,'{\"unique_id\":\"Z1-26000000008\"}','127.0.0.1',NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(212,NULL,NULL,'created','App\\Models\\Attendance','5',NULL,'{\"user_id\":136,\"user_category\":\"resident\",\"attendance_method\":\"event_qr_scan\",\"scanned_at\":\"2026-10-24 10:51:00\",\"points_awarded\":0,\"announcement_id\":29,\"updated_at\":\"2026-10-25 18:47:10\",\"created_at\":\"2026-10-25 18:47:10\",\"id\":5}','127.0.0.1',NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(213,NULL,NULL,'created','App\\Models\\User','137',NULL,'{\"username\":\"local-demo-resident-05\",\"name\":\"Local Demo Resident 05\",\"first_name\":\"Local Demo\",\"last_name\":\"Resident 05\",\"email\":null,\"role\":\"resident\",\"is_verified\":true,\"points\":0,\"birthdate\":\"1996-10-25 00:00:00\",\"house_no\":\"15\",\"street\":\"Demo Street\",\"zone\":\"1\",\"barangay\":\"San Jose\",\"city\":\"Iriga City\",\"province\":\"Camarines Sur\",\"country\":\"Philippines\",\"postal_code\":\"4431\",\"updated_at\":\"2026-10-25 18:47:10\",\"created_at\":\"2026-10-25 18:47:10\",\"id\":137}','127.0.0.1',NULL,'2026-10-25 10:47:11','2026-10-25 10:47:11'),(214,NULL,NULL,'updated','App\\Models\\User','137',NULL,'{\"unique_id\":\"Z1-26000000009\"}','127.0.0.1',NULL,'2026-10-25 10:47:11','2026-10-25 10:47:11'),(215,NULL,NULL,'created','App\\Models\\Attendance','6',NULL,'{\"user_id\":137,\"user_category\":\"resident\",\"attendance_method\":\"event_qr_scan\",\"scanned_at\":\"2026-10-24 11:03:00\",\"points_awarded\":0,\"announcement_id\":29,\"updated_at\":\"2026-10-25 18:47:11\",\"created_at\":\"2026-10-25 18:47:11\",\"id\":6}','127.0.0.1',NULL,'2026-10-25 10:47:11','2026-10-25 10:47:11'),(216,132,'Z1-26000000004','updated','App\\Models\\Announcement','29','{\"event_end_at\":\"2026-10-24T04:00:00.000000Z\",\"audiences\":[\"public\"],\"qr_expires_at\":\"2026-10-24T04:00:00.000000Z\",\"venue_lat\":\"13.4262080\",\"venue_lng\":\"123.3987476\",\"geofence_radius\":300,\"updated_by\":null}','{\"event_end_at\":\"2026-10-30 12:00:00\",\"audiences\":\"[\\\"public\\\",\\\"students\\\",\\\"women\\\",\\\"pwd\\\",\\\"four_ps\\\",\\\"solo_parents\\\",\\\"out_of_school_youth\\\",\\\"lgbtqia\\\",\\\"sjtoda\\\",\\\"bpc\\\",\\\"youth\\\",\\\"senior\\\",\\\"family_heads\\\",\\\"officials\\\"]\",\"qr_expires_at\":\"2026-10-30 12:00:00\",\"venue_lat\":\"13.4002668\",\"venue_lng\":\"123.3814055\",\"geofence_radius\":\"5000\",\"updated_by\":132}','127.0.0.1',241,'2026-10-25 10:50:18','2026-10-25 10:50:18'),(217,132,'Z1-26000000004','login','App\\Models\\User','132',NULL,NULL,'127.0.0.1',363,'2026-10-25 10:53:09','2026-10-25 10:53:09'),(218,132,'Z1-26000000004','created','App\\Models\\Survey','10',NULL,'{\"title\":\"fgktlyytjxtd\",\"description\":null,\"due_at\":\"2026-10-27 22:30:00\",\"questions\":\"[\\\"Hello?\\\"]\",\"sections\":\"[{\\\"heading\\\":\\\"hi?\\\",\\\"start\\\":0}]\",\"suggestion_enabled\":true,\"research_consent_enabled\":true,\"points\":\"0\",\"audience\":\"public\",\"event_id\":null,\"created_by\":132,\"updated_at\":\"2026-10-27 21:23:38\",\"created_at\":\"2026-10-27 21:23:38\",\"id\":10}','127.0.0.1',199,'2026-10-27 13:23:38','2026-10-27 13:23:38');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `badge_user`
--

DROP TABLE IF EXISTS `badge_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `badge_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `badge_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `awarded_by` bigint(20) unsigned DEFAULT NULL,
  `award_rank` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `badge_user_badge_id_user_id_unique` (`badge_id`,`user_id`),
  KEY `badge_user_user_id_foreign` (`user_id`),
  KEY `badge_user_awarded_by_foreign` (`awarded_by`),
  CONSTRAINT `badge_user_awarded_by_foreign` FOREIGN KEY (`awarded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `badge_user_badge_id_foreign` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE,
  CONSTRAINT `badge_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `badge_user`
--

LOCK TABLES `badge_user` WRITE;
/*!40000 ALTER TABLE `badge_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `badge_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `badges`
--

DROP TABLE IF EXISTS `badges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `badges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'attendance',
  `rarity` varchar(255) NOT NULL DEFAULT 'common',
  `award_method` varchar(255) NOT NULL DEFAULT 'automatic',
  `condition_key` varchar(255) NOT NULL DEFAULT 'points',
  `limited_total` int(10) unsigned DEFAULT NULL,
  `announcement_id` bigint(20) unsigned DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `points_required` int(10) unsigned NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `badges_created_by_foreign` (`created_by`),
  KEY `badges_updated_by_foreign` (`updated_by`),
  KEY `badges_announcement_id_foreign` (`announcement_id`),
  CONSTRAINT `badges_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE SET NULL,
  CONSTRAINT `badges_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `badges_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `badges`
--

LOCK TABLES `badges` WRITE;
/*!40000 ALTER TABLE `badges` DISABLE KEYS */;
/*!40000 ALTER TABLE `badges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

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

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('talafair-cache-5c785c036466adea360111aa28563bfd556b5fba','i:2;',1792925632),('talafair-cache-5c785c036466adea360111aa28563bfd556b5fba:timer','i:1792925632;',1792925632),('talafair-cache-setting:home_background','N;',2110322869);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

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

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_facilitators`
--

DROP TABLE IF EXISTS `event_facilitators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_facilitators` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'facilitator',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_facilitators_announcement_id_user_id_unique` (`announcement_id`,`user_id`),
  KEY `event_facilitators_user_id_foreign` (`user_id`),
  KEY `event_facilitators_assigned_by_foreign` (`assigned_by`),
  CONSTRAINT `event_facilitators_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_facilitators_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `event_facilitators_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_facilitators`
--

LOCK TABLES `event_facilitators` WRITE;
/*!40000 ALTER TABLE `event_facilitators` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_facilitators` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_raffle_entries`
--

DROP TABLE IF EXISTS `event_raffle_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_raffle_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `is_early` tinyint(1) NOT NULL DEFAULT 0,
  `points_snapshot` int(10) unsigned DEFAULT NULL,
  `weight` decimal(12,4) DEFAULT NULL,
  `snapshot_at` timestamp NULL DEFAULT NULL,
  `selected_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_raffle_entries_announcement_id_user_id_unique` (`announcement_id`,`user_id`),
  KEY `event_raffle_entries_user_id_foreign` (`user_id`),
  CONSTRAINT `event_raffle_entries_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_raffle_entries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_raffle_entries`
--

LOCK TABLES `event_raffle_entries` WRITE;
/*!40000 ALTER TABLE `event_raffle_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_raffle_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_raffle_prizes`
--

DROP TABLE IF EXISTS `event_raffle_prizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_raffle_prizes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `prize_type` varchar(255) NOT NULL DEFAULT 'foods',
  `points_amount` int(10) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_raffle_prizes_created_by_foreign` (`created_by`),
  KEY `event_raffle_prizes_announcement_id_sort_order_index` (`announcement_id`,`sort_order`),
  CONSTRAINT `event_raffle_prizes_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_raffle_prizes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_raffle_prizes`
--

LOCK TABLES `event_raffle_prizes` WRITE;
/*!40000 ALTER TABLE `event_raffle_prizes` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_raffle_prizes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_raffle_winners`
--

DROP TABLE IF EXISTS `event_raffle_winners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_raffle_winners` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` bigint(20) unsigned NOT NULL,
  `event_raffle_prize_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `winner_name_snapshot` varchar(255) NOT NULL,
  `draw_sequence` int(10) unsigned NOT NULL,
  `drawn_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_raffle_winners_announcement_id_user_id_unique` (`announcement_id`,`user_id`),
  UNIQUE KEY `event_raffle_winners_event_raffle_prize_id_draw_sequence_unique` (`event_raffle_prize_id`,`draw_sequence`),
  KEY `event_raffle_winners_user_id_foreign` (`user_id`),
  CONSTRAINT `event_raffle_winners_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_raffle_winners_event_raffle_prize_id_foreign` FOREIGN KEY (`event_raffle_prize_id`) REFERENCES `event_raffle_prizes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_raffle_winners_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_raffle_winners`
--

LOCK TABLES `event_raffle_winners` WRITE;
/*!40000 ALTER TABLE `event_raffle_winners` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_raffle_winners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_reminders`
--

DROP TABLE IF EXISTS `event_reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_reminders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` bigint(20) unsigned NOT NULL,
  `sent_by` bigint(20) unsigned NOT NULL,
  `kind` enum('event_reminder','survey_closing') NOT NULL,
  `message` text DEFAULT NULL,
  `recipients_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_reminders_announcement_id_foreign` (`announcement_id`),
  KEY `event_reminders_sent_by_foreign` (`sent_by`),
  CONSTRAINT `event_reminders_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_reminders_sent_by_foreign` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_reminders`
--

LOCK TABLES `event_reminders` WRITE;
/*!40000 ALTER TABLE `event_reminders` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_reminders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_rsvps`
--

DROP TABLE IF EXISTS `event_rsvps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_rsvps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `status` enum('attending','not_attending') NOT NULL,
  `reason` text DEFAULT NULL,
  `responded_at` datetime NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_rsvps_announcement_id_user_id_unique` (`announcement_id`,`user_id`),
  KEY `event_rsvps_user_id_foreign` (`user_id`),
  KEY `event_rsvps_updated_by_foreign` (`updated_by`),
  CONSTRAINT `event_rsvps_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_rsvps_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `event_rsvps_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_rsvps`
--

LOCK TABLES `event_rsvps` WRITE;
/*!40000 ALTER TABLE `event_rsvps` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_rsvps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_substitutions`
--

DROP TABLE IF EXISTS `event_substitutions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_substitutions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` bigint(20) unsigned NOT NULL,
  `family_head_id` bigint(20) unsigned NOT NULL,
  `substitute_user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_substitutions_announcement_id_family_head_id_unique` (`announcement_id`,`family_head_id`),
  KEY `event_substitutions_family_head_id_foreign` (`family_head_id`),
  KEY `event_substitutions_substitute_user_id_foreign` (`substitute_user_id`),
  CONSTRAINT `event_substitutions_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_substitutions_family_head_id_foreign` FOREIGN KEY (`family_head_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_substitutions_substitute_user_id_foreign` FOREIGN KEY (`substitute_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_substitutions`
--

LOCK TABLES `event_substitutions` WRITE;
/*!40000 ALTER TABLE `event_substitutions` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_substitutions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

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

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `game_runs`
--

DROP TABLE IF EXISTS `game_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `game` varchar(30) NOT NULL,
  `played_on` date NOT NULL,
  `stage_scores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`stage_scores`)),
  `total_score` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `game_runs_user_id_game_played_on_unique` (`user_id`,`game`,`played_on`),
  CONSTRAINT `game_runs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game_runs`
--

LOCK TABLES `game_runs` WRITE;
/*!40000 ALTER TABLE `game_runs` DISABLE KEYS */;
/*!40000 ALTER TABLE `game_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

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

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
INSERT INTO `jobs` VALUES (1,'default','{\"uuid\":\"cb6b1537-f308-4209-9005-19bd2a77e4ca\",\"displayName\":\"App\\\\Jobs\\\\SendWebPushNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendWebPushNotification\",\"command\":\"O:32:\\\"App\\\\Jobs\\\\SendWebPushNotification\\\":5:{s:6:\\\"userId\\\";i:128;s:5:\\\"title\\\";s:27:\\\"Account information updated\\\";s:4:\\\"body\\\";s:45:\\\"An official updated your account information.\\\";s:3:\\\"url\\\";s:29:\\\"http:\\/\\/127.0.0.1:8000\\/account\\\";s:11:\\\"afterCommit\\\";b:1;}\",\"batchId\":null},\"createdAt\":1792654068,\"delay\":null}',0,NULL,1792654068,1792654068),(2,'default','{\"uuid\":\"5e0f4b32-aca2-4a95-b195-413a3e2dc8ce\",\"displayName\":\"App\\\\Jobs\\\\SendWebPushNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendWebPushNotification\",\"command\":\"O:32:\\\"App\\\\Jobs\\\\SendWebPushNotification\\\":5:{s:6:\\\"userId\\\";i:128;s:5:\\\"title\\\";s:27:\\\"Account information updated\\\";s:4:\\\"body\\\";s:45:\\\"An official updated your account information.\\\";s:3:\\\"url\\\";s:29:\\\"http:\\/\\/127.0.0.1:8000\\/account\\\";s:11:\\\"afterCommit\\\";b:1;}\",\"batchId\":null},\"createdAt\":1792654104,\"delay\":null}',0,NULL,1792654104,1792654104),(3,'default','{\"uuid\":\"3807a2d9-0f2d-47ef-81e2-57db4f1b9d42\",\"displayName\":\"App\\\\Jobs\\\\SendWebPushNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendWebPushNotification\",\"command\":\"O:32:\\\"App\\\\Jobs\\\\SendWebPushNotification\\\":5:{s:6:\\\"userId\\\";i:128;s:5:\\\"title\\\";s:27:\\\"Account information updated\\\";s:4:\\\"body\\\";s:45:\\\"An official updated your account information.\\\";s:3:\\\"url\\\";s:29:\\\"http:\\/\\/127.0.0.1:8000\\/account\\\";s:11:\\\"afterCommit\\\";b:1;}\",\"batchId\":null},\"createdAt\":1792654618,\"delay\":null}',0,NULL,1792654618,1792654618);
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_08_06_000001_create_spin_history_table',1),(5,'2026_08_06_000002_create_announcements_table',1),(6,'2026_08_06_000003_create_prizes_table',1),(7,'2026_08_17_142152_add_fields_to_users_table',1),(8,'2026_08_17_142444_add_event_fields_to_announcements_table',1),(9,'2026_08_17_142646_create_event_rsvp_table',1),(10,'2026_08_17_142816_create_attendances_table',1),(11,'2026_08_17_142936_create_audit_logs_table',1),(12,'2026_08_17_143035_create_badges_table',1),(13,'2026_08_17_143153_create_settings_table',1),(14,'2026_08_17_143248_create_event_reminders_table',1),(15,'2026_08_24_000001_add_official_positions_to_users_table',1),(16,'2026_08_24_000002_add_verification_status_to_users_table',1),(17,'2026_08_24_000003_create_raffle_entries_table',1),(18,'2026_08_25_000001_create_announcement_participations_table',1),(19,'2026_08_25_000002_add_participation_points_to_announcements_table',1),(20,'2026_08_26_000001_add_badge_mechanics',1),(21,'2026_08_26_000001_create_trivia_themes_table',1),(22,'2026_08_26_000002_add_guest_access',1),(23,'2026_08_26_000002_add_trivia_points_and_answers_table',1),(24,'2026_08_26_000003_create_game_runs_table',1),(25,'2026_08_26_000004_add_due_at_to_trivia_themes',1),(26,'2026_08_26_000005_create_event_raffle_entries_table',1),(27,'2026_08_28_000001_add_badge_award_metadata',1),(28,'2026_08_28_000001_add_student_and_occupation_to_users_table',1),(29,'2026_08_28_000002_add_duration_to_audit_logs_table',1),(30,'2026_08_28_000003_create_event_substitutions_table',1),(31,'2026_08_29_000001_create_surveys_tables',1),(32,'2026_08_29_000002_add_survey_id_to_user_notifications',1),(33,'2026_08_29_000003_add_due_at_and_points_to_surveys',1),(34,'2026_09_06_000001_add_confirmation_points_to_announcements_table',1),(35,'2026_09_06_000002_ensure_confirmation_points_on_announcements_table',1),(36,'2026_09_07_000001_add_event_raffle_system',1),(37,'2026_09_07_000001_add_user_classification_fields',1),(38,'2026_09_07_000002_add_official_attendance_support',1),(39,'2026_09_07_000002_add_student_details_to_users_table',1),(40,'2026_09_07_000003_add_social_classifications_to_users_table',1),(41,'2026_09_07_000003_add_type_to_event_raffle_prizes',1),(42,'2026_09_07_000004_create_event_facilitators_table',1),(43,'2026_09_07_000005_create_point_transactions_table',1),(44,'2026_09_07_000006_add_type_and_points_to_event_raffle_prizes',1),(45,'2026_09_13_000001_add_superadmin_role_to_users_table',1),(46,'2026_10_08_000001_change_audit_log_auditable_id_to_string',1),(47,'2026_10_12_000001_add_sections_to_surveys_table',1),(48,'2026_10_14_000001_add_sjtoda_and_bpc_classifications_to_users_table',1),(49,'2026_10_14_000002_make_user_email_nullable',1),(50,'2026_10_14_000003_preserve_notifications_after_announcement_deletion',1),(51,'2026_10_14_220934_create_push_subscriptions_table',1),(52,'2026_10_14_220934_increase_push_subscriptions_endpoint_length',1),(53,'2026_10_17_000001_add_is_archived_to_announcements_table',1),(54,'2026_10_22_000001_add_research_consent_to_surveys',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

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

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `point_transactions`
--

DROP TABLE IF EXISTS `point_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `point_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `announcement_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `base_points` int(10) unsigned NOT NULL DEFAULT 0,
  `multiplier` decimal(4,2) NOT NULL DEFAULT 1.00,
  `points_awarded` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `point_transactions_announcement_id_foreign` (`announcement_id`),
  KEY `point_transactions_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `point_transactions_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE SET NULL,
  CONSTRAINT `point_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `point_transactions`
--

LOCK TABLES `point_transactions` WRITE;
/*!40000 ALTER TABLE `point_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `point_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prizes`
--

DROP TABLE IF EXISTS `prizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prizes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(50) NOT NULL,
  `prize_type` enum('points','foods','electronics','cash','essentials','none') NOT NULL,
  `amount` int(10) unsigned NOT NULL DEFAULT 0,
  `color` varchar(7) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prizes`
--

LOCK TABLES `prizes` WRITE;
/*!40000 ALTER TABLE `prizes` DISABLE KEYS */;
/*!40000 ALTER TABLE `prizes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `push_subscriptions`
--

DROP TABLE IF EXISTS `push_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `push_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subscribable_type` varchar(255) NOT NULL,
  `subscribable_id` bigint(20) unsigned NOT NULL,
  `endpoint` varchar(1024) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `public_key` varchar(255) DEFAULT NULL,
  `auth_token` varchar(255) DEFAULT NULL,
  `content_encoding` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `push_subscriptions_endpoint_unique` (`endpoint`),
  KEY `push_subscriptions_subscribable_morph_idx` (`subscribable_type`,`subscribable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `push_subscriptions`
--

LOCK TABLES `push_subscriptions` WRITE;
/*!40000 ALTER TABLE `push_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `push_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `raffle_entries`
--

DROP TABLE IF EXISTS `raffle_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `raffle_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `extra_chances` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `raffle_entries_user_id_unique` (`user_id`),
  CONSTRAINT `raffle_entries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `raffle_entries`
--

LOCK TABLES `raffle_entries` WRITE;
/*!40000 ALTER TABLE `raffle_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `raffle_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

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

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('0jeARpIgzjW0e6aZaPsRrpnDa7aYn19ukEcDHYU0',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9711','YTozOntzOjY6Il90b2tlbiI7czo0MDoidmM2VGc5NkhCYnBlR1lzY1dSbUltUlNERDFEbzRtQUtPYUlQaW5iOSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1792926131),('c0gm1svg441nMkwcEy0llEzi0mxf90kWubOpQkiV',132,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.146.0 Chrome/150.0.7871.250 Electron/43.8.6 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZThxZXhWcmxPa0Y4NldEZTN0YjEzTUt5YXVTYlZVSlp6WmpmZk5pUyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTMyO30=',1794962875),('D7PeAyJWwkCGGN6UktqjXhHWVcxDUJUufxZqgvJC',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9711','YTo1OntzOjY6Il90b2tlbiI7czo0MDoicTNHZ2pKOHJiRkxHbUxBNExtMWtoNEFHdzF4M2JCNWw1ekR2MTg3ZSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjI6e2k6MDtzOjY6ImVycm9ycyI7aToxO3M6MTA6Il9vbGRfaW5wdXQiO31zOjM6Im5ldyI7YTowOnt9fXM6NjoiZXJyb3JzIjtPOjMxOiJJbGx1bWluYXRlXFN1cHBvcnRcVmlld0Vycm9yQmFnIjoxOntzOjc6IgAqAGJhZ3MiO2E6MTp7czo3OiJkZWZhdWx0IjtPOjI5OiJJbGx1bWluYXRlXFN1cHBvcnRcTWVzc2FnZUJhZyI6Mjp7czoxMToiACoAbWVzc2FnZXMiO2E6MTp7czo1OiJsb2dpbiI7YToxOntpOjA7czo1MDoiVGhlIHByb3ZpZGVkIGNyZWRlbnRpYWxzIGRvIG5vdCBtYXRjaCBvdXIgcmVjb3Jkcy4iO319czo5OiIAKgBmb3JtYXQiO3M6ODoiOm1lc3NhZ2UiO319fXM6MTA6Il9vbGRfaW5wdXQiO2E6MTp7czo1OiJsb2dpbiI7czoyOToiY3NyZi1jaGVjay1pbnZhbGlkLWxvY2FsLXVzZXIiO319',1792591642),('Mg4WReeH4uqtwG58qk3EgDMenLtOUMS4TFDgyitn',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9711','YTozOntzOjY6Il90b2tlbiI7czo0MDoiT21scnJRZm14QWxDZDJ2emVMOElYVmRMbFptN21DMTJ5bHl1a2pmdSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1792926177);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`key`),
  KEY `settings_updated_by_foreign` (`updated_by`),
  CONSTRAINT `settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `spin_history`
--

DROP TABLE IF EXISTS `spin_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spin_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `prize_label` varchar(255) NOT NULL,
  `prize_type` enum('points','foods','electronics','cash','essentials','none') NOT NULL,
  `amount` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `spin_history_user_id_foreign` (`user_id`),
  CONSTRAINT `spin_history_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `spin_history`
--

LOCK TABLES `spin_history` WRITE;
/*!40000 ALTER TABLE `spin_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `spin_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_responses`
--

DROP TABLE IF EXISTS `survey_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_responses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `survey_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`answers`)),
  `suggestion` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `research_profile` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`research_profile`)),
  `research_consent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `survey_responses_survey_id_user_id_unique` (`survey_id`,`user_id`),
  KEY `survey_responses_user_id_foreign` (`user_id`),
  CONSTRAINT `survey_responses_survey_id_foreign` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE,
  CONSTRAINT `survey_responses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey_responses`
--

LOCK TABLES `survey_responses` WRITE;
/*!40000 ALTER TABLE `survey_responses` DISABLE KEYS */;
/*!40000 ALTER TABLE `survey_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `surveys`
--

DROP TABLE IF EXISTS `surveys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `surveys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_at` datetime DEFAULT NULL,
  `questions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`questions`)),
  `suggestion_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `points` int(10) unsigned NOT NULL DEFAULT 0,
  `audience` varchar(255) NOT NULL DEFAULT 'event_attendees',
  `event_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sections` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sections`)),
  `research_consent_enabled` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `surveys_event_id_foreign` (`event_id`),
  KEY `surveys_created_by_foreign` (`created_by`),
  CONSTRAINT `surveys_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `surveys_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `announcements` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `surveys`
--

LOCK TABLES `surveys` WRITE;
/*!40000 ALTER TABLE `surveys` DISABLE KEYS */;
INSERT INTO `surveys` VALUES (10,'fgktlyytjxtd',NULL,'2026-10-27 22:30:00','[\"Hello?\"]',1,0,'public',NULL,132,'2026-10-27 13:23:38','2026-10-27 13:23:38','[{\"heading\":\"hi?\",\"start\":0}]',1);
/*!40000 ALTER TABLE `surveys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trivia_answers`
--

DROP TABLE IF EXISTS `trivia_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trivia_answers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trivia_theme_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `question_index` int(10) unsigned NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `rank` int(10) unsigned DEFAULT NULL,
  `points_awarded` int(10) unsigned NOT NULL DEFAULT 0,
  `answered_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `trivia_answers_trivia_theme_id_user_id_question_index_unique` (`trivia_theme_id`,`user_id`,`question_index`),
  KEY `trivia_answers_user_id_foreign` (`user_id`),
  KEY `trivia_answers_rank_idx` (`trivia_theme_id`,`question_index`,`is_correct`,`answered_at`),
  CONSTRAINT `trivia_answers_trivia_theme_id_foreign` FOREIGN KEY (`trivia_theme_id`) REFERENCES `trivia_themes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trivia_answers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trivia_answers`
--

LOCK TABLES `trivia_answers` WRITE;
/*!40000 ALTER TABLE `trivia_answers` DISABLE KEYS */;
/*!40000 ALTER TABLE `trivia_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trivia_themes`
--

DROP TABLE IF EXISTS `trivia_themes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trivia_themes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `base_points` int(10) unsigned NOT NULL DEFAULT 100,
  `due_at` datetime DEFAULT NULL,
  `questions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`questions`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trivia_themes_created_by_foreign` (`created_by`),
  CONSTRAINT `trivia_themes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trivia_themes`
--

LOCK TABLES `trivia_themes` WRITE;
/*!40000 ALTER TABLE `trivia_themes` DISABLE KEYS */;
/*!40000 ALTER TABLE `trivia_themes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_notifications`
--

DROP TABLE IF EXISTS `user_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `announcement_id` bigint(20) unsigned DEFAULT NULL,
  `survey_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_notifications_created_by_foreign` (`created_by`),
  KEY `user_notifications_user_id_read_at_index` (`user_id`,`read_at`),
  KEY `user_notifications_survey_id_foreign` (`survey_id`),
  KEY `user_notifications_announcement_id_foreign` (`announcement_id`),
  CONSTRAINT `user_notifications_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_notifications_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_notifications_survey_id_foreign` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_notifications`
--

LOCK TABLES `user_notifications` WRITE;
/*!40000 ALTER TABLE `user_notifications` DISABLE KEYS */;
INSERT INTO `user_notifications` VALUES (28,132,NULL,NULL,'Created User','Account \"Superadmin Local Test\" was created by the system.','2026-10-22 15:43:44',NULL,'2026-10-21 14:13:40','2026-10-22 07:43:44'),(29,132,NULL,NULL,'Created Survey','Survey \"tfui\" was created by Official Local Test.','2026-10-22 15:43:44',130,'2026-10-22 07:18:34','2026-10-22 07:43:44'),(30,128,NULL,NULL,'New survey: tfui','Share your feedback in the new community survey.',NULL,130,'2026-10-22 07:18:34','2026-10-22 07:18:34'),(31,129,NULL,NULL,'New survey: tfui','Share your feedback in the new community survey.',NULL,130,'2026-10-22 07:18:34','2026-10-22 07:18:34'),(32,130,NULL,NULL,'New survey: tfui','Share your feedback in the new community survey.',NULL,130,'2026-10-22 07:18:34','2026-10-22 07:18:34'),(33,131,NULL,NULL,'New survey: tfui','Share your feedback in the new community survey.',NULL,130,'2026-10-22 07:18:34','2026-10-22 07:18:34'),(34,132,NULL,NULL,'New survey: tfui','Share your feedback in the new community survey.','2026-10-22 15:43:44',130,'2026-10-22 07:18:34','2026-10-22 07:43:44'),(35,128,NULL,NULL,'Account information updated','An official updated your account information.',NULL,132,'2026-10-22 07:27:48','2026-10-22 07:27:48'),(36,128,NULL,NULL,'Account information updated','An official updated your account information.',NULL,132,'2026-10-22 07:28:24','2026-10-22 07:28:24'),(37,128,NULL,NULL,'Account information updated','An official updated your account information.',NULL,132,'2026-10-22 07:36:58','2026-10-22 07:36:58'),(38,132,NULL,NULL,'Updated Survey','Survey \"tfui\" was updated by Superadmin Local Test.',NULL,132,'2026-10-22 08:45:55','2026-10-22 08:45:55'),(39,132,NULL,NULL,'Deleted Survey','Survey \"tfui\" was deleted by Superadmin Local Test.',NULL,132,'2026-10-25 08:27:26','2026-10-25 08:27:26'),(40,132,29,NULL,'Created Announcement','Announcement \"LOCAL DEMO - Community Clean-up Check-in\" was created by the system.',NULL,NULL,'2026-10-25 10:47:09','2026-10-25 10:47:09'),(41,132,29,NULL,'Updated Announcement','Announcement \"LOCAL DEMO - Community Clean-up Check-in\" was updated by the system.',NULL,NULL,'2026-10-25 10:47:09','2026-10-25 10:47:09'),(42,132,NULL,NULL,'Created User','Account \"Local Demo Resident 01\" was created by the system.',NULL,NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(43,132,29,NULL,'Created Attendance','Attendance #2 was created by the system.',NULL,NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(44,132,NULL,NULL,'Created User','Account \"Local Demo Resident 02\" was created by the system.',NULL,NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(45,132,29,NULL,'Created Attendance','Attendance #3 was created by the system.',NULL,NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(46,132,NULL,NULL,'Created User','Account \"Local Demo Resident 03\" was created by the system.',NULL,NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(47,132,29,NULL,'Created Attendance','Attendance #4 was created by the system.',NULL,NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(48,132,NULL,NULL,'Created User','Account \"Local Demo Resident 04\" was created by the system.',NULL,NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(49,132,29,NULL,'Created Attendance','Attendance #5 was created by the system.',NULL,NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(50,132,NULL,NULL,'Created User','Account \"Local Demo Resident 05\" was created by the system.',NULL,NULL,'2026-10-25 10:47:11','2026-10-25 10:47:11'),(51,132,29,NULL,'Created Attendance','Attendance #6 was created by the system.',NULL,NULL,'2026-10-25 10:47:11','2026-10-25 10:47:11'),(52,132,29,NULL,'Updated Announcement','Announcement \"LOCAL DEMO - Community Clean-up Check-in\" was updated by Superadmin Local Test.',NULL,132,'2026-10-25 10:50:18','2026-10-25 10:50:18'),(53,128,29,NULL,'Event updated: LOCAL DEMO - Community Clean-up Check-in','The event details have changed. Please review the latest information.',NULL,132,'2026-10-25 10:50:18','2026-10-25 10:50:18'),(54,129,29,NULL,'Event updated: LOCAL DEMO - Community Clean-up Check-in','The event details have changed. Please review the latest information.',NULL,132,'2026-10-25 10:50:18','2026-10-25 10:50:18'),(55,130,29,NULL,'Event updated: LOCAL DEMO - Community Clean-up Check-in','The event details have changed. Please review the latest information.',NULL,132,'2026-10-25 10:50:18','2026-10-25 10:50:18'),(56,131,29,NULL,'Event updated: LOCAL DEMO - Community Clean-up Check-in','The event details have changed. Please review the latest information.',NULL,132,'2026-10-25 10:50:18','2026-10-25 10:50:18'),(57,132,29,NULL,'Event updated: LOCAL DEMO - Community Clean-up Check-in','The event details have changed. Please review the latest information.',NULL,132,'2026-10-25 10:50:18','2026-10-25 10:50:18'),(58,133,29,NULL,'Event updated: LOCAL DEMO - Community Clean-up Check-in','The event details have changed. Please review the latest information.',NULL,132,'2026-10-25 10:50:18','2026-10-25 10:50:18'),(59,134,29,NULL,'Event updated: LOCAL DEMO - Community Clean-up Check-in','The event details have changed. Please review the latest information.',NULL,132,'2026-10-25 10:50:18','2026-10-25 10:50:18'),(60,135,29,NULL,'Event updated: LOCAL DEMO - Community Clean-up Check-in','The event details have changed. Please review the latest information.',NULL,132,'2026-10-25 10:50:18','2026-10-25 10:50:18'),(61,136,29,NULL,'Event updated: LOCAL DEMO - Community Clean-up Check-in','The event details have changed. Please review the latest information.',NULL,132,'2026-10-25 10:50:18','2026-10-25 10:50:18'),(62,137,29,NULL,'Event updated: LOCAL DEMO - Community Clean-up Check-in','The event details have changed. Please review the latest information.',NULL,132,'2026-10-25 10:50:18','2026-10-25 10:50:18'),(63,132,NULL,10,'Created Survey','Survey \"fgktlyytjxtd\" was created by Superadmin Local Test.','2026-11-15 20:36:57',132,'2026-10-27 13:23:38','2026-11-15 12:36:57'),(64,128,NULL,10,'New survey: fgktlyytjxtd','Share your feedback in the new community survey.',NULL,132,'2026-10-27 13:23:38','2026-10-27 13:23:38'),(65,129,NULL,10,'New survey: fgktlyytjxtd','Share your feedback in the new community survey.',NULL,132,'2026-10-27 13:23:38','2026-10-27 13:23:38'),(66,130,NULL,10,'New survey: fgktlyytjxtd','Share your feedback in the new community survey.',NULL,132,'2026-10-27 13:23:38','2026-10-27 13:23:38'),(67,131,NULL,10,'New survey: fgktlyytjxtd','Share your feedback in the new community survey.',NULL,132,'2026-10-27 13:23:38','2026-10-27 13:23:38'),(68,132,NULL,10,'New survey: fgktlyytjxtd','Share your feedback in the new community survey.',NULL,132,'2026-10-27 13:23:38','2026-10-27 13:23:38'),(69,133,NULL,10,'New survey: fgktlyytjxtd','Share your feedback in the new community survey.',NULL,132,'2026-10-27 13:23:38','2026-10-27 13:23:38'),(70,134,NULL,10,'New survey: fgktlyytjxtd','Share your feedback in the new community survey.',NULL,132,'2026-10-27 13:23:38','2026-10-27 13:23:38'),(71,135,NULL,10,'New survey: fgktlyytjxtd','Share your feedback in the new community survey.',NULL,132,'2026-10-27 13:23:38','2026-10-27 13:23:38'),(72,136,NULL,10,'New survey: fgktlyytjxtd','Share your feedback in the new community survey.',NULL,132,'2026-10-27 13:23:38','2026-10-27 13:23:38'),(73,137,NULL,10,'New survey: fgktlyytjxtd','Share your feedback in the new community survey.',NULL,132,'2026-10-27 13:23:38','2026-10-27 13:23:38');
/*!40000 ALTER TABLE `user_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `gender` enum('female','male','others') DEFAULT NULL,
  `gender_other` varchar(255) DEFAULT NULL,
  `sex_at_birth` varchar(255) DEFAULT NULL,
  `preferred_gender_identity` varchar(255) DEFAULT NULL,
  `gender_identity_other` varchar(255) DEFAULT NULL,
  `is_lgbtqia` tinyint(1) NOT NULL DEFAULT 0,
  `is_sjtoda` tinyint(1) NOT NULL DEFAULT 0,
  `is_bpc` tinyint(1) NOT NULL DEFAULT 0,
  `birthdate` date DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `is_student` tinyint(1) NOT NULL DEFAULT 0,
  `student_level` varchar(255) DEFAULT NULL,
  `is_pwd` tinyint(1) NOT NULL DEFAULT 0,
  `is_4ps_member` tinyint(1) NOT NULL DEFAULT 0,
  `is_solo_parent` tinyint(1) NOT NULL DEFAULT 0,
  `is_out_of_school_youth` tinyint(1) NOT NULL DEFAULT 0,
  `school` varchar(255) DEFAULT NULL,
  `school_other` varchar(255) DEFAULT NULL,
  `occupation` varchar(255) DEFAULT NULL,
  `house_no` varchar(255) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `zone` varchar(10) DEFAULT NULL,
  `barangay` varchar(255) NOT NULL DEFAULT 'San Jose',
  `city` varchar(255) NOT NULL DEFAULT 'Iriga City',
  `province` varchar(255) NOT NULL DEFAULT 'Camarines Sur',
  `country` varchar(255) NOT NULL DEFAULT 'Philippines',
  `postal_code` varchar(10) NOT NULL DEFAULT '4431',
  `is_head_of_family` tinyint(1) NOT NULL DEFAULT 0,
  `head_of_family_name` varchar(255) DEFAULT NULL,
  `head_of_family_id` bigint(20) unsigned DEFAULT NULL,
  `unique_id` varchar(24) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('resident','guest','official','superadmin') NOT NULL DEFAULT 'resident',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `official_group` varchar(255) DEFAULT NULL,
  `official_position` varchar(255) DEFAULT NULL,
  `points` bigint(20) unsigned NOT NULL DEFAULT 0,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_unique_id_unique` (`unique_id`),
  KEY `users_head_of_family_id_foreign` (`head_of_family_id`),
  KEY `users_birthdate_index` (`birthdate`),
  KEY `users_is_head_of_family_index` (`is_head_of_family`),
  KEY `users_is_verified_index` (`is_verified`),
  CONSTRAINT `users_head_of_family_id_foreign` FOREIGN KEY (`head_of_family_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=138 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (128,'Resident Local Test','Resident',NULL,'Local Test',NULL,'female',NULL,'female','woman',NULL,0,0,0,'2000-01-01',NULL,0,NULL,0,0,0,0,NULL,NULL,NULL,'1','Local Test Street','3','San Jose','Iriga City','Camarines Sur','Philippines','4431',1,NULL,NULL,'Z3-26000000001',NULL,'resident',NULL,NULL,'$2y$12$WGgZU23kUXm//db0Sfk/8exnShS7FVCrZ3Bp3szIu4uGnbr/IH/nq','resident',1,NULL,NULL,0,NULL,'2026-10-21 14:13:39','2026-10-22 07:36:58'),(129,'Guest Local Test','Guest',NULL,'Local Test',NULL,'female',NULL,'female','woman',NULL,0,0,0,'2000-01-01',NULL,0,NULL,0,0,0,0,NULL,NULL,NULL,'1','Local Test Street','1','San Jose','Iriga City','Camarines Sur','Philippines','4431',0,NULL,NULL,'GZ1-26000000001',NULL,'guest',NULL,NULL,'$2y$12$G8uwa.KOJPobhXl9dmQpL.jRUIEZ33THRP3oOUThW25EITJt7V0Vu','guest',1,NULL,NULL,0,NULL,'2026-10-21 14:13:39','2026-10-21 14:13:39'),(130,'Official Local Test','Official',NULL,'Local Test',NULL,'female',NULL,'female','woman',NULL,0,0,0,'2000-01-01',NULL,0,NULL,0,0,0,0,NULL,NULL,NULL,'1','Local Test Street','1','San Jose','Iriga City','Camarines Sur','Philippines','4431',0,NULL,NULL,'Z1-26000000002',NULL,'official',NULL,NULL,'$2y$12$fOEBte4hhl26fs7rjP2YH.kMJPCKOMeVOfg4Zst5MeVaS2RUKC1xu','official',1,'barangay_council','barangay_captain',0,'oC4Hr5tAKNTJ66pVOIhBaZJtdEwnDDj0Pt13kd6hIB6XbF5iGyYqgPR65YsM','2026-10-21 14:13:39','2026-10-21 14:13:39'),(131,'Personnel Local Test','Personnel',NULL,'Local Test',NULL,'female',NULL,'female','woman',NULL,0,0,0,'2000-01-01',NULL,0,NULL,0,0,0,0,NULL,NULL,NULL,'1','Local Test Street','1','San Jose','Iriga City','Camarines Sur','Philippines','4431',0,NULL,NULL,'Z1-26000000003',NULL,'personnel',NULL,NULL,'$2y$12$qMYqpoHbfRw4hXYm9ZmoqOmkNkPXztW75/xqruTAMpTpFaVV4LGti','official',1,'personnel','other_personnel',0,NULL,'2026-10-21 14:13:40','2026-10-21 14:13:40'),(132,'Superadmin Local Test','Superadmin',NULL,'Local Test',NULL,'female',NULL,'female','woman',NULL,0,0,0,'2000-01-01',NULL,0,NULL,0,0,0,0,NULL,NULL,NULL,'1','Local Test Street','1','San Jose','Iriga City','Camarines Sur','Philippines','4431',0,NULL,NULL,'Z1-26000000004',NULL,'superadmin',NULL,NULL,'$2y$12$/jni42YteMrbah3w6.t/cu48d8quu3XOP12njiSxQQa8bZAPDFm6K','superadmin',1,NULL,NULL,0,'QWTXW9iqDfj5k1YNmlSyoT9wlPVVhGPHeKiMoovcrB4w8q55sHFvglwnxiv4','2026-10-21 14:13:40','2026-10-21 14:13:40'),(133,'Local Demo Resident 01','Local Demo',NULL,'Resident 01',NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'1996-10-25',NULL,0,NULL,0,0,0,0,NULL,NULL,NULL,'11','Demo Street','1','San Jose','Iriga City','Camarines Sur','Philippines','4431',0,NULL,NULL,'Z1-26000000005',NULL,'local-demo-resident-01',NULL,NULL,'$2y$12$ObjSkpknv/6B3rlfsOV/ten2PGc2J9obs4U7Ernt7OJbA22Hi8UOS','resident',1,NULL,NULL,0,NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(134,'Local Demo Resident 02','Local Demo',NULL,'Resident 02',NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'1996-10-25',NULL,0,NULL,0,0,0,0,NULL,NULL,NULL,'12','Demo Street','1','San Jose','Iriga City','Camarines Sur','Philippines','4431',0,NULL,NULL,'Z1-26000000006',NULL,'local-demo-resident-02',NULL,NULL,'$2y$12$5wJgF3vS7oGR9e3gchYtr.I06MjIQDz8AsjvMpobIIlAWRj7DaKYu','resident',1,NULL,NULL,0,NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(135,'Local Demo Resident 03','Local Demo',NULL,'Resident 03',NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'1996-10-25',NULL,0,NULL,0,0,0,0,NULL,NULL,NULL,'13','Demo Street','1','San Jose','Iriga City','Camarines Sur','Philippines','4431',0,NULL,NULL,'Z1-26000000007',NULL,'local-demo-resident-03',NULL,NULL,'$2y$12$4jJsXiFHbaF4teP0/MuyU.mguTOunSOLXWQzc6qqSVy9wGLw1J4nG','resident',1,NULL,NULL,0,NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(136,'Local Demo Resident 04','Local Demo',NULL,'Resident 04',NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'1996-10-25',NULL,0,NULL,0,0,0,0,NULL,NULL,NULL,'14','Demo Street','1','San Jose','Iriga City','Camarines Sur','Philippines','4431',0,NULL,NULL,'Z1-26000000008',NULL,'local-demo-resident-04',NULL,NULL,'$2y$12$r0PasFg0DxZMUAsBpRlTeObdRGGY.MC5YUDiV56S2/bOqK8X6jQRS','resident',1,NULL,NULL,0,NULL,'2026-10-25 10:47:10','2026-10-25 10:47:10'),(137,'Local Demo Resident 05','Local Demo',NULL,'Resident 05',NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'1996-10-25',NULL,0,NULL,0,0,0,0,NULL,NULL,NULL,'15','Demo Street','1','San Jose','Iriga City','Camarines Sur','Philippines','4431',0,NULL,NULL,'Z1-26000000009',NULL,'local-demo-resident-05',NULL,NULL,'$2y$12$n94nA.0hZr3BEtodDAygvu41JuuM0s6a6Gfn7Xly41JU6zUITfwda','resident',1,NULL,NULL,0,NULL,'2026-10-25 10:47:10','2026-10-25 10:47:11');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'talafair'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-18  8:49:08

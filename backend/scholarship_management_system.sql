/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.3.2-MariaDB, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: sms
-- ------------------------------------------------------
-- Server version	12.3.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Current Database: `sms`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `sms` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `sms`;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin` (
  `adminID` int(11) NOT NULL AUTO_INCREMENT,
  `upMail` varchar(255) NOT NULL,
  `password` text NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `middleName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `contact` varchar(12) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`adminID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES
(2,'admin@gmail.com','$2y$12$reuFVMELN8s..xlrZIyzXuz.IbXnnrred4d4hsmkJ4vn8QMHHcfri','Rahul','C','Bindrani','','active');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `application`
--

DROP TABLE IF EXISTS `application`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `application` (
  `applicationID` int(11) NOT NULL AUTO_INCREMENT,
  `studentID` int(11) NOT NULL,
  `sigID` int(11) DEFAULT NULL,
  `scholarshipID` int(11) NOT NULL,
  `appDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `appstatus` varchar(20) NOT NULL DEFAULT 'Pending',
  `verifiedBySignatory` varchar(20) NOT NULL DEFAULT 'Pending',
  `previous_appstatus` varchar(20) NOT NULL,
  `previous_verifiedBySignatory` varchar(20) NOT NULL,
  PRIMARY KEY (`applicationID`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `application`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `application` WRITE;
/*!40000 ALTER TABLE `application` DISABLE KEYS */;
INSERT INTO `application` VALUES
(38,43,8,22,'2019-06-06 13:58:58','inactive','currently blocked','Rejected','Rejected'),
(39,43,8,23,'2019-06-06 13:40:15','Processing','Approved','Processing','Approved'),
(40,43,8,25,'2019-06-06 11:20:19','Pending','Pending','Pending','Pending'),
(41,43,7,31,'2019-06-07 16:17:26','Pending','Pending','Pending','Pending'),
(42,43,8,30,'2019-06-07 16:16:50','Pending','Pending','Pending','Pending'),
(43,43,8,26,'2026-06-09 14:59:58','Pending','Pending','Pending','Pending'),
(44,44,8,23,'2019-06-07 11:20:22','Pending','Pending','',''),
(45,49,9,34,'2026-06-16 15:55:16','Pending','Pending','Pending','Pending'),
(46,50,9,35,'2026-06-17 14:34:10','Processing','Approved','Rejected','Rejected'),
(47,49,9,35,'2026-06-18 04:55:31','Pending','Pending','Pending','Pending');
/*!40000 ALTER TABLE `application` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `reset_password`
--

DROP TABLE IF EXISTS `reset_password`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reset_password` (
  `upMail` varchar(255) NOT NULL,
  `num` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reset_password`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `reset_password` WRITE;
/*!40000 ALTER TABLE `reset_password` DISABLE KEYS */;
INSERT INTO `reset_password` VALUES
('dishantd999@gmail.com',744014),
('dishantd999@gmail.com',287736),
('dishantd999@gmail.com',851718),
('dishantd999@gmail.com',517402),
('dishantd999@gmail.com',979640),
('yuprivatebaddest21@gmail.com',307435),
('yuprivatebaddest21@gmail.com',253931),
('yuprivatebaddest21@gmail.com',212853),
('yuprivatebaddest21@gmail.com',501868),
('yuprivatebaddest21@gmail.com',992732);
/*!40000 ALTER TABLE `reset_password` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `scholarship`
--

DROP TABLE IF EXISTS `scholarship`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `scholarship` (
  `scholarshipID` int(11) NOT NULL AUTO_INCREMENT,
  `sigID` int(11) NOT NULL,
  `schname` varchar(255) NOT NULL,
  `schlocation` varchar(255) NOT NULL,
  `schlocationfrom` varchar(255) NOT NULL,
  `degree` varchar(255) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `target_financial_need` enum('Any','Low','Medium','High','Critical') DEFAULT 'Any',
  `religion` varchar(55) NOT NULL,
  `sch` varchar(30) NOT NULL,
  `appDeadline` date NOT NULL,
  `granteesNum` int(11) NOT NULL,
  `funding` varchar(20) NOT NULL,
  `description` varchar(4095) NOT NULL,
  `eligibility` varchar(4095) NOT NULL,
  `benefits` varchar(4095) NOT NULL,
  `apply` varchar(4095) NOT NULL,
  `links` varchar(1024) NOT NULL,
  `contact` varchar(1024) NOT NULL,
  `adminapproval` varchar(20) NOT NULL,
  `previous_adminapproval` varchar(20) NOT NULL,
  `schstatus` varchar(20) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`scholarshipID`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scholarship`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `scholarship` WRITE;
/*!40000 ALTER TABLE `scholarship` DISABLE KEYS */;
INSERT INTO `scholarship` VALUES
(34,9,'Test 1','Nairobi','Kibera','select','select','Any','','sports_talent','2026-06-20',10,'$400','testtt','testtt','testtt','testtt','testtt','testtt','Approved','Pending','active'),
(35,9,'MICROSOFT FELLOWSHIP','Nairobi','Mathare','diploma','male+female','Low','','technology_based','2026-06-18',10,'$400','This is the description','computer knowledge','Accomodation','Contact us.','https://teams.cloud.microsoft/','Contact us','Approved','Pending','active'),
(36,9,'MICROSOFT FELLOWSHIP PT II','Nairobi','Kibera','undergraduate','male+female','Low','','technology_based','2026-06-20',23,'$4000','Endless fun!!','Come prepared','Accomodation','contact us','https://teams.cloud.microsoft/','https://teams.cloud.microsoft/','Approved','Pending','active');
/*!40000 ALTER TABLE `scholarship` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `signatory`
--

DROP TABLE IF EXISTS `signatory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `signatory` (
  `sigID` int(11) NOT NULL AUTO_INCREMENT,
  `upMail` varchar(255) NOT NULL,
  `password` text NOT NULL,
  `firstName` varchar(255) DEFAULT '',
  `middleName` varchar(255) DEFAULT '',
  `lastName` varchar(255) DEFAULT '',
  `organization/university` varchar(255) DEFAULT '',
  `position` varchar(255) DEFAULT '',
  `contact` varchar(12) DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`sigID`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sms_dispatch_log`
--

DROP TABLE IF EXISTS `sms_dispatch_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_dispatch_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_count` int(11) NOT NULL DEFAULT 0,
  `success_count` int(11) NOT NULL DEFAULT 0,
  `failed_count` int(11) NOT NULL DEFAULT 0,
  `provider_http_code` int(11) NOT NULL DEFAULT 0,
  `provider_message` varchar(255) NOT NULL DEFAULT '',
  `message_preview` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sms_dispatch_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `signatory`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `signatory` WRITE;
/*!40000 ALTER TABLE `signatory` DISABLE KEYS */;
INSERT INTO `signatory` VALUES
(9,'yuprivatebaddest21@gmail.com','$2y$12$juv9EEfeVrwQqSCJLG9GoOPzfmwx2B6MQxFxh8kOElIE1Ami5I4IW','Ingridius','Finelite','Finelite','','Taraaa','','active');
/*!40000 ALTER TABLE `signatory` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `student`
--

DROP TABLE IF EXISTS `student`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student` (
  `studentID` int(11) NOT NULL AUTO_INCREMENT,
  `upMail` varchar(255) NOT NULL,
  `password` text NOT NULL,
  `firstName` varchar(255) DEFAULT '',
  `middleName` varchar(255) DEFAULT '',
  `lastName` varchar(255) DEFAULT '',
  `nationality` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `birthDate` date DEFAULT NULL,
  `birthPlace` varchar(255) DEFAULT NULL,
  `presStreetAddr` varchar(255) DEFAULT NULL,
  `presProvCity` varchar(255) DEFAULT NULL,
  `presRegion` varchar(255) DEFAULT NULL,
  `permStreetAddr` varchar(255) DEFAULT NULL,
  `permProvCity` varchar(255) DEFAULT NULL,
  `permRegion` varchar(255) DEFAULT NULL,
  `contactNo` varchar(20) DEFAULT NULL,
  `dept` varchar(255) DEFAULT '',
  `college` varchar(255) DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `current_level` varchar(255) DEFAULT NULL COMMENT 'Maps to scholarship degree requirements',
  `financial_need` enum('Low','Medium','High','Critical') DEFAULT NULL COMMENT 'Standardized urgency scale',
  `career_interests` varchar(1024) DEFAULT NULL COMMENT 'Comma-separated keywords for matching',
  PRIMARY KEY (`studentID`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `student` WRITE;
/*!40000 ALTER TABLE `student` DISABLE KEYS */;
INSERT INTO `student` VALUES
(49,'kifine@proton.me','$2y$12$T/fl/oN4XLvr0iNDiZERv.ZIxcdwzgGKTpD.g5xLgPWIjbLxkbvzC','Ingridius','Finelite','Kisiwani',NULL,'male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0655877995','','','active','diploma','Low','Technology Based'),
(50,'natalie.chelagat@strathmore.edu','$2y$12$RNtCP9t6dFgkDnBgFRSvOuT.b4g0iZSI744G9f7L6Glm1mHW8Tete','Natalie','Burgei','Chelagat',NULL,'female',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0655877995','','','active','undergraduate','Low','Visual Art, Technology Based');
/*!40000 ALTER TABLE `student` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `verify_signup`
--

DROP TABLE IF EXISTS `verify_signup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `verify_signup` (
  `upMail` varchar(255) NOT NULL,
  `action` int(2) NOT NULL DEFAULT 0,
  `num` int(8) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verify_signup`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `verify_signup` WRITE;
/*!40000 ALTER TABLE `verify_signup` DISABLE KEYS */;
INSERT INTO `verify_signup` VALUES
('admin@gmail.com',1,1234),
('yuprivatebaddest21@gmail.com',1,254814),
('kifine@proton.me',1,140591),
('natalie.chelagat@strathmore.edu',1,773400);
/*!40000 ALTER TABLE `verify_signup` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ir_engine_meta`
--

DROP TABLE IF EXISTS `ir_engine_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ir_engine_meta` (
  `meta_key` varchar(120) NOT NULL,
  `meta_value` text NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`meta_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ir_recommendation_cache`
--

DROP TABLE IF EXISTS `ir_recommendation_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ir_recommendation_cache` (
  `student_id` int(11) NOT NULL,
  `scholarship_id` int(11) NOT NULL,
  `score_percent` int(11) NOT NULL,
  `explanation_json` text NOT NULL,
  `profile_hash` char(40) NOT NULL,
  `corpus_hash` char(40) NOT NULL,
  `model_version` varchar(40) NOT NULL,
  `generated_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`student_id`,`scholarship_id`),
  KEY `idx_ir_rec_cache_lookup` (`student_id`,`profile_hash`,`corpus_hash`,`model_version`,`generated_at`),
  CONSTRAINT `fk_ir_rec_cache_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`studentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ir_rec_cache_scholarship` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarship` (`scholarshipID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ir_scholarship_norms`
--

DROP TABLE IF EXISTS `ir_scholarship_norms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ir_scholarship_norms` (
  `scholarship_id` int(11) NOT NULL,
  `norm_value` double NOT NULL,
  PRIMARY KEY (`scholarship_id`),
  CONSTRAINT `fk_ir_sch_norms_scholarship` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarship` (`scholarshipID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ir_scholarship_vectors`
--

DROP TABLE IF EXISTS `ir_scholarship_vectors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ir_scholarship_vectors` (
  `scholarship_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `tf_value` double NOT NULL,
  `tfidf_value` double NOT NULL,
  PRIMARY KEY (`scholarship_id`,`term_id`),
  KEY `idx_ir_sch_term` (`term_id`),
  CONSTRAINT `fk_ir_sch_vectors_scholarship` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarship` (`scholarshipID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ir_sch_vectors_term` FOREIGN KEY (`term_id`) REFERENCES `ir_vocabulary` (`term_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ir_stopwords`
--

DROP TABLE IF EXISTS `ir_stopwords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ir_stopwords` (
  `word` varchar(64) NOT NULL,
  PRIMARY KEY (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ir_student_documents`
--

DROP TABLE IF EXISTS `ir_student_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ir_student_documents` (
  `student_id` int(11) NOT NULL,
  `normalized_text` longtext NOT NULL,
  `profile_hash` char(40) NOT NULL,
  `corpus_hash` char(40) NOT NULL,
  `model_version` varchar(40) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`student_id`),
  KEY `idx_ir_student_doc_hash` (`profile_hash`,`corpus_hash`,`model_version`),
  CONSTRAINT `fk_ir_student_docs_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`studentID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ir_student_vectors`
--

DROP TABLE IF EXISTS `ir_student_vectors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ir_student_vectors` (
  `student_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `tf_value` double NOT NULL,
  `tfidf_value` double NOT NULL,
  PRIMARY KEY (`student_id`,`term_id`),
  KEY `idx_ir_student_term` (`term_id`),
  CONSTRAINT `fk_ir_student_vectors_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`studentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ir_student_vectors_term` FOREIGN KEY (`term_id`) REFERENCES `ir_vocabulary` (`term_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ir_synonyms`
--

DROP TABLE IF EXISTS `ir_synonyms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ir_synonyms` (
  `variant_term` varchar(191) NOT NULL,
  `canonical_term` varchar(191) NOT NULL,
  PRIMARY KEY (`variant_term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ir_vocabulary`
--

DROP TABLE IF EXISTS `ir_vocabulary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ir_vocabulary` (
  `term_id` int(11) NOT NULL AUTO_INCREMENT,
  `term` varchar(191) NOT NULL,
  `doc_freq` int(11) NOT NULL DEFAULT 0,
  `idf_value` double NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`term_id`),
  UNIQUE KEY `term` (`term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-06-23 23:14:06

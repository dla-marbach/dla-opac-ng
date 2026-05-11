-- MariaDB dump 10.19  Distrib 10.11.16-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: db
-- ------------------------------------------------------
-- Server version	10.11.16-MariaDB-ubu2204-log

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
-- Table structure for table `backend_layout`
--

DROP TABLE IF EXISTS `backend_layout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `backend_layout` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `crdate` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted` smallint(5) unsigned NOT NULL DEFAULT 0,
  `hidden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `t3_origuid` int(10) unsigned NOT NULL DEFAULT 0,
  `t3ver_oid` int(10) unsigned NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) unsigned NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `config` text NOT NULL,
  `icon` text DEFAULT NULL,
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backend_layout`
--

LOCK TABLES `backend_layout` WRITE;
/*!40000 ALTER TABLE `backend_layout` DISABLE KEYS */;
/*!40000 ALTER TABLE `backend_layout` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `be_dashboards`
--

DROP TABLE IF EXISTS `be_dashboards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `be_dashboards` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `crdate` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted` smallint(5) unsigned NOT NULL DEFAULT 0,
  `hidden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `starttime` int(10) unsigned NOT NULL DEFAULT 0,
  `endtime` int(10) unsigned NOT NULL DEFAULT 0,
  `identifier` varchar(120) NOT NULL DEFAULT '',
  `cruser_id` int(10) unsigned NOT NULL DEFAULT 0,
  `title` varchar(120) NOT NULL DEFAULT '',
  `widgets` text DEFAULT NULL,
  PRIMARY KEY (`uid`),
  KEY `identifier` (`identifier`),
  KEY `parent` (`pid`,`deleted`,`hidden`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `be_dashboards`
--

LOCK TABLES `be_dashboards` WRITE;
/*!40000 ALTER TABLE `be_dashboards` DISABLE KEYS */;
INSERT INTO `be_dashboards` VALUES
(1,0,1775859015,1775859015,0,0,0,0,'cfd24f2625218b39b29d99a37524271fbda93bb1',1,'My dashboard','{\"f409fc68202477477fb1324741cfdfc9a8cd8b6a\":{\"identifier\":\"t3information\"},\"a169c11020715d560fb0accfa6ee3a80c71a8430\":{\"identifier\":\"docGettingStarted\"}}');
/*!40000 ALTER TABLE `be_dashboards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `be_groups`
--

DROP TABLE IF EXISTS `be_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `be_groups` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `crdate` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted` smallint(5) unsigned NOT NULL DEFAULT 0,
  `hidden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `title` varchar(50) NOT NULL DEFAULT '',
  `non_exclude_fields` text DEFAULT NULL,
  `explicit_allowdeny` text DEFAULT NULL,
  `allowed_languages` varchar(255) NOT NULL DEFAULT '',
  `custom_options` text DEFAULT NULL,
  `db_mountpoints` text DEFAULT NULL,
  `pagetypes_select` text DEFAULT NULL,
  `tables_select` text DEFAULT NULL,
  `tables_modify` text DEFAULT NULL,
  `groupMods` text DEFAULT NULL,
  `availableWidgets` text DEFAULT NULL,
  `mfa_providers` text DEFAULT NULL,
  `file_mountpoints` text DEFAULT NULL,
  `file_permissions` text DEFAULT NULL,
  `TSconfig` text DEFAULT NULL,
  `subgroup` text DEFAULT NULL,
  `workspace_perms` smallint(6) NOT NULL DEFAULT 1,
  `category_perms` longtext DEFAULT NULL,
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `be_groups`
--

LOCK TABLES `be_groups` WRITE;
/*!40000 ALTER TABLE `be_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `be_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `be_sessions`
--

DROP TABLE IF EXISTS `be_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `be_sessions` (
  `ses_id` varchar(190) NOT NULL DEFAULT '',
  `ses_iplock` varchar(39) NOT NULL DEFAULT '',
  `ses_userid` int(10) unsigned NOT NULL DEFAULT 0,
  `ses_tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `ses_data` longblob DEFAULT NULL,
  PRIMARY KEY (`ses_id`),
  KEY `ses_tstamp` (`ses_tstamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `be_sessions`
--

LOCK TABLES `be_sessions` WRITE;
/*!40000 ALTER TABLE `be_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `be_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `be_users`
--

DROP TABLE IF EXISTS `be_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `be_users` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `crdate` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted` smallint(5) unsigned NOT NULL DEFAULT 0,
  `disable` smallint(5) unsigned NOT NULL DEFAULT 0,
  `starttime` int(10) unsigned NOT NULL DEFAULT 0,
  `endtime` int(10) unsigned NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `username` varchar(50) NOT NULL DEFAULT '',
  `avatar` int(10) unsigned NOT NULL DEFAULT 0,
  `password` varchar(255) NOT NULL DEFAULT '',
  `admin` smallint(5) unsigned NOT NULL DEFAULT 0,
  `usergroup` text DEFAULT NULL,
  `lang` varchar(10) NOT NULL DEFAULT 'default',
  `email` varchar(255) NOT NULL DEFAULT '',
  `db_mountpoints` text DEFAULT NULL,
  `options` smallint(5) unsigned NOT NULL DEFAULT 0,
  `realName` varchar(80) NOT NULL DEFAULT '',
  `userMods` text DEFAULT NULL,
  `allowed_languages` varchar(255) NOT NULL DEFAULT '',
  `uc` mediumblob DEFAULT NULL,
  `file_mountpoints` text DEFAULT NULL,
  `file_permissions` text DEFAULT NULL,
  `workspace_perms` smallint(6) NOT NULL DEFAULT 1,
  `TSconfig` text DEFAULT NULL,
  `workspace_id` int(11) NOT NULL DEFAULT 0,
  `mfa` mediumblob DEFAULT NULL,
  `category_perms` longtext DEFAULT NULL,
  `lastlogin` int(11) NOT NULL DEFAULT 0,
  `password_reset_token` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `username` (`username`),
  KEY `parent` (`pid`,`deleted`,`disable`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `be_users`
--

LOCK TABLES `be_users` WRITE;
/*!40000 ALTER TABLE `be_users` DISABLE KEYS */;
INSERT INTO `be_users` VALUES
(1,0,1775858890,1775858890,0,0,0,0,NULL,'admin',0,'$argon2i$v=19$m=65536,t=16,p=1$WnVoRDRFSWJJZmRLaVkzYQ$fnNcoIth0HM9o2im2ar5zZ/FvK2uyREoeN7RspEvg5w',1,NULL,'default','info@example.com',NULL,0,'',NULL,'','a:7:{s:10:\"moduleData\";a:6:{s:28:\"dashboard/current_dashboard/\";s:40:\"cfd24f2625218b39b29d99a37524271fbda93bb1\";s:57:\"TYPO3\\CMS\\Backend\\Utility\\BackendUtility::getUpdateSignal\";a:0:{}s:10:\"FormEngine\";a:2:{i:0;a:1:{s:32:\"750043f9a02785cc6557d2efe33fd403\";a:5:{i:0;s:15:\"[Default Title]\";i:1;a:5:{s:4:\"edit\";a:1:{s:5:\"pages\";a:1:{i:6;s:4:\"edit\";}}s:7:\"defVals\";N;s:12:\"overrideVals\";N;s:11:\"columnsOnly\";N;s:6:\"noView\";N;}i:2;s:28:\"&edit%5Bpages%5D%5B6%5D=edit\";i:3;a:5:{s:5:\"table\";s:5:\"pages\";s:3:\"uid\";i:6;s:3:\"pid\";i:2;s:3:\"cmd\";s:4:\"edit\";s:12:\"deleteAccess\";b:1;}i:4;s:91:\"/typo3/module/web/list?token=50e348a085297ce56a23a8b519d3f21a26ff9670&id=6&table=&pointer=1\";}}i:1;s:32:\"750043f9a02785cc6557d2efe33fd403\";}s:6:\"web_ts\";a:1:{s:6:\"action\";s:25:\"web_typoscript_infomodify\";}s:25:\"web_typoscript_infomodify\";a:1:{s:23:\"selectedTemplatePerPage\";a:3:{i:1;i:1;i:2;i:2;i:3;i:3;}}s:15:\"system_BelogLog\";a:1:{s:10:\"constraint\";s:337:\"O:39:\"TYPO3\\CMS\\Belog\\Domain\\Model\\Constraint\":11:{s:14:\"\0*\0userOrGroup\";s:1:\"0\";s:9:\"\0*\0number\";i:20;s:15:\"\0*\0workspaceUid\";i:-99;s:10:\"\0*\0channel\";s:3:\"php\";s:8:\"\0*\0level\";s:5:\"debug\";s:17:\"\0*\0startTimestamp\";i:0;s:15:\"\0*\0endTimestamp\";i:0;s:18:\"\0*\0manualDateStart\";N;s:17:\"\0*\0manualDateStop\";N;s:9:\"\0*\0pageId\";i:0;s:8:\"\0*\0depth\";i:0;}\";}}s:14:\"emailMeAtLogin\";i:0;s:8:\"titleLen\";i:50;s:20:\"edit_docModuleUpload\";s:1:\"1\";s:15:\"moduleSessionID\";a:6:{s:28:\"dashboard/current_dashboard/\";s:40:\"32f853f3acdc2da655558e76922f6fe511e2abf0\";s:57:\"TYPO3\\CMS\\Backend\\Utility\\BackendUtility::getUpdateSignal\";s:40:\"25b840a9590422c9770e62754c1176623ed4ca8a\";s:10:\"FormEngine\";s:40:\"25b840a9590422c9770e62754c1176623ed4ca8a\";s:6:\"web_ts\";s:40:\"32f853f3acdc2da655558e76922f6fe511e2abf0\";s:25:\"web_typoscript_infomodify\";s:40:\"32f853f3acdc2da655558e76922f6fe511e2abf0\";s:15:\"system_BelogLog\";s:40:\"b933e37f93f193c5a4c5685c5be8d438f96de611\";}s:17:\"BackendComponents\";a:1:{s:6:\"States\";a:1:{s:8:\"Pagetree\";a:1:{s:9:\"stateHash\";a:3:{s:3:\"0_0\";s:1:\"1\";s:3:\"0_1\";s:1:\"1\";s:3:\"0_2\";s:1:\"1\";}}}}s:17:\"systeminformation\";s:45:\"{\"system_BelogLog\":{\"lastAccess\":1776158736}}\";}',NULL,NULL,1,NULL,0,NULL,NULL,1776242939,''),
(2,0,1776243427,1776243427,0,0,0,0,NULL,'_cli_',0,'$argon2i$v=19$m=65536,t=16,p=1$TG4xbmNYOTVRVjB0aUw2cA$wJiDHWvjM+aFWsfGLfwWRf3lI4pqrY4phaHHLN7kazM',1,NULL,'default','',NULL,0,'',NULL,'','a:4:{s:10:\"moduleData\";a:0:{}s:14:\"emailMeAtLogin\";i:0;s:8:\"titleLen\";i:50;s:20:\"edit_docModuleUpload\";s:1:\"1\";}',NULL,NULL,1,NULL,0,NULL,NULL,0,'');
/*!40000 ALTER TABLE `be_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_hash`
--

DROP TABLE IF EXISTS `cache_hash`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_hash` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(250) NOT NULL DEFAULT '',
  `expires` int(10) unsigned NOT NULL DEFAULT 0,
  `content` longblob DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cache_id` (`identifier`(180),`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_hash`
--

LOCK TABLES `cache_hash` WRITE;
/*!40000 ALTER TABLE `cache_hash` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_hash` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_hash_tags`
--

DROP TABLE IF EXISTS `cache_hash_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_hash_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(250) NOT NULL DEFAULT '',
  `tag` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `cache_id` (`identifier`(191)),
  KEY `cache_tag` (`tag`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_hash_tags`
--

LOCK TABLES `cache_hash_tags` WRITE;
/*!40000 ALTER TABLE `cache_hash_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_hash_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_imagesizes`
--

DROP TABLE IF EXISTS `cache_imagesizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_imagesizes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(250) NOT NULL DEFAULT '',
  `expires` int(10) unsigned NOT NULL DEFAULT 0,
  `content` longblob DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cache_id` (`identifier`(180),`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_imagesizes`
--

LOCK TABLES `cache_imagesizes` WRITE;
/*!40000 ALTER TABLE `cache_imagesizes` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_imagesizes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_imagesizes_tags`
--

DROP TABLE IF EXISTS `cache_imagesizes_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_imagesizes_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(250) NOT NULL DEFAULT '',
  `tag` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `cache_id` (`identifier`(191)),
  KEY `cache_tag` (`tag`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_imagesizes_tags`
--

LOCK TABLES `cache_imagesizes_tags` WRITE;
/*!40000 ALTER TABLE `cache_imagesizes_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_imagesizes_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_pages`
--

DROP TABLE IF EXISTS `cache_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(250) NOT NULL DEFAULT '',
  `expires` int(10) unsigned NOT NULL DEFAULT 0,
  `content` longblob DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cache_id` (`identifier`(180),`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_pages`
--

LOCK TABLES `cache_pages` WRITE;
/*!40000 ALTER TABLE `cache_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_pages_tags`
--

DROP TABLE IF EXISTS `cache_pages_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_pages_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(250) NOT NULL DEFAULT '',
  `tag` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `cache_id` (`identifier`(191)),
  KEY `cache_tag` (`tag`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_pages_tags`
--

LOCK TABLES `cache_pages_tags` WRITE;
/*!40000 ALTER TABLE `cache_pages_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_pages_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_rootline`
--

DROP TABLE IF EXISTS `cache_rootline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_rootline` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(250) NOT NULL DEFAULT '',
  `expires` int(10) unsigned NOT NULL DEFAULT 0,
  `content` longblob DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cache_id` (`identifier`(180),`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_rootline`
--

LOCK TABLES `cache_rootline` WRITE;
/*!40000 ALTER TABLE `cache_rootline` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_rootline` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_rootline_tags`
--

DROP TABLE IF EXISTS `cache_rootline_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_rootline_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(250) NOT NULL DEFAULT '',
  `tag` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `cache_id` (`identifier`(191)),
  KEY `cache_tag` (`tag`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_rootline_tags`
--

LOCK TABLES `cache_rootline_tags` WRITE;
/*!40000 ALTER TABLE `cache_rootline_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_rootline_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_treelist`
--

DROP TABLE IF EXISTS `cache_treelist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_treelist` (
  `md5hash` varchar(32) NOT NULL DEFAULT '',
  `pid` int(11) NOT NULL DEFAULT 0,
  `treelist` mediumtext DEFAULT NULL,
  `tstamp` int(11) NOT NULL DEFAULT 0,
  `expires` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`md5hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_treelist`
--

LOCK TABLES `cache_treelist` WRITE;
/*!40000 ALTER TABLE `cache_treelist` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_treelist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_vhs_main`
--

DROP TABLE IF EXISTS `cache_vhs_main`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_vhs_main` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(250) NOT NULL DEFAULT '',
  `expires` int(10) unsigned NOT NULL DEFAULT 0,
  `content` longblob DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cache_id` (`identifier`(180),`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_vhs_main`
--

LOCK TABLES `cache_vhs_main` WRITE;
/*!40000 ALTER TABLE `cache_vhs_main` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_vhs_main` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_vhs_main_tags`
--

DROP TABLE IF EXISTS `cache_vhs_main_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_vhs_main_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(250) NOT NULL DEFAULT '',
  `tag` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `cache_id` (`identifier`(191)),
  KEY `cache_tag` (`tag`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_vhs_main_tags`
--

LOCK TABLES `cache_vhs_main_tags` WRITE;
/*!40000 ALTER TABLE `cache_vhs_main_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_vhs_main_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_vhs_markdown`
--

DROP TABLE IF EXISTS `cache_vhs_markdown`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_vhs_markdown` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(250) NOT NULL DEFAULT '',
  `expires` int(10) unsigned NOT NULL DEFAULT 0,
  `content` longblob DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cache_id` (`identifier`(180),`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_vhs_markdown`
--

LOCK TABLES `cache_vhs_markdown` WRITE;
/*!40000 ALTER TABLE `cache_vhs_markdown` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_vhs_markdown` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_vhs_markdown_tags`
--

DROP TABLE IF EXISTS `cache_vhs_markdown_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_vhs_markdown_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(250) NOT NULL DEFAULT '',
  `tag` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `cache_id` (`identifier`(191)),
  KEY `cache_tag` (`tag`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_vhs_markdown_tags`
--

LOCK TABLES `cache_vhs_markdown_tags` WRITE;
/*!40000 ALTER TABLE `cache_vhs_markdown_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_vhs_markdown_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `content_types`
--

DROP TABLE IF EXISTS `content_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `content_types` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(11) NOT NULL DEFAULT 0,
  `crdate` int(11) NOT NULL DEFAULT 0,
  `t3_origuid` int(11) NOT NULL DEFAULT 0,
  `editlock` smallint(6) NOT NULL DEFAULT 0,
  `deleted` smallint(6) NOT NULL DEFAULT 0,
  `hidden` smallint(6) NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `cruser_id` int(11) NOT NULL DEFAULT 0,
  `t3ver_oid` int(11) NOT NULL DEFAULT 0,
  `t3ver_id` int(11) NOT NULL DEFAULT 0,
  `t3ver_wsid` int(11) NOT NULL DEFAULT 0,
  `t3ver_label` varchar(30) NOT NULL DEFAULT '',
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_count` int(11) NOT NULL DEFAULT 0,
  `t3ver_tstamp` int(11) NOT NULL DEFAULT 0,
  `t3ver_move_id` int(11) NOT NULL DEFAULT 0,
  `title` varchar(400) DEFAULT NULL,
  `content_type` varchar(128) DEFAULT NULL,
  `extension_identity` varchar(128) NOT NULL DEFAULT 'FluidTYPO3.Flux',
  `content_configuration` text DEFAULT NULL,
  `grid` text DEFAULT NULL,
  `template_source` text DEFAULT NULL,
  `template_file` varchar(128) DEFAULT NULL,
  `icon` varchar(200) DEFAULT NULL,
  `validation` varchar(1) DEFAULT NULL,
  `template_dump` varchar(1) DEFAULT NULL,
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`),
  KEY `content_type` (`content_type`),
  KEY `fe_enabled` (`deleted`,`hidden`),
  KEY `extension_identity` (`extension_identity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `content_types`
--

LOCK TABLES `content_types` WRITE;
/*!40000 ALTER TABLE `content_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `content_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fe_groups`
--

DROP TABLE IF EXISTS `fe_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fe_groups` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `crdate` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted` smallint(5) unsigned NOT NULL DEFAULT 0,
  `hidden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `tx_extbase_type` varchar(255) NOT NULL DEFAULT '0',
  `title` varchar(50) NOT NULL DEFAULT '',
  `subgroup` tinytext DEFAULT NULL,
  `TSconfig` text DEFAULT NULL,
  `felogin_redirectPid` tinytext DEFAULT NULL,
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fe_groups`
--

LOCK TABLES `fe_groups` WRITE;
/*!40000 ALTER TABLE `fe_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `fe_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fe_sessions`
--

DROP TABLE IF EXISTS `fe_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fe_sessions` (
  `ses_id` varchar(190) NOT NULL DEFAULT '',
  `ses_iplock` varchar(39) NOT NULL DEFAULT '',
  `ses_userid` int(10) unsigned NOT NULL DEFAULT 0,
  `ses_tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `ses_data` mediumblob DEFAULT NULL,
  `ses_permanent` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`ses_id`),
  KEY `ses_tstamp` (`ses_tstamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fe_sessions`
--

LOCK TABLES `fe_sessions` WRITE;
/*!40000 ALTER TABLE `fe_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `fe_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fe_users`
--

DROP TABLE IF EXISTS `fe_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fe_users` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `crdate` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted` smallint(5) unsigned NOT NULL DEFAULT 0,
  `disable` smallint(5) unsigned NOT NULL DEFAULT 0,
  `starttime` int(10) unsigned NOT NULL DEFAULT 0,
  `endtime` int(10) unsigned NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `tx_extbase_type` varchar(255) NOT NULL DEFAULT '0',
  `lastlogin` int(11) NOT NULL DEFAULT 0,
  `username` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '',
  `usergroup` text DEFAULT NULL,
  `name` varchar(160) NOT NULL DEFAULT '',
  `first_name` varchar(50) NOT NULL DEFAULT '',
  `middle_name` varchar(50) NOT NULL DEFAULT '',
  `last_name` varchar(50) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  `telephone` varchar(30) NOT NULL DEFAULT '',
  `fax` varchar(30) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `uc` blob DEFAULT NULL,
  `title` varchar(40) NOT NULL DEFAULT '',
  `zip` varchar(10) NOT NULL DEFAULT '',
  `city` varchar(50) NOT NULL DEFAULT '',
  `country` varchar(40) NOT NULL DEFAULT '',
  `www` varchar(80) NOT NULL DEFAULT '',
  `company` varchar(80) NOT NULL DEFAULT '',
  `image` tinytext DEFAULT NULL,
  `TSconfig` text DEFAULT NULL,
  `is_online` int(10) unsigned NOT NULL DEFAULT 0,
  `mfa` mediumblob DEFAULT NULL,
  `felogin_redirectPid` tinytext DEFAULT NULL,
  `felogin_forgotHash` varchar(80) DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`username`(100)),
  KEY `username` (`username`(100)),
  KEY `is_online` (`is_online`),
  KEY `felogin_forgotHash` (`felogin_forgotHash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fe_users`
--

LOCK TABLES `fe_users` WRITE;
/*!40000 ALTER TABLE `fe_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `fe_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `flux_field`
--

DROP TABLE IF EXISTS `flux_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `flux_field` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(11) NOT NULL DEFAULT 0,
  `crdate` int(11) NOT NULL DEFAULT 0,
  `cruser_id` int(11) NOT NULL DEFAULT 0,
  `editlock` smallint(6) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(11) NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumtext DEFAULT NULL,
  `parent_field` int(11) NOT NULL DEFAULT 0,
  `sheet` int(11) NOT NULL DEFAULT 0,
  `field_name` varchar(255) DEFAULT NULL,
  `field_label` mediumtext DEFAULT NULL,
  `field_type` varchar(32) DEFAULT NULL,
  `field_value` text DEFAULT NULL,
  `field_options` text DEFAULT NULL,
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`),
  KEY `parent_field` (`parent_field`),
  KEY `sheet` (`sheet`),
  KEY `field_value` (`field_value`(32))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `flux_field`
--

LOCK TABLES `flux_field` WRITE;
/*!40000 ALTER TABLE `flux_field` DISABLE KEYS */;
/*!40000 ALTER TABLE `flux_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `flux_sheet`
--

DROP TABLE IF EXISTS `flux_sheet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `flux_sheet` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL DEFAULT 0,
  `tstamp` int(11) NOT NULL DEFAULT 0,
  `crdate` int(11) NOT NULL DEFAULT 0,
  `cruser_id` int(11) NOT NULL DEFAULT 0,
  `editlock` smallint(6) NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(11) NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumtext DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `sheet_label` mediumtext DEFAULT NULL,
  `source_table` varchar(255) DEFAULT NULL,
  `source_field` varchar(255) DEFAULT NULL,
  `source_uid` int(11) NOT NULL DEFAULT 0,
  `form_fields` int(11) NOT NULL DEFAULT 0,
  `json_data` text DEFAULT NULL,
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `flux_sheet`
--

LOCK TABLES `flux_sheet` WRITE;
/*!40000 ALTER TABLE `flux_sheet` DISABLE KEYS */;
/*!40000 ALTER TABLE `flux_sheet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `crdate` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted` smallint(5) unsigned NOT NULL DEFAULT 0,
  `hidden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `starttime` int(10) unsigned NOT NULL DEFAULT 0,
  `endtime` int(10) unsigned NOT NULL DEFAULT 0,
  `fe_group` varchar(255) NOT NULL DEFAULT '0',
  `sorting` int(11) NOT NULL DEFAULT 0,
  `rowDescription` text DEFAULT NULL,
  `editlock` smallint(5) unsigned NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) unsigned NOT NULL DEFAULT 0,
  `l10n_source` int(10) unsigned NOT NULL DEFAULT 0,
  `l10n_state` text DEFAULT NULL,
  `t3_origuid` int(10) unsigned NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) unsigned NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) unsigned NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `perms_userid` int(10) unsigned NOT NULL DEFAULT 0,
  `perms_groupid` int(10) unsigned NOT NULL DEFAULT 0,
  `perms_user` smallint(5) unsigned NOT NULL DEFAULT 0,
  `perms_group` smallint(5) unsigned NOT NULL DEFAULT 0,
  `perms_everybody` smallint(5) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `doktype` int(10) unsigned NOT NULL DEFAULT 0,
  `TSconfig` text DEFAULT NULL,
  `is_siteroot` smallint(6) NOT NULL DEFAULT 0,
  `php_tree_stop` smallint(6) NOT NULL DEFAULT 0,
  `url` varchar(255) NOT NULL DEFAULT '',
  `shortcut` int(10) unsigned NOT NULL DEFAULT 0,
  `shortcut_mode` int(10) unsigned NOT NULL DEFAULT 0,
  `subtitle` varchar(255) NOT NULL DEFAULT '',
  `layout` int(10) unsigned NOT NULL DEFAULT 0,
  `target` varchar(80) NOT NULL DEFAULT '',
  `media` int(10) unsigned NOT NULL DEFAULT 0,
  `keywords` text DEFAULT NULL,
  `cache_timeout` int(10) unsigned NOT NULL DEFAULT 0,
  `cache_tags` varchar(255) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `no_search` smallint(5) unsigned NOT NULL DEFAULT 0,
  `SYS_LASTCHANGED` int(10) unsigned NOT NULL DEFAULT 0,
  `abstract` text DEFAULT NULL,
  `module` varchar(255) NOT NULL DEFAULT '',
  `extendToSubpages` smallint(5) unsigned NOT NULL DEFAULT 0,
  `author` varchar(255) NOT NULL DEFAULT '',
  `author_email` varchar(255) NOT NULL DEFAULT '',
  `nav_title` varchar(255) NOT NULL DEFAULT '',
  `nav_hide` smallint(6) NOT NULL DEFAULT 0,
  `content_from_pid` int(10) unsigned NOT NULL DEFAULT 0,
  `mount_pid` int(10) unsigned NOT NULL DEFAULT 0,
  `mount_pid_ol` smallint(6) NOT NULL DEFAULT 0,
  `l18n_cfg` smallint(6) NOT NULL DEFAULT 0,
  `backend_layout` varchar(64) NOT NULL DEFAULT '',
  `backend_layout_next_level` varchar(64) NOT NULL DEFAULT '',
  `tsconfig_includes` text DEFAULT NULL,
  `categories` int(10) unsigned NOT NULL DEFAULT 0,
  `lastUpdated` int(11) NOT NULL DEFAULT 0,
  `newUntil` int(11) NOT NULL DEFAULT 0,
  `slug` varchar(2048) DEFAULT NULL,
  `tx_impexp_origuid` int(11) NOT NULL DEFAULT 0,
  `seo_title` varchar(255) NOT NULL DEFAULT '',
  `no_index` smallint(6) NOT NULL DEFAULT 0,
  `no_follow` smallint(6) NOT NULL DEFAULT 0,
  `og_title` varchar(255) NOT NULL DEFAULT '',
  `og_description` text DEFAULT NULL,
  `og_image` int(10) unsigned NOT NULL DEFAULT 0,
  `twitter_title` varchar(255) NOT NULL DEFAULT '',
  `twitter_description` text DEFAULT NULL,
  `twitter_image` int(10) unsigned NOT NULL DEFAULT 0,
  `twitter_card` varchar(255) NOT NULL DEFAULT '',
  `canonical_link` varchar(2048) NOT NULL DEFAULT '',
  `sitemap_priority` decimal(2,1) NOT NULL DEFAULT 0.5,
  `sitemap_changefreq` varchar(10) NOT NULL DEFAULT '',
  `tx_fed_page_flexform` text DEFAULT NULL,
  `tx_fed_page_flexform_sub` text DEFAULT NULL,
  `tx_fed_page_controller_action` varchar(255) NOT NULL DEFAULT '',
  `tx_fed_page_controller_action_sub` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `determineSiteRoot` (`is_siteroot`),
  KEY `language_identifier` (`l10n_parent`,`sys_language_uid`),
  KEY `slug` (`slug`(127)),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `translation_source` (`l10n_source`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES
(1,0,1775859151,1775859040,0,0,0,0,'0',256,NULL,0,0,0,0,NULL,0,'{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'Start',1,NULL,0,0,'',0,0,'',0,'',0,NULL,0,'',NULL,0,1775859151,NULL,'',0,'','','',0,0,0,0,0,'','',NULL,0,0,0,'/',0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',NULL,NULL,'',''),
(2,1,1775859548,1775859480,0,0,0,0,'',256,NULL,0,0,0,0,NULL,0,'{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'Katalog',1,NULL,0,0,'',0,0,'',0,'',0,NULL,0,'',NULL,0,1775859548,NULL,'',0,'','','',0,0,0,0,0,'','',NULL,0,0,0,'/katalog',0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',NULL,NULL,'',''),
(3,2,1775859904,1775859557,0,0,0,0,'',256,NULL,0,0,0,0,NULL,0,'{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\",\"tx_fed_page_controller_action\":\"\",\"tx_fed_page_controller_action_sub\":\"\"}',0,0,0,0,1,0,31,27,0,'Find',1,NULL,0,0,'',0,0,'',0,'',0,NULL,0,'',NULL,0,1775859904,NULL,'',0,'','','',0,0,0,0,0,'','',NULL,0,0,0,'/find',0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',NULL,NULL,'',''),
(4,2,1775860005,1775859587,0,0,0,0,'',128,NULL,0,0,0,0,NULL,0,'{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\",\"tx_fed_page_controller_action\":\"\",\"tx_fed_page_controller_action_sub\":\"\"}',0,0,0,0,1,0,31,27,0,'Bestandsübersicht',1,NULL,0,0,'',0,0,'',0,'',0,NULL,0,'',NULL,0,1775860005,NULL,'',0,'','','',0,0,0,0,0,'','',NULL,0,0,0,'/bestandsuebersicht',0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',NULL,NULL,'',''),
(5,2,1775860020,1775859631,0,0,0,0,'',64,NULL,0,0,0,0,NULL,0,'{\"doktype\":\"\",\"title\":\"\",\"slug\":\"\",\"nav_title\":\"\",\"subtitle\":\"\",\"seo_title\":\"\",\"description\":\"\",\"no_index\":\"\",\"no_follow\":\"\",\"canonical_link\":\"\",\"sitemap_changefreq\":\"\",\"sitemap_priority\":\"\",\"og_title\":\"\",\"og_description\":\"\",\"og_image\":\"\",\"twitter_title\":\"\",\"twitter_description\":\"\",\"twitter_image\":\"\",\"twitter_card\":\"\",\"abstract\":\"\",\"keywords\":\"\",\"author\":\"\",\"author_email\":\"\",\"lastUpdated\":\"\",\"layout\":\"\",\"newUntil\":\"\",\"backend_layout\":\"\",\"backend_layout_next_level\":\"\",\"content_from_pid\":\"\",\"target\":\"\",\"cache_timeout\":\"\",\"cache_tags\":\"\",\"is_siteroot\":\"\",\"no_search\":\"\",\"php_tree_stop\":\"\",\"module\":\"\",\"media\":\"\",\"tsconfig_includes\":\"\",\"TSconfig\":\"\",\"l18n_cfg\":\"\",\"hidden\":\"\",\"nav_hide\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"extendToSubpages\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\",\"tx_fed_page_controller_action\":\"\",\"tx_fed_page_controller_action_sub\":\"\"}',0,0,0,0,1,0,31,27,0,'Systematik',1,NULL,0,0,'',0,0,'',0,'',0,NULL,0,'',NULL,0,1775860020,NULL,'',0,'','','',0,0,0,0,0,'','',NULL,0,0,0,'/systematik',0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',NULL,NULL,'',''),
(6,2,1776243181,1776243151,0,0,0,0,'0',32,NULL,0,0,0,0,NULL,0,'{\"hidden\":\"\"}',0,0,0,0,1,0,31,27,0,'Daten',254,NULL,0,0,'',0,0,'',0,'',0,NULL,0,'',NULL,0,0,NULL,'',0,'','','',0,0,0,0,0,'','',NULL,0,0,0,'/katalog/default-title',0,'',0,0,'',NULL,0,'',NULL,0,'summary','',0.5,'',NULL,NULL,'','');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_be_shortcuts`
--

DROP TABLE IF EXISTS `sys_be_shortcuts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_be_shortcuts` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT 0,
  `route` varchar(255) NOT NULL DEFAULT '',
  `arguments` text DEFAULT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `sorting` int(11) NOT NULL DEFAULT 0,
  `sc_group` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`),
  KEY `event` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_be_shortcuts`
--

LOCK TABLES `sys_be_shortcuts` WRITE;
/*!40000 ALTER TABLE `sys_be_shortcuts` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_be_shortcuts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_category`
--

DROP TABLE IF EXISTS `sys_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_category` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `crdate` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted` smallint(5) unsigned NOT NULL DEFAULT 0,
  `hidden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `starttime` int(10) unsigned NOT NULL DEFAULT 0,
  `endtime` int(10) unsigned NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) unsigned NOT NULL DEFAULT 0,
  `l10n_state` text DEFAULT NULL,
  `t3_origuid` int(10) unsigned NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) unsigned NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) unsigned NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `title` tinytext NOT NULL,
  `items` int(11) NOT NULL DEFAULT 0,
  `parent` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`),
  KEY `category_parent` (`parent`),
  KEY `category_list` (`pid`,`deleted`,`sys_language_uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_category`
--

LOCK TABLES `sys_category` WRITE;
/*!40000 ALTER TABLE `sys_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_category_record_mm`
--

DROP TABLE IF EXISTS `sys_category_record_mm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_category_record_mm` (
  `uid_local` int(10) unsigned NOT NULL DEFAULT 0,
  `uid_foreign` int(10) unsigned NOT NULL DEFAULT 0,
  `tablenames` varchar(64) NOT NULL DEFAULT '',
  `fieldname` varchar(64) NOT NULL DEFAULT '',
  `sorting` int(10) unsigned NOT NULL DEFAULT 0,
  `sorting_foreign` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid_local`,`uid_foreign`,`tablenames`,`fieldname`),
  KEY `uid_local` (`uid_local`),
  KEY `uid_foreign` (`uid_foreign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_category_record_mm`
--

LOCK TABLES `sys_category_record_mm` WRITE;
/*!40000 ALTER TABLE `sys_category_record_mm` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_category_record_mm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_csp_resolution`
--

DROP TABLE IF EXISTS `sys_csp_resolution`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_csp_resolution` (
  `summary` varchar(40) NOT NULL,
  `created` int(10) unsigned NOT NULL,
  `scope` varchar(264) NOT NULL,
  `mutation_identifier` text DEFAULT NULL,
  `mutation_collection` mediumtext DEFAULT NULL,
  `meta` mediumtext DEFAULT NULL,
  PRIMARY KEY (`summary`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_csp_resolution`
--

LOCK TABLES `sys_csp_resolution` WRITE;
/*!40000 ALTER TABLE `sys_csp_resolution` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_csp_resolution` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_file`
--

DROP TABLE IF EXISTS `sys_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_file` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `last_indexed` int(11) NOT NULL DEFAULT 0,
  `missing` smallint(6) NOT NULL DEFAULT 0,
  `storage` int(11) NOT NULL DEFAULT 0,
  `type` varchar(10) NOT NULL DEFAULT '',
  `metadata` int(11) NOT NULL DEFAULT 0,
  `identifier` text DEFAULT NULL,
  `identifier_hash` varchar(40) NOT NULL DEFAULT '',
  `folder_hash` varchar(40) NOT NULL DEFAULT '',
  `extension` varchar(255) NOT NULL DEFAULT '',
  `mime_type` varchar(255) NOT NULL DEFAULT '',
  `name` tinytext DEFAULT NULL,
  `sha1` varchar(40) NOT NULL DEFAULT '',
  `size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `creation_date` int(11) NOT NULL DEFAULT 0,
  `modification_date` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`),
  KEY `sel01` (`storage`,`identifier_hash`),
  KEY `folder` (`storage`,`folder_hash`),
  KEY `tstamp` (`tstamp`),
  KEY `lastindex` (`last_indexed`),
  KEY `sha1` (`sha1`),
  KEY `parent` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_file`
--

LOCK TABLES `sys_file` WRITE;
/*!40000 ALTER TABLE `sys_file` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_file` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_file_collection`
--

DROP TABLE IF EXISTS `sys_file_collection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_file_collection` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `crdate` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted` smallint(5) unsigned NOT NULL DEFAULT 0,
  `hidden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `starttime` int(10) unsigned NOT NULL DEFAULT 0,
  `endtime` int(10) unsigned NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) unsigned NOT NULL DEFAULT 0,
  `l10n_state` text DEFAULT NULL,
  `t3_origuid` int(10) unsigned NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) unsigned NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) unsigned NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `title` tinytext DEFAULT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'static',
  `files` int(11) NOT NULL DEFAULT 0,
  `folder_identifier` varchar(255) NOT NULL DEFAULT '',
  `recursive` smallint(6) NOT NULL DEFAULT 0,
  `category` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_file_collection`
--

LOCK TABLES `sys_file_collection` WRITE;
/*!40000 ALTER TABLE `sys_file_collection` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_file_collection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_file_metadata`
--

DROP TABLE IF EXISTS `sys_file_metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_file_metadata` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `crdate` int(10) unsigned NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) unsigned NOT NULL DEFAULT 0,
  `l10n_state` text DEFAULT NULL,
  `t3_origuid` int(10) unsigned NOT NULL DEFAULT 0,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) unsigned NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) unsigned NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `file` int(11) NOT NULL DEFAULT 0,
  `title` tinytext DEFAULT NULL,
  `width` int(11) NOT NULL DEFAULT 0,
  `height` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `alternative` text DEFAULT NULL,
  `categories` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`),
  KEY `file` (`file`),
  KEY `fal_filelist` (`l10n_parent`,`sys_language_uid`),
  KEY `parent` (`pid`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_file_metadata`
--

LOCK TABLES `sys_file_metadata` WRITE;
/*!40000 ALTER TABLE `sys_file_metadata` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_file_metadata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_file_processedfile`
--

DROP TABLE IF EXISTS `sys_file_processedfile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_file_processedfile` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `tstamp` int(11) NOT NULL DEFAULT 0,
  `crdate` int(11) NOT NULL DEFAULT 0,
  `storage` int(11) NOT NULL DEFAULT 0,
  `original` int(11) NOT NULL DEFAULT 0,
  `identifier` varchar(512) NOT NULL DEFAULT '',
  `name` tinytext DEFAULT NULL,
  `processing_url` text DEFAULT NULL,
  `configuration` blob DEFAULT NULL,
  `configurationsha1` varchar(40) NOT NULL DEFAULT '',
  `originalfilesha1` varchar(40) NOT NULL DEFAULT '',
  `task_type` varchar(200) NOT NULL DEFAULT '',
  `checksum` varchar(32) NOT NULL DEFAULT '',
  `width` int(11) DEFAULT 0,
  `height` int(11) DEFAULT 0,
  PRIMARY KEY (`uid`),
  KEY `combined_1` (`original`,`task_type`(100),`configurationsha1`),
  KEY `identifier` (`storage`,`identifier`(180))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_file_processedfile`
--

LOCK TABLES `sys_file_processedfile` WRITE;
/*!40000 ALTER TABLE `sys_file_processedfile` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_file_processedfile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_file_reference`
--

DROP TABLE IF EXISTS `sys_file_reference`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_file_reference` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `crdate` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted` smallint(5) unsigned NOT NULL DEFAULT 0,
  `hidden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l10n_parent` int(10) unsigned NOT NULL DEFAULT 0,
  `l10n_state` text DEFAULT NULL,
  `l10n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) unsigned NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) unsigned NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `uid_local` int(11) NOT NULL DEFAULT 0,
  `uid_foreign` int(11) NOT NULL DEFAULT 0,
  `tablenames` varchar(64) NOT NULL DEFAULT '',
  `fieldname` varchar(64) NOT NULL DEFAULT '',
  `sorting_foreign` int(11) NOT NULL DEFAULT 0,
  `title` tinytext DEFAULT NULL,
  `description` text DEFAULT NULL,
  `alternative` text DEFAULT NULL,
  `link` varchar(1024) NOT NULL DEFAULT '',
  `crop` varchar(4000) NOT NULL DEFAULT '',
  `autoplay` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`),
  KEY `tablenames_fieldname` (`tablenames`(32),`fieldname`(12)),
  KEY `deleted` (`deleted`),
  KEY `uid_local` (`uid_local`),
  KEY `uid_foreign` (`uid_foreign`),
  KEY `combined_1` (`l10n_parent`,`t3ver_oid`,`t3ver_wsid`,`t3ver_state`,`deleted`),
  KEY `parent` (`pid`,`deleted`,`hidden`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_file_reference`
--

LOCK TABLES `sys_file_reference` WRITE;
/*!40000 ALTER TABLE `sys_file_reference` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_file_reference` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_file_storage`
--

DROP TABLE IF EXISTS `sys_file_storage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_file_storage` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `crdate` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted` smallint(5) unsigned NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `driver` tinytext DEFAULT NULL,
  `configuration` text DEFAULT NULL,
  `is_default` smallint(6) NOT NULL DEFAULT 0,
  `is_browsable` smallint(6) NOT NULL DEFAULT 0,
  `is_public` smallint(6) NOT NULL DEFAULT 0,
  `is_writable` smallint(6) NOT NULL DEFAULT 0,
  `is_online` smallint(6) NOT NULL DEFAULT 1,
  `auto_extract_metadata` smallint(6) NOT NULL DEFAULT 1,
  `processingfolder` tinytext DEFAULT NULL,
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_file_storage`
--

LOCK TABLES `sys_file_storage` WRITE;
/*!40000 ALTER TABLE `sys_file_storage` DISABLE KEYS */;
INSERT INTO `sys_file_storage` VALUES
(1,0,1775859495,1775859495,0,'This is the local fileadmin/ directory. This storage mount has been created automatically by TYPO3.','fileadmin','Local','<?xml version=\"1.0\" encoding=\"utf-8\" standalone=\"yes\" ?>\n<T3FlexForms>\n    <data>\n        <sheet index=\"sDEF\">\n            <language index=\"lDEF\">\n                <field index=\"basePath\">\n                    <value index=\"vDEF\">fileadmin/</value>\n                </field>\n                <field index=\"pathType\">\n                    <value index=\"vDEF\">relative</value>\n                </field>\n                <field index=\"caseSensitive\">\n                    <value index=\"vDEF\">1</value>\n                </field>\n            </language>\n        </sheet>\n    </data>\n</T3FlexForms>',1,1,1,1,1,1,NULL);
/*!40000 ALTER TABLE `sys_file_storage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_filemounts`
--

DROP TABLE IF EXISTS `sys_filemounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_filemounts` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted` smallint(5) unsigned NOT NULL DEFAULT 0,
  `hidden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `identifier` varchar(255) NOT NULL DEFAULT '',
  `read_only` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_filemounts`
--

LOCK TABLES `sys_filemounts` WRITE;
/*!40000 ALTER TABLE `sys_filemounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_filemounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_history`
--

DROP TABLE IF EXISTS `sys_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_history` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `actiontype` smallint(6) NOT NULL DEFAULT 0,
  `usertype` varchar(2) NOT NULL DEFAULT 'BE',
  `userid` int(10) unsigned DEFAULT NULL,
  `originaluserid` int(10) unsigned DEFAULT NULL,
  `recuid` int(11) NOT NULL DEFAULT 0,
  `tablename` varchar(255) NOT NULL DEFAULT '',
  `history_data` mediumtext DEFAULT NULL,
  `workspace` int(11) DEFAULT 0,
  `correlation_id` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `recordident_1` (`tablename`(100),`recuid`),
  KEY `recordident_2` (`tablename`(100),`tstamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_history`
--

LOCK TABLES `sys_history` WRITE;
/*!40000 ALTER TABLE `sys_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_http_report`
--

DROP TABLE IF EXISTS `sys_http_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_http_report` (
  `uuid` varchar(36) NOT NULL,
  `status` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created` int(10) unsigned NOT NULL,
  `changed` int(10) unsigned NOT NULL,
  `type` varchar(32) NOT NULL,
  `scope` varchar(100) NOT NULL,
  `request_time` bigint(20) unsigned NOT NULL,
  `meta` mediumtext DEFAULT NULL,
  `details` mediumtext DEFAULT NULL,
  `summary` varchar(40) NOT NULL,
  PRIMARY KEY (`uuid`),
  KEY `type_scope` (`type`,`scope`),
  KEY `created` (`created`),
  KEY `changed` (`changed`),
  KEY `request_time` (`request_time`),
  KEY `summary_created` (`summary`,`created`),
  KEY `all_conditions` (`type`,`status`,`scope`,`summary`,`request_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_http_report`
--

LOCK TABLES `sys_http_report` WRITE;
/*!40000 ALTER TABLE `sys_http_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_http_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_lockedrecords`
--

DROP TABLE IF EXISTS `sys_lockedrecords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_lockedrecords` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `record_table` varchar(255) NOT NULL DEFAULT '',
  `record_uid` int(11) NOT NULL DEFAULT 0,
  `record_pid` int(11) NOT NULL DEFAULT 0,
  `username` varchar(50) NOT NULL DEFAULT '',
  `feuserid` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`),
  KEY `event` (`userid`,`tstamp`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_lockedrecords`
--

LOCK TABLES `sys_lockedrecords` WRITE;
/*!40000 ALTER TABLE `sys_lockedrecords` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_lockedrecords` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_log`
--

DROP TABLE IF EXISTS `sys_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_log` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `userid` int(10) unsigned NOT NULL DEFAULT 0,
  `action` smallint(5) unsigned NOT NULL DEFAULT 0,
  `recuid` int(10) unsigned NOT NULL DEFAULT 0,
  `tablename` varchar(255) NOT NULL DEFAULT '',
  `recpid` int(11) NOT NULL DEFAULT 0,
  `error` smallint(5) unsigned NOT NULL DEFAULT 0,
  `details` text DEFAULT NULL,
  `type` smallint(5) unsigned NOT NULL DEFAULT 0,
  `channel` varchar(20) NOT NULL DEFAULT 'default',
  `details_nr` smallint(6) NOT NULL DEFAULT 0,
  `IP` varchar(39) NOT NULL DEFAULT '',
  `log_data` text DEFAULT NULL,
  `event_pid` int(11) NOT NULL DEFAULT -1,
  `workspace` int(11) NOT NULL DEFAULT 0,
  `NEWid` varchar(30) NOT NULL DEFAULT '',
  `request_id` varchar(13) NOT NULL DEFAULT '',
  `time_micro` double NOT NULL DEFAULT 0,
  `component` varchar(255) NOT NULL DEFAULT '',
  `level` varchar(10) NOT NULL DEFAULT 'info',
  `message` text DEFAULT NULL,
  `data` text DEFAULT NULL,
  PRIMARY KEY (`uid`),
  KEY `event` (`userid`,`event_pid`),
  KEY `recuidIdx` (`recuid`),
  KEY `user_auth` (`type`,`action`,`tstamp`),
  KEY `request` (`request_id`),
  KEY `combined_1` (`tstamp`,`type`,`userid`),
  KEY `errorcount` (`tstamp`,`error`),
  KEY `channel` (`channel`),
  KEY `level` (`level`),
  KEY `parent` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_log`
--

LOCK TABLES `sys_log` WRITE;
/*!40000 ALTER TABLE `sys_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_messenger_messages`
--

DROP TABLE IF EXISTS `sys_messenger_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_messenger_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `body` longtext NOT NULL,
  `headers` longtext NOT NULL,
  `queue_name` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL,
  `available_at` datetime NOT NULL,
  `delivered_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `queue_name` (`queue_name`),
  KEY `available_at` (`available_at`),
  KEY `delivered_at` (`delivered_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_messenger_messages`
--

LOCK TABLES `sys_messenger_messages` WRITE;
/*!40000 ALTER TABLE `sys_messenger_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_messenger_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_news`
--

DROP TABLE IF EXISTS `sys_news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_news` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `crdate` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted` smallint(5) unsigned NOT NULL DEFAULT 0,
  `hidden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `starttime` int(10) unsigned NOT NULL DEFAULT 0,
  `endtime` int(10) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `content` mediumtext DEFAULT NULL,
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`,`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_news`
--

LOCK TABLES `sys_news` WRITE;
/*!40000 ALTER TABLE `sys_news` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_note`
--

DROP TABLE IF EXISTS `sys_note`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_note` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `crdate` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted` smallint(5) unsigned NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `subject` varchar(255) NOT NULL DEFAULT '',
  `cruser` int(10) unsigned NOT NULL DEFAULT 0,
  `message` text DEFAULT NULL,
  `personal` smallint(5) unsigned NOT NULL DEFAULT 0,
  `category` smallint(5) unsigned NOT NULL DEFAULT 0,
  `position` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_note`
--

LOCK TABLES `sys_note` WRITE;
/*!40000 ALTER TABLE `sys_note` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_note` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_reaction`
--

DROP TABLE IF EXISTS `sys_reaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_reaction` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `updatedon` int(10) unsigned NOT NULL DEFAULT 0,
  `createdon` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted` smallint(5) unsigned NOT NULL DEFAULT 0,
  `disabled` smallint(5) unsigned NOT NULL DEFAULT 0,
  `starttime` int(10) unsigned NOT NULL DEFAULT 0,
  `endtime` int(10) unsigned NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `name` varchar(100) NOT NULL DEFAULT '',
  `reaction_type` varchar(255) NOT NULL DEFAULT '',
  `secret` varchar(255) NOT NULL DEFAULT '',
  `impersonate_user` int(10) unsigned NOT NULL DEFAULT 0,
  `table_name` varchar(255) NOT NULL DEFAULT '',
  `storage_pid` int(10) unsigned NOT NULL DEFAULT 0,
  `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`fields`)),
  `identifier` varchar(36) NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `identifier_key` (`identifier`),
  KEY `index_source` (`reaction_type`(5)),
  KEY `parent` (`pid`,`deleted`,`disabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_reaction`
--

LOCK TABLES `sys_reaction` WRITE;
/*!40000 ALTER TABLE `sys_reaction` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_reaction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_refindex`
--

DROP TABLE IF EXISTS `sys_refindex`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_refindex` (
  `hash` varchar(32) NOT NULL DEFAULT '',
  `tablename` varchar(255) NOT NULL DEFAULT '',
  `recuid` int(11) NOT NULL DEFAULT 0,
  `field` varchar(64) NOT NULL DEFAULT '',
  `flexpointer` varchar(255) NOT NULL DEFAULT '',
  `softref_key` varchar(30) NOT NULL DEFAULT '',
  `softref_id` varchar(40) NOT NULL DEFAULT '',
  `sorting` int(11) NOT NULL DEFAULT 0,
  `workspace` int(11) NOT NULL DEFAULT 0,
  `ref_table` varchar(255) NOT NULL DEFAULT '',
  `ref_uid` int(11) NOT NULL DEFAULT 0,
  `ref_string` varchar(1024) NOT NULL DEFAULT '',
  PRIMARY KEY (`hash`),
  KEY `lookup_rec` (`tablename`(100),`recuid`),
  KEY `lookup_uid` (`ref_table`(100),`ref_uid`),
  KEY `lookup_string` (`ref_string`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_refindex`
--

LOCK TABLES `sys_refindex` WRITE;
/*!40000 ALTER TABLE `sys_refindex` DISABLE KEYS */;
INSERT INTO `sys_refindex` VALUES
('ee6d11674a9e5b2a08c9f50ec1840ddf','sys_template',2,'config','','url','0',-1,0,'_STRING',0,'https://www.dla-marbach.de/cgi-bin/aDISCGI/kallias_prod/lib/ng-ausleihe.html?test=test');
/*!40000 ALTER TABLE `sys_refindex` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_registry`
--

DROP TABLE IF EXISTS `sys_registry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_registry` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_namespace` varchar(128) NOT NULL DEFAULT '',
  `entry_key` varchar(128) NOT NULL DEFAULT '',
  `entry_value` mediumblob DEFAULT NULL,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `entry_identifier` (`entry_namespace`,`entry_key`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_registry`
--

LOCK TABLES `sys_registry` WRITE;
/*!40000 ALTER TABLE `sys_registry` DISABLE KEYS */;
INSERT INTO `sys_registry` VALUES
(1,'installUpdate','TYPO3\\CMS\\Install\\Updates\\BackendGroupsExplicitAllowDenyMigration','i:1;'),
(2,'installUpdate','TYPO3\\CMS\\Install\\Updates\\BackendModulePermissionMigration','i:1;'),
(3,'installUpdate','TYPO3\\CMS\\Install\\Updates\\BackendUserLanguageMigration','i:1;'),
(4,'installUpdate','TYPO3\\CMS\\Install\\Updates\\FeLoginModeExtractionUpdate','i:1;'),
(5,'installUpdate','TYPO3\\CMS\\Install\\Updates\\CollectionsExtractionUpdate','i:1;'),
(6,'installUpdate','TYPO3\\CMS\\Install\\Updates\\MigrateSiteSettingsConfigUpdate','i:1;'),
(7,'installUpdate','TYPO3\\CMS\\Install\\Updates\\PasswordPolicyForFrontendUsersUpdate','i:1;'),
(8,'installUpdate','TYPO3\\CMS\\Install\\Updates\\ShortcutRecordsMigration','i:1;'),
(9,'installUpdate','TYPO3\\CMS\\Install\\Updates\\SvgFilesSanitization','i:1;'),
(10,'installUpdate','TYPO3\\CMS\\Install\\Updates\\SysFileCollectionIdentifierMigration','i:1;'),
(11,'installUpdate','TYPO3\\CMS\\Install\\Updates\\SysFileMountIdentifierMigration','i:1;'),
(12,'installUpdate','TYPO3\\CMS\\Install\\Updates\\SysLogChannel','i:1;'),
(13,'installUpdate','TYPO3\\CMS\\Install\\Updates\\SysLogSerializationUpdate','i:1;'),
(14,'installUpdate','TYPO3\\CMS\\Install\\Updates\\SysTemplateNoWorkspaceMigration','i:1;'),
(15,'installUpdateRows','rowUpdatersDone','a:4:{i:0;s:66:\"TYPO3\\CMS\\Install\\Updates\\RowUpdater\\L18nDiffsourceToJsonMigration\";i:1;s:77:\"TYPO3\\CMS\\Install\\Updates\\RowUpdater\\WorkspaceMovePlaceholderRemovalMigration\";i:2;s:76:\"TYPO3\\CMS\\Install\\Updates\\RowUpdater\\WorkspaceNewPlaceholderRemovalMigration\";i:3;s:69:\"TYPO3\\CMS\\Install\\Updates\\RowUpdater\\SysRedirectRootPageMoveMigration\";}'),
(16,'core','formProtectionSessionToken:1','s:64:\"8ceac2a2978d8d6c63c640cbb88585d4e00487c417a58078e67a2b3447a48909\";');
/*!40000 ALTER TABLE `sys_registry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_template`
--

DROP TABLE IF EXISTS `sys_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_template` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `crdate` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted` smallint(5) unsigned NOT NULL DEFAULT 0,
  `hidden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `starttime` int(10) unsigned NOT NULL DEFAULT 0,
  `endtime` int(10) unsigned NOT NULL DEFAULT 0,
  `sorting` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `t3_origuid` int(10) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `root` smallint(5) unsigned NOT NULL DEFAULT 0,
  `clear` smallint(5) unsigned NOT NULL DEFAULT 0,
  `include_static_file` text DEFAULT NULL,
  `constants` text DEFAULT NULL,
  `config` text DEFAULT NULL,
  `basedOn` tinytext DEFAULT NULL,
  `includeStaticAfterBasedOn` smallint(5) unsigned NOT NULL DEFAULT 0,
  `static_file_mode` smallint(5) unsigned NOT NULL DEFAULT 0,
  `tx_impexp_origuid` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`),
  KEY `roottemplate` (`deleted`,`hidden`,`root`),
  KEY `parent` (`pid`,`deleted`,`hidden`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_template`
--

LOCK TABLES `sys_template` WRITE;
/*!40000 ALTER TABLE `sys_template` DISABLE KEYS */;
INSERT INTO `sys_template` VALUES
(1,1,1775859335,1775859242,0,0,0,0,256,NULL,0,'NEW SITE',1,3,'EXT:fluid_styled_content/Configuration/TypoScript/,EXT:fluid_styled_content/Configuration/TypoScript/Styling/,EXT:find/Configuration/TypoScript,EXT:dla_opac_ng/Configuration/TypoScript',NULL,'    page.5 > // Flux-Renderer überschreiben, sonst Ausgabe doppelt\r\n    page = PAGE\r\n    page {\r\n      typeNum = 0\r\n      includeCSS.site = EXT:dla_opac_ng/Resources/Public/CSS/site.min.css\r\n      10 = FLUIDTEMPLATE\r\n      10 {\r\n        file = EXT:dla_opac_ng/Resources/Private/Templates/Page/Start.html\r\n        section = Main\r\n        layoutRootPaths.10 = EXT:dla_opac_ng/Resources/Private/Layouts/\r\n        partialRootPaths.10 = EXT:dla_opac_ng/Resources/Private/Partials/\r\n      }\r\n    }','',0,0,0),
(2,2,1775859757,1775859728,0,0,0,0,256,NULL,0,'+ext',0,0,'EXT:fluid_styled_content/Configuration/TypoScript/,EXT:fluid_styled_content/Configuration/TypoScript/Styling/,EXT:find/Configuration/TypoScript,EXT:dla_opac_ng/Configuration/TypoScript',NULL,'    plugin.tx_find.settings.mainPageUid = 2\r\n    plugin.tx_find.settings.indexPageUid = 3\r\n    plugin.tx_find.settings.collectionUid = 4\r\n    plugin.tx_find.settings.helpUid = 1\r\n    plugin.tx_find.settings.orderlink = https://www.dla-marbach.de/cgi-bin/aDISCGI/kallias_prod/lib/ng-ausleihe.html?test=test\r\n\r\n    # Browser-Zurück ermöglichen\r\n    config.additionalHeaders {\r\n        20.header = Cache-control: private, must-revalidate\r\n    }','',0,0,0),
(3,3,1775859781,1775859769,0,0,0,0,256,NULL,0,'+ext',0,0,NULL,NULL,'    # Trefferliste noindex,nofollow; Detailseite nur nofollow\r\n    page.meta.robots = noindex, nofollow\r\n    page.meta.robots.replace = 1\r\n    [traverse(request.getQueryParams(), \'tx_find_find/action\') == \'detail\']\r\n      page.meta.robots = nofollow\r\n      page.meta.robots.replace = 1\r\n    [END]\r\n\r\n    # Links rel=\"canonical\" und rel=\"alternate\" deaktivieren\r\n    config.disableCanonical = 1\r\n    config.disableHrefLang = 1','',0,0,0);
/*!40000 ALTER TABLE `sys_template` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_webhook`
--

DROP TABLE IF EXISTS `sys_webhook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_webhook` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `updatedon` int(10) unsigned NOT NULL DEFAULT 0,
  `createdon` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted` smallint(5) unsigned NOT NULL DEFAULT 0,
  `disabled` smallint(5) unsigned NOT NULL DEFAULT 0,
  `starttime` int(10) unsigned NOT NULL DEFAULT 0,
  `endtime` int(10) unsigned NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `name` varchar(100) NOT NULL DEFAULT '',
  `url` varchar(2048) NOT NULL DEFAULT '',
  `method` varchar(10) NOT NULL DEFAULT '',
  `secret` varchar(255) NOT NULL DEFAULT '',
  `webhook_type` varchar(255) NOT NULL DEFAULT '',
  `verify_ssl` int(11) NOT NULL DEFAULT 1,
  `additional_headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`additional_headers`)),
  `identifier` varchar(36) NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `identifier_key` (`identifier`),
  KEY `index_source` (`webhook_type`(5)),
  KEY `parent` (`pid`,`deleted`,`disabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_webhook`
--

LOCK TABLES `sys_webhook` WRITE;
/*!40000 ALTER TABLE `sys_webhook` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_webhook` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tt_content`
--

DROP TABLE IF EXISTS `tt_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tt_content` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rowDescription` text DEFAULT NULL,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `crdate` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted` smallint(5) unsigned NOT NULL DEFAULT 0,
  `hidden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `starttime` int(10) unsigned NOT NULL DEFAULT 0,
  `endtime` int(10) unsigned NOT NULL DEFAULT 0,
  `fe_group` varchar(255) NOT NULL DEFAULT '0',
  `sorting` int(11) NOT NULL DEFAULT 0,
  `editlock` smallint(5) unsigned NOT NULL DEFAULT 0,
  `sys_language_uid` int(11) NOT NULL DEFAULT 0,
  `l18n_parent` int(10) unsigned NOT NULL DEFAULT 0,
  `l10n_source` int(10) unsigned NOT NULL DEFAULT 0,
  `l10n_state` text DEFAULT NULL,
  `t3_origuid` int(10) unsigned NOT NULL DEFAULT 0,
  `l18n_diffsource` mediumblob DEFAULT NULL,
  `t3ver_oid` int(10) unsigned NOT NULL DEFAULT 0,
  `t3ver_wsid` int(10) unsigned NOT NULL DEFAULT 0,
  `t3ver_state` smallint(6) NOT NULL DEFAULT 0,
  `t3ver_stage` int(11) NOT NULL DEFAULT 0,
  `CType` varchar(255) NOT NULL DEFAULT '',
  `header` varchar(255) NOT NULL DEFAULT '',
  `header_position` varchar(255) NOT NULL DEFAULT '',
  `bodytext` mediumtext DEFAULT NULL,
  `bullets_type` smallint(5) unsigned NOT NULL DEFAULT 0,
  `uploads_description` smallint(5) unsigned NOT NULL DEFAULT 0,
  `uploads_type` smallint(5) unsigned NOT NULL DEFAULT 0,
  `assets` int(10) unsigned NOT NULL DEFAULT 0,
  `image` int(10) unsigned NOT NULL DEFAULT 0,
  `imagewidth` int(10) unsigned NOT NULL DEFAULT 0,
  `imageorient` smallint(5) unsigned NOT NULL DEFAULT 0,
  `imagecols` smallint(5) unsigned NOT NULL DEFAULT 0,
  `imageborder` smallint(5) unsigned NOT NULL DEFAULT 0,
  `media` int(10) unsigned NOT NULL DEFAULT 0,
  `layout` int(10) unsigned NOT NULL DEFAULT 0,
  `frame_class` varchar(60) NOT NULL DEFAULT 'default',
  `cols` int(10) unsigned NOT NULL DEFAULT 0,
  `space_before_class` varchar(60) NOT NULL DEFAULT '',
  `space_after_class` varchar(60) NOT NULL DEFAULT '',
  `records` text DEFAULT NULL,
  `pages` text DEFAULT NULL,
  `colPos` bigint(20) NOT NULL DEFAULT 0,
  `subheader` varchar(255) NOT NULL DEFAULT '',
  `header_link` varchar(1024) NOT NULL DEFAULT '',
  `image_zoom` smallint(5) unsigned NOT NULL DEFAULT 0,
  `header_layout` varchar(30) NOT NULL DEFAULT '0',
  `list_type` varchar(255) NOT NULL DEFAULT '',
  `sectionIndex` smallint(5) unsigned NOT NULL DEFAULT 0,
  `linkToTop` smallint(5) unsigned NOT NULL DEFAULT 0,
  `file_collections` text DEFAULT NULL,
  `filelink_size` smallint(5) unsigned NOT NULL DEFAULT 0,
  `filelink_sorting` varchar(64) NOT NULL DEFAULT '',
  `filelink_sorting_direction` varchar(4) NOT NULL DEFAULT '',
  `target` varchar(30) NOT NULL DEFAULT '',
  `recursive` smallint(5) unsigned NOT NULL DEFAULT 0,
  `imageheight` int(10) unsigned NOT NULL DEFAULT 0,
  `pi_flexform` mediumtext DEFAULT NULL,
  `accessibility_title` varchar(30) NOT NULL DEFAULT '',
  `accessibility_bypass` smallint(5) unsigned NOT NULL DEFAULT 0,
  `accessibility_bypass_text` varchar(30) NOT NULL DEFAULT '',
  `category_field` varchar(64) NOT NULL DEFAULT '',
  `table_class` varchar(60) NOT NULL DEFAULT '',
  `table_caption` varchar(255) DEFAULT NULL,
  `table_delimiter` smallint(5) unsigned NOT NULL DEFAULT 0,
  `table_enclosure` smallint(5) unsigned NOT NULL DEFAULT 0,
  `table_header_position` smallint(5) unsigned NOT NULL DEFAULT 0,
  `table_tfoot` smallint(5) unsigned NOT NULL DEFAULT 0,
  `categories` int(10) unsigned NOT NULL DEFAULT 0,
  `selected_categories` longtext DEFAULT NULL,
  `date` int(11) NOT NULL DEFAULT 0,
  `tx_impexp_origuid` int(11) NOT NULL DEFAULT 0,
  `tx_flux_migrated_version` varchar(11) DEFAULT NULL,
  PRIMARY KEY (`uid`),
  KEY `parent` (`pid`,`sorting`),
  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`),
  KEY `language` (`l18n_parent`,`sys_language_uid`),
  KEY `translation_source` (`l10n_source`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tt_content`
--

LOCK TABLES `tt_content` WRITE;
/*!40000 ALTER TABLE `tt_content` DISABLE KEYS */;
INSERT INTO `tt_content` VALUES
(1,'',1,1775859144,1775859144,0,0,0,0,'',256,0,0,0,0,NULL,0,'',0,0,0,0,'menu_sitemap','','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,NULL,0,'','',0,'0','',1,0,NULL,0,'','','',0,0,NULL,'',0,'','','',NULL,124,0,0,0,0,NULL,0,0,NULL),
(2,'',2,1775859542,1775859541,0,0,0,0,'',256,0,0,0,0,NULL,0,'{\"CType\":\"\",\"colPos\":\"\",\"header\":\"\",\"header_layout\":\"\",\"header_position\":\"\",\"date\":\"\",\"header_link\":\"\",\"subheader\":\"\",\"list_type\":\"\",\"pages\":\"\",\"recursive\":\"\",\"layout\":\"\",\"frame_class\":\"\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"sectionIndex\":\"\",\"linkToTop\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,'list','','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,'',0,'','',0,'0','dlaopacng_dlastart',1,0,NULL,0,'','','',0,0,NULL,'',0,'','','',NULL,124,0,0,0,0,NULL,0,0,NULL),
(3,'',3,1775859572,1775859572,0,0,0,0,'',256,0,0,0,0,NULL,0,'',0,0,0,0,'list','','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,'',0,'','',0,'0','find_find',1,0,NULL,0,'','','',0,0,NULL,'',0,'','','',NULL,124,0,0,0,0,NULL,0,0,NULL),
(4,'',4,1775859616,1775859615,0,0,0,0,'',256,0,0,0,0,NULL,0,'{\"CType\":\"\",\"colPos\":\"\",\"header\":\"\",\"header_layout\":\"\",\"header_position\":\"\",\"date\":\"\",\"header_link\":\"\",\"subheader\":\"\",\"list_type\":\"\",\"pages\":\"\",\"recursive\":\"\",\"layout\":\"\",\"frame_class\":\"\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"sectionIndex\":\"\",\"linkToTop\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,'list','','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,'',0,'','',0,'0','dlaopacng_dlacollection',1,0,NULL,0,'','','',0,0,NULL,'',0,'','','',NULL,124,0,0,0,0,NULL,0,0,NULL),
(5,'',5,1775859650,1775859647,0,0,0,0,'',256,0,0,0,0,NULL,0,'{\"CType\":\"\",\"colPos\":\"\",\"header\":\"\",\"header_layout\":\"\",\"header_position\":\"\",\"date\":\"\",\"header_link\":\"\",\"subheader\":\"\",\"list_type\":\"\",\"pages\":\"\",\"recursive\":\"\",\"layout\":\"\",\"frame_class\":\"\",\"space_before_class\":\"\",\"space_after_class\":\"\",\"sectionIndex\":\"\",\"linkToTop\":\"\",\"sys_language_uid\":\"\",\"hidden\":\"\",\"starttime\":\"\",\"endtime\":\"\",\"fe_group\":\"\",\"editlock\":\"\",\"categories\":\"\",\"rowDescription\":\"\"}',0,0,0,0,'list','','',NULL,0,0,0,0,0,0,0,2,0,0,0,'default',0,'','',NULL,'',0,'','',0,'0','dlaopacng_dlaclassification',1,0,NULL,0,'','','',0,0,NULL,'',0,'','','',NULL,124,0,0,0,0,NULL,0,0,NULL);
/*!40000 ALTER TABLE `tt_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tx_dlaopacng_classification`
--

DROP TABLE IF EXISTS `tx_dlaopacng_classification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_dlaopacng_classification` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL DEFAULT 0,
  `record_id` varchar(100) NOT NULL DEFAULT '',
  `parent_id` varchar(100) NOT NULL DEFAULT '',
  `displayTree` text NOT NULL,
  `display` text NOT NULL,
  `displayName` text NOT NULL,
  `displayAddition1` text NOT NULL,
  `displayAddition2` text NOT NULL,
  `facet_value` text NOT NULL,
  `hasChild` smallint(6) NOT NULL DEFAULT 0,
  `count` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `parent` (`parent_id`),
  KEY `record_id` (`record_id`),
  FULLTEXT KEY `search` (`displayTree`,`display`,`displayName`,`displayAddition1`,`displayAddition2`)
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tx_dlaopacng_classification`
--

LOCK TABLES `tx_dlaopacng_classification` WRITE;
/*!40000 ALTER TABLE `tx_dlaopacng_classification` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_dlaopacng_classification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tx_dlaopacng_collection`
--

DROP TABLE IF EXISTS `tx_dlaopacng_collection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_dlaopacng_collection` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL DEFAULT 0,
  `record_id` varchar(100) NOT NULL DEFAULT '',
  `parent_id` varchar(100) NOT NULL DEFAULT '',
  `displayTree` text NOT NULL,
  `display` text NOT NULL,
  `displayName` text NOT NULL,
  `displayAddition1` text NOT NULL,
  `displayAddition2` text NOT NULL,
  `facet_value` text NOT NULL,
  `hasChild` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`),
  KEY `parent` (`parent_id`),
  KEY `record_id` (`record_id`),
  FULLTEXT KEY `search` (`displayTree`,`display`,`displayName`,`displayAddition1`,`displayAddition2`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tx_dlaopacng_collection`
--

LOCK TABLES `tx_dlaopacng_collection` WRITE;
/*!40000 ALTER TABLE `tx_dlaopacng_collection` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_dlaopacng_collection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tx_extensionmanager_domain_model_extension`
--

DROP TABLE IF EXISTS `tx_extensionmanager_domain_model_extension`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_extensionmanager_domain_model_extension` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `extension_key` varchar(60) NOT NULL DEFAULT '',
  `repository` int(11) NOT NULL DEFAULT 1,
  `remote` varchar(100) NOT NULL DEFAULT 'ter',
  `version` varchar(15) NOT NULL DEFAULT '',
  `alldownloadcounter` int(10) unsigned NOT NULL DEFAULT 0,
  `downloadcounter` int(10) unsigned NOT NULL DEFAULT 0,
  `title` varchar(150) NOT NULL DEFAULT '',
  `description` mediumtext DEFAULT NULL,
  `state` int(11) NOT NULL DEFAULT 0,
  `review_state` int(11) NOT NULL DEFAULT 0,
  `category` int(11) NOT NULL DEFAULT 0,
  `serialized_dependencies` mediumtext DEFAULT NULL,
  `author_name` varchar(255) NOT NULL DEFAULT '',
  `author_email` varchar(255) NOT NULL DEFAULT '',
  `ownerusername` varchar(50) NOT NULL DEFAULT '',
  `md5hash` varchar(35) NOT NULL DEFAULT '',
  `update_comment` mediumtext DEFAULT NULL,
  `authorcompany` varchar(255) NOT NULL DEFAULT '',
  `integer_version` int(11) NOT NULL DEFAULT 0,
  `current_version` int(11) NOT NULL DEFAULT 0,
  `lastreviewedversion` int(11) NOT NULL DEFAULT 0,
  `documentation_link` varchar(2048) DEFAULT NULL,
  `distribution_image` varchar(255) DEFAULT NULL,
  `distribution_welcome_image` varchar(255) DEFAULT NULL,
  `last_updated` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `versionextrepo` (`extension_key`,`version`,`remote`),
  KEY `index_extrepo` (`extension_key`,`remote`),
  KEY `index_versionrepo` (`integer_version`,`remote`,`extension_key`),
  KEY `index_currentversions` (`current_version`,`review_state`),
  KEY `parent` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tx_extensionmanager_domain_model_extension`
--

LOCK TABLES `tx_extensionmanager_domain_model_extension` WRITE;
/*!40000 ALTER TABLE `tx_extensionmanager_domain_model_extension` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_extensionmanager_domain_model_extension` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tx_impexp_presets`
--

DROP TABLE IF EXISTS `tx_impexp_presets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tx_impexp_presets` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0,
  `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
  `crdate` int(10) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `public` smallint(6) NOT NULL DEFAULT 0,
  `item_uid` int(11) NOT NULL DEFAULT 0,
  `user_uid` int(10) unsigned NOT NULL DEFAULT 0,
  `preset_data` blob DEFAULT NULL,
  PRIMARY KEY (`uid`),
  KEY `lookup` (`item_uid`),
  KEY `parent` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tx_impexp_presets`
--

LOCK TABLES `tx_impexp_presets` WRITE;
/*!40000 ALTER TABLE `tx_impexp_presets` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_impexp_presets` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-15  9:05:50

-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: mini_erp_tenant_990103
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
-- Table structure for table `cfops`
--

DROP TABLE IF EXISTS `cfops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cfops` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `natureza` varchar(80) DEFAULT '',
  `aplicacao` varchar(80) DEFAULT '',
  `status` varchar(20) DEFAULT 'ativo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cfops`
--

LOCK TABLES `cfops` WRITE;
/*!40000 ALTER TABLE `cfops` DISABLE KEYS */;
/*!40000 ALTER TABLE `cfops` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefone` varchar(50) DEFAULT '',
  `cpf_cnpj` varchar(20) DEFAULT '',
  `inscricao_estadual` varchar(50) DEFAULT '',
  `logradouro` varchar(150) DEFAULT '',
  `numero` varchar(20) DEFAULT '',
  `complemento` varchar(100) DEFAULT '',
  `bairro` varchar(100) DEFAULT '',
  `municipio` varchar(100) DEFAULT '',
  `codigo_municipal` varchar(20) DEFAULT '',
  `uf` varchar(2) DEFAULT '',
  `cep` varchar(20) DEFAULT '',
  `cidade` varchar(100) DEFAULT '',
  `nome_fantasia` varchar(150) DEFAULT '',
  `tipo_pessoa` varchar(100) DEFAULT 'cliente',
  `pessoa_fisica` varchar(10) DEFAULT 'sim',
  `aniversario` date DEFAULT NULL,
  `genero` varchar(30) DEFAULT '',
  `data_cadastro` date DEFAULT NULL,
  `nome_contato` varchar(150) DEFAULT '',
  `fone_principal` varchar(50) DEFAULT '',
  `fone_2` varchar(50) DEFAULT '',
  `fone_3` varchar(50) DEFAULT '',
  `ponto_referencia` varchar(150) DEFAULT '',
  `codigo_ibge` varchar(7) DEFAULT '',
  `suprama` varchar(50) DEFAULT '',
  `im` varchar(50) DEFAULT '',
  `person_type` varchar(7) NOT NULL DEFAULT 'PF',
  `foreign_id` varchar(50) NOT NULL DEFAULT '',
  `state_registration_indicator` char(1) NOT NULL DEFAULT '9',
  `rg` varchar(30) NOT NULL DEFAULT '',
  `country_code` varchar(4) NOT NULL DEFAULT '1058',
  `country_name` varchar(60) NOT NULL DEFAULT 'BRASIL',
  `observations` text DEFAULT NULL,
  `role_customer` tinyint(1) NOT NULL DEFAULT 1,
  `role_supplier` tinyint(1) NOT NULL DEFAULT 0,
  `role_seller` tinyint(1) NOT NULL DEFAULT 0,
  `role_carrier` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) DEFAULT 'ativo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `establishments`
--

DROP TABLE IF EXISTS `establishments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `establishments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(10) unsigned NOT NULL,
  `tax_id` varchar(14) NOT NULL,
  `legal_name` varchar(150) NOT NULL,
  `trade_name` varchar(150) NOT NULL DEFAULT '',
  `state_registration` varchar(30) NOT NULL,
  `st_registration` varchar(30) NOT NULL DEFAULT '',
  `municipal_registration` varchar(30) NOT NULL DEFAULT '',
  `cnae` varchar(7) NOT NULL DEFAULT '',
  `tax_regime_code` char(1) NOT NULL,
  `street` varchar(150) NOT NULL,
  `number` varchar(20) NOT NULL,
  `complement` varchar(100) NOT NULL DEFAULT '',
  `district` varchar(100) NOT NULL,
  `city_ibge_code` char(7) NOT NULL,
  `city_name` varchar(100) NOT NULL,
  `state` char(2) NOT NULL,
  `postal_code` char(8) NOT NULL,
  `country_code` varchar(4) NOT NULL DEFAULT '1058',
  `country_name` varchar(60) NOT NULL DEFAULT 'BRASIL',
  `phone` varchar(30) NOT NULL DEFAULT '',
  `email` varchar(150) NOT NULL DEFAULT '',
  `establishment_type` varchar(20) NOT NULL DEFAULT 'MATRIZ',
  `is_primary` tinyint(1) NOT NULL DEFAULT 1,
  `status` varchar(10) NOT NULL DEFAULT 'ativo',
  `fiscal_readiness` varchar(20) NOT NULL DEFAULT 'INCOMPLETE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_establishments_tenant_tax_id` (`tenant_id`,`tax_id`),
  KEY `idx_establishments_primary` (`tenant_id`,`is_primary`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `establishments`
--

LOCK TABLES `establishments` WRITE;
/*!40000 ALTER TABLE `establishments` DISABLE KEYS */;
INSERT INTO `establishments` VALUES (1,990103,'07924387000200','INFOCASE INFORMATICA  FILIAL LTDA FILIAL SIMPLES','INFOCASE INFORMATICA  FILIAL LTDA FILIAL SIMPLES','9084712024','','01048731950','4751201','1','RODOVIA BR CENTO E DEZESSEIS','22881','LOJA 989','TATUQUARA','4106902','CURITIBA','PR','81690500','1058','BRASIL','4130335070','','MATRIZ',1,'ativo','INCOMPLETE','2026-08-24 13:38:01','2026-08-24 13:38:01');
/*!40000 ALTER TABLE `establishments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fiscal_artifacts`
--

DROP TABLE IF EXISTS `fiscal_artifacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fiscal_artifacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `establishment_id` bigint(20) unsigned NOT NULL,
  `fiscal_document_id` bigint(20) unsigned NOT NULL,
  `fiscal_document_version` int(10) unsigned NOT NULL DEFAULT 1,
  `certificate_id` bigint(20) unsigned DEFAULT NULL,
  `number_reservation_id` bigint(20) unsigned DEFAULT NULL,
  `model` char(2) NOT NULL,
  `environment` tinyint(3) unsigned NOT NULL DEFAULT 2,
  `series` int(10) unsigned NOT NULL,
  `number` bigint(20) unsigned NOT NULL,
  `access_key` varchar(64) NOT NULL,
  `artifact_type` varchar(40) NOT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'XSD_VALID_OFFLINE',
  `schema_package` varchar(80) NOT NULL DEFAULT 'nfe',
  `schema_version` varchar(32) NOT NULL DEFAULT '010e-v1.02',
  `schema_checksum` char(64) NOT NULL DEFAULT '',
  `storage_reference` varchar(500) NOT NULL,
  `sha256` char(64) NOT NULL,
  `size_bytes` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_artifact_document_version` (`tenant_id`,`fiscal_document_id`,`fiscal_document_version`,`artifact_type`),
  KEY `idx_artifact_document` (`tenant_id`,`fiscal_document_id`),
  KEY `idx_artifact_access_key` (`tenant_id`,`access_key`),
  KEY `idx_artifact_reservation` (`number_reservation_id`),
  KEY `idx_artifact_certificate` (`certificate_id`),
  KEY `idx_artifact_status` (`tenant_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fiscal_artifacts`
--

LOCK TABLES `fiscal_artifacts` WRITE;
/*!40000 ALTER TABLE `fiscal_artifacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `fiscal_artifacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fiscal_certificate_audit`
--

DROP TABLE IF EXISTS `fiscal_certificate_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fiscal_certificate_audit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `establishment_id` bigint(20) unsigned NOT NULL,
  `certificate_id` bigint(20) unsigned NOT NULL,
  `action` varchar(32) NOT NULL,
  `actor_id` bigint(20) unsigned NOT NULL,
  `details_json` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_certificate_audit_scope` (`tenant_id`,`establishment_id`,`certificate_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fiscal_certificate_audit`
--

LOCK TABLES `fiscal_certificate_audit` WRITE;
/*!40000 ALTER TABLE `fiscal_certificate_audit` DISABLE KEYS */;
INSERT INTO `fiscal_certificate_audit` VALUES (1,990103,1,1,'UPLOAD',9,'{\"sha256\":\"4c75a014330414db15989c3b5faca057d68a393558cd5c07a3c576399f9df5bb\",\"identity_status\":\"MISMATCH\",\"identity_source\":\"ICP_BRASIL_SAN_OTHERNAME_OID_2.16.76.1.3.3\"}','2026-08-24 15:06:35');
/*!40000 ALTER TABLE `fiscal_certificate_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fiscal_certificates`
--

DROP TABLE IF EXISTS `fiscal_certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fiscal_certificates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `establishment_id` bigint(20) unsigned NOT NULL,
  `storage_reference` varchar(500) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `sha256` char(64) NOT NULL,
  `fingerprint_sha256` char(64) NOT NULL,
  `subject` text NOT NULL,
  `issuer` text NOT NULL,
  `serial_number` varchar(255) NOT NULL,
  `tax_id` varchar(32) DEFAULT NULL,
  `valid_from` datetime NOT NULL,
  `valid_until` datetime NOT NULL,
  `status` varchar(32) NOT NULL,
  `secret_reference` varchar(500) NOT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `deactivated_by` bigint(20) unsigned DEFAULT NULL,
  `deactivated_at` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_active_certificate` (`tenant_id`,`establishment_id`,`active`),
  KEY `ix_certificate_scope` (`tenant_id`,`establishment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fiscal_certificates`
--

LOCK TABLES `fiscal_certificates` WRITE;
/*!40000 ALTER TABLE `fiscal_certificates` DISABLE KEYS */;
INSERT INTO `fiscal_certificates` VALUES (1,990103,1,'tenant-990103/establishment-1/413c4379199b9b81e0f7637247c108ea.pfx','1775766590_cca7a7d47b22c03f0e82.pfx','4c75a014330414db15989c3b5faca057d68a393558cd5c07a3c576399f9df5bb','3cae5966616c2ebd082e73bc11bedeb21fd4a114b1907d312201bf1b0bdd31cf','countryName=BR, organizationName=ICP-Brasil, organizationalUnitName=Certificado Digital PJ A1+Videoconferencia+32522131000125+AC SyngularID Multipla, commonName=NOVA INFOCASE INFORMATICA LTDA:07293395000107','countryName=BR, organizationalUnitName=AC SyngularID, organizationName=ICP-Brasil, commonName=AC SyngularID Multipla','55E2AC467B7650F59068','07293395000107','2025-02-20 08:43:13','2026-02-20 08:43:13','INVALID_IDENTITY','tenant-990103/establishment-1/fdfcc1db85bf811a5487b9de4d4d5e1f.secret',9,NULL,NULL,0,'2026-08-24 15:06:35','2026-08-24 15:06:35');
/*!40000 ALTER TABLE `fiscal_certificates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fiscal_classifications`
--

DROP TABLE IF EXISTS `fiscal_classifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fiscal_classifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference_version_id` bigint(20) unsigned NOT NULL,
  `classification_type` varchar(30) NOT NULL,
  `code` varchar(30) NOT NULL,
  `description` varchar(500) NOT NULL,
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`metadata_json`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fiscal_classification` (`reference_version_id`,`classification_type`,`code`),
  CONSTRAINT `fk_fiscal_classification_version` FOREIGN KEY (`reference_version_id`) REFERENCES `fiscal_reference_versions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fiscal_classifications`
--

LOCK TABLES `fiscal_classifications` WRITE;
/*!40000 ALTER TABLE `fiscal_classifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `fiscal_classifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fiscal_document_items`
--

DROP TABLE IF EXISTS `fiscal_document_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fiscal_document_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fiscal_document_id` bigint(20) unsigned NOT NULL,
  `source_order_item_id` bigint(20) unsigned NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_snapshot_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`product_snapshot_json`)),
  `quantity_commercial` decimal(18,4) NOT NULL,
  `quantity_taxable` decimal(18,4) NOT NULL,
  `unit_value_commercial` decimal(18,4) NOT NULL,
  `unit_value_taxable` decimal(18,4) NOT NULL,
  `gross_total` decimal(18,2) NOT NULL,
  `discount_amount` decimal(18,2) NOT NULL,
  `freight_amount` decimal(18,2) NOT NULL,
  `insurance_amount` decimal(18,2) NOT NULL,
  `other_amount` decimal(18,2) NOT NULL,
  `net_total` decimal(18,2) NOT NULL,
  `included_in_total` tinyint(1) NOT NULL DEFAULT 1,
  `fiscal_status` varchar(20) NOT NULL,
  `tax_context_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`tax_context_json`)),
  `tax_resolution_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tax_resolution_json`)),
  PRIMARY KEY (`id`),
  KEY `fk_fiscal_document_item_document` (`fiscal_document_id`),
  CONSTRAINT `fk_fiscal_document_item_document` FOREIGN KEY (`fiscal_document_id`) REFERENCES `fiscal_documents` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fiscal_document_items`
--

LOCK TABLES `fiscal_document_items` WRITE;
/*!40000 ALTER TABLE `fiscal_document_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `fiscal_document_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fiscal_documents`
--

DROP TABLE IF EXISTS `fiscal_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fiscal_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(10) unsigned NOT NULL,
  `source_order_id` bigint(20) unsigned NOT NULL,
  `document_version` int(10) unsigned NOT NULL DEFAULT 1,
  `idempotency_key` char(64) NOT NULL,
  `status` varchar(20) NOT NULL,
  `pending_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`pending_json`)),
  `issuer_snapshot_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`issuer_snapshot_json`)),
  `recipient_snapshot_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`recipient_snapshot_json`)),
  `payment_snapshot_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payment_snapshot_json`)),
  `transport_snapshot_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`transport_snapshot_json`)),
  `totals_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`totals_json`)),
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fiscal_document_idempotency` (`tenant_id`,`idempotency_key`),
  UNIQUE KEY `uq_fiscal_document_version` (`tenant_id`,`source_order_id`,`document_version`),
  KEY `fk_fiscal_document_order` (`source_order_id`),
  CONSTRAINT `fk_fiscal_document_order` FOREIGN KEY (`source_order_id`) REFERENCES `fiscal_orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fiscal_documents`
--

LOCK TABLES `fiscal_documents` WRITE;
/*!40000 ALTER TABLE `fiscal_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `fiscal_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fiscal_mirrors`
--

DROP TABLE IF EXISTS `fiscal_mirrors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fiscal_mirrors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(10) unsigned NOT NULL,
  `source_order_id` bigint(20) unsigned NOT NULL,
  `snapshot_version` int(10) unsigned NOT NULL,
  `operation_snapshot_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`operation_snapshot_json`)),
  `pending_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`pending_json`)),
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fiscal_mirror_version` (`tenant_id`,`source_order_id`,`snapshot_version`),
  KEY `fk_fiscal_mirror_order` (`source_order_id`),
  CONSTRAINT `fk_fiscal_mirror_order` FOREIGN KEY (`source_order_id`) REFERENCES `fiscal_orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fiscal_mirrors`
--

LOCK TABLES `fiscal_mirrors` WRITE;
/*!40000 ALTER TABLE `fiscal_mirrors` DISABLE KEYS */;
/*!40000 ALTER TABLE `fiscal_mirrors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fiscal_number_reservations`
--

DROP TABLE IF EXISTS `fiscal_number_reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fiscal_number_reservations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `establishment_id` bigint(20) unsigned NOT NULL,
  `fiscal_document_id` bigint(20) unsigned NOT NULL,
  `fiscal_document_version` int(10) unsigned NOT NULL DEFAULT 1,
  `fiscal_series_id` bigint(20) unsigned DEFAULT NULL,
  `model` char(2) NOT NULL,
  `series` int(10) unsigned NOT NULL,
  `number` bigint(20) unsigned NOT NULL,
  `environment` tinyint(3) unsigned NOT NULL DEFAULT 2,
  `cnf` varchar(32) DEFAULT NULL,
  `access_key` varchar(64) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'RESERVED',
  `idempotency_key` varchar(128) DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reservation_document_version` (`tenant_id`,`fiscal_document_id`,`fiscal_document_version`),
  UNIQUE KEY `uq_reservation_number` (`tenant_id`,`establishment_id`,`model`,`series`,`number`),
  UNIQUE KEY `uq_reservation_access_key` (`tenant_id`,`access_key`),
  KEY `idx_reservation_scope` (`tenant_id`,`establishment_id`,`model`,`series`,`status`),
  KEY `idx_reservation_document` (`tenant_id`,`fiscal_document_id`),
  KEY `idx_reservation_series` (`fiscal_series_id`),
  KEY `idx_reservation_idempotency` (`tenant_id`,`idempotency_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fiscal_number_reservations`
--

LOCK TABLES `fiscal_number_reservations` WRITE;
/*!40000 ALTER TABLE `fiscal_number_reservations` DISABLE KEYS */;
/*!40000 ALTER TABLE `fiscal_number_reservations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fiscal_order_items`
--

DROP TABLE IF EXISTS `fiscal_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fiscal_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(18,4) NOT NULL,
  `unit_price` decimal(18,4) NOT NULL,
  `discount_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `freight_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `insurance_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `other_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `gross_total` decimal(18,2) NOT NULL,
  `net_total` decimal(18,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_fiscal_order_item_order` (`order_id`),
  KEY `fk_fiscal_order_item_product` (`product_id`),
  CONSTRAINT `fk_fiscal_order_item_order` FOREIGN KEY (`order_id`) REFERENCES `fiscal_orders` (`id`),
  CONSTRAINT `fk_fiscal_order_item_product` FOREIGN KEY (`product_id`) REFERENCES `produtos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fiscal_order_items`
--

LOCK TABLES `fiscal_order_items` WRITE;
/*!40000 ALTER TABLE `fiscal_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `fiscal_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fiscal_orders`
--

DROP TABLE IF EXISTS `fiscal_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fiscal_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(10) unsigned NOT NULL,
  `operation_type` varchar(5) NOT NULL,
  `establishment_id` int(10) unsigned DEFAULT NULL,
  `person_id` int(11) DEFAULT NULL,
  `internal_code` varchar(50) NOT NULL DEFAULT '',
  `operation_date` date NOT NULL,
  `commercial_status` varchar(12) NOT NULL DEFAULT 'SAVED',
  `fiscal_status` varchar(20) NOT NULL DEFAULT 'NOT_CREATED',
  `operation_nature` varchar(120) NOT NULL DEFAULT '',
  `fiscal_model` char(2) NOT NULL DEFAULT '55',
  `purpose` varchar(20) NOT NULL DEFAULT 'NORMAL',
  `final_consumer` tinyint(1) NOT NULL DEFAULT 0,
  `presence_indicator` varchar(2) NOT NULL DEFAULT '1',
  `payment_condition` varchar(30) NOT NULL DEFAULT '',
  `payment_method` varchar(30) NOT NULL DEFAULT '',
  `first_due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `carrier_id` int(11) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `freight_mode` varchar(2) NOT NULL DEFAULT '9',
  `discount_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `freight_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `insurance_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `other_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `products_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fiscal_orders_tenant` (`tenant_id`,`operation_type`,`commercial_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fiscal_orders`
--

LOCK TABLES `fiscal_orders` WRITE;
/*!40000 ALTER TABLE `fiscal_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `fiscal_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fiscal_reference_versions`
--

DROP TABLE IF EXISTS `fiscal_reference_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fiscal_reference_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference_type` varchar(50) NOT NULL,
  `source_document` varchar(160) NOT NULL,
  `source_version` varchar(40) NOT NULL,
  `published_at` date NOT NULL,
  `checksum_sha256` char(64) NOT NULL,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fiscal_reference_version` (`reference_type`,`source_version`,`checksum_sha256`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fiscal_reference_versions`
--

LOCK TABLES `fiscal_reference_versions` WRITE;
/*!40000 ALTER TABLE `fiscal_reference_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `fiscal_reference_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fiscal_series`
--

DROP TABLE IF EXISTS `fiscal_series`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fiscal_series` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `establishment_id` bigint(20) unsigned NOT NULL,
  `model` char(2) NOT NULL,
  `series` int(10) unsigned NOT NULL,
  `next_number` bigint(20) unsigned NOT NULL DEFAULT 1,
  `environment` tinyint(3) unsigned NOT NULL DEFAULT 2,
  `emission_type` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `process_version` varchar(40) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fiscal_series` (`tenant_id`,`establishment_id`,`model`,`series`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fiscal_series`
--

LOCK TABLES `fiscal_series` WRITE;
/*!40000 ALTER TABLE `fiscal_series` DISABLE KEYS */;
/*!40000 ALTER TABLE `fiscal_series` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fiscal_series_audit`
--

DROP TABLE IF EXISTS `fiscal_series_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fiscal_series_audit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `establishment_id` bigint(20) unsigned NOT NULL,
  `fiscal_series_id` bigint(20) unsigned NOT NULL,
  `action` varchar(32) NOT NULL DEFAULT 'UPDATE',
  `model` char(2) NOT NULL,
  `series` int(10) unsigned NOT NULL,
  `old_next_number` bigint(20) unsigned DEFAULT NULL,
  `new_next_number` bigint(20) unsigned NOT NULL,
  `changed_by` bigint(20) unsigned NOT NULL,
  `reason` varchar(500) NOT NULL,
  `before_json` longtext DEFAULT NULL,
  `after_json` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_series_audit_scope` (`tenant_id`,`establishment_id`,`model`,`series`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fiscal_series_audit`
--

LOCK TABLES `fiscal_series_audit` WRITE;
/*!40000 ALTER TABLE `fiscal_series_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `fiscal_series_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fornecedores`
--

DROP TABLE IF EXISTS `fornecedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `nome_fantasia` varchar(150) DEFAULT '',
  `cpf_cnpj` varchar(20) DEFAULT '',
  `inscricao_estadual` varchar(50) DEFAULT '',
  `email` varchar(150) DEFAULT '',
  `telefone` varchar(50) DEFAULT '',
  `cep` varchar(20) DEFAULT '',
  `logradouro` varchar(150) DEFAULT '',
  `numero` varchar(20) DEFAULT '',
  `complemento` varchar(100) DEFAULT '',
  `bairro` varchar(100) DEFAULT '',
  `municipio` varchar(100) DEFAULT '',
  `uf` varchar(2) DEFAULT '',
  `cidade` varchar(100) DEFAULT '',
  `status` varchar(20) DEFAULT 'ativo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fornecedores`
--

LOCK TABLES `fornecedores` WRITE;
/*!40000 ALTER TABLE `fornecedores` DISABLE KEYS */;
/*!40000 ALTER TABLE `fornecedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itens_venda`
--

DROP TABLE IF EXISTS `itens_venda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `itens_venda` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venda_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_itens_venda` (`venda_id`),
  KEY `fk_itens_produtos` (`produto_id`),
  CONSTRAINT `fk_itens_produtos` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`),
  CONSTRAINT `fk_itens_venda` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itens_venda`
--

LOCK TABLES `itens_venda` WRITE;
/*!40000 ALTER TABLE `itens_venda` DISABLE KEYS */;
/*!40000 ALTER TABLE `itens_venda` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `motoristas`
--

DROP TABLE IF EXISTS `motoristas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `motoristas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `cpf` varchar(20) DEFAULT '',
  `cnh` varchar(20) DEFAULT '',
  `categoria_cnh` varchar(10) DEFAULT '',
  `vencimento_cnh` date DEFAULT NULL,
  `telefone` varchar(50) DEFAULT '',
  `status` varchar(20) DEFAULT 'ativo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `motoristas`
--

LOCK TABLES `motoristas` WRITE;
/*!40000 ALTER TABLE `motoristas` DISABLE KEYS */;
/*!40000 ALTER TABLE `motoristas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_taxes`
--

DROP TABLE IF EXISTS `product_taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_taxes` (
  `product_id` int(11) NOT NULL,
  `ipi` varchar(50) DEFAULT '',
  `icms` varchar(50) DEFAULT '',
  `pis` varchar(50) DEFAULT '',
  `cofins` varchar(50) DEFAULT '',
  PRIMARY KEY (`product_id`),
  CONSTRAINT `fk_taxes_produto` FOREIGN KEY (`product_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_taxes`
--

LOCK TABLES `product_taxes` WRITE;
/*!40000 ALTER TABLE `product_taxes` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_taxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produtos`
--

DROP TABLE IF EXISTS `produtos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `ncm` varchar(20) DEFAULT '',
  `cest` varchar(20) DEFAULT '',
  `merchandise_origin` char(1) NOT NULL DEFAULT '',
  `extipi` varchar(3) NOT NULL DEFAULT '',
  `tax_benefit_code` varchar(20) NOT NULL DEFAULT '',
  `fci_number` char(36) NOT NULL DEFAULT '',
  `unidade` varchar(10) DEFAULT 'UN',
  `taxable_unit` varchar(6) NOT NULL DEFAULT 'UN',
  `conversion_factor` decimal(18,6) NOT NULL DEFAULT 1.000000,
  `gtin` varchar(50) DEFAULT '',
  `gtin_tributable` varchar(14) NOT NULL DEFAULT 'SEM GTIN',
  `cfop_padrao` varchar(20) DEFAULT '',
  `categoria` varchar(80) DEFAULT '',
  `cost_price` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `preco` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estoque_atual` int(11) NOT NULL DEFAULT 0,
  `minimum_stock` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `status` varchar(20) DEFAULT 'ativo',
  `company_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produtos`
--

LOCK TABLES `produtos` WRITE;
/*!40000 ALTER TABLE `produtos` DISABLE KEYS */;
/*!40000 ALTER TABLE `produtos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_rule_versions`
--

DROP TABLE IF EXISTS `tax_rule_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tax_rule_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(10) unsigned NOT NULL,
  `rule_code` varchar(80) NOT NULL,
  `rule_version` int(10) unsigned NOT NULL,
  `priority` int(11) NOT NULL DEFAULT 0,
  `valid_from` date NOT NULL,
  `valid_to` date DEFAULT NULL,
  `source_document` varchar(160) NOT NULL,
  `source_version` varchar(40) NOT NULL,
  `source_date` date NOT NULL,
  `conditions_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`conditions_json`)),
  `cfop` char(4) NOT NULL,
  `icms_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`icms_json`)),
  `ipi_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`ipi_json`)),
  `pis_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`pis_json`)),
  `cofins_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`cofins_json`)),
  `ibs_cbs_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`ibs_cbs_json`)),
  `selective_tax_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`selective_tax_json`)),
  `status` varchar(12) NOT NULL DEFAULT 'ACTIVE',
  `fixture_kind` varchar(12) NOT NULL DEFAULT 'PRODUCTION',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tax_rule_version` (`tenant_id`,`rule_code`,`rule_version`),
  KEY `idx_tax_rule_effective` (`tenant_id`,`status`,`valid_from`,`valid_to`,`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_rule_versions`
--

LOCK TABLES `tax_rule_versions` WRITE;
/*!40000 ALTER TABLE `tax_rule_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `tax_rule_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenant_schema_migrations`
--

DROP TABLE IF EXISTS `tenant_schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tenant_schema_migrations` (
  `migration_id` varchar(190) NOT NULL,
  `checksum` char(64) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `execution_status` varchar(32) NOT NULL,
  `duration_ms` int(10) unsigned NOT NULL DEFAULT 0,
  `operator_source` varchar(190) NOT NULL,
  PRIMARY KEY (`migration_id`),
  KEY `ix_tenant_schema_migrations_status` (`execution_status`,`applied_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_schema_migrations`
--

LOCK TABLES `tenant_schema_migrations` WRITE;
/*!40000 ALTER TABLE `tenant_schema_migrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `tenant_schema_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transportadoras`
--

DROP TABLE IF EXISTS `transportadoras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transportadoras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `nome_fantasia` varchar(150) DEFAULT '',
  `cpf_cnpj` varchar(20) DEFAULT '',
  `inscricao_estadual` varchar(50) DEFAULT '',
  `email` varchar(150) DEFAULT '',
  `telefone` varchar(50) DEFAULT '',
  `cep` varchar(20) DEFAULT '',
  `logradouro` varchar(150) DEFAULT '',
  `numero` varchar(20) DEFAULT '',
  `complemento` varchar(100) DEFAULT '',
  `bairro` varchar(100) DEFAULT '',
  `municipio` varchar(100) DEFAULT '',
  `uf` varchar(2) DEFAULT '',
  `cidade` varchar(100) DEFAULT '',
  `status` varchar(20) DEFAULT 'ativo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transportadoras`
--

LOCK TABLES `transportadoras` WRITE;
/*!40000 ALTER TABLE `transportadoras` DISABLE KEYS */;
/*!40000 ALTER TABLE `transportadoras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendas`
--

DROP TABLE IF EXISTS `vendas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `data_venda` date NOT NULL,
  `empresa_cnpj` varchar(20) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'finalizada',
  PRIMARY KEY (`id`),
  KEY `fk_vendas_clientes` (`cliente_id`),
  CONSTRAINT `fk_vendas_clientes` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendas`
--

LOCK TABLES `vendas` WRITE;
/*!40000 ALTER TABLE `vendas` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'mini_erp_tenant_990103'
--

--
-- Dumping routines for database 'mini_erp_tenant_990103'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-24 12:43:21

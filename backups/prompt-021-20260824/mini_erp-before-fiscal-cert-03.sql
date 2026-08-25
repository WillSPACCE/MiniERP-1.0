-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: mini_erp
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cfops`
--

LOCK TABLES `cfops` WRITE;
/*!40000 ALTER TABLE `cfops` DISABLE KEYS */;
INSERT INTO `cfops` VALUES (1,'5102','Venda de mercadoria comprada de outra empresa','','','ativo');
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
  `cidade` varchar(100) DEFAULT '',
  `status` varchar(20) DEFAULT 'ativo',
  `nome_fantasia` varchar(150) DEFAULT '',
  `tipo_pessoa` varchar(50) DEFAULT 'cliente',
  `pessoa_fisica` varchar(10) DEFAULT 'sim',
  `aniversario` date DEFAULT NULL,
  `genero` varchar(30) DEFAULT '',
  `data_cadastro` date DEFAULT NULL,
  `nome_contato` varchar(150) DEFAULT '',
  `fone_principal` varchar(50) DEFAULT '',
  `fone_2` varchar(50) DEFAULT '',
  `fone_3` varchar(50) DEFAULT '',
  `estado` varchar(100) DEFAULT '',
  `ponto_referencia` varchar(150) DEFAULT '',
  `codigo_ibge` varchar(20) DEFAULT '',
  `suprama` varchar(50) DEFAULT '',
  `im` varchar(50) DEFAULT '',
  `vendedor` varchar(150) DEFAULT '',
  `status_pagamento` varchar(50) DEFAULT '',
  `pagamento` varchar(50) DEFAULT '',
  `anvisa_data_venc` date DEFAULT NULL,
  `anvisa_codigo` varchar(50) DEFAULT '',
  `comissao_percentual` varchar(20) DEFAULT '',
  `comissao_volume` varchar(20) DEFAULT '',
  `forma_pagamento` varchar(50) DEFAULT '',
  `limite_credito` decimal(10,2) DEFAULT 0.00,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `funeral` varchar(20) DEFAULT '',
  `transportadora` varchar(150) DEFAULT '',
  `placa` varchar(20) DEFAULT '',
  `placa_uf` varchar(10) DEFAULT '',
  `antt` varchar(50) DEFAULT '',
  `frete` decimal(10,2) DEFAULT 0.00,
  `valor_frete` decimal(10,2) DEFAULT 0.00,
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
  `tenant_id` int(11) DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'Maria Silva','maria@empresa.com','(11) 99999-1111','São Paulo','ativo','','cliente','sim',NULL,'',NULL,'','','','','','','','','','','','',NULL,'','','','',0.00,0.00,'','','','','',0.00,0.00,'','','','','','','','','','',NULL,NULL),(2,'José Almeida','jose@empresa.com','(11) 98888-2222','Rio de Janeiro','ativo','','cliente','sim',NULL,'',NULL,'','','','','','','','','','','','',NULL,'','','','',0.00,0.00,'','','','','',0.00,0.00,'','','','','','','','','','',NULL,NULL),(3,'Ana Costa','ana@empresa.com','(11) 97777-3333','Belo Horizonte','ativo','','cliente','sim',NULL,'',NULL,'','','','','','','','','','','','',NULL,'','','','',0.00,0.00,'','','','','',0.00,0.00,'','','','','','','','','','',NULL,NULL);
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apelido` varchar(150) DEFAULT '',
  `razao_social` varchar(255) DEFAULT '',
  `cnpj` varchar(32) DEFAULT '',
  `municipio` varchar(120) DEFAULT '',
  `regime` varchar(120) DEFAULT '',
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
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
  `tenant_id` int(11) DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
  `tenant_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_itens_venda` (`venda_id`),
  KEY `fk_itens_produtos` (`produto_id`),
  CONSTRAINT `fk_itens_produtos` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`),
  CONSTRAINT `fk_itens_venda` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itens_venda`
--

LOCK TABLES `itens_venda` WRITE;
/*!40000 ALTER TABLE `itens_venda` DISABLE KEYS */;
INSERT INTO `itens_venda` VALUES (1,1,1,1,299.90,299.90,NULL),(2,2,2,1,189.00,189.00,NULL);
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
  `tenant_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `motoristas`
--

LOCK TABLES `motoristas` WRITE;
/*!40000 ALTER TABLE `motoristas` DISABLE KEYS */;
/*!40000 ALTER TABLE `motoristas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_admin_audit_log`
--

DROP TABLE IF EXISTS `platform_admin_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_admin_audit_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  `target_type` varchar(64) DEFAULT NULL,
  `target_id` varchar(190) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `metadata_json` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_platform_admin_audit_admin` (`admin_id`,`created_at`),
  KEY `ix_platform_admin_audit_action` (`action`,`created_at`),
  CONSTRAINT `fk_platform_admin_audit_admin` FOREIGN KEY (`admin_id`) REFERENCES `platform_admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_admin_audit_log`
--

LOCK TABLES `platform_admin_audit_log` WRITE;
/*!40000 ALTER TABLE `platform_admin_audit_log` DISABLE KEYS */;
INSERT INTO `platform_admin_audit_log` VALUES (34,NULL,'SCHEMA_MIGRATION_APPLIED','main_schema','20260822_create_platform_admin_auth.sql',NULL,'{\"migration_sha256\": \"F3EFA9D6FB532D75D62F24C38570258FBA6B6F2928DF386165D311CA34D9E5D3\", \"backup_sha256\": \"E0E7AA7539526BE6058EAA1CAD30A3E3A97E744B0E9236E1AAD94E05C6555752\", \"source\": \"PLATFORM-AUTH-01\"}','2026-08-22 11:56:55'),(81,9,'ADMIN_CREATED','platform_admin_user','9',NULL,'{\"source\":\"CLI\"}','2026-08-22 12:26:42'),(82,9,'LOGIN_SUCCESS','platform_admin_user','9','127.0.0.1',NULL,'2026-08-22 12:26:42'),(83,NULL,'LOGIN_FAILED','platform_admin_user',NULL,'127.0.0.1',NULL,'2026-08-22 12:28:47'),(84,9,'LOGIN_FAILED','platform_admin_user','9','127.0.0.1',NULL,'2026-08-22 12:29:04'),(85,9,'LOGIN_SUCCESS','platform_admin_user','9','127.0.0.1',NULL,'2026-08-22 12:29:32'),(86,9,'MULTITENANT_DRY_RUN','migration','','127.0.0.1','{\"tenant_ids\":[14]}','2026-08-22 12:41:43'),(87,9,'MULTITENANT_DRY_RUN','migration','20260821_create_fiscal_operations.sql',NULL,'{\"plan_id\":\"b45ab5e7ca80d96553585210bf165c0b\",\"tenant_ids\":[14],\"write_performed\":false}','2026-08-22 12:41:43'),(88,9,'MULTITENANT_DRY_RUN','migration','','127.0.0.1','{\"tenant_ids\":[]}','2026-08-22 12:42:18'),(89,NULL,'LOGIN_FAILED','platform_admin_user',NULL,'127.0.0.1',NULL,'2026-08-24 09:00:55'),(90,9,'LOGIN_SUCCESS','platform_admin_user','9','127.0.0.1',NULL,'2026-08-24 09:01:00'),(91,9,'LOGOUT','platform_admin_user','9','127.0.0.1',NULL,'2026-08-24 09:45:07'),(92,9,'LOGIN_SUCCESS','platform_admin_user','9','127.0.0.1',NULL,'2026-08-24 10:00:26'),(93,9,'LOGOUT','platform_admin_user','9','127.0.0.1',NULL,'2026-08-24 10:38:39'),(94,9,'LOGIN_SUCCESS','platform_admin_user','9','127.0.0.1',NULL,'2026-08-24 10:38:41'),(95,9,'LOGOUT','platform_admin_user','9','127.0.0.1',NULL,'2026-08-24 11:05:26'),(96,9,'LOGIN_SUCCESS','platform_admin_user','9','127.0.0.1',NULL,'2026-08-24 11:05:32');
/*!40000 ALTER TABLE `platform_admin_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_admin_users`
--

DROP TABLE IF EXISTS `platform_admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_admin_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `role` varchar(32) NOT NULL DEFAULT 'SUPER_ADMIN',
  `failed_login_attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `password_changed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_platform_admin_email` (`email`),
  KEY `ix_platform_admin_active_role` (`active`,`role`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_admin_users`
--

LOCK TABLES `platform_admin_users` WRITE;
/*!40000 ALTER TABLE `platform_admin_users` DISABLE KEYS */;
INSERT INTO `platform_admin_users` VALUES (9,'Willyan Martins','admin@willyan','$2y$10$iRwyUfMa3FZuAyOAduNwMe4JgMsLgXmcyCICvGwfATDEO4M2ESKCa',1,'SUPER_ADMIN',0,NULL,'2026-08-24 11:05:32','2026-08-22 12:26:42','2026-08-22 12:26:42','2026-08-24 11:05:32');
/*!40000 ALTER TABLE `platform_admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_database_operation_targets`
--

DROP TABLE IF EXISTS `platform_database_operation_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_database_operation_targets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `operation_id` char(32) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `db_name` varchar(190) NOT NULL,
  `status` varchar(32) NOT NULL,
  `backup_path` varchar(500) DEFAULT NULL,
  `backup_size` bigint(20) unsigned DEFAULT NULL,
  `backup_sha256` char(64) DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `duration_ms` int(10) unsigned DEFAULT NULL,
  `validation_json` longtext DEFAULT NULL,
  `error_message` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_operation_tenant` (`operation_id`,`tenant_id`),
  KEY `ix_operation_target_status` (`status`),
  CONSTRAINT `fk_operation_target_operation` FOREIGN KEY (`operation_id`) REFERENCES `platform_database_operations` (`operation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_database_operation_targets`
--

LOCK TABLES `platform_database_operation_targets` WRITE;
/*!40000 ALTER TABLE `platform_database_operation_targets` DISABLE KEYS */;
/*!40000 ALTER TABLE `platform_database_operation_targets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_database_operations`
--

DROP TABLE IF EXISTS `platform_database_operations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_database_operations` (
  `operation_id` char(32) NOT NULL,
  `plan_id` char(32) NOT NULL,
  `admin_id` bigint(20) unsigned NOT NULL,
  `migration_id` varchar(190) NOT NULL,
  `checksum` char(64) NOT NULL,
  `risk` varchar(32) NOT NULL,
  `reason` varchar(500) NOT NULL,
  `created_at` datetime NOT NULL,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `status` varchar(32) NOT NULL,
  PRIMARY KEY (`operation_id`),
  UNIQUE KEY `plan_id` (`plan_id`),
  KEY `fk_database_operation_admin` (`admin_id`),
  CONSTRAINT `fk_database_operation_admin` FOREIGN KEY (`admin_id`) REFERENCES `platform_admin_users` (`id`),
  CONSTRAINT `fk_database_operation_plan` FOREIGN KEY (`plan_id`) REFERENCES `platform_migration_plans` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_database_operations`
--

LOCK TABLES `platform_database_operations` WRITE;
/*!40000 ALTER TABLE `platform_database_operations` DISABLE KEYS */;
/*!40000 ALTER TABLE `platform_database_operations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_migration_plans`
--

DROP TABLE IF EXISTS `platform_migration_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_migration_plans` (
  `plan_id` char(32) NOT NULL,
  `admin_id` bigint(20) unsigned NOT NULL,
  `migration_id` varchar(190) NOT NULL,
  `checksum` char(64) NOT NULL,
  `tenant_ids_json` longtext NOT NULL,
  `simulation_json` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `consumed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`plan_id`),
  KEY `ix_migration_plan_expiry` (`expires_at`,`consumed_at`),
  KEY `fk_migration_plan_admin` (`admin_id`),
  CONSTRAINT `fk_migration_plan_admin` FOREIGN KEY (`admin_id`) REFERENCES `platform_admin_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_migration_plans`
--

LOCK TABLES `platform_migration_plans` WRITE;
/*!40000 ALTER TABLE `platform_migration_plans` DISABLE KEYS */;
INSERT INTO `platform_migration_plans` VALUES ('b45ab5e7ca80d96553585210bf165c0b',9,'20260821_create_fiscal_operations.sql','8bcc4b317b3e67ecb6e6f5a05f8ffffaaaa4690ca505b59f320d6def4aefa1fd','[14]','[{\"tenant_id\":14,\"db_name\":\"mini_erp_tenant_14\",\"status\":\"ALREADY_APPLIED\",\"reasons\":[],\"write_performed\":false}]','2026-08-22 12:41:43','2026-08-22 12:51:43','2026-08-22 12:42:18');
/*!40000 ALTER TABLE `platform_migration_plans` ENABLE KEYS */;
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
  `tenant_id` int(11) DEFAULT NULL,
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
  `categoria` varchar(80) DEFAULT '',
  `preco` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estoque_atual` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) DEFAULT 'ativo',
  `company_id` int(11) DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `ix_produtos_tenant_id` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produtos`
--

LOCK TABLES `produtos` WRITE;
/*!40000 ALTER TABLE `produtos` DISABLE KEYS */;
INSERT INTO `produtos` VALUES (1,'Teclado Mecânico','TEC-001','Periféricos',299.90,18,'ativo',NULL,NULL),(2,'Mouse Gamer','MOU-010','Periféricos',189.00,24,'ativo',NULL,NULL),(3,'Monitor 24','MON-024','Eletrônicos',899.90,8,'ativo',NULL,NULL),(4,'Notebook Pro','NBP-500','Computadores',3499.00,5,'ativo',NULL,NULL);
/*!40000 ALTER TABLE `produtos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenants`
--

DROP TABLE IF EXISTS `tenants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tenants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(40) NOT NULL,
  `nome_fantasia` varchar(255) DEFAULT '',
  `razao_social` varchar(255) DEFAULT '',
  `cnpj` varchar(32) DEFAULT '',
  `slug` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'ativo',
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `db_name` varchar(255) DEFAULT NULL,
  `schema_version` varchar(32) DEFAULT NULL,
  `cep` varchar(16) DEFAULT NULL,
  `uf` varchar(4) DEFAULT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero` varchar(64) DEFAULT NULL,
  `complemento` varchar(255) DEFAULT NULL,
  `bairro` varchar(128) DEFAULT NULL,
  `telefone` varchar(48) DEFAULT NULL,
  `codigo_ibge` varchar(32) DEFAULT NULL,
  `municipio` varchar(128) DEFAULT NULL,
  `regime` varchar(64) DEFAULT NULL,
  `blocked` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenants_slug` (`slug`),
  UNIQUE KEY `uq_tenants_uuid` (`uuid`),
  UNIQUE KEY `uq_tenants_cnpj` (`cnpj`)
) ENGINE=InnoDB AUTO_INCREMENT=990104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenants`
--

LOCK TABLES `tenants` WRITE;
/*!40000 ALTER TABLE `tenants` DISABLE KEYS */;
INSERT INTO `tenants` VALUES (5,'7e281494db92e153','INFOCASE','INFOCASE INFORMATICA LTDA','07924387000111','infocase',NULL,'ativo','{\"action\":\"save_empresa\",\"apelido\":\"INFOCASE\",\"razao_social\":\"INFOCASE INFORMATICA LTDA\",\"cnpj\":\"07.924.387\\/0001-11\",\"municipio\":\"PINHAIS\",\"regime\":\"\",\"cep\":\"83320005\",\"uf\":\"PR\",\"logradouro\":\"RODOVIA DEPUTADO JOAO LEOPOLDO JACOMEL\",\"numero\":\"11034\",\"complemento\":\"\",\"bairro\":\"PINEVILLE\",\"telefone\":\"4130335070\",\"codigo_ibge\":\"\",\"nome_fantasia\":\"INFOCASE\",\"data\":\"{\\\"uf\\\":\\\"PR\\\",\\\"cep\\\":\\\"83320005\\\",\\\"qsa\\\":[{\\\"pais\\\":null,\\\"nome_socio\\\":\\\"DIEGO ALANO FRANTZ\\\",\\\"codigo_pais\\\":null,\\\"faixa_etaria\\\":\\\"Entre 41 a 50 anos\\\",\\\"cnpj_cpf_do_socio\\\":\\\"***330400**\\\",\\\"qualificacao_socio\\\":\\\"Sócio-Administrador\\\",\\\"codigo_faixa_etaria\\\":5,\\\"data_entrada_sociedade\\\":\\\"2006-03-30\\\",\\\"identificador_de_socio\\\":2,\\\"cpf_representante_legal\\\":\\\"***000000**\\\",\\\"nome_representante_legal\\\":\\\"\\\",\\\"codigo_qualificacao_socio\\\":49,\\\"qualificacao_representante_legal\\\":\\\"Não informada\\\",\\\"codigo_qualificacao_representante_legal\\\":0},{\\\"pais\\\":null,\\\"nome_socio\\\":\\\"MARCIA LECH\\\",\\\"codigo_pais\\\":null,\\\"faixa_etaria\\\":\\\"Entre 51 a 60 anos\\\",\\\"cnpj_cpf_do_socio\\\":\\\"***872559**\\\",\\\"qualificacao_socio\\\":\\\"Sócio-Administrador\\\",\\\"codigo_faixa_etaria\\\":6,\\\"data_entrada_sociedade\\\":\\\"2023-06-01\\\",\\\"identificador_de_socio\\\":2,\\\"cpf_representante_legal\\\":\\\"***000000**\\\",\\\"nome_representante_legal\\\":\\\"\\\",\\\"codigo_qualificacao_socio\\\":49,\\\"qualificacao_representante_legal\\\":\\\"Não informada\\\",\\\"codigo_qualificacao_representante_legal\\\":0},{\\\"pais\\\":null,\\\"nome_socio\\\":\\\"ZENITE LECH\\\",\\\"codigo_pais\\\":null,\\\"faixa_etaria\\\":\\\"Entre 61 a 70 anos\\\",\\\"cnpj_cpf_do_socio\\\":\\\"***439489**\\\",\\\"qualificacao_socio\\\":\\\"Sócio-Administrador\\\",\\\"codigo_faixa_etaria\\\":7,\\\"data_entrada_sociedade\\\":\\\"2019-11-18\\\",\\\"identificador_de_socio\\\":2,\\\"cpf_representante_legal\\\":\\\"***000000**\\\",\\\"nome_representante_legal\\\":\\\"\\\",\\\"codigo_qualificacao_socio\\\":49,\\\"qualificacao_representante_legal\\\":\\\"Não informada\\\",\\\"codigo_qualificacao_representante_legal\\\":0}],\\\"cnpj\\\":\\\"07924387000111\\\",\\\"pais\\\":null,\\\"email\\\":null,\\\"porte\\\":\\\"MICRO EMPRESA\\\",\\\"bairro\\\":\\\"PINEVILLE\\\",\\\"numero\\\":\\\"11034\\\",\\\"ddd_fax\\\":\\\"4130337878\\\",\\\"municipio\\\":\\\"PINHAIS\\\",\\\"logradouro\\\":\\\"DEPUTADO JOAO LEOPOLDO JACOMEL\\\",\\\"cnae_fiscal\\\":4751201,\\\"codigo_pais\\\":null,\\\"complemento\\\":\\\"\\\",\\\"codigo_porte\\\":1,\\\"razao_social\\\":\\\"INFOCASE INFORMATICA LTDA\\\",\\\"nome_fantasia\\\":\\\"INFOCASE\\\",\\\"capital_social\\\":20000,\\\"ddd_telefone_1\\\":\\\"4130335070\\\",\\\"ddd_telefone_2\\\":\\\"\\\",\\\"opcao_pelo_mei\\\":false,\\\"codigo_municipio\\\":5453,\\\"cnaes_secundarios\\\":[{\\\"codigo\\\":3314710,\\\"descricao\\\":\\\"Manutenção e reparação de máquinas e equipamentos para uso geral não especificados anteriormente\\\"},{\\\"codigo\\\":3329599,\\\"descricao\\\":\\\"Instalação de outros equipamentos não especificados anteriormente\\\"},{\\\"codigo\\\":4752100,\\\"descricao\\\":\\\"Comércio varejista especializado de equipamentos de telefonia e comunicação\\\"},{\\\"codigo\\\":4753900,\\\"descricao\\\":\\\"Comércio varejista especializado de eletrodomésticos e equipamentos de áudio e vídeo\\\"},{\\\"codigo\\\":4761003,\\\"descricao\\\":\\\"Comércio varejista de artigos de papelaria\\\"},{\\\"codigo\\\":4789099,\\\"descricao\\\":\\\"Comércio varejista de outros produtos não especificados anteriormente\\\"},{\\\"codigo\\\":6201501,\\\"descricao\\\":\\\"Desenvolvimento de programas de computador sob encomenda\\\"},{\\\"codigo\\\":6203100,\\\"descricao\\\":\\\"Desenvolvimento e licenciamento de programas de computador não-customizáveis\\\"},{\\\"codigo\\\":6319400,\\\"descricao\\\":\\\"Portais, provedores de conteúdo e outros serviços de informação na internet\\\"},{\\\"codigo\\\":7733100,\\\"descricao\\\":\\\"Aluguel de máquinas e equipamentos para escritórios\\\"},{\\\"codigo\\\":9511800,\\\"descricao\\\":\\\"Reparação e manutenção de computadores e de equipamentos periféricos\\\"},{\\\"codigo\\\":9512600,\\\"descricao\\\":\\\"Reparação e manutenção de equipamentos de comunicação\\\"}],\\\"natureza_juridica\\\":\\\"Sociedade Empresária Limitada\\\",\\\"regime_tributario\\\":[],\\\"situacao_especial\\\":\\\"\\\",\\\"opcao_pelo_simples\\\":true,\\\"situacao_cadastral\\\":2,\\\"data_opcao_pelo_mei\\\":null,\\\"data_exclusao_do_mei\\\":null,\\\"cnae_fiscal_descricao\\\":\\\"Comércio varejista especializado de equipamentos e suprimentos de informática\\\",\\\"codigo_municipio_ibge\\\":4119152,\\\"data_inicio_atividade\\\":\\\"2006-03-30\\\",\\\"data_situacao_especial\\\":null,\\\"data_opcao_pelo_simples\\\":\\\"2019-01-01\\\",\\\"data_situacao_cadastral\\\":\\\"2006-03-30\\\",\\\"nome_cidade_no_exterior\\\":\\\"\\\",\\\"codigo_natureza_juridica\\\":2062,\\\"data_exclusao_do_simples\\\":null,\\\"motivo_situacao_cadastral\\\":0,\\\"ente_federativo_responsavel\\\":\\\"\\\",\\\"identificador_matriz_filial\\\":1,\\\"qualificacao_do_responsavel\\\":49,\\\"descricao_situacao_cadastral\\\":\\\"ATIVA\\\",\\\"descricao_tipo_de_logradouro\\\":\\\"RODOVIA\\\",\\\"descricao_motivo_situacao_cadastral\\\":\\\"SEM MOTIVO\\\",\\\"descricao_identificador_matriz_filial\\\":\\\"MATRIZ\\\"}\"}','2026-08-14 15:37:05','2026-08-22 11:43:25','mini_erp_tenant_5','v1','83320005','PR','RODOVIA DEPUTADO JOAO LEOPOLDO JACOMEL','11034','','PINEVILLE','4130335070','','PINHAIS','',0),(14,'92fd73f3a9554e0adf943f74805a525f','willyan info','willyan informatica','07924387000112','willyaninfo',NULL,'ativa',NULL,'2026-08-20 14:59:00','2026-08-22 11:11:27','mini_erp_tenant_14','v1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0),(990103,'6e67dc0a533843e7bb14188b17e597d4','INFOCASE INFORMATICA  FILIAL LTDA FILIAL SIMPLES','INFOCASE INFORMATICA  FILIAL LTDA FILIAL SIMPLES','07924387000200','infocasefilial',NULL,'ativa',NULL,'2026-08-24 09:06:39','2026-08-24 09:07:03','mini_erp_tenant_990103','v1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0);
/*!40000 ALTER TABLE `tenants` ENABLE KEYS */;
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
  `tenant_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transportadoras`
--

LOCK TABLES `transportadoras` WRITE;
/*!40000 ALTER TABLE `transportadoras` DISABLE KEYS */;
/*!40000 ALTER TABLE `transportadoras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'user',
  `avatar` varchar(255) DEFAULT '',
  `status` varchar(20) DEFAULT 'ativo',
  `email_verified` tinyint(1) DEFAULT 0,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `permissions` text DEFAULT NULL,
  `cargo` varchar(50) DEFAULT 'funcionario',
  `company_id` int(11) DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `ix_usuarios_tenant_id` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (8,'INFOCASE','admin@infocase','$2y$10$RbiBB5hEBDxpftwqjrseZuYOMD8TInaIcLm4TON2.DNVZtjTt6A/O','admin','','ativo',1,NULL,NULL,'funcionario',NULL,5),(9,'Willyan Martins','willyan.gits@gmail.com','$2y$10$mdtqQDi2V10cg7MdKJ3youa32BSYkXmTT1HvcG2DCTf8Qiwsb1.GG','admin','','ativo',1,NULL,NULL,'funcionario',14,14),(10,'willyan','willyan@infocase.com','$2y$10$ShaPdJtbDg1eXcW2.YMDIe7m428NTovqdwCazEn6l/NPjRJ6BtUcq','user','','ativo',1,NULL,NULL,'funcionario',5,5);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
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
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'finalizada',
  `tenant_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_vendas_clientes` (`cliente_id`),
  CONSTRAINT `fk_vendas_clientes` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendas`
--

LOCK TABLES `vendas` WRITE;
/*!40000 ALTER TABLE `vendas` DISABLE KEYS */;
INSERT INTO `vendas` VALUES (1,1,'2026-08-01',299.90,'finalizada',NULL),(2,2,'2026-08-03',189.00,'finalizada',NULL);
/*!40000 ALTER TABLE `vendas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'mini_erp'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-24 11:30:50

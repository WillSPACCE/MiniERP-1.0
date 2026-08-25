-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: mini_erp_tenant_14
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
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'Maria Silva','maria@empresa.com','(11) 99999-1111','','','','','','','','','','','São Paulo','ativo','','cliente','sim',NULL,'',NULL,'','','','','','','','','','','','',NULL,'','','','',0.00,0.00,'','','','','',0.00,0.00,NULL,'PF','','9','','1058','BRASIL',NULL,1,0,0,0),(2,'José Almeida','jose@empresa.com','(11) 98888-2222','','','','','','','','','','','Rio de Janeiro','ativo','','cliente','sim',NULL,'',NULL,'','','','','','','','','','','','',NULL,'','','','',0.00,0.00,'','','','','',0.00,0.00,NULL,'PF','','9','','1058','BRASIL',NULL,1,0,0,0),(3,'Ana Costa','ana@empresa.com','(11) 97777-3333','','','','','','','','','','','Belo Horizonte','ativo','','cliente','sim',NULL,'',NULL,'','','','','','','','','','','','',NULL,'','','','',0.00,0.00,'','','','','',0.00,0.00,NULL,'PF','','9','','1058','BRASIL',NULL,1,0,0,0);
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `establishments`
--

LOCK TABLES `establishments` WRITE;
/*!40000 ALTER TABLE `establishments` DISABLE KEYS */;
INSERT INTO `establishments` VALUES (5,14,'07924387000112','willyan informatica','willyan info','PR','','','','1','Avenida Maringa','1354','Bloco D; Unidade 7','Emiliano Perneta','1234567','PINHAIS','PR','83324442','1058','BRASIL','4130124500','rma@fagundez.com','MATRIZ',1,'ativo','INCOMPLETE','2026-08-21 15:51:30','2026-08-21 15:51:30');
/*!40000 ALTER TABLE `establishments` ENABLE KEYS */;
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
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itens_venda`
--

LOCK TABLES `itens_venda` WRITE;
/*!40000 ALTER TABLE `itens_venda` DISABLE KEYS */;
INSERT INTO `itens_venda` VALUES (1,1,1,1,299.90,299.90),(2,2,2,1,189.00,189.00);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produtos`
--

LOCK TABLES `produtos` WRITE;
/*!40000 ALTER TABLE `produtos` DISABLE KEYS */;
INSERT INTO `produtos` VALUES (1,'Teclado Mecânico','TEC-001','','','','','','','UN','UN',1.000000,'','SEM GTIN','','Periféricos',0.0000,299.90,18,0.0000,'inativo',NULL),(2,'Mouse Gamer','MOU-010','','','','','','','UN','UN',1.000000,'','SEM GTIN','','Periféricos',0.0000,189.00,24,0.0000,'ativo',NULL),(3,'Monitor 24','MON-024','','','','','','','UN','UN',1.000000,'','SEM GTIN','','Eletrônicos',0.0000,899.90,8,0.0000,'ativo',NULL),(4,'Notebook Pro','NBP-500','','','','','','','UN','UN',1.000000,'','SEM GTIN','','Computadores',0.0000,3499.00,5,0.0000,'ativo',NULL),(7,'Notebook Gamer Nitro +5','11','12345678','1234567','0','','','','UN','UN',1.000000,'SEM GTIN','SEM GTIN','5102','notebook',0.0000,4123.50,14,1.0000,'ativo',NULL);
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
  `blocked` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenants_slug` (`slug`),
  UNIQUE KEY `uq_tenants_uuid` (`uuid`),
  UNIQUE KEY `uq_tenants_cnpj` (`cnpj`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenants`
--

LOCK TABLES `tenants` WRITE;
/*!40000 ALTER TABLE `tenants` DISABLE KEYS */;
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
  `company_id` int(11) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `permissions` text DEFAULT NULL,
  `cargo` varchar(50) DEFAULT 'funcionario',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Administrador','admin@localhost','$2y$10$ZB.1TvHPuXFC5mlRrm5ox.oud5ilJQOhKdz8YQo7o3GAlS0UEdAtS','admin','','ativo',NULL,0,NULL,NULL,'funcionario');
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
  `empresa_cnpj` varchar(20) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'finalizada',
  PRIMARY KEY (`id`),
  KEY `fk_vendas_clientes` (`cliente_id`),
  CONSTRAINT `fk_vendas_clientes` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendas`
--

LOCK TABLES `vendas` WRITE;
/*!40000 ALTER TABLE `vendas` DISABLE KEYS */;
INSERT INTO `vendas` VALUES (1,1,'2026-08-01',NULL,299.90,'finalizada'),(2,2,'2026-08-03',NULL,189.00,'finalizada');
/*!40000 ALTER TABLE `vendas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'mini_erp_tenant_14'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-21 15:10:39

-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: mini_erp
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
-- Current Database: `mini_erp`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `mini_erp` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `mini_erp`;

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
INSERT INTO `clientes` VALUES (1,'Maria Silva','maria@empresa.com','(11) 99999-1111','São Paulo','ativo','','cliente','sim',NULL,'',NULL,'','','','','','','','','','','','',NULL,'','','','',0.00,0.00,'','','','','',0.00,0.00,'','','','','','','','','','',1,NULL),(2,'José Almeida','jose@empresa.com','(11) 98888-2222','Rio de Janeiro','ativo','','cliente','sim',NULL,'',NULL,'','','','','','','','','','','','',NULL,'','','','',0.00,0.00,'','','','','',0.00,0.00,'','','','','','','','','','',1,NULL),(3,'Ana Costa','ana@empresa.com','(11) 97777-3333','Belo Horizonte','ativo','','cliente','sim',NULL,'',NULL,'','','','','','','','','','','','',NULL,'','','','',0.00,0.00,'','','','','',0.00,0.00,'','','','','','','','','','',1,NULL),(4,'Teste Persistencia','teste@teste.com','11999999999','Sao Paulo','ativo','Loja Teste','cliente','sim',NULL,'','2026-08-13','','','','','SP','','','','','','','',NULL,'','','','',0.00,0.00,'','','','','',0.00,0.00,'12345678901','','Rua A','123','','Centro','Sao Paulo','','SP','01000000',1,NULL),(5,'Cliente Teste UX','teste@ux.com','11999999999','São Paulo','ativo','','cliente','sim',NULL,'','2026-08-13','','','','','','','','','','','','',NULL,'','','','',0.00,0.00,'','','','','',0.00,0.00,'12345678900','','','','','','','','','',1,NULL),(6,'Cliente Teste UX','teste@ux.com','11999999999','São Paulo','ativo','','cliente','sim',NULL,'','2026-08-13','','','','','','','','','','','','',NULL,'','','','',0.00,0.00,'','','','','',0.00,0.00,'12345678900','','','','','','','','','',1,NULL),(7,'Cliente via Tela','cliente.tela@example.com','','São Paulo','ativo','','cliente','sim',NULL,'','2026-08-13','Contato Tela','','','','SP','','','','','','','',NULL,'','','','',0.00,0.00,'','','','','',0.00,0.00,'98765432100','','Av. Exemplo','123','','Centro','','','SP','01000000',1,NULL),(8,'Cliente via Tela','cliente.tela@example.com','','São Paulo','ativo','','cliente','sim',NULL,'','2026-08-13','Contato Tela','','','','SP','','','','','','','',NULL,'','','','',0.00,0.00,'','','','','',0.00,0.00,'98765432100','','Av. Exemplo','123','','Centro','','','SP','01000000',1,NULL),(9,'Cliente via Tela','cliente.tela@example.com','','São Paulo','ativo','','cliente','sim',NULL,'','2026-08-13','Contato Tela','','','','SP','','','','','','','',NULL,'','','','',0.00,0.00,'','','','','',0.00,0.00,'98765432100','','Av. Exemplo','123','','Centro','','','SP','01000000',1,NULL),(10,'Willyan Martins Martins','martins.willyan20@gmail.com','','','ativo','Willyan Martins Martins','cliente','sim','1997-02-15','Masculino','2026-08-13','Willyan Martins FAGUNDEZ','41996484746','','','PR','','','','','','','',NULL,'','','','',0.00,0.00,'','','','','',0.00,0.00,'1234567890001','','','','','','','','PR','',1,NULL);
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
INSERT INTO `fornecedores` VALUES (1,'Willyan Martins FAGUNDEZ','Willyan Martins FAGUNDEZ','1234567890001','948452456','rma@fagundez.com','4130124500','83324442','Avenida Maringa','1354','Bloco D; Unidade 7','Emiliano Perneta','pinhais','pr','PINHAIS','ativo',1,NULL);
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
INSERT INTO `itens_venda` VALUES (1,1,1,1,299.90,299.90,1),(2,2,2,1,189.00,189.00,1),(3,3,4,2,3499.00,6998.00,1),(4,4,3,1,899.90,899.90,1);
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
INSERT INTO `motoristas` VALUES (1,'Gabriel dos santos','11234567899','841848','AB','2027-09-09','4130124500','ativo',1);
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
INSERT INTO `password_resets` VALUES (1,'martins.willyan20@gmail.com','682100','2026-08-13 23:03:25','2026-08-13 17:03:25'),(2,'martins.willyan20@gmail.com','620958','2026-08-13 23:05:44','2026-08-13 17:05:44'),(3,'martins.willyan20@gmail.com','421991','2026-08-13 23:19:20','2026-08-13 17:19:20');
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
INSERT INTO `product_taxes` VALUES (4,'1.5','18','1.65','7.6',1),(5,'5','30','4','',1);
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
INSERT INTO `produtos` VALUES (1,'Teclado Mecânico','TEC-001','Periféricos',299.90,18,'ativo',NULL,1),(2,'Mouse Gamer','MOU-010','Periféricos',189.00,24,'ativo',NULL,1),(3,'Monitor 24','MON-024','Eletrônicos',899.90,7,'ativo',NULL,1),(4,'Notebook Pro','NBP-500','Computadores',3499.00,3,'ativo',NULL,1),(5,'Notebook Gamer Nitro +5','22','notebook',3900.00,22,'ativo',NULL,1);
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenants`
--

LOCK TABLES `tenants` WRITE;
/*!40000 ALTER TABLE `tenants` DISABLE KEYS */;
INSERT INTO `tenants` VALUES (1,'e4a437b0ea9149de','Default Tenant','Default Tenant','','default',NULL,'ativo','[]','2026-08-14 14:23:57','2026-08-14 15:51:48','mini_erp_tenant_1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0),(2,'8e88e3d4cdb5ddf4','Empresa Teste 1','Empresa Teste 1','10000000000001','tenant-1',NULL,'ativo','{\"created_by\":\"script\",\"index\":1}','2026-08-14 14:40:12','2026-08-14 14:40:12','mini_erp_tenant_1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0),(3,'0158bd003dfc47d9','Empresa Teste 2','Empresa Teste 2','10000000000002','tenant-2',NULL,'ativo','{\"created_by\":\"script\",\"index\":2}','2026-08-14 14:40:12','2026-08-14 14:40:12','mini_erp_tenant_2',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0),(4,'d36e23e4ce0fa733','Empresa Teste 3','Empresa Teste 3','10000000000003','tenant-3',NULL,'ativo','{\"created_by\":\"script\",\"index\":3}','2026-08-14 14:40:12','2026-08-14 14:40:12','mini_erp_tenant_3',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0),(5,'7e281494db92e153','INFOCASE','INFOCASE INFORMATICA LTDA','07924387000111','infocase',NULL,'ativo','{\"action\":\"save_empresa\",\"apelido\":\"INFOCASE\",\"razao_social\":\"INFOCASE INFORMATICA LTDA\",\"cnpj\":\"07.924.387\\/0001-11\",\"municipio\":\"PINHAIS\",\"regime\":\"\",\"cep\":\"83320005\",\"uf\":\"PR\",\"logradouro\":\"RODOVIA DEPUTADO JOAO LEOPOLDO JACOMEL\",\"numero\":\"11034\",\"complemento\":\"\",\"bairro\":\"PINEVILLE\",\"telefone\":\"4130335070\",\"codigo_ibge\":\"\",\"nome_fantasia\":\"INFOCASE\",\"data\":\"{\\\"uf\\\":\\\"PR\\\",\\\"cep\\\":\\\"83320005\\\",\\\"qsa\\\":[{\\\"pais\\\":null,\\\"nome_socio\\\":\\\"DIEGO ALANO FRANTZ\\\",\\\"codigo_pais\\\":null,\\\"faixa_etaria\\\":\\\"Entre 41 a 50 anos\\\",\\\"cnpj_cpf_do_socio\\\":\\\"***330400**\\\",\\\"qualificacao_socio\\\":\\\"Sócio-Administrador\\\",\\\"codigo_faixa_etaria\\\":5,\\\"data_entrada_sociedade\\\":\\\"2006-03-30\\\",\\\"identificador_de_socio\\\":2,\\\"cpf_representante_legal\\\":\\\"***000000**\\\",\\\"nome_representante_legal\\\":\\\"\\\",\\\"codigo_qualificacao_socio\\\":49,\\\"qualificacao_representante_legal\\\":\\\"Não informada\\\",\\\"codigo_qualificacao_representante_legal\\\":0},{\\\"pais\\\":null,\\\"nome_socio\\\":\\\"MARCIA LECH\\\",\\\"codigo_pais\\\":null,\\\"faixa_etaria\\\":\\\"Entre 51 a 60 anos\\\",\\\"cnpj_cpf_do_socio\\\":\\\"***872559**\\\",\\\"qualificacao_socio\\\":\\\"Sócio-Administrador\\\",\\\"codigo_faixa_etaria\\\":6,\\\"data_entrada_sociedade\\\":\\\"2023-06-01\\\",\\\"identificador_de_socio\\\":2,\\\"cpf_representante_legal\\\":\\\"***000000**\\\",\\\"nome_representante_legal\\\":\\\"\\\",\\\"codigo_qualificacao_socio\\\":49,\\\"qualificacao_representante_legal\\\":\\\"Não informada\\\",\\\"codigo_qualificacao_representante_legal\\\":0},{\\\"pais\\\":null,\\\"nome_socio\\\":\\\"ZENITE LECH\\\",\\\"codigo_pais\\\":null,\\\"faixa_etaria\\\":\\\"Entre 61 a 70 anos\\\",\\\"cnpj_cpf_do_socio\\\":\\\"***439489**\\\",\\\"qualificacao_socio\\\":\\\"Sócio-Administrador\\\",\\\"codigo_faixa_etaria\\\":7,\\\"data_entrada_sociedade\\\":\\\"2019-11-18\\\",\\\"identificador_de_socio\\\":2,\\\"cpf_representante_legal\\\":\\\"***000000**\\\",\\\"nome_representante_legal\\\":\\\"\\\",\\\"codigo_qualificacao_socio\\\":49,\\\"qualificacao_representante_legal\\\":\\\"Não informada\\\",\\\"codigo_qualificacao_representante_legal\\\":0}],\\\"cnpj\\\":\\\"07924387000111\\\",\\\"pais\\\":null,\\\"email\\\":null,\\\"porte\\\":\\\"MICRO EMPRESA\\\",\\\"bairro\\\":\\\"PINEVILLE\\\",\\\"numero\\\":\\\"11034\\\",\\\"ddd_fax\\\":\\\"4130337878\\\",\\\"municipio\\\":\\\"PINHAIS\\\",\\\"logradouro\\\":\\\"DEPUTADO JOAO LEOPOLDO JACOMEL\\\",\\\"cnae_fiscal\\\":4751201,\\\"codigo_pais\\\":null,\\\"complemento\\\":\\\"\\\",\\\"codigo_porte\\\":1,\\\"razao_social\\\":\\\"INFOCASE INFORMATICA LTDA\\\",\\\"nome_fantasia\\\":\\\"INFOCASE\\\",\\\"capital_social\\\":20000,\\\"ddd_telefone_1\\\":\\\"4130335070\\\",\\\"ddd_telefone_2\\\":\\\"\\\",\\\"opcao_pelo_mei\\\":false,\\\"codigo_municipio\\\":5453,\\\"cnaes_secundarios\\\":[{\\\"codigo\\\":3314710,\\\"descricao\\\":\\\"Manutenção e reparação de máquinas e equipamentos para uso geral não especificados anteriormente\\\"},{\\\"codigo\\\":3329599,\\\"descricao\\\":\\\"Instalação de outros equipamentos não especificados anteriormente\\\"},{\\\"codigo\\\":4752100,\\\"descricao\\\":\\\"Comércio varejista especializado de equipamentos de telefonia e comunicação\\\"},{\\\"codigo\\\":4753900,\\\"descricao\\\":\\\"Comércio varejista especializado de eletrodomésticos e equipamentos de áudio e vídeo\\\"},{\\\"codigo\\\":4761003,\\\"descricao\\\":\\\"Comércio varejista de artigos de papelaria\\\"},{\\\"codigo\\\":4789099,\\\"descricao\\\":\\\"Comércio varejista de outros produtos não especificados anteriormente\\\"},{\\\"codigo\\\":6201501,\\\"descricao\\\":\\\"Desenvolvimento de programas de computador sob encomenda\\\"},{\\\"codigo\\\":6203100,\\\"descricao\\\":\\\"Desenvolvimento e licenciamento de programas de computador não-customizáveis\\\"},{\\\"codigo\\\":6319400,\\\"descricao\\\":\\\"Portais, provedores de conteúdo e outros serviços de informação na internet\\\"},{\\\"codigo\\\":7733100,\\\"descricao\\\":\\\"Aluguel de máquinas e equipamentos para escritórios\\\"},{\\\"codigo\\\":9511800,\\\"descricao\\\":\\\"Reparação e manutenção de computadores e de equipamentos periféricos\\\"},{\\\"codigo\\\":9512600,\\\"descricao\\\":\\\"Reparação e manutenção de equipamentos de comunicação\\\"}],\\\"natureza_juridica\\\":\\\"Sociedade Empresária Limitada\\\",\\\"regime_tributario\\\":[],\\\"situacao_especial\\\":\\\"\\\",\\\"opcao_pelo_simples\\\":true,\\\"situacao_cadastral\\\":2,\\\"data_opcao_pelo_mei\\\":null,\\\"data_exclusao_do_mei\\\":null,\\\"cnae_fiscal_descricao\\\":\\\"Comércio varejista especializado de equipamentos e suprimentos de informática\\\",\\\"codigo_municipio_ibge\\\":4119152,\\\"data_inicio_atividade\\\":\\\"2006-03-30\\\",\\\"data_situacao_especial\\\":null,\\\"data_opcao_pelo_simples\\\":\\\"2019-01-01\\\",\\\"data_situacao_cadastral\\\":\\\"2006-03-30\\\",\\\"nome_cidade_no_exterior\\\":\\\"\\\",\\\"codigo_natureza_juridica\\\":2062,\\\"data_exclusao_do_simples\\\":null,\\\"motivo_situacao_cadastral\\\":0,\\\"ente_federativo_responsavel\\\":\\\"\\\",\\\"identificador_matriz_filial\\\":1,\\\"qualificacao_do_responsavel\\\":49,\\\"descricao_situacao_cadastral\\\":\\\"ATIVA\\\",\\\"descricao_tipo_de_logradouro\\\":\\\"RODOVIA\\\",\\\"descricao_motivo_situacao_cadastral\\\":\\\"SEM MOTIVO\\\",\\\"descricao_identificador_matriz_filial\\\":\\\"MATRIZ\\\"}\"}','2026-08-14 15:37:05','2026-08-14 15:48:44','mini_erp_tenant_5','83320005','PR','RODOVIA DEPUTADO JOAO LEOPOLDO JACOMEL','11034','','PINEVILLE','4130335070','','PINHAIS','',0),(12,'96171456b4755d2a','Empresa Portavel Teste','Empresa Portavel Teste LTDA','12345678000199','empresa-portavel-teste',NULL,'ativo','{\"nome_fantasia\":\"Empresa Portavel Teste\",\"razao_social\":\"Empresa Portavel Teste LTDA\",\"cnpj\":\"12345678000199\",\"cep\":\"01001000\",\"uf\":\"SP\",\"municipio\":\"Sao Paulo\"}','2026-08-14 15:47:54','2026-08-14 19:27:11','mini_erp_tenant_12','01001000','SP','','','','','','','Sao Paulo','',0),(14,'92fd73f3a9554e0adf943f74805a525f','willyan info','willyan informatica','07924387000112','willyaninfo',NULL,'ativa',NULL,'2026-08-20 14:59:00','2026-08-20 16:07:29','mini_erp_tenant_14',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0);
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
INSERT INTO `transportadoras` VALUES (1,'TRANSLOVAC','TRANSLOVAC','1234567890001','231231231','rma@fagundez.com','4130124500','83324442','Avenida Maringa','1354','Bloco D; Unidade 7','Emiliano Perneta','pinhais','pr','PINHAIS','ativo',1);
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Administrador','admin@localhost','$2y$10$/I7F51Kv.cODDbeXDpvhgehHyv37nBPVoRcui48IrVDkl3Etn.X4y','admin','','ativo',0,NULL,NULL,'funcionario',NULL,1),(2,'willyan','martins.willyan20@gmail.com','$2y$10$fxprLqPbmg1737SrvWbJMuOzEVR5rZEVnSIM5xzVkNJsa4JiMq436','admin','','ativo',1,NULL,NULL,'funcionario',NULL,1),(3,'Gabriel','gblkhewk@gmail.com','$2y$10$6PAZY/2ulhUKC1oAh6RjzOms4gkfd705i9cpcvEocTvrLC4h4f9Me','admin','','ativo',0,'7b9939a3a1aba6786d1aace1',NULL,'funcionario',NULL,1),(4,'Administrador 1','admin1@localhost','$2y$10$cTtkHLDuSYtSqm.3TMjp..njN9jgOMAldkCmxXoQWc6TKcBJwn0t6','admin','','ativo',0,NULL,NULL,'funcionario',NULL,2),(5,'Administrador 2','admin2@localhost','$2y$10$Biu4e3M3YL0LvBAMkoJ45OFOwkflK95Sa4yrbkNitnsuratJ9l7yy','admin','','ativo',0,NULL,NULL,'funcionario',NULL,3),(6,'Administrador 3','admin3@localhost','$2y$10$ynXG/uwKI8022t15suMNv.KVBgzZW8vfY7vF3eqyn8WE4q6EO33x2','admin','','ativo',0,NULL,NULL,'funcionario',NULL,4),(7,'Empresa Portavel Teste','admin@empresa-portavel-teste','$2y$10$TdhkWIrHEsy/vwhzSAGVyOE6r8KTuQPOpqkOgtg/chion1pfQe.Ma','admin','','ativo',1,NULL,NULL,'funcionario',NULL,12),(8,'INFOCASE','admin@infocase','$2y$10$RbiBB5hEBDxpftwqjrseZuYOMD8TInaIcLm4TON2.DNVZtjTt6A/O','admin','','ativo',1,NULL,NULL,'funcionario',NULL,5),(9,'Willyan Martins','willyan.gits@gmail.com','$2y$10$mdtqQDi2V10cg7MdKJ3youa32BSYkXmTT1HvcG2DCTf8Qiwsb1.GG','admin','','ativo',1,NULL,NULL,'funcionario',14,14),(10,'willyan','willyan@infocase.com','$2y$10$ShaPdJtbDg1eXcW2.YMDIe7m428NTovqdwCazEn6l/NPjRJ6BtUcq','user','','ativo',1,NULL,NULL,'funcionario',5,5);
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
INSERT INTO `vendas` VALUES (1,1,'2026-08-01',299.90,'finalizada',1),(2,2,'2026-08-03',189.00,'finalizada',1),(3,3,'2026-08-13',6998.00,'finalizada',1),(4,10,'2026-08-13',899.90,'finalizada',1);
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

-- Dump completed on 2026-08-22 11:09:09

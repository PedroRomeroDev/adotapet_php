-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: adotapet
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `formulario_adocao`
--

DROP TABLE IF EXISTS `formulario_adocao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `formulario_adocao` (
  `id_usuario_fk` int NOT NULL,
  `id_pet_fk` int NOT NULL,
  `ic_teve_pet_form` tinyint(1) NOT NULL,
  `ic_outros_animais_form` tinyint(1) NOT NULL,
  `qt_horas_sozinho` smallint NOT NULL,
  `nm_tipo_moradia` varchar(30) NOT NULL,
  `ic_protecao` tinyint(1) NOT NULL,
  `ds_motivacao` text NOT NULL,
  `cd_status` varchar(15) NOT NULL DEFAULT 'PENDENTE',
  `ds_resposta_ong` text,
  `dt_envio` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dt_resposta` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_usuario_fk`,`id_pet_fk`),
  KEY `fk_formulario_pet` (`id_pet_fk`),
  CONSTRAINT `fk_formulario_pet` FOREIGN KEY (`id_pet_fk`) REFERENCES `pet` (`cd_pet`) ON DELETE CASCADE,
  CONSTRAINT `fk_formulario_usuario` FOREIGN KEY (`id_usuario_fk`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `formulario_adocao_chk_1` CHECK ((`qt_horas_sozinho` between 0 and 24)),
  CONSTRAINT `formulario_adocao_chk_2` CHECK ((`nm_tipo_moradia` in (_utf8mb4'CASA',_utf8mb4'APARTAMENTO',_utf8mb4'SITIO',_utf8mb4'OUTRO'))),
  CONSTRAINT `formulario_adocao_chk_3` CHECK ((`cd_status` in (_utf8mb4'PENDENTE',_utf8mb4'EM_ANALISE',_utf8mb4'APROVADO',_utf8mb4'RECUSADO',_utf8mb4'CANCELADO')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `formulario_adocao`
--

LOCK TABLES `formulario_adocao` WRITE;
/*!40000 ALTER TABLE `formulario_adocao` DISABLE KEYS */;
/*!40000 ALTER TABLE `formulario_adocao` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-13 12:40:31

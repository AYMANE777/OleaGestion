-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 17, 2025 at 11:58 AM
-- Server version: 9.1.0
-- PHP Version: 8.2.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gestion_meknassa`
--

-- --------------------------------------------------------

--
-- Table structure for table `affectation_versement`
--

DROP TABLE IF EXISTS `affectation_versement`;
CREATE TABLE IF NOT EXISTS `affectation_versement` (
  `id_affectation` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_versement` bigint UNSIGNED NOT NULL,
  `id_bon` bigint UNSIGNED NOT NULL,
  `montant_affecte` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_affectation`),
  KEY `fk_affectation_versement` (`id_versement`),
  KEY `fk_affectation_bon` (`id_bon`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `avance`
--

DROP TABLE IF EXISTS `avance`;
CREATE TABLE IF NOT EXISTS `avance` (
  `id_avance` int NOT NULL AUTO_INCREMENT,
  `montant` decimal(10,2) NOT NULL,
  `Motif` varchar(2000) NOT NULL,
  `date_ajout` datetime NOT NULL,
  `id_ouvrier` int NOT NULL,
  PRIMARY KEY (`id_avance`),
  KEY `id_ouvrier` (`id_ouvrier`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `avance`
--

INSERT INTO `avance` (`id_avance`, `montant`, `Motif`, `date_ajout`, `id_ouvrier`) VALUES
(1, 100.00, '', '2025-10-17 12:09:03', 1);

-- --------------------------------------------------------

--
-- Table structure for table `bidons`
--

DROP TABLE IF EXISTS `bidons`;
CREATE TABLE IF NOT EXISTS `bidons` (
  `id_bidon` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom_bidon` varchar(50) NOT NULL,
  `type_bidon` varchar(50) NOT NULL,
  `prix_bidon` decimal(5,2) NOT NULL,
  `quantite_bidon` bigint DEFAULT '0',
  `bidon_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_bidon`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `bidons`
--

INSERT INTO `bidons` (`id_bidon`, `nom_bidon`, `type_bidon`, `prix_bidon`, `quantite_bidon`, `bidon_created_at`) VALUES
(1, '5L', '5L', 10.00, 100, '2025-10-17 11:30:19');

-- --------------------------------------------------------

--
-- Table structure for table `bon_de_huile`
--

DROP TABLE IF EXISTS `bon_de_huile`;
CREATE TABLE IF NOT EXISTS `bon_de_huile` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint UNSIGNED NOT NULL,
  `quantite` decimal(10,2) NOT NULL,
  `date_bon` date NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `prix_net` decimal(10,2) NOT NULL,
  `provider` varchar(120) DEFAULT NULL,
  `mode_paiement_huile` enum('ESPECES','CARTE','VIREMENT','CHEQUE','AUTRE') DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_bon_client` (`client_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `bon_de_huile`
--



-- --------------------------------------------------------

--
-- Table structure for table `bon_fitor`
--

DROP TABLE IF EXISTS `bon_fitor`;
CREATE TABLE IF NOT EXISTS `bon_fitor` (
  `id_bon` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `quantite` decimal(12,3) NOT NULL,
  `prix_init` decimal(12,2) NOT NULL,
  `prix_net` decimal(12,2) NOT NULL,
  `provider` varchar(120) NOT NULL,
  `id_taratonage` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_bon`),
  KEY `fk_bonFitor_taratonage` (`id_taratonage`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `bon_fitor`
--



-- --------------------------------------------------------

--
-- Table structure for table `bon_rue`
--

DROP TABLE IF EXISTS `bon_rue`;
CREATE TABLE IF NOT EXISTS `bon_rue` (
  `id_bon_rue` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref_bon_huile` bigint UNSIGNED DEFAULT NULL,
  `ref_bon_bidon` bigint UNSIGNED DEFAULT NULL,
  `statut` enum('ACTIVE','DESACTIVE','ANNULE') NOT NULL DEFAULT 'ACTIVE',
  `random_daily_value` varchar(255) NOT NULL,
  `commentaire` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `duree` datetime DEFAULT NULL,
  PRIMARY KEY (`id_bon_rue`),
  KEY `ref_bon_bidon` (`ref_bon_bidon`),
  KEY `ref_bon_huile` (`ref_bon_huile`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `bon_rue`
--


-- --------------------------------------------------------

--
-- Table structure for table `bon_sortie_bidon`
--

DROP TABLE IF EXISTS `bon_sortie_bidon`;
CREATE TABLE IF NOT EXISTS `bon_sortie_bidon` (
  `id_bon_sortie_bidon` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_client` bigint UNSIGNED NOT NULL,
  `destination` varchar(255) DEFAULT NULL,
  `statut` enum('PAYE','NON PAYE') NOT NULL DEFAULT 'PAYE',
  `mode_paiement` enum('ESPECES','CARTE','VIREMENT','CHEQUE','AUTRE') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_bon_sortie_bidon`),
  KEY `id_client` (`id_client`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `bon_sortie_bidon`
--

-- --------------------------------------------------------

--
-- Table structure for table `bon_sortie_huile`
--

DROP TABLE IF EXISTS `bon_sortie_huile`;
CREATE TABLE IF NOT EXISTS `bon_sortie_huile` (
  `id_bon_sortie_huile` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_detail` bigint UNSIGNED NOT NULL,
  `destination` varchar(255) DEFAULT NULL,
  `statut` enum('ACTIVE','DESACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_bon_sortie_huile`),
  KEY `id_detail` (`id_detail`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `chauffeur`
--

DROP TABLE IF EXISTS `chauffeur`;
CREATE TABLE IF NOT EXISTS `chauffeur` (
  `id_Fchauffeur` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `image_Fchauffeur` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_patron` bigint UNSIGNED NOT NULL,
  PRIMARY KEY (`id_Fchauffeur`),
  KEY `fk_id_patron` (`id_patron`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `chauffeur`
--


-- --------------------------------------------------------

--
-- Table structure for table `client`
--

DROP TABLE IF EXISTS `client`;
CREATE TABLE IF NOT EXISTS `client` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `image_client` varchar(250) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `ville` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `client`
--


-- --------------------------------------------------------

--
-- Table structure for table `details_bon_bidon`
--

DROP TABLE IF EXISTS `details_bon_bidon`;
CREATE TABLE IF NOT EXISTS `details_bon_bidon` (
  `id_detail` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_bon` bigint UNSIGNED NOT NULL,
  `id_bidon` bigint UNSIGNED DEFAULT NULL,
  `quantite` bigint NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `quantite_bidon` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_detail`),
  KEY `id_bon` (`id_bon`),
  KEY `id_bidon` (`id_bidon`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `details_bon_bidon`
--

-- --------------------------------------------------------

--
-- Table structure for table `details_bon_huile`
--

DROP TABLE IF EXISTS `details_bon_huile`;
CREATE TABLE IF NOT EXISTS `details_bon_huile` (
  `id_detail` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_bon` bigint UNSIGNED NOT NULL,
  `id_bidon` bigint UNSIGNED DEFAULT NULL,
  `quantite_bidon` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_detail`),
  KEY `id_bon` (`id_bon`),
  KEY `id_bidon` (`id_bidon`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `details_bon_huile`
--


-- --------------------------------------------------------

--
-- Table structure for table `fournisseur`
--

DROP TABLE IF EXISTS `fournisseur`;
CREATE TABLE IF NOT EXISTS `fournisseur` (
  `id_fournisseur` int NOT NULL AUTO_INCREMENT,
  `nom_fournisseur` varchar(200) NOT NULL,
  `prenom_fournisseur` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `tele_fournisseur` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `adress_fournisseur` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `date_ajout` datetime NOT NULL,
  PRIMARY KEY (`id_fournisseur`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `fournisseur`
--


-- --------------------------------------------------------

--
-- Table structure for table `historique_bidon`
--

DROP TABLE IF EXISTS `historique_bidon`;
CREATE TABLE IF NOT EXISTS `historique_bidon` (
  `id_historique` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_bidon` bigint UNSIGNED NOT NULL,
  `date_action` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `type_action` enum('ajout','retrait','correction') NOT NULL,
  `quantite_changee` decimal(10,2) NOT NULL,
  `utilisateur` varchar(100) DEFAULT NULL,
  `commentaire` text,
  PRIMARY KEY (`id_historique`),
  KEY `id_bidon` (`id_bidon`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `historique_bidon`
--


-- --------------------------------------------------------

--
-- Table structure for table `image_machine`
--

DROP TABLE IF EXISTS `image_machine`;
CREATE TABLE IF NOT EXISTS `image_machine` (
  `id_image` int NOT NULL AUTO_INCREMENT,
  `date_ajout` datetime NOT NULL,
  `image` varchar(200) NOT NULL,
  `id_machine` int NOT NULL,
  PRIMARY KEY (`id_image`),
  KEY `id_machine` (`id_machine`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `image_piece`
--

DROP TABLE IF EXISTS `image_piece`;
CREATE TABLE IF NOT EXISTS `image_piece` (
  `id_image` int NOT NULL AUTO_INCREMENT,
  `date_ajout` datetime NOT NULL,
  `image` varchar(200) NOT NULL,
  `id_piece` int NOT NULL,
  PRIMARY KEY (`id_image`),
  KEY `id_piece` (`id_piece`)
) ENGINE=InnoDB AUTO_INCREMENT=190 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `image_piece`
--



-- --------------------------------------------------------

--
-- Table structure for table `machine`
--

DROP TABLE IF EXISTS `machine`;
CREATE TABLE IF NOT EXISTS `machine` (
  `id_machine` int NOT NULL AUTO_INCREMENT,
  `date_ajout` datetime NOT NULL,
  `ref_machine` varchar(200) NOT NULL,
  `nom_machine` varchar(200) NOT NULL,
  `description_machine` varchar(2000) NOT NULL,
  `id_fournisseur` int NOT NULL,
  PRIMARY KEY (`id_machine`),
  KEY `id_fournisseur` (`id_fournisseur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `ouvrier`
--

DROP TABLE IF EXISTS `ouvrier`;
CREATE TABLE IF NOT EXISTS `ouvrier` (
  `id_ouvrier` int NOT NULL AUTO_INCREMENT,
  `prenom` varchar(200) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `cin` varchar(2000) NOT NULL,
  `img_cin` varchar(2000) NOT NULL,
  `image` varchar(2000) NOT NULL,
  `tele` varchar(200) NOT NULL,
  `fonction` varchar(200) NOT NULL,
  `adress` varchar(2000) NOT NULL,
  `date_ajout` datetime NOT NULL,
  PRIMARY KEY (`id_ouvrier`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `ouvrier`
--


-- --------------------------------------------------------

--
-- Table structure for table `patron`
--

DROP TABLE IF EXISTS `patron`;
CREATE TABLE IF NOT EXISTS `patron` (
  `id_patron` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom_patron` varchar(100) NOT NULL,
  `prenom_patron` varchar(100) NOT NULL,
  `telephone_patron` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `image_Patron` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id_patron`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `patron`
--


-- --------------------------------------------------------

--
-- Table structure for table `piece`
--

DROP TABLE IF EXISTS `piece`;
CREATE TABLE IF NOT EXISTS `piece` (
  `id_piece` int NOT NULL AUTO_INCREMENT,
  `date_ajout` datetime NOT NULL,
  `ref_piece` varchar(200) NOT NULL,
  `nom_piece` varchar(200) NOT NULL,
  `description_piece` varchar(2000) NOT NULL,
  `type_piece` varchar(200) NOT NULL,
  `id_fournisseur` int NOT NULL,
  PRIMARY KEY (`id_piece`),
  KEY `id_fournisseur` (`id_fournisseur`)
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `piece`
--
create table user(
  id int not null auto_increment,
  name varchar(255) not null,
  email varchar(255) not null,
  password varchar(255) not null,
  created_at timestamp default current_timestamp,
  primary key(id)
);


-- --------------------------------------------------------

--
-- Table structure for table `reglement_fournisseur`
--

DROP TABLE IF EXISTS `reglement_fournisseur`;
CREATE TABLE IF NOT EXISTS `reglement_fournisseur` (
  `id_reglement` int NOT NULL AUTO_INCREMENT,
  `date_ajout` datetime NOT NULL,
  `date_paiement` datetime DEFAULT NULL,
  `ref_reglement` varchar(2000) NOT NULL,
  `id_fournisseur` int NOT NULL,
  `situation` varchar(2000) NOT NULL,
  `payment_mode` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `img_facture` varchar(2000) NOT NULL,
  `img_justification` varchar(200) DEFAULT NULL,
  `prix_total` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_reglement`),
  KEY `id_fournisseur` (`id_fournisseur`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `reglement_fournisseur`
--


-- --------------------------------------------------------

--
-- Table structure for table `statut_machine`
--

DROP TABLE IF EXISTS `statut_machine`;
CREATE TABLE IF NOT EXISTS `statut_machine` (
  `id_statut` int NOT NULL AUTO_INCREMENT,
  `date_ajout` datetime NOT NULL,
  `statut` varchar(2000) NOT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `id_machine` int NOT NULL,
  PRIMARY KEY (`id_statut`),
  KEY `id_machine` (`id_machine`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `statut_piece`
--

DROP TABLE IF EXISTS `statut_piece`;
CREATE TABLE IF NOT EXISTS `statut_piece` (
  `id_statut` int NOT NULL AUTO_INCREMENT,
  `date_ajout` datetime NOT NULL,
  `statut` varchar(200) NOT NULL,
  `description` varchar(2000) DEFAULT NULL,
  `id_piece` int NOT NULL,
  `quantite` int NOT NULL,
  `id_machine` int DEFAULT NULL,
  PRIMARY KEY (`id_statut`),
  KEY `id_piece` (`id_piece`),
  KEY `id_machine` (`id_machine`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `stock_piece`
--

DROP TABLE IF EXISTS `stock_piece`;
CREATE TABLE IF NOT EXISTS `stock_piece` (
  `id_stock` int NOT NULL AUTO_INCREMENT,
  `date_ajout` datetime NOT NULL,
  `quantite_piece` int NOT NULL,
  `id_piece` int NOT NULL,
  `description` varchar(2000) NOT NULL,
  PRIMARY KEY (`id_stock`),
  KEY `id_piece` (`id_piece`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `stock_piece`
--



-- --------------------------------------------------------

--
-- Table structure for table `taratonage`
--

DROP TABLE IF EXISTS `taratonage`;
CREATE TABLE IF NOT EXISTS `taratonage` (
  `id_taratonage` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `poid_brut` decimal(10,2) NOT NULL,
  `poid_tarat` decimal(10,2) NOT NULL,
  `poid_net` decimal(10,2) NOT NULL,
  `note` varchar(250) DEFAULT NULL,
  `id_vehicule_Fitor` bigint UNSIGNED NOT NULL,
  `taratonage_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_taratonage`),
  KEY `fk_vehicule_tarat_Fchauffeur` (`id_vehicule_Fitor`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `taratonage`
--


-- --------------------------------------------------------

--
-- Table structure for table `taratonage_olive`
--

DROP TABLE IF EXISTS `taratonage_olive`;
CREATE TABLE IF NOT EXISTS `taratonage_olive` (
  `id_taratonage` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `poid_brut` decimal(10,2) NOT NULL,
  `poid_tarat` decimal(10,2) NOT NULL,
  `poid_net` decimal(10,2) NOT NULL,
  `note` varchar(250) DEFAULT NULL,
  `id_vehicule_trans` bigint UNSIGNED NOT NULL,
  `taratonage_olive_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_taratonage`),
  KEY `fk_vehicule_transporteur` (`id_vehicule_trans`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `taratonage_olive`
--



-- --------------------------------------------------------

--
-- Table structure for table `transporteur`
--

DROP TABLE IF EXISTS `transporteur`;
CREATE TABLE IF NOT EXISTS `transporteur` (
  `id_transporteur` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom_trans` varchar(100) NOT NULL,
  `prenom_trans` varchar(100) NOT NULL,
  `telephone_trans` varchar(20) NOT NULL,
  `image_trans` varchar(250) DEFAULT NULL,
  `Adresse_Source` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qr_path` varchar(2000) NOT NULL,
  PRIMARY KEY (`id_transporteur`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `transporteur`
--


-- --------------------------------------------------------

--
-- Table structure for table `vehicule`
--

DROP TABLE IF EXISTS `vehicule`;
CREATE TABLE IF NOT EXISTS `vehicule` (
  `id_vehicule` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom_vehicule` varchar(100) NOT NULL,
  `type` varchar(100) NOT NULL,
  `matricule` varchar(20) NOT NULL,
  `image_vehicule` varchar(250) DEFAULT NULL,
  `assurance` varchar(100) DEFAULT NULL,
  `date_expiration` date DEFAULT NULL,
  `couleur` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `id_Fchauffeur` bigint UNSIGNED NOT NULL,
  PRIMARY KEY (`id_vehicule`),
  UNIQUE KEY `matricule` (`matricule`),
  KEY `fk_id_Fchauffeur` (`id_Fchauffeur`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `vehicule`
--


-- --------------------------------------------------------

--
-- Table structure for table `vehiculetrans`
--

DROP TABLE IF EXISTS `vehiculetrans`;
CREATE TABLE IF NOT EXISTS `vehiculetrans` (
  `id_vehicule_trans` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom_vehi_trans` varchar(100) NOT NULL,
  `type` varchar(100) NOT NULL,
  `matricule` varchar(20) NOT NULL,
  `image_vehicule_trans` varchar(250) DEFAULT NULL,
  `assurance` varchar(100) DEFAULT NULL,
  `date_expiration` date DEFAULT NULL,
  `couleur` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `id_transporteur` bigint UNSIGNED NOT NULL,
  PRIMARY KEY (`id_vehicule_trans`),
  UNIQUE KEY `matricule` (`matricule`),
  KEY `fk_id_Transporteur` (`id_transporteur`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `vehiculetrans`
--

-- --------------------------------------------------------

--
-- Table structure for table `versement`
--

DROP TABLE IF EXISTS `versement`;
CREATE TABLE IF NOT EXISTS `versement` (
  `id_versement` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `montant` decimal(10,2) NOT NULL,
  `date_versement` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mode_paiement` enum('espece','virement','cheque','autre') NOT NULL,
  `note` varchar(250) DEFAULT NULL,
  `id_Fchauffeur` bigint UNSIGNED NOT NULL,
  `image_versement` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_versement`),
  KEY `fk_versement_Fchauffeur` (`id_Fchauffeur`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `versement`
--


--
-- Constraints for dumped tables
--

--
-- Constraints for table `avance`
--
ALTER TABLE `avance`
  ADD CONSTRAINT `avance_ibfk_1` FOREIGN KEY (`id_ouvrier`) REFERENCES `ouvrier` (`id_ouvrier`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `image_machine`
--
ALTER TABLE `image_machine`
  ADD CONSTRAINT `image_machine_ibfk_1` FOREIGN KEY (`id_machine`) REFERENCES `machine` (`id_machine`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `image_piece`
--
ALTER TABLE `image_piece`
  ADD CONSTRAINT `image_piece_ibfk_1` FOREIGN KEY (`id_piece`) REFERENCES `piece` (`id_piece`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `machine`
--
ALTER TABLE `machine`
  ADD CONSTRAINT `machine_ibfk_1` FOREIGN KEY (`id_fournisseur`) REFERENCES `fournisseur` (`id_fournisseur`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `piece`
--
ALTER TABLE `piece`
  ADD CONSTRAINT `piece_ibfk_2` FOREIGN KEY (`id_fournisseur`) REFERENCES `fournisseur` (`id_fournisseur`) ON DELETE RESTRICT ON UPDATE RESTRICT;

-- --------------------------------------------------------
--
-- Table structure for table `paiement_reglement`
--

DROP TABLE IF EXISTS `paiement_reglement`;
CREATE TABLE IF NOT EXISTS `paiement_reglement` (
  `id_paiement`       INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `id_reglement`      INT           NOT NULL,
  `montant`           DECIMAL(10,2) NOT NULL,
  `payment_mode`      VARCHAR(100)  DEFAULT NULL,
  `img_justification` VARCHAR(2000) DEFAULT NULL,
  `date_ajout`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_paiement`),
  KEY `fk_pr_reglement` (`id_reglement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
--
-- Table structure for table `piece_reglement`
--

DROP TABLE IF EXISTS `piece_reglement`;
CREATE TABLE IF NOT EXISTS `piece_reglement` (
  `id_piece_reg`  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `id_reglement`  INT           NOT NULL,
  `nom`           VARCHAR(255)  NOT NULL,
  `prix`          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `quantite`      INT           NOT NULL DEFAULT 1,
  `date_ajout`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_piece_reg`),
  KEY `fk_piecer_reglement` (`id_reglement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
--
-- Table structure for table `image_reglement`
--

DROP TABLE IF EXISTS `image_reglement`;
CREATE TABLE IF NOT EXISTS `image_reglement` (
  `id_image_reg`  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `id_reglement`  INT           NOT NULL,
  `img_facture`   VARCHAR(2000) NOT NULL,
  `date_ajout`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_image_reg`),
  KEY `fk_imgr_reglement` (`id_reglement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Constraints for table `piece_reglement`
--
ALTER TABLE `piece_reglement`
  ADD CONSTRAINT `piece_reglement_ibfk_1` FOREIGN KEY (`id_reglement`) REFERENCES `reglement_fournisseur` (`id_reglement`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reglement_fournisseur`
--
ALTER TABLE `reglement_fournisseur`
  ADD CONSTRAINT `reglement_fournisseur_ibfk_1` FOREIGN KEY (`id_fournisseur`) REFERENCES `fournisseur` (`id_fournisseur`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `statut_machine`
--
ALTER TABLE `statut_machine`
  ADD CONSTRAINT `statut_machine_ibfk_1` FOREIGN KEY (`id_machine`) REFERENCES `machine` (`id_machine`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `statut_piece`
--
ALTER TABLE `statut_piece`
  ADD CONSTRAINT `statut_piece_ibfk_1` FOREIGN KEY (`id_piece`) REFERENCES `piece` (`id_piece`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `stock_piece`
--
ALTER TABLE `stock_piece`
  ADD CONSTRAINT `stock_piece_ibfk_1` FOREIGN KEY (`id_piece`) REFERENCES `piece` (`id_piece`) ON DELETE RESTRICT ON UPDATE RESTRICT;

DELIMITER $$
--
-- Events
--
DROP EVENT IF EXISTS `ev_desactive_expired_bon_rue`$$
CREATE DEFINER=`root`@`localhost` EVENT `ev_desactive_expired_bon_rue` ON SCHEDULE EVERY 1 HOUR STARTS '2025-10-15 10:35:15' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE Bon_Rue
  SET statut = 'DESACTIVE'
  WHERE statut = 'ACTIVE'
    AND duree <= NOW()$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


--
-- Table structure for table `client Oublier`
--

DROP TABLE IF EXISTS `ForgeterClient`;
CREATE TABLE IF NOT EXISTS `ForgeterClient` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref_bon` varchar(100) NOT NULL,
  `qte` decimal(10,2) NOT NULL,
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `cin` VARCHAR(100) NOT NULL,
  `telephone` VARCHAR(100) NOT NULL,
  `preuve` varchar(250) DEFAULT NULL,
  `provider` varchar(100) NOT NULL,
  `note` VARCHAR(250) NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;



CREATE TABLE IF NOT EXISTS `ListdesBonOublier` (
  `idbonOublier` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_client` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idbonOublier`),
  FOREIGN KEY (`id_client`) REFERENCES `ForgeterClient`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb2
-- http://www.phpmyadmin.net
--
-- Client: localhost
-- Généré le: Dim 10 Novembre 2013 à 15:51
-- Version du serveur: 5.5.31
-- Version de PHP: 5.4.4-14+deb7u5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `beedb`
--

-- --------------------------------------------------------

--
-- Structure de la table `debug_log`
--

CREATE TABLE IF NOT EXISTS `debug_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `text` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `last_view`
--
CREATE TABLE IF NOT EXISTS `last_view` (
`last_view` datetime
,`object_id` int(10) unsigned
,`modem_id` varchar(20)
);
-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE IF NOT EXISTS `messages` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int(10) unsigned DEFAULT NULL,
  `rx_timestamp` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `payload` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `station_id` int(10) unsigned DEFAULT NULL,
  `station_lvl` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  UNIQUE KEY `msg_unique` (`object_id`,`rx_timestamp`),
  KEY `object_id` (`object_id`),
  KEY `rx_timestamp` (`rx_timestamp`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `objects`
--

CREATE TABLE IF NOT EXISTS `objects` (
  `object_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `modem_id` int(10) unsigned DEFAULT NULL,
  `modem_key` int(10) unsigned DEFAULT NULL,
  `object_name` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`object_id`),
  UNIQUE KEY `modem_id` (`modem_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sig_index`
--

CREATE TABLE IF NOT EXISTS `sig_index` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int(10) unsigned DEFAULT NULL,
  `var_id` int(10) unsigned DEFAULT NULL,
  `rx_timestamp` bigint(20) unsigned DEFAULT NULL,
  `index_p` int(10) unsigned DEFAULT NULL,
  `index_n` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_var` (`object_id`,`var_id`,`rx_timestamp`),
  KEY `object_id` (`object_id`),
  KEY `rx_timestamp` (`rx_timestamp`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sig_stat`
--

CREATE TABLE IF NOT EXISTS `sig_stat` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int(10) unsigned DEFAULT NULL,
  `var_id` int(10) unsigned DEFAULT NULL,
  `rx_timestamp` int(10) unsigned DEFAULT NULL,
  `var_min` int(11) DEFAULT NULL,
  `var_avg` int(11) DEFAULT NULL,
  `var_max` int(11) DEFAULT NULL,
  `var_inst` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `object_id` (`object_id`,`var_id`,`rx_timestamp`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `vars`
--

CREATE TABLE IF NOT EXISTS `vars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `var_name` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `var` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la vue `last_view`
--
DROP TABLE IF EXISTS `last_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `last_view` AS select from_unixtime((max(`messages`.`rx_timestamp`) / 1000)) AS `last_view`,`messages`.`object_id` AS `object_id`,hex(`objects`.`modem_id`) AS `modem_id` from (`messages` join `objects`) where (`objects`.`object_id` = `messages`.`object_id`) group by `messages`.`object_id` limit 100;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

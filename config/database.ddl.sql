# noinspection SqlNoDataSourceInspectionForFile

-- --------------------------------------------------------
-- Host:                         localhost
-- Server version:               5.7.17-log - MySQL Community Server (GPL)
-- Server OS:                    Win64
-- HeidiSQL Version:             9.4.0.5125
-- --------------------------------------------------------

-- Dumping database structure for livetree
CREATE DATABASE IF NOT EXISTS `livetree` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `livetree`;

-- Dumping structure for table livetree.event
CREATE TABLE IF NOT EXISTS `event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(45) NOT NULL,
  `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sessionid` varchar(255) DEFAULT NULL,
  `metadata` text,
  `ip` varchar(45) DEFAULT NULL,
  `update_tree` binary(1) NOT NULL,
  `factory_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lookup` (`datetime`,`update_tree`,`sessionid`),
  KEY `fk_event_factory` (`factory_id`),
  CONSTRAINT `fk_event_factory` FOREIGN KEY (`factory_id`) REFERENCES `factory` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='human interaction events';

-- Dumping structure for table livetree.factory
CREATE TABLE IF NOT EXISTS `factory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) DEFAULT NULL,
  `lower_bound` int(11) DEFAULT '0',
  `upper_bound` int(11) DEFAULT '100',
  `item_count` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 PACK_KEYS=0;

-- Dumping structure for table livetree.leaf
CREATE TABLE IF NOT EXISTS `leaf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `factory_id` int(11) NOT NULL,
  `value` int(11) DEFAULT NULL,
  `creation_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `factory_id` (`factory_id`),
  CONSTRAINT `leaf_fk1` FOREIGN KEY (`factory_id`) REFERENCES `factory` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 PACK_KEYS=0;

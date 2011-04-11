/*
SQLyog Community v9.0 RC
MySQL - 5.1.54-1ubuntu4 : Database - wp
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`wp` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `wp`;

/*Table structure for table `wp_wrg_order` */

DROP TABLE IF EXISTS `wp_wrg_order`;

CREATE TABLE `wp_wrg_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dtcreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(30) NOT NULL DEFAULT 'order',
  `dtlastupdated` datetime NOT NULL,
  `items_price` int(11) NOT NULL,
  `shipping_price` int(11) NOT NULL,
  `dtpayment` datetime DEFAULT NULL,
  `dtdelivery` datetime DEFAULT NULL,
  `delivery_number` varchar(100) DEFAULT NULL,
  `shipping_weight` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=latin1;

/*Table structure for table `wp_wrg_order_items` */

DROP TABLE IF EXISTS `wp_wrg_order_items`;

CREATE TABLE `wp_wrg_order_items` (
  `order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `name` varchar(512) NOT NULL,
  `quantity` int(11) NOT NULL,
  `weight` float NOT NULL DEFAULT '0',
  `price` float NOT NULL DEFAULT '0',
  KEY `idx_wrg_order_items` (`order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `wp_wrg_order_shipping` */

DROP TABLE IF EXISTS `wp_wrg_order_shipping`;

CREATE TABLE `wp_wrg_order_shipping` (
  `order_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile_phone` int(11) DEFAULT NULL,
  `phone` int(11) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `additional_info` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

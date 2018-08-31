/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50505
Source Host           : localhost:3306
Source Database       : dolibarr502

Target Server Type    : MYSQL
Target Server Version : 50505
File Encoding         : 65001

Date: 2017-06-19 00:03:39
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for llx_descuentos_presupuestos
-- ----------------------------
DROP TABLE IF EXISTS `llx_descuentos_presupuestos`;
CREATE TABLE `llx_descuentos_presupuestos` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_descuento` int(11) DEFAULT NULL,
  `fk_presupuesto` int(11) DEFAULT NULL,
  `date_create` datetime DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

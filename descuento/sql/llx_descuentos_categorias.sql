CREATE TABLE `llx_descuentos_categorias` (
	`rowid` int(11) NOT NULL AUTO_INCREMENT,
	`fk_soc` int(11) DEFAULT NULL,
	`fk_categorie` int(11) DEFAULT NULL,
	`type` int(11) DEFAULT NULL,
	`value` int(11) DEFAULT NULL,
	`date_start` DATE DEFAULT NULL,
	`date_end` DATE DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);


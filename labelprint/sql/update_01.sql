ALTER TABLE llx_labelprint ADD price_level INT NULL DEFAULT NULL;
ALTER TABLE llx_labelprint ADD typLabel SMALLINT NOT NULL DEFAULT 0;
ALTER TABLE llx_labelprint CHANGE COLUMN fk_product fk_object INTEGER;
ALTER TABLE llx_labelprint ADD  batch varchar(30) DEFAULT NULL;
ALTER TABLE llx_pos_ticket ADD fk_place integer DEFAULT 0 AFTER fk_soc;
ALTER TABLE llx_pos_ticket CHANGE fk_place fk_place INT( 11 ) NULL;  
ALTER TABLE llx_pos_ticketdet ADD note TEXT NULL; 
ALTER TABLE llx_pos_cash ADD barcode TINYINT NOT NULL DEFAULT '0' AFTER tactil; 
ALTER TABLE llx_pos_ticketdet ADD localtax1_type INT NULL AFTER localtax1_tx;
ALTER TABLE llx_pos_ticketdet ADD localtax2_type INT NULL AFTER localtax2_tx;  
CREATE TABLE llx_pos_facture(rowid integer AUTO_INCREMENT PRIMARY KEY, fk_cash integer NOT NULL; fk_facture integer NOT NULL);
ALTER TABLE llx_pos_facture ADD fk_control_cash INT NULL;
ALTER TABLE llx_pos_facture ADD fk_place INT NULL;
ALTER TABLE llx_pos_facture ADD UNIQUE INDEX idx_facture_uk_facnumber (fk_facture);
ALTER TABLE llx_pos_cash ADD fk_modepaybank_extra INT NULL;
ALTER TABLE llx_pos_cash ADD fk_paybank_extra INT NULL;
ALTER TABLE llx_pos_control_cash ADD  ref varchar(30) NOT NULL;
ALTER TABLE llx_pos_facture ADD customer_pay double(24,8) DEFAULT 0;
ALTER TABLE llx_pos_ticket CHANGE fk_cash fk_cash integer NOT NULL;
ALTER TABLE llx_pos_cash ADD printer_name varchar(30) NULL;

-- UPDATE DOLIBARR 3.9-4.0 --
ALTER TABLE llx_pos_ticket ADD multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_pos_ticket ADD multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_pos_ticket ADD multicurrency_total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_pos_ticketdet ADD multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_pos_ticketdet ADD multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_pos_ticketdet ADD multicurrency_total_ttc double(24,8) DEFAULT 0;



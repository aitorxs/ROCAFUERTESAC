-- ===================================================================
-- Copyright (C) 2011 Juanjo Menent <jmenent@2byte.es>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id: llx_pos_cash.sql,v 1.4 2011-08-22 10:34:40 jmenent Exp $
-- ===================================================================

create table llx_pos_cash
(
  rowid                INTEGER                    AUTO_INCREMENT PRIMARY KEY,
  entity               INTEGER DEFAULT 1 NOT NULL,
  code                 VARCHAR(3),
  name                 VARCHAR(30),
  tactil               TINYINT           NOT NULL DEFAULT 0,
  barcode              TINYINT           NOT NULL DEFAULT 0,
  fk_paycash           INTEGER,
  fk_modepaycash       INTEGER,
  fk_paybank           INTEGER,
  fk_modepaybank       INTEGER,
  fk_modepaybank_extra INTEGER           NULL,
  fk_paybank_extra     INTEGER           NULL,
  fk_warehouse         INTEGER,
  fk_device            INTEGER,
  printer_name         VARCHAR(30)       NULL,
  fk_soc               INTEGER,
  is_used              TINYINT                    DEFAULT 0,
  fk_user_u            INTEGER,
  fk_user_c            INTEGER,
  fk_user_m            INTEGER,
  datec                DATETIME,
  datea                DATETIME,
  is_closed            TINYINT                    DEFAULT 0
)ENGINE=innodb;

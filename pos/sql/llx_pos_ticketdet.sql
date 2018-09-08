-- ===================================================================
-- Copyright (C) 2011      Juanjo Menent        <jmenent@2byte.es>
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
-- $Id: llx_pos_ticketdet.sql,v 1.1 2011-08-04 16:33:26 jmenent Exp $
-- ===================================================================

create table llx_pos_ticketdet
(
  rowid                   INTEGER       AUTO_INCREMENT PRIMARY KEY,
  fk_ticket               INTEGER           NOT NULL,
  fk_parent_line          INTEGER           NULL,
  fk_product              INTEGER           NULL,
  description             TEXT,
  tva_tx                  DOUBLE(6, 3),
  localtax1_tx            DOUBLE(6, 3)  DEFAULT 0,
  localtax2_tx            DOUBLE(6, 3)  DEFAULT 0,
  qty                     REAL,
  remise_percent          REAL          DEFAULT 0,
  remise                  REAL          DEFAULT 0,
  fk_remise_except        INTEGER           NULL,
  subprice                DOUBLE(24, 8),
  price                   DOUBLE(24, 8),
  total_ht                DOUBLE(24, 8),
  total_tva               DOUBLE(24, 8),
  total_localtax1         DOUBLE(24, 8) DEFAULT 0,
  total_localtax2         DOUBLE(24, 8) DEFAULT 0,
  total_ttc               DOUBLE(24, 8),
  product_type            INTEGER       DEFAULT 0,
  date_start              DATETIME      DEFAULT NULL,
  date_end                DATETIME      DEFAULT NULL,
  info_bits               INTEGER       DEFAULT 0,
  fk_code_ventilation     INTEGER DEFAULT 0 NOT NULL,
  fk_export_compta        INTEGER DEFAULT 0 NOT NULL,
  rang                    INTEGER       DEFAULT 0,
  import_key              VARCHAR(14),
  note                    TEXT              NULL,
  multicurrency_total_ht  DOUBLE(24, 8) DEFAULT 0,
  multicurrency_total_tva DOUBLE(24, 8) DEFAULT 0,
  multicurrency_total_ttc DOUBLE(24, 8) DEFAULT 0
)ENGINE=innodb;


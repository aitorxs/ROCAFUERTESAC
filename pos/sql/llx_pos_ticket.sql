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
-- $Id: llx_pos_ticket.sql,v 1.1 2011-08-04 16:33:26 jmenent Exp $
-- ===================================================================


create table llx_pos_ticket
(
  rowid                   INTEGER       AUTO_INCREMENT PRIMARY KEY,

  ticketnumber            VARCHAR(30)         NOT NULL,
  type                    INTEGER,
  entity                  INTEGER DEFAULT 1   NOT NULL,

  fk_cash                 INTEGER             NOT NULL,
  fk_soc                  INTEGER             NOT NULL,
  fk_place                INTEGER       DEFAULT 0,
  date_creation           DATETIME,
  date_ticket             DATE,
  date_closed             DATETIME,
  tms                     TIMESTAMP,
  paye                    SMALLINT DEFAULT 0  NOT NULL,
  remise_percent          REAL          DEFAULT 0,
  remise_absolute         REAL          DEFAULT 0,
  remise                  REAL          DEFAULT 0,

  customer_pay            DOUBLE(24, 8) DEFAULT 0,
  difpayment              DOUBLE(24, 8) DEFAULT 0,

  tva                     DOUBLE(24, 8) DEFAULT 0,
  localtax1               DOUBLE(24, 8) DEFAULT 0,
  localtax2               DOUBLE(24, 8) DEFAULT 0,
  total_ht                DOUBLE(24, 8) DEFAULT 0,
  total_ttc               DOUBLE(24, 8) DEFAULT 0,

  fk_statut               SMALLINT DEFAULT 0  NOT NULL,

  fk_user_author          INTEGER,
  fk_user_close           INTEGER,

  fk_facture              INTEGER,
  fk_ticket_source        INTEGER,

  fk_mode_reglement       INTEGER,

  fk_control              INTEGER,

  note                    TEXT,
  note_public             TEXT,
  model_pdf               VARCHAR(255),
  import_key              VARCHAR(14),
  multicurrency_total_ht  DOUBLE(24, 8) DEFAULT 0,
  multicurrency_total_tva DOUBLE(24, 8) DEFAULT 0,
  multicurrency_total_ttc DOUBLE(24, 8) DEFAULT 0

)ENGINE=innodb;

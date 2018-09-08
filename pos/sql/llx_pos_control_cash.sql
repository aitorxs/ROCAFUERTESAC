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
-- $Id: llx_pos_control_cash.sql,v 1.4 2011-08-22 10:34:40 jmenent Exp $
-- ===================================================================

create table llx_pos_control_cash
(
  rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
  ref             VARCHAR(30)       NOT NULL,
  entity          INTEGER DEFAULT 1 NOT NULL,
  fk_cash         INTEGER,
  fk_user         INTEGER,
  date_c          DATETIME,
  type_control    TINYINT DEFAULT 0,
  amount_teor     DOUBLE(24, 8),
  amount_real     DOUBLE(24, 8),
  amount_diff     DOUBLE(24, 8),
  amount_mov_out  DOUBLE(24, 8),
  amount_mov_int  DOUBLE(24, 8),
  amount_next_day DOUBLE(24, 8),
  comment         TEXT
)ENGINE=innodb;

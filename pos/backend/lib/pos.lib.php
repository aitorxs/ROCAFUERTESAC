<?php
/* Copyright (C) 2012      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014-2017 Ferran Marcet        <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

function posadmin_prepare_head()
{
    global $langs;
    $langs->load("pos@pos");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath('/pos/admin/pos.php', 1);
    $head[$h][1] = $langs->trans("POSSetup");
    $head[$h][2] = 'configuration';
    $h++;

    return $head;
}
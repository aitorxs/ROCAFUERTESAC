<?php
/* Copyright (C) 2010-2011 	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2010		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011      	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2013-2015 	Ferran Marcet		<fmarcet@2byte.es>
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
 * or see http://www.gnu.org/
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function facturaeadmin_prepare_head()
{
	global $langs, $conf, $user;
	$langs->load("withdrawals");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/facturae/admin/facturae.php',1);
	$head[$h][1] = $langs->trans("FacturaeSetup");
	$head[$h][2] = 'configuration';
	$h++;
	
	$head[$h][0] = dol_buildpath('/facturae/admin/payments.php',1);
	$head[$h][1] = $langs->trans("FacturaePayments");
	$head[$h][2] = 'payments';
	$h++;

	return $head;
}

function getCountryIso($searchkey)
{
	global $db,$langs;

	// Check parameters
	if (version_compare(DOL_VERSION, 3.7) >= 0){
		$table_name = "c_country";	
	}
	else{
		$table_name = "c_pays";
	}
		
	$sql = "SELECT code_iso FROM ".MAIN_DB_PREFIX.$table_name;
	$sql.= " WHERE code='".$db->escape($searchkey)."'";
	
	dol_syslog("Facturae.lib::getCountry", LOG_DEBUG);
	$resql=$db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		if ($obj)
		{
			return $obj->code_iso;
		}
		else
		{
			return 'NotDefined';
		}
		$db->free($resql);
	}
	else dol_print_error($db,'');
	return 'Error';
	
}
?>
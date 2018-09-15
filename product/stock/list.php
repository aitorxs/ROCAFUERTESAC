<?php
/* Copyright (C) 2001-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2015       Juanjo Menent           <jmenent@2byte.es>
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

/**
 *      \file       htdocs/product/stock/list.php
 *      \ingroup    stock
 *      \brief      Page with warehouse and stock value
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

// Load translation files required by the page
$langs->load("stocks");

// Security check
$result=restrictedArea($user,'stock');

$sall=trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$search_ref=GETPOST("sref","alpha")?GETPOST("sref","alpha"):GETPOST("search_ref","alpha");
$search_label=GETPOST("snom","alpha")?GETPOST("snom","alpha"):GETPOST("search_label","alpha");
$search_status=GETPOST("search_status","int");

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield");
$sortorder = GETPOST("sortorder");
if (! $sortfield) $sortfield="e.ref";
if (! $sortorder) $sortorder="ASC";
$page = GETPOST("page");
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;

$year = strftime("%Y",time());

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'e.ref'=>"Ref",
    'e.lieu'=>"LocationSummary",
    'e.description'=>"Description",
    'e.address'=>"Address",
    'e.zip'=>'Zip',
    'e.town'=>'Town',
);


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // Both test are required to be compatible with all browsers
{
    $search_ref="";
    $sall="";
    $search_label="";
    $search_status="";
    $search_array_options=array();
}


/*
 *	View
 */

$form=new Form($db);
$warehouse=new Entrepot($db);

$sql = "SELECT e.rowid, e.ref, e.fk_pays, e.fk_parent,";
$sql.= "  ps.reel as stockqty";
$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps ON e.rowid = ps.fk_entrepot";
$sql.= " and ps.fk_product =2";
$sql.= " GROUP BY e.rowid";
$result = $db->query($sql);

if ($result)
{
	$num = $db->num_rows($result);

	$i = 0;
	if ($num)
	{

		while ($i < min($num,$limit))
		{
			$objp = $db->fetch_object($result);
            // Stock qty
            print '<td align="right">'.price2num($objp->stockqty,5).'</td>';
            $i++;
		}


	}


}


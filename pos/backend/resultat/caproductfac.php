<?php
/* Copyright (C) 2013       Antoine Iauch        	<aiauch@gpcsolutions.fr>
 * Copyright (C) 2013       Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2014-2017  Ferran Marcet  		<fmarcet@2byte.es>
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
 *       \file        htdocs/compta/stats/cabyprodserv.php
 *       \brief       Page reporting TO by Products & Services
 */

$res=@include("../../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../../main.inc.php");                // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

global $langs,$user,$conf,$db,$bc;

$langs->load("products");
$langs->load("categories");
$langs->load("errors");

// Security pack (data & check)
$socid = GETPOST('socid','int');

// Security check
if ($user->socid > 0) $socid = $user->socid;
if (!$user->rights->pos->stats)
accessforbidden();

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta = $conf->global->COMPTA_MODE;
if (GETPOST("modecompta")) $modecompta=GETPOST("modecompta");

$sortorder=GETPOST('sortorder','string');
$sortfield=GETPOST('sortfield','string');
if (! $sortorder) $sortorder="asc";
if (! $sortfield) $sortfield="name";

// Category
$selected_cat = (int) GETPOST('search_categ', 'int');
$subcat = false;
if (GETPOST('subcat', 'alpha') === 'yes') {
    $subcat = true;
}

// Date range
$year=GETPOST("year");
$month=GETPOST("month");
$date_startyear = GETPOST("date_startyear");
$date_startmonth = GETPOST("date_startmonth");
$date_startday = GETPOST("date_startday");
$date_endyear = GETPOST("date_endyear");
$date_endmonth = GETPOST("date_endmonth");
$date_endday = GETPOST("date_endday");
if (empty($year))
{
	$year_current = strftime("%Y",dol_now());
	$month_current = strftime("%m",dol_now());
	$year_start = $year_current;
} else {
	$year_current = $year;
	$month_current = strftime("%m",dol_now());
	$year_start = $year;
}
$date_start=dol_mktime(0,0,0,GETPOST("date_startmonth"),GETPOST("date_startday"),GETPOST("date_startyear"));
$date_end=dol_mktime(23,59,59,GETPOST("date_endmonth"),GETPOST("date_endday"),GETPOST("date_endyear"));
// Quarter
if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$date_start=dol_get_first_day($year_current,$month_current,false);
	$date_end=dol_get_last_day($year_current,$month_current,false);
}

$commonparams=array();
$commonparams['sortorder'] = $sortorder;
$commonparams['sortfield'] = $sortfield;

$headerparams = array();
$headerparams['date_startyear'] = $date_startyear;
$headerparams['date_startmonth'] = $date_startmonth;
$headerparams['date_startday'] = $date_startday;
$headerparams['date_endyear'] = $date_endyear;
$headerparams['date_endmonth'] = $date_endmonth;
$headerparams['date_endday'] = $date_endday;
$headerparams['q'] = $q;

$tableparams = array();
$tableparams['search_categ'] = $selected_cat;
$tableparams['subcat'] = ($subcat === true)?'yes':'';

// Adding common parameters
$allparams = array_merge($commonparams, $headerparams, $tableparams);
$headerparams = array_merge($commonparams, $headerparams);
$tableparams = array_merge($commonparams, $tableparams);

foreach($allparams as $key => $value) {
    $paramslink .= '&' . $key . '=' . $value;
}
/*
 * View
 */
llxHeader();
$form=new Form($db);
$formother = new FormOther($db);

// Show report header
$nom=$langs->trans("SalesTurnover").', '.$langs->trans("ByProductsAndServices");

$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
$description=$langs->trans("RulesCADue");
$builddate=time();

report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink);


// SQL request
$catotal=0;


$sql = "SELECT DISTINCT p.rowid as rowid, p.ref as ref, p.label as label,";
$sql.= " sum(l.total_ht) as amount, sum(l.total_ttc) as amount_ttc, sum(l.qty) as qty";
$sql.= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."pos_facture as pf, ".MAIN_DB_PREFIX."facturedet as l, ".MAIN_DB_PREFIX."product as p";
if ($selected_cat === -2)	// Without any category 
{
    $sql.= " LEFT OUTER JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON p.rowid = cp.fk_product";
}
else if ($selected_cat) 	// Into a specific category
{
    $sql.= ", ".MAIN_DB_PREFIX."categorie as c, ".MAIN_DB_PREFIX."categorie_product as cp";
}
$sql.= " WHERE l.fk_product = p.rowid";
$sql.= " AND f.rowid = pf.fk_facture";
$sql.= " AND l.fk_facture = f.rowid";
$sql.= " AND f.fk_statut in (1,2)";
if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
	$sql.= " AND f.type IN (0,1,2)";
} else {
	$sql.= " AND f.type IN (0,1,2,3)";
}
if ($date_start && $date_end) {
	$sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
}
if ($selected_cat === -2)	// Without any category  
{
    $sql.=" AND cp.fk_product is null";
}
else if ($selected_cat) {	// Into a specific category
    $sql.= " AND (c.rowid = ".$selected_cat;
    if ($subcat) $sql.=" OR c.fk_parent = " . $selected_cat;
    $sql.= ")";
	$sql.= " AND cp.fk_categorie = c.rowid AND cp.fk_product = p.rowid";
}
$sql.= " AND f.entity = ".$conf->entity;
$sql.= " GROUP BY p.rowid";
$sql.= " ORDER BY p.ref";

dol_syslog("cabyprodserv sql=".$sql);
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i=0;
	while ($i < $num) {
		$obj = $db->fetch_object($result);
		$amount_ht[$obj->rowid] = $obj->amount;
		$amount[$obj->rowid] = $obj->amount_ttc;
		$qty[$obj->rowid] = $obj->qty;
		$name[$obj->rowid] = $obj->ref . '&nbsp;-&nbsp;' . $obj->label;
		$catotal_ht+=$obj->amount;
		$catotal+=$obj->amount_ttc;
		$i++;
	}
} else {
	dol_print_error($db);
}

// Show Array
$i=0;
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
// Extra parameters management
foreach($headerparams as $key => $value)
{
	print '<input type="hidden" name="'.$key.'" value="'.$value.'">';
}

print '<table class="noborder" width="100%">';
// Category filter
print '<tr class="liste_titre">';
print '<td>';
print $langs->trans("Category") . ': ' . $formother->select_categories(0, $selected_cat, 'search_categ', true);
print ' ';
print $langs->trans("SubCats") . '? ';
print '<input type="checkbox" name="subcat" value="yes"';
if ($subcat) {
	print ' checked';
}
print '><td></td><td></td></td>';
print '<td colspan="3" align="right">';
print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'"  value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
print '</td></tr>';
// Array header
print "<tr class=\"liste_titre\">";
print_liste_field_titre($langs->trans("Product"),$_SERVER["PHP_SELF"],"name","",$paramslink,"",$sortfield,$sortorder);
print_liste_field_titre($langs->trans('AmountHT'),$_SERVER["PHP_SELF"],"amount_ht","",$paramslink,'align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("AmountTTC"),$_SERVER["PHP_SELF"],"amount_ttc","",$paramslink,'align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Percentage"),$_SERVER["PHP_SELF"],"amount_ttc","",$paramslink,'align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Units"),$_SERVER["PHP_SELF"],"qty","",$paramslink,'align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("OtherStatistics"),$_SERVER["PHP_SELF"],"","","",'align="center" width="20%"');
print "</tr>\n";
// Array Data
$var=true;

if (count($amount)) {
    $arrayforsort=$name;
    // defining arrayforsort
    if ($sortfield == 'nom' && $sortorder == 'asc') {
	    asort($name);
	    $arrayforsort=$name;
    }
    if ($sortfield == 'nom' && $sortorder == 'desc') {
	    arsort($name);
	    $arrayforsort=$name;
    }
    if ($sortfield == 'amount_ht' && $sortorder == 'asc') {
		asort($amount_ht);
		$arrayforsort=$amount_ht;
	}
	if ($sortfield == 'amount_ht' && $sortorder == 'desc') {
		arsort($amount_ht);
		$arrayforsort=$amount_ht;
	}
	if ($sortfield == 'amount_ttc' && $sortorder == 'asc') {
	    asort($amount);
	    $arrayforsort=$amount;
	}
	if ($sortfield == 'amount_ttc' && $sortorder == 'desc') {
	    arsort($amount);
		$arrayforsort=$amount;
	}
	if ($sortfield == 'qty' && $sortorder == 'asc') {
	    asort($qty);
	    $arrayforsort=$qty;
	}
	if ($sortfield == 'qty' && $sortorder == 'desc') {
	    arsort($qty);
		$arrayforsort=$qty;
	}
	foreach($arrayforsort as $key=>$value) {
	    $var=!$var;
	    print "<tr ".$bc[$var].">";

	    // Product
	     $fullname=$name[$key];
	   
		$linkname='<a href="'.DOL_URL_ROOT.'/product/card.php?id='.$key.'">'.img_object($langs->trans("ShowProduct"),'product').' '.$fullname.'</a>';

		print "<td>".$linkname."</td>\n";

		// Amount w/o VAT
		print '<td align="right">';
		print price($amount_ht[$key]);
		print '</td>';

		// Amount with VAT
		print '<td align="right">';
		print price($amount[$key]);
		print '</td>';

		// Percent;
		print '<td align="right">'.($catotal > 0 ? round(100 * $amount[$key] / $catotal, 2).'%' : '&nbsp;').'</td>';
		
		// Units;
		print '<td align="right">'.$qty[$key].'&nbsp;'.'</td>';

		// Other stats
        print '<td align="center">';
        if ($key>0) print '&nbsp;<a href="'.DOL_URL_ROOT.'/product/stats/card.php?id='.$key.'">'.img_picto($langs->trans("ProductStats"),"stats").'</a>&nbsp;';
        print '</td>';

		print "</tr>\n";
		$i++;
	}

	// Total
	print '<tr class="liste_total">';
	print '<td>'.$langs->trans("Total").'</td>';
	print '<td align="right">'.price($catotal_ht).'</td>';
	print '<td align="right">'.price($catotal).'</td>';
	print '<td>&nbsp;</td>';
	print '<td>&nbsp;</td>';
	print '<td>&nbsp;</td>';
	print '</tr>';

	$db->free($result);
}

print "</table>";
print '</form>';

llxFooter();
$db->close();

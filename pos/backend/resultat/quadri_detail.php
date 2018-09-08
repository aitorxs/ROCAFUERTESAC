<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006-2007 Yannick Warnier      <ywarnier@beeznest.org>
 * Copyright (C) 2014-2017 Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2016      Alexandre Spangaro   <aspangaro@zendsi.com>
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
 *	    \file       htdocs/compta/tva/quadri_detail.php
 *      \ingroup    tax
 *		\brief      Trimestrial page - detailed version
 *		TODO 		Deal with recurrent invoices as well
 */

$res=@include("../../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../../main.inc.php");                // For "custom" directory
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/paymentexpensereport.class.php';

global $langs, $user,$conf,$db, $bc;

$langs->load("bills");
$langs->load("compta");
$langs->load("companies");
$langs->load("products");
$langs->load("trips");
$langs->load("other");

// Date range
$year=GETPOST("year","int");
if (empty($year))
{
	$year_current = strftime("%Y",dol_now());
	$year_start = $year_current;
	$month_current = strftime("%m",dol_now());
} else {
	$year_current = $year;
	$year_start = $year;
	$month_current = strftime("%m",dol_now());
}
$date_start=dol_mktime(0,0,0,GETPOST("date_startmonth"),GETPOST("date_startday"),GETPOST("date_startyear"));
$date_end=dol_mktime(23,59,59,GETPOST("date_endmonth"),GETPOST("date_endday"),GETPOST("date_endyear"));
// Quarter
if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$date_start=dol_get_first_day($year_current,$month_current,false);
	$date_end=dol_get_last_day($year_current,$month_current,false);
}

$min = GETPOST("min");
if (empty($min)) $min = 0;

// Define modetax (0 or 1)
// 0=normal, 1=option vat for services is on debit
$modetax = $conf->global->TAX_MODE;
if (isset($_REQUEST["modetax"])) $modetax=$_REQUEST["modetax"];
if (empty($modetax)) $modetax=0;

// Security check
$socid = GETPOST('socid','int');
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'tax', '', '', 'charges');



/*
 * View
 */

$morequerystring='';
$listofparams=array('date_startmonth','date_startyear','date_startday','date_endmonth','date_endyear','date_endday');
foreach($listofparams as $param)
{
	if (GETPOST($param)!='') $morequerystring.=($morequerystring?'&':'').$param.'='.GETPOST($param);
}

llxHeader('',$langs->trans("VATReport"),'','',0,0,'','',$morequerystring);

$form=new Form($db);

$company_static=new Societe($db);
$invoice_customer=new Facture($db);
$invoice_supplier=new FactureFournisseur($db);
$expensereport=new ExpenseReport($db);
$product_static=new Product($db);
$payment_static=new Paiement($db);
$paymentfourn_static=new PaiementFourn($db);
$paymentexpensereport_static=new PaymentExpenseReport($db);

//print load_fiche_titre($langs->trans("VAT"),"");

//$fsearch.='<br>';
$fsearch.='  <input type="hidden" name="year" value="'.$year.'">';
$fsearch.='  <input type="hidden" name="modetax" value="'.$modetax.'">';
//$fsearch.='  '.$langs->trans("SalesTurnoverMinimum").': ';
//$fsearch.='  <input type="text" name="min" value="'.$min.'">';


// Affiche en-tete du rapport
if ($modetax==1)	// Calculate on invoice for goods and services
{
    $nom=$langs->trans("VATReportByQuartersInDueDebtMode");
    $calcmode=$langs->trans("CalcModeVATDebt");
    $calcmode.='<br>('.$langs->trans("TaxModuleSetupToModifyRules",DOL_URL_ROOT.'/admin/taxes.php').')';
    $period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
    $prevyear=$year_start; $prevquarter=$q;
	if ($prevquarter > 1) $prevquarter--;
	else { $prevquarter=4; $prevyear--; }
	$nextyear=$year_start; $nextquarter=$q;
	if ($nextquarter < 4) $nextquarter++;
	else { $nextquarter=1; $nextyear++; }
	//$periodlink=($prevyear?"<a href='".$_SERVER["PHP_SELF"]."?year=".$prevyear."&q=".$prevquarter."&modetax=".$modetax."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".$nextyear."&q=".$nextquarter."&modetax=".$modetax."'>".img_next()."</a>":"");
    $description=$langs->trans("RulesVATDueServices");
    $description.='<br>';
    $description.=$langs->trans("RulesVATDueProducts");
    //if ($conf->global->MAIN_MODULE_COMPTABILITE || $conf->global->MAIN_MODULE_ACCOUNTING) $description.='<br>'.img_warning().' '.$langs->trans('OptionVatInfoModuleComptabilite');
    //if (! empty($conf->global->MAIN_MODULE_COMPTABILITE)) $description.='<br>'.$langs->trans("WarningDepositsNotIncluded");
    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $description.='<br>'.$langs->trans("DepositsAreNotIncluded");
	else  $description.='<br>'.$langs->trans("DepositsAreIncluded");
    $description.=$fsearch;
    $builddate=time();
    //$exportlink=$langs->trans("NotYetAvailable");

	// Customers invoices
	$elementcust=$langs->trans("CustomersInvoices");
	$productcust=$langs->trans("ProductOrService");
	$amountcust=$langs->trans("AmountHT");
	$vatcust=$langs->trans("VATReceived");
	if ($mysoc->tva_assuj) $vatcust.=' ('.$langs->trans("ToPay").')';

	// Suppliers invoices
	$elementsup=$langs->trans("SuppliersInvoices");
	$productsup=$langs->trans("ProductOrService");
	$amountsup=$langs->trans("AmountHT");
	$vatsup=$langs->trans("VATPaid");
	if ($mysoc->tva_assuj) $vatsup.=' ('.$langs->trans("ToGetBack").')';

}
if ($modetax==0) 	// Invoice for goods, payment for services
{
    $nom=$langs->trans("VATReportByQuartersInInputOutputMode");
    $calcmode=$langs->trans("CalcModeVATEngagement");
    $calcmode.='<br>('.$langs->trans("TaxModuleSetupToModifyRules",DOL_URL_ROOT.'/admin/taxes.php').')';
    $period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
    $prevyear=$year_start; $prevquarter=$q;
	if ($prevquarter > 1) $prevquarter--;
	else { $prevquarter=4; $prevyear--; }
	$nextyear=$year_start; $nextquarter=$q;
	if ($nextquarter < 4) $nextquarter++;
	else { $nextquarter=1; $nextyear++; }
	//$periodlink=($prevyear?"<a href='".$_SERVER["PHP_SELF"]."?year=".$prevyear."&q=".$prevquarter."&modetax=".$modetax."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".$nextyear."&q=".$nextquarter."&modetax=".$modetax."'>".img_next()."</a>":"");
    $description=$langs->trans("RulesVATInServices");
    $description.=' '.$langs->trans("DepositsAreIncluded");
    $description.='<br>';
    $description.=$langs->trans("RulesVATInProducts");
    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $description.=' '.$langs->trans("DepositsAreNotIncluded");
	else  $description.=' '.$langs->trans("DepositsAreIncluded");
    //if ($conf->global->MAIN_MODULE_COMPTABILITE || $conf->global->MAIN_MODULE_ACCOUNTING) $description.='<br>'.img_warning().' '.$langs->trans('OptionVatInfoModuleComptabilite');
    //if (! empty($conf->global->MAIN_MODULE_COMPTABILITE)) $description.='<br>'.$langs->trans("WarningDepositsNotIncluded");
    $description.=$fsearch;
	$builddate=time();
    //$exportlink=$langs->trans("NotYetAvailable");

	// Customers invoices
	$elementcust=$langs->trans("CustomersInvoices");
	$productcust=$langs->trans("ProductOrService");
	$amountcust=$langs->trans("AmountHT");
	$vatcust=$langs->trans("VATReceived");
	if ($mysoc->tva_assuj) $vatcust.=' ('.$langs->trans("ToPay").')';

	// Suppliers invoices
	$elementsup=$langs->trans("SuppliersInvoices");
	$productsup=$langs->trans("ProductOrService");
	$amountsup=$langs->trans("AmountHT");
	$vatsup=$langs->trans("VATPaid");
	if ($mysoc->tva_assuj) $vatsup.=' ('.$langs->trans("ToGetBack").')';

}
report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink,array(),$calcmode);

$vatcust=$langs->trans("VATReceived");
$vatsup=$langs->trans("VATPaid");
$vatexpensereport=$langs->trans("VATPaid");

// VAT Received and paid
print '<table class="noborder" width="100%">';

$y = $year_current;
$total = 0;
$i=0;

// Load arrays of datas
$x_coll = vat_by_date($db, 0, 0, $date_start, $date_end, $modetax, 'sell');
/////empiezo
$list=array();


$invoicetable='facture';
$invoicedettable='facturedet';
$fk_facture='fk_facture';
$fk_facture2='fk_facture';
$fk_payment='fk_paiement';
$total_tva='total_tva';
$total_localtax1='total_localtax1';
$total_localtax2='total_localtax2';
$paymenttable='paiement';
$paymentfacturetable='paiement_facture';
$invoicefieldref='facnumber';


// CAS DES BIENS

// Define sql request
$sql='';
if ($modetax == 1)  // Option vat on delivery for goods (payment) and debit invoice for services
{
    // Count on delivery date (use invoice date as delivery is unknown)
    $sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.tva_tx as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
    $sql .=" d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
    $sql.= " d.date_start as date_start, d.date_end as date_end,";
    $sql.= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef, s.nom as company_name, s.rowid as company_id,";
    $sql.= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
    $sql.= " 0 as payment_id, 0 as payment_amount";
    $sql.= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
    $sql.= " ".MAIN_DB_PREFIX."pos_facture as posf,";
    $sql.= " ".MAIN_DB_PREFIX."societe as s,";
    $sql.= " ".MAIN_DB_PREFIX.$invoicedettable." as d" ;
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
    $sql.= " WHERE f.entity = " . $conf->entity;
    $sql.= " AND posf.fk_facture = f.rowid";
    $sql.= " AND f.fk_statut in (1,2)"; // Validated or paid (partially or completely)
    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql.= " AND f.type IN (0,1,2,5)";
    else $sql.= " AND f.type IN (0,1,2,3,5)";
    $sql.= " AND f.rowid = d.".$fk_facture;
    $sql.= " AND s.rowid = f.fk_soc";
    if ($y && $m)
    {
        $sql.= " AND f.datef >= '".$db->idate(dol_get_first_day($y,$m,false))."'";
        $sql.= " AND f.datef <= '".$db->idate(dol_get_last_day($y,$m,false))."'";
    }
    else if ($y)
    {
        $sql.= " AND f.datef >= '".$db->idate(dol_get_first_day($y,1,false))."'";
        $sql.= " AND f.datef <= '".$db->idate(dol_get_last_day($y,12,false))."'";
    }
    if ($q) $sql.= " AND (date_format(f.datef,'%m') > ".(($q-1)*3)." AND date_format(f.datef,'%m') <= ".($q*3).")";
    if ($date_start && $date_end) $sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
    $sql.= " AND (d.product_type = 0";                              // Limit to products
    $sql.= " AND d.date_start is null AND d.date_end IS NULL)";     // enhance detection of service
    $sql.= " ORDER BY d.rowid, d.".$fk_facture;
}
else    // Option vat on delivery for goods (payments) and payments for services
{
    // Count on delivery date (use invoice date as delivery is unknown)
    $sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.tva_tx as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
    $sql .=" d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
    $sql.= " d.date_start as date_start, d.date_end as date_end,";
    $sql.= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef as date_f, s.nom as company_name, s.rowid as company_id,";
    $sql.= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
    $sql.= " 0 as payment_id, 0 as payment_amount";
    $sql.= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
    $sql.= " ".MAIN_DB_PREFIX."pos_facture as posf,";
    $sql.= " ".MAIN_DB_PREFIX."societe as s,";
    $sql.= " ".MAIN_DB_PREFIX.$invoicedettable." as d" ;
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
    $sql.= " WHERE f.entity = " . $conf->entity;
    $sql.= " AND f.fk_statut in (1,2)"; // Validated or paid (partially or completely)
    $sql.= " AND posf.fk_facture = f.rowid";
    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql.= " AND f.type IN (0,1,2,5)";
    else $sql.= " AND f.type IN (0,1,2,3,5)";
    $sql.= " AND f.rowid = d.".$fk_facture;
    $sql.= " AND s.rowid = f.fk_soc";
    if ($y && $m)
    {
        $sql.= " AND f.datef >= '".$db->idate(dol_get_first_day($y,$m,false))."'";
        $sql.= " AND f.datef <= '".$db->idate(dol_get_last_day($y,$m,false))."'";
    }
    else if ($y)
    {
        $sql.= " AND f.datef >= '".$db->idate(dol_get_first_day($y,1,false))."'";
        $sql.= " AND f.datef <= '".$db->idate(dol_get_last_day($y,12,false))."'";
    }
    if ($q) $sql.= " AND (date_format(f.datef,'%m') > ".(($q-1)*3)." AND date_format(f.datef,'%m') <= ".($q*3).")";
    if ($date_start && $date_end) $sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
    $sql.= " AND (d.product_type = 0";                              // Limit to products
    $sql.= " AND d.date_start is null AND d.date_end IS NULL)";     // enhance detection of service
    $sql.= " ORDER BY d.rowid, d.".$fk_facture;
    //print $sql;
}

//print $sql.'<br>';
if (! $sql) return -1;
if ($sql == 'TODO') return -2;
if ($sql != 'TODO')
{
    dol_syslog("Tax.lib.php::vat_by_date", LOG_DEBUG);

    $resql = $db->query($sql);
    if ($resql)
    {
        $rate = -1;
        $oldrowid='';
        while($assoc = $db->fetch_array($resql))
        {
            if (! isset($list[$assoc['rate']]['totalht']))  $list[$assoc['rate']]['totalht']=0;
            if (! isset($list[$assoc['rate']]['vat']))      $list[$assoc['rate']]['vat']=0;
            if (! isset($list[$assoc['rate']]['localtax1']))      $list[$assoc['rate']]['localtax1']=0;
            if (! isset($list[$assoc['rate']]['localtax2']))      $list[$assoc['rate']]['localtax2']=0;

            if ($assoc['rowid'] != $oldrowid)       // Si rupture sur d.rowid
            {
                $oldrowid=$assoc['rowid'];
                $list[$assoc['rate']]['totalht']  += $assoc['total_ht'];
                $list[$assoc['rate']]['vat']      += $assoc['total_vat'];
                $list[$assoc['rate']]['localtax1']      += $assoc['total_localtax1'];
                $list[$assoc['rate']]['localtax2']      += $assoc['total_localtax2'];
            }
            $list[$assoc['rate']]['dtotal_ttc'][] = $assoc['total_ttc'];
            $list[$assoc['rate']]['dtype'][] = $assoc['dtype'];
            $list[$assoc['rate']]['datef'][] = $assoc['datef'];
            $list[$assoc['rate']]['company_name'][] = $assoc['company_name'];
            $list[$assoc['rate']]['company_id'][] = $assoc['company_id'];
            $list[$assoc['rate']]['ddate_start'][] = $db->jdate($assoc['date_start']);
            $list[$assoc['rate']]['ddate_end'][] = $db->jdate($assoc['date_end']);

            $list[$assoc['rate']]['facid'][] = $assoc['facid'];
            $list[$assoc['rate']]['facnum'][] = $assoc['facnum'];
            $list[$assoc['rate']]['type'][] = $assoc['type'];
            $list[$assoc['rate']]['ftotal_ttc'][] = $assoc['ftotal_ttc'];
            $list[$assoc['rate']]['descr'][] = $assoc['descr'];

            $list[$assoc['rate']]['totalht_list'][] = $assoc['total_ht'];
            $list[$assoc['rate']]['vat_list'][] = $assoc['total_vat'];
            $list[$assoc['rate']]['localtax1_list'][] = $assoc['total_localtax1'];
            $list[$assoc['rate']]['localtax2_list'][]  = $assoc['total_localtax2'];

            $list[$assoc['rate']]['pid'][] = $assoc['pid'];
            $list[$assoc['rate']]['pref'][] = $assoc['pref'];
            $list[$assoc['rate']]['ptype'][] = $assoc['ptype'];

            $list[$assoc['rate']]['payment_id'][] = $assoc['payment_id'];
            $list[$assoc['rate']]['payment_amount'][] = $assoc['payment_amount'];

            $rate = $assoc['rate'];
        }
    }
    else
    {
        dol_print_error($db);
        return -3;
    }
}


// CAS DES SERVICES

// Define sql request
$sql='';
if ($modetax == 1)  // Option vat on delivery for goods (payment) and debit invoice for services
{
    // Count on invoice date
    $sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.tva_tx as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
    $sql .=" d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
    $sql.= " d.date_start as date_start, d.date_end as date_end,";
    $sql.= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef, s.nom as company_name, s.rowid as company_id,";
    $sql.= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
    $sql.= " 0 as payment_id, 0 as payment_amount";
    $sql.= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
    $sql.= " ".MAIN_DB_PREFIX."pos_facture as posf,";
    $sql.= " ".MAIN_DB_PREFIX."societe as s,";
    $sql.= " ".MAIN_DB_PREFIX.$invoicedettable." as d" ;
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
    $sql.= " WHERE f.entity = " . $conf->entity;
    $sql.= " AND posf.fk_facture = f.rowid";
    $sql.= " AND f.fk_statut in (1,2)"; // Validated or paid (partially or completely)
    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql.= " AND f.type IN (0,1,2,5)";
    else $sql.= " AND f.type IN (0,1,2,3,5)";
    $sql.= " AND f.rowid = d.".$fk_facture;
    $sql.= " AND s.rowid = f.fk_soc";
    if ($y && $m)
    {
        $sql.= " AND f.datef >= '".$db->idate(dol_get_first_day($y,$m,false))."'";
        $sql.= " AND f.datef <= '".$db->idate(dol_get_last_day($y,$m,false))."'";
    }
    else if ($y)
    {
        $sql.= " AND f.datef >= '".$db->idate(dol_get_first_day($y,1,false))."'";
        $sql.= " AND f.datef <= '".$db->idate(dol_get_last_day($y,12,false))."'";
    }
    if ($q) $sql.= " AND (date_format(f.datef,'%m') > ".(($q-1)*3)." AND date_format(f.datef,'%m') <= ".($q*3).")";
    if ($date_start && $date_end) $sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
    $sql.= " AND (d.product_type = 1";                              // Limit to services
    $sql.= " OR d.date_start is NOT null OR d.date_end IS NOT NULL)";       // enhance detection of service
    $sql.= " ORDER BY d.rowid, d.".$fk_facture;
}
else    // Option vat on delivery for goods (payments) and payments for services
{
    // Count on payments date
    $sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.tva_tx as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
    $sql .=" d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
    $sql.= " d.date_start as date_start, d.date_end as date_end,";
    $sql.= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef, s.nom as company_name, s.rowid as company_id,";
    $sql.= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
    $sql.= " pf.".$fk_payment." as payment_id, pf.amount as payment_amount";
    $sql.= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
    $sql.= " ".MAIN_DB_PREFIX."pos_facture as posf,";
    $sql.= " ".MAIN_DB_PREFIX.$paymentfacturetable." as pf,";
    $sql.= " ".MAIN_DB_PREFIX.$paymenttable." as pa,";
    $sql.= " ".MAIN_DB_PREFIX."societe as s,";
    $sql.= " ".MAIN_DB_PREFIX.$invoicedettable." as d";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
    $sql.= " WHERE f.entity = " . $conf->entity;
    $sql.= " AND posf.fk_facture = f.rowid";
    $sql.= " AND f.fk_statut in (1,2)"; // Paid (partially or completely)
    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql.= " AND f.type IN (0,1,2,5)";
    else $sql.= " AND f.type IN (0,1,2,3,5)";
    $sql.= " AND f.rowid = d.".$fk_facture;
    $sql.= " AND s.rowid = f.fk_soc";
    $sql.= " AND pf.".$fk_facture2." = f.rowid";
    $sql.= " AND pa.rowid = pf.".$fk_payment;
    if ($y && $m)
    {
        $sql.= " AND pa.datep >= '".$db->idate(dol_get_first_day($y,$m,false))."'";
        $sql.= " AND pa.datep <= '".$db->idate(dol_get_last_day($y,$m,false))."'";
    }
    else if ($y)
    {
        $sql.= " AND pa.datep >= '".$db->idate(dol_get_first_day($y,1,false))."'";
        $sql.= " AND pa.datep <= '".$db->idate(dol_get_last_day($y,12,false))."'";
    }
    if ($q) $sql.= " AND (date_format(pa.datep,'%m') > ".(($q-1)*3)." AND date_format(pa.datep,'%m') <= ".($q*3).")";
    if ($date_start && $date_end) $sql.= " AND pa.datep >= '".$db->idate($date_start)."' AND pa.datep <= '".$db->idate($date_end)."'";
    $sql.= " AND (d.product_type = 1";                              // Limit to services
    $sql.= " OR d.date_start is NOT null OR d.date_end IS NOT NULL)";       // enhance detection of service
    $sql.= " ORDER BY d.rowid, d.".$fk_facture.", pf.rowid";
}

if (! $sql)
{
    dol_syslog("Tax.lib.php::vat_by_date no accountancy module enabled".$sql,LOG_ERR);
    return -1;  // -1 = Not accountancy module enabled
}
if ($sql == 'TODO') return -2; // -2 = Feature not yet available
if ($sql != 'TODO')
{
    dol_syslog("Tax.lib.php::vat_by_date", LOG_DEBUG);
    $resql = $db->query($sql);
    if ($resql)
    {
        $rate = -1;
        $oldrowid='';
        while($assoc = $db->fetch_array($resql))
        {
            if (! isset($list[$assoc['rate']]['totalht']))  $list[$assoc['rate']]['totalht']=0;
            if (! isset($list[$assoc['rate']]['vat']))      $list[$assoc['rate']]['vat']=0;
            if (! isset($list[$assoc['rate']]['localtax1']))      $list[$assoc['rate']]['localtax1']=0;
            if (! isset($list[$assoc['rate']]['localtax2']))      $list[$assoc['rate']]['localtax2']=0;

            if ($assoc['rowid'] != $oldrowid)       // Si rupture sur d.rowid
            {
                $oldrowid=$assoc['rowid'];
                $list[$assoc['rate']]['totalht']  += $assoc['total_ht'];
                $list[$assoc['rate']]['vat']      += $assoc['total_vat'];
                $list[$assoc['rate']]['localtax1']	 += $assoc['total_localtax1'];
                $list[$assoc['rate']]['localtax2']	 += $assoc['total_localtax2'];
            }
            $list[$assoc['rate']]['dtotal_ttc'][] = $assoc['total_ttc'];
            $list[$assoc['rate']]['dtype'][] = $assoc['dtype'];
            $list[$assoc['rate']]['datef'][] = $assoc['datef'];
            $list[$assoc['rate']]['company_name'][] = $assoc['company_name'];
            $list[$assoc['rate']]['company_id'][] = $assoc['company_id'];
            $list[$assoc['rate']]['ddate_start'][] = $db->jdate($assoc['date_start']);
            $list[$assoc['rate']]['ddate_end'][] = $db->jdate($assoc['date_end']);

            $list[$assoc['rate']]['facid'][] = $assoc['facid'];
            $list[$assoc['rate']]['facnum'][] = $assoc['facnum'];
            $list[$assoc['rate']]['type'][] = $assoc['type'];
            $list[$assoc['rate']]['ftotal_ttc'][] = $assoc['ftotal_ttc'];
            $list[$assoc['rate']]['descr'][] = $assoc['descr'];

            $list[$assoc['rate']]['totalht_list'][] = $assoc['total_ht'];
            $list[$assoc['rate']]['vat_list'][] = $assoc['total_vat'];
            $list[$assoc['rate']]['localtax1_list'][] = $assoc['total_localtax1'];
            $list[$assoc['rate']]['localtax2_list'][] = $assoc['total_localtax2'];

            $list[$assoc['rate']]['pid'][] = $assoc['pid'];
            $list[$assoc['rate']]['pref'][] = $assoc['pref'];
            $list[$assoc['rate']]['ptype'][] = $assoc['ptype'];

            $list[$assoc['rate']]['payment_id'][] = $assoc['payment_id'];
            $list[$assoc['rate']]['payment_amount'][] = $assoc['payment_amount'];

            $rate = $assoc['rate'];
        }
    }
    else
    {
        dol_print_error($db);
        return -3;
    }
}

$x_coll = $list;

////paro

if (! is_array($x_coll))
{
	$langs->load("errors");
	if ($x_coll == -1)
		print '<tr><td colspan="5">'.$langs->trans("ErrorNoAccountancyModuleLoaded").'</td></tr>';
	else if ($x_coll == -2)
		print '<tr><td colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
	else
		print '<tr><td colspan="5">'.$langs->trans("Error").'</td></tr>';
}
else
{
	$x_both = array();
	//now, from these two arrays, get another array with one rate per line
	foreach(array_keys($x_coll) as $my_coll_rate)
	{
		$x_both[$my_coll_rate]['coll']['totalht'] = $x_coll[$my_coll_rate]['totalht'];
		$x_both[$my_coll_rate]['coll']['vat']     = $x_coll[$my_coll_rate]['vat'];
		$x_both[$my_coll_rate]['paye']['totalht'] = 0;
		$x_both[$my_coll_rate]['paye']['vat'] = 0;
		$x_both[$my_coll_rate]['coll']['links'] = '';
		$x_both[$my_coll_rate]['coll']['detail'] = array();
		foreach($x_coll[$my_coll_rate]['facid'] as $id=>$dummy)
		{
			$invoice_customer->id=$x_coll[$my_coll_rate]['facid'][$id];
			$invoice_customer->ref=$x_coll[$my_coll_rate]['facnum'][$id];
			$invoice_customer->type=$x_coll[$my_coll_rate]['type'][$id];
			$x_both[$my_coll_rate]['coll']['detail'][] = array(
				'id'				=>$x_coll[$my_coll_rate]['facid'][$id],
				'descr'				=>$x_coll[$my_coll_rate]['descr'][$id],
				'pid'				=>$x_coll[$my_coll_rate]['pid'][$id],
				'pref'				=>$x_coll[$my_coll_rate]['pref'][$id],
				'ptype'				=>$x_coll[$my_coll_rate]['ptype'][$id],
				'payment_id'		=>$x_coll[$my_coll_rate]['payment_id'][$id],
				'payment_amount'	=>$x_coll[$my_coll_rate]['payment_amount'][$id],
				'ftotal_ttc'		=>$x_coll[$my_coll_rate]['ftotal_ttc'][$id],
				'dtotal_ttc'		=>$x_coll[$my_coll_rate]['dtotal_ttc'][$id],
				'dtype'				=>$x_coll[$my_coll_rate]['dtype'][$id],
				'ddate_start'		=>$x_coll[$my_coll_rate]['ddate_start'][$id],
				'ddate_end'			=>$x_coll[$my_coll_rate]['ddate_end'][$id],
				'totalht'			=>$x_coll[$my_coll_rate]['totalht_list'][$id],
				'vat'				=>$x_coll[$my_coll_rate]['vat_list'][$id],
				'link'				=>$invoice_customer->getNomUrl(1,'',12));
		}
	}
	//now we have an array (x_both) indexed by rates for coll and paye


	//print table headers for this quadri - incomes first

	$x_coll_sum = 0;
	$x_coll_ht = 0;

	$span=3;
	if ($modetax == 0) $span+=2;

	//print '<tr><td colspan="'.($span+1).'">'..')</td></tr>';

	// Customers invoices
	print '<tr class="liste_titre">';
	print '<td align="left">'.$elementcust.'</td>';
	print '<td align="left">'.$productcust.'</td>';
	if ($modetax == 0)
	{
		print '<td align="right">'.$amountcust.'</td>';
		print '<td align="right">'.$langs->trans("Payment").' ('.$langs->trans("PercentOfInvoice").')</td>';
	}
	print '<td align="right">'.$langs->trans("AmountHTVATRealReceived").'</td>';
	print '<td align="right">'.$vatcust.'</td>';
	print '</tr>';

	$action = "tvadetail";
	$parameters["mode"] = $modetax;
	$parameters["start"] = $date_start;
	$parameters["end"] = $date_end;
	$parameters["type"] = 'vat';

	$object = array(&$x_coll, &$x_both);
	// Initialize technical object to manage hooks of expenses. Note that conf->hooks_modules contains array array
	$hookmanager->initHooks(array('externalbalance'));
	$reshook=$hookmanager->executeHooks('addVatLine',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

	foreach(array_keys($x_coll) as $rate)
	{
		$subtot_coll_total_ht = 0;
		$subtot_coll_vat = 0;

		if (is_array($x_both[$rate]['coll']['detail']))
		{
			// VAT Rate
			$var=true;
			print "<tr>";
			print '<td class="tax_rate">'.$langs->trans("Rate").': '.vatrate($rate).'%</td><td colspan="'.$span.'"></td>';
			print '</tr>'."\n";

			foreach($x_both[$rate]['coll']['detail'] as $index => $fields)
			{
				// Define type
				$type=($fields['dtype']?$fields['dtype']:$fields['ptype']);
				// Try to enhance type detection using date_start and date_end for free lines where type
				// was not saved.
				if (! empty($fields['ddate_start'])) $type=1;
				if (! empty($fields['ddate_end'])) $type=1;

				$var=!$var;
				print '<tr '.$bc[$var].'>';

				// Ref
				print '<td class="nowrap" align="left">'.$fields['link'].'</td>';

				// Description
				print '<td align="left">';
				if ($fields['pid'])
				{
					$product_static->id=$fields['pid'];
					$product_static->ref=$fields['pref'];
					$product_static->type=$fields['ptype'];
					print $product_static->getNomUrl(1);
					if (dol_string_nohtmltag($fields['descr'])) print ' - '.dol_trunc(dol_string_nohtmltag($fields['descr']),16);
				}
				else
				{
					if ($type) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');
					if (preg_match('/^\((.*)\)$/',$fields['descr'],$reg))
					{
						if ($reg[1]=='DEPOSIT') $fields['descr']=$langs->transnoentitiesnoconv('Deposit');
						elseif ($reg[1]=='CREDIT_NOTE') $fields['descr']=$langs->transnoentitiesnoconv('CreditNote');
						else $fields['descr']=$langs->transnoentitiesnoconv($reg[1]);
					}
					print $text.' '.dol_trunc(dol_string_nohtmltag($fields['descr']),16);

					// Show range
					print_date_range($fields['ddate_start'],$fields['ddate_end']);
				}
				print '</td>';

				// Total HT
				if ($modetax == 0)
				{
					print '<td class="nowrap" align="right">';
					print price($fields['totalht']);
					if (price2num($fields['ftotal_ttc']))
					{
						//print $fields['dtotal_ttc']."/".$fields['ftotal_ttc']." - ";
						$ratiolineinvoice=($fields['dtotal_ttc']/$fields['ftotal_ttc']);
						//print ' ('.round($ratiolineinvoice*100,2).'%)';
					}
					print '</td>';
				}

				// Payment
				$ratiopaymentinvoice=1;
				if ($modetax == 0)
				{
					if (isset($fields['payment_amount']) && $fields['ftotal_ttc']) $ratiopaymentinvoice=($fields['payment_amount']/$fields['ftotal_ttc']);
					print '<td class="nowrap" align="right">';
					//print $fields['totalht']."-".$fields['payment_amount']."-".$fields['ftotal_ttc'];
					if ($fields['payment_amount'] && $fields['ftotal_ttc'])
					{
						$payment_static->id=$fields['payment_id'];
						print $payment_static->getNomUrl(2);
					}
					if ($type == 0)
					{
						print $langs->trans("NotUsedForGoods");
					}
					else {
						print price($fields['payment_amount']);
						if (isset($fields['payment_amount'])) print ' ('.round($ratiopaymentinvoice*100,2).'%)';
					}
					print '</td>';
				}

				// Total collected
				print '<td class="nowrap" align="right">';
				$temp_ht=$fields['totalht'];
				if ($type == 1) $temp_ht=$fields['totalht']*$ratiopaymentinvoice;
				print price(price2num($temp_ht,'MT'),1);
				print '</td>';

				// VAT
				print '<td class="nowrap" align="right">';
				$temp_vat=$fields['vat'];
				if ($type == 1) $temp_vat=$fields['vat']*$ratiopaymentinvoice;
				print price(price2num($temp_vat,'MT'),1);
				//print price($fields['vat']);
				print '</td>';
				print '</tr>';

				$subtot_coll_total_ht += $temp_ht;
				$subtot_coll_vat      += $temp_vat;
				$x_coll_sum           += $temp_vat;
			}
		}
        // Total customers for this vat rate
        print '<tr class="liste_total">';
        print '<td></td>';
        print '<td align="right">'.$langs->trans("Total").':</td>';
        if ($modetax == 0)
        {
            print '<td class="nowrap" align="right">&nbsp;</td>';
            print '<td align="right">&nbsp;</td>';
        }
        print '<td align="right">'.price(price2num($subtot_coll_total_ht,'MT')).'</td>';
        print '<td class="nowrap" align="right">'.price(price2num($subtot_coll_vat,'MT')).'</td>';
        print '</tr>';
	}

    if (count($x_coll) == 0)   // Show a total ine if nothing shown
    {
        print '<tr class="liste_total">';
        print '<td>&nbsp;</td>';
        print '<td align="right">'.$langs->trans("Total").':</td>';
        if ($modetax == 0)
        {
            print '<td class="nowrap" align="right">&nbsp;</td>';
            print '<td align="right">&nbsp;</td>';
        }
        print '<td align="right">'.price(price2num(0,'MT')).'</td>';
        print '<td class="nowrap" align="right">'.price(price2num(0,'MT')).'</td>';
        print '</tr>';
    }

    // Blank line
	print '<tr><td colspan="'.($span+1).'">&nbsp;</td></tr>';

	//print table headers for this quadri - expenses now
	//imprime les en-tete de tables pour ce quadri - maintenant les d�penses



	print '</table>';

	// Total to pay
    print '<br><br>';
    print '<table class="noborder" width="100%">';
    $diff = $x_coll_sum;
	print '<tr class="liste_total">';
	print '<td class="liste_total" colspan="'.$span.'">'.$langs->trans("TotalToPay").($q?', '.$langs->trans("Quadri").' '.$q:'').'</td>';
	print '<td class="liste_total nowrap" align="right"><b>'.price(price2num($diff,'MT'))."</b></td>\n";
	print "</tr>\n";

	$i++;
}
echo '</table>';

llxFooter();
$db->close();

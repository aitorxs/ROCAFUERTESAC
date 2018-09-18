<?php
/* Copyright (C) 2001-2005	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Marc Barilley / Ocebo		<marc@ocebo.com>
 * Copyright (C) 2005-2012	Regis Houssin	      		<regis.houssin@capnetworks.com>
 * Copyright (C) 2012		Juanjo Menent	      		<jmenent@2byte.es>
 * Copyright (C) 2012		Andreu Bisquerra Gaya		<jove@bisquerra.com>
 * Copyright (C) 2012		David Rodriguez Martinez	<davidrm146@gmail.com>
 * Copyright (C) 2013-2018	Ferran Marcet				<fmarcet@2byte.es>
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
 *	\file       htdocs/commande/liste.php
 *	\ingroup    commande
 *	\brief      Page to list orders
 */


$res=@include '../main.inc.php';					// For root directory
if (! $res) {$res=@include '../../main.inc.php';}	// For "custom" directory
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

global $langs,$user,$conf,$db;

$langs->load('orders');
$langs->load('deliveries');
$langs->load('companies');
$langs->load('sendings');
$langs->load('massorders@massorders');

$orderyear=GETPOST('orderyear', 'int');
$ordermonth=GETPOST('ordermonth', 'int');
$deliveryyear=GETPOST('deliveryyear', 'int');
$deliverymonth=GETPOST('deliverymonth', 'int');
$sref=GETPOST('sref','alpha');
$sref_client=GETPOST('sref_client','alpha');
$snom=GETPOST('snom','alpha');
$socid=GETPOST('socid','int');
$search_user=GETPOST('search_user','int');
$search_sale=GETPOST('search_sale','int');

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');

$date_start=dol_mktime(0,0,0,$_REQUEST['date_startmonth'],$_REQUEST['date_startday'],$_REQUEST['date_startyear']);
$date_end=dol_mktime(23,59,59,$_REQUEST['date_endmonth'],$_REQUEST['date_endday'],$_REQUEST['date_endyear']);

$delivery_date_start=dol_mktime(0,0,0,$_REQUEST['delivery_date_startmonth'],$_REQUEST['delivery_date_startday'],$_REQUEST['delivery_date_startyear']);
$delivery_date_end=dol_mktime(23,59,59,$_REQUEST['delivery_date_endmonth'],$_REQUEST['delivery_date_endday'],$_REQUEST['delivery_date_endyear']);

$company_start= GETPOST('company_start','int') > 0 ? GETPOST('company_start','int') :'';
$company_end= GETPOST('company_end','int') > 0 ? GETPOST('company_end','int') :'';
$category_start= GETPOST('category_start','int') > 0 ? GETPOST('category_start','int') :'';
$category_end= GETPOST('category_end','int') > 0 ? GETPOST('category_end','int') :'';

// Security check
$id = (GETPOST('orderid')?:GETPOST('id','int'));
if ($user->socid) {$socid=$user->socid;}
$result = restrictedArea($user, 'expedition', $id,'');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) {$sortfield='c.date_creation';}
if (! $sortorder) {$sortorder='ASC';}
$limit = $conf->liste_limit;

$viewstatut=GETPOST('viewstatut');
$orders_selected = GETPOST('orders');
if($orders_selected > 0) {
    $_SESSION['orders_selected'] = GETPOST('orders');
}
/*
 * Actions
 */


if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$year_current = strftime('%Y',dol_now());
	$month_current = strftime('%m',dol_now());
	$date_start =dol_get_first_day($year_current,$month_current); 
	$date_end = dol_get_last_day($year_current,$month_current);
}

// Action select position object
if ($action === 'confirm_massinvoicing' && $confirm !== 'yes') { $action=''; }
if ($action === 'confirm_massinvoicing' && $confirm === 'yes') {
    require_once DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php';
    require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
    require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
    require_once DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php';
    if (!empty($conf->projet->enabled)) {
        require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
    }
    dol_include_once('/massorders/class/massorders.class.php');
    $langs->load('bills');
    $langs->load('products');
    $langs->load('main');
    $langs->load('massorders@massorders');

    $closeOrders = GETPOST('autocloseorders') != '';
    $groupmonth = GETPOST('groupmonth') != '';
    $grouppaymode = GETPOST('grouppaymode') != '';
    $validateInvoice = GETPOST('validateinvoice') != '';

    $orders_prev = $_SESSION['orders'];
    $societe_prev = $_SESSION['companies'];
    $paymode_prev = $_SESSION['paymode'];
    $ref_prev = $_SESSION['ref'];
    $order_date_prev = $_SESSION['order_date'];

    $i = 0;
    $nume = count($_SESSION['orders_selected']);
    while ($i < $nume) {
        $orders[] = $orders_prev[$_SESSION['orders_selected'][$i]];
        $societe[] = $societe_prev[$_SESSION['orders_selected'][$i]];
        $paymode[] = $paymode_prev[$_SESSION['orders_selected'][$i]];
        $ref[] = $ref_prev[$_SESSION['orders_selected'][$i]];
        $order_date[] = $order_date_prev[$_SESSION['orders_selected'][$i]];
        $i++;
    }

    unset($_SESSION['orders_selected'], $_SESSION['orders'], $_SESSION['companies']
        , $_SESSION['paymode'], $_SESSION['ref'], $_SESSION['order_date']);

    //$nume = count($orders);
    $i = 0;
    $static_societe = new Societe($db);
    while ($i < $nume) {
        if (empty($paymode[$i])) {
            $static_societe->fetch($societe[$i]);
            if (empty($static_societe->mode_reglement_id)) {
                $paymode[$i] = $conf->global->MASSO_PAY_MODE;
            } else {
                $paymode[$i] = $static_societe->mode_reglement_id;
            }
        }
        $i++;
    }
    $i = 0;
    $array = null;
    if ($groupmonth && $grouppaymode) {
        while ($i < $nume) {
            //$j = count($array[$societe[$i]][$order_date[$i]][$paymode[$i]]);
            //$array[$societe[$i]][$order_date[$i]][$paymode[$i]][$j] = $orders[$i];
            $array[$societe[$i]][$order_date[$i]][$paymode[$i]][] = $orders[$i];
            $i++;
        }

    } else {
        if ($groupmonth) {
            while ($i < $nume) {
                //$j = count($array[$societe[$i]][$order_date[$i]]);
                //$array[$societe[$i]][$order_date[$i]][$j] = $orders[$i];
                $array[$societe[$i]][$order_date[$i]][] = $orders[$i];
                $i++;
            }
        } else {
            if ($grouppaymode) {
                while ($i < $nume) {
                    //$j = count($array[$societe[$i]][$paymode[$i]]);
                    //$array[$societe[$i]][$paymode[$i]][$j] = $orders[$i];
                    $array[$societe[$i]][$paymode[$i]][] = $orders[$i];
                    $i++;
                }
            } else {
                while ($i < $nume) {
                    //$j = count($array[$societe[$i]]);
                    //$array[$societe[$i]][$j] = $orders[$i];
                    $array[$societe[$i]][] = $orders[$i];
                    $i++;
                }
            }
        }
    }

    // Security check
    $fieldid = GETPOST('ref', 'alpha') ? 'facnumber' : 'rowid';
    if ($user->socid) {
        $socid = $user->socid;
    }
    $result = restrictedArea($user, 'facture', $id, '', '', 'fk_soc', $fieldid);

    $massor = new Massorders($db);

    foreach ((array)$array as $comp_id => $value) {
        if ($groupmonth && $grouppaymode) {
            foreach ($value as $date_id => $value1) {
                foreach ($value1 as $pay_id => $value2) {
                    $massor->orders = $value2;
                    $result = $massor->invoicing_shipment($comp_id, $closeOrders, $validateInvoice, $date_id, $pay_id);
                    if (!$result) {
                        $error++;
                    }
                }
            }
        } else {
            if ($groupmonth) {
                foreach ($value as $date_id => $value1) {
                    $massor->orders = $value1;
                    $result = $massor->invoicing_shipment($comp_id, $closeOrders, $validateInvoice, $date_id);
                    if (!$result) {
                        $error++;
                    }
                }
            } else {
                if ($grouppaymode) {
                    foreach ($value as $pay_id => $value1) {
                        $massor->orders = $value1;
                        $result = $massor->invoicing_shipment($comp_id, $closeOrders, $validateInvoice, '', $pay_id);
                        if (!$result) {
                            $error++;
                        }
                    }
                } else {
                    $massor->orders = $value;
                    $facid = $massor->invoicing_shipment($comp_id, $closeOrders, $validateInvoice);
                    if (!$facid) {
                        $error++;
                    }
                }
            }
        }
        if (($conf->global->MASSO_AUTO_PDF || $conf->global->MASSO_AUTO_MAIL) && $facid) {
            $fac = new Facture($db);
            $fac->fetch($facid);
            $outputlangs = $langs;
            $newlang = '';
            if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
                $newlang = $fac->client->default_lang;
            }
            if (!empty($newlang)) {
                $outputlangs = new Translate('', $conf);
                $outputlangs->setDefaultLang($newlang);
            }
            $result = $fac->generateDocument($fac->modelpdf, $outputlangs);
            if (!$result) {
                $error++;
            }
        }
        if ($conf->global->MASSO_AUTO_MAIL && $result) {
            $massor->socid = $comp_id;
            $result = $massor->search_mail(100);
            if ($result < 0) {
                $error++;
            } else {
                if ($fac->statut > 0) {
                    $result = $massor->send_mail($fac, 1);
                }
                if ($result < 0) {
                    $error++;
                }
            }
        }
    }
    if ($error) {
        setEventMessage($langs->trans('ErrorCreatingInvoice'), 'errors');
    } else {
        setEventMessage($langs->trans('OkCreatingInvoice'));
    }
}



/*
 * View
 */
global $db;
$now=dol_now();

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$companystatic = new Societe($db);

$helpurl='EN:Module_Massorders|FR:Module_Massorders_FR|ES:M&oacute;dulo_Massorders';
llxHeader('',$langs->trans('Shipments'),$helpurl);

print "\n\n<!-- debut cartouche rapport -->\n";
$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
$delivery_period = $form->select_date($delivery_date_start,'delivery_date_start',0,0,1,'',1,0,1).' - '.$form->select_date($delivery_date_end,'delivery_date_end',0,0,1,'',1,0,1);
$company=$form->select_company($company_start,'company_start','s.client=1',1).' - '.$form->select_company($company_end,'company_end','s.client=1',1);
$category=$form->select_all_categories(2,$category_start,'category_start').' - '.$form->select_all_categories(2,$category_end,'category_end');
$comercial=$formother->select_salesrepresentatives($search_sale,'search_sale',$user);
$contact=$form->select_dolusers($search_user,'search_user',1);


?>
<script language="javascript" type="text/javascript">
	function checkAll() {
		jQuery(".checkElement").attr('checked', true);
	}

	function checkNone() {
		jQuery(".checkElement").attr('checked', false);
	}
</script>
<?php

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input name="sortorder" type="hidden" value="' . $sortorder . '">';
print '<input name="sortfield" type="hidden" value="' . $sortfield . '">';

print '<table width="100%" class="border">';

// Ligne de titre
print '<tr>';
print '<td valign="top" colspan="4">'.$langs->trans('ShipmentsFilter').'</td>';
print '</tr>';

// Date Range
print '<tr>';
print '<td>'.$langs->trans('OrderDate').'</td>';
print '<td colspan="3">';
print $period;
print '</td>';
print '</tr>';

// Delivery Date Range
print '<tr>';
print '<td>'.$langs->trans('DeliveryDate').'</td>';
print '<td colspan="3">';
print $delivery_period;
print '</td>';
print '</tr>';

// Company Range
print '<tr>';
print '<td>'.$langs->trans('CompanyRange').'</td>';
print '<td colspan="3">';
print $company;
print '</td>';
print '</tr>';

// Category Range
if($conf->categorie->enabled){
	print '<tr>';
	print '<td>'.$langs->trans('CustomerCategoryRange').'</td>';
	print '<td colspan="3">';
	print $category;
	print '</td>';
	print '</tr>';
}

// If the user can view prospects other than his'
if ($user->rights->societe->client->voir || $socid)
{
	$langs->load('commercial');
	print '<tr>';
	print '<td>'.$langs->trans('ThirdPartiesOfSaleRepresentative').'</td>';
	print '<td colspan="3">';
	print $comercial;
	print '</td>';
	print '</tr>';
}
// If the user can view prospects other than his'
if ($user->rights->societe->client->voir || $socid)
{
	print '<tr>';
	print '<td>'.$langs->trans('LinkedToSpecificUsers').'</td>';
	print '<td colspan="3">';
	print $contact;
	print '</td>';
	print '</tr>';
}

print '<tr>';
print '<td colspan="4" align="center"><input type="submit" class="button" name="submit" value="'.$langs->trans('Search').'"></td>';
print '</tr>';

print '</table>';

print "\n<!-- fin cartouche rapport -->\n\n";

if ((empty($delivery_date_start) && !empty($delivery_date_end)) || (!empty($delivery_date_start) && empty($delivery_date_end))){
	setEventMessage($langs->trans('ErrorFilterDeliveryDate'), 'errors');
}


if (!empty($company_start) || !empty($company_end)){
	$soc = New Societe($db);
	$soc->fetch($company_start);
	$company_start_nom = $soc->name;
	if(!empty($company_start)){
		$soc->fetch($company_end);
		$company_end_nom = $soc->name;
	}
	
	$sql = 'SELECT rowid FROM ' .MAIN_DB_PREFIX."societe WHERE nom >= '".$company_start_nom."' AND nom <= '".$company_end_nom."' AND client=1";
	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$i=0;
		while ($i < $num-1)
		{
			$obj = $db->fetch_object($result);
			$companies .= $obj->rowid. ',';
			$i++;
		}
		$obj = $db->fetch_object($result);
		$companies .= $obj->rowid;
	}
	if(empty($companies)){
		setEventMessage($langs->trans('ErrorFilterCompany'), 'errors');
	}
}

if (!empty($category_start) || !empty($category_end)){
	$soc = New Categorie($db);
	if (!empty($category_start)) {
        $soc->fetch($category_start);
    }
	$category_start_lab = $soc->label;
	if(!empty($category_start_lab)){
		if(!empty($category_end)) {
            $soc->fetch($category_end);
        }
		$category_end_lab = $soc->label;
	}

	$sql = 'SELECT rowid FROM ' .MAIN_DB_PREFIX."categorie WHERE label >= '".$category_start_lab."' AND label <= '".$category_end_lab."' AND type=2";
	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$i=0;
		while ($i < $num-1)
		{
			$obj = $db->fetch_object($result);
			$categories .= $obj->rowid. ',';
			$i++;
		}
		$obj = $db->fetch_object($result);
		$categories .= $obj->rowid;
	}
	if(empty($categories)){
		setEventMessage($langs->trans('ErrorFilterCategory'), 'errors');
	}
}
$sql = 'SELECT s.nom, s.rowid as socid, s.client, c.rowid, c.ref, c.ref_customer,';
$sql.= ' c.date_valid, c.date_creation, c.date_delivery, c.fk_statut, co.fk_mode_reglement';
$sql.= ' FROM '.MAIN_DB_PREFIX.'expedition as c';
$sql.= ', '.MAIN_DB_PREFIX.'commande as co';
$sql.= ', '.MAIN_DB_PREFIX.'element_element as ee';
$sql.= ', '.MAIN_DB_PREFIX.'societe as s';
if (!empty($categories)){

    if(version_compare(DOL_VERSION, 3.8) >= 0){
        $sql.= ' left join '.MAIN_DB_PREFIX.'categorie_societe as cat on s.rowid = cat.fk_soc ';
    } else {
        $sql.= ' left join '.MAIN_DB_PREFIX.'categorie_societe as cat on s.rowid = cat.fk_societe ';
    }
}
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) {
    $sql .= ', ' .MAIN_DB_PREFIX. 'societe_commerciaux as sc';
}
if ($search_user > 0)
{
    $sql.= ', ' .MAIN_DB_PREFIX. 'element_contact as ec';
    $sql.= ', ' .MAIN_DB_PREFIX. 'c_type_contact as tc';
}
$sql.= ' WHERE c.fk_soc = s.rowid';
$sql.= ' AND c.entity = '.$conf->entity;
$sql.= ' AND co.rowid = ee.fk_source AND c.rowid = ee.fk_target AND ee.fk_target = c.rowid AND ee.targettype = "shipping" AND ee.sourcetype = "commande"';

if (!$user->rights->societe->client->voir && !$socid) {
    $sql.= ' AND s.rowid = sc.fk_soc AND sc.fk_user = ' .$user->id;
}
if ($sref)
{
	$sql.= " AND c.ref LIKE '%".$db->escape($sref)."%'";
}
if (!empty($sref_client))
{
	$sql.= ' AND c.ref_customer LIKE \'%'.$db->escape($sref_client).'%\'';
}

$sql.= ' AND c.fk_statut in (1,2,3)';

//check if the command is factured
$sql.= ' AND co.facture = 0';

//check if this expedition is factured
$sql .= ' AND (SELECT count(ee2.rowid) FROM llx_element_element ee2 WHERE ee2.fk_source = c.rowid AND ee2.targettype = "facture" AND ee2.sourcetype = "shipping") = 0';

$sql.= " AND c.date_creation BETWEEN '".$db->idate($date_start)."' AND '".$db->idate($date_end)."'";
if(!empty($delivery_date_start)){
	$sql.= " AND c.date_delivery BETWEEN '".$db->idate($delivery_date_start)."' AND '".$db->idate($delivery_date_end)."'";
}
   
if (!empty($companies))//Hacer un in con el rango
{
	$sql.= ' AND c.fk_soc IN('.$companies.')';
}
if (!empty($categories))//Hacer un in con el rango
{
	$sql.= ' AND cat.fk_categorie in ('.$categories.')';
}

if ($search_sale > 0) {
    $sql.= ' AND s.rowid = sc.fk_soc AND sc.fk_user = ' .$search_sale;
}
if ($search_user > 0)
{
    $sql.= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='commande' AND tc.source='internal' AND ec.element_id = co.rowid AND ec.fk_socpeople = ".$search_user;
}
$sql.= ' GROUP BY c.rowid, co.fk_mode_reglement ';
$sql.= ' ORDER BY '.$sortfield.' '.$sortorder;

//print $sql;
$resql = $db->query($sql);
if ($resql) {
    $i = 0;
    $num = $db->num_rows($resql);

    while ($i < $num) {
        $objp = $db->fetch_object($resql);

        $orders_list[$i] = $objp;

        $orders_id[] = $objp->rowid;
        $companies_id[] = $objp->socid;
        $paymode_id[] = $objp->fk_mode_reglement;
        $aux = substr($objp->date_creation, 0, 7);
        $order_date[] = $aux;
        $ref_id[] = $objp->ref;

        $i++;
    }

    if ($socid) {
        $soc = new Societe($db);
        $soc->fetch($socid);
        $title = $langs->trans('ListOfSendings') . ' - ' . $soc->name;
    } else {
        $title = $langs->trans('ListOfSendings');
    }

    $param = '&socid=' . $socid . '&viewstatut=' . $viewstatut;
    if ($ordermonth) {
        $param .= '&ordermonth=' . $ordermonth;
    }
    if ($orderyear) {
        $param .= '&orderyear=' . $orderyear;
    }
    if ($deliverymonth) {
        $param .= '&deliverymonth=' . $deliverymonth;
    }
    if ($deliveryyear) {
        $param .= '&deliveryyear=' . $deliveryyear;
    }
    if ($sref) {
        $param .= '&sref=' . $sref;
    }
    if ($snom) {
        $param .= '&snom=' . $snom;
    }
    if ($sref_client) {
        $param .= '&sref_client=' . $sref_client;
    }
    if ($search_user > 0) {
        $param .= '&search_user=' . $search_user;
    }
    if ($search_sale > 0) {
        $param .= '&search_sale=' . $search_sale;
    }
    if ($date_start) {
        $param .= '&date_startmonth=' . $_REQUEST['date_startmonth'] . '&date_startday=' . $_REQUEST['date_startday'] . '&date_startyear=' . $_REQUEST['date_startyear'];
    }
    if ($date_end) {
        $param .= '&date_endmonth=' . $_REQUEST['date_endmonth'] . '&date_endday=' . $_REQUEST['date_endday'] . '&date_endyear=' . $_REQUEST['date_endyear'];
    }
    if ($company_start) {
        $param .= '&company_start=' . $company_start;
    }
    if ($company_end) {
        $param .= '&company_end=' . $company_end;
    }
    if ($category_start) {
        $param .= '&category_start=' . $category_start;
    }
    if ($category_end) {
        $param .= '&category_end=' . $category_end;
    }


    $_SESSION['orders'] = $orders_id;
    $_SESSION['companies'] = $companies_id;
    $_SESSION['paymode'] = $paymode_id;
    $_SESSION['ref'] = $ref_id;
    $_SESSION['order_date'] = $order_date;

    print '<div class="tabsAction">';

    print ' <input style="margin-left:20px" type="submit" class="butAction" name="buttonInvoice" value="' . $langs->trans('MassInvoicing') . '">';

    print '</div>';


    $num = $db->num_rows($resql);
    print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', '');
    $i = 0;

    // Lignes des champs de filtre
    print '<input type="hidden" name="viewstatut" value="' . $viewstatut . '">';

    print '<table class="noborder" width="100%">';

    $moreforfilter = '';


    if (!empty($moreforfilter)) {
        print '<tr class="liste_titre">';
        print '<td class="liste_titre" colspan="9">';
        print $moreforfilter;
        print '</td></tr>';
    }

    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans('Ref'), $_SERVER['PHP_SELF'], 'c.ref', '', $param, 'width="25%"', $sortfield,
        $sortorder);
    print_liste_field_titre($langs->trans('Company'), $_SERVER['PHP_SELF'], 's.nom', '', $param, '', $sortfield,
        $sortorder);
    print_liste_field_titre($langs->trans('RefCustomerOrder'), $_SERVER['PHP_SELF'], 'c.ref_customer', '', $param, '',
        $sortfield, $sortorder);
    print_liste_field_titre($langs->trans('OrderDate'), $_SERVER['PHP_SELF'], 'c.date_creation', '', $param,
        'align="right"', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans('DeliveryDate'), $_SERVER['PHP_SELF'], 'c.date_delivery', '', $param,
        'align="right"', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans('Status'), $_SERVER['PHP_SELF'], 'c.fk_statut', '', $param, 'align="right"',
        $sortfield, $sortorder);
    if ($conf->use_javascript_ajax) {
        print '<td align="left" width="80"><a onClick="checkAll()">' . $langs->trans('All') . '</a> / <a onClick="checkNone()">' . $langs->trans('None') . '</a></td>';
    } else {
        print '	<td align="left" width="80">' . $langs->trans('Sel.') . '</td>';
    }
    print '</tr>';
    print '<tr class="liste_titre">';
    print '<td class="liste_titre">';
    print '<input class="flat" size="10" type="text" name="sref" value="' . $sref . '">';
    print '</td><td class="liste_titre">&nbsp';
    print '</td><td class="liste_titre" align="left">';
    print '<input class="flat" type="text" size="10" name="sref_client" value="' . $sref_client . '">';
    print '</td><td class="liste_titre">&nbsp;';
    print '</td><td class="liste_titre">&nbsp;';
    print '</td><td class="liste_titre">&nbsp;';
    print '</td><td align="right" class="liste_titre">';
    print '<input type="image" class="liste_titre" name="button_search" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png"  value="' . dol_escape_htmltag($langs->trans('Search')) . '" title="' . dol_escape_htmltag($langs->trans('Search')) . '">';
    print '</td></tr>';

    $var = true;

    $generic_commande = new Expedition($db);
    while ($i < $num) {
        $var = !$var;
        print '<tr ' . $bc[$var ? 1 : 0] . '>';
        print '<td nowrap="nowrap">';

        $generic_commande->id = $orders_list[$i]->rowid;
        $generic_commande->ref = $orders_list[$i]->ref;

        print '<table class="nobordernopadding"><tr class="nocellnopadd">';
        print '<td class="nobordernopadding" nowrap="nowrap">';
        print $generic_commande->getNomUrl(1);
        print '</td>';

        print '</tr></table>';

        print '</td>';

        // Company
        $companystatic->id = $orders_list[$i]->socid;
        $companystatic->name = $orders_list[$i]->nom;
        $companystatic->client = $orders_list[$i]->client;
        print '<td>';
        print $companystatic->getNomUrl(1, 'customer');

        print '</td>';

        print '<td>' . $orders_list[$i]->ref_customer . '</td>';

        // Order date
        $y = dol_print_date($db->jdate($orders_list[$i]->date_creation), '%Y');
        //$m = dol_print_date($db->jdate($orders_list[$i]->date_commande),'%m');
        $ml = dol_print_date($db->jdate($orders_list[$i]->date_creation), '%B');
        $d = dol_print_date($db->jdate($orders_list[$i]->date_creation), '%d');
        print '<td align="right">';
        print $d . ' ' . $ml . ' ' . $y;
        print '</td>';

        // Delivery date
        $y = dol_print_date($db->jdate($orders_list[$i]->date_delivery), '%Y');
        //$m = dol_print_date($db->jdate($orders_list[$i]->date_livraison),'%m');
        $ml = dol_print_date($db->jdate($orders_list[$i]->date_delivery), '%B');
        $d = dol_print_date($db->jdate($orders_list[$i]->date_delivery), '%d');
        print '<td align="right">';
        print $d . ' ' . $ml . ' ' . $y;
        print '</td>';

        // Statut
        print '<td align="right" nowrap="nowrap">' . $generic_commande->LibStatut($orders_list[$i]->fk_statut,
                5) . '</td>';

        // Checkbox
        print '	<td align="center">';

        print '<input name="orders[]" class="checkElement" value=' . $i . ' type="checkbox" checked/>';
        print '	</td>';

        print '</tr>';

        $i++;
    }
    print '</table>';

    print '</form>';

    if (isset($_POST['buttonInvoice']) && !$_REQUEST['cancel']) {
        $formquestionmassinvoicing = array(
            'text' => $langs->trans('SelectGroupMode'),
            array(
                'type' => 'checkbox',
                'name' => 'groupmonth',
                'label' => $langs->trans('MonthGroup'),
                'value' => 0,
                'size' => 5
            ),
            array(
                'type' => 'checkbox',
                'name' => 'grouppaymode',
                'label' => $langs->trans('PayModeGroup'),
                'value' => 0,
                'size' => 5
            )
        );

        if ($user->rights->expedition->creer) {
            $formquestionmassinvoicing[] = array(
                'type' => 'checkbox',
                'name' => 'autocloseorders',
                'label' => $langs->trans('CloseProcessedShipmentsAutomatically'),
                'value' => 1,
                'size' => 5
            );
        }

        if (version_compare(DOL_VERSION, 3.8) >= 0) {

            if (((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->facture->creer))
                    || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->facture->invoice_advance->validate)))
                && ($conf->global->STOCK_CALCULATE_ON_BILL == 0 || !isset($conf->global->STOCK_CALCULATE_ON_BILL))
            ) {
                $formquestionmassinvoicing[] = array(
                    'type' => 'checkbox',
                    'name' => 'validateinvoice',
                    'label' => $langs->trans('ValidateInvoicesAutomatically'),
                    'value' => 1,
                    'size' => 5
                );
            }

        } else {
            if ($user->rights->facture->valider && $conf->global->STOCK_CALCULATE_ON_BILL == 0) {
                $formquestionmassinvoicing[] = array(
                    'type' => 'checkbox',
                    'name' => 'validateinvoice',
                    'label' => $langs->trans('ValidateInvoicesAutomatically'),
                    'value' => 1,
                    'size' => 5
                );
            }
        }

        print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('MassInvoicing'),
            $langs->trans('ConfirmMassInvoicing'), 'confirm_massinvoicing', $formquestionmassinvoicing, 'yes', 1, 250,
            420);
    }

    $db->free($resql);
}
else
{
	dol_print_error($db);
}

llxFooter();

$db->close();
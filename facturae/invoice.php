<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012 Juanjo Menent 		<jmenent@2byte.es>
 * Copyright (C) 2013-2015 Ferran Marcet 		<fmarcet@2byte.es>
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
 *	\file       htdocs/compta/prelevement/fiche.php
 *	\ingroup    prelevement
 *	\brief      Fiche prelevement
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res) $res=@include("../../main.inc.php");	// For "custom" directory
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
}
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
dol_include_once('/facturae/class/facturae.class.php');

global $langs, $user, $db;

$langs->load('bills');
$langs->load('companies');
$langs->load('compta');
$langs->load('products');
$langs->load('banks');
$langs->load('main');

if (!$user->rights->facturae->read)
accessforbidden();

// Security check
if ($user->socid > 0) accessforbidden();

// Get supervariables
$action = GETPOST('action','alpha');
$id = GETPOST('id','int');
$ref = GETPOST('ref','alpha');


/*
 * Actions
 */
if($action == 'createfile'){
	
	$startday    =GETPOST('startdateday','int');
	$startmonth  =GETPOST('startdatemonth','int');
	$startyear   =GETPOST('startdateyear','int');
	if(strlen($startmonth) == 1) $startmonth='0'.$startmonth;
	if(strlen($startday) == 1) $startday='0'.$startday;
	
	$endday    =GETPOST('enddateday','int');
	$endmonth  =GETPOST('enddatemonth','int');
	$endyear   =GETPOST('enddateyear','int');
	if(strlen($endmonth) == 1) $endmonth='0'.$endmonth;
	if(strlen($endday) == 1) $endday='0'.$endday;
	
	$facturae = new Facturae($db);
	$facturae->reason = GETPOST('reason','alpha');
	$facturae->criterio = GETPOST('criterio','alpha');
	$facturae->type = GETPOST('facturetype','alpha');
	$startdate = $startyear.'-'.$startmonth.'-'.$startday;
	$enddate = $endyear.'-'.$endmonth.'-'.$endday;
	$facturae->startdate = $startdate;
	$facturae->enddate = $enddate;
		
	$res = $facturae->generateFile($id);
	
}


/*
 * View
 */

if ($user->socid > 0 || empty($user->rights->facturae->read))
{
	accessforbidden();
}

$object = new Facture($db);
$form = new Form($db);

$helpurl='EN:Module_Facturae|FR:Module_Facturae_FR|ES:M&oacute;dulo_Facturae';
llxHeader('',$langs->trans("InvoiceCustomer"), $helpurl);

dol_htmloutput_events();

if ($id > 0 || $ref)
{
	$object->fetch($id,$ref);
	$object->fetch_thirdparty();

	$head = facture_prepare_head($object);
	dol_fiche_head($head, 'facturae', $langs->trans("InvoiceCustomer"), '', 'bill');

	if (GETPOST('error','alpha')!='')
	{
		print '<div class="error">'.$bon->ReadError(GETPOST('error','alpha')).'</div>';
	}

	/*if ($action == 'credite')
	{
		$ret=$form->form_confirm("fiche.php?id=".$bon->id,$langs->trans("ClassCredited"),$langs->trans("ClassCreditedConfirm"),"confirm_credite",'',1,1);
		if ($ret == 'html') print '<br>';
	}*/

	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

	// Ref
	print '<tr><td width="20%">'.$langs->trans('Ref').'</td>';
	print '<td colspan="3">';
	$morehtmlref='';
	$discount=new DiscountAbsolute($db);
	$result=$discount->fetch(0,$object->id);
	if ($result > 0)
	{
		$morehtmlref=' ('.$langs->trans("CreditNoteConvertedIntoDiscount",$discount->getNomUrl(1,'discount')).')';
	}
	if ($result < 0)
	{
		dol_print_error('',$discount->error);
	}
	print $form->showrefnav($object, 'ref', $linkback, 1, 'facnumber', 'ref', $morehtmlref);
	print '</td></tr>';

	// Ref customer
	print '<tr><td width="20%">';
    print '<table class="nobordernopadding" width="100%"><tr><td>';
    print $langs->trans('RefCustomer');
    print '</td>';
    print '</tr></table>';
    print '</td>';
    print '<td colspan="5">';
    print $object->ref_client;
	print '</td></tr>';

	// Customer
	print "<tr><td>".$langs->trans("Company")."</td>";
	print '<td colspan="3">'.$object->thirdparty->getNomUrl(1,'compta').'</td></tr>';
	print "</table>";

	//dol_fiche_end();

	print '<br>';
	
	if(dol_is_file($conf->facturae->dir_output.'/'.$object->ref.'.xml')){

		print '<table class="border" width="100%"><tr><td width="20%">';
		print $langs->trans("FacturaeFile").'</td><td>';
		$relativepath = $object->ref.'.xml';
		print '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?type=text/plain&amp;modulepart=facturae&amp;file='.urlencode($relativepath).'">'.$relativepath.'</a>';
		print '</td></tr></table>';
	}

	dol_fiche_end();


	if( !empty($user->rights->facturae->create)){

		print '<form method="post" name="createfile" action="invoice.php?id='.$object->id.'" enctype="multipart/form-data">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="createfile">';
		print '<table class="border" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("FacturaeParameters").'</td></tr>';
		print '<tr><td width="20%">'.$langs->trans("FactureType").'</td><td>';
		$factype['FC']='Factura completa';
		$factype['FA']='Factura simplificada';
		print Form::selectarray('facturetype', $factype);
		print '</td></tr>';
		
		if($object->type){
			print '<tr><td width="20%">'.$langs->trans("RectReason").'</td><td>';
			 
			$reason['01']=$langs->trans("FacturaeRect01");
			$reason['02']=$langs->trans("FacturaeRect02");
			$reason['03']=$langs->trans("FacturaeRect03");
			$reason['04']=$langs->trans("FacturaeRect04");
			$reason['05']=$langs->trans("FacturaeRect05");
			$reason['06']=$langs->trans("FacturaeRect06");
			$reason['07']=$langs->trans("FacturaeRect07");
			$reason['08']=$langs->trans("FacturaeRect08");
			$reason['09']=$langs->trans("FacturaeRect09");
			$reason['10']=$langs->trans("FacturaeRect10");
			$reason['11']=$langs->trans("FacturaeRect11");
			$reason['12']=$langs->trans("FacturaeRect12");
			$reason['13']=$langs->trans("FacturaeRect13");
			$reason['14']=$langs->trans("FacturaeRect14");
			$reason['15']=$langs->trans("FacturaeRect15");
			$reason['16']=$langs->trans("FacturaeRect16");
			$reason['80']=$langs->trans("FacturaeRect80");
			$reason['81']=$langs->trans("FacturaeRect81");
			$reason['82']=$langs->trans("FacturaeRect82");
			$reason['83']=$langs->trans("FacturaeRect83");
			$reason['84']=$langs->trans("FacturaeRect84");
			$reason['85']=$langs->trans("FacturaeRect85");
			
			print Form::selectarray('reason', $reason);
			print '</td></tr>';
			
			$criterio['01']=$langs->trans("FacturaeCrit01");
			$criterio['02']=$langs->trans("FacturaeCrit02");
			$criterio['03']=$langs->trans("FacturaeCrit03");
			$criterio['04']=$langs->trans("FacturaeCrit04");
				
			print '<tr><td width="20%">'.$langs->trans("CorrectionMethod").'</td><td>';
			print Form::selectarray('criterio', $criterio);
			print '</td></tr>';	
			
			print '<tr><td width="20%">'.$langs->trans("FiscalPeriod").'</td><td>';
			print $form->select_date('','startdate','','','',"userfile",1,1).' - '.$form->select_date('','enddate','','','',"userfile",1,1);
			print '</td></tr>';
			
			
		}
	
		print '</table><br>';
		print '<span class="center"><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("CreateFile")).'"></span>';
		print '</form>';
	}
	
}


llxFooter();

$db->close();
?>

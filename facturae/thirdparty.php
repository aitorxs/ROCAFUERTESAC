<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		<patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013-2015 Ferran Marcet        <fmarcet@2byte.es>
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
 *  \file       htdocs/societe/agenda.php
 *  \ingroup    societe
 *  \brief      Page of third party events
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res) $res=@include("../../main.inc.php");	// For "custom" directory
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
dol_include_once('/facturae/class/facturae.class.php');

$langs->load("companies");
$langs->load("banks");
$langs->load("bills");

// Security check
$socid = GETPOST('socid','int');
$action = GETPOST('action','alpha');
$cancel = GETPOST('cancel','alpha');

if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'societe', $socid, '&societe');

$facturae = new FacturaeThirdparty($db);
$facturae->fetch($socid);

$persontype = (GETPOST("person_type")?GETPOST("person_type"):$facturae->person_type);
$administrative = GETPOST("administrative");
$administrative = ($administrative == 'no'?0:($administrative == 'yes'?1:$facturae->administrative));

/*
 *	Actions
 */
if($action == 'update'){
	
	$facturae->person_type = $persontype;
	$facturae->residence_type = GETPOST('residence_type','alpha');
	$facturae->contable = GETPOST('contable','alpha');
	$facturae->gestor = GETPOST('gestor','alpha');
	$facturae->tramitador = GETPOST('tramitador','alpha');
    $facturae->comprador = GETPOST('comprador','alpha');
	$facturae->name = GETPOST('name','alpha');
	$facturae->first_surname = GETPOST('first_surname','alpha');
	$facturae->second_surname = GETPOST('second_surname','alpha');
	$facturae->contact_name = GETPOST('contact_name','alpha');
	$facturae->administrative = $administrative;
		
	if($facturae->fk_soc)
		$res = $facturae->update();
	else{ 
		$facturae->fk_soc = $socid;
		$res = $facturae->create($user);
	}
	if($res < 0)
		setEventMessage($langs->trans("DataError"),"errors");
	else{
		setEventMessage($langs->trans("DataOK"));
	}
}

/*
 *	View
 */

$form = new Form($db);

// Protection if external user
if ($user->socid > 0 || empty($user->rights->facturae->read))
{
	accessforbidden();
}

/*
 * Fiche categorie de client et/ou fournisseur
 */
if ($socid)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	$langs->load("companies");


	$soc = new Societe($db);
	$result = $soc->fetch($socid);
	
	
	/*$helpurl='EN:Module_SEPA|FR:Module_SEPA_FR|ES:M&oacute;dulo_SEPA';*/
	llxHeader('',''/*,$helpurl*/);
	
	$head = societe_prepare_head($soc);
	dol_fiche_head($head, 'facturae', $langs->trans("ThirdParty"), 0, 'company');
	
	dol_htmloutput_events();
		
	if($action == "edit"){
		print '<form method="post" name="setup" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="socid" value="'.$socid.'">';
		print '<input type="hidden" name="action" value="update">';
	}

	print '<table class="border" width="100%">';

	print '<tr><td width="25%">'.$langs->trans("ThirdPartyName").'</td><td colspan="3">';
	print $form->showrefnav($soc,'socid','',($user->socid?0:1),'rowid','nom');
	print '</td></tr>';

    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
        print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$soc->prefix_comm.'</td></tr>';
    }

	if ($soc->client)
	{
		print '<tr><td>';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $soc->code_client;
		if ($soc->check_codeclient() <> 0) print ' <span class="error">('.$langs->trans("WrongCustomerCode").')</span>';
		print '</td></tr>';
	}

	if ($soc->fournisseur)
	{
		print '<tr><td>';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $soc->code_fournisseur;
		if ($soc->check_codefournisseur() <> 0) print ' <span class="error">('.$langs->trans("WrongSupplierCode").')</span>';
		print '</td></tr>';
	}

	if (! empty($conf->barcode->enabled))
	{
		print '<tr><td>'.$langs->trans('Gencod').'</td><td colspan="3">'.$soc->barcode.'</td></tr>';
	}

	print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">";
	dol_print_address($soc->address, 'gmap', 'thirdparty', $soc->id);
	print "</td></tr>";

	// Zip / Town
	print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$soc->zip."</td>";
	print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$soc->town."</td></tr>";

	// Country
	if ($soc->country) {
		print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
		$img=picto_from_langcode($soc->country_code);
		print ($img?$img.' ':'');
		print $soc->country;
		print '</td></tr>';
	}

	// EMail
	print '<tr><td>'.$langs->trans('EMail').'</td><td colspan="3">';
	print dol_print_email($soc->email,0,$soc->id,'AC_EMAIL');
	print '</td></tr>';

	// Web
	print '<tr><td>'.$langs->trans('Web').'</td><td colspan="3">';
	print dol_print_url($soc->url);
	print '</td></tr>';

	// Phone / Fax
	print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($soc->phone,$soc->country_code,0,$soc->id,'AC_TEL').'</td>';
	print '<td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($soc->fax,$soc->country_code,0,$soc->id,'AC_FAX').'</td></tr>';

	/*
     * Barre d'action
     */

	if($action == 'edit'){
				
		print "\n".'<script type="text/javascript" language="javascript">';
		print '$(document).ready(function () {
		$("#person_type").change(function() {
		document.setup.action.value="edit";
		document.setup.socid.value="'.$socid.'";
		document.setup.submit();
		});
		$("#administrative").change(function() {
		document.setup.action.value="edit";
		document.setup.socid.value="'.$socid.'";
		document.setup.submit();
		});
		});';
		print '</script>'."\n";
		
		print '<tr><td>'.$langs->trans("PersonTypeCode").'</td>';
		print '<td colspan="3" align="left">';
		$type['F']=$langs->trans('FacturaeF');
		$type['J']=$langs->trans('FacturaeJ');
		print Form::selectarray('person_type', $type, (GETPOST("person_type")?GETPOST("person_type"):$facturae->person_type),1);
		print '</td>';
		print '</tr>';
		
		print '<tr>';
		print '<td>'.$langs->trans("ResidenceTypeCode").'</td>';
		print '<td colspan="3" align="left">';
		$resi['R']=$langs->trans('FacturaeR');
		$resi['E']=$langs->trans('FacturaeE');
		$resi['U']=$langs->trans('FacturaeU');
		print Form::selectarray('residence_type', $resi, (GETPOST("residence_type","alpha")?GETPOST("residence_type","alpha"):$facturae->residence_type),1);
		print '</td>';
		print '</tr>';
		
		print '<tr>';
		print '<td>'.$langs->trans("Name").'</td>';
		print '<td colspan="3" align="left">';
		print '<input type="text" class="flat" name="name" value="'. (GETPOST("name","alpha")?GETPOST("name","alpha"):$facturae->name).'" size="50">';
		print '</td>';
		print '</tr>';
		
		if($persontype == 'F'){
			print '<tr>';
			print '<td>'.$langs->trans("FirstSurname").'</td>';
			print '<td colspan="3" align="left">';
			print '<input type="text" class="flat" name="first_surname" value="'. (GETPOST("first_surname","alpha")?GETPOST("first_surname","alpha"):$facturae->first_surname).'" size="50">';
			print '</td>';
			print '</tr>';
			
			print '<tr>';
			print '<td>'.$langs->trans("SecondSurname").'</td>';
			print '<td colspan="3" align="left">';
			print '<input type="text" class="flat" name="second_surname" value="'. (GETPOST("second_surname","alpha")?GETPOST("second_surname","alpha"):$facturae->second_surname).'" size="50">';
			print '</td>';
			print '</tr>';
		}
		print '<tr>';
		print '<td>'.$langs->trans("ContactName").'</td>';
		print '<td colspan="3" align="left">';
		print '<input type="text" class="flat" name="contact_name" value="'. (GETPOST("contact_name","alpha")?GETPOST("contact_name","alpha"):$facturae->contact_name).'" size="50">';
		print '</td>';
		print '</tr>';
		
		print '<tr>';
		print '<td>'.$langs->trans("Administrative").'</td>';
		print '<td colspan="3" align="left">';
		print $form->selectyesno('administrative',$administrative,0);
		print '</td>';
		print '</tr>';
		
		if($administrative){
			print '<tr>';
			print '<td>'.$langs->trans("CentreContable").'</td>';
			print '<td colspan="3" align="left">';
			print '<input type="text" class="flat" name="contable" value="'. (GETPOST("contable","alpha")?GETPOST("contable","alpha"):$facturae->contable).'" size="50">';
			print '</td>';
			print '</tr>';
			
			print '<tr>';
			print '<td>'.$langs->trans("CentreGestor").'</td>';
			print '<td colspan="3" align="left">';
			print '<input type="text" class="flat" name="gestor" value="'. (GETPOST("gestor","alpha")?GETPOST("gestor","alpha"):$facturae->gestor).'" size="50">';
			print '</td>';
			print '</tr>';
			
			print '<tr>';
			print '<td>'.$langs->trans("CentreTramitador").'</td>';
			print '<td colspan="3" align="left">';
			print '<input type="text" class="flat" name="tramitador" value="'. (GETPOST("tramitador","alpha")?GETPOST("tramitador","alpha"):$facturae->tramitador).'" size="50">';
			print '</td>';
			print '</tr>';

            print '<tr>';
            print '<td>'.$langs->trans("CentreComprador").'</td>';
            print '<td colspan="3" align="left">';
            print '<input type="text" class="flat" name="comprador" value="'. (GETPOST("comprador","alpha")?GETPOST("comprador","alpha"):$facturae->comprador).'" size="50">';
            print '</td>';
            print '</tr>';
		}
		
		print '</table>';

		print '<br><center><input type="submit" name="save" class="button" value="'.$langs->trans("Save").'"></center>';
		
		print '</form>';
		
	}
	else{
	    if (! empty($user->rights->facturae->create))
	    {
	    	
	    	print '<tr><td>'.$langs->trans("PersonTypeCode").'</td>';
	    	print '<td colspan="3" align="left">';
	    	if($facturae->person_type == 'F'){
	    		print $langs->trans('FacturaeF');
	    	}else if($facturae->person_type == 'J'){
	    		print $langs->trans('FacturaeJ');
	    	}
	    	print '</td>';
	    	print '</tr>';
	    	
	    	print '<tr>';
	    	print '<td>'.$langs->trans("ResidenceTypeCode").'</td>';
	    	print '<td colspan="3" align="left">';
	    	if ($facturae->residence_type == 'R')
	    		print $langs->trans('FacturaeR');
	    	else if ($facturae->residence_type == 'E')
	    		print $langs->trans('FacturaeE');
	    	else if ($facturae->residence_type == 'U')
	    		print $langs->trans('FacturaeU');
	    	print '</td>';
	    	print '</tr>';
	    	
	    	print '<tr>';
	    	print '<td>'.$langs->trans("Name").'</td>';
	    	print '<td colspan="3" align="left">';
	    	print $facturae->name;
	    	print '</td>';
	    	print '</tr>';
	    	
	    	if($persontype == 'F'){
	    		print '<tr>';
	    		print '<td>'.$langs->trans("FirstSurname").'</td>';
	    		print '<td colspan="3" align="left">';
	    		print $facturae->first_surname;
	    		print '</td>';
	    		print '</tr>';
	    			
	    		print '<tr>';
	    		print '<td>'.$langs->trans("SecondSurname").'</td>';
	    		print '<td colspan="3" align="left">';
	    		print $facturae->second_surname;
	    		print '</td>';
	    		print '</tr>';
	    	}
	    	print '<tr>';
	    	print '<td>'.$langs->trans("ContactName").'</td>';
	    	print '<td colspan="3" align="left">';
	    	print $facturae->contact_name;
	    	print '</td>';
	    	print '</tr>';
	    	
	    	print '<tr>';
	    	print '<td>'.$langs->trans("Administrative").'</td>';
	    	print '<td colspan="3" align="left">';
	    	print yn($facturae->administrative);
	    	print '</td>';
	    	print '</tr>';
	    	
	    	if($administrative){
		    	print '<tr>';
		    	print '<td>'.$langs->trans("CentreContable").'</td>';
		    	print '<td colspan="3" align="left">';
		    	print $facturae->contable;
		    	print '</td>';
		    	print '</tr>';
		    	
		    	print '<tr>';
		    	print '<td>'.$langs->trans("CentreGestor").'</td>';
		    	print '<td colspan="3" align="left">';
		    	print $facturae->gestor;
		    	print '</td>';
		    	print '</tr>';
		    	
		    	print '<tr>';
		    	print '<td>'.$langs->trans("CentreTramitador").'</td>';
		    	print '<td colspan="3" align="left">';
		    	print $facturae->tramitador;
		    	print '</td>';
		    	print '</tr>';

                print '<tr>';
                print '<td>'.$langs->trans("CentreComprador").'</td>';
                print '<td colspan="3" align="left">';
                print $facturae->comprador;
                print '</td>';
                print '</tr>';
	    	}
	    	
	    	print '</table>';
	    	
	    	print '<div class="tabsAction">';
	        print '<a class="butAction" href="thirdparty.php?socid='.$soc->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
	        print '</div>';
	    }
	}
}


llxFooter();

$db->close();

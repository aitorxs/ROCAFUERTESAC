<?PHP
/* Copyright (C) 2011-2012 	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013-2015	   Ferran Marcet		<fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/facturae/admin/facturae.php
 *	\ingroup    facturae
 *	\brief      Page configuration of facturae module
 */

$res=@include __DIR__.'/../../main.inc.php';					// For root directory
if (! $res) $res=@include __DIR__.'/../../../main.inc.php';		// For "custom" directory

require_once(DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php');
dol_include_once('/facturae/lib/facturae.lib.php');

global $langs,$user, $conf, $db;

$langs->load('admin');
$langs->load('facturae@facturae');

// Security check
if (!$user->admin)
accessforbidden();

$persontype = (GETPOST('FACTURAE_PERSON_TYPE_CODE')?GETPOST('FACTURAE_PERSON_TYPE_CODE'):$conf->global->FACTURAE_PERSON_TYPE_CODE);//   ($_POST["FACTURAE_PERSON_TYPE_CODE"]?$_POST["FACTURAE_PERSON_TYPE_CODE"]:$conf->global->FACTURAE_PERSON_TYPE_CODE));

if (GETPOST('save'))
{
	$db->begin();

	$res=0;

	$res+=dolibarr_set_const($db,'FACTURAE_PERSON_TYPE_CODE',trim(GETPOST("FACTURAE_PERSON_TYPE_CODE")),'chaine',0,'',$conf->entity);
	$res+=dolibarr_set_const($db,'FACTURAE_RESIDENCE_TYPE_CODE',trim(GETPOST("FACTURAE_RESIDENCE_TYPE_CODE")),'chaine',0,'',$conf->entity);
	$res+=dolibarr_set_const($db,'FACTURAE_EMISOR_NAME',trim(GETPOST("FACTURAE_EMISOR_NAME")),'chaine',0,'',$conf->entity);
	$res+=dolibarr_set_const($db,'FACTURAE_EMISOR_FIRST_SURNAME',trim(GETPOST("FACTURAE_EMISOR_FIRST_SURNAME")),'chaine',0,'',$conf->entity);
	$res+=dolibarr_set_const($db,'FACTURAE_EMISOR_SECOND_SURNAME',trim(GETPOST("FACTURAE_EMISOR_SECOND_SURNAME")),'chaine',0,'',$conf->entity);
	$res+=dolibarr_set_const($db,'FACTURAE_CONTACT_NAME',trim(GETPOST("FACTURAE_CONTACT_NAME")),'chaine',0,'',$conf->entity);
	$res+=dolibarr_set_const($db,'FACTURAE_TAX_TYPE',trim(GETPOST("FACTURAE_TAX_TYPE")),'chaine',0,'',$conf->entity);
	
	if ($res >= 6)
	{
		$db->commit();
		setEventMessage($langs->trans('SetupSaved'));
	}
	else
	{
		$db->rollback();
		setEventMessage($langs->trans('Error'),'errors');
		header('Location: '.$_SERVER['PHP_SELF']);
		exit;
	}
}

/*
 *	View
 */

$html=new Form($db);

//$helpurl='EN:Module_SEPA|FR:Module_SEPA_FR|ES:M&oacute;dulo_SEPA';
llxHeader('',$langs->trans('FacturaeSetup')/*, $helpurl*/);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans('BackToModuleList').'</a>';

print load_fiche_titre($langs->trans('FacturaeSetup'),$linkback,'setup');

$head = facturaeadmin_prepare_head();
dol_fiche_head($head, 'configuration', $langs->trans('Facturae'), 0, 'payment');

print "\n".'<script type="text/javascript" language="javascript">';
print '$(document).ready(function () {
$("#FACTURAE_PERSON_TYPE_CODE").change(function() {
document.setup.action.value="create";
document.setup.submit();
});
});';
print '</script>'."\n";

print '<form method="post" name="setup" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="30%">'.$langs->trans('Parameter').'</td>';
print '<td width="40%">'.$langs->trans('Value').'</td>';
print "</tr>\n";

print '<tr class="impair"><td>'.$langs->trans('PersonTypeCode').'</td>';
print '<td align="left">';
$type['F']=$langs->trans('FacturaeF');
$type['J']=$langs->trans('FacturaeJ');
print Form::selectarray('FACTURAE_PERSON_TYPE_CODE', $type, (GETPOST('FACTURAE_PERSON_TYPE_CODE')?GETPOST('FACTURAE_PERSON_TYPE_CODE'):$conf->global->FACTURAE_PERSON_TYPE_CODE),1);
print '</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bc[$var?1:0].'>';
print '<td>'.$langs->trans("ResidenceTypeCode").'</td>';
print '<td align="left">';
$resi['R']=$langs->trans('FacturaeR');
$resi['E']=$langs->trans('FacturaeE');
$resi['U']=$langs->trans('FacturaeU');
print $html->selectarray('FACTURAE_RESIDENCE_TYPE_CODE', $resi, (GETPOST("FACTURAE_RESIDENCE_TYPE_CODE","alpha")?GETPOST("FACTURAE_RESIDENCE_TYPE_CODE","alpha"):$conf->global->FACTURAE_RESIDENCE_TYPE_CODE),1);
print '</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bc[$var?1:0].'>';
print '<td>'.$langs->trans("Name").'</td>';
print '<td align="left">';
print '<input type="text" class="flat" name="FACTURAE_EMISOR_NAME" value="'. (GETPOST("FACTURAE_EMISOR_NAME","alpha")?GETPOST("FACTURAE_EMISOR_NAME","alpha"):$conf->global->FACTURAE_EMISOR_NAME).'" size="50">';
print '</td>';
print '</tr>';

if($persontype == 'F'){
	$var=!$var;
	print '<tr '.$bc[$var?1:0].'>';
	print '<td>'.$langs->trans("FirstSurname").'</td>';
	print '<td align="left">';
	print '<input type="text" class="flat" name="FACTURAE_EMISOR_FIRST_SURNAME" value="'. (GETPOST("FACTURAE_EMISOR_FIRST_SURNAME","alpha")?GETPOST("FACTURAE_EMISOR_FIRST_SURNAME","alpha"):$conf->global->FACTURAE_EMISOR_FIRST_SURNAME).'" size="50">';
	print '</td>';
	print '</tr>';
	
	$var=!$var;
	print '<tr '.$bc[$var?1:0].'>';
	print '<td>'.$langs->trans("SecondSurname").'</td>';
	print '<td align="left">';
	print '<input type="text" class="flat" name="FACTURAE_EMISOR_SECOND_SURNAME" value="'. (GETPOST("FACTURAE_EMISOR_SECOND_SURNAME","alpha")?GETPOST("FACTURAE_EMISOR_SECOND_SURNAME","alpha"):$conf->global->FACTURAE_EMISOR_SECOND_SURNAME).'" size="50">';
	print '</td>';
	print '</tr>';
}
$var=!$var;
print '<tr '.$bc[$var?1:0].'>';
print '<td>'.$langs->trans('ContactName').'</td>';
print '<td align="left">';
print '<input type="text" class="flat" name="FACTURAE_CONTACT_NAME" value="'. (GETPOST('FACTURAE_CONTACT_NAME','alpha')?GETPOST('FACTURAE_CONTACT_NAME','alpha'):$conf->global->FACTURAE_CONTACT_NAME).'" size="50">';
print '</td>';
print '</tr>';

print '<tr><td width="20%">'.$langs->trans('TaxType').'</td><td>';
$taxtype['01']='IVA';
$taxtype['03']='IGIC';
print $html->selectarray('FACTURAE_TAX_TYPE', $taxtype, (GETPOST('FACTURAE_TAX_TYPE','alpha')?GETPOST('FACTURAE_TAX_TYPE','alpha'):$conf->global->FACTURAE_TAX_TYPE));
print '</td></tr>';

print '</table>';

print '<br><span class="center"><input type="submit" name="save" class="button" value="'.$langs->trans('Save').'"></span>';

print '</form>';

dol_htmloutput_events();

$db->close();

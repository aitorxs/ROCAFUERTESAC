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

global $langs, $db, $conf;

$res=@include("../../main.inc.php");					// For root directory
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
dol_include_once("/facturae/lib/facturae.lib.php");
dol_include_once("/facturae/class/facturaepayments.class.php");

$langs->load("admin");
$langs->load("bills");
$langs->load("facturae@facturae");

// Security check
if (!$user->admin)
accessforbidden();

/*$fac_pay = new Facturaepayments($db);
$fac_pay->*/

if (GETPOST("save"))
{
	$db->begin();
	$i=0;
	$error = 0;
	$pays = GETPOST('pay','array');
	foreach ($pays as $key => $value) {
		if($value > 0){
			$sql = "UPDATE ".MAIN_DB_PREFIX."facturae_payments SET";
			
			$sql.= " fk_payment=".$value;
					
			$sql.= " WHERE rowid=".$key;
			$resql = $db->query($sql);
			
			if(!$resql){
				$error++;
			}
		}
		
		$i++;
	}
	
	if ($error)
	{
		$db->rollback();
		setEventMessage($langs->trans("Error"),"errors");
		
	}
	else
	{
		$db->commit();
		setEventMessage($langs->trans("SetupSaved"));
	}
}

/*
 *	View
 */

$form=new Form($db);

//$helpurl='EN:Module_SEPA|FR:Module_SEPA_FR|ES:M&oacute;dulo_SEPA';
llxHeader('',$langs->trans("FacturaeSetup")/*, $helpurl*/);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print_fiche_titre($langs->trans("FacturaeSetup"),$linkback,'setup');

$head = facturaeadmin_prepare_head();
dol_fiche_head($head, 'payments', $langs->trans("Facturae"), 0, 'payment');

$sql = "SELECT rowid, fk_payment, facturae_cod FROM ".MAIN_DB_PREFIX."facturae_payments WHERE entity = ".$conf->entity;
$resql = $db->query($sql);

$i = 0;

print '<form method="post" name="setup" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="save">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="30%">'.$langs->trans("Parameter").'</td>';
print '<td width="40%">'.$langs->trans("Value").'</td>';
print "</tr>\n";

while($i < 19){
	$obj = $db->fetch_object($resql);
	$var=!$var;
	print '<tr '.$bc[$var?1:0].'>';
	print '<td>'.$langs->trans("FacturaePay".$obj->facturae_cod).'</td>';
	print '<td align="left">';
	$form->select_types_paiements($obj->fk_payment, 'pay['.$obj->rowid.']','',0,1);
	print '</td>';
	print '</tr>';
	
	$i++;
}

print '</table>';

print '<br><center><input type="submit" name="save" class="button" value="'.$langs->trans("Save").'"></center>';

print '</form>';

dol_htmloutput_events();

$db->close();
?>

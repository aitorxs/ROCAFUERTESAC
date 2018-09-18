<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011 	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013-2017 Ferran Marcet        <fmarcet@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/cashdesk/admin/cashdesk.php
 *	\ingroup    cashdesk
 *	\brief      Setup page for cashdesk module
 */

$res=@include '../../main.inc.php';                                   // For root directory
if (! $res) {$res=@include '../../../main.inc.php';}                // For "custom" directory

require_once DOL_DOCUMENT_ROOT. '/core/lib/admin.lib.php';
dol_include_once('/massorders/lib/massorders.lib.php');

global $user, $langs, $conf, $db;

// Security check
if (!$user->admin) {
    accessforbidden();
}

$langs->load('admin');
$langs->load('bills');
$langs->load('massorders@massorders');


/*
 * Actions
 */

if (GETPOST('action') === 'set') {
    $db->begin();
    $res = dolibarr_set_const($db, 'MASSO_PAY_MODE', GETPOST('MASSO_PAY_MODE'), 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $error++;
    }

    $res = dolibarr_set_const($db, 'MASSO_PAY_COND', GETPOST('MASSO_PAY_COND'), 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $error++;
    }

    $res = dolibarr_set_const($db, 'MASSO_SHOW_ORIGIN', GETPOST('MASSO_SHOW_ORIGIN'), 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $error++;
    }

    $res = dolibarr_set_const($db, 'MASSO_AUTO_PDF', GETPOST('MASSO_AUTO_PDF'), 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $error++;
    }

    $res = dolibarr_set_const($db, 'MASSO_AUTO_MAIL', GETPOST('MASSO_AUTO_MAIL'), 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $error++;
    }

    $res = dolibarr_set_const($db, 'MASSO_WITHDRAWAL', GETPOST('MASSO_WITHDRAWAL'), 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $error++;
    }

    if (!$error) {
        $db->commit();
        setEventMessage($langs->trans('SetupSaved'));
    } else {
        $db->rollback();
        setEventMessage($langs->trans('Error'), 'errors');
    }
}

/*
 * View
 */
global $conf;

$helpurl='EN:Module_Massorders|FR:Module_Massorders_FR|ES:M&oacute;dulo_Massorders';
llxHeader('',$langs->trans('MASSOSetup'),$helpurl);

$html=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans('BackToModuleList').'</a>';
print load_fiche_titre($langs->trans('MASSOSetup'),$linkback,'title_setup');
$head = massordersadmin_prepare_head();

dol_fiche_head($head, 'configuration', $langs->trans('MassOrders'), 0, 'bill');

print load_fiche_titre($langs->trans('Options'));

// Mode
$var=true;
print '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('Parameters').'</td><td>'.$langs->trans('Value').'</td>';
print "</tr>\n";


$var=! $var;

$var=! $var;
print '<tr '.$bc[$var?1:0].'><td>';
print $langs->trans('DefaultPayMode');
print '<td colspan="2">';
print $html->select_types_paiements($conf->global->MASSO_PAY_MODE, 'MASSO_PAY_MODE');
print "</td></tr>\n";


$var=! $var;
print '<tr '.$bc[$var?1:0].'><td>';
print $langs->trans('DefaultPayCond');
print '<td colspan="2">';
print $html->select_conditions_paiements($conf->global->MASSO_PAY_COND, 'MASSO_PAY_COND');
print "</td></tr>\n";

$var=! $var;
print '<tr '.$bc[$var?1:0].'><td>';
print $langs->trans('ShowOriginRef');
print '<td colspan="2">';
print $html->selectyesno('MASSO_SHOW_ORIGIN',$conf->global->MASSO_SHOW_ORIGIN,1);
print "</td></tr>\n";

$var=! $var;
print '<tr '.$bc[$var?1:0].'><td>';
print $langs->trans('GenerateAutoPdf');
print '<td colspan="2">';
print $html->selectyesno('MASSO_AUTO_PDF',$conf->global->MASSO_AUTO_PDF,1);
print "</td></tr>\n";

$var=! $var;
print '<tr '.$bc[$var?1:0].'><td>';
print $langs->trans('AutoMail');
print '<td colspan="2">';
print $html->selectyesno('MASSO_AUTO_MAIL',$conf->global->MASSO_AUTO_MAIL,1);
print "</td></tr>\n";

$var=! $var;
print '<tr '.$bc[$var?1:0].'><td>';
print $langs->trans('AutoWithdrawal');
print '<td colspan="2">';
print $html->selectyesno('MASSO_WITHDRAWAL',$conf->global->MASSO_WITHDRAWAL,1);
print "</td></tr>\n";
	
print '</table>';
print '<br>';

print '<span class="center"><input type="submit" class="button" value="'.$langs->trans('Save').'"></span>';

print "</form>\n";

dol_htmloutput_events();

llxFooter();
$db->close();
<?php
/* Copyright (C) 2011 		Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2012-2015	Ferran Marcet <fmarcet@2byte.es>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	    \file       htdocs/pos/backend/newclose.php
 *      \ingroup    pos
 *		\brief      Page to create/view a closecash
 *		\version    $Id: fiche.php,v 1.6 2011-08-19 07:54:24 jmenent Exp $
 */

$res=@include("../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");                // For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php");
dol_include_once('/pos/class/cash.class.php');
dol_include_once('/pos/class/pos.class.php');

global $db, $conf, $langs,$user;

$langs->load("pos@pos");
$langs->load('bills');

$action = GETPOST('action','alpha');
$terminalid = GETPOST('terminalid','int');
$amountreel = GETPOST('amountreel');


if ($user->socid) $socid=$user->socid;
//$result=restrictedArea($user,'pos',$id,'pos_cash','','','rowid');

$control = new ControlCash($db, $terminalid);
$terminal = new Cash($db);

$amountteo = $control->getMoneyCash();
/*
 * Actions
 */
if (GETPOST('action','alpha') == 'add')
{
    $error=0;
    
    $data['userid'] 		= $user->id;
	$data['amount_reel'] 	= $amountreel;
	$data['amount_teoric'] 	= $amountteo;
	$data['amount_diff'] 	= $amountreel - $amountteo;
	$data['type_control'] 	= 1;

    $control->Create($data); 
	
    
    //controlar error
    if($control->errors)
    	setEventMessage("ErrCloseCash","errors");
    else{
    	setEventMessage("ErrCloseOK");
    	header("Location: ".dol_buildpath("/pos/backend/closes.php",1));
    	exit;
    }

}

/*
 * View
 */
$helpurl='EN:Module_DoliPos|FR:Module_DoliPos_FR|ES:M&oacute;dulo_DoliPos';
llxHeader('','',$helpurl);

$form = new Form($db);


/* ************************************************************************** */
/*                                                                            */
/* Affichage page en mode creation                                            */
/*                                                                            */
/* ************************************************************************** */



//$pos_class = new POS();

print load_fiche_titre($langs->trans("NewPlace"));

print "\n".'<script type="text/javascript" language="javascript">';
		print '$(document).ready(function () {
		$("#terminalid").change(function() {
			document.createclose.action.value="create";
			document.createclose.submit();
		});
		});';
		print '</script>'."\n";

print '<form action="'.$_SERVER["PHP_SELF"].'" name="createclose" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="add">';
print '<input type="hidden" name="clos" value="0">';

print '<table class="border" width="100%">';

// Name
print '<tr><td valign="top" class="fieldrequired">'.$langs->trans("Terminal").'</td>';
print '<td colspan="3">';
print $terminal->selectterminal('terminalid',$terminalid);
print '</td></tr>';

// AmountTeor
print '<tr><td valign="top">'.$langs->trans("CashMoney").'</td>';
print '<td colspan="3">';
print price($amountteo)." ".$conf->currency;
print '</td></tr>';

// AmountReel
print '<tr><td valign="top">'.$langs->trans("MoneyInCash").'</td>';
print '<td colspan="3">';
print '<input size="30" type="text" class="flat" name="amountreel" value="'.GETPOST('amountreel','alpha').'">';
print '</td></tr>';

print '<tr><td align="center" colspan="4"><input value="'.$langs->trans("CashAccount").'" type="submit" class="button"></td></tr>';
print '</form>';
print '</table>';

dol_htmloutput_events();
llxFooter();

$db->close();
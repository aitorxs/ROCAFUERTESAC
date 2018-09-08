<?php
/* Copyright (C) 2011-2012      Juanjo Menent		<jmenent@2byte.es>
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
 *      \file       htdocs/pos/backend/info.php
 *      \ingroup    pos
 *		\brief      Page des informations d'un ticket
*/

$res=@include("../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");                // For "custom" directory
dol_include_once('/pos/class/ticket.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
dol_include_once('/pos/backend/lib/ticket.lib.php');

global $langs,$user,$db;

$langs->load("pos@pos");

// Security check
$ticketid = isset($_GET["ticketid"])?$_GET["ticketid"]:'';
if ($user->socid) $socid=$user->socid;
if (!$user->rights->pos->backend)
accessforbidden();


/*
 * View
 */
$helpurl='EN:Module_DoliPos|FR:Module_DoliPos_FR|ES:M&oacute;dulo_DoliPos';
llxHeader('','',$helpurl);

$ticket = new Ticket($db);
$ticket->fetch($_GET["id"]);
$ticket->info($_GET["id"]);

$soc = new Societe($db, $ticket->socid);
$soc->fetch($ticket->socid);

$head = ticket_prepare_head($ticket);
dol_fiche_head($head, 'info', $langs->trans("Ticket"), 0, 'ticket');

print '<table width="100%"><tr><td>';
dol_print_object_info($ticket);
print '</td></tr></table>';

print '</div>';

llxFooter();

$db->close();
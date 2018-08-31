<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2017 AXeL <anass_denna@hotmail.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/about.php
 * 	\ingroup	listexportimport
 * 	\brief		This file is an example about page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/dolicalc.lib.php';

// Translations
$langs->load("dolicalc@dolicalc");

// Access control
if (! $user->admin) {
    accessforbidden();
}

/*
 * View
 */
$page_name = "About";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = DoliCalcAdminPrepareHead();
dol_fiche_head(
    $head,
    'about',
    $langs->trans("Module514000Name"),
    0,
    'dolicalc@dolicalc'
);

// About page goes here
print '<div style="float: left; margin-right: 20px;"><img src="../img/calc.png" /></div>';
print '<br/>';
print '<div>'.$langs->trans('DoliCalcAbout').'</div>';
print '<br/><br/>';

dol_fiche_end();

llxFooter();

$db->close();
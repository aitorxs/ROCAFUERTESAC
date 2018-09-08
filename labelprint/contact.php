<?php
/* Copyright (C) 2001-2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012      Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010           Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013           Florian Henry		 <florian.henry@open-concept.pro>
 * Copyright (C) 2017           Ferran Marcet        <fmarcet@2byte.es>
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
 *   \file       htdocs/contact/note.php
 *   \brief      Tab for notes on contact
 *   \ingroup    societe
 */

$res = @include("../main.inc.php");                    // For root directory
if (!$res) $res = @include("../../main.inc.php");    // For "custom" directory
require_once DOL_DOCUMENT_ROOT . '/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once("/labelprint/class/labelprint.class.php");
dol_include_once("/labelprint/lib/labelprint.lib.php");

$action = GETPOST('action');
$confirm = GETPOST('confirm', 'alpha');
$line = GETPOST('lineid', 'int');

global $langs, $user, $db, $conf;

$langs->load("companies");

// Security check
$id = GETPOST('id', 'int');
if ($user->socid) $id = $user->socid;
$result = restrictedArea($user, 'contact', $id, 'socpeople&societe');

$object = new Contact($db);
if ($id > 0) $object->fetch($id);

$permissionnote = $user->rights->societe->creer;    // Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

// Add third to list
if ($action == 'add') {
    $fac = new Contact($db);
    $fac->fetch($id);
    $error = 0;

    $label = new LabelsContacts($db);
    $label->fk_object = $fac->id;
    $label->qty = 1;

    $result = $label->create($user);
    if (!$result) $error++;

    if ($error) {
        setEventMessage($label->error, "errors");
    } else {
        setEventMessage($langs->trans("LinesContactAdded"));
    }
}

// Print list
if ($action == 'print') {
    /*$pdf=new pdfLabel();
    $pdf->createPdf();
    /*$res = $pdf->createPdf();
    if ($result)
    {
        Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
        exit;
    }*/
}

// Action select position object
if ($action == 'confirm_position' && $confirm != 'yes') {
    $action = '';
}
if ($action == 'confirm_position' && $confirm == 'yes') {
    $position = GETPOST('position', 'int');
    $res += dolibarr_set_const($db, 'LAB_START', $position, 'chaine', 0, '', $conf->entity);

    $pdf = new pdfLabelsContacts();
    $url = $pdf->createPdf();
    $action = 'confirm_truncate';
}

// Truncate list to print
if ($action == "confirm_truncate" && $confirm == 'yes') {
    $label = new LabelsContacts($db);
    $result = $label->truncate();

    if ($result > 0) {
        if (empty($url)) {
            setEventMessage($langs->trans("ListTruncated"));
            Header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&ref=' . $ref);
            exit;
        }
    } else {
        setEventMessage($label->error, "errors");
    }
}

// delete from list
if ($action == 'delete') {
    $label = new LabelsContacts($db);
    $result = $label->delete($line);

    if ($result > 0) {
        setEventMessage($langs->trans("LineContactDeleted"));
    } else {
        setEventMessage($label->error, "errors");
    }
}

// Add product to list
if ($action == 'updateline') {
    if (GETPOST('save', 'alpha') != '') {
        $qty = GETPOST('qty');
        $price_level = GETPOST('price_level', 'int');

        if ($qty <= 0) {

            $label = new LabelsContacts($db);
            $result = $label->delete($line);

        } else {
            $label = new LabelsContacts($db);
            $label->fetch($line);
            $label->qty = $qty;
            $result = $label->update();

        }

        if ($result > 0) {
            setEventMessage($langs->trans("LineContactUpdated"));
        } else {
            setEventMessage($label->error, "errors");
        }
    }
}


/*
 *	View
 */
$helpurl = 'EN:Module_Labels|FR:Module_Labels_FR|ES:M&oacute;dulo_Labels';
llxHeader('', $langs->trans("Contact"), $helpurl);

if (!empty($url)) {
    print '<script language="javascript" type="text/javascript">
        
                window.open("' . $url . '" );
    </script>';
}

$form = new Form($db);

if ($id > 0) {
    /*
     * Affichage onglets
     */
    if (!empty($conf->notification->enabled)) $langs->load("mails");

    $head = contact_prepare_head($object);

    // Confirmation to delete invoice
    $formconfirm = '';
    if ($action == 'truncate') {
        $text = $langs->trans('ConfirmTruncateList');
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $id, $langs->trans('TruncateList'), $text, 'confirm_truncate', '', 0, 1);
    }

    print $formconfirm;

    $title = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));

    dol_fiche_head($head, 'labelprint', $title, 0, 'contact');


    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';

    print '<table class="border" width="100%">';

    print '<tr><td width="25%">' . $langs->trans('Name') . '</td>';
    print '<td colspan="3">';
    print $form->showrefnav($object, 'id', '', ($user->socid ? 0 : 1), 'rowid', 'ref');
    print '</td></tr>';

    // Civility
    print '<tr><td class="titlefield">' . $langs->trans("UserTitle") . '</td><td colspan="3">';
    print $object->getCivilityLabel();
    print '</td></tr>';

    // Date To Birth
    print '<tr>';
    if (!empty($object->birthday)) {
        include_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

        print '<td class="titlefield">' . $langs->trans("DateToBirth") . '</td><td colspan="3">' . dol_print_date($object->birthday, "day");

        print ' &nbsp; ';
        $now = dol_now();
        //var_dump($birthdatearray);
        $ageyear = convertSecondToTime($now - $object->birthday, 'year') - 1970;
        $agemonth = convertSecondToTime($now - $object->birthday, 'month') - 1;
        if ($ageyear >= 2) print '(' . $ageyear . ' ' . $langs->trans("DurationYears") . ')';
        else if ($agemonth >= 2) print '(' . $agemonth . ' ' . $langs->trans("DurationMonths") . ')';
        else print '(' . $agemonth . ' ' . $langs->trans("DurationMonth") . ')';

        print '</td>';
    } else {
        print '<td>' . $langs->trans("DateToBirth") . '</td><td colspan="3">' . $langs->trans("Unknown") . "</td>";
    }
    print "</tr>";

    print "</table>";

    print '<div>';

    print '<br>';

    $cssclass = 'titlefield';
    //include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';


    dol_fiche_end();

    $formquestionposition = array(
        'text' => $langs->trans("ConfirmPosition"),
        array('type' => 'text', 'name' => 'position', 'label' => $langs->trans("HowManyPos"), 'value' => $conf->global->LAB_START, 'size' => 5)
    );

    /* ************************************************************************** */
    /*                                                                            */
    /* Barre d'action                                                             */
    /*                                                                            */
    /* ************************************************************************** */

    $sql = 'SELECT DISTINCT l.rowid id, l.qty, l.fk_user user_id,l.price_level,';
    $sql .= ' c.rowid as cid, c.firstname, c.lastname, c.zip, c.town';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'socpeople as c';
    $sql .= ' JOIN ' . MAIN_DB_PREFIX . 'labelprint as l';
    $sql .= ' WHERE l.fk_object=c.rowid AND l.typLabel=2';

    $result = $db->query($sql);
    $num = 0;
    if ($result) {
        $num = $db->num_rows($result);
    }

    //if (empty($_GET["action"]) || $_GET["action"]=='delete')
    //{
    print "<div class=\"tabsAction\">";

    if ($user->rights->societe->creer) {
        if ($num) print '<a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;action=truncate">' . $langs->trans("Truncate") . '</a>';
        print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;action=add">' . $langs->trans("AddToPrint") . '</a>';

        if ($num) {
            if ($conf->global->LABELPRINT_CONTACT_A4) {
                print '<span id="action-position" class="butAction">' . $langs->trans('PrintLabels') . '</span>' . "\n";
                print $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $id . '&ref=' . $ref, $langs->trans('SelectPosition'), $langs->trans('ConfirmSelectPosition'), 'confirm_position', $formquestionposition, 'yes', 'action-position', 170, 400);
            } else {
                print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&amp;ref=' . $ref . '&amp;action=confirm_position&amp;confirm=yes">' . $langs->trans('PrintLabels') . '</a>';
            }
        }
    }

    print "</div>";
    //}

    print '<br>';

    print '<table class="noborder" width="100%">';

    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans("Contact") . '</td>';
    print '<td>' . $langs->trans("Zip") . '</td>';
    print '<td>' . $langs->trans("Town") . '</td>';
    print '<td>' . $langs->trans("QtyToPrint") . '</td>';
    print '<td align="Center">' . $langs->trans("AddedBy") . '</td>';
    if ($user->rights->societe->contact->creer && $action != 'editline') print '<td align="right">&nbsp;</td>';
    if ($user->rights->societe->contact->creer && $action != 'editline') print '<td align="right">&nbsp;</td>';
    print '</tr>';

    if ($result) {
        $num = $db->num_rows($result);
        if ($num > 0) {

            $companystatic = new Contact($db);

            $var = True;
            $i = 0;
            while ($i < $num) {
                $objp = $db->fetch_object($result);
                $var = !$var;
                print '<tr ' . $bc[$var ? 1 : 0] . '>';

                // Ref
                print '<td nowrap="nowrap">';
                $companystatic->id = $objp->cid;
                $companystatic->firstname = $objp->firstname;
                $companystatic->lastname = $objp->lastname;
                print $companystatic->getNomUrl(1);
                print "</td>";

                print '<td>' . $objp->zip . '</td>';
                print '<td>' . $objp->town . '</td>';

                // Qty
                if ($action == 'editline' && $user->rights->societe->contact->creer && $line == $objp->id) {
                    print '<td align="right">';

                    print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '" method="post">';

                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="updateline">';
                    print '<input type="hidden" name="id" value="' . $objp->cid . '">';
                    print '<input type="hidden" name="lineid" value="' . $line . '">';

                    print '<input class="flat" type="text" size="2" name="qty" value="' . $objp->qty . '"> ';
                    print '<input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
                    print '<br><input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';

                    print '</td>';
                    print '</form>';
                } else
                    print '<td align="right">' . $objp->qty . '</td>';

                // User
                //print '<td align="right"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$objp->user_id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$objp->login.'</a></td>';
                //User
                $userstatic = new User($db);
                $userstatic->fetch($objp->user_id);
                print '<td align="right">' . $userstatic->getNomUrl(1) . '</td>';


                // Actions
                if ($user->rights->societe->contact->creer && $action != 'editline') {
                    print '<td align="right">';
                    print '<a href="' . $_SERVER["PHP_SELF"] . '?action=editline&amp;id=' . $id . '&amp;lineid=' . $objp->id . '">';
                    print img_edit();
                    print '</a>';
                    print '</td>';
                }

                if ($user->rights->societe->contact->creer && $action != 'editline') {
                    print '<td align="right">';
                    print '<a href="' . $_SERVER["PHP_SELF"] . '?action=delete&amp;id=' . $id . '&amp;lineid=' . $objp->id . '">';
                    print img_delete();
                    print '</a>';
                    print '</td>';
                }

                print "</tr>";
                $i++;
            }
            $db->free($result);
            print "</table>";
            print "<br>";
        }
    }
}

llxFooter();
$db->close();


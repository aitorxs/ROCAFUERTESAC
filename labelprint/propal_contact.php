<?php
/* Copyright (C) 2012		Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2013-2017	Ferran Marcet <fmarcet@2byte.es>
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
 *	\file       htdocs/labelprint/product.php
 *	\ingroup    labelprint
 *	\brief      Page to list products to print
 */

$res = @include("../main.inc.php");                    // For root directory
if (!$res) $res = @include("../../main.inc.php");    // For "custom" directory

require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/propal.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
dol_include_once("/labelprint/class/labelprint.class.php");
dol_include_once("/labelprint/lib/labelprint.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php");

global $langs, $conf, $db, $user;

$langs->load("orders");
$langs->load("companies");
$langs->load("customers");
$langs->load("commercial");
$langs->load("labelprint@labelprint");
$langs->load("stocks");
$langs->load('bills');

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$line = GETPOST('lineid', 'int');

if ($user->socid) $socid = $user->socid;
//$result=restrictedArea($user,'produit');

/*
 *	Actions
 */

// Add contact to list
if (substr($action, 0, 3) == 'add') {
    $fac = new Propal($db);
    $fac->fetch($id, $ref);
    $error = 0;

    $list = $fac->liste_contact(-1, 'external', 1, substr($action, 3));

    $object = new LabelsContacts($db);
    $object->fk_object = $list['0'];
    $object->qty = 1;

    $result = $object->create($user);
    if (!$result) $error++;

    if ($error) {
        setEventMessage($object->error, "errors");
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
    $object = new LabelsContacts($db);
    $result = $object->truncate();

    if ($result > 0) {
        if (empty($url)) {
            setEventMessage($langs->trans("ListTruncated"));
            Header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&ref=' . $ref);
            exit;
        }
    } else {
        setEventMessage($object->error, "errors");
    }
}

// delete from list
if ($action == 'delete') {
    $object = new LabelsContacts($db);
    $result = $object->delete($line);

    if ($result > 0) {
        setEventMessage($langs->trans("LineContactDeleted"));
    } else {
        setEventMessage($object->error, "errors");
    }
}

// Add product to list
if ($action == 'updateline') {
    if (GETPOST('save', 'alpha') != '') {
        $qty = GETPOST('qty');

        $object = new LabelsContacts($db);
        $object->fetch($line);
        $object->qty = $qty;
        $result = $object->update();

        if ($result > 0) {
            setEventMessage($langs->trans("LineContactUpdated"));
        } else {
            setEventMessage($object->error, "errors");
        }
    }
}


/*
 * View
 */
$helpurl = 'EN:Module_Labels|FR:Module_Labels_FR|ES:M&oacute;dulo_Labels';
llxHeader('', $langs->trans("Proposal"), $helpurl);

if (!empty($url)) {
    print '<script language="javascript" type="text/javascript">
        window.open("' . $url . '" );
    </script>';
}

$form = new Form($db);
$formcompany = new FormCompany($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
if ($id > 0 || !empty($ref)) {
    $object = new Propal($db);
    if ($object->fetch($id, $ref) > 0) {
        $object->fetch_thirdparty();

        $head = propal_prepare_head($object);

        // Confirmation to delete invoice
        if ($action == 'truncate') {
            $text = $langs->trans('ConfirmTruncateList');
            $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $id, $langs->trans('TruncateList'), $text, 'confirm_truncate', '', 0, 1);
        }

        print $formconfirm;

        dol_fiche_head($head, 'labelprintcontact', $langs->trans("Proposal"), 0, 'propal');

        /*
         *   Facture synthese pour rappel
         */
        print '<table class="border" width="100%">';

        $linkback = '<a href="' . DOL_URL_ROOT . '/comm/propal/list.php' . (!empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

        // Ref
        print '<tr><td width="25%">' . $langs->trans('Ref') . '</td><td colspan="3">';
        print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '');
        print '</td></tr>';

        // Ref client
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td class="nowrap">';
        print $langs->trans('RefCustomer') . '</td><td align="left">';
        print '</td>';
        print '</tr></table>';
        print '</td><td colspan="3">';
        print $object->ref_client;
        print '</td>';
        print '</tr>';

        // Customer
        print "<tr><td>" . $langs->trans("Company") . "</td>";
        print '<td colspan="3">' . $object->thirdparty->getNomUrl(1) . '</td></tr>';

        print '</table>';

        print '</div>';


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

        if ($result) {
            $num = $db->num_rows($result);
        }


        print "<div class=\"tabsAction\">";

        if ($user->rights->societe->contact->creer) {
            $contacts = $object->liste_contact();
            if ($num) print '<a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;ref=' . $ref . '&amp;action=truncate">' . $langs->trans("Truncate") . '</a>';
            foreach ($contacts as $value) {

                if ($object->statut > 0) print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;ref=' . $ref . '&amp;action=add' . $value['code'] . '">' . $langs->trans("AddToPrint") . ' ' . $value['libelle'] . '</a>';
                else print '<span class="butActionRefused" title="' . $langs->trans("PropalNotValidated") . '">' . $langs->trans('AddToPrint') . '</span>';
            }
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


        print '<br>';

        print '<table class="noborder" width="100%">';

        print '<tr class="liste_titre">';
        print '<td>' . $langs->trans("Contact") . '</td>';
        print '<td>' . $langs->trans("Zip") . '</td>';
        print '<td>' . $langs->trans("Town") . '</td>';
        print '<td align="right">' . $langs->trans("QtyToPrint") . '</td>';
        print '<td align="right">' . $langs->trans("AddedBy") . '</td>';
        if ($user->rights->produit->creer && $action != 'editline') print '<td align="right">&nbsp;</td>';
        if ($user->rights->produit->creer && $action != 'editline') print '<td align="right">&nbsp;</td>';
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
                    if ($action == 'editline' && $user->rights->societe->creer && $line == $objp->id) {
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
                    if ($user->rights->societe->creer && $action != 'editline') {
                        print '<td align="right">';
                        print '<a href="' . $_SERVER["PHP_SELF"] . '?action=editline&amp;id=' . $id . '&amp;ref=' . $ref . '&amp;lineid=' . $objp->id . '">';
                        print img_edit();
                        print '</a>';
                        print '</td>';
                    }

                    if ($user->rights->societe->contact->creer && $action != 'editline') {
                        print '<td align="right">';
                        print '<a href="' . $_SERVER["PHP_SELF"] . '?action=delete&amp;id=' . $id . '&amp;&amp;lineid=' . $objp->id . '">';
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
}
llxFooter();
$db->close();
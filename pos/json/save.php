<?php
/* Copyright (C) 2013 Andreu Bisquerra Gayà	<andreu@artadigital.com>
 * Released under the MIT license
 */
include("control.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once('../class/pos.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
$langs->load("pos@doliposbar");
$place = GETPOST('place');
$placenoid = GETPOST('placenoid');
$pay = GETPOST('pay');
$terminal=GETPOST('t');
$print = GETPOST('print');
$now=dol_now();
?><!doctype html>
<html>
<head>


	
<?php

if (is_object($mc))
	{
		$conf->entity=$entity;
		$user->fetch($userid);
	}
	
	

$sql="SELECT fk_soc, fk_paycash, fk_modepaycash, fk_paybank, fk_modepaybank, fk_warehouse FROM ".MAIN_DB_PREFIX."pos_cash where rowid=$terminal";
$resql = $db->query($sql);
$rowterminal = $db->fetch_array ($resql);


if ($placenoid!="")
{
$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."commande where ref='Place-$placenoid' and entity=$entity";
$resql = $db->query($sql);
$row = $db->fetch_array ($resql);
$place=$row[0];
}

$object = new Commande($db);
$res=$object->fetch($place);

$user = new User($db);
$user->fetch($userid);		
if (is_object($mc)) $facture=new Facture($db); //HAY UN BUG CON MULTIEMPRESA Y DOLIPOS
else $facture=new Facturesim($db);
if ($pay=="cash") $object->mode_reglement_id=$rowterminal[2]; else $object->mode_reglement_id=$rowterminal[4];
$facture->createFromOrder($object);

$user->getrights();
if ($conf->stock->enabled) $result=$facture->validate($user, '', $rowterminal[5]);

else $result=$facture->validate($user, '', '');

$object->delete($user);


$payment=new Paiement($db);
$payment->datepaye=$now;
if ($pay=="cash") $fk_bank=$rowterminal[1]; else $fk_bank=$rowterminal[3];
$payment->bank_account=$fk_bank;
$payment->amounts[$facture->id]=$object->total_ttc;
if ($pay=="cash") $payment->paiementid =4; else $payment->paiementid =6;
$payment->num_paiement='';
$payment_id = $payment->create($user,1);
$payment->addPaymentToBank($user, 'payment', 'Dolipos BAR', $fk_bank, '', '');
$facture->update_note($langs->trans("Table")." ".str_replace("Place-", "", $object->ref),'_public');
$facture->set_paid($user);


$sql="SELECT * FROM ".MAIN_DB_PREFIX."facture order by rowid DESC limit 0,1";
$resql = $db->query($sql);
$row = $db->fetch_array ($resql);

$sql="INSERT INTO ".MAIN_DB_PREFIX."pos_facture values ('',$terminal,NULL,$row[0],NULL,NULL)";
$db->query($sql);

if ($print==1) {
$sql="SELECT value FROM ".MAIN_DB_PREFIX."const where name='PENDING_PRINT_BAR' and entity=$entity";
$resql = $db->query($sql);
$const = $db->fetch_array ($resql);
dolibarr_set_const($db,"PENDING_PRINT_BAR", $const[0].'F'.$row[0].',','chaine',0,'',$entity);
}


?>
</head>
<body>
</body>
</html>
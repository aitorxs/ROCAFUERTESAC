<?php
/* Copyright (C) 2013 Andreu Bisquerra Gayà	<andreu@artadigital.com>
 * Released under the MIT license
 */
include("control.php");
require_once('../class/pos.class.php');
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
$id = GETPOST('id');
$action = GETPOST('action');

$sql="SELECT fk_soc, fk_paycash, fk_modepaycash, fk_paybank, fk_modepaybank, fk_warehouse FROM ".MAIN_DB_PREFIX."pos_cash where rowid=$terminal";
$resql = $db->query($sql);
$rowterminal = $db->fetch_array ($resql);

if ($action=="return")
{
$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."facture order by rowid DESC LIMIT 1";
$resql = $db->query($sql);
$row = $db->fetch_array ($resql);
if ($row[0]==$id) $action="delete";
}


if ($action=="return")
{
$facture=new Facturesim($db);
$facture->fetch($id);
$userpos = new User($db);
$userpos->fetch($userid);
$facture->type=2;
$facture->fk_facture_source=$id;
foreach($facture->lines as $line)
    {
    $line->subprice =-$line->subprice; // invert price for object
    $line->pa_ht = -$line->pa_ht;
    $line->total_ht=-$line->total_ht;
    $line->total_tva=-$line->total_tva;
    $line->total_ttc=-$line->total_ttc;
    $line->total_localtax1=-$line->total_localtax1;
    $line->total_localtax2=-$line->total_localtax2;
    }
$facture->create($userpos,'','');

$userpos->rights->facture->valider=1;
if ($conf->stock->enabled) $result=$facture->validate($userpos, '', $rowterminal[5]);
else $result=$facture->validate($userpos, '', '');

$payment=new Paiement($db);
$payment->datepaye=$now;
if ($facture->mode_reglement_id==1) $fk_bank=$rowterminal[3]; else $fk_bank=$rowterminal[1];
$payment->bank_account=$fk_bank;
$payment->amounts[$facture->id]=$facture->total_ttc;
$payment->paiementid =$facture->mode_reglement_id;
if ($payment->paiementid==5) $payment->paiementid=4;
$payment->num_paiement='';
$payment_id = $payment->create($userpos,1);
$id=$payment->addPaymentToBank($userpos, 'payment', 'Dolipos', $fk_bank, '', '');
$facture->set_paid($userpos);
$sql="SELECT * FROM ".MAIN_DB_PREFIX."facture order by rowid DESC limit 0,1";
$resql = $db->query($sql);
$row = $db->fetch_array ($resql);
$sql="INSERT INTO ".MAIN_DB_PREFIX."pos_facture values ('',$terminal,NULL,$row[0],NULL,NULL)";
$db->query($sql);
}

if ($action=="delete")
{
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$object = new Facturesim($db);
$object->fetch($id);
$object->set_unpaid('admin');

        $sql = 'SELECT p.datep as dp, p.num_paiement, p.rowid, p.fk_bank,';
        $sql.= ' c.code as payment_code, c.libelle as payment_label,';
        $sql.= ' pf.amount,';
        $sql.= ' ba.rowid as baid, ba.ref, ba.label';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'c_paiement as c, '.MAIN_DB_PREFIX.'paiement_facture as pf, '.MAIN_DB_PREFIX.'paiement as p';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
        $sql.= ' WHERE pf.fk_facture = '.$id.' AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid';
        $sql.= ' ORDER BY p.datep, p.tms';
		
		$result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
 
					$objp = $db->fetch_object($result);
					$paiement = new Paiement($db);
					$paiement->fetch($objp->rowid);
					$paiement->delete();				

		}
if ($conf->stock->enabled) $object->delete(0,0,$conf->global->POS_DEFAULT_WAREHOUSE); else $object->delete();
$sql= 'delete from '.MAIN_DB_PREFIX.'pos_facture where fk_facture='.$id;
$db->query($sql);
}


//Get records from database
$sql="SELECT rowid as iddet, facnumber, datec, fk_user_author as user, total_ttc as price FROM ".MAIN_DB_PREFIX."facture order by rowid DESC limit 50";
$resql = $db->query($sql);

//Add all records to an array
$rows = array();
while($row = $db->fetch_array ($resql))
{
	$row['price']=number_format($row['price'], 2, '.', '');
    $rows[] = $row;
}
 
//Return result to jTable
$jTableResult = array();
$jTableResult['Result'] = "OK";
$jTableResult['Records'] = $rows;
print json_encode($jTableResult);
<?php
/* Copyright (C) 2013 Andreu Bisquerra Gayà	<andreu@artadigital.com>
 * Released under the MIT license
 */
include("control.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
$langs->load("pos@doliposbar");
$sql="SELECT value FROM ".MAIN_DB_PREFIX."const where name='PENDING_PRINT_BAR' and entity=$entity";
$resql = $db->query($sql);
$const = $db->fetch_array ($resql);
$pending_print = explode(',',$const[0]);
$id=substr($pending_print[0], 1);

$drop=strlen($id);
$drop++;$drop++;
dolibarr_set_const($db,"PENDING_PRINT_BAR", substr($const[0], $drop),'chaine',0,'',$entity);

function clean_text ($text_to_clean){
    $source = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ';
    $destination = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr';
    $text_to_clean = utf8_decode($text_to_clean);
    $text_to_clean = strtr($text_to_clean, utf8_decode($source), $destination);
    //$text_to_clean = strtolower($text_to_clean);
    return utf8_encode($text_to_clean);
}

if ($pending_print[0][0]=="K"){
echo $pending_print[0];
exit;
}

$json_data = array();
$json_data['printerid'] = " ";

if ($pending_print[0][0]=="V" or $pending_print[0][0]=="F" or $pending_print[0][0]=="D") $json_data['printer']=$conf->global->BARPRINTERNAME;

if ($pending_print[0][0]=="F")
{
$sql="SELECT rowid, tva, total_ttc, facnumber, note_public FROM ".MAIN_DB_PREFIX."facture where rowid='$id'";
$resql = $db->query($sql);
$rowfacture = $db->fetch_array ($resql);
$json_data['type'] = $pending_print[0][0];
$json_data['ref'] = $rowfacture['facnumber'];
$json_data['tva'] = number_format($rowfacture['tva'], 2, '.', '');
$json_data['total'] = number_format($rowfacture['total_ttc'], 2, '.', '');
$json_data['datetime']= date("d-m-Y H:i:s");
$json_data['note']= $rowfacture['note_public'];
$sql="SELECT ".MAIN_DB_PREFIX."facture.rowid as id, ".MAIN_DB_PREFIX."facturedet.description as note, ".MAIN_DB_PREFIX."facturedet.rowid as iddet, ".MAIN_DB_PREFIX."facturedet.label as label, ".MAIN_DB_PREFIX."facturedet.qty as qty, ".MAIN_DB_PREFIX."facturedet.price as price, ".MAIN_DB_PREFIX."facturedet.remise_percent as remise, ".MAIN_DB_PREFIX."facturedet.total_ttc as total FROM ".MAIN_DB_PREFIX."facture, ".MAIN_DB_PREFIX."facturedet where ".MAIN_DB_PREFIX."facture.rowid=".MAIN_DB_PREFIX."facturedet.fk_facture and ".MAIN_DB_PREFIX."facture.rowid=$id";
$result= $db->query($sql);
$rows = array();
}

if ($pending_print[0][0]=="V")
{
$sql="SELECT ";
$sql.="rowid, tva, total_ttc FROM ".MAIN_DB_PREFIX."commande where ref='Place-$id'";
$resql = $db->query($sql);
$rowcommande = $db->fetch_array ($resql);
$json_data['place'] = $id;
$json_data['voucher_label']=$langs->trans("CheckBar");
$id=$rowcommande['rowid'];
$json_data['note'] = $rowcommande['note'];
$json_data['type'] = $pending_print[0][0];
$json_data['ref'] = $id;
$json_data['tva'] = number_format($rowcommande['tva'], 2, '.', '');
$json_data['total'] = number_format($rowcommande['total_ttc'], 2, '.', '');
$json_data['datetime']= date("d-m-Y H:i:s");
$sql="SELECT ";
$sql.=MAIN_DB_PREFIX."commandedet.description as note, ";
$sql.=MAIN_DB_PREFIX."commande.rowid as id, ".MAIN_DB_PREFIX."commandedet.rowid as iddet, ".MAIN_DB_PREFIX."commandedet.label as label, ".MAIN_DB_PREFIX."commandedet.qty as qty, ".MAIN_DB_PREFIX."commandedet.price as price, ".MAIN_DB_PREFIX."commandedet.remise_percent as remise, ".MAIN_DB_PREFIX."commandedet.total_ttc as total FROM ".MAIN_DB_PREFIX."commande, ".MAIN_DB_PREFIX."commandedet where ".MAIN_DB_PREFIX."commande.rowid=".MAIN_DB_PREFIX."commandedet.fk_commande and ".MAIN_DB_PREFIX."commande.rowid=$id";
$result = $db->query($sql);
$rows = array();
}

if ($pending_print[0][0]=="D") { $json_data['type'] = "D"; $row['price']=0; $rows[] = $row; $json_data['lines'] = $rows;}

if ($pending_print[0][0]=="V" or $pending_print[0][0]=="F")
{
$json_data['text1']=$conf->global->BARHEADTEXT1;
$json_data['text2']=$conf->global->BARHEADTEXT2;
$json_data['text3']=$conf->global->BARHEADTEXT3;
$json_data['text4']=$conf->global->BARFOOTERTEXT;
}

if ($pending_print[0][0]=="V" or $pending_print[0][0]=="F")
{
while($row = $db->fetch_array ($result))
{
	$row['price']=number_format($row['price'], 2, '.', '');
	$row['total']=number_format($row['total'], 2, '.', '');
	$row['label']=substr($row['label'],0,23);
	$row['note']=str_replace('_', ' ', $row['note']);
	if (strpos($row['note'],'FreeProduct=') !== false) $row['label']=str_replace('FreeProduct=', '', $row['note']);
	$row['label']=clean_text($row['label']);
	$row['label']=str_pad($row['label'], 25, ' ', STR_PAD_RIGHT);
	if ($row['qty']<10) $row['qty'] ="  ".$row['qty'];
	if ($row['qty']>=10 and $row['qty']<100) $row['qty']=" ".$row['qty'];
	if ($row['total']<10) $row['total']="       ".$row['total'];
	if ($row['total']>=10 and $row['total']<100) $row['total']="      ".$row['total'];
	if ($row['total']>=100 and $row['total']<1000) $row['total']="     ".$row['total'];
	if ($row['total']>=1000 and $row['total']<10000) $row['total']="   ".$row['total'];
	if ($row['total']>=10000 and $row['total']<100000) $row['total']="  ".$row['total'];
	if ($row['total']>=100000 and $row['total']<1000000) $row['total']=" ".$row['total'];
    $rows[] = $row;
}
if (count($rows)==0) exit;
$json_data['lines'] = $rows;
}


if ($pending_print[0][0]=="V" or $pending_print[0][0]=="F")print json_encode($json_data); else echo "null";
?>
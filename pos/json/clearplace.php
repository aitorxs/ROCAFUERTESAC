<?php
/* Copyright (C) 2013 Andreu Bisquerra Gayà	<andreu@artadigital.com>
 * Released under the MIT license
 */
$res=@include("../../master.inc.php");
if (! $res) $res=@include("../../../master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
$query= GETPOST('query');
$place= GETPOST('place');
$result=$user->fetch('','admin');
$user->getrights();

$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."commande where ref='Place-$place'";
$resql = $db->query($sql);
$row = $db->fetch_array ($resql);
$placeid=$row[0];

if ($placeid>0){
$db->query("DELETE FROM ".MAIN_DB_PREFIX."commandedet WHERE fk_commande = $placeid;");
$db->query("DELETE FROM ".MAIN_DB_PREFIX."commande WHERE rowid = $placeid;");
}

$db->query("update ".MAIN_DB_PREFIX."pos_places_bar set terminal=NULL where name='$place'");


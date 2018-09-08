<?php
/* Copyright (C) 2013 Andreu Bisquerra Gayà	<andreu@artadigital.com>
 * Released under the MIT license
 */
include("control.php");
 
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
$sql = GETPOST("s");
$one = GETPOST("o");
$sql=str_replace('-', ' ', $sql);
$sql=str_replace('FFF', MAIN_DB_PREFIX."facture", $sql);
$sql=str_replace('POSF', MAIN_DB_PREFIX."pos_facture", $sql);
$sql=str_replace('FFDET', MAIN_DB_PREFIX."facturedet", $sql);
$sql=str_replace('FFPAFA', MAIN_DB_PREFIX."paiement_facture", $sql);
$sql=str_replace('ENTITYID', $entity, $sql);
$sql=str_replace('TERMINALID', $terminal, $sql);

$result = $db->query($sql);
$rows = array();
while($row = $db->fetch_array ($result))
{
	$rows[] = $row;
}
if ($one=="y") echo $rows[0][0];
else print json_encode($rows);
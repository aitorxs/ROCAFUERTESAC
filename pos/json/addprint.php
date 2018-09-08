<?php
/* Copyright (C) 2013 Andreu Bisquerra Gayà	<andreu@artadigital.com>
 * Released under the MIT license
 */
include("control.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
$addprint = GETPOST('addprint');
$sql="SELECT value FROM ".MAIN_DB_PREFIX."const where name='PENDING_PRINT_BAR' and entity=$entity";
$resql = $db->query($sql);
$const = $db->fetch_array ($resql);
dolibarr_set_const($db,"PENDING_PRINT_BAR", $const[0].$addprint.',','chaine',0,'',$entity);
include "access.php";
?>
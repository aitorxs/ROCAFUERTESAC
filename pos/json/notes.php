<?php
/* Copyright (C) 2013 Andreu Bisquerra Gayà	<andreu@artadigital.com>
 * Released under the MIT license
 */
$res=@include("../../master.inc.php");
if (! $res) $res=@include("../../../master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
$query= GETPOST('query');
$place= GETPOST('place');
$id= GETPOST('id');
$notes= GETPOST('notes');



if ($query=="predefined") echo $conf->global->PREDEFINED_NOTES_BAR;
if ($query=="predefinedsave") dolibarr_set_const($db,"PREDEFINED_NOTES_BAR", $notes,'chaine',0,'',$conf->entity);


if ($query=="getnotes")
{
	if ($id>=0)
	{
	$sql="SELECT description FROM ".MAIN_DB_PREFIX."commandedet where rowid=$id";
	$resql = $db->query($sql);
	$row = $db->fetch_array ($resql);
	echo $row[0];
	}
	else
	{
	$sql="SELECT note_private FROM ".MAIN_DB_PREFIX."commande where ref='Place-$place'";
	$resql = $db->query($sql);
	$row = $db->fetch_array ($resql);
	echo $row[0];
	}
}


if ($query=="addnote")
{
$notes = str_replace('_', ' ', $notes);
if ($id>=0)
	{
	$db->begin();
	$db->query("update ".MAIN_DB_PREFIX."commandedet set description='$notes' where rowid=$id");
	$db->commit();
	}
else
	{
	$db->begin();
	$db->query("update ".MAIN_DB_PREFIX."commande set note_private='$notes' where ref='Place-$place'");
	$db->commit();
	}
}
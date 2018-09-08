<?php
/* Copyright (C) 2013 Andreu Bisquerra Gay�	<andreu@artadigital.com>
 * Released under the MIT license
 */
include("control.php");
$query= GETPOST('query');
$id= GETPOST('id');

$sql="CREATE TABLE IF NOT EXISTS llx_pos_places_bar
(
  rowid               	integer AUTO_INCREMENT PRIMARY KEY,

  entity				integer  DEFAULT 1 	NOT NULL,
  name		        	varchar(30) UNIQUE 	NOT NULL,
  description  			text,
  terminal            	integer  DEFAULT NULL,
  status			  	integer  DEFAULT 1 	NOT NULL,
  fk_user_c		    	integer,
  fk_user_m		    	integer,
  datec					datetime,
  datea					datetime,
  left_pos				float,
  top_pos				float,
  zone					int(3)
  
)ENGINE=innodb;";
$db->query($sql);

if ($query=="getplaces")
{
$tablebusy[]=array();
$sql="SELECT ref from ".MAIN_DB_PREFIX."commande where ref like 'Place-%'";
$resql = $db->query($sql);
while($row = $db->fetch_array ($resql)){
    $tablebusy[] = substr($row[0], 6);
}

$sql="SELECT name, left_pos, top_pos from ".MAIN_DB_PREFIX."pos_places_bar where zone=$id";
$resql = $db->query($sql);
$rows = array();
 while($row = $db->fetch_array ($resql))
{
$row["name"]=$row[0]; 
$row["left"]=$row[1];
$row["top"]=$row[2];
if (in_array($row[0], $tablebusy)) $row["status"]="busy"; else $row["status"]="";
$rows[] = $row;
}
print json_encode($rows);
}

if ($query=='do_move')
{
$origin=GETPOST('origin');
$destination=GETPOST('destination');
$sql="SELECT rowid from ".MAIN_DB_PREFIX."commande where ref like 'Place-$destination'";
$resql = $db->query($sql);
$row = $db->fetch_array ($resql);
if ($row[0]>0)
	{
	$sql="SELECT rowid from ".MAIN_DB_PREFIX."commande where ref like 'Place-$origin'";
	$resql = $db->query($sql);
	$origin_rowid = $db->fetch_array ($resql);
	$sql="UPDATE ".MAIN_DB_PREFIX."commandedet set fk_commande= $row[0] where fk_commande=$origin_rowid[0]";
	$resql = $db->query($sql);
	require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
	$result=$user->fetch('','admin');
	$object = new Commande($db);
	$ret=$object->fetch($origin_rowid[0]);
	$result=$object->delete($user);
	}
else
	{
	$sql="UPDATE ".MAIN_DB_PREFIX."commande set ref= 'Place-$destination' where ref= 'Place-$origin'";
	$resql = $db->query($sql);
	}
}


if ($query=="opens")
{
$sql="SELECT rowid, ref from ".MAIN_DB_PREFIX."commande where ref like 'Place%' and ref <>'Place-0'";
$resql = $db->query($sql);
while($row = $db->fetch_array ($resql))
{
$opens=$opens.str_replace("Place-","",$row[1]).";";
}
echo $opens;
}

	

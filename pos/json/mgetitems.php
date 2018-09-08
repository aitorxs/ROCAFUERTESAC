<?php
/* Copyright (C) 2013 Andreu Bisquerra Gayà	<andreu@artadigital.com>
 * Released under the MIT license
 */
include("control.php");

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");



$place = GETPOST("place");
if ($place!=""){
	
	$sql="SELECT terminal FROM ".MAIN_DB_PREFIX."pos_places_bar where name='$place'";
	$resql = $db->query($sql);
	$row = $db->fetch_array ($resql);
	if ($row[0]==NULL or $row[0]==$terminal) $db->query("update ".MAIN_DB_PREFIX."pos_places_bar set terminal=$terminal where name='$place'");
	else {echo "LOCKED"; exit;}

	$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."commande where ref='Place-$place'";
	$resql = $db->query($sql);
	$row = $db->fetch_array ($resql);
	$resql = $db->query("SELECT * from ".MAIN_DB_PREFIX."commandedet where fk_commande=$row[0]");
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
	
		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);
			echo "INSERT INTO comandedet values (NULL, ".$objp->fk_product.", '".$objp->label."', ".$objp->qty.", ".$objp->remise_percent.", ".$objp->price.", ".$objp->total_ttc.", '".$place."', '".$objp->description."')@@";
			$i++;
		}
	}
exit;
}





function getitems($id)
{

		global $db,$conf;
		$objs = array();
		$retarray=array();
			
		$sql = "SELECT o.rowid as id, o.price_ttc as price, o.label as label, o.barcode as barcode, c.fk_product";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie_product as c";
		$sql.= ", ".MAIN_DB_PREFIX."product as o";
		$sql .= " WHERE c.fk_categorie = ".$id;
		$sql .= " AND c.fk_product = o.rowid";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);
			
				$val["id"]= $objp->id;
				$val["label"]= $objp->label;
				$val["price"]= $objp->price;
				$val["price"]=round($val["price"] * 100) / 100;
				$val["barcode"]= $objp->barcode;
				$val["type"]="pro";
				
				echo "INSERT INTO products  values (".$val["id"].", ".$id.", '".$val["label"]."', ".$val["price"].")@@";
				//echo "<br>";
				$i++;
			}

		}
}



$objCat = new Categorie($db);
$cats = $objCat->get_full_arbo(0);

$retarray=array();
foreach($cats as $key => $val)
	{
	echo "INSERT INTO cats  values (".$val["rowid"].", ".$val["fk_parent"].", '".$val["label"]."')@@";
	//echo "<br>";
	getitems($val["rowid"]);
	}
	
	
$resql = $db->query("SELECT DISTINCT p.rowid as id, p.price_ttc as price, p.label as label, p.barcode as barcode FROM ".MAIN_DB_PREFIX."product as p LEFT JOIN llx_categorie_product as cp ON p.rowid = cp.fk_product LEFT JOIN llx_product_fournisseur_price as pfp ON p.rowid = pfp.fk_product WHERE p.entity IN ($entity) AND p.fk_product_type <> '1' AND cp.fk_categorie IS NULL GROUP BY p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type, p.fk_product_type, p.tms, p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte, p.desiredstock ORDER BY p.ref");
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	{
		$objp = $db->fetch_object($resql);
	
		$val["id"]= $objp->id;
		$val["label"]= $objp->label;
		$val["price"]= $objp->price;
		$val["barcode"]= $objp->barcode;
		$val["type"]="pro";
		
		echo "INSERT INTO products  values (".$val["id"].", 0, '".$val["label"]."', ".$val["price"].")@@";
		$i++;
	}
}







$sql="SELECT admin from ".MAIN_DB_PREFIX."user where rowid=$userid";
$resql = $db->query($sql);
$rowuser = $db->fetch_array ($resql);
$isadmin=$rowuser[0];
$sql="SELECT rowid, name from ".MAIN_DB_PREFIX."pos_cash";
if ($isadmin==0) $sql.=" where entity=$entity";
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	{
		$objp = $db->fetch_object($resql);
		echo "INSERT INTO terminals  values ('".$objp->name."', ".$objp->rowid.")@@";
		$i++;
	}
}	






$resql = $db->query("SELECT name from ".MAIN_DB_PREFIX."pos_places_bar");
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	{
		$objp = $db->fetch_object($resql);
		
		echo "INSERT INTO places values ('".$objp->name."')@@";
		$i++;
	}
}





echo "REPLACE INTO conf values ('notes', '".$conf->global->PREDEFINED_NOTES_BAR."')@@";





require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
$langcode=($conf->global->MAIN_LANG_DEFAULT);
if ($langcode=="auto") $langcode = GETPOST("lang");
$langs->setDefaultLang($langcode);
$langs->load("pos@pos");
$langs->load("client@pos");
$langs->load("main");
$langs->load("orders");
$langs->load("errors");

function addrow($text){
global $langs;
echo "INSERT INTO lang values ('".$text."', '".$langs->trans($text)."')@@";
}

addrow("Delete");
addrow("Table");
			



?>
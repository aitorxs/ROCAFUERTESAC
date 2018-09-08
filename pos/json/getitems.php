<?php
/* Copyright (C) 2013 Andreu Bisquerra Gayà	<andreu@artadigital.com>
 * Released under the MIT license
 */
include("control.php");

header ('Content-type: text/html; charset=ISO-8859-1');
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");

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
				
				echo "INSERT INTO DOLIPOSBAR.\"productos\"  values (".$val["id"].", ".$id.", '".$val["label"]."', ".$val["price"].", '".$val["barcode"]."')@@";
				//echo "<br>";
				$i++;
			}

		}
}







if (is_object($mc))
	{
		$user->fetch($userid);
		$conf->entity=$entity;
	}

$objCat = new Categorie($db);
$cats = $objCat->get_full_arbo(0);

$retarray=array();
foreach($cats as $key => $val)
	{
	echo "INSERT INTO DOLIPOSBAR.\"cats\"  values (".$val["rowid"].", ".$val["fk_parent"].", '".$val["label"]."')@@";
	getitems($val["rowid"]);
	}

	

	
	
	
$resql = $db->query("SELECT DISTINCT p.rowid as id, p.price_ttc as price, p.label as label, p.barcode as barcode FROM ".MAIN_DB_PREFIX."product as p LEFT JOIN llx_categorie_product as cp ON p.rowid = cp.fk_product LEFT JOIN llx_product_fournisseur_price as pfp ON p.rowid = pfp.fk_product WHERE p.entity IN ($entity) AND p.fk_product_type <> '1' AND cp.fk_categorie IS NULL AND p.entity=$entity GROUP BY p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type, p.fk_product_type, p.tms, p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte, p.desiredstock ORDER BY p.ref");
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
		
		echo "INSERT INTO DOLIPOSBAR.\"productos\"  values (".$val["id"].", 0, '".$val["label"]."', ".$val["price"].", '".$val["barcode"]."')@@";
		$i++;
	}
}	







$resql = $db->query("SELECT fk_product, price_level, price_ttc from ".MAIN_DB_PREFIX."product_price group by fk_product, price_level order by rowid DESC");
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	{
		$objp = $db->fetch_object($resql);
		echo "INSERT INTO DOLIPOSBAR.\"prices\"  values (".$objp->fk_product.", ".$objp->price_level.", ".$objp->price_ttc.")@@";
		$i++;
	}
}	







$resql = $db->query("SELECT rowid, login, lastname, firstname, pass_crypted from ".MAIN_DB_PREFIX."user where entity=$entity or entity=0");
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	{
		$objp = $db->fetch_object($resql);
		echo "INSERT INTO DOLIPOSBAR.\"users\"  values (".$objp->rowid.", '".$objp->login."', '".$objp->lastname."', '".$objp->firstname."', '".$objp->pass_crypted."')@@";
		$i++;
	}
}	




$resql = $db->query("SELECT rowid, nom from ".MAIN_DB_PREFIX."societe where entity=$entity");
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	{
		$objp = $db->fetch_object($resql);
		echo "INSERT INTO DOLIPOSBAR.\"societe\"  values (".$objp->rowid.", '".$objp->nom."')@@";
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
		echo "INSERT INTO DOLIPOSBAR.\"terminals\"  values (".$objp->rowid.", '".$objp->name."')@@";
		$i++;
	}
}	
	
				
				
				
				
?>
<?php
/* Copyright (C) 2013 Andreu Bisquerra Gayà	<andreu@artadigital.com>
 * Released under the MIT license
 */
include("control.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
$id= GETPOST('id');
$place= GETPOST('place');
$qty=GETPOST('q');
$price=GETPOST('p');
$dto=GETPOST('d');
$notes=GETPOST('n');
$label=GETPOST('l');
$customer=GETPOST('c');
$exit=GETPOST('e');
$result=$user->fetch($userid);
$user->getrights();

if (is_object($mc))
	{
		$conf->entity=$entity;
		$user->fetch($userid);
	}


$sql="SELECT fk_soc FROM ".MAIN_DB_PREFIX."pos_cash where rowid=$terminal";
$resql = $db->query($sql);
$row = $db->fetch_array ($resql);
if ($customer==0) $customer=$row[0];

$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."commande where ref='Place-$place' and entity=$entity";
$resql = $db->query($sql);
$row = $db->fetch_array ($resql);
$placeid=$row[0];
if ($placeid>0){
$db->query("DELETE FROM ".MAIN_DB_PREFIX."commandedet WHERE fk_commande = $placeid;");
$db->query("DELETE FROM ".MAIN_DB_PREFIX."commande WHERE rowid = $placeid;");
}

	$com = new Commande($db);
	$com->ref            = "Place-$place";
	$com->socid          = $customer;
	$com->date_commande  = mktime();
	$com->note           = '';
	$com->source         = 1;
	$com->remise_percent = 0;
	$result=$user->fetch($userid);
	$idobject=$com->create($user);
	$db->commit();
	$db->query("UPDATE ".MAIN_DB_PREFIX."commande SET ref='Place-$place' WHERE rowid=$idobject;");
	$db->commit();
	$placeid=$idobject;

for ($i = 1; $i <= count($id); $i++) {

$prod = new Product($db);
$prod->fetch($id[$i-1]);
if ($prod->tva_tx<1) $prod->tva_tx=21;
$vatmult=$prod->tva_tx/100+1;
$com = new OrderLine($db);
$com->fk_commande=$placeid;
$com->qty=$qty[$i-1];
$com->tva_tx=$prod->tva_tx;
$com->fk_product=$id[$i-1];
$com->subprice=round($price[$i-1]/$vatmult * 100) / 100;
$com->label=$label[$i-1];
$com->rang='1';
$com->remise_percent=$dto[$i-1];
$com->remise=round($price[$i-1]*$qty[$i-1]*$dto[$i-1]) / 100;
$com->total_ttc=round(($qty[$i-1]*$price[$i-1]-$com->remise) * 100) / 100;
$com->total_ht=round($com->total_ttc/$vatmult * 100) / 100;
$com->fk_parent_line='';
$com->total_tva=$com->total_ttc-$com->total_ht;
$com->pa_ht = '1';
$com->price=$price[$i-1];
$com->desc=$notes[$i-1];
$result=$com->insert();

}



			//UPDATE PRICE
        $fieldtva='total_tva';
        $fieldlocaltax1='total_localtax1';
        $fieldlocaltax2='total_localtax2';
        $sql = 'SELECT qty, total_ht, '.$fieldtva.' as total_tva, total_ttc, '.$fieldlocaltax1.' as total_localtax1, '.$fieldlocaltax2.' as total_localtax2,';
        $sql.= ' tva_tx as vatrate, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'commandedet' ;
        $sql.= ' WHERE fk_commande = '.$placeid;
        $resql = $db->query($sql);
        if ($resql)
        {
            $total_ht  = 0;
            $total_tva = 0;
            $total_localtax1 = 0;
            $total_localtax2 = 0;
            $total_ttc = 0;
            $vatrates = array();
            $vatrates_alllines = array();
            $num = $db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                $total_ht        += $obj->total_ht;
                $total_tva       += $obj->total_tva;
                $total_localtax1 += $obj->total_localtax1;
                $total_localtax2 += $obj->total_localtax2;
                $total_ttc       += $obj->total_ttc;
                if (! empty($conf->global->MAIN_USE_LOCALTAX_TYPE_7))
                {
                	if ($this->total_localtax1 == 0)
                    {
                		global $mysoc;
                    	$localtax1_array=getLocalTaxesFromRate($vatrate,1,$mysoc);
                    	if (empty($obj->localtax1_type))
                    	{
                    		$obj->localtax1_type = $localtax1_array[0];
                    		$obj->localtax1_tx = $localtax1_array[1];
                    	}
						if ($obj->localtax1_type == '7')
						{
							$total_localtax1 += $obj->localtax1_tx;
							$total_ttc       += $obj->localtax1_tx;
						}
					}
                    if ($this->total_localtax2 == 0)
                    {
                		global $mysoc;
                    	$localtax2_array=getLocalTaxesFromRate($vatrate,2,$mysoc);
                    	if (empty($obj->localtax2_type))
                    	{
                    		$obj->localtax2_type = $localtax2_array[0];
                    		$obj->localtax2_tx = $localtax2_array[1];
                    	}

                    	if ($obj->localtax2_type == '7')
						{
							$total_localtax2 += $obj->localtax2_tx;
							$total_ttc       += $obj->localtax2_tx;
						}
                    }
                }

                $i++;
            }

            $db->free($resql);
            $fieldht='total_ht';
            $fieldtva='tva';
            $fieldlocaltax1='localtax1';
            $fieldlocaltax2='localtax2';
            $fieldttc='total_ttc';
            if (empty($nodatabaseupdate))
            {
                $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande SET';
                $sql .= " ".$fieldht."='".price2num($total_ht)."',";
                $sql .= " ".$fieldtva."='".price2num($total_tva)."',";
                $sql .= " ".$fieldlocaltax1."='".price2num($total_localtax1)."',";
                $sql .= " ".$fieldlocaltax2."='".price2num($total_localtax2)."',";
                $sql .= " ".$fieldttc."='".price2num($total_ttc)."'";
                $sql .= ' WHERE rowid = '.$placeid;
                $resql=$db->query($sql);
            }
        }
		//END UPDATE PRICE


if ($exit=="yes") $db->query("update ".MAIN_DB_PREFIX."pos_places_bar set terminal=NULL where name='$place'");

include "access.php";

		

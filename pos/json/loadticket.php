<?php
/* Copyright (C) 2013 Andreu Bisquerra Gayà	<andreu@artadigital.com>
 * Released under the MIT license
 */
$res=@include("../../master.inc.php");
if (! $res) $res=@include("../../../master.inc.php");  
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
$id = GETPOST('id');
$action = GETPOST('action');
$place = GETPOST('place');
$number = GETPOST('number');
$terminal = GETPOST('t');
$result=$user->fetch('','admin');
$user->getrights();



if ($action!="onlycheck"){
$sql="SELECT terminal FROM ".MAIN_DB_PREFIX."pos_places_bar where name='$place'";
$resql = $db->query($sql);
$row = $db->fetch_array ($resql);
if ($row[0]==NULL or $row[0]==$terminal) $db->query("update ".MAIN_DB_PREFIX."pos_places_bar set terminal=$terminal where name='$place'");
else {echo "LOCKED"; exit;}
}



$sql="SELECT rowid, fk_soc FROM ".MAIN_DB_PREFIX."commande where ref='Place-$place'";
$resql = $db->query($sql);
$row = $db->fetch_array ($resql);
$placeid=$row[0];
$customer=$row[1];
if (! $placeid) $placeid=0;

$sql="SELECT fk_soc FROM ".MAIN_DB_PREFIX."pos_cash where rowid=$terminal";
$resql = $db->query($sql);
$row = $db->fetch_array ($resql);
if ($customer==$row[0]) $customer="0";
if ($customer==NULL) $customer="0";


if ($action=="deleteline")
{
if (! $id)
{
$sql="SELECT MAX(rowid) FROM ".MAIN_DB_PREFIX."commandedet where fk_commande=$placeid";
$resql = $db->query($sql);
$row = $db->fetch_array ($resql);
$db->begin();
$db->query("DELETE FROM ".MAIN_DB_PREFIX."commandedet WHERE rowid = $row[0];");
$db->commit();
}
else
{
$db->begin();
$db->query("delete from ".MAIN_DB_PREFIX."commandedet where rowid='$id'");
$db->commit();
}
$sql="SELECT count(*) FROM ".MAIN_DB_PREFIX."commandedet where fk_commande=$placeid";
$resql = $db->query($sql);
$row = $db->fetch_array ($resql);
if ($row[0]==0)
	{
	$object = new Commande($db);
	$ret=$object->fetch($placeid);
	$result=$object->delete($user);
	}
}

if ($action=="q")
{
$db->begin();
$db->query("update ".MAIN_DB_PREFIX."commandedet set qty='$number', total_ht=subprice*qty, total_tva=total_ht*tva_tx/100, total_ttc=total_ht+total_tva where rowid='$id'");
$db->commit();
}

if ($action=="p")
{
$db->begin();
$db->query("update ".MAIN_DB_PREFIX."commandedet set price='$number', remise=remise_percent*price/100, subprice=(price-remise)/(tva_tx/100+1), total_ht=subprice*qty, total_tva=total_ht*tva_tx/100, total_ttc=total_ht+total_tva where rowid='$id'");
$db->commit();
}

if ($action=="d")
{
$db->begin();
$db->query("update ".MAIN_DB_PREFIX."commandedet set remise_percent='$number', remise=remise_percent*price/100, subprice=(price-remise)/(tva_tx/100+1), total_ht=subprice*qty, total_tva=total_ht*tva_tx/100, total_ttc=total_ht+total_tva where rowid='$id'");
$db->commit();
}

if ($action=="addline" /* NOT IMPLEMENTET YET or $action=="addlinex" */)
{
	if ($placeid==0) {
	require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
	$com = new Commande($db);
	$com->ref            = 'Place-$place';
	$com->socid          = $conf->global->POS_DEFAULT_THIRD;
	$com->date_commande  = mktime();
	$com->note           = '';
	$com->source         = 1;
	$com->remise_percent = 0;
	$result=$user->fetch('','admin');
	$idobject=$com->create($user);
	$db->commit();
	$db->query("UPDATE ".MAIN_DB_PREFIX."commande SET ref='Place-$place' WHERE rowid=$idobject;");
	$db->commit();
	$placeid=$idobject;
	}

			
$db->begin();
$com = new OrderLine($db);
	if ($action=="addline") {
			$prod = new Product($db);
			$prod->fetch($id);
			$com->fk_commande=$placeid;
            $com->qty=1;
            $com->tva_tx=$prod->tva_tx;
            $com->fk_product=$id;
            $com->subprice=$prod->price;
			$com->label=$prod->label;
            $com->rang='1';
            $com->total_ht=$prod->price;
            $com->total_ttc=$prod->price_ttc;
            $com->fk_parent_line='';
			$com->total_tva=$prod->price_ttc-$prod->price;
			$com->pa_ht = '1';
            $com->price=$prod->price*(1+$prod->tva_tx/100);
			}
			else{
			$com->fk_commande=$placeid;
            $com->qty=1;
            $com->tva_tx=1;
            //$com->fk_product=$id;
            $com->subprice=1;
			$com->label='Varios';
            $com->rang='1';
            $com->total_ht=1;
            $com->total_ttc=1;
            $com->fk_parent_line='';
			$com->total_tva=1;
			$com->pa_ht = '1';
            $com->price=1;
			}	
				
            $result=$com->insert();		
$db->commit();
}



if ($action=="addline" || $action="q")
{
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
}


//Get records from database
$sql="SELECT ".MAIN_DB_PREFIX."commande.rowid as id, ".MAIN_DB_PREFIX."commandedet.rowid as iddet, ".MAIN_DB_PREFIX."commandedet.label as label, ".MAIN_DB_PREFIX."commandedet.qty as qty, ".MAIN_DB_PREFIX."commandedet.price as price, ".MAIN_DB_PREFIX."commandedet.remise_percent as remise, ".MAIN_DB_PREFIX."commandedet.total_ttc as total, ".MAIN_DB_PREFIX."commandedet.fk_product as idprod, ".MAIN_DB_PREFIX."commandedet.description as notes FROM ".MAIN_DB_PREFIX."commande, ".MAIN_DB_PREFIX."commandedet where ".MAIN_DB_PREFIX."commande.rowid=".MAIN_DB_PREFIX."commandedet.fk_commande and ".MAIN_DB_PREFIX."commande.rowid=$placeid";
$resql = $db->query($sql);

//Add all records to an array
$rows = array();
while($row = $db->fetch_array ($resql))
{
	$row['price']=number_format($row['price'], 2, '.', '');
	$row['total']=number_format($row['total'], 2, '.', '');
    $rows[] = $row;
}
 
//Return result to jTable
$jTableResult = array();
$jTableResult['Customer'] = $customer;
$jTableResult['Records'] = $rows;
print json_encode($jTableResult);
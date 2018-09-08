<?php




$res=@include("../../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../../main.inc.php");                // For "custom" directory

dol_include_once('/pos/class/ticket.class.php');
dol_include_once('/pos/class/cash.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
global $langs, $db, $mysoc, $conf;

$langs->load("main");
$langs->load("pos@pos");
$langs->load('users');
$langs->load('client');
header("Content-type: text/html; charset=".$conf->file->character_set_client);
$id=GETPOST('id');
//$terminal=GETPOST('terminal');
$form = new Form($db);
?>
<html>
<head>
<title>Print ticket</title>

<style type="text/css">

	body {
		font-size: 14px;
		position: relative;
		font-family: monospace,courier,arial,helvetica,system;
	}

	.entete {
/* 		position: relative; */
	}

		.adresse {
/* 			float: left; */
			font-size: 12px;
		}

		.date_heure {
			position: absolute;
			top: 0;
			right: 0;
			font-size: 15px;
		}

		.infos {
			position: relative;
			font-size: 14px;
		}


	.liste_articles {
		width: 100%;
		border-bottom: 1px solid #000;
		text-align: center;
		font-size: 14px;
	}

		.liste_articles tr.titres th {
			border-bottom: 1px solid #000;
			font-size: 14px;
		}

		.liste_articles td.total {
			text-align: right;
			font-size: 14px;
		}

	.totaux {
		margin-top: 11px;
		width: 30%;
		float: right;
		text-align: right;
		font-size: 14px;
	}

	.lien {
		position: absolute;
		top: 0;
		left: 0;
		display: none;
		font-size: 14px;
	}

	@media print {

		.lien {
			display: none;
		}

	}

</style>

</head>

<body onload="window.print()" onafterprint="<?php echo ($conf->global->POS_CLOSE_WIN ?'window.close()':''); ?>">

<?php

		// Cash
		
		$sql = "select ref, fk_user, date_c, fk_cash";
    	$sql .=" from ".MAIN_DB_PREFIX."pos_control_cash";
    	$sql .=" where rowid = ".$id;
    	$result=$db->query($sql);
		
		if ($result)
		{
			$objp = $db->fetch_object($result);
        	$date_end = $objp->date_c;
        	$fk_user = $objp->fk_user;
        	$ref = $objp->ref;
        	$terminal = $objp->fk_cash;
        }
        
		$sql = "select date_c";
    	$sql .=" from ".MAIN_DB_PREFIX."pos_control_cash";
    	$sql .=" where fk_cash = ".$terminal." AND date_c < '".$date_end."' AND type_control = 1";
    	$sql .=" ORDER BY date_c DESC";
    	$sql .=" LIMIT 1";
    	$result=$db->query($sql);
		
		if ($result)
		{
			$objd = $db->fetch_object($result);
        	$date_start = $objd->date_c;
        }
        

	?>



<div class="entete">
	<div class="logo">
	<?php print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('/thumbs/'.$mysoc->logo_small).'">'; ?>
	</div>
	<div class="infos">
		<p class="adresse"><?php echo $mysoc->name; ?><br>
		<?php echo $mysoc->idprof1; ?><br>
		<?php echo $mysoc->address; ?><br>
		<?php echo $mysoc->zip.' '.$mysoc->town; ?></p>
		<?php
			print '<p>'.$langs->trans("CloseCashReport").': '.$ref.'<br>';
			$cash = new Cash($db);
			$cash->fetch($terminal);
			print $langs->trans("Terminal").': '.$cash->name.'<br>';
			
			$userstatic=new User($db);
			$userstatic->fetch($fk_user);
			print $langs->trans("User").': '.$userstatic->firstname.' '.$userstatic->lastname.'</p>';
			print '<p class="date_heure">'.dol_print_date($db->jdate($date_end),'dayhour').'</p>';
		?>
	</div>
</div>
<?php if (!empty($cash->fk_modepaycash)) {?>
<p><?php $form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$id,$cash->fk_modepaycash,'none'); ?></p>
<table class="liste_articles">
	<tr class="titres"><th><?php print $langs->trans("Ticket"); ?></th><th><?php print $langs->trans("Total"); ?></th></tr>

	<?php

		// Cash
		
		$sql = "SELECT t.ticketnumber, pt.amount, t.type";
    	$sql .=" FROM ".MAIN_DB_PREFIX."pos_ticket as t, ".MAIN_DB_PREFIX."pos_paiement_ticket as pt, ".MAIN_DB_PREFIX."paiement as p";
    	$sql .=" WHERE t.fk_cash=".$terminal." AND p.fk_paiement=".$cash->fk_modepaycash." AND t.fk_statut > 0 AND p.datep > '".$date_start."' AND p.datep < '".$date_end."'";
    	$sql .= " AND p.rowid = pt.fk_paiement AND t.rowid = pt.fk_ticket ";
    	
    	$sql .= " UNION SELECT f.facnumber, pfac.amount, f.type";
    	$sql .= " FROM ".MAIN_DB_PREFIX."pos_facture as pf,".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement_facture as pfac, ".MAIN_DB_PREFIX."paiement as p ";
    	$sql .= " WHERE pf.fk_cash=".$terminal." AND p.fk_paiement=".$cash->fk_modepaycash. " AND pf.fk_facture = f.rowid and f.fk_statut > 0 AND p.datep > '".$date_start."' AND p.datep < '".$date_end."'";
    	$sql .= " AND p.rowid = pfac.fk_paiement AND f.rowid = pfac.fk_facture";
    	
    	$result=$db->query($sql);
		
		if ($result)
		{
			$num = $db->num_rows($result);
			if($num>0)
			{
	            $i = 0;
	            $subtotalcash=0;
	            while ($i < $num)
	            {
	            	$objp = $db->fetch_object($result);
	            	//if($objp->type == 1)$objp->amount= $objp->amount * -1;
	            	echo ('<tr><td align="left">'.$objp->ticketnumber.'</td><td align="right">'.price($objp->amount).'</td></tr>');
	            	$i++;
	            	$subtotalcash+=$objp->amount;
	            }
			}
			else
			{
				echo ('<tr><td align="left">'.$langs->Trans("NoTickets").'</td></tr>');
			}	
		}

	?>
</table>

<table class="totaux">
	<?php
	
	echo '<tr><th nowrap="nowrap">'.($langs->trans("Sales").' '. $form->cache_types_paiements[$cash->fk_modepaycash]['label'] ).'</th><td nowrap="nowrap">'.price($subtotalcash)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
	?>
</table>

<br><br>
<?php } if (!empty($cash->fk_modepaybank)){?>
    <p><?php $form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$id,$cash->fk_modepaybank,'none'); ?></p>
<table class="liste_articles">
	<tr class="titres"><th><?php print $langs->trans("Ticket"); ?></th><th><?php print $langs->trans("Total"); ?></th></tr>

	<?php

		// Credit card
		$sql = "SELECT t.ticketnumber, pt.amount, t.type";
    	$sql .=" FROM ".MAIN_DB_PREFIX."pos_ticket as t, ".MAIN_DB_PREFIX."pos_paiement_ticket as pt, ".MAIN_DB_PREFIX."paiement as p";
    	$sql .=" WHERE t.fk_cash=".$terminal." AND p.fk_paiement=".$cash->fk_modepaybank." AND t.fk_statut > 0 AND p.datep > '".$date_start."' AND p.datep < '".$date_end."'";
    	$sql .= " AND p.rowid = pt.fk_paiement AND t.rowid = pt.fk_ticket ";
    	
    	$sql .= " UNION SELECT f.facnumber, pfac.amount, f.type";
    	$sql .= " FROM ".MAIN_DB_PREFIX."pos_facture as pf,".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement_facture as pfac, ".MAIN_DB_PREFIX."paiement as p ";
    	$sql .= " WHERE pf.fk_cash=".$terminal." AND p.fk_paiement=".$cash->fk_modepaybank." AND pf.fk_facture = f.rowid and f.fk_statut > 0 AND p.datep > '".$date_start."' AND p.datep < '".$date_end."'";
    	$sql .= " AND p.rowid = pfac.fk_paiement AND f.rowid = pfac.fk_facture";
    	 
    	$result=$db->query($sql);
		
		if ($result)
		{
			$num = $db->num_rows($result);
			if($num>0)
			{
	            $i = 0;
	            $subtotalcard1=0;
	            while ($i < $num)
	            {
	            	$objp = $db->fetch_object($result);
	            	//if($objp->type == 1)$objp->amount= $objp->amount * -1;
	            	echo ('<tr><td align="left">'.$objp->ticketnumber.'</td><td align="right">'.price($objp->amount).'</td></tr>');
	            	$i++;
	            	$subtotalcard1+=$objp->amount;
	            }
			}
			else
			{
				echo ('<tr><td align="left">'.$langs->Trans("NoTickets").'</td></tr>');
			}	
		}

	?>
</table>

<table class="totaux">
	<?php
	echo '<tr><th nowrap="nowrap">'.($langs->trans("Sales").' '. $form->cache_types_paiements[$cash->fk_modepaybank]['label'] ) .'</th><td nowrap="nowrap">'.price($subtotalcard1)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
	
	?>
</table>
<br><br>
<?php } if ($cash->fk_modepaybank != $cash->fk_modepaybank_extra){?>

    <p><?php $form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$id,$cash->fk_modepaybank_extra,'none'); ?></p>
    <table class="liste_articles">
        <tr class="titres"><th><?php print $langs->trans("Ticket"); ?></th><th><?php print $langs->trans("Total"); ?></th></tr>

        <?php

        // Credit card
        $sql = "SELECT t.ticketnumber, pt.amount, t.type";
        $sql .=" FROM ".MAIN_DB_PREFIX."pos_ticket as t, ".MAIN_DB_PREFIX."pos_paiement_ticket as pt, ".MAIN_DB_PREFIX."paiement as p";
        $sql .=" WHERE t.fk_cash=".$terminal." AND p.fk_paiement=".$cash->fk_modepaybank_extra." AND t.fk_statut > 0 AND p.datep > '".$date_start."' AND p.datep < '".$date_end."'";
        $sql .= " AND p.rowid = pt.fk_paiement AND t.rowid = pt.fk_ticket ";

        $sql .= " UNION SELECT f.facnumber, pfac.amount, f.type";
        $sql .= " FROM ".MAIN_DB_PREFIX."pos_facture as pf,".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement_facture as pfac, ".MAIN_DB_PREFIX."paiement as p ";
        $sql .= " WHERE pf.fk_cash=".$terminal." AND p.fk_paiement=".$cash->fk_modepaybank_extra." AND pf.fk_facture = f.rowid and f.fk_statut > 0 AND p.datep > '".$date_start."' AND p.datep < '".$date_end."'";
        $sql .= " AND p.rowid = pfac.fk_paiement AND f.rowid = pfac.fk_facture";

        $result=$db->query($sql);

        if ($result)
        {
            $num = $db->num_rows($result);
            if($num>0)
            {
                $i = 0;
                $subtotalcard2=0;
                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);
                    //if($objp->type == 1)$objp->amount= $objp->amount * -1;
                    echo ('<tr><td align="left">'.$objp->ticketnumber.'</td><td align="right">'.price($objp->amount).'</td></tr>');
                    $i++;
                    $subtotalcard2+=$objp->amount;
                }
            }
            else
            {
                echo ('<tr><td align="left">'.$langs->Trans("NoTickets").'</td></tr>');
            }
        }

        ?>
    </table>

    <table class="totaux">
        <?php
        echo '<tr><th nowrap="nowrap">'.($langs->trans("Sales").' '. $form->cache_types_paiements[$cash->fk_modepaybank_extra]['label'] ) .'</th><td nowrap="nowrap">'.price($subtotalcard2)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";

        ?>
    </table>
    <br><br>

<?php } ?>
<?php if(!empty($conf->rewards->enabled)){?>
<p><?php print $langs->trans("Points"); ?></p>
<table class="liste_articles">
	<tr class="titres"><th><?php print $langs->trans("Ticket"); ?></th><th><?php print $langs->trans("Total"); ?></th></tr>

	<?php

		$sql = " SELECT f.facnumber, pfac.amount, f.type";
    	$sql .= " FROM ".MAIN_DB_PREFIX."pos_facture as pf,".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement_facture as pfac, ".MAIN_DB_PREFIX."paiement as p ";
    	$sql .= " WHERE pf.fk_cash=".$terminal." AND p.fk_paiement= 100 AND pf.fk_facture = f.rowid and f.fk_statut > 0 AND p.datep > '".$date_start."' AND p.datep < '".$date_end."'";
    	$sql .= " AND p.rowid = pfac.fk_paiement AND f.rowid = pfac.fk_facture";
    	 
    	$result=$db->query($sql);
		
		if ($result)
		{
			$num = $db->num_rows($result);
			if($num>0)
			{
	            $i = 0;
	            $subtotalpoint=0;
	            while ($i < $num)
	            {
	            	$objp = $db->fetch_object($result);
	            	
	            	echo ('<tr><td align="left">'.$objp->facnumber.'</td><td align="right">'.price($objp->amount).'</td></tr>');
	            	$i++;
	            	$subtotalpoint+=$objp->amount;
	            }
			}
			else
			{
				echo ('<tr><td align="left">'.$langs->Trans("NoTickets").'</td></tr>');
			}	
		}

	?>
</table>
<?php }/*?>
<table class="totaux">
	<?php
	if(!empty($conf->rewards->enabled)){ 
		echo '<tr><th nowrap="nowrap">'.$langs->trans("TotalPoints").'</th><td nowrap="nowrap">'.price($subtotalpoint)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
	}
	echo '<tr></td><td></td><td></tr>';
	echo '<tr></td><td></td><td></tr>';
	echo '<tr></td><td></td><td></tr>';
	
	$sql = "SELECT t.ticketnumber, t.type, l.total_ht, l.tva_tx, l.total_tva, l.total_localtax1, l.total_localtax2, l.total_ttc";
	$sql .=" FROM ".MAIN_DB_PREFIX."pos_ticket as t left join ".MAIN_DB_PREFIX."pos_ticketdet as l on l.fk_ticket= t.rowid";
	$sql .=" WHERE t.fk_control = ".$id." AND t.fk_cash=".$terminal." AND t.fk_statut > 0";
	
	$sql .= " UNION SELECT f.facnumber, f.type, fd.total_ht, fd.tva_tx, fd.total_tva, fd.total_localtax1, fd.total_localtax2, fd.total_ttc";
	$sql .=" FROM ".MAIN_DB_PREFIX."pos_facture as pf,".MAIN_DB_PREFIX."facture as f left join ".MAIN_DB_PREFIX."facturedet as fd on fd.fk_facture= f.rowid";
	$sql .=" WHERE pf.fk_control_cash = ".$id." AND pf.fk_cash=".$terminal." AND pf.fk_facture = f.rowid and f.fk_statut > 0";
	
	$result=$db->query($sql);
	
	if ($result)
	{
		$num = $db->num_rows($result);
		if($num>0)
		{
			$i = 0;
			$subtotalcardht=0;
			while ($i < $num)
			{
				$objp = $db->fetch_object($result);
				$i++;
				if($objp->type == 1){
					$objp->total_ht= $objp->total_ht * -1;
					$objp->total_tva= $objp->total_tva * -1;
					$objp->total_ttc= $objp->total_ttc * -1;
					$objp->total_localtax1= $objp->total_localtax1 * -1;
					$objp->total_localtax2= $objp->total_localtax2 * -1;
				}
				
				$subtotalcardht+=$objp->total_ht;
				$subtotalcardtva[$objp->tva_tx] += $objp->total_tva;
				$subtotalcardttc += $objp->total_ttc;
				$subtotalcardlt1 += $objp->total_localtax1;
				$subtotalcardlt2 += $objp->total_localtax2;
			}
		}
		
	}
	if(! empty($subtotalcardht))echo '<tr><th nowrap="nowrap" style="border-top: 1px solid #000000;">'.$langs->trans("TotalHT").'</th><td nowrap="nowrap" style="border-top: 1px solid #000000;">'.price($subtotalcardht)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
	if(! empty($subtotalcardtva)){
		foreach($subtotalcardtva as $tvakey => $tvaval){
			if($tvakey > 0)
				echo '<tr><th nowrap="nowrap">'.$langs->trans("TotalVAT").' '.round($tvakey).'%'.'</th><td nowrap="nowrap">'.price($tvaval)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
		}
	}
	if($subtotalcardlt1)
		echo '<tr><th nowrap="nowrap">'.$langs->transcountrynoentities("TotalLT1",$mysoc->country_code).'</th><td nowrap="nowrap">'.price($subtotalcardlt1)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
	if($subtotalcardlt2)
		echo '<tr><th nowrap="nowrap">'.$langs->transcountrynoentities("TotalLT2",$mysoc->country_code).'</th><td nowrap="nowrap">'.price($subtotalcardlt2)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
		
	echo '<tr><th nowrap="nowrap">'.$langs->trans("TotalPOS").'</th><td nowrap="nowrap">'.price($subtotalcardttc)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
	echo '</table>';
	*/?>
<br><br>

</body>
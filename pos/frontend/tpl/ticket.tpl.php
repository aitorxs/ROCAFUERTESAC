<?php



$res=@include("../../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../../main.inc.php");                // For "custom" directory

dol_include_once('/pos/class/ticket.class.php');
dol_include_once('/pos/class/cash.class.php');
dol_include_once('/pos/class/place.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once (DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
global $langs,$db,$mysoc,$conf;

$langs->load("main");
$langs->load("pos@pos");
header("Content-type: text/html; charset=".$conf->file->character_set_client);
$id=GETPOST('id');
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
			float: right;
		font-size: 12px;
		width: 100%;
		text-align: center;
		}

		.infos {
			position: relative;
			font-size: 14px;
		}


	.liste_articles {
		width: 100%;
		border-bottom: 1px solid #000;
		text-align: center;
		font-size: 12px;
	}

		.liste_articles tr.titres th {
			border-bottom: 1px solid #000;
			font-size: 13px;
		}

		.liste_articles td.total {
			text-align: right;
			font-size: 13px;
		}
		
	.total_tot {
	    font-size: 15px;
	    font-weight: bold;
	    text-align: right;
	}	

	.totaux {
		margin-top: 20px;
		width: 40%;
		float: right;
		text-align: right;
		font-size: 14px;
	}	
		
	.totpay {
		margin-left: 50%;
		width: 30%;
		float: right;
		text-align: right;
		font-size: 14px;
	}
	
	.note{
		float: right;
		font-size: 12px;
		width: 100%;
		text-align: center;
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
		@page{ 

		    margin: 0; 

		}

	}

</style>

</head>

<body onload="window.print()" onafterprint="<?php echo ($conf->global->POS_CLOSE_WIN ?'window.close()':''); ?>">

<div class="entete">
	<div class="logo">
	<?php print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('/thumbs/'.$mysoc->logo_small).'">'; ?>
	</div>
	<div class="infos">
		<p class="adresse"><?php echo $mysoc->name; ?><br>
		<?php echo $mysoc->address; ?><br>
		<?php echo $mysoc->zip.' '.$mysoc->town; ?><br>
		<?php echo $mysoc->phone; ?><br><br>
		
		<?php
		
			// Variables
		
			$object=new Ticket($db);
			$result=$object->fetch($id,$ref);
			
			$userstatic=new User($db);
			$userstatic->fetch($object->user_close);
			print $langs->trans("Vendor").': '.$userstatic->firstname.' '.$userstatic->lastname.'<br><br>';
			if(!empty($object->fk_place))
			{
				$place = new Place($db);
				$place->fetch($object->fk_place);
				print $langs->trans("Place").': '.$place->name."</p>";
			}
			
			
		?>
	</div>
</div>

<?php
if ($result){
	if (! empty($object->lines)){	
		$onediscount = false;			
		foreach ($object->lines as $line){
			if($line->remise_percent)
				$onediscount = true;
		}
	}
}

?>
<div class="infos"><?php print $object->note?></div>
<table class="liste_articles">
	<tr class="titres"><th><?php print $langs->trans("Label"); ?></th><th><?php print $langs->trans("Qty")."/".$langs->trans("Price"); ?></th><?php if($onediscount)print '<th>'.$langs->trans("DiscountLineal").'</th>'; ?><th><?php print $langs->trans("Total"); ?></th></tr>

	<?php
		
		if ($result)
		{
			//$object->getLinesArray();
			if (! empty($object->lines))
			{
                if (!empty($conf->global->MAIN_MULTILANGS)) {
                    $outputlangs = new Translate("",$conf);
                    $outputlangs->setDefaultLang($userstatic->lang);
                }
				//$subtotal=0;
				foreach ($object->lines as $line)
				{
				    $label = $line->libelle;

                    if (! empty($conf->global->MAIN_MULTILANGS)) {
                        $prodser = new Product($db);
                        if ($line->fk_product) {
                            $prodser->fetch($line->fk_product);

                            if (!empty($conf->global->MAIN_MULTILANGS)) {
                                $label = $prodser->label;
                            }
                        }
                    }
					$totalline= $line->qty*$line->subprice;
					echo ('<tr><td align="left">'.$label.(!empty($line->note)?'<br>'.$line->note:'').'</td><td align="left">'.$line->qty." * ".price(($conf->global->POS_TICKET_TTC?$line->subprice*(1+$line->tva_tx/100):$line->subprice),0,'',1,-1,$conf->global->MAIN_MAX_DECIMALS_TOT).'</td>'.($onediscount?'<td align="right">'.$line->remise_percent.'%</td>':'').'<td class="total">'.price(($conf->global->POS_TICKET_TTC?$line->total_ttc:$line->total_ht)).'</td></tr>');
					$subtotal[$line->tva_tx] += $line->total_ht;;
					$subtotaltva[$line->tva_tx] += $line->total_tva;
					if(!empty($line->total_localtax1)){
						$localtax1 = $line->localtax1_tx;
					}
					if(!empty($line->total_localtax2)){
						$localtax2 = $line->localtax2_tx;
					}
				}
			}
			else 
			{
				echo ('<p>'.$langs->trans("ErrNoArticles").'</p>'."\n");
			}
					
		}
		

	?>
</table>
<div class="total_tot"><?php echo $langs->trans("TotalTTC").'   '.price($object->total_ttc).' '.$langs->trans(currency_name($conf->currency));?></div>
<table class="totaux">
	<?php
		/*if($object->remise_percent>0)
		{
			echo '<tr><th nowrap="nowrap">'.$langs->trans("Subtotal").'</th><td nowrap="nowrap">'.price($subtotal)."</td></tr>\n";
			echo '<tr><th nowrap="nowrap">'.$langs->trans("DiscountGlobal").'</th><td nowrap="nowrap">'.$object->remise_percent."%</td></tr>\n";
		}*/
	echo '<tr><th nowrap="nowrap" style="width:50%;">'.$langs->trans("TotalHT").'</th><th nowrap="nowrap" style="width:25%;">'.$langs->trans("VAT").'</th><th nowrap="nowrap" style="width:25%;">'.$langs->trans("TotalVAT").'</th></tr>';
	if(! empty($subtotal)){
		foreach($subtotal as $totkey => $totval){
			echo '<tr><td nowrap="nowrap" style="text-align:left;">'.price($subtotal[$totkey]).'</td><td nowrap="nowrap">'.price($totkey).'%</td><td nowrap="nowrap">'.price($subtotaltva[$totkey]).'</td></tr>';
		}
	}
	
	
	echo '<tr><td nowrap="nowrap" style="border-top: 1px dashed #000000;text-align:left;">'.price($object->total_ht).'</td><td style="border-top: 1px dashed #000000;">--</td><td nowrap="nowrap" style="border-top: 1px dashed #000000;">'.price($object->total_tva)."</td></tr>";
		
		if($object->total_localtax1!=0){
			echo '<tr><td></td><th nowrap="nowrap">'.$langs->transcountrynoentities("TotalLT1",$mysoc->country_code).' '.price($localtax1).'%</th><td nowrap="nowrap">'.price($object->total_localtax1)."</td></tr>";
		}
		if($object->total_localtax2!=0){
			echo '<tr><td></td><th nowrap="nowrap">'.$langs->transcountrynoentities("TotalLT2",$mysoc->country_code).' '.price($localtax2).'%</th><td nowrap="nowrap">'.price($object->total_localtax2)."</td></tr>";
		}
		?>
				</table>
				
				<table class="totpay">
				<?php 
		echo '<tr><td></td></tr>';
		echo '<tr><td></td></tr>';

		$terminal = new Cash($db);
		$terminal->fetch($object->fk_cash);
		
		//if ($object->type==0)
		{
			$pay = $object->getSommePaiement();
		
			if($object->customer_pay > $pay)
				$pay = $object->customer_pay;
			
		}
		$diff_payment = $object->total_ttc - $pay;
		$listofpayments=$object->getListOfPayments();
		foreach($listofpayments as $paym)
		{
			if($paym['type'] != 'LIQ'){
				echo '<tr><th nowrap="nowrap">'.$terminal->select_Paymentname(dol_getIdFromCode($db,$paym['type'],'c_paiement')).'</th><td nowrap="nowrap">'.price($paym['amount'])." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
			}
			else{
				echo '<tr><th nowrap="nowrap">'.$terminal->select_Paymentname(dol_getIdFromCode($db,$paym['type'],'c_paiement')).'</th><td nowrap="nowrap">'.price($paym['amount']-($diff_payment<0?$diff_payment:0))." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
			}
		}
				
		echo '<tr><th nowrap="nowrap">'.($diff_payment<0?$langs->trans("CustomerRet"):$langs->trans("CustomerDeb")).'</th><td nowrap="nowrap">'.price(abs($diff_payment))." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
		
	?>
</table>

<div class="note"><p><?php print $conf->global->POS_PREDEF_MSG; ?> </p></div>
<div><?php // Recuperation et affichage de la date et de l'heure
			$now = dol_now();
			$label=$object->ref;
			$facture = new Facture($db);
			if($object->fk_facture){
				$facture->fetch($object->fk_facture);
				$label=$facture->ref;
			}
			
			print '<p class="date_heure" align="right">'.$label." ".dol_print_date($object->date_closed,'dayhour').'</p>';?></div>

</body>
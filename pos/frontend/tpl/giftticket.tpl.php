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
<h3>
<?php 
print $langs->trans("GiftTicket");
?>
</h3>
<table class="liste_articles">
	<tr class="titres"><th><?php print $langs->trans("Label"); ?></th><th><?php print $langs->trans("Qty")?></th></tr>

	<?php
		
		if ($result) {
            $object->getLinesArray();
            if (!empty($object->lines)) {

                if (!empty($conf->global->MAIN_MULTILANGS)) {
                    $outputlangs = new Translate("",$conf);
                    $outputlangs->setDefaultLang($userstatic->lang);
                }

                foreach ($object->lines as $line) {
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

                    $totalline = $line->qty * $line->subprice;
                    echo('<tr><td align="left">' . $label . '</td><td align="right">' . $line->qty . "</td></tr>\n");
                }
            } else {
                echo('<p>' . $langs->trans("ErrNoArticles") . '</p>' . "\n");
            }

        }
		

	?>
</table>

<table class="totaux">
	<?php
		
		$terminal = new Cash($db);
		$terminal->fetch($object->fk_cash);
		
		if ($object->type==0)
		{
			echo '<tr><th nowrap="nowrap">'.$langs->trans("Pago").'</th><td nowrap="nowrap">'.$terminal->select_Paymentname($object->mode_reglement_id)."</td></tr>\n";
			
		}
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
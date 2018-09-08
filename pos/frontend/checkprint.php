<?php
/* Copyright (C) 2013 		Andreu Bisquerra Gayà	<andreu@artadigital.com>
 * Copyright (C) 2014-2017 	Ferran Marcet			<fmarcet@2byte.es>
 * Released under the MIT license
 */

$res=@include("../../main.inc.php");
if (! $res) $res=@include("../../../main.inc.php");  
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
dol_include_once('/pos/class/cash.class.php');
dol_include_once('/pos/class/ticket.class.php');

global $conf, $mysoc, $db, $langs;

$pending_print = explode(',',$conf->global->POS_PENDING_PRINT);
$id=substr($pending_print[0], 1);


$langs->load("main");
$langs->load("pos@pos");
$langs->load("rewards@rewards");
$langs->load("bills");

if ($pending_print[0][0]=="F" || $pending_print[0][0]=="J") {
	$json_data = array();
	$facture = new Facture($db);
	$res = $facture->fetch($id);
	//$mysoc = new Societe($db);
	//$mysoc->fetch($facture->socid);
	$userstatic = new User($db);
	$userstatic->fetch($facture->user_valid);

	$json_data['type'] = $pending_print[0][0];

	$json_data['ref'] = $facture->ref;

	$json_data['mysoc_name'] = dol_string_unaccent(($mysoc->name ? $mysoc->name : ""));
	$json_data['mysoc_address'] = dol_string_unaccent(($mysoc->address ? $mysoc->address : ""));
	$json_data['mysoc_town'] = dol_string_unaccent(($mysoc->zip ? $mysoc->zip : "") . ' ' . ($mysoc->town ? $mysoc->town : ""));
	$json_data['mysoc_idprof'] = ($mysoc->idprof1 ? $mysoc->idprof1 : "");

	$json_data['datetime'] = dol_print_date($facture->date_creation, 'day');
	$json_data['vendor'] = dol_string_unaccent($langs->transnoentities("Vendor") . ': ' . $userstatic->firstname . " " . $userstatic->lastname);

	$client = new Societe($db);
	$client->fetch($facture->socid);
	$json_data['client_name'] = dol_string_unaccent(($client->name ? $client->name : ""));
	$json_data['client_idprof'] = ($client->idprof1 ? $client->idprof1 : "");
	$json_data['client_address'] = dol_string_unaccent(($client->address ? $client->address : ""));

	$sql = "SELECT fk_place,fk_cash FROM " . MAIN_DB_PREFIX . "pos_facture WHERE fk_facture =" . $facture->id;
	$result = $db->query($sql);

	if ($result) {
		$objp = $db->fetch_object($result);
		if ($objp->fk_place > 0) {
			$place = new Place($db);
			$place->fetch($objp->fk_place);
			$json_data['place'] = dol_string_unaccent($langs->trans("Place") . ': ' . $place->name);
		} else {
			$json_data['place'] = "";
		}
	}

	$json_data["gift"] = $langs->trans("GiftTicket");

	$json_data['header_lines'] = dol_string_unaccent(str_pad(substr($langs->transnoentities("Label"), 0, 23), 25, ' ',
					STR_PAD_RIGHT) . str_pad(substr($langs->transnoentities("Qty"), 0, 5), 6, ' ',
					STR_PAD_LEFT) . str_pad(substr($langs->transnoentities("Total"), 0, 5), 12, ' ', STR_PAD_LEFT));
	$rows = array();
	if (!empty($facture->lines)) {
		//$subtotal=0;

		foreach ($facture->lines as $line) {
			$totalline = $line->qty * $line->subprice;
			if (empty($line->libelle)) {
				$line->libelle = $line->description;
			}
			//$row['price']=number_format($line->subprice, 2, '.', '');
			$row['total'] = number_format($totalline, 2, '.', '');
			$row['label'] = substr($line->libelle, 0, 23);
			$row['label'] = dol_string_unaccent(str_pad($row['label'], 25, ' ', STR_PAD_RIGHT));
			/*if ($row['qty']<10) $row['qty'] ="  ".$line->qty;
			if ($row['qty']>=10 and $row['qty']<100) $row['qty']=" ".$line->qty;*/
			$row['qty'] = str_pad($line->qty, 6, ' ', STR_PAD_LEFT);
			$row['total'] = str_pad($row['total'], 12, ' ', STR_PAD_LEFT);
			/*if ($row['total']<10) $row['total']="       ".$row['total'];
			if ($row['total']>=10 and $row['total']<100) $row['total']="      ".$row['total'];
			if ($row['total']>=100 and $row['total']<1000) $row['total']="     ".$row['total'];
			if ($row['total']>=1000 and $row['total']<10000) $row['total']="   ".$row['total'];
			if ($row['total']>=10000 and $row['total']<100000) $row['total']="  ".$row['total'];
			if ($row['total']>=100000 and $row['total']<1000000) $row['total']=" ".$row['total'];*/
			$rows[] = $row;

			$subtotal[$line->tva_tx] += $line->total_ht;
			$subtotaltva[$line->tva_tx] += $line->total_tva;
			if (!empty($line->total_localtax1)) {
				$localtax1 = $line->localtax1_tx;
			}
			if (!empty($line->total_localtax2)) {
				$localtax2 = $line->localtax2_tx;
			}
		}
	} else {
		$row['label'] = $langs->transnoentities("ErrNoArticles");
		$rows[] = $row;
	}
	$json_data['lines'] = $rows;

	$json_data['total_ttc1'] = str_pad($langs->transnoentities("TotalTTC") . ":" . price($facture->total_ttc) . " " . $langs->trans(currency_name($conf->currency)),
			36, ' ', STR_PAD_LEFT);
	$json_data['total_ttc2'] = str_pad($langs->transnoentities("TotalTTC") . ":" . price($facture->total_ttc) . " " . $langs->trans(currency_name($conf->currency)),
			36, ' ', STR_PAD_RIGHT);
	$json_data['header_desg'] = dol_string_unaccent(str_pad(substr($langs->trans("TotalHT"), 0, 11), 12, ' ',
					STR_PAD_RIGHT) . str_pad(substr($langs->trans("VAT"), 0, 11), 12, ' ',
					STR_PAD_RIGHT) . str_pad(substr($langs->trans("TotalVAT"), 0, 11), 12, ' ', STR_PAD_LEFT));

	$desgs = array();
	if (!empty($subtotal)) {
		foreach ($subtotal as $totkey => $totval) {
			if ($totkey > 0) {
				$desg = str_pad(price($subtotal[$totkey]), 12, ' ', STR_PAD_RIGHT) . str_pad(price($totkey) . "% ", 12,
								' ', STR_PAD_RIGHT) . str_pad(price($subtotaltva[$totkey]), 12, ' ', STR_PAD_LEFT);
				$desgs[] = $desg;
			}
		}
	} else {
		$desg = $langs->transnoentities("ErrNoArticles");
		$desgs[] = $desg;
	}
	$json_data['desg_lines'] = $desgs;
	$json_data['desg_tot'] = str_pad(price($facture->total_ht), 12, ' ',
					STR_PAD_RIGHT) . "----------- " . str_pad(price($facture->total_tva), 12, ' ', STR_PAD_LEFT);

	if ($facture->total_localtax1 != 0) {
		$json_data['localtax1'] = $langs->transcountrynoentities("TotalLT1",
						$mysoc->country_code) . " " . price($localtax1) . "% " . price($facture->total_localtax1) . " " . $langs->trans(currency_name($conf->currency));
	} else {
		$json_data['localtax1'] = "";
	}
	if ($facture->total_localtax2 != 0) {
		$json_data['localtax2'] = $langs->transcountrynoentities("TotalLT2",
						$mysoc->country_code) . " " . price($localtax2) . "% " . price($facture->total_localtax2) . " " . $langs->trans(currency_name($conf->currency));
	} else {
		$json_data['localtax2'] = "";
	}

	$terminal = new Cash($db);
	$sql = 'SELECT fk_cash, customer_pay FROM ' . MAIN_DB_PREFIX . 'pos_facture WHERE fk_facture = ' . $facture->id;
	$resql = $db->query($sql);
	$obj = $db->fetch_object($resql);
	$customer_pay = $obj->customer_pay;
	$terminal->fetch($obj > fk_cash);

	if (!empty($conf->rewards->enabled)) {
        dol_include_once('/rewards/class/rewards.class.php');
		$rewards = new Rewards($db);
		$points = $rewards->getInvoicePoints($facture->id);
	}
	if ($facture->type == 0) {
		$pay = $facture->getSommePaiement();

		if (!empty($conf->rewards->enabled)) {
			$usepoints = abs($rewards->getInvoicePoints($facture->id, 1));
			$moneypoints = abs($usepoints * $conf->global->REWARDS_DISCOUNT);//falta fer algo per aci
			if ($customer_pay > $pay - $moneypoints) {
				$pay = $customer_pay;
			} else {
				$pay = $pay - $moneypoints;
			}
		} else {
			if ($customer_pay > $pay) {
				$pay = $customer_pay;
			}
		}
	}
	if ($facture->type == 2) {
		$customer_pay = $customer_pay * -1;
		$pay = $facture->getSommePaiement();

		if (!empty($conf->rewards->enabled)) {
			$usepoints = abs($rewards->getInvoicePoints($facture->id, 1));
			$moneypoints = abs($usepoints * $conf->global->REWARDS_DISCOUNT);//falta fer algo per aci
			if ($customer_pay > $pay - $moneypoints) {
				$pay = $customer_pay;
			} else {
				$pay = $pay - $moneypoints;
			}
		} else {
			if ($customer_pay > $pay) {
				$pay = $customer_pay;
			}
		}
	}
	$diff_payment = $facture->total_ttc - $moneypoints - $pay;
	$listofpayments = $facture->getListOfPayments();
	$pays = array();
	if (!empty($listofpayments)) {
		foreach ($listofpayments as $paym) {
			if ($paym['type'] != 'PNT') {
				if ($paym['type'] != 'LIQ') {
					$paytext = $terminal->select_Paymentname(dol_getIdFromCode($db, $paym['type'],
									'c_paiement')) . " " . price($paym['amount']) . " " . $langs->trans(currency_name($conf->currency));
				} else {
					$paytext = $terminal->select_Paymentname(dol_getIdFromCode($db, $paym['type'],
									'c_paiement')) . " " . price($paym['amount'] - ($diff_payment < 0 ? $diff_payment : 0)) . " " . $langs->trans(currency_name($conf->currency));
				}
				$pays[] = dol_string_unaccent($paytext);
			}
		}
	} else {
		$paytext = "";
		$pays[] = $paytext;
	}
	$json_data['pays_lines'] = $pays;
	if (!empty($conf->rewards->enabled)) {
		if ($moneypoints > 0) {
			$json_data['pays_point'] = dol_string_unaccent($langs->transnoentities("Bonification") . ': ' . $usepoints . " " . $langs->trans("Points") . " " . price($moneypoints) . " " . $langs->trans(currency_name($conf->currency)));
		}
	}

	$json_data['customer_ret'] = dol_string_unaccent(($diff_payment < 0 ? $langs->trans("CustomerRet") : $langs->trans("CustomerDeb")) . " " . price(abs($diff_payment)) . " " . $langs->trans(currency_name($conf->currency)));

	if ($points != 0 && !empty($conf->rewards->enabled)) {
		$json_data['total_points'] = dol_string_unaccent($langs->trans("TotalPointsInvoice") . " " . price($points) . " " . $langs->trans('Points'));
		$total_points = $rewards->getCustomerPoints($facture->socid);
		$json_data['dispo_points'] = dol_string_unaccent($langs->trans("DispoPoints") . " " . price($total_points) . " " . $langs->trans('Points'));
	}
	$json_data['predef_msg'] = dol_string_unaccent($conf->global->POS_PREDEF_MSG);

}
	
if ($pending_print[0][0]=="T" or $pending_print[0][0]=="G") {
	$json_data = array();
	$ticket = new Ticket($db);
	$res = $ticket->fetch($id);
	$mysoc = new Societe($db);
	$mysoc->fetch($ticket->socid);
	$userstatic = new User($db);
	$userstatic->fetch($ticket->user_close);

	$label = $ticket->ref;
	$facture = new Facture($db);
	if ($ticket->fk_facture) {
		$facture->fetch($ticket->fk_facture);
		$label = $facture->ref;
	}
	$json_data['type'] = $pending_print[0][0];

	$json_data['ref'] = $label;

	$json_data['mysoc_name'] = dol_string_unaccent(($mysoc->name ? $mysoc->name : ""));
	$json_data['mysoc_address'] = dol_string_unaccent(($mysoc->address ? $mysoc->address : ""));
	$json_data['mysoc_town'] = dol_string_unaccent(($mysoc->zip ? $mysoc->zip : "") . ' ' . ($mysoc->town ? $mysoc->town : ""));
	$json_data['mysoc_idprof'] = ($mysoc->idprof1 ? $mysoc->idprof1 : "");

	$json_data['datetime'] = dol_print_date($facture->date_closed, 'day');
	$json_data['vendor'] = dol_string_unaccent($langs->transnoentities("Vendor") . ': ' . $userstatic->firstname . " " . $userstatic->lastname);

	if (!empty($ticket->fk_place)) {
		$place = new Place($db);
		$place->fetch($ticket->fk_place);
		$json_data['place'] = dol_string_unaccent($langs->trans("Place") . ': ' . $place->name);
	} else {
		$json_data['place'] = "";
	}
	$json_data["gift"] = $langs->trans("GiftTicket");

	$json_data['header_lines'] = dol_string_unaccent(str_pad(substr($langs->transnoentities("Label"), 0, 23), 25, ' ',
					STR_PAD_RIGHT) . str_pad(substr($langs->transnoentities("Qty"), 0, 5), 6, ' ',
					STR_PAD_LEFT) . str_pad(substr($langs->transnoentities("Total"), 0, 5), 12, ' ', STR_PAD_LEFT));
	$rows = array();
	//$ticket->getLinesArray();
	if (!empty($ticket->lines)) {
		//$subtotal=0;
		foreach ($ticket->lines as $line) {
			$totalline = $line->qty * $line->subprice;
			if (empty($line->libelle)) {
				$line->libelle = $line->description;
			}
			//$row['price']=number_format($line->subprice, 2, '.', '');
			$row['total'] = number_format($totalline, 2, '.', '');
			$row['label'] = substr($line->libelle, 0, 23);
			$row['label'] = dol_string_unaccent(str_pad($row['label'], 25, ' ', STR_PAD_RIGHT));
			/*if ($row['qty']<10) $row['qty'] ="  ".$line->qty;
			if ($row['qty']>=10 and $row['qty']<100) $row['qty']=" ".$line->qty;*/
			$row['qty'] = str_pad($line->qty, 6, ' ', STR_PAD_LEFT);
			$row['total'] = str_pad($row['total'], 12, ' ', STR_PAD_LEFT);
			/*if ($row['total']<10) $row['total']="       ".$row['total'];
			if ($row['total']>=10 and $row['total']<100) $row['total']="      ".$row['total'];
			if ($row['total']>=100 and $row['total']<1000) $row['total']="     ".$row['total'];
			if ($row['total']>=1000 and $row['total']<10000) $row['total']="   ".$row['total'];
			if ($row['total']>=10000 and $row['total']<100000) $row['total']="  ".$row['total'];
			if ($row['total']>=100000 and $row['total']<1000000) $row['total']=" ".$row['total'];*/
			$rows[] = $row;

			$subtotal[$line->tva_tx] += $line->total_ht;
			$subtotaltva[$line->tva_tx] += $line->total_tva;
			if (!empty($line->total_localtax1)) {
				$localtax1 = $line->localtax1_tx;
			}
			if (!empty($line->total_localtax2)) {
				$localtax2 = $line->localtax2_tx;
			}
		}
	} else {
		$row['label'] = $langs->transnoentities("ErrNoArticles");
		$rows[] = $row;
	}
	$json_data['lines'] = $rows;

	$json_data['total_ttc1'] = str_pad($langs->transnoentities("TotalTTC") . ":" . price($ticket->total_ttc) . " " . $langs->trans(currency_name($conf->currency)),
			36, ' ', STR_PAD_LEFT);
	$json_data['total_ttc2'] = str_pad($langs->transnoentities("TotalTTC") . ":" . price($ticket->total_ttc) . " " . $langs->trans(currency_name($conf->currency)),
			36, ' ', STR_PAD_RIGHT);
	$json_data['header_desg'] = dol_string_unaccent(str_pad(substr($langs->trans("TotalHT"), 0, 11), 12, ' ',
					STR_PAD_RIGHT) . str_pad(substr($langs->trans("VAT"), 0, 11), 12, ' ',
					STR_PAD_RIGHT) . str_pad(substr($langs->trans("TotalVAT"), 0, 11), 12, ' ', STR_PAD_LEFT));

	$desgs = array();
	if (!empty($subtotal)) {
		foreach ($subtotal as $totkey => $totval) {
			if ($totkey > 0) {
				$desg = dol_string_unaccent(str_pad(price($subtotal[$totkey]), 12, ' ',
								STR_PAD_RIGHT) . str_pad(price($totkey) . "% ", 12, ' ',
								STR_PAD_RIGHT) . str_pad(price($subtotaltva[$totkey]), 12, ' ', STR_PAD_LEFT));
				$desgs[] = $desg;
			}
		}
	} else {
		$desg = $langs->transnoentities("ErrNoArticles");
		$desgs[] = $desg;
	}
	$json_data['desg_lines'] = $desgs;
	$json_data['desg_tot'] = str_pad(price($facture->total_ht), 12, ' ',
					STR_PAD_RIGHT) . "----------- " . str_pad(price($facture->total_tva), 12, ' ', STR_PAD_LEFT);

	if ($facture->total_localtax1 != 0) {
		$json_data['localtax1'] = $langs->transcountrynoentities("TotalLT1",
						$mysoc->country_code) . " " . price($localtax1) . "% " . price($ticket->total_localtax1) . " " . $langs->trans(currency_name($conf->currency));
	} else {
		$json_data['localtax1'] = "";
	}
	if ($facture->total_localtax2 != 0) {
		$json_data['localtax2'] = $langs->transcountrynoentities("TotalLT2",
						$mysoc->country_code) . " " . price($localtax2) . "% " . price($ticket->total_localtax2) . " " . $langs->trans(currency_name($conf->currency));
	} else {
		$json_data['localtax2'] = "";
	}

	$terminal = new Cash($db);
	$terminal->fetch($ticket->fk_cash);

	$pay = $ticket->getSommePaiement();

	if ($ticket->customer_pay > $pay) {
		$pay = $ticket->customer_pay;
	}


	$diff_payment = $ticket->total_ttc - $pay;
	$listofpayments = $ticket->getListOfPayments();
	$pays = array();
	if (!empty($listofpayments)) {
		foreach ($listofpayments as $paym) {
			if ($paym['type'] != 'LIQ') {
				$paytext = $terminal->select_Paymentname(dol_getIdFromCode($db, $paym['type'],
								'c_paiement')) . " " . price($paym['amount']) . " " . $langs->trans(currency_name($conf->currency));
			} else {
				$paytext = $terminal->select_Paymentname(dol_getIdFromCode($db, $paym['type'],
								'c_paiement')) . " " . price($paym['amount'] - ($diff_payment < 0 ? $diff_payment : 0)) . " " . $langs->trans(currency_name($conf->currency));
			}
			$pays[] = dol_string_unaccent($paytext);
		}
	} else {
		$paytext = "";
		$pays[] = $paytext;
	}
	$json_data['pays_lines'] = $pays;

	$json_data['customer_ret'] = dol_string_unaccent(($diff_payment < 0 ? $langs->trans("CustomerRet") : $langs->trans("CustomerDeb")) . " " . price(abs($diff_payment)) . " " . $langs->trans(currency_name($conf->currency)));

	$json_data['predef_msg'] = dol_string_unaccent($conf->global->POS_PREDEF_MSG);

}

if ($pending_print[0][0]=="C") {

	$json_data = array();
	$sql = "select fk_user, date_c, fk_cash, ref";
	$sql .= " from " . MAIN_DB_PREFIX . "pos_control_cash";
	$sql .= " where rowid = " . $id;
	$result = $db->query($sql);

	if ($result) {
		$objp = $db->fetch_object($result);
		$date = $objp->date_c;
		$fk_user = $objp->fk_user;
		$terminal = $objp->fk_cash;
		$ref = $objp->ref;
	}

	$cash = new Cash($db);
	$cash->fetch($terminal);

	$userstatic = new User($db);
	$userstatic->fetch($fk_user);

	$json_data['type'] = $pending_print[0][0];

	$json_data['ref'] = $langs->transnoentities("CloseCashReport") . ': ' . $ref;
	$json_data['terminal'] = $langs->transnoentities("Terminal") . ': ' . $cash->name;

	$json_data['mysoc_name'] = dol_string_unaccent($mysoc->name);
	$json_data['mysoc_address'] = dol_string_unaccent($mysoc->address);
	$json_data['mysoc_town'] = dol_string_unaccent($mysoc->zip . ' ' . $mysoc->town);
	$json_data['mysoc_idprof'] = $mysoc->idprof1;

	$json_data['datetime'] = dol_print_date($db->jdate($date), 'day');
	$json_data['vendor'] = dol_string_unaccent($langs->transnoentities("User") . ': ' . $userstatic->firstname . " " . $userstatic->lastname);


	$json_data['header_cash'] = $langs->transnoentities("TicketsCash");
	$json_data['header_lines'] = str_pad($langs->transnoentities("Ticket"), 18, ' ',
					STR_PAD_RIGHT) . str_pad($langs->transnoentities("Total"), 18, ' ', STR_PAD_LEFT);

	$sql = "SELECT t.ticketnumber, p.amount, t.type";
	$sql .= " FROM " . MAIN_DB_PREFIX . "pos_ticket as t, " . MAIN_DB_PREFIX . "pos_paiement_ticket as pt, " . MAIN_DB_PREFIX . "paiement as p";
	$sql .= " WHERE t.fk_control = " . $id . " AND t.fk_cash=" . $terminal . " AND p.fk_paiement=" . $cash->fk_modepaycash . " AND t.fk_statut > 0";
	$sql .= " AND p.rowid = pt.fk_paiement AND t.rowid = pt.fk_ticket ";

	$sql .= " UNION SELECT f.facnumber, p.amount, f.type";
	$sql .= " FROM " . MAIN_DB_PREFIX . "pos_facture as pf," . MAIN_DB_PREFIX . "facture as f, " . MAIN_DB_PREFIX . "paiement_facture as pfac, " . MAIN_DB_PREFIX . "paiement as p ";
	$sql .= " WHERE pf.fk_control_cash = " . $id . " AND pf.fk_cash=" . $terminal . " AND p.fk_paiement=" . $cash->fk_modepaycash . " AND pf.fk_facture = f.rowid and f.fk_statut > 0";
	$sql .= " AND p.rowid = pfac.fk_paiement AND f.rowid = pfac.fk_facture";

	$result = $db->query($sql);

	if ($result) {
		$rows = array();
		$num = $db->num_rows($result);
		if ($num > 0) {
			$i = 0;
			$subtotalcash = 0;
			while ($i < $num) {
				$objp = $db->fetch_object($result);

				$row['label'] = str_pad($objp->ticketnumber, 18, ' ', STR_PAD_RIGHT) . str_pad(price($objp->amount), 18,
								' ', STR_PAD_LEFT);
				$i++;
				$subtotalcash += $objp->amount;
				$rows[] = $row;
			}
		} else {
			$row['label'] = $langs->transnoentities("NoTickets");
			$rows[] = $row;
		}
	}
	$json_data['cash_lines'] = $rows;

	$json_data['footer_cash'] = $langs->trans("TotalCash") . " " . price($subtotalcash) . " " . $langs->trans(currency_name($conf->currency));
	$json_data['header_bank'] = $langs->trans("TicketsCreditCard");


	// Credit card
	$sql = "SELECT t.ticketnumber, p.amount, t.type";
	$sql .= " FROM " . MAIN_DB_PREFIX . "pos_ticket as t, " . MAIN_DB_PREFIX . "pos_paiement_ticket as pt, " . MAIN_DB_PREFIX . "paiement as p";
	$sql .= " WHERE t.fk_control = " . $id . " AND t.fk_cash=" . $terminal . " AND (p.fk_paiement=" . $cash->fk_modepaybank . " OR p.fk_paiement=" . $cash->fk_modepaybank_extra . ")AND t.fk_statut > 0";
	$sql .= " AND p.rowid = pt.fk_paiement AND t.rowid = pt.fk_ticket ";

	$sql .= " UNION SELECT f.facnumber, p.amount, f.type";
	$sql .= " FROM " . MAIN_DB_PREFIX . "pos_facture as pf," . MAIN_DB_PREFIX . "facture as f, " . MAIN_DB_PREFIX . "paiement_facture as pfac, " . MAIN_DB_PREFIX . "paiement as p ";
	$sql .= " WHERE pf.fk_control_cash = " . $id . " AND pf.fk_cash=" . $terminal . " AND (p.fk_paiement=" . $cash->fk_modepaybank . " OR p.fk_paiement=" . $cash->fk_modepaybank_extra . ") AND pf.fk_facture = f.rowid and f.fk_statut > 0";
	$sql .= " AND p.rowid = pfac.fk_paiement AND f.rowid = pfac.fk_facture";

	$result = $db->query($sql);

	if ($result) {
		$rows1 = array();
		$num = $db->num_rows($result);
		if ($num > 0) {
			$i = 0;
			$subtotalcard = 0;
			while ($i < $num) {
				$objp = $db->fetch_object($result);

				$row['label'] = str_pad($objp->ticketnumber, 18, ' ', STR_PAD_RIGHT) . str_pad(price($objp->amount), 18,
								' ', STR_PAD_LEFT);
				$i++;
				$subtotalcard += $objp->amount;
				$rows1[] = $row;
			}
		} else {
			$row['label'] = $langs->transnoentities("NoTickets");
			$rows1[] = $row;
		}
	}
	$json_data['bank_lines'] = $rows1;

	$json_data['footer_bank'] = $langs->trans("TotalCard") . " " . price($subtotalcard) . " " . $langs->trans(currency_name($conf->currency));

	if (!empty($conf->rewards->enabled)) {
        dol_include_once('/rewards/class/rewards.class.php');
		$json_data['header_points'] = $langs->trans("Points");

		$sql = " SELECT f.facnumber, p.amount, f.type";
		$sql .= " FROM " . MAIN_DB_PREFIX . "pos_facture as pf," . MAIN_DB_PREFIX . "facture as f, " . MAIN_DB_PREFIX . "paiement_facture as pfac, " . MAIN_DB_PREFIX . "paiement as p ";
		$sql .= " WHERE pf.fk_control_cash = " . $id . " AND pf.fk_cash=" . $terminal . " AND p.fk_paiement= 100 AND pf.fk_facture = f.rowid and f.fk_statut > 0";
		$sql .= " AND p.rowid = pfac.fk_paiement AND f.rowid = pfac.fk_facture";

		$result = $db->query($sql);

		if ($result) {
			$rows2 = array();
			$num = $db->num_rows($result);
			if ($num > 0) {
				$i = 0;
				$subtotalpoint = 0;
				while ($i < $num) {
					$objp = $db->fetch_object($result);
					$row['label'] = str_pad($objp->facnumber, 18, ' ', STR_PAD_RIGHT) . str_pad(price($objp->amount),
									18, ' ', STR_PAD_LEFT);
					$i++;
					$subtotalpoint += $objp->amount;
					$rows2[] = $row;
				}
			} else {
				$row['label'] = $langs->transnoentities("NoTickets");
				$rows2[] = $row;
			}
		}
		$json_data['points_lines'] = $rows2;

		$json_data['footer_points'] = $langs->trans("TotalPoints") . " " . price($subtotalpoint) . " " . $langs->trans(currency_name($conf->currency));
	}
	$sql = "SELECT t.ticketnumber, t.type, l.total_ht, l.tva_tx, l.total_tva, l.total_localtax1, l.total_localtax2, l.total_ttc";
	$sql .= " FROM " . MAIN_DB_PREFIX . "pos_ticket as t left join " . MAIN_DB_PREFIX . "pos_ticketdet as l on l.fk_ticket= t.rowid";
	$sql .= " WHERE t.fk_control = " . $id . " AND t.fk_cash=" . $terminal . " AND t.fk_statut > 0";

	$sql .= " UNION SELECT f.facnumber, f.type, fd.total_ht, fd.tva_tx, fd.total_tva, fd.total_localtax1, fd.total_localtax2, fd.total_ttc";
	$sql .= " FROM " . MAIN_DB_PREFIX . "pos_facture as pf," . MAIN_DB_PREFIX . "facture as f left join " . MAIN_DB_PREFIX . "facturedet as fd on fd.fk_facture= f.rowid";
	$sql .= " WHERE pf.fk_control_cash = " . $id . " AND pf.fk_cash=" . $terminal . " AND pf.fk_facture = f.rowid and f.fk_statut > 0";

	$result = $db->query($sql);

	if ($result) {
		$num = $db->num_rows($result);
		if ($num > 0) {
			$i = 0;
			$subtotalcardht = 0;
			while ($i < $num) {
				$objp = $db->fetch_object($result);
				$i++;
				if ($objp->type == 1) {
					$objp->total_ht = $objp->total_ht * -1;
					$objp->total_tva = $objp->total_tva * -1;
					$objp->total_ttc = $objp->total_ttc * -1;
					$objp->total_localtax1 = $objp->total_localtax1 * -1;
					$objp->total_localtax2 = $objp->total_localtax2 * -1;
				}

				$subtotalcardht += $objp->total_ht;
				$subtotalcardtva[$objp->tva_tx] += $objp->total_tva;
				$subtotalcardttc += $objp->total_ttc;
				$subtotalcardlt1 += $objp->total_localtax1;
				$subtotalcardlt2 += $objp->total_localtax2;
			}
		}

	}

	if (!empty($subtotalcardht)) {
		$json_data['total_ht'] = $langs->trans("TotalHT") . " " . price($subtotalcardht) . " " . $langs->trans(currency_name($conf->currency));
	} else {
		$json_data['total_ht'] = "";
	}

	if (!empty($subtotalcardtva)) {
		$desgs = array();
		foreach ($subtotalcardtva as $tvakey => $tvaval) {
			if ($tvakey > 0) {
				$desg = $langs->trans("TotalVAT") . ' ' . round($tvakey) . '%' . " " . price($tvaval) . " " . $langs->trans(currency_name($conf->currency));
				$desgs[] = $desg;
			}
		}
	} else {
		$desg = $langs->transnoentities("NoTickets");
		$desgs[] = $desg;
	}
	$json_data['desg_lines'] = $desgs;
	if ($subtotalcardlt1) {
		$json_data['localtax1'] = $langs->transcountrynoentities("TotalLT1",
						$mysoc->country_code) . " " . price($subtotalcardlt1) . " " . $langs->trans(currency_name($conf->currency));
	} else {
		$json_data['localtax1'] = "";
	}
	if ($subtotalcardlt2) {
		$json_data['localtax2'] = $langs->transcountrynoentities("TotalLT2",
						$mysoc->country_code) . " " . price($subtotalcardlt2) . " " . $langs->trans(currency_name($conf->currency));
	} else {
		$json_data['localtax2'] = "";
	}

	$json_data['total_pos'] = $langs->trans("TotalPOS") . " " . price($subtotalcardttc) . " " . $langs->trans(currency_name($conf->currency));
}

if ($pending_print[0][0]=="D") {
	$json_data['type'] = "D";
}


$drop=strlen($id);
$drop++;$drop++;
dolibarr_set_const($db,"POS_PENDING_PRINT", substr($conf->global->POS_PENDING_PRINT, $drop),'chaine',0);



print json_encode($json_data);
<?php
/* Copyright (C) 2011-2012 Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012-2013 Ferran Marcet           <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU  *General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/pos/backend/listefac.php
 *	\ingroup    factures
 *	\brief      Page to list factures created in POs
 */

$res=@include("../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");                // For "custom" directory

dol_include_once('/pos/class/ticket.class.php');
dol_include_once('/pos/class/facturesim.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
dol_include_once('/pos/class/cash.class.php');
require_once(DOL_DOCUMENT_ROOT ."/core/lib/date.lib.php");
dol_include_once('/pos/class/pos.class.php');
require_once(DOL_DOCUMENT_ROOT ."/compta/facture/class/facture.class.php");

$langs->load('pos@pos');
$langs->load('deliveries');
$langs->load('companies');
$langs->load('bills');
$langs->load('main');

$closeid=GETPOST('closeid','int');


$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$month    =GETPOST('month','int');
$year     =GETPOST('year','int');
$day	=GETPOST("day","int");
$search_ref = GETPOST("search_ref");
$search_societe= GETPOST("search_societe");
$search_montant= GETPOST("search_montant");
$search_paymenttype= GETPOST("search_paymenttype");

$limit = $conf->liste_limit;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='datep';

if ($action == 'send' && !$cancel) 
{
	$langs->load('mails');
	$actiontypecode='';$subject='';$actionmsg='';$actionmsg2='';

	if (GETPOST('sendto'))
	{
		// Le destinataire a ete fourni via le champ libre
		$sendto = GETPOST('sendto');
		$sendtoid = 0;
	}
	if (dol_strlen($sendto))
	{
		$langs->load("commercial");

		$from =  $conf->global->MAIN_INFO_SOCIETE_NOM."<".$conf->global->MAIN_INFO_SOCIETE_MAIL.">";
		$message = GETPOST('message','alpha');

		if (GETPOST('action','alpha') == 'send')
		{
			if (dol_strlen(GETPOST('subject','alpha'))) $subject = GETPOST('subject','alpha');
			else $subject = $langs->transnoentities('Bill').' '.$object->ref;
			$actiontypecode='AC_EMAIL';
			$actionmsg=$langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto.".\n";
			if ($message)
			{
				$actionmsg.=$langs->transnoentities('MailTopic').": ".$subject."\n";
				$actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody').":\n";
				$actionmsg.=$message;
			}
		}


		// Send mail
		require_once(DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php');
		$mailfile = new CMailFile($subject,$sendto,$from,$message);
		if(!preg_match("/^(?:[\w\d]+\.?)+@(?:(?:[\w\d]\-?)+\.)+\w{2,4}$/", $sendto)) {
			$mailfile->error = $langs->trans('ErrorFailedToSendMail',$from,$sendto);
		}

		if ($mailfile->error)
		{
			setEventMessage($mailfile->error,"errors");
		}
		else
		{
			$result=$mailfile->sendfile();
			if ($result)
			{
				setEventMessage($langs->trans('MailSuccessfulySent',$from,$sendto));		// Must not contain "

				Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id.'&mesg=1');
				//exit;

			}
			else
			{
				$langs->load("other");
				if ($mailfile->error)
				{
					setEventMessage($langs->trans('ErrorFailedToSendMail',$from,$sendto).'<br>'.$mailfile->error,"errors");
				}
				else
				{
					setEventMessage('No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS',"errors");
				}
				
			}
		}
	}
	else
	{
		$langs->load("other");
		setEventMessage($langs->trans('ErrorMailRecipientIsEmpty'),"errors");
		dol_syslog('Recipient email is empty');
	}

	$_GET['action'] = 'presend';
}
/*
 * View
 */
$helpurl='EN:Module_DoliPos|FR:Module_DoliPos_FR|ES:M&oacute;dulo_DoliPos';
llxHeader("",$langs->trans("Invoices"),$helpurl);
dol_htmloutput_events();

$html = new FormOther($db);
$form = new Form($db);

$now=dol_now();

if (!$user->rights->pos->backend)
{
	print '<a href="'.dol_buildpath('/pos/frontend/index.php',1).'"><img src='.dol_buildpath('/pos/frontend/img/bgback.png',1).' WIDTH="100%" HEIGHT="100%" ></a>';
}	
else {
	if ($page == -1) $page = 0 ;
	
	
	$sql = "select ref, fk_user, date_c, fk_cash";
    $sql .=" from ".MAIN_DB_PREFIX."pos_control_cash";
    $sql .=" where rowid = ".$closeid;
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
    
    $sql.= " c.code as paiement_code";
    $sql.= " FROM (".MAIN_DB_PREFIX."paiement as p,";
    $sql.= " ".MAIN_DB_PREFIX."c_paiement as c)";
    
	$sql = "SELECT t.rowid, t.ticketnumber as ref, pt.amount, t.type, p.datep,p.fk_paiement,";
    $sql.= ' s.nom, s.rowid as socid, 0 as ticket, c.code as paiement_code';
    $sql .=" FROM ".MAIN_DB_PREFIX."pos_ticket as t, ".MAIN_DB_PREFIX."pos_paiement_ticket as pt, ".MAIN_DB_PREFIX."paiement as p,";
    $sql.= MAIN_DB_PREFIX.'societe as s,'.MAIN_DB_PREFIX.'c_paiement as c';
    $sql .=" WHERE t.fk_cash=".$terminal." AND p.datep > '".$date_start."' AND p.datep < '".$date_end."'";
    $sql .= " AND p.rowid = pt.fk_paiement AND t.rowid = pt.fk_ticket AND s.rowid = t.fk_soc AND p.fk_paiement = c.id";
	if ($search_ref)
    {
    	$sql.= ' AND t.ticketnumber LIKE \'%'.$db->escape(trim($search_ref)).'%\'';
    }
    if ($search_societe)
    {
        $sql.= ' AND s.nom LIKE \'%'.$db->escape(trim($search_societe)).'%\'';
    }
	if ($search_montant)
    {
        $sql.= ' AND pt.amount = \''.$db->escape(trim($search_montant)).'\'';
    }
    if($search_paymenttype){
    	$sql .=" AND c.code='".$search_paymenttype."'";
    }
	if ($month > 0)
    {
        if ($year > 0 && empty($day))
        $sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
        else if ($year > 0 && ! empty($day))
        $sql.= " AND p.datep BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
        else
        $sql.= " AND date_format(p.datep, '%m') = '".$month."'";
    }
    else if ($year > 0)
    {
        $sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
    }
    $sql.= ' GROUP BY p.rowid, t.rowid, pt.amount';
    	
   	$sql .= " UNION SELECT f.rowid, f.facnumber as ref, pfac.amount, f.type, p.datep, p.fk_paiement,";
   	$sql.= ' s.nom, s.rowid as socid, 1 as ticket, c.code as paiement_code';
   	$sql .= " FROM ".MAIN_DB_PREFIX."pos_facture as pf,".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement_facture as pfac, ".MAIN_DB_PREFIX."paiement as p, ";
    $sql.= MAIN_DB_PREFIX.'societe as s,'.MAIN_DB_PREFIX.'c_paiement as c';
   	$sql .= " WHERE pf.fk_cash=".$terminal." AND pf.fk_facture = f.rowid AND p.datep > '".$date_start."' AND p.datep < '".$date_end."'";
   	$sql .= " AND p.rowid = pfac.fk_paiement AND f.rowid = pfac.fk_facture AND s.rowid = f.fk_soc AND p.fk_paiement = c.id";
	if ($search_ref)
    {
    	$sql.= ' AND f.facnumber LIKE \'%'.$db->escape(trim($search_ref)).'%\'';
    }
    if ($search_societe)
    {
        $sql.= ' AND s.nom LIKE \'%'.$db->escape(trim($search_societe)).'%\'';
    }
	if ($search_montant)
    {
        $sql.= ' AND pfac.amount = \''.$db->escape(trim($search_montant)).'\'';
    }
	if($search_paymenttype){
    	$sql .=" AND c.code='".$search_paymenttype."'";
    }
	if ($month > 0)
    {
        if ($year > 0 && empty($day))
        $sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
        else if ($year > 0 && ! empty($day))
        $sql.= " AND p.datep BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
        else
        $sql.= " AND date_format(p.datep, '%m') = '".$month."'";
    }
    else if ($year > 0)
    {
        $sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
    }
   	$sql.= ' GROUP BY rowid, pfac.amount,p.datep, p.fk_paiement';
	
    $sql.= ' ORDER BY '.$sortfield.' '.$sortorder;
    $sql.= $db->plimit($limit+1,$offset);
     
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);

        if ($month) $param.='&amp;month='.$month;
        if ($year)  $param.='&amp;year=' .$year;
        if ($closeid) $param.='&amp;closeid=' .$closeid;
        if ($search_ref) $param.='&amp;search_ref='.$search_ref;
        if ($search_societe) $param.='&amp;search_societe='.$search_societe;
        if ($search_montant) $param.='&amp;search_montant='.$search_montant;
        if ($search_paymenttype) $param.='&amp;search_paymenttype='.$search_paymenttype;
            
        $txtListe = $langs->trans('ReceivedCustomersPayments');
            
        print_barre_liste($txtListe,$page,'listecloses.php',$param,$sortfield,$sortorder,'',$num);

        $i = 0;
        print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
         print '<input type="hidden" name="closeid" value="'.$closeid.'">';
        print '<table class="liste" width="100%">';
        print '<tr class="liste_titre">';
        print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'ref','',$param,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Date'),$_SERVER['PHP_SELF'],'datep','',$param,'align="center"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Customer'),$_SERVER['PHP_SELF'],'nom','',$param,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("Type"),$_SERVER["PHP_SELF"],"paiement_code","",$param,"",$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Amount'),$_SERVER['PHP_SELF'],'amount','',$param,'align="right"',$sortfield,$sortorder);
        print '<td></td>';
        print '</tr>';

        // Filters lines
        print '<tr class="liste_titre">';
        print '<td class="liste_titre" align="left">';
        print '<input class="flat" size="10" type="text" name="search_ref" value="'.$search_ref.'">';
        print '</td>';
        print '<td class="liste_titre" align="center">';
        if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
        print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
        $html->select_year($year?$year:-1,'year',1, 20, 5);
        print '</td>';
        print '<td class="liste_titre" align="left">';
        print '<input class="flat" type="text" name="search_societe" value="'.$search_societe.'">';
        print '</td>';
        print '<td>';
    	$form->select_types_paiements($search_paymenttype,'search_paymenttype','',2,1,1);
  		print '</td>';
        print '<td class="liste_titre" align="right">';
        print '<input class="flat" type="text" size="10" name="search_montant" value="'.$search_montant.'"></td>';
        print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
        print "</td></tr>\n";

        if ($num > 0)
        {
            $var=True;
            $total=0;
            $totalrecu=0;

            while ($i < min($num,$limit))
            {
                $objp = $db->fetch_object($resql);
                $var=!$var;

                print '<tr '.$bc[$var].'>';
                print '<td nowrap="nowrap">';

                if($objp->ticket == 1){
                	$facturestatic=new Facture($db);
				}
                else{
                	$facturestatic=new Ticket($db);
                }
                $facturestatic->id=$objp->rowid;
                $facturestatic->ref=$objp->ref;
                $facturestatic->type=$objp->type;
                print $facturestatic->getNomUrl(1);
                print '</td>';

                // Date
                print '<td align="center" nowrap>';
                print dol_print_date($db->jdate($objp->datep),'day');
                print '</td>';

                //Customer
                print '<td>';
                $thirdparty=new Societe($db);
                $thirdparty->id=$objp->socid;
                $thirdparty->nom=$objp->nom;
                print $thirdparty->getNomUrl(1,'customer');
                print '</td>';
                
                print '<td>'.$langs->trans("PaymentTypeShort".$objp->paiement_code).'</td>';

                print '<td align="right">'.price($objp->amount).'</td>';
			             
				print "<td>&nbsp;</td>";
                print "</tr>\n";
                $total+=$objp->amount;
                $i++;
            }

            if (($offset + $num) <= $limit)
            {
                // Print total
                print '<tr class="liste_total">';
                print '<td class="liste_total" colspan="4" align="left">'.$langs->trans('Total').'</td>';
                print '<td class="liste_total" align="right">'.price($total).'</td>';
                print '<td class="liste_total" align="center">&nbsp;</td>';
                print '</tr>';
            }
        }

        print "</table>\n";
        print "</form>\n";
        $db->free($resql);
		print '<div class="tabsAction">';
		if ($closeid)
		{
			$url = '../frontend/tpl/closecash.tpl.php?id='.$closeid;
			print '<a class="butAction" href='.$url.' target="_blank">'.$langs->trans('PrintCopy').'</a>';
				
			print '<a class="butAction" href="'.dol_buildpath('/pos/backend/listecloses.php',1).'?closeid='.$closeid.'&action=mail">'.$langs->trans('MailCopy').'</a>';
				
		}
		print '</div>';	
		
		if( GETPOST('action','string') == 'mail')
		{
			include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
			$formmail = new FormMail($db);
			
			$action='send';
			$modelmail='body';
			
			print '<br>';
			
			print_titre($langs->trans($titre));
			
			$formmail->fromtype = 'user';
			$formmail->fromid   = $user->id;
			$formmail->fromname = $conf->global->MAIN_INFO_SOCIETE_NOM;
			$formmail->frommail = $conf->global->MAIN_INFO_SOCIETE_MAIL;
			$formmail->withfrom=0;
			$formmail->withto=empty($_POST["sendto"])?1:GETPOST('sendto');
			$formmail->withtocc=0;
			$formmail->withtoccsocid=0;
			$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
			$formmail->withtocccsocid=0;
			$formmail->withtopic=$conf->global->MAIN_INFO_SOCIETE_NOM.': '.$langs->trans("CopyOfCloseCash").' '.$closeid;
			$formmail->withfile=0;
			$formmail->withbody= POS::FillMailCloseCashBody($closeid);
			$formmail->withdeliveryreceipt=0;
			$formmail->withcancel=1;
			
			$formmail->param['action']=$action;
			$formmail->param['models']=$modelmail;
			$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?closeid='.$closeid;
			$formmail->show_form();
			
			print '<br>';
		}        
	}
    else
    {
    	dol_print_error($db);
    }
}
llxFooter();

$db->close();
?>
<?php
/* Copyright (C) 2001-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Eric Seigne             <erics@rycks.com>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2013-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Alexandre Spangaro      <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2017       Ferran Marcet		    <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/contact/list.php
 *      \ingroup    societe
 *		\brief      Page to list all contacts
 */

$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directoryinclude_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
dol_include_once("/labelprint/class/labelprint.class.php");
dol_include_once("/labelprint/lib/labelprint.lib.php");
dol_include_once("/core/lib/admin.lib.php");

global $langs, $user, $conf, $db;

$langs->load("companies");
$langs->load("suppliers");

// Security check
$id = GETPOST('id','int');
$contactid = GETPOST('id','int');
$ref = '';  // There is no ref for contacts
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'contact', $contactid,'');

$sall=GETPOST("sall");
$search_firstlast_only=GETPOST("search_firstlast_only");
$search_lastname=GETPOST("search_lastname");
$search_firstname=GETPOST("search_firstname");
$search_societe=GETPOST("search_societe");
$search_poste=GETPOST("search_poste");
$search_phone=GETPOST("search_phone");
$search_phone_perso=GETPOST("search_phone_perso");
$search_phone_pro=GETPOST("search_phone_pro");
$search_phone_mobile=GETPOST("search_phone_mobile");
$search_fax=GETPOST("search_fax");
$search_email=GETPOST("search_email");
$search_skype=GETPOST("search_skype");
$search_priv=GETPOST("search_priv");
$search_categ=GETPOST("search_categ",'int');
$search_categ_thirdparty=GETPOST("search_categ_thirdparty",'int');
$search_categ_supplier=GETPOST("search_categ_supplier",'int');
$search_status=GETPOST("search_status",'int');
$search_type=GETPOST('search_type','alpha');
$search_job=GETPOST('search_job','alpha');
if ($search_status=='') $search_status=1; // always display activ customer first

$optioncss = GETPOST('optioncss','alpha');
$action=GETPOST('action');

$toPrint = GETPOST('toPrint');
$line= GETPOST('lineid','int');
$confirm = GETPOST('confirm','alpha');

$type=GETPOST("type");
$view=GETPOST("view");

$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
$userid=GETPOST('userid','int');
$begin=GETPOST('begin');
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="p.lastname";
if (empty($page) || $page < 0) { $page = 0; }
$offset = $limit * $page;

$langs->load("companies");

$contextpage='contactlist';
$titre = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("ListOfContacts") : $langs->trans("ListOfContactsAddresses"));
if ($type == "p")
{
    $contextpage='contactprospectlist';
	$titre.='  ('.$langs->trans("ThirdPartyProspects").')';
	$urlfiche="card.php";
}
if ($type == "c")
{
    $contextpage='contactcustomerlist';
	$titre.='  ('.$langs->trans("ThirdPartyCustomers").')';
	$urlfiche="card.php";
}
else if ($type == "f")
{
    $contextpage='contactsupplierlist';
	$titre.=' ('.$langs->trans("ThirdPartySuppliers").')';
	$urlfiche="card.php";
}
else if ($type == "o")
{
    $contextpage='contactotherlist';
	$titre.=' ('.$langs->trans("OthersNotLinkedToThirdParty").')';
	$urlfiche="";
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array($contextpage));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('contact');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'p.lastname'=>'Lastname',
    'p.firstname'=>'Firstname',
    'p.email'=>'EMail',
    's.nom'=>"ThirdParty",
);

// Definition of fields for list
$arrayfields=array(
    'p.lastname'=>array('label'=>$langs->trans("Lastname"), 'checked'=>1),
    'p.firstname'=>array('label'=>$langs->trans("Firstname"), 'checked'=>1),
    'p.poste'=>array('label'=>$langs->trans("PostOrFunction"), 'checked'=>1),
    'p.town'=>array('label'=>$langs->trans("Town"), 'checked'=>0),
    'p.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>0),
    'p.phone'=>array('label'=>$langs->trans("Phone"), 'checked'=>1),
    'p.phone_perso'=>array('label'=>$langs->trans("PhonePerso"), 'checked'=>0),
    'p.phone_mobile'=>array('label'=>$langs->trans("PhoneMobile"), 'checked'=>1),
    'p.fax'=>array('label'=>$langs->trans("Fax"), 'checked'=>1),
    'p.email'=>array('label'=>$langs->trans("EMail"), 'checked'=>1),
    'p.skype'=>array('label'=>$langs->trans("Skype"), 'checked'=>1, 'enabled'=>(! empty($conf->skype->enabled))),
    'p.thirdparty'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1, 'enabled'=>empty($conf->global->SOCIETE_DISABLE_CONTACTS)),
    'p.priv'=>array('label'=>$langs->trans("ContactVisibility"), 'checked'=>1, 'position'=>200),
    'p.datec'=>array('label'=>$langs->trans("DateCreationShort"), 'checked'=>0, 'position'=>500),
    'p.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
    'p.statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
   foreach($extrafields->attribute_label as $key => $val)
   {
       $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>$extrafields->attribute_list[$key], 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>$extrafields->attribute_perms[$key]);
   }
}

$object=new Contact($db);
if (($id > 0 || ! empty($ref)) && $action != 'add')
{
    $result=$object->fetch($id,$ref);
    if ($result < 0) dol_print_error($db);
}


/*
 * Actions
 */

if (GETPOST('cancel')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    // Selection of new fields
    include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

    // Purge search criteria
    if (GETPOST('button_removefilter_x') || GETPOST('button_removefilter.x') || GETPOST('button_removefilter'))	// All tests are required to be compatible with all browsers
    {
        $sall="";
        $search_firstlast_only="";
        $search_lastname="";
        $search_firstname="";
        $search_societe="";
        $search_poste="";
        $search_phone="";
        $search_phone_perso="";
        $search_phone_pro="";
        $search_phone_mobile="";
        $search_fax="";
        $search_email="";
        $search_skype="";
        $search_priv="";
        $search_status=-1;
        $search_categ='';
        $search_categ_thirdparty='';
        $search_categ_supplier='';
        $search_array_options=array();
    }

    // Mass actions
    $objectclass='Contact';
    $objectlabel='Contact';
    $permtoread = $user->rights->societe->lire;
    $permtodelete = $user->rights->societe->delete;
    $uploaddir = $conf->societe->dir_output;
    include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

if ($action == 'create' && $user->rights->societe->lire && empty(GETPOST('button_search')))
    {
        if (is_array($toPrint))
        {
            $object = new LabelsContacts($db);
            $result = $object->multicreate($user,$toPrint);

            if ($result < 0)
            {
                setEventMessage($object->error,"errors");
            }
            else{
                setEventMessage($langs->trans("LinesContactAdded"));
            }
        }
    }

    // Action select position object
    if ($action == 'confirm_position' && $confirm != 'yes') { $action=''; }
    if ($action == 'confirm_position' && $confirm == 'yes')
    {
        $position=GETPOST('position','int');
        $res+=dolibarr_set_const($db,'LAB_START',$position,'chaine',0,'',$conf->entity);

        $pdf=new pdfLabelsContacts();
        $url = $pdf->createPdf();
        $action = 'confirm_truncate';
    }

    // Truncate list to print
    if ($action == "confirm_truncate" && $confirm == 'yes')
    {
        $object = new LabelsContacts($db);
        $result = $object->truncate();

        if ($result > 0)
        {
            if(empty($url)){
                setEventMessage($langs->trans("ListTruncated"));
                Header('Location: '.$_SERVER["PHP_SELF"]);
                exit;
            }
        }
        else
        {
            setEventMessage($object->error,"errors");
        }
    }

    // Add product to list
    if ($action == 'updateline')
    {
        if(GETPOST('save','alpha')!='')
        {
            $qty = GETPOST('qty','int');

            if($qty <= 0){

                $object = new LabelsContacts($db);
                $result = $object->delete($line);

            } else {

                $object = new LabelsContacts($db);
                $object->fetch($line);
                $object->qty=$qty;
                $result = $object->update();
            }
            if ($result > 0)
            {
                setEventMessage($langs->trans("LineContactUpdated"));
            }
            else
            {
                setEventMessage($object->error,"errors");
            }

        }
    }
}

if ($search_priv < 0) $search_priv='';


/*
 * View
 */

$form=new Form($db);
$formother=new FormOther($db);
$contactstatic=new Contact($db);

$title = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));

$sql = "SELECT s.rowid as socid, s.nom as name,";
$sql.= " p.rowid as cidp, p.lastname as lastname, p.statut, p.firstname, p.zip, p.town, p.poste, p.email, p.skype,";
$sql.= " p.phone as phone_pro, p.phone_mobile, p.phone_perso, p.fax, p.fk_pays, p.priv, p.datec as date_creation, p.tms as date_update,";
$sql.= " co.code as country_code";
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as p";
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople_extrafields as ef on (p.rowid = ef.fk_object)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as co ON co.rowid = p.fk_pays";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = p.fk_soc";
if (! empty($search_categ)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_contact as cc ON p.rowid = cc.fk_socpeople"; // We need this table joined to the select in order to filter by categ
if (! empty($search_categ_thirdparty)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_societe as cs ON s.rowid = cs.fk_soc";       // We need this table joined to the select in order to filter by categ
if (! empty($search_categ_supplier)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_fournisseur as cs2 ON s.rowid = cs2.fk_soc";       // We need this table joined to the select in order to filter by categ
if (!$user->rights->societe->client->voir && !$user->socid) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
$sql.= ' WHERE p.entity IN ('.getEntity('societe', 1).')';
if (!$user->rights->societe->client->voir && !$user->socid) //restriction
{
	$sql .= " AND (sc.fk_user = " .$user->id." OR p.fk_soc IS NULL)";
}
if (! empty($userid))    // propre au commercial
{
    $sql .= " AND p.fk_user_creat=".$db->escape($userid);
}

// Filter to exclude not owned private contacts
if ($search_priv != '0' && $search_priv != '1')
{
	$sql .= " AND (p.priv='0' OR (p.priv='1' AND p.fk_user_creat=".$user->id."))";
}
else
{
	if ($search_priv == '0') $sql .= " AND p.priv='0'";
	if ($search_priv == '1') $sql .= " AND (p.priv='1' AND p.fk_user_creat=".$user->id.")";
}

if ($search_categ > 0)   $sql.= " AND cc.fk_categorie = ".$db->escape($search_categ);
if ($search_categ == -2) $sql.= " AND cc.fk_categorie IS NULL";
if ($search_categ_thirdparty > 0)   $sql.= " AND cs.fk_categorie = ".$db->escape($search_categ_thirdparty);
if ($search_categ_thirdparty == -2) $sql.= " AND cs.fk_categorie IS NULL";
if ($search_categ_supplier > 0)   $sql.= " AND cs2.fk_categorie = ".$db->escape($search_categ_supplier);
if ($search_categ_supplier == -2) $sql.= " AND cs2.fk_categorie IS NULL";

if ($search_firstlast_only) {
    $sql .= natural_search(array('p.lastname','p.firstname'), $search_firstlast_only);
}
if ($search_lastname) {      // filter on lastname
    $sql .= natural_search('p.lastname', $search_lastname);
}
if ($search_firstname) {   // filter on firstname
    $sql .= natural_search('p.firstname', $search_firstname);
}
if ($search_societe) {  // filtre sur la societe
    $sql .= natural_search('s.nom', $search_societe);
}
if (strlen($search_poste)) {  // filtre sur la societe
    $sql .= natural_search('p.poste', $search_poste);
}
if (strlen($search_phone))
{
    $sql .= " AND (p.phone LIKE '%".$db->escape($search_phone)."%' OR p.phone_perso LIKE '%".$db->escape($search_phone)."%' OR p.phone_mobile LIKE '%".$db->escape($search_phone)."%')";
}
if (strlen($search_phone_perso))
{
    $sql .= " AND p.phone_perso LIKE '%".$db->escape($search_phone_perso)."%'";
}
if (strlen($search_phone_pro))
{
    $sql .= " AND p.phone LIKE '%".$db->escape($search_phone_pro)."%'";
}
if (strlen($search_phone_mobile))
{
    $sql .= " AND p.phone_mobile LIKE '%".$db->escape($search_phone_mobile)."%'";
}
if (strlen($search_fax))
{
    $sql .= " AND p.fax LIKE '%".$db->escape($search_fax)."%'";
}
if (strlen($search_email))      // filtre sur l'email
{
    $sql .= " AND p.email LIKE '%".$db->escape($search_email)."%'";
}
if (strlen($search_skype))      // filtre sur skype
{
    $sql .= " AND p.skype LIKE '%".$db->escape($search_skype)."%'";
}
if ($search_status != '' && $search_status >= 0) $sql .= " AND p.statut = ".$db->escape($search_status);
if ($type == "o")        // filtre sur type
{
    $sql .= " AND p.fk_soc IS NULL";
}
else if ($type == "f")        // filtre sur type
{
    $sql .= " AND s.fournisseur = 1";
}
else if ($type == "c")        // filtre sur type
{
    $sql .= " AND s.client IN (1, 3)";
}
else if ($type == "p")        // filtre sur type
{
    $sql .= " AND s.client IN (2, 3)";
}
if ($sall)
{
    $sql .= natural_search(array_keys($fieldstosearchall), $sall);
}
if (! empty($socid))
{
    $sql .= " AND s.rowid = ".$socid;
}
// Add where from extra fields
foreach ($search_array_options as $key => $val)
{
    $crit=$val;
    $tmpkey=preg_replace('/search_options_/','',$key);
    $typ=$extrafields->attribute_type[$tmpkey];
    $mode=0;
    if (in_array($typ, array('int','double'))) $mode=1;    // Search on a numeric
    if ($val && ( ($crit != '' && ! in_array($typ, array('select'))) || ! empty($crit)))
    {
        $sql .= natural_search('ef.'.$tmpkey, $crit, $mode);
    }
}
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
// Add order
if ($view == "recent")
{
    $sql.= $db->order("p.datec","DESC");
}
else
{
    $sql.= $db->order($sortfield,$sortorder);
}

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit+1, $offset);

//print $sql;
dol_syslog("contact/list.php", LOG_DEBUG);
$result = $db->query($sql);
if (! $result)
{
    dol_print_error($db);
    exit;
}

$num = $db->num_rows($result);

if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
{
    $obj = $db->fetch_object($result);
    $id = $obj->cidp;
    header("Location: ".DOL_URL_ROOT.'/contact/card.php?id='.$id);
    exit;
}

$help_url='EN:Module_Labels|FR:Module_Labels_FR|ES:M&oacute;dulo_Labels';
llxHeader('',$langs->trans("ImpressProducts"),$help_url);

if (!empty($url)) {
    print '<script language="javascript" type="text/javascript">
           
                    window.open("' . $url . '" );
        </script>';
}

// Confirmation to delete invoice
if ($action == 'truncate')
{
    $text=$langs->trans('ConfirmTruncateList');
    $formconfirm=$form->formconfirm($_SERVER['PHP_SELF']."?id=0",$langs->trans('TruncateList'),$text,'confirm_truncate','',0,1);
    print $formconfirm;
}

$formquestionposition=array(
    'text' => $langs->trans("ConfirmPosition"),
    array('type' => 'text', 'name' => 'position','label' => $langs->trans("HowManyPos"), 'value' => $conf->global->LAB_START, 'size'=>5)
);

$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
$param.='&begin='.urlencode($begin).'&view='.urlencode($view).'&userid='.urlencode($userid).'&contactname='.urlencode($sall);
$param.='&type='.urlencode($type).'&view='.urlencode($view);
if (!empty($search_categ)) $param.='&search_categ='.urlencode($search_categ);
if (!empty($search_categ_thirdparty)) $param.='&search_categ_thirdparty='.urlencode($search_categ_thirdparty);
if (!empty($search_categ_supplier)) $param.='&search_categ_supplier='.urlencode($search_categ_supplier);
if ($sall != '') $param.='&amp;sall='.urlencode($sall);
if ($search_lastname != '') $param.='&amp;search_lastname='.urlencode($search_lastname);
if ($search_firstname != '') $param.='&amp;search_firstname='.urlencode($search_firstname);
if ($search_societe != '') $param.='&amp;search_societe='.urlencode($search_societe);
if ($search_zip != '') $param.='&amp;search_zip='.urlencode($search_zip);
if ($search_town != '') $param.='&amp;search_town='.urlencode($search_town);
if ($search_job != '') $param.='&amp;search_job='.urlencode($search_job);
if ($search_phone_pro != '') $param.='&amp;search_phone_pro='.urlencode($search_phone_pro);
if ($search_phone_perso != '') $param.='&amp;search_phone_perso='.urlencode($search_phone_perso);
if ($search_phone_mobile != '') $param.='&amp;search_phone_mobile='.urlencode($search_phone_mobile);
if ($search_fax != '') $param.='&amp;search_fax='.urlencode($search_fax);
if ($search_email != '') $param.='&amp;search_email='.urlencode($search_email);
if ($search_status != '') $param.='&amp;search_status='.urlencode($search_status);
if ($search_priv == '0' || $search_priv == '1') $param.="&search_priv=".urlencode($search_priv);
if ($optioncss != '') $param.='&optioncss='.$optioncss;
// Add $param from extra fields
foreach ($search_array_options as $key => $val)
{
    $crit=$val;
    $tmpkey=preg_replace('/search_options_/','',$key);
    if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
}

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="view" value="'.dol_escape_htmltag($view).'">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="action" value="create">';

print_barre_liste($titre, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_companies.png', 0, '', '', $limit);

print "<div class=\"tabsAction\">";

$sql = "SELECT rowid";
$sql.= " FROM ".MAIN_DB_PREFIX."labelprint";
$sql.= " WHERE entity='". $conf->entity ."'";
$sql.= " AND typLabel=2";

$resql = $db->query($sql);
if ($resql)
{
    $numrows = $db->num_rows($resql);
    if ($numrows)
    {

        if ($user->rights->societe->lire)
        {
            print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=truncate">'.$langs->trans("Truncate").'</a>';

            if($num){
                if($conf->global->LABELPRINT_CONTACT_A4){
                    print '<span id="action-position" class="butAction">'.$langs->trans('PrintLabels').'</span>'."\n";
                    print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$id.'&ref='.$ref,$langs->trans('SelectPosition'),$langs->trans('ConfirmSelectPosition'),'confirm_position',$formquestionposition,'yes','action-position',170,400);
                } else {
                    print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&amp;ref='.$ref.'&amp;action=confirm_position&amp;confirm=yes">'.$langs->trans('PrintLabels').'</a>';
                }
            }
        }
    }
    else
    {
        print '<a class="butActionRefused" title="'.$langs->trans("ListTruncated").'">'.$langs->trans("Truncate").'</a>';
        print '<a class="butActionRefused" title="'.$langs->trans("ListTruncated").'">'.$langs->trans("PrintLabels").'</a>';
    }

}
print "</div>";
if ($sall)
{
    foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
    print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
}
if ($search_firstlast_only)
{
    print $langs->trans("FilterOnInto", $search_firstlast_only) . $langs->trans("Lastname").", ".$langs->trans("Firstname");
}

if (! empty($conf->categorie->enabled))
{
	require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
    $moreforfilter.='<div class="divsearchfield">';
	$moreforfilter.=$langs->trans('Categories'). ': ';
	$moreforfilter.=$formother->select_categories(Categorie::TYPE_CONTACT,$search_categ,'search_categ',1);
	$moreforfilter.='</div>';
	if (empty($type) || $type == 'c' || $type == 'p')
	{
        $moreforfilter.='<div class="divsearchfield">';
        if ($type == 'c') $moreforfilter.=$langs->trans('CustomersCategoriesShort'). ': ';
    	else if ($type == 'p') $moreforfilter.=$langs->trans('ProspectsCategoriesShort'). ': ';
    	else $moreforfilter.=$langs->trans('CustomersProspectsCategoriesShort'). ': ';
    	$moreforfilter.=$formother->select_categories(Categorie::TYPE_CUSTOMER,$search_categ_thirdparty,'search_categ_thirdparty',1);
    	$moreforfilter.='</div>';
	}
	if (empty($type) || $type == 'f')
	{
    	$moreforfilter.='<div class="divsearchfield">';
		$moreforfilter.=$langs->trans('SuppliersCategoriesShort'). ': ';
    	$moreforfilter.=$formother->select_categories(Categorie::TYPE_SUPPLIER,$search_categ_supplier,'search_categ_supplier',1);
    	$moreforfilter.='</div>';
	}
}
if ($moreforfilter)
{
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	$parameters=array('type'=>$type);
	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
	print '</div>';
}

$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

print '
        <script language="javascript" type="text/javascript">
        jQuery(document).ready(function()
        {
            jQuery("#checkall").click(function()
            {
                jQuery(".checkforproduct").attr(\'checked\', true);
            });
            jQuery("#checknone").click(function()
            {
                jQuery(".checkforproduct").attr(\'checked\', false);
            });
        });
        </script>
        ';

// Ligne des titres
print '<tr class="liste_titre">';
if (! empty($arrayfields['p.lastname']['checked']))            print_liste_field_titre($langs->trans("Lastname"),$_SERVER["PHP_SELF"],"p.lastname", $begin, $param, '', $sortfield,$sortorder);
if (! empty($arrayfields['p.firstname']['checked']))            print_liste_field_titre($langs->trans("Firstname"),$_SERVER["PHP_SELF"],"p.firstname", $begin, $param, '', $sortfield,$sortorder);
if (! empty($arrayfields['p.zip']['checked']))            print_liste_field_titre($langs->trans("Zip"),$_SERVER["PHP_SELF"],"p.zip", $begin, $param, '', $sortfield,$sortorder);
if (! empty($arrayfields['p.town']['checked']))            print_liste_field_titre($langs->trans("Town"),$_SERVER["PHP_SELF"],"p.town", $begin, $param, '', $sortfield,$sortorder);
if (! empty($arrayfields['p.poste']['checked']))            print_liste_field_titre($langs->trans("PostOrFunction"),$_SERVER["PHP_SELF"],"p.poste", $begin, $param, '', $sortfield,$sortorder);
if (! empty($arrayfields['p.phone']['checked']))            print_liste_field_titre($langs->trans("Phone"),$_SERVER["PHP_SELF"],"p.phone", $begin, $param, '', $sortfield,$sortorder);
if (! empty($arrayfields['p.phone_perso']['checked']))            print_liste_field_titre($langs->trans("PhonePerso"),$_SERVER["PHP_SELF"],"p.phone_perso", $begin, $param, '', $sortfield,$sortorder);
if (! empty($arrayfields['p.phone_mobile']['checked']))            print_liste_field_titre($langs->trans("PhoneMobile"),$_SERVER["PHP_SELF"],"p.phone_mobile", $begin, $param, '', $sortfield,$sortorder);
if (! empty($arrayfields['p.fax']['checked']))            print_liste_field_titre($langs->trans("Fax"),$_SERVER["PHP_SELF"],"p.fax", $begin, $param, '', $sortfield,$sortorder);
if (! empty($arrayfields['p.email']['checked']))            print_liste_field_titre($langs->trans("EMail"),$_SERVER["PHP_SELF"],"p.email", $begin, $param, '', $sortfield,$sortorder);
if (! empty($arrayfields['p.skype']['checked']))            print_liste_field_titre($langs->trans("Skype"),$_SERVER["PHP_SELF"],"p.skype", $begin, $param, '', $sortfield,$sortorder);
if (! empty($arrayfields['p.thirdparty']['checked']))            print_liste_field_titre($langs->trans("ThirdParty"),$_SERVER["PHP_SELF"],"s.nom", $begin, $param, '', $sortfield,$sortorder);
if (! empty($arrayfields['p.priv']['checked']))            print_liste_field_titre($langs->trans("ContactVisibility"),$_SERVER["PHP_SELF"],"p.priv", $begin, $param, 'align="center"', $sortfield,$sortorder);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
   foreach($extrafields->attribute_label as $key => $val)
   {
       if (! empty($arrayfields["ef.".$key]['checked']))
       {
			$align=$extrafields->getAlignFlag($key);
			print_liste_field_titre($extralabels[$key],$_SERVER["PHP_SELF"],"ef.".$key,"",$param,($align?'align="'.$align.'"':''),$sortfield,$sortorder);
       }
   }
}
// Hook fields
$parameters=array('arrayfields'=>$arrayfields);
$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (! empty($arrayfields['p.datec']['checked']))  print_liste_field_titre($langs->trans("DateCreationShort"),$_SERVER["PHP_SELF"],"p.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['p.tms']['checked']))    print_liste_field_titre($langs->trans("DateModificationShort"),$_SERVER["PHP_SELF"],"p.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['p.statut']['checked'])) print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"p.statut","",$param,'align="center"',$sortfield,$sortorder);
print '<td align="right">'.$langs->trans("QtyToPrint").'</td>';

    print '<td align="center" width="100px">' . $langs->trans("Select") . "<br>";
    if ($conf->use_javascript_ajax) print '<a href="#" id="checkall">' . $langs->trans("All") . '</a> / <a href="#" id="checknone">' . $langs->trans("None") . '</a>';
    print '</td>';

print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
print "</tr>\n";

// Lines for filter fields
print '<tr class="liste_titre">';
if (! empty($arrayfields['p.lastname']['checked']))
{
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="search_lastname" size="6" value="'.dol_escape_htmltag($search_lastname).'">';
    print '</td>';
}
if (! empty($arrayfields['p.firstname']['checked']))
{
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="search_firstname" size="6" value="'.dol_escape_htmltag($search_firstname).'">';
    print '</td>';
}
if (! empty($arrayfields['p.poste']['checked']))
{
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="search_poste" size="5" value="'.dol_escape_htmltag($search_poste).'">';
    print '</td>';
}
if (! empty($arrayfields['p.zip']['checked']))
{
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="search_zip" size="3" value="'.dol_escape_htmltag($search_zip).'">';
    print '</td>';
}
if (! empty($arrayfields['p.town']['checked']))
{
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="search_town" size="5" value="'.dol_escape_htmltag($search_town).'">';
    print '</td>';
}
if (! empty($arrayfields['p.phone']['checked']))
{
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="search_phone_pro" size="6" value="'.dol_escape_htmltag($search_phone_pro).'">';
    print '</td>';
}
if (! empty($arrayfields['p.phone_perso']['checked']))
{
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="search_phone_perso" size="6" value="'.dol_escape_htmltag($search_phone_perso).'">';
    print '</td>';
}
if (! empty($arrayfields['p.phone_mobile']['checked']))
{
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="search_phone_mobile" size="6" value="'.dol_escape_htmltag($search_phone_mobile).'">';
    print '</td>';
}
if (! empty($arrayfields['p.fax']['checked']))
{
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="search_fax" size="6" value="'.dol_escape_htmltag($search_fax).'">';
    print '</td>';
}
if (! empty($arrayfields['p.email']['checked']))
{
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="search_email" size="6" value="'.dol_escape_htmltag($search_email).'">';
    print '</td>';
}
if (! empty($arrayfields['p.skype']['checked']))
{
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="search_skype" size="6" value="'.dol_escape_htmltag($search_skype).'">';
    print '</td>';
}
if (! empty($arrayfields['p.thirdparty']['checked']))
{
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="search_societe" size="8" value="'.dol_escape_htmltag($search_societe).'">';
    print '</td>';
}
if (! empty($arrayfields['p.priv']['checked']))
{
    print '<td class="liste_titre" align="center">';
   $selectarray=array('0'=>$langs->trans("ContactPublic"),'1'=>$langs->trans("ContactPrivate"));
   print $form->selectarray('search_priv',$selectarray,$search_priv,1);
   print '</td>';
}
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
   foreach($extrafields->attribute_label as $key => $val)
   {
		if (! empty($arrayfields["ef.".$key]['checked']))
		{
			print '<td class="liste_titre">';
			print '</td>';
		}
   }
}
// Fields from hook
$parameters=array('arrayfields'=>$arrayfields);
$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Date creation
if (! empty($arrayfields['p.datec']['checked']))
{
    print '<td class="liste_titre">';
    print '</td>';
}
// Date modification
if (! empty($arrayfields['p.tms']['checked']))
{
    print '<td class="liste_titre">';
    print '</td>';
}
if (! empty($arrayfields['p.statut']['checked']))
{
    print '<td class="liste_titre" align="center">';
    print $form->selectarray('search_status', array('-1'=>'', '0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')),$search_status);
    print '</td>';
}
print '<td class="liste_titre" align="center">';
print '&nbsp;';
print '</td>';

print '<td class="liste_titre" align="center">';
print '&nbsp;';
print '</td>';

print '<td class="liste_titre" align="right">';
print '<input type="image" name="button_search" class="liste_titre" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
print '<input type="image" name="button_removefilter" class="liste_titre" src="'.img_picto($langs->trans("RemoveFilter"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
print '</td>';

print '</tr>';

$var=True;
$i = 0;
while ($i < min($num,$limit))
{
    $obj = $db->fetch_object($result);

	$var=!$var;
    print "<tr ".$bc[$var].">";

	$contactstatic->lastname=$obj->lastname;
	$contactstatic->firstname='';
	$contactstatic->id=$obj->cidp;
	$contactstatic->statut=$obj->statut;
	$contactstatic->poste=$obj->poste;
	$contactstatic->phone_pro=$obj->phone_pro;
	$contactstatic->phone_perso=$obj->phone_perso;
	$contactstatic->phone_mobile=$obj->phone_mobile;
	$contactstatic->zip=$obj->zip;
	$contactstatic->town=$obj->town;

    // Name
    if (! empty($arrayfields['p.lastname']['checked']))
    {
        print '<td valign="middle">';
	  print $contactstatic->getNomUrl(1,'',0);
	  print '</td>';
    }
	// Firstname
    if (! empty($arrayfields['p.firstname']['checked']))
    {
        print '<td>'.$obj->firstname.'</td>';
    }
	// Zip
    if (! empty($arrayfields['p.zip']['checked']))
    {
        print '<td>'.$obj->zip.'</td>';
    }
	// Town
    if (! empty($arrayfields['p.town']['checked']))
    {
        print '<td>'.$obj->town.'</td>';
    }
    // Function
    if (! empty($arrayfields['p.poste']['checked']))
    {
        print '<td>'.dol_trunc($obj->poste,20).'</td>';
    }
    // Phone
    if (! empty($arrayfields['p.phone']['checked']))
    {
        print '<td>'.dol_print_phone($obj->phone_pro,$obj->country_code,$obj->cidp,$obj->socid,'AC_TEL').'</td>';
    }
    // Phone perso
    if (! empty($arrayfields['p.phone_perso']['checked']))
    {
        print '<td>'.dol_print_phone($obj->phone_perso,$obj->country_code,$obj->cidp,$obj->socid,'AC_TEL').'</td>';
    }
    // Phone mobile
    if (! empty($arrayfields['p.phone_mobile']['checked']))
    {
        print '<td>'.dol_print_phone($obj->phone_mobile,$obj->country_code,$obj->cidp,$obj->socid,'AC_TEL').'</td>';
    }
    // Fax
    if (! empty($arrayfields['p.fax']['checked']))
    {
        print '<td>'.dol_print_phone($obj->fax,$obj->country_code,$obj->cidp,$obj->socid,'AC_TEL').'</td>';
    }
    // EMail
    if (! empty($arrayfields['p.email']['checked']))
    {
        print '<td>'.dol_print_email($obj->email,$obj->cidp,$obj->socid,'AC_EMAIL',18).'</td>';
    }
    // Skype
    if (! empty($arrayfields['p.skype']['checked']))
    {
        if (! empty($conf->skype->enabled)) { print '<td>'.dol_print_skype($obj->skype,$obj->cidp,$obj->socid,'AC_SKYPE',18).'</td>'; }
    }
    // Company
    if (! empty($arrayfields['p.thirdparty']['checked']))
    {
		print '<td>';
        if ($obj->socid)
        {
		$objsoc = new Societe($db);
		$objsoc->fetch($obj->socid);
		print $objsoc->getNomUrl(1);
        }
        else
            print '&nbsp;';
        print '</td>';
    }

    // Private/Public
    if (! empty($arrayfields['p.priv']['checked']))
    {
	    print '<td align="center">'.$contactstatic->LibPubPriv($obj->priv).'</td>';
    }

	// Extra fields
	if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
	{
	   foreach($extrafields->attribute_label as $key => $val)
	   {
			if (! empty($arrayfields["ef.".$key]['checked']))
			{
				print '<td';
				$align=$extrafields->getAlignFlag($key);
				if ($align) print ' align="'.$align.'"';
				print '>';
				$tmpkey='options_'.$key;
				print $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
				print '</td>';
			}
	   }
	}
    // Fields from hook
    $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
	$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    // Date creation
    if (! empty($arrayfields['p.datec']['checked']))
    {
        print '<td align="center">';
        print dol_print_date($db->jdate($obj->date_creation), 'dayhour');
        print '</td>';
    }
    // Date modification
    if (! empty($arrayfields['p.tms']['checked']))
    {
        print '<td align="center">';
        print dol_print_date($db->jdate($obj->date_update), 'dayhour');
        print '</td>';
    }
    // Status
    if (! empty($arrayfields['p.statut']['checked']))
    {
        print '<td align="center">'.$contactstatic->getLibStatut(3).'</td>';
    }

$sql = "SELECT rowid, qty";
    $sql.= " FROM ".MAIN_DB_PREFIX."labelprint";
    $sql.= " WHERE fk_object=".$obj->cidp;
    $sql.= " AND entity='". $conf->entity ."'";
    $sql.= " AND typLabel=2";

    $resql = $db->query($sql);

    if ($resql)
    {
        $objtp = $db->fetch_object($resql);

    }


    // Qty to print
    if ($result)
    {
        //$objtp = $db->fetch_object($result);
        if ($action == 'editline' && $user->rights->societe->creer && $line == $objtp->rowid)
        {
            print '<td align="right">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="updateline">';
            print '<input type="hidden" name="id" value="'.$product->id.'">';
            print '<input type="hidden" name="lineid" value="'.$line.'">';
            print '<input type="hidden" name="page" value="'.$page.'">';

            print '<input class="flat" type="text" size="2" name="qty" value="'.$objtp->qty.'"> ';
            print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
            print '<br><input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';

            print '</td>';
            print '</form>';
        }
        else
            print '<td align="right">'.$objtp->qty.'</td>';


    }
    else
    {
        print '<td align="right">0</td>';
    }

    // Action column
    print '<td align="center">';
    print '<input id="'.$i.'" class="flat checkforproduct" type="checkbox" name="toPrint[]" value="'.$obj->cidp.'">';

    if ($user->rights->societe->creer && $action != 'editline' && $objtp->qty)
    {
        print '<a href="'.$_SERVER["PHP_SELF"].'?action=editline&amp;page='.$page.'&amp;lineid='.$objtp->rowid.$param.'">';
        print img_edit();
        print '</a>';
    }
    print '</td>' ;

    print '<td class="liste_titre" align="center">';
    print '&nbsp;';
    print '</td>';

    print "</tr>\n";
    $i++;
}

print "</table>";
print "</div>";

if ($num > $limit || $page) print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_companies.png', 0, '', '', $limit, 1);

/*
     * Boutons Actions
     */

print '<div class="tabsAction">';

if ($user->rights->produit->creer)
{
    print '<input type="submit" class="button" value="'.$langs->trans("AddToPrint").'">';
}
print "</div>";
print '<br>';

print '</form>';

$db->free($result);


llxFooter();
$db->close();

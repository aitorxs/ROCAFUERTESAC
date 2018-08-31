<?php
	date_default_timezone_set("America/Mexico_City");
	require("../../main.inc.php");
	require("../funciones/funciones.php");
	
	$langs->load("admin");

	$action	= ( !GETPOST('action','alpha') ) ? 'create' : GETPOST('action','alpha');

	if( !isset($_REQUEST["mod"]) || $_REQUEST["mod"] == "file" ) { //Recibo los valores de la url para saber que mostrar, por default muestra tab1
		$page 	= "../pages/index.php";
		$tab 	= "uno";
	} 

	$title = "Configuración";
	$arrayofjs = array("/descuento/js/funciones.js");
	$arrayofcss = array('/descuento/css/descuento.css');

	llxHeader('',$title,'','',0,0,$arrayofjs,$arrayofcss);
	//llxHeader('', $title,'','','','',$morejs,'',0,0);

	$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>'; //Enlace para volver al listado de módulos
	print_fiche_titre($title,$linkback,'setup'); //Título y enlace derecho en el top de la página

	$head = timasivo_admin_prepare_head();
	dol_fiche_head($head, $tab, $title, 0, 'product'); //Checar parametros**

	include($page); //Incluye la información a mostrar en cada uno de los tabs

	llxFooter();
?>

<?php
	header('Content-Type: text/plain');

	require ("autoload.php");

	$cliente = new \Sunat\Sunat();
	
	$ruc = ( isset($_REQUEST["intra_vat"]))? $_REQUEST["intra_vat"] : false;
	echo $cliente->search( $ruc, true );
?>

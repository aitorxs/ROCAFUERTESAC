<?php
	require '../../main.inc.php';

	if( $_POST ) {
		$id = $_POST['id'];
		$resql = $db->query("DELETE FROM ".MAIN_DB_PREFIX."descuentos_categorias WHERE rowid=".$id);
		$respuesta = ( $resql ) ? 1: 0;
		print $respuesta;
	}

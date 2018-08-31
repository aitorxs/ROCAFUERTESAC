<?php 
	//Crea un array de tabs
	function timasivo_admin_prepare_head() {
		$h = 0;
		$head = array();

		$head[$h][0] = DOL_URL_ROOT."/descuento/admin/descuento_setup.php";
		$head[$h][1] = "Descuentos por línea de productos";
		$head[$h][2] = "uno";
		$h++;
		return $head;
	}

?>
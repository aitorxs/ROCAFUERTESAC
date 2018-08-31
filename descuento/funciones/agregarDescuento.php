<?php
	require '../../main.inc.php';

	if( $_POST ) {
		$tercero = $_POST['tercero'];
		$categoria = $_POST['categoria'];
		$tipo = $_POST['tipo'];
		$cantidad = $_POST['cantidad'];
		$fecha_inicio = $_POST['fecha_inicio'];
		$fecha_fin = $_POST['fecha_fin'];
		$respuesta = 0;
		$update = 0;

		$resql = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."societe WHERE nom LIKE '%".$tercero."%'");
		$num = $db->num_rows($resql);
		$obj_soc = ( $num > 0 ) ? $db->fetch_object($resql) : '';
		$fk_soc = ( $obj_soc ) ? $obj_soc->rowid : 0;

		$resql = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."descuentos_categorias WHERE fk_categorie=".$categoria." AND fk_soc =".$fk_soc);
		$num = $db->num_rows($resql);

		if( $num > 0 ) {
			$resql = $db->query("UPDATE ".MAIN_DB_PREFIX."descuentos_categorias SET type=".$tipo .", value=".$cantidad.", date_start='".$fecha_inicio."', date_end='".$fecha_fin."' WHERE fk_categorie =".$categoria." AND fk_soc=".$fk_soc);
			$update = 1;
		}
		else {
			$resql = $db->query("INSERT INTO ".MAIN_DB_PREFIX."descuentos_categorias (fk_soc, fk_categorie, type, value, date_start, date_end) VALUE(".$fk_soc.",".$categoria.",".$tipo.",".$cantidad.",'".$fecha_inicio."','".$fecha_fin."')");
			$id = $db->last_insert_id(MAIN_DB_PREFIX."descuentos_categorias");
			$resql = $db->query("SELECT *, d.type as tipo, d.rowid as id FROM ".MAIN_DB_PREFIX."descuentos_categorias d LEFT JOIN ".MAIN_DB_PREFIX."categorie c ON c.rowid = d.fk_categorie LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = d.fk_soc WHERE d.rowid=".$id);
			$obj = $db->fetch_object($resql);
		}

		if( $resql && $update == 0 ) {
			$cantidad = ( $tipo == 1 ) ? $cantidad.'%' : '$'.$cantidad; 
			$tipo = ( $tipo == 1 ) ? 'Porcentaje' : 'Cantidad';
			$fecha_inicio = ( $fecha_inicio ) ? $fecha_inicio: '0000-00-00';
			$fecha_fin = ( $fecha_fin ) ? $fecha_fin: '0000-00-00';
			$estatus = ( $obj->date_start == '0000-00-00' && $obj->date_end == '0000-00-00' ) ? 'Activo' : '';
			$estatus = ( $obj->date_start <= date('Y-m-d') ) ? 'Activo' : 'Pendiente';
			$respuesta = '<tr>';
			$respuesta .= '<td>'.$obj->nom.'</td>';
			$respuesta .= '<td>'.$obj->label.'</td>';
			$respuesta .= '<td>'.$tipo.'</td>';
			$respuesta .= '<td>'.$cantidad.'</td>';
			$respuesta .= '<td>';
			$respuesta .= '<table>';
			$respuesta .= '<tr><td>Inicio:</td><td>'.$fecha_inicio.'</td></tr>';
			$respuesta .= '<tr><td>Fin:</td><td>'.$fecha_fin.'</td></tr>';
			$respuesta .= '</table>';
			$respuesta .= '</td>';
			$respuesta .= '<td>'.$estatus.'</td>';
			$respuesta .= '<td align="center"><img src="../img/delete.png" /></td>';
			$respuesta .= '<tr>';
		}
		else {
			$respuesta = 1;
		}

		print $respuesta;
	}

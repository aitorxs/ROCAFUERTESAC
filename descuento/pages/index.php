<?php



	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';


	

	$sql = "SELECT * FROM ".MAIN_DB_PREFIX."societe f WHERE client = 1 ORDER BY nom";
	$resql = $db->query($sql);
	if ( $resql ) {
		$num = $db->num_rows($resql);
		if ( $num > 0 ) {
			while ($obj = $db->fetch_object($resql)) {
	
				$clients .= "<option value='".$obj->nom."'>".$obj->nom."</option>";
			}
		}
	}
	
	$type = (GETPOST('type') ? GETPOST('type') : Categorie::TYPE_PRODUCT);
	
	$categstatic = new Categorie($db);
	
	$cate_arbo = $categstatic->get_full_arbo($type);
	$fulltree = $cate_arbo;

	foreach($fulltree as $key => $val) {
		$option .= '<option value="'.$val['rowid'].'">'.$val['label'].'</option>';
	}

	$resql = $db->query("SELECT *, d.type as tipo, d.rowid as id FROM ".MAIN_DB_PREFIX."descuentos_categorias d LEFT JOIN ".MAIN_DB_PREFIX."categorie c ON c.rowid = d.fk_categorie LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = d.fk_soc WHERE c.type = 0 ");
	
	while( $obj = $db->fetch_object($resql) ) {

		$obj->value = ( $obj->tipo == 1 ) ? $obj->value.'%' : '$'.$obj->value; 
		$obj->tipo = ( $obj->tipo == 1 ) ? 'Porcentaje' : 'Cantidad';
		
		$estatus = ( $obj->date_start == '0000-00-00' && $obj->date_end == '0000-00-00' ) ? 'Activo' : (($obj->date_start <= date('Y-m-d') && $obj->date_end == '0000-00-00' ) ? 'Activo' : (($obj->date_start <= date('Y-m-d') && $obj->date_end < date('Y-m-d') ) ? 'Inactivo' : 'Pendiente'));
		
		$tr .= '<tr>';
		$tr .= '<td>'.$obj->nom.'</td>';
		$tr .= '<td>'.$obj->label.'</td>';
		$tr .= '<td>'.$obj->value.'</td>';
		$tr .= '<td>';
		$tr .= '<table>';
		$tr .= '<tr><td>Inicio:</td><td>'.$obj->date_start.'</td></tr>';
		$tr .= '<tr><td>Fin:</td><td>'.$obj->date_end.'</td></tr>';
		$tr .= '</table>';
		$tr .= '</td>';
		$tr .= '<td>'.$estatus.'</td>';
		$tr .= '<td align="center"><img src="../img/delete.png" class="eliminar_descuento" id="'.$obj->id.'" /></td>';
		$tr .= '<tr>';
	}

	if ( $action == 'create' ) { 

		print '<span>Favor de ingresar la información solitada para asignar un descuento.</span><br /><br />';
		print '<div id="div_mensaje"></div>';
		print '<form id="form_descuento">';
		print '<input type="hidden" id="tipo" value="1" >';
		print '<table class="liste nohover">';
		print '<tbody><tr class="liste_titre">';
		print '<td>Cliente</td>';
		print '<td width="15%">Línea de producto</td>';
		print '<td width="7%">Cantidad</td>';
		print '<td width="15%">Caducidad</td>';
		print '<td></td></tr>';
		print '<tr>';
		print '<td><select class="flat" id="tercero" ><option value="">--</option>'.$clients.'</select></td>';
		print '<td><select id="categoria" class="flat"><option value="">--</option>'.$option.'</select></td>';

		print '<td><input type="text" id="cantidad" />%</td>';
		print '<td>';
		print '<table>';
		print '<tr><td>Inicio:</td><td><input class="flat" type="date" name="fecha_inicio" id="fecha_inicio" /></td></tr>';
		print '<tr><td>Fin:</td><td><input class="flat" type="date" name="fecha_fin" id="fecha_fin" /></td></tr>';
		print '</table>';
		print '</td>';
		print '<td align="right"><button id="agregar_descuento" class="butAction">Agregar Descuento</button></td>';
		print '</tr></tbody></table>';
		print '</form>';
		print '<br /><br />';

		print '<table class="liste nohover" id="listado_descuento">';
		print '<tbody><tr class="liste_titre">';
		print '<td>Cliente</td>';
		print '<td width="15%">Línea de producto</td>';
	
		print '<td width="7%">Cantidad</td>';
		print '<td width="15%">Caducidad</td>';
		print '<td>Estatus</td>';
		print '<td align="center">Eliminar</td></tr>';
		print $tr;
		print '</tbody></table>';
	}

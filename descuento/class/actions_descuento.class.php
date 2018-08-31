<?php
class Actionsdescuento { 

	function doActions( $parameters, &$object, &$action, $hookmanager ){
		global $db;

		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

		if ( isset($_GET["id_desc"]) && $_GET["id_desc"]>0) {
			$linea_edit= new Propal($db);
			foreach ($object->lines as $key => $linea) {

				if ($linea->fk_product>0) {
					$cat = new Categorie($db);
					$categories = $cat->containing($linea->fk_product, 'product');

					foreach ($categories as $key_cat => $categoria) {
						$sql =" SELECT * FROM llx_descuentos_categorias as a WHERE a.fk_categorie=".$categoria->id." AND a.fk_soc=".$object->socid." AND a.rowid=".$_GET["id_desc"]." AND NOW() BETWEEN a.date_start AND a.date_end ";

						$query=$db->query($sql);
						$res=$db->num_rows($query);

						$descuento=$db->fetch_object($res);


						if ($res>0) {

							//$linea_edit->fetch($linea->id);
							if (isset($_GET["delete"]) && $_GET["delete"]==1) {
								$var=$object->statut;
								$object->statut=0;
								$object->updateline($object->lines[$key]->id,$linea->subprice,$linea->qty, 0,$linea->tva_tx);
								$object->statut=$var;

								$sql='
									DELETE FROM llx_descuentos_presupuestos WHERE fk_descuento='.$_GET["id_desc"].' AND fk_presupuesto='.$object->id;
								$db->query($sql);
							}else{
								$var=$object->statut;
								$object->statut=0;
								$object->updateline($object->lines[$key]->id,$linea->total_ht,$linea->qty,  $linea->remise+$descuento->value,$linea->tva_tx);
								$object->statut=$var;

								$sql='
									INSERT INTO llx_descuentos_presupuestos (
										fk_descuento,
										fk_presupuesto,
										date_create
									)
									VALUES
									('.$_GET["id_desc"].','.$object->id.',now());
								';
								$db->query($sql);
							}


							//$object->update($db);

							//$linea_edit->updateline($linea->id,$linea->total_ht,$linea->qty,  $linea->remise+$descuento->value,$linea->tva_tx);
						}
					}
				}
			}
		}
	}


	function formObjectOptions( $parameters, &$object, &$action, $hookmanager ){
		global $db;


		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		
		if (in_array('societedao', explode(':', $parameters['context']))) {
			
			
			if ($object->statut==1) {
				$arr= array();
				foreach ($object->lines as $key => $linea) {
					//$prod_prueba= new Product($db);
					if ($linea->fk_product>0) {
						//$prod_prueba->fetch($linea->fk_product);
						$cat = new Categorie($db);
						$categories = $cat->containing($linea->fk_product, 'product');
						foreach ($categories as $key_cat => $categoria) {
							$sql =" SELECT * FROM llx_descuentos_categorias as a WHERE a.fk_categorie=".$categoria->id." AND a.fk_soc=".$object->socid." AND NOW() BETWEEN a.date_start AND a.date_end ";

							$query=$db->query($sql);
							$res=$db->num_rows($query);
							if ($res>0) {
								$obj=$db->fetch_object($query);

								$obj->categoria=$categoria;

								array_push($arr,$obj);
							}
						}
					}
				}
				if (count($arr)>0) {
					print '
					<tr class="liste_titre">
						<td colspan=2>Decuentos:
						</td>
					</tr>';
					foreach ($arr as $key => $value) {
						print '<tr><td>'.$value->categoria->label." %".$value->value.'</td><td>';

						$sql='SELECT a.rowid FROM llx_descuentos_presupuestos as a WHERE a.fk_presupuesto='.$object->id.' AND a.fk_descuento='.$value->rowid;
					
						$res=$db->query($sql);
						$num=$db->num_rows($res);
						if ($num>0) {
							print ' <form action="'.$_SERVER["PHP_SELF"].'">';
								print '<input type="hidden" name="id" value="'.$_GET["id"].'" />';
								if (isset($_GET["action"])) {
									print '<input type="hidden" name="action" value="'.$_GET["action"].'" />';
								}
								print '<input type="hidden" name="delete" value="1" />';
								print '<input type="hidden" name="id_desc" value="'.$value->rowid.'" />';
								print '<input  style="font-weight:bold; background-color:#88CB37; color:#FFFFFF;" type="submit" value="Desvincular Descuento" />';
								print ' 
								</form>';

							print '</td></tr>';
						}else{
							print ' <form action="'.$_SERVER["PHP_SELF"].'">';
								print '<input type="hidden" name="id" value="'.$_GET["id"].'" />';
								if (isset($_GET["action"])) {
									print '<input type="hidden" name="action" value="'.$_GET["action"].'" />';
								}
								print '<input type="hidden" name="id_desc" value="'.$value->rowid.'" />';
								print '<input  style="font-weight:bold; background-color:#88CB37; color:#FFFFFF;" type="submit" value="Aplicar Descuento" />';
								print ' 
								</form>';

							print '</td></tr>';
						}
						
					}
					
				}
				
			}
			
		}

		if (in_array('commcard', explode(':', $parameters['context']))) {
			$id = $object->id;
			
			$resql = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."descuentos_categorias WHERE fk_soc=".$id);
			$num = ( $resql ) ? $db->num_rows($resql) : 0;

			$resql = $db->query("SELECT *, d.type as tipo, d.rowid as id FROM ".MAIN_DB_PREFIX."descuentos_categorias d LEFT JOIN ".MAIN_DB_PREFIX."categorie c ON c.rowid = d.fk_categorie LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = d.fk_soc WHERE c.type = 0 AND d.fk_soc=".$id);

			while( $obj = $db->fetch_object($resql) ) {
				$obj->value = ( $obj->tipo == 1 ) ? $obj->value.'%' : '$'.$obj->value; 
				$obj->tipo = ( $obj->tipo == 1 ) ? 'Porcentaje' : 'Cantidad';
				
				$estatus = ( $obj->date_start == '0000-00-00' && $obj->date_end == '0000-00-00' ) ? 'Activo' : (($obj->date_start <= date('Y-m-d') && $obj->date_end == '0000-00-00' ) ? 'Activo' : (($obj->date_start <= date('Y-m-d') && $obj->date_end < date('Y-m-d') ) ? 'Inactivo' : 'Pendiente'));

				$tr .= '<tr>';

				$tr .= '<td>'.$obj->label.'</td>';
				$tr .= '<td>'.$obj->tipo.'</td>';
				$tr .= '<td>'.$obj->value.'</td>';
				$tr .= '<td>';
				$tr .= '<table style="width:300px;">';
				$tr .= '<tr><td>Inicio:</td><td>'.$obj->date_start.'</td></tr>';
				$tr .= '<tr><td>Fin:</td><td>'.$obj->date_end.'</td></tr>';
				$tr .= '</table>';
				$tr .= '</td>';
				$tr .= '<td>'.$estatus.'</td>';
				$tr .= '<tr>';
			}

			require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';


			

			if ($num > 0 ) {
				print '<tr><td>';
				print '
					<form action="'.$_SERVER["PHP_SELF"].'">
						<input type="hidden" name="socid" value="'.$_GET["socid"].'" />
						<input type="hidden" name="act" value="list_desc" />
						Descuentos especiales</td><td colspan="3">';
						print $num.' descuento(s) &nbsp;&nbsp;
					    <input  style="font-weight:bold; background-color:#88CB37; color:#FFFFFF;" type="submit" value="ver" />
					</form>
				';
			}
			

			
		

			print '</td></tr>';

			if ($_GET["act"]=="list_desc") {
				$html.= '<tr><td colspan=2><table class="border" >';
			    $html.= '<tr class="liste_titre">';
	
				$html.= '<td width="15%">Línea de producto</td>';
				$html.= '<td width="15%">Tipo de descuento</td>';
				$html.= '<td width="7%">Cantidad</td>';
				$html.= '<td width="15%">Caducidad</td>';
				$html.= '<td>Estatus</td></tr>';
				$html.= $tr;
				$html.= '</table></td></tr>';
				echo $html;
			}
			
		}
	}



	
	
	/*
	$id = $object->id;

	$fk_soc = 0; 

	$resql_propal = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."propal WHERE rowid=".$id);
	if ( $resql_propal ) {
		$num_propal = $db->num_rows($resql_propal);
		if( $num_propal > 0 ) {
			$obj_propal = $db->fetch_object($resql_propal);
			$fk_soc = $obj_propal->fk_soc;
		}
	}

	$resql = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."descuentos_categorias WHERE fk_soc=".$fk_soc);
	if ( $resql ) {
		$num = $db->num_rows($resql);
		if( $num > 0 ) {
			$objd = $db->fetch_object($resql);
		}
	}

	print '<tr><td>Descuentos por línea</td><td>'.$num.' descuentos</td></tr>';

	function formObjectOptions( $parameters, &$object, &$action, $hookmanager ){
		global $db;
		if (in_array('ordersuppliercard', explode(':', $parameters['context']))) {
			

			$sql = "SELECT p.rowid, no_pedimento FROM ".MAIN_DB_PREFIX."pedimento p ";
			$sql .= "LEFT JOIN ".MAIN_DB_PREFIX."pedimentodet pd ON pd.id_pedimento = p.rowid ";
			$sql .= "WHERE id_pedimento IS NULL";
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;
				if ($num) {
					while ($i < $num) {
						$obj = $db->fetch_object($resql);
							$option_select .= '<option value="'.$obj->rowid.'">'.$obj->no_pedimento.'</option>';
						$i++;
					}
				}
			}

			$existe_pedimento = 0;

			$sql_com = "SELECT fk_product FROM ".MAIN_DB_PREFIX."commande_fournisseurdet WHERE fk_commande =".$object->id;
			$resql_com = $db->query($sql_com);
			if ($resql_com) {
				$num_com = $db->num_rows($resql_com);
				$i = 0;
				if ($num_com) {
					while ($i < $num_com) {
						$obj_com = $db->fetch_object($resql_com);

						$sql_ped = "SELECT * FROM ".MAIN_DB_PREFIX."product_extrafields WHERE fk_object =".$obj_com->fk_product." AND ped = 1";
						$resql_ped = $db->query($sql_ped);
						if ($resql_ped) {
							$num_ped = $db->num_rows($resql_ped);
						}

						if( $num_ped > 0 ) {
							$existe_pedimento = 1;
						}
						$i++;
					}
				}
			}

			$current_pedimento = 0;

			$sql = "SELECT * FROM  ".MAIN_DB_PREFIX."pedimento p LEFT JOIN ".MAIN_DB_PREFIX."pedimentodet pd ON pd.id_pedimento = p.rowid WHERE id_partida=".$object->id;
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				$current_pedimento = $obj->no_pedimento;
			}
			
			if( $object->statut > 2 && $existe_pedimento > 0 ) {

				print '<td><strong>Pedimento</strong></td>';
				if( $current_pedimento == 0 ){
					print '<td><input type="button" class="opener" value="Asignar" style="font-weight:bold; background-color:#88CB37; color:#FFFFFF" /></td>';
				}
				else {
					print '<td><strong>'.$current_pedimento.'</strong>';
					if($object->statut<5) {
						print '<input type="button" class="eliminar_pedimento" value="Eliminar" style="font-weight:bold; background-color:#D95636; color:#FFFFFF" />';
					}
					print '</td>';	
				}

				$current_pedimento = ( $current_pedimento == '') ? 0: $current_pedimento; 

				print '<script type="text/javascript" language="javascript">
					$(document).on("ready", function(){   

						var estatus_ped = '.$object->statut.';
						var pedimento_actual = '.$current_pedimento.';
						var auto_open = false;

						if( estatus_ped == 3 && pedimento_actual == 0 ) {
							auto_open = true;
							$( ".ficheaddleft" ).find("input, select").prop("disabled", true);
							$( ".ficheaddleft" ).after(\'<p style="color:#FF0000; text-align:center;font-weight:bold;">El No. de pedimento es requerido.</p>\');
						}

						$( ".opener" ).click(function() {
							$( "#mensaje" ).html("");
							$( ".dialog" ).dialog( "open" );
						});

						$( ".eliminar_pedimento" ).click(function() {
							var id = $( "#id_partida" ).val();
							$.ajax({
								data:"id_partida="+id ,
								url: "../../instapura/DeletePedimento.php",
								type: "POST",
								success: function(data) {
									if(data == 1 ) {
										location.reload();
									}
									else {
										$( "#contenido" ).html("<div style=\'padding:2px; color:red\'>Ocurrió un error.</div>");
									}
								}
							});
							
						});

						$( "#sel_pedimento" ).change(function() {
							var id = $(this).val();
							$( "#id_pedimento" ).val( id );
						});

						dialog = $( ".dialog" ).dialog({
							autoOpen: auto_open,
							closeOnEscape: true,
							height: 170,
							width: 340,
							buttons: {
								"Agregar": AddPedimento,
								Cerrar: function() {
									dialog.dialog( "close" );
								}
							},
							show: {
								effect: "blind",
								duration: 600
							},
							hide: {
								effect: "blind",
								duration: 400
							}
						});

						function AddPedimento() {
							var id_partida = $( "#id_partida" ).val();
							var id_pedimento = $( "#id_pedimento" ).val();
							$( "#mensaje" ).html("");

							if( id_pedimento > 0 ) {
								$.ajax({
									data:"id_partida="+id_partida+"&id_pedimento="+id_pedimento ,
									url: "../../instapura/AddPedimento.php",
									type: "POST",
									success: function(data) { 
										if(data == 1 ) {
											$( "#mensaje" ).html("<div style=\'padding:2px; color:green\'>Se ha registrado su No. de Pedimento</div>");
											location.reload();
										}
										else {
											$( "#mensaje" ).html("<div style=\'padding:2px; color:red\'>"+data+"</div>");
										}
									}
								});
							}
							else {
								$( "#mensaje" ).html("<div style=\'padding:2px; color:red\'>Debe seleccionar un No. de Pedimento</div>");
							}
						}
						
					});
				</script>';
				print '<div class="dialog" title="Asigna pedimentos">
							<div style="padding:3px;">
								No. Pedimento <select name="sel_pedimento" id="sel_pedimento">
									<option value="">--</option>
									'.$option_select.'
								</select>
								<div id="mensaje" style="font-size:11px; padding:4px;"><i><strong>Nota:</strong> El No. de Pedimento debió registrarse previamente.</i></div>
							</div>
						</div>';

				print '<input type="hidden" name="id_partida" id="id_partida" value="'.$object->id.'" />
						<input type="hidden" name="id_pedimento" id="id_pedimento" value="0" />';
			}
			else {
				//
			}
		}

		if (in_array('productcard', explode(':', $parameters['context']))) {
			$id = $object->id;

			$sql = "SELECT *, pd.rowid as id 
					FROM ".MAIN_DB_PREFIX."pedimentodet pd
					LEFT JOIN ".MAIN_DB_PREFIX."pedimento p ON pd.id_pedimento = p.rowid
					LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur f ON pd.id_partida = f.rowid 
					LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseurdet fd ON fd.fk_commande = f.rowid 
					WHERE fk_product =".$id." LIMIT 1";
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;
				if ($num) {
					while ($i < $num) {
						$obj = $db->fetch_object($resql);
						$pedimentos .= $obj->no_pedimento.' - ';
						$i++;
					}
				}
			}

			$pedimentos = substr($pedimentos, 0, -3);
			print '<tr><td>Pedimentos</td><td>'.$pedimentos.'</td></tr>';
		}

		if (in_array('propalcard', explode(':', $parameters['context']))) {
			
		}

	}

	function addMoreActionsButtons( $parameters, &$object, &$action, $hookmanager ){
		if (in_array('thirdpartycard', explode(':', $parameters['context'])) ) {
			global $db;

			$socid = $object->id;
			$code_client = $object->code_client;

			$sql_cat = "SELECT label, description FROM  ".MAIN_DB_PREFIX."categorie_societe cs 
						LEFT JOIN ".MAIN_DB_PREFIX."categorie cat ON cat.rowid = cs.fk_categorie 
						WHERE cs.fk_soc=".$socid;
			$resql = $db->query($sql_cat);
			if ( $resql ) {
				$num = $db->num_rows($resql);
				$i = 0;
				if ( $num ) {
					while ($i < $num) {
						$obj = $db->fetch_object($resql);
						$options .= '<option value="'.$obj->label.'">'.$obj->label.' - '.$obj->description.'</option>';
						$i++;
					}
				}
			}

			$sql_soc = "SELECT code_client FROM llx_societe WHERE code_client IS NOT NULL";
			$resql = $db->query($sql_soc);
			if ( $resql ) {
				$num_soc = $db->num_rows($resql);
				$i = 0;
				if ( $num_soc ) {
					while ($i < $num_soc) {
						$obj = $db->fetch_object($resql);
						$array_code[] = (int)preg_replace('/[a-zA-Z]/', '', $obj->code_client);								
						$i++;
					}
				}
			}

			$last_num = ($code_client) ? (int)preg_replace('/[a-zA-Z]/', '', $code_client)-1 : max($array_code);
			$up_clave = ($code_client) ? 1: 0;

			print '<td><a class="butAction opener" id="'.$socid.'">Generar Clave</a></td>';

			print '<script type="text/javascript" language="javascript">
						$(document).on("ready", function(){  

							var num =  $( "#num" ).val();
							var up_clave =  $( "#up_clave" ).val();
							num++;

							$( ".opener" ).click(function() {
								var categoria = $( "#sel_clave" ).val();
								if(categoria == 0 ) {
									categoria = "SC";
								}
								$( "#clave" ).html(categoria+num);
								$( ".dialog" ).dialog( "open" );
							});

							$( "#sel_clave" ).change(function() {
								var categoria = $( "#sel_clave" ).val();
								if(categoria == 0 ) {
									categoria = "SC";
								}
								$( "#clave" ).html(categoria+num);
							});

							dialog = $( ".dialog" ).dialog({
								autoOpen: false,
								closeOnEscape: true,
								height: 180,
								width: 340,
								buttons: {
									"Aceptar": agregarClave,
									Cancelar: function() {
										dialog.dialog( "close" );
									}
								},
								show: {
									effect: "blind",
									duration: 600
								},
								hide: {
									effect: "blind",
									duration: 400
								}
							});

							function agregarClave() {

								var socid = $("#socid").val();
								var clave = $("#clave").html();

								$.ajax({
									data:"socid="+socid+"&clave="+clave+"&up_clave="+up_clave ,
									url: "../instapura/AgregarClave.php",
									type: "POST",
									success: function(data) {
										if(data == 1 ) {
											location.reload();
										}
										else {
											$( ".dialog" ).html("<div style=\'padding:2px; color:red\'>Ocurrió un error. Inténtalo de nuevo.</div>");
											setTimeout(function(){
												location.reload();
											}, 2000);
										}
									}
								});

							}
						
						});
					</script>';

			print '<div class="dialog" title="Clave de cliente">
					<div style="padding:3px;">';
			if( $num > 0 ) {
				print '	Seleccione la categoría: <select name="sel_clave" id="sel_clave">
							'.$options.'
						</select>';
			}
			else {
				print '<input type="hidden" name="sel_clave" id="sel_clave" value="0" />';
				print 'Este cliente no esta asignado a ninguna categoría.<br /><i>(SC = Sin Categoría)</i>';
			}
			print '<p>La siguiente clave a asignar es: <strong><span id="clave"></span></strong></p>';
			print '	</div>
				</div>';

			print '<input type="hidden" name="num" id="num" value="'.$last_num.'" />
					<input type="hidden" name="socid" id="socid" value="'.$socid.'" />
					<input type="hidden" name="up_clave" id="up_clave" value="'.$up_clave.'" />';
		}
		if (in_array('propalcard', explode(':', $parameters['context']))) {

			$id = $object->id;			

			print '<script type="text/javascript" language="javascript">
						$(document).on("ready", function(){ 
							$( "#builddoc_generatebutton" ).attr("type","button");

							$( "#builddoc_generatebutton" ).click(function() {
								var loc = window.location;
								var pathName = loc.pathname.substring(0, loc.pathname.lastIndexOf("/") + 1);
								var url = loc.href.substring(0, loc.href.length - ((loc.pathname + loc.search + loc.hash).length - pathName.length));
								url = url + "pdf/index.php?id='.$id.'";
								window.location.href = url;

							});
						});
					</script>';
		}

		if (in_array('ordercard', explode(':', $parameters['context']))) {

			$id = $object->id;

			print '<td><a class="butAction pdf_almacen" id="'.$id.'">PDF Almacén</a></td>';
			
			print '<script type="text/javascript" language="javascript">
						$(document).on("ready", function(){ 
							$( "#builddoc_generatebutton" ).attr("type","button");

							var loc = window.location;
							var pathName = loc.pathname.substring(0, loc.pathname.lastIndexOf("/") + 1);
							var url = loc.href.substring(0, loc.href.length - ((loc.pathname + loc.search + loc.hash).length - pathName.length));

							$( "#builddoc_generatebutton" ).click(function() {
								url = url + "pdf/index.php?id='.$id.'";
								window.location.href = url;
							});

							$( ".pdf_almacen" ).click(function() {
								var id = $(this).attr("id");
								url = url + "pdf/almacen.php?id=" + id;								
								window.open(url, "_blank");
							});
						});
					</script>';
		}

		if (in_array('invoicecard', explode(':', $parameters['context']))) {

			$id = $object->id;
			
			print '<script type="text/javascript" language="javascript">
						$(document).on("ready", function(){ 
							$( "#builddoc_generatebutton" ).attr("type","button");

							
							$("table.border tr td:contains(\'Nota de crédito \')").after("<td colspan=\"5\">Nota de Crédito </td>");
							$("table.border tr td:contains(\'Nota de crédito \')").remove();

							$( "#builddoc_generatebutton" ).click(function() {
								var loc = window.location;
								var pathName = loc.pathname.substring(0, loc.pathname.lastIndexOf("/") + 1);
								var url = loc.href.substring(0, loc.href.length - ((loc.pathname + loc.search + loc.hash).length - pathName.length));
								url = url + "pdf/index.php?id='.$id.'";
								window.location.href = url;

							});
						});
					</script>';
		}

	}

	function formAddObjectLine( $parameters, &$object, &$action, $hookmanager ){
		if (in_array('propalcard', explode(':', $parameters['context']))) {

			$id = $object->id;

			print '<tr class="pair nodrag nodrop nohoverpair"><td class="nobottom" colspan="9" align="right"><a class="butAction opener">Disponiblidad de producto</a></td></tr>';
			print '<tr class="pair nodrag nodrop nohoverpair"><td class="nobottom" colspan="9" align="right"></td></tr>';

			print '<script type="text/javascript" language="javascript">
						$(document).on("ready", function(){  

							$( "#search_idprod" ).focusout(function() {
								setTimeout(function(){
									$("#price_ht").show();
									$("#tva_tx").show();
									var referencia = $("#search_idprod").val();
									
									if( idprod != "") {
										$.ajax({
											data:"idprod="+referencia ,
											url: "../instapura/MostrarPrecio.php",
											type: "POST",
											success: function(data) {
												$("#price_ht").val(data);
											}
										});

										var loc = window.location;
										var pathName = loc.pathname.substring(0, loc.pathname.lastIndexOf("/") + 1);
										var url = loc.href.substring(0, loc.href.length - ((loc.pathname + loc.search + loc.hash).length - pathName.length));
										loc = String(loc);
										loc = loc.split("=");

										$.ajax({
											data:"referencia="+referencia+"&fk_propal="+parseInt(loc[1]) ,
											url: "../instapura/MostrarDescuento.php",
											type: "POST",
											success: function(data) {
												if( data > 0 ) {
													$("#remise_percent").val(data);
												}
											}
										});
									}

								}, 100);
							});

							$( ".opener" ).click(function() {
								var producto = $( "#search_idprod" ).val();
								if( producto == "" ) {
									$( "#div_info" ).html("No ha seleccionado ningún producto");
								}
								else {

									$.ajax({
										data:"producto="+producto ,
										url: "../instapura/MostrarInfo.php",
										type: "POST",
										success: function(data) {
											if(data == 1 ) {
												$( ".dialog" ).html("<div style=\'padding:2px; color:red\'>Ocurrió un error. Inténtalo de nuevo.</div>");
												setTimeout(function(){
													location.reload();
												}, 2000);
											}
											else {
												$( "#div_info" ).html(data);
											}
										}
									});

								}

								$( ".dialog" ).dialog( "open" );
							});

							dialog = $( ".dialog" ).dialog({
								autoOpen: false,
								closeOnEscape: true,
								height: 300,
								width: 200,
								buttons: {
									Aceptar: function() {
										dialog.dialog( "close" );
									}
								},
								show: {
									effect: "blind",
									duration: 600
								},
								hide: {
									effect: "blind",
									duration: 400
								}
							});

							
						});
					</script>';

			print '<div class="dialog" title="Información del Producto">
					<div style="padding:3px;" id="div_info">
					</div>
				</div>';
		}

		if (in_array('ordercard', explode(':', $parameters['context']))) {

			$id = $object->id;

			print '<tr class="pair nodrag nodrop nohoverpair"><td class="nobottom" colspan="9" align="right"><a class="butAction opener">Disponiblidad de producto</a></td></tr>';
			print '<tr class="pair nodrag nodrop nohoverpair"><td class="nobottom" colspan="9" align="right"></td></tr>';

			print '<script type="text/javascript" language="javascript">
						$(document).on("ready", function(){ 

							$( "#search_idprod" ).focusout(function() {
								setTimeout(function(){
									$("#price_ht").show();
									$("#tva_tx").show();
									var idprod = $("#search_idprod").val();
									
									if( idprod != "") {
										$.ajax({
											data:"idprod="+idprod ,
											url: "../instapura/MostrarPrecio.php",
											type: "POST",
											success: function(data) {
												$("#price_ht").val(data);
											}
										});
									}

								}, 100);
							});

							$( ".opener" ).click(function() {
								var producto = $( "#search_idprod" ).val();
								if( producto == "" ) {
									$( "#div_info" ).html("No ha seleccionado ningún producto");
								}
								else {

									$.ajax({
										data:"producto="+producto ,
										url: "../instapura/MostrarInfo.php",
										type: "POST",
										success: function(data) {
											if(data == 1 ) {
												$( ".dialog" ).html("<div style=\'padding:2px; color:red\'>Ocurrió un error. Inténtalo de nuevo.</div>");
												setTimeout(function(){
													location.reload();
												}, 2000);
											}
											else {
												$( "#div_info" ).html(data);
											}
										}
									});

								}

								$( ".dialog" ).dialog( "open" );
							});

							dialog = $( ".dialog" ).dialog({
								autoOpen: false,
								closeOnEscape: true,
								height: 300,
								width: 200,
								buttons: {
									Aceptar: function() {
										dialog.dialog( "close" );
									}
								},
								show: {
									effect: "blind",
									duration: 600
								},
								hide: {
									effect: "blind",
									duration: 400
								}
							});


						});
					</script>'; 

			print '<div class="dialog" title="Información del Producto">
					<div style="padding:3px;" id="div_info">
					</div>
				</div>';
		}

		if (in_array('invoicecard', explode(':', $parameters['context']))) {

			$id = $object->id;

			print '<tr class="pair nodrag nodrop nohoverpair"><td class="nobottom" colspan="9" align="right"><a class="butAction opener">Disponiblidad de producto</a></td></tr>';
			print '<tr class="pair nodrag nodrop nohoverpair"><td class="nobottom" colspan="9" align="right"></td></tr>';

			print '<script type="text/javascript" language="javascript">
						$(document).on("ready", function(){  

							var idfact = '.$id.';
	
							$.ajax({
								data:"idfact="+idfact ,
								url: "../instapura/ProductosFacturados.php",
								type: "POST",
								success: function(data) {
									if( data != \'\') {
										var res = data.split(",");

										$(res).each(function () {
											$("#tablelines tr td> a:contains("+this+")").before("<strong>FACTURADO </strong>").css( "color", "red" );
											$("#tablelines tr td> a:contains("+this+")").parent().css( "color", "red" );
										});
									}
								}
							});

							//$("#tablelines tr td> a:contains(\'130312\')").before("<strong>FACTURADO </strong>").css( "color", "red" );
							//$("#tablelines tr td> a:contains(\'130312\')").parent().css( "color", "red" );
							

							$( "#search_idprod" ).focusout(function() {
								setTimeout(function(){
									$("#price_ht").show();
									$("#tva_tx").show();
									var idprod = $("#search_idprod").val();
									
									if( idprod != "") {
										$.ajax({
											data:"idprod="+idprod ,
											url: "../instapura/MostrarPrecio.php",
											type: "POST",
											success: function(data) {
												$("#price_ht").val(data);
											}
										});
									}

								}, 100);
							});

							$( ".opener" ).click(function() {
								var producto = $( "#search_idprod" ).val();
								if( producto == "" ) {
									$( "#div_info" ).html("No ha seleccionado ningún producto");
								}
								else {

									$.ajax({
										data:"producto="+producto ,
										url: "../instapura/MostrarInfo.php",
										type: "POST",
										success: function(data) {
											if(data == 1 ) {
												$( ".dialog" ).html("<div style=\'padding:2px; color:red\'>Ocurrió un error. Inténtalo de nuevo.</div>");
												setTimeout(function(){
													location.reload();
												}, 2000);
											}
											else {
												$( "#div_info" ).html(data);
											}
										}
									});

								}

								$( ".dialog" ).dialog( "open" );
							});

							dialog = $( ".dialog" ).dialog({
								autoOpen: false,
								closeOnEscape: true,
								height: 300,
								width: 200,
								buttons: {
									Aceptar: function() {
										dialog.dialog( "close" );
									}
								},
								show: {
									effect: "blind",
									duration: 600
								},
								hide: {
									effect: "blind",
									duration: 400
								}
							});

							
						});
					</script>';

			print '<div class="dialog" title="Información del Producto">
					<div style="padding:3px;" id="div_info">
					</div>
				</div>';
		}
	}*/

}

?>
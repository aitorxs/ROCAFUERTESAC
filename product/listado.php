Código PHP:
<?php
$registro=1;

//conexion a la base de datos de manera normal
$hostname = "localhost";
$database = "rocafuertesac";
$username = "root";
$password = "";
$conn = mysql_pconnect($hostname, $username, $password) or die(mysq_error());
mysql_select_db($database, $conn); 


$q='SELECT DISTINCT p.rowid, cfd.fk_product,cfd.qty as stockpedido, cfd.rowid as IDpedido  , cf.fk_statut as estadopedido, cf.date_livraison as tiempoentrega, cf.ref as cfreferencia FROM llx_product as p LEFT JOIN llx_commande_fournisseurdet as cfd  ON (p.rowid=cfd.fk_product )
	LEFT JOIN  llx_commande_fournisseur as cf  ON  (cfd.rowid=cf.rowid) where cfd.fk_product="'.$registro.'" and cf.fk_statut!=5'  ;



					//busco los datos
					$sql= mysql_query($q, $conn)or die(mysql_error());
					print '<td align="right">';
					?>

					<select name="clientes">
					<?php
					while($row = mysql_fetch_array($sql))
					{


					$fecha_inicial = date("Y/m/d", strtotime($row['tiempoentrega']));
	                $fecha_final= date("Y/m/d");
	                 
	                $inicio = strtotime($fecha_inicial);
	                $fin = strtotime($fecha_final);
	                $dif = $fin - $inicio;
	                  $diasFalt = (( ( $dif / 60 ) / 60 ) / 24);
	                $diasFalt =  ceil($diasFalt);



					?>
					<OPTION <?php echo '< title="Días en llegar: '.abs($diasFalt).'">'; echo ''.$row['stockpedido'].''; ?> </OPTION>
					<?php
					}
					?>
					</select>

					<?php
					print '</td>';
					if (! $i) $totalarray['nbfield']++;


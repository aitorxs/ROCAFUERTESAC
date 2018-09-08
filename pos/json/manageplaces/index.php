<?php
/* Copyright (C) 2013 Andreu Bisquerra Gayà	<andreu@artadigital.com>
 * Released under the MIT license
 */
$res=@include("../../../main.inc.php");
if (! $res) $res=@include("../../../../main.inc.php");  
$langs->load("pos@pos");
$langs->load("client@pos");
$zone= GETPOST('zone');
if (! $zone) $zone=1;
?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<title>BarPos</title>
	<link href="css/barpos.css" rel="stylesheet">
	<script src="js/jquery-1.9.1.min.js"></script>
	<script src="js/jquery-ui-1.10.2.custom.min.js"></script>
	<style type="text/css">
	div.tablediv{
	background-image:url(img/table.gif);
	-moz-background-size:100% 100%;
	-webkit-background-size:100% 100%;
	background-size:100% 100%;
	height:10%;
	width:10%;
	text-align: center;
	font-size:300%;
	color:white;
	}
	html, body
	{
    height: 100%;
	}
	</style>

	<script>
	var DragDrop='<?php echo $langs->trans("DragDrop"); ?>';
	
	function updateplace(idplace, left, top) {
	$.ajax({
		type: "POST",
		url: "updateplaces.php",
		data: { action: "update", left: left, top: top, place: idplace }
		}).done(function( msg ) {
		window.location.reload()
		});
	}
	
	function updatename(before) {
	var after=$("#"+before).text();
	$.ajax({
		type: "POST",
		url: "updateplaces.php",
		data: { action: "updatename", place: before, after: after }
		}).done(function( msg ) {
		window.location.reload()
		});
	}
	
	function updatezonename() {
	var after=$("#zonename").text();
	$.ajax({
		type: "POST",
		url: "updateplaces.php",
		data: { action: "updatezonename", zone: <?php echo $zone; ?>, after: after }
		}).done(function( msg ) {
		});
	}
	
				//Get places
			$.getJSON('../loadplaces.php?zone='+ <?php echo $zone; ?>, function(data) {
				$.each(data, function(key, val) {
				$('body').append('<div class="tablediv" contenteditable onblur="updatename(this.id);" style="position: absolute; left: '+val.left_pos+'%; top: '+val.top_pos+'%;" id="'+val.place+'">'+val.place+'</div>');
				$( "#"+val.place ).draggable(
				{
					start: function() {
					$("#add").attr("src","./img/delete.jpg");
					$("#addcaption").html(DragDrop);
					
					},
					stop: function() {
					var left=$(this).offset().left*100/$(window).width();
					var top=$(this).offset().top*100/$(window).height();
					updateplace($(this).attr('id'), left, top);
					}
					}
					);
					
					//simultaneous draggable and contenteditable
					$('#'+val.place).draggable().bind('click', function(){
					$(this).focus();
					})
					
					});
					});
	</script>
	</head>
	<body style="overflow: hidden">
	<div style="position: absolute; left: 0.1%; top: 0.8%; width:8%; height:11%;" onclick='
	$.ajax({
		type: "POST",
		url: "updateplaces.php",
		data: { action: "add", zone: <?php echo $zone; ?> }
		}).done(function( msg ) {
		window.location.reload()
		});'>
	<div class='wrapper3' style="width:100%;height:100%;" id="setup">
		<img src='img/plus.jpg' width="100%" height="100%" border="1" id='deleteimg'/>
		<div class='description2'>
		<div class='description_content' id="addcaption"><?php echo $langs->trans("AddTable"); ?></div>
		</div>
	</div>


	</div>
	
	<div style="position: absolute; left: 5%; bottom: 6%; width:50%; height:3%;">
	<center>
	<div style="float:left; width=40px;"><img src="./img/arrow-prev.png" width="40px" onclick="location.href='index.php?zone=<?php if ($zone>1) { $zone--; echo $zone; $zone++;} else echo "1"; ?>';"></div><div id="zonename" onblur='updatezonename();' style="float:left" contenteditable><h1>
	<?php
	$custom_name="DOLIPOSBAR_CUSTOM_ZONE_NAME".$zone;
	if ($conf->global->$custom_name!="") echo $conf->global->$custom_name;
	else echo $langs->trans("Zone")." ".$zone;
	?>
	</h1></div><div style="float:left; width=40px;"><img src="./img/arrow-next.png" width="40px" onclick="location.href='index.php?zone=<?php $zone++; echo $zone; ?>';"></div>
	</center>
	</div>
	</body>
</html>
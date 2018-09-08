<?php
$res=@include("../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");                // For "custom" directory

dol_include_once('/pos/class/pos.class.php');
//if(!class_exists('Mobile_Detect'))
//	dol_include_once('/pos/class/mobile_detect.php');
require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");
global $db, $langs,$conf;
$langs->load("pos@pos");
$langs->load("rewards@rewards");
$langs->load("bills");
$langs->load("companies");
$langs->load("products");
$langs->load('users');
$langs->load("main");
$langs->load("cashdesk");
$langs->load("stocks");

if(empty($_SESSION['uname']) || empty($_SESSION['TERMINAL_ID']))
{
	accessforbidden();
}
$cash = new Cash($db);
$cash->fetch($_SESSION['TERMINAL_ID']);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html style="height: 100%; overflow: hidden;" xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"> 
	<title><?php echo $langs->trans("DolibarTPV"); ?></title> 
	<link rel="stylesheet" type="text/css" href="css/layout-default-latest.css">
	<link rel="stylesheet" type="text/css" href="css/jquery.css">
	<link rel="stylesheet" type="text/css" href="css/keyboard.css">
	<link rel="stylesheet" type="text/css" href="css/jquery.ui.chatbox.css">
    <link href="css/jquery-ui.css" type="text/css" rel="Stylesheet" class="ui-theme">
	<script type="text/javascript" src="js/jquery-latest.js"></script> 
	<script type="text/javascript" src="js/jquery-ui-latest.js"></script> 
	<script type="text/javascript" src="js/jquery.js"></script>
	<script type="text/javascript" src="js/jquery.class.js"></script>
	<!--  <script type="text/javascript" src="js/jquery.tablesorter.min.js"></script>-->
	<script type="text/javascript" src="js/tpv.js"></script>
	<script type="text/javascript" src="js/layout.js"></script>
 	<script type="text/javascript" src="js/jquery.keyboard.min.js"></script>
 	<script type="text/javascript" src="js/jquery.printPage.js"></script>
 	<script type="text/javascript" src="js/jquery.ui.chatbox.js"></script>
  	<?php if ($conf->global->POS_PRINT_MODE == 1) { ?>
	<div style="visibility:hidden;position:absolute; top:1%; left:1%;">
	<applet id="qz" name="QZ Print Plugin" code="qz.PrintApplet.class" archive="./qz-print.jar" width="50px" height="50px">
	<param name="jnlp_href" value="qz-print_jnlp.jnlp">
	<param name="cache_option" value="plugin">
	<param name="disable_logging" value="false">
	<param name="initial_focus" value="false">
	</applet></div>
	<script src="js/printer.js" type="text/javascript"></script>
	<?php } ?>
	<script type="text/javascript">
	<?php 	
	echo "var printer_name='".$cash->printer_name."';";
	echo "var drawer='".$conf->global->POS_OPEN_DRAWER."';";
	?>
	
	

	$(document).ready(function () {

	<?php if ($conf->global->POS_PRINT_MODE==1) { ?>
	setInterval(function(){check()},5000);
	<?php } ?>
});
	</script>
 </head>

<body style="position: relative; overflow: hidden; margin: 0px; padding: 0px; border: medium none;" class="ui-layout-container"> 

<!--<div style="position: absolute; margin: 0px; top: 0px; bottom: auto; left: 0px; right: 0px; width: auto; z-index: 1; height: 19px; visibility: visible; display: none;" class="ui-layout-north ui-widget-content add-padding ui-layout-pane ui-layout-pane-north">North</div> 
<div style="position: absolute; margin: 0px; top: auto; bottom: 0px; left: 0px; right: 0px; width: auto; z-index: 1; height: 19px; visibility: visible; display: none;" class="ui-layout-south ui-widget-content add-padding ui-layout-pane ui-layout-pane-south">South</div>-->
<?php if($conf->global->POS_INV){?>
<div class="total_but_inv" style="margin:10px;-webkit-transform: rotate(-180deg);-moz-transform: rotate(-180deg);padding:0 5px 0 0; color:#fff;">
<?php echo $langs->trans("TotalTicket"); ?>&nbsp;<span id="totalTicketinv" style="clear:both; font-weight:bold; font-size:80px; line-height:40px; margin:1px 0 0;">0</span>&nbsp;<?php echo $conf->currency ?>
</div>
<?php }?>

<!-- CENTER COL -->
<div id="tabs-center" class="ui-layout-center no-scrollbar add-padding  ui-layout-pane ui-layout-pane-center ui-layout-container ui-tabs ui-widget ui-widget-content ui-corner-all ui-layout-pane-hover ui-layout-pane-center-hover ui-layout-pane-open-hover ui-layout-pane-center-open-hover">

<!-- CENTER COL HEADER -->
<div class="header darkblue gradient"  >
	
    <a href="tpv.php" title="" target="_self"><img class="dolipos_logo" style="border-right: 0px" src="img/dolipos_logo.png" alt="" title="" width="176" height="55" /></a>
    
    <div>
      	<img class="photo" id="id_image" alt="" src="" height="53px">
    </div>
    
    <div class="user_top">
    	<span id="id_user_name" class="user">
			<?php echo $langs->trans("User"); ?>
        </span>
         <span id="id_user_terminal" class="user terminal">	
			<?php echo $langs->trans("Terminal 1"); ?>
        </span>
        <span id="infoCartTicket">
            <span id="infoCustomer"><?php echo $langs->trans("ByDefault"); ?></span>
        </span>
     </div>
         	
   
     	<div class="fecha">
            <span style="font-size: 12px; color: #ffffff !important;">
                 <script type="text/javascript">
                    var dia=new Array(7);
                    dia[0]='<?php echo $langs->trans("Sunday");?>';
                    dia[1]='<?php echo $langs->trans("Monday");?>';
                    dia[2]='<?php echo $langs->trans("Tuesday");?>';
                    dia[3]='<?php echo $langs->trans("Wednesday");?>';
                    dia[4]='<?php echo $langs->trans("Thursday");?>';
                    dia[5]='<?php echo $langs->trans("Friday");?>';
                    dia[6]='<?php echo $langs->trans("Saturday");?>';
                    var date = new Date();
                    var day = date.getDate();
                    var month = date.getMonth() + 1;
                    var yy = date.getYear();
                    var year = (yy < 1000) ? yy + 1900 : yy;
                    document.write(dia[date.getDay()] + " " + day + "-" + month + "-" + year);
                </script>    	
                    
                    <script type="text/javascript">
                        function startTime(){
                        today=new Date();
                        h=today.getHours();
                        m=today.getMinutes();
                        s=today.getSeconds();
                        m=checkTime(m);
                        document.getElementById('reloj').innerHTML=h+":"+m;
                        t=setTimeout('startTime()',500);}
                        function checkTime(i)
                        {if (i < 10) {i="0" + i;}return i;}
                        window.onload=function(){startTime();}
                    </script>
              </span>
              <br/>
              <span id="reloj"></span>
                    
            </div>
    
    <a class="logout but"  href="#" id="btnLogout" title="<?php echo $langs->trans("Logout"); ?>" target="_self"></a>
    <a class="top_help but" href="https://liveagent.2byte.es/index.php?type=page&urlcode=715850&title=M%C3%B3dulo-DoliPOS&r=1" title="<?php echo $langs->trans("OnlineHelp"); ?>" target="_new"></a>
    <!--<a class="top_tactil on" style="background-color: #555;  border: 1px #ffffff solid;  border-radius: 0px 0px 0px 0px" id="id_btn_tpvtactil" href="#" title="<?php echo $langs->trans("TouchTPV"); ?>"></a>
    <a class="top_infoproduct" id="id_btn_infoproduct" href="#" title="<?php echo $langs->trans("InfoProduct"); ?>"></a> -->
    <a class="top_employee but"  id="id_btn_employee" href="#" title="<?php echo $langs->trans("ChangeEmployee"); ?>"></a>
    <!-- <a class="top_barcode off" id="id_btn_barcode" href="#" title="<?php echo $langs->trans("BarCode"); ?>"></a> -->
    <a class="top_closecash but"  id="id_btn_closecash" href="#" title="<?php echo $langs->trans("CashAccount"); ?>"></a>
    <!--  <a class="top_closecash but"  id="id_btn_closeproduct" href="#" title="<?php echo $langs->trans("CloseProducts"); ?>"></a>-->
   	<a class="top_closecash but"  id="id_btn_fullscreen" href="#" title="<?php echo $langs->trans("FullScreen"); ?>"></a>

    <?php if($conf->global->POS_CHAT){?><a class="top_closecash but"  id="id_btn_chat" href="#" title="<?php echo $langs->trans("Chat"); ?>"></a>
    <?php }?>
    <?php if($conf->global->POS_OPEN_DRAWER && $conf->global->POS_PRINT_MODE){?><a class="top_opendrawer but"  id="id_btn_opendrawer" href="#" title="<?php echo $langs->trans("PosOpenDrawer"); ?>"></a>
    <?php }?>
    
</div>
<!-- END CENTER COL HEADER -->

<!-- CENTER TABS -->
	<ul style="position: absolute; width: auto; z-index: 1; height: 40px !important; visibility: visible; display: block;" class="ticket ui-layout-north no-scrollbar allow-overflow ui-layout-pane ui-layout-pane-north ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header no-border no-bg no-padding">
		
        <li class="tab_tick ui-state-default ui-corner-top ui-tabs-selected ui-state-active"><a href="#tab-center-1"><span class="tab_icon"></span><?php echo $langs->trans("Ticket"); ?></a></li>
		<!--  <li class="tab_data ui-state-default ui-corner-top"><a href="#tab-center-2"><span class="tab_icon"></span><?php echo $langs->trans("Data"); ?></a></li>-->
        <!--  <li class="tab_cust ui-state-default ui-corner-top"><a href="#tab-center-3"><span class="tab_icon"></span><?php echo $langs->trans("Customers"); ?></a></li>-->
		<?php if($conf->global->POS_TICKET){?><li class="tab_hist ui-state-default ui-corner-top"><a id="tabHistory" href="#history"><span class="tab_icon"></span><?php echo $langs->trans("History"); ?></a></li><?php }?>
		<?php if($conf->global->POS_FACTURE){?><li class="tab_hist ui-state-default ui-corner-top"><a id="tabHistoryFac" href="#historyFac"><span class="tab_icon"></span><?php echo $langs->trans("HistoryFacture"); ?></a></li><?php }?>
		<li class="tab_stoc ui-state-default ui-corner-top"><a id="tabStock" href="#almacen"><span class="tab_icon"></span><?php echo $langs->trans("Products"); ?></a></li>
		 
	<!--  	<?php if($conf->global->POS_PLACES){?>
		<li class="tab_stoc ui-state-default ui-corner-top"><a id="tabPlaces" href="#places"><span class="tab_icon"></span><?php echo $langs->trans("Places"); ?></a></li>
        <?php }?>-->
       <!-- <li class="tab_dashboard ui-state-default ui-corner-top ui-tabs-selected ui-state-active"><a href="#tab-dashboard"><span class="tab_icon"></span><?php echo $langs->trans("Dashboard"); ?></a></li> --> 
		
         
         <!--   <span class="topinfo">
            	  <span>2</span>  
            	<img src="./img/info.png">
            </span>-->  
       
    </ul>
<!-- END CENTER TABS --> 

    
<p id="top_sep"> <br clear="all" />  </p>
	
    <div class="ticket_content ui-layout-center ui-widget-content add-scrollbar ui-layout-pane ui-layout-pane-center ui-layout-pane-hover ui-layout-pane-center-hover ui-layout-pane-open-hover ui-layout-pane-center-open-hover" style="">
 
        <div id="tab-center-1" class="outline ui-tabs-panel ui-widget-content ui-corner-bottom" style="margin-top:0 !important;">
		 	
      		<div id="ticketLeft">
      		
      		
      		<div style="">
	            <div class="clearfix tabContainer2" id="info_product">
	            	<div id="product-right-column" style="padding: 10px; color: #fff">
	                    <div>
	                    <img width="90px;" height="90px;" style=" float: left; padding-right: 8px; padding-bottom: 8px;" width="100%" id="bigpic" alt="" title="<?php echo $langs->trans("Product"); ?>" src="" style="display: inline;">
	                    </div>
	                    <div class="label">
	                        <span id="our_label_display" style="font-size: 20px !important;"> </span>
	                    </div>
	                     <div class="price">
	                        <span class="our_price_display" >
	                            <span id="our_price_display" style="font-size: 28px !important;"> </span><?php echo $langs->trans($conf->currency);?> 
	                        </span>
	                    </div>
	                    <div class="price_min">
	                        <span id="our_price_min" class="our_price_min_display" >
	                            <span id="our_price_min_display" style="font-size: 14px !important;"> </span><?php echo $langs->trans($conf->currency);?> 
	                        </span>
	                    </div>
	                    <a class="btn3d" id="btnHideInfo" style="width:80px; float:right;"><?php echo $langs->trans("More");?></a>
	                    <div id="short_description_block">
	                        <p><br><span class="rte align_justify" id="short_description_content" style="font-size: 11px;"><p></p></span></p>
	                    </div>
	                    
	                    
					</div>
	            	<div style="clear:both"></div>
	            </div>
            </div>	
            
            <!-- info del producto-->
            <div id="ticketOptions" class="leftBlock clearfix tabContainer2" style="display:none">
            	<div class="colActions"></div>
            </div> 
      		
      		<div id="products" class="leftBlock"  style="overflow: auto;" ></div> 
            
            <!-- INFO de datos -->
            
        	    
             
             <!--FIN INFO de datos -->
           <!-- FIN berni -->
          
          
            <div id="idTicketLine" class="leftBlock bloqueOpciones" style="display:none" title="">
			<div class="options">
				<ul>
					<li><label><?php echo $langs->trans("Units"); ?>:</label>
					<input onclick="this.select()" type="text" size="6" name="line_quantity" id="line_quantity" value="0"  class="numKeyboard"></li>
					<br clear="all" />
					<li><label>% <?php echo $langs->trans("Discount"); ?>:</label>
					<input onclick="this.select()" type="text" size="5" maxlength="3" name="line_discount" id="line_discount"  value="0" class="numKeyboard"></li>
					<br clear="all" />
					<li><label><?php echo $langs->trans("Price"); ?>:</label>
					<input onclick="this.select()" type="text" size="6" name="line_price" id="line_price" value="0"  class="numKeyboard"></li>
					<br clear="all" />
					<li><label><?php echo $langs->trans("Note"); ?>:</label>
					<input onclick="this.select()" type="text" size="6" name="line_note" id="line_note" value=""  class="quertyKeyboard"></li>
					<br clear="all" />
				</ul>	
					<input type="button" id="id_btn_editTicketline" value="<?php echo $langs->trans("Save"); ?>" class="btn3dbig">
						
			</div>	
			</div>
			
			
			<div id="payType" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("PaymentMode");?>">
				<div class="options">
					<div>
					<?php 	
						$payments = POS::select_Type_Payments();
						if(count($payments))
						{
							$i=0;
							while($i < count($payments))
							//foreach($payments as $payment) 
							{
								//echo "<div class='payment_types'><a class='btn3dbig' id='paytype".$payment['id']."' style='height:40px;'>".$payment['label']."</a></div>";
								echo '<div class="payment_types"><label>'.$payments[$i]['label'].'</label><input style="height:35px; width:60%; border-radius:6px;font-size:25px;" onclick="this.select()" type="text" value="" name="pay_client_'.$i.'" id="pay_client_'.$i.'" class="numKeyboard">';
								echo '<input type="button" id="pay_all_'.$i.'" value="'.$langs->trans("Remainder").'" class="chk3d	"></div>';
								$i++;
							}
						}
					?>	
					</div>
				</div>
				<?php if($conf->global->REWARDS_POS){?>
				<div id="payment_points" class="payment_options">
					<div id="points_div">
					<label><?php echo $langs->trans("Points");?></label>
					<div class="points_total"></div><div id=eur ><span class="points_money"></span><?php echo $conf->currency ?></div>
					<label><?php echo $langs->trans("UsePoints");?></label>
					<div class="points_client">
						<input onclick="this.select()" type="text" value="" name="points_client_id" id="points_client_id" class="numKeyboard">
					</div>
					</div>
					<!--  <label><?php echo $langs->trans("Total");?></label>
					<div class="payment_total"></div>-->
					<label><?php echo $langs->trans("CustomerRet");?></label>	
					<div class="payment_return"></div>
				
				</div>
				<?php }?>
			
		 		<div id="payment_coupon"><input type="button" id="id_btn_coupon" value="<?php echo $langs->trans("UseCoupon"); ?>" class="btn3dbig">
		 	
		 		</div>
				<div id="payment_total_points" class="payment_options">
				
				
				<!--  <label><?php echo $langs->trans("Total");?></label>
				<div  class="payment_total"></div>-->
				
				<label><?php echo $langs->trans("CustomerRet");?></label>	
				<div class="payment_return"></div>
				
				</div>
				<input type="button" id="id_btn_add_ticket" value="<?php echo $langs->trans("Save"); ?>" class="btn3dbig">	
			</div>
		
		<div id="payTypeRet" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("PaymentMode");?>">
			<div class="options">
			<div>
			<?php 	
				$payments = POS::select_Type_Payments();
				if(count($payments))
				{
					$i=0;
					while($i < count($payments))
					//foreach($payments as $payment) 
					{
						//echo "<div class='payment_types'><a class='btn3dbig' id='paytype".$payment['id']."' style='height:40px;'>".$payment['label']."</a></div>";
						echo '<div class="payment_types"><label>'.$payments[$i]['label'].'</label><input style="height:35px; width:60%; border-radius:6px;font-size:25px;" onclick="this.select()" type="text" value="" name="pay_client_ret_'.$i.'" id="pay_client_ret_'.$i.'" class="numKeyboard">';
						echo '<input type="button" id="pay_all_ret_'.$i.'" value="'.$langs->trans("Remainder").'" class="chk3d	"></div>';
						$i++;
					}
				}
			?>	
			</div>
			</div>
						
		 	<div id="payment_total_ret" class="payment_options">
				
				
				<label><?php echo $langs->trans("YetUnreturned");?></label>	
				<div class="payment_return_ret"></div>
				
			</div>
			<input type="button" id="id_btn_add_ticket_ret" value="<?php echo $langs->trans("DoPaymentBack"); ?>" class="btn3dbig">
			<div id="convert_coupon"><input type="button" id="id_btn_add_ticket_desc" value="<?php echo $langs->trans("ConvertToReduc"); ?>" class="btn3dbig"></div>
		</div>
			
		<div id="idFactureMode" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("Invoice"); ?>">
			<div class="options">
				<div>
					<?php if($conf->global->POS_TICKET) {?>
					<input type="button" id="id_btn_ticketPay" value="<?php echo $langs->trans("Ticket"); ?>" class="btn3dbig">
					<?php } if($conf->global->POS_FACTURE) {?>
					<input type="button" id="id_btn_facsimPay" value="<?php echo $langs->trans("Facturesim"); ?>" class="btn3dbig">
					<input type="button" id="id_btn_facturePay" value="<?php echo $langs->trans("Invoice"); ?>" class="btn3dbig">
					<?php }?>
				</div>		
			</div>	
		</div>

        <?php if($conf->numberseries->enabled && $conf->global->NUMBERSERIES_POS) {?>
        <div id="idSerieMode" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("Invoice"); ?>">
            <div class="options">
                <div>
                    <?php $series = POS::getSeries();
                    if(count($series))
                    {
                        $i=0;
                        echo '<label>'.$langs->trans('Numberserie').'</label>';
                        while($i < sizeof($series))
                        {
                            //echo '<input type="button" id="id_btn_Serie'.$series[$i]['rowid'].'" value="'.$series[$i]['ref'].'" class="btn3dbig">';
                            echo "<div class='series_types'><a class='btn3dbig' id='serietype".$series[$i]['rowid']."' style='height:40px;'>".$series[$i]['ref']."</a></div>";
                            $i++;
                        }
                    }?>
                </div>
            </div>
        </div>
        <?php } ?>
		
		<div id="idReturnMode" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("Invoice"); ?>">
			<div class="options">
				<div>
					<?php if($conf->global->POS_TICKET) {?>
					<input type="button" id="id_btn_ticketRet" value="<?php echo $langs->trans("Ticket"); ?>" class="btn3dbig">
					<?php } if($conf->global->POS_FACTURE) {?>
					<input type="button" id="id_btn_facsimRet" value="<?php echo $langs->trans("Facturesim"); ?>" class="btn3dbig">
					<input type="button" id="id_btn_factureRet" value="<?php echo $langs->trans("Invoice"); ?>" class="btn3dbig">
					<?php }?>
				</div>		
			</div>	
		</div>
			
			<div id="idTicketMode" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("Ticket"); ?>">
			<div class="options">
				<div>
					<?php if($conf->global->POS_PRINT) {?>
					<input type="checkbox" id="id_cb_ticketPrint" name="id_cb_ticketPrint" class="chk3d">
					<label style="float:left"><?php echo $langs->trans("GiftTicket"); ?></label>
					<input type="button" id="id_btn_ticketPrint" value="<?php echo $langs->trans("PrintTicket"); ?>" class="btn3dbig">
					<?php } if($conf->global->POS_MAIL) {?>
					<input type="button" id="id_btn_ticketMail" value="<?php echo $langs->trans("SendTicket"); ?>" class="btn3dbig">
					<?php }?>
				</div>		
			</div>	
		</div>
		<div id="idCashMode" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("CloseCash"); ?>">
			<div class="options">
				<div>
					<?php if($conf->global->POS_PRINT) {?>
					<input type="button" id="id_btn_cashPrint" value="<?php echo $langs->trans("PrintCloseCash"); ?>" class="btn3dbig">
					<?php } if($conf->global->POS_MAIL) {?>
					<input type="button" id="id_btn_cashMail" value="<?php echo $langs->trans("SendCloseCash"); ?>" class="btn3dbig">
					<?php }?>
				</div>		
			</div>	
		</div>					
		
			
			<div id="idDiscount" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("ApplyDiscount"); ?>">
			<div class="options">
				
					<!-- <div class='btnselect type_discount btnon'><a id='btnTypeDiscount0'><?php echo $langs->trans("Percent");?></a></div>
					<div class='btnselect type_discount'><a id='btnTypeDiscount1'><?php echo $langs->trans("Quantity");?></a></div>-->
					<ul>
					<li><div id="typeDiscount0">
						<label><?php echo $langs->trans("Percent"); ?></label><input onclick="this.select()" type="text" size="5" maxlength="2" name="ticket_discount_perc" id="ticket_discount_perc"  value="0" class="numKeyboard" />
					</div>
					<!-- <div id="typeDiscount1" style="display:none">
						<label><?php echo $langs->trans("Quantity"); ?>:</label><input type="text" size="6" name="ticket_discount_qty" id="ticket_discount_qty" value="0"  class="numKeyboard" />
					 </div>-->
				
				 <input type="button" id="id_btn_add_discount" value="<?php echo $langs->trans("Save"); ?>" class="btn3dbig">
				 </li>
				 </ul>	
			</div>	
		</div>
       		
			</div>
            <div id="ticketRight">
             <div id="productSearch" class="topSearch">
    
          <!--   <div class="but barcode">
                <img height="48" width="60" id="id_btn_codebar" title="<?php echo $langs->trans("AddBarcode"); ?>" name="btnShowManualProducts" src="./img/barcode.png"></img>
                <span class="text"><?php echo $langs->trans("BarCode"); ?></span>
              </div>-->  	
    
               <div class="inputs" style="width:100% !important;">
               
               		<div border="0" style="width:100%;  height:65px;"  >
	                    
	                    <div class="tabContainer0" style="width:30%;margin-right:5px;">
		                    <!-- <label>
		                    	<?php echo $langs->trans("Search"); ?> 
		                    </label>-->
		                    <img id="img_product_search" class="search_but" class="but" src="./img/search_prod.png" height="40px" style="float:left;margin-left:8px;cursor:pointer">
		                    <input onclick="this.select()" type="text" class="quertyKeyboard" size=10 name="id_product_search" id="id_product_search">  
	                    </div>
	                    <div class="tabContainer0" style="width:8%;margin-right:5px;" >
	                    	<h3 class="but" name="btnTotalNote" id="btnTotalNote" >
		                    	<?php echo $langs->trans("Notes"); ?>
		                   	</h3>
		                    <span id="totalNote_" style="display:block; margin:10px auto;text-align:center;font-size: 30px; color:#FFFFFF;font-weight:bold">
		                    	 0
		                    </span>
		                    
		                     
	                    </div>
	                     <?php if($conf->global->POS_PLACES){?>
	                    <div class="tabContainer1" style="width:35%;margin-right:5px;" >
	                       <?php }   else{?>
	                    <div class="tabContainer1" style="width:55%;margin-right:5px;" >  
	                     <?php }   ?>
	                    	<h3 id="infoCustomer_" ><?php echo $langs->trans("Customer");?></h3>
	                    	<div  name="btnChangeCustomer" id="btnChangeCustomer" ><a class="btn3d"><?php echo $langs->trans("ChangeCustomer");?></a></div>
	                    	<div   name="btnNewCustomer" id="btnNewCustomer"><a class="btn3d" ><?php echo $langs->trans("NewCustomer");?></a></div>
	                    </div>
	                    <?php if($conf->global->POS_PLACES){?>
	                    <div class="tabContainer0" style="width:24%; "> 
	                            <h3 class="text"><span id="totalPlace"> <?php echo $langs->trans("Place");?></span></h3>
	                            <div name="btnChangePlace" id="btnChangePlace" class="text" ><a  class="btn3d"><?php echo $langs->trans("ChangePlace");?></a></div>       
	                    </div>
	                    <?php }   ?>
	                    
                    </div>
               		<div id="divSelectProducts" style="display:none">
               			<select name="id_selectProduct" id="id_selectProduct"></select>
               		</div>
                    
	
	                	
	               </div>   
		    <br clear="all" />   
			</div>
            <div id="totalCart" class="grey">
            	<div id="totalCartDesc">
                <div class="but" name="btnOkTicket" id="btnOkTicket"><img height=" " width=" " title="<?php echo $langs->trans("SaveThisTicket"); ?>" src="./img/acceptTicket.png">
                	<span class="text" ><?php echo $langs->trans("SaveTicket"); ?></span></div>
                <div class="but" id="btnSaveTicket" name="btnSaveTicket"><img height=" " width=" " title="<?php echo $langs->trans("CreateDraftTicket"); ?>" src="./img/saveTicket.png">
                    <span class="text" ><?php echo $langs->trans("TicketDraft"); ?></span></div>
                <div class="but" name="btnAddDiscount" id="btnAddDiscount"><img height=" " width=" " title="<?php echo $langs->trans("ApplyDiscountTicket"); ?>" src="./img/discount.png">
                    <span class="text" ><?php echo $langs->trans("ApplyDiscount"); ?></span></div>
                <div class="but" name="btnNewTicket" id="btnNewTicket" ><img height=" " width=" " title="<?php echo $langs->trans("CreateNewTicket"); ?>" src="./img/new_ticket.png">
                    <span class="text" ><?php echo $langs->trans("CreateNewTicket"); ?></span></div>
                 <div class="but" name="btnReturnTicket" id="btnReturnTicket" style="display:none"><img height=" " width=" " title="<?php echo $langs->trans("ReturnTicket"); ?>" src="./img/deleteTicket.png">
                    <span class="text" ><?php echo $langs->trans("ReturnTicket"); ?></span></div>
                 <div class="but" name="btnTicketNote" id="btnTicketNote" style="display:none"><img height=" " width=" " title="<?php echo $langs->trans("Note"); ?>" src="./img/noteTicket.png">
                    <span class="text" ><?php echo $langs->trans("Note"); ?></span></div>
                 <div class="text" name="btnTicketRef" id="btnTicketRef" style="display:none; float:left;font-size:25px;"></div>
               </div>
             
                  <div id="totalCartTicket">
                	<div class="discount_but">
                    	<span class="total_text"><?php echo $langs->trans("Discount"); ?></span>
                        <span class="number"><span id="totalDiscount">0</span>&nbsp;<?php echo $conf->currency ?></span>
                        
                    </div>
                    <div class="total_but">
                       	<span class="total_text"><?php echo $langs->trans("TotalTicket"); ?></span>
                       <span class="number"><span id="totalTicket" >0</span>&nbsp;<?php echo $conf->currency ?><span id="alertfaclim" >
                       <img title="<?php echo $langs->trans('OverFactureLimit')?> " src="img/alert.png" style="float: left; margin: 1% 0px 0px 5%;"></span></span>
                                                   
                    </div>
		        </div>
            <div style="clear:both"></div>
            </div>   
            
            
			<div id="ticketCart">
					<table cellspacing="0" cellpadding="0" id="tablaTicket" class="tableList">
		            <thead>
		              <tr>
		                <th class="idCol" style="width:122px; text-align:left; padding:0 0 0 5px;"><?php echo $langs->trans("IdProduct"); ?></th>
		                <th style="text-align:left; padding:0 0 0 5px;"><?php echo $langs->trans("Product"); ?></th>
		                <th style="width:70px">% <?php echo $langs->trans("Dct"); ?></th>
		                <th style="width:100px"><?php echo $langs->trans("Price"); ?></th>
		                <th style="width:70px"><?php echo $langs->trans("Units"); ?></th>
		                <th style="width:100px;"><?php echo $langs->trans("Total"); ?></th>
		                <th style="display:none"><?php echo $langs->trans("Actions"); ?></th>
		              </tr>
		            </thead>
					<tbody id="listado_productos_ticket" style="overflow:scroll">
					 </tbody>
		          </table>

                  <div class="go_up"><a class="grey" id="top" title="" target="_self"><?php echo $langs->trans("Up"); ?></a></div>
				
            </div>
            </div>
			
		</div>
        
        
		<div id="tab-center-2" class="no-top no-border no-padding no-scrollbar ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" style="position: absolute; top: 0px !important; bottom: 0pt; left: 0pt; right: 0pt; margin-top:0px !important;">
			<div class="ui-layout-center no-scrollbar">
				<div class="topSearch">
					<label><?php echo $langs->trans("Information"); ?></label>
				</div>
				
            	<div class="bottom_search">
					<div class="clearfix" id="info_product">
						
                        <div id="product-right-column">
                            <img height="200" width="200" id="bigpic" alt="" title="<?php echo $langs->trans("Product"); ?>" src="" style="display: inline;">
							<h1><?php echo $langs->trans("SelectProduct"); ?></h1>
                            <div id="short_description_block">
								<div class="rte align_justify" id="short_description_content"><p><?php echo $langs->trans("NoDescription"); ?></p></div>
							</div>
                            
							<p class="price" >
								<span class="our_price_display" >
									<span id="our_price_display" ><?php echo $langs->trans("00,00"); ?></span>€
                        		</span>
							</p>
                        	<p id="quantity_wanted_p">
                        		<label><?php echo $langs->trans("Quantity"); ?></label>
								<input onclick="this.select()" type="text" maxlength="3" size="2" value="1" class="numKeyboard" id="id_product_quantity" name="qty">
							</p>
							<p class="buttons_bottom_block" id="add_to_cart">
								<input type="button"  class="addCart" value="<?php echo $langs->trans("AddToTicket"); ?>" name="btnAddProductCart" id="btnAddProductCart">
                       		</p>
						</div>
               		<div style="clear:both"></div>
                    </div>	
            	</div>
        	</div>
		</div>
		
        <div id="tab-center-3" class="no-padding no-scrollbar ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" style="position: absolute; top: 0pt; bottom: 0pt; left: 0pt; right: 0pt; margin-top:0px !important;">
			<div id="customerSearch" class="topSearch grey" style="height:60px;padding:8px;">
				   <div class="but">
              		<img id="btnAddCustomer" title="<?php echo $langs->trans("NewCustomer"); ?>" name="btnAddProduct" src="./img/new_customer.png" height="38" >
               		<!--<span class="text"><?php echo $langs->trans("New"); ?></span>-->
              	</div>
              	 <div class="code">
				<label><?php echo $langs->trans("Search"); ?></label>
				<input onclick="this.select()" type="text"  size=10 name="id_customer_search" id="id_customer_search"></input>
				</div>  
			</div>
			<table id="customerTable" class="tableList">
				<thead>
					<tr>
						<th style="display:none"><?php echo $langs->trans("ID"); ?></th>
						<th><?php echo $langs->transcountry('ProfId1',$mysoc->country_code); ?></th>
						<th><?php echo $langs->trans("Name"); ?></th>
						<th><?php echo $langs->trans("Address"); ?></th>
						<th><?php echo $langs->trans("Tel."); ?></th>
						<th><?php echo $langs->trans("Actions"); ?></th>
					</tr>	
					</thead>
				<tbody></tbody>
			</table>
            
             <div class="go_up"><a class="grey" id="top" title="" target="_self"><?php echo $langs->trans("Up"); ?></a></div>
		
        </div>

		<div id="history" class="outline ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" style="margin-top:0px !important;">
        	
            <!-- berni -->
            <!-- INFO datos-->
            <div id="historyLeft" style="width:100%;"> 
         		 <!-- info del producto-->
	            <div id="historyOptions" class="leftBlock clearfix tabContainer2" style="display:none">
	                <input type="hidden" id="historyTicketSelected" value="">
	            	<div class="colActions"></div>
	            </div> 
	             <div class="tabContainer0" style="display:block;width:100%;height:55px;whitespace:nowrap;">  
	             <div style="float:left;"><img  title="Filtrado" src="./img/calendar.png" width="23" style="margin-left:6px;margin-top:10px;margin-right:4px;"></div>
	             
                <div onclick="_TPV.searchByRef(100);" class="botonStats" align="center" title=" "   >
                <span><?php echo $langs->trans("Today")?> </span>
                <span id="histToday"  style="font-size:22px">0 </span>
                </div> 
                <div onclick="_TPV.searchByRef(101);" class="botonStats" align="center" title=" " >
                    <span><?php echo $langs->trans("Yesterday")?> </span>
                    <span id="histYesterday" style="font-size:22px">0  </span>
                </div>
                <div onclick="_TPV.searchByRef(102);" class="botonStats" align="center" title=" "   >
                    <span> <?php echo $langs->trans("ThisWeek")?></span>     
                    <span  id="histThisWeek" style="font-size:22px">  0   </span>

                </div> 
                <div onclick="_TPV.searchByRef(103);" class="botonStats" align="center" title=" " >
                   <span> <?php echo $langs->trans("LastWeek")?> </span>
                    <span id="histLastWeek" style="font-size:22px">0  </span>
                </div>
                <div onclick="_TPV.searchByRef(104);" class="botonStats" align="center" title=" "   >
                    <span> <?php echo $langs->trans("TwoWeeksAgo")?></span>
                     <span  id="histTwoWeeks" style="font-size:22px">  0   </span>

       
                </div> 
                <div onclick="_TPV.searchByRef(105);" class="botonStats" align="center" title=" " >
  					<span> <?php echo $langs->trans("ThreeWeeksAgo")?></span>                    
                    <span id="histThreeWeeks" style="font-size:22px">0  </span>

                </div><div onclick="_TPV.searchByRef(106);" class="botonStats" align="center" title=" "   >
		           <span> <?php echo $langs->trans("ThisMonth")?></span>
                   <span  id="histThisMonth" style="font-size:22px">  0   </span>
      
                </div>
                 <div onclick="_TPV.searchByRef(107);" class="botonStats" align="center" title=" "   >
                 	<span> <?php echo $langs->trans("OneMonthAgo")?></span>
                    <span  id="histOneMonth" style="font-size:22px">0</span>
	
         
                </div> 
                <div onclick="_TPV.searchByRef(108);" class="botonStats" align="center" title=" " >
                <span> <?php echo $langs->trans("LastMonth")?> </span>                      
                 <span id="histLastMonth" style="font-size:22px">0</span>
    
                </div>
             </div>
                 
            </div>
            
            <!-- FIN INFO Datos -->
            <!-- berni -->
        

        	<div id="historyRight">
       <div class="grey">			
			<div id="refSearch" class="topSearch tabContainer1" style="height:40px;padding:8px;">
				<!-- <label><?php echo $langs->trans("Search"); ?></label> -->	
				 
                <img id="img_ref_search" class="search_but" src="./img/search_ticket.png"  height="40px" style="float:left;">
            	<input onclick="this.select()" type="text" size=10 name="id_ref_search" id="id_ref_search"></input>
			</div>
			 <div id="historyTypes" >
                     <div id="legend" class="legend" >
                     	
                       <a class="icontype state0"  onclick="_TPV.searchByRef(0);"><?php echo $langs->trans('StatusTicketDraft');?></a> 
                        <a class="icontype state1" onclick="_TPV.searchByRef(1);"><?php echo $langs->trans('StatusTicketClosed');?></a>
                        <a class="icontype state2" onclick="_TPV.searchByRef(2);"><?php echo $langs->trans('StatusTicketProcessed');?></a>
                        <a class="icontype state3" onclick="_TPV.searchByRef(3);"><?php echo $langs->trans('StatusTicketCanceled');?></a>
                        <a class="icontype state1 type1" onclick="_TPV.searchByRef(4);"><?php echo $langs->trans('StatusTicketReturned');?></a>
                      
     
                    </div>
                </div>
	</div>	                
			<div id="historyContainer">
			<table id="historyTable" class="tableList">
				
				<thead>
					<tr>
						<th><?php echo $langs->trans("Reference"); ?></th>
						<th><?php echo $langs->trans("Date"); ?></th>
						<th><?php echo $langs->trans("Terminal"); ?></th>
						<th><?php echo $langs->trans("User"); ?></th>
						<th><?php echo $langs->trans("Customer"); ?></th>
						
						<th><?php echo $langs->trans("Total"); ?></th>
						<th style="display:none"><?php echo $langs->trans("Actions"); ?></th>
					</tr>	
				</thead>
				<tbody></tbody>
			</table>
			</div>
            </div>
      
            
             <div class="go_up"><a class="grey" id="top" title="" target="_self"><?php echo $langs->trans("Up"); ?></a></div>
		</div>
		
		<div id="historyFac" class="outline ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" style="margin-top:0px !important;">
        	
            <!-- berni -->
            <!-- INFO datos-->
            <div id="historyFacLeft" style="width:100%;"> 
         		 <!-- info del producto-->
	            <div id="historyFacOptions" class="leftBlock clearfix tabContainer2" style="display:none">
	                <input type="hidden" id="historyFacTicketSelected" value="">
	            	<div class="colActions"></div>
	            </div> 
	             <div class="tabContainer0" style="display:block;width:100%;height:55px;whitespace:nowrap;">  
	             <div style="float:left;"><img  title="Filtrado" src="./img/calendar.png" width="23" style="margin-left:6px;margin-top:10px;margin-right:4px;"></div>
	            
	            <div onclick="_TPV.searchByRefFac(100);" class="botonStats" align="center" title=" "  >
                    <span ><?php echo $langs->trans("Today")?> </span>
                    <span id="histFacToday"  style="font-size:22px">  0   </span>
                </div>
           
                <div onclick="_TPV.searchByRefFac(101);" class="botonStats" align="center" title=" " >
                     <span><?php echo $langs->trans("Yesterday")?> </span>
                    <span id="histFacYesterday" style="font-size:22px">0  </span>
                </div>
               
                <div onclick="_TPV.searchByRefFac(102);" class="botonStats" align="center" title=" "  >
                   <span> <?php echo $langs->trans("ThisWeek")?></span>
                    <span  id="histFacThisWeek" style="font-size:22px">  0   </span>
           		</div> 
                <div onclick="_TPV.searchByRefFac(103);" class="botonStats" align="center" title=" " >
                    <span> <?php echo $langs->trans("LastWeek")?> </span>
                    <span id="histFacLastWeek" style="font-size:22px">0  </span>
                </div>
                <div onclick="_TPV.searchByRefFac(104);" class="botonStats" align="center" title=" "  >
                     <span> <?php echo $langs->trans("TwoWeeksAgo")?></span>
                    <span  id="histFacTwoWeeks" style="font-size:22px">  0   </span>
	            </div> 
                <div onclick="_TPV.searchByRefFac(105);" class="botonStats" align="center" title=" " >
                     <span> <?php echo $langs->trans("ThreeWeeksAgo")?></span>
                    <span id="histFacThreeWeeks" style="font-size:22px">0  </span>
                </div>
                <div onclick="_TPV.searchByRefFac(106);" class="botonStats" align="center" title=" "  >
                     <span> <?php echo $langs->trans("ThisMonth")?></span>
                    <span  id="histFacThisMonth" style="font-size:22px">  0   </span>
                </div>
                 <div onclick="_TPV.searchByRefFac(107);" class="botonStats" align="center" title=" "  >
                    <span> <?php echo $langs->trans("OneMonthAgo")?></span>
                    <span  id="histFacOneMonth" style="font-size:22px">  0   </span>
                </div> 
                <div onclick="_TPV.searchByRefFac(108);" class="botonStats" align="center" title=" " >
                   <span> <?php echo $langs->trans("LastMonth")?> </span>
                   <span id="histFacLastMonth" style="font-size:22px">0  </span>
                </div>
             </div>
                 
            </div>
            
            <!-- FIN INFO Datos -->
            <!-- berni -->
        
        	<div id="historyFacRight">
			 <div class="grey">
			<div id="refFacSearch" class="topSearch tabContainer1" style="height:40px;padding:8px;">
				<!-- <label><?php echo $langs->trans("Search"); ?></label> -->	
				<img id="img_ref_fac_search" class="search_but" src="./img/search_ticket.png"  height="40px" style="float:left;">
                <input onclick="this.select()" type="text" size=10 name="id_ref_fac_search" id="id_ref_fac_search"></input>
			</div>
			 <div id="historyFacTypes" >
                     <div id="legendFac" class="legend" >
                        <a class="icontype state0"  onclick="_TPV.searchByRefFac(0);" ><?php echo $langs->trans('BillStatusDraft');?></a> 
                        <a class="icontype state1" onclick="_TPV.searchByRefFac(1);" ><?php echo $langs->trans('BillStatusValidated');?></a>
                        <a class="icontype state2" onclick="_TPV.searchByRefFac(2);" ><?php echo $langs->trans('BillStatusPaid');?></a>
                        <a class="icontype state3" onclick="_TPV.searchByRefFac(3);" ><?php echo $langs->trans('BillStatusCanceled');?></a>
                        <a class="icontype state1 type1" onclick="_TPV.searchByRefFac(4);"><?php echo $langs->trans('StatusTicketReturned');?></a>
                      
     
                    </div>
                </div>
                </div>
			<div id="historyFacContainer">
			<table id="historyFacTable" class="tableList">
				
				<thead>
					<tr>
						<th><?php echo $langs->trans("Reference"); ?></th>
						<th><?php echo $langs->trans("Date"); ?></th>
						<th><?php echo $langs->trans("Terminal"); ?></th>
						<th><?php echo $langs->trans("User"); ?></th>
						<th><?php echo $langs->trans("Customer"); ?></th>
						
						<th><?php echo $langs->trans("Total"); ?></th>
						<th style="display:none"><?php echo $langs->trans("Actions"); ?></th>
					</tr>	
				</thead>
				<tbody></tbody>
			</table>
			</div>
            </div>
      
      		      
             <div class="go_up"><a class="grey" id="top" title="" target="_self"><?php echo $langs->trans("Up"); ?></a></div>
		</div>
		
		<div id="almacen" class="outline ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" style="margin-top:0px !important;">
		
		
			
              <!-- berni -->
        <!-- INFO datos-->
        <div id="ticketLeft">
      		<div id="products" class="leftBlock"  style="overflow: auto;" ></div> 
        	<div style="">
	            <div class="clearfix tabContainer2" id="info_product_st">
	            	<div id="product-right-column" style="padding: 10px; color: #fff">
	                    <div>
	                    <img width="90px;" height="90px;" style="border-radius: 20px 15px 20px 20px; float: left; padding-right: 8px; padding-bottom: 8px;" width="100%" id="bigpic" alt="" title="<?php echo $langs->trans("Product"); ?>" src="" style="display: inline;">
	                    </div>
	                    <div class="label">
	                        <span id="our_label_display_st" style="font-size: 20px !important;"> </span>
	                    </div>
	                     <div class="price">
	                        <span class="our_price_display" >
	                            <span id="our_price_display_st" style="font-size: 28px !important;"> </span><?php echo $langs->trans($conf->currency);?> 
	                        </span>
	                    </div>
	                    <div class="price_min">
	                        <span id="our_price_min_st" class="our_price_min_display" >
	                            <span id="our_price_min_display_st" style="font-size: 14px !important;"> </span><?php echo $langs->trans($conf->currency);?> 
	                        </span>
	                    </div>
	                    <a class="btn3d" id="btnHideInfoSt" style="float: right; width: 80px;"><?php echo $langs->trans("More");?> </a>
	                    <div id="short_description_block_st">
	                        <p><br><span class="rte align_justify" id="short_description_content_st" style="font-size: 11px;"></span></p>
	                    </div>
	                    
	                    
					</div>
	            	<div style="clear:both"></div>
	            </div>
            </div>	
             
            
       <div>    
              <div id="stockOptions" class="leftBlock clearfix tabContainer2" style="display:none">
	                <input type="hidden" id="stockSelected" value="">
	            	<div class="colActions"></div>
	            </div> 
                <div onclick="_TPV.searchByStock(-1,_TPV.warehouseId);" class="botonStats" align="center" title=" " style="width: 48%">
                    <span ><?php echo $langs->trans('NoSell')?></span>
                    <span id="stockNoSell" style="font-size:22px">0</span>
                </div>
                
                <div onclick="_TPV.searchByStock(-2,_TPV.warehouseId);" class="botonStats" align="center" title=" " style="width: 48%" >
                    <span ><?php echo $langs->trans('Sell')?></span>
                    <span id="stockSell" style="font-size:22px">0</span>
                </div> 
                 
                <div onclick="_TPV.searchByStock(-3,_TPV.warehouseId);" class="botonStats" align="center" title=" " style="width: 48%">
                    <span ><?php echo $langs->trans('WithStock')?></span>
                    <span id="stockWith" style="font-size:22px">0</span>
                </div> 
                
                <div onclick="_TPV.searchByStock(-4,_TPV.warehouseId);" class="botonStats" align="center" title=" " style="width: 48%">
                    <span ><?php echo $langs->trans('NoStock')?></span>
                    <span id="stockWithout" style="font-size:22px">0</span>
                </div> 
                
                <div onclick="_TPV.searchByStock(-5,_TPV.warehouseId);" class="botonStats" align="center" title=" " style="width: 48%">
                    <span ><?php echo $langs->trans('BestSell')?></span>
                    <span id="stockBest" style="font-size:22px">0</span>
                </div> 
                
                <div onclick="_TPV.searchByStock(-6,_TPV.warehouseId);" class="botonStats" align="center" title=" " style="width: 48%">
                    <span ><?php echo $langs->trans('WorstSell')?></span>
                    <span id="stockWorst" style="font-size:22px">0</span>
                </div> 
                
                <?php 
       		
       		$list = array();
       		$list = POS::getWarehouse();
       		$num = count($list);
       		$i=0;
       		$warehouse = new Entrepot($db);
       		while($i < $num){
				$warehouse->fetch($list[$i]['id']);
				$ret = $warehouse->nb_products();
       ?>
                <div onclick="_TPV.searchByStock(1,<?php echo $list[$i]['id']?>);" class="botonStats" align="center" title=" " style="width: 48%">
                   <span><?php echo $warehouse->libelle;?></span>
                    <span   style="font-size:22px"><?php echo is_null($ret['nb'])?0:$ret['nb'];?> </span>
                </div> 
                <?php $i++;}?>
                
             </div>
             </div>
        
        <!-- FIN INFO Datos -->
        <!-- berni -->
            
            
            
			
       <div id="ticketRight"> 
			<div id="stockSearch" class="topSearch tabContainer1" style="width:100%; height:60px;padding:8px;" >
			<div class="but" >
              		 <img height="38" id="btnAddProduct" title="<?php echo $langs->trans("AddProductTicket"); ?>" name="btnAddProduct" src="./img/add_product.png">
              		 
               		<!-- <span class="text"><?php echo $langs->trans("NewProduct"); ?></span>-->
            </div>	
            <div class="inputs"  >
            <!-- 
            <label><?php echo $langs->trans("Search"); ?></label>
			-->
			<img id="img_stock_search" class="search_but" src="./img/search_prod.png" height="40px" style="float:left; margin-right:5px;"  >
			<input onclick="this.select()" type="text" size=10 name="id_stock_search" id="id_stock_search"></input>
			</div> 
			
			
			      
			</div>
            <div>
			<table id="storeTable" class="tableList" style="clear:both;" >
				<thead>
					<tr>
						<th>Id</th>
						<th><?php echo $langs->trans("Reference"); ?></th>
						<th><?php echo $langs->trans("Name"); ?></th>
						<th><?php echo $langs->trans("Stock"); ?></th>
						<th><?php echo $langs->trans("Warehouse"); ?></th>
						<th style="display:none"><?php echo $langs->trans("Actions"); ?></th>
					</tr>	
				</thead>
				<tbody>	</tbody>	
				</table>
            </div>
                
                 <div class="go_up"><a class="grey" id="top" title="" target="_self"><?php echo $langs->trans("Up"); ?></a></div>
		</div>		
		</div>
		<div id="places" class="outline ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" style="margin-top:0px !important;">
			<div id="placeTable"></div>
            <div class="go_up"><a class="grey" id="top" title="" target="_self"><?php echo $langs->trans("Up"); ?></a></div>
				
		
		
		</div>
		<!--  <div id="tab-dashboard" class="outline ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" style="margin-top:0px !important;">
			
			 <table cellpadding="10px;" cellspacing="10px;" width="100%">
			 	<tr valign="top">
			 		<td width="400px;">
			 			
			 			 <center><h2 style="background-color: #555;">Dashboard</h2></center>
			 			 <!-- dashboard 
			 			 	<script type="text/javascript" src="http://www.google.com/jsapi"></script>
			 			    <script type="text/javascript">
						      google.load('visualization', '1', {packages: ['gauge']});
						    </script>
						    <script type="text/javascript">
						      function drawVisualization() {
						        // Create and populate the data table.
						        var data = google.visualization.arrayToDataTable([
						          ['Label', 'Value'],
						          ['Memory', 80],
						          ['CPU', 55],
						          ['Network', 68]
						        ]);
						      
						        // Create and draw the visualization.
						        new google.visualization.Gauge(document.getElementById('visualization2')).
						            draw(data);
						      }
						      
						
						      google.setOnLoadCallback(drawVisualization);
						    </script> 
			 			 <center>
			 			     <div id="visualization2" style="width: 400px; height: 140px;"></div>
			 			 </center>
			 			
			 			
			 			<!-- gr�fico 
			 			
			 			 <script type="text/javascript" src="http://www.google.com/jsapi"></script>
						    <script type="text/javascript">
						      google.load('visualization', '1');
						    </script>
						    <script type="text/javascript">
						      function drawVisualization() {
						        var wrapper = new google.visualization.ChartWrapper({
						          chartType: 'ColumnChart',
						          dataTable: [['', 'Germany', 'USA', 'Brazil', 'Canada', 'France', 'RU'],
						                      ['', 700, 300, 400, 500, 600, 800]],
						          options: {'title': 'Countries'},
						          containerId: 'visualization'
						        });
						        wrapper.draw();
						      }
						      
						      
						
						      google.setOnLoadCallback(drawVisualization);
						    </script>
						 <center>
			 			 <div id="visualization" style="width: 400px; height: 200px;"></div>
			 			 </center>
			 			 
			 			 <div  class="botonStats" align="center" title=" " style="background:#e5d726 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;">
		                    <div align="center">
		                    <span  style="font-size:22px">8  </span>
		                    <br/>
		                    <span>Tickets sin cerrar </span>
		                    </div>
		                </div>
		                <div  class="botonStats" align="center" title=" "  style="background:#ff0000 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		                </div> 
		                <div  class="botonStats" align="center" title=" "  style="background:#e5d726 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		                </div> 
			 			 
			 		</td>
			 		<td width="29,33%" >
			 			<div style="margin-left: 10px;">
						<center><h2 style="background-color: #555;">Dashboard</h2></center>			 			
						<div  class="botonStats" align="center" title=" "  style="background:#ff0000 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		           
		                </div> 
		                <div  class="botonStats" align="center" title=" " style="background:#389f1d !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;">
		                    <div align="center">
		                    <span  style="font-size:22px">8  </span>
		                    <br/>
		                    <span>Tickets sin cerrar </span>
		                    </div>
		                </div>
		                <div  class="botonStats" align="center" title=" "  style="background:#e5d726 !important; border-radius:20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		           
		                </div> 
			 			<div  class="botonStats" align="center" title=" " style="background:#389f1d !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;">
		                    <div align="center">
		                    <span  style="font-size:22px">8  </span>
		                    <br/>
		                    <span>Tickets sin cerrar </span>
		                    </div>
		                </div>
		                <div  class="botonStats" align="center" title=" "  style="background:#ff0000 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		           
		                </div> 
		                <div  class="botonStats" align="center" title=" " style="background:#e5d726 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;">
		                    <div align="center">
		                    <span  style="font-size:22px">8  </span>
		                    <br/>
		                    <span>Tickets sin cerrar </span>
		                    </div>
		                </div>
		                <div  class="botonStats" align="center" title=" "  style="background:#ff0000 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		                </div> 
		                <div  class="botonStats" align="center" title=" "  style="background:#e5d726 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		                </div> 
		                <div  class="botonStats" align="center" title=" " style="background:#389f1d !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;">
		                    <div align="center">
		                    <span  style="font-size:22px">8  </span>
		                    <br/>
		                    <span>Tickets sin cerrar </span>
		                    </div>
		                </div>
		                <div  class="botonStats" align="center" title=" "  style="background:#ff0000 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		           
		                </div> 
		                <div  class="botonStats" align="center" title=" " style="background:#e5d726 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;">
		                    <div align="center">
		                    <span  style="font-size:22px">8  </span>
		                    <br/>
		                    <span>Tickets sin cerrar </span>
		                    </div>
		                </div>
		                
			 			</div>
			 		</td>
			 		<td >
			 			<center><h2 style="background-color: #555;">Dashboard</h2></center>
			 			<div style="margin-left: 10px;">
			 			<div  class="botonStats" align="center" title=" " style="background:#389f1d !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;">
		                    <div align="center">
		                    <span  style="font-size:22px">8  </span>
		                    <br/>
		                    <span>Tickets sin cerrar </span>
		                    </div>
		                </div>
		                <div  class="botonStats" align="center" title=" "  style="background:#e5d726 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		           
		                </div> 
		                <div  class="botonStats" align="center" title=" " style="background:#389f1d !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;">
		                    <div align="center">
		                    <span  style="font-size:22px">8  </span>
		                    <br/>
		                    <span>Tickets sin cerrar </span>
		                    </div>
		                </div>
		                <div  class="botonStats" align="center" title=" "  style="background:#ff0000 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		           
		                </div> 
		                <div  class="botonStats" align="center" title=" " style="background:#e5d726 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;">
		                    <div align="center">
		                    <span  style="font-size:22px">8  </span>
		                    <br/>
		                    <span>Tickets sin cerrar </span>
		                    </div>
		                </div>
		              </div>
			 		</td>
			 	</tr>
			 </table>
			 
			  
		</div>-->

	<div id="buttomTicket"  class="ui-layout-south ui-widget-content ui-corner-bottom no-scrollbar ui-layout-pane">
								
	</div>
	<!-- /centerTabsLayout--> 

</div>
<div id="showpanels" style="display:none">
		
		<div id="idEmployee" class="bloqueOpciones" title="<?php echo $langs->trans("Employees");?>" style="display:none;">
			<div class="options">
			<?php 	
				$users = POS::select_Users();
				if (!empty($users)) {
					foreach ($users as $user) {
						echo "<div class='btnselect'><a id='employeetype" . $user['code'] . "' photo='" . $user['photo'] . "' login='" . $user['login'] . "'>" . $user['label'] . "</a></div>";
					}
				}
			?>	
			</div>	
		</div>
		
		<div id="idEmpPass" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("Password"); ?>">
			<div class="options">
				<ul>
					<li><label> <?php echo $langs->trans("Password"); ?>:</label><input style="width:175px;" onclick="this.select()" type="password" size="5" maxlength="40" name="password" id="password"  value="" class="quertyKeyboard">
					<br clear="all" />
					<input type="button" id="id_btn_empPass" value="<?php echo $langs->trans("Send"); ?>" class="btn3dbig"></li>
				</ul>		
			</div>	
		</div>
								
	
								
		<div id="idPanelInfo" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("Info"); ?>">
			<div class="options">
				<div id="infoInfo" style="margin-top: 23px; margin-left: 15%;">
					<img src="./img/ok.png" style="float:left;">
					<span id="infoText"></span>
				</div>		
			</div>	
		</div>
		
		<div id="idPanelError" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("Error"); ?>">
			<div class="options">
				<div id="infoError" style="margin-top: 23px; margin-left: 15%;">
					<img src="./img/error.png" style="float:left;">
					<span id="errorText"></span>
				</div>		
			</div>	
		</div>
								
		
		
		<!-- Mis cosicas para enviar por mail -->
		<div id="idSendMail" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("SendMail"); ?>">
			<div class="options">
				<ul>
					<li><label> <?php echo $langs->trans("MailTo"); ?>:</label><input style="width:175px;" onclick="this.select()" type="text" size="5" maxlength="40" name="mail_to" id="mail_to"  value="" class="quertyKeyboard">
					<br clear="all" />
					<input type="button" id="id_btn_ticketLine" value="<?php echo $langs->trans("Send"); ?>" class="btn3dbig"></li>
				</ul>		
			</div>	
		</div>
		
		<div id="ticketNote" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("TicketNote"); ?>">
			<div class="options">
				<ul>
				<li><label><?php echo $langs->trans("Note"); ?></label><input onclick="this.select()" type="text" size="10" name="ticket_note" id="ticket_note"  value="" class="quertyKeyboard" />
				<input type="button" id="id_btn_ticket_note" value="<?php echo $langs->trans("Save"); ?>" class="btn3dbig"></li>
				</ul>	
			</div>	
		</div>

    <div id="paymentMenor" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("PaymentMenor"); ?>">
        <div class="options">
            <ul>
                <li><label><?php echo $langs->trans("ConfirmPaymentMenor"); ?></label>
                    <input type="button" id="id_btn_payment_menor_yes" value="<?php echo $langs->trans("Yes"); ?>" class="btn3dbig">
                    <input type="button" id="id_btn_payment_menor_no" value="<?php echo $langs->trans("No"); ?>" class="btn3dbig"></li>
            </ul>
        </div>
    </div>
		
		<div id="idTicketDelet" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("DeleteTicket"); ?>">
			<div class="options">
				<div>
					<p> <?php echo $langs->trans("ConfirmDeleteTicket"); ?></p>
					<input type="button" id="id_btn_ticketYes" value="<?php echo $langs->trans("Yes"); ?>" class="btn3dbig">
					<input type="button" id="id_btn_ticketNo" value="<?php echo $langs->trans("No"); ?>" class="btn3dbig">
				</div>		
			</div>	
		</div>
		
		<!-- Mis cosicas para enviar por mail -->
		
		<div id="idPanelProduct" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("AddProduct");?>">
			<div class="options">
				<ul>
					<li><label><?php echo $langs->trans("Name");?>:</label><input onclick="this.select()" type="text" name="id_product_name" id="id_product_name" class=""></li>
                    <br clear="all" />
					<li><label><?php echo $langs->trans("Reference");?>:</label><input onclick="this.select()" type="text" name="id_product_ref" id="id_product_ref" class=""></li>
                    <br clear="all" />
					<li><label><?php echo $langs->trans("PricePVP");?>:</label><input onclick="this.select()" type="text" name="id_product_price" class="numKeyboard" id="id_product_price" class=""></li>
					<br clear="all" />
				</ul>
				<div style="margin-left:5%;">	
					<?php 
						$taxes = POS::select_VAT();
						foreach($taxes as $tax) 
						{
							echo "<div class='btnselect btnminiselect tax_types'><a title='".$tax['id']."' id='taxtype".$tax['id']."'>".$tax['label']."</a></div>";
						}
					?>
				</div>
                    <input type="button" id="id_btn_add_product" value="<?php echo $langs->trans("New");?>" class="btn3dbig" onclick="" style="display:inline-block">
						
			</div>	
		</div>
		
		<div id="idClient" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("AddCustomer"); ?>">
			<div class="options">
				<ul>
					<li><label><?php echo $langs->trans("FirstName");?>:</label><input onclick="this.select()" type="text" name="id_customer_name" id="id_customer_name" class=""></li>
                    <br clear="all" />
					<li><label><?php echo $langs->trans("LastName");?>:</label><input onclick="this.select()" type="text" name="id_customer_lastname" id="id_customer_lastname" class=""></li>
                    <br clear="all" />
					<li><label><?php echo $langs->transcountry('ProfId1',$mysoc->country_code);?>:</label><input onclick="this.select()" type="text" name="id_customer_cif" id="id_customer_cif" class=""></li>
					<br clear="all" />
					<li><label><?php echo $langs->trans("Address");?>:</label><input onclick="this.select()" type="text" name="id_customer_address" id="id_customer_address" class=""></li>
					<br clear="all" />
					<li><label><?php echo $langs->trans("Town");?>:</label><input onclick="this.select()" type="text" name="id_customer_town" id="id_customer_town" class=""></li>
					<br clear="all" />
					<li><label><?php echo $langs->trans("Zip");?>:</label><input onclick="this.select()" type="text" name="id_customer_zip" id="id_customer_zip" class=""></li>
					<br clear="all" />
					<li><label><?php echo $langs->trans("Phone");?>:</label><input onclick="this.select()" type="text" name="id_customer_phone" id="id_customer_phone" class=""></li>
					<br clear="all" />
					<li><label><?php echo $langs->trans("Email");?>:</label><input onclick="this.select()" type="text" name="id_customer_email" id="id_customer_email" class=""></li>
					<br clear="all" />
                </ul>    
                <input type="button" id="id_btn_add_customer" value="<?php echo $langs->trans("New");?>" class="btn3dbig">
						
			</div>	
		</div>
		
		<div id="idCloseCash" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("CloseCash");?>">
			<div class="options">
				
					<!--<div  class='btnselect --><div class='close_types btnon'><a class="btn3dbig" id='closetype1' style='height:40px;'><?php echo $langs->trans("Closing");?></a></div>
                    <!--<div   class='btnselect--><div class='close_types'><a class="btn3dbig" id='closetype0' style='height:40px;'><?php echo $langs->trans("Arching");?></a></div>
                  <ul>                   
                    <br clear="all" />
					<li><label><?php echo $langs->trans("CashMoney");?>:</label><input onclick="this.select()" type="text" name="id_terminal_cash" id="id_terminal_cash" readonly="readonly"></li>
                    <br clear="all" />
					<li><label><?php echo $langs->trans("MoneyInCash");?>:</label><input onclick="this.select()" type="text" name="id_money_cash" id="id_money_cash" class="numKeyboard"></li>
                    <br clear="all" />
                </ul>    
                    <input type="button" id="id_btn_close_cash" value="<?php echo $langs->trans("MakeCloseCash");?>" class="btn3dbig">
						
			</div>	
		</div>
		
		<div id="idTotalNote" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("Notes");?>">
			<div class="options">
				<div>
					<table id="noteTable" class="tableList" >
				
				<thead>
					<tr>
						<!--<th style="display:none"><?php echo $langs->trans("ID"); ?></th>
						<th><?php echo $langs->trans("Reference"); ?></th>
						 <th><?php echo $langs->trans("Description"); ?></th> 
						<th><?php echo $langs->trans("Note"); ?></th>
						<th><?php echo $langs->trans("Actions"); ?></th>-->
					</tr>	
				</thead>
				<tbody></tbody>
			</table>
				</div>		
			</div>	
		</div>
		
		<div id="idChangeCustomer" class="bloqueOpciones" style="display:none; height:400px !important;" title="<?php echo $langs->trans("ChangeCustomer");?>">
			<div class="options">
				<div id="customerSearch_" class="topSearch">
					<div class="code">
						<img id="img_customer_search" class="search_but" class="but" src="./img/search_customer.png" height="40px" style="float:left;margin-left:8px;cursor:pointer">
						<input onclick="this.select()" type="text"  size=10 name="id_customer_search_" id="id_customer_search_"></input>
					</div>  
				</div>
			<table id="customerTable_" class="tableList" style="float:left;">
				<thead>
					<tr>
						<th style="display:none"><?php echo $langs->trans("ID"); ?></th>
						<th width=100px; style="color: #fff;"><?php echo $langs->transcountry('ProfId1',$mysoc->country_code); ?></th>
						<th><?php echo $langs->trans("Name"); ?></th>
						<th width=70px;><?php echo $langs->trans("Actions"); ?></th>
					</tr>	
					</thead>
				<tbody></tbody>
			</table>
            </div>         
        </div>
        
        <div id="idCoupon" class="bloqueOpciones" style="display:none; height:400px !important;" title="<?php echo $langs->trans("AddCoupon");?>">
			<div class="options">
			<table id="couponTable_" class="tableList" style="float:left;">
				<thead>
					<tr>
						<th style="display:none"><?php echo $langs->trans("ID"); ?></th>
						<th><?php echo $langs->trans("ReasonDiscount"); ?></th>
						<th width=100px;><?php echo $langs->trans("AmountTTC"); ?></th>
						<th width=70px;><?php echo $langs->trans("Actions"); ?></th>
					</tr>	
					</thead>
				<tbody></tbody>
			</table>
            </div>         
        </div>
    <?php if($conf->global->POS_BATCH_SERIE==1){ ?>
    <div id="idgetBatch" class="bloqueOpciones" style="display:none; height:400px !important;" title="<?php echo $langs->trans("GetBatch");?>">
        <div class="options">
            <div id="batchSearch_" class="topSearch">
                <div class="code">
                    <img id="img_batch_search" class="search_but" class="but" src="./img/search_prod.png" height="40px" style="float:left;margin-left:8px;cursor:pointer">
                    <input onclick="this.select()" type="text"  size=10 name="id_batch_search_" id="id_batch_search_"></input>
                </div>
            </div>
            <table id="batchTable_" class="tableList" style="float:left;">
                <thead>
                <tr>
                    <th width=100px; style="color: #fff;"><?php echo $langs->trans("Batch"); ?></th>
                    <th><?php echo $langs->trans('Sellby'); ?></th>
                    <th><?php echo $langs->trans("Eatby"); ?></th>
                    <th width=70px;><?php echo $langs->trans("Actions"); ?></th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div id="idgetBatchRet" class="bloqueOpciones" style="display:none; height:400px !important;" title="<?php echo $langs->trans("GetBatch");?>">
        <div class="options">
            <table id="batchTableRet_" class="tableList" style="float:left;">
                <thead>
                <tr>
                    <th width=200px; style="color: #fff;"><?php echo $langs->trans("Batch"); ?></th>
                    <th width=70px;><?php echo $langs->trans("Actions"); ?></th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <?php } ?>
    <div id="idChangePlace" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("ChangePlace");?>">
			<div class="options">
				<div id="placeTable_"></div>
            </div>
		</div>

      </div>                      
<a class="btnPrint" href='tpl/ticket.tpl.php?id=1' style="display:none"></a>
<div id="chat_div"></div>



<!-- GO TABLE, GO UP! -->
<script type="text/javascript">
					$("a#top").click(function() {
  					$("div.ticket_content").animate({ scrollTop: 0 }, "slow");
  					return false;
					});
                </script>

<?php if($conf->global->POS_CHAT){?>

   
                <script type="text/javascript">
      $(document).ready(function(){
          var box = null;
          oldscrollHeight = 0;
          box = $("#chat_div").chatbox({id:"chat_div", 
              user:{key : "value"},
              title : "Chat",
              messageSent : function(id, user, msg) {
                  $("#chat_div").chatbox("option", "boxManager").addMsg(id, msg);
              }});
          box.chatbox("option", "boxManager").toggleBox();
          $("#id_btn_chat").click(function() {
            //  if(box) {
                  box.chatbox("option", "boxManager").toggleBox();
              //}
            /*  else {
                  box = $("#chat_div").chatbox({id:"chat_div", 
                                                user:{key : "value"},
                                                title : "Chat",
                                                messageSent : function(id, user, msg) {
                                                    $("#chat_div").chatbox("option", "boxManager").addMsg(id, msg);
                                                }});
              }*/
          });
          function loadLog(){	
                            
           $.ajax({
      			url: "chat.html",
      			cache: false,
      			success: function(html){
      				$("#chat_div").html(html); //Insert chat log into the #chatbox div
      				var newscrollHeight = html.length;
      				if(newscrollHeight > oldscrollHeight){
              			oldscrollHeight = newscrollHeight;
      					$("#chat_div").animate({ scrollTop: newscrollHeight }, 'normal'); //Autoscroll to bottom of div
      					$("#chat_div").chatbox("option", "boxManager").myfunc();      					
      				}				
      		  	},
      		});
      	}
      	setInterval (loadLog, 4000);	//Reload file every 4 seconds
      });
    </script>
    <?php }?>
  
</body>
</html>

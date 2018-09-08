<?php
/* Copyright (C) 2011-2012	   Juanjo Menent   	   <jmenent@2byte.es>
 * Copyright (C) 2012-2017	   Ferran Marcet   	   <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/pos/frontend/index.php
 * 	\ingroup	pos
 *  \brief      File to login to point of sales
 */

// Set and init common variables
// This include will set: config file variable $dolibarr_xxx, $conf, $langs and $mysoc objects

$res=@include("../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");                // For "custom" directory

dol_include_once('/pos/class/pos.class.php');
//if(!class_exists('Mobile_Detect'))
//	dol_include_once('/pos/class/mobile_detect.php');

global $langs,$user;

$langs->load("admin");
$langs->load("pos@pos");

if (! $user->rights->pos->frontend)
  accessforbidden();

// Test if user logged
if ( $_SESSION['uid'] > 0 )
{
	header ('Location: '.dol_buildpath('/pos/frontend/disconect.php',1));
	exit;
}

global $user,$conf;

$usertxt=$user->login;
//$pwdtxt=$user->pass;

//hacer un getpost para recoger usuario, pass y terminal y que redireccione a verify.php Tú lo vales!!!
if(GETPOST("username")){
	$_SESSION["username"] = GETPOST("username","alpha");
	$_SESSION["password"] = GETPOST("password","alpha");
	$_SESSION["terminal"] = GETPOST("terminal","int");
 	header('Location: '.dol_buildpath('/pos/frontend/verify.php',1));
	exit;
}

$openterminal=GETPOST("openterminal");

/*
 * View
 */

$arrayofcss=array('/pos/frontend/css/pos.css');
top_htmlhead('','',0,0,'',$arrayofcss);

?>

	<!-- Basic Page Needs
  ================================================== -->
	<meta charset="utf-8">
	<title>DoliPOS</title>
	<meta name="description" content="">
	<meta name="author" content="">

	<!-- Mobile Specific Metas
  ================================================== -->
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

	<!-- CSS
  ================================================== -->
  	<link rel="stylesheet" href="js/jqtransform.css" type="text/css" media="all" />
  	<link rel="stylesheet" type="text/css" href="css/jquery.tweet.css"/>
	<link rel="stylesheet" type="text/css" href="css/keyboard.css">
	<link rel="stylesheet" href="css/base.css">
	<link rel="stylesheet" href="css/skeleton.css">
	<link rel="stylesheet" href="css/layout.css"> 
	

	
	
	
    <link href='https://fonts.googleapis.com/css?family=Exo:200,700' rel='stylesheet' type='text/css'>
    
	<script type="text/javascript" src="js/jquery.jqtransform.js" ></script>
	<script type="text/javascript" src="js/jquery.keyboard.min.js"></script>
	<script type="text/javascript" src="js/jquery-migrate-1.4.0.min.js"></script>
	<script language="javascript">
	$(function(){
		$('form.nice').jqTransform({imgPath:'img/'});
		
		});
		$(document).ready(function() {
			$('#tpvtactil').click(function(){
				tpvtactil();
			});
		});
	
		function tpvtactil()
		{
			$('#tpvtactil').removeClass('tactilon');	
			$('[type=text]').keyboard({
				layout:'qwerty',
				usePreview:false , 
				autoAccept : true,
				accepted : function(e, keyboard, el){
			
			}	
		});
		$('[type=password]').keyboard({
			layout:'qwerty',
			usePreview:false , 
			autoAccept : true,
			accepted : function(e, keyboard, el){
			
			}	
		});
	}
</script>
	
<body>


	<div class="container" >
		<div class="twelve2 columns">
        	<div class="twelve2 columns">
				<br><br><br><br><br><br>
				<h3><?php echo $langs->trans("HeadPos"); ?> </h3>
				<br><br><br>
			</div>
           
        	
		</div>
		
        
        
	<div class="twelve columns">
		<?php if(GETPOST("err","string")) {?>	
        <div class="errorLogin"><?php print GETPOST("err","string")."<br>"; ?></div> <?php }?>
		<fieldset class="cadre_facturation"><!--<legend class="titre1"><?php /*?><?php echo $langs->trans("Identification"); ?><?php */?></legend>-->

		
		 <?php 
				$tmp_terminals=POS::select_Terminals();
		 if (is_array($tmp_terminals)){
			 foreach ($tmp_terminals as $terminal)
			 {
				 if(POS::checkUserTerminal($user->id,$terminal["rowid"])) {
					 $terminals[]=$terminal;
				 }

			 }
		 }

				if(count($terminals))
				{	
?>
		  <div class="six columns">	
			<form id="frmLogin" method="POST" action="verify.php" class="nice">
				<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />

					<br>
					<label><?php echo $langs->trans("Login"); ?></label>
					<input name="username" class="texte_login" type="text" value="<?php echo $usertxt; ?>"  />
					<div class="sep"></div>
					<label><?php echo $langs->trans("Password"); ?></label>
					<input name="password" class="texte_login" type="password"	value="" />
					<div class="sep"></div>
					<label><?php echo $langs->trans("CashS"); ?></label>
					<select name='terminal'>
					<!--<option value='-1'><?php $langs->trans("Choose"); ?></option> -->
        
<?php 
					///$detect = new Mobile_Detect();
					$i=0;
					foreach ($terminals as $terminal)
	    			{
						print "<option value='".$terminal["rowid"]."'>".$terminal["name"]."</option>\n";
	      				$i++;
	    			}
?>
			  		</select>
			  	
			  	
        				<div class="sep"></div>
            			<input type="submit"  name="sbmtConnexion" value=<?php echo $langs->trans("Connection"); ?> />
						<input id="tpvtactil" type="button"  value=<?php echo $langs->trans("Tactil"); ?> />
						<input type="submit" id="Backend" name="sbmtBackend" value=<?php echo $langs->trans("Backend"); ?> />
				</form>		
					
		 </div>
		
		<?php 
			
	    	}
	    	else
	    	{ ?>
	    	<div class="six2 columns">
	    	<form id="frmLogin" method="POST" action="verify.php" class="nice">
	    		<p><?php echo $langs->trans("NotHasTerminal"); ?></p>
	    		<div class="sep"></div>
	    		<input type="submit" id="Backend" name="sbmtBackend" value=<?php echo $langs->trans("Backend"); ?> />
	    	</form>	
			</div>
<?php    	}
?>
		
		
        	<div class="sep"></div>
        	<div class="five columns">
				<div class="second_login">
					<img src="../../commande/tpl/logo.rocafuerte.png" width="245" height="250" alt="Logo" title="" >

 				</div>
 			</div>
        			
        	
			</fieldset>

<?php
		if ($_GET['err'] < 0) 
		{

			echo ('<script type="text/javascript">');
			echo ('	document.getElementById(\'frmLogin\').pwdPassword.focus();');
			echo ('</script>');

		}	 
		else 
		{

			echo ('<script type="text/javascript">');
			echo ('	document.getElementById(\'frmLogin\').username.focus();');
			echo ('</script>');

		}
?>
        	
    </div>	
		
        
		                    
		<!--  </li>-->
<!--END PAGE 1-->   
        
  

	<!--  </ul>-->


</div><!-- container -->

	<script type="text/javascript" src="js/jquery.tweet.js"></script>
	
	
<!-- LATEST TWEETS MODULE -->                       
<script type='text/javascript'>
    jQuery(function($){
        $(".tweet").tweet({
            username: "dolipos",
            join_text: "auto",
            avatar_size: 0,
            count: 20,
            auto_join_text_default: "",
            auto_join_text_ed: "",
            auto_join_text_ing: "",
            auto_join_text_reply: "",
            auto_join_text_url: "",
            loading_text: "Loading Tweets..."
        });
    });
</script> 

</body>

<?php
print '</html>';
?>
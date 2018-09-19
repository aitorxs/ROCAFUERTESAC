
   <?php  // Other attributes

   
      
    $tracking = $_GET['tracking'];          
?>

    <style type="text/css">
        .datagrid table { border-collapse: collapse; text-align: left; width: 100%; } 
        .datagrid {font: normal 12px/150% Arial, Helvetica, sans-serif; background: #fff; overflow: hidden; border: 3px solid #5a6482; -webkit-border-radius: 8px; -moz-border-radius: 8px; border-radius: 8px; }
        .datagrid table td, 
        .datagrid table th { padding: 6px 9px; }
        th {background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #800000), color-stop(1, #80141C) );background:-moz-linear-gradient( center top, #5a6482 5%, #5a6482 100% );filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#5a6482', endColorstr='#5a6482');background-color:#800000; color:#FFFFFF; font-size: 12px; font-weight: bold; border-left: 1px solid #000; } 
        th:first-child { border: none; }
        .datagrid table tbody td { color: #643c14; border-left: 1px solid #f8f8f8;font-size: 12px;font-weight: normal; }
        .datagrid table tbody .odd td { background: #f8f8f8; color: #000; }
        div .ups-form_label{ background: #f8f8f8; color: #000; }
        div .tracking { font-weight:bold; color: #000; }
        div .col-xs-5 { background: #f8f8f8; color: #000; }
         

    </style>
            
            <?php
                require 'simple_html_dom.php';
                
                $html = file_get_html("https://wwwapps.ups.com/WebTracking/processInputRequest?AgreeToTermsAndConditions=yes&loc=en_US&tracknum='".$tracking."'&Requester=trkinppg");
                
                echo " <div class='datagrid'>";
                $tabla = $html->find('div[class=col-xs-6]',0);
                if($tabla!=null){
                echo "<table><tbody><tr><td>";
                echo  "<div class='tracking'>Tracking: ".$tracking."</div>";
                echo  $tabla->find('div[class=ups-group ups-group_condensed]',0);
                echo  $tabla->find('div[class=ups-group ups-group_condensed]',1);
                echo  $tabla->find('div[class=ups-group ups-group_condensed]',2);
                echo "</td>";

                $tabla2 = $html->find('div[class=panel-body]',2);
                echo "<td>";                
                echo  $tabla2->find('div[class=row ups-group]',0);
                echo  $tabla2->find('div[class=row ups-group]',1);
                echo  $tabla2->find('div[class=row ups-group]',2);
                echo  $tabla2->find('div[class=row ups-group]',3);
                echo  $tabla2->find('div[class=row ups-group]',4);  
                echo "</td></tr></tbody></table>";
                echo "</div><br>";

                echo " <div class='datagrid'>";
                $tabla1 = $html->find('div[class=panel panel-default module3]',0);
                echo "<table><tbody>";
                echo  $tabla1->find('div[class=panel-body]',0);
                echo "</tbody></table>";
                echo "</div>";
            }else{

                echo  "<div class='tracking'>Numero de Tracking Incorrecto.</div>";
            }
            ?>





          
 

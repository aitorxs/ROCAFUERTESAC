<?php
/* Copyright (C) 2003-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2015  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2010-2013  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2011-2016  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2013  Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2012-2016  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2012       Cedric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2014       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/commande/card.php
 * \ingroup commande
 * \brief   Page to show customer order
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formorder.class.php';


// Load translation files required by the page


$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('orderid', 'int'));



$object = new Commande($db);
$extrafields = new ExtraFields($db);


// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once


llxHeader1( );
?>

<head>
    <title>IMPRESION PEDIDO</title>
    <link REL="stylesheet" href="../commande/tpl/mediaprintfac.css" type="text/css" media="print" />
    <link REL="stylesheet" href="../commande/tpl/style.css" type="text/css" media="screen" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
    <div class="entete">
        <div class="infos">
            <p class="factdate">
                <?php
                    // Recuperation et affichage de la date et de l'heure
                    $now = dol_now();
                    print "NOTA DE PEDIDO: ".$object->ref; ?>
            </p>
            <p class='vendedor'>Vendedor:<?php echo $object->vendedor; ?></p>
            <p class='almacen'>Almacen: <?php echo $obj->almacen; ?></p>
        </div>
        <p class="hora">
            <?php
            // Recuperation et affichage de la date et de l'heure
            $now = dol_now();
            print "Fecha: ".dol_print_date($object->date_livraison,'daytext');

            ?>
        </p>
    </div>


    <?php   
        $form = new Form($db);
        $formfile = new FormFile($db);
        $formorder = new FormOrder($db);
        $soc = new Societe($db);
        $soc->fetch($object->socid);

    ?>
    <?php echo "<img  src='tpl/jovilse.png' class='logo' >"; ?>
    <div><p class="usuario">Celular: 943928225 - 945362831 </p></div>
    <p class='cliente'>NOMBRE: <?php echo $soc->nom; ?></p> <br>
    <p class='ruc'>RUC: <?php echo $soc->code_client; ?></p> <br>
    <p class='direccion'>DIRECCIÓN:<?php print $soc->address; ?> </p><br>  
    
    <?php 
       print '<div class="cpago"> Condiciones de Pago: ';
        if ($action == 'editconditions') {
            $form->form_conditions_reglement($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->cond_reglement_id, 'cond_reglement_id', 1);
        } else {
            $form->form_conditions_reglement($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->cond_reglement_id, 'none', 1);
            
        }
        print '</div>';

    ?>


<div class="listado">
    <div class="datagrid">
        <div class="datagrid">
        <table class="">
            <tr>
                <td class="item"><b>ITEM</td>
                <td class="descripcion"><b>DESCRIPCIÓN</td>
                <td class="unidad"><b>UNIDAD</td>
                <td class="cantidad"><b>CANTIDAD</td>
                <td class="precio"><b>P.UNIT</td>
                <td class="pventa"><b>V.VENTA</td>
            </tr>
        </table>
        </div>
        <table class="">
            <?php
                $ret = $object->printObjectLinespedido($soc, $lineid, 1);
            ?>
        </table>
        <div class="datagrid">
            <table>
                <tr>
                    
                    <?php
                    $numer=$langs->trans($object->total_ttc, 1, '', 1, - 1, - 1 );
                    $str = $langs->getLabelFromNumber($numer,0|1);
                    $str = strtoupper($str);
                    echo '<td class="textwords">SON:  '.$str.'</td>';
                    echo '<td class="total" >TOTAL: S/ ' .number_format($object->total_ttc,2,'.',',')."</td>";
                ?>
                </tr>
            </table>
        </div>



    </div>
</div>
  
         
    


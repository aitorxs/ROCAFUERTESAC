

<!-- listado de producto para factura obedic....................................... -->
<?php $coldisplay=0; ?>
<!-- BEGIN PHP TEMPLATE objectline_view.tpl.php -->
<tr >
    <?php if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>

    <?php } ?>
    <td class="item"><?php $coldisplay++; ?>
    <?php if ((($line->info_bits & 2) != 2) && $line->special_code != 3) {
            // I comment this because it shows info even when not required
            // for example always visible on invoice but must be visible only if stock module on and stock decrease option is on invoice validation and status is not validated
            // must also not be output for most entities (proposal, intervention, ...)
            //if($line->qty > $line->stock) print img_picto($langs->trans("StockTooLow"),"warning", 'style="vertical-align: bottom;"')." ";
            echo $line->item;

        } else  ?>
    </td>
    <td class="descripcion">  
        <?php if (($line->info_bits & 2) == 2) { ?>

        <?php

    }
    else
    {
        if ($line->fk_product > 0) {
			echo $form->textwithtooltip($text,(!empty($line->fk_parent_line)?:''));


            echo get_date_range($line->date_start, $line->date_end);

            //echo $line->description;  sirve pára implementar serie numero en la factura

        } else {

             if ($type==1) $text = img_object($langs->trans('Service'),'service');
            else $text = img_object($langs->trans('Product'),'product');

            if (! empty($line->label)) {
                $text.= ' <strong>'.$line->label.'</strong>';
                echo $form->textwithtooltip($text,dol_htmlentitiesbr($line->description),3,'','',$i,0,(!empty($line->fk_parent_line)?img_picto('', 'rightarrow'):''));


            } else {
                if (! empty($line->fk_parent_line)) echo img_picto('', 'rightarrow');

                echo dol_htmlentitiesbr($line->description);//agrega la descripcion del servicio
            }

            // Show range
            echo get_date_range($line->date_start,$line->date_end);
        }
    }
    ?>
    
    

    </td>
    <td class="unidad">Und.</td>

    <td class="cantidad"><?php $coldisplay++; ?>
    <?php if ((($line->info_bits & 2) != 2) && $line->special_code != 3) {
            // I comment this because it shows info even when not required
            // for example always visible on invoice but must be visible only if stock module on and stock decrease option is on invoice validation and status is not validated
            // must also not be output for most entities (proposal, intervention, ...)
            //if($line->qty > $line->stock) print img_picto($langs->trans("StockTooLow"),"warning", 'style="vertical-align: bottom;"')." ";
            echo $line->qty;
        } else  ?>
    </td>
    <!-- <td class="precio"><?php $coldisplay++; ?><?php  $tth=$line->multicurrency_total_ttc/$line->qty; printf("%.2f",intval(($tth*100))/100); ?>
    </td> -->
    <td class="precio"><?php $coldisplay++; ?><?php echo price(round($line->subprice*1.18,2)); ?></td>
        
    <?php if ($conf->global->MAIN_FEATURES_LEVEL > 1) { ?>   

    $monto=$line->subprice*$line->;

    <?php if (! empty($conf->global->DISPLAY_MARGIN_RATES) && $user->rights->margins->liretous) {?>
        <?php
  }
  if (! empty($conf->global->DISPLAY_MARK_RATES) && $user->rights->margins->liretous) {?>

  <?php } } ?>

    <?php if ($line->special_code == 3) { ?>
    <td align="right" class="pventa"><?php $coldisplay++; ?><?php echo $langs->trans('Option'); ?></td>
    <?php } else { ?>
    
    <!--  <td align="right" class="total"><?php $coldisplay++; ?><?php  $total_t=$line->total_ttc; //echo price(price2num($total_t,'MT')); 
    //echo number_format($total_t,2,'.',','); ?></td> -->
  
  <td align="right" class="pventa"><?php $coldisplay++; ?><?php  $total_t=$line->multicurrency_total_ttc; echo price(price2num($total_t,'MT'));  ?></td>
  
     <!-- <td align="right" class="total"><?php $coldisplay++; ?><?php //echo price(price2num($line->total_ht*1.18,'MT')); ?></td> -->
    <?php } ?>
</tr>
<!-- listado de producto para boleta obedic....................................... -->


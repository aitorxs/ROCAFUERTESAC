<?php
/* Copyright (C) 2010-2013	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 *
 * $type, $text, $description, $line
 */

$usemargins=0;
if (! empty($conf->margin->enabled) && ! empty($object->element) && in_array($object->element,array('facture','propal','commande'))) $usemargins=1;

global $forceall, $senderissupplier, $inputalsopricewithtax;
if (empty($dateSelector)) $dateSelector=0;
if (empty($forceall)) $forceall=0;
if (empty($senderissupplier)) $senderissupplier=0;
if (empty($inputalsopricewithtax)) $inputalsopricewithtax=0;
$element = $object->element;
$line = $parameters['line'];

?>
<?php $coldisplay=0; ?>
<!-- BEGIN PHP TEMPLATE objectline_view.tpl.php -->
<tr <?php echo 'id="row-'.$line->id.'" '.$bcdd[$parameters['var']]; ?>>
	<?php if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
	<td align="center"><?php $coldisplay++; ?><?php echo ($i+1); ?></td>
	<?php } ?>
	<td><?php $coldisplay++; ?><div id="line_<?php echo $line->rowid; ?>"></div>
	<?php 
	
	$text = img_object($langs->trans('Header'),'header@massorders');

	if (! empty($line->description)) {
		echo ' <strong>'.$line->description.'</strong>';
	} 

	?>
	</td>

	<td align="right" class="nowrap"><?php $coldisplay++; ?><?php echo '&nbsp;'; ?></td>

	<td align="right" class="nowrap"><?php $coldisplay++; ?><?php echo '&nbsp;'; ?></td>

	<?php if ($inputalsopricewithtax) { ?>
	<td align="right" class="nowrap"><?php $coldisplay++; ?>&nbsp;</td>
	<?php } ?>

	<td align="right" class="nowrap"><?php $coldisplay++; ?>
	<?php  echo '&nbsp;';	?>
	</td>

	<td><?php $coldisplay++; ?>&nbsp;</td>
	<?php 

  if (! empty($conf->margin->enabled) && empty($user->socid)) {
	
  ?>
  	<td align="right" class="nowrap margininfos"><?php $coldisplay++; ?><?php echo '&nbsp;'; ?></td>
  	<?php if (! empty($conf->global->DISPLAY_MARGIN_RATES) && $user->rights->margins->liretous) {?>
  	  <td align="right" class="nowrap margininfos"><?php $coldisplay++; ?><?php echo '&nbsp;'; ?></td>
  	<?php
  }
  if (! empty($conf->global->DISPLAY_MARK_RATES) && $user->rights->margins->liretous) {?>
  	  <td align="right" class="nowrap margininfos"><?php $coldisplay++; ?><?php echo '&nbsp;'; ?></td>
  <?php } } ?>

	
	<td align="right" class="nowrap"><?php $coldisplay++; ?><?php echo '&nbsp;'; ?></td>
	
	<?php if ($object->statut == 0  && $user->rights->$element->creer) { ?>
	<td align="center"><?php $coldisplay++; echo '&nbsp;'; ?>
		
	</td>

	<td align="center"><?php $coldisplay++; echo '&nbsp;';?>
		
	</td>

	<td colspan="3"><?php $coldisplay=$coldisplay+3; ?></td>
<?php } ?>

<?php
//Line extrafield
if (!empty($extrafieldsline))
{
	print $line->showOptionals($extrafieldsline,'view',array('style'=>$bcdd[$var],'colspan'=>$coldisplay));
}
?>

</tr>
<!-- END PHP TEMPLATE objectline_view.tpl.php -->
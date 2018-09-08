<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011 	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012-2017 Ferran Marcet        <fmarcet@2byte.es>
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
 */

/**
 *	\file       htdocs/cashdesk/admin/cashdesk.php
 *	\ingroup    cashdesk
 *	\brief      Setup page for cashdesk module
 */

$res = @include("../../main.inc.php");	// For root directory
if (!$res) {
	$res = @include("../../../main.inc.php");
}                // For "custom" directory

require_once(DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php");
dol_include_once("/pos/class/ticket.class.php");
dol_include_once("/pos/backend/lib/pos.lib.php");

global $user, $langs, $db, $conf, $bc, $mysoc;

// Security check
if (!$user->admin) {
	accessforbidden();
}

$langs->load("admin");
$langs->load("main");
$langs->load("pos@pos");


/*
 * Actions
 */

if (GETPOST('action', 'string') == 'updateMask') {
	$maskconstticket = GETPOST('maskconstticket');
	$maskconstticketcredit = GETPOST('maskconstticketcredit');
	$maskticket = GETPOST('maskticket');
	$maskcredit = GETPOST('maskcredit');
	$maskconstfacsim = GETPOST('maskconstfacsim');
	$maskconstfacsimcredit = GETPOST('maskconstfacsimcredit');
	$maskfacsim = GETPOST('maskfacsim');
	$maskfacsimcredit = GETPOST('maskfacsimcredit');
	$maskconstclosecash = GETPOST('maskconstclosecash');
	$maskconstclosecasharq = GETPOST('maskconstclosecasharq');
	$maskclosecash = GETPOST('maskclosecash');
	$maskclosecasharq = GETPOST('maskclosecasharq');
	if ($maskconstticket) {
		dolibarr_set_const($db, $maskconstticket, $maskticket, 'chaine', 0, '', $conf->entity);
	}
	if ($maskconstticketcredit) {
		dolibarr_set_const($db, $maskconstticketcredit, $maskcredit, 'chaine', 0, '', $conf->entity);
	}
	if ($maskconstfacsim) {
		dolibarr_set_const($db, $maskconstfacsim, $maskfacsim, 'chaine', 0, '', $conf->entity);
	}
	if ($maskconstfacsimcredit) {
		dolibarr_set_const($db, $maskconstfacsimcredit, $maskfacsimcredit, 'chaine', 0, '', $conf->entity);
	}
	if ($maskconstclosecash) {
		dolibarr_set_const($db, $maskconstclosecash, $maskclosecash, 'chaine', 0, '', $conf->entity);
	}
	if ($maskconstclosecasharq) {
		dolibarr_set_const($db, $maskconstclosecasharq, $maskclosecasharq, 'chaine', 0, '', $conf->entity);
	}
}

if (GETPOST("action") == 'set') {
    $error = 0;
	if (GETPOST("POS_FACTURE", "int") == 0 && $conf->global->POS_TICKET == 0) {
		setEventMessage($langs->trans("ErrorConfig"), "errors");
	} else {
		$db->begin();
		$res = dolibarr_set_const($db, "POS_SERVICES", GETPOST("POS_SERVICES"), 'chaine', 0, '', $conf->entity);

		if (!$res > 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "POS_PLACES", GETPOST("POS_PLACES"), 'chaine', 0, '', $conf->entity);

		if (!$res > 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "POS_STOCK", GETPOST("POS_STOCK"), 'chaine', 0, '', $conf->entity);

		if (!$res > 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "POS_USER_TERMINAL", GETPOST("POS_USER_TERMINAL"), 'chaine', 0, '',
				$conf->entity);

		if (!$res > 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "POS_COMERCIAL", GETPOST("POS_COMERCIAL"), 'chaine', 0, '', $conf->entity);

		if (!$res > 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "POS_MAX_TTC", GETPOST("POS_MAX_TTC"), 'chaine', 0, '', $conf->entity);

		if (!$res > 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "POS_PRINT", GETPOST("POS_PRINT"), 'chaine', 0, '', $conf->entity);

		if (!$res > 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "POS_MAIL", GETPOST("POS_MAIL"), 'chaine', 0, '', $conf->entity);

		if (!$res > 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "POS_FACTURE", GETPOST("POS_FACTURE"), 'chaine', 0, '', $conf->entity);

		if (!$res > 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "REWARDS_POS", GETPOST("REWARDS_POS"), 'chaine', 0, '', $conf->entity);

        if (!$res > 0) {
            $error++;
        }
        $res = dolibarr_set_const($db, "NUMBERSERIES_POS", GETPOST("NUMBERSERIES_POS"), 'chaine', 0, '', $conf->entity);

		if (!$res > 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "POS_CHAT", GETPOST("POS_CHAT"), 'chaine', 0, '', $conf->entity);

		if (!$res > 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "POS_INV", GETPOST("POS_INV"), 'chaine', 0, '', $conf->entity);

		if (!$res > 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "POS_TICKET_TTC", GETPOST("POS_TICKET_TTC"), 'chaine', 0, '', $conf->entity);

		if (!$res > 0) {
			$error++;
		}
        $res = dolibarr_set_const($db, "POS_PRICE_MIN", GETPOST("POS_PRICE_MIN"), 'chaine', 0, '', $conf->entity);

        if (!$res > 0) {
            $error++;
        }
		$res = dolibarr_set_const($db, "POS_CLOSE_WIN", GETPOST("POS_CLOSE_WIN"), 'chaine', 0, '', $conf->entity);

		if (!$res > 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "POS_PRINT_MODE", GETPOST("POS_PRINT_MODE"), 'chaine', 0, '', $conf->entity);

		if (!$res > 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "POS_OPEN_DRAWER", GETPOST("POS_OPEN_DRAWER"), 'chaine', 0, '',
				$conf->entity);

		if (!$res > 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "POS_BATCH_SERIE", GETPOST("POS_BATCH_SERIE"), 'chaine', 0, '', $conf->entity);

		if (!$res > 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "POS_PREDEF_MSG", GETPOST("POS_PREDEF_MSG"), 'chaine', 0, '', $conf->entity);

		if (!$res > 0) {
			$error++;
		}
		if (!$error) {
			$db->commit();
			setEventMessage($langs->trans("SetupSaved"));
		} else {
			$db->rollback();
			setEventMessage($langs->trans("Error"), "errors");
		}

	}
}

if ($_GET["action"] == 'setmod') {
	dolibarr_set_const($db, "TICKET_ADDON", $_GET["value"], 'chaine', 0, '', $conf->entity);
}
if ($_GET["action"] == 'setmodfacsim') {
	dolibarr_set_const($db, "FACSIM_ADDON", $_GET["value"], 'chaine', 0, '', $conf->entity);
}
if ($_GET["action"] == 'setmodclosecash') {
	dolibarr_set_const($db, "CLOSECASH_ADDON", $_GET["value"], 'chaine', 0, '', $conf->entity);
}

function selectBatch($htmlname, $value='', $option=0, $disabled=false, $useempty='')
{
	global $langs;

	$no="no"; $yes="yes";
	if ($option)
	{
		$no="0";
		$yes="1";
	}

	$disabled = ($disabled ? ' disabled' : '');

	$resultyesno = '<select class="flat width175" id="'.$htmlname.'" name="'.$htmlname.'"'.$disabled.'>'."\n";
	if ($useempty) $resultyesno .= '<option value="-1"'.(($value < 0)?' selected':'').'>&nbsp;</option>'."\n";
	if (("$value" == 'no') || ($value == 0))
	{
		$resultyesno .= '<option value="'.$no.'" selected>'.$langs->trans("BatchYesBut").'</option>'."\n";
		$resultyesno .= '<option value="'.$yes.'">'.$langs->trans("BatchYes").'</option>'."\n";
	}
	else
	{
		$selected=(($useempty && $value != '1' && $value != 'yes')?'':' selected');
		$resultyesno .= '<option value="'.$no.'">'.$langs->trans("BatchYesBut").'</option>'."\n";
		$resultyesno .= '<option value="'.$yes.'"'.$selected.'>'.$langs->trans("BatchYes").'</option>'."\n";

	}
	$resultyesno .= '</select>'."\n";
	return $resultyesno;
}

/*
 * View
 */
$helpurl = 'EN:Module_DoliPos|FR:Module_DoliPos_FR|ES:M&oacute;dulo_DoliPos';
llxHeader('', $langs->trans("POSSetup"), $helpurl);

$html = new Form($db);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("POSSetup"),$linkback,'title_setup');

$head = posadmin_prepare_head();

dol_fiche_head($head, 'configuration', $langs->trans("POS"), 0, 'pos@pos');

if ($conf->global->POS_TICKET == 1) {
	print load_fiche_titre($langs->trans("TicketsNumberingModule"));

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Name") . '</td>';
	print '<td>' . $langs->trans("Description") . '</td>';
	print '<td nowrap>' . $langs->trans("Example") . '</td>';
	print '<td align="center" width="60">' . $langs->trans("Status") . '</td>';
	print '<td align="center" width="16">' . $langs->trans("Infos") . '</td>';
	print '</tr>' . "\n";

	clearstatcache();

	$var = true;
	foreach ($conf->file->dol_document_root as $dirroot) {
		$dir = $dirroot . "/pos/backend/numerotation/";

		if (is_dir($dir)) {
			$handle = opendir($dir);
			if (is_resource($handle)) {
				while (($file = readdir($handle)) !== false) {
					if (!is_dir($dir . $file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
						$filebis = $file;
						$classname = preg_replace('/\.php$/', '', $file);
						// For compatibility
						if (!is_file($dir . $filebis)) {
							$filebis = $file . "/" . $file . ".modules.php";
							$classname = "mod_ticket_" . $file;
						}
						//print "x".$dir."-".$filebis."-".$classname;
						if (!class_exists($classname) && is_readable($dir . $filebis) && (preg_match('/mod_/',
												$filebis) || preg_match('/mod_/', $classname)) && substr($filebis,
										dol_strlen($filebis) - 3, 3) == 'php'
						) {
							// Chargement de la classe de numerotation
							require_once($dir . $filebis);

							$module = new $classname($db);

							// Show modules according to features level
							if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) {
								continue;
							}
							if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) {
								continue;
							}

							if ($module->isEnabled()) {
								$var = !$var;
								print '<tr ' . $bc[$var] . '><td width="100">';
								echo preg_replace('/mod_ticket_/', '', preg_replace('/\.php$/', '', $file));
								print "</td><td>\n";

								print $module->info();

								print '</td>';

								// Show example of numbering module
								print '<td nowrap="nowrap">';
								$tmp = $module->getExample();
								if (preg_match('/^Error/', $tmp)) {
									print $langs->trans($tmp);
								} else {
									print $tmp;
								}
								print '</td>' . "\n";

								print '<td align="center">';
								//print "> ".$conf->global->FACTURE_ADDON." - ".$file;
								if ($conf->global->TICKET_ADDON == $file || $conf->global->TICKET_ADDON . '.php' == $file) {
									print img_picto($langs->trans("Activated"), 'on');
								} else {
									print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setmod&amp;value=' . preg_replace('/\.php$/',
													'', $file) . '">' . img_picto($langs->trans("Disabled"),
													'off') . '</a>';
								}
								print '</td>';

								$facture = new Ticket($db);
								// $facture->initAsSpecimen();

								// Example for standard invoice
								$htmltooltip = '';
								$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
								$facture->type = 0;
								$nextval = $module->getNextValue($mysoc, $facture);
								if ("$nextval" != $langs->trans("NotAvailable"))    // Keep " on nextval
								{
									$htmltooltip .= $langs->trans("NextValueForTickets") . ': ';
									if ($nextval) {
										$htmltooltip .= $nextval . '<br>';
									} else {
										$htmltooltip .= $langs->trans($module->error) . '<br>';
									}
								}


								print '<td align="center">';
								print $html->textwithpicto('', $htmltooltip, 1, 0);

								if ($conf->global->TICKET_ADDON . '.php' == $file)  // If module is the one used, we show existing errors
								{
									if (!empty($module->error)) {
										setEventMessage($module->error, "errors");
									}
								}

								print '</td>';

								print "</tr>\n";

							}
						}
					}
				}
				closedir($handle);
			}
		}
	}

	print '</table>';

	print "<br>";
}
if ($conf->global->POS_FACTURE == 1) {
	print load_fiche_titre($langs->trans("FacsimNumberingModule"));

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Name") . '</td>';
	print '<td>' . $langs->trans("Description") . '</td>';
	print '<td nowrap>' . $langs->trans("Example") . '</td>';
	print '<td align="center" width="60">' . $langs->trans("Status") . '</td>';
	print '<td align="center" width="16">' . $langs->trans("Infos") . '</td>';
	print '</tr>' . "\n";

	clearstatcache();

	$var = true;
	foreach ($conf->file->dol_document_root as $dirroot) {
		$dir = $dirroot . "/pos/backend/numerotation/numerotation_facsim/";

		if (is_dir($dir)) {
			$handle = opendir($dir);
			if (is_resource($handle)) {
				while (($file = readdir($handle)) !== false) {
					if (!is_dir($dir . $file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
						$filebis = $file;
						$classname = preg_replace('/\.php$/', '', $file);
						// For compatibility
						if (!is_file($dir . $filebis)) {
							$filebis = $file . "/" . $file . ".modules.php";
							$classname = "mod_facsim_" . $file;
						}
						//print "x".$dir."-".$filebis."-".$classname;
						if (!class_exists($classname) && is_readable($dir . $filebis) && (preg_match('/mod_/',
												$filebis) || preg_match('/mod_/', $classname)) && substr($filebis,
										dol_strlen($filebis) - 3, 3) == 'php'
						) {
							// Chargement de la classe de numerotation
							require_once($dir . $filebis);

							$module = new $classname($db);

							// Show modules according to features level
							if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) {
								continue;
							}
							if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) {
								continue;
							}

							if ($module->isEnabled()) {
								$var = !$var;
								print '<tr ' . $bc[$var] . '><td width="100">';
								echo preg_replace('/mod_facsim_/', '', preg_replace('/\.php$/', '', $file));
								print "</td><td>\n";

								print $module->info();

								print '</td>';

								// Show example of numbering module
								print '<td nowrap="nowrap">';
								$tmp = $module->getExample();
								if (preg_match('/^Error/', $tmp)) {
									print $langs->trans($tmp);
								} else {
									print $tmp;
								}
								print '</td>' . "\n";

								print '<td align="center">';
								//print "> ".$conf->global->FACTURE_ADDON." - ".$file;
								if ($conf->global->FACSIM_ADDON == $file || $conf->global->FACSIM_ADDON . '.php' == $file) {
									print img_picto($langs->trans("Activated"), 'on');
								} else {
									print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setmodfacsim&amp;value=' . preg_replace('/\.php$/',
													'', $file) . '">' . img_picto($langs->trans("Disabled"),
													'off') . '</a>';
								}
								print '</td>';

								$facture = new Ticket($db);
								//$facture->initAsSpecimen();

								// Example for standard invoice
								$htmltooltip = '';
								$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
								$facture->type = 0;
								$nextval = $module->getNextValue($mysoc, $facture);
								if ("$nextval" != $langs->trans("NotAvailable"))    // Keep " on nextval
								{
									$htmltooltip .= $langs->trans("NextValueForTickets") . ': ';
									if ($nextval) {
										$htmltooltip .= $nextval . '<br>';
									} else {
										$htmltooltip .= $langs->trans($module->error) . '<br>';
									}
								}


								print '<td align="center">';
								print $html->textwithpicto('', $htmltooltip, 1, 0);

								if ($conf->global->FACSIM_ADDON . '.php' == $file)  // If module is the one used, we show existing errors
								{
									if (!empty($module->error)) {
										setEventMessage($module->error, "errors");
									}
								}

								print '</td>';

								print "</tr>\n";

							}
						}
					}
				}
				closedir($handle);
			}
		}
	}

	print '</table>';

	print "<br>";
}


print load_fiche_titre($langs->trans("CloseCashNumberingModule"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td nowrap>' . $langs->trans("Example") . '</td>';
print '<td align="center" width="60">' . $langs->trans("Status") . '</td>';
print '<td align="center" width="16">' . $langs->trans("Infos") . '</td>';
print '</tr>' . "\n";

clearstatcache();

$var = true;
foreach ($conf->file->dol_document_root as $dirroot) {
	$dir = $dirroot . "/pos/backend/numerotation/numerotation_closecash/";

	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (!is_dir($dir . $file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
					$filebis = $file;
					$classname = preg_replace('/\.php$/', '', $file);
					// For compatibility
					if (!is_file($dir . $filebis)) {
						$filebis = $file . "/" . $file . ".modules.php";
						$classname = "mod_closecash_" . $file;
					}
					//print "x".$dir."-".$filebis."-".$classname;
					if (!class_exists($classname) && is_readable($dir . $filebis) && (preg_match('/mod_/',
											$filebis) || preg_match('/mod_/', $classname)) && substr($filebis,
									dol_strlen($filebis) - 3, 3) == 'php'
					) {
						// Chargement de la classe de numerotation
						require_once($dir . $filebis);

						$module = new $classname($db);

						// Show modules according to features level
						if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) {
							continue;
						}
						if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) {
							continue;
						}

						if ($module->isEnabled()) {
							$var = !$var;
							print '<tr ' . $bc[$var] . '><td width="100">';
							echo preg_replace('/mod_closecash_/', '', preg_replace('/\.php$/', '', $file));
							print "</td><td>\n";

							print $module->info();

							print '</td>';

							// Show example of numbering module
							print '<td nowrap="nowrap">';
							$tmp = $module->getExample();
							if (preg_match('/^Error/', $tmp)) {
								print $langs->trans($tmp);
							} else {
								print $tmp;
							}
							print '</td>' . "\n";

							print '<td align="center">';
							//print "> ".$conf->global->FACTURE_ADDON." - ".$file;
							if ($conf->global->CLOSECASH_ADDON == $file || $conf->global->CLOSECASH_ADDON . '.php' == $file) {
								print img_picto($langs->trans("Activated"), 'on');
							} else {
								print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setmodclosecash&amp;value=' . preg_replace('/\.php$/',
												'', $file) . '">' . img_picto($langs->trans("Disabled"),
												'off') . '</a>';
							}
							print '</td>';

							$facture = new Ticket($db);
							$facture->initAsSpecimen();

							// Example for standard invoice
							$htmltooltip = '';
							$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
							$facture->type_control = 1;
							$nextval = $module->getNextValue($mysoc, $facture);
							if ("$nextval" != $langs->trans("NotAvailable"))    // Keep " on nextval
							{
								$htmltooltip .= $langs->trans("NextValueForTickets") . ': ';
								if ($nextval) {
									$htmltooltip .= $nextval . '<br>';
								} else {
									$htmltooltip .= $langs->trans($module->error) . '<br>';
								}
							}


							print '<td align="center">';
							print $html->textwithpicto('', $htmltooltip, 1, 0);

							if ($conf->global->CLOSECASH_ADDON . '.php' == $file)  // If module is the one used, we show existing errors
							{
								if (!empty($module->error)) {
									setEventMessage($module->error, "errors");
								}
							}

							print '</td>';

							print "</tr>\n";

						}
					}
				}
			}
			closedir($handle);
		}
	}
}

print '</table>';

print "<br>";

print load_fiche_titre($langs->trans("OtherOptions"));

// Mode
$var = true;
print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td><td>' . $langs->trans("Value") . '</td>';
print "</tr>\n";

if ($conf->global->POS_TICKET) {

	/*$var=! $var;
	print '<tr '.$bc[$var].'><td>';
	print $langs->trans("POSUseTickets");
	print '<td colspan="2">';

	print $html->selectyesno("POS_TICKET",$conf->global->POS_TICKET,1,$disable);

	print "</td></tr>\n";*/

	$var = !$var;
	print '<tr ' . $bc[$var] . '><td>';
	print $langs->trans("POSFactureTicket");
	print '<td colspan="2">';

	print $html->selectyesno("POS_FACTURE", $conf->global->POS_FACTURE, 1);

	print "</td></tr>\n";
} else {
	print '<input type="hidden" name="POS_FACTURE" value="1">';
}

$var = !$var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("POSMaxTTC") . '</td>';
print '<td><input type="text" class="flat" name="POS_MAX_TTC" value="' . ($_POST["POS_MAX_TTC"] ? $_POST["POS_MAX_TTC"] : $conf->global->POS_MAX_TTC) . '" size="8"> ' . $langs->trans("Currency" . $conf->currency) . '</td>';
print '</tr>';

if ($conf->service->enabled) {
	$var = !$var;
	print '<tr ' . $bc[$var] . '><td>';
	print $langs->trans("POSShowServices");
	print '<td colspan="2">';;
	print $html->selectyesno("POS_SERVICES", $conf->global->POS_SERVICES, 1);
	print "</td></tr>\n";
}

$var = !$var;
print '<tr ' . $bc[$var] . '><td>';
print $langs->trans("POSShowPlaces");
print '<td colspan="2">';;
print $html->selectyesno("POS_PLACES", $conf->global->POS_PLACES, 1);
print "</td></tr>\n";

$var = !$var;
print '<tr ' . $bc[$var] . '><td>';
print $langs->trans("POSSellStock");
print '<td colspan="2">';
if (!empty($conf->global->STOCK_MUST_BE_ENOUGH_FOR_INVOICE)) {
	print $html->selectyesno("POS_STOCK", 0, 1, true);
	print $html->textwithpicto('', $langs->trans('StockEnoughInvoice'));
	$res = dolibarr_set_const($db, "POS_STOCK", 0, 'chaine', 0, '', $conf->entity);
} else {
	print $html->selectyesno("POS_STOCK", $conf->global->POS_STOCK, 1);
}
print "</td></tr>\n";

$var = !$var;
print '<tr ' . $bc[$var] . '><td>';
print $langs->trans("POSUserTerminal");
print '<td colspan="2">';;
print $html->selectyesno("POS_USER_TERMINAL", $conf->global->POS_USER_TERMINAL, 1);
print "</td></tr>\n";

$var = !$var;
print '<tr ' . $bc[$var] . '><td>';
print $langs->trans("POSComercial");
print '<td colspan="2">';;
print $html->selectyesno("POS_COMERCIAL", $conf->global->POS_COMERCIAL, 1);
print "</td></tr>\n";

$var = !$var;
print '<tr ' . $bc[$var] . '><td>';
print $langs->trans("POSPrintTicket");
print '<td colspan="2">';;
print $html->selectyesno("POS_PRINT", $conf->global->POS_PRINT, 1);
print "</td></tr>\n";

$var = !$var;
print '<tr ' . $bc[$var] . '><td>';
print $langs->trans("POSMailTicket");
print '<td colspan="2">';
print $html->selectyesno("POS_MAIL", $conf->global->POS_MAIL, 1);
print "</td></tr>\n";

$var = !$var;
print '<tr ' . $bc[$var] . '><td>';
print $langs->trans("POSChat");
print '<td colspan="2">';
print $html->selectyesno("POS_CHAT", $conf->global->POS_CHAT, 1);
print "</td></tr>\n";

$var = !$var;
print '<tr ' . $bc[$var] . '><td>';
print $langs->trans("POSRewards");
if (!empty($conf->rewards->enabled)) {
	print '<td colspan="2">';
	print $html->selectyesno("REWARDS_POS", $conf->global->REWARDS_POS, 1);
} else {
	print '<td colspan="2">' . $langs->trans("NoRewardsInstalled") . ' ' . $langs->trans("GetRewards",
					"https://www.dolistore.com/es/buscar?controller=search&orderby=position&orderway=desc&search_query=2rewards&submit_search=") . '</td>';
}
print "</td></tr>\n";

$var = !$var;
print '<tr ' . $bc[$var] . '><td>';
print $langs->trans("POSNumberseries");
if (!empty($conf->numberseries->enabled)) {
    print '<td colspan="2">';
    print $html->selectyesno("NUMBERSERIES_POS", $conf->global->NUMBERSERIES_POS, 1);
} else {
    print '<td colspan="2">' . $langs->trans("NoNumberseriesInstalled") . ' ' . $langs->trans("GetNumberseries",
            "https://www.dolistore.com/es/buscar?controller=search&orderby=position&orderway=desc&search_query=2Series&submit_search=") . '</td>';
}
print "</td></tr>\n";

$var = !$var;
print '<tr ' . $bc[$var] . '><td>';
print $langs->trans("InvertDisplay");
print '<td colspan="2">';
print $html->selectyesno("POS_INV", $conf->global->POS_INV, 1);
print "</td></tr>\n";

$var = !$var;
print '<tr ' . $bc[$var] . '><td>';
print $langs->trans("ShowTicketTtc");
print '<td colspan="2">';
print $html->selectyesno("POS_TICKET_TTC", $conf->global->POS_TICKET_TTC, 1);
print "</td></tr>\n";

$var = !$var;
print '<tr ' . $bc[$var] . '><td>';
print $langs->trans("UsePriceMin");
print '<td colspan="2">';
print $html->selectyesno("POS_PRICE_MIN", $conf->global->POS_TICKET_MIN, 1);
print "</td></tr>\n";

$var = !$var;
print '<tr ' . $bc[$var] . '><td>';
print $langs->trans("AutomaticCloseWindow");
print '<td colspan="2">';
print $html->selectyesno("POS_CLOSE_WIN", $conf->global->POS_CLOSE_WIN, 1);
print "</td></tr>\n";

$var = !$var;
print '<tr ' . $bc[$var] . '><td>';
print $langs->trans("PosPrintMode");
print '<td colspan="2">';
print $html->selectyesno("POS_PRINT_MODE", $conf->global->POS_PRINT_MODE, 1);
print "</td></tr>\n";

if ($conf->global->POS_PRINT_MODE == 1) {
	$var = !$var;
	print '<tr ' . $bc[$var] . '><td>';
	print $langs->trans("PosOpenDrawer");
	print '<td colspan="2">';
	print $html->selectyesno("POS_OPEN_DRAWER", $conf->global->POS_OPEN_DRAWER, 1);
	print "</td></tr>\n";
}

$var = !$var;
print '<tr ' . $bc[$var] . '><td>';
print $langs->trans("PosBatchSerie");
if (!empty($conf->productbatch->enabled)) {
	print '<td colspan="2">';
	print selectBatch("POS_BATCH_SERIE", $conf->global->POS_BATCH_SERIE, 1);
} else {
	print '<td colspan="2">' . $langs->trans("NoBatchseriesInstalled") . '</td>';
}
print "</td></tr>\n";

$var = !$var;
print '<tr ' . $bc[$var] . '><td colspan="2">';
print $langs->trans("PredefMsg") . '<br>';
print '<textarea name="POS_PREDEF_MSG" class="flat" cols="120">' . $conf->global->POS_PREDEF_MSG . '</textarea>';
print '</td></tr>';

print '</table>';
print '<br>';

print '<div style="text-align: center" ><input type="submit" class="button" value="' . $langs->trans("Save") . '"></div>';

print "</form>";

dol_htmloutput_events();

$db->close();

llxFooter();
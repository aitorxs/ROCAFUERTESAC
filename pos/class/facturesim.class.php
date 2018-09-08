<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio   <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier        <benoit.mortier@opensides.be>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2010-2012 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2012      Marcos García         <marcosgdf@gmail.com>
 * Copyright (C) 2012-2013 Ferran Marcet         <fmarcet@2byte.es>
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
 *	\file       htdocs/compta/facture/class/facture.class.php
 *	\ingroup    facture
 *	\brief      File of class to manage invoices
 */

include_once DOL_DOCUMENT_ROOT.'/core/class/commoninvoice.class.php';
include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT .'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT .'/societe/class/client.class.php';


/**
 *	Class to manage invoices
 */
class Facturesim extends Facture
{

    public $fk_cash;

    /**
     *      Return next reference of ticket not already used (or last reference)
     *      according to numbering module defined into constant FACSIM_ADDON
     * @param       object soc                   objet company
     * @param     string mode                    'next' for next value or 'last' for last value
     * @return    string                  free ref or last ref
     */
    function getNextNumRef($soc, $mode = 'next')
    {
        global $conf, $langs;
        $langs->load("bills");

        // Clean parameters (if not defined or using deprecated value)
        if (empty($conf->global->FACSIM_ADDON)) $conf->global->FACSIM_ADDON = 'mod_facsim_alcoy';
        else if ($conf->global->FACSIM_ADDON == 'alcoy') $conf->global->FACSIM_ADDON = 'mod_facsim_alcoy';

        $mybool = false;

        $file = $conf->global->FACSIM_ADDON . ".php";
        $classname = $conf->global->FACSIM_ADDON;
        // Include file with class
        foreach ($conf->file->dol_document_root as $dirroot) {
            $dir = $dirroot . "/pos/backend/numerotation/numerotation_facsim/";
            // Load file with numbering class (if found)
            $mybool |= @include_once($dir . $file);
        }

        // For compatibility
        if (!$mybool) {
            $file = $conf->global->FACSIM_ADDON . "/" . $conf->global->FACSIM_ADDON . ".modules.php";
            $classname = "mod_facsim_" . $conf->global->FACSIM_ADDON;
            // Include file with class
            foreach ($conf->file->dol_document_root as $dirroot) {
                $dir = $dirroot . "/pos/backend/numerotation/numerotation_facsim/";
                // Load file with numbering class (if found)
                $mybool |= @include_once($dir . $file);
            }
        }
        //print "xx".$mybool.$dir.$file."-".$classname;

        if (!$mybool) {
            dol_print_error('', "Failed to include file " . $file);
            return '';
        }

        $obj = new $classname();

        $numref = $obj->getNumRef($soc, $this, $mode);

        if ($numref != "") {
            return $numref;
        } else {
            //dol_print_error($db,"Ticket::getNextNumRef ".$obj->error);
            return false;
        }
    }
}

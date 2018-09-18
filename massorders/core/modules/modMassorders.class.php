<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2012	   Andreu Bisquerra Gaya<jove@bisquerra.com>
 * Copyright (C) 2012	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013-2017 Ferran Marcet		<fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *      \file       orderstoinvoice/core/modules/modOrderstoinvoice.class.php
 *      \ingroup    mymodule
 *      \brief      Description and activation file for module Orderstoinvoice
 */
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 * 		\class      modOrderstoinvoice
 *      \brief      Description and activation class for module MyModule
 */
class modMassorders extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;

        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 400010;
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'Massorders';

        // Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
        // It is used to group modules in module setup page
        $this->family = 'financial';
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        // Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
        $this->description = 'Mass orders';
        // Possible values for version are: 'development', 'experimental', 'dolibarr' or version
        $this->version = '8.0.0';
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        // Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
        $this->special = 0;
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto = 'bill';

        $this->editor_name = '<b>2byte.es</b>';
        $this->editor_web = 'www.2byte.es';

        // Defined if the directory /mymodule/includes/triggers/ contains triggers or not
        $this->triggers = 0;

        $this->module_parts = array('hooks' => array('invoicecard', 'pdfgeneration'));

        // Data directories to create when module is enabled.
        $this->dirs = array();
        $r = 0;

        // Config pages. Put here list of php page names stored in admmin directory used to setup module.
        $this->config_page_url = array('massorders.php@massorders');

        // Dependencies
        $this->depends = array();        // List of modules id that must be enabled if this module is enabled
        $this->requiredby = array();    // List of modules id to disable if this one is disabled
        $this->phpmin = array(5, 6);                    // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(4, 0);    // Minimum version of Dolibarr required by module
        $this->langfiles = array('massorders@massorders');

        // Constants
        $this->const = array();

        // Array to add new pages in new tabs
        $this->tabs = array();

        // Dictionnaries
        $this->dictionaries = array();

        // Boxes
        // Add here list of php file(s) stored in includes/boxes that contains class to show a box.
        $this->boxes = array();            // List of boxes
        $r = 0;

        // Permissions
        $this->rights = array();        // Permission array used by this module
        $r = 0;

		if (version_compare(DOL_VERSION, 7.0) >= 0) {
			$namemenu = 'billing';
		}
		else{
			$namemenu = 'accountancy';
		}

        //Menu left into products
        $this->menu[$r] = array(
            'fk_menu' => 'fk_mainmenu='.$namemenu,
            'type' => 'left',
            'titre' => 'MassOrders',
            'mainmenu' => $namemenu,
            'leftmenu' => 'massorders',
            'url' => 'compta/index.php',
            'langs' => 'massorders@massorders',
            'position' => 100,
            'enabled' => '$conf->massorders->enabled',
            'perms' => '1',
            'target' => '',
            'user' => 0
        );

        $r++; //1

        // Example to declare another Left Menu entry:
        $this->menu[$r] = array(
            'fk_menu' => 'fk_mainmenu='.$namemenu.',fk_leftmenu=massorders',
            // Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
            'type' => 'left',
            // This is a Left menu entry
            'titre' => 'Propals',
            'mainmenu' => $namemenu,
            'url' => '/massorders/proposals.php',
            'langs' => 'massorders@massorders',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 101,
            'enabled' => '$conf->propal->enabled',
            // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
            'perms' => '1',
            // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
            'target' => '',
            'user' => 0
        );                // 0=Menu for internal users, 1=external users, 2=both
        $r++; //2

        // Example to declare another Left Menu entry:
        $this->menu[$r] = array(
            'fk_menu' => 'fk_mainmenu='.$namemenu.',fk_leftmenu=massorders',
            // Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
            'type' => 'left',
            // This is a Left menu entry
            'titre' => 'CustomerOrders',
            'mainmenu' => $namemenu,
            'url' => '/massorders/customerorders.php',
            'langs' => 'massorders@massorders',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 101,
            'enabled' => '$conf->commande->enabled',
            // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
            'perms' => '1',
            // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
            'target' => '',
            'user' => 0
        );                // 0=Menu for internal users, 1=external users, 2=both
        $r++; //3

        // Example to declare another Left Menu entry:
        $this->menu[$r] = array(
            'fk_menu' => 'fk_mainmenu='.$namemenu.',fk_leftmenu=massorders',
            // Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
            'type' => 'left',
            // This is a Left menu entry
            'titre' => 'Sendings',
            'mainmenu' => $namemenu,
            'url' => '/massorders/shipment.php',
            'langs' => 'massorders@massorders',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 101,
            'enabled' => '$conf->expedition->enabled',
            // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
            'perms' => '1',
            // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
            'target' => '',
            'user' => 0
        );                // 0=Menu for internal users, 1=external users, 2=both
        $r++; //4

        // Example to declare another Left Menu entry:
        $this->menu[$r] = array(
            'fk_menu' => 'fk_mainmenu='.$namemenu.',fk_leftmenu=massorders',
            // Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
            'type' => 'left',
            // This is a Left menu entry
            'titre' => 'SupplierOrders',
            'mainmenu' => $namemenu,
            'url' => '/massorders/supplierorders.php',
            'langs' => 'massorders@massorders',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 101,
            'enabled' => '$conf->fournisseur->enabled',
            // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
            'perms' => '1',
            // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
            'target' => '',
            'user' => 0
        );                // 0=Menu for internal users, 1=external users, 2=both
        //$r++; //5
    }

    /**
     * Function called when module is enabled.
     * The init function adds tabs, constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     * It also creates data directories
     *
     * @param string $options Options when enabling module ('', 'newboxdefonly', 'noboxes')
     *                          'noboxes' = Do not insert boxes
     *                          'newboxdefonly' = For boxes, insert def of boxes only and not boxes activation
     * @return int                1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        $sql = array();

        $result = $this->load_tables();

        return $this->_init($sql);
    }

    /**
     * Function called when module is disabled.
     * The remove function removes tabs, constants, boxes, permissions and menus from Dolibarr database.
     * Data directories are not deleted
     *
     * @param      string $options Options when enabling module ('', 'noboxes')
     * @return     int                    1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();

        return $this->_remove($sql);
    }


    /**
     *        \brief        Create tables, keys and data required by module
     *                    Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
     *                    and create data commands must be stored in directory /mymodule/sql/
     *                    This function is called by this->init.
     *        \return        int        <=0 if KO, >0 if OK
     */
    public function load_tables()
    {
        return $this->_load_tables('/massorders/sql/');
    }
}
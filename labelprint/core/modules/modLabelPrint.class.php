<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2012      Juanjo Menent		<jmenent@2byte.es>
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
 * 		\file       modLabelPrint.class.php
 * 		\defgroup   LabelPrint     Module Labels
 *      \brief      File of construction class of label print
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 * 		\class      modLabelPrint
 *      \brief      Description and activation class for module LabelPrint
 */
class modLabelPrint extends DolibarrModules
{
	/**
	 *    Constructor. Define names, constants, directories, boxes, permissions
	 * @param      DoliDB $DB Database handler
	 */
	public function __construct($DB)
	{
		global $conf;

		$this->db = $DB;

		// Id for modul.
		$this->numero = 400007;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'labelprint';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		$this->family = "products";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = 'Labels print for products';
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '8.0.0';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 2;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'barcode';

		$this->editor_name = '2byte.es';
		$this->editor_url = 'www.2byte.es';

		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory
			//'triggers' => 1,
			// Set this to 1 if module has its own login method directory
			//'login' => 0,
			// Set this to 1 if module has its own substitution function file
			//'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory
			//'menus' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			// 'theme' => 0,
			// Set this to 1 if module overwrite template dir (core/tpl)
			// 'tpl' => 0,
			// Set this to 1 if module has its own barcode directory
				'barcode' => 1,
			// Set this to 1 if module has its own models directory
			//'models' => 1
			// Set this to relative path of css if module has its own css file
			//'css' => array('numberseries/css/mycss.css.php'),
			// Set this to relative path of js file if module must load a js on all pages
			// 'js' => array('numberseries/js/numberseries.js'),
			// Set here all hooks context managed by module
			// 'hooks' => array('hookcontext1','hookcontext2'),
			// To force the default directories names
			// 'dir' => array('output' => 'othermodulename'),
			// Set here all workflow context managed by module
			// Don't forget to depend on modWorkflow!
			// The description translation key will be descWORKFLOW_MODULE1_YOURACTIONTYPE_MODULE2
			// You will be able to check if it is enabled with the $conf->global->WORKFLOW_MODULE1_YOURACTIONTYPE_MODULE2 constant
			// Implementation is up to you and is usually done in a trigger.
			// 'workflow' => array(
			//     'WORKFLOW_MODULE1_YOURACTIONTYPE_MODULE2' => array(
			//         'enabled' => '! empty($conf->module1->enabled) && ! empty($conf->module2->enabled)',
			//         'picto' => 'yourpicto@numberseries',
			//         'warning' => 'WarningTextTranslationKey',
			//      ),
			// ),
		);

		// Data directories to create when module is enabled.
		$this->dirs = array();

		// Config pages. Put here list of php page names stored in admmin directory used to setup module.
		$this->config_page_url = array('labelprint.php@labelprint');

		// Dependencies
		$this->depends = array(
				'modProduct',
				'modBarcode'
		);                    // List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();                // List of modules id to disable if this one is disabled
		$this->phpmin = array(5, 6);                    // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(4, 0);    // Minimum version of Dolibarr required by module
		$this->langfiles = array('labelprint@labelprint');

        $LABELPRINT_A4 = $conf->global->LABELPRINT_A4 === 0 ?'0':'1';
        $LABELPRINT_THIRD_A4 = $conf->global->LABELPRINT_THIRD_A4 === 0 ?'0':'1';
		// Constants
		$this->const = array(
				0 => array('MAIN_MODULE_LABELPRINT_T_0', 'chaine', '1', '', 0),
				1 => array('MAIN_MODULE_LABELPRINT_T_1', 'chaine', '1', '', 0),
				2 => array('MAIN_MODULE_LABELPRINT_T_2', 'chaine', '1', '', 0),
				3 => array('MAIN_MODULE_LABELPRINT_LABELS_0', 'chaine', '1', '', 0),
				4 => array('LABELPRINT_SHOW_PRICE', 'chaine', '1', '', 0),
                5 => array('LABELPRINT_A4', 'chaine', $LABELPRINT_A4,'',0),
                6 => array('LABELPRINT_THIRD_A4', 'chaine', $LABELPRINT_THIRD_A4 ,'',0)
		);

		// Array to add new pages in new tabs
		$this->tabs = array(
				'supplier_invoice:+labelprint:ProductLabels:labelprint@labelprint:$conf->global->MAIN_MODULE_LABELPRINT_T_0:/labelprint/invoice_supplier.php?id=__ID__',
				'supplier_order:+labelprint:ProductLabels:labelprint@labelprint:$conf->global->MAIN_MODULE_LABELPRINT_T_1:/labelprint/order_supplier.php?id=__ID__',
				'product:+labelprint:Labels:labelprint@labelprint:$conf->global->MAIN_MODULE_LABELPRINT_T_2:/labelprint/product.php?id=__ID__',
				'propal:+labelprint:ThirdLabels:labelprint@labelprint:$conf->global->MAIN_MODULE_LABELPRINT_THIRD_0:/labelprint/propal_customer.php?id=__ID__',
				'order:+labelprint:ThirdLabels:labelprint@labelprint:$conf->global->MAIN_MODULE_LABELPRINT_THIRD_1:/labelprint/order_customer.php?id=__ID__',
				'invoice:+labelprint:ThirdLabels:labelprint@labelprint:$conf->global->MAIN_MODULE_LABELPRINT_THIRD_2:/labelprint/invoice_customer.php?id=__ID__',
				'thirdparty:+labelprint:Labels:labelprint@labelprint:$conf->global->MAIN_MODULE_LABELPRINT_THIRD_3:/labelprint/third.php?id=__ID__',
                'propal:+labelprintcontact:ContactLabels:labelprint@labelprint:$conf->global->MAIN_MODULE_LABELPRINT_CONTACT_0:/labelprint/propal_contact.php?id=__ID__',
                'order:+labelprintcontact:ContactLabels:labelprint@labelprint:$conf->global->MAIN_MODULE_LABELPRINT_CONTACT_1:/labelprint/order_contact.php?id=__ID__',
                'invoice:+labelprintcontact:ContactLabels:labelprint@labelprint:$conf->global->MAIN_MODULE_LABELPRINT_CONTACT_2:/labelprint/invoice_contact.php?id=__ID__',
				'contact:+labelprint:Labels:labelprint@labelprint:$conf->global->MAIN_MODULE_LABELPRINT_CONTACT_3:/labelprint/contact.php?id=__ID__'
		);

		// Boxes
		// Add here list of php file(s) stored in includes/boxes that contains class to show a box.
		$this->boxes = array();            // List of boxes

		// Permissions
		$this->rights = array();        // Permission array used by this module

		// Main menu entries
		$this->menus = array();            // List of menus to add
		$r = 0;

		//Menu left into products
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=products',
				'type' => 'left',
				'titre' => 'ProductLabels',
				'mainmenu' => 'products',
				'leftmenu' => 'labelprint',
				'url' => '/labelprint/product_list.php',
				'langs' => 'labelprint@labelprint',
				'position' => 100,
				'enabled' => '$conf->global->MAIN_MODULE_LABELPRINT_T_3',
				'perms' => '1',
				'target' => '',
				'user' => 0
		);

		//Menu left into thirds
		$r++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=companies',
				'type' => 'left',
				'titre' => 'ThirdLabels',
				'mainmenu' => 'companies',
				'leftmenu' => 'labelprint',
				'url' => '/labelprint/third_list.php',
				'langs' => 'labelprint@labelprint',
				'position' => 100,
				'enabled' => '$conf->global->MAIN_MODULE_LABELPRINT_THIRD_4',
				'perms' => '1',
				'target' => '',
				'user' => 0
		);

		//Menu left into contacts
        $r++;
        $this->menu[$r] = array(
            'fk_menu' => 'fk_mainmenu=companies',
            'type' => 'left',
            'titre' => 'ContactLabels',
            'mainmenu' => 'companies',
            'leftmenu' => 'labelprint',
            'url' => '/labelprint/contact_list.php',
            'langs' => 'labelprint@labelprint',
            'position' => 100,
            'enabled' => '$conf->global->MAIN_MODULE_LABELPRINT_CONTACT_5',
            'perms' => '1',
            'target' => '',
            'user' => 0
        );

	}

    /**
     * Function called when module is enabled.
     * The init function adds tabs, constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     * It also creates data directories
     *
     * @param string $options   Options when enabling module ('', 'newboxdefonly', 'noboxes')
     *                          'noboxes' = Do not insert boxes
     *                          'newboxdefonly' = For boxes, insert def of boxes only and not boxes activation
     * @return int				1 if OK, 0 if KO
     */
    public function init($options = '')
	{
		$sql = array();

		$this->load_tables();

		return $this->_init($sql);
	}

    /**
     * Function called when module is disabled.
     * The remove function removes tabs, constants, boxes, permissions and menus from Dolibarr database.
     * Data directories are not deleted
     *
     * @param      string	$options    Options when enabling module ('', 'noboxes')
     * @return     int             		1 if OK, 0 if KO
     */
    public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql);
	}


	/**
	 *        Create tables, keys and data required by module
	 *        Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 *        and create data commands must be stored in directory /mymodule/sql/
	 *        This function is called by this->init
	 *
	 * @return        int        <=0 if KO, >0 if OK
	 */
	public function load_tables()
	{
		return $this->_load_tables('/labelprint/sql/');
	}
}
<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr> 
 * Copyright (C) 2010-2012 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013-2015 Ferran Marcet		<fmarcet@2byte.es>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * 		\defgroup   2Facturae     Module FACTURAE
 *      \brief      Generate files according to Spanish standard Facturae.
 */

/**
 *      \file       htdocs/includes/modules/modFacturae.class.php
 *      \ingroup    FACTURAE
 *      \brief      Description and activation file for module Facturae
 */
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 * 		\class      modFacturae
 *      \brief      Description and activation class for module Facturae
 */
class modFacturae extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *   @param      DB      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		// Id for module (must be unique).
		$this->numero = 400024;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'facturae';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		$this->family = "financial";
		
		// Module label 
		$this->name = preg_replace('/^mod/i','',get_class($this));
		
		// Module description
		$this->description = "facturae";
		
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '8.0.0';
		
		// Key used in llx_const table to save module status enabled/disabled (where FACTURAE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 2;
		
		// Name of image file used for this module.
		$this->picto='facturae@facturae';
		
		$this->editor_name = '<b>2byte.es</b>';
		$this->editor_web = 'www.2byte.es';

		// Defined if the directory /module/inc/triggers/ contains triggers or not
		$this->triggers = 0;

		// Data directories to create when module is enabled.
		$this->dirs = array("/facturae");
		$r=0;

		// Relative path to module style sheet if exists. 
		$this->style_sheet = '';
		
		$this->langfiles = array("facturae@facturae");
 		
		// Config pages
        //-------------
		$this->config_page_url = array("facturae.php@facturae");
		
		// Dependencies
		$this->depends = array("modPrelevement");	// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();				// List of modules id to disable if this one is disabled
		$this->phpmin = array(5,6);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(4,0);	// Minimum version of Dolibarr required by module

		// Constants
		$this->const = array();			// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 0 or 'allentities')

		// Array to add new pages in new tabs
		// Example: $this->tabs = array('objecttype:+tabname1:Title1:mylangfile@mymodule:$user->rights->mymodule->read:/mymodule/mynewtab1.php?id=__ID__',  	// To add a new tab identified by code tabname1
        //                              'objecttype:+tabname2:Title2:mylangfile@mymodule:$user->rights->othermodule->read:/mymodule/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2
        //                              'objecttype:-tabname':NU:conditiontoremove);                                                     						// To remove an existing tab identified by code tabname
		// where objecttype can be
		// 'thirdparty'       to add a tab in third party view
		// 'intervention'     to add a tab in intervention view
		// 'order_supplier'   to add a tab in supplier order view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'invoice'          to add a tab in customer invoice view
		// 'order'            to add a tab in customer order view
		// 'product'          to add a tab in product view
		// 'stock'            to add a tab in stock view
		// 'propal'           to add a tab in propal view
		// 'member'           to add a tab in fundation member view
		// 'contract'         to add a tab in contract view
		// 'user'             to add a tab in user view
		// 'group'            to add a tab in group view
		// 'contact'          to add a tab in contact view
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
        $this->tabs = array('invoice:+facturae:Facturae:facturae@facturae:$user->rights->facturae->read:/facturae/invoice.php?id=__ID__',
        					'thirdparty:+facturae:Facturae:facturae@facturae:$user->rights->facturae->read:/facturae/thirdparty.php?socid=__ID__');
		
		// Boxes
		$this->boxes = array();			// List of boxes
		$r=0;

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;

		// Add here list of permission defined by an id, a label, a boolean and two constant strings.
		// Example:
		$this->rights[$r][0] = 4000241; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'read';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;
		
		$this->rights[$r][0] = 4000242; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'create';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'create';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		// Main menu entries
		$this->menus = array();			// List of menus to add
		$r=0;

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
		global $conf;
		$sql = array();

		$result=$this->load_tables();

		$sql2 = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'facturae_payments WHERE entity = '.$conf->entity;
		$resql = $this->db->query($sql2);

		if ($this->db->num_rows($resql) == 0){
			$i = 1;
			while($i <= 19){
				$sql2 = 'INSERT INTO '.MAIN_DB_PREFIX.'facturae_payments (entity,facturae_cod) VALUE ('.$conf->entity.',"'.str_pad($i,2,'0',STR_PAD_LEFT).'")';
				$this->db->query($sql2);

				$i++;
			}
		}


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
	 *		Create tables, keys and data required by module
	 * 		Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * 		and create data commands must be stored in directory /Asterisk/sql/
	 *		This function is called by this->init.
	 * 		@return		int		<=0 if KO, >0 if OK
	 */
	public function load_tables()
	{
		return $this->_load_tables('/facturae/sql/');
	}
}

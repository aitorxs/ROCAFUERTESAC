<?php 

	include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

	class modDescuento extends DolibarrModules
	{
		/**
		 *   Constructor. Define names, constants, directories, boxes, permissions
		 *
		 *   @param      DoliDB		$db      Database handler
		 */
		function __construct($db)
		{
			global $langs,$conf;

			$this->db = $db;

			// Id for module (must be unique).
			// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
			$this->numero = 203001; //100000;
			// Key text used to identify module (for permissions, menus, etc...)
			$this->rights_class = 'descuento';

			// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
			// It is used to group modules in module setup page
			$this->family = "crm";
			// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
			$this->name = preg_replace('/^mod/i','',get_class($this));
			// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
			$this->description = "Módulo para descuentos por línea de producción";
			// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
			$this->version = '5.0.2';
			// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
			$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
			// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
			$this->special = 0;
			// Name of image file used for this module.
			// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
			// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
			$this->picto='margin';

			$this->module_parts = array(
				'hooks' => array('commcard','propalcard')
			);

			// Data directories to create when module is enabled.
			// Example: this->dirs = array("/mymodule/temp");
			$this->dirs = array();

			// Config pages. Put here list of php page, stored into mymodule/admin directory, to use to setup module.
			$this->config_page_url = array("descuento_setup.php@descuento");

			// Dependencies
			$this->hidden = false;			// A condition to hide module
			$this->depends = array();		// List of modules id that must be enabled if this module is enabled
			$this->requiredby = array();	// List of modules id to disable if this one is disabled
			$this->conflictwith = array();	// List of modules id this module is in conflict with
			$this->phpmin = array(5,0);					// Minimum version of PHP required by module
			$this->need_dolibarr_version = array(3,0);	// Minimum version of Dolibarr required by module
			$this->langfiles = array("mylangfile@mymodule");

			$this->const = array();

			$this->tabs = array();

			$this->dictionaries=array();

			// Boxes
			// Add here list of php file(s) stored in core/boxes that contains class to show a box.
			$this->boxes = array();			// List of boxes

			// Permissions
			$this->rights = array();		// Permission array used by this module


		}

		/**
		 *		Function called when module is enabled.
		 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
		 *		It also creates data directories
		 *
		 *      @param      string	$options    Options when enabling module ('', 'noboxes')
		 *      @return     int             	1 if OK, 0 if KO
		 */
		function init($options='')
		{
			$sql = array();

			$result=$this->_load_tables('/descuento/sql/');

			return $this->_init($sql, $options);
		}

		/**
		 *		Function called when module is disabled.
		 *      Remove from database constants, boxes and permissions from Dolibarr database.
		 *		Data directories are not deleted
		 *
		 *      @param      string	$options    Options when enabling module ('', 'noboxes')
		 *      @return     int             	1 if OK, 0 if KO
		 */
		function remove($options='')
		{
			$sql = array();

			return $this->_remove($sql, $options);
		}

	}


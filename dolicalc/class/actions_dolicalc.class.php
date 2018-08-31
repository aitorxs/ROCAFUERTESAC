<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2017 AXeL <anass_denna@hotmail.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file	class/actions_listexportimport.class.php
 * \ingroup listexportimport
 * \brief   This file is an example hook overload class file
 *		  Put some comments here
 */

/**
 * Class ActionsListExportImport
 */
class ActionsDoliCalc
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Overloading the printTopRightMenu function
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function printTopRightMenu($parameters, &$object, &$action, $hookmanager)
	{
                global $langs;
                
		$error = 0; // Error counter
		$myvalue = ''; // A result value
 
		//print_r($parameters);
		//echo "action: " . $action;
		//print_r($object);
 
		if (in_array('toprightmenu', explode(':', $parameters['context'])))
		{
                    // do something only for the context 'toprightmenu'
                    $this->results = array('myreturn' => $myvalue);
                    // DoliCalc shortcut
                    $langs->load("dolicalc@dolicalc");
                    $text = '<a id="dolicalcbutton" href="#">';
                    $text.= img_picto(":".$langs->trans("Module514000Name"), 'calc-top@dolicalc');
                    $text.= '</a>';
                    $this->resprints = @Form::textwithtooltip('',$langs->trans("Module514000Name"),2,1,$text,'login_block_elem',2);
		}
 
		if (! $error)
		{
                    
                    return 0; // or return 1 to replace standard code
		}
		else
		{
			$this->errors[] = 'Error message';
			return -1;
		}
	}
}

<?php
/* Copyright (C) 2014-2017  Ferran Marcet <fmarcet@2byte.es>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/pos/class/actions_expenses.class.php
 *	\ingroup    expenses
 *	\brief      File Class expenses
 */

require __DIR__.'/ticket.class.php';

/**
 *	\class      ActionsExpenses
 *	\brief      Class Actions of the module expenses
 */
class ActionsPos
{
    public $db;
    public $dao;

    public $mesg;
    public $error;
    public $errors = array();
    //! Numero de l'erreur
    public $errno = 0;

    /**
     *    Constructor
     *
     * @param    DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Instantiation of DAO class
     *
     * @return    void
     */
    private function getInstanceDao()
    {
        if (!is_object($this->dao)) {
            $this->dao = new Ticket($this->db);
        }
    }

    /**
     *    Enter description here ...
     *
     * @param    string $action Action type
     * @return int
     */
    public function getLoginPageOptions($parameters, &$object, &$action = '', $hook)
    {
        global $langs;

        $langs->load("pos@pos");

        if (strstr($_SERVER["REQUEST_URI"], 'pos/frontend/index')) {

            $this->getInstanceDao();
            //if(!class_exists('Mobile_Detect'))
            //	dol_include_once('/pos/class/mobile_detect.php');
            dol_include_once('/pos/class/pos.class.php');
            $result = '';
            $terminals = POS::select_Terminals();
            if (is_array($terminals)) {
                $result = '<select class="flat" id="terminal" name="terminal">' . "\n";
                //$detect = new Mobile_Detect();
                $i = 0;
                foreach ((array)$terminals as $terminal) {
                    /*if($detect->isMobile())
                    {
                        if($terminal["tactil"] == 2)
                        {
                            $result.= "<option value='".$terminal["rowid"]."'>".$terminal["name"]."</option>\n";
                        }
                    }
                    else*/
                    {
                        $result .= "<option value='" . $terminal["rowid"] . "'>" . $terminal["name"] . "</option>\n";
                    }

                    $i++;
                }
                $result .= '</select>' . "\n";
            } else {
                $result .= '<label>' . $langs->trans("NotHasTerminal") . '</label>';
            }

            $divformat = '<div class="terminalBox"><strong><label for="Terminal">' . $langs->trans('Terminal') . '</label></strong>';
            $divformat .= $result;
            $divformat .= '</div>';

			if (version_compare(DOL_VERSION, 6.0) >= 0) {
				$this->results['div'] = $divformat;
			}
			else {
				$this->results['options']['div'] = $divformat;
			}
            $tableformat = '<tr><td class="loginfield nowrap" valign="middle"><strong><label for="Terminal">' . $langs->trans('Terminal') . '</label></strong></td>';
            $tableformat .= '<td valign="top" nowrap="nowrap">';
            $tableformat .= $result;
            $tableformat .= '</td></tr>';

			if (version_compare(DOL_VERSION, 6.0) >= 0) {
				$this->results['table'] = $tableformat;
			}
			else {
				$this->results['options']['table'] = $tableformat;
			}

            return 1;
        }
        return 0;

    }
}
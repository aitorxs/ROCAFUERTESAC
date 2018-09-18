<?php
/* Copyright (C) 2014-2017      Ferran Marcet <fmarcet@2byte.es>
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
 *	\file       htdocs/pos/class/actions_expenses.class.php
 *	\ingroup    expenses
 *	\brief      File Class expenses
 */

//require __DIR__.'/massorders.class.php';

/**
 *	\class      ActionsExpenses
 *	\brief      Class Actions of the module expenses
 */
class ActionsMassorders
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
            $this->dao = new Massorders($this->db);
        }
    }

    /**
     *    Enter description here ...
     *
     * @param    string $action Action type
     * @return int
     */
    public function printObjectLine($parameters, &$object, &$action = '', $hook)
    {
        global $langs;

        $langs->load('massorders@massorders');
        if ($parameters['line']->special_code == 4) {

            $tpl = dol_buildpath('/massorders/tpl/invoices.tpl.php');

            $res = @include $tpl;

            return 1;
        }
        return 0;

    }

    public function pdf_getlinevatrate($parameters, &$object, &$action = '', $hook)
    {

        if ($parameters['special_code'] == 4) {

            $this->resprints = '';

            return 1;
        }
        return 0;
    }

    public function pdf_getlineupexcltax($parameters, &$object, &$action = '', $hook)
    {

        if ($parameters['special_code'] == 4) {

            $this->resprints = '';

            return 1;
        }
        return 0;
    }

    public function pdf_getlineqty($parameters, &$object, &$action = '', $hook)
    {

        if ($parameters['special_code'] == 4) {

            $this->resprints = '';

            return 1;
        }
        return 0;
    }

    public function pdf_getlineunit($parameters, &$object, &$action = '', $hook)
    {

        if ($parameters['special_code'] == 4) {

            $this->resprints = '';

            return 1;
        }
        return 0;
    }

    public function pdf_getlineremisepercent($parameters, &$object, &$action = '', $hook)
    {

        if ($parameters['special_code'] == 4) {

            $this->resprints = '';

            return 1;
        }
        return 0;
    }

    public function pdf_getlineprogress($parameters, &$object, &$action = '', $hook)
    {

        if ($parameters['special_code'] == 4) {

            $this->resprints = '';

            return 1;
        }
        return 0;
    }

    public function pdf_getlinetotalexcltax($parameters, &$object, &$action = '', $hook)
    {

        if ($parameters['special_code'] == 4) {

            $this->resprints = '';

            return 1;
        }
        return 0;
    }
}
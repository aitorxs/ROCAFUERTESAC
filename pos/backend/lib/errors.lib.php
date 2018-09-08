<?php
/* Copyright (C) 2011 Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2017 Ferran Marcet           <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU  *General Public License as published by
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
 * 
 * Error controler
 * @param int 		$value		error number
 * @param string 	$function	function with error
 *
 * @return mixed
 */
function ErrorControl($value, $function="")
{
	global $langs;
	$langs->load("pos");
	
	if (! is_array($value))
	{
		return ControlNotArray($value,$function);
	}
	else 
	{
		return ControlArray($value,$function);
	}	
	
}

function ControlArray($value, $function)
{
    global $langs;
    $langs->load("pos");

    $ret = array();

    switch ($function) {
        case "GetProduct":
            $ret['error']['value'] = 0;
            $ret['error']['desc'] = '';
            $ret['data'] = $value;
            break;
        case "GetTicket":
            $ret['error']['value'] = 0;
            $ret['error']['desc'] = '';
            $ret['data'] = $value;
            break;
        case "GetHistoric":
            $ret['error']['value'] = 0;
            $ret['error']['desc'] = '';
            $ret['data'] = $value;
            break;
        case "getProductbyId":
            $ret['error']['value'] = 0;
            $ret['error']['desc'] = '';
            $ret['data'] = $value;
            break;
        case "SearchCustomer":
            $ret['error']['value'] = 0;
            $ret['error']['desc'] = '';
            $ret['data'] = $value;
            break;
		case "SearchBatch":
			$ret['error']['value'] = 0;
			$ret['error']['desc'] = '';
			$ret['data'] = $value;
			break;
		case "getBatchProduct":
			$ret['error']['value'] = 0;
			$ret['error']['desc'] = '';
			$ret['data'] = $value;
			break;
    }
    return $ret;
}

function ControlNotArray($value, $function)
{
    global $langs;
    $langs->load("pos");

    $ret = array();

    switch ($value) {
        case 0:
            switch ($function) {
                case "SetProduct":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrProductAlreadyExists");
                    $ret['data'] = null;
                    break;
                case "SetTicket":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrNoDataTicketReceived");
                    $ret['data'] = null;
                    break;
                case "GetTicket":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrNoDataTicketReceived");
                    $ret['data'] = null;
                    break;
                case "closeCash":
                    $ret['error']['value'] = 0;
                    $ret['error']['desc'] = $langs->trans("ErrCloseCashOK");;
                    $ret['data'] = null;
                    break;
                case "checkPassword":
                    $ret['error']['value'] = 0;
                    $ret['error']['desc'] = $langs->trans("ErrPasswordOK");;
                    $ret['data'] = 1;
                    break;
                case "sendMail":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrMailKO");;
                    $ret['data'] = null;
                    break;

            }
            break;
        case -1:
            switch ($function) {
                case "SetProduct":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrSetProductDesc");
                    $ret['data'] = null;
                    break;
                case "SetTicket":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrSaveTicket");
                    $ret['data'] = null;
                    break;
                case "GetTicket":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrLoadTicket");
                    $ret['data'] = null;
                    break;
                case "getHistoric":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrLoadHistoric");
                    $ret['data'] = '';
                    break;
                case "getLogin":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrLoadUser");
                    $ret['data'] = null;
                    break;
                case "getProductbyId":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrLoadProduct");
                    $ret['data'] = null;
                    break;
                case "SearchCustomer":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrLoadCustomer");
                    $ret['data'] = null;
                    break;
				case "SearchBatch":
					$ret['error']['value'] = 1;
					$ret['error']['desc'] = $langs->trans("ErrLoadBatch");
					$ret['data'] = null;
					break;
                case "closeCash":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrCloseCash");
                    break;
                case "SetCustomer":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrSetCustomerExist");
                    $ret['data'] = null;
                    break;
                case "checkPassword":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrPasswordKO");;
                    $ret['data'] = 0;
                    break;
                case "deleteTicket":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrDeleteKO");;
                    $ret['data'] = null;
                    break;
                case "sendMail":
                    $ret['error']['value'] = 0;
                    $ret['error']['desc'] = $langs->trans("ErrMailOK");;
                    $ret['data'] = $value;
                    break;

                default:
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrNotControled");
                    $ret['data'] = null;
            }

            break;
        case -2:
            switch ($function) {
                case "SetProduct":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrSetProductRef");
                    $ret['data'] = null;
                    break;
                case "SetTicket":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrSaveLineTicket");
                    $ret['data'] = null;
                    break;
                case "GetTicket":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrLoadTicket");
                    $ret['data'] = null;
                    break;
                case "getProductbyId":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrTooShortString");
                    $ret['data'] = null;
                    break;
                case "SearchCustomer":
				case "SearchBatch":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrTooShortString");
                    $ret['data'] = null;
                    break;
                case "getLogin":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrLoadTerminal");
                    break;
                case "SetCustomer":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrSetCustomerCreate");
                    $ret['data'] = null;
                    break;
                case "closeCash":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrCloseCashUser");
                    break;
                default:
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrNotControled");
                    $ret['data'] = null;
            }
            break;
        case -3:
            switch ($function) {
                case "SetTicket":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrSavePayTicket");
                    $ret['data'] = null;
                    break;
                case "GetTicket":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrLoadLinesTicket");
                    $ret['data'] = null;
                    break;
                case "SetCustomer":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrSetCustomerVerify");
                    $ret['data'] = null;
                    break;
                default:
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrNotControled");
                    $ret['data'] = null;
            }
            break;
        case -4:
            switch ($function) {
				case "SetProduct":
					$ret['error']['value'] = 1;
					$ret['error']['desc'] = $langs->trans("NotEnoughPermissions");
					$ret['data'] = null;
					break;
                case "SetTicket":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrSaveStockTicket");
                    $ret['data'] = null;
                    break;
                default:
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrNotControled");
                    $ret['data'] = null;
            }
            break;
        case -5:
            switch ($function) {
                case "SetTicket":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrorUpdateTicket");
                    $ret['data'] = null;
                    break;
                case "SetCustomer":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrorIdProfExist");
                    $ret['data'] = null;
                    break;
                default:
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrNotControled");
                    $ret['data'] = null;
            }
            break;
        case -6:
            switch ($function) {
                case "SetTicket":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrProductsAlreadyReturned");
                    $ret['data'] = null;
                    break;
                case "SetCustomer":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrorIDProfIsMandatory");
                    $ret['data'] = null;
                    break;
            }
            break;
        case -7:
            switch ($function) {
                case "SetTicket":
                    $ret['error']['value'] = 1;
                    $ret['error']['desc'] = $langs->trans("ErrorIDProfIsMandatory");
                    $ret['data'] = null;
                    break;
            }
            break;
        default:
            switch ($function) {
                case "SetProduct":
                    $ret['error']['value'] = 0;
                    $ret['error']['desc'] = $langs->trans("ProductSaved");
                    $ret['data'] = $value;
                    break;
                case "SetCustomer":
                    $ret['error']['value'] = 0;
                    $ret['error']['desc'] = $langs->trans("CustomerSaved");
                    $ret['data'] = $value;
                    break;

                case "SetTicket":
                    $ret['error']['value'] = 0;
                    $ret['error']['desc'] = $langs->trans("TicketSaved");
                    $ret['data'] = $value;
                    break;
                case "closeCash":
                    $ret['error']['value'] = 0;
                    $ret['error']['desc'] = $langs->trans("ErrCloseCashOK");;
                    $ret['data'] = $value;
                    break;
                case "deleteTicket":
                    $ret['error']['value'] = 0;
                    $ret['error']['desc'] = $langs->trans("ErrDeleteOK");;
                    $ret['data'] = $value;
                    break;

                default:
                    $ret['error']['value'] = 0;
                    $ret['error']['desc'] = $langs->trans("ErrNotControled");
                    $ret['data'] = null;
            }
    }
    return $ret;
}

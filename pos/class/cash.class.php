<?php
/* Copyright (C) 2011 		Juanjo Menent	<jmenent@2byte.es>
 * Copyright (C) 2013-2018 	Ferran Marcet	<fmarcet@2byte.es>
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
 *  \file       htdocs/pos/class/cash.class.php
 *  \ingroup    ticket
 *  \brief      Cash Class file
 *  \version    $Id: cash.class.php,v 1.5 2011-08-16 15:36:15 jmenent Exp $
 */

/**
 *  \class      Cash
 *  \brief      Class to manage Cash devices
 */

class Cash extends CommonObject
{
    var $db;
    var $error;
    var $errors = array();
    var $element = 'pos_cash';
    var $table_element = 'pos_cash';
    var $ismultientitymanaged = 1;    // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    var $id;
    var $code;
    var $name;
    var $ref;
    var $tactil;
    var $barcode;
    var $fk_paycash;
    var $fk_modepaycash;
    var $fk_paybank;
    var $fk_paybank_extra;
    var $fk_modepaybank;
    var $fk_modepaybank_extra;
    var $fk_warehouse;
    var $fk_device;
    var $printer_name;
    Var $fk_soc;
    var $is_used;
    var $is_closed;
    var $fk_user_u;
    var $fk_user_c;
    var $fk_user_m;


    /**
     * @brief  Constructeur de la classe
     * @param  DB            handler acces base de donnees
     * @param  code        id cash ('' par defaut)
     */
    function Cash($DB, $code = '')
    {
        $this->db = $DB;

        $this->code = $code;
        $this->fk_paycash = 0;
        $this->fk_modepaycash = 0;
        $this->fk_paycash = 0;
        $this->fk_modepaybank = 0;
        $this->fk_paybank = 0;
        $this->fk_modepaybank_extra = 0;
        $this->fk_paybank_extra = 0;
        $this->fk_warehouse = 0;
        $this->fk_soc = 0;
        $this->fk_device = 0;
        $this->is_used = 0;
        $this->is_closed = 0;
        $this->fk_user = 0;
        $this->tactil = 0;
        $this->barcode = 0;

    }

    /**
     *    Create cash in database
     * @param        object user            Object user that create
     * @return        int                <0 if KO, >0 if OK
     */
    function create($user)
    {
        global $conf;

        // Clean parameters

        dol_syslog("Cash::Create user=" . $user->id);

        // Check parameters

        $now = dol_now();
        $this->db->begin();
        // Insert into database

        if ($this->fk_modepaybank_extra == 0)
            $this->fk_modepaybank_extra = -1;

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "pos_cash (";
        $sql .= " code";
        $sql .= ", entity";
        $sql .= ", name";
        $sql .= ", tactil";
        $sql .= ", barcode";
        $sql .= ", fk_paycash";
        $sql .= ", fk_modepaycash";
        $sql .= ", fk_paybank";
        $sql .= ", fk_modepaybank";
        $sql .= ", fk_paybank_extra";
        $sql .= ", fk_modepaybank_extra";
        $sql .= ", fk_warehouse";
        $sql .= ", fk_device";
        $sql .= ", printer_name";
        $sql .= ", fk_soc";
        $sql .= ", fk_user_c";
        $sql .= ", datec";
        $sql .= ", datea";
        $sql .= ")";
        $sql .= " VALUES (";
        $sql .= "'" . $this->code . "'";
        $sql .= ", " . $conf->entity;
        $sql .= ", '" . $this->name . "'";
        $sql .= ", " . $this->tactil;
        $sql .= ", " . $this->barcode;
        $sql .= "," . ($this->fk_paycash ? $this->fk_paycash : "null");
        $sql .= "," . ($this->fk_modepaycash ? $this->fk_modepaycash : "null");
        $sql .= "," . ($this->fk_paybank ? $this->fk_paybank : "null");
        $sql .= "," . ($this->fk_modepaybank ? $this->fk_modepaybank : "null");
        $sql .= "," . ($this->fk_paybank_extra ? $this->fk_paybank_extra : "null");
        $sql .= "," . ($this->fk_modepaybank_extra ? $this->fk_modepaybank_extra : "null");
        $sql .= "," . ($this->fk_warehouse ? $this->fk_warehouse : "null");
        $sql .= "," . ($this->fk_device ? $this->fk_device : "null");
        $sql .= "," . ($this->printer_name ? "'" . $this->printer_name . "'" : "null");
        $sql .= "," . ($this->fk_soc ? $this->fk_soc : "null");
        $sql .= "," . ($user->id > 0 ? "'" . $user->id . "'" : "null");
        $sql .= ", '" . $this->db->idate($now)."'";
        $sql .= ", '" . $this->db->idate($now)."'";
        $sql .= ")";

        dol_syslog("Cash::Create sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->db->commit();
            return 0;

        } else {
            $this->error = $this->db->error();
            dol_syslog("Cash::create error " . $this->error . " sql=" . $sql, LOG_ERR);
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *    Get object and lines from database
     * @param      int rowid       Id of object to load
     * @param        string code        Code of cash
     * @return     int         >0 if OK, <0 if KO
     */
    function fetch($rowid, $ref = '')
    {
        global $conf;

        if (empty($rowid) && empty($ref)) return -1;

        $sql = 'SELECT rowid';
        $sql .= ', code';
        $sql .= ', name';
        $sql .= ', tactil';
        $sql .= ', barcode';
        $sql .= ', fk_paycash';
        $sql .= ', fk_modepaycash';
        $sql .= ', fk_paybank';
        $sql .= ', fk_modepaybank';
        $sql .= ', fk_paybank_extra';
        $sql .= ', fk_modepaybank_extra';
        $sql .= ', fk_warehouse';
        $sql .= ', fk_device';
        $sql .= ', printer_name';
        $sql .= ', fk_soc';
        $sql .= ', is_used';
        $sql .= ', is_closed';
        $sql .= ', fk_user_u';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'pos_cash';
        $sql .= ' WHERE entity = ' . $conf->entity;
        if ($rowid) $sql .= " AND rowid=" . $rowid;
        else $sql .= " AND name='" . $ref . "'";

        dol_syslog("Cash::Fetch sql=" . $sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result)) {
                $obj = $this->db->fetch_object($result);

                $this->id = $obj->rowid;
                $this->code = $obj->code;
                $this->name = $obj->name;
                $this->ref = $obj->name;
                $this->tactil = $obj->tactil;
                $this->barcode = $obj->barcode;
                $this->fk_paycash = $obj->fk_paycash;
                $this->fk_modepaycash = $obj->fk_modepaycash;
                $this->fk_paybank = $obj->fk_paybank;
                $this->fk_modepaybank = $obj->fk_modepaybank;
                $this->fk_paybank_extra = $obj->fk_paybank_extra;
                $this->fk_modepaybank_extra = $obj->fk_modepaybank_extra;
                $this->fk_warehouse = $obj->fk_warehouse;
                $this->fk_device = $obj->fk_device;
                $this->printer_name = $obj->printer_name;
                $this->fk_soc = $obj->fk_soc;
                $this->is_used = $obj->is_used;
                $this->is_closed = $obj->is_closed;
                $this->fk_user_u = $obj->fk_user_u;

                return 1;
            } else {
                $this->error = 'Cash with id ' . $rowid . ' or code ' . $ref . ' not found sql=' . $sql;
                dol_syslog('Ticket::Fetch Error ' . $this->error, LOG_ERR);
                return -2;
            }
        } else {
            $this->error = $this->db->error();
            dol_syslog('Cash::Fetch Error ' . $this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     *      \brief      Update database
     *      \param      user            User that modify
     *      \return     int            <0 if KO, >0 if OK
     */
    function update($user)
    {
        $error = 0;

        // Clean parameters
        if ($this->fk_modepaybank_extra == 0)
            $this->fk_modepaybank_extra = -1;

        if (isset($this->code)) $this->code = trim($this->code);
        if (isset($this->fk_paycash)) $this->fk_paycash = trim($this->fk_paycash);
        if (isset($this->fk_modepaycash)) $this->fk_modepaycash = trim($this->fk_modepaycash);
        if (isset($this->fk_paybank)) $this->fk_paybank = trim($this->fk_paybank);
        if (isset($this->fk_paybank_extra)) $this->fk_paybank_extra = trim($this->fk_paybank_extra);
        if (isset($this->fk_modepaybank)) $this->fk_modepaybank = trim($this->fk_modepaybank);
        if (isset($this->fk_modepaybank_extra)) $this->fk_modepaybank_extra = trim($this->fk_modepaybank_extra);
        if (isset($this->fk_warehouse)) $this->fk_warehouse = trim($this->fk_warehouse);
        if (isset($this->fk_device)) $this->fk_device = trim($this->fk_device);
        if (isset($this->fk_soc)) $this->fk_soc = trim($this->fk_soc);

        // Check parameters
        $now = dol_now();
        // Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE " . MAIN_DB_PREFIX . "pos_cash SET";
        $sql .= " datea = '" . $this->db->idate($now) . "',";
        $sql .= " code=" . (isset($this->code) ? "'" . $this->db->escape($this->code) . "'" : "null") . ",";
        $sql .= " name=" . (isset($this->name) ? "'" . $this->db->escape($this->name) . "'" : "null") . ",";
        $sql .= " tactil=" . $this->tactil . ",";
        $sql .= " barcode=" . $this->barcode . ",";
        $sql .= " fk_paycash=" . (isset($this->fk_paycash) ? $this->fk_paycash : "null") . ",";
        $sql .= " fk_modepaycash=" . (isset($this->fk_modepaycash) ? $this->fk_modepaycash : "null") . ",";
        $sql .= " fk_paybank=" . (isset($this->fk_paybank) ? $this->fk_paybank : "null") . ",";
        $sql .= " fk_paybank_extra=" . (isset($this->fk_paybank_extra) ? $this->fk_paybank_extra : "null") . ",";
        $sql .= " fk_modepaybank=" . (isset($this->fk_modepaybank) ? $this->fk_modepaybank : "null") . ",";
        $sql .= " fk_modepaybank_extra=" . (isset($this->fk_modepaybank_extra) ? $this->fk_modepaybank_extra : "null") . ",";
        $sql .= " fk_warehouse=" . (isset($this->fk_warehouse) ? $this->fk_warehouse : "null") . ",";
        $sql .= " fk_device=" . (isset($this->fk_device) ? $this->fk_device : "null") . ",";
        $sql .= " printer_name=" . (isset($this->printer_name) ? "'" . $this->printer_name . "'" : "null") . ",";
        $sql .= " fk_soc=" . (isset($this->fk_soc) ? $this->fk_soc : "null") . ",";
        $sql .= " fk_user_m=" . ($user->id > 0 ? "'" . $user->id . "'" : "null");
        $sql .= " WHERE rowid=" . $this->id;

        $this->db->begin();

        dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     *    Delete Cash
     * @param        rowid        Id of ticket to delete
     * @return        int            <0 if KO, >0 if OK
     */
    function delete($rowid = 0)
    {
        if (!$rowid) $rowid = $this->id;

        dol_syslog(get_class($this) . "::delete rowid=" . $rowid, LOG_DEBUG);

        // Test if child exists
        $haschild = 0;

        // Check if cash can be deleted
        $sql = "SELECT COUNT(*) as nb from " . MAIN_DB_PREFIX . "pos_ticket";
        $sql .= " WHERE fk_cash = " . $rowid;
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj->nb > 0) {
                $haschild += $obj->nb;
            }
        } else {
            $this->error .= $this->db->lasterror();
            dol_syslog(get_class($this) . "::Delete erreur -1 " . $this->error, LOG_ERR);
            return -1;
        }

        if ($haschild > 0) {
            $this->error = "ErrorRecordHasChildren";
            return -2;
        }

        // Remove third Cash
        $sql = "DELETE from " . MAIN_DB_PREFIX . "pos_cash";
        $sql .= " WHERE rowid = " . $rowid;

        dol_syslog("Societe::Delete sql=" . $sql, LOG_DEBUG);
        if ($this->db->query($sql)) {
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            dol_syslog("Societe::Delete erreur -3 " . $this->error, LOG_ERR);
            return -1;
        }

    }

    /**
     *      Tag the Cash with used
     * @param      object        User utilisateur qui modifie
     * @return     int         <0 si ok, >0 si ok
     */
    function set_used($user)
    {
        $error = 0;

        if ($this->is_used != 1 && $user->id > 0) {
            $this->db->begin();

            dol_syslog(get_class($this) . "::set_used rowid=" . $this->id, LOG_DEBUG);
            $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'pos_cash SET';
            $sql .= ' is_used=1';
            $sql .= ', is_closed=0';
            $sql .= ", fk_user_u=" . $user->id;

            $sql .= ' WHERE rowid = ' . $this->id;

            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->error = $this->db->error();
                dol_print_error($this->db);
            }

            if (!$error) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } else {
            return true;
        }
    }


    /**
     *      Tag the Cash with unused
     * @param      user        Objet utilisateur qui modifie
     * @return     int         <0 si ok, >0 si ok
     */
    function set_unused($user)
    {
        $error = 0;

        if ($this->is_used != 0) {
            $this->db->begin();

            dol_syslog(get_class($this) . "::set_used rowid=" . $this->id, LOG_DEBUG);
            $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'pos_cash SET';
            $sql .= ' is_used=0';
            $sql .= ", fk_user_u=0";

            $sql .= ' WHERE rowid = ' . $this->id;

            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->error = $this->db->error();
                dol_print_error($this->db);
            }

            if (!$error) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     *      Tag the Cash withcloesed
     * @param      user        Objet utilisateur qui modifie
     * @return     int         <0 si ok, >0 si ok
     */
    /*function set_closed($user)
    {
        global $conf,$langs;
        $error=0;

        if ($this->is_closed != 1)
        {
            $this->db->begin();

            dol_syslog(get_class($this)."::set_closed rowid=".$this->id, LOG_DEBUG);
            $sql = 'UPDATE '.MAIN_DB_PREFIX.'pos_cash SET';
            $sql.= ' is_closed=1';
            $sql.= ", fk_user_u=".$user->id;
            
            $sql.= ' WHERE rowid = '.$this->id;

            $resql = $this->db->query($sql);
            if (!$resql)
            {
                $error++;
                $this->error=$this->db->error();
                dol_print_error($this->db);
            }

            if ($error==0)
            {
                $this->db->commit();
                return true;
            }
            else
            {
                $this->db->rollback();
                return false;
            }
        }
        else
        {
            return true;
        }
    }*/


    /**
     *      Tag the Cash with unclosed
     * @param      user        Objet utilisateur qui modifie
     * @return     int         <0 si ok, >0 si ok
     */
    /*function set_open($user)
    {
		global $conf,$langs;
        $error=0;

        if ($this->is_closed != 0)
        {
            $this->db->begin();

            dol_syslog(get_class($this)."::set_open rowid=".$this->id, LOG_DEBUG);
            $sql = 'UPDATE '.MAIN_DB_PREFIX.'pos_cash SET';
            $sql.= ' is_closed=0';
            $sql.= ", fk_user_u=0";
            
            $sql.= ' WHERE rowid = '.$this->id;

            $resql = $this->db->query($sql);
            if (!$resql)
            {
                $error++;
                $this->error=$this->db->error();
                dol_print_error($this->db);
            }

            if ($error==0)
            {
                $this->db->commit();
                return true;
            }
            else
            {
                $this->db->rollback();
                return false;
            }
        }
        else
        {
            return true;
        }
    }*/

    /**
     *    Returns if a cash can be deleted
     * @return     boolean     true if yes, false if not
     */
    function can_be_deleted()
    {
        $can_be_deleted = false;

        $sql = "SELECT COUNT(*) as nb from " . MAIN_DB_PREFIX . "pos_ticket as t, " . MAIN_DB_PREFIX . "pos_facture as f ";
        $sql .= " WHERE t.fk_cash = " . $this->id . " OR f.fk_cash = " . $this->id;

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj->nb <= 1) $can_be_deleted = true;
        } else {
            dol_print_error($this->db);
        }
        return $can_be_deleted;
    }

    /**
     *        Renturns clicable name
     * @param        withpicto        Include picto in link
     * @return        string            String avec URL
     */
    function getNomUrl($withpicto = 0)
    {
        global $langs;

        $result = '';

        $lien = '<a href=' . dol_buildpath('/pos/backend/terminal/fiche.php', 1) . '?id=' . $this->id . '>';
        $lienfin = '</a>';

        if ($withpicto) $result .= ($lien . img_object($langs->trans("ShowCash"), 'barcode') . $lienfin . ' ');
        $result .= $lien . $this->name . $lienfin;
        return $result;
    }

    /**
     *        Renturns clicable name
     * @param        userid            User id
     * @return        integer            > 0 OK, < 0 KO
     */
    function addUser($userid, $type)
    {
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "pos_users (";
        $sql .= " fk_terminal";
        $sql .= ", fk_object";
        $sql .= ", objtype";
        $sql .= ")";
        $sql .= " VALUES (";
        $sql .= $this->id;
        $sql .= ", " . $userid;
        $sql .= ", '" . $type . "'";
        $sql .= ")";

        dol_syslog("Cash::addUser sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->db->commit();
            return 1;

        } else {
            $this->error = $this->db->error();
            dol_syslog("Cash::addUser error " . $this->error . " sql=" . $sql, LOG_ERR);
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *        Renturns clicable name
     * @param        userid            User id
     * @return        integer            > 0 OK, < 0 KO
     */
    function deleteUser($userid, $type)
    {

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "pos_users WHERE ";
        $sql .= "fk_terminal = " . $this->id;
        $sql .= " AND fk_object = " . $userid;
        $sql .= " AND objtype = '" . $type . "'";


        dol_syslog("Cash::deleteUser sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->db->commit();
            return 1;

        } else {
            $this->error = $this->db->error();
            dol_syslog("Cash::addUser error " . $this->error . " sql=" . $sql, LOG_ERR);
            $this->db->rollback();
            return -1;
        }
    }


    /**
     *    Return label of status (activity, closed)
     * @param      mode 0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
     * @return     string        Libelle
     */
    function getLibStatut($mode = 0)
    {
        if ($this->is_closed)
            return $this->LibStatut(2, $mode);
        else
            return $this->LibStatut($this->is_used, $mode);
    }

    /**
     *      Renvoi le libelle d'un statut donne
     * @param      int statut          Id statut
     * @param      string mode 0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     * @return     string          Libelle du statut
     */
    function LibStatut($statut, $mode = 0)
    {
        global $langs;
        $langs->load('pos');

        if ($mode == 0) {
            if ($statut == 0) return $langs->trans("NotInUse");
            if ($statut == 1) return $langs->trans("InUse");
            if ($statut == 2) return $langs->trans("Closed");
        }
        if ($mode == 1) {
            if ($statut == 0) return $langs->trans("InUse");
            if ($statut == 1) return $langs->trans("NotInUse");
            if ($statut == 2) return $langs->trans("Closed");
        }
        if ($mode == 2) {
            if ($statut == 0) return img_picto($langs->trans("NotInUse"), 'statut4') . ' ' . $langs->trans("NotInUse");
            if ($statut == 1) return img_picto($langs->trans("InUse"), 'statut8') . ' ' . $langs->trans("InUse");
            if ($statut == 2) return img_picto($langs->trans("NotInUse"), 'off') . ' ' . $langs->trans("Closed");
        }
        if ($mode == 3) {
            if ($statut == 0) return img_picto($langs->trans("NotInUse"), 'statut4');
            if ($statut == 1) return img_picto($langs->trans("InUse"), 'statut8');
            if ($statut == 2) return img_picto($langs->trans("Closed"), 'off');
        }
        if ($mode == 4) {
            if ($statut == 0) return img_picto($langs->trans("NotInUse"), 'statut4') . ' ' . $langs->trans("NotInUse");
            if ($statut == 1) return img_picto($langs->trans("InUse"), 'statut8') . ' ' . $langs->trans("InUse");
            if ($statut == 2) return img_picto($langs->trans("Closed"), 'off') . ' ' . $langs->trans("Closed");
        }
        if ($mode == 5) {
            if ($statut == 0) return $langs->trans("NotInUse") . ' ' . img_picto($langs->trans("NotInUse"), 'statut4');
            if ($statut == 1) return $langs->trans("InUse") . ' ' . img_picto($langs->trans("InUse"), 'statut6');
            if ($statut == 2) return $langs->trans("Closed") . ' ' . img_picto($langs->trans("Closed"), 'Closed');
        }
    }

    /**
     *       Charge les informations d'ordre info dans l'objet societe
     * @param     id     Id de la societe a charger
     */
    function info($id)
    {
        $sql = "SELECT rowid, name, datec, datea,";
        $sql .= " fk_user_c, fk_user_m";
        $sql .= " FROM " . MAIN_DB_PREFIX . "pos_cash as s";
        $sql .= " WHERE rowid = " . $id;

        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result)) {
                $obj = $this->db->fetch_object($result);

                $this->id = $obj->rowid;

                if ($obj->fk_user_c) {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_c);
                    $this->user_creation = $cuser;
                }

                if ($obj->fk_user_m) {
                    $muser = new User($this->db);
                    $muser->fetch($obj->fk_user_m);
                    $this->user_modification = $muser;
                }
                $this->name = $obj->name;
                $this->date_creation = $this->db->jdate($obj->datec);
                $this->date_modification = $this->db->jdate($obj->datea);
            }

            $this->db->free($result);

        } else {
            dol_print_error($this->db);
        }
    }

    /**
     * Returns the name of payment
     *
     * @param        int $id id of payment
     *
     * @return        string                    name of payment
     */
    function select_Paymentname($id)
    {
        global $db, $langs;


        $sql = "SELECT id, code, libelle, type";
        $sql .= " FROM " . MAIN_DB_PREFIX . "c_paiement";
        $sql .= " WHERE active > 0 and id = " . $id;
        $sql .= " ORDER BY id";

        $resql = $db->query($sql);

        if ($resql) {
            $langs->load("bills");

            $obj = $db->fetch_object($resql);

            $libelle = ($langs->trans("PaymentTypeShort" . $obj->code) != ("PaymentTypeShort" . $obj->code) ? $langs->trans("PaymentTypeShort" . $obj->code) : ($obj->libelle != '-' ? $obj->libelle : ''));
            $db->free($resql);
        }
        return $libelle;

    }

    /**
     *    Return an html string with a select combo box to choose Tactil, Normal or Mobile
     *
     * @param    string $htmlname Name of html select field
     * @param    string $value Pre-selected value
     * @param    int $option 0 return tactil/normal/mobile, 1 return 1/0/2
     * @param    bool $disabled true or false
     * @return    mixed                    See option
     */
    function selecttypeterminal($htmlname, $value = '', $option = 0, $disabled = false)
    {
        global $langs;

        $tactil = "tactil";
        $normal = "normal";
        $mobile = "mobile";

        if ($option) {
            $tactil = "1";
            $normal = "0";
            $mobile = "2";
        }

        $disabled = ($disabled ? ' disabled="disabled"' : '');

        $resultyesno = '<select class="flat" id="' . $htmlname . '" name="' . $htmlname . '"' . $disabled . '>' . "\n";
        if (("$value" == 'normal') || ($value == 0)) {
            $resultyesno .= '<option value="' . $tactil . '">' . $langs->trans("Tactil") . '</option>' . "\n";
            $resultyesno .= '<option value="' . $normal . '" selected="selected">' . $langs->trans("Normal") . '</option>' . "\n";
            $resultyesno .= '<option value="' . $mobile . '">' . $langs->trans("Mobile") . '</option>' . "\n";
        } elseif (("$value" == 'yes') || ($value == 1)) {
            $resultyesno .= '<option value="' . $tactil . '" selected="selected">' . $langs->trans("Tactil") . '</option>' . "\n";
            $resultyesno .= '<option value="' . $normal . '">' . $langs->trans("Normal") . '</option>' . "\n";
            $resultyesno .= '<option value="' . $mobile . '">' . $langs->trans("Mobile") . '</option>' . "\n";
        } else {
            $resultyesno .= '<option value="' . $tactil . '">' . $langs->trans("Tactil") . '</option>' . "\n";
            $resultyesno .= '<option value="' . $normal . '">' . $langs->trans("Normal") . '</option>' . "\n";
            $resultyesno .= '<option value="' . $mobile . '" selected="selected">' . $langs->trans("Mobile") . '</option>' . "\n";
        }
        $resultyesno .= '</select>' . "\n";
        return $resultyesno;
    }

    /**
     *    Return normal, tactil or mobile in current language
     *
     * @param    string $yesno Value to test (1, 'tactil'; 0, 'normal' or 2, 'mobile')
     * @return    string                    HTML string
     */
    function tactiltype($yesno)
    {
        global $langs;
        $result = 'unknown';
        if ($yesno == 1 || strtolower($yesno) == 'tactil')    // A mettre avant test sur no a cause du == 0
        {
            $result = $langs->trans("Tactil");
        } elseif ($yesno == 0 || strtolower($yesno) == 'normal') {
            $result = $langs->trans("Normal");
        } elseif ($yesno == 2 || strtolower($yesno) == 'mobile') {
            $result = $langs->trans("Mobile");
        }
        return $result;
    }

    /**
     *
     * Returns terminals of POS
     */
    function selectterminal($htmlname, $selected = '')
    {
        global $db, $conf;

        $sql = "SELECT rowid, name";
        $sql .= " FROM " . MAIN_DB_PREFIX . "pos_cash";
        $sql .= " WHERE entity = " . $conf->entity;

        $res = $db->query($sql);

        if ($res) {
            $terms = array();
            $i = 0;
            while ($record = $db->fetch_array($res)) {
                foreach ($record as $cle => $valeur) {
                    $terms[$i][$cle] = $valeur;
                }
                $i++;
            }

        }

        $resultyesno = '<select class="flat" id="' . $htmlname . '" name="' . $htmlname . '">' . "\n";
        $resultyesno .= '<option value="-1"></option>' . "\n";
        foreach ($terms as $termi) {
            if ($selected == $termi['rowid'])
                $resultyesno .= '<option value="' . $termi['rowid'] . '" selected="selected">' . $termi['name'] . '</option>' . "\n";
            else
                $resultyesno .= '<option value="' . $termi['rowid'] . '">' . $termi['name'] . '</option>' . "\n";
        }
        $resultyesno .= '</select>' . "\n";
        return $resultyesno;
    }

}
/**
 *  \class      CLoseCash
 *  \brief      Class to manage Close Cash
 */

class ControlCash extends CommonObject
{
    var $db;
    var $terminal;
    var $rowid;
    var $ref;
    var $fk_cash;
    var $fk_user;

    //0=Arqueo, 1=Cierre, 2=Apertura
    var $type_control;
    var $date_creation;
    var $amount_teoric;
    var $amount_reel;
    var $amount_diff;
    var $amount_nextday;
    var $amount_manual_in;
    var $amount_manual_out;
    var $comment;


    /**
     *    \brief  Constructeur de la classe
     *    \param  DB            handler acces base de donnees
     *    \param  code        id cash ('' par defaut)
     */
    function ControlCash($DB, $terminal)
    {
        $this->db = $DB;
        $this->terminal = $terminal;

    }

    /**
     *    Create a close cash in database
     * @param        data            array of data ($user id, amount real, teoric and dif)
     * @return        int                <0 if KO, >0 if OK
     */
    function Create($data)
    {
        global $db, $conf, $mysoc, $user;
        $error = 0;

        // Clean parameters

        dol_syslog("CloseCash::Create user=" . $user->id);

        // Check parameters
        $date_close = $this->get_datafromlastclosing();
        $now = dol_now();

        $this->type_control = $data['type_control'];


        $this->db->begin();
        // Insert into database

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "pos_control_cash (";
        $sql .= " entity";
        $sql .= ", ref";
        $sql .= ", fk_cash";
        $sql .= ", fk_user";
        $sql .= ", amount_real";
        $sql .= ", amount_teor";
        $sql .= ", amount_diff";
        $sql .= ", type_control";
        $sql .= ", date_c";
        $sql .= ")";
        $sql .= " VALUES (";
        $sql .= $conf->entity;
        $sql .= ", '" . $this->getNextNumRef($mysoc) . "'";
        $sql .= ", '" . $this->terminal . "'";
        $sql .= ", " . $data['userid'];
        $sql .= ", " . $data['amount_reel'];
        $sql .= ", " . $data['amount_teoric'];
        $sql .= ", " . $data['amount_diff'];
        $sql .= ", " . $data['type_control'];
        $sql .= ", '" . $db->idate($now)."'";
        $sql .= ")";

        dol_syslog("CloseCash::Create sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {

            $closeid = $this->db->last_insert_id(MAIN_DB_PREFIX . "pos_control_cash");
            $this->db->commit();

            if ($data['type_control'] == 1) {
                dol_include_once("/pos/class/ticket.class.php");

                $this->setTicketClosedbyCash($closeid, $date_close);
                $this->setFactureClosedbyCash($closeid);

                $ticket = new Ticket($this->db);

                $res = $ticket->delete($this->terminal);

                if (!$res) {
                    $error++;
                }
            }
        } else {
            $error++;
            $this->errors[] = $this->db->error();
            dol_syslog("CloseCash::create error " . $this->error . " sql=" . $sql, LOG_ERR);
            $this->db->rollback();
        }

        if ($error > 0)
            return $error;
        else
            return $closeid;
    }

    /**
     *
     * Return the data of last closing
     *
     * @return    datetime    data of last closing
     */
    function get_datafromlastclosing()
    {
        global $db;

        $sql = "select max(date_c) as date_c";
        $sql .= " from " . MAIN_DB_PREFIX . "pos_control_cash";
        $sql .= " where type_control = 1 and fk_cash=" . $this->terminal;

        $result = $db->query($sql);
        if ($result) {
            if ($db->num_rows($result)) {
                $obj = $db->fetch_object($result);

                $date_close = $obj->date_c;
            }
        } else {
            $sql = "select min(date_closed) as date_closed";
            $sql .= " from " . MAIN_DB_PREFIX . "pos_ticket";
            $sql .= " where fk_cash=" . $this->terminal;

            $result = $db->query($sql);
            if ($result) {
                if ($db->num_rows($result)) {
                    $obj = $db->fetch_object($result);

                    $date_close = $obj->date_closed;
                }
            }
        }

        return $date_close;
    }

    /**
     *
     * Return the money in cash
     *
     * @param        bool $open money for open or not
     * @return        double        Amount of cash since last closed
     */
    function getMoneyCash($open = false)
    {
        global $db, $conf;

        $cash = new Cash($db);
        $cash->fetch($this->terminal);

        $acount = $cash->fk_paycash;

        $sql = "SELECT sum( b.amount ) as amount";
        $sql .= " FROM " . MAIN_DB_PREFIX . "bank_account AS ba, " . MAIN_DB_PREFIX . "bank AS b";
        $sql .= " WHERE b.fk_account =" . $acount;
        $sql .= " AND b.fk_account = ba.rowid";
        $sql .= " AND ba.entity =" . $conf->entity;
        $sql .= " ORDER BY b.datev ASC , b.datec ASC ";

        $result = $db->query($sql);
        if ($result) {
            if ($db->num_rows($result)) {
                $obj = $db->fetch_object($result);

                $amount = $obj->amount;
            }
        }

        if (!$amount) $amount = 0;
        return $amount;

    }

    function setTicketClosedbyCash($closeid, $date_close)
    {
        global $db;

        $sql = "update " . MAIN_DB_PREFIX . "pos_ticket ";
        $sql .= " set fk_statut= 2,";
        $sql .= " fk_control=" . $closeid;
        $sql .= " where date_closed>'" . $date_close . "'";
        $sql .= " and fk_cash=" . $this->terminal;

        $db->query($sql);

    }

    function setFactureClosedbyCash($closeid)
    {
        global $db;

        $sql = "update " . MAIN_DB_PREFIX . "pos_facture ";
        $sql .= " set fk_control_cash=" . $closeid;
        $sql .= " where fk_control_cash IS NULL";
        $sql .= " and fk_cash=" . $this->terminal;

        $db->query($sql);

    }

    /**
     *    Return label of status (activity, closed)
     * @param      mode 0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
     * @return     string        Libelle
     */
    function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->type_control, $mode);
    }

    /**
     *      Renvoi le libelle d'un statut donne
     * @param      statut          Id statut
     * @param      mode 0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     * @return     string          Libelle du statut
     */
    function LibStatut($statut, $mode = 0)
    {
        global $langs;
        $langs->load('pos');

        if ($mode == 0) {
            if ($statut == 0) return $langs->trans("TypeControl0");
            if ($statut == 1) return $langs->trans("TypeControl1");
            if ($statut == 2) return $langs->trans("TypeControl2");
        }
        if ($mode == 1) {
            if ($statut == 0) return $langs->trans("TypeControl0");
            if ($statut == 1) return $langs->trans("TypeControl1");
            if ($statut == 2) return $langs->trans("TypeControl2");
        }
        if ($mode == 2) {
            if ($statut == 0) return img_picto($langs->trans("TypeControl0"), 'statut4') . ' ' . $langs->trans("TypeControl0");
            if ($statut == 1) return img_picto($langs->trans("TypeControl1"), 'statut8') . ' ' . $langs->trans("TypeControl1");
            if ($statut == 2) return img_picto($langs->trans("TypeControl2"), 'statut2') . ' ' . $langs->trans("TypeControl2");
        }
        if ($mode == 3) {
            if ($statut == 0) return img_picto($langs->trans("TypeControl0"), 'statut4');
            if ($statut == 1) return img_picto($langs->trans("TypeControl1"), 'statut8');
            if ($statut == 2) return img_picto($langs->trans("TypeControl2"), 'statut2');
        }
        if ($mode == 4) {
            if ($statut == 0) return img_picto($langs->trans("TypeControl0"), 'statut4') . ' ' . $langs->trans("TypeControl0");
            if ($statut == 1) return img_picto($langs->trans("TypeControl1"), 'statut8') . ' ' . $langs->trans("TypeControl1");
            if ($statut == 2) return img_picto($langs->trans("TypeControl2"), 'statut2') . ' ' . $langs->trans("TypeControl2");
        }
        if ($mode == 5) {
            if ($statut == 0) return $langs->trans("TypeControl0") . ' ' . img_picto($langs->trans("TypeControl0"), 'statut4');
            if ($statut == 1) return $langs->trans("TypeControl1") . ' ' . img_picto($langs->trans("TypeControl1"), 'statut8');
            if ($statut == 2) return $langs->trans("TypeControl2") . ' ' . img_picto($langs->trans("TypeControl2"), 'statut2');
        }
    }

    /**
     *      Return next reference of ticket not already used (or last reference)
     *      according to numbering module defined into constant CLOSECASH_ADDON
     * @param     object  soc                   objet company
     * @param     string mode 'next' for next value or 'last' for last value
     * @return    string                  free ref or last ref
     */
    function getNextNumRef($soc, $mode = 'next')
    {
        global $conf, $langs;
        $langs->load("bills");

        // Clean parameters (if not defined or using deprecated value)
        if (empty($conf->global->CLOSECASH_ADDON)) $conf->global->CLOSECASH_ADDON = 'mod_closecash_fideua';
        else if ($conf->global->CLOSECASH_ADDON == 'fideua') $conf->global->CLOSECASH_ADDON = 'mod_closecash_fideua';

        $mybool = false;

        $file = $conf->global->CLOSECASH_ADDON . ".php";
        $classname = $conf->global->CLOSECASH_ADDON;
        // Include file with class
        foreach ($conf->file->dol_document_root as $dirroot) {
            $dir = $dirroot . "/pos/backend/numerotation/numerotation_closecash/";
            // Load file with numbering class (if found)
            $mybool |= @include_once($dir . $file);
        }

        // For compatibility
        if (!$mybool) {
            $file = $conf->global->CLOSECASH_ADDON . "/" . $conf->global->CLOSECASH_ADDON . ".modules.php";
            $classname = "mod_closecash_" . $conf->global->CLOSECASH_ADDON;
            // Include file with class
            foreach ($conf->file->dol_document_root as $dirroot) {
                $dir = $dirroot . "/pos/backend/numerotation/numerotation_closecash/";
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
<?php
/* Copyright (C) 2001-2005 	Rodolphe Quiedeville   		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 	Laurent Destailleur   		<eldy@users.sourceforge.net>
 * Copyright (C) 2005      	Marc Barilley / Ocebo  		<marc@ocebo.com>
 * Copyright (C) 2005-2012 	Regis Houssin          		<regis.houssin@capnetworks.com>
 * Copyright (C) 2012	   	Andreu Bisquerra Gaya  		<jove@bisquerra.com>
 * Copyright (C) 2012	   	David Rodriguez Martinez 	<davidrm146@gmail.com>
 * Copyright (C) 2012	   	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2013-2017	Ferran Marcet				<fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU  *General Public License as published by
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
 *  \file       htdocs/massorders/class/massorders.class.php
 *  \brief      Cash Class file
 *  \version    $Id: cash.class.php,v 1.5 2011-08-16 15:36:15 jmenent Exp $
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

/**
 *  \class      Cash
 *  \brief      Class to manage Cash devices
 */

class Massorders extends CommonObject
{
    public $db;
    public $error;
    public $errors = array();
    public $element = 'massorders';
    public $table_element = 'massorders';

    public $socid;
    public $paymode;
    public $date;
    public $orders = array();


    /**
     *    Constructor
     *
     * @param    DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->socid = 0;
        $this->paymode = 0;
        $this->date = 0;
        //$this->orders=0;
        $this->email = 0;
        $this->contactid;

    }

    public function invoicing($socid, $closeOrders, $validateInvoice, $date = '', $paymode = -1)
    {
        require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

        global $conf, $user, $langs;

        $object = new Facture($this->db);
        $societe = new Societe($this->db);
        $societe->fetch($socid);

        if ($paymode == -1) {
            if (empty($societe->mode_reglement_id)) {
                $paymode = $conf->global->MASSO_PAY_MODE;
            } else {
                $paymode = $societe->mode_reglement_id;
            }
        }

        if (empty($societe->cond_reglement_id)) {
            $object->cond_reglement_id = $conf->global->MASSO_PAY_COND;
        } else {
            $object->cond_reglement_id = $societe->cond_reglement_id;
        }

        $date = dol_mktime(0, 0, 0, substr($date, 5, 2), 1, substr($date, 0, 4));
        $date = dol_print_date($date, '%B %Y');

        $ii = 0;
        $nn = count($this->orders);
        $error = 0;

        // Insert new invoice in database
        if ($user->rights->facture->creer) {
            $this->db->begin();

            $datefacture = dol_mktime(date('h'), date('M'), 0, date('m'), date('d'), date('Y'));

            if (!$error) {
                // Si facture standard
                $object->socid = $socid;
                $object->fetch_thirdparty();
                $object->type = 0;
                $object->date = $datefacture;
                $object->datec = $datefacture;
                $object->date_lim_reglement = $object->calculate_date_lim_reglement();
                $object->note_public = trim($_POST['note_public']);
                $object->ref_client = $societe->code_client;
                $object->ref_int = $societe->ref_int;
                $object->modelpdf = $conf->global->FACTURE_ADDON_PDF;
                //$object->cond_reglement_id	= $conf->global->MASSO_PAY_COND;
                $object->mode_reglement_id = $paymode;
                $object->remise_absolue = 0;
                $object->remise_percent = 0;
                $object->fk_user_author = $user->id;

				if(version_compare(DOL_VERSION, "6.0.0") >= 0) {
					$object->linkedObjectsIds['commande'] = $this->orders;
				}
				else{
					$object->origin = 'commande';
					$object->origin_id = $this->orders[$ii];
					$object->linked_objects = $this->orders;
				}

                $id = $object->create($user);

                if ($id > 0) {
					if(!(version_compare(DOL_VERSION, "6.0.0") >= 0)) {
						foreach ((array)$this->orders as $origin => $origin_id) {
							$origin_id = (!empty($origin_id) ? $origin_id : $object->origin_id);
							$this->db->begin();
							$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'element_element (';
							$sql .= 'fk_source';
							$sql .= ', sourcetype';
							$sql .= ', fk_target';
							$sql .= ', targettype';
							$sql .= ') VALUES (';
							$sql .= $origin_id;
							$sql .= ", '" . $object->origin . "'";
							$sql .= ', ' . $id;
							$sql .= ", '" . $object->element . "'";
							$sql .= ')';

							if ($this->db->query($sql)) {
								$this->db->commit();

								//update cond_reglement_id if is necessary
								$commande = new Commande($this->db);
								$commande->fetch($object->origin_id);
								if (!empty($commande->cond_reglement_id)) {
									$object->cond_reglement_id = $commande->cond_reglement_id;
									$object->update($user);
								}

							} else {
								$this->db->rollback();
							}
						}
					}
                    $rang = 1;
                    $listoforders = '';
                    while ($ii < $nn) {
                        dol_include_once('/commande/class/commande.class.php');
                        $srcobject = new Commande($this->db);
                        dol_syslog('Try to find source object origin=' . $object->origin . ' originid=' . $object->origin_id . ' to add lines');
                        $result = $srcobject->fetch($this->orders[$ii]);
                        $listoforders .= ($listoforders ? ', ' : '') . $srcobject->ref;
                        if ($result > 0) {
                            if ($closeOrders) {
                                $srcobject->classifyBilled($user);
                                $srcobject->setStatut(3);
                            }
                            $lines = $srcobject->lines;
                            if (empty($lines) && method_exists($srcobject, 'fetch_lines')) {
                                $srcobject->fetch_lines();
                                $lines = $srcobject->lines;
                            }
                            $fk_parent_line = 0;
                            //Insetar referencia pedido
                            if ($conf->global->MASSO_SHOW_ORIGIN) {
                                $result = $object->addline(
                                //$id,
                                    $langs->trans('Order') . ' ' . $srcobject->ref . ' - ' . dol_print_date($srcobject->date,
                                        'day'),
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    '',
                                    '',
                                    0,
                                    0,
                                    0,
                                    'HT',
                                    0,
                                    9,//product type
                                    $rang,
                                    4,//special code
                                    $object->origin,
                                    $lines[0]->rowid,
                                    $fk_parent_line,
                                    0,
                                    0
                                );
                            }

                            $num = count($lines);
                            for ($i = 0; $i < $num; $i++) {
                                $desc = ($lines[$i]->desc ? $lines[$i]->desc : $lines[$i]->label);
                                if ($lines[$i]->subprice < 0) {
                                    // Negative line, we create a discount line
                                    $discount = new DiscountAbsolute($this->db);
                                    $discount->fk_soc = $object->socid;
                                    $discount->amount_ht = abs($lines[$i]->total_ht);
                                    $discount->amount_tva = abs($lines[$i]->total_tva);
                                    $discount->amount_ttc = abs($lines[$i]->total_ttc);
                                    $discount->tva_tx = $lines[$i]->tva_tx;
                                    $discount->fk_user = $user->id;
                                    $discount->description = $desc;
                                    $discountid = $discount->create($user);
                                    if ($discountid > 0) {
                                        $result = $object->insert_discount($discountid);
                                        //$result=$discount->link_to_invoice($lineid,$id);
                                    } else {
                                        $mesgs[] = $discount->error;
                                        $error++;
                                        break;
                                    }
                                } else {
                                    // Positive line
                                    $product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);
                                    // Date start
                                    $date_start = false;
                                    if ($lines[$i]->date_debut_prevue) {
                                        $date_start = $lines[$i]->date_debut_prevue;
                                    }
                                    if ($lines[$i]->date_debut_reel) {
                                        $date_start = $lines[$i]->date_debut_reel;
                                    }
                                    if ($lines[$i]->date_start) {
                                        $date_start = $lines[$i]->date_start;
                                    }
                                    //Date end
                                    $date_end = false;
                                    if ($lines[$i]->date_fin_prevue) {
                                        $date_end = $lines[$i]->date_fin_prevue;
                                    }
                                    if ($lines[$i]->date_fin_reel) {
                                        $date_end = $lines[$i]->date_fin_reel;
                                    }
                                    if ($lines[$i]->date_end) {
                                        $date_end = $lines[$i]->date_end;
                                    }
                                    // Reset fk_parent_line for no child products and special product
                                    if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
                                        $fk_parent_line = 0;
                                    }
                                    $result = $object->addline(
                                    //$id,
                                        $desc,
                                        $lines[$i]->subprice,
                                        $lines[$i]->qty,
                                        $lines[$i]->tva_tx,
                                        $lines[$i]->localtax1_tx,
                                        $lines[$i]->localtax2_tx,
                                        $lines[$i]->fk_product,
                                        $lines[$i]->remise_percent,
                                        $date_start,
                                        $date_end,
                                        0,
                                        $lines[$i]->info_bits,
                                        $lines[$i]->fk_remise_except,
                                        'HT',
                                        0,
                                        $product_type,
                                        $rang,
                                        $lines[$i]->special_code,
                                        $object->origin,
                                        $lines[$i]->rowid,
                                        $fk_parent_line,
                                        $lines[$i]->fk_fournprice,
                                        $lines[$i]->pa_ht
                                    );
                                    if ($result > 0) {
                                        $lineid = $result;
                                        $rang++;
                                    } else {
                                        $lineid = 0;
                                        $error++;
                                        break;
                                    }
                                    // Defined the new fk_parent_line
                                    if ($result > 0 && $lines[$i]->product_type == 9) {
                                        $fk_parent_line = $result;
                                    }
                                }
                            }
                        } else {
                            $mesgs[] = $srcobject->error;
                            $error++;
                        }
                        $ii++;
                    }
                    $object->note_public = $langs->trans('Orders') . ' ' . $date . ': ' . $listoforders;
                    $object->update($user);
                    if ($validateInvoice) {
                        $result = $object->validate($user);
                        if ($result < 0) {
                            $mesgs[] = $object->error;
                            $error++;
                        }
                        if ($object->mode_reglement_code == 'PRE' && !empty($conf->prelevement->enabled) && $conf->global->MASSO_WITHDRAWAL) {
                            $object->demande_prelevement($user);
                        }
                    }
                } else {
                    $mesgs[] = $object->error;
                    $error++;
                }
            }
        } else {
            $error++;
        }
        // End of object creation, we show it
        if ($id > 0 && !$error) {
            $this->db->commit();
            return $object->id;
        }

        $this->db->rollback();

        return false;

    }

    public function invoicing_supplier($socid, $validateInvoice, $date = '', $paymode = -1)
    {
        //require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
        require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

        global $conf, $user, $langs;

        $object = new FactureFournisseur($this->db);
        $societe = new Societe($this->db);
        $societe->fetch($socid);

        if ($paymode == -1) {
            if (empty($societe->mode_reglement_supplier_id)) {
                $paymode = $conf->global->MASSO_PAY_MODE;
            } else {
                $paymode = $societe->mode_reglement_supplier_id;
            }
        }

        if (empty($societe->cond_reglement_supplier_id)) {
            $object->cond_reglement_id = $conf->global->MASSO_PAY_COND;
        } else {
            $object->cond_reglement_id = $societe->cond_reglement_supplier_id;
        }

        $date = dol_mktime(0, 0, 0, substr($date, 5, 2), 1, substr($date, 0, 4));
        $date = dol_print_date($date, '%B %Y');

        $ii = 0;
        $nn = count($this->orders);
        $error = 0;

        // Insert new invoice in database
        if ($user->rights->fournisseur->facture->creer) {
            $this->db->begin();

            $datefacture = dol_mktime(date('h'), date('M'), 0, date('m'), date('d'), date('Y'));

            if (!$error) {
                // Si facture standard
                $object->socid = $socid;
                $object->fk_soc = $socid;
                $object->fetch_thirdparty();
                $object->entity = $conf->entity;
                //$object->type				= 0;
                $object->date = $datefacture;
                $object->datec = $datefacture;
                $object->date_echeance = $object->calculate_date_lim_reglement();
                $object->note_public = trim($_POST['note_public']);
                $object->ref_supplier = $societe->code_fournisseur . '-' . $this->orders[0];
                //$object->ref_int			= $societe->ref_int;
                //$object->modelpdf			= $conf->global->FACTURE_ADDON_PDF;
                $object->fk_cond_reglement = $object->cond_reglement_id;
                $object->mode_reglement_id = $paymode;
                //$object->remise_absolue		= 0;
                //$object->remise_percent		= 0;
                $object->fk_user_author = $user->id;

				if(version_compare(DOL_VERSION, "6.0.0") >= 0) {
					$object->linkedObjectsIds['order_supplier'] = $this->orders;
				}
				else{
					$object->origin = 'order_supplier';
					$object->origin_id = $this->orders[$ii];
					$object->linked_objects = $this->orders;
				}

                $id = $object->create($user);

                if ($id > 0) {
					if(!(version_compare(DOL_VERSION, "6.0.0") >= 0)) {
						foreach ((array)$this->orders as $origin => $origin_id) {
							$origin_id = (!empty($origin_id) ? $origin_id : $object->origin_id);
							$this->db->begin();
							$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'element_element (';
							$sql .= 'fk_source';
							$sql .= ', sourcetype';
							$sql .= ', fk_target';
							$sql .= ', targettype';
							$sql .= ') VALUES (';
							$sql .= $origin_id;
							$sql .= ", '" . $object->origin . "'";
							$sql .= ', ' . $id;
							$sql .= ", '" . $object->element . "'";
							$sql .= ')';

							if ($this->db->query($sql)) {
								$this->db->commit();
							} else {
								$this->db->rollback();
							}
						}
					}
                    $rang = 1;
                    $listoforders = '';
                    while ($ii < $nn) {
                        dol_include_once('/fourn/class/fournisseur.commande.class.php');
                        $srcobject = new CommandeFournisseur($this->db);
                        dol_syslog('Try to find source object origin=' . $object->origin . ' originid=' . $object->origin_id . ' to add lines');
                        $result = $srcobject->fetch($this->orders[$ii]);
                        $listoforders .= ($listoforders ? ', ' : '') . $srcobject->ref;
                        if ($result > 0) {
                            $lines = $srcobject->lines;
                            if (empty($lines) && method_exists($srcobject, 'fetch_lines')) {
                                $lines = $srcobject->fetch_lines();
                            }
                            $num = count($lines);
                            for ($i = 0; $i < $num; $i++) {
                                $desc = ($lines[$i]->desc ? $lines[$i]->desc : $lines[$i]->label);

                                // Positive line
                                $product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);
                                // Date start
                                $date_start = false;
                                if ($lines[$i]->date_debut_prevue) {
                                    $date_start = $lines[$i]->date_debut_prevue;
                                }
                                if ($lines[$i]->date_debut_reel) {
                                    $date_start = $lines[$i]->date_debut_reel;
                                }
                                if ($lines[$i]->date_start) {
                                    $date_start = $lines[$i]->date_start;
                                }
                                //Date end
                                $date_end = false;
                                if ($lines[$i]->date_fin_prevue) {
                                    $date_end = $lines[$i]->date_fin_prevue;
                                }
                                if ($lines[$i]->date_fin_reel) {
                                    $date_end = $lines[$i]->date_fin_reel;
                                }
                                if ($lines[$i]->date_end) {
                                    $date_end = $lines[$i]->date_end;
                                }

                                $result = $object->addline(
                                //$id,
                                    $desc,
                                    $lines[$i]->subprice,
                                    $lines[$i]->tva_tx,
                                    $lines[$i]->localtax1_tx,
                                    $lines[$i]->localtax2_tx,
                                    $lines[$i]->qty,
                                    $lines[$i]->fk_product,
                                    $lines[$i]->remise_percent,
                                    $date_start,
                                    $date_end,
                                    0,
                                    $lines[$i]->info_bits,
                                    //$lines[$i]->fk_remise_except,
                                    'HT',
                                    //0,
                                    $product_type,
                                    $rang
                                );
                                if ($result > 0) {
                                    $lineid = $result;
                                    $rang++;
                                } else {
                                    $lineid = 0;
                                    $error++;
                                    break;
                                }
                            }
                        } else {
                            $mesgs[] = $srcobject->error;
                            $error++;
                        }
                        $ii++;
                    }
                    $object->note_public = $langs->trans('Orders') . ' ' . $date . ': ' . $listoforders;
                    $object->entity = $conf->entity;
                    $object->update($user);
                    if ($validateInvoice) {
                        $result = $object->validate($user);
                        if ($result < 0) {
                            $mesgs[] = $object->error;
                            $error++;
                        }
                    }
                } else {
                    $mesgs[] = $object->error;
                    $error++;
                }
            }
        } else {
            $error++;
        }
        // End of object creation, we show it
        if ($id > 0 && !$error) {
            $this->db->commit();
            return $object->id;
        }


        $this->db->rollback();

        return false;

    }

    public function invoicing_propals($socid, $closeOrders, $validateInvoice, $date = '', $paymode = -1)
    {
        require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

        global $conf, $user, $langs;

        $object = new Facture($this->db);
        $societe = new Societe($this->db);
        $societe->fetch($socid);

        if ($paymode == -1) {
            if (empty($societe->mode_reglement_id)) {
                $paymode = $conf->global->MASSO_PAY_MODE;
            } else {
                $paymode = $societe->mode_reglement_id;
            }
        }

        if (empty($societe->cond_reglement_id)) {
            $object->cond_reglement_id = $conf->global->MASSO_PAY_COND;
        } else {
            $object->cond_reglement_id = $societe->cond_reglement_id;
        }

        $date = dol_mktime(0, 0, 0, substr($date, 5, 2), 1, substr($date, 0, 4));
        $date = dol_print_date($date, '%B %Y');

        $ii = 0;
        $nn = count($this->orders);
        $error = 0;

        // Insert new invoice in database
        if ($user->rights->facture->creer) {
            $this->db->begin();

            $datefacture = dol_mktime(date('h'), date('M'), 0, date('m'), date('d'), date('Y'));

            if (!$error) {
                // Si facture standard
                $object->socid = $socid;
                $object->fetch_thirdparty();
                $object->type = 0;
                $object->date = $datefacture;
                $object->datec = $datefacture;
                $object->date_lim_reglement = $object->calculate_date_lim_reglement();
                $object->note_public = trim($_POST['note_public']);
                $object->ref_client = $societe->code_client;
                $object->ref_int = $societe->ref_int;
                $object->modelpdf = $conf->global->FACTURE_ADDON_PDF;
                //$object->cond_reglement_id	= $conf->global->MASSO_PAY_COND;
                $object->mode_reglement_id = $paymode;
                $object->remise_absolue = 0;
                $object->remise_percent = 0;
                $object->fk_user_author = $user->id;

				if(version_compare(DOL_VERSION, "6.0.0") >= 0) {
					$object->linkedObjectsIds['propal'] = $this->orders;
				}
				else{
					$object->origin = 'propal';
					$object->origin_id = $this->orders[$ii];
					//$object->linked_objects = $this->orders;
				}

                $id = $object->create($user);

                if ($id > 0) {
					if(!(version_compare(DOL_VERSION, "6.0.0") >= 0)) {
						foreach ((array)$this->orders as $origin => $origin_id) {
							$origin_id = (!empty($origin_id) ? $origin_id : $object->origin_id);
							$this->db->begin();
							$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'element_element (';
							$sql .= 'fk_source';
							$sql .= ', sourcetype';
							$sql .= ', fk_target';
							$sql .= ', targettype';
							$sql .= ') VALUES (';
							$sql .= $origin_id;
							$sql .= ", '" . $object->origin . "'";
							$sql .= ', ' . $id;
							$sql .= ", '" . $object->element . "'";
							$sql .= ')';

							if ($this->db->query($sql)) {
								$this->db->commit();
							} else {
								$this->db->rollback();
							}
						}
					}
                    $rang = 1;
                    $listoforders = '';
                    while ($ii < $nn) {
                        dol_include_once('/commande/class/commande.class.php');
                        $srcobject = new Propal($this->db);
                        dol_syslog('Try to find source object origin=' . $object->origin . ' originid=' . $object->origin_id . ' to add lines');
                        $result = $srcobject->fetch($this->orders[$ii]);
                        $listoforders .= ($listoforders ? ', ' : '') . $srcobject->ref;
                        if ($result > 0) {
                            if ($closeOrders) {
                                $srcobject->classifyBilled($user);
                                $srcobject->setStatut(4);
                            }
                            $lines = $srcobject->lines;
                            if (empty($lines) && method_exists($srcobject, 'fetch_lines')) {
                                $srcobject->fetch_lines();
                                $lines = $srcobject->lines;
                            }
                            $fk_parent_line = 0;

                            //Insetar referencia pedido
                            if ($conf->global->MASSO_SHOW_ORIGIN) {
                                $result = $object->addline(
                                //$id,
                                    $langs->trans('Proposal') . ' ' . $srcobject->ref . ' - ' . dol_print_date($srcobject->date,
                                        'day'),
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    '',
                                    '',
                                    0,
                                    0,
                                    0,
                                    'HT',
                                    0,
                                    9,//product type
                                    $rang,
                                    4,//special code
                                    $object->origin,
                                    $lines[0]->rowid,
                                    $fk_parent_line,
                                    0,
                                    0
                                );
                            }

                            $num = count($lines);
                            for ($i = 0; $i < $num; $i++) {
                                $desc = ($lines[$i]->desc ? $lines[$i]->desc : $lines[$i]->label);
                                if ($lines[$i]->subprice < 0) {
                                    // Negative line, we create a discount line
                                    $discount = new DiscountAbsolute($this->db);
                                    $discount->fk_soc = $object->socid;
                                    $discount->amount_ht = abs($lines[$i]->total_ht);
                                    $discount->amount_tva = abs($lines[$i]->total_tva);
                                    $discount->amount_ttc = abs($lines[$i]->total_ttc);
                                    $discount->tva_tx = $lines[$i]->tva_tx;
                                    $discount->fk_user = $user->id;
                                    $discount->description = $desc;
                                    $discountid = $discount->create($user);
                                    if ($discountid > 0) {
                                        $result = $object->insert_discount($discountid);
                                        //$result=$discount->link_to_invoice($lineid,$id);
                                    } else {
                                        $mesgs[] = $discount->error;
                                        $error++;
                                        break;
                                    }
                                } else {
                                    // Positive line
                                    $product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);
                                    // Date start
                                    $date_start = false;
                                    if ($lines[$i]->date_debut_prevue) {
                                        $date_start = $lines[$i]->date_debut_prevue;
                                    }
                                    if ($lines[$i]->date_debut_reel) {
                                        $date_start = $lines[$i]->date_debut_reel;
                                    }
                                    if ($lines[$i]->date_start) {
                                        $date_start = $lines[$i]->date_start;
                                    }
                                    //Date end
                                    $date_end = false;
                                    if ($lines[$i]->date_fin_prevue) {
                                        $date_end = $lines[$i]->date_fin_prevue;
                                    }
                                    if ($lines[$i]->date_fin_reel) {
                                        $date_end = $lines[$i]->date_fin_reel;
                                    }
                                    if ($lines[$i]->date_end) {
                                        $date_end = $lines[$i]->date_end;
                                    }
                                    // Reset fk_parent_line for no child products and special product
                                    if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
                                        $fk_parent_line = 0;
                                    }
                                    $result = $object->addline(
                                    //$id,
                                        $desc,
                                        $lines[$i]->subprice,
                                        $lines[$i]->qty,
                                        $lines[$i]->tva_tx,
                                        $lines[$i]->localtax1_tx,
                                        $lines[$i]->localtax2_tx,
                                        $lines[$i]->fk_product,
                                        $lines[$i]->remise_percent,
                                        $date_start,
                                        $date_end,
                                        0,
                                        $lines[$i]->info_bits,
                                        $lines[$i]->fk_remise_except,
                                        'HT',
                                        0,
                                        $product_type,
                                        $rang,
                                        $lines[$i]->special_code,
                                        $object->origin,
                                        $lines[$i]->rowid,
                                        $fk_parent_line,
                                        $lines[$i]->fk_fournprice,
                                        $lines[$i]->pa_ht
                                    );
                                    if ($result > 0) {
                                        $lineid = $result;
                                        $rang++;
                                    } else {
                                        $lineid = 0;
                                        $error++;
                                        break;
                                    }
                                    // Defined the new fk_parent_line
                                    if ($result > 0 && $lines[$i]->product_type == 9) {
                                        $fk_parent_line = $result;
                                    }
                                }
                            }
                        } else {
                            $mesgs[] = $srcobject->error;
                            $error++;
                        }
                        $ii++;
                    }
                    $object->note_public = $langs->trans('Propals') . ' ' . $date . ': ' . $listoforders;
                    $object->update($user);
                    if ($validateInvoice) {
                        $result = $object->validate($user);
                        if ($result < 0) {
                            $mesgs[] = $object->error;
                            $error++;
                        }
                        if ($object->mode_reglement_code == 'PRE' && !empty($conf->prelevement->enabled) && $conf->global->MASSO_WITHDRAWAL) {
                            $object->demande_prelevement($user);
                        }
                    }
                } else {
                    $mesgs[] = $object->error;
                    $error++;
                }
            }
        } else {
            $error++;
        }
        // End of object creation, we show it
        if ($id > 0 && !$error) {
            $this->db->commit();
            return $object->id;
        }


        $this->db->rollback();

        return false;

    }

    public function invoicing_shipment($socid, $closeOrders, $validateInvoice, $date = '', $paymode = -1)
    {
        require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

        global $conf, $user, $langs;

        $object = new Facture($this->db);
        $societe = new Societe($this->db);
        $societe->fetch($socid);

        if ($paymode == -1) {
            if (empty($societe->mode_reglement_id)) {
                $paymode = $conf->global->MASSO_PAY_MODE;
            } else {
                $paymode = $societe->mode_reglement_id;
            }
        }

        if (empty($societe->cond_reglement_id)) {
            $object->cond_reglement_id = $conf->global->MASSO_PAY_COND;
        } else {
            $object->cond_reglement_id = $societe->cond_reglement_id;
        }


        $date = dol_mktime(0, 0, 0, substr($date, 5, 2), 1, substr($date, 0, 4));
        $date = dol_print_date($date, '%B %Y');

        $ii = 0;
        $nn = count($this->orders);
        $error = 0;

        // Insert new invoice in database
        if ($user->rights->facture->creer) {
            $this->db->begin();

            $datefacture = dol_mktime(date('h'), date('M'), 0, date('m'), date('d'), date('Y'));

            if (!$error) {
                // Si facture standard
                $object->socid = $socid;
                $object->fetch_thirdparty();
                $object->type = 0;
                $object->date = $datefacture;
                $object->datec = $datefacture;
                $object->date_lim_reglement = $object->calculate_date_lim_reglement();
                $object->note_public = trim($_POST['note_public']);
                $object->ref_client = $societe->code_client;
                $object->ref_int = $societe->ref_int;
                $object->modelpdf = $conf->global->FACTURE_ADDON_PDF;
                //$object->cond_reglement_id	= $conf->global->MASSO_PAY_COND;
                $object->mode_reglement_id = $paymode;
                $object->remise_absolue = 0;
                $object->remise_percent = 0;
                $object->fk_user_author = $user->id;

				if(version_compare(DOL_VERSION, "6.0.0") >= 0) {
					$object->linkedObjectsIds['shipping'] = $this->orders;
				}
				else{
					$object->origin = 'shipping';
					$object->origin_id = $this->orders[$ii];
					$object->linked_objects = $this->orders;
				}

                $id = $object->create($user);

                if ($id > 0) {
					if(!(version_compare(DOL_VERSION, "6.0.0") >= 0)) {
						foreach ((array)$this->orders as $origin => $origin_id) {
							$origin_id = (!empty($origin_id) ? $origin_id : $object->origin_id);
							$this->db->begin();
							$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'element_element (';
							$sql .= 'fk_source';
							$sql .= ', sourcetype';
							$sql .= ', fk_target';
							$sql .= ', targettype';
							$sql .= ') VALUES (';
							$sql .= $origin_id;
							$sql .= ", '" . $object->origin . "'";
							$sql .= ', ' . $id;
							$sql .= ", '" . $object->element . "'";
							$sql .= ')';

							if ($this->db->query($sql)) {
								$this->db->commit();
							} else {
								$this->db->rollback();
							}
						}
					}
                    $rang = 1;
                    $listoforders = '';
                    while ($ii < $nn) {
                        dol_include_once('/expedition/class/expedition.class.php');
                        $srcobject = new Expedition($this->db);
                        dol_syslog('Try to find source object origin=' . $object->origin . ' originid=' . $object->origin_id . ' to add lines');
                        $result = $srcobject->fetch($this->orders[$ii]);
                        $srcobject->fetchObjectLinked();
                        if (count($srcobject->linkedObjectsIds['commande'][0]) > 0) {
                            foreach ($srcobject->linkedObjectsIds['commande'] as $origin => $origin_id) {
                                $origin_id = (!empty($origin_id) ? $origin_id : $object->origin_id);
                                $this->db->begin();
                                $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'element_element (';
                                $sql .= 'fk_source';
                                $sql .= ', sourcetype';
                                $sql .= ', fk_target';
                                $sql .= ', targettype';
                                $sql .= ') VALUES (';
                                $sql .= $origin_id;
                                $sql .= ", 'commande'";
                                $sql .= ', ' . $id;
                                $sql .= ", '" . $object->element . "'";
                                $sql .= ')';

                                if ($this->db->query($sql)) {
                                    $this->db->commit();
                                } else {
                                    $this->db->rollback();
                                }
                            }
                        }
                        $listoforders .= ($listoforders ? ', ' : '') . $srcobject->ref;
                        if ($result > 0) {
                            if ($closeOrders) {
                                $srcobject->set_billed();
                                $srcobject->setStatut(2);
                            }
                            $lines = $srcobject->lines;
                            if (empty($lines) && method_exists($srcobject, 'fetch_lines')) {
                                $srcobject->fetch_lines();
                                $lines = $srcobject->lines;
                            }
                            $fk_parent_line = 0;
                            //Insetar referencia pedido
                            if ($conf->global->MASSO_SHOW_ORIGIN) {
                                $result = $object->addline(
                                //$id,
                                    $langs->trans('Sending') . ' ' . $srcobject->ref . ' - ' . dol_print_date($srcobject->date_shipping,
                                        'day'),
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    '',
                                    '',
                                    0,
                                    0,
                                    0,
                                    'HT',
                                    0,
                                    9,//product type
                                    $rang,
                                    4,//special code
                                    $object->origin,
                                    $lines[0]->rowid,
                                    $fk_parent_line,
                                    0,
                                    0
                                );
                            }

                            $num = count($lines);
                            for ($i = 0; $i < $num; $i++) {
                                $desc = ($lines[$i]->desc ? $lines[$i]->desc : $lines[$i]->label);
                                if ($lines[$i]->subprice < 0) {
                                    // Negative line, we create a discount line
                                    $discount = new DiscountAbsolute($this->db);
                                    $discount->fk_soc = $object->socid;
                                    $discount->amount_ht = abs($lines[$i]->total_ht);
                                    $discount->amount_tva = abs($lines[$i]->total_tva);
                                    $discount->amount_ttc = abs($lines[$i]->total_ttc);
                                    $discount->tva_tx = $lines[$i]->tva_tx;
                                    $discount->fk_user = $user->id;
                                    $discount->description = $desc;
                                    $discountid = $discount->create($user);
                                    if ($discountid > 0) {
                                        $result = $object->insert_discount($discountid);
                                        //$result=$discount->link_to_invoice($lineid,$id);
                                    } else {
                                        $mesgs[] = $discount->error;
                                        $error++;
                                        break;
                                    }
                                } else {
                                    // Positive line
                                    $product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);
                                    // Date start
                                    $date_start = false;
                                    if ($lines[$i]->date_debut_prevue) {
                                        $date_start = $lines[$i]->date_debut_prevue;
                                    }
                                    if ($lines[$i]->date_debut_reel) {
                                        $date_start = $lines[$i]->date_debut_reel;
                                    }
                                    if ($lines[$i]->date_start) {
                                        $date_start = $lines[$i]->date_start;
                                    }
                                    //Date end
                                    $date_end = false;
                                    if ($lines[$i]->date_fin_prevue) {
                                        $date_end = $lines[$i]->date_fin_prevue;
                                    }
                                    if ($lines[$i]->date_fin_reel) {
                                        $date_end = $lines[$i]->date_fin_reel;
                                    }
                                    if ($lines[$i]->date_end) {
                                        $date_end = $lines[$i]->date_end;
                                    }
                                    // Reset fk_parent_line for no child products and special product
                                    if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
                                        $fk_parent_line = 0;
                                    }
                                    $result = $object->addline(
                                    //$id,
                                        $desc,
                                        $lines[$i]->subprice,
                                        $lines[$i]->qty,
                                        $lines[$i]->tva_tx,
                                        $lines[$i]->localtax1_tx,
                                        $lines[$i]->localtax2_tx,
                                        $lines[$i]->fk_product,
                                        $lines[$i]->remise_percent,
                                        $date_start,
                                        $date_end,
                                        0,
                                        $lines[$i]->info_bits,
                                        $lines[$i]->fk_remise_except,
                                        'HT',
                                        0,
                                        $product_type,
                                        $rang,
                                        $lines[$i]->special_code,
                                        $object->origin,
                                        $lines[$i]->rowid,
                                        $fk_parent_line,
                                        $lines[$i]->fk_fournprice,
                                        $lines[$i]->pa_ht
                                    );
                                    if ($result > 0) {
                                        $lineid = $result;
                                        $rang++;
                                    } else {
                                        $lineid = 0;
                                        $error++;
                                        break;
                                    }
                                    // Defined the new fk_parent_line
                                    if ($result > 0 && $lines[$i]->product_type == 9) {
                                        $fk_parent_line = $result;
                                    }
                                }
                            }
                        } else {
                            $mesgs[] = $srcobject->error;
                            $error++;
                        }
                        $ii++;
                    }
                    $object->note_public = $langs->trans('Sendings') . ' ' . $date . ': ' . $listoforders;
                    $object->update($user);
                    if ($validateInvoice) {
                        $result = $object->validate($user);
                        if ($result < 0) {
                            $mesgs[] = $object->error;
                            $error++;
                        }
                        if ($object->mode_reglement_code == 'PRE' && !empty($conf->prelevement->enabled) && $conf->global->MASSO_WITHDRAWAL) {
                            $object->demande_prelevement($user);
                        }
                    }
                } else {
                    $mesgs[] = $object->error;
                    $error++;
                }
            }
        } else {
            $error++;
        }

        // End of object creation, we show it
        if ($id > 0 && !$error) {
            $this->db->commit();
            return $object->id;
        }


        $this->db->rollback();

        return false;

    }

    /**
     *
     * @param integer $type 100=customer order, 142=supplier order, 40=proposal
     * @return >0 OK, <0 KO
     */
    public function search_mail($type)
    {
        $this->db->begin();
        $sql = 'SELECT sp.rowid, sp.email FROM ' . MAIN_DB_PREFIX . 'socpeople as sp, ' . MAIN_DB_PREFIX . 'element_contact as ec';
        $sql .= ' WHERE ec.fk_c_type_contact = ' . $type . ' AND ec.fk_socpeople = sp.rowid AND (';
        $flag = 0;
        foreach ((array)$this->orders as $origin => $origin_id) {
            //$origin_id = (! empty($origin_id) ? $origin_id : $object->origin_id);
            if ($flag == 0) {
                $sql .= ' ec.element_id = ' . $origin_id;
                $flag = 1;
            } else {
                $sql .= ' OR ec.element_id = ' . $origin_id;
            }
        }
        $sql .= ')';
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);

                if (!empty($obj->email)) {
                    $this->db->commit();
                    $this->email = $obj->email;
                    $this->contactid = $obj->rowid;
                    return 1;
                }

                $i++;
            }
            $soc = new Societe($this->db);
            $soc->fetch($this->socid);
            $this->db->commit();
            $this->email = $soc->email;
            $this->contactid = 0;
            return 1;
        }

        $this->error = $this->db->error();
        dol_print_error($this->db);
        $this->db->rollback();
        return -1;

    }

    /**
     *
     * @param object $fac
     * @param int $mode 1=customer facture, 2=supplier facture
     * @return >0 OK, <0 KO
     */
    public function send_mail($fac, $mode)
    {
        global $user, $langs, $conf, $hookmanager;

        $langs->load('commercial');
        $langs->load('mails');
        $langs->load('other');

        $ref = dol_sanitizeFileName($fac->ref);
        include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        if ($mode == 1) {
            $fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $ref, preg_quote($ref, '/'));
        } else {
            $fileparams = dol_most_recent_file($conf->fournisseur->facture->dir_output . '/' . get_exdir($fac->id, 2, 0,
                    0, $fac, 'supplier_invoice') . $ref, preg_quote($ref, '/'));
        }
        $file = $fileparams['fullname'];

        include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
        $formmail = new FormMail($this->db);

        // Tableau des substitutions
        $formmail->substit['__FACREF__'] = $fac->ref;
        $formmail->substit['__REF__'] = $fac->ref;
        $formmail->substit['__SIGNATURE__'] = $user->signature;
        $formmail->substit['__REFCLIENT__'] = $fac->ref_client;
        $formmail->substit['__PERSONALIZED__'] = '';
        $formmail->substit['__CONTACTCIVNAME__'] = '';

        if (!empty($this->contactid)) {
            require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
            $contactstatic = new Contact($this->db);
            $contactstatic->fetch($this->contactid);
            $custcontact = $contactstatic->getFullName($langs, 1);

            if (!empty($custcontact)) {
                $formmail->substit['__CONTACTCIVNAME__'] = $custcontact;
            }
        }
        if ($mode == 1) {
            $message = $langs->transnoentities('PredefinedMailContentSendInvoice');
        } else {
            $message = $langs->transnoentities('PredefinedMailContentSendSupplierInvoice');
        }

        $message = str_replace('\n', "\n", $message);

        // Deal with format differences between message and signature (text / HTML)
        if (dol_textishtml($message) && !dol_textishtml($formmail->substit['__SIGNATURE__'])) {
            $formmail->substit['__SIGNATURE__'] = dol_nl2br($formmail->substit['__SIGNATURE__']);
        } else {
            if (!dol_textishtml($message) && dol_textishtml($formmail->substit['__SIGNATURE__'])) {
                $message = dol_nl2br($message);
            }
        }

        if (!empty($conf->paypal->enabled) && !empty($conf->global->PAYPAL_ADD_PAYMENT_URL) && $mode == 1) {
            require_once DOL_DOCUMENT_ROOT . '/paypal/lib/paypal.lib.php';

            $langs->load('paypal');

            $url = getPaypalPaymentUrl(0, 'invoice', $ref);
            $formmail->substit['__PERSONALIZED__'] = str_replace('\n', "\n",
                $langs->transnoentitiesnoconv('PredefinedMailContentLink', $url));
        }

        $message = make_substitutions($message, $formmail->substit);


        $subject = make_substitutions($langs->transnoentities('SendBillRef', '__FACREF__'), $formmail->substit);
        $sendto = $this->email;

        $formmail->clear_attached_files();
        $formmail->add_attached_files($file, basename($file), dol_mimetype($file));

        $from = $user->email;
        $replyto = '';
        $sendtocc = '';
        $deliveryreceipt = '';

        $attachedfiles = $formmail->get_attached_files();
        $filepath = $attachedfiles['paths'];
        $filename = $attachedfiles['names'];
        $mimetype = $attachedfiles['mimes'];

        $actiontypecode = 'AC_EMAIL';

        $actionmsg = $langs->transnoentities('MailSentBy') . ' ' . $from . ' ' . $langs->transnoentities('To') . ' ' . $sendto . ".\n";
        if ($message) {
            $actionmsg .= $langs->transnoentities('MailTopic') . ': ' . $subject . "\n";
            $actionmsg .= $langs->transnoentities('TextUsedInTheMessageBody') . ":\n";
            $actionmsg .= $message;
        }
        $actionmsg2 = $langs->transnoentities('Action' . $actiontypecode);

        //2Webmail compatibility
        if ($conf->webmail->enabled) {
            $action = 'send';

            $_POST['receiver'] = 'thirdparty';
            $_POST['socid'] = $fac->socid;
            $_POST['sendto'] = $sendto;
            $_POST['subject'] = $subject;
            $_POST['fromname'] = $user->firstname . ' ' . $user->lastname;
            $_POST['frommail'] = $user->email;
            $_POST['message'] = $message;
            $_POST['sendmail'] = 1;
            $_POST['receivercc'] = -1;

            $action = 'send';
            $hookmanager->initHooks(array('massorders_autofac'));
            $reshook = $hookmanager->executeHooks('doActions', '', $fac,
                $action);    // Note that $action and $object may have been modified by some hooks

            if ($reshook < 0) {
                return -1;
            }

            return 1;


        } else {
            // Send mail
            require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
            $mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc,
                '', $deliveryreceipt, -1);
            if ($mailfile->error) {
                setEventMessage($mailfile->error, 'errors');
            } else {
                $result = $mailfile->sendfile();
                if ($result) {
                    $error = 0;

                    // Initialisation donnees
                    $fac->sendtoid = $this->contactid;
                    $fac->actiontypecode = $actiontypecode;
                    $fac->actionmsg = $actionmsg;  // Long text
                    $fac->actionmsg2 = $actionmsg2; // Short text
                    $fac->fk_element = $fac->id;
                    $fac->elementtype = $fac->element;

                    // Appel des triggers
                    include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                    $interface = new Interfaces($this->db);
                    if ($mode == 1) {
                        $result = $interface->run_triggers('BILL_SENTBYMAIL', $fac, $user, $langs, $conf);
                    } else {
                        $result = $interface->run_triggers('BILL_SUPPLIER_SENTBYMAIL', $fac, $user, $langs, $conf);
                    }
                    if ($result < 0) {
                        $error++;
                        $this->errors = $interface->errors;
                    }
                    // Fin appel triggers

                    if ($error) {
                        dol_print_error($this->db);
                        return -1;
                    }

                    // Redirect here
                    // This avoid sending mail twice if going out and then back to page
                    $mesg = $langs->trans('MailSuccessfulySent', CMailFile::getValidAddress($from, 2),
                        CMailFile::getValidAddress($sendto, 2));
                    setEventMessage($mesg);
                    return 1;

                } else {
                    if ($mailfile->error) {
                        setEventMessage($langs->trans('ErrorFailedToSendMail', $from,
                                $sendto) . '<br>' . $mailfile->error,
                            'errors');
                    } else {
                        setEventMessage('No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS',
                            'warnings');
                    }
                    return -1;
                }
            }
        }
        return 0;
    }
}
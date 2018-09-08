<?php
/* Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013-2017  Ferran Marcet        <fmarcet@2byte.es>
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
 *      \file       labelprint/class/labelprint.class.php
 *      \ingroup    labelprint
 *      \brief      Class for labelprint
 *		\author		Juanjo Menent
 */


/**
 *      \class      Labelprint
 *      \brief      Put here description of your class
 */
class LabelsProducts
{
	public $db;                            //!< To store db handler
	public $error;                            //!< To return error code (or message)
	public $errors = array();                //!< To return several error codes (or messages)

	public $id;

	public $type = 0;

	public $entity;
	public $fk_object;
	public $qty;
	public $fk_user;
	public $datec = '';
	public $price_level;
    public $batch;


	/**
	 *      Constructor
	 * @param      doliDB $DB Database handler
	 */
	public function __construct($DB)
	{
		$this->db = $DB;
	}


	/**
	 *      Create object into database
	 * @param      User $user      User that create
	 * @return     int                    <0 if KO, Id of created object if OK
	 */
	public function create($user)
	{
		global $conf;
		$error = 0;

		// Clean parameters
		$qty = $this->qty;

		$res = $this->fetch($this->id, $this->fk_object);
		if ($res == 1) {
			$qty = $this->qty + $qty;
			$this->qty = $qty;
			$res = $this->update();
			return $res;
		}

		if (isset($this->fk_object)) {
			$this->fk_object = trim($this->fk_object);
		}
		if (isset($this->qty)) {
			$this->qty = trim($this->qty);
		}
		if(isset($this->batch)){
		    $this->batch = trim($this->batch);
        }

		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "labelprint(";

		$sql .= "entity,";
		$sql .= " typLabel,";
		$sql .= " fk_object,";
		$sql .= " qty,";
		$sql .= " fk_user,";
		$sql .= " datec,";
		$sql .= " price_level,";
        $sql .= 'batch';

		$sql .= ") VALUES (";

		$sql .= " " . $conf->entity . ",";
		$sql .= " '" . $this->type . "',";
		$sql .= " " . (!isset($this->fk_object) ? 'NULL' : "'" . $this->fk_object . "'") . ",";
		$sql .= " " . (!isset($this->qty) ? 'NULL' : "'" . $this->qty . "'") . ",";
		$sql .= " " . $user->id . ",";
		$sql .= " " . (!isset($this->datec) || dol_strlen($this->datec) == 0 ? 'NULL' : "'".$this->db->idate($this->datec)."'") . ",";
		$sql .= " " . (!isset($this->price_level) ? 1 : $this->price_level) . ",";
        $sql .= ' "' . $this->batch . '"';

		$sql .= ")";

		$this->db->begin();

		dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "labelprint");

		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(__METHOD__ . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 *      Create object into database
	 * @param      User  $user    User that create
	 * @param      array $toPrint Array with products to print
	 * @return     int                    <0 if KO, Id of created object if OK
	 */
	public function multicreate($user, $toPrint)
	{
		global $conf;
		$error = 0;
		require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
		$product = new Product($this->db);
        $qty = 0;
		foreach ($toPrint as $prodid) {

			$result = $product->fetch($prodid);
			if ($result) {
				if ($conf->stock->enabled) {
					$product->load_stock();
					$qty = $product->stock_reel;
				}

			}


			$res = $this->fetch($this->id, $product->id);
			if ($res == 1) {
				$qty = $this->qty + $qty;
				$this->qty = $qty;
				$res = $this->update();
				if ($res != 1) {
					$error++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}
			} else {
				// Insert request
				$sql = "INSERT INTO " . MAIN_DB_PREFIX . "labelprint(";

				$sql .= "entity,";
				$sql .= "typLabel,";
				$sql .= "fk_object,";
				$sql .= "qty,";
				$sql .= "fk_user,";
				$sql .= "datec,";
				$sql .= "price_level";

				$sql .= ") VALUES (";

				$sql .= " " . $conf->entity . ",";
				$sql .= " '" . $this->type . "',";
				$sql .= " " . $product->id . ",";
				$sql .= " " . $qty . ",";
				$sql .= " " . $user->id . ",";
				$sql .= " " . (!isset($this->datec) || dol_strlen($this->datec) == 0 ? 'NULL' : "'".$this->db->idate($this->datec)."'") . ",";
				$sql .= " " . (!isset($this->price_level) ? 1 : $this->price_level) . "";

				$sql .= ")";

				//$this->db->begin();

				dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}
			}

		}
		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "labelprint");

		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(__METHOD__ . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			//$this->db->rollback();
			return -1 * $error;
		} else {
			//$this->db->commit();
			return $this->id;
		}
	}


	/**
	 *    Load object in memory from database
	 * @param      int $id        id row
	 * @param      int $fk_object id object
	 * @return     int                 <0 if KO, >0 if OK
	 */
	public function fetch($id, $fk_object = 0)
	{
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.entity,";
		$sql .= " t.fk_object,";
		$sql .= " t.qty,";
		$sql .= " t.fk_user,";
		$sql .= " t.datec,";
		$sql .= " t.price_level,";
        $sql .= 'batch';


		$sql .= " FROM " . MAIN_DB_PREFIX . "labelprint as t";

		if ($fk_object) {
			$sql .= " WHERE t.fk_object = " . $fk_object;
            if (empty($this->batch)){
				$sql .= " AND batch IS NULL ";
			}
			else {
				$sql .= " AND batch = ".$this->batch;
			}

		} else {
			$sql .= " WHERE t.rowid = " . $id;
		}

		$sql .= " AND typLabel='0'";

		dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->entity = $obj->entity;
				$this->fk_object = $obj->fk_object;
				$this->qty = $obj->qty;
				$this->fk_user = $obj->fk_user;
				$this->datec = $this->db->jdate($obj->datec);
				$this->price_level = $obj->price_level;
                $this->batch = $obj->batch;

				$this->db->free($resql);

				return 1;
			} else {
				$this->db->free($resql);

				return -1;
			}

		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(__METHOD__ . $this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *      Update object into database
	 *
	 * @return     int                    <0 if KO, >0 if OK
	 */
	public function update()
	{
		$error = 0;

		// Clean parameters

		if (isset($this->entity)) {
			$this->entity = trim($this->entity);
		}
		if (isset($this->fk_object)) {
			$this->fk_object = trim($this->fk_object);
		}
		if (isset($this->qty)) {
			$this->qty = trim($this->qty);
		}
		if (isset($this->fk_user)) {
			$this->fk_user = trim($this->fk_user);
		}
		if (isset($this->price_level)) {
			$this->price_level = trim($this->price_level);
		}

		// Check parameters

		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "labelprint SET";

		$sql .= " entity=" . (isset($this->entity) ? $this->entity : "null") . ",";
		$sql .= " typLabel='0',";
		$sql .= " fk_object=" . (isset($this->fk_object) ? $this->fk_object : "null") . ",";
		$sql .= " qty=" . (isset($this->qty) ? $this->qty : "null") . ",";
		$sql .= " fk_user=" . (isset($this->fk_user) ? $this->fk_user : "null") . ",";
		$sql .= " price_level=" . (isset($this->price_level) ? $this->price_level : 1) . ",";
		$sql .= " datec=" . (dol_strlen($this->datec) != 0 ? "'" . $this->db->idate($this->datec) . "'" : 'null');
        $sql .= (isset($this->batch) ? ' , batch="'.trim($this->batch).'"' : '');

		$sql .= " WHERE rowid=" . $this->id;

		$this->db->begin();

		dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(__METHOD__ . $errmsg, LOG_ERR);
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
	 *   Delete object in database
	 * @return    int                        <0 if KO, >0 if OK
	 */
	public function truncate()
    {
		$error = 0;

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "labelprint";
		$sql .= " WHERE typLabel  ='0'";
		$this->db->begin();

		dol_syslog(__METHOD__ . " sql=" . $sql);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(__METHOD__ . $errmsg, LOG_ERR);
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
	 *   Delete object in database
	 *
	 * @param     int $line      User that delete
	 * @return    int                        <0 if KO, >0 if OK
	 */
	public function delete($line)
	{
		$error = 0;

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "labelprint";
		$sql .= " WHERE rowid=" . $line;
		$this->db->begin();

		dol_syslog(get_class($this) . "::delete sql=" . $sql);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
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
	 *  Load an object from its id and create a new one in database
	 *
	 * @param      int $fromid Id of object to clone
	 * @return        int                            New id of clone
	 */
	public function createFromClone($fromid)
	{
		global $user;

		$error = 0;

		$object = new LabelsProducts($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id = 0;

		// Create clone
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$this->error = $object->error;
			$error++;
		}

		// End
		if (!$error) {
			$this->db->commit();
			return $object->id;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Initialise object with example values
	 *  Id must be 0 if object instance is a specimen.
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;

		$this->entity = '';
		$this->type = 0;
		$this->fk_object = '';
		$this->qty = '';
		$this->fk_user = '';
		$this->datec = '';
		$this->price_level = '';
        $this->batch = '';
	}


	/**
	 *   Encode EAN
	 *
	 * @param    int $id_prod Code
	 * @return    boolean                array('encoding': the encoding which has been used, 'bars': the bars, 'text': text-positioning info)
	 */
	public function generate_barcode($id_prod)
	{
		require_once(DOL_DOCUMENT_ROOT . "/core/lib/barcode.lib.php");

		global $conf, $db;
		$loop = true;
		$res = 0;
		while ($loop) {
			if (!empty($conf->barcode->enabled) && !empty($conf->global->BARCODE_PRODUCT_ADDON_NUM)) {
				$module = strtolower($conf->global->BARCODE_PRODUCT_ADDON_NUM);
				$dirbarcode = array_merge(array('/core/modules/barcode/'), $conf->modules_parts['barcode']);
				foreach ($dirbarcode as $dirroot) {
					$res = dol_include_once($dirroot . $module . '.php');
					if ($res) {
						break;
					}
				}
				if ($res > 0) {
					$modBarCodeProduct = new $module();
					$ean = $modBarCodeProduct->getNextValue($id_prod, 0);
				} else {
					$ean = rand(200000, 299999) . str_pad(rand(0, 999999), 6, 0, STR_PAD_LEFT);
				}
			} else {
				$ean = rand(200000, 299999) . str_pad(rand(0, 999999), 6, 0, STR_PAD_LEFT);
			}

			$ean = substr($ean, 0, 12);
			$eansum = barcode_gen_ean_sum($ean);
			$ean .= $eansum;

			$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "product";
			$sql .= " WHERE barcode='" . $ean . "'";
			$db->begin();

			$resql = $db->query($sql);

			if ($resql) {
				if ($db->num_rows($resql) == 0) {
					$loop = false;

					$sql = "UPDATE " . MAIN_DB_PREFIX . "product";
					$sql .= " SET barcode = '" . $ean . "' WHERE rowid=" . $id_prod;

					$res = $db->query($sql);
					$db->commit();
				}
			}
		}
		return $res;
	}
}

/**
 *      \class      pdfLabel
 *      \brief      Create a PDF with the labelprint
 */
class pdfLabelProducts
{

    /**
     *
     * Create a pdf with the labelprint
     *
     */
    public function createPdf()
    {
        global $conf, $mysoc, $db, $langs;
        $langs->load("other");
        $langs->load("productbatch");


        if (version_compare(DOL_VERSION, 3.9) >= 0) {
            require_once(DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf.php');
        } else {
            require_once(DOL_DOCUMENT_ROOT . '/includes/tcpdf/tcpdf.php');
        }
        require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
        require_once(DOL_DOCUMENT_ROOT . "/core/lib/product.lib.php");
        require_once(DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');

        if ($conf->global->LABELPRINT_A4) {
            $pdf = new TCPDF();
        } else {
            $width = 0;
            $height = 0;
            if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_0 || $conf->global->MAIN_MODULE_LABELPRINT_LABELS_1) {
                $width = 70;
                if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_0) {
                    $height = 36;
                } else {
                    $height = 37;
                }
            } elseif ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_2) {
                $width = 38;
                $height = 21;
            } elseif ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_3 || $conf->global->MAIN_MODULE_LABELPRINT_LABELS_4) {
                $width = 48;
                $height = 25;
            } elseif ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_5) {
                $width = 105;
                $height = 37;
            } elseif ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_6) {
                $width = 105;
                $height = 25;
            } elseif ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_7) {
                $width = 105;
                $height = 57;
            }


            $dim = array($width, $height);


            $pdf = new TCPDF('L', 'mm', $dim);
        }

        //$pdf=new TCPDF();

        if (class_exists('TCPDF')) {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }

        $pdf->SetFont(pdf_getPDFFont($langs), '', 10);

        $lab_start = $conf->global->LAB_START;

        if ($conf->global->LABELPRINT_A4) {
            $PosX = 0;
            $PosY = 0;
            if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_0) {
                $PosY = 5 + (floor($lab_start / 3) * 36);
                $PosX = 5 + ($lab_start % 3) * 70;
            } elseif ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_1) {
                $PosY = 4 + (floor($lab_start / 3) * 37);
                $PosX = 5 + ($lab_start % 3) * 70;
            } elseif ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_2) {
                $PosY = 12 + (floor($lab_start / 5) * 21.2);
                $PosX = 10 + ($lab_start % 5) * 38;
            } elseif ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_3) {
                $PosY = 24 + (floor($lab_start / 4) * 25);
                $PosX = 12 + ($lab_start % 4) * 48;
            } elseif ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_4) {
                $PosY = 11 + (floor($lab_start / 4) * 25);
                $PosX = 12 + ($lab_start % 4) * 48;
            } elseif ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_5) {
                $PosY = 4 + (floor($lab_start / 2) * 37);
                $PosX = 5 + ($lab_start % 2) * 105;
            } elseif ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_6) {
                $PosY = 11 + (floor($lab_start / 4) * 25);
                $PosX = 12 + ($lab_start % 4) * 55;
            } elseif ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_7) {
                $PosY = 4 + (floor($lab_start / 2) * 57);
                $PosX = 5 + ($lab_start % 2) * 105;
            }

        } else {

            $PosX = 0;
            $PosY = 0;
            if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_6) {
                $PosX = 3;
            }
        }
        //$PosX=5+($lab_start % 3)*70;

        //$PosX=5;
        $Line = 5;

        // define barcode style
        $style = array(
            'position' => '',
            'align' => 'C',
            'stretch' => false,
            'fitwidth' => true,
            'cellfitalign' => '',
            'border' => false,
            'hpadding' => 'auto',
            'vpadding' => 'auto',
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => false, //array(255,255,255),
            'text' => true,
            'font' => 'helvetica',
            'fontsize' => 8,
            'stretchtext' => 4
        );

        //First page
        $pdf->AddPage();

        $sql = "SELECT fk_object, qty, price_level, batch";
        $sql .= " FROM " . MAIN_DB_PREFIX . "labelprint";
        $sql .= " WHERE entity=" . $conf->entity;
        $sql .= " AND typLabel='0'";
        $resql = $db->query($sql);

        if ($resql) {
            $num = $db->num_rows($resql);
            $i = 0;

            while ($i < $num) {
                $objp = $db->fetch_object($resql);
                $product = new Product($db);

                $product->fetch($objp->fk_object);
                $qty = $objp->qty;
                $n = 0;
                $PosXLabel = 0;
                while ($n < $qty) {
                    if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_0 || $conf->global->MAIN_MODULE_LABELPRINT_LABELS_1) {
                        $pdf->SetAutoPageBreak(false);

                        //Position X
                        if ($conf->global->LABELPRINT_A4) {
                            $PosXLabel = ($PosX < 70 ? $PosX : $PosX - 3);
                        }

                        //Soc Name
                        $pdf->SetFont('', 'B', 10);
                        $pdf->SetXY($PosXLabel, $PosY);
                        $pdf->SetFillColor(230, 230, 230);
                        if ($conf->global->LAB_COMP) {
                            $pdf->Cell(65, 10, dol_trunc($mysoc->name, 50), 0, 'L');
                            $flag = 1;
                        } elseif ($conf->global->LAB_PROD_REF) {
                            $pdf->Cell(65, 10, dol_trunc($product->ref, 50), 0, 'L');
                            $flag = 2;
                        } elseif ($conf->global->LAB_PROD_LABEL) {
                            $pdf->Cell(65, 10, dol_trunc($product->label, 50), 0, 'L');
                            $flag = 3;
                        } else {
                            $pdf->Cell(65, 10, dol_trunc($conf->global->LAB_FREE_TEXT, 50), 0, 'L');
                            $flag = 4;
                        }
                        $pdf->SetFont('', '', 10);

                        //Position Y
                        $PosYLabel = $PosY + $Line + 2;

                        //Product label
                        $pdf->SetXY($PosXLabel, $PosYLabel);
                        if ($flag == 1) {
                            if ($conf->global->LAB_PROD_REF) {
                                $pdf->Cell(25, 5, dol_trunc($product->ref, 30), 0, 0, 'L');
                            } elseif ($conf->global->LAB_PROD_LABEL) {
                                $pdf->Cell(25, 5, dol_trunc($product->label, 30), 0, 0, 'L');
                            } else {
                                $pdf->Cell(25, 5, dol_trunc($conf->global->LAB_FREE_TEXT, 30), 0, 0, 'L');
                            }
                        }
                        if ($flag == 2) {
                            if ($conf->global->LAB_PROD_LABEL) {
                                $pdf->Cell(25, 5, dol_trunc($product->label, 30), 0, 0, 'L');
                            } else {
                                $pdf->Cell(25, 5, dol_trunc($conf->global->LAB_FREE_TEXT, 30), 0, 0, 'L');
                            }
                        }
                        if ($flag == 3) {
                            $pdf->Cell(25, 5, dol_trunc($conf->global->LAB_FREE_TEXT, 30), 0, 0, 'L');
                        } else {
                            $pdf->Cell(25, 5, "", 0, 0, 'L');
                        }
                        //$pdf->Cell(25,5,dol_trunc($product->libelle,25),0,0,'L');
                        //$pdf->Write($Line,dol_trunc($product->libelle,25));

                        $PosYLabel = $PosYLabel + $Line + 2;
                        $pdf->SetXY($PosXLabel, $PosYLabel);

                        //Barcode
                        if ($conf->barcode->enabled) {
                            $product->fetch_barcode();

                            $pdf->write1DBarcode($product->barcode, $product->barcode_type_code, '', '', 35, 18, 0.4,
                                $style, 'N');

                        }

                        //Price
                        $pdf->SetFont('', 'B', 10);
                        if ($conf->global->LABELPRINT_SHOW_PRICE) {
                            if (empty($conf->global->PRODUIT_MULTIPRICES)) {
								if ($conf->global->LABELPRINT_SHOW_PRICE_TTC) {
									$labelPrice = price($product->price_ttc);
								}
								else{
									$labelPrice = price($product->price);
								}
                            } else {
								if ($conf->global->LABELPRINT_SHOW_PRICE_TTC) {
									$labelPrice = price($product->multiprices_ttc[$objp->price_level]);
								}
								else{
									$labelPrice = price($product->multiprices[$objp->price_level]);
								}
                            }
                            $pdf->SetXY($PosXLabel + 38, $PosYLabel);
                            $pdf->Cell(25, 5, $labelPrice, 0, 0, 'R');

                            $PosYLabel = $PosYLabel + $Line + 1;
                            $labelPrice = $langs->trans(currency_name($conf->currency));
                            $pdf->SetXY($PosXLabel + 38, $PosYLabel);
                            $pdf->Cell(25, 5, $labelPrice, 0, 0, 'R');
                        }


                        $PosYLabel = $PosYLabel + $Line;
                        $labelSet = '';
                        if ($conf->global->LAB_WEIGHT) {
                            $labelSet = $product->weight;
                            $labelSet .= " " . measuring_units_string($product->weight_units, "weight");
                        } elseif ($conf->global->LAB_LENGTH) {
                            $labelSet = $product->length;
                            $labelSet .= " " . measuring_units_string($product->length_units, "size");
                        } elseif ($conf->global->LAB_AREA) {
                            $labelSet = $product->surface;
                            $labelSet .= " " . measuring_units_string($product->surface_units, "surface");
                        } elseif ($conf->global->LAB_VOLUME) {
                            $labelSet = $product->volume;
                            $labelSet .= " " . measuring_units_string($product->volume_units, "volume");
                        } elseif ($conf->global->LAB_COUNTRY) {
                            $labelSet = getCountry($product->country_id, '', 0, $langs, 0);
                        }

                        $pdf->SetXY($PosXLabel + 38, $PosYLabel);
                        $pdf->Cell(25, 5, $labelSet, 0, 0, 'R');

                        $PosX = $PosX + 70;
                        if ($conf->global->LABELPRINT_A4) {
                            if ($PosX >= 200) {
                                $PosX = 5;
                                if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_0) {
                                    $PosY = $PosY + 36;
                                } else {
                                    $PosY = $PosY + 37;
                                }
                                If ($PosY >= 265) {
                                    if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_0) {
                                        $PosY = 5;
                                    } else {
                                        $PosY = 4;
                                    }

                                    $pdf->AddPage();
                                }
                            }
                        } else {
                            $PosX = 0;
                            $PosY = 0;
                            if ($qty - $n > 1) {
                                $pdf->AddPage();

                            } else {
                                if ($num - $i > 1) {
                                    $pdf->AddPage();
                                }
                            }
                        }
                        $n++;
                    } elseif ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_2) {
                        $pdf->SetAutoPageBreak(true, 1);
                        $Line = 3;
                        if (!$conf->global->LABELPRINT_A4) {
                            $PosX = 5;
                        }
                        $PosXLabel = $PosX;
                        //Position X
                        if ($conf->global->LABELPRINT_A4) {
                            $PosXLabel = ($PosX < 38 ? $PosX : $PosX - 2);
                        }

                        $pdf->SetFont('', 'B', 8);
                        $pdf->SetXY($PosXLabel + 1, $PosY);
                        $pdf->SetFillColor(230, 230, 230);
                        if ($conf->global->LAB_COMP) {
                            $pdf->Cell(65, 5, dol_trunc($mysoc->name, 14), 0, 'L');
                        } elseif ($conf->global->LAB_PROD_REF) {
                            $pdf->Cell(65, 5, dol_trunc($product->ref, 14), 0, 'L');
                        } elseif ($conf->global->LAB_PROD_LABEL) {
                            $pdf->Cell(65, 5, dol_trunc($product->label, 14), 0, 'L');
                        } else {
							if ($conf->global->LABELPRINT_SHOW_PRICE) {
								if (empty($conf->global->PRODUIT_MULTIPRICES)) {
									if ($conf->global->LABELPRINT_SHOW_PRICE_TTC) {
										$labelPrice = price($product->price_ttc);
									} else {
										$labelPrice = price($product->price);
									}
								} else {
									if ($conf->global->LABELPRINT_SHOW_PRICE_TTC) {
										$labelPrice = price($product->multiprices_ttc[$objp->price_level]);
									} else {
										$labelPrice = price($product->multiprices[$objp->price_level]);
									}
								}

								$pdf->Cell(65, 5,
									dol_trunc($labelPrice . " " . $langs->trans(currency_name($conf->currency)), 14), 0,
									'L');
							}
                        }
                        //Position Y
                        $PosYLabel = $PosY + $Line;
                        //$PosYLabel=$PosYLabel+$Line+2;
                        $pdf->SetXY($PosXLabel, $PosYLabel);

                        //Barcode
                        if ($conf->barcode->enabled) {
                            $product->fetch_barcode();

                            $pdf->write1DBarcode($product->barcode, $product->barcode_type_code, '', '', 31, 16,
                                0.4, $style, 'N');

                        }

                        $PosX = $PosX + 39;
                        if ($conf->global->LABELPRINT_A4) {
                            if ($PosX >= 200) {
                                $PosX = 10;
                                $PosY = $PosY + 21.2;

                                if ($PosY >= 280) {
                                    $PosY = 12;

                                    $pdf->AddPage();
                                }
                            }
                        } else {
                            $PosX = 0;
                            $PosY = 0;

                            if ($qty - $n > 1) {
                                $pdf->AddPage();

                            } else {
                                if ($num - $i > 1) {
                                    $pdf->AddPage();
                                }
                            }
                        }
                        $n++;
                    } elseif ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_3 || $conf->global->MAIN_MODULE_LABELPRINT_LABELS_4 || $conf->global->MAIN_MODULE_LABELPRINT_LABELS_6) {
                        $Line = 3;
                        $pdf->SetAutoPageBreak(true, 1);
                        $PosXLabel = $PosX + 1;
                        //Position X
                        if ($conf->global->LABELPRINT_A4) {
                            $PosXLabel = ($PosX < 48 ? $PosX : $PosX - 2);
                        }

                        $pdf->SetFont('', 'B', 8);
                        $pdf->SetXY($PosXLabel + 1, $PosY);
                        $pdf->SetFillColor(230, 230, 230);
                        if ($conf->global->LAB_COMP) {
                            $pdf->Cell(65, 5, dol_trunc($mysoc->name, 18), 0, 'L');
                        } elseif ($conf->global->LAB_PROD_REF) {
                            $pdf->Cell(65, 5, dol_trunc($product->ref, 18), 0, 'L');
                        } elseif ($conf->global->LAB_PROD_LABEL) {
                            $pdf->Cell(65, 5, dol_trunc($product->label, 18), 0, 'L');
                        } else {
                            $pdf->Cell(65, 5, dol_trunc($conf->global->LAB_FREE_TEXT, 18), 0, 'L');
                        }
                        //else{
						$PosYLabel = $PosY;
						if ($conf->global->LABELPRINT_SHOW_PRICE) {
							$PosYLabel = $PosY + $Line;
							$pdf->SetXY($PosXLabel + 1, $PosYLabel);
							if (empty($conf->global->PRODUIT_MULTIPRICES)) {
								if ($conf->global->LABELPRINT_SHOW_PRICE_TTC) {
									$labelPrice = price($product->price_ttc);
								}
								else{
									$labelPrice = price($product->price);
								}
							} else {
								if ($conf->global->LABELPRINT_SHOW_PRICE_TTC) {
									$labelPrice = price($product->multiprices_ttc[$objp->price_level]);
								}
								else{
									$labelPrice = price($product->multiprices[$objp->price_level]);
								}
							}
							$pdf->Cell(65, 5,
								dol_trunc($labelPrice . " " . $langs->trans(currency_name($conf->currency)),
									18), 0, 'L');
						}
                        //}
                        //Position Y
                        $PosYLabel = $PosYLabel + $Line;
                        //$PosYLabel=$PosYLabel+$Line+2;
                        $pdf->SetXY($PosXLabel, $PosYLabel);

                        //Barcode
                        if ($conf->barcode->enabled) {
                            $product->fetch_barcode();

                            if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_6) {
                                $pdf->write1DBarcode($product->barcode, $product->barcode_type_code, '', '', 44, 17,
                                    0.4, $style, 'N');
                            } else {
                                $pdf->write1DBarcode($product->barcode, $product->barcode_type_code, '', '', 34, 17,
                                    0.4, $style, 'N');
                            }


                        }

                        $PosX = $PosX + 48;
                        if ($conf->global->LABELPRINT_A4) {
                            if ($PosX >= 200) {
                                $PosX = 12;
                                if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_3) {
                                    $PosY = $PosY + 25.1;
                                } else {
                                    $PosY = $PosY + 25.3;
                                }

                                if ($PosY >= 265) {
                                    if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_3) {
                                        $PosY = 24;
                                    } else {
                                        $PosY = 11;
                                    }

                                    $pdf->AddPage();
                                }
                            }
                        } else {


                            if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_6) {
                                $PosX = 60;
                                $PosY = 0;
                                if ($n % 2 !== 0 && $qty - $n > 1) {
                                    $pdf->AddPage();
                                    $PosX = 0;
                                    $PosY = 0;
                                }
                            } else {
                                $PosX = 0;
                                $PosY = 0;
                                if ($qty - $n > 1) {
                                    $pdf->AddPage();

                                } else {
                                    if ($num - $i > 1) {
                                        $pdf->AddPage();
                                    }
                                }
                            }

                        }
                        $n++;
                    } elseif ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_5) {
                        $pdf->SetAutoPageBreak(false);
                        //Position X
                        if ($conf->global->LABELPRINT_A4) {
                            $PosXLabel = ($PosX < 105 ? $PosX : $PosX - 3);
                        }

                        //Soc Name
                        $pdf->SetFont('', 'B', 10);
                        $pdf->SetXY($PosXLabel, $PosY);
                        $pdf->SetFillColor(230, 230, 230);
                        if ($conf->global->LAB_COMP) {
                            $pdf->Cell(65, 5, dol_trunc($mysoc->name, 50), 0, 'L');
                            $flag = 1;
                        } elseif ($conf->global->LAB_PROD_REF) {
                            $pdf->Cell(65, 5, dol_trunc($product->ref, 50), 0, 'L');
                            $flag = 2;
                        } elseif ($conf->global->LAB_PROD_LABEL) {
                            $pdf->Cell(65, 5, dol_trunc($product->label, 50), 0, 'L');
                            $flag = 3;
                        } else {
                            $pdf->Cell(65, 5, dol_trunc($conf->global->LAB_FREE_TEXT, 50), 0, 'L');
                            $flag = 4;
                        }
                        $pdf->SetFont('', '', 10);

                        //Position Y
                        $PosYLabel = $PosY + $Line + 2;

                        //Product label
                        $pdf->SetXY($PosXLabel, $PosYLabel);
                        if ($flag == 1) {
                            if ($conf->global->LAB_PROD_REF) {
                                $pdf->Cell(25, 5, dol_trunc($product->ref, 50), 0, 0, 'L');
                            } elseif ($conf->global->LAB_PROD_LABEL) {
                                $pdf->Cell(25, 5, dol_trunc($product->label, 50), 0, 0, 'L');
                            } else {
                                $pdf->Cell(25, 5, dol_trunc($conf->global->LAB_FREE_TEXT, 50), 0, 0, 'L');
                            }
                        }
                        if ($flag == 2) {
                            if ($conf->global->LAB_PROD_LABEL) {
                                $pdf->Cell(25, 5, dol_trunc($product->label, 50), 0, 0, 'L');
                            } else {
                                $pdf->Cell(25, 5, dol_trunc($conf->global->LAB_FREE_TEXT, 50), 0, 0, 'L');
                            }
                        }
                        if ($flag == 3) {
                            $pdf->Cell(25, 5, dol_trunc($conf->global->LAB_FREE_TEXT, 50), 0, 0, 'L');
                        } else {
                            $pdf->Cell(25, 5, "", 0, 0, 'L');
                        }
                        //$pdf->Cell(25,5,dol_trunc($product->libelle,25),0,0,'L');
                        //$pdf->Write($Line,dol_trunc($product->libelle,25));

                        $PosYLabel = $PosYLabel + $Line + 2;
                        $pdf->SetXY($PosXLabel, $PosYLabel);

                        //Barcode
                        if ($conf->barcode->enabled) {
                            $product->fetch_barcode();

                            $pdf->write1DBarcode($product->barcode, $product->barcode_type_code, '', '', 40,
                                18, 0.4, $style, 'N');

                        }

                        //Price
                        if ($conf->global->LABELPRINT_SHOW_PRICE) {
                            $pdf->SetFont('', 'B', 10);
                            if (empty($conf->global->PRODUIT_MULTIPRICES)) {
								if ($conf->global->LABELPRINT_SHOW_PRICE_TTC) {
									$labelPrice = price($product->price_ttc);
								}
								else{
									$labelPrice = price($product->price);
								}
                            } else {
								if ($conf->global->LABELPRINT_SHOW_PRICE_TTC) {
									$labelPrice = price($product->multiprices_ttc[$objp->price_level]);
								}
								else{
									$labelPrice = price($product->multiprices[$objp->price_level]);
								}
                            }
                            $pdf->SetXY($PosXLabel + 38, $PosYLabel);
                            $pdf->Cell(25, 5, $labelPrice, 0, 0, 'R');

                            $PosYLabel = $PosYLabel + $Line + 1;
                            $labelPrice = $langs->trans(currency_name($conf->currency));
                            $pdf->SetXY($PosXLabel + 38, $PosYLabel);
                            $pdf->Cell(25, 5, $labelPrice, 0, 0, 'R');
                        }


                        $PosYLabel = $PosYLabel + $Line;
                        $labelSet = '';
                        if ($conf->global->LAB_WEIGHT) {
                            $labelSet = $product->weight;
                            $labelSet .= " " . measuring_units_string($product->weight_units, "weight");
                        } elseif ($conf->global->LAB_LENGTH) {
                            $labelSet = $product->length;
                            $labelSet .= " " . measuring_units_string($product->length_units, "size");
                        } elseif ($conf->global->LAB_AREA) {
                            $labelSet = $product->surface;
                            $labelSet .= " " . measuring_units_string($product->surface_units, "surface");
                        } elseif ($conf->global->LAB_VOLUME) {
                            $labelSet = $product->volume;
                            $labelSet .= " " . measuring_units_string($product->volume_units, "volume");
                        } elseif ($conf->global->LAB_COUNTRY) {
                            $labelSet = getCountry($product->country_id, '', 0, $langs, 0);
                        }

                        $pdf->SetXY($PosXLabel + 38, $PosYLabel);
                        $pdf->Cell(25, 5, $labelSet, 0, 0, 'R');

                        $PosX = $PosX + 105;
                        if ($conf->global->LABELPRINT_A4) {
                            if ($PosX >= 200) {
                                $PosX = 5;
                                $PosY = $PosY + 37;

                                if ($PosY >= 265) {
                                    $PosY = 5;
                                    $pdf->AddPage();
                                }
                            }
                        } else {
                            $PosX = 0;
                            $PosY = 0;
                            if ($qty - $n > 1) {
                                $pdf->AddPage();

                            } else {
                                if ($num - $i > 1) {
                                    $pdf->AddPage();
                                }
                            }
                        }
                        $n++;
                    } elseif ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_7) {
                        $Line = 4;
                        $pdf->SetAutoPageBreak(false);
                        //Position X
                        if ($conf->global->LABELPRINT_A4) {
                            $PosXLabel = ($PosX < 105 ? $PosX : $PosX - 3);
                        }

                        //Soc Name
                        $pdf->SetFont('', 'B', 11);
                        $pdf->SetXY($PosXLabel, $PosY);
                        $pdf->SetFillColor(230, 230, 230);
                        if ($conf->global->LAB_COMP) {
                            $pdf->Cell(65, 5, dol_trunc($mysoc->name, 50), 0, 'L');
                            $flag = 1;
                        } elseif ($conf->global->LAB_PROD_REF) {
                            $pdf->Cell(65, 5, dol_trunc($product->ref, 50), 0, 'L');
                            $flag = 2;
                        } elseif ($conf->global->LAB_PROD_LABEL) {
                            $pdf->Cell(65, 5, dol_trunc($product->label, 50), 0, 'L');
                            $flag = 3;
                        } else {
                            $pdf->Cell(65, 5, dol_trunc($conf->global->LAB_FREE_TEXT, 50), 0, 'L');
                            $flag = 4;
                        }
                        $pdf->SetFont('', '', 9);

                        //Position Y
                        $PosYLabel = $PosY + (2 * $Line);

                        //Product label
                        $pdf->SetXY($PosXLabel, $PosYLabel);
                        if ($flag == 1) {
                            if ($conf->global->LAB_PROD_REF) {
                                $pdf->MultiCell(100, 5, $product->ref, 0, 'L');
                            } elseif ($conf->global->LAB_PROD_LABEL) {
                                $pdf->MultiCell(100, 5, $product->label, 0, 'L');
                            } else {
                                $pdf->MultiCell(100, 5, $conf->global->LAB_FREE_TEXT, 0, 'L');
                            }
                        }
                        if ($flag == 2) {
                            if ($conf->global->LAB_PROD_LABEL) {
                                $pdf->MultiCell(100, 5, $product->label, 0, 'L');
                            } else {
                                $pdf->MultiCell(100, 5, $conf->global->LAB_FREE_TEXT, 0, 'L');
                            }
                        }
                        if ($flag == 3) {
                            $pdf->MultiCell(100, 5, $conf->global->LAB_FREE_TEXT, 0, 'L');
                        } else {
                            $pdf->MultiCell(100, 5, "", 0, 'L');
                        }
                        //$pdf->Cell(25,5,dol_trunc($product->libelle,25),0,0,'L');
                        //$pdf->Write($Line,dol_trunc($product->libelle,25));

                        $PosYLabel = $PosYLabel + (3 * $Line);

                        $pdf->SetXY($PosXLabel + 30, $PosYLabel);
                        if (!empty($objp->batch)) {
                            $labelSet = $langs->trans('Batch') . ': ';
                            $labelSet .= $objp->batch;
                            $pdf->Cell(25, 0, $labelSet, 0, 0, 'L');
                        }

                        $pdf->SetXY($PosXLabel, $PosYLabel);

                        if ($conf->global->LAB_WEIGHT) {
                            $labelSet = $langs->trans('Weight') . ': ';
                            $labelSet .= $product->weight;
                            $labelSet .= " " . measuring_units_string($product->weight_units, "weight");

                            $pdf->Cell(25, 0, $labelSet, 0, 0, 'L');
                            $PosYLabel = $PosYLabel + $Line;
                            $pdf->SetXY($PosXLabel, $PosYLabel);
                        }
                        if ($conf->global->LAB_LENGTH) {
                            $labelSet = $langs->trans('Length') . ': ';
                            $labelSet .= $product->length;
                            $labelSet .= " " . measuring_units_string($product->length_units, "size");

                            $pdf->Cell(25, 0, $labelSet, 0, 0, 'L');
                            $PosYLabel = $PosYLabel + $Line;
                            $pdf->SetXY($PosXLabel, $PosYLabel);
                        }
                        if ($conf->global->LAB_AREA) {
                            $labelSet = $product->surface;
                            $labelSet .= " " . measuring_units_string($product->surface_units, "surface");

                            $pdf->Cell(25, 0, $labelSet, 0, 0, 'L');
                            $PosYLabel = $PosYLabel + $Line;
                            $pdf->SetXY($PosXLabel, $PosYLabel);
                        }
                        if ($conf->global->LAB_VOLUME) {
                            $labelSet = $product->volume;
                            $labelSet .= " " . measuring_units_string($product->volume_units, "volume");

                            $pdf->Cell(25, 0, $labelSet, 0, 0, 'L');
                            $PosYLabel = $PosYLabel + $Line;
                            $pdf->SetXY($PosXLabel, $PosYLabel);
                        }
                        if ($conf->global->LAB_COUNTRY) {
                            $labelSet = getCountry($product->country_id, '', 0, $langs, 0);

                            $pdf->Cell(25, 0, $labelSet, 0, 0, 'L');
                            $PosYLabel = $PosYLabel + $Line;
                            $pdf->SetXY($PosXLabel, $PosYLabel);
                        }
                        $PosYLabel = $PosYLabel + (2 * $Line);
                        $pdf->SetXY($PosXLabel, $PosYLabel);

                        //Barcode
                        if ($conf->barcode->enabled) {
                            $product->fetch_barcode();

                            $pdf->write1DBarcode($product->barcode, $product->barcode_type_code, '', '', 40,
                                18, 0.4, $style, 'N');

                        }

                        //Price
                        if ($conf->global->LABELPRINT_SHOW_PRICE) {
                            if (empty($conf->global->PRODUIT_MULTIPRICES)) {
								if ($conf->global->LABELPRINT_SHOW_PRICE_TTC) {
									$labelPrice = price($product->price_ttc);
								}
								else{
									$labelPrice = price($product->price);
								}
                            } else {
								if ($conf->global->LABELPRINT_SHOW_PRICE_TTC) {
									$labelPrice = price($product->multiprices_ttc[$objp->price_level]);
								}
								else{
									$labelPrice = price($product->multiprices[$objp->price_level]);
								}
                            }
                            //$pdf->SetXY($PosXLabel + 38, $PosYLabel);
                            //$pdf->Cell(25, 5, $labelPrice, 0, 0, 'R');

                            //$PosYLabel = $PosYLabel + $Line + 1;
                            $labelPrice .= ' ' . $langs->trans(currency_name($conf->currency));
                            $pdf->SetXY($PosXLabel + 38, $PosYLabel);
                            $pdf->Cell(25, 5, $labelPrice, 0, 0, 'R');
                        }


                        $PosX = $PosX + 105;
                        if ($conf->global->LABELPRINT_A4) {
                            if ($PosX >= 200) {
                                $PosX = 5;
                                $PosY = $PosY + 57;

                                if ($PosY >= 265) {
                                    $PosY = 5;
                                    $pdf->AddPage();
                                }
                            }
                        } else {
                            $PosX = 0;
                            $PosY = 0;
                            if ($qty - $n > 1) {
                                $pdf->AddPage();

                            } else {
                                if ($num - $i > 1) {
                                    $pdf->AddPage();
                                }
                            }
                        }
                        $n++;
                    }
                }
                $i++;
            }
        }
        ini_set('display_errors', 'Off');
        $buf = $pdf->Output("", "S");

        //$file_temp = ini_get("session.save_path")."/".dol_now().".pdf";
        $file_temp = DOL_DATA_ROOT . "/" . dol_now() . ".pdf";

        $gestor = fopen($file_temp, "w");
        fwrite($gestor, $buf);
        fclose($gestor);
        $url = dol_buildpath("/labelprint/download.php", 1) . "?file=" . $file_temp;
        return $url;
        //print "<meta http-equiv='refresh' content='0;url=".$url."'>";

    }
}

class LabelsThirds
{
	public $db;                            //!< To store db handler
	public $error;                            //!< To return error code (or message)
	public $errors = array();                //!< To return several error codes (or messages)

	public $id;
	public $type = 1;
	public $entity;
	public $fk_object;
	public $qty;
	public $fk_user;
	public $datec = '';
	public $price_level;

	/**
	 *      Constructor
	 * @param      doliDB $DB Database handler
	 */
	public function __construct($DB)
	{
		$this->db = $DB;
	}

	/**
	 *      Create object into database
	 * @param      User $user      User that create
	 * @return     int                    <0 if KO, Id of created object if OK
	 */
	public function create($user)
	{
		global $conf;
		$error = 0;

		// Clean parameters
		$qty = $this->qty;

		$res = $this->fetch($this->id, $this->fk_object);
		if ($res == 1) {
			$qty = $this->qty + $qty;
			$this->qty = $qty;
			$res = $this->update();
			return $res;
		}

		if (isset($this->fk_object)) {
			$this->fk_object = trim($this->fk_object);
		}
		if (isset($this->qty)) {
			$this->qty = trim($this->qty);
		}

		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "labelprint(";

		$sql .= "entity,";
		$sql .= " typLabel,";
		$sql .= " fk_object,";
		$sql .= " qty,";
		$sql .= " fk_user,";
		$sql .= " datec,";
		$sql .= " price_level";

		$sql .= ") VALUES (";

		$sql .= " " . $conf->entity . ",";
		$sql .= " '1',";
		$sql .= " " . (!isset($this->fk_object) ? 'NULL' : "'" . $this->fk_object . "'") . ",";
		$sql .= " " . (!isset($this->qty) ? 'NULL' : "'" . $this->qty . "'") . ",";
		$sql .= " " . $user->id . ",";
		$sql .= " " . (!isset($this->datec) || dol_strlen($this->datec) == 0 ? 'NULL' : "'".$this->db->idate($this->datec)."'") . ",";
		$sql .= " " . (!isset($this->price_level) ? 1 : $this->price_level) . "";

		$sql .= ")";

		$this->db->begin();

		dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "labelprint");

		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(__METHOD__ . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 *      Create object into database
	 * @param      User  $user    User that create
	 * @param      array $toPrint Array with products to print
	 * @return     int                    <0 if KO, Id of created object if OK
	 */
	public function multicreate($user, $toPrint)
	{
		global $conf;
		$error = 0;
		require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
		$societe = new Societe($this->db);
		$qty = 0;
		foreach ($toPrint as $socid) {
			$result = $societe->fetch($socid);
			if ($result) {
				$qty = 1;

			}


			$res = $this->fetch($this->id, $societe->id);
			if ($res == 1) {
				$qty = $this->qty + $qty;
				$this->qty = $qty;
				$res = $this->update();
				if ($res != 1) {
					$error++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}
			} else {
				// Insert request
				$sql = "INSERT INTO " . MAIN_DB_PREFIX . "labelprint(";

				$sql .= "entity,";
				$sql .= "typLabel,";
				$sql .= "fk_object,";
				$sql .= "qty,";
				$sql .= "fk_user,";
				$sql .= "datec,";
				$sql .= "price_level";

				$sql .= ") VALUES (";

				$sql .= " " . $conf->entity . ",";
				$sql .= " 1,";
				$sql .= " " . $societe->id . ",";
				$sql .= " " . $qty . ",";
				$sql .= " " . $user->id . ",";
				$sql .= " '" .  $this->db->idate(dol_now()) . "',";
				$sql .= " " . (!isset($this->price_level) ? 1 : $this->price_level) . "";

				$sql .= ")";

				//$this->db->begin();

				dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}
			}

		}
		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "labelprint");

		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(__METHOD__ . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			//$this->db->rollback();
			return -1 * $error;
		} else {
			//$this->db->commit();
			return $this->id;
		}
	}


	/**
	 *    Load object in memory from database
	 * @param      int $id        id row
	 * @param      int $fk_object id object
	 * @return     int                 <0 if KO, >0 if OK
	 */
	public function fetch($id, $fk_object = 0)
	{
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.entity,";
		$sql .= " t.fk_object,";
		$sql .= " t.qty,";
		$sql .= " t.fk_user,";
		$sql .= " t.datec,";
		$sql .= " t.price_level";

		$sql .= " FROM " . MAIN_DB_PREFIX . "labelprint as t";

		if ($fk_object) {
			$sql .= " WHERE t.fk_object = " . $fk_object;
		} else {
			$sql .= " WHERE t.rowid = " . $id;
		}

		$sql .= " AND typLabel='1'";

		dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->entity = $obj->entity;
				$this->fk_object = $obj->fk_object;
				$this->qty = $obj->qty;
				$this->fk_user = $obj->fk_user;
				$this->datec = $this->db->jdate($obj->datec);
				$this->price_level = $obj->price_level;
				$this->db->free($resql);

				return 1;
			} else {
				$this->db->free($resql);

				return -1;
			}

		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(__METHOD__ . $this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *      Update object into database
	 *
	 * @return     int                    <0 if KO, >0 if OK
	 */
	public function update()
	{
		$error = 0;

		// Clean parameters

		if (isset($this->entity)) {
			$this->entity = trim($this->entity);
		}
		if (isset($this->fk_object)) {
			$this->fk_object = trim($this->fk_object);
		}
		if (isset($this->qty)) {
			$this->qty = trim($this->qty);
		}
		if (isset($this->fk_user)) {
			$this->fk_user = trim($this->fk_user);
		}
		if (isset($this->price_level)) {
			$this->price_level = trim($this->price_level);
		}

		// Check parameters

		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "labelprint SET";

		$sql .= " entity=" . (isset($this->entity) ? $this->entity : "null") . ",";
		$sql .= " typLabel='" . $this->type . "',";
		$sql .= " fk_object=" . (isset($this->fk_object) ? $this->fk_object : "null") . ",";
		$sql .= " qty=" . (isset($this->qty) ? $this->qty : "null") . ",";
		$sql .= " fk_user=" . (isset($this->fk_user) ? $this->fk_user : "null") . ",";
		$sql .= " price_level=" . (isset($this->price_level) ? $this->price_level : 1) . ",";
		$sql .= " datec=" . (dol_strlen($this->datec) != 0 ? "'" . $this->db->idate($this->datec) . "'" : 'null') . "";

		$sql .= " WHERE rowid=" . $this->id;

		$this->db->begin();

		dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(__METHOD__ . $errmsg, LOG_ERR);
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
	 *   Delete object in database
	 * @return    int                        <0 if KO, >0 if OK
	 */
	public function truncate()
	{
		$error = 0;

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "labelprint";
		$sql .= " WHERE typLabel='1'";
		$this->db->begin();

		dol_syslog(__METHOD__ . " sql=" . $sql);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(__METHOD__ . $errmsg, LOG_ERR);
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
	 *   Delete object in database
	 *
	 * @param     int $line      User that delete
	 * @return    int                        <0 if KO, >0 if OK
	 */
	public function delete($line)
	{
		$error = 0;

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "labelprint";
		$sql .= " WHERE rowid=" . $line;
		$this->db->begin();

		dol_syslog(get_class($this) . "::delete sql=" . $sql);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
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
	 *  Load an object from its id and create a new one in database
	 *
	 * @param      int $fromid Id of object to clone
	 * @return        int                            New id of clone
	 */
	public function createFromClone($fromid)
	{
		global $user;

		$error = 0;

		$object = new LabelsThirds($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id = 0;

		// Create clone
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$this->error = $object->error;
			$error++;
		}

		// End
		if (!$error) {
			$this->db->commit();
			return $object->id;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Initialise object with example values
	 *  Id must be 0 if object instance is a specimen.
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;

		$this->entity = '';
		$this->type = 1;
		$this->fk_object = '';
		$this->qty = '';
		$this->fk_user = '';
		$this->datec = '';
		$this->price_level = '';
	}


}

class pdfLabelsThirds
{
	/**
	 *
	 * Create a pdf with the labels
	 *
	 */
	public function createPdf()
	{
		global $conf, $mysoc, $db, $langs;
		$langs->load("other");

		if (version_compare(DOL_VERSION, 3.9) >= 0) {
			require_once(DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf.php');
		} else {
			require_once(DOL_DOCUMENT_ROOT . '/includes/tcpdf/tcpdf.php');
		}

		require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
		require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php");
		require_once(DOL_DOCUMENT_ROOT . "/core/lib/product.lib.php");
		require_once(DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');


		if ($conf->global->LABELPRINT_THIRD_A4) {
			$pdf = new TCPDF();
		} else {
		    $width = 0;
		    $height = 0;
            if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_0 || $conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_1) {
                $width = 70;
                if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_0) {
                    $height = 36;
                } else {
                    $height = 37;
                }
            } else {
                if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_2) {
                    $width = 105;
                    $height = 37;
                }
            }

            $dim = array($width, $height);


            $pdf = new TCPDF('L', 'mm', $dim);
        }

		if (class_exists('TCPDF')) {
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
		}

		$pdf->SetFont(pdf_getPDFFont($langs), '', 10);

		$lab_start = $conf->global->LAB_START;

		if ($conf->global->LABELPRINT_THIRD_A4) {
		    $PosX = 0;
		    $PosY = 0;
            if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_0) {
                $PosY = 5 + (floor($lab_start / 3) * 36);
                $PosX = 5 + ($lab_start % 3) * 70;
            } else {
                if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_1) {
                    $PosY = 4 + (floor($lab_start / 3) * 37);
                    $PosX = 5 + ($lab_start % 3) * 70;
                } else {
                    if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_2) {
                        $PosY = 4 + (floor($lab_start / 2) * 37);
                        $PosX = 5 + ($lab_start % 2) * 105;
                    }
                }
            }
        } else {
			$PosX = 0;
			$PosY = 0;
		}
		//$PosX=5+($lab_start % 3)*70;

		//$PosX=5;
		$Line = 5;

		//First page
		$pdf->AddPage();

		$sql = "SELECT fk_object, qty, price_level";
		$sql .= " FROM " . MAIN_DB_PREFIX . "labelprint";
		$sql .= " WHERE entity=" . $conf->entity;
		$sql .= " AND typLabel='1'";
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;

			while ($i < $num) {
				$objp = $db->fetch_object($resql);
				$societe = new Societe($db);

				$societe->fetch($objp->fk_object);
				$qty = $objp->qty;
				$n = 0;

				while ($n < $qty) {
					//70*36 - 70*37
					if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_0 || $conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_1) {
						$PosXLabel = $PosX;
						$pdf->SetAutoPageBreak(false);
						//Position X
						if ($conf->global->LABELPRINT_THIRD_A4) {
							$PosXLabel = ($PosX < 70 ? $PosX : $PosX - 3);
						}

						//Soc Name
						$pdf->SetFont('', 'B', 10);
						$pdf->SetXY($PosXLabel, $PosY);
						$pdf->SetFillColor(230, 230, 230);

						if ($conf->global->LAB_THIRD_COMP) {
							$pdf->Cell(65, 10, dol_trunc($societe->name, 50), 0, 'L');
						}
						$PosYLabel = $PosY + $Line + 2;
						if ($conf->global->LAB_THIRD_CONTACT) {
							$pdf->SetXY($PosXLabel, $PosYLabel);
							$pdf->Cell(65, 10, dol_trunc($societe->ref, 50), 0, 'L');
						}
						$PosYLabel = $PosY + $Line + 2;

						$pdf->SetFont('', '', 10);

						if ($conf->global->LAB_THIRD_ADDRESS) {
							$pdf->SetXY($PosXLabel, $PosYLabel);
							$carac_client = dol_format_address($societe,(!empty($societe->country_code) && $societe->country_code != $mysoc->country_code?1:0));

							// Country
							/*if (!empty($societe->country_code) && $societe->country_code != $mysoc->country_code) {
								$carac_client .= "\n" . $langs->convToOutputCharset($langs->transnoentitiesnoconv("Country" . $societe->country_code)) . "\n";
							} else {
								if (empty($societe->country_code) && !empty($societe->country_code) && ($societe->country_code != $mysoc->country_code)) {
									$carac_client .= "\n" . $langs->convToOutputCharset($langs->transnoentitiesnoconv("Country" . $societe->country_code)) . "\n";
								}
							}*/

							//$pdf->Cell(65, 10, $carac_client, 0, 'L');
							$pdf->MultiCell(65,10,$carac_client,0,'L');
						}
						$PosY2 = $pdf->GetY();
						$PosYLabel = $PosY2 + $Line + 2;

						if ($conf->global->LAB_THIRD_FREE_TEXT) {
							$pdf->SetXY($PosXLabel, $PosYLabel);
							$pdf->Cell(65, 10, dol_trunc($conf->global->LAB_THIRD_FREE_TEXT, 50), 0, 'L');
						}


						$PosX = $PosX + 70;
						if ($conf->global->LABELPRINT_THIRD_A4) {
							if ($PosX >= 200) {
								$PosX = 5;
								if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_0) {
									$PosY = $PosY + 36;
								} else {
									$PosY = $PosY + 37;
								}

								if ($PosY >= 265) {
									if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_0) {
										$PosY = 5;
									} else {
										$PosY = 4;
									}

									$pdf->AddPage();
								}
							}
						} else {
							$PosX = 0;
							$PosY = 0;
							if ($qty - $n > 1) {
								$pdf->AddPage();

							} else {
								if ($num - $i > 1) {
									$pdf->AddPage();
								}
							}
						}

						$n++;
					} //105*37
					else {
						if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_2) {
							$pdf->SetAutoPageBreak(false);
							$PosXLabel = $PosX;
							//Position X
							if ($conf->global->LABELPRINT_THIRD_A4) {
								$PosXLabel = ($PosX < 105 ? $PosX : $PosX - 3);
							}

							//Soc Name
							$pdf->SetFont('', 'B', 10);
							$pdf->SetXY($PosXLabel, $PosY);
							$pdf->SetFillColor(230, 230, 230);

							if ($conf->global->LAB_THIRD_COMP) {
								$pdf->Cell(65, 10, dol_trunc($societe->name, 50), 0, 'L');
							}
							$PosYLabel = $PosY + $Line + 2;
							if ($conf->global->LAB_THIRD_CONTACT) {
								$pdf->SetXY($PosXLabel, $PosYLabel);
								$pdf->Cell(65, 10, dol_trunc($societe->ref, 50), 0, 'L');
							}
							$PosYLabel = $PosY + $Line + 2;

							$pdf->SetFont('', '', 10);

							if ($conf->global->LAB_THIRD_ADDRESS) {
								$pdf->SetXY($PosXLabel, $PosYLabel);
								$carac_client = dol_format_address($societe,(!empty($societe->country_code) && $societe->country_code != $mysoc->country_code?1:0));

								// Country
								/*if (!empty($societe->country_code) && $societe->country_code != $mysoc->country_code) {
                                    $carac_client .= "\n" . $langs->convToOutputCharset($langs->transnoentitiesnoconv("Country" . $societe->country_code)) . "\n";
                                } else {
                                    if (empty($societe->country_code) && !empty($societe->country_code) && ($societe->country_code != $mysoc->country_code)) {
                                        $carac_client .= "\n" . $langs->convToOutputCharset($langs->transnoentitiesnoconv("Country" . $societe->country_code)) . "\n";
                                    }
                                }*/

								//$pdf->Cell(65, 10, $carac_client, 0, 'L');
								$pdf->MultiCell(65,10,$carac_client,0,'L');
							}
							$PosY2 = $pdf->GetY();
							$PosYLabel = $PosY2 + $Line + 2;

							if ($conf->global->LAB_THIRD_FREE_TEXT) {
								$pdf->SetXY($PosXLabel, $PosYLabel);
								$pdf->Cell(65, 10, dol_trunc($conf->global->LAB_THIRD_FREE_TEXT, 50), 0, 'L');
							}


							$PosX = $PosX + 105;
							if ($conf->global->LABELPRINT_THIRD_A4) {
								if ($PosX >= 200) {
									$PosX = 5;
									$PosY = $PosY + 37;

									if ($PosY >= 265) {
										$PosY = 5;
										$pdf->AddPage();
									}
								}
							} else {
								$PosX = 0;
								$PosY = 0;
								if ($qty - $n > 1) {
									$pdf->AddPage();

								} else {
									if ($num - $i > 1) {
										$pdf->AddPage();
									}
								}
							}
							$n++;
						}
					}

					/*
                    else if($conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_3 || $conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_4)
                    {
                        $Line = 3;
                        //Position X
                        $PosXLabel=($PosX<48?$PosX:$PosX-2);

                        $pdf->SetFont('','B', 8);
                        $pdf->SetXY($PosXLabel+1,$PosY);
                        $pdf->SetFillColor(230,230,230);

						if	($conf->global->LAB_THIRD_COMP)
						{
							$pdf->MultiCell(65,10,dol_trunc($societe->name,50),0,'L');
							$flag=1;
						}
						$PosYLabel=$PosY+$Line+2;
						if	($conf->global->LAB_THIRD_CONTACT)
						{
							$pdf->SetXY($PosXLabel,$PosYLabel);
							$pdf->MultiCell(65,10,dol_trunc($societe->ref,50),0,'L');
							$flag=2;
						}
						$PosYLabel=$PosY+$Line+2;

						$pdf->SetFont('','', 10);

						if	($conf->global->LAB_THIRD_ADDRESS)
						{
							$pdf->SetXY($PosXLabel,$PosYLabel);
							$carac_client=dol_format_address($societe);

							// Country
							if (!empty($societe->country_code) && $societe->country_code != $mysoc->country_code) {
								$carac_client.="\n".$langs->convToOutputCharset($langs->transnoentitiesnoconv("Country".$societe->country_code))."\n";
							}
							else if (empty($societe->country_code) && !empty($societe->country_code) && ($societe->country_code != $mysoc->country_code)) {
								$carac_client.="\n".$langs->convToOutputCharset($langs->transnoentitiesnoconv("Country".$societe->country_code))."\n";
							}

							$pdf->MultiCell(65,10,$carac_client,0,'L');
							$flag=3;
						}
						$PosYLabel=$PosY+$Line+2;

						if ($conf->global->LAB_FREE_TEXT)
						{
							$pdf->SetXY($PosXLabel,$PosYLabel);
							$pdf->MultiCell(65,10,dol_trunc($conf->global->LAB_FREE_TEXT,50),0,'L');
							$flag=4;
						}

						$PosYLabel=$PosY+$Line;

                        $pdf->SetXY($PosXLabel,$PosYLabel);
                        $pdf->SetAutoPageBreak(true,1);

                        $PosX=$PosX+48;

                        if($PosX>=200)
                        {
                            $PosX=12;
                            if($conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_3)
                                $PosY=$PosY+25.1;
                            else
                                $PosY=$PosY+25.3;

                            if($PosY>=265)
                            {
                                if($conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_3)
                                    $PosY=24;
                                else
                                    $PosY=11;

                                $pdf->AddPage();
                            }
                        }
                        $n++;
                    }
                    else if($conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_5)
                    {
                        //Position X
                        $PosXLabel=($PosX<105?$PosX:$PosX-3);

                        //Soc Name
                        $pdf->SetFont('','B', 10);
                        $pdf->SetXY($PosXLabel,$PosY);
                        $pdf->SetFillColor(230,230,230);

						if	($conf->global->LAB_THIRD_COMP)
						{
							$pdf->MultiCell(65,10,dol_trunc($societe->name,50),0,'L');
							$flag=1;
						}
						$PosYLabel=$PosY+$Line+2;
						if	($conf->global->LAB_THIRD_CONTACT)
						{
							$pdf->SetXY($PosXLabel,$PosYLabel);
							$pdf->MultiCell(65,10,dol_trunc($societe->ref,50),0,'L');
							$flag=2;
						}
						$PosYLabel=$PosY+$Line+2;

						$pdf->SetFont('','', 10);

						if	($conf->global->LAB_THIRD_ADDRESS)
						{
							$pdf->SetXY($PosXLabel,$PosYLabel);
							$carac_client=dol_format_address($societe);

							// Country
							if (!empty($societe->country_code) && $societe->country_code != $mysoc->country_code) {
								$carac_client.="\n".$langs->convToOutputCharset($langs->transnoentitiesnoconv("Country".$societe->country_code))."\n";
							}
							else if (empty($societe->country_code) && !empty($societe->country_code) && ($societe->country_code != $mysoc->country_code)) {
								$carac_client.="\n".$langs->convToOutputCharset($langs->transnoentitiesnoconv("Country".$societe->country_code))."\n";
							}

							$pdf->MultiCell(65,10,$carac_client,0,'L');
							$flag=3;
						}
						$PosYLabel=$PosY+$Line+2;

						if ($conf->global->LAB_FREE_TEXT)
						{
							$pdf->SetXY($PosXLabel,$PosYLabel);
							$pdf->MultiCell(65,10,dol_trunc($conf->global->LAB_FREE_TEXT,50),0,'L');
							$flag=4;
						}

                        //Position Y
                        $PosYLabel=$PosY+$Line+2;

                        $pdf->SetAutoPageBreak(false);

                        $PosX=$PosX+105;
                        if($PosX>=200)
                        {
                            $PosX=5;
                            $PosY=$PosY+37;

                            if($PosY>=265)
                            {
                                $PosY=5;
                                $pdf->AddPage();
                            }
                        }
                        $n++;
                    }*/
				}
				$i++;
			}
		}
		ini_set('display_errors', 'Off');
		$buf = $pdf->Output('', 'S');

		//$file_temp = ini_get("session.save_path")."/".dol_now().".pdf";
		$file_temp = DOL_DATA_ROOT . '/' . dol_now() . '.pdf';

		$gestor = fopen($file_temp, 'w');
		fwrite($gestor, $buf);
		fclose($gestor);
		$url = dol_buildpath('/labelprint/download.php', 1) . '?file=' . $file_temp;

		return $url;
		//print "<meta http-equiv='refresh' content='0;url=".$url."'>";
	}
}

class LabelsContacts
{
    public $db;                            //!< To store db handler
    public $error;                            //!< To return error code (or message)
    public $errors = array();                //!< To return several error codes (or messages)

    public $id;
    public $type = 2;
    public $entity;
    public $fk_object;
    public $qty;
    public $fk_user;
    public $datec = '';
    public $price_level;

    /**
     *      Constructor
     * @param      doliDB $DB Database handler
     */
    public function __construct($DB)
    {
        $this->db = $DB;
    }

    /**
     *      Create object into database
     * @param      User $user User that create
     * @return     int                    <0 if KO, Id of created object if OK
     */
    public function create($user)
    {
        global $conf;
        $error = 0;

        // Clean parameters
        $qty = $this->qty;

        $res = $this->fetch($this->id, $this->fk_object);
        if ($res == 1) {
            $qty = $this->qty + $qty;
            $this->qty = $qty;
            $res = $this->update();
            return $res;
        }

        if (isset($this->fk_object)) {
            $this->fk_object = trim($this->fk_object);
        }
        if (isset($this->qty)) {
            $this->qty = trim($this->qty);
        }

        // Check parameters
        // Put here code to add control on parameters values

        // Insert request
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "labelprint(";

        $sql .= "entity,";
        $sql .= " typLabel,";
        $sql .= " fk_object,";
        $sql .= " qty,";
        $sql .= " fk_user,";
        $sql .= " datec,";
        $sql .= " price_level";

        $sql .= ") VALUES (";

        $sql .= " " . $conf->entity . ",";
        $sql .= " '2',";
        $sql .= " " . (!isset($this->fk_object) ? 'NULL' : "'" . $this->fk_object . "'") . ",";
        $sql .= " " . (!isset($this->qty) ? 'NULL' : "'" . $this->qty . "'") . ",";
        $sql .= " " . $user->id . ",";
        $sql .= " " . (!isset($this->datec) || dol_strlen($this->datec) == 0 ? 'NULL' : "'".$this->db->idate($this->datec)."'") . ",";
        $sql .= " " . (!isset($this->price_level) ? 1 : $this->price_level) . "";

        $sql .= ")";

        $this->db->begin();

        dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "labelprint");

        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(__METHOD__ . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return $this->id;
        }
    }

    /**
     *      Create object into database
     * @param      User $user User that create
     * @param      array $toPrint Array with products to print
     * @return     int                    <0 if KO, Id of created object if OK
     */
    public function multicreate($user, $toPrint)
    {
        global $conf;
        $error = 0;
        require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
        $contact = new Contact($this->db);
        $qty = 0;
        foreach ($toPrint as $contactid) {
            $result = $contact->fetch($contactid);
            if ($result) {
                $qty = 1;

            }


            $res = $this->fetch($this->id, $contact->id);
            if ($res == 1) {
                $qty = $this->qty + $qty;
                $this->qty = $qty;
                $res = $this->update();
                if ($res != 1) {
                    $error++;
                    $this->errors[] = "Error " . $this->db->lasterror();
                }
            } else {
                // Insert request
                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "labelprint(";

                $sql .= "entity,";
                $sql .= "typLabel,";
                $sql .= "fk_object,";
                $sql .= "qty,";
                $sql .= "fk_user,";
                $sql .= "datec,";
                $sql .= "price_level";

                $sql .= ") VALUES (";

                $sql .= " " . $conf->entity . ",";
                $sql .= " 2,";
                $sql .= " " . $contact->id . ",";
                $sql .= " " . $qty . ",";
                $sql .= " " . $user->id . ",";
                $sql .= " " . (!isset($this->datec) || dol_strlen($this->datec) == 0 ? 'NULL' : "'".$this->db->idate($this->datec)."'") . ",";
                $sql .= " " . (!isset($this->price_level) ? 1 : $this->price_level) . "";

                $sql .= ")";

                //$this->db->begin();

                dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
                $resql = $this->db->query($sql);
                if (!$resql) {
                    $error++;
                    $this->errors[] = "Error " . $this->db->lasterror();
                }
            }

        }
        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "labelprint");

        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(__METHOD__ . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            //$this->db->rollback();
            return -1 * $error;
        } else {
            //$this->db->commit();
            return $this->id;
        }
    }


    /**
     *    Load object in memory from database
     * @param      int $id id row
     * @param      int $fk_object id object
     * @return     int                 <0 if KO, >0 if OK
     */
    public function fetch($id, $fk_object = 0)
    {
        $sql = "SELECT";
        $sql .= " t.rowid,";

        $sql .= " t.entity,";
        $sql .= " t.fk_object,";
        $sql .= " t.qty,";
        $sql .= " t.fk_user,";
        $sql .= " t.datec,";
        $sql .= " t.price_level";

        $sql .= " FROM " . MAIN_DB_PREFIX . "labelprint as t";

        if ($fk_object) {
            $sql .= " WHERE t.fk_object = " . $fk_object;
        } else {
            $sql .= " WHERE t.rowid = " . $id;
        }

        $sql .= " AND typLabel='2'";

        dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->entity = $obj->entity;
                $this->fk_object = $obj->fk_object;
                $this->qty = $obj->qty;
                $this->fk_user = $obj->fk_user;
                $this->datec = $this->db->jdate($obj->datec);
                $this->price_level = $obj->price_level;
                $this->db->free($resql);

                return 1;
            } else {
                $this->db->free($resql);

                return -1;
            }

        } else {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(__METHOD__ . $this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     *      Update object into database
     *
     * @return     int                    <0 if KO, >0 if OK
     */
    public function update()
    {
        $error = 0;

        // Clean parameters

        if (isset($this->entity)) {
            $this->entity = trim($this->entity);
        }
        if (isset($this->fk_object)) {
            $this->fk_object = trim($this->fk_object);
        }
        if (isset($this->qty)) {
            $this->qty = trim($this->qty);
        }
        if (isset($this->fk_user)) {
            $this->fk_user = trim($this->fk_user);
        }
        if (isset($this->price_level)) {
            $this->price_level = trim($this->price_level);
        }

        // Check parameters

        // Update request
        $sql = "UPDATE " . MAIN_DB_PREFIX . "labelprint SET";

        $sql .= " entity=" . (isset($this->entity) ? $this->entity : "null") . ",";
        $sql .= " typLabel='" . $this->type . "',";
        $sql .= " fk_object=" . (isset($this->fk_object) ? $this->fk_object : "null") . ",";
        $sql .= " qty=" . (isset($this->qty) ? $this->qty : "null") . ",";
        $sql .= " fk_user=" . (isset($this->fk_user) ? $this->fk_user : "null") . ",";
        $sql .= " price_level=" . (isset($this->price_level) ? $this->price_level : 1) . ",";
        $sql .= " datec=" . (dol_strlen($this->datec) != 0 ? "'" . $this->db->idate($this->datec) . "'" : 'null') . "";

        $sql .= " WHERE rowid=" . $this->id;

        $this->db->begin();

        dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(__METHOD__ . $errmsg, LOG_ERR);
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
     *   Delete object in database
     * @return    int                        <0 if KO, >0 if OK
     */
    public function truncate()
    {
        $error = 0;

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "labelprint";
        $sql .= " WHERE typLabel='2'";
        $this->db->begin();

        dol_syslog(__METHOD__ . " sql=" . $sql);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(__METHOD__ . $errmsg, LOG_ERR);
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
     *   Delete object in database
     *
     * @param     int $line User that delete
     * @return    int                        <0 if KO, >0 if OK
     */
    public function delete($line)
    {
        $error = 0;

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "labelprint";
        $sql .= " WHERE rowid=" . $line;
        $this->db->begin();

        dol_syslog(get_class($this) . "::delete sql=" . $sql);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
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
     *  Load an object from its id and create a new one in database
     *
     * @param      int $fromid Id of object to clone
     * @return        int                            New id of clone
     */
    public function createFromClone($fromid)
    {
        global $user;

        $error = 0;

        $object = new LabelsContacts($this->db);

        $this->db->begin();

        // Load source object
        $object->fetch($fromid);
        $object->id = 0;

        // Create clone
        $result = $object->create($user);

        // Other options
        if ($result < 0) {
            $this->error = $object->error;
            $error++;
        }

        // End
        if (!$error) {
            $this->db->commit();
            return $object->id;
        } else {
            $this->db->rollback();
            return -1;
        }
    }


    /**
     *  Initialise object with example values
     *  Id must be 0 if object instance is a specimen.
     */
    public function initAsSpecimen()
    {
        $this->id = 0;

        $this->entity = '';
        $this->type = 2;
        $this->fk_object = '';
        $this->qty = '';
        $this->fk_user = '';
        $this->datec = '';
        $this->price_level = '';
    }


}

class pdfLabelsContacts
{
    /**
     *
     * Create a pdf with the labels
     *
     */
    public function createPdf()
    {
        global $conf, $mysoc, $db, $langs;
        $langs->load("other");

        if (version_compare(DOL_VERSION, 3.9) >= 0) {
            require_once(DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf.php');
        } else {
            require_once(DOL_DOCUMENT_ROOT . '/includes/tcpdf/tcpdf.php');
        }

        require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
        require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php");
        require_once(DOL_DOCUMENT_ROOT . "/core/lib/product.lib.php");
        require_once(DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');

        if ($conf->global->LABELPRINT_CONTACT_A4) {
            $pdf = new TCPDF();
        } else {
            $width = 0;
            $height = 0;
            if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_CONTACT_0 || $conf->global->MAIN_MODULE_LABELPRINT_LABELS_CONTACT_1) {
                $width = 70;
                if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_CONTACT_0) {
                    $height = 36;
                } else {
                    $height = 37;
                }
            } else {
                if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_CONTACT_2) {
                    $width = 105;
                    $height = 37;
                }
            }

            $dim = array($width, $height);

            $pdf = new TCPDF('L', 'mm', $dim);
        }

        //$pdf=new TCPDF();

        if (class_exists('TCPDF')) {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }

        $pdf->SetFont(pdf_getPDFFont($langs), '', 10);

        $lab_start = $conf->global->LAB_START;

        if ($conf->global->LABELPRINT_CONTACT_A4) {
            $PosX = 0;
            $PosY = 0;
            if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_CONTACT_0) {
                $PosY = 5 + (floor($lab_start / 3) * 36);
                $PosX = 5 + ($lab_start % 3) * 70;
            } else {
                if ($conf->global->MAIN_MODULE_LABELPRINT_CONTACT_THIRD_1) {
                    $PosY = 4 + (floor($lab_start / 3) * 37);
                    $PosX = 5 + ($lab_start % 3) * 70;
                } else {
                    if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_CONTACT_2) {
                        $PosY = 4 + (floor($lab_start / 2) * 37);
                        $PosX = 5 + ($lab_start % 2) * 105;
                    }
                }
            }
        } else {
            $PosX = 0;
            $PosY = 0;
        }

        $Line = 5;

        //First page
        $pdf->AddPage();

        $sql = "SELECT fk_object, qty, price_level";
        $sql .= " FROM " . MAIN_DB_PREFIX . "labelprint";
        $sql .= " WHERE entity=" . $conf->entity;
        $sql .= " AND typLabel='2'";
        $resql = $db->query($sql);

        if ($resql) {
            $num = $db->num_rows($resql);
            $i = 0;

            while ($i < $num) {
                $objp = $db->fetch_object($resql);
                $contact = new Contact($db);

                $contact->fetch($objp->fk_object);
                $qty = $objp->qty;
                $n = 0;
                while ($n < $qty) {
                    //70*36 - 70*37
                    if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_CONTACT_0 || $conf->global->MAIN_MODULE_LABELPRINT_LABELS_CONTACT_1) {
                        $PosXLabel = $PosX;
                        $pdf->SetAutoPageBreak(false);
                        //Position X
                        if ($conf->global->LABELPRINT_CONTACT_A4) {
                            $PosXLabel = ($PosX < 70 ? $PosX : $PosX - 3);
                        }

                        //Soc Name
                        $pdf->SetFont('', 'B', 10);
                        $pdf->SetXY($PosXLabel, $PosY);
                        $pdf->SetFillColor(230, 230, 230);

                        if ($conf->global->LAB_CONTACT_COMP) {
                            $pdf->Cell(65, 10, dol_trunc($contact->getFullName($langs), 50), 0, 'L');
                        }
                        $PosYLabel = $PosY + $Line + 2;

                        $pdf->SetFont('', '', 10);

                        if ($conf->global->LAB_CONTACT_ADDRESS) {
                            $pdf->SetXY($PosXLabel, $PosYLabel);
                            $carac_client = dol_format_address($contact, (!empty($contact->country_code) && $contact->country_code != $mysoc->country_code ? 1 : 0));

                            //$pdf->Cell(65, 10, $carac_client, 0, 'L');
                            $pdf->MultiCell(65, 10, $carac_client, 0, 'L');
                        }
                        $PosY2 = $pdf->GetY();
                        $PosYLabel = $PosY2 + $Line + 2;

                        if ($conf->global->LAB_CONTACT_FREE_TEXT) {
                            $pdf->SetXY($PosXLabel, $PosYLabel);
                            $pdf->Cell(65, 10, dol_trunc($conf->global->LAB_CONTACT_FREE_TEXT, 50), 0, 'L');
                        }


                        $PosX = $PosX + 70;
                        if ($conf->global->LABELPRINT_CONTACT_A4) {
                            if ($PosX >= 200) {
                                $PosX = 5;
                                if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_CONTACT_0) {
                                    $PosY = $PosY + 36;
                                } else {
                                    $PosY = $PosY + 37;
                                }

                                if ($PosY >= 265) {
                                    if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_CONTACT_0) {
                                        $PosY = 5;
                                    } else {
                                        $PosY = 4;
                                    }

                                    $pdf->AddPage();
                                }
                            }
                        } else {
                            $PosX = 0;
                            $PosY = 0;
                            if ($qty - $n > 1) {
                                $pdf->AddPage();

                            } else {
                                if ($num - $i > 1) {
                                    $pdf->AddPage();
                                }
                            }
                        }

                        $n++;
                    } //105*37
                    else {
                        if ($conf->global->MAIN_MODULE_LABELPRINT_LABELS_CONTACT_2) {
                            $pdf->SetAutoPageBreak(false);
                            $PosXLabel = $PosX;
                            //Position X
                            if ($conf->global->LABELPRINT_CONTACT_A4) {
                                $PosXLabel = ($PosX < 105 ? $PosX : $PosX - 3);
                            }

                            //Soc Name
                            $pdf->SetFont('', 'B', 10);
                            $pdf->SetXY($PosXLabel, $PosY);
                            $pdf->SetFillColor(230, 230, 230);

                            if ($conf->global->LAB_CONTACT_COMP) {
                                $pdf->Cell(65, 10, dol_trunc($contact->getFullName($langs), 50), 0, 'L');
                            }
                            $PosYLabel = $PosY + $Line + 2;

                            $pdf->SetFont('', '', 10);

                            if ($conf->global->LAB_CONTACT_ADDRESS) {
                                $pdf->SetXY($PosXLabel, $PosYLabel);
                                $carac_client = dol_format_address($contact, (!empty($contact->country_code) && $contact->country_code != $mysoc->country_code ? 1 : 0));

                                // Country
                                /*if (!empty($societe->country_code) && $societe->country_code != $mysoc->country_code) {
                                    $carac_client .= "\n" . $langs->convToOutputCharset($langs->transnoentitiesnoconv("Country" . $societe->country_code)) . "\n";
                                } else {
                                    if (empty($societe->country_code) && !empty($societe->country_code) && ($societe->country_code != $mysoc->country_code)) {
                                        $carac_client .= "\n" . $langs->convToOutputCharset($langs->transnoentitiesnoconv("Country" . $societe->country_code)) . "\n";
                                    }
                                }*/

                                //$pdf->Cell(65, 10, $carac_client, 0, 'L');
                                $pdf->MultiCell(65, 10, $carac_client, 0, 'L');
                            }
                            $PosY2 = $pdf->GetY();
                            $PosYLabel = $PosY2 + $Line + 2;

                            if ($conf->global->LAB_CONTACT_FREE_TEXT) {
                                $pdf->SetXY($PosXLabel, $PosYLabel);
                                $pdf->Cell(65, 10, dol_trunc($conf->global->LAB_CONTACT_FREE_TEXT, 50), 0, 'L');
                            }


                            $PosX = $PosX + 105;
                            if ($conf->global->LABELPRINT_CONTACT_A4) {
                                if ($PosX >= 200) {
                                    $PosX = 5;
                                    $PosY = $PosY + 37;

                                    if ($PosY >= 265) {
                                        $PosY = 5;
                                        $pdf->AddPage();
                                    }
                                }
                            } else {
                                $PosX = 0;
                                $PosY = 0;
                                if ($qty - $n > 1) {
                                    $pdf->AddPage();

                                } else {
                                    if ($num - $i > 1) {
                                        $pdf->AddPage();
                                    }
                                }
                            }
                            $n++;
                        }
                    }

                    /*
                    else if($conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_3 || $conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_4)
                    {
                        $Line = 3;
                        //Position X
                        $PosXLabel=($PosX<48?$PosX:$PosX-2);

                        $pdf->SetFont('','B', 8);
                        $pdf->SetXY($PosXLabel+1,$PosY);
                        $pdf->SetFillColor(230,230,230);

                        if	($conf->global->LAB_THIRD_COMP)
                        {
                            $pdf->MultiCell(65,10,dol_trunc($societe->name,50),0,'L');
                            $flag=1;
                        }
                        $PosYLabel=$PosY+$Line+2;
                        if	($conf->global->LAB_THIRD_CONTACT)
                        {
                            $pdf->SetXY($PosXLabel,$PosYLabel);
                            $pdf->MultiCell(65,10,dol_trunc($societe->ref,50),0,'L');
                            $flag=2;
                        }
                        $PosYLabel=$PosY+$Line+2;

                        $pdf->SetFont('','', 10);

                        if	($conf->global->LAB_THIRD_ADDRESS)
                        {
                            $pdf->SetXY($PosXLabel,$PosYLabel);
                            $carac_client=dol_format_address($societe);

                            // Country
                            if (!empty($societe->country_code) && $societe->country_code != $mysoc->country_code) {
                                $carac_client.="\n".$langs->convToOutputCharset($langs->transnoentitiesnoconv("Country".$societe->country_code))."\n";
                            }
                            else if (empty($societe->country_code) && !empty($societe->country_code) && ($societe->country_code != $mysoc->country_code)) {
                                $carac_client.="\n".$langs->convToOutputCharset($langs->transnoentitiesnoconv("Country".$societe->country_code))."\n";
                            }

                            $pdf->MultiCell(65,10,$carac_client,0,'L');
                            $flag=3;
                        }
                        $PosYLabel=$PosY+$Line+2;

                        if ($conf->global->LAB_FREE_TEXT)
                        {
                            $pdf->SetXY($PosXLabel,$PosYLabel);
                            $pdf->MultiCell(65,10,dol_trunc($conf->global->LAB_FREE_TEXT,50),0,'L');
                            $flag=4;
                        }

                        $PosYLabel=$PosY+$Line;

                        $pdf->SetXY($PosXLabel,$PosYLabel);
                        $pdf->SetAutoPageBreak(true,1);

                        $PosX=$PosX+48;

                        if($PosX>=200)
                        {
                            $PosX=12;
                            if($conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_3)
                                $PosY=$PosY+25.1;
                            else
                                $PosY=$PosY+25.3;

                            if($PosY>=265)
                            {
                                if($conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_3)
                                    $PosY=24;
                                else
                                    $PosY=11;

                                $pdf->AddPage();
                            }
                        }
                        $n++;
                    }
                    else if($conf->global->MAIN_MODULE_LABELPRINT_LABELS_THIRD_5)
                    {
                        //Position X
                        $PosXLabel=($PosX<105?$PosX:$PosX-3);

                        //Soc Name
                        $pdf->SetFont('','B', 10);
                        $pdf->SetXY($PosXLabel,$PosY);
                        $pdf->SetFillColor(230,230,230);

                        if	($conf->global->LAB_THIRD_COMP)
                        {
                            $pdf->MultiCell(65,10,dol_trunc($societe->name,50),0,'L');
                            $flag=1;
                        }
                        $PosYLabel=$PosY+$Line+2;
                        if	($conf->global->LAB_THIRD_CONTACT)
                        {
                            $pdf->SetXY($PosXLabel,$PosYLabel);
                            $pdf->MultiCell(65,10,dol_trunc($societe->ref,50),0,'L');
                            $flag=2;
                        }
                        $PosYLabel=$PosY+$Line+2;

                        $pdf->SetFont('','', 10);

                        if	($conf->global->LAB_THIRD_ADDRESS)
                        {
                            $pdf->SetXY($PosXLabel,$PosYLabel);
                            $carac_client=dol_format_address($societe);

                            // Country
                            if (!empty($societe->country_code) && $societe->country_code != $mysoc->country_code) {
                                $carac_client.="\n".$langs->convToOutputCharset($langs->transnoentitiesnoconv("Country".$societe->country_code))."\n";
                            }
                            else if (empty($societe->country_code) && !empty($societe->country_code) && ($societe->country_code != $mysoc->country_code)) {
                                $carac_client.="\n".$langs->convToOutputCharset($langs->transnoentitiesnoconv("Country".$societe->country_code))."\n";
                            }

                            $pdf->MultiCell(65,10,$carac_client,0,'L');
                            $flag=3;
                        }
                        $PosYLabel=$PosY+$Line+2;

                        if ($conf->global->LAB_FREE_TEXT)
                        {
                            $pdf->SetXY($PosXLabel,$PosYLabel);
                            $pdf->MultiCell(65,10,dol_trunc($conf->global->LAB_FREE_TEXT,50),0,'L');
                            $flag=4;
                        }

                        //Position Y
                        $PosYLabel=$PosY+$Line+2;

                        $pdf->SetAutoPageBreak(false);

                        $PosX=$PosX+105;
                        if($PosX>=200)
                        {
                            $PosX=5;
                            $PosY=$PosY+37;

                            if($PosY>=265)
                            {
                                $PosY=5;
                                $pdf->AddPage();
                            }
                        }
                        $n++;
                    }*/
                }
                $i++;
            }
        }
        ini_set('display_errors', 'Off');
        $buf = $pdf->Output('', 'S');

        //$file_temp = ini_get("session.save_path")."/".dol_now().".pdf";
        $file_temp = DOL_DATA_ROOT . '/' . dol_now() . '.pdf';

        $gestor = fopen($file_temp, 'w');
        fwrite($gestor, $buf);
        fclose($gestor);
        $url = dol_buildpath('/labelprint/download.php', 1) . '?file=' . $file_temp;

        return $url;
        //print "<meta http-equiv='refresh' content='0;url=".$url."'>";
    }
}
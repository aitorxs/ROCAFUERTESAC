<?php
/* Copyright (C) 2011 Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012-2018 Ferran Marcet      <fmarcet@2byte.es>
 * Copyright (C) 2013 Iván Casco              <admin@gestionintegraltn.com>
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

require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
dol_include_once('/pos/class/ticket.class.php');
dol_include_once('/pos/class/payment.class.php');
require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");
dol_include_once('/pos/class/cash.class.php');
dol_include_once('/pos/backend/lib/errors.lib.php');
dol_include_once('/pos/class/place.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once (DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
dol_include_once('/pos/class/facturesim.class.php');
require_once (DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php');
require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");
if (! empty($conf->productbatch->enabled)) require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';

/**
 *	\class      POS
 *	\brief      Class for POS gestion
 */
class POS extends CommonObject
{


	/**
	 *  Return Categories list
	 * @param        int $idCat Id of Category, if 0 return Principal cats
	 * @return      array|int                Array with categories
	 */
	public static function getCategories($idCat = 0)
	{
		global $db;
		switch ($idCat) {
			case 0: //devolver las categorias con nivel 1
				$objCat = new Categorie($db);
				if (version_compare(DOL_VERSION, 3.8) >= 0) {
					$cats = $objCat->get_full_arbo('product');
				} else {
					$cats = $objCat->get_full_arbo(0);
				}
				//$cats = $objCat->get_full_arbo($idCat);

				if (count($cats) > 0) {
					$retarray = array();
					foreach ($cats as $key => $val) {
						if ($val['level'] < 2) {
							$val['image'] = self::getImageCategory($val['id'], false);
							//$val['thumb']= self::getImageCategory($val['id'],true);
							$retarray[] = $val;
						}
					}
					return $retarray;
				}
				break;

			case ($idCat > 0):
				$objCat = new Categorie($db);

				$result = $objCat->fetch($idCat);
				if ($result > 0) {
					$cats = $objCat->get_filles();
					//$cats = self::get_filles($idCat);
					if (is_array($cats) && count($cats) > 0) {
						$retarray = array();
						foreach ((array)$cats as $val) {
							$cat['id'] = $val->id;
							$cat['label'] = $val->label;
							$cat['fulllabel'] = $val->label;
							$cat['fullpath'] = '_' . $val->id;
							$cat['image'] = self::getImageCategory($val->id, false);
							//$cat['thumb']=self::getImageCategory($val->id,true);
							$retarray[] = $cat;
						}
						return $retarray;
					}
				}

				break;

			default:
				return -1;
				break;
		}
	}

	/**
	 *  Return path of a catergory image
	 * @param        int  $idCat Id of Category
	 * @param        bool $thumb If enabled use thumb
	 * @return      string                Image path
	 */
	public static function getImageCategory($idCat, $thumb)
	{
		global $conf, $db;

		$extName = "_small";
		$extImgTarget = ".png";
		$outDir = "thumbs";
		$maxWidth = 90;
		$maxHeight = 90;
		$quality = 50;

		if ($idCat > 0) {
			$objCat = new Categorie($db);
			$objCat->fetch($idCat);

			$pdir = get_exdir($idCat, 2, 0, 0, (object)$objCat, 'category') . "/" . $idCat . "/photos/";
			$dir = $conf->categorie->multidir_output[$objCat->entity] . '/' . $pdir;

            $realpath = null;

			foreach ($objCat->liste_photos($dir, 1) as $key => $obj) {
				$filename = $dir . $obj['photo'];
				$filethumbs = $dir . $obj['photo_vignette'];

				/*$fileName = preg_replace('/(\.gif|\.jpeg|\.jpg|\.png|\.bmp)$/i','',$filethumbs);
				$fileName = basename($fileName);
				$imgThumbName = $dir.$outDir.'/'.$fileName.$extName.$extImgTarget;

				$file_osencoded=$imgThumbName;*/
				if (!dol_is_file($filethumbs)) {
					require_once(DOL_DOCUMENT_ROOT . "/core/lib/images.lib.php");
					vignette($filename, $maxWidth, $maxHeight, $extName, $quality, $outDir, 3);
					$filethumbs = preg_replace('/(\.gif|\.jpeg|\.jpg|\.png|\.bmp)$/i', '', $obj['photo']);
					$filethumbs = basename($filethumbs);
					$obj['photo_vignette'] = $outDir . '/' . $filethumbs . $extName . $extImgTarget;
				}

				if (!$thumb) {
					$filename = $obj['photo'];
				} else {
					$filename = $obj['photo_vignette'];
				}

				$realpath = DOL_URL_ROOT . '/viewimage.php?modulepart=category&entity=' . $objCat->entity . '&file=' . urlencode($pdir . $filename);

			}
			if (!$realpath) {
				$realpath = DOL_URL_ROOT . '/viewimage.php?modulepart=product&file=' . urlencode('noimage.jpg');
			}
			return $realpath;
		}

	}

	/**
	 *  Return products by a category
	 * @param        int $idCat       Id of Category
	 * @param        int $more        list products position
	 * @param        int $ticketstate Ticket state (2= return)
	 * @return      array                List of products
	 */
	public static function getProductsbyCategory($idCat, $more, $ticketstate)
    {
        global $db, $conf;

        if ($idCat) //Productos de la categoría
        {
            $object = new Categorie($db);
            $result = $object->fetch($idCat);
            if ($result > 0) {
                if ($object->type == 0) {
                    return self::get_prod($idCat, $more, $ticketstate);


                }
            }
        } else //Productos sin categorías
        {

            $sql = "SELECT o.rowid as id, o.ref, o.label, o.description, o.price_ttc, o.price_min_ttc,";
            $sql .= " o.fk_product_type";
            $sql .= " FROM " . MAIN_DB_PREFIX . "product as o";

            if ($conf->global->POS_STOCK || $ticketstate == 1) {
                $sql .= " WHERE rowid NOT IN ";
                $sql .= " (SELECT fk_product FROM " . MAIN_DB_PREFIX . "categorie_product as cp, " . MAIN_DB_PREFIX . "categorie as c WHERE c.rowid = cp.fk_categorie AND entity IN (" . getEntity("category",
                        1) . "))";
                $sql .= " AND tosell=1";
                $sql .= " AND entity IN (" . getEntity("product", 1) . ")";
                if (!$conf->global->POS_SERVICES) {
                    $sql .= " AND o.fk_product_type = 0";
                }
            } else {
                $cashid = $_SESSION['TERMINAL_ID'];
                $cash = new Cash($db);
                $cash->fetch($cashid);
                $warehouse = $cash->fk_warehouse;

                $sql .= ", " . MAIN_DB_PREFIX . "product_stock as ps";
                $sql .= " WHERE o.rowid NOT IN ";
                $sql .= " (SELECT fk_product FROM " . MAIN_DB_PREFIX . "categorie_product as cp, " . MAIN_DB_PREFIX . "categorie as c WHERE c.rowid = cp.fk_categorie AND entity IN (" . getEntity("category",
                        1) . "))";
                $sql .= " AND tosell=1";
                $sql .= " AND entity IN (" . getEntity("product", 1) . ")";
                $sql .= " AND o.rowid = ps.fk_product";
                $sql .= " AND ps.fk_entrepot = " . $warehouse;
                $sql .= " AND ps.reel > 0";
                if ($conf->global->POS_SERVICES) {
                    $sql .= " union select o.rowid as id, o.ref, o.label, o.description, o.price_ttc, o.price_min_ttc, 	";
                    $sql .= " o.fk_product_type";
                    $sql .= " FROM " . MAIN_DB_PREFIX . "product as o";
                    $sql .= " WHERE o.rowid NOT IN ";
                    $sql .= " (SELECT fk_product FROM " . MAIN_DB_PREFIX . "categorie_product)";
                    $sql .= " AND tosell=1";
                    $sql .= " AND fk_product_type=1";
                    $sql .= " AND entity IN (" . getEntity("product", 1) . ")";
                }
            }
            $sql.= ' ORDER BY label';
            if ($more >= 0) {
                $sql .= " LIMIT " . $more . ",10 ";
            }

            $res = $db->query($sql);

            if ($res) {
                $num = $db->num_rows($res);
                $i = 0;

                while ($i < $num) {
                    $objp = $db->fetch_object($res);

                    $ret[$objp->label]["id"] = $objp->id;
                    $ret[$objp->label]["ref"] = $objp->ref;
                    $ret[$objp->label]["label"] = $objp->label;
                    $ret[$objp->label]["price_ttc"] = $objp->price_ttc;
                    $ret[$objp->label]["price_min_ttc"] = $objp->price_min_ttc;
                    $ret[$objp->label]["description"] = $objp->description;

                    $ret[$objp->label]["image"] = self::getImageProduct($objp->id, false);
                    $ret[$objp->label]["thumb"] = self::getImageProduct($objp->id, true);

                    $i++;

                }
                return $ret;
            } else {
                return -1;
            }
        }
        return -1;
    }

	/**
	 *  Return a catergory
	 * @param        int $idCat Id of Category
	 * @return      array                Category info
	 */
	public static function getCategorybyId($idCat)
	{
		global $db;
		$objCat = new Categorie($db);
		$result = $objCat->fetch($idCat);
		if ($result > 0) {
			return $objCat;
		}
		return -1;
	}

	/**
	 *  Return product info
	 * @param        int $idProd Id of Product
	 * @return      array                Product info
	 */
	public static function getProductbyId($idProd, $idCust)
	{
		global $db, $conf, $mysoc;
		if ($conf->global->PRODUIT_MULTIPRICES) {
			$sql = "SELECT price_level";
			$sql .= " FROM " . MAIN_DB_PREFIX . "societe";
			$sql .= " WHERE rowid = " . $idCust;
			$res = $db->query($sql);
			if ($res) {
				$obj = $db->fetch_object($res);
				if ($obj->price_level == null) {
					$pricelevel = 1;
				} else {
					$pricelevel = $obj->price_level;
				}
			}
		} else {
			$pricelevel = 1;
		}

		$function = "getProductbyId";

		$objp = new Product($db);
		$objp->fetch($idProd);

        $societe = new Societe($db);
        $societe->fetch($idCust);

        $ret[0]["tva_tx"] = get_default_tva($mysoc, $societe, $idProd);

		$ret[0]["id"] = $objp->id;
		$ret[0]["ref"] = $objp->ref;
		$ret[0]["label"] = $objp->label;
		$ret[0]["description"] = $objp->description;
		$ret[0]["fk_product_type"] = $objp->type;
		$ret[0]["diff_price"] = 0;

		if (! empty($conf->productbatch->enabled)) {
			$ret[0]["batch"] = $objp->status_batch;
		}
		else {
			$ret[0]["batch"] = 0;
		}

		if (! empty($conf->ecotaxdeee->enabled)) {
			$ret[0]["ecotax"] = self::searchEcotax($idProd);
		}
		else {
			$ret[0]["exotax"] = 0;
		}

		if (!empty($objp->multiprices[$pricelevel]) && $objp->multiprices[$pricelevel] > 0) {
			//$ret[0]["tva_tx"] = $objp->multiprices_tva_tx[$pricelevel];
			$ret[0]["price_base_type"] = $objp->multiprices_base_type[$pricelevel];
			$ret[0]["price_ht"] = $objp->multiprices[$pricelevel];
			$ret[0]["price_ttc"] = $objp->multiprices_ttc[$pricelevel];
			$ret[0]["price_min_ht"] = $objp->multiprices_min[$pricelevel];
			$ret[0]["price_min_ttc"] = $objp->multiprices_min_ttc[$pricelevel];
		} else {
			if ($conf->global->PRODUIT_CUSTOMER_PRICES) {

				require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';
				$prodcustprice = new Productcustomerprice($db);
				$filter = array('t.fk_product' => $objp->id, 't.fk_soc' => $idCust);

				$result = $prodcustprice->fetch_all('ASC', 't.rowid', 0, 0, $filter);
				if ($result >= 0) {
					if (count($prodcustprice->lines) > 0) {
						$ret[0]["price_ht"] = $prodcustprice->lines[0]->price;
						$ret[0]["price_ttc"] = $prodcustprice->lines[0]->price_ttc;
						$ret[0]["price_min_ht"] = $prodcustprice->lines[0]->price_min;
						$ret[0]["price_min_ttc"] = $prodcustprice->lines[0]->price_min_ttc;
						$ret[0]["price_base_type"] = $prodcustprice->lines[0]->price_base_type;
						//$ret[0]["tva_tx"] = $prodcustprice->lines[0]->tva_tx;
					} else {
						$ret[0]["price_ht"] = $objp->price;
						$ret[0]["price_ttc"] = $objp->price_ttc;
						$ret[0]["price_min_ht"] = $objp->price_min;
						$ret[0]["price_min_ttc"] = $objp->price_min_ttc;
						$ret[0]["price_base_type"] = $objp->price_base_type;
						//$ret[0]["tva_tx"] = $objp->tva_tx;
					}
				}
			} else {
				//$ret[0]["tva_tx"] = $objp->tva_tx;
				$ret[0]["price_base_type"] = $objp->price_base_type;
				$ret[0]["price_ht"] = $objp->price;
				$ret[0]["price_ttc"] = $objp->price_ttc;
				$ret[0]["price_min_ht"] = $objp->price_min;
				$ret[0]["price_min_ttc"] = $objp->price_min_ttc;
				if ($conf->global->PRODUIT_MULTIPRICES) {
					$ret[0]["diff_price"] = 1;
				}
			}
		}
		$ret[0]["localtax1_tx"] = $objp->localtax1_tx;
		$ret[0]["localtax2_tx"] = $objp->localtax2_tx;

		$objp->load_stock();

		$cash = new Cash($db);

		$terminal = $_SESSION['TERMINAL_ID'];
		$cash->fetch($terminal);

		//TODO controla si estamos vendiendo sin stock y controla que haya al menos una unidad
		if (!$conf->global->POS_STOCK) {
			if (($conf->global->STOCK_SUPPORTS_SERVICES && $objp->type == 1) || $objp->type == 0) {
				$ret[0]["stock"] = $objp->stock_warehouse[$cash->fk_warehouse]->real;
			} else {
				$ret[0]["stock"] = "all";
			}
		} else {
			$ret[0]["stock"] = "all";
		}

		$ret[0]["orig_price"] = $ret[0]["price_ht"];
		$ret[0]["is_promo"] = 0;

		$ret[0]["image"] = self::getImageProduct($objp->id, false);
		$ret[0]["thumb"] = self::getImageProduct($objp->id, true);

		if ($conf->discounts->enabled) {
			$ret[0]["socid"] = $idCust;
			$ret[0]["idProduct"] = $idProd;
			$ret[0]["cant"] = 1;

			$precios = self::calculePrice($ret[0]);

			$ret[0]["price_ht"] = $precios["pu_ht"];
			$ret[0]["price_ttc"] = $precios["pu_ttc"];
		}

        if ($conf->global->POS_PRICE_MIN) {
            $ret[0]["price_ht"] = ($ret[0]["price_min"] > 0 ? $ret[0]["price_min"] : $ret[0]["price"]);
            $ret[0]["price_ttc"] = ($ret[0]["price_min_ttc"] > 0 ? $ret[0]["price_min_ttc"] : $ret[0]["price_ttc"]);
        }

		return Errorcontrol($ret, $function);
	}

	/**
	 *  Return product info
	 *
	 * @param        string  $idSearch    Part of code, label or barcode
	 * @param        boolean $stock       Return stocks of products into info
	 * @param        int     $warehouse   Warehouse id
	 * @param                int          mode                Mode of search
	 * @param        int     $ticketstate Ticket state
	 * @return      array                    Product info
	 */
	public static function SearchProduct(
			$idSearch,
			$stock = false,
			$warehouse,
			$mode = 0,
			$ticketstate = 0,
			$customerId
	) {
		global $db, $conf;

		$ret = array();
		$function = "getProductbyId";

		if (dol_strlen($idSearch) != 0 && dol_strlen($idSearch) < $conf->global->PRODUIT_USE_SEARCH_TO_SELECT && $mode != -5 && $mode != -6) {
			return ErrorControl(-2, $function);
		}

		$prefix = empty($conf->global->PRODUCT_DONOTSEARCH_ANYWHERE) ? '%' : '';    // Can use index if PRODUCT_DONOTSEARCH_ANYWHERE is on

		if ($mode >= 0) {
			if ($stock) {
				$sql = "SELECT distinct p.rowid, p.ref, p.label ,";
				$sql .= "(select w.reel from " . MAIN_DB_PREFIX . "product_stock w left join " . MAIN_DB_PREFIX . "entrepot e on w.fk_entrepot = e.rowid";
				$sql .= " where w.fk_product = p.rowid and e.rowid=ep.rowid) as stock";
				if (version_compare(DOL_VERSION, 7.0) >= 0) {
					$sql .= " , ep.ref as warehouse,";
				}
				else{
					$sql .= " , ep.label as warehouse,";
				}
				$sql .= " ep.rowid as warehouseId";
				$sql .= " FROM " . MAIN_DB_PREFIX . "product p, " . MAIN_DB_PREFIX . "entrepot ep ";

			} else {
				if ($conf->global->PRODUIT_MULTIPRICES) {
					$sql = "SELECT price_level";
					$sql .= " FROM " . MAIN_DB_PREFIX . "societe";
					$sql .= " WHERE rowid = " . $customerId;
					$res = $db->query($sql);
					if ($res) {
						$obj = $db->fetch_object($res);
						if ($obj->price_level == null) {
							$pricelevel = 1;
						} else {
							$pricelevel = $obj->price_level;
						}
					}
				} else {
					$pricelevel = 1;
				}
				$sql = "SELECT p.rowid, p.ref, p.label, ep.rowid as warehouseId";
				$sql .= " FROM " . MAIN_DB_PREFIX . "product as p, " . MAIN_DB_PREFIX . "product_stock as w, " . MAIN_DB_PREFIX . "entrepot as ep ";
			}

			$sql .= " WHERE p.tosell = 1 AND ep.statut = 1";
			$sql .= " AND p.entity IN (" . getEntity("product", 1) . ")";
			$sql .= " AND ep.entity IN (" . getEntity("stock", 1) . ")";
			if ($warehouse > 0) {
				$sql .= " AND ep.rowid = " . $warehouse;
			}
			if (!$stock) {
				if (!$conf->global->POS_STOCK && $ticketstate != 1) {
                    $sql .= " AND w.fk_product = p.rowid AND ep.rowid=w.fk_entrepot ";
					$sql .= " AND w.reel > 0";
				}
			}

			if (!$conf->global->POS_SERVICES || $stock) {
				$sql .= " AND p.fk_product_type = 0";
			}

			$sql .= " AND (p.ref LIKE '" . $prefix . $db->escape(trim($idSearch)) . "%' OR p.label LIKE '" . $prefix . $db->escape(trim($idSearch)) . "%' ";

			if ($conf->barcode->enabled) {
				$sql .= " OR p.barcode='" . $db->escape(trim($idSearch)) . "')";
			} else {
				$sql .= ")";
			}

			if (!$stock && $conf->global->POS_SERVICES) {
				$sql = "SELECT p.rowid, p.ref, p.label, ep.rowid as warehouseId";
				$sql .= " FROM " . MAIN_DB_PREFIX . "product as p left join " . MAIN_DB_PREFIX . "product_stock as w on w.fk_product = p.rowid, " . MAIN_DB_PREFIX . "entrepot as ep ";
				$sql .= " WHERE";
				if (!$conf->global->POS_STOCK) {
					$sql .= " ep.rowid = " . $warehouse." AND";
				}
				$sql.= " ((p.tosell = 1 AND  p.entity IN (" . getEntity("product", 1) . ")";
				if (!$conf->global->POS_STOCK && $ticketstate != 1) {
					$sql .= " AND ep.rowid=w.fk_entrepot ";
				}
				$sql .= " AND (p.ref LIKE '" . $prefix . $db->escape(trim($idSearch)) . "%' OR p.label LIKE '" . $prefix . $db->escape(trim($idSearch)) . "%' ";
				if ($conf->barcode->enabled) {
					$sql .= " OR p.barcode='" . $db->escape(trim($idSearch)) . "')";
				} else {
					$sql .= ")";
				}
				if (!$conf->global->POS_STOCK && $ticketstate != 1) {
					$sql .= " AND ep.rowid = " . $warehouse . " AND w.reel > 0";
				}

				$sql .= " ) OR (p.tosell = 1 AND p.entity IN (" . getEntity("product",
								1) . ") AND p.fk_product_type = 1";
				$sql .= " AND (p.ref LIKE '" . $prefix . $db->escape(trim($idSearch)) . "%' OR p.label LIKE '" . $prefix . $db->escape(trim($idSearch)) . "%' ";
				if ($conf->barcode->enabled) {
					$sql .= " OR p.barcode='" . $db->escape(trim($idSearch)) . "')";
				} else {
					$sql .= ")";
				}

				$sql .= "))";
			}

			if (!$stock /*&& $conf->global->POS_STOCK*/) {
				$sql .= " GROUP BY p.label, p.rowid";
			} else {
				$sql .= " GROUP BY p.rowid, ";
				if( version_compare(DOL_VERSION, 7.0) >= 0){
					$sql .= "ep.ref";
				}
				else{
					$sql .= "ep.label";
				}
				$sql .= " ORDER BY p.label, ep.rowid";
			}
			$sql .= " LIMIT 100";
		} else {
			$sql = "SELECT distinct p.rowid, p.ref, p.label , w.reel as stock, w.fk_entrepot as warehouseId,";
			if (version_compare(DOL_VERSION, 7.0) >= 0) {
				$sql .= "e.ref as warehouse ";
			}
			else{
				$sql .= "e.label as warehouse ";
			}
			$sql .= " FROM " . MAIN_DB_PREFIX . "product p INNER JOIN " . MAIN_DB_PREFIX . "product_stock w ON w.fk_product=p.rowid ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "entrepot e ON e.rowid=w.fk_entrepot";
			$sql .= " WHERE p.entity IN (" . getEntity("product", 1) . ")";
			$sql .= " AND w.fk_entrepot=" . $warehouse;
			if (!$conf->global->POS_SERVICES) {
				$sql .= " AND p.fk_product_type = 0";
			}
			$sql .= " AND (p.ref LIKE '" . $prefix . $db->escape(trim($idSearch)) . "%' OR p.label LIKE '" . $prefix . $db->escape(trim($idSearch)) . "%' ";
			if ($conf->barcode->enabled) {
				$sql .= " OR p.barcode='" . $db->escape(trim($idSearch)) . "')";
			} else {
				$sql .= ")";
			}
			if ($mode == -1) {//no sell
				$sql .= " AND p.tosell = 0";
				$sql .= " LIMIT 100";
			}
			if ($mode == -2) {//sell
				$sql .= " AND p.tosell = 1";
				$sql .= " LIMIT 100";
			}
			if ($mode == -3) {//with stock
				$sql .= " AND w.reel > 0";
				$sql .= " LIMIT 100";
			}
			if ($mode == -4) {//no stock
				$sql .= " AND w.reel <= 0";
				$sql .= " LIMIT 100";
			}
			if ($mode == -5) {//best sell
				$sql = "SELECT SUM(fd.qty) as qty, pr.rowid, pr.ref, pr.label, ";
				$sql .= "	(select w.reel from " . MAIN_DB_PREFIX . "product_stock w left join " . MAIN_DB_PREFIX . "entrepot e on w.fk_entrepot = e.rowid";
				$sql .= " where w.fk_product = pr.rowid and e.rowid=ep.rowid) as stock, ";
				if (version_compare(DOL_VERSION, 7.0) >= 0){
					$sql .= "ep.ref as warehouse, ";
				}
				else{
					$sql .= "ep.label as warehouse, ";
				}
				$sql .= "ep.rowid as warehouseId";
				$sql .= " FROM " . MAIN_DB_PREFIX . "facturedet as fd, " . MAIN_DB_PREFIX . "facture as f, " . MAIN_DB_PREFIX . "product as pr,";
				$sql .= " " . MAIN_DB_PREFIX . "entrepot as ep, " . MAIN_DB_PREFIX . "pos_facture as pf ";
				$sql .= " WHERE ep.rowid = " . $warehouse . " and pr.tosell = 1 AND f.rowid = fd.fk_facture AND f.entity = " . $conf->entity . " and pr.rowid = fd.fk_product";
				if (!$conf->global->POS_SERVICES) {
					$sql .= " AND pr.fk_product_type = 0";
				}
				$sql .= " and pf.fk_facture = f.rowid GROUP BY fd.fk_product ORDER BY qty DESC limit 10";
			}
			if ($mode == -6) {//worst sell

				$sql = "SELECT 0 as qty, pr.rowid, pr.ref, pr.label, (select w.reel";
				$sql .= " from " . MAIN_DB_PREFIX . "product_stock w left join " . MAIN_DB_PREFIX . "entrepot e on w.fk_entrepot = e.rowid";
				$sql .= " where w.fk_product = pr.rowid and e.rowid=ep.rowid) as stock,";
				if (version_compare(DOL_VERSION, 7.0) >= 0) {
					$sql .= " ep.ref as warehouse,";
				}
				else{
					$sql .= " ep.label as warehouse,";
				}
				$sql .= " ep.rowid as warehouseId";
				$sql .= " from " . MAIN_DB_PREFIX . "product as pr, " . MAIN_DB_PREFIX . "entrepot as ep";
				$sql .= " where pr.rowid not in ( SELECT p.rowid";
				$sql .= " FROM " . MAIN_DB_PREFIX . "facturedet as fd, " . MAIN_DB_PREFIX . "facture as f, " . MAIN_DB_PREFIX . "product as p, " . MAIN_DB_PREFIX . "pos_facture as pf";
				$sql .= " WHERE p.tosell = 1 AND f.rowid = fd.fk_facture AND f.entity = " . $conf->entity . " and p.rowid = fd.fk_product";

				if (!$conf->global->POS_SERVICES) {
					$sql .= " AND p.fk_product_type = 0";
				}
				$sql .= " and pf.fk_facture = f.rowid group by fd.fk_product ) AND ep.rowid = " . $warehouse;
				if (!$conf->global->POS_SERVICES) {
					$sql .= " AND pr.fk_product_type = 0";
				}
				$sql .= " ORDER BY qty ASC limit 10";
			}
		}
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
            $warehouse = new Entrepot($db);
			while ($i < $num) {
				$objp = $db->fetch_object($resql);

				$ret[$i]["id"] = $objp->rowid;
				$ret[$i]["ref"] = $objp->ref;
				$ret[$i]["label"] = $objp->label;
				$ret[$i]["warehouseId"] = $objp->warehouseId;
                $warehouse->fetch($objp->warehouseId);
                $ret[$i]["warehouseName"] = $warehouse->libelle;

				if ($stock) {
					$ret[$i]["warehouse"] = $objp->warehouse;
					if ($objp->stock) {
						$ret[$i]["stock"] = $objp->stock;
					} else {
						$ret[$i]["stock"] = 0;
					}
					$ret[$i]["flag"] = $conf->global->POS_STOCK;
				} else {
					$prod = new Product($db);
					$prod->fetch($objp->rowid);

					if (!empty($prod->multiprices[$pricelevel]) && $prod->multiprices[$pricelevel] > 0) {
						$ret[$i]["price_ttc"] = $prod->multiprices_ttc[$pricelevel];
					} else {
						$ret[$i]["price_ttc"] = $prod->price_ttc;
					}
				}
				$i++;

			}
			if ($mode == -6 && $num < 10) {
				$resto = 10 - $num;
				$sql = "SELECT SUM(facd.qty) as qty, p.rowid, p.ref, p.label, (select wa.reel";
				$sql .= " from " . MAIN_DB_PREFIX . "product_stock wa left join " . MAIN_DB_PREFIX . "entrepot entr on wa.fk_entrepot = entr.rowid";
				$sql .= " where wa.fk_product = p.rowid and entr.rowid=en.rowid) as stock,";
				$sql .= " en.label as warehouse, en.rowid as warehouseId";
				$sql .= " FROM " . MAIN_DB_PREFIX . "facturedet as facd, " . MAIN_DB_PREFIX . "facture as fac, " . MAIN_DB_PREFIX . "product as p, " . MAIN_DB_PREFIX . "entrepot as en, " . MAIN_DB_PREFIX . "pos_facture as pfac";
				$sql .= " WHERE p.tosell = 1 AND fac.rowid = facd.fk_facture AND fac.entity = " . $conf->entity;
				$sql .= " AND facd.fk_product != 'NULL'and p.rowid = facd.fk_product AND pfac.fk_facture = fac.rowid and en.rowid = " . $warehouse->id;
				$sql .= " group by facd.fk_product";
				$sql .= " order by qty ASC limit " . $resto;

				$resql = $db->query($sql);
				if ($resql) {
					$num2 = $db->num_rows($resql);
					$i = $num;

					while ($i < $num2) {
						$objp = $db->fetch_object($resql);

						$ret[$i]["id"] = $objp->rowid;
						$ret[$i]["ref"] = $objp->ref;
						$ret[$i]["label"] = $objp->label;
						$ret[$i]["warehouseId"] = $objp->warehouseId;

						if ($stock) {
							$ret[$i]["warehouse"] = $objp->warehouse;
							if ($objp->stock) {
								$ret[$i]["stock"] = $objp->stock;
							} else {
								$ret[$i]["stock"] = 0;
							}
							$ret[$i]["flag"] = $conf->global->POS_STOCK;
						}
						$i++;

					}
				}
			}
		}

		return ErrorControl(count($ret)>0?$ret:-1, $function);
	}


	public static function getBatchProduct($idProd, $idFac)
	{
		global $db, $conf;


		$function = "getBatchProduct";

		$objp = new Product($db);
		$objp->fetch($idProd);


		if (! empty($conf->productbatch->enabled)) {
			$ret[0]["batch"] = $objp->status_batch;
			if ($objp->status_batch) {
				$sql = "SELECT DISTINCT batch";
				$sql.= " FROM " . MAIN_DB_PREFIX . "stock_mouvement";
				$sql.= " WHERE fk_origin =".$idFac;
				$sql.= " AND origintype='facture'";

				$res = $db->query($sql);

				if ($res) {

					$num = $db->num_rows($res);
					$i = 0;

					while ($i < $num) {

						$obj = $db->fetch_object($res);

						$batchs[$i]["id"] = $i+1;
						$batchs[$i]["batch"] = $obj->batch;

						$i++;
					}
					$ret[0]['batchs'] = $batchs;
				}

			}
		}
		else {
			$ret[0]["batch"] = 0;
		}

		if (! empty($conf->ecotaxdeee->enabled)) {
			$ret[0]["ecotax"] = self::searchEcotax($idProd);
		}
		else {
			$ret[0]["exotax"] = 0;
		}


		return Errorcontrol($ret, $function);
	}


	public static function CountProduct($warehouseId)
	{
		global $db;

		$ret = -1;
		$function = "getProductbyId";

		$sql = "select(select count(p.rowid) from " . MAIN_DB_PREFIX . "product p, " . MAIN_DB_PREFIX . "product_stock ps where p.tosell = 0 and p.fk_product_type = 0 and ps.fk_entrepot = " . $warehouseId . " and ps.fk_product = p.rowid) as no_venta, ";
		$sql .= "(select count(p.rowid) from " . MAIN_DB_PREFIX . "product p, " . MAIN_DB_PREFIX . "product_stock ps where p.tosell = 1 and p.fk_product_type = 0 and ps.fk_entrepot = " . $warehouseId . " and ps.fk_product = p.rowid) as en_venta, ";
		$sql .= "(select count(p.rowid) from " . MAIN_DB_PREFIX . "product p, " . MAIN_DB_PREFIX . "product_stock ps where p.fk_product_type = 0 ";
		$sql .= "and ps.fk_entrepot = " . $warehouseId . " and ps.reel > 0 and ps.fk_product = p.rowid) as con_stock, ";
		$sql .= "(select count(p.rowid) from " . MAIN_DB_PREFIX . "product p, " . MAIN_DB_PREFIX . "product_stock ps where p.fk_product_type = 0 ";
		$sql .= " and ps.fk_entrepot = " . $warehouseId . " and ps.reel <= 0 and ps.fk_product = p.rowid) as sin_stock";

		$res = $db->query($sql);

		if ($res) {
			$obj = $db->fetch_object($res);

			$result["no_sell"] = $obj->no_venta;
			$result["sell"] = $obj->en_venta;
			$result["stock"] = $obj->con_stock;
			$result["no_stock"] = $obj->sin_stock;
			$result["best_sell"] = 10;
			$result["worst_sell"] = 10;

			return ErrorControl($result, $function);
		} else {
			return ErrorControl($ret, $function);
		}


	}


	/**
	 *  Return customer info
	 *
	 * @param        string  $idSearch Part of code, name, firstname, idprof1
	 * @param        boolean $extended Return more info
	 * @return      array                    Customer info
	 */
	public static function SearchCustomer($idSearch, $extended = false)
	{
		global $db, $conf, $user;

		$ret = array();
		$function = "SearchCustomer";

		if (dol_strlen($idSearch) <= $conf->global->COMPANY_USE_SEARCH_TO_SELECT) {
			return ErrorControl(-2, $function);
		}

		$prefix = empty($conf->global->COMPANY_DONOTSEARCH_ANYWHERE) ? '%' : '';    // Can use index if COMPANY_DONOTSEARCH_ANYWHERE is on

		$sql = "SELECT c.rowid, c.nom, c.code_client, c.siren, c.remise_client";
		$sql .= " FROM " . MAIN_DB_PREFIX . "societe as c";
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON c.rowid = sc.fk_soc AND sc.fk_user = " . $user->id;
		} // We need this table joined to the select in order to filter by sale
		$sql .= " WHERE c.client IN (1,3)";
		$sql .= " AND c.entity IN (" . getEntity("societe",1).")";
		$sql .= " AND (c.nom LIKE '" . $prefix . $db->escape(trim($idSearch)) . "%' OR c.code_client LIKE '" . $prefix . $db->escape(trim($idSearch)) . "%' OR c.siren LIKE '" . $prefix . $db->escape(trim($idSearch)) . "%' ";
		$sql .= ")";
		$sql .= " ORDER BY c.nom";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			$soc = new Societe($db);
			unset($ret);

			while ($i < $num) {
				$objp = $db->fetch_object($resql);
				$ret[$i]['points'] = null;
				if ($conf->global->REWARDS_POS && !empty($conf->rewards->enabled)) {
                    dol_include_once('/rewards/class/rewards.class.php');
					$rew = new Rewards($db);
					$res = $rew->getCustomerReward($objp->rowid);
					if ($res) {
						$ret[$i]['points'] = $rew->getCustomerPoints($objp->rowid);
					}
				}
				$soc->fetch($objp->rowid);
				$ret[$i]["coupon"] = $soc->getAvailableDiscounts();
				$ret[$i]["id"] = $objp->rowid;
				$ret[$i]["nom"] = $objp->nom;
				$ret[$i]["profid1"] = $objp->siren;
				$ret[$i]["remise"] = $objp->remise_client;
				$i++;

			}
		}
		return ErrorControl(count($ret)>0?$ret:-1, $function);
	}

	/**
	 *  Return path of a catergory image
	 *
	 * @param        int $idCat Id of Category
	 * @return      string                Image path
	 */
	public static function getImageProduct($idProd, $thumb = false)
	{
		global $conf, $db;

		$extName = "_small";
		$extImgTarget = ".png";
		$outDir = "thumbs";
		$maxWidth = 90;
		$maxHeight = 90;
		$quality = 50;

		if ($idProd > 0) {
			$objProd = new Product($db);
			$objProd->fetch($idProd);

			if (!empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) {
				$pdir[0] = get_exdir($objProd->id, 2, 0, 0, $objProd, 'product') . "/" . $objProd->id . "/photos/";
				$pdir[1] = dol_sanitizeFileName($objProd->ref) . '/';
			} else {
				$pdir[0] = dol_sanitizeFileName($objProd->ref) . '/';
				$pdir[1] = get_exdir($objProd->id, 2, 0, 0, $objProd, 'product') . "/" . $objProd->id . "/photos/";
			}
			$arephoto = false;
			foreach ($pdir as $midir) {
				if (!$arephoto) {
					$dir = $conf->product->multidir_output[$objProd->entity] . '/' . $midir;

					foreach ($objProd->liste_photos($dir, 1) as $key => $obj) {
						$filename = $dir . $obj['photo'];
						$filethumbs = $dir . $obj['photo_vignette'];

						/*$fileName = preg_replace('/(\.gif|\.jpeg|\.jpg|\.png|\.bmp)$/i','',$filethumbs);
						$fileName = basename($fileName);
						$imgThumbName = $dir.$outDir.'/'.$fileName.$extName.$extImgTarget;

						$file_osencoded=$imgThumbName;
						"\.jpg|\.jpeg|\.bmp|\.gif|\.png|\.tiff" */
						if (!dol_is_file($filethumbs)) {
							require_once(DOL_DOCUMENT_ROOT . "/core/lib/images.lib.php");
							vignette($filename, $maxWidth, $maxHeight, $extName, $quality, $outDir, 3);
							$filethumbs = preg_replace('/(\.gif|\.jpeg|\.jpg|\.png|\.bmp)$/i', '', $obj['photo']);
							$filethumbs = basename($filethumbs);
							$obj['photo_vignette'] = $outDir . '/' . $filethumbs . $extName . $extImgTarget;
						}

						if (!$thumb) {
							$filename = $obj['photo'];
						} else {
							$filename = $obj['photo_vignette'];
						}

						$realpath = DOL_URL_ROOT . '/viewimage.php?modulepart=product&entity=' . $objProd->entity . '&file=' . urlencode($midir . $filename);
						$arephoto = true;
					}
				}
			}
			if (!$realpath) {
				$realpath = DOL_URL_ROOT . '/viewimage.php?modulepart=product&file=' . urlencode('noimage.jpg');
			}
			return $realpath;
		}

	}

	/**
	 *  Returns internal users of Dolibarr
	 * @param        string $selected RowId of user for select
	 * @param        string $htmlname name for object
	 * @return      array                    Dolibarr internal users
	 */
	public static function select_Users($selected = '', $htmlname = 'users')
	{
		global $db, $conf;

		$sql = "SELECT rowid, lastname, firstname, login";
		$sql .= " FROM " . MAIN_DB_PREFIX . "user";
		$sql .= " WHERE entity IN (0," . $conf->entity . ")";
		if ($conf->global->POS_USER_TERMINAL) {
			$sql .= " AND rowid IN(";
			$sql .= "SELECT fk_object";
			$sql .= " FROM " . MAIN_DB_PREFIX . "pos_users";
			$sql .= " WHERE fk_terminal = " . $_SESSION["TERMINAL_ID"];
			//$sql.= " AND fk_object = ".$_SESSION["uid"];
			$sql .= " AND objtype = 'user'";

			$sql .= "UNION SELECT u.rowid";
			$sql .= " FROM " . MAIN_DB_PREFIX . "pos_users as pu";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "usergroup_user as g ON pu.fk_object = g.fk_usergroup";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON u.rowid = g.fk_user";
			$sql .= " WHERE pu.fk_terminal = " . $_SESSION["TERMINAL_ID"];
			//$sql.= " AND g.fk_user = ".$_SESSION["uid"];
			$sql .= " AND pu.objtype = 'group')";
		}

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$var = true;
			$i = 0;

			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$var = !$var;
				if (!$obj->fk_societe) {
					$userstatic = new User($db);
					$userstatic->fetch($obj->rowid);
					$userstatic->getrights('pos');
					$dir = $conf->user->dir_output;
					$file = '';

					if ($userstatic->rights->pos->frontend) {
						$username = $obj->firstname . ' ' . $obj->lastname;
						$internalusers[$i]['code'] = $obj->rowid;
						$internalusers[$i]['label'] = $username;
						$internalusers[$i]['login'] = $obj->login;

						if ($userstatic->photo) {
							$file = get_exdir($userstatic->id, 2, 0, 1, $userstatic, 'user') . "/" . $userstatic->photo;
						}
						if ($file && file_exists($dir . "/" . $file)) {
							$internalusers[$i]['photo'] = DOL_URL_ROOT . '/viewimage.php?modulepart=userphoto&entity=' . $userstatic->entity . '&file=' . urlencode($file);
						} else {
							if (version_compare(DOL_VERSION, 3.8) >= 0) {

								if ($userstatic->gender == "woman") {

									$internalusers[$i]['photo'] = DOL_URL_ROOT . '/public/theme/common/user_woman.png';
								} else {

									$internalusers[$i]['photo'] = DOL_URL_ROOT . '/public/theme/common/user_man.png';
								}

							} else {

								$internalusers[$i]['photo'] = DOL_URL_ROOT . '/theme/common/nophoto.jpg';
							}

						}
					}
				}

				$i++;
			}
			$db->free($resql);
		}

		return $internalusers;
	}

	/**
	 * Returns the type payments
	 *
	 * @return        array                    type of payments
	 */
	public static function select_Type_Payments()
	{
		global $db, $langs;

		$cash = new Cash($db);

		$terminal = $_SESSION['TERMINAL_ID'];
		$cash->fetch($terminal);

		$sql = "SELECT id, code, libelle, type";
		$sql .= " FROM " . MAIN_DB_PREFIX . "c_paiement";
		$sql .= " WHERE active > 0 and (id = " . $cash->fk_modepaycash . " or id =" . $cash->fk_modepaybank . " or id =" . $cash->fk_modepaybank_extra . ")";
		$sql .= " ORDER BY id";

		$resql = $db->query($sql);

		if ($resql) {
			$langs->load("bills");
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$j = $obj->id == $cash->fk_modepaycash ? 0 : ($obj->id == $cash->fk_modepaybank ? 1 : 2);

				$libelle = ($langs->trans("PaymentTypeShort" . $obj->code) != ("PaymentTypeShort" . $obj->code) ? $langs->trans("PaymentTypeShort" . $obj->code) : ($obj->libelle != '-' ? $obj->libelle : ''));
				$payments[$j]['id'] = $obj->id;
				$payments[$j]['code'] = $obj->code;
				$payments[$j]['label'] = $libelle;
				$payments[$j]['type'] = $obj->type;
				$i++;
			}
			$db->free($resql);
		}


		return $payments;

	}

	/**
	 *    Get object and lines from database
	 * @param        int $idTicket Id of ticket
	 * @return        Object            object if OK, <0 if KO
	 */
	public static function fetch($idTicket)
	{
		global $db;
		$object = new Ticket($db);
		$res = $object->fetch($idTicket);
		if ($res) {
			return $object;
		} else {
			return -1;
		}
	}

	/**
	 *
	 * Set Ticket into DB
	 *
	 * @param        array $aryTicket Ticket object
	 * @return        array    $result        Result
	 */
	public static function SetTicket($aryTicket)
	{
		$function = "SetTicket";
		$res = 0;

		$data = $aryTicket['data'];

		if (count($data) > 0) {
			if ($data['mode'] == 0) {
				if ($data['id']) {
					$res = self::UpdateTicket($aryTicket);
				} else {
					$res = self::CreateTicket($aryTicket);
				}
			} else {
				$res = self::CreateFacture($aryTicket);
			}

		}

		return ErrorControl($res, $function);
	}

	/**
	 *
	 * Get Ticket from DB
	 *
	 * @param    int $id Id Ticket to load
	 * @return    array            Array with data
	 */
	public static function GetTicket($id)
	{
		$function = "GetTicket";
		$res = 0;

		if ($id) {
			$ret = self::LoadTicket($id);
			return ErrorControl($ret, $function);
		} else {
			return ErrorControl($res, $function);
		}
	}

	/**
	 *
	 * Get Facture from DB
	 *
	 * @param    int $id Id Ticket to load
	 * @return    array            Array with data
	 */
	public static function GetFacture($id)
	{
		$function = "GetTicket";
		$res = 0;

		if ($id) {
			$ret = self::LoadFacture($id);
			return ErrorControl($ret, $function);
		} else {
			return ErrorControl($res, $function);
		}
	}

	/**
	 *
	 * Load Ticket from DB
	 *
	 * @param    int $id Id of ticket
	 * @return    array            Array with ticket data
	 */
	private static function LoadTicket($id)
	{
		global $db, $conf;
		$dataticket = array();

		$data = array();

		$object = new Ticket($db);
		$res = $object->fetch($id);

		if ($res) {
			require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php");

			$data['id'] = $object->id;
			$data['ref'] = $object->ref;
			$data['type'] = $object->type;
			$data['customerId'] = $object->socid;
			// hay que cargar nombre
			$soc = new Societe($db);
			$soc->fetch($object->socid);
			$data['customerName'] = $soc->name;
			$data['points'] = null;
			if($conf->global->REWARDS_POS && ! empty($conf->rewards->enabled)){
				dol_include_once('/rewards/class/rewards.class.php');
				$rew= new Rewards($db);
				$res = $rew->getCustomerReward($object->socid);
				if($res){
					$data['points'] = $rew->getCustomerPoints($object->socid);
				}
			}
			$data['coupon'] = $soc->getAvailableDiscounts();
			$data['state'] = $object->statut;
			$data['discount_percent'] = $object->remise_percent;
			$data['discount_qty'] = $object->remise_absolut;
			$data['payment_type'] = $object->mode_reglement_id;
			$data['customerpay'] = $object->customer_pay;
			$data['difpayment'] = $object->diff_payment;
			$data['total_ttc'] = $object->total_ttc;
			$data['id_place'] = $object->fk_place;
			$data['note'] = $object->note;
			$data['lines'] = self::LoadTicketLines($object->lines);

			$data['ret_points'] = $object->getSommePaiement();

			$sql = "SELECT sum(pf.amount) as amount FROM " . MAIN_DB_PREFIX . "pos_paiement_ticket as pf, " . MAIN_DB_PREFIX . "pos_ticket as f";
			$sql .= " WHERE f.entity = " . $conf->entity . " AND f.rowid = pf.fk_ticket AND f.fk_ticket_source = " . $id;
			$resql = $db->query($sql);
			$obj = $db->fetch_object($resql);

			$data['ret_points'] = price2num($data['ret_points'] + $obj->amount, 'MT');

			$dataticket['data'] = $data;
			return $dataticket;
		} else {
			return $res;
		}
	}

	/**
	 *
	 * Load Facture from DB
	 *
	 * @param    int $id Id of facture
	 * @return    array            Array with ticket data
	 */
	private static function LoadFacture($id)
	{
		global $db, $conf;
		$dataticket = array();

		$data = array();

		$object = new Facture($db);
		$res = $object->fetch($id);

		if ($res) {
			require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php");

			$data['id'] = $object->id;
			$data['ref'] = $object->ref;
			$data['type'] = $object->type;
			$data['customerId'] = $object->socid;
			//hay que cargar nombre
			$soc = new Societe($db);
			$soc->fetch($object->socid);
			$data['customerName'] = $soc->name;
			$data['state'] = $object->statut;
			$data['discount_percent'] = $object->remise_percent;
			$data['discount_qty'] = $object->remise_absolue;
			$data['payment_type'] = $object->mode_reglement_id;
			$data['total_ttc'] = $object->total_ttc;
			$data['lines'] = self::LoadFactureLines($object->lines);

			$listofpayments = $object->getListOfPayments();
			foreach ($listofpayments as $paym) {
				// This payment might be this one or a previous one
				if ($paym['type'] != 'PNT') {
					$data['ret_points'] += $paym['amount'];
				}
			}
			$sql = "SELECT sum(pf.amount) as amount FROM " . MAIN_DB_PREFIX . "paiement_facture as pf, " . MAIN_DB_PREFIX . "facture as f";
			$sql .= " WHERE f.entity = " . $conf->entity . " AND f.rowid = pf.fk_facture AND f.fk_facture_source = " . $id;
			$resql = $db->query($sql);
			$obj = $db->fetch_object($resql);

			$data['ret_points'] = price2num($data['ret_points'] + $obj->amount, 'MT');

			$dataticket['data'] = $data;
			return $dataticket;
		} else {
			return $res;
		}
	}

	/**
	 *
	 * Load lines of a ticket.
	 *
	 * @param    array $lines Lines into database
	 * @return    array                Lines for front end
	 */
	private static function LoadTicketLines($lines)
	{
		global $db;
		$aryLines = array();
		$prod = new Product($db);
		$i = 0;
		foreach ($lines as $line) {
			if (count($line) > 0) {
				$prod->fetch($line->fk_product);
				$aryLines[$i]['id'] = $line->rowid;
				$aryLines[$i]['label'] = $prod->label;
				$aryLines[$i]['price_ht'] = $line->subprice;
				$aryLines[$i]['cant'] = $line->qty;
				$aryLines[$i]['tva_tx'] = $line->tva_tx;
				$aryLines[$i]['localtax1_tx'] = $line->localtax1_tx;
				$aryLines[$i]['localtax2_tx'] = $line->localtax2_tx;
				$aryLines[$i]['idProduct'] = $line->fk_product;
				$aryLines[$i]['discount'] = $line->remise_percent;
				$aryLines[$i]['total_ht'] = $line->total_ht;
				$aryLines[$i]['total_ttc'] = $line->total_ttc;
				$aryLines[$i]['remise'] = $line->remise;
				$aryLines[$i]['fk_product_type'] = $line->fk_product_type;
				if ($line->note != 'null') {
					$aryLines[$i]['note'] = $line->note;
				} else {
					$aryLines[$i]['note'] = '';
				}


				$i++;
			}
		}
		return $aryLines;
	}

	/**
	 *
	 * Load lines of a facture.
	 *
	 * @param    array $lines Lines into database
	 * @return    array                Lines for front end
	 */
	private static function LoadFactureLines($lines)
	{
		global $db;
		$aryLines = array();
		$prod = new Product($db);
		$i = 0;
		foreach ($lines as $line) {
			if (count($line) > 0) {
				if (empty($line->fk_product)) {
					$aryLines[$i]['label'] = $line->desc;
				} else {
					$prod->fetch($line->fk_product);
					$aryLines[$i]['label'] = $prod->label;
				}

				$aryLines[$i]['id'] = $line->rowid;
				$aryLines[$i]['price_ht'] = $line->subprice;
				$aryLines[$i]['cant'] = $line->qty;
				$aryLines[$i]['tva_tx'] = $line->tva_tx;
				$aryLines[$i]['localtax1_tx'] = $line->localtax1_tx;
				$aryLines[$i]['localtax2_tx'] = $line->localtax2_tx;
				$aryLines[$i]['idProduct'] = $line->fk_product;
				$aryLines[$i]['discount'] = $line->remise_percent;
				$aryLines[$i]['total_ht'] = $line->total_ht;
				$aryLines[$i]['total_ttc'] = $line->total_ttc;

				$i++;
			}
		}
		return $aryLines;
	}

    /**
     *
     * Create ticket into Database
     *
     * @param    array $aryTicket Ticket object
     * @return int
     */
	private static function CreateTicket($aryTicket)
	{
		global $db, $user;

		$data = $aryTicket['data'];

		if ($data['idsource'] > 0) {
			$prods_returned = self::testSource($aryTicket);

			if (count($prods_returned) > 0) {
				return -6;
			}
			$vater = self::fetch($data['idsource']);

			$data['payment_type'] = $vater->mode_reglement_id;
		}

		$cash = new Cash($db);

		$terminal = $data["cashId"];
		$cash->fetch($terminal);

		if (!$data['customerId']) {
			$socid = $cash->fk_soc;
			$data['customerId'] = $socid;
		} else {
			$socid = $data['customerId'];
		}

		if (!$data['employeeId']) {
			$employee = $_SESSION['uid'];
		} else {
			$employee = $data['employeeId'];
		}

		$object = new Ticket($db);
		$object->type = $data['type'];
		$object->socid = $socid;
		$object->statut = $data['state'];
		$object->fk_cash = $terminal;
		$object->remise_percent = $data['discount_percent'];
		$object->remise_absolut = $data['discount_qty'];
		if ($data['customerpay1'] > 0) {
			$object->mode_reglement_id = $cash->fk_modepaycash;
		} else {
			if ($data['customerpay2'] > 0) {
				$object->mode_reglement_id = $cash->fk_modepaybank;
			} else {
				$object->mode_reglement_id = $cash->fk_modepaybank_extra;
			}
		}

		$object->fk_place = $data['id_place'];
		$object->note = $data['note'];

		$object->customer_pay = $data['customerpay'];

		$object->diff_payment = $data['difpayment'];
		$object->id_source = $data['idsource'];

		$db->begin;

		$idTicket = $object->create($employee, 1, 0);
		$data['ref'] = $object->ref;

		if ($idTicket < 0) {
			$db->rollback();
			return -1;
		} else {
			//Adding lines
			$data['id'] = $idTicket;
			if ($data['id_place']) {
				$place = new Place($db);
				$place->fetch($data['id_place']);
				$place->fk_ticket = $idTicket;
				$place->set_place($idTicket);
			}
			$idLines = self::addTicketLines($data, $idTicket, ($object->type == 1 ? true : false));

			if ($idLines < 0) {
				$db->rollback();
				return -2;
			} else {
				if ($object->fk_place) {
					$place = new Place($db);
					$place->fetch($object->fk_place);
				}

				if ($object->statut != 0) {
					//Adding Payments
					$payment = self::addPayment($data);
					if (!$payment) {
						$db->rollback();
						return -3;
					} else {
						if ($object->diff_payment == 0 || ($object->diff_payment < 0 && $object->type == 0) || ($object->diff_payment > 0 && $object->type == 1)) {
							$object->set_paid($employee);
						}
					}
					//Decrease stock

					$stock = self::quitSotck($data, ($object->type == 1 ? true : false));

					if ($stock) {
						$db->rollback();
						return -4;
					}

					// liberar puesto
					if ($place) {
						$place->free_place();
					}
				} else {
					// usar puesto
					if ($place) {
						$place->set_place($idTicket);
					}
				}
			}
		}


		$db->commit;

		return $idTicket;
	}

    /**
     *
     * Create facture into Database
     *
     * @param    array $aryTicket Ticket object
     * @return int
     */
	private static function CreateFacture($aryTicket)
	{
		global $db, $user, $conf, $langs;

		$data = $aryTicket['data'];
		$idTicket = $data["id"];

		if ($data['idsource'] > 0) {
			$prods_returned = self::testSourceFac($aryTicket);

			if (count($prods_returned) > 0) {
				return -6;
			}
			$vater = new Facture($db);
			$vater->fetch($data['idsource']);

			$data['payment_type'] = $vater->mode_reglement_id;
			if ($conf->numberseries->enabled && $conf->global->NUMBERSERIES_POS) {
                $data['serie'] = $vater->array_options['options_serie'];
            }
		}

		$cash = new Cash($db);

		$terminal = $data['cashId'];
		$cash->fetch($terminal);

		if (!$data['customerId']) {

			$socid = $cash->fk_soc;
			$data['customerId'] = $socid;

		} else {
			$socid = $data['customerId'];
		}

		if (!$data['employeeId']) {
			$employee = $_SESSION['uid'];

		} else {
			$employee = $data['employeeId'];
		}
		if ($data['mode'] == 1) {
			$object = new Facturesim($db);
		} else {
			$object = new Facture($db);
		}
		$object->type = ($data['type'] == 0 ? 0 : 2);
		$object->socid = $socid;
		$object->statut = $data['state'];
		$object->fk_cash = $terminal;

		$object->remise_absolue = $data['discount_qty'];

		if ($data['customerpay1'] > 0) {
			$object->mode_reglement_id = $cash->fk_modepaycash;
		} else {
			if ($data['customerpay2'] > 0) {
				$object->mode_reglement_id = $cash->fk_modepaybank;
			} else {
				$object->mode_reglement_id = $cash->fk_modepaybank_extra;
			}
		}

		$object->fk_place = $data['id_place'];
		$object->note_private = $data['note'];

		$object->customer_pay = $data['customerpay'];

		if ($object->customer_pay > 0) {
			$object->diff_payment = $data['difpayment'];
		} else {
			$object->diff_payment = $data['total'];
		}

		$object->fk_facture_source = $data['idsource'];

		$employ = new User($db);
		$employ->fetch($employee);
		$employ->getrights();
		$now = dol_now();
		$object->date = $now;

		if ($conf->numberseries->enabled && $conf->global->NUMBERSERIES_POS) {
            $object->array_options['options_serie'] = $data['serie'];
        }

		$soc = new Societe($db);
		$soc->fetch($socid);

		if (!$soc->idprof1 && !empty($conf->global->SOCIETE_IDPROF1_INVOICE_MANDATORY)) {
			return -7;
		}

		$db->begin();

		$idFacture = $object->create($employ);
		if ($object->statut == 1 || $object->type == 2) {
			$res = $object->validate($employ);
			if ($res < 0) {
				$num = $object->getNextNumRef($soc);
				// Validate
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'facture';
				$sql .= " SET facnumber='" . $num . "', fk_statut = 1, fk_user_valid = " . $employ->id . ", date_valid = '" . $db->idate($now) . "'";
				if (!empty($conf->global->FAC_FORCE_DATE_VALIDATION))    // If option enabled, we force invoice date
				{
					$sql .= ', datef="' . $db->idate($now).'"';
					$sql .= ', date_lim_reglement="' . $db->idate($now).'"';
				}
				$sql .= ' WHERE rowid = ' . $object->id;

				dol_syslog(__METHOD__ . "::validate sql=" . $sql);
				$resql = $db->query($sql);
				$object->ref = $num;
			}

		}

		$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'pos_facture (fk_cash, fk_place,fk_facture,customer_pay) VALUES (' . $object->fk_cash . ',' . ($object->fk_place ? $object->fk_place : 'null') . ',' . $idFacture . ',' . $object->customer_pay . ')';

		dol_syslog("pos_facture::update sql=" . $sql);
		$resql = $db->query($sql);
		if (!$resql) {
			$db->rollback();
			return -1;
		}
		$data['ref'] = $object->ref;

		if ($idFacture < 0) {
			$db->rollback();
			return -1;
		} else {
			//Adding lines
			$data['id'] = $idFacture;

			$idLines = self::addFactureLines($data, $idFacture, ($object->type == 1 ? true : false));

            //introducir descuentos debería ponerse como pago, no como línea
            if (!empty($data['idCoupon'])) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
                $discount = new DiscountAbsolute($db);
                $discount->fetch($data["idCoupon"]);
                if (preg_match('/\(CREDIT_NOTE\)/', $discount->description)) {
                    $res_dis = $discount->link_to_invoice(0, $object->id);
                }
                else {
                    $res_dis = $object->insert_discount($data['idCoupon']);
                }
            } else {
                $res_dis = 1;
            }

			if ($idLines < 0 || $res_dis < 0) {
				$db->rollback();
				return -2;
			} else {
				//Adding Payments
				$payment = self::addPaymentFac($data);
				if ($payment < 0) {
					$db->rollback();
					return -3;
				}

				//Decrease stock

				$stock = self::quitSotck($data, ($object->type == 2 ? true : false));

				if ($stock) {
					$db->rollback();
					return -4;
				}
			}
		}
		if ($object->statut == '1'){
		    $object->fetch($idFacture);
            $paiement = $object->getSommePaiement();
            $creditnotes=$object->getSumCreditNotesUsed();
            $deposits=$object->getSumDepositsUsed();
            $remaintopay=price2num($object->total_ttc - $paiement - $creditnotes - $deposits,'MT');
            if ($remaintopay == '0'){
                $object->set_paid($employ);
            }
        }

		if ($idTicket) {
			$ticket = new Ticket($db);
			$ticket->fetch($idTicket);
			$ticket->delete_ticket();
		}
		$db->commit();
		return $idFacture;
	}


	/**
	 *
	 * Update Ticket into Database
	 * @param    array $aryTicket Ticket object
	 */
	private static function UpdateTicket($aryTicket)
	{
		global $db, $user;

		$data = $aryTicket['data'];
		$lines = $data['lines'];

		$idTicket = $data['id'];

		if (!$data['customerId']) {
			$cash = new Cash($db);

			$terminal = $_SESSION['TERMINAL_ID'];
			$cash->fetch($terminal);
			$socid = $cash->fk_soc;

		} else {
			$socid = $data['customerId'];
		}

		if (!$data['employeeId']) {
			$employee = $_SESSION['uid'];

		} else {
			$employee = $data['employeeId'];
		}

		$object = new Ticket($db);
		$object->fetch($idTicket);

		$object->type = $data['type'];
		$object->socid = $socid;
		$object->statut = $data['state'];
		$object->fk_cash = $_SESSION['TERMINAL_ID'];
		$object->remise_percent = $data['discount_percent'];
		$object->remise_absolut = $data['discount_qty'];
		$object->mode_reglement_id = $data['payment_type'];
		$object->fk_place = $data['id_place'];
		$object->note = $data['note'];

		$cash = new Cash($db);
		$cash->fetch($_SESSION['TERMINAL_ID']);

		if ($data['payment_type'] != $cash->fk_modepaycash) {
			if ($data['points'] > 0) {
				$object->customer_pay = $data['total_with_points'];
			} else {
				$object->customer_pay = $data['total'];
			}
		} else {
			$object->customer_pay = $data['customerpay'];
		}
		$data['customerpay'] = $object->customer_pay;
		$object->diff_payment = $data['difpayment'];
		$object->id_source = $data['idsource'];

		$userstatic = new User($db);
		$userstatic->fetch($employee);

		$db->begin;

		$res = $object->update($userstatic->id);
		$data['ref'] = $object->ref;
		if ($res < 0) {
			$db->rollback();
			return -5;
		} else {
			//Adding lines
            $idLines = self::addTicketLines($data, $idTicket, ($object->type == 1 ? true : false));
            if ($idLines < 0) {
				$db->rollback();
				return -2;
			} else {
				$place = new Place($db);
				$place->fetch($object->fk_place);

				if ($object->statut != 0) {
					//Adding Payments
					$payment = self::addPayment($data);
					if (!$payment) {
						$db->rollback();
						return -3;
					} else {
                        if ($object->diff_payment == 0 || ($object->diff_payment < 0 && $object->type == 0) || ($object->diff_payment > 0 && $object->type == 1)) {
							$object->set_paid($user);
						}
					}
					//Decrease stock
					$stock = self::quitSotck($lines);
					if ($stock) {
						$db->rollback();
						return -4;
					}

					// liberar puesto
					$place->free_place();

				} else {
					// usar puesto
					$place->set_place($idTicket);

				}
			}
		}

		$db->commit;
		return $idTicket;

	}

	/**
	 *    Delete ticket
	 * @param        int $idTicket Id of ticket to delete
	 * @return        int                    <0 if KO, >0 if OK
	 */
	/*public static function DeleteTicket($idTicket=0)
	{
		global $db;

		$object= new Ticket($db);
		$db->begin;
		$res=$object->delete($idTicket);

		if ($res==1)
		{
			$reslines=deleteTicketLines($idTicket);
			if($reslines==1)
			{
				$db->commit();
			}
			else
			{
				$db->rollback();
				$res=-1;
			}
		}
		else
		{
			$db->rollback;
		}

		return $res;
	}*/

	/**
	 *        Add ticket line into database (linked to product/service or not)
	 * @param        array $lines Ticket Lines
	 * @return        array                        Result of adding
	 */
	private static function addTicketLines($data, $idTicket, $isreturn = false)
	{
		global $db;

		$res = 0;

		self::deleteTicketLines($idTicket);

		$object = new Ticket($db);
		$object->fetch($idTicket);

		if (count($data['lines']) > 0) {
			foreach ($data['lines'] as $line) {
				if (count($line) > 0) {
					if ($line['idProduct'] > 0) {
						$product_static = new Product($db);
						$product_static->id = $line['idProduct'];
						$product_static->load_stock();

						if ($product_static->stock_reel < 1 || $product_static->stock_reel < $line['cant']) {
							$res = -4;
						}


						if (!$isreturn) {
							$qty = $line['cant'];
						} else {
							$qty = $line['cant'] * -1;
						}
						$line['discount'] = $line['discount'] + $object->remise_percent;
						$line['description'] = $line['description'] . " " . $line['note'];
						$res = $object->addline(/*$idTicket,*/
								$line['description'], $line['price_ht'], $qty, $line['tva_tx'], $line['localtax1_tx'],
								$line['localtax2_tx'], $line['idProduct'], $line['discount'], $line['note'],
								$line['fk_product_type'], $line['price_ttc'], $line['price_base_type']);

					}
				} else {
					$res = -1;
				}

			}
		}
		return $res;

	}

	/**
	 *        Add ticket line into database (linked to product/service or not)
	 * @param        array $lines Ticket Lines
	 * @return        array                        Result of adding
	 */
	private static function addFactureLines($data, $idTicket, $isreturn = false)
	{
		global $db, $conf, $user, $langs;

		$res = 0;

		$object = new Facture($db);
		$object->fetch($idTicket);
		$object->brouillon = 1;
		$object->fetch_thirdparty();

		if (count($data['lines']) > 0) {
			foreach ($data['lines'] as $line) {
				if (count($line) > 0) {
					if ($line['idProduct'] > 0) {
						$product_static = new Product($db);
						$product_static->id = $line['idProduct'];
						$product_static->load_stock();

						if ($product_static->stock_reel < 1 || $product_static->stock_reel < $line['cant']) {
							$res = -4;
						}


						if (!$isreturn) {
							$qty = $line['cant'];
						} else {
							$qty = $line['cant'] * -1;
						}
						$object->brouillon = 1;
						$line['discount'] = $line['discount'] + $data['discount_percent'];
						//$line['description'] = $line['description']?$line['description']:$line['label'];


						if (count($line['batchs'])>0) {
							foreach ($line['batchs'] as $batch) {
								$line['description'] = $line['description']. "<br>". $langs->trans('Batch').": ". $batch['batch'];
							}
						}

						$line['description'] = $line['description'] . " " . $line['note'];

						// TODO buscar el pmp del producto para este almacén, si es cero, pmp en general.
                        if (version_compare(DOL_VERSION, 3.9) >= 0) {
                            $pmp = $product_static->defineBuyPrice(0,0,$line['idProduct']);
                        }
                        else {
                            $sql = "SELECT	p.pmp as totpmp FROM " . MAIN_DB_PREFIX . "product as p WHERE p.rowid = " . $line["idProduct"];
                            $resql = $db->query($sql);

                            if ($resql) {
                                $objp = $db->fetch_object($resql);

                                $pmp = $objp->totpmp;

                                if ($pmp <= 0 && $conf->global->ForceBuyingPriceIfNull) {
                                    $pmp = $line['price_ht'];
                                }
                            }
                        }

                        if ($object->type != Facture::TYPE_CREDIT_NOTE) {
                            $res = $object->addline(/*$idTicket,*/
                                $line['description'], $line['price_ht'], $qty, $line['tva_tx'], $line['localtax1_tx'],
                                $line['localtax2_tx'], $line['idProduct'], $line['discount'], '', '', 0, 0, '',
                                ($line['price_base_type'] ? $line['price_base_type'] : 'HT'), $line['price_ttc'],
                                $line['fk_product_type'], -1, 0, '', 0, 0,
                                null, $pmp);
                        }
                        else{
                            $old_var = $conf->global->STOCK_MUST_BE_ENOUGH_FOR_INVOICE;
                            $conf->global->STOCK_MUST_BE_ENOUGH_FOR_INVOICE = 0;

                                $res = $object->addline(/*$idTicket,*/
                                    $line['description'], $line['price_ht'], $qty, $line['tva_tx'], $line['localtax1_tx'],
                                    $line['localtax2_tx'], $line['idProduct'], $line['discount'], '', '', 0, 0, '',
                                    ($line['price_base_type'] ? $line['price_base_type'] : 'HT'), $line['price_ttc'],
                                    $line['fk_product_type'], -1, 0, '', 0, 0,
                                    null, $pmp);

                            $conf->global->STOCK_MUST_BE_ENOUGH_FOR_INVOICE = $old_var;
                        }

						if ($conf->discounts->enabled && $line['is_promo'] == 1 && $res) {
							dol_include_once('/discounts/class/discount_doc.class.php');
							$dis_doc = new Discounts_doc($db);
							$dis_doc->type_doc = 3;//Factura
							$dis_doc->fk_doc = $res;
							$dis_doc->ori_subprice = $line['orig_price'];
							$dis_doc->ori_totalht = $line['orig_price'] * $line['cant'];
							$dis_doc->descr = $line['promo_desc'];
							$dis_doc->create($user);
						}

					}
				} else {
					$res = -1;
				}

			}
		}
		return $res;

	}

	/**
	 *    Update a detail line
	 * @param        array $line Line Ticket
	 * @return        array           Result of update
	 */
	public static function updateTicketLine($line)
	{
		global $db;
		$object = new Ticket($db);
		if (count($line) > 0) {
			$res = $object->updateline($line->idTicketLine, $line->desc, $line->pu, $line->qty, $line->remise_percent,
					'', '', $line->txtva, $line->txlocaltax1, $line->txlocaltax2, $line->price_base_type);
		} else {
			$res = -1;
		}
		return $res;
	}

	/**
	 *    Delete line in database
	 * @param        int $idTicket Id Ticket to delete lines
	 * @return        int                        <0 if KO, >0 if OK
	 */
	public static function deleteTicketLines($idTicket)
	{
		global $db;

		$sql = "SELECT rowid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "pos_ticketdet";
		$sql .= " WHERE  fk_ticket= " . $idTicket;

		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			$object = new Ticket($db);

			while ($i < $num) {
				$objp = $db->fetch_object($resql);
				$res = $object->deleteline($objp->rowid);
				if ($res != 1) {
					return -1;
				}

				$i++;
			}

		}
		return 1;
	}

	/**
	 *
	 * Returns terminals of POS
	 */
	public static function select_Terminals()
	{
		global $db, $conf;

		$sql = "SELECT rowid, code, name, fk_device, is_used, fk_user_u, tactil";
		$sql .= " FROM " . MAIN_DB_PREFIX . "pos_cash";
		$sql .= " WHERE entity = " . $conf->entity;
		$sql .= " AND is_used = 0 OR (is_used=1 AND is_closed=1)";

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
			return $terms;
		} else {
			return -1;
		}
	}

	/**
	 *
	 * Returns terminals of POS
	 */
	public static function checkUserTerminal($userid, $terminalid)
	{
		global $db, $conf;

		if ($conf->global->POS_USER_TERMINAL) {

			$sql = "SELECT rowid";
			$sql .= " FROM " . MAIN_DB_PREFIX . "pos_users";
			$sql .= " WHERE fk_terminal = " . $terminalid;
			$sql .= " AND fk_object = " . $userid;
			$sql .= " AND objtype = 'user'";

			$sql .= "UNION SELECT g.rowid";
			$sql .= " FROM " . MAIN_DB_PREFIX . "pos_users as pu";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "usergroup_user as g ON pu.fk_object = g.fk_usergroup	";
			$sql .= " WHERE pu.fk_terminal = " . $terminalid;
			$sql .= " AND g.fk_user = " . $userid;
			$sql .= " AND pu.objtype = 'group'";

			$res = $db->query($sql);

			if ($res) {
				$num = $db->num_rows($res);

				if ($num > 0) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
		return true;
	}

	/**
	 *
	 * Return Ticket history
	 *
	 * @param    string $ticketnumber ticket number for filter
	 * @param    int    $stat         status of ticket
	 * @param  int      $mode         0, count rows; 1, get rows
	 * @param    string $terminal     terminal for filter
	 * @param    string $seller       seller user for filter
	 * @param    string $client       client for filter
	 * @param    float  $amount       amount for filter
	 * @param    int    $month        month for filter
	 * @param    int    $year         year for filter
	 */
	public static function getHistoric(
			$ticketnumber = '',
			$stat,
			$terminal = '',
			$seller = '',
			$client = '',
			$amount = '',
			$months = 0,
			$years = 0
	) {
		global $db, $conf, $user, $langs;

		$ret = -1;
		$function = "GetHistoric";

		$sql = ' SELECT ';

		$sql .= ' f.rowid as ticketid, f.ticketnumber, f.total_ttc,';
		$sql .= ' f.date_closed, f.fk_user_close, f.date_creation as datec,';
		$sql .= ' f.fk_statut, f.customer_pay, f.difpayment, f.fk_place, ';
		$sql .= ' s.nom, s.rowid as socid,';
		$sql .= ' u.firstname, u.lastname,';
		$sql .= ' t.name, f.fk_cash, f.type';

		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'societe as s';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON s.rowid = sc.fk_soc AND sc.fk_user = " . $user->id;
		} // We need this table joined to the select in order to filter by sale
		$sql .= ', ' . MAIN_DB_PREFIX . 'pos_ticket as f';
		$sql .= ', ' . MAIN_DB_PREFIX . 'pos_cash as t';
		$sql .= ', ' . MAIN_DB_PREFIX . 'user as u';
		$sql .= ' WHERE f.fk_soc = s.rowid';
		$sql .= " AND f.entity = " . $conf->entity;
		$sql .= " AND f.fk_cash = t.rowid";
		$sql .= " AND u.rowid = f.fk_user_author";

		if ($conf->global->POS_USER_TERMINAL) {
			$sql .= " AND f.fk_cash IN (";
			$sql .= "SELECT pu.fk_terminal FROM " . MAIN_DB_PREFIX . "pos_users as pu WHERE pu.fk_object = " . $_SESSION["uid"] . " AND pu.objtype = 'user'";
			$sql .= " UNION SELECT pu.fk_terminal FROM " . MAIN_DB_PREFIX . "pos_users as pu LEFT JOIN " . MAIN_DB_PREFIX . "usergroup_user as ug ON pu.fk_object = ug.fk_usergroup";
			$sql .= " WHERE ug.fk_user = " . $_SESSION["uid"] . " AND pu.objtype = 'group')";
		}

		if ($stat >= 0 && $stat != 4 && $stat <= 99) {
			$sql .= " AND f.fk_statut = " . $stat;
			$sql .= " AND f.type = 0";
		}
		if ($stat == 4) {
			$sql .= " AND f.type = 1";
		}

		//if ($socid) $sql.= ' AND s.rowid = '.$socid;

		if ($ticketnumber) {
			$sql .= ' AND f.ticketnumber LIKE \'%' . $db->escape(trim($ticketnumber)) . '%\'';
		}
		if ($months > 0) {
			if ($years > 0) {
				$sql .= " AND f.date_ticket BETWEEN '" . $db->idate(dol_get_first_day($years, $months,
								false)) . "' AND '" . $db->idate(dol_get_last_day($years, $months, false)) . "'";
			} else {
				$sql .= " AND date_format(f.date_ticket, '%m') = '" . $months . "'";
			}
		} else {
			if ($years > 0) {
				$sql .= " AND f.date_ticket BETWEEN '" . $db->idate(dol_get_first_day($years, 1,
								false)) . "' AND '" . $db->idate(dol_get_last_day($years, 12, false)) . "'";
			}
		}
		$now = dol_now();
		$time = dol_getdate($now);
		$day = $time['mday'];
		$month = $time['mon'];
		$year = $time['year'];

		if ($stat == 100) {//Today
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
			$sql .= " AND f.date_ticket BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 101) {//Yesterday
			$time = dol_get_prev_day($day, $month, $year);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $time['day'], 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $time['day'], 23, 59, 59);
			$sql .= " AND f.date_ticket BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 102) {//This week
			$time = dol_get_first_day_week($day, $month, $year);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $time['first_day'], 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
			$sql .= " AND f.date_ticket BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 103) {//Last week
			$time = dol_get_first_day_week($day, $month, $year);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time['prev_year'], $time['prev_month'], $time['prev_day'], 0, 0,
					0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $year,
					$time['first_day'] - 1 == 0 ? $time['prev_month'] : $month,
					$time['first_day'] - 1 == 0 ? $time['prev_day'] + 6 : $time['first_day'] - 1, 23, 59, 59);
			$sql .= " AND f.date_ticket BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 104) {//Two weeks ago
			$time = dol_get_prev_week($day, '', $month, $year);
			$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time2['year'], $time2['month'], $time2['day'], 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'],
					$time['day'] - 1 == 0 ? $time2['month'] : $time['month'],
					$time['day'] - 1 == 0 ? $time2['day'] + 6 : $time['day'] - 1, 23, 59, 59);
			$sql .= " AND f.date_ticket BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 105) {//Three weeks ago
			$time = dol_get_prev_week($day, '', $month, $year);
			$time = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
			$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time2['year'], $time2['month'], $time2['day'], 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'],
					$time['day'] - 1 == 0 ? $time2['month'] : $time['month'],
					$time['day'] - 1 == 0 ? $time2['day'] + 6 : $time['day'] - 1, 23, 59, 59);
			$sql .= " AND f.date_ticket BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 106) {//This month
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, 01, 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
			$sql .= " AND f.date_ticket BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 107) {//One month ago
			$time = dol_get_prev_month($month, $year);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $day, 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
			$sql .= " AND f.date_ticket BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 108) {//Last month
			$time = dol_get_prev_month($month, $year);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], 01, 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], 31, 0, 0, 0);
			$sql .= " AND f.date_ticket BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($terminal) {
			$sql .= ' AND t.name LIKE \'%' . $db->escape(trim($terminal)) . '%\'';
		}
		if ($seller) {
			$sql .= ' AND (u.firstname LIKE \'%' . $db->escape(trim($seller)) . '%\'';
			$sql .= ' OR u.lastname LIKE \'%' . $db->escape(trim($seller)) . '%\')';
		}
		if ($client) {
			$prefix = empty($conf->global->COMPANY_DONOTSEARCH_ANYWHERE) ? '%' : '';    // Can use index if COMPANY_DONOTSEARCH_ANYWHERE is on
			$sql .= ' AND s.nom LIKE \'' . $prefix . $db->escape(trim($client)) . '%\'';
		}

		if ($amount) {
			$sql .= ' AND f.total_ttc = \'' . $db->escape(trim($amount)) . '\'';
		}

		$sql .= ' GROUP BY f.rowid, u.firstname, u.lastname';

		$sql .= ' ORDER BY ';
		$sql .= ' datec DESC ';
		$sql .= 'LIMIT 0,50';

		$res = $db->query($sql);

		if ($res) {
			$num = $db->num_rows($res);
			$i = 0;
			$ticketstatic = new Ticket($db);
			$tickets = array();
			while ($i < $num) {
				$obj = $db->fetch_object($res);

				$tickets[$i]["id"] = $obj->ticketid;
				$tickets[$i]["type"] = $obj->type;
				$tickets[$i]["ticketnumber"] = $obj->ticketnumber;
				$tickets[$i]["date_creation"] = dol_print_date($db->jdate($obj->datec), 'dayhour');
				$tickets[$i]["date_close"] = dol_print_date($db->jdate($obj->date_closed), 'dayhour');
				$tickets[$i]["fk_place"] = $obj->fk_place;

				$cash = new Cash($db);
				$cash->fetch($obj->fk_cash);
				$tickets[$i]["terminal"] = $cash->name;

				$userstatic = new User($db);
				$userstatic->fetch($obj->fk_user_close);
				$tickets[$i]["seller"] = $userstatic->getFullName($langs);

				$tickets[$i]["client"] = $obj->nom;
				$tickets[$i]["amount"] = $obj->total_ttc;
				$tickets[$i]["customer_pay"] = $obj->customer_pay;
				$tickets[$i]["statut"] = $obj->fk_statut;
				$tickets[$i]["statutlabel"] = $ticketstatic->LibStatut($obj->fk_statut, 0);

				$i++;
			}
			return ErrorControl($tickets, $function);


		} else {
			return ErrorControl($ret, $function);
		}

	}

	/**
	 *
	 * Return Facture history
	 *
	 * @param    string $ticketnumber ticket number for filter
	 * @param    int    $stat         status of ticket
	 * @param  int      $mode         0, count rows; 1, get rows
	 * @param    string $terminal     terminal for filter
	 * @param    string $seller       seller user for filter
	 * @param    string $client       client for filter
	 * @param    float  $amount       amount for filter
	 * @param    int    $month        month for filter
	 * @param    int    $year         year for filter
	 */
	public static function getHistoricFac(
			$ticketnumber = '',
			$stat,
			$terminal = '',
			$seller = '',
			$client = '',
			$amount = '',
			$months = 0,
			$years = 0
	) {
		global $db, $conf, $user, $langs;

		$ret = -1;
		$function = "GetHistoric";

		$sql = ' SELECT ';

		$sql .= ' f.rowid as ticketid, f.facnumber, f.total_ttc,';
		$sql .= ' f.fk_user_valid, f.datec as datec,';
		$sql .= ' f.fk_statut, pf.fk_place, ';
		$sql .= ' s.nom, s.rowid as socid,';
		$sql .= ' u.firstname, u.lastname,';
		$sql .= ' t.name, pf.fk_cash, f.type';

		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'societe as s';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON s.rowid = sc.fk_soc AND sc.fk_user = " . $user->id;
		} // We need this table joined to the select in order to filter by sale
		$sql .= ', ' . MAIN_DB_PREFIX . 'facture as f';
		$sql .= ', ' . MAIN_DB_PREFIX . 'pos_facture as pf';
		$sql .= ', ' . MAIN_DB_PREFIX . 'pos_cash as t';
		$sql .= ', ' . MAIN_DB_PREFIX . 'user as u';
		$sql .= ' WHERE f.fk_soc = s.rowid';
		$sql .= " AND f.entity = " . $conf->entity;
		$sql .= " AND pf.fk_cash = t.rowid";
		$sql .= " AND pf.fk_facture = f.rowid";
		$sql .= " AND u.rowid = f.fk_user_valid";

		if ($conf->global->POS_USER_TERMINAL) {
			$sql .= " AND pf.fk_cash IN (";
			$sql .= "SELECT pu.fk_terminal FROM " . MAIN_DB_PREFIX . "pos_users as pu WHERE pu.fk_object = " . $_SESSION["uid"] . " AND pu.objtype = 'user'";
			$sql .= " UNION SELECT pu.fk_terminal FROM " . MAIN_DB_PREFIX . "pos_users as pu LEFT JOIN " . MAIN_DB_PREFIX . "usergroup_user as ug ON pu.fk_object = ug.fk_usergroup";
			$sql .= " WHERE ug.fk_user = " . $_SESSION["uid"] . " AND pu.objtype = 'group')";
		}

		if ($stat >= 0 && $stat != 4 && $stat <= 99) {
			$sql .= " AND f.fk_statut = " . $stat;
			$sql .= " AND f.type = 0";
		}
		if ($stat == 4) {
			$sql .= " AND f.type = 2";
		}

		//if ($socid) $sql.= ' AND s.rowid = '.$socid;

		if ($ticketnumber) {
			$sql .= ' AND f.facnumber LIKE \'%' . $db->escape(trim($ticketnumber)) . '%\'';
		}
		if ($months > 0) {
			if ($years > 0) {
				$sql .= " AND f.datec BETWEEN '" . $db->idate(dol_get_first_day($years, $months,
								false)) . "' AND '" . $db->idate(dol_get_last_day($years, $months, false)) . "'";
			} else {
				$sql .= " AND date_format(f.datec, '%m') = '" . $months . "'";
			}
		} else {
			if ($years > 0) {
				$sql .= " AND f.datec BETWEEN '" . $db->idate(dol_get_first_day($years, 1,
								false)) . "' AND '" . $db->idate(dol_get_last_day($years, 12, false)) . "'";
			}
		}
		$now = dol_now();
		$time = dol_getdate($now);
		$day = $time['mday'];
		$month = $time['mon'];
		$year = $time['year'];

		if ($stat == 100) {//Today
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
			$sql .= " AND f.datec BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 101) {//Yesterday
			$time = dol_get_prev_day($day, $month, $year);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $time['day'], 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $time['day'], 23, 59, 59);
			$sql .= " AND f.datec BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 102) {//This week
			$time = dol_get_first_day_week($day, $month, $year);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $time['first_day'], 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
			$sql .= " AND f.datec BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 103) {//Last week
			$time = dol_get_first_day_week($day, $month, $year);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time['prev_year'], $time['prev_month'], $time['prev_day'], 0, 0,
					0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $year,
					$time['first_day'] - 1 == 0 ? $time['prev_month'] : $month,
					$time['first_day'] - 1 == 0 ? $time['prev_day'] + 6 : $time['first_day'] - 1, 23, 59, 59);
			$sql .= " AND f.datec BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 104) {//Two weeks ago
			$time = dol_get_prev_week($day, '', $month, $year);
			$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time2['year'], $time2['month'], $time2['day'], 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'],
					$time['day'] - 1 == 0 ? $time2['month'] : $time['month'],
					$time['day'] - 1 == 0 ? $time2['day'] + 6 : $time['day'] - 1, 23, 59, 59);
			$sql .= " AND f.datec BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 105) {//Three weeks ago
			$time = dol_get_prev_week($day, '', $month, $year);
			$time = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
			$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time2['year'], $time2['month'], $time2['day'], 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'],
					$time['day'] - 1 == 0 ? $time2['month'] : $time['month'],
					$time['day'] - 1 == 0 ? $time2['day'] + 6 : $time['day'] - 1, 23, 59, 59);
			$sql .= " AND f.datec BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 106) {//This month
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, 01, 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
			$sql .= " AND f.datec BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 107) {//One month ago
			$time = dol_get_prev_month($month, $year);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $day, 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
			$sql .= " AND f.datec BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 108) {//Last month
			$time = dol_get_prev_month($month, $year);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], 01, 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], 31, 0, 0, 0);
			$sql .= " AND f.datec BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($terminal) {
			$sql .= ' AND t.name LIKE \'%' . $db->escape(trim($terminal)) . '%\'';
		}
		if ($seller) {
			$sql .= ' AND (u.firstname LIKE \'%' . $db->escape(trim($seller)) . '%\'';
			$sql .= ' OR u.lastname LIKE \'%' . $db->escape(trim($seller)) . '%\')';
		}
		if ($client) {
			$prefix = empty($conf->global->COMPANY_DONOTSEARCH_ANYWHERE) ? '%' : '';    // Can use index if COMPANY_DONOTSEARCH_ANYWHERE is on
			$sql .= ' AND s.nom LIKE \'' . $prefix . $db->escape(trim($client)) . '%\'';
		}

		if ($amount) {
			$sql .= ' AND f.total_ttc = \'' . $db->escape(trim($amount)) . '\'';
		}

		$sql .= ' GROUP BY f.rowid';

		$sql .= ' UNION SELECT ';

		$sql .= ' p.rowid as ticketid, p.ticketnumber, p.total_ttc,';
		$sql .= ' p.fk_user_close, p.date_creation as datec,';
		$sql .= ' p.fk_statut, p.fk_place, ';
		$sql .= ' s.nom, s.rowid as socid,';
		$sql .= ' u.firstname, u.lastname,';
		$sql .= ' t.name, p.fk_cash, p.type';

		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'societe as s';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON s.rowid = sc.fk_soc AND sc.fk_user = " . $user->id;
		} // We need this table joined to the select in order to filter by sale
		$sql .= ', ' . MAIN_DB_PREFIX . 'pos_ticket as p';
		$sql .= ', ' . MAIN_DB_PREFIX . 'pos_cash as t';
		$sql .= ', ' . MAIN_DB_PREFIX . 'user as u';
		$sql .= ' WHERE p.fk_soc = s.rowid';
		$sql .= " AND p.entity = " . $conf->entity;
		$sql .= " AND p.fk_cash = t.rowid";
		$sql .= " AND p.fk_statut = 0";
		$sql .= " AND u.rowid = p.fk_user_author";

		if ($conf->global->POS_USER_TERMINAL) {
			$sql .= " AND p.fk_cash IN (";
			$sql .= "SELECT pu.fk_terminal FROM " . MAIN_DB_PREFIX . "pos_users as pu WHERE pu.fk_object = " . $_SESSION["uid"] . " AND pu.objtype = 'user'";
			$sql .= " UNION SELECT pu.fk_terminal FROM " . MAIN_DB_PREFIX . "pos_users as pu LEFT JOIN " . MAIN_DB_PREFIX . "usergroup_user as ug ON pu.fk_object = ug.fk_usergroup";
			$sql .= " WHERE ug.fk_user = " . $_SESSION["uid"] . " AND pu.objtype = 'group')";
		}

		if ($stat >= 0 && $stat != 4 && $stat <= 99) {
			$sql .= " AND p.fk_statut = " . $stat;
		}
		if ($stat == 4) {
			$sql .= " AND p.type = 1";
		}

		//if ($socid) $sql.= ' AND s.rowid = '.$socid;

		if ($ticketnumber) {
			$sql .= ' AND p.ticketnumber LIKE \'%' . $db->escape(trim($ticketnumber)) . '%\'';
		}
		if ($months > 0) {
			if ($years > 0) {
				$sql .= " AND p.date_ticket BETWEEN '" . $db->idate(dol_get_first_day($years, $months,
								false)) . "' AND '" . $db->idate(dol_get_last_day($years, $months, false)) . "'";
			} else {
				$sql .= " AND date_format(p.date_ticket, '%m') = '" . $months . "'";
			}
		} else {
			if ($years > 0) {
				$sql .= " AND p.date_ticket BETWEEN '" . $db->idate(dol_get_first_day($years, 1,
								false)) . "' AND '" . $db->idate(dol_get_last_day($years, 12, false)) . "'";
			}
		}
		$now = dol_now();
		$time = dol_getdate($now);
		$day = $time['mday'];
		$month = $time['mon'];
		$year = $time['year'];

		if ($stat == 100) {//Today
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
			$sql .= " AND p.date_ticket BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 101) {//Yesterday
			$time = dol_get_prev_day($day, $month, $year);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $time['day'], 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $time['day'], 23, 59, 59);
			$sql .= " AND p.date_ticket BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 102) {//This week
			$time = dol_get_first_day_week($day, $month, $year);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $time['first_day'], 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
			$sql .= " AND p.date_ticket BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 103) {//Last week
			$time = dol_get_first_day_week($day, $month, $year);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time['prev_year'], $time['prev_month'], $time['prev_day'], 0, 0,
					0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $year,
					$time['first_day'] - 1 == 0 ? $time['prev_month'] : $month,
					$time['first_day'] - 1 == 0 ? $time['prev_day'] + 6 : $time['first_day'] - 1, 23, 59, 59);
			$sql .= " AND p.date_ticket BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 104) {//Two weeks ago
			$time = dol_get_prev_week($day, '', $month, $year);
			$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time2['year'], $time2['month'], $time2['day'], 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'],
					$time['day'] - 1 == 0 ? $time2['month'] : $time['month'],
					$time['day'] - 1 == 0 ? $time2['day'] + 6 : $time['day'] - 1, 23, 59, 59);
			$sql .= " AND p.date_ticket BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 105) {//Three weeks ago
			$time = dol_get_prev_week($day, '', $month, $year);
			$time = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
			$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time2['year'], $time2['month'], $time2['day'], 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'],
					$time['day'] - 1 == 0 ? $time2['month'] : $time['month'],
					$time['day'] - 1 == 0 ? $time2['day'] + 6 : $time['day'] - 1, 23, 59, 59);
			$sql .= " AND p.date_ticket BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 106) {//This month
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, 01, 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
			$sql .= " AND p.date_ticket BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 107) {//One month ago
			$time = dol_get_prev_month($month, $year);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $day, 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
			$sql .= " AND p.date_ticket BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($stat == 108) {//Last month
			$time = dol_get_prev_month($month, $year);
			$ini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], 01, 0, 0, 0);
			$fin = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], 31, 0, 0, 0);
			$sql .= " AND p.date_ticket BETWEEN '" . $ini . "' AND '" . $fin . "'";
		}
		if ($terminal) {
			$sql .= ' AND t.name LIKE \'%' . $db->escape(trim($terminal)) . '%\'';
		}
		if ($seller) {
			$sql .= ' AND (u.firstname LIKE \'%' . $db->escape(trim($seller)) . '%\'';
			$sql .= ' OR u.lastname LIKE \'%' . $db->escape(trim($seller)) . '%\')';
		}
		if ($client) {
			$prefix = empty($conf->global->COMPANY_DONOTSEARCH_ANYWHERE) ? '%' : '';    // Can use index if COMPANY_DONOTSEARCH_ANYWHERE is on
			$sql .= ' AND s.nom LIKE \'' . $prefix . $db->escape(trim($client)) . '%\'';
		}

		if ($amount) {
			$sql .= ' AND p.total_ttc = \'' . $db->escape(trim($amount)) . '\'';
		}

		$sql .= ' GROUP BY p.rowid';

		$sql .= ' ORDER BY ';
		$sql .= ' datec DESC ';
		$sql .= 'LIMIT 0,50';

		$res = $db->query($sql);

		if ($res) {
			$num = $db->num_rows($res);
			$i = 0;
			$ticketstatic = new Ticket($db);
			while ($i < $num) {
				$obj = $db->fetch_object($res);

				$tickets[$i]["id"] = $obj->ticketid;
				$tickets[$i]["type"] = ($obj->type == 2 ? 1 : $obj->type);
				$tickets[$i]["ticketnumber"] = $obj->facnumber;
				$tickets[$i]["date_creation"] = dol_print_date($db->jdate($obj->datec), 'dayhour');
				$tickets[$i]["date_close"] = dol_print_date($db->jdate($obj->date_closed), 'dayhour');
				$tickets[$i]["fk_place"] = $obj->fk_place;

				$cash = new Cash($db);
				$cash->fetch($obj->fk_cash);
				$tickets[$i]["terminal"] = $cash->name;

				$userstatic = new User($db);
				$userstatic->fetch($obj->fk_user_valid);
				$tickets[$i]["seller"] = $userstatic->getFullName($langs);

				$tickets[$i]["client"] = $obj->nom;
				$tickets[$i]["amount"] = $obj->total_ttc;
				$tickets[$i]["customer_pay"] = $obj->customer_pay;
				$tickets[$i]["statut"] = $obj->fk_statut;
				$tickets[$i]["statutlabel"] = $ticketstatic->LibStatut($obj->fk_statut, 0);

				$i++;
			}
			return ErrorControl($tickets, $function);


		} else {
			return ErrorControl($ret, $function);
		}

	}

	/**
	 *
	 * Count Ticket history
	 *
	 */
	public static function countHistoric()
	{
		global $db, $conf, $user;

		$ret = -1;
		$function = "GetHistoric";

		$sql = 'SELECT (SELECT COUNT(f.rowid)';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'pos_ticket as f';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON f.fk_soc = sc.fk_soc AND sc.fk_user = " . $_SESSION["uid"];
		} // We need this table joined to the select in order to filter by sale
		$sql .= ' WHERE f.entity = ' . $conf->entity;

		if ($conf->global->POS_USER_TERMINAL) {
			$sql2 = " AND f.fk_cash IN (";
			$sql2 .= "SELECT pu.fk_terminal FROM " . MAIN_DB_PREFIX . "pos_users as pu WHERE pu.fk_object = " . $_SESSION["uid"] . " AND pu.objtype = 'user'";
			$sql2 .= " UNION SELECT pu.fk_terminal FROM " . MAIN_DB_PREFIX . "pos_users as pu LEFT JOIN " . MAIN_DB_PREFIX . "usergroup_user as ug ON pu.fk_object = ug.fk_usergroup";
			$sql2 .= " WHERE ug.fk_user = " . $_SESSION["uid"] . " AND pu.objtype = 'group')";
		}

		$sql .= $sql2;

		$now = dol_now();
		$time = dol_getdate($now);
		$day = $time['mday'];
		$month = $time['mon'];
		$year = $time['year'];

		//Today
		$todayini = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 0, 0, 0);
		$todayfin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
		$sql .= " AND f.date_ticket BETWEEN '" . $todayini . "' AND '" . $todayfin . "' ) as today, ";

		$sql .= '(SELECT COUNT(f.rowid)';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'pos_ticket as f';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON f.fk_soc = sc.fk_soc AND sc.fk_user = " . $_SESSION["uid"];
		} // We need this table joined to the select in order to filter by sale
		$sql .= ' WHERE f.entity = ' . $conf->entity;

		$sql .= $sql2;

		//Yesterday
		$time = dol_get_prev_day($day, $month, $year);
		$yestini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $time['day'], 0, 0, 0);
		$yestfin = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $time['day'], 23, 59, 59);
		$sql .= " AND f.date_ticket BETWEEN '" . $yestini . "' AND '" . $yestfin . "' ) as yesterday, ";

		$sql .= '(SELECT COUNT(f.rowid)';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'pos_ticket as f';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON f.fk_soc = sc.fk_soc AND sc.fk_user = " . $_SESSION["uid"];
		} // We need this table joined to the select in order to filter by sale
		$sql .= ' WHERE f.entity = ' . $conf->entity;

		$sql .= $sql2;

		//This week
		$time = dol_get_first_day_week($day, $month, $year);
		$weekini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $time['first_day'], 0, 0, 0);
		$weekfin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
		$sql .= " AND f.date_ticket BETWEEN '" . $weekini . "' AND '" . $weekfin . "' ) as thisweek, ";

		$sql .= '(SELECT COUNT(f.rowid)';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'pos_ticket as f';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON f.fk_soc = sc.fk_soc AND sc.fk_user = " . $_SESSION["uid"];
		} // We need this table joined to the select in order to filter by sale
		$sql .= ' WHERE f.entity = ' . $conf->entity;

		$sql .= $sql2;

		//Last week
		$time = dol_get_first_day_week($day, $month, $year);
		$lweekini = sprintf("%04d%02d%02d%02d%02d%02d", $time['prev_year'], $time['prev_month'], $time['prev_day'], 0,
				0, 0);
		$lweekfin = sprintf("%04d%02d%02d%02d%02d%02d", $year,
				$time['first_day'] - 1 == 0 ? $time['prev_month'] : $month,
				$time['first_day'] - 1 == 0 ? $time['prev_day'] + 6 : $time['first_day'] - 1, 23, 59, 59);
		$sql .= " AND f.date_ticket BETWEEN '" . $lweekini . "' AND '" . $lweekfin . "' ) as lastweek, ";

		$sql .= '(SELECT COUNT(f.rowid)';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'pos_ticket as f';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON f.fk_soc = sc.fk_soc AND sc.fk_user = " . $_SESSION["uid"];
		} // We need this table joined to the select in order to filter by sale
		$sql .= ' WHERE f.entity = ' . $conf->entity;

		$sql .= $sql2;

		//Two weeks ago
		$time = dol_get_prev_week($day, '', $month, $year);
		$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
		$ini2week = sprintf("%04d%02d%02d%02d%02d%02d", $time2['year'], $time2['month'], $time2['day'], 0, 0, 0);
		$fin2week = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'],
				$time['day'] - 1 == 0 ? $time2['month'] : $time['month'],
				$time['day'] - 1 == 0 ? $time2['day'] + 6 : $time['day'] - 1, 23, 59, 59);
		$sql .= " AND f.date_ticket BETWEEN '" . $ini2week . "' AND '" . $fin2week . "' ) as twoweek, ";

		$sql .= '(SELECT COUNT(f.rowid)';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'pos_ticket as f';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON f.fk_soc = sc.fk_soc AND sc.fk_user = " . $_SESSION["uid"];
		} // We need this table joined to the select in order to filter by sale
		$sql .= ' WHERE f.entity = ' . $conf->entity;

		$sql .= $sql2;

		//Three weeks ago
		$time = dol_get_prev_week($day, '', $month, $year);
		$time = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
		$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
		$ini3week = sprintf("%04d%02d%02d%02d%02d%02d", $time2['year'], $time2['month'], $time2['day'], 0, 0, 0);
		$fin3week = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'],
				$time['day'] - 1 == 0 ? $time2['month'] : $time['month'],
				$time['day'] - 1 == 0 ? $time2['day'] + 6 : $time['day'] - 1, 23, 59, 59);
		$sql .= " AND f.date_ticket BETWEEN '" . $ini3week . "' AND '" . $fin3week . "' ) as threeweek, ";

		$sql .= '(SELECT COUNT(f.rowid)';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'pos_ticket as f';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON f.fk_soc = sc.fk_soc AND sc.fk_user = " . $_SESSION["uid"];
		} // We need this table joined to the select in order to filter by sale
		$sql .= ' WHERE f.entity = ' . $conf->entity;

		$sql .= $sql2;

		//This month
		$monthini = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, 01, 0, 0, 0);
		$monthfin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
		$sql .= " AND f.date_ticket BETWEEN '" . $monthini . "' AND '" . $monthfin . "' ) as thismonth, ";

		$sql .= '(SELECT COUNT(f.rowid)';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'pos_ticket as f';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON f.fk_soc = sc.fk_soc AND sc.fk_user = " . $_SESSION["uid"];
		} // We need this table joined to the select in order to filter by sale
		$sql .= ' WHERE f.entity = ' . $conf->entity;

		$sql .= $sql2;

		//One month ago
		$time = dol_get_prev_month($month, $year);
		$monthagoini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $day, 0, 0, 0);
		$monthagofin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
		$sql .= " AND f.date_ticket BETWEEN '" . $monthagoini . "' AND '" . $monthagofin . "' ) as monthago, ";

		$sql .= '(SELECT COUNT(f.rowid)';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'pos_ticket as f';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON f.fk_soc = sc.fk_soc AND sc.fk_user = " . $_SESSION["uid"];
		} // We need this table joined to the select in order to filter by sale
		$sql .= ' WHERE f.entity = ' . $conf->entity;

		$sql .= $sql2;

		//Last month
		$time = dol_get_prev_month($month, $year);
		$lmonthini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], 01, 0, 0, 0);
		$lmonthfin = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], 31, 0, 0, 0);
		$sql .= " AND f.date_ticket BETWEEN '" . $lmonthini . "' AND '" . $lmonthfin . "' ) as lastmonth";

		$res = $db->query($sql);

		if ($res) {
			$obj = $db->fetch_object($res);

			$result["today"] = $obj->today;
			$result["yesterday"] = $obj->yesterday;
			$result["thisweek"] = $obj->thisweek;
			$result["lastweek"] = $obj->lastweek;
			$result["twoweek"] = $obj->twoweek;
			$result["threeweek"] = $obj->threeweek;
			$result["thismonth"] = $obj->thismonth;
			$result["monthago"] = $obj->monthago;
			$result["lastmonth"] = $obj->lastmonth;

			return ErrorControl($result, $function);
		} else {
			return ErrorControl($ret, $function);
		}

	}

	/**
	 *
	 * Count Facture history
	 *
	 */
	public static function countHistoricFac()
	{
		global $db, $conf, $user;

		$ret = -1;
		$function = "GetHistoric";

		$sql = 'SELECT (SELECT COUNT(f.rowid)';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture as f';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON f.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
		} // We need this table joined to the select in order to filter by sale
		$sql .= ', ' . MAIN_DB_PREFIX . 'pos_facture as pf';
		$sql .= ' WHERE f.entity = ' . $conf->entity;
		$sql .= ' AND pf.fk_facture = f.rowid';

		if ($conf->global->POS_USER_TERMINAL) {
			$sql2 = " AND pf.fk_cash IN (";
			$sql2 .= "SELECT pu.fk_terminal FROM " . MAIN_DB_PREFIX . "pos_users as pu WHERE pu.fk_object = " . $_SESSION["uid"] . " AND pu.objtype = 'user'";
			$sql2 .= " UNION SELECT pu.fk_terminal FROM " . MAIN_DB_PREFIX . "pos_users as pu LEFT JOIN " . MAIN_DB_PREFIX . "usergroup_user as ug ON pu.fk_object = ug.fk_usergroup";
			$sql2 .= " WHERE ug.fk_user = " . $_SESSION["uid"] . " AND pu.objtype = 'group')";
		}

		$sql .= $sql2;

		$now = dol_now();
		$time = dol_getdate($now);
		$day = $time['mday'];
		$month = $time['mon'];
		$year = $time['year'];

		//Today
		$todayini = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 0, 0, 0);
		$todayfin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
		$sql .= " AND f.datec BETWEEN '" . $todayini . "' AND '" . $todayfin . "' ) as today, ";

		$sql .= '(SELECT COUNT(f.rowid)';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture as f';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON f.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
		} // We need this table joined to the select in order to filter by sale
		$sql .= ', ' . MAIN_DB_PREFIX . 'pos_facture as pf';
		$sql .= ' WHERE f.entity = ' . $conf->entity;
		$sql .= ' AND pf.fk_facture = f.rowid';

		$sql .= $sql2;

		//Yesterday
		$time = dol_get_prev_day($day, $month, $year);
		$yestini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $time['day'], 0, 0, 0);
		$yestfin = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $time['day'], 23, 59, 59);
		$sql .= " AND f.datec BETWEEN '" . $yestini . "' AND '" . $yestfin . "' ) as yesterday, ";

		$sql .= '(SELECT COUNT(f.rowid)';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture as f';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON f.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
		} // We need this table joined to the select in order to filter by sale
		$sql .= ', ' . MAIN_DB_PREFIX . 'pos_facture as pf';
		$sql .= ' WHERE f.entity = ' . $conf->entity;
		$sql .= ' AND pf.fk_facture = f.rowid';

		$sql .= $sql2;

		//This week
		$time = dol_get_first_day_week($day, $month, $year);
		$weekini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $time['first_day'], 0, 0, 0);
		$weekfin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
		$sql .= " AND f.datec BETWEEN '" . $weekini . "' AND '" . $weekfin . "' ) as thisweek, ";

		$sql .= '(SELECT COUNT(f.rowid)';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture as f';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON f.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
		} // We need this table joined to the select in order to filter by sale
		$sql .= ', ' . MAIN_DB_PREFIX . 'pos_facture as pf';
		$sql .= ' WHERE f.entity = ' . $conf->entity;
		$sql .= ' AND pf.fk_facture = f.rowid';

		$sql .= $sql2;

		//Last week
		$time = dol_get_first_day_week($day, $month, $year);
		$lweekini = sprintf("%04d%02d%02d%02d%02d%02d", $time['prev_year'], $time['prev_month'], $time['prev_day'], 0,
				0, 0);
		$lweekfin = sprintf("%04d%02d%02d%02d%02d%02d", $year,
				$time['first_day'] - 1 == 0 ? $time['prev_month'] : $month,
				$time['first_day'] - 1 == 0 ? $time['prev_day'] + 6 : $time['first_day'] - 1, 23, 59, 59);
		$sql .= " AND f.datec BETWEEN '" . $lweekini . "' AND '" . $lweekfin . "' ) as lastweek, ";

		$sql .= '(SELECT COUNT(f.rowid)';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture as f';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON f.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
		} // We need this table joined to the select in order to filter by sale
		$sql .= ', ' . MAIN_DB_PREFIX . 'pos_facture as pf';
		$sql .= ' WHERE f.entity = ' . $conf->entity;
		$sql .= ' AND pf.fk_facture = f.rowid';

		$sql .= $sql2;

		//Two weeks ago
		$time = dol_get_prev_week($day, '', $month, $year);
		$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
		$ini2week = sprintf("%04d%02d%02d%02d%02d%02d", $time2['year'], $time2['month'], $time2['day'], 0, 0, 0);
		$fin2week = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'],
				$time['day'] - 1 == 0 ? $time2['month'] : $time['month'],
				$time['day'] - 1 == 0 ? $time2['day'] + 6 : $time['day'] - 1, 23, 59, 59);
		$sql .= " AND f.datec BETWEEN '" . $ini2week . "' AND '" . $fin2week . "' ) as twoweek, ";

		$sql .= '(SELECT COUNT(f.rowid)';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture as f';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON f.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
		} // We need this table joined to the select in order to filter by sale
		$sql .= ', ' . MAIN_DB_PREFIX . 'pos_facture as pf';
		$sql .= ' WHERE f.entity = ' . $conf->entity;
		$sql .= ' AND pf.fk_facture = f.rowid';

		$sql .= $sql2;

		//Three weeks ago
		$time = dol_get_prev_week($day, '', $month, $year);
		$time = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
		$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
		$ini3week = sprintf("%04d%02d%02d%02d%02d%02d", $time2['year'], $time2['month'], $time2['day'], 0, 0, 0);
		$fin3week = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'],
				$time['day'] - 1 == 0 ? $time2['month'] : $time['month'],
				$time['day'] - 1 == 0 ? $time2['day'] + 6 : $time['day'] - 1, 23, 59, 59);
		$sql .= " AND f.datec BETWEEN '" . $ini3week . "' AND '" . $fin3week . "' ) as threeweek, ";

		$sql .= '(SELECT COUNT(f.rowid)';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture as f';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON f.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
		} // We need this table joined to the select in order to filter by sale
		$sql .= ', ' . MAIN_DB_PREFIX . 'pos_facture as pf';
		$sql .= ' WHERE f.entity = ' . $conf->entity;
		$sql .= ' AND pf.fk_facture = f.rowid';

		$sql .= $sql2;

		//This month
		$monthini = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, 01, 0, 0, 0);
		$monthfin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
		$sql .= " AND f.datec BETWEEN '" . $monthini . "' AND '" . $monthfin . "' ) as thismonth, ";

		$sql .= '(SELECT COUNT(f.rowid)';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture as f';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON f.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
		} // We need this table joined to the select in order to filter by sale
		$sql .= ', ' . MAIN_DB_PREFIX . 'pos_facture as pf';
		$sql .= ' WHERE f.entity = ' . $conf->entity;
		$sql .= ' AND pf.fk_facture = f.rowid';

		$sql .= $sql2;

		//One month ago
		$time = dol_get_prev_month($month, $year);
		$monthagoini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], $day, 0, 0, 0);
		$monthagofin = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month, $day, 23, 59, 59);
		$sql .= " AND f.datec BETWEEN '" . $monthagoini . "' AND '" . $monthagofin . "' ) as monthago, ";

		$sql .= '(SELECT COUNT(f.rowid)';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture as f';
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " RIGHT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON f.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
		} // We need this table joined to the select in order to filter by sale
		$sql .= ', ' . MAIN_DB_PREFIX . 'pos_facture as pf';
		$sql .= ' WHERE f.entity = ' . $conf->entity;
		$sql .= ' AND pf.fk_facture = f.rowid';

		$sql .= $sql2;

		//Last month
		$time = dol_get_prev_month($month, $year);
		$lmonthini = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], 01, 0, 0, 0);
		$lmonthfin = sprintf("%04d%02d%02d%02d%02d%02d", $time['year'], $time['month'], 31, 0, 0, 0);
		$sql .= " AND f.datec BETWEEN '" . $lmonthini . "' AND '" . $lmonthfin . "' ) as lastmonth";

		$res = $db->query($sql);

		if ($res) {
			$obj = $db->fetch_object($res);

			$result["today"] = $obj->today;
			$result["yesterday"] = $obj->yesterday;
			$result["thisweek"] = $obj->thisweek;
			$result["lastweek"] = $obj->lastweek;
			$result["twoweek"] = $obj->twoweek;
			$result["threeweek"] = $obj->threeweek;
			$result["thismonth"] = $obj->thismonth;
			$result["monthago"] = $obj->monthago;
			$result["lastmonth"] = $obj->lastmonth;

			return ErrorControl($result, $function);
		} else {
			return ErrorControl($ret, $function);
		}

	}

    /**
     *
     * Add ticket Payment
     *
     * @param array $aryTicket Ticket data array
     * @return int
     */
	private static function addPayment($aryTicket)
	{
		global $db, $langs;

		require_once(DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php');
		$now = dol_now();
		$userstatic = new User($db);
		if (!$aryTicket['employeeId']) {
			$employee = $_SESSION['uid'];

		} else {
			$employee = $aryTicket['employeeId'];
		}
		$userstatic->fetch($employee);

		if ($aryTicket['type'] == 1) {
			$aryTicket['total'] = $aryTicket['total'] * -1;
			$aryTicket['customerpay1'] = $aryTicket['customerpay1'] * -1;
			$aryTicket['customerpay2'] = $aryTicket['customerpay2'] * -1;
			$aryTicket['customerpay3'] = $aryTicket['customerpay3'] * -1;
		}

		$cash = new Cash($db);

		$terminal = $_SESSION['TERMINAL_ID'];
		$cash->fetch($terminal);

		if ($aryTicket['customerpay1'] != 0) {
			$bankaccountid[1] = $cash->fk_paycash;
			$modepay[1] = $cash->fk_modepaycash;
			$amount[1] = $aryTicket['customerpay1'] + ($aryTicket['difpayment'] < 0 ? $aryTicket['difpayment'] : 0);
		}
		if ($aryTicket['customerpay2'] != 0) {
			$bankaccountid[2] = $cash->fk_paybank;
			$modepay[2] = $cash->fk_modepaybank;
			$amount[2] = $aryTicket['customerpay2'];
		}
		if ($aryTicket['customerpay3'] != 0) {
			$bankaccountid[3] = $cash->fk_paybank_extra;
			$modepay[3] = $cash->fk_modepaybank_extra;
			$amount[3] = $aryTicket['customerpay3'];
		}

		$i = 1;

		$payment = new Payment($db);
		$error = 0;
		while ($i <= 3) {
			$payment->datepaye = $now;
			$payment->bank_account = $bankaccountid[$i];
			$payment->amounts[$aryTicket['id']] = $amount[$i];
			$payment->note = $langs->trans("Payment") . ' ' . $langs->trans("Ticket") . ' ' . $aryTicket['ref'];
			$payment->paiementid = $modepay[$i];
			$payment->num_paiement = '';

			if ($amount[$i] != 0) {
				$paiement_id = $payment->create($userstatic);
				if ($paiement_id > 0) {
					$result = $payment->addPaymentToBank($userstatic, 'payment', '(CustomerFacturePayment)',
							$bankaccountid[$i], $aryTicket['customerId'], '', '');
					if (!$result > 0) {
						$error++;
					}
				} else {
					$error++;
				}
			}
			$i++;
		}
		if ($error) {
			return -1;
		} else {
			return $paiement_id;
		}
	}

	/**
	 *
	 * Add facture Payment
	 *
	 * @param array $aryTicket Ticket data array
	 */
	private static function addPaymentFac($aryTicket)
	{
		global $db, $langs, $conf, $user;

		require_once(DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php');
		$now = dol_now();
		$userstatic = new User($db);
		$error = 0;
		if (!$aryTicket['employeeId']) {
			$employee = $_SESSION['uid'];

		} else {
			$employee = $aryTicket['employeeId'];
		}
		$userstatic->fetch($employee);

		$max_ite = 3;

		if ($aryTicket['convertDis']) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
			$object = new Facture($db);
			$object->fetch($aryTicket['id']);
			$object->fetch_thirdparty();


			// Check if there is already a discount (protection to avoid duplicate creation when resubmit post)
			$discountcheck = new DiscountAbsolute($db);
			$result = $discountcheck->fetch(0, $object->id);

			$canconvert = 0;
            if ($object->type == Facture::TYPE_DEPOSIT && $object->paye == 1 && empty($discountcheck->id)) {
                $canconvert = 1;    // we can convert deposit into discount if deposit is payed completely and not already converted (see real condition into condition used to show button converttoreduc)
            }
            if ($object->type == Facture::TYPE_CREDIT_NOTE && $object->paye == 0 && empty($discountcheck->id)) {
				$canconvert = 1;
			}    // we can convert credit note into discount if credit note is not payed back and not already converted and amount of payment is 0 (see real condition into condition used to show button converttoreduc)
			if ($canconvert) {
				$db->begin();

				// Boucle sur chaque taux de tva
				$i = 0;
				$amount_ht = array();
				$amount_tva = array();
				$amount_ttc = array();
				foreach ($object->lines as $line) {
					$amount_ht [$line->tva_tx] += $line->total_ht;
					$amount_tva [$line->tva_tx] += $line->total_tva;
					$amount_ttc [$line->tva_tx] += $line->total_ttc;
					$i++;
				}

				// Insert one discount by VAT rate category
				$discount = new DiscountAbsolute($db);
				if ($object->type == Facture::TYPE_CREDIT_NOTE)
                    $discount->description = '(CREDIT_NOTE)';
                elseif ($object->type == Facture::TYPE_DEPOSIT)
                    $discount->description = '(DEPOSIT)';

				$discount->tva_tx = abs($object->total_ttc);
				$discount->fk_soc = $object->socid;
				$discount->fk_facture_source = $object->id;

				$error = 0;
				foreach ($amount_ht as $tva_tx => $xxx) {
					$discount->amount_ht = abs($amount_ht [$tva_tx]);
					$discount->amount_tva = abs($amount_tva [$tva_tx]);
					$discount->amount_ttc = abs($amount_ttc [$tva_tx]);
					$discount->tva_tx = abs($tva_tx);

					$paiement_id = $discount->create($userstatic);
					if ($paiement_id < 0) {
						$error++;
						break;
					}
				}

				if (empty($error)) {
					// Classe facture
					$paiement_id = $object->set_paid($user);
					if ($result >= 0) {
						//$mesgs[]='OK'.$discount->id;
						$db->commit();
					} else {
						$db->rollback();
					}
				} else {
					$db->rollback();
				}
			}
		} else {
			if ($aryTicket['type'] == 1) {
				if ($aryTicket['total'] > $aryTicket['customerpay'] && $aryTicket['difpayment'] == 0 && !empty($conf->rewards->enabled)) {
					dol_include_once('/rewards/class/rewards.class.php');
					$reward = new Rewards($db);
					$facture = new Facture($db);
					$facture->fetch($aryTicket['id']);

					$modepay[4] = dol_getIdFromCode($db, 'PNT', 'c_paiement');
					$amount[4] = $aryTicket['total'] - $aryTicket['customerpay'];

					$result = $reward->create($facture, (price2num($amount[4]) / $conf->global->REWARDS_DISCOUNT));
					$max_ite++;
					$amount[4] = $amount[4] * -1;
					//TODO tot molt bonico, pero que pasa si no gaste punts?
				}
				$aryTicket['total'] = $aryTicket['total'] * -1;
				$aryTicket['customerpay1'] = $aryTicket['customerpay1'] * -1;
				$aryTicket['customerpay2'] = $aryTicket['customerpay2'] * -1;
				$aryTicket['customerpay3'] = $aryTicket['customerpay3'] * -1;
			}

			$cash = new Cash($db);

			$terminal = $_SESSION['TERMINAL_ID'];
			$cash->fetch($terminal);

			if ($aryTicket['customerpay1'] != 0) {
				$bankaccountid[1] = $cash->fk_paycash;
				$modepay[1] = $cash->fk_modepaycash;
				$amount[1] = $aryTicket['customerpay1'] + ($aryTicket['difpayment'] < 0 ? $aryTicket['difpayment'] : 0);
			}
			if ($aryTicket['customerpay2'] != 0) {
				$bankaccountid[2] = $cash->fk_paybank;
				$modepay[2] = $cash->fk_modepaybank;
				$amount[2] = $aryTicket['customerpay2'];
			}
			if ($aryTicket['customerpay3'] != 0) {
				$bankaccountid[3] = $cash->fk_paybank_extra;
				$modepay[3] = $cash->fk_modepaybank_extra;
				$amount[3] = $aryTicket['customerpay3'];
			}
			//Añadir el posible pago de puntos
			if ($aryTicket['points'] > 0 && !empty($conf->rewards->enabled)) {
				dol_include_once('/rewards/class/rewards.class.php');
				$reward = new Rewards($db);
				$facture = new Facture($db);
				$facture->fetch($aryTicket['id']);
				$res = $reward->usePoints($facture, $aryTicket['points']);
			}
			$i = 1;

			$payment = new Paiement($db);

			while ($i <= $max_ite) {
				$payment->datepaye = $now;
				$payment->bank_account = $bankaccountid[$i];
				$payment->amounts[$aryTicket['id']] = $amount[$i];
				$payment->note = $langs->trans("Payment") . ' ' . $langs->trans("Invoice") . ' ' . $aryTicket['ref'];
				$payment->paiementid = $modepay[$i];
				$payment->num_paiement = '';
				$payment->multicurrency_amounts[$aryTicket['id']] = $amount[$i];

				if ($amount[$i] != 0) {
					$paiement_id = $payment->create($userstatic, 1);
					if ($paiement_id > 0) {
						if ($payment->paiementid != dol_getIdFromCode($db, 'PNT', 'c_paiement')) {
							$result = $payment->addPaymentToBank($userstatic, 'payment', '(CustomerFacturePayment)',
									$bankaccountid[$i], $aryTicket['customerId'], '', '');
							if ($result < 0) {
								$error++;
							}
						}
					} else {
						$error++;
					}
				}
				$i++;
			}
		}
		if ($error > 0) {
			return -1;
		} else {
			return 1;
		}//$paiement_id;
	}

	private static function quitSotck($data, $isreturn = false)
	{
		global $db, $langs, $conf;
		require_once(DOL_DOCUMENT_ROOT . "/product/stock/class/mouvementstock.class.php");

		$userstatic = new User($db);
		$userstatic->fetch($_SESSION['uid']);

		$error = 0;
		$cash = new Cash($db);
		$terminal = $_SESSION['TERMINAL_ID'];
		$cash->fetch($terminal);
		$warehouse = $cash->fk_warehouse;

		foreach ($data['lines'] as $line) {
			if (count($line) > 0) {
				if ($line['idProduct']) {
					$mouvP = new MouvementStock($db);
					// We decrease stock for product
                    if ($data['mode'] > 0){
                        $mouvP->origin = new Facture($db);
                        $mouvP->origin->id = $data['id'];
                    }

					if (!empty($conf->productbatch->enabled)) {
						if (count($line['batchs']) > 0) {

							foreach ($line['batchs'] as $batch) {
								if (!$isreturn) {

									$result = $mouvP->livraison($userstatic, $line['idProduct'], $warehouse, $batch['qty'],
										$line['price'], $langs->trans("TicketCreatedInDolibarr"), '', '', '',
										$batch['batch']);
								} else {
									$result = $mouvP->reception($userstatic, $line['idProduct'], $warehouse, $batch['qty'],
										0, $langs->trans("TicketCreatedInDolibarr"), '', '', $batch['batch']);
								}
							}

						} else {
							if ($conf->global->POS_BATCH_SERIE == 0) {
								//2byte.es impulse
								$sql = "SELECT t.rowid as idbatch, w.rowid as idprod_stock, p.tobatch";
								$sql .= " FROM " . MAIN_DB_PREFIX . "product AS p LEFT JOIN " . MAIN_DB_PREFIX . "product_stock w ON w.fk_product = p.rowid";
								$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_batch t ON t.fk_product_stock = w.rowid";
								$sql .= " WHERE p.rowid = " . $line['idProduct'] . " AND w.fk_entrepot = " . $warehouse;
								$sql .= " ORDER BY t.sellby, t.eatby ";
								$sql .= " LIMIT 1";
								$resql = $db->query($sql);
								if ($resql) {
									if ($db->num_rows($resql)) {
										$obj = $db->fetch_object($resql);

										if ($obj->tobatch == 0) {
											if (!$isreturn) {
												$result = $mouvP->livraison($userstatic, $line['idProduct'], $warehouse,
													$line['cant'], $line['price'], $langs->trans("TicketCreatedInDolibarr"), '',
													'', '');
											} else {
												$result = $mouvP->reception($userstatic, $line['idProduct'], $warehouse,
													$line['cant'], 0, $langs->trans("TicketCreatedInDolibarr"), '', '', '',
													'Indefinido');
												//$result = $mouvP->_create($userstatic, $line['idProduct'], $warehouse, $line['cant'], 3, $line['price'], $langs->trans("TicketCreatedInDolibarr"), '', '', '', '', '',true);
											}
										} else {
											if (!$isreturn) {
												$result = $mouvP->livraison($userstatic, $line['idProduct'], $warehouse,
													$line['cant'], $line['price'], $langs->trans("TicketCreatedInDolibarr"), '',
													'', '', 'Indefinido');
												/*$dluo = array();
												$dluo['fk_product_stock'] = $obj->idprod_stock;
												$dluo['eatby'] = '';
												$dluo['sellby'] = '';
												$dluo['batchnumber'] = 'Indefinido';
												$result = $mouvP->_create_batch($dluo, $line['cant']);*/
											} else {
												//$result=$mouvP->reception($userstatic, $line['idProduct'], $warehouse, $line['cant'], $line['price'], $langs->trans("TicketCreatedInDolibarr"),'','','','Indefinido');
												$result = $mouvP->_create($userstatic, $line['idProduct'], $warehouse,
													$line['cant'], 3, 0, $langs->trans("TicketCreatedInDolibarr"), '', '', '',
													'', 'Indefinido', true);
												/*$dluo = array();
												$dluo['fk_product_stock'] = $obj->idprod_stock;
												$dluo['eatby'] = '';
												$dluo['sellby'] = '';
												$dluo['batchnumber'] = 'Indefinido';
												$result = $mouvP->_create_batch($dluo, $line['cant']);*/
											}
										}
									}
								}
							}

						}
					} else {
						if (!$isreturn) {
							$mouvP->livraison($userstatic, $line['idProduct'], $warehouse, $line['cant'],
								$line['price'], $langs->trans("TicketCreatedInDolibarr"), '', '', '');
						} else {
							$mouvP->reception($userstatic, $line['idProduct'], $warehouse, $line['cant'], 0,
								$langs->trans("TicketCreatedInDolibarr"), '', '', '');
						}
					}
					if ($result < 0) {
						$error++;
					}
				}

			}
		}
		return $error;
	}

	/**
	 *
	 * Get user POS
	 *
	 * @return    array    User and terminal name
	 */
	public static function getLogin()
	{
		global $db, $langs;

		$error = 0;
		$function = "getLogin";

		$userstatic = new User($db);
		if ($userstatic->fetch($_SESSION['uid']) != 0) {
			$error++;
		}

		$cash = new Cash($db);
		$terminal = $_SESSION['TERMINAL_ID'];
		if ($cash->fetch($terminal) < 0) {
			$error++;
		}

		if (!$error) {
			$ret['User'] = $userstatic->getFullName($langs);
			$ret['Terminal'] = $cash->name;
			return ErrorControl($ret, $function);
		} else {
			$error = $error * -1;
			return ErrorControl($error, $function);
		}
	}

	/**
	 *
	 * Create Customer into DB
	 *
	 * @param        array $aryCustomer Customer object
	 * @return        array    $result        Result
	 */
	public static function SetCustomer($aryCustomer)
	{
		require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php");

		global $conf, $db, $user, $mysoc;
		$function = "SetCustomer";

		$object = new Societe($db);

		if ($aryCustomer["idprof1"] && $object->id_prof_verifiable(1)) {
			if ($object->id_prof_exists(1, $aryCustomer["idprof1"])) {
				$res = -5;
				return ErrorControl($res, $function);
			}
		}

		if (!$aryCustomer["idprof1"] && !empty($conf->global->SOCIETE_IDPROF1_MANDATORY)) {
			$res = -6;
			return ErrorControl($res, $function);
		}


		//We use code creation
		$module = $conf->global->SOCIETE_CODECLIENT_ADDON;

		if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php') {
			$module = substr($module, 0, dol_strlen($module) - 4);
		}
		require_once(DOL_DOCUMENT_ROOT . "/core/modules/societe/" . $module . ".php");
		$modCodeClient = new $module;

		$object->particulier = 1;
		$object->typent_id = 8; // TODO predict another method if the field "special" change of rowid
		$object->client = 1;
		$object->fournisseur = 0;
		$object->tva_assuj = 1;
		$object->status = 1;
		$object->country_id = $mysoc->country_id;

		$object->name = $conf->global->MAIN_FIRSTNAME_NAME_POSITION ? trim($aryCustomer['prenom'] . ' ' . $aryCustomer["nom"]) : trim($aryCustomer["nom"] . ' ' . $aryCustomer["prenom"]);
		$object->idprof1 = $aryCustomer["idprof1"];
		$object->address = $aryCustomer["address"];
		$object->town = $aryCustomer["town"];
		$object->zip = $aryCustomer["zip"];
		$object->phone = $aryCustomer["tel"];
		$object->email = $aryCustomer["email"];

		if ($modCodeClient->code_auto) {
			$tmpcode = $modCodeClient->getNextValue($object, 0);
		}
		$object->code_client = $tmpcode;

		$res = $object->create($user);

		//Si opción de configuración, asignar como comercial el usuario activo.
		if ($conf->global->POS_COMERCIAL) {
			$object->add_commercial($user, $user->id);

			if ($conf->global->POS_USER_TERMINAL) {
				$sql = "SELECT u.rowid as id";
				$sql .= " FROM " . MAIN_DB_PREFIX . "user as u";
				$sql .= " , " . MAIN_DB_PREFIX . "pos_users as pu";
				$sql .= " WHERE pu.fk_terminal =" . $_SESSION["TERMINAL_ID"];
				$sql .= " AND pu.fk_object = u.rowid";
				$sql .= " AND objtype = 'user'";

				$sql .= " UNION SELECT DISTINCT v.fk_user as id";
				$sql .= " FROM " . MAIN_DB_PREFIX . "usergroup as ug";
				$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "usergroup_user as v ON v.fk_usergroup = ug.rowid";
				$sql .= " , " . MAIN_DB_PREFIX . "pos_users as pu";
				$sql .= " WHERE pu.fk_terminal =" . $_SESSION["TERMINAL_ID"];
				$sql .= " AND pu.fk_object = ug.rowid";
				$sql .= " AND objtype = 'group'";

				$resql = $db->query($sql);
				if ($resql) {
					$num = $db->num_rows($resql);
					$i = 0;

					while ($i < $num) {
						$obj = $db->fetch_object($resql);

						$object->add_commercial($user, $obj->id);

						$i++;
					}

					$db->free($resql);
				} else {
					dol_print_error($db);
				}
			}
		}

		return ErrorControl($res, $function);

	}

	/**
	 *
	 * Create product into DB
	 *
	 * @param        array $aryProduct Product object
	 * @return        array    $result        Result
	 */
	public static function SetProduct($aryProduct)
	{
		require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");

		global $db, $conf, $mysoc;

		$code_pays = "'" . $mysoc->country_code . "'";

		$function = "SetProduct";

		$userstatic = new User($db);
		$userstatic->fetch($_SESSION['uid']);

		$userstatic->getrights('produit');

		if ($userstatic->rights->produit->creer) {

			$sql = "SELECT DISTINCT t.taux";
			$sql .= " FROM " . MAIN_DB_PREFIX . "c_tva as t, " . MAIN_DB_PREFIX . "c_country as p";
			$sql .= " WHERE t.fk_pays = p.rowid";
			$sql .= " AND t.active = 1";
			$sql .= " AND t.rowid = " . $aryProduct['tax'];
			$sql .= " AND p.code in (" . $code_pays . ")";
			$sql .= " ORDER BY t.taux DESC";

			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				if ($num) {
					for ($i = 0; $i < $num; $i++) {
						$obj = $db->fetch_object($resql);

					}
				}
			}

			$myproduct = new Product($db);

			$myproduct->ref = $aryProduct['ref'];
			$myproduct->libelle = $aryProduct['label'];
			$myproduct->label = $aryProduct['label'];
			if ($conf->global->POS_TICKET_TTC) {
				$myproduct->price_ttc = $aryProduct['price_ttc'];
				$myproduct->price_base_type = 'TTC';
			} else {
				$myproduct->price_ht = $aryProduct['price_ht'];
				$myproduct->price_base_type = 'HT';
			}
			$myproduct->tva_tx = $obj->taux;
			$myproduct->type = 0;
			$myproduct->status = 1;



			$res = $myproduct->create($userstatic);
		}
		else{
			$res = -4;
		}

		return ErrorControl($res, $function);
	}

	/**
	 *
	 * Return the VAT list
	 *
	 * @return        array        Applicable VAT
	 */
	public static function select_VAT()
	{
		global $db, $mysoc;

		$code_pays = "'" . $mysoc->country_code . "'";

		$sql = "SELECT DISTINCT t.rowid, t.taux";
		$sql .= " FROM " . MAIN_DB_PREFIX . "c_tva as t, " . MAIN_DB_PREFIX . "c_country as p";
		$sql .= " WHERE t.fk_pays = p.rowid";
		$sql .= " AND t.active = 1";
		$sql .= " AND p.code in (" . $code_pays . ")";
		$sql .= " ORDER BY t.taux DESC";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			if ($num) {
				for ($i = 0; $i < $num; $i++) {
					$obj = $db->fetch_object($resql);
					$vat[$i]['id'] = $obj->rowid;
					$vat[$i]['label'] = $obj->taux . '%';
				}
			}
		}

		return $vat;
	}

	/**
	 *
	 * Return the money in cash
	 *
	 * @return        array        Applicable VAT
	 */
	public static function getMoneyCash($open = false)
	{
		global $db;

		$terminal = $_SESSION['TERMINAL_ID'];

		$cash = new ControlCash($db, $terminal);

		return $cash->getMoneyCash($open);

	}

	/**
	 *
	 * Enter description here ...
	 * @param $aryClose
	 */
	public static function setControlCash($aryClose)
	{
		global $db;

		$function = "closeCash";
		$error = 0;

		$terminalid = $_SESSION['TERMINAL_ID'];
		$userpos = new User($db);
		$userpos->fetch($aryClose['employeeId']);
		$userpos->getrights('pos');
		if ($userpos->rights->pos->closecash || !$aryClose['type']) {


			$cash = new ControlCash($db, $terminalid);

			$data['userid'] = $aryClose['employeeId'];
			$data['amount_reel'] = $aryClose['moneyincash'];
			$data['amount_teoric'] = $cash->getMoneyCash();
			$data['amount_diff'] = $data['amount_reel'] - $data['amount_teoric'];
			$data['type_control'] = $aryClose['type'];
			$data['print'] = $aryClose['print'];

			$res = $cash->create($data);

			if ($res > 0) {
				$terminal = new Cash($db);
				$userstatic = new User($db);
				$userstatic->fetch($data['userid']);
				$terminal->fetch($terminalid);

				if ($aryClose['type'] == 1) {
					if (!$terminal->set_used($userstatic)) {
						$error++;
					}
				} elseif ($aryClose['type'] == 2) {
					if (!$terminal->set_unused($userstatic)) {
						$error++;
					}
				}
			} else {
				$error++;
			}
		} else {
			$error = 2;
		}

		if ($error == 0) {
			$error = $res;
		} else {
			$error = $error * -1;
		}

		return ErrorControl($error, $function);

	}

	/**
	 *
	 * Return POS Config
	 *
	 * @return    array        Array with config
	 */
	public static function getConfig()
	{
		global $db, $conf, $langs;

		$cash = new Cash($db);

		$terminal = $_SESSION['TERMINAL_ID'];
		$cash->fetch($terminal);

		$userstatic = new User($db);
		$userstatic->fetch($_SESSION['uid']);

		$soc = new Societe($db, $cash->fk_soc);
		$soc->fetch($cash->fk_soc);
		$name = $soc->name ? $soc->name : $soc->nom;

		$ret['error']['value'] = 0;
		$ret['error']['desc'] = '';

		$ret['data']['terminal']['id'] = $cash->id;
		$ret['data']['terminal']['name'] = $cash->name;
		$ret['data']['terminal']['tactil'] = $cash->tactil;
		$ret['data']['terminal']['warehouse'] = $cash->fk_warehouse;
		$ret['data']['terminal']['barcode'] = $cash->barcode;
		$ret['data']['terminal']['mode_info'] = 0;
		$ret['data']['terminal']['faclimit'] = $conf->global->POS_MAX_TTC;

		$ret['data']['module']['places'] = $conf->global->POS_PLACES;
		$ret['data']['module']['print'] = $conf->global->POS_PRINT;
		$ret['data']['module']['mail'] = $conf->global->POS_MAIL;
		$ret['data']['module']['points'] = $conf->global->REWARDS_DISCOUNT;
		$ret['data']['module']['ticket'] = $conf->global->POS_TICKET;
		$ret['data']['module']['facture'] = $conf->global->POS_FACTURE;
		$ret['data']['module']['print_mode'] = $conf->global->POS_PRINT_MODE;
		$ret['data']['module']['series']= 0;
		$ret['data']['module']['ttc'] = $conf->global->POS_TICKET_TTC?$conf->global->POS_TICKET_TTC:0;

		if ($conf->global->FACTURE_ADDON == 'mod_facture_numberseries' && $conf->numberseries->enabled && $conf->global->NUMBERSERIES_POS) {
            $ret['data']['module']['series'] = 1;
        }

		$ret['data']['user']['id'] = $userstatic->id;
		$ret['data']['user']['name'] = $userstatic->getFullName($langs);
		$dir = $conf->user->dir_output;
		if ($userstatic->photo) {
			$file = get_exdir($userstatic->id, 2, 0, 1, $userstatic, 'user') . "/" . $userstatic->photo;
		}
		if ($file && file_exists($dir . "/" . $file)) {
			$ret['data']['user']['photo'] = DOL_URL_ROOT . '/viewimage.php?modulepart=userphoto&entity=' . $userstatic->entity . '&file=' . urlencode($file);
		} else {

			if (version_compare(DOL_VERSION, 3.8) >= 0) {

				if ($userstatic->gender == "woman") {

					$ret['data']['user']['photo'] = DOL_URL_ROOT . '/public/theme/common/user_woman.png';
				} else {

					$ret['data']['user']['photo'] = DOL_URL_ROOT . '/public/theme/common/user_man.png';
				}

			} else {

				$ret['data']['user']['photo'] = DOL_URL_ROOT . '/theme/common/nophoto.jpg';
			}

		}
		$ret['data']['customer']['id'] = $soc->id;
		$ret['data']['customer']['name'] = $name;
		$ret['data']['customer']['remise'] = $soc->remise_percent;
		$ret['data']['customer']['coupon'] = $soc->getAvailableDiscounts();
		$ret['data']['customer']['points'] = null;
		if ($conf->global->REWARDS_POS && !empty($conf->rewards->enabled)) {
            dol_include_once('/rewards/class/rewards.class.php');
			$rew = new Rewards($db);
			$res = $rew->getCustomerReward($soc->id);
			if ($res) {
				$ret['data']['customer']['points'] = $rew->getCustomerPoints($soc->id);
			}
		}

		$ret['data']['decrange']['unit'] = $conf->global->MAIN_MAX_DECIMALS_UNIT;
		$ret['data']['decrange']['tot'] = $conf->global->MAIN_MAX_DECIMALS_TOT;
		$ret['data']['decrange']['maxshow'] = $conf->global->MAIN_MAX_DECIMALS_SHOWN;

		return $ret;
	}

	public static function testSource($aryTicket)
	{
		global $db;

		$data = $aryTicket['data'];
		$lines = $data['lines'];

		//Compare
		$i = 0;
		foreach ($lines as $line) {
			if (count($line) > 0) {
				if ($line['idProduct'] > 0) {
					//Returned products for Source ticket
					$sql = "SELECT td.qty from " . MAIN_DB_PREFIX . "pos_ticketdet td";
					$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "pos_ticket t";
					$sql .= " WHERE td.fk_ticket = t.rowid";
					$sql .= " AND t.rowid= " . $data['idsource'];
					$sql .= " AND td.fk_product = " . $line['idProduct'];

					$resql = $db->query($sql);

					if ($resql) {
						//Compare quantity returned
						if ($db->num_rows($resql)) {
							$obj = $db->fetch_object($resql);

							$vendidas = $obj->qty;

						}
					}

					//Returned products for Source ticket
					$sql = "SELECT sum(td.qty) as qty from " . MAIN_DB_PREFIX . "pos_ticketdet td";
					$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "pos_ticket t";
					$sql .= " WHERE td.fk_ticket = t.rowid";
					$sql .= " AND t.fk_ticket_source= " . $data['idsource'];
					$sql .= " AND td.fk_product = " . $line['idProduct'];

					$resql = $db->query($sql);

					if ($resql) {
						//Compare quantity returned
						if ($db->num_rows($resql)) {
							$obj = $db->fetch_object($resql);
							if ($vendidas - abs($obj->qty) < $line['cant']) {
								$prods_returns[$i] = $line['idProduct'];
								$i++;
							}
						}
					}
				}
			}
		}


		return $prods_returns;
	}

	public static function testSourceFac($aryTicket)
	{
		global $db;

		$data = $aryTicket['data'];
		$lines = $data['lines'];

		//Compare
		$i = 0;
		foreach ($lines as $line) {
			if (count($line) > 0) {
				if ($line['idProduct'] > 0) {
					$sql = "SELECT fd.qty from " . MAIN_DB_PREFIX . "facturedet fd";
					$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture f";
					$sql .= " WHERE fd.fk_facture = f.rowid";
					$sql .= " AND f.rowid= " . $data['idsource'];
					$sql .= " AND fd.fk_product = " . $line['idProduct'];

					$resql = $db->query($sql);
					if ($resql) {

						//Compare quantity returned
						if ($db->num_rows($resql)) {
							$obj = $db->fetch_object($resql);

							$vendidas = $obj->qty;

						}
					}
					//Returned products for Source ticket
					$sql = "SELECT sum(fd.qty) as qty from " . MAIN_DB_PREFIX . "facturedet fd";
					$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture f";
					$sql .= " WHERE fd.fk_facture = f.rowid";
					$sql .= " AND f.fk_facture_source= " . $data['idsource'];
					$sql .= " AND fd.fk_product = " . $line['idProduct'];

					$resql = $db->query($sql);

					if ($resql) {
						//Compare quantity returned
						if ($db->num_rows($resql)) {
							$obj = $db->fetch_object($resql);
							if ($vendidas - abs($obj->qty) < $line['cant']) {
								$prods_returns[$i] = $line['idProduct'];
								$i++;
							}
						}
					}

				}
			}
		}


		return $prods_returns;
	}

	/**
	 * Return the places of the company
	 *
	 * @return array        return <0 if KO; array of places
	 */
	public static function getPlaces()
	{
		global $db, $conf;

		$sql = 'SELECT rowid,';
		$sql .= 'name, ';
		$sql .= 'description, ';
		$sql .= 'status, ';
		$sql .= 'fk_ticket ';
		$sql .= 'From ' . MAIN_DB_PREFIX . 'pos_places p';
		$sql .= ' WHERE p.entity =' . $conf->entity;

		$resql = $db->query($sql);

		if ($resql) {
			$places = array();
			$num = $db->num_rows($resql);
			$i = 0;

			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$places[$i]["id"] = $obj->rowid;
				$places[$i]["name"] = $obj->name;
				$places[$i]["description"] = $obj->description;
				$places[$i]["fk_ticket"] = $obj->fk_ticket;
				$places[$i]["status"] = $obj->status;

				$i++;
			}
		}
		return $places;
	}

	/**
	 * Fill the body of email's message with a ticket
	 *
	 * @param int $id
	 *
	 * @return string        String with ticket data
	 */
	public static function fillMailTicketBody($id)
	{
		global $db, $conf, $langs, $mysoc;

		$ticket = new Ticket($db);
		$res = $ticket->fetch($id);
		$mysoc = new Societe($db);
		$mysoc->fetch($ticket->socid);
		$userstatic = new User($db);
		$userstatic->fetch($ticket->user_close);

		$label = $ticket->ref;
		$facture = new Facture($db);
		if ($ticket->fk_facture) {
			$facture->fetch($ticket->fk_facture);
			$label = $facture->ref;
		}

		$message = $conf->global->MAIN_INFO_SOCIETE_NOM . " \n" . $conf->global->MAIN_INFO_SOCIETE_ADRESSE . " \n" . $conf->global->MAIN_INFO_SOCIETE_CP . ' ' . $conf->global->MAIN_INFO_SOCIETE_VILLE . " \n\n";

		$message .= $label . " \n" . dol_print_date($ticket->date_closed, 'dayhourtext') . " \n";
		$message .= $langs->transnoentities("Vendor") . ': ' . $userstatic->firstname . " " . $userstatic->lastname . "\n";
		if (!empty($ticket->fk_place)) {
			$place = new Place($db);
			$place->fetch($ticket->fk_place);
			$message .= $langs->trans("Place") . ': ' . $place->name . "\n";
		}

		$message .= "\n";
		$message .= $langs->transnoentities("Label") . "\t\t\t\t\t\t\t\t\t" . $langs->transnoentities("Qty") . "/" . $langs->transnoentities("Price") . "\t\t" ./*$langs->transnoentities("DiscountLineal")."\t\t".*/
				$langs->transnoentities("Total") . "\n";
		//$ticket->getLinesArray();
		if (!empty($ticket->lines)) {
			//$subtotal=0;
			foreach ($ticket->lines as $line) {
				$espacio = '';
				$totalline = $line->qty * $line->subprice;
				$subtotal = array();
				$subtotaltva = array();
				while (dol_strlen(dol_trunc($line->libelle, 30) . $espacio) < 29) {
					$espacio .= "    \t";
				}
				$message .= dol_trunc($line->libelle, 33) . $espacio;
				$message .= "\t\t" . $line->qty . " * " . price($line->total_ttc / $line->qty) . "\t\t" ./*$line->remise_percent."%\t\t\t".*/
						price($line->total_ttc) . ' ' . $langs->trans(currency_name($conf->currency)) . "\n";
				$subtotal[$line->tva_tx] += $line->total_ht;;
				$subtotaltva[$line->tva_tx] += $line->total_tva;
				if (!empty($line->total_localtax1)) {
					$localtax1 = $line->localtax1_tx;
				}
				if (!empty($line->total_localtax2)) {
					$localtax2 = $line->localtax2_tx;
				}
			}
		} else {
			$message .= $langs->transnoentities("ErrNoArticles") . "\n";
		}
		$message .= $langs->transnoentities("TotalTTC") . ":\t" . price($ticket->total_ttc) . " " . $langs->trans(currency_name($conf->currency)) . "\n";

		$message .= '\n' . $langs->trans("TotalHT") . "\t" . $langs->trans("VAT") . "\t" . $langs->trans("TotalVAT") . "\n";

		if (!empty($subtotal)) {
			foreach ($subtotal as $totkey => $totval) {
				$message .= price($subtotal[$totkey]) . "\t\t\t" . price($totkey) . "%\t" . price($subtotaltva[$totkey]) . "\n";
			}
		}
		$message .= "-------------------------------\n";
		$message .= price($ticket->total_ht) . "\t\t\t----\t" . price($ticket->total_tva) . "\n";
		if ($ticket->total_localtax1 != 0) {
			$message .= $langs->transcountrynoentities("TotalLT1",
							$mysoc->country_code) . " " . price($localtax1) . "%\t" . price($ticket->total_localtax1) . " " . $langs->trans(currency_name($conf->currency)) . "\n";
		}
		if ($ticket->total_localtax2 != 0) {
			$message .= $langs->transcountrynoentities("TotalLT2",
							$mysoc->country_code) . " " . price($localtax2) . "%\t" . price($ticket->total_localtax2) . " " . $langs->trans(currency_name($conf->currency)) . "\n";
		}

		$message .= "\n\n";

		$terminal = new Cash($db);
		$terminal->fetch($ticket->fk_cash);

		$pay = $ticket->getSommePaiement();

		if ($ticket->customer_pay > $pay) {
			$pay = $ticket->customer_pay;
		}


		$diff_payment = $ticket->total_ttc - $pay;
		$listofpayments = $ticket->getListOfPayments();
		foreach ($listofpayments as $paym) {
			if ($paym['type'] != 'LIQ') {
				$message .= $terminal->select_Paymentname(dol_getIdFromCode($db, $paym['type'],
								'c_paiement')) . "\t" . price($paym['amount']) . " " . $langs->trans(currency_name($conf->currency)) . "\n";
			} else {
				$message .= $terminal->select_Paymentname(dol_getIdFromCode($db, $paym['type'],
								'c_paiement')) . "\t" . price($paym['amount'] - ($diff_payment < 0 ? $diff_payment : 0)) . " " . $langs->trans(currency_name($conf->currency)) . "\n";
			}
		}

		$message .= ($diff_payment < 0 ? $langs->trans("CustomerRet") : $langs->trans("CustomerDeb")) . "\t" . price(abs($diff_payment)) . " " . $langs->trans(currency_name($conf->currency)) . "\n";

		$message .= $conf->global->POS_PREDEF_MSG;
		return $message;
	}

	/**
	 * Fill the body of email's message with a facture
	 *
	 * @param int $id
	 *
	 * @return string        String with ticket data
	 */
	public static function FillMailFactureBody($id)
	{
		global $db, $conf, $langs;

		$facture = new Facture($db);
		$res = $facture->fetch($id);
		$facture->fetch_thirdparty();
		$userstatic = new User($db);
		$userstatic->fetch($facture->user_valid);


		$sql = "SELECT label, topic, content, lang";
		$sql .= " FROM " . MAIN_DB_PREFIX . 'c_email_templates';
		$sql .= " WHERE type_template='facture_send'";
		$sql .= " AND entity IN (" . getEntity("c_email_templates") . ")";
		$sql .= " AND (fk_user is NULL or fk_user = 0 or fk_user = " . $userstatic->id . ")";
		$sql .= $db->order("lang,label", "ASC");
		//print $sql;

		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);    // Get first found
			if ($obj) {
				$defaultmessage = $obj->content;
			} else {
				$langs->load("other");
				$defaultmessage = $langs->transnoentities("PredefinedMailContentSendInvoice");
			}

			$db->free($resql);
		}

		$substit['__FACREF__'] = $facture->ref;
		$substit['__REF__'] = $facture->ref;
		$substit['__SIGNATURE__'] = $userstatic->signature;
		$substit['__REFCLIENT__'] = $facture->ref_client;
		$substit['__THIRPARTY_NAME__'] = $facture->thirdparty->name;
		$substit['__PROJECT_REF__'] = (is_object($facture->projet) ? $facture->projet->ref : '');
		$substit['__PERSONALIZED__'] = '';
		$substit['__CONTACTCIVNAME__'] = '';

		// Find the good contact adress
		$custcontact = '';
		$contactarr = $facture->liste_contact(-1, 'external');

		if (is_array($contactarr) && count($contactarr) > 0) {
			foreach ($contactarr as $contact) {
				if ($contact['libelle'] == $langs->trans('TypeContact_facture_external_BILLING')) {    // TODO Use code and not label

					require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

					$contactstatic = new Contact($db);
					$contactstatic->fetch($contact ['id']);
					$custcontact = $contactstatic->getFullName($langs, 1);
				}
			}

			if (!empty($custcontact)) {
				$substit['__CONTACTCIVNAME__'] = $custcontact;
			}
		}

		// Complete substitution array
		if (!empty($conf->paypal->enabled) && !empty($conf->global->PAYPAL_ADD_PAYMENT_URL)) {
			require_once DOL_DOCUMENT_ROOT . '/paypal/lib/paypal.lib.php';

			$langs->load('paypal');

			if ($facture->param["models"] == 'facture_send') {
				$url = getPaypalPaymentUrl(0, 'invoice', $substit['__FACREF__']);
				$substit['__PERSONALIZED__'] = str_replace('\n', "\n",
						$langs->transnoentitiesnoconv("PredefinedMailContentLink", $url));
			}
		}

		$defaultmessage = str_replace('\n', "\n", $defaultmessage);

		// Deal with format differences between message and signature (text / HTML)
		if (dol_textishtml($defaultmessage) && !dol_textishtml($substit['__SIGNATURE__'])) {
			$substit['__SIGNATURE__'] = dol_nl2br($substit['__SIGNATURE__']);
		} else {
			if (!dol_textishtml($defaultmessage) && dol_textishtml($substit['__SIGNATURE__'])) {
				$defaultmessage = dol_nl2br($defaultmessage);
			}
		}

		$defaultmessage = make_substitutions($defaultmessage, $substit);
		// Clean first \n and br (to avoid empty line when CONTACTCIVNAME is empty)
		$defaultmessage = preg_replace("/^(<br>)+/", "", $defaultmessage);
		$defaultmessage = preg_replace("/^\n+/", "", $defaultmessage);

		return $defaultmessage;
	}

	/**
	 * Fill the body of email's message with a close cash
	 *
	 * @param int $id
	 *
	 * @return string        String with ticket data
	 */
	public static function FillMailCloseCashBody($id)
	{
		global $db, $conf, $langs;

		$sql = "select fk_user, date_c, fk_cash, ref";
		$sql .= " from " . MAIN_DB_PREFIX . "pos_control_cash";
		$sql .= " where rowid = " . $id;
		$result = $db->query($sql);

		if ($result) {
			$objp = $db->fetch_object($result);
			$date_end = $objp->date_c;
			$fk_user = $objp->fk_user;
			$terminal = $objp->fk_cash;
			$ref = $objp->ref;
		}

		$sql = "select date_c";
		$sql .= " from " . MAIN_DB_PREFIX . "pos_control_cash";
		$sql .= " where fk_cash = " . $terminal . " AND date_c < '" . $date_end . "' AND type_control = 1";
		$sql .= " ORDER BY date_c DESC";
		$sql .= " LIMIT 1";
		$result = $db->query($sql);

		if ($result) {
			$objd = $db->fetch_object($result);
			$date_start = $objd->date_c;
		}

		$message = $conf->global->MAIN_INFO_SOCIETE_NOM . " \n" . $conf->global->MAIN_INFO_SOCIETE_ADRESSE . " \n" . $conf->global->MAIN_INFO_SOCIETE_CP . ' ' . $conf->global->MAIN_INFO_SOCIETE_VILLE . " \n\n";
		$message .= $langs->transnoentities("CloseCashReport") . ': ' . $ref . "\n";
		$cash = new Cash($db);
		$cash->fetch($terminal);
		$message .= $langs->transnoentities("Terminal") . ': ' . $cash->name . "\n";

		$userstatic = new User($db);
		$userstatic->fetch($fk_user);
		$message .= $langs->transnoentities("User") . ': ' . $userstatic->firstname . ' ' . $userstatic->lastname . "\n";
		$message .= dol_print_date($db->jdate($date_end), 'dayhourtext') . "\n\n";

		$message .= $langs->transnoentities("TicketsCash") . "\n";
		$message .= $langs->transnoentities("Ticket") . "\t\t\t\t\t" . $langs->transnoentities("Total") . "\n";

		$sql = "SELECT t.ticketnumber, p.amount, t.type";
		$sql .= " FROM " . MAIN_DB_PREFIX . "pos_ticket as t, " . MAIN_DB_PREFIX . "pos_paiement_ticket as pt, " . MAIN_DB_PREFIX . "paiement as p";
		$sql .= " WHERE t.fk_cash=" . $terminal . " AND p.fk_paiement=" . $cash->fk_modepaycash . " AND t.fk_statut > 0 AND p.datep > '" . $date_start . "' AND p.datep < '" . $date_end . "'";
		$sql .= " AND p.rowid = pt.fk_paiement AND t.rowid = pt.fk_ticket ";

		$sql .= " UNION SELECT f.facnumber, p.amount, f.type";
		$sql .= " FROM " . MAIN_DB_PREFIX . "pos_facture as pf," . MAIN_DB_PREFIX . "facture as f, " . MAIN_DB_PREFIX . "paiement_facture as pfac, " . MAIN_DB_PREFIX . "paiement as p ";
		$sql .= " WHERE pf.fk_cash=" . $terminal . " AND p.fk_paiement=" . $cash->fk_modepaycash . " AND pf.fk_facture = f.rowid and f.fk_statut > 0 AND p.datep > '" . $date_start . "' AND p.datep < '" . $date_end . "'";
		$sql .= " AND p.rowid = pfac.fk_paiement AND f.rowid = pfac.fk_facture";

		$result = $db->query($sql);

		if ($result) {
			$num = $db->num_rows($result);
			if ($num > 0) {
				$i = 0;
				$subtotalcash = 0;
				while ($i < $num) {
					$objp = $db->fetch_object($result);

					$message .= $objp->ticketnumber . "\t\t" . price($objp->amount) . "\n";
					$i++;
					$subtotalcash += $objp->amount;
				}
			} else {
				$message .= $langs->transnoentities("NoTickets") . "\n";
			}
		}

		$message .= $langs->trans("TotalCash") . "\t" . price($subtotalcash) . " " . $langs->trans(currency_name($conf->currency)) . "\n";
		$message .= $langs->trans("TicketsCreditCard") . "\n";

		$message .= $langs->trans("Ticket") . "\t\t" . $langs->trans("Total") . "\n";

		// Credit card
		$sql = "SELECT t.ticketnumber, p.amount, t.type";
		$sql .= " FROM " . MAIN_DB_PREFIX . "pos_ticket as t, " . MAIN_DB_PREFIX . "pos_paiement_ticket as pt, " . MAIN_DB_PREFIX . "paiement as p";
		$sql .= " WHERE t.fk_cash=" . $terminal . " AND (p.fk_paiement=" . $cash->fk_modepaybank . " OR p.fk_paiement=" . $cash->fk_modepaybank_extra . ")AND t.fk_statut > 0 AND p.datep > '" . $date_start . "' AND p.datep < '" . $date_end . "'";
		$sql .= " AND p.rowid = pt.fk_paiement AND t.rowid = pt.fk_ticket ";

		$sql .= " UNION SELECT f.facnumber, p.amount, f.type";
		$sql .= " FROM " . MAIN_DB_PREFIX . "pos_facture as pf," . MAIN_DB_PREFIX . "facture as f, " . MAIN_DB_PREFIX . "paiement_facture as pfac, " . MAIN_DB_PREFIX . "paiement as p ";
		$sql .= " WHERE pf.fk_cash=" . $terminal . " AND (p.fk_paiement=" . $cash->fk_modepaybank . " OR p.fk_paiement=" . $cash->fk_modepaybank_extra . ") AND pf.fk_facture = f.rowid and f.fk_statut > 0 AND p.datep > '" . $date_start . "' AND p.datep < '" . $date_end . "'";
		$sql .= " AND p.rowid = pfac.fk_paiement AND f.rowid = pfac.fk_facture";

		$result = $db->query($sql);

		if ($result) {
			$num = $db->num_rows($result);
			if ($num > 0) {
				$i = 0;
				$subtotalcard = 0;
				while ($i < $num) {
					$objp = $db->fetch_object($result);

					$message .= $objp->ticketnumber . "\t\t" . price($objp->amount) . "\n";
					$i++;
					$subtotalcard += $objp->amount;
				}
			} else {
				$message .= $langs->transnoentities("NoTickets") . "\n";
			}
		}

		$message .= $langs->trans("TotalCard") . "\t" . price($subtotalcard) . " " . $langs->trans(currency_name($conf->currency)) . "\n";

		if (!empty($conf->rewards->enabled)) {
			$message .= $langs->trans("Points") . "\n";

			$message .= $langs->trans("Ticket");
			"\t\t" . $langs->trans("Total") . "\n";

			$sql = " SELECT f.facnumber, p.amount, f.type";
			$sql .= " FROM " . MAIN_DB_PREFIX . "pos_facture as pf," . MAIN_DB_PREFIX . "facture as f, " . MAIN_DB_PREFIX . "paiement_facture as pfac, " . MAIN_DB_PREFIX . "paiement as p ";
			$sql .= " WHERE pf.fk_cash=" . $terminal . " AND p.fk_paiement= 100 AND pf.fk_facture = f.rowid and f.fk_statut > 0 AND p.datep > '" . $date_start . "' AND p.datep < '" . $date_end . "'";
			$sql .= " AND p.rowid = pfac.fk_paiement AND f.rowid = pfac.fk_facture";

			$result = $db->query($sql);

			if ($result) {
				$num = $db->num_rows($result);
				if ($num > 0) {
					$i = 0;
					$subtotalpoint = 0;
					while ($i < $num) {
						$objp = $db->fetch_object($result);
						$message .= $objp->facnumber . "\t\t" . price($objp->amount) . "\n";
						$i++;
						$subtotalpoint += $objp->amount;
					}
				} else {
					$message .= $langs->transnoentities("NoTickets") . "\n";
				}
			}


			$message .= $langs->trans("TotalPoints") . "\t" . price($subtotalpoint) . " " . $langs->trans(currency_name($conf->currency)) . "\n\n\n";
		}
		/*$sql = "SELECT t.ticketnumber, t.type, l.total_ht, l.tva_tx, l.total_tva, l.total_localtax1, l.total_localtax2, l.total_ttc";
	$sql .=" FROM ".MAIN_DB_PREFIX."pos_ticket as t left join ".MAIN_DB_PREFIX."pos_ticketdet as l on l.fk_ticket= t.rowid";
	$sql .=" WHERE t.fk_control = ".$id." AND t.fk_cash=".$terminal." AND t.fk_statut > 0";

	$sql .= " UNION SELECT f.facnumber, f.type, fd.total_ht, fd.tva_tx, fd.total_tva, fd.total_localtax1, fd.total_localtax2, fd.total_ttc";
	$sql .=" FROM ".MAIN_DB_PREFIX."pos_facture as pf,".MAIN_DB_PREFIX."facture as f left join ".MAIN_DB_PREFIX."facturedet as fd on fd.fk_facture= f.rowid";
	$sql .=" WHERE pf.fk_control_cash = ".$id." AND pf.fk_cash=".$terminal." AND pf.fk_facture = f.rowid and f.fk_statut > 0";

	$result=$db->query($sql);

	if ($result)
	{
		$num = $db->num_rows($result);
		if($num>0)
		{
			$i = 0;
			$subtotalcardht=0;
			while ($i < $num)
			{
				$objp = $db->fetch_object($result);
				$i++;
				if($objp->type == 1){
					$objp->total_ht= $objp->total_ht * -1;
					$objp->total_tva= $objp->total_tva * -1;
					$objp->total_ttc= $objp->total_ttc * -1;
					$objp->total_localtax1= $objp->total_localtax1 * -1;
					$objp->total_localtax2= $objp->total_localtax2 * -1;
				}

				$subtotalcardht+=$objp->total_ht;
				$subtotalcardtva[$objp->tva_tx] += $objp->total_tva;
				$subtotalcardttc += $objp->total_ttc;
				$subtotalcardlt1 += $objp->total_localtax1;
				$subtotalcardlt2 += $objp->total_localtax2;
			}
		}

	}
	$message .= "------------------\n";
	if(! empty($subtotalcardht))$message .= $langs->trans("TotalHT")."\t".price($subtotalcardht)." ".$langs->trans(currency_name($conf->currency))."\n";
	if(! empty($subtotalcardtva)){
		foreach($subtotalcardtva as $tvakey => $tvaval){
			if($tvakey > 0)
				$message .= $langs->trans("TotalVAT").' '.round($tvakey).'%'."\t".price($tvaval)." ".$langs->trans(currency_name($conf->currency))."\n";
		}
	}
	if($subtotalcardlt1)
		$message .= $langs->transcountrynoentities("TotalLT1",$mysoc->country_code)."\t".price($subtotalcardlt1)." ".$langs->trans(currency_name($conf->currency))."\n";
	if($subtotalcardlt2)
		$message .= $langs->transcountrynoentities("TotalLT2",$mysoc->country_code)."\t".price($subtotalcardlt2)." ".$langs->trans(currency_name($conf->currency))."\n";

	$message .= $langs->trans("TotalPOS")."\t".price($subtotalcardttc)." ".$langs->trans(currency_name($conf->currency))."\n";
	*/
		return $message;
	}


	/**
	 * Send mail with ticket data
	 * @param  $email
	 * @return int            <0 if KO; >0 if OK
	 */
	public static function sendMail($email)
	{
		global $db, $conf, $langs;
		$function = "sendMail";


		require_once(DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php');
		if ($email["idTicket"]) {
			$ticket = new Ticket($db);
			$ticket->fetch($email["idTicket"]);
			$subject = $conf->global->MAIN_INFO_SOCIETE_NOM . ': ' . $langs->trans("CopyOfTicket") . ' ' . $ticket->ticketnumber;
			$message = self::FillMailTicketBody($ticket->id);
		}
		if ($email["idFacture"]) {
			$facture = new Facture($db);
			$facture->fetch($email["idFacture"]);
			$subject = $conf->global->MAIN_INFO_SOCIETE_NOM . ': ' . $langs->trans("CopyOfFacture") . ' ' . $facture->ref;
			$message = self::FillMailFactureBody($facture->id);

			$ref = dol_sanitizeFileName($facture->ref);
			include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
			$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $ref, preg_quote($ref, '/'));
			$file = $fileparams ['fullname'];

			// Build document if it not exists
			if (!$file || !is_readable($file)) {
				$result = $facture->generateDocument($facture->modelpdf, $langs,
						(!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0),
						(!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0),
						(!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));
				if ($result <= 0) {
					dol_print_error($db, $result);
					exit();
				}
				$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $ref, preg_quote($ref, '/'));
				$file = $fileparams ['fullname'];
			}

		}
		if ($email["idCloseCash"]) {
			$subject = $conf->global->MAIN_INFO_SOCIETE_NOM . ': ' . $langs->trans("CopyOfCloseCash") . ' ' . $email["idCloseCash"];
			$message = self::FillMailCloseCashBody($email["idCloseCash"]);
		}
		$from = $conf->global->MAIN_INFO_SOCIETE_NOM . "<" . $conf->global->MAIN_MAIL_EMAIL_FROM . ">";
		if (!empty($file)) {

			$filename_list[] = $file;
			$mimetype_list[] = dol_mimetype($file);
			$mimefilename_list[] = basename($file);

		}


		$mailfile = new CMailFile($subject, $email["mail_to"], $from, $message, $filename_list, $mimetype_list,
				$mimefilename_list);
		if ($mailfile->error) {
			$mesg = '<div class="error">' . $mailfile->error . '</div>';
			$res = -1;
		} else {
			$res = $mailfile->sendfile();
		}

		return ErrorControl($res, $function);
	}

	/**
	 *    Delete ticket
	 * @param        int $idTicket Id of ticket to delete
	 * @return        int                    <0 if KO, >0 if OK
	 */
	public static function Delete_Ticket($idTicket)
	{
		global $db;

		$function = "deleteTicket";

		$object = new Ticket($db);
		$object->fetch($idTicket);
		$db->begin;
		$res = $object->delete_ticket();

		if ($res) {
			$db->commit();
		} else {
			$db->rollback();
		}

		return ErrorControl($res, $function);
	}

	public static function calculePrice($product)
	{
		global $mysoc, $conf, $db;
		require_once(DOL_DOCUMENT_ROOT . "/core/lib/price.lib.php");
		$qty = $product["cant"];
		if ($product["price_base_type"] == "HT") {
			$pu = $product["price_ht"];
		} else {
			$pu = $product["price_ttc"];
		}
		$remise_percent_ligne = $product["discount"] ? $product["discount"] : 0;
		$txtva = $product["tva_tx"];
		$uselocaltax1_rate = $product["localtax1_tx"] > 0 ? $product["localtax1_tx"] : 0;
		$uselocaltax2_rate = $product["localtax2_tx"] > 0 ? $product["localtax2_tx"] : 0;
		$remise_percent_global = $product["remise_percent_global"] ? $product["remise_percent_global"] : 0;
		$price_base_type = $product["price_base_type"];
		$type = $product["fk_product_type"] ? $product["fk_product_type"] : 0;
		$info_bits = 0;
		$remise_percent_ligne = $remise_percent_global + $remise_percent_ligne;
		$remise_percent_global = 0;

		if ($product["buyer"] > 0){
			dol_include_once('/societe/class/societe.class.php');
			$buyer = new Societe($db);
			$buyer->fetch($product["buyer"]);
		}

		$localtaxes_type = getLocalTaxesFromRate($txtva, 0, $buyer,$mysoc);
		if ($conf->discounts->enabled) {
			dol_include_once('/discounts/lib/discounts.lib.php');
			dol_include_once('/discounts/class/discounts.class.php');
			$res = calcul_discount_pos($product);
			$promo_price = $res;
			if (!empty($promo_price)) {
				$pu = $promo_price;
				$price_base_type = 'HT';
				$result["is_promo"] = 1;
				$result["promo_desc"] = $res['desc'];
			} else {
				$result["is_promo"] = 0;
			}
		}

		$tabprice = calcul_price_total($qty, $pu, $remise_percent_ligne, $txtva, $uselocaltax1_rate, $uselocaltax2_rate,
				$remise_percent_global, $price_base_type, $info_bits, $type, $mysoc, $localtaxes_type);

		if (isset($conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND)) {
			$tmpvat = price2num($tabprice[0] * $txtva / 100, 'MT', 1);
			$diff = price2num($tabprice[1] - $tmpvat, 'MT', 1);
			if ($diff) {
				$tabprice[1] -= $diff;
				$tabprice[2] -= $diff;
			}
		}

		$result["total_ht"] = $tabprice[0];
		$result["total_tva"] = $tabprice[1];
		$result["total_ttc"] = $tabprice[2];
		$result["total_localtax1"] = $tabprice[9];
		$result["total_localtax2"] = $tabprice[10];
		$result["pu_ht"] = $tabprice[3];
		$result["pu_tva"] = $tabprice[4];
		$result["pu_ttc"] = $tabprice[5];
		$result["total_ht_without_discount"] = $tabprice[6];
		$result["total_ttc_without_discount"] = $tabprice[8];
		$result["orig_price"] = $product["orig_price"];//2Promo

		if ($price_base_type == "TTC") {
			$tabprice = calcul_price_total($qty, $result["pu_ht"], $remise_percent_ligne, $txtva, $uselocaltax1_rate,
					$uselocaltax2_rate, $remise_percent_global, "HT", $info_bits, $type, $mysoc, $localtaxes_type);
			$result["total_ht"] = $tabprice[0];
			$result["total_tva"] = $tabprice[1];
			$result["total_ttc"] = $tabprice[2];
			$result["total_localtax1"] = $tabprice[9];
			$result["total_localtax2"] = $tabprice[10];
			$result["pu_ht"] = $tabprice[3];
			$result["pu_tva"] = $tabprice[4];
			$result["pu_ttc"] = $tabprice[5];
			$result["total_ht_without_discount"] = $tabprice[6];
			$result["total_ttc_without_discount"] = $tabprice[8];
			$result["orig_price"] = $product["orig_price"];//2Promo
		}
		return $result;
	}

	public static function calculePriceTotal($ticket)
	{
		global $mysoc, $db, $conf;
		if (isset($conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND)) {
			require_once(DOL_DOCUMENT_ROOT . "/core/lib/price.lib.php");

			$total_ht = 0;
			$total_tva = 0;
			$total_localtax1 = 0;
			$total_localtax2 = 0;
			$total_ttc = 0;
			$total_ht_by_vats = array();
			$total_tva_by_vats = array();

			dol_include_once('/societe/class/societe.class.php');
			$customer = new Societe($db);
			$customer->fetch($ticket['customerId']);

			foreach ($ticket['lines'] as $line) {

				$localtaxes_type = getLocalTaxesFromRate($line['tva_tx'], 0, $customer, $mysoc);
				$remise_percent_ligne = $line['remise_percent_global'] + $line['discount'];

				$tabprice = calcul_price_total($line['cant'], $line['price_ht'], $remise_percent_ligne, $line['tva_tx'],
					$line['localtax1_tx'], $line['localtax2_tx'],
					0, 'HT', 0, $line['fk_product_type'], $mysoc, $localtaxes_type);

				$total_ht += $tabprice[0];        // The only field visible at end of line detail
				$total_tva += $tabprice[1];
				$total_localtax1 += $tabprice[9];
				$total_localtax2 += $tabprice[10];
				$total_ttc += $tabprice[2];

				if (!isset($total_ht_by_vats[$line['tva_tx']])) {
					$total_ht_by_vats[$line['tva_tx']] = 0;
				}
				if (!isset($total_tva_by_vats[$line['tva_tx']])) {
					$total_tva_by_vats[$line['tva_tx']] = 0;
				}

				$total_ht_by_vats[$line['tva_tx']] += $tabprice[0];
				$total_tva_by_vats[$line['tva_tx']] += $tabprice[1];


				$tmpvat = price2num($total_ht_by_vats[$line['tva_tx']] * $line['tva_tx'] / 100, 'MT', 1);
				$diff = price2num($total_tva_by_vats[$line['tva_tx']] - $tmpvat, 'MT', 1);
				if ($diff) {
					$total_ttc -= $diff;
					$total_tva_by_vats[$line['tva_tx']] -= $diff;
				}

			}

			$result['total'] = $total_ttc;
		}
		else {
			$result['total'] = $ticket['total'];
		}
		return $result;
	}

	public static function getLocalTax($data)
	{
		global $db;
		require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions.lib.php");
		$customer = new Societe($db);
		$customer->fetch($data["customer"]);
		$localtax['1'] = get_localtax($data["tva"], 1, $customer);
		$localtax['2'] = get_localtax($data["tva"], 2, $customer);
		return $localtax;
	}

	public static function getNotes($mode)
	{
		global $db;

		$ret = -1;
		$function = "GetNotes";
		if ($mode) {
			$sql = 'SELECT f.rowid as ticketid, f.ticketnumber, fd.description, f.note as ticketNote, fd.note as lineNote';
		} else {
			$sql = 'SELECT count(*)';
		}
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'pos_ticket as f';
		$sql .= ', ' . MAIN_DB_PREFIX . 'pos_ticketdet as fd';
		$sql .= ' WHERE f.fk_statut = 0';
		$sql .= ' AND f.rowid = fd.fk_ticket';
		$sql .= ' AND (f.note is not null';
		$sql .= ' OR fd.note is not null)';
		if ($mode == 0) {
			$sql .= 'GROUP BY f.ticketnumber';
		}

		$res = $db->query($sql);
		if ($res) {
			$num = $db->num_rows($res);
			if ($mode) {
				$i = 0;
				$j = 0;
				$id = 0;
				while ($i < $num) {
					$obj = $db->fetch_object($res);

					if ($id != $obj->ticketid) {
						$id = $obj->ticketid;
						$tickets[$j]["id"] = $j;
						$tickets[$j]["ticketid"] = $obj->ticketid;
						$tickets[$j]["ticketnumber"] = $obj->ticketnumber;
						$tickets[$j]["description"] = '';
						$tickets[$j]["note"] = $obj->ticketNote ? $obj->ticketNote : '';
						$j++;
					}
					if ($obj->lineNote) {
						$tickets[$j]["id"] = $j;
						$tickets[$j]["ticketid"] = $obj->ticketid;
						$tickets[$j]["ticketnumber"] = '';
						$tickets[$j]["description"] = $obj->description;
						$tickets[$j]["note"] = $obj->lineNote;
						$j++;
					}

					$i++;
				}
				return $tickets;
			} else {
				return $num;
			}

		} else {
			return ErrorControl($ret, $function);
		}
	}

	/**
	 *  Return list of all warehouses
	 *
	 * @param    int $status Status
	 * @return array                Array list of warehouses
	 */
	public static function getWarehouse($status = 1)
	{
		global $db;
		$liste = array();

		$sql = "SELECT rowid, lieu";
		$sql .= " FROM " . MAIN_DB_PREFIX . "entrepot";
		$sql .= " WHERE entity IN (" . getEntity('stock', 1) . ")";
		$sql .= " AND statut = " . $status;

		$result = $db->query($sql);
		$i = 0;
		$num = $db->num_rows($result);
		if ($result) {
			while ($i < $num) {
				$row = $db->fetch_row($result);
				$liste[$i]["id"] = $row[0];
				$liste[$i]["lieu"] = $row[1];
				$i++;
			}
			$db->free($result);
		}
		return $liste;
	}

	/**
	 *    Reconstruit l'arborescence des categories sous la forme d'un tableau
	 *    Renvoi un tableau de tableau('id','id_mere',...) trie selon arbre et avec:
	 *                id = id de la categorie
	 *                id_mere = id de la categorie mere
	 *                id_children = tableau des id enfant
	 *                label = nom de la categorie
	 *                fulllabel = nom avec chemin complet de la categorie
	 *                fullpath = chemin complet compose des id
	 *
	 * @param      string $type        Type of categories (0=product, 1=suppliers, 2=customers, 3=members)
	 * @param      int    $markafterid Mark all categories after this leaf in category tree.
	 * @return        array|int                      Array of categories
	 */
	public function get_full_arbo($type)
	{
		global $db;

		$categorie = new Categorie($db);

		$categorie->cats = array();

		// Init $this->cats array
		$sql = "SELECT DISTINCT c.rowid, c.label, c.description, c.fk_parent";    // Distinct reduce pb with old tables with duplicates
		$sql .= " FROM " . MAIN_DB_PREFIX . "categorie as c";
		$sql .= " WHERE c.entity IN (" . getEntity('category', 1) . ")";
		$sql .= " AND c.type = " . $type;
		$sql .= " AND fk_parent = 0";

		dol_syslog(get_class($categorie) . "::get_full_arbo get category list sql=" . $sql, LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			while ($obj = $db->fetch_object($resql)) {
				$categorie->cats[$obj->rowid]['rowid'] = $obj->rowid;
				$categorie->cats[$obj->rowid]['id'] = $obj->rowid;
				$categorie->cats[$obj->rowid]['fk_parent'] = $obj->fk_parent;
				$categorie->cats[$obj->rowid]['label'] = $obj->label;
				$categorie->cats[$obj->rowid]['description'] = $obj->description;
				$i++;
			}
		} else {
			dol_print_error($db);
			return -1;
		}

		// We add the fullpath property to each elements of first level (no parent exists)
		dol_syslog(get_class($categorie) . "::get_full_arbo call to build_path_from_id_categ", LOG_DEBUG);
		foreach ($categorie->cats as $key => $val) {
			$categorie->build_path_from_id_categ($key,
					0);    // Process a branch from the root category key (this category has no parent)
		}

		dol_syslog(get_class($categorie) . "::get_full_arbo dol_sort_array", LOG_DEBUG);
		$categorie->cats = dol_sort_array($categorie->cats, 'fulllabel', 'asc', true, false);

		//$this->debug_cats();

		return $categorie->cats;
	}

    /**
     *    Return list of contents of a category
     *
     * @param $idCat
     * @param $more
     * @param $ticketstate
     * @return array|int
     * @internal param string $field Field name for select in table. Full field name will be fk_field.
     * @internal param string $classname PHP Class of object to store entity
     * @internal param string $category_table Table name for select in table. Full table name will be PREFIX_categorie_table.
     * @internal param string $object_table Table name for select in table. Full table name will be PREFIX_table.
     */
	public static function get_prod($idCat, $more, $ticketstate)
	{
		global $db, $conf;
		$objs = array();

		$sql = "SELECT o.rowid as id, o.ref, o.label, o.description, ";
		$sql .= " o.fk_product_type";
		$sql .= " FROM " . MAIN_DB_PREFIX . "categorie_product as c";
		$sql .= ", " . MAIN_DB_PREFIX . "product as o";
		if ($conf->global->POS_STOCK || $ticketstate == 1) {
			$sql .= " WHERE o.entity IN (" . getEntity("product", 1) . ")";
			$sql .= " AND c.fk_categorie = " . $idCat;
			$sql .= " AND c.fk_product = o.rowid";
			$sql .= " AND o.tosell = 1";
			if (!$conf->global->POS_SERVICES) {
				$sql .= " AND o.fk_product_type = 0";
			}
		} else {
			$cashid = $_SESSION['TERMINAL_ID'];
			$cash = new Cash($db);
			$cash->fetch($cashid);
			$warehouse = $cash->fk_warehouse;

			$sql .= ", " . MAIN_DB_PREFIX . "product_stock as ps";
			$sql .= " WHERE o.entity IN (" . getEntity("product", 1) . ")";
			$sql .= " AND c.fk_categorie = " . $idCat;
			$sql .= " AND c.fk_product = o.rowid";
			$sql .= " AND o.tosell = 1";
			$sql .= " AND o.rowid = ps.fk_product";
			$sql .= " AND ps.fk_entrepot = " . $warehouse;
			$sql .= " AND ps.reel > 0";
			if ($conf->global->POS_SERVICES) {
				$sql .= " union select o.rowid as id, o.ref, o.label, o.description,	";
				$sql .= " o.fk_product_type";
				$sql .= " FROM " . MAIN_DB_PREFIX . "categorie_product as c,";
				$sql .= MAIN_DB_PREFIX . "product as o";
				$sql .= " where c.fk_categorie = " . $idCat;
				$sql .= " AND c.fk_product = o.rowid";
				$sql .= " AND o.tosell = 1";
				$sql .= " AND fk_product_type=1";
			}
		}
		$sql.= ' ORDER BY label';
		if ($more >= 0) {
			$sql .= " LIMIT " . $more . ",10 ";
		}

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;

			while ($i < $num) {
				$objp = $db->fetch_object($resql);

				$objs[$objp->label]["id"] = $objp->id;
				$objs[$objp->label]["ref"] = $objp->ref;
				$objs[$objp->label]["label"] = $objp->label;
				$objs[$objp->label]["description"] = $objp->description;
				$objs[$objp->label]["type"] = $objp->fk_product_type;

				$objs[$objp->label]["image"] = self::getImageProduct($objp->id, false);
				$objs[$objp->label]["thumb"] = self::getImageProduct($objp->id, true);
				$i++;
			}
			return $objs;
		} else {
			return -1;
		}
	}

	public static function checkPassword($login, $password, $userid)
	{
		dol_include_once('/pos/class/auth.class.php');
		global $db;
		$function = "checkPassword";

		$auth = new Auth($db);
		$res = $auth->verif($login, $password);

		if ($res >= 0) {
			$_SESSION['uid'] = $userid;
			$_SESSION['uname'] = $login;

			$sql = "UPDATE " . MAIN_DB_PREFIX . "pos_cash";
			$sql .= " SET fk_user_u = " . $_SESSION['uid'];
			$sql .= " WHERE is_used = 1 AND rowid = " . $_SESSION["TERMINAL_ID"];

			$resql = $db->query($sql);
		}

		return ErrorControl($res, $function);
	}

	public static function checkTerminal()
	{
		global $conf, $db;
		$sql = "SELECT rowid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "pos_cash";
		$sql .= " WHERE entity = " . $conf->entity;
		$sql .= " AND is_used = 1 AND rowid = " . $_SESSION["TERMINAL_ID"] . " AND fk_user_u = " . $_SESSION['uid'];

		$resql = $db->query($sql);

		$num = $db->num_rows($resql);

		return $num;
	}

	public static function searchCoupon($customerId)
	{
		global $db, $langs;
        $coupon=array();

		$sql = "SELECT rc.rowid, rc.amount_ttc,";
		$sql .= "  rc.description, fa.facnumber as ref";
		$sql .= " FROM  " . MAIN_DB_PREFIX . "societe_remise_except as rc";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture as fa ON rc.fk_facture_source = fa.rowid";
		$sql .= " WHERE rc.fk_soc =" . $customerId;
		$sql .= " AND (rc.fk_facture_line IS NULL AND rc.fk_facture IS NULL)";
		$sql .= " ORDER BY rc.datec DESC";

		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$coupon[$i]['id'] = $obj->rowid;
				$coupon[$i]['amount_ttc'] = $obj->amount_ttc;

                if (preg_match('/\(CREDIT_NOTE\)/',$obj->description))
                {
                    $coupon[$i]['description'] = preg_replace('/\(CREDIT_NOTE\)/',$langs->trans("CreditNote"),$obj->description).' '.$obj->ref;
                }
                elseif (preg_match('/\(DEPOSIT\)/',$obj->description))
                {
                    $coupon[$i]['description'] = preg_replace('/\(DEPOSIT\)/',$langs->trans("InvoiceDeposit"),$obj->description).' '.$obj->ref;
                }
                else
                {
                    $coupon[$i]['description'] = $obj->description;
                }

				$i++;
			}
		}
		return $coupon;
	}

	public static function addPrint($addprint)
	{
		require_once(DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php");
		global $db, $conf;

		$res = dolibarr_set_const($db, "POS_PENDING_PRINT", $conf->global->POS_PENDING_PRINT . $addprint . ',',
				'chaine', 0, '');

		return $res;
	}

	public static function getSeries()
    {
        global $conf, $db;

        if ($conf->numberseries->enabled && $conf->global->NUMBERSERIES_POS) {
            $sql = "SELECT rowid, ref FROM " . MAIN_DB_PREFIX . "numberseries WHERE typedoc = 1 AND entity = " . $conf->entity . " ORDER BY defaultserie DESC";
            $resql = $db->query($sql);
            if ($resql) {
                $series = array();
                $i = 0;
                while ($obj = $db->fetch_object($resql)) {
                    $series[$i]['rowid'] = $obj->rowid;
                    $series[$i]['ref'] = $obj->ref;
                    $i++;
                }
                return $series;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

	public static function searchBatch($prodid,$batch){
		global $db, $conf;

		$ret = array();

		$function = "SearchBatch";

		if (dol_strlen($batch) <= $conf->global->COMPANY_USE_SEARCH_TO_SELECT) {
			return ErrorControl(-2, $function);
		}

		$cash = new Cash($db);
		$terminal = $_SESSION['TERMINAL_ID'];
		$cash->fetch($terminal);
		$warehouse = $cash->fk_warehouse;

		$sql = "SELECT ps.rowid as product_stock_id";
		$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
		$sql.= " ".MAIN_DB_PREFIX."product_stock as ps";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = ps.fk_product";
		$sql.= " WHERE ps.reel != 0";
		$sql.= " AND ps.fk_entrepot = e.rowid";
		$sql.= " AND e.entity IN (".getEntity('stock').")";
		$sql.= " AND ps.fk_product = ".$prodid;
		$sql.= " AND e.rowid = ".$warehouse;

		$resql=$db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);

			$i = 0;
			$n = 0;

			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$details = Productbatch::findAll($db, $obj->product_stock_id, 1, $prodid);
				if ($details < 0) dol_print_error($db);
				foreach ($details as $pdluo) {
					if($pdluo->qty>0 && strpos($pdluo->batch, $batch)!==false)
					{
						$ret[$n]["id"] = $pdluo->id;
						$ret[$n]["batch"] = $pdluo->batch;
						$ret[$n]["sellby"]= $pdluo->sellby?dol_print_date($pdluo->sellby):'';
						$ret[$n]["eatby"]= $pdluo->eatby?dol_print_date($pdluo->eatby):'';
						$ret[$n]["stock"] = $pdluo->qty;
						$n++;
					}
				}
				$i++;
			}
		}
		return ErrorControl(count($ret)>0?$ret:-1, $function);
	}

	public static function searchEcotax($prodid)
	{
		global $db;

		$sql = "SELECT ecotaxdeee";
		$sql.= " FROM " . MAIN_DB_PREFIX . "product_extrafields";
		$sql.= " WHERE fk_object = " . $prodid;

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);

			$i = 0;

			if ($num) {
				$obj = $db->fetch_object($resql);
				$ret = (float)$obj->ecotaxdeee;
			}
			return $ret;
		}

		 else  {
			return 0;
		}
	}
}

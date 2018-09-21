<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010-2011 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013-2017 Ferran Marcet		<fmarcet@2byte.es>
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
 *      \file       htdocs/facturae/class/facturae.class.php
 *      \ingroup    facturae
 *      \brief      File of construction class of Facturae file
 */

/**
 \class 	facturae
 \brief     Class to generate files acording the Facturae rule
 */

class Facturae
{
	public $db;

	public $id;
	public $prelevement_id;
	public $code; //CORE o COR1
	public $type; //FRST, RCUR, FNAL, OOFF
	public $chargedate;
	public $numlines;
	public $lines;
	public $file;
	public $filename;

	
	
	
	
	/**
     *	Constructor
     *
     *  @param		DoliDB		$db      	Database handler
     */
    public function __construct($db)
    {
        global $conf,$langs;

        $error = 0;
        $this->db = $db;

        $this->prelevement_id = 0;

        $this->type = "";
        $this->reason = "";
        $this->criterio = "";
        
        $this->startdate = "";
        $this->enddate = "";
    }

	public function generateFile($id)
	{
		require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
		require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		dol_include_once("/facturae/lib/facturae.lib.php");
		dol_include_once("/facturae/class/facturaepayments.class.php");
				
		global $conf, $mysoc, $langs;
		
		$langs->load("facturae@facturae");
		
		$thousand=$langs->transnoentitiesnoconv("SeparatorThousand");
		if ($thousand == 'Space') $thousand=' ';	// ' ' does not work on trans method
		if ($thousand == 'None') $thousand='';
		$dec=$langs->transnoentitiesnoconv("SeparatorDecimal");
		
		$error=0;
		
		$msgid = date("YmdHis").$this->prelevement_id;
		
		$object = new Facture($this->db);
		$object->fetch($id);
		$object->fetch_thirdparty();
		$fac_third = new FacturaeThirdparty($this->db);
		$fac_third->fetch($object->socid);
		$CrLf = "\n";
		$this->filename=$conf->facturae->dir_output.'/'.$object->ref.'.xml';
        $this->file = fopen($this->filename,"w");
        
        fwrite($this->file, '<'.'?xml version="1.0" encoding="UTF-8"?'.'>'.$CrLf);
		fwrite($this->file, '<n:Facturae xmlns:n="http://www.facturae.es/Facturae/2009/v3.2/Facturae" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.w3.org/TR/xmldsig-core/xmldsig-core-schema.xsd">'.$CrLf);
        fwrite($this->file, '	<FileHeader>'.$CrLf);
        fwrite($this->file, '		<SchemaVersion>3.2</SchemaVersion>'.$CrLf);
        fwrite($this->file, '		<Modality>I</Modality>'.$CrLf); //Modality I individual, L lotes
		fwrite($this->file, '		<InvoiceIssuerType>EM</InvoiceIssuerType>'.$CrLf); //EM de emisor
		//Aquí debería ir una sección para las que son firmadas por terceros 1.4 Thirdparty
		$pagado = $object->getSommePaiement();
		fwrite($this->file, '		<Batch>'.$CrLf);
		fwrite($this->file, '			<BatchIdentifier>'.$mysoc->idprof1.$object->ref.'</BatchIdentifier>'.$CrLf);
		fwrite($this->file, '			<InvoicesCount>1</InvoicesCount>'.$CrLf);
		fwrite($this->file, '			<TotalInvoicesAmount>'.$CrLf);
		fwrite($this->file, '				<TotalAmount>'.number_format($object->total_ttc,2,'.','').'</TotalAmount>'.$CrLf); //Total de la factura independientemente de anticipos, subvenciones, retenciones, etc.
		fwrite($this->file, '			</TotalInvoicesAmount>'.$CrLf);
		fwrite($this->file, '			<TotalOutstandingAmount>'.$CrLf);
		fwrite($this->file, '				<TotalAmount>'.number_format($object->total_ttc-$pagado,2,'.','').'</TotalAmount>'.$CrLf); //Total que se debe. Se descontarían los anticipos
		fwrite($this->file, '			</TotalOutstandingAmount>'.$CrLf);
		fwrite($this->file, '			<TotalExecutableAmount>'.$CrLf);
		fwrite($this->file, '				<TotalAmount>'.number_format($object->total_ttc-$pagado,2,'.','').'</TotalAmount>'.$CrLf); //Total a ejecutar Es el importe que se adeuda minorado en un posible importe retenido en garantía de cumplimiento contractuales
		fwrite($this->file, '			</TotalExecutableAmount>'.$CrLf);
		fwrite($this->file, '			<InvoiceCurrencyCode>EUR</InvoiceCurrencyCode>'.$CrLf);
		fwrite($this->file, '		</Batch>'.$CrLf);
		//1.6 Factory Assignment Data aquí
		fwrite($this->file, '	</FileHeader>'.$CrLf);
		fwrite($this->file, '	<Parties>'.$CrLf);
		fwrite($this->file, '		<SellerParty>'.$CrLf);
		fwrite($this->file, '			<TaxIdentification>'.$CrLf);//Los datos de la empresa podrían ir en la configuración del módulo para evitar problemas
		fwrite($this->file, '				<PersonTypeCode>'.$conf->global->FACTURAE_PERSON_TYPE_CODE.'</PersonTypeCode>'.$CrLf);
		fwrite($this->file, '				<ResidenceTypeCode>'.$conf->global->FACTURAE_RESIDENCE_TYPE_CODE.'</ResidenceTypeCode>'.$CrLf);
		fwrite($this->file, '				<TaxIdentificationNumber>'.($mysoc->country_code != $object->thirdparty->country_code?$mysoc->country_code:'').$mysoc->idprof1.'</TaxIdentificationNumber>'.$CrLf);
        fwrite($this->file, '			</TaxIdentification>'.$CrLf);
        if($conf->global->FACTURAE_PERSON_TYPE_CODE == 'F'){
	        fwrite($this->file, '			<Individual>'.$CrLf);
	        fwrite($this->file, '				<Name>'.$conf->global->FACTURAE_EMISOR_NAME.'</Name>'.$CrLf);
	        fwrite($this->file, '				<FirstSurname>'.$conf->global->FACTURAE_EMISOR_FIRST_SURNAME.'</FirstSurname>'.$CrLf);
	        fwrite($this->file, '				<SecondSurname>'.$conf->global->FACTURAE_EMISOR_SECOND_SURNAME.'</SecondSurname>'.$CrLf);
        }
        else{
        	fwrite($this->file, '			<LegalEntity>'.$CrLf);
        	fwrite($this->file, '				<CorporateName>'.$conf->global->FACTURAE_EMISOR_NAME.'</CorporateName>'.$CrLf);
        }
	    fwrite($this->file, '				<AddressInSpain>'.$CrLf);
        fwrite($this->file, '					<Address>'.dol_trunc($mysoc->address,75,'right','UTF-8',1).'</Address>'.$CrLf);
        fwrite($this->file, '					<PostCode>'.$mysoc->zip.'</PostCode>'.$CrLf);
        fwrite($this->file, '					<Town>'.$mysoc->town.'</Town>'.$CrLf);
        fwrite($this->file, '					<Province>'.getState($mysoc->state_id).'</Province>'.$CrLf);
        fwrite($this->file, '					<CountryCode>'.getCountryIso($mysoc->country_code).'</CountryCode>'.$CrLf);
        fwrite($this->file, '				</AddressInSpain>'.$CrLf);
        if($mysoc->phone || $mysoc->email || $conf->global->FACTURAE_CONTACT_NAME){
	        fwrite($this->file, '				<ContactDetails>'.$CrLf);
	        if($mysoc->phone)
	        	fwrite($this->file, '					<Telephone>'.dol_trunc($mysoc->phone,15,'right','UTF-8',1).'</Telephone>'.$CrLf);
	        if($mysoc->email)
	        	fwrite($this->file, '					<ElectronicMail>'.$mysoc->email.'</ElectronicMail>'.$CrLf);
	        if($conf->global->FACTURAE_CONTACT_NAME)        
	        	fwrite($this->file, '					<ContactPersons>'.$conf->global->FACTURAE_CONTACT_NAME.'</ContactPersons>'.$CrLf);
	        fwrite($this->file, '				</ContactDetails>'.$CrLf);
		}
	    if($conf->global->FACTURAE_PERSON_TYPE_CODE == 'F')
	       	fwrite($this->file, '			</Individual>'.$CrLf);
	    else
	       	fwrite($this->file, '			</LegalEntity>'.$CrLf);
        fwrite($this->file, '		</SellerParty>'.$CrLf);
        fwrite($this->file, '		<BuyerParty>'.$CrLf);
        fwrite($this->file, '			<TaxIdentification>'.$CrLf);
        fwrite($this->file, '				<PersonTypeCode>'.$fac_third->person_type.'</PersonTypeCode>'.$CrLf);
        fwrite($this->file, '				<ResidenceTypeCode>'.$fac_third->residence_type.'</ResidenceTypeCode>'.$CrLf);
		fwrite($this->file, '				<TaxIdentificationNumber>'.($mysoc->country_code != $object->thirdparty->country_code?$object->thirdparty->country_code:'').$object->thirdparty->idprof1.'</TaxIdentificationNumber>'.$CrLf);
        fwrite($this->file, '			</TaxIdentification>'.$CrLf);
        //Creo que aquí iría lo del DIR3 TODO
        if($fac_third->administrative){
	        fwrite($this->file, '			<AdministrativeCentres>'.$CrLf);
	        for($i=1;$i<5;$i++){
	            $existe_centro = false;
		        //montar un array con los tres centros
		        if($i==1 && $fac_third->contable) {
					fwrite($this->file, '				<AdministrativeCentre>'.$CrLf);
                    fwrite($this->file,'					<CentreCode>' . $fac_third->contable . '</CentreCode>' . $CrLf);
                    $existe_centro = true;
                }
		        if($i==2 && $fac_third->gestor) {
					fwrite($this->file, '				<AdministrativeCentre>'.$CrLf);
                    fwrite($this->file,'					<CentreCode>' . $fac_third->gestor . '</CentreCode>' . $CrLf);
                    $existe_centro = true;
                }
		        if($i==3 && $fac_third->tramitador) {
					fwrite($this->file, '				<AdministrativeCentre>'.$CrLf);
                    fwrite($this->file,'					<CentreCode>' . $fac_third->tramitador . '</CentreCode>' . $CrLf);
                    $existe_centro = true;
                }
                if($i==4 && $fac_third->comprador) {
					fwrite($this->file, '				<AdministrativeCentre>'.$CrLf);
                    fwrite($this->file,'					<CentreCode>' . $fac_third->comprador . '</CentreCode>' . $CrLf);
                    $existe_centro = true;
                }
                if ($existe_centro) {
                    fwrite($this->file, '					<RoleTypeCode>0' . $i . '</RoleTypeCode>' . $CrLf);
                    fwrite($this->file, '					<AddressInSpain>' . $CrLf);
                    fwrite($this->file, '						<Address>' . dol_trunc($object->thirdparty->address, 75, 'right','UTF-8', 1) . '</Address>' . $CrLf);
                    fwrite($this->file, '						<PostCode>' . $object->thirdparty->zip . '</PostCode>' . $CrLf);
                    fwrite($this->file, '						<Town>' . $object->thirdparty->town . '</Town>' . $CrLf);
                    fwrite($this->file, '						<Province>' . getState($object->thirdparty->state_id) . '</Province>' . $CrLf);
                    fwrite($this->file, '						<CountryCode>' . getCountryIso($object->thirdparty->country_code) . '</CountryCode>' . $CrLf);
                    fwrite($this->file, '					</AddressInSpain>' . $CrLf);
                    fwrite($this->file, '				</AdministrativeCentre>' . $CrLf);
                }
	        }
	        fwrite($this->file, '			</AdministrativeCentres>'.$CrLf);
        }
        
        if($fac_third->person_type == 'F'){
	        fwrite($this->file, '			<Individual>'.$CrLf);
	        fwrite($this->file, '				<Name>'.$fac_third->name.'</Name>'.$CrLf);
	        fwrite($this->file, '				<FirstSurname>'.$fac_third->first_surname.'</FirstSurname>'.$CrLf);
	        fwrite($this->file, '				<SecondSurname>'.$fac_third->second_surname.'</SecondSurname>'.$CrLf);
        }
        else{
        	fwrite($this->file, '			<LegalEntity>'.$CrLf);
        	fwrite($this->file, '				<CorporateName>'.$fac_third->name.'</CorporateName>'.$CrLf);
        }
	    fwrite($this->file, '				<AddressInSpain>'.$CrLf);
        fwrite($this->file, '					<Address>'.dol_trunc($object->thirdparty->address,75,'right','UTF-8',1).'</Address>'.$CrLf);
        fwrite($this->file, '					<PostCode>'.$object->thirdparty->zip.'</PostCode>'.$CrLf);
        fwrite($this->file, '					<Town>'.$object->thirdparty->town.'</Town>'.$CrLf);
        fwrite($this->file, '					<Province>'.getState($object->thirdparty->state_id).'</Province>'.$CrLf);
        fwrite($this->file, '					<CountryCode>'.getCountryIso($object->thirdparty->country_code).'</CountryCode>'.$CrLf);
        fwrite($this->file, '				</AddressInSpain>'.$CrLf);
        if($object->thirdparty->phone || $object->thirdparty->email || $fac_third->contact_name){
	        fwrite($this->file, '				<ContactDetails>'.$CrLf);
	        if($object->thirdparty->phone)
	        	fwrite($this->file, '					<Telephone>'.dol_trunc($object->thirdparty->phone,15,'right','UTF-8',1).'</Telephone>'.$CrLf);
	        if($object->thirdparty->email)
	        	fwrite($this->file, '					<ElectronicMail>'.$object->thirdparty->email.'.com</ElectronicMail>'.$CrLf);
	        if($fac_third->contact_name)
	        	fwrite($this->file, '					<ContactPersons>'.$fac_third->contact_name.'</ContactPersons>'.$CrLf);
	        fwrite($this->file, '				</ContactDetails>'.$CrLf);
        }
	    if($fac_third->person_type == 'F')
	       	fwrite($this->file, '			</Individual>'.$CrLf);
	    else
	       	fwrite($this->file, '			</LegalEntity>'.$CrLf);
        fwrite($this->file, '		</BuyerParty>'.$CrLf);
        fwrite($this->file, '	</Parties>'.$CrLf);
        fwrite($this->file, '	<Invoices>'.$CrLf);
        fwrite($this->file, '		<Invoice>'.$CrLf);
        fwrite($this->file, '			<InvoiceHeader>'.$CrLf);
        fwrite($this->file, '				<InvoiceNumber>'.$object->ref.'</InvoiceNumber>'.$CrLf);
        fwrite($this->file, '				<InvoiceDocumentType>'.$this->type.'</InvoiceDocumentType>'.$CrLf);
        fwrite($this->file, '				<InvoiceClass>'.($object->type == 1?'OR':'OO').'</InvoiceClass>'.$CrLf);
        if($object->type == 1){
	        fwrite($this->file, '				<Corrective>'.$CrLf);
	        $facreplaced = new Facture($this->db);
	        $facreplaced->fetch($object->fk_facture_source);
	        
	        fwrite($this->file, '					<InvoiceNumber>'.$facreplaced->ref.'</InvoiceNumber>'.$CrLf);
	        fwrite($this->file, '					<ReasonCode>'.$this->reason.'</ReasonCode>'.$CrLf);
	        fwrite($this->file, '					<ReasonDescription>'.$langs->transnoentities("FacturaeRect".$this->reason).'</ReasonDescription>'.$CrLf);
	        fwrite($this->file, '					<TaxPeriod>'.$CrLf);
	        fwrite($this->file, '						<StartDate>'.$this->startdate.'</StartDate>'.$CrLf);
	        fwrite($this->file, '						<EndDate>'.$this->enddate.'</EndDate>'.$CrLf);
	        fwrite($this->file, '					</TaxPeriod>'.$CrLf);
	        fwrite($this->file, '					<CorrectionMethod>'.$this->criterio.'</CorrectionMethod>'.$CrLf);
	        
	        fwrite($this->file, '					<CorrectionMethodDescription>'.$langs->transnoentities("FacturaeCrit".$this->criterio).'</CorrectionMethodDescription>'.$CrLf);
	        fwrite($this->file, '				</Corrective>'.$CrLf);
        }
        fwrite($this->file, '			</InvoiceHeader>'.$CrLf);
        fwrite($this->file, '			<InvoiceIssueData>'.$CrLf);
        fwrite($this->file, '				<IssueDate>'.dol_print_date($object->date,'%Y-%m-%d').'</IssueDate>'.$CrLf);
        fwrite($this->file, '				<InvoiceCurrencyCode>EUR</InvoiceCurrencyCode>'.$CrLf);
        fwrite($this->file, '				<TaxCurrencyCode>EUR</TaxCurrencyCode>'.$CrLf);
        fwrite($this->file, '				<LanguageName>es</LanguageName>'.$CrLf);
        fwrite($this->file, '			</InvoiceIssueData>'.$CrLf);
        fwrite($this->file, '			<TaxesOutputs>'.$CrLf);
        //bucle
        $subtotal = null;
        $subtotaltva = null;
        $subtotaltax1 = null;
        $subtotaltax2ht = null;
        $subtotaltax2 = null;
        foreach ($object->lines as $line)
        {
        	$subtotal[$line->tva_tx] += $line->total_ht;
        	$subtotaltva[$line->tva_tx] += $line->total_tva;
        	if($line->total_localtax1 > 0){
        		$subtotaltax1[$line->tva_tx] += $line->total_localtax1;
        		$subtotaltax1rate[$line->tva_tx] = $line->localtax1_tx;
        	}
        	if($line->total_localtax2 < 0){
        		$subtotaltax2ht += $line->total_ht;
        		$subtotaltax2 += $line->total_localtax2;
        		$subtotaltax2rate = $line->localtax2_tx;
        	}
        }
        // VAT
        foreach((array)$subtotal as $totkey => $totval){

        	//if ($totkey > 0)    // On affiche pas taux 0
        	//{
        		fwrite($this->file, '				<Tax>'.$CrLf);
        		fwrite($this->file, '					<TaxTypeCode>'.$conf->global->FACTURAE_TAX_TYPE.'</TaxTypeCode>'.$CrLf); //TODO 01 - IVA 02 - IPSI 03 - IGIC 04 - IRPF 05 - Otro 06 - ITPAJD 07 - IE 08 - Ra 09 - IGTECM 10 - IECDPCAC 11 - IIIMAB 12 - ICIO 13 - IMVDN 14 - IMSN 15 - IMGSN 16 - IMPN
        		fwrite($this->file, '					<TaxRate>'.number_format($totkey,2,'.','').'</TaxRate>'.$CrLf); // Tasa de impuesto
        		fwrite($this->file, '					<TaxableBase>'.$CrLf);
        		fwrite($this->file, '						<TotalAmount>'.number_format($subtotal[$totkey],2,'.','').'</TotalAmount>'.$CrLf); //base imponible afectada por el impuesto
        		fwrite($this->file, '					</TaxableBase>'.$CrLf);
        		fwrite($this->file, '					<TaxAmount>'.$CrLf);
        		fwrite($this->file, '						<TotalAmount>'.number_format($subtotaltva[$totkey],2,'.','').'</TotalAmount>'.$CrLf); //Importe impuesto
        		fwrite($this->file, '					</TaxAmount>'.$CrLf);
        		//Esto es el recargo de equivalencia y no es obligatorio
        		if($subtotaltax1rate[$totkey] > 0){
	        		fwrite($this->file, '					<EquivalenceSurcharge>'.number_format($subtotaltax1rate[$totkey],2,'.','').'</EquivalenceSurcharge>'.$CrLf); // esto es para el recargo de equivalencia
	        		fwrite($this->file, '					<EquivalenceSurchargeAmount>'.$CrLf); //esto es para el recargo de equivalencia
	        		fwrite($this->file, '						<TotalAmount>'.number_format($subtotaltax1[$totkey],2,'.','').'</TotalAmount>'.$CrLf);
	        		fwrite($this->file, '					</EquivalenceSurchargeAmount>'.$CrLf);
        		}
	        	fwrite($this->file, '				</Tax>'.$CrLf);
        	//}
        }
        //cierro bucle
        fwrite($this->file, '			</TaxesOutputs>'.$CrLf);
        //Retenciones, también opcionales
        if($subtotaltax2rate < 0){
	        fwrite($this->file, '			<TaxesWithheld>'.$CrLf);
	        fwrite($this->file, '				<Tax>'.$CrLf);
	        fwrite($this->file, '					<TaxTypeCode>04</TaxTypeCode>'.$CrLf); //04 es el valor del irpf
	        fwrite($this->file, '            		<TaxRate>'.number_format(abs($subtotaltax2rate),2,'.','').'</TaxRate>'.$CrLf);
	        fwrite($this->file, '            		<TaxableBase>'.$CrLf);
	        fwrite($this->file, '            			<TotalAmount>'.number_format(abs($subtotaltax2ht),2,'.','').'</TotalAmount>'.$CrLf);
	        fwrite($this->file, '            		</TaxableBase>'.$CrLf);
	        fwrite($this->file, '            		<TaxAmount>'.$CrLf);
	        fwrite($this->file, '            			<TotalAmount>'.number_format(abs($subtotaltax2),2,'.','').'</TotalAmount>'.$CrLf);
	        fwrite($this->file, '            		</TaxAmount>'.$CrLf);
	        fwrite($this->file, '            	</Tax>'.$CrLf);
	        fwrite($this->file, '            </TaxesWithheld>'.$CrLf);
        }
        fwrite($this->file, '            <InvoiceTotals>'.$CrLf);
        fwrite($this->file, '            	<TotalGrossAmount>'.number_format($object->total_ht,2,'.','').'</TotalGrossAmount>'.$CrLf);
        fwrite($this->file, '            	<TotalGrossAmountBeforeTaxes>'.number_format($object->total_ht,2,'.','').'</TotalGrossAmountBeforeTaxes>'.$CrLf); //En hazteunfacturae se pasa por el forro que la línea tenga descuentos TODO Total de GrossAmount - TotalGeneralDiscount + TotalGeneralSurcharges
		fwrite($this->file, '            	<TotalTaxOutputs>'.number_format($object->total_tva+$object->total_localtax1,2,'.','').'</TotalTaxOutputs>'.$CrLf); //total de impuesto repercutidos
		fwrite($this->file, '            	<TotalTaxesWithheld>'.number_format(abs($object->total_localtax2),2,'.','').'</TotalTaxesWithheld>'.$CrLf); //total de impuestos retenidos
		fwrite($this->file, '            	<InvoiceTotal>'.number_format($object->total_ttc,2,'.','').'</InvoiceTotal>'.$CrLf); //TODO total factura
		//Aquí irían anticipos
		fwrite($this->file, '            	<TotalOutstandingAmount>'.number_format($object->total_ttc - $pagado,2,'.','').'</TotalOutstandingAmount>'.$CrLf); //total pendiente de pago
        fwrite($this->file, '            	<TotalExecutableAmount>'.number_format($object->total_ttc - $pagado,2,'.','').'</TotalExecutableAmount>'.$CrLf); //total a ejecutar
        fwrite($this->file, '            </InvoiceTotals>'.$CrLf);
        fwrite($this->file, '            	<Items>'.$CrLf);
        
        foreach($object->lines as $line){
	        //ojo bucle
	        fwrite($this->file, '            		<InvoiceLine>'.$CrLf);
	        if($object->ref_client)fwrite($this->file, '            		<IssuerTransactionReference>'.(dol_substr($object->ref_client,0,20)).'</IssuerTransactionReference>'.$CrLf); //TODO pedido de referencia también hay de contrato, expediciones y referencias del receptor
			fwrite($this->file, '            		<ItemDescription>'.dol_html_entity_decode(dol_string_nohtmltag(trim($line->product_ref.' '.$line->product_label.' '.$line->product_desc.' '.$line->desc)),ENT_QUOTES).'</ItemDescription>'.$CrLf);  //TODO descripción de la línea
			fwrite($this->file, '            		<Quantity>'.number_format($line->qty,2,'.','').'</Quantity>'.$CrLf);  //unidades de la línea
	        //fwrite($this->file, '            		<UnitOfMeasure>01</UnitOfMeasure>'.$CrLf);  // unidades de medida
	        fwrite($this->file, '            		<UnitPriceWithoutTax>'.number_format($line->subprice,6,'.','').'</UnitPriceWithoutTax>'.$CrLf);  //precio unitario
	        fwrite($this->file, '            		<TotalCost>'.number_format($line->subprice*$line->qty,6,'.','').'</TotalCost>'.$CrLf);  //total linea
	        //aquí un if descuento
	        if($line->remise_percent > 0){
		        fwrite($this->file, '            		<DiscountsAndRebates>'.$CrLf);
				fwrite($this->file, '	            		<Discount>'.$CrLf);
		        fwrite($this->file, '    	        			<DiscountReason>'.$langs->trans("ComercialDiscount").'</DiscountReason>'.$CrLf);//Razón descuento
		        fwrite($this->file, '        	    			<DiscountRate>'.number_format($line->remise_percent,4,'.','').'</DiscountRate>'.$CrLf);//% de descuento
		        fwrite($this->file, '            				<DiscountAmount>'.number_format($line->subprice*$line->qty - $line->total_ht,6,'.','').'</DiscountAmount>'.$CrLf);//importe de descuento
		        fwrite($this->file, '            			</Discount>'.$CrLf);
		        fwrite($this->file, '	            	</DiscountsAndRebates>'.$CrLf);
	        }
	        /*if($line->total_localtax1 > 0){
	        	fwrite($this->file, '            		<Charges>'.$CrLf);
	        	fwrite($this->file, '            			<Charge>'.$CrLf);
	        	fwrite($this->file, '            				<ChargeReason>'.$langs->trans("ChargeReason").'</ChargeReason>'.$CrLf);
	        	fwrite($this->file, '            				<ChargeRate>'.str_replace($dec,'.',str_replace($thousand, '', price($line->localtax1_tx,0,'',1,4))).'</ChargeRate>'.$CrLf);
	        	fwrite($this->file, '            				<ChargeAmount>'.str_replace($dec,'.',str_replace($thousand, '', price($line->total_localtax1,0,'',1,6))).'</ChargeAmount>'.$CrLf);
	        	fwrite($this->file, '            			</Charge>'.$CrLf);
	        	fwrite($this->file, '            		</Charges>'.$CrLf);
	        }*/
	        fwrite($this->file, '    	        	<GrossAmount>'.number_format($line->total_ht,6,'.','').'</GrossAmount>'.$CrLf); //Importe bruto con descuentos aplicados
	        if($line->total_localtax2 < 0){
	        	fwrite($this->file, '            		<TaxesWithheld>'.$CrLf);
	        	fwrite($this->file, '            			<Tax>'.$CrLf);
	        	fwrite($this->file, '            				<TaxTypeCode>04</TaxTypeCode>'.$CrLf); //04 es IRPF
	        	fwrite($this->file, '            				<TaxRate>'.number_format(abs($line->localtax2_tx),2,'.','').'</TaxRate>'.$CrLf);
	        	fwrite($this->file, '            				<TaxableBase>'.$CrLf);
	        	fwrite($this->file, '            					<TotalAmount>'.number_format($line->total_ht,2,'.','').'</TotalAmount>'.$CrLf);
	        	fwrite($this->file, '            				</TaxableBase>'.$CrLf);
	        	fwrite($this->file, '            				<TaxAmount>'.$CrLf);
	        	fwrite($this->file, '            					<TotalAmount>'.number_format(abs($line->total_localtax2),2,'.','').'</TotalAmount>'.$CrLf);
	        	fwrite($this->file, '            				</TaxAmount>'.$CrLf);
	        	fwrite($this->file, '            			</Tax>'.$CrLf);
	        	fwrite($this->file, '            		</TaxesWithheld>'.$CrLf);
	        }
	        fwrite($this->file, '        	    	<TaxesOutputs>'.$CrLf);
	        fwrite($this->file, '            			<Tax>'.$CrLf);
	        fwrite($this->file, '            				<TaxTypeCode>'.$conf->global->FACTURAE_TAX_TYPE.'</TaxTypeCode>'.$CrLf); //01 es el IVA, tipo de impuesto
	        fwrite($this->file, '            				<TaxRate>'.number_format($line->tva_tx,2,'.','').'</TaxRate>'.$CrLf); //tasa iva
	        fwrite($this->file, '            				<TaxableBase>'.$CrLf);
	        fwrite($this->file, '            					<TotalAmount>'.number_format($line->total_ht,2,'.','').'</TotalAmount>'.$CrLf);  //base imponible sujeta a impuestos
	        fwrite($this->file, '	            			</TaxableBase>'.$CrLf);
	        fwrite($this->file, '    	        			<TaxAmount>'.$CrLf);
	        fwrite($this->file, '        	    				<TotalAmount>'.number_format($line->total_tva,2,'.','').'</TotalAmount>'.$CrLf); //total del impuesto
	        fwrite($this->file, '            				</TaxAmount>'.$CrLf);
	        fwrite($this->file, '	            		</Tax>'.$CrLf);
	        fwrite($this->file, '    	        	</TaxesOutputs>'.$CrLf);
	        fwrite($this->file, '					</InvoiceLine>'.$CrLf);
        }
        fwrite($this->file, '				</Items>'.$CrLf);
        fwrite($this->file, '			<PaymentDetails>'.$CrLf);
        fwrite($this->file, '				<Installment>'.$CrLf);
        fwrite($this->file, '					<InstallmentDueDate>'.dol_print_date($object->date_lim_reglement,"%Y-%m-%d").'</InstallmentDueDate>'.$CrLf); //fecha vencimiento
        fwrite($this->file, '					<InstallmentAmount>'.number_format($object->total_ttc - $pagado,2,'.','').'</InstallmentAmount>'.$CrLf); //importe a pagar
        //ojo si la forma de pago es transferencia habrá que poner bloque con datos bancarios
        $fac_pay = new Facturaepayments($this->db);
        $fac_pay->fetch('',$object->mode_reglement_id);
        fwrite($this->file, '					<PaymentMeans>'.$fac_pay->facturae_cod.'</PaymentMeans>'.$CrLf); //metodo pago 01 - Al contado 02 - Recibo Domiciliado 03 - Recibo 04 - Transferencia 05 - Letra Aceptada 06 - Crédito Documentario 07 - Contrato Adjudicación 08 - Letra de cambio 09 - Pagaré a la Orden 10 - Pagaré No a la Orden 11 - Cheque 12 - Reposición 13 - Especiales 14 - Compensación 15 - Giro postal 16 - Cheque conformado 17 - Cheque bancario 18 - Pago contra reembolso 19 - Pago mediante tarjeta
        //Si es transferencia, indicar el IBAN
        if($fac_pay->facturae_cod == '04'){
	        fwrite($this->file, '					<AccountToBeCredited>'.$CrLf);
	        $bankid = ($object->fk_account?$object->fk_account:$conf->global->FACTURE_RIB_NUMBER);
	        $bank = new Account($this->db);
	        $bank->fetch($bankid);
	        fwrite($this->file, '						<IBAN>'.$bank->iban.'</IBAN>'.$CrLf);
	        fwrite($this->file, '					</AccountToBeCredited>'.$CrLf);
        }
        fwrite($this->file, '				</Installment>'.$CrLf);
        fwrite($this->file, '			</PaymentDetails>'.$CrLf);
		if(!empty($object->note_public)) {
			fwrite($this->file, '			<AdditionalData>' . $CrLf);
			fwrite($this->file,
					'				<InvoiceAdditionalInformation>' . dol_trunc(dol_html_entity_decode(strip_tags($object->note_public),ENT_QUOTES), 2500, 'right',
							'UTF-8', 1) . '</InvoiceAdditionalInformation>' . $CrLf);
			fwrite($this->file, '			</AdditionalData>' . $CrLf);
		}
        fwrite($this->file, '		</Invoice>'.$CrLf);
       	fwrite($this->file, '	</Invoices>'.$CrLf);
        fwrite($this->file, '</n:Facturae>'.$CrLf);
    
        fclose($this->file);
        if (! empty($conf->global->MAIN_UMASK))
        @chmod($this->file, octdec($conf->global->MAIN_UMASK));
        setEventMessage($langs->trans("CreateFileOK"));
        return 1;
	
	}

	/**
	 *	Check if a string is a correct iso string
	 *	If not, it will we considered not HTML encoded even if it is by FPDF.
	 *	Example, if string contains euro symbol that has ascii code 128
	 *
	 *	@param	string	$s      String to convert
	 *	@return	string     		clean parameters
	 */
	public function convertGoodIso($s)
	{
		$buenos['!'] = '?';
		$buenos['"'] = '?';
		$buenos['#'] = '?';
		$buenos['$'] = 'S';
		$buenos['%'] = '?';
		$buenos['&'] = '?';
		$buenos['*'] = '?';
		$buenos[';'] = '.';
		$buenos['<'] = '?';
		$buenos['='] = '?';
		$buenos['>'] = '?';
		$buenos['@'] = 'a';
		$buenos['{'] = '?';
		$buenos['|'] = '?';
		$buenos['}'] = '?';
		$buenos['~'] = '?';
		$buenos['€'] = 'E';
		$buenos['‚'] = '?';
		$buenos['ƒ'] = 'f';
		$buenos['„'] = '?';
		$buenos['…'] = '?';
		$buenos['†'] = '?';
		$buenos['‡'] = '?';
		$buenos['ˆ'] = '?';
		$buenos['‰'] = '?';
		$buenos['Š'] = 'S';
		$buenos['‹'] = '?';
		$buenos['Œ'] = 'OE';
		$buenos['Ž'] = 'Z';
		$buenos['‘'] = '?';
		$buenos['’'] = '?';
		$buenos['“'] = '?';
		$buenos['”'] = '?';
		$buenos['•'] = '?';
		$buenos['–'] = '?';
		$buenos['—'] = '?';
		$buenos['˜'] = '?';
		$buenos['™'] = 'TM';
		$buenos['š'] = 's';
		$buenos['›'] = '?';
		$buenos['œ'] = 'oe';
		$buenos['ž'] = 'z';
		$buenos['Ÿ'] = 'Y';
		$buenos['¡'] = '?';
		$buenos['¢'] = 'c';
		$buenos['£'] = 'L';
		$buenos['¤'] = '?';
		$buenos['¥'] = 'Y';
		$buenos['¦'] = '?';
		$buenos['§'] = 'S';
		$buenos['¨'] = '?';
		$buenos['©'] = 'c';
		$buenos['ª'] = 'a';
		$buenos['«'] = '?';
		$buenos['¬'] = '?';
		$buenos['®'] = 'r';
		$buenos['¯'] = '?';
		$buenos['°'] = 'o';
		$buenos['±'] = '?';
		$buenos['²'] = '2';
		$buenos['³'] = '3';
		$buenos['´'] = '?';
		$buenos['µ'] = 'm';
		$buenos['¶'] = 'P';
		$buenos['·'] = '?';
		$buenos['¸'] = '?';
		$buenos['¹'] = '1';
		$buenos['º'] = 'o';
		$buenos['»'] = '?';
		$buenos['¼'] = '?';
		$buenos['½'] = '?';
		$buenos['¾'] = '?';
		$buenos['¿'] = '?';
		$buenos['À'] = 'A';
		$buenos['Á'] = 'A';
		$buenos['Â'] = 'A';
		$buenos['Ã'] = 'A';
		$buenos['Ä'] = 'A';
		$buenos['Å'] = 'A';
		$buenos['Æ'] = 'AE';
		$buenos['Ç'] = 'C';
		$buenos['È'] = 'E';
		$buenos['É'] = 'E';
		$buenos['Ê'] = 'E';
		$buenos['Ë'] = 'E';
		$buenos['Ì'] = 'I';
		$buenos['Í'] = 'I';
		$buenos['Î'] = 'I';
		$buenos['Ï'] = 'I';
		$buenos['Ð'] = 'D';
		$buenos['Ñ'] = 'N';
		$buenos['Ò'] = 'O';
		$buenos['Ó'] = 'O';
		$buenos['Ô'] = 'O';
		$buenos['Õ'] = 'O';
		$buenos['Ö'] = 'O';
		$buenos['×'] = 'x';
		$buenos['Ø'] = 'O';
		$buenos['Ù'] = 'U';
		$buenos['Ú'] = 'U';
		$buenos['Û'] = 'U';
		$buenos['Ü'] = 'U';
		$buenos['Ý'] = 'Y';
		$buenos['Þ'] = 'T';
		$buenos['ß'] = 's';
		$buenos['à'] = 'a';
		$buenos['á'] = 'a';
		$buenos['â'] = 'a';
		$buenos['ã'] = 'a';
		$buenos['ä'] = 'a';
		$buenos['å'] = 'a';
		$buenos['æ'] = 'ae';
		$buenos['ç'] = 'c';
		$buenos['è'] = 'e';
		$buenos['é'] = 'e';
		$buenos['ê'] = 'e';
		$buenos['ë'] = 'e';
		$buenos['ì'] = 'i';
		$buenos['í'] = 'i';
		$buenos['î'] = 'i';
		$buenos['ï'] = 'i';
		$buenos['ð'] = 'd';
		$buenos['ñ'] = 'n';
		$buenos['ò'] = 'o';
		$buenos['ó'] = 'o';
		$buenos['ô'] = 'o';
		$buenos['õ'] = 'o';
		$buenos['ö'] = 'o';
		$buenos['÷'] = '?';
		$buenos['ø'] = 'o';
		$buenos['ù'] = 'u';
		$buenos['ú'] = 'u';
		$buenos['û'] = 'u';
		$buenos['ü'] = 'u';
		$buenos['ý'] = 'y';
		$buenos['þ'] = 't';
		$buenos['ÿ'] = 'y';
		$buenos['['] = '?';
		$buenos["\\"] = '?';
		$buenos[']'] = '?';
		$buenos['^'] = '?';
		$buenos['_'] = '?';
		$buenos['`'] = '?';
		
		$newstr=strtr($s, $buenos);	
		
		return $newstr;
	}
}

/**
 *      \file       htdocs/sepa/class/sepa.class.php
 *      \ingroup    sepa
 *      \brief      File of construction class of SEPA thirdparty
 */

/**
 \class 	Sepa
 \brief     Class to generate files acording the SEPA rule
 */

class FacturaeThirdparty
{
	public $db;							//!< To store db handler
	public $error;							//!< To return error code (or message)
	public $errors=array();				//!< To return several error codes (or messages)
	public $element='facturaethirdparty';			//!< Id that identify managed objects
	public $table_element='facturaethirdparty';		//!< Name of table without prefix where object is stored

	public $id;

	public $entity;
	public $fk_soc;
	public $person_type;
	public $residence_type;
	public $name;
	public $first_surname;
	public $second_surname;
	public $contact_name;
	public $administrative;
	public $contable;
	public $gestor;
	public $tramitador;
	public $comprador;
		
	
	/**
     *	Constructor
     *
     *  @param		DoliDB		$db      	Database handler
     */
    public function __construct($db)
    {
    	$this->db = $db;
    }
    /**
	 *	Create record into database
	 *	@return     int         <0 si ko, >0 si ok
	 */
    public function create(){
    	
    	global $conf, $langs;
		$error=0;

		// Clean parameters
        
		if ($this->person_type!==null) $this->person_type=trim($this->person_type);
		if ($this->residence_type!==null) $this->residence_type=trim($this->residence_type);
		if ($this->name!==null) $this->name=trim($this->name);
		if ($this->first_surname!==null) $this->first_surname=trim($this->first_surname);
		if ($this->second_surname!==null) $this->second_surname=trim($this->second_surname);
		if ($this->contact_name!==null) $this->contact_name=trim($this->contact_name);
		if ($this->administrative!==null) $this->administrative=trim($this->administrative);
		if ($this->contable!==null) $this->contable=trim($this->contable);
		if ($this->gestor!==null) $this->gestor=trim($this->gestor);
		if ($this->tramitador!==null) $this->tramitador=trim($this->tramitador);
        if ($this->comprador!==null) $this->comprador=trim($this->comprador);

        

		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facturae_thirdparty(';
		
		$sql.= 'entity,';
		$sql.= 'fk_soc,';
		$sql.= 'person_type,';
		$sql.= 'residence_type,';
		$sql.= 'name,';
		$sql.= 'first_surname,';
		$sql.= 'second_surname,';
		$sql.= 'contact_name,';
		$sql.= 'administrative,';
		$sql.= 'contable,';
		$sql.= 'gestor,';
		$sql.= 'tramitador,';
        $sql.= 'comprador';

		
        $sql.= ') VALUES (';
        
		$sql.= ' '.$conf->entity.',';
		$sql.= ' '.$this->fk_soc.',';
		$sql.= " ".(! isset($this->person_type)?'NULL':"'".$this->db->escape($this->person_type)."'").",";
		$sql.= " ".(! isset($this->residence_type)?'NULL':"'".$this->db->escape($this->residence_type)."'").",";
		$sql.= " ".(! isset($this->name)?'NULL':"'".$this->db->escape($this->name)."'").",";
		$sql.= " ".(! isset($this->first_surname)?'NULL':"'".$this->db->escape($this->first_surname)."'").",";
		$sql.= " ".(! isset($this->second_surname)?'NULL':"'".$this->db->escape($this->second_surname)."'").",";
		$sql.= " ".(! isset($this->contact_name)?'NULL':"'".$this->db->escape($this->contact_name)."'").",";
		$sql.= " ".(! isset($this->administrative)?'NULL':"'".$this->administrative."'").",";
		$sql.= " ".(! isset($this->contable)?'NULL':"'".$this->db->escape($this->contable)."'").",";
		$sql.= " ".(! isset($this->gestor)?'NULL':"'".$this->db->escape($this->gestor)."'").",";
		$sql.= " ".(! isset($this->tramitador)?'NULL':"'".$this->db->escape($this->tramitador)."'").",";
        $sql.= " ".(! isset($this->comprador)?'NULL':"'".$this->db->escape($this->comprador)."'")."";

        
		$sql.= ')';

		$this->db->begin();

	   	dol_syslog(get_class($this).'::create sql='.$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]='Error '.$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'facturae_thirdparty');

			/*if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
			}*/
        }

        // Commit or rollback
        if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this).'::create '.$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}
    }
    
	 /**
	 *	Load object in memory from database
	 *	@param      int fk_soc      thirdparty
	 *  @return     int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($fk_soc)
	{
		global $langs;
        $sql = 'SELECT';
		$sql.= ' t.rowid,';
		
		$sql.= ' t.entity,';
		$sql.= ' t.fk_soc,';
		$sql.= ' t.person_type,';
		$sql.= ' t.residence_type,';
		$sql.= ' t.name,';
		$sql.= ' t.first_surname,';
		$sql.= ' t.second_surname,';
		$sql.= ' t.contact_name,';
		$sql.= ' t.administrative,';
		$sql.= ' t.contable,';
		$sql.= ' t.gestor,';
		$sql.= ' t.tramitador,';
        $sql.= ' t.comprador';

		
        $sql.= ' FROM '.MAIN_DB_PREFIX.'facturae_thirdparty as t';
        $sql.= ' WHERE t.fk_soc = '.$fk_soc;

    	dol_syslog(get_class($this).'::fetch sql='.$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;
                
				$this->entity = $obj->entity;
				$this->fk_soc = $obj->fk_soc;
				$this->person_type = $obj->person_type;
				$this->residence_type = $obj->residence_type;
				$this->name = $obj->name;
				$this->first_surname = $obj->first_surname;
				$this->second_surname = $obj->second_surname;
				$this->contact_name = $obj->contact_name;
				$this->administrative = $obj->administrative;
				$this->contable = $obj->contable;
				$this->gestor = $obj->gestor;
				$this->tramitador = $obj->tramitador;
                $this->comprador = $obj->comprador;

                
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error='Error '.$this->db->lasterror();
            dol_syslog(get_class($this).'::fetch '.$this->error, LOG_ERR);
            return -1;
        }
	}
	
	 /**
	 *  Update object into database
	 *
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return int     		   	 <0 if KO, >0 if OK
	 */
	public function update($notrigger=0)
	{
		global $conf, $langs;
		$error=0;
	
		// Clean parameters
	
		if (isset($this->person_type)) $this->person_type=trim($this->person_type);
		if (isset($this->residence_type)) $this->residence_type=trim($this->residence_type);
		if (isset($this->name)) $this->name=trim($this->name);
		if (isset($this->first_surname)) $this->first_surname=trim($this->first_surname);
		if (isset($this->second_surname)) $this->second_surname=trim($this->second_surname);
		if (isset($this->contact_name)) $this->contact_name=trim($this->contact_name);
		if (isset($this->administrative)) $this->administrative=trim($this->administrative);
		if (isset($this->contable)) $this->contable=trim($this->contable);
		if (isset($this->gestor)) $this->gestor=trim($this->gestor);
		if (isset($this->tramitador)) $this->tramitador=trim($this->tramitador);
        if (isset($this->comprador)) $this->comprador=trim($this->comprador);
	
	
	
		// Check parameters
		// Put here code to add a control on parameters values
	
		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."facturae_thirdparty SET";
	
		$sql.= " entity=".$conf->entity.",";
		$sql.= " fk_soc=".$this->fk_soc.",";
		$sql.= " person_type=".(isset($this->person_type)?"'".$this->db->escape($this->person_type)."'":"null").",";
		$sql.= " residence_type=".(isset($this->residence_type)?"'".$this->db->escape($this->residence_type)."'":"null").",";
		$sql.= " name=".(isset($this->name)?"'".$this->db->escape($this->name)."'":"null").",";
		$sql.= " first_surname=".(isset($this->first_surname)?"'".$this->db->escape($this->first_surname)."'":"null").",";
		$sql.= " second_surname=".(isset($this->second_surname)?"'".$this->db->escape($this->second_surname)."'":"null").",";
		$sql.= " contact_name=".(isset($this->contact_name)?"'".$this->db->escape($this->contact_name)."'":"null").",";
		$sql.= " administrative=".(isset($this->administrative)?$this->administrative:"null").",";
		$sql.= " contable=".(isset($this->contable)?"'".$this->db->escape($this->contable)."'":"null").",";
		$sql.= " gestor=".(isset($this->gestor)?"'".$this->db->escape($this->gestor)."'":"null").",";
		$sql.= " tramitador=".(isset($this->tramitador)?"'".$this->db->escape($this->tramitador)."'":"null").",";
        $sql.= " comprador=".(isset($this->comprador)?"'".$this->db->escape($this->comprador)."'":"null")."";
	
	
		$sql.= " WHERE rowid=".$this->id;
	
		$this->db->begin();
	
		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error++; $this->errors[]="Error ".$this->db->lasterror();
		}
	
		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.
	
				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}
	
		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}
}
?>
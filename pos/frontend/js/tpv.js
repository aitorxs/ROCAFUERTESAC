var globalTicket;
var Ticket = jQuery.Class({

    init: function () {
        this.id = 0;
        this.payment_type = 0;
        this.type = 0;
        this.discount_percent = 0;
        this.discount_qty = 0;
        this.lines = new Array();
        this.oldproducts = new Array();
        this.total = 0;
        this.customerpay = 0;
        this.difpayment = 0;
        this.customerId = 0;
        this.employeeId = 0;
        this.idsource = 0;
        this.state = 1; // 0=Draft, 1=To Invoice , 2=Invoiced, 3=No invoiceble
        this.id_place = 0;
        this.note = "";
        this.mode = 0;
        this.points = 0;
        this.idCoupon = 0;
        this.ret_points = 0;
        this.customerpay1 = 0;
        this.customerpay2 = 0;
        this.customerpay3 = 0;
        this.serie = 0;
    },
    setButtonState: function (hasTicket) {
        if (!hasTicket) {
            $('#btnReturnTicket').hide();
            $('#btnTicketRef').hide();
            $('#btnSaveTicket').hide();
            $('#btnAddDiscount').hide();
            $('#btnOkTicket').hide();
            $('#btnTicketNote').hide();
            $('#alertfaclim').hide();
        }
        else {

            $('#btnReturnTicket').hide();
            $('#btnTicketRef').hide();
            if (_TPV.ticketState == 0 && _TPV.ticket.type != 1) {
                $('#btnSaveTicket').show();
                $('#btnAddDiscount').show();
            }
            $('#btnOkTicket').show();
            $('#btnTicketNote').show();
        }

    },
    checkApplyQuantity: function (idProduct, cant) {
        var lineproduct = null;
        if (typeof _TPV.ticket.oldproducts != 'undefined' && _TPV.ticket.oldproducts.length > 0) {
            for (var i = 0; i < _TPV.ticket.oldproducts.length; i++) {
                if (_TPV.ticket.oldproducts[i]['idProduct'] == idProduct) {
                    lineproduct = _TPV.ticket.oldproducts[i];
                    break;
                }
            }
            if (parseFloat(cant) >= parseFloat(lineproduct.cant))
                return false;
        }
        return true;
    },
    checkExistReturnProduct: function (idProduct) {

        if (typeof _TPV.ticket.oldproducts != 'undefined' && _TPV.ticket.oldproducts.length > 0) {
            for (var i = 0; i < _TPV.ticket.oldproducts.length; i++) {
                if (_TPV.ticket.oldproducts[i]['idProduct'] == idProduct) {
                    return true;
                }
            }
        }
        return false;
    },
    newTicket: function () {
        this.init();
        this.customerId = _TPV.customerId;
        this.cashId = _TPV.cashId;
        this.discount_percent = _TPV.discount;
        _TPV.ticketState = 0;
        _TPV.getDataCategories(0);
        this.setButtonState(false);

        $('#tablaTicket tbody tr').remove();
        $('#totalDiscount').html(displayPrice(0));
        $('#totalTicket').html(displayPrice(0));
        $('#totalTicketinv').html(displayPrice(0));
        $('#totalPlace').html('');
        var result = ajaxDataSend('getNotes', 0);
        if (result) {
            $('#totalNote_').html(result);
        }
        else {
            $('#totalNote_').html(0);
        }
        if (typeof _TPV.defaultConfig['customer']['name'] != 'undefined') {
            $('#infoCustomer').html(_TPV.defaultConfig['customer']['name']);
            $('#infoCustomer_').html(_TPV.defaultConfig['customer']['name']);
        }
        _TPV.points = _TPV.defaultConfig['customer']['points'];
        _TPV.coupon = _TPV.defaultConfig['customer']['coupon'];
        _TPV.activeIdProduct = 0;
        $('#info_product').hide();
        $('#payment_points').hide();
        hideLeftContent();
        if (_TPV.defaultConfig['terminal']['barcode'] == 1) {
            $('#id_product_search').focus();
        }
    },
    newTicketPlace: function (id_place) {
        this.newTicket();
        $('#totalPlace').html(_TPV.places[id_place]);
        this.id_place = id_place;
        showTicketContent();

    },
    setLine: function (idProduct, line) {
        this.lines.push(line);
        if (this.lines.length == 1)
            this.setButtonState(true);
    },
    setPlace: function (idPlace) {
        this.id_place = idPlace;
    },
    getLine: function (idProduct) {
        for (var i in this.lines) {
            if (this.lines[i]['idProduct'] == idProduct)
                return this.lines[i];
        }
        return null;
    },
    getTotal: function () {

        return this.total.toFixed(2);
    },
    calculeDiscountTotal: function (total_lines) {
        discount = total_lines - this.total;
        var pricediscount = new Number(discount);
        pricediscount = pricediscount.toFixed(2);
        $('#totalDiscount').html(displayPrice(pricediscount));
        var total = new Number(this.total);
        total = total.toFixed(2);
        $('#totalTicket').html(displayPrice(total));
        $('#totalTicketinv').html(displayPrice(total));
    },
    calculeTotal: function () {
        var sum = 0;
        var sum2 = 0;
        discount = 0;

        if (this.discount_percent != null) {
            discount = this.discount_percent;
        }
        for (var i in this.lines) {
            var line = this.lines[i];

            if (parseFloat(line["remise_percent_global"]) != parseFloat(discount)) {
                line["remise_percent_global"] = discount;
                if (_TPV.ticket.customerId != 0) {
                    line["socid"] = _TPV.ticket.customerId;
                }
                else {
                    line["socid"] = _TPV.customerId;
                }

                if (!line["price_base_type"])
                    line["price_base_type"] = "TTC";

				if (_TPV.ticket.customerId != 0) {
					line['buyer'] = _TPV.ticket.customerId;
				}
				else {
					line['buyer'] = _TPV.customerId;
				}
                var result = ajaxDataSend('calculePrice', line);
                this.lines[i].total = result["total_ttc"];
                this.lines[i].total_ttc_without_discount = result["total_ttc_without_discount"];
                sum = parseFloat(sum) + Math.round(parseFloat(result["total_ttc"]) * 100) / 100;
                sum2 = parseFloat(sum2) + Math.round(parseFloat(result["total_ttc_without_discount"]) * 100) / 100;
            }
            else {
                sum = parseFloat(sum) + Math.round(parseFloat(this.lines[i].total_ttc) * 100) / 100;
                sum2 = parseFloat(sum2) + Math.round(parseFloat(this.lines[i].total_ttc_without_discount) * 100) / 100;
            }
        }

        this.total = Math.round(sum * 100) / 100;
        sum2 = Math.round(sum2 * 100) / 100;

        var pricediscount = new Number(discount);
        pricediscount = sum2 - this.total;
        pricediscount = Math.round(pricediscount * 100) / 100;
        $('#totalDiscount').html(displayPrice(pricediscount));
        var total = new Number(this.total);
        total = total.toFixed(2);
        $('#totalTicket').html(displayPrice(total));
        $('#totalTicketinv').html(displayPrice(total));
        var limfac = new Number(_TPV.faclimit);
        if (total >= limfac) {
            $('#alertfaclim').show();
        }
        else {
            $('#alertfaclim').hide();
        }
    },
    addProductLine: function () {
        if (!this.getLine(_TPV.activeIdProduct)) {
            this.addLine(_TPV.activeIdProduct, true);
        }
        var cant = parseInt($('#id_product_quantity').val());
        if (cant > 1) {
            cant = cant - 1;
            this.getLine(_TPV.activeIdProduct).cant = this.getLine(_TPV.activeIdProduct).cant + cant;
            this.getLine(_TPV.activeIdProduct).setQuantity(this.getLine(_TPV.activeIdProduct).cant);
            this.getLine(_TPV.activeIdProduct).showTotal();
        }
        showTicketContent();
    },
    addManualProduct: function (id, qty, disc, pri, note) {
        if (typeof id != 'undefined' && id != 0) {
            _TPV.activeIdProduct = id;
            _TPV.ticket.addLine(id);
            var flag = 0;
            if (typeof qty != 'undefined' && qty != 1) {

                cant = qty - 1;
                this.getLine(_TPV.activeIdProduct).cant = this.getLine(_TPV.activeIdProduct).cant + cant;
                this.getLine(_TPV.activeIdProduct).setQuantity(this.getLine(_TPV.activeIdProduct).cant);
                flag = 1;

            }

            if (typeof disc != 'undefined' && disc != 0) {
                this.getLine(_TPV.activeIdProduct).setDiscount(disc);
                //this.getLine(_TPV.activeIdProduct).price = this.getLine(_TPV.activeIdProduct).price / (1-disc/100);
                flag = 1;
            }
            if (note) {
                this.getLine(_TPV.activeIdProduct).setNote(note);
            }
            if (flag) {
                this.getLine(_TPV.activeIdProduct).showTotal();
            }
        }
    },
    addReturnProduct: function (idProduct) {
        if (!this.checkExistReturnProduct(idProduct))
            return;
        if (this.getLine(idProduct) != undefined) {
            var line = this.getLine(idProduct);
            var quantity = line.cant;
            if (!this.checkApplyQuantity(idProduct, quantity++))
                return;
            line.setQuantity(quantity++);
            line.showTotal();
        }
        else {
            var line = new TicketLine();
            line.setLineByIdLine(idProduct);
            if (line.discount != 0)
                line.setDiscount(line.discount);
            //this.total = this.total + line.price_ttc;
            //$('#totalTicket').html(displayPrice(this.total));

            // Batch
            var data = new Object();
            data['prodid'] = idProduct;
            data['facid'] = _TPV.ticket.idsource;

            var result = ajaxDataSend('getBatchProduct', data);

            if (result[0]['batch']=='1'){
                line.cant = "1";
                line.price_ttc = line.total_ttc / line.cant;

                $("#batchTableRet_ tr.data").remove();
                var win = "$('#idgetBatchRet').dialog('close')";
                $.each(result[0]['batchs'], function (id, item) {
                    $('#batchTableRet_').append('<tr class="data"><td class="itemId">' + item['batch'] + '</td><td class="action add"><a class="action addbatch" onclick="_TPV.ticket.addBatchRet('  + idProduct + ',\'' + item['batch']  + '\');' + win + ';"></a></td></tr>');
                });

                _TPV.getBatchRet(idProduct);

            }

            this.setLine(idProduct, line);
            $('#tablaTicket > tbody:last').prepend(line.getHtml());
            line.price_base_type = 'HT';
            line.showTotal();
            //this.calculeDiscountTotal();


        }


    },
    addLine: function (idProduct, add) {
        if (_TPV.ticketState == 1)
            return;
        if (_TPV.infoProduct == 0 || typeof add != 'undefined') {
            showTicketContent();

            if (_TPV.ticket.idsource != 0) {
                this.addReturnProduct(idProduct);
                return;
            }
            if (this.getLine(idProduct) != undefined) {
                if (_TPV.products[idProduct]["stock"] > this.getLine(idProduct).cant || _TPV.products[idProduct]["stock"] == "all") {

                    // Batch
                    if (_TPV.products[idProduct]["batch"] > 0) {
                        _TPV.getBatch(idProduct);

                    }

                    this.getLine(idProduct).cant = this.getLine(idProduct).cant + 1;
                    this.getLine(idProduct).setQuantity(this.getLine(idProduct).cant);
                    this.getLine(idProduct).showTotal();
                }
                else {
                    //Muestro un error diciendo que no hay stock ni se le espera...
                    var txt = ajaxDataSend('Translate', 'NoStockEnough');
                    _TPV.showError(txt);
                }
            }
            else {
                var line = new TicketLine();
                line.setLineByIdProducts(idProduct);
                if (_TPV.products[idProduct]["stock"] >= line.cant || _TPV.products[idProduct]["stock"] == "all") {

                    // Batch
                    if (_TPV.products[idProduct]["batch"] > 0) {
                        _TPV.getBatch(idProduct);

                    }

                    this.total = this.total + line.total_ttc;
                    $('#totalTicket').html(displayPrice(this.total));
                    $('#totalTicketinv').html(displayPrice(this.total));
                    this.setLine(idProduct, line);
                    $('#tablaTicket > tbody:last').prepend(line.getHtml());
                }
            }
        }
        else {

            showInfoProduct();
        }
        _TPV.ticket.calculeTotal();
        _TPV.addInfoProduct(idProduct);
        if (_TPV.defaultConfig['terminal']['barcode'] == 1) {
            $('#id_product_search').focus();
        }

    },
    addTicketCoupon: function (amount, id) {
        this.total = this.total - amount;
        _TPV.ticket.difpayment = _TPV.ticket.difpayment - amount;
        if (_TPV.ticket.difpayment > 0)
            $('.payment_return').addClass('negat');
        else
            $('.payment_return').removeClass('negat');
        $('.payment_options .payment_return').html(displayPrice(_TPV.ticket.difpayment));
        this.idCoupon = id;
        $('#payment_coupon').hide();
        var txt = ajaxDataSend('Translate', 'CouponAdded');
        _TPV.showInfo(txt);
    },
    editTicketLine: function (idProduct) {

        if (_TPV.ticket.getLine(idProduct).batch==1) {
            $('#line_quantity').attr('disabled','disabled');
        } else {
            $('#line_quantity').removeAttr('disabled');
        }

        $('#line_quantity').val(_TPV.ticket.getLine(idProduct).cant);
        $('#line_discount').val(_TPV.ticket.getLine(idProduct).discount);
        if (_TPV.defaultConfig['module']['ttc'] == 0)
            $('#line_price').val(Math.round(_TPV.ticket.getLine(idProduct).price_ht * 100) / 100);
        else
            $('#line_price').val(Math.round(_TPV.ticket.getLine(idProduct).price_ttc * 100) / 100);
        $('#line_note').val(_TPV.ticket.getLine(idProduct).note);
        //$('#idTicketLine').dialog({width: 400});
        showLeftContent('#idTicketLine');
        $('#id_btn_editTicketline').unbind('click');
        $('#id_btn_editTicketline').click(function () {
            if (_TPV.ticket.checkApplyQuantity(idProduct, $('#line_quantity').val())) {
                var line = _TPV.ticket.getLine(idProduct);
                line.setQuantity($('#line_quantity').val());
                line.setDiscount($('#line_discount').val());
                line.setPrice($('#line_price').val());
                line.setNote($('#line_note').val());
                line.showTotal();
            }
            //$('#idTicketLine').dialog("close");
            hideLeftContent();
        });
    },
    deleteLine: function (idProduct) {
        var success = ajaxSend('deleteLine');
        $('#ticketLine' + idProduct).remove();
        this.total = this.total - this.getLine(idProduct).total;
        $('#totalTicket').html(displayPrice(this.total));
        $('#totalTicketinv').html(displayPrice(this.total));
        this.lines = removeKey(this.lines, idProduct);
        if (this.lines.length == 0) {
            this.setButtonState(false);

        }
        this.calculeTotal();
        $('#ticketOptions').html('').hide();
    },
    cancelTicket: function () {
        var success = ajaxSend('cancelTicket');
        $('#tablaTicket tbody tr').remove();
    },
    saveTicket: function () {
        // Set State to draft
        _TPV.ticket.mode = 0;
        _TPV.ticket.state = 0;
        _TPV.ticket.employeeId = _TPV.employeeId;
        var result = ajaxDataSend('saveTicket', _TPV.ticket);
        $('#tablaTicket tbody tr').remove();
        _TPV.ticket.newTicket();
    },

    okTicket: function () {
        $('#id_btn_add_ticket').hide();
        $('#payment_points').hide();
        $('#info_product').hide();
        _TPV.ticket.employeeId = _TPV.employeeId;
        _TPV.ticket.convertDis = false;
		var result = ajaxDataSend('calculePriceTotal', this);
		if (result['total'] != this.total) {
		    var diff = this.total - result['total'];
			$('#ticketLine' + this.lines[0].idProduct).find('.total').html(displayPrice(this.lines[0].total-diff));
			this.total = result['total'];
			$('#totalTicket').html(displayPrice(this.total));
			$('#totalTicketinv').html(displayPrice(this.total));
		}
        if (_TPV.ticket.type == 1) {
            $('#pay_client_ret_0').val('');
            $('#pay_client_ret_1').val('');
            $('#pay_client_ret_2').val('');
            $('.payment_options .payment_return_ret').html('');
            this.difpayment = Math.min(this.total, this.ret_points);

            if (_TPV.ticket.difpayment > 0)
                $('.payment_return_ret').addClass('negat');
            else
                $('.payment_return_ret').removeClass('negat');

            //$('.payment_options .payment_total').html(this.total);
            $('.payment_options .payment_return_ret').html(displayPrice(this.difpayment));
            $('#payment_options').hide();

            //la opcion para elegir ticket, facsim o factura
			/*if(_TPV.defaultConfig['module']['ticket'] == 1 && _TPV.defaultConfig['module']['facture'] == 1){
			 showLeftContent('#idReturnMode');
			 }*/
            //else if(_TPV.defaultConfig['module']['ticket'] == 1){
            if (_TPV.ticket.mode == 0) {
                showLeftContent('#payTypeRet');
                $('#convert_coupon').hide();
                $('#payment_total_ret').show();
                $('#payment_total_points_ret').show();
                _TPV.ticket.mode = 0;
            }
            else if (_TPV.ticket.total < _TPV.faclimit) {
                showLeftContent('#payTypeRet');
                _TPV.ticket.showTotalBlockRet();
                $('#convert_coupon').show();
                _TPV.ticket.mode = 1;
            }
            else {
                showLeftContent('#payTypeRet');
                _TPV.ticket.showTotalBlockRet();
                $('#convert_coupon').show();
                _TPV.ticket.mode = 2;
            }
			/*$('#id_btn_ticketRet').click(function(){
			 $('#id_btn_ticketRet').unbind('click');
			 showLeftContent('#payTypeRet');
			 $('#payment_total_ret').show();
			 if(_TPV.ticket.mode == 0)
			 $('#convert_coupon').hide();
			 $('#id_btn_add_ticket_ret').show();
			 _TPV.ticket.mode=0;
			 });
			 $('#id_btn_facsimRet').click(function(){
			 $('#id_btn_facsimRet').unbind('click');
			 _TPV.ticket.mode=1;
			 showLeftContent('#payTypeRet');
			 _TPV.ticket.showTotalBlockRet();
			 });
			 $('#id_btn_factureRet').click(function(){
			 $('#id_btn_factureRet').unbind('click');
			 _TPV.ticket.mode=2;
			 showLeftContent('#payTypeRet');
			 _TPV.ticket.showTotalBlockRet();
			 });*/
        }
        else {
            $('#pay_client_0').val('');
            $('#pay_client_1').val('');
            $('#pay_client_2').val('');
            $('#points_client_id').val('');
            $('.payment_options .payment_return').html('');
            this.difpayment = this.total;

            if (_TPV.ticket.difpayment > 0)
                $('.payment_return').addClass('negat');
            else
                $('.payment_return').removeClass('negat');

            //$('.payment_options .payment_total').html(this.total);
            $('.payment_options .payment_return').html(displayPrice(this.difpayment));
            $('#payment_options').hide();

            //la opcion para elegir ticket, facsim o factura
            if (_TPV.defaultConfig['module']['ticket'] == 1 && _TPV.defaultConfig['module']['facture'] == 1) {
                showLeftContent('#idFactureMode');
            }
            else if (_TPV.defaultConfig['module']['ticket'] == 1) {
                _TPV.ticket.mode = 0;
                showLeftContent('#payType');
                _TPV.ticket.showTotalBlock();
            }

            else if (_TPV.ticket.total < _TPV.faclimit) {
                _TPV.ticket.mode = 1;
                showLeftContent('#payType');
                _TPV.ticket.showTotalBlock();
            }
            else {
                _TPV.ticket.mode = 2;
                if (_TPV.defaultConfig['module']['series'] == 1) {
                    showLeftContent('#idSerieMode');
                }
                else {
                    showLeftContent('#payType');
                    _TPV.ticket.showTotalBlock();
                }
            }
            $('#id_btn_ticketPay').click(function () {
                $('#id_btn_ticketPay').unbind('click');
                _TPV.ticket.mode = 0;
                showLeftContent('#payType');
                $('#payment_coupon').hide();

                $('#payment_total_points').show();
                $('#id_btn_add_ticket').show();
            });
            $('#id_btn_facsimPay').click(function () {
                $('#id_btn_facsimPay').unbind('click');
                _TPV.ticket.mode = 1;
                showLeftContent('#payType');
                _TPV.ticket.showTotalBlock();
            });
            $('#id_btn_facturePay').click(function () {
                $('#id_btn_facturePay').unbind('click');
                _TPV.ticket.mode = 2;
                if (_TPV.defaultConfig['module']['series'] == 1) {
                    showLeftContent('#idSerieMode');
                }
                else {
                    showLeftContent('#payType');
                    _TPV.ticket.showTotalBlock();
                }
            });
        }

        $('#id_btn_add_ticket').unbind('click');
        $('#id_btn_add_ticket_ret').unbind('click');
        $('#id_btn_add_ticket_desc').unbind('click');

        $('#id_btn_add_ticket').click(function () {
            _TPV.ticket.sendTicket();
        });

        $('#id_btn_add_ticket_ret').click(function () {
            _TPV.ticket.sendTicket();
        });
        $('#id_btn_add_ticket_desc').click(function () {
            _TPV.ticket.convertDis = true;
            _TPV.ticket.sendTicket();
        });

    },
    sendTicket: function () {
        _TPV.ticket.state = 1;
        _TPV.ticket.cashId = _TPV.cashId;

        //Comprobar si el importe es menor
        if (_TPV.ticket.difpayment > 0){
            _TPV.ticket.paymentMenor();
        }
        else {
            var sendTicket = _TPV.ticket;
            var result = ajaxDataSend('saveTicket', sendTicket);

            hideLeftContent();
            if (!result)
                return;
            if (_TPV.defaultConfig['module']['print'] > 0) {
                if (_TPV.ticket.mode == 0) {
                    _TPV.printing('ticket', result);
                }
                else {
                    _TPV.printing('facture', result);
                }
                _TPV.ticket.newTicket();

            }
            else {
                _TPV.ticket.newTicket();
            }
        }
    },
    showAddCustomer: function (customer) {
        $('#idClient').dialog({modal: true});
        $('#idClient').dialog({width: 440});
        $('#id_btn_add_customer').unbind('click');
        $('#id_btn_add_customer').click(function () {
            var customer = new Customer();
            customer.nom = $('#id_customer_name').val();
            customer.prenom = $('#id_customer_lastname').val();
            customer.address = $('#id_customer_address').val();
            customer.town = $('#id_customer_town').val();
            customer.zip = $('#id_customer_zip').val();
            customer.idprof1 = $('#id_customer_cif').val();
            customer.tel = $('#id_customer_phone').val();
            customer.email = $('#id_customer_email').val();
            var result = ajaxDataSend('addCustomer', customer);
            $('#idClient').dialog('close');
            //if(result.length>0)
            //_TPV.ticket.id= result[0];
        });

    },
    addTicketCustomer: function (idcustomer, name, remise, coupon, points) {
        _TPV.ticket.customerId = idcustomer;
        _TPV.ticket.discount_percent = remise;
        _TPV.points = points;
        _TPV.coupon = coupon;
        $('#infoCustomer').html(name);
        $('#infoCustomer_').html(name);
        showTicketContent();
    },
    addBatchLine: function (idProduct, batch,stock){
        var line = _TPV.ticket.getLine(idProduct);
        var data = new Object();

        data['batch'] = batch;
        data['stock'] = stock;
        data['qty'] = 1 ;

        line.batchs.push(data);

        $('#id_batch_search_').val('');
        $('#batchTable_ tbody tr').remove();

    },
    addBatchRet: function (idProduct, batch){
        var line = _TPV.ticket.getLine(idProduct);
        var data = new Object();

        data['batch'] = batch;
        data['qty'] = 1 ;

        line.batchs.push(data);

        $('#id_batch_search_').val('');
        $('#batchTable_ tbody tr').remove();

    },
    showAddProduct: function (customer) {
        var product = new Product();
        $('#id_product_name').val('');
        $('#id_product_ref').val('');
        $('#id_product_price').val('');
        $('#idPanelProduct').dialog({modal: true});
        $('#idPanelProduct').dialog({height: 450, width: 440});
        $('.tax_types').removeClass('btnon');
        $('.tax_types').unbind('click');
        $('.tax_types').click(function () {
            $('.tax_types').removeClass('btnon');
            $(this).addClass('btnon');
            product.tax = $(this).find('a:first').attr('id').substring(7);
        })
        $('#id_btn_add_product').unbind('click');
        $('#id_btn_add_product').click(function () {

            product.label = $('#id_product_name').val();
            product.ref = $('#id_product_ref').val();
            if (_TPV.defaultConfig['module']['ttc'] == 0)
                product.price_ht = $('#id_product_price').val();
            else
                product.price_ttc = $('#id_product_price').val();
            var result = ajaxDataSend('addNewProduct', product);
            $('#idPanelProduct').dialog('close');
            if (result)
                _TPV.getDataCategories(0);

        });

    },
    addDiscount: function () {
        $('#ticket_discount_perc').val('');
        $('#ticket_discount_qty').val('');
        //	$('#idDiscount').show();
        //$('#products').hide();
        showLeftContent('#idDiscount');
        $('#id_btn_add_discount').unbind('click');
        $('#id_btn_add_discount').click(function () {
            _TPV.ticket.discount_percent = $('#ticket_discount_perc').val();
            _TPV.ticket.discount_qty = $('#ticket_discount_qty').val();
            _TPV.ticket.calculeTotal();
            //$('#idDiscount').hide();
            //$('#products').show();
            hideLeftContent();
        });
    },
    addTicketNote: function () {
        $('#ticket_note').val(_TPV.ticket.note);
        $('#ticketNote').dialog({modal: true});
        $('#ticketNote').dialog({width: 450});
        $('#id_btn_ticket_note').unbind('click');
        $('#id_btn_ticket_note').click(function () {
            _TPV.ticket.note = $('#ticket_note').val();

            $('#ticketNote').dialog("close");

        });

    },
    paymentMenor: function () {
        $('#paymentMenor').dialog({modal: true});
        $('#paymentMenor').dialog({height: 250, width: 350});
        $('#id_btn_payment_menor_yes').unbind('click');
        $('#id_btn_payment_menor_no').unbind('click');
        $('#id_btn_payment_menor_yes').click(function () {
            _TPV.ticket.state = 1;
            _TPV.ticket.cashId = _TPV.cashId;

            var sendTicket = _TPV.ticket;
            var result = ajaxDataSend('saveTicket', sendTicket);

            hideLeftContent();
            if (!result)
                return;
            if (_TPV.defaultConfig['module']['print'] > 0) {
                if (_TPV.ticket.mode == 0) {
                    _TPV.printing('ticket', result);
                }
                else {
                    _TPV.printing('facture', result);
                }
                _TPV.ticket.newTicket();

            }
            else {
                _TPV.ticket.newTicket();
            }

            $('#paymentMenor').dialog("close");

        });
        $('#id_btn_payment_menor_no').click(function () {
            _TPV.ticket.close = 0;

            $('#paymentMenor').dialog("close");

        });
    },
    showCoupon: function () {
        if (_TPV.ticket.customerId == 0) {
            _TPV.ticket.customerId = _TPV.customerId;
        }
        var result = ajaxDataSend('searchCoupon', _TPV.ticket.customerId);
        $('#idCoupon').dialog({modal: true});
        $('#idCoupon').dialog({height: 450, width: 600});
        $("#couponTable_ tr.data").remove();
        var win = "$('#idCoupon').dialog('close')";
        $.each(result, function (id, item) {
            $('#couponTable_').append('<tr class="data"><td class="itemId" style="display:none">' + item['id'] + '</td><td class="itemReason">' + item['description'] + '</td><td class="itemAmount">' + displayPrice(item['amount_ttc']) + '</td><td class="action add"><a class="action addcoupon" onclick="_TPV.ticket.addTicketCoupon(' + item['amount_ttc'] + ',' + item['id'] + ');' + win + ';"></a></td></tr>');
        });

    },
    showTotalBlock: function () {
        if (_TPV.coupon <= 0 || _TPV.ticket.idCoupon > 0) {
            $('#payment_coupon').hide();
        }
        else {
            $('#payment_coupon').show();
        }
        if (_TPV.points != null && _TPV.ticket.mode != 0) {
            $('#payment_points').show();
            $('#payment_total_points').hide();
        }
        else {
            $('#payment_total_points').show();
        }
        $('#id_btn_add_ticket').show();
        //Initialize Values
        $('.points_total').html(_TPV.points);
        $('.points_money').html(_TPV.defaultConfig['module']['points'] * _TPV.points + ' ');
        //$('.payment_total').html(displayPrice(_TPV.ticket.total));
    },
    showTotalBlockRet: function () {
        $('#payment_total_ret').show();

        $('#id_btn_add_ticket_ret').show();
        $('#convert_coupon').hide();

        //Initialize Values
        //$('.payment_total').html(displayPrice(_TPV.ticket.total));
    },
    showZoomProducts: function () {
        $('#idProducts').append($('#products').html());
        $('#idProducts').dialog({modal: true});
        $('#idProducts').dialog({width: 640});
    },
    showManualProducts: function () {
        $('#idManualProducts').dialog({modal: true});
        $('#idManualProducts').dialog({width: 640});
    },
    showTicketOptions: function (idProduct) {
        $('.leftBlock').hide();
        $('#products').show();
        $('#ticketOptions').html($('#ticketLine' + idProduct).find('.colActions').html()).show();
        _TPV.addInfoProduct(idProduct);

        $('#tablaTicket tr').removeClass('lineSelected');
        $('#ticketLine' + idProduct).addClass('lineSelected');
    },
    hideTicketOptions: function (idProduct) {

        $('#ticketOptions').html($('#ticketLine' + idProduct).find('.colActions').html()).hide();

    },
    showHistoryOptions: function (idTicket) {

        $('#historyOptions .colActions').html($('#historyTicket' + idTicket).find('.colActions').html()).show();


        $('#historyOptions').show();
        $('#historyTable tr').removeClass('lineSelected');
        $('#historyTicket' + idTicket).addClass('lineSelected');
    },
    hideHistoryOptions: function (idTicket) {

        $('#historyOptions .colActions').html($('#historyTicket' + idTicket).find('.colActions').html()).hide();


        $('#historyOptions').hide();

    },
    showHistoryFacOptions: function (idTicket) {

        $('#historyFacOptions .colActions').html($('#historyFacTicket' + idTicket).find('.colActions').html()).show();


        $('#historyFacOptions').show();
        $('#historyFacTable tr').removeClass('lineSelected');
        $('#historyFacTicket' + idTicket).addClass('lineSelected');
    },
    hideHistoryFacOptions: function (idTicket) {

        $('#historyFacOptions .colActions').html($('#historyFacTicket' + idTicket).find('.colActions').html()).hide();


        $('#historyFacOptions').hide();

    },
    showStockOptions: function (idProduct, idWarehouse) {

        $('#stockOptions .colActions').html($('#stock' + idProduct + '_' + idWarehouse).find('.colActions').html()).show();


        $('#stockOptions').show();
        $('#storeTable tr').removeClass('lineSelected');
        $('#stock' + idProduct + '_' + idWarehouse).addClass('lineSelected');
    },
    hideStockOptions: function (idProduct, idWarehouse) {

        $('#stockOptions .colActions').html($('#stock' + idProduct + '_' + idWarehouse).find('.colActions').html()).hide();


        $('#stockOptions').hide();

    },

});

// CLASS TICKET LINE **********************************************************************
var TicketLine = jQuery.Class({
    init: function () {
        this.id = 0;
        this.idProduct = 0;
        this.ref = 0;
        this.label = '';
        this.description = '';
        this.discount = 0;
        this.cant = 1;
        this.idTicket = 0;
        this.localtax1_tx = 0;
        this.localtax2_tx = 0;
        this.tva_tx = 0;
        this.price_ht = 0; //pu_ht
        this.price_ttc = 0; //pu_ht+pu_tva
        this.total_ht = 0; //total_ht
        this.total_ht_without_discount = 0;
        this.price_min_ttc = 0;
        this.price_base_type = '';
        this.fk_product_type = 0;
        this.total_ttc = 0; //total_ht+total_tva+total_localtax1+total_localtax2
        this.total_ttc_without_discount = 0;
        this.diff_price = 0;
        this.orig_price = 0; //2Promo
        this.is_promo = 0;
        this.promo_desc = '';
	this.batchs = new Array();


    },
    getHtml: function () {
        var hide = "$('#info_product').toggle()";
        if (this.diff_price == 0) {
            if (_TPV.defaultConfig['module']['ttc'] == 0)
                return '<tr id="ticketLine' + this.idProduct + '" onclick="_TPV.ticket.showTicketOptions(' + this.idProduct + ')"><td class="idCol">' + this.idProduct + '</td><td class="description">' + this.label + '</td><td class="discount">' + this.discount + '%</td><td class="price">' + displayPrice(this.total_ht / this.cant) + '</td><td class="cant">' + this.cant + '</td><td class="total">' + displayPrice(this.total_ht) + '</td><td class="colActions"><a class="action edit" onclick="_TPV.ticket.editTicketLine(' + this.idProduct + ');"></a><a class="action delete" onclick="_TPV.ticket.deleteLine(' + this.idProduct + ');"></a><a class="action info" onclick="' + hide + '"></a><a class="action close" onclick="_TPV.ticket.hideTicketOptions(' + this.idProduct + ')"></a></td></tr>';
            else
                return '<tr id="ticketLine' + this.idProduct + '" onclick="_TPV.ticket.showTicketOptions(' + this.idProduct + ')"><td class="idCol">' + this.idProduct + '</td><td class="description">' + this.label + '</td><td class="discount">' + this.discount + '%</td><td class="price">' + displayPrice(this.total_ttc / this.cant) + '</td><td class="cant">' + this.cant + '</td><td class="total">' + displayPrice(this.total_ttc) + '</td><td class="colActions"><a class="action edit" onclick="_TPV.ticket.editTicketLine(' + this.idProduct + ');"></a><a class="action delete" onclick="_TPV.ticket.deleteLine(' + this.idProduct + ');"></a><a class="action info" onclick="' + hide + '"></a><a class="action close" onclick="_TPV.ticket.hideTicketOptions(' + this.idProduct + ')"></a></td></tr>';
        }
        else {
            var txt = ajaxDataSend('Translate', 'DiffPrice');
            if (_TPV.defaultConfig['module']['ttc'] == 0)
                return '<tr id="ticketLine' + this.idProduct + '" onclick="_TPV.ticket.showTicketOptions(' + this.idProduct + ')"><td class="idCol">' + this.idProduct + '</td><td class="description">' + this.label + '</td><td class="discount">' + this.discount + '%</td><td class="price"><img style="float: left; margin: 3% 0px 0px 26%;" src="img/alert.png" title="' + txt + '">  ' + displayPrice(this.total_ht / this.cant) + '</td><td class="cant">' + this.cant + '</td><td class="total">' + displayPrice(this.total_ht) + '</td><td class="colActions"><a class="action edit" onclick="_TPV.ticket.editTicketLine(' + this.idProduct + ');"></a><a class="action delete" onclick="_TPV.ticket.deleteLine(' + this.idProduct + ');"></a><a class="action info" onclick="' + hide + '"></a><a class="action close" onclick="_TPV.ticket.hideTicketOptions(' + this.idProduct + ')"></a></td></tr>';
            else
                return '<tr id="ticketLine' + this.idProduct + '" onclick="_TPV.ticket.showTicketOptions(' + this.idProduct + ')"><td class="idCol">' + this.idProduct + '</td><td class="description">' + this.label + '</td><td class="discount">' + this.discount + '%</td><td class="price"><img style="float: left; margin: 3% 0px 0px 26%;" src="img/alert.png" title="' + txt + '">  ' + displayPrice(this.total_ttc / this.cant) + '</td><td class="cant">' + this.cant + '</td><td class="total">' + displayPrice(this.total_ttc) + '</td><td class="colActions"><a class="action edit" onclick="_TPV.ticket.editTicketLine(' + this.idProduct + ');"></a><a class="action delete" onclick="_TPV.ticket.deleteLine(' + this.idProduct + ');"></a><a class="action info" onclick="' + hide + '"></a><a class="action close" onclick="_TPV.ticket.hideTicketOptions(' + this.idProduct + ')"></a></td></tr>';
        }
    },
    setLineByIdProducts: function (idProduct) {
        var info = new Object();
        info['product'] = idProduct;
        if (_TPV.ticket.customerId != 0) {
            info['customer'] = _TPV.ticket.customerId;
        }
        else {
            info['customer'] = _TPV.customerId;
        }
        var result = ajaxDataSend('getProduct', info);
        if (result.length > 0) {
            if (result[0]["stock"] == "all" || result[0]["stock"] > 0) {
                _TPV.products[idProduct] = result[0];

                //cada vez que se elige un producto se carga de base de datos
                var product = _TPV.products[idProduct];

                var data = new Object();
                data['customer'] = info['customer'];
                data['tva'] = product.tva_tx;
                var localtax = ajaxDataSend('getLocalTax', data)

                product["cant"] = 1;
                product["remise_percent_global"] = 0;
                product["localtax1_tx"] = localtax['1'];
                product["localtax2_tx"] = localtax['2'];
                product["buyer"] = info['customer'];

                var result = ajaxDataSend('calculePrice', product);
                this.is_promo = result["is_promo"];
                this.promo_desc = result["promo_desc"];

                if (localtax['1'] != 0 || localtax['2'] != 0) {
                    this.price_ht = result["pu_ht"];
                    this.price_ttc = parseFloat(result["pu_ht"]) + parseFloat(result["pu_tva"]);
                    this.total_ht = result["total_ht"];
                    this.total_ht_without_discount = result["total_ht_without_discount"];
                    this.total_ttc = parseFloat(result["total_ht"]) + parseFloat(result["total_tva"]);
                    this.total_ttc_without_discount = result["total_ttc_without_discount"];
                    this.orig_price = result["orig_price"];
                }
                else {
                    this.price_ht = product.price_ht;
                    this.price_ttc = product.price_ttc;
                    this.total_ht = product.price_ht;
                    this.total_ht_without_discount = product.price_ht;
                    this.total_ttc = product.price_ttc;
                    this.total_ttc_without_discount = product.price_ttc;
                    this.orig_price = product.orig_price;
                }
                this.idProduct = idProduct;
                this.ref = 0;
                this.label = product.label;
                this.description = product.description;
                this.localtax1_tx = localtax['1'];
                this.localtax2_tx = localtax['2'];
                this.tva_tx = product.tva_tx;
                this.idTicket = _TPV.ticket.id;
                this.price_min_ht = product.price_min_ht;
                this.price_min_ttc = product.price_min_ttc;
                this.price_base_type = product.price_base_type;
                this.fk_product_type = product.fk_product_type;
                this.remise_percent_global = 0;
                this.diff_price = product.diff_price;
		this.batch = product.batch;
            }
            else {
                //Muestro un error diciendo que no hay stock ni se le espera...
                var txt = ajaxDataSend('Translate', 'NoStockEnough');
                _TPV.showError(txt);
            }

            if (globalTicket != undefined) {

                //buscamos al producto que hace referencia
                var num = 0;
                while (this.idProduct != globalTicket.data.lines[num].idProduct) {
                    num++;
                }
                this.localtax1_tx = globalTicket.data.lines[num].localtax1_tx;
                this.localtax2_tx = globalTicket.data.lines[num].localtax2_tx;
                this.tva_tx = globalTicket.data.lines[num].tva_tx;
                this.price_ht = globalTicket.data.lines[num].price_ht;
                this.price_ttc = globalTicket.data.lines[num].price_ht * (1 + (globalTicket.data.lines[num].tva_tx / 100));
                this.total_ht = globalTicket.data.lines[num].total;
                this.total_ht_without_discount = globalTicket.data.lines[num].total_ht;
                this.total_ttc = globalTicket.data.lines[num].total_ttc;
                this.total_ttc_without_discount = globalTicket.data.lines[num].total_ttc;
                this.orig_price = product.orig_price;
            }
        }


    },
    setLineByIdLine: function (idProduct) {
        if (typeof _TPV.ticket.oldproducts == 'undefined') {
            return;
        }
        var lines = _TPV.ticket.oldproducts;
        var line = null;
        for (var i = 0; i < lines.length; i++) {
            if (lines[i]['idProduct'] == idProduct) {
                line = lines[i];
                break;
            }
        }
        if (!line)
            return 1;
        this.idProduct = idProduct;
        this.ref = 0;
        this.label = line.label;
        this.discount = line.discount;
        this.description = line.description;
        this.localtax1_tx = line.localtax1_tx;
        this.localtax2_tx = line.localtax2_tx;
        this.tva_tx = line.tva_tx;
        this.price = line.price;///(1-line.discount/100);
        this.cant = line.cant;
		this.price_ht = line.price_ht;///(1-line.discount/100);
        this.price_ttc = line.price_ht * (1 + (line.tva_tx/100));
        this.total_ht = line.total_ht;
		this.total_ttc = line.total_ttc;
        this.price_min_ttc = line.price_min_ttc;
        this.price_base_type = line.price_base_type;
        this.fk_product_type = line.fk_product_type;

        /*this.price_ht = line.total_ht / line.cant;*/
        /*this.price_ttc = line.total_ttc / line.cant;*/
        if (_TPV.ticket.discount_percent)
            this.remise_percent_global = _TPV.ticket.discount_percent;
        else
            this.remise_percent_global = 0;
        this.total_ht_without_discount = line.total_ht;
        this.total_ttc_without_discount = line.total_ttc;

        return 0;
    },
    setQuantity: function (cant) {
        number = parseFloat(cant);
        // Add Quantity
        this.cant = number;
    },
    setDiscount: function (discount) {
        quantitydiscount = parseFloat(discount);
        if (quantitydiscount > 100 || quantitydiscount < 0)
            quantitydiscount = 0;
        // Add Discount
        this.discount = quantitydiscount;
    },
    setPrice: function (new_price) {
        price = parseFloat(new_price);

        if (_TPV.defaultConfig['module']['ttc'] == 0) {
            price_old = Math.round(this.price_ht * 100) / 100;
            if (price == price_old)
                return;

            tva = parseFloat(this.tva_tx);
            if (price < this.price_min_ht) {
                var txt = ajaxDataSend('Translate', 'PriceMinError');
                _TPV.showError(txt);
            } else {
                this.price_ht = price;
                this.price_base_type = "HT";
            }
        }
        else {
            price_old = Math.round(this.price_ttc * 100) / 100;
            if (price == price_old)
                return;
            if (price < this.price_min_ttc) {
                var txt = ajaxDataSend('Translate', 'PriceMinError');
                _TPV.showError(txt);
            } else {
                this.price_ttc = price;
                this.price_base_type = "TTC";
            }
        }
    },
    setNote: function (note) {
        // Add Note
        this.note = note;

    },
    setBatch : function(batch,havebatch){
        if(havebatch != 0){
            this.batch = batch;
        }
    },
    setTotal: function (total) {
        // Add Total
        this.total = total;
        $('#ticketLine' + this.idProduct).find('.total').html(displayPrice(total));
    },
    showTotal: function () {
        var line = this;
        if (_TPV.ticket.type == 0) {
            line["remise_percent_global"] = 0;
            if (_TPV.ticket.customerId != 0) {
                line["socid"] = _TPV.ticket.customerId;
            }
            else {
                line["socid"] = _TPV.customerId;
            }

            if (!line["price_base_type"])
                if (_TPV.defaultConfig['module']['ttc'] == 0)
                    line["price_base_type"] = "HT";
                else
                    line["price_base_type"] = "TTC";

			if (_TPV.ticket.customerId != 0) {
				line['buyer'] = _TPV.ticket.customerId;
			}
			else {
				line['buyer'] = _TPV.customerId;
			}

            var result = ajaxDataSend('calculePrice', line);
            if (result['total_ttc'] < this.cant * this.price_min_ttc) {
                var txt = ajaxDataSend('Translate', 'PriceMinError');
                _TPV.showError(txt);
                this.discount = 0;
            }
            else {
                this.price_ht = result["pu_ht"];
                this.price_ttc = parseFloat(result["pu_ht"]) + parseFloat(result["pu_tva"]);
                this.total_ht = result["total_ht"];
                this.total_ht_without_discount = result["total_ht_without_discount"];
                this.total_ttc = parseFloat(result["total_ht"]) + parseFloat(result["total_tva"]);
                this.total_ttc_without_discount = result["total_ttc_without_discount"];
                this.orig_price = result["orig_price"];
                this.is_promo = result["is_promo"];
                this.promo_desc = result["promo_desc"];
            }
            $('#ticketLine' + this.idProduct).find('.cant').html(this.cant);
            $('#ticketLine' + this.idProduct).find('.discount').html(this.discount + '%');
            if (line.diff_price == 0)
                if (_TPV.defaultConfig['module']['ttc'] == 0)
                    $('#ticketLine' + this.idProduct).find('.price').html(displayPrice(result["pu_ht"]));
                else
                    $('#ticketLine' + this.idProduct).find('.price').html(displayPrice(result["pu_ttc"]));
            else {
                var txt = ajaxDataSend('Translate', 'DiffPrice');
                if (_TPV.defaultConfig['module']['ttc'] == 0)
                    $('#ticketLine' + this.idProduct).find('.price').html('<img style="float: left; margin: 3% 0px 0px 26%;" src="img/alert.png" title="' + txt + '"> ' + displayPrice(result["pu_ht"]) + '');
                else
                    $('#ticketLine' + this.idProduct).find('.price').html('<img style="float: left; margin: 3% 0px 0px 26%;" src="img/alert.png" title="' + txt + '"> ' + displayPrice(result["pu_ttc"]) + '');
            }
            if (_TPV.defaultConfig['module']['ttc'] == 0)
                $('#ticketLine' + this.idProduct).find('.total').html(displayPrice(this.total_ht));
            else
                $('#ticketLine' + this.idProduct).find('.total').html(displayPrice(this.total_ttc));
        }
        else {
            line["remise_percent_global"] = 0;
            if (_TPV.ticket.customerId != 0) {
                line["socid"] = _TPV.ticket.customerId;
            }
            else {
                line["socid"] = _TPV.customerId;
            }
            if (!line["price_base_type"])
                if (_TPV.defaultConfig['module']['ttc'] == 0)
                    line["price_base_type"] = "HT";
                else
                    line["price_base_type"] = "TTC";

			if (_TPV.ticket.customerId != 0) {
				line['buyer'] = _TPV.ticket.customerId;
			}
			else {
				line['buyer'] = _TPV.customerId;
			}

            var result = ajaxDataSend('calculePrice', line);
            if (result['total_ttc'] < this.cant * this.price_min_ttc) {
                var txt = ajaxDataSend('Translate', 'PriceMinError');
                _TPV.showError(txt);
                this.discount = 0;
            }
            else {
                this.price_ht = result["pu_ht"];
                this.price_ttc = parseFloat(result["pu_ht"]) + parseFloat(result["pu_tva"]);
                this.total_ht = result["total_ht"];
                this.total_ht_without_discount = result["total_ht_without_discount"];
                this.total_ttc = parseFloat(result["total_ht"]) + parseFloat(result["total_tva"]);
                this.total_ttc_without_discount = result["total_ttc_without_discount"];
                this.orig_price = result["orig_price"];
                this.is_promo = result["is_promo"];
                this.promo_desc = result["promo_desc"];
            }
            $('#ticketLine' + this.idProduct).find('.cant').html(this.cant);
            $('#ticketLine' + this.idProduct).find('.discount').html(this.discount + '%');
            if (line.diff_price == 0)
                if (_TPV.defaultConfig['module']['ttc'] == 0)
                    $('#ticketLine' + this.idProduct).find('.price').html(displayPrice(result["pu_ht"]));
                else
                    $('#ticketLine' + this.idProduct).find('.price').html(displayPrice(result["pu_ttc"]));
            else {
                var txt = ajaxDataSend('Translate', 'DiffPrice');
                if (_TPV.defaultConfig['module']['ttc'] == 0)
                    $('#ticketLine' + this.idProduct).find('.price').html('<img style="float: left; margin: 3% 0px 0px 26%;" src="img/alert.png" title="' + txt + '"> ' + displayPrice(result["pu_ht"]) + '');
                else
                    $('#ticketLine' + this.idProduct).find('.price').html('<img style="float: left; margin: 3% 0px 0px 26%;" src="img/alert.png" title="' + txt + '"> ' + displayPrice(result["pu_ttc"]) + '');
            }
            if (_TPV.defaultConfig['module']['ttc'] == 0)
                $('#ticketLine' + this.idProduct).find('.total').html(displayPrice(this.total_ht))
            else
                $('#ticketLine' + this.idProduct).find('.total').html(displayPrice(this.total_ttc))
            //$('#ticketLine'+this.idProduct).find('.total').html(displayPrice(this.total_ttc));
        }
        _TPV.ticket.calculeTotal();
    }
});


// CLASS CUSTOMER *******************************************************************
var Customer = jQuery.Class({
    init: function () {
        this.id = 0;
        this.nom = '';
        this.prenom = '';
        this.idprof1 = '';
        this.address = '';
        this.cp = '';
        this.ville = '';
        this.tel = '';
        this.email = '';
        this.batch= '';
    }

});

// CLASS PRODUCT ********************************************************
var Product = jQuery.Class({
    init: function () {
        this.id = 0;
        this.label = '';
        this.price_ht = 0;
        this.price_ttc = 0;
        this.ref = '';
        this.tax = 0;
        this.price_min_ht = 0;
        this.price_min_ttc = 0;

    }

});
//CLASS CASH ************************************************************
var Cash = jQuery.Class({
    init: function () {
        this.moneyincash = 0;
        this.type = 1;
        this.printer = 1;
        this.employeeId = 0;
        this.mail = 1;

    }

});


// CLASS TPV  ***********************************************************
var TPV = jQuery.Class({

    init: function () {
        this.categories = new Array();
        this.products = new Array();
        this.places = new Array();
        this.ticket = new Ticket();
        this.activeIdProduct = 0;
        this.employeeId = 0;
        this.barcode = 0;
        this.infoProduct = 0;
        this.defaultConfig = new Array();
        this.ticketState = 0; // 0 => Normal, 1 => Blocked to add products, 2 => Return products
        this.cash = new Cash();
        this.cashId = 0;
        this.warehouseId = 0;
        this.fullscreen = 0;
        this.faclimit = 0;
        this.discount;
        this.points = 0;
        this.coupon = 0;
        this.showingProd = 0;
    },

    setButtonEvents: function () {
        $('#btnNewTicket').click(function () {
            _TPV.ticket.newTicket();
        });

        $('#btnOkTicket').click(function () {
            _TPV.ticket.okTicket();
        });
        $('#btnHistory').click(function () {
            _TPV.getHistory();
        });
        $('#btnSaveTicket').click(function () {
            _TPV.ticket.saveTicket();
        });
        $('#btnCancelTicket').click(function () {
            _TPV.ticket.cancelTicket();
        });
        $('#btnReturnTicket').click(function () {
            _TPV.ticketState = 2;
            _TPV.ticket.setButtonState(false);
            var id = _TPV.ticket.idsource;
            var discount_percent = _TPV.ticket.discount_percent;
            var discount_qty = _TPV.ticket.discount_qty;
            var lines = _TPV.ticket.oldproducts;
            var ret_points = _TPV.ticket.ret_points;
            var customerid = _TPV.ticket.customerId;
            var customername = $('#infoCustomer_').text();
            var mode = _TPV.ticket.mode;
            _TPV.ticket.newTicket();
            _TPV.ticket.idsource = id;
            _TPV.ticket.oldproducts = lines;
            _TPV.ticket.ret_points = ret_points;
            _TPV.ticket.discount_percent = discount_percent;
            _TPV.ticket.discount_qty = discount_qty;
            _TPV.ticket.type = 1;
            _TPV.ticket.customerId = customerid;
            _TPV.ticket.mode = mode;
            $('#infoCustomer').html(customername);
            $('#infoCustomer_').html(customername);
            $('#btnTicketRef').show();
        });
        $('#btnViewTicket').click(function () {
            _TPV.ticket.viewTicket();
        });

        $('#btnAddCustomer').click(function () {
            _TPV.ticket.showAddCustomer();
        });
        $('#btnNewCustomer').click(function () {
            _TPV.ticket.showAddCustomer();
        });
        $('#btnAddDiscount').click(function () {
            _TPV.ticket.addDiscount();
        });
        $('#btnAddProduct').click(function () {
            _TPV.ticket.showAddProduct();
        });
        $('#btnTicketNote').click(function () {
            _TPV.ticket.addTicketNote();
        });
        $('#btnShowManualProducts').click(function () {
            _TPV.ticket.showManualProducts();
        });
        $('#btnLogout').click(function () {
            window.location.href = "./disconect.php";
        });
        $('#btnZoomCategories').click(function () {
            _TPV.ticket.showZoomProducts();
        });
        $('#btnAddProductCart').click(function () {
            _TPV.ticket.addProductLine();
        });
        $('#btnHideInfo').click(function () {
            $('#short_description_content').toggle();
        });
        $('#btnHideInfoSt').click(function () {
            $('#short_description_content_st').toggle();
        });

        // Filter Product Search Events
        $('#id_product_search').live("keypress", function (e) {
            if (e.keyCode == 13 || e.which == 13) {
                _TPV.searchProduct();
            }
            if (_TPV.defaultConfig['terminal']['barcode'] == 1) {
                $('#id_product_search').focus();
            }
        });
        $('#img_product_search').click(function () {
            _TPV.searchProduct();
        });
        // Filter Sotck products Search Events
        $('#id_stock_search').live("keypress", function (e) {
            if (e.keyCode == 13 || e.which == 13) {
                _TPV.searchByStock(1, '');
            }
        });
        $('#img_stock_search').click(function () {
            _TPV.searchByStock(1, '');
        });
        // Filter Cusotmer Search
        $('#id_customer_search_').live("keypress", function (e) {
            if (e.keyCode == 13 || e.which == 13) {
                _TPV.searchCustomer();
            }
        });
        $('#img_customer_search').click(function () {
            _TPV.searchCustomer();
        });
        // Filter batch Search
        $('#id_batch_search_').live("keypress", function (e) {
            if (e.keyCode == 13 || e.which == 13) {
                _TPV.searchBatch(_TPV.activeIdProduct);
            }
        });
        $('#img_batch_search').click(function () {
            _TPV.searchBatch(_TPV.activeIdProduct);
        });

        $('#tabStock').click(function () {
            $('#info_product_st').hide();
            _TPV.countByStock();
            //_TPV.searchByStock();
        });
        $('#tabPlaces').click(function () {
            _TPV.searchByPlace();
        });
        $('#id_place_search').live("keypress", function (e) {
            if (e.keyCode == 13 || e.which == 13) {
                _TPV.searchByPlace();
            }
        });
        $('#tabHistory').click(function () {
            _TPV.searchByRef(-1);
            _TPV.countByRef();
        });
        $('#tabHistoryFac').click(function () {
            _TPV.searchByRefFac(-1);
            _TPV.countByRefFac();
        });

        // Filter Reference Search
        $('#id_ref_search').live("keypress", function (e) {
            if (e.keyCode == 13 || e.which == 13) {
                _TPV.searchByRef(-1);
            }
        });
        $('#img_ref_search').click(function () {
            _TPV.searchByRef(-1);
        });
        $('#id_ref_fac_search').live("keypress", function (e) {
            if (e.keyCode == 13 || e.which == 13) {
                _TPV.searchByRefFac(-1);
            }
        });
        $('#img_ref_fac_search').click(function () {
            _TPV.searchByRefFac(-1);
        });
        $('#id_selectProduct').change(function () {
            if ($(this).val() != 0) {
                _TPV.ticket.addLine($(this).val());
                $('#divSelectProducts').hide();
                $('#id_product_search').val('');
            }
        });
		/*$('.payment_types').each(function(){
		 $(this).click(function() {
		 $('#payment_coupon').hide();
		 if(_TPV.points != null && _TPV.ticket.mode!=0){
		 $('#payment_points').show();
		 $('#payment_total_points').hide();
		 }
		 else
		 {$('#payment_total_points').show();}
		 $('#id_btn_add_ticket').show();
		 $('.payment_types').removeClass('btnon');
		 $(this).addClass('btnon');
		 _TPV.ticket.setPaymentType($(this).find('a:first').attr('id').substring(7));
		 });
		 });*/
        $('.series_types').each(function () {
            $(this).click(function () {

                $('.series_types').removeClass('btnon');
                $(this).addClass('btnon');
                _TPV.ticket.serie = $(this).find('a:first').attr('id').substring(9);
                showLeftContent('#payType');
                _TPV.ticket.showTotalBlock();
            });
        });
        $('#id_btn_coupon').click(function () {
            _TPV.ticket.showCoupon();
        });
        $('#line_quantity').keyup(function () {//normal mode
            _TPV.checkStock();
        });
        $('#line_quantity').blur(function () {//tactil mode
            _TPV.checkStock();
        });
        $('#points_client_id').keyup(function () {//normal mode
            _TPV.pointsClient();
        });
        $('#points_client_id').blur(function () {//tactil mode
            _TPV.pointsClient();
        });
        $('#pay_client_0').keyup(function () {//normal mode
            _TPV.payClient();
        });
        $('#pay_client_0').blur(function () {//tactil mode
            _TPV.payClient();
        });
        $('#pay_client_1').keyup(function () {//normal mode
            _TPV.payClient();
        });
        $('#pay_client_1').blur(function () {//tactil mode
            _TPV.payClient();
        });
        $('#pay_client_2').keyup(function () {//normal mode
            _TPV.payClient();
        });
        $('#pay_client_2').blur(function () {//tactil mode
            _TPV.payClient();
        });
        $('#pay_client_ret_0').keyup(function () {//normal mode
            _TPV.payClientRet();
        });
        $('#pay_client_ret_0').blur(function () {//tactil mode
            _TPV.payClientRet();
        });
        $('#pay_client_ret_1').keyup(function () {//normal mode
            _TPV.payClientRet();
        });
        $('#pay_client_ret_1').blur(function () {//tactil mode
            _TPV.payClientRet();
        });
        $('#pay_client_ret_2').keyup(function () {//normal mode
            _TPV.payClientRet();
        });
        $('#pay_client_ret_2').blur(function () {//tactil mode
            _TPV.payClientRet();
        });
        $('#pay_all_0').click(function () {//tactil mode
            if ($('#pay_client_0').val() != "")
                var prev = $('#pay_client_0').val();
            else
                var prev = 0;
            $('#pay_client_0').val(displayPrice(_TPV.ticket.difpayment + parseFloat(prev)));
            _TPV.payClient();
        });
        $('#pay_all_1').click(function () {//tactil mode
            if ($('#pay_client_1').val() != "")
                var prev = $('#pay_client_1').val();
            else
                var prev = 0;
            $('#pay_client_1').val(displayPrice(_TPV.ticket.difpayment + parseFloat(prev)));
            _TPV.payClient();
        });
        $('#pay_all_2').click(function () {//tactil mode
            if ($('#pay_client_2').val() != "")
                var prev = $('#pay_client_2').val();
            else
                var prev = 0;
            $('#pay_client_2').val(displayPrice(_TPV.ticket.difpayment + parseFloat(prev)));
            _TPV.payClient();
        });
        $('#pay_all_ret_0').click(function () {//tactil mode
            if ($('#pay_client_ret_0').val() != "")
                var prev = $('#pay_client_ret_0').val();
            else
                var prev = 0;
            $('#pay_client_ret_0').val(displayPrice(_TPV.ticket.difpayment + parseFloat(prev)));
            _TPV.payClientRet();
        });
        $('#pay_all_ret_1').click(function () {//tactil mode
            if ($('#pay_client_ret_1').val() != "")
                var prev = $('#pay_client_ret_1').val();
            else
                var prev = 0;
            $('#pay_client_ret_1').val(displayPrice(_TPV.ticket.difpayment + parseFloat(prev)));
            _TPV.payClientRet();
        });
        $('#pay_all_ret_2').click(function () {//tactil mode
            if ($('#pay_client_ret_2').val() != "")
                var prev = $('#pay_client_ret_2').val();
            else
                var prev = 0;
            $('#pay_client_ret_2').val(displayPrice(_TPV.ticket.difpayment + parseFloat(prev)));
            _TPV.payClientRet();
        });

        $('#id_btn_tpvtactil').click(function () {
            if ($(this).hasClass('on')) {
                $(this).removeClass('on');
                $(this).addClass('off');
                _TPV.tpvTactil(false);
            }
            else {
                $(this).addClass('on');
                $(this).removeClass('off');
                _TPV.tpvTactil(true);
            }
        });
        $('#id_btn_barcode').click(function () {
            if ($(this).hasClass('on')) {
                $(this).removeClass('on');
                $(this).addClass('off');
                _TPV.barcode = 0;
            }
            else if ($(this).hasClass('off')) {
                $(this).addClass('on');
                $(this).removeClass('off');
                _TPV.barcode = 1;
            }
        });


        $('#id_btn_infoproduct').click(function () {
            if ($(this).hasClass('on')) {
                $(this).removeClass('on');
                $(this).addClass('off');
                _TPV.showInfoProduct(false);
            }
            else {
                $(this).addClass('on');
                $(this).removeClass('off');
                _TPV.showInfoProduct(true);
            }
        });
        $('#id_btn_closeproduct').click(function () {
            $('#products').toggle();
            //$('#productSearch').toggle();
        });
        $('#id_btn_fullscreen').click(function () {

            if (_TPV.fullscreen == 0) {
                var docElm = document.documentElement;
                if (docElm.requestFullscreen) {
                    docElm.requestFullscreen();
                }
                else if (docElm.mozRequestFullScreen) {
                    docElm.mozRequestFullScreen();
                }
                else if (docElm.webkitRequestFullScreen) {
                    docElm.webkitRequestFullScreen(docElm.ALLOW_KEYBOARD_INPUT);
                }
                _TPV.fullscreen = 1;
            }
            else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
                else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                }
                else if (document.webkitCancelFullScreen) {
                    document.webkitCancelFullScreen();
                }
                _TPV.fullscreen = 0;
            }

        });
        $('#id_btn_closecash').click(function () {
            var money = ajaxDataSend('getMoneyCash', null);
            $('#id_terminal_cash').val(displayPrice(money));
            $('#id_money_cash').val('');
            $('#idCloseCash').dialog({modal: true});
            $('#idCloseCash').dialog({width: 440});
            $('#id_btn_close_cash').unbind('click');

            $('#id_btn_close_cash').click(function () {

                if ($('#id_money_cash').val())
                    _TPV.cash.moneyincash = $('#id_money_cash').val();
                _TPV.cash.employeeId = _TPV.employeeId;
                var result = ajaxDataSend('closeCash', _TPV.cash);
                $('#idCloseCash').dialog('close');
                if (!result)
                    return;
                if (_TPV.cash.type == 1) {
                    if (_TPV.defaultConfig['module']['print'] > 0 || _TPV.defaultConfig['module']['mail'] > 0) {
                        $('#idCashMode').dialog({modal: true});
                        $('#idCashMode').dialog({width: 400});

                        $('#id_btn_cashPrint').click(function () {
                            $('#id_btn_cashPrint').unbind('click');
                            _TPV.printing('closecash', result);
                            $('#idCashMode').dialog("close");
                            if (_TPV.defaultConfig['module']['print_mode'] == 0)
                                $('#btnLogout').click();
                        });

                        $('#id_btn_cashMail').click(function () {
                            $('#id_btn_cashMail').unbind('click');
                            _TPV.mailCash(result, _TPV.defaultConfig['terminal']['id'])
                            $('#idCashMode').dialog("close");

                        });
                    }
                    else {
                        $('#btnLogout').click();
                    }
                }
            });
        });
        $('#btnTotalNote').click(function () {
            _TPV.showNotes();
        });
        $('#btnChangeCustomer').click(function () {
            _TPV.changeCustomer();
        });
        $('#btnChangePlace').click(function () {
            _TPV.searchByPlace();
        });
        $('.close_types').click(function () {
            $('.close_types').removeClass('btnon');
            $(this).addClass('btnon');
            _TPV.cash.type = $(this).find('a:first').attr('id').substring(9);
        });
        $('.print_close_types').click(function () {
            $('.print_close_types').removeClass('btnon');
            $(this).addClass('btnon');
            _TPV.cash.printer = $(this).find('a:first').attr('id').substring(14);
        });
        $('.mail_close_types').click(function () {
            $('.mail_close_types').removeClass('btnon');
            $(this).addClass('btnon');
            _TPV.cash.mail = $(this).find('a:first').attr('id').substring(14);
        });
        $('.type_discount').click(function () {
            $('.type_discount').removeClass('btnon');
            $(this).addClass('btnon');
            if ($(this).find('a:first').attr('id') == 'btnTypeDiscount0') {
                $('#typeDiscount0').show();
                $('#typeDiscount1').hide();
                $('#typeDiscount1').val(0);
            }
            else {
                $('#typeDiscount1').show();
                $('#typeDiscount0').hide();
                $('#typeDiscount0').val(0);
            }
        });

        $('#id_btn_employee').click(function () {


            $('#idEmployee').dialog({width: 400});
            $('#idEmployee a').unbind('click');

            $('#idEmployee a').click(function () {

                var login = $(this).attr('login');
                var userid = $(this).attr('id').substring(12);
                var username = $(this).html();
                var photo = $(this).attr('photo');

                $('#idEmpPass').dialog({modal: true});
                $('#idEmpPass').dialog({width: 400});

                $('#id_btn_empPass').unbind('click');
                $('#id_btn_empPass').click(function () {
                    var pass = new Object();
                    pass.pass = $('#password').val();
                    pass.login = login;
                    pass.userid = userid;

                    var result = ajaxDataSend('checkPassword', pass);

                    if (result > 0) {
                        _TPV.employeeId = userid;
                        $('#id_user_name').html(username);
                        $('#id_image').attr("src", photo);
                    }
                    $('#password').val('');
                    $('#idEmpPass').dialog('close');
                    $('#idEmployee').dialog('close');

                });
            });
        });

        $('#id_btn_opendrawer').click(function () {
            ajaxDataSend('addPrint', "D");
        });


    },
    checkStock: function () {
        cant = $('#line_quantity').val();
        if (_TPV.products[this.activeIdProduct]["stock"] != "all") {
            if (parseFloat(cant) > parseFloat(_TPV.products[_TPV.activeIdProduct]["stock"])) {
                $('#line_quantity').val(_TPV.products[_TPV.activeIdProduct]["stock"]);
            }
        }

    },
    pointsClient: function () {
        _TPV.ticket.points = $('#points_client_id').val();
        if (parseFloat(_TPV.ticket.points) > parseFloat(_TPV.points)) {
            _TPV.ticket.points = _TPV.points;
        }
        if (parseFloat(_TPV.ticket.points) * parseFloat(_TPV.defaultConfig['module']['points']) > parseFloat(_TPV.ticket.total)) {
            _TPV.ticket.points = parseFloat(_TPV.ticket.total) / parseFloat(_TPV.defaultConfig['module']['points']);
        }
        $('#points_client_id').val(_TPV.ticket.points);
        discount = _TPV.ticket.points * _TPV.defaultConfig['module']['points'];
        _TPV.ticket.total_with_points = _TPV.ticket.total - discount;

        $('.payment_total').html(displayPrice(_TPV.ticket.total_with_points));
        if (_TPV.ticket.points > 0) {
            _TPV.ticket.difpayment = _TPV.ticket.total_with_points - _TPV.ticket.customerpay;
        }
        else {
            _TPV.ticket.difpayment = _TPV.ticket.total - _TPV.ticket.customerpay;
        }
        if (_TPV.ticket.difpayment > 0)
            $('.payment_return').addClass('negat');
        else
            $('.payment_return').removeClass('negat');
        $('.payment_return').html(displayPrice(_TPV.ticket.difpayment));
        _TPV.ticketState.customerpay = _TPV.ticket.total_with_points;
    },
    payClient: function () {
        if ($('#pay_client_0').val() != "")
            var mode1 = $('#pay_client_0').val();
        else
            var mode1 = 0;
        if ($('#pay_client_1').val() != "")
            var mode2 = $('#pay_client_1').val();
        else
            var mode2 = 0;
        if ($('#pay_client_2').val() != "")
            var mode3 = $('#pay_client_2').val();
        else
            var mode3 = 0;

        if (typeof mode2 == 'undefined') mode2 = 0;
        if (typeof mode3 == 'undefined') mode3 = 0;

        _TPV.ticket.customerpay = parseFloat(mode1) + parseFloat(mode2) + parseFloat(mode3);
        _TPV.ticket.customerpay1 = parseFloat(mode1);
        _TPV.ticket.customerpay2 = parseFloat(mode2);
        _TPV.ticket.customerpay3 = parseFloat(mode3);
        if (_TPV.ticket.points > 0) {
            _TPV.ticket.difpayment = _TPV.ticket.total_with_points - _TPV.ticket.customerpay;
        }
        else {
            _TPV.ticket.difpayment = _TPV.ticket.total - _TPV.ticket.customerpay;
        }
        if (_TPV.ticket.difpayment > 0)
            $('.payment_return').addClass('negat');
        else
            $('.payment_return').removeClass('negat');
        $('.payment_return').html(displayPrice(_TPV.ticket.difpayment));
    },

    payClientRet: function () {
        if ($('#pay_client_ret_0').val() != "")
            var mode1 = $('#pay_client_ret_0').val();
        else
            var mode1 = 0;
        if ($('#pay_client_ret_1').val() != "")
            var mode2 = $('#pay_client_ret_1').val();
        else
            var mode2 = 0;
        if ($('#pay_client_ret_2').val() != "")
            var mode3 = $('#pay_client_ret_2').val();
        else
            var mode3 = 0;
        if (typeof mode2 == 'undefined') mode2 = 0;
        if (typeof mode3 == 'undefined') mode3 = 0;

        _TPV.ticket.customerpay = parseFloat(mode1) + parseFloat(mode2) + parseFloat(mode3);
        if (_TPV.ticket.customerpay > Math.min(_TPV.ticket.total, _TPV.ticket.ret_points)) {
            $('#pay_client_ret_0').val(0);
            $('#pay_client_ret_1').val(0);
            $('#pay_client_ret_2').val(0);
            _TPV.ticket.customerpay = 0;
            _TPV.ticket.customerpay1 = 0;
            _TPV.ticket.customerpay2 = 0;
            _TPV.ticket.customerpay3 = 0;
        }

        _TPV.ticket.customerpay1 = parseFloat(mode1);
        _TPV.ticket.customerpay2 = parseFloat(mode2);
        _TPV.ticket.customerpay3 = parseFloat(mode3);
        _TPV.ticket.difpayment = Math.min(_TPV.ticket.total, _TPV.ticket.ret_points) - _TPV.ticket.customerpay;

        if (_TPV.ticket.difpayment > 0)
            $('.payment_return_ret').addClass('negat');
        else
            $('.payment_return_ret').removeClass('negat');
        $('.payment_return_ret').html(displayPrice(_TPV.ticket.difpayment));
    },

    getTicket: function (idTicket, edit) {
        if (edit) {
            $('#idTotalNote').dialog("close");
            _TPV.ticketState = 0;
            $('#btnReturnTicket').hide();
            $('#btnTicketRef').hide();
            $('#btnSaveTicket').show();
            $('#btnAddDiscount').show();
            $('#btnOkTicket').show();
            $('#btnTicketNote').show();

        }
        else {
            if (_TPV.ticketState != 1)
                $('#btnReturnTicket').show();
            $('#btnTicketRef').show();
            $('#btnSaveTicket').hide();
            $('#btnAddDiscount').hide();
            $('#btnOkTicket').hide();
            $('#btnTicketNote').hide();
            _TPV.ticketState = 1;

        }

        if (typeof idTicket != 'undefined') {
            var result = ajaxDataSend('getTicket', idTicket);
            globalTicket = result;
            $.each(result, function (id, item) {
                _TPV.ticket.init();
                _TPV.ticket.id = item['id'];
                $('#btnTicketRef').html(item['ref']);
                _TPV.ticket.payment_type = item['payment_type'];
                _TPV.ticket.type = item['type'];

                if (typeof item['discount_percent'] != 'undefined')
                    _TPV.ticket.discount_percent = item['discount_percent'];
                else
                    _TPV.ticket.discount_percent = 0;
                if (typeof item['discount_qty'] != 'undefined')
                    _TPV.ticket.discount_qty = item['discount_qty'];
                else
                    _TPV.ticket.discount_qty = 0;

                _TPV.ticket.customerpay = item['customerpay'];
                _TPV.ticket.difpayment = item['difpayment'];
                _TPV.ticket.customerId = item['customerId'];
                $('#infoCustomer').html(item['customerName']);
                $('#infoCustomer_').html(item['customerName']);
                _TPV.points = item['points'];
                _TPV.coupon = item['coupon'];
                _TPV.ticket.state = item['state'];
                //_TPV.ticket.total = item['total_ttc'];
                _TPV.ticket.id_place = item['id_place'];
                _TPV.ticket.note = item['note'];

                if (!edit) {
                    _TPV.ticket.idsource = idTicket;
                    _TPV.ticket.oldproducts = item['lines'];
                    _TPV.ticket.ret_points = item['ret_points'];
                }
                $('#tablaTicket > tbody tr').remove();
                var total_dis = 0;
                $.each(item['lines'], function (idline, line) {
                    if (!edit) {
                        var totalLine = 0;
                        var discount = 1;
                        if (line['discount'] != 0)
                            discount = 1 - line['discount'] / 100;
                        //line['price_ttx']  = line['price_ttx']*(1+discount);
                        total_dis = total_dis + parseFloat(line['remise']) * ((parseFloat(line['tva_tx']) + parseFloat(line['localtax1_tx']) + parseFloat(line['localtax2_tx'])) / 100 + 1)
                        var tr = '<tr id="ticketLine' + line['idProduct'] + '"><td class="idCol" >' + line['idProduct'] + '</td><td class="description">' + line['label'] + '</td><td class="discount">' + line['discount'] + '%</td><td class="price">' + displayPrice(line['total_ttc'] / line['cant']) + '</td><td class="cant">' + line['cant'] + '</td><td class="total">' + displayPrice(line['total_ttc']) + '</td>';
                        tr = tr + '</tr>';

                        $('#tablaTicket > tbody:last').prepend(tr);
                    }
                    else {
                        line['discount'] = line['discount'] - _TPV.ticket.discount_percent;
                        //_TPV.ticket.addManualProduct(line['idProduct'],line['cant'],line['discount'],line['total_ttc']);
                        _TPV.ticket.addManualProduct(line['idProduct'], line['cant'], line['discount'], line['price_ht'], line['note']);
                    }
                });
                if (!edit) {
                    _TPV.ticket.total = item['total_ttc'];
                    $('#totalDiscount').html(displayPrice(total_dis));
                    $('#totalTicket').html(displayPrice(_TPV.ticket.total));
                    $('#totalTicketinv').html(displayPrice(_TPV.ticket.total));
                    //_TPV.ticket.calculeDiscountTotal(total);
                }
                if (item['id_place']) {
                    $('#totalPlace').html(_TPV.places[item['id_place']]);
                }
                else {
                    $('#totalPlace').html('');
                }
                showTicketContent();
            });
            globalTicket = null;
        }
    },


    getFacture: function (idTicket, edit) {
        if (edit) {
            _TPV.ticketState = 0;
            $('#btnReturnTicket').hide();
            $('#btnTicketRef').hide();
            $('#btnSaveTicket').show();
            $('#btnAddDiscount').show();
            $('#btnOkTicket').show();
            $('#btnTicketNote').show();
        }
        else {
            if (_TPV.ticketState != 1)
                $('#btnReturnTicket').show();
            $('#btnTicketRef').show();
            $('#btnSaveTicket').hide();
            $('#btnAddDiscount').hide();
            $('#btnOkTicket').hide();
            $('#btnTicketNote').hide();
            _TPV.ticketState = 1;

        }

        if (typeof idTicket != 'undefined') {
            var result = ajaxDataSend('getFacture', idTicket);
            $.each(result, function (id, item) {
                _TPV.ticket.init();
                _TPV.ticket.id = item['id'];
                $('#btnTicketRef').html(item['ref']);
                _TPV.ticket.payment_type = item['payment_type'];
                _TPV.ticket.type = item['type'];
                if (typeof item['discount_percent'] != 'undefined')
                    _TPV.ticket.discount_percent = item['discount_percent'];
                else
                    _TPV.ticket.discount_percent = 0;
                if (typeof item['discount_qty'] != 'undefined')
                    _TPV.ticket.discount_qty = item['discount_qty'];
                else
                    _TPV.ticket.discount_qty = 0;
                _TPV.ticket.customerId = item['customerId'];
                _TPV.ticket.mode = 1;//Para diferencia de los tickets a la hora de hacer devoluciones
                $('#infoCustomer').html(item['customerName']);
                $('#infoCustomer_').html(item['customerName']);
                _TPV.ticket.state = item['state'];
                if (!edit) {
                    _TPV.ticket.idsource = idTicket;
                    _TPV.ticket.oldproducts = item['lines'];
                    _TPV.ticket.ret_points = item['ret_points'];
                }
                $('#tablaTicket > tbody tr').remove();
                var total = 0;
                $.each(item['lines'], function (idline, line) {
                    if (!edit) {
                        var totalLine = 0;
                        var discount = 1;
                        _TPV.ticket.discount_percent = 0;
                        //line['discount']=line['discount']-_TPV.ticket.discount_percent;
                        if (line['discount'] != 0)
                            discount = 1 - line['discount'] / 100;
                        totalLine = parseFloat(line['total_ttc']);
                        total += totalLine;
                        totalLine = displayPrice(totalLine);
                        var tr = '<tr id="ticketLine' + line['idProduct'] + '"><td class="idCol" >' + line['idProduct'] + '</td><td class="description">' + line['label'] + '</td><td class="discount">' + line['discount'] + '%</td><td class="price">' + displayPrice(line['price_ht'] * (1 + line['tva_tx'] / 100)) + '</td><td class="cant">' + line['cant'] + '</td><td class="total">' + totalLine + '</td>';
                        tr = tr + '</tr>';

                        $('#tablaTicket > tbody:last').prepend(tr);
                    }
                    else {
                        _TPV.ticket.addManualProduct(line['idProduct'], line['cant'], line['discount'])
                    }
                });
                if (!edit) {
                    _TPV.ticket.total = item['total_ttc'];
                    _TPV.ticket.calculeDiscountTotal(total);
                    //$('#totalTicket').html(displayPrice(total));
                }
                showTicketContent();
            });
        }
    },

    countByStock: function () {

        var result = ajaxDataSend('countProduct', _TPV.warehouseId);

        $('#stockNoSell').html(result["no_sell"]);

        $('#stockSell').html(result["sell"]);

        $('#stockWith').html(result["stock"]);

        $('#stockWithout').html(result["no_stock"]);

        $('#stockBest').html(result["best_sell"]);

        $('#stockWorst').html(result["worst_sell"]);

    },

    searchByStock: function (mode, warehouse) {
        var filter = new Object();
        filter.search = $('#id_stock_search').val();
        filter.mode = mode;
        filter.warehouse = warehouse;
        var result = ajaxDataSend('searchStocks', filter);
        var actionsHtml = '';
        $("#storeTable tr.data").remove();
        $.each(result, function (id, item) {
            actionsHtml = '';
            if (item['warehouseId'] == _TPV.warehouseId) {
                if (item['flag'] == 1 || item['stock'] > 0) {
                    actionsHtml = '<a class="accion addline" onclick="_TPV.ticket.addLine(' + item['id'] + ');"></a>';
                }
            }
            var hide = "$('#info_product_st').toggle()";
            actionsHtml += '<a class="accion info" onclick="' + hide + '"></a><a class="action close" onclick="_TPV.ticket.hideStockOptions(' + item['id'] + '_' + item['warehouseId'] + ')"></a>';

            $('#storeTable').append('<tr id="stock' + item['id'] + '_' + item['warehouseId'] + '" onclick="_TPV.ticket.showStockOptions(' + item['id'] + ',' + item['warehouseId'] + ');_TPV.addInfoProductSt(' + item['id'] + ');" class="data"><td>' + item['id'] + '</td><td>' + item['ref'] + '</td><td>' + item['label'] + '</td><td>' + item['stock'] + '</td><td>' + item['warehouse'] + '</td><td class="colActions"  style="text-align:center">' + actionsHtml + '</tr>');
            //$('#historyTable').append('<tr id="historyTicket'+item['id']+'" onclick="_TPV.ticket.showHistoryOptions('+item['id']+')" class="data"><td><a class="icontype state'+item['statut']+' type'+item['type']+'"></a>'+item['ticketnumber']+'</td><td>'+date+'</td><td>'+item['terminal']+'</td><td>'+item['seller']+'</td><td>'+item['client']+'</td><td style="text-align:right;">'+displayPrice(item['amount'])+'</td><td class="colActions"  style="text-align:center">'+actionsHtml+'</tr>');
        });
    },

    searchProduct: function () {
        var data = new Object;
        data['search'] = $('#id_product_search').val();
        data['warehouse'] = _TPV.warehouseId;
        data['ticketstate'] = _TPV.ticket.type;
        data['customer'] = _TPV.ticket.customerId;
        var result = ajaxDataSend('searchProducts', data);
        $("#id_selectProduct option").remove();
        $('#id_selectProduct').append(
            $('<option></option>').val(0).html('Productos ' + result.length)
        );
        $.each(result, function (id, item) {
                if (_TPV.defaultConfig['module']['ttc'] == 0) {
					$('#id_selectProduct').append(
						$('<option></option>').val(item['id']).html(item['label'] + ' --> ' + displayPrice(item['price_ht']) + '  -  ' + item['warehouseName'])
					);
				}
                else {
					$('#id_selectProduct').append(
						$('<option></option>').val(item['id']).html(item['label'] + ' --> ' + displayPrice(item['price_ttc']) + '  -  ' + item['warehouseName'])
					);
				}
        });
        if (_TPV.barcode == 1) {
            $('#id_product_search').val('');
            $('#divSelectProducts').show();

        }
        else if (result.length == 1) {

            _TPV.ticket.addLine(result[0]['id']);
            $('#divSelectProducts').hide();
            if (_TPV.defaultConfig['terminal']['barcode'] == 1) {
                $('#id_product_search').focus();
            }
            $('#id_product_search').val('');

        }
        else {
            $('#divSelectProducts').show();
        }
    },

    searchCustomer: function () {
        var result = ajaxDataSend('searchCustomer', $('#id_customer_search_').val());
        $("#customerTable_ tr.data").remove();
        var win = "$('#idChangeCustomer').dialog('close')";
        $.each(result, function (id, item) {
            $('#customerTable_').append('<tr class="data"><td class="itemId" style="display:none">' + item['id'] + '</td><td class="itemDni">' + item['profid1'] + '</td><td class="itemName">' + item['nom'] + '</td><td class="action add"><a class="action addcustomer" onclick="_TPV.ticket.addTicketCustomer(' + item['id'] + ',\'' + item['nom'] + '\',' + item['remise'] + ',' + item['coupon'] + ',' + item['points'] + ');' + win + ';"></a></td></tr>');
        });
    },

    searchBatch: function (idProduct) {
        var data = new Object();
        data['prodid'] = idProduct;
        data['batch'] = $('#id_batch_search_').val();

        var result = ajaxDataSend('searchBatch', data);
        $("#batchTable_ tr.data").remove();
        var win = "$('#idgetBatch').dialog('close')";
        $.each(result, function (id, item) {
            $('#batchTable_').append('<tr class="data"><td class="itemId">' + item['batch'] + '</td><td class="itemSellby">' + item['sellby'] + '</td><td class="itemEatby">' + item['eatby'] + '</td><td class="action add"><a class="action addbatch" onclick="_TPV.ticket.addBatchLine('  + idProduct + ',\'' + item['batch']  + '\',' + item['stock'] +');' + win + ';"></a></td></tr>');
        });
    },
    getBatchProduct: function (idProduct,idFac){
        var data = new Object();

        data['prodid'] = idProduct;
        data['idFac'] = idFac;

        var result = ajaxDataSend('getBatchProduct', data);
        $("#batchTable_ tr.data").remove();
        var win = "$('#idgetBatch').dialog('close')";
        $.each(result, function (id, item) {
            $('#batchTable_').append('<tr class="data"><td class="itemId">' + item['batch'] + '</td><td class="itemSellby">' + item['sellby'] + '</td><td class="itemEatby">' + item['eatby'] + '</td><td class="action add"><a class="action addbatch" onclick="_TPV.ticket.addBatchLine('  + idProduct + ',\'' + item['batch']  + '\',' + item['stock'] +');' + win + ';"></a></td></tr>');
        });
    },

    searchByPlace: function () {
        var result = ajaxDataSend('getPlaces');
        $("#placeTable_ div").remove();


        $.each(result, function (id, item) {

            if (item['fk_ticket'] > 0) {
                _TPV.places[item['id']] = item['name'];
                $('#placeTable_').append('<div class="placeDiv placeDivFree" onclick="_TPV.getTicket(' + item['fk_ticket'] + ',true); ">' + item['name'] + '</div>');
            }
            else {
                _TPV.places[item['id']] = item['name'];
                $('#placeTable_').append('<div class="placeDiv" onclick="_TPV.ticket.newTicketPlace(' + item['id'] + '); ">' + item['name'] + '</div>');
            }
        });
        $('#idChangePlace').dialog({modal: true});
        $('#idChangePlace').dialog({width: 600});

        $('#idChangePlace').unbind('click');

        $('#idChangePlace').click(function () {

            $('#idChangePlace').dialog('close');

        });

    },
    countByRef: function () {

        var result = ajaxDataSend('countHistory', '');

        $('#histToday').html(result["today"]);

        $('#histYesterday').html(result["yesterday"]);

        $('#histThisWeek').html(result["thisweek"]);

        $('#histLastWeek').html(result["lastweek"]);

        $('#histTwoWeeks').html(result["twoweek"]);

        $('#histThreeWeeks').html(result["threeweek"]);

        $('#histThisMonth').html(result["thismonth"]);

        $('#histOneMonth').html(result["monthago"]);

        $('#histLastMonth').html(result["lastmonth"]);

    },

    countByRefFac: function () {

        var result = ajaxDataSend('countHistoryFac', '');

        $('#histFacToday').html(result["today"]);

        $('#histFacYesterday').html(result["yesterday"]);

        $('#histFacThisWeek').html(result["thisweek"]);

        $('#histFacLastWeek').html(result["lastweek"]);

        $('#histFacTwoWeeks').html(result["twoweek"]);

        $('#histFacThreeWeeks').html(result["threeweek"]);

        $('#histFacThisMonth').html(result["thismonth"]);

        $('#histFacOneMonth').html(result["monthago"]);

        $('#histFacLastMonth').html(result["lastmonth"]);

    },

    searchByRef: function (stat) {
        var filter = new Object();
        filter.search = $('#id_ref_search').val();
        filter.stat = stat;
        var result = ajaxDataSend('getHistory', filter);
        $("#historyTable tr.data").remove();


        $.each(result, function (id, item) {
            var edit = false;
            var delet = false;
            var actionsHtml = '';
            var strticket = "'ticket'";
            if (item['statut'] == 0) {
                edit = true;
                delet = true;
            }
            strticketgift = "'giftticket'";
            var date = '-';
            if (item['date_close'].length > 0 && item['date_close'] != '')
                date = item['date_close'];
            else if (item['date_creation'].length > 0 && item['date_creation'] != '')
                date = item['date_creation'];
            var blocked = '';
            var strticket = "'ticket'";
            if (item['type'] == 1)
                blocked = '_TPV.ticketState=1;';


            actionsHtml += '<a class="action edit" onclick="' + blocked + '_TPV.getTicket(' + item['id'] + ',' + edit + ');"></a>';

            if (delet) {
                actionsHtml += '<a class="action delete" onclick="_TPV.deletTicket(' + item['id'] + ');"></a>';
            }
            if (_TPV.defaultConfig['module']['print'] > 0) {
                actionsHtml += '<a class="action print" onclick="_TPV.printing(' + strticket + ',' + item['id'] + ');"></a>';
            }
            if (_TPV.defaultConfig['module']['print'] > 0 && !delet) {
                actionsHtml += '<a class="action printgift" onclick="_TPV.printing(' + strticketgift + ',' + item['id'] + ');"></a>';
            }
            if (_TPV.defaultConfig['module']['mail'] > 0) {
                actionsHtml += '<a class="action mail" onclick="_TPV.mailTicket(' + item['id'] + ');"></a>';
            }
            actionsHtml += '<a class="action close" onclick="_TPV.ticket.hideHistoryOptions(' + item['id'] + ')"></a>';
            $('#historyTable').append('<tr id="historyTicket' + item['id'] + '" onclick="_TPV.ticket.showHistoryOptions(' + item['id'] + ')" class="data"><td><a class="icontype state' + item['statut'] + ' type' + item['type'] + '"></a>' + item['ticketnumber'] + '</td><td>' + date + '</td><td>' + item['terminal'] + '</td><td>' + item['seller'] + '</td><td>' + item['client'] + '</td><td style="text-align:right;">' + displayPrice(item['amount']) + '</td><td class="colActions"  style="text-align:center">' + actionsHtml + '</tr>');
        });
    },

    searchByRefFac: function (stat) {
        var filter = new Object();
        filter.search = $('#id_ref_fac_search').val();
        filter.stat = stat;
        var result = ajaxDataSend('getHistoryFac', filter);
        $("#historyFacTable tr.data").remove();


        $.each(result, function (id, item) {
            var edit = false;
            var delet = false;
            var actionsHtml = '';
            var strticket = "'facture'";
            if (item['statut'] == 0) {
                edit = true;
                delet = true;
                strticket = "'ticket'";
            }
            strticketgift = "'giftfacture'";
            var date = '-';
            if (item['date_close'].length > 0 && item['date_close'] != '')
                date = item['date_close'];
            else if (item['date_creation'].length > 0 && item['date_creation'] != '')
                date = item['date_creation'];
            var blocked = '';
            if (item['type'] == 1)
                blocked = '_TPV.ticketState=1;';

            if (delet)
                actionsHtml += '<a class="action edit" onclick="' + blocked + '_TPV.getTicket(' + item['id'] + ',' + edit + ');"></a>';
            else
                actionsHtml += '<a class="action edit" onclick="' + blocked + '_TPV.getFacture(' + item['id'] + ',' + edit + ');"></a>';

            if (delet) {
                actionsHtml += '<a class="action delete" onclick="_TPV.deletTicket(' + item['id'] + ');"></a>';
            }
            if (_TPV.defaultConfig['module']['print'] > 0) {
                actionsHtml += '<a class="action print" onclick="_TPV.printing(' + strticket + ',' + item['id'] + ');"></a>';
            }
            if (_TPV.defaultConfig['module']['print'] > 0 && !delet) {
                actionsHtml += '<a class="action printgift" onclick="_TPV.printing(' + strticketgift + ',' + item['id'] + ');"></a>';
            }
            if (_TPV.defaultConfig['module']['mail'] > 0 && !delet) {
                actionsHtml += '<a class="action mail" onclick="_TPV.mailFacture(' + item['id'] + ');"></a>';
            }
            else if (_TPV.defaultConfig['module']['mail'] > 0 && delet) {
                actionsHtml += '<a class="action mail" onclick="_TPV.mailTicket(' + item['id'] + ');"></a>';
            }
            actionsHtml += '<a class="action close" onclick="_TPV.ticket.hideHistoryFacOptions(' + item['id'] + ')"></a>';
            $('#historyFacTable').append('<tr id="historyFacTicket' + item['id'] + '" onclick="_TPV.ticket.showHistoryFacOptions(' + item['id'] + ')" class="data"><td><a class="icontype state' + item['statut'] + ' type' + item['type'] + '"></a>' + item['ticketnumber'] + '</td><td>' + date + '</td><td>' + item['terminal'] + '</td><td>' + item['seller'] + '</td><td>' + item['client'] + '</td><td style="text-align:right;">' + displayPrice(item['amount']) + '</td><td class="colActions"  style="text-align:center">' + actionsHtml + '</tr>');
        });
    },

    showNotes: function () {
        var result = ajaxDataSend('getNotes', 1);
        $("#noteTable tr.data").remove();
        var blocked = ' ';
        var idtick = 0;
        $.each(result, function (id, item) {
            if (item['ticketid'] != idtick) {
                idtick = item['ticketid'];
                $('#noteTable').append('<tr class="data"><td class="itemId" style="display:none">' + item['id'] + '</td><td width=10px; id="noteCabe">' + item['ticketnumber'] + '</td><td colspan=2 id="noteCabe">' + item['note'] + '</td><td width=10px; id="noteCabe"><a class="action addNote" onclick="' + blocked + '_TPV.getTicket(' + item['ticketid'] + ',true);" ></a></td></tr>');
            }
            else {
                $('#noteTable').append('<tr class="data"><td class="itemId" style="display:none">' + item['id'] + '</td><td colspan=2>' + item['description'] + '</td><td colspan=2>' + item['note'] + '</td></tr>');
            }
        });
        $('#idTotalNote').dialog({modal: true});
        $('#idTotalNote').dialog({height: 450, width: 600});
    },
    changeCustomer: function () {
        $('#idChangeCustomer').dialog({modal: true});
        $('#idChangeCustomer').dialog({height: 450, width: 600});
    },
    getBatch: function(product){
        $('#idgetBatch').dialog({modal: true});
        $('#idgetBatch').dialog({height: 450, width: 600});
    },
    getBatchRet: function(product){
        $('#idgetBatchRet').dialog({modal: true});
        $('#idgetBatchRet').dialog({height: 450, width: 600});
    },

    getDataCategories: function (category) {
        $('#products').html('');
        this.getCategories(category);
    },
    getCategories: function (category) {
        $('#products').html('');

        var categories = this.categories;
        $.getJSON('./ajax_pos.php?action=getCategories&parentcategory=' + category, function (data) {

            if (category != 0) {
                $('#products').append('<div align="center" onclick="_TPV.getDataCategories(' + categories[category]['parent'] + ')" id="category_' + categories[category]['parent'] + '" title="Up" class="botonCategoria">'
                    + '<div align="center"></div>UP</div>');
            }

            $.each(data, function (key, val) {
                if (categories[val.id] == undefined) {
                    categories[val.id] = val;
                    categories[val.id]['parent'] = category;
                }
                $('#products').append('<div align="center" onclick="_TPV.getDataCategories(' + val.id + ')" id="category_' + val.id + '" title="' + val.label + '" class="botonCategoria">'
                    + '<div align="center"><img border="0" alt="" src="' + val.image + '"></div>' + val.label + '</div>');

            }, _TPV.getProducts(category));


        });
    },
    loadMoreProducts: function (category, pag) {
        //_TPV.showingProd = 0;
        $('#btnLoadMore').detach();
        var products = this.products;
        var categories = this.categories;
        var addProducts = true;

        $.getJSON('./ajax_pos.php?action=getMoreProducts&category=' + category + '&pag=' + pag + '&ticketstate=' + _TPV.ticket.type, function (data) {
            $.each(data, function (key, val) {
                if (products[val.id] == undefined)
                    products[val.id] = val;
                if (addProducts && categories[category] != undefined) {
                    var arrayItem = categories[category]['products'].length;
                    categories[category]['products'][arrayItem] = val.id;
                }
                $('#products').append('<div onclick="_TPV.ticket.addLine(' + val.id + ');_TPV.go_up();" align="center" id="produc_' + val.id + '"  class="botonProducto">'
                    + '<div align="center"><a ><img border="0"  src="' + val.thumb + '"></a></div>' + val.label + '</div>');
                _TPV.showingProd++;
            });
            if (_TPV.showingProd % 10 == 0 && _TPV.showingProd > 0) {
                var txt = ajaxDataSend('Translate', 'More');
                $('#products').append('<div class="butProd" id="btnLoadMore" onclick="_TPV.loadMoreProducts(' + category + ',' + _TPV.showingProd + ')">' + txt + '</div>');
            }
        });
    },
    getProducts: function (category) {
        _TPV.showingProd = 0;
        var products = this.products;
        var categories = this.categories;
        if (typeof category != 'undefined') {
            var addProducts = false;
            if (categories[category] != undefined) {
                if (categories[category]['products'] != undefined) {
                    categoryProducts = this.categories[category]['products'];

                    $.each(categoryProducts, function (key, val) {
                        product = products[val];
                        $('#products').append('<div onclick="_TPV.ticket.addLine(' + product.id + ');_TPV.go_up();" align="center" id="produc_' + product.id + '" class="botonProducto">'
                            + '<div align="center"><a ><img border="0"  src="' + product.thumb + '"></a></div>' + product.label + '</div>');
                        _TPV.showingProd++;
                    });
                    if (_TPV.showingProd % 10 == 0 && _TPV.showingProd > 0) {
                        var txt = ajaxDataSend('Translate', 'More');
                        $('#products').append('<div class="butProd" id="btnLoadMore" onclick="_TPV.loadMoreProducts(' + category + ',' + _TPV.showingProd + ')">' + txt + '</div>');
                    }
                    return;
                }
                else {
                    addProducts = true;
                    categories[category]['products'] = new Array();
                }
            }

            $.getJSON('./ajax_pos.php?action=getProducts&category=' + category + '&ticketstate=' + _TPV.ticket.type, function (data) {
                $.each(data, function (key, val) {
                    if (products[val.id] == undefined)
                        products[val.id] = val;
                    if (addProducts) {
                        var arrayItem = categories[category]['products'].length;
                        categories[category]['products'][arrayItem] = val.id;
                    }
                    $('#products').append('<div onclick="_TPV.ticket.addLine(' + val.id + ');_TPV.go_up();" align="center" id="produc_' + val.id + '"  class="botonProducto">'
                        + '<div align="center"><a ><img border="0"  src="' + val.thumb + '"></a></div>' + val.label + '</div>');
                    _TPV.showingProd++;
                });
                if (_TPV.showingProd % 10 == 0 && _TPV.showingProd > 0) {
                    var txt = ajaxDataSend('Translate', 'More');
                    $('#products').append('<div class="butProd" id="btnLoadMore" onclick="_TPV.loadMoreProducts(' + category + ',' + _TPV.showingProd + ')">' + txt + ' </div>');
                }
            });
        }

    },

    go_up: function () {

        $("div.ticket_content").animate({scrollTop: 0}, "slow");
        return false;
    },

    printing: function (type, id) {
        //$(".btnPrint").printPage();
        switch (type) {
            case 'ticket':
                if (_TPV.defaultConfig['module']['print_mode'] == 0)
                    $(".btnPrint").attr('href', 'tpl/ticket.tpl.php?id=' + id);
                else
                    ajaxDataSend('addPrint', "T" + id);
                break;
            case 'facture':
                if (_TPV.defaultConfig['module']['print_mode'] == 0)
                    $(".btnPrint").attr('href', 'tpl/facture.tpl.php?id=' + id);
                else
                    ajaxDataSend('addPrint', "F" + id);
                break;
            case 'giftticket':
                if (_TPV.defaultConfig['module']['print_mode'] == 0)
                    $(".btnPrint").attr('href', 'tpl/giftticket.tpl.php?id=' + id);
                else
                    ajaxDataSend('addPrint', "G" + id);
                break;
            case 'giftfacture':
                if (_TPV.defaultConfig['module']['print_mode'] == 0)
                    $(".btnPrint").attr('href', 'tpl/giftfacture.tpl.php?id=' + id);
                else
                    ajaxDataSend('addPrint', "J" + id);
                break;
            case 'closecash':
                if (_TPV.defaultConfig['module']['print_mode'] == 0)
                    $(".btnPrint").attr('href', 'tpl/closecash.tpl.php?id=' + id);
                else
                    ajaxDataSend('addPrint', "C" + id);
                break;

        }

        if (_TPV.defaultConfig['module']['print_mode'] == 0) {
            var windowSizeArray = ["width=0,height=0",
                "width=0,height=0,scrollbars=no"];

            $('.btnPrint').click(function (event) {
                $('.btnPrint').unbind('click');
                var url = $(this).attr("href");
                var windowName = "_blank";//$(this).attr("name");popup
                var windowSize = windowSizeArray[0];

                window.open(url, windowName, windowSize);

                event.preventDefault();

            });
            $(".btnPrint").click();
        }
    },

    validateMail: function (valor) {

        if (/^[0-9a-z_\-\.]+@[0-9a-z\-\.]+\.[a-z]{2,4}$/i.test(valor)) {
            return true;
        }
        else {
            var txt = ajaxDataSend('Translate', 'MailError');
            _TPV.showError(txt);
            return false;
        }
    },


    mailTicket: function (idTicket) {
        $('#mail_to').val("");
        $('#idSendMail').dialog({modal: true});
        $('#idSendMail').dialog({width: 400});
        $('#id_btn_ticketLine').unbind('click');
        $('#id_btn_ticketLine').click(function () {
            var email = new Object();
            email.idTicket = idTicket;
            email.mail_to = $('#mail_to').val();

            if (_TPV.validateMail($('#mail_to').val())) {
                var result = ajaxDataSend('SendMail', email);
            }
            $('#idSendMail').dialog("close");
        });
    },
    mailFacture: function (idFacture) {
        $('#mail_to').val("");
        $('#idSendMail').dialog({modal: true});
        $('#idSendMail').dialog({width: 400});
        $('#id_btn_ticketLine').unbind('click');
        $('#id_btn_ticketLine').click(function () {
            var email = new Object();
            email.idFacture = idFacture;
            email.mail_to = $('#mail_to').val();

            if (_TPV.validateMail($('#mail_to').val())) {
                var result = ajaxDataSend('SendMail', email);
            }
            $('#idSendMail').dialog("close");
        });
    },

    mailCash: function (idCloseCash) {
        $('#mail_to').val("");
        $('#idSendMail').dialog({modal: true});
        $('#idSendMail').dialog({width: 400});
        $('#id_btn_ticketLine').unbind('click');
        $('#id_btn_ticketLine').click(function () {
            var email = new Object();
            email.idCloseCash = idCloseCash;
            email.mail_to = $('#mail_to').val();

            if (_TPV.validateMail($('#mail_to').val())) {
                var result = ajaxDataSend('SendMail', email);
            }

            $('#idSendMail').dialog("close");
            $('#btnLogout').click();
        });
    },

    deletTicket: function (idTicket) {
        $('#delete').val("");
        $('#idTicketDelet').dialog({modal: true});
        $('#idTicketDelet').dialog({width: 400});
        $('#id_btn_ticketYes').click(function () {
            $('#id_btn_ticketYes').unbind('click');
            var result = ajaxDataSend('deleteTicket', idTicket);
            _TPV.searchByRef(-1);
            _TPV.searchByRefFac(-1);

            $('#idTicketDelet').dialog("close");
        });

        $('#id_btn_ticketNo').click(function () {
            $('#id_btn_ticketNo').unbind('click');

            $('#idTicketDelet').dialog("close");
        });
    },

    showInfo: function (error) {
        $('#infoText').html(error);
        $('#idPanelInfo').dialog({modal: true});
        $('#idPanelInfo').dialog({width: 500, height: 200});
        setTimeout(function () {
            $('#idPanelInfo').dialog("close")
        }, 3000);
    },
    showError: function (error) {
        $('#errorText').html(error);
        $('#idPanelError').dialog({modal: true});
        $('#idPanelError').dialog({width: 500, height: 200});
        setTimeout(function () {
            $('#idPanelError').dialog("close")
        }, 6000);
    },
    addInfoProduct: function (idProduct) {
        $('#short_description_content').hide();
        $('#info_product').show();
        this.activeIdProduct = idProduct;
        product = this.products[idProduct];
        $('#info_product').find('#our_label_display').html(product.label);
        $('#info_product').find('#short_description_content').html(product.description);
        if (product.description) {
            $('#btnHideInfo').show();
        }
        else {
            $('#btnHideInfo').hide();
        }
        if (_TPV.defaultConfig['module']['ttc'] == 0) {
            var price = new Number(product.price_ht);
            var price_min = new Number(product.price_min_ht);
        }
        else {
            var price = new Number(product.price_ttc);
            var price_min = new Number(product.price_min_ttc);
        }
        price = price.toFixed(2);
        price_min = price_min.toFixed(2);
        $('#info_product').find('#our_price_display').html(price);
        if (price_min > 0) {
            $('#info_product').find('#our_price_min_display').html(price_min);
            $('#our_price_min').show();
        }
        else {
            $('#our_price_min').hide();
        }
        $('#info_product').find('#bigpic').attr({src: product.image});
        $('#info_product').find('#hiddenIdProduct').val(idProduct);
    },
    addInfoProductSt: function (idProduct) {
        $('#short_description_content_st').hide();
        $('#info_product_st').show();
        this.activeIdProduct = idProduct;
        if (typeof this.products[idProduct] == 'undefined') {
            var info = new Object();
            info['product'] = idProduct;
            if (_TPV.ticket.customerId != 0) {
                info['customer'] = _TPV.ticket.customerId;
            }
            else {
                info['customer'] = _TPV.customerId;
            }
            var result = ajaxDataSend('getProduct', info);
            if (result.length > 0)
                this.products[idProduct] = result[0];
        }
        product = this.products[idProduct];
        $('#info_product_st').find('#our_label_display_st').html(product.label);
        $('#info_product_st').find('#short_description_content_st').html(product.description);
        if (product.description) {
            $('#btnHideInfoSt').show();
        }
        else {
            $('#btnHideInfoSt').hide();
        }
        if (_TPV.defaultConfig['module']['ttc'] == 0) {
            var price = new Number(product.price_ht);
            var price_min = new Number(product.price_min_ttc);
        } else {
            var price = new Number(product.price_ttc);
            var price_min = new Number(product.price_min_ht);
        }

        price = price.toFixed(2);
        price_min = price_min.toFixed(2);
        $('#info_product_st').find('#our_price_display_st').html(price);
        if (price_min > 0) {
            $('#info_product_st').find('#our_price_min_display_st').html(price_min);
            $('#our_price_min_st').show();
        }
        else {
            $('#our_price_min_st').hide();
        }
        $('#info_product_st').find('#bigpic').attr({src: product.image});
        $('#info_product_st').find('#hiddenIdProduct').val(idProduct);
    },
    loadConfig: function () {

        var result = ajaxDataSend('getPlaces');

        if (result) {
            _TPV.places[null] = '';
            $.each(result, function (id, item) {
                _TPV.places[item['id']] = item['name'];
            });
        }
        var result = ajaxDataSend('getConfig', null);
        if (result) {
            this.defaultConfig = result;
            $('#id_user_name').html(result['user']['name']);
            $('#id_user_terminal').html(result['terminal']['name']);
            $('#infoCustomer').html(result['customer']['name']);
            $('#infoCustomer_').html(result['customer']['name']);
            $('#id_image').attr("src", result['user']['photo']);
            _TPV.customerId = result['customer']['id'];
            _TPV.employeeId = result['user']['id'];
            _TPV.warehouseId = result['terminal']['warehouse'];
            _TPV.faclimit = result['terminal']['faclimit'];
            _TPV.discount = result['customer']['remise'];
            _TPV.points = result['customer']['points'];
            _TPV.coupon = result['customer']['coupon'];
            _TPV.cashId = result['terminal']['id'];

            if (result['terminal']['tactil'] == 1) {
                _TPV.tpvTactil(true);
            }
            else {
                _TPV.tpvTactil(false);
            }
            if (result['terminal']['barcode'] == 1) {
                $('#id_product_search').focus();
            }
        }
        var result = ajaxDataSend('getNotes', 0);
        if (result) {
            $('#totalNote_').html(result);
        }

        return;
    },
    showInfoProduct: function (on) {

        if (!on) {
            _TPV.infoProduct = 0;

        } else {
            _TPV.infoProduct = 1;
        }
        return;
    },
    tpvTactil: function (on) {

        if (!on) {
            //$('.quertyKeyboard').keyboard('option', 'openOn', '');
            $('[type=text]').each(function () {
                $(this).getkeyboard().destroy();
            });
            return;
        }
        else {
            $('[type=text]:not(.numKeyboard)').keyboard({
                layout: 'qwerty',
                usePreview: false,
                autoAccept: true,
                accepted: function (e, keyboard, el) {

                }
            });
            $('.numKeyboard').keyboard({
                //layout:'num',
                layout: 'custom',
                usePreview: false,
                autoAccept: true,
                customLayout: {
                    'default': [

                        '7 8 9',
                        '4 5 6',
                        '1 2 3',
                        '0 . {sign}',
                        '{bksp} {a} {c}'
                    ]
                },
                accepted: function (e, el) {

                }
            });
            return;
        }

    }
});

$(function() {

    _TPV.setButtonEvents();

    _TPV.tpvTactil(true);
    $.keyboard.keyaction.enter = function (base) {
        if (base.el.tagName === "INPUT") {
            //base.accept();      // accept the content
            var e = $.Event('keypress');
            e.which = 13;
            base.close(true);
            $(base.el).trigger(e);
            // same as base.accept();
            return false;

        } else {
            base.insertText('\r\n'); // textarea
        }
    };
});
$(document).ready(function() {
    _TPV.loadConfig();
    _TPV.ticket.newTicket();
    _TPV.ticket.setButtonState(false);
    $(".numKeyboard").keypress(function (e) {


        if (window.event) { // IE
            var charCode = e.keyCode;
        } else if (e.which) { // Safari 4, Firefox 3.0.4
            var charCode = e.which
        }
        if (charCode != 8 && charCode != 0 && ((charCode < 48 && charCode != 46 && charCode != 44) || charCode > 57))

            return false;
        return true;
    });

});
var _TPV = new TPV();
//_TPV.getDataCategories(0);

function removeKey(arrayName,key) {
    var x;
    var tmpArray = new Array();
    for (var i = 0; i < arrayName.length; i++) {
        if (arrayName[i]['idProduct'] != key) {
            tmpArray.push(arrayName[i]);
        }
    }
    return tmpArray;
}

function ajaxSend(action) {
    var result;
    $.ajax({
        type: "POST",
        url: './ajax_pos.php',
        data: 'action=' + action,
        async: false,
        success: function (msg) {
            result = msg;
        }
    });
    return result;
}
function displayPrice(pr) {
    //return (Math.round(pr*100/5)*5/100).toFixed(2);
    var precision = 2;
    if (typeof _TPV.defaultConfig['decrange']['tot'] != 'undefined')
        precision = _TPV.defaultConfig['decrange']['tot'];
    return parseFloat(pr).toFixed(precision);

}
function showLeftContent(divcontent) {
    $('.leftBlock').each(function () {
        $(this).hide();
    });
    $(divcontent).show();
}
function hideLeftContent() {
    $('.leftBlock').each(function () {
        $(this).hide();
    });
    $('#products').show();
}
function ajaxDataSend(action,data) {
    var result;

    var DTO = {'data': data};

    var data = JSON.stringify(DTO);
    $.ajax({
        type: "POST",
        traditional: true,
        cache: false,
        url: './ajax_pos.php?action=' + action,
        contentType: "application/json;charset=utf-8",
        dataType: "json",
        async: false,
        processData: false,
        data: data,
        success: function (msg) {
            result = msg;
        }
    });

    if (result != null && typeof result != 'undefined') {
        if (typeof result['error'] != 'undefined' && typeof result['error']['desc'] != 'undefined' && result['error']['desc'] != '') // desc,value
        {
            if (result['error']['value'] == 0) {
                _TPV.showInfo(result['error']['desc']);
            }
            else if (result['error']['value'] == 99) {
                _TPV.showError(result['error']['desc']);
                window.location.href = "./disconect.php";
            }
            else {
                _TPV.showError(result['error']['desc']);
            }
            if (typeof result['error']['value'] != 'undefined' && parseInt(result['error']['value']) == 1)
                return false;
        }
        if (typeof result['error'] != 'undefined' && typeof result['error']['value'] != 'undefined' && result['error']['value'] == 0) // desc,value
        {

            if (typeof result['data'] != 'undefined')
                return result['data'];
        }
        //_TPV.showInfo('Error de ejecucion del codigo javascript');
    }
    return result;
}

function check() {
    $.getJSON('checkprint.php', function (data) {
        if (data != null) {
            if (data.type == "D") {
                var applet = document.getElementById('qz');
                applet.findPrinter(printer_name);
                applet.append(String.fromCharCode(27));
                applet.append(String.fromCharCode(112));
                applet.append(String.fromCharCode(48));
                applet.append(String.fromCharCode(55));
                applet.append(String.fromCharCode(121));
                applet.print();
            }
            else {
                if (data.type == "F" || data.type == "J" || data.type == "T" || data.type == "G") {
                    var applet = document.getElementById('qz');
                    applet.findPrinter(printer_name);
                    applet.append(data.mysoc_name + "\r\n");
                    applet.append(data.mysoc_address + "\r\n");
                    applet.append(data.mysoc_town + "\r\n");
                    applet.append(data.mysoc_idprof + "\r\n");

                    applet.append(data.ref + " " + data.datetime + "\r\n");
                    applet.append(data.vendor + "\r\n");
                    applet.append(data.place + "\r\n");

                    if (data.type == "F" || data.type == "J") {
                        applet.append(data.client_name + "\r\n");
                        applet.append(data.client_address + "\r\n");
                        applet.append(data.client_idprof + "\r\n");
                    }

                    if (data.type != "F" && data.type != "T") applet.append(data.gift);

                    applet.append(data.header_lines + "\r\n");

                    $.each(data.lines, function (key, val) {
                        applet.append(val.label);
                        applet.append(val.qty);
                        if (data.type != "J" && data.type != "G") applet.append(val.total);
                        applet.append("\r\n");
                    });
                    applet.append("----------------------------------------\r\n");
                    if (data.type == "F" || data.type == "T") {
                        applet.append(data.total_ttc1 + "\r\n");
                        applet.append(data.header_desg + "\r\n");
                        $.each(data.desg_lines, function (key, val) {
                            applet.append(val + "\r\n");
                        });
                        applet.append("----------------------------------------\r\n");
                        applet.append(data.desg_tot + "\r\n");
                        applet.append(data.localtax1 + "\r\n");
                        applet.append(data.localtax2 + "\r\n");
                        applet.append(data.total_ttc2 + "\r\n");

                        if (data.type == "F" && typeof data.pays_point != 'undefined') applet.append(data.pays_point + "\r\n");
                        //applet.append(data.header_desg+"\r\n");
                        $.each(data.pays_lines, function (key, val) {
                            applet.append(val + "\r\n");
                        });

                        applet.append(data.customer_ret + "\r\n");

                        if (data.type == "F" && typeof data.total_points != 'undefined') {
                            applet.append(data.total_points + "\r\n");
                            applet.append(data.dispo_points + "\r\n");
                        }
                        applet.append("----------------------------------------\r\n");
                    }

                    if (typeof data.predef_msg != 'undefined') applet.append(data.predef_msg + "\r\n");

                }

                else if (data.type == "C") {
                    var applet = document.getElementById('qz');
                    applet.findPrinter(printer_name);
                    applet.append(data.ref + " " + data.datetime + "\r\n");
                    applet.append(data.terminal + "\r\n");
                    applet.append(data.mysoc_name + "\r\n");
                    applet.append(data.mysoc_address + "\r\n");
                    applet.append(data.mysoc_town + "\r\n");
                    applet.append(data.mysoc_idprof + "\r\n");
                    applet.append(data.vendor + "\r\n");

                    applet.append(data.header_cash + "\r\n");
                    applet.append(data.header_lines + "\r\n");
                    $.each(data.cash_lines, function (key, val) {
                        applet.append(val.label + "\r\n");
                    });
                    applet.append(data.footer_cash + "\r\n");

                    applet.append(data.header_bank + "\r\n");
                    applet.append(data.header_lines + "\r\n");
                    $.each(data.bank_lines, function (key, val) {
                        applet.append(val.label + "\r\n");
                    });
                    applet.append(data.footer_bank + "\r\n");

                    if (typeof data.pays_point != 'undefined') {
                        applet.append(data.header_points + "\r\n");
                        applet.append(data.header_lines + "\r\n");
                        $.each(data.points_lines, function (key, val) {
                            applet.append(val.label + "\r\n");
                        });
                        applet.append(data.footer_points + "\r\n");
                    }

                    applet.append(data.total_ht + "\r\n");
                    $.each(data.desg_lines, function (key, val) {
                        applet.append(val + "\r\n");
                    });
                    applet.append(data.localtax1 + "\r\n");
                    applet.append(data.localtax2 + "\r\n");
                    applet.append(data.total_pos + "\r\n");
                }
                if (drawer == 1) {
                    applet.append(String.fromCharCode(27));
                    applet.append(String.fromCharCode(112));
                    applet.append(String.fromCharCode(48));
                    applet.append(String.fromCharCode(55));
                    applet.append(String.fromCharCode(121));
                }
                applet.append("\r\n\r\n\r\n\r\n\r\n\r\n");
                applet.append(String.fromCharCode(27));
                applet.append(String.fromCharCode(109));
                applet.print();
            }
        }
    });
}

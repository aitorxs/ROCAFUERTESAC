$(function() {
	$( document ).on( "click", "#agregar_descuento", function(e) {
		e.preventDefault();
		var tercero = $("#tercero").val(), categoria = $("#categoria").val(), tipo = $("#tipo").val(), cantidad = $("#cantidad").val(), fecha_inicio = $("#fecha_inicio").val(), fecha_fin = $("#fecha_fin").val();
		$("#div_mensaje").html('');

		if( tercero.length < 1 ) {
			$("#div_mensaje").html('Es necesario especificar el cliente.');
		}
		else if( categoria.length < 1 ) {
			$("#div_mensaje").html('Es necesario especificar la línea de producto.');
		}
		else if( tipo.length < 1 ){
			$("#div_mensaje").html('Es necesario especificar el tipo de producto.');
		}
		else if( cantidad.length < 1 ) {
			$("#div_mensaje").html('Es necesario especificar la cantidad referente al descuento seleccionado.');
		}
		else if( !$.isNumeric($("#cantidad").val()) ) {
			$("#div_mensaje").html('Es necesario especificar la cantidad con números.');	
		}
		else if( tipo == 1 && cantidad > 100) {
			$("#div_mensaje").html('El porcentaje no puede ser mayor al 100%.');	
		}
		else if( cantidad < 1 ) {
			$("#div_mensaje").html('La cantidad tiene que ser mayor a cero.');	
		}
		else {
			$.ajax({
				data:"tercero="+tercero+"&categoria="+categoria+"&tipo="+tipo+"&cantidad="+cantidad+"&fecha_inicio="+fecha_inicio+"&fecha_fin="+fecha_fin,
				url: "../funciones/agregarDescuento.php",
				type: "POST",
				success: function(data) {
					if( data == 1 ) {
						location.reload(true);
					}
					else {
						$('#listado_descuento tr:last').after(data);
						$('#form_descuento').each (function(){ this.reset();});
					}
				}
			});
		}
	});
	$( document ).on( "click", ".eliminar_descuento", function() {
		var id = $(this).attr('id'), current = $(this);
		$.ajax({
			data:"id="+id ,
			url: "../funciones/eliminarDescuento.php",
			type: "POST",
			success: function(data) {
				if( data == 1 ){
					current.parent().parent().remove();
				}
			}
		});
	});
	$( document ).on( "change", "#tipo", function() {
		var id = $(this).val();
		if( id == 1 ) {
			$("#cantidad").after('<span id="span_porcentaje")>%</span>');
			$("#span_cantidad").remove();
		}
		else if( id == 2 ) {
			$("#cantidad").before('<span id="span_cantidad")>$</span>');
			$("#span_porcentaje").remove();
		}
		else {
			$("#span_porcentaje , #span_cantidad").remove();	
		}
	});
	$.widget( "custom.combobox", {
		_create: function() {
			this.wrapper = $( "<span>" ).addClass( "custom-combobox" ).insertAfter( this.element );
			this.element.hide();
			this._createAutocomplete();
		},
		_createAutocomplete: function() {
			var selected = this.element.children( ":selected" ), value = selected.val() ? selected.text() : "";
 
			this.input = $( "<input size='55'>" )
			.appendTo( this.wrapper )
			.val( value )
			.attr( "title", "" )
			.addClass( "" )
			.autocomplete({
				delay: 0,
				minLength: 0,
				source: $.proxy( this, "_source" )
			})
			.tooltip({
				tooltipClass: "ui-state-highlight"
			});
			this._on( this.input, {
				autocompleteselect: function( event, ui ) {
					ui.item.option.selected = true;
					this._trigger( "select", event, {
						item: ui.item.option
					});
				},
				autocompletechange: "_removeIfInvalid"
			});
		},
		_source: function( request, response ) {
			var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
			response( this.element.children( "option" ).map(function() {
				var text = $( this ).text();
				if ( this.value && ( !request.term || matcher.test(text) ) )
					return {
						label: text,
						value: text,
						option: this
					};
				}));
		},
		_removeIfInvalid: function( event, ui ) {
			if ( ui.item ) {
				return;
			}
			var value = this.input.val(),
				valueLowerCase = value.toLowerCase(),
				valid = false;
			this.element.children( "option" ).each(function() {
				if ( $( this ).text().toLowerCase() === valueLowerCase ) {
					this.selected = valid = true;
					return false;
				}
			});
			// Found a match, nothing to do
			if ( valid ) {
				return;
			}
			// Remove invalid value
			this.input
			.val( "" )
			.attr( "title", value + " didn't match any item" )
			.tooltip( "open" );
			this.element.val( "" );
			this._delay(function() {
				this.input.tooltip( "close" ).attr( "title", "" );
			}, 2500 );
			this.input.autocomplete( "instance" ).term = "";
		},
 		_destroy: function() {
			this.wrapper.remove();
			this.element.show();
	  	}
	});
	$( "#tercero" ).combobox();
	$( "#toggle" ).click(function() {
		$( "#tercero" ).toggle();
	});
});
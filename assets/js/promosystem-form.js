jQuery( function ( $ ) {
	
	var mshotThirdTryTimer = null
	
	$( 'a.activate-option' ).click( function(){
		var link = $( this );
		if ( link.hasClass( 'clicked' ) ) {
			link.removeClass( 'clicked' );
		}
		else {
			link.addClass( 'clicked' );
		}
		$( '.toggle-have-key' ).slideToggle( 'slow', function() {});
		return false;
	});
	
	$( '#ps_check_form' ).on( 'click', '#check_button', function () {
		var ps_form = $( '#ps_check_form' ).serialize();
        
		$.ajax({
			url: ajax_object.ajaxurl,
			type: 'POST',
			data: ps_form,
			dataType: 'json',
			beforeSend: function () {
                $( '#ps_check_form .check_result').hide().empty();
                $( '#ps_check_form .check_loading').show();
				// zagryzka	
			},
			success: function (response, status) {
				console.log(response);
                $( '#ps_check_form .check_loading').hide();
				
				var mes = response.message;
                
                     $( '#ps_check_form .check_result')
					 .html(	$('<span class=main_result> </span>').text( mes[0] ) )
					 .append( '<br>' )
					 .append( mes[1] )
					.show();
				if (response.status == 'valid' || response.status == 'already') {
                    var prod = response.product;
                    $( '#ps_check_form .check_result').append( '<br>' ).append(prod.title);
                }
			
			}
		});

		return false;
	});
});




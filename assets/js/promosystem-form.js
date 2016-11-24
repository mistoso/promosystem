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
        console.log( ps_form );
        console.log( ajax_object.ajax_url );
		return false;
		$.ajax({
			url: ajax_object.ajaxurl, //'/wp-content/plugins/promosystem/promosystem.php',
			type: 'POST',
			data: ps_form,
			beforeSend: function () {
                console.log('send, waiting . . . ')},
			success: function (response) {
                console.log(response);
                return false;
			/*	if (response) {
					// Show status/undo link
					$("#author_comment_url_"+ thisId)
						.attr('cid', thisId)
						.addClass('akismet_undo_link_removal')
						.html(
							$( '<span/>' ).text( WPAkismet.strings['URL removed'] )
						)
						.append( ' ' )
						.append(
							$( '<span/>' )
								.text( WPAkismet.strings['(undo)'] )
								.addClass( 'akismet-span-link' )
						);
				}
				*/
			}
		});

		return false;
	});
});




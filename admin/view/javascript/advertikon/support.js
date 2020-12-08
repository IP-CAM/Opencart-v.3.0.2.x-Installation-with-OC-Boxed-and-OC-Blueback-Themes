( function( $ ) {

	/**
	 * Loads ticket button
	 * @returns {void}
	 */
	function renderTicketButton() {
		var element = $( "#ticket-wrapper" );

		if ( element.length ) {
			element.load( element.attr( "data-url" ).replace( /&amp;/g, "&" ), initSupportFields );
		}
	}

	/**
	 * Send support request
	 * @return {void}
	 */
	function askSupport() {
		var
			button = $( this ),
			form = new FormData();

		$( this ).closest( "#support-wrapper" ).find( ".form-field" ).each( function each() {
			var input = $( this );

			if ( "file" === this.type ) {
				for( var i = 0; i < this.files.length; i++ ) {
					form.append( input.attr( "data-name" ), this.files[ i ], this.files[ i ].name );
				}

			} else {
				form.append( input.attr( "data-name" ), input.val() );
			}
		} );

		button.btnActive();

		$.ajax( {
			url:      $( this ).attr( "data-url" ).replace( /&amp;/g, "&" ),
			type:     "POST",
			data:     form,
			async:    true,
			dataType: "json",
			success:  function ok( msg ) {
				if ( msg.success ) {
					ADK.n.notification( msg.success );

				} else if ( msg.error ) {
					ADK.n.alert( msg.error );

				} else {
					ADK.n.error( ADK.locale.networkError);
				}
			},
			complete: function done() {
				button.btnReset();
			},
			cache:       false,
			contentType: false,
			processData: false
		} );

	}

	/**
	 * Rollback DB button click handler
	 * @returns {void}
	 */
	function rollbackDb() {
		$( this ).buttonClick( function cb( resp ) {
			if ( resp.success ) {
				ADK.n.notification( resp.success );

				setTimeout( function sleep() {
					location.reload();
				}, 1000 );

			} else if ( resp.error ) {
				ADK.n.alert( resp.error );

			} else {
				ADK.n.error( ADK.locale.networkError);
			}
		} );
	}

	/**
	 * Perform manual update of DB data
	 * @returns {void}
	 */
	function updateDB() {
		$( this ).buttonClick( function cb( resp ) {
			if ( resp.success ) {
				ADK.n.notification( resp.success );

				setTimeout( function sleep() {
					location.reload();
				}, 1000 );

			} else if ( resp.error ) {
				ADK.n.alert( resp.error );

			} else {
				ADK.n.error( ADK.locale.networkError);
			}
		} );
	}

	/**
	 * Flush the cache click handler
	 * @returns {void}
	 */
	function flushCache() {
		$( this ).buttonClick( function cb( resp ) { console.log( resp );
			if ( resp.success ) {
				ADK.n.notification( resp.success );

			} else if ( resp.error ) {
				ADK.n.alert( resp.error );

			} else {
				ADK.n.alert( ADK.locale.networkError);
			}
		} );
	}

	function getLicenseById() {
		$( this ).buttonClick( { id: $( "#license_by_id_input" ).val() } )

		.done( function( resp ) {
			if ( resp.error ) {
				ADK.alert( resp.error );
			}

			if ( resp.success ) {
				ADK.alert( resp.success ).yes( function(){ window.location.reload(); } );
			}
		} );
	}

	function registerExtension() {
		$( this ).buttonClick( function( resp ) {
			if ( resp.error ) {
				ADK.alert( resp.error );
			}

			if ( resp.success ) {
				ADK.alert( resp.success ).yes( function(){ window.location.reload(); } );
			}
		}, { license: $( "#register_license" ).val() } );
	}

	function finishTransfer() {
		$( this ).buttonClick( function( resp ) {
			if ( resp.error ) {
				ADK.alert( resp.error );
			}

			if ( resp.success ) {
				ADK.alert( resp.success ).yes( function(){ window.location.reload(); } );
			}
		}, {
			name:  $( "#license_name" ).val(),
			email: $( "#license_email" ).val(),
			code:  $( "#license_code" ).val()
		} );
	}

	function cancelTransfer() {
		$( this ).buttonClick( function( resp ) {
			if ( resp.error ) {
				ADK.alert( resp.error );
			}

			if ( resp.success ) {
				ADK.alert( resp.success ).yes( function(){ window.location.reload(); } );
			}
		}, {} );
	}

	function initSupportFields() {
		$( "#license_by_id_button" ).on( "click", getLicenseById );
		$( "#register_license_button" ).on( "click", registerExtension );
		$( "#finish_transfer" ).on( "click", finishTransfer );
		$( "#cancel_transfer" ).on( "click", cancelTransfer );
	}

	$( document ).ready( function ready() {
		// Send support request
		$( document ).delegate( "#ask-support-button", "click", askSupport );

		// Rollback DB version
		$( document ).delegate( "#rollback-db", "click", rollbackDb );

		// Run update process of DB date manually
		$( "#update-button" ).on( "click", updateDB );

		// Flush system cache
		$( "#clear-cache" ).on( "click", flushCache );

		// Initialize tabs
		$( ".nav-tabs" ).tab();

		// Render support tab contents
		renderTicketButton();
	} );
} )( jQuery );
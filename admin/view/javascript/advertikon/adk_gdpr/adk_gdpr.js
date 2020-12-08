( function ( $ ) {
	"use strict";
	
	/**
	 * Checks terms version freshness
	 * @returns {undefined}
	 */
	function checkTermVersion() {
		if ( ADK.isEmpty( $(this ).val() ) ) {
			return;
		}

		$.getJSON( ADK.locale.checkTermUrl, { type: this.id } )

		.done( function () {
			ADK.e.trigger( "term.version.updated" );
		} );
	}
	
	/**
	 * Loads ters into preview window
	 * @returns {undefined}
	 */
	function showTermVersion() {
		$( "#term-version-preview-frame" )
			.attr(
				"src",
				ADK.locale.termPreviewUrl + "&id=" + $( this ).attr( "data-id" ) +
					"&type=" + $( this ).attr( "data-type" )
			);
	}
	
	function updateTermsVersion() {
		$( "#term-version-table" ).trigger( "refresh" );
	}
	
	/**
	 * Shows terms preview window
	 * @returns {undefined}
	 */
	function showTermPreviewWindow() {
		if( !this.src ) {
			return; // FireFox
		}

		$( "#term-version-preview" ).modal( "show" );
	}

	function initTemplateTranslations() {
		var self = this;

		setTimeout( function(){
			$(  $( self ).attr( "href" ) ).find( ".adk-translate-control-select" ).trigger( "change" );
		}, 1000 );
	}

	function requestManualAction() {
		var
			text = $( this ).hasClass( "adk-request-reject" ) ? ADK.locale.rejectText : ADK.locale.fulfillText,
			button = $( this );

		ADK.confirm( text )
		.yes( function(){
			button.buttonClick()
			.done( function ( resp ) {
				if ( resp.success ) {
					ADK.n.notification( resp.success );
				}
				
				if ( resp.error ) {
					ADK.n.alert( resp.error );
				}
				
				$( "#request-table" ).trigger( "refresh" );
				$( "#consent-table" ).trigger( "refresh" );
			} );
		} );
	}

	function addBreachReport() {
		var
			fact   = $( "#breach-report-fact" ).val(),
			effect = $( "#breach-report-effect" ).val(),
			remedy = $( "#breach-report-remedy" ).val();

		$( this ).buttonClick( {
			fact:   fact,
			effect: effect,
			remedy: remedy
		} )

		.done( function( resp ) { 
			if ( resp.error ) {
				ADK.n.alert( resp.error );
			}

			if ( resp.success ) {
				ADK.n.notification( resp.success );
			}
		} );
	}

	function sendBreachReport() {
		var
			parent = $( this ).closest( ".tab-pane" ),
			text = parent.find( ".breach-report-text" ).val(),
			subject = parent.find( ".breach-report-subject" ).val(),
			emails = [];

		parent.find( ".breach-report-email" ).each( function() {
			if ( "INPUT" === this.tagName && !$( this ).is( ":checked" ) ) {
				return;
			}

			emails.push( $( this ).val() );
		} );

		$( this ).buttonClick( {
			text:    text,
			subject: subject,
			emails:  emails
		} )

		.done( function( resp ) {
			if ( resp.error ) {
				ADK.n.alert( resp.error );
			}

			if ( resp.success ) {
				ADK.n.notification( resp.success );
			}
		} );
	}

	function getAllEmail() {
		$( this ).closest( "div" ).load( $( this ).attr( "data-url" ) );
	}

	function selectAllEmail() {
		var is_on = $( this ).is( ":checked" );

		$( this ).closest( ".adk-email-list" ).find( "input" ).each( function() {
			if ( is_on ) {
				$( this ).prop( "checked", true );

			} else {
				$( this ).prop( "checked", false );
			}
		} );
	}

	function audit() {
		$( this ).buttonClick()
		.done( function( resp ) {
			if ( resp.error ) {
				ADK.n.alert( resp.error );
			}

			if ( resp.success ) {
				ADK.n.notification( resp.success );
				$( "#audit-table" ).trigger( "refresh" );
			}
		} );
	}

	function saveCookie() {
		var
			name = $( this ).attr( "id" ),
			value = $( this ).val(),
			control = $( this );

		$.post( ADK.locale.cookieUrl, { name: name, value: value } )
		.done( function ( resp ) {
			var response = ADK.checkResponse( resp );

			if ( null === response ) {
				// if ( 'fancyCheckbox' in control ) {
				// 	control.fancyCheckbox( 'toggle-view' );
				// }

				return;
			}

			if ( response.error ) {
				ADK.n.alert( response.error );

				// if ( 'fancyCheckbox' in control ) {
				// 	control.fancyCheckbox( 'toggle-view' );
				// }

			} else if ( response.success ) {
				ADK.n.notification( response.success );
			}
		} );
	}

	function downloadFont() {
		var button = this;

		$( this ).buttonClick()

		.done( function( resp ) {
			if ( resp.success ) {
				ADK.n.notification( resp.success );
				$( button ).attr( "disabled", "disabled" );
			}

			if ( resp.error ) {
				ADK.n.alert( resp.error );
			}
		} );
	}
	
	ADK.e.subscribe( "term.version.updated", updateTermsVersion );
	
	$( document ).delegate( ".track-term", "change", checkTermVersion );
	
	$( document ).delegate( ".term-version-show", "click", showTermVersion );

	$( document ).on( "click", ".request-action-button", requestManualAction );

	$( document ).on( "click", ".breach-report-email-all", selectAllEmail );

	$( document ).on( "click", ".ignore-audit-button", audit );

	$( document ).on( "click", ".anonymize-order-button", audit );

	$( document).on( "click", ".delete-order-button", audit );

	$( document ).delegate( ".cookie-control", "change", saveCookie );

	$( document ).ready( function() {
		$( ".adk-date" ).datetimepicker();
		$( "#term-version-preview-frame" ).on( "load", showTermPreviewWindow );
		$( ".adk-translate-control-select" ).initTranslateControls();
		$( "a[href^=#tab-request-template], a[href^=#tab-cookie]" ).one( "click", initTemplateTranslations );
		$( "#breach-report-add-button" ).on( "click", addBreachReport );
		ADK.initSelect2( $( "#breach-report-authority-email" ), {
			tags:           true,
			templateSelection: function formatReponce( data, element ) {
				return $( "<span>" + data.id + "</span>" );
			}
		} );

		$( ".send-breach-report-button" ).on( "click", sendBreachReport );
		$( "#get-all-email-button" ).on( "click", getAllEmail );
		$( "#run-audit-button" ).on( "click", audit );
		$( "#remove-missed-orders-button" ).on( "click", audit );
		$( "#remove-expired-orders-button" ).on( "click", audit );
		$( "#anonymize-unconsented-orders-button" ).on( "click", audit );

		$( ".adk-color" ).spectrum( {
			showInitial:     true,
			showAlpha:       true,
			preferredFormat: "rgb"
		} );

		$( "#download-font" ).on( "click", downloadFont );
	} );
} )( jQuery );
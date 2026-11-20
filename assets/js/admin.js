( function ( $ ) {
	$( function () {
		$( '.sts-sd-color' ).wpColorPicker();

		// "Design a label" checkbox: off by default for a group (no parent), on for a deal inside one.
		// Choosing a parent ticks it, choosing none clears it; either way it can be changed by hand.
		// Hidden fields still submit, so saved label settings are never lost.
		var $parent = $( '#parent' );
		var $toggle = $( '#sts_label_toggle' );
		function hasParent() {
			var v = String( $parent.val() );
			return $parent.length && v !== '-1' && v !== '0';
		}
		function applyLabelFields() {
			$( '.sts-sd-badge-fields' ).toggle( $toggle.is( ':checked' ) );
		}
		function syncGroupFields() {
			$toggle.prop( 'checked', hasParent() );
			applyLabelFields();
		}
		$toggle.on( 'change', applyLabelFields );
		applyLabelFields();
		if ( window.stsSaleDeals && stsSaleDeals.parentHint ) {
			$( '#parent-description' ).text( stsSaleDeals.parentHint );
		}
		$parent.on( 'change', syncGroupFields );

		var frame;
		$( '#sts_label_image_pick' ).on( 'click', function ( e ) {
			e.preventDefault();
			if ( ! frame ) {
				frame = wp.media( { multiple: false, library: { type: 'image' } } );
				frame.on( 'select', function () {
					var att = frame.state().get( 'selection' ).first().toJSON();
					var url = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
					$( '#sts_label_image' ).val( att.id );
					$( '#sts_label_image_preview' ).attr( 'src', url ).show();
				} );
			}
			frame.open();
		} );

		$( '#sts_label_image_clear' ).on( 'click', function ( e ) {
			e.preventDefault();
			$( '#sts_label_image' ).val( '' );
			$( '#sts_label_image_preview' ).hide().attr( 'src', '' );
		} );

		// After adding a term via AJAX the form stays on screen: reset the custom fields.
		$( document ).ajaxComplete( function ( event, xhr, settings ) {
			if ( settings.data && settings.data.indexOf( 'action=add-tag' ) !== -1 ) {
				$( '#sts_label_text' ).val( '' );
				$( '#sts_label_image' ).val( '' );
				$( '#sts_label_image_preview' ).hide().attr( 'src', '' );
				syncGroupFields();
			}
		} );
	} );
} )( jQuery );

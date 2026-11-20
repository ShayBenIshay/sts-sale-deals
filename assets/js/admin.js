( function ( $ ) {
	$( function () {
		$( '.sts-sd-color' ).wpColorPicker();

		// No parent = a group, which carries no label: hide the label design fields (their values stay in the form).
		var $parent = $( '#parent' );
		function syncGroupFields() {
			var v = String( $parent.val() );
			var isGroup = ! $parent.length || v === '-1' || v === '0';
			$( '.sts-sd-badge-fields' ).toggle( ! isGroup );
			$( '.sts-sd-group-note' ).toggle( isGroup );
		}
		if ( window.stsSaleDeals && stsSaleDeals.parentHint ) {
			$( '#parent-description' ).text( stsSaleDeals.parentHint );
		}
		$parent.on( 'change', syncGroupFields );
		syncGroupFields();

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

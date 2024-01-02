jQuery(document).ready( function( $ ) {

	$( document ).on( 'click', '.qr_pay_upload_image_button', function( e ) {
        e.preventDefault();
  
        var button = $( this ),
        aw_uploader = wp.media({
            title: 'Custom image',
            library : {
                uploadedTo : wp.media.view.settings.post.id, 
                type : 'image', //Allowed file type
				mimeTypes: ['image/jpeg','image/webp','image/jpg', 'image/png', 'image/gif', 'image/svg+xml'] // Allowed image file extensions
            },
            button: {
                text: 'Select This Image'
            },
            multiple: false
        }).on( 'select', function() {
            var attachment = aw_uploader.state().get( 'selection' ).first().toJSON();

			$( '.preview_area' ).html(
				'<img src="' + attachment.url + '" class="upload_qr" style="display: block; padding: 10px; width: 200px; border: 1px solid; margin-bottom: 10px;">' +
				'<button class="remove_qr button-secondary" type="button">Remove</button>'
			);
            $( '.qr_media_upload_url' ).val( attachment.url ); //media url
			$( '.qr_pay_preview_qr' ).val( attachment.url ); //preview url
        })
        .open();
			
    });

	 $( document ).on( 'click', '.remove_qr', function( e ) {
        e.preventDefault();
        $( '.upload_qr' ).remove();
        $( '.qr_media_upload_url' ).val(''); //media url
		$( '.qr_pay_preview_qr' ).val(''); //preview url
        $( this ).remove();
    });	
	
	// Add an event listener to update qr_pay_preview_qr when qr_media_upload_url is manually changed
	$( document ).on( 'input', '.qr_media_upload_url', function() {
		var manualInputUrl = $(this).val();
		$( '.qr_pay_preview_qr' ).val(manualInputUrl);
	});
	
});
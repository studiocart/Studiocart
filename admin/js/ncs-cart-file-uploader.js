(function( $ ) {

	'use strict';

	$(function() {

		var field, upload, remove;

		//Opens the Media Library, assigns chosen file URL to input field, switches links
		$( '.upload-file' ).on( 'click', function( e ) {          

            var parent = $(this).closest('.sc-field, .wrap-field, td');            
            field = parent.find( '[data-id="url-file"]' );
            remove = parent.find( '.remove-file' );
            upload = parent.find( '.upload-file' );

			// Stop the anchor's default behavior
			e.preventDefault();
			var file_frame, json;

			if ( undefined !== file_frame ) {
				file_frame.open();
				return;
			}

			file_frame = wp.media.frames.file_frame = wp.media({
				button: {
					text: 'Choose File',
				},
				frame: 'select',
				multiple: false,
				title: 'Choose File'
			});

			file_frame.on( 'select', function() {
				json = file_frame.state().get( 'selection' ).first().toJSON();
				if ( 0 > $.trim( json.url.length ) ) {
					return;
				}
				
				/*
				View all the properties in the console available from the returned JSON object
				for ( var property in json ) {
					console.log( property + ': ' + json[ property ] );
				}*/

				field.val( json.url );
				upload.toggleClass( 'hide' );
				remove.toggleClass( 'hide' );
			});

			file_frame.open();
		});

		//Remove value from input, switch links
		$( '.remove-file' ).on( 'click', function( e ) {
			// Stop the anchor's default behavior
			e.preventDefault();
            var parent = $(this).closest('.wrap-field, .sc-field, td');
            field = parent.find( '[data-id="url-file"]' );
            remove = parent.find( '.remove-file' );
            upload = parent.find( '.upload-file' );

			// clear the value from the input
			field.val('');
			
			// change the link message
			upload.toggleClass( 'hide' );
			remove.toggleClass( 'hide' );
		});

		// Handle upload
		$( '.sc-upload-file' ).on( 'click', function( e ) {
			// Stop the anchor's default behavior
			e.preventDefault();
            var parent = $(this).closest('.wrap-field, .sc-field, td');
            parent.find( '[name="async-upload"]' ).click();
		});

		$( '.sc-upload-file-field' ).on('change', function(e) {
			e.preventDefault();

			var formData = new FormData();
			var $imgFile = $(this);
			var parent = $(this).closest('.wrap-field, .sc-field, td');

			formData.append('action', 'upload-attachment');
			formData.append('async-upload', $imgFile[0].files[0]);
			formData.append('name', $imgFile[0].files[0].name);
			formData.append('type', 'sc_upload');
			formData.append('_wpnonce', sc_reg_vars.media_nonce);

			var url = sc_reg_vars.upload_url;

			$.ajax({
				url: sc_reg_vars.upload_url,
				data: formData,
				processData: false,
				contentType: false,
				dataType: 'json',
				/*xhr: function() {
					var myXhr = $.ajaxSettings.xhr();

					if ( myXhr.upload ) {
						myXhr.upload.addEventListener( 'progress', function(e) {
							if ( e.lengthComputable ) {
								var perc = ( e.loaded / e.total ) * 100;
								perc = perc.toFixed(2);
								$imgNotice.html('Uploading&hellip;(' + perc + '%)');
							}
						}, false );
					}

					return myXhr;
				},*/
				type: 'POST',
				/*beforeSend: function() {
					$imgFile.hide();
					$imgNotice.html('Uploading&hellip;').show();
				},*/
				success: function(resp) {
					if ( resp.success ) {
						field = parent.find( '[data-id="url-file"]' );
						field.val(resp.data.url);

					} else {
						alert('Something went wrong, please try again.');
					}
				}
			});
		});
	});
	
})( jQuery );
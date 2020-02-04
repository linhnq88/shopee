(function($){
        $('.wcvaattributecolorselect').wpColorPicker();



        var select2 = document.getElementById('display_type')	
        
		onChange2 = function(event) {
           var colordiv                  = this.options[this.selectedIndex].value == 'Color';
	       var imagediv                  = this.options[this.selectedIndex].value == 'Image';
	       var textblcokdiv              = this.options[this.selectedIndex].value == 'textblock';
	
  
	       document.getElementById('wcvacolorp').style.display         = colordiv ? '' : 'none';
	       document.getElementById('wcvaimagep').style.display         = imagediv ? '' : 'none';
	       document.getElementById('wcvatextblockp').style.display     = textblcokdiv ? '' : 'none';
	
        };


 
        if (window.addEventListener) {
            select2.addEventListener('change', onChange2, false);
        } else {
   
           select2.attachEvent('onchange2', function() {
               onChange2.apply(select2, arguments);
           });
        }


        $(".image-upload-div").each(function(){
    	    var parentId = $(this).closest('div').attr('idval');
		 		 // Only show the "remove image" button when needed
		    var srcvalue    = $('#thumbnail_id_' + parentId + '').val();
				
				if ( !srcvalue ){
				    jQuery('.wcva_remove_image_button_' + parentId + ' ').hide();
                }  
				// Uploading files
				var file_frame;

				jQuery(document).on( 'click', '.wcva_upload_image_button_' + parentId + ' ', function( event ){
                  
				   
					event.preventDefault();

					// If the media frame already exists, reopen it.
					if ( file_frame ) {
						file_frame.open();
						return;
					}

					// Create the media frame.
					file_frame = wp.media.frames.downloadable_file = wp.media({
						title: wcvaterm.uploadimage,
						button: {
							text: wcvaterm.useimage,
						},
						multiple: false
					});

					// When an image is selected, run a callback.
					file_frame.on( 'select', function() {
						attachment = file_frame.state().get('selection').first().toJSON();
						jQuery('#thumbnail_id_' + parentId + '').val( attachment.id );
						jQuery('#facility_thumbnail_' + parentId + ' img').attr('src', attachment.url );
						jQuery('.wcva_remove_image_button_' + parentId + '').show();
					});

					// Finally, open the modal.
					file_frame.open();
				});

				jQuery(document).on( 'click', '.wcva_remove_image_button_' + parentId + '', function( event ){
				    
					jQuery('#facility_thumbnail_' + parentId + ' img').attr('src', wcvaterm.placeholder );
					jQuery('#thumbnail_id_' + parentId + '').val('');
					jQuery('.wcva_remove_image_button_' + parentId + '').hide();
					return false;
				});
		 
	    });		
		
 
})(jQuery); 
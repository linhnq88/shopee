$(document).on('click','.add-variation-gallery-image',function(e1){

        e1.preventDefault();

        var addimagebutton = $( this ).find('button');
       

        
       
        var $image_gallery_ids = $(this).parent().find('.wmvi_variation_images');
        var $images_ui         = $(this).parent().find('.wmvi-image-ui-div').find('ul.images_ui');

      
    

    
        wp_default_media_upload = wp.media.frames.product_gallery = wp.media({
            states: [new wp.media.controller.Library({ multiple: true })]
        });

        /**
		 * Open wp media editor
		 */
        wp_default_media_upload.open();
		
        wp_default_media_upload.on( 'select', function() {
			
            var selection = wp_default_media_upload.state().get( 'selection' );
            var attachment_ids = $image_gallery_ids.val();
			
			/*
			var selection = wp.media.editor.state().get('selection');

            var attachment_ids = selection.map( function( attachment ) {
               attachment = attachment.toJSON();
               return attachment.id;
             }).join();
			*/
			

            selection.map( function( attachment ) {
                attachment = attachment.toJSON();

                if ( attachment.id ) {
                    attachment_ids   = attachment_ids ? attachment_ids + ',' + attachment.id : attachment.id;
                    
					var attachment_image = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                    
					var appendhtml   = '<li class="image" data-attachment_id="' + attachment.id + '"><img src="' + attachment_image + '" /><ul class="removeimage"><li><a class="wmvi-remove-image" ></a></li></ul></li>';
                    
					$images_ui.append( appendhtml );
                }
            });

            $image_gallery_ids.val( attachment_ids );
			
			
        });

       
        
         /**
		  * Now variation needs update
		  */		
		$( this )
                 .closest( '#variable_product_options' )
                 .find( '.woocommerce_variation' )
                 .addClass( 'variation-needs-update' );
 
        $( 'button.cancel-variation-changes, button.save-variation-changes' ).removeAttr( 'disabled' );
 
        $( '#variable_product_options' ).trigger( 'woocommerce_variations_defaults_changed' );


});
	
/**
 * code that handles image removal
 */
$(document).on('click',".wmvi-remove-image",function(el2){

        el2.preventDefault();

        var iconparentimage = $(this).parent().parent().parent();
		
		
        var attachment_id = $(iconparentimage).attr('data-attachment_id');

        $(iconparentimage).fadeOut();

        var selectedimageids = $(iconparentimage).parent().parent().find(".wmvi_variation_images").val();
        var splitids = selectedimageids.split(',');

        splitids = $.grep(splitids, function(value) {
            return value != attachment_id;
        });

        $(iconparentimage).parent().parent().find(".wmvi_variation_images").val(splitids);
        
		
		/**
		  * Now variation needs update
		  */
		
		$( this )
                 .closest( '#variable_product_options' )
                 .find( '.woocommerce_variation' )
                 .addClass( 'variation-needs-update' );
 
        $( 'button.cancel-variation-changes, button.save-variation-changes' ).removeAttr( 'disabled' );
 
        $( '#variable_product_options' ).trigger( 'woocommerce_variations_defaults_changed' );

});



                

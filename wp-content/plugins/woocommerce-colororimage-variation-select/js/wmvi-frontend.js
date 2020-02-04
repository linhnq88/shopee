jQuery( document ).ready( function( $ ) {
	'use strict';
    
	var class1  = '.product .images .flex-control-nav, .product .images .thumbnails';
	var class2  = '.woocommerce-product-gallery';
	var html1   = $( class1 ).html();
	var html2   = $( class2 ).html();
	var vform   = $( 'form.variations_form' );
    
	
	
	$.wmvi_js_prm = {
		

		changeimages: function( result, cvm ) {
		
				
					var parent = $( class2 ).parent();
					$( class2 ).remove();
					parent.prepend( result.main_images );
				

			if ( cvm ) {
				cvm();
			}
		},

		changeimagesOriginal: function( cvm ) {


				$( class1 ).fadeOut( 50, function() {
					$( this ).html( html1 ).hide().fadeIn( 100 );
				});
			


			if ( cvm ) {
				cvm();
			}
		},

		loadfunction: function() {

			var initial = true;

		
			
				vform.on( 'reset_image', function( event, variation ) {
					vform.trigger( 'wmvi_js_prm_reset_variation' );

					if ( initial ) {
						initial = false;
						return;
					}

					var $data = {
							action: 'wmvi_load_frontend_images',
							post_id: vform.data( 'product_id' )
						};
	                
					$( class2 ).block({
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 1.0
						}
					});
					
					
	
					$.post( wmvi_variation_images.ajaxurl, $data, function( result ) {
						if ( result.length ) {
							result = $.parseJSON( result );
	
							$.wmvi_js_prm.changeimages( result );
	
						} else {
	
							
							$.wmvi_js_prm.changeimagesOriginal();
						}
	                    
						$( class2 ).unblock();
						
					});
				});
			
			


			
			vform.on( 'show_variation', function( event, variation ) {

				var $data = {
						action: 'wmvi_load_frontend_images',
						variation_id: variation.variation_id,
						post_id: vform.data( 'product_id' )
					};
                
				$( class2 ).block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 1.0
					}
				});
				
				

				$.post( wmvi_variation_images.ajaxurl, $data, function( result ) {
					if ( result.length ) {
						result = $.parseJSON( result );

						
						$.wmvi_js_prm.changeimages( result );

					} else {

					
						$.wmvi_js_prm.changeimagesOriginal();
					}
                    
					$( class2 ).unblock();
					
					/**
					 * remove image if src is empty.
					 */
					$('ol.flex-control-nav.flex-control-thumbs img[src=""]').remove();
					
				});
			});

		
			vform.on( 'click', '.reset_variations', function() {
				$.wmvi_js_prm.reset();
			});

			
			vform.on( 'reset_image', function() {
				$.wmvi_js_prm.reset();
			});

			vform.trigger( 'wmvi_js_prm_loadfunction', [ class1, class2, html1, html2 ] );
		},
		
		
		
			reset: function( cvm ) {


				
			$( class1 ).fadeOut( 50, function() {
					$( this ).html( html1 ).hide().fadeIn( 100 );
			});
		
                if ( cvm ) {
				      cvm();
			        }
		    },
			
			hideGallery: function() {
			    $( class1 ).hide().css( 'visibility', 'hidden' );
		    },

		    showGallery: function() {
			     $( class1 ).css( 'visibility', 'visible' ).fadeIn( 'fast' );
		    },
	};
	

			
    

	$.wmvi_js_prm.loadfunction();


});

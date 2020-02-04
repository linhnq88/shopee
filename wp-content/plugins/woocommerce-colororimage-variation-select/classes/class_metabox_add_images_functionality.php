<?php
class wmvi_metabox_add_images_class {
	
	  /**
	   * Construct
	   * since version 1.0.0
	   */
        public function __construct() {
	   
         add_action('admin_enqueue_scripts', array(&$this, 'wmvi_register_scripts'));
	     add_action('woocommerce_variation_options', array($this, 'wmvi_add_upload_images_button'), 10, 3);
	     add_action('woocommerce_process_product_meta', array($this, 'wmvi_process_product_meta'), 10, 2);
		 add_action('woocommerce_save_product_variation', array($this,'wmvi_update_variation_meta'), 10, 2 );
	    }
		
	    /**
		 * saving variation images.
		 * 
		 * @since 1.0.0
		 */
		public function wmvi_update_variation_meta($post_id){
			
			
			if (isset($_POST['wmvi_variation_images'][ $post_id ])) {
				$wmvi_variation_images = sanitize_text_field($_POST['wmvi_variation_images'][ $post_id ]);
			} else {
			    $wmvi_variation_images = '';
			}
			
			
			update_post_meta( $post_id, 'wmvi_variation_images', $wmvi_variation_images );
			
		}
      
	   /**
	    * Adds required js/css assets
	    * since version 1.0.0
	    */
	   public function wmvi_register_scripts() {
		global $wp_query, $post;
		$screen         = get_current_screen();
        $screen_id      = $screen ? $screen->id : '';
		
		
		
		if ( in_array( $screen_id, array( 'product', 'edit-product' ) ) ) {
		
		
		  wp_register_script( 'wmvi-meta', ''.wcva_PLUGIN_URL.'js/wmvi-meta.js' );
		  wp_register_style( 'wmvi-meta', ''.wcva_PLUGIN_URL.'css/wmvi-meta.css' );
	      
	      wp_enqueue_script('wmvi-meta');
		  wp_enqueue_style('wmvi-meta');
		  
		}
	     
	   }
	    
		
		/**
	     * Adds html part on variation tab
	     * since version 1.0.0
	     */
	    public function wmvi_add_upload_images_button( $loop, $variation_data, $variation ) { 
		 ?>
		 <div class="wmvi-image-ui-main-div">
          <div class="wmvi-image-ui-div images_ui">
           <ul class="images_ui ui-sortable">
             <?php echo $this->wmvi_display_all_images($variation->ID); ?>
           </ul>
          
		   <input class="wmvi_variation_images" type="hidden" value="<?php echo get_post_meta( $variation->ID, 'wmvi_variation_images', true ) ?>" name="wmvi_variation_images[<?php echo $variation->ID; ?>]">
           </div>

          
         </div>
		 
		 
         <br/>
         <button type="submit" class="add-variation-gallery-image button" ><?php _e( 'Add More Images', 'wcva' ); ?></button>
		
         <?php  
        }

	  
	
	    /**
		 * displaying gallery images variation wise.
		 * 
		 * @since 1.0.0
		 */
		public function wmvi_display_all_images($variation_id){
			
			$wmvi_variation_images = get_post_meta( $variation_id, 'wmvi_variation_images', true );
             
			
			
			$attachments = array_filter( explode( ',', $wmvi_variation_images ) );

			$data_changed = false;
			
			if ( ! empty( $attachments ) ) {
				foreach ( $attachments as $attachment_id ) {
					$attachedimage = wp_get_attachment_image( $attachment_id, 'thumbnail' );

					
					if ( empty( $attachedimage ) ) {
						$data_changed = true;

						continue;
					}

                    ?>
					<li class="image" data-attachment_id="<?php echo( esc_attr( $attachment_id ) ); ?>">
                    <?php echo( $attachedimage ); ?>
                     <ul class="removeimage">
                     <li>
                      <a class="wmvi-remove-image"><?php echo( __( 'Remove', 'wcva' ) ); ?></a>
                     </li>
                     </ul>
                     </li>
					 <?php

					
					$wmvi_images[] = $attachment_id;
				}

			
				if ( $data_changed ) {
					update_post_meta( $variation_id, 'wmvi_variation_images', implode( ',', $wmvi_images ) );
				}
			}
		}
		
		

    }
         
new wmvi_metabox_add_images_class();

?>
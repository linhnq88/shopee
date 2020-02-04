<?php


class wmvi_frontend_functionality_class {
	


	public function __construct() {
		
        add_action( 'wp_enqueue_scripts', array( $this, 'wmvi_load_assets' ) );
	    add_action( 'wp_ajax_wmvi_load_frontend_images', array( $this, 'wmvi_ajax_images_load_function' ) );
	    add_action( 'wp_ajax_nopriv_wmvi_load_frontend_images', array( $this, 'wmvi_ajax_images_load_function' ) );
		
    }




	public function wmvi_load_assets() {
		

		wp_enqueue_script( 'wmvi-frontend', ''.wcva_PLUGIN_URL.'js/wmvi-frontend.js' , array('jquery'));
		wp_enqueue_style( 'wmvi-frontend', ''.wcva_PLUGIN_URL.'css/wmvi-frontend.css' );
		
        $wmvi_locals = array(
			'ajaxurl'                      => admin_url( 'admin-ajax.php' )
		);

		wp_localize_script( 'wmvi-frontend', 'wmvi_variation_images', $wmvi_locals );

		
	}





	public function wmvi_ajax_images_load_function() {
		


		$post_id = absint( $_POST['post_id'] );
		
		$wmvi_script    = plugins_url( 'woocommerce/assets/js/frontend/single-product.js' );
		
		$figureclasses  = 'woocommerce-product-gallery__image flex-active-slide';
		
		$maindivclasses = 'woocommerce-product-gallery woocommerce-product-gallery--with-images woocommerce-product-gallery--columns-4 images';

		if ( ! isset( $_POST['variation_id'] ) ) {
			
			$main_product_images = get_post_thumbnail_id( $post_id ) . ',' . get_post_meta( $post_id, '_product_image_gallery', true );
		
		} else {
			
			$variation_id = absint( $_POST['variation_id'] );

	        $main_product_images = get_post_meta( $variation_id, 'wmvi_variation_images', true );
			
		}

		$main_product_images = explode( ',', $main_product_images );

		$product = wc_get_product( $variation_id );


		if ( $product ) {
			$main_image_id = $product->get_image_id();

			if ( ! empty( $main_image_id ) ) {
				array_unshift( $main_product_images, $main_image_id );
			}
		}
        $wmvi_loop_html  = '<script type="text/javascript" src="' . $wmvi_script . '"></script>';
		$wmvi_loop_html .= '<div class="'.$maindivclasses.'" data-columns="4"><ol class="woocommerce-product-gallery__wrapper">';

		

		if ( count( $main_product_images ) >= 0 ) {
			
			foreach ( $main_product_images as $image_id ) {
				
				
				$image_title      = esc_attr( get_the_title( $image_id ) );
				$big_image        = wp_get_attachment_image_src( $image_id, 'full' );
				$thumbnail        = wp_get_attachment_image_src( $image_id, 'shop_thumbnail' );

				$prms = array(
					'title'                   => $image_title,
					'data-large_image'        => $big_image[0],
					'data-large_image_width'  => $big_image[1],
					'data-large_image_height' => $big_image[2],
				);

			    $attachment_img = wp_get_attachment_image( $image_id, 'shop_single', false, $prms );
 
				$wmvi_loop_html .= apply_filters( 'woocommerce_single_product_image_html', sprintf( '<li data-thumb="%s" class="' . $figureclasses. '">%s</li>', esc_url( $thumbnail[0] ), $attachment_img ), $post_id );

				
			} 
		} 

		$wmvi_loop_html .= '</ol></div>';
		

		echo json_encode( array( 'main_images' => $wmvi_loop_html ) );
		
		exit;
	}



}

new wmvi_frontend_functionality_class();

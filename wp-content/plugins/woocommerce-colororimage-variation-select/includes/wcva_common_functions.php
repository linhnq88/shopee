<?php

   /*
    * returns displytypenumber - which decide weather to replace variable.php template or not
	* @param $product-global product variable
	* @param $post-global post variable
	*/
	
	function wcva_return_displaytype_number($product = NULL,$post) {
	   
	   
	   $displaytypenumber = 0;
	   
	   $global_activation    = get_option("wcva_woocommerce_global_activation");
	   
	   
	   if (!is_product()) {
		   if ( is_page() || is_single()) {
			   
			   if (is_shop() || is_cart() || is_checkout()) {
				   return 0;
			   } else {
				   $displaytypenumber = 0;
			   }
			   
		   } else {
			   return 0;
		   }
	   } 
	   
		
	   
	   
	   if (isset($global_activation) && ($global_activation == "yes")) {
		   return 1;
	   }
	   
	   
	   
	   if ( ! empty( $post->post_content ) && strstr( $post->post_content, '[product_page' ) ) {
		  $post_content         = $post->post_content;
		  $shortcode_product_id = wcva_get_shortcode_product_id($post->post_content);
		  $product              = wc_get_product($shortcode_product_id);
		  $post_id              = $shortcode_product_id;
		  
		
	   } else {
		  $product              = wc_get_product($post->ID);
		  $post_id              = $post->ID;
		  
	   } 
	    
	   if (isset($post_id)) {
		  $post_type= get_post_type( $post_id );
		 }
	     
	   if (isset($post_type) && ($post_type == "product")) {
		  $product_id           = $product->get_id();
		  $product_type         = $product->get_type();
	   }
	     
	    
	   if (isset($product_id)) {
		   $_coloredvariables = get_post_meta( $product_id, '_coloredvariables', true );
	   }
        
	    
		
		
	    $displaytype="none";
		
	  if (isset($product_type) && ( $product_type == 'variable' )) {
	       $product = new WC_Product_Variable( $post_id ); 
	       $attributes = $product->get_variation_attributes(); 
	    
		}
	
	  if ((!empty($attributes)) && (sizeof($attributes) >0)) { 
	     
	    foreach ($attributes as $key=>$values) { 
		
	       if (isset($_coloredvariables[$key]['display_type'])) {
	         $displaytype=$_coloredvariables[$key]['display_type'];
	       }
		 
	     if (($displaytype == "colororimage") || ($displaytype == "variationimage"))  {
		     $displaytypenumber++;
		 }
	  } 
	 
	  }
	  
	  return $displaytypenumber;
	}
	
	/**
	 * Extract product id from [product_page] shortcode.
	 *
	 * @param $post->post_content - post content
	 * @since 1.6.2
	 */
	
	function wcva_get_shortcode_product_id($post_content) {
		global $post;
		
        
     
        $regex_pattern = get_shortcode_regex();
        preg_match ('/'.$regex_pattern.'/s', $post->post_content, $regex_matches);
        if ($regex_matches[2] == 'product_page') :
       
            $attribureStr = str_replace (" ", "&", trim ($regex_matches[3]));
            $attribureStr = str_replace ('"', '', $attribureStr);

            //  Parse the attributes
            $defaults = array (
                'preview' => '1',
            );
            $attributes = wp_parse_args ($attribureStr, $defaults);

            if (isset ($attributes["id"])) :
                return $attributes["id"];
            endif;
           
        endif;
	
	}
	
	/**
	 * Output a list of variation attributes for use in the cart forms.
	 *
	 * @param array $args
	 * @since 2.4.0
	 */
	function wcva_dropdown_variation_attribute_options1( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'options'          => false,
			'attribute'        => false,
			'product'          => false,
			'selected' 	       => false,
			'name'             => '',
			'id'               => '',
			'class'            => '',
			'show_option_none' => __( 'Choose an option', 'woocommerce' )
		) );
		
		$show_hidden_dropdown  = apply_filters('wcva_show_hidden_dropdown', "no" );
		
		if (isset($show_hidden_dropdown)	&& ($show_hidden_dropdown == "yes")) {
		    $hidden_select_css="";
	    } else {
		    $hidden_select_css="display:none !important;";
	    }

		$options   = $args['options'];
		$product   = $args['product'];
		$attribute = $args['attribute'];
		$name      = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
		$id        = $args['id'] ? $args['id'] : sanitize_title( $attribute );
		$class     = $args['class'];

		if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
			$attributes = $product->get_variation_attributes();
			$options    = $attributes[ $attribute ];
		}

		echo '<select style="'. $hidden_select_css .'" id="' . esc_attr( rawurldecode($id) ) . '" class="wcva-single-select ' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '">';

		if ( $args['show_option_none'] ) {
			echo '<option value="">' . esc_html( $args['show_option_none'] ) . '</option>';
		}

		if ( ! empty( $options ) ) {
			if ( $product && taxonomy_exists( $attribute ) ) {
				// Get terms if this is a taxonomy - ordered. We need the names too.
				$product_id = $product->get_id();
				$terms = wc_get_product_terms( $product_id, $attribute, array( 'fields' => 'all' ) );

				foreach ( $terms as $term ) {
					if ( in_array( $term->slug, $options ) ) {
						echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . apply_filters( 'woocommerce_variation_option_name', $term->name ) . '</option>';
					}
				}
			} else {
				foreach ( $options as $option ) {
					// This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
					$selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
					echo '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</option>';
				}
			}
		}

		echo '</select>';
	}
	
	
	/**
	 * Output a list of variation attributes for use in the cart forms.
	 *
	 * @param array $args
	 * @since 2.4.0
	 */
	function wcva_dropdown_variation_attribute_options2( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'options'          => false,
			'attribute'        => false,
			'product'          => false,
			'selected' 	       => false,
			'name'             => '',
			'id'               => '',
			'class'            => '',
			'show_option_none' => __( 'Choose an option', 'woocommerce' )
		) );

		$options   = $args['options'];
		$product   = $args['product'];
		$attribute = $args['attribute'];
		$name      = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
		$id        = $args['id'] ? $args['id'] : sanitize_title( $attribute );
		$class     = $args['class'];

		if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
			$attributes = $product->get_variation_attributes();
			$options    = $attributes[ $attribute ];
		}

		echo '<select id="' . esc_attr( rawurldecode($id) ) . '" class="' . esc_attr( $class ) . ' wcva-standard-select" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '">';

		if ( $args['show_option_none'] ) {
			echo '<option value="">' . esc_html( $args['show_option_none'] ) . '</option>';
		}

		if ( ! empty( $options ) ) {
			if ( $product && taxonomy_exists( $attribute ) ) {
				// Get terms if this is a taxonomy - ordered. We need the names too.
				$product_id = $product->get_id();
				$terms      = wc_get_product_terms( $product_id, $attribute, array( 'fields' => 'all' ) );

				foreach ( $terms as $term ) {
					if ( in_array( $term->slug, $options ) ) {
						echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . apply_filters( 'woocommerce_variation_option_name', $term->name ) . '</option>';
					}
				}
			} else {
				foreach ( $options as $option ) {
					// This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
					$selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
					echo '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</option>';
				}
			}
		}

		echo '</select>';
	}
	
	

	
	/**
	  * This function determines weather plugin should load shop swatches js/css file on current page. 
	  * Since version 2.2.2
	  * Used in Classes/class_shop_page_swatchs.php
	  */
	function wcva_load_shop_page_assets() {
	    $load_assests = "no";
		
		
		if (is_cart() || is_product() || is_shop() || (is_product_category()) || is_product_tag() || is_page()) {
			
			$load_assests = "yes";
			
		} else {
			
			$load_assests = "no";
			
		}
		
		return $load_assests;
	}
   
?>
<?php
class wcva_direct_variation_link {
	
	public function __construct() {
      $direct_variation_link_enable = get_option('woocommerce_shop_swatch_link',"no");	
	  
	  if (isset($direct_variation_link_enable) && ($direct_variation_link_enable == "yes")) {
		  add_filter('woocommerce_product_get_default_attributes', array(&$this, 'wcva_direct_variation_valueues'));
	  }
	  
	}
	
	public function wcva_direct_variation_valueues($selected_attributes) {
		
	 $attribute_options = $this->wcva_get_ending_variation_values();
	 $default_values = $this->wcva_get_variation_default_values( $attribute_options,$selected_attributes);
	
	  if(!empty($default_values)) {
		return $default_values;
	  } else {
		return $selected_attributes;
	 }
    }
	
	
	public function wcva_get_ending_variation_values() {
	 global $post, $pagenow;
	 $product              = wc_get_product($post->ID);
	 
	 
	 if (( 'post-new.php' != $pagenow ) && (is_product())) {
		 $available_variations = $product->get_variation_attributes();
	 }
	 
	 
	 $attribute_options = array();
	
	 if (isset($available_variations)) {
		  foreach ( $available_variations as $key => $variations ) {
		     array_push( $attribute_options, $key );
	      }
	 }
	
	
	 return $attribute_options;
   }
   
    public function wcva_get_variation_default_values( $attribute_options,$selected_attributes ) {
	 global $post, $pagenow;
     $product              = wc_get_product($post->ID);
	 
	 if (( 'post-new.php' != $pagenow ) && (is_product())) {
	  $product_attributes = $product->get_variation_attributes();
	 }
	 $_GET_lower         = array_change_key_case($_GET, CASE_LOWER);

	
	$default_values = array();

	foreach ( $attribute_options as $name ) {
	
	
		
		$lower_name = strtolower( $name );
		$global_name = str_replace( 'pa_', '', $lower_name );
		$found = false;
		
		
		if ( isset( $_GET_lower[ $lower_name ] ) ) {
		
			foreach( $product_attributes[ $name ] as $value ) {		
				if ( strtolower( $value ) == strtolower( $_GET_lower[ $lower_name ] ) ) {
					$found = true;
				}			
			}

			if ( $found == true ) {
				$default_values[ $lower_name ] = $_GET_lower[ $lower_name ];
			}
		
		
		} elseif ( isset( $_GET_lower[ $global_name ] ) ) {
		
			foreach( $product_attributes[ $name ] as $value ) {		
				if ( strtolower( $value ) == strtolower( $_GET_lower[ $global_name ] ) ) {
					$found = true;
				}			
			}

			if ( $found == true ) {
				$default_values[ $lower_name ] = $_GET_lower[ $global_name ];
			}
		} else {
			
			foreach ($selected_attributes as $default_attribute => $attribute_valueue) {
				if ($default_attribute == $name) {
				  $default_values[ $lower_name ] = $attribute_valueue;
				}
			}
			
		} 
	
	}
	
	return $default_values;
    }
}

new wcva_direct_variation_link();

?>
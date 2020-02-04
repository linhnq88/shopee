<?php
class wcva_register_style_scripts {

   public function __construct() {
       add_action( 'wp_enqueue_scripts', array(&$this,'wcva_register_my_scripts' ));
   }

   public function wcva_register_my_scripts() {
     global $post,$product;
       $displaytypenumber                   = 0;
	   $woocommerce_wcva_swatch_tooltip     = get_option('woocommerce_wcva_swatch_tooltip');
	   $wcva_swatch_behaviour               = get_option('wcva_disable_unavailable_options','01');
	   $woo_version                         =  wcva_get_woo_version_number();
	   $iPod                                = stripos($_SERVER['HTTP_USER_AGENT'],"iPod");
       $iPhone                              = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
       $iPad                                = stripos($_SERVER['HTTP_USER_AGENT'],"iPad");
       $woocommerce_wcva_disableios_tooltip = get_option('woocommerce_wcva_disableios_tooltip');
	   $woocommerce_show_selected_attribute_name = get_option('woocommerce_show_selected_attribute_name');
	   
	   if (isset($woocommerce_wcva_disableios_tooltip) && $woocommerce_wcva_disableios_tooltip == "yes" && ( $iPod || $iPhone || $iPad)) {
			  $woocommerce_wcva_swatch_tooltip ="no";
		}
        
       if (isset($wcva_swatch_behaviour)	&& ($wcva_swatch_behaviour == "02")) {
		      $wcva_disable_options = "yes";
		      $wcva_hide_options    = "no";
	   } elseif (isset($wcva_swatch_behaviour)	&& ($wcva_swatch_behaviour == "03")) {
              $wcva_disable_options = "yes";
              $wcva_hide_options    = "yes";
	   } else {
		      $wcva_disable_options ="no";
		      $wcva_hide_options    ="no";
	   }
	   
	   
	   
       if ( is_product()  ) {
	       
		 $product            = wc_get_product($post->ID);
		 $product_type       = $product->get_type();
		 $displaytypenumber = wcva_return_displaytype_number($product,$post);
       
	   } elseif  ( ! empty( $post->post_content ) && strstr( $post->post_content, '[product_page' ) ) {
		   
		 $product            = wc_get_product($post->ID);
		 $product_type       = get_post_type($post->ID);
		 $displaytypenumber = wcva_return_displaytype_number($product,$post);
		   
	   }
	   

	    
	  
	   wp_register_style( 'wcva-frontend', wcva_PLUGIN_URL . 'css/front-end.css' );
	   wp_register_style( 'powerTip', wcva_PLUGIN_URL . 'css/powerTip.css' );
      
	  $goahead=1;
	 
    if(isset($_SERVER['HTTP_USER_AGENT'])){
         $agent = $_SERVER['HTTP_USER_AGENT'];
      }
	  
	if (preg_match('/(?i)msie [5-8]/', $agent)) {
         $goahead=0;
     }
	 
	
	 
   
    if (($displaytypenumber >0) && ($goahead == 1) ) {
      
	   wp_register_script( 'product-frontend', wcva_PLUGIN_URL . 'js/product-frontend.js' ,array( 'jquery'), false, true);
	   
	   
	   $wcva_localize = array(
	    'tooltip'         => $woocommerce_wcva_swatch_tooltip,
		'disable_options' => $wcva_disable_options,
		'hide_options'    => $wcva_hide_options,
		'show_attribute'  => $woocommerce_show_selected_attribute_name,
		'quick_view'      => wcva_quick_view_mode
	   );
	   
       wp_localize_script( 'product-frontend', 'wcva', $wcva_localize );
	  
   	   wp_register_script( 'powerTip', wcva_PLUGIN_URL . 'js/powerTip.js' ,array( 'jquery'), false, true);
	   
	  
	}
	
	if ( ((is_product()) && ($product_type == "variable")) || ( ! empty( $post->post_content ) && strstr( $post->post_content, '[product_page' ) ) ) {
	   if (($displaytypenumber >0) && ($goahead == 1)) {
		
	    wp_enqueue_script('product-frontend'); 
		wp_deregister_script('wc-add-to-cart-variation'); 
        wp_dequeue_script ('wc-add-to-cart-variation'); 
		
		if  ($woo_version < 3.0) {
		    if ($woo_version < 2.5) {
				
	            wp_register_script( 'wc-add-to-cart-variation', wcva_PLUGIN_URL . 'js/add-to-cart-variation1.js' ,array( 'jquery'), false, true);
	            
		    } else {
			    wp_register_script( 'wc-add-to-cart-variation', wcva_PLUGIN_URL . 'js/add-to-cart-variation2.js' ,array( 'jquery', 'wp-util' ), false, true);
		    }
		} else {
			    wp_register_script( 'wc-add-to-cart-variation', wcva_PLUGIN_URL . 'js/add-to-cart-variation3.js' ,array( 'jquery', 'wp-util' ), false, true);
		}
		
		wp_enqueue_script('wc-add-to-cart-variation'); 
	
	    
		wp_enqueue_style('wcva-frontend');
	   
	   if (isset($woocommerce_wcva_swatch_tooltip) && ($woocommerce_wcva_swatch_tooltip == "yes")) {
		  
		  
 		   
		  if (isset($woocommerce_wcva_disableios_tooltip) && $woocommerce_wcva_disableios_tooltip == "yes" && ( $iPod || $iPhone || $iPad)) {
			  wp_deregister_script('powerTip');
	          wp_deregister_style('powerTip');
		  } else {
			  wp_enqueue_script('powerTip');
	          wp_enqueue_style('powerTip');
		  }
		   
		      
	   
	   }
	    
	 }
	    
	}
	}
	
   }

new wcva_register_style_scripts();



?>

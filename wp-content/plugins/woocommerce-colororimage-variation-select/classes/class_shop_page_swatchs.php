<?php
class wcva_shop_page_swatches {

    public function __construct() {

	    add_action('init', array(&$this, 'wcva_shop_page_init'));
	    add_action( 'wp_enqueue_scripts', array(&$this,'wcva_register_shop_scripts' ));
	}
	
	public function wcva_shop_page_init() {
		
		$swatch_location  = get_option('woocommerce_shop_swatches_display',"01");
		
		switch($swatch_location) {
			
			case "01":
			  add_action('woocommerce_after_shop_loop_item_title', array(&$this, 'wcva_change_shop_attribute_swatches'));
			break;
			  
			case "02":
			  add_action('woocommerce_before_shop_loop_item_title', array(&$this, 'wcva_change_shop_attribute_swatches'));
			break;
			
			case "03":
			  add_action('woocommerce_after_shop_loop_item', array(&$this, 'wcva_change_shop_attribute_swatches'));
			break;
			 
			default:
			  add_action('woocommerce_after_shop_loop_item_title', array(&$this, 'wcva_change_shop_attribute_swatches'));
			
		}
		
	   
	}
    
	public function wcva_register_shop_scripts() {
		
        
	    require_once 'wcva_mobile_detect.php';
	    
		$mobile_click  = get_option('woocommerce_wcva_disable_mobile_hover',0);
	    $load_assets   = wcva_load_shop_page_assets();
	    $detect        = new WCVA_Mobile_Detect;
        
        
      
    
	   
        if (isset($load_assets) && ($load_assets == "yes")) {
		   
		    wp_enqueue_script('jquery');
           
		    if (isset($mobile_click) && ($mobile_click == "yes") && ( $detect->isMobile() ) ) {
			  wp_enqueue_script( 'wcva-shop-frontend-mobile', ''.wcva_PLUGIN_URL.'js/shop-frontend-mobile.js');
		    } else {
			  wp_enqueue_script( 'wcva-shop-frontend', ''.wcva_PLUGIN_URL.'js/shop-frontend.js');
		    }
		   
		   
		    wp_enqueue_style( 'wcva-shop-frontend', ''.wcva_PLUGIN_URL.'css/shop-frontend.css');
       
	    }

	}
	
	public function wcva_change_shop_attribute_swatches($product) {
	  global $product; 
	  
	  $product_type             =  $product->get_type();
	  $product_id               =  $product->get_id();
	  $shop_swatches            =  get_post_meta( $product_id, '_shop_swatches', true );
	  $shop_swatches_attribute  =  get_post_meta( $product_id, '_shop_swatches_attribute', true );
	  $fullarray                =  get_post_meta( $product_id, '_coloredvariables', true );
	  $template                 =  '';
      $display_shape            =  'wcvasquare';
	  $newvaluearray            = array();
	  
	    if (isset($shop_swatches) && ($shop_swatches == "yes")) {
		  
		    if (isset($shop_swatches_attribute) && ($shop_swatches != "")) {
		  
		        if ( taxonomy_exists( $shop_swatches_attribute ) ) {
		   
		            $terms = wc_get_product_terms( $product_id, $shop_swatches_attribute, array( 'fields' => 'all' ) );
		  
		            foreach ($terms as $term) {
			 
			            if (isset($fullarray[$shop_swatches_attribute]['values']) && (!empty($fullarray[$shop_swatches_attribute]['values']))) {
					
					        foreach ($fullarray[$shop_swatches_attribute]['values'] as $key=>$value) {
				               if ($key == $term->slug) {
					                $newvaluearray[$shop_swatches_attribute]['values'][$key]            = $fullarray[$shop_swatches_attribute]['values'][$key];
					                $newvaluearray[$shop_swatches_attribute]['values'][$key]['term_id'] = $term->term_id;
						            $newvaluearray[$shop_swatches_attribute]['display_type']            = $fullarray[$shop_swatches_attribute]['display_type'];
				                }
			                }
				        }
				
		            }
	            }
		
		    }
	    
		}
	  
	  
	  
	    if (isset($fullarray[$shop_swatches_attribute]['displaytype']) && ($fullarray[$shop_swatches_attribute]['displaytype'] == 'round')) {
		    $display_shape            =  'wcvaround';
	    }
	  
	        
	    if (isset($fullarray[$shop_swatches_attribute]['values']) ) {
			$_values                  =  $fullarray[$shop_swatches_attribute]['values'];
		}
	      
	
	    if (($product_type == 'variable') && isset($shop_swatches) && ($shop_swatches == "yes") ) {
	     
		    if ((isset($newvaluearray)) && (!empty($newvaluearray))) {
			 
			    if (isset($shop_swatches_attribute) && ($newvaluearray[$shop_swatches_attribute]['display_type'] == "colororimage" || $newvaluearray[$shop_swatches_attribute]['display_type'] == "global")) {
		           $template=$this->wcva_variable_swatches_template($newvaluearray[$shop_swatches_attribute]['values'],$shop_swatches_attribute,$product_id,$display_shape,$newvaluearray[$shop_swatches_attribute]['display_type']);
	            } 
				
		    } else {
			 
			    if (isset($shop_swatches_attribute) && ($fullarray[$shop_swatches_attribute]['display_type'] == "colororimage" || $fullarray[$shop_swatches_attribute]['display_type'] == "global")) {
		           $template=$this->wcva_variable_swatches_template($_values,$shop_swatches_attribute,$product_id,$display_shape,$fullarray[$shop_swatches_attribute]['display_type']);
	            } 
			 
		    }
		 
		 
		 
	    }
	  
	  return $template;
	}



	 /**
	  * Shows text for variable products with swatches enabled
	  * @$values- attribute value array of swatch settings
	  * @name- attribute name
	  * $pid - product id to get product url
	  */
	public function wcva_variable_swatches_template($values,$name,$pid,$display_shape,$main_display_type ) { 
	  
	        $imagewidth        = get_option('woocommerce_shop_swatch_width',"32");  
            $imageheight       = get_option('woocommerce_shop_swatch_height',"32");  
		    $global_activation = get_option('wcva_woocommerce_global_activation');
			$wcva_global       = get_option('wcva_global');
			$hover_image_size  = get_option('woocommerce_hover_imaga_size',"shop_catalog");  
			$direct_link       = get_option('woocommerce_shop_swatch_link', "no");  
			$product_url       = get_permalink( $pid );
			$mobile_click      = get_option('woocommerce_wcva_disable_mobile_hover',"no");
	   
	        require_once 'wcva_mobile_detect.php';
	   
	        $detect = new WCVA_Mobile_Detect;
			
			if (isset($mobile_click) && ($mobile_click == "yes") && ( $detect->isMobile() ) ) {
			    $load_direct_variation = "no";
			} else {
				$load_direct_variation = "yes";
			}
			
		
        ?>
	<div class="shopswatchinput" prod-img="">
	    <?php  
		
		$load_assets   = wcva_load_shop_page_assets();
      
    
	   
        if (isset($load_assets) && ($load_assets == "yes")) {
		
	        foreach ($values as $key=>$value) { 

            
			    $lower_name       =   strtolower( $name );
			    $clean_name       =   str_replace( 'pa_', '', $lower_name );
			    $lower_key        =   rawurldecode($key);
			    $direct_url       =  ''.$product_url.'?'.$clean_name.'='.$lower_key.'';
			
			    if ($main_display_type == "global") {
				
				    if (isset($global_activation) && $global_activation == "yes") {
				
				        if ($wcva_global[$name]['displaytype'] == "round") {
				 	        $display_shape =  'wcvaround';
				        }
		            }
				
			            $swatchtype       = get_woocommerce_term_meta( $value['term_id'], 'display_type', true );
				        $swatchcolor      = get_woocommerce_term_meta( $value['term_id'], 'color', true );
				        $attrtextblock    = get_woocommerce_term_meta( $value['term_id'], 'textblock', true );
				        $swatchimage      = absint( get_woocommerce_term_meta( $value['term_id'], 'thumbnail_id', true ) );
				        $hoverimage       = absint( get_woocommerce_term_meta( $value['term_id'], 'hoverimage', true ) );
			    
			    } else {
				        
						$swatchtype       = $value['type'];
				        $swatchcolor      = $value['color'];
				        $swatchimage      = $value['image'];
				        $hoverimage       = $value['hoverimage'];
				        $attrtextblock    = $value['textblock'];
			    }
			
			

                $swatchimageurl   =  apply_filters('wcva_swatch_image_url',wp_get_attachment_thumb_url($swatchimage),$swatchimage);
			    $hoverimage       =  wp_get_attachment_image_src($hoverimage,$hover_image_size);
                $hoverimageurl    =  apply_filters('wcva_hover_image_url',$hoverimage[0],$hoverimage[0]);
			 
			
			
			 
			    if (isset($swatchtype)) {
				    switch ($swatchtype) {
             	        case 'Color':
             		        ?>
                            <a <?php if ((isset($direct_link)) && ($direct_link == "yes") && ( $load_direct_variation == "yes" )) { ?> href="<?php echo $direct_url; ?>" <?php } ?> class="wcvaswatchinput" data-o-src="<?php if (isset($hoverimageurl)) { echo $hoverimageurl; } ?>" style="width:<?php echo $imagewidth; ?>px; height:<?php echo $imageheight; ?>px;">
                            <div class="wcvashopswatchlabel <?php echo $display_shape; ?>" style="background-color:<?php if (isset($swatchcolor)) { echo $swatchcolor; } else { echo '#ffffff'; } ?>; width:<?php echo $imagewidth; ?>px; float:left; height:<?php echo $imageheight; ?>px;"></div>
                            </a>
             		        <?php
             		    break;

             	        case 'Image':
             		        ?>
                            <a <?php if ((isset($direct_link)) && ($direct_link == "yes") && ( $load_direct_variation == "yes" )) { ?> href="<?php echo $direct_url; ?>" <?php } ?> class="wcvaswatchinput" data-o-src="<?php if (isset($hoverimageurl)) { echo $hoverimageurl; } ?>" >
                            <div class="wcvashopswatchlabel <?php echo $display_shape; ?>"  style="background-image:url(<?php if (isset($swatchimageurl)) { echo $swatchimageurl; } ?>); background-size: <?php echo $imagewidth; ?>px <?php echo $imageheight; ?>px; float:left; width:<?php echo $imagewidth; ?>px; height:<?php echo $imageheight; ?>px;"></div>
                            </a>
             		        <?php
             		    break;
				
				        case 'textblock':
             		        ?>
                            <a <?php if ((isset($direct_link)) && ($direct_link == "yes") && ( $load_direct_variation == "yes" )) { ?> href="<?php echo $direct_url; ?>" <?php } ?> class="wcvaswatchinput" data-o-src="<?php if (isset($hoverimageurl)) { echo $hoverimageurl; } ?>" style="width:<?php echo $imagewidth; ?>px; height:<?php echo $imageheight; ?>px;">
                            <div class="wcvashopswatchlabel wcva_shop_textblock <?php echo $display_shape; ?>" style="min-width:<?php echo $imagewidth; ?>px; "><?php  if (isset($attrtextblock)) { echo $attrtextblock; }   ?></div>
                            </a>
             		        <?php
             		    break;
             	
             
                    } 
			    }
			 
            
            }
		}		?>
	</div>
	     
	<?php 
	
	}



}

new wcva_shop_page_swatches();
?>
<?php
class wcva_override_variable_template {
    /*
	 * Construct 
	 */
    public function __construct() {
	
     add_action( 'woocommerce_locate_template', array(&$this,'wcva_override_default_variable_template'), 10, 3 );
    }
    
	/*
	 * Overrides core variables template
	 * since 1.0.0
	 */
	public function wcva_override_default_variable_template( $template, $template_name, $template_path ) {
      global $woocommerce,$post,$product;
	   $displaytypenumber = 0;
	   
      
	      $displaytypenumber = wcva_return_displaytype_number($product,$post);
      
	   
	   $goahead=1;

	 if (isset($_SERVER['HTTP_USER_AGENT'])){
         $agent = $_SERVER['HTTP_USER_AGENT'];
      }
	
	if (preg_match('/(?i)msie [5-8]/', $agent))  {
         $goahead=0;
     }
	 
       
      if ( ($goahead == 1) && strstr($template, 'variable.php') && ($displaytypenumber >0)) {
       $template = wcva_plugin_path() . '/woocommerce/single-product/add-to-cart/variable.php';
      }
    
     
    
     return $template;
    
    }

   }
   
   
   

new wcva_override_variable_template();

?>
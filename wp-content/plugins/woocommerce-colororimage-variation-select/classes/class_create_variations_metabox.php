<?php
class wcva_add_colored_variation_metabox {
    /*
	 * Construct
	 * since version 1.0.0
	 */
       public function __construct() {
	   
	     add_action('admin_enqueue_scripts', array(&$this, 'wcva_register_scripts'));
	     add_action('woocommerce_product_write_panel_tabs', array($this, 'wcva_add_colored_variable_metabox'));
	     add_action('woocommerce_product_data_panels', array($this, 'colored_variable_tab_options'));
	     add_action('woocommerce_process_product_meta', array($this, 'process_product_meta_colored_variable_tab'), 10, 2);
	   
	   }
	/*
	 * Add metabox tab
	 * since version 1.0.0
	 */
	   public function wcva_register_scripts() {
	   
	      wp_register_script( 'wcva-meta', ''.wcva_PLUGIN_URL.'js/wcva-meta.js' );
	      wp_register_script( 'jquery.accordion', ''.wcva_PLUGIN_URL.'js/jquery.accordion.js' );
	      wp_register_style( 'wcva-meta', ''.wcva_PLUGIN_URL.'css/wcva-meta.css' );
	      wp_register_style( 'jquery.accordion', ''.wcva_PLUGIN_URL.'css/jquery.accordion.css' );
	      wp_register_style( 'example-styles', ''.wcva_PLUGIN_URL.'css/example-styles.css' );
          $translation_array = array( 
		      'uploadimage'    => __( 'Choose an image' ,'wcva'),
			  'useimage'       => __( 'Use Image' ,'wcva'),
			  'placeholder'    => wedd_placeholder_img_src(),
		    );
           wp_localize_script( 'wcva-meta', 'wcvameta', $translation_array );
	     
	   
	     
	   }
	/*
	 * Add metabox tab
	 * since version 1.0.0
	 */
	   
       public function wcva_add_colored_variable_metabox() {
	   ?>
        <a href="#colored_variable_tab_data"><li class="colored_variable_tab show_if_variable" >&nbsp;&nbsp;<?php _e('WooSwatches', 'wcva'); ?></a></li>
	   <?php }
	

	/*
	 * Adds metabox tab content
	 * since version 1.0.0
	 */
	   public function colored_variable_tab_options() {
	     global $post,$woocommerce;
	   
	        $woo_version              =  wcva_get_woo_version_number();
	        $_coloredvariables        =  get_post_meta( $post->ID, '_coloredvariables', true );
			$shop_swatches            =  get_post_meta( $post->ID, '_shop_swatches', true );
			$shop_swatches_attribute  =  get_post_meta( $post->ID, '_shop_swatches_attribute', true );
	        $helpimg                  =  ''.wcva_PLUGIN_URL.'images/help.png';
	        
			
            
			
	        wp_enqueue_script('wcva-meta');
	        wp_enqueue_script('jquery.accordion');
	        wp_enqueue_style('wcva-meta');
	        wp_enqueue_style('jquery.accordion');
	        wp_enqueue_style('jquery.accordion');
	        wp_enqueue_style('example-styles');
	        wp_enqueue_script('wp-color-picker');
            wp_enqueue_style( 'wp-color-picker' );
	        wp_enqueue_media();
	 
	        /**
	         * Includes Metabox form
	         */
	        include('forms/wcva_variation_select_tab_content.php');
      ?>    
            
	        
	  <?php
    }
	
	
	/**
	 * Adds save metabox tab options
	 * @$post_id - product id
	 */
    
    public function process_product_meta_colored_variable_tab($post_id) {
	
	      $shop_swatches          = isset( $_POST['shop_swatches'] ) ? 'yes' : 'no';
	    
	      if (isset($_POST['coloredvariables']))
	         update_post_meta( $post_id, '_coloredvariables', $_POST['coloredvariables'] );
		  
		  if (isset($shop_swatches))
	         update_post_meta( $post_id, '_shop_swatches', $shop_swatches );

	      if (isset($_POST['shop_swatches_attribute']))
	         update_post_meta( $post_id, '_shop_swatches_attribute', $_POST['shop_swatches_attribute'] );
		
	   
        
	}	
	   
}
new wcva_add_colored_variation_metabox();

?>
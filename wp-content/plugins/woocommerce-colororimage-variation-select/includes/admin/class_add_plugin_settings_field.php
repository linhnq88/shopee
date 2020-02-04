<?php

class WCVA_wcva_settings {
    /**
     * Bootstraps the class and hooks required actions & filters.
     *
     */
    public static function init() {
		add_filter( 'plugin_action_links_' . wcva_base_url , __CLASS__ . '::wcva_add_action_links' );
        add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
        add_action( 'woocommerce_settings_tabs_wcva_settings', __CLASS__ . '::settings_tab' );
        add_action( 'woocommerce_update_options_wcva_settings', __CLASS__ . '::update_settings' );
		add_action( 'woocommerce_admin_field_wcva_global', 'wcva_global_settings' );
		add_action( 'admin_enqueue_scripts', __CLASS__ . '::wcva_admin_scripts' );
		
    }
	
	public static function wcva_admin_scripts() {
		if ((isset($_GET['page']) && ($_GET['page'] == "wc-settings")) && (isset($_GET['tab']) && ($_GET['tab'] == "wcva_settings"))) {
			 wp_register_script( 'wcva-admin', wcva_PLUGIN_URL . 'js/wcva_admin.js' , array( 'jquery'), false, true);
			 wp_enqueue_script ('wcva-admin');
		}
	}
    
     public static function wcva_add_action_links ( $links ) {
           $mylinks = array(
              '<a href="' . admin_url( '/admin.php?page=wc-settings&tab=wcva_settings' ) . '">Settings</a>',
             );
           return array_merge( $links, $mylinks );
      }

    public static function add_settings_tab( $settings_tabs ) {
        $settings_tabs['wcva_settings'] = __( 'WooSwatches', 'wcva' );
        return $settings_tabs;
    }
  
    public static function settings_tab() {
        woocommerce_admin_fields( self::get_settings() );
    }

    public static function update_settings() {
        woocommerce_update_options( self::get_settings() );
    }

    public static function get_settings() {
        $settings = array(
            array(
                'name'     => __( 'Color/image Swatches Settings', 'wcva' ),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'wc_wcva_settings_section'
            ),

			
			 array(
			              'name'     => __( 'Product page custom swatches height', 'wcva' ),

                          'desc_tip' => __( 'Custom swatch height on product page.you will need to chose custom as display type in variation select tab.', 'wcva' ),

                          'id'       => 'woocommerce_custom_swatch_height',

                          'type'     => 'text',

                          'css'      => 'width:35px;',
          
                          'default'  => '32', 

                          'desc'     => 'px'
                          
            ),
			 array(
			              'name'     => __( 'Product page custom swatches width', 'wcva' ),

                          'desc_tip' => __( 'Custom swatch height on product page.you will need to chose custom as display type in variation select tab.', 'wcva' ),

                          'id'       => 'woocommerce_custom_swatch_width',

                          'type'     => 'text',

                          'css'      => 'width:35px;',
          
                          'default'  => '32', 

                          'desc'     => 'px'
                          
            ),
			array(
			              'name'     => __( 'Enable tooltip on swatches', 'wcva' ),

                          'id'       => 'woocommerce_wcva_swatch_tooltip',

                          'type'     => 'checkbox',
          
                          'default'  => 'no'
                          
            ),
			array(
			              'name'     => __( 'Show selected attribute name on single product page', 'wcva' ),

                          'id'       => 'woocommerce_show_selected_attribute_name',

                          'type'     => 'checkbox',
          
                          'default'  => 'yes'
                          
            ),
			
			array(
			              'name'     => __( 'Disable tooltip on iOS devices', 'wcva' ),

                          'id'       => 'woocommerce_wcva_disableios_tooltip',

                          'type'     => 'checkbox',
          
                          'default'  => 'no'
                          
            ),
			

			
			 array(
			               'title'    => __( 'Shop swatches location', 'wcva' ),
					       'desc'     => __( 'This controls location of shop swatches on shop/category/archive pages.', 'woocommerce' ),
					       'id'       => 'woocommerce_shop_swatches_display',
					       'class'    => 'chosen_select',
					       'css'      => 'min-width:300px;',
					       'default'  => '01',
					       'type'     => 'select',
					       'options'  => array(
						      '01'              => __( 'After item title and price', 'wcva' ),
						      '02'              => __( 'Before item title and price', 'wcva' ),
						      '03'              => __( 'After select options button', 'wcva' ),
						
					        ),
					        
                          
            ),
			
			array(
			               'title'    => __( 'Shop hover image size', 'wcva' ),
					       'desc'     => __( 'This controls size of hover image on shop/category/archive pages.', 'woocommerce' ),
					       'id'       => 'woocommerce_hover_imaga_size',
					       'class'    => 'chosen_select',
					       'css'      => 'min-width:300px;',
					       'default'  => 'shop_catalog',
					       'type'     => 'select',
					       'options'  => array(
						      'shop_catalog'        => __( 'Shop catalog size', 'wcva' ),
						      'thumbnail'           => __( 'Thumbnail size', 'wcva' ),
						      'medium'              => __( 'Medium Size', 'wcva' ),
							  'large'               => __( 'Full Size', 'wcva' ),
						
					        ),
					        
                          
            ),
		
			array(
			               'title'    => __( 'Unavailable options behavior', 'wcva' ),
					       'id'       => 'wcva_disable_unavailable_options',
					       'class'    => 'chosen_select',
					       'css'      => 'min-width:300px;',
					       'default'  => '01',
					       'type'     => 'select',
					       'options'  => array(
						      '01'        => __( 'Default - do not disable or hide unavailable options', 'wcva' ),
							  '02'        => __( 'Disable unavailable options', 'wcva' ),
							  '03'        => __( 'Hide unavailable options', 'wcva' )
						    ),
					        
                          
            ),
			
			 array(
			              'name'     => __( 'Shop swatches height', 'wcva' ),

                          'desc_tip' => __( 'Swatches height on shop page.', 'wcva' ),

                          'id'       => 'woocommerce_shop_swatch_height',

                          'type'     => 'text',

                          'css'      => 'width:35px;',
          
                          'default'  => '32', 

                          'desc'     => 'px'
                          
            ),
			 array(
			              'name'     => __( 'Shop swatches width', 'wcva' ),

                          'desc_tip' => __( 'Swatches width on shop page.', 'wcva' ),

                          'id'       => 'woocommerce_shop_swatch_width',

                          'type'     => 'text',

                          'css'      => 'width:35px;',
          
                          'default'  => '32', 

                          'desc'     => 'px'
                          
            ),
			 array(
			              'name'     => __( 'Replace hover with click on mobile devices', 'wcva' ),

                          'id'       => 'woocommerce_wcva_disable_mobile_hover',

                          'type'     => 'checkbox',
          
                          'default'  => 'no'
                          
            ),
			 array(
			              'name'     => __( 'Enable direct variation link', 'wcva' ),

                          'id'       => 'woocommerce_shop_swatch_link',

                          'type'     => 'checkbox',
          
                          'default'  => 'no', 

                          'desc_tip'     => 'Plugin uses inbuilt direct variation link feature.No need to use any third party plugin for this.'
                          
            ),
			 array(
			              'name'     => __( 'Enable default attribute options', 'wcva' ),

                          'id'       => 'wcva_woocommerce_global_activation',

                          'type'     => 'checkbox',
          
                          'default'  => 'no', 
						  
						  'desc_tip' => 'if enabled all those product attributes which does not have display type set under variation select tab will inherit the display type value from below given "default attribute options" table.you will still be able to override the value on product edit page.'
                          
            ),
			  			 
			
			
			
		
			
			array(     'type'            => 'wcva_global',
					   'id'              => 'wcva_global'
					  
			
			),
			
          
            'section_end' => array(
                 'type' => 'sectionend',
                 'id' => 'wc_wcva_settings_section'
            )
        );
	
        return apply_filters( 'wc_wcva_settings_settings', $settings );
    }
	

}


function wcva_global_settings() {
	   $attribute_taxonomies = wc_get_attribute_taxonomies();
	   $global_activation    = get_option("wcva_woocommerce_global_activation");
	   $wcva_global          = get_option("wcva_global");
	   

	   ?>
		<tr valign="top" style="<?php if (isset($global_activation) && ($global_activation == "yes")) { echo 'display:;'; } else {echo 'display:none;';} ?>">
			<th scope="row" class="titledesc"><?php _e( 'Default attribute options', 'wcva' ) ?></th>
			<td class="forminp">
				<table class="widefat wp-list-table" cellspacing="0">
					<thead>
						<tr>
							<th width="15%" class="name">&emsp;<?php _e( 'Attribute', 'wcva' ); ?></th>
							<th>&emsp;<?php _e( 'Display Type', 'wcva' ); ?></th>
							<th width="40%">&emsp;<?php _e( 'Size', 'wcva' ); ?></th>
							<th>&emsp;<?php _e( 'Show Name', 'wcva' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php if ((!empty($attribute_taxonomies)) && (sizeof($attribute_taxonomies) >0)) : ?>
						<?php foreach ($attribute_taxonomies as $value) : ?>
						<?php $value           = json_decode(json_encode($value), True); ?>
						<?php $attribute_name  = $value['attribute_name']; ?>
						<?php $global_attribute_name  = 'pa_'.$value['attribute_name'].''; ?>
						<tr>
						        <td width="15%" class="name">&emsp;
									<span class="name"><?php echo $attribute_name; ?></span>
								</td>
								<td class="status">
									 <select name="wcva_global[pa_<?php echo $attribute_name; ?>][display_type]">
	                                     <option value="none"><span class="wcvaformfield"><?php echo __('Dropdown Select','wcva'); ?></span></option>
		                                 <option value="colororimage" <?php if ((isset($wcva_global[$global_attribute_name]['display_type'])) && ($wcva_global[$global_attribute_name]['display_type'] == "colororimage")) {echo "selected";} ?>><span class="wcvaformfield"><?php echo __('Color or Image','wcva'); ?></span></option>
	                                 </select>
								</td>
								<td width="40%">
								    <select name="wcva_global[pa_<?php echo $attribute_name; ?>][size]">
	                                  <option value="small"  <?php if ((isset($wcva_global[$global_attribute_name]['size'])) && ($wcva_global[$global_attribute_name]['size'] == "small")) {echo "selected";} ?>><span class="wcvaformfield"><?php echo __('Small (32px * 32px)','wcva'); ?></span></option>
		                              
									  <option value="extrasmall" <?php if ((isset($wcva_global[$global_attribute_name]['size'])) && ($wcva_global[$global_attribute_name]['size'] == "extrasmall")) {echo "selected";} ?>><span class="wcvaformfield"><?php echo __('Extra Small (22px * 22px)','wcva'); ?></span></option>
		                              
									  <option value="medium" <?php if ((isset($wcva_global[$global_attribute_name]['size'])) && ($wcva_global[$global_attribute_name]['size'] == "medium")) {echo "selected";} ?>><span class="wcvaformfield"><?php echo __('Middle (40px * 40px)','wcva'); ?></span></option>
		                             
									  <option value="big" <?php if ((isset($wcva_global[$global_attribute_name]['size'])) && ($wcva_global[$global_attribute_name]['size'] == "big")) {echo "selected";} ?>><span class="wcvaformfield"><?php echo __('Big (60px * 60px)','wcva'); ?></span></option>
		                              
									  <option value="extrabig" <?php if ((isset($wcva_global[$global_attribute_name]['size'])) && ($wcva_global[$global_attribute_name]['size'] == "extrabig")) {echo "selected";} ?>><span class="wcvaformfield"><?php echo __('Extra Big (90px * 90px)','wcva'); ?></span></option>
			                          
									  <option value="custom" <?php if ((isset($wcva_global[$global_attribute_name]['size'])) && ($wcva_global[$global_attribute_name]['size'] == "custom")) {echo "selected";} ?>><span class="wcvaformfield"><?php echo __('Custom','wcva'); ?></span></option>
		                             </select>
									 <select name="wcva_global[pa_<?php echo $attribute_name; ?>][displaytype]">
									 
	                                   <option value="square" <?php if ((isset($wcva_global[$global_attribute_name]['displaytype'])) && ($wcva_global[$global_attribute_name]['displaytype'] == "square")) {echo "selected";} ?>><span class="wcvaformfield"><?php echo __('Square','wcva'); ?></span></option>
									   
		                               <option value="round" <?php if ((isset($wcva_global[$global_attribute_name]['displaytype'])) && ($wcva_global[$global_attribute_name]['displaytype'] == "round")) {echo "selected";} ?>><span class="wcvaformfield"><?php echo __('Round','wcva'); ?></span></option>
		                              </select>
								</td>
								<td>
								   <select name="wcva_global[pa_<?php echo $attribute_name; ?>][show_name]" class="wcvadisplaytype">
	                                   <option value="no" <?php if ((isset($wcva_global[$global_attribute_name]['show_name'])) && ($wcva_global[$global_attribute_name]['show_name'] == "no")) {echo "selected";} ?>><span class="wcvaformfield"><?php echo __('No','wcva'); ?></span></option>
		                               <option value="yes" <?php if ((isset($wcva_global[$global_attribute_name]['show_name'])) && ($wcva_global[$global_attribute_name]['show_name'] == "yes")) {echo "selected";} ?>><span class="wcvaformfield"><?php echo __('Yes','wcva'); ?></span></option>
		                           </select>
								</td>
								<td>
									<a href="edit-tags.php?taxonomy=<?php echo wc_attribute_taxonomy_name($attribute_name); ?>&amp;post_type=product" class="button alignright configure-terms"><?php echo __('Set color/images','wcva'); ?></a>
								</td>
								
							</tr>
						<?php endforeach; ?>
	                    <?php endif;?>
					</tbody>
					
				</table>
			</td>
		</tr>
	
	<?php }	


WCVA_wcva_settings::init();
?>
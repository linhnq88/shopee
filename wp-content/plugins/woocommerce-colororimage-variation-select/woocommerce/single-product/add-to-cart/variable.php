<?php
/**
 * Variable product add to cart
 *
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.4.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product,$post;

$attribute_keys           =  array_keys( $attributes );
$_coloredvariables        =  get_post_meta( $post->ID, '_coloredvariables', true );
$woo_version              =  wcva_get_woo_version_number();
$wcva_global_activation   =  get_option("wcva_woocommerce_global_activation");
$wcva_global              =  get_option("wcva_global");
$product_id               =  $product->get_id();
$wcva_current_theme       =  wp_get_theme(); 	
$wcva_show_selected       = get_option('woocommerce_show_selected_attribute_name');

do_action( 'woocommerce_before_add_to_cart_form' ); ?>


<form class="variations_form cart" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product_id ); ?>" data-product_variations="<?php echo esc_attr( json_encode( $available_variations ) ) ?>">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php _e( 'This product is currently out of stock and unavailable.', 'woocommerce' ); ?></p>
	<?php else : ?>
		<table class="variations" cellspacing="0">
			<tbody>
				<?php 
				
				foreach ( $attributes as $attribute_name => $options ) { 
				    
				    if (isset( $_coloredvariables[$attribute_name]['display_type'])) {
                        $attribute_display_type  = $_coloredvariables[$attribute_name]['display_type'];
		            } elseif ((isset($wcva_global_activation)) && ($wcva_global_activation == "yes") && isset($wcva_global[$attribute_name]))  {
						$attribute_display_type  = $wcva_global[$attribute_name]['display_type'];
					} 
				    
					
					
					if (isset($attribute_display_type)) {
						$attribute_display_type = apply_filters('wcva_attribute_display_type', $attribute_display_type );
					}
						
                        
                    if ($woo_version <2.1) {
	                    $labeltext=$woocommerce->attribute_label( $attribute_name );  
	                } else {
	                    $labeltext=wc_attribute_label( $attribute_name );
	                }
						
							
				    $taxonomies = array($attribute_name);
	                              $args = array(
                         'hide_empty' => 0
                       );
                    
					$newvalues = get_terms( $taxonomies, $args);
					
					$fields   = new wcva_swatch_form_fields();
					
					$selected = isset( $_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] ) ? wc_clean(    $_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] ) : $product->get_variation_default_attribute( $attribute_name );
				    
					$terms_selected = wc_get_product_terms( $product_id, $attribute_name, array( 'fields' => 'all' ) );
					
                    $selected_text = $selected;			
									
                    foreach ( $terms_selected as $term_selected ) {
							  
                        if ( !in_array( $term_selected->slug, $options ) ) continue; { 
										  
							$selected_text = get_term_by('slug', $selected, $attribute_name);	
                            if (isset($selected_text->name)) { $selected_text = $selected_text->name; }				
					    
						}
					}
				?>
					<tr>
						<td class="label">
						  <span class="swatchtitlelabel"><?php if (isset($labeltext) && ($labeltext != '')) { echo $labeltext; } ?></span>
						<?php if ($wcva_show_selected == "yes") { ?>
						  : <span class="wcva_selected_attribute "><?php echo $selected_text; ?></span>
						<?php } ?>
						</td>
					</tr>
					<tr>
					   
						
						<td class="value">
				<?php
							if (isset($attribute_display_type) && ($attribute_display_type  == "colororimage")) {
								
								    wcva_dropdown_variation_attribute_options1( array( 'options' => $options, 'attribute' =>        $attribute_name, 'product' => $product, 'selected' => $selected ) );
								
								    $extra = array(
                                       "display_type" => $attribute_display_type
                                    );
									
									$fields->wcva_load_colored_select($product,$attribute_name,$options,$_coloredvariables,$newvalues,$selected,$extra);
								
								
							} elseif (isset($attribute_display_type) && ($attribute_display_type  == "variationimage")) {
								    
									wcva_dropdown_variation_attribute_options1( array( 'options' => $options, 'attribute' =>        $attribute_name, 'product' => $product, 'selected' => $selected ) );
								    
									$extra = array(
                                       "display_type" => $attribute_display_type
                                    );
									
								    $fields->wcva_load_colored_select($product,$attribute_name,$options,$_coloredvariables,$newvalues,$selected,$extra);
							
							} elseif (isset($attribute_display_type) && ($attribute_display_type  == "global") ) {
								
							    if (isset($wcva_global[$attribute_name])) {
									$wcva_global_display_type  = $wcva_global[$attribute_name]['display_type'];
								}
								
								
								if (isset($wcva_global_display_type) && ($wcva_global_display_type == "colororimage")) {
								
							        wcva_dropdown_variation_attribute_options1( array( 'options' => $options, 'attribute' =>        $attribute_name, 'product' => $product, 'selected' => $selected ) );
								  
								    $fields->wcva_load_colored_select2($product,$attribute_name,$options,$newvalues,$selected);
								
								} else {
								
								    wcva_dropdown_variation_attribute_options2( array( 'options' => $options, 'attribute' => $attribute_name, 'product' => $product, 'selected' => $selected ) );
								
								}
							} else {
								
								    wcva_dropdown_variation_attribute_options2( array( 'options' => $options, 'attribute' =>        $attribute_name, 'product' => $product, 'selected' => $selected ) );
								    echo '<br />';
							
							}
                                do_action( 'wcva_after_attribute_swatches',$attribute_name );
											
				?>
						</td>
					</tr>
					
		        <?php } ?>
				
			
	
			    <?php	//pricing fix for avada theme
				 if (($wcva_current_theme == "Avada Child") || ($wcva_current_theme == "Avada")) { ?>
				    <tr> 
			          
				      <td>
					    <div class="single_variation_price_reset">
						<div class="single_variation_wrap">
							<div class="single_variation"></div>
						</div>
						<?php echo end( $attribute_keys ) === $attribute_name ? '<a class="reset_variations" href="#">' . esc_html__( 'Clear selection', 'Avada' ) . '</a>' : ''; ?>
						</div>	
					   </td>
					   <td></td>
			        </tr>
				  <?php } else { ?>
				    <tr> 
  					
					 <td><?php   
						       echo end( $attribute_keys ) === $attribute_name ? '<a class="reset_variations" href="#">' . __( 'Clear selection', 'wcva' ) . '</a>' : ''; 
				         ?> 
					 </td>
					 <td></td>
					</tr>
				  <?php } ?>
					
					
			
			</tbody>
		</table>
        <div class="single_variation_wrap">
			<?php
				/**
				 * woocommerce_before_single_variation Hook
				 */
				do_action( 'woocommerce_before_single_variation' );

				/**
				 * woocommerce_single_variation hook. Used to output the cart button and placeholder for variation data.
				 * @since 2.4.0
				 * @hooked woocommerce_single_variation - 10 Empty div for variation data.
				 * @hooked woocommerce_single_variation_add_to_cart_button - 20 Qty and cart button.
				 */
				do_action( 'woocommerce_single_variation' );

				/**
				 * woocommerce_after_single_variation Hook
				 */
				do_action( 'woocommerce_after_single_variation' );
			?>
		</div>

		
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>
<script>
	var wcva_attribute_number = <?php echo count($attributes); ?>;
</script>
<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

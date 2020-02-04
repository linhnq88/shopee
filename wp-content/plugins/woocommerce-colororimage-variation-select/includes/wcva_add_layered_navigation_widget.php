<?php	
add_action( 'widgets_init', 'wcva_register_new_widget');	

function wcva_register_new_widget(){
     register_widget( 'wcva_swatches_widget' );
}

/**
 * Adds My_Widget widget.
 */
class wcva_swatches_widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'wcva_swatches_widget', // Base ID
			__('WooSwatches Filter', 'wcva'), // Name
			array('description' => __( 'WooSwatches color/image swatches filter widget', 'wcva' ),) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$this->wcva_widget_contet($args, $instance);	
	}
	
	/**
	 * Contents of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $attribute_name     Name of chosen attribute.
	 */
	
	public function wcva_widget_contet($args, $instance) {
		
		$_chosen_attributes = WC_Query::get_layered_nav_chosen_attributes();
		
		if ( ! is_post_type_archive( 'product' ) && ! is_tax( get_object_taxonomies( 'product' ) ) ) {
			return;
		}
		
		
		$taxonomy           = isset( $instance['filter_attribute'] ) ? wc_attribute_taxonomy_name( $instance['filter_attribute'] ) : $instance['filter_attribute'];
		$query_type         = isset( $instance['query_type'] ) ? $instance['query_type'] : $instance['query_type'];
		
		if ( ! taxonomy_exists( $taxonomy ) ) {
			
			return;
		}
		
		
		$get_terms_args = array( 'hide_empty' => '1' );

		$orderby = wc_attribute_orderby( $taxonomy );

		switch ( $orderby ) {
			case 'name' :
				$get_terms_args['orderby']    = 'name';
				$get_terms_args['menu_order'] = false;
			break;
			case 'id' :
				$get_terms_args['orderby']    = 'id';
				$get_terms_args['order']      = 'ASC';
				$get_terms_args['menu_order'] = false;
			break;
			case 'menu_order' :
				$get_terms_args['menu_order'] = 'ASC';
			break;
		}

		$terms = get_terms( $taxonomy, $get_terms_args );

		if ( 0 === sizeof( $terms ) ) {
			return;
		}
		
	

		switch ( $orderby ) {
			case 'name_num' :
				usort( $terms, '_wc_get_product_terms_name_num_usort_callback' );
			break;
			case 'parent' :
				usort( $terms, '_wc_get_product_terms_parent_usort_callback' );
			break;
		}

		ob_start();
		
		?> 
		 <br />
	     <div class="widget-area wcva-filter-widget"> 
		   <aside class="widget woocommerce widget_layered_nav wcva_layered_nav">
		    
            <h3 class="wcva_filter-widget-title"><?php echo $instance['wcva_widget_title']; ?>  <?php echo $instance['filter_attribute']; ?></h3>
		     <?php  $found = $this->wcva_layered_swatches( $terms, $taxonomy,$query_type); ?>
		   </aside>
		 </div> 
         <br />	
		
		<?php
		
		
		
		
		
		// Force found when option is selected - do not force found on taxonomy attributes
		if ( ! is_tax() && is_array( $_chosen_attributes ) && array_key_exists( $taxonomy, $_chosen_attributes ) ) {
			$found = true;
		}

		if ( ! $found ) {
			ob_end_clean();
		} else {
			echo ob_get_clean();
		}
	}
	
	
	/**
	 * display swatches div and label.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $terms     all terms of chosen attribute.
	 * @param array $terms     all terms of chosen attribute.
	 */
	public function wcva_layered_swatches($terms, $taxonomy ,$query_type) {
		// List display
		echo '<div class="wcva_filter_widget">';

		$term_counts        = $this->get_filtered_term_product_counts( wp_list_pluck( $terms, 'term_id' ), $taxonomy, $query_type );
		$_chosen_attributes = WC_Query::get_layered_nav_chosen_attributes();
		$found              = false;
        $global_activation = get_option('wcva_woocommerce_global_activation');
		$wcva_global       = get_option('wcva_global');
		$display_shape     = 'wcvasquare';
		
		if (isset($global_activation) && $global_activation == "yes") {
				
				    if ($wcva_global[$taxonomy]['displaytype'] == "round") {
				 	    $display_shape =  'wcvaround';
				    }
		}
		
		
		foreach ( $terms as $term ) {
			
			
			
			
			
			$current_values    = isset( $_chosen_attributes[ $taxonomy ]['terms'] ) ? $_chosen_attributes[ $taxonomy ]['terms'] : array();
			$option_is_set     = in_array( $term->slug, $current_values );
			$count             = isset( $term_counts[ $term->term_id ] ) ? $term_counts[ $term->term_id ] : 0;

			// skip the term for the current archive
			if ( $this->get_current_term_id() === $term->term_id ) {
				continue;
			}

			// Only show options with count > 0
			if ( 0 < $count ) {
				$found = true;
			} elseif ( 'and' === $query_type && 0 === $count && ! $option_is_set ) {
				continue;
			}

			$filter_name    = 'filter_' . sanitize_title( str_replace( 'pa_', '', $taxonomy ) );
			$current_filter = isset( $_GET[ $filter_name ] ) ? explode( ',', wc_clean( $_GET[ $filter_name ] ) ) : array();
			$current_filter = array_map( 'sanitize_title', $current_filter );

			if ( ! in_array( $term->slug, $current_filter ) ) {
				$current_filter[] = $term->slug;
			}

			$link = $this->get_page_base_url( $taxonomy );

			// Add current filters to URL.
			foreach ( $current_filter as $key => $value ) {
				// Exclude query arg for current term archive term
				if ( $value === $this->get_current_term_slug() ) {
					unset( $current_filter[ $key ] );
				}

				// Exclude self so filter can be unset on click.
				if ( $option_is_set && $value === $term->slug ) {
					unset( $current_filter[ $key ] );
				}
			}

			if ( ! empty( $current_filter ) ) {
				$link = add_query_arg( $filter_name, implode( ',', $current_filter ), $link );

				// Add Query type Arg to URL
				if ( $query_type === 'or' && ! ( 1 === sizeof( $current_filter ) && $option_is_set ) ) {
					$link = add_query_arg( 'query_type_' . sanitize_title( str_replace( 'pa_', '', $taxonomy ) ), 'or', $link );
				}
			}
			
			$this->wcva_load_each_swatch_html($count,$option_is_set,$link,$term,$display_shape);
           
		}

		echo '</div>';

		return $found;
	}
	
	public function wcva_load_each_swatch_html($count,$option_is_set,$link,$term,$display_shape) {
		$imagewidth        = get_option('woocommerce_shop_swatch_width',"32");  
        $imageheight       = get_option('woocommerce_shop_swatch_height',"32"); 
		
		
		$swatchtype       = get_woocommerce_term_meta( $term->term_id, 'display_type', true );
		$swatchcolor      = get_woocommerce_term_meta( $term->term_id, 'color', true );
		$attrtextblock    = get_woocommerce_term_meta( $term->term_id, 'textblock', true );
		$swatchimage      = absint( get_woocommerce_term_meta( $term->term_id, 'thumbnail_id', true ) );
		
		$swatchimageurl   =  apply_filters('wcva_swatch_image_url',wp_get_attachment_thumb_url($swatchimage),$swatchimage);
		
		if (isset($swatchtype)) {
				switch ($swatchtype) {
             	    case 'Color':
             		    ?>
                        <a title="<?php echo esc_html( $term->name ); ?>" <?php echo ( $count > 0 || $option_is_set ) ? 'href="' . esc_url( apply_filters( 'woocommerce_layered_nav_link', $link ) ) . '"' : ''; ?> class="wcvaswatchinput" rel="nofollow" style="width:<?php echo $imagewidth; ?>px; height:<?php echo $imageheight; ?>px;">
                        <div class="wcvashopswatchlabel <?php echo $display_shape; ?> <?php if ($option_is_set) { echo 'wcva-selected-filter'; } ?>" style="background-color:<?php if (isset($swatchcolor)) { echo $swatchcolor; } else { echo '#ffffff'; } ?>; width:<?php echo $imagewidth; ?>px; float:left; height:<?php echo $imageheight; ?>px;"></div>
						</a>
             		    <?php
             		break;

             	    case 'Image':
             		    ?>
                        <a title="<?php echo esc_html( $term->name ); ?>" <?php echo ( $count > 0 || $option_is_set ) ? 'href="' . esc_url( apply_filters( 'woocommerce_layered_nav_link', $link ) ) . '"' : ''; ?> class="wcvaswatchinput" rel="nofollow">
                        <div class="wcvashopswatchlabel <?php echo $display_shape; ?> <?php if ($option_is_set) { echo 'wcva-selected-filter'; } ?>"  style="background-image:url(<?php if (isset($swatchimageurl)) { echo $swatchimageurl; } ?>); background-size: <?php echo $imagewidth; ?>px <?php echo $imageheight; ?>px; float:left; width:<?php echo $imagewidth; ?>px; height:<?php echo $imageheight; ?>px;"></div>
						</a>
             		    <?php
             		break;
				
				    case 'textblock':
             		    ?>
                        <a title="<?php echo esc_html( $term->name ); ?>" <?php echo ( $count > 0 || $option_is_set ) ? 'href="' . esc_url( apply_filters( 'woocommerce_layered_nav_link', $link ) ) . '"' : ''; ?> class="wcvaswatchinput" rel="nofollow" style="width:<?php echo $imagewidth; ?>px; height:<?php echo $imageheight; ?>px;">
                        <div class="wcvashopswatchlabel <?php echo $display_shape; ?> <?php if ($option_is_set) { echo 'wcva-selected-filter'; } ?> wcva_filter_textblock" style="min-width:<?php echo $imagewidth; ?>px; "><?php  if (isset($attrtextblock)) { echo $attrtextblock; }   ?></div>
						</a>
             		    <?php
             		break;
             	
             
             } 
		}
	}
	
	/**
	 * Return the currently viewed taxonomy name.
	 * @return string
	 */
	protected function get_current_taxonomy() {
		return is_tax() ? get_queried_object()->taxonomy : '';
	}
	
	/**
	 * Return the currently viewed term ID.
	 * @return int
	 */
	protected function get_current_term_id() {
		return absint( is_tax() ? get_queried_object()->term_id : 0 );
	}
	
	/**
	 * Return the currently viewed term slug.
	 * @return int
	 */
	protected function get_current_term_slug() {
		return absint( is_tax() ? get_queried_object()->slug : 0 );
	}
	
	/**
	 * Get current page URL for layered nav items.
	 * @return string
	 */
	protected function get_page_base_url( $taxonomy ) {
		if ( defined( 'SHOP_IS_ON_FRONT' ) ) {
			$link = home_url();
		} elseif ( is_post_type_archive( 'product' ) || is_page( wc_get_page_id( 'shop' ) ) ) {
			$link = get_post_type_archive_link( 'product' );
		} elseif ( is_product_category() ) {
			$link = get_term_link( get_query_var( 'product_cat' ), 'product_cat' );
		} elseif ( is_product_tag() ) {
			$link = get_term_link( get_query_var( 'product_tag' ), 'product_tag' );
		} else {
			$queried_object = get_queried_object();
			$link = get_term_link( $queried_object->slug, $queried_object->taxonomy );
		}

		// Min/Max
		if ( isset( $_GET['min_price'] ) ) {
			$link = add_query_arg( 'min_price', wc_clean( $_GET['min_price'] ), $link );
		}

		if ( isset( $_GET['max_price'] ) ) {
			$link = add_query_arg( 'max_price', wc_clean( $_GET['max_price'] ), $link );
		}

		// Orderby
		if ( isset( $_GET['orderby'] ) ) {
			$link = add_query_arg( 'orderby', wc_clean( $_GET['orderby'] ), $link );
		}

		/**
		 * Search Arg.
		 * To support quote characters, first they are decoded from &quot; entities, then URL encoded.
		 */
		if ( get_search_query() ) {
			$link = add_query_arg( 's', rawurlencode( htmlspecialchars_decode( get_search_query() ) ), $link );
		}

		// Post Type Arg
		if ( isset( $_GET['post_type'] ) ) {
			$link = add_query_arg( 'post_type', wc_clean( $_GET['post_type'] ), $link );
		}

		// Min Rating Arg
		if ( isset( $_GET['min_rating'] ) ) {
			$link = add_query_arg( 'min_rating', wc_clean( $_GET['min_rating'] ), $link );
		}

		// All current filters
		if ( $_chosen_attributes = WC_Query::get_layered_nav_chosen_attributes() ) {
			foreach ( $_chosen_attributes as $name => $data ) {
				if ( $name === $taxonomy ) {
					continue;
				}
				$filter_name = sanitize_title( str_replace( 'pa_', '', $name ) );
				if ( ! empty( $data['terms'] ) ) {
					$link = add_query_arg( 'filter_' . $filter_name, implode( ',', $data['terms'] ), $link );
				}
				if ( 'or' == $data['query_type'] ) {
					$link = add_query_arg( 'query_type_' . $filter_name, 'or', $link );
				}
			}
		}

		return $link;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		
		
		 $attribute_taxonomies = wc_get_attribute_taxonomies();?>	
		
		<p>
			<label for="<?php echo $this->get_field_id( 'wcva_widget_title' ); ?>"><?php echo __( 'Title','wcva' ); ?></label>
			<input class="widefat " id="<?php echo $this->get_field_id( 'wcva_widget_title' ); ?>" name="<?php echo $this->get_field_name( 'wcva_widget_title' ); ?>" type="text" value="<?php echo __( 'Filter by','wcva' ); ?> ">
		</p>
		
		<p>
		    <label for="<?php echo $this->get_field_id( 'filter_attribute' ); ?>"><?php echo __( 'Attribute','wcva' ); ?></label>
			
		   
			<select class="widefat" id="<?php echo $this->get_field_id( 'filter_attribute' ); ?>" name="<?php echo $this->get_field_name( 'filter_attribute' ); ?>">
			<?php if ((!empty($attribute_taxonomies)) && (sizeof($attribute_taxonomies) >0)) : ?>
		    <?php foreach ($attribute_taxonomies as $value) : ?>
			<?php $value           = json_decode(json_encode($value), True); ?>
			<?php $attribute_name  = $value['attribute_name']; ?>
			  <option value="<?php echo $attribute_name; ?>" <?php if (isset($instance[ 'filter_attribute' ]) && $instance[ 'filter_attribute' ] == $attribute_name) { echo 'selected'; } ?>><?php echo $attribute_name; ?></option>
			<?php endforeach; ?>
	        <?php endif;?>
			</select>
		</p>
		
		<p>
		    <label for="<?php echo $this->get_field_id( 'query_type' ); ?>"><?php echo __( 'Query type','wcva' ); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id( 'query_type' ); ?>" name="<?php echo $this->get_field_name( 'query_type' ); ?>">
		      <option value="and" <?php if (isset($instance[ 'query_type' ]) && $instance[ 'query_type' ] == "and") { echo 'selected'; } ?>><?php echo __('AND','wcva'); ?></option>
			  <option value="or" <?php if (isset($instance[ 'query_type' ]) && $instance[ 'query_type' ] == "or") { echo 'selected'; } ?>><?php echo __('OR','wcva'); ?></option>
			</select>
		</p>
	   
		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		
		$instance = array();
		$instance['wcva_widget_title'] = ( ! empty( $new_instance['wcva_widget_title'] ) ) ? strip_tags( $new_instance['wcva_widget_title'] ) : '';
		$instance['filter_attribute'] = ( ! empty( $new_instance['filter_attribute'] ) ) ? strip_tags( $new_instance['filter_attribute'] ) : '';
		$instance['query_type'] = ( ! empty( $new_instance['query_type'] ) ) ? strip_tags( $new_instance['query_type'] ) : '';
		return $instance;
	}
	
	/**
	 * Count products within certain terms, taking the main WP query into consideration.
	 * @param  array $term_ids
	 * @param  string $taxonomy
	 * @param  string $query_type
	 * @return array
	 */
	protected function get_filtered_term_product_counts( $term_ids, $taxonomy) {
		global $wpdb;

		$tax_query  = WC_Query::get_main_tax_query();
		$meta_query = WC_Query::get_main_meta_query();

	

		$meta_query      = new WP_Meta_Query( $meta_query );
		$tax_query       = new WP_Tax_Query( $tax_query );
		$meta_query_sql  = $meta_query->get_sql( 'post', $wpdb->posts, 'ID' );
		$tax_query_sql   = $tax_query->get_sql( $wpdb->posts, 'ID' );

		// Generate query
		$query           = array();
		$query['select'] = "SELECT COUNT( DISTINCT {$wpdb->posts}.ID ) as term_count, terms.term_id as term_count_id";
		$query['from']   = "FROM {$wpdb->posts}";
		$query['join']   = "
			INNER JOIN {$wpdb->term_relationships} AS term_relationships ON {$wpdb->posts}.ID = term_relationships.object_id
			INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy USING( term_taxonomy_id )
			INNER JOIN {$wpdb->terms} AS terms USING( term_id )
			" . $tax_query_sql['join'] . $meta_query_sql['join'];

		$query['where']   = "
			WHERE {$wpdb->posts}.post_type IN ( 'product' )
			AND {$wpdb->posts}.post_status = 'publish'
			" . $tax_query_sql['where'] . $meta_query_sql['where'] . "
			AND terms.term_id IN (" . implode( ',', array_map( 'absint', $term_ids ) ) . ")
		";

		if ( $search = WC_Query::get_main_search_query_sql() ) {
			$query['where'] .= ' AND ' . $search;
		}

		$query['group_by'] = "GROUP BY terms.term_id";
		$query             = apply_filters( 'woocommerce_get_filtered_term_product_counts_query', $query );
		$query             = implode( ' ', $query );
		$results           = $wpdb->get_results( $query );

		return wp_list_pluck( $results, 'term_count', 'term_count_id' );
	}

} // class My_Widget
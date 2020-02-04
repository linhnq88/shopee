<?php
/**
 * class-ot-vertical-menu-settings.php
 *
 * @since: 1.1.0
 * @version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class OTFL_Vertical_Menu_Settings {
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;

	/**
	 * Start up
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page() {
		add_options_page(
			__( 'OT Flatsome Vertical Menu Settings', 'ot' ),
			__( 'OT FS Vertical Menu', 'ot' ),
			'manage_options',
			'ot-flatsome-vertical-menu',
			array( $this, 'create_admin_page' )
		);
	}

	public function create_admin_page() {
		$this->options = get_option( 'ot_vm' );

		$menu_event   = isset( $this->options['menu_event'] ) ? esc_attr( $this->options['menu_event'] ) : 'click';
		$show_overlay = isset( $this->options['show_overlay'] ) ? esc_attr( $this->options['show_overlay'] ) : '0';
		$sub_top      = isset( $this->options['sub_top'] ) ? esc_attr( $this->options['sub_top'] ) : '0';
		$show_home    = isset( $this->options['show_home'] ) ? esc_attr( $this->options['show_home'] ) : '0';

		?>
        <div class="wrap">
            <h1><?php _e( 'OT Flatsome Vertical Menu Settings', 'ot' ); ?></h1>

            <form method="post" action="options.php">
				<?php settings_fields( 'ot_vm' ); ?>
				<?php do_settings_sections( 'ot_vm' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Menu Title', 'ot' ); ?></th>
                        <td>
                            <input type="text" name="ot_vm[menu_title]" class="regular-text"
                                   value="<?php echo isset( $this->options['menu_title'] ) ? esc_attr( $this->options['menu_title'] ) : __( 'Danh mục sản phẩm', '' ); ?>"/>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Event show', 'ot' ); ?></th>
                        <td>
                            <select class="regular-text" name="ot_vm[menu_event]" id="menu_event">
                                <option value="click" <?php selected( $menu_event, 'click' ); ?>><?php _e( 'Click to show', 'ot' ); ?></option>
                                <option value="hover" <?php selected( $menu_event, 'hover' ); ?>><?php _e( 'Hover to show', 'ot' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Show Overlay Background', 'ot' ); ?></th>
                        <td>
                            <input type="checkbox" name="ot_vm[show_overlay]"
                                   value="1" <?php checked( $show_overlay, 1 ); ?>>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Auto Show Sub in Homepage', 'ot' ); ?></th>
                        <td>
                            <input type="checkbox" name="ot_vm[show_home]"
                                   value="1" <?php checked( $show_home, 1 ); ?>>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Submenu Always At The Top', 'ot' ); ?></th>
                        <td>
                            <input type="checkbox" name="ot_vm[sub_top]"
                                   value="1" <?php checked( $sub_top, 1 ); ?>>
                            <p><?php _e( 'Checked:', 'ot' ) ?> <a target="_blank"
                                                                  href="http://prntscr.com/nup7kl"><?php _e( 'View demo', 'ot' ) ?></a>
                            </p>
                            <p><?php _e( 'Uncheck:', 'ot' ) ?> <a target="_blank"
                                                                  href="http://prntscr.com/nup7zx"><?php _e( 'View demo', 'ot' ) ?></a>
                            </p>
                        </td>
                    </tr>

                </table>

				<?php submit_button(); ?>

            </form>
        </div>
		<?php
	}

	public function page_init() {
		register_setting(
			'ot_vm', // Option group
			'ot_vm', // Option name
			array( $this, 'sanitize' ) // Sanitize
		);
	}

	public function sanitize( $input ) {
		$new_input = array();

		if ( isset( $input['menu_title'] ) ) {
			$new_input['menu_title'] = sanitize_text_field( $input['menu_title'] );
		}
		if ( isset( $input['menu_event'] ) ) {
			$new_input['menu_event'] = sanitize_text_field( $input['menu_event'] );
		}
		if ( isset( $input['show_overlay'] ) ) {
			$new_input['show_overlay'] = sanitize_text_field( $input['show_overlay'] );
		}
		if ( isset( $input['sub_top'] ) ) {
			$new_input['sub_top'] = sanitize_text_field( $input['sub_top'] );
		}
		if ( isset( $input['show_home'] ) ) {
			$new_input['show_home'] = sanitize_text_field( $input['show_home'] );
		}

		return $new_input;
	}
}

if ( is_admin() ) {
	new OTFL_Vertical_Menu_Settings();
}

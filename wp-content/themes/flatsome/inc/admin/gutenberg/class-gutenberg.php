<?php
/**
 * Gutenberg compatibility
 *
 * @author     UX Themes
 * @category   Gutenberg
 * @package    Flatsome/Gutenberg
 * @since      3.7.0
 */

namespace Flatsome\Inc\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Gutenberg
 *
 * @package Flatsome\Inc\Admin
 */
class Gutenberg {

	/**
	 * Current version
	 *
	 * @var string
	 */
	private $version = '1.0.0';

	/**
	 * Holds assets directory.
	 *
	 * @var string
	 */
	private $assets;

	/**
	 * Gutenberg constructor.
	 */
	public function __construct() {
		$this->assets = get_template_directory_uri() . '/inc/admin/gutenberg/assets';
		$this->init();
	}

	/**
	 * Initialise
	 */
	private function init() {
		if ( function_exists( 'gutenberg_init' ) ) {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
			add_action( 'enqueue_block_editor_assets', [ $this, 'add_edit_button' ], 11 );
			// add_action( 'admin_print_scripts-edit.php', [ $this, 'add_new_button' ], 11 );
		}
	}

	/**
	 * Register and enqueue main styles.
	 */
	public function enqueue_styles() {
		wp_register_style( 'flatsome-gutenberg', $this->assets . '/css/style.css', [], $this->version );
		wp_enqueue_style( 'flatsome-gutenberg' );
	}

	/**
	 * Add 'Edit with UX Builder' inside gutenberg editor header.
	 */
	public function add_edit_button() {
		global $typenow;
		if ( ! gutenberg_can_edit_post_type( $typenow ) ) {
			return;
		}
		wp_enqueue_script( 'flatsome-gutenberg-edit-button', $this->assets . '/js/edit-button.js', array( 'wp-edit-post' ), $this->version, true );

		$page_id = get_the_ID();

		$params = [
			'edit_button' => [
				'enabled' => $this->is_edit_button_visible( $page_id ),
				'text'    => __( 'Edit with UX Builder', 'flatsome' ),
				'url'     => ux_builder_edit_url( $page_id ),
			],
		];

		wp_localize_script( 'flatsome-gutenberg-edit-button', 'flatsome_gutenberg', $params );
	}

	/**
	 * Determines when the edit button should be visible or not.
	 *
	 * @param int $page_id The ID of the current post.
	 *
	 * @return bool
	 */
	private function is_edit_button_visible( $page_id ) {
		// Do not show UX Builder link on Shop page.
		if ( function_exists( 'is_woocommerce' ) && $page_id == wc_get_page_id( 'shop' ) ) {
			return false;
		}

		// Do not show UX Builder link on Posts Page.
		$page_for_posts = get_option( 'page_for_posts' );
		if ( $page_id == $page_for_posts ) {
			return false;
		}

		return true;
	}

	/**
	 * Insert new UX builder add new page button in admin backend.
	 */
	public function add_new_button() {
		global $typenow;
		if ( ! gutenberg_can_edit_post_type( $typenow ) ) {
			return;
		}
		// phpcs:disable
		?>
		<script type="text/javascript">
          document.addEventListener('DOMContentLoaded', function () {
            var dropdown = document.querySelector('#split-page-title-action .dropdown')
            if (!dropdown) {
              return
            }
            var url = '<?php echo esc_url( 'http://google.com' ); ?>'
            dropdown.insertAdjacentHTML('afterbegin', '<a href="' + url + '">UX Builder</a>')
          })
		</script>
		<?php
		// phpcs:enable
	}

}

new Gutenberg();

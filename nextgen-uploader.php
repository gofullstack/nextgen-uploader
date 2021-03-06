<?php
/*
Plugin Name: NextGEN  Uploader
Plugin URI: https://github.com/gofullstack/nextgen-uploader
Description: NextGEN Uploader is an extension to NextGEN Gallery which allows frontend image uploads for your users.
Version: 2.0

Author: Fullstack
Text Domain: nextgen-uploader
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NGGallery_Public_uploader
 */
class NGGallery_Public_uploader {

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	public $basename = '';

	/**
	 * Plugin server path.
	 *
	 * @var string
	 */
	public $directory_path = '';

	/**
	 * Plugin browser url path.
	 *
	 * @var string
	 */
	public $directory_url = '';

	/**
	 * Lets build some galleries.
	 */
	public function __construct() {

		// Some useful properties.
        $this->basename         = plugin_basename( __FILE__ );
        $this->directory_path   = plugin_dir_path( __FILE__ );
        $this->directory_url    = plugins_url( dirname( $this->basename ) );

        // And a registration hook.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		// Lets let everyone be able to read it, regardless of dialect.
		load_plugin_textdomain( 'nextgen-uploader', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		// We need NextGen Gallery to work.
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );

		// And our helper functions.
		add_action( 'plugins_loaded', array( $this, 'includes' ) );

		// Here's how people will access the settings.
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'plugin_settings' ) );

		// Or this way. Handy!
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'filter_plugin_actions' ) );

		add_action( 'npu_plugin_options_page_after_form', array( $this, 'shortcodes' ) );
		add_action( 'npu_plugin_options_page_after_form', array( $this, 'footer_text' ), 11 );
	}

	/**
	 * Checks if NextGen Gallery is available.
	 *
	 * @since 1.9.0
	 *
	 * @return bool Whether the NGG base class exists.
	 */
	public static function meets_requirements() {

		if ( class_exists( 'C_NextGEN_Bootstrap' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if we meet requirements, and disable if we don't.
	 *
	 * @since  1.9
	 */
	public function maybe_disable_plugin() {

		if ( ! $this->meets_requirements() ) {
			// Display our error.
			echo '<div id="message" class="error">';
			echo '<p>';
			echo sprintf(
				esc_html__( '%s Nextgen Uploader %s requires NextGEN Gallery in order to work. Please deactivate Nextgen Uploader or activate %s NextGEN Gallery %s', 'nextgen-uploader' ),
				'<p><strong>',
				'</strong>',
				'<a href="' . admin_url( '/plugin-install.php?tab=plugin-information&plugin=nextgen-gallery&TB_iframe=true&width=600&height=550' ) . '" target="_blank" class="thickbox onclick">',
				'</a>.</strong></p>'
			);
			echo '</p>';
			echo '</div>';

			// Deactivate our plugin.
			deactivate_plugins( $this->basename );
		}

	}

	/**
	 * Load our resources if we meet requirements.
	 *
	 * @since unknown
	 */
	public function includes() {

		if ( $this->meets_requirements() ) {
			require_once( dirname (__FILE__) . '/inc/npu-upload.php');
		}

	}

	/**
	 * Set default option values if we don't have any.
	 */
	public function activate() {

		if ( $this->meets_requirements() ) {
			// If our settings don't already exist, load defaults into the database.
			if ( ! get_option( 'npu_default_gallery' ) ) {
				update_option( 'npu_default_gallery', '1' );
				update_option( 'npu_user_role_select', '99' );
				update_option( 'npu_exclude_select', 'Enabled' );
				update_option( 'npu_image_description_select', 'Enabled' );
				update_option( 'npu_description_text', '' );
				update_option( 'npu_notification_email', get_option( 'admin_email' ) );
				update_option( 'npu_upload_button', esc_html__( 'Upload', 'nextgen-uploader' ) );
				update_option( 'npu_no_file', esc_html__( 'No file selected.', 'nextgen-uploader' ) );
				update_option( 'npu_notlogged', esc_html__( 'You are not authorized to upload an image.', 'nextgen-uploader' ) );
				update_option( 'npu_upload_success', esc_html__( 'Your image has been successfully uploaded.', 'nextgen-uploader' ) );
				update_option( 'npu_upload_failed', esc_html__( 'Your upload failed. Please try again.', 'nextgen-uploader' ) );
			}
		}

	}

	/**
	 * Add our menu.
	 *
	 * @since unknown
	 */
	public function menu() {

		// NOTE: Until I figure out how to make it a submenu, it's going as a main menu item
		/*
		 add_submenu_page(
			'nextgen-gallery',
			__( 'Nextgen Uploader', 'nextgen-uploader' ),
			__( 'Public Uploader', 'nextgen-uploader' ),
			'manage_options',
			'nextgen-uploader',
			array( $this, 'options_page' )
		);*/
		add_menu_page(
			esc_html__( 'Nextgen Uploader', 'nextgen-uploader' ),
			esc_html__( 'Nextgen Uploader', 'nextgen-uploader' ),
			'manage_options',
			'nextgen-uploader',
			array( $this, 'options_page' )
		);

	}

	/**
	 * Render our options page.
	 *
	 * @since unknown
	 *
	 * @return mixed HTML
	 */
	public function options_page() { ?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Nextgen Uploader', 'nextgen-uploader' ); ?></h1>

			<?php if ( isset( $_GET['settings-updated'] ) ) { ?>
				<div class="updated"><p><?php esc_html_e( 'Settings saved.', 'nextgen-uploader' ); ?></p></div>
			<?php
			} ?>

			<?php do_action( 'npu_plugin_options_page_before_form' ); ?>

			<form action="options.php" method="post">

				<?php
					settings_fields( 'npu_settings' );
					do_settings_sections( 'nextgen-uploader' );
					submit_button();
				?>

			</form>

			<?php do_action( 'npu_plugin_options_page_after_form' ); ?>
		</div>

	<?php
	}

	/**
	 * Set up and register our settings
	 */
	public function plugin_settings() {

		// Register our settings section.
		add_settings_section( 'npu_settings', esc_html__( 'Settings', 'nextgen-uploader' ), array( $this, 'settings_description' ), 'nextgen-uploader' );

		// Register all our settings.
		register_setting( 'npu_settings', 'npu_default_gallery', array( $this, 'settings_sanitization' ) );
		register_setting( 'npu_settings', 'npu_user_role_select', array( $this, 'settings_sanitization' ) );
		register_setting( 'npu_settings', 'npu_image_description_select', array( $this, 'settings_sanitization' ) );
		register_setting( 'npu_settings', 'npu_exclude_select', array( $this, 'settings_sanitization' ) );
		register_setting( 'npu_settings', 'npu_notification_email', array( $this, 'settings_sanitization' ) );
		register_setting( 'npu_settings', 'npu_upload_button', array( $this, 'settings_sanitization' ) );
		register_setting( 'npu_settings', 'npu_no_file', array( $this, 'settings_sanitization' ) );
		register_setting( 'npu_settings', 'npu_description_text', array( $this, 'settings_sanitization' ) );
		register_setting( 'npu_settings', 'npu_notlogged', array( $this, 'settings_sanitization' ) );
		register_setting( 'npu_settings', 'npu_upload_success', array( $this, 'settings_sanitization' ) );
		register_setting( 'npu_settings', 'npu_upload_failed', array( $this, 'settings_sanitization' ) );

		// Setup the options for our gallery selector.
		$gallery_options = array();
		$mapper          = C_Gallery_Mapper::get_instance();
		$gallerylist     = $mapper->find_all();

		foreach ( $gallerylist as $gallery ) {
			$name = !empty( $gallery->title ) ? $gallery->title : $gallery->name;
			$gallery_options[ $gallery->gid ] = $gallery->gid . ' &ndash; ' . $name;
		}

		$role_options = apply_filters( 'npu_plugin_roles', array(
			'99' => esc_html__( 'Visitor', 'nextgen-uploader' ),
			'0'  => esc_html__( 'Subscriber', 'nextgen-uploader' ),
			'1'  => esc_html__( 'Contributor', 'nextgen-uploader' ),
			'2'  => esc_html__( 'Author', 'nextgen-uploader' ),
			'7'  => esc_html__( 'Editor', 'nextgen-uploader' ),
			'10' => esc_html__( 'Admin', 'nextgen-uploader' )
		) );

		// Add our settings fields.
		add_settings_field(
			'npu_default_gallery',
			esc_html__( 'Default Gallery:', 'nextgen-uploader' ),
			array( $this, 'settings_select' ),
			'nextgen-uploader',
			'npu_settings',
			array(
				'ID' => 'npu_default_gallery',
				'description' => sprintf( esc_html__( 'The default gallery ID when using %s with no ID specified.', 'nextgen-uploader' ),
				'<code>[ngg_uploader]</code>' ),
				'options' => $gallery_options
			)
		);
		add_settings_field(
			'npu_user_role_select',
			esc_html__( 'Minimum User Role:', 'nextgen-uploader' ),
			array( $this, 'settings_select' ),
			'nextgen-uploader',
			'npu_settings',
			array(
				'ID' => 'npu_user_role_select',
				'description' => esc_html__( 'The minimum user role required for image uploading.', 'nextgen-uploader' ),
				'options' => $role_options
			)
		);
		add_settings_field(
			'npu_exclude_select',
			esc_html__( 'Uploads Require Approval:', 'nextgen-uploader' ),
			array( $this, 'settings_checkbox' ),
			'nextgen-uploader',
			'npu_settings',
			array(
				'ID' => 'npu_exclude_select',
				'description' => '',
				'value' => esc_attr__( 'Enabled', 'nextgen-uploader' ),
				'label' => esc_html__( 'Exclude images from appearing in galleries until they have been approved.', 'nextgen-uploader' )
			)
		);
		add_settings_field(
			'npu_image_description_select',
			esc_html__( 'Show Description Field:', 'nextgen-uploader' ),
			array( $this, 'settings_checkbox' ),
			'nextgen-uploader',
			'npu_settings',
			array(
				'ID' => 'npu_image_description_select',
				'description' => '',
				'value' => esc_attr__( 'Enabled', 'nextgen-uploader' ),
				'label' => esc_html__( 'Enable the Image Description text field.', 'nextgen-uploader' )
			)
		);
		add_settings_field(
			'npu_description_text',
			esc_html__( 'Image Description Label:', 'nextgen-uploader' ),
			array( $this, 'settings_text' ),
			'nextgen-uploader',
			'npu_settings',
			array(
				'ID' => 'npu_description_text',
				'description' => esc_html__( 'Default label shown for the image description textbox.', 'nextgen-uploader' )
			)
		);
		add_settings_field(
			'npu_notification_email',
			esc_html__( 'Notification Email:', 'nextgen-uploader' ),
			array( $this, 'settings_text' ),
			'nextgen-uploader',
			'npu_settings',
			array(
				'ID' => 'npu_notification_email',
				'description' => esc_html__( 'The email address to be notified when a image has been submitted.', 'nextgen-uploader' )
			)
		);
		add_settings_field(
			'npu_upload_button',
			esc_html__( 'Upload Button Text:', 'nextgen-uploader' ),
			array( $this, 'settings_text' ),
			'nextgen-uploader',
			'npu_settings',
			array(
				'ID' => 'npu_upload_button',
				'description' => esc_html__( 'Custom text for upload button.', 'nextgen-uploader' )
			)
		);
		add_settings_field(
			'npu_no_file',
			esc_html__( 'No File Selected Warning:', 'nextgen-uploader' ),
			array( $this, 'settings_text' ),
			'nextgen-uploader',
			'npu_settings',
			array(
				'ID' => 'npu_no_file',
				'description' => esc_html__( 'Warning displayed when no file has been selected for upload.', 'nextgen-uploader' )
			)
		);
		add_settings_field(
			'npu_notlogged',
			esc_html__( 'Unauthorized Warning:', 'nextgen-uploader' ),
			array( $this, 'settings_text' ),
			'nextgen-uploader',
			'npu_settings',
			array(
				'ID' => 'npu_notlogged',
				'description' => esc_html__( 'Warning displayed when a user does not have permission to upload.', 'nextgen-uploader' )
			)
		);
		add_settings_field(
			'npu_upload_success',
			esc_html__( 'Upload Success Message:', 'nextgen-uploader' ),
			array( $this, 'settings_text' ),
			'nextgen-uploader',
			'npu_settings',
			array(
				'ID' => 'npu_upload_success',
				'description' => esc_html__( 'Message displayed when an image has been successfully uploaded.', 'nextgen-uploader' )
			)
		);
		add_settings_field(
			'npu_upload_failed',
			esc_html__( 'Upload Failed Message:', 'nextgen-uploader' ),
			array( $this, 'settings_text' ),
			'nextgen-uploader',
			'npu_settings',
			array(
				'ID' => 'npu_upload_failed',
				'description' => esc_html__( 'Message displayed when an image failed to upload.', 'nextgen-uploader' )
			)
		);
	}

	/**
	 * Description setting.
	 *
	 * @since unknown
	 */
	public function settings_description() {
		echo '<p>' . esc_html__( 'Edit the settings below to control the default behaviors of this plugin. Shortcode example(s) available at the bottom of the page.', 'nextgen-uploader' ) . '</p>';
	}

	/**
	 * Echo a <select> input.
	 *
	 * @param array $args array of arguments to use.
	 * @return mixed        html select input with populated options
	 */
	public function settings_select( $args ) {

		$output = '<select name="' . $args['ID'] . '">';
		foreach ( $args['options'] as $value => $label ) {
			$output .= '<option ' . selected( $value, get_option($args['ID']), false ) . ' value="' . $value . '">' . $label . '</option>';
		}
		$output .= '</select>';

		if ( isset( $args['description'] ) ) {
			$output .= '<p><span class="description">' . $args['description'] . '</span></p>';
		}

		echo $output;
	}

	/**
	 * Echo a checkbox input.
	 *
	 * @param array $args Array of arguments to use.
	 * @return mixed        html checkbox input
	 */
	public function settings_checkbox( $args ) {

		$output = '';
		$output .= '<label for="' . $args['ID'] . '"><input type="checkbox" id="' . $args['ID'] . '" name="' . $args['ID'] . '" value="' . $args['value'] . '" ' . checked( get_option($args['ID']), $args['value'], false ) . ' /> ' . $args['label'] . '</label>';
		if ( isset( $args['description'] ) ) {
			$output .= '<p><span class="description">' . $args['description'] . '</span></p>';
		}

		echo $output;
	}

	/**
	 * Echo a text input
	 *
	 * @param array $args Array of arguments to use.
	 * @return mixed HTML text input.
	 */
	public function settings_text( $args ) {

		$output = '';
		$output .= '<input type="text" class="regular-text" name="' . $args['ID'] . '" value="' . get_option($args['ID']) . '" />';
		if ( isset( $args['description'] ) ) {
			$output .= '<p><span class="description">' . $args['description'] . '</span></p>';
		}
		echo $output;
	}

	/**
	 * Sanitize our settings.
	 *
	 * @param string $input Value to sanitize before saving.
	 * @return string Sanitized value
	 */
	public function settings_sanitization( $input ) {
		return esc_html( $input );
	}

	/**
	 * Add our settings link to the plugins listing for our plugin.
	 *
	 * @param array $links Array of links already available.
	 * @return array Array of new links to use
	 */
	public function filter_plugin_actions( $links ) {
		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=nextgen-uploader' ) . '">' . esc_html__( 'Settings', 'nextgen-uploader' ) . '</a>'
			),
			$links
		);
	}

	/**
	 * Shortcode stuff.
	 *
	 * @since unknown
	 */
	public function shortcodes() { ?>
		<h2><?php esc_html_e( 'Shortcode Examples', 'nextgen-uploader' ) ?></h2>
		<p><?php printf( esc_html__( 'To insert the public uploader into any content area, use %s or %s, where %s is the ID of the corresponding gallery.', 'nextgen-uploader' ), '<code>[ngg_uploader]</code>', '<code>[ngg_uploader id="1"]</code>', '<strong>1</strong>' ); ?></p>

		<?php do_action( 'npu_shortcodes' ); ?>
	<?php
	}

	/**
	 * Custom footer content.
	 *
	 * @since unknown
	 */
	public function footer_text() { ?>
		<p>
			<strong><?php esc_html_e('Current Version', 'nextgen-uploader') ?>:</strong> <?php $plugin_data = get_plugin_data( __FILE__, false ); echo $plugin_data['Version']; ?> |
			<a href="http://webdevstudios.com">WebDevStudios.com</a> |
			<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=3084056"><?php _e('Donate', 'nextgen-uploader' ) ?></a> |
			<a href="http://wordpress.org/plugins/nextgen-uploader/"><?php _e('Plugin Homepage', 'nextgen-uploader' ) ?></a> |
			<a href="http://wordpress.org/support/plugin/nextgen-uploader/"><?php _e('Support Forum', 'nextgen-uploader' ) ?></a>
		</p>
	<?php
	}

}
// Have a nice day!
$nggpu = new NGGallery_Public_uploader;

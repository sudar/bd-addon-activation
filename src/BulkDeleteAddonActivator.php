<?php
/**
 * Bulk Delete Addon Activation handler.
 * Based on the code from https://github.com/easydigitaldownloads/EDD-Extension-Boilerplate
 *
 * Namespace is not used since this class might be used in PHP 5.2
 *
 * @license  GPL-2.0-or-later
 * @package  BulkDelete\Addon
 * @version  1.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( class_exists( 'BulkDeleteAddonActivator' ) ) {
	return;
}

/**
 * Bulk Delete Addon Activation Handler Class.
 *
 * @since 1.0.0
 */
class BulkDeleteAddonActivator {

	/**
	 * Add-on name.
	 *
	 * @var string
	 */
	protected $addon_name;

	/**
	 * Add-on path.
	 *
	 * @var string
	 */
	protected $addon_directory;

	/**
	 * Add-on file.
	 *
	 * @var string
	 */
	protected $addon_file;

	/**
	 * Is Bulk Delete plugin installed?
	 *
	 * It may or may not be activated.
	 *
	 * @var bool
	 */
	protected $has_bd;

	/**
	 * Bulk Delete plugin base path.
	 *
	 * @var string
	 */
	protected $bd_base;

	/**
	 * Is Bulk Delete plugin active.
	 *
	 * @var bool
	 */
	protected $is_bd_active;

	/**
	 * Bulk Delete plugin version.
	 *
	 * @var string
	 */
	protected $bd_version;

	/**
	 * Minimum version of Bulk Delete that is needed.
	 *
	 * @var string
	 */
	protected $required_bd_version;

	/**
	 * Minimum version of PHP that is needed.
	 *
	 * @var string
	 */
	protected $required_php_version;

	/**
	 * Setup the activation class.
	 *
	 * @param string $addon_file_path      Add-on main file.
	 * @param string $required_bd_version  The minimum version of Bulk Delete that is required. Default 6.0.0.
	 * @param string $required_php_version The minimum version of PHP that is required. Default is 5.6.0.
	 *
	 * @since 1.0.0 minimum required version of PHP is increased to 5.6.0.
	 */
	public function __construct( $addon_file_path, $required_bd_version = '6.0.0', $required_php_version = '5.6.0' ) {
		$this->required_bd_version  = $required_bd_version;
		$this->required_php_version = $required_php_version;

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$plugins = get_plugins();

		// Set addon directory.
		$directories           = array_filter( explode( '/', plugin_dir_path( $addon_file_path ) ) );
		$this->addon_directory = end( $directories );

		// Set addon file.
		$this->addon_file = basename( $addon_file_path );

		// Set plugin name.
		if ( isset( $plugins[ $this->addon_directory . '/' . $this->addon_file ]['Name'] ) ) {
			$this->addon_name = str_replace( 'Bulk Delete - ', '', $plugins[ $this->addon_directory . '/' . $this->addon_file ]['Name'] );
		} else {
			$this->addon_name = __( 'This plugin', 'bulk-delete' );
		}

		// Is Bulk Delete installed?
		foreach ( $plugins as $plugin_path => $plugin ) {
			if ( 'Bulk Delete' === $plugin['Name'] ) {
				$this->has_bd       = true;
				$this->bd_base      = $plugin_path;
				$this->bd_version   = $plugin['Version'];
				$this->is_bd_active = is_plugin_active( $plugin_path );
				break;
			}
		}
	}

	/**
	 * Check if the required version of Bulk Delete plugin is installed.
	 * If not, show a notice.
	 *
	 * @return bool True, if requirement are met, False otherwise.
	 */
	public function requirement_met() {
		if ( version_compare( PHP_VERSION, $this->required_php_version, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'update_php_notice' ) );

			return false;
		}

		$bulk_delete_class_name = '\BulkWP\BulkDelete\Core\BulkDelete';
		if ( ! class_exists( $bulk_delete_class_name ) ) {
			add_action( 'admin_notices', array( $this, 'missing_plugin_notice' ) );

			return false;
		} else {
			if ( ! version_compare( $this->bd_version, $this->required_bd_version, '>=' ) ) {
				add_action( 'admin_notices', array( $this, 'upgrade_plugin_notice' ) );

				return false;
			}
		}

		return true;
	}

	/**
	 * Display update PHP notice.
	 */
	public function update_php_notice() {
		printf(
			'<div class="error"><p>%s %s</p></div>',
			esc_html( $this->addon_name ),
			sprintf(
				/* translators: 1 Required PHP Version, 2 Available PHP version  */
				esc_html__( 'requires at least PHP %1$s or above! You are currently using PHP %2$s, which is very old. Please contact your web host and upgrade PHP.', 'bulk-delete' ),
				esc_html( $this->required_php_version ),
				PHP_VERSION //@codingStandardsIgnoreLine No need to escape constant.
			)
		);
	}

	/**
	 * Display notice if Bulk Delete isn't installed or activated.
	 */
	public function missing_plugin_notice() {
		if ( $this->has_bd ) {
			$url  = esc_url( wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $this->bd_base ), 'activate-plugin_' . $this->bd_base ) );
			$link = '<a href="' . $url . '">' . __( 'activate it', 'bulk-delete' ) . '</a>';
		} else {
			$url  = esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=bulk-delete' ), 'install-plugin_bulk-delete' ) );
			$link = '<a href="' . $url . '">' . __( 'install it', 'bulk-delete' ) . '</a>';
		}

		printf(
			'<div class="error"><p>%s %s</p></div>',
			esc_html( $this->addon_name ),
			sprintf( __( 'requires Bulk Delete! Please %s to continue!', 'bulk-delete' ), $link ) //@codingStandardsIgnoreLine Link is constructed just above.
		);
	}

	/**
	 * Display notice if Bulk Delete needs to be updated.
	 */
	public function upgrade_plugin_notice() {
		$url  = esc_url( wp_nonce_url( admin_url( 'plugins.php?action=upgrade-plugin&plugin=' . $this->bd_base ), 'upgrade-plugin_' . $this->bd_base ) );
		$link = '<a href="' . $url . '">' . __( 'update it', 'bulk-delete' ) . '</a>';

		printf(
			'<div class="error"><p>%s %s</p></div>',
			esc_html( $this->addon_name ),
			sprintf( __( 'requires Bulk Delete version %s or above! Please %s to continue!', 'bulk-delete' ), $this->required_bd_version, $link ) //@codingStandardsIgnoreLine Link is constructed just above.
		);
	}
}

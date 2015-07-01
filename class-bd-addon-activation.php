<?php
/**
 * Bulk Delete Addon Activation handler.
 * Based on the code from https://github.com/easydigitaldownloads/EDD-Extension-Boilerplate
 *
 * @package  BulkDelete\Addon
 * @version  1.0.0
 */


defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Bulk Delete Addon Activation Handler Class.
 *
 * @since   1.0.0
 */
class BD_Addon_Activation {

	private $plugin_name;
	private	$plugin_path;
	private $plugin_file;
	private	$has_bd;
	private	$bd_base;

	/**
	 * Setup the activation class.
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function __construct( $plugin_path, $plugin_file ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$plugins = get_plugins();

		// Set plugin directory
		$plugin_path = array_filter( explode( '/', $plugin_path ) );
		$this->plugin_path = end( $plugin_path );

		// Set plugin file
		$this->plugin_file = $plugin_file;

		// Set plugin name
		if ( isset( $plugins[ $this->plugin_path . '/' . $this->plugin_file ]['Name'] ) ) {
			$this->plugin_name = str_replace( 'Bulk Delete - ', '', $plugins[ $this->plugin_path . '/' . $this->plugin_file ]['Name'] );
		} else {
			$this->plugin_name = __( 'This plugin', 'bulk-delete' );
		}

		// Is Bulk Delete installed?
		foreach ( $plugins as $plugin_path => $plugin ) {
			if ( 'Bulk Delete' == $plugin['Name'] ) {
				$this->has_bd = true;
				$this->bd_base = $plugin_path;
				break;
			}
		}
	}

	/**
	 * Check if the required version of Bulk Delete plugin is installed.
	 * If not, then show a notice.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param string $version The minimum version of Bulk Delete that is required. Default 5.5
	 * @return bool True, if requirement met, False otherwise.
	 */
	public function requirement_met( $version = '5.5' ) {
		if ( ! class_exists( 'Bulk_Delete' ) ) {
			add_action( 'admin_notices', array( $this, 'missing_plugin_notice' ) );
			return false;
		} else if ( ! version_compare( Bulk_Delete::VERSION, $version, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'upgrade_plugin_notice' ) );
			return false;
		}

		return true;
	}

	/**
	 * Display notice if Bulk Delete isn't installed or activated.
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      string The notice to display
	 */
	public function missing_plugin_notice() {
		if ( $this->has_bd ) {
			$url  = esc_url( wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $this->bd_base ), 'activate-plugin_' . $this->bd_base ) );
			$link = '<a href="' . $url . '">' . __( 'activate it', 'bulk-delete' ) . '</a>';
		} else {
			$url  = esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=bulk-delete' ), 'install-plugin_bulk-delete' ) );
			$link = '<a href="' . $url . '">' . __( 'install it', 'bulk-delete' ) . '</a>';
		}

		printf( '<div class="error"><p>%s %s</p></div>', $this->plugin_name, sprintf( __( 'requires Bulk Delete! Please %s to continue!', 'bulk-delete' ), $link ) );
	}

	/**
	 * Display notice if Bulk Delete needs to be updated.
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      string The notice to display
	 */
	public function upgrade_plugin_notice() {
		$url  = esc_url( wp_nonce_url( admin_url( 'plugins.php?action=upgrade-plugin&plugin=' . $this->bd_base ), 'upgrade-plugin_' . $this->bd_base ) );
		$link = '<a href="' . $url . '">' . __( 'update it', 'bulk-delete' ) . '</a>';

		printf( '<div class="error"><p>"%s" %s</p></div>', $this->plugin_name, sprintf( __( ' addon requires Bulk Delete plugin version %s or above! Please %s to continue!', 'bulk-delete' ), $link ) );
	}
}

<?php
/**
 * Plugin Name:       Borderless Currency Calculator
 * Description:       Adds a configurable currency converter and calculator widget via the [bcc_currency_converter] shortcode, with live exchange rates and no API key required.
 * Version:            1.0.0
 * Requires at least:  5.8
 * Requires PHP:       7.4
 * Author:             mihirdev21
 * License:            GPL v2 or later
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:        borderless-currency-calculator
 *
 * @package Borderless_Currency_Calculator
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName -- Main plugin
 * bootstrap file is intentionally named after the plugin slug, per WordPress
 * plugin repository conventions.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core plugin constants.
 */
define( 'BCCALC_VERSION', '1.0.0' );
define( 'BCCALC_PLUGIN_FILE', __FILE__ );
define( 'BCCALC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BCCALC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BCCALC_OPTION_NAME', 'bccalc_settings' );
define( 'BCCALC_CURRENCIES_TRANSIENT', 'bccalc_currency_list' );

/**
 * Load plugin dependencies.
 */
require_once BCCALC_PLUGIN_DIR . 'includes/class-bccalc-currencies.php';
require_once BCCALC_PLUGIN_DIR . 'includes/class-bccalc-api.php';
require_once BCCALC_PLUGIN_DIR . 'includes/class-bccalc-admin.php';
require_once BCCALC_PLUGIN_DIR . 'includes/class-bccalc-shortcode.php';

/**
 * Main plugin bootstrap class.
 *
 * Wires up the admin settings screen, the [bcc_currency_converter] shortcode
 * and the AJAX conversion endpoint, and owns activation defaults.
 */
final class Currency_Converter_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Currency_Converter_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Currency_Converter_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor. Hooks everything up.
	 */
	private function __construct() {
		register_activation_hook( BCCALC_PLUGIN_FILE, array( __CLASS__, 'activate' ) );



		if ( is_admin() ) {
			new BCCALC_Admin();
		}

		new BCCALC_Shortcode();
	}



	/**
	 * Runs on plugin activation. Seeds default settings if none exist yet.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( false === get_option( BCCALC_OPTION_NAME ) ) {
			update_option( BCCALC_OPTION_NAME, BCCALC_Admin::get_default_settings() );
		}
	}
}

Currency_Converter_Plugin::instance();

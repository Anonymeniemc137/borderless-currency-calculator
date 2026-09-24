<?php
/**
 * Uninstall handler.
 *
 * Removes the plugin's option and any cached exchange-rate/currency-list
 * transients. Runs only when the plugin is deleted from the Plugins screen.
 *
 * @package Currency_Converter
 */

// Exit if accessed directly, or not via the WordPress uninstall process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'bccalc_settings' );
delete_transient( 'bccalc_currency_list' );

global $wpdb;

// Clean up per-currency rate transients (bccalc_rates_{code}), added dynamically
// so they can't be targeted individually with delete_transient().
$bccalc_transient_like = $wpdb->esc_like( '_transient_bccalc_rates_' ) . '%';
$bccalc_timeout_like   = $wpdb->esc_like( '_transient_timeout_bccalc_rates_' ) . '%';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $bccalc_transient_like ) );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $bccalc_timeout_like ) );

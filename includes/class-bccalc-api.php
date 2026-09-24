<?php
/**
 * Exchange rate API handler.
 *
 * @package Borderless_Currency_Calculator
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches and caches exchange rates from the Fawaz Ahmed Currency API,
 * with an automatic fallback to the Cloudflare-hosted mirror.
 *
 * @see https://github.com/fawazahmed0/exchange-api
 */
class BCCALC_API {

	/**
	 * Primary API endpoint template. %s is replaced with the lowercase base currency code.
	 *
	 * @var string
	 */
	const PRIMARY_ENDPOINT = 'https://latest.currency-api.pages.dev/v1/currencies/%s.min.json';

	/**
	 * Fallback API endpoint template.
	 *
	 * @var string
	 */
	const FALLBACK_ENDPOINT = 'https://latest.currency-api.pages.dev/v1/currencies/%s.min.json';

	/**
	 * How long to cache a base currency's rate table, in seconds.
	 *
	 * @var int
	 */
	const CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * Get the full rate table for a base currency (rates of 1 unit of $base
	 * expressed in every other currency), using the transient cache where possible.
	 *
	 * @param string $base Lowercase 3-letter base currency code.
	 * @return array|WP_Error Rate table keyed by lowercase currency code, or WP_Error on failure.
	 */
	public static function get_rates( $base ) {
		$base = strtolower( sanitize_text_field( $base ) );

		if ( '' === $base || ! preg_match( '/^[a-z0-9]{2,5}$/', $base ) ) {
			return new WP_Error( 'bccalc_invalid_currency', __( 'Invalid currency code.', 'borderless-currency-calculator' ) );
		}

		$transient_key = 'bccalc_rates_' . $base;
		$cached        = get_transient( $transient_key );

		if ( is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}

		$rates = self::fetch_from_endpoint( sprintf( self::PRIMARY_ENDPOINT, $base ), $base );

		if ( is_wp_error( $rates ) ) {
			$rates = self::fetch_from_endpoint( sprintf( self::FALLBACK_ENDPOINT, $base ), $base );
		}

		if ( is_wp_error( $rates ) ) {
			return $rates;
		}

		set_transient( $transient_key, $rates, self::CACHE_TTL );

		return $rates;
	}

	/**
	 * Get the conversion rate between two currencies.
	 *
	 * @param string $from Lowercase base currency code.
	 * @param string $to   Lowercase target currency code.
	 * @return float|WP_Error
	 */
	public static function get_rate( $from, $to ) {
		$from = strtolower( sanitize_text_field( $from ) );
		$to   = strtolower( sanitize_text_field( $to ) );

		if ( $from === $to ) {
			return 1.0;
		}

		$rates = self::get_rates( $from );

		if ( is_wp_error( $rates ) ) {
			return $rates;
		}

		if ( ! isset( $rates[ $to ] ) ) {
			return new WP_Error( 'bccalc_rate_not_found', __( 'Exchange rate unavailable for the selected currencies.', 'borderless-currency-calculator' ) );
		}

		return (float) $rates[ $to ];
	}

	/**
	 * Perform the actual HTTP request and normalize the response body.
	 *
	 * @param string $url  Full request URL.
	 * @param string $base Lowercase base currency code (used as the top-level JSON key).
	 * @return array|WP_Error
	 */
	private static function fetch_from_endpoint( $url, $base ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			return new WP_Error( 'BCCALC_API_error', __( 'The currency service returned an unexpected response.', 'borderless-currency-calculator' ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || ! isset( $body[ $base ] ) || ! is_array( $body[ $base ] ) ) {
			return new WP_Error( 'BCCALC_API_bad_payload', __( 'The currency service returned an invalid response.', 'borderless-currency-calculator' ) );
		}

		$rates = array();

		foreach ( $body[ $base ] as $code => $value ) {
			$rates[ sanitize_text_field( $code ) ] = (float) $value;
		}

		return $rates;
	}
}

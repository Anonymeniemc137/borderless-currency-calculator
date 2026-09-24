<?php
/**
 * Currency list helper.
 *
 * @package Borderless_Currency_Calculator
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides the bundled list of currencies available to the admin multi-select.
 * Keeping the list in the plugin avoids an unnecessary external request.
 */
class BCCALC_Currencies {

	/**
	 * Get the map of currency code => label (e.g. 'usd' => 'USD - US Dollar').
	 *
	 * Uses the bundled static list so the settings page does not make an
	 * unnecessary external request.
	 *
	 * @return array<string,string>
	 */
	public static function get_all() {
		$cached = get_transient( BCCALC_CURRENCIES_TRANSIENT );

		if ( is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}

		return self::get_static_list();
	}

	/**
	 * Derive the ISO 3166-1 alpha-2 country code for a currency code.
	 *
	 * For most currencies the first two letters of the code match an ISO
	 * 3166-1 alpha-2 country code (e.g. "USD" -> "US"). A short override
	 * list handles currencies that don't map to a single country (e.g. EUR,
	 * regional CFA francs), and codes with no sensible flag (e.g. XDR)
	 * return an empty string.
	 *
	 * @param string $code Lowercase or uppercase 3-letter currency code.
	 * @return string Two-letter country code, or an empty string.
	 */
	public static function get_country_code( $code ) {
		$code = strtoupper( sanitize_text_field( $code ) );

		$overrides = array(
			'EUR' => 'EU',
			'XAF' => 'CM',
			'XOF' => 'SN',
			'XCD' => 'AG',
			'XPF' => 'PF',
			'ANG' => 'CW',
			'XDR' => '',
		);

		if ( isset( $overrides[ $code ] ) ) {
			$country = $overrides[ $code ];
		} else {
			$country = substr( $code, 0, 2 );
		}

		return preg_match( '/^[A-Z]{2}$/', $country ) ? $country : '';
	}

	/**
	 * Get a flag emoji for a currency code.
	 *
	 * Builds the flag from Unicode regional indicator symbols with no image
	 * assets or external dependency. Uses numeric character references so the
	 * plugin does not require the mbstring extension.
	 *
	 * @param string $code Lowercase or uppercase 3-letter currency code.
	 * @return string Flag emoji, or an empty string if one can't be built.
	 */
	public static function get_flag_emoji( $code ) {
		$country = self::get_country_code( $code );

		if ( '' === $country ) {
			return '';
		}

		$entities = '';
		foreach ( str_split( $country ) as $letter ) {
			$entities .= '&#x' . dechex( 0x1F1E6 + ( ord( $letter ) - 65 ) ) . ';';
		}

		return html_entity_decode( $entities, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * Combine a currency's flag emoji with its stored label for display.
	 *
	 * @param string $code  Lowercase currency code.
	 * @param string $label Label as stored by get_all(), e.g. "USD - US Dollar".
	 * @return string
	 */
	public static function get_display_label( $code, $label ) {
		$flag = self::get_flag_emoji( $code );
		return '' !== $flag ? $flag . ' ' . $label : $label;
	}

	/**
	 * Bundled static fallback list, sourced from the site's original
	 * converter markup (top common currencies first).
	 *
	 * @return array<string,string>
	 */
	public static function get_static_list() {
		return array(
			'gbp' => 'GBP - British Pound',
			'eur' => 'EUR - Euro',
			'usd' => 'USD - US Dollar',
			'aud' => 'AUD - Australian Dollar',
			'cad' => 'CAD - Canadian Dollar',
			'chf' => 'CHF - Swiss Franc',
			'inr' => 'INR - Indian Rupee',
			'jpy' => 'JPY - Japanese Yen',
			'nzd' => 'NZD - New Zealand Dollar',
			'thb' => 'THB - Thai Baht',
			'afn' => 'AFN - Afghani',
			'all' => 'ALL - Lek',
			'amd' => 'AMD - Armenian Dram',
			'ars' => 'ARS - Argentine Peso',
			'awg' => 'AWG - Aruban Florin',
			'azn' => 'AZN - Azerbaijani Manat',
			'bam' => 'BAM - Convertible Mark',
			'bbd' => 'BBD - Barbados Dollar',
			'bdt' => 'BDT - Bangladeshi Taka',
			'bgn' => 'BGN - Bulgarian Leva',
			'bhd' => 'BHD - Bahraini Dinars',
			'bif' => 'BIF - Burundian Franc',
			'bmd' => 'BMD - Bermudian Dollar',
			'bnd' => 'BND - Brunei Dollar',
			'bob' => 'BOB - Boliviano',
			'brl' => 'BRL - Brazilian Real',
			'bsd' => 'BSD - Bahamian Dollar',
			'btn' => 'BTN - Ngultrum',
			'bwp' => 'BWP - Botswana Pula',
			'byr' => 'BYR - Belarussian Ruble',
			'bzd' => 'BZD - Belize Dollar',
			'cdf' => 'CDF - Congolese Franc',
			'clp' => 'CLP - Chilean Peso',
			'cny' => 'CNY - Chinese Yuan Renminbi',
			'cop' => 'COP - Colombian Peso',
			'crc' => 'CRC - Costa Rican Colon',
			'cup' => 'CUP - Cuban Peso',
			'cve' => 'CVE - Cape Verde Escudo',
			'czk' => 'CZK - Czech Koruna',
			'djf' => 'DJF - Djibouti Franc',
			'dkk' => 'DKK - Danish Kroner',
			'dop' => 'DOP - Dominican Peso',
			'dzd' => 'DZD - Algerian Dinar',
			'egp' => 'EGP - Egyptian Pound',
			'ern' => 'ERN - Nakfa',
			'etb' => 'ETB - Ethiopian Birr',
			'fjd' => 'FJD - Fiji Dollar',
			'fkp' => 'FKP - Falkland Islands Pound',
			'gel' => 'GEL - Georgian Lari',
			'ghs' => 'GHS - Ghanaian Cedi',
			'gip' => 'GIP - Gibraltar Pound',
			'gmd' => 'GMD - Dalasi',
			'gnf' => 'GNF - Guinean Franc',
			'gtq' => 'GTQ - Quetzal',
			'gyd' => 'GYD - Guyana Dollar',
			'hkd' => 'HKD - Hong Kong Dollar',
			'hnl' => 'HNL - Lempira',
			'hrk' => 'HRK - Croatian Kuna',
			'htg' => 'HTG - Gourde',
			'huf' => 'HUF - Hungarian Forint',
			'idr' => 'IDR - Indonesian Rupiah',
			'ils' => 'ILS - Israeli Shekel',
			'iqd' => 'IQD - Iraqi Dinar',
			'irr' => 'IRR - Iranian Rial',
			'isk' => 'ISK - Icelandic Krona',
			'jmd' => 'JMD - Jamaican Dollar',
			'jod' => 'JOD - Jordanian Dinar',
			'kes' => 'KES - Kenyan Shillings',
			'kgs' => 'KGS - Som',
			'khr' => 'KHR - Riel',
			'krw' => 'KRW - South Korean Won',
			'kwd' => 'KWD - Kuwaiti Dinars',
			'kyd' => 'KYD - Cayman Islands Dollar',
			'kzt' => 'KZT - Tenge',
			'lbp' => 'LBP - Lebanese Pound',
			'lkr' => 'LKR - Sri Lankan Rupee',
			'lrd' => 'LRD - Liberian Dollar',
			'lsl' => 'LSL - Loti',
			'lyd' => 'LYD - Libyan Dinar',
			'mad' => 'MAD - Moroccan Dirham',
			'mdl' => 'MDL - Moldovan Leu',
			'mga' => 'MGA - Malagasy Ariary',
			'mmk' => 'MMK - Kyat',
			'mnt' => 'MNT - Tugrik',
			'mop' => 'MOP - Pataca',
			'mro' => 'MRO - Ouguiya',
			'mur' => 'MUR - Mauritian Rupee',
			'mvr' => 'MVR - Rufiyaa',
			'mwk' => 'MWK - Malawian Kwacha',
			'mxn' => 'MXN - Mexican Peso',
			'myr' => 'MYR - Malaysian Ringits',
			'mzn' => 'MZN - Mozambique Metical',
			'nad' => 'NAD - Namibia Dollar',
			'ngn' => 'NGN - Nigerian Naira',
			'nio' => 'NIO - Cordoba Oro',
			'nok' => 'NOK - Norwegian Kroner',
			'npr' => 'NPR - Nepalese Rupee',
			'omr' => 'OMR - Omani Rial',
			'pab' => 'PAB - Balboa',
			'pen' => 'PEN - Peruvian Nuevo Sol',
			'pgk' => 'PGK - Kina',
			'php' => 'PHP - Philippine Peso',
			'pkr' => 'PKR - Pakistani Rupee',
			'pln' => 'PLN - Polish Zlotych',
			'pyg' => 'PYG - Guarani',
			'qar' => 'QAR - Qatari Rial',
			'ron' => 'RON - Romanian New Leu',
			'rsd' => 'RSD - Serbian Dinar',
			'rwf' => 'RWF - Rwanda Franc',
			'sar' => 'SAR - Saudi Riyal',
			'sbd' => 'SBD - Solomon Islands Dollar',
			'sdg' => 'SDG - Sudanese Pound',
			'sek' => 'SEK - Swedish Kroner',
			'shp' => 'SHP - Saint Helena Pound',
			'sll' => 'SLL - Leone',
			'sos' => 'SOS - Somali Shilling',
			'srd' => 'SRD - Surinam Dollar',
			'std' => 'STD - Dobra',
			'szl' => 'SZL - Lilangeni',
			'tjs' => 'TJS - Somoni',
			'tmt' => 'TMT - Turkmenistan New Manat',
			'tnd' => 'TND - Tunisian Dinar',
			'top' => 'TOP - Paanga',
			'try' => 'TRY - Turkish New Lira',
			'ttd' => 'TTD - Trinidad And Tobago Dollar',
			'twd' => 'TWD - New Taiwan Dollar',
			'tzs' => 'TZS - Tanzanian Shilling',
			'uah' => 'UAH - Hryvnia',
			'ugx' => 'UGX - Ugandan Shilling',
			'uyu' => 'UYU - Peso Uruguayo',
			'uzs' => 'UZS - Uzbekistan Sum',
			'vef' => 'VEF - Bolivar',
			'vnd' => 'VND - Dong',
			'vuv' => 'VUV - Vatu',
			'xaf' => 'XAF - Central African Franc',
			'xcd' => 'XCD - East Caribbean Dollars',
			'xpf' => 'XPF - Cfp Franc',
			'yer' => 'YER - Yemeni Rial',
			'zar' => 'ZAR - South African Rand',
			'zmw' => 'ZMW - Zambian Kwacha',
			'zwl' => 'ZWL - Zimbabwe Dollar',
		);
	}
}

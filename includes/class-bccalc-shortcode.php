<?php
/**
 * Frontend shortcode and AJAX conversion handler.
 *
 * @package Borderless_Currency_Calculator
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the [bcc_currency_converter] shortcode and handles the AJAX
 * conversion request that powers the "Convert" button.
 */
class BCCALC_Shortcode {

	/**
	 * Nonce action used for the AJAX conversion request.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'bccalc_convert_nonce';

	/**
	 * Counter used to give each shortcode instance unique element IDs.
	 *
	 * @var int
	 */
	private static $instance_count = 0;

	/**
	 * Constructor. Hooks the shortcode and AJAX actions.
	 */
	public function __construct() {
		add_shortcode( 'bcc_currency_converter', array( $this, 'render_shortcode' ) );

		add_action( 'wp_ajax_bccalc_convert', array( $this, 'ajax_convert' ) );
		add_action( 'wp_ajax_nopriv_bccalc_convert', array( $this, 'ajax_convert' ) );
	}

	/**
	 * Enqueue frontend assets and output inline theme variables.
	 *
	 * @param array $settings Plugin settings.
	 * @return void
	 */
	private function enqueue_assets( $settings ) {
		wp_enqueue_style( 'cconv-frontend', BCCALC_PLUGIN_URL . 'assets/css/frontend.css', array(), BCCALC_VERSION );

		wp_enqueue_script(
			'cconv-frontend',
			BCCALC_PLUGIN_URL . 'assets/js/frontend.js',
			array(),
			BCCALC_VERSION,
			true
		);

		wp_localize_script(
			'cconv-frontend',
			'cconvConverter',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
				'i18n'    => array(
					'converting' => __( 'Converting…', 'borderless-currency-calculator' ),
					'error'      => __( 'Unable to fetch exchange rates right now. Please try again shortly.', 'borderless-currency-calculator' ),
					'invalid'    => __( 'Please enter a valid amount.', 'borderless-currency-calculator' ),
				),
				'flags'   => $this->build_flag_map( $settings['selected_currencies'] ),
			)
		);

		$custom_css = $this->build_inline_css( $settings );
		if ( '' !== $custom_css ) {
			wp_add_inline_style( 'cconv-frontend', $custom_css );
		}
	}

	/**
	 * Build the CSS custom properties for the converter, based on settings.
	 *
	 * Properties are set on the .cconv-currency-converter element (not :root)
	 * so they actually win over the element-level defaults in frontend.css.
	 *
	 * @param array $settings Plugin settings.
	 * @return string
	 */
	private function build_inline_css( $settings ) {
		$css  = '.cconv-currency-converter{';
		$css .= '--bccalc-border-radius:' . absint( $settings['border_radius'] ) . 'px;';
		$css .= '--bccalc-font-size:' . absint( $settings['font_size'] ) . 'px;';
		$css .= '--bccalc-width:' . sanitize_text_field( $settings['converter_width'] ) . ';';

		if ( empty( $settings['theme_enabled'] ) ) {
			$css .= '--bccalc-primary:' . sanitize_hex_color( $settings['primary_color'] ) . ';';
			$css .= '--bccalc-secondary:' . sanitize_hex_color( $settings['secondary_color'] ) . ';';
			$css .= '--bccalc-background:' . sanitize_hex_color( $settings['background_color'] ) . ';';
			$css .= '--bccalc-text:' . sanitize_hex_color( $settings['text_color'] ) . ';';
			$css .= '--bccalc-button:' . sanitize_hex_color( $settings['button_color'] ) . ';';
			$css .= '--bccalc-button-text:' . sanitize_hex_color( $settings['button_text_color'] ) . ';';
		}

		$css .= '}';

		return $css;
	}

	/**
	 * Build the code => flag emoji map for the localized frontend data.
	 *
	 * @param array $codes Selected currency codes.
	 * @return array<string,string>
	 */
	private function build_flag_map( $codes ) {
		$map = array();

		foreach ( $codes as $code ) {
			$map[ $code ] = BCCALC_Currencies::get_flag_emoji( $code );
		}

		return $map;
	}

	/**
	 * Render a "From"/"To" field as a custom dropdown.
	 *
	 * Options in a native <select> can't contain images consistently, so the
	 * actual UI is a button + listbox where each row shows a flag indicator.
	 * A visually hidden <select> is kept in the DOM so the frontend JS and the
	 * form have the current value as before.
	 *
	 * @param string $uid      Unique instance prefix for element IDs.
	 * @param array  $all      Full currency list from BCCALC_Currencies::get_all().
	 * @param array  $selected Selected currency codes, in display order.
	 * @param string $current  Currently selected currency code.
	 * @param string $name     Field name ("from" or "to").
	 * @param string $label    Field label text.
	 * @return void
	 */
	private function render_currency_field( $uid, $all, $selected, $current, $name, $label ) {
		?>
		<div class="cconv-form-group cconv-<?php echo esc_attr( $name ); ?>-group">
			<label id="<?php echo esc_attr( $uid ); ?>-<?php echo esc_attr( $name ); ?>-label" for="<?php echo esc_attr( $uid ); ?>-<?php echo esc_attr( $name ); ?>-toggle"><?php echo esc_html( $label ); ?></label>
			<div class="cconv-dropdown">
				<button
					type="button"
					id="<?php echo esc_attr( $uid ); ?>-<?php echo esc_attr( $name ); ?>-toggle"
					class="cconv-dropdown-toggle"
					aria-haspopup="listbox"
					aria-expanded="false"
					aria-controls="<?php echo esc_attr( $uid ); ?>-<?php echo esc_attr( $name ); ?>-menu"
					aria-labelledby="<?php echo esc_attr( $uid ); ?>-<?php echo esc_attr( $name ); ?>-label"
				>
					<span class="cconv-dropdown-flag" aria-hidden="true"><?php echo esc_html( BCCALC_Currencies::get_flag_emoji( $current ) ); ?></span>
					<span class="cconv-dropdown-code"><?php echo esc_html( strtoupper( $current ) ); ?></span>
					<span class="cconv-dropdown-caret" aria-hidden="true"></span>
				</button>

				<select
					id="<?php echo esc_attr( $uid ); ?>-<?php echo esc_attr( $name ); ?>"
					name="<?php echo esc_attr( $name ); ?>Currency"
					class="cconv-select cconv-select-<?php echo esc_attr( $name ); ?> cconv-visually-hidden"
					tabindex="-1"
					aria-hidden="true"
				>
					<?php foreach ( $selected as $code ) : ?>
						<?php
						if ( ! isset( $all[ $code ] ) ) {
							continue; }
						?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $current, $code ); ?>>
							<?php echo esc_html( $this->get_option_label( $code, $all[ $code ] ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<ul
					id="<?php echo esc_attr( $uid ); ?>-<?php echo esc_attr( $name ); ?>-menu"
					class="cconv-dropdown-menu"
					role="listbox"
					aria-hidden="true"
					aria-labelledby="<?php echo esc_attr( $uid ); ?>-<?php echo esc_attr( $name ); ?>-label"
				>
					<?php foreach ( $selected as $code ) : ?>
						<?php
						if ( ! isset( $all[ $code ] ) ) {
							continue; }
						$is_selected = $current === $code;
						?>
						<li
							role="option"
							class="cconv-dropdown-option<?php echo $is_selected ? ' cconv-selected' : ''; ?>"
							data-code="<?php echo esc_attr( $code ); ?>"
							aria-selected="<?php echo $is_selected ? 'true' : 'false'; ?>"
							tabindex="-1"
						>
							<span class="cconv-dropdown-flag" aria-hidden="true"><?php echo esc_html( BCCALC_Currencies::get_flag_emoji( $code ) ); ?></span>
							<span class="cconv-dropdown-name"><?php echo esc_html( $this->get_option_label( $code, $all[ $code ] ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the [bcc_currency_converter] shortcode.
	 *
	 * @return string
	 */
	public function render_shortcode() {
		$settings = BCCALC_Admin::get_settings();
		$all      = BCCALC_Currencies::get_all();
		$selected = $settings['selected_currencies'];

		if ( empty( $selected ) ) {
			return '<p>' . esc_html__( 'The currency converter has not been configured yet.', 'borderless-currency-calculator' ) . '</p>';
		}

		$this->enqueue_assets( $settings );

		$theme_class = ! empty( $settings['theme_enabled'] ) ? 'cconv-theme-custom' : 'cconv-theme-configurable';

		++self::$instance_count;
		$uid = 'cconv-' . self::$instance_count;

		ob_start();
		?>
		<div class="cconv-currency-converter <?php echo esc_attr( $theme_class ); ?>">
			<form class="cconv-form" novalidate>
				<?php wp_nonce_field( self::NONCE_ACTION, 'bccalc_nonce_field_' . esc_attr( $uid ) ); ?>
				<div class="cconv-form-row">
					<div class="cconv-form-group cconv-amount-group">
						<label for="<?php echo esc_attr( $uid ); ?>-amount"><?php esc_html_e( 'Amount', 'borderless-currency-calculator' ); ?></label>
						<input
							type="text"
							inputmode="decimal"
							id="<?php echo esc_attr( $uid ); ?>-amount"
							name="amount"
							class="cconv-input"
							value="100"
							autocomplete="off"
						/>
					</div>

					<?php $this->render_currency_field( $uid, $all, $selected, $settings['default_from'], 'from', __( 'From', 'borderless-currency-calculator' ) ); ?>

					<div class="cconv-form-group cconv-switch-group">
						<button type="button" class="cconv-switch cconv-switch-currencies" aria-label="<?php esc_attr_e( 'Swap currencies', 'borderless-currency-calculator' ); ?>">
							<span aria-hidden="true">⇄</span>
						</button>
					</div>

					<?php $this->render_currency_field( $uid, $all, $selected, $settings['default_to'], 'to', __( 'To', 'borderless-currency-calculator' ) ); ?>

					<div class="cconv-form-group cconv-convert-group">
						<label class="cconv-visually-hidden" for="<?php echo esc_attr( $uid ); ?>-convert"><?php esc_html_e( 'Convert', 'borderless-currency-calculator' ); ?></label>
						<button type="submit" id="<?php echo esc_attr( $uid ); ?>-convert" class="cconv-convert-btn">
							<span><?php esc_html_e( 'Convert', 'borderless-currency-calculator' ); ?></span>
						</button>
					</div>
				</div>
			</form>

			<div class="cconv-result-block" aria-live="polite">
				<h2 class="cconv-result"></h2>
				<h4 class="cconv-exchange-rate"></h4>
				<p class="cconv-timestamp"></p>
				<p class="cconv-error" role="alert"></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Strip the "CODE - " prefix already stored in the label so it isn't duplicated.
	 *
	 * @param string $label Full label, e.g. "USD - US Dollar".
	 * @return string
	 */
	private function get_currency_name( $label ) {
		$parts = explode( ' - ', $label, 2 );
		return isset( $parts[1] ) ? $parts[1] : $label;
	}

	/**
	 * Build a select option's display label: "CODE - Name".
	 *
	 * Flags are rendered as images beside the select rather than as emoji
	 * inside the options, since Windows browsers don't draw flag emoji.
	 *
	 * @param string $code  Lowercase currency code.
	 * @param string $label Full label as stored by BCCALC_Currencies::get_all(), e.g. "USD - US Dollar".
	 * @return string
	 */
	private function get_option_label( $code, $label ) {
		return strtoupper( $code ) . ' - ' . $this->get_currency_name( $label );
	}

	/**
	 * AJAX handler for the "Convert" button.
	 *
	 * @return void
	 */
	public function ajax_convert() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$settings = BCCALC_Admin::get_settings();
		$selected = $settings['selected_currencies'];

		$amount = isset( $_POST['amount'] ) ? sanitize_text_field( wp_unslash( $_POST['amount'] ) ) : '';
		$from   = isset( $_POST['from'] ) ? strtolower( sanitize_text_field( wp_unslash( $_POST['from'] ) ) ) : '';
		$to     = isset( $_POST['to'] ) ? strtolower( sanitize_text_field( wp_unslash( $_POST['to'] ) ) ) : '';

				$normalized_amount = str_replace( ',', '', $amount );
		$amount            = is_numeric( $normalized_amount ) ? (float) $normalized_amount : false;

		if ( false === $amount || $amount < 0 ) {
			wp_send_json_error(
				array( 'message' => __( 'Please enter a valid amount.', 'borderless-currency-calculator' ) ),
				400
			);
		}

		if ( ! in_array( $from, $selected, true ) || ! in_array( $to, $selected, true ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid currency selection.', 'borderless-currency-calculator' ) ),
				400
			);
		}

		$rate = BCCALC_API::get_rate( $from, $to );

		if ( is_wp_error( $rate ) ) {
			wp_send_json_error(
				array( 'message' => $rate->get_error_message() ),
				502
			);
		}

		$converted = $amount * $rate;

		wp_send_json_success(
			array(
				'amount'    => number_format( $amount, 2, '.', ',' ),
				'from'      => strtoupper( $from ),
				'to'        => strtoupper( $to ),
				'rate'      => number_format( $rate, 4, '.', ',' ),
				'converted' => number_format( $converted, 2, '.', ',' ),
				'timestamp' => current_time( 'd M Y, H:i A' ),
			)
		);
	}
}

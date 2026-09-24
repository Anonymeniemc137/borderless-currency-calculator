<?php
/**
 * Admin settings screen.
 *
 * @package Borderless_Currency_Calculator
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the plugin's single settings page under Settings,
 * using the WordPress Settings API.
 */
class BCCALC_Admin {

	/**
	 * Option group name used by register_setting().
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'bccalc_settings_group';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'currency-converter-settings';

	/**
	 * Constructor. Hooks admin menu, settings registration and assets.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Default settings, used on activation and as a sanitize fallback.
	 *
	 * @return array
	 */
	public static function get_default_settings() {
		return array(
			'selected_currencies' => array( 'gbp', 'eur', 'usd', 'aud', 'cad', 'chf', 'inr', 'jpy', 'nzd', 'thb' ),
			'default_from'        => 'gbp',
			'default_to'          => 'usd',
			'theme_enabled'       => 1,
			'primary_color'       => '#2271b1',
			'secondary_color'     => '#135e96',
			'background_color'    => '#ffffff',
			'text_color'          => '#1d2327',
			'button_color'        => '#2271b1',
			'button_text_color'   => '#ffffff',
			'border_radius'       => 6,
			'font_size'           => 15,
			'converter_width'     => '100%',
		);
	}

	/**
	 * Get the saved plugin settings, merged with defaults.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$saved = get_option( BCCALC_OPTION_NAME, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, self::get_default_settings() );
	}

	/**
	 * Register the plugin settings page under Settings.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Borderless Currency Calculator Settings', 'borderless-currency-calculator' ),
			__( 'Borderless Currency Calculator', 'borderless-currency-calculator' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register the single settings option with the Settings API.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			BCCALC_OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => self::get_default_settings(),
			)
		);

		add_settings_section(
			'bccalc_section_currencies',
			__( 'Currencies', 'borderless-currency-calculator' ),
			array( $this, 'render_currencies_section_intro' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'bccalc_field_currencies',
			__( 'Available Currencies', 'borderless-currency-calculator' ),
			array( $this, 'render_currencies_field' ),
			self::PAGE_SLUG,
			'bccalc_section_currencies'
		);

		add_settings_field(
			'bccalc_field_defaults',
			__( 'Default Currencies', 'borderless-currency-calculator' ),
			array( $this, 'render_defaults_field' ),
			self::PAGE_SLUG,
			'bccalc_section_currencies'
		);

		add_settings_section(
			'bccalc_section_appearance',
			__( 'Appearance', 'borderless-currency-calculator' ),
			array( $this, 'render_appearance_section_intro' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'bccalc_field_theme_enabled',
			__( 'Use Custom Theme', 'borderless-currency-calculator' ),
			array( $this, 'render_theme_enabled_field' ),
			self::PAGE_SLUG,
			'bccalc_section_appearance'
		);

		add_settings_field(
			'bccalc_field_colors',
			__( 'Colors', 'borderless-currency-calculator' ),
			array( $this, 'render_colors_field' ),
			self::PAGE_SLUG,
			'bccalc_section_appearance'
		);

		add_settings_field(
			'bccalc_field_layout',
			__( 'Layout', 'borderless-currency-calculator' ),
			array( $this, 'render_layout_field' ),
			self::PAGE_SLUG,
			'bccalc_section_appearance'
		);
	}

	/**
	 * Sanitize the entire settings array on save.
	 *
	 * @param array $input Raw submitted settings.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$defaults = self::get_default_settings();
		$output   = array();
		$valid    = array_keys( BCCALC_Currencies::get_all() );

		// Selected currencies: keep submission order (drag order), valid codes only, no duplicates.
		$selected = array();
		if ( isset( $input['selected_currencies'] ) && is_array( $input['selected_currencies'] ) ) {
			foreach ( $input['selected_currencies'] as $code ) {
				$code = strtolower( sanitize_text_field( $code ) );
				if ( in_array( $code, $valid, true ) && ! in_array( $code, $selected, true ) ) {
					$selected[] = $code;
				}
			}
		}
		if ( empty( $selected ) ) {
			$selected = $defaults['selected_currencies'];
		}
		$output['selected_currencies'] = $selected;

		// Defaults must be part of the selected list.
		$default_from = isset( $input['default_from'] ) ? strtolower( sanitize_text_field( $input['default_from'] ) ) : '';
		$default_to   = isset( $input['default_to'] ) ? strtolower( sanitize_text_field( $input['default_to'] ) ) : '';

		$output['default_from'] = in_array( $default_from, $selected, true ) ? $default_from : $selected[0];
		$output['default_to']   = in_array( $default_to, $selected, true ) ? $default_to : ( isset( $selected[1] ) ? $selected[1] : $selected[0] );

		// Theme toggle.
		$output['theme_enabled'] = ! empty( $input['theme_enabled'] ) ? 1 : 0;

		// Colors.
		foreach ( array( 'primary_color', 'secondary_color', 'background_color', 'text_color', 'button_color', 'button_text_color' ) as $color_key ) {
			$value                = isset( $input[ $color_key ] ) ? sanitize_hex_color( $input[ $color_key ] ) : '';
			$output[ $color_key ] = $value ? $value : $defaults[ $color_key ];
		}

		// Border radius (0-50px).
		$radius                  = isset( $input['border_radius'] ) ? absint( $input['border_radius'] ) : $defaults['border_radius'];
		$output['border_radius'] = min( 50, max( 0, $radius ) );

		// Font size (10-30px).
		$font_size           = isset( $input['font_size'] ) ? absint( $input['font_size'] ) : $defaults['font_size'];
		$output['font_size'] = min( 30, max( 10, $font_size ) );

		// Converter width, restricted to an allow-list of values from the select field.
		$allowed_widths            = array( '100%', '320px', '400px', '480px', '560px', '640px', '720px', '800px' );
		$width                     = isset( $input['converter_width'] ) ? sanitize_text_field( $input['converter_width'] ) : $defaults['converter_width'];
		$output['converter_width'] = in_array( $width, $allowed_widths, true ) ? $width : $defaults['converter_width'];

		// Refresh the cached rate transients so appearance/currency changes take effect immediately.
		foreach ( $selected as $code ) {
			delete_transient( 'bccalc_rates_' . $code );
		}

		return $output;
	}

	/**
	 * Enqueue admin CSS/JS, only on our settings page.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_script(
			'bccalc-admin',
			BCCALC_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker', 'jquery-ui-sortable' ),
			BCCALC_VERSION,
			true
		);

		wp_enqueue_style(
			'bccalc-admin',
			BCCALC_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			BCCALC_VERSION
		);
	}

	/**
	 * Render the settings page shell.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap bccalc-settings-wrap">
			<h1><?php esc_html_e( 'Borderless Currency Calculator Settings', 'borderless-currency-calculator' ); ?></h1>
			<p>
				<?php
				printf(
					/* translators: %s: shortcode tag. */
					esc_html__( 'Use the shortcode %s to display the converter on any page or post.', 'borderless-currency-calculator' ),
					'<code>[bcc_currency_converter]</code>'
				);
				?>
			</p>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button( __( 'Save Settings', 'borderless-currency-calculator' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Intro text for the currencies section.
	 *
	 * @return void
	 */
	public function render_currencies_section_intro() {
		echo '<p>' . esc_html__( 'Choose which currencies appear in the frontend converter, and drag to reorder them.', 'borderless-currency-calculator' ) . '</p>';
	}

	/**
	 * Intro text for the appearance section.
	 *
	 * @return void
	 */
	public function render_appearance_section_intro() {
		echo '<p>' . esc_html__( 'Control how the converter looks on the frontend.', 'borderless-currency-calculator' ) . '</p>';
	}

	/**
	 * Render the sortable currency checkbox list.
	 *
	 * @return void
	 */
	public function render_currencies_field() {
		$settings = self::get_settings();
		$all      = BCCALC_Currencies::get_all();
		$selected = $settings['selected_currencies'];

		// Order the list: selected currencies first (in saved order), then the rest.
		$ordered_codes = $selected;
		foreach ( array_keys( $all ) as $code ) {
			if ( ! in_array( $code, $ordered_codes, true ) ) {
				$ordered_codes[] = $code;
			}
		}
		?>
		<p class="bccalc-bulk-actions">
			<button type="button" class="button" id="bccalc-select-all"><?php esc_html_e( 'Select All', 'borderless-currency-calculator' ); ?></button>
			<button type="button" class="button" id="bccalc-select-none"><?php esc_html_e( 'Select None', 'borderless-currency-calculator' ); ?></button>
		</p>
		<ul class="bccalc-currency-sortable">
			<?php foreach ( $ordered_codes as $code ) : ?>
				<?php
				if ( ! isset( $all[ $code ] ) ) {
					continue; }
				?>
				<li class="bccalc-currency-item">
					<span class="bccalc-drag-handle dashicons dashicons-menu" aria-hidden="true"></span>
					<label>
						<input
							type="checkbox"
							name="<?php echo esc_attr( BCCALC_OPTION_NAME ); ?>[selected_currencies][]"
							value="<?php echo esc_attr( $code ); ?>"
							<?php checked( in_array( $code, $selected, true ) ); ?>
						/>
						<?php echo esc_html( BCCALC_Currencies::get_display_label( $code, $all[ $code ] ) ); ?>
					</label>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="description">
			<?php esc_html_e( 'Checked currencies are shown on the frontend. Drag rows (using the handle) to set their display order.', 'borderless-currency-calculator' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the default from/to currency dropdowns.
	 *
	 * @return void
	 */
	public function render_defaults_field() {
		$settings = self::get_settings();
		$all      = BCCALC_Currencies::get_all();
		$selected = $settings['selected_currencies'];
		?>
		<p>
			<label for="bccalc-default-from"><?php esc_html_e( 'Default "From" Currency', 'borderless-currency-calculator' ); ?></label><br />
			<select name="<?php echo esc_attr( BCCALC_OPTION_NAME ); ?>[default_from]" id="bccalc-default-from">
				<?php foreach ( $selected as $code ) : ?>
					<?php
					if ( ! isset( $all[ $code ] ) ) {
						continue; }
					?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $settings['default_from'], $code ); ?>>
						<?php echo esc_html( $all[ $code ] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="bccalc-default-to"><?php esc_html_e( 'Default "To" Currency', 'borderless-currency-calculator' ); ?></label><br />
			<select name="<?php echo esc_attr( BCCALC_OPTION_NAME ); ?>[default_to]" id="bccalc-default-to">
				<?php foreach ( $selected as $code ) : ?>
					<?php
					if ( ! isset( $all[ $code ] ) ) {
						continue; }
					?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $settings['default_to'], $code ); ?>>
						<?php echo esc_html( $all[ $code ] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="description"><?php esc_html_e( 'Only currencies checked above are available here.', 'borderless-currency-calculator' ); ?></p>
		<?php
	}

	/**
	 * Render the custom-theme toggle.
	 *
	 * @return void
	 */
	public function render_theme_enabled_field() {
		$settings = self::get_settings();
		?>
		<label>
			<input
				type="checkbox"
				id="bccalc-theme-enabled"
				name="<?php echo esc_attr( BCCALC_OPTION_NAME ); ?>[theme_enabled]"
				value="1"
				<?php checked( ! empty( $settings['theme_enabled'] ) ); ?>
			/>
			<?php esc_html_e( 'Use the original custom theme (recommended). Disable to use the color/layout options below instead.', 'borderless-currency-calculator' ); ?>
		</label>
		<?php
	}

	/**
	 * Render the color picker fields (used when the custom theme is disabled).
	 *
	 * @return void
	 */
	public function render_colors_field() {
		$settings = self::get_settings();
		$defaults = self::get_default_settings();
		$fields   = array(
			'primary_color'     => __( 'Primary Color', 'borderless-currency-calculator' ),
			'secondary_color'   => __( 'Secondary / Accent Color', 'borderless-currency-calculator' ),
			'background_color'  => __( 'Background Color', 'borderless-currency-calculator' ),
			'text_color'        => __( 'Text Color', 'borderless-currency-calculator' ),
			'button_color'      => __( 'Button Color', 'borderless-currency-calculator' ),
			'button_text_color' => __( 'Button Text Color', 'borderless-currency-calculator' ),
		);
		?>
		<div class="bccalc-color-fields">
			<?php foreach ( $fields as $key => $label ) : ?>
				<p>
					<label for="bccalc-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label><br />
					<input
						type="text"
						class="bccalc-color-picker"
						id="bccalc-<?php echo esc_attr( $key ); ?>"
						name="<?php echo esc_attr( BCCALC_OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>]"
						value="<?php echo esc_attr( $settings[ $key ] ); ?>"
						data-default-color="<?php echo esc_attr( $defaults[ $key ] ); ?>"
					/>
				</p>
			<?php endforeach; ?>
		</div>
		<p class="description"><?php esc_html_e( 'These only apply when the custom theme above is disabled.', 'borderless-currency-calculator' ); ?></p>
		<?php
	}

	/**
	 * Render layout-related fields: border radius, font size, width.
	 *
	 * @return void
	 */
	public function render_layout_field() {
		$settings = self::get_settings();
		$defaults = self::get_default_settings();
		$widths   = array( '100%', '320px', '400px', '480px', '560px', '640px', '720px', '800px' );
		?>
		<p>
			<label for="bccalc-border-radius"><?php esc_html_e( 'Border Radius (px)', 'borderless-currency-calculator' ); ?></label><br />
			<input
				type="number"
				id="bccalc-border-radius"
				name="<?php echo esc_attr( BCCALC_OPTION_NAME ); ?>[border_radius]"
				min="0"
				max="50"
				value="<?php echo esc_attr( $settings['border_radius'] ); ?>"
				data-default="<?php echo esc_attr( $defaults['border_radius'] ); ?>"
			/>
		</p>
		<p>
			<label for="bccalc-font-size"><?php esc_html_e( 'Font Size (px)', 'borderless-currency-calculator' ); ?></label><br />
			<input
				type="number"
				id="bccalc-font-size"
				name="<?php echo esc_attr( BCCALC_OPTION_NAME ); ?>[font_size]"
				min="10"
				max="30"
				value="<?php echo esc_attr( $settings['font_size'] ); ?>"
				data-default="<?php echo esc_attr( $defaults['font_size'] ); ?>"
			/>
		</p>
		<p>
			<label for="bccalc-converter-width"><?php esc_html_e( 'Converter Width', 'borderless-currency-calculator' ); ?></label><br />
			<select
				name="<?php echo esc_attr( BCCALC_OPTION_NAME ); ?>[converter_width]"
				id="bccalc-converter-width"
				data-default="<?php echo esc_attr( $defaults['converter_width'] ); ?>"
			>
				<?php foreach ( $widths as $width ) : ?>
					<option value="<?php echo esc_attr( $width ); ?>" <?php selected( $settings['converter_width'], $width ); ?>>
						<?php echo esc_html( $width ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="description"><?php esc_html_e( 'Border radius and font size apply in both theme modes; width applies to the converter container.', 'borderless-currency-calculator' ); ?></p>
		<?php
	}
}

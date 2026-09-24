# Borderless Currency Calculator

A lightweight WordPress currency converter and calculator widget with live exchange rates, configurable currencies, currency flags, responsive design, and shortcode support.

## Features

* Live currency exchange rates
* No API key required
* Uses the Fawaz Ahmed Currency API
* Select which currencies are available in the converter
* Local currency list bundling (no external requests for settings page)
* Configure default From and To currencies
* Currency flags
* Currency swap functionality
* Responsive layout
* Configurable appearance
* Shortcode support
* Two-decimal conversion values
* Four-decimal exchange-rate display
* Thousands separators for large amounts
* WordPress Transients API caching
* Cloudflare API fallback endpoint
* No visitor account or personal information required

## Shortcode

Add the following shortcode to any WordPress page, post, or shortcode-enabled area:

```text
[bcc_currency_converter]
```

## Requirements

* WordPress 5.8 or higher
* PHP 7.4 or higher

## Installation

### WordPress

1. Download or clone the repository.
2. Upload the `borderless-currency-calculator` folder to:

```text
/wp-content/plugins/
```

3. Activate **Borderless Currency Calculator** from the WordPress Plugins screen.
4. Go to:

```text
Settings > Borderless Currency Calculator
```

5. Configure your currencies and appearance.
6. Add the shortcode to your page:

```text
[bcc_currency_converter]
```

### Git

Clone the repository into your WordPress plugins directory:

```bash
git clone https://github.com/YOUR-USERNAME/borderless-currency-calculator.git
```

## Exchange Rate API

This plugin uses the [Fawaz Ahmed Currency API](https://github.com/fawazahmed0/currency-api).

The API does not require an API key.

Exchange-rate responses are cached using WordPress transients to reduce repeated external requests. The currency list is bundled locally so the settings page does not need a separate external request.

The plugin uses a primary API endpoint and a documented Cloudflare fallback endpoint when the primary endpoint is unavailable.

## Number Formatting

Converted amounts are displayed with two decimal places and thousands separators.

Example:

```text
1,000.00 GBP = 1,338.26 USD
```

Exchange rates are displayed with four decimal places.

Example:

```text
1 GBP = 1.3383 USD
```

## Configuration

The plugin settings allow administrators to:

* Select available currencies
* Select default From currency
* Select default To currency
* Select all currencies
* Clear all currency selections
* Configure converter appearance
* Configure frontend display options

## Development

The plugin follows WordPress coding standards and includes a PHPCS configuration.

Run PHP syntax checks with:

```bash
php -l borderless-currency-calculator.php
```

Run PHP CodeSniffer with:

```bash
phpcs --standard=phpcs.xml.dist .
```

## Privacy

Borderless Currency Calculator does not require visitors to create an account and does not collect visitor information.

Exchange-rate requests are sent to the Fawaz Ahmed Currency API to retrieve current exchange-rate data.

## License

This plugin is licensed under the GPL v2 or later.

See the `LICENSE` file for details.

## Credits

Exchange-rate data is provided by the Fawaz Ahmed Currency API project:

https://github.com/fawazahmed0/currency-api

## Changelog

### 1.0.0

* Initial WordPress.org submission release
* Configurable currency selection
* Live exchange rates
* Currency flags
* Currency swapping
* Responsive converter
* Shortcode support
* Exchange-rate caching
* Configurable frontend appearance

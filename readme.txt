=== Borderless Currency Calculator ===
Contributors: Mihir Dave, mihirdev21
Tags: currency, converter, calculator, exchange rate, shortcode
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight currency converter and calculator widget with configurable currencies, live exchange rates, and shortcode support.

== Description ==

Borderless Currency Calculator adds a configurable currency converter to WordPress sites with the `[bcc_currency_converter]` shortcode.

* Live exchange rates from the free Fawaz Ahmed Currency API. No API key is required.
* Exchange-rate responses are cached with the WordPress Transients API to reduce requests. The currency list is bundled locally so the settings page does not need a separate external request.
* Choose which currencies are available and set the default From and To currencies.
* Includes flag indicators, responsive layout, currency swapping, and configurable appearance options.
* Conversion amounts display with two decimal places and thousands separators, while exchange rates display with four decimal places.
* The plugin does not require an account or collect visitor information.

The exchange-rate service is provided by the Fawaz Ahmed Exchange API project:
https://github.com/fawazahmed0/exchange-api

== Installation ==

1. Upload the `borderless-currency-calculator` folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Visit Settings > Borderless Currency Calculator to choose currencies and styling.
4. Add the `[bcc_currency_converter]` shortcode to any page or post.

== Frequently Asked Questions ==

= Does this require an API key? =

No. The Fawaz Ahmed Currency API does not require an API key for the exchange-rate data used by this plugin.

= How are exchange rates fetched? =

The plugin requests exchange-rate data from the Fawaz Ahmed Exchange API over HTTPS and caches successful responses with WordPress transients. If the primary endpoint is unavailable, the documented Cloudflare fallback endpoint is used.

= What happens if the currency API is unavailable? =

The plugin tries the fallback endpoint. If both endpoints fail, the converter displays an error instead of breaking the page. Previously cached rates remain available until their cache expires.

= How are numbers formatted? =

Converted amounts use two decimal places and comma thousands separators, for example `1,000.00`. The exchange-rate line uses four decimal places, for example `1 GBP = 1.3383 USD`.

== External services ==

This plugin connects to an API to obtain live currency exchange rates. This is necessary to perform accurate conversions between different currencies in the widget.

It sends a request to the Fawaz Ahmed Currency API (`https://latest.currency-api.pages.dev/v1/currencies/{currency}.min.json`) when exchange rates need to be updated. No personally identifiable data or user information is sent, only the currency code being queried.
This service is provided by the Fawaz Ahmed Currency API project:
* GitHub Repository: https://github.com/fawazahmed0/currency-api
* Please review the project documentation for more details.

== Changelog ==

= 1.0.0 =
* Initial WordPress.org submission release.

<?php
/**
 * Google Tag Manager loader. Consent Mode defaults and the saved consent update
 * are emitted by frontend.php at priority 0, before this loader runs.
 *
 * @package IsuDev\Consent
 */

declare( strict_types = 1 );

namespace IsuDev\Consent;

defined( 'ABSPATH' ) || exit;

\add_action( 'wp_head', __NAMESPACE__ . '\\print_gtm_head', 1 );

/**
 * Normalize and validate a Google Tag Manager container ID.
 *
 * @param mixed $value Raw container ID.
 * @return string
 */
function sanitize_gtm_id( $value ): string {
	$id = \strtoupper( \trim( (string) $value ) );

	return \preg_match( '/^GTM-[A-Z0-9]{4,12}$/', $id ) ? $id : '';
}

/**
 * Return the configured GTM ID, or an empty string when GTM is disabled.
 *
 * @return string
 */
function get_gtm_id(): string {
	return get_config()['gtm_id'];
}

/**
 * Whether GTM should load on this request.
 *
 * @return bool
 */
function is_gtm_active(): bool {
	return is_active() && '' !== get_gtm_id() && (bool) \apply_filters( 'isudev_consent_gtm_enabled', true );
}

/**
 * Print the GTM loader after Consent Mode has established its default state.
 *
 * @return void
 */
function print_gtm_head(): void {
	if ( ! is_gtm_active() ) {
		return;
	}

	\printf(
		"<script id=\"isudev-consent-gtm\">(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',%s);</script>\n",
		\wp_json_encode( get_gtm_id() ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Validated ID, JSON-encoded.
	);
}

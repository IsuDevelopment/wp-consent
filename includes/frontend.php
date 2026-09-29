<?php
/**
 * Front end: in <head> (first, priority 0) the Consent Mode v2 defaults and — when the visitor already chose — the
 * `update` from the cookie, so tags loaded later (GTM) start with the right state; in the footer the config and the
 * deferred banner script (it builds the banner and loads the CSS only when needed). Works with full-page caching:
 * nothing depends on the cookie server-side.
 *
 * @package IsuDev\Consent
 */

declare( strict_types = 1 );

namespace IsuDev\Consent;

defined( 'ABSPATH' ) || exit;

const COOKIE = 'isudev_consent';

\add_action( 'wp_head', __NAMESPACE__ . '\\print_head', 0 );
\add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue' );

/**
 * Whether the banner runs on this request.
 *
 * @return bool
 */
function is_active(): bool {
	return ! \is_admin() && ! \is_customize_preview() && ! \is_feed() && ! \wp_is_json_request() && (bool) \apply_filters( 'isudev_consent_enabled', true );
}

/**
 * Consent defaults + stored choice → gtag. Tiny, inline, before any tag.
 *
 * @return void
 */
function print_head(): void {
	if ( ! is_active() ) {
		return;
	}

	$version = get_config()['version'];

	// c = { p: preferences, a: analytics, m: marketing } (1/0); v = policy version.
	$script = 'window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}'
		. "gtag('consent','default',{ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',analytics_storage:'denied',functionality_storage:'denied',personalization_storage:'denied',security_storage:'granted',wait_for_update:500});gtag('set','ads_data_redaction',true);"
		. '(function(){var m=document.cookie.match(/(?:^|; )' . COOKIE . "=([^;]+)/);if(!m)return;try{var s=JSON.parse(decodeURIComponent(m[1]));if(s.v!=={$version})return;var c=s.c||{},g=function(x){return x?'granted':'denied'};"
		. "gtag('consent','update',{functionality_storage:g(c.p),personalization_storage:g(c.p),analytics_storage:g(c.a),ad_storage:g(c.m),ad_user_data:g(c.m),ad_personalization:g(c.m)});window.isudevConsent=s;}catch(e){}})();";

	\printf( "<script id=\"isudev-consent-mode\">%s</script>\n", $script ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static script, integer version.
}

/**
 * Banner script (deferred, footer) + its config.
 *
 * @return void
 */
function enqueue(): void {
	if ( ! is_active() ) {
		return;
	}

	$settings = get_config();
	$privacy  = (int) \get_option( 'wp_page_for_privacy_policy' );

	\wp_enqueue_script( 'isudev-consent', \plugins_url( 'assets/consent.js', FILE ), [], VERSION, [
		'in_footer' => true,
		'strategy'  => 'defer',
	] );

	\wp_add_inline_script( 'isudev-consent', 'window.isudevConsentConfig=' . \wp_json_encode( [
		'cookie'     => COOKIE,
		'version'    => $settings['version'],
		'days'       => $settings['days'],
		'categories' => \array_keys( \array_filter( $settings['categories'] ) ),
		'texts'      => get_texts(),
		'policy'     => $privacy && 'publish' === \get_post_status( $privacy ) ? (string) \get_permalink( $privacy ) : '',
		'css'        => \plugins_url( 'assets/consent.css', FILE ) . '?ver=' . VERSION,
		'log'        => \esc_url_raw( \rest_url( 'isudev-consent/v1/log' ) ),
	] ) . ';', 'before' );
}

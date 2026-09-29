<?php
/**
 * Consent log: table {prefix}isudev_consent_log and public POST /wp-json/isudev-consent/v1/log (sent with
 * sendBeacon). Pseudonymous: random consent ID from the cookie, choices, policy version, action, time and a salted
 * hash of the truncated network address (IPv4 /24, IPv6 /48). Rate limited per address; rows older than 2 years
 * are pruned. Used to demonstrate consent (GDPR art. 7(1)).
 *
 * @package IsuDev\Consent
 */

declare( strict_types = 1 );

namespace IsuDev\Consent;

defined( 'ABSPATH' ) || exit;

const DB_VERSION        = '1';
const DB_VERSION_OPTION = 'isudev_consent_db';
const RATE_LIMIT        = 20;

\add_action( 'init', __NAMESPACE__ . '\\maybe_install' );
\add_action( 'rest_api_init', __NAMESPACE__ . '\\register_route' );

/**
 * Table name.
 *
 * @return string
 */
function log_table(): string {
	global $wpdb;

	return $wpdb->prefix . 'isudev_consent_log';
}

/**
 * Create or update the table when the schema version changes.
 *
 * @return void
 */
function maybe_install(): void {
	if ( DB_VERSION !== \get_option( DB_VERSION_OPTION ) ) {
		install_log_table();
	}
}

/**
 * Create the table.
 *
 * @return void
 */
function install_log_table(): void {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	\dbDelta(
		'CREATE TABLE ' . log_table() . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		consent_id char(36) NOT NULL,
		choices varchar(64) NOT NULL,
		action varchar(20) NOT NULL,
		version smallint(5) unsigned NOT NULL,
		ip_hash char(64) NOT NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY consent_id (consent_id),
		KEY created_at (created_at)
	) {$wpdb->get_charset_collate()};"
	);

	\update_option( DB_VERSION_OPTION, DB_VERSION, false );
}

/**
 * Public route (anonymous visitors give consent too).
 *
 * @return void
 */
function register_route(): void {
	\register_rest_route(
		'isudev-consent/v1',
		'/log',
		[
			'methods'             => 'POST',
			'callback'            => __NAMESPACE__ . '\\store',
			'permission_callback' => '__return_true',
			'args'                => [
				'id'     => [
					'type'     => 'string',
					'required' => true,
					'pattern'  => '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$',
				],
				'v'      => [
					'type'    => 'integer',
					'minimum' => 1,
				],
				'c'      => [ 'type' => 'object' ],
				'action' => [
					'type' => 'string',
					'enum' => [ 'accept_all', 'reject_all', 'custom' ],
				],
			],
		]
	);
}

/**
 * Salted hash of the truncated client address.
 *
 * @return string
 */
function ip_hash(): string {
	$ip = (string) \filter_var( \wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ), FILTER_VALIDATE_IP ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated as an IP.

	if ( \str_contains( $ip, ':' ) ) {
		$ip = \implode( ':', \array_slice( \explode( ':', $ip ), 0, 3 ) ) . '::';
	} elseif ( '' !== $ip ) {
		$ip = (string) \preg_replace( '/\.\d+$/', '.0', $ip );
	}

	return \hash_hmac( 'sha256', $ip, \wp_salt( 'nonce' ) );
}

/**
 * Store one choice.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response|\WP_Error
 */
function store( \WP_REST_Request $request ) {
	global $wpdb;

	$hash  = ip_hash();
	$key   = 'isudev_consent_rl_' . \substr( $hash, 0, 20 );
	$count = (int) \get_transient( $key );

	if ( $count >= RATE_LIMIT ) {
		return new \WP_Error( 'isudev_consent_rate', 'Too many requests.', [ 'status' => 429 ] );
	}

	\set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS );

	$choices = (array) $request->get_param( 'c' );
	$flags   = \implode( ',', \array_map( fn( $c ) => $c . '=' . ( empty( $choices[ \substr( $c, 0, 1 ) ] ) ? 0 : 1 ), CATEGORIES ) );

	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Own table.
		log_table(),
		[
			'consent_id' => (string) $request->get_param( 'id' ),
			'choices'    => $flags,
			'action'     => (string) ( $request->get_param( 'action' ) ?? 'custom' ),
			'version'    => (int) ( $request->get_param( 'v' ) ?? 1 ),
			'ip_hash'    => $hash,
			'created_at' => \current_time( 'mysql', true ),
		]
	);

	// Occasional retention cleanup (2 years).
	if ( 1 === \wp_rand( 1, 200 ) ) {
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE created_at < %s', log_table(), \gmdate( 'Y-m-d H:i:s', \time() - 2 * YEAR_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Own table.
	}

	return new \WP_REST_Response( null, 204 );
}

/**
 * Totals for the settings page (last 30 days).
 *
 * @return void
 */
function render_log_summary(): void {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Own table, admin only.
	$rows = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT action, COUNT(*) AS n FROM %i WHERE created_at >= %s GROUP BY action', log_table(), \gmdate( 'Y-m-d H:i:s', \time() - 30 * DAY_IN_SECONDS ) ), ARRAY_A );

	if ( ! $rows ) {
		echo '<p>' . \esc_html__( 'No choices recorded in the last 30 days.', 'isudev-consent' ) . '</p>';
		return;
	}

	$labels = [
		'accept_all' => \__( 'Accepted all', 'isudev-consent' ),
		'reject_all' => \__( 'Rejected all', 'isudev-consent' ),
		'custom'     => \__( 'Custom choice', 'isudev-consent' ),
	];

	echo '<table class="widefat striped" style="max-width:420px"><thead><tr><th>' . \esc_html__( 'Last 30 days', 'isudev-consent' ) . '</th><th>#</th></tr></thead><tbody>';

	foreach ( $rows as $row ) {
		\printf( '<tr><td>%s</td><td>%d</td></tr>', \esc_html( $labels[ $row['action'] ] ?? $row['action'] ), (int) $row['n'] );
	}

	echo '</tbody></table>';
}

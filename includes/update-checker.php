<?php
/**
 * GitHub release update checker for packaged installs.
 *
 * @package IsuDev\Consent
 */

declare( strict_types = 1 );

namespace IsuDev\Consent;

defined( 'ABSPATH' ) || exit;

const REPOSITORY_URL = 'https://github.com/IsuDevelopment/wp-consent/';

/**
 * Whether the updater should register.
 *
 * @param bool $is_admin            Whether this is an admin request.
 * @param bool $doing_cron          Whether WordPress cron is running.
 * @param bool $factory_available   Whether Plugin Update Checker is loaded.
 * @param bool $source_checkout     Whether the plugin contains Git metadata.
 * @param bool $explicitly_disabled Whether self-updates are disabled.
 * @param bool $filter_enabled      Whether the site-level filter allows updates.
 * @return bool
 */
function should_register_updater(
	bool $is_admin,
	bool $doing_cron,
	bool $factory_available,
	bool $source_checkout,
	bool $explicitly_disabled,
	bool $filter_enabled
): bool {
	return ( $is_admin || $doing_cron )
		&& $factory_available
		&& ! $source_checkout
		&& ! $explicitly_disabled
		&& $filter_enabled;
}

/**
 * Register Plugin Update Checker against GitHub releases.
 *
 * @return void
 */
function register_update_checker(): void {
	$autoloaders = [ __DIR__ . '/../vendor/autoload.php' ];

	if ( \defined( 'WP_CONTENT_DIR' ) ) {
		$autoloaders[] = \dirname( WP_CONTENT_DIR, 2 ) . '/vendor/autoload.php';
	}

	foreach ( $autoloaders as $autoloader ) {
		if ( \is_readable( $autoloader ) ) {
			require_once $autoloader;
			break;
		}
	}

	// Composer-managed sites update this package with the application lock file.
	if ( \class_exists( '\\Composer\\InstalledVersions' ) && \Composer\InstalledVersions::isInstalled( 'isudev/consent' ) ) {
		return;
	}

	$disabled = \defined( 'ISUDEV_CONSENT_DISABLE_SELF_UPDATES' ) && (bool) \constant( 'ISUDEV_CONSENT_DISABLE_SELF_UPDATES' );
	$enabled  = (bool) \apply_filters( 'isudev_consent_self_updates_enabled', true, PLUGIN_FILE );

	if ( ! should_register_updater(
		\is_admin(),
		\wp_doing_cron(),
		\class_exists( '\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ),
		\is_dir( __DIR__ . '/../.git' ),
		$disabled,
		$enabled
	) ) {
		return;
	}

	$checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		REPOSITORY_URL,
		PLUGIN_FILE,
		'isudev-consent'
	);

	if ( \method_exists( $checker, 'getVcsApi' ) ) {
		$vcs_api = $checker->getVcsApi();
		if ( \is_object( $vcs_api ) && \method_exists( $vcs_api, 'enableReleaseAssets' ) ) {
			$vcs_api->enableReleaseAssets();
		}
	}
}

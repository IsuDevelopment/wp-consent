<?php
/**
 * Plugin Name:       IsuDev Consent
 * Description:       Lightweight cookie consent banner: Google Consent Mode v2, equal Accept/Reject, categories, consent log. No external scripts; CSS loads only when the banner shows.
 * Version:           1.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Author:            IsuDev
 * Author URI:        https://isudev.pl
 * Update URI:        false
 * License:           GPL-3.0-or-later
 * Text Domain:       isudev-consent
 * Domain Path:       /languages
 *
 * @package IsuDev\Consent
 */

declare( strict_types = 1 );

namespace IsuDev\Consent;

defined( 'ABSPATH' ) || exit;

const VERSION = '1.1.0';
const FILE    = __FILE__;

// Flat glob, alphabetical: settings.php (options, defaults) is used by the others only at run time.
\array_map( fn( $file ) => require_once $file, (array) \glob( __DIR__ . '/includes/*.php' ) );

\register_activation_hook( FILE, __NAMESPACE__ . '\\install_log_table' );

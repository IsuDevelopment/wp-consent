<?php
/**
 * Settings (one option) and the admin page under Settings → Cookies: texts (empty = translated default), which
 * categories exist, policy version (bump to ask everyone again), consent lifetime, plus consent log totals.
 *
 * @package IsuDev\Consent
 */

declare( strict_types = 1 );

namespace IsuDev\Consent;

defined( 'ABSPATH' ) || exit;

const OPTION     = 'isudev_consent';
const CAPABILITY = 'manage_options';
const CATEGORIES = [ 'preferences', 'analytics', 'marketing' ];

\add_action( 'admin_init', __NAMESPACE__ . '\\register_settings' );
\add_action( 'admin_menu', __NAMESPACE__ . '\\add_page' );
\add_filter( 'plugin_action_links_' . \plugin_basename( FILE ), __NAMESPACE__ . '\\add_settings_link' );

/**
 * Stored settings merged with defaults.
 *
 * @return array{version: int, days: int, title: string, message: string, categories: array<string, bool>, gravity_datalayer: bool, gf_recaptcha_net: bool}
 */
function get_config(): array {
	$saved = (array) \get_option( OPTION, [] );

	return [
		'version'           => \max( 1, (int) ( $saved['version'] ?? 1 ) ),
		'days'              => \min( 395, \max( 30, (int) ( $saved['days'] ?? 180 ) ) ),
		'title'             => (string) ( $saved['title'] ?? '' ),
		'message'           => (string) ( $saved['message'] ?? '' ),
		'categories'        => \array_combine( CATEGORIES, \array_map( fn( $c ) => (bool) ( $saved['categories'][ $c ] ?? true ), CATEGORIES ) ),
		'gravity_datalayer' => ! empty( $saved['gravity_datalayer'] ),
		'gf_recaptcha_net'  => ! empty( $saved['gf_recaptcha_net'] ),
	];
}

/**
 * Texts shown in the banner (settings override the translated defaults).
 *
 * @return array<string, string>
 */
function get_texts(): array {
	$settings = get_config();

	return [
		'title'         => '' !== $settings['title'] ? $settings['title'] : \__( 'We use cookies', 'isudev-consent' ),
		'message'       => '' !== $settings['message'] ? $settings['message'] : \__( 'We use necessary cookies to run the site. With your consent we also use analytics and marketing cookies to see how the site is used and to show relevant ads. You can change your choice at any time in cookie settings.', 'isudev-consent' ),
		'accept'        => \__( 'Accept all', 'isudev-consent' ),
		'reject'        => \__( 'Reject all', 'isudev-consent' ),
		'settings'      => \__( 'Settings', 'isudev-consent' ),
		'save'          => \__( 'Save choices', 'isudev-consent' ),
		'allow_all'     => \__( 'Allow all', 'isudev-consent' ),
		'intro'         => \__( 'We respect your privacy. Choose which cookies we may use; you can change it at any time.', 'isudev-consent' ),
		'policy'        => \__( 'Privacy policy', 'isudev-consent' ),
		'necessary'     => \__( 'Necessary', 'isudev-consent' ),
		'necessary_d'   => \__( 'Required for the site to work: cart, login, security, this choice. Always on.', 'isudev-consent' ),
		'preferences'   => \__( 'Preferences', 'isudev-consent' ),
		'preferences_d' => \__( 'Remember your settings, e.g. recently viewed items.', 'isudev-consent' ),
		'analytics'     => \__( 'Analytics', 'isudev-consent' ),
		'analytics_d'   => \__( 'Statistics that help us improve the site, e.g. Google Analytics.', 'isudev-consent' ),
		'marketing'     => \__( 'Marketing', 'isudev-consent' ),
		'marketing_d'   => \__( 'Ads and remarketing, e.g. Google Ads or Meta.', 'isudev-consent' ),
		'always'        => \__( 'Always on', 'isudev-consent' ),
	];
}

/**
 * Register the option.
 *
 * @return void
 */
function register_settings(): void {
	\register_setting(
		'isudev_consent',
		OPTION,
		[
			'type'              => 'array',
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize',
			'default'           => [],
		]
	);
}

/**
 * Sanitize the option.
 *
 * @param mixed $value Raw value.
 * @return array<string, mixed>
 */
function sanitize( $value ): array {
	$value = (array) $value;

	return [
		'version'           => \max( 1, (int) ( $value['version'] ?? 1 ) ),
		'days'              => \min( 395, \max( 30, (int) ( $value['days'] ?? 180 ) ) ),
		'title'             => \sanitize_text_field( (string) ( $value['title'] ?? '' ) ),
		'message'           => \sanitize_textarea_field( (string) ( $value['message'] ?? '' ) ),
		'categories'        => \array_combine( CATEGORIES, \array_map( fn( $c ) => ! empty( $value['categories'][ $c ] ), CATEGORIES ) ),
		'gravity_datalayer' => ! empty( $value['gravity_datalayer'] ),
		'gf_recaptcha_net'  => ! empty( $value['gf_recaptcha_net'] ),
	];
}

/**
 * Settings → Cookies.
 *
 * @return void
 */
function add_page(): void {
	\add_options_page( \__( 'Cookie consent', 'isudev-consent' ), \__( 'Cookies', 'isudev-consent' ), CAPABILITY, 'isudev-consent', __NAMESPACE__ . '\\render_page' );
}

/**
 * Plugins screen shortcut.
 *
 * @param array<int, string> $links Action links.
 * @return array<int, string>
 */
function add_settings_link( array $links ): array {
	\array_unshift( $links, \sprintf( '<a href="%s">%s</a>', \esc_url( \admin_url( 'options-general.php?page=isudev-consent' ) ), \esc_html__( 'Settings', 'isudev-consent' ) ) );

	return $links;
}

/**
 * The settings page.
 *
 * @return void
 */
function render_page(): void {
	if ( ! \current_user_can( CAPABILITY ) ) {
		return;
	}

	$settings = get_config();
	$texts    = get_texts();
	$name     = static fn( string $key ): string => \esc_attr( OPTION . '[' . $key . ']' );
	?>
	<div class="wrap">
		<h1><?php \esc_html_e( 'Cookie consent', 'isudev-consent' ); ?></h1>
		<form method="post" action="options.php">
			<?php \settings_fields( 'isudev_consent' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ic-title"><?php \esc_html_e( 'Title', 'isudev-consent' ); ?></label></th>
					<td><input type="text" id="ic-title" class="regular-text" name="<?php echo $name( 'title' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in $name. ?>" value="<?php echo \esc_attr( $settings['title'] ); ?>" placeholder="<?php echo \esc_attr( $texts['title'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php \esc_html_e( 'Gravity Forms reCAPTCHA', 'isudev-consent' ); ?></th>
					<td>
						<label><input type="checkbox" name="<?php echo $name( 'gf_recaptcha_net' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in $name. ?>" value="1"<?php \checked( $settings['gf_recaptcha_net'] ); ?>> <?php \esc_html_e( 'Load GF CAPTCHA from recaptcha.net', 'isudev-consent' ); ?></label>
						<p class="description"><?php \esc_html_e( 'Replaces the Gravity Forms reCAPTCHA script host to avoid unnecessary google.com cookies.', 'isudev-consent' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ic-message"><?php \esc_html_e( 'Message', 'isudev-consent' ); ?></label></th>
					<td><textarea id="ic-message" class="large-text" rows="4" name="<?php echo $name( 'message' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in $name. ?>" placeholder="<?php echo \esc_attr( $texts['message'] ); ?>"><?php echo \esc_textarea( $settings['message'] ); ?></textarea>
					<p class="description"><?php \esc_html_e( 'Empty = the default text. The privacy policy link comes from Settings → Privacy.', 'isudev-consent' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><?php \esc_html_e( 'Categories', 'isudev-consent' ); ?></th>
					<td>
						<?php foreach ( CATEGORIES as $category ) : ?>
							<label style="display:block;margin-bottom:6px"><input type="checkbox" name="<?php echo $name( 'categories' ) . '[' . \esc_attr( $category ) . ']'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped. ?>" value="1"<?php \checked( $settings['categories'][ $category ] ); ?>> <?php echo \esc_html( $texts[ $category ] . ' – ' . $texts[ $category . '_d' ] ); ?></label>
						<?php endforeach; ?>
						<p class="description"><?php \esc_html_e( 'Show only the categories the site really uses. Consent Mode: preferences = functionality/personalization storage, analytics = analytics_storage, marketing = ad_storage, ad_user_data, ad_personalization.', 'isudev-consent' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ic-version"><?php \esc_html_e( 'Policy version', 'isudev-consent' ); ?></label></th>
					<td><input type="number" min="1" id="ic-version" class="small-text" name="<?php echo $name( 'version' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in $name. ?>" value="<?php echo \esc_attr( (string) $settings['version'] ); ?>">
					<p class="description"><?php \esc_html_e( 'Raise it after adding a new tool or purpose: every visitor is asked again.', 'isudev-consent' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><label for="ic-days"><?php \esc_html_e( 'Remember the choice for (days)', 'isudev-consent' ); ?></label></th>
					<td><input type="number" min="30" max="395" id="ic-days" class="small-text" name="<?php echo $name( 'days' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in $name. ?>" value="<?php echo \esc_attr( (string) $settings['days'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php \esc_html_e( 'Google Tag Manager', 'isudev-consent' ); ?></th>
					<td>
						<label><input type="checkbox" name="<?php echo $name( 'gravity_datalayer' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in $name. ?>" value="1"<?php \checked( $settings['gravity_datalayer'] ); ?>> <?php \esc_html_e( 'Add Gravity Forms DataLayer events', 'isudev-consent' ); ?></label>
						<p class="description"><?php \esc_html_e( 'Pushes gform_submit after a successful Gravity Forms submission. Events are sent only after analytics or marketing consent.', 'isudev-consent' ); ?></p>
					</td>
				</tr>
			</table>
			<?php \submit_button(); ?>
		</form>
		<h2><?php \esc_html_e( 'Consent log', 'isudev-consent' ); ?></h2>
		<p><?php \esc_html_e( 'Pseudonymous record of choices (random ID, choices, policy version, time, hashed network address), kept for 2 years, to demonstrate consent. Re-open the banner with any link to #cookie-settings or an element with data-consent-open.', 'isudev-consent' ); ?></p>
		<?php render_log_summary(); ?>
	</div>
	<?php
}

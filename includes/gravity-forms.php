<?php
/**
 * Optional Gravity Forms → GTM DataLayer bridge.
 *
 * @package IsuDev\Consent
 */

declare( strict_types = 1 );

namespace IsuDev\Consent;

defined( 'ABSPATH' ) || exit;

\add_action( 'wp_footer', __NAMESPACE__ . '\\print_gravity_forms_datalayer', 100 );
\add_filter( 'script_loader_tag', __NAMESPACE__ . '\\rewrite_gf_recaptcha_source', 10, 3 );

/**
 * Print the optional Gravity Forms success event bridge.
 *
 * @return void
 */
function print_gravity_forms_datalayer(): void {
	if ( ! is_active() || ! get_config()['gravity_datalayer'] ) {
		return;
	}
	?>
	<script id="isudev-gravity-forms-datalayer">
	(() => {
		const pushed = new Set();

		const pushSuccess = (data, confirmation = true) => {
			if (!confirmation || !data?.formId) {
				return;
			}

			const consent = window.isudevConsent?.c || {};

			if (!consent.a && !consent.m) {
				return;
			}

			const formId = String(data.formId);

			if (pushed.has(formId)) {
				return;
			}

			pushed.add(formId);
			const form = document.getElementById(`gform_${formId}`);
			const pagePath = window.location.pathname + window.location.search + window.location.hash;

			window.dataLayer = window.dataLayer || [];
			window.dataLayer.push({
				event: 'gform_submit',
				form_id: formId,
				form_name: data.formTitle || data.form?.title || form?.dataset?.title || form?.getAttribute('data-title') || '',
				form_type: 'gform',
				form_status: 'success',
				page_location: window.location.href,
				page_path: pagePath,
				page_title: document.title,
				consent_analytics: Boolean(consent.a),
				consent_marketing: Boolean(consent.m),
			});
		};

		document.addEventListener('gform/ajax/post_ajax_submission', (event) => {
			const data = event.detail || {};

			pushSuccess(data, Boolean(data.submissionResult?.confirmation_message));
		});

		if (window.jQuery) {
			window.jQuery(document).on('gform_confirmation_loaded', (event, formId) => {
				pushSuccess({ formId });
			});
		}
	})();
	</script>
	<?php
}

/**
 * Optionally load Gravity Forms reCAPTCHA from recaptcha.net.
 *
 * @param string $tag    Script tag.
 * @param string $handle Script handle.
 * @param string $src    Script source URL.
 * @return string
 */
function rewrite_gf_recaptcha_source( string $tag, string $handle, string $src ): string {
	if ( ! get_config()['gf_recaptcha_net'] || 'gform_recaptcha' !== $handle || ! str_contains( $src, 'www.google.com/recaptcha/api.js' ) ) {
		return $tag;
	}

	return str_replace( 'www.google.com/recaptcha/api.js', 'www.recaptcha.net/recaptcha/api.js', $tag );
}

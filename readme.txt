=== IsuDev Consent ===
Contributors: isudev
Tags: cookies, consent, privacy, google consent mode, gdpr
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.3.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Lightweight cookie consent banner for WordPress with Google Consent Mode v2, equal Accept/Reject controls, configurable categories and a pseudonymous consent log.

== Description ==

IsuDev Consent has no external scripts and is designed to remain compatible with full-page caching. It prints Consent Mode defaults before later tag loaders, updates the state from the first-party consent cookie, and only loads the banner CSS when the visitor needs to make a choice.

Settings are available under **Settings → Cookies**. The banner supports preferences, analytics and marketing categories, a configurable policy version, consent lifetime, a privacy-policy link and an optional validated Google Tag Manager container ID. GTM loads after the Consent Mode defaults; its no-JavaScript iframe is intentionally omitted because it cannot receive those defaults.

Third-party snippets can be delayed with `script type="text/plain"` and `data-consent="analytics"`, `marketing` or `preferences`. The plugin emits `consent_update` and `consent_ready` data layer events for GTM integrations.

When enabled in **Settings → Cookies**, the Gravity Forms DataLayer bridge emits a `gform_submit` event after successful submissions, including AJAX forms. It includes `form_id`, `form_name`, `form_type`, `form_status`, `page_location`, `page_path` and `page_title`. The optional reCAPTCHA setting changes the Gravity Forms script host to `recaptcha.net`.

Packaged installs update from GitHub releases through Plugin Update Checker. Source checkouts containing `.git` and Composer-managed installs do not self-update. Define `ISUDEV_CONSENT_DISABLE_SELF_UPDATES` or use the `isudev_consent_self_updates_enabled` filter to disable self-updates.

== Installation ==

Download `isudev-consent.zip` from the GitHub releases page and install it as a plugin, or require it with Composer:

`composer require isudev/consent`

== Changelog ==

= 1.3.0 =
* Added an optional, validated Google Tag Manager container loader that runs after Consent Mode defaults.
* Added bundled Polish, German, Norwegian Bokmål, Swedish, Portuguese, French, Spanish, Ukrainian and Italian translations.
* Fixed packaged translation loading.

= 1.2.1 =
* Lowered banner selector specificity to zero for theme overrides.
* Added button background, text, border and hover design tokens.

= 1.2.0 =
* Added optional Gravity Forms `gform_submit` DataLayer events with page context.
* Added optional Gravity Forms reCAPTCHA loading from `recaptcha.net`.
* Modernized PHP array syntax across the plugin.

= 1.1.0 =
* Prepared the plugin as an installable standalone release.
* Added GitHub release updates for packaged installs.
* Added release version checks and clean ZIP packaging.
* Fixed the policy-version value emitted in the inline Consent Mode bootstrap.

= 1.0.0 =
* Initial standalone consent banner with Consent Mode v2, categories and pseudonymous consent logging.

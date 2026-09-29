# Code map: isudev-consent

Lightweight cookie consent banner (`packages/plugins/isudev-consent`, Composer `isudev/consent`, namespace
`IsuDev\Consent`, text domain `isudev-consent`). Generic on purpose — nothing Butik-specific, meant to be forked
into a standalone plugin (user plan 2026-09-28) because banner plugins (CookieYes…) top speed reports. PHP + one
plain JS and CSS file, no build step.

## Entry points

| Concern | Files |
| --- | --- |
| Bootstrap | `isudev-consent.php` — header, flat glob of `includes/*.php`, activation → log table |
| Settings | `includes/settings.php` — option `isudev_consent` (`version`, `days` 30–395, `title`, `message`, `categories` preferences/analytics/marketing), `get_config()`, `get_texts()` (settings override translated defaults), page Settings → Cookies (`manage_options`), log totals |
| Head | `includes/frontend.php` `print_head()` @ `wp_head` 0: `dataLayer`/`gtag` stub, Consent Mode v2 defaults (all denied except `security_storage`, `wait_for_update` 500, `ads_data_redaction`), then — if cookie `isudev_consent` has the current `v` — `gtag('consent','update')` from it. Must stay before any tag loader |
| Banner | `enqueue()` — `assets/consent.js` (footer, `defer`) + `window.isudevConsentConfig` (cookie, version, days, categories, texts, privacy page URL from `wp_page_for_privacy_policy`, CSS URL, log URL). JS builds the banner only without a valid choice, injects `assets/consent.css` then; `role="dialog"` non-modal, full-width dark bar. Layer 1: Settings · Reject · Accept all (same size, Accept filled = suggested). Layer 2 (Settings): intro, categories with `role="switch"` checkboxes (Necessary "always on"), Reject · Save choices · Allow all. Re-open: `a[href$="#cookie-settings"]` or `[data-consent-open]` (focus moves in, returns on close) |
| Choice | cookie `isudev_consent` = JSON `{v, t, id (UUID), c: {p, a, m}}`, `SameSite=Lax`, `Secure` on HTTPS, `max-age` = days; `gtag('consent','update')`; dataLayer `consent_update` (on choice) / `consent_ready` (later page views) with `{preferences, analytics, marketing}`; releases `<script type="text/plain" data-consent="analytics\|marketing\|preferences">` |
| Log | `includes/log.php` — table `{prefix}isudev_consent_log` (`consent_id`, `choices`, `action` accept_all/reject_all/custom, `version`, `ip_hash` = HMAC of IPv4 /24 or IPv6 /48 with `wp_salt('nonce')`, `created_at` GMT), schema option `isudev_consent_db`; `POST /wp-json/isudev-consent/v1/log` (public, `sendBeacon`, 20 per 10 min per address), prunes rows > 2 years |
| Translations | `packages/translations/plugins/isudev-consent-pl_PL.po` (+ mo, l10n.php); pot in `languages/` |

## External couplings

- Google Consent Mode v2 command names and storage keys; GTM reads `dataLayer`. Block-library `includes/gtm.php`
  skips its own defaults when `IsuDev\Consent\print_head` exists (both print in `<head>`: consent @0, GTM @1).
- Theme footer pattern: "Cookie settings" link `#cookie-settings`; stage footer (DB) got "Ustawienia cookies".
- Styles fall back from `--ic-*` to `theme.json` presets (`--wp--preset--color--primary`, `surface`, `contrast`,
  `muted`, `border`, `primary-hover`, font `body`).

## Decisions

- Reject is on the first layer and the same size as Accept (EDPB Cookie Banner Taskforce 2023: no reject on layer 1 = breach for most DPAs). Accept may be the filled/suggested button (user decision 2026-09-28, variant 1 instead of a Reserved-style banner without reject). Closing without a choice is not consent, so there is no ×.
- No server-side cookie check: the HTML stays cacheable (LiteSpeed full-page cache); everything reads the cookie in JS.
- CSS is not enqueued: returning visitors load only the head snippet (~0.8 KB) + the deferred script (~2.8 KB gzip).
- Categories map: preferences → functionality/personalization storage, analytics → analytics_storage,
  marketing → ad_storage, ad_user_data, ad_personalization.

## Verify

- New visitor (private window) at 390 and 1280: banner bottom (full width / 480 px box), no CLS, keyboard reachable.
- Reject → cookie set, banner gone, `dataLayer` has `consent update` denied and `consent_update`; reload → no banner,
  no `consent.css` request, `consent_ready`. Footer "Ustawienia cookies" → settings open, focus on the first switch.
- Settings → Cookies shows the 30-day totals; `wp db query "SELECT * FROM wp_isudev_consent_log ORDER BY id DESC LIMIT 3"`.
- Raise "Policy version" → every visitor sees the banner again.

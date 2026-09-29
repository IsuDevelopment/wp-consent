# IsuDev Consent

Lightweight cookie consent for WordPress: Google Consent Mode v2, equal Accept/Reject, categories (preferences,
analytics, marketing), a pseudonymous consent log, no external scripts. Returning visitors load ~0.8 KB inline in
`<head>` and ~2.8 KB (gzip) deferred JS; the CSS loads only while the banner is shown. Page-cache safe.

- Settings → Cookies: texts, categories, policy version (raise to ask again), lifetime, GTM container ID and 30-day totals.
- Re-open the banner: a link to `#cookie-settings` or any element with `data-consent-open`.
- Load a third-party snippet only after consent: `<script type="text/plain" data-consent="analytics">…</script>`.
- GTM: enter a validated container ID (`GTM-…`) in Settings → Cookies. Its loader runs after the plugin's Consent
  Mode snippet; use `consent_update` / `consent_ready` dataLayer events or the built-in consent checks in tags.
  The no-JavaScript GTM iframe is deliberately omitted because it cannot receive Consent Mode defaults.
- Styling: the plugin selectors have zero specificity. Override tokens with a selector such as
  `:root :where(.ic-banner)`. Secondary buttons use `--ic-btn-secondary-bg`, `--ic-btn-secondary-color`,
  `--ic-btn-secondary-border` and matching `--ic-btn-secondary-hover-*` tokens. Primary buttons use the matching
  `--ic-btn-primary-*` tokens.

Details and the rationale: `.agents/code-map/isudev-consent.md` in the repository.

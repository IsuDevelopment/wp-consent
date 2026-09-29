# IsuDev Consent

Lightweight cookie consent for WordPress: Google Consent Mode v2, equal Accept/Reject, categories (preferences,
analytics, marketing), a pseudonymous consent log, no external scripts. Returning visitors load ~0.8 KB inline in
`<head>` and ~2.8 KB (gzip) deferred JS; the CSS loads only while the banner is shown. Page-cache safe.

- Settings → Cookies: texts, categories, policy version (raise to ask again), lifetime, 30-day totals.
- Re-open the banner: a link to `#cookie-settings` or any element with `data-consent-open`.
- Load a third-party snippet only after consent: `<script type="text/plain" data-consent="analytics">…</script>`.
- GTM: load it after this plugin's head snippet (priority > 0); use `consent_update` / `consent_ready` dataLayer
  events or the built-in consent checks in tags.
- Styling: override `--ic-bg`, `--ic-text`, `--ic-muted`, `--ic-border`, `--ic-accent`, `--ic-accent-hover`,
  `--ic-on-accent`, `--ic-radius`, `--ic-font` on `.ic-banner`.

Details and the rationale: `.agents/code-map/isudev-consent.md` in the repository.

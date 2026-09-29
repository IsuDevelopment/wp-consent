/**
 * IsuDev Consent banner. No dependencies. Shows the banner when there is no valid choice, stores it in a
 * first-party cookie, updates Google Consent Mode, pushes `consent_update` to the dataLayer, runs
 * `<script type="text/plain" data-consent="analytics|marketing|preferences">` once allowed and logs the choice.
 * Re-open: any `a[href="#cookie-settings"]` or `[data-consent-open]`.
 */
(() => {
	const cfg = window.isudevConsentConfig;

	if (!cfg) {
		return;
	}

	const KEYS = { preferences: 'p', analytics: 'a', marketing: 'm' };
	const t = cfg.texts;
	let root = null;
	let lastFocus = null;

	const read = () => {
		const m = document.cookie.match(new RegExp('(?:^|; )' + cfg.cookie + '=([^;]+)'));

		try {
			const s = m ? JSON.parse(decodeURIComponent(m[1])) : null;

			return s && s.v === cfg.version ? s : null;
		} catch {
			return null;
		}
	};

	// randomUUID needs a secure context; the fallback only has to be unique enough for a log key.
	const uuid = () => {
		if (window.crypto?.randomUUID) {
			return window.crypto.randomUUID();
		}

		const hex = Array.from({ length: 32 }, () => Math.floor(Math.random() * 16).toString(16)).join('');

		return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-4${hex.slice(13, 16)}-${'89ab'[Math.floor(Math.random() * 4)]}${hex.slice(17, 20)}-${hex.slice(20)}`;
	};

	// fromHead: the head script already sent the update, only announce it and release snippets.
	const apply = (c, fromHead = false) => {
		const g = (on) => (on ? 'granted' : 'denied');

		window.dataLayer = window.dataLayer || [];
		window.gtag =
			window.gtag ||
			function () {
				window.dataLayer.push(arguments);
			};
		if (!fromHead) {
			window.gtag('consent', 'update', {
				functionality_storage: g(c.p),
				personalization_storage: g(c.p),
				analytics_storage: g(c.a),
				ad_storage: g(c.m),
				ad_user_data: g(c.m),
				ad_personalization: g(c.m),
			});
		}
		window.dataLayer.push({
			event: fromHead ? 'consent_ready' : 'consent_update',
			consent: { preferences: !!c.p, analytics: !!c.a, marketing: !!c.m },
		});

		// Deferred third-party snippets marked with their category.
		document.querySelectorAll('script[type="text/plain"][data-consent]').forEach((el) => {
			if (c[KEYS[el.dataset.consent]]) {
				const s = document.createElement('script');

				[...el.attributes].forEach((a) => a.name !== 'type' && s.setAttribute(a.name, a.value));
				s.text = el.text;
				el.replaceWith(s);
			}
		});
	};

	const save = (c, action) => {
		const prev = read();
		const state = { v: cfg.version, t: Date.now(), id: prev?.id || uuid(), c };

		document.cookie =
			cfg.cookie +
			'=' +
			encodeURIComponent(JSON.stringify(state)) +
			';path=/;max-age=' +
			cfg.days * 86400 +
			';SameSite=Lax' +
			(window.location.protocol === 'https:' ? ';Secure' : '');
		window.isudevConsent = state;
		apply(c);

		const body = JSON.stringify({ id: state.id, v: cfg.version, c, action });

		if (!navigator.sendBeacon?.(cfg.log, new Blob([body], { type: 'application/json' }))) {
			fetch(cfg.log, {
				method: 'POST',
				body,
				headers: { 'Content-Type': 'application/json' },
				keepalive: true,
			}).catch(() => {});
		}

		hide();
	};

	const all = (on) => {
		const c = {};

		cfg.categories.forEach((cat) => (c[KEYS[cat]] = on ? 1 : 0));

		return c;
	};

	const el = (tag, props = {}, children = []) => {
		const node = document.createElement(tag);

		Object.entries(props).forEach(([k, v]) => (k === 'text' ? (node.textContent = v) : node.setAttribute(k, v)));
		children.forEach((child) => child && node.append(child));

		return node;
	};

	const loadCss = () =>
		new Promise((resolve) => {
			if (document.getElementById('isudev-consent-css')) {
				resolve();
				return;
			}

			const link = el('link', { id: 'isudev-consent-css', rel: 'stylesheet', href: cfg.css });

			link.onload = resolve;
			link.onerror = resolve;
			document.head.append(link);
		});

	// Layer 1: message + Settings / Reject / Accept (same size; Accept is the filled one). Layer 2 (settings):
	// categories with switches + Reject / Save / Allow all. Reject stays on both layers (EDPB: as easy as accepting).
	const build = (openSettings) => {
		const current = read()?.c || {};
		const option = (cat, locked) => {
			const id = 'ic-' + cat;
			const head = el('div', { class: 'ic-option-head' }, [
				locked
					? el('span', { class: 'ic-option-title', text: t[cat] })
					: el('label', { for: id, class: 'ic-option-title', text: t[cat] }),
			]);

			if (locked) {
				head.append(el('span', { class: 'ic-always', text: t.always }));
			} else {
				const input = el('input', {
					type: 'checkbox',
					role: 'switch',
					id,
					'data-cat': cat,
					class: 'ic-switch',
				});

				input.checked = !!current[KEYS[cat]];
				head.append(input);
			}

			return el('div', { class: 'ic-option' }, [head, el('p', { class: 'ic-option-text', text: t[cat + '_d'] })]);
		};

		const settings = el('div', { class: 'ic-settings', id: 'ic-settings' }, [
			el('p', { class: 'ic-intro', text: t.intro }),
			option('necessary', true),
			...cfg.categories.map((cat) => option(cat, false)),
		]);
		const button = (text, primary) =>
			el('button', { type: 'button', class: primary ? 'ic-btn is-primary' : 'ic-btn', text });
		const toggle = button(t.settings, false);
		const reject = button(t.reject, false);
		const saveBtn = button(t.save, false);
		const accept = button(t.accept, true);
		const allow = button(t.allow_all, true);

		toggle.setAttribute('aria-controls', 'ic-settings');

		const setLayer = (open) => {
			settings.hidden = !open;
			toggle.hidden = open;
			accept.hidden = open;
			saveBtn.hidden = !open;
			allow.hidden = !open;
			toggle.setAttribute('aria-expanded', String(open));
		};

		setLayer(openSettings);
		toggle.addEventListener('click', () => {
			setLayer(true);
			settings.querySelector('input')?.focus();
		});
		saveBtn.addEventListener('click', () => {
			const c = all(false);

			settings.querySelectorAll('input[data-cat]').forEach((input) => {
				c[KEYS[input.dataset.cat]] = input.checked ? 1 : 0;
			});
			save(c, 'custom');
		});
		accept.addEventListener('click', () => save(all(true), 'accept_all'));
		allow.addEventListener('click', () => save(all(true), 'accept_all'));
		reject.addEventListener('click', () => save(all(false), 'reject_all'));

		const message = el('p', { class: 'ic-text', id: 'ic-text', text: t.message + ' ' });

		if (cfg.policy) {
			message.append(el('a', { href: cfg.policy, text: t.policy }));
		}

		return el(
			'div',
			{
				class: 'ic-banner',
				role: 'dialog',
				'aria-modal': 'false',
				'aria-labelledby': 'ic-title',
				'aria-describedby': 'ic-text',
			},
			[
				el('div', { class: 'ic-inner' }, [
					el('p', { class: 'ic-title', id: 'ic-title', text: t.title }),
					message,
					settings,
					el('div', { class: 'ic-actions' }, [toggle, reject, saveBtn, accept, allow]),
				]),
			]
		);
	};

	const show = async (openSettings = false, focus = false) => {
		await loadCss();
		hide();
		lastFocus = focus ? document.body.ownerDocument.activeElement : null;
		root = build(openSettings);
		document.body.append(root);

		if (focus) {
			root.querySelector(openSettings ? 'input:not(:disabled), button' : 'button')?.focus();
		}
	};

	const hide = () => {
		root?.remove();
		root = null;
		lastFocus?.focus?.();
		lastFocus = null;
	};

	document.addEventListener('click', (e) => {
		const trigger = e.target.closest?.('a[href$="#cookie-settings"], [data-consent-open]');

		if (trigger) {
			e.preventDefault();
			show(true, true);
		}
	});

	const stored = read();

	if (stored) {
		apply(stored.c || {}, true);
	} else {
		show();
	}
})();

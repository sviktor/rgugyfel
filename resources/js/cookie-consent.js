/*
 * GDPR cookie consent - banner + settings modal logic (Alpine component).
 * Ported from the Claude Design handoff (rg-cookies.jsx). The decision is
 * stored in a plain (NOT Laravel-encrypted) cookie as JSON so both this
 * script and the Blade layer (App\Support\CookieConsent, e.g. the GA gate
 * in the layout) can read it. Cookie lifetime comes from COOKIE_CONSENT_DAYS
 * (config/consent.php). This exact file ships in all three public sites.
 */

/**
 * Read and validate the saved consent record from document.cookie.
 *
 * @param {string} cookieName - the consent cookie name (e.g. 'rg-cookie-consent-v1')
 * @returns {?{choices: Object, timestamp: string, version: number}} null when no valid decision exists
 * @example
 *	const rec = readConsent('rg-cookie-consent-v1');
 *	if (rec && rec.choices.statistics) { initAnalytics(); }
 */
export function readConsent(cookieName) {
	const hit = document.cookie.split('; ').find((c) => c.startsWith(cookieName + '='));
	if (!hit) return null;
	try {
		const parsed = JSON.parse(decodeURIComponent(hit.slice(cookieName.length + 1)));
		return (parsed && typeof parsed === 'object' && parsed.choices) ? parsed : null;
	} catch (e) {
		return null;
	}
}

/**
 * Alpine.data factory for the cookie consent banner + settings modal
 * (markup: partials/cookie-consent.blade.php).
 *
 * After every save a `cookie-consent-changed` CustomEvent fires on window
 * with the saved choices in event.detail. Any element carrying a
 * `data-cookie-settings` attribute (footer link) reopens the settings modal.
 *
 * @param {Object} config
 * @param {string} config.cookieName - per-site consent cookie name
 * @param {number} config.days - consent lifetime in days (COOKIE_CONSENT_DAYS)
 * @param {string} [config.gaId] - Google Analytics measurement id; when set,
 *	a first-time statistics opt-in injects gtag.js immediately (no reload),
 *	while a revoke drops the _ga* cookies and reloads to stop the running GA.
 * @returns {Object} Alpine component definition
 * @example
 *	Alpine.data('cookieConsent', cookieConsent);
 *	// <div x-data="cookieConsent({ cookieName: 'rg-cookie-consent-v1', days: 180 })">
 */
export default function cookieConsent(config) {
	return {
		choices: { preferences: false, statistics: false, marketing: false },
		bannerVisible: false,
		settingsOpen: false,
		saved: null,

		init() {
			this.saved = readConsent(config.cookieName);
			if (this.saved) {
				this.choices = {
					preferences: !!this.saved.choices.preferences,
					statistics: !!this.saved.choices.statistics,
					marketing: !!this.saved.choices.marketing,
				};
			}
			this.bannerVisible = !this.saved;
			// Delegated so links inside late-rendered fragments work too.
			document.addEventListener('click', (e) => {
				const trigger = e.target.closest('[data-cookie-settings]');
				if (trigger) {
					e.preventDefault();
					this.openSettings();
				}
			});
		},

		openSettings() {
			this.settingsOpen = true;
			this.bannerVisible = false;
			document.body.style.overflow = 'hidden';
		},

		// Close without saving - only once a decision exists (GDPR: the
		// first-visit dialog must not be dismissible without a choice).
		closeSettings() {
			if (!this.saved || !this.settingsOpen) return;
			this.settingsOpen = false;
			document.body.style.overflow = '';
		},

		acceptAll() { this.persist({ preferences: true, statistics: true, marketing: true }); },
		rejectAll() { this.persist({ preferences: false, statistics: false, marketing: false }); },
		saveSelected() { this.persist({ ...this.choices }); },

		persist(choices) {
			const prevStats = !!(this.saved && this.saved.choices.statistics);
			const record = {
				choices: { ...choices, necessary: true },
				timestamp: new Date().toISOString(),
				version: 1,
			};
			let cookie = config.cookieName + '=' + encodeURIComponent(JSON.stringify(record))
				+ '; max-age=' + Math.max(1, Math.round(config.days * 86400))
				+ '; path=/; samesite=lax';
			if (location.protocol === 'https:') cookie += '; secure';
			document.cookie = cookie;
			this.saved = record;
			this.choices = {
				preferences: !!choices.preferences,
				statistics: !!choices.statistics,
				marketing: !!choices.marketing,
			};
			this.settingsOpen = false;
			this.bannerVisible = false;
			document.body.style.overflow = '';
			window.dispatchEvent(new CustomEvent('cookie-consent-changed', { detail: record.choices }));
			this.applyStatistics(prevStats, !!record.choices.statistics);
		},

		// Enforce the statistics choice on Google Analytics. The gtag snippet
		// itself is server-gated (layout emits it only with saved consent);
		// here we cover the two in-page transitions.
		applyStatistics(prev, now) {
			if (!prev && now && config.gaId && typeof window.gtag === 'undefined') {
				// First opt-in: start GA right away so the landing page counts.
				const s = document.createElement('script');
				s.async = true;
				s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(config.gaId);
				document.head.appendChild(s);
				window.dataLayer = window.dataLayer || [];
				window.gtag = function () { window.dataLayer.push(arguments); };
				window.gtag('js', new Date());
				window.gtag('config', config.gaId);
			} else if (prev && !now) {
				// Revoke: best-effort cookie cleanup, then reload so the page
				// re-renders without the gtag snippet.
				this.dropAnalyticsCookies();
				if (typeof window.gtag !== 'undefined') window.location.reload();
			}
		},

		// Best-effort removal of the Google Analytics cookies (_ga, _ga_*).
		dropAnalyticsCookies() {
			const names = document.cookie.split('; ')
				.map((c) => c.split('=')[0])
				.filter((n) => /^_ga/.test(n));
			names.forEach((name) => {
				['', location.hostname, '.' + location.hostname].forEach((domain) => {
					document.cookie = name + '=; max-age=0; path=/' + (domain ? '; domain=' + domain : '');
				});
			});
		},
	};
}

import './bootstrap';
import Alpine from 'alpinejs';
import cookieConsent, { readConsent } from './cookie-consent';
import { initAuthForms, registerWizard } from './auth-forms';

window.Alpine = Alpine;
Alpine.data('cookieConsent', cookieConsent);
Alpine.data('registerWizard', registerWizard);

/**
 * Global consent check for future scripts (e.g. embedded maps, pixels).
 *
 * @param {string} category - necessary|preferences|statistics|marketing
 * @returns {boolean}
 * @example if (window.cookieConsentAllows('marketing')) { loadPixel(); }
 */
window.cookieConsentAllows = (category) => {
	if (category === 'necessary') return true;
	const rec = readConsent('cp-cookie-consent-v1');
	return !!(rec && rec.choices && rec.choices[category]);
};

/**
 * Copy text to the clipboard, with a legacy fallback for non-secure (HTTP)
 * contexts where navigator.clipboard is unavailable. Resolves true on success,
 * false otherwise - callers drive their own UI feedback off the result (see the
 * bank-transfer modal copy buttons).
 *
 * @param {string} text - the value to copy
 * @returns {Promise<boolean>}
 * @example window.ptCopy('HU42 1177 ...').then(ok => { if (ok) flash(); })
 */
window.ptCopy = async (text) => {
	const value = String(text ?? '');

	if (navigator.clipboard && window.isSecureContext) {
		try {
			await navigator.clipboard.writeText(value);
			return true;
		} catch (e) {
			// fall through to the legacy textarea path
		}
	}

	try {
		const ta = document.createElement('textarea');
		ta.value = value;
		ta.setAttribute('readonly', '');
		ta.style.position = 'fixed';
		ta.style.top = '-9999px';
		document.body.appendChild(ta);
		ta.select();
		const ok = document.execCommand('copy');
		document.body.removeChild(ta);
		return ok;
	} catch (e) {
		return false;
	}
};

Alpine.start();

// Bind the AJAX auth forms (login / forgot / reset).
if (document.readyState !== 'loading') {
	initAuthForms();
} else {
	window.addEventListener('DOMContentLoaded', initAuthForms);
}

import './bootstrap';
import Alpine from 'alpinejs';
import cookieConsent, { readConsent } from './cookie-consent';

window.Alpine = Alpine;
Alpine.data('cookieConsent', cookieConsent);

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

Alpine.start();

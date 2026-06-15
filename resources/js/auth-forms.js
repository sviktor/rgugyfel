/**
 * Portal auth forms - AJAX submit with ptAlert feedback (port of rgsite's
 * contact-form.js to the portal's window.ptAlert lightbox).
 *
 * Any `form[data-auth-form]` (login, forgot, reset) submits via fetch:
 *   - 200 + {redirect}        -> navigate there (login/register/forgot/reset all
 *                                hand back a redirect target).
 *   - 200 (no redirect)       -> success ptAlert.
 *   - 422 {title?, message?}  -> error ptAlert (a single message), OR
 *   - 422 {errors:{...}}      -> error ptAlert listing every message (one per
 *                                line; .pt-alert p has white-space: pre-line).
 *
 * The register wizard is NOT a data-auth-form (it manages its own steps), so it
 * calls window.authSubmit(form, button) once the final step validates.
 *
 * @example initAuthForms(); // called once from app.js
 */

/**
 * Submit a form via fetch and report the result in the ptAlert lightbox.
 *
 * @param {HTMLFormElement} form
 * @param {HTMLButtonElement|null} btn  the trigger button to disable while busy
 * @example window.authSubmit(myForm, myButton);
 */
async function authSubmit(form, btn) {
	if (btn) btn.disabled = true;

	try {
		const resp = await fetch(form.action, {
			method: 'POST',
			headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
			body: new FormData(form), // includes the @csrf _token field
		});
		const data = await resp.json().catch(() => null);

		if (resp.ok) {
			if (data && data.redirect) {
				window.location.assign(data.redirect);
				return;
			}
			window.ptAlert({
				variant: 'success',
				title: (data && data.title) || 'Kész',
				message: (data && data.message) || '',
			});
			form.reset();
			if (window.grecaptcha) window.grecaptcha.reset();
			return;
		}

		let message = 'Kérjük, ellenőrizze a megadott adatokat.';
		if (data && data.message) {
			message = data.message;
		} else if (data && data.errors) {
			const list = Object.values(data.errors).flat();
			if (list.length) message = list.join('\n');
		}
		window.ptAlert({
			variant: 'error',
			title: (data && data.title) || 'Hiányzó vagy hibás adatok',
			message,
		});
		if (window.grecaptcha) window.grecaptcha.reset();
	} catch {
		window.ptAlert({
			variant: 'error',
			title: 'Hiba történt',
			message: 'A művelet nem sikerült. Kérjük, próbálja meg később újra.',
		});
	} finally {
		if (btn) btn.disabled = false;
	}
}

// Expose for the register wizard (Alpine component below).
window.authSubmit = authSubmit;

export function initAuthForms() {
	document.querySelectorAll('form[data-auth-form]').forEach((form) => {
		form.addEventListener('submit', (e) => {
			e.preventDefault();
			authSubmit(form, form.querySelector('button[type="submit"]'));
		});
	});
}

/**
 * Register wizard - the Alpine data factory for the 3-step registration form.
 * Per-step client validation mirrors RegisterController; messages surface in the
 * ptAlert lightbox. The final step hands off to window.authSubmit.
 *
 * @example Alpine.data('registerWizard', registerWizard);
 */
export function registerWizard() {
	return {
		step: 1,

		emailValid(s) {
			return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test((s || '').trim());
		},

		/** @returns {string|null} the first error message for the step, or null */
		validateStep(s) {
			const el = this.$root.elements;
			const val = (n) => (el[n] && el[n].value ? el[n].value.trim() : '');

			if (s === 1) {
				if (!val('lastName') || !val('firstName')) return 'Kérjük, adja meg vezeték- és keresztnevét.';
				if (!this.emailValid(val('email'))) return 'Kérjük, adjon meg egy érvényes e-mail címet (pl. nev@example.hu).';
				if (!val('phone')) return 'Kérjük, adja meg telefonszámát.';
			}
			if (s === 2) {
				const hasId = val('contract_number');
				const hasAddr = val('zip') && val('city') && val('street');
				if (!hasId && !hasAddr) return 'Az azonosításhoz adja meg szerződésszámát és születési dátumát, vagy töltse ki a teljes lakcímét (irányítószám, település, utca).';
				if (!hasAddr && !val('birth_date')) return 'A szerződésszámmal történő azonosításhoz adja meg születési dátumát is (vagy töltse ki a teljes lakcímét).';
			}
			if (s === 3) {
				const pw = (el['password'] && el['password'].value) || '';
				if (pw.length < 10 || !/[A-Z]/.test(pw) || !/[0-9]/.test(pw)) return 'A jelszónak legalább 10 karakter hosszúnak kell lennie, és tartalmaznia kell nagybetűt és számot is.';
				if (pw !== ((el['password_confirmation'] && el['password_confirmation'].value) || '')) return 'A két megadott jelszó nem egyezik.';
				if (!(el['accept'] && el['accept'].checked)) return 'A regisztráció lezárásához el kell fogadnia az Általános Szerződési Feltételeket és az Adatvédelmi Tájékoztatót.';
			}
			return null;
		},

		next() {
			const err = this.validateStep(this.step);
			if (err) {
				window.ptAlert({ variant: 'error', title: 'Hiányzó vagy hibás adat', message: err });
				return;
			}
			if (this.step < 3) this.step++;
		},

		prev() {
			if (this.step > 1) this.step--;
		},

		/** Form submit handler: advance until the last step, then send. */
		onFormSubmit(e) {
			if (this.step < 3) {
				this.next();
				return;
			}
			const err = this.validateStep(3);
			if (err) {
				window.ptAlert({ variant: 'error', title: 'Hiányzó vagy hibás adat', message: err });
				return;
			}
			window.authSubmit(this.$root, e.submitter || null);
		},
	};
}

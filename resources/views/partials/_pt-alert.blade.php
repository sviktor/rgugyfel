{{--
	Portal lightbox alert (toast) - vanilla port of portal-alert.jsx.
	Exposes window.ptAlert({ variant: 'success'|'error'|'info', title, message, actionLabel }).
	Styled by design/portal.css (.pt-alert*). Included in both portal + auth layouts.
--}}
<script>
(function () {
	if (window.ptAlert) return;
	var VARIANTS = {
		success: { icon: 'check', label: 'Sikeres művelet' },
		error:   { icon: 'alert-triangle', label: 'Hiba történt' },
		info:    { icon: 'info', label: 'Tájékoztatás' }
	};
	function dismiss(backdrop) {
		backdrop.classList.add('is-leaving');
		document.removeEventListener('keydown', backdrop._onKey);
		setTimeout(function () { backdrop.remove(); }, 180);
	}
	window.ptAlert = function (opts) {
		opts = opts || {};
		var variant = VARIANTS[opts.variant] ? opts.variant : 'info';
		var v = VARIANTS[variant];
		document.querySelectorAll('.pt-alert-backdrop').forEach(function (b) { b.remove(); });

		var bd = document.createElement('div');
		bd.className = 'pt-alert-backdrop';
		var modal = document.createElement('div');
		modal.className = 'pt-alert pt-alert-' + variant;
		modal.setAttribute('role', 'alertdialog');
		modal.setAttribute('aria-modal', 'true');
		modal.innerHTML =
			'<span class="pt-alert-badge" aria-hidden="true"><i data-lucide="' + v.icon + '"></i></span>' +
			'<h2></h2>' +
			'<div class="pt-alert-orn" aria-hidden="true"><span>✦</span></div>' +
			(opts.message ? '<p></p>' : '') +
			'<button type="button" class="rt-btn rt-btn-primary"></button>';
		modal.querySelector('h2').textContent = opts.title || v.label;
		if (opts.message) modal.querySelector('p').textContent = opts.message;
		var btn = modal.querySelector('button');
		btn.textContent = opts.actionLabel || 'Rendben';

		bd.appendChild(modal);
		bd.addEventListener('click', function (e) { if (e.target === bd) dismiss(bd); });
		btn.addEventListener('click', function () { dismiss(bd); });
		bd._onKey = function (e) { if (e.key === 'Escape') dismiss(bd); };
		document.addEventListener('keydown', bd._onKey);

		document.body.appendChild(bd);
		if (window.lucide) window.lucide.createIcons();
		btn.focus();
	};
})();
</script>

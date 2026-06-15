# rgugyfel - Royal Telecom Customer Portal

**Authenticated customer area** for Royal Telecom subscribers. Lets customers see their account, invoices, payment status, support tickets.

Part of the [Royal Group project family](../CLAUDE.md). Siblings:
- [rgsite](../rgsite/CLAUDE.md) - Royal Group main site
- [rgtelekom](../rgtelekom/CLAUDE.md) - telecom public site (the "front door"; non-logged-in users land there)
- [rgadmin](../rgadmin/CLAUDE.md) - admins manage these customers & invoices here

## Purpose

- Customer registration + login (separate `cus_users` accounts linked to the shared `customers`).
- Dashboard: current package, next invoice, balance.
- Invoice history + PDF download.
- Payment status / outstanding balance (data from `ad_invoices`, `ad_payments`).
- Support tickets / messages (writes to `cp_tickets`).
- Profile edit (limited - most fields read-only, billing changes go through admin).

## Stack

- **Laravel 11** + Blade
- **Tailwind CSS 3** + Vite
- Alpine.js for light interactivity (modals, dropdowns)
- PHP 8.4+
- Laravel auth - a `customer` guard against the rgugyfel-owned `cus_users` accounts (linked to the shared `customers`)

## Database

- DB: `mc_rg` (shared)
- **No global table prefix** - see the [table naming policy in the root CLAUDE.md](../CLAUDE.md#table-naming-policy--no-automatic-prefix).
- Owns (project-private, prefixed to avoid collision):
  - the **`cus_*` auth cluster** (BUILT): `cus_users` (portal login accounts), `cus_password_reset_tokens`, `cus_login_attempts`, `cus_contract_requests`. Migrations live here, tracked in `cp_migrations`.
  - `cp_tickets`, `cp_ticket_messages` (tickets phase - not built yet). NOTE: the once-planned `cp_login_attempts` is superseded by `cus_login_attempts`.
- Reads (shared business tables, owned by `rgadmin`, no prefix):
  - `customers` (the CRM party a `cus_users` account links to once approved)
  - `subscriptions`, `products`
  - `invoices`, `payments`
  - `email_templates` (the verify/reset mail templates - see Auth setup)
- Laravel infra: `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` (no DB tables).

### Auth setup (BUILT - 2026-06-15)

The portal authenticates against a **separate `cus_users` table** (rgugyfel-owned), NOT the shared `customers` (owned by rgadmin). A `cus_users` row is created by self-registration and is linked to a `customers` row (`customers_id`) only once a staff member approves the customer's contract request - a deliberate split (a login account is not the CRM entity).

- `config/auth.php`: provider `customers` -> `App\Models\CustomerUser` (`cus_users`); guard `customer` (session); password broker `customers` (`cus_password_reset_tokens`, 30-min expiry). Defaults are **hardcoded** to `customer`/`customers` (no `env()`) so a stale `AUTH_GUARD`/`AUTH_MODEL` can't break auth.
- `App\Models\CustomerUser extends Illuminate\Foundation\Auth\User` (so `CanResetPassword` comes for free); `password` is the `hashed` cast (store PLAIN, never `Hash::make` it again). Helpers: `isLocked()`, `hasVerifiedEmail()`, `markEmailVerified()`.
- **Registration = staff approval** (no automatic DB match): `RegisterController::store` creates the (unverified) `cus_users` row + a PENDING `cus_contract_requests` row, then sends the e-mail verification link. Step 2 identification = **either** szerződésszám (`SV00-00000`) **+ birth date**, **or** a full address (zip+city+street); the **birth date is required ONLY on the contract-number path** (a complete address needs none). The "SV..." legacy rental contract number is not stored in `leases`, so it can't be auto-matched. The rgadmin approval screen that sets `customers_id` is a **follow-up, not built**.
- **Legal pages** (BUILT): `/aszf` (`terms`) + `/adatvedelem` (`privacy`) -> `LegalController`, public, CMS-driven from the portal's own `web_sections` (`legal.terms`/`legal.privacy`) exactly like rgsite/rgtelekom, rendered through a slim guest `layouts/page.blade.php` (brand bar + content + footer; the `.p-page-*` / `.p-legal-*` styles live in `style.css`). Until the rgadmin "Ügyfélkapu -> Jogi oldalak" CMS editor exists (**follow-up**) the page shows a placeholder instead of a 404, so the registration links are always real. The register step-3 ÁSZF/Adatvédelem checkbox links to these (`target="_blank"`).
- **E-mail verification**: a 24h `URL::temporarySignedRoute('verification.verify', ...)` link (hash = `sha1(email)`); `EmailVerificationController::verify` checks the signature MANUALLY (friendly result page, not a bare 403). Login is blocked until verified (it resends + routes to the notice page).
- **Brute-force lockout**: 5 failed logins within 60 min -> `cus_users.locked_until = +1 day` (counter in `cus_login_attempts`, cleared on success). The lockout modal deliberately tells the user "próbálja meg **2 nap múlva**" (real ban 1 day - intentional).
- **Remember-me**: `Auth::guard('customer')->setRememberDuration(60*24*180)` before login -> 180-day cookie.
- **reCAPTCHA v2** on the registration form only (`config/recaptcha.php`, `App\Support\Recaptcha`, keys copied from rgsite/rgtelekom; off until `RECAPTCHA_*` env set).
- **Mail**: verify/reset use the shared rgadmin `email_templates` (aliases `customer_verify_email`, `customer_password_reset`) read via `App\Models\EmailTemplate::useTemplate()` (resilient: missing table/row -> built-in Hungarian fallback) and sent by `App\Support\CustomerMail::send()` (try/catch, like `ExceptionMailer`). Mailpit in dev (`MAIL_*` already set).
- **AJAX + ptAlert**: login/forgot/reset are `data-auth-form` AJAX forms; register is an Alpine 3-step wizard (`registerWizard` in `resources/js/auth-forms.js`). All errors/messages surface in the `window.ptAlert` lightbox; cross-redirect messages ride a `session('pt_alert')` -> ptAlert bridge added to BOTH layouts. JSON contract: success `{redirect}`, failure 422 `{title?, message?}` or `{errors:{...}}`.
- Legacy `herminatelekom` used MD5 + a hardcoded bypass - **not carried over** (bcrypt via the cast).

## Routes (current - full design port, visual mockup)

| Route | Method | Controller | Purpose |
|---|---|---|---|
| `/login` | GET/POST | `AuthController::showLogin/login` | Login (AJAX JSON; lockout + verify gates; 180-day remember) - `guest:customer` |
| `/regisztracio` | GET/POST | `RegisterController::show/store` | Alpine 3-step register wizard -> creates `cus_users` + pending `cus_contract_requests` + sends verify mail - `guest:customer` |
| `/regisztracio/megerosites` | GET | `EmailVerificationController::notice` | "Check your inbox" page (after register / unverified login) |
| `/email-megerosites/{id}/{hash}` | GET | `EmailVerificationController::verify` | Signed verify link -> stamps `email_verified_at` -> result page |
| `/email-megerosites/ujrakuldes` | POST | `EmailVerificationController::resend` | Re-send verify link (`throttle:6,1`) |
| `/elfelejtett-jelszo` | GET/POST | `PasswordResetController::requestForm/sendLink` | Reset request (anti-enumeration; broker token + custom mail) - `guest:customer` |
| `/jelszo-visszaallitas/{token}` | GET/POST | `PasswordResetController::resetForm/reset` | Set new password -> login - `guest:customer` |
| `/aszf` | GET | `LegalController::terms` | ÁSZF (public, CMS-driven via `layouts.page`) |
| `/adatvedelem` | GET | `LegalController::privacy` | Adatvédelmi Tájékoztató (public, CMS-driven) |
| `/logout` | POST | `AuthController::logout` | Logout → `/login` (`auth:customer`) |
| `/` | GET | `PortalController::dashboard` | Áttekintés - financial hero + bank-transfer modal, active ticket, quick actions, contracts list, add-contract request |
| `/szamlak` | GET | `PortalController::invoices` | Számláim - filter tabs + status-badged table + bank-transfer modal + PDF-like invoice preview (per invoice) |
| `/szerzodeseim` | GET | `PortalController::plans` | Szerződéseim - contract detail cards (loyalty bar) + available plans showcase + 3-step plan-switch wizard |
| `/forgalom` | GET | `PortalController::usage` | Forgalom & sebesség - "Hamarosan" stub (a stub in the design too) |
| `/hibabejelentes` | GET | `PortalController::tickets` | Hibabejelentés - open/closed tabs + ticket list + new-ticket modal + per-ticket detail thread |
| `/dokumentumok` | GET | `PortalController::docs` | Dokumentumok - category filters + document list + "how to use" help |
| `/profil` | GET | `PortalController::profile` | Profil & beállítások - 4 tabs (personal / address / security / notifications) + account meta |

The portal nav (sidebar) is the design's **grouped** menu: Áttekintés (Főoldal), Pénzügy (Számláim), Szolgáltatásaim (Szerződéseim / Forgalom), Ügyintézés (Hibabejelentés / Dokumentumok), Fiók (Profil). Nav items are real `<a>` links; the topbar carries a notifications bell-popover + a settings link to the profile. Modals / tabs / wizards run on Alpine (visual only); form submission / payment / plan-switch / ticket reply are the **programming phase**.

**Auth middleware is applied** (2026-06-15): the portal pages (`/`, `/szamlak`, ...) + `/logout` + the dashboard `contract.request` POST are behind `['auth:customer', 'customer.verified']`; the auth screens are behind `guest:customer`. `bootstrap/app.php` sets `redirectGuestsTo(login)` + `redirectUsersTo('/')` and registers the `customer.verified` alias (`EnsureCustomerEmailVerified`).

## CSS structure

Split into focused files so the auth screens, portal chrome and global helpers stay readable. `app.css` is the entry that `@import`s the others in order:

| File | Owns |
|---|---|
| `resources/css/app.css` | Entry - `@import`s the design bundle, then `style.css`, `auth.css`, `portal-ui.css`, then Tailwind directives. |
| `resources/css/design/colors_and_type.css`, `kit.css`, `portal.css` | **Verbatim** Claude Design handoff - never edit. `portal.css` owns the whole `.p-*` portal/auth system (sidebar, topbar, cards, badges, hero-fin, bank popover, invoice doc, plan-switch, tickets, profile, docs) + the `.pt-alert*` toast. Source: the final handoff bundle `w:\sv\rg\_design\royal-telecom-sites\project\` (HTML/JSX prototypes - `portal-*.jsx` + `portal.css`). |
| `resources/css/style.css` | Global helpers - `overflow-x: hidden`, `[data-lucide]` size classes, `[x-cloak]`, **+ design-token gap fills**: the handoff's `portal.css` references `--rt-font-body` and `--rt-gold-400`, which `colors_and_type.css` never defines; we bridge them at `:root` (font-body → font-sans, gold-400 → a light gold) so ~30 rules don't fall back to inherited font / cream-instead-of-gold. |
| `resources/css/auth.css` | Auth screens - port helpers for `.p-auth-form`, register wizard progress, forgot "sent" state. |
| `resources/css/portal-ui.css` | Port helpers - the bits that were **inline styles** in the JSX prototypes (the design's components live in `portal.css`; we only add classes for the inline padding/width/etc.). Also: the **`<a>` nav mirror** (the design styles `.p-sidebar nav button` / `.p-quick button`, we render `<a>` links, so the button rules are mirrored onto `a`), the **fixed-sidebar app-shell** (desktop: shell `height:100vh; overflow:hidden`, only `.p-main` scrolls - sticky was unreliable under `overflow-x:hidden`), and CSS-var driven data widths (loyalty bar, password-strength meter). |

**Crest on the dark panels** uses `crest-monogram-light.svg` (the cream/gold variant) rendered as an `<img>` via `asset()` (Vite can't resolve the design's relative `url(assets/...)` through the `@import`); the auth `.crest-bg` watermark + sidebar logo both use it.

**Porting rule:** the design CSS targets `<button>` for in-app actions; where we use real `<a>` links (sidebar nav, quick actions) the button rules must be mirrored onto `a` in `portal-ui.css` (keep in sync with `portal.css`). Two design-CSS quirks are deliberately worked around: the `.p-switch` toggle class (44×24px) is **not** put on the plan-switch modal (it would shrink it), and the two undefined tokens above are gap-filled.

## Layouts

- `resources/views/layouts/auth.blade.php` - split-screen (dark navy intro side with the light crest + form panel). Used by login, register, forgot.
- `resources/views/layouts/portal.blade.php` - sidebar + topbar + main. Mobile sidebar drawer toggled by Alpine `x-data="{ navOpen: false }"`; on desktop the shell is fixed and only `.p-main` scrolls. Used by every portal page.

Both layouts load Lucide via CDN with a `MutationObserver` for Alpine-injected nodes (same pattern as rgsite/rgtelekom), and both include `partials/_pt-alert.blade.php` - a vanilla `window.ptAlert({ variant, title, message })` lightbox toast (port of `portal-alert.jsx`, styled by `portal.css` `.pt-alert*`) used by the profile saves (and ready for auth validation).

Shared partials: `partials/_bank-modal.blade.php` (the bank-transfer card, used by the dashboard + invoices; expects `$bank` + Alpine `bank`/`bankAmount`/`bankRef`).

## Design scope / wiring TODO

The **full** portal design is ported (login + register + forgot, dashboard, invoices, plans, tickets, documents, profile; `forgalom` stays a stub as in the design). It is **visual only** - the data comes from `app/Support/PortalMockData.php` (a faithful mirror of the handoff's `portal-shared.jsx` + the notifications/docs data, with `huf()` / `date()` / `daysUntil()` view helpers - a single file, easy to delete once the real models exist). The interactive flows run on Alpine but do **not** persist; wiring them is the programming phase:

- **Customer auth: DONE** (2026-06-15) - register / login / forgot / e-mail verify / password reset / lockout / 180-day remember / reCAPTCHA, all against `cus_users` (see Auth setup above). The dashboard "Szerződés hozzárendelése" form now files a pending `cus_contract_requests`. STILL TODO: (1) the **rgadmin approval screen** for `cus_contract_requests` (sets `customers_id` on the request + the `cus_users` row) - until then approved links never form; (2) binding the portal pages (dashboard/invoices/...) to the **real logged-in customer** instead of `PortalMockData`; (3) the profile-save backend (still just pops the toast). Tests: `tests/Unit/CustomerUserTest` + `tests/Feature/{Registration,EmailVerification,Login,PasswordReset,Legal}Test`, run against the dedicated MariaDB **`mc_rg_test`** (forced + guarded in phpunit.xml + `tests/TestCase.php`, the same mc_rg_test rgadmin uses; the host PHP has no sqlite driver). `App\Support\SiteContent` + `App\Models\EmailTemplate` degrade gracefully when their shared tables (`web_sections` / `email_templates`) are absent from the test DB.
- **Invoices**: needs `invoices` + `payments` tables (rgadmin). The bank-transfer "Kifizetem", PDF download/print, and search are visual.
- **Subscriptions / plans**: needs `subscriptions` + `products` tables (rgadmin). The plan-switch wizard does not submit.
- **Tickets**: needs `cp_tickets` + `cp_ticket_messages` (this project's migrations). The new-ticket form + thread reply do not submit.
- **Documents / notifications**: static lists for now; the downloads + notification read-state need a backend.

## Local dev

- Suggested hostname: `ugyfel.royaltelekom.test`

## Coding & access rules

See [root CLAUDE.md](../CLAUDE.md). Same conventions as siblings.

- Local Windows lint: `f:\xampp\php\php.exe`
- **No inline CSS in Blade** - write classes in `resources/css/style.css`. Only Alpine `:style="..."` reactive bindings and JS-toggled `style="display:none"` flags are exempt (see root CLAUDE.md).
- **No inline images** - files go to `public/assets/` and are referenced by URL.

## CLAUDE.md & skills

If, during development, you think something is worth adding to this CLAUDE.md (or the root one) for more efficient work, propose it in the summary (chat language: Hungarian) and write the addition in English.

If you think something should be added to the skills for more efficient work, propose it in the summary (chat language: Hungarian) and write the skill content in English.

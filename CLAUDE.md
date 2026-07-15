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

- **Laravel 12** + Blade
- **Tailwind CSS 3** + Vite
- Alpine.js for light interactivity (modals, dropdowns)
- PHP 8.4+
- Laravel auth - a `customer` guard against the rgugyfel-owned `cus_users` accounts (linked to the shared `customers`)

## Database

- DB: `mc_rg` (shared)
- **No global table prefix** - see the [table naming policy in the root CLAUDE.md](../CLAUDE.md#table-naming-policy--no-automatic-prefix).
- Owns (project-private, prefixed to avoid collision):
  - the **`cus_*` auth cluster** (BUILT): `cus_users` (portal login accounts), `cus_password_reset_tokens`, `cus_login_attempts`, `cus_contract_requests`, `cus_users_customers` (the account <-> CRM-customer PIVOT: 1 account -> N customers, link order = pivot id = approval order; written by rgadmin's approval, rgadmin carries a guarded create-if-absent twin migration). Migrations live here, tracked in `cp_migrations`. The old single `cus_users.customers_id` column is RETIRED (backfilled into the pivot; nothing reads or writes it - physical drop is a later migration). A second, `_test`-guarded migration creates minimal MIRRORS of the shared read tables (customers, invoices, payments, subscriptions) ONLY in `mc_rg_cp_test`, so linked-account feature tests can run (keep test `subscriptions` rows empty - the eager-load chain is not mirrored).
  - `cp_tickets`, `cp_ticket_messages` (tickets phase - not built yet). NOTE: the once-planned `cp_login_attempts` is superseded by `cus_login_attempts`.
- Reads (shared business tables, owned by `rgadmin`, no prefix):
  - `customers` (the CRM party a `cus_users` account links to once approved)
  - `subscriptions`, `subscriptions_products`, `products` (the customer's contracts + packages)
  - `prop_properties`, `prop_locations` (a contract's unit + its address label)
  - `product_listings` (the published package cards - the Szerződéseim "Elérhető csomagjaink")
  - `invoices`, `payments` (invoice list/detail + the **settlement-derived** paid status)
  - `email_templates` (the verify/reset mail templates - see Auth setup)
- Laravel infra: `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` (no DB tables).

### Auth setup (BUILT - 2026-06-15)

The portal authenticates against a **separate `cus_users` table** (rgugyfel-owned), NOT the shared `customers` (owned by rgadmin). A `cus_users` row is created by self-registration and is linked to `customers` rows through the **`cus_users_customers` pivot** - one link ADDED per staff-approved contract request, so **one account may hold N customers** (a relative's or a second property's service) and an approval never detaches an earlier link. The portal shows ONE customer at a time: `PortalController::currentCustomerId()` resolves the ACTIVE customer (the session's `active_customer_id` validated against `CustomerUser::linkedCustomerIds()`, default = first linked, 0 = unlinked -> welcome state), and multi-customer accounts get a **topbar customer switcher** (`partials/_customer-switch`, `POST /ugyfel-valtas`). The invoice PDF download ownership is the exception: ANY linked customer's invoice resolves (`WebInvoices::find(id, linkedIds)`), so a stale tab survives a switch.

- `config/auth.php`: provider `customers` -> `App\Models\CustomerUser` (`cus_users`); guard `customer` (session); password broker `customers` (`cus_password_reset_tokens`, 30-min expiry). Defaults are **hardcoded** to `customer`/`customers` (no `env()`) so a stale `AUTH_GUARD`/`AUTH_MODEL` can't break auth.
- `App\Models\CustomerUser extends Illuminate\Foundation\Auth\User` (so `CanResetPassword` comes for free); `password` is the `hashed` cast (store PLAIN, never `Hash::make` it again). Helpers: `isLocked()`, `hasVerifiedEmail()`, `markEmailVerified()`.
- **Registration = staff approval** (no automatic DB match): `RegisterController::store` creates the (unverified) `cus_users` row + ALWAYS a `cus_contract_requests` row (pending), then sends the e-mail verification link. **Step 2 identification (szerződésszám `SV00-00000` + birth date) is OPTIONAL** - the customer may register without it and supply it later in the portal; the request row is created regardless (it lists as **"Adatokra vár"** in rgadmin until it carries both a contract number + birth date, then **"Jóváhagyásra vár"**). Identification is **ONLY** contract number + birth date - the old address-based path was removed (no `zip`/`city`/`street`). The "Szerződés hozzárendelése" card (shared by the dashboard + Szerződéseim, `partials/_add-contract`) posts via the portal's generic AJAX mechanism (`data-auth-form` -> `auth-forms.js`): a validation error surfaces in the ptAlert lightbox in place, a success reloads the originating page so the `pt_alert` flash fires the confirmation modal. The card lists the account's PENDING requests (injected by an AppServiceProvider view composer) above the form. The form (`ContractRequestController::store`) decision order: a same-number pending row is UPDATED in place (no duplicate), a number-less pending row is COMPLETED, else a new request is added - a pending row carrying a DIFFERENT number is never overwritten. Registration also writes the birth date onto `cus_users.birth_date` (Profil + prefill). The "SV..." legacy rental contract number is not stored in `leases`, so it can't be auto-matched. **The rgadmin approval screen IS built** (rgadmin Telekom -> Ügyfélkapu) - it ADDS a `cus_users_customers` pivot link + backfills `subscriptions.contract_number` + auto-closes the account's same-number duplicate requests + e-mails the customer.
- **Legal pages** (BUILT): `/aszf` (`terms`) + `/adatvedelem` (`privacy`) -> `LegalController`, public, CMS-driven from the portal's own `web_sections` (`legal.terms`/`legal.privacy`) exactly like rgsite/rgtelekom, rendered through a slim guest `layouts/page.blade.php` (brand bar + content + footer; the `.p-page-*` / `.p-legal-*` styles live in `style.css`). Until the rgadmin "Ügyfélkapu -> Jogi oldalak" CMS editor exists (**follow-up**) the page shows a placeholder instead of a 404, so the registration links are always real. The register step-3 ÁSZF/Adatvédelem checkbox links to these (`target="_blank"`).
- **E-mail verification**: a 24h `URL::temporarySignedRoute('verification.verify', ...)` link (hash = `sha1(email)`); `EmailVerificationController::verify` checks the signature MANUALLY (friendly result page, not a bare 403). Login is blocked until verified (it resends + routes to the notice page).
- **Brute-force lockout**: 5 failed logins within 60 min -> `cus_users.locked_until = +1 day` (counter in `cus_login_attempts`, cleared on success). The lockout modal deliberately tells the user "próbálja meg **2 nap múlva**" (real ban 1 day - intentional).
- **Rate limiting + disabled-account gate** (2026-07-03): the public auth POSTs carry inline `throttle` middleware IN ADDITION to the per-account lockout (which skips non-existent accounts, so the throttle is what caps password-spraying) - `login.submit` `throttle:10,1`, `register.submit` `throttle:6,10`, `forgot.submit` `throttle:5,10`. The forgot throttle is also the reset-mail-bombing cap (`sendLink()` calls `createToken()` directly, bypassing the broker's own throttle - deliberate, the route throttle covers it). The login path additionally gates on `cus_users.status = 1` - checked AFTER a valid password so a wrong guess cannot probe whether an account exists or is disabled; a profile password change rotates the session id (`session()->regenerate()`). **Rule: any new public POST route gets a `throttle`.** Tests: `tests/Feature/LoginSecurityTest`.
- **Remember-me**: `Auth::guard('customer')->setRememberDuration(60*24*180)` before login -> 180-day cookie.
- **reCAPTCHA v2** on the registration form only (`config/recaptcha.php`, `App\Support\Recaptcha`, keys copied from rgsite/rgtelekom; off until `RECAPTCHA_*` env set).
- **Mail**: verify/reset use the shared rgadmin `email_templates` (aliases `customer_verify_email`, `customer_password_reset`) read via `App\Models\EmailTemplate::useTemplate()` (resilient: missing table/row -> built-in Hungarian fallback) and sent by `App\Support\CustomerMail::send()` (try/catch, like `ExceptionMailer`). Mailpit in dev (`MAIL_*` already set).
- **AJAX + ptAlert**: login/forgot/reset are `data-auth-form` AJAX forms; register is an Alpine 3-step wizard (`registerWizard` in `resources/js/auth-forms.js`). All errors/messages surface in the `window.ptAlert` lightbox; cross-redirect messages ride a `session('pt_alert')` -> ptAlert bridge added to BOTH layouts. JSON contract: success `{redirect}`, failure 422 `{title?, message?}` or `{errors:{...}}`.
- Legacy `herminatelekom` used MD5 + a hardcoded bypass - **not carried over** (bcrypt via the cast).

## Routes (current)

| Route | Method | Controller | Purpose |
|---|---|---|---|
| `/login` | GET/POST | `AuthController::showLogin/login` | Login (AJAX JSON; `throttle:10,1` + lockout + status + verify gates; 180-day remember) - `guest:customer` |
| `/regisztracio` | GET/POST | `RegisterController::show/store` | Alpine 3-step register wizard (`throttle:6,10`) -> creates `cus_users` + pending `cus_contract_requests` + sends verify mail - `guest:customer` |
| `/regisztracio/megerosites` | GET | `EmailVerificationController::notice` | "Check your inbox" page (after register / unverified login) |
| `/email-megerosites/{id}/{hash}` | GET | `EmailVerificationController::verify` | Signed verify link -> stamps `email_verified_at` -> result page |
| `/email-megerosites/ujrakuldes` | POST | `EmailVerificationController::resend` | Re-send verify link (`throttle:6,1`) |
| `/elfelejtett-jelszo` | GET/POST | `PasswordResetController::requestForm/sendLink` | Reset request (`throttle:5,10`; anti-enumeration; broker token + custom mail) - `guest:customer` |
| `/jelszo-visszaallitas/{token}` | GET/POST | `PasswordResetController::resetForm/reset` | Set new password -> login - `guest:customer` |
| `/aszf` | GET | `LegalController::terms` | ÁSZF (public, CMS-driven via `layouts.page`) |
| `/adatvedelem` | GET | `LegalController::privacy` | Adatvédelmi Tájékoztató (public, CMS-driven) |
| `/logout` | POST | `AuthController::logout` | Logout → `/login` (`auth:customer`) |
| `/` | GET | `PortalController::dashboard` | Áttekintés - financial hero + bank-transfer modal, quick actions, contracts list, add-contract request |
| `/szamlak` | GET | `PortalController::invoices` | Számláim - filter tabs + status-badged table + bank-transfer modal + real PDF-like invoice preview (per invoice). Redirects to `/` while unlinked. |
| `/szamlak/{id}/letoltes` | GET | `PortalController::invoiceDownload` | Gated invoice PDF download (ANY linked customer's own invoice - stale tabs survive a switch; private `invoices` disk) |
| `/szerzodeseim` | GET | `PortalController::plans` | Szerződéseim - the customer's subscription contract cards (loyalty bar) + the available-plans showcase (from `product_listings`); NO plan switching. Redirects to `/` while unlinked. |
| `/dokumentumok` | GET | `PortalController::docs` | Dokumentumok - CMS intro + help cards + the REAL public library list (`App\Support\WebDocuments`, shared `documents` table) |
| `/dokumentumok/{id}/letoltes` | GET | `PortalController::docDownload` | Gated library download (`WebDocuments::downloadable()` 404s any non-library id; private `documents` disk) |
| `/profil` | GET | `PortalController::profile` | Profil & beállítások - 3 tabs (personal / security / notifications), the active tab in `?tab=`, + account meta |
| `/profil/szemelyes` | POST | `PortalController::profilePersonalSave` | Save name / phone / birth_date (email read-only, never saved) - real POST + redirect |
| `/profil/jelszo` | POST | `PortalController::profilePasswordSave` | Change password (current-password `Hash::check`; native submit so the browser offers to save) |
| `/profil/ertesitesek` | POST | `PortalController::notificationsSave` | Save the notification toggles to `cus_users.settings` |
| `/ugyfel-valtas` | POST | `PortalController::switchCustomer` | Topbar customer switcher - stores the ACTIVE customer id in the session (403 on a non-linked id) |

The portal nav (sidebar) is the design's **grouped** menu: Áttekintés (Főoldal), Pénzügy (Számláim), Szolgáltatásaim (Szerződéseim), Ügyintézés (Dokumentumok), Fiók (Profil). **The Pénzügy + Szolgáltatásaim groups are HIDDEN until the account is linked to a `customers` row** (unlinked = only Főoldal / Dokumentumok / Profil + the add-contract card). Nav items are real `<a>` links; the topbar carries the customer switcher (multi-customer accounts only, `partials/_customer-switch` - the FIRST child of the actions row), a notifications bell-popover (empty until the feature lands) + a settings link to the profile.

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

The full portal design is ported AND **the customer-facing pages are now bound to the logged-in customer's REAL data** (DONE items below). `app/Support/PortalMockData.php` was stripped to just the `huf()` / `date()` / `daysUntil()` / `daysAgo()` view FORMATTERS - the mock DATA is gone. **Linkage gating:** an account NOT yet linked to a `customers` row shows NO customer data - the dashboard renders only the "Szerződés hozzárendelése" card, the **Számláim + Szerződéseim nav groups are hidden**, and `invoices()`/`plans()` redirect to `/`. The remaining wiring is noted at the end.

- **Customer auth: DONE** (2026-06-15) - register / login / forgot / e-mail verify / password reset / lockout / 180-day remember / reCAPTCHA, all against `cus_users` (see Auth setup above). The **rgadmin approval screen IS built** (rgadmin Telekom -> Ügyfélkapu). Tests: `tests/Unit/{CustomerUserTest,ContractRequestTest,WebInvoicesParseTest}` + `tests/Feature/{Registration,EmailVerification,Login,LoginSecurity,PasswordReset,Legal,ContractRequest,Docs}Test`, run against a **dedicated** MariaDB **`mc_rg_cp_test`** (forced + guarded in phpunit.xml + `tests/TestCase.php`; SEPARATE from rgadmin's `mc_rg_test` so the two suites' migrate:fresh runs never collide on the shared cus_* tables; the host PHP has no sqlite driver). `App\Support\SiteContent` + `App\Models\EmailTemplate` degrade gracefully when their shared tables are absent from the test DB. NOTE: since the multi-customer round the `_test`-guarded mirror migration builds MINIMAL `customers`/`invoices`/`payments`/`subscriptions` tables in `mc_rg_cp_test`, so linked-account feature tests CAN insert shared-table rows with `DB::table()` (keep test `subscriptions` empty - the eager-load chain is not mirrored; `/szerzodeseim` still needs `product_listings`, which is absent). Deep read-layer logic stays covered by pure-logic Unit tests (e.g. `WebInvoicesParseTest` via reflection).
- **Invoices: DONE** - `/szamlak` + the dashboard hero show the linked customer's invoices via `App\Support\WebInvoices::forCustomer($cid)` (+ a read-only `App\Models\Invoice`). **Paid status is settlement-derived, NOT `invoices.paid`** (rgadmin never maintains that column): a month is paid iff a `settlement` `payments` row exists for `(payable_type, payable_id, billing month = period_start)`; a `credit` row reduces the per-invoice `outstanding` (= gross - credit). The preview modal (`forCustomer($cid, true)`) renders the **real invoice document** - line items + buyer + seller + net/vat - parsed from the stored **`xml_data` JSON** (a single `<tetel>` flattens to an assoc array, many to a 0-indexed list) + the normalized columns; the seller is the BILLING ENTITY from `xml_data.fejlec.elado` (not the portal company), the line name is the full original `termeknev` (2+ spaces -> line breaks). **The read-only `Invoice` model MUST cast `xml_data`=>`array`** or `(array)$jsonString` silently breaks the parse. PDF via the gated `/szamlak/{id}/letoltes` (private `invoices` disk = `INVOICES_STORAGE_PATH`, add the disk block to `config/filesystems.php`).
- **Contracts (Szerződéseim): DONE** - `App\Support\WebContracts::forCustomer($cid)` maps the customer's active `subscriptions` (+ their `products`, `monthlyFee()`/`loyaltyExpiry()` mirrored from rgadmin, unit address from `prop_properties`/`prop_locations`) to the contract cards. **Available plans** = `App\Support\WebPackages::availablePlans()` from the published `product_listings` (the same source as rgtelekom `/csomagok`). The **plan-switch wizard + Csomagváltás buttons were REMOVED** (no switching on the portal - contact customer service).
- **Profile: DONE** - 3 tabs, each a REAL POST that redirects back to its `?tab=` with a `pt_alert` flash (so the browser's password manager can offer to save the new password): personal (name / phone / birth_date; **email read-only, never saved**), password (`Hash::check` current + min10/upper/number/confirmed), notifications (toggles -> `cus_users.settings` JSON). New `cus_users` columns **`birth_date`** + **`settings`** (rgugyfel migrations `2026_06_16_000010/000011`, mirrored in the rgadmin guarded `*_create_cus_portal_tables_if_absent` create). A set birth_date prefills the add-contract form (AppServiceProvider composer -> `$accountBirthDate`). The portal layout has BOTH a `pt_alert` flash bridge AND an `$errors`->ptAlert bridge (for the full-page POST validation).
- **New read models** (read-only, no write trait): `Subscription` / `Product` / `Property` / `Location` / `Payment` / `ProductListing`. **New read layers:** `WebContracts`, `WebPackages` (+ the expanded `WebInvoices`).
- **Documents: DONE** (2026-06-16) - the `/dokumentumok` list is the REAL shared public library (`App\Support\WebDocuments` over the `documents` table, the rgtelekom pattern; binding `web_documents/portal/documents`, uploaded via the rgadmin web editor's portal Dokumentumok page) + the gated `docs.download` route (404s any non-library id; private `documents` disk = `SHARED_DOCUMENTS_PATH`). `WebDocuments` try/catch-degrades to an empty library when the shared table is absent (test DB). Per-customer PRIVATE documents = a future round.
- **Removed / still TODO**: Forgalom (`/forgalom`) + Hibabejelentés (`/hibabejelentes`, tickets `cp_tickets`) were dropped from routes/views/nav; the topbar **notifications feed is empty** (no mock) until a real source; the bank-transfer "Kifizetem" stays visual (no payment gateway); the invoice "Nyomtatás" prints the open invoice via `window.print()` + an `@media print` block.

## Local dev

- Suggested hostname: `ugyfel.royaltelekom.test`

## Coding & access rules

See [root CLAUDE.md](../CLAUDE.md). Same conventions as siblings.

- Local Windows lint: `d:\xampp\php\php.exe`
- **No inline CSS in Blade** - write classes in `resources/css/style.css`. Only Alpine `:style="..."` reactive bindings and JS-toggled `style="display:none"` flags are exempt (see root CLAUDE.md).
- **No inline images** - files go to `public/assets/` and are referenced by URL.

## CLAUDE.md & skills

If, during development, you think something is worth adding to this CLAUDE.md (or the root one) for more efficient work, propose it in the summary (chat language: Hungarian) and write the addition in English.

If you think something should be added to the skills for more efficient work, propose it in the summary (chat language: Hungarian) and write the skill content in English.

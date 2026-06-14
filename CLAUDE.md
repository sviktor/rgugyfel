# rgugyfel - Royal Telecom Customer Portal

**Authenticated customer area** for Royal Telecom subscribers. Lets customers see their account, invoices, payment status, support tickets.

Part of the [Royal Group project family](../CLAUDE.md). Siblings:
- [rgsite](../rgsite/CLAUDE.md) - Royal Group main site
- [rgtelekom](../rgtelekom/CLAUDE.md) - telecom public site (the "front door"; non-logged-in users land there)
- [rgadmin](../rgadmin/CLAUDE.md) - admins manage these customers & invoices here

## Purpose

- Customer login (against `ad_customers`).
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
- Laravel built-in auth - guard configured against `ad_customers`

## Database

- DB: `mc_rg` (shared)
- **No global table prefix** - see the [table naming policy in the root CLAUDE.md](../CLAUDE.md#table-naming-policy--no-automatic-prefix).
- Owns (project-private, prefixed to avoid collision): `cp_tickets`, `cp_ticket_messages`, `cp_login_attempts`.
- Reads (shared business tables, owned by `rgadmin`, no prefix):
  - `customers` (auth source - see below)
  - `subscriptions`, `products`
  - `invoices`, `payments`
- Laravel infra: `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` (no DB tables).

### Auth setup

Custom Eloquent user provider pointing to `customers`:
- `config/auth.php` → `providers.customers.model = App\Models\Customer`
- `App\Models\Customer extends Authenticatable`, `protected $table = 'customers'`
- Password column TBD - coordinate with `rgadmin` migration (likely bcrypt `password` column added on `customers`).
- Legacy `herminatelekom` used MD5 + a hardcoded bypass - **do not carry that over**. Force a password reset flow on first login if importing old accounts.

## Routes (current - full design port, visual mockup)

| Route | Method | Controller | Purpose |
|---|---|---|---|
| `/login` | GET/POST | `AuthController::showLogin/login` | Login screen (fake-redirect → /) |
| `/regisztracio?step=1\|2\|3` | GET/POST | `AuthController::showRegister/register` | 3-step register wizard (SSR step state via `?step=`) |
| `/elfelejtett-jelszo` | GET/POST | `AuthController::showForgot/forgot` | Password reset request (session-flag for the "sent" state) |
| `/logout` | POST | `AuthController::logout` | Logout → `/login` |
| `/` | GET | `PortalController::dashboard` | Áttekintés - financial hero + bank-transfer modal, active ticket, quick actions, contracts list, add-contract request |
| `/szamlak` | GET | `PortalController::invoices` | Számláim - filter tabs + status-badged table + bank-transfer modal + PDF-like invoice preview (per invoice) |
| `/szerzodeseim` | GET | `PortalController::plans` | Szerződéseim - contract detail cards (loyalty bar) + available plans showcase + 3-step plan-switch wizard |
| `/forgalom` | GET | `PortalController::usage` | Forgalom & sebesség - "Hamarosan" stub (a stub in the design too) |
| `/hibabejelentes` | GET | `PortalController::tickets` | Hibabejelentés - open/closed tabs + ticket list + new-ticket modal + per-ticket detail thread |
| `/dokumentumok` | GET | `PortalController::docs` | Dokumentumok - category filters + document list + "how to use" help |
| `/profil` | GET | `PortalController::profile` | Profil & beállítások - 4 tabs (personal / address / security / notifications) + account meta |

The portal nav (sidebar) is the design's **grouped** menu: Áttekintés (Főoldal), Pénzügy (Számláim), Szolgáltatásaim (Szerződéseim / Forgalom), Ügyintézés (Hibabejelentés / Dokumentumok), Fiók (Profil). Nav items are real `<a>` links; the topbar carries a notifications bell-popover + a settings link to the profile. Modals / tabs / wizards run on Alpine (visual only); form submission / payment / plan-switch / ticket reply are the **programming phase**.

**No auth middleware yet** - every route is publicly reachable. Once the `customers` table lands (via rgadmin) and we wire `Auth::guard('customer')`, we'll add `auth:customer` to all the portal routes.

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

- **Customer auth + profile**: needs `customers` table (rgadmin migration). Then add `Auth::guard('customer')` + `App\Models\Customer extends Authenticatable` with `protected $table = 'customers'`. Login/register/forgot currently fake-succeed; the profile saves only pop the toast.
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

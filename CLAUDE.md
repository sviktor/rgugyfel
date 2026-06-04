# rgugyfel — Royal Telecom Customer Portal

**Authenticated customer area** for Royal Telecom subscribers. Lets customers see their account, invoices, payment status, support tickets.

Part of the [Royal Group project family](../CLAUDE.md). Siblings:
- [rgsite](../rgsite/CLAUDE.md) — Royal Group main site
- [rgtelekom](../rgtelekom/CLAUDE.md) — telecom public site (the "front door"; non-logged-in users land there)
- [rgadmin](../rgadmin/CLAUDE.md) — admins manage these customers & invoices here

## Purpose

- Customer login (against `ad_customers`).
- Dashboard: current package, next invoice, balance.
- Invoice history + PDF download.
- Payment status / outstanding balance (data from `ad_invoices`, `ad_payments`).
- Support tickets / messages (writes to `cp_tickets`).
- Profile edit (limited — most fields read-only, billing changes go through admin).

## Stack

- **Laravel 11** + Blade
- **Tailwind CSS 3** + Vite
- Alpine.js for light interactivity (modals, dropdowns)
- PHP 8.4+
- Laravel built-in auth — guard configured against `ad_customers`

## Database

- DB: `mc_rg` (shared)
- **No global table prefix** — see the [table naming policy in the root CLAUDE.md](../CLAUDE.md#table-naming-policy--no-automatic-prefix).
- Owns (project-private, prefixed to avoid collision): `cp_tickets`, `cp_ticket_messages`, `cp_login_attempts`.
- Reads (shared business tables, owned by `rgadmin`, no prefix):
  - `customers` (auth source — see below)
  - `subscriptions`, `products`
  - `invoices`, `payments`
- Laravel infra: `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` (no DB tables).

### Auth setup

Custom Eloquent user provider pointing to `customers`:
- `config/auth.php` → `providers.customers.model = App\Models\Customer`
- `App\Models\Customer extends Authenticatable`, `protected $table = 'customers'`
- Password column TBD — coordinate with `rgadmin` migration (likely bcrypt `password` column added on `customers`).
- Legacy `herminatelekom` used MD5 + a hardcoded bypass — **do not carry that over**. Force a password reset flow on first login if importing old accounts.

## Routes (current — MVP, visual mockup)

| Route | Method | Controller | Purpose |
|---|---|---|---|
| `/login` | GET/POST | `AuthController::showLogin/login` | Login screen (fake-redirect → /) |
| `/regisztracio?step=1\|2\|3` | GET/POST | `AuthController::showRegister/register` | 3-step register wizard (SSR step state via `?step=`) |
| `/elfelejtett-jelszo` | GET/POST | `AuthController::showForgot/forgot` | Password reset request (session-flag for the "sent" state) |
| `/logout` | POST | `AuthController::logout` | Logout → `/login` |
| `/` | GET | `PortalController::dashboard` | Dashboard (MVP: 3-card overview from mock data) |
| `/szamlak` | GET | `PortalController::invoices` | Stub — "Hamarosan" |
| `/szerzodeseim` | GET | `PortalController::plans` | Stub — "Hamarosan" |
| `/forgalom` | GET | `PortalController::usage` | Stub — "Hamarosan" |
| `/hibabejelentes` | GET | `PortalController::tickets` | Stub — "Hamarosan" |
| `/dokumentumok` | GET | `PortalController::docs` | Stub — "Hamarosan" |
| `/profil` | GET | `PortalController::profile` | Stub — "Hamarosan" |

**No auth middleware yet** — every route is publicly reachable. Once the `customers` table lands (via rgadmin) and we wire `Auth::guard('customer')`, we'll add `auth:customer` to all the portal routes.

## CSS structure

Split into four focused files so the auth screens, portal chrome and global helpers stay readable. `app.css` is the entry that `@import`s the others in order:

| File | Owns |
|---|---|
| `resources/css/app.css` | Entry — `@import`s the design bundle, then `style.css`, `auth.css`, `portal-ui.css`, then Tailwind directives. |
| `resources/css/design/colors_and_type.css`, `kit.css`, `portal.css` | **Verbatim** Claude Design handoff — never edit. Owns `.p-*` portal chrome, auth screens, cards, badges. |
| `resources/css/style.css` | Global helpers — `html, body { overflow-x: hidden }`, `[data-lucide]` size classes, `[x-cloak]`. |
| `resources/css/auth.css` | Auth screens — port-specific helpers for `.p-auth-form`, register wizard progress, forgot "sent" state. |
| `resources/css/portal-ui.css` | Portal chrome — sidebar/topbar overrides, stub-page card. |

The design `portal.css` references `url('assets/crest-monogram.svg')` for `.p-auth-side .crest-bg`; Vite can't resolve that relative path through the `@import`, so we render `<img class="crest-bg" src="{{ asset('assets/crest-monogram.svg') }}">` in the auth layout and override the CSS background to `none`.

## Layouts

- `resources/views/layouts/auth.blade.php` — split-screen (dark navy side with crest + form panel). Used by login, register, forgot.
- `resources/views/layouts/portal.blade.php` — sidebar + topbar + main. Mobile sidebar drawer toggled by Alpine `x-data="{ navOpen: false }"`. Used by dashboard and all stub pages.

Both layouts load Lucide via CDN with a `MutationObserver` for Alpine-injected nodes (same pattern as rgsite/rgtelekom).

## MVP scope

The full portal design is ~2400 lines of JSX across 7 files; this port shipped only the **MVP** (auth screens, layout, dashboard 3-card overview, 6 "Hamarosan" stubs). The detailed pages (full invoice list + PDF, contract details + loyalty bar, ticket threads + reply, profile editor) port later, once the real schema arrives:

- **Customer auth + profile**: needs `customers` table (rgadmin migration). Then add `Auth::guard('customer')` + `App\Models\Customer extends Authenticatable` with `protected $table = 'customers'`.
- **Invoices**: needs `invoices` + `payments` tables (rgadmin).
- **Subscriptions**: needs `subscriptions` + `products` tables (rgadmin).
- **Tickets**: needs `cp_tickets` + `cp_ticket_messages` tables (this project's migrations).

Mock data is in `app/Support/PortalMockData.php` — a single file makes it easy to delete in one go once the real models exist.

## Local dev

- Suggested hostname: `ugyfel.royaltelekom.test`

## Coding & access rules

See [root CLAUDE.md](../CLAUDE.md). Same conventions as siblings.

- Local Windows lint: `f:\xampp\php\php.exe`
- **No inline CSS in Blade** — write classes in `resources/css/style.css`. Only Alpine `:style="..."` reactive bindings and JS-toggled `style="display:none"` flags are exempt (see root CLAUDE.md).
- **No inline images** — files go to `public/assets/` and are referenced by URL.

## CLAUDE.md & skills

If, during development, you think something is worth adding to this CLAUDE.md (or the root one) for more efficient work, propose it in the summary (chat language: Hungarian) and write the addition in English.

If you think something should be added to the skills for more efficient work, propose it in the summary (chat language: Hungarian) and write the skill content in English.

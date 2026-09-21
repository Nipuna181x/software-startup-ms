# Startsuite

A multi-tenant SaaS platform. An organization registers on the public website
and immediately gets a private dashboard carrying its own name, logo and
colours, where it manages its own team.

This is the foundation release: tenancy, branding, authentication, user
management and settings. Business modules (calling, marketing, AI assistant)
are added on top of it later.

---

## Setup

Requirements: PHP 8.4, Composer, Node 20+, MySQL 8.

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate
```

Set the database connection in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=startsuite
DB_USERNAME=root
DB_PASSWORD=your-password
```

Create the database, then:

```bash
# 3. Schema and demo data
php artisan migrate
php artisan db:seed

# 4. Logo uploads are served from the public disk
php artisan storage:link

# 5. Assets
npm run build     # or: npm run dev

# 6. Run
composer run dev  # server, queue, logs and Vite together
```

Visit `http://localhost:8000`.

### Demo organization

`DemoOrganizationSeeder` reads its credentials from the environment and **skips
entirely unless `DEMO_ADMIN_PASSWORD` is set**, so no known password can reach a
deployed environment. Set these in `.env` for local use:

```dotenv
DEMO_ORGANIZATION="Northwind Logistics"
DEMO_ORGANIZATION_COLOR=#0f766e
DEMO_ADMIN_NAME="Demo Admin"
DEMO_ADMIN_EMAIL=admin@northwind.test
DEMO_ADMIN_PASSWORD=choose-a-local-password
```

### Tests

```bash
php artisan test
```

Tests run against a separate `startsuite_testing` MySQL database (configured in
`phpunit.xml`); create it once with:

```sql
CREATE DATABASE startsuite_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Quality checks: `composer run lint` (Pint) and `composer run types:check`
(PHPStan).

### Mail

`MAIL_MAILER` defaults to `log`, so the forgot-password email is written to
`storage/logs/laravel.log` rather than delivered. The flow itself works with no
extra setup. Point `MAIL_*` at real SMTP credentials for production.

---

## How tenancy works

One database, shared tables, every tenant row carrying an `organization_id`.

**The three pieces:**

| Piece | File | Role |
|---|---|---|
| `Tenancy` | `app/Support/Tenancy.php` | Answers "which organization is current?" |
| `OrganizationScope` | `app/Models/Scopes/OrganizationScope.php` | Global scope filtering every query |
| `BelongsToOrganization` | `app/Concerns/BelongsToOrganization.php` | The trait a model uses to opt in |

By default the current organization comes from the authenticated user
(`auth()->user()->organization_id`). When there is no authenticated user — a
seeder, a queued job, a console command — the scope does nothing, so those
contexts must be explicit:

```php
Tenancy::actAs($organization, fn () => Invoice::count());  // scope to one tenant
Tenancy::withoutScoping(fn () => Organization::create(…));  // deliberately global
```

`withoutScoping` is intentionally verbose: bypassing isolation should always be
visible in a diff.

### Adding a module

Give the module's table an `organization_id` foreign key, then add one trait:

```php
use App\Concerns\BelongsToOrganization;

class Campaign extends Model
{
    use BelongsToOrganization;
}
```

That is the whole integration. Reads are filtered automatically, and
`organization_id` is stamped on create, so `Campaign::create([...])` already
belongs to the right tenant.

Register a sidebar entry from a service provider:

```php
use App\Support\Navigation;

Navigation::register(
    label: 'Campaigns',
    route: 'campaigns.index',
    icon: 'megaphone',
    group: 'Modules',
    order: 40,
    visible: fn (User $user) => $user->isSuperAdmin(),
);
```

The sidebar renders whatever is in the registry — no layout edits needed.

### Two layers of defence

The global scope is the primary guard, but authorization never relies on it
alone. `UserPolicy` and `OrganizationPolicy` re-check that the actor and the
target share an organization, so a future module that queries without the scope
is still refused. `DataIsolationTest` asserts both layers, including the case
where the scope is deliberately bypassed.

### Roles

`App\Enums\Role` holds `SuperAdmin` and `User`. Roles are a column on `users`,
not a package — add a case to the enum and a check to the relevant policy to
extend it. The `super-admin` gate guards routes; policies guard actions.

Rules enforced in `UserPolicy`:

- A Super Admin cannot delete or deactivate their own account.
- The last Super Admin of an organization can never be deleted, deactivated or
  demoted.

### Platform owner

There is deliberately no Startsuite staff panel yet. Nothing blocks one: the
organization is resolved through `Tenancy` rather than read from the session in
scattered places, so a staff panel would set the organization explicitly with
`Tenancy::actAs()` and reuse every existing screen.

---

## How theming works

Each organization stores one `primary_color` hex. Everything else is derived.

1. **`App\Support\Color`** builds a ten-step shade ramp from that hex by mixing
   it with white (tints) and black (shades), and picks the button text colour
   by WCAG contrast ratio — white on dark colours, near-black on light ones. A
   colour too light to read on white (white, near-white) is rejected at
   registration and in settings by the `ReadableThemeColor` rule.

2. **`App\Support\OrganizationTheme`** resolves the viewer's organization and
   builds its CSS custom properties **once per request**. It is registered as a
   scoped singleton; a view composer then shares `$organization` and
   `$themeStyle` with every view. The memoisation matters: views render many
   times over, and an unmemoised lookup previously produced a runaway query
   loop (guarded now by `ThemeRenderingTest`).

3. **The layout** prints those properties on `<body>`, so the whole page
   inherits them:

   ```html
   <body style="--brand:#0f766e;--brand-hover:#0d6860;…">
   ```

4. **Markup uses the variables**, never a hard-coded colour:

   ```html
   <div style="background:var(--brand);color:var(--brand-foreground)">
   ```

   Tailwind equivalents (`bg-brand`, `text-brand-600`, `border-brand-border`)
   are mapped in `resources/css/app.css` under `@theme inline`.

Available properties: `--brand-50` … `--brand-900`, plus the semantic aliases
`--brand`, `--brand-hover`, `--brand-active`, `--brand-foreground`,
`--brand-subtle` and `--brand-border`.

A module that sticks to these variables is themed correctly for every
organization without doing anything else.

The interface is **light only**. Dark mode was removed deliberately, so the
dashboard is always white plus the organization's colour.

Startsuite's own accent (the public website and auth pages) is separate and
lives in one place: `config/startsuite.php`, overridable with
`STARTSUITE_ACCENT` in `.env`. The preset swatches offered during registration
are in the same file.

---

## Calling module

The first business module built on the tenancy foundation above: organizing
outbound calls to businesses, grouped by category and business type.

### Tables

```
call_categories         Top-level groups, e.g. "Marketing Agencies"
call_business_types     Types within a category, e.g. "Phone Shops"
businesses              Name, address, owner, website, notes
business_phones         One or more numbers per business, one marked primary
business_screenshots    Paths on the private disk (never the public one)
calls                   One row per call attempt — never updated, only added to
```

Every table uses `BelongsToOrganization`, the same trait every other module
model uses, so the existing global scope isolates them automatically. A
business belongs to one business type; a business type belongs to one
category. There is no separate `organization_id` join required anywhere —
each table carries its own, stamped on create.

### Sidebar and routing

Calling registers itself in `AppServiceProvider::configureNavigation()` with
one `Navigation::register()` call, exactly like Users and Settings — no
layout changes were needed. Routes live in `routes/calling.php` and nest three
levels deep (`categories/{category}/types/{type}/businesses/{business}`) with
`Route::scopeBindings()`, so a business type's slug is only ever resolved
under its real parent category; swapping in a sibling's slug 404s rather than
silently resolving.

### Call outcomes

`App\Enums\Calling\CallOutcome` (`NotAnswered`, `Pending`, `Interested`,
`Rejected`) is a plain PHP enum, matching how `Role` is modelled — outcomes
are a fixed, developer-defined list, not something an organization configures
for itself. Adding a new outcome later means adding a case and a label/color,
no schema change.

A business's current status is derived, not stored: `Business::currentStatus()`
reads the most recent call whose outcome is not `NotAnswered`
(`Business::latestAnsweredCall()`, a "latest of many" relation scoped to
answered outcomes). Nothing is ever overwritten — changing a business's status
later just adds another row to `calls`, and the full history is always
available on the business's page, newest first.

### Screenshots

Stored on the **private** `local` disk (`storage/app/private`), not the
public disk used for the organization logo, since a screenshot may show a
business's private contact details. `BusinessScreenshotController` is the
only way to read one: it authorizes against the screenshot's business before
streaming the file, so a screenshot id from another organization 404s and a
guest is redirected to log in.

### Duplicate phone detection

`App\Support\PhoneNumber::normalize()` strips spaces, dashes and a leading
`+94` or `0`, so `"+94 71 234 5678"`, `"0712345678"` and `"71-234-5678"` all
compare equal. `Business::findByPhoneNumber()` checks this against the
current organization only (via the existing global scope) and is used to
warn — not block — when a phone number entered on the business form already
belongs to another business.

### Permissions

Any organization member can view categories/types/businesses, add and edit
businesses, and log calls. Only a Super Admin can manage categories and
business types, or delete a business. `CallCategoryPolicy`,
`CallBusinessTypePolicy`, `BusinessPolicy` and `CallPolicy` enforce this the
same way `UserPolicy` does — re-checking organization membership
independently of the global scope. Deleting a category or business type is
blocked while it still contains children, rather than cascading.

### Demo data

`CallingDemoSeeder` seeds the categories, business types and a few
businesses with fake Sri Lankan-format numbers described above, for the demo
organization only. It skips entirely if the demo organization does not
exist yet.

## Structure

```
app/
├── Actions/Organizations/RegisterOrganization.php   Org + first Super Admin, one transaction
├── Concerns/BelongsToOrganization.php               The tenancy trait
├── Enums/Role.php                                   Super Admin / User
├── Http/Middleware/EnsureAccountIsActive.php        Logs out mid-session deactivations
├── Models/Scopes/OrganizationScope.php              Global tenant filter
├── Policies/                                        Authorization
├── Rules/ReadableThemeColor.php                     Hex + contrast validation
└── Support/
    ├── Color.php                                    Shade ramp and contrast maths
    ├── Navigation.php                               Sidebar registry for modules
    ├── OrganizationTheme.php                        Per-request theme resolution
    └── Tenancy.php                                  Current organization

resources/views/
├── components/
│   ├── wordmark.blade.php                           Startsuite mark (CSS type, no image)
│   ├── dashboard-mockup.blade.php                   Home page hero visual
│   └── organization-preview.blade.php               Live preview, registration + settings
├── layouts/{public,auth,app}                        Public site / auth / dashboard
└── pages/                                           Livewire single-file pages
```

Routes: `routes/web.php` (public, dashboard) and `routes/settings.php`
(profile, organization, users).

### Authentication

Laravel Fortify, with **public self-registration disabled** — the only ways to
get an account are registering an organization, or a Super Admin adding a user.
Two-factor auth and passkeys were removed to keep the foundation lean.

Login is throttled to 5 attempts per minute per email and IP; organization
registration to 3 per minute and 10 per day per IP. Inactive users, and users
whose organization is inactive, are refused with a message naming which applies
— and are logged out on their next request if deactivated mid-session.

---

## Test coverage

174 tests covering organization registration (including logo rules and colour
contrast), login with inactive users and organizations, role authorization,
last-Super-Admin protection, theme rendering, cross-organization data
isolation by guessed IDs and URLs, and the Calling module (category/type/
business permissions, duplicate phone detection, screenshot isolation and
the full call outcome workflow).

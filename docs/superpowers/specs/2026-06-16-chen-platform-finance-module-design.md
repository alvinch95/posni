# Chen — Personal App Platform (Module #1: Finance)

**Date:** 2026-06-16
**Status:** Approved design
**Location:** Inside the existing `posni/` Laravel 8 project, fully isolated from existing modules.

---

## 1. Goal & framing

Build **Chen**, a personal app *platform* served on its own subdomain, with its own login,
its own visual identity, and a module system designed to host many features over time.

The **Finance** module is the first feature. It tracks expenses, income, recurring transactions,
and monthly savings. The platform shell (auth, layout, nav, settings scaffold) is built once and
reused by every future module — adding a feature later means dropping in a module folder, not
reworking the shell.

**Hard constraint:** existing posni modules (POS, stock, sales, attendance, etc.) must be
completely unaffected. No edits to `web.php`, `api.php`, the `web` auth guard, or posni's
Laravel Mix asset pipeline. Everything new lives under the `App\Chen` namespace and `chen_*`/`fin_*`
tables so it is grep-able and removable as a unit.

---

## 2. Platform shell (built once, reused by every module)

### Routing / subdomain
- New route file `routes/chen.php`, registered in `RouteServiceProvider::boot()`:
  ```php
  Route::domain('chen.' . env('CHEN_DOMAIN'))
      ->middleware(['web'])
      ->name('chen.')
      ->group(base_path('routes/chen.php'));
  ```
- Existing `web.php` / `api.php` registrations untouched — posni keeps serving on the bare domain.
- New env var `CHEN_DOMAIN` (= `posni.test` locally → `chen.posni.test`; the real domain in prod).
- **Local dev:** one `hosts` entry `127.0.0.1 chen.posni.test`.
- **Production:** DNS record + server-block for `chen.*` pointing at the same Laravel `public/`.
- `SESSION_DOMAIN` is left unset (current behaviour) — cookies are per-host, so `chen.posni.test`
  gets its own session cookie and auth isolation comes for free. Do **not** change session config.

### Authentication (fully separate)
- **Additive** changes to `config/auth.php` only:
  - guard `chen` → session driver, provider `chen_users`.
  - provider `chen_users` → eloquent, model `App\Chen\Models\User`.
  - The existing `web` guard and `users` provider are **not modified**.
- Model `App\Chen\Models\User` → table `chen_users`. No roles — authenticated = full access.
- Accounts created via console command `php artisan chen:user {email}` (prompts for name + password).
  No registration route.
- Login + logout only. Routes protected by `auth:chen`; guests redirected to the Chen login page.
- A `RedirectIfAuthenticated`-style guard for the Chen login route uses the `chen` guard.

### Module system
Convention-based, deliberately lightweight:
- Each module = `app/Chen/Modules/<Module>/` containing its Controllers, Models, Views,
  a `routes.php`, and a `module.php` manifest returning an array:
  `['key' => 'finance', 'label' => 'Finance', 'icon' => '...', 'order' => 10, 'enabled' => true]`.
- `routes/chen.php` loops the registered/enabled modules and includes each module's `routes.php`
  under prefix `/<key>` and route-name prefix `<key>.`.
- The shell sidebar nav is rendered from module manifests (filtered to `enabled`, sorted by `order`).
- Adding a future feature = drop a module folder + ensure it's discovered. No shell edits.

### Layout / styling
- One fresh Blade layout `resources/views/chen/layout.blade.php`:
  - **Tailwind via Play CDN** + a small custom CSS file for the Chen identity.
  - **Alpine.js via CDN** for interactivity (modals, tabs, dropdowns).
  - Sidebar nav (from manifests) + top bar + content slot + flash partial.
- Completely separate from posni's Blade views and assets. Distinct, intentional visual identity
  (not a Bootstrap/admin-template clone).

### Settings (two tiers)
- **Platform settings** — table `chen_settings`, single row per user:
  `user_id, display_name, default_currency (default 'IDR'), locale (default 'id'), theme, timestamps`.
- **Module settings** — kept inside each module (Finance has its own `fin_settings`).

---

## 3. Finance module — data model

Expense and income are structurally identical, so a single transactions table with a `type` column
is used. Savings = `sum(income) − sum(expense)`.

- **`fin_categories`**
  `id, chen_user_id (fk), type ENUM('expense','income'), name, color (hex, for charts),
   icon (nullable), sort_order, deleted_at (soft delete), timestamps`.
  One master screen with an expense/income toggle. Soft-deleted so historical transactions keep
  their category label; a category in use cannot be hard-deleted.

- **`fin_transactions`**
  `id, chen_user_id (fk), type ENUM('expense','income'), fin_category_id (fk),
   date (date), amount DECIMAL(15,2), notes (text, nullable),
   recurring_rule_id (nullable fk → fin_recurring_rules), timestamps`.
  Indexes: `(chen_user_id, date)`, `(chen_user_id, type, date)`.
  Amounts are `DECIMAL(15,2)` — never float.

- **`fin_recurring_rules`**
  `id, chen_user_id (fk), type ENUM('expense','income'), fin_category_id (fk),
   amount DECIMAL(15,2), notes (nullable),
   frequency ENUM('weekly','monthly','yearly'),
   day_of_month (nullable, for monthly/yearly), weekday (nullable, for weekly),
   start_date (date), end_date (date, nullable), next_run_date (date),
   active (bool, default true), timestamps`.

- **`fin_settings`**
  `id, chen_user_id (fk, unique), currency (default from platform),
   monthly_spending_target DECIMAL(15,2) nullable,
   monthly_savings_target DECIMAL(15,2) nullable, timestamps`.

All Finance data is scoped by `chen_user_id`; every query filters by the authenticated Chen user.

### Recurring generation
- Recurring rules **materialize real `fin_transactions` rows** when due (cleaner analytics +
  rows are individually editable). Generated rows carry `recurring_rule_id`.
- Console command `php artisan chen:finance:run-recurring`:
  - For each active rule where `next_run_date <= today` and `next_run_date <= end_date (or no end)`,
    create the due transaction(s), then advance `next_run_date` by the frequency. Idempotent —
    safe to run repeatedly; never double-creates a period.
- Registered in the Laravel scheduler to run daily.
- **Also** a lightweight idempotent catch-up runs on dashboard load (same logic, driven by
  `next_run_date`), so the feature works even without cron configured.

---

## 4. Finance module — screens

1. **Login** (platform shell) — email + password (`chen` guard).
2. **Dashboard** (`/finance`) — analytics, see §5.
3. **Transactions** (`/finance/transactions`) — Expense / Income tabs (type filter),
   month + category filter, text search on notes, paginated (newest first),
   running total of the filtered set. Inline create/edit via Alpine modal; delete with confirm.
4. **Recurring** (`/finance/recurring`) — list + CRUD of recurring rules (expense or income),
   toggle active, shows next run date.
5. **Categories** (`/finance/categories`) — CRUD master with expense/income type toggle,
   name + color picker + sort order.
6. **Settings** (`/finance/settings`) — currency, monthly spending target, monthly savings target.

---

## 5. Finance module — dashboard analytics

- **This-month cards:** total income, total expense, **net saving** (income − expense),
  net saving vs `monthly_savings_target`.
- **Savings trend** — bar/line of monthly net saving over the last 6 months
  ("how much we saved each month").
- **Income vs expense** — grouped bars per month (last 6 months).
- **Expense by category** — donut for the current month (toggle to income), slice color =
  category color.
- **Averages** — average expense per day this month, average per transaction.
- **Recent transactions** — last 5.
- **Range switcher** — This month / Last month / Last 6 months, driving the charts.
- Charts rendered with **ApexCharts (CDN), client-side**; controllers pass aggregated JSON.
  (Not the server-side larapex-charts wrapper — keeps the new UI self-contained.)

---

## 6. File layout

```
app/Chen/
├── Models/User.php                         (chen guard user)
├── Models/Setting.php                      (platform settings)
├── Console/CreateUser.php                  (chen:user command)
├── Http/Controllers/Auth/LoginController.php
├── Support/ModuleRegistry.php              (discovers + lists module manifests)
└── Modules/Finance/
    ├── module.php                          (manifest)
    ├── routes.php
    ├── Console/RunRecurring.php            (chen:finance:run-recurring)
    ├── Models/{Category,Transaction,RecurringRule,FinanceSetting}.php
    ├── Services/RecurringGenerator.php     (materialize due rows, idempotent)
    ├── Controllers/{DashboardController,TransactionController,RecurringController,
    │                CategoryController,SettingController}.php
    └── Views/ (dashboard, transactions, recurring, categories, settings)

resources/views/chen/
├── layout.blade.php                        (shell: sidebar + topbar)
├── auth/login.blade.php
└── partials/ (nav, flash)

routes/chen.php                             (shell auth routes + module loader)
database/migrations/                        (new chen_* and fin_* files only)
config/auth.php                             (additive: chen guard + provider)
```

---

## 7. Testing

PHPUnit feature tests (tests use a separate sqlite/in-memory connection — never touch shared MySQL):
- **Auth:** login required on Chen routes, guest redirected to Chen login, wrong credentials rejected,
  the `chen` guard is independent of the `web` guard.
- **Transactions:** CRUD scoped to the authenticated Chen user (user A cannot see/edit user B's rows).
- **Categories:** soft-delete when in use; type toggle (expense/income) respected.
- **Recurring:** generator creates due rows, advances `next_run_date`, is idempotent (no double-create),
  respects `end_date` and `active`.
- **Dashboard aggregates:** monthly income/expense/net-saving totals and the 6-month savings trend
  return correct numbers from seeded data.
- Model factories for Chen user, categories, transactions, recurring rules.

---

## 8. Out of scope (future modules / later)

- Multi-currency per transaction (single currency per user for now).
- Budgets per category, tags, attachments/receipts.
- Additional Chen modules beyond Finance (the shell is built to accept them).
- Public registration (accounts are seeded/CLI-created).

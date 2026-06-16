# Chen — personal app platform

Chen lives **inside** the posni Laravel project but is served on its own subdomain with its own
login, its own visual identity, and a module system designed to host many features over time.
It is fully isolated from posni's existing modules (POS, stock, sales, attendance).

**First module:** Finance — expenses, income, recurring transactions, and monthly savings analytics.

---

## What was added (and what posni files were touched)

Everything new lives under the `App\Chen` namespace, the `chen_*` / `fin_*` tables, and
`database/migrations/chen/`. Only **three** existing posni files were modified, all additively:

- `config/app.php` — registers `App\Chen\ChenServiceProvider`.
- `config/auth.php` — adds a `chen` guard + `chen_users` provider (the `web` guard is untouched).
- `app/Http/Middleware/Authenticate.php` — an extra branch: unauthenticated requests on the
  Chen subdomain redirect to `chen.login` instead of posni's `login`.

`ChenServiceProvider` registers the subdomain route group, the `chen`/module view namespaces,
the `database/migrations/chen` path, the console commands, and the daily recurring schedule —
without editing posni's `RouteServiceProvider` or `Console/Kernel`.

> Note: the Chen subdomain route group is registered in the provider's `register()` (not `boot()`)
> on purpose. posni's `RouteServiceProvider` defers `routes/web.php` to a `booted()` callback, so
> registering Chen's domain-constrained routes earlier ensures they match the `chen.*` host before
> posni's unconstrained routes (e.g. posni's bare `/login`). `php artisan route:cache` works fine.

---

## Local setup (Herd)

Herd serves `*.test` via dnsmasq and resolves **wildcard subdomains** of a parked site, so
`chen.posni.test` already points at the posni app — **no `hosts` edit needed**.

1. (Optional) set the base domain in posni `.env` — it defaults to `posni.test` if unset:
   ```
   CHEN_DOMAIN=posni.test
   ```
2. Run the migrations (Chen migrations auto-load from `database/migrations/chen/`):
   ```
   php artisan migrate
   ```
   This creates `chen_users`, `chen_settings`, `fin_categories`, `fin_recurring_rules`,
   `fin_transactions`, `fin_settings` in the shared MySQL database. It does **not** touch posni tables.
3. Create your login account (no public registration exists):
   ```
   php artisan chen:user you@example.com
   ```
   (prompts for name + password)
4. Visit **http://chen.posni.test/** and log in. The Finance module is in the sidebar.

---

## Production deployment

- **DNS:** add an A/CNAME record for `chen.<your-domain>` pointing at the same server as posni.
- **Web server:** add a server block / vhost for `chen.<your-domain>` with the **same document root**
  (`public/`) as posni. (One Laravel app serves both hosts; routing is by domain.)
- **.env:** set `CHEN_DOMAIN=<your-domain>` so the subdomain route group matches `chen.<your-domain>`.
- **Migrate:** `php artisan migrate` on the server.
- **Scheduler (recommended):** ensure Laravel's scheduler runs so recurring transactions materialize
  daily:
  ```
  * * * * * cd /path/to/posni && php artisan schedule:run >> /dev/null 2>&1
  ```
  The recurring generator also runs an idempotent **catch-up on every dashboard load**, so if cron
  isn't configured, due recurring rows are still created the next time you open the Finance dashboard
  — cron just makes it happen punctually at 00:30.

---

## The Finance module

- **Transactions** (`/finance/transactions`) — expenses and income in one ledger (a `type`
  discriminator), with month / type / category / notes-search filters, paginated, and income /
  expense / net totals for the filtered set.
- **Categories** (`/finance/categories`) — typed (expense | income), color-coded, soft-deleted so
  historical transactions keep their label.
- **Recurring** (`/finance/recurring`) — rules that auto-generate transactions. `start_date` is the
  source of truth for the recurring day (it seeds `next_run_date`); the generator advances weekly /
  monthly / yearly and stops at `end_date`. Toggle a rule active/inactive.
- **Settings** (`/finance/settings`) — currency + monthly spending target + monthly savings target.
- **Dashboard** (`/finance`) — month cards (income, expense, net saving vs savings target, averages),
  a 6-month savings trend (income/expense/saving bars), an expense-by-category donut, and recent
  transactions. Charts are ApexCharts (CDN).

### Recurring behaviour caveats

- **Idempotent + safe:** generation per rule runs inside a DB transaction with a row lock and a
  per-run cap (`MAX_PER_RULE`), so the cron + dashboard catch-up can't double-create, and a
  mis-seeded old rule can't flood the table in one request.
- **Month-end drift:** a monthly rule starting on the 31st uses Carbon's `addMonthNoOverflow`, so it
  becomes the 28th after passing February and stays there (it does not snap back to true month-end).
  If you want "last day of month" semantics, set the rule to a safe day or extend the generator.
- `day_of_month` / `weekday` columns exist for future use but are **not** read by the generator yet
  (and the UI does not expose them) — `start_date` drives the day.

---

## Adding a new Chen module later

The platform is built to grow. To add a feature module `Foo`:

1. Create `app/Chen/Modules/Foo/module.php` returning:
   ```php
   <?php
   return ['key' => 'foo', 'label' => 'Foo', 'icon' => '🦊', 'order' => 20, 'enabled' => true];
   ```
2. Add `app/Chen/Modules/Foo/routes.php` — it's auto-included under prefix `/foo` with route-name
   prefix `chen.foo.`, inside the `auth:chen` group.
3. Put Blade views in `app/Chen/Modules/Foo/Views/` — available under the `foo::` namespace.
4. Define a `chen.foo.dashboard` route — the sidebar nav links each module to `chen.<key>.dashboard`.
5. Put any migrations in `database/migrations/chen/` (auto-loaded) and tables under a `foo_*` prefix.

No changes to the shell, the provider, or posni are needed — the module is discovered automatically.

---

## Testing

Chen tests live under `tests/Feature/Chen/` and extend `Tests\Chen\ChenTestCase`, which runs on an
in-memory SQLite database migrating **only** `database/migrations/chen` — the shared MySQL is never
touched. Run them with:

```
php vendor/bin/phpunit --filter Chen      # all Chen + Finance tests
php vendor/bin/phpunit --filter Finance   # Finance module only
```

> On this Windows + Herd setup, `php` is on PATH in **PowerShell** (not Git Bash).

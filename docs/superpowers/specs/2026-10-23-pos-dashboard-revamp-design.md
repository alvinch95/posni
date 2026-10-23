# POS Dashboard Revamp — Design

**Date:** 2026-10-23
**Status:** Approved design
**Location:** `posni/` — the admin dashboard at `/dashboard` (`DashboardController`, `resources/views/dashboard/index.blade.php`) plus the Cash Balance pages.

---

## 1. Intent

The owner uses the POS dashboard to review how the business is doing. Today it can't answer the
questions that matter:

- Only the metric cards follow the period dropdown. Every chart has its own filter (year, date
  range) or none at all, so the page never describes one consistent period.
- Card labels are wrong. "Total Revenue" sums `total_revenue` (which is actually *cuan*, i.e.
  selling price − capital − marketplace fee). "Total Orders" sums `total_order` (gross sales in
  Rupiah) and formats it as currency. "Avg Order Value" is cuan ÷ orders.
- Top products ignore the period, and their profit figure is wrong:
  `SUM(selling_price - capital_price)` is not multiplied by `qty`.
- The Cash In and Cash Out cards show cash flow, which isn't what the owner wants to know. They
  want **profit after operating costs**.
- Manual cash-outs (salary, ads, packaging, tax, and so on) have no category, only a free-text
  remark, so nobody can see where the money goes.

**The goal:** one period selector drives the whole page, which shows sales, gross profit (cuan),
net profit (cuan − operating costs), top-selling products, and operating costs broken down by
category.

## 2. Goals, non-goals, anti-goals

**Goals**
1. Top-selling products for the selected period.
2. The whole dashboard driven by the selected period.
3. Replace the Cash In and Cash Out cards with a **Laba Bersih** (net profit) card.
4. Operating cost breakdown per category for the selected period.

**Non-goals**
- A page for managing categories (create, edit, delete). The list is fixed in code.
- Counting manual cash-ins toward net profit. There are only a handful of rows, and they look
  like refunds or entry mistakes, so they are ignored.
- Accounting-grade P&L. There is no accrual and no depreciation, so an asset purchase counts as
  an expense in the period it was paid.
- Redesigning other posni pages, or changing the sales, purchase, or running-balance logic.
- Filtering or searching Cash Balance by category.

**Anti-goals** (the work fails if any of these happen, even if the rest works)
- **Numbers that disagree with each other on screen.** Laba Bersih must equal Cuan − Biaya ops,
  and Biaya ops must equal the sum of the breakdown slices.
- **Breaking any existing flow in production.** Sales, purchases, cancelling a sale, and Cash
  Balance create must behave exactly as before, apart from the new category field.
- **Non-admin staff seeing profit or salary data.**
- **A test run wiping the local database.** The local MySQL database is literally named
  `posni_production` (a copy of production; see §8).

## 3. Metric definitions (source of truth)

"In period" means the date column falls between `from` and `to` inclusive, with whole days in
`Asia/Jakarta` (the app timezone). Soft-deleted rows (`deleted_at` not null) are always excluded.
Cancelling a sale soft-deletes the sales order and its details, so cancelled sales drop out
automatically.

| Metric | Definition |
|---|---|
| **Omzet** (gross sales) | `SUM(sales_orders.total_order)` where `order_date` is in the period |
| **Cuan** (gross profit) | `SUM(sales_orders.total_revenue)`. This is already total − capital − customer fee, saved at order time. |
| **Margin %** | Cuan ÷ Omzet. Show "—" when Omzet is 0. |
| **Jumlah Order** | `COUNT(sales_orders)` |
| **Rata-rata nilai order** | Omzet ÷ Jumlah Order. Show 0 when there are no orders. |
| **Biaya ops** | `SUM(cash_balances.amount)` where `cash_type = 'CashOut'` and `related_to = 'Manual Input'` and `transaction_date` is in the period |
| **Laba Bersih** | Cuan − Biaya ops. Can be negative and must render as negative. |
| **Cost breakdown** | Biaya ops grouped by `cash_balances.category`. A null category is shown as *Lainnya*. |

**Every manual cash-out is an operating cost.** This is the owner's decision. There is no
excluded "non-operational" category. Rows with an empty or unclear remark still count, and land in *Lainnya* until they are recategorised.

**Not counted as operating cost:**
- `Purchase` cash-outs. Stock purchases are already reflected in capital/HPP, which is inside
  cuan.
- `Cancel Sales` cash-outs. The cancelled order has already dropped out of the sales totals.

**Known gap, accepted:** `purchases.additional_fee` (shipping on stock) is added to the Purchase
cash-out, but it never reaches `hampers.capital_price`. So it is in neither Cuan nor Biaya ops.
In the local data it is negligible, so it is not handled.

**Top products** come from `sales_order_details` joined to non-deleted `sales_orders` (filtered on
`order_date`) and to `hampers`, grouped by hamper:
- qty = `SUM(qty)`
- omzet = `SUM(selling_price * qty)`
- margin = `SUM((selling_price - capital_price) * qty)`

Line-level margin is *before* order-level discount and marketplace fee, so it will not add up to
the Cuan card. Label the column **Margin** (not "Cuan") and add a short hint, e.g. "sebelum diskon
& fee". The list shows the top 10, with a toggle to rank by **Qty** or by **Margin**.

**Inventory value** comes from `inventory_values.total` by `record_date`. The daily
`inventory-value:calculate` command writes one snapshot per day. It is a snapshot, not a flow:
- Daily granularity: the value for each day. A missing day is a gap (null), **not 0**.
- Monthly granularity: the last snapshot in each month.

## 4. Period model

**Presets.** The default is `this_month`.

| Key | Range | Comparison range |
|---|---|---|
| `today` | today | yesterday |
| `last_7_days` | today−6 … today | the 7 days before that |
| `this_month` | 1st of this month … today | 1st of last month … the same day-of-month last month (clamped to month end, e.g. 31 Mar → 28/29 Feb) |
| `last_month` | the whole previous month | the whole month before that |
| `this_year` | 1 Jan … today | 1 Jan … the same date last year |
| `last_year` | the whole previous year | the whole year before that |
| `custom` | `from` … `to` | a window of the same length ending the day before `from` |

**% change** = (current − previous) ÷ |previous|. When previous is 0, show "—" instead of ∞ or
NaN. Cards show % change. Charts don't.

**Range label** under the selector, e.g. *"1–23 Okt 2026 · dibanding 1–23 Sep 2026"*.

**Trend granularity.** Daily when the range is ≤ 62 days, monthly otherwise. Buckets with no data
are 0 for sales and order count, and null (a gap) for inventory.

**Custom range** needs `from ≤ to`. Reject invalid input with a 422 and show a message instead of
rendering garbage.

**URL state.** Reflect the current period in the query string (`?period=…&from=…&to=…`) so
refresh, back, and bookmarks keep it.

## 5. Dashboard layout (mobile-first)

Keep the existing stack and layout: `dashboard.layouts.main`, Bootstrap 5.0.2, jQuery 3.6,
ApexCharts, Bootstrap Icons, and the existing palette (sage `#8c9c84`, beige `#d4c5b0`, brown
`#4a4036`, accent `#e67e22`). Copy is in Indonesian.

Sections, top to bottom:

1. **Header.** Title, the period dropdown (presets + Custom), from/to date inputs that appear
   only for Custom, and the range label.
2. **Laba Bersih hero card.** Full width on phones. Shows the big number, a sub-line
   *"Cuan Rp X − Biaya ops Rp Y"*, and % change vs the comparison period. Negative values are
   visibly negative (sign + colour).
3. **Four cards:** Omzet, Cuan (with Margin %), Jumlah Order, Rata-rata nilai order. Each shows
   % change. On phones they sit in a 2-column grid. On desktop, the hero card and these four
   share one row.
4. **Trend charts** (full width, in one card). Granularity follows §4. They replace the old
   Monthly Transaction, Monthly Order Count, and Daily Activity charts, and their separate filters
   are removed.
   - Top chart: Omzet as bars and Cuan as a line, sharing one Rupiah axis.
   - Below it: a small Jumlah Order bar chart on the same x-axis.
   - *Changed during the build:* there is no dual axis. Two y-scales on one chart are a known
     misreading trap, so the order count gets its own chart.
   - Colours were checked with the dataviz palette validator, which passes colour-vision
     deficiency (CVD) and contrast: Omzet `#c88432`, Cuan `#23693a`.
5. **Top products** and **cost breakdown.** Side by side on desktop, stacked on phones.
   - **Top products:** a ranked list (rank, hamper name, qty, omzet, margin) with a Qty/Margin
     toggle. Use a list, not a horizontal bar chart, so long hamper names wrap instead of being
     cut off on phones.
   - **Cost breakdown:** a ranked list of category → Rupiah → % of total Biaya ops, each row
     with a single-hue magnitude bar.
   - *Changed during the build:* no donut. Eight slices is past the readable limit for a donut
     (about 6), and a ranked bar list compares the values better.
6. **Inventory value trend** (full width), following §3 and §4.

**Behaviour**
- One request fetches everything for a period change, so the page isn't stitched together from
  several partially-filtered calls.
- While loading, show placeholders ("—" or skeleton). Every section has an empty state
  ("Belum ada data di periode ini") instead of a blank chart.
- Mobile checks from `CLAUDE.md` §7 apply. Test at 320, 375, 390, 414, and 430 px:
  - no horizontal scroll
  - Rupiah values don't overflow their cards
  - tap targets are ≥ 44 px
  - body text is ≥ 14 px

## 6. Expense categories

**Fixed list in code.** Store the slug and display the label:

| slug | label |
|---|---|
| `gaji` | Gaji & THR |
| `iklan` | Iklan |
| `affiliate` | Affiliate |
| `packaging` | Packaging |
| `pajak` | Pajak |
| `utilitas` | Utilitas |
| `peralatan` | Peralatan & Aset |
| `lainnya` | Lainnya |

**Schema.** An additive migration adds a nullable string `category` column to `cash_balances`
(after `related_to`). Existing rows stay valid.

**Backfill command:** `php artisan cashbalance:backfill-categories {--dry-run}`.
- Scope: rows with `cash_type = 'CashOut'`, `related_to = 'Manual Input'`, and
  `category IS NULL`. It is idempotent: rows that already have a category are never touched.
- Rules are case-insensitive substring matches on `remark`. The first match wins, in this order:
  1. `gaji`, `thr`, `bonus`, `lembur` → `gaji`
  2. `aff` → `affiliate`
  3. `ads`, `iklan` → `iklan`
  4. `bubble`, `lakban`, `fragile`, `kardus`, `packing`, `plastik`, `selotip` → `packaging`
  5. `pajak` → `pajak`
  6. `listrik`, `biznet`, `internet`, `wifi`, `ipl`, `pln`, `air pam`, or a remark that is exactly
     `air` → `utilitas`. Don't match a bare `air` substring.
  7. `mesin`, `printer`, `rak`, `hp`, `laptop`, `servis`, `service`, `tinta`, `gunting`, `mouse`,
     `kursi`, `bangku` → `peralatan`
  8. anything else, including an empty remark → `lainnya`
- `--dry-run` writes nothing. It prints a table of remark → category → count → total Rp, then
  per-category totals, so the owner can review it before the real run.
- Misses are expected (e.g. names of people or shops, or raw arithmetic as the remark). They land
  in Lainnya and get fixed through inline edit (below).

**Cash Balance create form**
- When **Cash Out** is selected, a required **Kategori** select appears. With **Cash In** it is
  hidden and saved as null.
- Server-side validation: `required_if:cashType,CashOut` and the value must be in the list.
  Don't rely on the UI alone.
- The remark stays as a free-text note.
- The running-balance calculation is unchanged.

**Cash Balance index page.** Add a **Kategori** column:
- **CashOut + Manual Input** rows: an inline select. Changing it saves straight away through an
  admin-only endpoint that updates only `category` (validated against the list) and shows a
  SweetAlert toast. On failure, revert the select and show an error.
- **Purchase** rows: the plain label "Pembelian stok", not editable and not stored.
- Other rows: blank.

**Purchase flow.** `PurchaseController` is unchanged, and Purchase rows keep a null `category`.

## 7. Access

- `/dashboard` and `/dashboard/data` change from `auth` to `admin` middleware.
- `/` is currently `DashboardController@index` with `auth`:
  - admins see the dashboard
  - non-admins are **redirected to `/attendance/checkin`**, not given a 403 (login already sends
    them there)
- The new category-update endpoint is `admin`-only.

**Out of scope, flagged separately.** Two other pages expose profit to non-admins who type the
URL. This work leaves both alone, because cashiers may rely on Sales History to cancel orders. The
anti-goal in §2 covers only the dashboard and the new endpoint.
- `/dashboard/sales/history` only requires `auth` and shows a "Laba" column.
- The POS screen `/dashboard/sales` puts `total_modal` and `total_cuan` in hidden inputs.

## 8. Constraints

- **Versions:** Laravel 8.75, PHP `^7.3|^8.0` (the local runtime is Herd), `doctrine/dbal`
  available. Write PHP that runs on 7.3+ unless the production PHP version is confirmed, so no
  enums, no `match`, no constructor property promotion.
- **posni is production.** Changes must be additive (one nullable column, new UI, new
  endpoint). Do not touch how sales, purchases, or cancellations write `cash_balances`. Leave the
  duplicate `Route::resource('/dashboard/cashbalances', …)` lines in `routes/web.php` as they are.
- **Tests must never touch the `.env` database.** `.env` points at the MySQL database
  `posni_production`, and `phpunit.xml` doesn't override the DB connection. Follow the
  `tests/Chen/ChenTestCase.php` pattern:
  - switch to in-memory sqlite in `setUp()`
  - create only the tables the test needs
  - never use `RefreshDatabase` or `migrate:fresh` against the default connection

  Because tests run on sqlite, dashboard queries must not depend on MySQL-only SQL such as
  `MONTH()` or `DATE_FORMAT()`. Bucket in PHP or use driver-aware expressions.
- **Local data stops at 2026-04-02.** For manual testing locally, use Custom ranges inside
  2024-05 … 2026-04, because "Bulan ini" will be empty.
- **Volume:** tens of thousands of rows at most. Plain aggregate queries are
  fine, with no caching layer needed.

## 9. Rollout

1. Deploy the code, then right away:
   - check `php artisan migrate:status`, because Chen migrations may be pending too
   - run `php artisan migrate --force`; the `category` column starts empty
   - run `php artisan route:clear`. A stale route cache would keep `/dashboard/data` open to
     staff and 404 the category endpoint.

   Until the migration runs, the dashboard data, Cash Out create, and the inline category edit
   fail. Cash In is unaffected.
2. Run `php artisan cashbalance:backfill-categories --dry-run` on production. The owner reviews
   the output.
3. Run it without `--dry-run`.
4. The owner fixes misclassified rows through inline edit on the Cash Balance page.

Between steps 1 and 3 the breakdown shows everything as *Lainnya*. That is acceptable.

## 10. Left to the builder

- Whether to pull the dashboard queries into a service class, and the JSON response shape.
- Whether the Qty/Margin toggle refetches or both rankings come back in one response.
- Where the category list lives (a config file or a small constants class) and the category
  colours.
- The Apex chart configuration details, skeleton style, and exact spacing.
- How the backfill command formats its output.
- Test structure (unit tests for period/comparison maths and keyword mapping, feature tests for
  the endpoints and access rules).

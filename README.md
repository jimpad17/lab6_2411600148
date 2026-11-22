# JimTrack Fitness — Real-Time Inventory Tracking System (Laboratory Exercise 6)

Laravel 12 + MySQL extension of the Lab 5 **JimTrack Fitness / PulseTrack** inventory system.
Lab 5 data is preserved: this project adds transactional stock movements, an event-driven
observer, queued low-stock notifications, a live dashboard API, physical-count adjustments,
and audit-ready history/reporting through **incremental migrations only**.

---

## Quick start (XAMPP)

1. Start **Apache** and **MySQL** in XAMPP.
2. Keep the existing Lab 5 database `jimtrack_inventory`.
   If it does not exist yet, create it:

   ```sql
   CREATE DATABASE IF NOT EXISTS jimtrack_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. `.env` is configured for:

   ```dotenv
   APP_NAME="JimTrack Fitness Inventory"
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=jimtrack_inventory
   DB_USERNAME=root
   DB_PASSWORD=
   QUEUE_CONNECTION=database
   ```

4. Install and run the **incremental** Lab 6 migrations (do **not** use `migrate:fresh`
   if you want to keep Lab 5 data):

   ```bash
   composer install
   npm install && npm run build
   cp .env.example .env      # only if .env is missing
   php artisan key:generate
   php artisan migrate       # adds Lab 6 columns/tables only
   php artisan serve
   ```

5. In a second terminal, run the queue worker so queued low-stock emails are delivered:

   ```bash
   php artisan queue:work
   ```

6. Open <http://127.0.0.1:8000>.

> The three Lab 6 migrations are
> `2026_11_22_000001_extend_inventory_transactions_for_tracking.php`,
> `2026_11_22_000002_create_notifications_table.php`, and
> `2026_11_22_000003_add_low_stock_notified_at_to_products_table.php`.
> Each uses `Schema::hasColumn()` / `hasIndex()` guards so re-running is safe.

---

## Login accounts

| Username / Email | Password | Role | Status |
|------------------|----------|------|--------|
| `ADMIN` / `admin@jimtrack.local` | `ADMIN123` | Admin | Active |
| `juan@jimtrack.local` | `STAFF123` | Staff | Active |
| `maria@jimtrack.local` | `STAFF123` | Staff | Active |
| `test@jimtrack.local` | `STAFF123` | Staff | Inactive (login blocked) |

`ADMIN` is mapped to `admin@jimtrack.local` in `app/Http/Requests/Auth/LoginRequest.php`,
so the Lab 4/5 credentials still work. The seeded admin cannot be deactivated or deleted.

---

## Roles

| Area | Admin | Staff |
|------|:-----:|:-----:|
| Dashboard, live KPIs, charts | ✅ | ✅ |
| Inventory overview + Stock In / Stock Out | ✅ | ✅ |
| Transaction log, create, details | ✅ | ✅ |
| Products / Brands / Categories — view, search, CSV export | ✅ | ✅ |
| Add / edit / deactivate / restore master data | ✅ | ❌ |
| Physical-count stock adjustments | ✅ | ❌ |
| Reverse a transaction (creates opposite record) | ✅ | ❌ |
| Users management | ✅ | ❌ |
| Reports (valuation, tracking, CSV) | ✅ | ❌ |
| Notifications inbox + mark as read | ✅ | ✅ |
| Activity Log | ✅ | ❌ |

Admin-only routes are protected by the `admin` middleware in `routes/web.php` (403 on
direct access) and their links are hidden in the Blade views.

---

## What is new in Lab 6

| Feature | Where |
|---------|-------|
| **InventoryTransaction model** with `product()` / `user()` relations, typed constants, scopes, signed quantity and running-balance helpers | `app/Models/InventoryTransaction.php` |
| **Event-driven observer** — the only writer to `products.quantity`; locks the product row, rejects overselling, sets `balance_after`, fires alerts | `app/Observers/InventoryTransactionObserver.php`, `app/Providers/EventServiceProvider.php` |
| **Atomic stock operations** — controllers create a transaction inside `DB::transaction()`; `InsufficientStockException` rolls everything back | `app/Http/Controllers/InventoryController.php`, `InventoryTransactionController.php`, `app/Rules/SufficientStock.php` |
| **Transaction UI** — filterable/paginated log, create form with live stock preview, detail page with running balance | `resources/views/transactions/*` |
| **Queued low-stock notifications** (database + mail), deduped per low-stock episode via `products.low_stock_notified_at` | `app/Notifications/LowStockNotification.php`, nav bell + `resources/views/notifications/index.blade.php` |
| **Live dashboard** — 30-second AJAX polling, animated KPIs, auto-refreshing low-stock list and recent movements | `routes/api.php`, `app/Http/Controllers/Api/InventoryApiController.php`, `app/Http/Resources/*`, `public/js/inventory-live.js` |
| **Physical-count adjustments** — admin enters a count; difference is turned into an adjustment transaction | `app/Http/Controllers/StockAdjustmentController.php`, `resources/views/adjustments/create.blade.php` |
| **Audit-friendly reversal** — deleting a transaction creates an opposite movement and marks the original `is_reversed`; history is never erased | `InventoryTransactionController@destroy` |
| **Product history + reports** — running balance, date filters, stock movement/low stock/valuation report with CSV export | `resources/views/products/show.blade.php`, `resources/views/reports/tracking.blade.php`, `ReportController@tracking` |
| **Tests** — observer, rollback, validation, alerts, API, roles, pages | `tests/Feature/InventoryTrackingTest.php` |

### Backward compatibility with Lab 5

- The Lab 5 `reference` column was renamed to `reference_number`.
  `InventoryTransaction` keeps a `reference` accessor/mutator, so old Lab 5 views and data
  continue to work.
- Old seeders bypass the observer with
  `InventoryTransactionObserver::withoutStockUpdates()` so historical sample quantities
  are not recalculated on re-seed.
- All Lab 5 soft-deactivation behaviour and the broken-URL fallback remain unchanged.

### API endpoints (session-authenticated, same-origin)

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/dashboard/stats` | KPI snapshot + generated timestamp |
| GET | `/api/products/low-stock` | 10 lowest-stock active products |
| GET | `/api/products/{product}/stock` | Stock lookup for create/adjustment forms |
| GET | `/api/transactions/recent` | 8 newest non-reversed movements |

Responses use `InventoryStatResource`, `ProductStockResource` and
`InventoryTransactionResource` for a consistent `data` wrapper.

---

## Main routes

- `/dashboard` — live statistics, charts, low-stock list and recent movements
- `/inventory` — stock levels and quick Stock In / Stock Out
- `/inventory-transactions` — filterable transaction log (create/show/reverse)
- `/adjustments/create` — admin physical-count adjustment
- `/notifications` — low-stock alert inbox
- `/products` — product management with per-product transaction history
- `/reports/tracking` — movement, low-stock and valuation report (admin)
- `/reports` — Lab 5 valuation report (admin)
- `/brands`, `/categories`, `/users`, `/activity-logs` — Lab 5 modules

---

## Important behaviour: nothing is deleted

Deleting a **product, brand, category or user account** only sets `is_active = false`.
Deleting/reversing a **transaction** creates an opposite record and flags the original;
the audit trail is always kept.

---

## Tests

```bash
php artisan test
```

**43 tests pass**, covering Breeze auth, Lab 5 stock/CRUD/roles/activity logging, and the
new Lab 6 observer, rollback, oversell rejection, notifications, API, adjustments,
reversal, access control and page rendering.

---

## Screenshots

Browser screenshots used in the lab report are stored in `screenshots/`:

- `05-login-page.png`
- `06-dashboard.png`
- `07-product-index.png`
- `08-add-product.png`
- `09-edit-product.png`
- `lab6-transactions.png`, `lab6-transaction-create.png`
- `lab6-tracking-report.png`, `lab6-adjustment-form.png`
- `lab6-notifications.png`, `lab6-product-history.png`

See `LAB_REPORT.md` for the written discussion, questionnaire answers and conclusion.

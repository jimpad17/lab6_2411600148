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

> The Lab 6 migrations are `000001_extend_inventory_transactions_for_tracking`,
> `000002_create_notifications_table`, `000003_add_low_stock_notified_at_to_products_table`,
> `000005_add_stockout_notified_at_to_products_table`, `000006_create_orders_table`,
> `000007_update_admin_credentials`, `000008_add_avatar_to_users_table` and
> `000009_add_receipt_number_to_orders_table` (all under `database/migrations/`). Each uses
> `hasColumn()` / `hasTable()` guards and only adds data, so Lab 5 records are preserved.

---

## Login accounts

| Username / Email | Password | Role | Status |
|------------------|----------|------|--------|
| `jimpadillo@gmail.com` | `admin123` | Admin | Active |
| `juan@jimtrack.local` | `STAFF123` | Staff | Active |
| `maria@jimtrack.local` | `STAFF123` | Staff | Active |
| `test@jimtrack.local` | `STAFF123` | Staff | Inactive (login blocked) |

The admin login is shown at the bottom of the login form. Typing `ADMIN` is still mapped to
the protected admin account in `app/Http/Requests/Auth/LoginRequest.php`, but the password is
now `admin123` (migration `2026_11_22_000007_update_admin_credentials.php` updated the
existing Lab 5 row in place — no data was deleted). The seeded admin cannot be deactivated
or deleted.

---

## Roles

| Area | Admin | Staff |
|------|:-----:|:-----:|
| Dashboard, live KPIs, charts | ✅ | ✅ |
| Dashboard: sales/purchase report, daily history, product history | ✅ | ❌ |
| Dashboard: product report + own orders / profile | ❌ | ✅ |
| Inventory overview + Stock In / Stock Out (products, brands, categories) | ✅ | ✅ |
| Menu: Product Report / Sales Order / Purchase Order / Transaction History / Product History / Audit Trail | ✅ | ✅ |
| Menu: Daily Report + Daily Report History | ✅ | ❌ |
| Receipt list + printable receipt (auto-generated on approval) | ✅ | Own receipts only |
| Transaction log, create, details | ✅ | ✅ |
| Orders: submit purchase/sales orders | ✅ | ✅ |
| Orders: correct quantity, approve/reject (stock only moves on approval) | ✅ | ❌ |
| Profile: view / edit name, email, avatar / change password | ✅ | ✅ |
| Products / Brands / Categories — view, search, CSV export | ✅ | ✅ |
| Add / edit / deactivate / restore master data | ✅ | ❌ |
| Physical-count stock adjustments | ✅ | ❌ |
| Reverse a transaction (creates opposite record) | ✅ | ❌ |
| Users management | ✅ | ❌ |
| Reports (valuation, tracking, CSV) | ✅ | ❌ |
| Notifications inbox + mark as read | ✅ | ✅ |
| Audit trail / Activity Log | ✅ | ✅ (read-only via `/audit-trail`) |

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
| **Order approval workflow** — staff submit Pending, admin edits the quantity (original kept in `original_quantity`) then approves; the observer posts stock only on approval | `app/Models/Order.php`, `app/Http/Controllers/OrderController.php`, `resources/views/orders/*` |
| **Order date rule** — `ValidOrderDate` rejects yesterday/past dates; today and future dates are accepted | `app/Rules/ValidOrderDate.php` |
| **Stockout automation** — an outbound movement to zero sets `is_active = false` ("Unavailable"), sends a bell + email `StockOutNotification` to admins, and writes an audit entry; restocking restores availability | `app/Observers/InventoryTransactionObserver.php`, `app/Notifications/StockOutNotification.php`, `products.stockout_notified_at` |
| **Role-specific dashboard reports** — admin sees sales/purchase, product, daily-history and product-history panels; staff sees product report and own orders | `DashboardController@index`, `resources/views/dashboard.blade.php` |
| **Alphanumeric Product ID / SKU** — letters and numbers (plus `.`, `-`, `_`) are accepted and validated | `ProductController@rules`, `resources/views/products/_form.blade.php` |
| **New tests for the above** — 10 extra feature tests | `tests/Feature/OrderApprovalTest.php` |
| **Exact sidebar menu** — admin: MAIN / INVENTORY / MANAGEMENT / ORDERS / RECORDS / MONITORING; staff: the subset without Inventory, Users and Daily Report History | `resources/views/layouts/app.blade.php` |
| **Receipts** — approval assigns `RCP-YYYY-####`; list + printable receipt with signature lines; staff see only their own | `app/Http/Controllers/ReceiptController.php`, `resources/views/receipts/*`, migration `000009` |
| **Profile editing** — change name/email/avatar (2 MB limit) and password with current-password check; protected admin email cannot be changed | `ProfileController@update`, `ProfileController@changePassword`, `resources/views/profile/index.blade.php`, migration `000008` |
| **Menu report pages** — dedicated Product Report, Daily Report, Daily Report History and Product History pages | `app/Http/Controllers/MenuController.php`, `resources/views/menu/*` |
| **New menu/profile tests** — 11 extra feature tests | `tests/Feature/AppMenuTest.php` |

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
- `/inventory` — stock levels and quick Stock In / Stock Out (filter by brand, category and status including Unavailable)
- `/inventory-transactions` — filterable transaction log (create/show/reverse)
- `/orders` — purchase/sales order queue; `/orders/create` — submit an order; admin can edit/approve/reject
- `/sales-orders`, `/purchase-orders` — dedicated order lists by type
- `/receipts` — printable receipts generated from approved orders
- `/product-report`, `/product-history`, `/transaction-history`, `/audit-trail` — menu pages
- `/daily-report`, `/daily-report-history` — admin-only daily reports
- `/profile` — account details, edit profile/avatar, change password, recent orders
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

**64 tests pass**, covering Breeze auth, Lab 5 stock/CRUD/roles/activity logging, the Lab 6
observer, rollback, oversell rejection, notifications, API, adjustments, reversal, access
control and page rendering, plus the admin credentials, order approval workflow, date rule,
stockout alert, alphanumeric SKU, role-specific dashboard reports, exact sidebar menu,
receipt generation and profile editing.

---

## dbGate (browse the MySQL database)

See `DBGATE.md` for the exact connection settings (`127.0.0.1:3306`, database
`jimtrack_inventory`, user `root`, blank password), table overview, verification queries
and optional SQL backup/import steps. dbGate connects to the live XAMPP MySQL instance, so
no data migration is needed.

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

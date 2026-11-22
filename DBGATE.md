# Connecting the JimTrack database to dbGate

This project uses the existing XAMPP MySQL database. dbGate can connect to the
**live** database directly — no export/import is required.

## 1. Start the database first

1. Open **XAMPP Control Panel**.
2. Start **Apache** and **MySQL**.
3. The Laravel app must be reachable at `http://127.0.0.1:8000`
   (`php artisan serve`).

## 2. Connection details

| Field | Value |
|-------|-------|
| Connection type | **MySQL** |
| Host | `127.0.0.1` |
| Port | `3306` |
| Database | `jimtrack_inventory` |
| User | `root` |
| Password | _(blank — leave empty)_ |
| SSL | Disabled / none |

These match `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=jimtrack_inventory
DB_USERNAME=root
DB_PASSWORD=
```

> If you changed your XAMPP MySQL root password, use that password in dbGate
> instead and update `DB_PASSWORD` in `.env` to the same value.

## 3. Steps in dbGate

1. Open dbGate.
2. Click **New connection** (the `+` button) → choose **MySQL**.
3. Fill in the fields from the table above.
4. Leave **Database** blank on the connection form if you want to browse every
   schema, or enter `jimtrack_inventory` to open it directly.
5. Click **Save**, then double-click the connection to connect.
6. Expand `jimtrack_inventory` → **Tables** to browse the data.

## 4. Tables you will see

| Table | Contents |
|-------|----------|
| `products` | Catalog, `quantity`, `is_active`, `stockout_notified_at`, `avatar`-related user data |
| `users` | Admin `jimpadillo@gmail.com` (password hash) and staff accounts |
| `brands`, `categories` | Master data |
| `inventory_transactions` | Every stock movement (audit source of truth) |
| `orders` | Purchase/sales orders with `status`, `receipt_number`, `original_quantity` |
| `activity_logs` | Audit trail |
| `notifications` | Bell/email stock alerts |
| `migrations` | Migration history |

## 5. Verify the new features from dbGate

```sql
-- Confirm the admin login row
SELECT id, name, email, role, is_active FROM users
WHERE email = 'jimpadillo@gmail.com';

-- Confirm approved orders have receipts
SELECT order_number, receipt_number, type, quantity, status
FROM orders ORDER BY id DESC LIMIT 10;

-- Confirm stockout auto-unavailability
SELECT sku, name, quantity, is_active, stockout_notified_at
FROM products WHERE is_active = 0;

-- Confirm stockout audit entries
SELECT action, module, description, created_at
FROM activity_logs WHERE module = 'Inventory' ORDER BY id DESC LIMIT 10;
```

## 6. Optional: export a backup from dbGate

1. Right-click the `jimtrack_inventory` node → **Export** → **SQL**.
2. Choose **Structure + Data**, then save the file (for example
   `jimtrack_inventory_backup.sql`).
3. To restore later, right-click the connection → **Import** → choose that file.

## Troubleshooting

- **Connection refused** — MySQL is not running; start it in XAMPP.
- **Access denied for root@localhost** — blank password works on default XAMPP;
  otherwise use the password you set.
- **Database not listed** — create it first:

  ```sql
  CREATE DATABASE IF NOT EXISTS jimtrack_inventory
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  ```

  then run `php artisan migrate --force` in the project folder.

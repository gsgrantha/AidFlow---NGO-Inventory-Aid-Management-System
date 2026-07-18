# AidFlow — DBMS Project

Same app, same design, same database logic as before — just organized into
smaller, role-specific files instead of one big `api.php`. Nothing about
what the app *does* has changed, only how the code is organized.

## Folder structure

```
AidFlow/
├── index.html              ← the app itself (UI unchanged)
├── config/
│   ├── database.php          ← ONE JOB: connects to MySQL
│   └── helpers.php           ← ONE JOB: shared helpers (session checks,
│                                 access control, id formatting)
├── api/
│   ├── auth.php               ← signup, login, logout, session check
│   ├── users.php               ← user list/roles/delete (admin) + profile edit
│   ├── donations.php           ← donations CRUD + the approve transaction
│   ├── inventory.php           ← inventory CRUD
│   ├── requests.php            ← aid-requests CRUD
│   └── distribution.php        ← distribution CRUD + the delivery transaction
└── sql/
    └── aidflow.sql             ← creates the 5 empty tables (no sample data)
```

Every table still starts **completely empty** — nothing appears until you
sign up and use the app yourself.

## Why this is easier to read

Before, `api.php` was one long file handling every table. Now each file has
exactly one job:
- Want to see how **login** works? → open `api/auth.php`
- Want to see how a **donation gets approved**? → open `api/donations.php`,
  look at the `approve` case
- Want to see the **database connection**? → open `config/database.php`
- Want to see **shared logic** like "is this user an admin?" → open
  `config/helpers.php`

The front end (`index.html`) now calls a specific file per resource, e.g.:
```js
fetch('api/donations.php', { method:'POST', body: JSON.stringify({action:'add', ...}) })
```
instead of one shared `api.php` with a `resource` field.

## Setup

1. Put the `AidFlow` folder in `htdocs`:
   - Windows: `C:\xampp\htdocs\AidFlow`
   - macOS: `/Applications/XAMPP/htdocs/AidFlow`
   - Linux: `/opt/lampp/htdocs/AidFlow`
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Go to `http://localhost/phpmyadmin` → **Import** → choose
   `sql/aidflow.sql` → **Go**. This creates 5 empty tables.
4. Visit `http://localhost/AidFlow/` → sign up your first account.

## If you get a "Database error"

Usually means either:
- MySQL isn't running (check the XAMPP control panel), or
- You imported an older version of the schema before. Fix: in phpMyAdmin,
  click `aidflow_db` → **Operations** → **Drop the database (DROP)** → then
  re-import `sql/aidflow.sql` fresh.

## Demoing "how DBMS works" to your teacher

Same idea as before, just point to the specific file for whatever you're
explaining:

1. phpMyAdmin → show all 5 tables empty
2. Sign up in the app → `api/auth.php` (`signup` case) runs the `INSERT INTO
   users` → show the new row in phpMyAdmin
3. Submit a donation → `api/donations.php` (`add` case) → show it in the
   `donations` table (status = Pending)
4. Approve it as admin → open `api/donations.php`, scroll to the `approve`
   case, and show the transaction:
   ```php
   $pdo->beginTransaction();
   ...
   $pdo->prepare("UPDATE donations SET status='Approved' WHERE id=?")->execute([$id]);
   $pdo->prepare("UPDATE inventory SET quantity=?, status=? WHERE id=?")->execute([...]);
   ...
   $pdo->commit();
   ```
   Point out: two tables updated in one atomic transaction — if either
   statement fails, `rollBack()` undoes both.
5. Mark a distribution "Delivered" → same idea in `api/distribution.php`
   (`updateStatus` case) — deducts stock from `inventory`.
6. Open `sql/aidflow.sql` → show the `FOREIGN KEY` constraints linking
   `donations.user_id`, `requests.user_id`, `distribution.user_id` back to
   `users.id` — referential integrity enforced by MySQL itself.
7. Open any `api/*.php` file and show a `$pdo->prepare(...)->execute([...])`
   call — this is a **prepared statement**, which protects against SQL
   injection.

SalesRecord (plain PHP)
=======================
Subscription/sales tracking portal with two roles:
- **Admin:** approve/reject submissions, edit current-month sales, manage agents, view ledgers, expenses, reports, shareholder reserve, exam boards.
- **Agent:** submit new sales or renewals, pay outstanding dues, see monthly dashboard and rejection notes.

Stack & Requirements
- PHP 8.x, Apache with `mod_rewrite` on (tested on XAMPP locally, cPanel in prod).
- MySQL/MariaDB.
- Writable folders: `public/proofs` (uploads) and `storage` (logs). Keep their `.htaccess` files.
- Timezone: Asia/Karachi (set in `app/init.php`).

Project Layout
- `index.php` – bootstraps init + all controllers and registers routes.
- `app/` – init, router, auth helper, date window helpers, controllers.
- `config/config.php` – DB, base URL, upload paths.
- `public/proofs/` – receipt uploads; `.htaccess` blocks PHP execution.
- `views/` – layouts, auth, agent, admin, boards, expenses, reports, shareholder.
- `.htaccess` (root) – front-controller rewrites to `index.php`.

Configuration (config/config.php)
- `db.dsn`, `db.user`, `db.pass`: point to your MySQL database.
- `base_url`: absolute URL in production (`https://example.com/salesrecord`), folder-relative in local (`/salesrecord`).
- `upload_dir` / `upload_url`: where receipt images live. Defaults are `../public/proofs` and `/salesrecord/public/proofs`.
- `app/init.php` has `$IS_PROD = true;` by default. Flip to `false` for local to show errors.

Database Schema (create in this order)
```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL, -- currently plain-text in controllers; switch to password_hash() for prod
  role ENUM('admin','agent') NOT NULL DEFAULT 'agent',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_boards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agent_user_id INT NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  city VARCHAR(120) NOT NULL,
  school_name VARCHAR(200) NOT NULL,
  module_name VARCHAR(150) NOT NULL,
  package_duration VARCHAR(120) DEFAULT NULL,
  package_start_date DATE NOT NULL,
  package_end_date DATE NOT NULL,
  amount_to_be_paid INT UNSIGNED NOT NULL DEFAULT 0,
  amount_paid INT UNSIGNED NOT NULL DEFAULT 0,
  amount_due INT UNSIGNED NOT NULL DEFAULT 0,
  next_payment_date DATE NULL,
  customer_type ENUM('new','old') NOT NULL DEFAULT 'new',
  payment_method ENUM('bank_transfer','easypaisa','jazzcash','cash','other') NOT NULL DEFAULT 'cash',
  agent_name VARCHAR(120) NOT NULL,
  receipt_image_path VARCHAR(255) DEFAULT NULL,
  commission_amount INT UNSIGNED NOT NULL DEFAULT 0,
  sale_source ENUM('Referral','Ad boost','Manual','Old Customer','Sales Officer','Add classes') NOT NULL DEFAULT 'Manual',
  province ENUM('Punjab','AJK','Federal') NOT NULL DEFAULT 'Punjab',
  exam_board VARCHAR(120) NOT NULL DEFAULT 'PTB',
  agent_note VARCHAR(250) DEFAULT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  approved_by INT NULL,
  approved_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sales_user FOREIGN KEY (agent_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sale_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NULL,
  school_name VARCHAR(200) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  amount INT UNSIGNED NOT NULL,
  method ENUM('bank_transfer','easypaisa','jazzcash','cash','other') NOT NULL DEFAULT 'cash',
  source ENUM('new','renewal','due') NOT NULL DEFAULT 'new',
  agent_user_id INT NULL,
  receipt_path VARCHAR(255) DEFAULT NULL,
  paid_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_paid_at (paid_at),
  CONSTRAINT fk_payments_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sale_due_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NOT NULL,
  school_name VARCHAR(200) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  amount INT UNSIGNED NOT NULL,
  method ENUM('bank_transfer','easypaisa','jazzcash','cash','other') NOT NULL DEFAULT 'cash',
  agent_user_id INT NOT NULL,
  next_payment_date DATE NULL,
  agent_note VARCHAR(250) DEFAULT NULL,
  receipt_path VARCHAR(255) DEFAULT NULL,
  payable_at_request INT UNSIGNED NOT NULL DEFAULT 0,
  remaining_at_request INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  reviewer_note VARCHAR(250) DEFAULT NULL,
  reviewed_by_user_id INT NULL,
  reviewed_at DATETIME NULL,
  payment_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_due_sale FOREIGN KEY (sale_id) REFERENCES sales(id),
  CONSTRAINT fk_due_agent FOREIGN KEY (agent_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE approval_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NOT NULL,
  action ENUM('approved','rejected') NOT NULL,
  by_user_id INT NOT NULL,
  note VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_sale FOREIGN KEY (sale_id) REFERENCES sales(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE expenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  amount INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE shareholder_spends (
  id INT AUTO_INCREMENT PRIMARY KEY,
  amount INT UNSIGNED NOT NULL,
  note VARCHAR(200) NOT NULL,
  created_by_user_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_shareholder_user FOREIGN KEY (created_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Seed Data (quick start)
```sql
INSERT INTO users(name,email,password,role) VALUES
('Admin','admin@example.com','Admin@123','admin'),
('Agent Ali','agent@example.com','Agent@123','agent');

INSERT INTO exam_boards(name) VALUES
('PTB'),('AJK'),('Federal'),('Afaq'),('PTB + Federal'),
('Federal + AJK + Afaq'),('PTB + Afaq'),('PTB + AJK');
```
> Passwords are plain text in controllers. Switch to `password_hash`/`password_verify` before production.

Routing & Rewrite
- Root `.htaccess` rewrites everything to `index.php`, which uses the custom router (`app/Router.php`).
- `public/proofs/.htaccess` disables PHP execution inside the upload folder.

Key Behaviors (high level)
- Session is isolated via `session_name('salesrecord_sid')` and scoped cookies to `base_url`.
- CSRF tokens on every POST; all controllers call `csrf_check`.
- Date windows: lists default to current month via `month_window()`; admin list supports `?date_from` / `?date_to` and `?status` filters.
- Agents: create new sales (customer type `new`), renewals (`old` with commission locked to zero), or pay dues for exact school+phone pairs. Rejected sales/due payments are the only ones editable.
- Admin: approve/reject (logs approval_logs and syncs `sale_payments` ledger), edit current-month sales, review due payments queue, view payments ledger with search + date filter, manage exam boards, expenses, users, and run annual report (`admin/reports/annual`). Shareholder screen holds 15% reserve of lifetime profit with spend tracking.
- Receipts: uploaded JPG/PNG only, 3MB max, stored deterministically as `sale_{id}.ext` or `pay_{id}.ext` (due payments use `duepend_*` temp names).

Running Locally
1) Create a MySQL DB and run the schema above. Insert at least one admin and agent.
2) Copy the project into a folder (e.g., `/salesrecord`) under your web root.
3) Update `config/config.php` for your local DSN/user/pass and set `base_url` to `/salesrecord`. Flip `$IS_PROD = false;` in `app/init.php` for verbose errors.
4) Ensure `public/proofs` and `storage` are writable by PHP. Keep both `.htaccess` files.
5) Visit `/salesrecord/`  -> login. Admin lands on `/admin/sales`; agent lands on `/agent/dashboard`.

Deploying to cPanel / Subfolder
- Upload the entire folder into `/public_html/salesrecord` (or similar).
- Set `base_url` to the full HTTPS URL (`https://yourdomain.com/salesrecord`).
- Update DB credentials for the live database and import the schema/seeds via phpMyAdmin.
- Confirm `.htaccess` files survived upload and Apache has `AllowOverride All` for rewrites.
- File uploads will go to `/public_html/salesrecord/public/proofs`.

Troubleshooting
- Redirect loops: ensure root `.htaccess` matches the one in repo (rewrite to `index.php?r=$1`).
- Blank/white page: set `$IS_PROD = false;` locally to surface errors; check `storage/php_errors.log` in prod.
- Upload failures: verify `public/proofs` exists and is writable; only JPG/PNG <= 3MB allowed.
- Login failing on fresh DB: controllers compare plain text. Make sure stored passwords exactly match what you type or refactor to hashes.

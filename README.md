# NCD 3D Print

A lightweight system for the **National Center for the Distinguished (NCD), Syria** to
organize 3D-printing requests from robotics teams.

Built to run on **free shared hosting** (e.g. InfinityFree) — plain PHP 8 + MySQL, no
Composer, no Node, no build step. Just upload the files and import one SQL file.

---

## ✨ Features

**For team members**
- Personal dashboard with request counts and status
- Submit a print request: project name, description, priority, desired color,
  **one project image**, and **one or more print files** (STL / 3MF / ZIP)
- Track each request through its stages (Submitted → Approved → Printing → Completed)
- Cancel a request while it is still awaiting approval

**For administrators**
- Dashboard with stat cards, **requests-by-month** and **filament-consumption** charts,
  waiting-approval list, currently-printing list, low-filament and busy-printer widgets
- Full request workflow: approve, reject, start printing (pick Printer 1/2 + spool),
  mark completed (auto-deducts filament), cancel, delete
- Enter estimated / actual filament weight and internal lab notes
- Team management (name, competition, supervisor)
- Account management (create members & admins, assign to teams, activate/deactivate)
- Filament inventory with rename, remaining-weight tracking and low-stock warnings
  (🟠 below 300 g, 🔴 below 100 g)
- Simple two-printer status board
- Search & filter requests by project, team, status, priority
- Activity log

**News banner (both roles)**
- The dashboard opens with a photo banner (shaded gradient overlay) showing the **latest news**,
  its **date/time** and author — visible to admins and team members alike
- The admin can publish an update (title, text, optional new image) straight from the banner;
  the previous item moves automatically into **الأخبار السابقة** (the news archive at `/news`)
- **Share on WhatsApp** button — admins only

**Design** — modern minimal UI inspired by GitHub / Notion / Linear: rounded cards,
clean tables, blue accent, responsive layout, and a **light/dark theme toggle**.

---

## 🔐 Sample logins

| Role   | Email           | Password    |
|--------|-----------------|-------------|
| Admin  | `admin@ncd.sy`  | `admin123`  |
| Member | `ahmad@ncd.sy`  | `member123` |
| Member | `rana@ncd.sy`   | `member123` |
| Member | `yousef@ncd.sy` | `member123` |

> **Change these immediately after your first sign-in** (Members page → edit account).
> There is no public registration — the administrator creates every account.

---

## 🧰 Tech stack

- PHP 8 (tested on 8.2) with **PDO** and prepared statements
- MySQL / MariaDB
- Simple hand-rolled **MVC** (router + base Controller/Model)
- Bootstrap 5 + Bootstrap Icons (via CDN) and a custom stylesheet
- Vanilla JavaScript (Fetch/AJAX-ready, no framework)
- Session authentication, CSRF protection, password hashing, file validation

No Laravel, Composer, Node, React/Vue/Angular, Docker, Redis, WebSockets, or SSH required.

---

## 📁 Folder structure

```
.
├── index.php            # front controller (routes every request)
├── .htaccess            # pretty URLs + basic hardening
├── database.sql         # schema + sample data
├── config/              # config.php (DB + settings), database.php (PDO)
├── core/                # Router, base Controller, base Model
├── helpers/             # Auth, Csrf, Flash, Upload, functions
├── controllers/         # Auth, Dashboard, Requests, Teams, Users, Filament, Printers, Activity
├── models/              # User, Team, PrintRequest, RequestFile, Filament, Printer, ActivityLog
├── views/               # layouts + pages (auth, dashboard, requests, teams, users, filament, printers, activity, errors)
├── assets/              # css/style.css, js/app.js
└── uploads/             # images/ and files/ (writable; protected by .htaccess)
```

**Database tables:** `users`, `teams`, `requests`, `request_files`, `filament`, `news`,
`printers`, `activity_logs` (normalized, InnoDB, with foreign keys).

---

## 🚀 Quick start

See **[INSTALL.md](INSTALL.md)** for full step-by-step instructions (InfinityFree and
local XAMPP). In short:

1. Create a MySQL database and import `database.sql`.
2. Copy `config/config.sample.php` to `config/config.php` and fill in your database
   host / name / user / password. (`config/config.php` is gitignored so real
   credentials are never committed.)
3. Upload all files to your web root (e.g. `htdocs`).
4. Make sure `uploads/` is writable.
5. Open the site and sign in with the admin account above.

---

## ⚙️ Configuration (`config/config.php`)

| Setting | Purpose |
|---|---|
| `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` | Database connection |
| `MAX_UPLOAD_MB` | Max size per uploaded file (also limited by host `php.ini`) |
| `ALLOWED_FILE_EXT` | Allowed print-file types (default: stl, 3mf, zip) |
| `ALLOWED_IMAGE_EXT` | Allowed image types |
| `FILAMENT_WARN_LOW` / `FILAMENT_WARN_CRIT` | Low-stock thresholds in grams (300 / 100) |
| `APP_DEBUG` | `true` shows errors — **set to `false` on a live server** |

---

## 🔁 Resetting a password manually

If you ever get locked out, open phpMyAdmin, run this on a PHP host to get a hash, or use
any bcrypt generator, then update the row:

```sql
-- password below is 'admin123'
UPDATE users
SET password = '$2y$10$smqCZ4YQwFuFYVj7cjuOyupRwyu8HEbkTI65OtJKwHQait5eoieUS'
WHERE email = 'admin@ncd.sy';
```

---

## 📝 Notes

- The two sample `request_files` rows point to placeholder paths; real files appear only
  after users upload them through the app.
- When the admin **approves** a request they pick a filament spool and enter the weight,
  which is subtracted from that spool immediately. If the request is later cancelled,
  rejected, or deleted, the filament is automatically returned to the spool.
- Each request can be exported as an official Arabic print form (letterhead with the three
  logos) once the admin has responded — including a rejection version with the reasons.
- The center has exactly two printers by design — no complex printer management.

---

## 🚢 Deployment

Pushing to `main` triggers the **Deploy to InfinityFree** GitHub Actions workflow
(`.github/workflows/deploy.yml`), which uploads the repository to `htdocs/` over FTP
using the `FTP_SERVER` / `FTP_USERNAME` / `FTP_PASSWORD` repository secrets.

Note that `config/config.php` is **not** in the repository, so the deployment never
overwrites the credentials already present on the server.

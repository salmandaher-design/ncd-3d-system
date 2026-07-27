# Installation Guide — NCD 3D Print

Two paths are covered:

- **A. InfinityFree** (free shared hosting) — the intended production target
- **B. Local (XAMPP / MAMP / WAMP)** — for testing on your own computer

---

## A. Deploy on InfinityFree

### 1. Create an account & site
1. Sign up at <https://infinityfree.net> and create a new hosting account (a free
   subdomain like `yourname.rf.gd`, or connect your own domain).
2. Wait until the account is activated (a few minutes).

### 2. Create the database
1. In the control panel open **MySQL Databases**.
2. Create a database. Note the values it shows you:
   - **Database host** (e.g. `sqlXXX.infinityfree.com`)
   - **Database name** (e.g. `if0_12345678_ncd`)
   - **Username** (e.g. `if0_12345678`)
   - **Password** (your account password)

### 3. Import the SQL
1. Open **phpMyAdmin** for that database from the panel.
2. Select the database on the left, go to the **Import** tab.
3. Choose `database.sql` from this project and click **Go**.
   - ⚠️ Leave the `CREATE DATABASE` / `USE` lines commented out (they already are).

### 4. Configure the app
Open `config/config.php` and set the four database constants to the values from step 2:

```php
define('DB_HOST', 'sqlXXX.infinityfree.com');
define('DB_NAME', 'if0_12345678_ncd');
define('DB_USER', 'if0_12345678');
define('DB_PASS', 'your-account-password');
```

Also set `define('APP_DEBUG', false);` for a live site.

### 5. Upload the files
1. Open the **File Manager** (or use FTP with the credentials from the panel).
2. Upload **the contents of this project** into the `htdocs` folder
   (so `index.php` sits directly inside `htdocs`).
3. Ensure the `uploads/` folder (and `uploads/images`, `uploads/files`) exists and is
   writable. On InfinityFree, folders are writable by default (permissions 755).

### 6. Sign in
Visit your site (e.g. `https://yourname.rf.gd`). You should see the login screen.
Sign in as **`admin@ncd.sy` / `admin123`** and then:
- change the admin password (Members → edit),
- create your real teams and member accounts,
- update the filament inventory and rename printers if needed.

**Tip:** InfinityFree caps uploads around **10 MB**. If members need bigger files, ask
them to zip and split, or upgrade hosting. `MAX_UPLOAD_MB` in `config.php` should not
exceed the host limit.

---

## B. Run locally with XAMPP

1. Install [XAMPP](https://www.apachefriends.org/) (PHP 8+).
2. Copy this project folder into `C:\xampp\htdocs\ncd` (or any name).
3. Start **Apache** and **MySQL** from the XAMPP control panel.
4. Open <http://localhost/phpmyadmin>, create a database named `ncd_printing`,
   then **Import** `database.sql`.
   - (Or uncomment the `CREATE DATABASE` / `USE` lines at the top of `database.sql`
     and import it without pre-creating the database.)
5. The default `config/config.php` already matches XAMPP
   (`localhost` / `root` / empty password / `ncd_printing`), so no edits are needed.
6. Visit <http://localhost/ncd/> and sign in with the sample admin account.

> Because the app uses clean URLs via `.htaccess`, keep Apache's `mod_rewrite` enabled
> (it is on by default in XAMPP).

---

## Troubleshooting

| Symptom | Fix |
|---|---|
| **500 error / blank page** | Set `APP_DEBUG` to `true` in `config.php` to see the message. Most often wrong DB credentials. |
| **"Database connection failed"** | Re-check `DB_HOST/NAME/USER/PASS`. On InfinityFree the host is **not** `localhost`. |
| **Clean URLs 404 (e.g. `/requests`)** | Ensure `.htaccess` was uploaded and `mod_rewrite` is enabled. |
| **Uploads fail** | Confirm `uploads/`, `uploads/images`, `uploads/files` exist and are writable, and the file is within `MAX_UPLOAD_MB` and the host's `upload_max_filesize`. |
| **Images/CSS not styled** | The layout loads Bootstrap from a CDN — make sure the server has internet access (InfinityFree does). |
| **Locked out** | Reset a password via phpMyAdmin (see the SQL snippet in `README.md`). |

---

## Post-install checklist

- [ ] Changed the default admin password
- [ ] Created real teams
- [ ] Created member accounts and assigned them to teams
- [ ] Updated filament spools (colors, materials, weights)
- [ ] Renamed the two printers if desired
- [ ] Set `APP_DEBUG` to `false`

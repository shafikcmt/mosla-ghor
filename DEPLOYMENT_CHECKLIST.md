# Deployment Checklist — মসলা ঘর

## Server Requirements
- [ ] PHP >= 8.2 with extensions: `pdo`, `pdo_sqlite` (or `pdo_mysql`), `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd` (for image validation)
- [ ] Composer >= 2.x

## First-Time Setup

### 1. Clone & Install
```bash
git clone <repo-url> mosla-ghor
cd mosla-ghor
composer install --no-dev --optimize-autoloader
```

### 2. Environment File
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set at minimum:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=sqlite          # or mysql
# If using MySQL:
# DB_HOST=127.0.0.1
# DB_DATABASE=mosla_ghor
# DB_USERNAME=dbuser
# DB_PASSWORD=secret

# If Steadfast API is enabled:
# (stored in DB via admin panel, not .env)
```

> **CRITICAL**: `APP_DEBUG=false` in production. Leaving it `true` exposes stack traces and `.env` values.

### 3. Database & Storage
```bash
# Create SQLite file (if using SQLite)
touch database/database.sqlite

php artisan migrate --force
php artisan db:seed --force

# Symlink storage so uploads are publicly accessible
php artisan storage:link
```

### 4. File Permissions (Linux/Mac)
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 5. Optimize for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Admin Access
- URL: `https://yourdomain.com/admin`
- Default email: `admin@mosla.test`
- Default password: `password`
- **Change the password immediately after first login.**

---

## Post-Deploy Verification (Smoke Tests)

| URL | Expected |
|-----|----------|
| `/` | 200 — Homepage loads |
| `/admin` | Redirect to `/admin/login` |
| `/admin/login` | 200 — Login form |
| `/admin/orders` | 200 (after login) |
| `/admin/settings` | 200 (after login) |
| `/admin/products` | 200 (after login) |

### Functional checks
- [ ] Place a test order (Cash on Delivery) — verify order appears in admin
- [ ] Verify stock decrements after order is placed
- [ ] Upload a payment screenshot — verify thumbnail appears in admin
- [ ] Print invoice from admin order page
- [ ] Print receipt from order success page

---

## Re-deploy (No Data Loss)

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Do **not** run `db:seed` on re-deploy unless intentionally resetting reference data (products, zones, couriers). All seeders are idempotent (safe if accidentally re-run — they use `updateOrCreate`/`firstOrCreate`).

---

## Steadfast Courier API (Optional)
Configure in admin: **Settings → Courier Settings → Steadfast**.
- Requires an active Steadfast merchant account
- Enter `Api-Key` and `Secret-Key` from the Steadfast portal
- Keys are stored encrypted in the database, never logged

---

## Ongoing Maintenance
- Payment screenshots are stored in `storage/app/public/payment-screenshots/`
- Logs are in `storage/logs/laravel.log`
- Clear caches after config changes: `php artisan optimize:clear`

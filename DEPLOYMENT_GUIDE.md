# 🚀 Nawala Checker - Deployment Guide

Complete guide untuk deploy Nawala Checker ke production dengan Supabase PostgreSQL.

---

## 📋 Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+ & NPM
- Supabase Account (sudah setup)
- Git

---

## 🗄️ Database Setup (Supabase)

### 1. Login ke Supabase Dashboard

1. Go to [https://supabase.com/dashboard](https://supabase.com/dashboard)
2. Login dengan akun Anda
3. Pilih project: **xdqeezcjirmdqkzuzcsi**

### 2. Run Initial SQL

1. Di Supabase Dashboard, go to **SQL Editor**
2. Click **New Query**
3. Copy seluruh isi file `initial.sql` dari repository
4. Paste ke SQL Editor
5. Click **Run** atau tekan `Ctrl+Enter`

SQL akan membuat:
- ✅ 12 tables dengan proper indexes
- ✅ Foreign key constraints
- ✅ Default data (admin user, tags, resolvers, groups)

### 3. Verify Database

Run query berikut untuk verify:

```sql
-- Check all tables created
SELECT table_name 
FROM information_schema.tables 
WHERE table_schema = 'public' 
AND table_name LIKE 'nc_%';

-- Should return 12 tables:
-- nc_tags, nc_groups, nc_resolvers, nc_vantage_nodes, 
-- nc_targets, nc_target_tag, nc_check_results, 
-- nc_shortlink_groups, nc_shortlinks, nc_shortlink_targets,
-- nc_notification_channels, nc_rotation_history

-- Check default data
SELECT COUNT(*) FROM nc_tags; -- Should be 5
SELECT COUNT(*) FROM nc_resolvers; -- Should be 4
SELECT COUNT(*) FROM nc_groups; -- Should be 1
```

---

## ⚙️ Application Setup

### 1. Clone Repository

```bash
git clone https://github.com/el-pablos/nawala-checker.git
cd nawala-checker
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node dependencies
npm install
```

### 3. Environment Configuration

File `.env` sudah ada di repository dengan Supabase credentials:

```env
# Database (Supabase PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=db.xdqeezcjirmdqkzuzcsi.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=vpsTaman1ch

# Supabase
SUPABASE_URL=https://xdqeezcjirmdqkzuzcsi.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
SUPABASE_SERVICE_ROLE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
SUPABASE_JWT_SECRET=o+EtlfVD3oOuAvWB7WEs0FUJ8NilEH5H...
```

**IMPORTANT**: Generate application key:

```bash
php artisan key:generate
```

### 4. Optional: Telegram Notifications

Jika ingin enable Telegram notifications, update `.env`:

```env
TELEGRAM_BOT_TOKEN=your_bot_token_here
```

Cara mendapatkan bot token:
1. Chat dengan [@BotFather](https://t.me/BotFather) di Telegram
2. Send `/newbot`
3. Follow instructions
4. Copy token yang diberikan

### 5. Build Frontend Assets

```bash
# Production build
npm run build

# Atau untuk development
npm run dev
```

### 6. Setup Storage & Cache

```bash
# Create storage symlink
php artisan storage:link

# Clear and optimize cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🔧 Queue & Scheduler Setup

### Queue Worker (Recommended)

Queue worker untuk background processing:

```bash
# Run queue worker
php artisan queue:work --tries=3 --timeout=300

# Atau dengan supervisor (production)
# Create file: /etc/supervisor/conf.d/nawala-checker-worker.conf
```

**Supervisor Config Example:**

```ini
[program:nawala-checker-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/nawala-checker/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/nawala-checker/storage/logs/worker.log
stopwaitsecs=3600
```

### Scheduler (Optional)

Add to crontab untuk auto-checks:

```bash
crontab -e
```

Add line:

```bash
* * * * * cd /path/to/nawala-checker && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🌐 Web Server Configuration

### Nginx

```nginx
server {
    listen 80;
    server_name nawala-checker.yourdomain.com;
    root /path/to/nawala-checker/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Apache

```apache
<VirtualHost *:80>
    ServerName nawala-checker.yourdomain.com
    DocumentRoot /path/to/nawala-checker/public

    <Directory /path/to/nawala-checker/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/nawala-checker-error.log
    CustomLog ${APACHE_LOG_DIR}/nawala-checker-access.log combined
</VirtualHost>
```

---

## ✅ Verification

### 1. Test Database Connection

```bash
php artisan tinker
```

```php
// In tinker
DB::connection()->getPdo();
// Should return PDO object without errors

\App\Models\NawalaChecker\Tag::count();
// Should return 5

\App\Models\NawalaChecker\Resolver::count();
// Should return 4
```

### 2. Test Application

```bash
php artisan serve
```

Visit: `http://localhost:8000`

### 3. Run Tests

```bash
php artisan test
```

All tests should pass.

---

## 🔐 Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production`
- [ ] Strong `APP_KEY` generated
- [ ] Database credentials secure
- [ ] `.env` file NOT in git (already in .gitignore)
- [ ] File permissions correct (755 for directories, 644 for files)
- [ ] `storage/` and `bootstrap/cache/` writable
- [ ] SSL/HTTPS enabled (recommended)
- [ ] Firewall configured
- [ ] Regular backups enabled

---

## 📊 Monitoring

### Application Logs

```bash
tail -f storage/logs/laravel.log
```

### Queue Logs

```bash
tail -f storage/logs/worker.log
```

### Database Monitoring

Use Supabase Dashboard:
- Go to **Database** → **Logs**
- Monitor query performance
- Check connection pool

---

## 🔄 Updates & Maintenance

### Pull Latest Changes

```bash
git pull origin main
composer install --optimize-autoloader --no-dev
npm install && npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Database Migrations (Future)

```bash
php artisan migrate --force
```

### Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## 🆘 Troubleshooting

### Database Connection Failed

1. Check Supabase project status
2. Verify credentials in `.env`
3. Check firewall/network
4. Test connection: `php artisan tinker` → `DB::connection()->getPdo();`

### Queue Not Processing

1. Check queue worker running: `ps aux | grep queue:work`
2. Check logs: `storage/logs/laravel.log`
3. Restart worker: `php artisan queue:restart`

### Frontend Not Loading

1. Rebuild assets: `npm run build`
2. Clear cache: `php artisan view:clear`
3. Check Vite manifest: `public/build/manifest.json` exists

---

## 📞 Support

- **GitHub Issues**: [https://github.com/el-pablos/nawala-checker/issues](https://github.com/el-pablos/nawala-checker/issues)
- **Email**: yeteprem.end23juni@gmail.com

---

**Deployment completed! 🎉**


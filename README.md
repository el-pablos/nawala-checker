# 🔍 Nawala Checker

**Nawala Checker** adalah aplikasi monitoring domain/URL 24/7 untuk mendeteksi pemblokiran (DNS/HTTP/HTTPS/SNI) oleh resolver/ISP termasuk Nawala, dengan kemampuan auto-rotation shortlink.

Built with **Laravel 11**, **Inertia.js**, **React**, **TypeScript**, dan **Supabase PostgreSQL**.

---

## ✨ Features

### 🎯 Core Features
- **Multi-Resolver Checking** - DNS, DoH (DNS over HTTPS), DoT (DNS over TLS)
- **Block Detection** - DNS filtering, HTTP block page, SNI blocking, RST detection
- **Shortlink Auto-Rotation** - Automatic failover dengan priority-based selection
- **Telegram Notifications** - Real-time alerts untuk status changes
- **Groups & Tags** - Flexible organization dengan shared settings
- **Dashboard & Statistics** - Real-time monitoring dan historical data

### 🔒 Security
- Input sanitization (XSS prevention)
- Rate limiting pada critical endpoints
- Permission-based access control
- SQL injection prevention via Eloquent ORM

### ⚡ Performance
- Database indexing untuk fast queries
- Caching strategy untuk frequently accessed data
- Queue processing untuk background tasks
- Optimized verdict fusion algorithm

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+ & NPM
- Supabase Account (PostgreSQL database)

### Installation

1. **Clone Repository**
   ```bash
   git clone https://github.com/el-pablos/nawala-checker.git
   cd nawala-checker
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Setup Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Edit `.env` dan update:
   - Database credentials (Supabase)
   - `TELEGRAM_BOT_TOKEN` (optional)

4. **Setup Database**

   Run `initial.sql` di Supabase SQL Editor:
   - Login ke [Supabase Dashboard](https://supabase.com/dashboard)
   - Pilih project Anda
   - Go to SQL Editor
   - Copy-paste isi file `initial.sql`
   - Run query

5. **Build Frontend**
   ```bash
   npm run build
   ```

6. **Start Application**
   ```bash
   php artisan serve
   ```

---

## 📖 Usage

### Creating Targets

1. Navigate to `/nawala-checker/targets`
2. Click "Add Target"
3. Fill in domain/URL, group, tags, check interval
4. Enable monitoring
5. Click "Create Target"

### Creating Shortlinks

1. Navigate to `/nawala-checker/shortlinks`
2. Click "Add Shortlink"
3. Fill in slug, group, and multiple targets
4. Set priority and weight for each target
5. Click "Create Shortlink"

---

## 🔧 Console Commands

```bash
# Run checks for all enabled targets
php artisan nawala:run-checks

# Run check for specific target
php artisan nawala:run-checks --target-id=123

# Auto-rotate shortlinks
php artisan nawala:auto-rotate
```

---

## 🛠️ Tech Stack

- **Backend**: Laravel 11, PHP 8.2+
- **Frontend**: React 18, TypeScript, Inertia.js
- **Database**: Supabase PostgreSQL
- **Styling**: Tailwind CSS
- **Build Tool**: Vite
- **Testing**: PHPUnit

---

## 📝 Architecture

### Service Layer Pattern
- `NawalaCheckerService` - Main orchestration
- `CheckRunnerService` - DNS/HTTP checking
- `ShortlinkRotationService` - Auto-rotation logic
- `TelegramNotifierService` - Notifications
- `CacheService` - Caching strategy

### Security Features
- `SanitizesInput` trait - XSS prevention
- `CheckPermission` middleware - Access control
- `RateLimitMiddleware` - Rate limiting
- Form Request validation

---

## 🤝 Contributing

Contributions are welcome! Please follow commit format:

```
<type>(<scope>): <subject>

Types: feat, fix, docs, refactor, test, chore
Scopes: nawala-checker, database, frontend, backend, tests
```

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

---

## 👥 Author

**el-pablos**
- GitHub: [@el-pablos](https://github.com/el-pablos)
- Email: yeteprem.end23juni@gmail.com

---

**Made with ❤️ for internet freedom monitoring**

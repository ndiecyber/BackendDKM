# DKM Shared Backend API

Shared backend REST API untuk ekosistem aplikasi DKM (Dewan Kemakmuran Masjid) Jami Kassiti, dibangun dengan **Laravel 12** dan **PostgreSQL**.


## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| Database | PostgreSQL |
| Authentication | Laravel Sanctum (Token-based) |
| Authorization | Spatie Laravel Permission |
| API Docs | Scramble (OpenAPI 3.1.0) |
| Testing | PHPUnit |
| Code Quality | Laravel Pint |
| CI/CD | GitHub Actions |

## Prerequisites

- PHP 8.4+
- Composer 2.x
- PostgreSQL 15+
- PHP Extensions: `pdo_pgsql`, `pgsql`, `mbstring`, `xml`, `curl`, `zip`, `bcmath`
- Git

---

## Setup — Windows

### 1. Install Prerequisites

**PHP & Composer:**
1. Download dan install [Laragon](https://laragon.org/download/) (Full) — sudah termasuk PHP, Composer, dan Git
2. Atau install manual:
   - [PHP 8.4](https://windows.php.net/download/) — extract ke `C:\php`, tambahkan ke `PATH`
   - [Composer](https://getcomposer.org/download/) — jalankan installer `.exe`

**PostgreSQL:**
1. Download dan install [PostgreSQL](https://www.postgresql.org/download/windows/) (versi 15+)
2. Saat instalasi, catat password untuk user `postgres`
3. Pastikan centang **pgAdmin** dan **Command Line Tools**

**Aktifkan PHP Extensions:**

Edit file `php.ini` (lokasi: `C:\php\php.ini` atau di folder Laragon), uncomment baris berikut (hapus `;` di depan):

```ini
extension=pdo_pgsql
extension=pgsql
extension=mbstring
extension=curl
extension=zip
extension=bcmath
extension=openssl
extension=fileinfo
```

### 2. Clone & Install

```powershell
git clone https://github.com/ndiecyber/BackendDKM.git
cd BackendDKM
composer install
```

### 3. Environment Configuration

```powershell
copy .env.example .env
php artisan key:generate
```

Edit `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=dkm_db
DB_USERNAME=postgres
DB_PASSWORD=your_password
API_DOMAIN=api.localhost
```

### 4. Database Setup

Buka **pgAdmin** atau **SQL Shell (psql)** lalu jalankan:

```sql
CREATE DATABASE dkm_db;
CREATE USER dkm_user WITH PASSWORD 'your_password';
GRANT ALL PRIVILEGES ON DATABASE dkm_db TO dkm_user;
ALTER DATABASE dkm_db OWNER TO dkm_user;
```

Atau via Command Prompt:

```powershell
psql -U postgres -c "CREATE DATABASE dkm_db;"
psql -U postgres -c "CREATE USER dkm_user WITH PASSWORD 'your_password';"
psql -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE dkm_db TO dkm_user;"
psql -U postgres -c "ALTER DATABASE dkm_db OWNER TO dkm_user;"
```

### 5. Run Migrations, Seeders & Serve

```powershell
php artisan migrate
php artisan db:seed
php artisan serve
```

Server berjalan di `http://localhost:8000`.

---

## Setup — Linux

### 1. Install Prerequisites

**Ubuntu / Debian:**

```bash
# PHP 8.4 + extensions
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install -y php8.4 php8.4-cli php8.4-pgsql php8.4-mbstring \
    php8.4-xml php8.4-curl php8.4-zip php8.4-bcmath php8.4-common unzip git

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# PostgreSQL
sudo apt install -y postgresql postgresql-client
sudo systemctl enable --now postgresql
```

**Fedora / RHEL:**

```bash
# PHP 8.4 + extensions
sudo dnf install -y php php-cli php-pgsql php-mbstring \
    php-xml php-curl php-zip php-bcmath php-common unzip git

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# PostgreSQL
sudo dnf install -y postgresql postgresql-server
sudo postgresql-setup --initdb
sudo systemctl enable --now postgresql
```

**Arch Linux:**

```bash
# PHP 8.4 + extensions
sudo pacman -S php php-pgsql composer postgresql git

# Aktifkan extensions di /etc/php/php.ini (uncomment):
# extension=pdo_pgsql
# extension=pgsql
# extension=bcmath

# PostgreSQL
sudo -u postgres initdb -D /var/lib/postgres/data
sudo systemctl enable --now postgresql
```

### 2. Clone & Install

```bash
git clone https://github.com/ndiecyber/BackendDKM.git
cd BackendDKM
composer install
```

### 3. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=dkm_db
DB_USERNAME=dkm_user
DB_PASSWORD=your_password
API_DOMAIN=api.localhost
```

### 4. Database Setup

```bash
sudo -u postgres psql -c "CREATE DATABASE dkm_db;"
sudo -u postgres psql -c "CREATE USER dkm_user WITH PASSWORD 'your_password';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE dkm_db TO dkm_user;"
sudo -u postgres psql -c "ALTER DATABASE dkm_db OWNER TO dkm_user;"
```

### 5. Run Migrations, Seeders & Serve

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

Server berjalan di `http://localhost:8000`.

---

## Docker Setup

Untuk local development maupun deployment ke VPS, sangat disarankan menggunakan Docker.

### 1. Prasyarat

- Install [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows/Mac) atau Docker Engine (Linux).
- Pastikan Port `80` (Nginx), `9000` (PHP), dan `5432` (PostgreSQL) tidak sedang dipakai aplikasi lain.

### 2. Konfigurasi Environment

Copy file `.env`:
```bash
cp .env.example .env
```
Karena kita menggunakan container, ubah host database di `.env` menjadi nama service Docker-nya (`db`):
```env
DB_HOST=db
DB_PASSWORD=secret
```

### 3. Menjalankan Docker Compose

Build dan jalankan container di *background*:
```bash
docker compose up -d --build
```

Setelah container berjalan (bisa dicek dengan `docker compose ps`), masuk ke container aplikasi untuk menginstall dependency dan migrasi database:
```bash
# Masuk ke container app
docker compose exec app bash

# Di dalam container, jalankan:
composer install
php artisan key:generate
php artisan migrate --seed

# Keluar dari container
exit
```

Aplikasi sekarang dapat diakses di `http://localhost`.

---

## API Endpoints

Base URL: `http://api.localhost:8000/v1`

### Authentication

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/auth/register` | Public | Register user baru |
| `POST` | `/auth/login` | Public | Login & dapatkan token |
| `POST` | `/auth/logout` | 🔒 Bearer | Logout & revoke token |
| `GET` | `/auth/me` | 🔒 Bearer | Profil user + roles |
| `POST` | `/auth/refresh` | 🔒 Bearer | Rotate token |

### Contoh Penggunaan

```bash
# Register
curl -X POST http://api.localhost:8000/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John","email":"john@example.com","password":"password123","password_confirmation":"password123"}'

# Login
curl -X POST http://api.localhost:8000/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"password"}'

# Access protected endpoint
curl http://api.localhost:8000/v1/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### API Documentation

Dokumentasi interaktif tersedia di:
- **UI**: `http://api.localhost:8000/docs/api`
- **OpenAPI JSON**: `http://api.localhost:8000/docs/api.json`

## Default Admin Account

| Field | Value |
|---|---|
| Email | `admin@dkm.local` |
| Username | `admin` |
| Password | `password` |
| Role | `superadmin` / `super-admin` |

> ⚠️ **Ganti password default setelah setup pertama kali.**

## Roles & Permissions

Seeder role mengikuti struktur manajemen akses frontend: `key`, `name`, `hierarchy`, dan `modules`.

| Key Frontend | Role Backend | Hierarchy | Modules | Deskripsi |
|---|---|---:|---|---|
| `superadmin` | `super-admin` | 1 | `web`, `keuangan`, `qurban`, `sistem` | Akses penuh ke semua fitur |
| `admin` | `admin` | 2 | `web`, `keuangan`, `qurban`, `sistem` | Admin umum untuk seluruh modul |
| `bendahara` | `bendahara` | 3 | `keuangan` | Pengelolaan kas, transaksi, dan laporan |
| `sekretaris` | `sekretaris` | 4 | `web` | Pengelolaan konten web dan data jamaah/profil |
| `viewer` | `viewer` | 99 | - | Read-only lewat permission granular |

Jalankan ulang seeder ini setelah deploy perubahan role/permission:

```bash
php artisan db:seed --class=RolePermissionSeeder
```

## Development

### Running Tests

```bash
# Semua tests
./vendor/bin/phpunit

# Filter specific tests
./vendor/bin/phpunit --filter=Auth

# Dengan coverage
./vendor/bin/phpunit --coverage-text
```

### Code Style (Linting)

```bash
# Fix code style
./vendor/bin/pint

# Check tanpa fixing
./vendor/bin/pint --test
```

### CORS

Frontend origins dikonfigurasi via `.env`:

```env
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173,http://localhost:8080
```

### Untuk Tim Frontend

1. Checkout branch yang sesuai: `git checkout feature/profile`
2. Develop di branch tersebut
3. Buat Pull Request ke `development`
4. Akan direview oleh tim Backend
5. Setelah Pass, akan dimerge ke `development` oleh tim Backend

## Project Structure

```
BackendDKM/
├── .github/workflows/ci.yml       ← CI/CD Pipeline
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/    ← API Controllers
│   │   ├── Middleware/             ← Custom middleware
│   │   └── Requests/Auth/         ← Form Requests
│   ├── Models/                     ← Eloquent Models
│   ├── Providers/                  ← Service Providers
│   └── Traits/                     ← Shared traits
├── config/                         ← Configuration files
├── database/
│   ├── migrations/                 ← Database migrations
│   └── seeders/                    ← Database seeders
├── routes/api.php                  ← API route definitions
├── tests/Feature/Auth/             ← Feature tests
├── .env.example                    ← Environment template
├── pint.json                       ← Code style config
└── phpunit.xml                     ← Test configuration
```

## License

Private — Internal DKM use only.

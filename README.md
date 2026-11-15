# Sports Field & Equipment Booking System

**Implementation language:** PHP 8.2+ with Laravel 12 · **Database:** MySQL / MariaDB

## Requirements
- PHP 8.2 or newer (extensions: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`)
- Composer 2
- MySQL or MariaDB running on `127.0.0.1:3306` (user `root`, empty password — or edit `DB_*` in `.env`)

## Setup and run
```bash
git clone https://github.com/abdullah3894/swe485-booking-system.git
cd swe485-booking-system
composer install
cp .env.example .env          # Windows CMD: copy .env.example .env
php artisan key:generate

# create the app and test databases
mysql -u root -e "CREATE DATABASE IF NOT EXISTS booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE IF NOT EXISTS booking_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# XAMPP on Windows: C:\xampp\mysql\bin\mysql.exe -u root -e "..."

php artisan migrate:fresh --seed
php artisan serve
```
Open http://127.0.0.1:8000

## Accounts
| Role | Email | Password |
|---|---|---|
| Supervisor | supervisor@example.com | Password123 |
| Member | member1@example.com | Password123 |

## Tests
```bash
php artisan test
```
Tests use the separate database `booking_test`.

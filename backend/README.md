# SelfShop Backend (Laravel API)

Laravel 8 API: auth, products, orders, vendor/wholesale, etc.

## Setup

```bash
composer install
cp .env.example .env   # if .env missing
php artisan key:generate
php artisan migrate    # or migrate --path=... for specific migrations
```

## Run

```bash
php artisan serve
```

API: `http://127.0.0.1:8000/api`

For vendor API and testing, see root [../docs/TESTING-VENDOR.md](../docs/TESTING-VENDOR.md).

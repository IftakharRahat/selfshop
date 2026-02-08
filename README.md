# SelfShop

Monorepo: Laravel API backend + Next.js frontend.

## Structure

| Folder    | Stack     | Description                |
|-----------|-----------|----------------------------|
| **backend** | Laravel 8 | API, auth, DB, vendor/wholesale |
| **Client**  | Next.js 16 | Frontend (pnpm)            |
| **docs**    | —         | Testing and setup notes    |

## Quick start

### Backend (API on port 8000)

```bash
cd backend
composer install
# Copy .env.example to .env and set DB_* if needed
php artisan serve
```

API base: `http://127.0.0.1:8000/api`

### Frontend (port 3000 or 3001)

```bash
cd Client
pnpm install
pnpm dev
```

Open: `http://localhost:3000`

### Env (Client)

In `Client/.env` use development API and image base:

- `NEXT_PUBLIC_BASE_URL=http://localhost:8000/api`
- `NEXT_PUBLIC_IMAGE_BASE=http://localhost:8000`

## Vendor (wholesale) testing

See [docs/TESTING-VENDOR.md](docs/TESTING-VENDOR.md).

## License

MIT (see backend and Client for details).

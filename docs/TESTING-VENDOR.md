# How to Test Vendor (Wholesale) Module

## 1. Backend (Laravel) – API

### 1.1 Start Laravel
```bash
cd c:\Users\Rahat\OneDrive\Documents\backup\backend
php artisan serve
```
API base: `http://127.0.0.1:8000/api`

### 1.2 Test without auth (public product – should work)
```bash
curl -s "http://127.0.0.1:8000/api/product-details/YOUR_PRODUCT_SLUG" | head -c 500
```
Replace `YOUR_PRODUCT_SLUG` with a real product slug from your DB (e.g. from `products.ProductSlug`).

### 1.3 Get a login token
```bash
curl -s -X POST "http://127.0.0.1:8000/api/login" ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"YOUR_EMAIL_OR_PHONE\",\"password\":\"YOUR_PASSWORD\"}"
```
Copy the `access_token` from the JSON response.

### 1.4 Mark your user as verified wholesaler (required for vendor API)
In MySQL or Laravel Tinker:
```bash
cd c:\Users\Rahat\OneDrive\Documents\backup\backend
php artisan tinker
```
Then:
```php
\User::where('email', 'YOUR_EMAIL')->update(['is_verified_wholesaler' => true]);
```
Or in SQL: `UPDATE users SET is_verified_wholesaler = 1 WHERE email = 'YOUR_EMAIL';`

### 1.5 Test vendor product details (with token)
```bash
curl -s "http://127.0.0.1:8000/api/vendor/product-details/YOUR_PRODUCT_SLUG" ^
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```
- **200**: You should see `product_details` and `price_tiers`.
- **403**: User is not verified wholesaler (do step 1.4).
- **401**: Invalid or missing token (do step 1.3).

### 1.6 Test bulk add to cart (with token)
```bash
curl -s -X POST "http://127.0.0.1:8000/api/vendor/bulk-add-to-cart" ^
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" ^
  -H "Content-Type: application/json" ^
  -d "{\"product_id\":1,\"items\":[{\"variant_label\":\"Red / S\",\"qty\":2,\"unit_price\":350,\"size\":\"S\"}]}"
```
- **201**: Cart rows created.
- **422**: Check body (product_id, items array required).

---

## 2. Frontend (Next.js) – UI

### 2.1 Start Next.js
```bash
cd Client
pnpm dev
```
Open: `http://localhost:3000`

### 2.2 Test vendor area (no subdomain)
- Go to: **http://localhost:3000/vendor**  
  You should see the Vendor (Wholesale) dashboard.
- Go to a product page, click **“Bulk Order (mixed sizes · tier discount)”**  
  You should land on `/vendor/product/{slug}` with the **Stepper Matrix** (variant list with [-] qty [+]) and **sticky footer** (Total Qty, Unit price, Total BDT).

### 2.3 Test with login
1. Log in on the main site (so Redux has `access_token`).
2. Ensure your user is **verified wholesaler** (step 1.4).
3. Open **http://localhost:3000/vendor/product/YOUR_PRODUCT_SLUG**.
4. Change quantities with the steppers; footer should update (tier discount if total qty ≥ 50 or 100).
5. Click **“Add to cart · X pcs”**.
   - If 201: you should be redirected and cart should have the new lines.
   - If 401/403: check token and `is_verified_wholesaler`.

### 2.4 Test with vendor subdomain (optional)
- Add to `C:\Windows\System32\drivers\etc\hosts`:  
  `127.0.0.1 vendor.localhost`
- Open **http://vendor.localhost:3000**  
  Same app, but middleware rewrites to the vendor UI (e.g. `/vendor`).

---

## 3. Quick checklist

| Step | What to check |
|------|----------------|
| Laravel running | `http://127.0.0.1:8000` responds |
| Public product API | `GET /api/product-details/{slug}` returns JSON |
| User verified | `users.is_verified_wholesaler = 1` for your user |
| Vendor product API | `GET /api/vendor/product-details/{slug}` with Bearer token returns 200 + `price_tiers` |
| Bulk cart API | `POST /api/vendor/bulk-add-to-cart` with token returns 201 |
| Next.js running | `http://localhost:3000/vendor` loads |
| Bulk Order button | On product page, link goes to `/vendor/product/{slug}` |
| Matrix + footer | Quantities and totals update when steppers are used |
| Add to cart (UI) | After “Add to cart”, no error and cart has new items (or redirect works) |

If any step fails, use the error message (401/403/422 or UI error) and the section above to fix it.
